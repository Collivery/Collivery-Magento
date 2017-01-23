<?php

/**
 * MDS Collivery Shipping Module
 * URL: https://github.com/Collivery/Collivery-Magento
 */
class MDS_Collivery_Model_Carrier_Collivery
	extends Mage_Shipping_Model_Carrier_Abstract
	implements Mage_Shipping_Model_Carrier_Interface
{

	// Unique internal shipping method identifier
	protected $_code = 'collivery';

	// Protected Cached MDS Variables
	protected $soap, $authenticate, $towns, $suburbs, $location_types,
	$client_address, $my_address, $addresses, $address_contact;

	/**
	 * Setup Soap Connection if not already active
	 *
	 * @return Soap Authentication
	 */
	private function soap_init()
	{
		// Check if soap session exists
		if (!$this->soap){
			// Start Soap Client
			$this->soap = new SoapClient("http://www.collivery.co.za/wsdl/v2");
			// Plugin and Host information
			$info = array('name' => 'Magento Shipping Module by MDS Collivery', 'version'=> (string) Mage::getConfig()->getNode()->modules->MDS_Collivery->version, 'host'=> 'Magento '. (string) Mage::getVersion());
			// Authenticate
			$authenticate = $this->soap->authenticate(Mage::helper('core')->decrypt($this->getConfigData('mds_user')), Mage::helper('core')->decrypt($this->getConfigData('mds_pass')), @$_SESSION['token'], $info);
			// Save Authentication token in session to identify the user again later
			$_SESSION['token'] = $authenticate['token'];

			if(!$authenticate['token']) {
				exit("Authentication Error : ".$authenticate['access']);
			}
			// Make authentication publically accessible
			$this->authenticate=$authenticate;
		}
		return $this->soap;
	}

	/**
	 * Collect rates for this shipping method based on information in $request
	 *
	 * @param Mage_Shipping_Model_Rate_Request $data
	 * @return Mage_Shipping_Model_Rate_Result
	 */
	public function collectRates(Mage_Shipping_Model_Rate_Request $request)
	{
		// Skip if not enabled
		if (!$this->getConfigFlag('active')) {
			return false;
		}

		// Get custom MDS fields from Checkout
		$checkout_session = Mage::getSingleton('checkout/session');
		$shipping_address = $checkout_session->getQuote()->getShippingAddress();

		$ship2billing = $shipping_address->getData('same_as_billing');
		$town = $this->get_code($this->get_towns(),$shipping_address->getRegion());

		// Skip if no values from Custom Fields recieved
		if (!isset($town)||$town==""||$town=="NA"){
			return FALSE;
		}

		$cptypes = $shipping_address->getMds_cptype();

		// Get cart items and put them in an Array or quit
		$items = $request->getAllItems();
		if ($items)
			$cart = $this->get_cart_content($items);
		else return false;

		// Get Available services from MDS
		$services = $this->get_services();

		foreach ($services as $key => $value) {
			// Get Shipping Estimate for current service
			$i=$this->get_shipping_estimate($town, $cptypes, $key, $cart);
			if ($i>1){
				// Create Response Array
				$response[] =
					Array(
						'code'    => $key,
						'title'   => $value,
						'cost'    => $i,
						'price'   => $i * (1+($this->getConfigData('markup')/100)),
					);
			}
		}

		// Result Object Returned
		$result = Mage::getModel('shipping/rate_result');

		foreach ($response as $rMethod) {
			$method = Mage::getModel('shipping/rate_result_method');

			// Record carrier information
			$method -> setCarrier($this -> _code);
			$method -> setCarrierTitle($this -> getConfigData('title'));

			// Record method information
			$method -> setMethod($rMethod['code']);
			$method -> setMethodTitle($rMethod['title']);

			// Record how much it costs to vendor to ship
			$method -> setCost($rMethod['cost']);

			// Price the client will be charged
			$method -> setPrice($rMethod['price']);

			// Add this rate to the result
			$result -> append($method);
		}

		return $result;
	}

	/**
	 * Create array with cart content for MDS
	 *
	 * @param Cart Items
	 * @return MDS Formatted Array with Cart Info
	 */
	function get_cart_content($items)
	{

		// Reset array to defaults
		$cart = array(
				'count' => 0,
				'weight' => 0,
				'parcels' => Array()
			);

		// Loop through every product in the cart
		foreach ($items as $item) {
			if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
				continue;
			}

			// If the product has children and is shipped seperately, get info for each child product
			if ($item->getHasChildren() && $item->isShipSeparately()) {
				foreach ($item->getChildren() as $child) {
					if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
						$product_id = $child->getProductId();
						$productObj = Mage::getModel('catalog/product')->load($product_id);

						// Get info from product
						$qty = $child->getQty();
						$weight = $child->getWeight();
						$width = $productObj->getData($this->getConfigData('widthLabel'));
						$length = $productObj->getData($this->getConfigData('lengthLabel'));
						$height = $productObj->getData($this->getConfigData('heightLabel'));

						$cart['count'] += $qty;
						$cart['weight'] += $weight * $qty;

						for ($i=0; $i<$qty; $i++)
							$cart['parcels'][] = array(
									'length' => $length,
									'width' => $width,
									'height' => $height,
									'weight' => $weight,
								);
					}
				}
			// Else get the data for current product
			} else {
				$product_id = $item->getProductId();
				$productObj = Mage::getModel('catalog/product')->load($product_id);

				// Get info from product
				$qty = $item->getQty();
				if (!isset($qty)) $qty = $item->getQtyOrdered();

				$weight = $item->getWeight();
				$width = $productObj->getData($this->getConfigData('widthLabel'));
				$length = $productObj->getData($this->getConfigData('lengthLabel'));
				$height = $productObj->getData($this->getConfigData('heightLabel'));

				$cart['count'] += $qty;
				$cart['weight'] += $weight * $qty;

				for ($i=0; $i<$qty; $i++)
					$cart['parcels'][] = array(
							'length' => $length,
							'width' => $width,
							'height' => $height,
							'weight' => $weight,
						);
			}
		}
		return $cart;
	}

	/**
	 * Get a shipping cost estimate from MDS based on current data.
	 *
	 * @return int Estimate
	 */
	function get_shipping_estimate($town_brief, $town_type, $service_type, $cart)
	{
		// Create MDS Data Array
		$data = array (
				//'from_town_id' => $my_address['address']['town_id'],
				//'from_town_type' => $my_address['address']['location_type'],
				'collivery_from' => $this->authenticate['default_address_id'],
				'to_town_id' => $town_brief,
				'service' => $service_type,
				'cover' => $this->getConfigData('risk_cover'),
				'parcel_count' => $cart['count'],
				'weight' => $cart['weight'],
				'parcels' => $cart['parcels'],
			);
		// If Location Type is set, add it to the array
		if ((isset($town_type)) && ($town_type!="NA"))
			$data['to_town_type'] = $town_type;

		$price = $this->get_price($data);
		if (is_array($price))
			return $price['inc_vat'];
		else
			return false;
	}

	/**
	 * Find key in Array with label as value
	 *
	 * @param array
	 * @param string Label
	 * @return key|bool
	 */
	function get_code($array, $label)
	{
		foreach($array as $key=>$value){
			if($label == $value){
				return $key;
			}
		}
		return false;
	}

	/****************************
	 *
	 * MDS Specific functions
	 *
	 ****************************/

	/**
	 * Retrieve list of available Services from MDS
	 *
	 * @return Array
	 */

	function get_services()
	{
		/* Uncomment the following lines of code if you'd like to edit/remove any services.
		 *
		 * 1: Overnight Before 10:00
		 * 2: Overnight Before 16:00
		 * 5: Road Freight Express
		 * 3: Road Freight
		 */

		/*return array(
				1 => "Overnight Before 10:00", // 1: Overnight Before 10:00
				2 => "Overnight before 16:00", // 2: Overnight Before 16:00
				5 => "Road Freight Express", //   5: Road Freight Express
				3 => "Road Freight" //            3: Road Freight
			);
		*/

		if (!isset($this->services))
		{
			try{
				$this->soap_init();
				$services = $this->soap->get_services($this->authenticate['token']);
				if (is_array($services['services'])&&!isset($services['error'])){
					$this->services = $services['services'];
				} else {
					$this->log("Error returning services! Recieved: ". $services, 3);
					return false;
				}
			} catch (SoapFault $e){
				$this->log("Error returning services! SoapFault: ". $e->faultcode ." - ". $e->getMessage(), 2);
				return false;
			}
		}
		return $this->services;
	}

	/**
	 * Retrieve list of Towns from MDS
	 *
	 * @return Array
	 */
	public function get_price($data)
	{
		try{
			$this->soap_init();
			$price = $this->soap->get_price($data,$_SESSION['token']);
			if (is_array($price['price'])&&!isset($price['error'])){
				return $price['price'];
			} else {
				$this->log("Error getting price! Recieved: ". $price, 3);
				return false;
			}
		} catch (SoapFault $e){
			$this->log("Error getting price! SoapFault: ". $e->faultcode ." - ". $e->getMessage(), 2);
			return false;
		}
		return $this->towns;
	}

	/**
	 * Retrieve list of Towns from MDS
	 *
	 * @return Array
	 */
	public function get_towns()
	{
		if (!isset($this->towns))
		{
			try{
				$this->soap_init();
				$towns = $this->soap->get_towns($this->authenticate['token']);
				if (is_array($towns['towns'])&&!isset($towns['error'])){
					$this->towns = $towns['towns'];
				} else {
					$this->log("Error returning towns! Recieved: ". $towns, 3);
					return false;
				}
			} catch (SoapFault $e){
				$this->log("Error returning towns! SoapFault: ". $e->faultcode ." - ". $e->getMessage(), 2);
				return false;
			}
		}
		return $this->towns;
	}

	/**
	 * Retrieve list of Suburbs from MDS
	 *
	 * @param string Town Name
	 * @param int Mode - 0: Return Array, 1: Return result, 2: Get town code and Return Array, 3: Get town code and Return Result
	 * @return Array
	 */
	public function get_suburbs($town, $mode = 1)
	{
		if ($mode>1){
			$town_code = $this->get_code($this->get_towns(),$town);
		} else {
			$town_code = $town;
		}

		if (!isset($this->suburbs[$town_code]))
		{
			try{
				$this->soap_init();
				$suburbs = $this->soap->get_suburbs($town_code,$this->authenticate['token']);
				if (is_array($suburbs['suburbs'])&&!isset($suburbs['error'])){
					$this->suburbs[$town_code] = $suburbs['suburbs'];
				} else {
					$this->log("Error returning suburbs! Recieved: ". $suburbs, 3);
					return false;
				}
			} catch (SoapFault $e){
				$this->log("Error returning suburbs! SoapFault: ". $e->faultcode ." - ". $e->getMessage(), 2);
				return false;
			}
		}
		return $this->suburbs[$town_code];
	}

	/**
	 * Retrieve list of CPTypes (Building types) from MDS
	 *
	 * @param Array
	 */
	public function get_location_types()
	{
		if (!isset($this->location_types))
		{
			try{
				$this->soap_init();
				$location_types = $this->soap->get_location_types($this->authenticate['token']);
				if (isset($location_types['results'])){
					$this->location_types = $location_types['results'];
				} else {
					$this->log("Error returning location types! Recieved: ". $location_types, 3);
					return false;
				}
			} catch (SoapFault $e){
				$this->log("Error returning location types! SoapFault: ". $e->faultcode ." - ". $e->getMessage(), 2);
				return false;
			}
		}
		return $this->location_types;
	}

	/**
	 * Retrieve default address for authenticated account
	 *
	 * @return Array
	 */
	public function get_my_address()
	{
		if (!isset($this->my_address)){
			$default_address_id = $this->authenticate['default_address_id'];
			$this->my_address = $this->get_address($default_address_id);
		}
		return $this->my_address;
	}

	public function get_my_info()
	{
		if (!isset($this->my_info)){
			$address = $this->get_my_address();
			if (is_array($address)){
				$contacts = $this->get_address_contacts($address['address_id']);
				if (is_array($contacts)){
					$first_contact_id = each($contacts);
					$my_info = array_merge($address, $contacts[$first_contact_id[0]]);
					$this->my_info = $my_info;
				} else return false;
			} else return false;
		}
		return $this->my_info;
	}

	/**
	 * Retrieve Client Address
	 *
	 * @param string Client ID
	 * @return Array
	 */
	public function get_address($address_id)
	{
		if (!isset($this->addresses[$address_id])){
			try{
				$this->soap_init();
				$address = $this->soap->get_address($address_id, $this->authenticate['token']);
				if (is_array($address))
					$this->addresses[$address_id] = $address['address'];
				else {
					$this->log("Error returning address ". $address_id ."! Array expected, recieved: ". $address, 3);
					return false;
				}
			} catch (SoapFault $e){
				$this->log("Error returning address ". $address_id ."! SoapFault: ". $e->faultcode ." - ". $e->getMessage(), 2);
				return false;
			}
		}
		return $this->addresses[$address_id];
	}

	/**
	 * Retrieve all the contacts for a given address
	 */
	public function get_address_contacts($address_id)
	{
		if (!isset($this->address_contact[$address_id])){
			try{
				$this->soap_init();
				$contacts = $this->soap->get_contacts($address_id, $this->authenticate['token']);
				if (is_array($contacts['contacts']))
					$this->address_contact[$address_id] = $contacts['contacts'];
				else {
					$this->log("Error returning contacts for address ". $address_id ."! Array expected, recieved: ". $address, 3);
					return false;
				}
			} catch (SoapFault $e){
				$this->log("Error returning contacts for address ". $address_id ."! SoapFault: ". $e->faultcode ." - ". $e->getMessage(), 2);
				return false;
			}
		}
		return $this->address_contact[$address_id];
	}

	/**
	 * Create a new address
	 */
	public function add_address($address_data)
	{
		try{
			$this->soap_init();
			$address_id = $this->soap->add_address($address_data,$this->authenticate['token']);
			if (isset($address_id['address_id'])&&isset($address_id['contact_id']))
				return $address_id;
			else {
				$this->log("Error creating new address! Recieved: ". print_r($address_id, true), 3);
				return false;
			}
		} catch (SoapFault $e){
			$this->log("Error creating new address address! SoapFault: ". $e->faultcode ." - ". $e->getMessage(), 2);
			return false;
		}
	}

	public function add_contact($contact_data)
	{
		try{
			$this->soap_init();
			$contact_id = $this->soap->add_contact($contact_data,$this->authenticate['token']);
			if (isset($contact_id['contact_id']))
				return $contact_id['contact_id'];
			else {
				$this->log("Error creating new address contact! Recieved: ". $address, 3);
				return false;
			}
		} catch (SoapFault $e){
			$this->log("Error creating new address contact! SoapFault: ". $e->faultcode ." - ". $e->getMessage(), 2);
			return false;
		}
	}

	public function validate_collivery($data)
	{
		try{
			$this->soap_init();
			$validation = $this->soap->validate_collivery($data,$this->authenticate['token']);
			if (!isset($validation['error'])||!isset($validation['error_id']))
				return $validation;
			else {
				$this->log("Error validating collivery! Recieved: ". $validation, 3);
				return false;
			}
		} catch (SoapFault $e){
			$this->log("Error validating collivery! SoapFault: ". $e->faultcode ." - ". $e->getMessage(), 2);
			return false;
		}
	}

	public function register_shipping($data)
	{
		try{
			$this->soap_init();
			$collivery = $this->soap->add_collivery($data,$this->authenticate['token']);
			if($collivery['collivery_id']) {
				$collivery_id = $collivery['collivery_id'];
				//$send_emails = 1;
				$accepted = $this->soap->accept_collivery($collivery_id, $this->authenticate['token']);
				if ($accepted['result'] != "Accepted"){
					$this->log("Error accepting new collivery! Recieved: ". $accepted, 3);
					$collivery['error'] = 'Error accepting collivery!';
				}
			} else {
				$this->log("Error adding new collivery! Recieved: ". $collivery, 3);
				return false;
			}
		} catch (SoapFault $e){
			$this->log("Error creating new collivery! SoapFault: ". $e->faultcode ." - ". $e->getMessage(), 2);
			return false;
		}
		return $collivery;
	}

	public function get_status($collivery_id)
	{
		try{
			$this->soap_init();
			$status = $this->soap->get_collivery_status($collivery_id, $this->authenticate['token']);
			if (!isset($status['error'])||!isset($status['error_id']))
				return $status;
			else {
				$this->log("Error fetching collivery status! Recieved: ". $status, 3);
				return false;
			}
		} catch (SoapFault $e){
			$this->log("Error fetching collivery status! SoapFault: ". $e->faultcode ." - ". $e->getMessage(), 2);
			return false;
		}
	}

	/**
	 * This method is used when viewing / listing Shipping Methods with Codes programmatically
	 */
	public function getAllowedMethods()
	{
		return array($this -> _code => 'Collivery');
	}

	private function log($text, $level = null) {
		Mage::log($text, $level, 'mds_collivery.log');
	}

}

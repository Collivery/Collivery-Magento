<?php

/**
 * MDS Collivery Shipping Module
 * URL: https://github.com/Xethron/magento-mds-collivery
 */
class MDS_Collivery_Model_Carrier_Collivery
extends Mage_Shipping_Model_Carrier_Abstract
implements Mage_Shipping_Model_Carrier_Interface {

	// Unique internal shipping method identifier
	protected $_code = 'collivery';

	// Protected Cached MDS Variables
	protected $soap, $authenticate, $towns, $suburbs, $cptypes, $client_address, $my_address;
	
	/**
	 * Setup Soap Connection if not already active
	 * 
	 * @return Soap Authentication
	 */
	private function soap_init(){
		// Check if soap session exists
		if (!$this->soap){
			// Start Soap Client
			$this->soap = new SoapClient("http://www.collivery.co.za/webservice.php?wsdl");
			// Prevent caching of the wsdl
			ini_set("soap.wsdl_cache_enabled", "0");
			// Authenticate
			$authenticate = $this->soap->Authenticate(Mage::helper('core')->decrypt($this->getConfigData('mds_user')), Mage::helper('core')->decrypt($this->getConfigData('mds_pass')), $_SESSION['token']);
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
	public function collectRates(Mage_Shipping_Model_Rate_Request $request) {

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
		$services = $this->get_available_services();
		foreach ($services['results'] as $key => $value) {
			// Get Shipping Estimate for current service
			$i=$this->get_shipping_estimate($town, $cptypes, $key, $cart['max_weight']);
			// Create Response Array
			$response[] =
					Array(
						'code' => $key,
						'title' => $value,
						'cost' => $i,
						'price' => $i * (1+($this->getConfigData('markup')/100)),
					);
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
	function get_cart_content($items){
	
		// Reset array to defaults
		$cart = array(
				'count' => 0,
				'weight' => 0,
				'max_weight' => 0,
				'products' => Array()
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
						
						// Work out Volumetric Weight based on MDS's calculations
						$vol_weight = (($length * $width * $height) / 4000);
						
						if ($vol_weight>$weight)
							$cart['max_weight'] += $vol_weight * $qty;
						else
							$cart['max_weight'] += $weight * $qty;
						
						for ($i=0; $i<$qty; $i++)
							$cart['products'][] = array(
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
				$weight = $item->getWeight();
				$width = $productObj->getData($this->getConfigData('widthLabel'));
				$length = $productObj->getData($this->getConfigData('lengthLabel'));
				$height = $productObj->getData($this->getConfigData('heightLabel'));
				
				$cart['count'] += $qty;
				$cart['weight'] += $weight * $qty;
				
				// Work out Volumetric Weight based on MDS's calculations
				$vol_weight = (($length * $width * $height) / 4000);
				
				if ($vol_weight>$weight)
					$cart['max_weight'] += $vol_weight * $qty;
				else
					$cart['max_weight'] += $weight * $qty;
				
				for ($i=0; $i<$qty; $i++)
					$cart['products'][] = array(
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
	function get_shipping_estimate($town_brief, $town_type, $service_type, $weight){
		// Load default address for current account (Vendor)
		$my_address = $this->my_address();
		// Create MDS Data Array
		$data = array (
				'from_town_brief' => $my_address['results']['TownBrief'],
				'from_town_type' => $my_address['results']['CP_Type'],
				'to_town_brief' => $town_brief,
				'service_type' => $service_type,
				'mds_cover' => true,
				'weight' => $weight,
			);
		// If Location Type is set, add it to the array
		if ((isset($town_type)) && ($town_type!="NA"))
			$data['to_town_type'] = $town_type;
		
		$this->soap_init();
		
		$pricing = $this->soap->GetPricing($data,$_SESSION['token']);
		
		return $pricing['results']['Total'];
	}
	
	/**
	 * Find key in Array with label as value
	 * 
	 * @param array
	 * @param string Label
	 * @return key|bool
	 */
	function get_code($array, $label){
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
	
	function get_available_services(){
		$this->soap_init();
		$services = $this->soap->getServices($this->authenticate['token']);
		return $services;
	}
	
	/**
	 * Retrieve list of Towns from MDS
	 * 
	 * @return Array
	 */
	public function get_towns($mode = 1){
		if (!isset($this->towns))
		{
			$this->soap_init();
			$this->towns = $this->soap->getTowns(null,$this->authenticate['token']);
		}
		if ($mode==1){
			if (isset($this->towns['results']))
				return $this->towns['results'];
			else
				return false;
		} else{
			return $this->towns;
		}
	}
	
	/**
	 * Retrieve list of Suburbs from MDS
	 * 
	 * @param string Town Name
	 * @param int Mode - 0: Return Array, 1: Return result, 2: Get town code and Return Array, 3: Get town code and Return Result
	 * @return Array
	 */
	public function get_suburbs($town, $mode = 1){
		if ($mode>1){
			$town_code = $this->get_code($this->get_towns(),$town);
			$mode -= 2;
		} else {
			$town_code = $town;
		}
		
		if (!isset($this->suburbs[$town_code]))
		{
			$this->soap_init();
			$this->suburbs[$town_code] = $this->soap->getSuburbs(null,$town_code,$this->authenticate['token']);
		}
		if ($mode==1){
			if (isset($this->suburbs[$town_code]['results']))
				return $this->suburbs[$town_code]['results'];
			else
				return false;
		} else{
			return $this->suburbs[$town_code];
		}
	}
	
	/**
	 * Retrieve list of CPTypes (Building types) from MDS
	 * 
	 * @param Array
	 */
	public function get_cptypes($mode = 1){
		if (!isset($this->cptypes))
		{
			$this->soap_init();
			$this->cptypes = $this->soap->getCPTypes($this->authenticate['token']);
		}
		if ($mode==1){
			if (isset($this->cptypes['results']))
				return $this->cptypes['results'];
			else
				return false;
		} else{
			return $this->cptypes;
		}
	}
	
	/**
	 * Retrieve default address for authenticated account
	 * 
	 * @return Array
	 */
	public function my_address()
	{
		if (!isset($this->my_address)){
			$default_address_id = $this->authenticate['DefaultAddressID'];
			$this->my_address = $this->get_client_address($default_address_id);
			$this->my_address['address_id'] = $default_address_id;
		}
		return $this->my_address;
	}
	
	/**
	 * Retrieve Client Address
	 * 
	 * @param string Client ID
	 * @return Array
	 */
	public function get_client_address($cpid)
	{
		if (!isset($this->client_address[$cpid])){
			$this->soap_init();
			$this->client_address[$cpid] = $this->soap->getClientAddresses(null,null,$cpid,null,$this->authenticate['token']);
			$this->client_address[$cpid]['address_id']=$this->client_address[$cpid]['results']['colliveryPoint_PK'];
		}
		return $this->client_address[$cpid];
	}

	/**
	 * This method is used when viewing / listing Shipping Methods with Codes programmatically
	 */
	public function getAllowedMethods() {
		return array($this -> _code => 'Collivery');
	}

}

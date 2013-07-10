<?php

class MDS_Collivery_Block_Adminhtml_Sales_Order_Collivery extends Mage_Adminhtml_Block_Widget
{
	public $order;
	public $address;
	public $collivery;
	
	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate( 'collivery/order.phtml' );
		
		$this->order = $this->getOrder();
		$this->address = $this->order->getShippingAddress();
		$this->collivery = Mage::getModel('mds_collivery/carrier_collivery');
	}
	
	public function getOrder()
	{
		return Mage::registry( 'current_order' );
	}
	
	public function validate($client_info, $my_info)
	{
		$shipping_method = $this->order->shipping_method;
		$service = substr($shipping_method, -1);
		$collivery_data = $this->collivery->get_cart_content($this->order->getAllItems());
		$collivery_data['collivery_from']=$my_info['address_id'];
		$collivery_data['contact_from']=$my_info['contact_id'];
		$collivery_data['collivery_to']=$client_info['address_id'];
		$collivery_data['contact_to']=$client_info['contact_id'];
		$collivery_data['collivery_type']=2;
		$collivery_data['service']=$service;

		return $this->collivery->validate($collivery_data);
	}
	
	public function getAddressId($address, $contact)
	{
		$results = array();
		
		$client_address_id = $this->address->getMds_address_id();
		$client_address_hash = $this->address->getMds_address_hash();
		if (isset($client_address_id)&&$client_address_hash==md5(implode(',', $address)))
		{
			
			$results['address_id'] = $client_address_id;
			$results['message'] = "<p class=\"green\"><strong>Address already added, using previous ID</strong></p>";
		} else {
			$address_id = $this->collivery->addAddress($address);
			if(isset($address_id['error_message'])) {
				$error['error'] = "Error - ".$address_id['error_message'];
				return $error;
			} else {
				$this->address->setMds_address_id($address_id['results']['address_id']);
				$this->address->setMds_address_hash(md5(implode(',', $address)));
				$contact_text = "";
				if (isset($address_id['results']['contact_id'])){
					$this->address->setMds_contact_id($address_id['results']['contact_id']);
					$this->address->setMds_contact_hash(md5(implode(',', $contact)));
					$contact_text = "and Contact";
					$results['contact_id'] = $address_id['results']['contact_id'];
				}
				$this->address->save();
				
				$results['address_id'] = $address_id['results']['address_id'];
				$results['message'] = "<p class=\"green\"><strong>Address $contact_text Successfully added to Collivery</strong></p>";
			}
		}
		return $results;
	}

	public function getContactId($contact)
	{
		$results = array();
		
		$client_contact_id = $this->address->getMds_contact_id();
		$client_contact_hash = $this->address->getMds_contact_hash();
		if (isset($client_contact_id)&&$client_contact_hash==md5(implode(',', $contact)))
		{
			$results['contact_id'] = $client_contact_id;
			$results['message'] = "<p class=\"green\"><strong>Contact already added, using previous ID</strong></p>";
		} else {
			$client_id = $this->collivery->addContact($contact);
			if(isset($client_id['error_message'])){
				$error['error'] = "Error - ".$client_id['error_message'];
				return $error;
			} else {
				$this->address->setMds_contact_id($client_id['results']['contact_id']);
				$this->address->setMds_contact_hash(md5(implode(',', $contact)));
				$this->address->save();
				$results['contact_id'] = $client_id['results']['contact_id'];
				$results['message'] = "</strong></p><p class=\"green\"><strong>Address and Contact added succesfully!</strong></p>";
			}
		}
		return $results;
	}
	
	public function addClient($address, $contact)
	{
		$results = array();
		
		$address_id = $this->getAddressId($address, $contact);
		
		if(isset($address_id['error'])) {
			$error['error'] = $address_id['error'];
			return $error;
		} else {
			$contact['cpid'] = $address_id['address_id'];
			$results['address_id'] = $address_id['address_id'];
			$results['message'][]=$address_id['message'];
			
			if(isset($address_id['contact_id'])){
				$results['contact_id'] = $address_id['contact_id'];
			} else {
				$contact_id = $this->getContactId($contact);
				if(isset($contact_id['error'])) {
					$error['error'] = $contact_id['error'];
					return $error;
				} else {
					$results['contact_id'] = $contact_id['contact_id'];
					$results['message'][]=$contact_id['message'];
				}
			}
			return $results;
		}
	}
	
	public function getAddress()
	{
		//$cptypes = $this->collivery->get_cptypes();
		$cptype = $this->address->getMds_cptype();
		
		$towns = $this->collivery->get_towns();
		$town = $this->collivery->get_code($towns, $this->address->getRegion());
		
		$suburbs = $this->collivery->get_suburbs($town);
		$suburb = $this->collivery->get_code($suburbs, $this->address->getCity());
		
		//$my_info=$this->collivery->my_info();
		
		$street = $this->address->getStreet();
		return array(
			'company_name'=>$this->address->getCompany(),
			'address_type'=>$cptype,
			'town_id'=>$town,
			'TownBrief'=>$town,
			'suburb_id'=>$suburb,
			'building'=>$this->address->getMds_building(),
			'street'=>implode(', ', $street),
			'full_name'=>$this->address->getName(),
			'phone'=>$this->address->getTelephone(),
			'email'=>$this->address->getEmail(),
			);
	}
	
	public function getContact()
	{
		return array(
			'fname'=>$this->address->getName(),
			'cellNo'=>$this->address->getTelephone(),
			'emailAddr'=>$this->address->getEmail(),
			);
	}
	
	/**
	 * Completes the Shipment, followed by completing the Order life-cycle
	 * It is assumed that the Invoice has already been generated
	 * and the amount has been captured.
	 */
	public function completeShipment($waybill)
	{
		/**
		 * Provide the Shipment Tracking Number,
		 * which will be sent out by any warehouse to Magento
		 */
		$shipmentTrackingNumber = $waybill;
	
		/**
		 * This can be blank also.
		 */
		$customerEmailComments = '';
		
		$order = $this->getOrder();
	
		if (!$order->getId()) {
			Mage::throwException("Error loading Order.");
		}
	
		if ($order->canShip()) {
			try {
				$shipment = Mage::getModel('sales/service_order', $order)
								->prepareShipment($this->_getItemQtys($order));
	
				/**
				 * Carrier Codes can be like "ups" / "fedex" / "custom",
				 * but they need to be active from the System Configuration area.
				 * These variables can be provided custom-value, but it is always
				 * suggested to use Order values
				 */
				$shipmentCarrierCode = 'Collivery';
				$shipmentCarrierTitle = 'MDS Collivery';
	
				$arrTracking = array(
					'carrier_code' => isset($shipmentCarrierCode) ? $shipmentCarrierCode : $order->getShippingCarrier()->getCarrierCode(),
					'title' => isset($shipmentCarrierTitle) ? $shipmentCarrierTitle : $order->getShippingCarrier()->getConfigData('title'),
					'number' => $shipmentTrackingNumber,
				);
	
				$track = Mage::getModel('sales/order_shipment_track')->addData($arrTracking);
				$shipment->addTrack($track);
	
				// Register Shipment
				$shipment->register();
	
				// Save the Shipment
				$this->_saveShipment($shipment, $order, $customerEmailComments);
	
				// Finally, Save the Order
				$this->_saveOrder($order);
			} catch (Exception $e) {
				throw $e;
			}
		}
	}
	
	/**
	 * Get the Quantities shipped for the Order, based on an item-level
	 * This method can also be modified, to have the Partial Shipment functionality in place
	 *
	 * @param $order Mage_Sales_Model_Order
	 * @return array
	 */
	protected function _getItemQtys(Mage_Sales_Model_Order $order)
	{
		$qty = array();
	
		foreach ($order->getAllItems() as $_eachItem) {
			if ($_eachItem->getParentItemId()) {
				$qty[$_eachItem->getParentItemId()] = $_eachItem->getQtyOrdered();
			} else {
				$qty[$_eachItem->getId()] = $_eachItem->getQtyOrdered();
			}
		}
	
		return $qty;
	}
	
	/**
	 * Saves the Shipment changes in the Order
	 *
	 * @param $shipment Mage_Sales_Model_Order_Shipment
	 * @param $order Mage_Sales_Model_Order
	 * @param $customerEmailComments string
	 */
	protected function _saveShipment(Mage_Sales_Model_Order_Shipment $shipment, Mage_Sales_Model_Order $order, $customerEmailComments = '')
	{
		$shipment->getOrder()->setIsInProcess(true);
		$transactionSave = Mage::getModel('core/resource_transaction')
							   ->addObject($shipment)
							   ->addObject($order)
							   ->save();
	
		$emailSentStatus = $shipment->getData('email_sent');
		if (!is_null($emailSentStatus) && !$emailSentStatus) {
			$shipment->sendEmail(true, $customerEmailComments);
			$shipment->setEmailSent(true);
		}
	
		return $this;
	}
	
	/**
	 * Saves the Order, to complete the full life-cycle of the Order
	 * Order status will now show as Complete
	 *
	 * @param $order Mage_Sales_Model_Order
	 */
	protected function _saveOrder(Mage_Sales_Model_Order $order)
	{
		$order->setData('state', Mage_Sales_Model_Order::STATE_COMPLETE);
		$order->setData('status', Mage_Sales_Model_Order::STATE_COMPLETE);
	
		$order->save();
	
		return $this;
	}
}
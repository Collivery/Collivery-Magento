<?php
class MDS_Shipping_Block_Adminhtml_Checkout_Order extends Mage_Adminhtml_Block_Sales_Order_Abstract{
	public function getMdsVars(){
		$model = Mage::getModel('collivery/shipping_quote');
		return $model->getByOrder($this->getOrder()->getId());
	}
}
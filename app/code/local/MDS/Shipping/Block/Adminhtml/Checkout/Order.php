<?php
class MDS_Shipping_Block_Adminhtml_Checkout_Order extends Mage_Adminhtml_Block_Sales_Order_Abstract{
	public function getMdsVars(){
		$model = Mage::getModel('mds_collivery/checkout_quote');
		return $model->getByOrder($this->getOrder()->getId());
	}
}
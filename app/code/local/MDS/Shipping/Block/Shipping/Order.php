<?php
class MDS_Shipping_Block_Shipping_Order extends Mage_Core_Block_Template{
	public function getMdsVars(){
		$model = Mage::getModel('mds_shipping/shipping_quote');
		return $model->getByOrder($this->getOrder()->getId());
	}
	public function getOrder()
	{
		return Mage::registry('current_order');
	}
}
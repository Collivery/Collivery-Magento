<?php
class MDS_Collivery_Block_Shipping_Order extends Mage_Core_Block_Template{
	public function getMdsVars(){
		$model = Mage::getModel('mds_collivery/shipping_order');
		return $model->getByOrder($this->getOrder()->getId());
	}
	public function getOrder()
	{
		return Mage::registry('current_order');
	}
}
<?php
class MDS_Collivery_Block_Adminhtml_Shipping_Order extends Mage_Adminhtml_Block_Sales_Order_Abstract{
	public function getMdsVars(){
		$model = Mage::getModel('mds_collivery/shipping_order');
		return $model->getByOrder($this->getOrder()->getId());
	}
}
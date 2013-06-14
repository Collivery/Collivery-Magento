<?php

class MDS_Collivery_Block_Adminhtml_Sales_Order_Collivery extends Mage_Adminhtml_Block_Widget
{
	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate( 'collivery/order.phtml' );
	}
	
	public function getOrder()
    {
        return Mage::registry( 'current_order' );
    }

}
<?php

require_once "Mage/Adminhtml/controllers/Sales/OrderController.php";

class MDS_Collivery_Adminhtml_Sales_OrderController extends Mage_Adminhtml_Sales_OrderController
{
	public function mdsAction()
	{
		$this->_title($this->__('Sales'))->_title($this->__('Orders'))->_title($this->__('MDS Collivery'));
		if ($order = $this->_initOrder()) {
			$this->_initAction();

			$this->renderLayout();
		}
	}
}
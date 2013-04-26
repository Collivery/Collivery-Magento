<?php

class MDS_Shipping_Model_Mysql4_Checkout_Quote_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {
	public function _construct() {
		parent::_construct();
		$this -> _init('mds_collivery/checkout_quote');
	}
}

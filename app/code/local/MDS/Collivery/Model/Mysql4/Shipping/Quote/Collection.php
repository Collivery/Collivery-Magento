<?php

class MDS_Collivery_Model_Mysql4_Shipping_Quote_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {
	public function _construct() {
		parent::_construct();
		$this -> _init('mds_collivery/shipping_quote');
	}
}

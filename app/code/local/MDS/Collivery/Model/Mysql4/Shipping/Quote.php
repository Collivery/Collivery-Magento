<?php
class MDS_Collivery_Model_Mysql4_Shipping_Quote extends Mage_Core_Model_Mysql4_Abstract{
	public function _construct()
	{
		$this->_init('mds_collivery/shipping_quote', 'id');
	}
	public function deteleByQuote($quote_id,$var){
		$table = $this->getMainTable();
		$where = $this->_getWriteAdapter()->quoteInto('quote_id = ? AND ', $quote_id)
		.$this->_getWriteAdapter()->quoteInto('`key` = ? 	', $var);
		$this->_getWriteAdapter()->delete($table,$where);
	}
	public function getByQuote($quote_id,$var = ''){
		$table = $this->getMainTable();
		$where = $this->_getReadAdapter()->quoteInto('quote_id = ?', $quote_id);
		if(!empty($var)){
			$where .= $this->_getReadAdapter()->quoteInto(' AND `key` = ? ', $var);
		}
		$sql = $this->_getReadAdapter()->select()->from($table)->where($where);
		$rows = $this->_getReadAdapter()->fetchAll($sql);
		$return = array();
		foreach($rows as $row){
			$return[$row['key']] = $row['value'];
		}
		return $return;
	}
}
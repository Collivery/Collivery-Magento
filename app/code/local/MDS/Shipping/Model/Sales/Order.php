<?php
class MDS_Shipping_Model_Sales_Order extends Mage_Sales_Model_Order{
	public function hasMdsFields(){
		$var = $this->getSuburb();
		if($var && !empty($var)){
			return true;
		}else{
			return false;
		}
	}
	public function getFieldHtml(){
		$var = $this->getSuburb();
		$html = '<b>Suburb:</b>'.$var.'<br/>';
		return $html;
	}
}
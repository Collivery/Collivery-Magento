<?php
class MDS_Collivery_Model_Sales_Order extends Mage_Sales_Model_Order{
	public function hasMdsFields(){
		$var = $this->getMds();
		if($var && !empty($var)){
			return true;
		}else{
			return false;
		}
	}
	public function getFieldHtml(){
		$var = $this->getMds();
		$html = '<b>Suburb:</b>'.$var.'<br/>';
		return $html;
	}
}
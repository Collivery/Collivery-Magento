<?php
class MDS_Shipping_AjaxController extends Mage_Core_Controller_Front_Action
{
	public function suburbAction()
	{
		$model = Mage::getModel('mds_shipping/carrier_collivery');
		
		foreach ($model->get_suburbs($_POST['town']) as $key => $value) {
			echo '<option value="'. $key .'">'. $value .'</option>';
		}
	}
	
	public function cptypesAction()
	{
		$model = Mage::getModel('mds_shipping/carrier_collivery');
		
		foreach ($model->get_cptypes() as $key => $value) {
			echo '<option value="'. $key .'">'. $value .'</option>';
		}
	}

}

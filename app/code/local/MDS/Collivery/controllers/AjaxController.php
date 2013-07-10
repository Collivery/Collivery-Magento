<?php
class MDS_Collivery_AjaxController extends Mage_Core_Controller_Front_Action
{
	public function suburbAction()
	{
		$model = Mage::getModel('mds_collivery/carrier_collivery');
		
		foreach ($model->get_suburbs($_POST['town'], 3) as $key => $value) {
			echo '<option value="'. $key .'">'. $value .'</option>';
		}
	}
	
	public function cptypesAction()
	{
		$model = Mage::getModel('mds_collivery/carrier_collivery');
		
		foreach ($model->get_cptypes() as $key => $value) {
			echo '<option value="'. $key .'">'. $value .'</option>';
		}
	}
	
	public function baseAction()
	{
		echo "var BASE_URL = \"". Mage::getBaseUrl() ."\";";
	}

}

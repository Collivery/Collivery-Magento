<?php
class MDS_Collivery_TrackingController extends Mage_Core_Controller_Front_Action
{
	public function indexAction()
	{
		$waybill = $this->getRequest()->getParam('id');

		echo "<!DOCTYPE html><html><head><title>MDS Parcel Tracking</title></head><body>";
		echo "<iframe src=\"http://quote.collivery.co.za/tracking.php";
		if(isset($waybill)){
			 echo "?Check=1&waybillNo=$waybill";
		}
		echo "\" style=\"width: 100%; height: 100%; border: none;\"></iframe>";
		echo "</body></html>";
	}

}

<?php
class MDS_Shipping_AjaxController extends Mage_Core_Controller_Front_Action
{
	public function suburbAction()
	{
		$model = Mage::getModel('mds_shipping/carrier_collivery');
		
		foreach ($model->get_suburbs($_POST['town']) as $suburb) {
			echo '<option value="'. $suburb .'">'. $suburb .'</option>';
		}
	}
	
	public function suburbLayoutAction()
	{
		echo '
		<div class="field">
			<label class="required" for="mds:billing_suburb"><em>*</em>Suburb</label>
			<div class="input-box">
				<select class="mds-billing required-entry" title="Town" name="mds[billing_suburb]" id="mds:billing_suburb" defaultvalue="">
					<option value="">Please select a Town first</option>
				</select>
			</div>
		</div>';
	}
		
}

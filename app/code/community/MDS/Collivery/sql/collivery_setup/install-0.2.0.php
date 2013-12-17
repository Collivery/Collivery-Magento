<?php
//******************************** SOAP ********************************
// Prevent caching of the wsdl
$options = array('cache_wsdl' => WSDL_CACHE_NONE);
// Start Soap Client
try{
	$soap = new SoapClient("http://www.collivery.co.za/wsdl/v2");
} catch (SoapFault $e) {
	exit('Error starting soap client!');
}
// Plugin and Host information
$info = array('name' => 'Magento Shipping Module Installer by MDS Collivery', 'version'=> (string) Mage::getConfig()->getNode()->modules->MDS_Collivery->version, 'host'=> 'Magento '. (string) Mage::getVersion());
// Authenticate
try{
	$authenticate = $soap->authenticate('demo@collivery.co.za', 'demo', '', $info);
} catch (SoapFault $e) {
	exit('Error authenticating to MDS Webserver!');
}
if(!$authenticate['token']) {
	exit("Authentication Error : ".$authenticate['access']);
}

$towns = $soap->get_towns($authenticate['token']);
$town_sql = '';

foreach ($towns['towns'] as $key => $value) {
	$town_sql .= "('ZA', '". addslashes($key) ."', '". addslashes($value) ."'),";
}
// Replace last ',' with ';'
$town_sql = substr($town_sql, 0, -1) . ';';
//**************************** END OF SOAP ****************************

$installer = $this;

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

$installer->startSetup();

$dimention_attributes = array(
	'length'=>'Shipping Length',
	'width'=>'Shipping Width',
	'height'=>'Shipping Height'
);

foreach ($dimention_attributes as $key => $value) {
	$setup->addAttribute('catalog_product', $key, array(
		'group'         => 'General',
		'input'         => 'text',
		'type'          => 'decimal',
		'label'         => $value,
		'backend'       => '',
		'visible'       => true,
		'required'      => false,
		'user_defined'  => true,
		'searchable'    => false,
		'filterable'    => false,
		'comparable'    => false,
		'visible_on_front' => true,
		'visible_in_advanced_search'  => false,
		'is_html_allowed_on_front' => false,
		'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
	));
}

$address_attributes = array(
	'mds_building'            => array(
		'label'           => 'Building Details',
		'type'            => 'varchar',
		'input'           => 'text',
		'user_defined'    => 1,
		'system'          => 0,
		'visible'         => 1,
		'required'        => 0,
		'validate_rules'  => array(
			'max_text_length' => 255,
			'min_text_length' => 1
		),
	),
	'mds_cptype'            => array(
		'label'           => 'Location Type',
		'type'            => 'int',
		'input'           => 'text',
		'user_defined'    => 1,
		'system'          => 0,
		'visible'         => 1,
		'required'        => 0,
	),
);

foreach ($address_attributes as $attributeCode => $data) {
	$installer->addAttribute('customer_address', $attributeCode, array());
	Mage::getSingleton('eav/config')
		->getAttribute('customer_address', $attributeCode)
		->setData('used_in_forms', array(
					'customer_register_address',
					'customer_address_edit',
					'adminhtml_customer_address',
				))
		->save();
}

$installer->run("
	DELETE FROM {$this->getTable('directory_country_region')} WHERE `country_id` = 'ZA';

	INSERT INTO {$this->getTable('directory_country_region')} (`country_id`, `code`, `default_name`) VALUES
	" . $town_sql
);

$installer->run("
	ALTER TABLE {$this->getTable('sales_flat_quote_address')} ADD COLUMN `mds_building` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL AFTER `fax`;
	ALTER TABLE {$this->getTable('sales_flat_order_address')} ADD COLUMN `mds_building` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL AFTER `fax`;
	ALTER TABLE {$this->getTable('sales_flat_quote_address')} ADD COLUMN `mds_cptype` INT(11) NULL AFTER `mds_building`;
	ALTER TABLE {$this->getTable('sales_flat_order_address')} ADD COLUMN `mds_cptype` INT(11) NULL AFTER `mds_building`;
	ALTER TABLE {$this->getTable('sales_flat_order_address')} ADD COLUMN `mds_address_id` INT(11) NULL AFTER `mds_cptype`;
	ALTER TABLE {$this->getTable('sales_flat_order_address')} ADD COLUMN `mds_contact_id` INT(11) NULL AFTER `mds_cptype`;
	ALTER TABLE {$this->getTable('sales_flat_order_address')} ADD COLUMN `mds_address_hash` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL AFTER `mds_address_id`;
	ALTER TABLE {$this->getTable('sales_flat_order_address')} ADD COLUMN `mds_contact_hash` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL AFTER `mds_contact_id`;
	ALTER TABLE {$this->getTable('sales_flat_order_address')} ADD COLUMN `mds_waybill` INT NULL AFTER `mds_address_hash`
");

$installer->endSetup();
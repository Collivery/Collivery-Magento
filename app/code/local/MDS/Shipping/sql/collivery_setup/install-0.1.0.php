<?php
//******************************** SOAP ********************************
$soap = new SoapClient("http://www.collivery.co.za/webservice.php?wsdl");
// Prevent caching of the wsdl
ini_set("soap.wsdl_cache_enabled", "0");
// Authenticate
$authenticate = $soap->Authenticate('demo@collivery.co.za', 'demo', '');

if(!$authenticate['token']) {
	exit("Authentication Error : ".$authenticate['access']);
}

$towns = $soap->getTowns(null,$authenticate['token']);
$town_sql = '';

foreach ($towns['results'] as $key => $value) {
	$town_sql .= "('ZA', '". addslashes($key) ."', '". addslashes($value) ."'),";
}
// Replace last ',' with ';'
$town_sql = substr($town_sql, 0, -1) . ';';
//**************************** END OF SOAP ****************************

$installer = $this;

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

$installer->startSetup();

$installer->run("

CREATE TABLE IF NOT EXISTS mds_collivery_order (
  `id` int(11) unsigned NOT NULL auto_increment,
  `order_id` int(11) unsigned NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS mds_collivery_quote (
  `id` int(11) unsigned NOT NULL auto_increment,
  `quote_id` int(11) unsigned NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DELETE FROM `directory_country_region` WHERE `country_id` = 'ZA';

INSERT INTO `directory_country_region` (`country_id`, `code`, `default_name`) VALUES
" . $town_sql);

$attributes = array('length'=>'Length','width'=>'Width','height'=>'Height');

foreach ($attributes as $key => $value) {
	$setup->addAttribute('catalog_product', $key, array(
		'group'         => 'General',
		'input'         => 'text',
		'type'          => 'dec',
		'label'         => $value,
		'backend'       => '',
		'visible'       => TRUE,
		'required'      => TRUE,
		'user_defined'  => TRUE,
		'searchable'    => FALSE,
		'filterable'    => FALSE,
		'comparable'    => FALSE,
		'visible_on_front' => FALSE,
		'visible_in_advanced_search'  => FALSE,
		'is_html_allowed_on_front' => FALSE,
		'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
	));
}
$installer->endSetup();
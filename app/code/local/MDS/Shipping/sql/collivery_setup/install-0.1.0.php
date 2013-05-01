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

$installer->startSetup();

$installer->run("

CREATE TABLE IF NOT EXISTS mds_collivery_order (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `town` varchar(16) NOT NULL,
  `suburb` int(11) NOT NULL,
  `cptype` int(11) NOT NULL,
  `company` varchar(255) NOT NULL,
  `building_details` text NOT NULL,
  `street_no` varchar(8) NOT NULL,
  `street_name` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `cell_phone` varchar(16) NOT NULL,
  `email` varchar(255) NOT NULL,
  `notes` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS mds_collivery_quote (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) NOT NULL,
  `town` varchar(16) NOT NULL,
  `suburb` int(11) NOT NULL,
  `cptype` int(11) NOT NULL,
  `company` varchar(255) NOT NULL,
  `building_details` text NOT NULL,
  `street_no` varchar(8) NOT NULL,
  `street_name` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `cell_phone` varchar(16) NOT NULL,
  `email` varchar(255) NOT NULL,
  `notes` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;

DELETE FROM `directory_country_region` WHERE `country_id` = 'ZA';

INSERT INTO `directory_country_region` (`country_id`, `code`, `default_name`) VALUES
" . $town_sql);

$installer->endSetup();
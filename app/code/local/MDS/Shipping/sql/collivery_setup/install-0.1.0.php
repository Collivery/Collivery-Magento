<?php

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");

$installer->endSetup();
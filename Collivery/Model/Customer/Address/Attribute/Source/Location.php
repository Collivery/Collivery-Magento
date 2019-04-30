<?php

namespace MDS\Collivery\Model\Customer\Address\Attribute\Source;

use MDS\Collivery\Model\MdsCollivery;

class Location extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{

    public $_collivery;
    public $_town;

    public function __construct($town=null)
    {
        $this->_town = 360;

        $config = [
            'app_name'      => 'Default App Name', // Application Name
            'app_version'   => '0.0.1',            // Application Version
            'app_host'      => '', // Framework/CMS name and version, eg 'Wordpress 3.8.1 WooCommerce 2.0.20' / 'Joomla! 2.5.17 VirtueMart 2.0.26d'
            'app_url'       => '', // URL your site is hosted on
            'user_email'    => 'api@collivery.co.za',
            'user_password' => 'api123',
            'demo'          => false,
        ];

        $collivery = new MdsCollivery($config);
        $this->_collivery = $collivery;
    }

    public function getAllOptions($withEmpty = true, $defaultValues = false)
    {
        if (!$this->_options) {
            $this->_options = $this->getLocations();
        }

        return $this->_options;
    }

    public function getLocations()
    {
        $locations = $this->_collivery->getLocationTypes();

        foreach ($locations as $key => $location) {
            $suburb_field[] =
                [
                    'value' => $key,
                    'label' => $location,
                ];
        }

        //echo "<br>orange<br>";
        //var_dump([$suburb_field]);
        //print_r([$suburb_field]);
        //die();

        return $suburb_field;
    }
}

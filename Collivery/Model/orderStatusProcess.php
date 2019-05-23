<?php

namespace MDS\Collivery\Model;

use MDS\Collivery\Orders\ProcessOrder;

class orderStatusProcess implements ProcessOrder
{
    private $_collivery;

    public function __construct()
    {
        $config = [
            'app_name'      => 'Default App Name', // Application Name
            'app_version'   => '0.0.1',            // Application Version
            'app_host'      => '', // Framework/CMS name and version, eg 'Wordpress 3.8.1 WooCommerce 2.0.20' / 'Joomla! 2.5.17 VirtueMart 2.0.26d'
            'app_url'       => '', // URL your site is hosted on
            'user_email'    => 'api@collivery.co.za',
            'user_password' => 'api123',
            'demo'          => false,
        ];

        $this->_collivery = new MdsCollivery($config);
    }

    public function addAddress($params)
    {
        return $this->_collivery->addAddress($params);
    }

    public function addContactAddress($params)
    {
        return $this->_collivery->addContact($params);
    }

    public function validateCollivery($params)
    {
        return $this->_collivery->validate($params);
    }

    public function addCollivery($params)
    {
        return $this->_collivery->addCollivery($params);
    }

    public function acceptWaybill($params)
    {
        return $this->_collivery->acceptCollivery($params);
    }

    public function getShopperOwnerDetails()
    {
        return $this->_collivery->getContacts($this->_collivery->getDefaultAddressId());
    }
}

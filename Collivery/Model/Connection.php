<?php

namespace MDS\Collivery\Model;

class Connection
{
    private $_collivery;

    public function __construct($username = null, $password = null)
    {
        $config = [
            'app_name'      => 'Magento', // Application Name
            'app_version'   => '2.3.1', // Application Version
            'app_host'      => 'Magento ver. 2.3.1', // Framework/CMS name and version, eg 'Wordpress 3.8.1 WooCommerce 2.0.20' / 'Joomla! 2.5.17 VirtueMart 2.0.26d'
            'app_url'       => '', // URL your site is hosted on
            'user_email'    => $username ?? 'api@collivery.co.za',
            'user_password' => $password ?? 'api123',
            'demo'          => false,
        ];

        $this->_collivery = new MdsCollivery($config);
    }

    public function getConnection()
    {
        return $this->_collivery;
    }
}
<?php

namespace MDS\Collivery\Model;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Config\Model\Config;

class Connection
{
    private $collivery;
    private $objectManager;
    private $config;

    const USERNAME_XML_CONFIG_PATH = 'carriers/collivery/username';
    const PASSWORD_XML_CONFIG_PATH = 'carriers/collivery/password';
    public function __construct()
    {
        $this->objectManager = ObjectManager::getInstance();
        $productMetadata = $this->objectManager->get(ProductMetadataInterface::class);
        $config = $this->objectManager->get(Config::class);
        $username = $config->getConfigDataValue(self::USERNAME_XML_CONFIG_PATH);
        $password = $config->getConfigDataValue(self::PASSWORD_XML_CONFIG_PATH);

        $this->config = [
            'app_name'      => $productMetadata->getName(), // Application Name
            'app_version'   =>  $productMetadata->getVersion(), // Application Version
            'app_host'      => "{$productMetadata->getName()} Version. {$productMetadata->getVersion()}", // Framework/CMS name and version, eg 'Wordpress 3.8.1 WooCommerce 2.0.20' / 'Joomla! 2.5.17 VirtueMart 2.0.26d'
            'app_url'       => '', // URL your site is hosted on
            'user_email'    => $username,
            'user_password' => $password,
            'demo'          => false,
        ];

        $this->collivery = new MdsCollivery($this->config);
    }

    /**
     * @return MdsCollivery
     */
    public function getConnection()
    {
        return $this->collivery;
    }
}
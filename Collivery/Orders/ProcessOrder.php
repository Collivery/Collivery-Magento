<?php

namespace MDS\Collivery\Orders;

use MDS\Collivery\Model\Connection;

abstract class ProcessOrder
{
    /**
     * @var \MDS\Collivery\Model\Connection
     */
    private $_collivery;
    private $objectManager;

    /** @var \Psr\Log\LoggerInterface $logger */
    private $logger;

    public function __construct()
    {
        $collivery = new Connection();
        $this->_collivery = $collivery->getConnection();
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->logger = $this->objectManager->get('Psr\Log\LoggerInterface');
    }

    /**
     * @param $params
     *
     * @return array
     */
    public function addAddress($params)
    {
        return $this->_collivery->addAddress($params);
    }

    /**
     * @param $params
     *
     * @return int
     */
    public function addContactAddress($params)
    {
        return $this->_collivery->addContact($params);
    }

    /**
     * @param $params
     *
     * @return array
     */
    public function validateCollivery($params)
    {
        return $this->_collivery->validate($params);
    }

    /**
     * @param $params
     *
     * @return int
     */
    public function addCollivery($params)
    {
        return $this->_collivery->addCollivery($params);
    }

    /**
     * @param $params
     *
     * @return array
     */
    public function acceptWaybill($params)
    {
        return $this->_collivery->acceptCollivery($params);
    }

    /**
     * @return array
     */
    public function getShopOwnerDetails()
    {
        return $this->_collivery->getContacts($this->_collivery->getDefaultAddressId());
    }

    /**
     * @return string
     */
    public function getErrors()
    {
        $this->logger->error(print_r($this->_collivery->getErrors(), true));

        return implode(' ', $this->_collivery->getErrors());
    }
}

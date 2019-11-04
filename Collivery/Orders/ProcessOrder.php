<?php

namespace MDS\Collivery\Orders;

use Magento\Config\Model\Config;
use Magento\Framework\App\ObjectManager;
use MDS\Collivery\Exceptions\NoConfigCredentialsException;
use MDS\Collivery\Model\Connection;

abstract class ProcessOrder
{
    /**
     * @var \MDS\Collivery\Model\Connection
     */
    private $collivery;
    protected $objectManager;

    /** @var \Psr\Log\LoggerInterface $logger */
    private $logger;

    /**
     * ProcessOrder constructor.
     *
     * @throws NoConfigCredentialsException
     */
    public function __construct()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->logger = $this->objectManager->get('Psr\Log\LoggerInterface');

        $connection = new Connection();
        $this->collivery = $connection->getConnection();

    }

    /**
     * @param $params
     *
     * @return array
     */
    public function addAddress($params)
    {
        return $this->collivery->addAddress($params);
    }

    /**
     * @param $params
     *
     * @return int
     */
    public function addContactAddress($params)
    {
        return $this->collivery->addContact($params);
    }

    /**
     * @param $params
     *
     * @return array
     */
    public function validateCollivery($params)
    {
        return $this->collivery->validate($params);
    }

    /**
     * @param $params
     *
     * @return int
     */
    public function addCollivery($params)
    {
        return $this->collivery->addCollivery($params);
    }

    /**
     * @param $params
     *
     * @return array
     */
    public function acceptWaybill($params)
    {
        return $this->collivery->acceptCollivery($params);
    }

    /**
     * @return array
     */
    public function getShopOwnerDetails()
    {
        return $this->collivery->getContacts($this->collivery->getDefaultAddressId());
    }

    /**
     * @return string
     */
    public function getErrors()
    {
        $this->logger->error(print_r($this->collivery->getErrors(), true));

        return implode(' ', $this->collivery->getErrors());
    }
}

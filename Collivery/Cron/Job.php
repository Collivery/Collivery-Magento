<?php

namespace MDS\Collivery\Cron;

use Magento\Framework\App\ObjectManager;
use Psr\Log\LoggerInterface;

class Job
{
    private $logger;

    public function __construct()
    {
        $objectManager = ObjectManager::getInstance();
        $this->logger = $objectManager->get(LoggerInterface::class);
    }

    public function execute()
    {
        $this->logger->debug('Enabled MDS_Collivery module.');
        exec('php ../../../../bin/magento module:enable MDS_Collivery');
        $this->logger->debug('Run indexer:reindex.');
        exec('php ../../../../bin/magento indexer:reindex');
        $this->logger->debug('Run setup:upgrade.');
        exec('php ../../../../bin/magento setup:upgrade');
        $this->logger->debug('Run cache:flush.');
        exec('php ../../../../bin/magento bin/magento cache:flush');
    }
}

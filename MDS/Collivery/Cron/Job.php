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
        $this->logger->debug('Run indexer:reindex.');
        exec('php -f ' . BP . '/bin/magento indexer:reindex');
        $this->logger->debug('Run setup:upgrade.');
        exec('php -f ' . BP . '/bin/magento setup:upgrade');
        $this->logger->debug('Run cache:flush.');
        exec('php -f ' . BP . '/bin/magento bin/magento cache:flush');
    }
}

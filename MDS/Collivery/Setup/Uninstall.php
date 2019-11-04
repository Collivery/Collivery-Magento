<?php

namespace MDS\Collivery\Setup;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Psr\Log\LoggerInterface;

class Uninstall implements UninstallInterface
{
    private $logger;

    public function __construct()
    {
        $objectManager = ObjectManager::getInstance();
        $this->logger = $objectManager->get(LoggerInterface::class);
    }

    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        //remove attribute in forms
        $customFields = ['location', 'town', 'suburb'];
        $position = 333;

        // get quote_address table
        $quoteAddressTable = $setup->getTable('quote_address');
        $salesOrderTable = $setup->getTable('sales_order');
        foreach ($customFields as $field) {
            $source = 'MDS\Collivery\Model\Customer\Address\Attribute\Source\\' . ucfirst($field);
            $this->eavSetup->removeAttribute(
                AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
                $field,
                [
                    'label' => ucfirst($field),
                    'input' => 'select',
                    'source' => $source,
                    'visible' => true,
                    'required' => false,
                    'position' => $position,
                    'sort_order' => 150,
                    'system' => false
                ]
            );

            //remove ['location', 'town', 'suburb'] in quote_address
            $this->removeCustomAttributes($setup, $quoteAddressTable, $field);

            $position += 1;
        }
        //remove collivery_id in sales order
        $this->removeCustomAttributes($setup, $salesOrderTable, 'collivery_id');

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param                      $eavTable
     * @param                      $field
     */
    private function removeCustomAttributes(SchemaSetupInterface $setup, $eavTable, $field)
    {
        // Check if the table already exists
        if ($setup->getConnection()->isTableExists($eavTable) == true) {
            $connection = $setup->getConnection();

            // delete ['location', 'town', 'suburb'] in quote_address
            $connection->dropColumn($eavTable, $field);
            $this->logger->debug("Done dropping column $field in $eavTable");
        }
    }
}

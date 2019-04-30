<?php

namespace MDS\Collivery\Setup;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    /**
     * Attribute Code of the Custom Attribute
     */
    const CUSTOM_ATTRIBUTE_CODE = 'custom';
    /**
     * @var EavSetup
     */
    private $eavSetup;
    /**
     * @var Config
     */
    private $eavConfig;
    /**
     * InstallData constructor.
     * @param EavSetup $eavSetup
     * @param Config $config
     */
    public function __construct(
        EavSetup $eavSetup,
        Config $config
    ) {
        $this->eavSetup = $eavSetup;
        $this->eavConfig = $config;
    }
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $customFields = ['location', 'town', 'suburb'];
        foreach ($customFields as $field) {
            $source = 'MDS\Collivery\Model\Customer\Address\Attribute\Source\\' . ucfirst($field);
            $this->eavSetup->addAttribute(
                AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
                $field,
                [
                    'label' => ucfirst($field),
                    'input' => 'select',
                    'source' => $source,
                    'visible' => true,
                    'required' => false,
                    'position' => 150,
                    'sort_order' => 150,
                    'system' => false
                ]
            );
            $customAttribute = $this->eavConfig->getAttribute(
                AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
                $field
            );
            $customAttribute->setData(
                'used_in_forms',
                ['adminhtml_customer_address', 'customer_address_edit', 'customer_register_address']
            );
            $customAttribute->save();

            $customerTables = ['quote_address', 'sales_order_address', 'customer_address_entity'];

            foreach ($customerTables as $table) {
                $installer->getConnection()->addColumn(
                    $installer->getTable($table),
                    $field,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 100,
                        'comment' => 'custom field ' . $field,
                    ]
                );
            }
        }

        $installer->endSetup();
    }
}

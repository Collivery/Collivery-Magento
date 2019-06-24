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
        $position = 333;
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
                    'position' => $position,
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

            //Add ['location', 'town', 'suburb'] in quote_address
            $this->addTableColumns(
                $setup,
                'quote_address',
                $field,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                100,
                'custom field ' . $field
            );

            $position += 1;
        }

        //Add collivery_id in sales order for tracking
        $this->addTableColumns(
            $setup,
            'sales_order',
            'collivery_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            11,
            'MDS Collivery waybill no'
        );

        $installer->endSetup();
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param string                   $table
     * @param string                   $column
     * @param                          $type
     * @param int                      $length
     * @param string                   $comment
     *
     * @return void
     */
    public function addTableColumns(ModuleDataSetupInterface $setup, $table, $column, $type, $length, $comment)
    {
        $installer = $setup;
        $installer->getConnection()->addColumn(
            $installer->getTable($table),
            $column,
            [
                'type' => $type,
                'length' => $length,
                'comment' => $comment,
            ]
        );
    }
}

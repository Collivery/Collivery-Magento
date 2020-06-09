<?php

namespace MDS\Collivery\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();
        $customFields = ['location', 'town', 'suburb'];
        foreach ($customFields as $field) {
            //Add ['location', 'town', 'suburb'] in quote_address
            $this->addTableColumns(
                $setup,
                'quote_address',
                $field,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                100,
                'custom field ' . $field
            );
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
     * @param SchemaSetupInterface     $setup
     * @param string                   $table
     * @param string                   $column
     * @param                          $type
     * @param int                      $length
     * @param string                   $comment
     *
     * @return void
     */
    public function addTableColumns($setup, $table, $column, $type, $length, $comment)
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

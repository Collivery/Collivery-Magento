<?php

namespace MDS\Collivery\Setup;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Psr\Log\LoggerInterface;

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
        Config $config,
        LoggerInterface $logger = null
    ) {
        $this->eavSetup = $eavSetup;
        $this->eavConfig = $config;
        $this->logger = $logger;
    }
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {

        $this->logger->error('MDS Install started');

        $installer = $setup;

        $installer->startSetup();

        $customFields = ['location', 'town', 'suburb'];
        $position = 333;
        foreach ($customFields as $field) {
            $this->logger->error('Adding Field'. $field);

            $source = 'MDS\Collivery\Model\Customer\Address\Attribute\Source\\' . ucfirst($field);
            try {
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
                        'system' => false,
                    ]
                );
            } catch (LocalizedException $e) {
                $this->logger->error('MDS Field failed LocalizedException');
                $this->logger->error($e->getMessage());

            } catch (\Zend_Validate_Exception $e) {
                $this->logger->error('MDS Field failed Zend_Validate_Exception');
                $this->logger->error($e->getMessage());
            }
            $customAttribute = $this->eavConfig->getAttribute(
                AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
                $field
            );
            $customAttribute->setData(
                'used_in_forms',
                ['adminhtml_customer_address', 'customer_address_edit', 'customer_register_address']
            );
            $customAttribute->save();
            $position += 1;
        }
        $installer->endSetup();
    }
}

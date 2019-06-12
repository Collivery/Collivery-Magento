<?php

namespace MDS\Collivery\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use MDS\Collivery\Block\Customer\Address\BillingAddressRenderer;

class BillingAddressProvider implements ConfigProviderInterface
{
    /**
     * CmsBlockCheckoutConfigProvider constructor.
     *
     * @param BillingAddressRenderer $block
     * @param string                 $blockIdentifier ID or identifier (code) of the block
     */
    protected $cmsBlockRepository;
    private $cmsBlockWidget;

    public function __construct(
        BillingAddressRenderer $block,
        string $blockIdentifier
    ) {
        if (is_numeric($blockIdentifier)) {
            $blockId = (int) $blockIdentifier;
        } else {
            $blockId = (string) $blockIdentifier; // loader of block works with a string as well
        }
        if (!$blockId) {
            return;
        }
        $this->cmsBlockWidget = $block;
        $block->setData('block_id', $blockId);
    }


    public function getConfig()
    {
        if (!$this->cmsBlockWidget) {
            return [];
        }
        return [
            'cmsBillingAddress' => $this->cmsBlockWidget->toHtml()
        ];
    }
}

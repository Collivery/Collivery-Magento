<?php
/**
 * Created by PhpStorm.
 * User: mosa
 * Date: 2019/06/12
 * Time: 1:01 PM
 */

namespace MDS\Collivery\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use MDS\Collivery\Block\Customer\Address\ShippingAddressRenderer;

class ShippingAddressProvider implements ConfigProviderInterface
{
    private $cmsBlockWidget;

    public function __construct(
        ShippingAddressRenderer $block,
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

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        if (!$this->cmsBlockWidget) {
            return [];
        }
        return [
            'cmsShippingAddress' => $this->cmsBlockWidget->toHtml()
        ];
    }
}

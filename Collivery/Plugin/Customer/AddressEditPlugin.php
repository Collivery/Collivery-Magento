<?php

namespace MDS\Collivery\Plugin\Customer;

use Magento\Customer\Block\Address\Edit;
use Magento\Framework\View\LayoutInterface;

class AddressEditPlugin
{
    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * AddressEditPlugin constructor.
     *
     * @param LayoutInterface $layout
     */
    public function __construct(LayoutInterface $layout)
    {
        $this->layout = $layout;
    }

    /**
     * @param Edit $edit
     * @param string $result
     *
     * @return string
     */
    public function afterGetNameBlockHtml(Edit $edit, $result)
    {
        $customBlock = $this->layout->createBlock(
            'MDS\Collivery\Block\Customer\Address\Form\Edit\Custom',
            'Collivery_Custom_Attribute'
        );

        return $result . $customBlock->toHtml();
    }
}

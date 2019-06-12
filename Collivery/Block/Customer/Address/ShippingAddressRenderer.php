<?php

namespace MDS\Collivery\Block\Customer\Address;

use Magento\Framework\View\Element\Template;

class ShippingAddressRenderer extends Template
{
    public function __construct(Template\Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('widget/static_block/shippingAddress.phtml');
    }
}
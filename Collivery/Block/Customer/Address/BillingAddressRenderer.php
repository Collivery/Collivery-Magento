<?php

namespace MDS\Collivery\Block\Customer\Address;

use Magento\Framework\View\Element\Template;

class BillingAddressRenderer extends Template
{
    /**
     * Custom constructor.
     *
     * @param Template\Context $context
     * @param array            $data
     */
    public function __construct(
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('widget/static_block/billingAddress.phtml');
    }
}

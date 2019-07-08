<?php

namespace MDS\Collivery\Plugin\Checkout\Model;

class LayoutProcessor
{
    public function aroundProcess(\Magento\Checkout\Block\Checkout\LayoutProcessor $subject, \Closure $proceed, $jsLayout)
    {
        $layout = $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children'];

        $layout['country_id']['sortOrder'] = 70;
        $layout['town']['sortOrder'] = 80;
        $layout['suburb']['sortOrder'] = 90;

        $customJsLayout = $proceed($jsLayout);

        return $customJsLayout;
    }
}

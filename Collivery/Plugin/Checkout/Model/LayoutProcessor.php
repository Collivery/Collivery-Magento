<?php

namespace MDS\Collivery\Plugin\Checkout\Model;

class LayoutProcessor
{
    public function aroundProcess(\Magento\Checkout\Block\Checkout\LayoutProcessor $subject, \Closure $proceed, $jsLayout)
    {
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['town']['sortOrder'] = 70;

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['suburb']['sortOrder'] = 80;

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['country_id']['sortOrder'] = 90;

        $customJsLayout = $proceed($jsLayout);

        return $customJsLayout;
    }
}

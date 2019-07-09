<?php

namespace MDS\Collivery\Plugin\Checkout\Model;

class LayoutProcessor
{
    public function aroundProcess(\Magento\Checkout\Block\Checkout\LayoutProcessor $subject, \Closure $proceed, $jsLayout)
    {
        $fields = [
            'company' => 50,
            'country_id' => 60,
            'town' => 61,
            'city' => 62,
            'suburb' => 63
        ];

        foreach ($fields as $field => $orderNo) {
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children'][$field]['sortOrder'] = $orderNo;
        }

        $customJsLayout = $proceed($jsLayout);

        return $customJsLayout;
    }
}

<?php

namespace MDS\Collivery\Plugin\Checkout\Model;

class LayoutProcessor
{
    public function __construct(
        \Magento\Payment\Model\Config $paymentModelConfig
    ) {
        $this->paymentModelConfig = $paymentModelConfig;
    }

    /* disable city from checkout page */
    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        array $jsLayout
    ) {
        /* For Disable city field from checkout page shipping form */
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shipping-address-fieldset']['children']['city'] = [
            'visible' => false
        ];

        $activePayments = $this->paymentModelConfig->getActiveMethods();
        /* For Disable city field from checkout billing form */
        if (count($activePayments)) {
            foreach ($activePayments as $paymentCode => $payment) {
                $jsLayout['components']['checkout']['children']['steps']['children']
                ['billing-step']['children']['payment']['children']
                ['payments-list']['children'][$paymentCode . '-form']['children']
                ['form-fields']['children']['city'] = [
                    'visible' => false
                ];
            }
        }

        return $jsLayout;
    }

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

    public function process(\Magento\Checkout\Block\Checkout\LayoutProcessor $subject, $jsLayout)
    {
        unset($jsLayout['components']['checkout']['children']['steps']['city']);
        return $jsLayout;
    }
}

<?php

namespace MDS\Collivery\Api;

use Magento\Checkout\Model\Cart;
use Psr\Log\LoggerInterface;

class CustomAttributes
{
    private $cart;
    private $logger;

    public function __construct(
        Cart $cart,
        LoggerInterface $logger
    ) {
        $this->cart = $cart;
        $this->logger = $logger;
    }

    /**
     * @return void
     */
    public function insertCustomAttributes()
    {
        $quote = $this->cart->getQuote();

        $data = [
            'location' => $_GET['location'],
            'town' => $_GET['town'],
            'suburb' => $_GET['suburb']
        ];

        if (isset($_GET['address_type']) && $_GET['address_type'] == 'shipping_address') {
            echo 'update shipping';
            $addresses = [$quote->getShippingAddress()];
        } elseif (isset($_GET['address_type']) && $_GET['address_type'] == 'billing_address') {
            $addresses = [$quote->getBillingAddress()];
            echo 'update billing';
        } else {
            echo 'update All';
            $addresses = [$quote->getShippingAddress(), $quote->getBillingAddress()];
        }

        foreach ($addresses as $address) {
            $address->load($address->getAddressId())->addData($data);
            $address->setId($address->getAddressId())->save();
        }

        return;
    }

    /**
     * @param string $param
     *
     * @return array
     */
    public function getCustomAttribute($param)
    {
        $quote = $this->cart->getQuote();
        $address = $param === 'shipping_address' ? $quote->getShippingAddress() : $quote->getBillingAddress();

        $data = new \stdClass();
        $data->location = $address->getLocation();
        $data->town = $address->getTown();
        $data->suburb = $address->getSuburb();

        return json_encode($data);
    }
}

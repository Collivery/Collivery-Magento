<?php

namespace MDS\Collivery\Api;

use Magento\Checkout\Model\Cart;
use MDS\Collivery\Model\Customer\Address\Attribute\Source\Location;
use MDS\Collivery\Model\Customer\Address\Attribute\Source\Suburb;
use MDS\Collivery\Model\Customer\Address\Attribute\Source\Town;

class Address
{
    private $cart;

    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }

    /**
     * Save Billing Address to quote_address table.
     *
     * @return mixed
     * @throws \Exception
     */
    public function updateBillingAddress()
    {
        $billingAddress = $this->cart->getQuote()->getBillingAddress();

        $data = [];
        parse_str($_SERVER['QUERY_STRING'], $data);

        $billingAddress->load($billingAddress->getId())->addData($data);
        $billingAddress->setId($billingAddress->getId())->save();
    }

    /**
     * Get Billing Address from quote_address table.
     *
     * @return string
     */
    public function getBillingAddress()
    {
        $quoteBillingAddress = $this->cart->getQuote()->getBillingAddress();
        $locationType = Location::getLocationById($quoteBillingAddress->getLocation());
        $town = Town::getTownById($quoteBillingAddress->getTown());
        $suburb = Suburb::getSuburbById($quoteBillingAddress->getSuburb());
        $street = implode(', ', $quoteBillingAddress->getStreet());

        $billingAddress = $quoteBillingAddress->getFirstname() . ' ' . $quoteBillingAddress->getLastname();
        $billingAddress .= $quoteBillingAddress->getCompany() ? "<br> {$quoteBillingAddress->getCompany()}" : "";
        $billingAddress .= "<br> $street";
        $billingAddress .= "<br> $suburb, $locationType";
        $billingAddress .= "<br>$town, {$quoteBillingAddress->getPostcode()}";

        return $billingAddress;
    }

    /**
     * Get shipping Address from quote_address table.
     *
     * @return string
     */
    public function getShippingAddress()
    {
        $quoteShippingAddress = $this->cart->getQuote()->getShippingAddress();
        $locationType = Location::getLocationById($quoteShippingAddress->getLocation());
        $town = Town::getTownById($quoteShippingAddress->getTown());
        $suburb = Suburb::getSuburbById($quoteShippingAddress->getSuburb());
        $street = implode(', ', $quoteShippingAddress->getStreet());

        $shippingAddress = $quoteShippingAddress->getFirstname() . ' ' . $quoteShippingAddress->getLastname();
        $shippingAddress .= $quoteShippingAddress->getCompany() ? "<br> {$quoteShippingAddress->getCompany()}" : "";
        $shippingAddress .= "<br> $street";
        $shippingAddress .= "<br> $suburb, $locationType";
        $shippingAddress .= "<br>$town, {$quoteShippingAddress->getPostcode()}";

        return $shippingAddress;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: mosa
 * Date: 2019/05/03
 * Time: 11:28 AM
 */

namespace MDS\Collivery\Api;

use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Session;
use Magento\Quote\Api\Data\AddressInterface;
use Psr\Log\LoggerInterface;

class CustomAttributes
{
    private $cart;
    private $logger;
    private $session;
    private $address;

    public function __construct(
        Session $session,
        Cart $cart,
        LoggerInterface $logger,
        AddressInterface $address
    ) {
        $this->cart = $cart;
        $this->logger = $logger;
        $this->session = $session;
        $this->address  = $address;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function insertCustomAttributes()
    {
        $quote = $this->cart->getQuote();

        $address = $quote->getShippingAddress();
        $data = [
            'location' => $_GET['location_type'],
            'town' => $_GET['town'],
            'suburb' => $_GET['suburb']
        ];
        $address->load($address->getAddressId())->addData($data);
        $address->setId($address->getAddressId())->save();

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

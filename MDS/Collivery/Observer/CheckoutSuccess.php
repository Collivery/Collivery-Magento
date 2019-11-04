<?php

namespace MDS\Collivery\Observer;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CheckoutSuccess implements ObserverInterface
{
    private $session;
    private $logger;
    private $addressRepository;

    public function __construct(
        \Magento\Customer\Model\Session $session,
        \Psr\Log\LoggerInterface $logger,
        AddressRepositoryInterface $addressRepository
    ) {
        $this->session = $session;
        $this->logger = $logger;
        $this->addressRepository = $addressRepository;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        if ($this->session->isLoggedIn()) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            /** @var \Magento\Sales\Model\Order $order */
            $order = $observer->getEvent()->getOrder();
            $shippingAddressObj = $order->getShippingAddress();

            $shippingAddressArray = $shippingAddressObj->getData();
            $options = $objectManager->create('Magento\Quote\Api\Data\AddressInterface');
            /** @var \Magento\Quote\Api\Data\AddressInterface $quote */
            $quote = $options->load($shippingAddressArray['quote_address_id']);

            $location = $quote->getLocation();
            $suburb = $quote->getSuburb();
            $town = $quote->getTown();
            $address = $this->addressRepository->getById($shippingAddressArray['customer_address_id']);
            if (!$address->getCustomAttributes()) {
                $address->setCustomAttribute('location', $location);
                $address->setCustomAttribute('suburb', $suburb);
                $address->setCustomAttribute('town', $town);
                $this->addressRepository->save($address);
            }
        }

        return;
    }
}

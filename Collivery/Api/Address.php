<?php
/**
 * Created by PhpStorm.
 * User: mosa
 * Date: 2019/06/27
 * Time: 12:21 PM
 */

namespace MDS\Collivery\Api;

use Magento\Checkout\Model\Session;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class Address
{
    private $customer;
    private $logger;
    private $session;
    private $address;
    private $addressRepository;

    public function __construct(
        LoggerInterface $logger,
        Customer $customer,
        Session $session,
        \Magento\Customer\Api\Data\AddressInterface $address,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
    ) {
        $this->session = $session;
        $this->customer = $customer;
        $this->logger = $logger;
        $this->address = $address;
        $this->addressRepository = $addressRepository;
    }

    /**
     * @return void
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setCustomerDefaultAddress()
    {
        $customer = $this->getCustomer();
        $customer->setDefaultShipping($_GET['address_id']);
        try {
            $customer->save();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return;
    }

    /**
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function addAddress()
    {
        $customerId = $this->getCustomer()->getId();
        $objectManager = ObjectManager::getInstance();
        $region = $objectManager->create(RegionInterface::class);
        $region->setRegion($_GET['region']);
        $address = $this->address->setCustomerId($customerId)
            ->setFirstname($_GET['firstname'])
            ->setLastname($_GET['lastname'])
            ->setCountryId($_GET['country_id'])
            ->setCompany($_GET['company'])
            ->setPostcode($_GET['postcode'])
            ->setRegion($region)
            ->setCity($_GET['city'])
            ->setTelephone($_GET['telephone'])
            ->setStreet($_GET['street'])
            ->setCustomAttribute('location', $_GET['location'])
            ->setCustomAttribute('town', $_GET['town'])
            ->setCustomAttribute('suburb', $_GET['suburb'])
            ->setIsDefaultBilling(1)
            ->setIsDefaultShipping(1);

        try {
            $this->addressRepository->save($address);
            return true;
        } catch (LocalizedException $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }

    /**
     * @return Customer
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCustomer()
    {
        $customerId = $this->session->getQuote()->getCustomer()->getId();

        return $this->customer->load($customerId);
    }
}

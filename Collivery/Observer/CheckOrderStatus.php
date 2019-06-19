<?php

namespace MDS\Collivery\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use MDS\Collivery\Orders\ProcessOrder;

class CheckOrderStatus extends ProcessOrder implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    private $logger;

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct();
        $this->messageManager = $messageManager;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        $orderItems = $order->getAllItems();

        foreach ($orderItems as $item) {
            $product = $objectManager->create('Magento\Catalog\Model\Product')->load($item->getProductId());
            $parcels[] = [
                'weight' => $item->getWeight(),
                'height' => $product->getTsDimensionsHeight(),
                'length' => $product->getTsDimensionsLength(),
                'width' => $product->getTsDimensionsWidth(),
            ];
        }

        $shippingAddressObj = $order->getShippingAddress();

        $shippingAddressArray = $shippingAddressObj->getData();

        if (is_null($shippingAddressArray['customer_address_id'])) {
            $options = $objectManager->create('Magento\Quote\Api\Data\AddressInterface');
            $quote = $options->load($shippingAddressArray['quote_address_id']);
            $customAttributes = [
                'location' => $quote->getLocation(),
                'town' => $quote->getTown(),
                'suburb' => $quote->getSuburb(),
            ];
        } else {
            $customAttributes = $this->getCustomAttributes($shippingAddressArray['customer_address_id']);
        }

        if ($order->getState() == Order::STATE_COMPLETE) {
            $fullname = $shippingAddressArray['firstname'] . ' ' . $shippingAddressArray['lastname'];
            $addAddressData = [
                'company_name' =>$shippingAddressArray['company']
                    ?? $fullname,
                'street' => $shippingAddressArray['street'],
                'location_type' => $customAttributes['location'],
                'suburb_id' => $customAttributes['suburb'],
                'town_id' => $customAttributes['town'],
                'full_name' => $fullname,
                'phone' => $shippingAddressArray['telephone'],
                'cellphone' => $shippingAddressArray['telephone'],
                'email' => $shippingAddressArray['email'],
            ];

            //add address
            $insertedAddress = $this->addAddress($addAddressData);
            is_null($insertedAddress) && $this->returnBack($this->getErrors());

            $addContactdata = [
                'address_id' => $insertedAddress['address_id']
                ] + array_intersect_key($addAddressData, array_flip(['full_name', 'phone', 'cellphone', 'email']));

            //add contact address
            $addedContact = $this->addContactAddress($addContactdata);
            is_null($addedContact) && $this->returnBack($this->getErrors());

            //validate collivery
            $client = $this->getShopOwnerDetails();
            $client = reset($client);

            $validateData = [
                'collivery_from' => $client['address_id'],
                'contact_from' => $client['contact_id'],
                'collivery_to' => $insertedAddress['address_id'],
                'contact_to' => $addedContact['contact_id'],
                'collivery_type' => 2, //Use default Package as collivery type
                'service' => (int)$order->getShippingMethod('data')->getData('method'),
                'cover' => true,
                'rica' => true,
                'parcels' => $parcels
            ];

            $validatedCollivery = $this->validateCollivery($validateData);
            is_null($validatedCollivery) && $this->returnBack($this->getErrors());

            //add collivery
            $waybill = $this->addCollivery($validatedCollivery);
            !is_numeric($waybill) && $this->returnBack($this->getErrors());

            //accept collivery
            !$this->acceptWaybill($waybill) && $this->returnBack($this->getErrors());

            $this->saveWaybill($waybill, $order->getId());
            $this->messageManager->addSuccess(__('waybill: ' . $waybill . ' created successfully'));
        }
    }

    /**
     * @param $customerAddressId
     *
     * @return array
     */
    private function getCustomAttributes($customerAddressId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $addressRepoInterface = $objectManager->get('Magento\Customer\Api\AddressRepositoryInterface');
        $address = $addressRepoInterface->getById($customerAddressId);
        $location = $address->getCustomAttribute('location')->getValue();
        $town = $address->getCustomAttribute('town')->getValue();
        $suburb = $address->getCustomAttribute('suburb')->getValue();

        return compact('location', 'town', 'suburb');
    }

    /**
     * @param $error
     *
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function returnBack($error)
    {
        $this->logger->error($error);
        throw new \Magento\Framework\Exception\NoSuchEntityException(__($error));
    }
}

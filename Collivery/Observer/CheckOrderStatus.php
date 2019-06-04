<?php

namespace MDS\Collivery\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use MDS\Collivery\Model\Connection;
use MDS\Collivery\Orders\ProcessOrder;

class CheckOrderStatus extends ProcessOrder implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $redirect;

    public function __construct(
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        parent::__construct();
        $this->redirect = $redirect;
        $this->messageManager = $messageManager;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
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

            $rateLimitExceededMessage = __('Daily Rate Limit Exceeded, please feel free to contact MDS Collivery to discuss custom limits');

            if (!$insertedAddress) {
                $this->messageManager->addErrorMessage($rateLimitExceededMessage);
            }

            $addContactdata = [
                'address_id' => $insertedAddress['address_id']
                ] + array_intersect_key($addAddressData, array_flip(['full_name', 'phone', 'cellphone', 'email']));

            //add contact address
            $addedContact = $this->addContactAddress($addContactdata);

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

            //add collivery
            $waybill = $this->addCollivery($validatedCollivery);

            //accept collivery
            $acceptCollivery = $this->acceptWaybill($waybill);

            try {
                //store waybill in sales order
                $order->setColliveryId($waybill);
                $order->save();
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }

            if ($acceptCollivery['result'] == 'Accepted') {
                $this->messageManager->addSuccess(__('waybill: ' . $waybill . ' created successfully'));
            }
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
}

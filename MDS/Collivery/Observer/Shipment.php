<?php

namespace MDS\Collivery\Observer;

use Magento\Catalog\Model\Product;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\AddressInterface;
use MDS\Collivery\Model\Constants;
use MDS\Collivery\Orders\ProcessOrder;
use Throwable;

class Shipment extends ProcessOrder implements ObserverInterface
{
    const XML_CONFIG_PATH = 'carriers/collivery/active';
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    private $logger;
    private $subject;

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Config\Model\Config $subject
    ) {
        parent::__construct();
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->subject = $subject;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws \InvalidArgumentException
     * @throws Throwable
     */
    public function execute(Observer $observer)
    {
        if ($this->subject->getConfigDataValue(self::XML_CONFIG_PATH) == 1) {
            $resource = $this->objectManager->get(ResourceConnection::class);
            $connection = $resource->getConnection();

            try {
                $connection->beginTransaction();
                $shipment = $observer->getEvent()->getShipment();
                /** @var \Magento\Sales\Model\Order $order */
                $order = $shipment->getOrder();
                $orderItems = $order->getAllItems();

                foreach ($orderItems as $item) {
                    $product = $this->objectManager->create(Product::class)->load($item->getProductId());
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
                    $options = $this->objectManager->create(AddressInterface::class);
                    $quote = $options->load($shippingAddressArray['quote_address_id']);
                    $customAttributes = [
                        'location' => $quote->getLocation(),
                        'town' => $quote->getTown(),
                        'suburb' => $quote->getSuburb(),
                    ];
                } else {
                    $customAttributes = $this->getCustomAttributes($shippingAddressArray['customer_address_id']);
                }

                $fullname = $shippingAddressArray['firstname'] . ' ' . $shippingAddressArray['lastname'];
                $addAddressData = [
                    'company_name' =>isset($shippingAddressArray['company'])
                        ? $shippingAddressArray['company']
                        : $fullname,
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
                is_null($insertedAddress) && $this->errorBag($this->getErrors());

                $addContactdata = [
                        'address_id' => $insertedAddress['address_id']
                    ] + array_intersect_key($addAddressData, array_flip(['full_name', 'phone', 'cellphone', 'email']));

                //add contact address
                $addedContact = $this->addContactAddress($addContactdata);
                is_null($addedContact) && $this->errorBag($this->getErrors());

                //validate collivery
                $client = $this->getShopOwnerDetails();
                $client = reset($client);

                $validateData = [
                    'collivery_from' => $client['address_id'],
                    'contact_from' => $client['contact_id'],
                    'collivery_to' => $insertedAddress['address_id'],
                    'contact_to' => $addedContact['contact_id'],
                    'collivery_type' => Constants::DEFAULT_PACKAGE, //Use default Package as collivery type
                    'service' => (int)$order->getShippingMethod('data')->getData('method'),
                    'cover' => false,
                    'rica' => false,
                    'parcels' => $parcels,
                    'cust_ref' => $order->getRealOrderId()
                ];

                //add collivery
                $waybill = $this->addCollivery($validateData);
                !is_numeric($waybill) && $this->errorBag($this->getErrors());

                //accept collivery
                !$this->acceptWaybill($waybill) && $this->errorBag($this->getErrors());

                $order->setData('collivery_id', $waybill);
                $order->afterSave();
                $this->messageManager->addSuccessMessage(__('waybill: ' . $waybill . ' created successfully'));
                $connection->commit();
            } catch (\InvalidArgumentException $e) {
                $connection->rollBack();
                $this->errorBag($e->getMessage());
            }
        }
    }

    /**
     * @param $customerAddressId
     *
     * @return array
     * @throws NoSuchEntityException
     * @throws Throwable
     */
    private function getCustomAttributes($customerAddressId)
    {
        $addressRepoInterface = $this->objectManager->get(AddressRepositoryInterface::class);

        try {
            $address = $addressRepoInterface->getById($customerAddressId);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage('Delivery Address could not be found');
            $this->errorBag($e->getMessage());
        } catch (Throwable $e) {
            throw $e;
        }

        $location = $address->getCustomAttribute('location')->getValue();
        $town = $address->getCustomAttribute('town')->getValue();
        $suburb = $address->getCustomAttribute('suburb')->getValue();

        return compact('location', 'town', 'suburb');
    }

    /**
     * @param $error
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function errorBag($error)
    {
        $this->logger->error($error);
        throw new NoSuchEntityException(__($error));
    }
}

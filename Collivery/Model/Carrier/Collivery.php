<?php

namespace MDS\Collivery\Model\Carrier;

use Magento\Checkout\Model\Cart;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use MDS\Collivery\Exceptions\NoShippingException;
use MDS\Collivery\Model\Connection;
use Psr\Log\LoggerInterface;

class Collivery extends AbstractCarrier implements CarrierInterface
{
    protected $_code = 'collivery';
    protected $_isFixed = true;
    protected $_rateResultFactory;
    protected $_rateMethodFactory;
    protected $_scopeConfig;
    public $_collivery;
    protected $cache;

    /**
     * Rate result data
     *
     * @var Result
     */
    protected $_result;
    private $_session;
    private $_customer;
    private $_rateRequest;
    private $_cart;
    private $orderFactory;
    private $addressRepository;
    private $logger;
    private $messageManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        Session $session,
        Cart $cart,
        OrderFactory $orderFactory,
        AddressRepositoryInterface $addressRepository,
        ManagerInterface $messageManager,
        $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_session = $session;
        $this->_cart = $cart;
        $this->orderFactory = $orderFactory;
        $this->addressRepository = $addressRepository;
        $this->_customer = $this->getCustomer();
        $this->logger = $logger;
        $this->messageManager = $messageManager;

        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);

        $username = $this->getConfigData('username');
        $password = $this->getConfigData('password');
        $collivery = new Connection($username, $password);
        $this->_collivery = $collivery->getConnection();
    }

    public function getAllowedMethods()
    {
        if ($this->_session->isLoggedIn() && $this->_customer->getDefaultShipping()) {
            try {
                $defaultShippingAddress = $this->addressRepository->getById($this->_customer->getDefaultShipping());
                $defaultShippingCustomAttributes = $defaultShippingAddress->getCustomAttributes();
                if (!array_key_exists('location', $defaultShippingCustomAttributes)) {
                    $error = "Please set location type in your default address in address book to get shipping estimates (My Account --> Address Book)";
                    throw new NoSuchEntityException(__($error));
                }

                $customAddress = [
                    'town' => $defaultShippingCustomAttributes['town']->getValue(),
                    'location' => $defaultShippingCustomAttributes['location']->getValue()
                ];
            } catch (LocalizedException $e) {
                $this->logger->critical($e->getMessage());
                throw new NoSuchEntityException(__($e->getMessage()));
            }
        } else {
            $quote = $this->_cart->getQuote();
            $address = $quote->getShippingAddress();
            $customAddress = [
                'town' => $address->getTown(),
                'location' => $address->getLocation()
            ];
        }

        if (empty($customAddress)) {
            $services =  $this->_collivery->getServices();
            empty($services) && $this->showErrorMessage($this->_collivery->getErrors());

            return $services;
        }

        return $this->getServices($customAddress);
    }

    public function collectRates(RateRequest $request)
    {
        if (!$this->isActive()) {
            return false;
        }
        $this->_rateRequest = $request;

        $this->_result = $this->_rateResultFactory->create();

        $result = $this->_rateResultFactory->create();
        $shippingMethods = $this->getAllowedMethods();

        foreach ($shippingMethods as $key => $service) {
            $carrier = $this->_rateMethodFactory->create();
            $carrier->setCarrier($this->getCarrierCode());
            $carrier->setCarrierTitle($this->getConfigData('title'));
            $carrier->setMethod(isset($service['code']) ? $service['code'] : $key);
            $carrier->setMethodTitle(isset($service['title']) ? $service['title'] : $service);

            $carrier->setPrice(isset($service['price']) ? $service['price'] : 0);
            $carrier->setCost(isset($service['cost']) ? $service['cost'] : 0);

            $result->append($carrier);
        }

        return $result;
    }

    /**
     * @param $addressId
     *
     * @return array
     */
    public function getAddress($addressId)
    {
        return $this->_collivery->getAddress($addressId);
    }

    /**
     * @param $customerAddress
     * @param $service
     *
     * @return array
     * @throws NoShippingException
     */
    public function shippingPrice($customerAddress, $service)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $state = $objectManager->get('Magento\Framework\App\State');

        if ($state->getAreaCode() !== 'adminhtml') {
            $items = $this->_rateRequest->getAllItems();
            $parcelDimensions = $this->getProductDimensions($items);

            $data = [
                'collivery_from'   => $this->_collivery->getDefaultAddressId(),
                'to_town_id'       => (int)$customerAddress['town'],
                'to_location_type' => (int)$customerAddress['location'],
                'service'          => $service,
                'parcels'          => $parcelDimensions
            ];

            $prices = $this->_collivery->getPrice($data);

            !$prices && $this->showErrorMessage($this->_collivery->getErrors());

            return $prices ? $prices['price']['ex_vat'] : [];
        }
    }

    /**
     * @param $customerAddress
     *
     * @return array
     * @throws NoShippingException
     */
    public function getServices($customerAddress)
    {
        $services = $this->_collivery->getServices();
        $response = [];

        foreach ($services as $key => $value) {
            // Get Shipping Estimate for current service
            $i = $this->shippingPrice($customerAddress, $key);

            if ($i) {
                // Create Response Array
                $response[] =
                        [
                            'code'    => $key,
                            'title'   => $value,
                            'cost'    => $i,
                            'price'   => $i * (1+($this->getConfigData('markup')/100)),
                        ];
            }
        }

        return $response;
    }

    /**
     * @return array
     */
    private function getCustomer()
    {
        $customerId = $this->_session->getCustomerId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        return $objectManager->create('Magento\Customer\Model\Customer')->load($customerId);
    }

    /**
     * @param $items
     *
     * @return array
     */
    public function getProductDimensions($items)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $parcels = [];
        foreach ($items as $item) {
            $product = $objectManager->create('Magento\Catalog\Model\Product')->load($item->getProductId());
            for ($x = 1; $x <= $item->getQty(); $x++) {
                $parcels[] = [
                    'weight' => $item->getWeight(),
                    'height' => $product->getTsDimensionsHeight(),
                    'length' => $product->getTsDimensionsLength(),
                    'width' => $product->getTsDimensionsWidth(),
                ];
            }
        }

        return $parcels;
    }

    private function showErrorMessage($error)
    {
        $this->logger->error(json_encode($error));
        throw new NoShippingException();
    }
}

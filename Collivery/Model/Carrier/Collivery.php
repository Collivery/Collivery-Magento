<?php

namespace MDS\Collivery\Model\Carrier;

use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
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

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        Session $session,
        Cart $cart,
        $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_session = $session;
        $this->_cart = $cart;
        $this->_customer = $this->getCustomer();

        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);

        $username = $this->getConfigData('username');
        $password = $this->getConfigData('password');
        $collivery = new Connection($username, $password);
        $this->_collivery = $collivery->getConnection();
    }

    public function getAllowedMethods()
    {
        $quote = $this->_cart->getQuote();
        $address = $quote->getShippingAddress();
        $customerAddress = [
                'town' => $address->getTown(),
                'location' => $address->getLocation()
            ];

        if (empty($customerAddress)) {
            return $this->_collivery->getServices();
        }

        return $this->getServices($customerAddress);
    }

    public function collectRates(RateRequest $request)
    {
        if (!$this->isActive()) {
            return false;
        }
        $this->_rateRequest = $request;

        $this->_result = $this->_rateResultFactory->create();

        $result = $this->_rateResultFactory->create();

        foreach ($this->getAllowedMethods() as $key => $service) {
            $test = $this->_rateMethodFactory->create();
            $test->setCarrier($this->getCarrierCode());
            $test->setCarrierTitle($this->getConfigData('title'));
            $test->setMethod(isset($service['code']) ? $service['code'] : $key);
            $test->setMethodTitle(isset($service['title']) ? $service['title'] : $service);

            $test->setPrice(isset($service['price']) ? $service['price'] : 0);
            $test->setCost(isset($service['cost']) ? $service['cost'] : 0);

            $result->append($test);
        }

        return $result;
    }

    public function getAddress($addressId)
    {
        return $this->_collivery->getAddress($addressId);
    }

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

            if (!is_array($prices)) {
                return false;
            }

            return $prices['price']['ex_vat'];
        }
    }

    public function getServices($customerAddress)
    {
        $services = $this->_collivery->getServices();
        $response = [];
        foreach ($services as $key => $value) {
            // Get Shipping Estimate for current service
            $i = $this->shippingPrice($customerAddress, $key);

            if ($i>1) {
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

    private function getCustomer()
    {
        $customerId = $this->_session->getCustomerId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        return $objectManager->create('Magento\Customer\Model\Customer')->load($customerId);
    }

    public function getProductDimensions($items)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

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
}

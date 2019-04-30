<?php

namespace MDS\Collivery\Model\Carrier;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use MDS\Collivery\Model\MdsCollivery;
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

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        Session $session,
        $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_session = $session;
        $this->_customer = $this->getCustomer();

        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);

        $config = [
            'app_name'      => 'Default App Name', // Application Name
            'app_version'   => '0.0.1',            // Application Version
            'app_host'      => '', // Framework/CMS name and version, eg 'Wordpress 3.8.1 WooCommerce 2.0.20' / 'Joomla! 2.5.17 VirtueMart 2.0.26d'
            'app_url'       => '', // URL your site is hosted on
            'user_email'    => 'api@collivery.co.za',
            'user_password' => 'api123',
            'demo'          => false,
        ];

        $this->_collivery = new MdsCollivery($config);
    }

    public function getAllowedMethods()
    {
        $customerAddress = [];
        foreach ($this->_customer->getAddresses() as $address) {
            $customerAddress[] = $address->toArray();
        }

        if (empty($customerAddress[0])) {
            return $this->_collivery->getServices();
        }

        return $this->getServices($customerAddress[0]);
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
            $test->setMethod($service['code'] ?? $key);
            $test->setMethodTitle($service['title'] ?? $service);

            $test->setPrice($service['price'] ?? 0);
            $test->setCost($service['cost'] ?? 0);
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
        $items = $this->_rateRequest->getAllItems();
        $parcelDimensions = $this->getProductDimensions($items);
        $data = [
            'collivery_from' => $this->_collivery->getDefaultAddressId(),
            'to_town_id' => (int) $customerAddress['town'],
            'to_location_type' => (int) $customerAddress['location'],
            'service' => $service,
            'parcels' => $parcelDimensions
        ];

        $prices = $this->_collivery->getPrice($data);

        if (!is_array($prices)) {
            return false;
        }

        return $prices['price']['inc_vat'];
    }

    public function getServices($customerAddress)
    {
        $services = $this->_collivery->getServices();

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

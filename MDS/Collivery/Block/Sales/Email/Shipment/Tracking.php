<?php

namespace MDS\Collivery\Block\Sales\Email\Shipment;

use MDS\Collivery\Model\Connection;
use MDS\Collivery\Model\Constants;

class Tracking extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    private $_collivery;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $registry
     * @param \Magento\Sales\Helper\Admin             $adminHelper
     * @param array                                   $data
     * @param Connection                              $collivery
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        array $data = [],
        Connection $collivery
    ) {
        parent::__construct($context, $registry, $adminHelper, $data);
        $this->_collivery = $collivery->getConnection();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getTrackingInfo()
    {
        $waybill = $this->getOrder()->getColliveryId();
        $trackInfo =  $this->_collivery->getStatus((int)$waybill);

        if ($trackInfo) {
            $info = "Collivery $waybill is in status: {$trackInfo['status_text']}</br>";
            $time = array_key_exists('delivery_time', $trackInfo) ? $trackInfo['delivery_time'] : Constants::LATEST_TIME;
            $date = $trackInfo['delivery_date'] . '' . $time;
            $formatedDate = date('j F Y,  H:i', strtotime($date));

            if ($trackInfo['status_id'] == Constants::DELIVERY_DRIVER_DISPATCHED) {
                $info .= "Delivery will be before $formatedDate";
            } elseif ($trackInfo['status_id'] == Constants::IN_TRANSIT || $trackInfo['status_id'] == Constants::RECEIVED_BY_COURIER) {
                $info .= "Will be before $formatedDate";
            } else {
                $info .= '';
            }

            return $info;
        }

        return '';
    }
}

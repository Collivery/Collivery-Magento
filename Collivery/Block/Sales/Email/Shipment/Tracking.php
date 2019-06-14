<?php

namespace MDS\Collivery\Block\Sales\Email\Shipment;

use MDS\Collivery\Model\Connection;

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
            $time = array_key_exists('delivery_time', $trackInfo) ? $trackInfo['delivery_time'] : '16:00';
            $date = $trackInfo['delivery_date'] . '' . $time;
            $formatedDate = date('j F Y,  H:i', strtotime($date));

            if ($trackInfo['status_id'] == 15) {
                $info .= "Delivery will be before $formatedDate";
            } elseif ($trackInfo['status_id'] == 9 || $trackInfo['status_id'] == 21) {
                $info .= "Will be before $formatedDate";
            } else {
                $info .= '';
            }

            return $info;
        }

        return '';
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: mosa
 * Date: 2019/04/02
 * Time: 9:33 AM
 */

namespace MDS\Collivery\Plugin\Magento\Quote\Model;

class BillingAddressManagement
{
    protected $logger;

    public function __construct(
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    public function beforeAssign(
        \Magento\Quote\Model\BillingAddressManagement $subject,
        $cartId,
        \Magento\Quote\Api\Data\AddressInterface $address,
        $useForShipping = false
    ) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        if ($customerSession->isLoggedIn()) {
            $extAttributes = $address->getExtensionAttributes();
            if (!empty($extAttributes)) {
                try {
                    $address->setLocation($extAttributes->getLocation());
                    $address->setTown($extAttributes->getTown());
                    $address->setSuburb($extAttributes->getSuburb());
                } catch (\Exception $e) {
                    $this->logger->critical($e->getMessage());
                }
            }
        }
    }
}

<?php

namespace MDS\Collivery\Block\Customer\Address\Form\Edit;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;

class CustomAddressEditBlock extends Template
{
    /**
     * @var AddressInterface
     */
    private $address;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var AddressInterfaceFactory
     */
    private $addressFactory;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * Custom constructor.
     *
     * @param Template\Context           $context
     * @param array                      $data
     * @param AddressRepositoryInterface $addressRepository
     * @param AddressInterfaceFactory    $addressFactory
     * @param Session                    $session
     */
    public function __construct(
        Template\Context $context,
        AddressRepositoryInterface $addressRepository,
        AddressInterfaceFactory $addressFactory,
        Session $session,
        $data = []
    ) {
        parent::__construct($context, $data);
        $this->addressRepository = $addressRepository;
        $this->addressFactory = $addressFactory;
        $this->customerSession = $session;
    }

    /**
     * @return Template
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        $addressId = $this->getRequest()->getParam('id');
        if ($addressId) {
            try {
                $this->address = $this->addressRepository->getById($addressId);
                if ($this->address->getCustomerId() != $this->customerSession->getCustomerId()) {
                    $this->address = null;
                }
            } catch (NoSuchEntityException $e) {
                $this->address = null;
            }
        }

        if ($this->address == null) {
            $this->address = $this->addressFactory->create();
        }

        return parent::_prepareLayout();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _toHtml()
    {
        $customWidgetBlock = $this->getLayout()->createBlock(
            'MDS\Collivery\Block\Customer\Widget\CustomAddressEditWidget'
        );
        $customWidgetBlock->setAddress($this->address);

        return $customWidgetBlock->toHtml();
    }
}

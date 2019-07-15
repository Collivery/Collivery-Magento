<?php

namespace MDS\Collivery\Block\Customer\Widget;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;

class CustomAddressEditWidget extends Template
{
    /**
     * @var AddressMetadataInterface
     */
    private $addressMetadata;

    /**
     * Custom constructor.
     *
     * @param Template\Context         $context
     * @param array                    $data
     * @param AddressMetadataInterface $metadata
     */
    public function __construct(
        Template\Context $context,
        array $data = [],
        AddressMetadataInterface $metadata
    ) {
        parent::__construct($context, $data);
        $this->addressMetadata = $metadata;
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('widget/customAddressEdit.phtml');
    }

    /**
     * @param string $name
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isRequired($name)
    {
        return $this->getAttribute($name) ? $this->getAttribute($name)->isRequired() : false;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getFieldId($name)
    {
        return $name;
    }

    /**
     * @param string $name
     *
     * @return \Magento\Framework\Phrase|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFieldLabel($name)
    {
        return $this->getAttribute($name) ? $this->getAttribute($name)->getFrontendLabel() : __(ucfirst($name));
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getFieldName($name)
    {
        return $name;
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function getValue($name)
    {
        /** @var AddressInterface $address */
        $address = $this->getAddress();

        if ($address instanceof AddressInterface) {
            return $address->getCustomAttribute($name)
                ? $address->getCustomAttribute($name)->getValue()
                : null;
        }

        return null;
    }

    /**
     * @param string $name
     *
     * @return \Magento\Customer\Api\Data\AttributeMetadataInterface|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAttribute($name)
    {
        try {
            $attribute = $this->addressMetadata->getAttributeMetadata($name);
        } catch (NoSuchEntityException $e) {
            return null;
        }

        return $attribute;
    }
}

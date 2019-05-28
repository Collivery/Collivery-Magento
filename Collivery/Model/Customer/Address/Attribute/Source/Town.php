<?php

namespace MDS\Collivery\Model\Customer\Address\Attribute\Source;

use MDS\Collivery\Model\Connection;

class Town extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    public $_collivery;

    public function __construct(Connection $collivery)
    {
        $this->_collivery = $collivery->getConnection();
    }

    public function getAllOptions($withEmpty = true, $defaultValues = false)
    {
        if (!$this->_options) {
            $this->_options = $this->getTowns();
        }

        return $this->_options;
    }

    public function getTowns()
    {
        $towns = $this->_collivery->getTowns();
        if (!$towns) {
            return false;
        }

        foreach ($towns as $key => $town) {
            $towns_field[] =
                [
                    'value' => $key,
                    'label' => $town,
                ];
        }

        return $towns_field;
    }
}

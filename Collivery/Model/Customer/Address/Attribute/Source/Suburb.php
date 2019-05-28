<?php

namespace MDS\Collivery\Model\Customer\Address\Attribute\Source;

use MDS\Collivery\Model\Connection;

class Suburb extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    public $_collivery;
    public $_town;

    public function __construct($town=null)
    {
        $this->_town = $town;
        $collivery = new Connection();
        $this->_collivery = $collivery->getConnection();
    }


    public function getAllOptions($withEmpty = true, $defaultValues = false)
    {
        if (!$this->_options) {
            $this->_options = $this->getSuburbs();
        }

        return $this->_options;
    }

    public function getSuburbs()
    {
        $suburbs = $this->_collivery->getSuburbs($this->_town);
        if (!$suburbs) {
            return false;
        }

        foreach ($suburbs as $key => $suburb) {
            $suburb_field[] =
                    [
                        'value' => $key,
                        'label' => $suburb,
                    ];
        }

        return $suburb_field;
    }
}

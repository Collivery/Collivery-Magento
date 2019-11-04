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

    private function colliverySuburbs()
    {
        $suburbs = $this->_collivery->getSuburbs($this->_town);
        if (!$suburbs) {
            return false;
        }

        return $suburbs;
    }

    public static function getSuburbById($id)
    {
        $suburbs = (new Suburb())->colliverySuburbs();

        return $suburbs[$id];
    }

    public function getSuburbs()
    {
        $suburbs = $this->colliverySuburbs();

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

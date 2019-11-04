<?php

namespace MDS\Collivery\Api;

use MDS\Collivery\Model\Customer\Address\Attribute\Source\Suburb;

class SuburbsManagement
{
    protected $_town;

    /**
     * GET for suburbs api
     * @param string $param
     * @return string
     */
    public function getSuburbs($param)
    {
        $this->_town = new Suburb($param);
        $suburbs = $this->_town->getAllOptions(false);

        return $suburbs;
    }
}

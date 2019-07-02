<?php

namespace MDS\Collivery\Api;

use MDS\Collivery\Model\Customer\Address\Attribute\Source\Suburb;

class SuburbsManagement
{
    protected $town;

    /**
     * GET for suburbs api
     * @param string $param
     * @return string
     */
    public function getSuburbs($param)
    {
        $this->town = new Suburb($param);
        $suburbs = $this->town->getAllOptions(false);

        return $suburbs;
    }
}

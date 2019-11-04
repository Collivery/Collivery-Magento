<?php

namespace MDS\Collivery\Model;

trait ModuleStatus
{
    public static $path = 'carriers/collivery/active';
    private $subject;

    public function __construct(\Magento\Config\Model\Config $subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->subject->getConfigDataValue(self::$path) == 1 ? true : false;
    }
}

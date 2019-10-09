<?php

namespace MDS\Collivery\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

class ModuleStatusConfigProvider implements ConfigProviderInterface
{
    use ModuleStatus;

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return bool
     */
    public function getConfig()
    {
        $status = $this->isActive();

        if ($status == true) { //
            $config['IsColliveryActive'] = true;
        } else {
            $config['IsColliveryActive'] = false;
        }

        return $config;
    }
}

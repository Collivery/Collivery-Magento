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
        return $config['IsColliveryActive'] = $this->isActive();
    }
}

<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\Keycloak\Model;

use Magento\Framework\Model\AbstractModel;

class Identity extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Forbesons\Keycloak\Model\ResourceModel\Identity::class);
    }
}
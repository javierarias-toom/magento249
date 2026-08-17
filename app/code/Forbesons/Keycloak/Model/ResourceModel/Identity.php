<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\Keycloak\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Identity extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('forbesons_keycloak_identity', 'identity_id');
    }
}
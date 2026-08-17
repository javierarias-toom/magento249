<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\Keycloak\Model\ResourceModel\Identity;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'identity_id';

    protected function _construct()
    {
        $this->_init(
            \Forbesons\Keycloak\Model\Identity::class,
            \Forbesons\Keycloak\Model\ResourceModel\Identity::class
        );
    }
}
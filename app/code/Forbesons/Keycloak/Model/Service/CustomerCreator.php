<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\Keycloak\Model\Service;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Math\Random;
use Magento\Store\Model\StoreManagerInterface;

class CustomerCreator
{
    private CustomerFactory $customerFactory;
    private CustomerResource $customerResource;
    private EncryptorInterface $encryptor;
    private Random $random;
    private StoreManagerInterface $storeManager;

    public function __construct(
        CustomerFactory $customerFactory,
        CustomerResource $customerResource,
        EncryptorInterface $encryptor,
        Random $random,
        StoreManagerInterface $storeManager
    ) {
        $this->customerFactory = $customerFactory;
        $this->customerResource = $customerResource;
        $this->encryptor = $encryptor;
        $this->random = $random;
        $this->storeManager = $storeManager;
    }

    /**
     * @param array{sub: string, email: string, given_name: string, family_name: string} $profile
     */
    public function create(array $profile): Customer
    {
        $website = $this->storeManager->getWebsite();
        $store = $website->getDefaultStore();

        $password = $this->random->getRandomString(32, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');

        /** @var Customer $customer */
        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($website->getId());
        $customer->setStoreId($store ? $store->getId() : 0);
        $customer->setEmail($profile['email']);
        $customer->setFirstname($profile['given_name'] ?: 'Keycloak');
        $customer->setLastname($profile['family_name'] ?: 'User');
        $customer->setConfirmation(null);

        try {
            $this->customerResource->save($customer);
            $this->customerResource->saveAttribute($customer, 'password_hash');
            $customer->setData('password_hash', $this->encryptor->getHash($password, true));
            $this->customerResource->saveAttribute($customer, 'password_hash');
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to create a customer account for the email %1.', $profile['email']), $e);
        }
        return $customer;
    }
}
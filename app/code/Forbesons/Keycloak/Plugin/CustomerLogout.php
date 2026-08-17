<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\Keycloak\Plugin;

use Forbesons\Keycloak\Model\Service\IdentityLinker;
use Forbesons\Keycloak\Model\Service\UrlBuilder;
use Forbesons\Keycloak\Model\Session\AuthState;
use Magento\Customer\Controller\Account\Logout;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;

class CustomerLogout
{
    private IdentityLinker $identityLinker;
    private AuthState $authState;
    private UrlBuilder $urlBuilder;
    private RedirectFactory $redirectFactory;
    private CustomerSession $customerSession;

    public function __construct(
        IdentityLinker $identityLinker,
        AuthState $authState,
        UrlBuilder $urlBuilder,
        RedirectFactory $redirectFactory,
        CustomerSession $customerSession
    ) {
        $this->identityLinker = $identityLinker;
        $this->authState = $authState;
        $this->urlBuilder = $urlBuilder;
        $this->redirectFactory = $redirectFactory;
        $this->customerSession = $customerSession;
    }

    public function aroundExecute(Logout $subject, callable $proceed)
    {
        $idToken = $this->authState->getIdToken();
        $isKeycloakUser = false;
        if ($this->customerSession->isLoggedIn()) {
            $identity = $this->identityLinker->findByCustomerId((int)$this->customerSession->getId());
            $isKeycloakUser = $identity !== null;
        }

        $result = $proceed();

        if (!$isKeycloakUser || $idToken === null) {
            return $result;
        }
        /** @var Redirect $redirect */
        $redirect = $this->redirectFactory->create();
        return $redirect->setUrl($this->urlBuilder->getEndSessionUrl($idToken));
    }
}
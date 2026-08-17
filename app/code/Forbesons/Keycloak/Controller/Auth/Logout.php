<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\Keycloak\Controller\Auth;

use Forbesons\Keycloak\Model\Service\UrlBuilder;
use Forbesons\Keycloak\Model\Session\AuthState;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;

class Logout implements HttpPostActionInterface
{
    private RequestInterface $request;
    private RedirectFactory $redirectFactory;
    private AuthState $authState;
    private CustomerSession $customerSession;
    private UrlBuilder $urlBuilder;

    public function __construct(
        RequestInterface $request,
        RedirectFactory $redirectFactory,
        AuthState $authState,
        CustomerSession $customerSession,
        UrlBuilder $urlBuilder
    ) {
        $this->request = $request;
        $this->redirectFactory = $redirectFactory;
        $this->authState = $authState;
        $this->customerSession = $customerSession;
        $this->urlBuilder = $urlBuilder;
    }

    public function execute()
    {
        $idToken = $this->authState->getIdToken();
        $this->customerSession->logout();
        $this->authState->clear();

        /** @var Redirect $result */
        $result = $this->redirectFactory->create();
        if ($idToken) {
            return $result->setUrl($this->urlBuilder->getEndSessionUrl($idToken));
        }
        return $result->setPath('customer/account');
    }
}
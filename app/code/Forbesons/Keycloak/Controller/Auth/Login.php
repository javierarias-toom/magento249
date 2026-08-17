<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\Keycloak\Controller\Auth;

use Forbesons\Keycloak\Model\Config;
use Forbesons\Keycloak\Model\Service\UrlBuilder;
use Forbesons\Keycloak\Model\Session\AuthState;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;

class Login implements HttpGetActionInterface
{
    private Config $config;
    private UrlBuilder $urlBuilder;
    private AuthState $authState;
    private RedirectFactory $redirectFactory;

    public function __construct(
        Config $config,
        UrlBuilder $urlBuilder,
        AuthState $authState,
        RedirectFactory $redirectFactory
    ) {
        $this->config = $config;
        $this->urlBuilder = $urlBuilder;
        $this->authState = $authState;
        $this->redirectFactory = $redirectFactory;
    }

    public function execute()
    {
        if (!$this->config->isEnabled()) {
            return $this->redirectFactory->create()->setPath('customer/account/login');
        }
        $params = $this->authState->start();
        $url = $this->urlBuilder->getAuthorizationUrl(
            $params['state'],
            $params['nonce'],
            $params['challenge']
        );
        /** @var Redirect $result */
        $result = $this->redirectFactory->create();
        return $result->setUrl($url);
    }
}
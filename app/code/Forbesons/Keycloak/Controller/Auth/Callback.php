<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\Keycloak\Controller\Auth;

use Forbesons\Keycloak\Model\Config;
use Forbesons\Keycloak\Model\Oidc\ClaimsExtractor;
use Forbesons\Keycloak\Model\Oidc\IdTokenValidator;
use Forbesons\Keycloak\Model\Oidc\TokenExchanger;
use Forbesons\Keycloak\Model\Service\CustomerCreator;
use Forbesons\Keycloak\Model\Service\IdentityLinker;
use Forbesons\Keycloak\Model\Service\UrlBuilder;
use Forbesons\Keycloak\Model\Session\AuthState;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;

class Callback implements HttpGetActionInterface
{
    private RequestInterface $request;
    private RedirectFactory $redirectFactory;
    private AuthState $authState;
    private Config $config;
    private TokenExchanger $tokenExchanger;
    private IdTokenValidator $idTokenValidator;
    private ClaimsExtractor $claimsExtractor;
    private IdentityLinker $identityLinker;
    private CustomerCreator $customerCreator;
    private CustomerRepositoryInterface $customerRepository;
    private CustomerSession $customerSession;
    private UrlBuilder $urlBuilder;
    private ManagerInterface $messageManager;

    public function __construct(
        RequestInterface $request,
        RedirectFactory $redirectFactory,
        AuthState $authState,
        Config $config,
        TokenExchanger $tokenExchanger,
        IdTokenValidator $idTokenValidator,
        ClaimsExtractor $claimsExtractor,
        IdentityLinker $identityLinker,
        CustomerCreator $customerCreator,
        CustomerRepositoryInterface $customerRepository,
        CustomerSession $customerSession,
        UrlBuilder $urlBuilder,
        ManagerInterface $messageManager
    ) {
        $this->request = $request;
        $this->redirectFactory = $redirectFactory;
        $this->authState = $authState;
        $this->config = $config;
        $this->tokenExchanger = $tokenExchanger;
        $this->idTokenValidator = $idTokenValidator;
        $this->claimsExtractor = $claimsExtractor;
        $this->identityLinker = $identityLinker;
        $this->customerCreator = $customerCreator;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->urlBuilder = $urlBuilder;
        $this->messageManager = $messageManager;
    }

    public function execute()
    {
        $error = $this->request->getParam('error');
        if ($error) {
            return $this->fail(__('The sign-in could not be completed. %1', $error));
        }
        $code = $this->request->getParam('code');
        $state = $this->request->getParam('state');
        if (!$code || !$state) {
            return $this->fail(__('The sign-in response is missing required parameters.'));
        }

        $auth = $this->authState->consume((string)$state);
        if ($auth === null) {
            return $this->fail(__('The sign-in request has expired. Please try again.'));
        }

        try {
            $tokens = $this->tokenExchanger->exchange(
                (string)$code,
                $this->urlBuilder->getRedirectUri(),
                (string)$auth['verifier']
            );
            $claims = $this->idTokenValidator->validate($tokens['id_token'], (string)$auth['nonce']);
            $profile = $this->claimsExtractor->extract($claims);

            if (!$profile['email']) {
                return $this->fail(__('The identity provider did not provide an email address.'));
            }
            if ($this->config->isEmailVerificationRequired() && !$profile['email_verified']) {
                return $this->fail(__('Your email address is not verified in Forbesons. Please verify it first.'));
            }
            if ($this->config->getRequiredRole()
                && !in_array($this->config->getRequiredRole(), $profile['roles'], true)) {
                return $this->fail(__('Your Forbesons account does not have the required customer role.'));
            }

            $identity = $this->identityLinker->findBySub($profile['sub']);
            if ($identity) {
                $customerId = (int)$identity->getCustomerId();
            } else {
                $customerId = $this->resolveCustomer($profile);
                $this->identityLinker->link($customerId, $profile['sub']);
            }

            $this->customerSession->loginById($customerId);
            $this->authState->saveIdToken($tokens['id_token']);

            /** @var Redirect $result */
            $result = $this->redirectFactory->create();
            return $result->setPath('customer/account');
        } catch (LocalizedException $e) {
            return $this->fail($e->getMessage());
        } catch (\Exception $e) {
            return $this->fail(__('An unexpected error occurred during sign-in.'));
        }
    }

    private function resolveCustomer(array $profile): int
    {
        if (!$this->config->canCreateCustomer()) {
            throw new LocalizedException(__('Customer accounts are not created automatically.'));
        }
        try {
            $existing = $this->customerRepository->getByEmail($profile['email']);
            if ($existing->getId()) {
                throw new LocalizedException(
                    __('An account with this email already exists. Sign in with your password instead, or contact support to link it to Forbesons.')
                );
            }
        } catch (NoSuchEntityException $e) {
            // No existing customer: create a new one.
        }
        $customer = $this->customerCreator->create($profile);
        return (int)$customer->getId();
    }

    private function fail(string $message): Redirect
    {
        $this->messageManager->addErrorMessage($message);
        /** @var Redirect $result */
        $result = $this->redirectFactory->create();
        return $result->setPath('customer/account/login');
    }
}
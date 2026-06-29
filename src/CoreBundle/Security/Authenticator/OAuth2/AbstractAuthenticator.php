<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authenticator\OAuth2;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use KnpU\OAuth2ClientBundle\Security\Exception\InvalidStateAuthenticationException;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

abstract class AbstractAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    protected string $providerName = '';

    protected OAuth2ClientInterface $client;

    public function __construct(
        protected readonly ClientRegistry $clientRegistry,
        protected readonly RouterInterface $router,
        protected readonly UserRepository $userRepository,
        protected readonly AuthenticationConfigHelper $authenticationConfigHelper,
        protected readonly AccessUrlHelper $accessUrlHelper,
        protected readonly EntityManagerInterface $entityManager,
    ) {
        $this->client = $this->clientRegistry->getClient($this->providerName);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $targetUrl = $this->router->generate('login');

        return new RedirectResponse($targetUrl);
    }

    abstract public function supports(Request $request): ?bool;

    public function authenticate(Request $request): Passport
    {
        $accessToken = $this->fetchAccessToken($this->client);

        $user = $this->userLoader($accessToken);

        $passport = new SelfValidatingPassport(
            new UserBadge(
                $user->getUserIdentifier()
            ),
        );

        if ($customBadge = $this->getCustomBadge()) {
            $passport->addBadge($customBadge);
        }

        return $passport;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $targetUrl = $this->router->generate('index');

        return new RedirectResponse($targetUrl);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // On invalid OAuth2 state (session lost between redirect and callback), restart the
        // auth flow silently. One auto-retry per provider; a second failure falls through to
        // the normal error path so a broken session can't loop indefinitely.
        if ($exception instanceof InvalidStateAuthenticationException && $request->hasSession()) {
            $session = $request->getSession();
            $retryKey = '_oauth2_state_retry_'.$this->providerName;

            if (!$session->get($retryKey, false)) {
                $session->set($retryKey, true);
                $startRoute = 'chamilo.oauth2_'.$this->providerName.'_start';

                return new RedirectResponse($this->router->generate($startRoute));
            }

            $session->remove($retryKey);
        }

        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        if ($request->hasSession()) {
            $request->getSession()->getFlashBag()->add('error', $message);
        }

        return new RedirectResponse($this->router->generate('index'));
    }

    /**
     * Find or create and save the new user.
     */
    abstract protected function userLoader(AccessToken $accessToken): User;

    abstract protected function getCustomBadge(): ?BadgeInterface;
}

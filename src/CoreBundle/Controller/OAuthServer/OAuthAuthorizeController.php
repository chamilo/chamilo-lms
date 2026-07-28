<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\OAuthServer;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Exception\OAuthServer\OAuthException;
use Chamilo\CoreBundle\Service\OAuthServer\OAuthAuthorizationService;
use Chamilo\CoreBundle\Service\OAuthServer\OAuthClientResolver;
use Chamilo\CoreBundle\Settings\SettingsManager;
use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * The /oauth/authorize endpoint: real Chamilo login (via the normal /login
 * page, whatever backend the portal uses) followed by a real consent screen.
 * There is deliberately no auto-approve path anywhere in this controller.
 */
final class OAuthAuthorizeController extends BaseController
{
    private const string CSRF_INTENT = 'oauth_consent';

    public function __construct(
        private readonly OAuthAuthorizationService $authorizationService,
        private readonly OAuthClientResolver $clientResolver,
        private readonly SettingsManager $settingsManager,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly RateLimiterFactory $oauthAuthorizationLimiter,
    ) {}

    #[Route('/oauth/authorize', name: 'oauth_authorize', methods: ['GET'])]
    public function authorize(Request $request): Response
    {
        $this->assertEnabled();
        $this->consumeRateLimit($request);

        try {
            $resolved = $this->authorizationService->resolveClientAndRedirectUri($request);
        } catch (RuntimeException $exception) {
            return $this->renderError($exception->getMessage());
        }

        $client = $resolved['client'];
        $redirectUri = $resolved['redirectUri'];

        try {
            $authorizeRequest = $this->authorizationService->validateAuthorizeParameters($request);
        } catch (OAuthException $exception) {
            return new RedirectResponse($this->authorizationService->buildRedirectUrl($redirectUri, [
                'error' => $exception->getErrorCode(),
                'error_description' => $exception->getMessage(),
                'state' => (string) $request->query->get('state', ''),
            ]));
        }

        $this->authorizationService->stashInSession($request, $authorizeRequest);

        $user = $this->getUser();
        if (!$user instanceof User) {
            $queryString = $request->getQueryString();
            $target = $request->getPathInfo().(null !== $queryString && '' !== $queryString ? '?'.$queryString : '');

            return new RedirectResponse('/login?redirect='.rawurlencode($target));
        }

        if (!$this->authorizationService->assertUserActiveOnCurrentPortal($user)) {
            return $this->renderError('Your account is not currently active on this Chamilo portal.');
        }

        return $this->render('@ChamiloCore/OAuthServer/consent.html.twig', [
            'client' => $client,
            'oauth_user' => $user,
            'redirect_uri' => $redirectUri,
            'resource' => $authorizeRequest->resource,
            'csrf_token' => $this->csrfTokenManager->getToken(self::CSRF_INTENT)->getValue(),
        ]);
    }

    #[Route('/oauth/authorize', name: 'oauth_authorize_consent', methods: ['POST'])]
    public function consent(Request $request): Response
    {
        $this->assertEnabled();
        $this->consumeRateLimit($request);

        $token = (string) $request->request->get('_token', '');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_INTENT, $token))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $authorizeRequest = $this->authorizationService->popFromSession($request);
        if (null === $authorizeRequest
            || $authorizeRequest->isStale(time(), OAuthAuthorizationService::PENDING_REQUEST_TTL_SECONDS)
        ) {
            return $this->renderError('Your authorization request has expired. Please start again.');
        }

        $client = $this->clientResolver->resolveActive($authorizeRequest->clientId);
        if (null === $client || !$client->supportsRedirectUri($authorizeRequest->redirectUri)) {
            return $this->renderError('This application is no longer available. Please start again.');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Authentication is required.');
        }

        if (!$this->authorizationService->assertUserActiveOnCurrentPortal($user)) {
            return $this->renderError('Your account is not currently active on this Chamilo portal.');
        }

        $action = (string) $request->request->get('action', '');

        if ('deny' === $action) {
            return new RedirectResponse($this->authorizationService->buildRedirectUrl($authorizeRequest->redirectUri, [
                'error' => 'access_denied',
                'state' => $authorizeRequest->state,
            ]));
        }

        if ('allow' !== $action) {
            return $this->renderError('Invalid request.');
        }

        $code = $this->authorizationService->issueCode($client, $authorizeRequest, $user, $request);

        $response = new RedirectResponse($this->authorizationService->buildRedirectUrl($authorizeRequest->redirectUri, [
            'code' => $code,
            'state' => $authorizeRequest->state,
        ]));
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }

    private function consumeRateLimit(Request $request): void
    {
        $limiter = $this->oauthAuthorizationLimiter->create((string) $request->getClientIp());
        if (!$limiter->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException();
        }
    }

    private function assertEnabled(): void
    {
        if ('true' !== $this->settingsManager->getSetting('security.oauth_server_enabled')) {
            throw new NotFoundHttpException();
        }
    }

    private function renderError(string $message): Response
    {
        return $this->render(
            '@ChamiloCore/OAuthServer/error.html.twig',
            ['message' => $message],
            new Response('', Response::HTTP_BAD_REQUEST),
        );
    }
}

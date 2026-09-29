<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\OAuthServer;

use Chamilo\CoreBundle\Exception\OAuthServer\OAuthException;
use Chamilo\CoreBundle\Service\OAuthServer\OAuthClientResolver;
use Chamilo\CoreBundle\Service\OAuthServer\OAuthTokenService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The OAuth 2.1 token endpoint (RFC 6749 §3.2): authorization_code and
 * refresh_token grants. Public: the client authenticates via an explicit
 * secret in the request body (PKCE verifier or client_secret), never via a
 * Chamilo session — so CSRF protection does not apply here.
 */
#[AsController]
final readonly class OAuthTokenController
{
    public function __construct(
        private OAuthClientResolver $clientResolver,
        private OAuthTokenService $tokenService,
        private SettingsManager $settingsManager,
        private RateLimiterFactory $oauthTokenLimiter,
    ) {}

    #[Route('/oauth/token', name: 'oauth_token', methods: ['POST'])]
    public function token(Request $request): JsonResponse
    {
        if ('true' !== $this->settingsManager->getSetting('security.oauth_server_enabled')) {
            throw new NotFoundHttpException();
        }

        $limiter = $this->oauthTokenLimiter->create((string) $request->getClientIp());
        if (!$limiter->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException();
        }

        try {
            $client = $this->clientResolver->authenticateFromRequest($request);

            $grantType = (string) $request->request->get('grant_type', '');
            $result = match ($grantType) {
                'authorization_code' => $this->tokenService->exchangeAuthorizationCode($client, $request->request->all()),
                'refresh_token' => $this->tokenService->refresh($client, $request->request->all()),
                default => throw OAuthException::unsupportedGrantType(),
            };
        } catch (OAuthException $exception) {
            return $this->errorResponse($exception);
        }

        $response = new JsonResponse($result);
        $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    private function errorResponse(OAuthException $exception): JsonResponse
    {
        $response = new JsonResponse(
            ['error' => $exception->getErrorCode(), 'error_description' => $exception->getMessage()],
            $exception->getHttpStatusCode(),
        );
        $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('Pragma', 'no-cache');

        foreach ($exception->getExtraHeaders() as $name => $value) {
            $response->headers->set($name, $value);
        }

        return $response;
    }
}

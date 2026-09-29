<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\OAuthServer;

use Chamilo\CoreBundle\Exception\OAuthServer\OAuthException;
use Chamilo\CoreBundle\Service\OAuthServer\OAuthClientResolver;
use Chamilo\CoreBundle\Service\OAuthServer\OAuthTokenService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Token revocation (RFC 7009). Always returns 200 with an empty body,
 * regardless of whether the token existed, was already revoked, or belonged
 * to a different client — never disclose which (RFC 7009 §2.2).
 */
#[AsController]
final readonly class OAuthRevocationController
{
    public function __construct(
        private OAuthClientResolver $clientResolver,
        private OAuthTokenService $tokenService,
        private SettingsManager $settingsManager,
        private RateLimiterFactory $oauthRevocationLimiter,
    ) {}

    #[Route('/oauth/revoke', name: 'oauth_revoke', methods: ['POST'])]
    public function revoke(Request $request): Response
    {
        if ('true' !== $this->settingsManager->getSetting('security.oauth_server_enabled')) {
            throw new NotFoundHttpException();
        }

        $limiter = $this->oauthRevocationLimiter->create((string) $request->getClientIp());
        if (!$limiter->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException();
        }

        $token = (string) $request->request->get('token', '');
        $tokenTypeHint = $request->request->get('token_type_hint');
        $tokenTypeHint = \is_string($tokenTypeHint) ? $tokenTypeHint : null;

        try {
            $client = $this->clientResolver->authenticateFromRequest($request);

            if ('' !== $token) {
                $this->tokenService->revoke($client, $token, $tokenTypeHint);
            }
        } catch (OAuthException $exception) {
            $response = new Response('', $exception->getHttpStatusCode());
            foreach ($exception->getExtraHeaders() as $name => $value) {
                $response->headers->set($name, $value);
            }

            return $response;
        }

        $response = new Response('', Response::HTTP_OK);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}

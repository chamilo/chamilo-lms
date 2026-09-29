<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\OAuthServer;

use Chamilo\CoreBundle\Exception\OAuthServer\OAuthException;
use Chamilo\CoreBundle\Service\OAuthServer\OAuthClientRegistrar;
use Chamilo\CoreBundle\Settings\SettingsManager;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

use const JSON_THROW_ON_ERROR;

/**
 * Dynamic Client Registration (RFC 7591). Public, unauthenticated: it
 * registers an application, never a person. No new Chamilo user account is
 * ever created here.
 */
#[AsController]
final readonly class OAuthRegistrationController
{
    private const int MAX_BODY_BYTES = 8192;

    public function __construct(
        private OAuthClientRegistrar $registrar,
        private SettingsManager $settingsManager,
        private RateLimiterFactory $oauthRegistrationLimiter,
    ) {}

    #[Route('/oauth/register', name: 'oauth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        if ('true' !== $this->settingsManager->getSetting('security.oauth_server_enabled')) {
            throw new NotFoundHttpException();
        }

        $limiter = $this->oauthRegistrationLimiter->create($request->getClientIp());
        if (!$limiter->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException();
        }

        $content = $request->getContent();
        if (mb_strlen($content) > self::MAX_BODY_BYTES) {
            throw new BadRequestHttpException('The registration request body is too large.');
        }

        try {
            $metadata = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $metadata = null;
        }

        if (!\is_array($metadata)) {
            return $this->errorResponse('invalid_client_metadata', 'The request body must contain a valid JSON object.', 400);
        }

        try {
            $response = $this->registrar->register($metadata, (string) $request->getClientIp());
        } catch (OAuthException $exception) {
            return $this->errorResponse($exception->getErrorCode(), $exception->getMessage(), $exception->getHttpStatusCode());
        }

        $json = new JsonResponse($response, Response::HTTP_CREATED);
        $json->headers->set('Cache-Control', 'no-store');
        $json->headers->set('Pragma', 'no-cache');

        return $json;
    }

    private function errorResponse(string $error, string $description, int $status): JsonResponse
    {
        $response = new JsonResponse(['error' => $error, 'error_description' => $description], $status);
        $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}

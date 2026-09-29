<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\OAuthServer;

use Chamilo\CoreBundle\Service\OAuthServer\OAuthMetadataService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * OAuth Authorization Server discovery documents (RFC 8414, RFC 9728).
 *
 * Public, unauthenticated, cacheable. Not resource-specific: any resource
 * server hosted on this AS (today, only /mcp) is reachable via the same
 * authorization-server document; the protected-resource document accepts an
 * optional path suffix per RFC 9728's path-insertion convention.
 */
#[AsController]
final readonly class OAuthDiscoveryController
{
    public function __construct(
        private OAuthMetadataService $metadata,
        private SettingsManager $settingsManager,
    ) {}

    #[Route(
        '/.well-known/oauth-protected-resource',
        name: 'oauth_protected_resource_metadata',
        methods: ['GET'],
    )]
    #[Route(
        '/.well-known/oauth-protected-resource/{resourcePath}',
        name: 'oauth_protected_resource_metadata_scoped',
        requirements: ['resourcePath' => '.+'],
        methods: ['GET'],
    )]
    public function protectedResourceMetadata(string $resourcePath = 'mcp'): JsonResponse
    {
        $this->assertEnabled();

        return $this->cached(
            new JsonResponse($this->metadata->buildProtectedResourceMetadata($resourcePath)),
        );
    }

    #[Route(
        '/.well-known/oauth-authorization-server',
        name: 'oauth_authorization_server_metadata',
        methods: ['GET'],
    )]
    #[Route(
        '/.well-known/oauth-authorization-server/{resourcePath}',
        name: 'oauth_authorization_server_metadata_scoped',
        requirements: ['resourcePath' => '.+'],
        methods: ['GET'],
    )]
    public function authorizationServerMetadata(): JsonResponse
    {
        $this->assertEnabled();

        return $this->cached(
            new JsonResponse($this->metadata->buildAuthorizationServerMetadata()),
        );
    }

    private function cached(JsonResponse $response): JsonResponse
    {
        $response->headers->set('Cache-Control', 'public, max-age=3600');

        return $response;
    }

    private function assertEnabled(): void
    {
        if ('true' !== $this->settingsManager->getSetting('security.oauth_server_enabled')) {
            throw new NotFoundHttpException();
        }
    }
}

<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\OAuthServer;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\OAuthClient;
use Chamilo\CoreBundle\Exception\OAuthServer\OAuthException;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\OAuthClientRepository;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Read path for OAuthClient: resolving and authenticating a client from a
 * request. Deliberately separate from OAuthClientRegistrar so the token
 * endpoint's read/auth path never touches client creation.
 */
final readonly class OAuthClientResolver
{
    public function __construct(
        private OAuthClientRepository $clientRepository,
        private AccessUrlHelper $accessUrlHelper,
    ) {}

    public function resolveActive(string $clientId): ?OAuthClient
    {
        $accessUrl = $this->requireAccessUrl();

        return $this->clientRepository->findActiveByClientIdAndAccessUrl($clientId, (int) $accessUrl->getId());
    }

    /**
     * Authenticates the client making the request per RFC 6749 §2.3:
     * client_id (+ optional client_secret) in the body, or HTTP Basic. Public
     * clients (token_endpoint_auth_method "none") only need a valid client_id.
     */
    public function authenticateFromRequest(Request $request): OAuthClient
    {
        $clientId = (string) $request->request->get('client_id', '');
        $clientSecret = (string) $request->request->get('client_secret', '');
        $usedBasicAuth = false;

        if ('' === $clientId) {
            $header = (string) $request->headers->get('Authorization', '');
            if (str_starts_with($header, 'Basic ')) {
                $decoded = base64_decode(mb_substr($header, 6), true);
                if (\is_string($decoded) && str_contains($decoded, ':')) {
                    [$rawClientId, $rawClientSecret] = explode(':', $decoded, 2);
                    $clientId = rawurldecode($rawClientId);
                    $clientSecret = rawurldecode($rawClientSecret);
                    $usedBasicAuth = true;
                }
            }
        }

        if ('' === $clientId) {
            throw OAuthException::invalidClient();
        }

        $client = $this->resolveActive($clientId);
        if (!$client instanceof OAuthClient) {
            throw OAuthException::invalidClient();
        }

        if ($client->isPublicClient()) {
            return $client;
        }

        $hash = $client->getClientSecretHash();
        if (null === $hash || '' === $clientSecret || !hash_equals($hash, hash('sha256', $clientSecret))) {
            throw OAuthException::invalidClient('Client authentication failed.', $usedBasicAuth ? ['WWW-Authenticate' => 'Basic'] : []);
        }

        return $client;
    }

    private function requireAccessUrl(): AccessUrl
    {
        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (!$accessUrl instanceof AccessUrl || null === $accessUrl->getId()) {
            throw new RuntimeException('The current access URL could not be resolved.');
        }

        return $accessUrl;
    }
}

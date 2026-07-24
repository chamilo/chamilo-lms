<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Mcp;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserApiKey;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\UserApiKeyRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class McpApiKeyManager
{
    public const KEY_PREFIX = 'chamilo_mcp_';
    public const SERVICE = 'mcp';

    public function __construct(
        private UserHelper $userHelper,
        private AccessUrlHelper $accessUrlHelper,
        private AccessUrlRepository $accessUrlRepository,
        private UserApiKeyRepository $apiKeyRepository,
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getCurrentMetadata(): array
    {
        [$user, $accessUrl] = $this->resolveCurrentContext();
        $apiKey = $this->apiKeyRepository->findForUserAndAccessUrl(
            (int) $user->getId(),
            (int) $accessUrl->getId(),
            self::SERVICE,
        );

        return $this->normalize($apiKey, $accessUrl);
    }

    /**
     * @return array<string, mixed>
     */
    public function generateForCurrentUser(): array
    {
        [$user, $accessUrl] = $this->resolveCurrentContext();
        $now = new DateTime();
        $plainKey = self::KEY_PREFIX.$this->base64UrlEncode(random_bytes(32));
        $hash = hash('sha256', $plainKey);
        $visiblePrefix = mb_substr($plainKey, 0, 24);

        $apiKey = $this->apiKeyRepository->findForUserAndAccessUrl(
            (int) $user->getId(),
            (int) $accessUrl->getId(),
            self::SERVICE,
        );

        if (!$apiKey instanceof UserApiKey) {
            $apiKey = (new UserApiKey())
                ->setUserId((int) $user->getId())
                ->setApiService(self::SERVICE)
                ->setAccessUrlId((int) $accessUrl->getId())
            ;
            $this->entityManager->persist($apiKey);
        }

        $apiKey
            ->setApiKey($hash)
            ->setApiEndPoint($this->buildEndpoint($accessUrl))
            ->setCreatedDate($now)
            ->setValidityStartDate($now)
            ->setValidityEndDate(null)
            ->setDescription('Personal MCP API key')
            ->setKeyPrefix($visiblePrefix)
            ->setLastUsedAt(null)
            ->setRevokedAt(null)
        ;

        $this->entityManager->flush();

        $metadata = $this->normalize($apiKey, $accessUrl);
        $metadata['plainKey'] = $plainKey;

        return $metadata;
    }

    public function revokeForCurrentUser(): void
    {
        [$user, $accessUrl] = $this->resolveCurrentContext();
        $apiKey = $this->apiKeyRepository->findForUserAndAccessUrl(
            (int) $user->getId(),
            (int) $accessUrl->getId(),
            self::SERVICE,
        );

        if (!$apiKey instanceof UserApiKey || null !== $apiKey->getRevokedAt()) {
            return;
        }

        $apiKey->setRevokedAt(new DateTime());
        $this->entityManager->flush();
    }

    public static function isMcpKey(string $plainKey): bool
    {
        if (!str_starts_with($plainKey, self::KEY_PREFIX)) {
            return false;
        }

        $secret = substr($plainKey, strlen(self::KEY_PREFIX));

        return 1 === preg_match('/^[A-Za-z0-9_-]{43}$/', $secret);
    }

    private function buildEndpoint(AccessUrl $accessUrl): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null !== $request) {
            return rtrim(
                $request->getSchemeAndHttpHost().$request->getBaseUrl(),
                '/',
            ).'/mcp';
        }

        return rtrim($accessUrl->getUrl(), '/').'/mcp';
    }

    /**
     * @return array{0: User, 1: AccessUrl}
     */
    private function resolveCurrentContext(): array
    {
        $user = $this->userHelper->getCurrent();
        if (!$user instanceof User || null === $user->getId()) {
            throw new AccessDeniedException('Authentication is required.');
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (!$accessUrl instanceof AccessUrl || null === $accessUrl->getId()) {
            throw new RuntimeException('The current access URL could not be resolved.');
        }

        if (!$this->accessUrlRepository->isUrlActiveForUser($accessUrl, $user)) {
            throw new AccessDeniedException('The authenticated user is not active on this access URL.');
        }

        return [$user, $accessUrl];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalize(?UserApiKey $apiKey, AccessUrl $accessUrl): array
    {
        $now = new DateTime();
        $active = $apiKey instanceof UserApiKey && $apiKey->isActiveAt($now);
        $prefix = $apiKey?->getKeyPrefix();

        return [
            'id' => 'current',
            'active' => $active,
            'maskedKey' => null !== $prefix && '' !== $prefix ? $prefix.'••••••••••••' : null,
            'plainKey' => null,
            'endpoint' => $this->buildEndpoint($accessUrl),
            'createdAt' => $apiKey?->getCreatedDate()?->format(DATE_ATOM),
            'lastUsedAt' => $apiKey?->getLastUsedAt()?->format(DATE_ATOM),
            'revokedAt' => $apiKey?->getRevokedAt()?->format(DATE_ATOM),
        ];
    }

    private function base64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}

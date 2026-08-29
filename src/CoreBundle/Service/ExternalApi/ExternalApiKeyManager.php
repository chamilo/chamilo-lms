<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\ExternalApi;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserApiKey;
use Chamilo\CoreBundle\Repository\UserApiKeyRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

use const DATE_ATOM;

/**
 * Issues and revokes "external" API keys: long-lived, per-user, per-portal
 * bearer credentials for trusted server-to-server integrations (e.g. the
 * WordPress storefront plugin's service account) against the general /api
 * firewall. Sibling of Chamilo\CoreBundle\Service\Mcp\McpApiKeyManager,
 * scoped to a caller-supplied User rather than "the current user", since
 * this is driven by an admin-run console command, not a self-service page.
 */
final readonly class ExternalApiKeyManager
{
    public const string KEY_PREFIX = 'chamilo_ext_';
    public const string SERVICE = 'external';

    public function __construct(
        private UserApiKeyRepository $apiKeyRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @return array{plainKey: string, keyPrefix: string, createdAt: string}
     */
    public function generateForUser(User $user, AccessUrl $accessUrl): array
    {
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
            ->setCreatedDate($now)
            ->setValidityStartDate($now)
            ->setValidityEndDate(null)
            ->setDescription('External API key (server-to-server integrations, e.g. the WordPress storefront plugin)')
            ->setKeyPrefix($visiblePrefix)
            ->setLastUsedAt(null)
            ->setRevokedAt(null)
        ;

        $this->entityManager->flush();

        return [
            'plainKey' => $plainKey,
            'keyPrefix' => $visiblePrefix,
            'createdAt' => $now->format(DATE_ATOM),
        ];
    }

    public function revokeForUser(User $user, AccessUrl $accessUrl): bool
    {
        $apiKey = $this->apiKeyRepository->findForUserAndAccessUrl(
            (int) $user->getId(),
            (int) $accessUrl->getId(),
            self::SERVICE,
        );

        if (!$apiKey instanceof UserApiKey || null !== $apiKey->getRevokedAt()) {
            return false;
        }

        $apiKey->setRevokedAt(new DateTime());
        $this->entityManager->flush();

        return true;
    }

    public static function isExternalKey(string $plainKey): bool
    {
        if (!str_starts_with($plainKey, self::KEY_PREFIX)) {
            return false;
        }

        $secret = substr($plainKey, \strlen(self::KEY_PREFIX));

        return 1 === preg_match('/^[A-Za-z0-9_-]{43}$/', $secret);
    }

    private function base64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}

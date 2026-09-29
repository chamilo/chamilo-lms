<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\OAuthServer;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\OAuthRefreshToken;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\OAuthRefreshTokenRepository;
use DateTime;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use const DATE_ATOM;

/**
 * Self-service surface for "Authorized applications": listing and revoking
 * a user's own OAuth grants. Ownership is enforced here, at the repository
 * query level — never via the API Platform security: expression alone.
 */
final readonly class OAuthGrantManager
{
    public function __construct(
        private UserHelper $userHelper,
        private AccessUrlHelper $accessUrlHelper,
        private AccessUrlRepository $accessUrlRepository,
        private OAuthRefreshTokenRepository $refreshTokenRepository,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForCurrentUser(): array
    {
        [$user, $accessUrl] = $this->resolveCurrentContext();
        $now = new DateTime();
        $grants = $this->refreshTokenRepository->findActiveGrantsForUserAndAccessUrl(
            (int) $user->getId(),
            (int) $accessUrl->getId(),
            $now,
        );

        return array_map($this->normalize(...), $grants);
    }

    public function revokeForCurrentUser(string $grantId): void
    {
        [$user, $accessUrl] = $this->resolveCurrentContext();
        $now = new DateTime();
        $grant = $this->refreshTokenRepository->findActiveGrantByIdForUser(
            $grantId,
            (int) $user->getId(),
            (int) $accessUrl->getId(),
            $now,
        );

        if (!$grant instanceof OAuthRefreshToken) {
            throw new NotFoundHttpException('This authorized application was not found.');
        }

        $this->refreshTokenRepository->revokeFamily($grant->getGrantId(), OAuthRefreshToken::REVOKED_REASON_USER, $now);
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
    private function normalize(OAuthRefreshToken $grant): array
    {
        return [
            'id' => $grant->getGrantId(),
            'clientName' => $grant->getClient()->getClientName() ?? 'Unknown application',
            'clientUri' => $grant->getClient()->getClientUri(),
            'connectedAt' => $grant->getConsentedAt()->format(DATE_ATOM),
            'lastUsedAt' => $grant->getLastUsedAt()?->format(DATE_ATOM),
            'expiresAt' => $grant->getExpiresAt()->format(DATE_ATOM),
            'scope' => $grant->getScope(),
        ];
    }
}

<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelUser;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UrlManager;

use const PHP_SESSION_ACTIVE;

/**
 * Data source for assigning users to access URLs, replacing the legacy
 * access_url_edit_users_to_url.php / access_url_add_users_to_url.php pair.
 */
#[IsGranted('ROLE_GLOBAL_ADMIN')]
#[Route('/admin/access-urls-users-data')]
class AccessUrlUsersController extends AbstractController
{
    private const string CSRF_INTENT = 'access_url_users';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    #[Route('', name: 'admin_access_urls_users_data', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        $accessUrlId = (int) $request->query->get('access_url_id', 0);

        $urls = $this->em->createQueryBuilder()
            ->select('a.id AS id, a.url AS url')
            ->from(AccessUrl::class, 'a')
            ->where('a.active = 1')
            ->orderBy('a.url', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $assignedIds = $this->getAssignedUserIds($accessUrlId);

        $users = $this->em->createQueryBuilder()
            ->select('u.id AS id, u.firstname AS firstname, u.lastname AS lastname, u.username AS username')
            ->from(User::class, 'u')
            ->where('u.active <> :softDeleted')
            ->andWhere('u.status <> :anonymous')
            ->andWhere('u.status <> :fallback')
            ->setParameter('softDeleted', User::SOFT_DELETED)
            ->setParameter('anonymous', User::ANONYMOUS)
            ->setParameter('fallback', User::ROLE_FALLBACK)
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $assigned = [];
        $available = [];
        foreach ($users as $u) {
            $id = (int) $u['id'];
            $item = [
                'id' => $id,
                'firstname' => $u['firstname'],
                'lastname' => $u['lastname'],
                'username' => $u['username'],
            ];
            if (\in_array($id, $assignedIds, true)) {
                $assigned[] = $item;
            } else {
                $available[] = $item;
            }
        }

        return $this->json([
            'urls' => array_map(
                static fn (array $u): array => ['id' => (int) $u['id'], 'url' => $u['url']],
                $urls
            ),
            'assigned' => $assigned,
            'available' => $available,
            'csrfToken' => $this->csrfTokenManager->getToken(self::CSRF_INTENT)->getValue(),
        ]);
    }

    #[Route('', name: 'admin_access_urls_users_assign', methods: ['POST'])]
    public function assign(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        if (!$this->isCsrfTokenValid(self::CSRF_INTENT, (string) ($data['_token'] ?? ''))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $accessUrlId = (int) ($data['access_url_id'] ?? 0);
        if ($accessUrlId <= 0) {
            return $this->json(['error' => 'Select a URL'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $userIds = array_map('intval', $data['user_ids'] ?? []);

        UrlManager::update_urls_rel_user($userIds, $accessUrlId, true);

        return $this->json(['success' => true]);
    }

    #[Route('/bulk', name: 'admin_access_urls_users_bulk', methods: ['POST'])]
    public function bulk(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        if (!$this->isCsrfTokenValid(self::CSRF_INTENT, (string) ($data['_token'] ?? ''))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $userIds = array_map('intval', $data['user_ids'] ?? []);
        $urlIds = array_map('intval', $data['url_ids'] ?? []);
        $action = (string) ($data['action'] ?? '');

        if ([] === $userIds || [] === $urlIds || !\in_array($action, ['add', 'remove'], true)) {
            return $this->json(['error' => 'You must select at least one user and one URL'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ('add' === $action) {
            UrlManager::add_users_to_urls($userIds, $urlIds);
        } else {
            UrlManager::remove_users_from_urls($userIds, $urlIds);
        }

        return $this->json(['success' => true]);
    }

    /**
     * @return int[]
     */
    private function getAssignedUserIds(int $accessUrlId): array
    {
        if ($accessUrlId <= 0) {
            return [];
        }

        $rows = $this->em->createQueryBuilder()
            ->select('IDENTITY(r.user) AS userId')
            ->from(AccessUrlRelUser::class, 'r')
            ->where('r.url = :urlId')
            ->setParameter('urlId', $accessUrlId)
            ->getQuery()
            ->getArrayResult()
        ;

        return array_map(static fn (array $row): int => (int) $row['userId'], $rows);
    }
}

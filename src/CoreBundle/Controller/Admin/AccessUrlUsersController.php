<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlScopeHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UrlManager;

use const PHP_SESSION_ACTIVE;

/**
 * Data source for assigning users to access URLs, replacing the legacy
 * access_url_edit_users_to_url.php / access_url_add_users_to_url.php pair.
 *
 * CSRF is handled globally by CsrfProtectionListener (stateless, origin-based)
 * for every state-changing request the router resolves — no per-endpoint
 * token is generated or checked here.
 *
 * A ROLE_GLOBAL_ADMIN registered in the topmost URL of a tree is unrestricted; one
 * registered only in a non-root URL is scoped to that URL's subtree (AccessUrlScopeHelper)
 * and may only list/assign URLs within it.
 */
#[IsGranted('ROLE_GLOBAL_ADMIN')]
#[Route('/admin/access-urls-users-data')]
class AccessUrlUsersController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AccessUrlScopeHelper $accessUrlScope,
        private readonly UserHelper $userHelper,
    ) {}

    private function currentUser(): User
    {
        $user = $this->userHelper->getCurrent();
        if (null === $user) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    #[Route('', name: 'admin_access_urls_users_data', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        $currentUser = $this->currentUser();
        $managedUrlIds = $this->accessUrlScope->getManagedUrlIds($currentUser);
        $accessUrlId = (int) $request->query->get('access_url_id', '0');
        if ($accessUrlId > 0 && !$this->accessUrlScope->isUrlManaged($currentUser, $accessUrlId)) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $urlsQb = $this->em->createQueryBuilder()
            ->select('a.id AS id, a.url AS url')
            ->from(AccessUrl::class, 'a')
            ->where('a.active = 1')
            ->orderBy('a.url', 'ASC')
        ;
        if (null !== $managedUrlIds) {
            $urlsQb->andWhere('a.id IN (:managedUrlIds)')->setParameter('managedUrlIds', $managedUrlIds);
        }
        $urls = $urlsQb->getQuery()->getArrayResult();

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
        ]);
    }

    #[Route('', name: 'admin_access_urls_users_assign', methods: ['POST'])]
    public function assign(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $accessUrlId = (int) ($data['access_url_id'] ?? 0);
        if ($accessUrlId <= 0) {
            return $this->json(['error' => 'Select a URL'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (!$this->accessUrlScope->isUrlManaged($this->currentUser(), $accessUrlId)) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $userIds = array_map('intval', $data['user_ids'] ?? []);

        UrlManager::update_urls_rel_user($userIds, $accessUrlId, true);

        return $this->json(['success' => true]);
    }

    #[Route('/bulk', name: 'admin_access_urls_users_bulk', methods: ['POST'])]
    public function bulk(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $userIds = array_map('intval', $data['user_ids'] ?? []);
        $urlIds = array_map('intval', $data['url_ids'] ?? []);
        $action = (string) ($data['action'] ?? '');

        if ([] === $userIds || [] === $urlIds || !\in_array($action, ['add', 'remove'], true)) {
            return $this->json(['error' => 'You must select at least one user and one URL'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $currentUser = $this->currentUser();
        foreach ($urlIds as $urlId) {
            if (!$this->accessUrlScope->isUrlManaged($currentUser, $urlId)) {
                return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
            }
        }

        if ('add' === $action) {
            UrlManager::add_users_to_urls($userIds, $urlIds);
        } else {
            UrlManager::remove_users_from_urls($userIds, $urlIds);
        }

        return $this->json(['success' => true]);
    }

    /**
     * Full platform user population for the bulk cross-URL assign tab, independent
     * of any single URL's assignment state. Mirrors the legacy
     * access_url_add_users_to_url.php safeguard: platforms with more than 1000
     * users default to a last-name-starting-with-"A" subset instead of shipping
     * every user in one response, unless the caller explicitly asks for a letter.
     */
    #[Route('/all-users', name: 'admin_access_urls_users_all', methods: ['GET'])]
    public function allUsers(Request $request): JsonResponse
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        $totalUsers = (int) $this->em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->where('u.active <> :softDeleted')
            ->andWhere('u.status <> :anonymous')
            ->andWhere('u.status <> :fallback')
            ->setParameter('softDeleted', User::SOFT_DELETED)
            ->setParameter('anonymous', User::ANONYMOUS)
            ->setParameter('fallback', User::ROLE_FALLBACK)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        // No "first_letter" param at all means the caller has no preference yet
        // (first load): apply the size-based default. An explicit "__all__" is
        // how the caller asks to see everyone despite that default.
        if (!$request->query->has('first_letter')) {
            $firstLetter = $totalUsers > 1000 ? 'A' : '';
        } else {
            $rawFirstLetter = (string) $request->query->get('first_letter', '');
            $firstLetter = '__all__' === $rawFirstLetter ? '' : strtoupper(trim($rawFirstLetter));
        }

        $qb = $this->em->createQueryBuilder()
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
        ;

        if ('' !== $firstLetter) {
            $qb->andWhere('u.lastname LIKE :firstLetter')
                ->setParameter('firstLetter', $firstLetter.'%')
            ;
        }

        $users = $qb->getQuery()->getArrayResult();

        return $this->json([
            'items' => array_map(static fn (array $u): array => [
                'id' => (int) $u['id'],
                'firstname' => $u['firstname'],
                'lastname' => $u['lastname'],
                'username' => $u['username'],
            ], $users),
            'totalUsers' => $totalUsers,
            'appliedFirstLetter' => '' === $firstLetter ? '__all__' : $firstLetter,
        ]);
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

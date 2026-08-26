<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelUserGroup;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
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
 * Data source for assigning user groups (classes) to access URLs, replacing the
 * legacy access_url_edit_usergroup_to_url.php / access_url_add_usergroup_to_url.php pair.
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
#[Route('/admin/access-urls-usergroups-data')]
class AccessUrlUserGroupsController extends AbstractController
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

    #[Route('', name: 'admin_access_urls_usergroups_data', methods: ['GET'])]
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

        $assignedIds = $this->getAssignedGroupIds($accessUrlId);

        $groups = $this->em->createQueryBuilder()
            ->select('g.id AS id, g.title AS title')
            ->from(Usergroup::class, 'g')
            ->orderBy('g.title', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $assigned = [];
        $available = [];
        foreach ($groups as $g) {
            $id = (int) $g['id'];
            $item = ['id' => $id, 'title' => $g['title']];
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

    #[Route('', name: 'admin_access_urls_usergroups_assign', methods: ['POST'])]
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

        $groupIds = array_map('intval', $data['group_ids'] ?? []);

        UrlManager::update_urls_rel_usergroup($groupIds, $accessUrlId);

        return $this->json(['success' => true]);
    }

    #[Route('/bulk', name: 'admin_access_urls_usergroups_bulk', methods: ['POST'])]
    public function bulk(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $groupIds = array_map('intval', $data['group_ids'] ?? []);
        $urlIds = array_map('intval', $data['url_ids'] ?? []);
        $action = (string) ($data['action'] ?? '');

        if ([] === $groupIds || [] === $urlIds || !\in_array($action, ['add', 'remove'], true)) {
            return $this->json(['error' => 'You must select at least one group and one URL'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $currentUser = $this->currentUser();
        foreach ($urlIds as $urlId) {
            if (!$this->accessUrlScope->isUrlManaged($currentUser, $urlId)) {
                return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
            }
        }

        if ('add' === $action) {
            UrlManager::addUserGroupListToUrl($groupIds, $urlIds);
        } else {
            UrlManager::removeUserGroupListFromUrl($groupIds, $urlIds);
        }

        return $this->json(['success' => true]);
    }

    /**
     * @return int[]
     */
    private function getAssignedGroupIds(int $accessUrlId): array
    {
        if ($accessUrlId <= 0) {
            return [];
        }

        $rows = $this->em->createQueryBuilder()
            ->select('IDENTITY(r.userGroup) AS groupId')
            ->from(AccessUrlRelUserGroup::class, 'r')
            ->where('r.url = :urlId')
            ->setParameter('urlId', $accessUrlId)
            ->getQuery()
            ->getArrayResult()
        ;

        return array_map(static fn (array $row): int => (int) $row['groupId'], $rows);
    }
}

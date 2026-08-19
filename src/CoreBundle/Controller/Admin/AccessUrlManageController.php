<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AccessUrlHierarchyHelper;
use Chamilo\CoreBundle\Helpers\AccessUrlScopeHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use DateTime;
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
 * CRUD data source for the "Access URLs" admin page, replacing the legacy
 * access_urls.php / access_url_edit.php pair.
 *
 * CSRF is handled globally by CsrfProtectionListener (stateless, origin-based)
 * for every state-changing request the router resolves — no per-endpoint
 * token is generated or checked here.
 *
 * A ROLE_GLOBAL_ADMIN registered in the topmost URL of a tree is unrestricted; one registered
 * only in a non-root URL is scoped to that URL's subtree for read access only — see
 * AccessUrlScopeHelper. Creating, editing, locking/unlocking and deleting a URL (i.e. the
 * AccessUrl entity's own CRUD, as opposed to what is assigned to it) are reserved to
 * unrestricted admins specifically: a subtree admin may not do any of these, even for a URL
 * within their own subtree. Same for registerAdmin() (self-registration into every URL).
 */
#[IsGranted('ROLE_GLOBAL_ADMIN')]
#[Route('/admin/access-urls-manage-data')]
class AccessUrlManageController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AccessUrlRepository $accessUrlRepository,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly AccessUrlScopeHelper $accessUrlScope,
        private readonly AccessUrlHierarchyHelper $accessUrlHierarchy,
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

    #[Route('', name: 'admin_access_urls_manage_data', methods: ['GET'])]
    public function list(): JsonResponse
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        $currentUser = $this->currentUser();
        $managedUrlIds = $this->accessUrlScope->getManagedUrlIds($currentUser);

        $urlQb = $this->em->createQueryBuilder()
            ->select('a')
            ->from(AccessUrl::class, 'a')
        ;
        if (null !== $managedUrlIds) {
            $urlQb->andWhere('a.id IN (:managedUrlIds)')->setParameter('managedUrlIds', $managedUrlIds);
        }

        /** @var AccessUrl[] $urls */
        $urls = $urlQb->getQuery()->getResult();
        // Display order: a parent immediately followed by its own children, recursively,
        // siblings alphabetical -- so the Vue table can show the hierarchy via indentation
        // alone (see AccessUrlHierarchyHelper).
        $orderedUrls = $this->accessUrlHierarchy->order($urls);

        $currentUserId = (int) $currentUser->getId();

        $registeredUrlIds = [];
        if ($currentUserId > 0) {
            $rows = $this->em->createQueryBuilder()
                ->select('IDENTITY(r.url) AS urlId')
                ->from(AccessUrlRelUser::class, 'r')
                ->where('r.user = :userId')
                ->setParameter('userId', $currentUserId)
                ->getQuery()
                ->getArrayResult()
            ;
            $registeredUrlIds = array_map(static fn (array $row): int => (int) $row['urlId'], $rows);
        }

        $items = [];
        $myMissingUrls = [];
        foreach ($orderedUrls as $entry) {
            $url = $entry['url'];
            $id = (int) $url->getId();
            $items[] = [
                'id' => $id,
                'url' => $url->getUrl(),
                'description' => $url->getDescription() ?? '',
                'active' => 1 === $url->getActive(),
                'isLoginOnly' => $url->isLoginOnly(),
                'tms' => $url->getTms()?->format('Y-m-d H:i:s'),
                'isDefault' => 1 === $id,
                'parentId' => $url->getSuperior()?->getId(),
                'depth' => $entry['depth'],
            ];

            if (1 === $url->getActive() && !\in_array($id, $registeredUrlIds, true)) {
                $myMissingUrls[] = ['id' => $id, 'url' => $url->getUrl()];
            }
        }

        return $this->json([
            'items' => $items,
            'myMissingUrls' => $myMissingUrls,
            // Creating a URL and registering into every URL are reserved to unrestricted
            // admins; the Vue page uses this to hide those actions rather than only refuse
            // them on submit.
            'canManageAllUrls' => null === $managedUrlIds,
        ]);
    }

    #[Route('', name: 'admin_access_urls_manage_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        if (!$this->accessUrlScope->isUnrestricted($this->currentUser())) {
            throw $this->createAccessDeniedException('Only an unrestricted global admin may create a new access URL.');
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $url = $this->normalizeUrl((string) ($data['url'] ?? ''));
        if (null === $url) {
            return $this->json(['error' => 'Please provide a valid http(s) URL.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($this->accessUrlRepository->exists($url)) {
            return $this->json(['error' => 'This URL already exists, please select another URL'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $parentAccessUrl = null;
        $parentId = (int) ($data['parentId'] ?? 0);
        if ($parentId > 0) {
            $parentAccessUrl = $this->em->find(AccessUrl::class, $parentId);
            if (null === $parentAccessUrl) {
                return $this->json(['error' => 'The selected parent URL does not exist.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $isLoginOnly = (bool) ($data['isLoginOnly'] ?? false);
        $warning = null;
        if ($isLoginOnly) {
            $sameDomain = $this->accessUrlHelper->isSameBaseDomain(
                array_merge($this->accessUrlRepository->getUrlList(), [$url])
            );
            if (!$sameDomain) {
                $warning = 'To use the central login page feature, all URLs defined MUST use the same (root) '
                    .'domain name in order to limit security risks linked to sharing access tokens between URLs. '
                    .'URLs using a different domain name might not be taken into account for access sharing.';
            }
        }

        $accessUrl = new AccessUrl();
        $accessUrl
            ->setUrl($url)
            ->setDescription((string) ($data['description'] ?? ''))
            ->setActive((bool) ($data['active'] ?? false) ? 1 : 0)
            ->setCreatedBy((int) $this->userHelper->getCurrent()?->getId())
            ->setIsLoginOnly($isLoginOnly)
        ;

        // AccessUrlListener::prePersist() defaults a URL's parent to the login-only URL (if
        // any) or the first URL, but only when no parent has been set yet — so an explicit
        // choice here is respected, and omitting it keeps today's default behavior.
        if (null !== $parentAccessUrl) {
            $accessUrl->setSuperior($parentAccessUrl)->setParentResourceNode($parentAccessUrl->resourceNode->getId());
        }

        $this->em->persist($accessUrl);
        $this->em->flush();

        return $this->json(['id' => $accessUrl->getId(), 'warning' => $warning], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'admin_access_urls_manage_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        if (!$this->accessUrlScope->isUnrestricted($this->currentUser())) {
            throw $this->createAccessDeniedException('Only an unrestricted global admin may edit an access URL.');
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $accessUrl = $this->em->find(AccessUrl::class, $id);
        if (null === $accessUrl) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $url = $this->normalizeUrl((string) ($data['url'] ?? ''));
        if (null === $url) {
            return $this->json(['error' => 'Please provide a valid http(s) URL.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // The default (id=1) URL can never be deactivated.
        $active = 1 === $id ? 1 : ((bool) ($data['active'] ?? false) ? 1 : 0);

        if (\array_key_exists('parentId', $data) && (int) $data['parentId'] > 0) {
            $parentId = (int) $data['parentId'];
            $isOwnDescendant = \in_array($parentId, $this->accessUrlScope->getDescendantUrlIds($id), true);
            if ($isOwnDescendant) {
                return $this->json(['error' => 'The selected parent URL is not valid.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $parentAccessUrl = $this->em->find(AccessUrl::class, $parentId);
            if (null === $parentAccessUrl) {
                return $this->json(['error' => 'The selected parent URL does not exist.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $accessUrl->setSuperior($parentAccessUrl)->setParentResourceNode($parentAccessUrl->resourceNode->getId());
        }

        $accessUrl
            ->setUrl($url)
            ->setDescription((string) ($data['description'] ?? ''))
            ->setActive($active)
            ->setCreatedBy((int) $this->userHelper->getCurrent()?->getId())
            ->setTms(new DateTime())
            ->setIsLoginOnly((bool) ($data['isLoginOnly'] ?? false))
        ;

        $this->em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/{id}/lock', name: 'admin_access_urls_manage_lock', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function lock(int $id): JsonResponse
    {
        return $this->setStatus($id, false);
    }

    #[Route('/{id}/unlock', name: 'admin_access_urls_manage_unlock', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function unlock(int $id): JsonResponse
    {
        return $this->setStatus($id, true);
    }

    #[Route('/register-admin', name: 'admin_access_urls_manage_register_admin', methods: ['POST'])]
    public function registerAdmin(): JsonResponse
    {
        $currentUser = $this->currentUser();
        $currentUserId = (int) $currentUser->getId();
        if ($currentUserId <= 0) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->accessUrlScope->isUnrestricted($currentUser)) {
            throw $this->createAccessDeniedException('Only an unrestricted global admin may register into every access URL.');
        }

        foreach ($this->accessUrlRepository->findAll() as $url) {
            if (1 !== $url->getActive()) {
                continue;
            }

            $urlId = (int) $url->getId();
            $exists = $this->em->createQueryBuilder()
                ->select('COUNT(r.id)')
                ->from(AccessUrlRelUser::class, 'r')
                ->where('r.user = :userId')
                ->andWhere('r.url = :urlId')
                ->setParameter('userId', $currentUserId)
                ->setParameter('urlId', $urlId)
                ->getQuery()
                ->getSingleScalarResult()
            ;

            if (0 === (int) $exists) {
                UrlManager::add_user_to_url($currentUserId, $urlId);
            }
        }

        return $this->json(['success' => true]);
    }

    private function setStatus(int $id, bool $active): JsonResponse
    {
        if (1 === $id) {
            return $this->json(['error' => 'The default URL cannot be disabled.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$this->accessUrlScope->isUnrestricted($this->currentUser())) {
            throw $this->createAccessDeniedException('Only an unrestricted global admin may lock or unlock an access URL.');
        }

        $accessUrl = $this->em->find(AccessUrl::class, $id);
        if (null === $accessUrl) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $accessUrl->setActive($active ? 1 : 0);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    private function normalizeUrl(string $url): ?string
    {
        $url = trim($url);
        if ('' === $url || !preg_match('#^https?://#i', $url)) {
            return null;
        }

        if (!str_ends_with($url, '/')) {
            $url .= '/';
        }

        return $url;
    }
}

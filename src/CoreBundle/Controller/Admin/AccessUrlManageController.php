<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelUser;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use DateTime;
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
 * CRUD data source for the "Access URLs" admin page, replacing the legacy
 * access_urls.php / access_url_edit.php pair.
 */
#[IsGranted('ROLE_GLOBAL_ADMIN')]
#[Route('/admin/access-urls-manage-data')]
class AccessUrlManageController extends AbstractController
{
    private const string CSRF_INTENT = 'access_url_manage';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AccessUrlRepository $accessUrlRepository,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly UserHelper $userHelper,
    ) {}

    #[Route('', name: 'admin_access_urls_manage_data', methods: ['GET'])]
    public function list(): JsonResponse
    {
        // The CSRF token must be minted (and written to the session) before the
        // session is closed below — closing it first would return a token that
        // was never actually persisted, so every subsequent write action using
        // it would fail CSRF validation.
        $csrfToken = $this->csrfTokenManager->getToken(self::CSRF_INTENT)->getValue();

        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        $urls = $this->accessUrlRepository->findAll();
        $currentUserId = (int) $this->userHelper->getCurrent()?->getId();

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
        foreach ($urls as $url) {
            $id = (int) $url->getId();
            $items[] = [
                'id' => $id,
                'url' => $url->getUrl(),
                'description' => $url->getDescription() ?? '',
                'active' => 1 === $url->getActive(),
                'isLoginOnly' => $url->isLoginOnly(),
                'tms' => $url->getTms()?->format('Y-m-d H:i:s'),
                'isDefault' => 1 === $id,
            ];

            if (1 === $url->getActive() && !\in_array($id, $registeredUrlIds, true)) {
                $myMissingUrls[] = ['id' => $id, 'url' => $url->getUrl()];
            }
        }

        return $this->json([
            'items' => $items,
            'myMissingUrls' => $myMissingUrls,
            'csrfToken' => $csrfToken,
        ]);
    }

    #[Route('', name: 'admin_access_urls_manage_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        if (!$this->isCsrfTokenValid(self::CSRF_INTENT, (string) ($data['_token'] ?? ''))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $url = $this->normalizeUrl((string) ($data['url'] ?? ''));
        if (null === $url) {
            return $this->json(['error' => 'Please provide a valid http(s) URL.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($this->accessUrlRepository->exists($url)) {
            return $this->json(['error' => 'This URL already exists, please select another URL'], Response::HTTP_UNPROCESSABLE_ENTITY);
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

        $this->em->persist($accessUrl);
        $this->em->flush();

        return $this->json(['id' => $accessUrl->getId(), 'warning' => $warning], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'admin_access_urls_manage_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        if (!$this->isCsrfTokenValid(self::CSRF_INTENT, (string) ($data['_token'] ?? ''))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

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
    public function lock(int $id, Request $request): JsonResponse
    {
        return $this->setStatus($id, $request, false);
    }

    #[Route('/{id}/unlock', name: 'admin_access_urls_manage_unlock', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function unlock(int $id, Request $request): JsonResponse
    {
        return $this->setStatus($id, $request, true);
    }

    #[Route('/register-admin', name: 'admin_access_urls_manage_register_admin', methods: ['POST'])]
    public function registerAdmin(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        if (!$this->isCsrfTokenValid(self::CSRF_INTENT, (string) ($data['_token'] ?? ''))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $currentUserId = (int) $this->userHelper->getCurrent()?->getId();
        if ($currentUserId <= 0) {
            throw $this->createAccessDeniedException();
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

    private function setStatus(int $id, Request $request, bool $active): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        if (!$this->isCsrfTokenValid(self::CSRF_INTENT, (string) ($data['_token'] ?? ''))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if (1 === $id) {
            return $this->json(['error' => 'The default URL cannot be disabled.'], Response::HTTP_UNPROCESSABLE_ENTITY);
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

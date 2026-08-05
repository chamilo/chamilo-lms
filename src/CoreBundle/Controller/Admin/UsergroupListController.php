<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Entity\UsergroupRelCourse;
use Chamilo\CoreBundle\Entity\UsergroupRelSession;
use Chamilo\CoreBundle\Entity\UsergroupRelUser;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/usergroups-data')]
class UsergroupListController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly IllustrationRepository $illustrationRepository,
    ) {}

    #[Route('', name: 'admin_usergroups_data', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', '1'));
        $limit = max(1, min(100, (int) $request->query->get('limit', '20')));
        $search = trim((string) $request->query->get('search', ''));
        $offset = ($page - 1) * $limit;

        $qb = $this->em->createQueryBuilder()
            ->select('ug')
            ->from(Usergroup::class, 'ug')
        ;

        $this->applyAccessUrlFilter($qb);

        if ('' !== $search) {
            $qb->andWhere('ug.title LIKE :search')
                ->setParameter('search', '%'.$search.'%')
            ;
        }

        $total = (int) (clone $qb)
            ->select('COUNT(DISTINCT ug.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        /** @var Usergroup[] $usergroups */
        $usergroups = $qb
            ->orderBy('ug.title', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        $ids = array_map(static fn (Usergroup $ug): int => (int) $ug->getId(), $usergroups);

        $userCounts = [];
        $courseCounts = [];
        $sessionCounts = [];

        if (!empty($ids)) {
            $userCountRows = $this->em->createQueryBuilder()
                ->select('IDENTITY(ru.usergroup) AS ugId, COUNT(ru.id) AS cnt')
                ->from(UsergroupRelUser::class, 'ru')
                ->where('ru.usergroup IN (:ids)')
                ->setParameter('ids', $ids)
                ->groupBy('ru.usergroup')
                ->getQuery()
                ->getArrayResult()
            ;
            foreach ($userCountRows as $row) {
                $userCounts[(int) $row['ugId']] = (int) $row['cnt'];
            }

            $courseCountRows = $this->em->createQueryBuilder()
                ->select('IDENTITY(rc.usergroup) AS ugId, COUNT(rc.id) AS cnt')
                ->from(UsergroupRelCourse::class, 'rc')
                ->where('rc.usergroup IN (:ids)')
                ->setParameter('ids', $ids)
                ->groupBy('rc.usergroup')
                ->getQuery()
                ->getArrayResult()
            ;
            foreach ($courseCountRows as $row) {
                $courseCounts[(int) $row['ugId']] = (int) $row['cnt'];
            }

            $sessionCountRows = $this->em->createQueryBuilder()
                ->select('IDENTITY(rs.usergroup) AS ugId, COUNT(rs.id) AS cnt')
                ->from(UsergroupRelSession::class, 'rs')
                ->where('rs.usergroup IN (:ids)')
                ->setParameter('ids', $ids)
                ->groupBy('rs.usergroup')
                ->getQuery()
                ->getArrayResult()
            ;
            foreach ($sessionCountRows as $row) {
                $sessionCounts[(int) $row['ugId']] = (int) $row['cnt'];
            }
        }

        $items = [];
        foreach ($usergroups as $ug) {
            $id = (int) $ug->getId();
            $items[] = [
                'id' => $id,
                'title' => $ug->getTitle(),
                'userCount' => $userCounts[$id] ?? 0,
                'courseCount' => $courseCounts[$id] ?? 0,
                'sessionCount' => $sessionCounts[$id] ?? 0,
                'groupType' => $ug->getGroupType(),
                'description' => $ug->getDescription(),
                'url' => $ug->getUrl(),
                'visibility' => $ug->getVisibility(),
                'allowMembersToLeaveGroup' => $ug->getAllowMembersToLeaveGroup(),
                'pictureUrl' => $this->illustrationRepository->getIllustrationUrl($ug),
            ];
        }

        return $this->json([
            'items' => $items,
            'totalItems' => $total,
            'csrfToken' => $this->csrfTokenManager->getToken('usergroup_list')->getValue(),
            'importCsrfToken' => $this->csrfTokenManager->getToken('usergroup_import')->getValue(),
        ]);
    }

    #[Route('/{id}/preview', name: 'admin_usergroup_preview_data', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function preview(int $id): JsonResponse
    {
        $usergroup = $this->em->find(Usergroup::class, $id);
        if (null === $usergroup) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->belongsToCurrentUrl($usergroup)) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        /** @var list<array<string, mixed>> $userRows */
        $userRows = $this->em->createQueryBuilder()
            ->select(
                'u.id',
                'u.firstname',
                'u.lastname',
                'u.username',
                'u.email',
                'ru.relationType'
            )
            ->from(UsergroupRelUser::class, 'ru')
            ->join('ru.user', 'u')
            ->where('ru.usergroup = :usergroupId')
            ->setParameter('usergroupId', $id, Types::INTEGER)
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        /** @var list<array<string, mixed>> $courseRows */
        $courseRows = $this->em->createQueryBuilder()
            ->select('c.id', 'c.title', 'c.code', 'c.visualCode')
            ->from(UsergroupRelCourse::class, 'rc')
            ->join('rc.course', 'c')
            ->where('rc.usergroup = :usergroupId')
            ->setParameter('usergroupId', $id, Types::INTEGER)
            ->orderBy('c.title', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $users = array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'firstname' => (string) ($row['firstname'] ?? ''),
                'lastname' => (string) ($row['lastname'] ?? ''),
                'username' => (string) ($row['username'] ?? ''),
                'email' => (string) ($row['email'] ?? ''),
                'relationType' => (int) ($row['relationType'] ?? 0),
            ],
            $userRows
        );

        $courses = array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'title' => (string) ($row['title'] ?? ''),
                'code' => (string) ($row['code'] ?? ''),
                'visualCode' => (string) ($row['visualCode'] ?? ''),
            ],
            $courseRows
        );

        return $this->json([
            'group' => [
                'id' => (int) $usergroup->getId(),
                'title' => $usergroup->getTitle(),
                'description' => $usergroup->getDescription(),
                'groupType' => $usergroup->getGroupType(),
            ],
            'users' => $users,
            'courses' => $courses,
        ]);
    }

    #[Route('/export', name: 'admin_usergroups_export', methods: ['GET'])]
    public function export(): StreamedResponse
    {
        $qb = $this->em->createQueryBuilder()
            ->select('ug')
            ->from(Usergroup::class, 'ug')
            ->orderBy('ug.title', 'ASC')
        ;

        $this->applyAccessUrlFilter($qb);

        /** @var Usergroup[] $usergroups */
        $usergroups = $qb->getQuery()->getResult();

        $response = new StreamedResponse(static function () use ($usergroups): void {
            $handle = fopen('php://output', 'w');
            if (false === $handle) {
                return;
            }

            fputcsv($handle, ['title', 'description', 'url', 'visibility', 'groupType']);

            foreach ($usergroups as $ug) {
                fputcsv($handle, [
                    $ug->getTitle(),
                    $ug->getDescription(),
                    $ug->getUrl(),
                    $ug->getVisibility(),
                    $ug->getGroupType(),
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="classes.csv"');

        return $response;
    }

    #[Route('', name: 'admin_usergroups_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('usergroup_list', $token)) {
            return $this->json(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $title = trim((string) $request->request->get('title', ''));
        if ('' === $title) {
            return $this->json(['error' => 'Title is required'], Response::HTTP_BAD_REQUEST);
        }

        $existing = $this->em->getRepository(Usergroup::class)->findOneBy(['title' => $title]);
        if (null !== $existing) {
            return $this->json(['error' => 'Already exists'], Response::HTTP_CONFLICT);
        }

        $ug = new Usergroup();
        $ug->setTitle($title)
            ->setDescription((string) $request->request->get('description', ''))
            ->setGroupType($this->sanitizedGroupType($request, 'groupType'))
            ->setUrl((string) $request->request->get('url', ''))
            ->setVisibility($this->sanitizedVisibility($request, 'visibility'))
            ->setAllowMembersToLeaveGroup((int) (bool) $request->request->get('allowMembersToLeaveGroup', 0))
        ;

        $this->em->persist($ug);
        $this->em->flush();

        if ($this->accessUrlHelper->isMultiple()) {
            $accessUrl = $this->accessUrlHelper->getCurrent();
            if (null !== $accessUrl) {
                $ug->addAccessUrl($accessUrl);
                $this->em->flush();
            }
        }

        $picture = $request->files->get('picture');
        $currentUser = $this->getUser();
        if (null !== $picture && $currentUser instanceof User) {
            $this->illustrationRepository->addIllustration($ug, $currentUser, $picture);
        }

        return $this->json(['success' => true, 'id' => $ug->getId()], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'admin_usergroups_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('usergroup_list', $token)) {
            return $this->json(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $ug = $this->em->find(Usergroup::class, $id);
        if (null === $ug) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->belongsToCurrentUrl($ug)) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $title = trim((string) $request->request->get('title', ''));
        if ('' === $title) {
            return $this->json(['error' => 'Title is required'], Response::HTTP_BAD_REQUEST);
        }

        $existing = $this->em->getRepository(Usergroup::class)->findOneBy(['title' => $title]);
        if (null !== $existing && $existing->getId() !== $id) {
            return $this->json(['error' => 'Already exists'], Response::HTTP_CONFLICT);
        }

        $ug->setTitle($title)
            ->setDescription((string) $request->request->get('description', ''))
            ->setGroupType($this->sanitizedGroupType($request, 'groupType'))
            ->setUrl((string) $request->request->get('url', ''))
            ->setVisibility($this->sanitizedVisibility($request, 'visibility'))
            ->setAllowMembersToLeaveGroup((int) (bool) $request->request->get('allowMembersToLeaveGroup', 0))
        ;

        $this->em->flush();

        $picture = $request->files->get('picture');
        $currentUser = $this->getUser();
        if (null !== $picture && $currentUser instanceof User) {
            $this->illustrationRepository->deleteIllustration($ug);
            $this->illustrationRepository->addIllustration($ug, $currentUser, $picture);
        } elseif ((bool) $request->request->get('deletePicture', false)) {
            $this->illustrationRepository->deleteIllustration($ug);
        }

        return $this->json(['success' => true]);
    }

    #[Route('/{id}', name: 'admin_usergroups_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Request $request, int $id): JsonResponse
    {
        $token = (string) $request->headers->get('X-CSRF-Token', '');
        if (!$this->isCsrfTokenValid('usergroup_list', $token)) {
            return $this->json(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $ug = $this->em->find(Usergroup::class, $id);
        if (null === $ug) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->belongsToCurrentUrl($ug)) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($ug);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    private function applyAccessUrlFilter(QueryBuilder $qb): void
    {
        if (!$this->accessUrlHelper->isMultiple()) {
            return;
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $accessUrl) {
            return;
        }

        $qb->innerJoin('ug.urls', 'urlRel')
            ->andWhere('urlRel.url = :accessUrlId')
            ->setParameter('accessUrlId', $accessUrl->getId(), Types::INTEGER)
        ;
    }

    private function belongsToCurrentUrl(Usergroup $ug): bool
    {
        if (!$this->accessUrlHelper->isMultiple()) {
            return true;
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $accessUrl) {
            return true;
        }

        $urlId = $accessUrl->getId();
        foreach ($ug->getUrls() as $urlRel) {
            $relUrl = $urlRel->getUrl();
            if (null !== $relUrl && $relUrl->getId() === $urlId) {
                return true;
            }
        }

        return false;
    }

    private function sanitizedGroupType(Request $request, string $key): int
    {
        $value = (int) $request->request->get($key, Usergroup::NORMAL_CLASS);

        return \in_array($value, [Usergroup::NORMAL_CLASS, Usergroup::SOCIAL_CLASS], true) ? $value : Usergroup::NORMAL_CLASS;
    }

    private function sanitizedVisibility(Request $request, string $key): string
    {
        $value = (string) $request->request->get($key, (string) Usergroup::GROUP_PERMISSION_OPEN);

        return \in_array($value, ['1', '2'], true) ? $value : (string) Usergroup::GROUP_PERMISSION_OPEN;
    }
}

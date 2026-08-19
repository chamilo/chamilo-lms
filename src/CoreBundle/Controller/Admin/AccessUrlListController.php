<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelCourse;
use Chamilo\CoreBundle\Entity\AccessUrlRelSession;
use Chamilo\CoreBundle\Entity\AccessUrlRelUser;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UsergroupRelUser;
use Chamilo\CoreBundle\Helpers\AccessUrlHierarchyHelper;
use Chamilo\CoreBundle\Helpers\AccessUrlScopeHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Service\Update\InstalledChamiloVersionProvider;
use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use const PHP_SESSION_ACTIVE;
use const PHP_VERSION;

/**
 * Read-only data source for the "Multi URLs" admin dashboard (see issue #8903).
 *
 * Lists every AccessUrl with its user/course/session counts and assigned admins,
 * plus install-wide totals. Global-admin only: this is a superset of the
 * per-URL data any single-URL admin can already see.
 *
 * A ROLE_GLOBAL_ADMIN registered in the topmost URL of a tree is unrestricted (sees
 * everything below, unchanged). One registered only in a non-root URL is scoped to that
 * URL's subtree: every query below adds its managed-URL filter only when
 * AccessUrlScopeHelper::getManagedUrlIds() returns a non-null list.
 */
#[IsGranted('ROLE_GLOBAL_ADMIN')]
#[Route('/admin/urls-data')]
class AccessUrlListController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly InstalledChamiloVersionProvider $versionProvider,
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

    #[Route('', name: 'admin_urls_data', methods: ['GET'])]
    public function list(): JsonResponse
    {
        // Diagnostics are read-only; release the session lock early for concurrent admin tabs.
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        $managedUrlIds = $this->accessUrlScope->getManagedUrlIds($this->currentUser());

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
        $ids = array_map(static fn (AccessUrl $url): int => (int) $url->getId(), $urls);

        $userCounts = [];
        $courseCounts = [];
        $sessionCounts = [];
        $adminsByUrl = [];

        if (!empty($ids)) {
            // Counts only consider active, non-anonymous users: access_url_rel_user rows
            // survive soft deletion (User::SOFT_DELETED) and the anonymous user is also
            // subscribed to a URL, so an unfiltered COUNT would inflate the numbers.
            $userCountRows = $this->em->createQueryBuilder()
                ->select('IDENTITY(r.url) AS urlId, COUNT(r.id) AS cnt')
                ->from(AccessUrlRelUser::class, 'r')
                ->innerJoin('r.user', 'u')
                ->where('r.url IN (:ids)')
                ->andWhere('u.active <> :softDeleted')
                ->andWhere('u.status <> :anonymous')
                ->setParameter('ids', $ids)
                ->setParameter('softDeleted', User::SOFT_DELETED)
                ->setParameter('anonymous', User::ANONYMOUS)
                ->groupBy('r.url')
                ->getQuery()
                ->getArrayResult()
            ;
            foreach ($userCountRows as $row) {
                $userCounts[(int) $row['urlId']] = (int) $row['cnt'];
            }

            $courseCountRows = $this->em->createQueryBuilder()
                ->select('IDENTITY(r.url) AS urlId, COUNT(r.id) AS cnt')
                ->from(AccessUrlRelCourse::class, 'r')
                ->where('r.url IN (:ids)')
                ->setParameter('ids', $ids)
                ->groupBy('r.url')
                ->getQuery()
                ->getArrayResult()
            ;
            foreach ($courseCountRows as $row) {
                $courseCounts[(int) $row['urlId']] = (int) $row['cnt'];
            }

            $sessionCountRows = $this->em->createQueryBuilder()
                ->select('IDENTITY(r.url) AS urlId, COUNT(r.id) AS cnt')
                ->from(AccessUrlRelSession::class, 'r')
                ->where('r.url IN (:ids)')
                ->setParameter('ids', $ids)
                ->groupBy('r.url')
                ->getQuery()
                ->getArrayResult()
            ;
            foreach ($sessionCountRows as $row) {
                $sessionCounts[(int) $row['urlId']] = (int) $row['cnt'];
            }

            // Admins per URL in a single batched query (not a loop over
            // UserRepository::findByRoleList(), which takes one URL id at a time and
            // hydrates full User entities). The LIKE idiom mirrors
            // UserRepository::addRoleListQueryBuilder() since "roles" is a JSON column.
            $adminRows = $this->em->createQueryBuilder()
                ->select('IDENTITY(r.url) AS urlId, u.id AS id, u.firstname AS firstname, u.lastname AS lastname, u.username AS username')
                ->from(AccessUrlRelUser::class, 'r')
                ->innerJoin('r.user', 'u')
                ->where('r.url IN (:ids)')
                ->andWhere('u.active = 1')
                ->andWhere('u.status <> :anonymous')
                ->andWhere('u.roles LIKE :roleAdmin OR u.roles LIKE :roleGlobalAdmin')
                ->setParameter('ids', $ids)
                ->setParameter('anonymous', User::ANONYMOUS)
                ->setParameter('roleAdmin', '%"ROLE_ADMIN"%')
                ->setParameter('roleGlobalAdmin', '%"ROLE_GLOBAL_ADMIN"%')
                ->orderBy('u.lastname', 'ASC')
                ->addOrderBy('u.firstname', 'ASC')
                ->getQuery()
                ->getArrayResult()
            ;
            foreach ($adminRows as $row) {
                $adminsByUrl[(int) $row['urlId']][] = [
                    'id' => (int) $row['id'],
                    'firstname' => $row['firstname'],
                    'lastname' => $row['lastname'],
                    'username' => $row['username'],
                ];
            }
        }

        $items = [];
        foreach ($orderedUrls as $entry) {
            $url = $entry['url'];
            $id = (int) $url->getId();
            $items[] = [
                'id' => $id,
                'url' => $url->getUrl(),
                'description' => $url->getDescription() ?? '',
                'active' => 1 === $url->getActive(),
                'userCount' => $userCounts[$id] ?? 0,
                'courseCount' => $courseCounts[$id] ?? 0,
                'sessionCount' => $sessionCounts[$id] ?? 0,
                'admins' => $adminsByUrl[$id] ?? [],
                'depth' => $entry['depth'],
            ];
        }

        // Unrestricted admins keep the raw install-wide totals. A scoped admin instead gets
        // "reachable through a managed URL" totals, via a join on the corresponding rel table.
        $totalUsersQb = $this->em->createQueryBuilder()
            ->select(null === $managedUrlIds ? 'COUNT(u.id)' : 'COUNT(DISTINCT u.id)')
            ->from(User::class, 'u')
            ->where('u.active <> :softDeleted')
            ->andWhere('u.status <> :anonymous')
            ->setParameter('softDeleted', User::SOFT_DELETED)
            ->setParameter('anonymous', User::ANONYMOUS)
        ;
        $totalCoursesQb = $this->em->createQueryBuilder()
            ->select(null === $managedUrlIds ? 'COUNT(c.id)' : 'COUNT(DISTINCT c.id)')
            ->from(Course::class, 'c')
        ;
        $totalSessionsQb = $this->em->createQueryBuilder()
            ->select(null === $managedUrlIds ? 'COUNT(s.id)' : 'COUNT(DISTINCT s.id)')
            ->from(Session::class, 's')
        ;
        if (null !== $managedUrlIds) {
            $totalUsersQb->innerJoin(AccessUrlRelUser::class, 'scopeRelUser', 'WITH', 'scopeRelUser.user = u.id')
                ->andWhere('scopeRelUser.url IN (:managedUrlIds)')
                ->setParameter('managedUrlIds', $managedUrlIds)
            ;
            $totalCoursesQb->innerJoin(AccessUrlRelCourse::class, 'scopeRelCourse', 'WITH', 'scopeRelCourse.course = c.id')
                ->where('scopeRelCourse.url IN (:managedUrlIds)')
                ->setParameter('managedUrlIds', $managedUrlIds)
            ;
            $totalSessionsQb->innerJoin(AccessUrlRelSession::class, 'scopeRelSession', 'WITH', 'scopeRelSession.session = s.id')
                ->where('scopeRelSession.url IN (:managedUrlIds)')
                ->setParameter('managedUrlIds', $managedUrlIds)
            ;
        }

        $totalUsers = (int) $totalUsersQb->getQuery()->getSingleScalarResult();
        $totalCourses = (int) $totalCoursesQb->getQuery()->getSingleScalarResult();
        $totalSessions = (int) $totalSessionsQb->getQuery()->getSingleScalarResult();

        return $this->json([
            'system' => [
                'chamiloVersion' => $this->versionProvider->getInstalledVersion(),
                'phpVersion' => PHP_VERSION,
                'totalUsers' => $totalUsers,
                'totalCourses' => $totalCourses,
                'totalSessions' => $totalSessions,
            ],
            'items' => $items,
            'totalItems' => \count($items),
        ]);
    }

    /**
     * Aggregated user directory with URL attribution (issue #8903). Only the
     * URL membership itself is shown — per-user metrics like login frequency
     * or time spent are not stored per URL anywhere in the codebase, so they
     * are intentionally not part of this endpoint.
     */
    #[Route('/users', name: 'admin_urls_users_data', methods: ['GET'])]
    public function users(Request $request): JsonResponse
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        $managedUrlIds = $this->accessUrlScope->getManagedUrlIds($this->currentUser());

        $page = max(1, (int) $request->query->get('page', '1'));
        $limit = max(1, min(100, (int) $request->query->get('limit', '20')));
        $search = trim((string) $request->query->get('search', ''));
        $offset = ($page - 1) * $limit;

        $qb = $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.active <> :softDeleted')
            ->andWhere('u.status <> :anonymous')
            ->setParameter('softDeleted', User::SOFT_DELETED)
            ->setParameter('anonymous', User::ANONYMOUS)
        ;

        // A scalar subquery, not a join: joining here would multiply rows before the
        // setFirstResult()/setMaxResults() pagination below is applied at the SQL level.
        if (null !== $managedUrlIds) {
            $qb->andWhere(
                'u.id IN (SELECT IDENTITY(scopeRel.user) FROM '.AccessUrlRelUser::class.' scopeRel '
                .'WHERE scopeRel.url IN (:managedUrlIds))'
            )->setParameter('managedUrlIds', $managedUrlIds);
        }

        if ('' !== $search) {
            $qb->andWhere('u.firstname LIKE :search OR u.lastname LIKE :search OR u.username LIKE :search')
                ->setParameter('search', '%'.$search.'%')
            ;
        }

        $total = (int) (clone $qb)
            ->select('COUNT(DISTINCT u.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        /** @var User[] $users */
        $users = $qb
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        $ids = array_map(static fn (User $u): int => (int) $u->getId(), $users);

        $urlsByUser = [];
        $usergroupsByUser = [];
        $creatorNameById = [];
        if (!empty($ids)) {
            $urlRowsQb = $this->em->createQueryBuilder()
                ->select('IDENTITY(r.user) AS userId, a.id AS urlId, a.url AS url')
                ->from(AccessUrlRelUser::class, 'r')
                ->innerJoin('r.url', 'a')
                ->where('r.user IN (:ids)')
                ->setParameter('ids', $ids)
            ;
            if (null !== $managedUrlIds) {
                $urlRowsQb->andWhere('r.url IN (:managedUrlIds)')->setParameter('managedUrlIds', $managedUrlIds);
            }
            $rows = $urlRowsQb->getQuery()->getArrayResult();
            foreach ($rows as $row) {
                $urlsByUser[(int) $row['userId']][] = [
                    'id' => (int) $row['urlId'],
                    'url' => $row['url'],
                ];
            }

            $groupRows = $this->em->createQueryBuilder()
                ->select('IDENTITY(r.user) AS userId, g.title AS title')
                ->from(UsergroupRelUser::class, 'r')
                ->innerJoin('r.usergroup', 'g')
                ->where('r.user IN (:ids)')
                ->setParameter('ids', $ids)
                ->orderBy('g.title', 'ASC')
                ->getQuery()
                ->getArrayResult()
            ;
            foreach ($groupRows as $row) {
                $usergroupsByUser[(int) $row['userId']][] = $row['title'];
            }

            $creatorIds = array_values(array_unique(array_filter(
                array_map(static fn (User $u): int => (int) $u->getCreatorId(), $users)
            )));
            if (!empty($creatorIds)) {
                $creatorRows = $this->em->createQueryBuilder()
                    ->select('u.id AS id, u.firstname AS firstname, u.lastname AS lastname')
                    ->from(User::class, 'u')
                    ->where('u.id IN (:ids)')
                    ->setParameter('ids', $creatorIds)
                    ->getQuery()
                    ->getArrayResult()
                ;
                foreach ($creatorRows as $row) {
                    $creatorNameById[(int) $row['id']] = trim($row['firstname'].' '.$row['lastname']);
                }
            }
        }

        $items = [];
        foreach ($users as $u) {
            $id = (int) $u->getId();
            $creatorId = (int) $u->getCreatorId();
            $items[] = [
                'id' => $id,
                'firstname' => $u->getFirstname(),
                'lastname' => $u->getLastname(),
                'username' => $u->getUsername(),
                'email' => $u->getEmail(),
                'officialCode' => $u->getOfficialCode() ?? '',
                'registrationDate' => $u->getCreatedAt()->format('Y-m-d H:i:s'),
                'creatorName' => $creatorNameById[$creatorId] ?? '',
                'usergroups' => $usergroupsByUser[$id] ?? [],
                'urls' => $urlsByUser[$id] ?? [],
            ];
        }

        return $this->json([
            'items' => $items,
            'totalItems' => $total,
        ]);
    }

    /**
     * Consolidated course directory with URL distribution (issue #8903).
     * Comparative usage statistics are intentionally not included: course
     * access/activity tracking is not stored per URL anywhere in the
     * codebase, so there is nothing differentiable by URL to show there.
     */
    #[Route('/courses', name: 'admin_urls_courses_data', methods: ['GET'])]
    public function courses(Request $request): JsonResponse
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        $managedUrlIds = $this->accessUrlScope->getManagedUrlIds($this->currentUser());

        $page = max(1, (int) $request->query->get('page', '1'));
        $limit = max(1, min(100, (int) $request->query->get('limit', '20')));
        $search = trim((string) $request->query->get('search', ''));
        $offset = ($page - 1) * $limit;

        $qb = $this->em->createQueryBuilder()
            ->select('c')
            ->from(Course::class, 'c')
        ;

        // A scalar subquery, not a join: joining here would multiply rows before the
        // setFirstResult()/setMaxResults() pagination below is applied at the SQL level.
        if (null !== $managedUrlIds) {
            $qb->andWhere(
                'c.id IN (SELECT IDENTITY(scopeRel.course) FROM '.AccessUrlRelCourse::class.' scopeRel '
                .'WHERE scopeRel.url IN (:managedUrlIds))'
            )->setParameter('managedUrlIds', $managedUrlIds);
        }

        if ('' !== $search) {
            $qb->andWhere('c.title LIKE :search OR c.code LIKE :search')
                ->setParameter('search', '%'.$search.'%')
            ;
        }

        $total = (int) (clone $qb)
            ->select('COUNT(DISTINCT c.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        /** @var Course[] $courses */
        $courses = $qb
            ->orderBy('c.title', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        $ids = array_map(static fn (Course $c): int => (int) $c->getId(), $courses);

        $urlsByCourse = [];
        if (!empty($ids)) {
            $urlRowsQb = $this->em->createQueryBuilder()
                ->select('IDENTITY(r.course) AS courseId, a.id AS urlId, a.url AS url')
                ->from(AccessUrlRelCourse::class, 'r')
                ->innerJoin('r.url', 'a')
                ->where('r.course IN (:ids)')
                ->setParameter('ids', $ids)
            ;
            if (null !== $managedUrlIds) {
                $urlRowsQb->andWhere('r.url IN (:managedUrlIds)')->setParameter('managedUrlIds', $managedUrlIds);
            }
            $rows = $urlRowsQb->getQuery()->getArrayResult();
            foreach ($rows as $row) {
                $urlsByCourse[(int) $row['courseId']][] = [
                    'id' => (int) $row['urlId'],
                    'url' => $row['url'],
                ];
            }
        }

        $items = [];
        foreach ($courses as $c) {
            $id = (int) $c->getId();
            $items[] = [
                'id' => $id,
                'title' => $c->getTitle(),
                'code' => $c->getCode(),
                'urls' => $urlsByCourse[$id] ?? [],
            ];
        }

        return $this->json([
            'items' => $items,
            'totalItems' => $total,
        ]);
    }

    /**
     * Attributes a user's course/session enrollment rows to the URLs they
     * belong to (issue #8903 follow-up: per-user, per-URL detail page).
     * Whether the user is actually enrolled is already established by the
     * caller (the existing global reporting "learner-detail" section, whose
     * rows already carry a sessionId — 0 for a direct enrollment) — this only
     * answers where each row belongs.
     *
     * Critically, a row's URL(s) depend on HOW the user is enrolled, not
     * merely which URLs the course happens to be linked to: a course can be
     * directly linked to several URLs while this specific user only ever
     * took it through one session in one of them. So a direct row (sessionId
     * 0) is attributed via the course's own direct links
     * (access_url_rel_course), while a session row is attributed via that
     * session's own links (access_url_rel_session) — never both — otherwise
     * session-only progress would incorrectly appear duplicated under every
     * URL the course is merely linked to.
     */
    #[Route('/users/{id}/urls', name: 'admin_urls_user_urls_data', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function userUrls(int $id, Request $request): JsonResponse
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        $currentUser = $this->currentUser();
        if (!$this->accessUrlScope->isUserManaged($currentUser, $id)) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }
        $managedUrlIds = $this->accessUrlScope->getManagedUrlIds($currentUser);

        $pairs = [];
        foreach (explode(',', (string) $request->query->get('pairs', '')) as $token) {
            if ('' === $token || !str_contains($token, ':')) {
                continue;
            }
            [$courseId, $sessionId] = explode(':', $token, 2);
            $pairs[] = ['courseId' => (int) $courseId, 'sessionId' => (int) $sessionId];
        }

        $urlRowsQb = $this->em->createQueryBuilder()
            ->select('a.id AS id, a.url AS url')
            ->from(AccessUrlRelUser::class, 'r')
            ->innerJoin('r.url', 'a')
            ->where('r.user = :userId')
            ->setParameter('userId', $id)
        ;
        if (null !== $managedUrlIds) {
            $urlRowsQb->andWhere('r.url IN (:managedUrlIds)')->setParameter('managedUrlIds', $managedUrlIds);
        }
        $urlRows = $urlRowsQb->getQuery()->getArrayResult();

        $directCourseIds = array_values(array_unique(array_map(
            static fn (array $pair): int => $pair['courseId'],
            array_filter($pairs, static fn (array $pair): bool => 0 === $pair['sessionId'])
        )));
        $pairSessionIds = array_values(array_unique(array_map(
            static fn (array $pair): int => $pair['sessionId'],
            array_filter($pairs, static fn (array $pair): bool => $pair['sessionId'] > 0)
        )));

        $urlIdsByCourse = [];
        if (!empty($directCourseIds)) {
            $rows = $this->em->createQueryBuilder()
                ->select('IDENTITY(r.course) AS courseId, IDENTITY(r.url) AS urlId')
                ->from(AccessUrlRelCourse::class, 'r')
                ->where('r.course IN (:courseIds)')
                ->setParameter('courseIds', $directCourseIds)
                ->getQuery()
                ->getArrayResult()
            ;
            foreach ($rows as $row) {
                $urlIdsByCourse[(int) $row['courseId']][] = (int) $row['urlId'];
            }
        }

        $urlIdsBySession = [];
        $sessionTitleById = [];
        if (!empty($pairSessionIds)) {
            $rows = $this->em->createQueryBuilder()
                ->select('IDENTITY(r.session) AS sessionId, IDENTITY(r.url) AS urlId')
                ->from(AccessUrlRelSession::class, 'r')
                ->where('r.session IN (:sessionIds)')
                ->setParameter('sessionIds', $pairSessionIds)
                ->getQuery()
                ->getArrayResult()
            ;
            foreach ($rows as $row) {
                $urlIdsBySession[(int) $row['sessionId']][] = (int) $row['urlId'];
            }

            $sessionRows = $this->em->createQueryBuilder()
                ->select('s.id AS id, s.title AS title')
                ->from(Session::class, 's')
                ->where('s.id IN (:sessionIds)')
                ->setParameter('sessionIds', $pairSessionIds)
                ->getQuery()
                ->getArrayResult()
            ;
            foreach ($sessionRows as $row) {
                $sessionTitleById[(int) $row['id']] = $row['title'];
            }
        }

        $rowsByUrl = [];
        foreach ($pairs as $pair) {
            $urlIds = $pair['sessionId'] > 0
                ? ($urlIdsBySession[$pair['sessionId']] ?? [])
                : ($urlIdsByCourse[$pair['courseId']] ?? []);

            foreach ($urlIds as $urlId) {
                $rowsByUrl[$urlId][] = [
                    'courseId' => $pair['courseId'],
                    'sessionId' => $pair['sessionId'],
                    'sessionTitle' => $pair['sessionId'] > 0 ? ($sessionTitleById[$pair['sessionId']] ?? null) : null,
                ];
            }
        }

        $urls = [];
        foreach ($urlRows as $row) {
            $urlId = (int) $row['id'];
            $urls[] = [
                'id' => $urlId,
                'url' => $row['url'],
                'rows' => $rowsByUrl[$urlId] ?? [],
            ];
        }

        return $this->json([
            'urls' => $urls,
        ]);
    }

    /**
     * One course's own detail page (issue #8903 follow-up): global metrics
     * aggregated across every URL and session the course is used in, plus a
     * breakdown of each URL the course touches — either directly
     * (access_url_rel_course) or through a session in that URL
     * (session_rel_course + access_url_rel_session). There is no existing
     * "single course aggregate" query anywhere in the codebase to reuse (the
     * closest, GlobalReportingSectionQueryService::getAdminCourseOverview(),
     * is scoped to the current URL only and explicitly excludes session
     * usage via a hardcoded session_id = 0 filter) — these are new queries,
     * kept local to this controller rather than touching that shared file.
     */
    #[Route('/courses/{id}/detail', name: 'admin_urls_course_detail_data', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function courseDetail(int $id): JsonResponse
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        $course = $this->em->find(Course::class, $id);
        if (null === $course) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $currentUser = $this->currentUser();
        if (!$this->accessUrlScope->isCourseManaged($currentUser, $id)) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }
        $managedUrlIds = $this->accessUrlScope->getManagedUrlIds($currentUser);

        $connection = $this->em->getConnection();

        // The "global metrics" block below must never fold in tracking data from users the
        // caller cannot manage, so every query gets an extra user-scope filter when scoped
        // (added as a subquery against access_url_rel_user, keyed on each table's own user
        // column — most tables use user_id, track_e_exercises uses exe_user_id).
        $scopeParams = [];
        $scopeTypes = [];
        $userScopeSql = '';
        $exeUserScopeSql = '';
        if (null !== $managedUrlIds) {
            $scopeParams['managedUrlIds'] = $managedUrlIds;
            $scopeTypes['managedUrlIds'] = ArrayParameterType::INTEGER;
            $userScopeSql = ' AND user_id IN (SELECT user_id FROM access_url_rel_user WHERE access_url_id IN (:managedUrlIds))';
            $exeUserScopeSql = ' AND exe_user_id IN (SELECT user_id FROM access_url_rel_user WHERE access_url_id IN (:managedUrlIds))';
        }

        $learners = (int) $connection->fetchOne(
            "SELECT COUNT(*) FROM (
                SELECT user_id FROM course_rel_user WHERE c_id = :courseId{$userScopeSql}
                UNION
                SELECT user_id FROM session_rel_course_rel_user WHERE c_id = :courseId{$userScopeSql}
            ) learners",
            ['courseId' => $id, ...$scopeParams],
            $scopeTypes
        );

        $totalTimeSeconds = (int) $connection->fetchOne(
            "SELECT COALESCE(SUM(
                CASE WHEN logout_course_date IS NOT NULL AND logout_course_date >= login_course_date
                     THEN TIMESTAMPDIFF(SECOND, login_course_date, logout_course_date)
                     ELSE 0 END
            ), 0) FROM track_e_course_access WHERE c_id = :courseId{$userScopeSql}",
            ['courseId' => $id, ...$scopeParams],
            $scopeTypes
        );

        $avgProgress = (float) $connection->fetchOne(
            "SELECT COALESCE(AVG(progress), 0) FROM c_lp_view WHERE c_id = :courseId{$userScopeSql}",
            ['courseId' => $id, ...$scopeParams],
            $scopeTypes
        );

        $avgScore = (float) $connection->fetchOne(
            "SELECT COALESCE(AVG(CASE WHEN max_score > 0 THEN score * 100 / max_score ELSE 0 END), 0)
               FROM track_e_exercises WHERE c_id = :courseId{$exeUserScopeSql}",
            ['courseId' => $id, ...$scopeParams],
            $scopeTypes
        );

        $directLearners = (int) $connection->fetchOne(
            "SELECT COUNT(DISTINCT user_id) FROM course_rel_user WHERE c_id = :courseId{$userScopeSql}",
            ['courseId' => $id, ...$scopeParams],
            $scopeTypes
        );

        $sessionRows = $this->em->createQueryBuilder()
            ->select('s.id AS id, s.title AS title, s.displayStartDate AS displayStartDate, s.displayEndDate AS displayEndDate')
            ->from(SessionRelCourse::class, 'src')
            ->innerJoin('src.session', 's')
            ->where('src.course = :courseId')
            ->setParameter('courseId', $id)
            ->getQuery()
            ->getArrayResult()
        ;
        $sessionIds = array_map(static fn (array $row): int => (int) $row['id'], $sessionRows);

        $sessionLearnersById = [];
        if (!empty($sessionIds)) {
            $rows = $connection->fetchAllAssociative(
                'SELECT session_id, COUNT(DISTINCT user_id) AS cnt
                   FROM session_rel_course_rel_user
                  WHERE c_id = :courseId AND session_id IN (:sessionIds)
               GROUP BY session_id',
                ['courseId' => $id, 'sessionIds' => $sessionIds],
                ['sessionIds' => ArrayParameterType::INTEGER]
            );
            foreach ($rows as $row) {
                $sessionLearnersById[(int) $row['session_id']] = (int) $row['cnt'];
            }
        }

        // Per-row (direct + each session) time/progress/score, keyed by session id
        // with 0 meaning "direct". track_e_course_access stores 0 for direct rows,
        // while c_lp_view and track_e_exercises store NULL there instead — verified
        // against the live data, not assumed — so COALESCE(session_id, 0) normalizes
        // both conventions to the same key across all three tables.
        $metricSessionKeys = array_values(array_unique(array_merge([0], $sessionIds)));

        $timeBySessionKey = [];
        $rows = $connection->fetchAllAssociative(
            'SELECT COALESCE(session_id, 0) AS sessionKey, COALESCE(SUM(
                CASE WHEN logout_course_date IS NOT NULL AND logout_course_date >= login_course_date
                     THEN TIMESTAMPDIFF(SECOND, login_course_date, logout_course_date)
                     ELSE 0 END
            ), 0) AS timeSeconds
               FROM track_e_course_access
              WHERE c_id = :courseId AND COALESCE(session_id, 0) IN (:sessionKeys)
           GROUP BY COALESCE(session_id, 0)',
            ['courseId' => $id, 'sessionKeys' => $metricSessionKeys],
            ['sessionKeys' => ArrayParameterType::INTEGER]
        );
        foreach ($rows as $row) {
            $timeBySessionKey[(int) $row['sessionKey']] = (int) $row['timeSeconds'];
        }

        $progressBySessionKey = [];
        $rows = $connection->fetchAllAssociative(
            'SELECT COALESCE(session_id, 0) AS sessionKey, COALESCE(AVG(progress), 0) AS avgProgress
               FROM c_lp_view
              WHERE c_id = :courseId AND COALESCE(session_id, 0) IN (:sessionKeys)
           GROUP BY COALESCE(session_id, 0)',
            ['courseId' => $id, 'sessionKeys' => $metricSessionKeys],
            ['sessionKeys' => ArrayParameterType::INTEGER]
        );
        foreach ($rows as $row) {
            $progressBySessionKey[(int) $row['sessionKey']] = round((float) $row['avgProgress'], 2);
        }

        $scoreBySessionKey = [];
        $rows = $connection->fetchAllAssociative(
            'SELECT COALESCE(session_id, 0) AS sessionKey,
                    COALESCE(AVG(CASE WHEN max_score > 0 THEN score * 100 / max_score ELSE 0 END), 0) AS avgScore
               FROM track_e_exercises
              WHERE c_id = :courseId AND COALESCE(session_id, 0) IN (:sessionKeys)
           GROUP BY COALESCE(session_id, 0)',
            ['courseId' => $id, 'sessionKeys' => $metricSessionKeys],
            ['sessionKeys' => ArrayParameterType::INTEGER]
        );
        foreach ($rows as $row) {
            $scoreBySessionKey[(int) $row['sessionKey']] = round((float) $row['avgScore'], 2);
        }

        $directUrlRowsQb = $this->em->createQueryBuilder()
            ->select('a.id AS id')
            ->from(AccessUrlRelCourse::class, 'r')
            ->innerJoin('r.url', 'a')
            ->where('r.course = :courseId')
            ->setParameter('courseId', $id)
        ;
        if (null !== $managedUrlIds) {
            $directUrlRowsQb->andWhere('r.url IN (:managedUrlIds)')->setParameter('managedUrlIds', $managedUrlIds);
        }
        $directUrlRows = $directUrlRowsQb->getQuery()->getArrayResult();
        $directUrlIds = array_map(static fn (array $row): int => (int) $row['id'], $directUrlRows);

        $sessionUrlsBySession = [];
        $allSessionUrlIds = [];
        if (!empty($sessionIds)) {
            $sessionUrlRowsQb = $this->em->createQueryBuilder()
                ->select('IDENTITY(r.session) AS sessionId, a.id AS urlId')
                ->from(AccessUrlRelSession::class, 'r')
                ->innerJoin('r.url', 'a')
                ->where('r.session IN (:sessionIds)')
                ->setParameter('sessionIds', $sessionIds)
            ;
            if (null !== $managedUrlIds) {
                $sessionUrlRowsQb->andWhere('r.url IN (:managedUrlIds)')->setParameter('managedUrlIds', $managedUrlIds);
            }
            $sessionUrlRows = $sessionUrlRowsQb->getQuery()->getArrayResult();
            foreach ($sessionUrlRows as $row) {
                $urlId = (int) $row['urlId'];
                $sessionUrlsBySession[(int) $row['sessionId']][] = $urlId;
                $allSessionUrlIds[] = $urlId;
            }
        }

        // A session with no remaining managed-URL link (e.g. only linked to a portal outside
        // this admin's subtree) must not be counted or shown, even though its metrics were
        // already computed above using the full, unfiltered session id set.
        if (null !== $managedUrlIds) {
            $sessionRows = array_values(array_filter(
                $sessionRows,
                static fn (array $row): bool => !empty($sessionUrlsBySession[(int) $row['id']] ?? [])
            ));
        }

        $allUrlIds = array_values(array_unique(array_merge($directUrlIds, $allSessionUrlIds)));

        $urls = [];
        if (!empty($allUrlIds)) {
            $allUrlRows = $this->em->createQueryBuilder()
                ->select('a.id AS id, a.url AS url')
                ->from(AccessUrl::class, 'a')
                ->where('a.id IN (:ids)')
                ->setParameter('ids', $allUrlIds)
                ->getQuery()
                ->getArrayResult()
            ;

            foreach ($allUrlRows as $row) {
                $urlId = (int) $row['id'];

                $sessionsForUrl = [];
                foreach ($sessionRows as $sessionRow) {
                    $sessionId = (int) $sessionRow['id'];
                    if (!\in_array($urlId, $sessionUrlsBySession[$sessionId] ?? [], true)) {
                        continue;
                    }

                    $sessionsForUrl[] = [
                        'id' => $sessionId,
                        'title' => $sessionRow['title'],
                        'displayStartDate' => $sessionRow['displayStartDate']?->format('Y-m-d'),
                        'displayEndDate' => $sessionRow['displayEndDate']?->format('Y-m-d'),
                        'learners' => $sessionLearnersById[$sessionId] ?? 0,
                        'totalTimeSeconds' => $timeBySessionKey[$sessionId] ?? 0,
                        'avgProgress' => $progressBySessionKey[$sessionId] ?? 0,
                        'avgScore' => $scoreBySessionKey[$sessionId] ?? 0,
                    ];
                }

                $urls[] = [
                    'id' => $urlId,
                    'url' => $row['url'],
                    'direct' => \in_array($urlId, $directUrlIds, true) ? [
                        'learners' => $directLearners,
                        'totalTimeSeconds' => $timeBySessionKey[0] ?? 0,
                        'avgProgress' => $progressBySessionKey[0] ?? 0,
                        'avgScore' => $scoreBySessionKey[0] ?? 0,
                    ] : null,
                    'sessions' => $sessionsForUrl,
                ];
            }
        }

        return $this->json([
            'course' => [
                'id' => $id,
                'title' => $course->getTitle(),
                'code' => $course->getCode(),
            ],
            'metrics' => [
                'learners' => $learners,
                'totalTimeSeconds' => $totalTimeSeconds,
                'avgProgress' => round($avgProgress, 2),
                'avgScore' => round($avgScore, 2),
                'sessionsCount' => \count($sessionRows),
                'urlsCount' => \count($allUrlIds),
            ],
            'urls' => $urls,
        ]);
    }

    /**
     * Daily login counts for the "Multi URLs" dashboard's chart. Login records
     * (track_e_login) carry no access_url_id column, so a scoped admin's figures are only
     * an approximation: logins by users *currently* registered in one of their managed
     * URLs, not necessarily the URL they were registered in at the time of that login.
     * An unrestricted admin gets the same install-wide, unscoped figures as before. Every
     * row counts as a login regardless of session duration, per this feature's own
     * requirement.
     */
    #[Route('/logins', name: 'admin_urls_logins_data', methods: ['GET'])]
    public function logins(Request $request): JsonResponse
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        $managedUrlIds = $this->accessUrlScope->getManagedUrlIds($this->currentUser());

        $today = new DateTimeImmutable('today');
        $from = $this->parseDate($request->query->get('from'), $today->modify('-29 days'));
        $to = $this->parseDate($request->query->get('to'), $today);

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $qb = $this->em->createQueryBuilder()
            ->select(
                'SUBSTRING(l.loginDate, 1, 10) AS day, COUNT(l.loginId) AS cnt,
                 COUNT(DISTINCT IDENTITY(l.user)) AS uniqueCnt'
            )
            ->from(TrackELogin::class, 'l')
            ->where('l.loginDate BETWEEN :from AND :to')
            ->setParameter('from', $from->setTime(0, 0, 0))
            ->setParameter('to', $to->setTime(23, 59, 59))
            ->groupBy('day')
        ;
        // A subquery, not a join: a join would duplicate a login row for every managed URL
        // the logging-in user happens to belong to, inflating the plain (non-distinct) count.
        if (null !== $managedUrlIds) {
            $qb->andWhere(
                'IDENTITY(l.user) IN (SELECT IDENTITY(scopeRel.user) FROM '.AccessUrlRelUser::class.' scopeRel '
                .'WHERE scopeRel.url IN (:managedUrlIds))'
            )->setParameter('managedUrlIds', $managedUrlIds);
        }

        $rows = $qb->getQuery()->getArrayResult();

        $countsByDay = [];
        $uniqueCountsByDay = [];
        foreach ($rows as $row) {
            $countsByDay[$row['day']] = (int) $row['cnt'];
            $uniqueCountsByDay[$row['day']] = (int) $row['uniqueCnt'];
        }

        $labels = [];
        $counts = [];
        $uniqueCounts = [];
        $cursor = $from;
        while ($cursor <= $to) {
            $day = $cursor->format('Y-m-d');
            $labels[] = $day;
            $counts[] = $countsByDay[$day] ?? 0;
            $uniqueCounts[] = $uniqueCountsByDay[$day] ?? 0;
            $cursor = $cursor->modify('+1 day');
        }

        return $this->json([
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'labels' => $labels,
            'counts' => $counts,
            'uniqueCounts' => $uniqueCounts,
            'scoped' => null !== $managedUrlIds,
        ]);
    }

    private function parseDate(?string $value, DateTimeImmutable $default): DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return $default;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            return $default;
        }
    }
}

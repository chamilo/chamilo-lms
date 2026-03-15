<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\AccessUrlRelCourse;
use Chamilo\CoreBundle\Entity\CatalogueCourseRelAccessUrlRelUsergroup;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/course-list-data')]
class CourseListController extends AbstractController
{
    private const ALLOWED_SORT_FIELDS = [
        'title' => 'c.title',
        'code' => 'c.code',
        'courseLanguage' => 'c.courseLanguage',
        'subscribe' => 'c.subscribe',
        'unsubscribe' => 'c.unsubscribe',
        'creationDate' => 'c.creationDate',
        'visibility' => 'c.visibility',
    ];

    private const VISIBILITY_LABELS = [
        Course::CLOSED => 'Closed - the account is not active',
        Course::REGISTERED => 'Private access (access authorized to group members only)',
        Course::OPEN_PLATFORM => 'Open - access allowed for users registered on the platform',
        Course::OPEN_WORLD => 'Public - access allowed for the whole world',
        Course::HIDDEN => 'Hidden - Pair with access URL condition',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly CourseRelUserRepository $courseRelUserRepository,
    ) {}

    #[Route('', name: 'admin_course_list_data', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(200, (int) $request->query->get('limit', 20)));
        $sortField = (string) $request->query->get('sortField', 'title');
        $sortOrder = 'DESC' === strtoupper((string) $request->query->get('sortOrder', 'ASC')) ? 'DESC' : 'ASC';
        $view = (string) $request->query->get('view', 'simple');

        $keyword = trim((string) $request->query->get('keyword', ''));
        $keywordCode = trim((string) $request->query->get('keyword_code', ''));
        $keywordTitle = trim((string) $request->query->get('keyword_title', ''));
        $keywordCategory = trim((string) $request->query->get('keyword_category', ''));
        $keywordLanguage = trim((string) $request->query->get('keyword_language', ''));
        $keywordVisibility = $request->query->get('keyword_visibility');
        $keywordSubscribe = $request->query->get('keyword_subscribe');
        $keywordUnsubscribe = $request->query->get('keyword_unsubscribe');
        $courseTeachers = $request->query->all('course_teachers');

        $dqlSortField = self::ALLOWED_SORT_FIELDS[$sortField] ?? 'c.title';

        $accessUrl = $this->accessUrlHelper->getCurrent();

        $qb = $this->em->createQueryBuilder()
            ->from(Course::class, 'c')
            ->innerJoin(AccessUrlRelCourse::class, 'auc', 'WITH', 'auc.course = c')
        ;

        if ($accessUrl) {
            $qb->andWhere('auc.url = :accessUrl')
                ->setParameter('accessUrl', $accessUrl)
            ;
        }

        if ('' !== $keyword) {
            $qb->andWhere('(c.title LIKE :kw OR c.code LIKE :kw)')
                ->setParameter('kw', '%'.$keyword.'%')
            ;
        } else {
            if ('' !== $keywordTitle) {
                $qb->andWhere('c.title LIKE :kwTitle')->setParameter('kwTitle', '%'.$keywordTitle.'%');
            }
            if ('' !== $keywordCode) {
                $qb->andWhere('c.code LIKE :kwCode')->setParameter('kwCode', '%'.$keywordCode.'%');
            }
            if ('' !== $keywordLanguage) {
                $qb->andWhere('c.courseLanguage = :kwLang')->setParameter('kwLang', $keywordLanguage);
            }
            if ('' !== $keywordCategory) {
                $qb->innerJoin('c.categories', 'cat')
                    ->andWhere('cat.title LIKE :kwCat')
                    ->setParameter('kwCat', '%'.$keywordCategory.'%')
                ;
            }
            if (null !== $keywordVisibility && '' !== $keywordVisibility) {
                $qb->andWhere('c.visibility = :kwVis')->setParameter('kwVis', (int) $keywordVisibility);
            }
            if (null !== $keywordSubscribe && '' !== $keywordSubscribe) {
                $qb->andWhere('c.subscribe = :kwSub')->setParameter('kwSub', (int) $keywordSubscribe);
            }
            if (null !== $keywordUnsubscribe && '' !== $keywordUnsubscribe) {
                $qb->andWhere('c.unsubscribe = :kwUnsub')->setParameter('kwUnsub', (int) $keywordUnsubscribe);
            }
            if (!empty($courseTeachers)) {
                $qb->innerJoin('c.users', 'cruFilter')
                    ->andWhere('cruFilter.user IN (:teacherIds)')
                    ->andWhere('cruFilter.status = 1')
                    ->setParameter('teacherIds', array_map('intval', $courseTeachers))
                ;
            }
        }

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(DISTINCT c.id)')->getQuery()->getSingleScalarResult();

        $rows = (clone $qb)
            ->select('DISTINCT c.id, c.title, c.code, c.courseLanguage, c.visibility, c.subscribe, c.unsubscribe, c.creationDate')
            ->orderBy($dqlSortField, $sortOrder)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult()
        ;

        $courseIds = array_column($rows, 'id');

        $categoriesMap = [];
        $catalogueMap = [];
        $teachersMap = [];
        $lastAccessMap = [];

        if (!empty($courseIds)) {
            $this->loadCategories($courseIds, $categoriesMap);

            if ('simple' === $view) {
                $this->loadCatalogueStatus($courseIds, $accessUrl, $catalogueMap);
            }

            if ('admin' === $view) {
                $teachersMap = $this->courseRelUserRepository->getTeacherUsersByCourseIds($courseIds);
                $this->loadLastAccess($courseIds, $lastAccessMap);
            }
        }

        $items = [];
        foreach ($rows as $row) {
            $courseId = (int) $row['id'];
            $item = [
                'id' => $courseId,
                'title' => $row['title'] ?? '',
                'code' => $row['code'] ?? '',
                'courseLanguage' => $row['courseLanguage'] ?? '',
                'visibility' => (int) $row['visibility'],
                'visibilityLabel' => self::VISIBILITY_LABELS[(int) $row['visibility']] ?? 'Unknown',
                'subscribe' => (bool) $row['subscribe'],
                'unsubscribe' => (bool) $row['unsubscribe'],
                'creationDate' => $row['creationDate'] ? $row['creationDate']->format('Y-m-d H:i') : null,
            ];

            $item['categories'] = $categoriesMap[$courseId] ?? [];

            if ('simple' === $view) {
                $item['inCatalogue'] = $catalogueMap[$courseId] ?? false;
            }

            if ('admin' === $view) {
                $teachers = $teachersMap[$courseId] ?? [];
                $item['teachers'] = array_map(static fn ($user) => [
                    'id' => $user->getId(),
                    'name' => $user->getFirstname().' '.$user->getLastname(),
                ], $teachers);
                $item['lastAccess'] = $lastAccessMap[$courseId] ?? null;
            }

            $items[] = $item;
        }

        return $this->json([
            'items' => $items,
            'total' => $total,
            'csrfToken' => $this->csrfTokenManager->getToken('admin_course_list')->getValue(),
        ]);
    }

    private function loadCategories(array $courseIds, array &$categoriesMap): void
    {
        $catRows = $this->em->createQueryBuilder()
            ->select('c.id AS courseId, cat.id AS catId, cat.title AS catName')
            ->from(Course::class, 'c')
            ->innerJoin('c.categories', 'cat')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $courseIds)
            ->getQuery()
            ->getArrayResult()
        ;

        foreach ($catRows as $row) {
            $categoriesMap[(int) $row['courseId']][] = [
                'id' => (int) $row['catId'],
                'name' => $row['catName'],
            ];
        }
    }

    private function loadCatalogueStatus(array $courseIds, $accessUrl, array &$catalogueMap): void
    {
        if (!$accessUrl) {
            return;
        }

        $catRecords = $this->em->createQueryBuilder()
            ->select('IDENTITY(cc.course) AS courseId')
            ->from(CatalogueCourseRelAccessUrlRelUsergroup::class, 'cc')
            ->where('cc.course IN (:ids)')
            ->andWhere('cc.accessUrl = :url')
            ->setParameter('ids', $courseIds)
            ->setParameter('url', $accessUrl)
            ->getQuery()
            ->getArrayResult()
        ;

        foreach ($catRecords as $rec) {
            $catalogueMap[(int) $rec['courseId']] = true;
        }
    }

    private function loadLastAccess(array $courseIds, array &$lastAccessMap): void
    {
        $conn = $this->em->getConnection();
        $result = $conn->executeQuery(
            'SELECT c_id, MAX(logout_course_date) AS last_access FROM track_e_course_access WHERE c_id IN (?) AND session_id = 0 GROUP BY c_id',
            [$courseIds],
            [ArrayParameterType::INTEGER],
        );

        foreach ($result->fetchAllAssociative() as $row) {
            $lastAccessMap[(int) $row['c_id']] = $row['last_access'];
        }
    }
}

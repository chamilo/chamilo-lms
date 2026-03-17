<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use RuntimeException;
use SessionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_SESSION_MANAGER")'))]
#[Route('/admin/session-list-data')]
class SessionListController extends AbstractController
{
    private const ALLOWED_SORT_FIELDS = [
        'title' => 's.title',
        'categoryName' => 'sc.title',
        'displayStartDate' => 's.displayStartDate',
        'displayEndDate' => 's.displayEndDate',
        'nbrUsers' => 's.nbrUsers',
        'nbrCourses' => 's.nbrCourses',
        'visibility' => 's.visibility',
        'status' => 's.status',
    ];

    private const VISIBILITY_LABELS = [
        Session::READ_ONLY => 'Read only',
        Session::VISIBLE => 'Visible',
        Session::INVISIBLE => 'Invisible',
        Session::AVAILABLE => 'Available',
        Session::LIST_ONLY => 'List only',
    ];

    private const STATUS_LABELS = [
        Session::STATUS_PLANNED => 'Planned',
        Session::STATUS_PROGRESS => 'In progress',
        Session::STATUS_FINISHED => 'Finished',
        Session::STATUS_CANCELLED => 'Cancelled',
        Session::STATUS_UNKNOWN => 'Unknown',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    #[Route('', name: 'admin_session_list_data', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(200, (int) $request->query->get('limit', 20)));
        $sortField = (string) $request->query->get('sortField', 'title');
        $sortOrder = 'DESC' === strtoupper((string) $request->query->get('sortOrder', 'ASC')) ? 'DESC' : 'ASC';
        $keyword = trim((string) $request->query->get('keyword', ''));
        $categoryFilter = $request->query->get('category');
        $listType = (string) $request->query->get('listType', 'all');

        $dqlSortField = self::ALLOWED_SORT_FIELDS[$sortField] ?? 's.title';

        $qb = $this->em->createQueryBuilder()
            ->from(Session::class, 's')
            ->leftJoin('s.category', 'sc')
        ;

        $this->applyListTypeFilter($qb, $listType);

        // Keyword search
        if ('' !== $keyword) {
            $qb->andWhere('(s.title LIKE :kw OR sc.title LIKE :kw)')
                ->setParameter('kw', '%'.$keyword.'%')
            ;
        }

        // Category filter
        if (null !== $categoryFilter && '' !== $categoryFilter) {
            $qb->andWhere('sc.id = :catId')
                ->setParameter('catId', (int) $categoryFilter)
            ;
        }

        // Session admin restriction: only show sessions they manage
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            $qb->join('s.users', 'sru')
                ->andWhere('sru.user = :currentUser')
                ->andWhere('sru.relationType = :sessionAdminType')
                ->setParameter('currentUser', $user->getId(), Types::INTEGER)
                ->setParameter('sessionAdminType', Session::SESSION_ADMIN)
            ;
        }

        // Count – when GROUP BY / HAVING is active (replication tab), we must
        // count the number of groups rather than a plain COUNT aggregate.
        $countQb = clone $qb;
        if (!empty($countQb->getDQLPart('groupBy'))) {
            $total = \count($countQb->select('s.id')->getQuery()->getSingleColumnResult());
        } else {
            $total = (int) $countQb->select('COUNT(s.id)')->getQuery()->getSingleScalarResult();
        }

        // Data query
        $dataQb = (clone $qb)
            ->select(
                's.id',
                's.title',
                's.displayStartDate',
                's.displayEndDate',
                's.visibility',
                's.nbrUsers',
                's.nbrCourses',
                's.status',
                's.parentId',
                's.daysToNewRepetition',
                'sc.title AS categoryName',
            )
            ->orderBy($dqlSortField, $sortOrder)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
        ;

        $rows = $dataQb->getQuery()->getArrayResult();

        $items = [];
        foreach ($rows as $row) {
            $item = [
                'id' => $row['id'],
                'title' => $row['title'],
                'categoryName' => $row['categoryName'] ?? '',
                'displayStartDate' => $row['displayStartDate'] ? $row['displayStartDate']->format('Y-m-d H:i') : null,
                'displayEndDate' => $row['displayEndDate'] ? $row['displayEndDate']->format('Y-m-d H:i') : null,
                'visibility' => $row['visibility'],
                'visibilityLabel' => self::VISIBILITY_LABELS[$row['visibility']] ?? 'Unknown',
                'nbrUsers' => $row['nbrUsers'],
                'nbrCourses' => $row['nbrCourses'],
                'status' => $row['status'],
                'statusLabel' => self::STATUS_LABELS[$row['status']] ?? 'Unknown',
                'parentId' => $row['parentId'],
            ];

            // For replication tab, mark child sessions
            if ('replication' === $listType && null !== $row['parentId']) {
                $item['isChild'] = true;
            }

            $items[] = $item;
        }

        // For replication tab, fetch child sessions for each parent
        if ('replication' === $listType) {
            $items = $this->enrichWithChildSessions($items);
        }

        $isPlatformAdmin = $this->isGranted('ROLE_ADMIN');

        return $this->json([
            'items' => $items,
            'total' => $total,
            'statusLabels' => self::STATUS_LABELS,
            'visibilityLabels' => self::VISIBILITY_LABELS,
            'viewer' => [
                'isPlatformAdmin' => $isPlatformAdmin,
            ],
            'csrfToken' => $this->csrfTokenManager->getToken('session_list_action')->getValue(),
        ]);
    }

    #[Route('-action', name: 'admin_session_list_action', methods: ['POST'])]
    public function action(Request $request): JsonResponse
    {
        $action = (string) $request->request->get('action', '');
        $sessionIds = $request->request->all('sessionIds');
        $token = (string) $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('session_list_action', $token)) {
            return $this->json(['error' => 'Invalid CSRF token.'], 403);
        }

        if (empty($sessionIds)) {
            return $this->json(['error' => 'No sessions selected.'], 400);
        }

        $sessionIds = array_map('intval', $sessionIds);
        $isPlatformAdmin = $this->isGranted('ROLE_ADMIN');

        switch ($action) {
            case 'delete':
                if (!$isPlatformAdmin) {
                    return $this->json(['error' => 'Only platform admins can delete sessions.'], 403);
                }

                $sessions = $this->em->getRepository(Session::class)->findBy(['id' => $sessionIds]);
                foreach ($sessions as $session) {
                    $this->em->remove($session);
                }
                $this->em->flush();

                return $this->json(['success' => true, 'message' => 'Sessions deleted.']);

            case 'copy':
                // Session admins may only copy sessions they manage
                if (!$isPlatformAdmin) {
                    $allowedIds = $this->getSessionIdsManagedByCurrentUser();
                    $sessionIds = array_intersect($sessionIds, $allowedIds);
                }

                $copied = [];
                $errors = [];
                foreach ($sessionIds as $id) {
                    $newId = SessionManager::copy($id);
                    if ($newId) {
                        $copied[] = $newId;
                    } else {
                        $errors[] = $id;
                    }
                }

                return $this->json([
                    'success' => true,
                    'copied' => $copied,
                    'errors' => $errors,
                ]);

            case 'export_csv':
                if (!$isPlatformAdmin) {
                    $allowedIds = $this->getSessionIdsManagedByCurrentUser();
                    $sessionIds = array_intersect($sessionIds, $allowedIds);
                }
                if (empty($sessionIds)) {
                    return $this->json(['error' => 'No sessions to export.'], 400);
                }

                try {
                    // This method sends headers and calls exit() on success,
                    // or returns with no output if there is no data.
                    SessionManager::exportSessionsAsCSV($sessionIds);
                } catch (RuntimeException) {
                    return $this->json(['error' => 'No data to export.'], 400);
                }

                // Reached when no data was found (method returned without output)
                return $this->json(['error' => 'No data to export.'], 400);

            case 'export_zip':
                if (!$isPlatformAdmin) {
                    $allowedIds = $this->getSessionIdsManagedByCurrentUser();
                    $sessionIds = array_intersect($sessionIds, $allowedIds);
                }
                if (empty($sessionIds)) {
                    return $this->json(['error' => 'No sessions to export.'], 400);
                }

                try {
                    // This method sends headers and calls exit() on success
                    SessionManager::exportSessionsAsZip($sessionIds);
                } catch (RuntimeException) {
                    return $this->json(['error' => 'No data to export.'], 400);
                }

                // Fallback — should not be reached
                return $this->json(['error' => 'No data to export.'], 400);

            default:
                return $this->json(['error' => 'Unknown action.'], 400);
        }
    }

    /**
     * Returns session IDs that the current (non-admin) user manages as session admin.
     */
    private function getSessionIdsManagedByCurrentUser(): array
    {
        $user = $this->getUser();

        return array_map(
            'intval',
            $this->em->createQueryBuilder()
                ->select('IDENTITY(sru.session)')
                ->from(SessionRelUser::class, 'sru')
                ->where('sru.user = :user')
                ->andWhere('sru.relationType = :type')
                ->setParameter('user', $user->getId(), Types::INTEGER)
                ->setParameter('type', Session::SESSION_ADMIN)
                ->getQuery()
                ->getSingleColumnResult()
        );
    }

    private function applyListTypeFilter(QueryBuilder $qb, string $listType): void
    {
        $now = new DateTime('now', new DateTimeZone('UTC'));

        match ($listType) {
            // Active: access_end_date is null OR current date falls within access date range
            'active' => $qb->andWhere(
                $qb->expr()->orX(
                    's.accessEndDate IS NULL',
                    $qb->expr()->andX(
                        's.accessStartDate IS NOT NULL',
                        's.accessEndDate IS NOT NULL',
                        's.accessStartDate <= :now',
                        's.accessEndDate >= :now',
                    ),
                    $qb->expr()->andX(
                        's.accessStartDate IS NULL',
                        's.accessEndDate IS NOT NULL',
                        's.accessEndDate >= :now',
                    ),
                )
            )->setParameter('now', $now, Types::DATETIME_MUTABLE),

            // Closed: current date is past access_end_date
            'close' => $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        's.accessStartDate IS NOT NULL',
                        's.accessEndDate IS NOT NULL',
                        's.accessEndDate < :now',
                    ),
                    $qb->expr()->andX(
                        's.accessStartDate IS NULL',
                        's.accessEndDate IS NOT NULL',
                        's.accessEndDate < :now',
                    ),
                )
            )->setParameter('now', $now, Types::DATETIME_MUTABLE),

            // Custom: only PLANNED or IN PROGRESS status
            'custom' => $qb->andWhere('s.status IN (:customStatuses)')
                ->setParameter('customStatuses', [Session::STATUS_PLANNED, Session::STATUS_PROGRESS], ArrayParameterType::INTEGER),

            // Replication: only sessions configured for repetition with <= 1 child
            'replication' => $qb->andWhere('s.daysToNewRepetition IS NOT NULL')
                ->andWhere('s.parentId IS NULL')
                ->leftJoin(Session::class, 'child', 'WITH', 'child.parentId = s.id')
                ->groupBy('s.id')
                ->addGroupBy('sc.id')
                ->having('COUNT(child.id) <= 1'),

            // All: no filter
            default => null,
        };
    }

    /**
     * For the replication tab, insert child sessions after their parent.
     */
    private function enrichWithChildSessions(array $items): array
    {
        $parentIds = array_column($items, 'id');
        if (empty($parentIds)) {
            return $items;
        }

        $children = $this->em->createQueryBuilder()
            ->select(
                's.id',
                's.title',
                's.displayStartDate',
                's.displayEndDate',
                's.visibility',
                's.nbrUsers',
                's.nbrCourses',
                's.status',
                's.parentId',
                'sc.title AS categoryName',
            )
            ->from(Session::class, 's')
            ->leftJoin('s.category', 'sc')
            ->where('s.parentId IN (:parentIds)')
            ->setParameter('parentIds', $parentIds, ArrayParameterType::INTEGER)
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $childMap = [];
        foreach ($children as $child) {
            $childMap[$child['parentId']][] = [
                'id' => $child['id'],
                'title' => '-- '.$child['title'],
                'categoryName' => $child['categoryName'] ?? '',
                'displayStartDate' => $child['displayStartDate'] ? $child['displayStartDate']->format('Y-m-d H:i') : null,
                'displayEndDate' => $child['displayEndDate'] ? $child['displayEndDate']->format('Y-m-d H:i') : null,
                'visibility' => $child['visibility'],
                'visibilityLabel' => self::VISIBILITY_LABELS[$child['visibility']] ?? 'Unknown',
                'nbrUsers' => $child['nbrUsers'],
                'nbrCourses' => $child['nbrCourses'],
                'status' => $child['status'],
                'statusLabel' => self::STATUS_LABELS[$child['status']] ?? 'Unknown',
                'parentId' => $child['parentId'],
                'isChild' => true,
            ];
        }

        // Interleave: insert children right after their parent
        $result = [];
        foreach ($items as $item) {
            $result[] = $item;
            if (isset($childMap[$item['id']])) {
                foreach ($childMap[$item['id']] as $childItem) {
                    $result[] = $childItem;
                }
            }
        }

        return $result;
    }
}

<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpView;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CourseRelUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseRelUser::class);
    }

    /**
     * Retrieves users from a course and their LP progress (without session).
     */
    public function getCourseUsers(int $courseId, array $lpIds): array
    {
        $qb = $this->createQueryBuilder('cu')
            ->select('u.id AS userId, c.title AS courseTitle, lp.iid AS lpId, COALESCE(lpv.progress, 0) AS progress')
            ->innerJoin('cu.user', 'u')
            ->innerJoin('cu.course', 'c')
            ->leftJoin(CLpView::class, 'lpv', 'WITH', 'lpv.user = u.id AND lpv.course = cu.course AND lpv.lp IN (:lpIds)')
            ->leftJoin(CLp::class, 'lp', 'WITH', 'lp.iid IN (:lpIds)')
            ->innerJoin('lp.resourceNode', 'rn')
            ->where('cu.course = :courseId')
            ->andWhere('rn.parent = c.resourceNode')
            ->andWhere('(lpv.progress < 100 OR lpv.progress IS NULL)')
            ->setParameter('courseId', $courseId)
            ->setParameter('lpIds', $lpIds)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * Count distinct courses where the given user is a teacher (status == TEACHER).
     */
    public function countTaughtCoursesForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('cru')
            ->select('COUNT(DISTINCT c.id)')
            ->innerJoin('cru.course', 'c')
            ->andWhere('cru.user = :user')
            ->andWhere('cru.status = :teacher')
            ->setParameter('user', $user)
            ->setParameter('teacher', CourseRelUser::TEACHER)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count all subscriptions (any status) for the given course ID.
     * Optionally exclude a specific relationType (e.g., RRHH).
     *
     * @param int        $courseId
     * @param int|null   $excludeRelationType If provided, rows with this relationType are excluded
     */
    public function countAllByCourseId(int $courseId, ?int $excludeRelationType = null): int
    {
        $qb = $this->createQueryBuilder('cru')
            ->select('COUNT(cru.id)')
            ->innerJoin('cru.course', 'c')
            ->andWhere('c.id = :cid')
            ->setParameter('cid', $courseId);

        if (null !== $excludeRelationType) {
            $qb->andWhere('cru.relationType <> :rt')
                ->setParameter('rt', $excludeRelationType);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Compute a capacity snapshot for UI (limit/used/left/is_full).
     * This only considers the global hosting limit and an optional per-course limit
     * already computed by caller. A limit of 0 means "no limit".
     *
     * @param int        $courseId
     * @param int|null   $perCourseLimit  Optional course-level limit (e.g., extra field). If provided, the effective
     *                                    limit will be min(global, per-course). If null, only global limit is used.
     *
     * @return array{limit:int, used:int, left:int|null, is_full:bool}
     */
    public function getCapacitySnapshot(int $courseId, ?int $perCourseLimit = null): array
    {
        // Read global limit (0 means unlimited)
        $global = (int) api_get_setting('hosting_limit_users_per_course');

        // Effective limit resolution rule:
        // - if per-course limit is provided and > 0, use min(global, per-course) when global > 0, else per-course
        // - else use global as-is (0 => unlimited)
        $limit = 0;
        if ($perCourseLimit && $perCourseLimit > 0) {
            $limit = $global > 0 ? min($global, $perCourseLimit) : $perCourseLimit;
        } else {
            $limit = $global;
        }

        if ($limit <= 0) {
            return ['limit' => 0, 'used' => 0, 'left' => null, 'is_full' => false];
        }

        // Exclude RRHH relation type if available (keeps legacy behavior)
        $exclude = \defined('COURSE_RELATION_TYPE_RRHH') ? COURSE_RELATION_TYPE_RRHH : null;
        $used = $this->countAllByCourseId($courseId, $exclude);

        return [
            'limit'   => $limit,
            'used'    => $used,
            'left'    => max(0, $limit - $used),
            'is_full' => $used >= $limit,
        ];
    }
}

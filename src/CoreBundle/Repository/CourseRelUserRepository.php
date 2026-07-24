<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
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
     * Returns courses managed by the user as a teacher in the given access URL.
     *
     * @return list<array{
     *     id: int,
     *     title: string,
     *     code: string,
     *     visualCode: string|null,
     *     visibility: int
     * }>
     */
    public function findTeacherCoursesForUserAndAccessUrl(User $user, AccessUrl $accessUrl): array
    {
        /** @var list<array{id: int, title: string, code: string, visualCode: string|null, visibility: int}> $courses */
        $courses = $this->createQueryBuilder('cru')
            ->select(
                'DISTINCT course.id AS id, course.title AS title, course.code AS code, '
                .'course.visualCode AS visualCode, course.visibility AS visibility'
            )
            ->innerJoin('cru.course', 'course')
            ->innerJoin('course.urls', 'urlRelation')
            ->andWhere('cru.user = :user')
            ->andWhere('cru.status = :teacherStatus')
            ->andWhere('urlRelation.url = :accessUrl')
            ->setParameter('user', $user)
            ->setParameter('teacherStatus', CourseRelUser::TEACHER)
            ->setParameter('accessUrl', $accessUrl)
            ->orderBy('course.title', 'ASC')
            ->addOrderBy('course.id', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        return $courses;
    }

    /**
     * Returns a course only when the user manages it as a teacher in the given access URL.
     */
    public function findTeacherCourseForUserAndAccessUrl(
        User $user,
        AccessUrl $accessUrl,
        int $courseId,
    ): ?Course {
        /** @var CourseRelUser|null $relation */
        $relation = $this->createQueryBuilder('cru')
            ->addSelect('course')
            ->innerJoin('cru.course', 'course')
            ->innerJoin('course.urls', 'urlRelation')
            ->andWhere('cru.user = :user')
            ->andWhere('cru.status = :teacherStatus')
            ->andWhere('course.id = :courseId')
            ->andWhere('urlRelation.url = :accessUrl')
            ->setParameter('user', $user)
            ->setParameter('teacherStatus', CourseRelUser::TEACHER)
            ->setParameter('courseId', $courseId)
            ->setParameter('accessUrl', $accessUrl)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $relation?->getCourse();
    }

    /**
     * Counts users directly registered as students in the base course.
     */
    public function countDirectStudentsForCourse(Course $course): int
    {
        return (int) $this->createQueryBuilder('cru')
            ->select('COUNT(DISTINCT student.id)')
            ->innerJoin('cru.user', 'student')
            ->andWhere('cru.course = :course')
            ->andWhere('cru.status = :studentStatus')
            ->setParameter('course', $course)
            ->setParameter('studentStatus', CourseRelUser::STUDENT)
            ->getQuery()
            ->getSingleScalarResult()
        ;
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
            ->getSingleScalarResult()
        ;
    }

    /**
     * Returns teachers grouped by courseId.
     *
     * Output:
     * [
     *   12 => [User, User, ...],
     *   45 => [User, ...],
     * ]
     */
    public function getTeacherUsersByCourseIds(array $courseIds, ?int $sessionId = null): array
    {
        $courseIds = array_values(array_unique(array_filter(array_map('intval', $courseIds), static fn (int $id) => $id > 0)));
        if (empty($courseIds)) {
            return [];
        }

        $qb = $this->createQueryBuilder('cru')
            ->innerJoin('cru.course', 'c')->addSelect('c')
            ->innerJoin('cru.user', 'u')->addSelect('u')
            ->andWhere('c.id IN (:courseIds)')
            ->andWhere('cru.status = :teacher')
            ->setParameter('courseIds', $courseIds)
            ->setParameter('teacher', CourseRelUser::TEACHER)
        ;

        if (null !== $sessionId && $sessionId > 0) {
            $meta = $this->getClassMetadata();
            if ($meta->hasAssociation('session')) {
                $qb->andWhere('cru.session = :sid')->setParameter('sid', $sessionId);
            } elseif ($meta->hasField('sessionId')) {
                $qb->andWhere('cru.sessionId = :sid')->setParameter('sid', $sessionId);
            }
        }

        /** @var CourseRelUser[] $crus */
        $crus = $qb->getQuery()->getResult();

        $out = [];
        foreach ($crus as $cru) {
            $courseId = (int) $cru->getCourse()->getId();
            $user = $cru->getUser();
            $userId = (int) $user->getId();

            if ($courseId <= 0 || $userId <= 0) {
                continue;
            }
            $out[$courseId][$userId] = $user;
        }

        foreach ($out as $cid => $usersById) {
            $out[$cid] = array_values($usersById);
        }

        return $out;
    }
}

<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\ChamiloHelper;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class CourseRepository.
 *
 * The functions inside this class should return an instance of QueryBuilder.
 */
class CourseRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    public function deleteCourse(Course $course): void
    {
        $em = $this->getEntityManager();

        // Deleting all nodes connected to the course:
        // $node = $course->getResourceNode();
        // $children = $node->getChildren();
        // /* var ResourceNode $child
        /*foreach ($children as $child) {
            var_dump($child->getId().'-'.$child->getTitle().'<br />');
            var_dump(get_class($child));
            $em->remove($child);
        }*/

        $em->remove($course);
        $em->flush();
        $em->clear();
    }

    public function findOneByCode(string $code): ?Course
    {
        return $this->findOneBy([
            'code' => $code,
        ]);
    }

    /**
     * Get course user relationship based in the course_rel_user table.
     *
     * @return array<int, CourseRelUser>
     */
    public function getCoursesByUser(User $user, AccessUrl $url): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('courseRelUser')
            ->from(Course::class, 'c')
            ->innerJoin(CourseRelUser::class, 'courseRelUser')
            // ->innerJoin('c.users', 'courseRelUser')
            ->innerJoin('c.urls', 'accessUrlRelCourse')
            ->where('courseRelUser.user = :user')
            ->andWhere('accessUrlRelCourse.url = :url')
            ->setParameters([
                'user' => $user,
                'url' => $url,
            ])
        ;

        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * Get info from courses where the user has the given role.
     *
     * @return Course[]
     */
    public function getCoursesInfoByUser(User $user, AccessUrl $url, int $status, string $keyword = ''): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('DISTINCT c.id, c.title, c.code')
            ->from(Course::class, 'c')
            ->innerJoin(CourseRelUser::class, 'courseRelUser')
            ->innerJoin('c.urls', 'accessUrlRelCourse')
            ->where('accessUrlRelCourse.url = :url')
            ->andWhere('courseRelUser.user = :user')
            ->andWhere('courseRelUser.status = :status')
            ->setParameters([
                'user' => $user,
                'url' => $url,
                'status' => $status,
            ])
        ;

        if (!empty($keyword)) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('c.title', ':keyword'),
                $qb->expr()->like('c.code', ':keyword')
            ))
                ->setParameter('keyword', '%'.$keyword.'%')
            ;
        }

        $query = $qb->getQuery();

        return $query->getArrayResult();
    }

    /**
     * Get all users that are registered in the course. No matter the status.
     *
     * @return QueryBuilder
     */
    public function getSubscribedUsers(Course $course)
    {
        // Course builder
        $queryBuilder = $this->createQueryBuilder('c');

        // Selecting user info.
        $queryBuilder->select('DISTINCT user');

        // Selecting courses for users.
        $queryBuilder->innerJoin('c.users', 'subscriptions');
        $queryBuilder->innerJoin(
            User::class,
            'user',
            Join::WITH,
            'subscriptions.user = user.id'
        );

        if (api_is_western_name_order()) {
            $queryBuilder->orderBy('user.firstname', Criteria::ASC);
        } else {
            $queryBuilder->orderBy('user.lastname', Criteria::ASC);
        }

        $wherePart = $queryBuilder->expr()->andx();

        // Get only users subscribed to this course
        $wherePart->add($queryBuilder->expr()->eq('c.id', $course->getId()));

        // $wherePart->add($queryBuilder->expr()->eq('c.status', $status));

        $queryBuilder->where($wherePart);

        return $queryBuilder;
    }

    /**
     * Gets students subscribed in the course.
     *
     * @return QueryBuilder
     */
    public function getSubscribedStudents(Course $course)
    {
        return $this->getSubscribedUsersByStatus($course, STUDENT);
    }

    /**
     * Gets the students subscribed in the course.
     *
     * @return QueryBuilder
     */
    public function getSubscribedCoaches(Course $course)
    {
        return $this->getSubscribedUsers($course);
    }

    /**
     * Gets the teachers subscribed in the course.
     *
     * @return QueryBuilder
     */
    public function getSubscribedTeachers(Course $course)
    {
        return $this->getSubscribedUsersByStatus($course, ChamiloHelper::COURSE_MANAGER);
    }

    /**
     * @param int $status use legacy chamilo constants COURSEMANAGER|STUDENT
     *
     * @return QueryBuilder
     */
    public function getSubscribedUsersByStatus(Course $course, int $status)
    {
        $queryBuilder = $this->getSubscribedUsers($course);
        $queryBuilder
            ->andWhere(
                $queryBuilder->expr()->eq('subscriptions.status', $status)
            )
        ;

        return $queryBuilder;
    }

    public function courseCodeExists(string $code): bool
    {
        $qb = $this->createQueryBuilder('c')
            ->select('count(c.id)')
            ->where('c.code = :code OR c.visualCode = :code')
            ->setParameter('code', $code)
            ->getQuery()
        ;

        return (int) $qb->getSingleScalarResult() > 0;
    }

    public function findCourseAsArray($id)
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c.id, c.code, c.title, c.visualCode, c.courseLanguage, c.departmentUrl, c.departmentName')
            ->where('c.id = :id')
            ->setParameter('id', $id)
        ;

        $query = $qb->getQuery();

        return $query->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);
    }

    public function getPersonalSessionCourses(
        User $user,
        AccessUrl $url,
        bool $isAllowedToCreateCourse,
        ?int $sessionLimit = null,
    ): array {
        $em = $this->getEntityManager();

        /** @var SessionRepository $sessionRepo */
        $sessionRepo = $em->getRepository(Session::class);
        $sessionCourseUserRepo = $em->getRepository(SessionRelCourseRelUser::class);

        $qb = $em->createQueryBuilder();

        $courseListSqlResult = $qb
            ->select('c.id AS cid')
            ->from(CourseRelUser::class, 'cru')
            ->leftJoin('cru.course', 'c')
            ->leftJoin('c.urls', 'urc')
            ->where($qb->expr()->eq('cru.user', ':user'))
            ->andWhere($qb->expr()->neq('cru.relationType', ':relationType'))
            ->andWhere($qb->expr()->eq('urc.url', ':url'))
            ->setParameters([
                'user' => $user->getId(),
                'relationType' => COURSE_RELATION_TYPE_RRHH,
                'url' => $url->getId(),
            ])
            ->getQuery()
            ->getResult()
        ;

        $personalCourseList = $courseListSqlResult;

        $sessionListFromCourseCoach = [];
        // Getting sessions that are related to a coach in the session_rel_course_rel_user table
        if ($isAllowedToCreateCourse) {
            $sessionListFromCourseCoach = array_map(
                fn (SessionRelCourseRelUser $srcru) => $srcru->getSession()->getId(),
                $sessionCourseUserRepo->findBy(['user' => $user->getId(), 'status' => Session::COURSE_COACH])
            );
        }

        // Get the list of sessions where the user is subscribed
        // This is divided into two different queries
        /** @var array<int, Session> $sessions */
        $sessions = [];

        $qb = $sessionRepo->createQueryBuilder('s');
        $qbParams = [
            'user' => $user->getId(),
            'relationType' => Session::STUDENT,
        ];

        $qb
            ->innerJoin('s.users', 'su')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('su.user', ':user'),
                    $qb->expr()->neq('su.relationType', ':relationType')
                )
            )
        ;

        if ($sessionListFromCourseCoach) {
            $qb->orWhere($qb->expr()->in('s.id', ':sessionListFromCourseCoach'));

            $qbParams['coachCourseConditions'] = $sessionListFromCourseCoach;
        }

        $result = $qb
            ->orderBy('s.accessStartDate')
            ->addOrderBy('s.accessEndDate')
            ->addOrderBy('s.title')
            ->setMaxResults($sessionLimit)
            ->setParameters($qbParams)
            ->getQuery()
            ->getResult()
        ;

        /** @var Session $row */
        foreach ($result as $row) {
            $row->setAccessVisibilityByUser($user);

            $sessions[$row->getId()] = $row;
        }

        $qb = $sessionRepo->createQueryBuilder('s');
        $qbParams = [
            'user' => $user->getId(),
            'relationType' => Session::GENERAL_COACH,
        ];

        $qb
            ->innerJoin('s.users', 'sru')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('sru.user', ':user'),
                    $qb->expr()->neq('sru.relationType', ':relationType')
                )
            )
        ;

        if ($sessionListFromCourseCoach) {
            $qb->orWhere($qb->expr()->in('s.id', ':sessionListFromCourseCoach'));

            $qbParams['coachCourseConditions'] = $sessionListFromCourseCoach;
        }

        $result = $qb
            ->orderBy('s.accessStartDate')
            ->addOrderBy('s.accessEndDate')
            ->addOrderBy('s.title')
            ->setParameters($qbParams)
            ->getQuery()
            ->getResult()
        ;

        /** @var Session $row */
        foreach ($result as $row) {
            $row->setAccessVisibilityByUser($user);

            $sessions[$row->getId()] = $row;
        }

        if ($isAllowedToCreateCourse) {
            foreach ($sessions as $enreg) {
                if (Session::INVISIBLE == $enreg->getAccessVisibility()) {
                    continue;
                }

                $coursesAsGeneralCoach = $sessionRepo->getSessionCoursesByStatusInUserSubscription(
                    $user,
                    $enreg,
                    Session::GENERAL_COACH,
                    $url
                );
                $coursesAsCourseCoach = $sessionRepo->getSessionCoursesByStatusInCourseSubscription(
                    $user,
                    $enreg,
                    Session::COURSE_COACH,
                    $url
                );

                // This query is horribly slow when more than a few thousand
                // users and just a few sessions to which they are subscribed
                $coursesInSession = array_merge($coursesAsGeneralCoach, $coursesAsCourseCoach);

                /** @var SessionRelCourse $resultRow */
                foreach ($coursesInSession as $resultRow) {
                    $sid = $resultRow->getSession()->getId();
                    $cid = $resultRow->getCourse()->getId();

                    $personalCourseList["$sid - $cid"] = [
                        'cid' => $cid,
                        'sid' => $sid,
                    ];
                }
            }
        }

        foreach ($sessions as $enreg) {
            if (Session::INVISIBLE == $enreg->getAccessVisibility()) {
                continue;
            }

            // This query is very similar to the above query,
            // but it will check the session_rel_course_user table if there are courses registered to our user or not */
            $qb = $sessionCourseUserRepo->createQueryBuilder('scu');

            $result = $qb
                ->select('c.id as cid', 's.id AS sid')
                ->innerJoin('scu.course', 'c', Join::WITH, 'scu.session = :session')
                ->innerJoin('scu.session', 's')
                ->leftJoin('scu.user', 'u')
                ->where($qb->expr()->eq('scu.user', ':user'))
                ->orderBy('c.title')
                ->setParameters([
                    'session' => $enreg->getId(),
                    'user' => $user->getId(),
                ])
                ->getQuery()
                ->getResult()
            ;

            foreach ($result as $resultRow) {
                $key = $resultRow['sid'].' - '.$resultRow['cid'];

                if (!isset($personalCourseList[$key])) {
                    $personalCourseList[$key] = $resultRow;
                }
            }
        }

        return $personalCourseList;
    }

    /**
     * Returns all courses assigned to a specific AccessUrl.
     *
     * @return Course[]
     */
    public function getCoursesByAccessUrl(AccessUrl $url): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.urls', 'u')
            ->where('u.url = :url')
            ->setParameter('url', $url)
            ->orderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}

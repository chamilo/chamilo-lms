<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Repository;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Course;
use Doctrine\ORM\Query\Expr\Join;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\UserBundle\Entity\User;

//use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
//use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

/**
 * Class UserRepository
 *
 * All functions that query the database (selects)
 * Functions should return query builders.
 *
 * @package Chamilo\UserBundle\Repository
 */
class UserRepository extends EntityRepository
{
    /**
    * @param string $keyword
     *
    * @return mixed
    */
    public function searchUserByKeyword($keyword)
    {
        $qb = $this->createQueryBuilder('a');

        // Selecting user info
        $qb->select('DISTINCT b');

        $qb->from('Chamilo\UserBundle\Entity\User', 'b');

        // Selecting courses for users
        //$qb->innerJoin('u.courses', 'c');

        //@todo check app settings
        $qb->add('orderBy', 'b.firstname ASC');
        $qb->where('b.firstname LIKE :keyword OR b.lastname LIKE :keyword ');
        $qb->setParameter('keyword', "%$keyword%");
        $query = $qb->getQuery();

        return $query->execute();
    }

    /**
     * @param string $role
     * @return array
     */
    public function findByRole($role)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('u')
            ->from($this->_entityName, 'u')
            ->where('u.roles LIKE :roles')
            ->setParameter('roles', '%"'.$role.'"%');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get course user relationship based in the course_rel_user table.
     * @return array
     */
    /*public function getCourses(User $user)
    {
        $queryBuilder = $this->createQueryBuilder('user');

        // Selecting course info.
        $queryBuilder->select('c');

        // Loading User.
        //$qb->from('Chamilo\UserBundle\Entity\User', 'u');

        // Selecting course
        $queryBuilder->innerJoin('Chamilo\CoreBundle\Entity\Course', 'c');

        //@todo check app settings
        //$qb->add('orderBy', 'u.lastname ASC');

        $wherePart = $queryBuilder->expr()->andx();

        // Get only users subscribed to this course
        $wherePart->add($queryBuilder->expr()->eq('user.userId', $user->getUserId()));

        $queryBuilder->where($wherePart);
        $query = $queryBuilder->getQuery();

        return $query->execute();
    }

    public function getTeachers()
    {
        $queryBuilder = $this->createQueryBuilder('u');

        // Selecting course info.
        $queryBuilder
            ->select('u')
            ->where('u.groups.id = :groupId')
            ->setParameter('groupId', 1);

        $query = $queryBuilder->getQuery();

        return $query->execute();
    }*/

    /*public function getUsers($group)
    {
        $queryBuilder = $this->createQueryBuilder('u');

        // Selecting course info.
        $queryBuilder
            ->select('u')
            ->where('u.groups = :groupId')
            ->setParameter('groupId', $group);

        $query = $queryBuilder->getQuery();

        return $query->execute();
    }*/

    /**
     * Get a filtered list of user by status and (optionally) access url
     * @todo not use status
     *
     * @param string $query The query to filter
     * @param int $status The status
     * @param int $accessUrlId The access URL ID
     * @return array
     */
    public function searchUsersByStatus($query, $status, $accessUrlId = null)
    {
        $accessUrlId = intval($accessUrlId);

        $queryBuilder = $this->createQueryBuilder('u');

        if ($accessUrlId > 0) {
            $queryBuilder->innerJoin(
                'ChamiloCoreBundle:AccessUrlRelUser',
                'auru',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'u.id = auru.userId'
            );
        }

        $queryBuilder->where('u.status = :status')
            ->andWhere('u.username LIKE :query OR u.firstname LIKE :query OR u.lastname LIKE :query')
            ->setParameter('status', $status)
            ->setParameter('query', "$query%");

        if ($accessUrlId > 0) {
            $queryBuilder->andWhere('auru.accessUrlId = :url')
                ->setParameter(':url', $accessUrlId);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get the coaches for a course within a session
     * @param Session $session The session
     * @param Course $course The course
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getCoachesForSessionCourse(Session $session, Course $course)
    {
        $queryBuilder = $this->createQueryBuilder('u');

        $queryBuilder->select('u')
            ->innerJoin(
                'ChamiloCoreBundle:SessionRelCourseRelUser',
                'scu',
                Join::WITH,
                'scu.user = u'
            )
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('scu.session', $session->getId()),
                    $queryBuilder->expr()->eq('scu.course', $course->getId()),
                    $queryBuilder->expr()->eq('scu.status', SessionRelCourseRelUser::STATUS_COURSE_COACH)
                )
            );

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get course user relationship based in the course_rel_user table.
     * @return array
     */
    /*public function getCourses(User $user)
    {
        $queryBuilder = $this->createQueryBuilder('user');

        // Selecting course info.
        $queryBuilder->select('c');

        // Loading User.
        //$qb->from('Chamilo\UserBundle\Entity\User', 'u');

        // Selecting course
        $queryBuilder->innerJoin('Chamilo\CoreBundle\Entity\Course', 'c');

        //@todo check app settings
        //$qb->add('orderBy', 'u.lastname ASC');

        $wherePart = $queryBuilder->expr()->andx();

        // Get only users subscribed to this course
        $wherePart->add($queryBuilder->expr()->eq('user.userId', $user->getUserId()));

        $queryBuilder->where($wherePart);
        $query = $queryBuilder->getQuery();

        return $query->execute();
    }

    public function getTeachers()
    {
        $queryBuilder = $this->createQueryBuilder('u');

        // Selecting course info.
        $queryBuilder
            ->select('u')
            ->where('u.groups.id = :groupId')
            ->setParameter('groupId', 1);

        $query = $queryBuilder->getQuery();

        return $query->execute();
    }*/

    /*public function getUsers($group)
    {
        $queryBuilder = $this->createQueryBuilder('u');

        // Selecting course info.
        $queryBuilder
            ->select('u')
            ->where('u.groups = :groupId')
            ->setParameter('groupId', $group);

        $query = $queryBuilder->getQuery();

        return $query->execute();
    }*/

    /**
     * Get the sessions admins for a user
     * @param User $user The user
     * @return array
     */
    public function getSessionAdmins(User $user)
    {
        $queryBuilder = $this->createQueryBuilder('u');
        $queryBuilder
            ->distinct()
            ->innerJoin(
                'ChamiloCoreBundle:SessionRelUser',
                'su',
                Join::WITH,
                $queryBuilder->expr()->eq('u', 'su.user')
            )
            ->innerJoin(
                'ChamiloCoreBundle:SessionRelCourseRelUser',
                'scu',
                Join::WITH,
                $queryBuilder->expr()->eq('su.session', 'scu.session')
            )
            ->where(
                $queryBuilder->expr()->eq('scu.user', $user->getId())
            )
            ->andWhere(
                $queryBuilder->expr()->eq('su.relationType', SESSION_RELATION_TYPE_RRHH)
            )
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get the student bosses for a user
     * @param User $user The user
     * @return array
     */
    public function getStudentBosses(User $user)
    {
        $queryBuilder = $this->createQueryBuilder('u');
        $queryBuilder
            ->distinct()
            ->innerJoin(
                'ChamiloCoreBundle:UserRelUser',
                'uu',
                Join::WITH,
                $queryBuilder->expr()->eq('u.id', 'uu.friendUserId')
            )
            ->where(
                $queryBuilder->expr()->eq('uu.relationType', USER_RELATION_TYPE_BOSS)
            )
            ->andWhere(
                $queryBuilder->expr()->eq('uu.userId', $user->getId())
            );

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get number of users in URL
     * @param AccessUrl $url
     *
     * @return int
     */
    public function getCountUsersByUrl(AccessUrl $url)
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->innerJoin('a.portals', 'u')
            ->where('u.portal = :u')
            ->setParameters(['u' => $url])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get number of users in URL
     * @param AccessUrl $url
     *
     * @return int
     */
    public function getCountTeachersByUrl(AccessUrl $url)
    {
        $qb = $this->createQueryBuilder('a');

        return $qb
            ->select('COUNT(a)')
            ->innerJoin('a.portals', 'u')
            ->where('u.portal = :u and u.group = :g')
            ->andWhere($qb->expr()->in('a.roles', ['ROLE_TEACHER']))
            ->setParameters(['u' => $url, 'g' => $group])
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}

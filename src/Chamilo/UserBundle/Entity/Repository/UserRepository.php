<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Query\Expr\Join;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;

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
     * Get the sessions admins for a user
     * @param User $user The user
     * @return array
     */
    public function getSessionAdmins($user)
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
            );

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get the student bosses for a user
     * @param User $user The user
     * @return array
     */
    public function getStudentBosses($user)
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
     * Find potencial users to send a message
     * @param int $currentUserId The current user ID
     * @param string $search The search text to filter the user list
     * @param int $limit Optional. Sets the maximum number of results to retrieve
     * @return mixed
     */
    public function findUsersToSendMessage($currentUserId, $search, $limit = 10)
    {
        $accessUrlId = api_get_multiple_access_url() ? api_get_current_access_url_id() : 1;

        if (api_get_setting('allow_social_tool') === 'true' && api_get_setting('allow_message_tool') === 'true') {
            // All users
            if (api_get_setting('allow_send_message_to_all_platform_users') === 'true' || api_is_platform_admin()) {
                $dql = "SELECT DISTINCT U
                        FROM ChamiloUserBundle:User U
                        LEFT JOIN ChamiloCoreBundle:AccessUrlRelUser R WITH U = R.user
                        WHERE
                            U.status != 6  AND
                            U.id != $currentUserId AND
                            R.portal = $accessUrlId";
            } else {
                $dql = "SELECT DISTINCT U
                        FROM ChamiloCoreBundle:AccessUrlRelUser R, ChamiloCoreBundle:UserRelUser UF
                        INNER JOIN ChamiloUserBundle:User AS U WITH UF.friendUserId = U
                        WHERE
                            U.status != 6 AND
                            UF.relationType NOT IN(" . USER_RELATION_TYPE_DELETED . ", " . USER_RELATION_TYPE_RRHH . ") AND
                            UF.userId = $currentUserId AND
                            UF.friendUserId != $currentUserId AND
                            U = R.user AND
                            R.portal = $accessUrlId";
            }
        } elseif (
            api_get_setting('allow_social_tool') === 'false' && api_get_setting('allow_message_tool') === 'true'
        ) {
            if (api_get_setting('allow_send_message_to_all_platform_users') === 'true') {
                $dql = "SELECT DISTINCT U
                        FROM ChamiloUserBundle:User U
                        LEFT JOIN ChamiloCoreBundle:AccessUrlRelUser R WITH U = R.user
                        WHERE
                            U.status != 6  AND
                            U.id != $currentUserId AND
                            R.portal = $accessUrlId";
            } else {
                $time_limit = api_get_setting('time_limit_whosonline');
                $online_time = time() - $time_limit * 60;
                $limit_date = api_get_utc_datetime($online_time);
                $dql = "SELECT DISTINCT U
                        FROM ChamiloUserBundle:User U
                        INNER JOIN ChamiloCoreBundle:TrackEOnline T WITH U.id = T.loginUserId
                        WHERE T.loginDate >= '" . $limit_date . "'";
            }
        }

        $dql .= ' AND (U.firstname LIKE :search OR U.lastname LIKE :search OR U.email LIKE :search)';

        return $this->getEntityManager()
            ->createQuery($dql)
            ->setMaxResults($limit)
            ->setParameters(['search' => "%$search%"])
            ->getResult();
    }
}

<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

//use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
//use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

/**
 * Class UserRepository.
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
     *
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
     *
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
     * Get a filtered list of user by status and (optionally) access url.
     *
     * @param string $query       The query to filter
     * @param int    $status      The status
     * @param int    $accessUrlId The access URL ID
     *
     * @return array
     */
    public function searchUsersByStatus($query, $status, $accessUrlId = 0)
    {
        $accessUrlId = (int) $accessUrlId;
        $queryBuilder = $this->createQueryBuilder('u');

        if ($accessUrlId > 0) {
            $queryBuilder->innerJoin(
                'ChamiloCoreBundle:AccessUrlRelUser',
                'auru',
                Join::WITH,
                'u.id = auru.userId'
            );
        }

        $queryBuilder
            ->where('u.status = :status')
            ->andWhere('u.username LIKE :query OR u.firstname LIKE :query OR u.lastname LIKE :query')
            ->setParameter('status', $status)
            ->setParameter('query', "$query%");

        if ($accessUrlId > 0) {
            $queryBuilder
                ->andWhere('auru.accessUrlId = :url')
                ->setParameter(':url', $accessUrlId);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get the coaches for a course within a session.
     *
     * @param Session $session The session
     * @param Course  $course  The course
     *
     * @return array
     */
    public function getCoachesForSessionCourse(Session $session, Course $course)
    {
        $queryBuilder = $this->createQueryBuilder('u');

        $queryBuilder
            ->select('u')
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
     *
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
     * Get the sessions admins for a user.
     *
     * @param User $user
     *
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
            );

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get the student bosses for a user.
     *
     * @param User $user
     *
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
     * Find potential users to send a message.
     *
     * @param int    $currentUserId The current user ID
     * @param string $search        The search text to filter the user list
     * @param int    $limit         Optional. Sets the maximum number of results to retrieve
     *
     * @return mixed
     */
    public function findUsersToSendMessage($currentUserId, $search, $limit = 10)
    {
        $allowSendMessageToAllUsers = api_get_setting('allow_send_message_to_all_platform_users');
        $accessUrlId = api_get_multiple_access_url() ? api_get_current_access_url_id() : 1;

        if (api_get_setting('allow_social_tool') === 'true' &&
            api_get_setting('allow_message_tool') === 'true'
        ) {
            // All users
            if ($allowSendMessageToAllUsers === 'true' || api_is_platform_admin()) {
                $dql = "SELECT DISTINCT U
                        FROM ChamiloUserBundle:User U
                        LEFT JOIN ChamiloCoreBundle:AccessUrlRelUser R
                        WITH U = R.user
                        WHERE
                            U.active = 1 AND
                            U.status != 6  AND
                            U.id != $currentUserId AND
                            R.portal = $accessUrlId";
            } else {
                $dql = "SELECT DISTINCT U
                        FROM ChamiloCoreBundle:AccessUrlRelUser R, ChamiloCoreBundle:UserRelUser UF
                        INNER JOIN ChamiloUserBundle:User AS U 
                        WITH UF.friendUserId = U
                        WHERE
                            U.active = 1 AND
                            U.status != 6 AND
                            UF.relationType NOT IN(".USER_RELATION_TYPE_DELETED.", ".USER_RELATION_TYPE_RRHH.") AND
                            UF.userId = $currentUserId AND
                            UF.friendUserId != $currentUserId AND
                            U = R.user AND
                            R.portal = $accessUrlId";
            }
        } elseif (
            api_get_setting('allow_social_tool') === 'false' &&
            api_get_setting('allow_message_tool') === 'true'
        ) {
            if ($allowSendMessageToAllUsers === 'true') {
                $dql = "SELECT DISTINCT U
                        FROM ChamiloUserBundle:User U
                        LEFT JOIN ChamiloCoreBundle:AccessUrlRelUser R 
                        WITH U = R.user
                        WHERE
                            U.active = 1 AND
                            U.status != 6  AND
                            U.id != $currentUserId AND
                            R.portal = $accessUrlId";
            } else {
                $time_limit = api_get_setting('time_limit_whosonline');
                $online_time = time() - $time_limit * 60;
                $limit_date = api_get_utc_datetime($online_time);
                $dql = "SELECT DISTINCT U
                        FROM ChamiloUserBundle:User U
                        INNER JOIN ChamiloCoreBundle:TrackEOnline T 
                        WITH U.id = T.loginUserId
                        WHERE 
                          U.active = 1 AND 
                          T.loginDate >= '".$limit_date."'";
            }
        }

        $dql .= ' AND (U.firstname LIKE :search OR U.lastname LIKE :search OR U.email LIKE :search OR U.username LIKE :search)';

        return $this->getEntityManager()
            ->createQuery($dql)
            ->setMaxResults($limit)
            ->setParameters(['search' => "%$search%"])
            ->getResult();
    }

    /**
     * Get the list of HRM who have assigned this user.
     *
     * @param int $userId
     * @param int $urlId
     *
     * @return array
     */
    public function getAssignedHrmUserList($userId, $urlId)
    {
        $qb = $this->createQueryBuilder('user');

        $hrmList = $qb
            ->select('uru')
            ->innerJoin('ChamiloCoreBundle:UserRelUser', 'uru', Join::WITH, 'uru.userId = user.id')
            ->innerJoin('ChamiloCoreBundle:AccessUrlRelUser', 'auru', Join::WITH, 'auru.userId = uru.friendUserId')
            ->where(
                $qb->expr()->eq('auru.accessUrlId', $urlId)
            )
            ->andWhere(
                $qb->expr()->eq('uru.userId', $userId)
            )
            ->andWhere(
                $qb->expr()->eq('uru.relationType', USER_RELATION_TYPE_RRHH)
            )
            ->getQuery()
            ->getResult();

        return $hrmList;
    }

    /**
     * Serialize the whole entity to an array.
     *
     * @param int   $userId
     * @param array $substitutionTerms Substitute terms for some elements
     *
     * @return array $values
     */
    public function getPersonalDataToJson($userId, array $substitutionTerms)
    {
        /** @var User $user */
        $user = $this->find($userId);

        $user->setPassword($substitutionTerms['password']);
        $user->setSalt($substitutionTerms['salt']);
        $noDataLabel = $substitutionTerms['empty'];

        // Dummy content
        $user->setDateOfBirth(null);
        //$user->setBiography($noDataLabel);
        $user->setFacebookData($noDataLabel);
        $user->setFacebookName($noDataLabel);
        $user->setFacebookUid($noDataLabel);
        //$user->setImageName($noDataLabel);
        //$user->setTwoStepVerificationCode($noDataLabel);
        $user->setGender($noDataLabel);
        $user->setGplusData($noDataLabel);
        $user->setGplusName($noDataLabel);
        $user->setGplusUid($noDataLabel);
        $user->setLocale($noDataLabel);
        $user->setTimezone($noDataLabel);
        $user->setTwitterData($noDataLabel);
        $user->setTwitterName($noDataLabel);
        $user->setTwitterUid($noDataLabel);
        $user->setWebsite($noDataLabel);
        $user->setToken($noDataLabel);

        //$courses = $user->getCourses();

        $user->setCourses([]);
        $user->setClasses([]);
        $user->setDropBoxSentFiles([]);
        $user->setDropBoxReceivedFiles([]);
        $user->setGroups([]);
        $user->setCurriculumItems([]);
        $user->setPortals([]);
        $user->setSessionCourseSubscriptions([]);
        $user->setSessionAsGeneralCoach([]);
        $user->setAchievedSkills([]);
        $user->setCommentedUserSkills([]);

        $extraFieldValues = new \ExtraFieldValue('user');
        $items = $extraFieldValues->getAllValuesByItem($userId);
        $user->setExtraFields($items);


        $lastLogin = $user->getLastLogin();
        if (empty($lastLogin)) {
            $login = $this->getLastLogin($user);
            if ($login) {
                $lastLogin = $login->getLoginDate();
            }
        }
        $user->setLastLogin($lastLogin);

        $dateNormalizer = new GetSetMethodNormalizer();

        $dateNormalizer->setCircularReferenceHandler(function ($object) {
            return get_class($object);
        });

        $ignore = [
            'twoStepVerificationCode',
            'biography',
            'dateOfBirth',
            'gender',
            'facebookData',
            'facebookName',
            'facebookUid',
            'gplusData',
            'gplusName',
            'gplusUid',
            'locale',
            'timezone',
            'twitterData',
            'twitterName',
            'twitterUid',
            'gplusUid',
            'token',
            'website',
            'plainPassword',
            'completeNameWithUsername',
            'completeName',
            'completeNameWithClasses',
        ];

        $dateNormalizer->setIgnoredAttributes($ignore);

        $callback = function ($dateTime) {
            return $dateTime instanceof \DateTime
                ? $dateTime->format(\DateTime::ISO8601)
                : '';
        };
        $dateNormalizer->setCallbacks(
            [
                'createdAt' => $callback,
                'lastLogin' => $callback,
                'registrationDate' => $callback,
                'memberSince' => $callback,
            ]
        );

        $normalizers = [$dateNormalizer];
        $serializer = new Serializer($normalizers, [new JsonEncoder()]);

        $jsonContent = $serializer->serialize($user, 'json');

        return $jsonContent;
    }

    /**
     * Get the last login from the track_e_login table.
     * This might be different from user.last_login in the case of legacy users
     * as user.last_login was only implemented in 1.10 version with a default
     * value of NULL (not the last record from track_e_login).
     *
     * @param User $user
     *
     * @throws \Exception
     *
     * @return null|TrackELogin
     */
    public function getLastLogin(User $user)
    {
        $repo = $this->getEntityManager()->getRepository('ChamiloCoreBundle:TrackELogin');
        $qb = $repo->createQueryBuilder('l');

        $login = $qb
            ->select('l')
            ->where(
                $qb->expr()->eq('l.loginUserId', $user->getId())
            )
            ->setMaxResults(1)
            ->orderBy('l.loginDate', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();

        return $login;
    }
}

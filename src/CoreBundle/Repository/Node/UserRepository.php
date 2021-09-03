<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\CoreBundle\Entity\TrackEOnline;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;
use Datetime;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends ResourceRepository implements PasswordUpgraderInterface
{
    protected ?UserPasswordHasherInterface $hasher = null;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function loadUserByIdentifier(string $identifier): ?User
    {
        return $this->findOneBy([
            'username' => $identifier,
        ]);
    }

    public function setHasher(UserPasswordHasherInterface $hasher): void
    {
        $this->hasher = $hasher;
    }

    public function createUser(): User
    {
        return new User();
    }

    public function updateUser(User $user, bool $andFlush = true): void
    {
        $this->updateCanonicalFields($user);
        $this->updatePassword($user);
        $this->getEntityManager()->persist($user);
        if ($andFlush) {
            $this->getEntityManager()->flush();
        }
    }

    public function canonicalize(string $string): string
    {
        $encoding = mb_detect_encoding($string, mb_detect_order(), true);

        return $encoding
            ? mb_convert_case($string, MB_CASE_LOWER, $encoding)
            : mb_convert_case($string, MB_CASE_LOWER);
    }

    public function updateCanonicalFields(User $user): void
    {
        $user->setUsernameCanonical($this->canonicalize($user->getUsername()));
        $user->setEmailCanonical($this->canonicalize($user->getEmail()));
    }

    public function updatePassword(User $user): void
    {
        $password = (string) $user->getPlainPassword();
        if ('' !== $password) {
            $password = $this->hasher->hashPassword($user, $password);
            $user->setPassword($password);
            $user->eraseCredentials();
        }
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        /** @var User $user */
        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function getRootUser(): User
    {
        $qb = $this->createQueryBuilder('u');
        $qb
            ->innerJoin(
                'u.resourceNode',
                'r'
            )
        ;
        $qb
            ->where('r.creator = u')
            ->andWhere('r.parent IS NULL')
            ->getFirstResult()
        ;

        $rootUser = $qb->getQuery()->getSingleResult();

        if (null === $rootUser) {
            throw new UserNotFoundException('Root user not found');
        }

        return $rootUser;
    }

    public function deleteUser(User $user): void
    {
        $em = $this->getEntityManager();
        $type = $user->getResourceNode()->getResourceType();
        $rootUser = $this->getRootUser();

        // User children will be set to the root user.
        $criteria = Criteria::create()->where(Criteria::expr()->eq('resourceType', $type));
        $userNodeCreatedList = $user->getResourceNodes()->matching($criteria);
        /** @var ResourceNode $userCreated */
        foreach ($userNodeCreatedList as $userCreated) {
            $userCreated->setCreator($rootUser);
        }

        $em->remove($user->getResourceNode());

        foreach ($user->getGroups() as $group) {
            $user->removeGroup($group);
        }

        $em->remove($user);
        $em->flush();
    }

    public function addUserToResourceNode(int $userId, int $creatorId): ResourceNode
    {
        /** @var User $user */
        $user = $this->find($userId);
        $creator = $this->find($creatorId);

        $resourceNode = (new ResourceNode())
            ->setTitle($user->getUsername())
            ->setCreator($creator)
            ->setResourceType($this->getResourceType())
            //->setParent($resourceNode)
        ;

        $user->setResourceNode($resourceNode);

        $this->getEntityManager()->persist($resourceNode);
        $this->getEntityManager()->persist($user);

        return $resourceNode;
    }

    public function addRoleListQueryBuilder(array $roleList, QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'u');
        if (!empty($roleList)) {
            $qb
                ->andWhere('u.roles IN (:roles)')
                ->setParameter('roles', $roleList, Types::ARRAY)
            ;
        }

        return $qb;
    }

    public function findByUsername(string $username): ?User
    {
        $user = $this->findOneBy([
            'username' => $username,
        ]);

        if (null === $user) {
            throw new UserNotFoundException(sprintf("User with id '%s' not found.", $username));
        }

        return $user;
    }

    /**
     * Get a filtered list of user by role and (optionally) access url.
     *
     * @param string $keyword     The query to filter
     * @param int    $accessUrlId The access URL ID
     *
     * @return User[]
     */
    public function findByRole(string $role, string $keyword, int $accessUrlId = 0)
    {
        $qb = $this->createQueryBuilder('u');

        $this->addAccessUrlQueryBuilder($accessUrlId, $qb);
        $this->addRoleQueryBuilder($role, $qb);
        $this->addSearchByKeywordQueryBuilder($keyword, $qb);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get course user relationship based in the course_rel_user table.
     *
     * @return Course[]
     */
    public function getCourses(User $user, AccessUrl $url, int $status, string $keyword = '')
    {
        $qb = $this->createQueryBuilder('u');

        $qb
            //->select('DISTINCT course')
            ->innerJoin('u.courses', 'courseRelUser')
            ->innerJoin('courseRelUser.course', 'course')
            ->innerJoin('course.urls', 'accessUrlRelCourse')
            ->innerJoin('accessUrlRelCourse.url', 'url')
            ->where('url = :url')
            ->andWhere('courseRelUser.user = :user')
            ->andWhere('courseRelUser.status = :status')
            ->setParameters(
                [
                    'user' => $user,
                    'url' => $url,
                    'status' => $status,
                ]
            )
        //    ->addSelect('courseRelUser')
        ;

        if (!empty($keyword)) {
            $qb
                ->andWhere('course.title like = :keyword OR course.code like = :keyword')
                ->setParameter('keyword', $keyword)
            ;
        }

        $qb->orderBy('course.title', Criteria::DESC);

        $query = $qb->getQuery();

        return $query->getResult();
    }

    /*
    public function getTeachers()
    {
        $queryBuilder = $this->repository->createQueryBuilder('u');

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
        $queryBuilder = $this->repository->createQueryBuilder('u');

        // Selecting course info.
        $queryBuilder
            ->select('u')
            ->where('u.groups = :groupId')
            ->setParameter('groupId', $group);

        $query = $queryBuilder->getQuery();

        return $query->execute();
    }*/

    /**
     * Get the coaches for a course within a session.
     *
     * @return Collection|array
     */
    public function getCoachesForSessionCourse(Session $session, Course $course)
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('u')
            ->innerJoin(
                'ChamiloCoreBundle:SessionRelCourseRelUser',
                'scu',
                Join::WITH,
                'scu.user = u'
            )
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('scu.session', $session->getId()),
                    $qb->expr()->eq('scu.course', $course->getId()),
                    $qb->expr()->eq('scu.status', SessionRelCourseRelUser::STATUS_COURSE_COACH)
                )
            )
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * Get course user relationship based in the course_rel_user table.
     *
     * @return array
     */
    /*public function getCourses(User $user)
    {
        $qb = $this->createQueryBuilder('user');

        // Selecting course info.
        $qb->select('c');

        // Loading User.
        //$qb->from('Chamilo\CoreBundle\Entity\User', 'u');

        // Selecting course
        $qb->innerJoin('Chamilo\CoreBundle\Entity\Course', 'c');

        //@todo check app settings
        //$qb->add('orderBy', 'u.lastname ASC');

        $wherePart = $qb->expr()->andx();

        // Get only users subscribed to this course
        $wherePart->add($qb->expr()->eq('user.userId', $user->getUserId()));

        $qb->where($wherePart);
        $query = $qb->getQuery();

        return $query->execute();
    }

    public function getTeachers()
    {
        $qb = $this->createQueryBuilder('u');

        // Selecting course info.
        $qb
            ->select('u')
            ->where('u.groups.id = :groupId')
            ->setParameter('groupId', 1);

        $query = $qb->getQuery();

        return $query->execute();
    }*/

    /*public function getUsers($group)
    {
        $qb = $this->createQueryBuilder('u');

        // Selecting course info.
        $qb
            ->select('u')
            ->where('u.groups = :groupId')
            ->setParameter('groupId', $group);

        $query = $qb->getQuery();

        return $query->execute();
    }*/

    /**
     * Get the sessions admins for a user.
     *
     * @return array
     */
    public function getSessionAdmins(User $user)
    {
        $qb = $this->createQueryBuilder('u');
        $qb
            ->distinct()
            ->innerJoin(
                'ChamiloCoreBundle:SessionRelUser',
                'su',
                Join::WITH,
                'u = su.user'
            )
            ->innerJoin(
                'ChamiloCoreBundle:SessionRelCourseRelUser',
                'scu',
                Join::WITH,
                'su.session = scu.session'
            )
            ->where(
                $qb->expr()->eq('scu.user', $user->getId())
            )
            ->andWhere(
                $qb->expr()->eq('su.relationType', SESSION_RELATION_TYPE_RRHH)
            )
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * Get number of users in URL.
     *
     * @return int
     */
    public function getCountUsersByUrl(AccessUrl $url)
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(a)')
            ->innerJoin('a.portals', 'p')
            ->where('p.portal = :p')
            ->setParameters([
                'p' => $url,
            ])
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Get number of users in URL.
     *
     * @return int
     */
    public function getCountTeachersByUrl(AccessUrl $url)
    {
        $qb = $this->createQueryBuilder('u');

        return $qb
            ->select('COUNT(u)')
            ->innerJoin('a.portals', 'p')
            ->where('p.portal = :p')
            ->andWhere($qb->expr()->in('u.roles', ['ROLE_TEACHER']))
            ->setParameters([
                'p' => $url,
            ])
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Find potential users to send a message.
     *
     * @todo remove  api_is_platform_admin
     *
     * @param int    $currentUserId The current user ID
     * @param string $searchFilter  Optional. The search text to filter the user list
     * @param int    $limit         Optional. Sets the maximum number of results to retrieve
     *
     * @return User[]
     */
    public function findUsersToSendMessage(int $currentUserId, string $searchFilter = null, int $limit = 10)
    {
        $allowSendMessageToAllUsers = api_get_setting('allow_send_message_to_all_platform_users');
        $accessUrlId = api_get_multiple_access_url() ? api_get_current_access_url_id() : 1;

        $messageTool = 'true' === api_get_setting('allow_message_tool');
        if (!$messageTool) {
            return [];
        }

        $qb = $this->createQueryBuilder('u');
        $this->addActiveAndNotAnonUserQueryBuilder($qb);
        $this->addAccessUrlQueryBuilder($accessUrlId, $qb);

        $dql = null;
        if ('true' === api_get_setting('allow_social_tool')) {
            // All users
            if ('true' === $allowSendMessageToAllUsers || api_is_platform_admin()) {
                $this->addNotCurrentUserQueryBuilder($currentUserId, $qb);
            /*$dql = "SELECT DISTINCT U
                    FROM ChamiloCoreBundle:User U
                    LEFT JOIN ChamiloCoreBundle:AccessUrlRelUser R
                    WITH U = R.user
                    WHERE
                        U.active = 1 AND
                        U.status != 6  AND
                        U.id != {$currentUserId} AND
                        R.url = {$accessUrlId}";*/
            } else {
                $this->addOnlyMyFriendsQueryBuilder($currentUserId, $qb);
                /*$dql = 'SELECT DISTINCT U
                        FROM ChamiloCoreBundle:AccessUrlRelUser R, ChamiloCoreBundle:UserRelUser UF
                        INNER JOIN ChamiloCoreBundle:User AS U
                        WITH UF.friendUserId = U
                        WHERE
                            U.active = 1 AND
                            U.status != 6 AND
                            UF.relationType NOT IN('.USER_RELATION_TYPE_DELETED.', '.USER_RELATION_TYPE_RRHH.") AND
                            UF.user = {$currentUserId} AND
                            UF.friendUserId != {$currentUserId} AND
                            U = R.user AND
                            R.url = {$accessUrlId}";*/
            }
        } else {
            if ('true' === $allowSendMessageToAllUsers) {
                $this->addNotCurrentUserQueryBuilder($currentUserId, $qb);
            } else {
                return [];
            }

            /*else {
                $time_limit = (int) api_get_setting('time_limit_whosonline');
                $online_time = time() - ($time_limit * 60);
                $limit_date = api_get_utc_datetime($online_time);
                $dql = "SELECT DISTINCT U
                        FROM ChamiloCoreBundle:User U
                        INNER JOIN ChamiloCoreBundle:TrackEOnline T
                        WITH U.id = T.loginUserId
                        WHERE
                          U.active = 1 AND
                          T.loginDate >= '".$limit_date."'";
            }*/
        }

        if (!empty($searchFilter)) {
            $this->addSearchByKeywordQueryBuilder($searchFilter, $qb);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the list of HRM who have assigned this user.
     *
     * @return User[]
     */
    public function getAssignedHrmUserList(int $userId, int $urlId)
    {
        $qb = $this->createQueryBuilder('u');
        $this->addAccessUrlQueryBuilder($urlId, $qb);
        $this->addActiveAndNotAnonUserQueryBuilder($qb);
        $this->addUserRelUserQueryBuilder($userId, UserRelUser::USER_RELATION_TYPE_RRHH, $qb);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the last login from the track_e_login table.
     * This might be different from user.last_login in the case of legacy users
     * as user.last_login was only implemented in 1.10 version with a default
     * value of NULL (not the last record from track_e_login).
     *
     * @return null|TrackELogin
     */
    public function getLastLogin(User $user)
    {
        $qb = $this->createQueryBuilder('u');

        return $qb
            ->select('l')
            ->innerJoin('u.logins', 'l')
            ->where(
                $qb->expr()->eq('l.user', $user)
            )
            ->setMaxResults(1)
            ->orderBy('u.loginDate', Criteria::DESC)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function addAccessUrlQueryBuilder(int $accessUrlId, QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'u');
        $qb
            ->innerJoin('u.portals', 'p')
            ->andWhere('p.url = :url')
            ->setParameter('url', $accessUrlId, Types::INTEGER)
        ;

        return $qb;
    }

    public function addActiveAndNotAnonUserQueryBuilder(QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'u');
        $qb
            ->andWhere('u.active = 1')
            ->andWhere('u.status <> :status')
            ->setParameter('status', User::ANONYMOUS, Types::INTEGER)
        ;

        return $qb;
    }

    public function addExpirationDateQueryBuilder(QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'u');
        $qb
            ->andWhere('u.registrationDate IS NULL OR u.registrationDate > :now')
            ->setParameter('now', new Datetime(), Types::DATETIME_MUTABLE)
        ;

        return $qb;
    }

    /**
     * @return CSurveyInvitation[]
     */
    public function getUserPendingInvitations(User $user)
    {
        $qb = $this->createQueryBuilder('u');
        $qb
            ->select('s')
            ->innerJoin('u.surveyInvitations', 's')
            ->andWhere('s.user = :u')
            ->andWhere('s.availFrom <= :now AND s.availTill >= :now')
            ->andWhere('s.answered = 0')
            ->setParameters([
                'now' => new Datetime(),
                'u' => $user,
            ])
            ->orderBy('s.availTill', Criteria::ASC)
        ;

        return $qb->getQuery()->getResult();
    }

    private function addRoleQueryBuilder(string $role, QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'u');
        $qb
            ->andWhere('u.roles LIKE :roles')
            ->setParameter('roles', '%"'.$role.'"%', Types::STRING)
        ;

        return $qb;
    }

    private function addSearchByKeywordQueryBuilder(string $keyword, QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'u');
        $qb
            ->andWhere('
                u.firstname LIKE :keyword OR
                u.lastname LIKE :keyword OR
                u.email LIKE :keyword OR
                u.username LIKE :keyword
            ')
            ->setParameter('keyword', "%$keyword%", Types::STRING)
            ->orderBy('u.firstname', Criteria::ASC)
        ;

        return $qb;
    }

    private function addUserRelUserQueryBuilder(int $userId, int $relationType, QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'u');
        $qb->leftJoin('u.friends', 'relations');
        $qb
            ->andWhere('relations.relationType = :relationType')
            ->andWhere('relations.user = :userRelation AND relations.friend <> :userRelation')
            ->setParameter('relationType', $relationType)
            ->setParameter('userRelation', $userId)
        ;

        return $qb;
    }

    private function addOnlyMyFriendsQueryBuilder(int $userId, QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'u');
        $qb
            ->leftJoin('u.friends', 'relations')
            ->andWhere(
                $qb->expr()->notIn(
                    'relations.relationType',
                    [UserRelUser::USER_RELATION_TYPE_DELETED, UserRelUser::USER_RELATION_TYPE_RRHH]
                )
            )
            ->andWhere('relations.user = :user AND relations.friend <> :user')
            ->setParameter('user', $userId, Types::INTEGER)
        ;

        return $qb;
    }

    private function addNotCurrentUserQueryBuilder(int $userId, QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'u');
        $qb
            ->andWhere('u.id <> :id')
            ->setParameter('id', $userId, Types::INTEGER)
        ;

        return $qb;
    }
}

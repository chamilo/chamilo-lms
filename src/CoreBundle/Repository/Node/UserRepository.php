<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository\Node;

use Agenda;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\GradebookResult;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\SkillRelUserComment;
use Chamilo\CoreBundle\Entity\Ticket;
use Chamilo\CoreBundle\Entity\TicketMessage;
use Chamilo\CoreBundle\Entity\TrackEAccess;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackECourseAccess;
use Chamilo\CoreBundle\Entity\TrackEDefault;
use Chamilo\CoreBundle\Entity\TrackEDownloads;
use Chamilo\CoreBundle\Entity\TrackEExercises;
use Chamilo\CoreBundle\Entity\TrackELastaccess;
use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\CoreBundle\Entity\TrackEOnline;
use Chamilo\CoreBundle\Entity\TrackEUploads;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserCourseCategory;
use Chamilo\CoreBundle\Entity\UserRelCourseVote;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CAttendanceResult;
use Chamilo\CourseBundle\Entity\CAttendanceSheet;
use Chamilo\CourseBundle\Entity\CBlogPost;
use Chamilo\CourseBundle\Entity\CDropboxFeedback;
use Chamilo\CourseBundle\Entity\CDropboxFile;
use Chamilo\CourseBundle\Entity\CDropboxPerson;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\CourseBundle\Entity\CNotebook;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationComment;
use Chamilo\CourseBundle\Entity\CSurveyAnswer;
use Chamilo\CourseBundle\Entity\CWiki;
use Datetime;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use SocialManager;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

class UserRepository extends ResourceRepository implements UserLoaderInterface, PasswordUpgraderInterface
{
    protected ?UserPasswordEncoderInterface $encoder = null;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function setEncoder(UserPasswordEncoderInterface $encoder): void
    {
        $this->encoder = $encoder;
    }

    public function loadUserByUsername(string $username): ?User
    {
        return $this->findOneBy([
            'username' => $username,
        ]);
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
        //UserPasswordEncoderInterface $passwordEncoder
        $password = (string) $user->getPlainPassword();
        if ('' !== $password) {
            $password = $this->encoder->encodePassword($user, $password);
            $user->setPassword($password);
            $user->eraseCredentials();
            // $encoder = $this->getEncoder($user);
            //$user->setPassword($encoder->encodePassword($password, $user->getSalt()));
            //$user->eraseCredentials();
        }
    }

    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        // this code is only an example; the exact code will depend on
        // your own application needs
        /** @var User $user */
        $user->setPassword($newEncodedPassword);
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
        $qb->where('r.creator = u');
        $qb->andWhere('r.parent IS NULL');
        $qb->getFirstResult();

        $rootUser = $qb->getQuery()->getSingleResult();

        if (null === $rootUser) {
            throw new UsernameNotFoundException('Root user not found');
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

        $resourceNode = new ResourceNode();
        $resourceNode
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

    public function findByUsername(string $username): ?User
    {
        $user = $this->findOneBy([
            'username' => $username,
        ]);

        if (null === $user) {
            throw new UsernameNotFoundException(sprintf("User with id '%s' not found.", $username));
        }

        return $user;
    }

    /**
     * @return User[]
     */
    public function findByRole(string $role)
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('u')
            ->from($this->_entityName, 'u')
            ->where('u.roles LIKE :roles')
            ->setParameter('roles', '%"'.$role.'"%', Types::STRING)
        ;

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
     * Get a filtered list of user by status and (optionally) access url.
     *
     * @param string $keyword     The query to filter
     * @param int    $status      The status
     * @param int    $accessUrlId The access URL ID
     *
     * @return User[]
     */
    public function findByStatus(string $keyword, int $status, int $accessUrlId = 0)
    {
        $qb = $this->createQueryBuilder('u');

        $this->addAccessUrlQueryBuilder($accessUrlId, $qb);
        $this->addStatusQueryBuilder($status, $qb);
        $this->addSearchByKeywordQueryBuilder($keyword, $qb);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the coaches for a course within a session.
     *
     * @param Session $session The session
     * @param Course  $course  The course
     */
    public function getCoachesForSessionCourse(Session $session, Course $course): Collection
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
     * Get the student bosses for a user.
     *
     * @return array
     */
    public function getStudentBosses(User $user)
    {
        $qb = $this->createQueryBuilder('u');
        $qb
            ->distinct()
            ->innerJoin(
                'ChamiloCoreBundle:UserRelUser',
                'uu',
                Join::WITH,
                'u.id = uu.friendUserId'
            )
            ->where(
                $qb->expr()->eq('uu.relationType', USER_RELATION_TYPE_BOSS)
            )
            ->andWhere(
                $qb->expr()->eq('uu.userId', $user->getId())
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
        return $this->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->innerJoin('a.portals', 'u')
            ->where('u.portal = :u')
            ->setParameters([
                'u' => $url,
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
        $qb = $this->createQueryBuilder('a');

        return $qb
            ->select('COUNT(a)')
            ->innerJoin('a.portals', 'u')
            ->where('u.portal = :u')
            ->andWhere($qb->expr()->in('a.roles', ['ROLE_TEACHER']))
            ->setParameters([
                'u' => $url,
            ])
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Find potential users to send a message.
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
        $this->addUserRelUserQueryBuilder($userId, USER_RELATION_TYPE_RRHH, $qb);

        return $qb->getQuery()->getResult();
    }

    /**
     * Serialize the whole entity to an array.
     *
     * @param array $substitutionTerms Substitute terms for some elements
     *
     * @return string
     */
    public function getPersonalDataToJson(int $userId, array $substitutionTerms)
    {
        $em = $this->getEntityManager();
        $dateFormat = Datetime::ATOM;

        /** @var User $user */
        $user = $this->find($userId);

        $user->setPassword($substitutionTerms['password']);
        $user->setSalt($substitutionTerms['salt']);

        $noDataLabel = $substitutionTerms['empty'];

        // Dummy content
        $user->setDateOfBirth(null);
        //$user->setBiography($noDataLabel);
        /*$user->setFacebookData($noDataLabel);
        $user->setFacebookName($noDataLabel);
        $user->setFacebookUid($noDataLabel);*/
        //$user->setImageName($noDataLabel);
        //$user->setTwoStepVerificationCode($noDataLabel);
        //$user->setGender($noDataLabel);
        /*$user->setGplusData($noDataLabel);
        $user->setGplusName($noDataLabel);
        $user->setGplusUid($noDataLabel);*/
        $user->setLocale($noDataLabel);
        $user->setTimezone($noDataLabel);
        /*$user->setTwitterData($noDataLabel);
        $user->setTwitterName($noDataLabel);
        $user->setTwitterUid($noDataLabel);*/
        $user->setWebsite($noDataLabel);
        //$user->setToken($noDataLabel);

        $friends = SocialManager::get_friends($userId);
        $friendList = [];
        if (!empty($friends)) {
            foreach ($friends as $friend) {
                $friendList[] = $friend['user_info']['complete_name'];
            }
        }

        $agenda = new Agenda('personal');
        $events = $agenda->getEvents(0, 0, 0, 0, $userId, 'array');
        $eventList = [];
        if (!empty($events)) {
            foreach ($events as $event) {
                $eventList[] = $event['title'].' '.$event['start_date_localtime'].' / '.$event['end_date_localtime'];
            }
        }

        // GradebookCertificate
        $result = $em->getRepository(GradebookCertificate::class)->findBy(['user' => $userId]);
        $gradebookCertificate = [];
        /** @var GradebookCertificate $item */
        foreach ($result as $item) {
            $createdAt = $item->getCreatedAt()->format($dateFormat);
            $list = [
                'Score: '.$item->getScoreCertificate(),
                'Path: '.$item->getPathCertificate(),
                'Created at: '.$createdAt,
            ];
            $gradebookCertificate[] = implode(', ', $list);
        }

        // TrackEExercises
        $criteria = [
            'exeUserId' => $userId,
        ];
        $result = $em->getRepository(TrackEExercises::class)->findBy($criteria);
        $trackEExercises = [];
        /** @var TrackEExercises $item */
        foreach ($result as $item) {
            $date = $item->getExeDate()->format($dateFormat);
            $list = [
                'IP: '.$item->getUserIp(),
                'Start: '.$date,
                'Status: '.$item->getStatus(),
                // 'Result: '.$item->getExeResult(),
                // 'Weighting: '.$item->getExeWeighting(),
            ];
            $trackEExercises[] = implode(', ', $list);
        }

        // TrackEAttempt
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(TrackEAttempt::class)->findBy($criteria);
        $trackEAttempt = [];
        /** @var TrackEAttempt $item */
        foreach ($result as $item) {
            $date = $item->getTms()->format($dateFormat);
            $list = [
                'Attempt #'.$item->getExeId(),
                'Course # '.$item->getCourse()->getCode(),
                //'Answer: '.$item->getAnswer(),
                'Session #'.$item->getSessionId(),
                //'Marks: '.$item->getMarks(),
                'Position: '.$item->getPosition(),
                'Date: '.$date,
            ];
            $trackEAttempt[] = implode(', ', $list);
        }

        // TrackECourseAccess
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(TrackECourseAccess::class)->findBy($criteria);
        $trackECourseAccessList = [];
        /** @var TrackECourseAccess $item */
        foreach ($result as $item) {
            $startDate = $item->getLoginCourseDate()->format($dateFormat);
            $endDate = null !== $item->getLogoutCourseDate() ? $item->getLogoutCourseDate()->format($dateFormat) : '';
            $list = [
                'IP: '.$item->getUserIp(),
                'Start: '.$startDate,
                'End: '.$endDate,
            ];
            $trackECourseAccessList[] = implode(', ', $list);
        }

        $checkEntities = [
            TrackELogin::class => 'loginUserId',
            TrackEAccess::class => 'accessUserId',
            TrackEOnline::class => 'loginUserId',
            TrackEDefault::class => 'defaultUserId',
            TrackELastaccess::class => 'accessUserId',
            TrackEUploads::class => 'uploadUserId',
            GradebookResult::class => 'user',
            TrackEDownloads::class => 'downUserId',
        ];

        $maxResults = 1000;
        $trackResults = [];
        foreach ($checkEntities as $entity => $field) {
            $qb = $em->createQueryBuilder();
            $qb->select($qb->expr()->count('l'))
                ->from($entity, 'l')
                ->where("l.$field = :login")
                ->setParameter('login', $userId)
            ;
            $query = $qb->getQuery();
            $count = $query->getSingleScalarResult();

            if ($count > $maxResults) {
                $qb = $em->getRepository($entity)->createQueryBuilder('l');
                $qb
                    ->select('l')
                    ->where("l.$field = :login")
                    ->setParameter('login', $userId)
                ;
                $qb
                    ->setFirstResult(0)
                    ->setMaxResults($maxResults)
                ;
                $result = $qb->getQuery()->getResult();
            } else {
                $criteria = [
                    $field => $userId,
                ];
                $result = $em->getRepository($entity)->findBy($criteria);
            }
            $trackResults[$entity] = $result;
        }

        $trackELoginList = [];
        /** @var TrackELogin $item */
        foreach ($trackResults[TrackELogin::class] as $item) {
            $startDate = $item->getLoginDate()->format($dateFormat);
            $endDate = null !== $item->getLogoutDate() ? $item->getLogoutDate()->format($dateFormat) : '';
            $list = [
                'IP: '.$item->getUserIp(),
                'Start: '.$startDate,
                'End: '.$endDate,
            ];
            $trackELoginList[] = implode(', ', $list);
        }

        // TrackEAccess
        $trackEAccessList = [];
        /** @var TrackEAccess $item */
        foreach ($trackResults[TrackEAccess::class] as $item) {
            $date = $item->getAccessDate()->format($dateFormat);
            $list = [
                'IP: '.$item->getUserIp(),
                'Tool: '.$item->getAccessTool(),
                'End: '.$date,
            ];
            $trackEAccessList[] = implode(', ', $list);
        }

        // TrackEOnline
        $trackEOnlineList = [];
        /** @var TrackEOnline $item */
        foreach ($trackResults[TrackEOnline::class] as $item) {
            $date = $item->getLoginDate()->format($dateFormat);
            $list = [
                'IP: '.$item->getUserIp(),
                'Login date: '.$date,
                'Course # '.$item->getCId(),
                'Session # '.$item->getSessionId(),
            ];
            $trackEOnlineList[] = implode(', ', $list);
        }

        // TrackEDefault
        $trackEDefault = [];
        /** @var TrackEDefault $item */
        foreach ($trackResults[TrackEDefault::class] as $item) {
            $date = $item->getDefaultDate()->format($dateFormat);
            $list = [
                'Type: '.$item->getDefaultEventType(),
                'Value: '.$item->getDefaultValue(),
                'Value type: '.$item->getDefaultValueType(),
                'Date: '.$date,
                'Course #'.$item->getCId(),
                'Session # '.$item->getSessionId(),
            ];
            $trackEDefault[] = implode(', ', $list);
        }

        // TrackELastaccess
        $trackELastaccess = [];
        /** @var TrackELastaccess $item */
        foreach ($trackResults[TrackELastaccess::class] as $item) {
            $date = $item->getAccessDate()->format($dateFormat);
            $list = [
                'Course #'.$item->getCId(),
                'Session # '.$item->getAccessSessionId(),
                'Tool: '.$item->getAccessTool(),
                'Access date: '.$date,
            ];
            $trackELastaccess[] = implode(', ', $list);
        }

        // TrackEUploads
        $trackEUploads = [];
        /** @var TrackEUploads $item */
        foreach ($trackResults[TrackEUploads::class] as $item) {
            $date = $item->getUploadDate()->format($dateFormat);
            $list = [
                'Course #'.$item->getCId(),
                'Uploaded at: '.$date,
                'Upload id # '.$item->getUploadId(),
            ];
            $trackEUploads[] = implode(', ', $list);
        }

        $gradebookResult = [];
        /** @var GradebookResult $item */
        foreach ($trackResults[GradebookResult::class] as $item) {
            $date = $item->getCreatedAt()->format($dateFormat);
            $list = [
                'Evaluation id# '.$item->getEvaluation()->getId(),
                //'Score: '.$item->getScore(),
                'Creation date: '.$date,
            ];
            $gradebookResult[] = implode(', ', $list);
        }

        $trackEDownloads = [];
        /** @var TrackEDownloads $item */
        foreach ($trackResults[TrackEDownloads::class] as $item) {
            $date = $item->getDownDate()->format($dateFormat);
            $list = [
                'File: '.$item->getDownDocPath(),
                'Download at: '.$date,
            ];
            $trackEDownloads[] = implode(', ', $list);
        }

        // UserCourseCategory
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(UserCourseCategory::class)->findBy($criteria);
        $userCourseCategory = [];
        /** @var UserCourseCategory $item */
        foreach ($result as $item) {
            $list = [
                'Title: '.$item->getTitle(),
            ];
            $userCourseCategory[] = implode(', ', $list);
        }

        // Forum
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(CForumPost::class)->findBy($criteria);
        $cForumPostList = [];
        /** @var CForumPost $item */
        foreach ($result as $item) {
            $date = $item->getPostDate()->format($dateFormat);
            $list = [
                'Title: '.$item->getPostTitle(),
                'Creation date: '.$date,
            ];
            $cForumPostList[] = implode(', ', $list);
        }

        // CForumThread
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(CForumThread::class)->findBy($criteria);
        $cForumThreadList = [];
        /** @var CForumThread $item */
        foreach ($result as $item) {
            $date = $item->getThreadDate()->format($dateFormat);
            $list = [
                'Title: '.$item->getThreadTitle(),
                'Creation date: '.$date,
            ];
            $cForumThreadList[] = implode(', ', $list);
        }

        // CForumAttachment
        /*$criteria = [
            'threadPosterId' => $userId,
        ];
        $result = $em->getRepository('ChamiloCourseBundle:CForumAttachment')->findBy($criteria);
        $cForumThreadList = [];
        * @var CForumThread $item
        foreach ($result as $item) {
            $list = [
                'Title: '.$item->getThreadTitle(),
                'Creation date: '.$item->getThreadDate()->format($dateFormat),
            ];
            $cForumThreadList[] = implode(', ', $list);
        }*/

        // cGroupRelUser
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(CGroupRelUser::class)->findBy($criteria);
        $cGroupRelUser = [];
        /** @var CGroupRelUser $item */
        foreach ($result as $item) {
            $list = [
                'Course # '.$item->getCId(),
                'Group #'.$item->getGroup()->getIid(),
                'Role: '.$item->getStatus(),
            ];
            $cGroupRelUser[] = implode(', ', $list);
        }

        // CAttendanceSheet
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(CAttendanceSheet::class)->findBy($criteria);
        $cAttendanceSheetList = [];
        /** @var CAttendanceSheet $item */
        foreach ($result as $item) {
            $list = [
                'Presence: '.$item->getPresence(),
                'Calendar id: '.$item->getAttendanceCalendar()->getIid(),
            ];
            $cAttendanceSheetList[] = implode(', ', $list);
        }

        // CBlogPost
        $criteria = [
            'authorId' => $userId,
        ];
        $result = $em->getRepository(CBlogPost::class)->findBy($criteria);
        $cBlog = [];
        /** @var CBlogPost $item */
        foreach ($result as $item) {
            $date = $item->getDateCreation()->format($dateFormat);
            $list = [
                'Title: '.$item->getTitle(),
                'Date: '.$date,
            ];
            $cBlog[] = implode(', ', $list);
        }

        // CAttendanceResult
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(CAttendanceResult::class)->findBy($criteria);
        $cAttendanceResult = [];
        /** @var CAttendanceResult $item */
        foreach ($result as $item) {
            $list = [
                'Score : '.$item->getScore(),
                'Calendar id: '.$item->getAttendance()->getIid(),
            ];
            $cAttendanceResult[] = implode(', ', $list);
        }

        // Message
        $criteria = [
            'userSender' => $userId,
        ];
        $result = $em->getRepository(Message::class)->findBy($criteria);
        $messageList = [];
        /** @var Message $item */
        foreach ($result as $item) {
            $date = $item->getSendDate()->format($dateFormat);
            $userName = '';
            if ($item->getUserReceiver()) {
                $userName = $item->getUserReceiver()->getUsername();
            }
            $list = [
                'Title: '.$item->getTitle(),
                'Sent date: '.$date,
                'To user: '.$userName,
                'Status'.$item->getMsgStatus(),
            ];
            $messageList[] = implode(', ', $list);
        }

        // CSurveyAnswer
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(CSurveyAnswer::class)->findBy($criteria);
        $cSurveyAnswer = [];
        /** @var CSurveyAnswer $item */
        foreach ($result as $item) {
            $list = [
                'Answer # '.$item->getIid(),
                'Value: '.$item->getValue(),
            ];
            $cSurveyAnswer[] = implode(', ', $list);
        }

        // CDropboxFile
        $criteria = [
            'uploaderId' => $userId,
        ];
        $result = $em->getRepository(CDropboxFile::class)->findBy($criteria);
        $cDropboxFile = [];
        /** @var CDropboxFile $item */
        foreach ($result as $item) {
            $date = $item->getUploadDate()->format($dateFormat);
            $list = [
                'Title: '.$item->getTitle(),
                'Uploaded date: '.$date,
                'File: '.$item->getFilename(),
            ];
            $cDropboxFile[] = implode(', ', $list);
        }

        // CDropboxPerson
        $criteria = [
            'userId' => $userId,
        ];
        $result = $em->getRepository(CDropboxPerson::class)->findBy($criteria);
        $cDropboxPerson = [];
        /** @var CDropboxPerson $item */
        foreach ($result as $item) {
            $list = [
                'File #'.$item->getFileId(),
                'Course #'.$item->getCId(),
            ];
            $cDropboxPerson[] = implode(', ', $list);
        }

        // CDropboxPerson
        $criteria = [
            'authorUserId' => $userId,
        ];
        $result = $em->getRepository(CDropboxFeedback::class)->findBy($criteria);
        $cDropboxFeedback = [];
        /** @var CDropboxFeedback $item */
        foreach ($result as $item) {
            $date = $item->getFeedbackDate()->format($dateFormat);
            $list = [
                'File #'.$item->getFileId(),
                'Feedback: '.$item->getFeedback(),
                'Date: '.$date,
            ];
            $cDropboxFeedback[] = implode(', ', $list);
        }

        // CNotebook
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(CNotebook::class)->findBy($criteria);
        $cNotebook = [];
        /** @var CNotebook $item */
        foreach ($result as $item) {
            $date = $item->getUpdateDate()->format($dateFormat);
            $list = [
                'Title: '.$item->getTitle(),
                'Date: '.$date,
            ];
            $cNotebook[] = implode(', ', $list);
        }

        // CLpView
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(CLpView::class)->findBy($criteria);
        $cLpView = [];
        /** @var CLpView $item */
        foreach ($result as $item) {
            $list = [
                //'Id #'.$item->getId(),
                'LP #'.$item->getLp()->getIid(),
                'Progress: '.$item->getProgress(),
                //'Course #'.$item->getCId(),
                //'Session #'.$item->getSessionId(),
            ];
            $cLpView[] = implode(', ', $list);
        }

        // CStudentPublication
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(CStudentPublication::class)->findBy($criteria);
        $cStudentPublication = [];
        /** @var CStudentPublication $item */
        foreach ($result as $item) {
            $list = [
                'Title: '.$item->getTitle(),
                //'URL: '.$item->getTitle(),
            ];
            $cStudentPublication[] = implode(', ', $list);
        }

        // CStudentPublicationComment
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(CStudentPublicationComment::class)->findBy($criteria);
        $cStudentPublicationComment = [];
        /** @var CStudentPublicationComment $item */
        foreach ($result as $item) {
            $date = $item->getSentAt()->format($dateFormat);
            $list = [
                'Commment: '.$item->getComment(),
                'File '.$item->getFile(),
                //'Course # '.$item->getCId(),
                'Date: '.$date,
            ];
            $cStudentPublicationComment[] = implode(', ', $list);
        }

        // CWiki
        $criteria = [
            'userId' => $userId,
        ];
        $result = $em->getRepository(CWiki::class)->findBy($criteria);
        $cWiki = [];
        /** @var CWiki $item */
        foreach ($result as $item) {
            $list = [
                'Title: '.$item->getTitle(),
                'Progress: '.$item->getProgress(),
                'IP: '.$item->getUserIp(),
            ];
            $cWiki[] = implode(', ', $list);
        }

        // Ticket
        $criteria = [
            'insertUserId' => $userId,
        ];
        $result = $em->getRepository(Ticket::class)->findBy($criteria);
        $ticket = [];
        /** @var Ticket $item */
        foreach ($result as $item) {
            $list = [
                'Code: '.$item->getCode(),
                'Subject: '.$item->getSubject(),
            ];
            $ticket[] = implode(', ', $list);
        }

        // Message
        $criteria = [
            'insertUserId' => $userId,
        ];
        $result = $em->getRepository(TicketMessage::class)->findBy($criteria);
        $ticketMessage = [];
        /** @var TicketMessage $item */
        foreach ($result as $item) {
            $date = $item->getInsertDateTime()->format($dateFormat);
            $list = [
                'Subject: '.$item->getSubject(),
                'IP: '.$item->getIpAddress(),
                'Status: '.$item->getStatus(),
                'Creation date: '.$date,
            ];
            $ticketMessage[] = implode(', ', $list);
        }

        // SkillRelUserComment
        $criteria = [
            'feedbackGiver' => $userId,
        ];
        $result = $em->getRepository(SkillRelUserComment::class)->findBy($criteria);
        $skillRelUserComment = [];
        /** @var SkillRelUserComment $item */
        foreach ($result as $item) {
            $date = $item->getFeedbackDateTime()->format($dateFormat);
            $list = [
                'Feedback: '.$item->getFeedbackText(),
                'Value: '.$item->getFeedbackValue(),
                'Created at: '.$date,
            ];
            $skillRelUserComment[] = implode(', ', $list);
        }

        // UserRelCourseVote
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(UserRelCourseVote::class)->findBy($criteria);
        $userRelCourseVote = [];
        /** @var UserRelCourseVote $item */
        foreach ($result as $item) {
            $list = [
                'Course #'.$item->getCourse()->getId(),
                //'Session #'.$item->getSession()->getId(),
                'Vote: '.$item->getVote(),
            ];
            $userRelCourseVote[] = implode(', ', $list);
        }

        /*$user->setDropBoxSentFiles(
            [
                'Friends' => $friendList,
                'Events' => $eventList,
                'GradebookCertificate' => $gradebookCertificate,

                'TrackECourseAccess' => $trackECourseAccessList,
                'TrackELogin' => $trackELoginList,
                'TrackEAccess' => $trackEAccessList,
                'TrackEDefault' => $trackEDefault,
                'TrackEOnline' => $trackEOnlineList,
                'TrackEUploads' => $trackEUploads,
                'TrackELastaccess' => $trackELastaccess,
                'GradebookResult' => $gradebookResult,
                'Downloads' => $trackEDownloads,
                'UserCourseCategory' => $userCourseCategory,
                'SkillRelUserComment' => $skillRelUserComment,
                'UserRelCourseVote' => $userRelCourseVote,

                // courses
                'AttendanceResult' => $cAttendanceResult,
                'Blog' => $cBlog,
                'DocumentsAdded' => $documents,
                'Chat' => $chatFiles,
                'ForumPost' => $cForumPostList,
                'ForumThread' => $cForumThreadList,
                'TrackEExercises' => $trackEExercises,
                'TrackEAttempt' => $trackEAttempt,

                'GroupRelUser' => $cGroupRelUser,
                'Message' => $messageList,
                'Survey' => $cSurveyAnswer,
                'StudentPublication' => $cStudentPublication,
                'StudentPublicationComment' => $cStudentPublicationComment,
                'DropboxFile' => $cDropboxFile,
                'DropboxPerson' => $cDropboxPerson,
                'DropboxFeedback' => $cDropboxFeedback,

                'LpView' => $cLpView,
                'Notebook' => $cNotebook,

                'Wiki' => $cWiki,
                // Tickets

                'Ticket' => $ticket,
                'TicketMessage' => $ticketMessage,
            ]
        );*/

        //$user->setDropBoxReceivedFiles([]);
        //$user->setGroups([]);
        //$user->setCurriculumItems([]);

        /*$portals = $user->getPortals();
        if (!empty($portals)) {
            $list = [];
            /** @var AccessUrlRelUser $portal */
        /*foreach ($portals as $portal) {
            $portalInfo = UrlManager::get_url_data_from_id($portal->getUrl()->getId());
            $list[] = $portalInfo['url'];
        }
        }
        $user->setPortals($list);*/

        /*$skillRelUserList = $user->getAchievedSkills();
        $list = [];
        foreach ($skillRelUserList as $skillRelUser) {
            $list[] = $skillRelUser->getSkill()->getName();
        }
        $user->setAchievedSkills($list);
        $user->setCommentedUserSkills([]);*/

        //$extraFieldValues = new \ExtraFieldValue('user');

        $lastLogin = $user->getLastLogin();
        if (empty($lastLogin)) {
            $login = $this->getLastLogin($user);
            if (null !== $login) {
                $lastLogin = $login->getLoginDate();
            }
        }
        $user->setLastLogin($lastLogin);

        /*$dateNormalizer = new GetSetMethodNormalizer();
        $dateNormalizer->setCircularReferenceHandler(function ($object) {
            return get_class($object);
        });*/

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
            'salt',
            'dropBoxSentFiles',
            'dropBoxReceivedFiles',
            'currentUrl',
            'uuid',
            'curriculumItems',
            'currentSession',
            'currentCourse',
            'resourceNode'
        ];

        $callback = function ($dateTime) {
            return $dateTime instanceof DateTime ? $dateTime->format(DateTime::ATOM) : '';
        };

        $defaultContext = [
            AbstractNormalizer::CALLBACKS => [
                'createdAt' => $callback,
                'lastLogin' => $callback,
                'registrationDate' => $callback,
                'memberSince' => $callback,
            ],
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return get_class($object);
            },
        ];

        $normalizer = new GetSetMethodNormalizer(null, null, null, null, null, $defaultContext);
        $serializer = new Serializer(
            [$normalizer],
            [new JsonEncoder()]
        );

        return $serializer->serialize($user, 'json', [
            AbstractNormalizer::IGNORED_ATTRIBUTES => $ignore,
        ]);
    }

    /**
     * Get the last login from the track_e_login table.
     * This might be different from user.last_login in the case of legacy users
     * as user.last_login was only implemented in 1.10 version with a default
     * value of NULL (not the last record from track_e_login).
     *
     * @throws Exception
     *
     * @return null|TrackELogin
     */
    public function getLastLogin(User $user)
    {
        $repo = $this->getEntityManager()->getRepository(TrackELogin::class);
        $qb = $repo->createQueryBuilder('u');

        return $qb
            ->select('u')
            ->where(
                $qb->expr()->eq('u.loginUserId', $user->getId())
            )
            ->setMaxResults(1)
            ->orderBy('u.loginDate', Criteria::DESC)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function addSearchByKeywordQueryBuilder($keyword, QueryBuilder $qb = null): QueryBuilder
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
        $qb->leftJoin('u.userRelUsers', 'relations');
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
            ->leftJoin('u.userRelUsers', 'relations')
            ->andWhere(
                $qb->expr()->notIn('relations.relationType', [USER_RELATION_TYPE_DELETED, USER_RELATION_TYPE_RRHH])
            )
            ->andWhere('relations.user = :user AND relations.friend <> :user')
            ->setParameter('user', $userId, Types::INTEGER)
        ;

        return $qb;
    }

    private function addAccessUrlQueryBuilder(int $accessUrlId, QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'u');
        $qb
            ->innerJoin('u.portals', 'p')
            ->andWhere('p.url = :url')
            ->setParameter('url', $accessUrlId, Types::INTEGER)
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

    private function addActiveAndNotAnonUserQueryBuilder(QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'u');
        $qb
            ->andWhere('u.active = 1')
            ->andWhere('u.status <> :status')
            ->setParameter('status', User::ANONYMOUS, Types::INTEGER)
        ;

        return $qb;
    }

    private function addStatusQueryBuilder(int $status, QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'u');
        $qb
            ->andWhere('u.status = :status')
            ->setParameter('status', $status, Types::INTEGER)
        ;

        return $qb;
    }
}

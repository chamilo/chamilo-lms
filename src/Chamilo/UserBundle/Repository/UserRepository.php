<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Repository;

use Chamilo\CoreBundle\Entity\AccessUrlRelUser;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\GradebookResult;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Entity\SkillRelUserComment;
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
use Chamilo\CoreBundle\Entity\UserApiKey;
use Chamilo\CoreBundle\Entity\UserCourseCategory;
use Chamilo\CoreBundle\Entity\UserRelCourseVote;
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
use Chamilo\TicketBundle\Entity\Ticket;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Class UserRepository.
 *
 * All functions that query the database (selects)
 * Functions should return query builders.
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
                        LEFT JOIN ChamiloCoreBundle:AccessUrlRelUser R
                        WITH U = R.user
			INNER JOIN ChamiloCoreBundle:TrackEOnline T
                        WITH U.id = T.loginUserId
			WHERE
                          R.portal = $accessUrlId AND
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
     * @return string
     */
    public function getPersonalDataToJson($userId, array $substitutionTerms)
    {
        $em = $this->getEntityManager();
        $dateFormat = \Datetime::ATOM;

        /** @var User $user */
        $dbUser = $this->find($userId);

        $user = new User();
        $user->setUserId($userId);
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

        $user->setFirstname($dbUser->getFirstname());
        $user->setLastname($dbUser->getLastname());
        $user->setAuthSource($dbUser->getAuthSource());
        $user->setEmail($dbUser->getEmail());
        $user->setStatus($dbUser->getStatus());
        $user->setOfficialCode($dbUser->getOfficialCode());
        $user->setPhone($dbUser->getPhone());
        $user->setAddress($dbUser->getAddress());
        $user->setPictureUri($dbUser->getPictureUri());
        $user->setCreatorId($dbUser->getCreatorId());
        $user->setCompetences($dbUser->getCompetences());
        $user->setDiplomas($dbUser->getDiplomas());
        $user->setOpenarea($dbUser->getOpenarea());
        $user->setTeach($dbUser->getTeach());
        $user->setProductions($dbUser->getProductions());
        $user->setLanguage($dbUser->getLanguage());
        $user->setRegistrationDate($dbUser->getRegistrationDate());
        $user->setExpirationDate($dbUser->getExpirationDate());
        $user->setActive($dbUser->getActive());
        $user->setOpenid($dbUser->getOpenid());
        $user->setTheme($dbUser->getTheme());
        $user->setHrDeptId($dbUser->getHrDeptId());
        $user->setSlug($dbUser->getSlug());
        $user->setLastLogin($dbUser->getLastLogin());
        //$user->setExtraFieldList($dbUser->getExtraFields());
        $user->setUsername($dbUser->getUsername());
        $user->setPasswordRequestedAt($dbUser->getPasswordRequestedAt());
        $user->setCreatorId($dbUser->getCreatorId());
        $user->setUpdatedAt($dbUser->getUpdatedAt());

        if ($dbUser->getExpiresAt()) {
            $user->setExpiresAt($dbUser->getExpiresAt());
        }

        $user->setExpirationDate($dbUser->getExpirationDate());
        $user->setCredentialsExpireAt($dbUser->getCredentialsExpireAt());
        //$user->setBiography($dbUser->getBiography());
        //$user->setDateOfBirth($dbUser->getDateOfBirth());
        //$user->setGender($dbUser->getGender());
        //$user->setLocale($dbUser->getLocale());
        //$user->setTimezone($dbUser->getTimezone());
        //$user->setWebsite($dbUser->getWebsite());
        $user->setUsernameCanonical($dbUser->getUsernameCanonical());
        $user->setEmailCanonical($dbUser->getEmailCanonical());
        $user->setRoles($dbUser->getRoles());
        $user->setLocked($dbUser->getLocked());
        $user->setProfileCompleted($dbUser->isProfileCompleted());

        $courses = $dbUser->getCourses();
        $list = [];
        $chatFiles = [];
        foreach ($courses as $course) {
            $list[] = $course->getCourse()->getCode();
            $course->getCourse()->setToolList(null);
            $courseDir = api_get_path(SYS_COURSE_PATH).$course->getCourse()->getDirectory();
            $documentDir = $courseDir.'/document/chat_files/';
            if (is_dir($documentDir)) {
                $fs = new Finder();
                $fs->files()->in($documentDir);
                foreach ($fs as $file) {
                    $chatFiles[] =
                        $course->getCourse()->getDirectory().'/document/chat_files/'.$file->getFilename().' - '.
                        get_lang('ContentNotAccessibleRequestFromDataPrivacyOfficer');
                }
            }
        }

        $user->setCourses($list);

        $classes = $dbUser->getClasses();
        $list = [];
        foreach ($classes as $class) {
            $name = $class->getUsergroup()->getName();
            $list[$class->getUsergroup()->getGroupType()][] = $name.' - Status: '.$class->getRelationType();
        }
        $user->setClasses($list);

        $collection = $dbUser->getSessionCourseSubscriptions();
        $list = [];
        foreach ($collection as $item) {
            $list[$item->getSession()->getName()][] = $item->getCourse()->getCode();
        }
        $user->setSessionCourseSubscriptions($list);

        $documents = \DocumentManager::getAllDocumentsCreatedByUser($userId);
        $friends = \SocialManager::get_friends($userId);
        $friendList = [];
        if (!empty($friends)) {
            foreach ($friends as $friend) {
                $friendList[] = $friend['user_info']['complete_name'];
            }
        }

        $agenda = new \Agenda('personal');
        $events = $agenda->getEvents('', '', null, null, $userId, 'array');
        $eventList = [];
        if (!empty($events)) {
            foreach ($events as $event) {
                $eventList[] = $event['title'].' '.$event['start_date_localtime'].' / '.$event['end_date_localtime'];
            }
        }

        // GradebookCertificate
        $criteria = [
            'userId' => $userId,
        ];
        $result = $em->getRepository('ChamiloCoreBundle:GradebookCertificate')->findBy($criteria);
        $gradebookCertificate = [];
        /** @var GradebookCertificate $item */
        foreach ($result as $item) {
            $createdAt = $item->getCreatedAt() ? $item->getCreatedAt()->format($dateFormat) : '';
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
        $result = $em->getRepository('ChamiloCoreBundle:TrackEExercises')->findBy($criteria);
        $trackEExercises = [];
        /** @var TrackEExercises $item */
        foreach ($result as $item) {
            $date = $item->getExeDate() ? $item->getExeDate()->format($dateFormat) : '';
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
            'userId' => $userId,
        ];
        $result = $em->getRepository('ChamiloCoreBundle:TrackEAttempt')->findBy($criteria);
        $trackEAttempt = [];
        /** @var TrackEAttempt $item */
        foreach ($result as $item) {
            $date = $item->getTms() ? $item->getTms()->format($dateFormat) : '';
            $list = [
                'Attempt #'.$item->getExeId(),
                'Course # '.$item->getCId(),
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
            'userId' => $userId,
        ];
        $result = $em->getRepository('ChamiloCoreBundle:TrackECourseAccess')->findBy($criteria);
        $trackECourseAccessList = [];
        /** @var TrackECourseAccess $item */
        foreach ($result as $item) {
            $startDate = $item->getLoginCourseDate() ? $item->getLoginCourseDate()->format($dateFormat) : '';
            $endDate = $item->getLogoutCourseDate() ? $item->getLogoutCourseDate()->format($dateFormat) : '';
            $list = [
                'IP: '.$item->getUserIp(),
                'Start: '.$startDate,
                'End: '.$endDate,
            ];
            $trackECourseAccessList[] = implode(', ', $list);
        }

        $checkEntities = [
            'ChamiloCoreBundle:TrackELogin' => 'loginUserId',
            'ChamiloCoreBundle:TrackEAccess' => 'accessUserId',
            'ChamiloCoreBundle:TrackEOnline' => 'loginUserId',
            'ChamiloCoreBundle:TrackEDefault' => 'defaultUserId',
            'ChamiloCoreBundle:TrackELastaccess' => 'accessUserId',
            'ChamiloCoreBundle:TrackEUploads' => 'uploadUserId',
            'ChamiloCoreBundle:GradebookResult' => 'userId',
            'ChamiloCoreBundle:TrackEDownloads' => 'downUserId',
        ];

        $maxResults = 1000;
        $trackResults = [];
        foreach ($checkEntities as $entity => $field) {
            $qb = $em->createQueryBuilder();
            $qb->select($qb->expr()->count('l'))
                ->from($entity, 'l')
                ->where("l.$field = :login")
                ->setParameter('login', $userId);
            $query = $qb->getQuery();
            $count = $query->getSingleScalarResult();

            if ($count > $maxResults) {
                $qb = $em->getRepository($entity)->createQueryBuilder('l');
                $qb
                    ->select('l')
                    ->where("l.$field = :login")
                    ->setParameter('login', $userId);
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
        foreach ($trackResults['ChamiloCoreBundle:TrackELogin'] as $item) {
            $startDate = $item->getLoginDate() ? $item->getLoginDate()->format($dateFormat) : '';
            $endDate = $item->getLogoutDate() ? $item->getLogoutDate()->format($dateFormat) : '';
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
        foreach ($trackResults['ChamiloCoreBundle:TrackEAccess'] as $item) {
            $date = $item->getAccessDate() ? $item->getAccessDate()->format($dateFormat) : '';
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
        foreach ($trackResults['ChamiloCoreBundle:TrackEOnline'] as $item) {
            $date = $item->getLoginDate() ? $item->getLoginDate()->format($dateFormat) : '';
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
        foreach ($trackResults['ChamiloCoreBundle:TrackEDefault'] as $item) {
            $date = $item->getDefaultDate() ? $item->getDefaultDate()->format($dateFormat) : '';
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
        foreach ($trackResults['ChamiloCoreBundle:TrackELastaccess'] as $item) {
            $date = $item->getAccessDate() ? $item->getAccessDate()->format($dateFormat) : '';
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
        foreach ($trackResults['ChamiloCoreBundle:TrackEUploads'] as $item) {
            $date = $item->getUploadDate() ? $item->getUploadDate()->format($dateFormat) : '';
            $list = [
                'Course #'.$item->getCId(),
                'Uploaded at: '.$date,
                'Upload id # '.$item->getUploadId(),
            ];
            $trackEUploads[] = implode(', ', $list);
        }

        $gradebookResult = [];
        /** @var GradebookResult $item */
        foreach ($trackResults['ChamiloCoreBundle:GradebookResult'] as $item) {
            $date = $item->getCreatedAt() ? $item->getCreatedAt()->format($dateFormat) : '';
            $list = [
                'Evaluation id# '.$item->getEvaluationId(),
                //'Score: '.$item->getScore(),
                'Creation date: '.$date,
            ];
            $gradebookResult[] = implode(', ', $list);
        }

        $trackEDownloads = [];
        /** @var TrackEDownloads $item */
        foreach ($trackResults['ChamiloCoreBundle:TrackEDownloads'] as $item) {
            $date = $item->getDownDate() ? $item->getDownDate()->format($dateFormat) : '';
            $list = [
                'File: '.$item->getDownDocPath(),
                'Download at: '.$date,
            ];
            $trackEDownloads[] = implode(', ', $list);
        }

        // UserCourseCategory
        $criteria = [
            'userId' => $userId,
        ];
        $result = $em->getRepository('ChamiloCoreBundle:UserCourseCategory')->findBy($criteria);
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
            'posterId' => $userId,
        ];
        $result = $em->getRepository('ChamiloCourseBundle:CForumPost')->findBy($criteria);
        $cForumPostList = [];
        /** @var CForumPost $item */
        foreach ($result as $item) {
            $date = $item->getPostDate() ? $item->getPostDate()->format($dateFormat) : '';
            $list = [
                'Title: '.$item->getPostTitle(),
                'Creation date: '.$date,
            ];
            $cForumPostList[] = implode(', ', $list);
        }

        // CForumThread
        $criteria = [
            'threadPosterId' => $userId,
        ];
        $result = $em->getRepository('ChamiloCourseBundle:CForumThread')->findBy($criteria);
        $cForumThreadList = [];
        /** @var CForumThread $item */
        foreach ($result as $item) {
            $date = $item->getThreadDate() ? $item->getThreadDate()->format($dateFormat) : '';
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
            'userId' => $userId,
        ];
        $result = $em->getRepository('ChamiloCourseBundle:CGroupRelUser')->findBy($criteria);
        $cGroupRelUser = [];
        /** @var CGroupRelUser $item */
        foreach ($result as $item) {
            $list = [
                'Course # '.$item->getCId(),
                'Group #'.$item->getGroupId(),
                'Role: '.$item->getStatus(),
            ];
            $cGroupRelUser[] = implode(', ', $list);
        }

        // CAttendanceSheet
        $criteria = [
            'userId' => $userId,
        ];
        $result = $em->getRepository('ChamiloCourseBundle:CAttendanceSheet')->findBy($criteria);
        $cAttendanceSheetList = [];
        /** @var CAttendanceSheet $item */
        foreach ($result as $item) {
            $list = [
                'Presence: '.$item->getPresence(),
                'Calendar id: '.$item->getAttendanceCalendarId(),
            ];
            $cAttendanceSheetList[] = implode(', ', $list);
        }

        // CBlogPost
        $criteria = [
            'authorId' => $userId,
        ];
        $result = $em->getRepository('ChamiloCourseBundle:CBlogPost')->findBy($criteria);
        $cBlog = [];
        /** @var CBlogPost $item */
        foreach ($result as $item) {
            $date = $item->getDateCreation() ? $item->getDateCreation()->format($dateFormat) : '';
            $list = [
                'Title: '.$item->getTitle(),
                'Date: '.$date,
            ];
            $cBlog[] = implode(', ', $list);
        }

        // CAttendanceResult
        $criteria = [
            'userId' => $userId,
        ];
        $result = $em->getRepository('ChamiloCourseBundle:CAttendanceResult')->findBy($criteria);
        $cAttendanceResult = [];
        /** @var CAttendanceResult $item */
        foreach ($result as $item) {
            $list = [
                'Score : '.$item->getScore(),
                'Calendar id: '.$item->getAttendanceId(),
            ];
            $cAttendanceResult[] = implode(', ', $list);
        }

        // Message
        $criteria = [
            'userSenderId' => $userId,
        ];
        $result = $em->getRepository('ChamiloCoreBundle:Message')->findBy($criteria);
        $messageList = [];
        /** @var Message $item */
        foreach ($result as $item) {
            $date = $item->getSendDate() ? $item->getSendDate()->format($dateFormat) : '';
            $list = [
                'Title: '.$item->getTitle(),
                'Sent date: '.$date,
                'To user # '.$item->getUserReceiverId(),
                'Status'.$item->getMsgStatus(),
            ];
            $messageList[] = implode(', ', $list);
        }

        // CSurveyAnswer
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository('ChamiloCourseBundle:CSurveyAnswer')->findBy($criteria);
        $cSurveyAnswer = [];
        /** @var CSurveyAnswer $item */
        foreach ($result as $item) {
            $list = [
                'Answer # '.$item->getAnswerId(),
                'Value: '.$item->getValue(),
            ];
            $cSurveyAnswer[] = implode(', ', $list);
        }

        // CDropboxFile
        $criteria = [
            'uploaderId' => $userId,
        ];
        $result = $em->getRepository('ChamiloCourseBundle:CDropboxFile')->findBy($criteria);
        $cDropboxFile = [];
        /** @var CDropboxFile $item */
        foreach ($result as $item) {
            $date = $item->getUploadDate() ? $item->getUploadDate()->format($dateFormat) : '';
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
        $result = $em->getRepository('ChamiloCourseBundle:CDropboxPerson')->findBy($criteria);
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
        $result = $em->getRepository('ChamiloCourseBundle:CDropboxFeedback')->findBy($criteria);
        $cDropboxFeedback = [];
        /** @var CDropboxFeedback $item */
        foreach ($result as $item) {
            $date = $item->getFeedbackDate() ? $item->getFeedbackDate()->format($dateFormat) : '';
            $list = [
                'File #'.$item->getFileId(),
                'Feedback: '.$item->getFeedback(),
                'Date: '.$date,
            ];
            $cDropboxFeedback[] = implode(', ', $list);
        }

        // CNotebook
        $criteria = [
            'userId' => $userId,
        ];
        $result = $em->getRepository('ChamiloCourseBundle:CNotebook')->findBy($criteria);
        $cNotebook = [];
        /** @var CNotebook $item */
        foreach ($result as $item) {
            $date = $item->getUpdateDate() ? $item->getUpdateDate()->format($dateFormat) : '';
            $list = [
                'Title: '.$item->getTitle(),
                'Date: '.$date,
            ];
            $cNotebook[] = implode(', ', $list);
        }

        // CLpView
        $criteria = [
            'userId' => $userId,
        ];
        $result = $em->getRepository('ChamiloCourseBundle:CLpView')->findBy($criteria);
        $cLpView = [];
        /** @var CLpView $item */
        foreach ($result as $item) {
            $list = [
                //'Id #'.$item->getId(),
                'LP #'.$item->getLpId(),
                'Progress: '.$item->getProgress(),
                'Course #'.$item->getCId(),
                'Session #'.$item->getSessionId(),
            ];
            $cLpView[] = implode(', ', $list);
        }

        // CStudentPublication
        $criteria = [
            'userId' => $userId,
        ];
        $result = $em->getRepository('ChamiloCourseBundle:CStudentPublication')->findBy($criteria);
        $cStudentPublication = [];
        /** @var CStudentPublication $item */
        foreach ($result as $item) {
            $list = [
                'Title: '.$item->getTitle(),
                'URL: '.$item->getUrl(),
            ];
            $cStudentPublication[] = implode(', ', $list);
        }

        // CStudentPublicationComment
        $criteria = [
            'userId' => $userId,
        ];
        $result = $em->getRepository('ChamiloCourseBundle:CStudentPublicationComment')->findBy($criteria);
        $cStudentPublicationComment = [];
        /** @var CStudentPublicationComment $item */
        foreach ($result as $item) {
            $date = $item->getSentAt() ? $item->getSentAt()->format($dateFormat) : '';
            $list = [
                'Commment: '.$item->getComment(),
                'File '.$item->getFile(),
                'Course # '.$item->getCId(),
                'Date: '.$date,
            ];
            $cStudentPublicationComment[] = implode(', ', $list);
        }

        // CWiki
        $criteria = [
            'userId' => $userId,
        ];
        $result = $em->getRepository('ChamiloCourseBundle:CWiki')->findBy($criteria);
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
        $result = $em->getRepository('ChamiloTicketBundle:Ticket')->findBy($criteria);
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
        $result = $em->getRepository('ChamiloTicketBundle:Message')->findBy($criteria);
        $ticketMessage = [];
        /** @var \Chamilo\TicketBundle\Entity\Message $item */
        foreach ($result as $item) {
            $date = $item->getInsertDateTime() ? $item->getInsertDateTime()->format($dateFormat) : '';
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
        $result = $em->getRepository('ChamiloCoreBundle:SkillRelUserComment')->findBy($criteria);
        $skillRelUserComment = [];
        /** @var SkillRelUserComment $item */
        foreach ($result as $item) {
            $date = $item->getFeedbackDateTime() ? $item->getFeedbackDateTime()->format($dateFormat) : '';
            $list = [
                'Feedback: '.$item->getFeedbackText(),
                'Value: '.$item->getFeedbackValue(),
                'Created at: '.$date,
            ];
            $skillRelUserComment[] = implode(', ', $list);
        }

        // UserRelCourseVote
        $criteria = [
            'userId' => $userId,
        ];
        $result = $em->getRepository('ChamiloCoreBundle:UserRelCourseVote')->findBy($criteria);
        $userRelCourseVote = [];
        /** @var UserRelCourseVote $item */
        foreach ($result as $item) {
            $list = [
                'Course #'.$item->getCId(),
                'Session #'.$item->getSessionId(),
                'Vote: '.$item->getVote(),
            ];
            $userRelCourseVote[] = implode(', ', $list);
        }

        // UserApiKey
        $criteria = [
            'userId' => $userId,
        ];
        $result = $em->getRepository('ChamiloCoreBundle:UserApiKey')->findBy($criteria);
        $userApiKey = [];
        /** @var UserApiKey $item */
        foreach ($result as $item) {
            $validityStart = $item->getValidityStartDate() ? $item->getValidityStartDate()->format($dateFormat) : '';
            $validityEnd = $item->getValidityEndDate() ? $item->getValidityEndDate()->format($dateFormat) : '';
            $created = $item->getCreatedDate() ? $item->getCreatedDate()->format($dateFormat) : '';

            $list = [
                'ApiKey #'.$item->getApiKey(),
                'Service: '.$item->getApiService(),
                'EndPoint: '.$item->getApiEndPoint(),
                'Validity start date: '.$validityStart,
                'Validity enddate: '.$validityEnd,
                'Created at: '.$created,
            ];
            $userApiKey[] = implode(', ', $list);
        }

        $user->setDropBoxSentFiles(
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
                'UserApiKey' => $userApiKey,

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
        );

        $user->setDropBoxReceivedFiles([]);
        $user->setCurriculumItems([]);

        $portals = $dbUser->getPortals();
        if (!empty($portals)) {
            $list = [];
            /** @var AccessUrlRelUser $portal */
            foreach ($portals as $portal) {
                $portalInfo = \UrlManager::get_url_data_from_id($portal->getAccessUrlId());
                $list[] = $portalInfo['url'];
            }
        }
        $user->setPortals($list);

        $coachList = $dbUser->getSessionAsGeneralCoach();
        $list = [];
        /** @var Session $session */
        foreach ($coachList as $session) {
            $list[] = $session->getName();
        }
        $user->setSessionAsGeneralCoach($list);

        $skillRelUserList = $dbUser->getAchievedSkills();
        $list = [];
        /** @var SkillRelUser $skillRelUser */
        foreach ($skillRelUserList as $skillRelUser) {
            $list[] = $skillRelUser->getSkill()->getName();
        }
        $user->setAchievedSkills($list);
        $user->setCommentedUserSkills([]);

        $extraFieldValues = new \ExtraFieldValue('user');
        $items = $extraFieldValues->getAllValuesByItem($userId);
        $user->setExtraFields($items);

        $lastLogin = $dbUser->getLastLogin();
        if (empty($lastLogin)) {
            $login = $this->getLastLogin($dbUser);
            if ($login) {
                $lastLogin = $login->getLoginDate();
            }
        }

        if (!empty($lastLogin)) {
            $user->setLastLogin($lastLogin);
        }

        $dateNormalizer = new GetSetMethodNormalizer();
        $dateNormalizer->setCircularReferenceHandler(function ($object) {
            return get_class($object);
        });

        $ignore = [
            'id',
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
        ];

        $dateNormalizer->setIgnoredAttributes($ignore);

        $callback = function ($dateTime) {
            return $dateTime instanceof \DateTime ? $dateTime->format(\DateTime::ISO8601) : '';
        };

        $dateNormalizer->setCallbacks(
            [
                'createdAt' => $callback,
                'lastLogin' => $callback,
                'registrationDate' => $callback,
                'memberSince' => $callback,
            ]
        );

        $serializer = new Serializer([$dateNormalizer], [new JsonEncoder()]);

        return $serializer->serialize($user, 'json');
    }

    /**
     * Get the last login from the track_e_login table.
     * This might be different from user.last_login in the case of legacy users
     * as user.last_login was only implemented in 1.10 version with a default
     * value of NULL (not the last record from track_e_login).
     *
     * @throws \Exception
     *
     * @return TrackELogin|null
     */
    public function getLastLogin(User $user)
    {
        $repo = $this->getEntityManager()->getRepository('ChamiloCoreBundle:TrackELogin');
        $qb = $repo->createQueryBuilder('l');

        return $qb
            ->select('l')
            ->where(
                $qb->expr()->eq('l.loginUserId', $user->getId())
            )
            ->setMaxResults(1)
            ->orderBy('l.loginDate', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }
}

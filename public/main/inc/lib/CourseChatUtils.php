<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CChatConnected;
use Chamilo\CourseBundle\Entity\CChatConversation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Michelf\MarkdownExtra;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class CourseChat
 * Manage the chat for a course.
 */
class CourseChatUtils
{
    private $groupId;
    private $courseId;
    private $sessionId;
    private $userId;
    private $resourceNode;

    /**
     * CourseChat constructor.
     *
     * @param int $courseId
     * @param int $userId
     * @param int $sessionId
     * @param int $groupId
     */
    public function __construct($courseId, $userId, $sessionId, $groupId, ResourceNode $resourceNode, ResourceRepository $repository)
    {
        $this->courseId = (int) $courseId;
        $this->userId = (int) $userId;
        $this->sessionId = (int) $sessionId;
        $this->groupId = (int) $groupId;
        $this->resourceNode = $resourceNode;
        $this->repository = $repository;
    }

    /**
     * Prepare a message. Clean and insert emojis.
     *
     * @param string $message The message to prepare
     *
     * @return string
     */
    public function prepareMessage($message)
    {
        if (empty($message)) {
            return '';
        }

        //Emojione\Emojione::$imagePathPNG = api_get_path(WEB_LIBRARY_PATH).'javascript/emojione/png/';
        //Emojione\Emojione::$ascii = true;

        $message = trim($message);
        $message = nl2br($message);
        // Security XSS
        $message = Security::remove_XSS($message);
        //search urls
        $message = preg_replace(
            '@((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)@',
            '<a href="$1" target="_blank">$1</a>',
            $message
        );
        // add "http://" if not set
        $message = preg_replace(
            '/<a\s[^>]*href\s*=\s*"((?!https?:\/\/)[^"]*)"[^>]*>/i',
            '<a href="http://$1" target="_blank">',
            $message
        );

        // Parsing emojis
        //$message = Emojione\Emojione::toImage($message);
        // Parsing text to understand markdown (code highlight)
        $message = MarkdownExtra::defaultTransform($message);

        return $message;
    }

    /**
     * Save a chat message in a HTML file.
     *
     * @param string $message
     * @param int    $friendId
     *
     * @return bool
     */
    public function saveMessage($message, $friendId = 0)
    {
        if (empty($message)) {
            return false;
        }
        $friendId = (int) $friendId;

        $user = api_get_user_entity($this->userId);
        $courseInfo = api_get_course_info_by_id($this->courseId);
        $isMaster = api_is_course_admin();
        $date_now = date('Y-m-d');
        $timeNow = date('d/m/y H:i:s');
        $basename_chat = 'messages-'.$date_now;

        if ($this->groupId && !$friendId) {
            $basename_chat = 'messages-'.$date_now.'_gid-'.$this->groupId;
        } elseif ($this->sessionId && !$friendId) {
            $basename_chat = 'messages-'.$date_now.'_sid-'.$this->sessionId;
        } elseif ($friendId) {
            if ($this->userId < $friendId) {
                $basename_chat = 'messages-'.$date_now.'_uid-'.$this->userId.'-'.$friendId;
            } else {
                $basename_chat = 'messages-'.$date_now.'_uid-'.$friendId.'-'.$this->userId;
            }
        }

        $message = self::prepareMessage($message);

        $fileTitle = $basename_chat.'-log.html';

        $userPhoto = UserManager::getUserPicture($this->userId, USER_IMAGE_SIZE_MEDIUM);

        if ($isMaster) {
            $fileContent = '
                <div class="message-teacher">
                    <div class="content-message">
                        <div class="chat-message-block-name">'.UserManager::formatUserFullName($user).'</div>
                        <div class="chat-message-block-content">'.$message.'</div>
                        <div class="message-date">'.$timeNow.'</div>
                    </div>
                    <div class="icon-message"></div>
                    <img class="chat-image" src="'.$userPhoto.'">
                </div>
            ';
        } else {
            $fileContent = '
                <div class="message-student">
                    <img class="chat-image" src="'.$userPhoto.'">
                    <div class="icon-message"></div>
                    <div class="content-message">
                        <div class="chat-message-block-name">'.UserManager::formatUserFullName($user).'</div>
                        <div class="chat-message-block-content">'.$message.'</div>
                        <div class="message-date">'.$timeNow.'</div>
                    </div>
                </div>
            ';
        }

        $criteria = [
            'slug' => $fileTitle,
            'parent' => $this->resourceNode,
        ];

        $resourceNode = $this->repository->getResourceNodeRepository()->findOneBy($criteria);
        if ($resourceNode) {
            $resource = $this->repository->getResourceFromResourceNode($resourceNode->getId());
            if ($resource) {
                $content = $this->repository->getResourceNodeFileContent($resourceNode);
                $this->repository->updateResourceFileContent($resource, $content.$fileContent);
            }
        }

        /*fputs($fp, $fileContent);
        fclose($fp);
        $size = filesize($absoluteFilePath);
        update_existing_document($courseInfo, $doc_id, $size);
        item_property_update_on_folder($courseInfo, $basepath_chat, $this->userId);*/

        return true;
    }

    /**
     * Disconnect a user from course chats.
     *
     * @param int $userId
     */
    public static function exitChat($userId)
    {
        $listCourse = CourseManager::get_courses_list_by_user_id($userId);

        foreach ($listCourse as $course) {
            Database::getManager()
                ->createQuery('
                    DELETE FROM ChamiloCourseBundle:CChatConnected ccc
                    WHERE ccc.cId = :course AND ccc.userId = :user
                ')
                ->execute([
                    'course' => intval($course['real_id']),
                    'user' => intval($userId),
                ]);
        }
    }

    /**
     * Disconnect users who are more than 5 seconds inactive.
     */
    public function disconnectInactiveUsers()
    {
        $em = Database::getManager();
        $extraCondition = "AND ccc.toGroupId = {$this->groupId}";
        if (empty($this->groupId)) {
            $extraCondition = "AND ccc.sessionId = {$this->sessionId}";
        }

        $connectedUsers = $em
            ->createQuery("
                SELECT ccc FROM ChamiloCourseBundle:CChatConnected ccc
                WHERE ccc.cId = :course $extraCondition
            ")
            ->setParameter('course', $this->courseId)
            ->getResult();

        $now = new DateTime(api_get_utc_datetime(), new DateTimeZone('UTC'));
        $cd_count_time_seconds = $now->getTimestamp();
        /** @var CChatConnected $connection */
        foreach ($connectedUsers as $connection) {
            $date_count_time_seconds = $connection->getLastConnection()->getTimestamp();
            if (0 !== strcmp($now->format('Y-m-d'), $connection->getLastConnection()->format('Y-m-d'))) {
                continue;
            }

            if (($cd_count_time_seconds - $date_count_time_seconds) <= 5) {
                continue;
            }

            $em
                ->createQuery('
                    DELETE FROM ChamiloCourseBundle:CChatConnected ccc
                    WHERE ccc.cId = :course AND ccc.userId = :user AND ccc.toGroupId = :group
                ')
                ->execute([
                    'course' => $this->courseId,
                    'user' => $connection->getUserId(),
                    'group' => $this->groupId,
                ]);
        }
    }

    /**
     * Keep registered to a user as connected.
     */
    public function keepUserAsConnected()
    {
        $em = Database::getManager();
        $extraCondition = null;

        if ($this->groupId) {
            $extraCondition = 'AND ccc.toGroupId = '.$this->groupId;
        } else {
            $extraCondition = 'AND ccc.sessionId = '.$this->sessionId;
        }

        $currentTime = new DateTime(api_get_utc_datetime(), new DateTimeZone('UTC'));

        /** @var CChatConnected $connection */
        $connection = $em
            ->createQuery("
                SELECT ccc FROM ChamiloCourseBundle:CChatConnected ccc
                WHERE ccc.userId = :user AND ccc.cId = :course $extraCondition
            ")
            ->setParameters([
                'user' => $this->userId,
                'course' => $this->courseId,
            ])
            ->getOneOrNullResult();

        if ($connection) {
            $connection->setLastConnection($currentTime);
            $em->persist($connection);
            $em->flush();

            return;
        }

        $connection = new CChatConnected();
        $connection
            ->setCId($this->courseId)
            ->setUserId($this->userId)
            ->setLastConnection($currentTime)
            ->setSessionId($this->sessionId)
            ->setToGroupId($this->groupId);

        $em->persist($connection);
        $em->flush();
    }

    /**
     * Get the emoji allowed on course chat.
     *
     * @return array
     */
    /*public static function getEmojiStrategy()
    {
        return require_once api_get_path(SYS_CODE_PATH).'chat/emoji_strategy.php';
    }*/

    /**
     * Get the emoji list to include in chat.
     *
     * @return array
     */
    public static function getEmojisToInclude()
    {
        return [
            ':bowtie:',
            ':smile:' |
            ':laughing:',
            ':blush:',
            ':smiley:',
            ':relaxed:',
            ':smirk:',
            ':heart_eyes:',
            ':kissing_heart:',
            ':kissing_closed_eyes:',
            ':flushed:',
            ':relieved:',
            ':satisfied:',
            ':grin:',
            ':wink:',
            ':stuck_out_tongue_winking_eye:',
            ':stuck_out_tongue_closed_eyes:',
            ':grinning:',
            ':kissing:',
            ':kissing_smiling_eyes:',
            ':stuck_out_tongue:',
            ':sleeping:',
            ':worried:',
            ':frowning:',
            ':anguished:',
            ':open_mouth:',
            ':grimacing:',
            ':confused:',
            ':hushed:',
            ':expressionless:',
            ':unamused:',
            ':sweat_smile:',
            ':sweat:',
            ':disappointed_relieved:',
            ':weary:',
            ':pensive:',
            ':disappointed:',
            ':confounded:',
            ':fearful:',
            ':cold_sweat:',
            ':persevere:',
            ':cry:',
            ':sob:',
            ':joy:',
            ':astonished:',
            ':scream:',
            ':neckbeard:',
            ':tired_face:',
            ':angry:',
            ':rage:',
            ':triumph:',
            ':sleepy:',
            ':yum:',
            ':mask:',
            ':sunglasses:',
            ':dizzy_face:',
            ':imp:',
            ':smiling_imp:',
            ':neutral_face:',
            ':no_mouth:',
            ':innocent:',
            ':alien:',
        ];
    }

    /**
     * Get the chat history file name.
     *
     * @param bool $absolute Optional. Whether get the base or the absolute file path
     * @param int  $friendId optional
     *
     * @return string
     */
    public function getFileName($absolute = false, $friendId = 0)
    {
        $date = date('Y-m-d');
        $base = 'messages-'.$date.'.log.html';

        if ($this->groupId && !$friendId) {
            $base = 'messages-'.$date.'_gid-'.$this->groupId.'.log.html';
        } elseif ($this->sessionId && !$friendId) {
            $base = 'messages-'.$date.'_sid-'.$this->sessionId.'.log.html';
        } elseif ($friendId) {
            if ($this->userId < $friendId) {
                $base = 'messages-'.$date.'_uid-'.$this->userId.'-'.$friendId.'.log.html';
            } else {
                $base = 'messages-'.$date.'_uid-'.$friendId.'-'.$this->userId.'.log.html';
            }
        }

        if (!$absolute) {
            return $base;
        }

        //$courseInfo = api_get_course_info_by_id($this->courseId);

        $document_path = '/document';
        $chatPath = $document_path.'/chat_files/';

        if ($this->groupId) {
            $group_info = GroupManager::get_group_properties($this->groupId);
            $chatPath = $document_path.$group_info['directory'].'/chat_files/';
        }

        return $chatPath.$base;
    }

    /**
     * Get the chat history.
     *
     * @param bool $reset
     * @param int  $friendId optional
     *
     * @return string
     */
    public function readMessages($reset = false, $friendId = 0)
    {
        $courseInfo = api_get_course_info_by_id($this->courseId);
        $date_now = date('Y-m-d');
        $isMaster = (bool) api_is_course_admin();
        if ($this->groupId) {
            $group_info = GroupManager:: get_group_properties($this->groupId);
        }

        //$chat_path = $document_path.$basepath_chat.'/';

        $filename_chat = 'messages-'.$date_now.'-log.html';

        if ($this->groupId && !$friendId) {
            $filename_chat = 'messages-'.$date_now.'_gid-'.$this->groupId.'-log.html';
        } elseif ($this->sessionId && !$friendId) {
            $filename_chat = 'messages-'.$date_now.'_sid-'.$this->sessionId.'-log.html';
        } elseif ($friendId) {
            if ($this->userId < $friendId) {
                $filename_chat = 'messages-'.$date_now.'_uid-'.$this->userId.'-'.$friendId.'-log.html';
            } else {
                $filename_chat = 'messages-'.$date_now.'_uid-'.$friendId.'-'.$this->userId.'-log.html';
            }
        }

        $criteria = [
            'slug' => $filename_chat,
            'parent' => $this->resourceNode,
        ];

        $resourceNode = $this->repository->getResourceNodeRepository()->findOneBy($criteria);

        /** @var ResourceNode $resourceNode */
        //$resourceNode = $this->repository->findOneBy($criteria);
        //var_dump($filename_chat, $this->resourceNode->getId());exit;

        if (null === $resourceNode) {
            $em = Database::getManager();
            $resource = new CChatConversation();
            $resource->setName($filename_chat);

            $handle = tmpfile();
            fwrite($handle, '');
            $meta = stream_get_meta_data($handle);
            $file = new UploadedFile($meta['uri'], $filename_chat, 'text/html', null, true);

            $this->repository->addResourceToCourse(
                $resource,
                ResourceLink::VISIBILITY_PUBLISHED,
                api_get_user_entity(api_get_user_id()),
                api_get_course_entity(),
                api_get_session_entity(),
                api_get_group_entity(),
                $file
            );
            $em->flush();

            $resourceNode = $resource->getResourceNode();
        }

        if ($resourceNode->hasResourceFile()) {
            //$resourceFile = $resourceNode->getResourceFile();
            //$fileName = $this->getFilename($resourceFile);
            return $this->repository->getResourceNodeFileContent($resourceNode);
        }

        return '';

        $remove = 0;
        $content = [];

        if (file_exists($chat_path.$basename_chat.'.log.html')) {
            $content = file($chat_path.$basename_chat.'.log.html');
            $nbr_lines = sizeof($content);
            $remove = $nbr_lines - 100;
        }

        if ($remove < 0) {
            $remove = 0;
        }

        array_splice($content, 0, $remove);

        if (isset($_GET['origin']) && 'whoisonline' == $_GET['origin']) {
            //the caller
            $content[0] = get_lang('Chat call has been sent. Waiting for the approval of your partner.').'<br />'.$content[0];
        }

        $history = '<div id="content-chat">';
        foreach ($content as $this_line) {
            $history .= $this_line;
        }
        $history .= '</div>';

        if ($isMaster || $GLOBALS['is_session_general_coach']) {
            $history .= '
                <div id="clear-chat">
                    <button type="button" id="chat-reset" class="btn btn-danger btn-sm">
                        '.get_lang('Clear the chat').'
                    </button>
                </div>
            ';
        }

        return $history;
    }

    /**
     * Get the number of users connected in chat.
     *
     * @return int
     */
    public function countUsersOnline()
    {
        $date = new DateTime(api_get_utc_datetime(), new DateTimeZone('UTC'));
        $date->modify('-5 seconds');

        if ($this->groupId) {
            $extraCondition = 'AND ccc.toGroupId = '.$this->groupId;
        } else {
            $extraCondition = 'AND ccc.sessionId = '.$this->sessionId;
        }

        $number = Database::getManager()
            ->createQuery("
                SELECT COUNT(ccc.userId) FROM ChamiloCourseBundle:CChatConnected ccc
                WHERE ccc.lastConnection > :date AND ccc.cId = :course $extraCondition
            ")
            ->setParameters([
                'date' => $date,
                'course' => $this->courseId,
            ])
            ->getSingleScalarResult();

        return (int) $number;
    }

    /**
     * Get the users online data.
     *
     * @return array
     */
    public function listUsersOnline()
    {
        $subscriptions = $this->getUsersSubscriptions();
        $usersInfo = [];

        if ($this->groupId) {
            /** @var User $groupUser */
            foreach ($subscriptions as $groupUser) {
                $usersInfo[] = $this->formatUser(
                    $groupUser,
                    $groupUser->getStatus()
                );
            }
        } else {
            /** @var CourseRelUser|SessionRelCourseRelUser $subscription */
            foreach ($subscriptions as $subscription) {
                $user = $subscription->getUser();
                $usersInfo[] = $this->formatUser(
                    $user,
                    $this->sessionId ? $user->getStatus() : $subscription->getStatus()
                );
            }
        }

        return $usersInfo;
    }

    /**
     * Format the user data to return it in the user list.
     *
     * @param int $status
     *
     * @return array
     */
    private function formatUser(User $user, $status)
    {
        return [
            'id' => $user->getId(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'status' => $status,
            'image_url' => UserManager::getUserPicture($user->getId(), USER_IMAGE_SIZE_MEDIUM),
            'profile_url' => api_get_path(WEB_CODE_PATH).'social/profile.php?u='.$user->getId(),
            'complete_name' => UserManager::formatUserFullName($user),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'isConnected' => $this->userIsConnected($user->getId()),
        ];
    }

    /**
     * Get the users subscriptions (SessionRelCourseRelUser array or CourseRelUser array) for chat.
     *
     * @return ArrayCollection
     */
    private function getUsersSubscriptions()
    {
        $em = Database::getManager();

        if ($this->groupId) {
            $students = $em
                ->createQuery(
                    'SELECT u FROM ChamiloCoreBundle:User u
                    INNER JOIN ChamiloCourseBundle:CGroupRelUser gru
                        WITH u.id = gru.userId AND gru.cId = :course
                    WHERE u.id != :user AND gru.groupId = :group
                        AND u.active = true'
                )
                ->setParameters(['course' => $this->courseId, 'user' => $this->userId, 'group' => $this->groupId])
                ->getResult();
            $tutors = $em
                ->createQuery(
                    'SELECT u FROM ChamiloCoreBundle:User u
                    INNER JOIN ChamiloCourseBundle:CGroupRelTutor grt
                        WITH u.id = grt.userId AND grt.cId = :course
                    WHERE u.id != :user AND grt.groupId = :group
                        AND u.active = true'
                )
                ->setParameters(['course' => $this->courseId, 'user' => $this->userId, 'group' => $this->groupId])
                ->getResult();

            return array_merge($tutors, $students);
        }

        $course = api_get_course_entity($this->courseId);

        if ($this->sessionId) {
            $session = api_get_session_entity($this->sessionId);
            $criteria = Criteria::create()->where(Criteria::expr()->eq('course', $course));
            $userIsCoach = api_is_course_session_coach($this->userId, $course->getId(), $session->getId());

            if (api_get_configuration_value('course_chat_restrict_to_coach')) {
                if ($userIsCoach) {
                    $criteria->andWhere(
                        Criteria::expr()->eq('status', Session::STUDENT)
                    );
                } else {
                    $criteria->andWhere(
                        Criteria::expr()->eq('status', Session::COACH)
                    );
                }
            }

            $criteria->orderBy(['status' => Criteria::DESC]);

            return $session
                ->getUserCourseSubscriptions()
                ->matching($criteria)
                ->filter(function (SessionRelCourseRelUser $sessionRelCourseRelUser) {
                    return $sessionRelCourseRelUser->getUser()->isActive();
                });
        }

        return $course
            ->getUsers()
            ->filter(function (CourseRelUser $courseRelUser) {
                return $courseRelUser->getUser()->isActive();
            });
    }

    /**
     * Check if a user is connected in course chat.
     *
     * @param int $userId
     *
     * @return int
     */
    private function userIsConnected($userId)
    {
        $date = new DateTime(api_get_utc_datetime(), new DateTimeZone('UTC'));
        $date->modify('-5 seconds');

        if ($this->groupId) {
            $extraCondition = 'AND ccc.toGroupId = '.$this->groupId;
        } else {
            $extraCondition = 'AND ccc.sessionId = '.$this->sessionId;
        }

        $number = Database::getManager()
            ->createQuery("
                SELECT COUNT(ccc.userId) FROM ChamiloCourseBundle:CChatConnected ccc
                WHERE ccc.lastConnection > :date AND ccc.cId = :course AND ccc.userId = :user $extraCondition
            ")
            ->setParameters([
                'date' => $date,
                'course' => $this->courseId,
                'user' => $userId,
            ])
            ->getSingleScalarResult();

        return (int) $number;
    }
}

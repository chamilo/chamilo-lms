<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CNotebook;
use Chamilo\CourseBundle\Entity\Repository\CNotebookRepository;
use Chamilo\UserBundle\Entity\User;

/**
 * Class RestApi.
 */
class Rest extends WebService
{
    const SERVICE_NAME = 'MsgREST';
    const EXTRA_FIELD_GCM_REGISTRATION = 'gcm_registration_id';

    const GET_AUTH = 'authenticate';
    const GET_USER_MESSAGES = 'user_messages';
    const POST_USER_MESSAGE_READ = 'user_message_read';
    const POST_USER_MESSAGE_UNREAD = 'user_message_unread';
    const SAVE_GCM_ID = 'gcm_id';
    const GET_USER_COURSES = 'user_courses';
    const GET_PROFILE = 'user_profile';
    const GET_COURSE_INFO = 'course_info';
    const GET_COURSE_DESCRIPTIONS = 'course_descriptions';
    const GET_COURSE_DOCUMENTS = 'course_documents';
    const GET_COURSE_ANNOUNCEMENTS = 'course_announcements';
    const GET_COURSE_ANNOUNCEMENT = 'course_announcement';
    const GET_COURSE_AGENDA = 'course_agenda';
    const GET_COURSE_NOTEBOOKS = 'course_notebooks';
    const GET_COURSE_FORUM_CATEGORIES = 'course_forumcategories';
    const GET_COURSE_FORUM = 'course_forum';
    const GET_COURSE_FORUM_THREAD = 'course_forumthread';
    const GET_COURSE_LEARNPATHS = 'course_learnpaths';
    const GET_COURSE_LEARNPATH = 'course_learnpath';
    const GET_COURSE_LP_PROGRESS = 'course_lp_progress';
    const SAVE_FORUM_POST = 'save_forum_post';
    const GET_USER_SESSIONS = 'user_sessions';
    const SAVE_USER_MESSAGE = 'save_user_message';
    const GET_MESSAGE_USERS = 'message_users';
    const SAVE_COURSE_NOTEBOOK = 'save_course_notebook';
    const SAVE_FORUM_THREAD = 'save_forum_thread';
    const SAVE_COURSE = 'save_course';
    const SAVE_USER = 'save_user';
    const SAVE_USER_JSON = 'save_user_json';
    const SUBSCRIBE_USER_TO_COURSE = 'subscribe_user_to_course';
    const EXTRAFIELD_GCM_ID = 'gcm_registration_id';
    const GET_USER_MESSAGES_RECEIVED = 'user_messages_received';
    const GET_USER_MESSAGES_SENT = 'user_messages_sent';
    const DELETE_USER_MESSAGE = 'delete_user_message';
    const SET_MESSAGE_READ = 'set_message_read';
    const CREATE_CAMPUS = 'add_campus';
    const EDIT_CAMPUS = 'edit_campus';
    const DELETE_CAMPUS = 'delete_campus';
    const SAVE_SESSION = 'save_session';
    const GET_USERS = 'get_users';
    const GET_COURSES = 'get_courses';
    const ADD_COURSES_SESSION = 'add_courses_session';
    const ADD_USERS_SESSION = 'add_users_session';
    const CREATE_SESSION_FROM_MODEL = 'create_session_from_model';
    const SUBSCRIBE_USER_TO_SESSION_FROM_USERNAME = 'subscribe_user_to_session_from_username';
    const GET_SESSION_FROM_EXTRA_FIELD = 'get_session_from_extra_field';
    const UPDATE_USER_FROM_USERNAME = 'update_user_from_username';
    const USERNAME_EXIST = 'username_exist';
    const GET_COURSE_QUIZ_MDL_COMPAT = 'get_course_quiz_mdl_compat';
    const UPDATE_USER_PAUSE_TRAINING = 'update_user_pause_training';

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Course
     */
    private $course;

    /**
     * Rest constructor.
     *
     * @param string $username
     * @param string $apiKey
     */
    public function __construct($username, $apiKey)
    {
        parent::__construct($username, $apiKey);
    }

    /**
     * @param string $username
     * @param string $apiKeyToValidate
     *
     * @throws Exception
     *
     * @return Rest
     */
    public static function validate($username, $apiKeyToValidate)
    {
        $apiKey = self::findUserApiKey($username, self::SERVICE_NAME);

        if ($apiKey != $apiKeyToValidate) {
            throw new Exception(get_lang('InvalidApiKey'));
        }

        return new self($username, $apiKey);
    }

    /**
     * Create the gcm_registration_id extra field for users.
     */
    public static function init()
    {
        $extraField = new ExtraField('user');
        $fieldInfo = $extraField->get_handler_field_info_by_field_variable(self::EXTRA_FIELD_GCM_REGISTRATION);

        if (empty($fieldInfo)) {
            $extraField->save(
                [
                    'variable' => self::EXTRA_FIELD_GCM_REGISTRATION,
                    'field_type' => ExtraField::FIELD_TYPE_TEXT,
                    'display_text' => self::EXTRA_FIELD_GCM_REGISTRATION,
                ]
            );
        }
    }

    /**
     * @param string $encoded
     *
     * @return array
     */
    public static function decodeParams($encoded)
    {
        return json_decode($encoded);
    }

    /**
     * Set the current course.
     *
     * @param int $id
     *
     * @throws Exception
     */
    public function setCourse($id)
    {
        if (!$id) {
            $this->course = null;

            return;
        }

        $em = Database::getManager();
        /** @var Course $course */
        $course = $em->find('ChamiloCoreBundle:Course', $id);

        if (!$course) {
            throw new Exception(get_lang('NoCourse'));
        }

        $this->course = $course;
    }

    /**
     * Set the current session.
     *
     * @param int $id
     *
     * @throws Exception
     */
    public function setSession($id)
    {
        if (!$id) {
            $this->session = null;

            return;
        }

        $em = Database::getManager();
        /** @var Session $session */
        $session = $em->find('ChamiloCoreBundle:Session', $id);

        if (!$session) {
            throw new Exception(get_lang('NoSession'));
        }

        $this->session = $session;
    }

    /**
     * @param string $registrationId
     *
     * @return bool
     */
    public function setGcmId($registrationId)
    {
        $registrationId = Security::remove_XSS($registrationId);
        $extraFieldValue = new ExtraFieldValue('user');

        return $extraFieldValue->save(
            [
                'variable' => self::EXTRA_FIELD_GCM_REGISTRATION,
                'value' => $registrationId,
                'item_id' => $this->user->getId(),
            ]
        );
    }

    /**
     * @param int $lastMessageId
     *
     * @return array
     */
    public function getUserMessages($lastMessageId = 0)
    {
        $lastMessages = MessageManager::getMessagesFromLastReceivedMessage($this->user->getId(), $lastMessageId);
        $messages = [];

        foreach ($lastMessages as $message) {
            $hasAttachments = MessageManager::hasAttachments($message['id']);

            $messages[] = [
                'id' => $message['id'],
                'title' => $message['title'],
                'sender' => [
                    'id' => $message['user_id'],
                    'lastname' => $message['lastname'],
                    'firstname' => $message['firstname'],
                    'completeName' => api_get_person_name($message['firstname'], $message['lastname']),
                ],
                'sendDate' => $message['send_date'],
                'content' => $message['content'],
                'hasAttachments' => $hasAttachments,
                'url' => api_get_path(WEB_CODE_PATH).'messages/view_message.php?'
                    .http_build_query(['type' => 1, 'id' => $message['id']]),
            ];
        }

        return $messages;
    }

    /**
     * @return array
     */
    public function getUserReceivedMessages()
    {
        $lastMessages = MessageManager::getReceivedMessages($this->user->getId(), 0);
        $messages = [];

        foreach ($lastMessages as $message) {
            $hasAttachments = MessageManager::hasAttachments($message['id']);
            $attachmentList = [];
            if ($hasAttachments) {
                $attachmentList = MessageManager::getAttachmentList($message['id']);
            }
            $messages[] = [
                'id' => $message['id'],
                'title' => $message['title'],
                'msgStatus' => $message['msg_status'],
                'sender' => [
                    'id' => $message['user_id'],
                    'lastname' => $message['lastname'],
                    'firstname' => $message['firstname'],
                    'completeName' => api_get_person_name($message['firstname'], $message['lastname']),
                    'pictureUri' => $message['pictureUri'],
                ],
                'sendDate' => $message['send_date'],
                'content' => $message['content'],
                'hasAttachments' => $hasAttachments,
                'attachmentList' => $attachmentList,
                'url' => '',
            ];
        }

        return $messages;
    }

    /**
     * @return array
     */
    public function getUserSentMessages()
    {
        $lastMessages = MessageManager::getSentMessages($this->user->getId(), 0);
        $messages = [];

        foreach ($lastMessages as $message) {
            $hasAttachments = MessageManager::hasAttachments($message['id']);

            $messages[] = [
                'id' => $message['id'],
                'title' => $message['title'],
                'msgStatus' => $message['msg_status'],
                'receiver' => [
                    'id' => $message['user_id'],
                    'lastname' => $message['lastname'],
                    'firstname' => $message['firstname'],
                    'completeName' => api_get_person_name($message['firstname'], $message['lastname']),
                    'pictureUri' => $message['pictureUri'],
                ],
                'sendDate' => $message['send_date'],
                'content' => $message['content'],
                'hasAttachments' => $hasAttachments,
                'url' => '',
            ];
        }

        return $messages;
    }

    /**
     * Get the user courses.
     *
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return array
     */
    public function getUserCourses()
    {
        $courses = CourseManager::get_courses_list_by_user_id($this->user->getId());
        $data = [];

        foreach ($courses as $courseInfo) {
            /** @var Course $course */
            $course = Database::getManager()->find('ChamiloCoreBundle:Course', $courseInfo['real_id']);
            $teachers = CourseManager::getTeacherListFromCourseCodeToString($course->getCode());
            $picturePath = CourseManager::getPicturePath($course, true)
                ?: Display::return_icon('session_default.png', null, null, null, null, true);

            $data[] = [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'code' => $course->getCode(),
                'directory' => $course->getDirectory(),
                'urlPicture' => $picturePath,
                'teachers' => $teachers,
                'isSpecial' => !empty($courseInfo['special_course']),
            ];
        }

        return $data;
    }

    /**
     * @throws Exception
     *
     * @return array
     */
    public function getCourseInfo()
    {
        $teachers = CourseManager::getTeacherListFromCourseCodeToString($this->course->getCode());
        $tools = CourseHome::get_tools_category(
            TOOL_STUDENT_VIEW,
            $this->course->getId(),
            $this->session ? $this->session->getId() : 0
        );

        return [
            'id' => $this->course->getId(),
            'title' => $this->course->getTitle(),
            'code' => $this->course->getCode(),
            'directory' => $this->course->getDirectory(),
            'urlPicture' => CourseManager::getPicturePath($this->course, true),
            'teachers' => $teachers,
            'tools' => array_map(
                function ($tool) {
                    return ['type' => $tool['name']];
                },
                $tools
            ),
        ];
    }

    /**
     * Get the course descriptions.
     *
     * @throws Exception
     *
     * @return array
     */
    public function getCourseDescriptions()
    {
        $descriptions = CourseDescription::get_descriptions($this->course->getId());
        $results = [];

        /** @var CourseDescription $description */
        foreach ($descriptions as $description) {
            $results[] = [
                'id' => $description->get_description_type(),
                'title' => $description->get_title(),
                'content' => str_replace('src="/', 'src="'.api_get_path(WEB_PATH), $description->get_content()),
            ];
        }

        return $results;
    }

    /**
     * @param int $directoryId
     *
     * @throws Exception
     *
     * @return array
     */
    public function getCourseDocuments($directoryId = 0)
    {
        /** @var string $path */
        $path = '/';
        $sessionId = $this->session ? $this->session->getId() : 0;

        if ($directoryId) {
            $directory = DocumentManager::get_document_data_by_id(
                $directoryId,
                $this->course->getCode(),
                false,
                $sessionId
            );

            if (!$directory) {
                throw new Exception('NoDataAvailable');
            }

            $path = $directory['path'];
        }

        $courseInfo = api_get_course_info_by_id($this->course->getId());
        $documents = DocumentManager::getAllDocumentData(
            $courseInfo,
            $path,
            0,
            null,
            false,
            false,
            $sessionId
        );
        $results = [];

        if (!empty($documents)) {
            $webPath = api_get_path(WEB_CODE_PATH).'document/document.php?';

            /** @var array $document */
            foreach ($documents as $document) {
                if ($document['visibility'] != '1') {
                    continue;
                }

                $icon = $document['filetype'] == 'file'
                    ? choose_image($document['path'])
                    : chooseFolderIcon($document['path']);

                $results[] = [
                    'id' => $document['id'],
                    'type' => $document['filetype'],
                    'title' => $document['title'],
                    'path' => $document['path'],
                    'url' => $webPath.http_build_query(
                        [
                            'username' => $this->user->getUsername(),
                            'api_key' => $this->apiKey,
                            'cidReq' => $this->course->getCode(),
                            'id_session' => $sessionId,
                            'gidReq' => 0,
                            'gradebook' => 0,
                            'origin' => '',
                            'action' => 'download',
                            'id' => $document['id'],
                        ]
                    ),
                    'icon' => $icon,
                    'size' => format_file_size($document['size']),
                ];
            }
        }

        return $results;
    }

    /**
     * @throws Exception
     *
     * @return array
     */
    public function getCourseAnnouncements()
    {
        $sessionId = $this->session ? $this->session->getId() : 0;

        $announcements = AnnouncementManager::getAnnouncements(
            null,
            null,
            false,
            null,
            null,
            null,
            null,
            null,
            0,
            $this->user->getId(),
            $this->course->getId(),
            $sessionId
        );

        $announcements = array_map(
            function ($announcement) {
                return [
                    'id' => (int) $announcement['id'],
                    'title' => strip_tags($announcement['title']),
                    'creatorName' => strip_tags($announcement['username']),
                    'date' => strip_tags($announcement['insert_date']),
                ];
            },
            $announcements
        );

        return $announcements;
    }

    /**
     * @param int $announcementId
     *
     * @throws Exception
     *
     * @return array
     */
    public function getCourseAnnouncement($announcementId)
    {
        $sessionId = $this->session ? $this->session->getId() : 0;
        $announcement = AnnouncementManager::getAnnouncementInfoById(
            $announcementId,
            $this->course->getId(),
            $this->user->getId()
        );

        if (!$announcement) {
            throw new Exception(get_lang('NoAnnouncement'));
        }

        return [
            'id' => $announcement['announcement']->getIid(),
            'title' => $announcement['announcement']->getTitle(),
            'creatorName' => UserManager::formatUserFullName($announcement['item_property']->getInsertUser()),
            'date' => api_convert_and_format_date(
                $announcement['item_property']->getInsertDate(),
                DATE_TIME_FORMAT_LONG_24H
            ),
            'content' => AnnouncementManager::parseContent(
                $this->user->getId(),
                $announcement['announcement']->getContent(),
                $this->course->getCode(),
                $sessionId
            ),
        ];
    }

    /**
     * @throws Exception
     *
     * @return array
     */
    public function getCourseAgenda()
    {
        $sessionId = $this->session ? $this->session->getId() : 0;

        $agenda = new Agenda(
            'course',
            $this->user->getId(),
            $this->course->getId(),
            $sessionId
        );
        $result = $agenda->parseAgendaFilter(null);

        $start = new DateTime(api_get_utc_datetime(), new DateTimeZone('UTC'));
        $start->modify('first day of this month');
        $start->setTime(0, 0, 0);
        $end = new DateTime(api_get_utc_datetime(), new DateTimeZone('UTC'));
        $end->modify('last day of this month');
        $end->setTime(23, 59, 59);

        $groupId = current($result['groups']);
        $userId = current($result['users']);

        $events = $agenda->getEvents(
            $start->getTimestamp(),
            $end->getTimestamp(),
            $this->course->getId(),
            $groupId,
            $userId,
            'array'
        );

        if (!is_array($events)) {
            return [];
        }

        $webPath = api_get_path(WEB_PATH);

        return array_map(
            function ($event) use ($webPath) {
                return [
                    'id' => (int) $event['unique_id'],
                    'title' => $event['title'],
                    'content' => str_replace('src="/', 'src="'.$webPath, $event['description']),
                    'startDate' => $event['start_date_localtime'],
                    'endDate' => $event['end_date_localtime'],
                    'isAllDay' => $event['allDay'] ? true : false,
                ];
            },
            $events
        );
    }

    /**
     * @throws Exception
     *
     * @return array
     */
    public function getCourseNotebooks()
    {
        $em = Database::getManager();
        /** @var CNotebookRepository $notebooksRepo */
        $notebooksRepo = $em->getRepository('ChamiloCourseBundle:CNotebook');
        $notebooks = $notebooksRepo->findByUser($this->user, $this->course, $this->session);

        return array_map(
            function (CNotebook $notebook) {
                return [
                    'id' => $notebook->getIid(),
                    'title' => $notebook->getTitle(),
                    'description' => $notebook->getDescription(),
                    'creationDate' => api_format_date(
                        $notebook->getCreationDate()->getTimestamp()
                    ),
                    'updateDate' => api_format_date(
                        $notebook->getUpdateDate()->getTimestamp()
                    ),
                ];
            },
            $notebooks
        );
    }

    /**
     * @throws Exception
     *
     * @return array
     */
    public function getCourseForumCategories()
    {
        $sessionId = $this->session ? $this->session->getId() : 0;
        $webCoursePath = api_get_path(WEB_COURSE_PATH).$this->course->getDirectory().'/upload/forum/images/';

        require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';

        $categoriesFullData = get_forum_categories('', $this->course->getId(), $sessionId);
        $categories = [];
        $includeGroupsForums = api_get_setting('display_groups_forum_in_general_tool') === 'true';
        $forumsFullData = get_forums('', $this->course->getCode(), $includeGroupsForums, $sessionId);
        $forums = [];

        foreach ($forumsFullData as $forumId => $forumInfo) {
            $forum = [
                'id' => (int) $forumInfo['iid'],
                'catId' => (int) $forumInfo['forum_category'],
                'title' => $forumInfo['forum_title'],
                'description' => $forumInfo['forum_comment'],
                'image' => $forumInfo['forum_image'] ? ($webCoursePath.$forumInfo['forum_image']) : '',
                'numberOfThreads' => isset($forumInfo['number_of_threads']) ? intval(
                    $forumInfo['number_of_threads']
                ) : 0,
                'lastPost' => null,
            ];

            $lastPostInfo = get_last_post_information($forumId, false, $this->course->getId(), $sessionId);

            if ($lastPostInfo) {
                $forum['lastPost'] = [
                    'date' => api_convert_and_format_date($lastPostInfo['last_post_date']),
                    'user' => api_get_person_name(
                        $lastPostInfo['last_poster_firstname'],
                        $lastPostInfo['last_poster_lastname']
                    ),
                ];
            }

            $forums[] = $forum;
        }

        foreach ($categoriesFullData as $category) {
            $categoryForums = array_filter(
                $forums,
                function (array $forum) use ($category) {
                    if ($forum['catId'] != $category['cat_id']) {
                        return false;
                    }

                    return true;
                }
            );

            $categories[] = [
                'id' => (int) $category['iid'],
                'title' => $category['cat_title'],
                'catId' => (int) $category['cat_id'],
                'description' => $category['cat_comment'],
                'forums' => $categoryForums,
                'courseId' => $this->course->getId(),
            ];
        }

        return $categories;
    }

    /**
     * @param int $forumId
     *
     * @throws Exception
     *
     * @return array
     */
    public function getCourseForum($forumId)
    {
        require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';

        $sessionId = $this->session ? $this->session->getId() : 0;
        $forumInfo = get_forums($forumId, $this->course->getCode(), true, $sessionId);

        if (!isset($forumInfo['iid'])) {
            throw new Exception(get_lang('NoForum'));
        }

        $webCoursePath = api_get_path(WEB_COURSE_PATH).$this->course->getDirectory().'/upload/forum/images/';
        $forum = [
            'id' => $forumInfo['iid'],
            'title' => $forumInfo['forum_title'],
            'description' => $forumInfo['forum_comment'],
            'image' => $forumInfo['forum_image'] ? ($webCoursePath.$forumInfo['forum_image']) : '',
            'threads' => [],
        ];

        $threads = get_threads($forumInfo['iid'], $this->course->getId(), $sessionId);

        foreach ($threads as $thread) {
            $forum['threads'][] = [
                'id' => $thread['iid'],
                'title' => $thread['thread_title'],
                'lastEditDate' => api_convert_and_format_date($thread['lastedit_date'], DATE_TIME_FORMAT_LONG_24H),
                'numberOfReplies' => $thread['thread_replies'],
                'numberOfViews' => $thread['thread_views'],
                'author' => api_get_person_name($thread['firstname'], $thread['lastname']),
            ];
        }

        return $forum;
    }

    /**
     * @param int $forumId
     * @param int $threadId
     *
     * @return array
     */
    public function getCourseForumThread($forumId, $threadId)
    {
        require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';

        $sessionId = $this->session ? $this->session->getId() : 0;
        $threadInfo = get_thread_information($forumId, $threadId, $sessionId);

        $thread = [
            'id' => intval($threadInfo['iid']),
            'cId' => intval($threadInfo['c_id']),
            'title' => $threadInfo['thread_title'],
            'forumId' => intval($threadInfo['forum_id']),
            'posts' => [],
        ];

        $forumInfo = get_forums($threadInfo['forum_id'], $this->course->getCode(), true, $sessionId);
        $postsInfo = getPosts($forumInfo, $threadInfo['iid'], 'ASC');

        foreach ($postsInfo as $postInfo) {
            $thread['posts'][] = [
                'id' => $postInfo['iid'],
                'title' => $postInfo['post_title'],
                'text' => $postInfo['post_text'],
                'author' => api_get_person_name($postInfo['firstname'], $postInfo['lastname']),
                'date' => api_convert_and_format_date($postInfo['post_date'], DATE_TIME_FORMAT_LONG_24H),
                'parentId' => $postInfo['post_parent_id'],
            ];
        }

        return $thread;
    }

    /**
     * @return array
     */
    public function getUserProfile()
    {
        $pictureInfo = UserManager::get_user_picture_path_by_id($this->user->getId(), 'web');

        $result = [
            'pictureUri' => $pictureInfo['dir'].$pictureInfo['file'],
            'id' => $this->user->getId(),
            'status' => $this->user->getStatus(),
            'fullName' => UserManager::formatUserFullName($this->user),
            'username' => $this->user->getUsername(),
            'officialCode' => $this->user->getOfficialCode(),
            'phone' => $this->user->getPhone(),
            'extra' => [],
        ];

        $fieldValue = new ExtraFieldValue('user');
        $extraInfo = $fieldValue->getAllValuesForAnItem($this->user->getId(), true);

        foreach ($extraInfo as $extra) {
            /** @var ExtraFieldValues $extraValue */
            $extraValue = $extra['value'];
            $result['extra'][] = [
                'title' => $extraValue->getField()->getDisplayText(true),
                'value' => $extraValue->getValue(),
            ];
        }

        return $result;
    }

    public function getCourseLpProgress()
    {
        $sessionId = $this->session ? $this->session->getId() : 0;
        $userId = $this->user->getId();

        /*$sessionId = $this->session ? $this->session->getId() : 0;
        $courseId = $this->course->getId();*/

        $result = Tracking::getCourseLpProgress($userId, $sessionId);

        return [$result];
    }

    /**
     * @throws Exception
     *
     * @return array
     */
    public function getCourseLearnPaths()
    {
        $sessionId = $this->session ? $this->session->getId() : 0;
        $categoriesTempList = learnpath::getCategories($this->course->getId());

        $categoryNone = new CLpCategory();
        $categoryNone->setId(0);
        $categoryNone->setName(get_lang('WithOutCategory'));
        $categoryNone->setPosition(0);

        $categories = array_merge([$categoryNone], $categoriesTempList);
        $categoryData = [];

        /** @var CLpCategory $category */
        foreach ($categories as $category) {
            $learnPathList = new LearnpathList(
                $this->user->getId(),
                api_get_course_info($this->course->getCode()),
                $sessionId,
                null,
                false,
                $category->getId()
            );

            $flatLpList = $learnPathList->get_flat_list();

            if (empty($flatLpList)) {
                continue;
            }

            $listData = [];

            foreach ($flatLpList as $lpId => $lpDetails) {
                if ($lpDetails['lp_visibility'] == 0) {
                    continue;
                }

                if (!learnpath::is_lp_visible_for_student(
                    $lpId,
                    $this->user->getId(),
                    api_get_course_info($this->course->getCode()),
                    $sessionId
                )) {
                    continue;
                }

                $timeLimits = false;

                // This is an old LP (from a migration 1.8.7) so we do nothing
                if (empty($lpDetails['created_on']) && empty($lpDetails['modified_on'])) {
                    $timeLimits = false;
                }

                // Checking if expired_on is ON
                if (!empty($lpDetails['expired_on'])) {
                    $timeLimits = true;
                }

                if ($timeLimits) {
                    if (!empty($lpDetails['publicated_on']) && !empty($lpDetails['expired_on'])) {
                        $startTime = api_strtotime($lpDetails['publicated_on'], 'UTC');
                        $endTime = api_strtotime($lpDetails['expired_on'], 'UTC');
                        $now = time();
                        $isActiveTime = false;

                        if ($now > $startTime && $endTime > $now) {
                            $isActiveTime = true;
                        }

                        if (!$isActiveTime) {
                            continue;
                        }
                    }
                }

                $progress = learnpath::getProgress($lpId, $this->user->getId(), $this->course->getId(), $sessionId);

                $listData[] = [
                    'id' => $lpId,
                    'title' => Security::remove_XSS($lpDetails['lp_name']),
                    'progress' => $progress,
                    'url' => api_get_path(WEB_CODE_PATH).'webservices/api/v2.php?'.http_build_query(
                        [
                            'hash' => $this->encodeParams(
                                [
                                    'action' => 'course_learnpath',
                                    'lp_id' => $lpId,
                                    'course' => $this->course->getId(),
                                    'session' => $sessionId,
                                ]
                            ),
                        ]
                    ),
                ];
            }

            if (empty($listData)) {
                continue;
            }

            $categoryData[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'learnpaths' => $listData,
            ];
        }

        return $categoryData;
    }

    /**
     * Start login for a user. Then make a redirect to show the learnpath.
     *
     * @param int $lpId
     */
    public function showLearningPath($lpId)
    {
        $loggedUser['user_id'] = $this->user->getId();
        $loggedUser['status'] = $this->user->getStatus();
        $loggedUser['uidReset'] = true;
        $sessionId = $this->session ? $this->session->getId() : 0;

        ChamiloSession::write('_user', $loggedUser);
        Login::init_user($this->user->getId(), true);

        $url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.http_build_query(
            [
                'cidReq' => $this->course->getCode(),
                'id_session' => $sessionId,
                'gidReq' => 0,
                'gradebook' => 0,
                'origin' => '',
                'action' => 'view',
                'lp_id' => (int) $lpId,
                'isStudentView' => 'true',
            ]
        );

        header("Location: $url");
        exit;
    }

    /**
     * @param int $forumId
     *
     * @return array
     */
    public function saveForumPost(array $postValues, $forumId)
    {
        require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';

        $forum = get_forums($forumId, $this->course->getCode());
        store_reply($forum, $postValues, $this->course->getId(), $this->user->getId());

        return [
            'registered' => true,
        ];
    }

    /**
     * Get the list of sessions for current user.
     *
     * @return array the sessions list
     */
    public function getUserSessions()
    {
        $data = [];
        $sessionsByCategory = UserManager::get_sessions_by_category($this->user->getId(), false);

        foreach ($sessionsByCategory as $category) {
            $categorySessions = [];

            foreach ($category['sessions'] as $sessions) {
                $sessionCourses = [];

                foreach ($sessions['courses'] as $course) {
                    $courseInfo = api_get_course_info_by_id($course['real_id']);
                    $teachers = SessionManager::getCoachesByCourseSessionToString(
                        $sessions['session_id'],
                        $course['real_id']
                    );

                    $sessionCourses[] = [
                        'id' => $courseInfo['real_id'],
                        'title' => $courseInfo['title'],
                        'code' => $courseInfo['code'],
                        'directory' => $courseInfo['directory'],
                        'pictureUrl' => $courseInfo['course_image_large'],
                        'urlPicture' => $courseInfo['course_image_large'],
                        'teachers' => $teachers,
                    ];
                }

                $sessionBox = Display::getSessionTitleBox($sessions['session_id']);

                $categorySessions[] = [
                    'name' => $sessionBox['title'],
                    'id' => $sessions['session_id'],
                    'date' => $sessionBox['dates'],
                    'duration' => isset($sessionBox['duration']) ? $sessionBox['duration'] : null,
                    'courses' => $sessionCourses,
                ];
            }

            $data[] = [
                'id' => $category['session_category']['id'],
                'name' => $category['session_category']['name'],
                'sessions' => $categorySessions,
            ];
        }

        return $data;
    }

    /**
     * @param string $subject
     * @param string $text
     *
     * @return array
     */
    public function saveUserMessage($subject, $text, array $receivers)
    {
        foreach ($receivers as $userId) {
            MessageManager::send_message($userId, $subject, $text);
        }

        return [
            'sent' => true,
        ];
    }

    /**
     * @param string $search
     *
     * @return array
     */
    public function getMessageUsers($search)
    {
        $repo = UserManager::getRepository();

        $users = $repo->findUsersToSendMessage($this->user->getId(), $search);
        $showEmail = api_get_setting('show_email_addresses') === 'true';
        $data = [];

        /** @var User $user */
        foreach ($users as $user) {
            $userName = UserManager::formatUserFullName($user);

            if ($showEmail) {
                $userName .= " ({$user->getEmail()})";
            }

            $data[] = [
                'id' => $user->getId(),
                'name' => $userName,
            ];
        }

        return $data;
    }

    /**
     * @param string $title
     * @param string $text
     *
     * @return array
     */
    public function saveCourseNotebook($title, $text)
    {
        $values = ['note_title' => $title, 'note_comment' => $text];
        $sessionId = $this->session ? $this->session->getId() : 0;

        $noteBookId = NotebookManager::save_note(
            $values,
            $this->user->getId(),
            $this->course->getId(),
            $sessionId
        );

        return [
            'registered' => $noteBookId,
        ];
    }

    /**
     * @param int $forumId
     *
     * @return array
     */
    public function saveForumThread(array $values, $forumId)
    {
        require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';

        $sessionId = $this->session ? $this->session->getId() : 0;
        $forum = get_forums($forumId, $this->course->getCode(), true, $sessionId);
        $courseInfo = api_get_course_info($this->course->getCode());
        $thread = store_thread($forum, $values, $courseInfo, false, $this->user->getId(), $sessionId);

        return [
            'registered' => $thread->getIid(),
        ];
    }

    /**
     * @return array
     */
    public function getUsersCampus(array $params)
    {
        $conditions = [
            'status' => $params['status'],
        ];
        $idCampus = $params['id_campus'];
        $users = UserManager::get_user_list($conditions, ['firstname'], false, false, $idCampus);
        $list = [];
        foreach ($users as $item) {
            $listTemp = [
                'id' => $item['user_id'],
                'firstname' => $item['firstname'],
                'lastname' => $item['lastname'],
                'email' => $item['email'],
            ];
            $list[] = $listTemp;
        }

        return $list;
    }

    /**
     * @return array
     */
    public function getCoursesCampus(array $params)
    {
        $idCampus = $params['id_campus'];

        $courseList = CourseManager::get_courses_list(
            0, //offset
            0, //howMany
            1, //$orderby = 1
            'ASC',
            -1, //visibility
            null,
            $idCampus, //$urlId
            true //AlsoSearchCode
        );

        return $courseList;
    }

    /**
     * @return array
     */
    public function addSession(array $params)
    {
        $name = $params['name'];
        $coach_username = (int) $params['coach_username'];
        $startDate = $params['access_start_date'];
        $endDate = $params['access_end_date'];
        $displayStartDate = $startDate;
        $displayEndDate = $endDate;
        $description = $params['description'];
        $idUrlCampus = $params['id_campus'];
        $extraFields = isset($params['extra']) ? $params['extra'] : [];

        $return = SessionManager::create_session(
            $name,
            $startDate,
            $endDate,
            $displayStartDate,
            $displayEndDate,
            null,
            null,
            $coach_username,
            null,
            1,
            false,
            null,
            $description,
            1,
            $extraFields,
            null,
            false,
            $idUrlCampus
        );

        if ($return) {
            $out = [
                'status' => true,
                'message' => get_lang('ANewSessionWasCreated'),
                'id_session' => $return,
            ];
        } else {
            $out = [
                'status' => false,
                'message' => get_lang('ErrorOccurred'),
            ];
        }

        return $out;
    }

    /**
     * @return array
     */
    public function addCourse(array $courseParam)
    {
        $results = [];
        $idCampus = isset($courseParam['id_campus']) ? $courseParam['id_campus'] : 1;
        $title = isset($courseParam['title']) ? $courseParam['title'] : '';
        $wantedCode = isset($courseParam['wanted_code']) ? $courseParam['wanted_code'] : null;
        $diskQuota = isset($courseParam['disk_quota']) ? $courseParam['disk_quota'] : '100';
        $visibility = isset($courseParam['visibility']) ? (int) $courseParam['visibility'] : null;

        if (isset($courseParam['visibility'])) {
            if ($courseParam['visibility'] &&
                $courseParam['visibility'] >= 0 &&
                $courseParam['visibility'] <= 3
            ) {
                $visibility = (int) $courseParam['visibility'];
            }
        }

        $params = [];
        $params['title'] = $title;
        $params['wanted_code'] = 'CAMPUS_'.$idCampus.'_'.$wantedCode;
        $params['user_id'] = $this->user->getId();
        $params['visibility'] = $visibility;
        $params['disk_quota'] = $diskQuota;

        $courseInfo = CourseManager::create_course($params, $params['user_id'], $idCampus);

        if (!empty($courseInfo)) {
            $results['status'] = true;
            $results['code_course'] = $courseInfo['code'];
            $results['title_course'] = $courseInfo['title'];
            $results['message'] = sprintf(get_lang('CourseXAdded'), $courseInfo['code']);
        } else {
            $results['status'] = false;
            $results['message'] = get_lang('CourseCreationFailed');
        }

        return $results;
    }

    /**
     * @param $userParam
     *
     * @throws Exception
     *
     * @return array
     */
    public function addUser($userParam)
    {
        $firstName = $userParam['firstname'];
        $lastName = $userParam['lastname'];
        $status = $userParam['status'];
        $email = $userParam['email'];
        $loginName = $userParam['loginname'];
        $password = $userParam['password'];

        $official_code = '';
        $language = '';
        $phone = '';
        $picture_uri = '';
        $auth_source = PLATFORM_AUTH_SOURCE;
        $expiration_date = '';
        $active = 1;
        $hr_dept_id = 0;
        $extra = null;

        $original_user_id_name = $userParam['original_user_id_name'];
        $original_user_id_value = $userParam['original_user_id_value'];

        $extra_list = isset($userParam['extra']) ? $userParam['extra'] : [];
        if (isset($userParam['language'])) {
            $language = $userParam['language'];
        }
        if (isset($userParam['phone'])) {
            $phone = $userParam['phone'];
        }
        if (isset($userParam['expiration_date'])) {
            $expiration_date = $userParam['expiration_date'];
        }

        // Default language.
        if (empty($language)) {
            $language = api_get_setting('platformLanguage');
        }

        // First check wether the login already exists.
        if (!UserManager::is_username_available($loginName)) {
            throw new Exception(get_lang('UserNameNotAvailable'));
        }

        $userId = UserManager::create_user(
            $firstName,
            $lastName,
            $status,
            $email,
            $loginName,
            $password,
            $official_code,
            $language,
            $phone,
            $picture_uri,
            $auth_source,
            $expiration_date,
            $active,
            $hr_dept_id
        );

        if (empty($userId)) {
            throw new Exception(get_lang('UserNotRegistered'));
        }

        if (api_is_multiple_url_enabled()) {
            if (api_get_current_access_url_id() != -1) {
                UrlManager::add_user_to_url(
                    $userId,
                    api_get_current_access_url_id()
                );
            } else {
                UrlManager::add_user_to_url($userId, 1);
            }
        } else {
            // We add by default the access_url_user table with access_url_id = 1
            UrlManager::add_user_to_url($userId, 1);
        }

        // Save new field label into user_field table.
        UserManager::create_extra_field(
            $original_user_id_name,
            1,
            $original_user_id_name,
            ''
        );
        // Save the external system's id into user_field_value table.
        UserManager::update_extra_field_value(
            $userId,
            $original_user_id_name,
            $original_user_id_value
        );

        if (is_array($extra_list) && count($extra_list) > 0) {
            foreach ($extra_list as $extra) {
                $extra_field_name = $extra['field_name'];
                $extra_field_value = $extra['field_value'];
                // Save new field label into user_field table.
                UserManager::create_extra_field(
                    $extra_field_name,
                    1,
                    $extra_field_name,
                    ''
                );
                // Save the external system's id into user_field_value table.
                UserManager::update_extra_field_value(
                    $userId,
                    $extra_field_name,
                    $extra_field_value
                );
            }
        }

        return [$userId];
    }

    /**
     * Subscribe User to Course.
     *
     * @param array $params
     *
     * @return array
     */
    public function subscribeUserToCourse($params)
    {
        $course_id = $params['course_id'];
        $course_code = $params['course_code'];
        $user_id = $params['user_id'];
        if (!$course_id && !$course_code) {
            return [false];
        }
        if (!$course_code) {
            $course_code = CourseManager::get_course_code_from_course_id($course_id);
        }
        if (CourseManager::subscribeUser($user_id, $course_code)) {
            return [true];
        } else {
            return [false];
        }

        return [true];
    }

    public function deleteUserMessage($messageId, $messageType)
    {
        if ($messageType === "sent") {
            return MessageManager::delete_message_by_user_sender($this->user->getId(), $messageId);
        } else {
            return MessageManager::delete_message_by_user_receiver($this->user->getId(), $messageId);
        }
    }

    public function setMessageRead($messageId)
    {
        MessageManager::update_message($this->user->getId(), $messageId);
    }

    /**
     * Add Campus Virtual.
     *
     * @param array Params Campus
     *
     * @return array
     */
    public function createCampusURL($params)
    {
        $urlCampus = Security::remove_XSS($params['url']);
        $description = Security::remove_XSS($params['description']);

        $active = isset($params['active']) ? intval($params['active']) : 0;
        $num = UrlManager::url_exist($urlCampus);
        if ($num == 0) {
            // checking url
            if (substr($urlCampus, strlen($urlCampus) - 1, strlen($urlCampus)) == '/') {
                $idCampus = UrlManager::add($urlCampus, $description, $active, true);
            } else {
                //create
                $idCampus = UrlManager::add($urlCampus.'/', $description, $active, true);
            }

            return [
                'status' => true,
                'id_campus' => $idCampus,
            ];
        }

        return [
            'status' => false,
            'id_campus' => 0,
        ];
    }

    /**
     * Edit Campus Virtual.
     *
     * @param array Params Campus
     *
     * @return array
     */
    public function editCampusURL($params)
    {
        $urlCampus = Security::remove_XSS($params['url']);
        $description = Security::remove_XSS($params['description']);

        $active = isset($params['active']) ? intval($params['active']) : 0;
        $url_id = isset($params['id']) ? intval($params['id']) : 0;

        if (!empty($url_id)) {
            //we can't change the status of the url with id=1
            if ($url_id == 1) {
                $active = 1;
            }
            //checking url
            if (substr($urlCampus, strlen($urlCampus) - 1, strlen($urlCampus)) == '/') {
                UrlManager::update($url_id, $urlCampus, $description, $active);
            } else {
                UrlManager::update($url_id, $urlCampus.'/', $description, $active);
            }

            return [true];
        }

        return [false];
    }

    /**
     * Delete Campus Virtual.
     *
     * @param array Params Campus
     *
     * @return array
     */
    public function deleteCampusURL($params)
    {
        $url_id = isset($params['id']) ? intval($params['id']) : 0;

        $result = UrlManager::delete($url_id);
        if ($result) {
            return [
                'status' => true,
                'message' => get_lang('URLDeleted'),
            ];
        } else {
            return [
                'status' => false,
                'message' => get_lang('Error'),
            ];
        }
    }

    /**
     * @throws Exception
     *
     * @return array
     */
    public function addCoursesSession(array $params)
    {
        $sessionId = $params['id_session'];
        $courseList = $params['list_courses'];
        $importAssignments = isset($params['import_assignments']) ? 1 === (int) $params['import_assignments'] : false;

        $result = SessionManager::add_courses_to_session(
            $sessionId,
            $courseList,
            true,
            false,
            false,
            $importAssignments
        );

        if ($result) {
            return [
                'status' => $result,
                'message' => get_lang('Updated'),
            ];
        }

        return [
            'status' => $result,
            'message' => get_lang('ErrorOccurred'),
        ];
    }

    /**
     * @return array
     */
    public function addUsersSession(array $params)
    {
        $sessionId = $params['id_session'];
        $userList = $params['list_users'];

        if (!is_array($userList)) {
            $userList = [];
        }

        SessionManager::subscribeUsersToSession(
            $sessionId,
            $userList,
            null,
            false
        );

        return [
            'status' => true,
            'message' => get_lang('UsersAdded'),
        ];
    }

    /**
     * Creates a session from a model session.
     *
     * @param $modelSessionId
     * @param $sessionName
     * @param $startDate
     * @param $endDate
     *
     * @throws Exception
     *
     * @return int, the id of the new session
     */
    public function createSessionFromModel($modelSessionId, $sessionName, $startDate, $endDate, array $extraFields = [])
    {
        if (empty($modelSessionId) || empty($sessionName) || empty($startDate) || empty($endDate)) {
            throw new Exception(get_lang('NoData'));
        }

        if (!SessionManager::isValidId($modelSessionId)) {
            throw new Exception(get_lang('ModelSessionDoesNotExist'));
        }

        $modelSession = SessionManager::fetch($modelSessionId);

        $modelSession['accessUrlId'] = 1;
        if (api_is_multiple_url_enabled()) {
            if (api_get_current_access_url_id() != -1) {
                $modelSession['accessUrlId'] = api_get_current_access_url_id();
            }
        }

        $newSessionId = SessionManager::create_session(
            $sessionName,
            $startDate,
            $endDate,
            $startDate,
            $endDate,
            $startDate,
            $endDate,
            $modelSession['id_coach'],
            $modelSession['session_category_id'],
            $modelSession['visibility'],
            false,
            $modelSession['duration'],
            $modelSession['description'],
            $modelSession['show_description'],
            $extraFields,
            $modelSession['session_admin_id'],
            $modelSession['send_subscription_notification'],
            $modelSession['accessUrlId']
        );

        if (empty($newSessionId)) {
            throw new Exception(get_lang('SessionNotRegistered'));
        }

        if (is_string($newSessionId)) {
            throw new Exception($newSessionId);
        }

        $promotionId = $modelSession['promotion_id'];
        if ($promotionId) {
            $sessionList = array_keys(SessionManager::get_all_sessions_by_promotion($promotionId));
            $sessionList[] = $newSessionId;
            SessionManager::subscribe_sessions_to_promotion($modelSession['promotion_id'], $sessionList);
        }

        $modelExtraFields = [];
        $fields = SessionManager::getFilteredExtraFields($modelSessionId);
        if (is_array($fields) and !empty($fields)) {
            foreach ($fields as $field) {
                $modelExtraFields[$field['variable']] = $field['value'];
            }
        }
        $allExtraFields = array_merge($modelExtraFields, $extraFields);
        foreach ($allExtraFields as $name => $value) {
            // SessionManager::update_session_extra_field_value returns false when no row is changed,
            // which can happen since extra field values are initialized by SessionManager::create_session
            // therefore we do not throw an exception when false is returned
            SessionManager::update_session_extra_field_value($newSessionId, $name, $value);
        }

        $courseList = array_keys(SessionManager::get_course_list_by_session_id($modelSessionId));
        if (is_array($courseList)
            && !empty($courseList)
            && !SessionManager::add_courses_to_session($newSessionId, $courseList)) {
            throw new Exception(get_lang('CoursesNotAddedToSession'));
        }

        if (api_is_multiple_url_enabled()) {
            if (api_get_current_access_url_id() != -1) {
                UrlManager::add_session_to_url(
                    $newSessionId,
                    api_get_current_access_url_id()
                );
            } else {
                UrlManager::add_session_to_url($newSessionId, 1);
            }
        } else {
            UrlManager::add_session_to_url($newSessionId, 1);
        }

        return $newSessionId;
    }

    /**
     * subscribes a user to a session.
     *
     * @param int    $sessionId the session id
     * @param string $loginName the user's login name
     *
     * @throws Exception
     *
     * @return boolean, whether it worked
     */
    public function subscribeUserToSessionFromUsername($sessionId, $loginName)
    {
        if (!SessionManager::isValidId($sessionId)) {
            throw new Exception(get_lang('SessionNotFound'));
        }

        $userId = UserManager::get_user_id_from_username($loginName);
        if (false === $userId) {
            throw new Exception(get_lang('UserNotFound'));
        }

        $subscribed = SessionManager::subscribeUsersToSession(
            $sessionId,
            [$userId],
            SESSION_VISIBLE_READ_ONLY,
            false
        );
        if (!$subscribed) {
            throw new Exception(get_lang('UserNotSubscribed'));
        }

        return true;
    }

    /**
     * finds the session which has a specific value in a specific extra field.
     *
     * @param $fieldName
     * @param $fieldValue
     *
     * @throws Exception when no session matched or more than one session matched
     *
     * @return int, the matching session id
     */
    public function getSessionFromExtraField($fieldName, $fieldValue)
    {
        // find sessions that that have value in field
        $valueModel = new ExtraFieldValue('session');
        $sessionIdList = $valueModel->get_item_id_from_field_variable_and_field_value(
            $fieldName,
            $fieldValue,
            false,
            false,
            true
        );

        // throw if none found
        if (empty($sessionIdList)) {
            throw new Exception(get_lang('NoSessionMatched'));
        }

        // throw if more than one found
        if (count($sessionIdList) > 1) {
            throw new Exception(get_lang('MoreThanOneSessionMatched'));
        }

        // return sessionId
        return intval($sessionIdList[0]['item_id']);
    }

    /**
     * updates a user identified by its login name.
     *
     * @param array $parameters
     *
     * @throws Exception on failure
     *
     * @return boolean, true on success
     */
    public function updateUserFromUserName($parameters)
    {
        // find user
        $userId = null;
        if (!is_array($parameters) || empty($parameters)) {
            throw new Exception('NoData');
        }
        foreach ($parameters as $name => $value) {
            if (strtolower($name) === 'loginname') {
                $userId = UserManager::get_user_id_from_username($value);
                if (false === $userId) {
                    throw new Exception(get_lang('UserNotFound'));
                }
                break;
            }
        }
        if (is_null($userId)) {
            throw new Exception(get_lang('NoData'));
        }
        /** @var User $user */
        $user = UserManager::getRepository()->find($userId);
        if (empty($user)) {
            throw new Exception(get_lang('CouldNotLoadUser'));
        }

        // tell the world we are about to update a user
        $hook = HookUpdateUser::create();
        if (!empty($hook)) {
            $hook->notifyUpdateUser(HOOK_EVENT_TYPE_PRE);
        }

        // apply submitted modifications
        foreach ($parameters as $name => $value) {
            switch (strtolower($name)) {
                case 'email':
                    $user->setEmail($value);
                    break;
                case 'enabled':
                    $user->setEnabled($value);
                    break;
                case 'lastname':
                    $user->setLastname($value);
                    break;
                case 'firstname':
                    $user->setFirstname($value);
                    break;
                case 'phone':
                    $user->setPhone($value);
                    break;
                case 'address':
                    $user->setAddress($value);
                    break;
                case 'roles':
                    $user->setRoles($value);
                    break;
                case 'profile_completed':
                    $user->setProfileCompleted($value);
                    break;
                case 'auth_source':
                    $user->setAuthSource($value);
                    break;
                case 'status':
                    $user->setStatus($value);
                    break;
                case 'official_code':
                    $user->setOfficialCode($value);
                    break;
                case 'picture_uri':
                    $user->setPictureUri($value);
                    break;
                case 'creator_id':
                    $user->setCreatorId($value);
                    break;
                case 'competences':
                    $user->setCompetences($value);
                    break;
                case 'diplomas':
                    $user->setDiplomas($value);
                    break;
                case 'openarea':
                    $user->setOpenArea($value);
                    break;
                case 'teach':
                    $user->setTeach($value);
                    break;
                case 'productions':
                    $user->setProductions($value);
                    break;
                case 'language':
                    $languages = api_get_languages();
                    if (!in_array($value, $languages['folder'])) {
                        throw new Exception(get_lang('LanguageUnavailable'));
                    }
                    $user->setLanguage($value);
                    break;
                case 'registration_date':
                    $user->setRegistrationDate($value);
                    break;
                case 'expiration_date':
                    $user->setExpirationDate(
                        new DateTime(
                            api_get_utc_datetime($value),
                            new DateTimeZone('UTC')
                        )
                    );
                    break;
                case 'active':
                    // see UserManager::update_user() usermanager.lib.php:1205
                    if ($user->getActive() != $value) {
                        $user->setActive($value);
                        Event::addEvent($value ? LOG_USER_ENABLE : LOG_USER_DISABLE, LOG_USER_ID, $userId);
                    }
                    break;
                case 'openid':
                    $user->setOpenId($value);
                    break;
                case 'theme':
                    $user->setTheme($value);
                    break;
                case 'hr_dept_id':
                    $user->setHrDeptId($value);
                    break;
                case 'extra':
                    if (is_array($value)) {
                        if (count($value) > 0) {
                            if (is_array($value[0])) {
                                foreach ($value as $field) {
                                    $fieldName = $field['field_name'];
                                    $fieldValue = $field['field_value'];
                                    if (!isset($fieldName) || !isset($fieldValue) ||
                                        !UserManager::update_extra_field_value($userId, $fieldName, $fieldValue)) {
                                        throw new Exception(get_lang('CouldNotUpdateExtraFieldValue').': '.print_r($field, true));
                                    }
                                }
                            } else {
                                foreach ($value as $fieldName => $fieldValue) {
                                    if (!UserManager::update_extra_field_value($userId, $fieldName, $fieldValue)) {
                                        throw new Exception(get_lang('CouldNotUpdateExtraFieldValue').': '.$fieldName);
                                    }
                                }
                            }
                        }
                    }
                    break;
                case 'username':
                case 'api_key':
                case 'action':
                case 'loginname':
                    break;
                case 'email_canonical':
                case 'locked':
                case 'expired':
                case 'credentials_expired':
                case 'credentials_expire_at':
                case 'expires_at':
                case 'salt':
                case 'last_login':
                case 'created_at':
                case 'updated_at':
                case 'confirmation_token':
                case 'password_requested_at':
                case 'password': // see UserManager::update_user usermanager.lib.php:1182
                case 'username_canonical':
                default:
                    throw new Exception(get_lang('UnsupportedUpdate')." '$name'");
            }
        }

        // save modifications
        UserManager::getManager()->updateUser($user, true);

        // tell the world we just updated this user
        if (!empty($hook)) {
            $hook->setEventData(['user' => $user]);
            $hook->notifyUpdateUser(HOOK_EVENT_TYPE_POST);
        }

        // invalidate cache for this user
        $cacheAvailable = api_get_configuration_value('apc');
        if ($cacheAvailable === true) {
            $apcVar = api_get_configuration_value('apc_prefix').'userinfo_'.$userId;
            if (apcu_exists($apcVar)) {
                apcu_delete($apcVar);
            }
        }

        return true;
    }

    /**
     * Returns whether a user login name exists.
     *
     * @param string $loginname the user login name
     *
     * @return bool whether the user login name exists
     */
    public function usernameExist($loginname)
    {
        return false !== api_get_user_info_from_username($loginname);
    }

    /**
     * This service roughly matches what the call to MDL's API core_course_get_contents function returns.
     *
     * @return array
     */
    public function getCourseQuizMdlCompat()
    {
        $userId = $this->user->getId();
        $courseId = $this->course->getId();
        $sessionId = $this->session ? $this->session->getId() : 0;

        $toolVisibility = CourseHome::getToolVisibility(TOOL_QUIZ, $courseId, $sessionId);

        $json = [
            "id" => $this->course->getId(),
            "name" => get_lang('Exercises'),
            "visible" => (int) $toolVisibility,
            "summary" => '',
            "summaryformat" => 1,
            "section" => 1,
            "hiddenbynumsections" => 0,
            "uservisible" => $toolVisibility,
            "modules" => [],
        ];

        $quizIcon = Display::return_icon('quiz.png', '', [], ICON_SIZE_SMALL, false, true);

        $json['modules'] = array_map(
            function (array $exercise) use ($quizIcon) {
                return [
                    'id' => $exercise['id'],
                    'url' => $exercise['url'],
                    'name' => $exercise['name'],
                    'instance' => 1,
                    'visible' => 1,
                    'uservisible' => true,
                    'visibleoncoursepage' => 0,
                    'modicon' => $quizIcon,
                    'modname' => 'quiz',
                    'modplural' => get_lang('Exercises'),
                    'availability' => null,
                    'indent' => 0,
                    'onclick' => '',
                    'afterlink' => null,
                    'customdata' => "",
                    'noviewlink' => false,
                    'completion' => (int) ($exercise[1] > 0),
                ];
            },
            Exercise::exerciseGrid(0, '', $userId, $courseId, $sessionId, true)
        );

        return [$json];
    }

    /**
     * @param array $additionalParams Optional
     *
     * @return string
     */
    private function encodeParams(array $additionalParams = [])
    {
        $params = array_merge(
            $additionalParams,
            [
                'api_key' => $this->apiKey,
                'username' => $this->user->getUsername(),
            ]
        );

        return json_encode($params);
    }
}

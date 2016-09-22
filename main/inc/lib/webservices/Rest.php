<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CourseBundle\Entity\Repository\CAnnouncementRepository;
use Chamilo\CourseBundle\Entity\Repository\CNotebookRepository;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\UserBundle\Entity\User;

/**
 * Class RestApi
 */
class Rest extends WebService
{
    const SERVIVE_NAME = 'MsgREST';
    const EXTRA_FIELD_GCM_REGISTRATION = 'gcm_registration_id';

    const GET_AUTH = 'authenticate';
    const GET_USER_MESSAGES = 'user_messages';
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
    const SAVE_FORUM_POST = 'save_forum_post';
    const GET_USER_SESSIONS = 'user_sessions';
    const SAVE_USER_MESSAGE = 'save_user_message';
    const GET_MESSAGE_USERS = 'message_users';
    const SAVE_COURSE_NOTEBOOK = 'save_course_notebook';
    const SAVE_FORUM_THREAD = 'save_forum_thread';

    const EXTRAFIELD_GCM_ID = 'gcm_registration_id';

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
     * @param string $username
     * @param string $apiKey
     */
    public function __construct($username, $apiKey)
    {
        parent::__construct($username, $apiKey);
    }

    /**
     * Set the current course
     * @param int $id
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

    /** Set the current session
     * @param int $id
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
     * @param string $username
     * @param string $apiKeyToValidate
     * @return Rest
     * @throws Exception
     */
    public static function validate($username, $apiKeyToValidate)
    {
        $apiKey = self::findUserApiKey($username, self::SERVIVE_NAME);

        if ($apiKey != $apiKeyToValidate) {
            throw new Exception(get_lang('InvalidApiKey'));
        }

        return new self($username, $apiKey);
    }

    /**
     * Create the gcm_registration_id extra field for users
     */
    public static function init()
    {
        $extraField = new ExtraField('user');
        $fieldInfo = $extraField->get_handler_field_info_by_field_variable(self::EXTRA_FIELD_GCM_REGISTRATION);

        if (empty($fieldInfo)) {
            $extraField->save([
                'variable' => self::EXTRA_FIELD_GCM_REGISTRATION,
                'field_type' => ExtraField::FIELD_TYPE_TEXT,
                'display_text' => self::EXTRA_FIELD_GCM_REGISTRATION
            ]);
        }
    }

    /**
     * @param string $registrationId
     * @return bool
     */
    public function setGcmId($registrationId)
    {
        $registrationId = Security::remove_XSS($registrationId);
        $extraFieldValue = new ExtraFieldValue('user');

        return $extraFieldValue->save([
            'variable' => self::EXTRA_FIELD_GCM_REGISTRATION,
            'value' => $registrationId,
            'item_id' => $this->user->getId()
        ]);
    }

    /**
     * @param int $lastMessageId
     * @return array
     */
    public function getUserMessages($lastMessageId = 0)
    {
        $lastMessages = MessageManager::getMessagesFromLastReceivedMessage($this->user->getId(), $lastMessageId);
        $messages = [];

        foreach ($lastMessages as $message) {
            $hasAttachments = MessageManager::hasAttachments($message['id']);

            $messages[] = array(
                'id' => $message['id'],
                'title' => $message['title'],
                'sender' => array(
                    'id' => $message['user_id'],
                    'lastname' => $message['lastname'],
                    'firstname' => $message['firstname'],
                    'completeName' => api_get_person_name($message['firstname'], $message['lastname']),
                ),
                'sendDate' => $message['send_date'],
                'content' => $message['content'],
                'hasAttachments' => $hasAttachments,
                'url' => ''
            );
        }

        return $messages;
    }

    /**
     * Get the user courses
     * @return array
     */
    public function getUserCourses()
    {
        $courses = CourseManager::get_courses_list_by_user_id($this->user->getId());
        $data = [];

        foreach ($courses as $courseId) {
            /** @var Course $course */
            $course = Database::getManager()->find('ChamiloCoreBundle:Course', $courseId['real_id']);
            $teachers = CourseManager::get_teacher_list_from_course_code_to_string($course->getCode());

            $data[] = [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'code' => $course->getCode(),
                'directory' => $course->getDirectory(),
                'urlPicture' => $course->getPicturePath(true),
                'teachers' => $teachers
            ];
        }

        return $data;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getCourseInfo()
    {
        $teachers = CourseManager::get_teacher_list_from_course_code_to_string($this->course->getCode());

        return [
            'id' => $this->course->getId(),
            'title' => $this->course->getTitle(),
            'code' => $this->course->getCode(),
            'directory' => $this->course->getDirectory(),
            'urlPicture' => $this->course->getPicturePath(true),
            'teachers' => $teachers
        ];
    }

    /**
     * Get the course descriptions
     * @return array
     * @throws Exception
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
                'content' => str_replace('src="/', 'src="' . api_get_path(WEB_PATH), $description->get_content())
            ];
        }

        return $results;
    }

    /**
     * @param int $directoryId
     * @return array
     * @throws Exception
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
        require_once api_get_path(LIBRARY_PATH) . 'fileDisplay.lib.php';

        $courseInfo = api_get_course_info_by_id($this->course->getId());
        $documents = DocumentManager::get_all_document_data(
            $courseInfo,
            $path,
            0,
            null,
            false,
            false,
            $sessionId
        );
        $results = [];

        if (is_array($documents)) {
            $webPath = api_get_path(WEB_CODE_PATH) . 'document/document.php?';

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
                    'url' => $webPath . http_build_query([
                        'username' => $this->user->getUsername(),
                        'api_key' => $this->apiKey,
                        'cidReq' => $this->course->getCode(),
                        'id_session' => $sessionId,
                        'gidReq' => 0,
                        'gradebook' => 0,
                        'origin' => '',
                        'action' => 'download',
                        'id' => $document['id']
                    ]),
                    'icon' => $icon,
                    'size' => format_file_size($document['size'])
                ];
            }
        }

        return $results;
    }

    /**
     * @param int $courseId
     * @return array
     * @throws Exception
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

        $announcements = array_map(function ($announcement) {
            return [
                'id' => intval($announcement['id']),
                'title' => strip_tags($announcement['title']),
                'creatorName' => strip_tags($announcement['username']),
                'date' => strip_tags($announcement['insert_date'])
            ];
        }, $announcements);

        return $announcements;
    }

    /**
     * @param int $announcementId
     * @return array
     * @throws Exception
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
            'id' => intval($announcement['announcement']->getIid()),
            'title' => $announcement['announcement']->getTitle(),
            'creatorName' => $announcement['item_property']->getInsertUser()->getCompleteName(),
            'date' => api_convert_and_format_date($announcement['item_property']->getInsertDate(), DATE_TIME_FORMAT_LONG_24H),
            'content' => AnnouncementManager::parse_content(
                $this->user->getId(),
                $announcement['announcement']->getContent(),
                $this->course->getCode(),
                $sessionId
            )
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getCourseAgenda()
    {
        $sessionId = $this->session ? $this->session->getId() : 0;

        $agenda = new Agenda($this->user->getId(), $this->course->getId(), $sessionId);
        $agenda->setType('course');
        $result = $agenda->parseAgendaFilter(null);

        $start = new DateTime('now');
        $start->modify('first day of month');
        $end = new DateTime('now');
        $end->modify('first day of month');

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
                    'id' => intval($event['unique_id']),
                    'title' => $event['title'],
                    'content' => str_replace('src="/', 'src="' . $webPath, $event['description']),
                    'startDate' => $event['start_date_localtime'],
                    'endDate' => $event['end_date_localtime'],
                    'isAllDay' => $event['allDay'] ? true : false
                ];
            },
            $events
        );
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getCourseNotebooks()
    {
        $em = Database::getManager();
        /** @var CNotebookRepository $notebooksRepo */
        $notebooksRepo = $em->getRepository('ChamiloCourseBundle:CNotebook');
        $notebooks = $notebooksRepo->findByUser($this->user, $this->course, $this->session);

        return array_map(
            function (\Chamilo\CourseBundle\Entity\CNotebook $notebook) {
                return [
                    'id' => $notebook->getIid(),
                    'title' => $notebook->getTitle(),
                    'description' => $notebook->getDescription(),
                    'creationDate' => api_format_date(
                        $notebook->getCreationDate()->getTimestamp()
                    ),
                    'updateDate' => api_format_date(
                        $notebook->getUpdateDate()->getTimestamp()
                    )
                ];
            },
            $notebooks
        );
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getCourseForumCategories()
    {
        $sessionId = $this->session ? $this->session->getId() : 0;
        $webCoursePath = api_get_path(WEB_COURSE_PATH) . $this->course->getDirectory() . '/upload/forum/images/';

        require_once api_get_path(SYS_CODE_PATH) . 'forum/forumfunction.inc.php';

        $categoriesFullData = get_forum_categories('', $this->course->getId(), $sessionId);
        $categories = [];
        $includeGroupsForums = api_get_setting('display_groups_forum_in_general_tool') === 'true';
        $forumsFullData = get_forums('', $this->course->getCode(), $includeGroupsForums, $sessionId);
        $forums = [];

        foreach ($forumsFullData as $forumId => $forumInfo) {
            $forum = [
                'id' => intval($forumInfo['iid']),
                'catId' => intval($forumInfo['forum_category']),
                'title' => $forumInfo['forum_title'],
                'description' => $forumInfo['forum_comment'],
                'image' => $forumInfo['forum_image'] ? ($webCoursePath . $forumInfo['forum_image']) : '',
                'numberOfThreads' => intval($forumInfo['number_of_threads']),
                'lastPost' => null
            ];

            $lastPostInfo = get_last_post_information($forumId, false, $this->course->getId());

            if ($lastPostInfo) {
                $forum['lastPost'] = [
                    'date' => api_convert_and_format_date($lastPostInfo['last_post_date']),
                    'user' => api_get_person_name(
                        $lastPostInfo['last_poster_firstname'],
                        $lastPostInfo['last_poster_lastname']
                    )
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
                'id' => intval($category['iid']),
                'title' => $category['cat_title'],
                'catId' => intval($category['cat_id']),
                'description' => $category['cat_comment'],
                'forums' => $categoryForums,
                'courseId' => $this->course->getId()
            ];
        }

        return $categories;
    }

    /**
     * @param int $forumId
     * @return array
     * @throws Exception
     */
    public function getCourseForum($forumId)
    {
        require_once api_get_path(SYS_CODE_PATH) . 'forum/forumfunction.inc.php';

        $forumInfo = get_forums($forumId, $this->course->getCode());

        if (!isset($forumInfo['iid'])) {
            throw new Exception(get_lang('NoForum'));
        }

        $webCoursePath = api_get_path(WEB_COURSE_PATH) . $this->course->getDirectory() . '/upload/forum/images/';
        $forum = [
            'id' => $forumInfo['iid'],
            'title' => $forumInfo['forum_title'],
            'description' => $forumInfo['forum_comment'],
            'image' => $forumInfo['forum_image'] ? ($webCoursePath . $forumInfo['forum_image']) : '',
            'threads' => []
        ];

        $threads = get_threads($forumInfo['iid'], $this->course->getId());

        foreach ($threads as $thread) {
            $forum['threads'][] = [
                'id' => $thread['iid'],
                'title' => $thread['thread_title'],
                'lastEditDate' => api_convert_and_format_date($thread['lastedit_date'], DATE_TIME_FORMAT_LONG_24H),
                'numberOfReplies' => $thread['thread_replies'],
                'numberOfViews' => $thread['thread_views'],
                'author' => api_get_person_name($thread['firstname'], $thread['lastname'])
            ];
        }

        return $forum;
    }

    /**
     * @param int $forumId
     * @param int $threadId
     * @return array
     */
    public function getCourseForumThread($forumId, $threadId)
    {
        require_once api_get_path(SYS_CODE_PATH) . 'forum/forumfunction.inc.php';

        $threadInfo = get_thread_information($forumId, $threadId);

        $thread = [
            'id' => intval($threadInfo['iid']),
            'cId' => intval($threadInfo['c_id']),
            'title' => $threadInfo['thread_title'],
            'forumId' => intval($threadInfo['forum_id']),
            'posts' => []
        ];

        $forumInfo = get_forums($threadInfo['forum_id'], $this->course->getCode());

        $postsInfo = getPosts($forumInfo, $threadInfo['iid'], 'ASC');

        foreach ($postsInfo as $postInfo) {
            $thread['posts'][] = [
                'id' => $postInfo['iid'],
                'title' => $postInfo['post_title'],
                'text' => $postInfo['post_text'],
                'author' => api_get_person_name($postInfo['firstname'], $postInfo['lastname']),
                'date' => api_convert_and_format_date($postInfo['post_date'], DATE_TIME_FORMAT_LONG_24H),
                'parentId' => $postInfo['post_parent_id']
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
            'pictureUri' => $pictureInfo['dir'] . $pictureInfo['file'],
            'fullName' => $this->user->getCompleteName(),
            'username' => $this->user->getUsername(),
            'officialCode' => $this->user->getOfficialCode(),
            'phone' => $this->user->getPhone(),
            'extra' => []
        ];

        $fieldValue = new ExtraFieldValue('user');
        $extraInfo = $fieldValue->getAllValuesForAnItem($this->user->getId(), true);

        foreach ($extraInfo as $extra) {
            /** @var ExtraFieldValues $extraValue */
            $extraValue = $extra['value'];

            $result['extra'][] = [
                'title' => $extraValue->getField()->getDisplayText(true),
                'value' => $extraValue->getValue()
            ];
        }

        return $result;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getCourseLearnPaths()
    {
        $sessionId = $this->session ? $this->session->getId() : 0;
        $categoriesTempList = learnpath::getCategories($this->course->getId());

        $categoryNone = new \Chamilo\CourseBundle\Entity\CLpCategory();
        $categoryNone->setId(0);
        $categoryNone->setName(get_lang('WithOutCategory'));
        $categoryNone->setPosition(0);

        $categories = array_merge([$categoryNone], $categoriesTempList);

        $categoryData = array();

        /** @var CLpCategory $category */
        foreach ($categories as $category) {
            $learnPathList = new LearnpathList(
                $this->user->getId(),
                $this->course->getCode(),
                $sessionId,
                null,
                false,
                $category->getId()
            );

            $flatLpList = $learnPathList->get_flat_list();

            if (empty($flatLpList)) {
                continue;
            }

            $listData = array();

            foreach ($flatLpList as $lpId => $lpDetails) {
                if ($lpDetails['lp_visibility'] == 0) {
                    continue;
                }

                if (!learnpath::is_lp_visible_for_student(
                    $lpId,
                    $this->user->getId(),
                    $this->course->getCode(),
                    $sessionId
                )) {
                    continue;
                }

                $timeLimits = false;

                //This is an old LP (from a migration 1.8.7) so we do nothing
                if (empty($lpDetails['created_on']) && empty($lpDetails['modified_on'])) {
                    $timeLimits = false;
                }

                //Checking if expired_on is ON
                if (!empty($lpDetails['expired_on'])) {
                    $timeLimits = true;
                }

                if ($timeLimits) {
                    if (!empty($lpDetails['publicated_on']) && !empty($lpDetails['expired_on'])) {
                        $startTime = api_strtotime($lpDetails['publicated_on'], 'UTC');
                        $endTime = api_strtotime($lpDetails['expired_on'], 'UTC');
                        $now = time();
                        $isActivedTime = false;

                        if ($now > $startTime && $endTime > $now) {
                            $isActivedTime = true;
                        }

                        if (!$isActivedTime) {
                            continue;
                        }
                    }
                }

                $progress = learnpath::getProgress($lpId, $this->user->getId(), $this->course->getId(), $sessionId);

                $listData[] = array(
                    'id' => $lpId,
                    'title' => Security::remove_XSS($lpDetails['lp_name']),
                    'progress' => intval($progress),
                    'url' => api_get_path(WEB_CODE_PATH) . 'webservices/api/v2.php?' . http_build_query([
                        'hash' => $this->encodeParams([
                            'action' => 'course_learnpath',
                            'lp_id' => $lpId,
                            'course' => $this->course->getId(),
                            'session' => $sessionId
                        ])
                    ])
                );
            }

            if (empty($listData)) {
                continue;
            }

            $categoryData[] = array(
                'id' => $category->getId(),
                'name' => $category->getName(),
                'learnpaths' => $listData
            );
        }

        return $categoryData;
    }

    /**
     * @param array $additionalParams Optional
     * @return string
     */
    private function encodeParams(array $additionalParams = [])
    {
        $params = array_merge($additionalParams, [
            'api_key' => $this->apiKey,
            'username' => $this->user->getUsername(),
        ]);

        $strParams = serialize($params);

        $b64Encoded = base64_encode($strParams);

        return str_replace(['+', '/', '='], ['-', '_', '.'], $b64Encoded);
    }

    /**
     * @param string $encoded
     * @return array
     */
    public static function decodeParams($encoded){
        $decoded = str_replace(['-', '_', '.'], ['+', '/', '='], $encoded);
        $mod4 = strlen($decoded) % 4;

        if ($mod4) {
            $decoded .= substr('====', $mod4);
        }

        $b64Decoded = base64_decode($decoded);

        return unserialize($b64Decoded);
    }

    /**
     * Start login for a user. Then make a redirect to show the learnpath
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

        $url = api_get_path(WEB_CODE_PATH) . 'lp/lp_controller.php?' . http_build_query([
            'cidReq' => $this->course->getCode(),
            'id_session' => $sessionId,
            'gidReq' => 0,
            'gradebook' => 0,
            'origin' => '',
            'action' => 'view',
            'lp_id' => intval($lpId),
            'isStudentView' => 'true'
        ]);

        header("Location: $url");
        exit;
    }

    /**
     * @param array $postValues
     * @param int $forumId
     * @return array
     */
    public function saveForumPost(array $postValues, $forumId)
    {
        require_once api_get_path(SYS_CODE_PATH) . 'forum/forumfunction.inc.php';

        $forum = get_forums($forumId, $this->course->getCode());

        store_reply($forum, $postValues, $this->course->getId(), $this->user->getId());

        return [
            'registered' => true
        ];
    }

    /**
     * Get the list of sessions for current user
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

                    $sessionCourses[] = [
                        'visibility' => $course['visibility'],
                        'status' => $course['status'],
                        'id' => $courseInfo['real_id'],
                        'title' => $courseInfo['title'],
                        'code' => $courseInfo['code'],
                        'directory' => $courseInfo['directory'],
                        'pictureUrl' => $courseInfo['course_image_large']
                    ];
                }

                $categorySessions[] = [
                    'session_name' => $sessions['session_name'],
                    'session_id' => $sessions['session_id'],
                    'accessStartDate' => api_format_date($sessions['access_start_date'], DATE_TIME_FORMAT_SHORT),
                    'accessEndDate' => api_format_date($sessions['access_end_date'], DATE_TIME_FORMAT_SHORT),
                    'courses' => $sessionCourses
                ];
            }

            $data[] = [
                'id' => $category['session_category']['id'],
                'name' => $category['session_category']['name'],
                'sessions' => $categorySessions
            ];
        }

        return $data;
    }

    /**
     * @param string $subject
     * @param string $text
     * @param array $receivers
     * @return array
     */
    public function saveUserMessage($subject, $text, array $receivers)
    {
        foreach ($receivers as $userId) {
            MessageManager::send_message($userId, $subject, $text);
        }

        return [
            'sent' => true
        ];
    }

    /**
     * @param string $search
     * @return array
     */
    public function getMessageUsers($search)
    {
        /** @var UserRepository $repo */
        $repo = Database::getManager()
            ->getRepository('ChamiloUserBundle:User');

        $users = $repo->findUsersToSendMessage($this->user->getId(), $search);

        $showEmail = api_get_setting('show_email_addresses') === 'true';
        $data = [];

        /** @var User $user */
        foreach ($users as $user) {
            $userName = $user->getCompleteName();

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
     * @return bool
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
            'registered' => $noteBookId
        ];
    }

    /**
     * @param array $values
     * @param int $forumId
     * @return array
     */
    public function saveForumThread(array $values, $forumId)
    {
        require_once api_get_path(SYS_CODE_PATH) . 'forum/forumfunction.inc.php';

        $forum = get_forums($forumId, $this->course->getCode());
        $courseInfo = api_get_course_info($this->course->getCode());
        $sessionId = $this->session ? $this->session->getId() : 0;

        $id = store_thread($forum, $values, $courseInfo, false, $this->user->getId(), $sessionId);

        return [
            'registered' => $id
        ];
    }
}

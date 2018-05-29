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
    const SAVE_COURSE = 'save_course';
    const SAVE_USER = 'save_user';
    const SUBSCRIBE_USER_TO_COURSE = 'subscribe_user_to_course';
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
     *
     * @param string $username
     * @param string $apiKey
     */
    public function __construct($username, $apiKey)
    {
        parent::__construct($username, $apiKey);
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

    /** Set the current session
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
     * @param string $username
     * @param string $apiKeyToValidate
     *
     * @throws Exception
     *
     * @return Rest
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
     * Create the gcm_registration_id extra field for users.
     */
    public static function init()
    {
        $extraField = new ExtraField('user');
        $fieldInfo = $extraField->get_handler_field_info_by_field_variable(self::EXTRA_FIELD_GCM_REGISTRATION);

        if (empty($fieldInfo)) {
            $extraField->save([
                'variable' => self::EXTRA_FIELD_GCM_REGISTRATION,
                'field_type' => ExtraField::FIELD_TYPE_TEXT,
                'display_text' => self::EXTRA_FIELD_GCM_REGISTRATION,
            ]);
        }
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

        return $extraFieldValue->save([
            'variable' => self::EXTRA_FIELD_GCM_REGISTRATION,
            'value' => $registrationId,
            'item_id' => $this->user->getId(),
        ]);
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
                'url' => '',
            ];
        }

        return $messages;
    }

    /**
     * Get the user courses.
     *
     * @return array
     */
    public function getUserCourses()
    {
        $courses = CourseManager::get_courses_list_by_user_id($this->user->getId());
        $data = [];

        foreach ($courses as $courseId) {
            /** @var Course $course */
            $course = Database::getManager()->find('ChamiloCoreBundle:Course', $courseId['real_id']);
            $teachers = CourseManager::getTeacherListFromCourseCodeToString($course->getCode());

            $data[] = [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'code' => $course->getCode(),
                'directory' => $course->getDirectory(),
                'urlPicture' => $course->getPicturePath(true),
                'teachers' => $teachers,
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
            'urlPicture' => $this->course->getPicturePath(true),
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
                    'url' => $webPath.http_build_query([
                        'username' => $this->user->getUsername(),
                        'api_key' => $this->apiKey,
                        'cidReq' => $this->course->getCode(),
                        'id_session' => $sessionId,
                        'gidReq' => 0,
                        'gradebook' => 0,
                        'origin' => '',
                        'action' => 'download',
                        'id' => $document['id'],
                    ]),
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
                    'id' => intval($announcement['id']),
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
            'id' => intval($announcement['announcement']->getIid()),
            'title' => $announcement['announcement']->getTitle(),
            'creatorName' => $announcement['item_property']->getInsertUser()->getCompleteName(),
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
                    'id' => intval($event['unique_id']),
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
                'id' => intval($forumInfo['iid']),
                'catId' => intval($forumInfo['forum_category']),
                'title' => $forumInfo['forum_title'],
                'description' => $forumInfo['forum_comment'],
                'image' => $forumInfo['forum_image'] ? ($webCoursePath.$forumInfo['forum_image']) : '',
                'numberOfThreads' => isset($forumInfo['number_of_threads']) ? intval($forumInfo['number_of_threads']) : 0,
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
                'id' => intval($category['iid']),
                'title' => $category['cat_title'],
                'catId' => intval($category['cat_id']),
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
            'fullName' => $this->user->getCompleteName(),
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

            $listData = [];

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

                $listData[] = [
                    'id' => $lpId,
                    'title' => Security::remove_XSS($lpDetails['lp_name']),
                    'progress' => intval($progress),
                    'url' => api_get_path(WEB_CODE_PATH).'webservices/api/v2.php?'.http_build_query([
                        'hash' => $this->encodeParams([
                            'action' => 'course_learnpath',
                            'lp_id' => $lpId,
                            'course' => $this->course->getId(),
                            'session' => $sessionId,
                        ]),
                    ]),
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
     * @param string $encoded
     *
     * @return array
     */
    public static function decodeParams($encoded)
    {
        $decoded = json_decode($encoded);

        return $decoded;
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

        $url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.http_build_query([
            'cidReq' => $this->course->getCode(),
            'id_session' => $sessionId,
            'gidReq' => 0,
            'gradebook' => 0,
            'origin' => '',
            'action' => 'view',
            'lp_id' => intval($lpId),
            'isStudentView' => 'true',
        ]);

        header("Location: $url");
        exit;
    }

    /**
     * @param array $postValues
     * @param int   $forumId
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
     * @param array  $receivers
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
        /** @var UserRepository $repo */
        $repo = Database::getManager()->getRepository('ChamiloUserBundle:User');

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
     * @param array $values
     * @param int   $forumId
     *
     * @return array
     */
    public function saveForumThread(array $values, $forumId)
    {
        require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';

        $sessionId = $this->session ? $this->session->getId() : 0;
        $forum = get_forums($forumId, $this->course->getCode(), true, $sessionId);
        $courseInfo = api_get_course_info($this->course->getCode());
        $id = store_thread($forum, $values, $courseInfo, false, $this->user->getId(), $sessionId);

        return [
            'registered' => $id,
        ];
    }

    /**
     * @param array $courseParam
     *
     * @return array
     */
    public function addCourse(array $courseParam)
    {
        $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
        $extraList = [];
        $results = [];

        $title = isset($courseParam['title']) ? $courseParam['title'] : '';
        $categoryCode = isset($courseParam['category_code']) ? $courseParam['category_code'] : '';
        $wantedCode = isset($courseParam['wanted_code']) ? intval($courseParam['wanted_code']) : 0;
        $tutorName = isset($courseParam['tutor_name']) ? $courseParam['tutor_name'] : '';
        $courseLanguage = isset($courseParam['language']) ? $courseParam['language'] : null;
        $originalCourseIdName = isset($courseParam['original_course_id_name'])
            ? $courseParam['original_course_id_name']
            : null;
        $originalCourseIdValue = isset($courseParam['original_course_id_value'])
            ? $courseParam['original_course_id_value']
            : null;
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

        // Check whether exits $x_course_code into user_field_values table.
        $courseInfo = CourseManager::getCourseInfoFromOriginalId(
            $originalCourseIdValue,
            $originalCourseIdName
        );

        if (!empty($courseInfo)) {
            if ($courseInfo['visibility'] != 0) {
                $sql = "UPDATE $tableCourse SET
                            course_language = '".Database::escape_string($courseLanguage)."',
                            title = '".Database::escape_string($title)."',
                            category_code = '".Database::escape_string($categoryCode)."',
                            tutor_name = '".Database::escape_string($tutorName)."',
                            visual_code = '".Database::escape_string($wantedCode)."'";
                if ($visibility !== null) {
                    $sql .= ", visibility = $visibility ";
                }
                $sql .= " WHERE id = ".$courseInfo['real_id'];
                Database::query($sql);
                if (is_array($extraList) && count($extraList) > 0) {
                    foreach ($extraList as $extra) {
                        $extraFieldName = $extra['field_name'];
                        $extraFieldValue = $extra['field_value'];
                        // Save the external system's id into course_field_value table.
                        CourseManager::update_course_extra_field_value(
                            $courseInfo['code'],
                            $extraFieldName,
                            $extraFieldValue
                        );
                    }
                }
                $results[] = $courseInfo['code'];
            }
        }

        $params = [];
        $params['title'] = $title;
        $params['wanted_code'] = $wantedCode;
        $params['category_code'] = $categoryCode;
        $params['course_category'] = $categoryCode;
        $params['tutor_name'] = $tutorName;
        $params['course_language'] = $courseLanguage;
        $params['user_id'] = $this->user->getId();
        $params['visibility'] = $visibility;
        $params['disk_quota'] = $diskQuota;
        $params['subscribe'] = empty($courseParam['subscribe']) ? 0 : 1;
        $params['unsubscribe'] = empty($courseParam['unsubscribe']) ? 0 : 1;

        $courseInfo = CourseManager::create_course($params, $params['user_id']);

        if (!empty($courseInfo)) {
            $courseCode = $courseInfo['code'];

            // Save new field label into course_field table
            CourseManager::create_course_extra_field(
                $originalCourseIdName,
                1,
                $originalCourseIdName,
                ''
            );

            // Save the external system's id into user_field_value table.
            CourseManager::update_course_extra_field_value(
                $courseCode,
                $originalCourseIdName,
                $originalCourseIdValue
            );

            if (is_array($extraList) && count($extraList) > 0) {
                foreach ($extraList as $extra) {
                    $extraFieldName = $extra['field_name'];
                    $extraFieldValue = $extra['field_value'];
                    // Save new fieldlabel into course_field table.
                    CourseManager::create_course_extra_field(
                        $extraFieldName,
                        1,
                        $extraFieldName,
                        ''
                    );
                    // Save the external system's id into course_field_value table.
                    CourseManager::update_course_extra_field_value(
                        $courseCode,
                        $extraFieldName,
                        $extraFieldValue
                    );
                }
            }
            $results[] = $courseCode;
        }

        return $results;
    }

    /**
     * @param $user_param
     *
     * @return array
     */
    public function addUser($user_param)
    {
        $results = [];
        $orig_user_id_value = [];
        $userManager = UserManager::getManager();
        $userRepository = UserManager::getRepository();

        $firstName = $user_param['firstname'];
        $lastName = $user_param['lastname'];
        $status = $user_param['status'];
        $email = $user_param['email'];
        $loginName = $user_param['loginname'];
        $password = $user_param['password'];
        $official_code = '';
        $language = '';
        $phone = '';
        $picture_uri = '';
        $auth_source = PLATFORM_AUTH_SOURCE;
        $expiration_date = '';
        $active = 1;
        $hr_dept_id = 0;
        $extra = null;
        $original_user_id_name = $user_param['original_user_id_name'];
        $original_user_id_value = $user_param['original_user_id_value'];
        $orig_user_id_value[] = $user_param['original_user_id_value'];
        $extra_list = $user_param['extra'];
        if (!empty($user_param['language'])) {
            $language = $user_param['language'];
        }
        if (!empty($user_param['phone'])) {
            $phone = $user_param['phone'];
        }
        if (!empty($user_param['expiration_date'])) {
            $expiration_date = $user_param['expiration_date'];
        }

        // Default language.
        if (empty($language)) {
            $language = api_get_setting('platformLanguage');
        }

        // First check wether the login already exists.
        if (!UserManager::is_username_available($loginName)) {
            $results[] = 0;
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

        if ($userId) {
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
            $results[] = $userId;
        } else {
            $results[] = 0;
        }

        return $results;
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
        if (CourseManager::subscribe_user($user_id, $course_code)) {
            return [true];
        } else {
            return [false];
        }

        return [true];
    }

    /**
     * @param array $additionalParams Optional
     *
     * @return string
     */
    private function encodeParams(array $additionalParams = [])
    {
        $params = array_merge($additionalParams, [
            'api_key' => $this->apiKey,
            'username' => $this->user->getUsername(),
        ]);
        $encoded = json_encode($params);

        return $encoded;
    }
}

<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CNotebook;
use Chamilo\CourseBundle\Entity\Repository\CNotebookRepository;
use Chamilo\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * Class RestApi.
 */
class Rest extends WebService
{
    public const SERVICE_NAME = 'MsgREST';
    public const EXTRA_FIELD_GCM_REGISTRATION = 'gcm_registration_id';

    public const GET_AUTH = 'authenticate';
    public const SAVE_GCM_ID = 'gcm_id';
    public const LOGOUT = 'logout';

    public const GET_USER_MESSAGES = 'user_messages';
    public const GET_USER_MESSAGES_RECEIVED = 'user_messages_received';
    public const DELETE_USER_MESSAGE = 'delete_user_message';
    public const GET_USER_MESSAGES_SENT = 'user_messages_sent';
    public const GET_COUNT_NEW_MESSAGES = 'get_count_new_messages';
    public const SET_MESSAGE_READ = 'set_message_read';
    public const POST_USER_MESSAGE_READ = 'user_message_read';
    public const POST_USER_MESSAGE_UNREAD = 'user_message_unread';
    public const SAVE_USER_MESSAGE = 'save_user_message';
    public const GET_MESSAGE_USERS = 'message_users';
    public const VIEW_MESSAGE = 'view_message';

    public const GET_USER_COURSES = 'user_courses';
    public const GET_USER_COURSES_BY_DATES = 'user_courses_by_dates';
    public const GET_USER_SESSIONS = 'user_sessions';

    public const VIEW_PROFILE = 'view_user_profile';
    public const GET_PROFILE = 'user_profile';
    public const GET_PROFILES_BY_EXTRA_FIELD = 'users_profiles_by_extra_field';

    public const VIEW_MY_COURSES = 'view_my_courses';
    public const VIEW_COURSE_HOME = 'view_course_home';
    public const GET_COURSE_INFO = 'course_info';
    public const GET_COURSE_DESCRIPTIONS = 'course_descriptions';
    public const GET_COURSE_DOCUMENTS = 'course_documents';
    public const GET_COURSE_ANNOUNCEMENTS = 'course_announcements';
    public const GET_COURSE_ANNOUNCEMENT = 'course_announcement';
    public const GET_COURSE_AGENDA = 'course_agenda';
    public const GET_COURSE_NOTEBOOKS = 'course_notebooks';
    public const GET_COURSE_FORUM_CATEGORIES = 'course_forumcategories';
    public const GET_COURSE_FORUM = 'course_forum';
    public const GET_COURSE_FORUM_THREAD = 'course_forumthread';
    public const GET_COURSE_LEARNPATHS = 'course_learnpaths';
    public const GET_COURSE_LEARNPATH = 'course_learnpath';
    public const GET_COURSE_LP_PROGRESS = 'course_lp_progress';
    public const GET_COURSE_LINKS = 'course_links';
    public const GET_COURSE_WORKS = 'course_works';
    public const GET_COURSE_EXERCISES = 'course_exercises';
    public const GET_COURSES_DETAILS_BY_EXTRA_FIELD = 'courses_details_by_extra_field';

    public const SAVE_COURSE_NOTEBOOK = 'save_course_notebook';

    public const SAVE_FORUM_POST = 'save_forum_post';
    public const SAVE_FORUM_THREAD = 'save_forum_thread';
    public const SET_THREAD_NOTIFY = 'set_thread_notify';
    public const DOWNLOAD_FORUM_ATTACHMENT = 'download_forum_attachment';

    public const GET_WORK_LIST = 'get_work_list';
    public const GET_WORK_STUDENTS_WITHOUT_PUBLICATIONS = 'get_work_students_without_publications';
    public const GET_WORK_USERS = 'get_work_users';
    public const GET_WORK_STUDENT_LIST = 'get_work_student_list';
    public const PUT_WORK_STUDENT_ITEM_VISIBILITY = 'put_course_work_visibility';
    public const DELETE_WORK_STUDENT_ITEM = 'delete_work_student_item';
    public const DELETE_WORK_CORRECTIONS = 'delete_work_corrections';
    public const DOWNLOAD_WORK_FOLDER = 'download_work_folder';
    public const DOWNLOAD_WORK_COMMENT_ATTACHMENT = 'download_work_comment_attachment';
    public const DOWNLOAD_WORK = 'download_work';

    public const VIEW_DOCUMENT_IN_FRAME = 'view_document_in_frame';

    public const VIEW_QUIZ_TOOL = 'view_quiz_tool';

    public const VIEW_SURVEY_TOOL = 'view_survey_tool';

    public const CREATE_CAMPUS = 'add_campus';
    public const EDIT_CAMPUS = 'edit_campus';
    public const DELETE_CAMPUS = 'delete_campus';

    public const GET_USERS = 'get_users';
    public const USERNAME_EXIST = 'username_exist';
    public const SAVE_USER = 'save_user';
    public const SAVE_USER_GET_APIKEY = 'save_user_get_apikey';
    public const SAVE_USER_JSON = 'save_user_json';
    public const UPDATE_USER_FROM_USERNAME = 'update_user_from_username';
    public const UPDATE_USER_APIKEY = 'update_user_apikey';
    public const DELETE_USER = 'delete_user';
    public const GET_USERS_API_KEYS = 'get_users_api_keys';
    public const GET_USER_API_KEY = 'get_user_api_key';
    public const GET_USER_SUB_GROUP = 'get_user_sub_group';

    public const GET_COURSES = 'get_courses';
    public const GET_COURSES_FROM_EXTRA_FIELD = 'get_courses_from_extra_field';
    public const SAVE_COURSE = 'save_course';
    public const DELETE_COURSE = 'delete_course';

    public const GET_SESSION_FROM_EXTRA_FIELD = 'get_session_from_extra_field';
    public const SAVE_SESSION = 'save_session';
    public const CREATE_SESSION_FROM_MODEL = 'create_session_from_model';
    public const UPDATE_SESSION = 'update_session';
    public const GET_SESSIONS = 'get_sessions';

    public const SUBSCRIBE_USER_TO_COURSE = 'subscribe_user_to_course';
    public const SUBSCRIBE_USER_TO_COURSE_PASSWORD = 'subscribe_user_to_course_password';
    public const UNSUBSCRIBE_USER_FROM_COURSE = 'unsubscribe_user_from_course';
    public const GET_USERS_SUBSCRIBED_TO_COURSE = 'get_users_subscribed_to_course';

    public const ADD_COURSES_SESSION = 'add_courses_session';
    public const ADD_USERS_SESSION = 'add_users_session';
    public const SUBSCRIBE_USER_TO_SESSION_FROM_USERNAME = 'subscribe_user_to_session_from_username';
    public const SUBSCRIBE_USERS_TO_SESSION = 'subscribe_users_to_session';
    public const UNSUBSCRIBE_USERS_FROM_SESSION = 'unsubscribe_users_from_session';
    public const GET_USERS_SUBSCRIBED_TO_SESSION = 'get_users_subscribed_to_session';

    public const GET_COURSE_QUIZ_MDL_COMPAT = 'get_course_quiz_mdl_compat';

    public const UPDATE_USER_PAUSE_TRAINING = 'update_user_pause_training';

    public const CHECK_CONDITIONAL_LOGIN = 'check_conditional_login';
    public const GET_LEGAL_CONDITIONS = 'get_legal_conditions';
    public const UPDATE_CONDITION_ACCEPTED = 'update_condition_accepted';
    public const GET_TEST_UPDATES_LIST = 'get_test_updates_list';
    public const GET_TEST_AVERAGE_RESULTS_LIST = 'get_test_average_results_list';

    public const GET_GROUPS = 'get_groups';
    public const GROUP_EXISTS = 'group_exists';
    public const ADD_GROUP = 'add_group';
    public const DELETE_GROUP = 'delete_group';
    public const GET_GROUP_SUB_USERS = 'get_group_sub_users';
    public const GET_GROUP_SUB_COURSES = 'get_group_sub_courses';
    public const GET_GROUP_SUB_SESSIONS = 'get_group_sub_sessions';
    public const ADD_GROUP_SUB_USER = 'add_group_sub_user';
    public const ADD_GROUP_SUB_COURSE = 'add_group_sub_course';
    public const ADD_GROUP_SUB_SESSION = 'add_group_sub_session';
    public const DELETE_GROUP_SUB_USER = 'delete_group_sub_user';
    public const DELETE_GROUP_SUB_COURSE = 'delete_group_sub_course';
    public const DELETE_GROUP_SUB_SESSION = 'delete_group_sub_session';

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
     * Get user's username or another field if so configured through $_configuration['webservice_return_user_field'].
     *
     * @param int $userId
     */
    private function __getConfiguredUsernameById(int $userId = null): string
    {
        if (empty($userId)) {
            return '';
        }
        $userField = api_get_configuration_value('webservice_return_user_field');
        if (empty($userField)) {
            return api_get_user_info($userId)['username'];
        }

        $fieldValue = new ExtraFieldValue('user');
        $extraInfo = $fieldValue->get_values_by_handler_and_field_variable($userId, $userField);
        if (!empty($extraInfo)) {
            return $extraInfo['value'];
        } else {
            return api_get_user_info($userId)['username'];
        }
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
        global $_course;

        if (!$id) {
            $this->course = null;

            ChamiloSession::erase('_real_cid');
            ChamiloSession::erase('_cid');
            ChamiloSession::erase('_course');

            return;
        }

        $em = Database::getManager();
        /** @var Course $course */
        $course = $em->find('ChamiloCoreBundle:Course', $id);

        if (!$course) {
            throw new Exception(get_lang('NoCourse'));
        }

        $this->course = $course;

        $courseInfo = api_get_course_info($course->getCode());
        $_course = $courseInfo;

        ChamiloSession::write('_real_cid', $course->getId());
        ChamiloSession::write('_cid', $course->getCode());
        ChamiloSession::write('_course', $courseInfo);
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

            ChamiloSession::erase('session_name');
            ChamiloSession::erase('id_session');

            return;
        }

        $em = Database::getManager();
        /** @var Session $session */
        $session = $em->find('ChamiloCoreBundle:Session', $id);

        if (!$session) {
            throw new Exception(get_lang('NoSession'));
        }

        $this->session = $session;

        ChamiloSession::write('session_name', $session->getName());
        ChamiloSession::write('id_session', $session->getId());
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

        $webPath = api_get_path(WEB_PATH);

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
                'content' => str_replace('src="/"', $webPath, $message['content']),
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
     * @throws Exception
     */
    public function getUserCourses($userId = 0): array
    {
        if (!empty($userId)) {
            if (!api_is_platform_admin() && $userId != $this->user->getId()) {
                self::throwNotAllowedException();
            }
        }

        if (empty($userId)) {
            $userId = $this->user->getId();
        }

        Event::courseLogout(
            [
                'uid' => $userId,
                'cid' => api_get_course_id(),
                'sid' => api_get_session_id(),
            ]
        );

        $courses = CourseManager::get_courses_list_by_user_id($userId);
        $data = [];

        $webCodePath = api_get_path(WEB_CODE_PATH).'webservices/api/v2.php?';

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
                'url' => $webCodePath.http_build_query(
                    [
                        'action' => self::VIEW_COURSE_HOME,
                        'api_key' => $this->apiKey,
                        'username' => $this->user->getUsername(),
                        'course' => $course->getId(),
                    ]
                ),
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
     * @param array $fields A list of extra fields to include in the answer. Searches for the field in the including course
     *
     * @throws Exception
     */
    public function getCourseDescriptions($fields = []): array
    {
        Event::event_access_tool(TOOL_COURSE_DESCRIPTION);

        // Check the extra fields criteria (whether to add extra field information or not)
        $fieldSource = [];
        if (count($fields) > 0) {
            // For each field, check where to get it from (quiz or course)
            $courseExtraField = new ExtraField('course');
            foreach ($fields as $fieldName) {
                // The field does not exist on the exercise, so use it from the course
                $courseFieldExists = $courseExtraField->get_handler_field_info_by_field_variable($fieldName);
                if ($courseFieldExists === false) {
                    continue;
                }
                $fieldSource[$fieldName] = ['item_type' => 'course', 'id' => $courseFieldExists['id']];
            }
        }

        $courseId = $this->course->getId();
        $descriptions = CourseDescription::get_descriptions($courseId);
        $results = [];

        $webPath = api_get_path(WEB_PATH);

        /** @var CourseDescription $description */
        foreach ($descriptions as $description) {
            $descriptionDetails = [
                'id' => $description->get_description_type(),
                'title' => $description->get_title(),
                'content' => str_replace('src="/', 'src="'.$webPath, $description->get_content()),
            ];
            if (count($fieldSource) > 0) {
                $extraFieldValuesTable = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
                $fieldsSearchString = "SELECT field_id, value FROM $extraFieldValuesTable WHERE item_id = %d AND field_id = %d";
                foreach ($fieldSource as $fieldName => $fieldDetails) {
                    $itemId = $courseId;
                    $result = Database::query(sprintf($fieldsSearchString, $itemId, $fieldDetails['id']));
                    if (Database::num_rows($result) > 0) {
                        $row = Database::fetch_assoc($result);
                        $descriptionDetails['extra_'.$fieldName] = $row['value'];
                    } else {
                        $descriptionDetails['extra_'.$fieldName] = '';
                    }
                }
            }
            $results[] = $descriptionDetails;
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
        Event::event_access_tool(TOOL_DOCUMENT);

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
        Event::event_access_tool(TOOL_ANNOUNCEMENT);

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
        Event::event_access_tool(TOOL_ANNOUNCEMENT);

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
        Event::event_access_tool(TOOL_CALENDAR_EVENT);

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
        Event::event_access_tool(TOOL_NOTEBOOK);

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
        Event::event_access_tool(TOOL_FORUM);

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
        Event::event_access_tool(TOOL_FORUM);

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
        Event::event_access_tool(TOOL_FORUM);

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
                'attachments' => getAttachedFiles(
                    $forumId,
                    $threadId,
                    $postInfo['iid'],
                    0,
                    $this->course->getId()
                ),
            ];
        }

        return $thread;
    }

    public function getCourseLinks(): array
    {
        Event::event_access_tool(TOOL_LINK);

        $courseId = $this->course->getId();
        $sessionId = $this->session ? $this->session->getId() : 0;

        $webCodePath = api_get_path(WEB_CODE_PATH);
        $cidReq = api_get_cidreq();

        $categories = array_merge(
            [
                [
                    'iid' => 0,
                    'c_id' => $courseId,
                    'id' => 0,
                    'category_title' => get_lang('NoCategory'),
                    'description' => '',
                    'display_order' => 0,
                    'session_id' => $sessionId,
                    'visibility' => 1,
                ],
            ],
            Link::getLinkCategories($courseId, $sessionId)
        );

        $categories = array_filter(
            $categories,
            function (array $category) {
                return $category['visibility'] != 0;
            }
        );

        return array_map(
            function (array $category) use ($webCodePath, $cidReq, $courseId, $sessionId) {
                $links = array_filter(
                    Link::getLinksPerCategory($category['iid'], $courseId, $sessionId),
                    function (array $link) {
                        return $link['visibility'] != 0;
                    }
                );

                $links = array_map(
                    function (array $link) use ($webCodePath, $cidReq) {
                        return [
                            'id' => (int) $link['id'],
                            'title' => Security::remove_XSS($link['title']),
                            'description' => Security::remove_XSS($link['description']),
                            'visibility' => (int) $link['visibility'],
                            'url' => $webCodePath."link/link_goto.php?$cidReq&link_id=".$link['id'],
                        ];
                    },
                    $links
                );

                return [
                    'id' => (int) $category['iid'],
                    'title' => Security::remove_XSS($category['category_title']),
                    'description' => Security::remove_XSS($category['description']),
                    'visibility' => (int) $category['visibility'],
                    'links' => $links,
                ];
            },
            $categories
        );
    }

    /**
     * It gets the courses and visible tests of a user by dates.
     *
     * @throws Exception
     */
    public function getUserCoursesByDates(int $userId, string $startDate, string $endDate): array
    {
        self::protectAdminEndpoint();
        $userCourses = CourseManager::get_courses_list_by_user_id($userId);
        $courses = [];
        if (!empty($userCourses)) {
            foreach ($userCourses as $course) {
                $courseCode = $course['code'];
                $courseId = $course['real_id'];
                $exercises = Exercise::exerciseGrid(
                    0,
                    '',
                    0,
                    $courseId,
                    0,
                    true,
                    0,
                    0,
                    0,
                    null,
                    false,
                    false
                );
                $trackExercises = Tracking::getUserTrackExerciseByDates(
                    $userId,
                    $courseId,
                    $startDate,
                    $endDate
                );
                $takenExercises = [];
                if (!empty($trackExercises)) {
                    $totalSore = 0;
                    foreach ($trackExercises as $track) {
                        $takenExercises[] = $track['title'];
                        $totalSore += $track['score'];
                    }
                    $avgScore = round($totalSore / count($trackExercises));
                    $takenExercises['avg_score'] = $avgScore;
                }
                $courses[] = [
                    'course_code' => $courseCode,
                    'course_title' => $course['title'],
                    'visible_tests' => $exercises,
                    'taken_tests' => $takenExercises,
                ];
            }
        }

        return $courses;
    }

    /**
     * Get the list of courses from extra field included count of visible exercises.
     *
     * @throws Exception
     */
    public function getCoursesByExtraField(string $fieldName, string $fieldValue): array
    {
        self::protectAdminEndpoint();
        $extraField = new ExtraField('course');
        $extraFieldInfo = $extraField->get_handler_field_info_by_field_variable($fieldName);

        if (empty($extraFieldInfo)) {
            throw new Exception("$fieldName not found");
        }

        $extraFieldValue = new ExtraFieldValue('course');
        $items = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
            $fieldName,
            $fieldValue,
            false,
            false,
            true
        );

        $courses = [];
        foreach ($items as $item) {
            $courseId = $item['item_id'];
            $courses[$courseId] = api_get_course_info_by_id($courseId);
            $exercises = Exercise::exerciseGrid(
                0,
                '',
                0,
                $courseId,
                0,
                true,
                0,
                0,
                0,
                null,
                false,
                false
            );
            $courses[$courseId]['count_visible_tests'] = count($exercises);
        }

        return $courses;
    }

    /**
     * Get the list of users from extra field.
     *
     * @param string $fieldName  The name of the extra_field (as in extra_field.variable) we want to filter on.
     * @param string $fieldValue The value of the extra_field we want to filter on. If a user doesn't have the given extra_field set to that value, it will not be returned.
     * @param int    $active     Additional filter. If 1, only return active users. Otherwise, return them all.
     *
     * @throws Exception
     */
    public function getUsersProfilesByExtraField(string $fieldName, string $fieldValue, int $active = 0): array
    {
        self::protectAdminEndpoint();
        $users = [];
        $extraValues = UserManager::get_extra_user_data_by_value(
            $fieldName,
            $fieldValue
        );
        if (!empty($extraValues)) {
            foreach ($extraValues as $value) {
                $userId = (int) $value;
                $user = api_get_user_entity($userId);
                if ($active && !$user->getActive()) {
                    // If this user is not active, and we only asked for active users, skip to next user.
                    continue;
                }
                $pictureInfo = UserManager::get_user_picture_path_by_id($user->getId(), 'web');
                $users[$userId] = [
                    'pictureUri' => $pictureInfo['dir'].$pictureInfo['file'],
                    'id' => $userId,
                    'status' => $user->getStatus(),
                    'fullName' => UserManager::formatUserFullName($user),
                    'username' => $user->getUsername(),
                    'officialCode' => $user->getOfficialCode(),
                    'phone' => $user->getPhone(),
                    //'extra' => [],
                ];
            }
        }

        return $users;
    }

    /**
     * Get one's own profile.
     */
    public function getUserProfile(): array
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

    /**
     * Get one's own (avg) progress in learning paths.
     */
    public function getCourseLpProgress(): array
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
     */
    public function getCourseLearnPaths(): array
    {
        Event::event_access_tool(TOOL_LEARNPATH);

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
     */
    public function showLearningPath(int $lpId)
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
        Event::event_access_tool(TOOL_FORUM);

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

        $webCodePath = api_get_path(WEB_CODE_PATH).'webservices/api/v2.php?';

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
                        'url' => $webCodePath.http_build_query(
                            [
                                'action' => self::VIEW_COURSE_HOME,
                                'api_key' => $this->apiKey,
                                'username' => $this->user->getUsername(),
                                'course' => $courseInfo['real_id'],
                                'session' => $sessions['session_id'],
                            ]
                        ),
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

    public function getUsersSubscribedToCourse()
    {
        $users = CourseManager::get_user_list_from_course_code($this->course->getCode());

        $userList = [];
        foreach ($users as $user) {
            $userList[] = [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'firstname' => $user['firstname'],
                'lastname' => $user['lastname'],
                'status_rel' => $user['status_rel'],
            ];
        }

        return $userList;
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
        Event::event_access_tool(TOOL_NOTEBOOK);

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
        Event::event_access_tool(TOOL_FORUM);

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
     * Returns an array of users with id, firstname, lastname, email and username.
     *
     * @param array $params An array of parameters to filter the results (currently supports 'status', 'id_campus' and 'extra_fields')
     *
     * @throws Exception
     */
    public function getUsersCampus(array $params): array
    {
        self::protectAdminEndpoint();

        if ('*' === $params['status']) {
            $conditions = [];
        } else {
            $conditions = [
                'status' => $params['status'],
            ];
        }
        $idCampus = !empty($params['id_campus']) ?? 1;
        $fields = [];
        if (!empty($params['extra_fields'])) {
            //extra_fields must be sent as a comma-separated list of extra_field variable names
            $fields = explode(',', $params['extra_fields']);
        }
        $users = UserManager::get_user_list($conditions, ['firstname'], false, false, $idCampus);
        $list = [];
        foreach ($users as $item) {
            $listTemp = [
                'id' => $item['user_id'],
                'firstname' => $item['firstname'],
                'lastname' => $item['lastname'],
                'email' => $item['email'],
                'username' => $item['username'],
                'active' => $item['active'],
            ];
            foreach ($fields as $field) {
                $field = trim($field);
                $value = UserManager::get_extra_user_data_by_field($item['user_id'], $field);
                if (!empty($value)) {
                    $listTemp[$field] = $value[$field];
                }
            }
            $list[] = $listTemp;
        }

        return $list;
    }

    /**
     * Returns a list of courses in the given URL. If no URL is provided, we assume we are not in a multi-URL setup and
     * return all the courses.
     */
    public function getCoursesCampus(int $campusId = 0): array
    {
        return CourseManager::get_courses_list(
            0, //offset
            0, //howMany
            1, //$orderby = 1
            'ASC',
            -1, //visibility
            null,
            empty($campusId) ? null : $campusId, //$urlId
            true //AlsoSearchCode
        );
    }

    /**
     * Returns a list of sessions in the given URL. If no URL is provided, we assume we are not in a multi-URL setup and
     * return all the sessions.
     *
     * @param int $campusId Optional
     *
     * @throws Exception
     */
    public function getSessionsCampus(int $campusId = 0): array
    {
        self::protectAdminEndpoint();

        $list = SessionManager::get_sessions_list(
            [],
            [],
            null,
            null,
            $campusId
        );
        $shortList = [];
        foreach ($list as $session) {
            $shortList[] = [
                'id' => $session['id'],
                'name' => $session['name'],
                'access_start_date' => $session['access_start_date'],
                'access_end_date' => $session['access_end_date'],
            ];
        }

        return $shortList;
    }

    /**
     * Returns an array of groups with id, group_type, name, description, visibility.
     *
     * @param array $params An array of parameters to filter the results (currently supports 'type')
     *
     * @throws Exception
     */
    public function getGroups(array $params): array
    {
        self::protectAdminEndpoint();

        if ('*' === $params['type']) {
            $conditions = [];
        } else {
            $conditions = ['where' => ['group_type = ?' => $params['type']]];
        }
        $userGroup = new UserGroup();
        $groups = $userGroup->getDataToExport($conditions);
        $list = [];
        /** @var \Chamilo\UserBundle\Entity\Group $item */
        foreach ($groups as $item) {
            $listTemp = [
                'id' => $item['id'],
                'name' => $item['name'],
                'description' => $item['description'],
                'visibility' => $item['visibility'],
                'type' => $item['group_type'],
            ];
            if (in_array($item['group_type'], [0, 1])) {
                $listTemp['type_name'] = ($item['group_type'] == 0) ? 'class' : 'social';
            }
            if (in_array($item['visibility'], [1, 2])) {
                $listTemp['visibility_name'] = ($item['visibility'] == 1) ? 'open' : 'closed';
            }
            $list[] = $listTemp;
        }

        return $list;
    }

    /**
     * @throws Exception
     */
    public function addSession(array $params): array
    {
        self::protectAdminEndpoint();

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

    public function addCourse(array $courseParam): array
    {
        self::protectAdminEndpoint();

        $idCampus = isset($courseParam['id_campus']) ? $courseParam['id_campus'] : 1;
        $title = isset($courseParam['title']) ? $courseParam['title'] : '';
        $wantedCode = isset($courseParam['wanted_code']) ? $courseParam['wanted_code'] : null;
        $diskQuota = isset($courseParam['disk_quota']) ? $courseParam['disk_quota'] : '100';
        $visibility = isset($courseParam['visibility']) ? (int) $courseParam['visibility'] : null;
        $removeCampusId = $courseParam['remove_campus_id_from_wanted_code'] ?? 0;
        $language = $courseParam['language'] ?? '';

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
        if (1 === (int) $removeCampusId) {
            $params['wanted_code'] = $wantedCode;
        }
        $params['user_id'] = $this->user->getId();
        $params['visibility'] = $visibility;
        $params['disk_quota'] = $diskQuota;
        $params['course_language'] = $language;

        foreach ($courseParam as $key => $value) {
            if (substr($key, 0, 6) === 'extra_') { //an extra field
                $params[$key] = $value;
            }
        }

        $courseInfo = CourseManager::create_course($params, $params['user_id'], $idCampus);
        $results = [];
        if (!empty($courseInfo)) {
            $results['status'] = true;
            $results['id'] = $courseInfo['real_id'];
            $results['code_course'] = $courseInfo['code'];
            $results['title_course'] = $courseInfo['title'];
            $extraFieldValues = new ExtraFieldValue('course');
            $extraFields = $extraFieldValues->getAllValuesByItem($courseInfo['real_id']);
            $results['extra_fields'] = $extraFields;
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
     */
    public function addUser($userParam): array
    {
        self::protectAdminEndpoint();

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
        $auth_source = $userParam['auth_source'] ?? PLATFORM_AUTH_SOURCE;
        $expiration_date = '';
        $active = 1;
        $hr_dept_id = 0;
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
     * @throws Exception
     */
    public function addUserGetApikey(array $userParams): array
    {
        list($userId) = $this->addUser($userParams);

        UserManager::add_api_key($userId, self::SERVICE_NAME);

        $apiKey = UserManager::get_api_keys($userId, self::SERVICE_NAME);

        return [
            'id' => $userId,
            'api_key' => current($apiKey),
        ];
    }

    /**
     * @throws Exception
     */
    public function updateUserApiKey(int $userId, string $oldApiKey): array
    {
        if (!api_is_platform_admin() && $userId != $this->user->getId()) {
            self::throwNotAllowedException();
        }

        if (false === $currentApiKeys = UserManager::get_api_keys($userId, self::SERVICE_NAME)) {
            self::throwNotAllowedException();
        }

        if (current($currentApiKeys) !== $oldApiKey) {
            self::throwNotAllowedException();
        }

        UserManager::update_api_key($userId, self::SERVICE_NAME);

        $apiKey = UserManager::get_api_keys($userId, self::SERVICE_NAME);

        return [
            'api_key' => current($apiKey),
        ];
    }

    /**
     * Subscribe User to Course.
     *
     * @throws Exception
     */
    public function subscribeUserToCourse(array $params): array
    {
        $course_id = $params['course_id'];
        $course_code = $params['course_code'];
        $user_id = $params['user_id'];
        $status = $params['status'] ?? STUDENT;

        if (!api_is_platform_admin() && $user_id != $this->user->getId()) {
            self::throwNotAllowedException();
        }

        if (!$course_id && !$course_code) {
            return [false];
        }
        if (!$course_code) {
            $course_code = CourseManager::get_course_code_from_course_id($course_id);
        }

        if (CourseManager::subscribeUser($user_id, $course_code, $status, 0, 0, false)) {
            return [true];
        }

        return [false];
    }

    /**
     * @throws Exception
     */
    public function subscribeUserToCoursePassword($courseCode, $password)
    {
        $courseInfo = api_get_course_info($courseCode);

        if (empty($courseInfo)) {
            throw new Exception(get_lang('NoCourse'));
        }

        if (sha1($password) === $courseInfo['registration_code']) {
            CourseManager::processAutoSubscribeToCourse($courseCode);

            return;
        }

        throw new Exception(get_lang('CourseRegistrationCodeIncorrect'));
    }

    /**
     * @throws Exception
     */
    public function unSubscribeUserToCourse(array $params): array
    {
        $courseId = $params['course_id'];
        $courseCode = $params['course_code'];
        $userId = $params['user_id'];

        if (!api_is_platform_admin() && $userId != $this->user->getId()) {
            self::throwNotAllowedException();
        }

        if (!$courseId && !$courseCode) {
            return [false];
        }

        if (!$courseCode) {
            $courseCode = CourseManager::get_course_code_from_course_id($courseId);
        }

        if (CourseManager::unsubscribe_user($userId, $courseCode)) {
            return [true];
        }

        return [false];
    }

    public function deleteUserMessage($messageId, $messageType)
    {
        if ($messageType === 'sent') {
            return MessageManager::delete_message_by_user_sender($this->user->getId(), $messageId);
        } else {
            return MessageManager::delete_message_by_user_receiver($this->user->getId(), $messageId);
        }
    }

    /**
     * Set a given message as already read.
     *
     * @param $messageId
     */
    public function setMessageRead(int $messageId)
    {
        // MESSAGE_STATUS_NEW is also used for messages that have been "read"
        MessageManager::update_message_status($this->user->getId(), $messageId, MESSAGE_STATUS_NEW);
    }

    /**
     * Add a group.
     *
     * @param array Params
     */
    public function createGroup($params)
    {
        self::protectAdminEndpoint();

        $name = $params['name'];
        $description = $params['description'];
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
     */
    public function addCoursesSession(array $params): array
    {
        self::protectAdminEndpoint();

        $sessionId = $params['id_session'];
        $courseList = $params['list_courses'];
        $importAssignments = isset($params['import_assignments']) && 1 === (int) $params['import_assignments'];

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
     * Simple legacy shortcut to subscribeUsersToSession.
     *
     * @throws Exception
     */
    public function addUsersSession(array $params): array
    {
        return self::subscribeUsersToSession($params);
    }

    /**
     * Subscribe a list of users to the given session.
     *
     * @param array $params Containing 'id_session' and 'list_users' entries
     *
     * @throws Exception
     */
    public function subscribeUsersToSession(array $params): array
    {
        $sessionId = $params['id_session'];
        $userList = $params['list_users'];

        if (!is_array($userList)) {
            $userList = [];
        }

        if (!api_is_platform_admin() && !in_array($this->user->getId(), $userList)) {
            self::throwNotAllowedException();
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
     * Unsubscribe a given list of users from the given session.
     *
     * @throws Exception
     */
    public function unsubscribeUsersFromSession(array $params): array
    {
        self::protectAdminEndpoint();

        $sessionId = $params['id_session'];
        $userList = $params['list_users'];

        if (!is_array($userList)) {
            $userList = [];
        }

        if (!api_is_platform_admin() && !in_array($this->user->getId(), $userList)) {
            self::throwNotAllowedException();
        }

        foreach ($userList as $userId) {
            SessionManager::unsubscribe_user_from_session(
                $sessionId,
                $userId
            );
        }

        return [
            'status' => true,
            'message' => get_lang('UserUnsubscribed'),
        ];
    }

    /**
     * Creates a session from a model session.
     *
     * @throws Exception
     */
    public function createSessionFromModel(HttpRequest $request): int
    {
        self::protectAdminEndpoint();

        $modelSessionId = $request->request->getInt('modelSessionId');
        $sessionName = $request->request->get('sessionName');
        $startDate = $request->request->get('startDate');
        $endDate = $request->request->get('endDate');
        $extraFields = $request->request->get('extraFields', []);
        $duplicateAgendaContent = $request->request->getBoolean('duplicateAgendaContent');

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

        $table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $courseListOrdered = SessionManager::get_course_list_by_session_id($modelSessionId, null, 'position');
        $count = 0;
        foreach ($courseListOrdered as $course) {
            if ($course['position'] == '') {
                $course['position'] = $count;
            }
            // Saving order.
            $sql = "UPDATE $table SET position = ".$course['position']."
                    WHERE session_id = $newSessionId AND c_id = '".$course['real_id']."'";
            Database::query($sql);
            $count++;
        }

        if ($duplicateAgendaContent) {
            foreach ($courseList as $courseId) {
                SessionManager::importAgendaFromSessionModel($modelSessionId, $newSessionId, $courseId);
            }
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
     * @throws Exception
     */
    public function subscribeUserToSessionFromUsername(int $sessionId, string $loginName): bool
    {
        if (!SessionManager::isValidId($sessionId)) {
            throw new Exception(get_lang('SessionNotFound'));
        }

        $userId = UserManager::get_user_id_from_username($loginName);
        if (false === $userId) {
            throw new Exception(get_lang('UserNotFound'));
        }

        if (!api_is_platform_admin() && $userId != $this->user->getId()) {
            self::throwNotAllowedException();
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
     * Get a list of users subscribed to the given session.
     *
     * @params int $sessionId
     * @params int $moveInfo Whether to return the "moved_*" fields or not
     */
    public function getUsersSubscribedToSession(int $sessionId, int $moveInfo = 0): array
    {
        self::protectAdminEndpoint();

        $users = SessionManager::get_users_by_session($sessionId);

        $userList = [];
        foreach ($users as $user) {
            $userInfo = [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'firstname' => $user['firstname'],
                'lastname' => $user['lastname'],
                'status' => $user['relation_type'],
            ];
            if (1 === $moveInfo) {
                $userInfo['moved_to'] = $user['moved_to'];
                $userInfo['moved_status'] = $user['moved_status'];
                $userInfo['moved_at'] = $user['moved_at'];
            }
            $userList[] = $userInfo;
        }

        return $userList;
    }

    /**
     * Updates a user identified by its login name.
     *
     * @throws Exception on failure
     */
    public function updateUserFromUserName(array $parameters): bool
    {
        // find user
        $userId = null;
        if (empty($parameters)) {
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

        if (!api_is_platform_admin() && $userId != $this->user->getId()) {
            self::throwNotAllowedException();
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
     * Returns whether a user group name exists.
     *
     * @param string $name the group name
     *
     * @return bool whether the group name exists
     */
    public function groupExists($name)
    {
        $userGroup = new UserGroup();

        return false !== $userGroup->usergroup_exists($name);
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
                    'id' => (int) $exercise['id'],
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
     * @throws Exception
     */
    public function updateSession(array $params): array
    {
        self::protectAdminEndpoint();

        $id = $params['session_id'];
        $reset = $params['reset'] ?? null;
        $name = $params['name'] ?? null;
        $coachId = isset($params['id_coach']) ? (int) $params['id_coach'] : null;
        $sessionCategoryId = isset($params['session_category_id']) ? (int) $params['session_category_id'] : null;
        $description = $params['description'] ?? null;
        $showDescription = $params['show_description'] ?? null;
        $duration = $params['duration'] ?? null;
        $visibility = $params['visibility'] ?? null;
        $promotionId = $params['promotion_id'] ?? null;
        $displayStartDate = $params['display_start_date'] ?? null;
        $displayEndDate = $params['display_end_date'] ?? null;
        $accessStartDate = $params['access_start_date'] ?? null;
        $accessEndDate = $params['access_end_date'] ?? null;
        $coachStartDate = $params['coach_access_start_date'] ?? null;
        $coachEndDate = $params['coach_access_end_date'] ?? null;
        $sendSubscriptionNotification = $params['send_subscription_notification'] ?? null;
        $extraFields = $params['extra'] ?? [];

        $reset = (bool) $reset;
        $visibility = (int) $visibility;
        $tblSession = Database::get_main_table(TABLE_MAIN_SESSION);

        if (!SessionManager::isValidId($id)) {
            throw new Exception(get_lang('NoData'));
        }

        if (!empty($accessStartDate) && !api_is_valid_date($accessStartDate, 'Y-m-d H:i') &&
            !api_is_valid_date($accessStartDate, 'Y-m-d H:i:s')
        ) {
            throw new Exception(get_lang('InvalidDate'));
        }

        if (!empty($accessEndDate) && !api_is_valid_date($accessEndDate, 'Y-m-d H:i') &&
            !api_is_valid_date($accessEndDate, 'Y-m-d H:i:s')
        ) {
            throw new Exception(get_lang('InvalidDate'));
        }

        if (!empty($accessStartDate) && !empty($accessEndDate) && $accessStartDate >= $accessEndDate) {
            throw new Exception(get_lang('InvalidDate'));
        }

        $values = [];

        if ($reset) {
            $values['name'] = $name;
            $values['id_coach'] = $coachId;
            $values['session_category_id'] = $sessionCategoryId;
            $values['description'] = $description;
            $values['show_description'] = $showDescription;
            $values['duration'] = $duration;
            $values['visibility'] = $visibility;
            $values['promotion_id'] = $promotionId;
            $values['display_start_date'] = !empty($displayStartDate) ? api_get_utc_datetime($displayStartDate) : null;
            $values['display_end_date'] = !empty($displayEndDate) ? api_get_utc_datetime($displayEndDate) : null;
            $values['access_start_date'] = !empty($accessStartDate) ? api_get_utc_datetime($accessStartDate) : null;
            $values['access_end_date'] = !empty($accessEndDate) ? api_get_utc_datetime($accessEndDate) : null;
            $values['coach_access_start_date'] = !empty($coachStartDate) ? api_get_utc_datetime($coachStartDate) : null;
            $values['coach_access_end_date'] = !empty($coachEndDate) ? api_get_utc_datetime($coachEndDate) : null;
            $values['send_subscription_notification'] = $sendSubscriptionNotification;
        } else {
            if (!empty($name)) {
                $values['name'] = $name;
            }

            if (!empty($coachId)) {
                $values['id_coach'] = $coachId;
            }

            if (!empty($sessionCategoryId)) {
                $values['session_category_id'] = $sessionCategoryId;
            }

            if (!empty($description)) {
                $values['description'] = $description;
            }

            if (!empty($showDescription)) {
                $values['show_description'] = $showDescription;
            }

            if (!empty($duration)) {
                $values['duration'] = $duration;
            }

            if (!empty($visibility)) {
                $values['visibility'] = $visibility;
            }

            if (!empty($promotionId)) {
                $values['promotion_id'] = $promotionId;
            }

            if (!empty($displayStartDate)) {
                $values['display_start_date'] = api_get_utc_datetime($displayStartDate);
            }

            if (!empty($displayEndDate)) {
                $values['display_end_date'] = api_get_utc_datetime($displayEndDate);
            }

            if (!empty($accessStartDate)) {
                $values['access_start_date'] = api_get_utc_datetime($accessStartDate);
            }

            if (!empty($accessEndDate)) {
                $values['access_end_date'] = api_get_utc_datetime($accessEndDate);
            }

            if (!empty($coachStartDate)) {
                $values['coach_access_start_date'] = api_get_utc_datetime($coachStartDate);
            }

            if (!empty($coachEndDate)) {
                $values['coach_access_end_date'] = api_get_utc_datetime($coachEndDate);
            }

            if (!empty($sendSubscriptionNotification)) {
                $values['send_subscription_notification'] = $sendSubscriptionNotification;
            }
        }

        Database::update(
            $tblSession,
            $values,
            ['id = ?' => $id]
        );

        if (!empty($extraFields)) {
            $extraFields['item_id'] = $id;
            $sessionFieldValue = new ExtraFieldValue('session');
            $sessionFieldValue->saveFieldValues($extraFields);
        }

        return [
            'status' => true,
            'message' => get_lang('Updated'),
            'id_session' => $id,
        ];
    }

    public function checkConditionalLogin(): bool
    {
        $file = api_get_path(SYS_CODE_PATH).'auth/conditional_login/conditional_login.php';

        if (!file_exists($file)) {
            return true;
        }

        include_once $file;

        if (!isset($login_conditions)) {
            return true;
        }

        foreach ($login_conditions as $condition) {
            //If condition fails we redirect to the URL defined by the condition
            if (!isset($condition['conditional_function'])) {
                continue;
            }

            $function = $condition['conditional_function'];
            $result = $function(['user_id' => $this->user->getId()]);

            if ($result == false) {
                return false;
            }
        }

        return true;
    }

    public function getLegalConditions(): array
    {
        $language = api_get_language_id(
            api_get_interface_language()
        );

        $termPreview = LegalManager::get_last_condition($language);

        if ($termPreview) {
            return $termPreview;
        }

        $language = api_get_language_id(
            api_get_setting('platformLanguage')
        );

        $termPreview = LegalManager::get_last_condition($language);

        if ($termPreview) {
            return $termPreview;
        }

        $language = api_get_language_id('english');

        return LegalManager::get_last_condition($language);
    }

    public function updateConditionAccepted()
    {
        $legalAcceptType = $_POST['legal_accept_type'] ?? null;

        $condArray = explode(':', $legalAcceptType);
        $condArray = array_map('intval', $condArray);

        if (empty($condArray[0]) || empty($condArray[1])) {
            return;
        }

        $conditionToSave = intval($condArray[0]).':'.intval($condArray[1]).':'.time();

        LegalManager::sendEmailToUserBoss(
            $this->user->getId(),
            $conditionToSave
        );
    }

    /**
     * Get the list of test with last user attempt and his datetime.
     *
     * @param array $fields A list of extra fields to include in the answer. Searches for the field in exercise, then in course
     *
     * @throws Exception
     */
    public function getTestUpdatesList($fields = []): array
    {
        self::protectAdminEndpoint();

        $tableCQuiz = Database::get_course_table(TABLE_QUIZ_TEST);
        $tableTrackExercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $tableUser = Database::get_main_table(TABLE_MAIN_USER);
        $resultArray = [];

        // Check the extra fields criteria (whether to add extra field information or not)
        $fieldSource = [];
        $extraFieldValuesTable = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $fieldsSearchString = "SELECT field_id, value FROM $extraFieldValuesTable WHERE item_id = %d AND field_id = %d";
        if (count($fields) > 0) {
            // For each field, check where to get it from (quiz or course)
            $quizExtraField = new ExtraField('exercise');
            $courseExtraField = new ExtraField('course');
            foreach ($fields as $fieldName) {
                $fieldExists = $quizExtraField->get_handler_field_info_by_field_variable($fieldName);
                if ($fieldExists === false) {
                    // The field does not exist on the exercise, so use it from the course
                    $courseFieldExists = $courseExtraField->get_handler_field_info_by_field_variable($fieldName);
                    if ($courseFieldExists === false) {
                        continue;
                    }
                    $fieldSource[$fieldName] = ['item_type' => 'course', 'id' => $courseFieldExists['id']];
                } else {
                    $fieldSource[$fieldName] = ['item_type' => 'exercise', 'id' => $fieldExists['id']];
                }
            }
        }

        $sql = "
            SELECT q.iid AS id,
                q.title,
                MAX(a.start_date) AS last_attempt_time,
                u.username AS last_attempt_username,
                q.c_id
            FROM $tableCQuiz q
            JOIN $tableTrackExercises a ON q.iid = a.exe_exo_id
            JOIN $tableUser u ON a.exe_user_id = u.id
            GROUP BY q.iid
        ";

        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_assoc($result)) {
                // Check the whole extra fields thing
                if (count($fieldSource) > 0) {
                    foreach ($fieldSource as $fieldName => $fieldDetails) {
                        if ($fieldDetails['item_type'] == 'course') {
                            $itemId = $row['c_id'];
                        } else {
                            $itemId = $row['id'];
                        }
                        $fieldResult = Database::query(sprintf($fieldsSearchString, $itemId, $fieldDetails['id']));
                        if (Database::num_rows($fieldResult) > 0) {
                            $fieldRow = Database::fetch_assoc($fieldResult);
                            $row['extra_'.$fieldName] = $fieldRow['value'];
                        } else {
                            $row['extra_'.$fieldName] = '';
                        }
                    }
                }
                // Get item authoring data
                $itemProps = api_get_last_item_property_info($row['c_id'], 'quiz', $row['id']);
                $row['created_by'] = $this->__getConfiguredUsernameById($itemProps['insert_user_id']);
                if ($itemProps['insert_user_id'] == $itemProps['lastedit_user_id']) {
                    $row['updated_by'] = $row['created_by'];
                } else {
                    $row['updated_by'] = $this->__getConfiguredUsernameById($itemProps['lastedit_user_id']);
                }
                $resultArray[] = $row;
            }
        }

        return $resultArray;
    }

    /**
     * Get tests results data
     * Not support sessions
     * By default, is successful if score greater than 50%.
     *
     * @throws Exception
     *
     * @return array e.g: [ { "id": 4, "title": "aiken", "updated_by": "-", "type": "1", "completion": 0 } ]
     */
    public function getTestAverageResultsList(array $ids = [], ?array $fields = []): array
    {
        self::protectAdminEndpoint();
        $tableTrackExercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $tableCQuiz = Database::get_course_table(TABLE_QUIZ_TEST);
        $tableCourseRelUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);

        $resultArray = [];
        $countUsersInCourses = [];
        $extraArray = [];

        if (!empty($ids)) {
            if (!is_array($ids)) {
                $ids = [$ids];
            }
            if (!is_array($fields)) {
                $fields = [$fields];
            }
            if (!empty($fields)) {
                foreach ($fields as $field) {
                    $extraArray['extra_'.$field] = '';
                }
            }

            $queryUsersInCourses = "
                SELECT c_id, count(*)
                FROM $tableCourseRelUser
                GROUP BY c_id
                ORDER BY c_id;
            ";

            $resultUsersInCourses = Database::query($queryUsersInCourses);
            while ($row = Database::fetch_array($resultUsersInCourses)) {
                $countUsersInCourses[$row[0]] = $row[1];
            }

            foreach ($ids as $item) {
                $item = (int) $item;

                $queryCQuiz = "
                    SELECT c_id,
                        title,
                        feedback_type,
                        pass_percentage
                    FROM $tableCQuiz
                    WHERE iid = $item";

                $resultCQuiz = Database::query($queryCQuiz);
                if (Database::num_rows($resultCQuiz) <= 0) {
                    continue;
                }
                $row = Database::fetch_assoc($resultCQuiz);

                $cId = $row['c_id'];
                $title = $row['title'];
                $type = Exercise::getFeedbackTypeLiteral($row['feedback_type']);
                $passPercentage = empty($row['pass_percentage']) ? 0.5 : $row['pass_percentage'];

                // Get item authoring data
                $itemProps = api_get_last_item_property_info($row['c_id'], 'quiz', $item);
                $createdBy = $this->__getConfiguredUsernameById($itemProps['insert_user_id']);
                if ($itemProps['insert_user_id'] == $itemProps['lastedit_user_id']) {
                    $updatedBy = $createdBy;
                } else {
                    $updatedBy = $this->__getConfiguredUsernameById($itemProps['lastedit_user_id']);
                }

                $sql = "
                    SELECT a.exe_exo_id AS id,
                           a.exe_user_id,
                           MAX(a.start_date),
                           a.exe_result,
                           a.exe_weighting
                    FROM $tableTrackExercises a
                    WHERE a.exe_exo_id = $item
                    GROUP BY a.exe_exo_id, a.exe_user_id
                ";

                $result = Database::query($sql);
                if (Database::num_rows($result) > 0) {
                    $countAttempts = 0;
                    $countSuccess = 0;
                    $scoreSum = 0;

                    while ($row = Database::fetch_assoc($result)) {

                        // If test is badly configured, with all questions at score 0
                        if ($row['exe_weighting'] == 0) {
                            continue;
                        }
                        $score = $row['exe_result'] / $row['exe_weighting'];
                        if ($score >= $passPercentage) {
                            $countSuccess++;
                        }
                        $scoreSum += $score;
                        $countAttempts++;
                    }
                    $completionMethod = 'Success on users count';
                    if ($countAttempts === 0) {
                        // In some cases, there are no attempts at all. Return 0 completion & score.
                        $averageScore = 0;
                        $completion = 0;
                    } else {
                        $averageScore = round(($scoreSum / $countAttempts) * 100, 2);
                        if (empty($countUsersInCourses[$cId])) {
                            // Users might have all been unsubscribed from the course since taking the test
                            $completion = $countSuccess / $countAttempts;
                            $completionMethod = 'Success on attempts count';
                        } else {
                            $completion = $countSuccess / $countUsersInCourses[$cId];
                        }
                    }
                    $params = [
                        'id' => $item,
                        'title' => $title,
                        'created_by' => $createdBy,
                        'updated_by' => $updatedBy,
                        'type' => $type,
                        'completion' => $completion,
                        'completion_method' => $completionMethod,
                        'number_of_last_attempts' => $countAttempts,
                        'average_score_in_percent' => $averageScore,
                    ];
                    foreach ($extraArray as $name => $value) {
                        $params[$name] = $value;
                    }
                    $resultArray[] = $params;
                }
            }
        }

        return $resultArray;
    }

    public function logout()
    {
        online_logout($this->user->getId());

        Event::courseLogout(
            [
                'uid' => $this->user->getId(),
                'cid' => $this->course ? $this->course->getId() : 0,
                'sid' => $this->session ? $this->session->getId() : 0,
            ]
        );
    }

    /**
     * @throws Exception
     */
    public function setThreadNotify(int $threadId): string
    {
        require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';

        $result = set_notification(
            'thread',
            $threadId,
            false,
            api_get_user_info($this->user->getId()),
            api_get_course_info($this->course->getCode())
        );

        if (false === $result) {
            self::throwNotAllowedException();
        }

        return $result;
    }

    public function getCourseWorks(): array
    {
        Event::event_access_tool(TOOL_STUDENTPUBLICATION);

        require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';

        $isAllowedToEdit = $this->user->getStatus() !== STUDENT;

        $courseId = $this->course->getId();
        $sessionId = $this->session ? $this->session->getId() : 0;

        $courseInfo = api_get_course_info_by_id($this->course->getId());

        $works = array_filter(
            getWorkListTeacherData($courseId, $sessionId, 0, 0, 0, 'title', 'ASC', ''),
            function (array $work) use ($isAllowedToEdit, $courseInfo, $courseId, $sessionId) {
                if (!$isAllowedToEdit
                    && !userIsSubscribedToWork($this->user->getId(), $work['id'], $courseId)
                ) {
                    return false;
                }

                $visibility = api_get_item_visibility($courseInfo, 'work', $work['id'], $sessionId);

                if (!$isAllowedToEdit && $visibility != 1) {
                    return false;
                }

                return true;
            }
        );

        return array_map(
            function (array $work) use ($isAllowedToEdit, $courseInfo) {
                $work['type'] = 'work.png';

                if (!$isAllowedToEdit) {
                    $workList = get_work_user_list(
                        0,
                        1000,
                        null,
                        null,
                        $work['id'],
                        ' AND u.id = '.$this->user->getId()
                    );

                    $count = getTotalWorkComment($workList, $courseInfo);
                    $lastWork = getLastWorkStudentFromParentByUser($this->user->getId(), $work, $courseInfo);

                    $work['feedback'] = ' '.Display::label('0 '.get_lang('Feedback'), 'warning');

                    if (!empty($count)) {
                        $work['feedback'] = ' '.Display::label($count.' '.get_lang('Feedback'), 'info');
                    }

                    $work['last_upload'] = '';

                    if (!empty($lastWork)) {
                        $work['last_upload'] = !empty($lastWork['qualification'])
                            ? $lastWork['qualification_rounded'].' - '
                            : '';
                        $work['last_upload'] .= api_get_local_time($lastWork['sent_date']);
                    }
                }

                return $work;
            },
            $works
        );
    }

    /**
     * Returns a list of exercises in the given course. The given course is received through generic param at instanciation.
     *
     * @param array $fields A list of extra fields to include in the answer. Searches for the field in exercise, then in course
     */
    public function getCourseExercises($fields = []): array
    {
        Event::event_access_tool(TOOL_QUIZ);

        $sessionId = $this->session ? $this->session->getId() : 0;
        $courseInfo = api_get_course_info_by_id($this->course->getId());

        // Check the extra fields criteria (whether to add extra field information or not)
        $fieldSource = [];
        if (count($fields) > 0) {
            // For each field, check where to get it from (quiz or course)
            $quizExtraField = new ExtraField('exercise');
            $courseExtraField = new ExtraField('course');
            foreach ($fields as $fieldName) {
                $fieldExists = $quizExtraField->get_handler_field_info_by_field_variable($fieldName);
                if ($fieldExists === false) {
                    // The field does not exist on the exercise, so use it from the course
                    $courseFieldExists = $courseExtraField->get_handler_field_info_by_field_variable($fieldName);
                    if ($courseFieldExists === false) {
                        continue;
                    }
                    $fieldSource[$fieldName] = ['item_type' => 'course', 'id' => $courseFieldExists['id']];
                } else {
                    $fieldSource[$fieldName] = ['item_type' => 'exercise', 'id' => $fieldExists['id']];
                }
            }
        }
        $list = ExerciseLib::get_all_exercises($courseInfo, $sessionId);

        // Now check the whole extra fields thing
        if (count($fieldSource) > 0) {
            $extraFieldValuesTable = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
            $fieldsSearchString = "SELECT field_id, value FROM $extraFieldValuesTable WHERE item_id = %d AND field_id = %d";
            foreach ($list as $id => $exercise) {
                foreach ($fieldSource as $fieldName => $fieldDetails) {
                    if ($fieldDetails['item_type'] == 'course') {
                        $itemId = $exercise['c_id'];
                    } else {
                        $itemId = $exercise['iid'];
                    }
                    $result = Database::query(sprintf($fieldsSearchString, $itemId, $fieldDetails['id']));
                    if (Database::num_rows($result) > 0) {
                        $row = Database::fetch_assoc($result);
                        $list[$id]['extra_'.$fieldName] = $row['value'];
                    } else {
                        $list[$id]['extra_'.$fieldName] = '';
                    }
                }
            }
        }
        foreach ($list as $id => $row) {
            // Get item authoring data
            $itemProps = api_get_last_item_property_info($row['c_id'], 'quiz', $row['iid']);
            $createdBy = $this->__getConfiguredUsernameById($itemProps['insert_user_id']);
            if ($itemProps['insert_user_id'] == $itemProps['lastedit_user_id']) {
                $updatedBy = $createdBy;
            } else {
                $updatedBy = $this->__getConfiguredUsernameById($itemProps['lastedit_user_id']);
            }
            $list[$id]['created_by'] = $createdBy;
            $list[$id]['updated_by'] = $updatedBy;
        }

        return $list;
    }

    /**
     * @throws Exception
     */
    public function putCourseWorkVisibility(int $workId, int $status): bool
    {
        Event::event_access_tool(TOOL_STUDENTPUBLICATION);

        $courseInfo = api_get_course_info_by_id($this->course->getId());

        require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';

        switch ($status) {
            case 1:
                return makeVisible($workId, $courseInfo);
            case 0:
                return makeInvisible($workId, $courseInfo);
            default:
                throw new Exception(get_lang('ActionNotAllowed'));
        }
    }

    public function deleteWorkStudentItem(int $workId): string
    {
        Event::event_access_tool(TOOL_STUDENTPUBLICATION);

        require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';

        $courseInfo = api_get_course_info_by_id($this->course->getId());

        $fileDeleted = deleteWorkItem($workId, $courseInfo);

        if ($fileDeleted) {
            return get_lang('TheDocumentHasBeenDeleted');
        }

        return get_lang('YouAreNotAllowedToDeleteThisDocument');
    }

    public function deleteWorkCorrections(int $workId): string
    {
        Event::event_access_tool(TOOL_STUDENTPUBLICATION);

        require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';

        $courseInfo = api_get_course_info_by_id($this->course->getId());

        $result = get_work_user_list(null, null, null, null, $workId);

        if ($result) {
            foreach ($result as $item) {
                $workInfo = get_work_data_by_id($item['id']);

                deleteCorrection($courseInfo, $workInfo);
            }
        }

        return get_lang('Deleted');
    }

    public function getWorkList(int $workId): array
    {
        $isAllowedToEdit = api_is_allowed_to_edit();

        Event::event_access_tool(TOOL_STUDENTPUBLICATION);

        require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';

        $userId = $this->user->getId();
        $courseId = $this->course->getId();
        $sessionId = $this->session ? $this->session->getId() : 0;

        $courseInfo = api_get_course_info_by_id($courseId);
        $webPath = api_get_path(WEB_PATH);

        $whereCondition = !$isAllowedToEdit ? " AND u.id = $userId" : '';

        $works = get_work_user_list(
            0,
            0,
            'title',
            'asc',
            $workId,
            $whereCondition,
            null,
            false,
            $courseId,
            $sessionId
        );

        return array_map(
            function (array $work) use ($courseInfo, $webPath) {
                $itemId = $work['id'];
                $count = getWorkCommentCount($itemId, $courseInfo);

                $work['feedback'] = $count.' '.Display::returnFontAwesomeIcon('comments-o');
                $work['feedback_clean'] = $count;

                $workInfo = get_work_data_by_id($itemId);
                $commentsTmp = getWorkComments($workInfo);
                $comments = [];

                foreach ($commentsTmp as $comment) {
                    $comment['comment'] = str_replace('src="/', 'src="'.$webPath.'app/', $comment['comment']);
                    $comments[] = $comment;
                }

                $work['comments'] = $comments;

                if (empty($workInfo['qualificator_id'])) {
                    $qualificator_id = Display::label(get_lang('NotRevised'), 'warning');
                } else {
                    $qualificator_id = Display::label(get_lang('Revised'), 'success');
                }

                $work['qualificator_id'] = $qualificator_id;

                return $work;
            },
            $works
        );
    }

    public function getWorkStudentsWithoutPublications(int $workId): array
    {
        Event::event_access_tool(TOOL_STUDENTPUBLICATION);

        require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';

        return get_list_users_without_publication($workId);
    }

    public function getWorkUsers(int $workId): array
    {
        Event::event_access_tool(TOOL_STUDENTPUBLICATION);

        require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';

        $courseId = $this->course->getId();
        $sessionId = $this->session ? $this->session->getId() : 0;
        $courseInfo = api_get_course_info_by_id($courseId);

        $items = getAllUserToWork($workId, $courseId);
        $usersAdded = [];
        $result = [
            'users_added' => [],
            'users_to_add' => [],
        ];

        if (!empty($items)) {
            foreach ($items as $data) {
                $usersAdded[] = $data['user_id'];

                $userInfo = api_get_user_info($data['user_id']);

                $result['users_added'][] = [
                    'user_id' => (int) $data['user_id'],
                    'complete_name_with_username' => $userInfo['complete_name_with_username'],
                ];
            }
        }

        if (empty($sessionId)) {
            $status = STUDENT;
        } else {
            $status = 0;
        }

        $userList = CourseManager::get_user_list_from_course_code(
            $courseInfo['code'],
            $sessionId,
            null,
            null,
            $status
        );

        $userToAddList = [];
        foreach ($userList as $user) {
            if (!in_array($user['user_id'], $usersAdded)) {
                $userToAddList[] = $user;
            }
        }

        if (!empty($userToAddList)) {
            foreach ($userToAddList as $user) {
                $userName = api_get_person_name($user['firstname'], $user['lastname']).' ('.$user['username'].') ';

                $result['users_to_add'][] = [
                    'user_id' => (int) $user['user_id'],
                    'complete_name_with_username' => $userName,
                ];
            }
        }

        return $result;
    }

    public function getWorkStudentList(int $workId): array
    {
        Event::event_access_tool(TOOL_STUDENTPUBLICATION);

        require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';

        $courseId = $this->course->getId();
        $courseCode = $this->course->getCode();
        $sessionId = $this->session ? $this->session->getId() : 0;

        $myFolderData = get_work_data_by_id($workId);

        $workParents = [];

        if (empty($myFolderData)) {
            $workParents = getWorkList($workId, $myFolderData);
        }

        $workIdList = [];

        if (!empty($workParents)) {
            foreach ($workParents as $work) {
                $workIdList[] = $work->id;
            }
        }

        $userList = getWorkUserList(
            $courseCode,
            $sessionId,
            0,
            0,
            null,
            null,
            null
        );

        return array_map(
            function ($userId) use ($courseId, $sessionId, $workParents, $workIdList) {
                $user = api_get_user_info($userId);

                $userWorks = 0;

                if (!empty($workIdList)) {
                    $userWorks = getUniqueStudentAttempts(
                        $workIdList,
                        0,
                        $courseId,
                        $sessionId,
                        $user['user_id']
                    );
                }

                $works = $userWorks." / ".count($workParents);

                return [
                    'id' => $userId,
                    'complete_name' => api_get_person_name($user['firstname'], $user['lastname']),
                    'works' => $works,
                ];
            },
            $userList
        );
    }

    public function viewUserProfile(int $userId)
    {
        $url = api_get_path(WEB_CODE_PATH).'social/profile.php';

        if ($userId) {
            $url .= '?'.http_build_query(['u' => $userId]);
        }

        header("Location: $url");
        exit;
    }

    public function viewCourseHome()
    {
        $url = api_get_course_url($this->course->getCode(), $this->session ? $this->session->getId() : 0);

        header("Location: $url");
        exit;
    }

    public function viewDocumentInFrame(int $documentId)
    {
        $courseCode = $this->course->getCode();
        $sessionId = $this->session ? $this->session->getId() : 0;

        $url = api_get_path(WEB_CODE_PATH).'document/showinframes.php?'
            .http_build_query(
                [
                    'cidReq' => $courseCode,
                    'id_session' => $sessionId,
                    'gidReq' => 0,
                    'gradebook' => 0,
                    'origin' => self::SERVICE_NAME,
                    'id' => $documentId,
                ]
            );

        header("Location: $url");
        exit;
    }

    public function viewQuizTool()
    {
        $courseCode = $this->course->getCode();
        $sessionId = $this->session ? $this->session->getId() : 0;

        $url = api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'
            .http_build_query(
                [
                    'cidReq' => $courseCode,
                    'id_session' => $sessionId,
                    'gidReq' => 0,
                    'gradebook' => 0,
                    'origin' => self::SERVICE_NAME,
                ]
            );

        header("Location: $url");
        exit;
    }

    public function viewSurveyTool()
    {
        $courseCode = $this->course->getCode();
        $sessionId = $this->session ? $this->session->getId() : 0;

        $url = api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'
            .http_build_query(
                [
                    'cidReq' => $courseCode,
                    'id_session' => $sessionId,
                    'gidReq' => 0,
                    'gradebook' => 0,
                    'origin' => self::SERVICE_NAME,
                ]
            );

        header("Location: $url");
        exit;
    }

    public function viewMessage(int $messageId)
    {
        $url = api_get_path(WEB_CODE_PATH).'messages/view_message.php?'.http_build_query(['id' => $messageId]);

        header("Location: $url");
        exit;
    }

    public function downloadForumPostAttachment(string $path)
    {
        $courseCode = $this->course->getCode();
        $sessionId = $this->session ? $this->session->getId() : 0;

        $url = api_get_path(WEB_CODE_PATH).'forum/download.php?'
            .http_build_query(
                [
                    'cidReq' => $courseCode,
                    'id_session' => $sessionId,
                    'gidReq' => 0,
                    'gradebook' => 0,
                    'origin' => self::SERVICE_NAME,
                    'file' => Security::remove_XSS($path),
                ]
            );

        header("Location: $url");
        exit;
    }

    public function downloadWorkFolder(int $workId)
    {
        $cidReq = api_get_cidreq();
        $url = api_get_path(WEB_CODE_PATH)."work/downloadfolder.inc.php?id=$workId&$cidReq";

        header("Location: $url");
        exit;
    }

    public function downloadWorkCommentAttachment(int $commentId)
    {
        $cidReq = api_get_cidreq();
        $url = api_get_path(WEB_CODE_PATH)."work/download_comment_file.php?comment_id=$commentId&$cidReq";

        header("Location: $url");
        exit;
    }

    public function downloadWork(int $workId, bool $isCorrection = false)
    {
        $cidReq = api_get_cidreq();
        $url = api_get_path(WEB_CODE_PATH)."work/download.php?$cidReq&"
            .http_build_query(
                [
                    'id' => $workId,
                    'correction' => $isCorrection ? 1 : null,
                ]
            );

        header("Location: $url");
        exit;
    }

    /**
     * @throws Exception
     */
    public function getAllUsersApiKeys(int $page, int $length, bool $force = false, ?int $urlId = null): array
    {
        if (false === api_get_configuration_value('webservice_enable_adminonly_api')
            || !UserManager::is_admin($this->user->getId())
        ) {
            self::throwNotAllowedException();
        }

        $limitOffset = ($page - 1) * $length;

        $currentUrlId = $urlId ?: api_get_current_access_url_id();

        $data = [];
        $data['total'] = UserManager::get_number_of_users(0, $currentUrlId);
        $data['list'] = array_map(
            function (array $user) use ($force) {
                $apiKeys = UserManager::get_api_keys($user['id'], self::SERVICE_NAME);
                $apiKey = $apiKeys ? current($apiKeys) : null;

                if ($force && empty($apiKey)) {
                    $apiKey = self::generateApiKeyForUser((int) $user['id']);
                }

                return [
                    'id' => (int) $user['id'],
                    'username' => $user['username'],
                    'api_key' => $apiKey,
                ];
            },
            $users = UserManager::get_user_list([], [], $limitOffset, $length, $currentUrlId)
        );
        $data['length'] = count($users);

        if ($page * $length < $data['total']) {
            $nextPageQueryParams = [
                'page' => $page + 1,
                'per_page' => $length,
                'url_id' => $urlId,
            ];

            $data['next'] = $this->generateUrl($nextPageQueryParams);
        }

        return $data;
    }

    /**
     * @throws Exception
     */
    public function getUserApiKey(string $username, bool $force = false): array
    {
        if (false === api_get_configuration_value('webservice_enable_adminonly_api')
            || !UserManager::is_admin($this->user->getId())
        ) {
            self::throwNotAllowedException();
        }

        $userInfo = api_get_user_info_from_username($username);

        if (empty($userInfo)) {
            throw new Exception(get_lang('UserNotFound'));
        }

        $apiKeys = UserManager::get_api_keys($userInfo['id'], self::SERVICE_NAME);
        $apiKey = $apiKeys ? current($apiKeys) : null;

        if ($force && empty($apiKey)) {
            $apiKey = self::generateApiKeyForUser((int) $userInfo['id']);
        }

        return [
            'id' => $userInfo['id'],
            'username' => $userInfo['username'],
            'api_key' => $apiKey,
        ];
    }

    public static function isAllowedByRequest(bool $inpersonate = false): bool
    {
        $username = $_GET['username'] ?? null;
        $apiKey = $_GET['api_key'] ?? null;

        if (empty($username) || empty($apiKey)) {
            return false;
        }

        try {
            $restApi = self::validate($username, $apiKey);
        } catch (Exception $e) {
            return false;
        }

        if ($inpersonate) {
            Login::init_user($restApi->getUser()->getId(), true);
        }

        return (bool) $restApi;
    }

    public function viewMyCourses()
    {
        $url = api_get_path(WEB_PATH).'user_portal.php?'
            .http_build_query(['nosession' => 'true']);

        header("Location: $url");
        exit;
    }

    /**
     * Create a group/class.
     *
     * @param $params
     *
     * @throws Exception
     */
    public function addGroup($params): array
    {
        self::protectAdminEndpoint();

        if (!empty($params['type'])) {
            $params['group_type'] = $params['type'];
        }

        // First check wether the login already exists.
        $userGroup = new UserGroup();
        if ($userGroup->usergroup_exists($params['name'])) {
            throw new Exception($params['name'].' '.get_lang('AlreadyExists'));
        }

        $groupId = $userGroup->save($params);

        if (empty($groupId)) {
            throw new Exception(get_lang('NotRegistered'));
        }

        return [$groupId];
    }

    /**
     * Delete a group/class.
     *
     * @throws Exception
     *
     * @return bool
     */
    public function deleteGroup(int $id): array
    {
        self::protectAdminEndpoint();

        if (empty($id)) {
            return false;
        }

        // First check wether the login already exists.
        $userGroup = new UserGroup();
        if (!$userGroup->delete($id)) {
            throw new Exception(get_lang('NotDeleted'));
        }

        return [$id];
    }

    /**
     * Get the list of users subscribed to the given group/class.
     *
     * @return array The list of users (userID => [firstname, lastname, relation_type]
     */
    public function getGroupSubscribedUsers(int $groupId): array
    {
        $userGroup = new UserGroup();

        return $userGroup->get_all_users_by_group($groupId);
    }

    /**
     * Get the list of courses to which the given group/class is subscribed.
     *
     * @return array The list of courses (ID => [title]
     */
    public function getGroupSubscribedCourses(int $groupId): array
    {
        $userGroup = new UserGroup();

        return $userGroup->get_courses_by_usergroup($groupId, true);
    }

    /**
     * Get the list of sessions to which the given group/class is subscribed.
     *
     * @return array The list of courses (ID => [title]
     */
    public function getGroupSubscribedSessions(int $groupId): array
    {
        $userGroup = new UserGroup();

        return $userGroup->get_sessions_by_usergroup($groupId, true);
    }

    /**
     * Add a new user to the given group/class.
     *
     * @param int $groupId
     * @param int $userId
     * @param int $relationType (1:admin, 2:reader, etc. See GROUP_USER_PERMISSION_ constants in api.lib.php)
     *
     * @return array One item array containing true on success, false otherwise
     */
    public function addGroupSubscribedUser(int $groupId, int $userId, int $relationType = 2): array
    {
        $userGroup = new UserGroup();

        if (!$userGroup->groupExists($groupId) or !$userGroup->userExists($userId)) {
            throw new Exception('user_id or group_id does not exist');
        }

        return [$userGroup->add_user_to_group($userId, $groupId, $relationType)];
    }

    /**
     * Get the list of group/class IDs to which the user belongs.
     *
     * @param int $userId
     *
     * @return array Array containing the group IDs like ['groups' => [1, 2, 3]]
     */
    public function getUserSubGroup(int $userId): array
    {
        $userGroup = new UserGroup();

        $res = $userGroup->get_usergroup_by_user($userId);
        return ['groups' => $res];
    }

    /**
     * Add a new course to which the given group/class is subscribed.
     *
     * @return array One item array containing the ID of the course on success, nothing on failure
     */
    public function addGroupSubscribedCourse(int $groupId, int $courseId): array
    {
        $userGroup = new UserGroup();

        return [$userGroup->subscribe_courses_to_usergroup($groupId, [$courseId], false)];
    }

    /**
     * Add a new session to which the given group/class is subscribed.
     *
     * @return array One item array containing the ID of the session on success, nothing on failure
     */
    public function addGroupSubscribedSession(int $groupId, int $sessionId): array
    {
        $userGroup = new UserGroup();

        return [$userGroup->subscribe_sessions_to_usergroup($groupId, [$sessionId], false)];
    }

    /**
     * Remove a user from the given group/class.
     *
     * @return array One item array containing true on success, false otherwise
     */
    public function deleteGroupSubscribedUser(int $groupId, int $userId): array
    {
        $userGroup = new UserGroup();

        return [$userGroup->delete_user_rel_group($userId, $groupId)];
    }

    /**
     * Remove a course to which the given group/class is subscribed.
     *
     * @return array One item array containing true on success, false otherwise
     */
    public function deleteGroupSubscribedCourse(int $groupId, int $courseId): array
    {
        $userGroup = new UserGroup();

        return [$userGroup->unsubscribe_courses_from_usergroup($groupId, [$courseId])];
    }

    /**
     * Remove a session to which the given group/class is subscribed.
     *
     * @return array One item array containing true on success, false otherwise
     */
    public function deleteGroupSubscribedSession(int $groupId, int $sessionId): array
    {
        $userGroup = new UserGroup();

        return [$userGroup->unsubscribeSessionsFromUserGroup($groupId, [$sessionId], false)];
    }

    protected static function generateApiKeyForUser(int $userId): string
    {
        UserManager::add_api_key($userId, self::SERVICE_NAME);

        $apiKeys = UserManager::get_api_keys($userId, self::SERVICE_NAME);

        return current($apiKeys);
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

    private function generateUrl(array $additionalParams = []): string
    {
        $queryParams = [
            'course' => $this->course ? $this->course->getId() : null,
            'session' => $this->session ? $this->session->getId() : null,
            'api_key' => $this->apiKey,
            'username' => $this->user->getUsername(),
        ];

        return api_get_self().'?'
            .http_build_query(array_merge($queryParams, $additionalParams));
    }
}

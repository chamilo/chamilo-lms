<?php

/* For licensing terms, see /license.txt */

/**
 * Entry point for REST web services in Chamilo.
 *
 * Call it with the 'authenticate' action first, to get an api_key, then use
 * the api_key in all subsequent calls.
 *
 * Send the REST call parameters as a 'hash' in POST or GET. The hash must be
 * JSON encoded and contain at least 'action', 'username', and either
 * 'password' for the first call or 'api_key' in subsequent calls.
 * You can store the API key on an external system (it will remain the same),
 * although it is not recommended to do so (for security reasons).
 */

use Symfony\Component\HttpFoundation\Request as HttpRequest;

require_once __DIR__.'/../../inc/global.inc.php';

api_protect_webservices();

$httpRequest = HttpRequest::createFromGlobals();

$hash = $httpRequest->query->get('hash');

if ($hash) {
    $hashParams = Rest::decodeParams($hash);
    if (!empty($hashParams)) {
        foreach ($hashParams as $key => $value) {
            $httpRequest->query->set($key, Security::remove_XSS($value));
        }
    }
}

$action = $httpRequest->query->get('action') ?: $httpRequest->request->get('action');
$username = Security::remove_XSS(
    $httpRequest->query->get('username') ?: $httpRequest->request->get('username')
);
$apiKey = Security::remove_XSS(
    $httpRequest->query->get('api_key') ?: $httpRequest->request->get('api_key')
);
$course = $httpRequest->query->getInt('course') ?: $httpRequest->request->getInt('course');
$session = $httpRequest->query->getInt('session') ?: $httpRequest->request->getInt('session');

$restResponse = new RestResponse();

try {
    /** @var Rest $restApi */
    $restApi = $apiKey ? Rest::validate($username, $apiKey) : null;

    if ($restApi) {
        LoginCheck($restApi->getUser()->getId());
        Tracking::updateUserLastLogin($restApi->getUser()->getId());

        $restApi->setCourse($course);
        $restApi->setSession($session);

        if ($course) {
            Event::accessCourse();
            Event::eventCourseLoginUpdate(api_get_course_int_id(), api_get_user_id(), api_get_session_id());
        }
    }

    switch ($action) {
        case Rest::GET_AUTH:
            Rest::init();

            $password = $_POST['password'] ?? null;
            $isValid = Rest::isValidUser($username, $password);
            if (!$isValid) {
                throw new Exception(get_lang('InvalideUserDetected'));
            }
            $userId = UserManager::get_user_id_from_username($username);
            Event::addEvent(LOG_WS.$action, 'username', $username, null, $userId);
            $restResponse->setData([
                'url' => api_get_path(WEB_PATH),
                'apiKey' => Rest::findUserApiKey($username, Rest::SERVICE_NAME),
                'gcmSenderId' => api_get_setting('messaging_gdc_project_number'),
            ]);
            break;
        case Rest::SAVE_GCM_ID:
            $gcmId = isset($_POST['registration_id']) ? Security::remove_XSS($_POST['registration_id']) : null;
            Event::addEvent(LOG_WS.$action, 'gcm_id', $gcmId);
            $restApi->setGcmId($gcmId);
            $restResponse->setData(['status' => true]);
            break;
        case Rest::LOGOUT:
            Event::addEvent(LOG_WS.$action, 'username', $username);
            $restApi->logout();
            $restResponse->setData(['status' => true]);
            break;
        case Rest::GET_USER_MESSAGES:
            $lastMessageId = isset($_POST['last']) ? (int) $_POST['last'] : 0;
            $messages = $restApi->getUserMessages($lastMessageId);
            Event::addEvent(LOG_WS.$action, 'last_message_id', $lastMessageId);
            $restResponse->setData($messages);
            break;
        case Rest::GET_USER_MESSAGES_RECEIVED:
            Event::addEvent(LOG_WS.$action, 'username', $username);
            $messages = $restApi->getUserReceivedMessages();
            $restResponse->setData($messages);
            break;
        case Rest::DELETE_USER_MESSAGE:
            $messageId = isset($_POST['message_id']) ? (int) $_POST['message_id'] : 0;
            $messageType = !empty($_POST['msg_type']) ? $_POST['msg_type'] : '';
            Event::addEvent(LOG_WS.$action, 'message_id', $messageId);
            $restApi->deleteUserMessage($messageId, $messageType);
            $restResponse->setData(['status' => true]);
            break;
        case Rest::GET_USER_MESSAGES_SENT:
            Event::addEvent(LOG_WS.$action, 'username', $username);
            $messages = $restApi->getUserSentMessages();
            $restResponse->setData($messages);
            break;
        case Rest::GET_COUNT_NEW_MESSAGES:
            Event::addEvent(LOG_WS.$action, 'username', $username);
            $restResponse->setData(
                MessageManager::getMessagesCountForUser($restApi->getUser()->getId())
            );
            break;
        case Rest::SET_MESSAGE_READ:
            $messageId = isset($_POST['message_id']) ? (int) $_POST['message_id'] : 0;
            $restApi->setMessageRead($messageId);
            Event::addEvent(LOG_WS.$action, 'message_id', $messageId);
            $restResponse->setData(['status' => true]);
            break;
        case Rest::POST_USER_MESSAGE_READ:
        case Rest::POST_USER_MESSAGE_UNREAD:
            $messagesId = isset($_POST['messages']) && is_array($_POST['messages'])
                ? array_map('intval', $_POST['messages'])
                : [];

            $messagesId = array_filter($messagesId);
            if (empty($messagesId)) {
                throw new Exception(get_lang('NoData'));
            }
            $messageStatus = $action === Rest::POST_USER_MESSAGE_READ ? MESSAGE_STATUS_NEW : MESSAGE_STATUS_UNREAD;
            $data = array_flip($messagesId);

            foreach ($messagesId as $messageId) {
                $data[$messageId] = MessageManager::update_message_status(
                    $restApi->getUser()->getId(),
                    $messageId,
                    $messageStatus
                );
            }
            Event::addEvent(LOG_WS.$action, 'messages_id', implode(',', $messagesId));

            $restResponse->setData($data);
            break;
        case Rest::SAVE_USER_MESSAGE:
            $receivers = $_POST['receivers'] ?? [];
            $subject = !empty($_POST['subject']) ? $_POST['subject'] : null;
            $text = !empty($_POST['text']) ? $_POST['text'] : null;
            $data = $restApi->saveUserMessage($subject, $text, $receivers);
            Event::addEvent(LOG_WS.$action, 'username', $username);
            $restResponse->setData($data);
            break;
        case Rest::GET_MESSAGE_USERS:
            $search = !empty($_REQUEST['q']) ? $_REQUEST['q'] : null;
            if (!$search || strlen($search) < 2) {
                throw new Exception(get_lang('TooShort'));
            }
            Event::addEvent(LOG_WS.$action, 'filter_search', $search);
            $data = $restApi->getMessageUsers($search);
            $restResponse->setData($data);
            break;
        case Rest::VIEW_MESSAGE:
            $messageId = isset($_GET['message']) ? (int) $_GET['message'] : 0;
            Event::addEvent(LOG_WS.$action, 'message_id', $messageId);
            $restApi->viewMessage($messageId);
            break;
        case Rest::GET_USER_COURSES:
            $userId = isset($_REQUEST['user_id']) ? (int) $_REQUEST['user_id'] : 0;
            Event::addEvent(LOG_WS.$action, 'username', $username);
            $courses = $restApi->getUserCourses($userId);
            $restResponse->setData($courses);
            break;
        case Rest::GET_USER_SESSIONS:
            Event::addEvent(LOG_WS.$action, 'username', $username);
            $courses = $restApi->getUserSessions();
            $restResponse->setData($courses);
            break;
        case Rest::VIEW_PROFILE:
            $userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
            Event::addEvent(LOG_WS.$action, 'user_id', $userId);
            $restApi->viewUserProfile($userId);
            break;
        case Rest::GET_PROFILE:
            Event::addEvent(LOG_WS.$action, 'username', $username);
            $userInfo = $restApi->getUserProfile();
            $restResponse->setData($userInfo);
            break;
        case Rest::GET_PROFILES_BY_EXTRA_FIELD:
            Event::addEvent(LOG_WS.$action, 'extra_field_name', $_POST['field_name']);
            $active = !empty($_POST['active']) && $_POST['active'] == 1 ? 1 : 0;
            // If "active" is set, will drop inactive users (user.active = 0) from the response
            $users = $restApi->getUsersProfilesByExtraField($_POST['field_name'], $_POST['field_value'], $active);
            $restResponse->setData($users);
            break;
        case Rest::GET_COURSES_DETAILS_BY_EXTRA_FIELD:
            Event::addEvent(LOG_WS.$action, 'extra_field_name', $_POST['field_name']);
            $courses = $restApi->getCoursesByExtraField($_POST['field_name'], $_POST['field_value']);
            $restResponse->setData($courses);
            break;
        case Rest::GET_USER_COURSES_BY_DATES:
            Event::addEvent(LOG_WS.$action, 'user_id', (int) $_POST['user_id']);
            $courses = $restApi->getUserCoursesByDates($_POST['user_id'], $_POST['start_date'], $_POST['end_date']);
            $restResponse->setData($courses);
            break;
        case Rest::VIEW_MY_COURSES:
            Event::addEvent(LOG_WS.$action, 'username', $username);
            $restApi->viewMyCourses();
            break;
        case Rest::VIEW_COURSE_HOME:
            Event::addEvent(LOG_WS.$action, 'username', $username);
            $restApi->viewCourseHome();
            break;
        case Rest::GET_COURSE_INFO:
            Event::addEvent(LOG_WS.$action, 'course_id', (int) $_POST['course']);
            $courseInfo = $restApi->getCourseInfo();
            $restResponse->setData($courseInfo);
            break;
        case Rest::GET_COURSE_DESCRIPTIONS:
            Event::addEvent(LOG_WS.$action, 'course_id', (int) $_POST['course']);
            $fields = $_POST['fields'] ?? [];
            $descriptions = $restApi->getCourseDescriptions($fields);
            $restResponse->setData($descriptions);
            break;
        case Rest::GET_COURSE_DOCUMENTS:
            $directoryId = isset($_POST['dir_id']) ? Security::remove_XSS($_POST['dir_id']) : null;
            Event::addEvent(LOG_WS.$action, 'directory_id', $directoryId);
            $documents = $restApi->getCourseDocuments($directoryId);
            $restResponse->setData($documents);
            break;
        case Rest::GET_COURSE_ANNOUNCEMENTS:
            Event::addEvent(LOG_WS.$action, 'course_id', (int) $_POST['course']);
            $announcements = $restApi->getCourseAnnouncements();
            $restResponse->setData($announcements);
            break;
        case Rest::GET_COURSE_ANNOUNCEMENT:
            $announcementId = isset($_POST['announcement']) ? Security::remove_XSS($_POST['announcement']) : 0;
            Event::addEvent(LOG_WS.$action, 'announcement_id', $announcementId);
            $announcement = $restApi->getCourseAnnouncement($announcementId);
            $restResponse->setData($announcement);
            break;
        case Rest::GET_COURSE_AGENDA:
            Event::addEvent(LOG_WS.$action, 'course_id', (int) $_POST['course']);
            $agenda = $restApi->getCourseAgenda();
            $restResponse->setData($agenda);
            break;
        case Rest::GET_COURSE_NOTEBOOKS:
            Event::addEvent(LOG_WS.$action, 'course_id', (int) $_POST['course']);
            $notebooks = $restApi->getCourseNotebooks();
            $restResponse->setData($notebooks);
            break;
        case Rest::GET_COURSE_FORUM_CATEGORIES:
            Event::addEvent(LOG_WS.$action, 'course_id', (int) $_POST['course']);
            $forums = $restApi->getCourseForumCategories();
            $restResponse->setData($forums);
            break;
        case Rest::GET_COURSE_FORUM:
            $forumId = isset($_POST['forum']) ? Security::remove_XSS($_POST['forum']) : 0;
            Event::addEvent(LOG_WS.$action, 'course_id-forum_id', (int) $_POST['forum'].':'.$forumId);
            $forum = $restApi->getCourseForum($forumId);
            $restResponse->setData($forum);
            break;
        case Rest::GET_COURSE_FORUM_THREAD:
            $forumId = isset($_POST['forum']) ? (int) $_POST['forum'] : 0;
            $threadId = isset($_POST['thread']) ? (int) $_POST['thread'] : 0;
            Event::addEvent(
                LOG_WS.$action,
                'course_id-forum_id-thread_id',
                (int) $_POST['course'].':'.$forumId.':'.$threadId
            );

            $thread = $restApi->getCourseForumThread($forumId, $threadId);
            $restResponse->setData($thread);
            break;
        case Rest::GET_COURSE_LEARNPATHS:
            Event::addEvent(LOG_WS.$action, 'username', $username);
            $data = $restApi->getCourseLearnPaths();
            $restResponse->setData($data);
            break;
        case Rest::GET_COURSE_LEARNPATH:
            $lpId = isset($_REQUEST['lp_id']) ? (int) $_REQUEST['lp_id'] : 1;
            Event::addEvent(LOG_WS.$action, 'lp_id', $lpId);
            $restApi->showLearningPath($lpId);
            break;
        case Rest::GET_COURSE_LP_PROGRESS:
            Event::addEvent(LOG_WS.$action, 'username', $username);
            $restResponse->setData($restApi->getCourseLpProgress());
            break;
        case Rest::GET_COURSE_LINKS:
            Event::addEvent(LOG_WS.$action, 'username', $username);
            $restResponse->setData(
                $restApi->getCourseLinks()
            );
            break;
        case Rest::GET_COURSE_WORKS:
            Event::addEvent(LOG_WS.$action, 'username', $username);
            $restResponse->setData(
                $restApi->getCourseWorks()
            );
            break;
        case Rest::GET_COURSE_EXERCISES:
            Event::addEvent(LOG_WS.$action, 'course_id', (int) $_POST['course']);
            $fields = $_POST['fields'] ?? [];
            $restResponse->setData(
                $restApi->getCourseExercises($fields)
            );
            break;
        case Rest::SAVE_COURSE_NOTEBOOK:
            $title = !empty($_POST['title']) ? $_POST['title'] : null;
            $text = !empty($_POST['text']) ? $_POST['text'] : null;
            $data = $restApi->saveCourseNotebook($title, $text);
            Event::addEvent(LOG_WS.$action, 'notebook_id', $data['registered']);
            $restResponse->setData($data);
            break;
        case Rest::SAVE_FORUM_POST:
            if (
                empty($_POST['title']) || empty($_POST['text']) || empty($_POST['thread']) || empty($_POST['forum'])
            ) {
                throw new Exception(get_lang('NoData'));
            }

            $forumId = $httpRequest->request->getInt('forum');
            $notify = $httpRequest->request->has('notify');
            $parentId = $httpRequest->request->getInt('parent') ?: null;

            $postValues = [
                'post_title' => $_POST['title'],
                'post_text' => nl2br($_POST['text']),
                'thread_id' => $_POST['thread'],
                'forum_id' => $_POST['forum'],
                'post_notification' => $notify,
                'post_parent_id' => $parentId,
            ];

            $data = $restApi->saveForumPost($postValues, $forumId);
            Event::addEvent(LOG_WS.$action, 'registered', $data['registered']);
            $restResponse->setData($data);
            break;
        case Rest::SAVE_FORUM_THREAD:
            if (empty($_POST['title']) || empty($_POST['text']) || empty($_POST['forum'])) {
                throw new Exception(get_lang('NoData'));
            }

            $forumId = $httpRequest->request->getInt('forum');
            $notify = !empty($_POST['notify']);
            $threadInfo = [
                'post_title' => $_POST['title'],
                'forum_id' => $_POST['forum'],
                'post_text' => nl2br($_POST['text']),
                'post_notification' => $notify,
            ];

            $data = $restApi->saveForumThread($threadInfo, $forumId);
            Event::addEvent(LOG_WS.$action, 'registered', $data['registered']);
            $restResponse->setData($data);
            break;
        case Rest::SET_THREAD_NOTIFY:
            $threadId = isset($_POST['thread']) ? (int) $_POST['thread'] : 0;

            if (empty($threadId)) {
                throw new Exception(get_lang('NoData'));
            }

            $restResponse->setData(
                [
                    'message' => $restApi->setThreadNotify($threadId),
                ]
            );
            Event::addEvent(LOG_WS.$action, 'thread_id', $threadId);
            break;
        case Rest::DOWNLOAD_FORUM_ATTACHMENT:
            if (empty($_GET['path'])) {
                throw new Exception(get_lang('ActionNotAllowed'));
            }
            Event::addEvent(LOG_WS.$action, 'path', $_GET['path']);
            $restApi->downloadForumPostAttachment($_GET['path']);
            break;
        case Rest::GET_WORK_LIST:
            if (!isset($_GET['work'])) {
                throw new Exception(get_lang('ActionNotAllowed'));
            }
            $workId = (int) $_GET['work'];
            Event::addEvent(LOG_WS.$action, 'work_id', $workId);
            $restResponse->setData(
                $restApi->getWorkList($workId)
            );
            break;
        case Rest::GET_WORK_STUDENTS_WITHOUT_PUBLICATIONS:
            if (!isset($_GET['work'])) {
                throw new Exception(get_lang('ActionNotAllowed'));
            }

            if (!api_is_allowed_to_edit(false, true)) {
                throw new Exception(get_lang('NotAllowed'));
            }
            $workId = (int) $_GET['work'];
            Event::addEvent(LOG_WS.$action, 'work_id', $workId);
            $restResponse->setData(
                $restApi->getWorkStudentsWithoutPublications($workId)
            );
            break;
        case Rest::GET_WORK_USERS:
            if (!isset($_GET['work'])) {
                throw new Exception(get_lang('ActionNotAllowed'));
            }

            if (!api_is_allowed_to_edit()) {
                throw new Exception(get_lang('NotAllowed'));
            }
            $workId = (int) $_GET['work'];
            Event::addEvent(LOG_WS.$action, 'work_id', $workId);
            $restResponse->setData(
                $restApi->getWorkUsers($workId)
            );
            break;
        case Rest::GET_WORK_STUDENT_LIST:
            if (!isset($_GET['work'])) {
                throw new Exception(get_lang('ActionNotAllowed'));
            }

            $workId = (int) $_GET['work'];

            if (!api_is_allowed_to_edit()) {
                throw new Exception(get_lang('NotAllowed'));
            }

            Event::addEvent(LOG_WS.$action, 'work_id', $workId);
            $restResponse->setData(
                $restApi->getWorkStudentList($workId)
            );
            break;
        case Rest::PUT_WORK_STUDENT_ITEM_VISIBILITY:
            if (!isset($_POST['status'], $_POST['work'])) {
                throw new Exception(get_lang('ActionNotAllowed'));
            }

            $workId = (int) $_POST['work'];

            if (!api_is_allowed_to_edit() && !api_is_coach()) {
                throw new Exception(get_lang('NotAllowed'));
            }

            $data = $restApi->putCourseWorkVisibility(
                $workId,
                (int) $_POST['status']
            );

            Event::addEvent(LOG_WS.$action, 'work_id', $workId);
            $restResponse->setData(['status' => $data]);
            break;
        case Rest::DELETE_WORK_STUDENT_ITEM:
            if (!isset($_POST['work'])) {
                throw new Exception(get_lang('ActionNotAllowed'));
            }

            if (!api_is_allowed_to_edit() && !api_is_coach()) {
                throw new Exception(get_lang('NotAllowed'));
            }
            $workId = (int) $_POST['work'];
            Event::addEvent(LOG_WS.$action, 'work_id', $workId);
            $restResponse->setData(
                [
                    'message' => $restApi->deleteWorkStudentItem($workId),
                ]
            );
            break;
        case Rest::DELETE_WORK_CORRECTIONS:
            if (!isset($_POST['work'])) {
                throw new Exception(get_lang('ActionNotAllowed'));
            }

            if (!api_is_allowed_to_edit() && !api_is_coach()) {
                throw new Exception(get_lang('NotAllowed'));
            }

            $workId = (int) $_POST['work'];
            Event::addEvent(LOG_WS.$action, 'work_id', $workId);
            $restResponse->setData(
                [
                    'message' => $restApi->deleteWorkCorrections($workId),
                ]
            );
            break;
        case Rest::DOWNLOAD_WORK_FOLDER:
            if (!isset($_GET['work'])) {
                throw new Exception(get_lang('ActionNotAllowed'));
            }

            $workId = (int) $_GET['work'];
            Event::addEvent(LOG_WS.$action, 'work_id', $workId);
            $restApi->downloadWorkFolder($workId);
            break;
        case Rest::DOWNLOAD_WORK_COMMENT_ATTACHMENT:
            if (!isset($_GET['comment'])) {
                throw new Exception(get_lang('ActionNotAllowed'));
            }

            Event::addEvent(LOG_WS.$action, 'comment_id', (int) $_GET['comment']);
            $restApi->downloadWorkCommentAttachment((int) $_GET['comment']);
            break;
        case Rest::DOWNLOAD_WORK:
            if (!isset($_GET['work'])) {
                throw new Exception(get_lang('ActionNotAllowed'));
            }

            $isCorrection = isset($_GET['correction']);
            $workId = (int) $_GET['work'];
            Event::addEvent(LOG_WS.$action, 'work_id', $workId);
            $restApi->downloadWork($workId, $isCorrection);
            break;
        case Rest::VIEW_DOCUMENT_IN_FRAME:
            $lpId = isset($_REQUEST['document']) ? (int) $_REQUEST['document'] : 0;
            Event::addEvent(LOG_WS.$action, 'document_id', $lpId);
            $restApi->viewDocumentInFrame($lpId);
            break;
        case Rest::VIEW_QUIZ_TOOL:
            Event::addEvent(LOG_WS.$action, 'username', $username);
            $restApi->viewQuizTool();
            break;
        case Rest::VIEW_SURVEY_TOOL:
            Event::addEvent(LOG_WS.$action, 'username', $username);
            $restApi->viewSurveyTool();
            break;
        case Rest::CREATE_CAMPUS:
            $data = $restApi->createCampusURL($_POST);
            Event::addEvent(LOG_WS.$action, 'campus_id', $data['id_campus']);
            $restResponse->setData($data);
            break;
        case Rest::EDIT_CAMPUS:
            $data = $restApi->editCampusURL($_POST);
            Event::addEvent(LOG_WS.$action, 'campus_id', $_POST['id']);
            $restResponse->setData($data);
            break;
        case Rest::DELETE_CAMPUS:
            $data = $restApi->deleteCampusURL($_POST);
            Event::addEvent(LOG_WS.$action, 'campus_id', $_POST['id']);
            $restResponse->setData($data);
            break;
        case Rest::GET_USERS:
            Event::addEvent(LOG_WS.$action, 'username', $username);
            $data = $restApi->getUsersCampus($_POST);
            $restResponse->setData($data);
            break;
        case Rest::USERNAME_EXIST:
            Event::addEvent(LOG_WS.$action, 'username', $_POST['loginname']);
            $data = $restApi->usernameExist($_POST['loginname']);
            $restResponse->setData([$data]);
            break;
        case Rest::SAVE_USER:
            $data = $restApi->addUser($_POST);
            Event::addEvent(LOG_WS.$action, 'user_id', $data);
            $restResponse->setData($data);
            break;
        case Rest::SAVE_USER_GET_APIKEY:
            $data = $restApi->addUserGetApikey($_POST);
            Event::addEvent(LOG_WS.$action, 'user_id', $data['id']);
            $restResponse->setData($data);
            break;
        case Rest::SAVE_USER_JSON:
            if (!array_key_exists('json', $_POST)) {
                throw new Exception(get_lang('NoData'));
            }
            $json = json_decode($_POST['json'], true);
            if (is_null($json)) {
                throw new Exception(get_lang('NoData'));
            }
            $data = $restApi->addUser($json);
            Event::addEvent(LOG_WS.$action, 'user_id', $data);
            $restResponse->setData($data);
            break;
        case Rest::UPDATE_USER_FROM_USERNAME:
            $data = $restApi->updateUserFromUserName($_POST);
            Event::addEvent(LOG_WS.$action, 'username', $username);
            $restResponse->setData([$data]);
            break;
        case Rest::UPDATE_USER_APIKEY:
            $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
            $currentApiKey = $_POST['current_api_key'] ?? '';

            if (empty($userId) || empty($currentApiKey)) {
                throw new Exception(get_lang('NotAllowed'));
            }

            Event::addEvent(LOG_WS.$action, 'user_id', $userId);
            $data = $restApi->updateUserApiKey($userId, $currentApiKey);
            $restResponse->setData($data);
            break;
        case Rest::DELETE_USER:
            if (!api_is_platform_admin()) {
                throw new Exception(get_lang('NotAllowed'));
            }

            $result = UserManager::delete_user($_REQUEST['user_id']);
            Event::addEvent(LOG_WS.$action, 'user_id', (int) $_REQUEST['user_id']);
            $restResponse->setData(['status' => $result]);
            break;
        case Rest::GET_USERS_API_KEYS:
            Event::addEvent(LOG_WS.$action, 'username', $username);
            $restResponse->setData(
                $restApi->getAllUsersApiKeys(
                    $httpRequest->query->getInt('page', 1),
                    $httpRequest->query->getInt('per_page', 30),
                    $httpRequest->query->getBoolean('force', false),
                    $httpRequest->query->getInt('url_id', 0) ?: null
                )
            );
            break;
        case Rest::GET_USER_API_KEY:
            $username = (string) $httpRequest->query->get('user');

            if (empty($username)) {
                throw new Exception(get_lang('NoData'));
            }

            Event::addEvent(LOG_WS.$action, 'username', $username);
            $restResponse->setData(
                $restApi->getUserApiKey(
                    $username,
                    $httpRequest->query->getBoolean('force', false)
                )
            );
            break;
        case Rest::GET_USER_SUB_GROUP:
            $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
            if (empty($userId)) {
                throw new Exception('user_id not provided');
            }

            Event::addEvent(LOG_WS.$action, 'user_id', $userId);
            $data = $restApi->getUserSubGroup($userId);
            $restResponse->setData($data);
            break;
        case Rest::GET_COURSES:
            $campusId = api_get_current_access_url_id();
            if (!empty($_POST['id_campus'])) {
                $campusId = (int) $_POST['id_campus'];
            }
            Event::addEvent(LOG_WS.$action, 'id_campus', $campusId);
            $data = $restApi->getCoursesCampus($campusId);
            $restResponse->setData($data);
            break;
        case Rest::GET_COURSES_FROM_EXTRA_FIELD:
            $variable = $_REQUEST['extra_field_variable'] ?? '';
            $value = $_REQUEST['extra_field_value'] ?? '';
            $urlId = $_REQUEST['id_campus'] ?? '';
            $extraField = new ExtraField('course');
            $extraFieldInfo = $extraField->get_handler_field_info_by_field_variable($variable);

            if (empty($extraFieldInfo)) {
                throw new Exception("$variable not found");
            }

            Event::addEvent(
                LOG_WS.$action,
                'extra_field-extra_field_value',
                Database::escape_string($variable).':'.Database::escape_string($value)
            );
            $extraFieldValue = new ExtraFieldValue('course');
            $items = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
                $variable,
                $value,
                false,
                false,
                true
            );

            $courseList = [];
            foreach ($items as $item) {
                $courseId = $item['item_id'];
                if (UrlManager::relation_url_course_exist($courseId, $urlId)) {
                    $courseList[] = api_get_course_info_by_id($courseId);
                }
            }

            $restResponse->setData($courseList);
            break;
        case Rest::SAVE_COURSE:
            $data = $restApi->addCourse($_POST);
            Event::addEvent(LOG_WS.$action, 'course_id', $data['id']);
            $restResponse->setData($data);
            break;
        case Rest::DELETE_COURSE:
            if (!api_is_platform_admin()) {
                throw new Exception(get_lang('NotAllowed'));
            }

            $courseCode = $_REQUEST['course_code'] ?? '';
            $courseId = $_REQUEST['course_id'] ?? 0;

            $course = [];
            if (!empty($courseCode)) {
                $course = api_get_course_info($courseCode);
            }

            if (empty($course) && !empty($courseId)) {
                $course = api_get_course_info_by_id($courseId);
            }

            if (empty($course)) {
                throw new Exception("Course doesn't exists");
            }

            $result = CourseManager::delete_course($course['code']);
            Event::addEvent(LOG_WS.$action, 'course_id', $courseId);
            $restResponse->setData(['status' => $result]);
            break;
        case Rest::GET_SESSION_FROM_EXTRA_FIELD:
            if (empty($_POST['field_name']) || empty($_POST['field_value'])) {
                throw new Exception(get_lang('NoData'));
            }
            $idSession = $restApi->getSessionFromExtraField($_POST['field_name'], $_POST['field_value']);
            Event::addEvent(
                LOG_WS.$action,
                'extra_field_name-extra_field_value',
                Database::escape_string($_POST['field_name']).':'.Database::escape_string($_POST['field_value'])
            );
            $restResponse->setData([$idSession]);
            break;
        case Rest::SAVE_SESSION:
            $data = $restApi->addSession($_POST);
            Event::addEvent(LOG_WS.$action, 'session_id', $data['id_session']);
            $restResponse->setData($data);
            break;
        case Rest::CREATE_SESSION_FROM_MODEL:
            $newSessionId = $restApi->createSessionFromModel($httpRequest);
            Event::addEvent(LOG_WS.$action, 'session_id', $newSessionId);
            $restResponse->setData([$newSessionId]);
            break;
        case Rest::UPDATE_SESSION:
            $data = $restApi->updateSession($_POST);
            Event::addEvent(LOG_WS.$action, 'session_id', $data['id_session']);
            $restResponse->setData($data);
            break;
        case Rest::SUBSCRIBE_USER_TO_COURSE:
            $data = $restApi->subscribeUserToCourse($_POST);
            Event::addEvent(LOG_WS.$action, 'course_id-user_id', (int) $_POST['course_id'].':'.(int) $_POST['user_id']);
            $restResponse->setData($data);
            break;
        case Rest::SUBSCRIBE_USER_TO_COURSE_PASSWORD:
            $courseCode = isset($_POST['code']) ? Security::remove_XSS($_POST['code']) : null;
            $password = $_POST['password'] ?? null;
            Event::addEvent(LOG_WS.$action, 'course_code', $courseCode);

            $restApi->subscribeUserToCoursePassword($courseCode, $password);
            $restResponse->setData(['status' => true]);
            break;
        case Rest::UNSUBSCRIBE_USER_FROM_COURSE:
            $data = $restApi->unSubscribeUserToCourse($_POST);
            Event::addEvent(LOG_WS.$action, 'course_id-user_id', (int) $_POST['course_id'].':'.(int) $_POST['user_id']);
            $restResponse->setData($data);
            break;
        case Rest::GET_USERS_SUBSCRIBED_TO_COURSE:
            Event::addEvent(LOG_WS.$action, 'course_id', (int) $_POST['course']);
            $users = $restApi->getUsersSubscribedToCourse();
            $restResponse->setData($users);
            break;
        case Rest::GET_SESSIONS:
            $campusId = api_get_current_access_url_id();
            if (!empty($_POST['id_campus'])) {
                $campusId = (int) $_POST['id_campus'];
            }
            Event::addEvent(LOG_WS.$action, 'id_campus', $campusId);
            $data = $restApi->getSessionsCampus($campusId);
            $restResponse->setData($data);
            break;
        case Rest::ADD_COURSES_SESSION:
            $data = $restApi->addCoursesSession($_POST);
            Event::addEvent(
                LOG_WS.$action,
                'session_id-course_ids',
                (int) $_POST['id_session'].':'.implode(',', $_POST['list_courses'])
            );
            $restResponse->setData($data);
            break;
        case Rest::ADD_USERS_SESSION:
        case Rest::SUBSCRIBE_USERS_TO_SESSION:
            $data = $restApi->addUsersSession($_POST);
            Event::addEvent(
                LOG_WS.$action,
                'session_id-users_ids',
                (int) $_POST['id_session'].':'.implode(',', $_POST['list_users'])
            );
            $restResponse->setData($data);
            break;
        case Rest::UNSUBSCRIBE_USERS_FROM_SESSION:
            $data = $restApi->unsubscribeUsersFromSession($_POST);
            Event::addEvent(
                LOG_WS.$action,
                'session_id-users_ids',
                (int) $_POST['id_session'].':'.implode(',', $_POST['list_users'])
            );
            $restResponse->setData($data);
            break;
        case Rest::SUBSCRIBE_USER_TO_SESSION_FROM_USERNAME:
            if (empty($_POST['sessionId']) || empty($_POST['loginname'])) {
                throw new Exception(get_lang('NoData'));
            }
            $subscribed = $restApi->subscribeUserToSessionFromUsername($_POST['sessionId'], $_POST['loginname']);
            Event::addEvent(
                LOG_WS.$action,
                'session_id-username',
                (int) $_POST['sessionId'].':'.Database::escape_string($_POST['loginname'])
            );
            $restResponse->setData([$subscribed]);
            break;
        case Rest::GET_USERS_SUBSCRIBED_TO_SESSION:
            Event::addEvent(LOG_WS.$action, 'session_id', (int) $_POST['id_session']);
            $users = $restApi->getUsersSubscribedToSession($_POST['id_session'], $_POST['move_info']);
            $restResponse->setData($users);
            break;
        case Rest::GET_COURSE_QUIZ_MDL_COMPAT:
            Event::addEvent(LOG_WS.$action, 'course_id', (int) $_POST['course']);
            $data = $restApi->getCourseQuizMdlCompat();

            echo json_encode($data, JSON_PRETTY_PRINT);
            exit;
        case Rest::UPDATE_USER_PAUSE_TRAINING:
            $allow = api_get_plugin_setting('pausetraining', 'tool_enable') === 'true';

            if (false === $allow) {
                throw new Exception(get_lang('Plugin configured'));
            }

            if (empty($_POST['user_id'])) {
                throw new Exception('user_id is required');
            }
            if (null === $restApi) {
                throw new Exception('Check that the username and api_key are field in the request');
            }
            $plugin = PauseTraining::create();
            $data = $plugin->updateUserPauseTraining($_POST['user_id'], $_POST);
            Event::addEvent(LOG_WS.$action, 'user_id', (int) $_POST['user_id']);
            $restResponse->setData([$data]);
            break;
        case Rest::CHECK_CONDITIONAL_LOGIN:
            Event::addEvent(LOG_WS.$action, 'username', $username);
            $restResponse->setData(
                [
                    'check_conditional_login' => $restApi->checkConditionalLogin(),
                ]
            );
            break;
        case Rest::GET_LEGAL_CONDITIONS:
            Event::addEvent(LOG_WS.$action, 'username', $username);
            $restResponse->setData(
                $restApi->getLegalConditions()
            );
            break;
        case Rest::UPDATE_CONDITION_ACCEPTED:
            $restApi->updateConditionAccepted();
            Event::addEvent(LOG_WS.$action, 'success', 'true');
            $restResponse->setData(['status' => true]);
            break;
        case Rest::GET_TEST_UPDATES_LIST:
            Event::addEvent(LOG_WS.$action, 'success', 'true');
            $fields = $_POST['fields'] ?? [];
            $restResponse->setData(
                $restApi->getTestUpdatesList($fields)
            );
            break;
        case Rest::GET_TEST_AVERAGE_RESULTS_LIST:
            if (empty($_POST['ids'])) {
                throw new Exception(get_lang('NoData'));
            }
            Event::addEvent(LOG_WS.$action, 'success', 'true');
            $fields = $_POST['fields'] ?? [];
            $restResponse->setData(
                $restApi->getTestAverageResultsList($_POST['ids'], $fields)
            );
            break;
        /* groups/classes */
        case Rest::GET_GROUPS:
            Event::addEvent(LOG_WS.$action, 'username', $username);
            $data = $restApi->getGroups($_POST);
            $restResponse->setData($data);
            break;
        case Rest::GROUP_EXISTS:
            Event::addEvent(LOG_WS.$action, 'groupname', $_POST['name']);
            $data = $restApi->groupExists($_POST['name']);
            $restResponse->setData([$data]);
            break;
        case Rest::ADD_GROUP:
            $data = $restApi->addGroup($_POST);
            Event::addEvent(LOG_WS.$action, 'user_id', $data);
            $restResponse->setData($data);
            break;
        case Rest::DELETE_GROUP:
            $data = $restApi->deleteGroup($_POST['id']);
            Event::addEvent(LOG_WS.$action, 'group_id', $data);
            $restResponse->setData($data);
            break;
        case Rest::GET_GROUP_SUB_USERS:
            $data = $restApi->getGroupSubscribedUsers($_POST['id']);
            Event::addEvent(LOG_WS.$action, 'group_id', $data);
            $restResponse->setData($data);
            break;
        case Rest::GET_GROUP_SUB_COURSES:
            $data = $restApi->getGroupSubscribedCourses($_POST['id']);
            Event::addEvent(LOG_WS.$action, 'group_id', $data);
            $restResponse->setData($data);
            break;
        case Rest::GET_GROUP_SUB_SESSIONS:
            $data = $restApi->getGroupSubscribedSessions($_POST['id']);
            Event::addEvent(LOG_WS.$action, 'group_id', $data);
            $restResponse->setData($data);
            break;
        case Rest::ADD_GROUP_SUB_USER:
            $groupId = (int) $_POST['group_id'];
            $userId = (int) $_POST['user_id'];
            if (empty($userId)) {
                throw new Exception('user_id not provided');
            }
            if (empty($groupId)) {
                throw new Exception('group_id not provided');
            }
            $role = 2;
            if (isset($_POST['role'])) {
                $role = (int) $_POST['role'];
            }
            $data = $restApi->addGroupSubscribedUser($groupId, $userId, $role);
            Event::addEvent(LOG_WS.$action, 'group_id', $groupId);
            $restResponse->setData($data);
            break;
        case Rest::ADD_GROUP_SUB_COURSE:
            $groupId = (int) $_POST['group_id'];
            $courseId = (int) $_POST['course_id'];
            $data = $restApi->addGroupSubscribedCourse($groupId, $courseId);
            Event::addEvent(LOG_WS.$action, 'group_id', $groupId);
            $restResponse->setData($data);
            break;
        case Rest::ADD_GROUP_SUB_SESSION:
            $groupId = (int) $_POST['group_id'];
            $sessionId = (int) $_POST['session_id'];
            $data = $restApi->addGroupSubscribedSession($groupId, $sessionId);
            Event::addEvent(LOG_WS.$action, 'group_id', $groupId);
            $restResponse->setData($data);
            break;
        case Rest::DELETE_GROUP_SUB_USER:
            $groupId = (int) $_POST['group_id'];
            $userId = (int) $_POST['user_id'];
            $data = $restApi->deleteGroupSubscribedUser($groupId, $userId);
            Event::addEvent(LOG_WS.$action, 'group_id', $groupId);
            $restResponse->setData($data);
            break;
        case Rest::DELETE_GROUP_SUB_COURSE:
            $groupId = (int) $_POST['group_id'];
            $courseId = (int) $_POST['course_id'];
            $data = $restApi->deleteGroupSubscribedCourse($groupId, $courseId);
            Event::addEvent(LOG_WS.$action, 'group_id', $groupId);
            $restResponse->setData($data);
            break;
        case Rest::DELETE_GROUP_SUB_SESSION:
            $groupId = (int) $_POST['group_id'];
            $sessionId = (int) $_POST['session_id'];
            $data = $restApi->deleteGroupSubscribedSession($groupId, $sessionId);
            Event::addEvent(LOG_WS.$action, 'group_id', $groupId);
            $restResponse->setData($data);
            break;
        default:
            throw new Exception(get_lang('InvalidAction'));
    }
} catch (Exception $exception) {
    $restResponse->setErrorMessage(
        $exception->getMessage()
    );
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo $restResponse->format();

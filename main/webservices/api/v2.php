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
require_once __DIR__.'/../../inc/global.inc.php';

$hash = isset($_REQUEST['hash']) ? $_REQUEST['hash'] : null;

if ($hash) {
    $hashParams = Rest::decodeParams($hash);
    if (!empty($hashParams)) {
        foreach ($hashParams as $key => $value) {
            $_REQUEST[$key] = Security::remove_XSS($value);
        }
    }
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
$username = isset($_REQUEST['username']) ? Security::remove_XSS($_REQUEST['username']) : null;
$apiKey = isset($_REQUEST['api_key']) ? Security::remove_XSS($_REQUEST['api_key']) : null;
$course = !empty($_REQUEST['course']) ? (int) $_REQUEST['course'] : null;
$session = !empty($_REQUEST['session']) ? (int) $_REQUEST['session'] : null;

$restResponse = new RestResponse();

try {
    /** @var Rest $restApi */
    $restApi = $apiKey ? Rest::validate($username, $apiKey) : null;

    if ($restApi) {
        $restApi->setCourse($course);
        $restApi->setSession($session);
    }

    switch ($action) {
        case Rest::GET_AUTH:
            Rest::init();

            $password = isset($_POST['password']) ? $_POST['password'] : null;
            $isValid = Rest::isValidUser($username, $password);
            if (!$isValid) {
                throw new Exception(get_lang('InvalideUserDetected'));
            }

            $restResponse->setData([
                'url' => api_get_path(WEB_PATH),
                'apiKey' => Rest::findUserApiKey($username, Rest::SERVICE_NAME),
                'gcmSenderId' => api_get_setting('messaging_gdc_project_number'),
            ]);
            break;

        case Rest::SAVE_GCM_ID:
            $gcmId = isset($_POST['registration_id']) ? Security::remove_XSS($_POST['registration_id']) : null;
            $restApi->setGcmId($gcmId);
            $restResponse->setData(['status' => true]);
            break;

        case Rest::GET_USER_MESSAGES:
            $lastMessageId = isset($_POST['last']) ? (int) $_POST['last'] : 0;
            $messages = $restApi->getUserMessages($lastMessageId);
            $restResponse->setData($messages);
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

            $restResponse->setData($data);
            break;
        case Rest::GET_USER_COURSES:
            $courses = $restApi->getUserCourses();
            $restResponse->setData($courses);
            break;
        case Rest::GET_COURSE_INFO:
            $courseInfo = $restApi->getCourseInfo();
            $restResponse->setData($courseInfo);
            break;
        case Rest::GET_COURSE_DESCRIPTIONS:
            $descriptions = $restApi->getCourseDescriptions();
            $restResponse->setData($descriptions);
            break;
        case Rest::GET_COURSE_DOCUMENTS:
            $directoryId = isset($_POST['dir_id']) ? Security::remove_XSS($_POST['dir_id']) : null;
            $documents = $restApi->getCourseDocuments($directoryId);
            $restResponse->setData($documents);
            break;
        case Rest::GET_COURSE_ANNOUNCEMENTS:
            $announcements = $restApi->getCourseAnnouncements();
            $restResponse->setData($announcements);
            break;
        case Rest::GET_COURSE_ANNOUNCEMENT:
            $announcementId = isset($_POST['announcement']) ? Security::remove_XSS($_POST['announcement']) : 0;
            $announcement = $restApi->getCourseAnnouncement($announcementId);
            $restResponse->setData($announcement);
            break;
        case Rest::GET_COURSE_AGENDA:
            $agenda = $restApi->getCourseAgenda();
            $restResponse->setData($agenda);
            break;
        case Rest::GET_COURSE_NOTEBOOKS:
            $notebooks = $restApi->getCourseNotebooks();
            $restResponse->setData($notebooks);
            break;
        case Rest::GET_COURSE_FORUM_CATEGORIES:
            $forums = $restApi->getCourseForumCategories();
            $restResponse->setData($forums);
            break;
        case Rest::GET_COURSE_FORUM:
            $forumId = isset($_POST['forum']) ? Security::remove_XSS($_POST['forum']) : 0;
            $forum = $restApi->getCourseForum($forumId);
            $restResponse->setData($forum);
            break;
        case Rest::GET_COURSE_FORUM_THREAD:
            $forumId = isset($_POST['forum']) ? (int) $_POST['forum'] : 0;
            $threadId = isset($_POST['thread']) ? (int) $_POST['thread'] : 0;
            $thread = $restApi->getCourseForumThread($forumId, $threadId);
            $restResponse->setData($thread);
            break;
        case Rest::GET_PROFILE:
            $userInfo = $restApi->getUserProfile();
            $restResponse->setData($userInfo);
            break;
        case Rest::GET_COURSE_LEARNPATHS:
            $data = $restApi->getCourseLearnPaths();
            $restResponse->setData($data);
            break;
        case Rest::GET_COURSE_LP_PROGRESS:
            $restResponse->setData($restApi->getCourseLpProgress());
            break;
        case Rest::GET_COURSE_LEARNPATH:
            $lpId = isset($_REQUEST['lp_id']) ? (int) $_REQUEST['lp_id'] : 1;
            $restApi->showLearningPath($lpId);
            break;
        case Rest::SAVE_COURSE:
            $data = $restApi->addCourse($_POST);
            $restResponse->setData($data);
            break;
        case Rest::SAVE_USER:
            $data = $restApi->addUser($_POST);
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
            $restResponse->setData($data);
            break;
        case Rest::SUBSCRIBE_USER_TO_COURSE:
            $data = $restApi->subscribeUserToCourse($_POST);
            $restResponse->setData($data);
            break;
        case Rest::CREATE_CAMPUS:
            $data = $restApi->createCampusURL($_POST);
            $restResponse->setData($data);
            break;
        case Rest::EDIT_CAMPUS:
            $data = $restApi->editCampusURL($_POST);
            $restResponse->setData($data);
            break;
        case Rest::DELETE_CAMPUS:
            $data = $restApi->deleteCampusURL($_POST);
            $restResponse->setData($data);
            break;
        case Rest::SAVE_SESSION:
            $data = $restApi->addSession($_POST);
            $restResponse->setData($data);
            break;
        case Rest::GET_USERS:
            $data = $restApi->getUsersCampus($_POST);
            $restResponse->setData($data);
            break;
        case Rest::GET_COURSES:
            $data = $restApi->getCoursesCampus($_POST);
            $restResponse->setData($data);
            break;
        case Rest::ADD_COURSES_SESSION:
            $data = $restApi->addCoursesSession($_POST);
            $restResponse->setData($data);
            break;
        case Rest::ADD_USERS_SESSION:
            $data = $restApi->addUsersSession($_POST);
            $restResponse->setData($data);
            break;
        case Rest::SAVE_FORUM_POST:
            if (
                empty($_POST['title']) || empty($_POST['text']) || empty($_POST['thread']) || empty($_POST['forum'])
            ) {
                throw new Exception(get_lang('NoData'));
            }

            $forumId = isset($_POST['forum']) ? (int) $_POST['forum'] : 0;
            $notify = !empty($_POST['notify']);
            $parentId = !empty($_POST['parent']) ? (int) $_POST['parent'] : null;

            $postValues = [
                'post_title' => $_POST['title'],
                'post_text' => nl2br($_POST['text']),
                'thread_id' => $_POST['thread'],
                'forum_id' => $_POST['forum'],
                'post_notification' => $notify,
                'post_parent_id' => $parentId,
            ];

            $data = $restApi->saveForumPost($postValues, $forumId);
            $restResponse->setData($data);
            break;
        case Rest::GET_USER_SESSIONS:
            $courses = $restApi->getUserSessions();
            $restResponse->setData($courses);
            break;
        case Rest::SAVE_USER_MESSAGE:
            $receivers = isset($_POST['receivers']) ? $_POST['receivers'] : [];
            $subject = !empty($_POST['subject']) ? $_POST['subject'] : null;
            $text = !empty($_POST['text']) ? $_POST['text'] : null;
            $data = $restApi->saveUserMessage($subject, $text, $receivers);
            $restResponse->setData($data);
            break;
        case Rest::GET_MESSAGE_USERS:
            $search = !empty($_REQUEST['q']) ? $_REQUEST['q'] : null;
            if (!$search || strlen($search) < 2) {
                throw new Exception(get_lang('TooShort'));
            }

            $data = $restApi->getMessageUsers($search);
            $restResponse->setData($data);
            break;
        case Rest::SAVE_COURSE_NOTEBOOK:
            $title = !empty($_POST['title']) ? $_POST['title'] : null;
            $text = !empty($_POST['text']) ? $_POST['text'] : null;
            $data = $restApi->saveCourseNotebook($title, $text);
            $restResponse->setData($data);
            break;
        case Rest::SAVE_FORUM_THREAD:
            if (empty($_POST['title']) || empty($_POST['text']) || empty($_POST['forum'])) {
                throw new Exception(get_lang('NoData'));
            }

            $forumId = isset($_POST['forum']) ? (int) $_POST['forum'] : 0;
            $notify = !empty($_POST['notify']);

            $threadInfo = [
                'post_title' => $_POST['title'],
                'forum_id' => $_POST['forum'],
                'post_text' => nl2br($_POST['text']),
                'post_notification' => $notify,
            ];

            $data = $restApi->saveForumThread($threadInfo, $forumId);
            $restResponse->setData($data);
            break;
        case Rest::GET_USER_MESSAGES_RECEIVED:
            $lastMessageId = isset($_POST['last']) ? (int) $_POST['last'] : 0;
            $messages = $restApi->getUserReceivedMessages($lastMessageId);
            $restResponse->setData($messages);
            break;
        case Rest::GET_USER_MESSAGES_SENT:
            $lastMessageId = isset($_POST['last']) ? (int) $_POST['last'] : 0;
            $messages = $restApi->getUserSentMessages($lastMessageId);
            $restResponse->setData($messages);
            break;
        case Rest::DELETE_USER_MESSAGE:
            $messageId = isset($_POST['message_id']) ? (int) $_POST['message_id'] : 0;
            $messageType = !empty($_POST['msg_type']) ? $_POST['msg_type'] : '';
            $restApi->deleteUserMessage($messageId, $messageType);
            $restResponse->setData(['status' => true]);
            break;
        case Rest::SET_MESSAGE_READ:
            $messageId = isset($_POST['message_id']) ? (int) $_POST['message_id'] : 0;
            $restApi->setMessageRead($messageId);
            $restResponse->setData(['status' => true]);
            break;
        case Rest::CREATE_SESSION_FROM_MODEL:
            $newSessionId = $restApi->createSessionFromModel(
                $_POST['modelSessionId'],
                $_POST['sessionName'],
                $_POST['startDate'],
                $_POST['endDate'],
                isset($_POST['extraFields']) ? $_POST['extraFields'] : []);
            $restResponse->setData([$newSessionId]);
            break;
        case Rest::SUBSCRIBE_USER_TO_SESSION_FROM_USERNAME:
            if (empty($_POST['sessionId']) || empty($_POST['loginname'])) {
                throw new Exception(get_lang('NoData'));
            }
            $subscribed = $restApi->subscribeUserToSessionFromUsername($_POST['sessionId'], $_POST['loginname']);
            $restResponse->setData([$subscribed]);
            break;
        case Rest::GET_SESSION_FROM_EXTRA_FIELD:
            if (empty($_POST['field_name']) || empty($_POST['field_value'])) {
                throw new Exception(get_lang('NoData'));
            }
            $idSession = $restApi->getSessionFromExtraField($_POST['field_name'], $_POST['field_value']);
            $restResponse->setData([$idSession]);
            break;
        case Rest::UPDATE_USER_FROM_USERNAME:
            $data = $restApi->updateUserFromUserName($_POST);
            $restResponse->setData([$data]);
            break;
        case Rest::USERNAME_EXIST:
            $data = $restApi->usernameExist($_POST['loginname']);
            $restResponse->setData([$data]);
            break;
        case Rest::GET_COURSE_QUIZ_MDL_COMPAT:
            $data = $restApi->getCourseQuizMdlCompat();

            echo json_encode($data, JSON_PRETTY_PRINT);
            exit;
            break;
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
            $restResponse->setData([$data]);
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

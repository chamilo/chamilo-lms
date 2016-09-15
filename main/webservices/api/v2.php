<?php
/* For licensing terms, see /license.txt */

require_once '../../inc/global.inc.php';

$hash = isset($_REQUEST['hash']) ? $_REQUEST['hash'] : null;

if ($hash) {
    $hashParams = Rest::decodeParams($hash);

    foreach ($hashParams as $key => $value) {
        $_REQUEST[$key] = $value;
    }
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
$username = isset($_REQUEST['username']) ? Security::remove_XSS($_REQUEST['username']) : null;
$apiKey = isset($_REQUEST['api_key']) ? Security::remove_XSS($_REQUEST['api_key']) : null;
$course = !empty($_REQUEST['course']) ? intval($_REQUEST['course']) : null;
$session = !empty($_REQUEST['session']) ? intval($_REQUEST['session']) : null;

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
                'apiKey' => Rest::findUserApiKey($username, Rest::SERVIVE_NAME),
                'gcmSenderId' => api_get_setting('messaging_gdc_project_number')
            ]);
            break;

        case Rest::SAVE_GCM_ID:
            $gcmId = isset($_POST['registration_id']) ? Security::remove_XSS($_POST['registration_id']) : null;
            $restApi->setGcmId($gcmId);
            $restResponse->setData(['status' => true]);
            break;

        case Rest::GET_USER_MESSAGES:
            $lastMessageId = isset($_POST['last']) ? intval($_POST['last']) : 0;
            $messages = $restApi->getUserMessages($lastMessageId);
            $restResponse->setData($messages);
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
            $forumId = isset($_POST['forum']) ? intval($_POST['forum']) : 0;
            $threadId = isset($_POST['thread']) ? intval($_POST['thread']) : 0;
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
        case Rest::GET_COURSE_LEARNPATH:
            $lpId = isset($_REQUEST['lp_id']) ? intval($_REQUEST['lp_id']) : 0;
            $restApi->showLearningPath($lpId);
            break;
        case Rest::SAVE_FORUM_POST:
            if (
                empty($_POST['title']) || empty($_POST['text']) || empty($_POST['thread']) || empty($_POST['forum'])
            ) {
                throw new Exception(get_lang('NoData'));
            }

            $forumId = isset($_POST['forum']) ? intval($_POST['forum']) : 0;
            $notify = !empty($_POST['notify']);
            $parentId = !empty($_POST['parent']) ? intval($_POST['parent']) : null;

            $postValues = [
                'post_title' => $_POST['title'],
                'post_text' => nl2br($_POST['text']),
                'thread_id' => $_POST['thread'],
                'forum_id' => $_POST['forum'],
                'post_notification' => $notify,
                'post_parent_id' => $parentId
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
            $title = !empty($_POST['title'])? $_POST['title'] : null;
            $text = !empty($_POST['text'])? $_POST['text'] : null;
            $data = $restApi->saveCourseNotebook($title, $text);
            $restResponse->setData($data);
            break;
        case Rest::SAVE_FORUM_THREAD:
            if (
                empty($_POST['title']) || empty($_POST['text']) || empty($_POST['forum'])
            ) {
                throw new Exception(get_lang('NoData'));
            }

            $forumId = isset($_POST['forum']) ? intval($_POST['forum']) : 0;
            $notify = !empty($_POST['notify']);

            $threadInfo = [
                'post_title' => $_POST['title'],
                'forum_id' => $_POST['forum'],
                'post_text' => nl2br($_POST['text']),
                'post_notification' => $notify
            ];

            $data = $restApi->saveForumThread($threadInfo, $forumId);
            $restResponse->setData($data);
            break;
        default:
            throw new Exception(get_lang('InvalidAction'));
    }
} catch (Exception $exeption) {
    $restResponse->setErrorMessage(
        $exeption->getMessage()
    );

}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo $restResponse->format();

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
        case Rest::ACTION_AUTH:
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

        case Rest::ACTION_GCM_ID:
            $gcmId = isset($_POST['registration_id']) ? Security::remove_XSS($_POST['registration_id']) : null;

            $restApi->setGcmId($gcmId);

            $restResponse->setData(['status' => true]);
            break;

        case Rest::ACTION_USER_MESSAGES:
            $lastMessageId = isset($_POST['last']) ? intval($_POST['last']) : 0;

            $messages = $restApi->getUserMessages($lastMessageId);

            $restResponse->setData($messages);
            break;

        case Rest::ACTION_USER_COURSES:
            $courses = $restApi->getUserCourses();

            $restResponse->setData($courses);
            break;

        case Rest::ACTION_COURSE_INFO:
            $courseInfo = $restApi->getCourseInfo();

            $restResponse->setData($courseInfo);
            break;

        case Rest::ACTION_COURSE_DESCRIPTIONS:
            $descriptions = $restApi->getCourseDescriptions();

            $restResponse->setData($descriptions);
            break;

        case Rest::ACTION_COURSE_DOCUMENTS:
            $directoryId = isset($_POST['dir_id']) ? Security::remove_XSS($_POST['dir_id']) : null;

            $documents = $restApi->getCourseDocuments($directoryId);

            $restResponse->setData($documents);
            break;

        case Rest::ACTION_COURSE_ANNOUNCEMENTS:
            $announcements = $restApi->getCourseAnnouncements();

            $restResponse->setData($announcements);
            break;

        case Rest::ACTION_COURSE_ANNOUNCEMENT:
            $announcementId = isset($_POST['announcement']) ? Security::remove_XSS($_POST['announcement']) : 0;

            $announcement = $restApi->getCourseAnnouncement($announcementId);

            $restResponse->setData($announcement);
            break;

        case Rest::ACTION_COURSE_AGENDA:
            $agenda = $restApi->getCourseAgenda();

            $restResponse->setData($agenda);
            break;

        case Rest::ACTION_COURSE_NOTEBOOKS:
            $notebooks = $restApi->getCourseNotebooks();

            $restResponse->setData($notebooks);
            break;

        case Rest::ACTION_COURSE_FORUM_CATEGORIES:
            $forums = $restApi->getCourseForumCategories();

            $restResponse->setData($forums);
            break;

        case Rest::ACTION_COURSE_FORUM:
            $forumId = isset($_POST['forum']) ? Security::remove_XSS($_POST['forum']) : 0;

            $forum = $restApi->getCourseForum($forumId);

            $restResponse->setData($forum);
            break;

        case Rest::ACTION_COURSE_FORUM_THREAD:
            $threadId = isset($_POST['thread']) ? Security::remove_XSS($_POST['thread']) : 0;

            $thread = $restApi->getCourseForumThread($threadId);

            $restResponse->setData($thread);
            break;

        case Rest::ACTION_PROFILE:
            $userInfo = $restApi->getUserProfile();

            $restResponse->setData($userInfo);
            break;

        case Rest::ACTION_COURSE_LEARNPATHS:
            $data = $restApi->getCourseLearnPaths();

            $restResponse->setData($data);
            break;

        case Rest::ACTION_COURSE_LEARNPATH:
            $lpId = isset($_REQUEST['lp_id']) ? intval($_REQUEST['lp_id']) : 0;

            $restApi->showLearningPath($lpId);
            break;

        case Rest::ACTION_SAVE_FORUM_POST:
            if (
                empty($_POST['title']) || empty($_POST['text']) || empty($_POST['thread']) || empty($_POST['forum'])
            ) {
                throw new Exception(get_lang('NoData'));
            }

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

        case Rest::ACTION_USER_SESSIONS:
            $courses = $restApi->getUserSessions();

            $restResponse->setData($courses);
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

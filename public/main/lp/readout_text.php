<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CDocument;

/**
 * Print a read-out text inside a session.
 */
$_in_course = true;

require_once __DIR__.'/../inc/global.inc.php';

$current_course_tool = TOOL_LEARNPATH;

api_protect_course_script(true);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$lpId = isset($_GET['lp_id']) ? (int) $_GET['lp_id'] : 0;
$courseInfo = api_get_course_info();
$courseCode = $courseInfo['code'];
$courseId = $courseInfo['real_id'];
$userId = api_get_user_id();
$sessionId = api_get_session_id();

$em = Database::getManager();
$documentRepo = Container::getDocumentRepository();

// This page can only be shown from inside a learning path
if (!$id && !$lpId) {
    api_not_allowed(true);
}

/** @var CDocument $document */
$document = $documentRepo->find($id);

if (null === $document) {
    Display::return_message(get_lang('The file was not found'), 'error');
    exit;
}

$documentText = $documentRepo->getResourceFileContent($document);
$documentText = api_remove_tags_with_space($documentText);

$wordsInfo = preg_split('/ |\n/', $documentText, -1, PREG_SPLIT_OFFSET_CAPTURE);
$words = [];

foreach ($wordsInfo as $wordInfo) {
    $words[$wordInfo[1]] = nl2br($wordInfo[0]);
}

$htmlHeadXtra[] = '<script>
    var words = '.json_encode($words, JSON_OBJECT_AS_ARRAY).',
        wordsCount = '.count($words).'
</script>';
$htmlHeadXtra[] = api_get_js('readout_text/js/start.js');
$htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_JS_PATH).'readout_text/css/start.css');

$template = new Template(strip_tags($document->getTitle()));
$template->display_blank_template();

<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This file is responsible for passing requested documents to the browser.
 * Many functions updated and moved to lib/document.lib.php.
 */
session_cache_limiter('none');

require_once __DIR__.'/../inc/global-min.inc.php';
$this_section = SECTION_COURSES;

api_protect_course_script();
$_course = api_get_course_info();

if (!isset($_course)) {
    api_not_allowed(true);
}

// Change the '&' that got rewritten to '///' by mod_rewrite back to '&'
$doc_url = str_replace('///', '&', $_GET['doc_url']);
// Still a space present? it must be a '+' (that got replaced by mod_rewrite)
$docUrlNoPlus = $doc_url;
$doc_url = str_replace(' ', '+', $doc_url);
$docUrlParts = preg_split('/\/|\\\/', $doc_url);
$doc_url = '';

foreach ($docUrlParts as $docUrlPart) {
    if (empty($docUrlPart) || in_array($docUrlPart, ['.', '..', '0'])) {
        continue;
    }

    $doc_url .= '/'.$docUrlPart;
}
if (empty($doc_url)) {
    api_not_allowed(!empty($_GET['origin']) && $_GET['origin'] === 'learnpath');
}

// Dealing with image included into survey: when users receive a link towards a
// survey while not being authenticated on the platform.
// The administrator should probably be able to disable this code through admin
// interface.
$refer_script = isset($_SERVER['HTTP_REFERER']) ? strrchr($_SERVER['HTTP_REFERER'], '/') : null;
$sys_course_path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
if (substr($refer_script, 0, 15) === '/fillsurvey.php') {
    $parts = parse_url($refer_script);
    parse_str($parts['query'], $queryParts);
    $course = isset($queryParts['course']) ? $queryParts['course'] : '';
    $invitation = isset($queryParts['invitationcode']) ? $queryParts['invitationcode'] : '';
    include '../survey/survey.download.inc.php';
    if ('auto' === $invitation && $queryParts['scode']) {
        $invitation = Session::read('auto_invitation_code_'.$queryParts['scode']);
    }
    $_course = check_download_survey($course, $invitation, $doc_url);
    $_course['path'] = $_course['directory'];
} else {
    // If the rewrite rule asks for a directory, we redirect to the document explorer
    if (is_dir($sys_course_path.$doc_url)) {
        // Remove last slash if present
        // mod_rewrite can change /some/path/ to /some/path// in some cases, so clean them all off (René)
        while ($doc_url[$dul = strlen($doc_url) - 1] == '/') {
            $doc_url = substr($doc_url, 0, $dul);
        }
        // Group folder?
        $gid_req = ($_GET['gidReq']) ? '&gidReq='.intval($_GET['gidReq']) : '';
        // Create the path
        $document_explorer = api_get_path(WEB_CODE_PATH).'document/document.php?curdirpath='.urlencode($doc_url).'&'.api_get_cidreq_params(Security::remove_XSS($_GET['cidReq'], 0, $gid_req));
        // Redirect
        header('Location: '.$document_explorer);
        exit;
    }
}

//Fixes swf upload problem in chamilo 1.8.x. When uploading a file with
//the character "-" the filename was changed from "-" to "_" in the DB for no reason
$path_info = pathinfo($doc_url);

$fix_file_name = false;
if (isset($path_info['extension']) && $path_info['extension'] === 'swf') {
    $fixed_url = str_replace('-', '_', $doc_url);
    $doc_id = DocumentManager::get_document_id(api_get_course_info(), $doc_url);
    if (!$doc_id) {
        $doc_id = DocumentManager::get_document_id(api_get_course_info(), $doc_url, '0');
        if (!$doc_id) {
            $fix_file_name = true;
        }
    }
}

// When dealing with old systems or wierd migrations, it might so happen that
// the filename contains spaces, that were replaced above by '+' signs, but
// these '+' signs might not match the real filename. Give files with spaces
// another chance if the '+' version doesn't exist.
if (!is_file($sys_course_path.$doc_url) && is_file($sys_course_path.$docUrlNoPlus)) {
    $doc_url = $docUrlNoPlus;
}

if (Security::check_abs_path($sys_course_path.$doc_url, $sys_course_path.'/')) {
    $fullFileName = $sys_course_path.$doc_url;
    if ($fix_file_name) {
        $doc_url = $fixed_url;
    }
    // Check visibility of document and paths
    $is_visible = DocumentManager::is_visible($doc_url, $_course, api_get_session_id());

    //Document's slideshow thumbnails
    //correct $is_visible used in below and ??. Now the students can view the thumbnails too
    if (preg_match('/\.thumbs\/\./', $doc_url)) {
        $doc_url_thumbs = str_replace('.thumbs/.', '', $doc_url);
        $is_visible = DocumentManager::is_visible($doc_url_thumbs, $_course, api_get_session_id());
    }

    if (!api_is_allowed_to_edit() && !$is_visible) {
        echo Display::return_message(get_lang('ProtectedDocument'), 'error'); //api_not_allowed backbutton won't work.
        exit; // You shouldn't be here anyway.
    }
    // Launch event
    Event::event_download($doc_url);
    $download = !empty($_GET['dl']) ? true : false;

    $result = DocumentManager::file_send_for_download($fullFileName, $download);
    if ($result === false) {
        api_not_allowed(true);
    }
}
exit;

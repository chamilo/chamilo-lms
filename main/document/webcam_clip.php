<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This file allows record wav files.
 *
 * @package chamilo.document
 *
 * @author  Juan Carlos Raña Trabado herodoto@telefonica.net
 * @since   7/jun/2012
 * @Updated 04/09/2015 Upgrade to WebCamJS
 */
require_once __DIR__.'/../inc/global.inc.php';

$_SESSION['whereami'] = 'document/webcamclip';
$this_section = SECTION_COURSES;
$nameTools = get_lang('WebCamClip');
$htmlHeadXtra[] = api_get_js_simple(api_get_path(WEB_PATH).'web/assets/webcamjs/webcam.js');
$groupRights = Session::read('group_member_with_upload_rights');

api_protect_course_script();
api_block_anonymous_users();

$userId = api_get_user_id();
$courseCode = api_get_course_id();
$groupId = api_get_group_id();
$sessionId = api_get_session_id();

$document_data = DocumentManager::get_document_data_by_id($_GET['id'], $courseCode, true);

if (empty($document_data)) {
    if (api_is_in_group()) {
        $group_properties = GroupManager::get_group_properties($groupId);
        $document_id = DocumentManager::get_document_id(
            api_get_course_info(),
            $group_properties['directory']
        );
        $document_data = DocumentManager::get_document_data_by_id($document_id, $courseCode);
    }
}

$document_id = $document_data['id'];
$dir = $document_data['path'];

//make some vars
$webcamdir = $dir;
if ($webcamdir == "/") {
    $webcamdir = '';
}

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

// Please, do not modify this dirname formatting
if (strstr($dir, '..')) {
    $dir = '/';
}

if ($dir[0] == '.') {
    $dir = substr($dir, 1);
}

if ($dir[0] != '/') {
    $dir = '/'.$dir;
}

if ($dir[strlen($dir) - 1] != '/') {
    $dir .= '/';
}

$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document'.$dir;

if (!is_dir($filepath)) {
    $filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
    $dir = '/';
}

if (!empty($groupId)) {
    $interbreadcrumb[] = [
        "url" => "../group/group_space.php?".api_get_cidreq(),
        "name" => get_lang('GroupSpace'),
    ];
    $noPHP_SELF = true;
    $group = GroupManager::get_group_properties($groupId);
    $path = explode('/', $dir);
    if ('/'.$path[1] != $group['directory']) {
        api_not_allowed(true);
    }
}

$interbreadcrumb[] = [
    "url" => "./document.php?id=".$document_id."&".api_get_cidreq(),
    "name" => get_lang('Documents'),
];

if (!api_is_allowed_in_course()) {
    api_not_allowed(true);
}

$isMySharedFolder = DocumentManager::is_my_shared_folder($userId, Security::remove_XSS($dir), $sessionId);

if (!($is_allowed_to_edit || $groupRights || $isMySharedFolder)) {
    api_not_allowed(true);
}

/*	Header */
Event::event_access_tool(TOOL_DOCUMENT);

$display_dir = $dir;
if (isset($group)) {
    $display_dir = explode('/', $dir);
    unset($display_dir[0]);
    unset($display_dir[1]);
    $display_dir = implode('/', $display_dir);
}

// Interbreadcrumb for the current directory root path
$counter = 0;
if (isset($document_data['parents'])) {
    foreach ($document_data['parents'] as $document_sub_data) {
        //fixing double group folder in breadcrumb
        if ($groupId) {
            if ($counter == 0) {
                $counter++;
                continue;
            }
        }
        $interbreadcrumb[] = [
            'url' => $document_sub_data['document_url'],
            'name' => $document_sub_data['title'],
        ];
        $counter++;
    }
}

$actions = Display::toolbarAction(
    'webcam_toolbar',
    [
        Display::url(
            Display::return_icon(
                'back.png',
                get_lang('BackTo').' '.get_lang('DocumentsOverview'),
                [],
                ICON_SIZE_MEDIUM
            ),
            'document.php?id='.$document_id.'&'.api_get_cidreq()
        ),
    ]
);

$template = new Template($nameTools);
$template->assign('webcam_dir', $webcamdir);
$template->assign('user_id', $userId);
$template->assign('filename', 'video_clip.jpg');

$layout = $template->get_template('document/webcam.tpl');
$content = $template->fetch($layout);

$template->assign('header', get_lang('TakeYourPhotos'));
$template->assign('actions', $actions);
$template->assign('content', $content);
$template->display_one_col_template();

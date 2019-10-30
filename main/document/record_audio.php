<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This file allows record audio files.
 *
 * @package chamilo.document
 */
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;
$groupRights = Session::read('group_member_with_upload_rights');
$nameTools = get_lang('Voice record');

api_protect_course_script();
api_block_anonymous_users();
api_protect_course_group(GroupManager::GROUP_TOOL_DOCUMENTS);

$document_data = DocumentManager::get_document_data_by_id(
    $_GET['id'],
    api_get_course_id(),
    true
);
if (empty($document_data)) {
    if (api_is_in_group()) {
        $group_properties = GroupManager::get_group_properties(
            api_get_group_id()
        );
        $document_id = DocumentManager::get_document_id(
            api_get_course_info(),
            $group_properties['directory']
        );
        $document_data = DocumentManager::get_document_data_by_id(
            $document_id,
            api_get_course_id()
        );
    }
}

$document_id = $document_data['id'];
$dir = $document_data['path'];

//make some vars
$wamidir = $dir;
if ($wamidir == "/") {
    $wamidir = '';
}
$wamiurlplay = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document'.$wamidir."/";
$groupId = api_get_group_id();
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

/*$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document'.$dir;
if (!is_dir($filepath)) {
    $filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
    $dir = '/';
}*/

//groups //TODO: clean
if (!empty($groupId)) {
    $interbreadcrumb[] = ["url" => "../group/group_space.php?".api_get_cidreq(), "name" => get_lang('Group area')];
    $group = GroupManager :: get_group_properties($groupId);
    $path = explode('/', $dir);
    if ('/'.$path[1] != $group['directory']) {
        api_not_allowed(true);
    }
}

$interbreadcrumb[] = ["url" => "./document.php?id=".$document_id.'&'.api_get_cidreq(), "name" => get_lang('Documents')];

if (!api_is_allowed_in_course()) {
    api_not_allowed(true);
}

if (!($is_allowed_to_edit || $groupRights ||
    DocumentManager::is_my_shared_folder(
        api_get_user_id(),
        Security::remove_XSS($dir),
        api_get_session_id()
    ))
) {
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
        if (api_get_group_id()) {
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

//make some vars
$wamiuserid = api_get_user_id();

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'rtc/RecordRTC.js"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'wami-recorder/recorder.js"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'wami-recorder/gui.js"></script>';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'swfobject/swfobject.js"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'swfobject/swfobject.js"></script>';
$htmlHeadXtra[] = api_get_js('record_audio/record_audio.js');

$actions = Display::toolbarButton(
    get_lang('Back to').' '.get_lang('Documents overview'),
    'document.php?'.api_get_cidreq()."&id=$document_id",
    'arrow-left',
    'default',
    [],
    false
);

$template = new Template($nameTools);
$template->assign('directory', $wamidir);
$template->assign('user_id', api_get_user_id());
$template->assign('reload_page', 1);

$layout = $template->get_template('document/record_audio.tpl');
$content = $template->fetch($layout);

$template->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actions])
);
$template->assign('content', $content);
$template->display_one_col_template();

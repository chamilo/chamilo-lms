<?php

/* For licensing terms, see /license.txt */

exit;

use ChamiloSession as Session;

/**
 * This file allows creating audio files from a text.
 *
 * @package chamilo.document
 *
 * @author Juan Carlos Raña Trabado
 *
 * @since 30/January/2011
 *
 * @todo clean all file
 */
require_once __DIR__.'/../inc/global.inc.php';

if (api_get_setting('enabled_support_paint') === 'false') {
    api_not_allowed(true);
}

$this_section = SECTION_COURSES;
$nameTools = get_lang('PhotoRetouching');
$groupRights = Session::read('group_member_with_upload_rights');

api_protect_course_script();
api_block_anonymous_users();
$_course = api_get_course_info();
$document_data = DocumentManager::get_document_data_by_id($_GET['id'], api_get_course_id(), true);
if (empty($document_data)) {
    if (api_is_in_group()) {
        $group_properties = GroupManager::get_group_properties(api_get_group_id());
        $document_id = DocumentManager::get_document_id(api_get_course_info(), $group_properties['directory']);
        $document_data = DocumentManager::get_document_data_by_id($document_id, api_get_course_id());
    }
}

$document_id = $document_data['id'];
$dir = $document_data['path'];
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

// path for pixlr save
$paintDir = Security::remove_XSS($dir);
if (empty($paintDir)) {
    $paintDir = '/';
}

Session::write('paint_dir', $paintDir);
Session::write('paint_file', get_lang('NewImage'));

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

$groupId = api_get_group_id();

if (!empty($groupId)) {
    $interbreadcrumb[] = [
        "url" => api_get_path(WEB_CODE_PATH)."group/group_space.php?".api_get_cidreq(),
        "name" => get_lang('GroupSpace'),
    ];
    $group = GroupManager::get_group_properties($groupId);
    $path = explode('/', $dir);
    if ('/'.$path[1] != $group['directory']) {
        api_not_allowed(true);
    }
}

$interbreadcrumb[] = [
    "url" => "./document.php?curdirpath=".urlencode($dir)."&".api_get_cidreq(),
    "name" => get_lang('Documents'),
];

if (!api_is_allowed_in_course()) {
    api_not_allowed(true);
}

if (!($is_allowed_to_edit || $groupRights ||
    DocumentManager::is_my_shared_folder($_user['user_id'], Security::remove_XSS($dir), api_get_session_id()))
) {
    api_not_allowed(true);
}

/* Header */
Event::event_access_tool(TOOL_DOCUMENT);
$display_dir = $dir;
if (isset($group)) {
    $display_dir = explode('/', $dir);
    unset($display_dir[0]);
    unset($display_dir[1]);
    $display_dir = implode('/', $display_dir);
}

// Interbreadcrumb for the current directory root path
if (empty($document_data['parents'])) {
    $interbreadcrumb[] = ['url' => '#', 'name' => $document_data['title']];
} else {
    foreach ($document_data['parents'] as $document_sub_data) {
        $interbreadcrumb[] = [
            'url' => $document_sub_data['document_url'],
            'name' => $document_sub_data['title'],
        ];
    }
}

Display::display_header($nameTools, 'Doc');
echo '<div class="actions">';
echo '<a href="document.php?id='.$document_id.'">'.
    Display::return_icon(
        'back.png',
        get_lang('BackTo').' '.get_lang('DocumentsOverview'),
        '',
        ICON_SIZE_MEDIUM
    ).
    '</a>';
echo '</div>';

// pixlr
// max size 1 Mb ??
$title = urlencode(utf8_encode(get_lang('NewImage'))); //TODO:check
$image = Display::returnIconPath('canvas1024x768.png');
$exit_path = api_get_path(WEB_CODE_PATH).'document/exit_pixlr.php';
Session::write('exit_pixlr', $document_data['path']);
$target_path = api_get_path(WEB_CODE_PATH).'document/save_pixlr.php';
$target = $target_path;
$locktarget = 'true';
$locktitle = 'false';
$referrer = 'Chamilo';

if ($_SERVER['HTTP_HOST'] == "localhost") {
    $path_and_file = api_get_path(SYS_PATH).'/crossdomain.xml';
    if (!file_exists($path_and_file)) {
        $crossdomain = '<?xml version="1.0"?>
			<!DOCTYPE cross-domain-policy SYSTEM "http://www.adobe.com/xml/dtds/cross-domain-policy.dtd">
			<cross-domain-policy>
				<allow-access-from domain="cdn.pixlr.com" />
				<site-control permitted-cross-domain-policies="master-only"/>
				<allow-http-request-headers-from domain="cnd.pixlr.com" headers="*" secure="true"/>
			</cross-domain-policy>'; //more open domain="*"
        @file_put_contents($path_and_file, $crossdomain);
    }
    $credentials = 'true';
} else {
    $credentials = 'false';
}
$pixlr_url = '//pixlr.com/editor/?title='.$title.'&image='.$image.'&referrer='.$referrer.'&target='.$target.'&exit='.$exit_path.'&locktarget='.$locktarget.'&locktitle='.$locktitle.'&credentials='.$credentials;
?>
<script>
document.write('<iframe id="frame" frameborder="0" scrolling="no" src="<?php echo $pixlr_url; ?>" width="100%" height="100%"><noframes><p>Sorry, your browser does not handle frames</p></noframes></iframe></div>');
function resizeIframe() {
    var height = window.innerHeight;
    //max lower size
    if (height<600) {
        height=600;
    }
    document.getElementById('frame').style.height = height +"px";
};
document.getElementById('frame').onload = resizeIframe;
window.onresize = resizeIframe;
</script>
<?php
echo '<noscript>';
echo '<iframe style="height: 600px; width: 100%;" scrolling="no" frameborder="0" src="'.$pixlr_url.'"><noframes><p>Sorry, your browser does not handle frames</p></noframes></iframe>';
echo '</noscript>';

Display::display_footer();

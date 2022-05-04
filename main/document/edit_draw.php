<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This file allows creating new svg and png documents with an online editor.
 *
 * @package chamilo.document
 *
 * @author Juan Carlos Ra�a Trabado
 *
 * @since 25/september/2010
 */
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;
$groupRights = Session::read('group_member_with_upload_rights');

api_protect_course_script(true);
api_block_anonymous_users();

$document_data = DocumentManager::get_document_data_by_id(
    $_GET['id'],
    api_get_course_id(),
    true
);

$file_path = '';
if (empty($document_data)) {
    api_not_allowed();
} else {
    $document_id = $document_data['id'];
    $file_path = $document_data['path'];
    $dir = dirname($document_data['path']);
    $parent_id = DocumentManager::get_document_id(api_get_course_info(), $dir);
    $my_cur_dir_path = isset($_GET['curdirpath']) ? Security::remove_XSS($_GET['curdirpath']) : '';
}
//and urlencode each url $curdirpath (hack clean $curdirpath under Windows - Bug #3261)
$dir = str_replace('\\', '/', $dir);

/* Constants & Variables */
$current_session_id = api_get_session_id();
$group_id = api_get_group_id();

//path for svg-edit save
Session::write('draw_dir', Security::remove_XSS($dir));
if ($dir == '/') {
    Session::write('draw_dir', '');
}
Session::write('draw_file', basename(Security::remove_XSS($file_path)));
$get_file = Security::remove_XSS($file_path);
$file = basename($get_file);
$temp_file = explode(".", $file);
$filename = $temp_file[0];
$nameTools = get_lang('EditDocument').': '.$filename;
$courseDir = $_course['path'].'/document';
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

/*	Other initialization code */

/* Please, do not modify this dirname formatting */

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

//groups //TODO:clean
if (!empty($group_id)) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
        'name' => get_lang('GroupSpace'),
    ];
    $group_document = true;
}

$is_certificate_mode = DocumentManager::is_certificate_mode($dir);

if (!$is_certificate_mode) {
    $interbreadcrumb[] = [
        "url" => "./document.php?curdirpath=".urlencode($my_cur_dir_path).'&'.api_get_cidreq(),
        "name" => get_lang('Documents'),
    ];
} else {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Gradebook'),
    ];
}

// Interbreadcrumb for the current directory root path
if (empty($document_data['parents'])) {
    $interbreadcrumb[] = ['url' => '#', 'name' => $document_data['title']];
} else {
    foreach ($document_data['parents'] as $document_sub_data) {
        if ($document_data['title'] == $document_sub_data['title']) {
            continue;
        }
        $interbreadcrumb[] = [
            'url' => $document_sub_data['document_url'],
            'name' => $document_sub_data['title'],
        ];
    }
}

$is_allowedToEdit = api_is_allowed_to_edit(null, true) || $groupRights || DocumentManager::is_my_shared_folder(api_get_user_id(), $dir, $current_session_id);

if (!$is_allowedToEdit) {
    api_not_allowed(true);
}

Event::event_access_tool(TOOL_DOCUMENT);

Display::display_header($nameTools, 'Doc');
echo '<div class="actions">';
echo '<a href="document.php?id='.$parent_id.'">'.
    Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('DocumentsOverview'), '', ICON_SIZE_MEDIUM).'</a>';
echo '<a href="edit_document.php?'.api_get_cidreq().'&id='.$document_id.'&origin=editdraw">'.
    Display::return_icon('edit.png', get_lang('Rename').'/'.get_lang('Comments'), '', ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

if (api_browser_support('svg')) {
    //automatic loading the course language
    $svgedit_code_translation_table = [
        '' => 'en',
        'pt' => 'pt-Pt',
        'sr' => 'sr_latn',
    ];
    $langsvgedit = api_get_language_isocode();
    $langsvgedit = isset($svgedit_code_translation_table[$langsvgedit]) ? $svgedit_code_translation_table[$langsvgedit] : $langsvgedit;
    $langsvgedit = file_exists(api_get_path(LIBRARY_PATH).'javascript/svgedit/locale/lang.'.$langsvgedit.'.js') ? $langsvgedit : 'en';
    $svg_url = api_get_path(WEB_LIBRARY_PATH).'javascript/svgedit/svg-editor.php?url=../../../../../courses/'.$courseDir.$dir.$file.'&lang='.$langsvgedit; ?>
    <script>
    document.write ('<iframe id="frame" frameborder="0" scrolling="no" src="<?php echo $svg_url; ?>" width="100%" height="100%"><noframes><p>Sorry, your browser does not handle frames</p></noframes></iframe>');
    function resizeIframe() {
        var height = window.innerHeight -50;
        //max lower size
        if (height<550) {
            height=550;
        }
        document.getElementById('frame').style.height = height +"px";
    };
    document.getElementById('frame').onload = resizeIframe;
    window.onresize = resizeIframe;
    </script>
    <?php
    echo '<noscript>';
    echo '<iframe style="height: 550px; width: 100%;" scrolling="no" frameborder="0\' src="'.$svg_url.'"<noframes><p>Sorry, your browser does not handle frames</p></noframes></iframe>';
    echo '</noscript>';
} else {
    echo Display::return_message(get_lang('BrowserDontSupportsSVG'), 'error');
}
Display::display_footer();

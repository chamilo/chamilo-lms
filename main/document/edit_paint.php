<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This file allows creating new svg and png documents with an online editor.
 *
 * @package chamilo.document
 *
 * @todo used the document_id instead of the curdirpath
 *
 * @author Juan Carlos Raña Trabado
 *
 * @since 30/january/2011
 */
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;
$groupRights = Session::read('group_member_with_upload_rights');

api_protect_course_script(true);
api_block_anonymous_users();
$_course = api_get_course_info();
$groupId = api_get_group_id();
$document_data = DocumentManager::get_document_data_by_id(
    $_GET['id'],
    api_get_course_id(),
    true
);

if (empty($document_data)) {
    api_not_allowed();
} else {
    $document_id = $document_data['id'];
    $file_path = $document_data['path'];
    $dir = dirname($document_data['path']);
    $parent_id = DocumentManager::get_document_id(api_get_course_info(), $dir);
    $my_cur_dir_path = isset($_GET['curdirpath']) ? Security::remove_XSS($_GET['curdirpath']) : null;
}

//and urlencode each url $curdirpath (hack clean $curdirpath under Windows - Bug #3261)
$dir = str_replace('\\', '/', $dir);
if (empty($dir)) {
    $dir = '/';
}

/* Constants & Variables */
$current_session_id = api_get_session_id();
//path for pixlr save
Session::write('paint_dir', Security::remove_XSS($dir));
Session::write('paint_file', basename(Security::remove_XSS($file_path)));
$get_file = Security::remove_XSS($file_path);
$file = basename($get_file);
$temp_file = explode(".", $file);
$filename = $temp_file[0];
$nameTools = get_lang('EditDocument').': '.$filename;
$courseDir = $_course['path'].'/document';
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
/* Other initialization code */
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
if (!empty($groupId)) {
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
        $interbreadcrumb[] = ['url' => $document_sub_data['document_url'], 'name' => $document_sub_data['title']];
    }
}

$is_allowedToEdit = api_is_allowed_to_edit(null, true) || $groupRights ||
    DocumentManager::is_my_shared_folder(api_get_user_id(), $dir, $current_session_id);

if (!$is_allowedToEdit) {
    api_not_allowed(true);
}

Event::event_access_tool(TOOL_DOCUMENT);

Display::display_header($nameTools, 'Doc');
echo '<div class="actions">';
echo '<a href="document.php?id='.$parent_id.'&'.api_get_cidreq().'">'.
    Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('DocumentsOverview'), '', ICON_SIZE_MEDIUM).'</a>';
echo '<a href="edit_document.php?'.api_get_cidreq().'&id='.$document_id.'&'.api_get_cidreq().'&origin=editpaint">'.
    Display::return_icon('edit.png', get_lang('Rename').'/'.get_lang('Comment'), '', ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

// pixlr
$title = $file; //disk name. No sql name because pixlr return this when save

$langpixlr = api_get_language_isocode();
$langpixlr = isset($pixlr_code_translation_table[$langpixlr]) ? $pixlredit_code_translation_table[$langpixlr] : $langpixlr;
$loc = $langpixlr; // deprecated ?? TODO:check pixlr read user browser

$exit_path = api_get_path(WEB_CODE_PATH).'document/exit_pixlr.php';
Session::write('exit_pixlr', Security::remove_XSS($parent_id));
$referrer = "Chamilo";
$target_path = api_get_path(WEB_CODE_PATH).'document/save_pixlr.php';
$target = $target_path;
$locktarget = "true";
$locktitle = "false";

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
    $credentials = "true";
} else {
    $credentials = "false";
}

//make temp images
$temp_folder = api_get_path(SYS_ARCHIVE_PATH).'temp/images';
if (!file_exists($temp_folder)) {
    @mkdir($temp_folder, api_get_permissions_for_new_directories(), true); //TODO:check $permissions value, now empty;
}

//make htaccess with allow from all, and file index.html into temp/images
$htaccess = api_get_path(SYS_ARCHIVE_PATH).'temp/images/.htaccess';
if (!file_exists($htaccess)) {
    $htaccess_content = "order deny,allow\r\nallow from all\r\nOptions -Indexes";
    $fp = @fopen(api_get_path(SYS_ARCHIVE_PATH).'temp/images/.htaccess', 'w');
    if ($fp) {
        fwrite($fp, $htaccess_content);
        fclose($fp);
    }
}

$html_index = api_get_path(SYS_ARCHIVE_PATH).'temp/images/index.html';
if (!file_exists($html_index)) {
    $html_index_content = "<html><head></head><body></body></html>";
    $fp = @fopen(api_get_path(SYS_ARCHIVE_PATH).'temp/images/index.html', 'w');
    if ($fp) {
        fwrite($fp, $html_index_content);
        fclose($fp);
    }
}

//encript temp name file
$name_crip = sha1(uniqid()); //encript
$findext = explode(".", $file);
$extension = $findext[count($findext) - 1];
$file_crip = $name_crip.'.'.$extension;

//copy file to temp/images directory
$from = $filepath.$file;
$to = api_get_path(SYS_ARCHIVE_PATH).'temp/images/'.$file_crip;
copy($from, $to);
Session::write('temp_realpath_image', $to);

//load image to url
$to_url = api_get_path(WEB_ARCHIVE_PATH).'temp/images/'.$file_crip;
$image = urlencode($to_url);
$pixlr_url = '//pixlr.com/editor/?title='.$title.'&image='.$image.'&loc='.$loc.'&referrer='.$referrer.'&target='.$target.'&exit='.$exit_path.'&locktarget='.$locktarget.'&locktitle='.$locktitle.'&credentials='.$credentials;

//make frame an send image
?>
<script>
document.write ('<iframe id="frame" frameborder="0" scrolling="no" src="<?php echo $pixlr_url; ?>" width="100%" height="100%"><noframes><p>Sorry, your browser does not handle frames</p></noframes></iframe>');
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

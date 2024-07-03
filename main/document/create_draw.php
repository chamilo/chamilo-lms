<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This file allows creating new svg and png documents with an online editor.
 *
 * @author Juan Carlos Raña Trabado
 *
 * @since 25/september/2010
 */
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;
$groupRights = Session::read('group_member_with_upload_rights');
$nameTools = get_lang('Draw');

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

// path for svg-edit save
Session::write('draw_dir', Security::remove_XSS($dir));
if ($dir == '/') {
    Session::write('draw_dir', '');
}

$dir = isset($dir) ? Security::remove_XSS($dir) : (isset($_POST['dir']) ? Security::remove_XSS($_POST['dir']) : '/');
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

$groupId = api_get_group_id();
if (!empty($groupId)) {
    $interbreadcrumb[] = [
        "url" => "../group/group_space.php?".api_get_cidreq(),
        "name" => get_lang('GroupSpace'),
    ];
    $group = GroupManager::get_group_properties($groupId);
    $path = explode('/', $dir);
    if ('/'.$path[1] != $group['directory']) {
        api_not_allowed(true);
    }
}

$interbreadcrumb[] = [
    "url" => "./document.php?".api_get_cidreq(),
    "name" => get_lang('Documents'),
];

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

Event::event_access_tool(TOOL_DOCUMENT);
$display_dir = $dir;
if (isset($group)) {
    $display_dir = explode('/', $dir);
    unset($display_dir[0]);
    unset($display_dir[1]);
    $display_dir = implode('/', $display_dir);
}

// Interbreadcrumb for the current directory root path
// Copied from document.php
$dir_array = explode('/', $dir);
$array_len = count($dir_array);

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
    Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('DocumentsOverview'), '', ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

if (api_browser_support('svg')) {
    // Automatic loading the course language
    $translationList = ['' => 'en', 'pt' => 'pt-Pt', 'sr' => 'sr_latn'];
    $langsvgedit = api_get_language_isocode();
    $langsvgedit = isset($translationList[$langsvgedit]) ? $translationList[$langsvgedit] : $langsvgedit;
    $langsvgedit = file_exists(api_get_path(LIBRARY_PATH).'javascript/svgedit/locale/lang.'.$langsvgedit.'.js') ? $langsvgedit : 'en';
    $svg_url = api_get_path(WEB_LIBRARY_PATH).'javascript/svgedit/svg-editor.php?'.api_get_cidreq().'&lang='.$langsvgedit; ?>
    <script>
        document.write('<iframe id="frame" frameborder="0" scrolling="no" src="<?php echo $svg_url; ?>" width="100%" height="100%"><noframes><p>Sorry, your browser does not handle frames</p></noframes></iframe>');
        function resizeIframe() {
            var height = window.innerHeight -50;
            // max lower size
            if (height<550) {
                height=550;
            }
            document.getElementById('frame').style.height = height +"px";
        }
    document.getElementById('frame').onload = resizeIframe;
    window.onresize = resizeIframe;
    </script>
    <?php
    echo '<noscript>';
    echo '<iframe style="height: 550px; width: 100%;" scrolling="no" frameborder="0" src="'.$svg_url.'"><noframes><p>Sorry, your browser does not handle frames</p></noframes></iframe>';
    echo '</noscript>';
} else {
    echo Display::return_message(get_lang('BrowserDontSupportsSVG'), 'error');
}

Display::display_footer();

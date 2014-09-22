<?php
/* For licensing terms, see /license.txt */
/**
 *	This file allows creating new html documents with an online WYSIWYG html editor.
 *
 *	@package chamilo.document
 */
/**
 * Code
 */
/*	INIT SECTION */

// Name of the language file that needs to be included
$language_file = array('document', 'gradebook');

////require_once '../inc/global.inc.php';

api_protect_course_script();

$_SESSION['whereami'] = 'document/create';
$this_section = SECTION_COURSES;

$htmlHeadXtra[] = '<script>

var hide_bar = function() {
    $("#template_col").hide();
    $("#doc_form").removeClass("span9");
    $("#doc_form").addClass("span11");
}

$(document).ready(function() {
    if ($(window).width() <= 785 ) {
        hide_bar();
    }
    $("#hide_bar_template").toggle(
        function() {
          hide_bar();
        },
        function() {
            $("#template_col").show();
            $("#doc_form").removeClass("span11");
            $("#doc_form").addClass("span9");
        }
    );
});

function setFocus() {
   $("#document_title").focus();
}

$(window).load(function () {
    setFocus();
});
</script>';

require_once api_get_path(SYS_CODE_PATH).'document/document.inc.php';

//I'm in the certification module?
$is_certificate_mode = false;
$_course = api_get_course_info();
$groupId = api_get_group_id();

if (isset($_REQUEST['certificate']) && $_REQUEST['certificate'] == 'true') {
    $is_certificate_mode = true;
}

if ($is_certificate_mode) {
    $nameTools = get_lang('CreateCertificate');
} else {
    $nameTools = get_lang('CreateDocument');
}

/*	Constants and variables */

$doc_table = Database::get_course_table(TABLE_DOCUMENT);
$course_id = api_get_course_int_id();

$document_data = DocumentManager::get_document_data_by_id($_REQUEST['id'], api_get_course_id(), true);
if (empty($document_data)) {
    if (api_is_in_group()) {
        $group_properties = GroupManager::get_group_properties($groupId);
        $document_id = DocumentManager::get_document_id(api_get_course_info(), $group_properties['directory']);
        $document_data = DocumentManager::get_document_data_by_id($document_id, api_get_course_id());

        $dir = $document_data['path'];
        $folder_id = $document_data['id'];
    } else {
        $dir = '/';
        $folder_id = 0;
    }
} else {
    $folder_id = $document_data['id'];
    $dir = $document_data['path'];
}

/*	MAIN CODE */

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

if ($is_certificate_mode) {
    $document_id = DocumentManager::get_document_id(api_get_course_info(), '/certificates');
    $document_data = DocumentManager::get_document_data_by_id($document_id, api_get_course_id(), true);
    $folder_id = $document_data['id'];
    $dir = '/certificates/';
}

// Configuration for the FCKEDITOR
$doc_tree = explode('/', $dir);
$count_dir = count($doc_tree) - 2; // "2" because at the begin and end there are 2 "/"

if (api_is_in_group()) {
    $group_properties = GroupManager::get_group_properties($groupId);

    // Level correction for group documents.
    if (!empty($group_properties['directory'])) {
        $count_dir = $count_dir > 0 ? $count_dir - 1 : 0;
    }
}
$relative_url = '';
for ($i = 0; $i < ($count_dir); $i++) {
    $relative_url .= '../';
}

// We do this in order to avoid the condition in html_editor.php ==> if ($this -> fck_editor->Config['CreateDocumentWebDir']=='' || $this -> fck_editor->Config['CreateDocumentDir']== '')
if ($relative_url == '') {
    $relative_url = '/';
}

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

$html_editor_config = array(
    'ToolbarSet' => ($is_allowed_to_edit ? 'Documents' : 'DocumentsStudent'),
    'Width' => '100%',
    'Height' => '500',
    'FullPage' => true,
    'InDocument' => true,
    'CreateDocumentDir' => $relative_url,
    'CreateDocumentWebDir' => (empty($group_properties['directory']))
        ? api_get_path(WEB_COURSE_PATH).$_course['path'].'/document/'
        : api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document'.$group_properties['directory'].'/',
    'BaseHref' => api_get_path(WEB_COURSE_PATH).$_course['path'].'/document'.$dir
);

if ($is_certificate_mode) {
    $html_editor_config['CreateDocumentDir'] = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document/';
    $html_editor_config['CreateDocumentWebDir'] = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document/';
    $html_editor_config['BaseHref'] = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document'.$dir;
}

$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document'.$dir;

if (!is_dir($filepath)) {
    $filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
    $dir = '/';
}

if (!$is_certificate_mode) {
    $req_gid = null;
    if (api_is_in_group()) {
        $req_gid = '&amp;gidReq='.$groupId;
        $interbreadcrumb[] = array(
            "url" => "../group/group_space.php?gidReq=".$groupId,
            "name" => get_lang('GroupSpace')
        );
        $noPHP_SELF = true;
        $path = explode('/', $dir);
        if ('/'.$path[1] != $group_properties['directory']) {
            api_not_allowed(true);
        }
    }
    $interbreadcrumb[] = array(
        "url" => "./document.php?curdirpath=".urlencode($dir).$req_gid,
        "name" => get_lang('Documents')
    );
} else {
    $interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'gradebook/'.$_SESSION['gradebook_dest'], 'name' => get_lang('Gradebook'));
}
if (!($is_allowed_to_edit || GroupManager::groupMemberWithUploadRights() || is_my_shared_folder(
    $_user['user_id'],
    Security::remove_XSS($dir),
    api_get_session_id()
))
) {
    api_not_allowed(true);
}

Event::event_access_tool(TOOL_DOCUMENT);

$display_dir = $dir;
if (isset ($group_properties)) {
    $display_dir = explode('/', $dir);
    unset ($display_dir[0]);
    unset ($display_dir[1]);
    $display_dir = implode('/', $display_dir);
}

$select_cat = isset($_GET['selectcat']) ? intval($_GET['selectcat']) : null;

// Create a new form
$form = new FormValidator('create_document', 'post', api_get_self().'?'.api_get_cidreq().'&dir='.Security::remove_XSS(
    urlencode($dir)
).'&selectcat='.$select_cat, null, array('class' => 'form-horizontal'));

// form title
$form->addElement('header', $nameTools);

if ($is_certificate_mode) { //added condition for certicate in gradebook
    $form->addElement('hidden', 'certificate', 'true', array('id' => 'certificate'));
    if (isset($_GET['selectcat'])) {
        $form->addElement('hidden', 'selectcat', $select_cat);
    }

}
// Hidden element with current directory
$form->addElement('hidden', 'id');
$defaults = array();
$defaults['id'] = $folder_id;

// Filename
$form->addElement('hidden', 'title_edited', 'false', 'id="title_edited"');

/**
 * Check if a document width the choosen filename allready exists
 */
function document_exists($filename)
{
    global $filepath;
    $filename = addslashes(trim($filename));
    $filename = Security::remove_XSS($filename);
    $filename = api_replace_dangerous_char($filename);
    $filename = FileManager::disable_dangerous_file($filename);

    return !file_exists($filepath.$filename.'.html');
}

// Add group to the form
if ($is_certificate_mode) {
    $form->addElement('text', 'title', get_lang('CertificateName'), array('id' => 'document_title'));
} else {
    $form->addElement('text', 'title', get_lang('Title'), array('id' => 'document_title'));
}

// Show read-only box only in groups
if (!empty($groupId)) {
    $group[] = $form->createElement('checkbox', 'readonly', '', get_lang('ReadOnly'));
}
$form->addRule('title', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('title', get_lang('FileExists'), 'callback', 'document_exists');

$current_session_id = api_get_session_id();
$form->add_html_editor('content', '', false, false, $html_editor_config);

// Comment-field
$folders = DocumentManager::get_all_document_folders($_course, $groupId, $is_allowed_to_edit);

// If we are not in the certificates creation, display a folder chooser for the
// new document created

if (!$is_certificate_mode && !is_my_shared_folder($_user['user_id'], $dir, $current_session_id)) {
    $folders = DocumentManager::get_all_document_folders($_course, $groupId, $is_allowed_to_edit);

    $parent_select = $form->addElement('select', 'curdirpath', array(null, get_lang('DestinationDirectory')));

    // Following two conditions copied from document.inc.php::build_directory_selector()
    $folder_titles = array();

    if (is_array($folders)) {
        $escaped_folders = array();
        foreach ($folders as $key => & $val) {
            //Hide some folders
            if ($val == '/HotPotatoes_files' || $val == '/certificates' || basename($val) == 'css') {
                continue;
            }
            //Admin setting for Hide/Show the folders of all users
            if (api_get_setting('show_users_folders') == 'false' && (strstr($val, '/shared_folder') || strstr(
                $val,
                'shared_folder_session_'
            ))
            ) {
                continue;
            }
            //Admin setting for Hide/Show Default folders to all users
            if (api_get_setting(
                'show_default_folders'
            ) == 'false' && ($val == '/images' || $val == '/flash' || $val == '/audio' || $val == '/video' || strstr(
                $val,
                '/images/gallery'
            ) || $val == '/video/flv')
            ) {
                continue;
            }
            //Admin setting for Hide/Show chat history folder
            if (api_get_setting('show_chat_folder') == 'false' && $val == '/chat_files') {
                continue;
            }

            $escaped_folders[$key] = Database::escape_string($val);
        }
        $folder_sql = implode("','", $escaped_folders);

        $sql = "SELECT * FROM $doc_table WHERE c_id = $course_id AND filetype='folder' AND path IN ('".$folder_sql."')";
        $res = Database::query($sql);
        $folder_titles = array();
        while ($obj = Database::fetch_object($res)) {
            $folder_titles[$obj->path] = $obj->title;
        }
    }

    if (empty($group_dir)) {
        $parent_select->addOption(get_lang('HomeDirectory'), '/');
        if (is_array($folders)) {
            foreach ($folders as & $folder) {
                //Hide some folders
                if ($folder == '/HotPotatoes_files' || $folder == '/certificates' || basename($folder) == 'css') {
                    continue;
                }
                //Admin setting for Hide/Show the folders of all users
                if (api_get_setting('show_users_folders') == 'false' && (strstr($folder, '/shared_folder') || strstr(
                    $folder,
                    'shared_folder_session_'
                ))
                ) {
                    continue;
                }
                //Admin setting for Hide/Show Default folders to all users
                if (api_get_setting(
                    'show_default_folders'
                ) == 'false' && ($folder == '/images' || $folder == '/flash' || $folder == '/audio' || $folder == '/video' || strstr(
                    $folder,
                    '/images/gallery'
                ) || $folder == '/video/flv')
                ) {
                    continue;
                }
                //Admin setting for Hide/Show chat history folder
                if (api_get_setting('show_chat_folder') == 'false' && $folder == '/chat_files') {
                    continue;
                }

                $selected = (substr($dir, 0, -1) == $folder) ? ' selected="selected"' : '';
                $path_parts = explode('/', $folder);
                $folder_titles[$folder] = Text::cut($folder_titles[$folder], 80);
                $space_counter = count($path_parts) - 2;
                if ($space_counter > 0) {
                    $label = str_repeat('&nbsp;&nbsp;&nbsp;', $space_counter).' &mdash; '.$folder_titles[$folder];
                } else {
                    $label = ' &mdash; '.$folder_titles[$folder];
                }
                $parent_select->addOption($label, $folder);
                if ($selected != '') {
                    $parent_select->setSelected($folder);
                }
            }
        }
    } else {
        foreach ($folders as & $folder) {
            $selected = (substr($dir, 0, -1) == $folder) ? ' selected="selected"' : '';
            $label = $folder_titles[$folder];
            if ($folder == $group_dir) {
                $label = '/ ('.get_lang('HomeDirectory').')';
            } else {
                $path_parts = explode('/', str_replace($group_dir, '', $folder));
                $label = Text::cut($label, 80);
                $label = str_repeat('&nbsp;&nbsp;&nbsp;', count($path_parts) - 2).' &mdash; '.$label;
            }
            $parent_select->addOption($label, $folder);
            if ($selected != '') {
                $parent_select->setSelected($folder);
            }
        }
    }
}

if ($is_certificate_mode) {
    $form->addElement('style_submit_button', 'submit', get_lang('CreateCertificate'), 'class="save"');
} else {
    $form->addElement('style_submit_button', 'submit', get_lang('CreateDoc'), 'class="save"');
}

$form->setDefaults($defaults);

// If form validates -> save the new document
if ($form->validate()) {
    $values = $form->exportValues();
    $readonly = isset($values['readonly']) ? 1 : 0;

    $values['title'] = trim($values['title']);

    if (!empty($values['curdirpath'])) {
        $dir = $values['curdirpath'];
    }

    if ($dir[strlen($dir) - 1] != '/') {
        $dir .= '/';
    }

    //Setting the filename
    $filename = $values['title'];
    $filename = addslashes(trim($filename));
    $filename = Security::remove_XSS($filename);
    $filename = api_replace_dangerous_char($filename);
    $filename = FileManager::disable_dangerous_file($filename);

    //Setting the title
    $title = $values['title'];

    //Setting the extension
    $extension = 'html';

    $content = Security::remove_XSS($values['content'], COURSEMANAGERLOWSECURITY);

    if (strpos($content, '/css/frames.css') === false) {
        $content = str_replace(
            '</head>',
            '<style> body{margin:10px;}</style><link rel="stylesheet" href="./css/frames.css" type="text/css" /></head>',
            $content
        );
    }
    if ($fp = @fopen($filepath.$filename.'.'.$extension, 'w')) {
        $content = str_replace(api_get_path(WEB_COURSE_PATH), $_configuration['url_append'].'/courses/', $content);

        // change the path of mp3 to absolute
        // first regexp deals with ../../../ urls
        // Disabled by Ivan Tcholakov.
        //$content = preg_replace("|(flashvars=\"file=)(\.+/)+|","$1".api_get_path(REL_COURSE_PATH).$_course['path'].'/document/',$content);
        //second regexp deals with audio/ urls
        // Disabled by Ivan Tcholakov.
        //$content = preg_replace("|(flashvars=\"file=)([^/]+)/|","$1".api_get_path(REL_COURSE_PATH).$_course['path'].'/document/$2/',$content);
        fputs($fp, $content);
        fclose($fp);
        chmod($filepath.$filename.'.'.$extension, api_get_permissions_for_new_files());
        if (!is_dir($filepath.'css')) {
            mkdir($filepath.'css', api_get_permissions_for_new_directories());
            $doc_id = FileManager::add_document($_course, $dir.'css', 'folder', 0, 'css');
            api_item_property_update(
                $_course,
                TOOL_DOCUMENT,
                $doc_id,
                'FolderCreated',
                $_user['user_id'],
                null,
                null,
                null,
                null,
                $current_session_id
            );
            api_item_property_update(
                $_course,
                TOOL_DOCUMENT,
                $doc_id,
                'invisible',
                $_user['user_id'],
                null,
                null,
                null,
                null,
                $current_session_id
            );
        }

        if (!is_file($filepath.'css/frames.css')) {
            // Make a copy of the current css for the new document
            copy(
                api_get_path(SYS_CSS_PATH).'themes/'.api_get_setting('stylesheets').'/frames.css',
                $filepath.'css/frames.css'
            );
            $doc_id = FileManager::add_document(
                $_course,
                $dir.'css/frames.css',
                'file',
                filesize($filepath.'css/frames.css'),
                'frames.css'
            );
            api_item_property_update(
                $_course,
                TOOL_DOCUMENT,
                $doc_id,
                'DocumentAdded',
                $_user['user_id'],
                null,
                null,
                null,
                null,
                $current_session_id
            );
            api_item_property_update(
                $_course,
                TOOL_DOCUMENT,
                $doc_id,
                'invisible',
                $_user['user_id'],
                null,
                null,
                null,
                null,
                $current_session_id
            );
        }

        $file_size = filesize($filepath.$filename.'.'.$extension);
        $save_file_path = $dir.$filename.'.'.$extension;

        $document_id = FileManager::add_document(
            $_course,
            $save_file_path,
            'file',
            $file_size,
            $title,
            null,
            $readonly
        );
        if ($document_id) {
            api_item_property_update(
                $_course,
                TOOL_DOCUMENT,
                $document_id,
                'DocumentAdded',
                $_user['user_id'],
                $groupId,
                null,
                null,
                null,
                $current_session_id
            );
            // Update parent folders
            FileManager::item_property_update_on_folder($_course, $dir, $_user['user_id']);
            $new_comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
            $new_comment = Database::escape_string($new_comment);
            $new_title = isset($_POST['title']) ? trim($_POST['title']) : '';
            $new_title = htmlspecialchars($new_title);
            $new_title = Database::escape_string($new_title);
            if ($new_comment || $new_title) {
                $ct = '';
                if ($new_comment) {
                    $ct .= ", comment='$new_comment'";
                }
                if ($new_title) {
                    $ct .= ", title='$new_title'";
                }
                Database::query(
                    "UPDATE $doc_table SET".substr($ct, 1)." WHERE c_id = $course_id AND id = '$document_id'"
                );
            }
            $dir = substr($dir, 0, -1);
            $selectcat = '';
            if (isset($_REQUEST['selectcat'])) {
                $selectcat = "&selectcat=".Security::remove_XSS($_REQUEST['selectcat']);
            }
            $certificate_condition = '';
            if ($is_certificate_mode) {
                $df = DocumentManager::get_default_certificate_id($_course['code']);
                if (!isset($df)) {
                    DocumentManager::attach_gradebook_certificate($_course['code'], $document_id);
                }
                $certificate_condition = '&certificate=true';
            }
            header('Location: document.php?'.api_get_cidreq().'&id='.$folder_id.$selectcat.$certificate_condition);
            exit();
        } else {
            Display :: display_header($nameTools, 'Doc');
            Display :: display_error_message(get_lang('Impossible'));
            Display :: display_footer();
        }
    } else {
        Display :: display_header($nameTools, 'Doc');
        Display :: display_error_message(get_lang('Impossible'));
        Display :: display_footer();
    }
} else {

    // Interbreadcrumb for the current directory root path
    // Copied from document.php
    $dir_array = explode('/', $dir);
    $array_len = count($dir_array);

    // Interbreadcrumb for the current directory root path
    if (empty($document_data['parents'])) {
        $interbreadcrumb[] = array('url' => '#', 'name' => $document_data['title']);
    } else {
        foreach ($document_data['parents'] as $document_sub_data) {
            $interbreadcrumb[] = array(
                'url' => $document_sub_data['document_url'],
                'name' => $document_sub_data['title']
            );
        }
    }

    $interbreadcrumb[] = array('url' => '#', 'name' => $nameTools);

    Display :: display_header($nameTools, "Doc");

    if ($is_certificate_mode) {
        $all_information_by_create_certificate = DocumentManager::get_all_info_to_certificate(
            api_get_user_id(),
            api_get_course_id()
        );

        $str_info = '';
        foreach ($all_information_by_create_certificate[0] as $info_value) {
            $str_info .= $info_value.'<br/>';
        }
        $create_certificate = get_lang('CreateCertificateWithTags');
        Display::display_normal_message($create_certificate.': <br /><br/>'.$str_info, false);
    }
    echo $form->return_form();
    Display :: display_footer();
}

<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This file allows creating new html documents with an online WYSIWYG html editor.
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script();
api_protect_course_group(GroupManager::GROUP_TOOL_DOCUMENTS);

$this_section = SECTION_COURSES;
$groupRights = Session::read('group_member_with_upload_rights');
$htmlHeadXtra[] = '
<script>
$(function() {
    $(".scrollbar-light").scrollbar();

    expandColumnToogle("#hide_bar_template", {
        selector: "#template_col",
        width: 3
    }, {
        selector: "#doc_form",
        width: 9
    });

    CKEDITOR.on("instanceReady", function (e) {
        showTemplates();
        e.editor.on("beforeCommandExec", function (event) {
            if (event.data.name == "save") {
                $("#create_document").append("<input type=hidden name=button_ck value=1 />");
            }
        });
    });
});

$(document).on("change", ".selectpicker", function () {
    var dirValue = $(this).val();
    $.ajax({
        contentType: "application/x-www-form-urlencoded",
        data: "dirValue="+dirValue,
        url: "'.api_get_path(WEB_AJAX_PATH).'document.ajax.php?a=document_destination",
        type: "POST",
        success: function(response) {
            $("[name=\'dirValue\']").val(response)
        }
    });
});

function setFocus() {
   $("#document_title").focus();
}

$(window).on("load", function () {
	setFocus();
});

</script>';

//I'm in the certification module?
$is_certificate_mode = false;
if (isset($_REQUEST['certificate']) && $_REQUEST['certificate'] === 'true') {
    $is_certificate_mode = true;
}

$nameTools = get_lang('CreateDocument');
if ($is_certificate_mode) {
    $nameTools = get_lang('CreateCertificate');
}

/* Constants and variables */
$doc_table = Database::get_course_table(TABLE_DOCUMENT);
$course_id = api_get_course_int_id();
$courseCode = api_get_course_id();
$sessionId = api_get_session_id();
$userId = api_get_user_id();
$_course = api_get_course_info();
$groupId = api_get_group_id();
$document_data = [];

if (isset($_REQUEST['id'])) {
    $document_data = DocumentManager::get_document_data_by_id(
        $_REQUEST['id'],
        $courseCode,
        true,
        0
    );
}

if (!empty($sessionId) && empty($document_data)) {
    $document_data = DocumentManager::get_document_data_by_id(
        $_REQUEST['id'],
        $courseCode,
        true,
        $sessionId
    );
}
$groupIid = 0;
$group_properties = [];
if (!empty($groupId)) {
    $group_properties = GroupManager::get_group_properties($groupId);
    $groupIid = $group_properties['iid'];
}

if (empty($document_data)) {
    $dir = '/';
    $folder_id = 0;
    if (api_is_in_group()) {
        $document_id = DocumentManager::get_document_id($_course, $group_properties['directory']);
        $document_data = DocumentManager::get_document_data_by_id($document_id, api_get_course_id());
        $dir = $document_data['path'];
        $folder_id = $document_data['id'];
    }
} else {
    $folder_id = $document_data['id'];
    $dir = $document_data['path'];
}

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
    $document_id = DocumentManager::get_document_id(
        api_get_course_info(),
        '/certificates'
    );
    $document_data = DocumentManager::get_document_data_by_id(
        $document_id,
        api_get_course_id(),
        true
    );
    $folder_id = $document_data['id'];
    $dir = '/certificates/';
}

$doc_tree = explode('/', $dir);
$count_dir = count($doc_tree) - 2; // "2" because at the begin and end there are 2 "/"

if (api_is_in_group()) {
    $group_properties = GroupManager::get_group_properties(api_get_group_id());

    // Level correction for group documents.
    if (!empty($group_properties['directory'])) {
        $count_dir = $count_dir > 0 ? $count_dir - 1 : 0;
    }
}
$relative_url = '';
for ($i = 0; $i < ($count_dir); $i++) {
    $relative_url .= '../';
}

if ($relative_url == '') {
    $relative_url = '/';
}

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
$editorConfig = [
    'ToolbarSet' => $is_allowed_to_edit ? 'Documents' : 'DocumentsStudent',
    'Width' => '100%',
    'Height' => '400',
    'cols-size' => [2, 10, 0],
    'FullPage' => true,
    'InDocument' => true,
    'CreateDocumentDir' => $relative_url,
    'CreateDocumentWebDir' => (empty($group_properties['directory']))
                                ? api_get_path(WEB_COURSE_PATH).$_course['path'].'/document/'
                                : api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document'.$group_properties['directory'].'/',
    'BaseHref' => api_get_path(WEB_COURSE_PATH).$_course['path'].'/document'.$dir,
];

if ($is_certificate_mode) {
    $editorConfig['CreateDocumentDir'] = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document/';
    $editorConfig['CreateDocumentWebDir'] = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document/';
    $editorConfig['BaseHref'] = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document'.$dir;
}

$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';

if (!is_dir($filepath)) {
    $filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
    $dir = '/';
}

if (!$is_certificate_mode) {
    if (api_is_in_group()) {
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
            'name' => get_lang('Groups'),
        ];
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
            'name' => get_lang('GroupSpace').' '.$group_properties['name'],
        ];
        $path = explode('/', $dir);
        if (strcasecmp('/'.$path[1], $group_properties['directory']) !== 0) {
            api_not_allowed(true);
        }
    }
    $interbreadcrumb[] = [
        "url" => "./document.php?curdirpath=".urlencode($dir)."&".api_get_cidreq(),
        "name" => get_lang('Documents'),
    ];
} else {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Gradebook'),
    ];
}

if (!api_is_allowed_in_course()) {
    api_not_allowed(true);
}

if (!($is_allowed_to_edit ||
    $groupRights ||
    DocumentManager::is_my_shared_folder($userId, $dir, api_get_session_id()))
) {
    api_not_allowed(true);
}

Event::event_access_tool(TOOL_DOCUMENT);
$display_dir = $dir;
if (isset($group_properties)) {
    $display_dir = explode('/', $dir);
    unset($display_dir[0]);
    unset($display_dir[1]);
    $display_dir = implode('/', $display_dir);
}

$select_cat = isset($_REQUEST['selectcat']) ? (int) $_REQUEST['selectcat'] : null;
$curDirPath = isset($_GET['curdirpath']) ? Security::remove_XSS($_GET['curdirpath']) : null;

// Create a new form
$form = new FormValidator(
    'create_document',
    'post',
    api_get_self().'?'.api_get_cidreq().'&dir='.Security::remove_XSS(urlencode($dir)).'&selectcat='.$select_cat,
    null
);

// form title
$form->addElement('header', $nameTools);

if ($is_certificate_mode) {
    //added condition for certicate in gradebook
    $form->addElement(
        'hidden',
        'certificate',
        'true',
        ['id' => 'certificate']
    );
    if (isset($_GET['selectcat'])) {
        $form->addElement('hidden', 'selectcat', $select_cat);
    }
}

// Hidden element with current directory
$form->addElement('hidden', 'id');
$defaults = [];
$defaults['id'] = $folder_id;

// Filename
$form->addElement('hidden', 'title_edited', 'false', 'id="title_edited"');

/**
 * Check if a document width the chosen filename already exists.
 */
function document_exists($filename)
{
    global $dir;
    $cleanName = api_replace_dangerous_char($filename);

    // No "dangerous" files
    $cleanName = disable_dangerous_file($cleanName);

    return !DocumentManager::documentExists(
        $dir.$cleanName.'.html',
        api_get_course_info(),
        api_get_session_id(),
        api_get_group_id()
    );
}

// Add group to the form
if ($is_certificate_mode) {
    $form->addText(
        'title',
        get_lang('CertificateName'),
        true,
        ['cols-size' => [2, 10, 0], 'autofocus']
    );
} else {
    $form->addText(
        'title',
        get_lang('Title'),
        true,
        ['cols-size' => [2, 10, 0], 'autofocus']
    );
}

// Show read-only box only in groups
if (!empty($groupId)) {
    $group[] = $form->createElement(
        'checkbox',
        'readonly',
        '',
        get_lang('ReadOnly')
    );
}
$form->addRule('title', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('title', get_lang('FileExists'), 'callback', 'document_exists');

$current_session_id = api_get_session_id();
$form->addHtmlEditor(
    'content',
    get_lang('Content'),
    true,
    true,
    $editorConfig
);

// Comment-field
$folders = DocumentManager::get_all_document_folders(
    $_course,
    $groupIid,
    $is_allowed_to_edit
);

// If we are not in the certificates creation, display a folder chooser for the
// new document created
if (!$is_certificate_mode &&
    !DocumentManager::is_my_shared_folder($userId, $dir, $current_session_id)
) {
    $folders = DocumentManager::get_all_document_folders(
        $_course,
        $groupIid,
        $is_allowed_to_edit
    );

    $parent_select = $form->addSelect(
        'curdirpath',
        get_lang('DestinationDirectory'),
        null,
        ['cols-size' => [2, 10, 0]]
    );

    $folder_titles = [];
    if (is_array($folders)) {
        $escaped_folders = [];
        foreach ($folders as $key => &$val) {
            // Hide some folders
            if ($val == '/HotPotatoes_files' || $val == '/certificates' || basename($val) == 'css') {
                continue;
            }
            // Admin setting for Hide/Show the folders of all users
            if (api_get_setting('show_users_folders') == 'false' &&
                (strstr($val, '/shared_folder') || strstr($val, 'shared_folder_session_'))
            ) {
                continue;
            }
            // Admin setting for Hide/Show Default folders to all users
            if (api_get_setting('show_default_folders') == 'false' && ($val == '/images' || $val == '/flash' || $val == '/audio' || $val == '/video' || strstr($val, '/images/gallery') || $val == '/video/flv')) {
                continue;
            }
            // Admin setting for Hide/Show chat history folder
            if (api_get_setting('show_chat_folder') == 'false' && $val == '/chat_files') {
                continue;
            }
            $escaped_folders[$key] = Database::escape_string($val);
        }
        $folder_sql = implode("','", $escaped_folders);

        $sql = "SELECT * FROM $doc_table
				WHERE
				    c_id = $course_id AND
				    filetype = 'folder' AND
				    path IN ('".$folder_sql."')";
        $res = Database::query($sql);
        $folder_titles = [];
        while ($obj = Database::fetch_object($res)) {
            $folder_titles[$obj->path] = Security::remove_XSS($obj->title);
        }
    }

    if (empty($group_dir)) {
        $parent_select->addOption(get_lang('HomeDirectory'), '/');
        if (is_array($folders)) {
            foreach ($folders as &$folder) {
                //Hide some folders
                if ($folder == '/HotPotatoes_files' || $folder == '/certificates' || basename($folder) == 'css') {
                    continue;
                }
                //Admin setting for Hide/Show the folders of all users
                if (api_get_setting('show_users_folders') == 'false' &&
                    (strstr($folder, '/shared_folder') || strstr($folder, 'shared_folder_session_'))
                ) {
                    continue;
                }
                //Admin setting for Hide/Show Default folders to all users
                if (api_get_setting('show_default_folders') == 'false' &&
                    (
                        $folder == '/images' ||
                        $folder == '/flash' ||
                        $folder == '/audio' ||
                        $folder == '/video' ||
                        strstr($folder, '/images/gallery') ||
                        $folder == '/video/flv'
                    )
                ) {
                    continue;
                }
                //Admin setting for Hide/Show chat history folder
                if (api_get_setting('show_chat_folder') == 'false' &&
                    $folder == '/chat_files'
                ) {
                    continue;
                }

                $selected = (substr($dir, 0, -1) == $folder) ? ' selected="selected"' : '';
                $path_parts = explode('/', $folder);
                $folder_titles[$folder] = cut($folder_titles[$folder], 80);
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
        if (is_array($folders) && !empty($folders)) {
            foreach ($folders as &$folder) {
                $selected = (substr($dir, 0, -1) == $folder) ? ' selected="selected"' : '';
                $label = $folder_titles[$folder];
                if ($folder == $group_dir) {
                    $label = '/ ('.get_lang('HomeDirectory').')';
                } else {
                    $path_parts = explode('/', str_replace($group_dir, '', $folder));
                    $label = cut($label, 80);
                    $label = str_repeat('&nbsp;&nbsp;&nbsp;', count($path_parts) - 2).' &mdash; '.$label;
                }
                $parent_select->addOption($label, $folder);
                if ($selected != '') {
                    $parent_select->setSelected($folder);
                }
            }
        }
    }
}

$form->addHidden('dirValue', '');

if ($is_certificate_mode) {
    $form->addButtonCreate(get_lang('CreateCertificate'));
} else {
    $form->addButtonCreate(get_lang('CreateDoc'));
}

$form->setDefaults($defaults);

// If form validates -> save the new document
if ($form->validate()) {
    $values = $form->exportValues();
    $readonly = isset($values['readonly']) ? 1 : 0;
    $values['title'] = trim($values['title']);

    if (!empty($values['dirValue'])) {
        $dir = $values['dirValue'];
    }

    if ($dir[strlen($dir) - 1] != '/') {
        $dir .= '/';
    }
    $filepath = $filepath.$dir;

    // Setting the filename
    $filename = $values['title'];
    $filename = addslashes(trim($filename));
    $filename = Security::remove_XSS($filename);
    $filename = api_replace_dangerous_char($filename);
    $filename = disable_dangerous_file($filename);
    $filename .= DocumentManager::getDocumentSuffix(
        $_course,
        api_get_session_id(),
        api_get_group_id()
    );

    // Setting the title
    $title = $values['title'];
    $extension = 'html';
    $content = Security::remove_XSS($values['content'], COURSEMANAGERLOWSECURITY);
    // Don't create file with the same name.
    if (file_exists($filepath.$filename.'.'.$extension)) {
        Display::addFlash(Display::return_message(get_lang('FileExists').' '.$title, 'error', false));
        Display::display_header($nameTools, 'Doc');
        Display::display_footer();
        exit;
    }

    if ($fp = @fopen($filepath.$filename.'.'.$extension, 'w')) {
        $content = str_replace(
            api_get_path(WEB_COURSE_PATH),
            api_get_configuration_value('url_append').api_get_path(REL_COURSE_PATH),
            $content
        );

        fputs($fp, $content);
        fclose($fp);
        chmod($filepath.$filename.'.'.$extension, api_get_permissions_for_new_files());
        $file_size = filesize($filepath.$filename.'.'.$extension);
        $save_file_path = $dir.$filename.'.'.$extension;

        $document_id = add_document(
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
                $userId,
                $group_properties,
                null,
                null,
                null,
                $current_session_id
            );
            // Update parent folders
            item_property_update_on_folder($_course, $dir, $userId);
            $new_comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
            $new_title = isset($_POST['title']) ? trim($_POST['title']) : '';
            $new_title = htmlspecialchars($new_title);
            if ($new_comment || $new_title) {
                $ct = '';
                $params = [];
                if ($new_comment) {
                    $params['comment'] = $new_comment;
                }
                if ($new_title) {
                    $params['title'] = $new_title;
                }
                if (!empty($params)) {
                    Database::update(
                        $doc_table,
                        $params,
                        ['c_id = ? AND id = ?' => [$course_id, $document_id]]
                    );
                }
            }
            $dir = substr($dir, 0, -1);
            $selectcat = '';
            if (isset($_REQUEST['selectcat'])) {
                $selectcat = "&selectcat=".intval($_REQUEST['selectcat']);
            }
            $certificate_condition = '';
            if ($is_certificate_mode) {
                $df = DocumentManager::get_default_certificate_id($_course['code']);
                if (!isset($df)) {
                    DocumentManager::attach_gradebook_certificate($_course['code'], $document_id);
                }
                $certificate_condition = '&certificate=true&curdirpath=/certificates';
            }
            Display::addFlash(Display::return_message(get_lang('ItemAdded')));
            $url = 'document.php?'.api_get_cidreq().'&id='.$folder_id.$selectcat.$certificate_condition;

            $redirectToEditPage = isset($_POST['button_ck']) && 1 === (int) $_POST['button_ck'];
            if ($redirectToEditPage) {
                $url = 'edit_document.php?'.api_get_cidreq().'&id='.$document_id.$selectcat.$certificate_condition;
            }

            header('Location: '.$url);
            exit();
        } else {
            Display::addFlash(Display::return_message(get_lang('Impossible'), 'error'));
            Display::display_header($nameTools, 'Doc');
            Display::display_footer();
        }
    } else {
        Display::addFlash(Display::return_message(get_lang('Impossible'), 'error'));
        Display::display_header($nameTools, 'Doc');
        Display::display_footer();
    }
} else {
    // Copied from document.php
    $dir_array = explode('/', $dir);
    $array_len = count($dir_array);

    // Breadcrumb for the current directory root path
    if (!empty($document_data)) {
        if (empty($document_data['parents'])) {
            $interbreadcrumb[] = [
                'url' => '#',
                'name' => $document_data['title'],
            ];
        } else {
            foreach ($document_data['parents'] as $document_sub_data) {
                $interbreadcrumb[] = [
                    'url' => $document_sub_data['document_url'],
                    'name' => $document_sub_data['title'],
                ];
            }
        }
    }

    Display::display_header($nameTools, "Doc");
    // link back to the documents overview
    if ($is_certificate_mode) {
        $actionsLeft = '<a href="document.php?'.api_get_cidreq().'&certificate=true&id='.$folder_id.'&selectcat='.$select_cat.'">'.
            Display::return_icon('back.png', get_lang('Back').' '.get_lang('To').' '.get_lang('CertificateOverview'), '', ICON_SIZE_MEDIUM).'</a>';
        $actionsLeft .= '<a id="hide_bar_template" href="#" role="button">'.
            Display::return_icon('expand.png', get_lang('Back'), ['id' => 'expand'], ICON_SIZE_MEDIUM).
            Display::return_icon('contract.png', get_lang('Back'), ['id' => 'contract', 'class' => 'hide'], ICON_SIZE_MEDIUM).'</a>';
    } else {
        $actionsLeft = '<a href="document.php?'.api_get_cidreq().'&curdirpath='.Security::remove_XSS($dir).'">'.
            Display::return_icon('back.png', get_lang('Back').' '.get_lang('To').' '.get_lang('DocumentsOverview'), '', ICON_SIZE_MEDIUM).'</a>';
        $actionsLeft .= '<a id="hide_bar_template" href="#" role="button">'.
            Display::return_icon('expand.png', get_lang('Expand'), ['id' => 'expand'], ICON_SIZE_MEDIUM).
            Display::return_icon('contract.png', get_lang('Collapse'), ['id' => 'contract', 'class' => 'hide'], ICON_SIZE_MEDIUM).'</a>';
    }

    echo $toolbar = Display::toolbarAction('actions-documents', [$actionsLeft]);

    if ($is_certificate_mode) {
        $all_information_by_create_certificate = DocumentManager::get_all_info_to_certificate(
            api_get_user_id(),
            api_get_course_id(),
            api_get_session_id()
        );

        $str_info = '';
        foreach ($all_information_by_create_certificate[0] as $info_value) {
            $str_info .= $info_value.'<br/>';
        }
        $create_certificate = get_lang('CreateCertificateWithTags');
        echo Display::return_message($create_certificate.': <br /><br/>'.$str_info, 'normal', false);
    }

    // HTML-editor
    echo '<div class="page-create">
            <div class="row" style="overflow:hidden">
            <div id="template_col" class="col-md-3">
                <div class="panel panel-default">
                <div class="panel-body">
                    <div id="frmModel" class="items-templates scrollbar-light"></div>
                </div>
                </div>
            </div>
            <div id="doc_form" class="col-md-9">
                '.$form->returnForm().'
            </div>
          </div></div>';
    Display::display_footer();
}

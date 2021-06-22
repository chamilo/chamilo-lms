<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';

api_protect_course_script(true);

// Adding extra javascript to the form
$htmlHeadXtra[] = api_get_jquery_libraries_js(['jquery-ui', 'jquery-upload']);

// Variables
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
$_course = api_get_course_info();
$groupId = api_get_group_id();
$courseDir = $_course['path'].'/document';
$sys_course_path = api_get_path(SYS_COURSE_PATH);
$base_work_dir = $sys_course_path.$courseDir;
$sessionId = api_get_session_id();
$selectcat = isset($_GET['selectcat']) ? (int) $_GET['selectcat'] : null;

$document_data = [];

if (isset($_REQUEST['id'])) {
    $document_data = DocumentManager::get_document_data_by_id(
        $_REQUEST['id'],
        api_get_course_id(),
        true,
        $sessionId
    );

    if (0 != $sessionId && !$document_data) {
        $document_data = DocumentManager::get_document_data_by_id(
            $_REQUEST['id'],
            api_get_course_id(),
            true,
            0
        );
    }
}

if (empty($document_data)) {
    $document_id = $parent_id = 0;
    $path = '/';
} else {
    $document_id = $document_data['id'];
    $path = $document_data['path'];
    $parent_id = DocumentManager::get_document_id(
        api_get_course_info(),
        dirname($path)
    );
}
$group_properties = [];

$htmlHeadXtra[] = '<script>
function check_unzip() {
    if (document.upload.unzip.checked) {
        document.upload.if_exists[1].checked=true;
    } else {
        document.upload.if_exists[2].checked=true;
    }
}

function setFocus() {
    $("#title_file").focus();
}
</script>';

$groupIid = 0;
// This needs cleaning!
if (!empty($groupId)) {
    // If the group id is set, check if the user has the right to be here
    // Get group info
    $group_properties = GroupManager::get_group_properties($groupId);
    $groupIid = $group_properties['iid'];

    // Only courseadmin or group members allowed
    if ($is_allowed_to_edit || GroupManager::is_user_in_group(api_get_user_id(), $group_properties)) {
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
            'name' => get_lang('Group area'),
        ];
    } else {
        api_not_allowed(true);
    }
    GroupManager::allowUploadEditDocument(api_get_user_id(), api_get_course_int_id(), $group_properties, null, true);
} elseif ($is_allowed_to_edit ||
    DocumentManager::is_my_shared_folder(api_get_user_id(), $path, api_get_session_id())) {
} else {
    // No course admin and no group member...
    api_not_allowed(true);
}

// Group docs can only be uploaded in the group directory
if (0 != $groupId && '/' == $path) {
    $path = $group_properties['directory'];
}

// I'm in the certification module?
$is_certificate_mode = false;
$is_certificate_array = explode('/', $path);
array_shift($is_certificate_array);
if ('certificates' == $is_certificate_array[0]) {
    $is_certificate_mode = true;
}

// Title of the tool
$add_group_to_title = null;
if (0 != $groupId) {
    // Add group name after for group documents
    $add_group_to_title = ' ('.$group_properties['name'].')';
}
if (isset($_REQUEST['certificate'])) {
    $nameTools = get_lang('Upload certificate').$add_group_to_title;
    $is_certificate_mode = true;
} else {
    $nameTools = get_lang('Upload documents').$add_group_to_title;
}

$certificateLink = '';
if ($is_certificate_mode) {
    $certificateLink = '&certificate=true';
}

// Breadcrumbs
if ($is_certificate_mode) {
    $interbreadcrumb[] = [
        'url' => '../gradebook/index.php?'.api_get_cidreq().$certificateLink,
        'name' => get_lang('Assessments'),
    ];
} else {
    $interbreadcrumb[] = [
        'url' => './document.php?id='.$parent_id.'&'.api_get_cidreq().$certificateLink,
        'name' => get_lang('Documents'),
    ];
}

// Interbreadcrumb for the current directory root path
if ($document_data) {
    if (empty($document_data['parents'])) {
        $interbreadcrumb[] = ['url' => '#', 'name' => $document_data['title']];
    } else {
        foreach ($document_data['parents'] as $document_sub_data) {
            $interbreadcrumb[] = [
                'url' => $document_sub_data['document_url'].$certificateLink,
                'name' => $document_sub_data['title'],
            ];
        }
    }
}

$this_section = SECTION_COURSES;

/*    Here we do all the work */
$unzip = isset($_POST['unzip']) ? $_POST['unzip'] : null;
$index = isset($_POST['index_document']) ? $_POST['index_document'] : null;
// User has submitted a file

if (!empty($_FILES)) {
    $document = DocumentManager::upload_document(
        $_FILES,
        $_POST['curdirpath'],
        $_POST['title'],
        $_POST['comment'],
        $unzip,
        $_POST['if_exists'],
        $index,
        true,
        'file',
        true,
        $_REQUEST['id'] ?? 0
    );

    $redirectUrl = api_get_self().'?'.api_get_cidreq().$certificateLink;
    if ($document) {
        $redirectUrl .= '&'.http_build_query(
            [
                'id' => $parent_id,
            ]
        );
    }

    header("Location: $redirectUrl");
    exit;
}

// Display the header
Display::display_header($nameTools, 'Doc');

// Actions
// Link back to the documents overview
if ($is_certificate_mode) {
    $actions = '<a href="document.php?id='.$document_id.'&selectcat='.$selectcat.'&'.api_get_cidreq().'">'.
        Display::return_icon('back.png', get_lang('Back to').' '.get_lang('Certificate overview'), '', ICON_SIZE_MEDIUM).
        '</a>';
} else {
    $actions = '<a href="document.php?id='.$document_id.'&'.api_get_cidreq().'">'.
        Display::return_icon('back.png', get_lang('Back to').' '.get_lang('Documents overview'), '', ICON_SIZE_MEDIUM).
        '</a>';
}

// Link to create a folder
echo $toolbar = Display::toolbarAction('toolbar-upload', [$actions]);
// Form to select directory
$folders = DocumentManager::get_all_document_folders(
    $_course,
    $groupIid,
    $is_allowed_to_edit
);
if (!$is_certificate_mode) {
    echo DocumentManager::build_directory_selector(
        $folders,
        $document_id,
        (isset($group_properties['directory']) ? $group_properties['directory'] : [])
    );
}

$action = api_get_self().'?'.api_get_cidreq().'&id='.$document_id.$certificateLink;

$form = new FormValidator(
    'upload',
    'POST',
    $action.'#tabs-2',
    '',
    ['enctype' => 'multipart/form-data']
);
$form->addElement('hidden', 'id', $document_id);
$form->addElement('hidden', 'curdirpath', $path);

$repo = Container::getDocumentRepository();
$total = $repo->getTotalSpace(api_get_course_int_id());
$courseQuota = format_file_size(DocumentManager::get_course_quota() - $total);
$label =
    get_lang('Maximum file size').': '.ini_get('upload_max_filesize').'<br/>'.
    get_lang('Space Available').': '.$courseQuota;

$form->addElement('file', 'file', [get_lang('File'), $label], 'style="width: 250px" id="user_upload"');
$form->addElement('text', 'title', get_lang('Title'), ['id' => 'title_file']);
$form->addElement('textarea', 'comment', get_lang('Comment'));

// Advanced parameters
$form->addButtonAdvancedSettings('advanced_params');
$form->addElement('html', '<div id="advanced_params_options" style="display:none">');

// Check box options
$form->addElement(
    'checkbox',
    'unzip',
    get_lang('Options'),
    get_lang('Uncompress zip'),
    'onclick="javascript: check_unzip();" value="1"'
);

if ('true' === api_get_setting('search_enabled')) {
    //TODO: include language file
    $supportedFormats = get_lang('Supported formats for index').': HTML, PDF, TXT, PDF, Postscript, MS Word, RTF, MS Power Point';
    $form->addElement(
        'checkbox',
        'index_document',
        '',
        get_lang('Index document text?').'<div style="font-size: 80%" >'.$supportedFormats.'</div>'
    );
    $form->addElement('html', '<br /><div class="sub-form">');
    $form->addElement('html', '<div class="label">'.get_lang('Document language for indexation').'</div>');
    //$form->addLabel(get_lang('Language'), api_get_languages_combo());
    $form->addElement('html', '</div><div class="sub-form">');
    $specific_fields = get_specific_field_list();
    foreach ($specific_fields as $specific_field) {
        $form->addElement('text', $specific_field['code'], $specific_field['name']);
    }
    $form->addElement('html', '</div>');
}

$form->addElement('radio', 'if_exists', get_lang('If file exists:'), get_lang('Do nothing'), 'nothing');
$form->addElement('radio', 'if_exists', '', get_lang('Overwrite the existing file'), 'overwrite');
$form->addElement('radio', 'if_exists', '', get_lang('Rename the uploaded file if it exists'), 'rename');
// Close the java script and avoid the footer up
$form->addElement('html', '</div>');

// Button upload document
$form->addButtonSend(get_lang('Upload file'), 'submitDocument');
$form->addProgress('DocumentUpload', 'file');

$fileExistsOption = api_get_setting('document_if_file_exists_option');

$defaultFileExistsOption = 'rename';
if (!empty($fileExistsOption)) {
    $defaultFileExistsOption = $fileExistsOption;
}

$defaults = [
    'index_document' => 'checked="checked"',
    'if_exists' => $defaultFileExistsOption,
];

$form->setDefaults($defaults);

$url = api_get_path(WEB_AJAX_PATH).
    'document.ajax.php?'.api_get_cidreq().'&a=upload_file&curdirpath='.$path.'&directory_parent_id='.(int) ($_REQUEST['id'] ?? 0);

$multipleForm = new FormValidator(
    'drag_drop',
    'post',
    '#',
    ['enctype' => 'multipart/form-data']
);
$multipleForm->addMultipleUpload($url);

$headers = [
    get_lang('Upload'),
    get_lang('Upload').' ('.get_lang('Simple').')',
];

echo Display::tabs($headers, [$multipleForm->returnForm(), $form->returnForm()], 'tabs');

Display::display_footer();

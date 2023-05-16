<?php
/* For licensing terms, see /license.txt */

/**
 * Main script for the documents tool.
 *
 * This script allows the user to manage files and directories on a remote http server.
 *
 * The user can : - navigate through files and directories.
 *                 - upload a file
 *                 - delete, copy a file or a directory
 *                 - edit properties & content (name, comments, html content)
 *
 * The script is organised in four sections.
 *
 * 1) Execute the command called by the user
 *                Note: somme commands of this section are organised in two steps.
 *                The script always begins with the second step,
 *                so it allows to return more easily to the first step.
 *
 *                Note (March 2004) some editing functions (renaming, commenting)
 *                are moved to a separate page, edit_document.php. This is also
 *                where xml and other stuff should be added.
 *
 * 2) Define the directory to display
 *
 * 3) Read files and directories from the directory defined in part 2
 * 4) Display all of that on an HTML page
 *
 * @todo eliminate code duplication between
 * document/document.php, scormdocument.php
 *
 * @package chamilo.document
 */
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

    if ($sessionId != 0 && !$document_data) {
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
            'name' => get_lang('GroupSpace'),
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
if ($groupId != 0 && $path == '/') {
    $path = $group_properties['directory'];
}

// I'm in the certification module?
$is_certificate_mode = false;
$is_certificate_array = explode('/', $path);
array_shift($is_certificate_array);
if ($is_certificate_array[0] == 'certificates') {
    $is_certificate_mode = true;
}

// Title of the tool
$add_group_to_title = null;
if ($groupId != 0) {
    // Add group name after for group documents
    $add_group_to_title = ' ('.$group_properties['name'].')';
}
if (isset($_REQUEST['certificate'])) {
    $nameTools = get_lang('UploadCertificate').$add_group_to_title;
    $is_certificate_mode = true;
} else {
    $nameTools = get_lang('UplUploadDocument').$add_group_to_title;
}

$certificateLink = '';
if ($is_certificate_mode) {
    $certificateLink = '&certificate=true';
}

// Breadcrumbs
if ($is_certificate_mode) {
    $interbreadcrumb[] = [
        'url' => '../gradebook/index.php?'.api_get_cidreq().$certificateLink,
        'name' => get_lang('Gradebook'),
    ];
} else {
    $interbreadcrumb[] = [
        'url' => './document.php?id='.$document_id.'&'.api_get_cidreq().$certificateLink,
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
    DocumentManager::upload_document(
        $_FILES,
        $_POST['curdirpath'],
        $_POST['title'],
        $_POST['comment'],
        $unzip,
        $_POST['if_exists'],
        $index,
        true
    );

    $redirectUrl = api_get_self().'?'.api_get_cidreq().$certificateLink;
    if ($document_data) {
        $redirectUrl .= '&'.http_build_query(
            [
                'id' => $document_data['iid'],
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
        Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('CertificateOverview'), '', ICON_SIZE_MEDIUM).
        '</a>';
} else {
    $actions = '<a href="document.php?id='.$document_id.'&'.api_get_cidreq().'">'.
        Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('DocumentsOverview'), '', ICON_SIZE_MEDIUM).
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

$courseQuota = format_file_size(DocumentManager::get_course_quota() - DocumentManager::documents_total_space());
$label =
    get_lang('MaxFileSize').': '.getIniMaxFileSizeInBytes(true).'<br/>'.
    get_lang('DocumentQuota').': '.$courseQuota;

$form->addElement('BigUpload', 'file', [get_lang('File'), $label], ['id' => 'bigUploadFile', 'data-origin' => 'document']);
//$form->addElement('file', 'file', [get_lang('File'), $label], ['id' => 'user_upload']);
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
    get_lang('Uncompress'),
    'onclick="javascript: check_unzip();" value="1"'
);

if (api_get_setting('search_enabled') === 'true') {
    //TODO: include language file
    $supportedFormats = get_lang('SupportedFormatsForIndex').': HTML, PDF, TXT, PDF, Postscript, MS Word, RTF, MS Power Point';
    $form->addElement(
        'checkbox',
        'index_document',
        '',
        get_lang('SearchFeatureDoIndexDocument').'<div style="font-size: 80%" >'.$supportedFormats.'</div>'
    );
    $form->addElement('html', '<br /><div class="sub-form">');
    $form->addElement('html', '<div class="label">'.get_lang('SearchFeatureDocumentLanguage').'</div>');
    $form->addLabel(get_lang('Language'), api_get_languages_combo());
    $form->addElement('html', '</div><div class="sub-form">');
    $specific_fields = get_specific_field_list();
    foreach ($specific_fields as $specific_field) {
        $form->addElement('text', $specific_field['code'], $specific_field['name']);
    }
    $form->addElement('html', '</div>');
}

$form->addElement('radio', 'if_exists', get_lang('UplWhatIfFileExists'), get_lang('UplDoNothing'), 'nothing');
$form->addElement('radio', 'if_exists', '', get_lang('UplOverwriteLong'), 'overwrite');
$form->addElement('radio', 'if_exists', '', get_lang('UplRenameLong'), 'rename');
// Close the java script and avoid the footer up
$form->addElement('html', '</div>');

// Button upload document
$form->addButtonSend(get_lang('SendDocument'), 'submitDocument');
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

$url = api_get_path(WEB_AJAX_PATH).'document.ajax.php?'.api_get_cidreq().'&a=upload_file&curdirpath='.$path;

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

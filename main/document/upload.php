<?php
/* For licensing terms, see /license.txt */

/**
 * Main script for the documents tool
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
// Including the global initialization file
require_once '../inc/global.inc.php';

// Including additional libraries
require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';

// Adding extra javascript to the form
$htmlHeadXtra[] = api_get_jquery_libraries_js(array('jquery-ui', 'jquery-upload'));

// Variables

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
$_course = api_get_course_info();
$groupId = api_get_group_id();
$courseDir = $_course['path'].'/document';
$sys_course_path = api_get_path(SYS_COURSE_PATH);
$base_work_dir = $sys_course_path.$courseDir;
$sessionId = api_get_session_id();
$selectcat = isset($_GET['selectcat']) ? Security::remove_XSS($_GET['selectcat']) : null;

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

if (empty($document_data)) {
    $document_id  = $parent_id =  0;
    $path = '/';
} else {
    $document_id = $document_data['id'];
    $path = $document_data['path'];
    $parent_id = DocumentManager::get_document_id(
        api_get_course_info(),
        dirname($path)
    );
}
$group_properties = array();

$url = api_get_path(WEB_AJAX_PATH).'document.ajax.php?'.api_get_cidreq().'&a=upload_file&curdirpath='.$path;

$htmlHeadXtra[] = '<script>

function check_unzip() {
    if (document.upload.unzip.checked){
        document.upload.if_exists[0].disabled=true;
        document.upload.if_exists[1].checked=true;
        document.upload.if_exists[2].disabled=true;
    } else {
        document.upload.if_exists[0].checked=true;
        document.upload.if_exists[0].disabled=false;
        document.upload.if_exists[2].disabled=false;
    }
}

function setFocus(){
    $("#title_file").focus();
}
</script>';

$htmlHeadXtra[] = "
<script>
$(function () {
    'use strict';
    var url = '".$url."';
    var uploadButton = $('<button/>')
        .addClass('btn btn-primary')
        .prop('disabled', true)
        .text('".get_lang('Loading')."')
        .on('click', function () {
            var \$this = $(this),
            data = \$this.data();

            \$this
                .off('click')
                .text('".get_lang('Cancel')."')
                .on('click', function () {
                    \$this.remove();
                    data.abort();
                });
            data.submit().always(function () {
                \$this.remove();
            });
        });

    $('#file_upload').fileupload({
        url: url,
        dataType: 'json',
        autoUpload: false,
        // Enable image resizing, except for Android and Opera,
        // which actually support image resizing, but fail to
        // send Blob objects via XHR requests:
        disableImageResize: /Android(?!.*Chrome)|Opera/.test(window.navigator.userAgent),
        previewMaxWidth: 100,
        previewMaxHeight: 100,
        previewCrop: true
     }).on('fileuploadadd', function (e, data) {
        data.context = $('<div/>').appendTo('#files');

        $.each(data.files, function (index, file) {
            var node = $('<p/>').append($('<span/>').text(file.name));
            if (!index) {
                node
                    .append('<br>')
                    .append(uploadButton.clone(true).data(data));
            }
            node.appendTo(data.context);
        });
    }).on('fileuploadprocessalways', function (e, data) {
        var index = data.index,
            file = data.files[index],
            node = $(data.context.children()[index]);
        if (file.preview) {
            node
                .prepend('<br>')
                .prepend(file.preview);
        }
        if (file.error) {
            node
                .append('<br>')
                .append($('<span class=\"text-danger\"/>').text(file.error));
        }
        if (index + 1 === data.files.length) {
            data.context.find('button')
                .text('Upload')
                .prop('disabled', !!data.files.error);
        }
    }).on('fileuploadprogressall', function (e, data) {
        var progress = parseInt(data.loaded / data.total * 100, 10);
        $('#progress .progress-bar').css(
            'width',
            progress + '%'
        );
    }).on('fileuploaddone', function (e, data) {

        $.each(data.result.files, function (index, file) {
            if (file.url) {
                var link = $('<a>')
                    .attr('target', '_blank')
                    .prop('href', file.url);

                $(data.context.children()[index]).wrap(link);
            } else if (file.error) {
                var error = $('<span class=\"text-danger\"/>').text(file.error);
                $(data.context.children()[index])
                    .append('<br>')
                    .append(error);
            }
        });
    }).on('fileuploadfail', function (e, data) {
        $.each(data.files, function (index) {
            var error = $('<span class=\"text-danger\"/>').text('File upload failed.');
            $(data.context.children()[index])
                .append('<br>')
                .append(error);
        });
    }).prop('disabled', !$.support.fileInput)
        .parent().addClass($.support.fileInput ? undefined : 'disabled');

});

</script>";


// This needs cleaning!
if (!empty($groupId)) {
    // If the group id is set, check if the user has the right to be here
    // Get group info
    $group_properties = GroupManager::get_group_properties($groupId);

    // Only courseadmin or group members allowed
    if ($is_allowed_to_edit || GroupManager::is_user_in_group(api_get_user_id(), $groupId)) {
        $interbreadcrumb[] = array(
            'url' => '../group/group_space.php?'.api_get_cidreq(),
            'name' => get_lang('GroupSpace'),
        );
    } else {
        api_not_allowed(true);
    }
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

// Breadcrumbs
if ($is_certificate_mode) {
    $interbreadcrumb[] = array(
        'url' => '../gradebook/index.php?'.api_get_cidreq(),
        'name' => get_lang('Gradebook'),
    );
} else {
    $interbreadcrumb[] = array(
        'url' => './document.php?id='.$document_id.'&'.api_get_cidreq(),
        'name' => get_lang('Documents'),
    );
}

// Interbreadcrumb for the current directory root path
if (empty($document_data['parents'])) {
    $interbreadcrumb[] = array('url' => '#', 'name' => $document_data['title']);
} else {
    foreach ($document_data['parents'] as $document_sub_data) {
        $interbreadcrumb[] = array(
            'url' => $document_sub_data['document_url'],
            'name' => $document_sub_data['title'],
        );
    }
}

$this_section = SECTION_COURSES;

// Display the header
Display::display_header($nameTools, 'Doc');

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
}

// Actions

// Link back to the documents overview
if ($is_certificate_mode) {
    $actions = '<a href="document.php?id='.$document_id.'&selectcat=' . $selectcat.'&'.api_get_cidreq().'">'.
        Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('CertificateOverview'),'',ICON_SIZE_MEDIUM).'</a>';
} else {
    $actions = '<a href="document.php?id='.$document_id.'&'.api_get_cidreq().'">'.
        Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('DocumentsOverview'),'',ICON_SIZE_MEDIUM).'</a>';
}

// Link to create a folder

echo $toolbar = Display::toolbarAction('toolbar-upload', array( 0 => $actions), 1);
// Form to select directory
$folders = DocumentManager::get_all_document_folders($_course, $groupId, $is_allowed_to_edit);
if (!$is_certificate_mode) {
    echo DocumentManager::build_directory_selector(
        $folders,
        $document_id,
        (isset($group_properties['directory']) ? $group_properties['directory'] : array())
    );
}

$action = api_get_self().'?'.api_get_cidreq().'&id='.$document_id;

$form = new FormValidator(
    'upload',
    'POST',
    $action.'#tabs-2',
    '',
    array('enctype' => 'multipart/form-data')
);
$form->addElement('hidden', 'id', $document_id);
$form->addElement('hidden', 'curdirpath', $path);

$course_quota = format_file_size(DocumentManager::get_course_quota() - DocumentManager::documents_total_space());
$label = get_lang('MaxFileSize').': '.ini_get('upload_max_filesize').'<br/>'.get_lang('DocumentQuota').': '.$course_quota;

$form->addElement('file', 'file', array(get_lang('File'), $label), 'style="width: 250px" id="user_upload"');
$form->addElement('text', 'title', get_lang('Title'), array('id' => 'title_file'));
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

if (api_get_setting('search_enabled') == 'true') {
    //TODO: include language file
    $supported_formats = get_lang('SupportedFormatsForIndex').': HTML, PDF, TXT, PDF, Postscript, MS Word, RTF, MS Power Point';
    $form->addElement('checkbox', 'index_document', '', get_lang('SearchFeatureDoIndexDocument').'<div style="font-size: 80%" >'.$supported_formats.'</div>');
    $form->addElement('html', '<br /><div class="sub-form">');
    $form->addElement('html', '<div class="label">'.get_lang('SearchFeatureDocumentLanguage').'</div>');
    $form->addElement('html', '<div>' . api_get_languages_combo(null) . '</div>');
    $form->addElement('html', '</div><div class="sub-form">');
    $specific_fields = get_specific_field_list();
    foreach ($specific_fields as $specific_field) {
        $form->addElement('text', $specific_field['code'], $specific_field['name'].' : ');
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
$form->add_real_progress_bar('DocumentUpload', 'file');

$fileExistsOption = api_get_setting('document_if_file_exists_option');

$defaultFileExistsOption = 'rename';
if (!empty($fileExistsOption)) {
    $defaultFileExistsOption = $fileExistsOption;
}

$defaults = array(
    'index_document' => 'checked="checked"',
    'if_exists' => $defaultFileExistsOption
);

$form->setDefaults($defaults);

$simple_form = $form->returnForm();

$multiple_form = '<div class="description-upload">'.get_lang('ClickToSelectOrDragAndDropMultipleFilesOnTheUploadField').'</div>';
$multiple_form .=  '
<span class="btn btn-success fileinput-button">
    <i class="glyphicon glyphicon-plus"></i>
    <span>'.get_lang('AddFiles').'</span>
    <!-- The file input field used as target for the file upload widget -->
    <input id="file_upload" type="file" name="files[]" multiple>
</span>

<br />
<br />
<!-- The global progress bar -->
<div id="progress" class="progress">
    <div class="progress-bar progress-bar-success"></div>
</div>
<div id="files" class="files"></div>
';

$nav_info = api_get_navigator();
if ($nav_info ['name'] == 'Internet Explorer') {
    echo $simple_form;
} else {
    $headers = array(
        get_lang('Upload'),
        get_lang('Upload').' ('.get_lang('Simple').')',
    );
    echo Display::tabs($headers, array($multiple_form, $simple_form), 'tabs');
}

Display::display_footer();

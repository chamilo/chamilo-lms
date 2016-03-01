<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_STUDENTPUBLICATION;

api_protect_course_script(true);

// Including necessary files
require_once 'work.lib.php';

$this_section = SECTION_COURSES;

$work_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : null;

$is_allowed_to_edit = api_is_allowed_to_edit();
$course_id = api_get_course_int_id();
$user_id = api_get_user_id();
$userInfo = api_get_user_info();
$session_id = api_get_session_id();
$course_info = api_get_course_info();
$course_code = $course_info['code'];
$group_id = api_get_group_id();

if (empty($work_id)) {
    api_not_allowed(true);
}

protectWork($course_info, $work_id);

$workInfo = get_work_data_by_id($work_id);

$is_course_member = CourseManager::is_user_subscribed_in_real_or_linked_course(
    $user_id,
    $course_id,
    $session_id
);
$is_course_member = $is_course_member || api_is_platform_admin();

if ($is_course_member == false || api_is_invitee()) {
    api_not_allowed(true);
}

$check = Security::check_token('post');
$token = Security::get_token();

$student_can_edit_in_session = api_is_allowed_to_session_edit(false, true);

//  @todo add an option to allow/block multiple attempts.
/*
if (!empty($workInfo) && !empty($workInfo['qualification'])) {
    $count =  get_work_count_by_student($user_id, $work_id);
    if ($count >= 1) {
        Display::display_header();
        if (api_get_course_setting('student_delete_own_publication') == '1') {
            Display::display_warning_message(get_lang('CantUploadDeleteYourPaperFirst'));
        } else {
            Display::display_warning_message(get_lang('YouAlreadySentAPaperYouCantUpload'));
        }
        Display::display_footer();
        exit;
    }
}*/

$homework = get_work_assignment_by_id($workInfo['id']);
$validationStatus = getWorkDateValidationStatus($homework);

$interbreadcrumb[] = array(
    'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
    'name' => get_lang('StudentPublications'),
);
$interbreadcrumb[] = array(
    'url' => api_get_path(WEB_CODE_PATH).'work/work_list.php?'.api_get_cidreq().'&id='.$work_id,
    'name' => $workInfo['title'],
);
$interbreadcrumb[] = array('url' => '#', 'name' => get_lang('UploadADocument'));

$form = new FormValidator(
    'form',
    'POST',
    api_get_self()."?".api_get_cidreq()."&id=".$work_id,
    '',
    array('enctype' => "multipart/form-data")
);

setWorkUploadForm($form, $workInfo['allow_text_assignment']);

$form->addElement('hidden', 'id', $work_id);
$form->addElement('hidden', 'sec_token', $token);

$error_message = null;

$succeed = false;
if ($form->validate()) {

    if ($student_can_edit_in_session && $check) {
        $values = $form->getSubmitValues();
        // Process work
        processWorkForm(
            $workInfo,
            $values,
            $course_info,
            $session_id,
            $group_id,
            $user_id
        );
        $script = 'work_list.php';
        if ($is_allowed_to_edit) {
            $script = 'work_list_all.php';
        }
        header('Location: '.api_get_path(WEB_CODE_PATH).'work/'.$script.'?'.api_get_cidreq().'&id='.$work_id);
        exit;
    } else {
        // Bad token or can't add works
        $error_message = Display::return_message(get_lang('IsNotPosibleSaveTheDocument'), 'error');
    }
}

$url = api_get_path(WEB_AJAX_PATH).'work.ajax.php?'.api_get_cidreq().'&a=upload_file&id='.$work_id;

$htmlHeadXtra[] = api_get_jquery_libraries_js(array('jquery-ui', 'jquery-upload'));
$htmlHeadXtra[] = to_javascript_work();
$htmlHeadXtra[] = "<script>
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

Display :: display_header(null);

$headers = array(
    get_lang('Upload'),
    get_lang('Upload').' ('.get_lang('Simple').')',
);

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

$tabs = Display::tabs($headers, array($multiple_form, $form->returnForm()), 'tabs');

if (!empty($work_id)) {
    echo $validationStatus['message'];
    if ($is_allowed_to_edit) {
        if (api_resource_is_locked_by_gradebook($work_id, LINK_STUDENTPUBLICATION)) {
            echo Display::display_warning_message(get_lang('ResourceLockedByGradebook'));
        } else {
            echo $tabs;
        }
    } elseif ($student_can_edit_in_session && $validationStatus['has_ended'] == false) {
        echo $tabs;
    } else {
        Display::display_error_message(get_lang('ActionNotAllowed'));
    }
} else {
    Display::display_error_message(get_lang('ActionNotAllowed'));
}

Display :: display_footer();

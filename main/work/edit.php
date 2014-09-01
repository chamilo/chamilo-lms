<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

$language_file = array('exercice', 'work', 'document', 'admin', 'gradebook');

require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_STUDENTPUBLICATION;

api_protect_course_script(true);

// Including files
require_once 'work.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileDisplay.lib.php';

$this_section = SECTION_COURSES;

$work_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : null;
$item_id = isset($_REQUEST['item_id']) ? intval($_REQUEST['item_id']) : null;

$work_table = Database :: get_course_table(TABLE_STUDENT_PUBLICATION);

$is_allowed_to_edit = api_is_allowed_to_edit();
$course_id = api_get_course_int_id();
$user_id = api_get_user_id();
$session_id = api_get_session_id();
$course_code = api_get_course_id();
$course_info = api_get_course_info();

if (empty($work_id) || empty($item_id)) {
    api_not_allowed(true);
}

$parent_data = $my_folder_data = get_work_data_by_id($work_id);

if (empty($parent_data)) {
    api_not_allowed(true);
}

$is_course_member = CourseManager::is_user_subscribed_in_real_or_linked_course($user_id, $course_code, $session_id);
$is_course_member = $is_course_member || api_is_platform_admin();

if ($is_course_member == false) {
    api_not_allowed(true);
}

$check = Security::check_token('post');
$token = Security::get_token();

$student_can_edit_in_session = api_is_allowed_to_session_edit(false, true);
$has_ended   = false;
$is_author = false;
$work_item = get_work_data_by_id($item_id);

// Get the author ID for that document from the item_property table
$is_author = user_is_author($item_id);

if (!$is_author) {
    api_not_allowed(true);
}

// Student's can't edit work only if he can delete his docs.
if (!api_is_allowed_to_edit()) {
    if (api_get_course_setting('student_delete_own_publication') != 1) {
        api_not_allowed(true);
    }
}

if (!empty($my_folder_data)) {
    $homework = get_work_assignment_by_id($my_folder_data['id']);

    if ($homework['expires_on'] != '0000-00-00 00:00:00' || $homework['ends_on'] != '0000-00-00 00:00:00') {
        $time_now = time();

        if (!empty($homework['expires_on']) && $homework['expires_on'] != '0000-00-00 00:00:00') {
            $time_expires 	= api_strtotime($homework['expires_on'], 'UTC');
            $difference 	= $time_expires - $time_now;
            if ($difference < 0) {
                $has_expired = true;
            }
        }

        if (empty($homework['expires_on']) || $homework['expires_on'] == '0000-00-00 00:00:00') {
            $has_expired = false;
        }

        if (!empty($homework['ends_on']) && $homework['ends_on'] != '0000-00-00 00:00:00') {
            $time_ends 		= api_strtotime($homework['ends_on'], 'UTC');
            $difference2 	= $time_ends - $time_now;
            if ($difference2 < 0) {
                $has_ended = true;
            }
        }

        $ends_on 	= api_convert_and_format_date($homework['ends_on']);
        $expires_on = api_convert_and_format_date($homework['expires_on']);
    }
}

$interbreadcrumb[] = array(
    'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
    'name' => get_lang('StudentPublications')
);

if (api_is_allowed_to_edit()) {
    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq().'&id='.$work_id,
        'name' =>  $parent_data['title']
    );
} else {
    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'work/work_list.php?'.api_get_cidreq().'&id='.$work_id,
        'name' =>  $parent_data['title']
    );
}

// form title
$form_title = get_lang('Edit');

$interbreadcrumb[] = array('url' => '#', 'name'  => $form_title);

$form = new FormValidator(
    'form',
    'POST',
    api_get_self()."?".api_get_cidreq()."&id=".$work_id,
    '',
    array('enctype' => "multipart/form-data")
);
$form->addElement('header', $form_title);

$show_progress_bar = false;
/*
if ($submitGroupWorkUrl) {
    // For user coming from group space to publish his work
    $realUrl = str_replace($_configuration['root_sys'], api_get_path(WEB_PATH), str_replace("\\", '/', realpath($submitGroupWorkUrl)));
    $form->addElement('hidden', 'newWorkUrl', $submitGroupWorkUrl);
    $text_document = $form->addElement('text', 'document', get_lang('Document'));
    $defaults['document'] = '<a href="' . format_url($submitGroupWorkUrl) . '">' . $realUrl . '</a>';
    $text_document->freeze();
} elseif ($item_id && ($is_allowed_to_edit or $is_author)) {
    $workUrl = $currentCourseRepositoryWeb . $workUrl;
}*/

$form->addElement('hidden', 'id', $work_id);
$form->addElement('hidden', 'item_id', $item_id);
$form->addElement('text', 'title', get_lang('Title'), array('id' => 'file_upload', 'class' => 'span4'));
if ($is_allowed_to_edit && !empty($item_id)) {
    $sql = "SELECT contains_file, url FROM $work_table WHERE c_id = $course_id AND id ='$item_id' ";
    $result = Database::query($sql);
    if ($result !== false && Database::num_rows($result) > 0) {
        $row = Database::fetch_array($result);
        if ($row['contains_file'] || !empty($row['url'])) {
            $form->addElement('html', '<div class="control-group"><label class="control-label">'.get_lang('Download').'</label><div class="controls"><a href="'.api_get_path(WEB_CODE_PATH).'work/download.php?id='.$item_id.'&'.api_get_cidreq().'">'.Display::return_icon('save.png', get_lang('Save'),array(), ICON_SIZE_MEDIUM).'</a></div></div>');
        }
    }
}
$form->add_html_editor('description', get_lang('Description'), false, false, getWorkDescriptionToolbar());

$defaults['title'] 			= $work_item['title'];
$defaults["description"] 	= $work_item['description'];
$defaults['qualification']  = $work_item['qualification'];

if ($is_allowed_to_edit && !empty($item_id)) {
    // Get qualification from parent_id that will allow the validation qualification over
    $sql = "SELECT qualification FROM $work_table WHERE c_id = $course_id AND id ='$work_id' ";
    $result = Database::query($sql);
    $row = Database::fetch_array($result);
    $qualification_over = $row['qualification'];
    if (!empty($qualification_over) && intval($qualification_over) > 0) {
        $form->addElement('text', 'qualification', array(get_lang('Qualification'), null, " / ".$qualification_over), 'size="10"');
        $form->addElement('hidden', 'qualification_over', $qualification_over);
    }
}

$form->addElement('hidden', 'active', 1);
$form->addElement('hidden', 'accepted', 1);
$form->addElement('hidden', 'item_to_edit', $item_id);
$form->addElement('hidden', 'sec_token', $token);

$text = get_lang('UpdateWork');
$class = 'save';

// fix the Ok button when we see the tool in the learn path
$form->addElement('style_submit_button', 'editWork', $text, array('class'=> $class, 'value' => "editWork"));

$form->setDefaults($defaults);
$error_message = null;
$_course = api_get_course_info();
$currentCourseRepositorySys = api_get_path(SYS_COURSE_PATH).$_course['path'] . '/';

$succeed = false;
if ($form->validate()) {
    if ($student_can_edit_in_session && $check) {

        if (isset($_POST['editWork'])) {
            /*
             * SPECIAL CASE ! For a work edited
            */
            //Get the author ID for that document from the item_property table
            $item_to_edit_id 	= intval($_POST['item_to_edit']);
            $is_author 			= user_is_author($item_to_edit_id);

            if ($is_author) {
                $work_data = get_work_data_by_id($item_to_edit_id);

                if (!empty($_POST['title'])) {
                    $title = isset($_POST['title']) ? $_POST['title'] : $work_data['title'];
                }
                $description = isset($_POST['description']) ? $_POST['description'] : $work_data['description'];

                if ($is_allowed_to_edit && ($_POST['qualification'] !='' )) {
                    $add_to_update = ', qualificator_id ='."'".api_get_user_id()."', ";
                    $add_to_update .= ' qualification = '."'".Database::escape_string($_POST['qualification'])."',";
                    $add_to_update .= ' date_of_qualification = '."'".api_get_utc_datetime()."'";
                }

                if ($_POST['qualification'] > $_POST['qualification_over']) {
                    $error_message .= Display::return_message(get_lang('QualificationMustNotBeMoreThanQualificationOver'), 'error');
                } else {
                    $sql = "UPDATE  " . $work_table . "
                            SET	title = '".Database::escape_string($title)."',
                                description = '".Database::escape_string($description)."'
                                ".$add_to_update."
                            WHERE c_id = $course_id AND id = $item_to_edit_id";
                    Database::query($sql);
                }
                api_item_property_update($_course, 'work', $item_to_edit_id, 'DocumentUpdated', $user_id);

                $succeed = true;
                $error_message .= Display::return_message(get_lang('ItemUpdated'), 'warning');
            } else {
                $error_message .= Display::return_message(get_lang('IsNotPosibleSaveTheDocument'), 'error');
            }
        } else {
            $error_message .= Display::return_message(get_lang('IsNotPosibleSaveTheDocument'), 'error');
        }
        Security::clear_token();
    } else {
        // Bad token or can't add works
        $error_message = Display::return_message(get_lang('IsNotPosibleSaveTheDocument'), 'error');
    }

    if (!empty($error_message)) {
        Session::write('error_message', $error_message);
    }

    $script = 'work_list.php';
    if ($is_allowed_to_edit) {
        $script = 'work_list_all.php';
    }
    header('Location: '.api_get_path(WEB_CODE_PATH).'work/'.$script.'?'.api_get_cidreq().'&id='.$work_id);
    exit;
}

$htmlHeadXtra[] = to_javascript_work();

$tpl = new Template();
$content = null;
if (!empty($work_id)) {
    if ($is_allowed_to_edit) {
        if (api_resource_is_locked_by_gradebook($work_id, LINK_STUDENTPUBLICATION)) {
            echo Display::display_warning_message(get_lang('ResourceLockedByGradebook'));
        } else {

            $comments = getWorkComments($my_folder_data);

            $template = $tpl->get_template('work/comments.tpl');
            $tpl->assign('work_comment_enabled', ALLOW_USER_COMMENTS);
            $tpl->assign('comments', $comments);

            $content .= $form->return_form();
            $content  .= $tpl->fetch($template);
        }
    } elseif ($is_author) {
        if (empty($work_item['qualificator_id']) || $work_item['qualificator_id'] == 0) {
            $content .= $form->return_form();
        } else {
            $content .= Display::return_message(get_lang('ActionNotAllowed'), 'error');
        }
    } elseif ($student_can_edit_in_session && $has_ended == false) {
        $content .= $form->return_form();
    } else {
        $content .= Display::return_message(get_lang('ActionNotAllowed'), 'error');
    }
} else {
    $content .= Display::return_message(get_lang('ActionNotAllowed'), 'error');
}

$tpl->assign('content', $content);
$tpl->display_one_col_template();

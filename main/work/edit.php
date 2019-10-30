<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_STUDENTPUBLICATION;

api_protect_course_script(true);

$blockEdition = api_get_configuration_value('block_student_publication_edition');

if ($blockEdition && !api_is_platform_admin()) {
    api_not_allowed(true);
}

// Including files
require_once 'work.lib.php';

$this_section = SECTION_COURSES;

$work_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : null;
$item_id = isset($_REQUEST['item_id']) ? intval($_REQUEST['item_id']) : null;
$work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

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

$is_course_member = CourseManager::is_user_subscribed_in_real_or_linked_course(
    $user_id,
    $course_id,
    $session_id
);

$is_course_member = $is_course_member || api_is_platform_admin();

if ($is_course_member == false) {
    api_not_allowed(true);
}

$check = Security::check_token('post');
$token = Security::get_token();

$student_can_edit_in_session = api_is_allowed_to_session_edit(false, true);
$has_ended = false;
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

    if (!empty($homework['expires_on']) || !empty($homework['ends_on'])) {
        $time_now = time();

        if (!empty($homework['expires_on']) &&
            !empty($homework['expires_on'])
        ) {
            $time_expires = api_strtotime($homework['expires_on'], 'UTC');
            $difference = $time_expires - $time_now;
            if ($difference < 0) {
                $has_expired = true;
            }
        }

        if (empty($homework['expires_on'])) {
            $has_expired = false;
        }

        if (!empty($homework['ends_on'])) {
            $time_ends = api_strtotime($homework['ends_on'], 'UTC');
            $difference2 = $time_ends - $time_now;
            if ($difference2 < 0) {
                $has_ended = true;
            }
        }

        $ends_on = api_convert_and_format_date($homework['ends_on']);
        $expires_on = api_convert_and_format_date($homework['expires_on']);
    }
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
    'name' => get_lang('Assignments'),
];

if (api_is_allowed_to_edit()) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq().'&id='.$work_id,
        'name' => $parent_data['title'],
    ];
} else {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'work/work_list.php?'.api_get_cidreq().'&id='.$work_id,
        'name' => $parent_data['title'],
    ];
}

// form title
$form_title = get_lang('Edit');

$interbreadcrumb[] = ['url' => '#', 'name' => $form_title];

$form = new FormValidator(
    'form',
    'POST',
    api_get_self()."?".api_get_cidreq()."&id=".$work_id,
    '',
    ['enctype' => "multipart/form-data"]
);
$form->addElement('header', $form_title);
$show_progress_bar = false;
$form->addElement('hidden', 'id', $work_id);
$form->addElement('hidden', 'item_id', $item_id);
$form->addText('title', get_lang('Title'), true, ['id' => 'file_upload']);
if ($is_allowed_to_edit && !empty($item_id)) {
    $sql = "SELECT contains_file, url
            FROM $work_table
            WHERE c_id = $course_id AND id ='$item_id' ";
    $result = Database::query($sql);
    if ($result !== false && Database::num_rows($result) > 0) {
        $row = Database::fetch_array($result);
        if ($row['contains_file'] || !empty($row['url'])) {
            $form->addLabel(
                get_lang('Download'),
                '<a href="'.api_get_path(WEB_CODE_PATH).'work/download.php?id='.$item_id.'&'.api_get_cidreq().'">'.
                    Display::return_icon('save.png', get_lang('Save'), [], ICON_SIZE_MEDIUM).'
                </a>'
            );
        }
    }
}
$form->addHtmlEditor(
    'description',
    get_lang('Description'),
    false,
    false,
    getWorkDescriptionToolbar()
);

$defaults['title'] = $work_item['title'];
$defaults["description"] = $work_item['description'];
$defaults['qualification'] = $work_item['qualification'];

if ($is_allowed_to_edit && !empty($item_id)) {
    // Get qualification from parent_id that will allow the validation qualification over
    /*$sql = "SELECT qualification FROM $work_table
            WHERE c_id = $course_id AND id ='$work_id' ";
    $result = Database::query($sql);
    $row = Database::fetch_array($result);
    $qualification_over = $row['qualification'];
    if (!empty($qualification_over) && intval($qualification_over) > 0) {
        $form->addText('qualification', array(get_lang('Score'), " / ".$qualification_over), false, 'size="10"');
        $form->addElement('hidden', 'qualification_over', $qualification_over);
    }*/

    $form->addCheckBox(
        'send_email',
        null,
        get_lang('Send mail to student')
    );

    // Check if user to qualify has some DRHs
    $drhList = UserManager::getDrhListFromUser($work_item['user_id']);
    if (!empty($drhList)) {
        $form->addCheckBox(
            'send_to_drh_users',
            null,
            get_lang('Send mail to HR manager')
        );
    }
}

$form->addElement('hidden', 'active', 1);
$form->addElement('hidden', 'accepted', 1);
$form->addElement('hidden', 'item_to_edit', $item_id);
$form->addElement('hidden', 'sec_token', $token);

$text = get_lang('Update this task');
$class = 'save';

// fix the Ok button when we see the tool in the learn path
$form->addButtonUpdate($text);

$form->setDefaults($defaults);
$_course = api_get_course_info();
$currentCourseRepositorySys = api_get_path(SYS_COURSE_PATH).$_course['path'].'/';

$succeed = false;
if ($form->validate()) {
    if ($student_can_edit_in_session && $check) {
        /*
         * SPECIAL CASE ! For a work edited
        */
        //Get the author ID for that document from the item_property table
        $item_to_edit_id = intval($_POST['item_to_edit']);
        $is_author = user_is_author($item_to_edit_id);

        if ($is_author) {
            $work_data = get_work_data_by_id($item_to_edit_id);

            if (!empty($_POST['title'])) {
                $title = isset($_POST['title']) ? $_POST['title'] : $work_data['title'];
            }
            $description = isset($_POST['description']) ? $_POST['description'] : $work_data['description'];

            $add_to_update = null;
            if ($is_allowed_to_edit && ($_POST['qualification'] != '')) {
                /*$add_to_update = ', qualificator_id ='."'".api_get_user_id()."', ";
                $add_to_update .= ' qualification = '."'".api_float_val($_POST['qualification'])."',";
                $add_to_update .= ' date_of_qualification = '."'".api_get_utc_datetime()."'";*/

                if (isset($_POST['send_email'])) {
                    $url = api_get_path(WEB_CODE_PATH).'work/view.php?'.api_get_cidreq().'&id='.$item_to_edit_id;
                    $subject = sprintf(get_lang('There\'s a new feedback in work: %s'), $work_item['title']);
                    $message = sprintf(get_lang('There\'s a new feedback in work: %sInWorkXHere'), $work_item['title'], $url);

                    MessageManager::send_message_simple(
                        $work_item['user_id'],
                        $subject,
                        $message,
                        api_get_user_id(),
                        isset($_POST['send_to_drh_users'])
                    );
                }
            }

            if ($_POST['qualification'] > $_POST['qualification_over']) {
                Display::addFlash(Display::return_message(
                    get_lang('ScoreMustNotBeMoreThanScoreOver'),
                    'error'
                ));
            } else {
                $sql = "UPDATE  ".$work_table."
                        SET	title = '".Database::escape_string($title)."',
                            description = '".Database::escape_string($description)."'
                            ".$add_to_update."
                        WHERE c_id = $course_id AND id = $item_to_edit_id";
                Database::query($sql);
            }

            api_item_property_update(
                $_course,
                'work',
                $item_to_edit_id,
                'DocumentUpdated',
                $user_id
            );

            $succeed = true;
            Display::addFlash(Display::return_message(get_lang('Item updated')));
        }
        Security::clear_token();
    } else {
        // Bad token or can't add works
        Display::addFlash(Display::return_message(get_lang('Impossible to save the document'), 'error'));
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
            echo Display::return_message(get_lang('This option is not available because this activity is contained by an assessment, which is currently locked. To unlock the assessment, ask your platform administrator.'), 'warning');
        } else {
            $content .= $form->returnForm();
        }
    } elseif ($is_author) {
        if (empty($work_item['qualificator_id']) || $work_item['qualificator_id'] == 0) {
            $content .= $form->returnForm();
        } else {
            $content .= Display::return_message(get_lang('Action not allowed'), 'error');
        }
    } elseif ($student_can_edit_in_session && $has_ended == false) {
        $content .= $form->returnForm();
    } else {
        $content .= Display::return_message(get_lang('Action not allowed'), 'error');
    }
} else {
    $content .= Display::return_message(get_lang('Action not allowed'), 'error');
}

$tpl->assign('content', $content);
$tpl->display_one_col_template();

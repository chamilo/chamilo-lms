<?php
/* For licensing terms, see /license.txt */

/**
 * A page for detailed preview or edition of a given course request.
 * @package chamilo.admin
 * @author Ivan Tcholakov <ivantcholakov@gmail.com>, 2010
 */

// Initialization section.

// Language files that need to be included.
$language_file = array('admin', 'create_course');

$cidReset = true;
require '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;
$tool_name = get_lang('CourseRequestEdit');

api_protect_admin_script();

require_once api_get_path(LIBRARY_PATH).'add_course.lib.inc.php';
require_once api_get_path(CONFIGURATION_PATH).'course_info.conf.php';
require_once api_get_path(LIBRARY_PATH).'course_request.lib.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';

// Including a configuration file.
require_once api_get_path(CONFIGURATION_PATH).'add_course.conf.php';

// Including additional libraries.
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';

// A check whether the course validation feature is enabled.
$course_validation_feature = api_get_setting('course_validation') == 'true';

// Filltering passed to this page parameters.
$id = intval($_GET['id']);
$caller = intval($_GET['caller']);

if ($course_validation_feature) {

    // Retrieve request's data from the corresponding database record.
    $course_request_info = CourseRequestManager::get_course_request_info($id);
    if (!is_array($course_request_info)) {
        // Prepare an error message notifying that the course request has not been found or does not exist.
        $message = get_lang('CourseRequestHasNotBeenFound');
        $is_error_message = true;
    } else {

        global $_configuration;
        $dbnamelength = strlen($_configuration['db_prefix']);
        // Ensure the database prefix + database name do not get over 40 characters.
        $maxlength = 40 - $dbnamelength;

        // Build the form.
        $form = new FormValidator('add_course', 'post', 'course_request_edit.php?id='.$id.'&caller='.$caller);

        // Form title.
        $form->addElement('header', '', $tool_name);

        // Title.
        $form->addElement('text', 'title', get_lang('CourseName'), array('size' => '60', 'id' => 'title'));
        $form->applyFilter('title', 'html_filter');
        $form->addRule('title', get_lang('ThisFieldIsRequired'), 'required');

        // Course category.
        $categories_select = $form->addElement('select', 'category_code', get_lang('Fac'), array());
        $form->applyFilter('category_code', 'html_filter');
        CourseManager::select_and_sort_categories($categories_select);

        // Course code.
        $form->add_textfield('wanted_code', get_lang('Code'), false, array('size' => '$maxlength', 'maxlength' => $maxlength));
        $form->applyFilter('wanted_code', 'html_filter');
        $form->addRule('wanted_code', get_lang('Max'), 'maxlength', $maxlength);
        $form->addRule('wanted_code', get_lang('ThisFieldIsRequired'), 'required');

        // The teacher.
        $titular = & $form->add_textfield('tutor_name', get_lang('Professor'), null, array('size' => '60', 'disabled' => 'disabled'));
        //$form->applyFilter('tutor_name', 'html_filter');

        // Description of the requested course.
        $form->addElement('textarea', 'description', get_lang('Description'), array('style' => 'border:#A5ACB2 solid 1px; font-family:arial,verdana,helvetica,sans-serif; font-size:12px', 'rows' => '3', 'cols' => '116'));
        $form->addRule('description', get_lang('ThisFieldIsRequired'), 'required');

        // Objectives of the requested course.
        $form->addElement('textarea', 'objetives', get_lang('Objectives'), array('style' => 'border:#A5ACB2 solid 1px; font-family:arial,verdana,helvetica,sans-serif; font-size:12px', 'rows' => '3', 'cols' => '116'));
        $form->addRule('objetives', get_lang('ThisFieldIsRequired'), 'required');

        // Target audience of the requested course.
        $form->addElement('textarea', 'target_audience', get_lang('TargetAudience'), array('style' => 'border:#A5ACB2 solid 1px; font-family:arial,verdana,helvetica,sans-serif; font-size:12px', 'rows' => '3', 'cols' => '116'));
        $form->addRule('target_audience', get_lang('ThisFieldIsRequired'), 'required');

        // Course language.
        $form->addElement('select_language', 'course_language', get_lang('Ln'));
        $form->applyFilter('select_language', 'html_filter');

        // Exemplary content checkbox.
        $form->addElement('checkbox', 'exemplary_content', get_lang('FillWithExemplaryContent'));

        // Submit buttons.
        $submit_buttons[] = FormValidator::createElement('style_submit_button', 'save_button', get_lang('Save'), array('class' => 'save'));
        if ($course_request_info['status'] != COURSE_REQUEST_ACCEPTED) {
            $submit_buttons[] = FormValidator::createElement('style_submit_button', 'accept_button', get_lang('Accept'), array('class' => 'save', 'style' => 'background-image: url('.api_get_path(WEB_IMG_PATH).'icons/16/accept.png);'));
        }
        if ($course_request_info['status'] != COURSE_REQUEST_ACCEPTED && $course_request_info['status'] != COURSE_REQUEST_REJECTED) {
            $submit_buttons[] = FormValidator::createElement('style_submit_button', 'reject_button', get_lang('Reject'), array('class' => 'save', 'style' => 'background-image: url('.api_get_path(WEB_IMG_PATH).'icons/16/error.png);'));
        }
        if ($course_request_info['status'] != COURSE_REQUEST_ACCEPTED && intval($course_request_info['info']) <= 0) {
            $submit_buttons[] = FormValidator::createElement('style_submit_button', 'ask_info_button', get_lang('AskAdditionalInfo'), array('class' => 'save', 'style' => 'background-image: url('.api_get_path(WEB_IMG_PATH).'request_info.gif);'));
        }
        $form->addGroup($submit_buttons);

        // Hidden form fields.
        $form->addElement('hidden', 'user_id');
        $form->addElement('hidden', 'directory');
        $form->addElement('hidden', 'db_name');
        $form->addElement('hidden', 'visual_code');
        $form->addElement('hidden', 'request_date');
        $form->addElement('hidden', 'status');
        $form->addElement('hidden', 'info');

        // Set the default values based on the corresponding database record.
        $values['wanted_code'] = $course_request_info['code'];
        $values['user_id'] = $course_request_info['user_id'];
        $values['directory'] = $course_request_info['directory'];
        $values['db_name'] = $course_request_info['db_name'];
        $values['course_language'] = $course_request_info['course_language'];
        $values['title'] = $course_request_info['title'];
        $values['description'] = $course_request_info['description'];
        $values['category_code'] = $course_request_info['category_code'];
        $values['tutor_name'] = $course_request_info['tutor_name'];
        $values['visual_code'] = $course_request_info['visual_code'];
        $values['request_date'] = $course_request_info['request_date'];
        $values['objetives'] = $course_request_info['objetives'];
        $values['target_audience'] = $course_request_info['target_audience'];
        $values['status'] = $course_request_info['status'];
        $values['info'] = $course_request_info['info'];
        $values['exemplary_content'] = $course_request_info['exemplary_content'];
        $form->setDefaults($values);

        // Validate the form and perform the ordered actions.
        if ($form->validate()) {
            $course_request_values = $form->exportValues();

            // Filter incoming data.
            foreach ($course_request_values as &$value) {
                $value = trim(Security::remove_XSS(stripslashes($value)));
            }

            // Detection which submit button has been pressed.
            $submit_button = isset($_POST['save_button']) ? 'save_button'
                : (isset($_POST['accept_button']) ? 'accept_button'
                : (isset($_POST['reject_button']) ? 'reject_button'
                : (isset($_POST['ask_info_button']) ? 'ask_info_button'
                : 'submit_button')));

            // Check the course code for avoiding duplication.
            $course_code_ok = $course_request_values['wanted_code'] == $course_request_info['code']
                ? true
                : !CourseRequestManager::course_code_exists($course_request_values['wanted_code']);

            if ($course_code_ok) {
                $message = array();
                $is_error_message = false;

                // Update the course request.
                $update_ok = CourseRequestManager::update_course_request($id,
                    $course_request_values['wanted_code'],
                    $course_request_values['title'],
                    $course_request_values['description'],
                    $course_request_values['category_code'],
                    $course_request_values['course_language'],
                    $course_request_values['objetives'],
                    $course_request_values['target_audience'],
                    $course_request_values['user_id'],
                    $course_request_values['exemplary_content']
                );

                if ($update_ok) {
                    $message[] = sprintf(get_lang(CourseRequestUpdated), $course_request_values['wanted_code']);

                    switch ($submit_button) {
                        case 'accept_button':
                            if (CourseRequestManager::accept_course_request($id)) {
                                $message[] = sprintf(get_lang('CourseRequestAccepted'), $course_request_values['wanted_code'], $course_request_values['wanted_code']);
                            } else {
                                $message[] = sprintf(get_lang('CourseRequestAcceptanceFailed'), $course_request_values['wanted_code']);
                                $is_error_message = true;
                            }
                            break;
                        case 'reject_button':
                            if (CourseRequestManager::reject_course_request($id)) {
                                $message[] = sprintf(get_lang('CourseRequestRejected'), $course_request_values['wanted_code']);
                            } else {
                                $message[] = sprintf(get_lang('CourseRequestRejectionFailed'), $course_request_values['wanted_code']);
                                $is_error_message = true;
                            }
                            break;
                        case 'ask_info_button':
                            if (CourseRequestManager::ask_for_additional_info($id)) {
                                $message[] = sprintf(get_lang('CourseRequestInfoAsked'), $course_request_values['wanted_code']);
                            } else {
                                $message[] = sprintf(get_lang('CourseRequestInfoFailed'), $course_request_values['wanted_code']);
                                $is_error_message = true;
                            }
                            break;
                    }
                    // Line of code for testing purposes, to be removed
                    //$message = 'The button "'.$submit_button.'" has been pressed.';

                } else {
                    $message[] = sprintf(get_lang(CourseRequestUpdateFailed), $course_request_values['wanted_code']);
                    $is_error_message = true;
                }

                $message = implode(' ', $message);

                $back_url = get_caller_name($caller);
                if ($message != '') {
                    $back_url = api_add_url_param($back_url, 'message='.urlencode(Security::remove_XSS($message)), false);
                }
                if ($is_error_message) {
                    $back_url = api_add_url_param($back_url, 'is_error_message=1', false);
                }
                header('location:'.$back_url);

            } else {

                $message = $course_request_values['wanted_code'].' - '.get_lang('CourseCodeAlreadyExists');
                $is_error_message = true;
            }
        }
    }

} else {

    // Prepare an error message notifying that the course validation feature has not been enabled.
    $link_to_setting = api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Platform#course_validation';
    $message = sprintf(get_lang('PleaseActivateCourseValidationFeature'), sprintf('<strong><a href="%s">%s</a></strong>', $link_to_setting, get_lang('EnableCourseValidation')));
    $is_error_message = true;

}

// Functions.

// Converts the given numerical id to the name of the page that opened this editor.
function get_caller_name($caller_id) {
    switch ($caller_id) {
        case 1:
            return 'course_request_accepted.php';
        case 2:
            return 'course_request_rejected.php';
    }
    return 'course_request_review.php';
}

// The header.
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'course_list.php', 'name' => get_lang('CourseList'));

Display :: display_header($tool_name);

// Display confirmation or error message.
if (!empty($message)) {
    if ($is_error_message) {
        Display::display_error_message($message, false);
    } else {
        Display::display_normal_message($message, false);
    }
}

if (!$course_validation_feature) {
    // Disabled course validation feature - show nothing after the error message.
    Display :: display_footer();
    exit;
}

// The action bar.
echo '<div class="actions">';
echo '<a href="course_list.php">'.Display::return_icon('courses.gif', get_lang('CourseList')).get_lang('CourseList').'</a>';
echo '<a href="course_request_review.php">'.Display::return_icon('course_request_pending.png', get_lang('ReviewCourseRequests')).get_lang('ReviewCourseRequests').'</a>';
echo '<a href="course_request_accepted.php">'.Display::return_icon('course_request_accepted.gif', get_lang('AcceptedCourseRequests')).get_lang('AcceptedCourseRequests').'</a>';
echo '<a href="course_request_rejected.php">'.Display::return_icon('course_request_rejected.gif', get_lang('RejectedCourseRequests')).get_lang('RejectedCourseRequests').'</a>';
echo '</div>';

if (!is_array($course_request_info)) {
    // Not accessible database record - show the error message and the action bar.
    Display :: display_footer();
    exit;
}

// Display the form.
$form->display();

// The footer.
Display :: display_footer();

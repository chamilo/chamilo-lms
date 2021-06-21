<?php

/* For licensing terms, see /license.txt */

/**
 * A page for detailed preview or edition of a given course request.
 *
 * @author Ivan Tcholakov <ivantcholakov@gmail.com>, 2010
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;
$tool_name = get_lang('Edit a course request');

api_protect_admin_script();

// A check whether the course validation feature is enabled.
$course_validation_feature = 'true' === api_get_setting('course_validation');

// Filtering passed to this page parameters.
$id = (int) ($_GET['id']);
$caller = (int) ($_GET['caller']);

if ($course_validation_feature) {
    // Retrieve request's data from the corresponding database record.
    $course_request_info = CourseRequestManager::get_course_request_info($id);
    if (!is_array($course_request_info)) {
        // Prepare an error message notifying that the course request has not been found or does not exist.
        Display::addFlash(
            Display::return_message(
                get_lang('The course request you wanted to access has not been found or it does not exist.'),
                'warning',
                false
            )
        );
    } else {
        // Ensure the database prefix + database name do not get over 40 characters.
        $maxlength = 40;

        // Build the form.
        $form = new FormValidator(
            'add_course',
            'post',
            'course_request_edit.php?id='.$id.'&caller='.$caller
        );

        // Form title.
        $form->addElement('header', $tool_name);

        // Title.
        $form->addElement('text', 'title', get_lang('Course name'), ['size' => '60', 'id' => 'title']);
        $form->applyFilter('title', 'html_filter');
        $form->addRule('title', get_lang('Required field'), 'required');

        // Course category.
        $url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_category';

        $courseSelect = $form->addSelectAjax(
            'category_code',
            get_lang('Category'),
            [],
            ['url' => $url]
        );

        if (!empty($course_request_info['category_code'])) {
            $data = CourseCategory::getCategory($course_request_info['category_code']);
            $courseSelect->addOption($data['name'], $data['code'], ['selected' => 'selected']);
        }

        // Course code.
        $form->addText(
            'wanted_code',
            get_lang('Course code'),
            false,
            ['size' => '$maxlength', 'maxlength' => $maxlength]
        );
        $form->applyFilter('wanted_code', 'html_filter');
        $form->addRule('wanted_code', get_lang('max. 20 characters, e.g. <i>INNOV21</i>'), 'maxlength', $maxlength);
        $form->addRule('wanted_code', get_lang('Required field'), 'required');

        // The teacher.
        $titular = $form->addText(
            'tutor_name',
            get_lang('Trainer'),
            null,
            ['size' => '60', 'disabled' => 'disabled']
        );

        // Description of the requested course.
        $form->addElement('textarea', 'description', get_lang('Description'));
        $form->addRule('description', get_lang('Required field'), 'required');

        // Objectives of the requested course.
        $form->addElement('textarea', 'objetives', get_lang('Objectives'));
        $form->addRule('objetives', get_lang('Required field'), 'required');

        // Target audience of the requested course.
        $form->addElement('textarea', 'target_audience', get_lang('Target audience'));
        $form->addRule('target_audience', get_lang('Required field'), 'required');

        // Course language.
        $form->addSelectLanguage('course_language', get_lang('Language'));

        // Exemplary content checkbox.
        $form->addElement('checkbox', 'exemplary_content', get_lang('Fill with demo content'));

        // Submit buttons.
        $submit_buttons[] = $form->addButtonSave(get_lang('Save'), 'save_button', true);
        if (COURSE_REQUEST_ACCEPTED != $course_request_info['status']) {
            $submit_buttons[] = $form->addButtonSave(get_lang('Accept'), 'accept_button', true);
        }
        if (COURSE_REQUEST_ACCEPTED != $course_request_info['status'] &&
            COURSE_REQUEST_REJECTED != $course_request_info['status']
        ) {
            $submit_buttons[] = $form->addButtonCancel(get_lang('Reject'), 'reject_button', true);
        }
        if (COURSE_REQUEST_ACCEPTED != $course_request_info['status'] && (int) ($course_request_info['info']) <= 0) {
            $submit_buttons[] = $form->addButtonPreview(get_lang('Ask for additional information'), 'ask_info_button', true);
        }
        $form->addGroup($submit_buttons);

        // Hidden form fields.
        $form->addElement('hidden', 'user_id');
        $form->addElement('hidden', 'directory');
        $form->addElement('hidden', 'visual_code');
        $form->addElement('hidden', 'request_date');
        $form->addElement('hidden', 'status');
        $form->addElement('hidden', 'info');

        // Set the default values based on the corresponding database record.
        $values['wanted_code'] = $course_request_info['code'];
        $values['user_id'] = $course_request_info['user_id'];
        $values['directory'] = $course_request_info['directory'];
        $values['course_language'] = $course_request_info['course_language'];
        $values['title'] = $course_request_info['title'];
        $values['description'] = $course_request_info['description'];
        //$values['category_code'] = $course_request_info['category_code'];
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
            $course_request_values = $form->getSubmitValues();

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
                $message = [];

                // Update the course request.
                $update_ok = CourseRequestManager::update_course_request(
                    $id,
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
                    Display::addFlash(
                        Display::return_message(
                            sprintf(
                                get_lang('The course request %s has been updated.'),
                                $course_request_values['wanted_code']
                            ),
                            'normal',
                            false
                        )
                    );

                    switch ($submit_button) {
                        case 'accept_button':
                            if (CourseRequestManager::accept_course_request($id)) {
                                Display::addFlash(
                                    Display::return_message(
                                        sprintf(
                                            get_lang('The course request %s has been accepted. A new course %s has been created.'),
                                            $course_request_values['wanted_code'],
                                            $course_request_values['wanted_code']
                                        ),
                                        'normal',
                                        false
                                    )
                                );
                            } else {
                                Display::addFlash(
                                    Display::return_message(
                                        sprintf(
                                            get_lang('The course request %s has not been accepted due to internal error.'),
                                            $course_request_values['wanted_code']
                                        )
                                    ),
                                    'error',
                                    false
                                );
                            }

                            break;
                        case 'reject_button':
                            if (CourseRequestManager::reject_course_request($id)) {
                                Display::addFlash(
                                    Display::return_message(
                                        sprintf(
                                            get_lang('The course request %s has been rejected.'),
                                            $course_request_values['wanted_code']
                                        )
                                    ),
                                    'normal',
                                    false
                                );
                            } else {
                                Display::addFlash(
                                    Display::return_message(
                                        sprintf(
                                            get_lang('The course request %s has not been rejected due to internal error.'),
                                            $course_request_values['wanted_code']
                                        )
                                    ),
                                    'error',
                                    false
                                );
                            }

                            break;
                        case 'ask_info_button':
                            if (CourseRequestManager::ask_for_additional_info($id)) {
                                Display::addFlash(
                                    Display::return_message(
                                        sprintf(
                                            get_lang('Additional information about the course request %s has been asked.'),
                                            $course_request_values['wanted_code']
                                        )
                                    ),
                                    'normal',
                                    false
                                );
                            } else {
                                Display::addFlash(
                                    Display::return_message(
                                        sprintf(
                                            get_lang('Additional information about the course request %s has not been asked due to internal error.'),
                                            $course_request_values['wanted_code']
                                        )
                                    ),
                                    'error',
                                    false
                                );
                            }

                            break;
                    }
                } else {
                    Display::addFlash(
                        Display::return_message(
                            sprintf(
                                get_lang('The course request %s has not been updated due to internal error.'),
                                $course_request_values['wanted_code']
                            )
                        ),
                        'error',
                        false
                    );
                }

                $back_url = get_caller_name($caller);
                header('location:'.$back_url);
                exit;
            } else {
                Display::addFlash(
                    Display::return_message(
                        $course_request_values['wanted_code'].' - '.get_lang('CourseCourse codeAlreadyExists')
                    ),
                    'error',
                    false
                );
            }
        }
    }
} else {
    // Prepare an error message notifying that the course validation feature has not been enabled.
    $link_to_setting = api_get_path(WEB_CODE_PATH).'admin/settings.php?search_field=course_validation&submit_button=&category=search_setting';
    $message = sprintf(
        get_lang('The "Course validation" feature is not enabled at the moment. In order to use this feature, please, enable it by using the  %s setting.'),
        sprintf(
            '<strong><a href="%s">%s</a></strong>',
            $link_to_setting,
            get_lang('Courses validation')
        )
    );
    Display::addFlash(
        Display::return_message($message),
        'error',
        false
    );
}

// Functions.

// Converts the given numerical id to the name of the page that opened this editor.
function get_caller_name($caller_id)
{
    switch ($caller_id) {
        case 1:
            return 'course_request_accepted.php';
        case 2:
            return 'course_request_rejected.php';
    }

    return 'course_request_review.php';
}

// The header.
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'course_list.php', 'name' => get_lang('Course list')];

Display :: display_header($tool_name);

if (!$course_validation_feature) {
    // Disabled course validation feature - show nothing after the error message.
    Display::display_footer();
    exit;
}

// The action bar.
echo '<div class="actions">';
echo '<a href="course_list.php">'.
    Display::return_icon('courses.gif', get_lang('Course list')).get_lang('Course list').'</a>';
echo '<a href="course_request_review.php">'.
    Display::return_icon('course_request_pending.png', get_lang('Review incoming course requests')).get_lang('Review incoming course requests').
    '</a>';
echo '<a href="course_request_accepted.php">'.
    Display::return_icon('course_request_accepted.gif', get_lang('Accepted course requests')).get_lang('Accepted course requests').
    '</a>';
echo '<a href="course_request_rejected.php">'.
    Display::return_icon('course_request_rejected.gif', get_lang('Rejected course requests')).get_lang('Rejected course requests').
    '</a>';
echo '</div>';

if (!is_array($course_request_info)) {
    // Not accessible database record - show the error message and the action bar.
    Display::display_footer();
    exit;
}

// Display the form.
$form->display();

// The footer.
Display::display_footer();

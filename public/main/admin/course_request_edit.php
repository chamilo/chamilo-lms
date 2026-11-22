<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

/**
 * A page for detailed preview or edition of a given course request.
 *
 * @author Ivan Tcholakov <ivantcholakov@gmail.com>, 2010
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

require_once __DIR__.'/../inc/lib/course_request_admin.inc.php';
$tool_name = get_lang('Edit a course request');

if (!api_protect_admin_script()) {
    exit;
}

// A check whether the course validation feature is enabled.
$course_validation_feature = 'true' === api_get_setting('course.course_validation');

// Filtering passed to this page parameters.
$id = is_scalar($_GET['id'] ?? null) ? max(0, (int) $_GET['id']) : 0;
$caller = is_scalar($_GET['caller'] ?? null) ? (int) $_GET['caller'] : COURSE_REQUEST_PENDING;
$caller = in_array($caller, [COURSE_REQUEST_PENDING, COURSE_REQUEST_ACCEPTED, COURSE_REQUEST_REJECTED], true) ? $caller : COURSE_REQUEST_PENDING;
$course_request_info = false;

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
        $caller = (int) $course_request_info['status'];
        // Keep the same course code length limit.
        $maxlength = 40;

        // Build the form.
        $form = new FormValidator(
            'add_course',
            'post',
            'course_request_edit.php?id='.$id.'&caller='.$caller
        );
        $form->protect();

        $form->protect();
        $form->addElement('header', get_lang('Course request details'));

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
            if (is_array($data) && isset($data['name'], $data['code'])) {
                $courseSelect->addOption($data['name'], $data['code'], ['selected' => 'selected']);
            }
        }

        // Course code.
        $form->addText(
            'wanted_code',
            get_lang('Course code'),
            false,
            ['maxlength' => $maxlength]
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
        $form->addElement('textarea', 'description', get_lang('Description'), ['rows' => 4]);
        $form->addRule('description', get_lang('Required field'), 'required');

        // Objectives of the requested course.
        $form->addElement('textarea', 'objetives', get_lang('Objectives'), ['rows' => 4]);
        $form->addRule('objetives', get_lang('Required field'), 'required');

        // Target audience of the requested course.
        $form->addElement('textarea', 'target_audience', get_lang('Target audience'), ['rows' => 4]);
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
        if (COURSE_REQUEST_ACCEPTED != $course_request_info['status']
            && COURSE_REQUEST_REJECTED != $course_request_info['status']
        ) {
            $submit_buttons[] = $form->addButtonCancel(get_lang('Reject'), 'reject_button', true);
        }
        if (COURSE_REQUEST_ACCEPTED != $course_request_info['status'] && (int) $course_request_info['info'] <= 0) {
            $submit_buttons[] = $form->addButtonPreview(get_lang('Ask for additional information'), 'ask_info_button', true);
        }
        $form->addGroup($submit_buttons);

        // Set the default values based on the corresponding database record.
        $values['wanted_code'] = $course_request_info['code'];
        $values['course_language'] = $course_request_info['course_language'];
        $values['title'] = $course_request_info['title'];
        $values['description'] = $course_request_info['description'];
        $values['category_code'] = $course_request_info['category_code'] ?? '';
        $values['tutor_name'] = $course_request_info['tutor_name'];
        $values['objetives'] = $course_request_info['objetives'];
        $values['target_audience'] = $course_request_info['target_audience'];
        $values['exemplary_content'] = $course_request_info['exemplary_content'];
        $form->setDefaults($values);

        // Validate the form and perform the ordered actions.
        if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? '') && $form->validate()) {
            if (!Security::check_token('form', $form)) {
                api_not_allowed(true);

                exit;
            }
            $course_request_values = $form->getSubmitValues();

            // Detection which submit button has been pressed.
            $submit_button = isset($_POST['save_button']) ? 'save_button'
                : (isset($_POST['accept_button']) ? 'accept_button'
                    : (isset($_POST['reject_button']) ? 'reject_button'
                        : (isset($_POST['ask_info_button']) ? 'ask_info_button'
                            : 'submit_button')));

            // Validate requested actions against the persisted request state.
            $allowedAction = match ($submit_button) {
                'save_button', 'submit_button' => true,
                'accept_button' => COURSE_REQUEST_ACCEPTED !== (int) $course_request_info['status'],
                'reject_button' => COURSE_REQUEST_PENDING === (int) $course_request_info['status'],
                'ask_info_button' => COURSE_REQUEST_ACCEPTED !== (int) $course_request_info['status'] && 0 === (int) $course_request_info['info'],
                default => false,
            };
            if (!$allowedAction) {
                api_not_allowed(true);

                exit;
            }

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
                    $course_request_values['category_code'] ?? '',
                    $course_request_values['course_language'],
                    $course_request_values['objetives'],
                    $course_request_values['target_audience'],
                    (int) $course_request_info['user_id'],
                    (int) !empty($course_request_values['exemplary_content'])
                );

                if ($update_ok) {
                    Display::addFlash(
                        Display::return_message(
                            sprintf(
                                get_lang('The course request %s has been updated.'),
                                course_request_admin_escape($course_request_values['wanted_code'])
                            ),
                            'normal',
                            false
                        )
                    );

                    switch ($submit_button) {
                        case 'accept_button':
                            $createdCode = CourseRequestManager::accept_course_request($id);
                            if ($createdCode) {
                                Display::addFlash(
                                    Display::return_message(
                                        sprintf(
                                            get_lang('The course request %s has been accepted. A new course %s has been created.'),
                                            course_request_admin_escape($course_request_values['wanted_code']),
                                            course_request_admin_escape($createdCode)
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
                                            course_request_admin_escape($course_request_values['wanted_code'])
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
                                            course_request_admin_escape($course_request_values['wanted_code'])
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
                                            course_request_admin_escape($course_request_values['wanted_code'])
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
                                            course_request_admin_escape($course_request_values['wanted_code'])
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
                                            course_request_admin_escape($course_request_values['wanted_code'])
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
                                course_request_admin_escape($course_request_values['wanted_code'])
                            )
                        ),
                        'error',
                        false
                    );
                }

                $back_url = get_caller_name($caller);
                header('location:'.$back_url);

                exit;
            }
            Display::addFlash(
                Display::return_message(
                    course_request_admin_escape($course_request_values['wanted_code']).' - '.get_lang('Sorry, but that course code already exists. Please choose another one.')
                ),
                'error',
                false
            );
        }
    }
} else {
    // Prepare an error message notifying that the course validation feature has not been enabled.
    $link_to_setting = '/admin/settings/search_settings?keyword=course_validation';
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
$interbreadcrumb[] = ['url' => '/admin/course-list', 'name' => get_lang('Course list')];

Display::display_header($tool_name);

if (!$course_validation_feature) {
    // Disabled course validation feature - show nothing after the error message.
    Display::display_footer();

    exit;
}

// Keep the same navigation as the list pages.
course_request_admin_navigation($caller);
echo '<div class="mb-4"><a class="btn btn--plain" href="'.course_request_admin_escape(get_caller_name($caller)).'"><span class="mdi mdi-arrow-left" aria-hidden="true"></span> '.course_request_admin_escape(get_lang('Back')).'</a></div>';

if (!is_array($course_request_info)) {
    // Not accessible database record - show the error message and the action bar.
    Display::display_footer();

    exit;
}

// Display the request details in the same admin card style.
echo '<section class="rounded-2xl border border-gray-25 bg-white p-4 shadow-sm">';
echo '<h2 class="mb-4 text-xl font-semibold text-gray-90">'.course_request_admin_escape($course_request_info['title']).' <span class="text-body-2 text-gray-50">('.course_request_admin_escape($course_request_info['code']).')</span></h2>';
$form->display();
echo '</section>';

// The footer.
Display::display_footer();

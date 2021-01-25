<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRecycler;
use Chamilo\CourseBundle\Component\CourseCopy\CourseSelectForm;

/**
 * Delete resources from a course.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 */
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_COURSE_MAINTENANCE;
api_protect_course_script(true);

$_course = api_get_course_info();
$current_course_code = $_course['official_code'];

// Check access rights (only teachers are allowed here)
if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

// Section for the tabs
$this_section = SECTION_COURSES;

// Breadcrumbs
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'course_info/maintenance.php',
    'name' => get_lang('Maintenance'),
];

// Displaying the header
$nameTools = get_lang('RecycleCourse');
Display::display_header($nameTools);

// Display the tool title
echo Display::page_header($nameTools);
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (Security::check_token('post') && (
        $action === 'course_select_form' ||
        (
            isset($_POST['recycle_option']) &&
            $_POST['recycle_option'] == 'full_backup'
        )
    )
) {
    // Clear token
    Security::clear_token();
    if (isset($_POST['action']) && $_POST['action'] === 'course_select_form') {
        $course = CourseSelectForm::get_posted_course();
    } else {
        $cb = new CourseBuilder();
        $course = $cb->build();
    }
    $recycle_type = '';
    $fullDelete = 0;
    $courseCodeConfirmation = '';
    if (isset($_POST['course_code_confirmation'])) {
        $courseCodeConfirmation = $_POST['course_code_confirmation'];
    }
    if (isset($_POST['recycle_option']) && $_POST['recycle_option'] === 'full_backup') {
        $recycle_type = 'full_backup';
        $fullDelete = 1;
    } elseif (isset($_POST['action']) && $_POST['action'] === 'course_select_form') {
        $recycle_type = 'select_items';
    }
    $cr = new CourseRecycler($course);
    if ($recycle_type === 'full_backup') {
        /* to delete, course code confirmation must be equal that current course code */
        if ($current_course_code == $courseCodeConfirmation) {
            $cr->recycle($recycle_type);
            echo Display::return_message(get_lang('RecycleFinished'), 'confirm');
        } else {
            echo Display::return_message(get_lang('CourseRegistrationCodeIncorrect'), 'error', false);
            echo '<p><a class="btn btn-primary" href="'.api_get_self().'?'.api_get_cidreq().'">'.
                get_lang('BackToPreviousPage').
                '</a></p>';
        }
    } elseif ($recycle_type === 'select_items') {
        $cr->recycle($recycle_type);
        echo Display::return_message(get_lang('RecycleFinished'), 'confirm');
    }
} elseif (Security::check_token('post') && (
        isset($_POST['recycle_option']) &&
        $_POST['recycle_option'] === 'select_items'
    )
) {
    // Clear token
    Security::clear_token();

    $cb = new CourseBuilder();
    $course = $cb->build();
    // Add token to Course select form
    $hiddenFields['sec_token'] = Security::get_token();
    CourseSelectForm::display_form($course, $hiddenFields);
} else {
    $cb = new CourseBuilder();
    $course = $cb->build();
    if (!$course->has_resources()) {
        echo get_lang('NoResourcesToRecycle');
    } else {
        echo Display::return_message(get_lang('RecycleWarning'), 'warning', false);
        $form = new FormValidator('recycle_course', 'post', api_get_self().'?'.api_get_cidreq());
        $form->addHeader(get_lang('SelectOptionForBackup'));
        $form->addElement('radio', 'recycle_option', null, get_lang('FullRecycle'), 'full_backup');
        $form->addElement('radio', 'recycle_option', null, get_lang('LetMeSelectItems'), 'select_items');

        $form->addHtml('<div class="course-full-delete hidden">');
        $form->addText('course_code_confirmation', get_lang('CourseCodeConfirmation'));
        $form->addHtml('</div>');

        $form->addButtonSave(get_lang('RecycleCourse'));
        $form->setDefaults(['recycle_option' => 'select_items']);
        // Add Security token
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(['sec_token' => $token]);
        $form->display();
        /* make recycle_course_course_code_confirmation required to put code course */
        echo '
            <script>
            $(function(){
                $(`[type="radio"]`).on(`change`, function (e) {
                    if ($(this).val() === `full_backup`) {
                        $(`#recycle_course_course_code_confirmation`).prop(`required`, `required`);
                        $(`.course-full-delete`).removeClass(`hidden`);
                    } else {
                        $(`#recycle_course_course_code_confirmation`).prop(`required`, ``);
                        $(`.course-full-delete`).addClass(`hidden`);
                    }
                });
            })
            </script>';
    }
}

// Display the footer
Display::display_footer();

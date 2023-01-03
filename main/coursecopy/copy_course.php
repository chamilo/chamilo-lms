<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use Chamilo\CourseBundle\Component\CourseCopy\CourseSelectForm;

/**
 * @todo rework file in order to use addFlash
 */

// Setting the global file that gets the general configuration, the databases, the languages, ...
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_COURSE_MAINTENANCE;
api_protect_course_script(true);

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

api_set_more_memory_and_time_limits();

// Breadcrumbs
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'course_info/maintenance.php?'.api_get_cidreq(),
    'name' => get_lang('Maintenance'),
];

// The section (for the tabs)
$this_section = SECTION_COURSES;

// Display the header
Display::display_header(get_lang('CopyCourse'));
echo Display::page_header(get_lang('CopyCourse'));

$action = isset($_POST['action']) ? $_POST['action'] : '';

// If a CourseSelectForm is posted or we should copy all resources, then copy them
if (Security::check_token('post') && (
    ($action === 'course_select_form') ||
    (isset($_POST['copy_option']) && $_POST['copy_option'] === 'full_copy')
    )
) {
    // Clear token
    Security::clear_token();
    if ($action === 'course_select_form') {
        $cb = new CourseBuilder('partial');
        $course = $cb->build(0, null, false, array_keys($_POST['resource']), $_POST['resource']);
        $course = CourseSelectForm::get_posted_course(null, 0, '', $course);
    } else {
        $cb = new CourseBuilder('complete');
        $course = $cb->build();
    }

    // It builds the documents and items related to the LP
    $cb->exportToCourseBuildFormat();
    // It builds documents added in text (quizzes, assignments)
    $cb->restoreDocumentsFromList();

    $cr = new CourseRestorer($course);
    $cr->set_file_option($_POST['same_file_name_option']);
    $cr->restore($_POST['destination_course']);
    echo Display::return_message(
        get_lang('CopyFinished').': <a href="'.api_get_course_url($_POST['destination_course']).'">'.
        Security::remove_XSS($_POST['destination_course']).
        '</a>',
        'normal',
        false
    );
} elseif (Security::check_token('post') && (
        isset($_POST['copy_option']) &&
        $_POST['copy_option'] === 'select_items'
    )
) {
    // Clear token
    Security::clear_token();

    $cb = new CourseBuilder();
    $course = $cb->build();
    $hiddenFields = [];
    $hiddenFields['same_file_name_option'] = $_POST['same_file_name_option'];
    $hiddenFields['destination_course'] = $_POST['destination_course'];
    // Add token to Course select form
    $hiddenFields['sec_token'] = Security::get_token();
    CourseSelectForm::display_form($course, $hiddenFields, true);
} else {
    $course_info = api_get_course_info();
    $courseList = CourseManager::getCoursesFollowedByUser(
        api_get_user_id(),
        COURSEMANAGER,
        null,
        null,
        null,
        null,
        false,
        null,
        null,
        false,
        'ORDER BY c.title'
    );
    $courses = [];
    foreach ($courseList as $courseItem) {
        if ($courseItem['real_id'] == $course_info['real_id']) {
            continue;
        }
        $courses[$courseItem['code']] = $courseItem['title'].' ('.$courseItem['code'].')';
    }

    if (empty($courses)) {
        echo Display::return_message(get_lang('NoDestinationCoursesAvailable'), 'normal');
    } else {
        $form = new FormValidator(
            'copy_course',
            'post',
            api_get_path(WEB_CODE_PATH).'coursecopy/copy_course.php?'.api_get_cidreq()
        );
        $form->addElement(
            'select',
            'destination_course',
            [
                get_lang('SelectDestinationCourse'),
                get_lang('YouCanOnlyCopyThisCourseToACourseYouTeach'),
            ],
            $courses
        );

        $group = [];
        $group[] = $form->createElement('radio', 'copy_option', null, get_lang('FullCopy'), 'full_copy');
        $group[] = $form->createElement('radio', 'copy_option', null, get_lang('LetMeSelectItems'), 'select_items');
        $form->addGroup($group, '', get_lang('SelectOptionForBackup'));

        $group = [];
        $group[] = $form->createElement(
            'radio',
            'same_file_name_option',
            null,
            get_lang('SameFilenameSkip'),
            FILE_SKIP
        );
        $group[] = $form->createElement(
            'radio',
            'same_file_name_option',
            null,
            get_lang('SameFilenameRename'),
            FILE_RENAME
        );
        $group[] = $form->createElement(
            'radio',
            'same_file_name_option',
            null,
            get_lang('SameFilenameOverwrite'),
            FILE_OVERWRITE
        );
        $form->addGroup($group, '', get_lang('SameFilename'));
        $form->addProgress();
        $form->addButtonSave(get_lang('CopyCourse'));
        $form->setDefaults(['copy_option' => 'select_items', 'same_file_name_option' => FILE_OVERWRITE]);

        // Add Security token
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(['sec_token' => $token]);
        $form->display();
    }
}

Display::display_footer();

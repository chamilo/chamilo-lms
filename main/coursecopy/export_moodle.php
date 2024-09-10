<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\CourseSelectForm;
use moodleexport\MoodleExport;

/**
 * Create a Moodle export.
 *
 */
require_once __DIR__.'/../inc/global.inc.php';
require_once api_get_path(SYS_PATH).'main/work/work.lib.php';

$current_course_tool = TOOL_COURSE_MAINTENANCE;

api_protect_course_script(true);

// Check access rights (only teachers are allowed here)
if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

api_check_archive_dir();
api_set_more_memory_and_time_limits();

// Section for the tabs
$this_section = SECTION_COURSES;

// Breadcrumbs
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'course_info/maintenance.php',
    'name' => get_lang('Maintenance'),
];

// Displaying the header
$nameTools = get_lang('ExportToMoodle');
Display::display_header($nameTools);

// Display the tool title
echo Display::page_header($nameTools);
$action = isset($_POST['action']) ? $_POST['action'] : '';
$exportOption = isset($_POST['export_option']) ? $_POST['export_option'] : '';

if (Security::check_token('post') &&
    ($action === 'course_select_form' || $exportOption === 'full_export')
) {
    // Clear token
    Security::clear_token();

    // Build course object based on the action
    if ($action === 'course_select_form') {
        $cb = new CourseBuilder('partial');
        $course = $cb->build(0, null, false, array_keys($_POST['resource']), $_POST['resource']);
        $course = CourseSelectForm::get_posted_course(null, 0, '', $course);
    } else {
        $cb = new CourseBuilder('complete');
        $course = $cb->build();
    }

    // Instantiate MoodleExport and pass course data to it
    $exporter = new MoodleExport($course);
    $courseId = api_get_course_id();  // Get course ID
    $exportDir = 'moodle_export_' . $courseId;
    $coursePath = api_get_course_path();  // Get course path

    try {
        $moodleVersion = isset($_POST['moodle_version']) ? $_POST['moodle_version'] : '3';

        // Pass the course data to the export function
        $mbzFile = $exporter->export($courseId, $exportDir, $moodleVersion);
        echo Display::return_message(get_lang('MoodleExportCreated'), 'confirm');
        echo '<br />';
        echo Display::url(
            get_lang('Download'),
            api_get_path(WEB_CODE_PATH).'course_info/download.php?archive_path=1&archive='.basename($mbzFile).'&'.api_get_cidreq(),
            ['class' => 'btn btn-primary btn-large']
        );
    } catch (Exception $e) {
        echo Display::return_message(get_lang('ErrorCreatingExport').': '.$e->getMessage(), 'error');
    }

} elseif (Security::check_token('post') && $exportOption === 'select_items') {
    // Clear token
    Security::clear_token();

    // Build course object for partial export
    $cb = new CourseBuilder('partial');
    $course = $cb->build();
    if ($course->has_resources()) {
        // Add token to Course select form
        $hiddenFields['sec_token'] = Security::get_token();
        CourseSelectForm::display_form($course, $hiddenFields, false, true);
    } else {
        echo Display::return_message(get_lang('NoResourcesToExport'), 'warning');
    }
} else {
    $form = new FormValidator(
        'create_export_form',
        'post',
        api_get_self().'?'.api_get_cidreq()
    );
    $form->addElement('radio', 'export_option', '', get_lang('CreateFullExport'), 'full_export');
    $form->addElement('radio', 'export_option', '', get_lang('LetMeSelectItems'), 'select_items');
    $form->addElement('select', 'moodle_version', get_lang('MoodleVersion'), [
        '3' => 'Moodle 3.x',
        '4' => 'Moodle 4.x',
    ]);

    $form->addButtonSave(get_lang('CreateExport'));
    $form->addProgress();
    // When progress bar appears we have to hide the title "Please select an export-option".
    $form->updateAttributes(
        [
            'onsubmit' => str_replace(
                'javascript: ',
                'javascript: page_title = getElementById(\'page_title\'); if (page_title) { setTimeout(\'page_title.style.display = \\\'none\\\';\', 2000); } ',
                $form->getAttribute('onsubmit')
            ),
        ]
    );
    $values['export_option'] = 'full_export';
    $form->setDefaults($values);

    // Add Security token
    $token = Security::get_token();
    $form->addElement('hidden', 'sec_token');
    $form->setConstants(['sec_token' => $token]);
    echo '<div class="row">';
    echo '<div class="col-md-12">';
    echo '<div class="tool-export">';
    $form->display();
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

Display::display_footer();

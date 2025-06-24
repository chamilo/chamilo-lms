<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\CourseSelectForm;
use moodleexport\MoodleExport;

/**
 * Create a Moodle export.
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

// Handle course selection form submission
if ($action === 'course_select_form' && Security::check_token('post')) {
    // Handle the selected resources and continue with export
    $selectedResources = $_POST['resource'] ?? null;

    if (!empty($selectedResources)) {
        // Rebuild the course object based on selected resources
        $cb = new CourseBuilder('partial');
        $course = $cb->build(0, null, false, array_keys($selectedResources), $selectedResources);
        $course = CourseSelectForm::get_posted_course(null, 0, '', $course);

        // Get admin details
        $adminId = (int) $_POST['admin_id'];
        $adminUsername = filter_var($_POST['admin_username'], FILTER_SANITIZE_STRING);
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $adminUsername)) {
            echo Display::return_message(get_lang('PleaseEnterValidLogin'), 'error');
            exit();
        }

        $adminEmail = filter_var($_POST['admin_email'], FILTER_SANITIZE_EMAIL);
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            echo Display::return_message(get_lang('PleaseEnterValidEmail'), 'error');
            exit();
        }

        $exporter = new MoodleExport($course);
        $exporter->setAdminUserData($adminId, $adminUsername, $adminEmail);

        // Perform export
        $courseId = api_get_course_id();
        $exportDir = 'moodle_export_'.$courseId;
        try {
            $moodleVersion = isset($_POST['moodle_version']) ? (int) $_POST['moodle_version'] : 3;
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
        exit();
    } else {
        echo Display::return_message(get_lang('NoResourcesSelected'), 'warning');
    }
} else {
    $form = new FormValidator(
        'create_export_form',
        'post',
        api_get_self().'?'.api_get_cidreq()
    );
    $form->addElement('radio', 'export_option', '', get_lang('CreateFullBackup'), 'full_export');
    $form->addElement('radio', 'export_option', '', get_lang('LetMeSelectItems'), 'select_items');
    $form->addElement('select', 'moodle_version', get_lang('MoodleVersion'), [
        '3' => 'Moodle 3.x',
        '4' => 'Moodle 4.x',
    ]);

    $form->addElement('text', 'admin_id', [get_lang('AdminID'), get_lang('MoodleExportAdminIDComment')], ['maxlength' => 10, 'size' => 10]);
    $form->addElement('text', 'admin_username', get_lang('AdminLogin'), ['maxlength' => 100, 'size' => 50]);
    $form->addElement('text', 'admin_email', get_lang('AdminEmail'), ['maxlength' => 100, 'size' => 50]);

    // Add validation rules
    $form->addRule('admin_id', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('admin_username', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('admin_email', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('admin_email', get_lang('EnterValidEmail'), 'email');

    $values['export_option'] = 'select_items';
    $form->setDefaults($values);

    // Add buttons
    $form->addButtonSave(get_lang('CreateExport'));
    $form->addProgress();

    if ($form->validate()) {
        $values = $form->exportValues();
        $adminId = (int) $values['admin_id'];
        $adminUsername = $values['admin_username'];
        $adminEmail = $values['admin_email'];

        if ($values['export_option'] === 'full_export') {
            $cb = new CourseBuilder('complete');
            $course = $cb->build();

            $exporter = new MoodleExport($course);
            $exporter->setAdminUserData($adminId, $adminUsername, $adminEmail);

            $courseId = api_get_course_id();  // Get course ID
            $exportDir = 'moodle_export_'.$courseId;

            try {
                $moodleVersion = $values['moodle_version'] ?? '3';
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
        } elseif ($values['export_option'] === 'select_items') {
            // Partial export - go to the item selection step
            $cb = new CourseBuilder('partial');
            $course = $cb->build();
            if ($course->has_resources()) {
                // Add token to Course select form
                $hiddenFields['sec_token'] = Security::get_token();
                $hiddenFields['admin_id'] = $adminId;
                $hiddenFields['admin_username'] = $adminUsername;
                $hiddenFields['admin_email'] = $adminEmail;

                CourseSelectForm::display_form($course, $hiddenFields, false, true);
            } else {
                echo Display::return_message(get_lang('NoResourcesToExport'), 'warning');
            }
        }
    } else {
        echo '<div class="row">';
        echo '<div class="col-md-12">';
        echo '<div class="tool-export">';
        $form->display();
        echo '</div>';
        echo '</div>';
    }
}

Display::display_footer();

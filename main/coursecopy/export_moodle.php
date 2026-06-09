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
$debugMoodleExport = MoodleExport::isDebugEnabled();
MoodleExport::registerDebugShutdownHandler();

if ($debugMoodleExport) {
    MoodleExport::debugStaticLog('Debug mode enabled from MoodleExport::$debugEnabled', [
        'script' => 'export_moodle.php',
        'course_id' => api_get_course_id(),
        'course_code' => api_get_course_id(),
        'cidreq' => api_get_cidreq(),
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
        'post_action' => $action,
        'export_option' => $exportOption,
        'post_keys' => array_keys($_POST),
        'content_length' => $_SERVER['CONTENT_LENGTH'] ?? '',
        'debug_file' => MoodleExport::getDebugFilePath(),
    ]);
}

// Handle course selection form submission
MoodleExport::debugStaticLog('Checking export form branch', [
    'action' => $action,
    'has_post_token' => isset($_POST['sec_token']),
]);

if ($action === 'course_select_form' && Security::check_token('post')) {
    MoodleExport::debugStaticLog('Course selection form submitted');

    // Handle the selected resources and continue with export
    $selectedResources = $_POST['resource'] ?? null;
    MoodleExport::debugStaticLog('Selected resources received', [
        'resource_groups' => is_array($selectedResources) ? count($selectedResources) : 0,
    ]);

    if (!empty($selectedResources)) {
        // Rebuild the course object based on selected resources
        MoodleExport::debugStaticLog('Building partial course from selected resources');
        $cb = new CourseBuilder('partial');
        $course = $cb->build(0, null, false, array_keys($selectedResources), $selectedResources);
        MoodleExport::restoreMainDatabaseConnection();
        MoodleExport::debugStaticLog('Partial course from selected resources built');

        MoodleExport::debugStaticLog('Normalizing posted course selection');
        $course = CourseSelectForm::get_posted_course(null, 0, '', $course);
        MoodleExport::restoreMainDatabaseConnection();
        MoodleExport::debugStaticLog('Posted course selection normalized');

        // Get admin details
        $adminId = (int) $_POST['admin_id'];
        $adminUsername = strip_tags((string) $_POST['admin_username']);
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $adminUsername)) {
            echo Display::return_message(get_lang('PleaseEnterValidLogin'), 'error');
            exit();
        }

        $adminEmail = filter_var($_POST['admin_email'], FILTER_SANITIZE_EMAIL);
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            echo Display::return_message(get_lang('PleaseEnterValidEmail'), 'error');
            exit();
        }

        MoodleExport::debugStaticLog('Creating MoodleExport instance for selected resources');
        $exporter = new MoodleExport($course);
        MoodleExport::debugStaticLog('MoodleExport instance created for selected resources');

        $exporter->setAdminUserData($adminId, $adminUsername, $adminEmail);
        MoodleExport::debugStaticLog('Admin user data configured', [
            'admin_id' => $adminId,
            'admin_username' => $adminUsername,
            'admin_email' => $adminEmail,
        ]);

        // Perform export
        $courseId = api_get_course_id();
        $exportDir = 'moodle_export_'.$courseId;
        try {
            $moodleVersion = isset($_POST['moodle_version']) ? (int) $_POST['moodle_version'] : 3;
            MoodleExport::debugStaticLog('Starting selected resources Moodle export', [
                'course_id' => $courseId,
                'export_dir' => $exportDir,
                'moodle_version' => $moodleVersion,
            ]);

            $mbzFile = $exporter->export($courseId, $exportDir, $moodleVersion);
            MoodleExport::debugStaticLog('Selected resources Moodle export finished', [
                'mbz_file' => $mbzFile,
            ]);

            echo Display::return_message(get_lang('MoodleExportCreated'), 'confirm');
            echo '<br />';
            echo Display::url(
                get_lang('Download'),
                api_get_path(WEB_CODE_PATH).'course_info/download.php?archive_path=1&archive='.basename($mbzFile).'&'.api_get_cidreq(),
                ['class' => 'btn btn-primary btn-large']
            );
        } catch (Throwable $e) {
            MoodleExport::restoreMainDatabaseConnection();

            if ($debugMoodleExport) {
                error_log('[MoodleExport] Export failed: '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
                error_log('[MoodleExport] Stack trace: '.$e->getTraceAsString());
            }

            echo Display::return_message(get_lang('ErrorCreatingExport').': '.$e->getMessage(), 'error');
        }
        exit();
    } else {
        echo Display::return_message(get_lang('NoResourcesSelected'), 'warning');
    }
} else {
    MoodleExport::debugStaticLog('Displaying initial export form or processing initial form submission');

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
    MoodleExport::debugStaticLog('Initial export form built');
    MoodleExport::debugStaticLog('Initial export form validate call started');
    $isInitialExportFormValid = $form->validate();
    MoodleExport::debugStaticLog('Initial export form validate call finished', [
        'is_valid' => $isInitialExportFormValid,
    ]);

    if ($isInitialExportFormValid) {
        MoodleExport::debugStaticLog('Initial export form validated');

        $values = $form->exportValues();
        MoodleExport::debugStaticLog('Initial export form values exported', [
            'export_option' => (string) ($values['export_option'] ?? ''),
            'moodle_version' => (string) ($values['moodle_version'] ?? ''),
        ]);
        $adminId = (int) $values['admin_id'];
        $adminUsername = $values['admin_username'];
        $adminEmail = $values['admin_email'];

        if ($values['export_option'] === 'full_export') {
            MoodleExport::debugStaticLog('Full export selected, building complete course');
            $cb = new CourseBuilder('complete');
            $course = $cb->build();
            MoodleExport::restoreMainDatabaseConnection();
            MoodleExport::debugStaticLog('Complete course built for full export');

            MoodleExport::debugStaticLog('Creating MoodleExport instance for full export');
            $exporter = new MoodleExport($course);
            MoodleExport::debugStaticLog('MoodleExport instance created for full export');

            $exporter->setAdminUserData($adminId, $adminUsername, $adminEmail);
            MoodleExport::debugStaticLog('Admin user data configured', [
                'admin_id' => $adminId,
                'admin_username' => $adminUsername,
                'admin_email' => $adminEmail,
            ]);

            $courseId = api_get_course_id();  // Get course ID
            $exportDir = 'moodle_export_'.$courseId;

            try {
                $moodleVersion = isset($values['moodle_version']) ? (int) $values['moodle_version'] : 3;
                MoodleExport::debugStaticLog('Starting full Moodle export', [
                    'course_id' => $courseId,
                    'export_dir' => $exportDir,
                    'moodle_version' => $moodleVersion,
                ]);

                $mbzFile = $exporter->export($courseId, $exportDir, $moodleVersion);
                MoodleExport::debugStaticLog('Full Moodle export finished', [
                    'mbz_file' => $mbzFile,
                ]);

                echo Display::return_message(get_lang('MoodleExportCreated'), 'confirm');
                echo '<br />';
                echo Display::url(
                    get_lang('Download'),
                    api_get_path(WEB_CODE_PATH).'course_info/download.php?archive_path=1&archive='.basename($mbzFile).'&'.api_get_cidreq(),
                    ['class' => 'btn btn-primary btn-large']
                );
            } catch (Throwable $e) {
                if ($debugMoodleExport) {
                    error_log('[MoodleExport] Export failed: '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
                    error_log('[MoodleExport] Stack trace: '.$e->getTraceAsString());
                }

                echo Display::return_message(get_lang('ErrorCreatingExport').': '.$e->getMessage(), 'error');
            }
        } elseif ($values['export_option'] === 'select_items') {
            // Partial export - go to the item selection step
            MoodleExport::debugStaticLog('Select items selected, building partial course for resource selection form');
            $cb = new CourseBuilder('partial');
            $course = $cb->build();
            MoodleExport::restoreMainDatabaseConnection();
            MoodleExport::debugStaticLog('Partial course built for resource selection form');

            if ($course->has_resources()) {
                MoodleExport::debugStaticLog('Partial course has resources, rendering resource selection form');

                // Add token to Course select form
                $hiddenFields['sec_token'] = Security::get_token();
                $hiddenFields['admin_id'] = $adminId;
                $hiddenFields['admin_username'] = $adminUsername;
                $hiddenFields['admin_email'] = $adminEmail;

                CourseSelectForm::display_form($course, $hiddenFields, false, true);
                MoodleExport::debugStaticLog('Resource selection form rendered');
            } else {
                MoodleExport::debugStaticLog('Partial course has no resources');
                echo Display::return_message(get_lang('NoResourcesToExport'), 'warning');
            }
        }
    } else {
        MoodleExport::debugStaticLog('Initial export form not submitted or not valid, rendering form');

        echo '<div class="row">';
        echo '<div class="col-md-12">';
        echo '<div class="tool-export">';
        MoodleExport::debugStaticLog('Initial export form display started');
        $form->display();
        MoodleExport::debugStaticLog('Initial export form display finished');
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
}

MoodleExport::debugStaticLog('Displaying page footer');
Display::display_footer();
MoodleExport::debugStaticLog('Page footer displayed');

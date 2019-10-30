<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Component\CourseCopy\CourseArchiver;
use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\CourseSelectForm;

/**
 * Create a backup.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 *
 * @package chamilo.backup
 */
require_once __DIR__.'/../inc/global.inc.php';
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
    'name' => get_lang('Backup'),
];

// Displaying the header
$nameTools = get_lang('Create a backup');
Display::display_header($nameTools);

// Display the tool title
echo Display::page_header($nameTools);
$action = isset($_POST['action']) ? $_POST['action'] : '';
$backupOption = isset($_POST['backup_option']) ? $_POST['backup_option'] : '';

if (Security::check_token('post') &&
    ($action === 'course_select_form' || $backupOption === 'full_backup')
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
    $zipFile = CourseArchiver::createBackup($course);
    echo Display::return_message(get_lang('The backup has been created. The download of this file will start in a few moments. If your download does not start, click the following link'), 'confirm');
    echo '<br />';
    echo Display::url(
        get_lang('Download'),
        api_get_path(WEB_CODE_PATH).'course_info/download.php?archive='.$zipFile.'&'.api_get_cidreq(),
        ['class' => 'btn btn-primary btn-large']
    );
} elseif (Security::check_token('post') && $backupOption === 'select_items') {
    // Clear token
    Security::clear_token();
    $cb = new CourseBuilder('partial');
    $course = $cb->build();
    if ($course->has_resources()) {
        // Add token to Course select form
        $hiddenFields['sec_token'] = Security::get_token();
        CourseSelectForm::display_form($course, $hiddenFields, false, true);
    } else {
        echo Display::return_message(get_lang('There are no resources to backup'), 'warning');
    }
} else {
    $form = new FormValidator(
        'create_backup_form',
        'post',
        api_get_self().'?'.api_get_cidreq()
    );
    $form->addElement('header', get_lang('Please select a backup option'));
    $form->addElement('radio', 'backup_option', '', get_lang('Create a complete backup of this course'), 'full_backup');
    $form->addElement('radio', 'backup_option', '', get_lang('Let me select learning objects'), 'select_items');
    $form->addButtonSave(get_lang('Create a backup'));
    $form->addProgress();
    // When progress bar appears we have to hide the title "Please select a backup-option".
    $form->updateAttributes(
        [
            'onsubmit' => str_replace(
                'javascript: ',
                'javascript: page_title = getElementById(\'page_title\'); if (page_title) { setTimeout(\'page_title.style.display = \\\'none\\\';\', 2000); } ',
                $form->getAttribute('onsubmit')
            ),
        ]
    );
    $values['backup_option'] = 'full_backup';
    $form->setDefaults($values);

    // Add Security token
    $token = Security::get_token();
    $form->addElement('hidden', 'sec_token');
    $form->setConstants(['sec_token' => $token]);
    echo '<div class="row">';
    echo '<div class="col-md-12">';
    echo '<div class="tool-backup">';
    $form->display();
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

Display::display_footer();

<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Component\CourseCopy\CourseSelectForm;
use Chamilo\CourseBundle\Component\CourseCopy\CourseArchiver;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;

/**
 * Import a backup.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.backup
 */

require_once __DIR__.'/../inc/global.inc.php';

$current_course_tool = TOOL_COURSE_MAINTENANCE;
api_protect_course_script(true);

// Check access rights (only teachers are allowed here)
if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

// Remove memory and time limits as much as possible as this might be a long process...
if (function_exists('ini_set')) {
    api_set_memory_limit('256M');
    ini_set('max_execution_time', 1800);
}

// Section for the tabs
$this_section = SECTION_COURSES;

// Breadcrumbs
$interbreadcrumb[] = array(
    'url' => '../course_info/maintenance.php',
    'name' => get_lang('Maintenance')
);

// Displaying the header
$nameTools = get_lang('ImportBackup');
Display::display_header($nameTools);

// Display the tool title
echo Display::page_header($nameTools);

/* MAIN CODE */
$filename = '';
if (Security::check_token('post') && (
        (
            isset($_POST['action']) &&
            $_POST['action'] == 'course_select_form'
        ) || (
            isset($_POST['import_option']) &&
            $_POST['import_option'] == 'full_backup'
        )
    )
) {
    // Clear token
    Security::clear_token();

    $error = false;
    if (isset($_POST['action']) && $_POST['action'] == 'course_select_form') {
        // Partial backup here we recover the documents posted
        // This gets $_POST['course']. Beware that when using Suhosin,
        // the post.max_value_length limit might get in the way of the
        // restoration of a course with many items. A value of 1,000,000 bytes
        // might be too short.
        $course = CourseSelectForm::get_posted_course();

    } else {
        if ($_POST['backup_type'] == 'server') {
            $filename = $_POST['backup_server'];
            $delete_file = false;
        } else {
            if ($_FILES['backup']['error'] == 0) {
                $filename = CourseArchiver::importUploadedFile(
                    $_FILES['backup']['tmp_name']
                );
                if ($filename === false) {
                    $error = true;
                } else {
                    $delete_file = true;
                }
            } else {
                $error = true;
            }
        }

        if (!$error) {
            // Full backup
            $course = CourseArchiver::readCourse($filename, $delete_file);
        }
    }

    if (!$error && is_object($course) && $course->has_resources()) {
        $cr = new CourseRestorer($course);
        $cr->set_file_option($_POST['same_file_name_option']);
        $cr->restore();
        Display::addFlash(Display::return_message(get_lang('ImportFinished')));
        echo '<a class="btn btn-default" href="'.api_get_path(WEB_COURSE_PATH).api_get_course_path().'/index.php">'.get_lang('CourseHomepage').'</a>';
    } else {
        if (!$error) {
            Display::addFlash(Display::return_message(get_lang('NoResourcesInBackupFile'), 'warning'));
            echo '<a class="btn btn-default" href="import_backup.php?'.api_get_cidreq().'">'.get_lang('TryAgain').'</a>';
        } elseif ($filename === false) {
            Display::addFlash(Display::return_message(get_lang('ArchivesDirectoryNotWriteableContactAdmin'), 'error'));
            echo '<a class="btn btn-default" href="import_backup.php?'.api_get_cidreq().'">'.get_lang('TryAgain').'</a>';
        } else {
            if ($filename == '') {
                Display::addFlash(Display::return_message(get_lang('SelectBackupFile'), 'error'));
                echo '<a class="btn btn-default" href="import_backup.php?'.api_get_cidreq().'">'.get_lang('TryAgain').'</a>';
            } else {
                Display::addFlash(Display::return_message(get_lang('UploadError'), 'error'));
                echo '<a class="btn btn-default" href="import_backup.php?'.api_get_cidreq().'">'.get_lang('TryAgain').'</a>';
            }
        }
    }
    CourseArchiver::cleanBackupDir();

} elseif (Security::check_token('post') && (
        isset($_POST['import_option']) &&
        $_POST['import_option'] == 'select_items'
    )
) {
    // Clear token
    Security::clear_token();

    if ($_POST['backup_type'] == 'server') {
        $filename = $_POST['backup_server'];
        $delete_file = false;
    } else {
        $filename = CourseArchiver::importUploadedFile($_FILES['backup']['tmp_name']);
        $delete_file = true;
    }
    $course = CourseArchiver::readCourse($filename, $delete_file);

    if ($course->has_resources() && ($filename !== false)) {
        $hiddenFields['same_file_name_option'] = $_POST['same_file_name_option'];
        // Add token to Course select form
        $hiddenFields['sec_token'] = Security::get_token();
        CourseSelectForm::display_form($course, $hiddenFields);
    } elseif ($filename === false) {
        Display::addFlash(Display::return_message(get_lang('ArchivesDirectoryNotWriteableContactAdmin'), 'error'));
        echo '<a class="btn btn-default" href="import_backup.php?'.api_get_cidreq().'">'.get_lang('TryAgain').'</a>';
    } else {
        Display::addFlash(Display::return_message(get_lang('NoResourcesInBackupFile'), 'warning'));
        echo '<a class="btn btn-default" href="import_backup.php?'.api_get_cidreq().'">'.get_lang('TryAgain').'</a>';
    }
} else {
    $user = api_get_user_info();
    $backups = CourseArchiver::getAvailableBackups(
        $is_platformAdmin ? null : $user['user_id']
    );
    $backups_available = count($backups) > 0;

    $form = new FormValidator(
        'import_backup_form',
        'post',
        api_get_path(WEB_CODE_PATH).'coursecopy/import_backup.php?'.api_get_cidreq(),
        '',
        array('enctype' => 'multipart/form-data')
    );
    $form->addElement('header', get_lang('SelectBackupFile'));
    $renderer = $form->defaultRenderer();
    $renderer->setCustomElementTemplate('<div>{element}</div> ');

    $form->addElement('hidden', 'action', 'restore_backup');

    $form->addElement(
        'radio',
        'backup_type',
        '',
        get_lang('LocalFile'),
        'local',
        'id="bt_local" class="checkbox" onclick="javascript: document.import_backup_form.backup_server.disabled=true;document.import_backup_form.backup.disabled=false;"'
    );
    $form->addElement('file', 'backup', '', 'style="margin-left: 50px;"');
    $form->addElement('html', '<br />');

    if ($backups_available) {
        $form->addElement(
            'radio',
            'backup_type',
            '',
            get_lang('ServerFile'),
            'server',
            'id="bt_server" class="checkbox" onclick="javascript: document.import_backup_form.backup_server.disabled=false;document.import_backup_form.backup.disabled=true;"'
        );
        $options['null'] = '-';
        foreach ($backups as $index => $backup) {
            $options[$backup['file']] = $backup['course_code'].' ('.$backup['date'].')';
        }
        $form->addElement(
            'select',
            'backup_server',
            '',
            $options,
            'style="margin-left: 50px;"'
        );
        $form->addElement(
            'html',
            '<script type="text/javascript">document.import_backup_form.backup_server.disabled=true;</script>'
        );
    } else {
        $form->addElement(
            'radio',
            '',
            '',
            '<i>'.get_lang('NoBackupsAvailable').'</i>',
            '',
            'disabled="true"'
        );
    }

    $form->addElement('html', '<br /><br />');

    $form->addElement(
        'radio',
        'import_option',
        '',
        get_lang('ImportFullBackup'),
        'full_backup',
        'id="import_option_1" class="checkbox"'
    );
    $form->addElement(
        'radio',
        'import_option',
        '',
        get_lang('LetMeSelectItems'),
        'select_items',
        'id="import_option_2" class="checkbox"'
    );

    $form->addElement('html', '<br /><br />');

    $form->addElement('html', get_lang('SameFilename'));
    $form->addElement('html', '<br /><br />');
    $form->addElement(
        'radio',
        'same_file_name_option',
        '',
        get_lang('SameFilenameSkip'),
        FILE_SKIP,
        'id="same_file_name_option_1" class="checkbox"'
    );
    $form->addElement(
        'radio',
        'same_file_name_option',
        '',
        get_lang('SameFilenameRename'),
        FILE_RENAME,
        'id="same_file_name_option_2" class="checkbox"'
    );
    $form->addElement(
        'radio',
        'same_file_name_option',
        '',
        get_lang('SameFilenameOverwrite'),
        FILE_OVERWRITE,
        'id="same_file_name_option_3" class="checkbox"'
    );

    $form->addElement('html', '<br />');
    $form->addButtonImport(get_lang('ImportBackup'));
    $values['backup_type'] = 'local';
    $values['import_option'] = 'full_backup';
    $values['same_file_name_option'] = FILE_OVERWRITE;
    $form->setDefaults($values);

    $form->addProgress();
    // When progress bar appears we have to hide the title "Select backup file".
    $form->updateAttributes(array(
        'onsubmit' => str_replace(
            'javascript: ',
            'javascript: page_title = getElementById(\'page_title\'); if (page_title) { setTimeout(\'page_title.style.display = \\\'none\\\';\', 2000); } ',
            $form->getAttribute('onsubmit')
        )
    ));

    // Add Security token
    $token = Security::get_token();
    $form->addElement('hidden', 'sec_token');
    $form->setConstants(array('sec_token' => $token));

    $form->display();
}

Display::display_footer();

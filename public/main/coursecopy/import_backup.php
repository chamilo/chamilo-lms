<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Component\CourseCopy\CourseArchiver;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use Chamilo\CourseBundle\Component\CourseCopy\CourseSelectForm;
use ChamiloSession as Session;

/**
 * Import a backup.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 */
require_once __DIR__.'/../inc/global.inc.php';

$current_course_tool = TOOL_COURSE_MAINTENANCE;
api_protect_course_script(true);

// Check access rights (only teachers are allowed here)
if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

api_set_more_memory_and_time_limits();
$isPlatformAdmin = api_is_platform_admin();

// Section for the tabs
$this_section = SECTION_COURSES;

// Breadcrumbs
$interbreadcrumb[] = [
    'url' => '../course_info/maintenance.php',
    'name' => get_lang('Backup'),
];

// Displaying the header
$nameTools = get_lang('Import backup');
Display::display_header($nameTools);

// Display the tool title
echo Display::page_header($nameTools);

$action = isset($_POST['action']) ? $_POST['action'] : '';
$importOption = isset($_POST['import_option']) ? $_POST['import_option'] : '';

/* MAIN CODE */
$filename = '';
if (Security::check_token('post') && ('course_select_form' === $action || 'full_backup' === $importOption)) {
    // Clear token
    Security::clear_token();

    $error = false;
    if ('course_select_form' === $action) {
        // Partial backup here we recover the documents posted
        $filename = Session::read('backup_file');
        $course = CourseArchiver::readCourse($filename, false);
        $course = CourseSelectForm::get_posted_course(null, null, null, $course);
    } else {
        if ('server' === $_POST['backup_type']) {
            $filename = $_POST['backup_server'];
            $delete_file = false;
        } else {
            if (0 == $_FILES['backup']['error']) {
                $filename = CourseArchiver::importUploadedFile($_FILES['backup']['tmp_name']);
                if (false === $filename) {
                    $error = true;
                } else {
                    $delete_file = false;
                }
                Session::write('backup_file', $filename);
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
        echo Display::return_message(get_lang('Import finished'));
        echo '<a class="btn btn-default" href="'.api_get_course_url(api_get_course_id()).'">'.
            get_lang('Course home').'</a>';
    } else {
        if (!$error) {
            echo Display::return_message(get_lang('There are no resources in backup file'), 'warning');
            echo '<a
                class="btn btn-default"
                href="import_backup.php?'.api_get_cidreq().'">'.get_lang('Try again').'</a>';
        } elseif (false === $filename) {
            echo Display::return_message(
                get_lang(
                    'The app/cache/ directory, used by this tool, is not writeable. Please contact your platform administrator.'
                ),
                'error'
            );
            echo '<a
                class="btn btn-default"
                href="import_backup.php?'.api_get_cidreq().'">'.get_lang('Try again').'</a>';
        } else {
            if ('' == $filename) {
                echo Display::return_message(get_lang('Select a backup file'), 'error');
                echo '<a class="btn btn-default" href="import_backup.php?'.api_get_cidreq().'">'.get_lang('Try again').'</a>';
            } else {
                echo Display::return_message(get_lang('Upload failed, please check maximum file size limits and folder rights.'), 'error');
                echo '<a class="btn btn-default" href="import_backup.php?'.api_get_cidreq().'">'.get_lang('Try again').'</a>';
            }
        }
    }
    CourseArchiver::cleanBackupDir();
} elseif (Security::check_token('post') && 'select_items' === $importOption) {
    // Clear token
    Security::clear_token();

    if ('server' === $_POST['backup_type']) {
        $filename = $_POST['backup_server'];
        $delete_file = false;
    } else {
        $filename = CourseArchiver::importUploadedFile($_FILES['backup']['tmp_name']);
        $delete_file = false;
        Session::write('backup_file', $filename);
    }
    $course = CourseArchiver::readCourse($filename, $delete_file);

    if ($course->has_resources() && false !== $filename) {
        $hiddenFields['same_file_name_option'] = $_POST['same_file_name_option'];
        // Add token to Course select form
        $hiddenFields['sec_token'] = Security::get_token();
        CourseSelectForm::display_form($course, $hiddenFields);
    } elseif (false === $filename) {
        echo Display::return_message(get_lang('The app/cache/ directory, used by this tool, is not writeable. Please contact your platform administrator.'), 'error');
        echo '<a class="btn btn-default" href="import_backup.php?'.api_get_cidreq().'">'.get_lang('Try again').'</a>';
    } else {
        echo Display::return_message(get_lang('There are no resources in backup file'), 'warning');
        echo '<a class="btn btn-default" href="import_backup.php?'.api_get_cidreq().'">'.get_lang('Try again').'</a>';
    }
} else {
    $user = api_get_user_info();
    $backups = CourseArchiver::getAvailableBackups($isPlatformAdmin ? null : $user['user_id']);
    $backups_available = count($backups) > 0;

    $form = new FormValidator(
        'import_backup_form',
        'post',
        api_get_path(WEB_CODE_PATH).'coursecopy/import_backup.php?'.api_get_cidreq(),
        '',
        ['enctype' => 'multipart/form-data']
    );
    $form->addElement('header', get_lang('Select a backup file'));
    $renderer = $form->defaultRenderer();
    $renderer->setCustomElementTemplate('<div>{element}</div> ');

    $form->addElement('hidden', 'action', 'restore_backup');

    $form->addElement(
        'radio',
        'backup_type',
        '',
        get_lang('local file'),
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
            get_lang('server file'),
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
            '<i>'.get_lang('No backup is available').'</i>',
            '',
            'disabled="true"'
        );
    }

    $form->addElement('html', '<br /><br />');

    $form->addElement(
        'radio',
        'import_option',
        '',
        get_lang('Import full backup'),
        'full_backup',
        'id="import_option_1" class="checkbox"'
    );
    $form->addElement(
        'radio',
        'import_option',
        '',
        get_lang('Let me select learning objects'),
        'select_items',
        'id="import_option_2" class="checkbox"'
    );

    $form->addElement('html', '<br /><br />');

    $form->addElement('html', get_lang('What should be done with imported files with the same file name as existing files?'));
    $form->addElement('html', '<br /><br />');
    $form->addElement(
        'radio',
        'same_file_name_option',
        '',
        get_lang('What should be done with imported files with the same file name as existing files?Skip'),
        FILE_SKIP,
        'id="same_file_name_option_1" class="checkbox"'
    );
    $form->addElement(
        'radio',
        'same_file_name_option',
        '',
        get_lang('What should be done with imported files with the same file name as existing files?Rename'),
        FILE_RENAME,
        'id="same_file_name_option_2" class="checkbox"'
    );
    $form->addElement(
        'radio',
        'same_file_name_option',
        '',
        get_lang('What should be done with imported files with the same file name as existing files?Overwrite'),
        FILE_OVERWRITE,
        'id="same_file_name_option_3" class="checkbox"'
    );

    $form->addElement('html', '<br />');
    $form->addButtonImport(get_lang('Import backup'));
    $values['backup_type'] = 'local';
    $values['import_option'] = 'full_backup';
    $values['same_file_name_option'] = FILE_OVERWRITE;
    $form->setDefaults($values);

    $form->addProgress();
    // When progress bar appears we have to hide the title "Select backup file".
    $form->updateAttributes([
        'onsubmit' => str_replace(
            'javascript: ',
            'javascript: page_title = getElementById(\'page_title\'); if (page_title) { setTimeout(\'page_title.style.display = \\\'none\\\';\', 2000); } ',
            $form->getAttribute('onsubmit')
        ),
    ]);

    // Add Security token
    $token = Security::get_token();
    $form->addElement('hidden', 'sec_token');
    $form->setConstants(['sec_token' => $token]);
    $form->display();
}

if (!isset($_POST['action'])) {
    Session::erase('backup_file');
}

Display::display_footer();

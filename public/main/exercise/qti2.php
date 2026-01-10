<?php

/* For licensing terms, see /license.txt */

/**
 * Code for Qti2 import integration.
 *
 * @author Ronny Velasquez
 *
 * @version $Id: qti2.php  2010-03-12 12:14:25Z $
 */

use Chamilo\CoreBundle\Enums\ActionIcon;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

// section (for the tabs)
$this_section = SECTION_COURSES;

// access restriction: only teachers are allowed here
if (!api_is_allowed_to_edit(null, true)) {
    api_not_allowed(true);
}

// the breadcrumbs
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq(),
    'name' => get_lang('Tests'),
];
$is_allowedToEdit = api_is_allowed_to_edit(null, true);

/**
 * This function displays the form to import the zip file with qti2.
 */
function displayForm()
{
    $form = '<div class="actions">';
    $form .= '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/exercise.php?show=test&'.api_get_cidreq().'">'.
        Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, sprintf(get_lang('Back to %s'), get_lang('Test list'))).'</a>';
    $form .= '</div>';
    $formValidator = new FormValidator(
        'qti_upload',
        'post',
        api_get_self().'?'.api_get_cidreq(),
        null,
        ['enctype' => 'multipart/form-data']
    );
    $formValidator->addHeader(get_lang('Import exercises Qti2'));
    $formValidator->addElement('file', 'userFile', get_lang('Download file'));
    $formValidator->addButtonImport(get_lang('Upload'));
    $form .= $formValidator->returnForm();
    echo $form;
}

/**
 * This function will import the zip file with the respective qti2.
 *
 * @param array $array_file ($_FILES)
 *
 * @return string|array
 */
function importFile($array_file)
{
    $unzip = 0;
    $process = process_uploaded_file($array_file, false);

    if (preg_match('/\.zip$/i', $array_file['name'])) {
        // if it's a zip, allow zip upload
        $unzip = 1;
    }

    if ($process && 1 == $unzip) {
        $main_path = api_get_path(SYS_CODE_PATH);
        require_once $main_path.'exercise/export/exercise_import.inc.php';

        // Move upload to a real temp location keeping the .zip name
        $permDirs = api_get_permissions_for_new_directories();
        $tmpBase = api_get_path(SYS_ARCHIVE_PATH).'qti2_import/'.api_get_unique_id().'/';

        if (!is_dir($tmpBase)) {
            mkdir($tmpBase, $permDirs, true);
        }

        $targetPath = $tmpBase.$array_file['name'];
        if (!move_uploaded_file($array_file['tmp_name'], $targetPath)) {
            if (!copy($array_file['tmp_name'], $targetPath)) {
                return 'FileError';
            }
        }

        return import_exercise($targetPath);
    }

    return 'FileError';
}

$message = null;

// import file
if (api_is_allowed_to_edit(null, true)) {
    if (isset($_POST['submit'])) {
        $imported = importFile($_FILES['userFile']);

        if (is_numeric($imported) && !empty($imported)) {
            header('Location: '.api_get_path(WEB_CODE_PATH).'exercise/admin.php?'.api_get_cidreq().'&exerciseId='.$imported);
            exit;
        } else {
            $message = Display::return_message(get_lang($imported));
        }
    }
}

Display::display_header(get_lang('Import exercises Qti2'), 'Exercises');

echo $message;

// display qti form
displayForm();

Display::display_footer();

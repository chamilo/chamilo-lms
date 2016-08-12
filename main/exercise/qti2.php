<?php
/* For licensing terms, see /license.txt */
/**
 *	Code for Qti2 import integration.
 *	@package chamilo.exercise
 * 	@author Ronny Velasquez
 * 	@version $Id: qti2.php  2010-03-12 12:14:25Z $
 */

// including the global Chamilo file
require_once '../inc/global.inc.php';

// section (for the tabs)
$this_section = SECTION_COURSES;

// access restriction: only teachers are allowed here
if (!api_is_allowed_to_edit(null, true)) {
    api_not_allowed();
}

// the breadcrumbs
$interbreadcrumb[]= array (
    "url" => api_get_path(WEB_CODE_PATH) . "exercise/exercise.php?".api_get_cidreq(),
    "name" => get_lang('Exercises')
);
$is_allowedToEdit = api_is_allowed_to_edit(null, true);

/**
 * This function displays the form to import the zip file with qti2
 */
function ch_qti2_display_form()
{
    $name_tools = get_lang('ImportQtiQuiz');
    $form  = '<div class="actions">';
    $form .= '<a href="' . api_get_path(WEB_CODE_PATH) . 'exercise/exercise.php?show=test&'.api_get_cidreq().'">'.
        Display :: return_icon('back.png', get_lang('BackToExercisesList'), '', ICON_SIZE_MEDIUM).'</a>';
    $form .= '</div>';
    $formValidator = new FormValidator(
        'qti_upload',
        'post',
        api_get_self()."?".api_get_cidreq(),
        null,
        array('enctype' => 'multipart/form-data')
    );
    $formValidator->addElement('header', $name_tools);
    $formValidator->addElement('file', 'userFile', get_lang('DownloadFile'));
    $formValidator->addButtonImport(get_lang('Upload'));
    $form .= $formValidator->returnForm();
    echo $form;
}

/**
 * This function will import the zip file with the respective qti2
 * @param array $array_file ($_FILES)
 * @return string|array
 */
function ch_qti2_import_file($array_file)
{
    $unzip = 0;
    $process = process_uploaded_file($array_file, false);

    if (preg_match('/\.zip$/i', $array_file['name'])) {
        // if it's a zip, allow zip upload
        $unzip = 1;
    }

    if ($process && $unzip == 1) {
        $main_path = api_get_path(SYS_CODE_PATH);
        require_once $main_path.'exercise/export/exercise_import.inc.php';
        require_once $main_path.'exercise/export/qti2/qti2_classes.php';

        return import_exercise($array_file['name']);
    }

    return 'langFileError';
}

$message = null;

// import file
if ((api_is_allowed_to_edit(null, true))) {
    if (isset($_POST['submit'])) {
        $imported = ch_qti2_import_file($_FILES['userFile']);

        if (is_numeric($imported) && !empty($imported)) {
            header('Location: '.api_get_path(WEB_CODE_PATH) . 'exercise/admin.php?'.api_get_cidreq().'&exerciseId='.$imported);
            exit;
        } else {
            $message = Display::return_message(get_lang($imported));
        }
    }
}

// Display header
Display::display_header(get_lang('ImportQtiQuiz'), 'Exercises');

echo $message;

// display qti form
ch_qti2_display_form();

// display the footer
Display::display_footer();

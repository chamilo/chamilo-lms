<?php
/* For licensing terms, see /license.txt */
/**
 * Code for HotPotatoes integration.
 * @package chamilo.exercise
 * @author Istvan Mandak (original author)
 */
/**
 * Code
 */
// Name of the language file that needs to be included.
$language_file ='exercice';

// Including the global initialization file.
require_once '../inc/global.inc.php';

// Including additional libraries.
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'pclzip/pclzip.lib.php';
require_once 'hotpotatoes.lib.php';

// Section (for the tabs).
$this_section = SECTION_COURSES;

// Access restriction: only teachers are allowed here.
if (!api_is_allowed_to_edit(null, true)) {
    api_not_allowed();
}

if (isset($_SESSION['gradebook'])) {
    $gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array(
            'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
            'name' => get_lang('ToolGradebook')
        );
}
// The breadcrumbs.
$interbreadcrumb[] = array('url' => './exercice.php', 'name' => get_lang('Exercices'));

$is_allowedToEdit = api_is_allowed_to_edit(null, true);

// Database table definitions.
$dbTable        = Database::get_course_table(TABLE_DOCUMENT);
$course_id = api_get_course_int_id();

// Setting some variables.
$document_sys_path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
$uploadPath = '/HotPotatoes_files';
$finish         = (!empty($_POST['finish']) ? $_POST['finish'] : 0);
$imgcount       = (!empty($_POST['imgcount']) ? $_POST['imgcount'] : null);
$fld            = (!empty($_POST['fld']) ? $_POST['fld'] : null);

// If user is allowed to edit...
if (api_is_allowed_to_edit(null, true)) {
    if (hotpotatoes_init($document_sys_path.$uploadPath)) {
        // If the directory doesn't exist, create the "HotPotatoes" directory.
        $doc_id = add_document($_course, '/HotPotatoes_files', 'folder', 0, get_lang('HotPotatoesFiles'));
        // Update properties in dbase (in any case).
        api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderCreated', api_get_user_id());
        // Make invisible (in any case) - why?
        api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'invisible', api_get_user_id());
    }
}

/** Display */
$nameTools = get_lang('HotPotatoesTests');

$form = new FormValidator('hotpotatoes', 'post', api_get_self()."?".api_get_cidreq(), null, array('enctype' => 'multipart/form-data'));    
$form->addElement('header', $nameTools);
$form->addElement('hidden', 'uploadPath');
$form->addElement('hidden', 'fld', $fld);
$form->addElement('hidden', 'imgcount', $imgcount);
$form->addElement('hidden', 'finish', $finish);

$form->addElement('html', GenerateHiddenList($imgparams));

$form->addElement('advanced_settings', Display::return_icon('hotpotatoes.jpg', get_lang('HotPotatoes')));

$label = get_lang('DownloadImg').' : ';
if ($finish == 0) {
    $label = get_lang('DownloadFile').' : ';
}    

$form->addElement('file', 'userFile', $label);
$form->addElement('button', 'submit', get_lang('SendFile'));


// If finish is set; it's because the user came from this script in the first place (displaying hidden "finish" field).
if ((api_is_allowed_to_edit(null, true)) && (($finish == 0) || ($finish == 2))) {
    

    // Moved this down here as the upload handling functions give output.
    if ($form->validate()) {

        $max_filled_space = DocumentManager::get_course_quota();

        //initialise $finish
        if (!isset($finish)) { $finish = 0; }

        //if the size is not defined, it's probably because there has been an error or no file was submitted
        if (!$_FILES['userFile']['size']) {
            $dialogBox .= get_lang('SendFileError').'<br />'.get_lang('Notice').' : '.get_lang('MaxFileSize').' '.ini_get('upload_max_filesize');
        } else {
            $unzip = 0;
            if (preg_match('/\.zip$/i', $_FILES['userFile']['name'])) {
                //if it's a zip, allow zip upload
                $unzip = 1;
            }

            if ($finish == 0) {
                // Generate new test folder if on first step of file upload.
                $filename = replace_dangerous_char(trim($_FILES['userFile']['name']), 'strict');
                $fld = GenerateHpFolder($document_sys_path.$uploadPath.'/');

                //$doc_id = add_document($_course, '/HotPotatoes_files/'.$fld, 'folder', 0, $fld);
                //api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderCreated', api_get_user_id());
                @mkdir($document_sys_path.$uploadPath.'/'.$fld, api_get_permissions_for_new_directories());
                $doc_id = add_document($_course, '/HotPotatoes_files/'.$fld, 'folder', 0, $fld);                    
                api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderCreated', api_get_user_id());
            } else {
                // It is not the first step... get the filename directly from the system params.
                $filename = $_FILES['userFile']['name'];
            }

            /*if (treat_uploaded_file($_FILES['userFile'], $document_sys_path, $uploadPath."/".$fld, $max_filled_space, $unzip))*/
            $allow_output_on_success = false;
            if (handle_uploaded_document($_course, $_FILES['userFile'], $document_sys_path, $uploadPath.'/'.$fld, api_get_user_id(), null, null, $max_filled_space, $unzip, '', $allow_output_on_success)) {

                if ($finish == 2) {
                    $imgparams = $_POST['imgparams'];
                    $checked = CheckImageName($imgparams, $filename);
                    if ($checked) { $imgcount = $imgcount-1; }
                    else {
                        $dialogBox .= $filename.' '.get_lang('NameNotEqual');
                        my_delete($document_sys_path.$uploadPath.'/'.$fld.'/'.$filename);
                        update_db_info('delete', $uploadPath.'/'.$fld.'/'.$filename);
                    }
                    if ($imgcount == 0) { // all image uploaded
                        $finish = 1;
                    }
                } else {
                    // If we are (still) on the first step of the upload process.
                    if ($finish == 0) {
                        $finish = 2;
                        // Get number and name of images from the files contents.
                        GetImgParams('/'.$filename, $document_sys_path.$uploadPath.'/'.$fld, $imgparams, $imgcount);
                        if ($imgcount == 0) // There is no img link, so finish the upload process.
                            { $finish = 1; }
                        else // There is still one or more img missing.
                            { $dialogBox .= get_lang('DownloadEnd'); }
                    }
                }

                $title = @htmlspecialchars(GetQuizName($filename, $document_sys_path.$uploadPath.'/'.$fld.'/'), ENT_COMPAT, api_get_system_encoding());
                $query = "UPDATE $dbTable SET comment='".Database::escape_string($title)."' WHERE c_id = $course_id AND path=\"".$uploadPath."/".$fld."/".$filename."\"";
                Database::query($query);
                api_item_property_update($_course, TOOL_QUIZ, $id, 'QuizAdded', api_get_user_id());

            } else {
                if ($finish == 2) {
                    // delete?
                    //$dialogBox .= get_lang('NoImg');
                }
                $finish = 0;    // error
                if (api_failure::get_last_failure() == 'not_enough_space') {
                    $dialogBox .= get_lang('NoSpace');
                } elseif (api_failure::get_last_failure() == 'php_file_in_zip_file') {
                    $dialogBox .= get_lang('ZipNoPhp');
                }
            }
        }        
    }
    if ($finish == 1) { /** ok -> send to main exercises page */
        header('Location: exercice.php?'.api_get_cidreq());
        exit;
    }

    Display::display_header($nameTools, get_lang('Exercise'));

    echo '<div class="actions">';
    echo '<a href="exercice.php?show=test">'.Display :: return_icon('back.png', get_lang('BackToExercisesList'),'',ICON_SIZE_MEDIUM).'</a>';
    echo '</div>';

    if ($finish==2) { // If we are in the img upload process.
        $dialogBox .= get_lang('ImgNote_st').$imgcount.get_lang('ImgNote_en').'<br />';
        while (list($key, $string) = each($imgparams)) {
            $dialogBox .= $string.'; ';
        }
    }

    if ($dialogBox) {
        Display::display_normal_message($dialogBox, false);
    }
    
    $form->display();    
}
// Display the footer.
Display::display_footer();

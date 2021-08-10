<?php
/* For licensing terms, see /license.txt */

/**
 * Code for HotPotatoes integration.
 *
 * @package chamilo.exercise
 *
 * @author Istvan Mandak (original author)
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

require_once 'hotpotatoes.lib.php';

// Section (for the tabs).
$this_section = SECTION_COURSES;
$_course = api_get_course_info();

// Access restriction: only teachers are allowed here.
if (!api_is_allowed_to_edit(null, true)) {
    api_not_allowed();
}

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('ToolGradebook'),
    ];
}
// The breadcrumbs.
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq(),
    'name' => get_lang('Exercises'),
];

$is_allowedToEdit = api_is_allowed_to_edit(null, true);

// Database table definitions.
$dbTable = Database::get_course_table(TABLE_DOCUMENT);
$course_id = $_course['real_id'];
$sessionId = api_get_session_id();

// Setting some variables.
$document_sys_path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
$uploadPath = '/HotPotatoes_files';
$finish = (!empty($_POST['finish']) ? $_POST['finish'] : 0);
$imgcount = (!empty($_POST['imgcount']) ? $_POST['imgcount'] : null);
$fld = (!empty($_POST['fld']) ? $_POST['fld'] : null);
$imgparams = [];
$dialogBox = '';

if ($finish == 2 && isset($_POST['imgparams'])) {
    $imgparams = $_POST['imgparams'];
}

// If user is allowed to edit...
if (api_is_allowed_to_edit(null, true)) {
    if (hotpotatoes_init($document_sys_path.$uploadPath)) {
        // If the directory doesn't exist, create the "HotPotatoes" directory.
        $doc_id = add_document(
            $_course,
            '/HotPotatoes_files',
            'folder',
            0,
            get_lang('HotPotatoesFiles')
        );
        // Update properties in dbase (in any case).
        api_item_property_update(
            $_course,
            TOOL_DOCUMENT,
            $doc_id,
            'FolderCreated',
            api_get_user_id()
        );
        // Make invisible (in any case) - why?
        api_item_property_update(
            $_course,
            TOOL_DOCUMENT,
            $doc_id,
            'invisible',
            api_get_user_id()
        );
    }
}

/** Display */
$nameTools = get_lang('HotPotatoesTests');

$form = new FormValidator(
    'hotpotatoes',
    'post',
    api_get_self()."?".api_get_cidreq(),
    null,
    ['enctype' => 'multipart/form-data']
);
$form->addElement('header', $nameTools);
$form->addElement('hidden', 'uploadPath');
$form->addElement('hidden', 'fld', $fld);
$form->addElement('hidden', 'imgcount', $imgcount);
$form->addElement('hidden', 'finish', $finish);
$form->addElement('html', GenerateHiddenList($imgparams));
$form->addElement('label', '', Display::return_icon('hotpotatoes.jpg', 'HotPotatoes'));
$label = get_lang('DownloadImg').' : ';
if ($finish == 0) {
    $label = get_lang('DownloadFile').' : ';
}

$form->addElement('file', 'userFile', $label);
$form->addButtonSend(get_lang('SendFile'));

// If finish is set; it's because the user came from this script in the first place (displaying hidden "finish" field).
if ((api_is_allowed_to_edit(null, true)) && (($finish == 0) || ($finish == 2))) {
    // Moved this down here as the upload handling functions give output.
    if ($form->validate()) {
        // Initialise $finish
        if (!isset($finish)) {
            $finish = 0;
        }

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
                $filename = api_replace_dangerous_char(trim($_FILES['userFile']['name']));
                $fld = GenerateHpFolder($document_sys_path.$uploadPath.'/');
                @mkdir($document_sys_path.$uploadPath.'/'.$fld, api_get_permissions_for_new_directories());
                $doc_id = add_document($_course, '/HotPotatoes_files/'.$fld, 'folder', 0, $fld);
                api_item_property_update(
                    $_course,
                    TOOL_DOCUMENT,
                    $doc_id,
                    'FolderCreated',
                    api_get_user_id()
                );
            } else {
                // It is not the first step... get the filename directly from the system params.
                $filename = $_FILES['userFile']['name'];
            }

            $allow_output_on_success = false;
            if (handle_uploaded_document(
                $_course,
                $_FILES['userFile'],
                $document_sys_path,
                $uploadPath.'/'.$fld,
                api_get_user_id(),
                null,
                null,
                $unzip,
                '',
                $allow_output_on_success
            )) {
                if ($finish == 2) {
                    $imgparams = $_POST['imgparams'];
                    $checked = CheckImageName($imgparams, $filename);
                    if ($checked) {
                        $imgcount = $imgcount - 1;
                    } else {
                        $dialogBox .= $filename.' '.get_lang('NameNotEqual');
                        my_delete($document_sys_path.$uploadPath.'/'.$fld.'/'.$filename);
                        DocumentManager::updateDbInfo('delete', $uploadPath.'/'.$fld.'/'.$filename);
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
                        if ($imgcount == 0) {
                            // There is no img link, so finish the upload process.
                            $finish = 1;
                        } else {
                            // There is still one or more img missing.
                            $dialogBox .= get_lang('DownloadEnd');
                        }
                    }
                }

                // Set the HotPotatoes title in the document record's "comment" field
                $title = @htmlspecialchars(
                    GetQuizName(
                        $filename,
                        $document_sys_path.$uploadPath.'/'.$fld.'/'
                    ),
                    ENT_COMPAT,
                    api_get_system_encoding()
                );
                $sessionAnd = ' AND session_id IS NULL ';
                if ($sessionId) {
                    $sessionAnd = " AND session_id = $sessionId ";
                }
                $path = $uploadPath.'/'.$fld.'/'.$filename;
                // Find the proper record
                $select = "SELECT iid FROM $dbTable
                          WHERE c_id = $course_id
                          $sessionAnd
                          AND path = '$path'";
                $query = Database::query($select);
                if (Database::num_rows($query)) {
                    $row = Database::fetch_array($query);
                    $hotPotatoesDocumentId = $row['iid'];
                    // Update the record with the 'comment' (HP title)
                    $query = "UPDATE $dbTable
                          SET comment = '".Database::escape_string($title)."'
                          WHERE iid = $hotPotatoesDocumentId";
                    Database::query($query);
                    // Mark the addition of the HP quiz in the item_property table
                    api_item_property_update(
                        $_course,
                        TOOL_QUIZ,
                        $hotPotatoesDocumentId,
                        'QuizAdded',
                        api_get_user_id()
                    );
                }
            } else {
                $finish = 0;
            }
        }
    }

    if ($finish == 1) {
        /** ok -> send to main exercises page */
        header('Location: exercise.php?'.api_get_cidreq());
        exit;
    }

    Display::display_header($nameTools, get_lang('Exercise'));

    echo '<div class="actions">';
    echo '<a href="exercise.php?show=test">'.
        Display::return_icon('back.png', get_lang('BackToExercisesList'), '', ICON_SIZE_MEDIUM).
        '</a>';
    echo '</div>';

    if ($finish == 2) {
        // If we are in the img upload process.
        $dialogBox .= get_lang('ImgNote_st').$imgcount.get_lang('ImgNote_en').'<br />';
        foreach ($imgparams as $key => $string) {
            $dialogBox .= $string.'; ';
        }
    }

    if ($dialogBox) {
        echo Display::return_message($dialogBox, 'normal', false);
    }

    $form->display();
}
// Display the footer.
Display::display_footer();

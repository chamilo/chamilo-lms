<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This is a learning path creation and player tool in Chamilo - previously learnpath_handler.php.
 *
 * @author Patrick Cool
 * @author Denes Nagy
 * @author Roan Embrechts, refactoring and code cleaning
 * @author Yannick Warnier <ywarnier@beeznest.org> - cleaning and update for new SCORM tool
 */
$this_section = SECTION_COURSES;

api_protect_course_script();

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

/** @var learnpath $learnPath */
$learnPath = Session::read('oLP');

$tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
$tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);

$isStudentView = isset($_REQUEST['isStudentView']) ? (int) $_REQUEST['isStudentView'] : null;
$learnpath_id = (int) $_REQUEST['lp_id'];
$submit = isset($_POST['submit_button']) ? $_POST['submit_button'] : null;
$_course = api_get_course_info();

if (!$is_allowed_to_edit || $isStudentView) {
    header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
    exit;
}

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}

$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('Learning paths'),
];
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=add_item&lp_id=$learnpath_id&".api_get_cidreq(),
    "name" => Security::remove_XSS($learnPath->getNameNoTags()),
];
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=add_item&type=step&lp_id=$learnpath_id&".api_get_cidreq(),
    'name' => get_lang('Add learning object or activity'),
];

if (isset($_REQUEST['updateaudio'])) {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Add audio')];
} else {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Organize')];
}

$htmlHeadXtra[] = '<script>'.$learnPath->get_js_dropdown_array().'</script>';
// Theme calls.
$show_learn_path = true;
$lp_theme_css = $learnPath->get_theme();

// POST action handling (uploading mp3, deleting mp3)
if (isset($_POST['save_audio'])) {
    //Updating the lp.modified_on
    $learnPath->set_modified_on();

    $lp_items_to_remove_audio = [];
    $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
    // Deleting the audio fragments.
    foreach ($_POST as $key => $value) {
        if ('removemp3' == substr($key, 0, 9)) {
            $lp_items_to_remove_audio[] = str_ireplace('removemp3', '', $key);
            // Removing the audio from the learning path item.
            $in = implode(',', $lp_items_to_remove_audio);
        }
    }
    if (count($lp_items_to_remove_audio) > 0) {
        $sql = "UPDATE $tbl_lp_item SET audio = ''
                WHERE iid IN (".$in.")";
        Database::query($sql);
    }

    // Uploading the audio files.
    DocumentManager::createDefaultAudioFolder($_course);

    // Uploading the audio files.
    foreach ($_FILES as $key => $value) {
        if ('mp3file' == substr($key, 0, 7) &&
            !empty($_FILES[$key]['tmp_name'])
        ) {
            // The id of the learning path item.
            $lp_item_id = str_ireplace('mp3file', '', $key);

            $file_name = $_FILES[$key]['name'];
            $file_name = stripslashes($file_name);
            // Add extension to files without one (if possible).
            $file_name = add_ext_on_mime($file_name, $_FILES[$key]['type']);
            $clean_name = api_replace_dangerous_char($file_name);
            // No "dangerous" files.
            $clean_name = disable_dangerous_file($clean_name);
            $check_file_path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/audio/'.$clean_name;

            // If the file exists we generate a new name.
            if (file_exists($check_file_path)) {
                $filename_components = explode('.', $clean_name);
                // Gettting the extension of the file.
                $file_extension = $filename_components[count($filename_components) - 1];
                // Adding something random to prevent overwriting.
                $filename_components[count($filename_components) - 1] = time();
                // Reconstructing the new filename.
                $clean_name = implode($filename_components).'.'.$file_extension;
                // Using the new name in the $_FILES superglobal.
                $_FILES[$key]['name'] = $clean_name;
            }
            $filePath = null;
            // Upload the file in the documents tool.
            /*$filePath = handle_uploaded_document(
                $_course,
                $_FILES[$key],
                api_get_path(SYS_COURSE_PATH).$_course['path'].'/document',
                '/audio',
                api_get_user_id(),
                '',
                '',
                '',
                '',
                false
            );*/

            // Store the mp3 file in the lp_item table.
            $sql = "UPDATE $tbl_lp_item
                    SET audio = '".Database::escape_string($filePath)."'
                    WHERE iid = ".(int) $lp_item_id;
            Database::query($sql);
        }
    }
    //echo Display::return_message(get_lang('Item updated'), 'confirm');
    Display::addFlash(Display::return_message(get_lang('ItemUpdated'), 'confirm'));
    $url = api_get_self().'?action=add_item&type=step&lp_id='.$learnPath->get_id().'&'.api_get_cidreq();
    header('Location: '.$url);
    exit;
}

$right = '';
switch ($_GET['action']) {
    case 'edit_item':
        if (isset($is_success) && true === $is_success) {
            $right .= Display::return_message(
                get_lang('The learning object has been edited'),
                'confirm'
            );
        } else {
            $right .= $learnPath->display_edit_item($lpItem);
        }
        break;
    case 'delete_item':
        if (isset($is_success) && true === $is_success) {
            $right .= Display::return_message(
                get_lang('The learning object has been deleted'),
                'confirm'
            );
        }
        break;
}
if (!empty($_GET['updateaudio'])) {
    // list of items to add audio files
    $right .= $learnPath->overview();
}

$tpl = new Template(get_lang('Prerequisites'));
$tpl->assign('actions', $learnPath->build_action_menu(true));
$tpl->assign('left', $learnPath->showBuildSideBar());
$tpl->assign('right', $right);
$tpl->displayTwoColTemplate();

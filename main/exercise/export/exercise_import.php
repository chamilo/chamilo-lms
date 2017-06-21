<?php
/* For licensing terms, see /license.txt */

/**
 * @copyright (c) 2001-2006 Universite catholique de Louvain (UCL)
 * @package chamilo.exercise
 * @author claro team <cvs@claroline.net>
 * @deprecated
 */
require '../../inc/global.inc.php';

//SECURITY CHECK

if (api_is_platform_admin()) {
    api_not_allowed();
}

require_once 'exercise_import.inc.php';

$tbl_exercise = Database::get_course_table(TABLE_QUIZ_TEST);
$tbl_question = Database::get_course_table(TABLE_QUIZ_QUESTION);
$tbl_rel_exercise_question = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);

$nameTools = get_lang('ImportExercise');
$interbredcrump[] = array('url' => '../exercise.php', 'name' => get_lang('Exercises'));

// EXECUTE COMMAND
$cmd = (isset($_REQUEST['cmd']) ? $_REQUEST['cmd'] : 'show_import');

switch ($cmd) {
    case 'show_import':
        $display = '<p>'
            .get_lang('Imported exercises must consist of a zip or an XML file (IMS-QTI) and be compatible with your Claroline version.').'<br>'
            .'</p>'
            .'<form enctype="multipart/form-data" action="" method="post">'
            .'<input name="cmd" type="hidden" value="import" />'
            .'<input name="uploadedExercise" type="file" /><br><br>'
            .get_lang('Import exercise').' : '
            .'<input value="'.get_lang('Ok').'" type="submit" /> '
            .claro_html_button($_SERVER['PHP_SELF'], get_lang('Cancel'))
            .'<br><br>'
            .'<small>'.get_lang('Max file size').' :  2&nbsp;MB</small>'
            .'</form>';
        break;
    case 'import':
        //include needed librabries for treatment
        $result_log = import_exercise($_FILES['uploadedExercise']['name']);
        //display the result message (fail or success)
        $dialogBox = '';
        foreach ($result_log as $log) {
            $dialogBox .= $log.'<br>';
        }
        break;
}


// DISPLAY
include api_get_path(SYS_INC_PATH).'/header.inc.php';

// Tool introduction
// TODO: These settings to be checked when it is possible.
Display::display_introduction_section(
    TOOL_QUIZ,
    array(
        'CreateDocumentWebDir' => api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/',
        'CreateDocumentDir' => '../../..'.api_get_path(REL_COURSE_PATH).api_get_course_path().'/document/',
        'BaseHref' => api_get_path(WEB_COURSE_PATH).api_get_course_path().'/'
    )
);

// Display Forms or dialog box(if needed)
if (isset($dialogBox)) {
    echo Display::return_message($dialogBox, 'normal', false);
}

if (isset($display)) {
    echo $display;
}

include api_get_path(SYS_INC_PATH).'/footer.inc.php';

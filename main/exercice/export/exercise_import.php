<?php // $Id:  $
/**
 * @copyright (c) 2001-2006 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package dokeos.exercise
 * @author claro team <cvs@claroline.net>
 */

require '../../inc/global.inc.php';

//SECURITY CHECK

if ( api_is_platform_admin() ) api_not_allowed();

//DECLARE NEEDED LIBRARIES

require_once api_get_path(LIBRARY_PATH) . 'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php';

require_once 'exercise_import.inc.php';
include_once '../exercise.class.php';
include_once '../question.class.php';
include_once 'qti/qti_classes.php';

//SQL table name

$tbl_exercise              = Database::get_course_table(TABLE_QUIZ_TEST);
$tbl_question              = Database::get_course_table(TABLE_QUIZ_QUESTION);
$tbl_rel_exercise_question = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);

// tool libraries

include_once '../exercise.class.php';

//Tool title

$nameTools = get_lang('ImportExercise');

//bredcrump

$interbredcrump[]= array ('url' => '../exercise.php','name' => get_lang('Exercises'));

//----------------------------------
// EXECUTE COMMAND
//----------------------------------

$cmd = (isset($_REQUEST['cmd'])? $_REQUEST['cmd'] : 'show_import');

switch ( $cmd )
{
    case 'show_import' :
    {
        $display = '<p>'
        .            get_lang('Imported exercises must consist of a zip or an XML file (IMS-QTI) and be compatible with your Claroline version.') . '<br>'
        .            '</p>'
        .            '<form enctype="multipart/form-data" action="" method="post">'
        .            '<input name="cmd" type="hidden" value="import" />'
        .            '<input name="uploadedExercise" type="file" /><br><br>'
        .            get_lang('Import exercise') . ' : '
        .            '<input value="' . get_lang('Ok') . '" type="submit" /> '
        .            claro_html_button( $_SERVER['PHP_SELF'], get_lang('Cancel'))
        .            '<br><br>'
        .            '<small>' . get_lang('Max file size') . ' :  2&nbsp;MB</small>'
        .            '</form>';
    }
    break;

    case 'import' :
    {
        //include needed librabries for treatment

        $result_log = import_exercise($_FILES['uploadedExercise']['name']);
       
        //display the result message (fail or success)

        $dialogBox = '';

        foreach ($result_log as $log)
        {
            $dialogBox .= $log . '<br>';
        }

    }
    break;
}

//----------------------------------
// FIND INFORMATION
//----------------------------------

//empty!

//----------------------------------
// DISPLAY
//----------------------------------

include api_get_path(INCLUDE_PATH) . '/header.inc.php';

//display title


// Tool introduction
// TODO: These settings to be checked when it is possible.
Display::display_introduction_section(TOOL_QUIZ, array(
		'CreateDocumentWebDir' => api_get_path('WEB_COURSE_PATH').api_get_course_path().'/document/',
		'CreateDocumentDir' => '../../../courses/'.api_get_course_path().'/document/',
		'BaseHref' => api_get_path('WEB_COURSE_PATH').api_get_course_path().'/'
	)
);


//Display Forms or dialog box(if needed)

if ( isset($dialogBox) ) echo Display::display_normal_message($dialogBox,false);

//display content

if (isset($display) ) echo $display;

//footer display

include api_get_path(INCLUDE_PATH) . '/footer.inc.php';
?>

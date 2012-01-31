<?php
/* For licensing terms, see /license.txt */
/**
*	Exercise list: This script shows the list of exercises for administrators and students.
*	@package chamilo.exercise
*	@author Olivier Brouckaert, original author
*	@author Denes Nagy, HotPotatoes integration
*	@author Wolfgang Schneider, code/html cleanup
*	@author Julio Montoya <gugli100@gmail.com>, lots of cleanup + several improvements
* Modified by hubert.borderiou (question category)
*/
/**
 * Code
 */
// name of the language file that needs to be included
$language_file = array('exercice','tracking');

// including the global library
require_once '../inc/global.inc.php';
require_once '../gradebook/lib/be.inc.php';

// Setting the tabs
$this_section = SECTION_COURSES;

$htmlHeadXtra[] = api_get_jquery_ui_js();
$htmlHeadXtra[] = api_get_js('qtip2/jquery.qtip.min.js');
$htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/qtip2/jquery.qtip.min.css');

// Access control
api_protect_course_script(true);

// including additional libraries
require_once 'exercise.class.php';
require_once 'exercise.lib.php';
require_once 'question.class.php';
require_once 'answer.class.php';
require_once 'testcategory.class.php';
require_once api_get_path(LIBRARY_PATH) . 'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php';
require_once 'hotpotatoes.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'document.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'mail.lib.inc.php';
require_once api_get_path(LIBRARY_PATH)."groupmanager.lib.php"; // for group filtering

/*	Constants and variables */
$is_allowedToEdit 			= api_is_allowed_to_edit(null,true);
$is_tutor 					= api_is_allowed_to_edit(true);
$is_tutor_course 			= api_is_course_tutor();

$TBL_DOCUMENT 				= Database :: get_course_table(TABLE_DOCUMENT);
$TBL_ITEM_PROPERTY 			= Database :: get_course_table(TABLE_ITEM_PROPERTY);
$TBL_EXERCICE_QUESTION 		= Database :: get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_EXERCICES 				= Database :: get_course_table(TABLE_QUIZ_TEST);
$TBL_TRACK_EXERCICES 		= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$table_lp_item              = Database::get_course_table(TABLE_LP_ITEM);

// document path
$documentPath = api_get_path(SYS_COURSE_PATH) . $_course['path'] . "/document";
// picture path
$picturePath  = $documentPath . '/images';
// audio path
$audioPath    = $documentPath . '/audio';

// hotpotatoes
$uploadPath     = DIR_HOTPOTATOES; //defined in main_api
$exercicePath   = api_get_self();
$exfile         = explode('/', $exercicePath);
$exfile         = strtolower($exfile[sizeof($exfile) - 1]);
$exercicePath   = substr($exercicePath, 0, strpos($exercicePath, $exfile));
$exercicePath   = $exercicePath . "exercice.php";

   
// Clear the exercise session
if (isset ($_SESSION['objExercise'])) {
	api_session_unregister('objExercise');
}
if (isset ($_SESSION['objQuestion'])) {
	api_session_unregister('objQuestion');
}
if (isset ($_SESSION['objAnswer'])) {
	api_session_unregister('objAnswer');
}
if (isset ($_SESSION['questionList'])) {
	api_session_unregister('questionList');
}
if (isset ($_SESSION['exerciseResult'])) {
	api_session_unregister('exerciseResult');
}

//General POST/GET/SESSION/COOKIES parameters recovery
if (empty ($origin)) {
	$origin = Security::remove_XSS($_REQUEST['origin']);
}
if (empty ($choice)) {
	$choice = $_REQUEST['choice'];
}
if (empty ($hpchoice)) {
	$hpchoice = $_REQUEST['hpchoice'];
}
if (empty ($exerciseId)) {
	$exerciseId = intval($_REQUEST['exerciseId']);
}
if (empty ($file)) {
	$file = Database :: escape_string($_REQUEST['file']);
}

$learnpath_id       = intval($_REQUEST['learnpath_id']);
$learnpath_item_id  = intval($_REQUEST['learnpath_item_id']);
$page               = intval($_REQUEST['page']);

$course_info        = api_get_course_info();
$course_id          = api_get_course_int_id();

if ($page < 0) {
    $page = 1;
}



if (!empty($_GET['gradebook']) && $_GET['gradebook']=='view' ) {
	$_SESSION['gradebook']=Security::remove_XSS($_GET['gradebook']);
	$gradebook=	$_SESSION['gradebook'];
} elseif (empty($_GET['gradebook'])) {
	unset($_SESSION['gradebook']);
	$gradebook=	'';
}

if (!empty($gradebook) && $gradebook=='view') {
	$interbreadcrumb[] = array ('url' => '../gradebook/' . $_SESSION['gradebook_dest'],'name' => get_lang('ToolGradebook'));
}

if ($show != 'result') {
	$nameTools = get_lang('Exercices');
} else {
	if ($is_allowedToEdit || $is_tutor) {
		$nameTools = get_lang('StudentScore');				
		$interbreadcrumb[] = array("url" => "exercice.php?gradebook=$gradebook","name" => get_lang('Exercices'));
	    $objExerciseTmp = new Exercise();        
        if ($objExerciseTmp->read($exerciseId)) {
            $interbreadcrumb[] = array("url" => "admin.php?exerciseId=".$exerciseId, "name" => $objExerciseTmp->name);    
        }
	} else {
		$nameTools = get_lang('YourScore');
		$interbreadcrumb[] = array ("url" => "exercice.php?gradebook=$gradebook","name" => get_lang('Exercices'));
	}
}

if ($is_allowedToEdit && !empty ($choice) && $choice == 'exportqti2') {
	require_once 'export/qti2/qti2_export.php';
	$export = export_exercise($exerciseId, true);

	require_once api_get_path(LIBRARY_PATH) . 'pclzip/pclzip.lib.php';
	$archive_path = api_get_path(SYS_ARCHIVE_PATH);
	$temp_dir_short = api_get_unique_id();
	$temp_zip_dir = $archive_path . "/" . $temp_dir_short;
	if (!is_dir($temp_zip_dir))
		mkdir($temp_zip_dir, api_get_permissions_for_new_directories());
	$temp_zip_file = $temp_zip_dir . "/" . api_get_unique_id() . ".zip";
	$temp_xml_file = $temp_zip_dir . "/qti2export_" . $exerciseId . '.xml';
	file_put_contents($temp_xml_file, $export);
	$zip_folder = new PclZip($temp_zip_file);
	$zip_folder->add($temp_xml_file, PCLZIP_OPT_REMOVE_ALL_PATH);
	$name = 'qti2_export_' . $exerciseId . '.zip';

	//DocumentManager::string_send_for_download($export,true,'qti2export_'.$exerciseId.'.xml');
	DocumentManager :: file_send_for_download($temp_zip_file, true, $name);
	unlink($temp_zip_file);
	unlink($temp_xml_file);
	rmdir($temp_zip_dir);
	exit; //otherwise following clicks may become buggy
}

$htmlHeadXtra[] = '<script>

    $(document).ready(function() {
        $(".link_tooltip").each(function(){            
            $(this).qtip({                
                content: $(this).find(".tooltip"),
                position: { at:"top right", my:"bottom left"},  
                show: {
                   event: false, 
                   ready: true // ... but show the tooltip when ready
                },
                hide: false, //         
            });
        });
    });
</script>';

if ($origin != 'learnpath') {
	//so we are not in learnpath tool
	Display :: display_header($nameTools, get_lang('Exercise'));
	if (isset ($_GET['message'])) {
		if (in_array($_GET['message'], array ('ExerciseEdited'))) {
			Display :: display_confirmation_message(get_lang($_GET['message']));
		}
	}
} else {
	//echo '<link rel="stylesheet" type="text/css" href="' . api_get_path(WEB_CODE_PATH) . 'css/default.css"/>';
	Display :: display_reduced_header();
}

event_access_tool(TOOL_QUIZ);

// Tool introduction
Display :: display_introduction_section(TOOL_QUIZ);

HotPotGCt($documentPath, 1, api_get_user_id() );

// only for administrator

if ($is_allowedToEdit) {
	if (!empty($choice)) {
		// construction of Exercise

		$objExerciseTmp = new Exercise();
		$check = Security::check_token('get');
		if ($objExerciseTmp->read($exerciseId)) {
			if ($check) {
				switch ($choice) {
					case 'delete' : // deletes an exercise
						$objExerciseTmp->delete();						
						require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';
						$link_id = is_resource_in_course_gradebook(api_get_course_id(), 1 , $exerciseId, api_get_session_id());
						if ($link_id !== false) {
						    remove_resource_from_course_gradebook($link_id);
						}
						Display :: display_confirmation_message(get_lang('ExerciseDeleted'));
						break;
					case 'enable' : // enables an exercise
						$objExerciseTmp->enable();
						$objExerciseTmp->save();
                        
                        api_item_property_update($course_info, TOOL_QUIZ, $objExerciseTmp->id,'visible', api_get_user_id());
						// "WHAT'S NEW" notification: update table item_property (previously last_tooledit)
						Display :: display_confirmation_message(get_lang('VisibilityChanged'));

						break;
					case 'disable' : // disables an exercise
						$objExerciseTmp->disable();
						$objExerciseTmp->save();
                        api_item_property_update($course_info, TOOL_QUIZ, $objExerciseTmp->id,'invisible', api_get_user_id());
						Display :: display_confirmation_message(get_lang('VisibilityChanged'));
						break;
					case 'disable_results' : //disable the results for the learners
						$objExerciseTmp->disable_results();
						$objExerciseTmp->save();
						Display :: display_confirmation_message(get_lang('ResultsDisabled'));
						break;
					case 'enable_results' : //disable the results for the learners
						$objExerciseTmp->enable_results();
						$objExerciseTmp->save();
						Display :: display_confirmation_message(get_lang('ResultsEnabled'));
						break;
					case 'clean_results' : //clean student results
							$quantity_results_deleted= $objExerciseTmp->clean_results();
							Display :: display_confirmation_message(sprintf(get_lang('XResultsCleaned'),$quantity_results_deleted));
					break;
					case 'copy_exercise' : //copy an exercise
							$objExerciseTmp->copy_exercise();
							Display :: display_confirmation_message(get_lang('ExerciseCopied'));
					break;
				}
			}
		}
		// destruction of Exercise
		unset ($objExerciseTmp);
		Security::clear_token();
	}

	if (!empty($hpchoice)) {
		switch($hpchoice) {
			case 'delete' : // deletes an exercise
				$imgparams = array ();
				$imgcount = 0;
				GetImgParams($file, $documentPath, $imgparams, $imgcount);
				$fld = GetFolderName($file);
				for ($i = 0; $i < $imgcount; $i++) {
					my_delete($documentPath . $uploadPath . "/" . $fld . "/" . $imgparams[$i]);
					update_db_info("delete", $uploadPath . "/" . $fld . "/" . $imgparams[$i]);
				}

				if (my_delete($documentPath . $file)) {
					update_db_info("delete", $file);
				}
				my_delete($documentPath . $uploadPath . "/" . $fld . "/");
				break;
			case 'enable' : // enables an exercise
				$newVisibilityStatus = "1"; //"visible"
				$query = "SELECT id FROM $TBL_DOCUMENT WHERE c_id = $course_id AND path='" . Database :: escape_string($file) . "'";
				$res = Database::query($query);
				$row = Database :: fetch_array($res, 'ASSOC');
				api_item_property_update($_course, TOOL_DOCUMENT, $row['id'], 'visible', $_user['user_id']);
				//$dialogBox = get_lang('ViMod');

				break;
			case 'disable' : // disables an exercise
				$newVisibilityStatus = "0"; //"invisible"
				$query = "SELECT id FROM $TBL_DOCUMENT WHERE c_id = $course_id AND path='" . Database :: escape_string($file) . "'";
				$res = Database::query($query);
				$row = Database :: fetch_array($res, 'ASSOC');
				api_item_property_update($_course, TOOL_DOCUMENT, $row['id'], 'invisible', $_user['user_id']);
				break;
			default :
				break;
		}
	}
}

// Actions div bar
if ($is_allowedToEdit) {
    echo '<div class="actions">';
}


// Selects $limit exercises at the same time
// maximum number of exercises on a same page
$limit = 50;

// Display the next and previous link if needed
$from = $page * $limit;
HotPotGCt($documentPath, 1, api_get_user_id());

//condition for the session
$session_id         = api_get_session_id();
$condition_session  = api_get_session_condition($session_id,true,true);


// Only for administrators
if ($is_allowedToEdit) {
    $total_sql = "SELECT count(id) as count FROM $TBL_EXERCICES WHERE c_id = $course_id AND active<>'-1' $condition_session ";
    $sql = "SELECT * FROM $TBL_EXERCICES WHERE c_id = $course_id AND active<>'-1' $condition_session ORDER BY title LIMIT ".$from."," .$limit;
} else { 
    // Only for students
    $total_sql = "SELECT count(id) as count FROM $TBL_EXERCICES WHERE c_id = $course_id AND active = '1' $condition_session ";
    $sql = "SELECT id, title, type, description, results_disabled, session_id, start_time, end_time, max_attempt FROM $TBL_EXERCICES 
            WHERE c_id = $course_id AND 
                  active='1' $condition_session 
            ORDER BY title LIMIT ".$from."," .$limit;
}

$result = Database::query($sql);        
$exercises_count = Database :: num_rows($result);

$result_total = Database::query($total_sql);
$total_exercises  = 0;    

if (Database :: num_rows($result_total)) {    
    $result_total = Database::fetch_array($result_total);
    $total_exercises = $result_total['count'];
}

//get HotPotatoes files (active and inactive)
if ($is_allowedToEdit) {
    $sql = "SELECT * FROM $TBL_DOCUMENT WHERE c_id = $course_id AND path LIKE '" . Database :: escape_string($uploadPath) . "/%/%'";
    $res = Database::query($sql);
    $hp_count = Database :: num_rows($res);
} else {
    $sql = "SELECT * FROM $TBL_DOCUMENT d, $TBL_ITEM_PROPERTY ip
            WHERE   d.id = ip.ref AND 
                    ip.tool = '" . TOOL_DOCUMENT . "' AND 
                    d.path LIKE '" . Database :: escape_string($uploadPath) . "/%/%' AND 
                    ip.visibility ='1' AND 
                    d.c_id      = ".$course_id." AND 
                    ip.c_id     = ".$course_id;
    $res = Database::query($sql);
    $hp_count = Database :: num_rows($res);    
}
$total = $total_exercises + $hp_count;		

if ($is_allowedToEdit && $origin != 'learnpath') {	
	echo '<a href="exercise_admin.php?' . api_get_cidreq() . '">' . Display :: return_icon('new_exercice.png', get_lang('NewEx'),'','32').'</a>';
	echo '<a href="question_create.php?' . api_get_cidreq() . '">' . Display :: return_icon('new_question.png', get_lang('AddQ'),'','32').'</a>';
	// Question category
	echo '<a href="tests_category.php">';
	echo Display::return_icon('question_category_show.gif', get_lang('QuestionCategory'));
	echo '</a>';		
	echo '<a href="question_pool.php">';
	echo Display::return_icon('database.png', get_lang('langQuestionPool'), array('style'=>'width:32px'));
	echo '</a>';
	// end question category
	echo '<a href="hotpotatoes.php?' . api_get_cidreq() . '">' . Display :: return_icon('import_hotpotatoes.png', get_lang('ImportHotPotatoesQuiz'),'','32').'</a>';
	// link to import qti2 ...
	echo '<a href="qti2.php?' . api_get_cidreq() . '">' . Display :: return_icon('import_qti2.png', get_lang('ImportQtiQuiz'),'','32') .'</a>';
    echo '<a href="upload_exercise.php?' . api_get_cidreq() . '">' . Display :: return_icon('import_excel.png', get_lang('ImportExcelQuiz'),'','32') .'</a>';	
} 

if ($is_allowedToEdit) {
    echo '</div>'; // closing the actions div
}

if ($total > $limit) {
    echo '<div style="float:right;height:20px;">';
    //show pages navigation link for previous page
    if ($page) {
        echo "<a href=\"" . api_get_self() . "?" . api_get_cidreq() . "&amp;page=" . ($page -1) . "\">" . Display :: return_icon('action_prev.png', get_lang('PreviousPage'))."</a>";
    } elseif ($total_exercises + $hp_count > $limit) {
        echo Display :: return_icon('action_prev_na.png', get_lang('PreviousPage'));
    }
        
    //show pages navigation link for previous page
    if ($total_exercises > $from + $limit ||  $hp_count > $from + $limit ) {
        echo ' '."<a href=\"" . api_get_self() . "?" . api_get_cidreq() . "&amp;page=" . ($page +1) . "\">" .Display::return_icon('action_next.png', get_lang('NextPage')) . "</a>";
    } elseif ($page) {
        echo ' '.Display :: return_icon('action_next_na.png', get_lang('NextPage'));
    }
    echo '</div>';
    }
 
    $i =1;
    $lis = '';

$online_icon  = Display::return_icon('online.png', get_lang('Visible'),array('width'=>'12px'));
$offline_icon = Display::return_icon('offline.png',get_lang('Invisible'),array('width'=>'12px'));

$exercise_list = array();
while ($row = Database :: fetch_array($result,'ASSOC')) {
    $exercise_list[] = $row;
} 

echo '<table class="data_table">';    
if (!empty($exercise_list)) {     
    /*  Listing exercises  */
    
    if ($origin != 'learnpath') {
        //avoid sending empty parameters
        $myorigin     = (empty ($origin)              ? '' : '&origin=' . $origin);
        $mylpid       = (empty ($learnpath_id)        ? '' : '&learnpath_id=' . $learnpath_id);
        $mylpitemid   = (empty ($learnpath_item_id)   ? '' : '&learnpath_item_id=' . $learnpath_item_id);
        
        $token = Security::get_token();
        $i=1;
        
        if ($is_allowedToEdit) {
            $headers = array(array('name' => get_lang('ExerciseName')), 
                             array('name' => get_lang('QuantityQuestions'), 'params' => array('width'=>'100px')), 
                             array('name' => get_lang('Actions'), 'params' => array('width'=>'180px')));
        } else {
        	$headers = array(array('name' => get_lang('ExerciseName')), 
                             array('name' => get_lang('Status')), 
                             array('name' => get_lang('Results')));
        }
        
        $header_list = '';
        foreach($headers as $header) {                
            $params = isset($header['params'])? $header['params'] : null;
            $header_list .= Display::tag('th', $header['name'], $params);	
        }
        echo Display::tag('tr', $header_list);
        
        $count = 0;
        if (!empty($exercise_list))
        foreach ($exercise_list as $row) {
            $my_exercise_id = $row['id'];                
            //echo '<div  id="tabs-'.$i.'">';
            $i++;                    
            //validacion when belongs to a session
            $session_img = api_get_session_image($row['session_id'], $_user['status']);
            
            $time_limits = false;                            
            if ($row['start_time'] != '0000-00-00 00:00:00' || $row['end_time'] != '0000-00-00 00:00:00') {
                $time_limits = true;    
            }
            
            if ($time_limits) {
                // check if start time
                $start_time = false;
                if ($row['start_time'] != '0000-00-00 00:00:00') {
                    $start_time = api_strtotime($row['start_time'],'UTC');
                }
                $end_time = false;
                if ($row['end_time'] != '0000-00-00 00:00:00') {
                    $end_time   = api_strtotime($row['end_time'],'UTC');  
                }                                    
                $now             = time();
                $is_actived_time = false;
                
                //If both "clocks" are enable
                if ($start_time && $end_time) {                  
                    if ($now > $start_time && $end_time > $now ) {                        
                        $is_actived_time = true;
                    }
                } else {
                    //we check the start and end
                    if ($start_time) {
                        if ($now > $start_time) {
                           $is_actived_time = true;
                        }
                    }
                    if ($end_time) {
                        if ($end_time > $now ) {
                           $is_actived_time = true;
                        }
                    }
                }                    
            }
             			
			//Blocking empty start times see BT#2800
        	global $_custom; 
			if (isset($_custom['exercises_hidden_when_no_start_date']) && $_custom['exercises_hidden_when_no_start_date']) { 
                if (empty($row['start_time']) || $row['start_time'] == '0000-00-00 00:00:00') {                	
                	$time_limits = true;
                	$is_actived_time = false;
                }
			}
            
            $cut_title = cut($row['title'], EXERCISE_MAX_NAME_SIZE);
            $alt_title = '';
            if ($cut_title != $row['title']) {
                $alt_title = ' title = "'.$row['title'].'" ';
            }
            
            // Teacher only                
            if ($is_allowedToEdit) {
                $show_quiz_edition = true;                
                
                $sql="SELECT max_score FROM $table_lp_item
                      WHERE c_id = $course_id AND 
                            item_type = '".TOOL_QUIZ."' AND 
                            path ='".Database::escape_string($row['id'])."'";
                $result = Database::query($sql);
                if (Database::num_rows($result) > 0) {
                    $show_quiz_edition = false;
                }
                
                $lp_blocked = '';
                if (!$show_quiz_edition) {
                    $lp_blocked = Display::tag('font', '<i>'.get_lang('AddedToALP').'</i>', array('style'=>'color:grey'));
                }                    
                                
                //Showing exercise title                    
                
                if ($session_id == $row['session_id']) {
                    //Settings                                                                
                    //echo Display::url(Display::return_icon('settings.png',get_lang('Edit'), array('width'=>'22px'))." ".get_lang('Edit'), 'exercise_admin.php?'.api_get_cidreq().'&modifyExercise=yes&exerciseId='.$row['id']);
                }                                                      

                if ($row['active'] == 0) {
                    $title = Display::tag('font', $cut_title, array('style'=>'color:grey'));
                } else {
                    $title = $cut_title;
                }
                    
                $count = intval(count_exercise_result_not_validated($my_exercise_id, $course_code, $session_id));
                
                $class_tip = '';
                if (!empty($count)) {
                    $results_text = $count == 1 ? get_lang('ResultNotRevised') : get_lang('ResultsNotRevised');
                    $title .= '<span class="tooltip" style="display: none;">'.$count.' '.$results_text.' </span>';
                    $class_tip = 'link_tooltip';
                }
                
                $url = '<a '.$alt_title.' class="'.$class_tip.'" id="tooltip_'.$row['id'].'" href="overview.php?'.api_get_cidreq().$myorigin.$mylpid.$mylpitemid.'&exerciseId='.$row['id'].'"><img src="../img/quiz.gif" /> '.$title.' </a>';
                           
                $item =  Display::tag('td', $url.' '.$session_img.$lp_blocked);  
                

                //count number exercice - teacher
                $sqlquery   = "SELECT count(*) FROM $TBL_EXERCICE_QUESTION WHERE c_id = $course_id AND exercice_id = $my_exercise_id";
                $sqlresult  = Database::query($sqlquery);
                $rowi       = Database :: result($sqlresult, 0);                 
                                    
                if ($session_id == $row['session_id']) {
                    //Settings
                    $actions =  Display::url(Display::return_icon('edit.png',get_lang('Edit'),'',22), 'admin.php?'.api_get_cidreq().'&exerciseId='.$row['id']);
                    
                    //Exercise results                        
                    $actions .='<a href="exercise_report.php?' . api_get_cidreq() . '&exerciseId='.$row['id'].'">'.Display :: return_icon('test_results.png', get_lang('Results'),'',22).'</a>';                    
                                            
                    //Export
                    $actions .= Display::url(Display::return_icon('cd.gif', get_lang('CopyExercise')),       '', array('onclick'=>"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('AreYouSureToCopy'),ENT_QUOTES,$charset))." ".addslashes($row['title'])."?"."')) return false;",'href'=>'exercice.php?'.api_get_cidreq().'&choice=copy_exercise&sec_token='.$token.'&exerciseId='.$row['id']));
                    //Clean exercise                    
                    $actions .= Display::url(Display::return_icon('clean.png', get_lang('CleanStudentResults'),'',22),'', array('onclick'=>"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('AreYouSureToDeleteResults'),ENT_QUOTES,$charset))." ".addslashes($row['title'])."?"."')) return false;",'href'=>'exercice.php?'.api_get_cidreq().'&choice=clean_results&sec_token='.$token.'&exerciseId='.$row['id']));                      
                    //Visible / invisible
                    if ($row['active']) {
                        $actions .= Display::url(Display::return_icon('visible.png', get_lang('Deactivate'),'',22) , 'exercice.php?'.api_get_cidreq().'&choice=disable&sec_token='.$token.'&page='.$page.'&exerciseId='.$row['id']);                        
                    } else { // else if not active                    
                        $actions .= Display::url(Display::return_icon('invisible.png', get_lang('Activate'),'',22) , 'exercice.php?'.api_get_cidreq().'&choice=enable&sec_token='.$token.'&page='.$page.'&exerciseId='.$row['id']);                      
                    }                        
                    // Export qti ...                    
                    $actions .= Display::url(Display::return_icon('export_qti2.png','IMS/QTI','','22'),        'exercice.php?choice=exportqti2&exerciseId='.$row['id']);
                } else { 
                    // not session                 
                    $actions = Display::return_icon('edit_na.png', get_lang('ExerciseEditionNotAvailableInSession'));                    
                    $actions .='<a href="exercise_report.php?' . api_get_cidreq() . '&exerciseId='.$row['id'].'">'.Display :: return_icon('test_results.png', get_lang('Results'),'',22).'</a>';                                            
                    $actions .= Display::url(Display::return_icon('cd.gif',   get_lang('CopyExercise')),     '',  array('onclick'=>"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('AreYouSureToCopy'),ENT_QUOTES,$charset))." ".addslashes($row['title'])."?"."')) return false;",'href'=>'exercice.php?'.api_get_cidreq().'&choice=copy_exercise&sec_token='.$token.'&exerciseId='.$row['id']));                           
                }
                
                //Delete
                if ($session_id == $row['session_id']) {
                    $actions .= Display::url(Display::return_icon('delete.png', get_lang('Delete'),'',22), '', array('onclick'=>"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('AreYouSureToDelete'),ENT_QUOTES,$charset))." ".addslashes($row['title'])."?"."')) return false;",'href'=>'exercice.php?'.api_get_cidreq().'&choice=delete&sec_token='.$token.'&exerciseId='.$row['id']));            
                }

				// Number of questions
                $random_label = '';                    
                if ($row['random'] > 0 || $row['random'] == -1) {
               	    // if random == -1 means use random questions with all questions
               	    $random_number_of_question = $row['random'];
               	    if ($random_number_of_question == -1) {
               	        $random_number_of_question = $rowi;
               	    }
					if ($row['random_by_category'] > 0) {	
						if (!class_exists("testcategory.class.php")) include_once "testcategory.class.php" ;
						$nbQuestionsTotal = Testcategory::getNumberOfQuestionRandomByCategory($my_exercise_id, $random_number_of_question);
						$number_of_questions .= $nbQuestionsTotal." ";
						$number_of_questions .= ($nbQuestionsTotal > 1) ? get_lang("QuestionsLowerCase") : get_lang("QuestionLowerCase") ;
						$number_of_questions .= " - ";
						//$number_of_questions .= Testcategory::getNumberMaxQuestionByCat($my_exercise_id).' '.get_lang('QuestionByCategory');
                        $number_of_questions .= min(Testcategory::getNumberMaxQuestionByCat($my_exercise_id), $random_number_of_question).' '.get_lang('QuestionByCategory');
					} else {
                   		$random_label = ' ('.get_lang('Random').') ';                       	
                   	    $number_of_questions = $random_number_of_question . ' ' .$random_label.' '.$textByCategory;
                   	    //Bug if we set a random value bigger than the real number of questions 
                   	    if ($random_number_of_question > $rowi) {
							$number_of_questions = $rowi. ' ' .$random_label;							
                   	    }
                   	}
                } else {                    
                    $number_of_questions = $rowi;
                }                
 
                //Attempts                    
                //$attempts = get_count_exam_results($row['id']).' '.get_lang('Attempts');
                
                //$item .=  Display::tag('td',$attempts);
                $item .=  Display::tag('td', $number_of_questions);
                    
            } else {                     
                // --- Student only                 
                
                // if time is actived show link to exercise
                
                if ($time_limits) {                 
                    if ($is_actived_time) {
                        $url =  '<a '.$alt_title.'  href="overview.php?'.api_get_cidreq().$myorigin.$mylpid.$mylpitemid.'&exerciseId='.$row['id'].'">'.$cut_title.'</a>';
                    } else {
                        $url = $row['title'];                            
                    }                       
                } else {
                    $url = '<a '.$alt_title.'  href="overview.php?'.api_get_cidreq().$myorigin.$mylpid.$mylpitemid.'&exerciseId='.$row['id'].'">'.$cut_title.'</a>';                       
                }                   
                
                //Link of the exercise             
                $item =  Display::tag('td',$url.' '.$session_img);  
                       
                //count number exercise questions
                $sqlquery   = "SELECT count(*) FROM $TBL_EXERCICE_QUESTION WHERE c_id = $course_id AND exercice_id = ".$row['id'];
                $sqlresult  = Database::query($sqlquery);
                $rowi       = Database::result($sqlresult, 0);
                
                if ($row['random'] > 0) {
                    $row['random'] . ' ' . api_strtolower(get_lang(($row['random'] > 1 ? 'Questions' : 'Question')));
                } else {
                    //show results student
                    $rowi . ' ' . api_strtolower(get_lang(($rowi > 1 ? 'Questions' : 'Question')));
                }      
                                    
                //This query might be improved later on by ordering by the new "tms" field rather than by exe_id
                //Don't remove this marker: note-query-exe-results
                $qry = "SELECT * FROM $TBL_TRACK_EXERCICES
                        WHERE   exe_exo_id      = ".$row['id']." AND 
                                exe_user_id     = ".api_get_user_id()." AND 
                                exe_cours_id    = '".api_get_course_id()."' AND 
                                status          <> 'incomplete' AND 
                                orig_lp_id      = 0 AND 
                                orig_lp_item_id = 0 AND 
                                session_id      =  '" . api_get_session_id() . "'
                        ORDER BY exe_id DESC";
                $qryres = Database::query($qry);
                $num    = Database :: num_rows($qryres);
        
                //Hide the results
                $my_result_disabled = $row['results_disabled'];
                
                //Time limits are on    
                if ($time_limits) {
                    // Examn is ready to be taken    
                    if ($is_actived_time) {
                        //Show results                    
                        if ($my_result_disabled == 0 || $my_result_disabled == 2) {
                            //More than one attempt
                            if ($num > 0) {
                                $row_track = Database :: fetch_array($qryres);                                
                                $attempt_text =  get_lang('LatestAttempt') . ' : ';                                
                                $attempt_text .= show_score($row_track['exe_result'], $row_track['exe_weighting']);
                            } else {
                                //No attempts
                                $attempt_text =  get_lang('NotAttempted');    
                            }                           
                        } else {
                            $attempt_text =  get_lang('CantShowResults');
                        }
                    } else {
                        //Quiz not ready due to time limits
                        
                        //@todo use the is_visible function                        
                        if ($row['start_time'] != '0000-00-00 00:00:00' && $row['end_time'] != '0000-00-00 00:00:00') {
                            $attempt_text =  sprintf(get_lang('ExerciseWillBeActivatedFromXToY'), api_convert_and_format_date($row['start_time']), api_convert_and_format_date($row['end_time']));
                        } else {
                            //$attempt_text = get_lang('ExamNotAvailableAtThisTime');                                
                            if ($row['start_time'] != '0000-00-00 00:00:00') { 
                                $attempt_text = sprintf(get_lang('ExerciseAvailableFromX'), api_convert_and_format_date($row['start_time']));
                            }
                            if ($row['end_time'] != '0000-00-00 00:00:00') {
                                $attempt_text = sprintf(get_lang('ExerciseAvailableUntilX'), api_convert_and_format_date($row['end_time']));     
                            }                                
                        }
                    }
                } else {
                    //Normal behaviour
                    //Show results
                    if ($my_result_disabled == 0 || $my_result_disabled == 2) {                         
                        if ($num > 0) {
                            $row_track = Database :: fetch_array($qryres);                                
                            $attempt_text =  get_lang('LatestAttempt') . ' : ';                                
                            $attempt_text .= show_score($row_track['exe_result'], $row_track['exe_weighting']);                                
                        } else {
                            $attempt_text =  get_lang('NotAttempted');
                        }
                    } else {                            
                        $attempt_text = get_lang('CantShowResults');
                    }
                }
                       
                $class_tip = '';
                
                if (empty($num)) {
                    $num = '';
                } else {
                    $class_tip = 'link_tooltip';
                    //@todo use sprintf and show the results validated by the teacher
                    if ($num == 1 ) {
                        $num = $num.' '.get_lang('Result');
                    } else {
                        $num = $num.' '.get_lang('Results');
                    }
                    $num = '<span class="tooltip" style="display: none;">'.$num.'</span>';
                }                 
                $item .=  Display::tag('td', $attempt_text);
                                 
                //See results
                $actions = '<a class="'.$class_tip.'" id="tooltip_'.$row['id'].'" href="exercise_report.php?' . api_get_cidreq() . '&exerciseId='.$row['id'].'">'.$num.Display::return_icon('test_results.png', get_lang('Results'),'',22).' </a>';
            }                
            $class = 'row_even';
            if ($count % 2) {
                $class = 'row_odd';
            }                                        
            $item .=  Display::tag('td', $actions);                    
            echo Display::tag('tr',$item, array('class'=>$class));
                   
            $count++;
        } // end foreach()
    } 
} else {
    if ($is_allowedToEdit && $origin != 'learnpath') {  
        echo '<div id="no-data-view">';
        echo '<h2>'.get_lang('Quiz').'</h2>';
        echo Display::return_icon('quiz.png', '', array(), 64);
        echo '<div class="controls">';    
        echo Display::url(get_lang('NewEx'), 'exercise_admin.php?' . api_get_cidreq(), array('class' => 'a_button white'));
        echo '</div>';
        echo '</div>';
    }
}
// end exercise list


   
//Hotpotatoes results            


if ($is_allowedToEdit) {
    $sql = "SELECT d.path as path, d.comment as comment, ip.visibility as visibility
            FROM $TBL_DOCUMENT d, $TBL_ITEM_PROPERTY ip
            WHERE   d.c_id = $course_id AND
                    ip.c_id = $course_id AND
                    d.id = ip.ref AND 
                    ip.tool = '" . TOOL_DOCUMENT . "' AND 
                    (d.path LIKE '%htm%') AND 
                    d.path  LIKE '" . Database :: escape_string($uploadPath) . "/%/%' 
                    LIMIT " .$from . "," .$limit; // only .htm or .html files listed
} else {
    $sql = "SELECT d.path as path, d.comment as comment, ip.visibility as visibility
            FROM $TBL_DOCUMENT d, $TBL_ITEM_PROPERTY ip
            WHERE d.c_id = $course_id AND
                    ip.c_id = $course_id AND 
            d.id = ip.ref AND ip.tool = '" . TOOL_DOCUMENT . "' AND (d.path LIKE '%htm%')
            AND   d.path  LIKE '" . Database :: escape_string($uploadPath) . "/%/%' AND ip.visibility='1' 
            LIMIT " .$from . "," .$limit;
}

$result = Database::query($sql);

while ($row = Database :: fetch_array($result, 'ASSOC')) {
    $attribute['path'][]        = $row['path'];
    $attribute['visibility'][]  = $row['visibility'];
    $attribute['comment'][]     = $row['comment'];
}

$nbrActiveTests = 0;
if (isset($attribute['path']) && is_array($attribute['path'])) {
    while (list($key, $path) = each($attribute['path'])) {
        $item = '';
        list ($a, $vis) = each($attribute['visibility']);
        if (strcmp($vis, "1") == 0) {
            $active = 1;
        } else {
            $active = 0;
        }
        $title = GetQuizName($path, $documentPath);
        if ($title == '') {
            $title = basename($path);
        }
        
        $class = 'row_even';
        if ($count % 2) {
            $class = 'row_odd';
        }
            
        // prof only
        if ($is_allowedToEdit) {
            $item  = Display::tag('td','<img src="../img/hotpotatoes_s.png" alt="HotPotatoes" /> <a href="showinframes.php?file='.$path.'&cid='.api_get_course_id().'&uid='.api_get_user_id().'"'.(!$active?'class="invisible"':'').'>'.$title.'</a> ');
            $item .= Display::tag('td','-');
                             
            $actions =  Display::url(Display::return_icon('edit.png',get_lang('Edit'),'',22), 'adminhp.php?'.api_get_cidreq().'&hotpotatoesName='.$path);
            $actions .='<a href="exercise_report.php?' . api_get_cidreq() . '&path='.$path.'">' . Display :: return_icon('test_results.png', get_lang('Results'),'',22).'</a>';
                                    
            // if active
            if ($active) {
                $nbrActiveTests = $nbrActiveTests +1;
                $actions .= '      <a href="'.$exercicePath.'?'.api_get_cidreq().'&hpchoice=disable&amp;page='.$page.'&amp;file='.$path.'">'.Display::return_icon('visible.png', get_lang('Deactivate'),'',22).'</a>';
            } else { // else if not active
                $actions .='    <a href="'.$exercicePath.'?'.api_get_cidreq().'&hpchoice=enable&amp;page='.$page.'&amp;file='.$path.'">'.Display::return_icon('invisible.png', get_lang('Activate'),'',22).'</a>';
            }
            $actions .= '<a href="'.$exercicePath.'?'.api_get_cidreq().'&amp;hpchoice=delete&amp;file='.$path.'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('AreYouSureToDelete'),ENT_QUOTES,$charset).' '.$title."?").'\')) return false;">'.Display::return_icon('delete.png', get_lang('Delete'),'',22).'</a>';
                                    
            //$actions .='<img src="../img/lp_quiz_na.gif" border="0" title="'.get_lang('NotMarkActivity').'" alt="" />';
            $item .= Display::tag('td', $actions);
            echo Display::tag('tr',$item, array('class'=>$class));                     
        } else { // student only
            if ($active == 1) {
                $nbrActiveTests = $nbrActiveTests +1;
                $item .= Display::tag('td', '<a href="showinframes.php?'.api_get_cidreq().'&file='.$path.'&cid='.api_get_course_id().'&uid='.api_get_user_id().'"'.(!$active?'class="invisible"':'').'">'.$title.'</a>');
                $item .= Display::tag('td', '');
                $actions ='<a href="exercise_report.php?' . api_get_cidreq() . '&path='.$path.'">' . Display :: return_icon('test_results.png', get_lang('Results'),'',22).'</a>';
                $item .= Display::tag('td', $actions);                            
                echo Display::tag('tr',$item, array('class'=>$class));
            }                        
        }
        $count ++;
    }
}    
echo '</table>';    
Display :: display_footer();    
exit;


if ($origin != 'learnpath') { //so we are not in learnpath tool
	Display :: display_footer();
}
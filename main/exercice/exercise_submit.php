<?php
/* For licensing terms, see /license.txt */
/**
*	Exercise submission
* 	This script allows to run an exercise. According to the exercise type, questions
* 	can be on an unique page, or one per page with a Next button.
*
* 	One exercise may contain different types of answers (unique or multiple selection,
* 	matching, fill in blanks, free answer, hot-spot).
*
* 	Questions are selected randomly or not.
*
* 	When the user has answered all questions and clicks on the button "Ok",
* 	it goes to exercise_result.php
*
* 	Notice : This script is also used to show a question before modifying it by
* 	the administrator
*	@package chamilo.exercise
* 	@author Olivier Brouckaert
* 	@author Julio Montoya multiple fill in blank option added (2008) and Cleaning exercises (2010), Adding hotspot delineation support (2011)
*/
/**
 * Code
 */
require_once 'exercise.class.php';
require_once 'question.class.php';
require_once 'answer.class.php';
require_once 'exercise.lib.php';

//$debug = 1; //debug value is set in the exercise.class.php file

// name of the language file that needs to be included
$language_file = 'exercice';

require_once '../inc/global.inc.php';

$this_section = SECTION_COURSES;

if($debug) { error_log('Entered exercise_submit.php: '.print_r($_POST,1)); }

// Notice for unauthorized people.
api_protect_course_script(true);
$is_allowedToEdit = api_is_allowed_to_edit(null,true);

if (api_get_setting('show_glossary_in_extra_tools') == 'true') {
    $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/glossary.js" type="text/javascript" language="javascript"></script>'; //Glossary
    $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.highlight.js" type="text/javascript" language="javascript"></script>';
}

//@todo we should only enable this when there is a time control

//This library is necessary for the time control feature
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.epiclock.min.js" type="text/javascript" language="javascript"></script>'; //jQuery

$_configuration['live_exercise_tracking'] = true;

$stat_table 			= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$exercice_attemp_table 	= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

$TBL_EXERCICE_QUESTION 	= Database :: get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_EXERCICES 			= Database :: get_course_table(TABLE_QUIZ_TEST);
$TBL_QUESTIONS 			= Database :: get_course_table(TABLE_QUIZ_QUESTION);
$TBL_REPONSES 			= Database :: get_course_table(TABLE_QUIZ_ANSWER);

// General parameters passed via POST/GET

if (empty ($origin)) {
    $origin = Security::remove_XSS($_REQUEST['origin']);
}
if (empty ($learnpath_id)) {
    $learnpath_id = intval($_REQUEST['learnpath_id']);
}
if (empty ($learnpath_item_id)) {
    $learnpath_item_id = intval($_REQUEST['learnpath_item_id']);
}
if (empty ($learnpath_item_view_id)) {
    $learnpath_item_view_id = intval($_REQUEST['learnpath_item_view_id']);
}
if (empty ($formSent)) {
    $formSent = $_REQUEST['formSent'];
}
if (empty ($exerciseResult)) {
    $exerciseResult = $_REQUEST['exerciseResult'];
}
if (empty ($exerciseResultCoordinates)) {
	$exerciseResultCoordinates = $_REQUEST['exerciseResultCoordinates'];
}
if (empty ($exerciseType)) {
    $exerciseType = $_REQUEST['exerciseType'];
}
if (empty ($exerciseId)) {
    $exerciseId = intval($_REQUEST['exerciseId']);
}
if (empty ($choice)) {
    $choice = $_REQUEST['choice'];
}
if (empty ($_REQUEST['choice'])) {
    $choice = $_REQUEST['choice2'];
}
if (empty ($questionNum)) {
    $questionNum = intval($_REQUEST['questionNum']);
}
if (empty ($nbrQuestions)) {
    $nbrQuestions = intval($_REQUEST['nbrQuestions']);
}
if (empty ($buttonCancel)) {
    $buttonCancel = $_REQUEST['buttonCancel'];
}
$error = '';

// if the user has clicked on the "Cancel" button
if ($buttonCancel) {
    // returns to the exercise list
    header("Location: exercice.php?origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id&learnpath_item_view_id=$learnpath_item_view_id");
    exit;
}

$safe_lp_id             = ($learnpath_id == '')             ? 0 : $learnpath_id;
$safe_lp_item_id        = ($learnpath_item_id == '')        ? 0 : $learnpath_item_id;
$safe_lp_item_view_id   = ($learnpath_item_view_id == '')   ? 0 : $learnpath_item_view_id;


/*
 * Teacher takes an exam and want to see a preview, we delete the objExercise from the session in order to get the latest changes
    in the exercise 
*/
if (api_is_allowed_to_edit(null,true) && $_GET['preview'] == 1 ) {
    api_session_unregister('objExercise');
}

// Loading the $objExercise variable
if (!isset ($_SESSION['objExercise']) || $_SESSION['objExercise']->id != $_REQUEST['exerciseId']) {    
    // Construction of Exercise
    $objExercise = new Exercise();   
    if ($debug) {error_log('Setting the $objExercise variable'); };
    unset($_SESSION['questionList']);

    // if the specified exercise doesn't exist or is disabled
    if (!$objExercise->read($exerciseId) || (!$objExercise->selectStatus() && !$is_allowedToEdit && ($origin != 'learnpath'))) {
    	if ($debug) {error_log('Error while reading the exercise'); };
        unset ($objExercise);
        $error = get_lang('ExerciseNotFound');
    } else {
        // saves the object into the session
        api_session_register('objExercise');
        if ($debug) {error_log('$_SESSION[objExercise] was unset - set now - end'); };        
    }    
}

if (!isset ($objExercise) && isset($_SESSION['objExercise'])) {
	if ($debug) {error_log('Loading $objExercise from session'); };
	 
    $objExercise = $_SESSION['objExercise'];        
}

if (!is_object($objExercise)) {
	if ($debug) {error_log('$objExercise was not set, kill the script'); };
    header('Location: exercice.php');
    exit;
}

$exerciseType 			= $objExercise->type;
$current_timestamp 		= time();

//Getting track exercise info
$exercise_stat_info = $objExercise->get_stat_track_exercise_info($safe_lp_id, $safe_lp_item_id, $safe_lp_item_view_id);

if ($debug) {error_log('$objExercise->get_stat_track_exercise_info function called::  '.print_r($exercise_stat_info, 1)); };

/*
 * Time control feature
 * if the expired time is major that zero(0) then the expired time is compute on this time. Disable for learning path
 */
$time_control = false;
if ($objExercise->expired_time != 0 && $origin != 'learnpath') {
	$time_control = true;
}
if ($time_control) {
	//Get the expired time of the current exercice in track_e_exercices
	$total_seconds 			= $objExercise->expired_time*60;
	//Generating the time control key
	$current_expired_time_key = generate_time_control_key($objExercise->id);
	if ($debug) {error_log('$current_expired_time_key  '.$current_expired_time_key); };
    if (!isset($_SESSION['expired_time'][$current_expired_time_key])) {
        //Timer - Get expired_time for a student        
        if (!empty($exercise_stat_info)) {
        	if ($debug) {error_log('Seems that the session ends and the user want to retake the exam'); };        	
	        $expired_time_of_this_attempt = $exercise_stat_info['expired_time_control'];
			if ($debug) {error_log('$expired_time_of_this_attempt: '.$expired_time_of_this_attempt); }			
	        //Get the last attempt of an exercice
	    	$last_attempt_date = get_last_attempt_date_of_exercise($exercise_stat_info['exe_id']);
	    	
	    	//This means that the user enters the exam but do not answer the first question we get the date from the track_e_exercises not from the track_et_attempt see #2069	    	
	    	if (empty($last_attempt_date)) {
	    		$diff = $current_timestamp - api_strtotime($exercise_stat_info['start_date'], 'UTC');
	    		$last_attempt_date = api_get_utc_datetime(api_strtotime($exercise_stat_info['start_date'],'UTC') + $diff);		
	    	} else {	    		
	    		//Recalculate the time control due #2069
	    		$diff = $current_timestamp - api_strtotime($last_attempt_date,'UTC');
	    		$last_attempt_date = api_get_utc_datetime(api_strtotime($last_attempt_date,'UTC') + $diff);
	    	}
	        if ($debug) {error_log('$last_attempt_date: '.$last_attempt_date); }
	        	
	        //New expired time - it is due to the possible closure of session
	        $new_expired_time_in_seconds = api_strtotime($expired_time_of_this_attempt, 'UTC') - api_strtotime($last_attempt_date,'UTC');
	        if ($debug) {error_log('$new_expired_time_in_seconds: '.$new_expired_time_in_seconds); }	        
	        
	        $expected_time	= $current_timestamp + $new_expired_time_in_seconds;
	        if ($debug) {error_log('$expected_time1: '.$expected_time); }
	        	        			
	        //$plugin_expired_time = date('M d, Y H:i:s', $expected_time);
	        $clock_expired_time  = api_get_utc_datetime($expected_time);
	        if ($debug) {error_log('$clock_expired_time: '.$clock_expired_time); }
	        	        
	        //@todo check this validation with Fasa: With this change the user can log out and login from the system and the counter will not work
	        /*
	        //We modify the "expired_time_control" field in track_e_exercices for this attempt
	        $sql_track_e_exe = "UPDATE $stat_table SET expired_time_control = '".$clock_expired_time."' WHERE exe_id = '".$exercise_stat_info['exe_id']."'";	        
	        if ($debug) {error_log('$sql_track_e_exe1: '.$sql_track_e_exe); }
	        Database::query($sql_track_e_exe);
	        */
	        	              		
			//First we update the attempt to today
			// How the expired time is changed into "track_e_exercices" table,then the last attempt for this student should be changed too,so
	        $sql_track_e_exe = "UPDATE $exercice_attemp_table SET tms = '".api_get_utc_datetime()."' WHERE exe_id = '".$exercise_stat_info['exe_id']."' AND tms = '".$last_attempt_date."' ";
	        if ($debug) {error_log('$sql_track_e_exe2: '.$sql_track_e_exe); }
	        Database::query($sql_track_e_exe);
	        	
	        //Sessions  that contain the expired time
	        $_SESSION['expired_time'][$current_expired_time_key] 		= $clock_expired_time;
	        
	        if ($debug) {error_log('1. Setting the $_SESSION[expired_time]: '.$_SESSION['expired_time'][$current_expired_time_key] ); };
        } else {
            $expected_time = $current_timestamp + $total_seconds;
            if ($debug)  error_log('$current_timestamp '.$current_timestamp);
            if ($debug)  error_log('$expected_time '.$expected_time);
            //$expected_time = api_strtotime(api_get_utc_datetime($expected_time));
			
            //$plugin_expired_time 	= date('M d, Y H:i:s', $expected_time);            
            //$clock_expired_time 	= date('Y-m-d H:i:s', $expected_time);
            $clock_expired_time 	= api_get_utc_datetime($expected_time);
             if ($debug) error_log('$expected_time '.$clock_expired_time);

            //Sessions  that contain the expired time
            $_SESSION['expired_time'][$current_expired_time_key] 	 = $clock_expired_time;
         //   $_SESSION['end_expired_time'][$current_expired_time_key] = $plugin_expired_time;            
            if ($debug) {error_log('2. Setting the $_SESSION[expired_time]: '.$_SESSION['expired_time'][$current_expired_time_key] ); };
            //if ($debug) {error_log('2. Setting the $_SESSION[end_expired_time]: '.$_SESSION['end_expired_time'][$current_expired_time_key] ); };
        }
    } else {
       // $plugin_expired_time = $_SESSION['end_expired_time'][$current_expired_time_key];
        $clock_expired_time =  $_SESSION['expired_time'][$current_expired_time_key];        
        if ($debug) {error_log('Getting the $_SESSION[end_expired_time]: '.$_SESSION['end_expired_time'][$current_expired_time_key] ); };
    }
}

// get time left for exipiring time
//$time_left = api_strtotime($plugin_expired_time) - api_strtotime(api_get_utc_datetime());
// get time left for exipiring time
$time_left = api_strtotime($clock_expired_time,'UTC') - time();

/*
 * The time control feature is enable here - this feature is enable for a jquery plugin called epiclock
 * for more details of how it works see this link : http://eric.garside.name/docs.html?p=epiclock
 */
if ($time_control) { //Sends the exercice form when the expired time is finished
	$htmlHeadXtra[] = $objExercise->show_time_control_js($time_left);
}

if ($_configuration['live_exercise_tracking'] && $objExercise->type == ONE_PER_PAGE && $objExercise->feedbacktype != EXERCISE_FEEDBACK_TYPE_DIRECT) {	
	if (!empty($exercise_stat_info)) {
        $exe_id = $exercise_stat_info['exe_id'];
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            define('QUESTION_LIST_ALREADY_LOGGED', 1);
            $recorded['questionList'] = explode(',', $exercise_stat_info['data_tracking']);            
            $query = 'SELECT * FROM ' . $exercice_attemp_table . ' WHERE exe_id = ' . $exercise_stat_info['exe_id'] . ' ORDER BY tms ASC';            
            $result = Database::query($query);
            while ($row = Database :: fetch_array($result,'ASSOC')) {
                $recorded['exerciseResult'][$row['question_id']] = 1;
            }
            $exerciseResult = $_SESSION['exerciseResult'] = $recorded['exerciseResult'];            
            $questionNum = count($recorded['exerciseResult']);
            $questionNum++;
            $questionList = $_SESSION['questionList'] = $recorded['questionList'];
        }
    }
}

// if the user has submitted the form

if ($formSent && isset($_POST)) {
    if ($debug > 0) { error_log('$formSent was set'); }

    // Initializing
    if (!is_array($exerciseResult)) {
        $exerciseResult = array ();
        $exerciseResultCoordinates = array();
    }

    //Only for hotspot
    if (!isset($choice) && isset($_REQUEST['hidden_hotspot_id'])) {
        $hotspot_id = (int)($_REQUEST['hidden_hotspot_id']);
        $choice     = array($hotspot_id => '');
    }
    // if the user has answered at least one question
    if (is_array($choice)) {
        if ($debug) { error_log('$choice is an array '.print_r($choice, 1)); } 	
        // Also store hotspot spots in the session ($exerciseResultCoordinates
        // will be stored in the session at the end of this script)

        if (isset ($_POST['hotspot'])) {
            $exerciseResultCoordinates = $_POST['hotspot'];
            if ($debug) { error_log('$_POST[hotspot] data '.print_r($exerciseResultCoordinates, 1)); }     
        }        	
        if ($exerciseType == ALL_ON_ONE_PAGE) {
            // $exerciseResult receives the content of the form.
            // Each choice of the student is stored into the array $choice
            $exerciseResult = $choice;            
        } else {
            // gets the question ID from $choice. It is the key of the array
            list ($key) = array_keys($choice);
            // if the user didn't already answer this question
            if (!isset($exerciseResult[$key])) {
                // stores the user answer into the array
                $exerciseResult[$key] = $choice[$key];
                //saving each question
                if ($_configuration['live_exercise_tracking'] && $objExercise->feedbacktype != EXERCISE_FEEDBACK_TYPE_DIRECT) {
                    $nro_question = $questionNum; // - 1;
                    //START of saving and qualifying each question submitted
                    define('ENABLED_LIVE_EXERCISE_TRACKING', 1);
                 	$questionId = $key;                    
                    // gets the student choice for this question
                    $choice = $exerciseResult[$questionId];                    
                    if (isset($exe_id)) {
                    	//Manage the question and answer attempts
                       if ($debug > 0) { error_log('manage_answer exe_id: '.$exe_id.' - $questionId: '.$questionId.' Choice'.print_r($choice,1)); }
                    	$objExercise->manage_answer($exe_id, $questionId, $choice,'exercise_show',$exerciseResultCoordinates, true, false,false);
                    }
                    //END of saving and qualifying
                }
            }
        }
        if ($debug > 0) { error_log('$choice is an array - end'); }
    }

    // the script "exercise_result.php" will take the variable $exerciseResult from the session
    api_session_register('exerciseResult');
    api_session_register('exerciseResultCoordinates');

    // if all questions on one page OR if it is the last question (only for an exercise with one question per page)

    if ($exerciseType == ALL_ON_ONE_PAGE || $questionNum >= $nbrQuestions) {        
        if (api_is_allowed_to_session_edit()) {
            // goes to the script that will show the result of the exercise
            if ($exerciseType == ALL_ON_ONE_PAGE) {
                if ($debug) { error_log('Exercise ALL_ON_ONE_PAGE -> Redirecting to exercise_result.php'); }
                
                //We check if the user attempts before sending to the exercise_result.php                
                if ($objExercise->selectAttempts() > 0) {
                    $attempt_count = get_attempt_count(api_get_user_id(), $exerciseId, $safe_lp_id, $safe_lp_item_id, $safe_lp_item_view_id);                
                    if ($attempt_count >= $objExercise->selectAttempts()) {
                        Display :: display_warning_message(sprintf(get_lang('ReachedMaxAttempts'), $exerciseTitle, $objExercise->selectAttempts()), false);                        
                        if ($origin != 'learnpath') {
                            //so we are not in learnpath tool
                            echo '</div>'; //End glossary div
                            Display :: display_footer();
                        } else {
                            echo '</body></html>';
                        }                        
                    }
                }             
                header("Location: exercise_result.php?exerciseType=$exerciseType&origin=$origin&learnpath_id=$safe_lp_id&learnpath_item_id=$safe_lp_item_id&learnpath_item_view_id=$safe_lp_item_view_id");
                exit;
            } else {
                //Time control is only enabled for ONE PER PAGE
                if (!empty($exe_id) && is_numeric($exe_id)) {
                    //Verify if the current test is fraudulent
                    if (exercise_time_control_is_valid($exerciseId)) {
                    	$sql_exe_result = "";                    	
                        if ($debug) { error_log('exercise_time_control_is_valid is valid'); }
                    } else {
                    	$sql_exe_result = ", exe_result = 0";
                        if ($debug) { error_log('exercise_time_control_is_valid is NOT valid then exe_result = 0 '); }
                    }
                    //Clean incomplete - @todo why setting to blank the status?                  
                    $update_query = "UPDATE $stat_table SET  status = '', exe_date = '".api_get_utc_datetime() ."' , orig_lp_item_view_id = '$safe_lp_item_view_id' $sql_exe_result  WHERE exe_id = ".$exe_id;
                    
                    if ($debug) { error_log('Updating track_e_exercises '.$update_query); }                    
                    Database::query($update_query);
                }                
                if ($debug) { error_log('Redirecting to exercise_show.php'); }
                header("Location: exercise_show.php?id=$exe_id&exerciseType=$exerciseType&origin=$origin&learnpath_id=$safe_lp_id&learnpath_item_id=$safe_lp_item_id&learnpath_item_view_id=$safe_lp_item_view_id");
                exit;
            }            
        } else {
            if ($debug) { error_log('Redirecting to exercise_submit.php'); }
            header("Location: exercise_submit.php?exerciseId=$exerciseId");
            exit;            
        }
    }
    if ($debug > 0) { error_log('$formSent was set - end'); }
}

$exerciseTitle 		   = $objExercise->selectTitle();
$exerciseDescription   = $objExercise->selectDescription();
$exerciseSound 		   = $objExercise->selectSound();
$exerciseType 		   = $objExercise->selectType();

//if (!isset($_SESSION['questionList']) || $origin == 'learnpath') {
//in LP's is enabled the "remember question" feature?
if (!isset($_SESSION['questionList'])) {    
    // selects the list of question ID        
    $questionList = ($objExercise->isRandom() ? $objExercise->selectRandomList() : $objExercise->selectQuestionList());
    api_session_register('questionList');
    if ($debug > 0) { error_log('$_SESSION[questionList] was set'); }
}
if (!isset ($objExercise) && isset ($_SESSION['objExercise'])) {
    $questionList = $_SESSION['questionList'];
}

$quizStartTime = time();
api_session_register('quizStartTime');
//Real question count
$nbrQuestions = count($questionList);

// if questionNum comes from POST and not from GET
if (!$questionNum || $_POST['questionNum']) {
    // only used for sequential exercises (see $exerciseType)
    if (!$questionNum) {
        $questionNum = 1;
    } else {
        $questionNum++;
    }
}

if (!empty ($_GET['gradebook']) && $_GET['gradebook'] == 'view') {
    $_SESSION['gradebook'] = Security :: remove_XSS($_GET['gradebook']);
    $gradebook = $_SESSION['gradebook'];
} elseif (empty ($_GET['gradebook'])) {
    unset ($_SESSION['gradebook']);
    $gradebook = '';
}

if (!empty ($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array ('url' => '../gradebook/' . Security::remove_XSS($_SESSION['gradebook_dest']),'name' => get_lang('ToolGradebook'));
}

$interbreadcrumb[] = array ("url" => "exercice.php?gradebook=$gradebook",	"name" => get_lang('Exercices'));
$interbreadcrumb[] = array ("url" => "#","name" => $objExercise->name);

if ($origin != 'learnpath') { //so we are not in learnpath tool
    //$htmlHeadXtra[] = $objExercise->show_lp_javascript();    
    Display :: display_header($nameTools,'Exercises');
    if (!api_is_allowed_to_session_edit() ) {
        Display :: display_warning_message(get_lang('SessionIsReadOnly'));
    }
} else {
    Display::display_reduced_header();
    echo '<div style="height:10px">&nbsp;</div>';
}

$show_quiz_edition = true;
if (isset($exerciseId) && !empty($exerciseId)) {
	$TBL_LP_ITEM	= Database::get_course_table(TABLE_LP_ITEM);
	$sql="SELECT max_score FROM $TBL_LP_ITEM WHERE item_type = '".TOOL_QUIZ."' AND path ='".$exerciseId."'";
	$result = Database::query($sql);
	if (Database::num_rows($result) > 0) {
		$show_quiz_edition = false;
	}
}

// I'm in a preview mode
if (api_is_course_admin() && $origin != 'learnpath') {
    echo '<div class="actions">';
    echo '<a href="exercice.php?show=test&id_session='.api_get_session_id().'">' . Display :: return_icon('back.png', get_lang('BackToExercisesList'),'','32').'</a>';
    if ($show_quiz_edition) {
    	echo '<a href="exercise_admin.php?' . api_get_cidreq() . '&modifyExercise=yes&exerciseId=' . $objExercise->id . '">'.Display :: return_icon('settings.png', get_lang('ModifyExercise'),'','32').'</a>';
        //echo Display :: return_icon('wizard.gif', get_lang('QuestionList')) . '<a href="exercice/admin.php?' . api_get_cidreq() . '&exerciseId=' . $objExercise->id . '">' . get_lang('QuestionList') . '</a>';
    } else {
    	echo '<a href="#">'.Display::return_icon('settings_na.png', get_lang('ModifyExercise'),'','32').'</a>';
    }
    echo '</div>';
}

$exerciseTitle = text_filter($objExercise->selectTitle());
echo Display::tag('h2', $exerciseTitle);
$show_clock = true;
$user_id = api_get_user_id();
if ($objExercise->selectAttempts() > 0) {	
	$attempt_count = get_attempt_count($user_id, $exerciseId, $safe_lp_id, $safe_lp_item_id, $safe_lp_item_view_id);
	
    if ($attempt_count >= $objExercise->selectAttempts()) {
    	$show_clock = false;
        if (!api_is_allowed_to_edit(null,true)) {
            
            if ($objExercise->results_disabled == 0 && $origin != 'learnpath') {
                
                //Showing latest attempt according with task BT#1628
                $exercise_stat_info = get_all_exercise_results_by_user($user_id, $exerciseId, api_get_course_id(), api_get_session_id());
                
                if (!empty($exercise_stat_info)) {               
                    $max_exe_id = max(array_keys($exercise_stat_info));
                    $last_attempt_info = $exercise_stat_info[$max_exe_id];
                    echo Display::div(get_lang('Date').': '.api_get_local_time($last_attempt_info['exe_date']), array('id'=>''));
                    
                    Display :: display_warning_message(sprintf(get_lang('ReachedMaxAttempts'), $exerciseTitle, $objExercise->selectAttempts()), false);                    
                    
                    if (!empty($last_attempt_info['question_list'])) {               
                        foreach($last_attempt_info['question_list'] as $question_data) {
                            $question_id = $question_data['question_id'];
                            $marks       = $question_data['marks'];
                            
                            $question_info = Question::read($question_id);                                                        
                            echo Display::div($question_info->question, array('id'=>'question_title','class'=>'sectiontitle'));                            
                            echo Display::div(get_lang('Score').' '.$marks, array('id'=>'question_score'));
                        }
                    }                    
                    $score =  show_score($last_attempt_info['exe_result'], $last_attempt_info['exe_weighting']);
                    echo Display::div(get_lang('YourTotalScore').' '.$score, array('id'=>'question_score'));
                    
                    
                    
                } else {
                    Display :: display_warning_message(sprintf(get_lang('ReachedMaxAttempts'), $exerciseTitle, $objExercise->selectAttempts()), false);	
                }
            } else {
                Display :: display_warning_message(sprintf(get_lang('ReachedMaxAttempts'), $exerciseTitle, $objExercise->selectAttempts()), false);
            }            
            if ($origin != 'learnpath')
                Display :: display_footer();
            exit;
        } else {            
            Display :: display_warning_message(sprintf(get_lang('ReachedMaxAttemptsAdmin'), $exerciseTitle, $objExercise->selectAttempts()), false);
        }
    }
}

$limit_time_exists = (($objExercise->start_time != '0000-00-00 00:00:00') || ($objExercise->end_time != '0000-00-00 00:00:00')) ? true : false;

if ($limit_time_exists) {	
    $exercise_start_time 	= api_strtotime($objExercise->start_time,'UTC');
    $exercise_end_time 		= api_strtotime($objExercise->end_time,'UTC');
    $time_now 				= time();
    
    if ($objExercise->start_time != '0000-00-00 00:00:00') {
        $permission_to_start = (($time_now - $exercise_start_time) > 0) ? true : false;
    } else {
        $permission_to_start = true;
    }         
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        if ($objExercise->end_time != '0000-00-00 00:00:00') {
            $exercise_timeover = (($time_now - $exercise_end_time) > 0) ? true : false;
        } else {
            $exercise_timeover = false;
        }        
    }
    
    if (!$permission_to_start || $exercise_timeover) {
        if (!api_is_allowed_to_edit(null,true)) {        	
            $message_warning = $permission_to_start ? get_lang('ReachedTimeLimit') : get_lang('ExerciseNoStartedYet');
            Display :: display_warning_message(sprintf($message_warning, $exerciseTitle, $objExercise->selectAttempts()));
            if ($origin != 'learnpath') {
            	Display :: display_footer();
            }
            exit;
        } else {
            $message_warning = $permission_to_start ? get_lang('ReachedTimeLimitAdmin') : get_lang('ExerciseNoStartedAdmin');
            Display :: display_warning_message(sprintf($message_warning, $exerciseTitle, $objExercise->selectAttempts()));
            exit;
        }
    }
}

//Timer control
if ($time_control) {
  echo '<div align="left" id="wrapper-clock"><div id="square" class="rounded"><div id="text-content" align="center" class="count_down"></div></div></div>';
  echo '<div style="display:none" class="warning-message" id="expired-message-id">'.get_lang('ExerciceExpiredTimeMessage').'</div>';
}

if ($origin != 'learnpath') {
   echo '<div id="highlight-plugin" class="glossary-content">';
}

if (!empty ($error)) {
    Display :: display_error_message($error, false);
} else {
    if (!empty ($exerciseSound)) {
        echo "<a href=\"../document/download.php?doc_url=%2Faudio%2F" . Security::remove_XSS($exerciseSound) . "\" target=\"_blank\">", "<img src=\"../img/sound.gif\" border=\"0\" align=\"absmiddle\" alt=", get_lang('Sound') . "\" /></a>";
    }
    // Get number of hotspot questions for javascript validation
    $number_of_hotspot_questions = 0;
    $onsubmit = '';
    $i = 0;
    //i have a doubt in this line cvargas
    //var_dump($questionList);
    if (!strcmp($questionList[0], '') === 0) {
        foreach ($questionList as $questionId) {
            $i++;
            $objQuestionTmp = Question::read($questionId);
            // for sequential exercises
            if ($exerciseType == ONE_PER_PAGE) {
                // if it is not the right question, goes to the next loop iteration
                if ($questionNum != $i) {
                    continue;
                } else {                    
                    if ($objQuestionTmp->selectType() == HOT_SPOT || $objQuestionTmp->selectType() == HOT_SPOT_DELINEATION) {
                        $number_of_hotspot_questions++;
                    }
                    break;
                }
            } else {
                if ($objQuestionTmp->selectType() == HOT_SPOT || $objQuestionTmp->selectType() == HOT_SPOT_DELINEATION) {
                    $number_of_hotspot_questions++;
                }
            }
        }
    }
    if ($number_of_hotspot_questions > 0) {
        $onsubmit = "onsubmit=\"return validateFlashVar('" . $number_of_hotspot_questions . "', '" . get_lang('HotspotValidateError1') . "', '" . get_lang('HotspotValidateError2') . "');\"";
    }
    echo "<p>$exerciseDescription</p>";
	$exercise_condition = '';
    if ($exerciseType == ONE_PER_PAGE) {
        $exercise_condition = "&exerciseId=" . $exerciseId;
    }
    echo '<form id="exercise_form" method="post" action="'.api_get_self().'?'.api_get_cidreq().'&autocomplete=off&gradebook='.$gradebook.$exercise_condition .'" name="frm_exercise" '.$onsubmit.'>
         <input type="hidden" name="formSent"		value="1" />
         <input type="hidden" name="exerciseType" 	value="' . $exerciseType . '" />
         <input type="hidden" name="exerciseId" 	value="' . $exerciseId . '" />
         <input type="hidden" name="questionNum" 	value="' . $questionNum . '" />
         <input type="hidden" name="nbrQuestions" 	value="' . $nbrQuestions . '" />
         <input type="hidden" name="origin" 		value="' . $origin . '" />
         <input type="hidden" name="learnpath_id" 	value="' . $learnpath_id . '" />
         <input type="hidden" name="learnpath_item_id" value="'. $learnpath_item_id . '" />
         <table id="question_list" width="100%" border="0" cellpadding="1" cellspacing="0">
            <tr>
                <td>
                    <table  width="100%" cellpadding="3" cellspacing="0" border="0">';
                        
		//Show list of questions
	    $i = 1;    
	    foreach ($questionList as $questionId) {
	       
	        // for sequential exercises
	        if ($exerciseType == ONE_PER_PAGE) {
	            // if it is not the right question, goes to the next loop iteration
	            if ($questionNum != $i) {
	                $i++;
	                continue;
	            } else {
	                if ($objExercise->feedbacktype != EXERCISE_FEEDBACK_TYPE_DIRECT) {
	                    // if the user has already answered this question
	                    if (isset ($exerciseResult[$questionId])) {
	                        // construction of the Question object
	                        $objQuestionTmp = Question::read($questionId);
	                        $questionName = $objQuestionTmp->selectTitle();
	                        // destruction of the Question object
	                        unset ($objQuestionTmp);
	                        Display :: display_normal_message(get_lang('AlreadyAnswered'));
	                        $i++;
	                        break;
	                    }
	                }
	            }
	        }        
	        // shows the question and its answers
	        showQuestion($questionId, false, $origin, $i);
	        $i++;
	        // for sequential exercises
	        if ($exerciseType == ONE_PER_PAGE) {
	            // quits the loop
	            break;
	        }
	    }    
	    // end foreach()
	    echo $objExercise->show_button($nbrQuestions, $questionNum, $exerciseId);     
	    echo '</table>
	            </td>
	            </tr>
	            </table></form>';
}
if ($objExercise->type == ONE_PER_PAGE) {	
  	if (empty($exercise_stat_info)) {
        $total_weight = 0; 	    
  	    foreach($questionList as $question_id) {
  	        $objQuestionTmp = Question::read($question_id);  	        
  	        $total_weight += floatval($objQuestionTmp->weighting);
  	    }
  		$objExercise->save_stat_track_exercise_info($clock_expired_time, $safe_lp_id, $safe_lp_item_id, $safe_lp_item_view_id, $questionList, $total_weight);
    }
}
if ($origin != 'learnpath') {
    //so we are not in learnpath tool
    echo '</div>'; //End glossary div
    Display :: display_footer();
} else {
    echo '</body></html>';
}

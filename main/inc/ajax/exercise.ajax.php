<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

require_once '../../exercice/exercise.class.php';
require_once '../../exercice/question.class.php';
require_once '../../exercice/answer.class.php';
require_once '../global.inc.php';
require_once '../../exercice/exercise.lib.php';
api_protect_course_script(true);
$action = $_REQUEST['a'];
$course_id = api_get_course_int_id();

switch ($action) {
    case 'get_live_stats':
        // 1. Setting variables needed by jqgrid
        $action= $_GET['a'];
        $exercise_id = intval($_GET['exercise_id']);
        $page  = intval($_REQUEST['page']); //page
        $limit = intval($_REQUEST['rows']); //quantity of rows
        $sidx  = $_REQUEST['sidx'];         //index to filter         
        $sord  = $_REQUEST['sord'];         //asc or desc
        if (!in_array($sord, array('asc','desc'))) {
            $sord = 'desc'; 
        }
        // get index row - i.e. user click to sort $sord = $_GET['sord']; 
        // get the direction 
        if (!$sidx) $sidx = 1;
        
        $track_exercise        = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
        $track_attempt         = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        $now    = time() - 60*30;
        $now    = api_get_utc_datetime($now);
        
        $where_condition = "WHERE status = 'incomplete' AND exe_exo_id = $exercise_id AND start_date > '$now' ";
        $sql    = "SELECT COUNT(DISTINCT exe_id) FROM $track_exercise  $where_condition ";
        $result = Database::query($sql);
        
        $count  = Database::fetch_row($result);
        $count  = $count[0];  
        
        //3. Calculating first, end, etc
                
        $total_pages = 0;
        if ($count > 0) { 
            if (!empty($limit)) {
                $total_pages = ceil($count/$limit);
            }
        }
        if ($page > $total_pages) { 
            $page = $total_pages;
        }     
        
        $start = $limit * $page - $limit;
        if ($start < 0 ) {
            $start = 0;
        }        
        
        $sql    = "SELECT * FROM $track_exercise $where_condition ";
        $result = Database::query($sql);
        $results = array();
        
        while ($row = Database::fetch_array($result,'ASSOC')){
            $results[] = $row;
        }        
        $response = new stdClass();           
        $response->page     = $page; 
        $response->total    = $total_pages; 
        $response->records  = $count; 
        $i=0;
        if (!empty($results)) {
            foreach($results as $row) {
                 //print_r($row);
                 $response->rows[$i]['id'] = $row['exe_id'];
                 $array = array();
                 $array['firstname'] = 'fff';
                 $array['lastname'] = 'fff';                  
                                    
                 $response->rows[$i]['cell'] = $array;
                 $i++; 
            }
        } 
        echo json_encode($response); 
        
        break;            
    case 'update_question_order':
        if (api_is_allowed_to_edit(null, true)) {    
            $new_question_list     = $_POST['question_id_list'];
            $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);        
            $counter = 1;
            foreach ($new_question_list as $new_order_id) {            
                Database::update($TBL_QUESTIONS, array('question_order'=>$counter), array('question_id = ? AND c_id = ? '=>array(intval($new_order_id), $course_id)));
                $counter++;
            }
            Display::display_confirmation_message(get_lang('Saved'));
        }
        break;
    case 'add_question_to_reminder':    	    	
    	$objExercise             = $_SESSION['objExercise'];    	
    	if (empty($objExercise)) {
    		echo 0;
    		exit;
    	} else {
    		$objExercise->edit_question_to_remind($_REQUEST['exe_id'], $_REQUEST['question_id'], $_REQUEST['action']); 
    	}
    	break;
    case 'save_exercise_by_now':
        //Use have permissions?
        if (api_is_allowed_to_session_edit()) {
        	            
            //"all" or "simple" strings means that there's one or all questions           
            $type                    = $_REQUEST['type'];            
            
            //Normal questions choices
            $choice                  = $_REQUEST['choice'];
            
            //All Hotspot coordinates from all questions 
            $hot_spot_coordinates    = $_REQUEST['hotspot'];
            
            
            //There is a reminder?
            $remind_list             = isset($_REQUEST['remind_list']) && !empty($_REQUEST['remind_list'])? array_keys($_REQUEST['remind_list']) : null;
               
            $exe_id = $_REQUEST['exe_id'];
            
            //Exercise information            
            $question_id             = intval($_REQUEST['question_id']);            
            $question_list           = $_SESSION['questionList'];
            $objExercise             = $_SESSION['objExercise'];
            
            if (empty($question_list) || empty($objExercise)) {
                echo 0;
                exit;
            }            
            //Getting information of the current exercise    
            $exercise_stat_info = $objExercise->get_stat_track_exercise_info_by_exe_id($exe_id);           
            
            $attempt_list = array();
              
            //First time here we create an attempt (getting the exe_id)
            if (empty($exercise_stat_info)) {           
            	/* 
                //$exe_id = create_event_exercice($objExercise->selectId());                
                $current_expired_time_key = get_time_control_key($objExercise->id);
                if (isset($_SESSION['expired_time'][$current_expired_time_key])) { //Only for exercice of type "One page"
                	$expired_date = $_SESSION['expired_time'][$current_expired_time_key];
                } else {
                	$expired_date = '0000-00-00 00:00:00';
                }
                $exe_id = $objExercise->save_stat_track_exercise_info($expired_date, $safe_lp_id, $safe_lp_item_id, $safe_lp_item_view_id, $question_list, 0); //total weight 0 by now                
                $total_score = $total_weight = 0;
                */
            } else {               
                //We know the user we get the exe_id
                $exe_id        = $exercise_stat_info['exe_id'];
                $total_score   = $exercise_stat_info['exe_result'];
                
                //Getting the list of attempts
                $attempt_list  = get_all_exercise_event_by_exe_id($exe_id);
            }
            
            
            //Updating Reminder algorythm
            if ($objExercise->type == ONE_PER_PAGE) {
            	$bd_reminder_list = explode(',', $exercise_stat_info['questions_to_check']);
            
            	if (empty($remind_list)) {
            		$remind_list = $bd_reminder_list;
            
            		$new_list = array();
            		foreach($bd_reminder_list as $item) {
            			if ($item != $question_id) {
            				$new_list[] = $item;
            			}
            		}
            		$remind_list = $new_list;
            	} else {
            		if (isset($remind_list[0])) {
            			if (!in_array($remind_list[0], $bd_reminder_list)) {
            				array_push($bd_reminder_list, $remind_list[0]);
            			}
            			$remind_list = $bd_reminder_list;
            		}
            	}
            }
            
            
            //No exe id? Can't save answer
            if (empty($exe_id)) {
                //Fires an error 
                echo 'error';
                exit;
            } else {
                $_SESSION['exe_id'] = $exe_id;
            }
            
            // Getting the total weight if the request is simple
            $total_weight = 0;
            if ($type == 'simple') {                
                foreach($question_list as $my_question_id) {
                    $objQuestionTmp  = Question :: read($my_question_id);
                    $total_weight   += $objQuestionTmp->selectWeighting();
                }
            }
            
           unset($objQuestionTmp);
            
            //Looping the question list
            
            foreach($question_list as $my_question_id) {                
                if ($type == 'simple' && $question_id != $my_question_id) {
                    continue;
                }             
                 
                $my_choice = $choice[$my_question_id];
                                
               // creates a temporary Question object
            	$objQuestionTmp        = Question::read($my_question_id);
            	
            	//Getting free choice data
            	if ($objQuestionTmp->type  == FREE_ANSWER && $type == 'all') {
            	    $my_choice = isset($_REQUEST['free_choice'][$my_question_id]) && !empty($_REQUEST['free_choice'][$my_question_id])? $_REQUEST['free_choice'][$my_question_id]: null;            	                	    
            	}  
            	
                if ($type == 'all') {  
                    $total_weight += $objQuestionTmp->selectWeighting();
                }      
            	
            	//this variable commes from exercise_submit_modal.php
            	$hotspot_delineation_result = $_SESSION['hotspot_delineation_result'][$objExercise->selectId()][$my_question_id];
            	
            	// Deleting old attempt
                if (isset($attempt_list) && !empty($attempt_list[$my_question_id])) {                    
                    delete_attempt($exe_id, api_get_user_id() , api_get_course_id(), api_get_session_id(), $my_question_id);
                    if ($objQuestionTmp->type  == HOT_SPOT) {            	        
            	        delete_attempt_hotspot($exe_id, api_get_user_id() , api_get_course_id(), $my_question_id);
                    }
            	    $total_score  -= $attempt_list[$my_question_id]['marks'];            	    
            	}
            	
            	// We're inside *one* question. Go through each possible answer for this question
            	$result = $objExercise->manage_answer($exe_id, $my_question_id, $my_choice,'exercise_result', $hot_spot_coordinates, true, false, $show_results, $objExercise->selectPropagateNeg(), $hotspot_delineation_result, true);
            	  	
                $total_score     += $result['score'];    
              	
                update_event_exercice($exe_id, $objExercise->selectId(), $total_score, $total_weight, api_get_session_id(), $exercise_stat_info['orig_lp_id'], $exercise_stat_info['orig_lp_item_id'], $exercise_stat_info['orig_lp_item_view_id'], $exercise_stat_info['exe_duration'], $question_list, 'incomplete', $remind_list);
                
                 // Destruction of the Question object
            	unset($objQuestionTmp); 
            }            
        }
        
        if ($objExercise->type == ONE_PER_PAGE) {
            echo 'one_per_page';
            exit;
        }
        echo 'ok';
        break;
    default:
        echo '';
}
exit;
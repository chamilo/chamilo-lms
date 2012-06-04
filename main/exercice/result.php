<?php
/* For licensing terms, see /license.txt */
/**
 *  Shows the exercise results
 *
 * @author Julio Montoya Armas  - Simple exercise result page
 *
 */

/**
 * Code
 */
// name of the language file that needs to be included
$language_file = array('exercice');

// including additional libraries
require_once 'exercise.class.php';
require_once 'question.class.php'; //also defines answer type constants
require_once 'answer.class.php';

require_once '../inc/global.inc.php';
require_once 'exercise.lib.php';

if (empty($origin)) {
    $origin = $_REQUEST['origin'];
}

if ($origin == 'learnpath')
	api_protect_course_script();
else
	api_protect_course_script(true);

$id 	       = isset($_REQUEST['id']) 	  ? intval($_GET['id']) : null; //exe id
$show_headers  = isset($_GET['show_headers']) ? intval($_GET['show_headers']) : null; //exe id

if ($origin == 'learnpath') {
	$show_headers = false;
}

if (empty($id)) {
	api_not_allowed();
}

$is_allowedToEdit   = api_is_allowed_to_edit(null,true) || $is_courseTutor;

//Getting results from the exe_id. This variable also contain all the information about the exercise
$track_exercise_info = get_exercise_track_exercise_info($id);

//No track info
if (empty($track_exercise_info)) {
    api_not_allowed(false);
}
$exercise_id        = $track_exercise_info['id'];
$exercise_date      = $track_exercise_info['start_date'];
$student_id         = $track_exercise_info['exe_user_id'];
$learnpath_id       = $track_exercise_info['orig_lp_id'];
$learnpath_item_id  = $track_exercise_info['orig_lp_item_id'];
$lp_item_view_id    = $track_exercise_info['orig_lp_item_view_id'];
$course_code        = api_get_course_id();
$current_user_id    = api_get_user_id();

if (empty($objExercise)) {
	$objExercise = new Exercise();
    $objExercise->read($exercise_id);
}

//Only users can see their own results
if (!$is_allowedToEdit) {
    if ($student_id != $current_user_id) {
    	api_not_allowed();
    }
}

if ($show_headers) {
	$interbreadcrumb[] = array("url" => "exercice.php","name" => get_lang('Exercices'));
	$interbreadcrumb[] = array("url" => "#","name" => get_lang('Result'));
	$this_section = SECTION_COURSES;

	Display::display_header();
} else {
	Display::display_reduced_header();
}

$show_results           = true;
$show_only_total_score  = false;
$display_category_name  = 1;

// Avoiding the "Score 0/0" message  when the exe_id is not set
if (!empty($track_exercise_info)) {
	$exerciseTitle			= $track_exercise_info['title'];
	$exerciseDescription	= $track_exercise_info['description'];
	// if the results_disabled of the Quiz is 1 when block the script
	$result_disabled		= $track_exercise_info['results_disabled'];
	$display_category_name = $track_exercise_info['display_category_name'];

	if (!(api_is_platform_admin() || api_is_course_admin()) ) {
		if ($result_disabled == EXERCISE_FEEDBACK_TYPE_DIRECT) {
			$show_results = false;
			Display::display_warning_message(get_lang('CantViewResults'));
		} elseif ($result_disabled == EXERCISE_FEEDBACK_TYPE_EXAM) {
		    $show_results = false;
		    $show_only_total_score = true;
		}
	}
}

if ($show_results || $show_only_total_score) {
    $user_info   = api_get_user_info($student_id);
    //Shows exercise header
    $objExercise->description = '';
    echo $objExercise->show_exercise_result_header(api_get_person_name($user_info['firstName'], $user_info['lastName']), api_convert_and_format_date($exercise_date, DATE_TIME_FORMAT_LONG));
}

$i = $totalScore = $totalWeighting = 0;
$result = get_exercise_results_by_attempt($id);

$question_list = $result[$id]['question_list'];

// for each question
$total_weighting = 0;
$counter = 1;
if (!empty($question_list)) {

	foreach ($question_list as $question_item) {
    	$objQuestionTmp     = Question::read($question_item['question_id'], api_get_course_int_id());
    	$total_weighting   += $objQuestionTmp->selectWeighting();
	}


	foreach ($question_list as $question_item) {
		$choice = $question_item['answer'];

		// creates a temporary Question object
		$questionId 		= $question_item['question_id'];
		$objQuestionTmp 	= Question::read($questionId, api_get_course_int_id());

		$questionName		= $objQuestionTmp->selectTitle();
		$questionDescription= $objQuestionTmp->selectDescription();
		$questionWeighting	= $objQuestionTmp->selectWeighting();
		$answerType			= $objQuestionTmp->selectType();
		$quesId 			= $objQuestionTmp->selectId();

	 	if ($show_results) {
            // display question category, if any
 	        Testcategory::displayCategoryAndTitle($questionId, $display_category_name );
		    echo $objQuestionTmp->return_header($objExercise->feedback_type, $counter);
		}
		$counter++;
		if ($answerType == MULTIPLE_ANSWER || $answerType == MULTIPLE_ANSWER_TRUE_FALSE) {
	        $question_result = $objExercise->manage_answer($id, $questionId, $choice,'exercise_show', array(), false, true, $show_results, $objExercise->selectPropagateNeg());
	        $questionScore   = $question_result['score'];
	        $totalScore      += $question_result['score'];
		} elseif ($answerType == MULTIPLE_ANSWER_COMBINATION || $answerType ==  MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE) {
			$choice = array();
	        $question_result = $objExercise->manage_answer($id, $questionId, $choice,'exercise_show', array(), false, true, $show_results, $objExercise->selectPropagateNeg());
	        $questionScore   = $question_result['score'];
	        $totalScore     += $question_result['score'];
		} elseif ($answerType == UNIQUE_ANSWER || $answerType ==  UNIQUE_ANSWER_NO_OPTION) {
	        $question_result = $objExercise->manage_answer($id, $questionId, $choice,'exercise_show', array(), false, true, $show_results, $objExercise->selectPropagateNeg());
	        $questionScore   = $question_result['score'];
	        $totalScore     += $question_result['score'];
		} elseif ($answerType == FILL_IN_BLANKS) {
	        $question_result = $objExercise->manage_answer($id, $questionId, $choice,'exercise_show', array(), false, true, $show_results, $objExercise->selectPropagateNeg());
	        $questionScore   = $question_result['score'];
	        $totalScore     += $question_result['score'];
		} elseif ($answerType == FREE_ANSWER) {
	        $answer = $str;
	        $question_result = $objExercise->manage_answer($id, $questionId, $choice,'exercise_show', array(), false, true, $show_results, $objExercise->selectPropagateNeg());
	        $questionScore   = $question_result['score'];
	        $totalScore     += $question_result['score'];
		} elseif ($answerType == MATCHING) {
	        $question_result = $objExercise->manage_answer($id, $questionId, $choice,'exercise_show', array(), false, true, $show_results, $objExercise->selectPropagateNeg());
	        $questionScore   = $question_result['score'];
	        $totalScore     += $question_result['score'];
		} elseif ($answerType == HOT_SPOT) {
			//@todo move this in the manage_answer function
		    if ($show_results) {
			    echo '<table width="500" border="0"><tr>
	                    <td valign="top" align="center" style="padding-left:0px;" >
	                        <table border="1" bordercolor="#A4A4A4" style="border-collapse: collapse;" width="552">';
			}
	        $question_result = $objExercise->manage_answer($id, $questionId, $choice,'exercise_show', array(), false, true, $show_results, $objExercise->selectPropagateNeg());
	        $questionScore  = $question_result['score'];
	        $totalScore    += $question_result['score'];

	        if ($show_results) {
				echo '</table></td></tr>';
			 	echo '<tr>
					<td colspan="2">'.
						'<object type="application/x-shockwave-flash" data="'.api_get_path(WEB_CODE_PATH).'plugin/hotspot/hotspot_solution.swf?modifyAnswers='.Security::remove_XSS($questionId).'&exe_id='.$id.'&from_db=1" width="552" height="352">
							<param name="movie" value="../plugin/hotspot/hotspot_solution.swf?modifyAnswers='.Security::remove_XSS($questionId).'&exe_id='.$id.'&from_db=1" />
						</object>
					</td>
				</tr>
				</table><br/>';
	        }

		} else if($answerType == HOT_SPOT_DELINEATION) {

	            $question_result  = $objExercise->manage_answer($id, $questionId, $choice,'exercise_show', array(), false, true, $show_results, $objExercise->selectPropagateNeg(), 'database');

	            $questionScore    = $question_result['score'];
	            $totalScore      += $question_result['score'];

	            $final_overlap    = $question_result['extra']['final_overlap'];
	            $final_missing    = $question_result['extra']['final_missing'];
	            $final_excess     = $question_result['extra']['final_excess'];

	            $overlap_color    = $question_result['extra']['overlap_color'];
	            $missing_color    = $question_result['extra']['missing_color'];
	            $excess_color     = $question_result['extra']['excess_color'];

	            $threadhold1      = $question_result['extra']['threadhold1'];
	            $threadhold2      = $question_result['extra']['threadhold2'];
	            $threadhold3      = $question_result['extra']['threadhold3'];


		        if ($show_results) {

	        	    if ($overlap_color) {
	        			$overlap_color='green';
	        	    } else {
	        			$overlap_color='red';
	        	    }

	        		if ($missing_color) {
	        			$missing_color='green';
	        	    } else {
	        			$missing_color='red';
	        	    }
	        		if ($excess_color) {
	        			$excess_color='green';
	        	    } else {
	        			$excess_color='red';
	        	    }


	        	    if (!is_numeric($final_overlap)) {
	            	    $final_overlap = 0;
	        	    }

	        	    if (!is_numeric($final_missing)) {
	        	    	$final_missing = 0;
	        	    }
	        	    if (!is_numeric($final_excess)) {
	        	    	$final_excess = 0;
	        	    }

	        	    if ($final_excess>100) {
	        	    	$final_excess = 100;
	        	    }

	        		$table_resume='<table class="data_table">
	        		<tr class="row_odd" >
	        		<td></td>
	        		<td ><b>'.get_lang('Requirements').'</b></td>
	        		<td><b>'.get_lang('YourAnswer').'</b></td>
	        		</tr>

	        		<tr class="row_even">
	        		<td><b>'.get_lang('Overlap').'</b></td>
	        		<td>'.get_lang('Min').' '.$threadhold1.'</td>
	        			<td><div style="color:'.$overlap_color.'">'.(($final_overlap < 0)?0:intval($final_overlap)).'</div></td>
	        		</tr>

	        		<tr>
	        			<td><b>'.get_lang('Excess').'</b></td>
	        			<td>'.get_lang('Max').' '.$threadhold2.'</td>
	        			<td><div style="color:'.$excess_color.'">'.(($final_excess < 0)?0:intval($final_excess)).'</div></td>
	        		</tr>

	        		<tr class="row_even">
	        			<td><b>'.get_lang('Missing').'</b></td>
	        			<td>'.get_lang('Max').' '.$threadhold3.'</td>
	        			<td><div style="color:'.$missing_color.'">'.(($final_missing < 0)?0:intval($final_missing)).'</div></td>
	        		</tr></table>';

	        		if ($answerType!= HOT_SPOT_DELINEATION) {
	        			$item_list=explode('@@',$destination);
	        			//print_R($item_list);
	        			$try = $item_list[0];
	        			$lp = $item_list[1];
	        			$destinationid= $item_list[2];
	        			$url=$item_list[3];
	        			$table_resume='';
	        		} else {
	        			if ($next==0) {
	        				$try = $try_hotspot;
	        				$lp = $lp_hotspot;
	        				$destinationid= $select_question_hotspot;
	        				$url=$url_hotspot;
	        			} else {
	        				//show if no error
	        				//echo 'no error';
	        				$comment=$answerComment=$objAnswerTmp->selectComment($nbrAnswers);
	        				$answerDestination=$objAnswerTmp->selectDestination($nbrAnswers);
	        			}
	        		}

	        		echo '<h1><div style="color:#333;">'.get_lang('Feedback').'</div></h1>';
	        		if ($answerType == HOT_SPOT_DELINEATION) {
	        			if ($organs_at_risk_hit>0) {
	        				$message='<br />'.get_lang('ResultIs').' <b>'.$result_comment.'</b><br />';
	        				$message.='<p style="color:#DC0A0A;"><b>'.get_lang('OARHit').'</b></p>';
	        			} else {
	        				$message='<p>'.get_lang('YourDelineation').'</p>';
	        				$message.=$table_resume;
	        				$message.='<br />'.get_lang('ResultIs').' <b>'.$result_comment.'</b><br />';
	        			}
	        			$message.='<p>'.$comment.'</p>';
	        			echo $message;
	        		} else {
	        			echo '<p>'.$comment.'</p>';
	        		}

	        		//showing the score
	        		/*
	         		$queryfree = "select marks from ".$TBL_TRACK_ATTEMPT." where exe_id = '".Database::escape_string($id)."' and question_id= '".Database::escape_string($questionId)."'";
	        		$resfree = Database::query($queryfree);
	        		$questionScore= Database::result($resfree,0,"marks");
	        		$totalScore+=$questionScore;*/
	        		 			?>
	        		 			</table>
	        		 		</td></tr>
	        		 		<?php
	        		 	echo '<tr>
	        				<td colspan="2">
	        					<object type="application/x-shockwave-flash" data="../plugin/hotspot/hotspot_solution.swf?modifyAnswers='.$questionId.'&exe_id='.$id.'&from_db=1" width="556" height="350">
	        						<param name="movie" value="../plugin/hotspot/hotspot_solution.swf?modifyAnswers='.$questionId.'&exe_id='.$id.'&from_db=1" />

	        					</object>
	        				</td>
	        			</tr>
	        			</table>';
		        }
		}

		if ($show_results) {
		    if ($answerType != HOT_SPOT) {
		        echo '</table>';
		    }
		}

		if ($show_results) {
			$comnt = get_comments($id, $questionId);
			if (!empty($comnt)) {
				echo '<b>'.get_lang('Feedback').'</b>';
				echo '<div id="question_feedback">'.$comnt.'</div>';
			}
		}

		$my_total_score  = $questionScore;
		$my_total_weight = $questionWeighting;

	    if ($objExercise->selectPropagateNeg() == 0 && $my_total_score < 0) {
	        $my_total_score = 0;
	    }
	    if ($show_results) {
		    echo '<div id="question_score">';
			echo get_lang('Score')." : ".show_score($my_total_score, $my_total_weight, false, false);
			echo '</div>';
	    }
		unset($objAnswerTmp);
		unset($objQuestionTmp);
		$i++;
		$totalWeighting += $questionWeighting;

	} // end of large foreach on questions
}

//Total score
if ($show_results || $show_only_total_score) {
	echo '<div id="question_score">'.get_lang('YourTotalScore').": ";
    $my_total_score_temp = $totalScore;
	if ($objExercise->selectPropagateNeg() == 0 && $my_total_score_temp < 0) {
		$my_total_score_temp = 0;
	}
    echo show_score($my_total_score_temp, $totalWeighting, false, true, true, $objExercise->selectPassPercentage());
	echo '</div>';
}

if ($show_headers) {
	Display::display_footer();
}
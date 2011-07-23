<?php
/* For licensing terms, see /license.txt */
/**
 *  Shows the exercise results 
 *
 * @author Julio Montoya Armas Added switchable fill in blank option added
 * @version $Id: exercise_show.php 22256 2009-07-20 17:40:20Z ivantcholakov $
 * @package chamilo.exercise
 * @todo remove the debug code and use the general debug library
 * @todo small letters for table variables
 * 
 */
/**
 * Code
 */
// name of the language file that needs to be included
$language_file = array('exercice');

// including additional libraries
require_once 'exercise.class.php';
require_once 'exercise.lib.php';
require_once 'question.class.php'; //also defines answer type constants
require_once 'answer.class.php';

require_once '../inc/global.inc.php';

require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';

if (empty($origin) ) {
    $origin = $_REQUEST['origin'];
}

if ($origin == 'learnpath')
	api_protect_course_script();
else
	api_protect_course_script(true);

// Database table definitions
$TBL_EXERCICE_QUESTION 	= Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_EXERCICES         	= Database::get_course_table(TABLE_QUIZ_TEST);
$TBL_QUESTIONS         	= Database::get_course_table(TABLE_QUIZ_QUESTION);
$TBL_REPONSES          	= Database::get_course_table(TABLE_QUIZ_ANSWER);
$main_user_table 		= Database::get_main_table(TABLE_MAIN_USER);
$main_course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$TBL_TRACK_EXERCICES	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$TBL_TRACK_ATTEMPT		= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

// General parameters passed via POST/GET
if($debug) { error_log('Entered exercise_result.php: '.print_r($_POST,1)); }

if ( empty ( $formSent ) ) {            $formSent       = $_REQUEST['formSent']; }
if ( empty ( $exerciseResult ) ) {      $exerciseResult = $_SESSION['exerciseResult'];}
if ( empty ( $questionId ) ) {          $questionId     = $_REQUEST['questionId'];}
if ( empty ( $choice ) ) {              $choice         = $_REQUEST['choice'];}
if ( empty ( $questionNum ) ) {         $questionNum    = $_REQUEST['questionNum'];}
if ( empty ( $nbrQuestions ) ) {        $nbrQuestions   = $_REQUEST['nbrQuestions'];}
if ( empty ( $questionList ) ) {        $questionList   = $_SESSION['questionList'];}
if ( empty ( $objExercise ) ) {         $objExercise    = $_SESSION['objExercise'];}
if ( empty ( $exeId ) ) {               $exeId          = $_REQUEST['id'];}
if ( empty ( $action ) ) {              $action         = $_REQUEST['action']; }

//$emailId       = $_REQUEST['email'];
$id 	       = intval($_REQUEST['id']); //exe id
$current_time  = time();

if (empty($id)) {
	api_not_allowed();
}

$is_allowedToEdit   = api_is_allowed_to_edit(null,true) || $is_courseTutor;

//Getting results from the exe_id. This variable also contain all the information about the exercise
$track_exercise_info = get_exercise_track_exercise_info($id);

//No track info
if (empty($track_exercise_info)) {
    api_not_allowed();
}

$exercise_id        = $track_exercise_info['id'];
$exercise_date      = $track_exercise_info['exe_date'];
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
$feedback_type = $objExercise->feedbacktype;

//If is not valid
$session_control_key = get_session_time_control_key($exercise_id);
if (isset($session_control_key) && !exercise_time_control_is_valid($exercise_id) && !in_array($action, array('qualify','edit'))) {
    $sql_fraud = "UPDATE $TBL_TRACK_ATTEMPT SET answer = 0, marks=0, position=0 WHERE exe_id = $id ";
    Database::query($sql_fraud);
}

//Only users can see their own results 
if (!$is_allowedToEdit) {
    if ($student_id != $current_user_id) {
    	api_not_allowed();
    }
}

//Unset session for clock time
exercise_time_control_delete($exercise_id);

$nameTools=get_lang('CorrectTest');
if (isset($_SESSION['gradebook'])) {
	$gradebook=	Security::remove_XSS($_SESSION['gradebook']);
}

if (!empty($gradebook) && $gradebook=='view') {
	$interbreadcrumb[]= array ('url' => '../gradebook/'.$_SESSION['gradebook_dest'],'name' => get_lang('ToolGradebook'));
}

$fromlink = '';
if($origin=='user_course') {
	$interbreadcrumb[] = array ("url" => "../user/user.php?cidReq=".Security::remove_XSS($_GET['course']), "name" => get_lang("Users"));
	$interbreadcrumb[] = array("url" => "../mySpace/myStudents.php?student=".$student_id."&course=".$_course['id']."&details=true&origin=".Security::remove_XSS($_GET['origin']) , "name" => get_lang("DetailsStudentInCourse"));
} else if($origin=='tracking_course') {
	//$interbreadcrumb[] = array ("url" => "../mySpace/index.php", "name" => get_lang('MySpace'));
 	//$interbreadcrumb[] = array ("url" => "../mySpace/myStudents.php?student=".Security::remove_XSS($student_id).'&details=true&origin='.$origin.'&course='.Security::remove_XSS($_GET['cidReq']), "name" => get_lang("DetailsStudentInCourse"));
 	$interbreadcrumb[] = array ("url" => api_get_path(WEB_COURSE_PATH).$_course['directory'], 'name' => $_course['title']);
	$interbreadcrumb[] = array ("url" => "../tracking/courseLog.php?cidReq=".$cidReq.'&studentlist=true&id_session='.$_SESSION['id_session'], "name" => get_lang("Tracking"));
	$interbreadcrumb[] = array ("url" => "../mySpace/myStudents.php?student=".$student_id.'&details=true&origin='.$origin.'&course='.Security::remove_XSS($_GET['cidReq']), "name" => get_lang("DetailsStudentInCourse"));
	$interbreadcrumb[] = array ("url" => "../mySpace/lp_tracking.php?action=stats&course=".$cidReq."&student_id=".$student_id."&lp_id=".Security::remove_XSS($_GET['my_lp_id'])."&origin=".Security::remove_XSS($_GET['origin']) , "name" => get_lang("LearningPathDetails"));

	$from_myspace = false;
	if (isset ($_GET['from']) && $_GET['from'] == 'myspace') {
		$fromlink = '&from=myspace';
		$this_section = SECTION_TRACKING;
	} else {
		$this_section = SECTION_COURSES;
	}
} elseif($origin=='student_progress') {
	$this_section = SECTION_TRACKING;
	$interbreadcrumb[] = array ("url" => "../auth/my_progress.php?id_session".Security::remove_XSS($_GET['id_session'])."&course=".$_cid, "name" => get_lang('MyProgress'));
	unset($_cid);
} else {
	$interbreadcrumb[]=array("url" => "exercice.php?gradebook=$gradebook","name" => get_lang('Exercices'));
	$this_section=SECTION_COURSES;
}

if ($origin != 'learnpath') {
	Display::display_header($nameTools,get_lang('Exercise'));
} else {
	Display::display_reduced_header();
}

?>
<style type="text/css">
<!--
#comments {
	position:absolute;
	left:795px;
	top:0px;
	width:200px;
	height:75px;
	z-index:1;
}
-->
</style>
<script language="javascript">
function showfck(sid,marksid) {
	document.getElementById(sid).style.display='block';
	document.getElementById(marksid).style.display='block';
	var comment = 'feedback_'+sid;
	document.getElementById(comment).style.display='none';
}

function getFCK(vals,marksid) {
	var f=document.getElementById('myform');

	var m_id = marksid.split(',');
	for(var i=0;i<m_id.length;i++){
		var oHidn = document.createElement("input");
		oHidn.type = "hidden";
		var selname = oHidn.name = "marks_"+m_id[i];
		var selid = document.forms['marksform_'+m_id[i]].marks.selectedIndex;
		oHidn.value = document.forms['marksform_'+m_id[i]].marks.options[selid].text;
		f.appendChild(oHidn);
	}

	var ids = vals.split(',');
	for(var k=0;k<ids.length;k++){
		var oHidden = document.createElement("input");
		oHidden.type = "hidden";
		oHidden.name = "comments_"+ids[k];
		oEditor = FCKeditorAPI.GetInstance(oHidden.name) ;
		oHidden.value = oEditor.GetXHTML(true);
		f.appendChild(oHidden);
	}
//f.submit();
}
</script>
<?php
$show_results           = true;
$show_only_total_score  = false;

// Avoiding the "Score 0/0" message  when the exe_id is not set
if (!empty($track_exercise_info)) {
	$exerciseTitle			= text_filter($track_exercise_info['title']);
	$exerciseDescription	= $track_exercise_info['description'];
	// if the results_disabled of the Quiz is 1 when block the script
	$result_disabled		= $track_exercise_info['results_disabled'];
	
	if (!(api_is_platform_admin() || api_is_course_admin()) ) {    
		if ($result_disabled == 1) {		    
			//api_not_allowed();
			$show_results = false;
			//Display::display_warning_message(get_lang('CantViewResults'));
			if ($origin != 'learnpath') {
			    echo '<table width="100%" border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td colspan="2">';
				Display::display_warning_message(get_lang('ThankYouForPassingTheTest').'<br /><br /><a href="exercice.php">'.(get_lang('BackToExercisesList')).'</a>', false);
				echo '</td>
				</tr>
				</table>';
			}
		} elseif ($result_disabled == 2) {
		    $show_results = false;
		    $show_only_total_score = true;			  
			if ($origin != 'learnpath') {
			    echo '<table width="100%" border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td colspan="2">';
				Display::display_warning_message(get_lang('ThankYouForPassingTheTest'), false);
				echo '</td>
				</tr>
				</table>';
			}			  
		}       
	}
} else {
	Display::display_warning_message(get_lang('CantViewResults'));
	$show_results = false;
}

if ($origin == 'learnpath' && !isset($_GET['fb_type']) ) {
	$show_results = false;
}
$html = '';
if ($show_results || $show_only_total_score) {
    $user_info   = api_get_user_info($student_id);
    //Shows exercise header
    echo $objExercise->show_exercise_result_header(api_get_person_name($user_info['firstName'], $user_info['lastName']), api_convert_and_format_date($exercise_date));
}

$i=$totalScore=$totalWeighting=0;

if($debug>0){error_log("ExerciseResult: ".print_r($exerciseResult,1)); error_log("QuestionList: ".print_r($questionList,1));}

$arrques = array();
$arrans  = array();

$user_restriction = $is_allowedToEdit ? '' :  "AND user_id=".intval($student_id)." ";
$query = "SELECT attempts.question_id, answer  from ".$TBL_TRACK_ATTEMPT." as attempts
				INNER JOIN ".$TBL_TRACK_EXERCICES." as stats_exercices ON stats_exercices.exe_id=attempts.exe_id
				INNER JOIN ".$TBL_EXERCICE_QUESTION." as quizz_rel_questions ON quizz_rel_questions.exercice_id=stats_exercices.exe_exo_id AND quizz_rel_questions.question_id = attempts.question_id
				INNER JOIN ".$TBL_QUESTIONS." as questions ON questions.id=quizz_rel_questions.question_id
		  WHERE attempts.exe_id='".Database::escape_string($id)."' $user_restriction
		  GROUP BY quizz_rel_questions.question_order, attempts.question_id";
			//GROUP BY questions.position, attempts.question_id";

$result =Database::query($query);	
$questionList = array();
$exerciseResult = array();

while ($row = Database::fetch_array($result)) {
	$questionList[] = $row['question_id'];
	$exerciseResult[$row['question_id']] = $row['answer'];
}

//Fixing #2073 Fixing order of questions
if (!empty($track_exercise_info['data_tracking']) && !empty($track_exercise_info['random']) ) {
	$tempquestionList = explode(',',$track_exercise_info['data_tracking']);
	if (is_array($tempquestionList) && count($tempquestionList) == count($questionList)) {
		$questionList = $tempquestionList;			
	}		
}

// for each question
$counter=0;

$total_weighting = 0;
foreach ($questionList as $questionId) {
    $objQuestionTmp     = Question::read($questionId);
    $total_weighting  +=$objQuestionTmp->selectWeighting();        
}

foreach ($questionList as $questionId) {
	$counter++;		
	$choice=$exerciseResult[$questionId];
		// destruction of the Question object
	unset($objQuestionTmp);
	
	// creates a temporary Question object
	$objQuestionTmp 	= Question::read($questionId);
	$questionName		= $objQuestionTmp->selectTitle();
	$questionDescription= $objQuestionTmp->selectDescription();
	$questionWeighting	= $objQuestionTmp->selectWeighting();
	$answerType			= $objQuestionTmp->selectType();
	$quesId 			= $objQuestionTmp->selectId();	
	        	
 	if ($show_results) { 	    
	    echo $objQuestionTmp->return_header($feedback_type);
	}		
	if ($answerType == MULTIPLE_ANSWER || $answerType == MULTIPLE_ANSWER_TRUE_FALSE) {
        $question_result = $objExercise->manage_answer($id, $questionId, $choice,'exercise_show', array(), false, true, $show_results, $objExercise->selectPropagateNeg());            
        $questionScore   = $question_result['score'];
        $totalScore      += $question_result['score'];
	} elseif ($answerType == MULTIPLE_ANSWER_COMBINATION || $answerType ==  MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE) {
		$choice=array();
        $question_result = $objExercise->manage_answer($id, $questionId, $choice,'exercise_show', array(), false, true, $show_results, $objExercise->selectPropagateNeg());                       
        $questionScore   = $question_result['score'];
        $totalScore     += $question_result['score'];	
	} elseif ($answerType == UNIQUE_ANSWER || $answerType ==  UNIQUE_ANSWER_NO_OPTION) {	
        $question_result = $objExercise->manage_answer($id, $questionId, $choice,'exercise_show', array(), false, true, $show_results, $objExercise->selectPropagateNeg());
        $questionScore   = $question_result['score'];
        $totalScore     += $question_result['score'];  
		echo '</table>';
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
        		//<p style="text-align:center">
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
        			
        			// by default we assume that the answer is ok but if the final answer after calculating the area in hotspot delineation =0 then update  
        			if ($final_answer==0) {
        				//update_exercise_attempt(0, 0,$questionId,$exeId, 0 ); //we do not update the user_id 
        				//update_event_exercice($exeId, )
        			}
        			
        		} else {
        			echo '<p>'.$comment.'</p>';
        		}
        		//echo '<a onclick="self.parent.tb_remove();" href="#" style="float:right;">'.get_lang('Close').'</a>';
        
        		
        		//showing the score	
         		$queryfree = "select marks from ".$TBL_TRACK_ATTEMPT." where exe_id = '".Database::escape_string($id)."' and question_id= '".Database::escape_string($questionId)."'";
        		$resfree = api_sql_query($queryfree, __FILE__, __LINE__);
        		$questionScore= mysql_result($resfree,0,"marks");
        		$totalScore+=$questionScore;        		
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

		echo '<table width="100%" border="0" cellspacing="3" cellpadding="0">';
		
		if ($is_allowedToEdit) {
			echo '<tr><td>';
			$name = "fckdiv".$questionId;
			$marksname = "marksName".$questionId;
			?>
			<br />
			<a href="javascript://" onclick="showfck('<?php echo $name; ?>','<?php echo $marksname; ?>');">
			<?php
			if ($answerType == FREE_ANSWER) {
				echo get_lang('EditCommentsAndMarks');
			} else {
				if ($action=='edit') {
					echo '<img src="../img/edit.gif"/>'.get_lang('EditIndividualComment');
				} else {
					echo get_lang('AddComments');
				}
			}
			echo '</a><br /><div id="feedback_'.$name.'" style="width:100%">';
			$comnt = trim(get_comments($id,$questionId));
			if (empty($comnt)) {
				echo '<br />';
			} else {
				echo '<div id="question_feedback">'.$comnt.'</div>';
			}
			echo '</div><div id="'.$name.'" style="display:none">';
			$arrid[] = $questionId;

			$feedback_form = new FormValidator('frmcomments'.$questionId,'post','');
			$feedback_form->addElement('html','<br>');
			$renderer =& $feedback_form->defaultRenderer();
			$renderer->setFormTemplate('<form{attributes}><div align="left">{content}</div></form>');
			$renderer->setElementTemplate('<div align="left">{element}</div>');
			$comnt = get_comments($id,$questionId);
			${user.$questionId}['comments_'.$questionId] = $comnt;
			$feedback_form->addElement('html_editor', 'comments_'.$questionId, null, null, array('ToolbarSet' => 'TestAnswerFeedback', 'Width' => '100%', 'Height' => '120'));
			$feedback_form->addElement('html','<br>');
			//$feedback_form->addElement('submit','submitQuestion',get_lang('Ok'));
			$feedback_form->setDefaults(${user.$questionId});
			$feedback_form->display();
			echo '</div>';
		} else {
			$comnt = get_comments($id,$questionId);
			echo '<tr><td><br />';
			if (!empty($comnt)) {
				echo '<b>'.get_lang('Feedback').'</b>';
				echo '<div id="question_feedback">'.$comnt.'</div>';
			}
			echo '</td><td>';
		}
		
		if ($is_allowedToEdit) {
			if ($answerType == FREE_ANSWER) {
				$marksname = "marksName".$questionId;
				?>
				<div id="<?php echo $marksname; ?>" style="display:none">
				<form name="marksform_<?php echo $questionId; ?>" method="post" action="">
			    <?php
				$arrmarks[] = $questionId;
				echo get_lang("AssignMarks");
				echo "&nbsp;<select name='marks' id='marks'>";
				for ($i=0;$i<=$questionWeighting;$i++) {
					echo '<option '.(($i==$questionScore)?"selected='selected'":'').'>'.$i.'</option>';
				}
				echo '</select>';
				echo '</form><br/ ></div>';
				if ($questionScore==-1 ) {
					$questionScore=0;
				  	echo '<br />'.get_lang('notCorrectedYet');
				}
			} else {
				$arrmarks[] = $questionId;
				echo '<div id="'.$marksname.'" style="display:none"><form name="marksform_'.$questionId.'" method="post" action="">
					  <select name="marks" id="marks" style="display:none;"><option>'.$questionScore.'</option></select></form><br/ ></div>';
			}
		} else {
			if ($questionScore==-1) {
				 $questionScore=0;
			}
		}		
    	echo '</td>
		</tr>
		</table>';		
	}
	
    
	/*
	Do not convert question results
	$my_total_score  = convert_score($questionScore, $total_weighting);
	$my_total_weight = convert_score($questionWeighting, $total_weighting);*/
	
	$my_total_score  = $questionScore;
	$my_total_weight = $questionWeighting;   
	
    if ($objExercise->selectPropagateNeg() == 0 && $my_total_score < 0) {
        $my_total_score = 0;
    }  
    if ($show_results) {
	    echo '<div id="question_score">';
		//echo get_lang('Score')." : $my_total_score / $my_total_weight";
		echo get_lang('Score')." : ".show_score($my_total_score, $my_total_weight, false, false);    
		
        //echo get_lang('Score')." : ".show_score($my_total_score, $total_weighting, false);
		echo '</div>';
    }
	unset($objAnswerTmp);
	$i++;

	$totalWeighting+=$questionWeighting;
    
} // end of large foreach on questions

//Total score
if ($origin!='learnpath' || ($origin == 'learnpath' && isset($_GET['fb_type']))) {
	if ($show_results || $show_only_total_score ) {        
		echo '<div id="question_score">'.get_lang('YourTotalScore').": ";
        $my_total_score_temp = $totalScore; 
	    if ($objExercise->selectPropagateNeg() == 0 && $my_total_score_temp < 0) {
	        $my_total_score_temp = 0;
	    }          
        echo show_score($my_total_score_temp, $totalWeighting, false);	
		echo '</div>';
	}
}

if (is_array($arrid) && is_array($arrmarks)) {
	$strids = implode(",",$arrid);
	$marksid = implode(",",$arrmarks);
}

if ($is_allowedToEdit) {
	if (in_array($origin, array('tracking_course','user_course','correct_exercise_in_lp'))) {        
		echo ' <form name="myform" id="myform" action="exercice.php?show=result&exerciseId='.$exercise_id.'&filter=2&comments=update&exeid='.$id.'&origin='.$origin.'&details=true&course='.Security::remove_XSS($_GET['cidReq']).$fromlink.'" method="post">';
		//echo ' <input type = "hidden" name="totalWeighting" value="'.$totalWeighting.'">';
		echo '<input type = "hidden" name="lp_item_id"       value="'.$lp_id.'">';
		echo '<input type = "hidden" name="lp_item_view_id"  value="'.$lp_item_view_id.'">';
		echo '<input type = "hidden" name="student_id"       value="'.$student_id.'">';
		echo '<input type = "hidden" name="total_score"      value="'.$totalScore.'"> ';
		echo '<input type = "hidden" name="my_exe_exo_id"    value="'.$exercise_id.'"> ';					
	} else {
		echo ' <form name="myform" id="myform" action="exercice.php?show=result&action=qualify&exerciseId='.$exercise_id.'&filter=2&comments=update&exeid='.$id.'" method="post">';
	}
	if ($origin!='learnpath' && $origin!='student_progress') {
		?>
		<button type="submit" class="save" value="<?php echo get_lang('Ok'); ?>" onclick="getFCK('<?php echo $strids; ?>','<?php echo $marksid; ?>');"><?php echo get_lang('FinishTest'); ?></button>
		</form>
		<?php
	}
}

//Came from lpstats in a lp
if ($origin =='student_progress') {?>
	<button type="button" class="back" onclick="window.back();" value="<?php echo get_lang('Back'); ?>" ><?php echo get_lang('Back');?></button>
<?php
} else if($origin=='myprogress') {
?>
	<button type="button" class="save" onclick="top.location.href='../auth/my_progress.php?course=<?php echo api_get_course_id()?>'" value="<?php echo get_lang('Finish'); ?>" ><?php echo get_lang('Finish');?></button>
<?php
}
   
if ($origin != 'learnpath') {
	//we are not in learnpath tool
	Display::display_footer();
} else {
	if (!isset($_GET['fb_type'])) {
		$lp_mode =  $_SESSION['lp_mode'];
		$url = '../newscorm/lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$learnpath_id.'&lp_item_id='.$learnpath_item_id.'&exeId='.$exeId.'&fb_type='.$feedback_type;
		$href = ($lp_mode == 'fullscreen')?' window.opener.location.href="'.$url.'" ':' top.location.href="'.$url.'" ';
		echo '<script language="javascript" type="text/javascript">'.$href.'</script>'."\n";

		//record the results in the learning path, using the SCORM interface (API)
		echo '<script language="javascript" type="text/javascript">window.parent.API.void_save_asset('.$totalScore.','.$totalWeighting.');</script>'."\n";
		echo '</body></html>';
	} else {
		Display::display_normal_message(get_lang('ExerciseFinished').' '.get_lang('ToContinueUseMenu'));
        echo '<br />';
	}
}

if (!$is_allowedToEdit) {	
    $objExercise->send_notification($arrques, $arrans, $origin);	
}

//destroying the session
api_session_unregister('questionList');
unset ($questionList);

api_session_unregister('exerciseResult');
unset ($exerciseResult);

<?php
/* For licensing terms, see /license.txt */

/**
*	Question Pool
* 	This script allows administrators to manage questions and add them into their exercises.
* 	One question can be in several exercises
*	@package chamilo.exercise
* 	@author Olivier Brouckaert
*   @author Julio Montoya adding support to query all questions from all session, courses, exercises  
*/
/**
 * Code
 */
// name of the language file that needs to be included
$language_file='exercice';

require_once 'exercise.class.php';
require_once 'exercise.lib.php';
require_once 'question.class.php';
require_once 'answer.class.php';
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'sessionmanager.lib.php';

$this_section=SECTION_COURSES;

$is_allowedToEdit=api_is_allowed_to_edit(null,true);

if (empty($delete)) {
    $delete = intval($_GET['delete']);
}
if ( empty ( $recup ) ) {
    $recup = intval($_GET['recup']);
}
if ( empty ( $fromExercise ) ) {
    $fromExercise = intval($_REQUEST['fromExercise']);
}
if(isset($_GET['exerciseId'])){
	$exerciseId = intval($_GET['exerciseId']);
}
if(isset($_GET['exerciseLevel'])){
	$exerciseLevel = intval($_REQUEST['exerciseLevel']);
}
if(isset($_GET['answerType'])){
	$answerType = intval($_REQUEST['answerType']);
}
$page = 0;
if(!empty($_GET['page'])){
	$page = intval($_GET['page']);
}
$copy_question = 0;
if(!empty($_GET['copy_question'])){
	$copy_question = intval($_GET['copy_question']);
}

//only that type of question
if(!empty($_GET['type'])){
	$type = intval($_GET['type']);
}

$session_id      = intval($_GET['session_id']);
$selected_course = intval($_GET['selected_course']);


// maximum number of questions on a same page
$limitQuestPage = 20;

// document path
$documentPath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
// picture path
$picturePath = $documentPath.'/images';

if(!($objExcercise instanceOf Exercise) && !empty($fromExercise)) {
    $objExercise = new Exercise();    
    $objExercise->read($fromExercise);
}

$nameTools = get_lang('QuestionPool');
$interbreadcrumb[] = array("url" => "exercice.php","name" => get_lang('Exercices'));
$interbreadcrumb[] = array("url" => "admin.php?exerciseId=".$objExercise->id, "name" => $objExercise->name);
    
if ($is_allowedToEdit) {
	
	//Duplicating a Question
    
	if (!isset($_POST['recup']) && $copy_question != 0 && isset($fromExercise)) {
        $origin_course_id   = intval($_GET['course_id']);
        $origin_course_info = api_get_course_info_by_id($origin_course_id);
        $current_course     = api_get_course_info();     
        $old_question_id    = $copy_question;       
        
        //Reading the source question
		$old_question_obj = Question::read($old_question_id, $origin_course_id);
		$old_question_obj->updateTitle($old_question_obj->selectTitle().' - '.get_lang('Copy'));     
        
        //Duplicating the source question, in the current course
		$new_id = $old_question_obj->duplicate($current_course);
		
        //Reading new question
		$new_question_obj = Question::read($new_id);			
		$new_question_obj->addToList($fromExercise);
        			
        //Reading Answers obj of the current course 
		$new_answer_obj = new Answer($old_question_id, $origin_course_id);
		$new_answer_obj->read();
        
        //Duplicating the Answers in the current course
		$new_answer_obj->duplicate($new_id, $current_course);		
		
		// destruction of the Question object
		unset($new_question_obj);
		unset($old_question_obj);

        if (!$objExcercise instanceOf Exercise) {
        	$objExercise = new Exercise();
            $objExercise->read($fromExercise);
        }
		api_session_register('objExercise');
		header("Location: admin.php?".api_get_cidreq()."&exerciseId=$fromExercise");
		exit();	
	}
	
	// deletes a question from the data base and all exercises
	if ($delete) {
		// construction of the Question object
		// if the question exists
		if($objQuestionTmp = Question::read($delete))
		{
			// deletes the question from all exercises
			$objQuestionTmp->delete();
		}

		// destruction of the Question object
		unset($objQuestionTmp);
	} elseif($recup && $fromExercise) {
		/*
		// gets an existing question and copies it into a new exercise
		$objQuestionTmp = Question :: read($recup);
		
		// if the question exists
		if($objQuestionTmp = Question :: read($recup)) {
			// adds the exercise ID represented by $fromExercise into the list of exercises for the current question
			$objQuestionTmp->addToList($fromExercise);
		}
		// destruction of the Question object
		unset($objQuestionTmp);

        if (!$objExcercise instanceOf Exercise) {
        	$objExercise = new Exercise();
            $objExercise->read($fromExercise);
        }
		// adds the question ID represented by $recup into the list of questions for the current exercise
		$objExercise->addToList($recup);
		api_session_register('objExercise');
		header("Location: admin.php?".api_get_cidreq()."&exerciseId=$fromExercise");
		exit();*/
	} else if( isset($_POST['recup']) && is_array($_POST['recup']) && $fromExercise) {
		$list_recup 		= $_POST['recup'];
		$origin_course_id   = intval($_REQUEST['course_id']);
		$origin_course_info = api_get_course_info_by_id($origin_course_id);
		$current_course     = api_get_course_info();
		
		
		foreach ($list_recup as $recup) {
			/*
			$recup = intval($recup);
			// if the question exists
			if($objQuestionTmp = Question :: read($recup)) {
				// adds the exercise ID represented by $fromExercise into the list of exercises for the current question
				$objQuestionTmp->addToList($fromExercise);
			}
			// destruction of the Question object
			unset($objQuestionTmp);
	        if(!$objExcercise instanceOf Exercise) {
	        	$objExercise = new Exercise();
	            $objExercise->read($fromExercise);
	        }
			// adds the question ID represented by $recup into the list of questions for the current exercise
			$objExercise->addToList($recup);
			*/
			
			
			
			
			
			$old_question_id    = $recup;
			
			//Reading the source question
			$old_question_obj = Question::read($old_question_id, $origin_course_id);
			$old_question_obj->updateTitle($old_question_obj->selectTitle().' - '.get_lang('Copy'));
			
			//Duplicating the source question, in the current course
			$new_id = $old_question_obj->duplicate($current_course);
			
			//Reading new question
			$new_question_obj = Question::read($new_id);
			$new_question_obj->addToList($fromExercise);
			 
			//Reading Answers obj of the current course
			$new_answer_obj = new Answer($old_question_id, $origin_course_id);
			$new_answer_obj->read();
			
			//Duplicating the Answers in the current course
			$new_answer_obj->duplicate($new_id, $current_course);
			
			// destruction of the Question object
			unset($new_question_obj);
			unset($old_question_obj);
			
			if (!$objExcercise instanceOf Exercise) {
				$objExercise = new Exercise();
				$objExercise->read($fromExercise);
			}
		}
		api_session_register('objExercise');
		header("Location: admin.php?".api_get_cidreq()."&exerciseId=$fromExercise");
		exit();
	}
}

if (isset($_SESSION['gradebook'])){
	$gradebook=	$_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook=='view') {
	$interbreadcrumb[]= array ('url' => '../gradebook/'.Security::remove_XSS($_SESSION['gradebook_dest']),'name' => get_lang('ToolGradebook'));
}

// if admin of course
if (!$is_allowedToEdit) {
    api_not_allowed(true);
}

$htmlHeadXtra[] = ' <script type="text/javascript">                 
	function submit_form(obj) {            
		document.question_pool.submit();
	}		
	</script>';
Display::display_header($nameTools,'Exercise');

// Menu 
echo '<div class="actions">';
if (isset($type)) {
	$url = api_get_self().'?type=1';
} else {
	$url = api_get_self();
}
echo '<a href="admin.php?'.api_get_cidreq().'&exerciseId='.$fromExercise.'">'.Display::return_icon('back.png', get_lang('GoBackToQuestionList'),'','32').'</a>';
echo '</div>';

//Title
echo '<h2>'.$nameTools.'</h2>';

//Form
echo '<form name="question_pool" method="GET" action="'.$url.'">';	 
if (isset($type)) {
	echo '<input type="hidden" name="type" value="1">';
}    
echo '<input type="hidden" name="fromExercise" value="'.$fromExercise.'">';

//Session list  
$session_list = SessionManager::get_sessions_by_coach(api_get_user_id());

$session_select_list = array();
foreach($session_list as $item) {
    $session_select_list[$item['id']] = $item['name'];
}
$select_session_html =  Display::select('session_id', $session_select_list, $session_id, array('onchange'=>'submit_form(this);'));
echo Display::form_row(get_lang('Session'), $select_session_html);

//Course list
if (!empty($session_id) && $session_id != '-1') {
    $course_list = SessionManager::get_course_list_by_session_id($session_id);
} else {        
    $course_list = CourseManager::get_course_list_of_user_as_course_admin(api_get_user_id());        
}    

$course_select_list = array();
foreach ($course_list as $item) {
	$course_select_list[$item['id']] = $item['title'];
}    

$select_course_html =  Display::select('selected_course', $course_select_list, $selected_course, array('onchange'=>'submit_form(this);'));
echo Display::form_row(get_lang('Course'), $select_course_html);    

if (empty($selected_course) || $selected_course == '-1') {
    $course_info = api_get_course_info();
    $db_name = $course_info['db_name'];
} else {        
	$course_info = CourseManager::get_course_information_by_id($selected_course);                
    $db_name = $course_info['db_name'];
}

//Redefining table calls
$TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION,   $db_name);
$TBL_EXERCICES         = Database::get_course_table(TABLE_QUIZ_TEST,            $db_name);
$TBL_QUESTIONS         = Database::get_course_table(TABLE_QUIZ_QUESTION,        $db_name);
$TBL_REPONSES          = Database::get_course_table(TABLE_QUIZ_ANSWER,          $db_name);

$exercise_list         = get_all_exercises($course_info, $session_id);

//Exercise List
$my_exercise_list = array();
$my_exercise_list['0']  = get_lang('AllExercises');
$my_exercise_list['-1'] = get_lang('OrphanQuestions'); 
    
if (is_array($exercise_list)) {
    foreach($exercise_list as $row) {        
        if ($row['id'] !=  $fromExercise) {       
            $my_exercise_list[$row['id']] = $row['title'];
        }
    }
}    
$select_exercise_html =  Display::select('exerciseId', $my_exercise_list, $exerciseId, array('onchange'=>'submit_form(this);'), false);
echo Display::form_row(get_lang('Exercise'), $select_exercise_html);

//Difficulty list (only from 0 to 5)                
$select_difficulty_html = Display::select('exerciseLevel', array(0=>0, 1=>1, 2=>2, 3=>3, 4=>4, 5=>5), $exerciseLevel, array('onchange'=>'submit_form(this);'));
echo Display::form_row(get_lang('Difficulty'), $select_difficulty_html);

//Answer type

$question_list = Question::get_types_information();
$new_question_list = array();
$objExercise->feedbacktype;
foreach ($question_list as $key=>$item) {
    if ($objExercise->feedbacktype == EXERCISE_FEEDBACK_TYPE_DIRECT) {
        if (!in_array($key, array(HOT_SPOT_DELINEATION, UNIQUE_ANSWER))) {
            continue;
        }
        $new_question_list[$key] = get_lang($item[1]);
    } else {
        if ($key == HOT_SPOT_DELINEATION) {
            continue;
        }
        $new_question_list[$key] = get_lang($item[1]);
    }
}

//Answer type list
$select_answer_html = Display::select('answerType', $new_question_list, $answerType, array('onchange'=>'submit_form(this);'));
echo Display::form_row(get_lang('AnswerType'), $select_answer_html);
$button = '<button class="save" type="submit" name="name" value="'.get_lang('Filter').'">'.get_lang('Filter').'</button>'; 
echo Display::form_row('', $button);
?>
</form>
<div class="clear"></div>
<form method="post" action="<?php echo $url.'?'.api_get_cidreq().'&fromExercise='.$fromExercise; ?>" >
<?php
echo '<input type="hidden" name="course_id" value="'.$selected_course.'">';
echo '<table class="data_table">';
$from = $page * $limitQuestPage;

// if we have selected an exercise in the list-box 'Filter'
if ($exerciseId > 0) {
	//$sql="SELECT id,question,type FROM $TBL_EXERCICE_QUESTION,$TBL_QUESTIONS WHERE question_id=id AND exercice_id='".Database::escape_string($exerciseId)."' ORDER BY question_order LIMIT $from, ".($limitQuestPage + 1);
	$where = '';
	if (isset($type) && $type==1) {
		$where = ' type = 1 AND ';
	}

	if (isset($exerciseLevel) && $exerciseLevel != -1) {
		$where .= ' level='.$exerciseLevel.' AND ';
	}

	if (isset($answerType) && $answerType != -1) {
		$where .= ' type='.$answerType.' AND ';
	}

	$sql="SELECT id,question,type,level
			FROM $TBL_EXERCICE_QUESTION,$TBL_QUESTIONS
		  	WHERE $where question_id=id AND exercice_id='".Database::escape_string($exerciseId)."'
			ORDER BY question_order";
            
    $result=Database::query($sql);
    while($row = Database::fetch_array($result, 'ASSOC')) {
    	$main_question_list[] = $row;
    }    
} elseif($exerciseId == -1) {

	// if we have selected the option 'Orphan questions' in the list-box 'Filter'

	// 1. Old logic: When a test is deleted, the correspondent records in 'quiz' and 'quiz_rel_question' tables are deleted.
	//$sql='SELECT id, question, type, exercice_id FROM '.$TBL_QUESTIONS.' as questions LEFT JOIN '.$TBL_EXERCICE_QUESTION.' as quizz_questions ON questions.id=quizz_questions.question_id WHERE exercice_id IS NULL LIMIT $from, '.($limitQuestPage + 1);

	// 2. New logic: When a test is deleted, the field 'active' takes value -1 (it is in the correspondent record in 'quiz' table).
	//$sql='SELECT questions.id, questions.question, questions.type, quizz_questions.exercice_id FROM '.$TBL_QUESTIONS.
	//	' as questions LEFT JOIN '.$TBL_EXERCICE_QUESTION.' as quizz_questions ON questions.id=quizz_questions.question_id LEFT JOIN '.$TBL_EXERCICES.
	//	' as exercices ON exercice_id=exercices.id WHERE exercices.active = -1 LIMIT $from, '.($limitQuestPage + 1);

	// 3. This is more safe to changes, it is a mix between old and new logic.

	/*$sql='SELECT questions.id, questions.question, questions.type, quizz_questions.exercice_id FROM '.$TBL_QUESTIONS.
		' as questions LEFT JOIN '.$TBL_EXERCICE_QUESTION.' as quizz_questions ON questions.id=quizz_questions.question_id LEFT JOIN '.$TBL_EXERCICES.
		' as exercices ON exercice_id=exercices.id WHERE quizz_questions.exercice_id IS NULL OR exercices.active = -1 LIMIT '.$from.', '.($limitQuestPage + 1);
	*/

	/*	4. Query changed because of the Level feature implemented
	$sql='SELECT id, question, type, exercice_id,level FROM '.$TBL_QUESTIONS.' as questions LEFT JOIN '.$TBL_EXERCICE_QUESTION.' as quizz_questions
		ON questions.id=quizz_questions.question_id AND exercice_id IS NULL '.
		(!is_null($exerciseLevel) && $exerciseLevel >= 0 ? 'WHERE level=\''.$exerciseLevel.'\' ' : '');
	*/
	// 5. this is the combination of the 3 and 4 query because of the level feature implementation

	// we filter the type of question, because in the DirectFeedback we can only add questions with type=1 = UNIQUE_ANSWER
	$type_where= '';
	if (isset($type) && $type==1) {
		$type_where = ' AND questions.type = 1 ';
	}

	$level_where = '';
	if (isset($exerciseLevel) && $exerciseLevel!= -1 ) {
		$level_where = ' level='.$exerciseLevel.' AND ';
	}

	$answer_where = '';
	if (isset($answerType) && $answerType != -1 ) {
		$answer_where = ' questions.type='.$answerType.' AND ';
	}

	$sql='SELECT questions.id, questions.question, questions.type, quizz_questions.exercice_id , level, session_id
			FROM '.$TBL_QUESTIONS.' as questions LEFT JOIN '.$TBL_EXERCICE_QUESTION.' as quizz_questions
			ON questions.id=quizz_questions.question_id LEFT JOIN '.$TBL_EXERCICES.' as exercices
			ON exercice_id=exercices.id
			WHERE '.$answer_where.' '.$level_where.' (quizz_questions.exercice_id IS NULL OR exercices.active = -1 )  '.$type_where.'
			LIMIT '.$from.', '.($limitQuestPage + 1);
    $result = Database::query($sql);
    
    while($row = Database::fetch_array($result, 'ASSOC')) {
        $main_question_list[] = $row;
    }

} else {
	// if we have not selected any option in the list-box 'Filter'

	//$sql="SELECT id,question,type FROM $TBL_QUESTIONS LIMIT $from, ".($limitQuestPage + 1);
	$filter = '';

	if (isset($type) && $type==1){
		$filter  .= ' AND qu.type = 1 ';
	}

	if (isset($exerciseLevel) && $exerciseLevel != -1) {
		$filter .= ' AND level='.$exerciseLevel.' ';
	}

	if (isset($answerType) && $answerType != -1) {
		$filter .= ' AND qu.type='.$answerType.' ';
	}
	
    if ($objExercise->feedbacktype != EXERCISE_FEEDBACK_TYPE_DIRECT) {
        $filter .= ' AND qu.type <> '.HOT_SPOT_DELINEATION.' ';
    }
    
	$new_limit_page = $limitQuestPage + 1;
    if ($session_id != 0) {       

        $main_question_list = array();
        if (!empty($course_list))
        foreach ($course_list as $course_item) {        
            if (!empty($selected_course) && $selected_course != '-1') {
                if ($selected_course != $course_item['id']) {                
                	continue;
                }
            }        
            $exercise_list = get_all_exercises($course_item, $session_id);

            if (!empty($exercise_list)) {        
                foreach ($exercise_list as $exercise) {                    
                    $my_exercise = new Exercise($course_item['id']);
                    $my_exercise->read($exercise['id']);
            
                    if (!empty($my_exercise)) {
                        if (!empty($my_exercise->questionList)) {                            
                            foreach ($my_exercise->questionList as $question_id) {  
                                                              
                            	$question_obj = Question::read($question_id, $course_item['id']);
                                if ($exerciseLevel != '-1')
                                if ($exerciseLevel != $question_obj->level) {
                                	continue;
                                }
                                
                                if ($answerType != '-1')
                                if ($answerType != $question_obj->type) {
                                	continue;
                                }
                                if ($objExercise->feedbacktype != EXERCISE_FEEDBACK_TYPE_DIRECT) {
                                     if ($question_obj->type == HOT_SPOT_DELINEATION)  {
                                         continue;
                                     }
                                }                    
                                $question_row       = array('id'=>$question_obj->id, 'question'=>$question_obj->question, 'type'=>$question_obj->type, 'level'=>$question_obj->level, 'exercise_id'=>$exercise['id']);
                                                            
                                $main_question_list[]    = $question_row;                        
                            }
                        }
                    }                    
                }
            }           	
        }      
    } else {
        //By default
    	$sql="SELECT qu.id, question, qu.type, level, q.session_id FROM $TBL_QUESTIONS as qu, $TBL_EXERCICE_QUESTION as qt, $TBL_EXERCICES as q
              WHERE q.id=qt.exercice_id AND qu.id=qt.question_id AND qt.exercice_id<>".$fromExercise." $filter ORDER BY session_id ASC LIMIT $from, $new_limit_page";
    }
	// forces the value to 0
	$exerciseId=0;
}
$nbrQuestions = count($main_question_list);
echo '<tr>',
  '<td colspan="',($fromExercise?4:4),'">',
	'<table border="0" cellpadding="0" cellspacing="0" width="100%">',
	'<tr><td align="right">';

if(!empty($page)) {	
	echo '<a href="',api_get_self(),'?',api_get_cidreq(),'&exerciseId=',$exerciseId,'&fromExercise=',$fromExercise,'&page=',($page-1),'&session_id='.$session_id.'&selected_course='.$selected_course.'&answerType=',$answerType,'&exerciseLevel='.$exerciseLevel.'">';
	echo Display::return_icon('action_prev.png');
	echo '&nbsp;';
	
} elseif($nbrQuestions > $limitQuestPage) {
	echo Display::return_icon('action_prev_na.png');
	echo '&nbsp;';
}

if($nbrQuestions > $limitQuestPage) {
	echo '<a href="',api_get_self(),'?',api_get_cidreq(),'&exerciseId=',$exerciseId,'&fromExercise=',$fromExercise,'&page=',($page+1),'&session_id='.$session_id.'&selected_course='.$selected_course.'&answerType=',$answerType,'&exerciseLevel='.$exerciseLevel.'">';
	echo Display::return_icon('action_next.png');
	echo '</a>';
	
} elseif($page) {
   	echo Display::return_icon('action_next_na.png');
   echo '&nbsp;';
}
echo '</td>
	</tr>
	</table>
  </td>
</tr>
<tr bgcolor="#e6e6e6">';

if (!empty($fromExercise)) {
	if (api_get_session_id() == 0 ){
    	echo '<th width="4%"> </th>';
	}
    echo '<th>',get_lang('Question'),'</th>',
        '<th>',get_lang('Level'),'</th>',
        '<th>',get_lang('Reuse'),'</th>';
} else {
    echo '<td width="60%" align="center">',get_lang('Question'),'</td>',
        '<td width="20%" align="center">',get_lang('Modify'),'</td>',
        '<td width="16%" align="center">',get_lang('Delete'),'</td>';
}
echo '</tr>';
$i=1;

$session_id  = api_get_session_id();
if (!empty($main_question_list))
foreach ($main_question_list as $row) {
	// if we come from the exercise administration to get a question,
    // don't show the questions already used by that exercise

    // original recipe -
    //if (!$fromExercise || !isset($objExercise) || !($objExercise instanceOf Exercise) || (!$objExercise->isInList($row['id'])))
	if (!$fromExercise || !isset($objExercise) || !($objExercise instanceOf Exercise) || (is_array($objExercise->questionList)) ) {
        echo '<tr ',($i%2==0?'class="row_odd"':'class="row_even"'),'>';
        if (api_get_session_id() == 0 ){
        	echo '<td align="center"> <input type="checkbox" value="'.$row['id'].'" name="recup[]"/></td>';
        }
        echo '  <td><a href="admin.php?',api_get_cidreq(),'&editQuestion=',$row['id'],'&fromExercise='.$fromExercise.'&answerType='.$row['type'].'">',$row['question'],'</a></td>';
        echo '  <td align="center" >';
		if (empty($fromExercise)) {
            echo '<a href="admin.php?'.api_get_cidreq().'&amp;editQuestion=',$row['id'],'"><img src="../img/edit.gif" border="0" alt="',get_lang('Modify'),'"></a>',
                '</td>',
                '<td align="center">',
                '<a href="',api_get_self(),'?',api_get_cidreq(),'&exerciseId=',$exerciseId,'&delete=',$row['id'],'" onclick="javascript:if(!confirm(\'',addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)),'\')) return false;"><img src="../img/delete.gif" border="0" alt="',get_lang('Delete'),'"></a>';
                //'<a href="',api_get_self(),'?',api_get_cidreq(),'&exerciseId=',$exerciseId,'&delete=',$row['id'],'" onclick="javascript:if(!confirm(\'',addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)),'\')) return false;"><img src="../img/delete.gif" border="0" alt="',get_lang('Delete'),'"></a>';
		} else {
			echo $row['level'],'</td>',
				 '<td align="center">';
			echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;copy_question='.$row['id'].'&course_id='.$selected_course.'&fromExercise=',$fromExercise,'">';                
            echo ' '.Display::return_icon('cd.gif', get_lang('ReUseACopyInCurrentTest'));
            echo '</a> ';
			if ($row['session_id'] == $session_id) {
			    if ($selected_course == api_get_course_int_id()) {
				    echo '<a href="',api_get_self(),'?',api_get_cidreq(),'&recup=',$row['id'],'&fromExercise=',$fromExercise,'"><img src="../img/view_more_stats.gif" border="0" title="'.get_lang('InsertALinkToThisQuestionInTheExercise').'" alt="'.get_lang('InsertALinkToThisQuestionInTheExercise').'"></a>';
			    }
			}
		}
        echo '</td>';
        echo '</tr>';

		// skips the last question, that is only used to know if we have or not to create a link "Next page"
		if($i == $limitQuestPage) {
			break;
		}
		$i++;
	}
}

if (!$nbrQuestions) {
    echo '<tr>',
        '<td colspan="',($fromExercise?4:4),'">',get_lang('NoQuestion'),'</td>',
        '</tr>';
}
echo '</table>';

if (api_get_session_id() == 0 ){
	echo '<div style="width:100%; border-top:1px dotted #4171B5;">
		  <button class="save" type="submit">'.get_lang('Reuse').'</button>
	  	</div></form>';
}
Display::display_footer();

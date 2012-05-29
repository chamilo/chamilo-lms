<?php
/* For licensing terms, see /license.txt */

/**
*	Question Pool
* 	This script allows administrators to manage questions and add them into their exercises.
* 	One question can be in several exercises
*	@package chamilo.exercise
* 	@author Olivier Brouckaert
*   @author Julio Montoya adding support to query all questions from all session, courses, exercises  
*   @author Modify by hubert borderiou 2011-10-21 Question's category
*/
/**
 * Code
 */
// name of the language file that needs to be included

use \ChamiloSession as Session;

$language_file = 'exercice';

require_once 'exercise.class.php';
require_once 'question.class.php';
require_once 'answer.class.php';
require_once '../inc/global.inc.php';
require_once 'exercise.lib.php';

$this_section = SECTION_COURSES;

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

if (isset($_GET['courseCategoryId'])) {
	$courseCategoryId = intval($_GET['courseCategoryId']);
}

$exerciseLevel = -1;
if(isset($_REQUEST['exerciseLevel'])){
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

$session_id      			= intval($_GET['session_id']);
$selected_course 			= intval($_GET['selected_course']);
$course_id_changed		= intval($_GET['course_id_changed']);	// save the id of the previous course selected by user to reset menu if we detect that user change course hub 13-10-2011
$exercice_id_changed 	= intval($_GET['exercice_id_changed']); // save the id of the previous exercice selected by user to reset menu if we detect that user change course hub 13-10-2011

// by default when we go to the page for the first time, we select the current course
if (!isset($_GET['selected_course']) && !isset($_GET['exerciseId'])) {
	$selected_course = api_get_course_int_id();
}

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
    
$displayMessage = "";	// messag to be displayed if actions succesfull
if ($is_allowedToEdit) {
	//Duplicating a Question
	if (!isset($_POST['recup']) && $copy_question != 0 && isset($fromExercise)) {
        $origin_course_id   = intval($_GET['course_id']);
        $origin_course_info = api_get_course_info_by_id($origin_course_id);
        $current_course     = api_get_course_info();
        $old_question_id    = $copy_question;       
        //Reading the source question
		$old_question_obj = Question::read($old_question_id, $origin_course_id);
		if ($old_question_obj) {
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
			Session::write('objExercise',$objExercise);
		}
		$displayMessage = get_lang('ItemAdded');
//		header("Location: admin.php?".api_get_cidreq()."&exerciseId=$fromExercise");
//		exit();	
	}
	// deletes a question from the database and all exercises
	if ($delete) {
		// construction of the Question object
		// if the question exists
		if($objQuestionTmp = Question::read($delete)) {
			// deletes the question from all exercises
			$objQuestionTmp->delete();
		}
		// destruction of the Question object
		unset($objQuestionTmp);
	} elseif($recup && $fromExercise) {
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
		Session::write('objExercise',$objExercise);

//		header("Location: admin.php?".api_get_cidreq()."&exerciseId=$fromExercise");
//		exit();
	} 
	else if( isset($_POST['recup']) && is_array($_POST['recup']) && $fromExercise) {
		$list_recup 		= $_POST['recup'];
		
		foreach ($list_recup as $course_id => $question_data) {
			
			$origin_course_id   = intval($course_id);
			$origin_course_info = api_get_course_info_by_id($origin_course_id);
			$current_course     = api_get_course_info();
			
			foreach ($question_data as $old_question_id) {			
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
				
				//Reading the source question
				$old_question_obj = Question::read($old_question_id, $origin_course_id);
				if ($old_question_obj) {
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
			}
		}
		Session::write('objExercise',$objExercise);
//		header("Location: admin.php?".api_get_cidreq()."&exerciseId=$fromExercise");
//		exit();
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

$confirmYourChoice = addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset));

$htmlHeadXtra[] = " 
<script type='text/javascript'>          
	function submit_form(obj) {            
		document.question_pool.submit();
	}		
	
	function mark_course_id_changed() { 
		$('#course_id_changed').val('1');
	}
	
	function mark_exercice_id_changed() { 
		$('#exercice_id_changed').val('1');
	}
	
	function confirm_your_choice() {
		return confirm('$confirmYourChoice');
	}
</script>
";

Display::display_header($nameTools,'Exercise');

// Menu 
echo '<div class="actions">';
	if (isset($type)) {
		$url = api_get_self().'?type=1';
	} else {
		$url = api_get_self();
	}
	if (isset($fromExercise) && $fromExercise > 0) {
		echo '<a href="admin.php?'.api_get_cidreq().'&exerciseId='.$fromExercise.'">'.Display::return_icon('back.png', get_lang('GoBackToQuestionList'),'',ICON_SIZE_MEDIUM).'</a>';
		$titleAdd = get_lang('AddQuestionToTest');
	}
	else {
		echo '<a href="exercice.php?'.api_get_cidReq().'">'.Display::return_icon('back.png', get_lang('BackToExercisesList'),'',ICON_SIZE_MEDIUM).'</a>';
		echo "<a href='admin.php?exerciseId=0'>".Display::return_icon('add_question.gif', get_lang('NewQu'), '', 32)."</a>";
		$titleAdd = get_lang('ManageAllQuestions');
	}
echo '</div>';

if ($displayMessage != "") {
	Display::display_confirmation_message($displayMessage);
	$displayMessage = "";
}

//Title
echo '<h2>'.$nameTools.' - '.$titleAdd.'</h2>';

//Form
echo '<form name="question_pool" method="GET" action="'.$url.'">';	 
if (isset($type)) {
	echo '<input type="hidden" name="type" value="1">';
}    
echo '<input type="hidden" name="fromExercise" value="'.$fromExercise.'">';

// Session list, if sessions are used.
$session_list = SessionManager::get_sessions_by_coach(api_get_user_id());
$tabAttrParam = array('class'=>'chzn-select', 'onchange'=>'submit_form(this)');	// when sessions are used
$labelFormRow = get_lang('Session');
if (api_get_setting('use_session_mode') == 'false') {
	$tabAttrParam = array('style'=>'visibility:hidden', 'onchange'=>'submit_form(this)');
	$labelFormRow = "";
}
$session_select_list = array();
foreach($session_list as $item) {
	$session_select_list[$item['id']] = $item['name'];
}
$select_session_html =  Display::select('session_id', $session_select_list, $session_id, $tabAttrParam);
echo Display::form_row($labelFormRow, $select_session_html);	// hub 13-10-2011

// Course list, get course list of session, or for course where user is admin
if (!empty($session_id) && $session_id != '-1') {
	$course_list = SessionManager::get_course_list_by_session_id($session_id);
} else {        
	$course_list = CourseManager::get_course_list_of_user_as_course_admin(api_get_user_id());        
}    
$course_select_list = array();
foreach ($course_list as $item) {
	$course_select_list[$item['id']] = "";
	if ($item['id'] == api_get_course_int_id()) {
		$course_select_list[$item['id']] = ">&nbsp;&nbsp;&nbsp;&nbsp;";
	}
	$course_select_list[$item['id']] .= $item['title'];
}

$select_course_html =  Display::select('selected_course', $course_select_list, $selected_course, array('class'=>'chzn-select','onchange'=>'mark_course_id_changed(); submit_form(this);'));
echo Display::form_row(get_lang('Course'), $select_course_html);    

if (empty($selected_course) || $selected_course == '-1') {
    $course_info = api_get_course_info();
    reset_menu_exo_lvl_type();    // no course selected, reset menu test / difficult� / type de reponse // hub 13-10-2011
} 
else {   
	$course_info = CourseManager::get_course_information_by_id($selected_course);                
}
// If course has changed, reset the menu default
if ($course_id_changed) {
	reset_menu_exo_lvl_type();
}
$course_id = $course_info['real_id'];
//Redefining table calls
$TBL_EXERCICE_QUESTION      = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_EXERCICES              = Database::get_course_table(TABLE_QUIZ_TEST);
$TBL_QUESTIONS              = Database::get_course_table(TABLE_QUIZ_QUESTION);
$TBL_REPONSES               = Database::get_course_table(TABLE_QUIZ_ANSWER);
$TBL_CATEGORY               = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);		// hub 13-10-2011
$TBL_COURSE_REL_CATEGORY	= Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);  // hub 13-10-2011

// Get course categories for the selected course

// get category list for the course $selected_course
$tabCatList = Testcategory::getCategoriesIdAndName($selected_course);
$selectCourseCateogry = Display::select('courseCategoryId', $tabCatList, $courseCategoryId, array('class'=>'chzn-select','onchange'=>'submit_form(this);'), false);
echo Display::form_row(get_lang("QuestionCategory"), $selectCourseCateogry);

// Get exercice list for this course

$exercise_list         = get_all_exercises_for_course_id($course_info, $session_id, $selected_course);
//Exercise List
$my_exercise_list = array();
$my_exercise_list['0']  = get_lang('AllExercises');
$my_exercise_list['-1'] = get_lang('OrphanQuestions'); 
if (is_array($exercise_list)) {
  foreach($exercise_list as $row) {       
		$my_exercise_list[$row['id']] = "";
    if ($row['id'] ==  $fromExercise && $selected_course == api_get_course_int_id()) {       
    	$my_exercise_list[$row['id']] = ">&nbsp;&nbsp;&nbsp;&nbsp;";	// hub 13-10-2011
    }    
    $my_exercise_list[$row['id']] .= $row['title'];
  }
}    

if ($exercice_id_changed == 1) {
	reset_menu_lvl_type();
}
$select_exercise_html =  Display::select('exerciseId', $my_exercise_list, $exerciseId, array('class'=>'chzn-select','onchange'=>'mark_exercice_id_changed(); submit_form(this);'), false);
echo Display::form_row(get_lang('Exercise'), $select_exercise_html);

// Difficulty list (only from 0 to 5)                
 
$select_difficulty_html = Display::select('exerciseLevel', array(-1 => get_lang('All'), 0=>0, 1=>1, 2=>2, 3=>3, 4=>4, 5=>5), $exerciseLevel, array('class'=>'chzn-select', 'onchange'=>'submit_form(this);'), false);
echo Display::form_row(get_lang('Difficulty'), $select_difficulty_html);

 
// Answer type
 
$question_list = Question::get_question_type_list();

$new_question_list = array();
$new_question_list['-1']  = get_lang('All');
$objExercise = new Exercise();
$objExercise->read($fromExercise);
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
$select_answer_html = Display::select('answerType', $new_question_list, $answerType, array('class'=>'chzn-select','onchange'=>'submit_form(this);'), false);
echo Display::form_row(get_lang('AnswerType'), $select_answer_html);
$button = '<button class="save" type="submit" name="name" value="'.get_lang('Filter').'">'.get_lang('Filter').'</button>'; 
echo Display::form_row('', $button);
echo "<input type='hidden' id='course_id_changed' name='course_id_changed' value='0' />"; 
echo "<input type='hidden' id='exercice_id_changed' name='exercice_id_changed' value='0' />";
?>
</form>
<div class="clear"></div>
<form method="post" action="<?php echo $url.'?'.api_get_cidreq().'&fromExercise='.$fromExercise; ?>" >
<?php
echo '<input type="hidden" name="course_id" value="'.$selected_course.'">';


// if we have selected an exercise in the list-box 'Filter'
 
if ($exerciseId > 0) {
	$where = '';
	$from = '';
	if (isset($courseCategoryId) && $courseCategoryId > 0) {
		$from = ", $TBL_COURSE_REL_CATEGORY crc ";
		$where .= " AND crc.c_id=$selected_course AND crc.question_id=qu.id AND crc.category_id=$courseCategoryId";
	}
	if (isset($exerciseLevel) && $exerciseLevel != -1) {
		$where .= ' AND level='.$exerciseLevel;
	}
	if (isset($answerType) && $answerType > 0) {
		$where .= ' AND type='.$answerType;
	}
	$sql = "SELECT DISTINCT 
	            id,question,
	            type,
	            level 
	        FROM 
	            $TBL_EXERCICE_QUESTION qt,
	            $TBL_QUESTIONS qu 
	            $from 
	        WHERE 
	            qt.question_id=qu.id 
	            AND qt.exercice_id=$exerciseId 
	            AND qt.c_id=$selected_course 
	            AND qu.c_id=$selected_course 
	            $where 
	        ORDER BY 
	            question_order";
    $result=Database::query($sql);
    while($row = Database::fetch_array($result, 'ASSOC')) {
        $main_question_list[] = $row;
    }    
} elseif ($exerciseId == -1) {
	// 
	// if we have selected the option 'Orphan questions' in the list-box 'Filter'
	// 
	$level_where = '';
	$from = '';
	if (isset($courseCategoryId) && $courseCategoryId > 0) {
		$from = ", $TBL_COURSE_REL_CATEGORY crc ";
		$level_where .= " AND crc.c_id=$selected_course AND crc.question_id=qu.id AND crc.category_id=$courseCategoryId";
	}	
	if (isset($exerciseLevel) && $exerciseLevel!= -1 ) {
		$level_where = ' AND level='.$exerciseLevel;
	}
	$answer_where = '';
	if (isset($answerType) && $answerType >0 -1 ) {
		$answer_where = ' AND type='.$answerType;
	}
	$sql = "SELECT DISTINCT * FROM $TBL_QUESTIONS qu $from WHERE qu.c_id=$selected_course AND qu.id NOT IN (SELECT question_id FROM $TBL_EXERCICE_QUESTION WHERE c_id=$selected_course ) $level_where $answer_where";
    $result = Database::query($sql);
    while($row = Database::fetch_array($result, 'ASSOC')) {
        $main_question_list[] = $row;
    }
} 
else {
	// 
	// All tests for selected course 
	// 
	// if we have not selected any option in the list-box 'Filter'
	$filter = '';
	$from = '';
	if (isset($courseCategoryId) && $courseCategoryId > 0) {
		$from = ", $TBL_COURSE_REL_CATEGORY crc ";
		$filter .= " AND crc.c_id=$selected_course AND crc.question_id=qu.id AND crc.category_id=$courseCategoryId";
	}		
	if (isset($exerciseLevel) && $exerciseLevel != -1) {
		$filter .= ' AND level='.$exerciseLevel.' ';
	}
	if (isset($answerType) && $answerType > 0) {
		$filter .= ' AND qu.type='.$answerType.' ';
	}
//	// why these lines ?
//  if ($objExercise->feedbacktype != EXERCISE_FEEDBACK_TYPE_DIRECT) {
//      $filter .= ' AND qu.type <> '.HOT_SPOT_DELINEATION.' ';
//  }
//  // fwhy
  // 
  // if in session
  // 
  if (!empty($session_id) && $session_id != '-1') {
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
                if ($answerType > 0)
                if ($answerType != $question_obj->type) {
                	continue;
                }
                
                if ($courseCategoryId > 0 && Testcategory::getCategoryForQuestion($question_obj->id, $selected_course)) {
                	continue;
                }
                if ($objExercise->feedbacktype != EXERCISE_FEEDBACK_TYPE_DIRECT) {
                   if ($question_obj->type == HOT_SPOT_DELINEATION)  {
                     continue;
                   }
                }                    
                $question_row = array(	'id'			=> $question_obj->id, 
                						'question'		=> $question_obj->question, 
                						'type'			=> $question_obj->type, 
                						'level'			=> $question_obj->level, 
                						'exercise_id'	=> $exercise['id'],
                						'course_id'		=> $course_item['id'],
                );
                $main_question_list[]    = $question_row;                        
              }
            }
          }                    
        }
      }           	
    }      
  } 
  else {
  	// 
    // All tests for the course selected, not in session
    // 
  	$sql = "SELECT DISTINCT qu.id, question, qu.type, level, q.session_id FROM $TBL_QUESTIONS as qu, $TBL_EXERCICE_QUESTION as qt, $TBL_EXERCICES as q $from WHERE qu.c_id=$selected_course AND qt.c_id=$selected_course AND q.c_id=$selected_course AND qu.id = qt.question_id AND q.id = qt.exercice_id $filter ORDER BY session_id ASC";
  	$result = Database::query($sql);
  	while($row = Database::fetch_array($result, 'ASSOC')) {
  		$main_question_list[] = $row;
  	}
  }
	// forces the value to 0
	$exerciseId=0;
}

$nbrQuestions = count($main_question_list);

// build the line of the array to display questions
// Actions are different if you launch the question_pool page
// They are different too if you have displayed questions from your course
// Or from another course you are the admin(or session admin)
// from a test or not
/*
+--------------------------------------------+--------------------------------------------+
|   NOT IN A TEST                            |         IN A TEST                          |
+----------------------+---------------------+---------------------+----------------------+
|IN THE COURSE (*)  "x | NOT IN THE COURSE o | IN THE COURSE    +  | NOT IN THE COURSE  o |
+----------------------+---------------------+---------------------+----------------------+
|Edit the question     | Do nothing          | Add question to test|Clone question in test|
|Delete the question   |                     |                     |                      |
|(true delete)         |                     |                     |                      |
+----------------------+---------------------+---------------------+----------------------+
(*) this is the only way to delete or modify orphan questions
*/
// 
if ($fromExercise <= 0) { // NOT IN A TEST - IN THE COURSE
	if ($selected_course == api_get_course_int_id()) {
		$actionLabel = get_lang('Modify');
		$actionIcon1 = "edit";
		$actionIcon2 = "delete";
		$questionTagA = 1;	// we are in the course, question title can be a link to the question edit page
	}
	else { // NOT IN A TEST - NOT IN THE COURSE
		$actionLabel = get_lang('langReuse');
		$actionIcon1 = get_lang('MustBeInATest');
		$actionIcon2 = "";
		$questionTagA = 0;	// we are not in this course, to messy if we link to the question in another course
	}
}
else { // IN A TEST - IN THE COURSE
	if ($selected_course == api_get_course_int_id()) {
		$actionLabel = get_lang('langReuse');
		$actionIcon1 = "add";
		$actionIcon2 = "";
		$questionTagA = 1;
	}
	else { // IN A TEST - NOT IN THE COURSE
		$actionLabel = get_lang('langReuse');
		$actionIcon1 = "clone";
		$actionIcon2 = "";
		$questionTagA = 0;
	}
}
// 
// display table
// 
$header = array();
$header[] = array(get_lang('QuestionUpperCaseFirstLetter'), false, array("style"=>"text-align:center"), '');
$header[] = array(get_lang('Type'), false, array("style"=>"text-align:center"), array("style"=>"text-align:center"), '');
$header[] = array(get_lang('QuestionCategory'), false, array("style"=>"text-align:center"), array("style"=>"text-align:center"), '');
$header[] = array(get_lang('Difficulty'), false, array("style"=>"text-align:center"), array("style"=>"text-align:center"), '');
$header[] = array($actionLabel, false, array("style"=>"text-align:center"), array("style"=>"text-align:center"), '');

$data = array();

if (is_array($main_question_list)) {
    foreach ($main_question_list as $tabQuestion) {
        $row = array();

        //This function checks if the question can be read
        $question_type = get_question_type_for_question($selected_course, $tabQuestion['id']);

        if (empty($question_type)) {
            continue;
        }

        $row[] = get_a_tag_for_question($questionTagA, $fromExercise, $tabQuestion['id'], $tabQuestion['type'], $tabQuestion['question']);
        $row[] = $question_type;
        $row[] = get_question_categorie_for_question($selected_course, $tabQuestion['id']);
        $row[] = $tabQuestion['level'];
        $row[] = get_action_icon_for_question($actionIcon1, $fromExercise, $tabQuestion['id'], $tabQuestion['type'], 
                    $tabQuestion['question'], $selected_course, $courseCategoryId, $exerciseLevel, 
                    $answerType, $session_id, $exerciseId).
                    "&nbsp;".
                    get_action_icon_for_question($actionIcon2, $fromExercise, $tabQuestion['id'], $tabQuestion['type'], 
                    $tabQuestion['question'], $selected_course, $courseCategoryId, $exerciseLevel, $answerType, 
                    $session_id, $exerciseId);
        $data[] = $row;
    }    
}
Display :: display_sortable_table($header, $data, '', array('per_page_default'=>999,'per_page'=>999,'page_nr'=>1));

if (!$nbrQuestions) {
	echo get_lang('NoQuestion');
}

// The (+) system as now make this button useless
// Hubert Borderiou 27-10-2011
//if (api_get_session_id() == 0){
//	echo '<div style="width:100%; border-top:1px dotted #4171B5;"><button class="save" type="submit">'.get_lang('Reuse').'</button></div></form>';
//}
Display::display_footer();



// Some functions here, just for question_pool to ease the code

/*
	Put the menu entry for level and type to default "Choice"
	It is usefull if you change the exercice, you need to reset the other menus
	hubert.borderiou 13-10-2011
*/
function reset_menu_lvl_type() {
	global $exerciseLevel, $answerType;
	$answerType = -1;
	$exerciseLevel = -1;
}
/*
	Put the menu entry for exercice and level and type to default "Choice"
	It is usefull if you change the course, you need to reset the other menus
	hubert.borderiou 13-10-2011
*/

function reset_menu_exo_lvl_type() {
	global $exerciseId, $courseCategoryId;
	reset_menu_lvl_type();
	$exerciseId = 0;
	$courseCategoryId = 0;
}

// 
// return the <a> link to admin question, if needed
// hubert.borderiou 13-10-2011
function get_a_tag_for_question($in_addA, $in_fromex, $in_questionid, $in_questiontype, $in_questionname) {
	$res = $in_questionname;
	if ($in_addA) {
		$res = "<a href='admin.php?".api_get_cidreq()."&editQuestion=$in_questionid&type=$in_questiontype&fromExercise=$in_fromex'>".$res."</a>";
	}
	return $res;
}


/*
$row[] = get_action_icon_for_question(
$actionIcon1, --
$fromExercise, --
$tabQuestion['id'], --
$tabQuestion['type'], 
$tabQuestion['question'], 
$selected_course, 
$courseCategoryId, 
$exerciseLevel, 
$answerType, 
$session_id, 
$exerciseId).

"&nbsp;".
get_action_icon_for_question($actionIcon2, $fromExercise, $tabQuestion['id'], $tabQuestion['type'], 
$tabQuestion['question'], $selected_course, $courseCategoryId, $exerciseLevel, $answerType, 
$session_id, $exerciseId);


*/

// 
// return the <a> html code for delete, add, clone, edit a question
// hubert.borderiou 13-10-2011
// in_action = the code of the action triggered by the button
// from_exercice = the id of the current exercice from which we click on question pool
// in_questionid = the id of the current question
// in_questiontype = the code of the type of the current question
// in_questionname = the name of the question
// in_selected_course = the if of the course chosen in the FILTERING MENU 
// in_courseCategoryId = the id of the category chosen in the FILTERING MENU 
// in_exerciseLevel = the level of the exercice chosen in the FILTERING MENU
// in_answerType = the code of the type of the question chosen in the FILTERING MENU
// in_session_id = the id of the session_id chosen in the FILTERING MENU
// in_exercice_id = the id of the exercice chosen in the FILTERING MENU
function get_action_icon_for_question($in_action, $from_exercice, $in_questionid, $in_questiontype, $in_questionname, 
    $in_selected_course, $in_courseCategoryId, $in_exerciseLevel, $in_answerType, $in_session_id, $in_exercice_id
) {
    
	$res = "";
	$getParams = "&selected_course=$in_selected_course&courseCategoryId=$in_courseCategoryId&exerciseId=$in_exercice_id&exerciseLevel=$in_exerciseLevel&answerType=$in_answerType&session_id=$in_session_id";
	
	switch ($in_action) {
		case "delete" :	
			$res = "<a href='".api_get_self()."?".api_get_cidreq()."&delete=$in_questionid$getParams' onclick='return confirm_your_choice()'>";
			$res .= Display::return_icon("delete.gif", get_lang('Delete'));
			$res .= "</a>";
			break;
		case "edit" :
			$res = get_a_tag_for_question(1, $from_exercice, $in_questionid, $in_questiontype, Display::return_icon("edit.gif", get_lang('Modify')));
			break;
		case "add":
			// add if question is not already in test
			$myObjEx = new Exercise();
			$myObjEx->read($from_exercice);
			if (!$myObjEx->isInList($in_questionid)) {
				$res = "<a href='".api_get_self()."?".api_get_cidreq()."&recup=$in_questionid&fromExercise=$from_exercice$getParams'>";
				$res .= Display::return_icon("view_more_stats.gif", get_lang('InsertALinkToThisQuestionInTheExercise'));
				$res .= "</a>";
			}
			else {
				$res = "-";
			}
			unset($myObjEx);
			break;
		case "clone":
			$res = "<a href='".api_get_self()."?".api_get_cidreq()."&amp;copy_question=$in_questionid&course_id=$in_selected_course&fromExercise=$from_exercice$getParams'>";
			$res .= Display::return_icon('cd.gif', get_lang('ReUseACopyInCurrentTest'));
			$res .= "</a>";
			break;
		default : 
			$res = $in_action;
			break;
	}
	return $res;
}

// return the icon for the question type
// hubert.borderiou 13-10-2011
function get_question_type_for_question($in_selectedcourse, $in_questionid) {
	$myObjQuestion = Question::read($in_questionid, $in_selectedcourse);    
    $questionType = null;
    if (!empty($myObjQuestion)) {        
        list($typeImg, $typeExpl) = $myObjQuestion->get_type_icon_html();
        $questionType = Display::tag('div', Display::return_icon($typeImg, $typeExpl, array(), 32), array());
        unset($myObjQuestion);
    }
	return $questionType;
}

// return the name of the category for the question in a course
// hubert.borderiou 13-10-2011
function get_question_categorie_for_question($in_courseid, $in_questionid) {
	$cat = Testcategory::getCategoryNameForQuestion($in_questionid, $in_courseid);
	return $cat;
}
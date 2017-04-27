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

use ChamiloSession as Session;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;

$is_allowedToEdit = api_is_allowed_to_edit(null, true);

$delete = isset($_GET['delete']) ? intval($_GET['delete']) : null;
$recup = isset($_GET['recup']) ? intval($_GET['recup']) : null;
$fromExercise = isset($_REQUEST['fromExercise']) ? intval($_REQUEST['fromExercise']) : null;
$exerciseId = isset($_REQUEST['exerciseId']) ? intval($_REQUEST['exerciseId']) : null;
$courseCategoryId = isset($_REQUEST['courseCategoryId']) ? intval($_REQUEST['courseCategoryId']) : null;
$exerciseLevel = isset($_REQUEST['exerciseLevel']) ? intval($_REQUEST['exerciseLevel']) : -1;
$answerType = isset($_REQUEST['answerType']) ? intval($_REQUEST['answerType']) : null;
$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 0;
$question_copy = isset($_REQUEST['question_copy']) ? intval($_REQUEST['question_copy']) : 0;
$session_id = isset($_REQUEST['session_id']) ? intval($_REQUEST['session_id']) : null;
$selected_course = isset($_GET['selected_course']) ? intval($_GET['selected_course']) : null;
// save the id of the previous course selected by user to reset menu if we detect that user change course hub 13-10-2011
$course_id_changed = isset($_GET['course_id_changed']) ? intval($_GET['course_id_changed']) : null;
// save the id of the previous exercise selected by user to reset menu if we detect that user change course hub 13-10-2011
$exercise_id_changed = isset($_GET['exercise_id_changed']) ? intval($_GET['exercise_id_changed']) : null;

// by default when we go to the page for the first time, we select the current course
if (!isset($_GET['selected_course']) && !isset($_GET['exerciseId'])) {
    $selected_course = api_get_course_int_id();
}

$_course = api_get_course_info();

if (empty($objExercise) && !empty($fromExercise)) {
    $objExercise = new Exercise();
    $objExercise->read($fromExercise);
}

$nameTools = get_lang('QuestionPool');
$interbreadcrumb[] = array("url" => "exercise.php", "name" => get_lang('Exercises'));

if (!empty($objExercise)) {
    $interbreadcrumb[] = array(
        "url" => "admin.php?exerciseId=".$objExercise->id."&".api_get_cidreq(),
        "name" => $objExercise->name
    );
}

// message to be displayed if actions successful
$displayMessage = "";
if ($is_allowedToEdit) {
    // Duplicating a Question
    if (!isset($_POST['recup']) && $question_copy != 0 && isset($fromExercise)) {
        $origin_course_id   = intval($_GET['course_id']);
        $origin_course_info = api_get_course_info_by_id($origin_course_id);
        $current_course     = api_get_course_info();
        $old_question_id    = $question_copy;
        // Reading the source question
        $old_question_obj = Question::read($old_question_id, $origin_course_id);

        $courseId = $current_course['real_id'];

        if ($old_question_obj) {
            $old_question_obj->updateTitle(
                $old_question_obj->selectTitle().' - '.get_lang('Copy')
            );
            //Duplicating the source question, in the current course
            $new_id = $old_question_obj->duplicate($current_course);
            //Reading new question
            $new_question_obj = Question::read($new_id);
            $new_question_obj->addToList($fromExercise);
            //Reading Answers obj of the current course
            $new_answer_obj = new Answer($old_question_id, $origin_course_id);
            $new_answer_obj->read();
            //Duplicating the Answers in the current course
            $new_answer_obj->duplicate($new_question_obj, $current_course);
            // destruction of the Question object
            unset($new_question_obj);
            unset($old_question_obj);

            $objExercise = new Exercise($courseId);
            $objExercise->read($fromExercise);
            Session::write('objExercise', $objExercise);
        }
        $displayMessage = get_lang('ItemAdded');
    }

    // Deletes a question from the database and all exercises
    if ($delete) {
        // Construction of the Question object
        $objQuestionTmp = Question::read($delete);
        // if the question exists
        if ($objQuestionTmp) {
            // deletes the question from all exercises
            $objQuestionTmp->delete();
        }
        // destruction of the Question object
        unset($objQuestionTmp);
    } elseif ($recup && $fromExercise) {
        // gets an existing question and copies it into a new exercise
        $objQuestionTmp = Question :: read($recup);
        // if the question exists
        if ($objQuestionTmp) {
            /* Adds the exercise ID represented by $fromExercise into the list
            of exercises for the current question */
            $objQuestionTmp->addToList($fromExercise);
        }
        // destruction of the Question object
        unset($objQuestionTmp);

        if (!$objExercise instanceOf Exercise) {
            $objExercise = new Exercise();
            $objExercise->read($fromExercise);
        }
        // Adds the question ID represented by $recup into the list of questions for the current exercise
        $objExercise->addToList($recup);
        Session::write('objExercise', $objExercise);
    } else if (isset($_POST['recup']) && is_array($_POST['recup']) && $fromExercise) {
        $list_recup = $_POST['recup'];

        foreach ($list_recup as $course_id => $question_data) {
            $origin_course_id   = intval($course_id);
            $origin_course_info = api_get_course_info_by_id($origin_course_id);
            $current_course     = api_get_course_info();

            foreach ($question_data as $old_question_id) {
                //Reading the source question
                $old_question_obj = Question::read($old_question_id, $origin_course_id);
                if ($old_question_obj) {
                    $old_question_obj->updateTitle(
                        $old_question_obj->selectTitle().' - '.get_lang('Copy')
                    );

                    //Duplicating the source question, in the current course
                    $new_id = $old_question_obj->duplicate($current_course);

                    //Reading new question
                    $new_question_obj = Question::read($new_id);
                    $new_question_obj->addToList($fromExercise);

                    //Reading Answers obj of the current course
                    $new_answer_obj = new Answer($old_question_id, $origin_course_id);
                    $new_answer_obj->read();

                    //Duplicating the Answers in the current course
                    $new_answer_obj->duplicate($new_question_obj, $current_course);

                    // destruction of the Question object
                    unset($new_question_obj);
                    unset($old_question_obj);

                    if (!$objExercise instanceOf Exercise) {
                        $objExercise = new Exercise();
                        $objExercise->read($fromExercise);
                    }
                }
            }
        }
        Session::write('objExercise', $objExercise);
    }
}

if (isset($_SESSION['gradebook'])) {
	$gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
	$interbreadcrumb[] = array('url' => '../gradebook/'.Security::remove_XSS($_SESSION['gradebook_dest']), 'name' => get_lang('ToolGradebook'));
}

// if admin of course
if (!$is_allowedToEdit) {
    api_not_allowed(true);
}

$confirmYourChoice = addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES, $charset));

$htmlHeadXtra[] = "
<script>
	function submit_form(obj) {
		document.question_pool.submit();
	}

	function mark_course_id_changed() {
		$('#course_id_changed').val('1');
	}

	function mark_exercise_id_changed() {
		$('#exercise_id_changed').val('1');
	}

	function confirm_your_choice() {
		return confirm('$confirmYourChoice');
	}
</script>";

Display::display_header($nameTools, 'Exercise');

// Menu
echo '<div class="actions">';
if (isset($type)) {
    $url = api_get_self().'?type=1';
} else {
    $url = api_get_self();
}
if (isset($fromExercise) && $fromExercise > 0) {
    echo '<a href="admin.php?'.api_get_cidreq().'&exerciseId='.$fromExercise.'">'.
            Display::return_icon('back.png', get_lang('GoBackToQuestionList'), '', ICON_SIZE_MEDIUM).'</a>';
    $titleAdd = get_lang('AddQuestionToTest');
} else {
    echo '<a href="exercise.php?'.api_get_cidreq().'">'.
        Display::return_icon('back.png', get_lang('BackToExercisesList'), '', ICON_SIZE_MEDIUM).'</a>';
    echo "<a href='admin.php?exerciseId=0'>".Display::return_icon('add_question.gif', get_lang('NewQu'), '', ICON_SIZE_MEDIUM)."</a>";
    $titleAdd = get_lang('ManageAllQuestions');
}
echo '</div>';

if ($displayMessage != "") {
	Display::display_confirmation_message($displayMessage);
	$displayMessage = "";
}

// Form
echo '<form class="form-horizontal" name="question_pool" method="GET" action="'.$url.'">';
// Title
echo '<legend>'.$nameTools.' - '.$titleAdd.'</legend>';
if (isset($type)) {
	echo '<input type="hidden" name="type" value="1">';
}
echo '<input type="hidden" name="fromExercise" value="'.$fromExercise.'">';

// Session list, if sessions are used.
$sessionList = SessionManager::get_sessions_by_user(api_get_user_id(), api_is_platform_admin());

$tabAttrParam = array('onchange' => 'submit_form(this)');
$labelFormRow = get_lang('Session');
$session_select_list = array();
foreach ($sessionList as $item) {
    $session_select_list[$item['session_id']] = $item['session_name'];
}
$select_session_html = Display::select('session_id', $session_select_list, $session_id, $tabAttrParam);
echo Display::form_row($labelFormRow, $select_session_html);

// Course list, get course list of session, or for course where user is admin
if (!empty($session_id) && $session_id != '-1' && !empty($sessionList)) {
    $sessionInfo = array();
    foreach ($sessionList as $session) {
        if ($session['session_id'] == $session_id) {
            $sessionInfo = $session;
        }
    }
    $course_list = $sessionInfo['courses'];
} else {
    $course_list = CourseManager::get_course_list_of_user_as_course_admin(
        api_get_user_id()
    );

    // Admin fix, add the current course in the question pool.
    if (api_is_platform_admin()) {
        $courseInfo = api_get_course_info();
        if (!empty($course_list)) {
            if (!in_array($courseInfo['real_id'], $course_list)) {
                $course_list = array_merge($course_list, array($courseInfo));
            }
        } else {
            $course_list = array($courseInfo);
        }
    }
}

$course_select_list = array();
foreach ($course_list as $item) {
    $courseItemId = $item['real_id'];
    $courseInfo = api_get_course_info_by_id($courseItemId);
	$course_select_list[$courseItemId] = "";
	if ($courseItemId == api_get_course_int_id()) {
		$course_select_list[$courseItemId] = ">&nbsp;&nbsp;&nbsp;&nbsp;";
	}
	$course_select_list[$courseItemId] .= $courseInfo['title'];
}

$select_course_html = Display::select(
    'selected_course',
    $course_select_list,
    $selected_course,
    array('onchange' => 'mark_course_id_changed(); submit_form(this);')
);

echo Display::form_row(get_lang('Course'), $select_course_html);

if (empty($selected_course) || $selected_course == '-1') {
    $course_info = api_get_course_info();
    // no course selected, reset menu test / difficult� / type de reponse
    reset_menu_exo_lvl_type();
} else {
	$course_info = api_get_course_info_by_id($selected_course);
}
// If course has changed, reset the menu default
if ($course_id_changed) {
	reset_menu_exo_lvl_type();
}

$course_id = $course_info['real_id'];
// Redefining table calls
$TBL_EXERCISE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_EXERCISES = Database::get_course_table(TABLE_QUIZ_TEST);
$TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
$TBL_REPONSES = Database::get_course_table(TABLE_QUIZ_ANSWER);
$TBL_CATEGORY = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
$TBL_COURSE_REL_CATEGORY = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);

// Get course categories for the selected course

// get category list for the course $selected_course
$categoryList = TestCategory::getCategoriesIdAndName($selected_course);
$selectCourseCategory = Display::select(
    'courseCategoryId',
    $categoryList,
    $courseCategoryId,
    array('onchange' => 'submit_form(this);'),
    false
);
echo Display::form_row(get_lang("QuestionCategory"), $selectCourseCategory);

// Get exercise list for this course

$exercise_list = ExerciseLib::get_all_exercises_for_course_id(
    $course_info,
    $session_id,
    $selected_course,
    false
);
//Exercise List
$my_exercise_list = array();
$my_exercise_list['0']  = get_lang('AllExercises');
$my_exercise_list['-1'] = get_lang('OrphanQuestions');
if (is_array($exercise_list)) {
    foreach ($exercise_list as $row) {
        $my_exercise_list[$row['id']] = "";
        if ($row['id'] == $fromExercise && $selected_course == api_get_course_int_id()) {
            $my_exercise_list[$row['id']] = ">&nbsp;&nbsp;&nbsp;&nbsp;";
        }
        $my_exercise_list[$row['id']] .= $row['title'];
    }
}

if ($exercise_id_changed == 1) {
	reset_menu_lvl_type();
}
$select_exercise_html = Display::select(
    'exerciseId',
    $my_exercise_list,
    $exerciseId,
    array('onchange' => 'mark_exercise_id_changed(); submit_form(this);'),
    false
);

echo Display::form_row(get_lang('Exercise'), $select_exercise_html);

// Difficulty list (only from 0 to 5)
$levels = array(
    -1 => get_lang('All'),
    0 => 0,
    1 => 1,
    2 => 2,
    3 => 3,
    4 => 4,
    5 => 5
);
$select_difficulty_html = Display::select(
    'exerciseLevel',
    $levels,
    $exerciseLevel,
    array('onchange' => 'submit_form(this);'),
    false
);
echo Display::form_row(get_lang('Difficulty'), $select_difficulty_html);

// Answer type
$question_list = Question::get_question_type_list();

$new_question_list = array();
$new_question_list['-1'] = get_lang('All');
if (!empty($_course)) {
    $objExercise = new Exercise();
    $objExercise->read($fromExercise);
    foreach ($question_list as $key => $item) {
        if ($objExercise->feedback_type == EXERCISE_FEEDBACK_TYPE_DIRECT) {
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
}
// Answer type list
$select_answer_html = Display::select(
    'answerType',
    $new_question_list,
    $answerType,
    array('onchange' => 'submit_form(this);'),
    false
);

echo Display::form_row(get_lang('AnswerType'), $select_answer_html);
$button = '<button class="save" type="submit" name="name" value="'.get_lang('Filter').'">'.get_lang('Filter').'</button>';
echo Display::form_row('', $button);
echo "<input type='hidden' id='course_id_changed' name='course_id_changed' value='0' />";
echo "<input type='hidden' id='exercise_id_changed' name='exercise_id_changed' value='0' />";
?>
</form>
<div class="clear"></div>
<form method="post" action="<?php echo $url.'?'.api_get_cidreq().'&fromExercise='.$fromExercise; ?>" >
<?php
echo '<input type="hidden" name="course_id" value="'.$selected_course.'">';
$mainQuestionList = array();

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
	            id,
	            question,
	            type,
	            level
	        FROM
	            $TBL_EXERCISE_QUESTION qt,
	            $TBL_QUESTIONS qu
	            $from
	        WHERE
	            qt.question_id = qu.id
	            AND qt.exercice_id=$exerciseId
	            AND qt.c_id=$selected_course
	            AND qu.c_id=$selected_course
	            $where
            ORDER BY question_order";

    $result = Database::query($sql);
    while ($row = Database::fetch_array($result, 'ASSOC')) {
        $mainQuestionList[] = $row;
    }
} elseif ($exerciseId == -1) {
	// If we have selected the option 'Orphan questions' in the list-box 'Filter'
	$level_where = '';
	$from = '';
	if (isset($courseCategoryId) && $courseCategoryId > 0) {
		$from = " INNER JOIN  $TBL_COURSE_REL_CATEGORY crc ON crc.question_id=q.id AND crc.c_id= q.c_id ";
		$level_where .= " AND
		        crc.c_id = $selected_course AND
		        crc.category_id = $courseCategoryId";
	}
	if (isset($exerciseLevel) && $exerciseLevel != -1) {
		$level_where = ' AND level='.$exerciseLevel;
	}
	$answer_where = '';
	if (isset($answerType) && $answerType > 0 - 1) {
		$answer_where = ' AND type='.$answerType;
	}

    // @todo fix this query with the new id field
    $sql = " (
                SELECT q.* FROM $TBL_QUESTIONS q
                INNER JOIN $TBL_EXERCISE_QUESTION r
                ON (q.c_id = r.c_id AND q.id = r.question_id)
                INNER JOIN $TBL_EXERCISES ex
                ON (ex.id = r.exercice_id AND ex.c_id = r.c_id )
                $from
                WHERE
                    ex.c_id = '$selected_course' AND
                    ex.active = '-1'
                    $level_where $answer_where
             )
             UNION
             (
                SELECT q.* FROM $TBL_QUESTIONS q
                LEFT OUTER JOIN $TBL_EXERCISE_QUESTION r
                ON (q.c_id = r.c_id AND q.id = r.question_id)
                $from
                WHERE
                    q.c_id = '$selected_course' AND
                    r.question_id is null
                    $level_where $answer_where
             )
             UNION
             (
                SELECT q.* FROM $TBL_QUESTIONS q
                INNER JOIN $TBL_EXERCISE_QUESTION r
                ON (q.c_id = r.c_id AND q.id = r.question_id)
                $from
                WHERE
                    r.c_id = '$selected_course' AND
                    (r.exercice_id = '-1' OR r.exercice_id = '0')
                    $level_where $answer_where
             )";
    $result = Database::query($sql);
    while ($row = Database::fetch_array($result, 'ASSOC')) {
        $mainQuestionList[] = $row;
    }
} else {
	// All tests for selected course
    // If we have not selected any option in the list-box 'Filter'
	$filter = '';
	$from = '';

	if (isset($courseCategoryId) && $courseCategoryId > 0) {
		$from = ", $TBL_COURSE_REL_CATEGORY crc ";
		$filter .= " AND
		            crc.c_id = $selected_course AND
		            crc.question_id = qu.id AND
		            crc.category_id = $courseCategoryId";
	}
	if (isset($exerciseLevel) && $exerciseLevel != -1) {
		$filter .= ' AND level='.$exerciseLevel.' ';
	}
	if (isset($answerType) && $answerType > 0) {
		$filter .= ' AND qu.type='.$answerType.' ';
	}

    if (!empty($session_id) && $session_id != '-1') {
        $mainQuestionList = array();
        if (!empty($course_list)) {
            foreach ($course_list as $course_item) {
                $courseItemId = $course_item['real_id'];

                if (!empty($selected_course) && $selected_course != '-1') {
                    if ($selected_course != $courseItemId) {
                        continue;
                    }
                }

                $exerciseList = ExerciseLib::get_all_exercises($course_item, $session_id);

                if (!empty($exerciseList)) {
                    foreach ($exerciseList as $exercise) {
                        $my_exercise = new Exercise($courseItemId);
                        $my_exercise->read($exercise['id']);
                        if (!empty($my_exercise)) {
                            if (!empty($my_exercise->questionList)) {
                                foreach ($my_exercise->questionList as $question_id) {

                                    $question_obj = Question::read(
                                        $question_id,
                                        $courseItemId
                                    );

                                    if ($exerciseLevel != '-1') {
                                        if ($exerciseLevel != $question_obj->level) {
                                            continue;
                                        }
                                    }

                                    if ($answerType > 0) {
                                        if ($answerType != $question_obj->type) {
                                            continue;
                                        }
                                    }

                                    $categoryIdFromQuestion = TestCategory::getCategoryForQuestion(
                                        $question_obj->id,
                                        $selected_course
                                    );

                                    if ($courseCategoryId > 0 &&
                                        $categoryIdFromQuestion != $courseCategoryId
                                    ) {
                                        continue;
                                    }

                                    if (!empty($objExercise) &&
                                        $objExercise->feedback_type != EXERCISE_FEEDBACK_TYPE_DIRECT
                                    ) {
                                        if ($question_obj->type == HOT_SPOT_DELINEATION) {
                                            continue;
                                        }
                                    }

                                    $question_row = array(
                                        'id' => $question_obj->id,
                                        'question' => $question_obj->question,
                                        'type' => $question_obj->type,
                                        'level' => $question_obj->level,
                                        'exercise_id' => $exercise['id'],
                                        'exercise_name' => $exercise['title'],
                                        'course_id' => $courseItemId,
                                    );
                                    $mainQuestionList[] = $question_row;
                                }
                            }
                        }
                    }
                }
            }
        }
    } else {
        if ($session_id == -1 or empty($session_id)) {
            $session_id = 0;
        }

        // All tests for the course selected, not in session
        $sql = "SELECT DISTINCT qu.id, question, qu.type, level, q.session_id
                FROM
                $TBL_QUESTIONS as qu,
                $TBL_EXERCISE_QUESTION as qt,
                $TBL_EXERCISES as q
                $from
                WHERE
                    qu.c_id = $selected_course AND
                    qt.c_id = $selected_course AND
                    q.c_id = $selected_course AND
                    qu.id = qt.question_id AND
                    q.session_id = $session_id AND
                    q.id = qt.exercice_id $filter
                ORDER BY session_id ASC";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $mainQuestionList[] = $row;
        }
    }
	// forces the value to 0
    $exerciseId = 0;
}

$nbrQuestions = count($mainQuestionList);

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
if ($fromExercise <= 0) {
    // NOT IN A TEST - IN THE COURSE
	if ($selected_course == api_get_course_int_id()) {
		$actionLabel = get_lang('Modify');
		$actionIcon1 = "edit";
		$actionIcon2 = "delete";
        // We are in the course, question title can be a link to the question edit page
		$questionTagA = 1;
    } else { // NOT IN A TEST - NOT IN THE COURSE
		$actionLabel = get_lang('Reuse');
		$actionIcon1 = get_lang('MustBeInATest');
		$actionIcon2 = "";
        // We are not in this course, to messy if we link to the question in another course
		$questionTagA = 0;
	}
} else {
    // IN A TEST - IN THE COURSE
	if ($selected_course == api_get_course_int_id()) {
		$actionLabel = get_lang('Reuse');
		$actionIcon1 = "add";
		$actionIcon2 = "";
		$questionTagA = 1;
    } else {
        // IN A TEST - NOT IN THE COURSE
		$actionLabel = get_lang('Reuse');
		$actionIcon1 = "clone";
		$actionIcon2 = "";
		$questionTagA = 0;
	}
}
// Display table
$header = array(
    array(get_lang('QuestionUpperCaseFirstLetter'), false, array("style"=>"text-align:center"), ''),
    array(get_lang('Type'), false, array("style"=>"text-align:center"), array("style"=>"text-align:center"), ''),
    array(get_lang('QuestionCategory'), false, array("style"=>"text-align:center"), array("style"=>"text-align:center"), ''),
    array(get_lang('Difficulty'), false, array("style"=>"text-align:center"), array("style"=>"text-align:center"), ''),
    array($actionLabel, false, array("style"=>"text-align:center"), array("style"=>"text-align:center"), '')
);

$data = array();

/*$hideDoubles = false;
if (empty($exerciseId) && !empty($session_id) && $session_id != '-1') {
    $hideDoubles = true;
}
$questionAdded = array();*/

if (is_array($mainQuestionList)) {
    foreach ($mainQuestionList as $question) {

        /*if ($hideDoubles) {
            if (in_array($question['question'], $questionAdded)) {
                continue;
            }
        }
        $questionAdded[$question['question']] = $question;*/

        $row = array();

        // This function checks if the question can be read
        $question_type = get_question_type_for_question(
            $selected_course,
            $question['id']
        );

        if (empty($question_type)) {
            continue;
        }

        $sessionId = isset($question['session_id']) ? $question['session_id'] : null;
        $exerciseName = isset($question['exercise_name']) ? '<br />('.$question['exercise_id'].') ' : null;
        $row[] = get_a_tag_for_question(
            $questionTagA,
            $fromExercise,
            $question['id'],
            $question['type'],
            $question['question'],
            $sessionId
        ).$exerciseName;
        $row[] = $question_type;
        $row[] = get_question_categorie_for_question($selected_course, $question['id']);
        $row[] = $question['level'];
        $row[] = get_action_icon_for_question(
                    $actionIcon1,
                    $fromExercise,
                    $question['id'],
                    $question['type'],
                    $question['question'],
                    $selected_course,
                    $courseCategoryId,
                    $exerciseLevel,
                    $answerType,
                    $session_id,
                    $exerciseId
                ).
                "&nbsp;".
                get_action_icon_for_question(
                    $actionIcon2,
                    $fromExercise,
                    $question['id'],
                    $question['type'],
                    $question['question'],
                    $selected_course,
                    $courseCategoryId,
                    $exerciseLevel,
                    $answerType,
                    $session_id,
                    $exerciseId
                );
        $data[] = $row;
    }
}

Display :: display_sortable_table(
    $header,
    $data,
    '',
    array('per_page_default' => 999, 'per_page' => 999, 'page_nr' => 1)
);

if (!$nbrQuestions) {
	echo get_lang('NoQuestion');
}

Display::display_footer();

/**
*	Put the menu entry for level and type to default "Choice"
*	It is useful if you change the exercise, you need to reset the other menus
*   @author hubert.borderiou 13-10-2011
*/
function reset_menu_lvl_type()
{
	global $exerciseLevel, $answerType;
	$answerType = -1;
	$exerciseLevel = -1;
}

/**
*   Put the menu entry for exercise and level and type to default "Choice"
*	It is useful if you change the course, you need to reset the other menus
*   @author hubert.borderiou 13-10-2011
*/
function reset_menu_exo_lvl_type()
{
	global $exerciseId, $courseCategoryId;
	reset_menu_lvl_type();
	$exerciseId = 0;
	$courseCategoryId = 0;
}

/**
 * return the <a> link to admin question, if needed
 * @param int $in_addA
 * @param int $in_fromex
 * @param int $in_questionid
 * @param int $in_questiontype
 * @param string $in_questionname
 * @param int $sessionId
 * @return string
 * @author hubert.borderiou
 */
function get_a_tag_for_question(
    $in_addA,
    $in_fromex,
    $in_questionid,
    $in_questiontype,
    $in_questionname,
    $sessionId
) {
	$res = $in_questionname;
    $sessionIcon = null;
	if ($in_addA) {
        if (!empty($sessionId) && $sessionId != -1) {
            $sessionIcon = ' '.Display::return_icon('star.png', get_lang('Session'));
        }
		$res = "<a href='admin.php?".api_get_cidreq()."&editQuestion=$in_questionid&type=$in_questiontype&fromExercise=$in_fromex'>".
            $res.$sessionIcon.
            "</a>";
	}
	return $res;
}

/**
    Return the <a> html code for delete, add, clone, edit a question
    in_action = the code of the action triggered by the button
    from_exercise = the id of the current exercise from which we click on question pool
    in_questionid = the id of the current question
    in_questiontype = the code of the type of the current question
    in_questionname = the name of the question
    in_selected_course = the if of the course chosen in the FILTERING MENU
    in_courseCategoryId = the id of the category chosen in the FILTERING MENU
    in_exerciseLevel = the level of the exercise chosen in the FILTERING MENU
    in_answerType = the code of the type of the question chosen in the FILTERING MENU
    in_session_id = the id of the session_id chosen in the FILTERING MENU
    in_exercise_id = the id of the exercise chosen in the FILTERING MENU
 */
function get_action_icon_for_question(
    $in_action,
    $from_exercise,
    $in_questionid,
    $in_questiontype,
    $in_questionname,
    $in_selected_course,
    $in_courseCategoryId,
    $in_exerciseLevel,
    $in_answerType,
    $in_session_id,
    $in_exercise_id
) {
	$res = "";
	$getParams = "&selected_course=$in_selected_course&courseCategoryId=$in_courseCategoryId&exerciseId=$in_exercise_id&exerciseLevel=$in_exerciseLevel&answerType=$in_answerType&session_id=$in_session_id";
	switch ($in_action) {
		case "delete" :
			$res = "<a href='".api_get_self()."?".api_get_cidreq().$getParams."&delete=$in_questionid' onclick='return confirm_your_choice()'>";
			$res .= Display::return_icon("delete.png", get_lang('Delete'));
			$res .= "</a>";
			break;
		case "edit" :
			$res = get_a_tag_for_question(
                1,
                $from_exercise,
                $in_questionid,
                $in_questiontype,
                Display::return_icon("edit.png", get_lang('Modify')),
                $in_session_id
            );
			break;
		case "add":
			// add if question is not already in test
			$myObjEx = new Exercise();
			$myObjEx->read($from_exercise);
			if (!$myObjEx->isInList($in_questionid)) {
				$res = "<a href='".api_get_self()."?".api_get_cidreq().$getParams."&recup=$in_questionid&fromExercise=$from_exercise'>";
				$res .= Display::return_icon("view_more_stats.gif", get_lang('InsertALinkToThisQuestionInTheExercise'));
				$res .= "</a>";
            } else {
				$res = "-";
			}
			unset($myObjEx);
			break;
		case "clone":
            $url = api_get_self()."?".api_get_cidreq().$getParams."&question_copy=$in_questionid&course_id=$in_selected_course&fromExercise=$from_exercise";
            $res = Display::url(
                Display::return_icon('cd.png', get_lang('ReUseACopyInCurrentTest')),
                $url
            );
			break;
		default:
			$res = $in_action;
			break;
	}

	return $res;
}

/**
 * Return the icon for the question type
 * @author hubert.borderiou 13-10-2011
 */
function get_question_type_for_question($in_selectedcourse, $in_questionid)
{
	$myObjQuestion = Question::read($in_questionid, $in_selectedcourse);
    $questionType = null;
    if (!empty($myObjQuestion)) {
        list($typeImg, $typeExpl) = $myObjQuestion->get_type_icon_html();
        $questionType = Display::tag('div', Display::return_icon($typeImg, $typeExpl, array(), 32), array());
        unset($myObjQuestion);
    }
	return $questionType;
}

/**
 * Return the name of the category for the question in a course
 * @author hubert.borderiou 13-10-2011
 */
function get_question_categorie_for_question($in_courseid, $in_questionid)
{
	$cat = TestCategory::getCategoryNameForQuestion($in_questionid, $in_courseid);
	return $cat;
}

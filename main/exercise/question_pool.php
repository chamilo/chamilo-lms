<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;
use Knp\Component\Pager\Paginator;

/**
 * Question Pool
 * This script allows administrators to manage questions and add them into their exercises.
 * One question can be in several exercises.
 *
 * @package chamilo.exercise
 *
 * @author Olivier Brouckaert
 * @author Julio Montoya adding support to query all questions from all session, courses, exercises
 * @author Modify by hubert borderiou 2011-10-21 Question's category
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$this_section = SECTION_COURSES;

$is_allowedToEdit = api_is_allowed_to_edit(null, true);

$delete = isset($_GET['delete']) ? intval($_GET['delete']) : null;
$recup = isset($_GET['recup']) ? intval($_GET['recup']) : null;
$fromExercise = isset($_REQUEST['fromExercise']) ? intval($_REQUEST['fromExercise']) : null;
$exerciseId = isset($_REQUEST['exerciseId']) ? intval($_REQUEST['exerciseId']) : null;
$courseCategoryId = isset($_REQUEST['courseCategoryId']) ? intval($_REQUEST['courseCategoryId']) : null;
$exerciseLevel = isset($_REQUEST['exerciseLevel']) ? intval($_REQUEST['exerciseLevel']) : -1;
$answerType = isset($_REQUEST['answerType']) ? intval($_REQUEST['answerType']) : null;
$question_copy = isset($_REQUEST['question_copy']) ? intval($_REQUEST['question_copy']) : 0;
$session_id = isset($_REQUEST['session_id']) ? intval($_REQUEST['session_id']) : null;
$selected_course = isset($_GET['selected_course']) ? intval($_GET['selected_course']) : null;
// save the id of the previous course selected by user to reset menu if we detect that user change course hub 13-10-2011
$course_id_changed = isset($_GET['course_id_changed']) ? intval($_GET['course_id_changed']) : null;
// save the id of the previous exercise selected by user to reset menu if we detect that user change course hub 13-10-2011
$exercise_id_changed = isset($_GET['exercise_id_changed']) ? intval($_GET['exercise_id_changed']) : null;
$questionId = isset($_GET['question_id']) && !empty($_GET['question_id']) ? (int) $_GET['question_id'] : '';
$description = isset($_GET['description']) ? Database::escape_string($_GET['description']) : '';
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

// by default when we go to the page for the first time, we select the current course
if (!isset($_GET['selected_course']) && !isset($_GET['exerciseId'])) {
    $selected_course = api_get_course_int_id();
}

$_course = api_get_course_info();
$objExercise = new Exercise();
if (!empty($fromExercise)) {
    $objExercise->read($fromExercise, false);
}

$nameTools = get_lang('QuestionPool');
$interbreadcrumb[] = ['url' => 'exercise.php?'.api_get_cidreq(), 'name' => get_lang('Exercises')];

if (!empty($objExercise->id)) {
    $interbreadcrumb[] = [
        'url' => 'admin.php?exerciseId='.$objExercise->id.'&'.api_get_cidreq(),
        'name' => $objExercise->selectTitle(true),
    ];
}

// message to be displayed if actions successful
$displayMessage = '';
if ($is_allowedToEdit) {
    // Duplicating a Question
    if (!isset($_POST['recup']) && $question_copy != 0 && isset($fromExercise)) {
        $origin_course_id = (int) $_GET['course_id'];
        $origin_course_info = api_get_course_info_by_id($origin_course_id);
        $current_course = api_get_course_info();
        $old_question_id = $question_copy;
        // Reading the source question
        $old_question_obj = Question::read($old_question_id, $origin_course_info);
        $courseId = $current_course['real_id'];
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
        $limitTeacherAccess = api_get_configuration_value('limit_exercise_teacher_access');
        if ($limitTeacherAccess && !api_is_platform_admin()) {
            api_not_allowed(true);
        }
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

        if (!$objExercise instanceof Exercise) {
            $objExercise = new Exercise();
            $objExercise->read($fromExercise);
        }
        // Adds the question ID represented by $recup into the list of questions for the current exercise
        $objExercise->addToList($recup);
        Session::write('objExercise', $objExercise);
        Display::addFlash(
            Display::return_message(get_lang('ItemAdded'), 'success')
        );
    } elseif (isset($_POST['recup']) && is_array($_POST['recup']) && $fromExercise) {
        $list_recup = $_POST['recup'];
        foreach ($list_recup as $course_id => $question_data) {
            $origin_course_id = (int) $course_id;
            $origin_course_info = api_get_course_info_by_id($origin_course_id);
            $current_course = api_get_course_info();
            foreach ($question_data as $old_question_id) {
                //Reading the source question
                $old_question_obj = Question::read($old_question_id, $origin_course_info);
                if ($old_question_obj) {
                    $old_question_obj->updateTitle(
                        $old_question_obj->selectTitle().' - '.get_lang('Copy')
                    );

                    // Duplicating the source question, in the current course
                    $new_id = $old_question_obj->duplicate($current_course);

                    // Reading new question
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

                    if (!$objExercise instanceof Exercise) {
                        $objExercise = new Exercise();
                        $objExercise->read($fromExercise);
                    }
                }
            }
        }
        Session::write('objExercise', $objExercise);
    }
}

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('ToolGradebook'),
    ];
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
$url = api_get_self().'?'.api_get_cidreq().'&'.http_build_query(
    [
        'fromExercise' => $fromExercise,
        'session_id' => $session_id,
        'selected_course' => $selected_course,
        'courseCategoryId' => $courseCategoryId,
        'exerciseId' => $exerciseId,
        'exerciseLevel' => $exerciseLevel,
        'answerType' => $answerType,
        'question_id' => $questionId,
        'description' => Security::remove_XSS($description),
        'course_id_changed' => $course_id_changed,
        'exercise_id_changed' => $exercise_id_changed,
    ]
);

if (isset($fromExercise) && $fromExercise > 0) {
    echo '<a href="admin.php?'.api_get_cidreq().'&exerciseId='.$fromExercise.'">'.
            Display::return_icon('back.png', get_lang('GoBackToQuestionList'), '', ICON_SIZE_MEDIUM).'</a>';
    $titleAdd = get_lang('AddQuestionToTest');
} else {
    echo '<a href="exercise.php?'.api_get_cidreq().'">'.
        Display::return_icon('back.png', get_lang('BackToExercisesList'), '', ICON_SIZE_MEDIUM).'</a>';
    echo "<a href='admin.php?exerciseId=0'>".
        Display::return_icon('add_question.gif', get_lang('NewQu'), '', ICON_SIZE_MEDIUM)."</a>";
    $titleAdd = get_lang('ManageAllQuestions');
}
echo '</div>';

if ($displayMessage != '') {
    echo Display::return_message($displayMessage, 'confirm');
    $displayMessage = '';
}

// Form
echo '<form class="form-horizontal" name="question_pool" method="GET" action="'.$url.'">';
// Title
echo '<legend>'.$nameTools.' - '.$titleAdd.'</legend>';
echo '<input type="hidden" name="fromExercise" value="'.$fromExercise.'">';

// Session list, if sessions are used.
$sessionList = SessionManager::get_sessions_by_user(api_get_user_id(), api_is_platform_admin());
$session_select_list = [];
foreach ($sessionList as $item) {
    $session_select_list[$item['session_id']] = $item['session_name'];
}
$select_session_html = Display::select(
    'session_id',
    $session_select_list,
    $session_id,
    ['onchange' => 'submit_form(this)']
);
echo Display::form_row(get_lang('Session'), $select_session_html);

// Course list, get course list of session, or for course where user is admin
if (!empty($session_id) && $session_id != '-1' && !empty($sessionList)) {
    $sessionInfo = [];
    foreach ($sessionList as $session) {
        if ($session['session_id'] == $session_id) {
            $sessionInfo = $session;
        }
    }
    $course_list = $sessionInfo['courses'];
} else {
    if (api_is_platform_admin()) {
        $course_list = CourseManager::get_courses_list(0, 0, 'title');
    } else {
        $course_list = CourseManager::get_course_list_of_user_as_course_admin(api_get_user_id());
    }

    // Admin fix, add the current course in the question pool.
    if (api_is_platform_admin()) {
        $courseInfo = api_get_course_info();
        if (!empty($course_list)) {
            if (!in_array($courseInfo['real_id'], $course_list)) {
                $course_list = array_merge($course_list, [$courseInfo]);
            }
        } else {
            $course_list = [$courseInfo];
        }
    }
}

$course_select_list = [];
foreach ($course_list as $item) {
    $courseItemId = $item['real_id'];
    $courseInfo = api_get_course_info_by_id($courseItemId);
    $course_select_list[$courseItemId] = '';
    if ($courseItemId == api_get_course_int_id()) {
        $course_select_list[$courseItemId] = ">&nbsp;&nbsp;&nbsp;&nbsp;";
    }
    $course_select_list[$courseItemId] .= $courseInfo['title'];
}

$select_course_html = Display::select(
    'selected_course',
    $course_select_list,
    $selected_course,
    ['onchange' => 'mark_course_id_changed(); submit_form(this);']
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

// Get category list for the course $selected_course
$categoryList = TestCategory::getCategoriesIdAndName($selected_course);
$selectCourseCategory = Display::select(
    'courseCategoryId',
    $categoryList,
    $courseCategoryId,
    ['onchange' => 'submit_form(this);'],
    false
);
echo Display::form_row(get_lang('QuestionCategory'), $selectCourseCategory);

// Get exercise list for this course
$exercise_list = ExerciseLib::get_all_exercises_for_course_id(
    $course_info,
    $session_id,
    $selected_course,
    false
);

// Exercise List
$my_exercise_list = [];
$my_exercise_list['0'] = get_lang('AllExercises');
$my_exercise_list['-1'] = get_lang('OrphanQuestions');
if (is_array($exercise_list)) {
    foreach ($exercise_list as $row) {
        $my_exercise_list[$row['id']] = '';
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
    ['onchange' => 'mark_exercise_id_changed(); submit_form(this);'],
    false
);

echo Display::form_row(get_lang('Exercise'), $select_exercise_html);

// Difficulty list (only from 0 to 5)
$levels = [
    -1 => get_lang('All'),
    0 => 0,
    1 => 1,
    2 => 2,
    3 => 3,
    4 => 4,
    5 => 5,
];

$select_difficulty_html = Display::select(
    'exerciseLevel',
    $levels,
    $exerciseLevel,
    ['onchange' => 'submit_form(this);'],
    false
);
echo Display::form_row(get_lang('Difficulty'), $select_difficulty_html);

// Answer type
$question_list = Question::get_question_type_list();

$new_question_list = [];
$new_question_list['-1'] = get_lang('All');
if (!empty($_course)) {
    foreach ($question_list as $key => $item) {
        if ($objExercise->feedback_type == EXERCISE_FEEDBACK_TYPE_DIRECT) {
            if (!in_array($key, [HOT_SPOT_DELINEATION, UNIQUE_ANSWER])) {
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
    ['onchange' => 'submit_form(this);'],
    false
);

echo Display::form_row(get_lang('AnswerType'), $select_answer_html);
echo Display::form_row(get_lang('Id'), Display::input('text', 'question_id', $questionId));
echo Display::form_row(
    get_lang('Description'),
    Display::input('text', 'description', Security::remove_XSS($description))
);

$button = '<button class="btn btn-primary save" type="submit" name="name" value="'.get_lang('Filter').'">'.
    get_lang('Filter').'</button>';
echo Display::form_row('', $button);
echo "<input type='hidden' id='course_id_changed' name='course_id_changed' value='0' />";
echo "<input type='hidden' id='exercise_id_changed' name='exercise_id_changed' value='0' />";
?>
</form>
<div class="clear"></div>
<?php

function getQuestions(
    $getCount,
    $start,
    $length,
    $exerciseId,
    $courseCategoryId,
    $selected_course,
    $session_id,
    $exerciseLevel,
    $answerType,
    $questionId,
    $description
) {
    $start = (int) $start;
    $length = (int) $length;
    $exerciseId = (int) $exerciseId;
    $courseCategoryId = (int) $courseCategoryId;
    $selected_course = (int) $selected_course;
    $session_id = (int) $session_id;
    $exerciseLevel = (int) $exerciseLevel;
    $answerType = (int) $answerType;
    $questionId = (int) $questionId;
    $description = Database::escape_string($description);

    $TBL_EXERCISE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
    $TBL_EXERCISES = Database::get_course_table(TABLE_QUIZ_TEST);
    $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
    $TBL_COURSE_REL_CATEGORY = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);

    // if we have selected an exercise in the list-box 'Filter'
    if ($exerciseId > 0) {
        $where = '';
        $from = '';
        if (isset($courseCategoryId) && $courseCategoryId > 0) {
            $from = ", $TBL_COURSE_REL_CATEGORY crc ";
            $where .= " AND 
                    crc.c_id = $selected_course AND 
                    crc.question_id = qu.id AND 
                    crc.category_id = $courseCategoryId";
        }
        if (isset($exerciseLevel) && $exerciseLevel != -1) {
            $where .= ' AND level='.$exerciseLevel;
        }
        if (isset($answerType) && $answerType > 0) {
            $where .= ' AND type='.$answerType;
        }

        if (!empty($questionId)) {
            $where .= ' AND qu.iid='.$questionId;
        }

        if (!empty($description)) {
            $where .= " AND qu.description LIKE '%$description%'";
        }

        $select = 'DISTINCT
                    id,
                    question,
                    type,
                    level,  
                    qt.exercice_id exerciseId';
        if ($getCount) {
            $select = 'count(qu.iid) as count';
        }
        $sql = "SELECT $select
                FROM
                    $TBL_EXERCISE_QUESTION qt,
                    $TBL_QUESTIONS qu
                    $from
                WHERE
                    qt.question_id = qu.id AND 
                    qt.exercice_id = $exerciseId AND 
                    qt.c_id = $selected_course  AND 
                    qu.c_id = $selected_course
                    $where
                ORDER BY question_order";
    } elseif ($exerciseId == -1) {
        // If we have selected the option 'Orphan questions' in the list-box 'Filter'
        $level_where = '';
        $from = '';
        if (isset($courseCategoryId) && $courseCategoryId > 0) {
            $from = " INNER JOIN $TBL_COURSE_REL_CATEGORY crc 
                      ON crc.question_id = q.id AND crc.c_id = q.c_id ";
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

        if (!empty($questionId)) {
            $answer_where .= ' AND q.iid='.$questionId;
        }

        if (!empty($description)) {
            $answer_where .= " AND q.description LIKE '%$description%'";
        }

        $select = ' q.*, r.exercice_id exerciseId  ';
        if ($getCount) {
            $select = 'count(q.iid) as count';
        }

        // @todo fix this query with the new id field
        $sql = " (
                    SELECT $select
                    FROM $TBL_QUESTIONS q
                    INNER JOIN $TBL_EXERCISE_QUESTION r
                    ON (q.c_id = r.c_id AND q.id = r.question_id)
                    INNER JOIN $TBL_EXERCISES ex
                    ON (ex.id = r.exercice_id AND ex.c_id = r.c_id)
                    $from
                    WHERE
                        ex.c_id = '$selected_course' AND
                        ex.active = '-1'
                        $level_where 
                        $answer_where
                )                  
                UNION                 
                (
                    SELECT $select
                    FROM $TBL_QUESTIONS q
                    LEFT OUTER JOIN $TBL_EXERCISE_QUESTION r
                    ON (q.c_id = r.c_id AND q.id = r.question_id)
                    $from
                    WHERE
                        q.c_id = '$selected_course' AND
                        r.question_id is null
                        $level_where 
                        $answer_where
                )                  
                UNION                 
                (
                        SELECT $select
                        FROM $TBL_QUESTIONS q
                        INNER JOIN $TBL_EXERCISE_QUESTION r
                        ON (q.c_id = r.c_id AND q.id = r.question_id)
                        $from
                        WHERE
                            r.c_id = '$selected_course' AND
                            (r.exercice_id = '-1' OR r.exercice_id = '0')
                            $level_where 
                            $answer_where
                    ) 
                 ";
        if ($getCount) {
            $sql = "SELECT SUM(count) count FROM ($sql) as total";
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

        if (!empty($questionId)) {
            $filter .= ' AND qu.iid='.$questionId;
        }

        if (!empty($description)) {
            $filter .= " AND qu.description LIKE '%$description%'";
        }

        if ($session_id == -1 || empty($session_id)) {
            $session_id = 0;
        }
        $sessionCondition = api_get_session_condition($session_id, true, 'q.session_id');

        $select = 'qu.id, question, qu.type, level, q.session_id, qt.exercice_id exerciseId  ';
        if ($getCount) {
            $select = 'count(qu.iid) as count';
        }

        // All tests for the course selected, not in session
        $sql = "SELECT DISTINCT
                    $select
                FROM
                $TBL_QUESTIONS as qu,
                $TBL_EXERCISE_QUESTION as qt,
                $TBL_EXERCISES as q
                $from
                WHERE
                    qu.c_id = $selected_course AND
                    qt.c_id = $selected_course AND
                    q.c_id = $selected_course AND
                    qu.id = qt.question_id
                    $sessionCondition AND
                    q.id = qt.exercice_id
                    $filter
                ORDER BY session_id ASC";
    }

    if ($getCount) {
        $result = Database::query($sql);
        $row = Database::fetch_array($result, 'ASSOC');

        return (int) $row['count'];
    }

    $sql .= " LIMIT $start, $length";

    $result = Database::query($sql);

    $mainQuestionList = [];
    while ($row = Database::fetch_array($result, 'ASSOC')) {
        $mainQuestionList[] = $row;
    }

    return $mainQuestionList;
}

$nbrQuestions = getQuestions(
    true,
    null,
    null,
    $exerciseId,
    $courseCategoryId,
    $selected_course,
    $session_id,
    $exerciseLevel,
    $answerType,
    $questionId,
    $description
);

$length = api_get_configuration_value('question_pagination_length');
if (empty($length)) {
    $length = 20;
}

$start = ($page - 1) * $length;

$paginator = new Paginator();
$pagination = $paginator->paginate([]);
$pagination->setTotalItemCount($nbrQuestions);
$pagination->setItemNumberPerPage($length);
$pagination->setCurrentPageNumber($page);

$pagination->renderer = function ($data) use ($url) {
    $render = '';
    if ($data['pageCount'] > 1) {
        $render = '<ul class="pagination">';
        for ($i = 1; $i <= $data['pageCount']; $i++) {
            $pageContent = '<li><a href="'.$url.'&page='.$i.'">'.$i.'</a></li>';
            if ($data['current'] == $i) {
                $pageContent = '<li class="active"><a href="#" >'.$i.'</a></li>';
            }
            $render .= $pageContent;
        }
        $render .= '</ul>';
    }

    return $render;
};

$mainQuestionList = getQuestions(
    false,
    $start,
    $length,
    $exerciseId,
    $courseCategoryId,
    $selected_course,
    $session_id,
    $exerciseLevel,
    $answerType,
    $questionId,
    $description
);

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

if ($fromExercise <= 0) {
    // NOT IN A TEST - NOT IN THE COURSE
    $actionLabel = get_lang('Reuse');
    $actionIcon1 = get_lang('MustBeInATest');
    $actionIcon2 = '';
    // We are not in this course, to messy if we link to the question in another course
    $questionTagA = 0;
    if ($selected_course == api_get_course_int_id()) {
        // NOT IN A TEST - IN THE COURSE
        $actionLabel = get_lang('Modify');
        $actionIcon1 = 'edit';
        $actionIcon2 = 'delete';
        // We are in the course, question title can be a link to the question edit page
        $questionTagA = 1;
    }
} else {
    // IN A TEST - NOT IN THE COURSE
    $actionLabel = get_lang('Reuse');
    $actionIcon1 = 'clone';
    $actionIcon2 = '';
    $questionTagA = 0;

    if ($selected_course == api_get_course_int_id()) {
        // IN A TEST - IN THE COURSE
        $actionLabel = get_lang('Reuse');
        $actionIcon1 = 'add';
        $actionIcon2 = '';
        $questionTagA = 1;
    }
}

$data = [];
if (is_array($mainQuestionList)) {
    foreach ($mainQuestionList as $question) {
        $row = [];
        // This function checks if the question can be read
        $question_type = get_question_type_for_question($selected_course, $question['id']);

        if (empty($question_type)) {
            continue;
        }
        $sessionId = isset($question['session_id']) ? $question['session_id'] : null;
        $exerciseName = isset($question['exercise_name']) ? '<br />('.$question['exercise_id'].') ' : null;
        $row[] = getLinkForQuestion(
            $questionTagA,
            $fromExercise,
            $question['id'],
            $question['type'],
            $question['question'],
            $sessionId,
            $question['exerciseId']
        ).$exerciseName;

        $row[] = $question_type;
        $row[] = TestCategory::getCategoryNameForQuestion($question['id'], $selected_course);
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
            $question['exerciseId'],
            $objExercise
        ).'&nbsp;'.
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
            $question['exerciseId'],
            $objExercise
        );
        $data[] = $row;
    }
}

// Display table
$header = [
    [
        get_lang('QuestionUpperCaseFirstLetter'),
        false,
        ['style' => 'text-align:center'],
        '',
    ],
    [
        get_lang('Type'),
        false,
        ['style' => 'text-align:center'],
        ['style' => 'text-align:center'],
        '',
    ],
    [
        get_lang('QuestionCategory'),
        false,
        ['style' => 'text-align:center'],
        ['style' => 'text-align:center'],
        '',
    ],
    [
        get_lang('Difficulty'),
        false,
        ['style' => 'text-align:center'],
        ['style' => 'text-align:center'],
        '',
    ],
    [
        $actionLabel,
        false,
        ['style' => 'text-align:center'],
        ['style' => 'text-align:center'],
        '',
    ],
];

echo $pagination;

Display::display_sortable_table(
    $header,
    $data,
    '',
    ['per_page_default' => 999, 'per_page' => 999, 'page_nr' => 1]
);

Display::display_footer();

/**
 * Put the menu entry for level and type to default "Choice"
 * It is useful if you change the exercise, you need to reset the other menus.
 *
 * @author hubert.borderiou 13-10-2011
 */
function reset_menu_lvl_type()
{
    global $exerciseLevel, $answerType;
    $answerType = -1;
    $exerciseLevel = -1;
}

/**
 * Put the menu entry for exercise and level and type to default "Choice"
 * It is useful if you change the course, you need to reset the other menus.
 *
 * @author hubert.borderiou 13-10-2011
 */
function reset_menu_exo_lvl_type()
{
    global $exerciseId, $courseCategoryId;
    reset_menu_lvl_type();
    $exerciseId = 0;
    $courseCategoryId = 0;
}

/**
 * return the <a> link to admin question, if needed.
 *
 * @param int    $in_addA
 * @param int    $in_fromex
 * @param int    $questionId
 * @param int    $questiontype
 * @param string $questionName
 * @param int    $sessionId
 * @param int    $exerciseId
 *
 * @return string
 *
 * @author hubert.borderiou
 */
function getLinkForQuestion(
    $in_addA,
    $fromExercise,
    $questionId,
    $questionType,
    $questionName,
    $sessionId,
    $exerciseId
) {
    $result = $questionName;
    if ($in_addA) {
        $sessionIcon = '';
        if (!empty($sessionId) && $sessionId != -1) {
            $sessionIcon = ' '.Display::return_icon('star.png', get_lang('Session'));
        }
        $exerciseId = (int) $exerciseId;
        $questionId = (int) $questionId;
        $questionType = (int) $questionType;
        $fromExercise = (int) $fromExercise;

        $result = Display::url(
            $questionName.$sessionIcon,
            "admin.php?".api_get_cidreq().
            "&exerciseId=$exerciseId&editQuestion=$questionId&type=$questionType&fromExercise=$fromExercise"
        );
    }

    return $result;
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
    $in_exercise_id,
    Exercise $myObjEx
) {
    $limitTeacherAccess = api_get_configuration_value('limit_exercise_teacher_access');
    $getParams = "&selected_course=$in_selected_course&courseCategoryId=$in_courseCategoryId&exerciseId=$in_exercise_id&exerciseLevel=$in_exerciseLevel&answerType=$in_answerType&session_id=$in_session_id";
    $res = '';
    switch ($in_action) {
        case 'delete':
            if ($limitTeacherAccess && !api_is_platform_admin()) {
                break;
            }
            $res = "<a href='".api_get_self()."?".
                api_get_cidreq().$getParams."&delete=$in_questionid' onclick='return confirm_your_choice()'>";
            $res .= Display::return_icon('delete.png', get_lang('Delete'));
            $res .= "</a>";
            break;
        case 'edit':
            $res = getLinkForQuestion(
                1,
                $from_exercise,
                $in_questionid,
                $in_questiontype,
                Display::return_icon('edit.png', get_lang('Modify')),
                $in_session_id,
                $in_exercise_id
            );
            break;
        case 'add':
            $res = '-';
            if (!$myObjEx->hasQuestion($in_questionid)) {
                $res = "<a href='".api_get_self()."?".
                    api_get_cidreq().$getParams."&recup=$in_questionid&fromExercise=$from_exercise'>";
                $res .= Display::return_icon('view_more_stats.gif', get_lang('InsertALinkToThisQuestionInTheExercise'));
                $res .= "</a>";
            }
            break;
        case 'clone':
            $url = api_get_self().'?'.api_get_cidreq().$getParams.
                "&question_copy=$in_questionid&course_id=$in_selected_course&fromExercise=$from_exercise";
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
 * Return the icon for the question type.
 *
 * @author hubert.borderiou 13-10-2011
 */
function get_question_type_for_question($in_selectedcourse, $in_questionid)
{
    $courseInfo = api_get_course_info_by_id($in_selectedcourse);
    $myObjQuestion = Question::read($in_questionid, $courseInfo);
    $questionType = null;
    if (!empty($myObjQuestion)) {
        list($typeImg, $typeExpl) = $myObjQuestion->get_type_icon_html();
        $questionType = Display::tag('div', Display::return_icon($typeImg, $typeExpl, [], 32), []);
    }

    return $questionType;
}

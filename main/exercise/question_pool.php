<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField as ExtraFieldEntity;
use ChamiloSession as Session;
use Knp\Component\Pager\Paginator;

/**
 * Question Pool
 * This script allows administrators to manage questions and add them into their exercises.
 * One question can be in several exercises.
 *
 * @author Olivier Brouckaert
 * @author Julio Montoya adding support to query all questions from all session, courses, exercises
 * @author Modify by hubert borderiou 2011-10-21 Question's category
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$this_section = SECTION_COURSES;

$is_allowedToEdit = api_is_allowed_to_edit(null, true);

$delete = isset($_GET['delete']) ? (int) $_GET['delete'] : null;
$recup = isset($_GET['recup']) ? (int) $_GET['recup'] : null;
$fromExercise = isset($_REQUEST['fromExercise']) ? (int) $_REQUEST['fromExercise'] : null;
$exerciseId = isset($_REQUEST['exerciseId']) ? (int) $_REQUEST['exerciseId'] : null;
$courseCategoryId = isset($_REQUEST['courseCategoryId']) ? (int) $_REQUEST['courseCategoryId'] : null;
$exerciseLevel = isset($_REQUEST['exerciseLevel']) ? (int) $_REQUEST['exerciseLevel'] : -1;
$answerType = isset($_REQUEST['answerType']) ? (int) $_REQUEST['answerType'] : null;
$question_copy = isset($_REQUEST['question_copy']) ? (int) $_REQUEST['question_copy'] : 0;
$session_id = isset($_REQUEST['session_id']) ? (int) $_REQUEST['session_id'] : null;
$selected_course = isset($_GET['selected_course']) ? (int) $_GET['selected_course'] : null;
// save the id of the previous course selected by user to reset menu if we detect that user change course hub 13-10-2011
$course_id_changed = isset($_GET['course_id_changed']) ? (int) $_GET['course_id_changed'] : null;
// save the id of the previous exercise selected by user to reset menu if we detect that user change course hub 13-10-2011
$exercise_id_changed = isset($_GET['exercise_id_changed']) ? (int) $_GET['exercise_id_changed'] : null;
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

if (!empty($objExercise->iid)) {
    $interbreadcrumb[] = [
        'url' => 'admin.php?exerciseId='.$objExercise->iid.'&'.api_get_cidreq(),
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
        $objQuestionTmp = isQuestionInActiveQuiz($delete) ? false : Question::read($delete);
        // if the question exists
        if ($objQuestionTmp) {
            // deletes the question from all exercises
            $objQuestionTmp->delete();

            // solving the error that when deleting a question from the question pool it is not displaying all questions
            $exerciseId = null;
        }
        // destruction of the Question object
        unset($objQuestionTmp);
    } elseif ($recup && $fromExercise) {
        // gets an existing question and copies it into a new exercise
        $objQuestionTmp = Question::read($recup);
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
        Display::addFlash(Display::return_message(get_lang('ItemAdded'), 'success'));
    } elseif (isset($_POST['recup']) && is_array($_POST['recup']) && $fromExercise) {
        $list_recup = $_POST['recup'];
        foreach ($list_recup as $course_id => $question_data) {
            $origin_course_id = (int) $course_id;
            $origin_course_info = api_get_course_info_by_id($origin_course_id);
            $current_course = api_get_course_info();
            foreach ($question_data as $old_question_id) {
                // Reading the source question
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

if (isset($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {
        case 'reuse':
            if (!empty($_REQUEST['questions']) && !empty($fromExercise)) {
                $questions = $_REQUEST['questions'];
                $objExercise = new Exercise();
                $objExercise->read($fromExercise, false);

                if (count($questions) > 0) {
                    foreach ($questions as $questionId) {
                        // gets an existing question and copies it into a new exercise
                        $objQuestionTmp = Question::read($questionId);
                        // if the question exists
                        if ($objQuestionTmp) {
                            if (false === $objExercise->hasQuestion($questionId)) {
                                $objExercise->addToList($questionId);
                                $objQuestionTmp->addToList($fromExercise);
                            }
                        }
                    }
                }

                Display::addFlash(Display::return_message(get_lang('Added')));
                header('Location: '.$url);
                exit;
            }
            break;
        case 'clone':
            if (!empty($_REQUEST['questions']) && !empty($fromExercise)) {
                $questions = $_REQUEST['questions'];
                $origin_course_id = (int) $_GET['course_id'];

                $origin_course_info = api_get_course_info_by_id($origin_course_id);
                $current_course = api_get_course_info();

                if (count($questions) > 0) {
                    foreach ($questions as $questionId) {
                        // gets an existing question and copies it into a new exercise
                        // Reading the source question
                        $old_question_obj = Question::read($questionId, $origin_course_info);
                        $courseId = $current_course['real_id'];
                        if ($old_question_obj) {
                            $old_question_obj->updateTitle($old_question_obj->selectTitle().' - '.get_lang('Copy'));
                            // Duplicating the source question, in the current course
                            $new_id = $old_question_obj->duplicate($current_course);
                            // Reading new question
                            $new_question_obj = Question::read($new_id);
                            $new_question_obj->addToList($fromExercise);
                            //Reading Answers obj of the current course
                            $new_answer_obj = new Answer($questionId, $origin_course_id);
                            $new_answer_obj->read();
                            //Duplicating the Answers in the current course
                            $new_answer_obj->duplicate($new_question_obj, $current_course);
                            // destruction of the Question object
                            unset($new_question_obj);
                            unset($old_question_obj);
                        }
                    }
                }

                Display::addFlash(Display::return_message(get_lang('Added')));
                header('Location: '.$url);
                exit;
            }
            break;
    }
}

Display::display_header($nameTools, 'Exercise');

// Menu
echo '<div class="actions">';
if (isset($fromExercise) && $fromExercise > 0) {
    echo '<a href="admin.php?'.api_get_cidreq().'&exerciseId='.$fromExercise.'">'.
            Display::return_icon('back.png', get_lang('GoBackToQuestionList'), '', ICON_SIZE_MEDIUM).'</a>';
    $titleAdd = get_lang('AddQuestionToTest');
} else {
    echo '<a href="exercise.php?'.api_get_cidreq().'">'.
        Display::return_icon('back.png', get_lang('BackToExercisesList'), '', ICON_SIZE_MEDIUM).'</a>';
    echo "<a href='question_create.php?".api_get_cidreq()."'>".
        Display::return_icon('add_question.gif', get_lang('NewQu'), '', ICON_SIZE_MEDIUM).'</a>';
    $titleAdd = get_lang('ManageAllQuestions');
}
echo '</div>';

if ('' != $displayMessage) {
    echo Display::return_message($displayMessage, 'confirm');
}

// Session list, if sessions are used.
$sessionList = SessionManager::get_sessions_by_user(api_get_user_id(), api_is_platform_admin());
$session_select_list = ['-1' => get_lang('Select')];
foreach ($sessionList as $item) {
    $session_select_list[$item['session_id']] = $item['session_name'];
}

// Course list, get course list of session, or for course where user is admin
$course_list = [];

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

$course_select_list = ['-1' => get_lang('Select')];
foreach ($course_list as $item) {
    $courseItemId = $item['real_id'];
    $courseInfo = api_get_course_info_by_id($courseItemId);
    $course_select_list[$courseItemId] = '';
    if ($courseItemId == api_get_course_int_id()) {
        $course_select_list[$courseItemId] = '>&nbsp;&nbsp;&nbsp;&nbsp;';
    }
    $course_select_list[$courseItemId] .= $courseInfo['title'];
}

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

// Get category list for the course $selected_course
$categoryList = TestCategory::getCategoriesIdAndName($selected_course);

// Get exercise list for this course
$exercise_list = ExerciseLib::get_all_exercises_for_course_id(
    $course_info,
    $session_id,
    $selected_course,
    false
);

if (1 == $exercise_id_changed) {
    reset_menu_lvl_type();
}

// Exercise List
$my_exercise_list = [];
$my_exercise_list['0'] = get_lang('AllExercises');
$my_exercise_list['-1'] = get_lang('OrphanQuestions');
$titleSavedAsHtml = api_get_configuration_value('save_titles_as_html');
if (is_array($exercise_list)) {
    foreach ($exercise_list as $row) {
        $my_exercise_list[$row['iid']] = '';
        if ($row['iid'] == $fromExercise && $selected_course == api_get_course_int_id()) {
            $my_exercise_list[$row['iid']] = ">&nbsp;&nbsp;&nbsp;&nbsp;";
        }

        $exerciseTitle = $row['title'];
        if ($titleSavedAsHtml) {
            $exerciseTitle = strip_tags(api_html_entity_decode(trim($exerciseTitle)));
        }
        $my_exercise_list[$row['iid']] .= $exerciseTitle;
    }
}

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

// Answer type
$question_list = Question::getQuestionTypeList();

$new_question_list = [];
$new_question_list['-1'] = get_lang('All');
if (!empty($_course)) {
    foreach ($question_list as $key => $item) {
        if (in_array(
            $objExercise->getFeedbackType(),
            [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP]
        )) {
            if (!in_array($key, [HOT_SPOT_DELINEATION, UNIQUE_ANSWER])) {
                continue;
            }
            $new_question_list[$key] = get_lang($item[1]);
        } else {
            if (HOT_SPOT_DELINEATION == $key) {
                continue;
            }
            $new_question_list[$key] = get_lang($item[1]);
        }
    }
}

// Form
$form = new FormValidator('question_pool', 'GET', $url);
$form->addHeader($nameTools.' - '.$titleAdd);
$form->addHidden('fromExercise', $fromExercise);
$form
    ->addSelect(
        'session_id',
        get_lang('Session'),
        $session_select_list,
        ['onchange' => 'submit_form(this)', 'id' => 'session_id']
    )
    ->setSelected($session_id);
$form
    ->addSelect(
        'selected_course',
        get_lang('Course'),
        $course_select_list,
        ['onchange' => 'mark_course_id_changed(); submit_form(this);', 'id' => 'selected_course']
    )
    ->setSelected($selected_course);
$form
    ->addSelect(
        'courseCategoryId',
        get_lang('QuestionCategory'),
        $categoryList,
        ['onchange' => 'submit_form(this);', 'id' => 'courseCategoryId']
    )
    ->setSelected($courseCategoryId);
$form
    ->addSelect(
        'exerciseId',
        get_lang('Exercise'),
        $my_exercise_list,
        ['onchange' => 'mark_exercise_id_changed(); submit_form(this);', 'id' => 'exerciseId']
    )
    ->setSelected($exerciseId);
$form
    ->addSelect(
        'exerciseLevel',
        get_lang('Difficulty'),
        $levels,
        ['onchange' => 'submit_form(this);', 'id' => 'exerciseLevel']
    )
    ->setSelected($exerciseLevel);
$form
    ->addSelect(
        'answerType',
        get_lang('AnswerType'),
        $new_question_list,
        ['onchange' => 'submit_form(this);', 'id' => 'answerType']
    )
    ->setSelected($answerType);
$form
    ->addText('question_id', get_lang('Id'), false)
    ->setValue($questionId);
$form
    ->addText('description', get_lang('Description'), false)
    ->setValue(Security::remove_XSS($description));

$form->addHidden('course_id_changed', '0');
$form->addHidden('exercise_id_changed', '0');

$extraField = new ExtraField('question');
$jsForExtraFields = $extraField->addElements($form, 0, [], true);

$form->addButtonFilter(get_lang('Filter'), 'name');

echo $form->display();

echo '<script>$(function () {
        '.$jsForExtraFields['jquery_ready_content'].'
    })</script>';
?>
<div class="clear"></div>
<?php

/**
 * @return array
 */
function getExtraFieldConditions(array $formValues, $queryType = 'from')
{
    $extraField = new ExtraField('question');
    $fields = $extraField->get_all(
        ['visible_to_self = ? AND filter = ?' => [1, 1]],
        'display_text'
    );

    $from = '';
    $where = '';

    foreach ($fields as $field) {
        $variable = $field['variable'];

        if (empty($formValues["extra_$variable"])) {
            continue;
        }

        $value = $formValues["extra_$variable"];

        switch ($field['field_type']) {
            case ExtraField::FIELD_TYPE_CHECKBOX:
                $value = $value["extra_$variable"];
                break;
            case ExtraField::FIELD_TYPE_DOUBLE_SELECT:
                if (!isset($value["extra_{$variable}_second"])) {
                    $value = null;
                    break;
                }

                $value = $value["extra_$variable"].'::'.$value["extra_{$variable}_second"];
                break;
        }

        if (empty($value)) {
            continue;
        }

        if ($queryType === 'from') {
            $from .= ", extra_field_values efv_$variable, extra_field ef_$variable";
            $where .= "AND (
                    qu.iid = efv_$variable.item_id
                    AND efv_$variable.field_id = ef_$variable.id
                    AND ef_$variable.extra_field_type = ".ExtraFieldEntity::QUESTION_FIELD_TYPE."
                    AND ef_$variable.variable = '$variable'
                    AND efv_$variable.value = '$value'
                )";
        } elseif ($queryType === 'join') {
            $from .= " INNER JOIN extra_field_values efv_$variable ON qu.iid = efv_$variable.item_id
                INNER JOIN extra_field ef_$variable ON efv_$variable.field_id = ef_$variable.id";
            $where .= "AND (
                    ef_$variable.extra_field_type = ".ExtraFieldEntity::QUESTION_FIELD_TYPE."
                    AND ef_$variable.variable = '$variable'
                    AND efv_$variable.value = '$value'
                )";
        }
    }

    return [
        'from' => $from,
        'where' => $where,
    ];
}

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
    $description,
    $fromExercise = 0,
    $formValues = []
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
    $fromExercise = (int) $fromExercise;
    $description = Database::escape_string($description);

    $TBL_EXERCISE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
    $TBL_EXERCISES = Database::get_course_table(TABLE_QUIZ_TEST);
    $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
    $TBL_COURSE_REL_CATEGORY = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);

    $currentExerciseCondition = '';
    if (!empty($fromExercise)) {
        $currentCourseId = api_get_course_int_id();
        $currentExerciseCondition = "
            AND qu.iid NOT IN (
                SELECT question_id FROM $TBL_EXERCISE_QUESTION
                WHERE exercice_id = $fromExercise AND c_id = $currentCourseId
            )";
    }

    // if we have selected an exercise in the list-box 'Filter'
    if ($exerciseId > 0) {
        $efConditions = getExtraFieldConditions($formValues, 'from');

        $where = '';
        $from = '';
        if (isset($courseCategoryId) && $courseCategoryId > 0) {
            $from = ", $TBL_COURSE_REL_CATEGORY crc ";
            $where .= " AND
                    crc.c_id = $selected_course AND
                    crc.question_id = qu.iid AND
                    crc.category_id = $courseCategoryId";
        }
        if (isset($exerciseLevel) && -1 != $exerciseLevel) {
            $where .= ' AND level = '.$exerciseLevel;
        }
        if (isset($answerType) && $answerType > 0) {
            $where .= ' AND type = '.$answerType;
        }

        if (!empty($questionId)) {
            $where .= ' AND qu.iid = '.$questionId;
        }

        if (!empty($description)) {
            $where .= " AND qu.description LIKE '%$description%'";
        }

        $select = 'DISTINCT
                    qu.iid,
                    qu.question,
                    qu.type,
                    qu.level,
                    qt.exercice_id exerciseId';
        if ($getCount) {
            $select = 'count(qu.iid) as count';
        }
        $sql = "SELECT $select
                FROM
                    $TBL_EXERCISE_QUESTION qt
                    INNER JOIN $TBL_QUESTIONS qu
                    ON qt.question_id = qu.iid
                    $from
                    {$efConditions['from']}
                WHERE
                    qt.exercice_id = $exerciseId AND
                    qt.c_id = $selected_course
                    $where
                    $currentExerciseCondition
                    {$efConditions['where']}
                ORDER BY BINARY qu.question ASC
                 ";
    } elseif ($exerciseId == -1) {
        $efConditions = getExtraFieldConditions($formValues, 'join');
        // If we have selected the option 'Orphan questions' in the list-box 'Filter'
        $level_where = '';
        $from = '';
        if (isset($courseCategoryId) && $courseCategoryId > 0) {
            $from = " INNER JOIN $TBL_COURSE_REL_CATEGORY crc
                      ON crc.question_id = ex.iid ";
            $level_where .= " AND
                    crc.c_id = $selected_course AND
                    crc.category_id = $courseCategoryId";
        }
        if (isset($exerciseLevel) && -1 != $exerciseLevel) {
            $level_where = ' AND level='.$exerciseLevel;
        }
        $answer_where = '';
        if (isset($answerType) && $answerType > 0 - 1) {
            $answer_where = ' AND qu.type='.$answerType;
        }

        if (!empty($questionId)) {
            $answer_where .= ' AND ex.iid = '.$questionId;
        }

        if (!empty($description)) {
            $answer_where .= " AND ex.description LIKE '%$description%'";
        }

        $select = ' qu.*, r.exercice_id exerciseId  ';
        if ($getCount) {
            $select = 'count(qu.iid) as count';
        }

        // @todo fix this query with the new id field
        $sql = " (
                    SELECT $select
                    FROM $TBL_QUESTIONS qu
                    INNER JOIN $TBL_EXERCISE_QUESTION r
                    ON qu.iid = r.question_id
                    INNER JOIN $TBL_EXERCISES ex
                    ON ex.iid = r.exercice_id
                    $from
                    {$efConditions['from']}
                    WHERE
                        ex.c_id = $selected_course AND
                        ex.active = '-1'
                        $level_where
                        $answer_where
                        {$efConditions['where']}
                )
                UNION
                (
                    SELECT $select
                    FROM $TBL_QUESTIONS qu
                    LEFT OUTER JOIN $TBL_EXERCISE_QUESTION r
                    ON qu.iid = r.question_id
                    $from
                    {$efConditions['from']}
                    WHERE
                        qu.c_id = $selected_course AND
                        r.question_id is null
                        $level_where
                        $answer_where
                        {$efConditions['where']}
                )
                UNION
                (
                        SELECT $select
                        FROM $TBL_QUESTIONS qu
                        INNER JOIN $TBL_EXERCISE_QUESTION r
                        ON qu.iid = r.question_id
                        $from
                        {$efConditions['from']}
                        WHERE
                            r.c_id = $selected_course AND
                            (r.exercice_id = '-1' OR r.exercice_id = '0')
                            $level_where
                            $answer_where
                            {$efConditions['where']}
                    )
                 ";
        if ($getCount) {
            $sql = "SELECT SUM(count) count FROM ($sql) as total";
        }
    } else {
        $efConditions = getExtraFieldConditions($formValues, 'from');
        // All tests for selected course
        // If we have not selected any option in the list-box 'Filter'
        $filter = '';
        $from = '';
        if (isset($courseCategoryId) && $courseCategoryId > 0) {
            $from = ", $TBL_COURSE_REL_CATEGORY crc ";
            $filter .= " AND
                        crc.c_id = $selected_course AND
                        crc.question_id = qu.iid AND
                        crc.category_id = $courseCategoryId";
        }
        if (isset($exerciseLevel) && -1 != $exerciseLevel) {
            $filter .= ' AND level = '.$exerciseLevel.' ';
        }
        if (isset($answerType) && $answerType > 0) {
            $filter .= ' AND qu.type = '.$answerType.' ';
        }

        if (!empty($questionId)) {
            $filter .= ' AND qu.iid = '.$questionId;
        }

        if (!empty($description)) {
            $filter .= " AND qu.description LIKE '%$description%'";
        }

        if (-1 == $session_id || empty($session_id)) {
            $session_id = 0;
        }
        $sessionCondition = api_get_session_condition($session_id, true, 'ex.session_id');

        $select = 'qu.iid, question, qu.type, level, ex.session_id, qt.exercice_id exerciseId  ';
        if ($getCount) {
            $select = 'count(qu.iid) as count';
        }

        // All tests for the course selected, not in session
        $sql = "SELECT DISTINCT
                    $select
                FROM
                $TBL_QUESTIONS as qu
                INNER JOIN $TBL_EXERCISE_QUESTION as qt
                ON qu.iid = qt.question_id
                INNER JOIN $TBL_EXERCISES as ex
                ON ex.iid = qt.exercice_id
                {$efConditions['from']}
                $from
                WHERE
                    qt.c_id = $selected_course AND
                    ex.c_id = $selected_course
                    $sessionCondition
                    $filter
                    $currentExerciseCondition
                    {$efConditions['where']}
                GROUP BY qu.iid
                ORDER BY BINARY qu.question ASC
                ";
    }

    if ($getCount) {
        $result = Database::query($sql);
        $row = Database::fetch_array($result, 'ASSOC');
        if ($row) {
            return (int) $row['count'];
        }

        return 0;
    }

    $sql .= " LIMIT $start, $length";
    $result = Database::query($sql);
    $mainQuestionList = [];
    while ($row = Database::fetch_array($result, 'ASSOC')) {
        if ($exerciseId == -1 && isQuestionInActiveQuiz($row['iid'])) {
            continue;
        }

        $mainQuestionList[] = $row;
    }

    return $mainQuestionList;
}

$formValues = $form->validate() ? $form->exportValues() : [];

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
    $description,
    $fromExercise,
    $formValues
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
    $description,
    $fromExercise,
    $formValues
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
    $actionLabel = get_lang('ReUseACopyInCurrentTest');
    $actionIcon1 = 'clone';
    $actionIcon2 = '';
    $questionTagA = 0;

    if ($selected_course == api_get_course_int_id()) {
        // IN A TEST - IN THE COURSE
        $actionLabel = get_lang('Reuse');
        $actionIcon1 = 'add';
        $actionIcon2 = '';
        $questionTagA = 1;
    } elseif (true === api_get_configuration_value('quiz_question_allow_inter_course_linking')) {
        $actionIcon2 = 'add';
    }
}

$data = [];
if (is_array($mainQuestionList)) {
    foreach ($mainQuestionList as $question) {
        $row = [];
        // This function checks if the question can be read
        $question_type = get_question_type_for_question($selected_course, $question['iid']);

        if (empty($question_type)) {
            continue;
        }
        $sessionId = isset($question['session_id']) ? $question['session_id'] : null;
        if (!$objExercise->hasQuestion($question['iid'])) {
            $row[] = Display::input(
                'checkbox',
                'questions[]',
                $question['iid'],
                ['class' => 'question_checkbox']
            );
        } else {
            $row[] = '';
        }

        $row[] = getLinkForQuestion(
            $questionTagA,
            $fromExercise,
            $question['iid'],
            $question['type'],
            $question['question'],
            $sessionId,
            $question['exerciseId']
        );

        $row[] = $question_type;
        $row[] = TestCategory::getCategoryNameForQuestion($question['iid'], $selected_course);
        $row[] = $question['level'];
        $row[] = get_action_icon_for_question(
            $actionIcon1,
            $fromExercise,
            $question['iid'],
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
            $question['iid'],
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

$headers = [
    '',
    get_lang('QuestionUpperCaseFirstLetter'),
    get_lang('Type'),
    get_lang('QuestionCategory'),
    get_lang('Difficulty'),
    $actionLabel,
];

echo $pagination;

$tableId = 'question_pool_id';
echo '<form id="'.$tableId.'" method="get" action="'.$url.'">';
echo '<input type="hidden" name="fromExercise" value="'.$fromExercise.'">';
echo '<input type="hidden" name="cidReq" value="'.$_course['code'].'">';
echo '<input type="hidden" name="selected_course" value="'.$selected_course.'">';
echo '<input type="hidden" name="course_id" value="'.$selected_course.'">';
echo '<input type="hidden" name="action">';

$table = new HTML_Table(['class' => 'table table-hover table-striped table-bordered data_table'], false);
$row = 0;
$column = 0;
foreach ($headers as $header) {
    $table->setHeaderContents($row, $column, $header);
    $column++;
}

$row = 1;
foreach ($data as $rows) {
    $column = 0;
    foreach ($rows as $value) {
        $table->setCellContents($row, $column, $value);
        $table->updateCellAttributes(
            $row,
            $column,
            $value
        );
        $column++;
    }
    $row++;
}

$table->display();

echo '</form>';

$html = '<div class="btn-toolbar">';
$html .= '<div class="btn-group">';
$html .= '<a
        class="btn btn-default"
        href="?'.$url.'selectall=1"
        onclick="javascript: setCheckbox(true, \''.$tableId.'\'); return false;">
        '.get_lang('SelectAll').'</a>';
$html .= '<a
            class="btn btn-default"
            href="?'.$url.'"
            onclick="javascript: setCheckbox(false, \''.$tableId.'\'); return false;">
            '.get_lang('UnSelectAll').'</a> ';
$html .= '</div>';
$html .= '<div class="btn-group">
            <button class="btn btn-default" onclick="javascript:return false;">'.get_lang('Actions').'</button>
            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                <span class="caret"></span>
            </button>
            <ul class="dropdown-menu">';

$actionLabel = get_lang('ReUseACopyInCurrentTest');
$actions = ['clone' => get_lang('ReUseACopyInCurrentTest')];
if ($selected_course == api_get_course_int_id()) {
    $actions = ['reuse' => get_lang('ReuseQuestion')];
}

foreach ($actions as $action => &$label) {
    $html .= '<li>
            <a
                data-action ="'.$action.'"
                href="#"
                onclick="javascript:action_click(this, \''.$tableId.'\');">'.
                $label.'
            </a>
          </li>';
}
$html .= '</ul>';
$html .= '</div>'; //btn-group
$html .= '</div>'; //toolbar

echo $html;

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
 * @param int    $fromExercise
 * @param int    $questionId
 * @param int    $questionType
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
        if (!empty($sessionId) && -1 != $sessionId) {
            $sessionIcon = ' '.Display::return_icon('star.png', get_lang('Session'));
        }
        $exerciseId = (int) $exerciseId;
        $questionId = (int) $questionId;
        $questionType = (int) $questionType;
        $fromExercise = (int) $fromExercise;

        $result = Display::url(
            $questionName.$sessionIcon,
            'admin.php?'.api_get_cidreq().
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

            if (isQuestionInActiveQuiz($in_questionid)) {
                $res = Display::return_icon('delete_na.png', get_lang('ThisQuestionExistsInAnotherExercisesWarning'));
            } else {
                $res = "<a href='".api_get_self()."?".
                    api_get_cidreq().$getParams."&delete=$in_questionid' onclick='return confirm_your_choice()'>";
                $res .= Display::return_icon('delete.png', get_lang('Delete'));
                $res .= "</a>";
            }

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
                $res = "<a href='".api_get_self().'?'.
                    api_get_cidreq().$getParams."&recup=$in_questionid&fromExercise=$from_exercise'>";
                $res .= Display::return_icon('view_more_stats.gif', get_lang('InsertALinkToThisQuestionInTheExercise'));
                $res .= '</a>';
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
 * @param int $questionId
 *
 * @return bool
 */
function isQuestionInActiveQuiz($questionId)
{
    $tblQuizRelQuestion = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
    $tblQuiz = Database::get_course_table(TABLE_QUIZ_TEST);

    $questionId = (int) $questionId;

    if (empty($questionId)) {
        return false;
    }

    $result = Database::fetch_assoc(
        Database::query(
            "SELECT COUNT(qq.question_id) count
                    FROM $tblQuizRelQuestion qq
                    INNER JOIN $tblQuiz ex
                    ON qq.exercice_id = ex.iid
                    WHERE
                        ex.active = 1 AND
                        qq.question_id = $questionId"
        )
    );

    return $result['count'] > 0;
}

/**
 * Return the icon for the question type.
 *
 * @author hubert.borderiou 13-10-2011
 */
function get_question_type_for_question($in_selectedcourse, $in_questionid)
{
    $courseInfo = api_get_course_info_by_id($in_selectedcourse);
    $question = Question::read($in_questionid, $courseInfo);
    $questionType = null;
    if (!empty($question)) {
        $typeImg = $question->getTypePicture();
        $typeExpl = $question->getExplanation();

        $questionType = Display::tag('div', Display::return_icon($typeImg, $typeExpl, [], 32), []);
    }

    return $questionType;
}

<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField as ExtraFieldEntity;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use ChamiloSession as Session;
use Knp\Component\Pager\Paginator;
use Chamilo\CoreBundle\Component\Utils\ActionIcon;
use Chamilo\CoreBundle\Component\Utils\ObjectIcon;

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

$nameTools = get_lang('Recycle existing questions');
$interbreadcrumb[] = ['url' => 'exercise.php?'.api_get_cidreq(), 'name' => get_lang('Tests')];

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
    if (!isset($_POST['recup']) && 0 != $question_copy && isset($fromExercise)) {
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
        $displayMessage = get_lang('Item added');
    }

    // Deletes a question from the database and all exercises
    if ($delete) {
        $limitTeacherAccess = ('true' === api_get_setting('exercise.limit_exercise_teacher_access'));
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
        Display::addFlash(Display::return_message(get_lang('Item added'), 'success'));
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
        'name' => get_lang('Assessments'),
    ];
}

// if admin of course
if (!$is_allowedToEdit) {
    api_not_allowed(true);
}

$confirmYourChoice = addslashes(api_htmlentities(get_lang('Please confirm your choice'), ENT_QUOTES));
$htmlHeadXtra[] = "
<script>
    document.addEventListener('DOMContentLoaded', function() {
      var actionButton = document.querySelector('.action-button');
      var dropdownMenu = document.getElementById('action-dropdown');

      function toggleDropdown(event) {
        event.preventDefault();
        var isDisplayed = dropdownMenu.style.display === 'block';
        dropdownMenu.style.display = isDisplayed ? 'none' : 'block';
      }

      actionButton.addEventListener('click', toggleDropdown);
      document.addEventListener('click', function(event) {
        if (!dropdownMenu.contains(event.target) && !actionButton.contains(event.target)) {
          dropdownMenu.style.display = 'none';
        }
      });
    });

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

// Form
$sessionList = SessionManager::get_sessions_by_user(api_get_user_id(), api_is_platform_admin());
$session_select_list = ['-1' => get_lang('Select')];
foreach ($sessionList as $item) {
    $session_select_list[$item['session_id']] = $item['session_name'];
}

// Course list, get course list of session, or for course where user is admin
$course_list = [];

// Course list, get course list of session, or for course where user is admin
if (!empty($session_id) && '-1' != $session_id && !empty($sessionList)) {
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

if (empty($selected_course) || '-1' == $selected_course) {
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
    $selected_course,
    (empty($session_id) ? 0 : $session_id),
    false
);

if (1 == $exercise_id_changed) {
    reset_menu_lvl_type();
}

// Exercise List
$my_exercise_list = [];
$my_exercise_list['0'] = get_lang('All tests');
$my_exercise_list['-1'] = get_lang('Orphan questions');
$titleSavedAsHtml = ('true' === api_get_setting('editor.save_titles_as_html'));
if (is_array($exercise_list)) {
    foreach ($exercise_list as $row) {
        $my_exercise_list[$row['iid']] = '';
        if ($row['iid'] == $fromExercise && $selected_course == api_get_course_int_id()) {
            $my_exercise_list[$row['iid']] = '>&nbsp;&nbsp;&nbsp;&nbsp;';
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

// Answer type list
$form = new FormValidator('question_pool', 'GET', $url);
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
        get_lang('Questions category'),
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

if (isset($fromExercise) && $fromExercise > 0) {
    $titleAdd = get_lang('Add question to test');
} else {
    $titleAdd = get_lang('Manage all questions');
}

$form->addHeader($nameTools.' - '.$titleAdd);

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

        switch ($field['value_type']) {
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

        if ('from' === $queryType) {
            $from .= ", extra_field_values efv_$variable, extra_field ef_$variable";
            $where .= "AND (
                    qu.iid = efv_$variable.item_id
                    AND efv_$variable.field_id = ef_$variable.id
                    AND ef_$variable.item_type = ".ExtraFieldEntity::QUESTION_FIELD_TYPE."
                    AND ef_$variable.variable = '$variable'
                    AND efv_$variable.field_value = '$value'
                )";
        } elseif ('join' === $queryType) {
            $from .= " INNER JOIN extra_field_values efv_$variable ON qu.iid = efv_$variable.item_id
                INNER JOIN extra_field ef_$variable ON efv_$variable.field_id = ef_$variable.id";
            $where .= "AND (
                    ef_$variable.item_type = ".ExtraFieldEntity::QUESTION_FIELD_TYPE."
                    AND ef_$variable.variable = '$variable'
                    AND efv_$variable.field_value = '$value'
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
    $selectedCourse,
    $sessionId,
    $exerciseLevel,
    $answerType,
    $questionId,
    $description,
    $fromExercise = 0,
    $formValues = []
) {
    $entityManager = Database::getManager();
    $qb = $entityManager->createQueryBuilder();

    $qb->select('qq', 'IDENTITY(qr.quiz) as exerciseId')
        ->from(CQuizQuestion::class, 'qq')
        ->leftJoin('qq.relQuizzes', 'qr');

    if ($exerciseId > 0) {
        $qb->andWhere('qr.quiz = :exerciseId')
            ->setParameter('exerciseId', $exerciseId);
    } elseif ($exerciseId == -1) {
        $qb->andWhere($qb->expr()->isNull('qr.quiz'));
    }

    if ($courseCategoryId > 0) {
        $qb->join('qq.categories', 'qc')
            ->andWhere('qc.id = :categoryId')
            ->setParameter('categoryId', $courseCategoryId);
    }

    if ($exerciseLevel !== null && $exerciseLevel != -1) {
        $qb->andWhere('qq.level = :level')
            ->setParameter('level', $exerciseLevel);
    }

    if ($answerType !== null && $answerType > 0) {
        $qb->andWhere('qq.type = :type')
            ->setParameter('type', $answerType);
    }

    if (!empty($questionId)) {
        $qb->andWhere('qq.iid = :questionId')
            ->setParameter('questionId', $questionId);
    }

    if (!empty($description)) {
        $qb->andWhere('qq.description LIKE :description')
            ->setParameter('description', '%' . $description . '%');
    }

    if (!empty($fromExercise)) {
        $subQb = $entityManager->createQueryBuilder();
        $subQb->select('IDENTITY(sq.question)')
            ->from(CQuizRelQuestion::class, 'sq')
            ->where('sq.quiz = :fromExercise');

        $qb->andWhere($qb->expr()->notIn('qq.iid', $subQb->getDQL()))
            ->setParameter('fromExercise', $fromExercise);
    }

    if ($getCount) {
        $qb->select('COUNT(qq.iid)');
        try {
            return (int) $qb->getQuery()->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return 0;
        }
    } else {
        $qb->select('qq.iid as id', 'qq.question', 'qq.type', 'qq.level', 'IDENTITY(qr.quiz) as exerciseId')
            ->setFirstResult($start)
            ->setMaxResults($length);

        $results = $qb->getQuery()->getArrayResult();

        $questions = [];
        foreach ($results as $result) {
            $question = [
                'iid' => $result['id'],
                'question' => $result['question'],
                'type' => $result['type'],
                'level' => $result['level'],
                'exerciseId' => $result['exerciseId'],
            ];
            $questions[] = $question;
        }

        return $questions;
    }
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

$length = intval(api_get_setting('exercise.question_pagination_length'));
if (empty($length)) {
    $length = 20;
}
$page = intval($page);
$start = ($page - 1) * $length;

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

$paginator = new Paginator(Container::$container->get('event_dispatcher'));
$pagination = $paginator->paginate($mainQuestionList, $page, $length);

$pagination->setTotalItemCount($nbrQuestions);
$pagination->setItemNumberPerPage($length);
$pagination->setCurrentPageNumber($page);
$pagination->renderer = function ($data) use ($url) {
    $render = '<nav aria-label="Page navigation" class="question-pool-pagination-nav">';
    $render .= '<ul class="pagination">';

    $link = function($page, $text, $label, $isActive = false) use ($url) {
        $activeClass = $isActive ? ' active' : '';
        return '<li class="page-item'.$activeClass.'"><a class="page-link" href="'.$url.'&page='.$page.'" aria-label="'.$label.'">'.$text.'</a></li>';
    };

    if ($data['current'] > 1) {
        $render .= $link(1, '&laquo;&laquo;', 'First');
        $prevPage = $data['current'] - 1;
        $render .= $link($prevPage, '&laquo;', 'Previous');
    }

    $startPage = max(1, $data['current'] - 2);
    $endPage = min($data['pageCount'], $data['current'] + 2);
    for ($i = $startPage; $i <= $endPage; $i++) {
        $render .= $link($i, $i, 'Page '.$i, $data['current'] == $i);
    }

    if ($data['current'] < $data['pageCount']) {
        $nextPage = $data['current'] + 1;
        $render .= $link($nextPage, '&raquo;', 'Next');
        $render .= $link($data['pageCount'], '&raquo;&raquo;', 'Last');
    }

    $render .= '</ul></nav>';

    return $render;
};

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
    $actionLabel = get_lang('Re-use in current test');
    $actionIcon1 = get_lang('Must be in a test');
    $actionIcon2 = '';
    // We are not in this course, to messy if we link to the question in another course
    $questionTagA = 0;
    if ($selected_course == api_get_course_int_id()) {
        // NOT IN A TEST - IN THE COURSE
        $actionLabel = get_lang('Edit');
        $actionIcon1 = 'edit';
        $actionIcon2 = 'delete';
        // We are in the course, question title can be a link to the question edit page
        $questionTagA = 1;
    }
} else {
    // IN A TEST - NOT IN THE COURSE
    $actionLabel = get_lang('Re-use a copy inside the current test');
    $actionIcon1 = 'clone';
    $actionIcon2 = '';
    $questionTagA = 0;

    if ($selected_course == api_get_course_int_id()) {
        // IN A TEST - IN THE COURSE
        $actionLabel = get_lang('Re-use in current test');
        $actionIcon1 = 'add';
        $actionIcon2 = '';
        $questionTagA = 1;
    }
}

$data = [];
if (is_array($mainQuestionList)) {
    foreach ($mainQuestionList as $question) {
        $questionId = $question['iid'];
        $row = [];
        // This function checks if the question can be read
        $question_type = get_question_type_for_question($selected_course, $questionId);

        if (empty($question_type)) {
            continue;
        }
        $sessionId = isset($question['session_id']) ? $question['session_id'] : null;
        if (!$objExercise->hasQuestion($question['iid'])) {
            $row[] = Display::input(
                'checkbox',
                'questions[]',
                $questionId,
                ['class' => 'question_checkbox']
            );
        } else {
            $row[] = '';
        }

        $row[] = getLinkForQuestion(
            $questionTagA,
            $fromExercise,
            $questionId,
            $question['type'],
            $question['question'],
            $sessionId,
            $question['exerciseId']
        );

        $row[] = $question_type;
        $row[] = TestCategory::getCategoryNameForQuestion($questionId, $selected_course);
        $row[] = $question['level'];
        $row[] = get_action_icon_for_question(
            $actionIcon1,
            $fromExercise,
                $questionId,
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
            $questionId,
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
    get_lang('Question'),
    get_lang('Type'),
    get_lang('Questions category'),
    get_lang('Difficulty'),
    $actionLabel,
];

Display::display_header($nameTools, 'Exercise');
$actions = '';
if (isset($fromExercise) && $fromExercise > 0) {
    $actions .= '<a href="admin.php?'.api_get_cidreq().'&exerciseId='.$fromExercise.'">'.
        Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Go back to the questions list')).'</a>';
} else {
    $actions .= '<a href="exercise.php?'.api_get_cidreq().'">'.
        Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back to tests list')).'</a>';
    $actions .= '<a href="question_create.php?'.api_get_cidreq().'">'.
        Display::getMdiIcon(ActionIcon::ADD, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('New question')).'</a>';
}
echo Display::toolbarAction('toolbar', [$actions]);

if ('' != $displayMessage) {
    echo Display::return_message($displayMessage, 'confirm');
}

echo $form->display();

echo '<script>$(function () {
        '.$jsForExtraFields['jquery_ready_content'].'
    })</script>';
?>
    <div class="clear"></div>
<?php

echo '<div class="text-center">';
echo $pagination;
echo '</div>';
$tableId = 'question_pool_id';
echo '<form id="'.$tableId.'" method="get" action="'.$url.'">';
echo '<input type="hidden" name="fromExercise" value="'.$fromExercise.'">';
echo '<input type="hidden" name="cidReq" value="'.$_course['real_id'].'">';
echo '<input type="hidden" name="cid" value="'.api_get_course_int_id().'">';
echo '<input type="hidden" name="sid" value="'.api_get_session_id().'">';
echo '<input type="hidden" name="selected_course" value="'.$selected_course.'">';
echo '<input type="hidden" name="course_id" value="'.$selected_course.'">';
echo '<input type="hidden" name="action">';

$table = new HTML_Table(['class' => 'table table-hover table-striped table-bordered data_table'], false);
$row = 0;
$column = 0;
$widths = ['10px', '250px', '50px', '200px', '50px', '100px'];
foreach ($headers as $header) {
    $table->setHeaderContents($row, $column, $header);
    $width = array_key_exists($column, $widths) ? $widths[$column] : 'auto';
    $table->setCellAttributes($row, $column, ['style' => "width:$width;"]);
    $column++;
}

$alignments = ['center', 'left', 'center', 'left', 'center', 'center'];
$row = 1;
foreach ($data as $rowData) {
    $column = 0;
    foreach ($rowData as $value) {
        $table->setCellContents($row, $column, $value);
        if (array_key_exists($column, $alignments)) {
            $alignment = $alignments[$column];
            $table->setCellAttributes(
                $row,
                $column,
                ['style' => "text-align:{$alignment};"]
            );
        }

        $column++;
    }
    $row++;
}
$table->display();
echo '</form>';

$html = '<div class="btn-toolbar question-pool-table-actions">';
$html .= '<div class="btn-group">';
$html .= '<a
        class="btn btn--plain"
        href="?'.$url.'selectall=1"
        onclick="javascript: setCheckbox(true, \''.$tableId.'\'); return false;">
        '.get_lang('Select all').'</a>';
$html .= '<a
            class="btn btn--plain"
            href="?'.$url.'"
            onclick="javascript: setCheckbox(false, \''.$tableId.'\'); return false;">
            '.get_lang('Unselect all').'</a> ';
$html .= '</div>';
$html .= '<div class="btn-group">
            <button class="btn btn--plain action-button">' .get_lang('Actions').'</button>
            <ul class="dropdown-menu" id="action-dropdown" style="display: none;">';

$actionLabel = get_lang('Re-use a copy inside the current test');
$actions = ['clone' => get_lang('Re-use a copy inside the current test')];
if ($selected_course == api_get_course_int_id()) {
    $actions = ['reuse' => get_lang('Re-use in current testQuestion')];
}

foreach ($actions as $action => $label) {
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
            $sessionIcon = ' '.Display::getMdiIcon(ObjectIcon::STAR, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Session'));
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
    $limitTeacherAccess = ('true' === api_get_setting('exercise.limit_exercise_teacher_access'));
    $getParams = "&selected_course=$in_selected_course&courseCategoryId=$in_courseCategoryId&exerciseId=$in_exercise_id&exerciseLevel=$in_exerciseLevel&answerType=$in_answerType&session_id=$in_session_id";
    $res = '';
    switch ($in_action) {
        case 'delete':
            if ($limitTeacherAccess && !api_is_platform_admin()) {
                break;
            }

            if (isQuestionInActiveQuiz($in_questionid)) {
                $res = Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon-disabled', null, ICON_SIZE_SMALL, get_lang('ThisQuestionExistsInAnotherExercisesWarning'));
            } else {
                $res = "<a href='".api_get_self()."?".
                api_get_cidreq().$getParams."&delete=$in_questionid' onclick='return confirm_your_choice()'>";
                $res .= Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete'));
                $res .= "</a>";
            }

            break;
        case 'edit':
            $res = getLinkForQuestion(
                1,
                $from_exercise,
                $in_questionid,
                $in_questiontype,
                Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')),
                $in_session_id,
                $in_exercise_id
            );

            break;
        case 'add':
            $res = '-';
            if (!$myObjEx->hasQuestion($in_questionid)) {
                $res = "<a href='".api_get_self().'?'.
                    api_get_cidreq().$getParams."&recup=$in_questionid&fromExercise=$from_exercise'>";
                $res .= Display::getMdiIcon(ActionIcon::VIEW_DETAILS, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('InsertALinkToThisQuestionInTheExercise'));
                $res .= '</a>';
            }

            break;
        case 'clone':
            $url = api_get_self().'?'.api_get_cidreq().$getParams.
                "&question_copy=$in_questionid&course_id=$in_selected_course&fromExercise=$from_exercise";
            $res = Display::url(
                Display::getMdiIcon(ActionIcon::COPY_CONTENT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Re-use a copy inside the current test')),
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
                    INNER JOIN $tblQuiz q
                    ON qq.quiz_id = q.iid
                    WHERE
                        q.active = 1 AND
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

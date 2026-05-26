<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField as ExtraFieldEntity;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use ChamiloSession as Session;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
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

// By default when we go to the page for the first time, we select the current course.
// NOTE: legacy behavior keeps -1 as "Select", so we normalize inside getQuestions().
if (!isset($_GET['selected_course']) && !isset($_GET['exerciseId'])) {
    $selected_course = -1;
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
            $new_question_obj->addToList($fromExercise, true);
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
        var isDisplayed = dropdownMenu && dropdownMenu.style.display === 'block';
        if (dropdownMenu) dropdownMenu.style.display = isDisplayed ? 'none' : 'block';
      }

      if (actionButton) {
        actionButton.addEventListener('click', toggleDropdown);
        document.addEventListener('click', function(event) {
          if (dropdownMenu && !dropdownMenu.contains(event.target) && !actionButton.contains(event.target)) {
            dropdownMenu.style.display = 'none';
          }
        });
      }
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
                        // Reading the source question
                        $old_question_obj = Question::read($questionId, $origin_course_info);
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
    $courseItemId = $item['id'];
    $courseInfo = api_get_course_info_by_id($courseItemId);
    $course_select_list[$courseItemId] = '';
    if ($courseItemId == api_get_course_int_id()) {
        $course_select_list[$courseItemId] = '>&nbsp;&nbsp;&nbsp;&nbsp;';
    }
    $course_select_list[$courseItemId] .= $courseInfo['title'];
}

if (empty($selected_course) || '-1' == $selected_course) {
    $course_info = api_get_course_info();
    // no course selected, reset menu test / difficulty / answer type
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
    $feedbackType = $objExercise->getFeedbackType();

    if (in_array($feedbackType, [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP])) {
        // Keep original base: only these two were allowed before.
        $allowedTypes = [
            UNIQUE_ANSWER        => true,
            HOT_SPOT_DELINEATION => true,
        ];

        // Start from all known types.
        $allTypes = $question_list;

        // Exclude open question types (no immediate feedback).
        unset($allTypes[FREE_ANSWER]);
        unset($allTypes[ORAL_EXPRESSION]);
        unset($allTypes[ANNOTATION]);
        unset($allTypes[MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY]);
        unset($allTypes[UPLOAD_ANSWER]);
        unset($allTypes[ANSWER_IN_OFFICE_DOC]);
        unset($allTypes[PAGE_BREAK]);

        // Append remaining non-open types (do not override base ones).
        foreach ($allTypes as $key => $item) {
            if (!isset($allowedTypes[$key])) {
                $allowedTypes[$key] = true;
            }
        }

        // Build the final select list in a stable order.
        foreach ($allowedTypes as $key => $_) {
            if (isset($question_list[$key])) {
                $item = $question_list[$key];
                $labelKey = $item[2] ?? $item[1];
                $new_question_list[$key] = get_lang($labelKey);
            }
        }
    } else {
        // Default behavior for non-adaptative / other feedback types:
        // keep all question types except HOT_SPOT_DELINEATION.
        foreach ($question_list as $key => $item) {
            if (HOT_SPOT_DELINEATION == $key) {
                continue;
            }
            $labelKey = $item[2] ?? $item[1];
            $new_question_list[$key] = get_lang($labelKey);
        }
    }
}

// Answer type list
$form = new FormValidator('question_pool', 'GET', $url);
$form->addHidden('cidReq', $_course['real_id']);
$form->addHidden('cid', api_get_course_int_id());
$form->addHidden('sid', api_get_session_id());
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
        get_lang('Test'),
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
        get_lang('Answer type'),
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

/**
 * Apply "active" constraints on a ResourceLink alias.
 *
 * This mirrors the UI/ExerciseLib behavior:
 * - Ignore soft-deleted links
 * - Ignore ended links (endVisibilityAt IS NULL)
 * - Keep only standard visibilities (0 or 2)
 * - When a session is selected, include both course-level links (session IS NULL)
 *   and session links (session = :sessionId)
 */
function applyActiveResourceLinkConstraints(QueryBuilder $qb, string $alias, int $sessionId, bool $includeCourseWhenSessionSelected = true): void
{
    $qb->andWhere($alias.'.deletedAt IS NULL');
    $qb->andWhere($alias.'.endVisibilityAt IS NULL');
    $qb->andWhere($alias.'.visibility IN (0,2)');

    if ($sessionId > 0) {
        if ($includeCourseWhenSessionSelected) {
            $qb->andWhere('(IDENTITY('.$alias.'.session) = :sessionId OR '.$alias.'.session IS NULL)');
        } else {
            $qb->andWhere('IDENTITY('.$alias.'.session) = :sessionId');
        }
    } else {
        $qb->andWhere($alias.'.session IS NULL');
    }
}

/**
 * Fetch questions using Doctrine (C2).
 *
 * Important UI rule:
 * - We do NOT exclude questions already linked to the current quiz (fromExercise).
 *   The UI already disables checkboxes/actions through $objExercise->hasQuestion().
 *
 * Soft delete rule:
 * - A question is considered linked to a quiz ONLY if that quiz is still "active"
 *   in this context (ResourceLink.deletedAt IS NULL).
 */
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

    // Normalize inputs
    $selectedCourse = (int) $selectedCourse;
    $sessionId = (int) $sessionId;
    $exerciseId = (int) $exerciseId;
    $fromExercise = (int) $fromExercise;

    if ($sessionId < 0) {
        $sessionId = 0;
    }

    // If "Select" (-1/0/null) is used, default to the current course to avoid empty results.
    if ($selectedCourse <= 0) {
        $selectedCourse = (int) api_get_course_int_id();
    }

    $qb = $entityManager->createQueryBuilder();
    $qb->from(CQuizQuestion::class, 'qq');

    // Parameters used by main query and all subqueries
    $qb->setParameter('courseId', $selectedCourse);
    if ($sessionId > 0) {
        $qb->setParameter('sessionId', $sessionId);
    }

    // ---------------------------------------------------------------------
    // COURSE SCOPE (C2):
    // - Primary: question's own ResourceNode -> ResourceLink -> course
    // - Secondary: question used in a quiz belonging to the course
    // ---------------------------------------------------------------------
    $questionMeta = $entityManager->getClassMetadata(CQuizQuestion::class);

    $existsViaQuiz = $entityManager->createQueryBuilder();
    $existsViaQuiz->select('1')
        ->from(CQuizRelQuestion::class, 'rq')
        ->innerJoin('rq.quiz', 'q')
        ->innerJoin('q.resourceNode', 'qRN')
        ->innerJoin('qRN.resourceLinks', 'qRL')
        ->where('rq.question = qq')
        ->andWhere('IDENTITY(qRL.course) = :courseId');
    applyActiveResourceLinkConstraints($existsViaQuiz, 'qRL', $sessionId, true);

    if ($questionMeta->hasAssociation('resourceNode')) {
        $qb->leftJoin('qq.resourceNode', 'rn');

        $existsQuestionLink = $entityManager->createQueryBuilder();
        $existsQuestionLink->select('1')
            ->from(\Chamilo\CoreBundle\Entity\ResourceLink::class, 'rl')
            ->where('rl.resourceNode = rn')
            ->andWhere('IDENTITY(rl.course) = :courseId');
        applyActiveResourceLinkConstraints($existsQuestionLink, 'rl', $sessionId, true);

        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->exists($existsQuestionLink->getDQL()),
                $qb->expr()->exists($existsViaQuiz->getDQL())
            )
        );
    } else {
        // Fallback: only include questions used in quizzes belonging to the course.
        // Orphan questions without quiz links cannot be discovered in this scenario.
        $qb->andWhere($qb->expr()->exists($existsViaQuiz->getDQL()));
    }

    // ---------------------------------------------------------------------
    // FILTERS
    // ---------------------------------------------------------------------
    if ($courseCategoryId > 0) {
        $qb->join('qq.categories', 'qc')
            ->andWhere('qc.id = :categoryId')
            ->setParameter('categoryId', (int) $courseCategoryId);
    }

    if ($exerciseLevel !== null && (int) $exerciseLevel !== -1) {
        $qb->andWhere('qq.level = :level')
            ->setParameter('level', (int) $exerciseLevel);
    }

    if ($answerType !== null && (int) $answerType > 0) {
        $qb->andWhere('qq.type = :type')
            ->setParameter('type', (int) $answerType);
    }

    if (!empty($questionId)) {
        $qb->andWhere('qq.iid = :questionId')
            ->setParameter('questionId', (int) $questionId);
    }

    if (!empty($description)) {
        $qb->andWhere('qq.description LIKE :description')
            ->setParameter('description', '%'.$description.'%');
    }

    // If a specific quiz is selected, keep only questions in that quiz,
    // but only if the quiz is still active in this context (not soft-deleted link).
    if ($exerciseId > 0) {
        $inQuiz = $entityManager->createQueryBuilder();
        $inQuiz->select('1')
            ->from(CQuizRelQuestion::class, 'rqq')
            ->innerJoin('rqq.quiz', 'qSel')
            ->innerJoin('qSel.resourceNode', 'qSelRN')
            ->innerJoin('qSelRN.resourceLinks', 'qSelRL')
            ->where('IDENTITY(rqq.quiz) = :exerciseId')
            ->andWhere('rqq.question = qq')
            ->andWhere('IDENTITY(qSelRL.course) = :courseId');
        applyActiveResourceLinkConstraints($inQuiz, 'qSelRL', $sessionId, true);

        $qb->andWhere($qb->expr()->exists($inQuiz->getDQL()))
            ->setParameter('exerciseId', $exerciseId);
    } elseif ($exerciseId === -1) {
        // Orphan: not linked to any ACTIVE quiz in this context.
        // A quiz with a soft-deleted link must NOT prevent a question from being orphan.
        $hasAnyActive = $entityManager->createQueryBuilder();
        $hasAnyActive->select('1')
            ->from(CQuizRelQuestion::class, 'rqq2')
            ->innerJoin('rqq2.quiz', 'q2')
            ->innerJoin('q2.resourceNode', 'q2rn')
            ->innerJoin('q2rn.resourceLinks', 'q2rl')
            ->where('rqq2.question = qq')
            ->andWhere('IDENTITY(q2rl.course) = :courseId');
        applyActiveResourceLinkConstraints($hasAnyActive, 'q2rl', $sessionId, true);

        $qb->andWhere($qb->expr()->not($qb->expr()->exists($hasAnyActive->getDQL())));
    }

    // ---------------------------------------------------------------------
    // EXECUTE
    // ---------------------------------------------------------------------
    if ($getCount) {
        $qb->select('COUNT(DISTINCT qq.iid)');

        try {
            return (int) $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    $qb->select('qq.iid as id', 'qq.question', 'qq.type', 'qq.level')
        ->setFirstResult((int) $start)
        ->setMaxResults((int) $length);

    try {
        $results = $qb->getQuery()->getArrayResult();
    } catch (\Throwable $e) {
        return [];
    }

    $questions = [];
    foreach ($results as $result) {
        $questions[] = [
            'iid' => $result['id'],
            'question' => $result['question'],
            'type' => $result['type'],
            'level' => $result['level'],
            // Keep expected shape used later
            'exerciseId' => $exerciseId > 0 ? $exerciseId : 0,
        ];
    }

    return $questions;
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

$length = (int) api_get_setting('exercise.question_pagination_length');
if (empty($length)) {
    $length = 20;
}
$page = (int) $page;
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

    $link = function ($page, $text, $label, $isActive = false) use ($url) {
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
    // OUTSIDE a test → show edit/delete column
    $actionLabel = get_lang('Actions');
    $actionIcon1 = 'edit';
    $actionIcon2 = 'delete';
    $questionTagA = 1;
} else {
    // INSIDE a test → show reuse options
    $actionLabel = get_lang('Re-use a copy inside the current test');
    $actionIcon1 = 'clone';
    $actionIcon2 = 'add';
    $questionTagA = 0;

    if ($selected_course == api_get_course_int_id()) {
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
            // Keep legacy behavior: skip rows that cannot build the type icon safely.
            continue;
        }

        $sessionId = $question['session_id'] ?? null;

        if ($fromExercise > 0 && !$objExercise->hasQuestion($question['iid'])) {
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

        $row[] =
            get_action_icon_for_question(
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
        Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, sprintf(get_lang('Back to %s'), get_lang('Test list'))).'</a>';
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

// --- Bulk actions toolbar (only when we are inside a test) ------------------
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

if ($fromExercise > 0) {
    $html .= '<div class="btn-group">
                <button class="btn btn--plain action-button">'.get_lang('Actions').'</button>
                <ul class="dropdown-menu" id="action-dropdown" style="display: none;">';

    $actions = ['clone' => get_lang('Re-use a copy inside the current test')];
    if ($selected_course == api_get_course_int_id()) {
        $actions = ['reuse' => get_lang('Re-use in current test')];
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
}

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

    $exerciseLevel = -1;

    if (!isset($_REQUEST['answerType'])) {
        $answerType = -1;
    }
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
 * Return the <a> html code for delete, add, clone, edit a question.
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
                $res = Display::getMdiIcon(
                    ActionIcon::DELETE,
                    'ch-tool-icon-disabled',
                    null,
                    ICON_SIZE_SMALL,
                    get_lang('This question is used in another exercises. If you continue its edition, the changes will affect all exercises that contain this question.')
                );
            } else {
                $res = "<a href='".api_get_self()."?".
                    api_get_cidreq().$getParams."&delete=$in_questionid' onclick='return confirm_your_choice()'>";
                $res .= Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete'));
                $res .= "</a>";
            }

            break;

        case 'edit':
            if (isQuestionInActiveQuiz($in_questionid)) {
                $res = Display::getMdiIcon(
                    ActionIcon::EDIT,
                    'ch-tool-icon-disabled',
                    null,
                    ICON_SIZE_SMALL,
                    get_lang('This question belongs to a test. Edit it from inside the test or filter for orphan questions only.')
                );
                break;
            }

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
            if ($from_exercise > 0 && !$myObjEx->hasQuestion($in_questionid)) {
                $res = "<a href='".api_get_self().'?'.
                    api_get_cidreq().$getParams."&recup=$in_questionid&fromExercise=$from_exercise'>";
                $res .= Display::getMdiIcon(ActionIcon::VIEW_DETAILS, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Use this question in the test as a link (not a copy)'));
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
            // When no action is expected, return empty string to keep layout clean.
            $res = '';
            break;
    }

    return $res;
}

/**
 * Checks whether a question is used by any ACTIVE quiz in the current context.
 *
 * Soft-delete aware:
 * - A quiz that only exists through a soft-deleted ResourceLink must NOT block question deletion.
 */
function isQuestionInActiveQuiz($questionId)
{
    global $selected_course, $session_id;

    $questionId = (int) $questionId;
    if (empty($questionId)) {
        return false;
    }

    $courseId = (int) $selected_course;
    if ($courseId <= 0) {
        $courseId = (int) api_get_course_int_id();
    }

    $sessionId = (int) $session_id;
    if ($sessionId < 0) {
        $sessionId = 0;
    }

    try {
        $entityManager = Database::getManager();

        $qb = $entityManager->createQueryBuilder();
        $qb->select('COUNT(DISTINCT q.iid)')
            ->from(CQuizRelQuestion::class, 'rqq')
            ->innerJoin('rqq.quiz', 'q')
            ->innerJoin('q.resourceNode', 'rn')
            ->innerJoin('rn.resourceLinks', 'rl')
            ->where('rqq.question = :questionId')
            ->andWhere('IDENTITY(rl.course) = :courseId')
            ->setParameter('questionId', $questionId)
            ->setParameter('courseId', $courseId);

        // Soft-delete aware "active link" rules.
        // NOTE: If you want session-only scope, change last param to false.
        applyActiveResourceLinkConstraints($qb, 'rl', $sessionId, true);

        if ($sessionId > 0) {
            $qb->setParameter('sessionId', $sessionId);
        }

        $count = (int) $qb->getQuery()->getSingleScalarResult();

        return $count > 0;
    } catch (\Throwable $e) {
        error_log('[question_pool] isQuestionInActiveQuiz failed: '.$e->getMessage());
        // Fail-safe: keep legacy safe behavior (treat as used).
        return true;
    }
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

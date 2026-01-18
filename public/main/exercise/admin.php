<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use ChamiloSession as Session;

/**
 * Exercise administration
 * This script allows to manage (create, modify) an exercise and its questions.
 *
 * Following scripts are includes for a best code understanding :
 *
 * - exercise.class.php : for the creation of an Exercise object
 * - question.class.php : for the creation of a Question object
 * - answer.class.php : for the creation of an Answer object
 * - exercise.lib.php : functions used in the exercise tool
 * - exercise_admin.inc.php : management of the exercise
 * - question_admin.inc.php : management of a question (statement & answers)
 * - question_list_admin.inc.php : management of the question list
 *
 * Main variables used in this script :
 *
 * - $objAnswer : answer object
 * - $exerciseId : the exercise ID
 * - $picturePath : the path of question pictures
 * - $newQuestion : ask to create a new question
 * - $modifyQuestion : ID of the question to modify
 * - $editQuestion : ID of the question to edit
 * - $submitQuestion : ask to save question modifications
 * - $cancelQuestion : ask to cancel question modifications
 * - $deleteQuestion : ID of the question to delete
 * - $moveUp : ID of the question to move up
 * - $moveDown : ID of the question to move down
 * - $modifyExercise : ID of the exercise to modify
 * - $submitExercise : ask to save exercise modifications
 * - $cancelExercise : ask to cancel exercise modifications
 * - $modifyAnswers : ID of the question which we want to modify answers for
 * - $cancelAnswers : ask to cancel answer modifications
 * - $buttonBack : ask to go back to the previous page in answers of type "Fill in blanks"
 *
 * @author Olivier Brouckaert
 * Modified by Hubert Borderiou 21-10-2011 Question by category
 */
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_QUIZ;
$this_section = SECTION_COURSES;

if (isset($_GET['r']) && 1 == $_GET['r']) {
    Exercise::cleanSessionVariables();
}

// Access control
api_protect_course_script(true);

$is_allowedToEdit = api_is_allowed_to_edit(null, true, false, false);
$sessionId = api_get_session_id();
$studentViewActive = api_is_student_view_active();
$showPagination = 'true' === api_get_setting('exercise.show_question_pagination');

if (!$is_allowedToEdit) {
    api_not_allowed(true);
}

$exerciseId = isset($_GET['exerciseId']) ? (int) $_GET['exerciseId'] : 0;
if (0 === $exerciseId && isset($_POST['exerciseId'])) {
    $exerciseId = (int) $_POST['exerciseId'];
}

$newQuestion = $_GET['newQuestion'] ?? 0;
$modifyAnswers = $_GET['modifyAnswers'] ?? 0;
$editQuestion = $_GET['editQuestion'] ?? 0;
$page = isset($_REQUEST['page']) ? max(1, (int) $_REQUEST['page']) : 1;
$modifyQuestion = $_GET['modifyQuestion'] ?? 0;
$deleteQuestion = $_GET['deleteQuestion'] ?? 0;
$cloneQuestion = $_REQUEST['clone_question'] ?? 0;

if (empty($questionId)) {
    $questionId = Session::read('questionId');
}
if (empty($modifyExercise)) {
    $modifyExercise = $_GET['modifyExercise'] ?? null;
}

$fromExercise = $fromExercise ?? null;
$cancelExercise = $cancelExercise ?? null;
$cancelAnswers = $cancelAnswers ?? null;
$modifyIn = $modifyIn ?? null;
$cancelQuestion = $cancelQuestion ?? null;

/* Cleaning all incomplete attempts of the admin/teacher to avoid weird problems
   when changing the exercise settings, number of questions, etc */
Event::delete_all_incomplete_attempts(
    api_get_user_id(),
    $exerciseId,
    api_get_course_int_id(),
    api_get_session_id()
);

// get from session
$objExercise = Session::read('objExercise');
$objQuestion = Session::read('objQuestion');

/**
 * IMPORTANT:
 * admin.php must allow editing/creating questions without an exercise context.
 * This is required by the global question bank (and question pool) use-case where
 * exerciseId=0 but editQuestion/newQuestion is provided.
 *
 * Without this, the script redirects to exercise.php because objExercise is empty,
 * which makes the behavior depend on an existing session state.
 */
$isStandaloneQuestionFlow = (0 === (int) $exerciseId) && (
        !empty($editQuestion) || !empty($newQuestion)
    );

if ($isStandaloneQuestionFlow && !($objExercise instanceof Exercise)) {
    // Create a minimal Exercise context to avoid redirecting to the test list.
    $objExercise = new Exercise();
    $objExercise->course_id = api_get_course_int_id();
    $objExercise->course = api_get_course_info();
    $objExercise->sessionId = $sessionId;
    Session::write('objExercise', $objExercise);
}

if (isset($_REQUEST['convertAnswer'])) {
    $objQuestion = $objQuestion->swapSimpleAnswerTypes();
    Session::write('objQuestion', $objQuestion);
}

$objAnswer = Session::read('objAnswer');
$_course = api_get_course_info();

// tables used in the exercise tool.
if (!empty($_GET['action']) && 'exportqti2' === $_GET['action'] && !empty($_GET['questionId'])) {
    require_once 'export/qti2/qti2_export.php';
    $export = export_question_qti($_GET['questionId'], true);
    $qid = (int) $_GET['questionId'];
    $name = 'qti2_export_'.$qid.'.zip';
    $zip = api_create_zip($name);
    $zip->addFile("qti2export_$qid.xml", $export);
    $zip->finish();
    exit;
}

// Exercise object creation.
if (!($objExercise instanceof Exercise)) {
    // creation of a new exercise if wrong or not specified exercise ID
    if ($exerciseId) {
        $objExercise = new Exercise();
        $parseQuestionList = $showPagination > 0 ? false : true;
        if ($editQuestion) {
            $parseQuestionList = false;
            $showPagination = true;
        }
        $objExercise->read($exerciseId, $parseQuestionList);
        Session::write('objExercise', $objExercise);
    }
}

// Exercise can be edited in their course.
if (empty($objExercise)) {
    Session::erase('objExercise');
    header('Location: '.api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq());
    exit;
}

// Exercise can be edited in their course.
if ($objExercise->sessionId != $sessionId) {
    api_not_allowed(true);
}

// doesn't select the exercise ID if we come from the question pool / global bank
// (avoid forcing modifyExercise='yes' when exerciseId=0)
if ($exerciseId > 0 && !$fromExercise) {
    // gets the right exercise ID, and if 0 creates a new exercise
    if (!$exerciseId = $objExercise->getId()) {
        $modifyExercise = 'yes';
    }
}

$nbrQuestions = $objExercise->getQuestionCount();

// Question object creation.
if ($editQuestion || $newQuestion || $modifyQuestion || $modifyAnswers) {
    if ($editQuestion || $newQuestion) {
        // reads question data
        if ($editQuestion) {
            // question not found
            if (!$objQuestion = Question::read($editQuestion)) {
                api_not_allowed(true);
            }
            // saves the object into the session
            Session::write('objQuestion', $objQuestion);
        }
    }

    // checks if the object exists
    if (is_object($objQuestion)) {
        // gets the question ID
        $questionId = $objQuestion->getId();
    }
}

// if cancelling an exercise
if ($cancelExercise) {
    // existing exercise
    if ($exerciseId) {
        unset($modifyExercise);
    } else {
        // new exercise
        // goes back to the exercise list
        header('Location: '.api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq());
        exit();
    }
}

// if cancelling question creation/modification
if ($cancelQuestion) {
    // if we are creating a new question from the question pool
    if (!$exerciseId && !$questionId) {
        // goes back to the question pool
        header('Location: question_pool.php?'.api_get_cidreq());
        exit();
    } else {
        // goes back to the question viewing
        $editQuestion = $modifyQuestion;
        unset($newQuestion, $modifyQuestion);
    }
}

if (!empty($cloneQuestion)) {
    $cloneQuestion = (int) $cloneQuestion;

    // Determine destination exercise id (if any)
    $targetExerciseId = 0;
    if (!empty($exerciseId)) {
        $targetExerciseId = (int) $exerciseId;
    } elseif ($objExercise instanceof Exercise && (int) $objExercise->getId() > 0) {
        $targetExerciseId = (int) $objExercise->getId();
    }

    $oldQuestionObj = Question::read($cloneQuestion);
    if (!$oldQuestionObj) {
        Display::addFlash(Display::return_message(get_lang('Question not found'), 'error'));
        exit;
    }

    $oldQuestionObj->question = $oldQuestionObj->question.' - '.get_lang('Copy');

    try {
        $newId = $oldQuestionObj->duplicate(api_get_course_info());
    } catch (Throwable $e) {
        Display::addFlash(Display::return_message(get_lang('Error'), 'error'));
        exit;
    }

    $newId = (int) $newId;
    if ($newId <= 0) {
        Display::addFlash(Display::return_message(get_lang('Error'), 'error'));
        exit;
    }

    $newQuestionObj = Question::read($newId);
    if (!$newQuestionObj) {
        Display::addFlash(Display::return_message(get_lang('Error'), 'error'));
        exit;
    }

    // Attach to exercise only if we have a valid target exercise id
    if ($targetExerciseId > 0) {
        $newQuestionObj->addToList($targetExerciseId);
    }

    // Save category to the destination course (always)
    if (!empty($oldQuestionObj->category)) {
        $newQuestionObj->saveCategory($oldQuestionObj->category);
    }

    // Duplicate answers
    try {
        $newAnswerObj = new Answer($cloneQuestion);
        $newAnswerObj->read();
        $newAnswerObj->duplicate($newQuestionObj);
    } catch (Throwable $e) {
        error_log('[exercise] clone_question exception during answers duplicate(): '.$e->getMessage());
    }

    // Reload exercise if we cloned inside a test
    if ($targetExerciseId > 0) {
        if (!($objExercise instanceof Exercise)) {
            $objExercise = new Exercise();
        }
        $objExercise->read($targetExerciseId, false);
        Session::write('objExercise', $objExercise);
    }

    Display::addFlash(Display::return_message(get_lang('Item copied')));

    // Redirect depending on context
    if ($targetExerciseId > 0) {
        header('Location: admin.php?'.api_get_cidreq().'&exerciseId='.$targetExerciseId.'&page='.$page);
    } else {
        header('Location: question_pool.php?'.api_get_cidreq().'&page='.$page);
    }
    exit;
}

// if cancelling answer creation/modification
if ($cancelAnswers) {
    // goes back to the question viewing
    $editQuestion = $modifyAnswers;
    unset($modifyAnswers);
}

$nameTools = '';
// modifies the query string that is used in the link of tool name
if ($editQuestion || $modifyQuestion || $newQuestion || $modifyAnswers) {
    $nameTools = get_lang('Question / Answer management');
}

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq(),
    'name' => get_lang('Tests'),
];

if (isset($_GET['newQuestion']) || isset($_GET['editQuestion'])) {
    // When editing a question without a real exercise, avoid relying on selectTitle()
    $exerciseTitle = get_lang('Questions');
    $exerciseLink = '#';

    if ($objExercise instanceof Exercise && (int) $objExercise->getId() > 0) {
        $exerciseTitle = $objExercise->selectTitle(true);
        $exerciseLink = api_get_path(WEB_CODE_PATH).'exercise/admin.php?exerciseId='.$objExercise->getId().'&'.api_get_cidreq();
    }

    $interbreadcrumb[] = [
        'url' => $exerciseLink,
        'name' => $exerciseTitle,
    ];
} else {
    $interbreadcrumb[] = [
        'url' => '#',
        'name' => ($objExercise instanceof Exercise) ? $objExercise->selectTitle(true) : get_lang('Tests'),
    ];
}

// shows a link to go back to the question pool
if (!$exerciseId && $nameTools != get_lang('Tests management')) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH)."exercise/question_pool.php?fromExercise=$fromExercise&".api_get_cidreq(),
        'name' => get_lang('Recycle existing questions'),
    ];
}

// if the question is duplicated, disable the link of tool name
if ('thisExercise' === $modifyIn) {
    if (!empty($buttonBack)) {
        $modifyIn = 'allExercises';
    }
}

$htmlHeadXtra[] = api_get_build_js('legacy_exercise.js');

$template = new Template();
$templateName = $template->get_template('exercise/submit.js.tpl');
$htmlHeadXtra[] = $template->fetch($templateName);
$htmlHeadXtra[] = api_get_js('d3/jquery.xcolor.js');
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/css/hotspot.css">';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/js/hotspot.js"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_PATH).'build/libs/select2/css/select2.min.css">';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_PATH).'build/libs/select2/js/select2.min.js"></script>';
$htmlHeadXtra[] = '<script>$(function(){ if ($.fn.select2){ $(".ch-select2").select2({width:"100%"}); } });</script>';

$messageMap = [
    'ExerciseStored' => 'Exercise stored',
    'ItemUpdated' => 'Item updated',
    'ItemAdded' => 'Item added',
];

if (isset($_GET['message']) && isset($messageMap[$_GET['message']])) {
    Display::addFlash(
        Display::return_message(get_lang($messageMap[$_GET['message']]), 'confirmation')
    );
}

Display::display_header($nameTools, 'Exercise');

// If we are in a test
$inATest = isset($exerciseId) && $exerciseId > 0;

if ($inATest) {
    $actions = '';
    if (isset($_GET['hotspotadmin']) || isset($_GET['newQuestion'])) {
        $actions .= '<a
        href="'.api_get_path(WEB_CODE_PATH).'exercise/admin.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'">'.
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Go back to the questions list')).'</a>';
    }

    if (!isset($_GET['hotspotadmin']) && !isset($_GET['newQuestion']) && !isset($_GET['editQuestion'])) {
        $actions .= '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq().'">'.
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, sprintf(get_lang('Back to %s'), get_lang('Test list'))).'</a>';
    }
    $actions .= '<a
        href="'.api_get_path(WEB_CODE_PATH).'exercise/overview.php?'.api_get_cidreq().'&exerciseId='.$objExercise->getId().'&preview=1">'.
        Display::getMdiIcon(ActionIcon::PREVIEW_CONTENT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Preview')).'</a>';

    $actions .= Display::url(
        Display::getMdiIcon('chart-box', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Results and feedback')),
        api_get_path(WEB_CODE_PATH).'exercise/exercise_report.php?'.api_get_cidreq().'&exerciseId='.$objExercise->getId()
    );

    $actions .= '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/exercise_admin.php?'.api_get_cidreq().'&modifyExercise=yes&exerciseId='.$objExercise->getId().'">'.
        Display::getMdiIcon('cog', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Edit test name and settings')).'</a>';

    $maxScoreAllQuestions = 0;
    if (false === $showPagination) {
        $questionList = $objExercise->selectQuestionList(true, $objExercise->random > 0 ? false : true);
        if (!empty($questionList)) {
            foreach ($questionList as $questionItemId) {
                $question = Question::read($questionItemId);
                if ($question) {
                    $maxScoreAllQuestions += $question->selectWeighting();
                }
            }
        }
    }

    echo Display::toolbarAction('toolbar', [$actions]);

    if ($objExercise->added_in_lp()) {
        echo Display::return_message(
            get_lang(
                'This exercise has been included in a learning path, so it cannot be accessed by students directly from here. If you want to put the same exercise available through the exercises tool, please make a copy of the current exercise using the copy icon.'
            ),
            'warning'
        );
    }
    if ($editQuestion && $objQuestion->existsInAnotherExercise()) {
        echo Display::return_message(
            Display::getMdiIcon('alert', 'ch-tool-icon', null, ICON_SIZE_SMALL)
            .get_lang('This question is used in another exercises. If you continue its edition, the changes will affect all exercises that contain this question.'),
            'warning',
            false
        );
    }

    $isHotspotEdit = is_object($objQuestion) && in_array((int) $objQuestion->selectType(), [HOT_SPOT, HOT_SPOT_COMBINATION, HOT_SPOT_DELINEATION], true);
    $alert = '';
    if (false === $showPagination && !$isHotspotEdit) {
        $originalSelectionType = $objExercise->questionSelectionType;
        $objExercise->questionSelectionType = EX_Q_SELECTION_ORDERED;

        // Get the full list of question IDs (as configured in this exercise).
        /** @var int[] $allIds */
        $allIds = (array) $objExercise->selectQuestionList(true, true);

        // Load questions and build a children map to detect "media" containers reliably.
        $questionsById = [];
        $childrenByParent = []; // parentId => [childId, ...]
        foreach ($allIds as $qid) {
            $q = Question::read($qid);
            if (!$q) {
                continue;
            }
            $questionsById[$qid] = $q;

            // some DBs might store parent_id as string/null
            $pid = (int) ($q->parent_id ?? 0);
            if ($pid > 0) {
                if (!isset($childrenByParent[$pid])) {
                    $childrenByParent[$pid] = [];
                }
                $childrenByParent[$pid][] = $qid;
            }
        }

        // is this question a media/container?
        $isMediaContainer = static function ($q, $qid, $childrenByParent) {
            // Case 1: explicit MEDIA_QUESTION type (when constant exists)
            $isMediaType = (defined('MEDIA_QUESTION') && (int) $q->selectType() === MEDIA_QUESTION);

            // Case 2: it is a parent of other questions within this exercise
            $isParent = isset($childrenByParent[$qid]) && !empty($childrenByParent[$qid]);

            // Case 3: some forks expose a method isMedia()
            $hasMethod = method_exists($q, 'isMedia') && $q->isMedia();

            return $isMediaType || $isParent || $hasMethod;
        };

        // Build the effective set of answerable questions (exclude media containers).
        $effectiveQuestions = []; // id => Question
        foreach ($questionsById as $qid => $q) {
            if ($isMediaContainer($q, $qid, $childrenByParent)) {
                continue; // skip media/parent containers
            }
            $effectiveQuestions[$qid] = $q;
        }

        // Compute counts and totals using only effective questions.
        $effectiveNbrQuestions = count($effectiveQuestions);
        $effectiveTotalScore = 0.0;
        foreach ($effectiveQuestions as $q) {
            $effectiveTotalScore += (float) $q->selectWeighting();
        }

        // Restore original selection type.
        $objExercise->questionSelectionType = $originalSelectionType;

        // First line: "X questions, total score Y." (media excluded)
        $alert .= sprintf(
            get_lang('%d questions, for a total score (all questions) of %s.'),
            $effectiveNbrQuestions,
            $effectiveTotalScore
        );

        // If random selection is enabled, display the limit and an informative max total
        if ($objExercise->random > 0) {
            $limit = min((int) $objExercise->random, $effectiveNbrQuestions);

            // Gather weights and take top-N.
            $weights = [];
            foreach ($effectiveQuestions as $id => $q) {
                $weights[$id] = (float) $q->selectWeighting();
            }
            arsort($weights, SORT_NUMERIC); // highest first

            $maxScoreSelected = 0.0;
            $i = 0;
            foreach ($weights as $w) {
                $maxScoreSelected += $w;
                if (++$i >= $limit) {
                    break;
                }
            }

            $alert .= '<br />'.sprintf(
                    get_lang('Only %s questions will be picked randomly following the quiz configuration.'),
                    $limit
                );
            $alert .= sprintf(
                '<br>'.get_lang('Only %d questions will be selected based on the test configuration, for a total score of %s.'),
                $limit,
                $maxScoreSelected
            );
        }

        // Category-based ordered selection: use effective counts/totals as well.
        if ($objExercise->questionSelectionType >= EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_ORDERED) {
            $alert .= sprintf(
                '<br>'.get_lang(
                    'Only %d questions will be selected based on the test configuration, for a total score of %s.'
                ),
                $effectiveNbrQuestions,
                $effectiveTotalScore
            );
        }
    } else {
        // Pagination enabled or hotspot edit: keep a minimal, safe notice for random selection.
        if ($objExercise->random > 0) {
            $limit = min((int) $objExercise->random, (int) $nbrQuestions);
            $alert .= '<br />'.sprintf(
                    get_lang('Only %s questions will be picked randomly following the quiz configuration.'),
                    $limit
                );
        }
    }
    if (!empty($alert)) {
        echo Display::return_message($alert, 'normal', false);
    }
} elseif (isset($_GET['newQuestion'])) {
    // we are in create a new question from question pool not in a test
    $actions = '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/admin.php?'.api_get_cidreq().'">'.
        Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Go back to the questions list')).'</a>';
    echo Display::toolbarAction('toolbar', [$actions]);
} else {
    // If we are in question_pool but not in a test, go back to the questions created in pool
    $actions = '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/question_pool.php?'.api_get_cidreq().'">'.
        Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Go back to the questions list')).
        '</a>';
    echo Display::toolbarAction('toolbar', [$actions]);
}

if ($newQuestion || $editQuestion) {
    // Question management
    $type = isset($_REQUEST['answerType']) ? Security::remove_XSS($_REQUEST['answerType']) : null;
    echo '<input type="hidden" name="Type" value="'.$type.'" />';

    if ('yes' === $newQuestion) {
        $objExercise->edit_exercise_in_lp = true;
        require 'question_admin.inc.php';
    }
    if ($editQuestion) {
        // Question preview if teacher clicked the "switch to student"
        if ($studentViewActive && $is_allowedToEdit) {
            echo '<div class="main-question">';
            echo Display::div($objQuestion->selectTitle(), ['class' => 'question_title']);
            ExerciseLib::showQuestion(
                $objExercise,
                $editQuestion,
                false,
                null,
                null,
                false,
                true,
                false,
                true,
                true
            );
            echo '</div>';
        } else {
            require 'question_admin.inc.php';
            ExerciseLib::showTestsWhereQuestionIsUsed($objQuestion->iid, $objExercise->getId());
        }
    }
}

if (isset($_GET['hotspotadmin'])) {
    require 'hotspot_admin.inc.php';
}

if (isset($_GET['mad_admin'])) {
    $qid = (int) $_GET['mad_admin'];
    $objQuestion = Question::read($qid);
    if (!$objQuestion) {
        api_not_allowed();
    }

    require 'multiple_answer_dropdown_admin.php';
    exit;
}

if (
    !$newQuestion
    && !$modifyQuestion
    && !$editQuestion
    && !isset($_GET['hotspotadmin'])
    && !isset($_GET['mad_admin'])
) {
    require 'question_list_admin.inc.php';
}

// if we are in question authoring, display warning to user is feedback not shown at the end of the test -ref #6619
// this test to display only message in the question authoring page and not in the question list page too
if (EXERCISE_FEEDBACK_TYPE_EXAM == $objExercise->getFeedbackType()) {
    echo Display::return_message(
        get_lang(
            'This test is configured not to display feedback to learners. Comments will not be seen at the end of the test, but may be useful for you, as teacher, when reviewing the question details.'
        ),
        'normal'
    );
}

Session::write('objQuestion', $objQuestion);
Session::write('objAnswer', $objAnswer);
Display::display_footer();

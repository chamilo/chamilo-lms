<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Enums\StateIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use ChamiloSession as Session;

/**
 * Exercise feedback modal (self-evaluation / popup).
 *
 * This script is loaded inside the global modal to show immediate feedback
 * (score, expected answers and navigation links) for a single question.
 * @author Julio Montoya <gugli100@gmail.com>
 */
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_QUIZ;

api_protect_course_script();

require_once api_get_path(LIBRARY_PATH).'geometry.lib.php';

/** @var Exercise $objExercise */
$objExercise = Session::read('objExercise');
$exerciseResult = Session::read('exerciseResult');

if (empty($objExercise)) {
    api_not_allowed();
}

$feedbackType = $objExercise->getFeedbackType();
$exerciseType = $objExercise->type;

// Adaptive mode: direct feedback behaves as an adaptive flow.
$isAdaptative = (EXERCISE_FEEDBACK_TYPE_DIRECT === $feedbackType);

// Only direct or popup feedback are supported here.
if (!in_array($feedbackType, [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP], true)) {
    api_not_allowed();
}

$learnpath_id = (int) ($_REQUEST['learnpath_id'] ?? 0);
$learnpath_item_id = (int) ($_REQUEST['learnpath_item_id'] ?? 0);
$learnpath_item_view_id = (int) ($_REQUEST['learnpath_item_view_id'] ?? 0);
$exerciseId = (int) ($_GET['exerciseId'] ?? 0);
$exeId = (int) (Session::read('exe_id') ?? 0);
$preview = (int) ($_GET['preview'] ?? 0);

$cidreq = api_get_cidreq();

// Base URLs used for navigation from the modal.
$exerciseBaseUrl = api_get_path(WEB_CODE_PATH).'exercise/exercise_submit.php?'
    .$cidreq
    .'&exerciseId='.$exerciseId
    .'&learnpath_id='.$learnpath_id
    .'&learnpath_item_id='.$learnpath_item_id
    .'&learnpath_item_view_id='.$learnpath_item_view_id;

if ($preview) {
    $exerciseBaseUrl .= '&preview='.$preview;
}

$exerciseResultUrl = api_get_path(WEB_CODE_PATH).'exercise/exercise_result.php?'
    .$cidreq
    .'&exe_id='.$exeId
    .'&learnpath_id='.$learnpath_id
    .'&learnpath_item_id='.$learnpath_item_id
    .'&learnpath_item_view_id='.$learnpath_item_view_id;

// Question list and current question index.
$questionList = array_values(Session::read('questionList') ?? []);
$questionNum = max(0, ((int) ($_GET['num'] ?? 1)) - 1);
$questionId = $questionList[$questionNum] ?? null;

$logPrefix = '[exercise_submit_modal] ';

if (!$questionId) {
    exit;
}

// Try to read answer/hotspot from GET first.
$choiceValue = $_GET['choice'][$questionId] ?? ($_GET['choice'] ?? '');
$hotSpot = $_GET['hotspot'][$questionId] ?? ($_GET['hotspot'] ?? '');
$tryAgain = ((int) ($_GET['tryagain'] ?? 0) === 1);
$loaded = (int) ($_GET['loaded'] ?? 0);

// Question entity.
$repo = Container::getQuestionRepository();
/** @var CQuizQuestion|null $question */
$question = $repo->find($questionId);

if (null === $question) {
    exit;
}

// Relationship to get adaptive destinations.
$entityManager = Database::getManager();
/** @var CQuizRelQuestion|null $rel */
$rel = $entityManager
    ->getRepository(CQuizRelQuestion::class)
    ->findOneBy([
        'quiz' => $exerciseId,
        'question' => $questionId,
    ]);

$destinationArray = [];
if ($rel && $rel->getDestination()) {
    $decoded = json_decode($rel->getDestination(), true);
    if (is_array($decoded)) {
        $destinationArray = $decoded;
    }
}

// Normalize failure destinations.
$failure = [];
if (isset($destinationArray['failure'])) {
    $failure = is_array($destinationArray['failure'])
        ? $destinationArray['failure']
        : [$destinationArray['failure']];
}

$allowTryAgain = $tryAgain && !empty($failure) && in_array('repeat', $failure, true);

// If student clicked "Try again", clear previous answer for this question.
if ($allowTryAgain && is_array($exerciseResult)) {
    unset($exerciseResult[$questionId]);
    error_log($logPrefix.'Cleared previous result for questionId='.$questionId);
}

// Reuse stored answer if present.
if (empty($choiceValue) && isset($exerciseResult[$questionId])) {
    $choiceValue = $exerciseResult[$questionId];
}

/**
 * If we still have no choice/hotspot, we need to fetch the answer from the
 * main exercise form (frm_exercise) in the parent page.
 *
 * First pass: redirect to the same script with "loaded=1".
 * Second pass (loaded=1 and still no answer): inject JS that reads the
 * current choice from the form and performs a third GET with the proper
 * parameters. That third GET will finally compute the feedback.
 */
if (
    empty($choiceValue) &&
    empty($hotSpot) &&
    !isset($_GET['loaded'])
) {
    $params = $_REQUEST;
    $params['loaded'] = 1;
    $redirectUrl = $_SERVER['PHP_SELF'].'?'.http_build_query($params);

    header("Location: $redirectUrl");
    exit;
}

if (
    empty($choiceValue) &&
    empty($hotSpot) &&
    isset($_GET['loaded'])
) {
    $url = api_get_path(WEB_CODE_PATH).'exercise/exercise_submit_modal.php?'.api_get_cidreq()
        .'&loaded=1&exerciseId='.$exerciseId
        .'&num='.($questionNum + 1)
        .'&learnpath_id='.$learnpath_id
        .'&learnpath_item_id='.$learnpath_item_id
        .'&learnpath_item_view_id='.$learnpath_item_view_id
        .'&preview='.$preview;

    echo "<script>
    $(document).ready(function() {
        var f = document.frm_exercise;
        if (!f) {
            // No exercise form found; nothing to do.
            if (window.console && console.warn) {
                console.warn('[exercise_submit_modal] frm_exercise not found in document');
            }
            return;
        }

        var finalUrl = '".addslashes($url)."';
        var selected = f.querySelector('input[name=\"choice[$questionId]\"]:checked');
        var choiceVal = selected ? selected.value : '';
        var hotspotInput = f.querySelector('input[name^=\"hotspot[$questionId]\"]');
        var hotspotVal = hotspotInput ? hotspotInput.value : '';

        if (choiceVal) {
            finalUrl += '&choice[$questionId]=' + encodeURIComponent(choiceVal);
        }
        if (hotspotVal) {
            finalUrl += '&hotspot[$questionId]=' + encodeURIComponent(hotspotVal);
        }

        $.get(finalUrl, function(data) {
            // Prefer the standard global modal body used by exercise_submit.php.
            var \$container = \$('#global-modal .modal-body');
            if (!\$container.length) {
                // Fallback for legacy templates that use #global-modal-body.
                \$container = \$('#global-modal-body');
            }
            if (\$container.length) {
                \$container.html(data);
            } else if (window.console && console.warn) {
                console.warn('[exercise_submit_modal] No modal container found (#global-modal or #global-modal-body)');
            }
        });
    });
    </script>";
    exit;
}

/**
 * Merge the current choice into exerciseResult, keeping all previous answers.
 * At this point $allowTryAgain might have removed the previous entry for this
 * question id, so we just store the fresh value.
 */
if (!is_array($exerciseResult)) {
    $exerciseResult = [];
}

$exerciseResult[$questionId] = $choiceValue;

Session::write('exerciseResult', $exerciseResult);

$answerType = $question->getType();
$showResult = $isAdaptative;

$objAnswerTmp = new Answer($questionId, api_get_course_int_id());

// Normalize choice value depending on answer type.
if (MULTIPLE_ANSWER == $answerType && is_array($choiceValue)) {
    $choiceValue = array_combine(array_values($choiceValue), array_values($choiceValue));
}

if (UNIQUE_ANSWER == $answerType && is_array($choiceValue) && isset($choiceValue[0])) {
    $choiceValue = $choiceValue[0];
}

if (HOT_SPOT_DELINEATION == $answerType && is_array($hotSpot)) {
    $choiceValue = $hotSpot[1] ?? '';
    $_SESSION['exerciseResultCoordinates'][$questionId] = $choiceValue;
    $_SESSION['hotspot_coord'][$questionId][1] = $objAnswerTmp->selectHotspotCoordinates(1);
    $_SESSION['hotspot_dest'][$questionId][1] = $objAnswerTmp->selectDestination(1);
}

// Capture HTML output from manage_answer; we only use it for some types.
ob_start();
$result = $objExercise->manage_answer(
    $exeId,
    $questionId,
    $choiceValue,
    'exercise_result',
    [],
    (EXERCISE_FEEDBACK_TYPE_POPUP === $feedbackType),
    false,
    $showResult,
    null,
    [],
    true,
    false,
    true
);
$manageAnswerHtmlContent = ob_get_clean();

// Basic score flags.
$contents = '';
$answerCorrect = ($result['score'] == $result['weight']);
$partialCorrect = !$answerCorrect && !empty($result['score']);
$destinationId = null;
$routeKey = $answerCorrect ? 'success' : 'failure';

// Compute destination based on adaptive routing or default sequential order.
if ($isAdaptative && !empty($destinationArray) && isset($destinationArray[$routeKey])) {
    $firstDest = $destinationArray[$routeKey];

    if (is_string($firstDest) && is_numeric($firstDest)) {
        $firstDest = (int) $firstDest;
    }

    if ($firstDest === 'repeat') {
        // Repeat the same question.
        $destinationId = $questionId;
    } elseif ($firstDest === -1) {
        // End of activity.
        $destinationId = -1;
    } elseif (is_int($firstDest)) {
        // Go to question with id = $firstDest.
        $destinationId = $firstDest;
    } elseif (is_string($firstDest) && str_starts_with($firstDest, '/')) {
        // Go to an external resource (relative path to WEB_PATH).
        $destinationId = $firstDest;
    }
} else {
    // Default: next question in the list (or -1 if there is no next question).
    $nextQuestion = $questionNum + 1;
    $destinationId = $questionList[$nextQuestion] ?? -1;
}

if (is_string($destinationId) && is_numeric($destinationId)) {
    $destinationId = (int) $destinationId;
}

// Build feedback contents depending on feedback model.
if ($isAdaptative && isset($result['correct_answer_id'])) {
    // Adaptive mode: show specific comments for correct answers.
    foreach ($result['correct_answer_id'] as $answerId) {
        $contents .= $objAnswerTmp->selectComment($answerId);
    }
} elseif (EXERCISE_FEEDBACK_TYPE_POPUP === $feedbackType) {
    $message = get_lang(
        $answerCorrect ? 'Correct' : ($partialCorrect ? 'PartialCorrect' : 'Incorrect')
    );
    $comments = '';

    if (HOT_SPOT_DELINEATION !== $answerType && isset($result['correct_answer_id'])) {
        $table = new HTML_Table(['class' => 'table data_table']);
        $table->setCellContents(0, 0, get_lang('Your answer'));
        if (DRAGGABLE !== $answerType) {
            $table->setCellContents(0, 1, get_lang('Comment'));
        }

        $row = 1;
        foreach ($result['correct_answer_id'] as $answerId) {
            $a = $objAnswerTmp->getAnswerByAutoId($answerId);
            $table->setCellContents(
                $row,
                0,
                $a['answer'] ?? $objAnswerTmp->selectAnswer($answerId)
            );
            $table->setCellContents(
                $row,
                1,
                $a['comment'] ?? $objAnswerTmp->selectComment($answerId)
            );
            $row++;
        }

        $comments = $table->toHtml();
    }

    // If there is no specific comment, at least show a basic message.
    if ('' === trim($comments)) {
        $comments = '<p>'.get_lang('No detailed feedback is available for this question.').'</p>';
    }

    $contents .= $comments;

    echo '
        <div class="modal-header">
            <h4 class="modal-title" id="global-modal-title">'.$message.'</h4>
        </div>';
}

// For hotspot delineation we keep the HTML generated by manage_answer.
if (HOT_SPOT_DELINEATION === $answerType) {
    $contents = $manageAnswerHtmlContent;
}

// Build navigation links for the adaptive / popup flow.
$links = '';
$navBranch = 'none';
$indexForLog = null;

// Small JS helper that navigates explicitly, without relying on SendEx().
echo '<script>
var chExerciseBaseUrl = "'.addslashes($exerciseBaseUrl).'";
var chExerciseResultUrl = "'.addslashes($exerciseResultUrl). '";

/**
 * Navigate to the given question index (0-based) or to the result page.
 *
 * idx >= 0 → go to exercise_submit.php with num = idx + 1
 * idx  = -1 → go to exercise_result.php
 */
function chExerciseSendEx(idx) {
    // Always navigate in the current window/frame.
    // When the exercise is launched from a learning path, exercise_submit.php
    // runs inside a frame; using window.parent here would navigate the LP
    // shell away and break the learning path context.
    var target = window;

    if (idx === -1) {
        // End of activity → show results.
        target.location.href = chExerciseResultUrl;
        return false;
    }

    var qIndex = parseInt(idx, 10);
    if (isNaN(qIndex) || qIndex < 0) {
        if (window.console && console.warn) {
            console.warn("[exercise_submit_modal] Invalid idx for chExerciseSendEx:", idx);
        }
        return false;
    }

    // Questions are 1-based in the URL.
    var num = qIndex + 1;

    target.location.href = chExerciseBaseUrl + "&num=" + num + "&tryagain=1";

    return false;
}
</script>';

if ($destinationId === $questionId) {
    // Repeat same question.
    $index = array_search($questionId, $questionList, true);
    $indexForLog = $index;
    $navBranch = 'repeatQuestion';

    $links .= Display::getMdiIcon(
            ActionIcon::REFRESH,
            'ch-tool-icon',
            'padding-left:0px;padding-right:5px;',
            ICON_SIZE_SMALL
        )
        .'<a onclick="return chExerciseSendEx('.$index.');" href="#">'
        .get_lang('Try again').'</a><br /><br />';
} elseif (-1 === $destinationId) {
    // End of activity.
    $navBranch = 'endActivity';

    $links .= Display::getMdiIcon(
            StateIcon::COMPLETE,
            'ch-tool-icon',
            'padding-left:0px;padding-right:5px;',
            ICON_SIZE_SMALL
        )
        .'<a onclick="return chExerciseSendEx(-1);" href="#">'
        .get_lang('End of activity').'</a><br /><br />';
} elseif (is_int($destinationId) && in_array($destinationId, $questionList, true)) {
    // Go to another question by id.
    $index = array_search($destinationId, $questionList, true);
    $indexForLog = $index;
    $navBranch = 'nextQuestion';

    $icon = Display::getMdiIcon(
        ObjectIcon::TEST,
        'ch-tool-icon',
        'padding-left:0px;padding-right:5px;',
        ICON_SIZE_SMALL
    );
    $links .= '<a onclick="return chExerciseSendEx('.$index.');" href="#">'
        .get_lang('Question').' '.($index + 1).'</a>&nbsp;'.$icon;
} elseif (is_string($destinationId) && str_starts_with($destinationId, '/')) {
    // External resource.
    $navBranch = 'externalResource';

    $icon = Display::getMdiIcon(
        ObjectIcon::LINK,
        'ch-tool-icon',
        'padding-left:0px;padding-right:5px;',
        ICON_SIZE_SMALL
    );
    $fullUrl = api_get_path(WEB_PATH).ltrim($destinationId, '/');
    $links .= '<a href="'.$fullUrl.'">'.get_lang('Go to resource').'</a>&nbsp;'.$icon;
}

// Body + navigation block.
echo '<div>'.$contents.'</div>';
echo '<div style="padding-left: 450px"><h5>'.$links.'</h5></div>';
echo '</div>';

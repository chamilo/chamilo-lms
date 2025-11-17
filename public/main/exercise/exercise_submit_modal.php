<?php

declare(strict_types=1);

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
 *
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

/**
 * Small helper: determine if an answer container really has data.
 *
 * - For arrays: at least one element.
 * - For scalars: '' and null are considered empty. "0" is a valid answer.
 *
 * @param mixed $value
 */
function chExerciseHasAnswer($value): bool
{
    if (is_array($value)) {
        return count($value) > 0;
    }

    return null !== $value && '' !== $value;
}

/**
 * Normalize a question answer container into a flat list of selected answer IDs.
 *
 * This tries to detect the most probable pattern:
 * - id => id  (e.g. [69432 => '69432'])
 * - 0..N-1 => id (e.g. [0 => '69432', 1 => '69433'])
 * - scalar '69432'
 *
 * @param mixed $value
 */
function chExerciseExtractAnswerIds($value): array
{
    if (!is_array($value)) {
        return is_numeric($value) ? [(int) $value] : [];
    }

    $keys = array_keys($value);
    $vals = array_values($value);

    $allNumericKeys = true;
    foreach ($keys as $k) {
        if (!is_numeric($k)) {
            $allNumericKeys = false;

            break;
        }
    }

    $allNumericVals = true;
    foreach ($vals as $v) {
        if (!is_numeric($v)) {
            $allNumericVals = false;

            break;
        }
    }

    $sequentialKeys = $allNumericKeys && ($keys === range(0, count($keys) - 1));

    $ids = [];

    if ($allNumericKeys && $allNumericVals && $keys === $vals) {
        // Pattern: id => id
        $ids = $keys;
    } elseif ($sequentialKeys && $allNumericVals) {
        // Pattern: 0..N-1 => id
        $ids = $vals;
    } elseif ($allNumericKeys && !$allNumericVals) {
        // Fallback: keys look like ids
        $ids = $keys;
    } elseif (!$allNumericKeys && $allNumericVals) {
        // Fallback: values look like ids
        $ids = $vals;
    } else {
        // Worst case: keep all numeric keys and values
        foreach ($keys as $k) {
            if (is_numeric($k)) {
                $ids[] = (int) $k;
            }
        }
        foreach ($vals as $v) {
            if (is_numeric($v)) {
                $ids[] = (int) $v;
            }
        }
    }

    $ids = array_map('intval', $ids);

    return array_values(array_unique($ids));
}

if (!$questionId) {
    exit;
}

// Try to read answer/hotspot from GET first.
$choiceValue = $_GET['choice'][$questionId] ?? ($_GET['choice'] ?? '');
$hotSpot = $_GET['hotspot'][$questionId] ?? ($_GET['hotspot'] ?? '');
$tryAgain = (1 === (int) ($_GET['tryagain'] ?? 0));
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
    ])
;

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
}

/*
 * If we still have no choice/hotspot, we need to fetch the answer from the
 * main exercise form (frm_exercise) in the parent page.
 *
 * First pass: redirect to the same script with "loaded=1".
 * Second pass (loaded=1 and still no answer): inject JS that reads the
 * current choice from the form and performs a third GET with the proper
 * parameters. That third GET will finally compute the feedback.
 */
if (
    !chExerciseHasAnswer($choiceValue)
    && !chExerciseHasAnswer($hotSpot)
    && !isset($_GET['loaded'])
) {
    $params = $_REQUEST;
    $params['loaded'] = 1;
    $redirectUrl = $_SERVER['PHP_SELF'].'?'.http_build_query($params);

    header("Location: $redirectUrl");

    exit;
}

if (
    !chExerciseHasAnswer($choiceValue)
    && !chExerciseHasAnswer($hotSpot)
    && isset($_GET['loaded'])
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

        // Collect all controls for this question using the \"choice[{$questionId}]\" prefix.
        // This should work for:
        // - Single choice (radio)
        // - Multiple choice (checkboxes)
        // - Matching (selects)
        // - Fill in the blanks (text/textarea using choice[...] naming)
        // - Other types reusing the same prefix
        var controls = f.querySelectorAll(
            'input[name^=\"choice[{$questionId}]\"],'
          + 'select[name^=\"choice[{$questionId}]\"],'
          + 'textarea[name^=\"choice[{$questionId}]\"]'
        );

        var hasChoice = false;

        controls.forEach(function(el) {
            var type = (el.type || '').toLowerCase();

            // For checkboxes/radios we only keep checked ones.
            if ((type === 'checkbox' || type === 'radio') && !el.checked) {
                return;
            }

            var val = el.value;
            if (val === undefined || val === null || val === '') {
                return;
            }

            hasChoice = true;

            // Preserve the original field name so PHP builds the same array structure.
            finalUrl += '&' + encodeURIComponent(el.name) + '=' + encodeURIComponent(val);
        });

        // Hotspot input (if any).
        var hotspotInput = f.querySelector('input[name^=\"hotspot[{$questionId}]\"]');
        var hotspotVal = hotspotInput ? hotspotInput.value : '';
        var hasHotspot = !!hotspotVal;

        // avoid infinite loop when no answer is selected
        if (!hasChoice && !hasHotspot) {
            var \$container = \$('#global-modal .modal-body');
            if (!\$container.length) {
                // Fallback for legacy templates that use #global-modal-body.
                \$container = \$('#global-modal-body');
            }
            if (\$container.length) {
                \$container.html('<p>".addslashes(get_lang('Please select an answer before checking the result.'))."</p>');
            }
            if (window.console && console.warn) {
                console.warn('[exercise_submit_modal] No answer/hotspot found in frm_exercise; aborting extra request.');
            }
            return;
        }

        if (hotspotVal) {
            finalUrl += '&hotspot[{$questionId}]=' + encodeURIComponent(hotspotVal);
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

/*
 * Merge the current choice into exerciseResult, keeping all previous answers.
 * At this point $allowTryAgain might have removed the previous entry for this
 * question id, so we will store the fresh, normalized value.
 */
if (!is_array($exerciseResult)) {
    $exerciseResult = [];
}

$answerType = $question->getType();
$showResult = $isAdaptative;

$objAnswerTmp = new Answer($questionId, api_get_course_int_id());

// Normalize choice value depending on answer type.
if (MULTIPLE_ANSWER == $answerType && is_array($choiceValue)) {
    // For multiple answers we expect an associative array id => id.
    $choiceValue = array_combine(array_values($choiceValue), array_values($choiceValue));
}

if (UNIQUE_ANSWER == $answerType && is_array($choiceValue)) {
    // For unique answer we keep a single selected id; prefer "id => id" format.
    $ids = chExerciseExtractAnswerIds($choiceValue);
    if (!empty($ids)) {
        $choiceValue = $ids[0];
    }
}

if (HOT_SPOT_DELINEATION == $answerType && is_array($hotSpot)) {
    // For hotspot delineation we keep coordinates in a dedicated structure.
    $choiceValue = $hotSpot[1] ?? '';
    $_SESSION['exerciseResultCoordinates'][$questionId] = $choiceValue;
    $_SESSION['hotspot_coord'][$questionId][1] = $objAnswerTmp->selectHotspotCoordinates(1);
    $_SESSION['hotspot_dest'][$questionId][1] = $objAnswerTmp->selectDestination(1);
}

// Only persist if we actually have an answer for this question.
if (chExerciseHasAnswer($choiceValue) || chExerciseHasAnswer($hotSpot)) {
    $exerciseResult[$questionId] = $choiceValue;
    Session::write('exerciseResult', $exerciseResult);
}

// Capture HTML output from manage_answer; we only use it for some types.
ob_start();
$result = $objExercise->manage_answer(
    $exeId,
    $questionId,
    $choiceValue,
    'exercise_result',
    [],
    EXERCISE_FEEDBACK_TYPE_POPUP === $feedbackType,
    false,
    $showResult,
    null,
    [],
    true,
    false,
    true
);
$manageAnswerHtmlContent = ob_get_clean();

// -----------------------------------------------------------------------------
// Decide success / failure (adaptive routing and feedback flags)
// -----------------------------------------------------------------------------
// We fully trust manage_answer() for the scoring logic and use the ratio
// score/weight to decide if the question is correct or not, similar to the
// regular exercise flow (save_exercise_by_now).
$contents = '';
$answerCorrect = false;
$partialCorrect = false;

$score = isset($result['score']) ? (float) $result['score'] : 0.0;
$weight = isset($result['weight']) ? (float) $result['weight'] : 0.0;

if ($weight > 0.0) {
    // Full success only when the achieved score reaches the max weight.
    $answerCorrect = ($score >= $weight);
    // Partial success when there is some score but not the full weight.
    $partialCorrect = !$answerCorrect && $score > 0.0;
} else {
    // Zero or undefined weight: any positive score counts as correct.
    $answerCorrect = ($score > 0.0);
    $partialCorrect = false;
}

$routeKey = $answerCorrect ? 'success' : 'failure';

// Compute destination based on adaptive routing or default sequential order.
$destinationId = null;
if ($isAdaptative && !empty($destinationArray) && isset($destinationArray[$routeKey])) {
    $firstDest = $destinationArray[$routeKey];

    if (is_string($firstDest) && is_numeric($firstDest)) {
        $firstDest = (int) $firstDest;
    }

    if ('repeat' === $firstDest) {
        // Repeat the same question.
        $destinationId = $questionId;
    } elseif (-1 === $firstDest) {
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
var chExerciseResultUrl = "'.addslashes($exerciseResultUrl).'";

/**
 * Navigate to the given question index (0-based) or to the result page.
 *
 * idx >= 0 → go to exercise_submit.php with num = idx + 1
 * idx  = -1 → go to exercise_result.php
 *
 * tryAgain = true → append tryagain=1, used when repeating the same question.
 */
function chExerciseSendEx(idx, tryAgain) {
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
    var url = chExerciseBaseUrl + "&num=" + num;

    if (tryAgain) {
        url += "&tryagain=1";
    }

    target.location.href = url;

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
        .'<a onclick="return chExerciseSendEx('.$index.', true);" href="#">'
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
        .'<a onclick="return chExerciseSendEx(-1, false);" href="#">'
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
    $links .= '<a onclick="return chExerciseSendEx('.$index.', false);" href="#">'
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

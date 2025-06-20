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
$isAdaptative = $feedbackType === EXERCISE_FEEDBACK_TYPE_DIRECT;

if (!in_array($feedbackType, [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP])) {
    api_not_allowed();
}

$learnpath_id = (int) ($_REQUEST['learnpath_id'] ?? 0);
$learnpath_item_id = (int) ($_REQUEST['learnpath_item_id'] ?? 0);
$exerciseId = (int) ($_GET['exerciseId'] ?? 0);
$questionList = array_values(Session::read('questionList') ?? []);
$questionNum = max(0, ((int) ($_GET['num'] ?? 1)) - 1);
$questionId = $questionList[$questionNum] ?? null;

if (!$questionId) {
    exit;
}

$choiceValue = $_GET['choice'][$questionId] ?? ($_GET['choice'] ?? '');
$hotSpot = $_GET['hotspot'][$questionId] ?? ($_GET['hotspot'] ?? '');
$tryAgain = (int) ($_GET['tryagain'] ?? 0) === 1;

$repo = Container::getQuestionRepository();
/** @var CQuizQuestion $question */
$question = $repo->find($questionId);

$entityManager = Database::getManager();
$rel = $entityManager->getRepository(CQuizRelQuestion::class)->findOneBy([
    'quiz' => $exerciseId,
    'question' => $questionId,
]);

$destinationArray = json_decode($rel?->getDestination(), true);
$failure = $destinationArray['failure'] ?? [];
$failure = is_array($failure) ? $failure : [$failure];

$allowTryAgain = $tryAgain && is_array($destinationArray) && in_array('repeat', $failure);


if ($allowTryAgain) {
    unset($exerciseResult[$questionId]);
}

if (empty($choiceValue) && isset($exerciseResult[$questionId])) {
    $choiceValue = $exerciseResult[$questionId];
}

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
        .'&learnpath_item_id='.$learnpath_item_id;

    echo "<script>
    $(document).ready(function() {
        var f = document.frm_exercise;
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
            $('#global-modal-body').html(data);
        });
    });
    </script>";
    exit;
}

$choice = [$questionId => $choiceValue];
if (!is_array($exerciseResult)) {
    $exerciseResult = [];
}
if (in_array($exerciseType, [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP])) {
    $exerciseResult = $choice;
} else {
    $key = array_key_first($choice);
    if (!isset($exerciseResult[$key])) {
        $exerciseResult[$key] = $choice[$key];
    }
}
Session::write('exerciseResult', $exerciseResult);

$answerType = $question->getType();
$showResult = $isAdaptative;

$objAnswerTmp = new Answer($questionId, api_get_course_int_id());

if ($answerType == MULTIPLE_ANSWER && is_array($choiceValue)) {
    $choiceValue = array_combine(array_values($choiceValue), array_values($choiceValue));
}
if ($answerType == UNIQUE_ANSWER && is_array($choiceValue) && isset($choiceValue[0])) {
    $choiceValue = $choiceValue[0];
}
if ($answerType == HOT_SPOT_DELINEATION && is_array($hotSpot)) {
    $choiceValue = $hotSpot[1] ?? '';
    $_SESSION['exerciseResultCoordinates'][$questionId] = $choiceValue;
    $_SESSION['hotspot_coord'][$questionId][1] = $objAnswerTmp->selectHotspotCoordinates(1);
    $_SESSION['hotspot_dest'][$questionId][1] = $objAnswerTmp->selectDestination(1);
}

ob_start();
$result = $objExercise->manage_answer(
    Session::read('exe_id') ?? 0,
    $questionId,
    $choiceValue,
    'exercise_result',
    [],
    $feedbackType === EXERCISE_FEEDBACK_TYPE_POPUP,
    false,
    $showResult,
    null,
    [],
    true,
    false,
    true
);
$manageAnswerHtmlContent = ob_get_clean();
$contents = '';
$answerCorrect = $result['score'] == $result['weight'];
$partialCorrect = !$answerCorrect && !empty($result['score']);
$destinationId = null;
$routeKey = $answerCorrect ? 'success' : 'failure';

if ($isAdaptative && is_array($destinationArray) && isset($destinationArray[$routeKey])) {
    $firstDest = $destinationArray[$routeKey];

    if (is_string($firstDest) && is_numeric($firstDest)) {
        $firstDest = (int) $firstDest;
    }

    if ($firstDest === 'repeat') {
        $destinationId = $questionId;
    } elseif ($firstDest === -1) {
        $destinationId = -1;
    } elseif (is_int($firstDest)) {
        $destinationId = $firstDest;
    } elseif (is_string($firstDest) && str_starts_with($firstDest, '/')) {
        $destinationId = $firstDest;
    }
} else {
    $nextQuestion = $questionNum + 1;
    $destinationId = $questionList[$nextQuestion] ?? -1;
}

if (is_string($destinationId) && is_numeric($destinationId)) {
    $destinationId = (int) $destinationId;
}

if ($isAdaptative && isset($result['correct_answer_id'])) {
    foreach ($result['correct_answer_id'] as $answerId) {
        $contents .= $objAnswerTmp->selectComment($answerId);
    }
} elseif ($feedbackType === EXERCISE_FEEDBACK_TYPE_POPUP) {
    $message = get_lang($answerCorrect ? 'Correct' : ($partialCorrect ? 'PartialCorrect' : 'Incorrect'));
    $comments = '';

    if ($answerType !== HOT_SPOT_DELINEATION && isset($result['correct_answer_id'])) {
        $table = new HTML_Table(['class' => 'table data_table']);
        $table->setCellContents(0, 0, get_lang('YourAnswer'));
        if ($answerType !== DRAGGABLE) {
            $table->setCellContents(0, 1, get_lang('Comment'));
        }

        $row = 1;
        foreach ($result['correct_answer_id'] as $answerId) {
            $a = $objAnswerTmp->getAnswerByAutoId($answerId);
            $table->setCellContents($row, 0, $a['answer'] ?? $objAnswerTmp->selectAnswer($answerId));
            $table->setCellContents($row, 1, $a['comment'] ?? $objAnswerTmp->selectComment($answerId));
            $row++;
        }

        $comments = $table->toHtml();
    }

    $contents .= $comments;
    echo '
        <div class="modal-header">
            <h4 class="modal-title" id="global-modal-title">'.$message.'</h4>
        </div>';
}

if (HOT_SPOT_DELINEATION === $answerType) {
    $contents = $manageAnswerHtmlContent;
}
$links = '';
if ($destinationId === 'repeat' || $destinationId === $questionId) {
    $index = array_search($questionId, $questionList);
    $links .= Display::getMdiIcon(ActionIcon::REFRESH, 'ch-tool-icon', 'padding-left:0px;padding-right:5px;', ICON_SIZE_SMALL)
        .'<a onclick="SendEx('.$index.');" href="#">'.get_lang('Try again').'</a><br /><br />';
} elseif ($destinationId == -1) {
    $links .= Display::getMdiIcon(StateIcon::COMPLETE, 'ch-tool-icon', 'padding-left:0px;padding-right:5px;', ICON_SIZE_SMALL)
        .'<a onclick="SendEx(-1);" href="#">'.get_lang('End of activity').'</a><br /><br />';
} elseif (in_array($destinationId, $questionList)) {
    $index = array_search($destinationId, $questionList);
    $icon = Display::getMdiIcon(ObjectIcon::TEST, 'ch-tool-icon', 'padding-left:0px;padding-right:5px;', ICON_SIZE_SMALL);
    $links .= '<a onclick="SendEx('.$index.');" href="#">'.get_lang('Question').' '.($index + 1).'</a>&nbsp;'.$icon;
} elseif (is_string($destinationId) && str_starts_with($destinationId, '/')) {
    $icon = Display::getMdiIcon(ObjectIcon::LINK, 'ch-tool-icon', 'padding-left:0px;padding-right:5px;', ICON_SIZE_SMALL);
    $fullUrl = api_get_path(WEB_PATH).ltrim($destinationId, '/');
    $links .= '<a href="'.$fullUrl.'">'.get_lang('Go to resource').'</a>&nbsp;'.$icon;
}
echo '<div>'.$contents.'</div>';
echo '<div style="padding-left: 450px"><h5>'.$links.'</h5></div>';
echo '</div>';

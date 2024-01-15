<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use ChamiloSession as Session;
use Chamilo\CoreBundle\Component\Utils\ActionIcon;
use Chamilo\CoreBundle\Component\Utils\ObjectIcon;
use Chamilo\CoreBundle\Component\Utils\StateIcon;

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
if (!in_array($feedbackType, [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP])) {
    api_not_allowed();
}

$learnpath_id = isset($_REQUEST['learnpath_id']) ? (int) $_REQUEST['learnpath_id'] : 0;
$learnpath_item_id = isset($_REQUEST['learnpath_item_id']) ? (int) $_REQUEST['learnpath_item_id'] : 0;
$questionList = Session::read('questionList');

$exerciseId = (int) $_GET['exerciseId'];
$questionNum = (int) $_GET['num'];
$questionId = $questionList[$questionNum];
$choiceValue = isset($_GET['choice']) ? $_GET['choice'] : '';
$hotSpot = isset($_GET['hotspot']) ? $_GET['hotspot'] : '';
$tryAgain = isset($_GET['tryagain']) && 1 === (int) $_GET['tryagain'];

$repo = Container::getQuestionRepository();
/** @var CQuizQuestion $question */
$question = $repo->find($questionId);

$allowTryAgain = false;
if ($tryAgain) {
    // Check if try again exists in this question, otherwise only allow one attempt BT#15827.
    $answerType = $question->getType();
    $showResult = false;
    //$objAnswerTmp = new Answer($questionId, api_get_course_int_id());
    $answers = $question->getAnswers();
    if (!empty($answers)) {
        foreach ($answers as $answerData) {
            $destination = $answerData->getDestination();
            if (!empty($destination)) {
                $itemList = explode('@@', $destination);
                if (isset($itemList[0]) && !empty($itemList[0])) {
                    $allowTryAgain = true;
                    break;
                }
            }
        }
    }
}
$loaded = isset($_GET['loaded']);
if ($allowTryAgain) {
    unset($exerciseResult[$questionId]);
}

if (empty($choiceValue) && isset($exerciseResult[$questionId])) {
    $choiceValue = $exerciseResult[$questionId];
}

if (!empty($hotSpot)) {
    if (isset($hotSpot[$questionId])) {
        $hotSpot = $hotSpot[$questionId];
    }
}

if (!empty($choiceValue)) {
    if (isset($choiceValue[$questionId])) {
        $choiceValue = $choiceValue[$questionId];
    }
}

$header = '';
$exeId = 0;
if (EXERCISE_FEEDBACK_TYPE_POPUP === $objExercise->getFeedbackType()) {
    $exeId = Session::read('exe_id');
    $header = '
        <div class="modal-header">
            <h4 class="modal-title" id="global-modal-title">'.get_lang('Incorrect').'</h4>
        </div>';
}
echo '<script>
function tryAgain() {
    $(function () {
        $("#global-modal").modal("hide");
    });
}

function SendEx(num) {
    if (num == -1) {
        window.location.href = "exercise_result.php?'.api_get_cidreq().'&exe_id='.$exeId.'&take_session=1&exerciseId='.$exerciseId.'&num="+num+"&learnpath_item_id='.$learnpath_item_id.'&learnpath_id='.$learnpath_id.'";
    } else {
        num -= 1;
        window.location.href = "exercise_submit.php?'.api_get_cidreq().'&tryagain=1&exerciseId='.$exerciseId.'&num="+num+"&learnpath_item_id='.$learnpath_item_id.'&learnpath_id='.$learnpath_id.'";
    }
    return false;
}
</script>';

echo '<div id="delineation-container">';
// Getting the options by js
if (empty($choiceValue) && empty($hotSpot) && $loaded) {
    $nextQuestion = $questionNum + 1;
    $destinationId = isset($questionList[$nextQuestion]) ? $questionList[$nextQuestion] : -1;
    $icon = Display::getMdiIcon(
        ActionIcon::REFRESH,
        'ch-tool-icon',
        'width:22px; height:22px; padding-left:0px;padding-right:5px;',
        ICON_SIZE_SMALL
    );
    $links = '<a onclick="tryAgain();" href="#">'.get_lang('Try again').'</a>&nbsp;'.$icon.'&nbsp;';

    // the link to finish the test
    if (-1 == $destinationId) {
        $links .= Display::getMdiIcon(
                StateIcon::COMPLETE,
                'ch-tool-icon',
                'width:22px; height:22px; padding-left:0px;padding-right:5px;',
                ICON_SIZE_SMALL
            ).'<a onclick="SendEx(-1);" href="#">'.get_lang('End of activity').'</a><br /><br />';
    } else {
        // the link to other question
        if (in_array($destinationId, $questionList)) {
            $num_value_array = array_keys($questionList, $destinationId);
            $icon = Display::getMdiIcon(
                ObjectIcon::TEST,
                '',
                ['style' => 'padding-left:0px;padding-right:5px;']
            );
            $links .= '<a onclick="SendEx('.$num_value_array[0].');" href="#">'.
                get_lang('Question').' '.$num_value_array[0].'</a>&nbsp;';
            $links .= $icon;
        }
    }
    echo $header;
    echo '<div class="row"><div class="col-md-5 col-md-offset-7"><h5 class="pull-right">'.$links.'</h5></div></div>';
    exit;
}

if (empty($choiceValue) && empty($hotSpot)) {
    echo "<script>
        // this works for only radio buttons
        var f = window.document.frm_exercise;
        var choice_js = {answers: []};
        var hotspot = new Array();
        var hotspotcoord = new Array();
        var counter = 0;

        for (var i = 0; i < f.elements.length; i++) {
            if (f.elements[i].type == 'radio' && f.elements[i].checked) {
                choice_js.answers.push(f.elements[i].value);
                counter ++;
            }

            if (f.elements[i].type == 'checkbox' && f.elements[i].checked) {
                choice_js.answers.push(f.elements[i].value);
                counter ++;
            }

            if (f.elements[i].type == 'hidden') {
                var name = f.elements[i].name;

                if (name.substr(0,7) == 'hotspot') {
                    hotspot.push(f.elements[i].value);
                }

                if (name.substr(0,20) == 'hotspot_coordinates') {
                    hotspotcoord.push(f.elements[i].value);
                }
            }
        }

        var my_choice = $('*[name*=\"choice[".$questionId."]\"]').serialize();
        var hotspot = $('*[name*=\"hotspot[".$questionId."]\"]').serialize();
    ";

    // IMPORTANT
    // This is the real redirect function
    $extraUrl = '&loaded=1&exerciseId='.$exerciseId.'&num='.$questionNum.'&learnpath_id='.$learnpath_id.'&learnpath_item_id='.$learnpath_item_id;
    $url = api_get_path(WEB_CODE_PATH).'exercise/exercise_submit_modal.php?'.api_get_cidreq().$extraUrl;
    echo ' url = "'.addslashes($url).'&hotspotcoord="+ hotspotcoord + "&"+ hotspot + "&"+ my_choice;';
    echo "$('#global-modal .modal-body').load(url);";
    echo '</script>';
    exit;
}
$choice = [];
$choice[$questionId] = isset($choiceValue) ? $choiceValue : null;
if (!is_array($exerciseResult)) {
    $exerciseResult = [];
}
$saveResults = EXERCISE_FEEDBACK_TYPE_POPUP == (int) $objExercise->getFeedbackType();

// if the user has answered at least one question
if (is_array($choice)) {
    if (in_array($exerciseType, [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP])) {
        // $exerciseResult receives the content of the form.
        // Each choice of the student is stored into the array $choice
        $exerciseResult = $choice;
    } else {
        // gets the question ID from $choice. It is the key of the array
        [$key] = array_keys($choice);
        // if the user didn't already answer this question
        if (!isset($exerciseResult[$key])) {
            // stores the user answer into the array
            $exerciseResult[$key] = $choice[$key];
        }
    }
}

// the script "exercise_result.php" will take the variable $exerciseResult from the session
Session::write('exerciseResult', $exerciseResult);

$answerType = $question->getType();
$showResult = false;

$objAnswerTmp = new Answer($questionId, api_get_course_int_id());
if (EXERCISE_FEEDBACK_TYPE_DIRECT === $objExercise->getFeedbackType()) {
    $showResult = true;
}

switch ($answerType) {
    case MULTIPLE_ANSWER:
        if (is_array($choiceValue)) {
            $choiceValue = array_combine(array_values($choiceValue), array_values($choiceValue));
        }

        break;
    case UNIQUE_ANSWER:
        if (is_array($choiceValue) && isset($choiceValue[0])) {
            $choiceValue = $choiceValue[0];
        }

        break;
    case DRAGGABLE:
        break;
    case HOT_SPOT_DELINEATION:
        $showResult = true;
        if (is_array($hotSpot)) {
            $choiceValue = isset($hotSpot[1]) ? $hotSpot[1] : '';
            $_SESSION['exerciseResultCoordinates'][$questionId] = $choiceValue; //needed for exercise_result.php
            $delineation_cord = $objAnswerTmp->selectHotspotCoordinates(1);
            $answer_delineation_destination = $objAnswerTmp->selectDestination(1);
            $_SESSION['hotspot_coord'][$questionId][1] = $delineation_cord;
            $_SESSION['hotspot_dest'][$questionId][1] = $answer_delineation_destination;
        }

        break;
    case CALCULATED_ANSWER:
        break;
}

ob_start();
$result = $objExercise->manage_answer(
    $exeId,
    $questionId,
    $choiceValue,
    'exercise_result',
    [],
    $saveResults,
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
$answerCorrect = false;
$partialCorrect = false;
if (!empty($result)) {
    switch ($answerType) {
        case MULTIPLE_ANSWER:
        case UNIQUE_ANSWER:
        case DRAGGABLE:
        case HOT_SPOT_DELINEATION:
        case CALCULATED_ANSWER:
            if ($result['score'] == $result['weight']) {
                $answerCorrect = true;
            }

            // Check partial correct
            if (false === $answerCorrect) {
                if (!empty($result['score'])) {
                    $partialCorrect = true;
                }
            }
            break;
    }
}

$header = '';
if (EXERCISE_FEEDBACK_TYPE_DIRECT === $objExercise->getFeedbackType()) {
    if (isset($result['correct_answer_id'])) {
        foreach ($result['correct_answer_id'] as $answerId) {
            /** @var Answer $answer */
            $contents .= $objAnswerTmp->selectComment($answerId);
        }
    }
} else {
    $message = get_lang('Incorrect');
    //$contents = Display::return_message($message, 'warning');

    if ($answerCorrect) {
        $message = get_lang('Correct');
    //$contents = Display::return_message($message, 'success');
    } else {
        if ($partialCorrect) {
            $message = get_lang('PartialCorrect');
        }
    }

    $comments = '';
    if (HOT_SPOT_DELINEATION != $answerType) {
        if (isset($result['correct_answer_id'])) {
            $table = new HTML_Table(['class' => 'table data_table']);
            $row = 0;
            $table->setCellContents($row, 0, get_lang('YourAnswer'));
            if (DRAGGABLE != $answerType) {
                $table->setCellContents($row, 1, get_lang('Comment'));
            }

            $data = [];
            foreach ($result['correct_answer_id'] as $answerId) {
                $answer = $objAnswerTmp->getAnswerByAutoId($answerId);
                if (!empty($answer) && isset($answer['comment'])) {
                    $data[] = [$answer['answer'], $answer['comment']];
                } else {
                    $answer = $objAnswerTmp->selectAnswer($answerId);
                    $comment = $objAnswerTmp->selectComment($answerId);
                    $data[] = [$answer, $comment];
                }
            }

            if (!empty($data)) {
                $row = 1;
                foreach ($data as $dataItem) {
                    $table->setCellContents($row, 0, $dataItem[0]);
                    $table->setCellContents($row, 1, $dataItem[1]);
                    $row++;
                }
                $comments = $table->toHtml();
            }
        }
    }

    $contents .= $comments;
    $header = '
        <div class="modal-header">
            <h4 class="modal-title" id="global-modal-title">'.$message.'</h4>
        </div>';
}

if (HOT_SPOT_DELINEATION === $answerType) {
    $contents = $manageAnswerHtmlContent;
}
$links = '';
if (EXERCISE_FEEDBACK_TYPE_DIRECT === $objExercise->getFeedbackType()) {
    if (isset($choiceValue) && -1 == $choiceValue) {
        if (HOT_SPOT_DELINEATION != $answerType) {
            $links .= '<a href="#" onclick="tb_remove();">'.get_lang('Choose an answer').'</a><br />';
        }
    }
}

$destinationId = null;
if (isset($result['answer_destination'])) {
    $itemList = explode('@@', $result['answer_destination']);
    $try = $itemList[0];
    $lp = $itemList[1];
    $destinationId = $itemList[2];
    $url = $itemList[3];
}

// the link to retry the question
if (isset($try) && 1 == $try) {
    $num_value_array = array_keys($questionList, $questionId);
    $links .= Display::getMdiIcon(
        ActionIcon::REFRESH,
        'ch-tool-icon',
        'padding-left:0px;padding-right:5px;',
        ICON_SIZE_SMALL
    ).'<a onclick="SendEx('.$num_value_array[0].');" href="#">'.get_lang('Try again').'</a><br /><br />';
}

// the link to theory (a learning path)
if (!empty($lp)) {
    $lp_url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$lp;
    $links .= Display::getMdiIcon(
        ObjectIcon::DOCUMENT,
        'ch-tool-icon',
        'padding-left:0px;padding-right:5px;',
        ICON_SIZE_SMALL
    ).'<a target="_blank" href="'.$lp_url.'">'.get_lang('Theory link').'</a><br />';
}

$links .= '<br />';

// the link to an external website or link
if (!empty($url) && -1 != $url) {
    $links .= Display::getMdiIcon(
        ObjectIcon::LINK,
        'ch-tool-icon',
        'padding-left:0px;padding-right:5px;',
        ICON_SIZE_SMALL
    ).'<a target="_blank" href="'.$url.'">'.get_lang('Visit this link').'</a><br /><br />';
}

$nextQuestion = $questionNum + 1;
$destinationId = isset($questionList[$nextQuestion]) ? $questionList[$nextQuestion] : -1;

// the link to finish the test
if (-1 == $destinationId) {
    $links .= Display::getMdiIcon(
        StateIcon::COMPLETE,
        'ch-tool-icon',
        'width:22px; height:22px; padding-left:0px;padding-right:5px;',
        ICON_SIZE_SMALL
    ).'<a onclick="SendEx(-1);" href="#">'.get_lang('End of activity').'</a><br /><br />';
} else {
    // the link to other question
    if (in_array($destinationId, $questionList)) {
        $num_value_array = array_keys($questionList, $destinationId);
        $icon = Display::getMdiIcon(
            ObjectIcon::TEST,
            'ch-tool-icon',
            'padding-left:0px;padding-right:5px;',
            ICON_SIZE_SMALL
        );
        $links .= '<a onclick="SendEx('.$num_value_array[0].');" href="#">'.
                get_lang('Question').' '.$num_value_array[0].'</a>&nbsp;';
        $links .= $icon;
    }
}

if (!empty($links)) {
    echo $header;
    echo '<div>'.$contents.'</div>';
    echo '<div style="padding-left: 450px"><h5>'.$links.'</h5></div>';
    echo '</div>';
} else {
    $questionNum++;
    echo '<script>
            window.location.href = "exercise_submit.php?exerciseId='.$exerciseId.'&num='.$questionNum.'&'.api_get_cidreq().'";
        </script>';
}
echo '</div>';

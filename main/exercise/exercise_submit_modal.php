<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * @package chamilo.exercise
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
require_once __DIR__.'/../inc/global.inc.php';

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
$loaded = isset($_GET['loaded']);

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

echo '<script>
function tryAgain() {
    $(function () {
        $("#global-modal").modal("hide");
    });
}

function SendEx(num) {
    if (num == -1) {
        window.location.href = "exercise_result.php?'.api_get_cidreq().'&take_session=1&exerciseId='.$exerciseId.'&num="+num+"&learnpath_item_id='.$learnpath_item_id.'&learnpath_id='.$learnpath_id.'";
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
    $icon = Display::return_icon(
        'reload.png',
        '',
        ['style' => 'width:22px; height:22px; padding-left:0px;padding-right:5px;']
    );
    $links = '<a onclick="tryAgain();" href="#">'.get_lang('Try again').'</a>&nbsp;'.$icon.'&nbsp;';

    // the link to finish the test
    if ($destinationId == -1) {
        $links .= Display::return_icon(
                'finish.gif',
                '',
                ['style' => 'width:22px; height:22px; padding-left:0px;padding-right:5px;']
            ).'<a onclick="SendEx(-1);" href="#">'.get_lang('End of activity').'</a><br /><br />';
    } else {
        // the link to other question
        if (in_array($destinationId, $questionList)) {
            $num_value_array = array_keys($questionList, $destinationId);
            $icon = Display::return_icon(
                'quiz.png',
                '',
                ['style' => 'padding-left:0px;padding-right:5px;']
            );
            $links .= '<a onclick="SendEx('.$num_value_array[0].');" href="#">'.
                get_lang('Question').' '.$num_value_array[0].'</a>&nbsp;';
            $links .= $icon;
        }
    }
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

// if the user has answered at least one question
if (is_array($choice)) {
    if (in_array($exerciseType, [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP])) {
        // $exerciseResult receives the content of the form.
        // Each choice of the student is stored into the array $choice
        $exerciseResult = $choice;
    } else {
        // gets the question ID from $choice. It is the key of the array
        list($key) = array_keys($choice);
        // if the user didn't already answer this question
        if (!isset($exerciseResult[$key])) {
            // stores the user answer into the array
            $exerciseResult[$key] = $choice[$key];
        }
    }
}

// the script "exercise_result.php" will take the variable $exerciseResult from the session
Session::write('exerciseResult', $exerciseResult);

$objQuestionTmp = Question::read($questionId);
$answerType = $objQuestionTmp->selectType();
$showResult = false;

$objAnswerTmp = new Answer($questionId, api_get_course_int_id());
if ($objExercise->getFeedbackType() === EXERCISE_FEEDBACK_TYPE_DIRECT) {
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
        /*$_SESSION['calculatedAnswerId'][$questionId] = mt_rand(
            1,
            $nbrAnswers
        );*/
        //var_dump($_SESSION['calculatedAnswerId'][$questionId]);
        break;
}

ob_start();
$result = $objExercise->manage_answer(
    0,
    $questionId,
    $choiceValue,
    'exercise_result',
    null,
    false,
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
if (!empty($result)) {
    switch ($answerType) {
        case UNIQUE_ANSWER:
        case MULTIPLE_ANSWER:
        case DRAGGABLE:
        case HOT_SPOT_DELINEATION:
        case CALCULATED_ANSWER:
            if ($result['score'] == $result['weight']) {
                $answerCorrect = true;
            }
            break;
    }
}

if ($objExercise->getFeedbackType() === EXERCISE_FEEDBACK_TYPE_DIRECT) {
    if (isset($result['correct_answer_id'])) {
        /** @var Answer $answer */
        $answerId = $result['correct_answer_id'];
        $contents = $objAnswerTmp->selectComment($answerId);
    }
} else {
    $contents = Display::return_message(get_lang('Incorrect'), 'warning');
    if ($answerCorrect) {
        $contents = Display::return_message(get_lang('Correct'), 'success');
    }
}

if ($answerType === HOT_SPOT_DELINEATION) {
    $contents = $manageAnswerHtmlContent;
}
$links = '';
if ($objExercise->getFeedbackType() === EXERCISE_FEEDBACK_TYPE_DIRECT) {
    if (isset($choiceValue) && $choiceValue == -1) {
        if ($answerType != HOT_SPOT_DELINEATION) {
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
if (isset($try) && $try == 1) {
    $num_value_array = array_keys($questionList, $questionId);
    $links .= Display:: return_icon(
        'reload.gif',
        '',
        ['style' => 'padding-left:0px;padding-right:5px;']
    ).'<a onclick="SendEx('.$num_value_array[0].');" href="#">'.get_lang('Try again').'</a><br /><br />';
}

// the link to theory (a learning path)
if (!empty($lp)) {
    $lp_url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$lp;
    $links .= Display:: return_icon(
        'theory.gif',
        '',
        ['style' => 'padding-left:0px;padding-right:5px;']
    ).'<a target="_blank" href="'.$lp_url.'">'.get_lang('Theory link').'</a><br />';
}

$links .= '<br />';

// the link to an external website or link
if (!empty($url) && $url != -1) {
    $links .= Display:: return_icon(
        'link.gif',
        '',
        ['style' => 'padding-left:0px;padding-right:5px;']
    ).'<a target="_blank" href="'.$url.'">'.get_lang('Visit this link').'</a><br /><br />';
}

$nextQuestion = $questionNum + 1;
$destinationId = isset($questionList[$nextQuestion]) ? $questionList[$nextQuestion] : -1;

// the link to finish the test
if ($destinationId == -1) {
    $links .= Display:: return_icon(
        'finish.gif',
        '',
        ['style' => 'width:22px; height:22px; padding-left:0px;padding-right:5px;']
    ).'<a onclick="SendEx(-1);" href="#">'.get_lang('End of activity').'</a><br /><br />';
} else {
    // the link to other question
    if (in_array($destinationId, $questionList)) {
        $num_value_array = array_keys($questionList, $destinationId);
        $icon = Display::return_icon(
                'quiz.png',
                '',
                ['style' => 'padding-left:0px;padding-right:5px;']
        );
        $links .= '<a onclick="SendEx('.$num_value_array[0].');" href="#">'.
                get_lang('Question').' '.$num_value_array[0].'</a>&nbsp;';
        $links .= $icon;
    }
}

if (!empty($links)) {
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

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

$message = null;
$dbg_local = 0;
$gradebook = null;
$final_overlap = null;
$final_missing = null;
$final_excess = null;
$threadhold1 = null;
$threadhold2 = null;
$threadhold3 = null;

$exerciseResult = Session::read('exerciseResult');
$exerciseResultCoordinates = isset($_REQUEST['exerciseResultCoordinates']) ? $_REQUEST['exerciseResultCoordinates'] : null;

$learnpath_id = isset($_REQUEST['learnpath_id']) ? (int) $_REQUEST['learnpath_id'] : 0;
$learnpath_item_id = isset($_REQUEST['learnpath_item_id']) ? (int) $_REQUEST['learnpath_item_id'] : 0;

/** @var Exercise $objExercise */
$objExercise = Session::read('objExercise');

if (empty($objExercise)) {
    api_not_allowed();
}

Session::write('hotspot_coord', []);
$newQuestionList = Session::read('newquestionList', []);
$questionList = Session::read('questionList');

$exerciseId = (int) $_GET['exerciseId'];
$exerciseType = (int) $_GET['exerciseType'];
$questionNum = (int) $_GET['num'];
$nbrQuestions = isset($_GET['nbrQuestions']) ? (int) $_GET['nbrQuestions'] : null;
$questionId = $questionList[$questionNum];

// Clean extra session variables
Session::erase('objExerciseExtra'.$exerciseId);
Session::erase('exerciseResultExtra'.$exerciseId);
Session::erase('questionListExtra'.$exerciseId);
$choiceValue = isset($_GET['choice']) ? $_GET['choice'] : '';
$hotSpot = isset($_GET['hotspot']) ? $_GET['hotspot'] : '';

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

echo '<div id="delineation-container">';
// Getting the options by js
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
        //var my_choiceDc = $('*[name*=\"choiceDegreeCertainty['+question_id+']\"]').serialize();        
    ";

    $url = api_get_path(WEB_CODE_PATH).'exercise/exercise_submit_modal.php';
    // IMPORTANT
    // This is the real redirect function
    $extraUrl = '&exerciseId='.$exerciseId.'&num='.$questionNum.'&exerciseType='.$exerciseType.'&'.api_get_cidreq().'learnpath_id='.$learnpath_id.'&learnpath_item_id='.$learnpath_item_id;
    echo ' url = "'.addslashes($url).'?hotspotcoord="+ hotspotcoord + "&"+ hotspot + "&"+ my_choice + "&'.$extraUrl.'";';
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
Session::write('exerciseResultCoordinates', $exerciseResultCoordinates);

// creates a temporary Question object
if (in_array($questionId, $questionList)) {
    $objQuestionTmp = Question::read($questionId);
    $questionName = $objQuestionTmp->selectTitle();
    $questionDescription = $objQuestionTmp->selectDescription();
    $questionWeighting = $objQuestionTmp->selectWeighting();
    $answerType = $objQuestionTmp->selectType();
    $quesId = $objQuestionTmp->selectId(); //added by priya saini
}

$objAnswerTmp = new Answer($questionId);
$nbrAnswers = $objAnswerTmp->selectNbrAnswers();
$choice = $exerciseResult[$questionId];
$destination = [];
$comment = '';
$next = 1;
Session::write('hotspot_coord', []);
Session::write('hotspot_dest', []);
$overlap_color = $missing_color = $excess_color = false;
$organs_at_risk_hit = 0;
$wrong_results = false;
$hot_spot_load = false;
$questionScore = 0;
$totalScore = 0;
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
            $_SESSION['hotspot_coord'][1] = $delineation_cord;
            $_SESSION['hotspot_dest'][1] = $answer_delineation_destination;
        }
        break;
    case CALCULATED_ANSWER:
        $_SESSION['calculatedAnswerId'][$questionId] = mt_rand(
            1,
            $nbrAnswers
        );
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

if ($showResult) {
    /*echo $objQuestionTmp->return_header(
        $objExercise,
        $questionNum,
        []
    );
    echo $manageAnswerHtmlContent;*/
}

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
    if ($answerType === HOT_SPOT_DELINEATION) {
        $contents = $manageAnswerHtmlContent;
    }
} else {
    $contents = Display::return_message(get_lang('Incorrect'), 'warning');
    if ($answerCorrect) {
        $contents = Display::return_message(get_lang('Correct'), 'success');
    }
}

Session::write('newquestionList', $newQuestionList);
$links = '';
if ($objExercise->getFeedbackType() === EXERCISE_FEEDBACK_TYPE_DIRECT) {
    if (isset($choiceValue) && $choiceValue == -1) {
        if ($answerType != HOT_SPOT_DELINEATION) {
            $links .= '<a href="#" onclick="tb_remove();">'.get_lang('ChooseAnAnswer').'</a><br />';
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
    ).'<a onclick="SendEx('.$num_value_array[0].');" href="#">'.get_lang('TryAgain').'</a><br /><br />';
}

// the link to theory (a learning path)
if (!empty($lp)) {
    $lp_url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$lp;
    /*$list = new LearnpathList(api_get_user_id());
    $flat_list = $list->get_flat_list();*/
    $links .= Display:: return_icon(
        'theory.gif',
        '',
        ['style' => 'padding-left:0px;padding-right:5px;']
    ).'<a target="_blank" href="'.$lp_url.'">'.get_lang('SeeTheory').'</a><br />';
}

$links .= '<br />';

// the link to an external website or link
if (!empty($url) && $url <> -1) {
    $links .= Display:: return_icon(
        'link.gif',
        '',
        ['style' => 'padding-left:0px;padding-right:5px;']
    ).'<a target="_blank" href="'.$url.'">'.get_lang('VisitUrl').'</a><br /><br />';
}

//if ($objExercise->getFeedbackType() === EXERCISE_FEEDBACK_TYPE_POPUP) {
    $nextQuestion = $questionNum + 1;
    $destinationId = isset($questionList[$nextQuestion]) ? $questionList[$nextQuestion] : -1;
//}

// the link to finish the test
if ($destinationId == -1) {
    $links .= Display:: return_icon(
        'finish.gif',
        '',
        ['style' => 'width:22px; height:22px; padding-left:0px;padding-right:5px;']
    ).'<a onclick="SendEx(-1);" href="#">'.get_lang('EndActivity').'</a><br /><br />';
} else {
    // the link to other question
    if (in_array($destinationId, $questionList)) {
        /*$objQuestionTmp = Question::read($destinationId);
        $questionName = $objQuestionTmp->selectTitle();*/
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

echo '<script>
function SendEx(num) {
    if (num == -1) {
        window.location.href = "exercise_result.php?'.api_get_cidreq().'&take_session=1&exerciseId='.$exerciseId.'&num="+num+"&exerciseType='.$exerciseType.'&learnpath_item_id='.$learnpath_item_id.'&learnpath_id='.$learnpath_id.'";
    } else {
        num -= 1;
        window.location.href = "exercise_submit.php?'.api_get_cidreq().'&tryagain=1&exerciseId='.$exerciseId.'&num="+num+"&exerciseType='.$exerciseType.'&learnpath_item_id='.$learnpath_item_id.'&learnpath_id='.$learnpath_id.'";
    }    
    return false;
}
</script>';

if (!empty($links)) {
    echo '<div>'.$contents.'</div>';
    echo '<div style="padding-left: 450px"><h5>'.$links.'</h5></div>';
    echo '</div>';

    Session::write('hot_spot_result', $message);
    $_SESSION['hotspot_delineation_result'][$exerciseId][$questionId] = [$message, $exerciseResult[$questionId]];
    // Resetting the exerciseResult variable
    Session::write('exerciseResult', $exerciseResult);

    // Save this variables just in case the exercise loads an LP with other exercise
    Session::write('objExerciseExtra'.$exerciseId, Session::read('objExercise'));
    Session::write('exerciseResultExtra'.$exerciseId, Session::read('exerciseResult'));
    Session::write('questionListExtra'.$exerciseId, Session::read('questionList'));
} else {
    $questionNum++;
    echo '<script>
            window.location.href = "exercise_submit.php?exerciseId='.$exerciseId.'&num='.$questionNum.'&exerciseType='.$exerciseType.'&'.api_get_cidreq().'";
        </script>';
}
echo '</div>';

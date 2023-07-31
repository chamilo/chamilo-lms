<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;
use Symfony\Component\HttpFoundation\Request;

/**
 * This script allows to manage answers. It is included from the
 * script admin.php.
 *
 * @package chamilo.exercise
 *
 * @author  Toon Keppens
 */
$modifyAnswers = (int) $_GET['hotspotadmin'];
if (!is_object($objQuestion)) {
    $objQuestion = Question::read($modifyAnswers);
}

$questionName = $objQuestion->selectTitle();
$answerType = $objQuestion->selectType();
$pictureName = $objQuestion->getPictureFilename();

$debug = 0; // debug variable to get where we are
$okPicture = empty($pictureName) ? false : true;

$httpRequest = Request::createFromGlobals();
$reponse = $httpRequest->request->get('reponse');
$comment = $httpRequest->request->get('comment');
$weighting = $httpRequest->request->get('weighting');
$hotspot_coordinates = $httpRequest->request->get('hotspot_coordinates');
$hotspot_type = $httpRequest->request->get('hotspot_type');

// if we come from the warning box "this question is used in several exercises"
if ($modifyIn) {
    if ($debug > 0) {
        echo '$modifyIn was set'."<br />\n";
    }
    // if the user has chosen to modify the question only in the current exercise
    if ($modifyIn === 'thisExercise') {
        // duplicates the question
        $questionId = $objQuestion->duplicate();

        // deletes the old question
        $objQuestion->delete($exerciseId);

        // removes the old question ID from the question list of the Exercise object
        $objExercise->removeFromList($modifyAnswers);

        // adds the new question ID into the question list of the Exercise object
        $objExercise->addToList($questionId);

        // construction of the duplicated Question
        $objQuestion = Question::read($questionId);

        // adds the exercise ID into the exercise list of the Question object
        $objQuestion->addToList($exerciseId);

        // copies answers from $modifyAnswers to $questionId
        $objAnswer->duplicate($objQuestion);

        // construction of the duplicated Answers
        $objAnswer = new Answer($questionId);
    }

    $color = UnserializeApi::unserialize('not_allowed_classes', $color);
    $reponse = UnserializeApi::unserialize('not_allowed_classes', $reponse);
    $comment = UnserializeApi::unserialize('not_allowed_classes', $comment);
    $weighting = UnserializeApi::unserialize('not_allowed_classes', $weighting);
    $hotspot_coordinates = UnserializeApi::unserialize('not_allowed_classes', $hotspot_coordinates);
    $hotspot_type = UnserializeApi::unserialize('not_allowed_classes', $hotspot_type);
    $destination = UnserializeApi::unserialize('not_allowed_classes', $destination);
    unset($buttonBack);
}

$hotspot_admin_url = api_get_path(WEB_CODE_PATH).'exercise/admin.php?'.api_get_cidreq().'&exerciseId='.$exerciseId;

// the answer form has been submitted
$submitAnswers = isset($_POST['submitAnswers']) ? true : false;
$buttonBack = isset($_POST['buttonBack']) ? true : false;
$nbrAnswers = isset($_POST['nbrAnswers']) ? (int) $_POST['nbrAnswers'] : 0;

if ($submitAnswers || $buttonBack) {
    if (in_array($answerType, [HOT_SPOT, HOT_SPOT_COMBINATION])) {
        if ($debug > 0) {
            echo '$submitAnswers or $buttonBack was set'."<br />\n";
        }
        $questionWeighting = $nbrGoodAnswers = 0;
        for ($i = 1; $i <= $nbrAnswers; $i++) {
            if ($debug > 0) {
                echo str_repeat('&nbsp;', 4).'$answerType is HOT_SPOT'."<br />\n";
            }

            $reponse[$i] = trim($reponse[$i]);
            $comment[$i] = trim($comment[$i]);
            $weighting[$i] = $weighting[$i]; // it can be float

            // checks if field is empty
            if (empty($reponse[$i]) && $reponse[$i] != '0') {
                $msgErr = get_lang('HotspotGiveAnswers');

                // clears answers already recorded into the Answer object
                $objAnswer->cancel();
                break;
            }

            if ($weighting[$i] <= 0) {
                $msgErr = get_lang('HotspotWeightingError');
                // clears answers already recorded into the Answer object
                $objAnswer->cancel();
                break;
            }

            if ($hotspot_coordinates[$i] == '0;0|0|0' || empty($hotspot_coordinates[$i])) {
                $msgErr = get_lang('HotspotNotDrawn');
                // clears answers already recorded into the Answer object
                $objAnswer->cancel();
                break;
            }
        }  // end for()

        if (empty($msgErr)) {
            for ($i = 1; $i <= $nbrAnswers; $i++) {
                if ($debug > 0) {
                    echo str_repeat('&nbsp;', 4).'$answerType is HOT_SPOT'."<br />\n";
                }

                $reponse[$i] = trim($reponse[$i]);
                $comment[$i] = trim($comment[$i]);
                $weighting[$i] = $weighting[$i]; // It can be float.

                if ($weighting[$i]) {
                    $questionWeighting += $weighting[$i];
                }

                if (HOT_SPOT_COMBINATION == $answerType) {
                    $weighting[$i] = 0;
                }

                // creates answer
                $objAnswer->createAnswer(
                    $reponse[$i],
                    '',
                    $comment[$i],
                    $weighting[$i],
                    $i,
                    $hotspot_coordinates[$i],
                    $hotspot_type[$i]
                );
            }  // end for()
            // saves the answers into the data base
            $objAnswer->save();

            // sets the total weighting of the question
            if (HOT_SPOT_COMBINATION == $answerType) {
                $questionWeighting = $httpRequest->request->get('questionWeighting');
            }
            $objQuestion->updateWeighting($questionWeighting);
            $objQuestion->save($objExercise);

            $editQuestion = $questionId;
            unset($modifyAnswers);
            echo '<script type="text/javascript">window.location.href="'.$hotspot_admin_url
                .'&message=ItemUpdated"</script>';
        }

        if ($debug > 0) {
            echo '$modifyIn was set - end'."<br />\n";
        }
    } else {
        if ($debug > 0) {
            echo '$submitAnswers or $buttonBack was set'."<br />\n";
        }

        $questionWeighting = $nbrGoodAnswers = 0;
        $select_question = isset($_POST['select_question']) ? $_POST['select_question'] : null;
        $try = isset($_POST['try']) ? $_POST['try'] : [];
        $url = isset($_POST['url']) ? $_POST['url'] : '';
        $destination = [];

        $threadhold1 = $_POST['threadhold1'];
        $threadhold2 = $_POST['threadhold2'];
        $threadhold3 = $_POST['threadhold3'];

        for ($i = 1; $i <= $nbrAnswers; $i++) {
            $reponse[$i] = trim($reponse[$i]);
            $comment[$i] = trim($comment[$i]);
            $weighting[$i] = $weighting[$i];

            if (empty($threadhold1[$i])) {
                $threadhold1_str = 0;
            } else {
                $threadhold1_str = (int) $threadhold1[$i];
            }

            if (empty($threadhold2[$i])) {
                $threadhold2_str = 0;
            } else {
                $threadhold2_str = (int) $threadhold2[$i];
            }

            if (empty($threadhold3[$i])) {
                $threadhold3_str = 0;
            } else {
                $threadhold3_str = (int) $threadhold3[$i];
            }

            $threadhold_total = $threadhold1_str.';'.$threadhold2_str.';'.$threadhold3_str;

            if (isset($try[$i]) && $try[$i] == 'on') {
                $try_str = 1;
            } else {
                $try_str = 0;
            }

            if (empty($lp[$i])) {
                $lp_str = 0;
            } else {
                $lp_str = $lp[$i];
            }

            $url_str = '';
            if (isset($url[$i]) && !empty($url[$i])) {
                $url_str = $url[$i];
            }

            if ($select_question[$i] == '') {
                $question_str = 0;
            } else {
                $question_str = $select_question[$i];
            }

            $destination[$i] = $threadhold_total.'@@'.$try_str.'@@'.$lp_str.'@@'.$question_str.'@@'.$url_str;

            // checks if field is empty
            if (empty($reponse[$i]) && $reponse[$i] != '0') {
                $msgErr = get_lang('HotspotGiveAnswers');

                // clears answers already recorded into the Answer object
                $objAnswer->cancel();
                break;
            }

            if ($weighting[$i] <= 0 && $_SESSION['tmp_answers']['hotspot_type'][$i] != 'oar') {
                $msgErr = get_lang('HotspotWeightingError');
                // clears answers already recorded into the Answer object
                $objAnswer->cancel();
                break;
            }

            if ($hotspot_coordinates[$i] == '0;0|0|0' || empty($hotspot_coordinates[$i])) {
                $msgErr = get_lang('HotspotNotDrawn');
                // clears answers already recorded into the Answer object
                $objAnswer->cancel();
                break;
            }
        }

        // now the noerror section
        $selectQuestionNoError = isset($_POST['select_question_noerror']) ? Security::remove_XSS($_POST['select_question_noerror']) : null;
        $lp_noerror = isset($_POST['lp_noerror']) ? Security::remove_XSS($_POST['lp_noerror']) : '';
        $try_noerror = isset($_POST['try_noerror']) ? Security::remove_XSS($_POST['try_noerror']) : null;
        $url_noerror = isset($_POST['url_noerror']) ? Security::remove_XSS($_POST['url_noerror']) : null;
        $comment_noerror = isset($_POST['comment_noerror']) ? Security::remove_XSS($_POST['comment_noerror']) : null;
        $threadhold_total = '0;0;0';

        if ($try_noerror == 'on') {
            $try_str = 1;
        } else {
            $try_str = 0;
        }

        if (empty($lp_noerror)) {
            $lp_str = 0;
        } else {
            $lp_str = $lp_noerror;
        }

        if ($url_noerror == '') {
            $url_str = '';
        } else {
            $url_str = $url_noerror;
        }

        if ($selectQuestionNoError == '') {
            $question_str = 0;
        } else {
            $question_str = $selectQuestionNoError;
        }

        $destination_noerror = $threadhold_total.'@@'.$try_str.'@@'.$lp_str.'@@'.$question_str.'@@'.$url_str;

        if (empty($msgErr)) {
            for ($i = 1; $i <= $nbrAnswers; $i++) {
                if ($debug > 0) {
                    echo str_repeat('&nbsp;', 4).'$answerType is HOT_SPOT'."<br />\n";
                }

                $reponse[$i] = trim($reponse[$i]);
                $comment[$i] = trim($comment[$i]);
                $weighting[$i] = ($weighting[$i]); //it can be float

                if ($weighting[$i]) {
                    $questionWeighting += $weighting[$i];
                }

                // creates answer
                $objAnswer->createAnswer(
                    $reponse[$i],
                    '',
                    $comment[$i],
                    $weighting[$i],
                    $i,
                    $hotspot_coordinates[$i],
                    $hotspot_type[$i],
                    $destination[$i]
                );
            }  // end for()
            // saves the answers into the data base

            $objAnswer->createAnswer(
                'noerror',
                '',
                $comment_noerror,
                '0',
                $nbrAnswers + 1,
                null,
                'noerror',
                $destination_noerror
            );
            $objAnswer->save();

            // sets the total weighting of the question
            $objQuestion->updateWeighting($questionWeighting);
            $objQuestion->save($objExercise);

            $editQuestion = $questionId;
            unset($modifyAnswers);
            echo '<script type="text/javascript">window.location.href="'.$hotspot_admin_url
                .'&message=ItemUpdated"</script>';
        }
    }
}

if (isset($modifyAnswers)) {
    if ($debug > 0) {
        echo str_repeat('&nbsp;', 0).'$modifyAnswers is set'."<br />\n";
    }

    // construction of the Answer object
    $objAnswer = new Answer($objQuestion->iid);
    Session::write('objAnswer', $objAnswer);

    if ($debug > 0) {
        echo str_repeat('&nbsp;', 2).'$answerType is HOT_SPOT'."<br />\n";
    }

    if ($answerType == HOT_SPOT_DELINEATION) {
        $try = isset($_POST['try']) ? $_POST['try'] : [];
        for ($i = 1; $i <= $nbrAnswers; $i++) {
            if (isset($try[$i]) && $try[$i] == 'on') {
                $try[$i] = 1;
            } else {
                $try[$i] = 0;
            }
        }

        if (isset($_POST['try_noerror']) && $_POST['try_noerror'] == 'on') {
            $try_noerror = 1;
        } else {
            $try_noerror = 0;
        }
    }

    if (!$nbrAnswers) {
        $nbrAnswers = $objAnswer->selectNbrAnswers();
        if ($answerType == HOT_SPOT_DELINEATION) {
            // the magic happens here ...
            // we do this to not count the if no error section
            if ($nbrAnswers >= 2) {
                $nbrAnswers--;
            }
        }

        $reponse = [];
        $comment = [];
        $weighting = [];
        $hotspot_coordinates = [];
        $hotspot_type = [];
        $destination_items = [];
        $destination = [];

        for ($i = 1; $i <= $nbrAnswers; $i++) {
            $reponse[$i] = $objAnswer->selectAnswer($i);

            if ($objExercise->getFeedbackType() != EXERCISE_FEEDBACK_TYPE_EXAM) {
                $comment[$i] = $objAnswer->selectComment($i);
            }

            $weighting[$i] = $objAnswer->selectWeighting($i);
            $hotspot_coordinates[$i] = $objAnswer->selectHotspotCoordinates($i);
            $hotspot_type[$i] = $objAnswer->selectHotspotType($i);

            if ($answerType == HOT_SPOT_DELINEATION) {
                $destination[$i] = $objAnswer->selectDestination($i);

                if (empty($destination[$i])) {
                    $destination[$i] = ';;;@@@@@@@@';
                }

                $destination_items = explode('@@', $destination[$i]);
                $threadhold_total = $destination_items[0];
                $threadhold_items = explode(';', $threadhold_total);
                $threadhold1[$i] = $threadhold_items[0];
                $threadhold2[$i] = $threadhold_items[1];
                $threadhold3[$i] = $threadhold_items[2];

                $try[$i] = $destination_items[1];
                $lp[$i] = $destination_items[2];
                $select_question[$i] = $destination_items[3];
                $url[$i] = $destination_items[4];
            }
        }
    }

    if ($answerType == HOT_SPOT_DELINEATION) {
        //added the noerror answer
        $comment_noerror = $objAnswer->selectComment($nbrAnswers + 1);
        $destination_noerror_list = $objAnswer->selectDestination($nbrAnswers + 1);

        if (empty($destination_noerror_list)) {
            $destination_noerror_list = '@@@@@@@@';
        }

        $destination_items = explode('@@', $destination_noerror_list);
        $try_noerror = $destination_items[1];
        $lp_noerror = $destination_items[2];
        $selectQuestionNoError = $destination_items[3];
        $url_noerror = $destination_items[4];
    }

    $_SESSION['tmp_answers'] = [];
    $_SESSION['tmp_answers']['answer'] = $reponse;

    if ($objExercise->getFeedbackType() != EXERCISE_FEEDBACK_TYPE_EXAM) {
        $_SESSION['tmp_answers']['comment'] = $comment;
    }

    $_SESSION['tmp_answers']['weighting'] = $weighting;
    $_SESSION['tmp_answers']['hotspot_coordinates'] = $hotspot_coordinates;
    $_SESSION['tmp_answers']['hotspot_type'] = $hotspot_type;

    if ($answerType == HOT_SPOT_DELINEATION) {
        $_SESSION['tmp_answers']['destination'] = isset($destination) ? $destination : null;
    }

    $lessAnswers = isset($_POST['lessAnswers']) ? true : false;
    if ($lessAnswers) {
        if ($answerType == HOT_SPOT_DELINEATION) {
            $lest_answer = 1;
            // At least 1 answer
            if ($nbrAnswers > $lest_answer) {
                $nbrAnswers--;
                // Remove the last answer
                $tmp = array_pop($_SESSION['tmp_answers']['answer']);
                $tmp = array_pop($_SESSION['tmp_answers']['comment']);
                $tmp = array_pop($_SESSION['tmp_answers']['weighting']);
                $tmp = array_pop($_SESSION['tmp_answers']['hotspot_coordinates']);
                $tmp = array_pop($_SESSION['tmp_answers']['hotspot_type']);

                if (is_array($_SESSION['tmp_answers']['destination'])) {
                    $tmp = array_pop($_SESSION['tmp_answers']['destination']);
                }
            } else {
                $msgErr = get_lang('MinHotspot');
            }
        } else {
            // At least 1 answer
            if ($nbrAnswers > 1) {
                $nbrAnswers--;
                // Remove the last answer
                $tmp = array_pop($_SESSION['tmp_answers']['answer']);
                if ($objExercise->getFeedbackType() != EXERCISE_FEEDBACK_TYPE_EXAM) {
                    $tmp = array_pop($_SESSION['tmp_answers']['comment']);
                }
                $tmp = array_pop($_SESSION['tmp_answers']['weighting']);
                $tmp = array_pop($_SESSION['tmp_answers']['hotspot_coordinates']);
                $tmp = array_pop($_SESSION['tmp_answers']['hotspot_type']);
            } else {
                $msgErr = get_lang('MinHotspot');
            }
        }
    }

    $moreAnswers = isset($_POST['moreAnswers']) ? true : false;

    if ($moreAnswers) {
        if ($nbrAnswers < 12) {
            $nbrAnswers++;

            // Add a new answer
            $_SESSION['tmp_answers']['answer'][] = '';
            if ($objExercise->getFeedbackType() != EXERCISE_FEEDBACK_TYPE_EXAM) {
                $_SESSION['tmp_answers']['comment'][] = '';
            }
            $_SESSION['tmp_answers']['weighting'][] = '1';
            $_SESSION['tmp_answers']['hotspot_coordinates'][] = '0;0|0|0';
            $_SESSION['tmp_answers']['hotspot_type'][] = 'square';
            $_SESSION['tmp_answers']['destination'][] = '';
        } else {
            $msgErr = get_lang('MaxHotspot');
        }
    }

    $moreOARAnswers = isset($_POST['moreOARAnswers']) ? true : false;

    if ($moreOARAnswers) {
        if ($nbrAnswers < 12) {
            // Add a new answer
            $nbrAnswers++;

            $_SESSION['tmp_answers']['answer'][] = '';
            $_SESSION['tmp_answers']['comment'][] = '';
            $_SESSION['tmp_answers']['weighting'][] = '1';
            $_SESSION['tmp_answers']['hotspot_coordinates'][] = '0;0|0|0';
            $_SESSION['tmp_answers']['hotspot_type'][] = 'oar';
            $_SESSION['tmp_answers']['destination'][] = '';
        } else {
            $msgErr = get_lang('MaxHotspot');
        }
    }

    if ($debug > 0) {
        echo str_repeat('&nbsp;', 2).'$usedInSeveralExercises is untrue'."<br />\n";
    }

    if ($debug > 0) {
        echo str_repeat('&nbsp;', 4).'$answerType is HOT_SPOT'."<br />\n";
    }

    if ($answerType == HOT_SPOT_DELINEATION) {
        $hotspot_colors = [
            "",
            "#4271B5",
            "#FE8E16",
            "#45C7F0",
            "#BCD631",
            "#D63173",
            "#D7D7D7",
            "#90AFDD",
            "#AF8640",
            "#4F9242",
            "#F4EB24",
            "#ED2024",
            "#3B3B3B",
        ];
    } else {
        $hotspot_colors = [
            "", // $i starts from 1 on next loop (ugly fix)
            "#4271B5",
            "#FE8E16",
            "#45C7F0",
            "#BCD631",
            "#D63173",
            "#D7D7D7",
            "#90AFDD",
            "#AF8640",
            "#4F9242",
            "#F4EB24",
            "#ED2024",
            "#3B3B3B",
            "#F7BDE2",
        ];
    }

    Display::tag(
        'h3',
        get_lang('Question').": ".$questionName.Display::return_icon('info3.gif', strip_tags(get_lang('HotspotChoose')))
    );

    if (!empty($msgErr)) {
        echo Display::return_message($msgErr, 'normal'); //main API
    }

    $hotspot_admin_url = api_get_path(WEB_CODE_PATH).'exercise/admin.php?'.api_get_cidreq().'&'
        .http_build_query(['hotspotadmin' => $modifyAnswers, 'exerciseId' => $exerciseId]); ?>
    <form method="post" action="<?php echo $hotspot_admin_url; ?>" class="form-horizontal" id="frm_exercise"
          name="frm_exercise">
        <div class="form-group">
            <div class="col-sm-12">
                <?php if ($answerType == HOT_SPOT_DELINEATION) {
            ?>
                    <button type="submit" class="btn btn-danger" name="lessAnswers" value="lessAnswers">
                        <em class="fa fa-trash"></em> <?php echo get_lang('LessOAR'); ?>
                    </button>
                    <button type="submit" class="btn btn-primary" name="moreOARAnswers" value="moreOARAnswers">
                        <em class="fa fa-plus"></em> <?php echo get_lang('MoreOAR'); ?>
                    </button>
                <?php
        } else {
            ?>
                    <button type="submit" class="btn btn-danger" name="lessAnswers" value="lessAnswers">
                        <em class="fa fa-trash"></em> <?php echo get_lang('LessHotspots'); ?>
                    </button>
                    <button type="submit" class="btn btn-primary" name="moreAnswers" value="moreAnswers">
                        <em class="fa fa-plus"></em> <?php echo get_lang('MoreHotspots'); ?>
                    </button>
                <?php
        } ?>
                <button type="submit" class="btn btn-primary" name="submitAnswers" value="submitAnswers">
                    <em class="fa fa-save"></em> <?php echo get_lang('AddQuestionToExercise'); ?>
                </button>
            </div>
        </div>
        <input type="hidden" name="formSent" value="1"/>
        <input type="hidden" name="nbrAnswers" value="<?php echo $nbrAnswers; ?>"/>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                <tr>
                    <th width="5">&nbsp;</th>
                    <th><?php echo get_lang('HotspotDescription'); ?> *</th>
                    <?php
                    if ($objExercise->getFeedbackType() == EXERCISE_FEEDBACK_TYPE_DIRECT) {
                        echo '<th>'.get_lang('Comment').'</th>';
                        if ($answerType == HOT_SPOT_DELINEATION) {
                            echo '<th >'.get_lang('Scenario').'</th>';
                        }
                    } else {
                        echo '<th colspan="2">'.get_lang('Comment').'</th>';
                    } ?>
                    <?php if (HOT_SPOT_COMBINATION !== $answerType) { ?>
                        <th><?php echo get_lang('QuestionWeighting'); ?> *</th>
                    <?php } ?>
                </tr>
                </thead>
                <tbody>
                <?php
                $list = new LearnpathList(api_get_user_id());
    // Loading list of LPs
    $flat_list = $list->get_flat_list();

    for ($i = 1; $i <= $nbrAnswers; $i++) {
        // is an delineation
        if ($answerType == HOT_SPOT_DELINEATION) {
            $option_lp = '';

            // setting the LP
            $isSelected = false;
            foreach ($flat_list as $id => $details) {
                $selected = '';
                if (isset($lp[$i]) && $id == $lp[$i]) {
                    $isSelected = true;
                    $selected = 'selected="selected"';
                }
                $option_lp .= '<option value="'.$id.'" '.$selected.'>'.$details['lp_name'].'</option>';
            }

            if ($isSelected) {
                $option_lp = '<option value="0">'.get_lang('SelectTargetLP').'</option>'.$option_lp;
            } else {
                $option_lp = '<option value="0" selected="selected" >'.get_lang('SelectTargetLP')
                                .'</option>'.$option_lp;
            }

            // Feedback SELECT
            $question_list = $objExercise->selectQuestionList();
            $option_feed = '';
            $option_feed .= '<option value="0">'.get_lang('SelectTargetQuestion').'</option>';

            foreach ($question_list as $key => $questionid) {
                $selected = '';
                $question = Question::read($questionid);
                $questionTitle = strip_tags($question->selectTitle());
                $val = "Q$key: $questionTitle";

                if (isset($select_question[$i]) && $questionid == $select_question[$i]) {
                    $selected = 'selected="selected"';
                }

                $option_feed .= '<option value="'.$questionid.'" '.$selected.' >'.$val.'</option>';
            }

            if (isset($select_question[$i]) && $select_question[$i] == -1) {
                $option_feed .= '<option value="-1" selected="selected" >'.get_lang('ExitTest').'</option>';
            } else {
                $option_feed .= '<option value="-1">'.get_lang('ExitTest').'</option>';
            }

            //-------- IF it is a delineation
            if ($_SESSION['tmp_answers']['hotspot_type'][$i] == 'delineation') {
                $option1 = $option2 = $option3 = '';
                for ($k = 1; $k <= 100; $k++) {
                    $selected1 = $selected2 = $selected3 = '';
                    if (isset($threadhold1[$i])) {
                        if ($k == $threadhold1[$i]) {
                            $selected1 = 'selected="selected"';
                        }
                    }
                    if (isset($threadhold2[$i])) {
                        if ($k == $threadhold2[$i]) {
                            $selected2 = 'selected="selected"';
                        }
                    }
                    if (isset($threadhold3[$i])) {
                        if ($k == $threadhold3[$i]) {
                            $selected3 = 'selected="selected"';
                        }
                    }
                    $option1 .= '<option '.$selected1.' >'.$k.' % </option>';
                    $option2 .= '<option '.$selected2.' >'.$k.' % </option>';
                    $option3 .= '<option '.$selected3.'>'.$k.' %</option>';
                } ?>
                            <tr>
                            <td>
                                <span class="fa fa-square fa-2x" aria-hidden="true"
                                      style="color: <?php echo $hotspot_colors[$i]; ?>;"></span>
                                <input type="hidden" name="reponse[<?php echo $i; ?>]" value="delineation"/>
                            </td>
                            <td>
                                <p><strong><?php echo get_lang('Delineation'); ?></strong></p>
                                <p>
                                    <?php echo get_lang('MinOverlap'); ?>
                                    <select class="form-control" name="threadhold1[<?php echo $i; ?>]">
                                        <?php echo $option1; ?>
                                    </select>
                                </p>
                                <p>
                                    <?php echo get_lang('MaxExcess'); ?>
                                    <select class="form-control" name="threadhold2[<?php echo $i; ?>]">
                                        <?php echo $option2; ?>
                                    </select>
                                </p>
                                <p>
                                    <?php echo get_lang('MaxMissing'); ?>
                                    <select class="form-control" name="threadhold3[<?php echo $i; ?>]">
                                        <?php echo $option3; ?>
                                    </select>
                                </p>
                            </td>
                            <td align="left">
                                <p>
                                    <textarea class="form-control" wrap="virtual" rows="3" cols="25"
                                              name="comment[<?php echo $i; ?>]"
                                              aria-describedBy="comment-<?php echo $i; ?>-help"><?php echo Security::remove_XSS($comment[$i]); ?></textarea>
                                    <span id="comment-<?php echo $i; ?>-help"
                                          class="help-block"><?php echo get_lang('LearnerIsInformed'); ?></span>
                                </p>
                                <input type="hidden" name="hotspot_type[<?php echo $i; ?>]" value="delineation"/>
                                <input type="hidden" name="hotspot_coordinates[<?php echo $i; ?>]" value="<?php
                                echo empty($hotspot_coordinates[$i]) ? '0;0|0|0' : $hotspot_coordinates[$i]; ?>"/>
                            </td>
                            <?php if ($objExercise->getFeedbackType() == EXERCISE_FEEDBACK_TYPE_DIRECT) {
                                    ?>
                                <td>
                                    <div class="checkbox">
                                        <p>
                                            <label>
                                                <input type="checkbox" class="checkbox" name="<?php echo 'try['.$i; ?>]"
                                                <?php if ($try[$i] == 1) {
                                        echo 'checked';
                                    } ?> />
                                                <?php echo get_lang('TryAgain'); ?>
                                            </label>
                                        </p>
                                    </div>
                                    <p>
                                        <?php echo get_lang('SeeTheory'); ?>
                                        <select class="form-control" name="lp[<?php echo $i; ?>]">
                                            <?php echo $option_lp; ?>
                                        </select>
                                    </p>
                                    <p>
                                        <?php echo get_lang('Other'); ?>
                                        <input class="form-control" name="url[<?php echo $i; ?>]"
                                               value="<?php echo $url[$i]; ?>">
                                    </p>
                                    <p>
                                        <?php echo get_lang('SelectQuestion'); ?>
                                        <select class="form-control" name="select_question[<?php echo $i; ?>]">
                                            <?php echo $option_feed; ?>
                                        </select>
                                    </p>
                                </td>
                                <?php
                                } else {
                                    ?>
                                <td> &nbsp;</td>
                                <?php
                                }
            } elseif (false) {
                ?>
                            <tr>
                                <th colspan="2"><?php echo get_lang('IfNoError'); ?></th>
                                <th colspan="3"><?php echo get_lang('Feedback'); ?></th>
                                <!-- th colspan="1" ><?php echo get_lang('Scenario'); ?></th -->
                                <th></th>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <?php echo get_lang('LearnerHasNoMistake'); ?>
                                    <input type="hidden" name="reponse[<?php echo $i; ?>]" value="noerror"/>
                                    <input type="hidden" name="weighting[<?php echo $i; ?>]" value="0"/>
                                    <input type="hidden" name="hotspot_type[<?php echo $i; ?>]" value="noerror"/>
                                    <input type="hidden" name="hotspot_coordinates[<?php echo $i; ?>]" value="0;0|0|0"/>
                                </td>
                                <td align="left">
                                    <textarea class="form-control" wrap="virtual" rows="3" cols="25"
                                              name="comment[<?php echo $i; ?>]"
                                              style="width: 100%"><?php echo Security::remove_XSS($comment[$i]); ?></textarea>
                                </td>
                                <?php if ($objExercise->getFeedbackType() == EXERCISE_FEEDBACK_TYPE_DIRECT) {
                    ?>
                                    <td>
                                        <table>
                                            <tr>
                                                <td>
                                                    <div class="checkbox">
                                                        <p>
                                                            <label>
                                                                <input type="checkbox" class="checkbox"
                                                                       name="<?php echo 'try['
                                                                           .$i; ?>]" <?php if ($try[$i] == 1) {
                                                                               echo 'checked';
                                                                           } ?> />
                                                                <?php echo get_lang('TryAgain'); ?>
                                                            </label>
                                                        </p>
                                                    </div>
                                                    <p>
                                                        <?php echo get_lang('SeeTheory'); ?>
                                                        <select class="form-control" name="lp[<?php echo $i; ?>]">
                                                            <?php echo $option_lp; ?>
                                                        </select>
                                                    </p>
                                                    <p>
                                                        <?php echo get_lang('Other'); ?> <br/>
                                                        <input class="form-control" name="url[<?php echo $i; ?>]"
                                                               value="<?php echo $url[$i]; ?>">
                                                    </p>
                                                    <p>
                                                        <?php echo get_lang('SelectQuestion'); ?> <br/>
                                                        <select class="form-control"
                                                                name="select_question[<?php echo $i; ?>]">
                                                            <?php echo $option_feed; ?>
                                                        </select>
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <?php
                } else {
                    ?>
                                    <td>&nbsp;</td>
                                    <?php
                } ?>
                            </tr>
                            <?php
            } elseif ($_SESSION['tmp_answers']['hotspot_type'][$i] == 'oar') {
                // if it's an OAR
                if ($i == 2) {
                    ?>
                                <tr>
                                    <th width="5">&nbsp;<?php /* echo get_lang('Hotspot'); */ ?></th>
                                    <th><?php echo get_lang('OAR'); ?>*</th>
                                    <?php if ($objExercise->getFeedbackType() == EXERCISE_FEEDBACK_TYPE_DIRECT) {
                        ?>
                                        <th colspan="2"><?php echo get_lang('Comment'); ?></th>
                                        <th><?php if ($answerType == HOT_SPOT_DELINEATION) {
                            echo get_lang('Scenario');
                        } ?></th>
                                        <?php
                    } else {
                        ?>
                                        <th colspan="3"><?php echo get_lang('Comment'); ?></th>
                                        <?php
                    } ?>
                                    <th>&nbsp;</th>
                                </tr>
                                <?php
                } ?>
                            <tr>
                            <td>
                                <span class="fa fa-square fa-2x" aria-hidden="true"
                                      style="color: <?php echo $hotspot_colors[$i]; ?>"></span>
                            </td>
                            <td>
                                <input class="form-control" type="text" name="reponse[<?php echo $i; ?>]"
                                       value="<?php echo isset($reponse[$i]) ? Security::remove_XSS($reponse[$i]) : ''; ?>"/>
                            </td>
                            <td colspan="2" align="left">
                                <textarea class="form-control" wrap="virtual" rows="3" cols="25"
                                          name="comment[<?php echo $i; ?>]"
                                          style="width: 100%"><?php echo isset($comment[$i]) ? Security::remove_XSS($comment[$i]) : ''; ?></textarea>
                                <input type="hidden" name="hotspot_type[<?php echo $i; ?>]" value="oar"/>
                                <input type="hidden" name="hotspot_coordinates[<?php echo $i; ?>]" value="<?php
                                echo empty($hotspot_coordinates[$i]) ? '0;0|0|0' : $hotspot_coordinates[$i]; ?>"/>
                            </td>
                            <?php if ($objExercise->getFeedbackType() == EXERCISE_FEEDBACK_TYPE_DIRECT) {
                                    ?>
                                <td>
                                    <div class="checkbox">
                                        <p>
                                            <label>
                                                <input type="checkbox" class="checkbox"
                                                       name="<?php echo 'try['.$i; ?>]" <?php if (isset($try[$i]) && $try[$i] == 1) {
                                        echo 'checked';
                                    } ?> />
                                                <?php echo get_lang('TryAgain'); ?>
                                            </label>
                                        </p>
                                    </div>
                                    <p>
                                        <?php echo get_lang('SeeTheory'); ?>
                                        <select class="form-control" name="lp[<?php echo $i; ?>]">
                                            <?php echo $option_lp; ?>
                                        </select>
                                    </p>
                                    <p>
                                        <?php echo get_lang('Other'); ?>
                                        <input class="form-control" name="url[<?php echo $i; ?>]"
                                               value="<?php echo isset($url[$i]) ? $url[$i] : ''; ?>">
                                    </p>
                                    <p>
                                        <?php echo get_lang('SelectQuestion'); ?>
                                        <select class="form-control" name="select_question[<?php echo $i; ?>]">
                                            <?php echo $option_feed; ?>
                                        </select>
                                    </p>
                                </td>
                                <?php
                                } else {
                                    ?>
                                <td>&nbsp;</td>
                                <?php
                                }
            }
            //end if is delineation
        } else {
            $commentValue = isset($comment[$i]) ? $comment[$i] : null;
            $responseValue = isset($reponse[$i]) ? $reponse[$i] : null; ?>
                        <tr>
                        <td>
                            <span class="fa fa-square fa-2x" style="color: <?php echo $hotspot_colors[$i]; ?>"
                                  aria-hidden="true"></span>
                        </td>
                        <td>
                            <input class="form-control" type="text" name="reponse[<?php echo $i; ?>]"
                                   value="<?php echo Security::remove_XSS($responseValue); ?>"/>
                        </td>
                        <?php
                        $form = new FormValidator('form_'.$i);
            $config = [
                            'ToolbarSet' => 'TestProposedAnswer',
                            'cols-size' => [0, 12, 0],
                        ];
            $form->addHtmlEditor('comment['.$i.']', null, false, false, $config);
            $renderer = $form->defaultRenderer();
            $form_template = '{content}';
            $renderer->setFormTemplate($form_template);
            $element_template = '{label} {element}';
            $renderer->setElementTemplate($element_template);

            $form->setDefaults(['comment['.$i.']' => $commentValue]);
            $return = $form->returnForm(); ?>
                        <td colspan="2" align="left"><?php echo $return; ?></td>
                        <?php
        } ?>
                    <td>
                        <?php
                        if ($answerType == HOT_SPOT_DELINEATION) {
                            if ($_SESSION['tmp_answers']['hotspot_type'][$i] == 'oar') {
                                ?>
                                <input type="hidden" name="weighting[<?php echo $i; ?>]" class="form-cotrol" value="0"/>
                                <?php
                            } else {
                                ?>
                                <input class="form-control" type="text" name="weighting[<?php echo $i; ?>]"
                                       value="<?php echo isset($weighting[$i]) ? $weighting[$i] : 10; ?>"/>
                                <?php
                            }
                        }
        if (in_array($answerType, [HOT_SPOT_COMBINATION, HOT_SPOT])) {
            ?>
                            <?php if (HOT_SPOT_COMBINATION === $answerType) { ?>
                                <input class="form-control" type="hidden" name="weighting[<?php echo $i; ?>]" value="1" />
                            <?php } else { ?>
                                <input class="form-control" type="text" name="weighting[<?php echo $i; ?>]"
                                       value="<?php echo isset($weighting[$i]) ? $weighting[$i] : 10; ?>"/>
                            <?php } ?>
                            <input type="hidden" name="hotspot_coordinates[<?php echo $i; ?>]"
                                   value="<?php echo empty($hotspot_coordinates[$i])
                                       ? '0;0|0|0'
                                       : $hotspot_coordinates[$i]; ?>"/>
                            <input type="hidden" name="hotspot_type[<?php echo $i; ?>]"
                                   value="<?php echo empty($hotspot_type[$i]) ? 'square' : $hotspot_type[$i]; ?>"/>
                            <?php
        } ?>
                    </td>
                    </tr>
                    <?php
    }

    $list = new LearnpathList(api_get_user_id());
    $flat_list = $list->get_flat_list();
    $option_lp = '';
    $isSelected = false;
    foreach ($flat_list as $id => $details) {
        $selected = '';
        if (isset($lp_noerror) && $id == $lp_noerror) {
            $selected = 'selected="selected"';
            $isSelected = true;
        }
        $option_lp .= '<option value="'.$id.'" '.$selected.'>'.$details['lp_name'].'</option>';
    }

    if ($isSelected) {
        $option_lp = '<option value="0">'.get_lang('SelectTargetLP').'</option>'.$option_lp;
    } else {
        $option_lp = '<option value="0" selected="selected" >'.get_lang('SelectTargetLP').'</option>'
                        .$option_lp;
    }

    // Feedback SELECT
    $question_list = $objExercise->selectQuestionList();
    $option_feed = '';
    $option_feed .= '<option value="0">'.get_lang('SelectTargetQuestion').'</option>';
    $selectQuestionNoError = isset($selectQuestionNoError) ? $selectQuestionNoError : null;
    foreach ($question_list as $key => $questionid) {
        $selected = '';
        $question = Question::read($questionid);
        $questionTitle = $question->selectTitle();
        $val = "Q$key: $questionTitle";
        if ($questionid == $selectQuestionNoError) {
            $selected = 'selected="selected"';
        }
        $option_feed .= '<option value="'.$questionid.'" '.$selected.' >'.$val.'</option>';
    }
    if ($selectQuestionNoError == -1) {
        $option_feed .= '<option value="-1" selected="selected" >'.get_lang('ExitTest').'</option>';
    } else {
        $option_feed .= '<option value="-1">'.get_lang('ExitTest').'</option>';
    }

    if ($answerType == HOT_SPOT_DELINEATION) {
        ?>
                    <tr>
                        <th colspan="2"><?php echo get_lang('IfNoError'); ?></th>
                        <?php if ($objExercise->getFeedbackType() == EXERCISE_FEEDBACK_TYPE_DIRECT) {
            ?>
                            <th colspan="2"><?php echo get_lang('Feedback'); ?></th>
                            <th><?php echo get_lang('Scenario'); ?></th>
                        <?php
        } else {
            ?>
                            <th colspan="3"><?php echo get_lang('Feedback'); ?></th>
                        <?php
        } ?>
                        <th>&nbsp;</th>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <?php echo get_lang('LearnerHasNoMistake'); ?>
                        </td>
                        <td colspan="2" align="left">
                            <textarea class="form-control" wrap="virtual" rows="3" cols="25"
                                      name="comment_noerror"><?php echo Security::remove_XSS($comment_noerror); ?></textarea>
                        </td>
                        <?php if ($objExercise->getFeedbackType() == EXERCISE_FEEDBACK_TYPE_DIRECT) {
            ?>
                            <td>
                                <div class="checkbox">
                                    <p>
                                        <label>
                                            <input type="checkbox" class="checkbox"
                                                   name="try_noerror" <?php if ($try_noerror == 1) {
                echo 'checked';
            } ?> />
                                            <?php echo get_lang('TryAgain'); ?>
                                        </label>
                                    </p>
                                </div>
                                <p>
                                    <?php echo get_lang('SeeTheory'); ?> <br/>
                                    <select class="form-control" name="lp_noerror">
                                        <?php echo $option_lp; ?>
                                    </select>
                                </p>
                                <p>
                                    <?php echo get_lang('Other'); ?> <br/>
                                    <input class="form-control" name="url_noerror" value="<?php echo $url_noerror; ?>">
                                </p>
                                <p>
                                    <?php echo get_lang('SelectQuestion'); ?> <br/>
                                    <select class="form-control" name="select_question_noerror">
                                        <?php echo $option_feed; ?>
                                    </select>
                                </p>
                            </td>
                            <td>&nbsp;</td>
                        <?php
        } else {
            ?>
                            <td colspan="2">&nbsp;</td>
                        <?php
        } ?>
                    </tr>
                <?php
    } ?>
                </tbody>
            </table>
        </div>
        <?php if (HOT_SPOT_COMBINATION === $answerType) { ?>
            <div class="form-group ">
                <label for="question_admin_form_questionWeighting" class="col-sm-2 control-label">
                    <span class="form_required">*</span>
                    <?php echo get_lang('Score'); ?>
                </label>
                <div class="col-sm-8">
                    <input value="<?php echo !empty($objQuestion->iid) ? $objQuestion->selectWeighting() : '10'; ?>" class="form-control required" name="questionWeighting" type="text" id="questionWeighting" required></div>
                <div class="col-sm-2"></div>
            </div>
        <?php } ?>
        <div class="row">
            <div class="col-xs-12">
                <?php

    $relPath = api_get_path(WEB_CODE_PATH); ?>
                <div id="hotspot-container" class="center-block"></div>
            </div>
        </div>
    </form>
    <script>
        $(function () {
            <?php if ($answerType == HOT_SPOT_DELINEATION) {
        ?>
                new DelineationQuestion({
                    questionId: <?php echo $modifyAnswers; ?>,
                    selector: '#hotspot-container',
                    for: 'admin',
                    relPath: '<?php echo $relPath; ?>'
                });
            <?php
    } else {
        ?>
                new HotspotQuestion({
                    questionId: <?php echo $modifyAnswers; ?>,
                    selector: '#hotspot-container',
                    for: 'admin',
                    relPath: '<?php echo $relPath; ?>'
                });
            <?php
    } ?>
        });
    </script>
    <?php
    if ($debug > 0) {
        echo str_repeat('&nbsp;', 0).'$modifyAnswers was set - end'."<br />\n";
    }
}

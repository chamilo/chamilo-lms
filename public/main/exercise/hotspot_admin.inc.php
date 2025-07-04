<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use ChamiloSession as Session;

/**
 * This script allows to manage answers. It is included from the
 * script admin.php.
 *
 * @author  Toon Keppens
 */
$modifyAnswers = (int) $_GET['hotspotadmin'];
if (!is_object($objQuestion)) {
    $objQuestion = Question::read($modifyAnswers);
}

$questionName = $objQuestion->selectTitle();
$answerType = $objQuestion->selectType();

$debug = 0;

$reponse = $_REQUEST['reponse'] ?? null;
$comment = $_REQUEST['comment'] ?? null;
$weighting = $_REQUEST['weighting'] ?? null;
$hotspot_coordinates = $_REQUEST['hotspot_coordinates'] ?? null;
$hotspot_type = $_REQUEST['hotspot_type'] ?? null;

// if we come from the warning box "this question is used in several exercises"
if ($modifyIn) {
    if ($debug > 0) {
        echo '$modifyIn was set'."<br />\n";
    }
    // if the user has chosen to modify the question only in the current exercise
    if ('thisExercise' === $modifyIn) {
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
    if (HOT_SPOT == $answerType) {
        if ($debug > 0) {
            echo '$submitAnswers or $buttonBack was set'."<br />\n";
        }
        $questionWeighting = $nbrGoodAnswers = 0;
        for ($i = 1; $i <= $nbrAnswers; $i++) {
            $reponse[$i] = trim($reponse[$i] ?? null);
            $comment[$i] = trim($comment[$i] ?? null);
            $weighting[$i] = $weighting[$i]; // it can be float

            // checks if field is empty
            if (empty($reponse[$i]) && '0' != $reponse[$i]) {
                $msgErr = get_lang('Please give an answer');

                // clears answers already recorded into the Answer object
                $objAnswer->cancel();

                break;
            }

            if ($weighting[$i] <= 0) {
                $msgErr = get_lang('You must give a positive score for each hotspots');
                // clears answers already recorded into the Answer object
                $objAnswer->cancel();

                break;
            }

            if ('0;0|0|0' === $hotspot_coordinates[$i] || empty($hotspot_coordinates[$i])) {
                $msgErr = get_lang('You haven\'t drawn all your hotspots yet');
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

                $reponse[$i] = trim($reponse[$i] ?? null);
                $comment[$i] = trim($comment[$i] ?? null);
                $weighting[$i] = $weighting[$i] ?? null; // It can be float.

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
                    $hotspot_type[$i]
                );
            }  // end for()
            // saves the answers into the data base
            $objAnswer->save();

            // sets the total weighting of the question
            $objQuestion->updateWeighting($questionWeighting);
            $objQuestion->save($objExercise);

            if (HOT_SPOT_DELINEATION == $answerType) {
                $destination = [
                    'success' => [
                        'type' => $_POST['scenario_success_selector'] ?? '',
                        'url' => $_POST['scenario_success_url'] ?? '',
                    ],
                    'failure' => [
                        'type' => $_POST['scenario_failure_selector'] ?? '',
                        'url' => $_POST['scenario_failure_url'] ?? '',
                    ],
                ];

                $rel = Database::getManager()->getRepository(CQuizRelQuestion::class)->findOneBy([
                    'question' => $objQuestion->iid,
                    'exercise' => $exerciseId,
                ]);

                if ($rel) {
                    $rel->setDestination(json_encode($destination));
                    Database::getManager()->flush();
                }
            }

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

        $threadhold1 = $_POST['threadhold1'] ?? null;
        $threadhold2 = $_POST['threadhold2'] ?? null;
        $threadhold3 = $_POST['threadhold3'] ?? null;

        for ($i = 1; $i <= $nbrAnswers; $i++) {
            $reponse[$i] = trim($reponse[$i] ?? null);
            $comment[$i] = trim($comment[$i] ?? null);
            $weighting[$i] = $weighting[$i] ?? null;

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

            if (isset($try[$i]) && 'on' === $try[$i]) {
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
            $question_str = 0;
            if (isset($select_question[$i]) && !empty($select_question[$i])) {
                $question_str = $select_question[$i];
            }

            // checks if field is empty
            if (empty($reponse[$i]) && '0' != $reponse[$i]) {
                $msgErr = get_lang('Please give an answer');

                // clears answers already recorded into the Answer object
                $objAnswer->cancel();

                break;
            }

            if ($weighting[$i] <= 0 && 'oar' !== $_SESSION['tmp_answers']['hotspot_type'][$i]) {
                $msgErr = get_lang('You must give a positive score for each hotspots');
                // clears answers already recorded into the Answer object
                $objAnswer->cancel();

                break;
            }

            if ('0;0|0|0' === $hotspot_coordinates[$i] || empty($hotspot_coordinates[$i])) {
                $msgErr = get_lang('You haven\'t drawn all your hotspots yet');
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

        if ('on' == $try_noerror) {
            $try_str = 1;
        } else {
            $try_str = 0;
        }

        if (empty($lp_noerror)) {
            $lp_str = 0;
        } else {
            $lp_str = $lp_noerror;
        }

        if ('' == $url_noerror) {
            $url_str = '';
        } else {
            $url_str = $url_noerror;
        }

        if ('' == $selectQuestionNoError) {
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
                $weighting[$i] = $weighting[$i]; //it can be float

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

            if (HOT_SPOT_DELINEATION == $answerType) {
                $destination = [
                    'success' => [
                        'type' => $_POST['scenario_success_selector'] ?? '',
                        'url' => $_POST['scenario_success_url'] ?? '',
                    ],
                    'failure' => [
                        'type' => $_POST['scenario_failure_selector'] ?? '',
                        'url' => $_POST['scenario_failure_url'] ?? '',
                    ],
                ];

                $rel = Database::getManager()->getRepository(CQuizRelQuestion::class)->findOneBy([
                    'question' => $objQuestion->iid,
                    'exercise' => $exerciseId,
                ]);

                if ($rel) {
                    $rel->setDestination(json_encode($destination));
                    Database::getManager()->flush();
                }
            }

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
    $objAnswer = new Answer($objQuestion->id);
    Session::write('objAnswer', $objAnswer);

    if ($debug > 0) {
        echo str_repeat('&nbsp;', 2).'$answerType is HOT_SPOT'."<br />\n";
    }

    if (HOT_SPOT_DELINEATION == $answerType) {
        $try = isset($_POST['try']) ? $_POST['try'] : [];
        for ($i = 1; $i <= $nbrAnswers; $i++) {
            if (isset($try[$i]) && 'on' == $try[$i]) {
                $try[$i] = 1;
            } else {
                $try[$i] = 0;
            }
        }

        if (isset($_POST['try_noerror']) && 'on' == $_POST['try_noerror']) {
            $try_noerror = 1;
        } else {
            $try_noerror = 0;
        }
    }

    if (!$nbrAnswers) {
        $nbrAnswers = $objAnswer->selectNbrAnswers();
        if (HOT_SPOT_DELINEATION == $answerType) {
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

            if (EXERCISE_FEEDBACK_TYPE_EXAM != $objExercise->getFeedbackType()) {
                $comment[$i] = $objAnswer->selectComment($i);
            }

            $weighting[$i] = $objAnswer->selectWeighting($i);
            $hotspot_coordinates[$i] = $objAnswer->selectHotspotCoordinates($i);
            $hotspot_type[$i] = $objAnswer->selectHotspotType($i);

            if (HOT_SPOT_DELINEATION == $answerType) {
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

    if (HOT_SPOT_DELINEATION == $answerType) {
        $destinationData = $objQuestion->getScenarioDestination($exerciseId);
        $scenario_success_selector = $_POST['scenario_success_selector'] ?? ($destinationData['success']['type'] ?? '');
        $scenario_success_url = $_POST['scenario_success_url'] ?? ($destinationData['success']['url'] ?? '');
        $scenario_failure_selector = $_POST['scenario_failure_selector'] ?? ($destinationData['failure']['type'] ?? '');
        $scenario_failure_url = $_POST['scenario_failure_url'] ?? ($destinationData['failure']['url'] ?? '');
    }

    $_SESSION['tmp_answers'] = [];
    $_SESSION['tmp_answers']['answer'] = $reponse;

    if (EXERCISE_FEEDBACK_TYPE_EXAM != $objExercise->getFeedbackType()) {
        $_SESSION['tmp_answers']['comment'] = $comment;
    }

    $_SESSION['tmp_answers']['weighting'] = $weighting;
    $_SESSION['tmp_answers']['hotspot_coordinates'] = $hotspot_coordinates;
    $_SESSION['tmp_answers']['hotspot_type'] = $hotspot_type;

    if (HOT_SPOT_DELINEATION == $answerType) {
        $_SESSION['tmp_answers']['destination'] = $destination ?? null;
    }

    $lessAnswers = isset($_POST['lessAnswers']);
    if ($lessAnswers) {
        if (HOT_SPOT_DELINEATION == $answerType) {
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
                $msgErr = get_lang('You have to create one (1) hotspot at least.');
            }
        } else {
            // At least 1 answer
            if ($nbrAnswers > 1) {
                $nbrAnswers--;
                // Remove the last answer
                $tmp = array_pop($_SESSION['tmp_answers']['answer']);
                if (EXERCISE_FEEDBACK_TYPE_EXAM != $objExercise->getFeedbackType()) {
                    $tmp = array_pop($_SESSION['tmp_answers']['comment']);
                }
                $tmp = array_pop($_SESSION['tmp_answers']['weighting']);
                $tmp = array_pop($_SESSION['tmp_answers']['hotspot_coordinates']);
                $tmp = array_pop($_SESSION['tmp_answers']['hotspot_type']);
            } else {
                $msgErr = get_lang('You have to create one (1) hotspot at least.');
            }
        }
    }

    $moreAnswers = isset($_POST['moreAnswers']) ? true : false;

    if ($moreAnswers) {
        if ($nbrAnswers < 12) {
            $nbrAnswers++;

            // Add a new answer
            $_SESSION['tmp_answers']['answer'][] = '';
            if (EXERCISE_FEEDBACK_TYPE_EXAM != $objExercise->getFeedbackType()) {
                $_SESSION['tmp_answers']['comment'][] = '';
            }
            $_SESSION['tmp_answers']['weighting'][] = '1';
            $_SESSION['tmp_answers']['hotspot_coordinates'][] = '0;0|0|0';
            $_SESSION['tmp_answers']['hotspot_type'][] = 'square';
            $_SESSION['tmp_answers']['destination'][] = '';
        } else {
            $msgErr = get_lang('The maximum hotspots you can create is twelve (12).');
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
            $msgErr = get_lang('The maximum hotspots you can create is twelve (12).');
        }
    }

    if ($debug > 0) {
        echo str_repeat('&nbsp;', 2).'$usedInSeveralExercises is untrue'."<br />\n";
    }

    if ($debug > 0) {
        echo str_repeat('&nbsp;', 4).'$answerType is HOT_SPOT'."<br />\n";
    }

    if (HOT_SPOT_DELINEATION == $answerType) {
        $hotspot_colors = [
            '',
            '#4271B5',
            '#FE8E16',
            '#45C7F0',
            '#BCD631',
            '#D63173',
            '#D7D7D7',
            '#90AFDD',
            '#AF8640',
            '#4F9242',
            '#F4EB24',
            '#ED2024',
            '#3B3B3B',
        ];
    } else {
        $hotspot_colors = [
            '', // $i starts from 1 on next loop (ugly fix)
            '#4271B5',
            '#FE8E16',
            '#45C7F0',
            '#BCD631',
            '#D63173',
            '#D7D7D7',
            '#90AFDD',
            '#AF8640',
            '#4F9242',
            '#F4EB24',
            '#ED2024',
            '#3B3B3B',
            '#F7BDE2',
        ];
    }

    Display::tag(
        'h3',
        get_lang('Question').': '.$questionName.Display::getMdiIcon(ActionIcon::INFORMATION, 'ch-tool-icon', null, ICON_SIZE_SMALL, strip_tags(get_lang('To create a hotspot: select a shape next to the colour, and draw the hotspot. To move a hotspot, select the colour, click another spot in the image, and draw the hotspot. To add a hotspot: click the Add hotspot button. To close a polygon shape: right click and select Close polygon.')))
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
                <?php if (HOT_SPOT_DELINEATION == $answerType) {
            ?>
                    <button type="submit" class="btn btn--danger" name="lessAnswers" value="lessAnswers">
                        <em class="fa fa-trash"></em> <?php echo get_lang('Less areas at risk'); ?>
                    </button>
                    <button type="submit" class="btn btn--primary" name="moreArea to avoidAnswers" value="moreArea to avoidAnswers">
                        <em class="fa fa-plus"></em> <?php echo get_lang('More areas at risk'); ?>
                    </button>
                <?php
        } else {
            ?>
                    <button type="submit" class="btn btn--danger" name="lessAnswers" value="lessAnswers">
                        <em class="fa fa-trash"></em> <?php echo get_lang('Remove hotspot'); ?>
                    </button>
                    <button type="submit" class="btn btn--primary" name="moreAnswers" value="moreAnswers">
                        <em class="fa fa-plus"></em> <?php echo get_lang('Add hotspot'); ?>
                    </button>
                <?php
        } ?>
                <button type="submit" class="btn btn--primary" name="submitAnswers" value="submitAnswers">
                    <em class="fa fa-save"></em> <?php echo get_lang('Add this question to the test'); ?>
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
                    <th><?php echo get_lang('Now click on : (...)'); ?> *</th>
                    <?php
                    if (EXERCISE_FEEDBACK_TYPE_DIRECT == $objExercise->getFeedbackType()) {
                        echo '<th>'.get_lang('Comment').'</th>';
                    } else {
                        echo '<th colspan="2">'.get_lang('Comment').'</th>';
                    } ?>
                    <th><?php echo get_lang('Score'); ?> *</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $list = new LearnpathList(api_get_user_id());
    // Loading list of LPs
    $flat_list = $list->get_flat_list();

    for ($i = 1; $i <= $nbrAnswers; $i++) {
        // is an delineation
        if (HOT_SPOT_DELINEATION == $answerType) {
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
                $option_lp = '<option value="0">'.get_lang('Select target course').'</option>'.$option_lp;
            } else {
                $option_lp = '<option value="0" selected="selected" >'.get_lang('Select target course')
                                .'</option>'.$option_lp;
            }

            // Feedback SELECT
            $question_list = $objExercise->selectQuestionList();
            $option_feed = '';
            $option_feed .= '<option value="0">'.get_lang('Select target question').'</option>';

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

            if (isset($select_question[$i]) && -1 == $select_question[$i]) {
                $option_feed .= '<option value="-1" selected="selected" >'.get_lang('Exit test').'</option>';
            } else {
                $option_feed .= '<option value="-1">'.get_lang('Exit test').'</option>';
            }

            //-------- IF it is a delineation
            if ('delineation' == $_SESSION['tmp_answers']['hotspot_type'][$i]) {
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
                                    <?php echo get_lang('Minimum overlap'); ?>
                                    <select class="form-control" name="threadhold1[<?php echo $i; ?>]">
                                        <?php echo $option1; ?>
                                    </select>
                                </p>
                                <p>
                                    <?php echo get_lang('Maximum excess'); ?>
                                    <select class="form-control" name="threadhold2[<?php echo $i; ?>]">
                                        <?php echo $option2; ?>
                                    </select>
                                </p>
                                <p>
                                    <?php echo get_lang('Maximum missing'); ?>
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
                                          class="help-block"><?php echo get_lang('This message, as well as the results table, will appear to the learner if he fails this step'); ?></span>
                                </p>
                                <input type="hidden" name="hotspot_type[<?php echo $i; ?>]" value="delineation"/>
                                <input type="hidden" name="hotspot_coordinates[<?php echo $i; ?>]" value="<?php
                                echo empty($hotspot_coordinates[$i]) ? '0;0|0|0' : $hotspot_coordinates[$i]; ?>"/>
                            </td>
                                <?php

            } elseif (false) {
                ?>
                            <tr>
                                <th colspan="2"><?php echo get_lang('If no error'); ?></th>
                                <th colspan="3"><?php echo get_lang('Feedback'); ?></th>
                                <th></th>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <?php echo get_lang('The learner made no mistake'); ?>
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
                            </tr>
                            <?php
            } elseif ('oar' == $_SESSION['tmp_answers']['hotspot_type'][$i]) {
                // if it's an Area to avoid
                if (2 == $i) {
                    ?>
                                <tr>
                                    <th width="5">&nbsp;<?php /* echo get_lang('Hotspot'); */ ?></th>
                                    <th><?php echo get_lang('Area to avoid'); ?>*</th>
                                    <?php if (EXERCISE_FEEDBACK_TYPE_DIRECT == $objExercise->getFeedbackType()) {
                        ?>
                                        <th colspan="2"><?php echo get_lang('Comment'); ?></th>
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
                                <?php
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
                        if (HOT_SPOT_DELINEATION == $answerType) {
                            if ('oar' == $_SESSION['tmp_answers']['hotspot_type'][$i]) {
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
        if (HOT_SPOT == $answerType) {
            ?>
                            <input class="form-control" type="text" name="weighting[<?php echo $i; ?>]"
                                   value="<?php echo isset($weighting[$i]) ? $weighting[$i] : 10; ?>"/>
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
        $option_lp = '<option value="0">'.get_lang('Select target course').'</option>'.$option_lp;
    } else {
        $option_lp = '<option value="0" selected="selected" >'.get_lang('Select target course').'</option>'
                        .$option_lp;
    }

    // Feedback SELECT
    $question_list = $objExercise->selectQuestionList();
    $option_feed = '';
    $option_feed .= '<option value="0">'.get_lang('Select target question').'</option>';
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
    if (-1 == $selectQuestionNoError) {
        $option_feed .= '<option value="-1" selected="selected" >'.get_lang('Exit test').'</option>';
    } else {
        $option_feed .= '<option value="-1">'.get_lang('Exit test').'</option>';
    }

    if (HOT_SPOT_DELINEATION == $answerType) {
        ?>
                    <tr>
                        <th colspan="2"><?php echo get_lang('If no error'); ?></th>
                        <?php if (EXERCISE_FEEDBACK_TYPE_DIRECT == $objExercise->getFeedbackType()) {
            ?>
                            <th colspan="2"><?php echo get_lang('Feedback'); ?></th>
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
                            <?php echo get_lang('The learner made no mistake'); ?>
                        </td>
                        <td colspan="2" align="left">
                            <textarea class="form-control" wrap="virtual" rows="3" cols="25"
                                      name="comment_noerror"><?php echo isset($comment_noerror) ? Security::remove_XSS($comment_noerror): ''; ?></textarea>
                        </td>
                    </tr>
                <?php
    } ?>
                </tbody>
            </table>
        </div>

        <?php if (HOT_SPOT_DELINEATION == $answerType): ?>
            <div class="mt-4">
                <h4><?php echo get_lang('Adaptive behavior (Success / Failure)'); ?></h4>
                <div class="form-group">
                    <label for="scenario_success_selector"><?php echo get_lang('On Success'); ?></label>
                    <select class="form-control" name="scenario_success_selector" id="scenario_success_selector">
                        <option value=""><?php echo get_lang('Select destination'); ?></option>
                        <option value="repeat" <?php if ($scenario_success_selector === 'repeat') echo 'selected'; ?>><?php echo get_lang('Repeat question'); ?></option>
                        <option value="-1" <?php if ($scenario_success_selector === '-1') echo 'selected'; ?>><?php echo get_lang('End of test'); ?></option>
                        <option value="url" <?php if ($scenario_success_selector === 'url') echo 'selected'; ?>><?php echo get_lang('Other (manual URL)'); ?></option>
                        <?php foreach ($objExercise->selectQuestionList() as $index => $qid):
                            $q = Question::read($qid);
                            $label = "Q$index: ".strip_tags($q->selectTitle()); ?>
                            <option value="<?php echo $qid; ?>" <?php if ($scenario_success_selector == $qid) echo 'selected'; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="scenario_success_url_block" style="display: none;">
                    <label for="scenario_success_url"><?php echo get_lang('Custom URL'); ?></label>
                    <input type="text" class="form-control" name="scenario_success_url" id="scenario_success_url" placeholder="/main/lp/134" value="<?php echo $scenario_success_url ?? ''; ?>">
                </div>

                <div class="form-group">
                    <label for="scenario_failure_selector"><?php echo get_lang('On Failure'); ?></label>
                    <select class="form-control" name="scenario_failure_selector" id="scenario_failure_selector">
                        <option value=""><?php echo get_lang('Select destination'); ?></option>
                        <option value="repeat" <?php if ($scenario_failure_selector === 'repeat') echo 'selected'; ?>><?php echo get_lang('Repeat question'); ?></option>
                        <option value="-1" <?php if ($scenario_failure_selector === '-1') echo 'selected'; ?>><?php echo get_lang('End of test'); ?></option>
                        <option value="url" <?php if ($scenario_failure_selector === 'url') echo 'selected'; ?>><?php echo get_lang('Other (manual URL)'); ?></option>
                        <?php foreach ($objExercise->selectQuestionList() as $index => $qid):
                            $q = Question::read($qid);
                            $label = "Q$index: ".strip_tags($q->selectTitle()); ?>
                            <option value="<?php echo $qid; ?>" <?php if ($scenario_failure_selector == $qid) echo 'selected'; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="scenario_failure_url_block" style="display: none;">
                    <label for="scenario_failure_url"><?php echo get_lang('Custom URL'); ?></label>
                    <input type="text" class="form-control" name="scenario_failure_url" id="scenario_failure_url" placeholder="/main/lp/134" value="<?php echo $scenario_failure_url ?? ''; ?>">
                </div>
            </div>

            <script>
                function toggleScenarioUrlFields() {
                    const success = document.getElementById("scenario_success_selector");
                    const successUrlBlock = document.getElementById("scenario_success_url_block");
                    const failure = document.getElementById("scenario_failure_selector");
                    const failureUrlBlock = document.getElementById("scenario_failure_url_block");

                    if (success && success.value === "url") {
                        successUrlBlock.style.display = "block";
                    } else {
                        successUrlBlock.style.display = "none";
                    }

                    if (failure && failure.value === "url") {
                        failureUrlBlock.style.display = "block";
                    } else {
                        failureUrlBlock.style.display = "none";
                    }
                }

                document.addEventListener("DOMContentLoaded", toggleScenarioUrlFields);
                document.getElementById("scenario_success_selector").addEventListener("change", toggleScenarioUrlFields);
                document.getElementById("scenario_failure_selector").addEventListener("change", toggleScenarioUrlFields);
            </script>
        <?php endif; ?>



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
            <?php if (HOT_SPOT_DELINEATION == $answerType) {
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

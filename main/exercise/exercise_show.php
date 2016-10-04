<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 *  Shows the exercise results
 *
 * @author Julio Montoya - Added switchable fill in blank option added
 * @version $Id: exercise_show.php 22256 2009-07-20 17:40:20Z ivantcholakov $
 * @package chamilo.exercise
 * @todo remove the debug code and use the general debug library
 * @todo small letters for table variables
 *
 */

require_once '../inc/global.inc.php';
$debug = false;
if (empty($origin)) {
    $origin = isset($_REQUEST['origin']) ? $_REQUEST['origin'] : null;
}

if ($origin == 'learnpath') {
    api_protect_course_script(false, false, true);
} else {
    api_protect_course_script(true, false, true);
}

// Database table definitions
$TBL_EXERCISE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
$TBL_TRACK_EXERCISES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$TBL_TRACK_ATTEMPT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

// General parameters passed via POST/GET
if ($debug) {
    error_log('Entered exercise_result.php: ' . print_r($_POST, 1));
}

if (empty($formSent)) {
    $formSent = isset($_REQUEST['formSent']) ? $_REQUEST['formSent'] : null;
}
if (empty($exerciseResult)) {
    $exerciseResult = Session::read('exerciseResult');
}
if (empty($questionId)) {
    $questionId = isset($_REQUEST['questionId']) ? $_REQUEST['questionId'] : null;
}
if (empty($choice)) {
    $choice = isset($_REQUEST['choice']) ? $_REQUEST['choice'] : null;
}
if (empty($questionNum)) {
    $questionNum = isset($_REQUEST['num']) ? $_REQUEST['num'] : null;
}
if (empty($nbrQuestions)) {
    $nbrQuestions = isset($_REQUEST['nbrQuestions']) ? $_REQUEST['nbrQuestions'] : null;
}
if (empty($questionList)) {
    $questionList = Session::read('questionList');
}
if (empty($objExercise)) {
    $objExercise = Session::read('objExercise');
}
if (empty($exeId)) {
    $exeId = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
}
if (empty($action)) {
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
}

$id = intval($_REQUEST['id']); //exe id

if (empty($id)) {
    api_not_allowed(true);
}

if (api_is_course_session_coach(
    api_get_user_id(),
    api_get_course_int_id(),
    api_get_session_id()
)) {
    if (!api_coach_can_edit_view_results(api_get_course_int_id(), api_get_session_id())) {
        api_not_allowed(true);
    }
}

$allowCoachFeedbackExercises = api_get_setting('allow_coach_feedback_exercises') === 'true';

$maxEditors = intval(api_get_setting('exercise_max_ckeditors_in_page'));
$is_allowedToEdit = api_is_allowed_to_edit(null, true) || $is_courseTutor || api_is_session_admin() || api_is_drh() || api_is_student_boss();
$isCoachAllowedToEdit = api_is_allowed_to_edit(false, true);
$isFeedbackAllowed = false;

//Getting results from the exe_id. This variable also contain all the information about the exercise
$track_exercise_info = ExerciseLib::get_exercise_track_exercise_info($id);

//No track info
if (empty($track_exercise_info)) {
    api_not_allowed(true);
}

$exercise_id = $track_exercise_info['id'];
$exercise_date = $track_exercise_info['start_date'];
$student_id = $track_exercise_info['exe_user_id'];
$learnpath_id = $track_exercise_info['orig_lp_id'];
$learnpath_item_id = $track_exercise_info['orig_lp_item_id'];
$lp_item_view_id = $track_exercise_info['orig_lp_item_view_id'];
$current_user_id = api_get_user_id();

if (api_is_excluded_user_type(true, $student_id)) {
    api_not_allowed(true);
}

$locked = api_resource_is_locked_by_gradebook($exercise_id, LINK_EXERCISE);

if (empty($objExercise)) {
    $objExercise = new Exercise();
    $objExercise->read($exercise_id);
}
$feedback_type = $objExercise->feedback_type;

//Only users can see their own results
if (!$is_allowedToEdit) {
    if ($student_id != $current_user_id) {
        api_not_allowed(true);
    }
}

if (isset($_SESSION['gradebook'])) {
    $gradebook = Security::remove_XSS($_SESSION['gradebook']);
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array('url' => '../gradebook/' . $_SESSION['gradebook_dest'], 'name' => get_lang('ToolGradebook'));
}

$fromlink = '';

$interbreadcrumb[] = array("url" => "exercise.php?" . api_get_cidreq(), "name" => get_lang('Exercises'));
$interbreadcrumb[] = array("url" => "overview.php?exerciseId=" . $exercise_id . '&' . api_get_cidreq(), "name" => $objExercise->name);
$interbreadcrumb[] = array("url" => "#", "name" => get_lang('Result'));

$this_section = SECTION_COURSES;

$htmlHeadXtra[] = '<link rel="stylesheet" href="' . api_get_path(WEB_LIBRARY_JS_PATH) . 'hotspot/css/hotspot.css">';
$htmlHeadXtra[] = '<script src="' . api_get_path(WEB_LIBRARY_JS_PATH) . 'hotspot/js/hotspot.js"></script>';

if ($origin != 'learnpath') {
    Display::display_header('');
} else {
    $htmlHeadXtra[] = "
    <style>
    body { background: none;}
    </style>
    ";
    Display::display_reduced_header();
}
?>
    <script>
        var maxEditors = <?php echo intval($maxEditors); ?>;

        function showfck(sid, marksid) {
            document.getElementById(sid).style.display = 'block';
            document.getElementById(marksid).style.display = 'block';
            var comment = 'feedback_' + sid;
            document.getElementById(comment).style.display = 'none';
        }

        function getFCK(vals, marksid) {
            var f = document.getElementById('myform');

            var m_id = marksid.split(',');
            for (var i = 0; i < m_id.length; i++) {
                var oHidn = document.createElement("input");
                oHidn.type = "hidden";
                var selname = oHidn.name = "marks_" + m_id[i];
                var selid = document.forms['marksform_' + m_id[i]].marks.selectedIndex;
                oHidn.value = document.forms['marksform_' + m_id[i]].marks.options[selid].text;
                f.appendChild(oHidn);
            }

            var ids = vals.split(',');
            for (var k = 0; k < ids.length; k++) {
                var oHidden = document.createElement("input");
                oHidden.type = "hidden";
                oHidden.name = "comments_" + ids[k];
                if (CKEDITOR.instances[oHidden.name]) {
                    oHidden.value = CKEDITOR.instances[oHidden.name].getData();
                } else {
                    oHidden.value = $("textarea[name='" + oHidden.name + "']").val();
                }
                f.appendChild(oHidden);
            }
        }
    </script>
<?php
$show_results = true;
$show_only_total_score = false;
$showTotalScoreAndUserChoicesInLastAttempt = true;

// Avoiding the "Score 0/0" message  when the exe_id is not set
if (!empty($track_exercise_info)) {
    // if the results_disabled of the Quiz is 1 when block the script
    $result_disabled = $track_exercise_info['results_disabled'];

    if (true) {
        if ($result_disabled == RESULT_DISABLE_NO_SCORE_AND_EXPECTED_ANSWERS) {
            $show_results = false;
        } elseif ($result_disabled == RESULT_DISABLE_SHOW_SCORE_ONLY) {
            $show_results = false;
            $show_only_total_score = true;
            if ($origin != 'learnpath') {
                echo '<table width="100%" border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td colspan="2">';
                Display::display_warning_message(get_lang('ThankYouForPassingTheTest'), false);
                echo '</td>
                </tr>
                </table>';
            }
        } elseif ($result_disabled == RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT) {
            $attempts = Event::getExerciseResultsByUser(
                api_get_user_id(),
                $objExercise->id,
                api_get_course_int_id(),
                api_get_session_id(),
                $track_exercise_info['orig_lp_id'],
                $track_exercise_info['orig_lp_item_id'],
                'desc'
            );
            $numberAttempts = count($attempts);
            if ($numberAttempts >= $track_exercise_info['max_attempt']) {
                $show_results = true;
                $show_only_total_score = true;
                // Attempt reach max so show score/feedback now
                $showTotalScoreAndUserChoicesInLastAttempt = true;
            } else {
                $show_results = true;
                $show_only_total_score = true;
                // Last attempt not reach don't show score/feedback
                $showTotalScoreAndUserChoicesInLastAttempt = false;
            }
        }
    }
} else {
    Display::display_warning_message(get_lang('CantViewResults'));
    $show_results = false;
}

if ($origin == 'learnpath' && !isset($_GET['fb_type'])) {
    $show_results = false;
}

if ($is_allowedToEdit && in_array($action, ['qualify', 'edit'])) {
    $show_results = true;
}

if ($show_results || $show_only_total_score || $showTotalScoreAndUserChoicesInLastAttempt) {
    $user_info = api_get_user_info($student_id);
    //Shows exercise header
    echo $objExercise->show_exercise_result_header(
        $user_info,
        api_convert_and_format_date($exercise_date),
        null,
        $track_exercise_info['user_ip']
    );
}

$i = $totalScore = $totalWeighting = 0;

if ($debug > 0) {
    error_log("ExerciseResult: " . print_r($exerciseResult, 1));
    error_log("QuestionList: " . print_r($questionList, 1));
}

$arrques = array();
$arrans = array();

$user_restriction = $is_allowedToEdit ? '' : "AND user_id=" . intval($student_id) . " ";
$sql = "SELECT attempts.question_id, answer
        FROM $TBL_TRACK_ATTEMPT as attempts
        INNER JOIN " . $TBL_TRACK_EXERCISES . " AS stats_exercises
        ON stats_exercises.exe_id=attempts.exe_id
        INNER JOIN $TBL_EXERCISE_QUESTION AS quizz_rel_questions
        ON
            quizz_rel_questions.exercice_id=stats_exercises.exe_exo_id AND
            quizz_rel_questions.question_id = attempts.question_id AND
            quizz_rel_questions.c_id=" . api_get_course_int_id() . "
        INNER JOIN " . $TBL_QUESTIONS . " AS questions
        ON
            questions.id=quizz_rel_questions.question_id AND
            questions.c_id = " . api_get_course_int_id() . "
        WHERE
            attempts.exe_id = " . intval($id) . " $user_restriction
		GROUP BY quizz_rel_questions.question_order, attempts.question_id";

$result = Database::query($sql);

$question_list_from_database = array();
$exerciseResult = array();

while ($row = Database::fetch_array($result)) {
    $question_list_from_database[] = $row['question_id'];
    $exerciseResult[$row['question_id']] = $row['answer'];
}

//Fixing #2073 Fixing order of questions
if (!empty($track_exercise_info['data_tracking'])) {
    $temp_question_list = explode(',', $track_exercise_info['data_tracking']);

    // Getting question list from data_tracking
    if (!empty($temp_question_list)) {
        $questionList = $temp_question_list;
    }
    // If for some reason data_tracking is empty we select the question list from db
    if (empty($questionList)) {
        $questionList = $question_list_from_database;
    }
} else {
    $questionList = $question_list_from_database;
}

// Display the text when finished message if we are on a LP #4227
$end_of_message = $objExercise->selectTextWhenFinished();
if (!empty($end_of_message) && ($origin == 'learnpath')) {
    Display::display_normal_message($end_of_message, false);
    echo "<div class='clear'>&nbsp;</div>";
}

// for each question
$total_weighting = 0;
foreach ($questionList as $questionId) {
    $objQuestionTmp = Question::read($questionId);
    $total_weighting += $objQuestionTmp->selectWeighting();
}

$counter = 1;
$exercise_content = null;
$category_list = array();

$useAdvancedEditor = true;

if (!empty($maxEditors) && count($questionList) > $maxEditors) {
    $useAdvancedEditor = false;
}

foreach ($questionList as $questionId) {
    $choice = $exerciseResult[$questionId];
    // destruction of the Question object
    unset($objQuestionTmp);

    // creates a temporary Question object
    $objQuestionTmp = Question::read($questionId);
    $questionWeighting = $objQuestionTmp->selectWeighting();
    $answerType = $objQuestionTmp->selectType();

    // Start buffer
    ob_start();

    if ($answerType == MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE) {
        $choice = array();
    }

    switch ($answerType) {
        case MULTIPLE_ANSWER_COMBINATION:
            //no break
        case MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE:
            //no break
        case UNIQUE_ANSWER:
            //no break;
        case UNIQUE_ANSWER_NO_OPTION:
            //no break
        case MULTIPLE_ANSWER:
            //no break
        case MULTIPLE_ANSWER_TRUE_FALSE:
            //no break
        case FILL_IN_BLANKS:
            //no break
        case CALCULATED_ANSWER:
            //no break
        case GLOBAL_MULTIPLE_ANSWER:
            //no break
        case FREE_ANSWER:
            //no break
        case ORAL_EXPRESSION:
            //no break
        case MATCHING:
            //no break
        case DRAGGABLE:
            //no break
        case MATCHING_DRAGGABLE:
            $question_result = $objExercise->manage_answer(
                $id,
                $questionId,
                $choice,
                'exercise_show',
                array(),
                false,
                true,
                $show_results,
                $objExercise->selectPropagateNeg(),
                [],
                $showTotalScoreAndUserChoicesInLastAttempt
            );
            $questionScore = $question_result['score'];
            $totalScore += $question_result['score'];
            break;
        case HOT_SPOT:
            if ($show_results || $showTotalScoreAndUserChoicesInLastAttempt) {
                echo '<table width="500" border="0"><tr>
                    <td valign="top" align="center" style="padding-left:0px;" >
                        <table border="1" bordercolor="#A4A4A4" style="border-collapse: collapse;" width="552">';
            }
            $question_result = $objExercise->manage_answer(
                $id,
                $questionId,
                $choice,
                'exercise_show',
                array(),
                false,
                true,
                $show_results,
                $objExercise->selectPropagateNeg(),
                [],
                $showTotalScoreAndUserChoicesInLastAttempt
            );
            $questionScore = $question_result['score'];
            $totalScore += $question_result['score'];

            if ($show_results) {
                $relPath = api_get_path(WEB_CODE_PATH);
                echo '</table></td></tr>';
                echo "
                        <tr>
                            <td colspan=\"2\">
                                <div id=\"hotspot-solution-$questionId-$id\"></div>
                                <script>
                                    $(document).on('ready', function () {
                                        new HotspotQuestion({
                                            questionId: $questionId,
                                            exerciseId: $id,
                                            selector: '#hotspot-solution-$questionId-$id',
                                            for: 'solution',
                                            relPath: '$relPath'
                                        });
                                    });
                                </script>
                            </td>
                        </tr>
                    </table>
                    <br>
                ";
            }
            break;
        case HOT_SPOT_DELINEATION:
            $question_result = $objExercise->manage_answer(
                $id,
                $questionId,
                $choice,
                'exercise_show',
                array(),
                false,
                true,
                $show_results,
                $objExercise->selectPropagateNeg(),
                'database',
                [],
                $showTotalScoreAndUserChoicesInLastAttempt
            );

            $questionScore = $question_result['score'];
            $totalScore += $question_result['score'];

            $final_overlap = $question_result['extra']['final_overlap'];
            $final_missing = $question_result['extra']['final_missing'];
            $final_excess = $question_result['extra']['final_excess'];

            $overlap_color = $question_result['extra']['overlap_color'];
            $missing_color = $question_result['extra']['missing_color'];
            $excess_color = $question_result['extra']['excess_color'];

            $threadhold1 = $question_result['extra']['threadhold1'];
            $threadhold2 = $question_result['extra']['threadhold2'];
            $threadhold3 = $question_result['extra']['threadhold3'];

            if ($show_results) {

                if ($overlap_color) {
                    $overlap_color = 'green';
                } else {
                    $overlap_color = 'red';
                }

                if ($missing_color) {
                    $missing_color = 'green';
                } else {
                    $missing_color = 'red';
                }
                if ($excess_color) {
                    $excess_color = 'green';
                } else {
                    $excess_color = 'red';
                }

                if (!is_numeric($final_overlap)) {
                    $final_overlap = 0;
                }

                if (!is_numeric($final_missing)) {
                    $final_missing = 0;
                }
                if (!is_numeric($final_excess)) {
                    $final_excess = 0;
                }

                if ($final_excess > 100) {
                    $final_excess = 100;
                }

                $table_resume = '<table class="data_table">
                    <tr class="row_odd" >
                    <td></td>
                    <td ><b>' . get_lang('Requirements') . '</b></td>
                    <td><b>' . get_lang('YourAnswer') . '</b></td>
                    </tr>
        
                    <tr class="row_even">
                    <td><b>' . get_lang('Overlap') . '</b></td>
                    <td>' . get_lang('Min') . ' ' . $threadhold1 . '</td>
                        <td><div style="color:' . $overlap_color . '">' . (($final_overlap < 0) ? 0 : intval($final_overlap)) . '</div></td>
                    </tr>
        
                    <tr>
                        <td><b>' . get_lang('Excess') . '</b></td>
                        <td>' . get_lang('Max') . ' ' . $threadhold2 . '</td>
                        <td><div style="color:' . $excess_color . '">' . (($final_excess < 0) ? 0 : intval($final_excess)) . '</div></td>
                    </tr>
        
                    <tr class="row_even">
                        <td><b>' . get_lang('Missing') . '</b></td>
                        <td>' . get_lang('Max') . ' ' . $threadhold3 . '</td>
                        <td><div style="color:' . $missing_color . '">' . (($final_missing < 0) ? 0 : intval($final_missing)) . '</div></td>
                    </tr></table>';

                if ($answerType != HOT_SPOT_DELINEATION) {
                    $item_list = explode('@@', $destination);

                    $try = $item_list[0];
                    $lp = $item_list[1];
                    $destinationid = $item_list[2];
                    $url = $item_list[3];
                    $table_resume = '';
                } else {
                    if ($next == 0) {
                        $try = $try_hotspot;
                        $lp = $lp_hotspot;
                        $destinationid = $select_question_hotspot;
                        $url = $url_hotspot;
                    } else {
                        //show if no error
                        $comment = $answerComment = $objAnswerTmp->selectComment($nbrAnswers);
                        $answerDestination = $objAnswerTmp->selectDestination($nbrAnswers);
                    }
                }

                echo '<h1><div style="color:#333;">' . get_lang('Feedback') . '</div></h1>';
                if ($answerType == HOT_SPOT_DELINEATION) {
                    if ($organs_at_risk_hit > 0) {
                        $message = '<br />' . get_lang('ResultIs') . ' <b>' . $result_comment . '</b><br />';
                        $message .= '<p style="color:#DC0A0A;"><b>' . get_lang('OARHit') . '</b></p>';
                    } else {
                        $message = '<p>' . get_lang('YourDelineation') . '</p>';
                        $message .= $table_resume;
                        $message .= '<br />' . get_lang('ResultIs') . ' <b>' . $result_comment . '</b><br />';
                    }
                    $message .= '<p>' . $comment . '</p>';
                    echo $message;
                } else {
                    echo '<p>' . $comment . '</p>';
                }

                //showing the score
                $queryfree = "select marks from " . $TBL_TRACK_ATTEMPT . " WHERE exe_id = " . intval($id) . " and question_id= " . intval($questionId) . "";
                $resfree = Database::query($queryfree);
                $questionScore = Database::result($resfree, 0, "marks");
                $totalScore += $questionScore;
                $relPath = api_get_path(REL_PATH);
                echo '</table></td></tr>';

                echo "
                        <tr>
                            <td colspan=\"2\">
                                <div id=\"hotspot-solution\"></div>
                                <script>
                                    $(document).on('ready', function () {
                                        new HotspotQuestion({
                                            questionId: $questionId,
                                            exerciseId: $id,
                                            selector: '#hotspot-solution',
                                            for: 'solution',
                                            relPath: '$relPath'
                                        });
                                    });
                                </script>
                            </td>
                        </tr>
                    </table>
                ";
            }
            break;
    }

    if ($answerType == MULTIPLE_ANSWER_TRUE_FALSE) {
        echo '</table>';
    }

    if ($show_results && $answerType != HOT_SPOT) {
        echo '</table>';
    }

    $comnt = null;

    if ($show_results) {
        if (
            $is_allowedToEdit &&
            $locked == false &&
            !api_is_drh() &&
            !api_is_student_boss() &&
            $isCoachAllowedToEdit
        ) {
            $isFeedbackAllowed = true;
        } else if (!$isCoachAllowedToEdit && $allowCoachFeedbackExercises) {
            $isFeedbackAllowed = true;
        }

        $marksname = '';

        if ($isFeedbackAllowed) {
            $name = "fckdiv" . $questionId;
            $marksname = "marksName" . $questionId;
            if (in_array($answerType, array(FREE_ANSWER, ORAL_EXPRESSION))) {
                $url_name = get_lang('EditCommentsAndMarks');
            } else {
                if ($action == 'edit') {
                    $url_name = get_lang('EditIndividualComment');
                } else {
                    $url_name = get_lang('AddComments');
                }
            }
            echo '<br />';
            echo Display::url($url_name, 'javascript://', array('class' => 'btn', 'onclick' => "showfck('" . $name . "', '" . $marksname . "');"));
            echo '<br />';

            echo '<div id="feedback_' . $name . '" style="width:100%">';
            $comnt = trim(Event::get_comments($id, $questionId));
            if (empty($comnt)) {
                echo '<br />';
            } else {
                echo '<div id="question_feedback">' . $comnt . '</div>';
            }
            echo '</div>';

            echo '<div id="' . $name . '" style="display:none">';
            $arrid[] = $questionId;
            $feedback_form = new FormValidator('frmcomments' . $questionId, 'post', '');
            $feedback_form->addElement('html', '<br>');
            $renderer =& $feedback_form->defaultRenderer();
            $renderer->setFormTemplate('<form{attributes}><div align="left">{content}</div></form>');
            $renderer->setCustomElementTemplate('<div align="left">{element}</div>');
            $comnt = Event::get_comments($id, $questionId);
            $default = array('comments_' . $questionId => $comnt);

            if ($useAdvancedEditor) {
                $feedback_form->addElement(
                    'html_editor',
                    'comments_' . $questionId,
                    null,
                    null,
                    array(
                        'ToolbarSet' => 'TestAnswerFeedback',
                        'Width' => '100%',
                        'Height' => '120'
                    )
                );
            } else {
                $feedback_form->addElement('textarea', 'comments_' . $questionId);
            }
            $feedback_form->addElement('html', '<br>');
            $feedback_form->setDefaults($default);
            $feedback_form->display();
            echo '</div>';

        } else {
            $comnt = Event::get_comments($id, $questionId);
            echo '<br />';
            if (!empty($comnt)) {
                echo '<b>' . get_lang('Feedback') . '</b>';
                echo '<div id="question_feedback">' . $comnt . '</div>';
            }
        }

        if ($is_allowedToEdit && $isFeedbackAllowed) {
            if (in_array($answerType, array(FREE_ANSWER, ORAL_EXPRESSION))) {
                $marksname = "marksName" . $questionId;
                echo '<div id="' . $marksname . '" style="display:none">';
                echo '<form name="marksform_' . $questionId . '" method="post" action="">';
                $arrmarks[] = $questionId;
                echo get_lang("AssignMarks");
                echo "&nbsp;<select name='marks' id='marks'>";
                for ($i = 0; $i <= $questionWeighting; $i++) {
                    echo '<option ' . (($i == $questionScore) ? "selected='selected'" : '') . '>' . $i . '</option>';
                }
                echo '</select>';
                echo '</form><br /></div>';

                if ($questionScore == -1) {
                    $questionScore = 0;
                    echo Display::return_message(get_lang('notCorrectedYet'));
                }
            } else {
                $arrmarks[] = $questionId;
                echo '<div id="' . $marksname . '" style="display:none"><form name="marksform_' . $questionId . '" method="post" action="">
					  <select name="marks" id="marks" style="display:none;"><option>' . $questionScore . '</option></select></form><br/ ></div>';
            }
        } else {
            if ($questionScore == -1) {
                $questionScore = 0;
            }
        }
    }

    $my_total_score = $questionScore;
    $my_total_weight = $questionWeighting;
    $totalWeighting += $questionWeighting;
    $category_was_added_for_this_test = false;

    if (isset($objQuestionTmp->category) && !empty($objQuestionTmp->category)) {
        if (!isset($category_list[$objQuestionTmp->category]['score'])) {
            $category_list[$objQuestionTmp->category]['score'] = 0;
        }

        if (!isset($category_list[$objQuestionTmp->category]['total'])) {
            $category_list[$objQuestionTmp->category]['total'] = 0;
        }

        $category_list[$objQuestionTmp->category]['score'] += $my_total_score;
        $category_list[$objQuestionTmp->category]['total'] += $my_total_weight;
        $category_was_added_for_this_test = true;
    }

    if (isset($objQuestionTmp->category_list) && !empty($objQuestionTmp->category_list)) {
        foreach ($objQuestionTmp->category_list as $category_id) {
            $category_list[$category_id]['score'] += $my_total_score;
            $category_list[$category_id]['total'] += $my_total_weight;
            $category_was_added_for_this_test = true;
        }
    }

    // No category for this question!
    if (!isset($category_list['none']['score'])) {
        $category_list['none']['score'] = 0;
    }

    if (!isset($category_list['none']['total'])) {
        $category_list['none']['total'] = 0;
    }

    if ($category_was_added_for_this_test == false) {
        $category_list['none']['score'] += $my_total_score;
        $category_list['none']['total'] += $my_total_weight;
    }

    if ($objExercise->selectPropagateNeg() == 0 && $my_total_score < 0) {
        $my_total_score = 0;
    }

    $score = array();
    if ($show_results) {
        $score['result'] = get_lang('Score') . " : " . ExerciseLib::show_score(
            $my_total_score,
            $my_total_weight,
            false,
            false
        );
        $score['pass'] = $my_total_score >= $my_total_weight ? true : false;
        $score['type'] = $answerType;
        $score['score'] = $my_total_score;
        $score['weight'] = $my_total_weight;
        $score['comments'] = isset($comnt) ? $comnt : null;
    }

    unset($objAnswerTmp);
    $i++;

    $contents = ob_get_clean();

    $question_content = '<div class="question_row">';

    if ($show_results) {
        //Shows question title an description
        $question_content .= $objQuestionTmp->return_header(null, $counter, $score);
    }
    $counter++;
    $question_content .= $contents;
    $question_content .= '</div>';
    $exercise_content .= $question_content;
} // end of large foreach on questions

$total_score_text = null;

//Total score
if ($origin != 'learnpath' || ($origin == 'learnpath' && isset($_GET['fb_type']))) {
    if ($show_results || $show_only_total_score || $showTotalScoreAndUserChoicesInLastAttempt) {
        $total_score_text .= '<div class="question_row">';
        $my_total_score_temp = $totalScore;
        if ($objExercise->selectPropagateNeg() == 0 && $my_total_score_temp < 0) {
            $my_total_score_temp = 0;
        }
        $total_score_text .= ExerciseLib::get_question_ribbon(
            $objExercise,
            $my_total_score_temp,
            $totalWeighting,
            true
        );
        $total_score_text .= '</div>';
    }
}

if (!empty($category_list) && ($show_results || $show_only_total_score || $showTotalScoreAndUserChoicesInLastAttempt)) {
    // Adding total
    $category_list['total'] = array(
        'score' => $my_total_score_temp,
        'total' => $totalWeighting
    );
    echo TestCategory::get_stats_table_by_attempt($objExercise->id, $category_list);
}

echo $total_score_text;
echo $exercise_content;
echo $total_score_text;

if ($isFeedbackAllowed) {
    if (is_array($arrid) && is_array($arrmarks)) {
        $strids = implode(",", $arrid);
        $marksid = implode(",", $arrmarks);
    }
}

if ($isFeedbackAllowed) {
    if (in_array($origin, array('tracking_course', 'user_course', 'correct_exercise_in_lp'))) {
        $formUrl = api_get_path(WEB_CODE_PATH) . 'exercise/exercise_report.php?' . api_get_cidreq() . '&';
        $formUrl .= http_build_query([
            'exerciseId' => $exercise_id,
            'filter' => 2,
            'comments' => 'update',
            'exeid' => $id,
            'origin' => $origin,
            'details' => 'true',
            'course' => Security::remove_XSS($_GET['cidReq'])
        ]);
        $formUrl .= $fromlink;

        echo '<form name="myform" id="myform" action="' . $formUrl . '" method="post">';
        echo '<input type = "hidden" name="lp_item_id"       value="' . $learnpath_id . '">';
        echo '<input type = "hidden" name="lp_item_view_id"  value="' . $lp_item_view_id . '">';
        echo '<input type = "hidden" name="student_id"       value="' . $student_id . '">';
        echo '<input type = "hidden" name="total_score"      value="' . $totalScore . '"> ';
        echo '<input type = "hidden" name="my_exe_exo_id"    value="' . $exercise_id . '"> ';
    } else {
        $formUrl = api_get_path(WEB_CODE_PATH) . 'exercise/exercise_report.php?' . api_get_cidreq() . '&';
        $formUrl .= http_build_query([
            'exerciseId' => $exercise_id,
            'filter' => 1,
            'comments' => 'update',
            'exeid' => $id
        ]);

        echo ' <form name="myform" id="myform" action="' . $formUrl . '" method="post">';
    }

    if ($origin != 'learnpath' && $origin != 'student_progress') {
        echo '<label><input type= "checkbox" name="send_notification"> ' . get_lang('SendEmail') . '</label>';
        ?>
        <br/>
        <button type="submit" class="btn btn-primary" value="<?php echo get_lang('Ok'); ?>"
                onclick="getFCK('<?php echo $strids; ?>','<?php echo $marksid; ?>');">
            <?php echo get_lang('CorrectTest'); ?>
        </button>
        </form>
        <?php
    }
}

//Came from lpstats in a lp
if ($origin == 'student_progress') { ?>
    <button type="button" class="back" onclick="window.history.go(-1);" value="<?php echo get_lang('Back'); ?>">
        <?php echo get_lang('Back'); ?></button>
    <?php
} else if ($origin == 'myprogress') {
    ?>
    <button type="button" class="save"
            onclick="top.location.href='../auth/my_progress.php?course=<?php echo api_get_course_id() ?>'"
            value="<?php echo get_lang('Finish'); ?>">
        <?php echo get_lang('Finish'); ?>
    </button>
    <?php
}

if ($origin != 'learnpath') {
    //we are not in learnpath tool
    Display::display_footer();
} else {
    if (!isset($_GET['fb_type'])) {
        $lp_mode = $_SESSION['lp_mode'];
        $url = '../lp/lp_controller.php?' . api_get_cidreq() . '&';
        $url .= http_build_url([
            'action' => 'view',
            'lp_id' => $learnpath_id,
            'lp_item_id' => $learnpath_item_id,
            'exeId' => $exeId,
            'fb_type' => $feedback_type
        ]);
        $href = ($lp_mode == 'fullscreen') ? ' window.opener.location.href="' . $url . '" ' : ' top.location.href="' . $url . '" ';
        echo '<script type="text/javascript">' . $href . '</script>';
        // Record the results in the learning path, using the SCORM interface (API)
        echo "<script>window.parent.API.void_save_asset('$totalScore', '$totalWeighting', 0, 'completed'); </script>";
        echo '</body></html>';
    } else {
        Display::display_normal_message(get_lang('ExerciseFinished') . ' ' . get_lang('ToContinueUseMenu'));
        echo '<br />';
    }
}

// Destroying the session
Session::erase('questionList');
unset ($questionList);

Session::erase('exerciseResult');
unset ($exerciseResult);

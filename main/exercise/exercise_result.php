<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Exercise result
 * This script gets information from the script "exercise_submit.php",
 * through the session, and calculates the score of the student for
 * that exercise.
 * Then it shows the results on the screen.
 *
 * @author  Olivier Brouckaert, main author
 * @author  Roan Embrechts, some refactoring
 * @author  Julio Montoya switchable fill in blank option added
 *
 * @todo    split more code up in functions, move functions to library?
 */
$debug = false;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;

api_protect_course_script(true);
$origin = api_get_origin();

/** @var Exercise $objExercise */
if (empty($objExercise)) {
    $objExercise = Session::read('objExercise');
}

$exeId = isset($_REQUEST['exe_id']) ? (int) $_REQUEST['exe_id'] : 0;
if (empty($objExercise)) {
    // Redirect to the exercise overview
    // Check if the exe_id exists
    $objExercise = new Exercise();
    $exercise_stat_info = $objExercise->get_stat_track_exercise_info_by_exe_id($exeId);
    if (!empty($exercise_stat_info) && isset($exercise_stat_info['exe_exo_id'])) {
        header('Location: overview.php?exerciseId='.$exercise_stat_info['exe_exo_id'].'&'.api_get_cidreq());
        exit;
    }
    api_not_allowed(true);
}

$js = '<script>'.api_get_language_translate_html().'</script>';
$htmlHeadXtra[] = $js;

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('ToolGradebook'),
    ];
}

$nameTools = get_lang('Exercises');
$currentUserId = api_get_user_id();

$interbreadcrumb[] = [
    'url' => 'exercise.php?'.api_get_cidreq(),
    'name' => get_lang('Exercises'),
];

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/js/hotspot.js"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/css/hotspot.css">';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'annotation/js/annotation.js"></script>';
if (api_get_configuration_value('quiz_prevent_copy_paste')) {
    $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'jquery.nocopypaste.js"></script>';
}

if (!empty($objExercise->getResultAccess())) {
    $htmlHeadXtra[] = api_get_css(
        api_get_path(WEB_LIBRARY_PATH).'javascript/epiclock/renderers/minute/epiclock.minute.css'
    );
    $htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.dateformat.min.js');
    $htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.epiclock.min.js');
    $htmlHeadXtra[] = api_get_js('epiclock/renderers/minute/epiclock.minute.js');
}

$showHeader = false;
$showFooter = false;
$pageActions = '';
$pageTop = '';
$pageBottom = '';
$pageContent = '';
$courseInfo = api_get_course_info();
if (!in_array($origin, ['learnpath', 'embeddable', 'mobileapp'])) {
    // So we are not in learnpath tool
    $showHeader = true;
}

// I'm in a preview mode as course admin. Display the action menu.
if (api_is_course_admin() && !in_array($origin, ['learnpath', 'embeddable'])) {
    $pageActions = Display::toolbarAction(
        'exercise_result_actions',
        [
            Display::url(
                Display::return_icon('back.png', get_lang('GoBackToQuestionList'), [], 32),
                'admin.php?'.api_get_cidreq().'&exerciseId='.$objExercise->id
            )
            .Display::url(
                Display::return_icon('settings.png', get_lang('ModifyExercise'), [], 32),
                'exercise_admin.php?'.api_get_cidreq().'&modifyExercise=yes&exerciseId='.$objExercise->id
            ),
        ]
    );
}
$exercise_stat_info = $objExercise->get_stat_track_exercise_info_by_exe_id($exeId);
$learnpath_id = isset($exercise_stat_info['orig_lp_id']) ? $exercise_stat_info['orig_lp_id'] : 0;
$learnpath_item_id = isset($exercise_stat_info['orig_lp_item_id']) ? $exercise_stat_info['orig_lp_item_id'] : 0;
$learnpath_item_view_id = isset($exercise_stat_info['orig_lp_item_view_id'])
    ? $exercise_stat_info['orig_lp_item_view_id'] : 0;

$logInfo = [
    'tool' => TOOL_QUIZ,
    'tool_id' => $objExercise->iId,
    'action' => $learnpath_id,
    'action_details' => $learnpath_id,
];
Event::registerLog($logInfo);

$allowSignature = ExerciseSignaturePlugin::exerciseHasSignatureActivated($objExercise);
if ($allowSignature) {
    $htmlHeadXtra[] = api_get_asset('signature_pad/signature_pad.umd.js');
}

if ($origin === 'learnpath') {
    $pageTop .= '
        <form method="GET" action="exercise.php?'.api_get_cidreq().'">
            <input type="hidden" name="origin" value='.$origin.'/>
            <input type="hidden" name="learnpath_id" value="'.$learnpath_id.'"/>
            <input type="hidden" name="learnpath_item_id" value="'.$learnpath_item_id.'"/>
            <input type="hidden" name="learnpath_item_view_id" value="'.$learnpath_item_view_id.'"/>
    ';
}

$i = $total_score = $max_score = 0;
$remainingMessage = '';
$attemptButton = '';
if ($origin !== 'embeddable') {
    $attemptButton = Display::toolbarButton(
        get_lang('AnotherAttempt'),
        api_get_path(WEB_CODE_PATH).'exercise/overview.php?'.api_get_cidreq().'&'.http_build_query(
            [
                'exerciseId' => $objExercise->id,
                'learnpath_id' => $learnpath_id,
                'learnpath_item_id' => $learnpath_item_id,
                'learnpath_item_view_id' => $learnpath_item_view_id,
            ]
        ),
        'pencil-square-o',
        'info'
    );
}

// We check if the user attempts before sending to the exercise_result.php
$attempt_count = Event::get_attempt_count(
    $currentUserId,
    $objExercise->id,
    $learnpath_id,
    $learnpath_item_id,
    $learnpath_item_view_id
);

if ($objExercise->selectAttempts() > 0) {
    if ($attempt_count >= $objExercise->selectAttempts()) {
        Display::addFlash(
            Display::return_message(
                sprintf(get_lang('ReachedMaxAttempts'), $objExercise->selectTitle(), $objExercise->selectAttempts()),
                'warning',
                false
            )
        );

        if (!in_array($origin, ['learnpath', 'embeddable'])) {
            $showFooter = true;
        }

        $template = new Template($nameTools, $showHeader, $showFooter);
        $template->assign('actions', $pageActions);
        $template->display_one_col_template();
        exit;
    } else {
        $attempt_count++;
        $remainingAttempts = $objExercise->selectAttempts() - $attempt_count;
        if ($remainingAttempts) {
            $attemptMessage = sprintf(get_lang('RemainingXAttempts'), $remainingAttempts);
            $remainingMessage = sprintf('<p>%s</p> %s', $attemptMessage, $attemptButton);
        }
    }
} else {
    $remainingMessage = $attemptButton ? "<p>$attemptButton</p>" : '';
}

$total_score = 0;
if (!empty($exercise_stat_info)) {
    $total_score = $exercise_stat_info['exe_result'];
}

$max_score = $objExercise->get_max_score();

if ($origin === 'embeddable') {
    $pageTop .= showEmbeddableFinishButton();
} else {
    Display::addFlash(
        Display::return_message(get_lang('Saved'), 'normal', false)
    );
}
$saveResults = true;
$feedbackType = $objExercise->getFeedbackType();

ob_start();
$stats = ExerciseLib::displayQuestionListByAttempt(
    $objExercise,
    $exeId,
    $saveResults,
    $remainingMessage,
    $allowSignature,
    api_get_configuration_value('quiz_results_answers_report'),
    false
);
$pageContent .= ob_get_contents();
ob_end_clean();
// Save here LP status
if (!empty($learnpath_id) && $saveResults) {
    // Save attempt in lp
    Exercise::saveExerciseInLp($learnpath_item_id, $exeId);
}

$notifications = api_get_configuration_value('exercise_finished_notification_settings');
if (!empty($notifications)) {
    $exerciseExtraFieldValue = new ExtraFieldValue('exercise');
    $attemptCountToSend = $attempt_count++;
    $wrongAnswersCount = $stats['failed_answers_count'];
    $exercisePassed = $stats['exercise_passed'];
    $countPendingQuestions = $stats['count_pending_questions'];
    // If there are no pending questions (Open questions).
    if (0 === $countPendingQuestions) {
        $totalScore = ExerciseLib::show_score($total_score, $max_score, false, true);
        $subject = sprintf(get_lang('WrongAttemptXInCourseX'), $attemptCountToSend, $courseInfo['title']);
        if ($exercisePassed) {
            $subject = sprintf(get_lang('ExerciseValidationInCourseX'), $courseInfo['title']);
        }

        if ($exercisePassed) {
            $extraFieldData = $exerciseExtraFieldValue->get_values_by_handler_and_field_variable(
                $objExercise->iId,
                'MailSuccess'
            );
        } else {
            $extraFieldData = $exerciseExtraFieldValue->get_values_by_handler_and_field_variable(
                $objExercise->iId,
                'MailAttempt'.$attemptCountToSend
            );
        }
        $content = '';
        if ($extraFieldData && isset($extraFieldData['value'])) {
            $content = $extraFieldData['value'];
            $content = ExerciseLib::parseContent($content, $stats, $objExercise, $exercise_stat_info);
            if (false === $exercisePassed) {
                if (0 !== $wrongAnswersCount) {
                    $content .= $stats['failed_answers_html'];
                }
            }

            // Send to student
            MessageManager::send_message($currentUserId, $subject, $content);
        }

        // Subject for notifications
        /*$subject = sprintf(get_lang('WrongAttemptXInCourseX'), $attemptCountToSend, $courseInfo['title']);
        if ($exercisePassed) {
            $subject = sprintf(get_lang('ExerciseValidationInCourseX'), $courseInfo['title']);
        }*/
        $extraFieldData = $exerciseExtraFieldValue->get_values_by_handler_and_field_variable(
            $objExercise->iId,
            'notifications'
        );

        $exerciseNotification = '';
        if ($extraFieldData && isset($extraFieldData['value'])) {
            $exerciseNotification = $extraFieldData['value'];
        }

        if (!empty($exerciseNotification) && !empty($notifications)) {
            foreach ($notifications as $name => $notificationList) {
                if ($exerciseNotification !== $name) {
                    continue;
                }
                foreach ($notificationList as $attemptData) {
                    $email = isset($attemptData['email']) ? $attemptData['email'] : '';
                    $emailList = explode(',', $email);
                    if (empty($emailList)) {
                        continue;
                    }
                    $attempts = $attemptData['attempts'];
                    foreach ($attempts as $attempt) {
                        $sendMessage = false;
                        if (isset($attempt['attempt']) && $attemptCountToSend !== (int) $attempt['attempt']) {
                            continue;
                        }

                        if (!isset($attempt['status'])) {
                            continue;
                        }

                        switch ($attempt['status']) {
                            case 'passed':
                                if ($exercisePassed) {
                                    $sendMessage = true;
                                }
                                break;
                            case 'failed':
                                if (false === $exercisePassed) {
                                    $sendMessage = true;
                                }
                                break;
                            case 'all':
                                $sendMessage = true;
                                break;
                        }

                        if ($sendMessage) {
                            $attachments = [];
                            if (isset($attempt['add_pdf']) && $attempt['add_pdf']) {
                                // Get pdf content
                                $pdfExtraData = $exerciseExtraFieldValue->get_values_by_handler_and_field_variable(
                                    $objExercise->iId,
                                    $attempt['add_pdf']
                                );

                                if ($pdfExtraData && isset($pdfExtraData['value'])) {
                                    $pdfContent = ExerciseLib::parseContent(
                                        $pdfExtraData['value'],
                                        $stats,
                                        $objExercise,
                                        $exercise_stat_info
                                    );

                                    @$pdf = new PDF();
                                    $filename = get_lang('Exercise');
                                    $cssFile = api_get_path(SYS_CSS_PATH).'themes/chamilo/default.css';
                                    $pdfPath = @$pdf->content_to_pdf(
                                        "<html><body>$pdfContent</body></html>",
                                        file_get_contents($cssFile),
                                        $filename,
                                        api_get_course_id(),
                                        'F',
                                        false,
                                        null,
                                        false,
                                        true
                                    );
                                    $attachments[] = ['filename' => $filename, 'path' => $pdfPath];
                                }
                            }

                            $content = isset($attempt['content_default']) ? $attempt['content_default'] : '';
                            if (isset($attempt['content'])) {
                                $extraFieldData = $exerciseExtraFieldValue->get_values_by_handler_and_field_variable(
                                    $objExercise->iId,
                                    $attempt['content']
                                );
                                if ($extraFieldData && isset($extraFieldData['value']) && !empty($extraFieldData['value'])) {
                                    $content = $extraFieldData['value'];
                                }
                            }

                            if (!empty($content)) {
                                $content = ExerciseLib::parseContent(
                                    $content,
                                    $stats,
                                    $objExercise,
                                    $exercise_stat_info
                                );
                                foreach ($emailList as $email) {
                                    if (empty($email)) {
                                        continue;
                                    }
                                    api_mail_html(
                                        null,
                                        $email,
                                        $subject,
                                        $content,
                                        null,
                                        null,
                                        [],
                                        $attachments
                                    );
                                }
                            }

                            if (isset($attempt['post_actions'])) {
                                foreach ($attempt['post_actions'] as $action => $params) {
                                    switch ($action) {
                                        case 'subscribe_student_to_courses':
                                            foreach ($params as $code) {
                                                CourseManager::subscribeUser($currentUserId, $code);
                                                break;
                                            }
                                            break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

$hookQuizEnd = HookQuizEnd::create();
$hookQuizEnd->setEventData(['exe_id' => $exeId]);
$hookQuizEnd->notifyQuizEnd();

// Unset session for clock time
ExerciseLib::exercise_time_control_delete(
    $objExercise->id,
    $learnpath_id,
    $learnpath_item_id
);
ExerciseLib::delete_chat_exercise_session($exeId);

if (!in_array($origin, ['learnpath', 'embeddable', 'mobileapp'])) {
    $pageBottom .= '<div class="question-return">';
    $pageBottom .= Display::url(
        get_lang('ReturnToCourseHomepage'),
        api_get_course_url(),
        ['class' => 'btn btn-primary']
    );
    $pageBottom .= '</div>';

    if (api_is_allowed_to_session_edit()) {
        Exercise::cleanSessionVariables();
    }

    $showFooter = true;
} elseif (in_array($origin, ['embeddable', 'mobileapp'])) {
    if (api_is_allowed_to_session_edit()) {
        Exercise::cleanSessionVariables();
    }
    Session::write('attempt_remaining', $remainingMessage);
    $showFooter = false;
} else {
    $lp_mode = Session::read('lp_mode');
    $url = '../lp/lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$learnpath_id
        .'&lp_item_id='.$learnpath_item_id.'&exeId='.$exeId
        .'&fb_type='.$objExercise->getFeedbackType().'#atoc_'.$learnpath_item_id;
    $href = $lp_mode === 'fullscreen' ? ' window.opener.location.href="'.$url.'" ' : ' top.location.href="'.$url.'"';

    if (api_is_allowed_to_session_edit()) {
        Exercise::cleanSessionVariables();
    }
    Session::write('attempt_remaining', $remainingMessage);

    // Record the results in the learning path, using the SCORM interface (API)
    $pageBottom .= "<script>window.parent.API.void_save_asset('$total_score', '$max_score', 0, 'completed');</script>";
    $pageBottom .= '<script type="text/javascript">'.$href.'</script>';
    $showFooter = false;
}

$template = new Template($nameTools, $showHeader, $showFooter);
$template->assign('page_top', $pageTop);
$template->assign('page_content', $pageContent);
$template->assign('page_bottom', $pageBottom);
$template->assign('allow_signature', $allowSignature);
$template->assign('exe_id', $exeId);
$template->assign('actions', $pageActions);
$template->assign('content', $template->fetch($template->get_template('exercise/result.tpl')));
$template->display_one_col_template();

function showEmbeddableFinishButton()
{
    $js = '<script>
        $(function () {
            $(\'.btn-close-quiz\').on(\'click\', function () {
                window.parent.$(\'video:not(.skip), audio:not(.skip)\').get(0).play();
            });
        });
    </script>';

    $html = Display::tag(
        'p',
        Display::toolbarButton(
            get_lang('GoBackToVideo'),
            '#',
            'undo',
            'warning',
            ['role' => 'button', 'class' => 'btn-close-quiz']
        ),
        ['class' => 'text-center']
    );

    return $js.PHP_EOL.$html;
}

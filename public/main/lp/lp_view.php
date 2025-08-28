<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CLp;
use ChamiloSession as Session;

/**
 * This file was originally the copy of document.php, but many modifications happened since then ;
 * the direct file view is not needed anymore, if the user uploads a scorm zip file, a directory
 * will be automatically created for it, and the files will be uncompressed there for example ;.
 *
 * @author Yannick Warnier <ywarnier@beeznest.org> - redesign
 * @author Denes Nagy, principal author
 * @author Isthvan Mandak, several new features
 * @author Roan Embrechts, code improvements and refactoring
 */
$use_anonymous = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script();
$origin = api_get_origin();

// To prevent the template class
$lp_id = !empty($_GET['lp_id']) ? (int) $_GET['lp_id'] : 0;
if (empty($lp_id)) {
    api_not_allowed();
}

$course_id = isset($_REQUEST['cid']) ? (int) $_REQUEST['cid'] : api_get_course_int_id();
$sessionId = isset($_REQUEST['sid']) ? (int) $_REQUEST['sid'] : api_get_session_id();

$courseInfo = api_get_course_info_by_id($course_id);
$course_code = $courseInfo['code'];
$user_id = api_get_user_id();
$course = api_get_course_entity($course_id);
$session = api_get_session_entity($sessionId);
$lpRepo = Container::getLpRepository();
/** @var learnpath $oLP */
$oLP = Session::read('oLP');
// Check if the learning path is visible for student - (LP requisites)
if (!api_is_platform_admin()) {
    if (!api_is_allowed_to_edit(null, true, false, false) &&
        !learnpath::is_lp_visible_for_student($lp, api_get_user_id(), $course)
    ) {
        api_not_allowed(true);
    }
}

// Checking visibility (eye icon)
$visibility = $lp->isVisible($course, $session);

if (false === $visibility &&
    !api_is_allowed_to_edit(false, true, false, false)
) {
    api_not_allowed(true);
}

$lp_item_id = $oLP->get_current_item_id();
$lpCurrentItemId = isset($_GET['lp_item_id']) ? (int) $_GET['lp_item_id'] : $oLP->get_current_item_id();
$lpType = $lp->getLpType();

if (!api_is_allowed_to_edit(null, true)) {
    $category = $lp->getCategory();
    $em = Database::getManager();
    if ($category) {
        $block = false;
        $user = api_get_user_entity($user_id);
        $users = $category->getUsers();
        if (!empty($users) && $users->count() > 0) {
            if ($user && !$category->hasUserAdded($user)) {
                $block = true;
            }
        }

        $isVisible = learnpath::categoryIsVisibleForStudent($category, $user, $course, $session);
        if ($isVisible) {
            $block = false;
        }

        if ($block) {
            api_not_allowed(true);
        }
    }
}

$my_style = Container::getThemeHelper()->getVisualTheme();
$ajaxUrl = api_get_path(WEB_AJAX_PATH).'lp.ajax.php?a=get_item_prerequisites&'.api_get_cidreq();
$htmlHeadXtra[] = '<script>
$(function() {
    $("div#log_content_cleaner").bind("click", function() {
        $("div#log_content").empty();
    });
});
var chamilo_xajax_handler = window.oxajax;
</script>';

$zoomOptions = api_get_setting('exercise.quiz_image_zoom', true);
if (isset($zoomOptions['options']) && !in_array($origin, ['embeddable', 'noheader'])) {
    $options = $zoomOptions['options'];
    $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'jquery.elevatezoom.js"></script>';
    $htmlHeadXtra[] = '<script>
        $(function() {
            $("img").each(function() {
                var attr = $(this).attr("data-zoom-image");
                // For some browsers, `attr` is undefined; for others,
                // `attr` is false.  Check for both.
                if (typeof attr !== typeof undefined && attr !== false) {
                    $(this).elevateZoom({
                        scrollZoom : true,
                        cursor: "crosshair",
                        tint:true,
                        tintColour:\'#CCC\',
                        tintOpacity:0.5,
                        zoomWindowWidth:'.$options['zoomWindowWidth'].',
                        zoomWindowHeight:'.$options['zoomWindowHeight'].'
                    });
                }
            });
        });
    </script>';
}

$allowLpItemTip = ('false' === api_get_setting('lp.hide_accessibility_label_on_lp_item'));

if ($allowLpItemTip) {
    $htmlHeadXtra[] = api_get_asset('qtip2/dist/jquery.qtip.js');
    $htmlHeadXtra[] = '<script>
    $(function() {
         $(".scorm_item_normal").qtip({
            content: {
                text: function(event, api) {
                    var item = $(this);
                    var itemId = $(this).attr("id");
                    itemId = itemId.replace("toc_", "");
                    var textToShow = "";
                    $.ajax({
                        type: "GET",
                        url: "'.$ajaxUrl.'&item_id="+ itemId,
                        async: false
                    })
                    .then(function(content) {
                        if (content == 1) {
                            textToShow = "'.addslashes(get_lang('Item can be viewed - Prerequisites completed')).'";
                            api.set("style.classes", "qtip-green qtip-shadow");
                        } else {
                            textToShow = content;
                            api.set("style.classes", "qtip-red qtip-shadow");
                        }
                        api.set("content.text", textToShow);
                        return textToShow;
                    });
                    return textToShow;
                }
            }
        });
    });
    </script>';
}

if ('impress' === $lp->getDefaultViewMod()) {
    $lp_id = $lp->getIid();
    $url = api_get_path(WEB_CODE_PATH)."lp/lp_impress.php?lp_id=$lp_id&".api_get_cidreq();
    header("Location: $url");
    exit;
}

// Prepare variables for the test tool (just in case) - honestly, this should disappear later on.
Session::write('scorm_view_id', $oLP->get_view_id());
Session::write('scorm_item_id', $lp_item_id);

// Reinit exercises variables to avoid spacename clashes (see exercise tool)
if (isset($exerciseResult) || isset($_SESSION['exerciseResult'])) {
    Session::erase('exerciseResult');
    Session::erase('objExercise');
    Session::erase('questionList');
    Session::erase('duration_time_previous');
    Session::erase('duration_time');
}

// additional APIs
$htmlHeadXtra[] = '<script>
chamilo_courseCode = "'.$course_code.'";
</script>';

$get_toc_list = $oLP->get_toc();
$get_teacher_buttons = $oLP->get_teacher_toc_buttons();

$itemType = '';
$type_quiz = false;
foreach ($get_toc_list as $toc) {
    if ($toc['id'] == $lpCurrentItemId) {
        $itemType = $toc['type'];
        $type_quiz = 'quiz' === $toc['type'];
    }
}

if (!isset($src)) {
    $src = null;
    switch ($lpType) {
        case CLp::LP_TYPE:
            $oLP->stop_previous_item();
            $htmlHeadXtra[] = '<script src="scorm_api.php?'.api_get_cidreq().'"></script>';
            $preReqCheck = $oLP->prerequisites_match($lp_item_id);

            if (true === $preReqCheck) {
                $src = $oLP->get_link(
                    'http',
                    $lp_item_id,
                    $get_toc_list
                );

                // Prevents FF 3.6 + Adobe Reader 9 bug see BT#794 when calling a pdf file in a LP.
                $file_info = parse_url($src);
                if (isset($file_info['path'])) {
                    $file_info = pathinfo($file_info['path']);
                }

                if (isset($file_info['extension']) &&
                    'pdf' === api_strtolower(substr($file_info['extension'], 0, 3))
                ) {
                    $src = api_get_path(WEB_CODE_PATH).'lp/lp_view_item.php?lp_item_id='.$lp_item_id.'&'.api_get_cidreq();
                }

                $src = $oLP->fixBlockedLinks($src);

                $oLP->start_current_item(); // starts time counter manually if asset
            } else {
                $src = 'blank.php?error=prerequisites';
            }
            break;
        case CLp::SCORM_TYPE:
            // save old if asset
            $oLP->stop_previous_item(); // save status manually if asset
            $htmlHeadXtra[] = '<script src="scorm_api.php?'.api_get_cidreq().'"></script>';
            $preReqCheck = $oLP->prerequisites_match($lp_item_id);

            if (true === $preReqCheck) {
                $src = $oLP->get_link('http', $lp_item_id, $get_toc_list);
                $oLP->start_current_item(); // starts time counter manually if asset
            } else {
                $src = 'blank.php?error=prerequisites';
            }
            break;
        case CLp::AICC_TYPE:
            $oLP->stop_previous_item(); // save status manually if asset
            $htmlHeadXtra[] = '<script src="'.$oLP->get_js_lib().'"></script>';
            $preReqCheck = $oLP->prerequisites_match($lp_item_id);
            if (true === $preReqCheck) {
                $src = $oLP->get_link(
                    'http',
                    $lp_item_id,
                    $get_toc_list
                );
                $oLP->start_current_item(); // starts time counter manually if asset
            } else {
                $src = 'blank.php';
            }
            break;
        case 4:
            break;
    }
}

$autostart = 'true';
// Update status, total_time from lp_item_view table when you finish the exercises in learning path.

if ($debug) {
    error_log('$type_quiz: '.$type_quiz);
    error_log('$_REQUEST[exeId]: '.(int) ($_REQUEST['exeId'] ?? 0));
    error_log('$lp_id: '.$lp_id);
    error_log('$_REQUEST[lp_item_id]: '.(int) ($_REQUEST['lp_item_id'] ?? 0));
}

if (!empty($_REQUEST['exeId']) &&
    isset($lp_id) &&
    isset($_REQUEST['lp_item_id'])
) {
    $oLP->items[$oLP->current]->write_to_db();
    $TBL_TRACK_EXERCICES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
    $TBL_LP_ITEM_VIEW = Database::get_course_table(TABLE_LP_ITEM_VIEW);
    $TBL_LP_ITEM = Database::get_course_table(TABLE_LP_ITEM);
    $safe_item_id = (int) $_REQUEST['lp_item_id'];
    $safe_id = $lp_id;
    $safe_exe_id = (int) $_REQUEST['exeId'];

    if (!empty($safe_id) && !empty($safe_item_id)) {
        Exercise::saveExerciseInLp($safe_item_id, $safe_exe_id, $course_id);
    }
    if (EXERCISE_FEEDBACK_TYPE_END != intval($_GET['fb_type'])) {
        $src = 'blank.php?msg=exerciseFinished&'.api_get_cidreq(true, true, 'learnpath');
    } else {
        $src = api_get_path(WEB_CODE_PATH).'exercise/result.php?id='.$safe_exe_id.'&'.api_get_cidreq(true, true, 'learnpath');
        if ($debug) {
            error_log('Calling URL: '.$src);
        }
    }
    $autostart = 'false';
}

$oLP->set_previous_item($lp_item_id);
$nameTools = Security::remove_XSS($oLP->get_name());

$save_setting = api_get_setting('show_navigation_menu');
/*global $_setting;
$_setting['show_navigation_menu'] = 'false';*/
$scorm_css_header = true;
$lp_theme_css = $oLP->get_theme();
// Sets the css theme of the LP this call is also use at the frames (toc, nav, message).
if ('fullscreen' === $oLP->mode) {
    $htmlHeadXtra[] = "<script>
        window.open('$src','content_id','toolbar=0,location=0,status=0,scrollbars=1,resizable=1');
    </script>";
}
// Set flag to ensure lp_header.php is loaded by this script (flag is unset in lp_header.php).
Session::write('loaded_lp_view', true);
$display_none = '';
$margin_left = '340px';

// Media player code
$display_mode = $lp->getDefaultViewMod();
$scorm_css_header = true;
$lp_theme_css = $lp->getTheme();

// Setting up the CSS theme if exists.
if (!empty($lp_theme_css) && !empty($mycourselptheme) && -1 != $mycourselptheme && 1 == $mycourselptheme) {
    //global $lp_theme_css;
} else {
    $lp_theme_css = $my_style;
}

$progress_bar = '';
if (!api_is_invitee()) {
    $progress_bar = $oLP->getProgressBar();
}
$navigation_bar = $oLP->get_navigation_bar();
$navigation_bar_bottom = $oLP->get_navigation_bar('control-bottom');
$mediaplayer = $oLP->get_mediaplayer($oLP->current, $autostart);

$tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
// Getting all the information about the item.
$sql = "SELECT audio FROM $tbl_lp_item
        WHERE lp_id = ".$lp->getIid();
$res_media = Database::query($sql);

$show_audioplayer = false;
if (Database::num_rows($res_media) > 0) {
    while ($row_media = Database::fetch_array($res_media)) {
        if (!empty($row_media['audio'])) {
            $show_audioplayer = true;
            break;
        }
    }
}

$is_allowed_to_edit = api_is_allowed_to_edit(false, true, true, false);

if ($is_allowed_to_edit) {
    $interbreadcrumb[] = [
        'url' => api_get_self().'?action=list&isStudentView=false&'.api_get_cidreq(true, true, 'course'),
        'name' => get_lang('Learning paths'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_self().
            "?action=add_item&type=step&lp_id={$lp->getIid()}&isStudentView=false&".api_get_cidreq(true, true, 'course'),
        'name' => $oLP->getNameNoTags(),
    ];

    $interbreadcrumb[] = [
        'url' => '#',
        'name' => get_lang('Preview'),
    ];
    $buttonHomeUrl = 'lp_controller.php?'.api_get_cidreq(true, true, 'course').'&'.http_build_query([
        'isStudentView' => 'false',
        'action' => 'return_to_course_homepage',
    ]);
} else {
    $buttonHomeUrl = 'lp_controller.php?'.api_get_cidreq(true, true, 'course').'&'.http_build_query([
        'action' => 'return_to_course_homepage',
    ]);
}

$buttonHomeText = get_lang('Course home');
$returnLink = api_get_course_setting('lp_return_link');
switch ($returnLink) {
    case 0: // to course home
        $buttonHomeUrl .= '&redirectTo=course_home&lp_id='.$lp->getIid();
        $buttonHomeText = get_lang('Course home');
        break;
    case 1: // lp list
        $buttonHomeUrl .= '&redirectTo=lp_list';
        $buttonHomeText = get_lang('Learning path list');
        break;
    case 2: // My courses
        $buttonHomeUrl .= '&redirectTo=my_courses&lp_id='.$lp->getIid();
        $buttonHomeText = get_lang('My courses');
        break;
    case 3: // Portal home
        $buttonHomeUrl .= '&redirectTo=portal_home&lp_id='.$lp->getIid();
        $buttonHomeText = get_lang('Home');
        break;
    case 4: // My sessions
        $buttonHomeUrl .= '&redirectTo=my_sessions&lp_id='.$lp->getIid();
        $buttonHomeText = get_lang('My sessions');
        break;
}

$lpPreviewImagePath = Display::returnIconPath('unknown.png', ICON_SIZE_BIG);
if ($lp->getResourceNode()->hasResourceFile()) {
    $lpPreviewImagePath = $lpRepo->getResourceFileUrl($lp).'?'.api_get_cidreq();
}

if ($oLP->current == $oLP->get_last()) {
    $categories = Category::load(
        null,
        null,
        $course_id,
        null,
        null,
        $sessionId
    );

    if (!empty($categories)) {
        $evaluations = $categories[0]->get_evaluations();
        $gradebookLinks = $categories[0]->get_links();

        if (0 === count($evaluations) &&
            1 === count($gradebookLinks) &&
            LINK_LEARNPATH == $gradebookLinks[0]->get_type() &&
            $gradebookLinks[0]->get_ref_id() == $oLP->lp_id
        ) {
            $minScore = $categories[0]->getCertificateMinScore();
            $userScore = $gradebookLinks[0]->calc_score($user_id, 'best');
            $categoryEntity = Container::getGradeBookCategoryRepository()->find($categories[0]->get_id());
            if (isset($userScore) && $userScore[0] >= $minScore) {
                Category::generateUserCertificate($categoryEntity, $user_id);
            }
        }
    }
}

$template = new Template('', false, false, true, true, false);
$fixLinkSetting = api_get_configuration_value('lp_fix_embed_content');
$fixLink = '';
if ($fixLinkSetting) {
    $fixLink = '{type:"script", id:"_fr10", src:"'.api_get_path(WEB_LIBRARY_PATH).'fixlinks.js"}';
}

$template->assign('fix_link', $fixLink);
$template->assign('glossary_tool_available_list', ['true', 'lp', 'exercise_and_lp']);

// If the global gamification mode is enabled...
$gamificationMode = api_get_setting('gamification_mode');
// ...AND this learning path is set in gamification mode, then change the display
$gamificationMode = $gamificationMode && $lp->getSeriousgameMode();

$template->assign('gamification_mode', $gamificationMode);
$template->assign('glossary_extra_tools', api_get_setting('show_glossary_in_extra_tools'));
$template->assign('show_glossary_in_documents', api_get_setting('show_glossary_in_documents'));
$template->assign('jquery_web_path', api_get_jquery_web_path());
$template->assign('jquery_ui_js_web_path', api_get_jquery_ui_js_web_path());
$template->assign('jquery_ui_css_web_path', api_get_jquery_ui_css_web_path());
$template->assign('is_allowed_to_edit', $is_allowed_to_edit);
$template->assign('button_home_url', $buttonHomeUrl);
$template->assign('button_home_text', $buttonHomeText);
$template->assign('navigation_bar', $navigation_bar);
$template->assign('progress_bar', $progress_bar);
$template->assign('show_audio_player', $show_audioplayer);
$template->assign('media_player', $mediaplayer);
$template->assign('toc_list', $get_toc_list);
$template->assign('teacher_toc_buttons', $get_teacher_buttons);
$template->assign('iframe_src', $src);
$template->assign('navigation_bar_bottom', $navigation_bar_bottom);
$template->assign('show_left_column', !$lp->getHideTocFrame());

$showMenu = 0;
$settings = api_get_setting('lp.lp_view_settings', true);
$display = $settings['display'] ?? false;
$navigationInTheMiddle = false;
if (!empty($display)) {
    $showMenu = isset($display['show_toolbar_by_default']) && $display['show_toolbar_by_default'] ? 1 : 0;
    $navigationInTheMiddle = isset($display['navigation_in_the_middle']) && $display['navigation_in_the_middle'] ? 1 : 0;
}

$template->assign('show_toolbar_by_default', $showMenu);
$template->assign('navigation_in_the_middle', $navigationInTheMiddle);

if (1 == $gamificationMode) {
    $template->assign('gamification_stars', $oLP->getCalculateStars($sessionId));
    $template->assign('gamification_points', $oLP->getCalculateScore($sessionId));
}

$template->assign('lp_author', $lp->getAuthor());

$lpMinTime = '';
if (Tracking::minimumTimeAvailable(api_get_session_id(), api_get_course_int_id())) {
    // Calculate minimum and accumulated time
    $timeLp = $_SESSION['oLP']->getAccumulateWorkTime();
    $timeTotalCourse = $_SESSION['oLP']->getAccumulateWorkTimeTotalCourse();
    // Minimum connection percentage
    $perc = 100;
    // Time from the course
    $tc = $timeTotalCourse;
    // Percentage of the learning paths
    $pl = 0;
    if (!empty($timeTotalCourse)) {
        $pl = $timeLp / $timeTotalCourse;
    }

    // Minimum time for each learning path
    $time_min = (int) ($pl * $tc * $perc / 100);

    if ($_SESSION['oLP']->getAccumulateWorkTime() > 0) {
        $lpMinTime = '('.$time_min.' min)';
    }

    $lpTimeList = Tracking::getCalculateTime($user_id, api_get_course_int_id(), api_get_session_id());
    $lpTime = isset($lpTimeList[TOOL_LEARNPATH][$lp_id]) ? (int) $lpTimeList[TOOL_LEARNPATH][$lp_id] : 0;

    if ($lpTime >= ($time_min * 60)) {
        $time_progress_perc = '100%';
        $time_progress_value = 100;
    } else {
        $time_progress_value = intval(($lpTime * 100) / ($time_min * 60));
        $time_progress_perc = $time_progress_value.'%';
    }

    $template->assign('time_progress_perc', $time_progress_perc);
    $template->assign('time_progress_value', $time_progress_value);
    // Cronometro
    $hour = (intval($lpTime / 3600)) < 10 ? '0'.intval($lpTime / 3600) : intval($lpTime / 3600);
    $template->assign('hour', $hour);
    $template->assign('minute', date('i', $lpTime));
    $template->assign('second', date('s', $lpTime));
    $template->assign('hour_min', api_time_to_hms($timeLp * 60, '</div><div class="divider">:</div><div>'));
}

$template->assign('lp_accumulate_work_time', $lpMinTime);
$template->assign('lp_mode', $lp->getDefaultViewMod());
$template->assign('lp_title_scorm', stripslashes($lp->getTitle()));
$template->assign('lp_item_parents', $oLP->getCurrentItemParentNames($lpCurrentItemId));

// @todo Fix lp_view_accordion
/*if ('true' === api_get_setting('lp.lp_view_accordion') && 1 == $lpType) {
    $template->assign('data_panel', $oLP->getTOCTree());
    $template->assign('data_list', null);
} else {*/

$template->assign('data_panel', null);
//echo '<pre>';var_dump($oLP->get_toc(), array_column($oLP->get_toc(), 'status_class', 'id'));exit;
$template->assign('status_list', array_column($oLP->get_toc(), 'status_class', 'id'));
$template->assign('data_list', $oLP->getListArrayToc());
//var_dump($oLP->getListArrayToc($get_toc_list));

$template->assign('lp_id', $lp->getIid());
$template->assign('lp_current_item_id', isset($_GET['lp_item_id']) ? (int) $_GET['lp_item_id'] : $oLP->get_current_item_id());

$menuLocation = 'left';
if ('false' !== api_get_setting('lp.lp_menu_location')) {
    $menuLocation = api_get_setting('lp.lp_menu_location');
}
$template->assign('menu_location', $menuLocation);
$template->assign('disable_js_in_lp_view', (int) ('true' === api_get_setting('lp.disable_js_in_lp_view')));
$template->assign('lp_preview_image', '<img src="'.$lpPreviewImagePath.'" alt="'.$oLP->getNameNoTags().'" />');

if ('video' === $itemType) {
    $src = api_get_path(WEB_CODE_PATH)
        . "lp/lp_video_view.php?lp_id=$lp_id&lp_item_id=$lpCurrentItemId&" . api_get_cidreq();
}
$htmlHeadXtra[] = '<script>
    olms.lms_item_types["i'.$lpCurrentItemId.'"] = "'.$itemType.'";
</script>';
$frameReady = Display::getFrameReadyBlock(
    '#content_id, #content_id_blank',
    $itemType,
    'function () {
        var arr = ["link", "sco", "xapi", "quiz", "h5p", "forum", "survey"];

        return $.inArray(olms.lms_item_type, arr) !== -1;
    }'
);
$template->assign('frame_ready', $frameReady);
$template->displayTemplate('@ChamiloCore/LearnPath/view.html.twig');

// Restore a global setting.
//$_setting['show_navigation_menu'] = $save_setting;

//Session::write('oLP', $lp);
Session::write('oLP', $oLP);

if ($debug) {
    error_log(' ------- end lp_view.php ------');
}

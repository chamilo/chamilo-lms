<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CLpCategory;
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
$this_section = SECTION_COURSES;

if ($lp_controller_touched != 1) {
    header('Location: lp_controller.php?action=view&item_id='.intval($_REQUEST['item_id']));
    exit;
}

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script();

if (isset($_REQUEST['origin']) && $_REQUEST['origin'] === 'learnpath') {
    $_REQUEST['origin'] = '';
}

// To prevent the template class
$lp_id = !empty($_GET['lp_id']) ? (int) $_GET['lp_id'] : 0;
$sessionId = api_get_session_id();
$course_code = api_get_course_id();
$course_id = api_get_course_int_id();
$user_id = api_get_user_id();

// Check if the learning path is visible for student - (LP requisites)
if (!api_is_platform_admin()) {
    if (!api_is_allowed_to_edit(null, true, false, false) &&
        !learnpath::is_lp_visible_for_student($lp_id, api_get_user_id())
    ) {
        api_not_allowed(true);
    }
}

// Checking visibility (eye icon)
$visibility = api_get_item_visibility(
    api_get_course_info(),
    TOOL_LEARNPATH,
    $lp_id,
    $action,
    api_get_user_id(),
    $sessionId
);

if ($visibility === 0 &&
    !api_is_allowed_to_edit(false, true, false, false)
) {
    api_not_allowed(true);
}

/** @var learnpath $lp */
$lp = Session::read('oLP');

if (empty($lp)) {
    api_not_allowed(true);
}

$debug = 0;
if ($debug) {
    error_log('------ Entering lp_view.php -------');
}

$lp_item_id = $lp->get_current_item_id();
$lpType = $lp->get_type();

if (!$is_allowed_to_edit) {
    $categoryId = $lp->getCategoryId();
    $em = Database::getManager();
    if (!empty($categoryId)) {
        /** @var CLpCategory $category */
        $category = $em->getRepository('ChamiloCourseBundle:CLpCategory')->find($categoryId);
        $block = false;
        if ($category) {
            $user = UserManager::getRepository()->find($user_id);
            $users = $category->getUsers();
            if (!empty($users) && $users->count() > 0) {
                if ($user && !$category->hasUserAdded($user)) {
                    $block = true;
                }
            }

            $isVisible = learnpath::categoryIsVisibleForStudent(
                $category,
                $user
            );

            if ($isVisible) {
                $block = false;
            }

            if ($block) {
                api_not_allowed(true);
            }
        }
    }
}

$platform_theme = api_get_setting('stylesheets');
$my_style = $platform_theme;
$extraParams = '';
if (isset($_REQUEST['lti_launch_id'])) {
    $extraParams .= '&lti_launch_id='.Security::remove_XSS($_REQUEST['lti_launch_id']);
}
$ajaxUrl = api_get_path(WEB_AJAX_PATH).'lp.ajax.php?a=get_item_prerequisites&'.api_get_cidreq().$extraParams;
$htmlHeadXtra[] = '<script>
<!--
var jQueryFrameReadyConfigPath = \''.api_get_jquery_web_path().'\';
-->
</script>';

$htmlHeadXtra[] = api_get_css_asset('qtip2/jquery.qtip.min.css');
$htmlHeadXtra[] = api_get_asset('qtip2/jquery.qtip.min.js');
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.frameready.js"></script>';
$htmlHeadXtra[] = '<script>
$(function() {
    $("div#log_content_cleaner").bind("click", function() {
        $("div#log_content").empty();
    });
});
var chamilo_xajax_handler = window.oxajax;
</script>';
if (!empty($lp->lti_launch_id)) {
    if (isset($_REQUEST['from']) && 'lti_provider' == $_REQUEST['from']) {
        $logout = api_get_path(WEB_PATH).'plugin/lti_provider/tool/logout.php?uid='.api_get_user_id();
        $htmlHeadXtra[] = '<script>
            $(function() {
              if ($("#btn-menu-float").length > 0) {
                $("#btn-menu-float").find("#home-course").show();
                $("#btn-menu-float").find("#home-course").attr("href", "'.$logout.'");
              }
            });
        </script>';
    } else {
        $htmlHeadXtra[] = '<script>
                $(function() {
                  if ($("#btn-menu-float").length > 0) {
                    $("#btn-menu-float").find("#home-course").hide();
                  }
                });
        </script>';
    }
}

$zoomOptions = api_get_configuration_value('quiz_image_zoom');
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

$allowLpItemTip = api_get_configuration_value('hide_accessibility_label_on_lp_item') === false;
if ($allowLpItemTip) {
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
                            textToShow = "'.addslashes(get_lang('LPItemCanBeAccessed')).'";
                            api.set("style.classes", "qtip-green qtip-shadow");
                        } else {
                            textToShow = content;
                            api.set("style.classes", "qtip-red qtip-shadow");
                        }

                        return textToShow;
                    });
                    return textToShow;
                }
            }
        });
    });
    </script>';
}

// Impress js
if ($lp->mode === 'impress') {
    $lp_id = $lp->get_id();
    $url = api_get_path(WEB_CODE_PATH)."lp/lp_impress.php?lp_id=$lp_id&".api_get_cidreq();
    header("Location: $url");
    exit;
}

// Prepare variables for the test tool (just in case) - honestly, this should disappear later on.
Session::write('scorm_view_id', $lp->get_view_id());
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
// Document API
$htmlHeadXtra[] = '<script src="js/documentapi.js" type="text/javascript" language="javascript"></script>';
// Storage API
$htmlHeadXtra[] = '<script>
var sv_user = \''.api_get_user_id().'\';
var sv_course = chamilo_courseCode;
var sv_sco = \''.$lp_id.'\';
</script>'; // FIXME fetch sco and userid from a more reliable source directly in sotrageapi.js
$htmlHeadXtra[] = '<script type="text/javascript" src="js/storageapi.js"></script>';

/**
 * Get a link to the corresponding document.
 */
if ($debug) {
    error_log(" src: $src ");
    error_log(" lp_type: $lpType ");
}

$get_toc_list = $lp->get_toc();
$get_teacher_buttons = $lp->get_teacher_toc_buttons();
$flowButtons = $lp->getFlowLpbuttons();
$itemType = '';
foreach ($get_toc_list as $toc) {
    if ($toc['id'] == $lp_item_id) {
        $itemType = $toc['type'];
    }
}

if (!isset($src)) {
    $src = null;
    switch ($lpType) {
        case 1:
            $lp->stop_previous_item();
            $htmlHeadXtra[] = '<script src="scorm_api.php" type="text/javascript" language="javascript"></script>';
            $preReqCheck = $lp->prerequisites_match($lp_item_id);

            if ($preReqCheck === true) {
                $src = $lp->get_link(
                    'http',
                    $lp_item_id,
                    $get_toc_list
                );

                if (empty($src)) {
                    $src = 'blank.php?'.api_get_cidreq().'&error=document_protected';
                    break;
                }

                // Prevents FF 3.6 + Adobe Reader 9 bug see BT#794 when calling a pdf file in a LP.
                $file_info = parse_url($src);
                if (isset($file_info['path'])) {
                    $file_info = pathinfo($file_info['path']);
                }

                if (isset($file_info['extension']) &&
                    api_strtolower(substr($file_info['extension'], 0, 3)) == 'pdf'
                ) {
                    $src = api_get_path(WEB_CODE_PATH).'lp/lp_view_item.php?lp_item_id='.$lp_item_id.'&'.api_get_cidreq();
                }

                $src = $lp->fixBlockedLinks($src);

                if (WhispeakAuthPlugin::isLpItemMarked($lp_item_id)) {
                    ChamiloSession::write(
                        WhispeakAuthPlugin::SESSION_LP_ITEM,
                        ['lp' => $lp->lp_id, 'lp_item' => $lp_item_id, 'src' => $src]
                    );

                    $src = api_get_path(WEB_PLUGIN_PATH).'whispeakauth/authentify.php';
                    break;
                }

                // This change the status to complete in Chamilo LP.
                $lp->start_current_item(); // starts time counter manually if asset
            } else {
                $src = 'blank.php?error=prerequisites';
            }
            break;
        case 2:
            // save old if asset
            $lp->stop_previous_item(); // save status manually if asset
            $htmlHeadXtra[] = '<script src="scorm_api.php" type="text/javascript" language="javascript"></script>';
            $preReqCheck = $lp->prerequisites_match($lp_item_id);
            if ($preReqCheck === true) {
                $src = $lp->get_link('http', $lp_item_id, $get_toc_list);
                $lp->start_current_item(); // starts time counter manually if asset
            } else {
                $src = 'blank.php?error=prerequisites';
            }
            break;
        case 3:
            // aicc
            $lp->stop_previous_item(); // save status manually if asset
            $htmlHeadXtra[] = '<script src="'.$lp->get_js_lib().'" type="text/javascript" language="javascript"></script>';
            $preReqCheck = $lp->prerequisites_match($lp_item_id);
            if ($preReqCheck === true) {
                $src = $lp->get_link(
                    'http',
                    $lp_item_id,
                    $get_toc_list
                );
                $lp->start_current_item(); // starts time counter manually if asset
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
    error_log('$_REQUEST[exeId]: '.intval($_REQUEST['exeId']));
    error_log('$lp_id: '.$lp_id);
    error_log('$_REQUEST[lp_item_id]: '.intval($_REQUEST['lp_item_id']));
}

if (!empty($_REQUEST['exeId']) &&
    isset($lp_id) &&
    isset($_REQUEST['lp_item_id'])
) {
    global $src;
    $lp->items[$lp->current]->write_to_db();

    $TBL_TRACK_EXERCICES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
    $TBL_LP_ITEM_VIEW = Database::get_course_table(TABLE_LP_ITEM_VIEW);
    $TBL_LP_ITEM = Database::get_course_table(TABLE_LP_ITEM);
    $safe_item_id = (int) $_REQUEST['lp_item_id'];
    $safe_id = $lp_id;
    $safe_exe_id = (int) $_REQUEST['exeId'];

    if (!empty($safe_id) && !empty($safe_item_id)) {
        Exercise::saveExerciseInLp($safe_item_id, $safe_exe_id);
    }
    /*if (intval($_GET['fb_type']) != EXERCISE_FEEDBACK_TYPE_END) {
       $src = 'blank.php?msg=exerciseFinished&'.api_get_cidreq(true, true, 'learnpath');
    } else {*/
    $src = api_get_path(WEB_CODE_PATH).'exercise/result.php?id='.$safe_exe_id.'&'.api_get_cidreq(true, true, 'learnpath');
    if ($debug) {
        error_log('Calling URL: '.$src);
    }
    $autostart = 'false';
}

$lp->set_previous_item($lp_item_id);
$nameTools = Security::remove_XSS($lp->get_name());

$save_setting = api_get_setting('show_navigation_menu');
global $_setting;
$_setting['show_navigation_menu'] = 'false';
$scorm_css_header = true;
$lp_theme_css = $lp->get_theme();
// Sets the css theme of the LP this call is also use at the frames (toc, nav, message).
if ($lp->mode === 'fullscreen') {
    $htmlHeadXtra[] = "<script>
        window.open('$src','content_id','toolbar=0,location=0,status=0,scrollbars=1,resizable=1');
    </script>";
}
// Set flag to ensure lp_header.php is loaded by this script (flag is unset in lp_header.php).
Session::write('loaded_lp_view', true);
$display_none = '';
$margin_left = '340px';

// Media player code
$display_mode = $lp->mode;
$scorm_css_header = true;
$lp_theme_css = $lp->get_theme();

// Setting up the CSS theme if exists.
if (!empty($lp_theme_css) && !empty($mycourselptheme) && $mycourselptheme != -1 && $mycourselptheme == 1) {
    global $lp_theme_css;
} else {
    $lp_theme_css = $my_style;
}

$progress_bar = '';
if (!api_is_invitee()) {
    $progress_bar = $lp->getProgressBar();
}
$navigation_bar = $lp->get_navigation_bar();
$navigation_bar_bottom = $lp->get_navigation_bar('control-bottom');
$mediaplayer = $lp->get_mediaplayer($lp->current, $autostart);

$tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
// Getting all the information about the item.
$sql = "SELECT audio FROM $tbl_lp_item
        WHERE c_id = $course_id AND lp_id = ".$lp->lp_id;
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

global $interbreadcrumb;
if ($is_allowed_to_edit) {
    $interbreadcrumb[] = [
        'url' => api_get_self().'?action=list&isStudentView=false&'.api_get_cidreq(true, true, 'course'),
        'name' => get_lang('LearningPaths'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_self()."?action=add_item&type=step&lp_id={$lp->lp_id}&isStudentView=false&".api_get_cidreq(true, true, 'course'),
        'name' => $lp->getNameNoTags(),
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

$buttonHomeText = get_lang('CourseHomepageLink');
$returnLink = api_get_course_setting('lp_return_link');
switch ($returnLink) {
    case 1: // lp list
        $buttonHomeUrl .= '&redirectTo=lp_list';
        $buttonHomeText = get_lang('LearningPathList');
        break;
    case 2: // Course home
        $buttonHomeUrl .= '&redirectTo=my_courses';
        $buttonHomeText = get_lang('MyCourses');
        break;
    case 3: // Portal home
        $buttonHomeUrl .= '&redirectTo=portal_home';
        $buttonHomeText = get_lang('Home');
        break;
}

$lpPreviewImagePath = Display::returnIconPath('unknown.png', ICON_SIZE_BIG);
if ($lp->get_preview_image()) {
    $lpPreviewImagePath = $lp->get_preview_image_path();
}

if ($lp->current == $lp->get_last()) {
    $categories = Category::load(
        null,
        null,
        $course_code,
        null,
        null,
        $sessionId
    );

    if (!empty($categories)) {
        $gradebookEvaluations = $categories[0]->get_evaluations();
        $gradebookLinks = $categories[0]->get_links();

        if (count($gradebookEvaluations) === 0 &&
            count($gradebookLinks) === 1 &&
            $gradebookLinks[0]->get_type() == LINK_LEARNPATH &&
            $gradebookLinks[0]->get_ref_id() == $lp->lp_id
        ) {
            $gradebookMinScore = $categories[0]->getCertificateMinScore();
            $userScore = $gradebookLinks[0]->calc_score($user_id, 'best');

            if ($userScore[0] >= $gradebookMinScore) {
                Category::generateUserCertificate($categories[0]->get_id(), $user_id);
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
$gamificationMode = $gamificationMode && $lp->seriousgame_mode;

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
$template->assign('flow_buttons', $flowButtons);
$template->assign('iframe_src', $src);
$template->assign('navigation_bar_bottom', $navigation_bar_bottom);
$template->assign('show_left_column', $lp->getHideTableOfContents() == 0);

$showMenu = 0;
$settings = api_get_configuration_value('lp_view_settings');
$display = isset($settings['display']) ? $settings['display'] : false;
$navigationInTheMiddle = false;
$addExtraQuitToHomeIcon = false;
if (!empty($display)) {
    $showMenu = isset($display['show_toolbar_by_default']) && $display['show_toolbar_by_default'] ? 1 : 0;
    $navigationInTheMiddle = isset($display['navigation_in_the_middle']) && $display['navigation_in_the_middle'] ? 1 : 0;
    $addExtraQuitToHomeIcon = $display['add_extra_quit_to_home_icon'] ?? false;

    $value = (new ExtraFieldValue('lp'))->get_values_by_handler_and_field_variable($lp_id, 'add_extra_quit_button');

    if (is_array($value)) {
        $addExtraQuitToHomeIcon = $value['value'] !== '0';
    }
}

$template->assign('show_toolbar_by_default', $showMenu);
$template->assign('navigation_in_the_middle', $navigationInTheMiddle);
$template->assign('add_extra_quit_to_home_icon', $addExtraQuitToHomeIcon);

if ($gamificationMode == 1) {
    $template->assign('gamification_stars', $lp->getCalculateStars($sessionId));
    $template->assign('gamification_points', $lp->getCalculateScore($sessionId));
}

$template->assign('lp_author', $lp->get_author());

$lpMinTime = '';
if (Tracking::minimumTimeAvailable(api_get_session_id(), api_get_course_int_id())) {
    // Calulate minimum and accumulated time
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

$template->assign('lp_mode', $lp->mode);
$template->assign('lp_title_scorm', $lp->get_name());
if (api_get_configuration_value('lp_view_accordion') === true && $lpType == 1) {
    $template->assign('data_panel', $lp->getTOCTree());
} else {
    $template->assign('data_list', $lp->getListArrayToc($get_toc_list));
}
$template->assign('lp_id', $lp->lp_id);
$template->assign('lp_current_item_id', $lp->get_current_item_id());

$menuLocation = 'left';
if (!empty(api_get_configuration_value('lp_menu_location'))) {
    $menuLocation = api_get_configuration_value('lp_menu_location');
}
$template->assign('menu_location', $menuLocation);
$template->assign('disable_js_in_lp_view', (int) api_get_configuration_value('disable_js_in_lp_view'));
$template->assign(
    'lp_preview_image',
    Display::img(
        $lpPreviewImagePath,
        $lp->getNameNoTags(),
        [],
        ICON_SIZE_BIG
    )
);

$frameReady = Display::getFrameReadyBlock(
    '#content_id, #content_id_blank',
    $itemType,
    'function () {
        var arr = ["link", "sco", "xapi", "quiz", "h5p"];

        return $.inArray(olms.lms_item_type, arr) !== -1;
    }'
);
$template->assign('frame_ready', $frameReady);

$view = $template->get_template('learnpath/view.tpl');
$content = $template->fetch($view);

$template->assign('content', $content);
$template->display_no_layout_template();

// Restore a global setting.
$_setting['show_navigation_menu'] = $save_setting;

Session::write('oLP', $lp);

if ($debug) {
    error_log(' ------- end lp_view.php ------');
}

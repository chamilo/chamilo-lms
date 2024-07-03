<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CLpCategory;
use ChamiloSession as Session;

/**
 * This file was originally the copy of document.php, but many modifications happened since then ;
 * the direct file view is not any more needed, if the user uploads a SCORM zip file, a directory
 * will be automatically created for it, and the files will be uncompressed there for example ;.
 *
 * @author  Yannick Warnier <ywarnier@beeznest.org>
 */
$this_section = SECTION_COURSES;
//@todo who turns on $lp_controller_touched?
if (empty($lp_controller_touched) || $lp_controller_touched != 1) {
    header('Location: lp_controller.php?action=list&'.api_get_cidreq());
    exit;
}

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script();

$courseDir = api_get_course_path().'/scorm';
$baseWordDir = $courseDir;

/**
 * Display initialisation and security checks.
 */
// Extra javascript functions for in html head:
$htmlHeadXtra[] = "<script>
function confirmation(name) {
    if (confirm(\" ".trim(get_lang('AreYouSureToDeleteJS'))." \"+name+\"?\")) {
        return true;
    } else {
        return false;
    }
}
</script>";
$nameTools = get_lang('LearningPaths');
Event::event_access_tool(TOOL_LEARNPATH);

/* Require the search widget and prepare the header with its stuff. */
if (api_get_setting('search_enabled') === 'true') {
    require api_get_path(LIBRARY_PATH).'search/search_widget.php';
    search_widget_prepare($htmlHeadXtra);
}
$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
$courseInfo = api_get_course_info();

$subscriptionSettings = learnpath::getSubscriptionSettings();

/* Introduction section (editable by course admins) */
$introduction = Display::return_introduction_section(
    TOOL_LEARNPATH,
    [
        'CreateDocumentWebDir' => api_get_path(WEB_COURSE_PATH)
            .api_get_course_path().'/document/',
        'CreateDocumentDir' => '../..'.api_get_path(REL_COURSE_PATH)
            .api_get_course_path().'/document/',
        'BaseHref' => api_get_path(WEB_COURSE_PATH)
            .api_get_course_path().'/',
    ]
);

$message = '';
$actions = '';

$allowCategory = true;
if (!empty($sessionId)) {
    $allowCategory = false;
    if (api_get_configuration_value('allow_session_lp_category')) {
        $allowCategory = true;
    }
}

if ($is_allowed_to_edit) {
    $actionLeft = '';
    $actionLeft .= Display::url(
        Display::return_icon(
            'new_learnpath.png',
            get_lang('LearnpathAddLearnpath'),
            '',
            ICON_SIZE_MEDIUM
        ),
        api_get_self().'?'.api_get_cidreq().'&action=add_lp'
    );
    $actionLeft .= Display::url(
        Display::return_icon(
            'import_scorm.png',
            get_lang('UploadScorm'),
            '',
            ICON_SIZE_MEDIUM
        ),
        '../upload/index.php?'.api_get_cidreq().'&curdirpath=/&tool='.TOOL_LEARNPATH
    );

    if (api_get_setting('service_ppt2lp', 'active') === 'true') {
        $actionLeft .= Display::url(
            Display::return_icon(
                'import_powerpoint.png',
                get_lang('PowerPointConvert'),
                '',
                ICON_SIZE_MEDIUM
            ),
            '../upload/upload_ppt.php?'.api_get_cidreq().'&curdirpath=/&tool='.TOOL_LEARNPATH
        );
    }

    if ($allowCategory) {
        $actionLeft .= Display::url(
            Display::return_icon(
                'new_folder.png',
                get_lang('AddCategory'),
                [],
                ICON_SIZE_MEDIUM
            ),
            api_get_self().'?'.api_get_cidreq().'&action=add_lp_category'
        );
    }

    if ('true' === api_get_plugin_setting('ai_helper', 'tool_enable') &&
        'true' === api_get_plugin_setting('ai_helper', 'tool_lp_enable')
    ) {
        $actionLeft .= Display::url(
            Display::return_icon(
                'new_learnpath_ai.png',
                get_lang('LpAiGenerator'),
                [],
                ICON_SIZE_MEDIUM
            ),
            api_get_self().'?'.api_get_cidreq().'&action=ai_helper'
        );
    }

    $actions = Display::toolbarAction('actions-lp', [$actionLeft]);
}

$token = Security::get_token();
$categoriesTempList = learnpath::getCategories($courseId);
$firstSessionCategoryId = 0;
if ($allowCategory) {
    $newCategoryFiltered = [];
    foreach ($categoriesTempList as $category) {
        $categorySessionId = (int) learnpath::getCategorySessionId($category->getId());
        if ($categorySessionId === $sessionId || $categorySessionId === 0) {
            $newCategoryFiltered[] = $category;
        }
        if (!empty($sessionId) && empty($firstSessionCategoryId) && $categorySessionId == $sessionId) {
            $firstSessionCategoryId = $category->getId();
        }
    }

    $categoriesTempList = $newCategoryFiltered;
}

$categoryTest = new CLpCategory();
$categoryTest->setId(0);
$categoryTest->setName(get_lang('WithOutCategory'));
$categoryTest->setPosition(0);
$categories = [$categoryTest];

if (!empty($categoriesTempList)) {
    $categories = array_merge($categories, $categoriesTempList);
}
$userId = api_get_user_id();
$userInfo = api_get_user_info();
$lpIsShown = false;
$filteredCategoryId = ($action === 'view_category' && !empty($_GET['id'])) ? intval($_GET['id']) : 0;

if ($filteredCategoryId) {
    /** @var CLpCategory $category */
    foreach ($categories as $category) {
        if ($category->getId() != $filteredCategoryId) {
            continue;
        }

        $interbreadcrumb[] = ['name' => $nameTools, 'url' => api_get_self()];
        $nameTools = strip_tags($category->getName());
    }
}

$test_mode = api_get_setting('server_type');
$showBlockedPrerequisite = api_get_configuration_value('show_prerequisite_as_blocked');
$allowLpChamiloExport = api_get_configuration_value('allow_lp_chamilo_export');
$allowMinTime = Tracking::minimumTimeAvailable($sessionId, $courseId);
$accumulateWorkTimeTotal = 0;
if ($allowMinTime) {
    $accumulateWorkTimeTotal = learnpath::getAccumulateWorkTimeTotal($courseId);
}

$user = api_get_user_entity($userId);
$ending = true;
$allLpTimeValid = true;
$isInvitee = api_is_invitee();
$hideScormExportLink = api_get_setting('hide_scorm_export_link');
$hideScormCopyLink = api_get_setting('hide_scorm_copy_link');
$hideScormPdfLink = api_get_setting('hide_scorm_pdf_link');
$options = learnpath::getIconSelect();
$cidReq = api_get_cidreq();

$defaultLpIcon = Display::return_icon(
    'learnpath.png',
    get_lang('LPName')
);

$defaultDisableLpIcon = Display::return_icon(
    'learnpath_na.png',
    get_lang('LPName')
);

$courseSettingsIcon = Display::return_icon(
    'settings.png',
    get_lang('CourseSettings')
);

$courseSettingsDisableIcon = Display::return_icon(
    'settings_na.png',
    get_lang('CourseSettings')
);

$enableAutoLaunch = api_get_course_setting('enable_lp_auto_launch');
$gameMode = api_get_setting('gamification_mode');
$allowDatesForStudent = api_get_configuration_value('lp_start_and_end_date_visible_in_student_view');

$data = [];
$tableCategory = Database::get_course_table(TABLE_LP_CATEGORY);
/** @var CLpCategory $item */
foreach ($categories as $item) {
    $categoryId = $item->getId();
    if (!empty($sessionId) && $allowCategory) {
        $categorySessionId = learnpath::getCategorySessionId($categoryId);
        $item->setSessionId($categorySessionId);
    }

    if ($categoryId !== 0 && $subscriptionSettings['allow_add_users_to_lp_category'] == true) {
        // "Without category" has id = 0
        $categoryVisibility = api_get_item_visibility(
            $courseInfo,
            TOOL_LEARNPATH_CATEGORY,
            $categoryId,
            $sessionId
        );

        if (!$is_allowed_to_edit) {
            if ($categoryVisibility !== 1 && $categoryVisibility != -1) {
                continue;
            }
        }
        if ($allowCategory && !empty($sessionId)) {
            // Check base course
            if (0 === $item->getSessionId()) {
                $categoryVisibility = api_get_item_visibility(
                    $courseInfo,
                    TOOL_LEARNPATH_CATEGORY,
                    $categoryId,
                    0
                );
                if ($categoryVisibility == 0) {
                    continue;
                }
            }
        }

        if ($user && !learnpath::categoryIsVisibleForStudent($item, $user)) {
            continue;
        }
    }

    $list = new LearnpathList(
        $userId,
        $courseInfo,
        $sessionId,
        null,
        false,
        $categoryId
    );

    $flat_list = $list->get_flat_list();

    // Hiding categories with out LPs (only for student)
    if (empty($flat_list) && !$is_allowed_to_edit) {
        continue;
    }

    $listData = [];
    $lpTimeList = [];
    if ($allowMinTime) {
        $lpTimeList = Tracking::getCalculateTime($userId, $courseId, $sessionId);
    }

    if (!empty($flat_list)) {
        $max = count($flat_list);
        $counter = 0;
        $current = 0;
        $autolaunch_exists = false;

        $progressList = learnpath::getProgressFromLpList(
            array_column($flat_list, 'lp_old_id'),
            $userId,
            $courseId,
            $sessionId
        );

        $now = time();
        foreach ($flat_list as $id => $details) {
            $id = $details['lp_old_id'];

            if (!$is_allowed_to_edit && $details['lp_visibility'] == 0) {
                // This is a student and this path is invisible, skip.
                continue;
            }

            $lpVisibility = learnpath::is_lp_visible_for_student($id, $userId, $courseInfo);

            // Check if the learnpath is visible for student.
            if (!$is_allowed_to_edit) {
                $isBlocked = learnpath::isBlockedByPrerequisite(
                    $userId,
                    $details['prerequisite'],
                    $courseInfo,
                    $sessionId
                );
                if ($lpVisibility === false && $isBlocked && $showBlockedPrerequisite === false) {
                    continue;
                }
            }

            $start_time = $end_time = '';
            if (!empty($details['publicated_on'])) {
                $start_time = api_convert_and_format_date($details['publicated_on'], DATE_TIME_FORMAT_LONG_24H);
            }
            if (!empty($details['expired_on'])) {
                $end_time = api_convert_and_format_date($details['expired_on'], DATE_TIME_FORMAT_LONG_24H);
            }

            if (!$is_allowed_to_edit) {
                $time_limits = false;
                // This is an old LP (from a migration 1.8.7) so we do nothing
                if (empty($details['created_on']) && empty($details['modified_on'])) {
                    $time_limits = false;
                }

                // Checking if expired_on is ON
                if ($details['expired_on'] != '') {
                    $time_limits = true;
                }

                if ($time_limits) {
                    // Check if start time
                    if (!empty($details['publicated_on']) && !empty($details['expired_on'])) {
                        $start_time = api_strtotime($details['publicated_on'], 'UTC');
                        $end_time = api_strtotime($details['expired_on'], 'UTC');
                        $is_actived_time = false;
                        if ($now > $start_time && $end_time > $now) {
                            $is_actived_time = true;
                        }

                        if (!$is_actived_time) {
                            continue;
                        }
                    }
                }
            }

            $counter++;
            $oddclass = 'row_even';
            if (($counter % 2) == 0) {
                $oddclass = 'row_odd';
            }

            $url_start_lp = 'lp_controller.php?'.$cidReq.'&action=view&lp_id='.$id;
            if (api_get_configuration_value('save_titles_as_html')) {
                $name = trim(Security::remove_XSS($details['lp_name']));
            } else {
                $name = trim(strip_tags(Security::remove_XSS($details['lp_name'])));
            }
            $extra = null;

            if ($is_allowed_to_edit) {
                // @todo This line is what makes the teacher switch to
                //   student view automatically. Many teachers are confused
                //   by that, so maybe a solution can be found to avoid it
                $url_start_lp .= '&isStudentView=true';
                $dsp_desc = '<em>'.$details['lp_maker'].'</em> '
                    .($lpVisibility
                        ? ''
                        : ' - ('.get_lang('LPNotVisibleToStudent').')');
                $extra = '<div class ="lp_content_type_label">'.$dsp_desc.'</div>';
            }

            $my_title = $name;
            $icon_learnpath = $defaultLpIcon;
            if ($details['lp_visibility'] == 0) {
                $my_title = Display::tag(
                    'font',
                    $name,
                    ['class' => 'text-muted']
                );
                $icon_learnpath = $defaultDisableLpIcon;
            }

            if (!empty($options)) {
                $icon = learnpath::getSelectedIconHtml($id);
                if (!empty($icon)) {
                    $icon_learnpath = $icon;
                }
            }

            // Students can see the lp but is inactive
            if (!$is_allowed_to_edit && $lpVisibility == false &&
                $showBlockedPrerequisite == true
            ) {
                $my_title = Display::tag(
                    'font',
                    $name,
                    ['class' => 'text-muted']
                );
                $icon_learnpath = $defaultDisableLpIcon;
                $url_start_lp = '#';
            }

            $dsp_desc = '';
            $dsp_export = '';
            $dsp_build = '';
            $dsp_delete = '';
            $dsp_visible = '';
            $trackingAction = '';
            $dsp_default_view = '';
            $dsp_debug = '';
            $dsp_order = '';
            $progress = 0;
            if (!$isInvitee) {
                $progress = isset($progressList[$id]) && !empty($progressList[$id]) ? $progressList[$id] : 0;
            }

            if ($is_allowed_to_edit) {
                $dsp_progress = '<center>'.$progress.'%</center>';
            } else {
                $dsp_progress = '';
                if (!$isInvitee) {
                    $dsp_progress = learnpath::get_progress_bar($progress, '%');
                }
            }

            if ($progress < 100) {
                $ending = false;
            }

            $dsp_time = '';
            $linkMinTime = '';
            if ($allowMinTime) {
                // Minimum time (in minutes) to pass the learning path
                $accumulateWorkTime = learnpath::getAccumulateWorkTimePrerequisite($id, $courseId);
                if ($accumulateWorkTime > 0) {
                    $lpTime = isset($lpTimeList[TOOL_LEARNPATH][$id]) ? $lpTimeList[TOOL_LEARNPATH][$id] : 0;

                    // Connect with the plugin_licences_course_session table
                    // which indicates what percentage of the time applies
                    $perc = 100;

                    // Percentage of the learning paths
                    $pl = 0;
                    if (!empty($accumulateWorkTimeTotal)) {
                        $pl = $accumulateWorkTime / $accumulateWorkTimeTotal;
                    }

                    // Minimum time for each learning path
                    $accumulateWorkTime = ($pl * $accumulateWorkTimeTotal * $perc / 100);

                    // If the time spent is less than necessary, then we show an icon in the actions column indicating the warning
                    $formattedLpTime = api_time_to_hms($lpTime);
                    $formattedAccumulateWorkTime = api_time_to_hms($accumulateWorkTime * 60);
                    if ($lpTime < ($accumulateWorkTime * 60)) {
                        $allLpTimeValid = false;
                        $linkMinTime = Display::return_icon(
                            'warning.png',
                            get_lang('LpMinTimeWarning').' - '.
                            $formattedLpTime.' / '.
                            $formattedAccumulateWorkTime
                        );
                    } else {
                        $linkMinTime = Display::return_icon(
                            'check.png',
                            get_lang('LpMinTimeWarning').' - '.
                            $formattedLpTime.' / '.
                            $formattedAccumulateWorkTime
                        );
                    }
                    $linkMinTime .= '&nbsp;<b>'.$formattedLpTime.' / '.$formattedAccumulateWorkTime.'</b>';

                    // Calculate the percentage exceeded of the time for the "exceeding the minimum time" bar
                    if ($lpTime >= ($accumulateWorkTime * 60)) {
                        $time_progress_perc = '100%';
                        $time_progress_value = 100;
                    } else {
                        $time_progress_value = (int) (($lpTime * 100) / ($accumulateWorkTime * 60));
                    }

                    if ($time_progress_value < 100) {
                        $ending = false;
                    }
                    $dsp_time = learnpath::get_progress_bar($time_progress_value, '%');
                } else {
                    $allLpTimeValid = false;
                }
            }

            $token_parameter = "&sec_token=$token";
            $dsp_edit_lp = null;
            $dsp_publish = null;
            $dsp_reinit = null;
            $subscribeUsers = null;
            $dsp_disk = null;
            $copy = null;
            $lp_auto_launch_icon = null;
            $actionSeriousGame = null;
            $actionUpdateScormFile = '';
            $actionExportToCourseBuild = '';
            // Only for "Chamilo" packages
            $allowExportCourseFormat = $allowLpChamiloExport && $details['lp_maker'] === 'Chamilo';

            if ($is_allowed_to_edit) {
                // EDIT LP
                if ($sessionId == $details['lp_session']) {
                    $dsp_edit_lp = Display::url(
                        $courseSettingsIcon,
                        'lp_controller.php?'.$cidReq."&action=edit&lp_id=$id"
                    );
                } else {
                    $dsp_edit_lp = $courseSettingsDisableIcon;
                }

                // BUILD
                if ($sessionId == $details['lp_session']) {
                    if ($details['lp_type'] == 1 || $details['lp_type'] == 2) {
                        $dsp_build = Display::url(
                            Display::return_icon(
                                'edit.png',
                                get_lang('LearnpathEditLearnpath')
                            ),
                            'lp_controller.php?'.$cidReq.'&'
                                .http_build_query(
                                    [
                                        'action' => 'add_item',
                                        'type' => 'step',
                                        'lp_id' => $id,
                                        'isStudentView' => 'false',
                                    ]
                                )
                        );
                    } else {
                        $dsp_build = Display::return_icon(
                            'edit_na.png',
                            get_lang('LearnpathEditLearnpath')
                        );
                    }
                } else {
                    $dsp_build = Display::return_icon(
                        'edit_na.png',
                        get_lang('LearnpathEditLearnpath')
                    );
                }

                /* VISIBILITY COMMAND */
                /*  Session test not necessary if we want to show base course learning
                    paths inside the session.
                    See http://support.chamilo.org/projects/chamilo-18/wiki/Tools_and_sessions).
                */
                if (!isset($details['subscribe_users']) ||
                    $details['subscribe_users'] != 1
                ) {
                    if ($details['lp_visibility'] == 0) {
                        $dsp_visible = Display::url(
                            Display::return_icon(
                                'invisible.png',
                                get_lang('Show')
                            ),
                            api_get_self().'?'.$cidReq."&lp_id=$id&action=toggle_visible&new_status=1"
                        );
                    } else {
                        $dsp_visible = Display::url(
                            Display::return_icon('visible.png', get_lang('Hide')),
                            api_get_self().'?'.$cidReq."&lp_id=$id&action=toggle_visible&new_status=0"
                        );
                    }
                }

                // Tracking command
                $trackingActionUrl = 'lp_controller.php?'.$cidReq.'&'.http_build_query(
                    ['action' => 'report', 'lp_id' => $id]
                );

                $trackingAction = Display::url(
                    Display::return_icon(
                        'test_results.png',
                        get_lang('Results')
                    ),
                    $trackingActionUrl
                );

                /* PUBLISH COMMAND */
                //if ($sessionId == $details['lp_session']) {
                if ($details['lp_published'] === 'i') {
                    $dsp_publish = Display::url(
                            Display::return_icon(
                                'lp_publish_na.png',
                                get_lang('LearnpathPublish')
                            ),
                            api_get_self().'?'.$cidReq."&lp_id=$id&action=toggle_publish&new_status=v"
                        );
                } else {
                    $dsp_publish = Display::url(
                            Display::return_icon(
                                'lp_publish.png',
                                get_lang('LearnpathDoNotPublish')
                            ),
                            api_get_self().'?'.$cidReq."&lp_id=$id&action=toggle_publish&new_status=i"
                        );
                }
                /*} else {
                    $dsp_publish = Display::return_icon(
                        'lp_publish_na.png',
                        get_lang('LearnpathDoNotPublish')
                    );
                }*/

                /*  MULTIPLE ATTEMPTS OR SERIOUS GAME MODE
                  SERIOUSGAME MODE is a special mode where :
                 * If a user exits the learning path before finishing it, he comes back where he left next time he tries
                 * When lp status is completed, user can still modify the attempt (adds/time change score, and browse it)
                 * It is thus a mix betwenn multiple attempt and mono attempt
                 */
                if ($sessionId == $details['lp_session']) {
                    if ($details['seriousgame_mode'] == 1 && $details['lp_prevent_reinit'] == 1) {
                        // seriousgame mode | next = single
                        $dsp_reinit = Display::url(
                            Display::return_icon(
                                'reload.png',
                                get_lang('PreventMultipleAttempts')
                            ),
                            'lp_controller.php?'.$cidReq."&action=switch_attempt_mode&lp_id=$id"
                        );
                    }
                    if ($details['seriousgame_mode'] == 0 &&
                        $details['lp_prevent_reinit'] == 1
                    ) {
                        // single mode | next = multiple
                        $dsp_reinit = Display::url(
                            Display::return_icon(
                                'reload_na.png',
                                get_lang('AllowMultipleAttempts')
                            ),
                            'lp_controller.php?'.$cidReq."&action=switch_attempt_mode&lp_id=$id"
                        );
                    }
                    if ($details['seriousgame_mode'] == 0 &&
                        $details['lp_prevent_reinit'] == 0
                    ) {
                        // multiple mode | next = seriousgame
                        $dsp_reinit = Display::url(
                            Display::return_icon(
                                'reload.png',
                                get_lang('AllowMultipleAttempts')
                            ),
                            'lp_controller.php?'.$cidReq."&action=switch_attempt_mode&lp_id=$id"
                        );
                    }
                } else {
                    $dsp_reinit = Display::return_icon(
                        'reload_na.png',
                        get_lang('AllowMultipleAttempts')
                    );
                }

                /* SCREEN LP VIEW */
                if ($sessionId == $details['lp_session']) {
                    switch ($details['lp_view_mode']) {
                        case 'fullscreen':
                            $dsp_default_view = Display::url(
                                Display::return_icon(
                                    'view_fullscreen.png',
                                    get_lang('ViewModeFullScreen')
                                ),
                                'lp_controller.php?'.$cidReq.'&action=switch_view_mode&lp_id='.$id.$token_parameter
                            );
                            break;
                        case 'embedded':
                            $dsp_default_view = Display::url(
                                Display::return_icon(
                                    'view_left_right.png',
                                    get_lang('ViewModeEmbedded')
                                ),
                                'lp_controller.php?'.$cidReq.'&action=switch_view_mode&lp_id='.$id.$token_parameter
                            );
                            break;
                        case 'embedframe':
                            $dsp_default_view = Display::url(
                                Display::return_icon(
                                    'view_nofullscreen.png',
                                    get_lang('ViewModeEmbedFrame')
                                ),
                                'lp_controller.php?'.$cidReq.'&action=switch_view_mode&lp_id='.$id.$token_parameter
                            );
                            break;
                        case 'impress':
                            $dsp_default_view = Display::url(
                                Display::return_icon(
                                    'window_list_slide.png',
                                    get_lang('ViewModeImpress')
                                ),
                                'lp_controller.php?'.$cidReq.'&action=switch_view_mode&lp_id='.$id.$token_parameter
                            );
                            break;
                    }
                } else {
                    if ($details['lp_view_mode'] === 'fullscreen') {
                        $dsp_default_view = Display::return_icon(
                            'view_fullscreen_na.png',
                            get_lang('ViewModeEmbedded')
                        );
                    } else {
                        $dsp_default_view = Display::return_icon(
                            'view_left_right_na.png',
                            get_lang('ViewModeEmbedded')
                        );
                    }
                }

                /*  DEBUG  */
                if ($test_mode === 'test' || api_is_platform_admin()) {
                    if ($details['lp_scorm_debug'] == 1) {
                        $dsp_debug = Display::url(
                            Display::return_icon(
                                'bug.png',
                                get_lang('HideDebug')
                            ),
                            "lp_controller.php?$cidReq&action=switch_scorm_debug&lp_id=$id"
                        );
                    } else {
                        $dsp_debug = Display::url(
                            Display::return_icon(
                                'bug_na.png',
                                get_lang('ShowDebug')
                            ),
                            'lp_controller.php?'.$cidReq."&action=switch_scorm_debug&lp_id=$id"
                        );
                    }
                }

                // Copy
                if (!api_get_configuration_value('lp_hide_copy_option')) {
                    $copy = Display::url(
                        Display::return_icon('cd_copy.png', get_lang('Copy')),
                        api_get_self().'?'.$cidReq."&action=copy&lp_id=$id"
                    );
                }

                // Subscribe users
                $subscribeUsers = '';
                if ($details['subscribe_users'] == 1 &&
                    $subscriptionSettings['allow_add_users_to_lp']
                ) {
                    $subscribeUsers = Display::url(
                        Display::return_icon(
                            'user.png',
                            get_lang('SubscribeUsersToLp')
                        ),
                        api_get_path(WEB_CODE_PATH)."lp/lp_subscribe_users.php?lp_id=$id&".$cidReq
                    );
                }

                /* Auto launch LP code */
                if ($enableAutoLaunch == 1) {
                    if ($details['autolaunch'] == 1 &&
                        $autolaunch_exists == false
                    ) {
                        $autolaunch_exists = true;
                        $lp_auto_launch_icon = Display::url(
                            Display::return_icon(
                                'launch.png',
                                get_lang('DisableLPAutoLaunch')
                            ),
                            api_get_self().'?'.$cidReq."&action=auto_launch&status=0&lp_id=$id"
                        );
                    } else {
                        $lp_auto_launch_icon = Display::url(
                            Display::return_icon(
                                'launch_na.png',
                                get_lang('EnableLPAutoLaunch')
                            ),
                            api_get_self().'?'.$cidReq."&action=auto_launch&status=1&lp_id=$id"
                        );
                    }
                }

                // Export to PDF
                $export_icon = Display::url(
                    Display::return_icon(
                        'pdf.png',
                        get_lang('ExportToPDFOnlyHTMLAndImages')
                    ),
                    api_get_self().'?'.$cidReq."&action=export_to_pdf&lp_id=$id"
                );

                /* Delete */
                if ($sessionId == $details['lp_session']) {
                    $dsp_delete = Display::url(
                        Display::return_icon(
                            'delete.png',
                            get_lang('LearnpathDeleteLearnpath')
                        ),
                        'lp_controller.php?'.$cidReq."&action=delete&lp_id=$id",
                        [
                            'onclick' => "javascript: return confirmation('".addslashes(strip_tags($name))."');",
                        ]
                    );
                } else {
                    $dsp_delete = Display::return_icon(
                        'delete_na.png',
                        get_lang('LearnpathDeleteLearnpath')
                    );
                }

                /* COLUMN ORDER	 */
                // Only active while session mode is not active
                if ($sessionId == 0) {
                    if ($details['lp_display_order'] == 1 && $max != 1) {
                        $dsp_order .= Display::url(
                            Display::return_icon('down.png', get_lang('MoveDown')),
                            "lp_controller.php?$cidReq&action=move_lp_down&lp_id=$id&category_id=$categoryId"
                        );
                    } elseif ($current == $max - 1 && $max != 1) {
                        $dsp_order .= Display::url(
                            Display::return_icon('up.png', get_lang('MoveUp')),
                            "lp_controller.php?$cidReq&action=move_lp_up&lp_id=$id&category_id=$categoryId"
                        );
                    } elseif ($max == 1) {
                        $dsp_order = '';
                    } else {
                        $dsp_order .= Display::url(
                            Display::return_icon('down.png', get_lang('MoveDown')),
                            "lp_controller.php?$cidReq&action=move_lp_down&lp_id=$id&category_id=$categoryId"
                        );
                        $dsp_order .= Display::url(
                            Display::return_icon('up.png', get_lang('MoveUp')),
                            "lp_controller.php?$cidReq&action=move_lp_up&lp_id=$id&category_id=$categoryId"
                        );
                    }
                }

                if ($details['lp_type'] == 2) {
                    $url = api_get_path(WEB_CODE_PATH).'lp/lp_update_scorm.php?'.$cidReq."&lp_id=$id";
                    $actionUpdateScormFile = Display::url(
                        Display::return_icon('upload_file.png', get_lang('Update')),
                        $url
                    );
                }

                if ($allowExportCourseFormat) {
                    $actionExportToCourseBuild = Display::url(
                        Display::return_icon(
                            'backup.png',
                            get_lang('ExportToChamiloFormat')
                        ),
                        api_get_self().'?'.$cidReq."&action=export_to_course_build&lp_id=$id"
                    );
                }

                if ($gameMode == 1) {
                    if ($details['seriousgame_mode'] == 0) {
                        $actionSeriousGame = Display::toolbarButton(
                            null,
                            api_get_self().'?'.$cidReq
                                ."&lp_id=$id&action=toggle_seriousgame",
                            'trophy',
                            'default',
                            [
                                'class' => 'btn-xs',
                                'title' => get_lang('EnableGamificationMode'),
                            ]
                        );
                    } else {
                        $actionSeriousGame = Display::toolbarButton(
                            null,
                            api_get_self().'?'.$cidReq
                                ."&lp_id=$id&action=toggle_seriousgame",
                            'trophy',
                            'warning',
                            [
                                'class' => 'btn-xs active',
                                'title' => get_lang('DisableGamificationMode'),
                            ]
                        );
                    }
                }
            } else {
                // Student
                $export_icon = Display::url(
                    Display::return_icon('pdf.png', get_lang('ExportToPDF')),
                    api_get_self().'?'.$cidReq."&action=export_to_pdf&lp_id=$id"
                );
            }

            /* Export */
            if ($details['lp_type'] == 1) {
                $dsp_disk = Display::url(
                    Display::return_icon('cd.png', get_lang('ExportShort')),
                    api_get_self()."?$cidReq&action=export&lp_id=$id"
                );
            } else {
                $dsp_disk = Display::return_icon(
                    'cd_na.png',
                    get_lang('ExportShort')
                );
            }

            if ($hideScormExportLink === 'true'
                || (false === api_get_configuration_value('lp_allow_export_to_students') && !$is_allowed_to_edit)
            ) {
                $dsp_disk = null;
            }

            if ($hideScormCopyLink === 'true') {
                $copy = null;
            }

            if ($hideScormPdfLink === 'true') {
                $export_icon = null;
            }

            $sessionImage = api_get_session_image(
                $details['lp_session'],
                $userInfo['status']
            );

            $listData[] = [
                'learnpath_icon' => $icon_learnpath,
                'url_start' => $url_start_lp,
                'title' => $my_title,
                'session_image' => $sessionImage,
                'extra' => $extra,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'dsp_progress' => $dsp_progress,
                'action_build' => $dsp_build,
                'action_edit' => $dsp_edit_lp,
                'action_tracking' => $trackingAction,
                'action_visible' => $dsp_visible,
                'action_publish' => $dsp_publish,
                'action_reinit' => $dsp_reinit,
                'action_default_view' => $dsp_default_view,
                'action_debug' => $dsp_debug,
                'action_export' => $dsp_disk,
                'action_copy' => $copy,
                'action_auto_launch' => $lp_auto_launch_icon,
                'action_pdf' => $export_icon,
                'action_delete' => $dsp_delete,
                'action_order' => $dsp_order,
                'action_serious_game' => $actionSeriousGame,
                'action_subscribe_users' => $subscribeUsers,
                'action_update_scorm' => $actionUpdateScormFile,
                'action_export_to_course_build' => $actionExportToCourseBuild,
                'info_time_prerequisite' => $linkMinTime,
            ];

            $lpIsShown = true;
            // Counter for number of elements treated
            $current++;
        }
    }

    $data[] = [
        'category' => $item,
        'category_visibility' => api_get_item_visibility(
            $courseInfo,
            TOOL_LEARNPATH_CATEGORY,
            $item->getId(),
            $sessionId
        ),
        'category_is_published' => learnpath::categoryIsPublished($item, $courseInfo['real_id']),
        'lp_list' => $listData,
    ];
}

// Deleting the objects
Session::erase('oLP');
Session::erase('lpobject');
Session::erase('scorm_view_id');
Session::erase('scorm_item_id');
Session::erase('exerciseResult');
Session::erase('objExercise');
Session::erase('questionList');

learnpath::generate_learning_path_folder($courseInfo);
DocumentManager::removeGeneratedAudioTempFile();

$downloadFileAfterFinish = '';
if ($ending && $allLpTimeValid && api_get_configuration_value('download_files_after_all_lp_finished')) {
    $downloadFilesSetting = api_get_configuration_value('download_files_after_all_lp_finished');
    $courseCode = $courseInfo['code'];
    $downloadFinishId = isset($_REQUEST['download_finished']) ? (int) $_REQUEST['download_finished'] : 0;
    if (isset($downloadFilesSetting['courses'][$courseCode])) {
        $files = $downloadFilesSetting['courses'][$courseCode];
        $coursePath = $courseInfo['course_sys_path'].'/document';
        foreach ($files as $documentId) {
            $documentData = DocumentManager::get_document_data_by_id($documentId, $courseCode);
            if ($documentData) {
                $downloadFileAfterFinish .= Display::url(
                    get_lang('Download').': '.$documentData['title'],
                    api_get_self().'?'.api_get_cidreq().'&download_finished='.$documentId,
                    ['class' => 'btn btn-primary']
                );
                if ($downloadFinishId === $documentId) {
                    $docUrl = $documentData['path'];
                    if (Security::check_abs_path($coursePath.$docUrl, $coursePath.'/')) {
                        Event::event_download($docUrl);
                        DocumentManager::file_send_for_download($coursePath.$docUrl, true);
                        exit;
                    }
                }
            }
        }
    }
}

$template = new Template($nameTools);
$template->assign('first_session_category', $firstSessionCategoryId);
$template->assign('session_star_icon', Display::return_icon('star.png', get_lang('Session')));
$template->assign('subscription_settings', $subscriptionSettings);
$template->assign('is_allowed_to_edit', $is_allowed_to_edit);
$template->assign('is_invitee', $isInvitee);
$template->assign('is_ending', $ending);
$template->assign('download_files_after_finish', $downloadFileAfterFinish);
$template->assign('actions', $actions);
$template->assign('categories', $categories);
$template->assign('message', $message);
$template->assign('introduction', $introduction);
$template->assign('data', $data);
$template->assign('lp_is_shown', $lpIsShown);
$template->assign('filtered_category', $filteredCategoryId);
$template->assign('allow_min_time', $allowMinTime);
$template->assign('allow_dates_for_student', $allowDatesForStudent);

$templateName = $template->get_template('learnpath/list.tpl');
$content = $template->fetch($templateName);
$template->assign('content', $content);
$template->display_one_col_template();

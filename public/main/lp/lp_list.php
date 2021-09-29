<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
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
if (empty($lp_controller_touched) || 1 != $lp_controller_touched) {
    header('Location: lp_controller.php?action=list&'.api_get_cidreq());
    exit;
}

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script();

// Extra javascript functions for in html head:
$htmlHeadXtra[] = "<script>
function confirmation(name) {
    if (confirm(\" ".trim(get_lang('Are you sure to delete'))." \"+name+\"?\")) {
        return true;
    } else {
        return false;
    }
}
</script>";
$nameTools = get_lang('Learning paths');
Event::event_access_tool(TOOL_LEARNPATH);

/* Require the search widget and prepare the header with its stuff. */
if ('true' === api_get_setting('search_enabled')) {
    require api_get_path(LIBRARY_PATH).'search/search_widget.php';
    search_widget_prepare($htmlHeadXtra);
}
$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
$courseInfo = api_get_course_info();
$course = api_get_course_entity($courseId);
$session = api_get_session_entity($sessionId);

$subscriptionSettings = learnpath::getSubscriptionSettings();
$introduction = '';
/* Introduction section (editable by course admins) */
/*$introduction = Display::return_introduction_section(
    TOOL_LEARNPATH,
    [
        'CreateDocumentWebDir' => api_get_path(WEB_COURSE_PATH)
            .api_get_course_path().'/document/',
        'CreateDocumentDir' => '../..'.api_get_path(REL_COURSE_PATH)
            .api_get_course_path().'/document/',
        'BaseHref' => api_get_path(WEB_COURSE_PATH)
            .api_get_course_path().'/',
    ]
);*/

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
    $actionLeft = Display::url(
        '<i class="mdi-map-marker-path mdi v-icon ch-tool-icon" style="font-size: 32px; width: 32px; height: 32px;" aria-hidden="true" medium="" title="'.htmlentities(get_lang('Create new learning path')).'"></i>',
        api_get_self().'?'.api_get_cidreq().'&action=add_lp'
    );
    $actionLeft .= Display::url(
        '<i class="mdi-archive-arrow-up mdi v-icon ch-tool-icon" style="font-size: 32px; width: 32px; height: 32px;" aria-hidden="true" medium="" title="'.htmlentities(get_lang('Import AICC, SCORM and Chamilo learning path')).'"></i>',
        '../upload/index.php?'.api_get_cidreq().'&curdirpath=/&tool='.TOOL_LEARNPATH
    );

    if ('true' === api_get_setting('service_ppt2lp', 'active')) {
        $actionLeft .= Display::url(
            '<i class="mdi-file-powerpoint mdi v-icon ch-tool-icon" style="font-size: 32px; width: 32px; height: 32px;" aria-hidden="true" medium="" title="'.htmlentities(get_lang('Chamilo RAPID')).'"></i>',
            '../upload/upload_ppt.php?'.api_get_cidreq().'&curdirpath=/&tool='.TOOL_LEARNPATH
        );
    }

    if ($allowCategory) {
        $actionLeft .= Display::url(
            '<i class="mdi-folder-plus mdi v-icon ch-tool-icon" style="font-size: 32px; width: 32px; height: 32px;" aria-hidden="true" medium="" title="'.htmlentities(get_lang('Add category')).'"></i>',
            api_get_self().'?'.api_get_cidreq().'&action=add_lp_category'
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
        $categorySessionId = (int) learnpath::getCategorySessionId($category->getIid());
        if ($categorySessionId === $sessionId || 0 === $categorySessionId) {
            $newCategoryFiltered[] = $category;
        }
        if (!empty($sessionId) && empty($firstSessionCategoryId) && $categorySessionId == $sessionId) {
            $firstSessionCategoryId = $category->getIid();
        }
    }

    $categoriesTempList = $newCategoryFiltered;
}

$categoryTest = new CLpCategory();
$categoryTest->setName(get_lang('Without category'));
$categoryTest->setPosition(0);
$categories = [$categoryTest];

if (!empty($categoriesTempList)) {
    $categories = array_merge($categories, $categoriesTempList);
}
$userId = api_get_user_id();
$userInfo = api_get_user_info();
$lpIsShown = false;
$filteredCategoryId = ('view_category' === $action && !empty($_GET['id'])) ? intval($_GET['id']) : 0;

if ($filteredCategoryId) {
    /** @var CLpCategory $category */
    foreach ($categories as $category) {
        if ($category->getIid() != $filteredCategoryId) {
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

$defaultLpIcon = '<i class="mdi-map-marker-path mdi v-icon ch-tool-icon" style="font-size: 22px; width: 22px; height: 22px;" aria-hidden="true" medium="" title="'.htmlentities(get_lang('Learning path name')).'"></i>';

$defaultDisableLpIcon = '<i class="mdi-map-marker-path mdi v-icon ch-tool-icon-disabled" style="font-size: 22px; width: 22px; height: 22px;" aria-hidden="true" medium="" title="'.htmlentities(get_lang('Learning path name')).'"></i>';

$courseSettingsIcon = Display::getMdiIcon('hammer-screwdriver', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;');

$courseSettingsDisableIcon = '<i class="mdi-hammer-screwdriver mdi v-icon ch-tool-icon-disabled" style="font-size: 22px; width: 22px; height: 22px;" aria-hidden="true" medium="" title="'.htmlentities(get_lang('Course settings')).'"></i>';

$enableAutoLaunch = api_get_course_setting('enable_lp_auto_launch');
$gameMode = api_get_setting('gamification_mode');

$data = [];
$shortcutRepository = Container::getShortcutRepository();

/** @var CLpCategory $category */
foreach ($categories as $category) {
    $categoryId = $category->getIid();
    $visibility = true;
    if (null !== $categoryId) {
        $visibility = $category->isVisible($course, $session);
    }
    if (null !== $categoryId && true === $subscriptionSettings['allow_add_users_to_lp_category']) {
        // "Without category" has id = 0
        /*$categoryVisibility = api_get_item_visibility(
            $courseInfo,
            TOOL_LEARNPATH_CATEGORY,
            $categoryId,
            $sessionId
        );*/

        if (!$is_allowed_to_edit) {
            if (!$visibility) {
                continue;
            }
        }

        if ($user && !learnpath::categoryIsVisibleForStudent($category, $user, $course)) {
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

            if (!$is_allowed_to_edit && 0 == $details['lp_visibility']) {
                // This is a student and this path is invisible, skip.
                continue;
            }

            $lpVisibility = learnpath::is_lp_visible_for_student($details['entity'], $userId, $course);

            // Check if the learnpath is visible for student.
            if (!$is_allowed_to_edit) {
                $isBlocked = learnpath::isBlockedByPrerequisite(
                    $userId,
                    $details['prerequisite'],
                    $course,
                    $sessionId
                );
                if (false === $lpVisibility && $isBlocked && false === $showBlockedPrerequisite) {
                    continue;
                }
            }

            $start_time = $end_time = '';
            if ($is_allowed_to_edit) {
                if (!empty($details['publicated_on'])) {
                    $start_time = api_convert_and_format_date($details['publicated_on'], DATE_TIME_FORMAT_SHORT);
                }
                if (!empty($details['expired_on'])) {
                    $end_time = api_convert_and_format_date($details['expired_on'], DATE_TIME_FORMAT_SHORT);
                }
            } else {
                $time_limits = false;
                // This is an old LP (from a migration 1.8.7) so we do nothing
                if (empty($details['created_on']) && empty($details['modified_on'])) {
                    $time_limits = false;
                }

                // Checking if expired_on is ON
                if ('' != $details['expired_on']) {
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
            if (0 == ($counter % 2)) {
                $oddclass = 'row_odd';
            }

            $url_start_lp = 'lp_controller.php?'.$cidReq.'&action=view&lp_id='.$id;
            $name = strip_tags(Security::remove_XSS($details['lp_name']));
            $extra = '';
            $maker = '';

            if ($is_allowed_to_edit) {
                // @todo This line is what makes the teacher switch to
                //   student view automatically. Many teachers are confused
                //   by that, so maybe a solution can be found to avoid it
                $maker = (empty($details['lp_maker']) ? '' : Security::remove_XSS($details['lp_maker']));
                $url_start_lp .= '&isStudentView=true';
                $extra = $lpVisibility ? '' : get_lang('Learners cannot see this learning path');
            }

            $my_title = $name;
            $icon_learnpath = $defaultLpIcon;
            if (0 == $details['lp_visibility']) {
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
            if (!$is_allowed_to_edit && false == $lpVisibility &&
                true == $showBlockedPrerequisite
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
                $dsp_progress = $progress.'%';
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
                        $linkText = get_lang('You didn\'t spend the minimum time required in the learning path.').' - '.
                            $formattedLpTime.' / '.
                            $formattedAccumulateWorkTime;
                        $linkMinTime = '<i class="mdi-alert mdi v-icon ch-tool-icon" style="font-size: 22px; width: 22px; height: 22px;" aria-hidden="true" medium="" title="'.$linkText.'"></i>';
                    } else {
                        $linkText = get_lang('You didn\'t spend the minimum time required in the learning path.').' - '.
                            $formattedLpTime.' / '.
                            $formattedAccumulateWorkTime;
                        $linkMinTime = '<i class="mdi-checkbox-marked mdi v-icon ch-tool-icon" style="font-size: 22px; width: 22px; height: 22px;" aria-hidden="true" medium="" title="'.$linkText.'"></i>';
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
            $allowExportCourseFormat = $allowLpChamiloExport && 'Chamilo' === $details['lp_maker'];

            if ($is_allowed_to_edit) {
                // EDIT LP
                if ($sessionId == $details['lp_session']) {
                    $dsp_edit_lp = Display::url(
                        $courseSettingsIcon,
                        'lp_controller.php?'.$cidReq."&action=edit&lp_id=$id",
                        ['title' => htmlentities(get_lang('Course settings'))]
                    );
                } else {
                    $dsp_edit_lp = $courseSettingsDisableIcon;
                }

                // BUILD
                if ($sessionId == $details['lp_session']) {
                    if (1 == $details['lp_type'] || 2 == $details['lp_type']) {
                        $dsp_build = Display::url(
                            Display::getMdiIcon('pencil', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                            'lp_controller.php?'.$cidReq.'&'
                                .http_build_query(
                                    [
                                        'action' => 'add_item',
                                        'type' => 'step',
                                        'lp_id' => $id,
                                        'isStudentView' => 'false',
                                    ]
                                ),
                            ['title' => htmlentities(get_lang('Edit learnpath'))]
                        );
                    } else {
                        $dsp_build = Display::getMdiIcon('pencil-off', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;');
                    }
                } else {
                    $dsp_build = Display::getMdiIcon('pencil-off', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;');
                }

                /* VISIBILITY COMMAND */
                /*  Session test not necessary if we want to show base course learning
                    paths inside the session.
                    See http://support.chamilo.org/projects/chamilo-18/wiki/Tools_and_sessions).
                */
                if (!isset($details['subscribe_users']) ||
                    1 != $details['subscribe_users']
                ) {
                    if (0 == $details['lp_visibility']) {
                        $dsp_visible = Display::url(
                            Display::getMdiIcon('eye-off', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                            api_get_self().'?'.$cidReq."&lp_id=$id&action=toggle_visible&new_status=1",
                            ['title' => htmlentities(get_lang('Show'))]
                        );
                    } else {
                        $dsp_visible = Display::url(
                            Display::getMdiIcon('eye', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                            api_get_self().'?'.$cidReq."&lp_id=$id&action=toggle_visible&new_status=0",
                            ['title' => htmlentities(get_lang('Hide'))]
                        );
                    }
                }

                // Tracking command
                $trackingActionUrl = 'lp_controller.php?'.$cidReq.'&'.http_build_query(
                    ['action' => 'report', 'lp_id' => $id]
                );

                $trackingAction = Display::url(
                    Display::getMdiIcon('chart-box', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                    $trackingActionUrl,
                    ['title' => get_lang('Results and feedback')]
                );

                /* PUBLISH COMMAND */
                if ($sessionId == $details['lp_session']) {
                    if ('i' == $details['lp_published']) {
                        $dsp_publish = Display::url(
                            Display::getMdiIcon('checkbox-multiple-blank-outline', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                            api_get_self().'?'.$cidReq."&lp_id=$id&action=toggle_publish&new_status=v",
                            ['title' => htmlentities(get_lang('Publish on course homepage'))]
                        );
                    } else {
                        $dsp_publish = Display::url(
                            Display::getMdiIcon('checkbox-multiple-blank', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                            api_get_self().'?'.$cidReq."&lp_id=$id&action=toggle_publish&new_status=i",
                            ['title' => htmlentities(get_lang('do not publish'))]
                        );
                    }
                } else {
                    $dsp_publish = '<i class="mdi-checkbox-multiple-blank-outline mdi v-icon ch-tool-icon" style="font-size: 22px; width: 22px; height: 22px;" aria-hidden="true" medium="" title="'.htmlentities(get_lang('do not publish')).'"></i>';
                }

                /*  MULTIPLE ATTEMPTS OR SERIOUS GAME MODE
                  SERIOUSGAME MODE is a special mode where :
                 * If a user exits the learning path before finishing it, he comes back where he left next time he tries
                 * When lp status is completed, user can still modify the attempt (adds/time change score, and browse it)
                 * It is thus a mix betwenn multiple attempt and mono attempt
                 */
                if ($sessionId == $details['lp_session']) {
                    if (1 == $details['seriousgame_mode'] && 1 == $details['lp_prevent_reinit']) {
                        // seriousgame mode | next = single
                        $dsp_reinit = Display::url(
                            Display::getMdiIcon('mdi-sync-circle', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                            'lp_controller.php?'.$cidReq."&action=switch_attempt_mode&lp_id=$id",
                            ['title' => htmlentities(get_lang('Prevent multiple attempts'))]
                        );
                    }
                    if (0 == $details['seriousgame_mode'] &&
                        1 == $details['lp_prevent_reinit']
                    ) {
                        // single mode | next = multiple
                        $dsp_reinit = Display::url(
                            Display::getMdiIcon('mdi-sync', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                            'lp_controller.php?'.$cidReq."&action=switch_attempt_mode&lp_id=$id",
                            ['title' => htmlentities(get_lang('Allow multiple attempts'))]
                        );
                    }
                    if (0 == $details['seriousgame_mode'] &&
                        0 == $details['lp_prevent_reinit']
                    ) {
                        // multiple mode | next = seriousgame
                        $dsp_reinit = Display::url(
                            Display::getMdiIcon('sync-circle', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                            'lp_controller.php?'.$cidReq."&action=switch_attempt_mode&lp_id=$id",
                            ['title' => htmlentities(get_lang('Allow multiple attempts'))]
                        );
                    }
                } else {
                    $dsp_reinit = '<i class="mdi-sync mdi v-icon ch-tool-icon" style="font-size: 22px; width: 22px; height: 22px;" aria-hidden="true" medium="" title="'.htmlentities(get_lang('Allow multiple attempts')).'"></i>';
                }

                /* SCREEN LP VIEW */
                if ($sessionId == $details['lp_session']) {
                    switch ($details['lp_view_mode']) {
                        case 'fullscreen':
                            $dsp_default_view = Display::url(
                                Display::getMdiIcon('fullscreen', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                                'lp_controller.php?'.$cidReq.'&action=switch_view_mode&lp_id='.$id.$token_parameter,
                                ['title' => htmlentities(get_lang('Current view mode: fullscreen'))]
                            );
                            break;
                        case 'embedded':
                            $dsp_default_view = Display::url(
                                Display::getMdiIcon('fullscreen-exit', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                                'lp_controller.php?'.$cidReq.'&action=switch_view_mode&lp_id='.$id.$token_parameter,
                                ['title' => htmlentities(get_lang('Current view mode: embedded'))]
                            );
                            break;
                        case 'embedframe':
                            $dsp_default_view = Display::url(
                                Display::getMdiIcon('overscan', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                                'lp_controller.php?'.$cidReq.'&action=switch_view_mode&lp_id='.$id.$token_parameter,
                                ['title' => htmlentities(get_lang('Current view mode: external embed. Use only for embedding in external sites.'))]
                            );
                            break;
                        case 'impress':
                            $dsp_default_view = Display::url(
                                Display::getMdiIcon('play-box-outline', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                                'lp_controller.php?'.$cidReq.'&action=switch_view_mode&lp_id='.$id.$token_parameter,
                                ['title' => htmlentities(get_lang('Current view mode: Impress'))]
                            );
                            break;
                    }
                } else {
                    if ('fullscreen' === $details['lp_view_mode']) {
                        $dsp_default_view = '<i class="mdi-fullscreen mdi v-icon ch-tool-icon-disabled" style="font-size: 22px; width: 22px; height: 22px;" aria-hidden="true" medium="" title="'.htmlentities(get_lang('Current view mode: fullscreen')).'"></i>';
                    } else {
                        $dsp_default_view = '<i class="mdi-fit-to-screen mdi v-icon ch-tool-icon-disabled" style="font-size: 22px; width: 22px; height: 22px;" aria-hidden="true" medium="" title="'.htmlentities(get_lang('Current view mode: embedded')).'"></i>';
                    }
                }

                /*  DEBUG  */
                if ('test' === $test_mode || api_is_platform_admin()) {
                    if (1 == $details['lp_scorm_debug']) {
                        $dsp_debug = Display::url(
                            Display::getMdiIcon('bug-check', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                            "lp_controller.php?$cidReq&action=switch_scorm_debug&lp_id=$id",
                            ['title' => htmlentities(get_lang('Hide debug'))]
                        );
                    } else {
                        $dsp_debug = Display::url(
                            Display::getMdiIcon('bug-outline', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                            'lp_controller.php?'.$cidReq."&action=switch_scorm_debug&lp_id=$id",
                            ['title' => htmlentities(get_lang('Show debug'))]
                        );
                    }
                }

                /* Export */
                if (1 == $details['lp_type']) {
                    $dsp_disk = Display::url(
                        Display::getMdiIcon('package', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                        api_get_self()."?$cidReq&action=export&lp_id=$id",
                        ['title' => htmlentities(get_lang('Export as SCORM'))]
                    );
                } elseif (2 == $details['lp_type']) {
                    $dsp_disk = Display::url(
                        Display::getMdiIcon('package', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                        api_get_self()."?$cidReq&action=export&lp_id=$id&export_name="
                            .api_replace_dangerous_char($name).'.zip',
                        ['title' => htmlentities(get_lang('Export as SCORM'))]
                    );
                } else {
                    $dsp_disk = '<i class="mdi-package mdi v-icon ch-tool-icon-disabled" style="font-size: 22px; width: 22px; height: 22px;" aria-hidden="true" medium="" title="'.htmlentities(get_lang('Export as SCORM')).'"></i>';
                }

                // Copy
                $copy = Display::url(
                    Display::getMdiIcon('text-box-plus', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                    api_get_self().'?'.$cidReq."&action=copy&lp_id=$id",
                    ['title' => htmlentities(get_lang('Copy'))]
                );

                // Subscribe users
                $subscribeUsers = '';
                if (1 == $details['subscribe_users'] &&
                    $subscriptionSettings['allow_add_users_to_lp']
                ) {
                    $subscribeUsers = Display::url(
                        Display::getMdiIcon('account-multiple-plus', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                        api_get_path(WEB_CODE_PATH)."lp/lp_subscribe_users.php?lp_id=$id&".$cidReq,
                        ['title' => htmlentities(get_lang('Subscribe users to learning path'))]
                    );
                }

                /* Auto launch LP code */
                if (1 == $enableAutoLaunch) {
                    if (1 == $details['autolaunch'] &&
                        false == $autolaunch_exists
                    ) {
                        $autolaunch_exists = true;
                        $lp_auto_launch_icon = Display::url(
                            Display::getMdiIcon('rocket-launch', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                            api_get_self().'?'.$cidReq."&action=auto_launch&status=0&lp_id=$id",
                            ['title' => htmlentities(get_lang('Disable learning path auto-launch'))]
                        );
                    } else {
                        $lp_auto_launch_icon = Display::url(
                            Display::getMdiIcon('rocket-launch', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                            api_get_self().'?'.$cidReq."&action=auto_launch&status=1&lp_id=$id",
                            ['title' => htmlentities(get_lang('Enable learning path auto-launch'))]
                        );
                    }
                }

                // Export to PDF
                $export_icon = Display::url(
                    Display::getMdiIcon('pdf', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                    api_get_self().'?'.$cidReq."&action=export_to_pdf&lp_id=$id",
                    ['title' => htmlentities(get_lang('Export to PDF web pages and images'))]
                );

                /* Delete */
                if ($sessionId == $details['lp_session']) {
                    $dsp_delete = Display::url(
                        Display::getMdiIcon('delete', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                        'lp_controller.php?'.$cidReq."&action=delete&lp_id=$id",
                        [
                            'title' => htmlentities(get_lang('Delete')),
                            'onclick' => "javascript: return confirmation('".addslashes($name)."');",
                        ]
                    );
                } else {
                    $dsp_delete = '<i class="mdi-delete mdi v-icon ch-tool-icon-disabled" style="font-size: 22px; width: 22px; height: 22px;" aria-hidden="true" medium="" title="'.htmlentities(get_lang('Delete')).'"></i>';
                }

                /* COLUMN ORDER	 */
                // Only active while session mode is not active
                if (0 == $sessionId) {
                    if (1 == $details['lp_display_order'] && 1 != $max) {
                        $dsp_order .= Display::url(
                            Display::getMdiIcon('arrow-down-bold', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                            "lp_controller.php?$cidReq&action=move_lp_down&lp_id=$id&category_id=$categoryId",
                            ['title' => htmlentities(get_lang('Move down'))]
                        );
                    } elseif ($current == $max - 1 && 1 != $max) {
                        $dsp_order .= Display::url(
                            Display::getMdiIcon('arrow-up-bold', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                            "lp_controller.php?$cidReq&action=move_lp_up&lp_id=$id&category_id=$categoryId",
                            ['title' => htmlentities(get_lang('Move up'))]
                        );
                    } elseif (1 == $max) {
                        $dsp_order = '';
                    } else {
                        $dsp_order .= Display::url(
                            Display::getMdiIcon('arrow-down-bold', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                            "lp_controller.php?$cidReq&action=move_lp_down&lp_id=$id&category_id=$categoryId",
                            ['title' => htmlentities(get_lang('Move down'))]
                        );
                        $dsp_order .= Display::url(
                            Display::getMdiIcon('arrow-up-bold', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                            "lp_controller.php?$cidReq&action=move_lp_up&lp_id=$id&category_id=$categoryId",
                            ['title' => htmlentities(get_lang('Move up'))]
                        );
                    }
                }

                if (2 == $details['lp_type']) {
                    $url = api_get_path(WEB_CODE_PATH).'lp/lp_update_scorm.php?'.$cidReq."&lp_id=$id";
                    $actionUpdateScormFile = Display::url(
                        Display::getMdiIcon('upload', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                        $url,
                        ['title' => htmlentities(get_lang('Update'))]
                    );
                }

                if ($allowExportCourseFormat) {
                    $actionExportToCourseBuild = Display::url(
                        Display::getMdiIcon('download', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                        api_get_self().'?'.$cidReq."&action=export_to_course_build&lp_id=$id",
                        ['title' => htmlentities(get_lang('Export to Chamilo format'))]
                    );
                }

                if (1 == $gameMode) {
                    if (0 == $details['seriousgame_mode']) {
                        $actionSeriousGame = Display::toolbarButton(
                            '',
                            api_get_self().'?'.$cidReq
                                ."&lp_id=$id&action=toggle_seriousgame",
                            'trophy',
                            'default',
                            [
                                'class' => 'btn-xs',
                                'title' => htmlentities(get_lang('Enable gamification mode')),
                            ]
                        );
                    } else {
                        $actionSeriousGame = Display::toolbarButton(
                            '',
                            api_get_self().'?'.$cidReq
                                ."&lp_id=$id&action=toggle_seriousgame",
                            'trophy',
                            'warning',
                            [
                                'class' => 'btn-xs active',
                                'title' => htmlentities(get_lang('Disable gamification mode')),
                            ]
                        );
                    }
                }
            } else {
                // Student
                $export_icon = Display::url(
                    Display::getMdiIcon('file-pdf-box', 'ch-tool-icon', 'font-size: 22px; width: 22px; height: 22px;'),
                    api_get_self().'?'.$cidReq."&action=export_to_pdf&lp_id=$id",
                    ['title' => htmlentities(get_lang('Export to PDF'))]
                );
            }

            if ('true' === $hideScormExportLink) {
                $dsp_disk = null;
            }

            if ('true' === $hideScormCopyLink) {
                $copy = null;
            }

            if ('true' === $hideScormPdfLink) {
                $export_icon = null;
            }

            $sessionImage = api_get_session_image(
                $details['lp_session'],
                $user
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
                'visible' => $details['lp_visibility'],
                'maker' => $maker,
            ];

            $lpIsShown = true;
            // Counter for number of elements treated
            $current++;
        } // end foreach ($flat_list)
    }

    $shortcut = false;
    if ($category->hasResourceNode()) {
        $shortcut = $shortcutRepository->getShortcutFromResource($category);
    }

    $data[] = [
        'category' => $category,
        'category_visibility' => $visibility,
        'category_is_published' => $shortcut ? 1 : 0,
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
//DocumentManager::removeGeneratedAudioTempFile();

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
$template->assign('session_star_icon', '<i class="mdi-star mdi v-icon ch-tool-icon" style="font-size: 22px; width: 22px; height: 22px;" aria-hidden="true" medium="" title="'.htmlentities(get_lang('Session')).'"></i>');
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

$template->assign('no_data', '');
if (false === $lpIsShown && api_is_allowed_to_edit()) {
    $noData = Display::noDataView(
        get_lang('Learning paths'),
        '<i class="mdi-map-marker-path mdi v-icon ch-tool-icon-gradient" style="font-size: 128px; width: 128px; height: 128px;" aria-hidden="true" medium="" title="'.htmlentities(get_lang('Create new learning path')).'"></i>',
        get_lang('Create new learning path'),
        api_get_self().'?'.api_get_cidreq().'&action=add_lp'
    );
    $template->assign('no_data', $noData);
}

$template->displayTemplate('@ChamiloCore/LearnPath/list.html.twig');

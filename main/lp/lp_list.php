<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;
use Chamilo\CourseBundle\Entity\CLpCategory;

/**
 * This file was originally the copy of document.php, but many modifications happened since then ;
 * the direct file view is not any more needed, if the user uploads a SCORM zip file, a directory
 * will be automatically created for it, and the files will be uncompressed there for example ;
 *
 * @package chamilo.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

$this_section = SECTION_COURSES;
//@todo who turns on $lp_controller_touched?
if (empty($lp_controller_touched) || $lp_controller_touched != 1) {
    header('location: lp_controller.php?action=list');
    exit;
}

require_once __DIR__.'/../inc/global.inc.php';
$courseDir = api_get_course_path().'/scorm';
$baseWordDir = $courseDir;

/**
 * Display initialisation and security checks
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
api_protect_course_script();

/**
 * Display
 */
/* Require the search widget and prepare the header with its stuff. */
if (api_get_setting('search_enabled') === 'true') {
    require api_get_path(LIBRARY_PATH).'search/search_widget.php';
    search_widget_prepare($htmlHeadXtra);
}
$current_session = api_get_session_id();

/* Introduction section (editable by course admins) */

$introductionSection = Display::return_introduction_section(
    TOOL_LEARNPATH,
    array(
        'CreateDocumentWebDir' => api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/',
        'CreateDocumentDir' => '../..'.api_get_path(REL_COURSE_PATH).api_get_course_path().'/document/',
        'BaseHref' => api_get_path(WEB_COURSE_PATH).api_get_course_path().'/',
    )
);

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
$courseInfo = api_get_course_info();
$message = '';
$actions = '';

if ($is_allowed_to_edit) {
    if (!empty($dialog_box)) {
        switch ($_GET['dialogtype']) {
            case 'confirmation':
                $message = Display::return_message($dialog_box, 'success');
                break;
            case 'error':
                $message = Display::return_message($dialog_box, 'danger');
                break;
            case 'warning':
                $message = Display::return_message($dialog_box, 'warning');
                break;
            default:
                $message = Display::return_message($dialog_box);
                break;
        }
    }
    $actionLeft = null;
    $actionLeft .= Display::url(
        Display::return_icon('new_folder.png', get_lang('AddCategory'), array(), ICON_SIZE_MEDIUM),
        api_get_self().'?'.api_get_cidreq().'&action=add_lp_category'
    );
    $actionLeft .= Display::url(
        Display::return_icon('new_learnpath.png', get_lang('LearnpathAddLearnpath'), '', ICON_SIZE_MEDIUM),
        api_get_self().'?'.api_get_cidreq().'&action=add_lp'
    );
    $actionLeft .= Display::url(
        Display::return_icon('import_scorm.png', get_lang('UploadScorm'), '', ICON_SIZE_MEDIUM),
        '../upload/index.php?'.api_get_cidreq().'&curdirpath=/&tool='.TOOL_LEARNPATH
    );

    if (api_get_setting('service_ppt2lp', 'active') === 'true') {
        $actionLeft .= Display::url(
            Display::return_icon('import_powerpoint.png', get_lang('PowerPointConvert'), '', ICON_SIZE_MEDIUM),
            '../upload/upload_ppt.php?'.api_get_cidreq().'&curdirpath=/&tool='.TOOL_LEARNPATH
        );
    }
    $actions = Display::toolbarAction('actions-lp', array($actionLeft));
}

$token = Security::get_token();

/* DISPLAY SCORM LIST */
$categoriesTempList = learnpath::getCategories(api_get_course_int_id());
$categoryTest = new CLpCategory();
$categoryTest->setId(0);
$categoryTest->setName(get_lang('WithOutCategory'));
$categoryTest->setPosition(0);

$categories = array(
    $categoryTest
);

if (!empty($categoriesTempList)) {
    $categories = array_merge($categories, $categoriesTempList);
}

$userId = api_get_user_id();
$userInfo = api_get_user_info();

$lpIsShown = false;

$test_mode = api_get_setting('server_type');
$user = UserManager::getRepository()->find($userId);

$data = [];
/** @var CLpCategory $item */
foreach ($categories as $item) {
    $categoryId = $item->getId();

    if (!$is_allowed_to_edit) {
        $users = $item->getUsers();
        if (!empty($users) && $users->count() > 0) {
            if (!$item->hasUserAdded($user)) {
                continue;
            }
        }
    }

    $list = new LearnpathList(
        api_get_user_id(),
        null,
        null,
        null,
        false,
        $categoryId
    );

    $flat_list = $list->get_flat_list();

    // Hiding categories with out LPs (only for student)
    if (empty($flat_list) && !api_is_allowed_to_edit()) {
        continue;
    }

    $showBlockedPrerequisite = api_get_configuration_value('show_prerequisite_as_blocked');
    $listData = [];

    if (!empty($flat_list)) {
        $max = count($flat_list);
        $counter = 0;
        $current = 0;
        $autolaunch_exists = false;

        foreach ($flat_list as $id => $details) {
            $id = $details['lp_old_id'];
            // Validation when belongs to a session.
            $session_img = api_get_session_image(
                $details['lp_session'],
                $userInfo['status']
            );

            if (!$is_allowed_to_edit && $details['lp_visibility'] == 0) {
                // This is a student and this path is invisible, skip.
                continue;
            }

            $lpVisibility = learnpath::is_lp_visible_for_student($id, $userId);
            $isBlocked = learnpath::isBlockedByPrerequisite(
                $userId,
                $details['prerequisite'],
                $courseInfo,
                api_get_session_id()
            );

            // Check if the learnpath is visible for student.
            if (!$is_allowed_to_edit && $lpVisibility === false &&
                ($isBlocked && $showBlockedPrerequisite === false)
            ) {
                continue;
            }

            $start_time = $end_time = '';
            if (!$is_allowed_to_edit) {
                $time_limits = false;

                // This is an old LP (from a migration 1.8.7) so we do nothing
                if ((empty($details['created_on'])) &&
                    (empty($details['modified_on']))
                ) {
                    $time_limits = false;
                }

                // Checking if expired_on is ON
                if ($details['expired_on'] != '') {
                    $time_limits = true;
                }

                if ($time_limits) {
                    // Check if start time
                    if (!empty($details['publicated_on']) && !empty($details['expired_on'])) {
                        $start_time = api_strtotime(
                            $details['publicated_on'],
                            'UTC'
                        );
                        $end_time = api_strtotime(
                            $details['expired_on'],
                            'UTC'
                        );
                        $now = time();
                        $is_actived_time = false;

                        if ($now > $start_time && $end_time > $now) {
                            $is_actived_time = true;
                        }

                        if (!$is_actived_time) {
                            continue;
                        }
                    }
                }
                $start_time = $end_time = '';
            } else {
                if (!empty($details['publicated_on'])) {
                    $start_time = api_convert_and_format_date(
                        $details['publicated_on'],
                        DATE_TIME_FORMAT_LONG_24H
                    );
                }
                if (!empty($details['expired_on'])) {
                    $end_time = api_convert_and_format_date(
                        $details['expired_on'],
                        DATE_TIME_FORMAT_LONG_24H
                    );
                }
            }

            $counter++;
            if (($counter % 2) == 0) {
                $oddclass = 'row_odd';
            } else {
                $oddclass = 'row_even';
            }

            $url_start_lp = 'lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$id;
            $name = Security::remove_XSS($details['lp_name']);
            $extra = null;

            if ($is_allowed_to_edit) {
                $url_start_lp .= '&isStudentView=true';
                $dsp_desc = '<em>'.$details['lp_maker'].'</em>   '.($lpVisibility ? '' : ' - ('.get_lang('LPNotVisibleToStudent').')');
                $extra = '<div class ="lp_content_type_label">'.$dsp_desc.'</div>';
            }

            $my_title = $name;
            $icon_learnpath = Display::return_icon(
                'learnpath.png',
                get_lang('LPName'),
                '',
                ICON_SIZE_SMALL
            );

            if ($details['lp_visibility'] == 0) {
                $my_title = Display::tag(
                    'font',
                    $name,
                    array('class' => 'text-muted')
                );
                $icon_learnpath = Display::return_icon(
                    'learnpath_na.png',
                    get_lang('LPName'),
                    '',
                    ICON_SIZE_SMALL
                );
            }

            // Students can see the lp but is inactive
            if (!$is_allowed_to_edit &&
                $lpVisibility == false &&
                $showBlockedPrerequisite == true
            ) {
                $my_title = Display::tag(
                    'font',
                    $name,
                    array('class' => 'text-muted')
                );
                $icon_learnpath = Display::return_icon(
                    'learnpath_na.png',
                    get_lang('LPName'),
                    '',
                    ICON_SIZE_SMALL
                );
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

            if (!api_is_invitee()) {
                $progress = learnpath::getProgress(
                    $id,
                    $userId,
                    api_get_course_int_id(),
                    api_get_session_id()
                );
            }

            if ($is_allowed_to_edit) {
                $dsp_progress = '<center>'.$progress.'</center>';
            } else {
                $dsp_progress = '';
                if (!api_is_invitee()) {
                    $dsp_progress = learnpath::get_progress_bar($progress, '%');
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

            if ($is_allowed_to_edit) {
                // EDIT LP
                if ($current_session == $details['lp_session']) {
                    $dsp_edit_lp = Display::url(
                        Display::return_icon('settings.png', get_lang('CourseSettings'), '', ICON_SIZE_SMALL),
                        "lp_controller.php?".api_get_cidreq()."&action=edit&lp_id=$id"
                    );
                } else {
                    $dsp_edit_lp = Display::return_icon(
                        'settings_na.png',
                        get_lang('CourseSettings'),
                        '',
                        ICON_SIZE_SMALL
                    );
                }

                // BUILD
                if ($current_session == $details['lp_session']) {
                    if ($details['lp_type'] == 1 || $details['lp_type'] == 2) {
                        $dsp_build = Display::url(
                            Display::return_icon('edit.png', get_lang('LearnpathEditLearnpath'), '', ICON_SIZE_SMALL),
                            'lp_controller.php?'.api_get_cidreq().'&'.http_build_query([
                                'action' => 'add_item',
                                'type' => 'step',
                                'lp_id' => $id,
                                'isStudentView' => 'false',
                            ])
                        );
                    } else {
                        $dsp_build = Display::return_icon(
                            'edit_na.png',
                            get_lang('LearnpathEditLearnpath'),
                            '',
                            ICON_SIZE_SMALL
                        );
                    }
                } else {
                    $dsp_build = Display::return_icon(
                        'edit_na.png',
                        get_lang('LearnpathEditLearnpath'),
                        '',
                        ICON_SIZE_SMALL
                    );
                }

                /* VISIBILITY COMMAND */

                /*  Session test not necessary if we want to show base course learning
                    paths inside the session.
                    See http://support.chamilo.org/projects/chamilo-18/wiki/Tools_and_sessions).
                */
                if (!isset($details['subscribe_users']) || $details['subscribe_users'] != 1) {
                    if ($details['lp_visibility'] == 0) {
                        $dsp_visible = Display::url(
                            Display::return_icon('invisible.png', get_lang('Show'), '', ICON_SIZE_SMALL),
                            api_get_self().'?'.api_get_cidreq()."&lp_id=$id&action=toggle_visible&new_status=1"
                        );
                    } else {
                        $dsp_visible = Display::url(
                            Display::return_icon('visible.png', get_lang('Hide'), '', ICON_SIZE_SMALL),
                            api_get_self().'?'.api_get_cidreq()."&lp_id=$id&action=toggle_visible&new_status=0"
                        );
                    }
                }

                //Tracking command
                $trackingActionUrl = 'lp_controller.php?'
                    . api_get_cidreq().'&'
                    . http_build_query([
                        'action' => 'report',
                        'lp_id' => $id,
                    ]);

                $trackingAction = Display::url(
                    Display::return_icon('test_results.png', get_lang('Results'), array(), ICON_SIZE_SMALL),
                    $trackingActionUrl
                );

                /* PUBLISH COMMAND */
                if ($current_session == $details['lp_session']) {
                    if ($details['lp_published'] == "i") {
                        $dsp_publish = Display::url(
                            Display::return_icon(
                                'lp_publish_na.png',
                                get_lang('LearnpathPublish'),
                                '',
                                ICON_SIZE_SMALL
                            ),
                            api_get_self().'?'.api_get_cidreq()."&lp_id=$id&action=toggle_publish&new_status=v"
                        );
                    } else {
                        $dsp_publish = "<a href='".api_get_self()."?".api_get_cidreq()."&lp_id=$id&action=toggle_publish&new_status=i'>".
                            Display::return_icon(
                                'lp_publish.png',
                                get_lang('LearnpathDoNotPublish'),
                                '',
                                ICON_SIZE_SMALL
                            )."</a>";
                        $dsp_publish = Display::url(
                            Display::return_icon(
                                'lp_publish.png',
                                get_lang('LearnpathDoNotPublish'),
                                '',
                                ICON_SIZE_SMALL
                            ),
                            api_get_self().'?'.api_get_cidreq()."&lp_id=$id&action=toggle_publish&new_status=i"
                        );
                    }
                } else {
                    $dsp_publish = Display::return_icon(
                        'lp_publish_na.png',
                        get_lang('LearnpathDoNotPublish'),
                        '',
                        ICON_SIZE_SMALL
                    );
                }

                /*  MULTIPLE ATTEMPTS OR SERIOUS GAME MODE

                  SERIOUSGAME MODE is a special mode where :
                 * If a user exits the learning path before finishing it, he comes back where he left next time he tries
                 * When lp status is completed, user can still modify the attempt (adds/time change score, and browse it)
                 * It is thus a mix betwenn multiple attempt and mono attempt
                 */
                if ($current_session == $details['lp_session']) {
                    if ($details['seriousgame_mode'] == 1 && $details['lp_prevent_reinit'] == 1) { //seriousgame mode | next = single
                        $dsp_reinit = Display::url(
                            Display::return_icon(
                                'reload.png',
                                get_lang('PreventMultipleAttempts'),
                                '',
                                ICON_SIZE_SMALL
                            ),
                            "lp_controller.php?".api_get_cidreq()."&action=switch_attempt_mode&lp_id=$id"
                        );
                    }
                    if ($details['seriousgame_mode'] == 0 && $details['lp_prevent_reinit'] == 1) { //single mode | next = multiple
                        $dsp_reinit = Display::url(
                            Display::return_icon(
                                'reload_na.png',
                                get_lang('AllowMultipleAttempts'),
                                '',
                                ICON_SIZE_SMALL
                            ),
                            "lp_controller.php?".api_get_cidreq()."&action=switch_attempt_mode&lp_id=$id"
                        );
                    }
                    if ($details['seriousgame_mode'] == 0 && $details['lp_prevent_reinit'] == 0) { //multiple mode | next = seriousgame
                        $dsp_reinit = Display::url(
                            Display::return_icon(
                                'reload.png',
                                get_lang('AllowMultipleAttempts'),
                                '',
                                ICON_SIZE_SMALL
                            ),
                            "lp_controller.php?".api_get_cidreq()."&action=switch_attempt_mode&lp_id=$id"
                        );
                    }
                } else {
                    $dsp_reinit = Display::return_icon(
                        'reload_na.png',
                        get_lang('AllowMultipleAttempts'),
                        '',
                        ICON_SIZE_SMALL
                    );
                }

                /* SCREEN LP VIEW */
                if ($current_session == $details['lp_session']) {
                    switch ($details['lp_view_mode']) {
                        case 'fullscreen':
                            $dsp_default_view = Display::url(
                                Display::return_icon(
                                    'view_fullscreen.png',
                                    get_lang('ViewModeFullScreen'),
                                    '',
                                    ICON_SIZE_SMALL
                                ),
                                'lp_controller.php?'.api_get_cidreq()
                                . '&action=switch_view_mode&lp_id='.$id.$token_parameter
                            );
                            break;
                        case 'embedded':
                            $dsp_default_view = Display::url(
                                Display::return_icon(
                                    'view_left_right.png',
                                    get_lang('ViewModeEmbedded'),
                                    '',
                                    ICON_SIZE_SMALL
                                ),
                                'lp_controller.php?'.api_get_cidreq()
                                . '&action=switch_view_mode&lp_id='.$id.$token_parameter
                            );
                            break;
                        case 'embedframe':
                            $dsp_default_view = Display::url(
                                Display::return_icon(
                                    'view_nofullscreen.png',
                                    get_lang('ViewModeEmbedFrame'),
                                    '',
                                    ICON_SIZE_SMALL
                                ),
                                'lp_controller.php?'.api_get_cidreq()
                                . '&action=switch_view_mode&lp_id='.$id.$token_parameter
                            );
                            break;
                        case 'impress':
                            $dsp_default_view = Display::url(
                                Display::return_icon(
                                    'window_list_slide.png',
                                    get_lang('ViewModeImpress'),
                                    '',
                                    ICON_SIZE_SMALL
                                ),
                                'lp_controller.php?'.api_get_cidreq()
                                . '&action=switch_view_mode&lp_id='.$id.$token_parameter
                            );
                            break;
                    }
                } else {
                    if ($details['lp_view_mode'] == 'fullscreen') {
                        $dsp_default_view = Display::return_icon(
                            'view_fullscreen_na.png',
                            get_lang('ViewModeEmbedded'),
                            '',
                            ICON_SIZE_SMALL
                        );
                    } else {
                        $dsp_default_view = Display::return_icon(
                            'view_left_right_na.png',
                            get_lang('ViewModeEmbedded'),
                            '',
                            ICON_SIZE_SMALL
                        );
                    }
                }

                /*  DEBUG  */
                if ($test_mode == 'test' or api_is_platform_admin()) {
                    if ($details['lp_scorm_debug'] == 1) {
                        $dsp_debug = Display::url(
                            Display::return_icon(
                                'bug.png',
                                get_lang('HideDebug'),
                                '',
                                ICON_SIZE_SMALL
                            ),
                            "lp_controller.php?".api_get_cidreq()."&action=switch_scorm_debug&lp_id=$id"
                        );
                    } else {
                        $dsp_debug = Display::url(
                            Display::return_icon(
                                'bug_na.png',
                                get_lang('ShowDebug'),
                                '',
                                ICON_SIZE_SMALL
                            ),
                            "lp_controller.php?".api_get_cidreq()."&action=switch_scorm_debug&lp_id=$id"
                        );
                    }
                }

                /* Export */
                if ($details['lp_type'] == 1) {
                    $dsp_disk = Display::url(
                        Display::return_icon(
                            'cd.png',
                            get_lang('Export'),
                            array(),
                            ICON_SIZE_SMALL
                        ),
                        api_get_self()."?".api_get_cidreq(
                        )."&action=export&lp_id=$id"
                    );
                } elseif ($details['lp_type'] == 2) {
                    $dsp_disk = Display::url(
                        Display::return_icon(
                            'cd.png',
                            get_lang('Export'),
                            array(),
                            ICON_SIZE_SMALL
                        ),
                        api_get_self()."?".api_get_cidreq()."&action=export&lp_id=$id&export_name=".api_replace_dangerous_char($name).".zip"
                    );
                } else {
                    $dsp_disk = Display::return_icon(
                        'cd_na.png',
                        get_lang('Export'),
                        array(),
                        ICON_SIZE_SMALL
                    );
                }

                // Copy
                $copy = Display::url(
                    Display::return_icon(
                        'cd_copy.png',
                        get_lang('Copy'),
                        array(),
                        ICON_SIZE_SMALL
                    ),
                    api_get_self()."?".api_get_cidreq()."&action=copy&lp_id=$id"
                );

                // Subscribe users
                $subscribeUsers = null;

                if ($details['subscribe_users'] == 1) {
                    $subscribeUsers = Display::url(
                        Display::return_icon('user.png', get_lang('SubscribeUsersToLp')),
                        api_get_path(WEB_CODE_PATH)."lp/lp_subscribe_users.php?lp_id=$id&".api_get_cidreq()
                    );
                }

                /* Auto launch LP code */
                if (api_get_course_setting('enable_lp_auto_launch') == 1) {
                    if ($details['autolaunch'] == 1 && $autolaunch_exists == false) {
                        $autolaunch_exists = true;
                        $lp_auto_launch_icon = Display::url(
                            Display::return_icon('launch.png', get_lang('DisableLPAutoLaunch')),
                            api_get_self().'?'.api_get_cidreq()."&action=auto_launch&status=0&lp_id=$id"
                        );
                    } else {
                        $lp_auto_launch_icon = Display::url(
                            Display::return_icon('launch_na.png', get_lang('EnableLPAutoLaunch')),
                            api_get_self().'?'.api_get_cidreq()."&action=auto_launch&status=1&lp_id=$id"
                        );
                    }
                }

                // Export to PDF
                $export_icon = Display::url(
                    Display::return_icon(
                        'pdf.png',
                        get_lang('ExportToPDFOnlyHTMLAndImages'),
                        '',
                        ICON_SIZE_SMALL
                    ),
                    api_get_self().'?'.api_get_cidreq()."&action=export_to_pdf&lp_id=$id"
                );

                /* Delete */
                if ($current_session == $details['lp_session']) {
                    $dsp_delete = Display::url(
                        Display::return_icon(
                            'delete.png',
                            get_lang('LearnpathDeleteLearnpath'),
                            '',
                            ICON_SIZE_SMALL
                        ),
                        'lp_controller.php?'.api_get_cidreq()."&action=delete&lp_id=$id",
                        ['onclick' => "javascript: return confirmation('".addslashes($name)."');"]
                    );
                } else {
                    $dsp_delete = Display::return_icon(
                        'delete_na.png',
                        get_lang('LearnpathDeleteLearnpath'),
                        '',
                        ICON_SIZE_SMALL
                    );
                }

                /* COLUMN ORDER	 */
                // Only active while session mode is not active
                if ($current_session == 0) {
                    if ($details['lp_display_order'] == 1 && $max != 1) {
                        $dsp_order .= Display::url(
                            Display::return_icon('down.png', get_lang('MoveDown'), '', ICON_SIZE_SMALL),
                            "lp_controller.php?".api_get_cidreq()."&action=move_lp_down&lp_id=$id"
                        );
                    } elseif ($current == $max - 1 && $max != 1) {
                        $dsp_order .= Display::url(
                            Display::return_icon('up.png', get_lang('MoveUp'), '', ICON_SIZE_SMALL),
                            "lp_controller.php?".api_get_cidreq()."&action=move_lp_up&lp_id=$id"
                        );
                    } elseif ($max == 1) {
                        $dsp_order = '';
                    } else {
                        $dsp_order .= Display::url(
                            Display::return_icon('down.png', get_lang('MoveDown'), '', ICON_SIZE_SMALL),
                            "lp_controller.php?".api_get_cidreq()."&action=move_lp_down&lp_id=$id"
                        );
                        $dsp_order .= Display::url(
                            Display::return_icon('up.png', get_lang('MoveUp'), '', ICON_SIZE_SMALL),
                            "lp_controller.php?".api_get_cidreq()."&action=move_lp_up&lp_id=$id"
                        );
                    }
                }

                if ($is_allowed_to_edit) {
                    $start_time = $start_time;
                    $end_time = $end_time;
                } else {
                    $start_time = $end_time = '';
                }

                if (api_get_setting('gamification_mode') == 1) {
                    if ($details['seriousgame_mode'] == 0) {
                        $actionSeriousGame = Display::toolbarButton(
                            null,
                            api_get_self().'?'.api_get_cidreq()."&lp_id=$id&action=toggle_seriousgame",
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
                            api_get_self().'?'.api_get_cidreq()."&lp_id=$id&action=toggle_seriousgame",
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
                    Display::return_icon('pdf.png', get_lang('ExportToPDF'), '', ICON_SIZE_SMALL),
                    api_get_self().'?'.api_get_cidreq()."&action=export_to_pdf&lp_id=$id"
                );
            }

            $hideScormExportLink = api_get_setting('hide_scorm_export_link');
            if ($hideScormExportLink === 'true') {
                $dsp_disk = null;
            }

            $hideScormCopyLink = api_get_setting('hide_scorm_copy_link');
            if ($hideScormCopyLink === 'true') {
                $copy = null;
            }

            $hideScormPdfLink = api_get_setting('hide_scorm_pdf_link');
            if ($hideScormPdfLink === 'true') {
                $export_icon = null;
            }

            $listData[] = [
                'learnpath_icon' => $icon_learnpath,
                'url_start' => $url_start_lp,
                'title' => $my_title,
                'session_image' => $session_img,
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
            ];

            $lpIsShown = true;
            // Counter for number of elements treated
            $current++;

        } // end foreach ($flat_list)
    }

    $data[] = [
        'category' => $item,
        'lp_list' => $listData
    ];
}

$template = new Template($nameTools);
$template->assign('is_allowed_to_edit', $is_allowed_to_edit);
$template->assign('is_invitee', api_is_invitee());
$template->assign('actions', $actions);
$template->assign('categories', $categories);
$template->assign('message', $message);
$template->assign('introduction_section', $introductionSection);
$template->assign('data', $data);
$template->assign('lp_is_shown', $lpIsShown);
$templateName = $template->get_template('learnpath/list.tpl');
$content = $template->fetch($templateName);
$template->assign('content', $content);
$template->display_one_col_template();
learnpath::generate_learning_path_folder($courseInfo);

// Deleting the objects
Session::erase('oLP');
Session::erase('lpobject');
DocumentManager::removeGeneratedAudioTempFile();

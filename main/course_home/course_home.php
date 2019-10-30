<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;
use Fhaculty\Graph\Graph;

/**
 * HOME PAGE FOR EACH COURSE.
 *
 * This page, included in every course's index.php is the home
 * page. To make administration simple, the teacher edits his
 * course from the home page. Only the login detects that the
 * visitor is allowed to activate, deactivate home page links,
 * access to the teachers tools (statistics, edit forums...).
 *
 * Edit visibility of tools
 *
 *   visibility = 1 - everybody
 *   visibility = 0 - course admin (teacher) and platform admin
 *
 * Who can change visibility ?
 *
 *   admin = 0 - course admin (teacher) and platform admin
 *   admin = 1 - platform admin
 *
 * Show message to confirm that a tools must be hide from available tools
 *
 *   visibility 0,1
 */
$use_anonymous = true;
require_once __DIR__.'/../inc/global.inc.php';

$js = '<script>'.api_get_language_translate_html().'</script>';
$htmlHeadXtra[] = $js;

$htmlHeadXtra[] = '<script>
/* option show/hide thematic-block */
$(function() {
    $("#thematic-show").click(function(){
        $(".btn-hide-thematic").hide();
        $(".btn-show-thematic").show(); //show using class
        $("#pross").fadeToggle(); //Not working collapse for Chrome
    });
    
    $("#thematic-hide").click(function(){
        $(".btn-show-thematic").hide(); //show using class
        $(".btn-hide-thematic").show();
        $("#pross").fadeToggle(); //Not working collapse for Chrome
    });
    
	$(".make_visible_and_invisible").attr("href", "javascript:void(0);");
	$(".make_visible_and_invisible > img").click(function () {
		make_visible = "visible.png";
		make_invisible = "invisible.png";
		path_name = $(this).attr("src");
		list_path_name = path_name.split("/");
		image_link = list_path_name[list_path_name.length - 1];
		tool_id = $(this).attr("id");
		tool_info = tool_id.split("_");
		my_tool_id = tool_info[1];
        $("#id_normal_message").attr("class", "normal-message alert alert-success");

		$.ajax({
			contentType: "application/x-www-form-urlencoded",
			beforeSend: function(myObject) {
				$(".normal-message").show();
				$("#id_confirmation_message").hide();
			},
			type: "GET",
			url: "'.api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?'.api_get_cidreq().'&a=set_visibility",
			data: "id=" + my_tool_id + "&sent_http_request=1",
			success: function(data) {
				eval("var info=" + data);
				new_current_tool_image = info.image;
				new_current_view       = "'.api_get_path('WEB_PUBLIC_PATH').'img/" + info.view;
				//eyes
				$("#" + tool_id).attr("src", new_current_view);
				//tool
				$("#toolimage_" + my_tool_id).attr("src", new_current_tool_image);
				//clase
				$("#tooldesc_" + my_tool_id).attr("class", info.tclass);
				$("#istooldesc_" + my_tool_id).attr("class", info.tclass);

				if (image_link == "visible.png") {
					$("#" + tool_id).attr("alt", "'.get_lang('Activate').'");
					$("#" + tool_id).attr("title", "'.get_lang('Activate').'");
				} else {
					$("#" + tool_id).attr("alt", "'.get_lang('Deactivate').'");
					$("#" + tool_id).attr("title", "'.get_lang('Deactivate').'");
				}
				if (info.message == "is_active") {
					message = "'.get_lang('ToolIsNowVisible').'";
				} else {
					message = "'.get_lang('ToolIsNowHidden').'";
				}
				$(".normal-message").hide();
				$("#id_confirmation_message").html(message);
				$("#id_confirmation_message").show();
			}
		});
	});
});

</script>';

// The section for the tabs
$this_section = SECTION_COURSES;

$user_id = api_get_user_id();
$course_code = api_get_course_id();
$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();
$show_message = '';

if (api_is_invitee()) {
    $isInASession = $sessionId > 0;
    $isSubscribed = CourseManager::is_user_subscribed_in_course(
        $user_id,
        $course_code,
        $isInASession,
        $sessionId
    );

    if (!$isSubscribed) {
        api_not_allowed(true);
    }
}

// Deleting group session
Session::erase('toolgroup');
Session::erase('_gid');

$isSpecialCourse = CourseManager::isSpecialCourse($courseId);

if ($isSpecialCourse) {
    if (isset($_GET['autoreg']) && $_GET['autoreg'] == 1) {
        if (CourseManager::subscribeUser($user_id, $course_code, STUDENT)) {
            Session::write('is_allowed_in_course', true);
        }
    }
}

$action = !empty($_GET['action']) ? Security::remove_XSS($_GET['action']) : '';

if ($action == 'subscribe') {
    if (Security::check_token('get')) {
        Security::clear_token();
        $result = CourseManager::autoSubscribeToCourse($course_code);
        if ($result) {
            if (CourseManager::is_user_subscribed_in_course($user_id, $course_code)) {
                Session::write('is_allowed_in_course', true);
            }
        }
        header('Location: '.api_get_self());
        exit;
    }
}

/*	Is the user allowed here? */
api_protect_course_script(true);

/*  STATISTICS */
if (!isset($coursesAlreadyVisited[$course_code])) {
    Event::accessCourse();
    $coursesAlreadyVisited[$course_code] = 1;
    Session::write('coursesAlreadyVisited', $coursesAlreadyVisited);
}

$logInfo = [
    'tool' => 'course-main',
    'tool_id' => 0,
    'tool_id_detail' => 0,
    'action' => $action,
    'info' => '',
];
Event::registerLog($logInfo);

/* Auto launch code */
$autoLaunchWarning = '';
$showAutoLaunchLpWarning = false;
$course_id = api_get_course_int_id();
$lpAutoLaunch = api_get_course_setting('enable_lp_auto_launch');
$session_id = api_get_session_id();
$allowAutoLaunchForCourseAdmins = api_is_platform_admin() || api_is_allowed_to_edit(true, true) || api_is_coach();

if (!empty($lpAutoLaunch)) {
    if ($lpAutoLaunch == 2) {
        // LP list
        if ($allowAutoLaunchForCourseAdmins) {
            $showAutoLaunchLpWarning = true;
        } else {
            $session_key = 'lp_autolaunch_'.$session_id.'_'.api_get_course_int_id().'_'.api_get_user_id();
            if (!isset($_SESSION[$session_key])) {
                // Redirecting to the LP
                $url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq().'&id_session='.$session_id;
                $_SESSION[$session_key] = true;
                header("Location: $url");
                exit;
            }
        }
    } else {
        $lp_table = Database::get_course_table(TABLE_LP_MAIN);
        $condition = '';
        if (!empty($session_id)) {
            $condition = api_get_session_condition($session_id);
            $sql = "SELECT id FROM $lp_table
                    WHERE c_id = $course_id AND autolaunch = 1 $condition
                    LIMIT 1";
            $result = Database::query($sql);
            // If we found nothing in the session we just called the session_id =  0 autolaunch
            if (Database::num_rows($result) == 0) {
                $condition = '';
            }
        }

        $sql = "SELECT id FROM $lp_table
                WHERE c_id = $course_id AND autolaunch = 1 $condition
                LIMIT 1";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            $lp_data = Database::fetch_array($result, 'ASSOC');
            if (!empty($lp_data['id'])) {
                if ($allowAutoLaunchForCourseAdmins) {
                    $showAutoLaunchLpWarning = true;
                } else {
                    $session_key = 'lp_autolaunch_'.$session_id.'_'.api_get_course_int_id().'_'.api_get_user_id();
                    if (!isset($_SESSION[$session_key])) {
                        // Redirecting to the LP
                        $url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$lp_data['id'];

                        $_SESSION[$session_key] = true;
                        header("Location: $url");
                        exit;
                    }
                }
            }
        }
    }
}

if ($showAutoLaunchLpWarning) {
    $autoLaunchWarning = get_lang('The learning path auto-launch setting is ON. When learners enter this course, they will be automatically redirected to the learning path marked as auto-launch.');
}

$forumAutoLaunch = api_get_course_setting('enable_forum_auto_launch');
if ($forumAutoLaunch == 1) {
    if ($allowAutoLaunchForCourseAdmins) {
        if (empty($autoLaunchWarning)) {
            $autoLaunchWarning = get_lang('The forum\'s auto-launch setting is on. Students will be redirected to the forum tool when entering this course.');
        }
    } else {
        $url = api_get_path(WEB_CODE_PATH).'forum/index.php?'.api_get_cidreq().'&id_session='.$session_id;
        header("Location: $url");
        exit;
    }
}

if (api_get_configuration_value('allow_exercise_auto_launch')) {
    $exerciseAutoLaunch = (int) api_get_course_setting('enable_exercise_auto_launch');
    if ($exerciseAutoLaunch == 2) {
        if ($allowAutoLaunchForCourseAdmins) {
            if (empty($autoLaunchWarning)) {
                $autoLaunchWarning = get_lang(
                    'TheExerciseAutoLaunchSettingIsONStudentsWillBeRedirectToTheExerciseList'
                );
            }
        } else {
            // Redirecting to the document
            $url = api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq().'&id_session='.$session_id;
            header("Location: $url");
            exit;
        }
    } elseif ($exerciseAutoLaunch == 1) {
        if ($allowAutoLaunchForCourseAdmins) {
            if (empty($autoLaunchWarning)) {
                $autoLaunchWarning = get_lang(
                    'TheExerciseAutoLaunchSettingIsONStudentsWillBeRedirectToAnSpecificExercise'
                );
            }
        } else {
            // Redirecting to an exercise
            $table = Database::get_course_table(TABLE_QUIZ_TEST);
            $condition = '';
            if (!empty($session_id)) {
                $condition = api_get_session_condition($session_id);
                $sql = "SELECT iid FROM $table
                        WHERE c_id = $course_id AND autolaunch = 1 $condition
                        LIMIT 1";
                $result = Database::query($sql);
                // If we found nothing in the session we just called the session_id = 0 autolaunch
                if (Database::num_rows($result) == 0) {
                    $condition = '';
                }
            }

            $sql = "SELECT iid FROM $table
                    WHERE c_id = $course_id AND autolaunch = 1 $condition
                    LIMIT 1";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                $row = Database::fetch_array($result, 'ASSOC');
                $exerciseId = $row['iid'];
                $url = api_get_path(WEB_CODE_PATH).
                    'exercise/overview.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'&id_session='.$session_id;
                header("Location: $url");
                exit;
            }
        }
    }
}

$documentAutoLaunch = api_get_course_setting('enable_document_auto_launch');
if ($documentAutoLaunch == 1) {
    if ($allowAutoLaunchForCourseAdmins) {
        if (empty($autoLaunchWarning)) {
            $autoLaunchWarning = get_lang('The document auto-launch feature configuration is enabled. Learners will be automatically redirected to document tool.');
        }
    } else {
        // Redirecting to the document
        $url = api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq().'&id_session='.$session_id;
        header("Location: $url");
        exit;
    }
}

// Used in different pages
$tool_table = Database::get_course_table(TABLE_TOOL_LIST);

/*	Introduction section (editable by course admins) */
$content = Display::return_introduction_section(
    TOOL_COURSE_HOMEPAGE,
    [
        'CreateDocumentWebDir' => api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/',
        'CreateDocumentDir' => 'document/',
        'BaseHref' => api_get_path(WEB_COURSE_PATH).api_get_course_path().'/',
    ]
);

/*	SWITCH TO A DIFFERENT HOMEPAGE VIEW
    the setting homepage_view is adjustable through
    the platform administration section */
if (!empty($autoLaunchWarning)) {
    $show_message .= Display::return_message(
        $autoLaunchWarning,
        'warning'
    );
}

/*$homePageView = api_get_setting('homepage_view');
switch ($homePageView) {
    case 'activity':
    case 'activity_big':
        require 'activity.php';
        break;
}*/
require 'activity.php';

// Get session-career diagram
$diagram = '';
$allow = api_get_configuration_value('allow_career_diagram');
if ($allow === true) {
    $htmlHeadXtra[] = api_get_js('jsplumb2.js');
    $extra = new ExtraFieldValue('session');
    $value = $extra->get_values_by_handler_and_field_variable(
        api_get_session_id(),
        'external_career_id'
    );

    if (!empty($value) && isset($value['value'])) {
        $careerId = $value['value'];
        $extraFieldValue = new ExtraFieldValue('career');
        $item = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
            'external_career_id',
            $careerId,
            false,
            false,
            false
        );

        if (!empty($item) && isset($item['item_id'])) {
            $careerId = $item['item_id'];
            $career = new Career();
            $careerInfo = $career->get($careerId);
            if (!empty($careerInfo)) {
                $extraFieldValue = new ExtraFieldValue('career');
                $item = $extraFieldValue->get_values_by_handler_and_field_variable(
                    $careerId,
                    'career_diagram',
                    false,
                    false,
                    false
                );

                if (!empty($item) && isset($item['value']) && !empty($item['value'])) {
                    /** @var Graph $graph */
                    $graph = UnserializeApi::unserialize(
                        'career',
                        $item['value']
                    );
                    $diagram = Career::renderDiagram($careerInfo, $graph);
                }
            }
        }
    }
}

$content = '<div id="course_tools">'.$diagram.$content.'</div>';

// Deleting the objects
Session::erase('_gid');
Session::erase('oLP');
Session::erase('lpobject');
api_remove_in_gradebook();
Exercise::cleanSessionVariables();
DocumentManager::removeGeneratedAudioTempFile();

$tpl = new Template(null);
$tpl->assign('message', $show_message);
$tpl->assign('content', $content);

// Direct login to course
$tpl->assign('course_code', $course_code);
$tpl->display_one_col_template();

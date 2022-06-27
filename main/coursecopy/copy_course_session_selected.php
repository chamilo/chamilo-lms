<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use Chamilo\CourseBundle\Component\CourseCopy\CourseSelectForm;
use ChamiloSession as Session;

/**
 * Copy resources from one course in a session to another one.
 *
 * @author Christian Fasanando
 * @author Julio Montoya <gugli100@gmail.com> Lots of bug fixes/improvements
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com> Code conventions
 *
 * @package chamilo.backup
 */
require_once __DIR__.'/../inc/global.inc.php';

$current_course_tool = TOOL_COURSE_MAINTENANCE;

api_protect_course_script(true, true);
api_set_more_memory_and_time_limits();

$xajax = new xajax();
$xajax->registerFunction('searchCourses');

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

if (!api_is_coach()) {
    api_not_allowed(true);
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

$courseId = api_get_course_int_id();
$courseInfo = api_get_course_info_by_id($courseId);
$courseCode = $courseInfo['code'];
$sessionId = api_get_session_id();

if (empty($courseCode) || empty($sessionId)) {
    api_not_allowed(true);
}

$this_section = SECTION_COURSES;
$nameTools = get_lang('CopyCourse');
$returnLink = api_get_path(WEB_CODE_PATH).'course_info/maintenance_coach.php?'.api_get_cidreq();
$interbreadcrumb[] = [
    'url' => $returnLink,
    'name' => get_lang('Maintenance'),
];

/**
 * @param string $name
 */
function make_select_session_list($name, $sessions, $attr = [])
{
    $attrs = '';
    if (count($attr) > 0) {
        foreach ($attr as $key => $value) {
            $attrs .= ' '.$key.'="'.$value.'"';
        }
    }
    $output = '<select name="'.$name.'" '.$attrs.'>';

    if (count($sessions) == 0) {
        $output .= '<option value = "0">'.get_lang(
                'ThereIsNotStillASession'
            ).'</option>';
    } else {
        $output .= '<option value = "0">'.get_lang(
                'SelectASession'
            ).'</option>';
    }

    if (is_array($sessions)) {
        foreach ($sessions as $session) {
            $category_name = '';
            if (!empty($session['category_name'])) {
                $category_name = ' ('.$session['category_name'].')';
            }

            $output .= '<option value="'.$session['id'].'">'.$session['name'].' '.$category_name.'</option>';
        }
    }
    $output .= '</select>';

    return $output;
}

/**
 * Show the form to copy courses.
 *
 * @global string $returnLink
 * @global string $courseCode
 */
function displayForm()
{
    global $returnLink, $courseCode;

    $courseInfo = api_get_course_info();
    $sessionId = api_get_session_id();
    $userId = api_get_user_id();

    $sessions = SessionManager::getSessionsCoachedByUser($userId);
    $html = '';
    // Actions
    $html .= '<div class="actions">';
    // Link back to the documents overview
    $html .= '<a href="'.$returnLink.'">'.Display::return_icon(
            'back.png',
        get_lang('BackTo').' '.get_lang('Maintenance'),
        '',
        ICON_SIZE_MEDIUM
        ).'</a>';
    $html .= '</div>';

    $html .= Display::return_message(
            get_lang('CopyCourseFromSessionToSessionExplanation')
    );

    $html .= '<form name="formulaire" method="post" action="'.api_get_self().'?'.api_get_cidreq().'" >';
    $html .= '<table border="0" cellpadding="5" cellspacing="0" width="100%">';

    // Source
    $html .= '<tr><td width="15%"><b>'.get_lang(
            'OriginCoursesFromSession'
        ).':</b></td>';
    $html .= '<td width="10%" align="left">'.api_get_session_name(
            $sessionId
        ).'</td>';
    $html .= '<td width="50%">';
    $html .= "{$courseInfo['title']} ({$courseInfo['code']})".'</td></tr>';

    // Destination
    $html .= '<tr><td width="15%"><b>'.get_lang(
            'DestinationCoursesFromSession'
        ).':</b></td>';
    $html .= '<td width="10%" align="left"><div id="ajax_sessions_list_destination">';
    $html .= '<select name="sessions_list_destination" onchange="javascript: xajax_searchCourses(this.value,\'destination\');">';
    if (empty($sessions)) {
        $html .= '<option value = "0">'.get_lang(
                'ThereIsNotStillASession'
            ).'</option>';
    } else {
        $html .= '<option value = "0">'.get_lang(
                'SelectASession'
            ).'</option>';
        foreach ($sessions as $session) {
            if ($session['id'] == $sessionId) {
                continue;
            }

            if (!SessionManager::sessionHasCourse($session['id'], $courseCode)) {
                continue;
            }

            $html .= '<option value="'.$session['id'].'">'.$session['name'].'</option>';
        }
    }

    $html .= '</select ></div></td>';

    $html .= '<td width="50%">';
    $html .= '<div id="ajax_list_courses_destination">';
    $html .= '<select id="destination" name="SessionCoursesListDestination[]" style="width:380px;" ></select></div></td>';
    $html .= '</tr></table>';

    $html .= "<fieldset>";
    $html .= '<legend>'.get_lang('TypeOfCopy').' <small>('.get_lang('CopyOnlySessionItems').')</small></legend>';
    $html .= '<label class="radio"><input type="radio" id="copy_option_1" name="copy_option" value="full_copy" checked="checked"/>';
    $html .= get_lang('FullCopy').'</label>';
    /*$html .= '<label class="radio"><input type="radio" id="copy_option_2" name="copy_option" value="select_items"/>';
    $html .= ' '.get_lang('LetMeSelectItems').'</label><br/>';*/

    $html .= "</fieldset>";

    $html .= '<button class="save" type="submit" onclick="javascript:if(!confirm('."'".addslashes(
            api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES)
        )."'".')) return false;">'.get_lang('CopyCourse').'</button>';
    $html .= '</form>';
    echo $html;
}

function searchCourses($idSession, $type)
{
    $xajaxResponse = new xajaxResponse();
    $return = null;
    $courseCode = api_get_course_id();

    if (!empty($type)) {
        $idSession = (int) $idSession;
        $courseList = SessionManager::get_course_list_by_session_id($idSession);

        $return .= '<select id="destination" name="SessionCoursesListDestination[]" style="width:380px;" >';

        foreach ($courseList as $course) {
            $course_list_destination[] = $course['code'];

            if ($course['code'] != $courseCode) {
                continue;
            }

            $courseTitle = str_replace("'", "\'", $course['title']);

            $return .= '<option value="'.$course['code'].'" title="'.@htmlspecialchars(
                    $course['title'].' ('.$course['visual_code'].')',
                ENT_QUOTES,
                api_get_system_encoding()
                ).'">'.
                $course['title'].' ('.$course['visual_code'].')</option>';
        }
        $return .= '</select>';
        Session::write('course_list_destination', $course_list_destination);

        // Send response by ajax
        $xajaxResponse->addAssign(
            'ajax_list_courses_destination',
            'innerHTML',
            api_utf8_encode($return)
        );
    }

    return $xajaxResponse;
}

$xajax->processRequests();

$htmlHeadXtra[] = $xajax->getJavascript(
    api_get_path(WEB_LIBRARY_PATH).'xajax/'
);
$htmlHeadXtra[] = '<script>
	function checkSelected(id_select,id_radio,id_title,id_destination) {
        var num=0;
        obj_origin = document.getElementById(id_select);
        obj_destination = document.getElementById(id_destination);

        for (x=0;x<obj_origin.options.length;x) {
            if (obj_origin.options[x].selected) {
                if (obj_destination.options.length > 0) {
                    for (y=0;y<obj_destination.options.length;y) {
                        if (obj_origin.options[x].value == obj_destination.options[y].value) {
                            obj_destination.options[y].selected = true;
                        }
                    }
                }
                num;
            } else {
                if (obj_destination.options.length > 0) {
                    for (y=0;y<obj_destination.options.length;y) {
                        if (obj_origin.options[x].value == obj_destination.options[y].value) {
                            obj_destination.options[y].selected = false;
                        }
                    }
                }
            }
        }

    if (num == 1) {
        document.getElementById(id_radio).disabled = false;
        document.getElementById(id_title).style.color = \'#000\';
    } else {
        document.getElementById(id_radio).disabled = true;
        document.getElementById(id_title).style.color = \'#aaa\';
    }

	}
</script>';

Display::display_header($nameTools);

/* MAIN CODE */

if (($action === 'course_select_form') ||
    (isset($_POST['copy_option']) && $_POST['copy_option'] == 'full_copy')
) {
    $destinationCourse = $destinationSession = '';
    $originCourse = api_get_course_id();
    $originSession = api_get_session_id();

    if ($action === 'course_select_form') {
        $destinationCourse = $_POST['destination_course'];
        $destinationSession = $_POST['destination_session'];
        $course = CourseSelectForm::get_posted_course(
            'copy_course',
            $originSession,
            $originCourse
        );

        $cr = new CourseRestorer($course);
        $cr->restore($destinationCourse, $destinationSession);
        echo Display::return_message(get_lang('CopyFinished'), 'confirmation');

        displayForm();
    } else {
        $arrCourseOrigin = [];
        $arrCourseDestination = [];
        $destinationSession = '';

        if (isset($_POST['SessionCoursesListDestination'])) {
            $arrCourseDestination = $_POST['SessionCoursesListDestination'];

            if (!empty($arrCourseDestination)) {
                $arrCourseOrigin = SessionManager::get_course_list_by_session_id(
                    api_get_session_id(),
                    $courseInfo['title']
                );
            }
        }

        if (isset($_POST['sessions_list_destination'])) {
            $destinationSession = $_POST['sessions_list_destination'];
        }

        if ((is_array($arrCourseOrigin) && count($arrCourseOrigin) > 0) && !empty($destinationSession)) {
            //We need only one value
            if (count($arrCourseOrigin) > 1 || count($arrCourseDestination) > 1) {
                echo Display::return_message(
                    get_lang('YouMustSelectACourseFromOriginalSession'),
                    'error'
                );
            } else {
                $courseDestination = $arrCourseDestination[0];

                $cb = new CourseBuilder('', $courseInfo);
                $course = $cb->build(
                    $originSession,
                    $courseCode
                );
                $cr = new CourseRestorer($course);
                $cr->restore($courseDestination, $destinationSession);
                echo Display::return_message(get_lang('CopyFinished'), 'confirmation');
            }

            displayForm();
        } else {
            echo Display::return_message(
                get_lang('YouMustSelectACourseFromOriginalSession'),
                'error'
            );
            displayForm();
        }
    }
} elseif (isset($_POST['copy_option']) && $_POST['copy_option'] == 'select_items') {
    // Else, if a CourseSelectForm is requested, show it
    if (api_get_setting('show_glossary_in_documents') != 'none') {
        echo Display::return_message(
            get_lang('ToExportDocumentsWithGlossaryYouHaveToSelectGlossary'),
            'normal'
        );
    }

    $arrCourseDestination = [];
    $destinationSession = '';

    if (isset($_POST['SessionCoursesListDestination'])) {
        $arrCourseDestination = $_POST['SessionCoursesListDestination'];
    }
    if (isset($_POST['sessions_list_destination'])) {
        $destinationSession = $_POST['sessions_list_destination'];
    }

    if (!empty($destinationSession)) {
        echo Display::return_message(
            get_lang('ToExportLearnpathWithQuizYouHaveToSelectQuiz'),
            'normal'
        );

        $cb = new CourseBuilder('', $courseInfo);
        $course = $cb->build($sessionId, $courseCode);
        $hiddenFields['destination_course'] = $arrCourseDestination[0];
        $hiddenFields['destination_session'] = $destinationSession;
        $hiddenFields['origin_course'] = api_get_course_id();
        $hiddenFields['origin_session'] = api_get_session_id();

        CourseSelectForm::display_form($course, $hiddenFields, true);

        echo '<div style="float:right"><a href="javascript:window.history.go(-1);">'.
            Display::return_icon(
                'back.png',
                get_lang('Back').' '.get_lang('To').' '.get_lang(
                    'PlatformAdmin'
                ),
                ['style' => 'vertical-align:middle']
            ).
            get_lang('Back').'</a></div>';
    } else {
        echo Display::return_message(
            get_lang('You must select a course from original session and select a destination session'),
            'error'
        );
        displayForm();
    }
} else {
    displayForm();
}

Display::display_footer();

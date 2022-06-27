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
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_COURSE_MAINTENANCE;

api_protect_admin_script();
api_protect_limit_for_session_admin();
api_set_more_memory_and_time_limits();

$xajax = new xajax();
$xajax->registerFunction('search_courses');

if (!api_is_allowed_to_edit() && !api_is_session_admin()) {
    api_not_allowed(true);
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$this_section = SECTION_PLATFORM_ADMIN;

$nameTools = get_lang('CopyCourse');
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
    'name' => get_lang('PlatformAdmin'),
];

// Database Table Definitions
$tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);

/**
 * @param string $name
 * @param array  $sessions
 * @param array  $attr
 *
 * @return string
 */
function make_select_session_list($name, $sessions, $attr = [])
{
    $attributes = '';
    if (count($attr) > 0) {
        foreach ($attr as $key => $value) {
            $attributes .= ' '.$key.'="'.$value.'"';
        }
    }
    $output = '<select id="session" class="form-control" name="'.$name.'" '.$attributes.'>';

    if (count($sessions) == 0) {
        $output .= '<option value = "0">'.get_lang('ThereIsNotStillASession').'</option>';
    } else {
        $output .= '<option value = "0">'.get_lang('SelectASession').'</option>';
    }

    if (is_array($sessions)) {
        foreach ($sessions as $session) {
            $categoryName = '';
            if (!empty($session['category_name'])) {
                $categoryName = ' ('.$session['category_name'].')';
            }

            $output .= '<option value="'.$session['id'].'">'.
                $session['name'].' '.$categoryName.
            '</option>';
        }
    }
    $output .= '</select>';

    return $output;
}

/**
 * @return string
 */
function display_form()
{
    $html = '';
    $sessions = SessionManager::get_sessions_list([], ['name', 'ASC']);

    // Link back to the documents overview
    $actionsLeft = '<a href="../admin/index.php">'.
        Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'), '', ICON_SIZE_MEDIUM).
        '</a>';

    $html .= Display::toolbarAction('toolbar-copysession', [$actionsLeft]);

    $html .= Display::return_message(get_lang('CopyCourseFromSessionToSessionExplanation'), 'warning');

    $html .= '<form class="form-horizontal" name="formulaire" method="post" action="'.api_get_self().'" >';
    $html .= '<div class="form-group">';

    // origin
    $html .= '<label class="col-sm-2 control-label">'.get_lang('OriginCoursesFromSession').': </label>';
    $html .= '<div class="col-sm-5">';
    $html .= make_select_session_list(
        'sessions_list_origin',
        $sessions,
        ['onchange' => 'javascript: xajax_search_courses(this.value,\'origin\');']
    );
    $html .= '</div>';
    $html .= '<div class="col-sm-5" id="ajax_list_courses_origin">';
    $html .= '<select id="origin" class="form-control" name="SessionCoursesListOrigin[]" ></select>';
    $html .= '</div></div>';

    //destination
    $html .= '<div class="form-group">';
    $html .= '<label class="col-sm-2 control-label">'.get_lang('DestinationCoursesFromSession').': </label>';
    $html .= '<div class="col-sm-5" id="ajax_sessions_list_destination">';
    $html .= '<select class="form-control" name="sessions_list_destination" onchange="javascript: xajax_search_courses(this.value,\'destination\');">';
    $html .= '<option value = "0">'.get_lang('ThereIsNotStillASession').'</option></select ></div>';

    $html .= '<div class="col-sm-5" id="ajax_list_courses_destination">';
    $html .= '<select id="destination" class="form-control" name="SessionCoursesListDestination[]" ></select>';
    $html .= '</div></div>';

    $options = '<div class="radio"><label><input type="radio" id="copy_option_1" name="copy_option" value="full_copy" checked="checked"/>';
    $options .= get_lang('FullCopy').'</label></div>';
    /*$options .= '<div class="radio"><label><input type="radio" id="copy_option_2" name="copy_option" value="select_items" disabled="disabled"/>';
    $options .= ' '.get_lang('LetMeSelectItems').'</label></div>';*/

    $options .= '<div class="checkbox"><label><input type="checkbox" id="copy_base_content_id" name="copy_only_session_items" />'.get_lang('CopyOnlySessionItems').'</label></div>';

    $html .= Display::panel($options, get_lang('TypeOfCopy'));

    $html .= '<div class="form-group"><div class="col-sm-12">';
    $html .= '<button class="btn btn-success" type="submit" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."'".')) return false;"><em class="fa fa-files-o"></em> '.get_lang('CopyCourse').'</button>';

    // Add Security token
    $html .= '<input type="hidden" value="'.Security::get_token().'" name="sec_token">';
    $html .= '</div></div>';
    $html .= '</form>';

    echo $html;
}

/**
 * @param int    $id_session
 * @param string $type
 *
 * @return xajaxResponse
 */
function search_courses($id_session, $type)
{
    global $tbl_course, $tbl_session_rel_course;
    $xajax_response = new xajaxResponse();
    $select_destination = '';
    $return = null;

    if (!empty($type)) {
        $id_session = (int) $id_session;
        if ($type == 'origin') {
            $course_list = SessionManager::get_course_list_by_session_id($id_session);
            $temp_course_list = [];
            $return .= '<select id="origin" name="SessionCoursesListOrigin[]" class="form-control" onclick="javascript: checkSelected(this.id,\'copy_option_2\',\'title_option2\',\'destination\');">';

            foreach ($course_list as $course) {
                $temp_course_list[] = "'{$course['code']}'";
                $return .= '<option value="'.$course['code'].'" title="'.@htmlspecialchars($course['title'].' ('.$course['visual_code'].')', ENT_QUOTES, api_get_system_encoding()).'">'.$course['title'].' ('.$course['visual_code'].')</option>';
            }

            $return .= '</select>';
            Session::write('course_list', $temp_course_list);
            Session::write('session_origin', $id_session);

            // Build select for destination sessions where is not included current session from select origin
            if (!empty($id_session)) {
                $sessions = SessionManager::get_sessions_list([], ['name', 'ASC']);
                $select_destination .= '<select name="sessions_list_destination" class="form-control" onchange = "javascript: xajax_search_courses(this.value,\'destination\');">';
                $select_destination .= '<option value = "0">-- '.get_lang('SelectASession').' --</option>';
                foreach ($sessions as $session) {
                    if ($id_session == $session['id']) {
                        continue;
                    }
                    if (!empty($session['category_name'])) {
                        $session['category_name'] = ' ('.$session['category_name'].') ';
                    }
                    $select_destination .= '<option value="'.$session['id'].'">'.$session['name'].' '.$session['category_name'].'</option>';
                }
                $select_destination .= '</select>';
                $xajax_response->addAssign('ajax_sessions_list_destination', 'innerHTML', api_utf8_encode($select_destination));
            } else {
                $select_destination .= '<select name="sessions_list_destination" class="form-control" onchange = "javascript: xajax_search_courses(this.value,\'destination\');">';
                $select_destination .= '<option value = "0">'.get_lang('ThereIsNotStillASession').'</option>';
                $select_destination .= '</select>';
                $xajax_response->addAssign('ajax_sessions_list_destination', 'innerHTML', api_utf8_encode($select_destination));
            }

            // Select multiple destination empty
            $select_multiple_empty = '<select id="destination" name="SessionCoursesListDestination[]" class="form-control"></select>';

            // Send response by ajax
            $xajax_response->addAssign('ajax_list_courses_origin', 'innerHTML', api_utf8_encode($return));
            $xajax_response->addAssign('ajax_list_courses_destination', 'innerHTML', api_utf8_encode($select_multiple_empty));
        } else {
            // Search courses by id_session where course codes is include en courses list destination
            $sql = "SELECT c.code, c.visual_code, c.title, src.session_id
                    FROM $tbl_course c, $tbl_session_rel_course src
                    WHERE src.c_id = c.id
                    AND src.session_id = '".intval($id_session)."'";
            //AND c.code IN ($list_courses_origin)";
            $rs = Database::query($sql);

            $course_list_destination = [];
            $return .= '<select id="destination" name="SessionCoursesListDestination[]" class="form-control">';
            while ($course = Database::fetch_array($rs)) {
                $course_list_destination[] = $course['code'];
                $return .= '<option value="'.$course['code'].'" title="'.@htmlspecialchars($course['title'].' ('.$course['visual_code'].')', ENT_QUOTES, api_get_system_encoding()).'">'.$course['title'].' ('.$course['visual_code'].')</option>';
            }
            $return .= '</select>';

            Session::write('course_list_destination', $course_list_destination);

            // Send response by ajax
            $xajax_response->addAssign(
                'ajax_list_courses_destination',
                'innerHTML',
                api_utf8_encode($return)
            );
        }
    }

    return $xajax_response;
}
$xajax->processRequests();

/* HTML head extra */
$htmlHeadXtra[] = $xajax->getJavascript(api_get_path(WEB_LIBRARY_PATH).'xajax/');
$htmlHeadXtra[] = '<script>
function checkSelected(id_select,id_radio,id_title,id_destination) {
   var num=0;
   obj_origin = document.getElementById(id_select);
   obj_destination = document.getElementById(id_destination);

   for (x=0;x<obj_origin.options.length;x++) {
      if (obj_origin.options[x].selected) {
            if (obj_destination.options.length > 0) {
                for (y=0;y<obj_destination.options.length;y++) {
                        if (obj_origin.options[x].value == obj_destination.options[y].value) {
                            obj_destination.options[y].selected = true;
                        }
                }
            }
            num++;
        } else {
            if (obj_destination.options.length > 0) {
                for (y=0;y<obj_destination.options.length;y++) {
                    if (obj_origin.options[x].value == obj_destination.options[y].value) {
                        obj_destination.options[y].selected = false;
                    }
                }
            }
        }
   }
}
</script>';

Display::display_header($nameTools);

$with_base_content = true;
if (isset($_POST['copy_only_session_items']) && $_POST['copy_only_session_items']) {
    $with_base_content = false;
}

/*  MAIN CODE  */
if (Security::check_token('post') && (
        ($action === 'course_select_form') || (
            isset($_POST['copy_option']) &&
            $_POST['copy_option'] == 'full_copy'
        )
    )
) {
    // Clear token
    Security::clear_token();
    $destination_course = $origin_course = $destination_session = $origin_session = '';
    if ($action === 'course_select_form') {
        $destination_course = $_POST['destination_course'];
        $origin_course = $_POST['origin_course'];
        $destination_session = $_POST['destination_session'];
        $origin_session = $_POST['origin_session'];

        if ($course_code != $origin_course) {
            $course = CourseSelectForm::get_posted_course(
                'copy_course',
                $origin_session,
                $origin_course
            );

            $cr = new CourseRestorer($course);
            //$cr->set_file_option($_POST['same_file_name_option']);
            $cr->restore($destination_course, $destination_session);
            echo Display::return_message(get_lang('CopyFinished'), 'confirmation');
            display_form();
        } else {
            echo Display::return_message(get_lang('PleaseSelectACourse'), 'confirm');
            display_form();
        }
    } else {
        $arr_course_origin = [];
        $arr_course_destination = [];
        $destination_session = '';
        $origin_session = '';

        if (isset($_POST['SessionCoursesListOrigin'])) {
            $arr_course_origin = $_POST['SessionCoursesListOrigin'];
        }
        if (isset($_POST['SessionCoursesListDestination'])) {
            $arr_course_destination = $_POST['SessionCoursesListDestination'];
        }
        if (isset($_POST['sessions_list_destination'])) {
            $destination_session = $_POST['sessions_list_destination'];
        }
        if (isset($_POST['sessions_list_origin'])) {
            $origin_session = $_POST['sessions_list_origin'];
        }

        if ((is_array($arr_course_origin) && count($arr_course_origin) > 0) && !empty($destination_session)) {
            //We need only one value
            if (count($arr_course_origin) > 1 || count($arr_course_destination) > 1) {
                echo Display::return_message(get_lang('YouMustSelectACourseFromOriginalSession'), 'error');
            } else {
                //first element of the array
                $course_code = $arr_course_origin[0];
                $course_destinatination = $arr_course_destination[0];

                if ($course_code != $course_destinatination) {
                    $course_origin = api_get_course_info($course_code);
                    $cb = new CourseBuilder('', $course_origin);
                    $course = $cb->build($origin_session, $course_code, $with_base_content);
                    $cr = new CourseRestorer($course);
                    $cr->restore($course_destinatination, $destination_session);

                    echo Display::return_message(get_lang('CopyFinished'), 'confirm');
                    display_form();
                } else {
                    echo Display::return_message(get_lang('PleaseSelectACourse'), 'confirm');
                    display_form();
                }
            }
        } else {
            echo Display::return_message(get_lang('YouMustSelectACourseFromOriginalSession'), 'error');
            display_form();
        }
    }
} elseif (Security::check_token('post') && (
        isset($_POST['copy_option']) &&
        $_POST['copy_option'] == 'select_items'
    )
) {
    // Clear token
    Security::clear_token();

    // Else, if a CourseSelectForm is requested, show it
    if (api_get_setting('show_glossary_in_documents') != 'none') {
        echo Display::return_message(get_lang('ToExportDocumentsWithGlossaryYouHaveToSelectGlossary'), 'normal');
    }

    $arr_course_origin = [];
    $arr_course_destination = [];
    $destination_session = '';
    $origin_session = '';

    if (isset($_POST['SessionCoursesListOrigin'])) {
        $arr_course_origin = $_POST['SessionCoursesListOrigin'];
    }
    if (isset($_POST['SessionCoursesListDestination'])) {
        $arr_course_destination = $_POST['SessionCoursesListDestination'];
    }
    if (isset($_POST['sessions_list_destination'])) {
        $destination_session = $_POST['sessions_list_destination'];
    }
    if (isset($_POST['sessions_list_origin'])) {
        $origin_session = $_POST['sessions_list_origin'];
    }

    if ((is_array($arr_course_origin) && count($arr_course_origin) > 0) && !empty($destination_session)) {
        echo Display::return_message(get_lang('ToExportLearnpathWithQuizYouHaveToSelectQuiz'), 'normal');
        $course_origin = api_get_course_info($arr_course_origin[0]);
        $cb = new CourseBuilder('', $course_origin);
        $course = $cb->build($origin_session, $arr_course_origin[0], $with_base_content);
        $hiddenFields['destination_course'] = $arr_course_destination[0];
        $hiddenFields['origin_course'] = $arr_course_origin[0];
        $hiddenFields['destination_session'] = $destination_session;
        $hiddenFields['origin_session'] = $origin_session;
        // Add token to Course select form
        $hiddenFields['sec_token'] = Security::get_token();
        CourseSelectForm::display_form($course, $hiddenFields, true);
        echo '<div style="float:right"><a href="javascript:window.history.go(-1);">'.
            Display::return_icon(
                'back.png',
                get_lang('Back').' '.get_lang('To').' '.get_lang('PlatformAdmin'),
                ['style' => 'vertical-align:middle']
            ).
            get_lang('Back').'</a></div>';
    } else {
        echo Display::return_message(
            get_lang('You must select a course from original session and select a destination session'),
            'error'
        );
        display_form();
    }
} else {
    display_form();
}

Display::display_footer();

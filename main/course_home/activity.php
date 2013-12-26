<?php
/* For licensing terms, see /license.txt */

/**
 *   HOME PAGE FOR EACH COURSE
 *
 *	This page, included in every course's index.php is the home
 *	page. To make administration simple, the teacher edits his
 *	course from the home page. Only the login detects that the
 *	visitor is allowed to activate, deactivate home page links,
 *	access to the teachers tools (statistics, edit forums...).
 *
 *	@package chamilo.course_home
 */
function return_block($title, $content)
{
    $html = '<div class="page-header">
                <h3>'.$title.'</h3>
            </div>
            '.$content.'</div>';
    return $html;
}

$session_id = api_get_session_id();
global $app;
$urlGenerator = $app['url_generator'];

$content = null;

// Start of tools for CourseAdmins (teachers/tutors)

if ($session_id == 0 && api_is_course_admin() && api_is_allowed_to_edit(null, true)) {
    $my_list = CourseHome::get_tools_category(TOOL_AUTHORING);
	$items = CourseHome::show_tools_category($urlGenerator, $my_list);
    $content .= return_block(get_lang('Authoring'), $items);

    $my_list = CourseHome::get_tools_category(TOOL_INTERACTION);
    $list2 = CourseHome::get_tools_category(TOOL_COURSE_PLUGIN);

    $my_list = array_merge($my_list, $list2);
    $items =  CourseHome::show_tools_category($urlGenerator, $my_list);

    $content .= return_block(get_lang('Interaction'), $items);

    $my_list = CourseHome::get_tools_category(TOOL_ADMIN_PLATFORM);
    $items = CourseHome::show_tools_category($urlGenerator, $my_list);

    $content .= return_block(get_lang('Administration'), $items);

} elseif (api_is_coach()) {

    $content .=  '<div class="row">';
    $my_list = CourseHome::get_tools_category(TOOL_STUDENT_VIEW);
    $content .= CourseHome::show_tools_category($urlGenerator, $my_list);
    $content .= '</div>';
} else {
	$my_list = CourseHome::get_tools_category(TOOL_STUDENT_VIEW);
	if (count($my_list) > 0) {
        $content .= '<div class="row">';
        $content .= CourseHome::show_tools_category($urlGenerator, $my_list);
        $content .= '</div>';
	}
}

return $content;

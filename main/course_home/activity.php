<?php
/* For licensing terms, see /license.txt */
use  \ChamiloSession as Session;

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

$urlGenerator = Session::$urlGenerator;

$content = null;

// Start of tools for CourseAdmins (teachers/tutors)
$totalList = array();

if ($session_id == 0 && api_is_course_admin() && api_is_allowed_to_edit(null, true)) {
    $list = CourseHome::get_tools_category(TOOL_AUTHORING);

	$result = CourseHome::show_tools_category($urlGenerator, $list);

    $content .= return_block(get_lang('Authoring'), $result['content']);

    $totalList = $result['tool_list'];

    $list = CourseHome::get_tools_category(TOOL_INTERACTION);
    $list2 = CourseHome::get_tools_category(TOOL_COURSE_PLUGIN);
    $list = array_merge($list, $list2);
    $result =  CourseHome::show_tools_category($urlGenerator, $list);
    $totalList = array_merge($totalList, $result['tool_list']);

    $content .= return_block(get_lang('Interaction'), $result['content']);

    $list = CourseHome::get_tools_category(TOOL_ADMIN_PLATFORM);
    $totalList = array_merge($totalList, $list);
    $result = CourseHome::show_tools_category($urlGenerator, $list);

    $totalList = array_merge($totalList, $result['tool_list']);

    $content .= return_block(get_lang('Administration'), $result['content']);

} elseif (api_is_coach()) {

    $content .=  '<div class="row">';
    $list = CourseHome::get_tools_category(TOOL_STUDENT_VIEW);
    $content .= CourseHome::show_tools_category($urlGenerator, $result['content']);
    $totalList = array_merge($totalList, $result['tool_list']);
    $content .= '</div>';
} else {
    $list = CourseHome::get_tools_category(TOOL_STUDENT_VIEW);
    if (count($list) > 0) {
        $content .= '<div class="row">';
        $result = CourseHome::show_tools_category($urlGenerator, $list);
        $content .= $result['content'];
        $totalList = array_merge($totalList, $result['tool_list']);
        $content .= '</div>';
    }
}

return array(
    'content' => $content,
    'tool_list' => $totalList
);

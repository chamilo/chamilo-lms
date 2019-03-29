<?php
/* For licensing terms, see /license.txt */

/**
 * Document quota management script.
 *
 * @package chamilo.document
 */
require_once __DIR__.'/../inc/global.inc.php';

if (!api_is_allowed_to_edit(null, true)) {
    api_not_allowed(true);
}

$current_course_tool = TOOL_DOCUMENT;
$this_section = SECTION_COURSES;
$tool_name = get_lang('DocumentQuota');
$interbreadcrumb[] = ['url' => 'document.php', 'name' => get_lang('Documents')];

$htmlHeadXtra[] = api_get_js('jqplot/jquery.jqplot.js');
$htmlHeadXtra[] = api_get_js('jqplot/plugins/jqplot.pieRenderer.js');
$htmlHeadXtra[] = api_get_js('jqplot/plugins/jqplot.donutRenderer.js');
$htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/jqplot/jquery.jqplot.css');

$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();
$groupId = api_get_group_id();
$userId = api_get_user_id();
$userInfo = api_get_user_info($userId);
$session = [];
$user_name = $userInfo['complete_name'];

$course_list = SessionManager::get_course_list_by_session_id($sessionId);
$session_list = SessionManager::get_session_by_course($courseId);
$total_quota_bytes = DocumentManager::get_course_quota();
$quota_bytes = DocumentManager::documents_total_space($courseId, 0, 0);
$quotaPercentage = round($quota_bytes / $total_quota_bytes, 2) * 100;

$session[] = [get_lang('Course').' ('.format_file_size($quota_bytes).')', $quotaPercentage];

$used_quota_bytes = $quota_bytes;

if (!empty($session_list)) {
    foreach ($session_list as $session_data) {
        $quotaPercentage = 0;
        $quota_bytes = DocumentManager::documents_total_space($courseId, null, $session_data['id']);
        if (!empty($quota_bytes)) {
            $quotaPercentage = round($quota_bytes / $total_quota_bytes, 2) * 100;
        }
        if ($sessionId == $session_data['id']) {
            $session_data['name'] = $session_data['name'].' * ';
        }
        $used_quota_bytes += $quota_bytes;
        $session[] = [
            addslashes(get_lang('Session').': '.$session_data['name']).' ('.format_file_size($quota_bytes).')',
            $quotaPercentage,
        ];
    }
}
$group_list = GroupManager::get_groups();

if (!empty($group_list)) {
    foreach ($group_list as $group_data) {
        $quotaPercentage = 0;
        $my_group_id = $group_data['id'];
        $quota_bytes = DocumentManager::documents_total_space($courseId, $my_group_id, 0);
        if (!empty($quota_bytes)) {
            $quotaPercentage = round($quota_bytes / $total_quota_bytes, 2) * 100;
        }
        if ($groupId == $my_group_id) {
            $group_data['name'] = $group_data['name'].' * ';
        }
        $used_quota_bytes += $quota_bytes;
        $session[] = [
            addslashes(get_lang('Group').': '.$group_data['name']).' ('.format_file_size($quota_bytes).')',
            $quotaPercentage,
        ];
    }
}
// Showing weight of documents uploaded by user
$document_list = DocumentManager::getAllDocumentData(api_get_course_info());
if (!empty($document_list)) {
    foreach ($document_list as $document_data) {
        if ($document_data['creator_id'] == api_get_user_id() && $document_data['filetype'] === 'file') {
            $quota_bytes += $document_data['size'];
        }
    }
    if ($quota_bytes != 0) {
        $quotaPercentage = round($quota_bytes / $total_quota_bytes, 2) * 100;
    }

    $session[] = [
        addslashes(get_lang('Teacher').': '.$user_name).' ('.format_file_size($quota_bytes).')',
        $quotaPercentage,
    ];
    //if a sesson is active
    if ($sessionId != 0) {
        if (!empty($course_list)) {
            $total_courses_quota = 0;
            $total_quota_bytes = 0;
            if (is_array($course_list) && !empty($course_list)) {
                foreach ($course_list as $course_data) {
                    $total_quota_bytes += DocumentManager::get_course_quota($course_data['id']);
                }
            }
            if ($quota_bytes != 0) {
                $quotaPercentage = round($quota_bytes / $total_quota_bytes, 2) * 100;
            }
        }
        $session[] = [addslashes(sprintf(get_lang('TeacherXInSession'), $user_name)), $quotaPercentage];
    }
}

$quotaPercentage = round(($total_quota_bytes - $used_quota_bytes) / $total_quota_bytes, 2) * 100;
$session[] = [
    addslashes(get_lang('ShowCourseQuotaUse')).' ('.format_file_size(
        $total_quota_bytes - $used_quota_bytes
    ).') ',
    $quotaPercentage,
];
$quota_data = json_encode($session);

$htmlHeadXtra[] = "<script>
$(function() {
    var data = ".$quota_data.";
    var plot1 = jQuery.jqplot('chart1', [data], {
        seriesDefaults: {
            // Make this a pie chart
            renderer: jQuery.jqplot.PieRenderer,
            rendererOptions: {
                // Put data labels on the pie slices.
                // By default, labels show the percentage of the slice.
                showDataLabels: true
            }
        },
        legend: { show:true, location: 'e' }
    });
});
</script>";

$tpl = new Template($tool_name);
$content = Display::page_subheader(get_lang('ShowCourseQuotaUse')).'<div id="chart1"></div>';
$tpl->assign('content', $content);
$tpl->display_one_col_template();

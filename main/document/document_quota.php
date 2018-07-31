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

$course_code = api_get_course_id();
$course_id = api_get_course_int_id();
$session_id = api_get_session_id();
$group_id = api_get_group_id();
$user_id = api_get_user_id();
$user_info = api_get_user_info($user_id);

$session = [];
$user_name = $user_info['complete_name'];

$course_list = SessionManager::get_course_list_by_session_id($session_id);
$session_list = SessionManager::get_session_by_course($course_id);
$total_quota_bytes = DocumentManager::get_course_quota();
$quota_bytes = DocumentManager::documents_total_space($course_id, 0, 0);
$quota_percentage = round($quota_bytes / $total_quota_bytes, 2) * 100;

$session[] = [get_lang('Course').' ('.format_file_size($quota_bytes).')', $quota_percentage];

$used_quota_bytes = $quota_bytes;

if (!empty($session_list)) {
    foreach ($session_list as $session_data) {
        $quota_percentage = 0;
        $quota_bytes = DocumentManager::documents_total_space($course_id, null, $session_data['id']);
        if (!empty($quota_bytes)) {
            $quota_percentage = round($quota_bytes / $total_quota_bytes, 2) * 100;
        }
        if ($session_id == $session_data['id']) {
            $session_data['name'] = $session_data['name'].' * ';
        }
        $used_quota_bytes += $quota_bytes;
        $session[] = [
            addslashes(get_lang('Session').': '.$session_data['name']).' ('.format_file_size($quota_bytes).')',
            $quota_percentage,
        ];
    }
}
$group_list = GroupManager::get_groups();

if (!empty($group_list)) {
    foreach ($group_list as $group_data) {
        $quota_percentage = 0;
        $my_group_id = $group_data['id'];
        $quota_bytes = DocumentManager::documents_total_space($course_id, $my_group_id, 0);
        if (!empty($quota_bytes)) {
            $quota_percentage = round($quota_bytes / $total_quota_bytes, 2) * 100;
        }
        if ($group_id == $my_group_id) {
            $group_data['name'] = $group_data['name'].' * ';
        }
        $used_quota_bytes += $quota_bytes;
        $session[] = [addslashes(get_lang('Group').': '.$group_data['name']).' ('.format_file_size($quota_bytes).')', $quota_percentage];
    }
}
// Showing weight of documents uploaded by user
$document_list = DocumentManager::getAllDocumentData($_course);
if (!empty($document_list)) {
    foreach ($document_list as $document_data) {
        if ($document_data['insert_user_id'] == api_get_user_id() && $document_data['filetype'] == 'file') {
            $quota_bytes += $document_data['size'];
        }
    }
    if ($quota_bytes != 0) {
        $quota_percentage = round($quota_bytes / $total_quota_bytes, 2) * 100;
    }

    $session[] = [addslashes(get_lang('Teacher').': '.$user_name).' ('.format_file_size($quota_bytes).')', $quota_percentage];
    //if a sesson is active
    if ($session_id != 0) {
        if (!empty($course_list)) {
            $total_courses_quota = 0;
            $total_quota_bytes = 0;
            if (is_array($course_list) && !empty($course_list)) {
                foreach ($course_list as $course_data) {
                    $total_quota_bytes += DocumentManager::get_course_quota($course_data['id']);
                }
            }
            if ($quota_bytes != 0) {
                $quota_percentage = round($quota_bytes / $total_quota_bytes, 2) * 100;
            }
        }
        $session[] = [addslashes(sprintf(get_lang('TeacherXInSession'), $user_name)), $quota_percentage];
    }
}

$quota_percentage = round(($total_quota_bytes - $used_quota_bytes) / $total_quota_bytes, 2) * 100;
$session[] = [addslashes(get_lang('ShowCourseQuotaUse')).' ('.format_file_size($total_quota_bytes - $used_quota_bytes).') ', $quota_percentage];
$quota_data = json_encode($session);

$htmlHeadXtra[] = "
<script>
$(document).ready(function(){
  var data = ".$quota_data.";
  var plot1 = jQuery.jqplot ('chart1', [data], {
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
    }
  );
});
</script>";

$tpl = new Template($tool_name);
$content = Display::page_subheader(get_lang('ShowCourseQuotaUse')).'<div id="chart1"></div>';
$tpl->assign('content', $content);
$tpl->display_one_col_template();

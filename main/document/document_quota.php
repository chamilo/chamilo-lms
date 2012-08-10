<?php

$language_file = array('document');

require_once '../inc/global.inc.php';

require_once api_get_path(LIBRARY_PATH).'fileDisplay.lib.php';

$current_course_tool = TOOL_DOCUMENT;
$this_section = SECTION_COURSES;

$tool_name = get_lang('DocumentQuota');

$interbreadcrumb[] = array('url' => 'document.php', 'name' => get_lang('Documents'));

$htmlHeadXtra[] = api_get_js('jqplot/jquery.jqplot.min.js');
$htmlHeadXtra[] = api_get_js('jqplot/plugins/jqplot.pieRenderer.min.js');
$htmlHeadXtra[] = api_get_js('jqplot/plugins/jqplot.donutRenderer.min.js');
$htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/jqplot/jquery.jqplot.min.css');

$course_code    = api_get_course_id();
$course_id      = api_get_course_int_id();
$session_id     = api_get_session_id();
$group_id   = api_get_group_id();

$session = array();
$session_list = SessionManager::get_session_by_course($course_code);

$total_quota_bytes = DocumentManager::get_course_quota();

$quota_bytes = DocumentManager::documents_total_space($course_id, 0 , 0);

$quota_percentage = round($quota_bytes/$total_quota_bytes, 2)*100;

$session[] = array(get_lang('Course').' ('.format_file_size($quota_bytes).')', $quota_percentage);

$used_quota_bytes = $quota_bytes;
if (!empty($session_list)) {
    foreach ($session_list as $session_data) {
        $quota_percentage = 0;
        $quota_bytes = intval(DocumentManager::documents_total_space($course_id, null, $session_data['id']));
        if (!empty($quota_bytes))  {
            $quota_percentage = round($quota_bytes/$total_quota_bytes, 2)*100;
        }
        if ($session_id == $session_data['id']) {
            $session_data['name'] = $session_data['name'] . ' * ';
        }
        $used_quota_bytes += $quota_bytes;        
        $session[] = array(addslashes(get_lang('Session').': '.$session_data['name']).' ('.format_file_size($quota_bytes).')', $quota_percentage);
    }
}

$group_list = GroupManager::get_groups();

if (!empty($group_list)) {
    foreach ($group_list as $group_data) {
        $quota_percentage = 0;
        $my_group_id = $group_data['id'];
        $quota_bytes = intval(DocumentManager::documents_total_space($course_id, $my_group_id, 0));        
        if (!empty($quota_bytes))  {
            $quota_percentage = round($quota_bytes/$total_quota_bytes, 2)*100;
        }
        if ($group_id == $my_group_id) {
            $group_data['name'] = $group_data['name'] . ' * ';
        }
        $used_quota_bytes += $quota_bytes;        
        $session[] = array(addslashes(get_lang('Group').': '.$group_data['name']).' ('.format_file_size($quota_bytes).')', $quota_percentage);
    }
}
$quota_percentage = round(($total_quota_bytes - $used_quota_bytes)/$total_quota_bytes, 2)*100;

$session[] = array(addslashes(get_lang('ShowCourseQuotaUse')).' ('.format_file_size($total_quota_bytes - $used_quota_bytes).') ', $quota_percentage);

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

$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();

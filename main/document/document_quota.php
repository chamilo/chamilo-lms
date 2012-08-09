<?php

$language_file = array('document');

require_once '../inc/global.inc.php';
$current_course_tool = TOOL_DOCUMENT;
$this_section = SECTION_COURSES;

$tool_name = get_lang('DocumentQuota');

$interbreadcrumb[] = array('url' => 'document.php', 'name' => get_lang('Documents'));

$htmlHeadXtra[] = api_get_js('jqplot/jquery.jqplot.min.js');
$htmlHeadXtra[] = api_get_js('jqplot/plugins/jqplot.pieRenderer.min.js');
$htmlHeadXtra[] = api_get_js('jqplot/plugins/jqplot.donutRenderer.min.js');
$htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/jqplot/jquery.jqplot.min.css');

$course_id = api_get_course_id();
$session_id = api_get_session_id();

$session = array();
$session_list = SessionManager::get_session_by_course($course_id);
$total_quota = DocumentManager::get_course_quota();

$only_base_quota = DocumentManager::documents_total_space(api_get_course_int_id(), 0, 0);

$course_quota = round($only_base_quota/$total_quota, 2)*100;
$session[] = array(get_lang('Course'), $course_quota);

$used_quota = $course_quota;
if (!empty($session_list)) {
    foreach ($session_list as $session_data) {
        $quota = intval(DocumentManager::documents_total_space(api_get_course_int_id(), null, $session_data['id']));
        if (!empty($quota))  {
            $quota = round($quota/$total_quota, 2)*100;
        }
        if ($session_id == $session_data['id']) {
            $session_data['name'] = $session_data['name'] . ' * ';
        }
        $used_quota += $quota;
        $session[] = array(addslashes($session_data['name']), $quota);
    }
}

$session[] = array(addslashes(get_lang('ShowCourseQuotaUse')), 100 - $used_quota);

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

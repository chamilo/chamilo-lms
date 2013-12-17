<?php
/* For licensing terms, see /license.txt */
/**
 * Special report for corporate users
 * @package chamilo.reporting
 */
/**
 * Code
 */
$language_file = array('admin', 'gradebook', 'tracking');

$cidReset = true;
require_once '../inc/global.inc.php';

if (!(api_is_platform_admin(false, true))) {
    api_not_allowed();
}

$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('MySpace'));
$tool_name = get_lang('Report');

$this_section = SECTION_TRACKING;

$htmlHeadXtra[] = api_get_jqgrid_js();

//jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_user_course_report';

$extra_fields = UserManager::get_extra_fields(0, 100, null, null, true, true);

//The order is important you need to check the the $column variable in the model.ajax.php file
$columns = array(
    get_lang('Course'),
    get_lang('User'),
    get_lang('ManHours'),
    get_lang('CertificateGenerated'),
    get_lang('Approved'),
    get_lang('CourseAdvance')
);

//Column config
$column_model = array(
    array('name'=>'course',         'index'=>'title',       'width'=>'180', 'align'=>'left', 'wrap_cell' => 'true'),
    array('name'=>'user',           'index'=>'user',        'width'=>'100', 'align'=>'left','sortable'=>'false', 'wrap_cell' => 'true'),
    array('name'=>'time',           'index'=>'time',        'width'=>'50',  'align'=>'left','sortable'=>'false'),
    array('name'=>'certificate',    'index'=>'certificate', 'width'=>'50',  'align'=>'left','sortable'=>'false'),
    array('name'=>'progress_100',   'index'=>'progress_100',       'width'=>'50',  'align'=>'left','sortable'=>'false'),
    array('name'=>'progress',       'index'=>'progress',    'width'=>'50',  'align'=>'left','sortable'=>'false')
);

if (!empty($extra_fields)) {
    foreach ($extra_fields as $extra) {
        $col = array(
            'name' => $extra['1'],
            'index' => $extra['1'],
            'width' => '120',
            'sortable' =>'false',
            'wrap_cell' => 'true'
        );
        $column_model[] = $col;

        $columns[] = $extra['3'];
    }
}

// Autowidth
$extra_params['autowidth'] = 'true';
// height auto
$extra_params['height'] = 'auto';

$htmlHeadXtra[] = '<script>
$(function() {
    '.Display::grid_js('user_course_report',  $url, $columns, $column_model, $extra_params, array(), null, true).'
    jQuery("#user_course_report").jqGrid("navGrid","#user_course_report_pager",{view:false, edit:false, add:false, del:false, search:false, excel:true});
    jQuery("#user_course_report").jqGrid("navButtonAdd","#user_course_report_pager",{
           caption:"",
           onClickButton : function () {
               jQuery("#user_course_report").jqGrid("excelExport",{"url":"'.$url.'&export_format=xls"});
           }
    });
});
</script>';
$content = Display::grid_html('user_course_report');

$tpl = new Template($tool_name);
$tpl->assign('content', $content);
$tpl->display_one_col_template();

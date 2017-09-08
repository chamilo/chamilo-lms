<?php
/* For licensing terms, see /license.txt */
/**
 * Special report for corporate users
 * @package chamilo.reporting
 */
/**
 * Code
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$userNotAllowed = !api_is_student_boss() && !api_is_platform_admin(false, true);

if ($userNotAllowed) {
    api_not_allowed(true);
}

$interbreadcrumb[] = array('url' => api_is_student_boss() ? '#' : 'index.php', 'name' => get_lang('MySpace'));
$tool_name = get_lang('Report');

$this_section = SECTION_TRACKING;

$htmlHeadXtra[] = api_get_jqgrid_js();
$sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : -1;

//jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_user_course_report&session_id='.$sessionId;

$extra_fields = UserManager::get_extra_fields(0, 100, null, null, true, true);

//The order is important you need to check the the $column variable in the model.ajax.php file
$columns = array(
    get_lang('Course'),
    get_lang('User'),
    get_lang('Email'),
    get_lang('ManHours'),
    get_lang('CertificateGenerated'),
    get_lang('Approved'),
    get_lang('CourseAdvance')
);

//Column config
$column_model = array(
    array('name'=>'course', 'index'=>'title', 'width'=>'180', 'align'=>'left', 'wrap_cell' => 'true'),
    array('name'=>'user', 'index'=>'user', 'width'=>'100', 'align'=>'left', 'sortable'=>'false', 'wrap_cell' => 'true'),
    array('name'=>'email', 'index'=>'email', 'width'=>'100', 'align'=>'left', 'sortable'=>'false', 'wrap_cell' => 'true'),
    array('name'=>'time', 'index'=>'time', 'width'=>'50', 'align'=>'left', 'sortable'=>'false'),
    array('name'=>'certificate', 'index'=>'certificate', 'width'=>'50', 'align'=>'left', 'sortable'=>'false'),
    array('name'=>'progress_100', 'index'=>'progress_100', 'width'=>'50', 'align'=>'left', 'sortable'=>'false'),
    array('name'=>'progress', 'index'=>'progress', 'width'=>'50', 'align'=>'left', 'sortable'=>'false')
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

if (api_is_student_boss()) {
    $column_model[] = array('name'=>'group', 'index'=>'group', 'width'=>'50', 'align'=>'left', 'sortable'=>'false');
    $columns[] = get_lang('Group');
}

// Autowidth
$extra_params['autowidth'] = 'true';
// height auto
$extra_params['height'] = 'auto';

$htmlHeadXtra[] = '<script>
$(function() {
    '.Display::grid_js('user_course_report', $url, $columns, $column_model, $extra_params, array(), null, true).'
    jQuery("#user_course_report").jqGrid("navGrid","#user_course_report_pager",{
        view:false,
        edit:false,
        add:false,
        del:false,
        search:false,
        excel:true
    });

    jQuery("#user_course_report").jqGrid("navButtonAdd","#user_course_report_pager", {
       caption:"",
       onClickButton : function () {
           jQuery("#user_course_report").jqGrid("excelExport",{"url":"'.$url.'&export_format=xls"});
       }
    });
});
</script>';

$actions = null;

if (api_is_student_boss()) {
    $actions .= Display::url(
        Display::return_icon('stats.png', get_lang('MyStats'), '', ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH)."auth/my_progress.php"
    );
    $actions .= Display::url(
        Display::return_icon('user.png', get_lang('Students'), array(), ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH)."mySpace/student.php"
    );
    $actions .= Display::url(
        Display::return_icon("statistics.png", get_lang("CompanyReport"), array(), ICON_SIZE_MEDIUM),
        "#"
    );
    $actions .= Display::url(
        Display::return_icon(
            "certificate_list.png",
            get_lang("GradebookSeeListOfStudentsCertificates"),
            [],
            ICON_SIZE_MEDIUM
        ),
        api_get_path(WEB_CODE_PATH)."gradebook/certificate_report.php"
    );
}

$content = '<div class="actions">';

if (!empty($actions)) {
    $content .= $actions;
}
$content .= Display::url(
    get_lang("CompanyReportResumed"),
    api_get_path(WEB_CODE_PATH)."mySpace/company_reports_resumed.php",
    array(
        'class' => 'btn btn-success'
    )
);
$content .= '</div>';
$content .= '<h1 class="page-header">'.get_lang('CompanyReport').'</h1>';
$content .= Display::grid_html('user_course_report');

$tpl = new Template($tool_name);
$tpl->assign('content', $content);
$tpl->display_one_col_template();

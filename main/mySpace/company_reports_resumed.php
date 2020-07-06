<?php
/* For licensing terms, see /license.txt */
/**
 * Special report for corporate users.
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$userNotAllowed = !api_is_student_boss() && !api_is_platform_admin(false, true);

if ($userNotAllowed) {
    api_not_allowed(true);
}

$interbreadcrumb[] = ['url' => api_is_student_boss() ? '#' : 'index.php', 'name' => get_lang('MySpace')];

$tool_name = get_lang('Report');
$this_section = SECTION_TRACKING;
$htmlHeadXtra[] = api_get_jqgrid_js();
$sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : -1;

// jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_user_course_report_resumed&session_id='.$sessionId;

$extra_fields = UserManager::get_extra_fields(0, 100, null, null, true, true);

// The order is important you need to check the the $column variable in the model.ajax.php file.
$columns = [
    get_lang('Company'),
    get_lang('TrainingHoursAccumulated'),
    get_lang('CountOfSubscriptions'),
    get_lang('CountOfUsers'),
    get_lang('AverageHoursPerStudent'),
    get_lang('CountCertificates'),
];

// Column config.
$column_model = [
    ['name' => 'extra_ruc', 'index' => 'extra_ruc', 'width' => '100', 'align' => 'left', 'sortable' => 'false', 'wrap_cell' => 'true'],
    ['name' => 'training_hours', 'index' => 'training_hours', 'width' => '100', 'align' => 'left'],
    ['name' => 'count_users', 'index' => 'count_users', 'width' => '100', 'align' => 'left', 'sortable' => 'false'],
    ['name' => 'count_users_registered', 'index' => 'count_users_registered', 'width' => '100', 'align' => 'left', 'sortable' => 'false'],
    ['name' => 'average_hours_per_user', 'index' => 'average_hours_per_user', 'width' => '100', 'align' => 'left', 'sortable' => 'false'],
    ['name' => 'count_certificates', 'index' => 'count_certificates', 'width' => '100', 'align' => 'left', 'sortable' => 'false'],
];

if (!empty($extra_fields)) {
    foreach ($extra_fields as $extra) {
        if ($extra['1'] == 'ruc') {
            continue;
        }
        $col = [
            'name' => $extra['1'],
            'index' => $extra['1'],
            'width' => '120',
            'sortable' => 'false',
            'wrap_cell' => 'true',
        ];
        $column_model[] = $col;

        $columns[] = $extra['3'];
    }
}

// Autowidth.
$extra_params['autowidth'] = 'true';
//height auto
$extra_params['height'] = 'auto';

$htmlHeadXtra[] = '<script>
$(function() {
    '.Display::grid_js('user_course_report', $url, $columns, $column_model, $extra_params, [], null, true).'
    jQuery("#user_course_report").jqGrid("navGrid","#user_course_report_pager",{view:false, edit:false, add:false, del:false, search:false, excel:true});
    jQuery("#user_course_report").jqGrid("navButtonAdd","#user_course_report_pager",{
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
        Display::return_icon('statistics.png', get_lang('MyStats'), '', ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH)."auth/my_progress.php"
    );
    $actions .= Display::url(
        Display::return_icon('user.png', get_lang('Students'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH)."mySpace/student.php"
    );
    $actions .= Display::url(
        Display::return_icon("statistics.png", get_lang("CompanyReport"), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH)."mySpace/company_reports.php"
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

if (!api_is_student_boss()) {
    $content .= Display::url(
        get_lang("CompanyReport"),
        api_get_path(WEB_CODE_PATH)."mySpace/company_reports.php",
        [
            'class' => 'btn btn-success',
        ]
    );
}

$content .= '</div>';
$content .= '<h1 class="page-header">'.get_lang('CompanyReportResumed').'</h1>';
$content .= Display::grid_html('user_course_report');

$tpl = new Template($tool_name);
$tpl->assign('content', $content);
$tpl->display_one_col_template();

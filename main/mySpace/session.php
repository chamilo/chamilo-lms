<?php
/* For licensing terms, see /license.txt */

/**
 * Sessions reporting
 * @package chamilo.reporting
 */

ob_start();
$cidReset = true;
require_once '../inc/global.inc.php';

api_block_anonymous_users();

$this_section = SECTION_TRACKING;

api_block_anonymous_users();
$htmlHeadXtra[] = api_get_jqgrid_js();
$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
Display::display_header(get_lang('Sessions'));

$export_csv = false;

if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $export_csv = true;
}

/*	MAIN CODE */

if (isset($_GET['id_coach']) && $_GET['id_coach'] != '') {
    $id_coach = intval($_GET['id_coach']);
} else {
    $id_coach = api_get_user_id();
}

if (api_is_drh() || api_is_session_admin() || api_is_platform_admin()) {

    $a_sessions = SessionManager::get_sessions_followed_by_drh(api_get_user_id());

    if (!api_is_session_admin()) {
        $menu_items[] = Display::url(
            Display::return_icon('stats.png', get_lang('MyStats'), '', ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH) . "auth/my_progress.php"
        );
        $menu_items[] = Display::url(
            Display::return_icon('user.png', get_lang('Students'), array(), ICON_SIZE_MEDIUM),
            "index.php?view=drh_students&amp;display=yourstudents"
        );
        $menu_items[] = Display::url(
            Display::return_icon('teacher.png', get_lang('Trainers'), array(), ICON_SIZE_MEDIUM),
            'teachers.php'
        );
        $menu_items[] = Display::url(
            Display::return_icon('course.png', get_lang('Courses'), array(), ICON_SIZE_MEDIUM),
            'course.php'
        );
        $menu_items[] = Display::url(
            Display::return_icon('session_na.png', get_lang('Sessions'), array(), ICON_SIZE_MEDIUM),
            '#'
        );
    }

    $menu_items[] = Display::url(
        Display::return_icon('works.png', get_lang('WorksReport'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH) . 'mySpace/works_in_session_report.php'
    );
    $menu_items[] = Display::url(
        Display::return_icon('clock.png', get_lang('TeacherTimeReportBySession'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH) . 'admin/teachers_time_by_session_report.php'
    );

    $actionsLeft = '';
    $nb_menu_items = count($menu_items);
    if ($nb_menu_items > 1) {
        foreach ($menu_items as $key => $item) {
            $actionsLeft .= $item;
        }
    }
    $actionsRight = '';
    if (count($a_sessions) > 0) {
        $actionsRight = Display::url(
            Display::return_icon('printer.png', get_lang('Print'), array(), 32),
            'javascript: void(0);',
            array('onclick' => 'javascript: window.print();')
        );
        $actionsRight .= Display::url(
            Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), array(), 32),
            api_get_self() . '?export=csv'
        );
    }

    $toolbar = Display::toolbarAction(
        'toolbar-session',
        array($actionsLeft, $actionsRight)
    );
    echo $toolbar;

    echo Display::page_header(get_lang('YourSessionsList'));

} elseif (api_is_teacher()) {
    $actionsRight = Display::url(
        Display::return_icon('clock.png', get_lang('TeacherTimeReportBySession'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH) . 'admin/teachers_time_by_session_report.php'
    );

    $toolbar = Display::toolbarAction(
        'toolbar-session',
        array('', $actionsRight)
    );
    echo $toolbar;

    echo Display::page_header(get_lang('YourSessionsList'));
} else {
    $a_sessions = Tracking::get_sessions_coached_by_user($id_coach);
}

$form = new FormValidator(
    'search_course',
    'get',
    api_get_path(WEB_CODE_PATH).'mySpace/session.php'
);
$form->addElement('text', 'keyword', get_lang('Keyword'));
$form->addButtonSearch(get_lang('Search'));
$keyword = '';
if ($form->validate()) {
    $keyword = $form->getSubmitValue('keyword');
}
$form->setDefaults(array('keyword' => $keyword));

$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_sessions_tracking&keyword='.Security::remove_XSS($keyword);

$columns = array(
    get_lang('Title'),
    get_lang('Date'),
    get_lang('NbCoursesPerSession'),
    get_lang('NbStudentPerSession'),
    get_lang('Details')
);

// Column config
$columnModel = array(
    array('name'=>'name', 'index'=>'name', 'width'=>'255', 'align'=>'left'),
    array('name'=>'date', 'index'=>'date', 'width'=>'150', 'align'=>'left','sortable'=>'false'),
    array('name'=>'course_per_session', 'index'=>'course_per_session', 'width'=>'150','sortable'=>'false'),
    array('name'=>'student_per_session', 'index'=>'student_per_session', 'width'=>'100','sortable'=>'false'),
    array('name'=>'details', 'index'=>'details', 'width'=>'100','sortable'=>'false')
);

$extraParams = array(
    'autowidth' => 'true',
    'height' => 'auto'
);

$js = '<script>
    $(function() {
        '.Display::grid_js(
        'session_tracking',
        $url,
        $columns,
        $columnModel,
        $extraParams,
        array(),
        null,
        true
    ).'
    });
</script>';

echo $js;
$form->display();

echo Display::grid_html('session_tracking');

Display::display_footer();

<?php
/* For licensing terms, see /license.txt */

/**
 * Script
 * @package chamilo.gradebook
 */
require_once __DIR__.'/../inc/global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/fe/exportgradebook.php';

$current_course_tool = TOOL_GRADEBOOK;

api_protect_course_script(true);

api_block_anonymous_users();
$isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(
    api_get_user_id(),
    api_get_course_info()
);

if (!$isDrhOfCourse) {
    GradebookUtils::block_students();
}

if (isset ($_POST['submit']) && isset ($_POST['keyword'])) {
    header('Location: '.api_get_self().'?selectcat='.intval($_GET['selectcat']).'&search='.Security::remove_XSS($_POST['keyword']));
    exit;
}

$interbreadcrumb[] = array(
    'url' => $_SESSION['gradebook_dest'].'?selectcat=1',
    'name' => get_lang('ToolGradebook')
);

$showeval = isset($_POST['showeval']) ? '1' : '0';
$showlink = isset($_POST['showlink']) ? '1' : '0';
if (($showlink == '0') && ($showeval == '0')) {
    $showlink = '1';
    $showeval = '1';
}

$cat = Category::load($_REQUEST['selectcat']);

if (isset($_GET['userid'])) {
    $userid = Security::remove_XSS($_GET['userid']);
} else {
    $userid = '';
}

if ($showeval) {
    $alleval = $cat[0]->get_evaluations($userid, true);
} else {
    $alleval = null;
}

if ($showlink) {
    $alllinks = $cat[0]->get_links($userid, true);
} else {
    $alllinks = null;
}

if (isset($export_flatview_form) && (!$file_type == 'pdf')) {
    Display::addFlash(Display::return_message($export_flatview_form->toHtml(), 'normal', false));
}

if (isset($_GET['selectcat'])) {
    $category_id = (int) $_GET['selectcat'];
} else {
    $category_id = '';
}

$simple_search_form = new UserForm(
    UserForm :: TYPE_SIMPLE_SEARCH,
    null,
    'simple_search_form',
    null,
    api_get_self().'?selectcat='.$category_id
);
$values = $simple_search_form->exportValues();

$keyword = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $keyword = Security::remove_XSS($_GET['search']);
}
if ($simple_search_form->validate() && (empty($keyword))) {
    $keyword = $values['keyword'];
}

if (!empty($keyword)) {
    $users = GradebookUtils::find_students($keyword);
} else {
    if (isset($alleval) && isset($alllinks)) {
        $users = GradebookUtils::get_all_users($alleval, $alllinks);
    } else {
        $users = null;
    }
}
$offset = isset($_GET['offset']) ? $_GET['offset'] : '0';

$addparams = array('selectcat' => $cat[0]->get_id());
if (isset($_GET['search'])) {
    $addparams['search'] = $keyword;
}

// Main course category
$mainCourseCategory = Category::load(
    null,
    null,
    api_get_course_id(),
    null,
    null,
    api_get_session_id()
);

$flatviewtable = new FlatViewTable(
    $cat[0],
    $users,
    $alleval,
    $alllinks,
    true,
    $offset,
    $addparams,
    $mainCourseCategory[0]
);

$flatviewtable->setAutoFill(false);

$parameters = array('selectcat' => intval($_GET['selectcat']));
$flatviewtable->set_additional_parameters($parameters);

$params = array();
if (isset($_GET['export_pdf']) && $_GET['export_pdf'] == 'category') {
    $params['only_total_category'] = true;
    $params['join_firstname_lastname'] = true;
    $params['show_official_code'] = true;
    $params['export_pdf'] = true;
    if ($cat[0]->is_locked() == true || api_is_platform_admin()) {
        Display :: set_header(null, false, false);
        GradebookUtils::export_pdf_flatview(
            $flatviewtable,
            $cat,
            $users,
            $alleval,
            $alllinks,
            $params,
            $mainCourseCategory[0]
        );
    }
}

if (isset($_GET['exportpdf'])) {
    $interbreadcrumb[] = array(
        'url' => api_get_self().'?selectcat='.Security::remove_XSS($_GET['selectcat']).'&'.api_get_cidreq(),
        'name' => get_lang('FlatView')
    );

    $pageNum = isset($_GET['flatviewlist_page_nr']) ? intval($_GET['flatviewlist_page_nr']) : null;
    $perPage = isset($_GET['flatviewlist_per_page']) ? intval($_GET['flatviewlist_per_page']) : null;
    $url = api_get_self().'?'.api_get_cidreq().'&'.http_build_query([
        'exportpdf' => '',
        'offset' => $offset,
        'selectcat' => intval($_GET['selectcat']),
        'flatviewlist_page_nr' => $pageNum,
        'flatviewlist_per_page' => $perPage
    ]);

    $export_pdf_form = new DataForm(
        DataForm::TYPE_EXPORT_PDF,
        'export_pdf_form',
        null,
        $url,
        '_blank',
        ''
    );

    if ($export_pdf_form->validate()) {
        $params = $export_pdf_form->exportValues();
        Display :: set_header(null, false, false);
        $params['join_firstname_lastname'] = true;
        $params['show_official_code'] = true;
        $params['export_pdf'] = true;
        $params['only_total_category'] = false;
        GradebookUtils::export_pdf_flatview(
            $flatviewtable,
            $cat,
            $users,
            $alleval,
            $alllinks,
            $params,
            $mainCourseCategory[0]
        );

    } else {
        Display :: display_header(get_lang('ExportPDF'));
    }
}

if (isset($_GET['print'])) {
    $printable_data = GradebookUtils::get_printable_data(
        $cat[0],
        $users,
        $alleval,
        $alllinks,
        $params,
        $mainCourseCategory[0]
    );
    echo print_table(
        $printable_data[1],
        $printable_data[0],
        get_lang('FlatView'),
        $cat[0]->get_name()
    );
    exit;
}

if (!empty($_GET['export_report']) &&
    $_GET['export_report'] == 'export_report'
) {
    if (api_is_platform_admin() || api_is_course_admin() || api_is_course_coach() || $isDrhOfCourse) {
        $user_id = null;

        if (empty($_SESSION['export_user_fields'])) {
            $_SESSION['export_user_fields'] = false;
        }
        if (!api_is_allowed_to_edit(false, false) and !api_is_course_tutor()) {
            $user_id = api_get_user_id();
        }
        $printable_data = GradebookUtils::get_printable_data(
            $cat[0],
            $users,
            $alleval,
            $alllinks,
            $params,
            $mainCourseCategory[0]
        );

        switch ($_GET['export_format']) {
            case 'xls':
                ob_start();
                $export = new GradeBookResult();
                $export->exportCompleteReportXLS($printable_data);
                $content = ob_get_contents();
                ob_end_clean();
                echo $content;
                break;
            case 'doc':
                ob_start();
                $export = new GradeBookResult();
                $export->exportCompleteReportDOC($printable_data);
                $content = ob_get_contents();
                ob_end_clean();
                echo $content;
                break;
            case 'csv':
            default:
                ob_start();
                $export = new GradeBookResult();
                $export->exportCompleteReportCSV($printable_data);
                $content = ob_get_contents();
                ob_end_clean();
                echo $content;
                exit;
                break;
        }
    } else {
        api_not_allowed(true);
    }
}

$this_section = SECTION_COURSES;

if (isset($_GET['exportpdf'])) {
    $export_pdf_form->display();
} else {
    Display :: display_header(get_lang('FlatView'));
}

if (isset($_GET['isStudentView']) && $_GET['isStudentView'] == 'false') {
    DisplayGradebook:: display_header_reduce_flatview(
        $cat[0],
        $showeval,
        $showlink,
        $simple_search_form
    );
    $flatviewtable->display();
} elseif (isset($_GET['selectcat']) && ($_SESSION['studentview'] == 'teacherview')) {

    DisplayGradebook:: display_header_reduce_flatview(
        $cat[0],
        $showeval,
        $showlink,
        $simple_search_form
    );

    // Table
    $flatviewtable->display();
    //@todo load images with jquery
    echo '<div id="contentArea" style="text-align: center;" >';
    $flatviewtable->display_graph_by_resource();
    echo '</div>';
}

Display :: display_footer();

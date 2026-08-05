<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
require_once __DIR__.'/lib/fe/exportgradebook.php';

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

$categoryId = isset($_REQUEST['selectcat']) ? (int) $_REQUEST['selectcat'] : 0;

if (isset($_POST['submit']) && isset($_POST['keyword'])) {
    $searchKeyword = trim(Security::remove_XSS((string) $_POST['keyword']));
    $searchParameters = [
        'selectcat' => $categoryId,
        'cid' => api_get_course_int_id(),
        'sid' => api_get_session_id(),
        'gid' => api_get_group_id(),
    ];

    if ('' !== $searchKeyword) {
        $searchParameters['search'] = $searchKeyword;
    }

    header('Location: '.api_get_self().'?'.http_build_query($searchParameters));
    exit;
}

$interbreadcrumb[] = [
    'url' => Category::getUrl().'selectcat=1',
    'name' => get_lang('Assessments'),
];

$showeval = isset($_POST['showeval']) ? '1' : '0';
$showlink = isset($_POST['showlink']) ? '1' : '0';
if ('0' == $showlink && '0' == $showeval) {
    $showlink = '1';
    $showeval = '1';
}

$cat = Category::load($categoryId);
$userId = isset($_GET['userid']) ? (int) $_GET['userid'] : 0;

$alleval = null;
if ($showeval) {
    $alleval = $cat[0]->get_evaluations($userId, true);
}

$alllinks = null;
if ($showlink) {
    $alllinks = $cat[0]->get_links($userId, true);
}

/*global $file_type;
if (isset($export_flatview_form) && 'pdf' === !$file_type) {
    Display::addFlash(
        Display::return_message(
            $export_flatview_form->toHtml(),
            'normal',
            false
        )
    );
}*/
$category_id = 0;
if (isset($_GET['selectcat'])) {
    $category_id = (int) $_GET['selectcat'];
}

$simple_search_form = new UserForm(
    UserForm::TYPE_SIMPLE_SEARCH,
    null,
    'simple_search_form',
    null,
    api_get_self().'?selectcat='.$category_id.'&'.api_get_cidreq()
);
$values = $simple_search_form->exportValues();

$keyword = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $keyword = trim(Security::remove_XSS((string) $_GET['search']));
}
if ($simple_search_form->validate() && empty($keyword)) {
    $keyword = $values['keyword'];
}

$users = null;
if (isset($alleval) && isset($alllinks)) {
    $users = GradebookUtils::get_all_users($alleval, $alllinks);
}

if ('' !== $keyword && is_array($users)) {
    $containsKeyword = static function (string $haystack, string $needle): bool {
        if (function_exists('mb_stripos')) {
            return false !== mb_stripos($haystack, $needle, 0, 'UTF-8');
        }

        return false !== stripos($haystack, $needle);
    };

    $users = array_values(
        array_filter(
            $users,
            static function (array $user) use ($containsKeyword, $keyword): bool {
                $username = isset($user[1]) ? (string) $user[1] : '';
                $lastName = isset($user[2]) ? (string) $user[2] : '';
                $firstName = isset($user[3]) ? (string) $user[3] : '';
                $officialCode = isset($user[4]) ? (string) $user[4] : '';
                $searchableText = implode(' ', [
                    $username,
                    $firstName,
                    $lastName,
                    $lastName,
                    $firstName,
                    $officialCode,
                ]);

                return $containsKeyword($searchableText, $keyword);
            }
        )
    );
}
$offset = isset($_GET['offset']) ? $_GET['offset'] : '0';

$addparams = ['selectcat' => $cat[0]->get_id()];
if (isset($_GET['search'])) {
    $addparams['search'] = $keyword;
}

$hasNoSearchResults = '' !== $keyword && empty($users);

// Main course category
$mainCourseCategory = Category::load(
    null,
    null,
    api_get_course_int_id(),
    null,
    null,
    api_get_session_id()
);

$flatViewTable = new FlatViewTable(
    $cat[0],
    $users,
    $alleval,
    $alllinks,
    true,
    $offset,
    $addparams,
    $mainCourseCategory[0]
);

$flatViewTable->setAutoFill(false);
$parameters = array_merge(
    $addparams,
    [
        'cid' => api_get_course_int_id(),
        'sid' => api_get_session_id(),
        'gid' => api_get_group_id(),
    ]
);
$flatViewTable->set_additional_parameters($parameters);

$params = [];
if (isset($_GET['export_pdf']) && 'category' == $_GET['export_pdf']) {
    $params['only_total_category'] = true;
    $params['join_firstname_lastname'] = true;
    $params['show_official_code'] = true;
    $params['export_pdf'] = true;
    if (true == $cat[0]->is_locked() || api_is_platform_admin()) {
        //Display::set_header(null, false, false);
        GradebookUtils::export_pdf_flatview(
            $flatViewTable,
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
    $interbreadcrumb[] = [
        'url' => api_get_self().'?selectcat='.$categoryId.'&'.api_get_cidreq(),
        'name' => get_lang('List View'),
    ];

    $pageNum = isset($_GET['flatviewlist_page_nr']) ? intval($_GET['flatviewlist_page_nr']) : null;
    $perPage = isset($_GET['flatviewlist_per_page']) ? intval($_GET['flatviewlist_per_page']) : null;
    $url = api_get_self().'?'.api_get_cidreq().'&'.http_build_query([
        'exportpdf' => '',
        'offset' => $offset,
        'selectcat' => $categoryId,
        'flatviewlist_page_nr' => $pageNum,
        'flatviewlist_per_page' => $perPage,
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
        //Display::set_header();
        $params['join_firstname_lastname'] = true;
        $params['show_official_code'] = true;
        $params['export_pdf'] = true;
        $params['only_total_category'] = false;
        GradebookUtils::export_pdf_flatview(
            $flatViewTable,
            $cat,
            $users,
            $alleval,
            $alllinks,
            $params,
            $mainCourseCategory[0]
        );
    } else {
        Display::display_header(get_lang('Export to PDF'));
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
        get_lang('List View'),
        $cat[0]->get_name()
    );
    exit;
}

if (!empty($_GET['export_report']) &&
    'export_report' === $_GET['export_report']
) {
    if (api_is_platform_admin() || api_is_course_admin() || api_is_session_general_coach() || $isDrhOfCourse) {
        $user_id = null;

        if (empty($_SESSION['export_user_fields'])) {
            $_SESSION['export_user_fields'] = false;
        }
        if (!api_is_allowed_to_edit(false, false) && !api_is_course_tutor()) {
            $user_id = api_get_user_id();
        }

        $params['show_official_code'] = true;
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
    Display::display_header(get_lang('List View'));
}

$studentView = api_is_student_view_active();
if (isset($_GET['isStudentView']) && 'false' === $_GET['isStudentView']) {
    DisplayGradebook::display_header_reduce_flatview(
    $cat[0],
    $showeval,
    $showlink,
    $simple_search_form
);
    if ($hasNoSearchResults) {
        echo Display::return_message(get_lang('No results found'), 'normal', false);
    } else {
        $flatViewTable->display();
    }
} elseif (isset($_GET['selectcat']) && (false === $studentView)) {
    DisplayGradebook::display_header_reduce_flatview(
        $cat[0],
        $showeval,
        $showlink,
        $simple_search_form
    );

    if ($hasNoSearchResults) {
        echo Display::return_message(get_lang('No results found'), 'normal', false);
    } else {
        $flatViewTable->display();
        //@todo load images with jquery
        echo '<div id="contentArea" style="text-align: center;" >';
        $flatViewTable->display_graph_by_resource();
        echo '</div>';
    }
}

Display::display_footer();

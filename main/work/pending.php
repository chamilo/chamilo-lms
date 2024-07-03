<?php

/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

// Only teachers.
if (false === api_is_teacher()) {
    api_not_allowed(true);
}

require_once 'work.lib.php';
$this_section = SECTION_COURSES;
$is_allowed_to_edit = api_is_allowed_to_edit() || api_is_coach();

$group_id = api_get_group_id();
$courseInfo = api_get_course_info();
$sessionId = api_get_session_id();
$htmlHeadXtra[] = api_get_jqgrid_js();
$userId = api_get_user_id();

/*$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
    'name' => get_lang('StudentPublications'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq().'&id='.$workId,
    'name' => $my_folder_data['title'],
];*/

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
$itemId = isset($_REQUEST['item_id']) ? (int) $_REQUEST['item_id'] : null;
$exportXls = isset($_REQUEST['export_xls']) && !empty($_REQUEST['export_xls']) ? (int) $_REQUEST['export_xls'] : 0;
$htmlHeadXtra[] = api_get_jquery_libraries_js(['jquery-upload']);

$plagiarismListJqgridColumn = [];
$plagiarismListJqgridLine = [];
/*$allowAntiPlagiarism = api_get_configuration_value('allow_compilatio_tool');
if ($allowAntiPlagiarism) {
    $plagiarismListJqgridColumn = ['Compilatio'];
    $plagiarismListJqgridLine = [
        [
            'name' => 'compilatio',
            'index' => 'compilatio',
            'width' => '40',
            'align' => 'left',
            'search' => 'false',
            'sortable' => 'false',
        ],
    ];
}*/

$orderName = api_is_western_name_order() ? 'firstname' : 'lastname';
$type = 'simple';
$columns = [
    get_lang('Course'),
    get_lang('WorkName'),
    get_lang('FullUserName'),
    get_lang('Title'),
    get_lang('Score'),
    get_lang('Date'),
    get_lang('Status'),
    get_lang('Corrector'),
    get_lang('CorrectionDate'),
    get_lang('UploadCorrection'),
];
$columns = array_merge($columns, $plagiarismListJqgridColumn);
$columns[] = get_lang('Actions');

$column_model = [
    [
        'name' => 'course',
        'index' => 'course',
        'width' => '30',
        'align' => 'left',
        'search' => 'false',
        'sortable' => 'false',
    ],
    [
        'name' => 'work_name',
        'index' => 'work_name',
        'width' => '30',
        'align' => 'left',
        'search' => 'false',
        'sortable' => 'false',
    ],
    [
        'name' => 'fullname',
        'index' => $orderName,
        'width' => '30',
        'align' => 'left',
        'search' => 'true',
        'sortable' => 'true',
    ],
    [
        'name' => 'title',
        'index' => 'title',
        'width' => '25',
        'align' => 'left',
        'search' => 'false',
        'wrap_cell' => 'true',
        'sortable' => 'false',
    ],
    [
        'name' => 'qualification',
        'index' => 'qualification',
        'width' => '15',
        'align' => 'center',
        'search' => 'true',
    ],
    [
        'name' => 'sent_date',
        'index' => 'sent_date',
        'width' => '25',
        'align' => 'left',
        'search' => 'true',
        'wrap_cell' => 'true',
    ],
    [
        'name' => 'qualificator_id',
        'index' => 'qualificator_id',
        'width' => '20',
        'align' => 'left',
        'search' => 'true',
    ],
    [
        'name' => 'qualificator_fullname',
        'index' => 'qualificator_fullname',
        'width' => '20',
        'align' => 'left',
        'search' => 'true',
    ],
    [
        'name' => 'date_of_qualification',
        'index' => 'date_of_qualification',
        'width' => '20',
        'align' => 'left',
        'search' => 'true',
    ],
    [
        'name' => 'correction',
        'index' => 'correction',
        'width' => '30',
        'align' => 'left',
        'search' => 'false',
        'sortable' => 'false',
        'title' => 'false',
    ],
];
$column_model = array_merge($column_model, $plagiarismListJqgridLine);
$column_model[] = [
    'name' => 'actions',
    'index' => 'actions',
    'width' => '25',
    'align' => 'left',
    'search' => 'false',
    'sortable' => 'false',
];

$extra_params = [
    'autowidth' => 'true',
    'height' => 'auto',
    'sortname' => 'sent_date',
    'sortorder' => 'desc',
    'sortable' => 'false',
    'multiselect' => 'false',
];

$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_work_pending_list&type='.$type;
$deleteUrl = null;
/*$workUrl = api_get_path(WEB_AJAX_PATH).'work.ajax.php?';
$deleteUrl = $workUrl.'&a=delete_student_work';
$showUrl = $workUrl.'&a=show_student_work';
$hideUrl = $workUrl.'&a=hide_student_work';*/
/*if ($allowAntiPlagiarism) {
    $extra_params['gridComplete'] = 'compilatioInit()';
}*/

$courses = CourseManager::get_courses_list_by_user_id($userId, false, false, false);
$content = '';
if (!empty($courses)) {
    $form = new FormValidator('pending', 'POST');
    $courses = array_column($courses, 'title', 'real_id');
    $selectCourse = $form->addSelect('course', get_lang('Course'), $courses, ['placeholder' => get_lang('All')]);
    $courseId = 0;
    if (isset($_REQUEST['course'])) {
        $courseId = (int) $_REQUEST['course'];
        $selectCourse->setSelected($courseId);
    }
    $status = [
        1 => get_lang('All'),
        2 => get_lang('NotRevised'),
        3 => get_lang('Revised'),
    ];
    $form->addSelect('status', get_lang('Status'), $status);
    $allWork = getAllWork(
        null,
        null,
        null,
        null,
        '',
        false,
        $courseId,
        0,
        true,
        false
    );
    $selectWork = $form->addSelect(
        'work_parent_ids',
        get_lang('Works'),
        [],
        ['placeholder' => get_lang('SelectAnOption'), 'id' => 'search-works', 'multiple' => true]
    );
    if (count($allWork) > 0) {
        foreach ($allWork as $work) {
            $selectWork->addOption(
                $work['title'],
                $work['id']
            );
        }
    }
    $form->addButtonSearch(get_lang('Search'), 'pendingSubmit');
    $content .= $form->returnForm();
    $tableWork = Display::grid_html('results');
    $content .= Display::panel($tableWork);

    if ($form->validate()) {
        $values = $form->getSubmitValues();
        $courseId = $values['course'] ?? 0;
        if (!empty($courseId)) {
            $url .= '&course='.(int) $courseId;
        }

        $status = $values['status'] ?? 0;
        if (!empty($status)) {
            $url .= '&status='.(int) $status;
        }
        if (!empty($values['work_parent_ids'])) {
            $url .= '&work_parent_ids='.Security::remove_XSS(implode(',', $values['work_parent_ids']));
        }
        if ($exportXls) {
            exportPendingWorksToExcel($values);
        }
    }
} else {
    $content .= Display::return_message(get_lang('NoCoursesForThisUser'), 'warning');
}

$htmlHeadXtra[] = '<script>
    $(function() {
        $("#export-xls").bind("click", function(e) {
            e.preventDefault();
            var input = $("<input>", {
                type: "hidden",
                name: "export_xls",
                value: "1"
            });
            $("#pending").append(input);
            $("#pending").submit();
        });
        $("#pending_pendingSubmit").bind("click", function(e) {
            e.preventDefault();
            if ($("input[name=\"export_xls\"]").length > 0) {
                $("input[name=\"export_xls\"]").remove();
            }
            $("#pending").submit();
        });
    });
</script>';

Display::display_header(get_lang('StudentPublications'));
?>
<script>
$(function() {
    <?php
    echo Display::grid_js('results', $url, $columns, $column_model, $extra_params);
    ?>

    $("#results").jqGrid(
        "navGrid",
        "#results_pager",
        { edit: false, add: false, search: false, del: false },
        { height:280, reloadAfterSubmit:false }, // edit options
        { height:280, reloadAfterSubmit:false }, // add options
        { reloadAfterSubmit:false, url: "<?php echo $deleteUrl; ?>" }, // del options
        { width:500 } // search options
    );

    $("select[name=\'course\']").bind('change', function () {
        $("#search-works").val(0);
        $("#pending_pendingSubmit").trigger("click");
        $("#pending_pendingSubmit").attr("disabled", true);
    });
});
</script>
<?php
$actions = '';
$actions .= Display::url(
    Display::return_icon('excel.png', get_lang('ExportAsXLS'), [], ICON_SIZE_MEDIUM),
    '#',
    ['id' => 'export-xls']
);

echo Display::div($actions, ['class' => 'actions']);
echo Display::page_header(get_lang('StudentPublicationToCorrect'));
echo Display::return_message(get_lang('StudentPublicationCorrectionWarning'), 'warning');
echo $content;

Display::display_footer();

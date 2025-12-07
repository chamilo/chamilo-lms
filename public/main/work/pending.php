<?php

/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

// Only teachers.
if (false === api_is_teacher()) {
    api_not_allowed(true);
}

$this_section = SECTION_COURSES;
$is_allowed_to_edit = api_is_allowed_to_edit() || api_is_coach();

$group_id = api_get_group_id();
$courseInfo = api_get_course_info();
$sessionId = api_get_session_id();
$userId = api_get_user_id();

/*$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
    'name' => get_lang('Assignments'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq().'&id='.$workId,
    'name' => $my_folder_data['title'],
];*/

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
$itemId = isset($_REQUEST['item_id']) ? (int) $_REQUEST['item_id'] : null;
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
    get_lang('Assignment name'),
    get_lang('Full name'),
    get_lang('Title'),
    get_lang('Score'),
    get_lang('Date'),
    get_lang('Status'),
    get_lang('Upload correction'),
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
    $form = new FormValidator('pending', 'GET');
    $courses = array_column($courses, 'title', 'real_id');
    $form->addSelect('course', get_lang('Course'), $courses, ['placeholder' => get_lang('All')]);
    $status = [
        1 => get_lang('All'),
        2 => get_lang('Not reviewed'),
        3 => get_lang('Reviewed'),
    ];
    $form->addSelect('status', get_lang('Status'), $status);
    $form->addButtonSearch(get_lang('Search'));
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
    }
} else {
    $content .= Display::return_message(get_lang("This user isn't subscribed in a course"), 'warning');
}

Display::display_header(get_lang('Assignments'));
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
});

</script>
<?php

/*$actionsLeft = null;
echo Display::toolbarAction('toolbar-worklist', [$actionsLeft]);*/
echo Display::page_header(get_lang("Student's assignments to be corrected"));
echo Display::return_message(get_lang('You will find below all the work that have been submitted by students in one of your course (it could be in the base course or in a course in a session). You can filter the list selecting a specific course or a work status.'), 'warning');
echo $content;

Display::display_footer();

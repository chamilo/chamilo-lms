<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$current_course_tool = TOOL_STUDENTPUBLICATION;

api_protect_course_script(true);
$this_section = SECTION_COURSES;

$workId = isset($_GET['id']) ? (int) ($_GET['id']) : null;

if (empty($workId)) {
    api_not_allowed(true);
}

$my_folder_data = get_work_data_by_id($workId);

if (empty($my_folder_data)) {
    api_not_allowed(true);
}

$work_data = get_work_assignment_by_id($workId);
$tool_name = get_lang('Assignments');
$group_id = api_get_group_id();
$courseInfo = api_get_course_info();

// not all users
if (1 == $courseInfo['show_score']) {
    api_not_allowed(true);
}

protectWork($courseInfo, $workId);

$htmlHeadXtra[] = api_get_jqgrid_js();

if (!empty($group_id)) {
    $group_properties = api_get_group_entity($group_id);
    $show_work = false;

    if (api_is_allowed_to_edit(false, true)) {
        $show_work = true;
    } else {
        // you are not a teacher
        $show_work = GroupManager::userHasAccess(
            api_get_user_id(),
            $group_properties,
            GroupManager::GROUP_TOOL_WORK
        );
    }

    if (!$show_work) {
        api_not_allowed();
    }

    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
        'name' => get_lang('Groups'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
        'name' => get_lang('Group area').' '.$group_properties->getName(),
    ];
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
    'name' => get_lang('Assignments'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work_list_others.php?'.api_get_cidreq().'&id='.$workId,
    'name' => $my_folder_data['title'],
];

Display::display_header(null);
$actions = '<a href="'.api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq().'>'.
    Display::return_icon('back.png', get_lang('Back to Assignments list'), '', ICON_SIZE_MEDIUM).'</a>';
echo Display::toolbarAction('toolbar', [$actions]);
if (!empty($my_folder_data['description'])) {
    echo '<p><div><strong>'.get_lang('Description').':</strong><p>'.
        Security::remove_XSS($my_folder_data['description']).'</p></div></p>';
}

$check_qualification = (int) ($my_folder_data['qualification']);

if (!empty($work_data['enable_qualification']) && !empty($check_qualification)) {
    $type = 'simple';
    $columns = [
        get_lang('Type'),
        get_lang('First name'),
        get_lang('Last name'),
        get_lang('Title'),
        get_lang('Score'),
        get_lang('Date'),
        get_lang('Status'),
        get_lang('Detail'),
    ];
    $column_model = [
        [
            'name' => 'type',
            'index' => 'file',
            'width' => '12',
            'align' => 'left',
            'search' => 'false',
            'sortable' => 'false',
        ],
        ['name' => 'firstname', 'index' => 'firstname', 'width' => '35', 'align' => 'left', 'search' => 'true'],
        ['name' => 'lastname', 'index' => 'lastname', 'width' => '35', 'align' => 'left', 'search' => 'true'],
        [
            'name' => 'title',
            'index' => 'title',
            'width' => '40',
            'align' => 'left',
            'search' => 'false',
            'wrap_cell' => 'true',
        ],
        [
            'name' => 'qualification',
            'index' => 'qualification',
            'width' => '20',
            'align' => 'left',
            'search' => 'true',
        ],
        [
            'name' => 'sent_date',
            'index' => 'sent_date',
            'width' => '50',
            'align' => 'left',
            'search' => 'true',
            'wrap_cell' => 'true',
        ],
        [
            'name' => 'qualificator_id',
            'index' => 'qualificator_id',
            'width' => '30',
            'align' => 'left',
            'search' => 'true',
        ],
        [
            'name' => 'actions',
            'index' => 'actions',
            'width' => '40',
            'align' => 'left',
            'search' => 'false',
            'sortable' => 'false',
        ],
    ];
} else {
    $type = 'complex';
    $columns = [
        get_lang('Type'),
        get_lang('First name'),
        get_lang('Last name'),
        get_lang('Title'),
        get_lang('Date'),
        get_lang('Detail'),
    ];
    $column_model = [
        [
            'name' => 'type',
            'index' => 'file',
            'width' => '12',
            'align' => 'left',
            'search' => 'false',
            'sortable' => 'false',
        ],
        ['name' => 'firstname', 'index' => 'firstname', 'width' => '35', 'align' => 'left', 'search' => 'true'],
        ['name' => 'lastname', 'index' => 'lastname', 'width' => '35', 'align' => 'left', 'search' => 'true'],
        [
            'name' => 'title',
            'index' => 'title',
            'width' => '40',
            'align' => 'left',
            'search' => 'false',
            'wrap_cell' => 'true',
        ],
        [
            'name' => 'sent_date',
            'index' => 'sent_date',
            'width' => '50',
            'align' => 'left',
            'search' => 'true',
            'wrap_cell' => 'true',
        ],
        [
            'name' => 'actions',
            'index' => 'actions',
            'width' => '40',
            'align' => 'left',
            'search' => 'false',
            'sortable' => 'false',
        ],
    ];
}

$extra_params = [];
$extra_params['autowidth'] = 'true';
$extra_params['height'] = 'auto';
$extra_params['sortname'] = 'firstname';
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_work_user_list_others&work_id='.$workId.'&type='.$type.'&'.api_get_cidreq();
?>
<script>
    $(function() {
        <?php
        echo Display::grid_js('results', $url, $columns, $column_model, $extra_params);
    ?>
    });
</script>
<?php
echo Display::grid_html('results');

Display :: display_footer();

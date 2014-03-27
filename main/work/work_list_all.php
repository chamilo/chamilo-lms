<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

$language_file = array('exercice', 'work', 'document', 'admin', 'gradebook');

require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_STUDENTPUBLICATION;

api_protect_course_script(true);

// Including necessary files
require_once 'work.lib.php';
$this_section = SECTION_COURSES;

$workId = isset($_GET['id']) ? intval($_GET['id']) : null;
$is_allowed_to_edit = api_is_allowed_to_edit();

if (empty($workId)) {
    api_not_allowed(true);
}

$my_folder_data = get_work_data_by_id($workId);
if (empty($my_folder_data)) {
    api_not_allowed(true);
}

$work_data = get_work_assignment_by_id($workId);

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$tool_name = get_lang('StudentPublications');
$group_id = api_get_group_id();
$courseInfo = api_get_course_info();
$htmlHeadXtra[] = api_get_jqgrid_js();

if (!empty($group_id)) {
    $group_properties  = GroupManager :: get_group_properties($group_id);
    $show_work = false;

    if (api_is_allowed_to_edit(false, true)) {
        $show_work = true;
    } else {
        // you are not a teacher
        $show_work = GroupManager::user_has_access($user_id, $group_id, GroupManager::GROUP_TOOL_WORK);
    }

    if (!$show_work) {
        api_not_allowed();
    }

    $interbreadcrumb[] = array('url' => '../group/group.php', 'name' => get_lang('Groups'));
    $interbreadcrumb[] = array('url' => '../group/group_space.php?gidReq='.$group_id, 'name' => get_lang('GroupSpace').' '.$group_properties['name']);
}

$interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(), 'name' => get_lang('StudentPublications'));
$interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq().'&id='.$workId, 'name' =>  $my_folder_data['title']);

$error_message = null;

Display :: display_header(null);

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
$item_id = isset($_REQUEST['item_id']) ? intval($_REQUEST['item_id']) : null;

/*	Delete document */
if ($action == 'delete' && $item_id) {
    $file_deleted = deleteWorkItem($item_id, $_course);
    if (!$file_deleted) {
        Display::display_error_message(get_lang('YouAreNotAllowedToDeleteThisDocument'));
    } else {
        Display::display_confirmation_message(get_lang('TheDocumentHasBeenDeleted'));
    }
}

/*	Visible */
if ($is_allowed_to_edit && $action == 'make_visible') {
    if (!empty($item_id)) {
        if (isset($item_id) && $item_id == 'all') {
        } else {
            makeVisible($item_id, $courseInfo);
            Display::display_confirmation_message(get_lang('FileVisible'));
        }
    }
}

if ($is_allowed_to_edit && $action == 'make_invisible') {

    /*	Invisible */
    if (!empty($item_id)) {
        if (isset($item_id) && $item_id == 'all') {
        } else {
            makeInvisible($item_id, $courseInfo);
            Display::display_confirmation_message(get_lang('FileInvisible'));
        }
    }
}

$documentsAddedInWork = getAllDocumentsFromWorkToString($workId, $courseInfo);

echo '<div class="actions">';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq().'">'.Display::return_icon('back.png', get_lang('BackToWorksList'),'',ICON_SIZE_MEDIUM).'</a>';
if (api_is_allowed_to_session_edit(false, true) && !empty($workId)) {
    /*echo '<a href="'.api_get_path(WEB_CODE_PATH).'work/upload.php?'.api_get_cidreq().'&id='.$workId.'">';
    echo Display::return_icon('upload_file.png', get_lang('UploadADocument'), '', ICON_SIZE_MEDIUM).'</a>';*/
    if (ADD_DOCUMENT_TO_WORK) {
        echo '<a href="'.api_get_path(WEB_CODE_PATH).'work/add_document.php?'.api_get_cidreq().'&id='.$workId.'">';
        echo Display::return_icon('new_document.png', get_lang('AddDocument'), '', ICON_SIZE_MEDIUM).'</a>';

        echo '<a href="'.api_get_path(WEB_CODE_PATH).'work/add_user.php?'.api_get_cidreq().'&id='.$workId.'">';
        echo Display::return_icon('user.png', get_lang('AddUsers'), '', ICON_SIZE_MEDIUM).'</a>';
    }

    $display_output = '<a href="'.api_get_path(WEB_CODE_PATH).'work/work_missing.php?'.api_get_cidreq().'&amp;id='.$workId.'&amp;list=without">'.
    Display::return_icon('exercice_uncheck.png', get_lang('ViewUsersWithoutTask'), '', ICON_SIZE_MEDIUM)."</a>";
    $count = get_count_work($workId);
    if ($count > 0) {
        $display_output .= '<a href="downloadfolder.inc.php?id='.$workId.'&'.api_get_cidreq().'">'.
            Display::return_icon('save_pack.png', get_lang('Save'), array('style' => 'float:right;'), ICON_SIZE_MEDIUM).'</a>';
    }
    echo $display_output;


    echo '<a href="'.api_get_path(WEB_CODE_PATH).'work/edit_work.php?'.api_get_cidreq().'&id='.$workId.'">';
    echo Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_MEDIUM).'</a>';
}
echo '</div>';

if (!empty($my_folder_data['title'])) {
    echo Display::page_subheader($my_folder_data['title']);
}

$error_message = Session::read('error_message');
if (!empty($error_message)) {
    echo $error_message;
    Session::erase('error_message');
}

if (!empty($my_folder_data['description'])) {
    echo '<p><div><strong>'.get_lang('Description').':</strong><p>'.Security::remove_XSS($my_folder_data['description']).'</p></div></p>';
}

$check_qualification = intval($my_folder_data['qualification']);

if (!empty($work_data['enable_qualification']) && !empty($check_qualification)) {
    $type = 'simple';

    $columns = array(
        get_lang('Type'),
        get_lang('FirstName'),
        get_lang('LastName'),
        get_lang('Title'),
        get_lang('Qualification'),
        get_lang('Date'),
        get_lang('Status'),
        get_lang('Actions')
    );
    $column_model   = array (
        array('name'=>'type',           'index'=>'file',            'width'=>'8',   'align'=>'left', 'search' => 'false', 'sortable' => 'false'),
        array('name'=>'firstname',      'index'=>'firstname',       'width'=>'35',   'align'=>'left', 'search' => 'true'),
        array('name'=>'lastname',		'index'=>'lastname',        'width'=>'35',   'align'=>'left', 'search' => 'true'),
        array('name'=>'title',          'index'=>'title',           'width'=>'40',   'align'=>'left', 'search' => 'false', 'wrap_cell' => 'true'),
        array('name'=>'qualification',	'index'=>'qualification',	'width'=>'20',   'align'=>'left', 'search' => 'true'),
        array('name'=>'sent_date',      'index'=>'sent_date',       'width'=>'40',   'align'=>'left', 'search' => 'true', 'wrap_cell' => 'true'),
        array('name'=>'qualificator_id','index'=>'qualificator_id', 'width'=>'25',   'align'=>'left', 'search' => 'true'),
        array('name'=>'actions',        'index'=>'actions',         'width'=>'30',   'align'=>'left', 'search' => 'false', 'sortable'=>'false', )
    );
} else {
    $type = 'complex';

    $columns = array(
        get_lang('Type'),
        get_lang('FirstName'),
        get_lang('LastName'),
        get_lang('Title'),
        get_lang('Date'),
        get_lang('Actions')
    );

    $column_model   = array (
        array('name'=>'type',           'index'=>'file',            'width'=>'8',   'align'=>'left', 'search' => 'false', 'sortable' => 'false'),
        array('name'=>'firstname',      'index'=>'firstname',       'width'=>'35',   'align'=>'left', 'search' => 'true'),
        array('name'=>'lastname',		'index'=>'lastname',        'width'=>'35',   'align'=>'left', 'search' => 'true'),
        array('name'=>'title',          'index'=>'title',           'width'=>'40',   'align'=>'left', 'search' => 'false', 'wrap_cell' => "true"),
        array('name'=>'sent_date',      'index'=>'sent_date',      'width'=>'45',   'align'=>'left', 'search' => 'true', 'wrap_cell' => 'true'),
        array('name'=>'actions',        'index'=>'actions',         'width'=>'40',   'align'=>'left', 'search' => 'false', 'sortable'=>'false', 'wrap_cell' => 'true')
    );
}

$extra_params = array();

// Auto-width
$extra_params['autowidth'] = 'true';

// height auto
$extra_params['height'] = 'auto';
$extra_params['sortname'] = 'firstname';

$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_work_user_list_all&work_id='.$workId.'&type='.$type;
?>
<script>
$(function() {
    <?php
    echo Display::grid_js('results', $url, $columns, $column_model, $extra_params);
?>
});
</script>
<?php

echo $documentsAddedInWork;
echo Display::grid_html('results');
Display :: display_footer();

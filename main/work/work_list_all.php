<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_STUDENTPUBLICATION;

api_protect_course_script(true);

// Including necessary files
require_once 'work.lib.php';
$this_section = SECTION_COURSES;

$workId = isset($_GET['id']) ? intval($_GET['id']) : null;
$is_allowed_to_edit = api_is_allowed_to_edit() || api_is_coach();

if (empty($workId)) {
    api_not_allowed(true);
}

$my_folder_data = get_work_data_by_id($workId);
if (empty($my_folder_data)) {
    api_not_allowed(true);
}

$work_data = get_work_assignment_by_id($workId);

$isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(
    api_get_user_id(),
    api_get_course_info()
);

if (!($is_allowed_to_edit || $isDrhOfCourse)) {
    api_not_allowed(true);
}

$tool_name = get_lang('StudentPublications');
$group_id = api_get_group_id();
$courseInfo = api_get_course_info();
$courseCode = $courseInfo['code'];
$sessionId = api_get_session_id();
$htmlHeadXtra[] = api_get_jqgrid_js();
$user_id = api_get_user_id();

if (!empty($group_id)) {
    $group_properties = GroupManager :: get_group_properties($group_id);
    $show_work = false;

    if (api_is_allowed_to_edit(false, true)) {
        $show_work = true;
    } else {
        // you are not a teacher
        $show_work = GroupManager::user_has_access(
            $user_id,
            $group_properties['iid'],
            GroupManager::GROUP_TOOL_WORK
        );
    }

    if (!$show_work) {
        api_not_allowed();
    }

    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
        'name' => get_lang('Groups')
    );

    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
        'name' => get_lang('GroupSpace').' '.$group_properties['name']
    );
}

$interbreadcrumb[] = array(
    'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
    'name' => get_lang('StudentPublications')
);
$interbreadcrumb[] = array(
    'url' => api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq().'&id='.$workId,
    'name' =>  $my_folder_data['title']
);

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
$itemId = isset($_REQUEST['item_id']) ? intval($_REQUEST['item_id']) : null;

switch ($action) {
    case 'export_to_doc':
        if ($is_allowed_to_edit) {
            if (!empty($itemId)) {
                $work = get_work_data_by_id($itemId);
                if (!empty($work)) {
                    Export::htmlToOdt($work['description'], $work['title']);
                }
            }
        }
        break;
    case 'delete':
        /*	Delete document */
        if ($itemId) {
            $fileDeleted = deleteWorkItem($itemId, $courseInfo);
            if (!$fileDeleted) {
                Display::addFlash(
                    Display::return_message(get_lang('YouAreNotAllowedToDeleteThisDocument'), 'error')
                );
            } else {
                Display::addFlash(
                    Display::return_message(get_lang('TheDocumentHasBeenDeleted'), 'confirmation')
                );
            }
        }
        break;
    case 'delete_correction':
        $result = get_work_user_list(null, null, null, null, $workId);
        if ($result) {
            foreach ($result as $item) {
                $workToDelete = get_work_data_by_id($item['id']);
                deleteCorrection($courseInfo, $workToDelete);
            }
            Display::addFlash(
                Display::return_message(get_lang('Deleted'), 'confirmation')
            );
        }
        header('Location: '.api_get_self().'?'.api_get_cidreq().'&id='.$workId);
        exit;
        break;
    case 'make_visible':
        /*	Visible */
        if ($is_allowed_to_edit) {
            if (!empty($itemId)) {
                if (isset($itemId) && $itemId == 'all') {
                } else {
                    makeVisible($itemId, $courseInfo);
                    Display::addFlash(
                        Display::return_message(get_lang('FileVisible'), 'confirmation')
                    );
                }
            }
        }
        break;
    case 'make_invisible':
        /*	Invisible */
        if (!empty($itemId)) {
            if (isset($itemId) && $itemId == 'all') {
            } else {
                makeInvisible($itemId, $courseInfo);
                Display::addFlash(
                    Display::return_message(get_lang('FileInvisible'), 'confirmation')
                );
            }
        }
        break;
    case 'export_pdf':
        exportAllStudentWorkFromPublication(
            $workId,
            $courseInfo,
            $sessionId,
            'pdf'
        );
        break;
}

$htmlHeadXtra[] = api_get_jquery_libraries_js(array('jquery-upload'));

Display :: display_header(null);

$documentsAddedInWork = getAllDocumentsFromWorkToString($workId, $courseInfo);

$actionsLeft = '<a href="'.api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq().'">'.
    Display::return_icon('back.png', get_lang('BackToWorksList'), '', ICON_SIZE_MEDIUM).'</a>';

if (api_is_allowed_to_session_edit(false, true) && !empty($workId) && !$isDrhOfCourse) {
    $actionsLeft .= '<a href="'.api_get_path(WEB_CODE_PATH).'work/add_document.php?'.api_get_cidreq().'&id='.$workId.'">';
    $actionsLeft .= Display::return_icon('new_document.png', get_lang('AddDocument'), '', ICON_SIZE_MEDIUM).'</a>';

    $actionsLeft .= '<a href="'.api_get_path(WEB_CODE_PATH).'work/add_user.php?'.api_get_cidreq().'&id='.$workId.'">';
    $actionsLeft .= Display::return_icon('addworkuser.png', get_lang('AddUsers'), '', ICON_SIZE_MEDIUM).'</a>';

    $actionsLeft .= '<a href="'.api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq().'&id='.$workId.'&action=export_pdf">';
    $actionsLeft .= Display::return_icon('pdf.png', get_lang('Export'), '', ICON_SIZE_MEDIUM).'</a>';

    $display_output = '<a href="'.api_get_path(WEB_CODE_PATH).'work/work_missing.php?'.api_get_cidreq().'&amp;id='.$workId.'&amp;list=without">'.
    Display::return_icon('exercice_uncheck.png', get_lang('ViewUsersWithoutTask'), '', ICON_SIZE_MEDIUM)."</a>";

    $actionsLeft .= '<a href="'.api_get_path(WEB_CODE_PATH).'work/edit_work.php?'.api_get_cidreq().'&id='.$workId.'">';
    $actionsLeft .= Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_MEDIUM).'</a>';

    $count = get_count_work($workId);
    if ($count > 0) {
        $display_output .= '<a class="btn-toolbar" href="downloadfolder.inc.php?id='.$workId.'&'.api_get_cidreq().'">'.
            Display::return_icon('save_pack.png', get_lang('DownloadTasksPackage'), null, ICON_SIZE_MEDIUM).' '.get_lang('DownloadTasksPackage').'</a>';
    }
    $actionsLeft .= $display_output;
    $url = api_get_path(WEB_CODE_PATH).'work/upload_corrections.php?'.api_get_cidreq().'&id='.$workId;
    $actionsLeft .= '<a class="btn-toolbar" href="'.$url.'">'.
        Display::return_icon('upload_package.png', get_lang('UploadCorrectionsPackage'), '', ICON_SIZE_MEDIUM).' '.get_lang('UploadCorrectionsPackage').'</a>';
    $url = api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq().'&id='.$workId.'&action=delete_correction';
    $actionsLeft .= Display::toolbarButton(get_lang('DeleteCorrections'), $url, 'remove', 'danger');
}

echo Display::toolbarAction('toolbar-worklist', array($actionsLeft));

if (!empty($my_folder_data['title'])) {
    echo Display::page_subheader($my_folder_data['title']);
}

if (!empty($my_folder_data['description'])) {
    $contentWork = Security::remove_XSS($my_folder_data['description']);
    $html = '';
    $html .= Display::panel($contentWork, get_lang('Description'));
    echo $html;
}

$check_qualification = intval($my_folder_data['qualification']);
$orderName = api_is_western_name_order() ? 'firstname' : 'lastname';


if (!empty($work_data['enable_qualification']) &&
    !empty($check_qualification)
) {
    $type = 'simple';
    $columns = array(
        get_lang('FullUserName'),
        get_lang('Title'),
        get_lang('Feedback'),
        get_lang('Date'),
        get_lang('Status'),
        get_lang('UploadCorrection'),
        get_lang('Actions')
    );

    $column_model = array(
        array(
            'name' => 'fullname',
            'index' => $orderName,
            'width' => '30',
            'align' => 'left',
            'search' => 'true',
        ),
        array(
            'name' => 'title',
            'index' => 'title',
            'width' => '25',
            'align' => 'left',
            'search' => 'false',
            'wrap_cell' => 'true',
        ),
        array(
            'name' => 'qualification',
            'index' => 'qualification',
            'width' => '15',
            'align' => 'center',
            'search' => 'true',
        ),
        array(
            'name' => 'sent_date',
            'index' => 'sent_date',
            'width' => '25',
            'align' => 'left',
            'search' => 'true',
            'wrap_cell' => 'true',
        ),
        array(
            'name' => 'qualificator_id',
            'index' => 'qualificator_id',
            'width' => '20',
            'align' => 'left',
            'search' => 'true',
        ),
        array(
            'name' => 'correction',
            'index' => 'correction',
            'width' => '30',
            'align' => 'left',
            'search' => 'false',
            'sortable' => 'false',
            'title' => 'false'
        ),
        array(
            'name' => 'actions',
            'index' => 'actions',
            'width' => '25',
            'align' => 'left',
            'search' => 'false',
            'sortable' => 'false',
        ),
    );
} else {
    $type = 'complex';
    $columns = array(
        get_lang('FullUserName'),
        get_lang('Title'),
        get_lang('Feedback'),
        get_lang('Date'),
        get_lang('UploadCorrection'),
        get_lang('Actions')
    );

    $column_model = array(
        array(
            'name' => 'fullname',
            'index' => $orderName,
            'width' => '35',
            'align' => 'left',
            'search' => 'true',
        ),
        array(
            'name' => 'title',
            'index' => 'title',
            'width' => '30',
            'align' => 'left',
            'search' => 'false',
            'wrap_cell' => "true",
        ),
        array(
            'name' => 'qualification',
            'index' => 'qualification',
            'width' => '30',
            'align' => 'left',
            'search' => 'true',
        ),
        array(
            'name' => 'sent_date',
            'index' => 'sent_date',
            'width' => '30',
            'align' => 'left',
            'search' => 'true',
            'wrap_cell' => 'true',
        ),
        array(
            'name' => 'correction',
            'index' => 'correction',
            'width' => '45',
            'align' => 'left',
            'search' => 'false',
            'sortable' => 'false',
            'title' => 'false',
        ),
        array(
            'name' => 'actions',
            'index' => 'actions',
            'width' => '30',
            'align' => 'left',
            'search' => 'false',
            'sortable' => 'false'
            //'wrap_cell' => 'true',
        )
    );
}

$extra_params = array(
    'autowidth' => 'true',
    'height' => 'auto',
    'sortname' => $orderName,
    'sortable' => 'false',
);

$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_work_user_list_all&work_id='.$workId.'&type='.$type.'&'.api_get_cidreq();
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
$tableWork = Display::grid_html('results');
echo Display::panel($tableWork);
echo '<div class="list-work-results">';
echo '<div class="panel panel-default">';
echo '<div class="panel-body">';
echo '<table style="display:none; width:100%" class="files data_table">
        <tr>
            <th>'.get_lang('FileName').'</th>
            <th>'.get_lang('Size').'</th>
            <th>'.get_lang('Status').'</th>
        </tr>
    </table>';
echo '</div></div></div>';
Display :: display_footer();

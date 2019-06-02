<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_STUDENTPUBLICATION;

api_protect_course_script(true);

// Including necessary files
require_once 'work.lib.php';
$this_section = SECTION_COURSES;

$workId = isset($_GET['id']) ? (int) $_GET['id'] : null;
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
    $group_properties = GroupManager::get_group_properties($group_id);
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

    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
        'name' => get_lang('Groups'),
    ];

    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
        'name' => get_lang('GroupSpace').' '.$group_properties['name'],
    ];
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
    'name' => get_lang('StudentPublications'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq().'&id='.$workId,
    'name' => $my_folder_data['title'],
];

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
$itemId = isset($_REQUEST['item_id']) ? (int) $_REQUEST['item_id'] : null;

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
        /* Visible */
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
        /* Invisible */
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

$htmlHeadXtra[] = api_get_jquery_libraries_js(['jquery-upload']);

Display::display_header(null);

$documentsAddedInWork = getAllDocumentsFromWorkToString($workId, $courseInfo);

$actionsLeft = '<a href="'.api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq().'">'.
    Display::return_icon('back.png', get_lang('BackToWorksList'), '', ICON_SIZE_MEDIUM).'</a>';

if (api_is_allowed_to_session_edit(false, true) && !empty($workId) && !$isDrhOfCourse) {
    $blockAddDocuments = api_get_configuration_value('block_student_publication_add_documents');

    if (!$blockAddDocuments) {
        $actionsLeft .= '<a href="'.api_get_path(WEB_CODE_PATH).'work/add_document.php?'.api_get_cidreq().'&id='.$workId.'">';
        $actionsLeft .= Display::return_icon('new_document.png', get_lang('AddDocument'), '', ICON_SIZE_MEDIUM).'</a>';
    }

    $actionsLeft .= '<a href="'.api_get_path(WEB_CODE_PATH).'work/add_user.php?'.api_get_cidreq().'&id='.$workId.'">';
    $actionsLeft .= Display::return_icon('addworkuser.png', get_lang('AddUsers'), '', ICON_SIZE_MEDIUM).'</a>';

    $actionsLeft .= '<a href="'.api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq().'&id='.$workId.'&action=export_pdf">';
    $actionsLeft .= Display::return_icon('pdf.png', get_lang('Export'), '', ICON_SIZE_MEDIUM).'</a>';

    $display_output = '<a href="'.api_get_path(WEB_CODE_PATH).'work/work_missing.php?'.api_get_cidreq().'&amp;id='.$workId.'&amp;list=without">'.
    Display::return_icon('exercice_uncheck.png', get_lang('ViewUsersWithoutTask'), '', ICON_SIZE_MEDIUM)."</a>";

    $editLink = '<a href="'.api_get_path(WEB_CODE_PATH).'work/edit_work.php?'.api_get_cidreq().'&id='.$workId.'">';
    $editLink .= Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_MEDIUM).'</a>';

    $blockEdition = api_get_configuration_value('block_student_publication_edition');
    if ($blockEdition && !api_is_platform_admin()) {
        $editLink = '';
    }
    $actionsLeft .= $editLink;

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

echo Display::toolbarAction('toolbar-worklist', [$actionsLeft]);

if (!empty($my_folder_data['title'])) {
    echo Display::page_subheader($my_folder_data['title']);
}

if (!empty($my_folder_data['description'])) {
    $contentWork = Security::remove_XSS($my_folder_data['description']);
    $html = '';
    $html .= Display::panel($contentWork, get_lang('Description'));
    echo $html;
}

$check_qualification = (int) $my_folder_data['qualification'];
$orderName = api_is_western_name_order() ? 'firstname' : 'lastname';

if (!empty($work_data['enable_qualification']) &&
    !empty($check_qualification)
) {
    $type = 'simple';
    $columns = [
        get_lang('FullUserName'),
        get_lang('Title'),
        get_lang('Score'),
        get_lang('Date'),
        get_lang('Status'),
        get_lang('UploadCorrection'),
        get_lang('Actions'),
    ];

    $column_model = [
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
        [
            'name' => 'actions',
            'index' => 'actions',
            'width' => '25',
            'align' => 'left',
            'search' => 'false',
            'sortable' => 'false',
        ],
    ];
} else {
    $type = 'complex';
    $columns = [
        get_lang('FullUserName'),
        get_lang('Title'),
        get_lang('Feedback'),
        get_lang('Date'),
        get_lang('UploadCorrection'),
        get_lang('Actions'),
    ];

    $column_model = [
        [
            'name' => 'fullname',
            'index' => $orderName,
            'width' => '35',
            'align' => 'left',
            'search' => 'true',
        ],
        [
            'name' => 'title',
            'index' => 'title',
            'width' => '30',
            'align' => 'left',
            'search' => 'false',
            'wrap_cell' => 'true',
        ],
        [
            'name' => 'qualification',
            'index' => 'qualification',
            'width' => '20',
            'align' => 'center',
            'search' => 'true',
        ],
        [
            'name' => 'sent_date',
            'index' => 'sent_date',
            'width' => '30',
            'align' => 'left',
            'search' => 'true',
            'wrap_cell' => 'true',
        ],
        [
            'name' => 'correction',
            'index' => 'correction',
            'width' => '40',
            'align' => 'left',
            'search' => 'false',
            'sortable' => 'false',
            'title' => 'false',
        ],
        [
            'name' => 'actions',
            'index' => 'actions',
            'width' => '30',
            'align' => 'left',
            'search' => 'false',
            'sortable' => 'false',
            //'wrap_cell' => 'true',
        ],
    ];
}

$extra_params = [
    'autowidth' => 'true',
    'height' => 'auto',
    'sortname' => $orderName,
    'sortable' => 'false',
    'multiselect' => 'true',
];

$url = api_get_path(WEB_AJAX_PATH).
    'model.ajax.php?a=get_work_user_list_all&work_id='.$workId.'&type='.$type.'&'.api_get_cidreq();

$workUrl = api_get_path(WEB_AJAX_PATH).'work.ajax.php?'.api_get_cidreq();
$deleteUrl = $workUrl.'&a=delete_student_work';
$showUrl = $workUrl.'&a=show_student_work';
$hideUrl = $workUrl.'&a=hide_student_work';

?>
<script>
$(function() {
    <?php
    echo Display::grid_js('results', $url, $columns, $column_model, $extra_params);
    ?>

    $("#results").jqGrid(
        "navGrid",
        "#results_pager",
        { edit: false, add: false, search: false, del: true },
        { height:280, reloadAfterSubmit:false }, // edit options
        { height:280, reloadAfterSubmit:false }, // add options
        { reloadAfterSubmit:false, url: "<?php echo $deleteUrl; ?>" }, // del options
        { width:500 } // search options
    ).navButtonAdd('#results_pager', {
        caption:"<i class=\"fa fa-eye\" ></i>",
        buttonicon:"ui-icon-blank",
        onClickButton: function(a) {
            var userIdList = $("#results").jqGrid('getGridParam', 'selarrrow');
            if (userIdList.length) {
                $.ajax({
                    type: "POST",
                    url: "<?php echo $showUrl; ?>&item_list=" + userIdList,
                    dataType: "json",
                    success: function(data) {
                        $('#results').trigger('reloadGrid');
                    }
                });
            } else {
                alert("<?php echo addslashes(get_lang('SelectAnOption')); ?>");
            }
        },
        position:"last"
    }).navButtonAdd('#results_pager', {
        //caption:"<?php //echo addslashes(get_lang('SetVisible'));?>//",
        caption:"<i class=\"fa fa-eye-slash\" ></i>",
        buttonicon:"ui-icon-blank",
        onClickButton: function(a) {
            var userIdList = $("#results").jqGrid('getGridParam', 'selarrrow');
            if (userIdList.length) {
                $.ajax({
                    type: "POST",
                    url: "<?php echo $hideUrl; ?>&item_list=" + userIdList,
                    dataType: "json",
                    success: function(data) {
                        $('#results').trigger('reloadGrid');
                    }
                });
            } else {
                alert("<?php echo addslashes(get_lang('SelectAnOption')); ?>");
            }
        },
        position:"last"
    });
});

</script>
<?php

echo $documentsAddedInWork;

$tableWork = Display::grid_html('results');

echo workGetExtraFieldData($workId);
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

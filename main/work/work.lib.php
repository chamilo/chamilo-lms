<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CStudentPublication;
use ChamiloSession as Session;
use Doctrine\DBAL\Driver\Statement;

/**
 *  @author Thomas, Hugues, Christophe - original version
 *  @author Patrick Cool <patrick.cool@UGent.be>, Ghent University -
 * ability for course admins to specify wether uploaded documents are visible or invisible by default.
 *  @author Roan Embrechts, code refactoring and virtual course support
 *  @author Frederic Vauthier, directories management
 *  @author Julio Montoya <gugli100@gmail.com> BeezNest 2011 LOTS of bug fixes
 *
 *  @todo   this lib should be convert in a static class and moved to main/inc/lib
 */

/**
 * Displays action links (for admins, authorized groups members and authorized students).
 *
 * @param   int Whether to show tool options
 * @param   int Whether to show upload form option
 * @param bool $isTutor
 */
function displayWorkActionLinks($id, $action, $isTutor)
{
    $id = $my_back_id = (int) $id;
    if ('list' === $action) {
        $my_back_id = 0;
    }

    $output = '';
    $origin = api_get_origin();
    if (!empty($id)) {
        $output .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&id='.$my_back_id.'">'.
            Display::return_icon('back.png', get_lang('BackToWorksList'), '', ICON_SIZE_MEDIUM).
            '</a>';
    }

    if (($isTutor || api_is_allowed_to_edit(null, true)) &&
        'learnpath' !== $origin
    ) {
        // Create dir
        if (empty($id)) {
            $output .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=create_dir">';
            $output .= Display::return_icon(
                'new_work.png',
                get_lang('CreateAssignment'),
                '',
                ICON_SIZE_MEDIUM
            );
            $output .= '</a>';
        }
    }

    if (api_is_allowed_to_edit(null, true) && $origin !== 'learnpath' && $action === 'list') {
        $output .= '<a id="open-view-list" href="#">'.
            Display::return_icon(
                'listwork.png',
                get_lang('ViewStudents'),
                '',
                ICON_SIZE_MEDIUM
            ).
            '</a>';
    }

    if ('' != $output) {
        echo '<div class="actions">';
        echo $output;
        echo '</div>';
    }
}

/**
 * @param string $path
 * @param int    $courseId
 *
 * @return array
 */
function get_work_data_by_path($path, $courseId = 0)
{
    $path = Database::escape_string($path);
    $courseId = (int) $courseId;
    if (empty($courseId)) {
        $courseId = api_get_course_int_id();
    }

    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $sql = "SELECT *  FROM $table
            WHERE url = '$path' AND c_id = $courseId ";
    $result = Database::query($sql);
    $return = [];
    if (Database::num_rows($result)) {
        $return = Database::fetch_array($result, 'ASSOC');
    }

    return $return;
}

/**
 * @param int $id
 * @param int $courseId
 * @param int $sessionId
 *
 * @return array
 */
function get_work_data_by_id($id, $courseId = 0, $sessionId = 0)
{
    $id = (int) $id;
    $courseId = ((int) $courseId) ?: api_get_course_int_id();
    $course = api_get_course_entity($courseId);
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

    $sessionCondition = '';
    if (!empty($sessionId)) {
        $sessionCondition = api_get_session_condition($sessionId, true);
    }

    $webCodePath = api_get_path(WEB_CODE_PATH);

    $sql = "SELECT * FROM $table
            WHERE
                id = $id AND c_id = $courseId
                $sessionCondition";
    $result = Database::query($sql);
    $work = [];
    if (Database::num_rows($result)) {
        $work = Database::fetch_array($result, 'ASSOC');
        if (empty($work['title'])) {
            $work['title'] = basename($work['url']);
        }
        $work['download_url'] = $webCodePath.'work/download.php?id='.$work['id'].'&'.api_get_cidreq();
        $work['view_url'] = $webCodePath.'work/view.php?id='.$work['id'].'&'.api_get_cidreq();
        $work['show_url'] = $webCodePath.'work/show_file.php?id='.$work['id'].'&'.api_get_cidreq();
        $work['show_content'] = '';
        if ($work['contains_file']) {
            $fileType = '';
            $file = api_get_path(SYS_COURSE_PATH).$course->getDirectory().'/'.$work['url'];
            if (file_exists($file)) {
                $fileType = mime_content_type($file);
            }

            if (in_array($fileType, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'])) {
                $work['show_content'] = Display::img($work['show_url'], $work['title'], null, false);
            } elseif (false !== strpos($fileType, 'video/')) {
                $work['show_content'] = Display::tag(
                    'video',
                    get_lang('FileFormatNotSupported'),
                    ['src' => $work['show_url']]
                );
            }
        }

        $fieldValue = new ExtraFieldValue('work');
        $work['extra'] = $fieldValue->getAllValuesForAnItem($id, true);
    }

    return $work;
}

/**
 * @param int $user_id
 * @param int $work_id
 *
 * @return int
 */
function get_work_count_by_student($user_id, $work_id)
{
    $user_id = (int) $user_id;
    $work_id = (int) $work_id;
    $course_id = api_get_course_int_id();
    $session_id = api_get_session_id();
    $sessionCondition = api_get_session_condition($session_id);

    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $sql = "SELECT COUNT(*) as count
            FROM  $table
            WHERE
                c_id = $course_id AND
                parent_id = $work_id AND
                user_id = $user_id AND
                active IN (0, 1)
                $sessionCondition";
    $result = Database::query($sql);
    $return = 0;
    if (Database::num_rows($result)) {
        $return = Database::fetch_row($result, 'ASSOC');
        $return = (int) ($return[0]);
    }

    return $return;
}

/**
 * @param int $id
 * @param int $courseId
 *
 * @return array
 */
function get_work_assignment_by_id($id, $courseId = 0)
{
    $courseId = (int) $courseId;
    if (empty($courseId)) {
        $courseId = api_get_course_int_id();
    }
    $id = (int) $id;
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
    $sql = "SELECT * FROM $table
            WHERE c_id = $courseId AND publication_id = $id";
    $result = Database::query($sql);
    $return = [];
    if (Database::num_rows($result)) {
        $return = Database::fetch_array($result, 'ASSOC');
    }

    return $return;
}

/**
 * @param int    $id
 * @param array  $my_folder_data
 * @param string $add_in_where_query
 * @param int    $course_id
 * @param int    $session_id
 *
 * @return array
 */
function getWorkList($id, $my_folder_data, $add_in_where_query = null, $course_id = 0, $session_id = 0)
{
    $work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

    $course_id = $course_id ? $course_id : api_get_course_int_id();
    $session_id = $session_id ? $session_id : api_get_session_id();
    $condition_session = api_get_session_condition($session_id);
    $group_id = api_get_group_id();

    $groupIid = 0;
    if ($group_id) {
        $groupInfo = GroupManager::get_group_properties($group_id);
        if ($groupInfo) {
            $groupIid = $groupInfo['iid'];
        }
    }

    $is_allowed_to_edit = api_is_allowed_to_edit(null, true);
    $linkInfo = GradebookUtils::isResourceInCourseGradebook(
        api_get_course_id(),
        3,
        $id,
        api_get_session_id()
    );

    if ($linkInfo) {
        $workInGradeBookLinkId = $linkInfo['id'];
        if ($workInGradeBookLinkId) {
            if ($is_allowed_to_edit) {
                if (intval($my_folder_data['qualification']) == 0) {
                    echo Display::return_message(
                        get_lang('MaxWeightNeedToBeProvided'),
                        'warning'
                    );
                }
            }
        }
    }

    $contains_file_query = '';
    // Get list from database
    if ($is_allowed_to_edit) {
        $active_condition = ' active IN (0, 1)';
        $sql = "SELECT * FROM $work_table
                WHERE
                    c_id = $course_id
                    $add_in_where_query
                    $condition_session AND
                    $active_condition AND
                    (parent_id = 0)
                    $contains_file_query AND
                    post_group_id = $groupIid
                ORDER BY sent_date DESC";
    } else {
        if (!empty($group_id)) {
            // set to select only messages posted by the user's group
            $group_query = " WHERE c_id = $course_id AND post_group_id = $groupIid";
            $subdirs_query = ' AND parent_id = 0';
        } else {
            $group_query = " WHERE c_id = $course_id AND (post_group_id = '0' OR post_group_id is NULL) ";
            $subdirs_query = ' AND parent_id = 0';
        }
        //@todo how we can active or not an assignment?
        $active_condition = ' AND active IN (1, 0)';
        $sql = "SELECT * FROM  $work_table
                $group_query
                $subdirs_query
                $add_in_where_query
                $active_condition
                $condition_session
                ORDER BY title";
    }

    $work_parents = [];

    $sql_result = Database::query($sql);
    if (Database::num_rows($sql_result)) {
        while ($work = Database::fetch_object($sql_result)) {
            if (0 == $work->parent_id) {
                $work_parents[] = $work;
            }
        }
    }

    return $work_parents;
}

/**
 * @param int $userId
 * @param int $courseId
 * @param int $sessionId
 *
 * @return array
 */
function getWorkPerUser($userId, $courseId = 0, $sessionId = 0)
{
    $works = getWorkList(null, null, null, $courseId, $sessionId);
    $result = [];
    if (!empty($works)) {
        foreach ($works as $workData) {
            $workId = $workData->id;
            $result[$workId]['work'] = $workData;
            $result[$workId]['work']->user_results = get_work_user_list(
                0,
                100,
                null,
                null,
                $workId,
                null,
                $userId,
                false,
                $courseId,
                $sessionId
            );
        }
    }

    return $result;
}

/**
 * @param int $workId
 * @param int $groupId
 * @param int $course_id
 * @param int $sessionId
 */
function getUniqueStudentAttemptsTotal($workId, $groupId, $course_id, $sessionId)
{
    $work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $user_table = Database::get_main_table(TABLE_MAIN_USER);
    $course_id = (int) $course_id;
    $workId = (int) $workId;
    $sessionId = (int) $sessionId;
    $groupId = (int) $groupId;
    $sessionCondition = api_get_session_condition(
        $sessionId,
        true,
        false,
        'w.session_id'
    );

    $groupIid = 0;
    if ($groupId) {
        $groupInfo = GroupManager::get_group_properties($groupId);
        $groupIid = $groupInfo['iid'];
    }

    $sql = "SELECT count(DISTINCT u.user_id)
            FROM $work_table w
            INNER JOIN $user_table u
            ON w.user_id = u.user_id
            WHERE
                w.c_id = $course_id
                $sessionCondition AND
                w.parent_id = $workId AND
                w.post_group_id = $groupIid AND
                w.active IN (0, 1)
            ";

    $res_document = Database::query($sql);
    $rowCount = Database::fetch_row($res_document);

    return $rowCount[0];
}

/**
 * @param mixed $workId
 * @param int   $groupId
 * @param int   $course_id
 * @param int   $sessionId
 * @param int   $userId       user id to filter
 * @param array $onlyUserList only parse this user list
 *
 * @return mixed
 */
function getUniqueStudentAttempts(
    $workId,
    $groupId,
    $course_id,
    $sessionId,
    $userId = null,
    $onlyUserList = []
) {
    $work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $user_table = Database::get_main_table(TABLE_MAIN_USER);

    $course_id = (int) $course_id;
    $workCondition = null;
    if (is_array($workId)) {
        $workId = array_map('intval', $workId);
        $workId = implode("','", $workId);
        $workCondition = " w.parent_id IN ('".$workId."') AND";
    } else {
        $workId = (int) $workId;
        $workCondition = ' w.parent_id = '.$workId.' AND';
    }

    $sessionId = (int) $sessionId;
    $groupId = (int) $groupId;
    $studentCondition = null;

    if (!empty($onlyUserList)) {
        $onlyUserList = array_map('intval', $onlyUserList);
        $studentCondition = "AND u.user_id IN ('".implode("', '", $onlyUserList)."') ";
    } else {
        if (empty($userId)) {
            return 0;
        }
    }

    $groupIid = 0;
    if ($groupId) {
        $groupInfo = GroupManager::get_group_properties($groupId);
        $groupIid = $groupInfo['iid'];
    }

    $sessionCondition = api_get_session_condition(
        $sessionId,
        true,
        false,
        'w.session_id'
    );

    $sql = "SELECT count(*) FROM (
                SELECT count(*), w.parent_id
                FROM $work_table w
                INNER JOIN $user_table u
                ON w.user_id = u.user_id
                WHERE
                    w.filetype = 'file' AND
                    w.c_id = $course_id
                    $sessionCondition AND
                    $workCondition
                    w.post_group_id = $groupIid AND
                    w.active IN (0, 1) $studentCondition
                ";
    if (!empty($userId)) {
        $userId = (int) $userId;
        $sql .= ' AND u.user_id = '.$userId;
    }
    $sql .= ' GROUP BY u.user_id, w.parent_id) as t';
    $result = Database::query($sql);
    $row = Database::fetch_row($result);

    return $row[0];
}

/**
 * Shows the work list (student view).
 *
 * @return string
 */
function showStudentWorkGrid()
{
    $courseInfo = api_get_course_info();
    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_work_student&'.api_get_cidreq();

    $columns = [
        get_lang('Type'),
        get_lang('Title'),
        get_lang('HandOutDateLimit'),
        get_lang('Feedback'),
        get_lang('LastUpload'),
    ];

    $columnModel = [
        ['name' => 'type', 'index' => 'type', 'width' => '30', 'align' => 'center', 'sortable' => 'false'],
        ['name' => 'title', 'index' => 'title', 'width' => '250', 'align' => 'left'],
        ['name' => 'expires_on', 'index' => 'expires_on', 'width' => '80', 'align' => 'center', 'sortable' => 'false'],
        ['name' => 'feedback', 'index' => 'feedback', 'width' => '80', 'align' => 'center', 'sortable' => 'false'],
        ['name' => 'last_upload', 'index' => 'feedback', 'width' => '125', 'align' => 'center', 'sortable' => 'false'],
    ];

    if ($courseInfo['show_score'] == 0) {
        $columnModel[] = [
            'name' => 'others',
            'index' => 'others',
            'width' => '80',
            'align' => 'left',
            'sortable' => 'false',
        ];
        $columns[] = get_lang('Others');
    }

    $params = [
        'autowidth' => 'true',
        'height' => 'auto',
    ];

    $html = '<script>
        $(function() {
            '.Display::grid_js('workList', $url, $columns, $columnModel, $params, [], null, true).'
        });
    </script>';

    $html .= Display::grid_html('workList');

    return $html;
}

/**
 * Shows the work list (student view).
 *
 * @return string
 */
function showStudentAllWorkGrid($withResults = 1)
{
    $withResults = (int) $withResults;
    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_all_work_student&with_results='.$withResults;

    $columns = [
        get_lang('Type'),
        get_lang('Title'),
        get_lang('HandOutDateLimit'),
    ];

    $id = 'workList';
    if ($withResults) {
        $id = 'workListWithResults';
        $columns[] = get_lang('Feedback');
        $columns[] = get_lang('LastUpload');
    }

    $columnModel = [
        ['name' => 'type', 'index' => 'type', 'width' => '50', 'align' => 'center', 'sortable' => 'false'],
        ['name' => 'title', 'index' => 'title', 'width' => '600', 'align' => 'left'],
        ['name' => 'expires_on', 'index' => 'expires_on', 'width' => '125', 'align' => 'center', 'sortable' => 'false'],
    ];

    if ($withResults) {
        $columnModel[] = [
            'name' => 'feedback',
            'index' => 'feedback',
            'width' => '150',
            'align' => 'center',
            'sortable' => 'false',
        ];
        $columnModel[] = [
            'name' => 'last_upload',
            'index' => 'last_upload',
            'width' => '150',
            'align' => 'center',
            'sortable' => 'false',
        ];
    }

    $params = [
        'autowidth' => 'true',
        'height' => 'auto',
    ];

    $html = '<script>
        $(function() {
            '.Display::grid_js($id, $url, $columns, $columnModel, $params, [], null, true).'
        });
    </script>';

    $html .= Display::grid_html($id);

    return $html;
}

/**
 * Shows the work list (teacher view).
 *
 * @return string
 */
function showTeacherWorkGrid()
{
    $columnModel = [
        ['name' => 'type', 'index' => 'type', 'width' => '35', 'align' => 'center', 'sortable' => 'false'],
        ['name' => 'title', 'index' => 'title', 'width' => '300', 'align' => 'left', 'wrap_cell' => "true"],
        ['name' => 'sent_date', 'index' => 'sent_date', 'width' => '125', 'align' => 'center'],
        ['name' => 'expires_on', 'index' => 'expires_on', 'width' => '125', 'align' => 'center'],
        ['name' => 'amount', 'index' => 'amount', 'width' => '110', 'align' => 'center', 'sortable' => 'false'],
        ['name' => 'actions', 'index' => 'actions', 'width' => '110', 'align' => 'left', 'sortable' => 'false'],
    ];
    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_work_teacher&'.api_get_cidreq();
    $deleteUrl = api_get_path(WEB_AJAX_PATH).'work.ajax.php?a=delete_work&'.api_get_cidreq();

    $columns = [
        get_lang('Type'),
        get_lang('Title'),
        get_lang('SentDate'),
        get_lang('HandOutDateLimit'),
        get_lang('AmountSubmitted'),
        get_lang('Actions'),
    ];

    $params = [
        'multiselect' => true,
        'autowidth' => 'true',
        'height' => 'auto',
        'sortname' => 'sent_date',
        'sortorder' => 'asc',
    ];

    $html = '<script>
    $(function() {
        '.Display::grid_js('workList', $url, $columns, $columnModel, $params, [], null, true).'
        $("#workList").jqGrid(
            "navGrid",
            "#workList_pager",
            { edit: false, add: false, del: true },
            { height:280, reloadAfterSubmit:false }, // edit options
            { height:280, reloadAfterSubmit:false }, // add options
            { reloadAfterSubmit:false, url: "'.$deleteUrl.'" }, // del options
            { width:500 } // search options
        );
    });
    </script>';
    $html .= Display::grid_html('workList');

    return $html;
}

/**
 * Builds the form that enables the user to
 * move a document from one directory to another
 * This function has been copied from the document/document.inc.php library.
 *
 * @param array  $folders
 * @param string $curdirpath
 * @param string $move_file
 * @param string $group_dir
 *
 * @return string html form
 */
function build_work_move_to_selector($folders, $curdirpath, $move_file, $group_dir = '')
{
    $course_id = api_get_course_int_id();
    $move_file = (int) $move_file;
    $tbl_work = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $sql = "SELECT title, url FROM $tbl_work
            WHERE c_id = $course_id AND id ='".$move_file."'";
    $result = Database::query($sql);
    $row = Database::fetch_array($result, 'ASSOC');
    $title = empty($row['title']) ? basename($row['url']) : $row['title'];

    $form = new FormValidator(
        'move_to_form',
        'post',
        api_get_self().'?'.api_get_cidreq().'&curdirpath='.Security::remove_XSS($curdirpath)
    );

    $form->addHeader(get_lang('MoveFile').' - '.Security::remove_XSS($title));
    $form->addHidden('item_id', $move_file);
    $form->addHidden('action', 'move_to');

    // Group documents cannot be uploaded in the root
    if ($group_dir == '') {
        if (is_array($folders)) {
            foreach ($folders as $fid => $folder) {
                //you cannot move a file to:
                //1. current directory
                //2. inside the folder you want to move
                //3. inside a subfolder of the folder you want to move
                if (($curdirpath != $folder) &&
                    ($folder != $move_file) &&
                    (substr($folder, 0, strlen($move_file) + 1) != $move_file.'/')
                ) {
                    $options[$fid] = $folder;
                }
            }
        }
    } else {
        if ($curdirpath != '/') {
            $form .= '<option value="0">/ ('.get_lang('Root').')</option>';
        }
        foreach ($folders as $fid => $folder) {
            if (($curdirpath != $folder) && ($folder != $move_file) &&
                (substr($folder, 0, strlen($move_file) + 1) != $move_file.'/')
            ) {
                //cannot copy dir into his own subdir
                $display_folder = substr($folder, strlen($group_dir));
                $display_folder = ($display_folder == '') ? '/ ('.get_lang('Root').')' : $display_folder;
                //$form .= '<option value="'.$fid.'">'.$display_folder.'</option>'."\n";
                $options[$fid] = $display_folder;
            }
        }
    }

    $form->addSelect('move_to_id', get_lang('Select'), $options);
    $form->addButtonSend(get_lang('MoveFile'), 'move_file_submit');

    return $form->returnForm();
}

/**
 * creates a new directory trying to find a directory name
 * that doesn't already exist.
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @author Bert Vanderkimpen
 * @author Yannick Warnier <ywarnier@beeznest.org> Adaptation for work tool
 *
 * @param string $workDir        Base work dir (.../work)
 * @param string $desiredDirName complete path of the desired name
 *
 * @return string actual directory name if it succeeds, boolean false otherwise
 */
function create_unexisting_work_directory($workDir, $desiredDirName)
{
    $counter = 0;
    $workDir = (substr($workDir, -1, 1) == '/' ? $workDir : $workDir.'/');
    $checkDirName = $desiredDirName;
    while (file_exists($workDir.$checkDirName)) {
        $counter++;
        $checkDirName = $desiredDirName.$counter;
    }

    if (@mkdir($workDir.$checkDirName, api_get_permissions_for_new_directories())) {
        return $checkDirName;
    } else {
        return false;
    }
}

/**
 * Delete a work-tool directory.
 *
 * @param int $id work directory id to delete
 *
 * @return int -1 on error
 */
function deleteDirWork($id)
{
    $locked = api_resource_is_locked_by_gradebook($id, LINK_STUDENTPUBLICATION);

    if ($locked == true) {
        echo Display::return_message(get_lang('ResourceLockedByGradebook'), 'warning');

        return false;
    }

    $_course = api_get_course_info();
    $id = (int) $id;
    $work_data = get_work_data_by_id($id);

    if (empty($work_data)) {
        return false;
    }

    $base_work_dir = api_get_path(SYS_COURSE_PATH).$_course['path'].'/work';
    $work_data_url = $base_work_dir.$work_data['url'];
    $check = Security::check_abs_path($work_data_url.'/', $base_work_dir.'/');
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $TSTDPUBASG = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
    $t_agenda = Database::get_course_table(TABLE_AGENDA);
    $course_id = api_get_course_int_id();
    $sessionId = api_get_session_id();

    if (!empty($work_data['url'])) {
        if ($check) {
            $consideredWorkingTime = api_get_configuration_value('considered_working_time');
            if (!empty($consideredWorkingTime)) {
                $fieldValue = new ExtraFieldValue('work');
                $resultExtra = $fieldValue->getAllValuesForAnItem(
                    $work_data['id'],
                    true
                );

                $workingTime = null;
                foreach ($resultExtra as $field) {
                    $field = $field['value'];
                    if ($consideredWorkingTime == $field->getField()->getVariable()) {
                        $workingTime = $field->getValue();

                        break;
                    }
                }

                $courseUsers = CourseManager::get_user_list_from_course_code($_course['code'], $sessionId);
                if (!empty($workingTime)) {
                    foreach ($courseUsers as $user) {
                        $userWorks = get_work_user_list(
                            0,
                            100,
                            null,
                            null,
                            $work_data['id'],
                            null,
                            $user['user_id'],
                            false,
                            $course_id,
                            $sessionId
                        );

                        if (count($userWorks) != 1) {
                            continue;
                        }
                        Event::eventRemoveVirtualCourseTime(
                            $course_id,
                            $user['user_id'],
                            $sessionId,
                            $workingTime,
                            $work_data['iid']
                        );
                    }
                }
            }

            // Deleting all contents inside the folder
            $sql = "UPDATE $table SET active = 2
                    WHERE c_id = $course_id AND filetype = 'folder' AND id = $id";
            Database::query($sql);

            $sql = "UPDATE $table SET active = 2
                    WHERE c_id = $course_id AND parent_id = $id";
            Database::query($sql);

            $new_dir = $work_data_url.'_DELETED_'.$id;

            if (api_get_setting('permanently_remove_deleted_files') == 'true') {
                my_delete($work_data_url);
            } else {
                if (file_exists($work_data_url)) {
                    rename($work_data_url, $new_dir);
                }
            }

            // Gets calendar_id from student_publication_assigment
            $sql = "SELECT add_to_calendar FROM $TSTDPUBASG
                    WHERE c_id = $course_id AND publication_id = $id";
            $res = Database::query($sql);
            $calendar_id = Database::fetch_row($res);

            // delete from agenda if it exists
            if (!empty($calendar_id[0])) {
                $sql = "DELETE FROM $t_agenda
                        WHERE c_id = $course_id AND id = '".$calendar_id[0]."'";
                Database::query($sql);
            }
            $sql = "DELETE FROM $TSTDPUBASG
                    WHERE c_id = $course_id AND publication_id = $id";
            Database::query($sql);

            Skill::deleteSkillsFromItem($id, ITEM_TYPE_STUDENT_PUBLICATION);

            Event::addEvent(
                LOG_WORK_DIR_DELETE,
                LOG_WORK_DATA,
                [
                    'id' => $work_data['id'],
                    'url' => $work_data['url'],
                    'title' => $work_data['title'],
                ],
                null,
                api_get_user_id(),
                api_get_course_int_id(),
                $sessionId
            );

            $linkInfo = GradebookUtils::isResourceInCourseGradebook(
                api_get_course_id(),
                3,
                $id,
                api_get_session_id()
            );
            $link_id = $linkInfo['id'];
            if ($linkInfo !== false) {
                GradebookUtils::remove_resource_from_course_gradebook($link_id);
            }

            return true;
        }
    }
}

/**
 * Get the path of a document in the student_publication table (path relative to the course directory).
 *
 * @param int $id
 *
 * @return string Path (or -1 on error)
 */
function get_work_path($id)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $course_id = api_get_course_int_id();
    $sql = 'SELECT url FROM '.$table.'
            WHERE c_id = '.$course_id.' AND id='.(int) $id;
    $res = Database::query($sql);
    if (Database::num_rows($res)) {
        $row = Database::fetch_array($res);

        return $row['url'];
    }

    return -1;
}

/**
 * Update the url of a work in the student_publication table.
 *
 * @param int    $id        of the work to update
 * @param string $new_path  Destination directory where the work has been moved (must end with a '/')
 * @param int    $parent_id
 *
 * @return mixed Int -1 on error, sql query result on success
 */
function updateWorkUrl($id, $new_path, $parent_id)
{
    if (empty($id)) {
        return -1;
    }
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $course_id = api_get_course_int_id();
    $id = (int) $id;
    $parent_id = (int) $parent_id;

    $sql = "SELECT * FROM $table
            WHERE c_id = $course_id AND id = $id";
    $res = Database::query($sql);
    if (Database::num_rows($res) != 1) {
        return -1;
    } else {
        $row = Database::fetch_array($res);
        $filename = basename($row['url']);
        $new_url = $new_path.$filename;
        $new_url = Database::escape_string($new_url);

        $sql = "UPDATE $table SET
                   url = '$new_url',
                   parent_id = '$parent_id'
                WHERE c_id = $course_id AND id = $id";

        return Database::query($sql);
    }
}

/**
 * Update the url of a dir in the student_publication table.
 *
 * @param array  $work_data work original data
 * @param string $newPath   Example: "folder1"
 *
 * @return bool
 */
function updateDirName($work_data, $newPath)
{
    $course_id = $work_data['c_id'];
    $work_id = (int) ($work_data['iid']);
    $oldPath = $work_data['url'];
    $originalNewPath = Database::escape_string($newPath);
    $newPath = Database::escape_string($newPath);
    $newPath = api_replace_dangerous_char($newPath);
    $newPath = disable_dangerous_file($newPath);

    if ($oldPath == '/'.$newPath) {
        return true;
    }

    if (!empty($newPath)) {
        $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
        $sql = "UPDATE $table SET
                    title = '".$originalNewPath."'
                WHERE
                    c_id = $course_id AND
                    iid = $work_id";
        Database::query($sql);
    }
}

/**
 * returns all the javascript that is required for easily
 * validation when you create a work
 * this goes into the $htmlHeadXtra[] array.
 */
function to_javascript_work()
{
    return '<script>
        function updateDocumentTitle(value) {
            var temp = value.indexOf("/");
            //linux path
            if(temp != -1){
                temp=value.split("/");
            } else {
                temp=value.split("\\\");
            }

            var fullFilename = temp[temp.length - 1];
            var baseFilename = fullFilename;

            // get file extension
            var fileExtension = "";
            if (fullFilename.match(/\..+/)) {
                fileInfo = fullFilename.match(/(.*)\.([^.]+)$/);
                if (fileInfo.length > 1) {
                    fileExtension = "."+fileInfo[fileInfo.length - 1];
                    baseFilename = fileInfo[fileInfo.length - 2];
                }
            }

            document.getElementById("file_upload").value = baseFilename;
            document.getElementById("file_extension").value = fileExtension;
            $("#contains_file_id").attr("value", 1);
        }
        function setFocus() {
            $("#work_title").focus();
        }

        $(function() {
            setFocus();
            var checked = $("#expiry_date").attr("checked");
            if (checked) {
                $("#option2").show();
            } else {
                $("#option2").hide();
            }

            var checkedEndDate = $("#end_date").attr("checked");
            if (checkedEndDate) {
                $("#option3").show();
                $("#ends_on").attr("checked", true);
            } else {
                $("#option3").hide();
                $("#ends_on").attr("checked", false);
            }

            $("#expiry_date").click(function() {
                $("#option2").toggle();
            });

            $("#end_date").click(function() {
                $("#option3").toggle();
            });
        });
    </script>';
}

/**
 * Gets the id of a student publication with a given path.
 *
 * @param string $path
 *
 * @return true if is found / false if not found
 */
// TODO: The name of this function does not fit with the kind of information it returns.
// Maybe check_work_id() or is_work_id()?
function get_work_id($path)
{
    $TBL_STUDENT_PUBLICATION = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $TBL_PROP_TABLE = Database::get_course_table(TABLE_ITEM_PROPERTY);
    $course_id = api_get_course_int_id();
    $path = Database::escape_string($path);

    if (api_is_allowed_to_edit()) {
        $sql = "SELECT work.id
                FROM $TBL_STUDENT_PUBLICATION AS work, $TBL_PROP_TABLE AS props
                WHERE
                    props.c_id = $course_id AND
                    work.c_id = $course_id AND
                    props.tool='work' AND
                    work.id=props.ref AND
                    work.url LIKE 'work/".$path."%' AND
                    work.filetype='file' AND
                    props.visibility<>'2'";
    } else {
        $sql = "SELECT work.id
                FROM $TBL_STUDENT_PUBLICATION AS work, $TBL_PROP_TABLE AS props
                WHERE
                    props.c_id = $course_id AND
                    work.c_id = $course_id AND
                    props.tool='work' AND
                    work.id=props.ref AND
                    work.url LIKE 'work/".$path."%' AND
                    work.filetype='file' AND
                    props.visibility<>'2' AND
                    props.lastedit_user_id = '".api_get_user_id()."'";
    }
    $result = Database::query($sql);
    $num_rows = Database::num_rows($result);

    if ($result && $num_rows > 0) {
        return true;
    } else {
        return false;
    }
}

/**
 * @param int $work_id
 * @param int $onlyMeUserId show only my works
 * @param int $notMeUserId  show works from everyone except me
 *
 * @return int
 */
function get_count_work($work_id, $onlyMeUserId = null, $notMeUserId = null)
{
    $work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $iprop_table = Database::get_course_table(TABLE_ITEM_PROPERTY);
    $user_table = Database::get_main_table(TABLE_MAIN_USER);

    $is_allowed_to_edit = api_is_allowed_to_edit(null, true) || api_is_coach();
    $session_id = api_get_session_id();
    $condition_session = api_get_session_condition(
        $session_id,
        true,
        false,
        'work.session_id'
    );

    $group_id = api_get_group_id();
    $course_info = api_get_course_info();
    $course_id = $course_info['real_id'];
    $work_id = (int) $work_id;

    $groupIid = 0;
    if ($group_id) {
        $groupInfo = GroupManager::get_group_properties($group_id);
        if ($groupInfo && isset($groupInfo['iid'])) {
            $groupIid = (int) $groupInfo['iid'];
        }
    }

    if (!empty($group_id)) {
        // set to select only messages posted by the user's group
        $extra_conditions = " work.post_group_id = '".$groupIid."' ";
    } else {
        $extra_conditions = " (work.post_group_id = '0' or work.post_group_id IS NULL) ";
    }

    if ($is_allowed_to_edit) {
        $extra_conditions .= ' AND work.active IN (0, 1) ';
    } else {
        $extra_conditions .= ' AND work.active IN (0, 1) AND accepted = 1';
        if (isset($course_info['show_score']) && $course_info['show_score'] == 1) {
            $extra_conditions .= " AND work.user_id = ".api_get_user_id()." ";
        } else {
            $extra_conditions .= '';
        }
    }

    $extra_conditions .= " AND parent_id  = ".$work_id."  ";
    $where_condition = null;
    if (!empty($notMeUserId)) {
        $where_condition .= " AND u.user_id <> ".intval($notMeUserId);
    }

    if (!empty($onlyMeUserId)) {
        $where_condition .= " AND u.user_id =  ".intval($onlyMeUserId);
    }

    $sql = "SELECT count(*) as count
            FROM $iprop_table prop
            INNER JOIN $work_table work
            ON (
                prop.ref = work.id AND
                prop.c_id = $course_id AND
                prop.tool='work' AND
                prop.visibility <> 2 AND
                work.c_id = $course_id
            )
            INNER JOIN $user_table u
            ON (work.user_id = u.user_id)
            WHERE $extra_conditions $where_condition $condition_session";

    $result = Database::query($sql);

    $users_with_work = 0;
    if (Database::num_rows($result)) {
        $result = Database::fetch_array($result);
        $users_with_work = $result['count'];
    }

    return $users_with_work;
}

/**
 * @param int    $start
 * @param int    $limit
 * @param string $column
 * @param string $direction
 * @param string $where_condition
 * @param bool   $getCount
 *
 * @return array
 */
function getWorkListStudent(
    $start,
    $limit,
    $column,
    $direction,
    $where_condition,
    $getCount = false
) {
    $workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $workTableAssignment = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
    $courseInfo = api_get_course_info();
    $course_id = $courseInfo['real_id'];
    $session_id = api_get_session_id();
    $condition_session = api_get_session_condition($session_id);
    $group_id = api_get_group_id();
    $userId = api_get_user_id();

    $isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(
        $userId,
        $courseInfo
    );

    if (!in_array($direction, ['asc', 'desc'])) {
        $direction = 'desc';
    }
    if (!empty($where_condition)) {
        $where_condition = ' AND '.$where_condition;
    }

    $column = !empty($column) ? Database::escape_string($column) : 'sent_date';
    $start = (int) $start;
    $limit = (int) $limit;

    $groupIid = 0;
    if ($group_id) {
        $groupInfo = GroupManager::get_group_properties($group_id);
        if ($groupInfo) {
            $groupIid = (int) $groupInfo['iid'];
        }
    }

    if (!empty($groupIid)) {
        $group_query = " WHERE w.c_id = $course_id AND post_group_id = $groupIid";
        $subdirs_query = 'AND parent_id = 0';
    } else {
        $group_query = " WHERE w.c_id = $course_id AND (post_group_id = '0' or post_group_id is NULL)  ";
        $subdirs_query = 'AND parent_id = 0';
    }

    $active_condition = ' AND active IN (1, 0)';

    if ($getCount) {
        $select = 'SELECT count(w.id) as count ';
    } else {
        $select = 'SELECT w.*, a.expires_on, expires_on, ends_on, enable_qualification ';
    }

    $sql = "$select
            FROM $workTable w
            LEFT JOIN $workTableAssignment a
            ON (a.publication_id = w.id AND a.c_id = w.c_id)
                $group_query
                $subdirs_query
                $active_condition
                $condition_session
                $where_condition
            ";

    $sql .= " ORDER BY `$column` $direction ";

    if (!empty($start) && !empty($limit)) {
        $sql .= " LIMIT $start, $limit";
    }

    $result = Database::query($sql);

    if ($getCount) {
        $row = Database::fetch_array($result);

        return $row['count'];
    }

    $works = [];
    $url = api_get_path(WEB_CODE_PATH).'work/work_list.php?'.api_get_cidreq();
    if ($isDrhOfCourse) {
        $url = api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq();
    }

    $urlOthers = api_get_path(WEB_CODE_PATH).'work/work_list_others.php?'.api_get_cidreq().'&id=';
    while ($work = Database::fetch_array($result, 'ASSOC')) {
        $isSubscribed = userIsSubscribedToWork($userId, $work['id'], $course_id);
        if ($isSubscribed == false) {
            continue;
        }

        $visibility = api_get_item_visibility($courseInfo, 'work', $work['id'], $session_id);

        if ($visibility != 1) {
            continue;
        }

        $work['type'] = Display::return_icon('work.png');
        $work['expires_on'] = empty($work['expires_on']) ? null : api_get_local_time($work['expires_on']);

        if (empty($work['title'])) {
            $work['title'] = basename($work['url']);
        }

        $whereCondition = " AND u.user_id = $userId ";

        $workList = get_work_user_list(
            0,
            1000,
            null,
            null,
            $work['id'],
            $whereCondition
        );

        $count = getTotalWorkComment($workList, $courseInfo);
        $lastWork = getLastWorkStudentFromParentByUser($userId, $work, $courseInfo);

        if (!is_null($count) && !empty($count)) {
            $urlView = api_get_path(WEB_CODE_PATH).'work/view.php?id='.$lastWork['id'].'&'.api_get_cidreq();

            $feedback = '&nbsp;'.Display::url(
                Display::returnFontAwesomeIcon('comments-o'),
                $urlView,
                ['title' => get_lang('View')]
            );

            $work['feedback'] = ' '.Display::label($count.' '.get_lang('Feedback'), 'info').$feedback;
        }

        if (!empty($lastWork)) {
            $work['last_upload'] = (!empty($lastWork['qualification'])) ? $lastWork['qualification_rounded'].' - ' : '';
            $work['last_upload'] .= api_get_local_time($lastWork['sent_date']);
        }

        $work['title'] = Display::url($work['title'], $url.'&id='.$work['id']);
        $work['others'] = Display::url(
            Display::return_icon('group.png', get_lang('Others')),
            $urlOthers.$work['id']
        );
        $works[] = $work;
    }

    return $works;
}

/**
 * @param int    $start
 * @param int    $limit
 * @param string $column
 * @param string $direction
 * @param string $where_condition
 * @param bool   $getCount
 * @param int    $withResults
 *
 * @return array
 */
function getAllWorkListStudent(
    $start,
    $limit,
    $column,
    $direction,
    $where_condition,
    $getCount = false,
    $withResults = 1
) {
    $workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $workTableAssignment = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
    $userId = api_get_user_id();

    if (empty($userId)) {
        return [];
    }

    $courses = CourseManager::get_courses_list_by_user_id($userId, true);

    if (!empty($where_condition)) {
        $where_condition = ' AND '.$where_condition;
    }

    if (!in_array($direction, ['asc', 'desc'])) {
        $direction = 'desc';
    }

    $column = !empty($column) ? Database::escape_string($column) : 'sent_date';
    $start = (int) $start;
    $limit = (int) $limit;
    $courseQuery = [];
    $courseList = [];
    foreach ($courses as $course) {
        $course_id = $course['real_id'];
        $courseInfo = api_get_course_info_by_id($course_id);
        $session_id = isset($course['session_id']) ? $course['session_id'] : 0;
        $conditionSession = api_get_session_condition($session_id, true, false, 'w.session_id');
        $parentCondition = '';
        if ($withResults) {
            $parentCondition = 'AND ww.parent_id is NOT NULL';
        }
        $courseQuery[] = " (w.c_id = $course_id $conditionSession $parentCondition )";
        $courseList[$course_id] = $courseInfo;
    }

    $courseQueryToString = implode(' OR ', $courseQuery);

    if ($getCount) {
        if (empty($courseQuery)) {
            return 0;
        }
        $select = 'SELECT count(DISTINCT(w.id)) as count ';
    } else {
        if (empty($courseQuery)) {
            return [];
        }
        $select = 'SELECT DISTINCT
                        w.title,
                        w.url,
                        w.id,
                        w.c_id,
                        w.session_id,
                        a.expires_on,
                        a.ends_on,
                        a.enable_qualification,
                        w.qualification,
                        a.publication_id';
    }

    $checkSentWork = " LEFT JOIN $workTable ww
                       ON (ww.c_id = w.c_id AND ww.parent_id = w.id AND ww.user_id = $userId ) ";
    $where = ' AND ww.url IS NULL ';
    $expirationCondition = " AND (a.expires_on IS NULL OR a.expires_on > '".api_get_utc_datetime()."') ";
    if ($withResults) {
        $where = '';
        $checkSentWork = " LEFT JOIN $workTable ww
                           ON (
                            ww.c_id = w.c_id AND
                            ww.parent_id = w.id AND
                            ww.user_id = $userId AND
                            a.expires_on IS NULL AND
                            ww.parent_id is NOT NULL
                        ) ";
        $expirationCondition = " OR (
                ww.parent_id is NULL AND
                a.expires_on IS NOT NULL AND
                a.expires_on < '".api_get_utc_datetime()."'
            ) ";
    }

    $sql = "$select
            FROM $workTable w
            LEFT JOIN $workTableAssignment a
            ON (a.publication_id = w.id AND a.c_id = w.c_id)
            $checkSentWork
            WHERE
                w.parent_id = 0 AND
                w.active IN (1, 0) AND
                ($courseQueryToString)
                $where_condition
                $expirationCondition
                $where
            ";

    $sql .= " ORDER BY `$column` $direction ";

    if (!empty($start) && !empty($limit)) {
        $sql .= " LIMIT $start, $limit";
    }

    $result = Database::query($sql);

    if ($getCount) {
        $row = Database::fetch_array($result);

        if ($row) {
            return (int) $row['count'];
        }

        return 0;
    }

    $works = [];
    while ($work = Database::fetch_array($result, 'ASSOC')) {
        $courseId = $work['c_id'];
        $courseInfo = $courseList[$work['c_id']];
        $courseCode = $courseInfo['code'];
        $sessionId = $work['session_id'];

        $cidReq = api_get_cidreq_params($courseCode, $sessionId);
        $url = api_get_path(WEB_CODE_PATH).'work/work_list.php?'.$cidReq;
        $isSubscribed = userIsSubscribedToWork($userId, $work['id'], $courseId);
        if ($isSubscribed == false) {
            continue;
        }

        $visibility = api_get_item_visibility($courseInfo, 'work', $work['id'], $sessionId);

        if ($visibility != 1) {
            continue;
        }

        $work['type'] = Display::return_icon('work.png');
        $work['expires_on'] = empty($work['expires_on']) ? null : api_get_local_time($work['expires_on']);

        if (empty($work['title'])) {
            $work['title'] = basename($work['url']);
        }

        if ($withResults) {
            $whereCondition = " AND u.user_id = $userId ";
            $workList = get_work_user_list(
                0,
                1000,
                null,
                null,
                $work['id'],
                $whereCondition,
                null,
                false,
                $courseId,
                $sessionId
            );

            $count = getTotalWorkComment($workList, $courseInfo);
            $lastWork = getLastWorkStudentFromParentByUser($userId, $work, $courseInfo);

            if (!is_null($count) && !empty($count)) {
                $urlView = api_get_path(WEB_CODE_PATH).'work/view.php?id='.$lastWork['id'].'&'.$cidReq;

                $feedback = '&nbsp;'.Display::url(
                        Display::returnFontAwesomeIcon('comments-o'),
                        $urlView,
                        ['title' => get_lang('View')]
                    );

                $work['feedback'] = ' '.Display::label($count.' '.get_lang('Feedback'), 'info').$feedback;
            }

            if (!empty($lastWork)) {
                $work['last_upload'] = (!empty($lastWork['qualification'])) ? $lastWork['qualification_rounded'].' - ' : '';
                $work['last_upload'] .= api_get_local_time($lastWork['sent_date']);
            }
        }

        $work['title'] = Display::url($work['title'], $url.'&id='.$work['id']);
        $works[] = $work;
    }

    return $works;
}

function getWorkListTeacherQuery(
    $courseId,
    $sessionId,
    $groupId,
    $start,
    $limit,
    $column,
    $direction,
    $whereCondition,
    $getCount = false
): ?Statement {
    $workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $workTableAssignment = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);

    $condition_session = api_get_session_condition($sessionId);
    $groupIid = 0;
    if ($groupId) {
        $groupInfo = GroupManager::get_group_properties($groupId);
        $groupIid = $groupInfo['iid'];
    }
    $groupIid = (int) $groupIid;

    $select = $getCount
        ? "count(w.id) as count"
        : "w.*, a.expires_on, expires_on, ends_on, enable_qualification";

    $sql = "SELECT $select
        FROM $workTable w
        LEFT JOIN $workTableAssignment a
            ON (a.publication_id = w.id AND a.c_id = w.c_id)
        WHERE
            w.c_id = $courseId
            $condition_session AND
            active IN (0, 1) AND
            parent_id = 0 AND
            post_group_id = $groupIid
            $whereCondition
        ORDER BY `$column` $direction";

    if (!empty($start) && !empty($limit)) {
        $sql .= " LIMIT $start, $limit";
    }

    return Database::query($sql);
}

/**
 * @return int|array
 */
function getWorkListTeacherData(
    $courseId,
    $sessionId,
    $groupId,
    $start,
    $limit,
    $column,
    $direction,
    $whereCondition,
    $getCount = false
) {
    $result = getWorkListTeacherQuery(
        $courseId,
        $sessionId,
        $groupId,
        $start,
        $limit,
        $column,
        $direction,
        $whereCondition,
        $getCount
    );

    if ($getCount) {
        $row = Database::fetch_array($result);

        return (int) $row['count'];
    }

    $works = [];

    while ($work = Database::fetch_array($result, 'ASSOC')) {
        $workId = $work['id'];
        $work['expires_on'] = empty($work['expires_on']) ? null : api_get_local_time($work['expires_on']);
        $work['ends_on'] = empty($work['expires_on']) ? null : api_get_local_time($work['ends_on']);

        $countUniqueAttempts = getUniqueStudentAttemptsTotal($workId, $groupId, $courseId, $sessionId);
        $totalUsers = getStudentSubscribedToWork($workId, $courseId, $groupId, $sessionId, true);

        $work['count_unique_attempts'] = $countUniqueAttempts;
        $work['amount'] = Display::label(
            "$countUniqueAttempts/$totalUsers",
            'success'
        );

        if (empty($work['title'])) {
            $work['title'] = basename($work['url']);
        }

        $work['sent_date'] = api_get_local_time($work['sent_date']);

        $works[] = $work;
    }

    return $works;
}

/**
 * @param int    $start
 * @param int    $limit
 * @param string $column
 * @param string $direction
 * @param string $where_condition
 * @param bool   $getCount
 *
 * @return int|array
 */
function getWorkListTeacher(
    $start,
    $limit,
    $column,
    $direction,
    $where_condition,
    $getCount = false,
    $courseInfoParam = []
) {
    $courseInfo = api_get_course_info();
    $course_id = api_get_course_int_id();
    if (!empty($courseInfoParam)) {
        $courseInfo = $courseInfoParam;
        $course_id = $courseInfoParam['real_id'];
    }

    $session_id = api_get_session_id();
    $group_id = api_get_group_id();

    $is_allowed_to_edit = api_is_allowed_to_edit() || api_is_coach();
    if (!in_array($direction, ['asc', 'desc'])) {
        $direction = 'desc';
    }
    if (!empty($where_condition)) {
        $where_condition = ' AND '.$where_condition;
    }

    $column = !empty($column) ? Database::escape_string($column) : 'sent_date';
    $start = (int) $start;
    $limit = (int) $limit;

    // Get list from database
    if (!$is_allowed_to_edit) {
        return $getCount ? 0 : [];
    }

    $result = getWorkListTeacherData(
        $course_id,
        $session_id,
        $group_id,
        $start,
        $limit,
        $column,
        $direction,
        $where_condition,
        $getCount
    );

    if (is_int($result)) {
        return $result;
    }

    $url = api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq();
    $blockEdition = api_get_configuration_value('block_student_publication_edition');

    return array_map(
        function (array $work) use ($courseInfo, $session_id, $blockEdition, $url) {
            $workId = $work['id'];
            $work['type'] = Display::return_icon('work.png');

            $visibility = api_get_item_visibility($courseInfo, 'work', $workId, $session_id);

            if ($visibility == 1) {
                $icon = 'visible.png';
                $text = get_lang('Visible');
                $action = 'invisible';
                $class = '';
            } else {
                $icon = 'invisible.png';
                $text = get_lang('Invisible');
                $action = 'visible';
                $class = 'muted';
            }

            $visibilityLink = Display::url(
                Display::return_icon($icon, $text),
                api_get_path(WEB_CODE_PATH)."work/work.php?id=$workId&action=$action&".api_get_cidreq()
            );

            $work['title'] = Display::url($work['title'], $url.'&id='.$workId, ['class' => $class]);
            $work['title'] .= ' '.Display::label(get_count_work($work['id']), 'success');

            if ($blockEdition && !api_is_platform_admin()) {
                $editLink = '';
            } else {
                $editLink = Display::url(
                    Display::return_icon('edit.png', get_lang('Edit')),
                    api_get_path(WEB_CODE_PATH).'work/edit_work.php?id='.$workId.'&'.api_get_cidreq()
                );
            }

            $correctionLink = Display::url(
                Display::return_icon('upload_package.png', get_lang('UploadCorrections')),
                api_get_path(WEB_CODE_PATH).'work/upload_corrections.php?'.api_get_cidreq().'&id='.$workId
            );

            if ($work['count_unique_attempts'] > 0) {
                $downloadLink = Display::url(
                    Display::return_icon('save_pack.png', get_lang('Save')),
                    api_get_path(WEB_CODE_PATH)."work/downloadfolder.inc.php?id=$workId&".api_get_cidreq()
                );
            } else {
                $downloadLink = Display::url(
                    Display::return_icon('save_pack_na.png', get_lang('Save')),
                    '#'
                );
            }
            // Remove Delete Work Button from action List
            // Because removeXSS "removes" the onClick JS Event to do the action (See model.ajax.php - Line 1639)
            // But still can use the another jqgrid button to remove works (trash icon)
            //
            // $deleteUrl = api_get_path(WEB_CODE_PATH).'work/work.php?id='.$workId.'&action=delete_dir&'.api_get_cidreq();
            // $deleteLink = '<a href="#" onclick="showConfirmationPopup(this, \'' . $deleteUrl . '\' ) " >' .
            //     Display::return_icon(
            //         'delete.png',
            //         get_lang('Delete'),
            //         [],
            //         ICON_SIZE_SMALL
            //     ) . '</a>';

            if (!api_is_allowed_to_edit()) {
                // $deleteLink = null;
                $editLink = null;
            }
            $work['actions'] = implode(PHP_EOL, [$visibilityLink, $correctionLink, $downloadLink, $editLink]);

            return $work;
        },
        $result
    );
}

/**
 * @param int    $start
 * @param int    $limit
 * @param string $column
 * @param string $direction
 * @param int    $workId
 * @param int    $studentId
 * @param string $whereCondition
 * @param bool   $getCount
 *
 * @return array
 */
function get_work_user_list_from_documents(
    $start,
    $limit,
    $column,
    $direction,
    $workId,
    $studentId = null,
    $whereCondition = '',
    $getCount = false
) {
    if ($getCount) {
        $select1 = ' SELECT count(u.user_id) as count ';
        $select2 = ' SELECT count(u.user_id) as count ';
    } else {
        $select1 = ' SELECT DISTINCT
                        u.firstname,
                        u.lastname,
                        u.user_id,
                        w.title,
                        w.parent_id,
                        w.document_id document_id,
                        w.id, qualification,
                        qualificator_id,
                        w.sent_date,
                        w.contains_file,
                        w.url,
                        w.url_correction
                    ';
        $select2 = ' SELECT DISTINCT
                        u.firstname, u.lastname,
                        u.user_id,
                        d.title,
                        w.parent_id,
                        d.id document_id,
                        0,
                        0,
                        0,
                        w.sent_date,
                        w.contains_file,
                        w.url,
                        w.url_correction
                    ';
    }

    $documentTable = Database::get_course_table(TABLE_DOCUMENT);
    $workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $workRelDocument = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_DOCUMENT);
    $userTable = Database::get_main_table(TABLE_MAIN_USER);

    $courseId = api_get_course_int_id();
    $sessionId = api_get_session_id();

    if (empty($studentId)) {
        $studentId = api_get_user_id();
    }

    $studentId = (int) $studentId;
    $workId = (int) $workId;

    $userCondition = " AND u.user_id = $studentId ";
    $sessionCondition = api_get_session_condition($sessionId, true, false, 'w.session_id');
    $workCondition = " AND w_rel.work_id = $workId";
    $workParentCondition = " AND w.parent_id = $workId";

    $sql = "(
                $select1 FROM $userTable u
                INNER JOIN $workTable w
                ON (u.user_id = w.user_id AND w.active IN (0, 1) AND w.filetype = 'file')
                WHERE
                    w.c_id = $courseId
                    $userCondition
                    $sessionCondition
                    $whereCondition
                    $workParentCondition
            ) UNION (
                $select2 FROM $workTable w
                INNER JOIN $workRelDocument w_rel
                ON (w_rel.work_id = w.id AND w.active IN (0, 1) AND w_rel.c_id = w.c_id)
                INNER JOIN $documentTable d
                ON (w_rel.document_id = d.id AND d.c_id = w.c_id)
                INNER JOIN $userTable u ON (u.user_id = $studentId)
                WHERE
                    w.c_id = $courseId
                    $workCondition
                    $sessionCondition AND
                    d.id NOT IN (
                        SELECT w.document_id id
                        FROM $workTable w
                        WHERE
                            user_id = $studentId AND
                            c_id = $courseId AND
                            filetype = 'file' AND
                            active IN (0, 1)
                            $sessionCondition
                            $workParentCondition
                    )
            )";

    $start = (int) $start;
    $limit = (int) $limit;

    $direction = in_array(strtolower($direction), ['desc', 'asc']) ? $direction : 'desc';
    $column = Database::escape_string($column);

    if ($getCount) {
        $result = Database::query($sql);
        $result = Database::fetch_array($result);

        return $result['count'];
    }

    $sql .= " ORDER BY `$column` $direction";
    $sql .= " LIMIT $start, $limit";

    $result = Database::query($sql);

    $currentUserId = api_get_user_id();
    $work_data = get_work_data_by_id($workId);
    $qualificationExists = false;
    if (!empty($work_data['qualification']) && intval($work_data['qualification']) > 0) {
        $qualificationExists = true;
    }

    $urlAdd = api_get_path(WEB_CODE_PATH).'work/upload_from_template.php?'.api_get_cidreq();
    $urlEdit = api_get_path(WEB_CODE_PATH).'work/edit.php?'.api_get_cidreq();
    $urlDelete = api_get_path(WEB_CODE_PATH).'work/work_list.php?action=delete&'.api_get_cidreq();
    $urlView = api_get_path(WEB_CODE_PATH).'work/view.php?'.api_get_cidreq();
    $urlDownload = api_get_path(WEB_CODE_PATH).'work/download.php?'.api_get_cidreq();

    $correctionIcon = Display::return_icon(
        'check-circle.png',
        get_lang('Correction'),
        null,
        ICON_SIZE_SMALL
    );
    $editIcon = Display::return_icon('edit.png', get_lang('Edit'));
    $addIcon = Display::return_icon('add.png', get_lang('Add'));
    $deleteIcon = Display::return_icon('delete.png', get_lang('Delete'));
    $viewIcon = Display::return_icon('default.png', get_lang('View'));
    $saveIcon = Display::return_icon(
        'save.png',
        get_lang('Save'),
        [],
        ICON_SIZE_SMALL
    );
    $allowEdition = api_get_course_setting('student_delete_own_publication') == 1;
    $cidReq = api_get_cidreq();
    $workList = [];
    while ($row = Database::fetch_array($result, 'ASSOC')) {
        $userId = $row['user_id'];
        $documentId = $row['document_id'];
        $itemId = $row['id'];
        $addLinkShowed = false;

        if (empty($documentId)) {
            $url = $urlEdit.'&item_id='.$row['id'].'&id='.$workId;
            $editLink = Display::url($editIcon, $url);
            if (1 != $allowEdition) {
                $editLink = null;
            }
        } else {
            $documentToWork = getDocumentToWorkPerUser($documentId, $workId, $courseId, $sessionId, $userId);

            if (empty($documentToWork)) {
                $url = $urlAdd.'&document_id='.$documentId.'&id='.$workId;
                $editLink = Display::url($addIcon, $url);
                $addLinkShowed = true;
            } else {
                $row['title'] = $documentToWork['title'];
                $row['sent_date'] = $documentToWork['sent_date'];
                $newWorkId = $documentToWork['id'];
                $url = $urlEdit.'&item_id='.$newWorkId.'&id='.$workId;
                $editLink = Display::url($editIcon, $url);

                if (1 != $allowEdition) {
                    $editLink = '';
                }
            }
        }

        $downloadLink = '';
        // If URL is present then there's a file to download keep BC.
        if ($row['contains_file'] || !empty($row['url'])) {
            $downloadLink = Display::url($saveIcon, $urlDownload.'&id='.$row['id']).'&nbsp;';
        }

        $viewLink = '';
        if (!empty($itemId)) {
            $viewLink = Display::url($viewIcon, $urlView.'&id='.$itemId);
        }

        $deleteLink = '';
        if ($allowEdition == 1 && !empty($itemId)) {
            $deleteLink = Display::url($deleteIcon, $urlDelete.'&item_id='.$itemId.'&id='.$workId);
        }

        $row['type'] = null;
        if ($qualificationExists) {
            if (empty($row['qualificator_id'])) {
                $status = Display::label(get_lang('NotRevised'), 'warning');
            } else {
                $status = Display::label(get_lang('Revised'), 'success');
            }
            $row['qualificator_id'] = $status;
        }

        $hasCorrection = '';
        if (!empty($row['url_correction'])) {
            $hasCorrection = '&nbsp;'.Display::url(
                $correctionIcon,
                api_get_path(WEB_CODE_PATH).'work/download.php?id='.$itemId.'&'.$cidReq.'&correction=1'
            );
        }

        $qualification_string = '';
        if ($qualificationExists) {
            if ($row['qualification'] == '') {
                $qualification_string = Display::label('-');
            } else {
                $qualification_string = formatWorkScore($row['qualification'], $work_data['qualification']);
            }
        }

        $row['qualification'] = $qualification_string.$hasCorrection;

        /*if (!empty($row['qualification'])) {
            $row['qualification'] = Display::label($row['qualification'], 'info');
        }*/

        if (!empty($row['sent_date'])) {
            $row['sent_date'] = Display::dateToStringAgoAndLongDate($row['sent_date']);
        }

        if ($userId == $currentUserId) {
            $row['actions'] = $downloadLink.$viewLink.$editLink.$deleteLink;
        }

        if ($addLinkShowed) {
            $row['qualification'] = '';
            $row['qualificator_id'] = '';
        }

        $workList[] = $row;
    }

    return $workList;
}

/**
 * @param int    $start
 * @param int    $limit
 * @param int    $column
 * @param string $direction
 * @param int    $work_id
 * @param string $whereCondition
 * @param int    $studentId
 * @param bool   $getCount
 * @param int    $courseId
 * @param int    $sessionId
 *
 * @return array
 */
function get_work_user_list(
    $start,
    $limit,
    $column,
    $direction,
    $work_id,
    $whereCondition = '',
    $studentId = null,
    $getCount = false,
    $courseId = 0,
    $sessionId = 0,
    $shortTitle = true
) {
    $work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $user_table = Database::get_main_table(TABLE_MAIN_USER);

    $session_id = $sessionId ? (int) $sessionId : api_get_session_id();
    $group_id = api_get_group_id();
    $course_info = api_get_course_info();
    $course_info = empty($course_info) ? api_get_course_info_by_id($courseId) : $course_info;
    $course_id = isset($course_info['real_id']) ? $course_info['real_id'] : $courseId;

    $work_id = (int) $work_id;
    $start = (int) $start;
    $limit = (int) $limit;

    $column = !empty($column) ? Database::escape_string($column) : 'sent_date';
    $compilation = null;
    if (api_get_configuration_value('allow_compilatio_tool')) {
        $compilation = new Compilatio();
    }

    if (!in_array($direction, ['asc', 'desc'])) {
        $direction = 'desc';
    }

    $work_data = get_work_data_by_id($work_id, $courseId, $sessionId);
    $is_allowed_to_edit = api_is_allowed_to_edit() || api_is_coach();
    $condition_session = api_get_session_condition(
        $session_id,
        true,
        false,
        'work.session_id'
    );

    $locked = api_resource_is_locked_by_gradebook(
        $work_id,
        LINK_STUDENTPUBLICATION,
        $course_info['code']
    );

    $isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(
        api_get_user_id(),
        $course_info
    );

    $isDrhOfSession = !empty(SessionManager::getSessionFollowedByDrh(api_get_user_id(), $session_id));

    $groupIid = 0;
    if ($group_id) {
        $groupInfo = GroupManager::get_group_properties($group_id);
        if ($groupInfo) {
            $groupIid = $groupInfo['iid'];
        }
    }

    if (!empty($work_data)) {
        if (!empty($group_id)) {
            // set to select only messages posted by the user's group
            $extra_conditions = " work.post_group_id = '".$groupIid."' ";
        } else {
            $extra_conditions = " (work.post_group_id = '0' OR work.post_group_id is NULL) ";
        }

        if ($is_allowed_to_edit || $isDrhOfCourse || $isDrhOfSession) {
            $extra_conditions .= ' AND work.active IN (0, 1) ';
        } else {
            if (isset($course_info['show_score']) &&
                1 == $course_info['show_score']
            ) {
                $extra_conditions .= ' AND (u.user_id = '.api_get_user_id().' AND work.active IN (0, 1)) ';
            } else {
                $extra_conditions .= ' AND work.active IN (0, 1) ';
            }
        }

        $extra_conditions .= " AND parent_id  = $work_id ";

        $select = 'SELECT DISTINCT
                        u.user_id,
                        work.id as id,
                        title as title,
                        description,
                        url,
                        sent_date,
                        contains_file,
                        has_properties,
                        view_properties,
                        qualification,
                        weight,
                        allow_text_assignment,
                        u.firstname,
                        u.lastname,
                        u.username,
                        parent_id,
                        accepted,
                        qualificator_id,
                        url_correction,
                        title_correction
                        ';
        if ($getCount) {
            $select = 'SELECT DISTINCT count(u.user_id) as count ';
        }

        $work_assignment = get_work_assignment_by_id($work_id, $courseId);

        if (!empty($studentId)) {
            $studentId = (int) $studentId;
            $whereCondition .= " AND u.user_id = $studentId ";
        }

        $sql = " $select
                FROM $work_table work
                INNER JOIN $user_table u
                ON (work.user_id = u.user_id)
                WHERE
                    work.c_id = $course_id AND
                    $extra_conditions
                    $whereCondition
                    $condition_session
                    AND u.status != ".INVITEE."
                ORDER BY `$column` $direction";

        if (!empty($start) && !empty($limit)) {
            $sql .= " LIMIT $start, $limit";
        }
        $result = Database::query($sql);
        $works = [];

        if ($getCount) {
            $work = Database::fetch_array($result, 'ASSOC');
            if ($work) {
                return (int) $work['count'];
            }

            return 0;
        }

        $url = api_get_path(WEB_CODE_PATH).'work/';
        $unoconv = api_get_configuration_value('unoconv.binaries');
        $loadingText = addslashes(get_lang('Loading'));
        $uploadedText = addslashes(get_lang('Uploaded'));
        $failsUploadText = addslashes(get_lang('UplNoFileUploaded'));
        $failsUploadIcon = Display::return_icon(
            'closed-circle.png',
            '',
            [],
            ICON_SIZE_TINY
        );
        $saveIcon = Display::return_icon(
            'save.png',
            get_lang('Save'),
            [],
            ICON_SIZE_SMALL
        );

        $correctionIcon = Display::return_icon(
            'check-circle.png',
            get_lang('Correction'),
            null,
            ICON_SIZE_SMALL
        );

        $correctionIconSmall = Display::return_icon(
            'check-circle.png',
            get_lang('Correction'),
            null,
            ICON_SIZE_TINY
        );

        $rateIcon = Display::return_icon(
            'rate_work.png',
            get_lang('CorrectAndRate'),
            [],
            ICON_SIZE_SMALL
        );

        $blockEdition = api_get_configuration_value('block_student_publication_edition');
        $blockScoreEdition = api_get_configuration_value('block_student_publication_score_edition');
        $loading = Display::returnFontAwesomeIcon('spinner', null, true, 'fa-spin');
        $cidReq = api_get_cidreq();

        $qualification_exists = false;
        if (!empty($work_data['qualification']) &&
            intval($work_data['qualification']) > 0
        ) {
            $qualification_exists = true;
        }

        while ($work = Database::fetch_array($result, 'ASSOC')) {
            $item_id = $work['id'];
            $dbTitle = $work['title'];
            // Get the author ID for that document from the item_property table
            $is_author = false;
            $can_read = false;
            $owner_id = $work['user_id'];

            /* Because a bug found when saving items using the api_item_property_update()
               the field $item_property_data['insert_user_id'] is not reliable. */
            if (!$is_allowed_to_edit && $owner_id == api_get_user_id()) {
                $is_author = true;
            }

            if ($course_info['show_score'] == 0) {
                $can_read = true;
            }

            $qualification_string = '';
            if ($qualification_exists) {
                if ($work['qualification'] == '') {
                    $qualification_string = Display::label('-');
                } else {
                    if (empty($work['qualificator_id'])) {
                        $finalScore = '?? / '.$work_data['qualification'];
                        $qualification_string = Display::label($finalScore, 'warning');
                    } else {
                        $qualification_string = formatWorkScore($work['qualification'], $work_data['qualification']);
                    }
                }
            }

            $work['qualification_score'] = $work['qualification'];
            $add_string = '';
            $time_expires = '';
            if (!empty($work_assignment['expires_on'])) {
                $time_expires = api_strtotime(
                    $work_assignment['expires_on'],
                    'UTC'
                );
            }

            if (!empty($work_assignment['expires_on']) &&
                !empty($time_expires) && ($time_expires < api_strtotime($work['sent_date'], 'UTC'))) {
                $add_string = Display::label(get_lang('Expired'), 'important').' - ';
            }

            if (($can_read && $work['accepted'] == '1') ||
                ($is_author && in_array($work['accepted'], ['1', '0'])) ||
                ($is_allowed_to_edit || api_is_drh())
            ) {
                // Firstname, lastname, username
                $work['fullname'] = Display::div(
                    api_get_person_name($work['firstname'], $work['lastname']),
                    ['class' => 'work-name']
                );
                // Title
                $work['title_clean'] = $work['title'];
                $work['title'] = Security::remove_XSS($work['title']);
                if (strlen($work['title']) > 30 && $shortTitle) {
                    $short_title = substr($work['title'], 0, 27).'...';
                    $work['title'] = Display::span($short_title, ['class' => 'work-title', 'title' => $work['title']]);
                } else {
                    $work['title'] = Display::div($work['title'], ['class' => 'work-title']);
                }

                // Type.
                $work['type'] = DocumentManager::build_document_icon_tag('file', $work['url']);

                // File name.
                $linkToDownload = '';
                // If URL is present then there's a file to download keep BC.
                if ($work['contains_file'] || !empty($work['url'])) {
                    $linkToDownload = '<a href="'.$url.'download.php?id='.$item_id.'&'.$cidReq.'">'.$saveIcon.'</a> ';
                }

                $feedback = '';
                $count = getWorkCommentCount($item_id, $course_info);
                if (!is_null($count) && !empty($count)) {
                    if ($qualification_exists) {
                        $feedback .= ' ';
                    }
                    $feedback .= Display::url(
                        $count.' '.Display::returnFontAwesomeIcon('comments-o'),
                        $url.'view.php?'.api_get_cidreq().'&id='.$item_id
                    );
                }

                $correction = '';
                $hasCorrection = '';
                if (!empty($work['url_correction'])) {
                    $hasCorrection = Display::url(
                        $correctionIcon,
                        api_get_path(WEB_CODE_PATH).'work/download.php?id='.$item_id.'&'.$cidReq.'&correction=1'
                    );
                }

                if ($qualification_exists) {
                    $work['qualification'] = $qualification_string.$feedback;
                } else {
                    $work['qualification'] = $qualification_string.$feedback.$hasCorrection;
                }

                $work['qualification_only'] = $qualification_string;

                // Date.
                $work_date = api_get_local_time($work['sent_date']);
                $date = date_to_str_ago($work['sent_date']).' '.$work_date;
                $work['formatted_date'] = $work_date.' '.$add_string;
                $work['expiry_note'] = $add_string;
                $work['sent_date_from_db'] = $work['sent_date'];
                $work['sent_date'] = '<div class="work-date" title="'.$date.'">'.
                    $add_string.' '.Display::dateToStringAgoAndLongDate($work['sent_date']).'</div>';
                $work['status'] = $hasCorrection;
                $work['has_correction'] = $hasCorrection;

                // Actions.
                $action = '';
                if (api_is_allowed_to_edit()) {
                    if ($blockScoreEdition && !api_is_platform_admin() && !empty($work['qualification_score'])) {
                        $rateLink = '';
                    } else {
                        $rateLink = '<a href="'.$url.'view.php?'.$cidReq.'&id='.$item_id.'" title="'.get_lang('View').'">'.
                            $rateIcon.'</a> ';
                    }
                    $action .= $rateLink;

                    if ($unoconv && empty($work['contains_file'])) {
                        $action .= '<a
                            href="'.$url.'work_list_all.php?'.$cidReq.'&id='.$work_id.'&action=export_to_doc&item_id='.$item_id.'"
                            title="'.get_lang('ExportToDoc').'" >'.
                            Display::return_icon('export_doc.png', get_lang('ExportToDoc'), [], ICON_SIZE_SMALL).'</a> ';
                    }

                    $alreadyUploaded = '';
                    if (!empty($work['url_correction'])) {
                        $alreadyUploaded = '<br />'.$work['title_correction'].' '.$correctionIconSmall;
                    }

                    $correction = '
                        <form
                        id="file_upload_'.$item_id.'"
                        class="work_correction_file_upload file_upload_small fileinput-button"
                        action="'.api_get_path(WEB_AJAX_PATH).'work.ajax.php?'.$cidReq.'&a=upload_correction_file&item_id='.$item_id.'"
                        method="POST"
                        enctype="multipart/form-data"
                        >
                        <div id="progress_'.$item_id.'" class="text-center button-load">
                            '.addslashes(get_lang('ClickOrDropOneFileHere')).'
                            '.Display::return_icon('upload_file.png', get_lang('Correction'), [], ICON_SIZE_TINY).'
                            '.$alreadyUploaded.'
                        </div>
                        <input id="file_'.$item_id.'" type="file" name="file" class="" multiple>
                        </form>
                    ';

                    $correction .= "<script>
                    $(function() {
                        $('.work_correction_file_upload').each(function () {
                            $(this).fileupload({
                                dropZone: $(this)
                            });
                        });

                        $('#file_upload_".$item_id."').fileupload({
                            add: function (e, data) {
                                $('#progress_$item_id').html();
                                data.context = $('#progress_$item_id').html('$loadingText <br /> <em class=\"fa fa-spinner fa-pulse fa-fw\"></em>');
                                data.submit();
                                $(this).removeClass('hover');
                            },
                            dragover: function (e, data) {
                                $(this).addClass('hover');
                            },
                            done: function (e, data) {
                                if (data._response.result.name) {
                                    $('#progress_$item_id').html('$uploadedText '+data._response.result.result+'<br />'+data._response.result.name);
                                } else {
                                    $('#progress_$item_id').html('$failsUploadText $failsUploadIcon');
                                }
                                $(this).removeClass('hover');
                            }
                        });
                        $('#file_upload_".$item_id."').on('dragleave', function (e) {
                            // dragleave callback implementation
                            $(this).removeClass('hover');
                        });
                    });
                    </script>";

                    if ($locked) {
                        if ($qualification_exists) {
                            $action .= Display::return_icon(
                                'edit_na.png',
                                get_lang('CorrectAndRate'),
                                [],
                                ICON_SIZE_SMALL
                            );
                        } else {
                            $action .= Display::return_icon('edit_na.png', get_lang('Comment'), [], ICON_SIZE_SMALL);
                        }
                    } else {
                        if ($blockEdition && !api_is_platform_admin()) {
                            $editLink = '';
                        } else {
                            if ($qualification_exists) {
                                $editLink = '<a href="'.$url.'edit.php?'.api_get_cidreq(
                                    ).'&item_id='.$item_id.'&id='.$work['parent_id'].'" title="'.get_lang(
                                        'Edit'
                                    ).'"  >'.
                                    Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL).'</a>';
                            } else {
                                $editLink = '<a href="'.$url.'edit.php?'.api_get_cidreq(
                                    ).'&item_id='.$item_id.'&id='.$work['parent_id'].'" title="'.get_lang(
                                        'Modify'
                                    ).'">'.
                                    Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL).'</a>';
                            }
                        }
                        $action .= $editLink;
                    }

                    if ($work['contains_file']) {
                        if ($locked) {
                            $action .= Display::return_icon(
                                'move_na.png',
                                get_lang('Move'),
                                [],
                                ICON_SIZE_SMALL
                            );
                        } else {
                            $action .= '<a href="'.$url.'work.php?'.api_get_cidreq().'&action=move&item_id='.$item_id.'&id='.$work['parent_id'].'" title="'.get_lang('Move').'">'.
                                Display::return_icon('move.png', get_lang('Move'), [], ICON_SIZE_SMALL).'</a>';
                        }
                    }

                    if ($work['accepted'] == '1') {
                        $action .= '<a href="'.$url.'work_list_all.php?'.api_get_cidreq().'&id='.$work_id.'&action=make_invisible&item_id='.$item_id.'" title="'.get_lang('Invisible').'" >'.
                            Display::return_icon('visible.png', get_lang('Invisible'), [], ICON_SIZE_SMALL).'</a>';
                    } else {
                        $action .= '<a href="'.$url.'work_list_all.php?'.api_get_cidreq().'&id='.$work_id.'&action=make_visible&item_id='.$item_id.'" title="'.get_lang('Visible').'" >'.
                            Display::return_icon('invisible.png', get_lang('Visible'), [], ICON_SIZE_SMALL).'</a> ';
                    }

                    if ($locked) {
                        $action .= Display::return_icon('delete_na.png', get_lang('Delete'), '', ICON_SIZE_SMALL);
                    } else {
                        $action .= '<a href="'.$url.'work_list_all.php?'.api_get_cidreq().'&id='.$work_id.'&action=delete&item_id='.$item_id.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."'".')) return false;" title="'.get_lang('Delete').'" >'.
                            Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>';
                    }
                } elseif ($is_author && (empty($work['qualificator_id']) || $work['qualificator_id'] == 0)) {
                    $action .= '<a href="'.$url.'view.php?'.api_get_cidreq().'&id='.$item_id.'" title="'.get_lang('View').'">'.
                        Display::return_icon('default.png', get_lang('View'), [], ICON_SIZE_SMALL).'</a>';

                    if (api_get_course_setting('student_delete_own_publication') == 1) {
                        if (api_is_allowed_to_session_edit(false, true)) {
                            $action .= '<a href="'.$url.'edit.php?'.api_get_cidreq().'&item_id='.$item_id.'&id='.$work['parent_id'].'" title="'.get_lang('Modify').'">'.
                                Display::return_icon('edit.png', get_lang('Comment'), [], ICON_SIZE_SMALL).'</a>';
                        }
                        $action .= ' <a href="'.$url.'work_list.php?'.api_get_cidreq().'&action=delete&item_id='.$item_id.'&id='.$work['parent_id'].'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."'".')) return false;" title="'.get_lang('Delete').'"  >'.
                            Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>';
                    }
                } else {
                    $action .= '<a href="'.$url.'view.php?'.api_get_cidreq().'&id='.$item_id.'" title="'.get_lang('View').'">'.
                        Display::return_icon('default.png', get_lang('View'), [], ICON_SIZE_SMALL).'</a>';
                }

                // Status.
                if (empty($work['qualificator_id'])) {
                    $qualificator_id = Display::label(get_lang('NotRevised'), 'warning');
                } else {
                    $qualificator_id = Display::label(get_lang('Revised'), 'success');
                }
                $work['qualificator_id'] = $qualificator_id.' '.$hasCorrection;
                $work['actions'] = '<div class="work-action">'.$linkToDownload.$action.'</div>';
                $work['correction'] = $correction;

                if (!empty($compilation) && $is_allowed_to_edit) {
                    $compilationId = $compilation->getCompilatioId($item_id, $course_id);
                    if ($compilationId) {
                        $actionCompilatio = "<div id='id_avancement".$item_id."' class='compilation_block'>
                            ".$loading.'&nbsp;'.get_lang('CompilatioConnectionWithServer').'</div>';
                    } else {
                        $workDirectory = api_get_path(SYS_COURSE_PATH).$course_info['directory'];
                        if (!Compilatio::verifiFileType($dbTitle)) {
                            $actionCompilatio = get_lang('FileFormatNotSupported');
                        } elseif (filesize($workDirectory.'/'.$work['url']) > $compilation->getMaxFileSize()) {
                            $sizeFile = round(filesize($workDirectory.'/'.$work['url']) / 1000000);
                            $actionCompilatio = get_lang('UplFileTooBig').': '.format_file_size($sizeFile).'<br />';
                        } else {
                            $actionCompilatio = "<div id='id_avancement".$item_id."' class='compilation_block'>";
                            $actionCompilatio .= Display::url(
                                get_lang('CompilatioAnalysis'),
                                'javascript:void(0)',
                                [
                                    'class' => 'getSingleCompilatio btn btn-primary btn-xs',
                                    'onclick' => "getSingleCompilatio($item_id);",
                                ]
                            );
                            $actionCompilatio .= get_lang('CompilatioWithCompilatio');
                        }
                    }
                    $work['compilatio'] = $actionCompilatio;
                }
                $works[] = $work;
            }
        }

        return $works;
    }
}

function getAllWork(
    $start,
    $limit,
    $column,
    $direction,
    $whereCondition = '',
    $getCount = false,
    $courseId = 0,
    $status = 0,
    $onlyParents = false,
    $shortTitle = true
) {
    $work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $user_table = Database::get_main_table(TABLE_MAIN_USER);
    $userId = api_get_user_id();
    if (empty($userId)) {
        return [];
    }

    $allowWorkFromAllSessions = api_get_configuration_value('assignment_base_course_teacher_access_to_all_session');
    $coursesInSession = [];
    $courses = CourseManager::get_courses_list_by_user_id($userId, false, false, false);
    if ($allowWorkFromAllSessions) {
        if (empty($courses)) {
            return [];
        }
    } else {
        $coursesInSession = SessionManager::getCoursesForCourseSessionCoach($userId);

        if (empty($courses) && empty($coursesInSession)) {
            return [];
        }
    }

    if (!empty($whereCondition)) {
        $whereCondition = ' AND '.$whereCondition;
    }
    $whereCondition = Database::escape_string($whereCondition);

    if (!in_array($direction, ['asc', 'desc'])) {
        $direction = 'desc';
    }

    $column = !empty($column) ? Database::escape_string($column) : 'sent_date';
    $start = (int) $start;
    $limit = (int) $limit;
    $courseQuery = [];
    $courseList = [];
    $withResults = 0;
    foreach ($courses as $course) {
        $courseIdItem = $course['real_id'];
        if (!empty($courseId) && $courseIdItem != $courseId) {
            continue;
        }
        $courseInfo = api_get_course_info_by_id($courseIdItem);
        // Only teachers or platform admins.
        $isAllow = api_is_platform_admin() || CourseManager::is_course_teacher($userId, $courseInfo['code']);
        if (false === $isAllow) {
            continue;
        }

        //$session_id = isset($course['session_id']) ? $course['session_id'] : 0;
        //$conditionSession = api_get_session_condition($session_id, true, false, 'w.session_id');
        $conditionSession = ' AND (work.session_id = 0 OR work.session_id IS NULL)';
        if ($allowWorkFromAllSessions) {
            $conditionSession = '';
        }
        $parentCondition = '';
        if ($withResults) {
            $parentCondition = 'AND ww.parent_id is NOT NULL';
        }
        $courseQuery[] = " (work.c_id = $courseIdItem $conditionSession $parentCondition ) ";
        $courseList[$courseIdItem] = $courseInfo;
    }

    if (false === $allowWorkFromAllSessions) {
        foreach ($coursesInSession as $courseIdInSession => $sessionList) {
            if (!empty($sessionList)) {
                if (!isset($courseList[$courseIdInSession])) {
                    $courseList[$courseIdInSession] = api_get_course_info_by_id($courseIdInSession);
                }

                foreach ($sessionList as $sessionId) {
                    $conditionSession = " AND (work.session_id = $sessionId)";
                    $parentCondition = '';
                    $courseQuery[] = " (work.c_id = $courseIdInSession $conditionSession $parentCondition ) ";
                }
            }
        }
    }

    if (empty($courseQuery)) {
        return [];
    }

    $courseQueryToString = implode(' OR ', $courseQuery);
    $compilation = null;
    /*if (api_get_configuration_value('allow_compilatio_tool')) {
        $compilation = new Compilatio();
    }*/

    if ($getCount) {
        if (empty($courseQuery)) {
            return 0;
        }
        $select = 'SELECT DISTINCT count(u.id) as count ';
    } else {
        $select = 'SELECT DISTINCT
                    u.id as user_id,
                    work.id as id,
                    title as title,
                    description,
                    url,
                    sent_date,
                    contains_file,
                    has_properties,
                    view_properties,
                    qualification,
                    weight,
                    allow_text_assignment,
                    u.firstname,
                    u.lastname,
                    u.username,
                    parent_id,
                    accepted,
                    qualificator_id,
                    url_correction,
                    title_correction,
                    work.c_id,
                    work.date_of_qualification,
                    work.session_id ';
    }

    $statusCondition = '';
    if (!empty($status)) {
        switch ($status) {
            case 2:
                $statusCondition = ' AND (qualificator_id IS NULL OR qualificator_id = 0) ';
                break;
            case 3:
                $statusCondition = ' AND (qualificator_id <> 0 AND qualificator_id IS NOT NULL) ';
                break;
        }
    }
    $filterParents = 'work.parent_id <> 0';
    if ($onlyParents) {
        $filterParents = 'work.parent_id = 0';
    }
    $sql = " $select
            FROM $work_table work
            INNER JOIN $user_table u
            ON (work.user_id = u.id)
            WHERE
                $filterParents AND
                work.active IN (1, 0)
                $whereCondition AND
                ($courseQueryToString)
                $statusCondition
                AND u.status != ".INVITEE;

    $sql .= " ORDER BY `$column` $direction ";

    if (!empty($start) && !empty($limit)) {
        $sql .= " LIMIT $start, $limit";
    }

    $result = Database::query($sql);
    $works = [];
    if ($getCount) {
        $work = Database::fetch_array($result, 'ASSOC');
        if ($work) {
            return (int) $work['count'];
        }

        return 0;
    }

    $url = api_get_path(WEB_CODE_PATH).'work/';
    $unoconv = api_get_configuration_value('unoconv.binaries');
    $loadingText = addslashes(get_lang('Loading'));
    $uploadedText = addslashes(get_lang('Uploaded'));
    $failsUploadText = addslashes(get_lang('UplNoFileUploaded'));
    $failsUploadIcon = Display::return_icon(
        'closed-circle.png',
        '',
        [],
        ICON_SIZE_TINY
    );
    $saveIcon = Display::return_icon(
        'save.png',
        get_lang('Save'),
        [],
        ICON_SIZE_SMALL
    );

    $correctionIcon = Display::return_icon(
        'check-circle.png',
        get_lang('Correction'),
        null,
        ICON_SIZE_SMALL
    );

    $correctionIconSmall = Display::return_icon(
        'check-circle.png',
        get_lang('Correction'),
        null,
        ICON_SIZE_TINY
    );

    $rateIcon = Display::return_icon(
        'rate_work.png',
        get_lang('CorrectAndRate'),
        [],
        ICON_SIZE_SMALL
    );
    $parentList = [];
    $blockEdition = api_get_configuration_value('block_student_publication_edition');
    $blockScoreEdition = api_get_configuration_value('block_student_publication_score_edition');
    $loading = Display::returnFontAwesomeIcon('spinner', null, true, 'fa-spin');
    $qualification_exists = true;
    while ($work = Database::fetch_array($result, 'ASSOC')) {
        $courseId = $work['c_id'];
        $courseInfo = $courseList[$work['c_id']];
        $sessionId = $work['session_id'];
        $cidReq = 'cidReq='.$courseInfo['code'].'&id_session='.$sessionId;

        $item_id = $work_id = $work['id'];
        $dbTitle = $work['title'];
        // Get the author ID for that document from the item_property table
        $is_author = false;
        $can_read = false;
        $owner_id = $work['user_id'];
        $visibility = api_get_item_visibility($courseInfo, 'work', $work['id'], $sessionId);
        if ($visibility != 1) {
            continue;
        }
        /*$locked = api_resource_is_locked_by_gradebook(
            $item_id,
            LINK_STUDENTPUBLICATION,
            $courseInfo['code']
        );*/
        $locked = false;

        /* Because a bug found when saving items using the api_item_property_update()
           the field $item_property_data['insert_user_id'] is not reliable. */
        /*if (!$is_allowed_to_edit && $owner_id == api_get_user_id()) {
            $is_author = true;
        }*/
        // Teacher can be treated as an author.
        $is_author = true;

        /*if ($course_info['show_score'] == 0) {
            $can_read = true;
        }*/

        $qualification_string = '';
        if ($qualification_exists) {
            if ($work['qualification'] == '') {
                $qualification_string = Display::label('-');
            } else {
                $qualification_string = formatWorkScore($work['qualification'], $work['qualification']);
            }
        }

        $work['qualification_score'] = $work['qualification'];
        $add_string = '';
        $time_expires = '';
        if (!empty($work_assignment['expires_on'])) {
            $time_expires = api_strtotime(
                $work_assignment['expires_on'],
                'UTC'
            );
        }

        if (!empty($work_assignment['expires_on']) &&
            !empty($time_expires) && ($time_expires < api_strtotime($work['sent_date'], 'UTC'))) {
            $add_string = Display::label(get_lang('Expired'), 'important').' - ';
        }

        if (($can_read && $work['accepted'] == '1') ||
            ($is_author && in_array($work['accepted'], ['1', '0']))
        ) {
            // Firstname, lastname, username
            $work['fullname'] = Display::div(
                api_get_person_name($work['firstname'], $work['lastname']),
                ['class' => 'work-name']
            );
            // Title
            $work['title_clean'] = $work['title'];
            $work['title'] = Security::remove_XSS($work['title']);
            if (strlen($work['title']) > 30 && $shortTitle) {
                $short_title = substr($work['title'], 0, 27).'...';
                $work['title'] = Display::span($short_title, ['class' => 'work-title', 'title' => $work['title']]);
            } else {
                $work['title'] = Display::div($work['title'], ['class' => 'work-title']);
            }

            // Type.
            $work['type'] = DocumentManager::build_document_icon_tag('file', $work['url']);

            // File name.
            $linkToDownload = '';
            // If URL is present then there's a file to download keep BC.
            if ($work['contains_file'] || !empty($work['url'])) {
                $linkToDownload = '<a href="'.$url.'download.php?id='.$item_id.'&'.$cidReq.'">'.$saveIcon.'</a> ';
            }

            $feedback = '';
            $count = getWorkCommentCount($item_id, $courseInfo);
            if (!is_null($count) && !empty($count)) {
                if ($qualification_exists) {
                    $feedback .= ' ';
                }
                $feedback .= Display::url(
                    $count.' '.Display::returnFontAwesomeIcon('comments-o'),
                    $url.'view.php?'.$cidReq.'&id='.$item_id
                );
            }

            $correction = '';
            $hasCorrection = '';
            if (!empty($work['url_correction'])) {
                $hasCorrection = Display::url(
                    $correctionIcon,
                    api_get_path(WEB_CODE_PATH).'work/download.php?id='.$item_id.'&'.$cidReq.'&correction=1'
                );
            }

            if ($qualification_exists) {
                $work['qualification'] = $qualification_string.$feedback;
            } else {
                $work['qualification'] = $qualification_string.$feedback.$hasCorrection;
            }

            $work['qualification_only'] = $qualification_string;

            // Date.
            $work_date = api_get_local_time($work['sent_date']);
            $date = date_to_str_ago($work['sent_date']).' '.$work_date;
            $work['formatted_date'] = $work_date.' '.$add_string;
            $work['expiry_note'] = $add_string;
            $work['sent_date_from_db'] = $work['sent_date'];
            $work['sent_date'] = '<div class="work-date" title="'.$date.'">'.
                $add_string.' '.Display::dateToStringAgoAndLongDate($work['sent_date']).'</div>';
            $work['status'] = $hasCorrection;
            $work['has_correction'] = $hasCorrection;
            $work['course'] = $courseInfo['title'];

            if (isset($parentList[$work['parent_id']])) {
                $parent = $parentList[$work['parent_id']];
            } else {
                $parent = get_work_data_by_id($work['parent_id'], $courseId);
            }
            $work['work_name'] = isset($parent['title']) ? $parent['title'] : '';

            // Actions.
            $action = '';
            if ($blockScoreEdition && !api_is_platform_admin() && !empty($work['qualification_score'])) {
                $rateLink = '';
            } else {
                $rateLink = '<a href="'.$url.'view.php?'.$cidReq.'&id='.$item_id.'" title="'.get_lang('View').'">'.
                    $rateIcon.'</a> ';
            }
            $action .= $rateLink;
            if ($unoconv && empty($work['contains_file'])) {
                $action .= '<a
                    href="'.$url.'work_list_all.php?'.$cidReq.'&id='.$work_id.'&action=export_to_doc&item_id='.$item_id.'"
                    title="'.get_lang('ExportToDoc').'" >'.
                    Display::return_icon('export_doc.png', get_lang('ExportToDoc'), [], ICON_SIZE_SMALL).'</a> ';
            }

            $alreadyUploaded = '';
            if (!empty($work['url_correction'])) {
                $alreadyUploaded = '<br />'.$work['title_correction'].' '.$correctionIconSmall;
            }

            $correction = '
                <form
                id="file_upload_'.$item_id.'"
                class="work_correction_file_upload file_upload_small fileinput-button"
                action="'.api_get_path(WEB_AJAX_PATH).'work.ajax.php?'.$cidReq.'&a=upload_correction_file&item_id='.$item_id.'"
                method="POST"
                enctype="multipart/form-data"
                >
                <div id="progress_'.$item_id.'" class="text-center button-load">
                    '.addslashes(get_lang('ClickOrDropOneFileHere')).'
                    '.Display::return_icon('upload_file.png', get_lang('Correction'), [], ICON_SIZE_TINY).'
                    '.$alreadyUploaded.'
                </div>
                <input id="file_'.$item_id.'" type="file" name="file" class="" multiple>
                </form>
            ';

            $correction .= "<script>
            $(function() {
                $('.work_correction_file_upload').each(function () {
                    $(this).fileupload({
                        dropZone: $(this)
                    });
                });
                $('#file_upload_".$item_id."').fileupload({
                    add: function (e, data) {
                        $('#progress_$item_id').html();
                        data.context = $('#progress_$item_id').html('$loadingText <br /> <em class=\"fa fa-spinner fa-pulse fa-fw\"></em>');
                        data.submit();
                        $(this).removeClass('hover');
                    },
                    dragover: function (e, data) {
                        $(this).addClass('hover');
                    },
                    done: function (e, data) {
                        if (data._response.result.name) {
                            $('#progress_$item_id').html('$uploadedText '+data._response.result.result+'<br />'+data._response.result.name);
                        } else {
                            $('#progress_$item_id').html('$failsUploadText $failsUploadIcon');
                        }
                        $(this).removeClass('hover');
                    }
                });
                $('#file_upload_".$item_id."').on('dragleave', function (e) {
                    // dragleave callback implementation
                    $(this).removeClass('hover');
                });
            });
            </script>";

            if ($locked) {
                if ($qualification_exists) {
                    $action .= Display::return_icon(
                        'edit_na.png',
                        get_lang('CorrectAndRate'),
                        [],
                        ICON_SIZE_SMALL
                    );
                } else {
                    $action .= Display::return_icon('edit_na.png', get_lang('Comment'), [], ICON_SIZE_SMALL);
                }
            } else {
                if ($blockEdition && !api_is_platform_admin()) {
                    $editLink = '';
                } else {
                    $editIcon = Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL);
                    if ($qualification_exists) {
                        $editLink = '<a
                            href="'.$url.'edit.php?'.$cidReq.'&item_id='.$item_id.'&id='.$work['parent_id'].'"
                            title="'.get_lang('Edit').'"  >'.
                            $editIcon.
                        '</a>';
                    } else {
                        $editLink = '<a
                            href="'.$url.'edit.php?'.$cidReq.'&item_id='.$item_id.'&id='.$work['parent_id'].'"
                            title="'.get_lang('Modify').'">'.
                            $editIcon.'</a>';
                    }
                }
                $action .= $editLink;
            }

            /*if ($work['contains_file']) {
                if ($locked) {
                    $action .= Display::return_icon(
                        'move_na.png',
                        get_lang('Move'),
                        [],
                        ICON_SIZE_SMALL
                    );
                } else {
                    $action .= '<a href="'.$url.'work.php?'.$cidReq.'&action=move&item_id='.$item_id.'&id='.$work['parent_id'].'" title="'.get_lang('Move').'">'.
                        Display::return_icon('move.png', get_lang('Move'), [], ICON_SIZE_SMALL).'</a>';
                }
            }*/

            /*if ($work['accepted'] == '1') {
                $action .= '<a href="'.$url.'work_list_all.php?'.$cidReq.'&id='.$work_id.'&action=make_invisible&item_id='.$item_id.'" title="'.get_lang('Invisible').'" >'.
                    Display::return_icon('visible.png', get_lang('Invisible'), [], ICON_SIZE_SMALL).'</a>';
            } else {
                $action .= '<a href="'.$url.'work_list_all.php?'.$cidReq.'&id='.$work_id.'&action=make_visible&item_id='.$item_id.'" title="'.get_lang('Visible').'" >'.
                    Display::return_icon('invisible.png', get_lang('Visible'), [], ICON_SIZE_SMALL).'</a> ';
            }*/
            /*if ($locked) {
                $action .= Display::return_icon('delete_na.png', get_lang('Delete'), '', ICON_SIZE_SMALL);
            } else {
                $action .= '<a href="'.$url.'work_list_all.php?'.$cidReq.'&id='.$work_id.'&action=delete&item_id='.$item_id.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."'".')) return false;" title="'.get_lang('Delete').'" >'.
                    Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>';
            }*/
            // Qualificator fullname and date of qualification
            $work['qualificator_fullname'] = '';
            if ($work['qualificator_id'] > 0) {
                $qualificatorAuthor = api_get_user_info($work['qualificator_id']);
                $work['qualificator_fullname'] = api_get_person_name($qualificatorAuthor['firstname'], $qualificatorAuthor['lastname']);
                $work['date_of_qualification'] = api_convert_and_format_date($work['date_of_qualification'], DATE_TIME_FORMAT_SHORT);
            }
            // Status.
            if (empty($work['qualificator_id'])) {
                $qualificator_id = Display::label(get_lang('NotRevised'), 'warning');
            } else {
                $qualificator_id = Display::label(get_lang('Revised'), 'success');
            }
            $work['qualificator_id'] = $qualificator_id.' '.$hasCorrection;
            $work['actions'] = '<div class="work-action">'.$linkToDownload.$action.'</div>';
            $work['correction'] = $correction;

            if (!empty($compilation)) {
                $compilationId = $compilation->getCompilatioId($item_id, $courseId);
                if ($compilationId) {
                    $actionCompilatio = "<div id='id_avancement".$item_id."' class='compilation_block'>
                        ".$loading.'&nbsp;'.get_lang('CompilatioConnectionWithServer').'</div>';
                } else {
                    $workDirectory = api_get_path(SYS_COURSE_PATH).$courseInfo['directory'];
                    if (!Compilatio::verifiFileType($dbTitle)) {
                        $actionCompilatio = get_lang('FileFormatNotSupported');
                    } elseif (filesize($workDirectory.'/'.$work['url']) > $compilation->getMaxFileSize()) {
                        $sizeFile = round(filesize($workDirectory.'/'.$work['url']) / 1000000);
                        $actionCompilatio = get_lang('UplFileTooBig').': '.format_file_size($sizeFile).'<br />';
                    } else {
                        $actionCompilatio = "<div id='id_avancement".$item_id."' class='compilation_block'>";
                        $actionCompilatio .= Display::url(
                            get_lang('CompilatioAnalysis'),
                            'javascript:void(0)',
                            [
                                'class' => 'getSingleCompilatio btn btn-primary btn-xs',
                                'onclick' => "getSingleCompilatio($item_id);",
                            ]
                        );
                        $actionCompilatio .= get_lang('CompilatioWithCompilatio');
                    }
                }
                $work['compilatio'] = $actionCompilatio;
            }
            $works[] = $work;
        }
    }

    return $works;
}

/**
 * Send reminder to users who have not given the task.
 *
 * @param int
 *
 * @return array
 *
 * @author cvargas carlos.vargas@beeznest.com cfasanando, christian.fasanado@beeznest.com
 */
function send_reminder_users_without_publication($task_data)
{
    $_course = api_get_course_info();
    $task_id = $task_data['id'];
    $task_title = !empty($task_data['title']) ? $task_data['title'] : basename($task_data['url']);
    $subject = '['.api_get_setting('siteName').'] ';

    // The body can be as long as you wish, and any combination of text and variables
    $content = get_lang('ReminderToSubmitPendingTask')."\n".get_lang('CourseName').' : '.$_course['name']."\n";
    $content .= get_lang('WorkName').' : '.$task_title."\n";
    $list_users = get_list_users_without_publication($task_id);
    $mails_sent_to = [];
    foreach ($list_users as $user) {
        $name_user = api_get_person_name($user[1], $user[0], null, PERSON_NAME_EMAIL_ADDRESS);
        $dear_line = get_lang('Dear')." ".api_get_person_name($user[1], $user[0]).", \n\n";
        $body = $dear_line.$content;
        MessageManager::send_message($user[3], $subject, $body);
        $mails_sent_to[] = $name_user;
    }

    return $mails_sent_to;
}

/**
 * @param int $workId    The work ID
 * @param int $courseId  The course ID
 * @param int $sessionId Optional. The session ID
 */
function sendEmailToDrhOnHomeworkCreation($workId, $courseId, $sessionId = 0)
{
    $courseInfo = api_get_course_info_by_id($courseId);
    $assignment = get_work_assignment_by_id($workId, $courseId);
    $work = get_work_data_by_id($workId, $courseId, $sessionId);
    $workInfo = array_merge($assignment, $work);

    if (empty($sessionId)) {
        $students = CourseManager::get_student_list_from_course_code($courseInfo['code']);
    } else {
        $students = CourseManager::get_student_list_from_course_code($courseInfo['code'], true, $sessionId);
    }

    $bodyView = new Template(null, false, false, false, false, false);

    foreach ($students as $student) {
        $studentInfo = api_get_user_info($student['user_id']);
        if (empty($studentInfo)) {
            continue;
        }

        $hrms = UserManager::getDrhListFromUser($student['id']);
        foreach ($hrms as $hrm) {
            $hrmName = api_get_person_name($hrm['firstname'], $hrm['lastname'], null, PERSON_NAME_EMAIL_ADDRESS);

            $bodyView->assign('hrm_name', $hrmName);
            $bodyView->assign('student', $studentInfo);
            $bodyView->assign('course', $courseInfo);
            $bodyView->assign('course_link', api_get_course_url($courseInfo['code'], $sessionId));
            $bodyView->assign('work', $workInfo);

            $bodyTemplate = $bodyView->get_template('mail/new_work_alert_hrm.tpl');

            MessageManager::send_message(
                $hrm['id'],
                sprintf(
                    get_lang('StudentXHasBeenAssignedNewWorkInCourseY'),
                    $student['firstname'],
                    $courseInfo['title']
                ),
                $bodyView->fetch($bodyTemplate)
            );
        }
    }
}

/**
 * Sends an email to the students of a course when a homework is created.
 *
 * @param int $workId
 * @param int $courseId
 * @param int $sessionId
 *
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 * @author Julio Montoya <gugli100@gmail.com> Adding session support - 2011
 */
function sendEmailToStudentsOnHomeworkCreation($workId, $courseId, $sessionId = 0)
{
    $courseInfo = api_get_course_info_by_id($courseId);
    $courseCode = $courseInfo['code'];
    // Get the students of the course
    if (empty($sessionId)) {
        $students = CourseManager::get_student_list_from_course_code($courseCode);
    } else {
        $students = CourseManager::get_student_list_from_course_code($courseCode, true, $sessionId);
    }
    $emailsubject = '['.api_get_setting('siteName').'] '.get_lang('HomeworkCreated');
    $currentUser = api_get_user_info(api_get_user_id());
    if (!empty($students)) {
        foreach ($students as $student) {
            $user_info = api_get_user_info($student['user_id']);
            if (!empty($user_info)) {
                $link = api_get_path(WEB_CODE_PATH).'work/work_list.php?'.api_get_cidreq().'&id='.$workId;
                $emailbody = get_lang('Dear')." ".$user_info['complete_name'].",\n\n";
                $emailbody .= get_lang('HomeworkHasBeenCreatedForTheCourse')." ".$courseCode.". "."\n\n".
                    '<a href="'.$link.'">'.get_lang('PleaseCheckHomeworkPage').'</a>';
                $emailbody .= "\n\n".$currentUser['complete_name'];

                $additionalParameters = [
                    'smsType' => SmsPlugin::ASSIGNMENT_BEEN_CREATED_COURSE,
                    'userId' => $student['user_id'],
                    'courseTitle' => $courseCode,
                    'link' => $link,
                ];

                MessageManager::send_message_simple(
                    $student['user_id'],
                    $emailsubject,
                    $emailbody,
                    null,
                    false,
                    false,
                    $additionalParameters,
                    false
                );
            }
        }
    }
}

/**
 * @param string $url
 *
 * @return bool
 */
function is_work_exist_by_url($url)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $url = Database::escape_string($url);
    $sql = "SELECT id FROM $table WHERE url='$url'";
    $result = Database::query($sql);
    if (Database::num_rows($result) > 0) {
        $row = Database::fetch_row($result);
        if (empty($row)) {
            return false;
        } else {
            return true;
        }
    } else {
        return false;
    }
}

/**
 * Check if a user is the author of a work document.
 *
 * @param int $itemId
 * @param int $userId
 * @param int $courseId
 * @param int $sessionId
 *
 * @return bool
 */
function user_is_author($itemId, $userId = null, $courseId = 0, $sessionId = 0)
{
    $userId = (int) $userId;

    if (empty($itemId)) {
        return false;
    }

    if (empty($userId)) {
        $userId = api_get_user_id();
    }

    $isAuthor = false;
    $is_allowed_to_edit = api_is_allowed_to_edit();

    if ($is_allowed_to_edit) {
        $isAuthor = true;
    } else {
        if (empty($courseId)) {
            $courseId = api_get_course_int_id();
        }
        if (empty($sessionId)) {
            $sessionId = api_get_session_id();
        }

        $data = api_get_item_property_info($courseId, 'work', $itemId, $sessionId);
        if ($data['insert_user_id'] == $userId) {
            $isAuthor = true;
        }

        $workData = get_work_data_by_id($itemId);
        if ($workData['user_id'] == $userId) {
            $isAuthor = true;
        }
    }

    if (!$isAuthor) {
        return false;
    }

    return $isAuthor;
}

/**
 * Get list of users who have not given the task.
 *
 * @param int
 * @param int
 *
 * @return array
 *
 * @author cvargas
 * @author Julio Montoya <gugli100@gmail.com> Fixing query
 */
function get_list_users_without_publication($task_id, $studentId = 0)
{
    $work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $table_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $table_user = Database::get_main_table(TABLE_MAIN_USER);
    $session_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

    $users = getAllUserToWork($task_id, api_get_course_int_id());
    $users = array_column($users, 'user_id');

    // Condition for the session
    $session_id = api_get_session_id();
    $course_id = api_get_course_int_id();
    $task_id = (int) $task_id;
    $sessionCondition = api_get_session_condition($session_id);

    if (0 == $session_id) {
        $sql = "SELECT user_id as id FROM $work_table
                WHERE
                    c_id = $course_id AND
                    parent_id = '$task_id' AND
                    active IN (0, 1)";
    } else {
        $sql = "SELECT user_id as id FROM $work_table
                WHERE
                    c_id = $course_id AND
                    parent_id = '$task_id' $sessionCondition AND
                    active IN (0, 1)";
    }

    $result = Database::query($sql);
    $users_with_tasks = [];
    while ($row = Database::fetch_array($result)) {
        $users_with_tasks[] = $row['id'];
    }

    if (0 == $session_id) {
        $sql_users = "SELECT cu.user_id, u.lastname, u.firstname, u.email
                      FROM $table_course_user AS cu, $table_user AS u
                      WHERE u.status != 1 and cu.c_id='".$course_id."' AND u.user_id = cu.user_id";
    } else {
        $sql_users = "SELECT cu.user_id, u.lastname, u.firstname, u.email
                      FROM $session_course_rel_user AS cu, $table_user AS u
                      WHERE
                        u.status != 1 AND
                        cu.c_id='".$course_id."' AND
                        u.user_id = cu.user_id AND
                        cu.session_id = '".$session_id."'";
    }

    if (!empty($studentId)) {
        $sql_users .= ' AND u.user_id = '.(int) $studentId;
    }

    $group_id = api_get_group_id();
    $new_group_user_list = [];

    if ($group_id) {
        $groupInfo = GroupManager::get_group_properties($group_id);
        $group_user_list = GroupManager::get_subscribed_users($groupInfo);
        if (!empty($group_user_list)) {
            foreach ($group_user_list as $group_user) {
                $new_group_user_list[] = $group_user['user_id'];
            }
        }
    }

    $result_users = Database::query($sql_users);
    $users_without_tasks = [];
    while ($rowUsers = Database::fetch_array($result_users)) {
        $userId = $rowUsers['user_id'];
        if (in_array($userId, $users_with_tasks)) {
            continue;
        }

        if ($group_id && !in_array($userId, $new_group_user_list)) {
            continue;
        }

        if (!empty($users)) {
            if (!in_array($userId, $users)) {
                continue;
            }
        }

        $row_users = [];
        $row_users[0] = $rowUsers['lastname'];
        $row_users[1] = $rowUsers['firstname'];
        $row_users[2] = Display::encrypted_mailto_link($rowUsers['email']);
        $row_users[3] = $userId;
        $users_without_tasks[] = $row_users;
    }

    return $users_without_tasks;
}

/**
 * Display list of users who have not given the task.
 *
 * @param int task id
 * @param int $studentId
 *
 * @author cvargas carlos.vargas@beeznest.com cfasanando, christian.fasanado@beeznest.com
 * @author Julio Montoya <gugli100@gmail.com> Fixes
 */
function display_list_users_without_publication($task_id, $studentId = null)
{
    $origin = api_get_origin();
    $table_header[] = [get_lang('LastName'), true];
    $table_header[] = [get_lang('FirstName'), true];
    $table_header[] = [get_lang('Email'), true];

    $data = get_list_users_without_publication($task_id);

    $sorting_options = [];
    $sorting_options['column'] = 1;
    $paging_options = [];
    $my_params = [];

    if (isset($_GET['edit_dir'])) {
        $my_params['edit_dir'] = Security::remove_XSS($_GET['edit_dir']);
    }
    if (isset($_GET['list'])) {
        $my_params['list'] = Security::remove_XSS($_GET['list']);
    }
    $my_params['origin'] = $origin;
    $my_params['id'] = (int) ($_GET['id']);

    //$column_show
    $column_show[] = 1;
    $column_show[] = 1;
    $column_show[] = 1;
    Display::display_sortable_config_table(
        'work',
        $table_header,
        $data,
        $sorting_options,
        $paging_options,
        $my_params,
        $column_show
    );
}

/**
 * @param int $documentId
 * @param int $workId
 * @param int $courseId
 */
function addDocumentToWork($documentId, $workId, $courseId)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_DOCUMENT);
    $params = [
        'document_id' => $documentId,
        'work_id' => $workId,
        'c_id' => $courseId,
    ];
    Database::insert($table, $params);
}

/**
 * @param int $documentId
 * @param int $workId
 * @param int $courseId
 *
 * @return array
 */
function getDocumentToWork($documentId, $workId, $courseId)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_DOCUMENT);
    $params = [
        'document_id = ? and work_id = ? and c_id = ?' => [$documentId, $workId, $courseId],
    ];

    return Database::select('*', $table, ['where' => $params]);
}

/**
 * @param int $documentId
 * @param int $workId
 * @param int $courseId
 * @param int $sessionId
 * @param int $userId
 * @param int $active
 *
 * @return array
 */
function getDocumentToWorkPerUser($documentId, $workId, $courseId, $sessionId, $userId, $active = 1)
{
    $workRel = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_DOCUMENT);
    $work = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

    $documentId = (int) $documentId;
    $workId = (int) $workId;
    $courseId = (int) $courseId;
    $userId = (int) $userId;
    $sessionId = (int) $sessionId;
    $active = (int) $active;
    $sessionCondition = api_get_session_condition($sessionId);

    $sql = "SELECT w.* FROM $work w
            INNER JOIN $workRel rel
            ON (w.parent_id = rel.work_id)
            WHERE
                w.document_id = $documentId AND
                w.parent_id = $workId AND
                w.c_id = $courseId
                $sessionCondition AND
                user_id = $userId AND
                active = $active
            ";

    $result = Database::query($sql);
    $workInfo = [];
    if (Database::num_rows($result)) {
        $workInfo = Database::fetch_array($result, 'ASSOC');
    }

    return $workInfo;
}

/**
 * @param int $workId
 * @param int $courseId
 *
 * @return array
 */
function getAllDocumentToWork($workId, $courseId)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_DOCUMENT);
    $params = [
        'work_id = ? and c_id = ?' => [$workId, $courseId],
    ];

    return Database::select('*', $table, ['where' => $params]);
}

/**
 * @param int $documentId
 * @param int $workId
 * @param int $courseId
 */
function deleteDocumentToWork($documentId, $workId, $courseId)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_DOCUMENT);
    $params = [
        'document_id = ? and work_id = ? and c_id = ?' => [$documentId, $workId, $courseId],
    ];
    Database::delete($table, $params);
}

/**
 * @param int $userId
 * @param int $workId
 * @param int $courseId
 */
function addUserToWork($userId, $workId, $courseId)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_USER);
    $params = [
        'user_id' => $userId,
        'work_id' => $workId,
        'c_id' => $courseId,
    ];
    Database::insert($table, $params);
}

/**
 * @param int $userId
 * @param int $workId
 * @param int $courseId
 *
 * @return array
 */
function getUserToWork($userId, $workId, $courseId)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_USER);
    $params = [
        'user_id = ? and work_id = ? and c_id = ?' => [$userId, $workId, $courseId],
    ];

    return Database::select('*', $table, ['where' => $params]);
}

/**
 * @param int  $workId
 * @param int  $courseId
 * @param bool $getCount
 *
 * @return array|int
 */
function getAllUserToWork($workId, $courseId, $getCount = false)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_USER);
    $params = [
        'work_id = ? and c_id = ?' => [$workId, $courseId],
    ];
    if ($getCount) {
        $count = 0;
        $result = Database::select(
            'count(user_id) as count',
            $table,
            ['where' => $params],
            'simple'
        );
        if (!empty($result)) {
            $count = (int) ($result['count']);
        }

        return $count;
    } else {
        return Database::select('*', $table, ['where' => $params]);
    }
}

/**
 * @param int $userId
 * @param int $workId
 * @param int $courseId
 */
function deleteUserToWork($userId, $workId, $courseId)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_USER);
    $params = [
        'user_id = ? and work_id = ? and c_id = ?' => [$userId, $workId, $courseId],
    ];
    Database::delete($table, $params);
}

/**
 * @param int $userId
 * @param int $workId
 * @param int $courseId
 *
 * @return bool
 */
function userIsSubscribedToWork($userId, $workId, $courseId)
{
    $subscribedUsers = getAllUserToWork($workId, $courseId);

    if (empty($subscribedUsers)) {
        return true;
    } else {
        $subscribedUsersList = [];
        foreach ($subscribedUsers as $item) {
            $subscribedUsersList[] = $item['user_id'];
        }
        if (in_array($userId, $subscribedUsersList)) {
            return true;
        }
    }

    return false;
}

/**
 * Get the list of students that have to submit their work.
 *
 * @param int  $workId    The internal ID of the assignment
 * @param int  $courseId  The course ID
 * @param int  $groupId   The group ID, if any
 * @param int  $sessionId The session ID, if any
 * @param bool $getCount  Whether we want just the amount or the full result
 *
 * @return array|int An integer (if we just asked for the count) or an array of users
 */
function getStudentSubscribedToWork(
    $workId,
    $courseId,
    $groupId = null,
    $sessionId = null,
    $getCount = false
) {
    $usersInWork = null;
    $usersInCourse = null;

    if (empty($groupId)) {
        $courseInfo = api_get_course_info_by_id($courseId);
        $status = STUDENT;
        if (!empty($sessionId)) {
            $status = 0;
        }
        $usersInCourse = CourseManager::get_user_list_from_course_code(
            $courseInfo['code'],
            $sessionId,
            null,
            null,
            $status,
            $getCount
        );
    } else {
        $usersInCourse = GroupManager::get_users(
            $groupId,
            false,
            null,
            null,
            $getCount,
            $courseId
        );
    }

    $usersInWork = getAllUserToWork($workId, $courseId, $getCount);

    if (empty($usersInWork)) {
        return $usersInCourse;
    } else {
        return $usersInWork;
    }
}

/**
 * @param int  $userId
 * @param int  $workId
 * @param int  $courseId
 * @param bool $forceAccessForCourseAdmins
 *
 * @return bool
 */
function allowOnlySubscribedUser($userId, $workId, $courseId, $forceAccessForCourseAdmins = false)
{
    if (api_is_platform_admin() || api_is_allowed_to_edit()) {
        return true;
    }

    if ($forceAccessForCourseAdmins) {
        if (api_is_course_admin() || api_is_coach()) {
            return true;
        }
    }

    return userIsSubscribedToWork($userId, $workId, $courseId);
}

/**
 * @param int   $workId
 * @param array $courseInfo
 * @param int   $documentId
 *
 * @return array
 */
function getDocumentTemplateFromWork($workId, $courseInfo, $documentId)
{
    $documents = getAllDocumentToWork($workId, $courseInfo['real_id']);
    if (!empty($documents)) {
        foreach ($documents as $doc) {
            if ($documentId != $doc['document_id']) {
                continue;
            }
            $docData = DocumentManager::get_document_data_by_id($doc['document_id'], $courseInfo['code']);
            $fileInfo = pathinfo($docData['path']);
            if ('html' == $fileInfo['extension']) {
                if (file_exists($docData['absolute_path']) && is_file($docData['absolute_path'])) {
                    $docData['file_content'] = file_get_contents($docData['absolute_path']);

                    return $docData;
                }
            }
        }
    }

    return [];
}

/**
 * @param int   $workId
 * @param array $courseInfo
 *
 * @return string
 */
function getAllDocumentsFromWorkToString($workId, $courseInfo)
{
    $documents = getAllDocumentToWork($workId, $courseInfo['real_id']);
    $content = null;
    if (!empty($documents)) {
        $content .= '<ul class="nav nav-list well">';
        $content .= '<li class="nav-header">'.get_lang('Documents').'</li>';
        foreach ($documents as $doc) {
            $docData = DocumentManager::get_document_data_by_id($doc['document_id'], $courseInfo['code']);
            if ($docData) {
                $content .= '<li><a class="link_to_download" target="_blank" href="'.$docData['url'].'">'.$docData['title'].'</a></li>';
            }
        }
        $content .= '</ul><br />';
    }

    return $content;
}

/**
 * Returns fck editor toolbar.
 *
 * @return array
 */
function getWorkDescriptionToolbar()
{
    return [
        'ToolbarStartExpanded' => 'true',
        'ToolbarSet' => 'Work',
        'Width' => '100%',
        'Height' => '400',
    ];
}

/**
 * @param array $work
 *
 * @return array
 */
function getWorkComments($work)
{
    $commentTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT_COMMENT);
    $userTable = Database::get_main_table(TABLE_MAIN_USER);

    $courseId = (int) $work['c_id'];
    $workId = (int) $work['id'];

    if (empty($courseId) || empty($workId)) {
        return [];
    }

    $sql = "SELECT
                c.id,
                c.user_id
            FROM $commentTable c
            INNER JOIN $userTable u
            ON (u.id = c.user_id)
            WHERE c_id = $courseId AND work_id = $workId
            ORDER BY sent_at
            ";
    $result = Database::query($sql);
    $comments = Database::store_result($result, 'ASSOC');
    if (!empty($comments)) {
        foreach ($comments as &$comment) {
            $userInfo = api_get_user_info($comment['user_id']);
            $comment['picture'] = $userInfo['avatar'];
            $comment['complete_name'] = $userInfo['complete_name_with_username'];
            $commentInfo = getWorkComment($comment['id']);
            if (!empty($commentInfo)) {
                $comment = array_merge($comment, $commentInfo);
            }
        }
    }

    return $comments;
}

/**
 * Get total score from a work list.
 *
 * @param $workList
 *
 * @return int|null
 */
function getTotalWorkScore($workList)
{
    $count = 0;
    foreach ($workList as $data) {
        $count += $data['qualification_score'];
    }

    return $count;
}

/**
 * Get comment count from a work list (docs sent by students).
 *
 * @param array $workList
 * @param array $courseInfo
 *
 * @return int|null
 */
function getTotalWorkComment($workList, $courseInfo = [])
{
    if (empty($courseInfo)) {
        $courseInfo = api_get_course_info();
    }

    $count = 0;
    foreach ($workList as $data) {
        $count += getWorkCommentCount($data['id'], $courseInfo);
    }

    return $count;
}

/**
 * Get comment count for a specific work sent by a student.
 *
 * @param int   $id
 * @param array $courseInfo
 *
 * @return int
 */
function getWorkCommentCount($id, $courseInfo = [])
{
    if (empty($courseInfo)) {
        $courseInfo = api_get_course_info();
    }

    $commentTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT_COMMENT);
    $id = (int) $id;

    $sql = "SELECT count(*) as count
            FROM $commentTable
            WHERE work_id = $id AND c_id = ".$courseInfo['real_id'];

    $result = Database::query($sql);
    if (Database::num_rows($result)) {
        $comment = Database::fetch_array($result);

        return $comment['count'];
    }

    return 0;
}

/**
 * Get comment count for a specific parent.
 *
 * @param int   $parentId
 * @param array $courseInfo
 * @param int   $sessionId
 *
 * @return int
 */
function getWorkCommentCountFromParent(
    $parentId,
    $courseInfo = [],
    $sessionId = 0
) {
    if (empty($courseInfo)) {
        $courseInfo = api_get_course_info();
    }

    if (empty($sessionId)) {
        $sessionId = api_get_session_id();
    } else {
        $sessionId = (int) $sessionId;
    }

    $work = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $commentTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT_COMMENT);
    $parentId = (int) $parentId;
    $sessionCondition = api_get_session_condition($sessionId, false, false, 'w.session_id');

    $sql = "SELECT count(*) as count
            FROM $commentTable c INNER JOIN $work w
            ON c.c_id = w.c_id AND w.id = c.work_id
            WHERE
                $sessionCondition AND
                parent_id = $parentId AND
                w.c_id = ".$courseInfo['real_id'];

    $result = Database::query($sql);
    if (Database::num_rows($result)) {
        $comment = Database::fetch_array($result);

        return $comment['count'];
    }

    return 0;
}

/**
 * Get last work information from parent.
 *
 * @param int   $parentId
 * @param array $courseInfo
 * @param int   $sessionId
 *
 * @return int
 */
function getLastWorkStudentFromParent(
    $parentId,
    $courseInfo = [],
    $sessionId = 0
) {
    if (empty($courseInfo)) {
        $courseInfo = api_get_course_info();
    }

    if (empty($sessionId)) {
        $sessionId = api_get_session_id();
    } else {
        $sessionId = (int) $sessionId;
    }

    $work = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $sessionCondition = api_get_session_condition($sessionId, false);
    $commentTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT_COMMENT);
    $parentId = (int) $parentId;

    $sql = "SELECT w.*
            FROM $commentTable c INNER JOIN $work w
            ON c.c_id = w.c_id AND w.id = c.work_id
            WHERE
                $sessionCondition AND
                parent_id = $parentId AND
                w.c_id = ".$courseInfo['real_id'].'
            ORDER BY w.sent_date
            LIMIT 1
            ';

    $result = Database::query($sql);
    if (Database::num_rows($result)) {
        return Database::fetch_array($result, 'ASSOC');
    }

    return [];
}

/**
 * Get last work information from parent.
 *
 * @param int   $userId
 * @param array $parentInfo
 * @param array $courseInfo
 * @param int   $sessionId
 *
 * @return int
 */
function getLastWorkStudentFromParentByUser(
    $userId,
    $parentInfo,
    $courseInfo = [],
    $sessionId = 0
) {
    if (empty($courseInfo)) {
        $courseInfo = api_get_course_info();
    }

    if (empty($sessionId)) {
        $sessionId = api_get_session_id();
    } else {
        $sessionId = (int) $sessionId;
    }

    $userId = (int) $userId;
    $work = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    if (empty($parentInfo)) {
        return false;
    }
    $parentId = $parentInfo['id'];

    $sessionCondition = api_get_session_condition($sessionId);

    $sql = "SELECT *
            FROM $work
            WHERE
                user_id = $userId
                $sessionCondition AND
                parent_id = $parentId AND
                c_id = ".$courseInfo['real_id']."
            ORDER BY sent_date DESC
            LIMIT 1
            ";
    $result = Database::query($sql);
    if (Database::num_rows($result)) {
        $work = Database::fetch_array($result, 'ASSOC');
        $work['qualification_rounded'] = formatWorkScore($work['qualification'], $parentInfo['qualification']);

        return $work;
    }

    return [];
}

/**
 * @param float $score
 * @param int   $weight
 *
 * @return string
 */
function formatWorkScore($score, $weight)
{
    $label = 'info';
    $weight = (int) $weight;
    $relativeScore = 0;
    if (!empty($weight)) {
        $relativeScore = $score / $weight;
    }

    if ($relativeScore < 0.5) {
        $label = 'important';
    } elseif ($relativeScore < 0.75) {
        $label = 'warning';
    }

    $scoreBasedInModel = ExerciseLib::convertScoreToModel($relativeScore * 100);
    if (empty($scoreBasedInModel)) {
        $finalScore = api_number_format($score, 1).' / '.$weight;

        return Display::label(
            $finalScore,
            $label
        );
    } else {
        return $scoreBasedInModel;
    }
}

/**
 * @param int   $id         comment id
 * @param array $courseInfo
 *
 * @return string
 */
function getWorkComment($id, $courseInfo = [])
{
    if (empty($courseInfo)) {
        $courseInfo = api_get_course_info();
    }

    if (empty($courseInfo['real_id'])) {
        return [];
    }

    $commentTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT_COMMENT);
    $id = intval($id);

    $sql = "SELECT * FROM $commentTable
            WHERE id = $id AND c_id = ".$courseInfo['real_id'];
    $result = Database::query($sql);
    $comment = [];
    if (Database::num_rows($result)) {
        $comment = Database::fetch_array($result, 'ASSOC');
        $filePath = null;
        $fileUrl = null;
        $deleteUrl = null;
        $fileName = null;
        if (!empty($comment['file'])) {
            $work = get_work_data_by_id($comment['work_id']);
            $workParent = get_work_data_by_id($work['parent_id']);
            $filePath = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/work/'.$workParent['url'].'/'.$comment['file'];
            $fileUrl = api_get_path(WEB_CODE_PATH).'work/download_comment_file.php?comment_id='.$id.'&'.api_get_cidreq();
            $deleteUrl = api_get_path(WEB_CODE_PATH).'work/view.php?'.api_get_cidreq().'&id='.$comment['work_id'].'&action=delete_attachment&comment_id='.$id;
            $fileParts = explode('_', $comment['file']);
            $fileName = str_replace($fileParts[0].'_'.$fileParts[1].'_', '', $comment['file']);
        }
        $comment['delete_file_url'] = $deleteUrl;
        $comment['file_path'] = $filePath;
        $comment['file_url'] = $fileUrl;
        $comment['file_name_to_show'] = $fileName;
        $comment['sent_at_with_label'] = Display::dateToStringAgoAndLongDate($comment['sent_at']);
    }

    return $comment;
}

/**
 * @param int   $id
 * @param array $courseInfo
 */
function deleteCommentFile($id, $courseInfo = [])
{
    $workComment = getWorkComment($id, $courseInfo);
    if (isset($workComment['file']) && !empty($workComment['file'])) {
        if (file_exists($workComment['file_path'])) {
            $result = my_delete($workComment['file_path']);
            if ($result) {
                $commentTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT_COMMENT);
                $params = ['file' => ''];
                Database::update(
                    $commentTable,
                    $params,
                    ['id = ? AND c_id = ? ' => [$workComment['id'], $workComment['c_id']]]
                );
            }
        }
    }
}

/**
 * Adds a comments to the work document.
 *
 * @param array $courseInfo
 * @param int   $userId
 * @param array $parentWork
 * @param array $work
 * @param array $data
 *
 * @return int
 */
function addWorkComment($courseInfo, $userId, $parentWork, $work, $data)
{
    $fileData = isset($data['attachment']) ? $data['attachment'] : null;
    $commentTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT_COMMENT);

    // If no attachment and no comment then don't save comment
    if (empty($fileData['name']) && empty($data['comment'])) {
        return false;
    }

    $params = [
        'work_id' => $work['id'],
        'c_id' => $work['c_id'],
        'user_id' => $userId,
        'comment' => $data['comment'],
        'sent_at' => api_get_utc_datetime(),
    ];

    $commentId = Database::insert($commentTable, $params);

    if ($commentId) {
        Display::addFlash(
            Display::return_message(get_lang('CommentAdded'))
        );

        $sql = "UPDATE $commentTable SET id = iid WHERE iid = $commentId";
        Database::query($sql);
    }

    $userIdListToSend = [];
    if (api_is_allowed_to_edit()) {
        if (isset($data['send_email']) && $data['send_email']) {
            // Teacher sends a feedback
            $userIdListToSend = [$work['user_id']];
        }
    } else {
        $sessionId = api_get_session_id();
        if (empty($sessionId)) {
            $teachers = CourseManager::get_teacher_list_from_course_code(
                $courseInfo['code']
            );
            if (!empty($teachers)) {
                $userIdListToSend = array_keys($teachers);
            }
        } else {
            $teachers = SessionManager::getCoachesByCourseSession(
                $sessionId,
                $courseInfo['real_id']
            );

            if (!empty($teachers)) {
                $userIdListToSend = array_values($teachers);
            }
        }

        $sendNotification = api_get_course_setting('email_to_teachers_on_new_work_feedback');
        if ($sendNotification != 1) {
            $userIdListToSend = [];
        }
    }

    $url = api_get_path(WEB_CODE_PATH).'work/view.php?'.api_get_cidreq().'&id='.$work['id'];
    $subject = sprintf(get_lang('ThereIsANewWorkFeedback'), $parentWork['title']);
    $content = sprintf(get_lang('ThereIsANewWorkFeedbackInWorkXHere'), $work['title'], $url);

    if (!empty($data['comment'])) {
        $content .= '<br /><b>'.get_lang('Comment').':</b><br />'.$data['comment'];
    }

    if (!empty($userIdListToSend)) {
        foreach ($userIdListToSend as $userIdToSend) {
            MessageManager::send_message_simple(
                $userIdToSend,
                $subject,
                $content
            );
        }
    }

    if (!empty($commentId) && !empty($fileData)) {
        $workParent = get_work_data_by_id($work['parent_id']);
        if (!empty($workParent)) {
            $uploadDir = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/work'.$workParent['url'];
            $newFileName = 'comment_'.$commentId.'_'.php2phps(api_replace_dangerous_char($fileData['name']));
            $newFilePath = $uploadDir.'/'.$newFileName;
            $result = move_uploaded_file($fileData['tmp_name'], $newFilePath);
            if ($result) {
                $params = ['file' => $newFileName];
                Database::update(
                    $commentTable,
                    $params,
                    ['id = ? AND c_id = ? ' => [$commentId, $work['c_id']]]
                );
            }
        }
    }
}

/**
 * @param array $work
 * @param array $workParent
 *
 * @return string
 */
function getWorkCommentForm($work, $workParent)
{
    $url = api_get_path(WEB_CODE_PATH).'work/view.php?id='.$work['id'].'&action=send_comment&'.api_get_cidreq();
    $form = new FormValidator(
        'work_comment',
        'post',
        $url,
        '',
        ['enctype' => "multipart/form-data"]
    );

    $qualification = $workParent['qualification'];

    $isCourseManager = api_is_platform_admin() || api_is_coach() || api_is_allowed_to_edit(false, false, true);
    $allowEdition = false;
    if ($isCourseManager) {
        $allowEdition = true;
        if (!empty($work['qualification']) && api_get_configuration_value('block_student_publication_score_edition')) {
            $allowEdition = false;
        }
    }

    if (api_is_platform_admin()) {
        $allowEdition = true;
    }

    if ($allowEdition) {
        if (!empty($qualification) && intval($qualification) > 0) {
            $model = ExerciseLib::getCourseScoreModel();
            if (empty($model)) {
                $form->addFloat(
                    'qualification',
                    [get_lang('Qualification'), " / ".$qualification],
                    false,
                    [],
                    false,
                    0,
                    $qualification
                );
            } else {
                ExerciseLib::addScoreModelInput(
                    $form,
                    'qualification',
                    $qualification,
                    $work['qualification']
                );
            }
            $form->addFile('file', get_lang('Correction'));
            $form->setDefaults(['qualification' => $work['qualification']]);
        }
    }

    Skill::addSkillsToUserForm($form, ITEM_TYPE_STUDENT_PUBLICATION, $workParent['id'], $work['user_id'], $work['id']);
    $form->addHtmlEditor('comment', get_lang('Comment'), false);
    $form->addFile('attachment', get_lang('Attachment'));
    $form->addElement('hidden', 'id', $work['id']);

    if (api_is_allowed_to_edit()) {
        $form->addCheckBox(
            'send_email',
            null,
            get_lang('SendMailToStudent')
        );
    }

    $form->addButtonSend(get_lang('Send'), 'button', false, ['onclick' => 'this.form.submit();this.disabled=true;']);

    return $form->returnForm();
}

/**
 * @param array $homework result of get_work_assignment_by_id()
 *
 * @return array
 */
function getWorkDateValidationStatus($homework)
{
    $message = null;
    $has_expired = false;
    $has_ended = false;

    if (!empty($homework)) {
        if (!empty($homework['expires_on']) || !empty($homework['ends_on'])) {
            $time_now = time();

            if (!empty($homework['expires_on'])) {
                $time_expires = api_strtotime($homework['expires_on'], 'UTC');
                $difference = $time_expires - $time_now;
                if ($difference < 0) {
                    $has_expired = true;
                }
            }

            if (empty($homework['expires_on'])) {
                $has_expired = false;
            }

            if (!empty($homework['ends_on'])) {
                $time_ends = api_strtotime($homework['ends_on'], 'UTC');
                $difference2 = $time_ends - $time_now;
                if ($difference2 < 0) {
                    $has_ended = true;
                }
            }

            $ends_on = api_convert_and_format_date($homework['ends_on']);
            $expires_on = api_convert_and_format_date($homework['expires_on']);
        }

        if ($has_ended) {
            $message = Display::return_message(get_lang('EndDateAlreadyPassed').' '.$ends_on, 'error');
        } elseif ($has_expired) {
            $message = Display::return_message(get_lang('ExpiryDateAlreadyPassed').' '.$expires_on, 'warning');
        } else {
            if ($has_expired) {
                $message = Display::return_message(get_lang('ExpiryDateToSendWorkIs').' '.$expires_on);
            }
        }
    }

    return [
        'message' => $message,
        'has_ended' => $has_ended,
        'has_expired' => $has_expired,
    ];
}

/**
 * @param FormValidator $form
 * @param int           $uploadFormType
 */
function setWorkUploadForm($form, $uploadFormType = 0)
{
    $form->addHeader(get_lang('UploadADocument'));
    $form->addHidden('contains_file', 0, ['id' => 'contains_file_id']);
    $form->addHidden('active', 1);
    $form->addHidden('accepted', 1);
    $form->addElement('text', 'title', get_lang('Title'), ['id' => 'file_upload']);
    $form->addElement(
        'text',
        'extension',
        get_lang('FileExtension'),
        ['id' => 'file_extension', 'readonly' => 'readonly']
    );
    $form->addRule('title', get_lang('ThisFieldIsRequired'), 'required');

    switch ($uploadFormType) {
        case 0:
            // File and text.
            $form->addElement(
                'file',
                'file',
                get_lang('UploadADocument'),
                'size="40" onchange="updateDocumentTitle(this.value)"'
            );
            $form->addProgress();
            $form->addHtmlEditor('description', get_lang('Description'), false, false, getWorkDescriptionToolbar());
            break;
        case 1:
            // Only text.
            $form->addHtmlEditor('description', get_lang('Description'), false, false, getWorkDescriptionToolbar());
            $form->addRule('description', get_lang('ThisFieldIsRequired'), 'required');
            break;
        case 2:
            // Only file.
            /*$form->addElement(
                'file',
                'file',
                get_lang('UploadADocument'),
                'size="40" onchange="updateDocumentTitle(this.value)"'
            );
            $form->addProgress();
            */
            $form->addElement('BigUpload', 'file', get_lang('UploadADocument'), ['id' => 'bigUploadFile', 'data-origin' => 'work']);
            $form->addRule('file', get_lang('ThisFieldIsRequired'), 'required');
            break;
    }

    $form->addButtonUpload(get_lang('Upload'), 'submitWork');
}

/**
 * @param array $my_folder_data
 * @param array $_course
 * @param bool  $isCorrection
 * @param array $workInfo
 * @param array $file
 *
 * @return array
 */
function uploadWork($my_folder_data, $_course, $isCorrection = false, $workInfo = [], $file = [])
{
    if (isset($_FILES['file']) && !empty($_FILES['file'])) {
        $file = $_FILES['file'];
    }

    if (empty($file['size'])) {
        return [
            'error' => Display::return_message(
                get_lang('UplUploadFailedSizeIsZero'),
                'error'
            ),
        ];
    }
    $updir = api_get_path(SYS_COURSE_PATH).$_course['path'].'/work/'; //directory path to upload

    // Try to add an extension to the file if it has'nt one
    $filename = add_ext_on_mime(stripslashes($file['name']), $file['type']);

    // Replace dangerous characters
    $filename = api_replace_dangerous_char($filename);

    // Transform any .php file in .phps fo security
    $filename = php2phps($filename);
    $filesize = filesize($file['tmp_name']);

    if (empty($filesize)) {
        return [
            'error' => Display::return_message(
                get_lang('UplUploadFailedSizeIsZero'),
                'error'
            ),
        ];
    } elseif (!filter_extension($new_file_name)) {
        return [
            'error' => Display::return_message(
                get_lang('UplUnableToSaveFileFilteredExtension'),
                'error'
            ),
        ];
    }

    $totalSpace = DocumentManager::documents_total_space($_course['real_id']);
    $course_max_space = DocumentManager::get_course_quota($_course['code']);
    $total_size = $filesize + $totalSpace;

    if ($total_size > $course_max_space) {
        return [
            'error' => Display::return_message(get_lang('NoSpace'), 'error'),
        ];
    }

    // Compose a unique file name to avoid any conflict
    $new_file_name = api_get_unique_id();

    if ($isCorrection) {
        if (!empty($workInfo['url'])) {
            $new_file_name = basename($workInfo['url']).'_correction';
        } else {
            $new_file_name = $new_file_name.'_correction';
        }
    }

    $curdirpath = basename($my_folder_data['url']);

    // If we come from the group tools the groupid will be saved in $work_table
    if (is_dir($updir.$curdirpath) || empty($curdirpath)) {
        if (isset($file['copy_file'])) {
            $result = copy(
                $file['tmp_name'],
                $updir.$curdirpath.'/'.$new_file_name
            );
            unlink($file['tmp_name']);
        } else {
            $result = move_uploaded_file(
                $file['tmp_name'],
                $updir.$curdirpath.'/'.$new_file_name
            );
        }
    } else {
        return [
            'error' => Display::return_message(
                get_lang('FolderDoesntExistsInFileSystem'),
                'error'
            ),
        ];
    }

    if ($result) {
        $url = 'work/'.$curdirpath.'/'.$new_file_name;
    } else {
        return false;
    }

    return [
        'url' => $url,
        'filename' => $filename,
        'filesize' => $filesize,
        'error' => '',
    ];
}

/**
 * Send an e-mail to users related to this work.
 *
 * @param array $workInfo
 * @param int   $workId
 * @param array $courseInfo
 * @param int   $sessionId
 */
function sendAlertToUsers($workInfo, $workId, $courseInfo, $sessionId = 0)
{
    $sessionId = (int) $sessionId;

    if (empty($workInfo) || empty($courseInfo) || empty($workId)) {
        return false;
    }

    $courseCode = $courseInfo['code'];

    $workData = get_work_data_by_id($workId, $courseInfo['real_id'], $sessionId);
    // last value is to check this is not "just" an edit
    // YW Tis part serve to send a e-mail to the tutors when a new file is sent
    $send = api_get_course_setting('email_alert_manager_on_new_doc');

    $userList = [];
    if ($send == SEND_EMAIL_EVERYONE || $send == SEND_EMAIL_TEACHERS) {
        // Lets predefine some variables. Be sure to change the from address!
        if (empty($sessionId)) {
            // Teachers
            $userList = CourseManager::get_user_list_from_course_code(
                $courseCode,
                null,
                null,
                null,
                COURSEMANAGER
            );
        } else {
            // Coaches
            $userList = CourseManager::get_user_list_from_course_code(
                $courseCode,
                $sessionId,
                null,
                null,
                2
            );
        }
    }

    if ($send == SEND_EMAIL_EVERYONE || $send == SEND_EMAIL_STUDENTS) {
        // Send mail only to sender
        $studentList = [[
           'user_id' => api_get_user_id(),
        ]];
        $userList = array_merge($userList, $studentList);
    }

    if ($send) {
        $folderUrl = api_get_path(WEB_CODE_PATH)."work/work_list_all.php?cidReq=".$courseInfo['code']."&id_session=".$sessionId."&id=".$workInfo['id'];
        $fileUrl = api_get_path(WEB_CODE_PATH)."work/view.php?cidReq=".$courseInfo['code']."&id_session=".$sessionId."&id=".$workData['id'];

        foreach ($userList as $userData) {
            $userId = $userData['user_id'];
            $userInfo = api_get_user_info($userId);
            if (empty($userInfo)) {
                continue;
            }

            $userPostedADocument = sprintf(
                get_lang('UserXPostedADocumentInCourseX'),
                $userInfo['complete_name'],
                $courseInfo['name']
            );

            $subject = "[".api_get_setting('siteName')."] ".$userPostedADocument;
            $message = $userPostedADocument."<br />";
            $message .= get_lang('DateSent')." : ".api_format_date(api_get_local_time())."<br />";
            $message .= get_lang('AssignmentName')." : ".Display::url($workInfo['title'], $folderUrl)."<br />";
            $message .= get_lang('Filename')." : ".$workData['title']."<br />";
            $message .= '<a href="'.$fileUrl.'">'.get_lang('DownloadLink')."</a><br />";

            MessageManager::send_message_simple(
                $userId,
                $subject,
                $message,
                0,
                false,
                false,
                [],
                false
            );
        }
    }
}

/**
 * Check if the current uploaded work filename already exists in the current assement.
 *
 * @param string $filename
 * @param int    $workId
 *
 * @return array
 */
function checkExistingWorkFileName($filename, $workId)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $filename = Database::escape_string($filename);
    $workId = (int) $workId;

    $sql = "SELECT title FROM $table
            WHERE parent_id = $workId AND title = '$filename' AND active = 1";
    $result = Database::query($sql);

    return Database::fetch_assoc($result);
}

/**
 * @param array $workInfo
 * @param array $values
 * @param array $courseInfo
 * @param int   $sessionId
 * @param int   $groupId
 * @param int   $userId
 * @param array $file
 * @param bool  $checkDuplicated
 * @param bool  $showFlashMessage
 *
 * @return string|null
 */
function processWorkForm(
    $workInfo,
    $values,
    $courseInfo,
    $sessionId,
    $groupId,
    $userId,
    $file = [],
    $checkDuplicated = false,
    $showFlashMessage = true
) {
    $work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

    $courseId = $courseInfo['real_id'];
    $groupId = (int) $groupId;
    $sessionId = (int) $sessionId;
    $userId = (int) $userId;

    $extension = '';
    if (isset($values['extension'])) {
        $extension = $values['extension'];
    } else {
        $fileInfo = pathinfo($values['title']);
        if (isset($fileInfo['extension']) && !empty($fileInfo['extension'])) {
            $extension = '.'.$fileInfo['extension'];
            $values['title'] = $fileInfo['filename'];
        }
    }

    $title = $values['title'].$extension;
    $description = isset($values['description']) ? $values['description'] : '';
    $containsFile = isset($values['contains_file']) && !empty($values['contains_file']) ? (int) $values['contains_file'] : 0;

    $saveWork = true;
    $filename = null;
    $url = null;
    $filesize = null;
    $workData = [];
    $message = null;

    if ($containsFile) {
        $saveWork = false;
        if ($checkDuplicated) {
            if (checkExistingWorkFileName($file['name'], $workInfo['id'])) {
                $saveWork = false;
                $result['error'] = get_lang('YouAlreadySentThisFile');
                $workData['error'] = get_lang('UplAlreadyExists');
            } else {
                $result = uploadWork($workInfo, $courseInfo, false, [], $file);
            }
        } else {
            $result = uploadWork($workInfo, $courseInfo, false, [], $file);
        }

        if (isset($result['error'])) {
            $saveWork = false;
            if ($showFlashMessage) {
                $message = $result['error'];
            }
            if (empty($result['error']) && isset($result['url']) && !empty($result['url'])) {
                $saveWork = true;
            }
        }
    }

    if ($saveWork) {
        $filename = isset($result['filename']) ? $result['filename'] : null;
        if (empty($title)) {
            $title = isset($result['title']) && !empty($result['title']) ? $result['title'] : get_lang('Untitled');
        }
        $filesize = isset($result['filesize']) ? $result['filesize'] : null;
        $url = isset($result['url']) ? $result['url'] : null;
    }

    if (empty($title)) {
        $title = get_lang('Untitled');
    }

    $groupIid = 0;
    $groupInfo = [];
    if ($groupId) {
        $groupInfo = GroupManager::get_group_properties($groupId);
        $groupIid = $groupInfo['iid'];
    }

    if ($saveWork) {
        $active = '1';
        $params = [
            'c_id' => $courseId,
            'url' => $url,
            'filetype' => 'file',
            'title' => $title,
            'description' => $description,
            'contains_file' => $containsFile,
            'active' => $active,
            'accepted' => '1',
            'qualificator_id' => 0,
            'document_id' => 0,
            'weight' => 0,
            'allow_text_assignment' => 0,
            'post_group_id' => $groupIid,
            'sent_date' => api_get_utc_datetime(),
            'parent_id' => $workInfo['id'],
            'session_id' => $sessionId ? $sessionId : null,
            'user_id' => $userId,
            'has_properties' => 0,
            'qualification' => 0,
            //'filesize' => $filesize
        ];
        $workId = Database::insert($work_table, $params);

        if ($workId) {
            $sql = "UPDATE $work_table SET id = iid WHERE iid = $workId ";
            Database::query($sql);

            if (array_key_exists('filename', $workInfo) && !empty($filename)) {
                $filename = Database::escape_string($filename);
                $sql = "UPDATE $work_table SET
                            filename = '$filename'
                        WHERE iid = $workId";
                Database::query($sql);
            }

            if (array_key_exists('document_id', $workInfo)) {
                $documentId = isset($values['document_id']) ? (int) $values['document_id'] : 0;
                $sql = "UPDATE $work_table SET
                            document_id = '$documentId'
                        WHERE iid = $workId";
                Database::query($sql);
            }

            api_item_property_update(
                $courseInfo,
                'work',
                $workId,
                'DocumentAdded',
                $userId,
                $groupInfo
            );
            sendAlertToUsers($workInfo, $workId, $courseInfo, $sessionId);
            Event::event_upload($workId);

            // The following feature requires the creation of a work-type
            // extra_field and the following setting in the configuration file
            // (until moved to the database). It allows te teacher to set a
            // "considered work time", meaning the time we assume a student
            // would have spent, approximately, to prepare the task before
            // handing it in Chamilo, adding this time to the student total
            // course use time, as a register of time spent *before* his
            // connection to the platform to hand the work in.
            $consideredWorkingTime = api_get_configuration_value('considered_working_time');

            if (!empty($consideredWorkingTime)) {
                // Get the "considered work time" defined for this work
                $fieldValue = new ExtraFieldValue('work');
                $resultExtra = $fieldValue->getAllValuesForAnItem(
                    $workInfo['iid'], //the ID of the work *folder*, not the document uploaded by the student
                    true
                );

                $workingTime = null;
                foreach ($resultExtra as $field) {
                    $field = $field['value'];
                    if ($consideredWorkingTime == $field->getField()->getVariable()) {
                        $workingTime = $field->getValue();
                    }
                }

                // If no time was defined, or a time of "0" was set, do nothing
                if (!empty($workingTime)) {
                    // If some time is set, get the list of docs handed in by
                    // this student (to make sure we count the time only once)
                    $userWorks = get_work_user_list(
                        0,
                        100,
                        null,
                        null,
                        $workInfo['id'],
                        null,
                        $userId,
                        false,
                        $courseId,
                        $sessionId
                    );

                    if (1 == count($userWorks)) {
                        // The student only uploaded one doc so far, so add the
                        // considered work time to his course connection time
                        Event::eventAddVirtualCourseTime(
                            $courseId,
                            $userId,
                            $sessionId,
                            $workingTime,
                            $workInfo['iid']
                        );
                    }
                }
            }
            $workData = get_work_data_by_id($workId);
            if ($workData && $showFlashMessage) {
                Display::addFlash(Display::return_message(get_lang('DocAdd')));
            }
        }
    } else {
        if ($showFlashMessage) {
            Display::addFlash(
                Display::return_message(
                    $message ? $message : get_lang('ImpossibleToSaveTheDocument'),
                    'error'
                )
            );
        }
    }

    return $workData;
}

/**
 * Creates a new task (directory) in the assignment tool.
 *
 * @param array $formValues
 * @param int   $user_id
 * @param array $courseInfo
 * @param int   $groupId
 * @param int   $sessionId
 *
 * @return bool|int
 * @note $params can have the following elements, but should at least have the 2 first ones: (
 *       'new_dir' => 'some-name',
 *       'description' => 'some-desc',
 *       'qualification' => 20 (e.g. 20),
 *       'weight' => 50 (percentage) to add to gradebook (e.g. 50),
 *       'allow_text_assignment' => 0/1/2,
 *
 * @todo Rename createAssignment or createWork, or something like that
 */
function addDir($formValues, $user_id, $courseInfo, $groupId, $sessionId = 0)
{
    $em = Database::getManager();

    $user_id = (int) $user_id;
    $groupId = (int) $groupId;
    $sessionId = (int) $sessionId;

    $groupIid = 0;
    $groupInfo = [];
    if (!empty($groupId)) {
        $groupInfo = GroupManager::get_group_properties($groupId);
        $groupIid = $groupInfo['iid'];
    }
    $session = $em->find('ChamiloCoreBundle:Session', $sessionId);

    $base_work_dir = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/work';
    $course_id = $courseInfo['real_id'];

    $directory = api_replace_dangerous_char($formValues['new_dir']);
    $directory = disable_dangerous_file($directory);

    if (strlen($directory) > CStudentPublication::WORK_TITLE_MAX_LENGTH) {
        $directory = api_substr($directory, 0, CStudentPublication::WORK_TITLE_MAX_LENGTH);
    }

    $created_dir = create_unexisting_work_directory($base_work_dir, $directory);

    if (empty($created_dir)) {
        return false;
    }

    $enableEndDate = isset($formValues['enableEndDate']) ? true : false;
    $enableExpiryDate = isset($formValues['enableExpiryDate']) ? true : false;

    if ($enableEndDate && $enableExpiryDate) {
        if ($formValues['expires_on'] > $formValues['ends_on']) {
            Display::addFlash(
                Display::return_message(
                    get_lang('DateExpiredNotBeLessDeadLine'),
                    'warning'
                )
            );

            return false;
        }
    }

    $dirName = '/'.$created_dir;
    $today = new DateTime(api_get_utc_datetime(), new DateTimeZone('UTC'));
    $title = isset($formValues['work_title']) ? $formValues['work_title'] : $formValues['new_dir'];

    $workTable = new CStudentPublication();
    $workTable
        ->setCId($course_id)
        ->setUrl($dirName)
        ->setTitle($title)
        ->setDescription($formValues['description'])
        ->setActive(true)
        ->setAccepted(true)
        ->setFiletype('folder')
        ->setPostGroupId($groupIid)
        ->setSentDate($today)
        ->setQualification($formValues['qualification'] != '' ? $formValues['qualification'] : 0)
        ->setParentId(0)
        ->setQualificatorId(0)
        ->setWeight(!empty($formValues['weight']) ? $formValues['weight'] : 0)
        ->setSession($session)
        ->setAllowTextAssignment($formValues['allow_text_assignment'])
        ->setContainsFile(0)
        ->setUserId($user_id)
        ->setHasProperties(0)
        ->setDocumentId(0);

    $em->persist($workTable);
    $em->flush();

    $workTable->setId($workTable->getIid());
    $em->merge($workTable);
    $em->flush();

    // Folder created
    api_item_property_update(
        $courseInfo,
        'work',
        $workTable->getIid(),
        'DirectoryCreated',
        $user_id,
        $groupInfo
    );

    updatePublicationAssignment(
        $workTable->getIid(),
        $formValues,
        $courseInfo,
        $groupIid
    );

    // Added the new Work ID to the extra field values
    $formValues['item_id'] = $workTable->getIid();

    $workFieldValue = new ExtraFieldValue('work');
    $workFieldValue->saveFieldValues($formValues);

    $sendEmailAlert = api_get_course_setting('email_alert_students_on_new_homework');

    switch ($sendEmailAlert) {
        case 1:
            sendEmailToStudentsOnHomeworkCreation(
                $workTable->getIid(),
                $course_id,
                $sessionId
            );
            //no break
        case 2:
            sendEmailToDrhOnHomeworkCreation(
                $workTable->getIid(),
                $course_id,
                $sessionId
            );
            break;
    }

    return $workTable->getIid();
}

/**
 * @param int   $workId
 * @param array $courseInfo
 *
 * @return int
 */
function agendaExistsForWork($workId, $courseInfo)
{
    $workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
    $courseId = $courseInfo['real_id'];
    $workId = (int) $workId;

    $sql = "SELECT add_to_calendar FROM $workTable
            WHERE c_id = $courseId AND publication_id = ".$workId;
    $res = Database::query($sql);
    if (Database::num_rows($res)) {
        $row = Database::fetch_array($res, 'ASSOC');
        if (!empty($row['add_to_calendar'])) {
            return $row['add_to_calendar'];
        }
    }

    return 0;
}

/**
 * Update work description, qualification, weight, allow_text_assignment.
 *
 * @param int   $workId     (iid)
 * @param array $params
 * @param array $courseInfo
 * @param int   $sessionId
 */
function updateWork($workId, $params, $courseInfo, $sessionId = 0)
{
    $workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $filteredParams = [
        'description' => $params['description'],
        'qualification' => $params['qualification'],
        'weight' => $params['weight'],
        'allow_text_assignment' => $params['allow_text_assignment'],
    ];

    Database::update(
        $workTable,
        $filteredParams,
        [
            'iid = ? AND c_id = ?' => [
                $workId,
                $courseInfo['real_id'],
            ],
        ]
    );

    $workFieldValue = new ExtraFieldValue('work');
    $workFieldValue->saveFieldValues($params);
}

/**
 * @param int   $workId
 * @param array $params
 * @param array $courseInfo
 * @param int   $groupId
 */
function updatePublicationAssignment($workId, $params, $courseInfo, $groupId)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
    $workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $workId = (int) $workId;
    $now = api_get_utc_datetime();
    $course_id = $courseInfo['real_id'];

    // Insert into agenda
    $agendaId = 0;
    if (isset($params['add_to_calendar']) && $params['add_to_calendar'] == 1) {
        // Setting today date
        $date = $end_date = $now;

        if (isset($params['enableExpiryDate'])) {
            $end_date = $params['expires_on'];
            $date = $end_date;
        }

        $title = sprintf(get_lang('HandingOverOfTaskX'), $params['new_dir']);
        $description = isset($params['description']) ? $params['description'] : '';
        $content = '<a href="'.api_get_path(WEB_CODE_PATH).'work/work_list.php?'.api_get_cidreq().'&id='.$workId.'">'
            .$params['new_dir'].'</a>'.$description;

        $agendaId = agendaExistsForWork($workId, $courseInfo);

        // Add/edit agenda
        $agenda = new Agenda('course');
        $agenda->set_course($courseInfo);

        if (!empty($agendaId)) {
            // add_to_calendar is set but it doesnt exists then invalidate
            $eventInfo = $agenda->get_event($agendaId);
            if (empty($eventInfo)) {
                $agendaId = 0;
            }
        }

        $eventColor = $agenda->eventStudentPublicationColor;

        if (empty($agendaId)) {
            $agendaId = $agenda->addEvent(
                $date,
                $end_date,
                'false',
                $title,
                $content,
                ['GROUP:'.$groupId],
                false,
                null,
                [],
                [],
                null,
                $eventColor
            );
        } else {
            $agenda->editEvent(
                $agendaId,
                $end_date,
                $end_date,
                'false',
                $title,
                $content,
                [],
                [],
                [],
                null,
                $eventColor
            );
        }
    }

    $qualification = isset($params['qualification']) && !empty($params['qualification']) ? 1 : 0;
    $expiryDate = isset($params['enableExpiryDate']) && (int) $params['enableExpiryDate'] == 1 ? api_get_utc_datetime($params['expires_on']) : '';
    $endDate = isset($params['enableEndDate']) && (int) $params['enableEndDate'] == 1 ? api_get_utc_datetime($params['ends_on']) : '';
    $data = get_work_assignment_by_id($workId, $course_id);
    if (!empty($expiryDate)) {
        $expiryDateCondition = "expires_on = '".Database::escape_string($expiryDate)."', ";
    } else {
        $expiryDateCondition = "expires_on = null, ";
    }

    if (!empty($endDate)) {
        $endOnCondition = "ends_on = '".Database::escape_string($endDate)."', ";
    } else {
        $endOnCondition = 'ends_on = null, ';
    }

    if (empty($data)) {
        $sql = "INSERT INTO $table SET
                c_id = $course_id ,
                $expiryDateCondition
                $endOnCondition
                add_to_calendar = $agendaId,
                enable_qualification = '$qualification',
                publication_id = '$workId'";
        Database::query($sql);
        $my_last_id = Database::insert_id();

        if ($my_last_id) {
            $sql = "UPDATE $table SET
                        id = iid
                    WHERE iid = $my_last_id";
            Database::query($sql);

            $sql = "UPDATE $workTable SET
                        has_properties  = $my_last_id,
                        view_properties = 1
                    WHERE c_id = $course_id AND id = $workId";
            Database::query($sql);
        }
    } else {
        $sql = "UPDATE $table SET
                    $expiryDateCondition
                    $endOnCondition
                    add_to_calendar  = $agendaId,
                    enable_qualification = '".$qualification."'
                WHERE
                    publication_id = $workId AND
                    c_id = $course_id AND
                    iid = ".$data['iid'];
        Database::query($sql);
    }

    if (!empty($params['category_id'])) {
        $link_info = GradebookUtils::isResourceInCourseGradebook(
            $courseInfo['code'],
            LINK_STUDENTPUBLICATION,
            $workId,
            api_get_session_id()
        );

        $linkId = null;
        if (!empty($link_info)) {
            $linkId = $link_info['id'];
        }

        if (isset($params['make_calification']) &&
            $params['make_calification'] == 1
        ) {
            if (empty($linkId)) {
                GradebookUtils::add_resource_to_course_gradebook(
                    $params['category_id'],
                    $courseInfo['code'],
                    LINK_STUDENTPUBLICATION,
                    $workId,
                    $params['new_dir'],
                    api_float_val($params['weight']),
                    api_float_val($params['qualification']),
                    $params['description'],
                    1,
                    api_get_session_id()
                );
            } else {
                GradebookUtils::updateResourceFromCourseGradebook(
                    $linkId,
                    $courseInfo['code'],
                    $params['weight']
                );
            }
        } else {
            // Delete everything of the gradebook for this $linkId
            GradebookUtils::remove_resource_from_course_gradebook($linkId);
        }
    }
}

/**
 * Delete all work by student.
 *
 * @param int   $userId
 * @param array $courseInfo
 *
 * @return array return deleted items
 */
function deleteAllWorkPerUser($userId, $courseInfo)
{
    $deletedItems = [];
    $workPerUser = getWorkPerUser($userId);
    if (!empty($workPerUser)) {
        foreach ($workPerUser as $work) {
            $work = $work['work'];
            foreach ($work->user_results as $userResult) {
                $result = deleteWorkItem($userResult['id'], $courseInfo);
                if ($result) {
                    $deletedItems[] = $userResult;
                }
            }
        }
    }

    return $deletedItems;
}

/**
 * @param int   $item_id
 * @param array $courseInfo course info
 *
 * @return bool
 */
function deleteWorkItem($item_id, $courseInfo)
{
    $item_id = (int) $item_id;

    if (empty($item_id) || empty($courseInfo)) {
        return false;
    }

    $work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $TSTDPUBASG = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
    $currentCourseRepositorySys = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/';
    $is_allowed_to_edit = api_is_allowed_to_edit();
    $file_deleted = false;
    $is_author = user_is_author($item_id);
    $work_data = get_work_data_by_id($item_id);
    $locked = api_resource_is_locked_by_gradebook($work_data['parent_id'], LINK_STUDENTPUBLICATION);
    $course_id = $courseInfo['real_id'];

    if (($is_allowed_to_edit && $locked == false) ||
        (
            $locked == false &&
            $is_author &&
            api_get_course_setting('student_delete_own_publication') == 1 &&
            $work_data['qualificator_id'] == 0
        )
    ) {
        // We found the current user is the author
        $sql = "SELECT url, contains_file, user_id, session_id, parent_id
                FROM $work_table
                WHERE c_id = $course_id AND id = $item_id";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $count = Database::num_rows($result);

        if ($count > 0) {
            // If the "considered_working_time" option is enabled, check
            // whether some time should be removed from track_e_course_access
            $consideredWorkingTime = api_get_configuration_value('considered_working_time');
            if ($consideredWorkingTime) {
                $userWorks = get_work_user_list(
                    0,
                    100,
                    null,
                    null,
                    $row['parent_id'],
                    null,
                    $row['user_id'],
                    false,
                    $course_id,
                    $row['session_id']
                );
                // We're only interested in deleting the time if this is the latest work sent
                if (count($userWorks) == 1) {
                    // Get the "considered work time" defined for this work
                    $fieldValue = new ExtraFieldValue('work');
                    $resultExtra = $fieldValue->getAllValuesForAnItem(
                        $row['parent_id'],
                        true
                    );

                    $workingTime = null;
                    foreach ($resultExtra as $field) {
                        $field = $field['value'];

                        if ($consideredWorkingTime == $field->getField()->getVariable()) {
                            $workingTime = $field->getValue();
                        }
                    }
                    // If no time was defined, or a time of "0" was set, do nothing
                    if (!empty($workingTime)) {
                        $sessionId = empty($row['session_id']) ? 0 : $row['session_id'];
                        // Getting false from the following call would mean the
                        // time record
                        Event::eventRemoveVirtualCourseTime(
                            $course_id,
                            $row['user_id'],
                            $sessionId,
                            $workingTime,
                            $row['parent_id']
                        );
                    }
                }
            } // end of considered_working_time check section

            $sql = "UPDATE $work_table SET active = 2
                    WHERE c_id = $course_id AND id = $item_id";
            Database::query($sql);
            $sql = "DELETE FROM $TSTDPUBASG
                    WHERE c_id = $course_id AND publication_id = $item_id";
            Database::query($sql);

            Compilatio::plagiarismDeleteDoc($course_id, $item_id);

            api_item_property_update(
                $courseInfo,
                'work',
                $item_id,
                'DocumentDeleted',
                api_get_user_id()
            );

            Event::addEvent(
                LOG_WORK_FILE_DELETE,
                LOG_WORK_DATA,
                [
                    'id' => $work_data['id'],
                    'url' => $work_data['url'],
                    'title' => $work_data['title'],
                ],
                null,
                api_get_user_id(),
                api_get_course_int_id(),
                api_get_session_id()
            );

            $work = $row['url'];

            if ($row['contains_file'] == 1) {
                if (!empty($work)) {
                    if (api_get_setting('permanently_remove_deleted_files') === 'true') {
                        my_delete($currentCourseRepositorySys.'/'.$work);
                        $file_deleted = true;
                    } else {
                        $extension = pathinfo($work, PATHINFO_EXTENSION);
                        $new_dir = $work.'_DELETED_'.$item_id.'.'.$extension;

                        if (file_exists($currentCourseRepositorySys.'/'.$work)) {
                            rename($currentCourseRepositorySys.'/'.$work, $currentCourseRepositorySys.'/'.$new_dir);
                            $file_deleted = true;
                        }
                    }
                }
            } else {
                $file_deleted = true;
            }
        }
    }

    return $file_deleted;
}

/**
 * @param FormValidator $form
 * @param array         $defaults
 * @param int           $workId
 *
 * @return FormValidator
 */
function getFormWork($form, $defaults = [], $workId = 0)
{
    $sessionId = api_get_session_id();
    if (!empty($defaults)) {
        if (isset($defaults['submit'])) {
            unset($defaults['submit']);
        }
    }

    // Create the form that asks for the directory name
    $form->addText(
        'new_dir',
        get_lang('AssignmentName'),
        true,
        ['maxlength' => 255]
    );
    $form->addHtmlEditor(
        'description',
        get_lang('Description'),
        false,
        false,
        getWorkDescriptionToolbar()
    );
    $form->addButtonAdvancedSettings('advanced_params', get_lang('AdvancedParameters'));

    if (!empty($defaults) && (isset($defaults['enableEndDate']) || isset($defaults['enableExpiryDate']))) {
        $form->addHtml('<div id="advanced_params_options" style="display:block">');
    } else {
        $form->addHtml('<div id="advanced_params_options" style="display:none">');
    }

    // QualificationOfAssignment
    $form->addElement('text', 'qualification', get_lang('QualificationNumeric'));

    if (($sessionId != 0 && Gradebook::is_active()) || $sessionId == 0) {
        $form->addElement(
            'checkbox',
            'make_calification',
            null,
            get_lang('MakeQualifiable'),
            [
                'id' => 'make_calification_id',
                'onclick' => "javascript: if(this.checked) { document.getElementById('option1').style.display='block';}else{document.getElementById('option1').style.display='none';}",
            ]
        );
    } else {
        // QualificationOfAssignment
        $form->addElement('hidden', 'make_calification', false);
    }

    if (!empty($defaults) && isset($defaults['category_id'])) {
        $form->addHtml('<div id=\'option1\' style="display:block">');
    } else {
        $form->addHtml('<div id=\'option1\' style="display:none">');
    }

    // Loading Gradebook select
    GradebookUtils::load_gradebook_select_in_tool($form);

    $form->addElement('text', 'weight', get_lang('WeightInTheGradebook'));
    $form->addHtml('</div>');

    $form->addElement('checkbox', 'enableExpiryDate', null, get_lang('EnableExpiryDate'), 'id="expiry_date"');
    if (isset($defaults['enableExpiryDate']) && $defaults['enableExpiryDate']) {
        $form->addHtml('<div id="option2" style="display: block;">');
    } else {
        $form->addHtml('<div id="option2" style="display: none;">');
    }

    $timeNextWeek = time() + 86400 * 7;
    $nextWeek = substr(api_get_local_time($timeNextWeek), 0, 10);
    if (!isset($defaults['expires_on'])) {
        $date = substr($nextWeek, 0, 10);
        $defaults['expires_on'] = $date.' 23:59';
    }

    $form->addElement('date_time_picker', 'expires_on', get_lang('ExpiresAt'));
    $form->addHtml('</div>');
    $form->addElement('checkbox', 'enableEndDate', null, get_lang('EnableEndDate'), 'id="end_date"');

    if (!isset($defaults['ends_on'])) {
        $nextDay = substr(api_get_local_time($timeNextWeek + 86400), 0, 10);
        $date = substr($nextDay, 0, 10);
        $defaults['ends_on'] = $date.' 23:59';
    }
    if (isset($defaults['enableEndDate']) && $defaults['enableEndDate']) {
        $form->addHtml('<div id="option3" style="display: block;">');
    } else {
        $form->addHtml('<div id="option3" style="display: none;">');
    }

    $form->addElement('date_time_picker', 'ends_on', get_lang('EndsAt'));
    $form->addHtml('</div>');

    $form->addElement('checkbox', 'add_to_calendar', null, get_lang('AddToCalendar'));
    $form->addElement('select', 'allow_text_assignment', get_lang('DocumentType'), getUploadDocumentType());

    // Extra fields
    $extraField = new ExtraField('work');
    $extra = $extraField->addElements($form, $workId);

    $htmlHeadXtra[] = '
        <script>
        $(function() {
            '.$extra['jquery_ready_content'].'
        });
        </script>';

    $form->addHtml('</div>');

    Skill::addSkillsToForm($form, api_get_course_int_id(), api_get_session_id(), ITEM_TYPE_STUDENT_PUBLICATION, $workId);

    if (!empty($defaults)) {
        $form->setDefaults($defaults);
    }

    return $form;
}

/**
 * @return array
 */
function getUploadDocumentType()
{
    return [
        0 => get_lang('AllowFileOrText'),
        1 => get_lang('AllowOnlyText'),
        2 => get_lang('AllowOnlyFiles'),
    ];
}

/**
 * @param int   $itemId
 * @param array $course_info
 *
 * @return bool
 */
function makeVisible($itemId, $course_info)
{
    $itemId = (int) $itemId;
    if (empty($course_info) || empty($itemId)) {
        return false;
    }
    $work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $course_id = $course_info['real_id'];

    $sql = "UPDATE $work_table SET accepted = 1
            WHERE c_id = $course_id AND id = $itemId";
    Database::query($sql);
    api_item_property_update($course_info, 'work', $itemId, 'visible', api_get_user_id());

    return true;
}

/**
 * @param int   $itemId
 * @param array $course_info
 *
 * @return int
 */
function makeInvisible($itemId, $course_info)
{
    $itemId = (int) $itemId;
    if (empty($course_info) || empty($itemId)) {
        return false;
    }

    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $course_id = $course_info['real_id'];
    $sql = "UPDATE $table
            SET accepted = 0
            WHERE c_id = $course_id AND id = '".$itemId."'";
    Database::query($sql);
    api_item_property_update(
        $course_info,
        'work',
        $itemId,
        'invisible',
        api_get_user_id()
    );

    return true;
}

/**
 * @param int    $item_id
 * @param string $path
 * @param array  $courseInfo
 * @param int    $groupId    iid
 * @param int    $sessionId
 *
 * @return string
 */
function generateMoveForm($item_id, $path, $courseInfo, $groupId, $sessionId)
{
    $work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $courseId = $courseInfo['real_id'];
    $folders = [];
    $session_id = (int) $sessionId;
    $groupId = (int) $groupId;
    $sessionCondition = empty($sessionId) ? ' AND (session_id = 0 OR session_id IS NULL) ' : " AND session_id='".$session_id."'";

    $groupIid = 0;
    if ($groupId) {
        $groupInfo = GroupManager::get_group_properties($groupId);
        $groupIid = $groupInfo['iid'];
    }

    $sql = "SELECT id, url, title
            FROM $work_table
            WHERE
                c_id = $courseId AND
                active IN (0, 1) AND
                url LIKE '/%' AND
                post_group_id = $groupIid
                $sessionCondition";
    $res = Database::query($sql);
    while ($folder = Database::fetch_array($res)) {
        $title = empty($folder['title']) ? basename($folder['url']) : $folder['title'];
        $folders[$folder['id']] = $title;
    }

    return build_work_move_to_selector($folders, $path, $item_id);
}

/**
 * @param int $workId
 *
 * @return string
 */
function showStudentList($workId)
{
    $columnModel = [
        [
            'name' => 'student',
            'index' => 'student',
            'width' => '350px',
            'align' => 'left',
            'sortable' => 'false',
        ],
        [
            'name' => 'works',
            'index' => 'works',
            'align' => 'center',
            'sortable' => 'false',
        ],
    ];
    $token = null;
    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_work_student_list_overview&work_id='.$workId.'&'.api_get_cidreq();

    $columns = [
        get_lang('Students'),
        get_lang('Works'),
    ];

    $order = api_is_western_name_order() ? 'firstname' : 'lastname';
    $params = [
        'autowidth' => 'true',
        'height' => 'auto',
        'rowNum' => 5,
        'sortname' => $order,
        'sortorder' => 'asc',
    ];

    $html = '<script>
    $(function() {
        '.Display::grid_js('studentList', $url, $columns, $columnModel, $params, [], null, true).'
        $("#workList").jqGrid(
            "navGrid",
            "#studentList_pager",
            { edit: false, add: false, del: false },
            { height:280, reloadAfterSubmit:false }, // edit options
            { height:280, reloadAfterSubmit:false }, // add options
            { width:500 } // search options
        );
    });
    </script>';
    $html .= Display::grid_html('studentList');

    return $html;
}

/**
 * @param string $courseCode
 * @param int    $sessionId
 * @param int    $groupId
 * @param int    $start
 * @param int    $limit
 * @param string $sidx
 * @param string $sord
 * @param $getCount
 *
 * @return array|int
 */
function getWorkUserList($courseCode, $sessionId, $groupId, $start, $limit, $sidx, $sord, $getCount = false)
{
    if (!empty($groupId)) {
        $userList = GroupManager::get_users(
            $groupId,
            false,
            $start,
            $limit,
            $getCount,
            null,
            $sidx,
            $sord
        );
    } else {
        $limitString = null;
        if (!empty($start) && !empty($limit)) {
            $start = (int) $start;
            $limit = (int) $limit;
            $limitString = " LIMIT $start, $limit";
        }

        $orderBy = null;
        if (!empty($sidx) && !empty($sord)) {
            if (in_array($sidx, ['firstname', 'lastname'])) {
                $orderBy = "ORDER BY `$sidx` $sord";
            }
        }

        if (empty($sessionId)) {
            $userList = CourseManager::get_user_list_from_course_code(
                $courseCode,
                $sessionId,
                $limitString,
                $orderBy,
                STUDENT,
                $getCount
            );
        } else {
            $userList = CourseManager::get_user_list_from_course_code(
                $courseCode,
                $sessionId,
                $limitString,
                $orderBy,
                0,
                $getCount
            );
        }

        if ($getCount == false) {
            $userList = array_keys($userList);
        }
    }

    return $userList;
}

/**
 * @param int    $workId
 * @param string $courseCode
 * @param int    $sessionId
 * @param int    $groupId
 * @param int    $start
 * @param int    $limit
 * @param int    $sidx
 * @param string $sord
 * @param bool   $getCount
 *
 * @return array|int
 */
function getWorkUserListData(
    $workId,
    $courseCode,
    $sessionId,
    $groupId,
    $start,
    $limit,
    $sidx,
    $sord,
    $getCount = false
) {
    $my_folder_data = get_work_data_by_id($workId);
    $workParents = [];
    if (empty($my_folder_data)) {
        $workParents = getWorkList($workId, $my_folder_data, null);
    }

    $workIdList = [];
    if (!empty($workParents)) {
        foreach ($workParents as $work) {
            $workIdList[] = $work->id;
        }
    }

    $courseInfo = api_get_course_info($courseCode);

    $userList = getWorkUserList(
        $courseCode,
        $sessionId,
        $groupId,
        $start,
        $limit,
        $sidx,
        $sord,
        $getCount
    );

    if ($getCount) {
        return $userList;
    }
    $results = [];
    if (!empty($userList)) {
        foreach ($userList as $userId) {
            $user = api_get_user_info($userId);
            $link = api_get_path(WEB_CODE_PATH).'work/student_work.php?'.api_get_cidreq().'&studentId='.$user['user_id'];
            $url = Display::url(api_get_person_name($user['firstname'], $user['lastname']), $link);
            $userWorks = 0;
            if (!empty($workIdList)) {
                $userWorks = getUniqueStudentAttempts(
                    $workIdList,
                    $groupId,
                    $courseInfo['real_id'],
                    $sessionId,
                    $user['user_id']
                );
            }
            $works = $userWorks." / ".count($workParents);
            $results[] = [
                'student' => $url,
                'works' => Display::url($works, $link),
            ];
        }
    }

    return $results;
}

/**
 * @param int   $id
 * @param array $course_info
 * @param bool  $isCorrection
 *
 * @return bool
 */
function downloadFile($id, $course_info, $isCorrection)
{
    return getFile(
        $id,
        $course_info,
        true,
        $isCorrection,
        api_is_course_admin() || api_is_coach()
    );
}

/**
 * @param int   $id
 * @param array $course_info
 * @param bool  $download
 * @param bool  $isCorrection
 * @param bool  $forceAccessForCourseAdmins
 *
 * @return bool
 */
function getFile($id, $course_info, $download = true, $isCorrection = false, $forceAccessForCourseAdmins = false)
{
    $file = getFileContents($id, $course_info, 0, $isCorrection, $forceAccessForCourseAdmins);
    if (!empty($file) && is_array($file)) {
        return DocumentManager::file_send_for_download(
            $file['path'],
            $download,
            $file['title']
        );
    }

    return false;
}

/**
 * Get the file contents for an assigment.
 *
 * @param int   $id
 * @param array $courseInfo
 * @param int   $sessionId
 * @param bool  $correction
 * @param bool  $forceAccessForCourseAdmins
 *
 * @return array|bool
 */
function getFileContents($id, $courseInfo, $sessionId = 0, $correction = false, $forceAccessForCourseAdmins = false)
{
    $id = (int) $id;
    if (empty($courseInfo) || empty($id)) {
        return false;
    }
    if (empty($sessionId)) {
        $sessionId = api_get_session_id();
    }

    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    if (!empty($courseInfo['real_id'])) {
        $sql = "SELECT *
                FROM $table
                WHERE c_id = ".$courseInfo['real_id']." AND id = $id";

        $result = Database::query($sql);
        if ($result && Database::num_rows($result)) {
            $row = Database::fetch_array($result, 'ASSOC');

            if ($correction) {
                $row['url'] = $row['url_correction'];
            }

            if (empty($row['url'])) {
                return false;
            }

            $full_file_name = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/'.$row['url'];

            $item_info = api_get_item_property_info(
                api_get_course_int_id(),
                'work',
                $row['id'],
                $sessionId
            );

            if (empty($item_info)) {
                return false;
            }

            $isAllow = allowOnlySubscribedUser(
                api_get_user_id(),
                $row['parent_id'],
                $courseInfo['real_id'],
                $forceAccessForCourseAdmins
            );

            if (!$isAllow) {
                return false;
            }

            /*
            field show_score in table course :
                0 =>    New documents are visible for all users
                1 =>    New documents are only visible for the teacher(s)
            field visibility in table item_property :
                0 => eye closed, invisible for all students
                1 => eye open
            field accepted in table c_student_publication :
                0 => eye closed, invisible for all students
                1 => eye open
            ( We should have visibility == accepted, otherwise there is an
            inconsistency in the Database)
            field value in table c_course_setting :
                0 => Allow learners to delete their own publications = NO
                1 => Allow learners to delete their own publications = YES

            +------------------+-------------------------+------------------------+
            |Can download work?| doc visible for all = 0 | doc visible for all = 1|
            +------------------+-------------------------+------------------------+
            |  visibility = 0  | editor only             | editor only            |
            |                  |                         |                        |
            +------------------+-------------------------+------------------------+
            |  visibility = 1  | editor                  | editor                 |
            |                  | + owner of the work     | + any student          |
            +------------------+-------------------------+------------------------+
            (editor = teacher + admin + anybody with right api_is_allowed_to_edit)
            */

            $work_is_visible = $item_info['visibility'] == 1 && $row['accepted'] == 1;
            $doc_visible_for_all = (int) $courseInfo['show_score'] === 0;

            $is_editor = api_is_allowed_to_edit(true, true, true);
            $student_is_owner_of_work = user_is_author($row['id'], api_get_user_id());

            if ($is_editor ||
                $student_is_owner_of_work ||
                ($forceAccessForCourseAdmins && $isAllow) ||
                ($doc_visible_for_all && $work_is_visible)
            ) {
                $title = $row['title'];
                if ($correction) {
                    $title = $row['title_correction'];
                }
                if (array_key_exists('filename', $row) && !empty($row['filename'])) {
                    $title = $row['filename'];
                }

                $title = str_replace(' ', '_', $title);

                if ($correction == false) {
                    $userInfo = api_get_user_info($row['user_id']);
                    if ($userInfo) {
                        $date = api_get_local_time($row['sent_date']);
                        $date = str_replace([':', '-', ' '], '_', $date);
                        $title = $date.'_'.$userInfo['username'].'_'.$title;
                    }
                }

                if (Security::check_abs_path(
                    $full_file_name,
                    api_get_path(SYS_COURSE_PATH).api_get_course_path().'/'
                )) {
                    Event::event_download($title);

                    return [
                        'path' => $full_file_name,
                        'title' => $title,
                        'title_correction' => $row['title_correction'],
                    ];
                }
            }
        }
    }

    return false;
}

/**
 * @param int    $userId
 * @param array  $courseInfo
 * @param string $format
 *
 * @return bool
 */
function exportAllWork($userId, $courseInfo, $format = 'pdf')
{
    $userInfo = api_get_user_info($userId);
    if (empty($userInfo) || empty($courseInfo)) {
        return false;
    }

    $workPerUser = getWorkPerUser($userId);

    switch ($format) {
        case 'pdf':
            if (!empty($workPerUser)) {
                $pdf = new PDF();

                $content = null;
                foreach ($workPerUser as $work) {
                    $work = $work['work'];
                    foreach ($work->user_results as $userResult) {
                        $content .= $userResult['title'];
                        // No need to use api_get_local_time()
                        $content .= $userResult['sent_date'];
                        $content .= $userResult['qualification'];
                        $content .= $userResult['description'];
                    }
                }

                if (!empty($content)) {
                    $pdf->content_to_pdf(
                        $content,
                        null,
                        api_replace_dangerous_char($userInfo['complete_name']),
                        $courseInfo['code']
                    );
                }
            }
            break;
    }
}

/**
 * @param int    $workId
 * @param array  $courseInfo
 * @param int    $sessionId
 * @param string $format
 *
 * @return bool
 */
function exportAllStudentWorkFromPublication(
    $workId,
    $courseInfo,
    $sessionId,
    $format = 'pdf'
) {
    if (empty($courseInfo)) {
        return false;
    }

    $workData = get_work_data_by_id($workId);
    if (empty($workData)) {
        return false;
    }

    $assignment = get_work_assignment_by_id($workId);

    $courseCode = $courseInfo['code'];
    $header = get_lang('Course').': '.$courseInfo['title'];
    $teachers = CourseManager::getTeacherListFromCourseCodeToString(
        $courseCode
    );

    if (!empty($sessionId)) {
        $sessionInfo = api_get_session_info($sessionId);
        if (!empty($sessionInfo)) {
            $header .= ' - '.$sessionInfo['name'];
            $header .= '<br />'.$sessionInfo['description'];
            $teachers = SessionManager::getCoachesByCourseSessionToString(
                $sessionId,
                $courseInfo['real_id']
            );
        }
    }

    $header .= '<br />'.get_lang('Teachers').': '.$teachers.'<br />';
    $header .= '<br />'.get_lang('Date').': '.api_get_local_time().'<br />';
    $header .= '<br />'.get_lang('WorkName').': '.$workData['title'].'<br />';

    $content = null;
    $expiresOn = null;

    if (!empty($assignment) && isset($assignment['expires_on'])) {
        $content .= '<br /><strong>'.get_lang('PostedExpirationDate').'</strong>: '.api_get_local_time($assignment['expires_on']);
        $expiresOn = api_get_local_time($assignment['expires_on']);
    }

    if (!empty($workData['description'])) {
        $content .= '<br /><strong>'.get_lang('Description').'</strong>: '.$workData['description'];
    }

    $workList = get_work_user_list(null, null, null, null, $workId);

    switch ($format) {
        case 'pdf':
            if (!empty($workList)) {
                $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
                $headers = [
                    get_lang('Name'),
                    get_lang('User'),
                    get_lang('HandOutDateLimit'),
                    get_lang('SentDate'),
                    get_lang('FileName'),
                    get_lang('Score'),
                    get_lang('Feedback'),
                ];

                $column = 0;
                foreach ($headers as $header) {
                    $table->setHeaderContents(0, $column, $header);
                    $column++;
                }

                $row = 1;

                //$pdf->set_custom_header($header);
                foreach ($workList as $work) {
                    $content .= '<hr />';
                    // getWorkComments need c_id
                    $work['c_id'] = $courseInfo['real_id'];

                    //$content .= get_lang('Date').': '.api_get_local_time($work['sent_date_from_db']).'<br />';
                    $score = null;
                    if (!empty($work['qualification_only'])) {
                        $score = $work['qualification_only'];
                    }

                    $comments = getWorkComments($work);

                    $feedback = null;
                    if (!empty($comments)) {
                        $content .= '<h4>'.get_lang('Feedback').': </h4>';
                        foreach ($comments as $comment) {
                            $feedback .= get_lang('User').': '.$comment['complete_name'].
                                '<br />';
                            $feedback .= $comment['comment'].'<br />';
                        }
                    }
                    $table->setCellContents($row, 0, strip_tags($workData['title']));
                    $table->setCellContents($row, 1, strip_tags($work['fullname']));
                    $table->setCellContents($row, 2, $expiresOn);
                    $table->setCellContents($row, 3, api_get_local_time($work['sent_date_from_db']));
                    $table->setCellContents($row, 4, strip_tags($work['title']));
                    $table->setCellContents($row, 5, $score);
                    $table->setCellContents($row, 6, $feedback);

                    $row++;
                }

                $content = $table->toHtml();

                if (!empty($content)) {
                    $params = [
                        'filename' => $workData['title'].'_'.api_get_local_time(),
                        'pdf_title' => api_replace_dangerous_char($workData['title']),
                        'course_code' => $courseInfo['code'],
                    ];
                    $pdf = new PDF('A4', null, $params);
                    $pdf->html_to_pdf_with_template($content);
                }
                exit;
            }
            break;
    }
}

/**
 * Downloads all user files per user.
 *
 * @param int   $userId
 * @param array $courseInfo
 *
 * @return bool
 */
function downloadAllFilesPerUser($userId, $courseInfo)
{
    $userInfo = api_get_user_info($userId);

    if (empty($userInfo) || empty($courseInfo)) {
        return false;
    }

    $tempZipFile = api_get_path(SYS_ARCHIVE_PATH).api_get_unique_id().".zip";
    $coursePath = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/work/';
    $zip = new PclZip($tempZipFile);
    $workPerUser = getWorkPerUser($userId);

    if (!empty($workPerUser)) {
        $files = [];
        foreach ($workPerUser as $work) {
            $work = $work['work'];
            foreach ($work->user_results as $userResult) {
                if (empty($userResult['url']) || empty($userResult['contains_file'])) {
                    continue;
                }
                $data = getFileContents($userResult['id'], $courseInfo);
                if (!empty($data) && isset($data['path'])) {
                    $files[basename($data['path'])] = [
                        'title' => $data['title'],
                        'path' => $data['path'],
                    ];
                }
            }
        }

        if (!empty($files)) {
            Session::write('files', $files);
            foreach ($files as $data) {
                $zip->add(
                    $data['path'],
                    PCLZIP_OPT_REMOVE_PATH,
                    $coursePath,
                    PCLZIP_CB_PRE_ADD,
                    'preAddAllWorkStudentCallback'
                );
            }
        }

        // Start download of created file
        $name = basename(api_replace_dangerous_char($userInfo['complete_name'])).'.zip';
        Event::event_download($name.'.zip (folder)');
        if (Security::check_abs_path($tempZipFile, api_get_path(SYS_ARCHIVE_PATH))) {
            DocumentManager::file_send_for_download($tempZipFile, true, $name);
            @unlink($tempZipFile);
            exit;
        }
    }
    exit;
}

/**
 * @param $p_event
 * @param array $p_header
 *
 * @return int
 */
function preAddAllWorkStudentCallback($p_event, &$p_header)
{
    $files = Session::read('files');
    if (isset($files[basename($p_header['stored_filename'])])) {
        $p_header['stored_filename'] = $files[basename($p_header['stored_filename'])]['title'];

        return 1;
    }

    return 0;
}

/**
 * Get all work created by a user.
 *
 * @param int $user_id
 * @param int $courseId
 * @param int $sessionId
 *
 * @return array
 */
function getWorkCreatedByUser($user_id, $courseId, $sessionId)
{
    $items = api_get_item_property_list_by_tool_by_user(
        $user_id,
        'work',
        $courseId,
        $sessionId
    );

    $list = [];
    if (!empty($items)) {
        foreach ($items as $work) {
            $item = get_work_data_by_id(
                $work['ref'],
                $courseId,
                $sessionId
            );
            if (!empty($item)) {
                $list[] = [
                    $item['title'],
                    api_get_local_time($work['insert_date']),
                    api_get_local_time($work['lastedit_date']),
                ];
            }
        }
    }

    return $list;
}

/**
 * @param array $courseInfo
 * @param int   $workId
 *
 * @return bool
 */
function protectWork($courseInfo, $workId)
{
    $userId = api_get_user_id();
    $groupId = api_get_group_id();
    $sessionId = api_get_session_id();
    $workData = get_work_data_by_id($workId);

    if (empty($workData) || empty($courseInfo)) {
        api_not_allowed(true);
    }

    if (api_is_platform_admin() || api_is_allowed_to_edit()) {
        return true;
    }

    $workId = $workData['id'];

    if ($workData['active'] != 1) {
        api_not_allowed(true);
    }

    $visibility = api_get_item_visibility($courseInfo, 'work', $workId, $sessionId);

    if ($visibility != 1) {
        api_not_allowed(true);
    }

    $isAllow = allowOnlySubscribedUser($userId, $workId, $courseInfo['real_id']);
    if (empty($isAllow)) {
        api_not_allowed(true);
    }

    $groupInfo = GroupManager::get_group_properties($groupId);

    if (!empty($groupId)) {
        $showWork = GroupManager::user_has_access(
            $userId,
            $groupInfo['iid'],
            GroupManager::GROUP_TOOL_WORK
        );
        if (!$showWork) {
            api_not_allowed(true);
        }
    }
}

/**
 * @param array $courseInfo
 * @param array $work
 */
function deleteCorrection($courseInfo, $work)
{
    if (isset($work['url_correction']) && !empty($work['url_correction']) && isset($work['iid'])) {
        $id = $work['iid'];
        $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
        $sql = "UPDATE $table SET
                    url_correction = '',
                    title_correction = ''
                WHERE iid = $id";
        Database::query($sql);
        $coursePath = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/';
        if (file_exists($coursePath.$work['url_correction'])) {
            if (Security::check_abs_path($coursePath.$work['url_correction'], $coursePath)) {
                unlink($coursePath.$work['url_correction']);
            }
        }
    }
}

/**
 * @param int $workId
 *
 * @return string
 */
function workGetExtraFieldData($workId)
{
    $sessionField = new ExtraField('work');
    $extraFieldData = $sessionField->getDataAndFormattedValues($workId);
    $result = '';
    if (!empty($extraFieldData)) {
        $result .= '<div class="well">';
        foreach ($extraFieldData as $data) {
            $result .= $data['text'].': <b>'.$data['value'].'</b>';
        }
        $result .= '</div>';
    }

    return $result;
}

/**
 * Export the pending works to excel.
 *
 * @params $values
 */
function exportPendingWorksToExcel($values)
{
    $headers = [
        get_lang('Course'),
        get_lang('WorkName'),
        get_lang('FullUserName'),
        get_lang('Title'),
        get_lang('Score'),
        get_lang('Date'),
        get_lang('Status'),
        get_lang('Corrector'),
        get_lang('CorrectionDate'),
    ];
    $tableXls[] = $headers;

    $courseId = $values['course'] ?? 0;
    $status = $values['status'] ?? 0;
    $whereCondition = '';
    if (!empty($values['work_parent_ids'])) {
        $whereCondition = ' parent_id IN('.implode(',', $values['work_parent_ids']).')';
    }
    $allWork = getAllWork(
        null,
        null,
        null,
        null,
        $whereCondition,
        false,
        $courseId,
        $status
    );
    if (!empty($allWork)) {
        foreach ($allWork  as $work) {
            $score = $work['qualification_score'].'/'.$work['weight'];
            $data = [
                $work['course'],
                $work['work_name'],
                strip_tags($work['fullname']),
                strip_tags($work['title']),
                $score,
                strip_tags($work['sent_date']),
                strip_tags($work['qualificator_id']),
                $work['qualificator_fullname'],
                $work['date_of_qualification'],
            ];
            $tableXls[] = $data;
        }
    }

    $fileName = get_lang('StudentPublicationToCorrect').'_'.api_get_local_time();
    Export::arrayToXls($tableXls, $fileName);

    return true;
}

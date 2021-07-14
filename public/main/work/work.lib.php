<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationAssignment;
use Chamilo\CourseBundle\Entity\CStudentPublicationComment;
use ChamiloSession as Session;

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
            Display::return_icon('back.png', get_lang('Back to Assignments list'), '', ICON_SIZE_MEDIUM).
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
                get_lang('Create assignment'),
                '',
                ICON_SIZE_MEDIUM
            );
            $output .= '</a>';
        }
    }

    if (api_is_allowed_to_edit(null, true) && 'learnpath' !== $origin && 'list' === $action) {
        $output .= '<a id="open-view-list" href="#">'.
            Display::return_icon(
                'listwork.png',
                get_lang('View students'),
                '',
                ICON_SIZE_MEDIUM
            ).
            '</a>';
    }

    if ('' !== $output) {
        echo Display::toolbarAction('toolbar', [$output]);
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
            WHERE url = '$path' ";
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
    $webCodePath = api_get_path(WEB_CODE_PATH);

    $repo = Container::getStudentPublicationRepository();
    /** @var CStudentPublication $studentPublication */
    $studentPublication = $repo->find($id);

    if (null === $studentPublication) {
        return [];
    }

    $work = [];
    if ($studentPublication) {
        $workId = $studentPublication->getIid();
        $work['iid'] = $workId;
        $work['description'] = $studentPublication->getDescription();
        $work['url'] = $repo->getResourceFileDownloadUrl($studentPublication).'?'.api_get_cidreq();
        $work['active'] = $studentPublication->getActive();
        $work['allow_text_assignment'] = $studentPublication->getAllowTextAssignment();
        //$work['c_id'] = $studentPublication->getCId();
        $work['user_id'] = $studentPublication->getUser()->getId();
        $work['parent_id'] = 0;
        if (null !== $studentPublication->getPublicationParent()) {
            $work['parent_id'] = $studentPublication->getPublicationParent()->getIid();
        }

        $work['qualification'] = $studentPublication->getQualification();
        $work['contains_file'] = $studentPublication->getContainsFile();
        $work['title'] = $studentPublication->getTitle();
        $url = $repo->getResourceFileDownloadUrl($studentPublication);
        $work['download_url'] = $url.'?'.api_get_cidreq();
        $work['view_url'] = $webCodePath.'work/view.php?id='.$workId.'&'.api_get_cidreq();
        $showUrl = $work['show_url'] = $webCodePath.'work/show_file.php?id='.$workId.'&'.api_get_cidreq();
        $work['show_content'] = '';
        if ($studentPublication->getContainsFile()) {
            $fileType = '';
            if (in_array($fileType, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'])) {
                $work['show_content'] = Display::img($showUrl, $studentPublication->getTitle(), null, false);
            } elseif (false !== strpos($fileType, 'video/')) {
                $work['show_content'] = Display::tag(
                    'video',
                    get_lang('File format not supported'),
                    ['src' => $showUrl]
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
            WHERE publication_id = $id";
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
 * @return CStudentPublication[]
 */
function getWorkList($id, $my_folder_data, $add_in_where_query = null, $course_id = 0, $session_id = 0)
{
    $work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

    $course_id = $course_id ?: api_get_course_int_id();
    $session_id = $session_id ?: api_get_session_id();
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
        if ($workInGradeBookLinkId && $is_allowed_to_edit) {
            if (0 == (int) ($my_folder_data['qualification'])) {
                echo Display::return_message(
                    get_lang('Max weight need to be provided'),
                    'warning'
                );
            }
        }
    }

    $repo = Container::getStudentPublicationRepository();
    $course = api_get_course_entity($course_id);
    $session = api_get_session_entity($session_id);
    $group = api_get_group_entity($group_id);

    $qb = $repo->getResourcesByCourse($course, $session, $group);

    $contains_file_query = '';
    // Get list from database
    if ($is_allowed_to_edit) {
        $qb->andWhere($qb->expr()->in('resource.active', [1, 0]));
        $qb->andWhere($qb->expr()->isNull('resource.publicationParent'));
    /*$active_condition = ' active IN (0, 1)';
    $sql = "SELECT * FROM $work_table
            WHERE
                c_id = $course_id
                $add_in_where_query
                $condition_session AND
                $active_condition AND
                (parent_id = 0)
                $contains_file_query AND
                post_group_id = $groupIid
            ORDER BY sent_date DESC";*/
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

    return $qb->getQuery()->getResult();

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

    $sql = "SELECT count(DISTINCT u.id)
            FROM $work_table w
            INNER JOIN $user_table u
            ON w.user_id = u.id
            WHERE
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
        $studentCondition = "AND u.id IN ('".implode("', '", $onlyUserList)."') ";
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
                ON w.user_id = u.id
                WHERE
                    w.filetype = 'file' AND
                    $workCondition
                    w.post_group_id = $groupIid AND
                    w.active IN (0, 1) $studentCondition
                ";
    if (!empty($userId)) {
        $userId = (int) $userId;
        $sql .= ' AND u.id = '.$userId;
    }
    $sql .= ' GROUP BY u.id, w.parent_id) as t';
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
        get_lang('Deadline'),
        get_lang('Feedback'),
        get_lang('Last upload'),
    ];

    $columnModel = [
        ['name' => 'type', 'index' => 'type', 'width' => '30', 'align' => 'center', 'sortable' => 'false'],
        ['name' => 'title', 'index' => 'title', 'width' => '250', 'align' => 'left'],
        ['name' => 'expires_on', 'index' => 'expires_on', 'width' => '80', 'align' => 'center', 'sortable' => 'false'],
        ['name' => 'feedback', 'index' => 'feedback', 'width' => '80', 'align' => 'center', 'sortable' => 'false'],
        ['name' => 'last_upload', 'index' => 'feedback', 'width' => '125', 'align' => 'center', 'sortable' => 'false'],
    ];

    if (0 == $courseInfo['show_score']) {
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
        [
            'name' => 'type',
            'index' => 'type',
            'width' => '35',
            'align' => 'center',
            'sortable' => 'false',
        ],
        [
            'name' => 'title',
            'index' => 'title',
            'width' => '300',
            'align' => 'left',
            'wrap_cell' => 'true',
        ],
        ['name' => 'sent_date', 'index' => 'sent_date', 'width' => '125', 'align' => 'center'],
        ['name' => 'expires_on', 'index' => 'expires_on', 'width' => '125', 'align' => 'center'],
        [
            'name' => 'amount',
            'index' => 'amount',
            'width' => '110',
            'align' => 'center',
            'sortable' => 'false',
        ],
        [
            'name' => 'actions',
            'index' => 'actions',
            'width' => '110',
            'align' => 'left',
            'sortable' => 'false',
        ],
    ];
    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_work_teacher&'.api_get_cidreq();
    $deleteUrl = api_get_path(WEB_AJAX_PATH).'work.ajax.php?a=delete_work&'.api_get_cidreq();

    $columns = [
        get_lang('Type'),
        get_lang('Title'),
        get_lang('Sent date'),
        get_lang('Deadline'),
        get_lang('Number submitted'),
        get_lang('Detail'),
    ];

    $params = [
        'multiselect' => true,
        'autowidth' => 'true',
        'height' => 'auto',
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
            WHERE iid ='".$move_file."'";
    $result = Database::query($sql);
    $row = Database::fetch_array($result, 'ASSOC');
    $title = empty($row['title']) ? basename($row['url']) : $row['title'];

    $form = new FormValidator(
        'move_to_form',
        'post',
        api_get_self().'?'.api_get_cidreq().'&curdirpath='.Security::remove_XSS($curdirpath)
    );

    $form->addHeader(get_lang('Move the file').' - '.Security::remove_XSS($title));
    $form->addHidden('item_id', $move_file);
    $form->addHidden('action', 'move_to');

    // Group documents cannot be uploaded in the root
    $options = [];
    if ('' == $group_dir) {
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
        if ('/' != $curdirpath) {
            $form .= '<option value="0">/ ('.get_lang('root').')</option>';
        }
        foreach ($folders as $fid => $folder) {
            if (($curdirpath != $folder) && ($folder != $move_file) &&
                (substr($folder, 0, strlen($move_file) + 1) != $move_file.'/')
            ) {
                //cannot copy dir into his own subdir
                $display_folder = substr($folder, strlen($group_dir));
                $display_folder = '' == $display_folder ? '/ ('.get_lang('root').')' : $display_folder;
                //$form .= '<option value="'.$fid.'">'.$display_folder.'</option>'."\n";
                $options[$fid] = $display_folder;
            }
        }
    }

    $form->addSelect('move_to_id', get_lang('Select'), $options);
    $form->addButtonSend(get_lang('Move the file'), 'move_file_submit');

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
    $workDir = ('/' == substr($workDir, -1, 1) ? $workDir : $workDir.'/');
    $checkDirName = $desiredDirName;
    while (file_exists($workDir.$checkDirName)) {
        $counter++;
        $checkDirName = $desiredDirName.$counter;
    }

    /*if (@mkdir($workDir.$checkDirName, api_get_permissions_for_new_directories())) {
        return $checkDirName;
    } else {
        return false;
    }*/
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
    if ($locked) {
        echo Display::return_message(
            get_lang(
                'This option is not available because this activity is contained by an assessment, which is currently locked. To unlock the assessment, ask your platform administrator.'
            ),
            'warning'
        );

        return false;
    }

    $_course = api_get_course_info();
    $id = (int) $id;
    $work_data = get_work_data_by_id($id);

    if (empty($work_data)) {
        return false;
    }

    //$base_work_dir = api_get_path(SYS_COURSE_PATH).$_course['path'].'/work';
    //$work_data_url = $base_work_dir.$work_data['url'];
    //$check = Security::check_abs_path($work_data_url.'/', $base_work_dir.'/');
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $TSTDPUBASG = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
    $t_agenda = Database::get_course_table(TABLE_AGENDA);
    $course_id = api_get_course_int_id();
    $sessionId = api_get_session_id();
    $check = true;

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
                        $work_data['iid'],
                        null,
                        $user['user_id'],
                        false,
                        $course_id,
                        $sessionId
                    );

                    if (1 != count($userWorks)) {
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
                WHERE filetype = 'folder' AND iid = $id";
        Database::query($sql);

        $sql = "UPDATE $table SET active = 2
                WHERE parent_id = $id";
        Database::query($sql);

        /*$new_dir = $work_data_url.'_DELETED_'.$id;

        if ('true' == api_get_setting('permanently_remove_deleted_files')) {
            my_delete($work_data_url);
        } else {
            if (file_exists($work_data_url)) {
                rename($work_data_url, $new_dir);
            }
        }*/

        // Gets calendar_id from student_publication_assigment
        $sql = "SELECT add_to_calendar FROM $TSTDPUBASG
                WHERE publication_id = $id";
        $res = Database::query($sql);
        $calendar_id = Database::fetch_row($res);

        // delete from agenda if it exists
        if (!empty($calendar_id[0])) {
            $sql = "DELETE FROM $t_agenda
                    WHERE id = '".$calendar_id[0]."'";
            Database::query($sql);
        }
        $sql = "DELETE FROM $TSTDPUBASG
                WHERE publication_id = $id";
        Database::query($sql);

        SkillModel::deleteSkillsFromItem($id, ITEM_TYPE_STUDENT_PUBLICATION);

        Event::addEvent(
            LOG_WORK_DIR_DELETE,
            LOG_WORK_DATA,
            [
                'id' => $work_data['iid'],
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
            $sessionId
        );

        if (false !== $linkInfo) {
            $link_id = $linkInfo['id'];
            GradebookUtils::remove_resource_from_course_gradebook($link_id);
        }

        return true;
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
            WHERE id='.(int) $id;
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
            WHERE iid = $id";
    $res = Database::query($sql);
    if (1 != Database::num_rows($res)) {
        return -1;
    } else {
        $row = Database::fetch_array($res);
        $filename = basename($row['url']);
        $new_url = $new_path.$filename;
        $new_url = Database::escape_string($new_url);

        $sql = "UPDATE $table SET
                   url = '$new_url',
                   parent_id = '$parent_id'
                WHERE iid = $id";

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
 * @param int $work_id
 * @param int $onlyMeUserId show only my works
 * @param int $notMeUserId  show works from everyone except me
 *
 * @return int
 */
function get_count_work($work_id, $onlyMeUserId = null, $notMeUserId = null)
{
    $work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $user_table = Database::get_main_table(TABLE_MAIN_USER);

    $is_allowed_to_edit = api_is_allowed_to_edit(null, true) || api_is_coach();
    $session_id = api_get_session_id();
    $condition_session = api_get_session_condition(
        $session_id,
        true,
        false,
        'link.session_id'
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
        if (isset($course_info['show_score']) && 1 == $course_info['show_score']) {
            $extra_conditions .= ' AND work.user_id = '.api_get_user_id().' ';
        } else {
            $extra_conditions .= '';
        }
    }

    $extra_conditions .= ' AND work.parent_id  = '.$work_id.'  ';
    $where_condition = null;
    if (!empty($notMeUserId)) {
        $where_condition .= ' AND u.id <> '.(int) $notMeUserId;
    }

    if (!empty($onlyMeUserId)) {
        $where_condition .= ' AND u.id =  '.(int) $onlyMeUserId;
    }

    $repo = Container::getStudentPublicationRepository();
    $typeId = $repo->getResourceType()->getId();
    $visibility = ResourceLink::VISIBILITY_PUBLISHED;

    $sql = "SELECT count(*) as count
            FROM resource_node node
            INNER JOIN resource_link link
            ON (link.resource_node_id = node.id)
            INNER JOIN $work_table work
            ON (node.id = work.resource_node_id)
            INNER JOIN $user_table u
            ON (work.user_id = u.id)
            WHERE
                link.c_id = $course_id AND
                resource_type_id = $typeId AND
                link.visibility = $visibility AND
            $extra_conditions $where_condition $condition_session";

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

    $isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh($userId, $courseInfo);

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
        $subdirs_query = 'AND w.parent_id = 0';
    } else {
        $group_query = " WHERE w.c_id = $course_id AND (post_group_id = '0' or post_group_id is NULL)  ";
        $subdirs_query = 'AND w.parent_id = 0';
    }

    $active_condition = ' AND active IN (1, 0)';

    if ($getCount) {
        $select = 'SELECT count(w.iid) as count ';
    } else {
        $select = 'SELECT w.*, a.expires_on, expires_on, ends_on, enable_qualification ';
    }

    $repo = Container::getStudentPublicationRepository();
    $course = api_get_course_entity($course_id);
    $session = api_get_session_entity($session_id);
    $group = api_get_group_entity($group_id);

    $qb = $repo->getResourcesByCourse($course, $session, $group);
    $qb->andWhere($qb->expr()->in('resource.active', [1, 0]));
    $qb->andWhere($qb->expr()->isNull('resource.publicationParent'));

    if ($getCount) {
        $qb->select('count(resource)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    $qb
        ->setFirstResult($start)
        ->setMaxResults($limit)
    ;

    /*$sql = "$select
            FROM $workTable w
            LEFT JOIN $workTableAssignment a
            ON (a.publication_id = w.iid AND a.c_id = w.c_id)
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
    }*/

    $works = [];
    $url = api_get_path(WEB_CODE_PATH).'work/work_list.php?'.api_get_cidreq();
    if ($isDrhOfCourse) {
        $url = api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq();
    }

    $studentPublications = $qb->getQuery()->getResult();
    $urlOthers = api_get_path(WEB_CODE_PATH).'work/work_list_others.php?'.api_get_cidreq().'&id=';
    //while ($work = Database::fetch_array($result, 'ASSOC')) {
    $icon = Display::return_icon('work.png');

    /** @var CStudentPublication $studentPublication */
    foreach ($studentPublications as $studentPublication) {
        $workId = $studentPublication->getIid();
        $assignment = $studentPublication->getAssignment();
        $isSubscribed = userIsSubscribedToWork($userId, $workId, $course_id);
        if (false === $isSubscribed) {
            continue;
        }

        /*$visibility = api_get_item_visibility($courseInfo, 'work', $workId, $session_id);
        if ($visibility != 1) {
            continue;
        }*/

        $work['type'] = $icon;
        $work['expires_on'] = '';
        if ($assignment) {
            $work['expires_on'] = api_get_local_time($assignment->getExpiresOn());
        }

        $title = $studentPublication->getTitle();

        /*$whereCondition = " AND u.id = $userId ";
        $workList = get_work_user_list(
            0,
            1000,
            null,
            null,
            $workId,
            $whereCondition
        );
        $count = getTotalWorkComment($workList, $courseInfo);*/
        $count = 0;
        //$lastWork = getLastWorkStudentFromParentByUser($userId, $work, $courseInfo);
        $lastWork = null;
        if (null !== $count && !empty($count)) {
            $urlView = api_get_path(WEB_CODE_PATH).'work/view.php?id='.$lastWork['iid'].'&'.api_get_cidreq();
            $feedback = '&nbsp;'.Display::url(
                Display::returnFontAwesomeIcon('comments-o'),
                $urlView,
                ['title' => get_lang('View')]
            );

            $work['feedback'] = ' '.Display::label($count.' '.get_lang('Feedback'), 'info').$feedback;
        }

        if (!empty($lastWork)) {
            $work['last_upload'] = !empty($lastWork['qualification']) ? $lastWork['qualification_rounded'].' - ' : '';
            $work['last_upload'] .= api_get_local_time($lastWork['sent_date']);
        }

        $work['title'] = Display::url($title, $url.'&id='.$workId);
        $work['others'] = Display::url(
            Display::return_icon('group.png', get_lang('Others')),
            $urlOthers.$workId
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
        $select = 'SELECT count(DISTINCT(w.iid)) as count ';
    } else {
        if (empty($courseQuery)) {
            return [];
        }
        $select = 'SELECT DISTINCT
                        w.url,
                        w.iid,
                        w.c_id,
                        w.session_id,
                        a.expires_on,
                        a.ends_on,
                        a.enable_qualification,
                        w.qualification,
                        a.publication_id';
    }

    $checkSentWork = " LEFT JOIN $workTable ww
                       ON (ww.c_id = w.c_id AND ww.parent_id = w.iid AND ww.user_id = $userId ) ";
    $where = ' AND ww.url IS NULL ';
    $expirationCondition = " AND (a.expires_on IS NULL OR a.expires_on > '".api_get_utc_datetime()."') ";
    if ($withResults) {
        $where = '';
        $checkSentWork = " LEFT JOIN $workTable ww
                           ON (
                            ww.c_id = w.c_id AND
                            ww.parent_id = w.iid AND
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
            ON (a.publication_id = w.iid AND a.c_id = w.c_id)
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
        $isSubscribed = userIsSubscribedToWork($userId, $work['iid'], $courseId);
        if (false == $isSubscribed) {
            continue;
        }

        /*$visibility = api_get_item_visibility($courseInfo, 'work', $work['iid'], $sessionId);

        if (1 != $visibility) {
            continue;
        }*/

        $work['type'] = Display::return_icon('work.png');
        $work['expires_on'] = empty($work['expires_on']) ? null : api_get_local_time($work['expires_on']);

        if (empty($work['title'])) {
            $work['title'] = basename($work['url']);
        }

        if ($withResults) {
            $whereCondition = " AND u.id = $userId ";
            $workList = get_work_user_list(
                0,
                1000,
                null,
                null,
                $work['iid'],
                $whereCondition,
                null,
                false,
                $courseId,
                $sessionId
            );

            $count = getTotalWorkComment($workList, $courseInfo);
            $lastWork = getLastWorkStudentFromParentByUser($userId, $work, $courseInfo);

            if (!is_null($count) && !empty($count)) {
                $urlView = api_get_path(WEB_CODE_PATH).'work/view.php?id='.$lastWork['iid'].'&'.$cidReq;

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

        $work['title'] = Display::url($work['title'], $url.'&id='.$work['iid']);
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
 * @return array
 */
function getWorkListTeacher(
    $start,
    $limit,
    $column,
    $direction,
    $where_condition,
    $getCount = false
) {
    $course_id = api_get_course_int_id();
    $session_id = api_get_session_id();
    $group_id = api_get_group_id();
    $groupIid = 0;
    if ($group_id) {
        $groupInfo = GroupManager::get_group_properties($group_id);
        $groupIid = $groupInfo['iid'];
    }
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
    $works = [];

    $repo = Container::getStudentPublicationRepository();
    $course = api_get_course_entity($course_id);
    $session = api_get_session_entity($session_id);
    $group = api_get_group_entity($group_id);

    // Get list from database
    if ($is_allowed_to_edit) {
        $active_condition = ' active IN (0, 1)';
        if ($getCount) {
            $select = ' SELECT count(w.iid) as count';
        } else {
            $select = ' SELECT w.*, a.expires_on, expires_on, ends_on, enable_qualification ';
        }

        $qb = $repo->getResourcesByCourse($course, $session, $group);

        $qb->andWhere($qb->expr()->in('resource.active', [1, 0]));
        $qb->andWhere($qb->expr()->isNull('resource.publicationParent'));
        if ($getCount) {
            $qb->select('count(resource)');
        }

        $qb
            ->setFirstResult($start)
            ->setMaxResults($limit)
        ;

        /*$sql = " $select
                FROM $workTable w
                LEFT JOIN $workTableAssignment a
                ON (a.publication_id = w.iid AND a.c_id = w.c_id)
                WHERE
                    w.c_id = $course_id
                    $condition_session AND
                    $active_condition AND
                    parent_id = 0 AND
                    post_group_id = $groupIid
                    $where_condition
                ORDER BY `$column` $direction
                LIMIT $start, $limit";
        $result = Database::query($sql);*/

        if ($getCount) {
            return (int) $qb->getQuery()->getSingleScalarResult();
            /*$row = Database::fetch_array($result);
            return (int) $row['count'];*/
        }

        $studentPublications = $qb->getQuery()->getResult();

        $url = api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq();
        $blockEdition = api_get_configuration_value('block_student_publication_edition');

        //while ($work = Database::fetch_array($result, 'ASSOC')) {
        $icon = Display::return_icon('work.png');
        /** @var CStudentPublication $studentPublication */
        foreach ($studentPublications as $studentPublication) {
            $workId = $studentPublication->getIid();
            $work = [];
            $work['iid'] = $workId;
            $work['type'] = $icon;
            $assignment = $studentPublication->getAssignment();
            $work['expires_on'] = '';
            if ($assignment) {
                $work['expires_on'] = api_get_local_time($assignment->getExpiresOn());
            }

            /*$countUniqueAttempts = getUniqueStudentAttemptsTotal(
                $workId,
                $group_id,
                $course_id,
                $session_id
            );*/
            $countUniqueAttempts = 0;
            $totalUsers = getStudentSubscribedToWork(
                $workId,
                $course_id,
                $group_id,
                $session_id,
                true
            );

            $work['amount'] = Display::label($countUniqueAttempts.'/'.$totalUsers, 'success');

            //$visibility = api_get_item_visibility($courseInfo, 'work', $workId, $session_id);
            $isVisible = $studentPublication->isVisible($course, $session);
            if ($isVisible) {
                $icon = 'visible.png';
                $text = get_lang('Visible');
                $action = 'invisible';
                $class = '';
            } else {
                $icon = 'invisible.png';
                $text = get_lang('invisible');
                $action = 'visible';
                $class = 'muted';
            }

            $visibilityLink = Display::url(
                Display::return_icon($icon, $text, [], ICON_SIZE_SMALL),
                api_get_path(WEB_CODE_PATH).'work/work.php?id='.$workId.'&action='.$action.'&'.api_get_cidreq()
            );

            $title = $studentPublication->getTitle();
            $work['title'] = Display::url($title, $url.'&id='.$workId, ['class' => $class]);
            $work['title'] .= ' '.Display::label(get_count_work($workId), 'success');
            $work['sent_date'] = api_get_local_time($studentPublication->getSentDate());

            if ($blockEdition && !api_is_platform_admin()) {
                $editLink = '';
            } else {
                $editLink = Display::url(
                    Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL),
                    api_get_path(WEB_CODE_PATH).'work/edit_work.php?id='.$workId.'&'.api_get_cidreq()
                );
            }

            $correctionLink = '&nbsp;'.Display::url(
                Display::return_icon('upload_package.png', get_lang('Upload corrections'), '', ICON_SIZE_SMALL),
                api_get_path(WEB_CODE_PATH).'work/upload_corrections.php?'.api_get_cidreq().'&id='.$workId
            ).'&nbsp;';

            if ($countUniqueAttempts > 0) {
                $downloadLink = Display::url(
                    Display::return_icon(
                        'save_pack.png',
                        get_lang('Save'),
                        [],
                        ICON_SIZE_SMALL
                    ),
                    api_get_path(WEB_CODE_PATH).'work/downloadfolder.inc.php?id='.$workId.'&'.api_get_cidreq()
                );
            } else {
                $downloadLink = Display::url(
                    Display::return_icon(
                        'save_pack_na.png',
                        get_lang('Save'),
                        [],
                        ICON_SIZE_SMALL
                    ),
                    '#'
                );
            }
            if (!api_is_allowed_to_edit()) {
                $editLink = null;
            }
            $work['actions'] = $visibilityLink.$correctionLink.$downloadLink.$editLink;
            $works[] = $work;
        }
    }

    return $works;
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
        $select1 = ' SELECT count(u.id) as count ';
        $select2 = ' SELECT count(u.id) as count ';
    } else {
        $select1 = ' SELECT DISTINCT
                        u.firstname,
                        u.lastname,
                        u.id as user_id,
                        w.title,
                        w.parent_id,
                        w.document_id document_id,
                        w.iid,
                        qualification,
                        qualificator_id,
                        w.sent_date,
                        w.contains_file,
                        w.url
                    ';
        $select2 = ' SELECT DISTINCT
                        u.firstname, u.lastname,
                        u.id as user_id,
                        d.title,
                        w.parent_id,
                        d.iid document_id,
                        0,
                        0,
                        0,
                        w.sent_date,
                        w.contains_file,
                        w.url
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

    $userCondition = " AND u.id = $studentId ";
    $sessionCondition = api_get_session_condition($sessionId, true, false, 'w.session_id');
    $workCondition = " AND w_rel.work_id = $workId";
    $workParentCondition = " AND w.parent_id = $workId";

    $sql = "(
                $select1 FROM $userTable u
                INNER JOIN $workTable w
                ON (u.id = w.user_id AND w.active IN (0, 1) AND w.filetype = 'file')
                WHERE
                    w.c_id = $courseId
                    $userCondition
                    $sessionCondition
                    $whereCondition
                    $workParentCondition
            ) UNION (
                $select2 FROM $workTable w
                INNER JOIN $workRelDocument w_rel
                ON (w_rel.work_id = w.iid AND w.active IN (0, 1) AND w_rel.c_id = w.c_id)
                INNER JOIN $documentTable d
                ON (w_rel.document_id = d.iid AND d.c_id = w.c_id)
                INNER JOIN $userTable u ON (u.id = $studentId)
                WHERE
                    w.c_id = $courseId
                    $workCondition
                    $sessionCondition AND
                    d.iid NOT IN (
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

    if (!empty($work_data['qualification']) && (int) ($work_data['qualification']) > 0) {
        $qualificationExists = true;
    }

    $urlAdd = api_get_path(WEB_CODE_PATH).'work/upload_from_template.php?'.api_get_cidreq();
    $urlEdit = api_get_path(WEB_CODE_PATH).'work/edit.php?'.api_get_cidreq();
    $urlDelete = api_get_path(WEB_CODE_PATH).'work/work_list.php?action=delete&'.api_get_cidreq();
    $urlView = api_get_path(WEB_CODE_PATH).'work/view.php?'.api_get_cidreq();
    $urlDownload = api_get_path(WEB_CODE_PATH).'work/download.php?'.api_get_cidreq();

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
    $allowEdition = 1 == api_get_course_setting('student_delete_own_publication');

    $workList = [];
    while ($row = Database::fetch_array($result, 'ASSOC')) {
        $userId = $row['user_id'];
        $documentId = $row['document_id'];
        $itemId = $row['iid'];
        $addLinkShowed = false;

        if (empty($documentId)) {
            $url = $urlEdit.'&item_id='.$row['iid'].'&id='.$workId;
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
                $newWorkId = $documentToWork['iid'];
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
            $downloadLink = Display::url($saveIcon, $urlDownload.'&id='.$row['iid']).'&nbsp;';
        }

        $viewLink = '';
        if (!empty($itemId)) {
            $viewLink = Display::url($viewIcon, $urlView.'&id='.$itemId);
        }

        $deleteLink = '';
        if (1 == $allowEdition && !empty($itemId)) {
            $deleteLink = Display::url($deleteIcon, $urlDelete.'&item_id='.$itemId.'&id='.$workId);
        }

        $row['type'] = null;

        if ($qualificationExists) {
            if (empty($row['qualificator_id'])) {
                $status = Display::label(get_lang('Not reviewed'), 'warning');
            } else {
                $status = Display::label(get_lang('Revised'), 'success');
            }
            $row['qualificator_id'] = $status;
        }

        if (!empty($row['qualification'])) {
            $row['qualification'] = Display::label($row['qualification'], 'info');
        }

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
    $workId,
    $whereCondition = '',
    $studentId = null,
    $getCount = false,
    $courseId = 0,
    $sessionId = 0
) {
    $session_id = $sessionId ? (int) $sessionId : api_get_session_id();
    $groupId = api_get_group_id();
    $course_info = api_get_course_info();
    $course_info = empty($course_info) ? api_get_course_info_by_id($courseId) : $course_info;
    $courseId = isset($course_info['real_id']) ? $course_info['real_id'] : $courseId;

    $course = api_get_course_entity($courseId);
    $session = api_get_session_entity($sessionId);
    $group = api_get_group_entity($groupId);

    $workId = (int) $workId;
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

    //$work_data = get_work_data_by_id($workId, $courseId, $sessionId);
    $is_allowed_to_edit = api_is_allowed_to_edit() || api_is_coach();
    $condition_session = api_get_session_condition(
        $session_id,
        true,
        false,
        'work.session_id'
    );

    $locked = api_resource_is_locked_by_gradebook(
        $workId,
        LINK_STUDENTPUBLICATION,
        $course_info['code']
    );

    $isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(
        api_get_user_id(),
        $course_info
    );

    $isDrhOfSession = !empty(SessionManager::getSessionFollowedByDrh(api_get_user_id(), $session_id));

    $repo = Container::getStudentPublicationRepository();
    /** @var CStudentPublication $studentPublication */
    $studentPublication = $repo->find($workId);

    if (null !== $studentPublication) {
        /*if (!empty($group_id)) {
            // set to select only messages posted by the user's group
            $extra_conditions = " work.post_group_id = '".$groupIid."' ";
        } else {
            $extra_conditions = " (work.post_group_id = '0' OR work.post_group_id is NULL) ";
        }*/

        /*if ($is_allowed_to_edit || $isDrhOfCourse || $isDrhOfSession) {
            $extra_conditions .= ' AND work.active IN (0, 1) ';
        } else {
            if (isset($course_info['show_score']) &&
                1 == $course_info['show_score']
            ) {
                $extra_conditions .= ' AND (u.id = '.api_get_user_id().' AND work.active IN (0, 1)) ';
            } else {
                $extra_conditions .= ' AND work.active IN (0, 1) ';
            }
        }

        $extra_conditions .= " AND parent_id  = $workId ";*/

        $select = 'SELECT DISTINCT
                        u.id as user_id,
                        work.iid as id,
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
                        qualificator_id
                        ';
        if ($getCount) {
            $select = 'SELECT DISTINCT count(u.id) as count ';
        }

        /*$work_assignment = get_work_assignment_by_id($workId, $courseId);
        if (!empty($studentId)) {
            $studentId = (int) $studentId;
            $whereCondition .= " AND u.id = $studentId ";
        }*/

        $qb = $repo->getStudentAssignments($studentPublication, $course, $session, $group);

        if ($getCount) {
            $qb->select('count(resource)');

            return $qb->getQuery()->getSingleScalarResult();
        }

        $qb->setFirstResult($start);
        $qb->setMaxResults($limit);

        $assignments = $qb->getQuery()->getResult();

        /*$sql = " $select
                FROM $work_table work
                INNER JOIN $user_table u
                ON (work.user_id = u.id)
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
        $result = Database::query($sql);*/
        $works = [];
        /*if ($getCount) {
            $work = Database::fetch_array($result, 'ASSOC');
            if ($work) {
                return (int) $work['count'];
            }

            return 0;
        }*/

        $url = api_get_path(WEB_CODE_PATH).'work/';
        $unoconv = api_get_configuration_value('unoconv.binaries');
        $loadingText = addslashes(get_lang('Loading'));
        $uploadedText = addslashes(get_lang('Uploaded.'));
        $failsUploadText = addslashes(get_lang('No file was uploaded..'));
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
            get_lang('Correct and rate'),
            [],
            ICON_SIZE_SMALL
        );

        $blockEdition = api_get_configuration_value('block_student_publication_edition');
        $blockScoreEdition = api_get_configuration_value('block_student_publication_score_edition');
        $loading = Display::returnFontAwesomeIcon('spinner', null, true, 'fa-spin');
        $router = Container::getRouter();
        $studentDeleteOwnPublication = api_get_course_setting('student_delete_own_publication');
        /** @var CStudentPublication $assignment */
        foreach ($assignments as $assignment) {
            $item_id = $assignment->getIid();
            // Get the author ID for that document from the item_property table
            $is_author = false;
            $can_read = false;
            $owner_id = $assignment->getUser()->getId();
            /* Because a bug found when saving items using the api_item_property_update()
               the field $item_property_data['insert_user_id'] is not reliable. */
            if (!$is_allowed_to_edit && $owner_id == api_get_user_id()) {
                $is_author = true;
            }

            if (0 == $course_info['show_score']) {
                $can_read = true;
            }
            $qualification = $assignment->getQualification();
            $qualification_exists = false;
            if (!empty($qualification) &&
                (int) ($qualification) > 0
            ) {
                $qualification_exists = true;
            }

            $qualification_string = '';
            if ($qualification_exists) {
                if ('' == $qualification) {
                    $qualification_string = Display::label('-');
                } else {
                    $qualification_string = formatWorkScore($qualification, $qualification);
                }
            }
            $work['iid'] = $assignment->getIid();
            $work['qualification_score'] = $studentPublication->getQualification();
            $add_string = '';
            $time_expires = '';
            $assignmentConfiguration = $studentPublication->getAssignment();
            $expiresOn = null;
            if (!empty($assignmentConfiguration)) {
                $expiresOn = $assignmentConfiguration->getExpiresOn();
                if (!empty($expiresOn)) {
                    $time_expires = api_strtotime(
                        $expiresOn->format('Y-m-d H:i:s'),
                        'UTC'
                    );
                }
            }

            if (!empty($expiresOn) &&
                !empty($time_expires) &&
                ($time_expires < api_strtotime($studentPublication->getSentDate(), 'UTC'))
            ) {
                $add_string = Display::label(get_lang('Expired'), 'important').' - ';
            }

            $accepted = $studentPublication->getAccepted();
            if (($can_read && $accepted) ||
                $is_author ||
                ($is_allowed_to_edit || api_is_drh())
            ) {
                // Firstname, lastname, username
                $work['fullname'] = Display::div(
                    UserManager::formatUserFullName($assignment->getUser()),
                    ['class' => 'work-name']
                );
                // Title
                $title = $assignment->getTitle();
                $work['title_clean'] = $title;
                $title = Security::remove_XSS($title);
                if (strlen($title) > 30) {
                    $short_title = substr($title, 0, 27).'...';
                    $work['title'] = Display::span($short_title, ['class' => 'work-title', 'title' => $title]);
                } else {
                    $work['title'] = Display::div($title, ['class' => 'work-title']);
                }

                // Type.
                //$work['type'] = DocumentManager::build_document_icon_tag('file', $studentPublication->getUrl());
                $work['type'] = '';

                // File name.
                $linkToDownload = '';
                // If URL is present then there's a file to download keep BC.
                if ($studentPublication->getContainsFile()) {
                    $downloadUrl = $repo->getResourceFileDownloadUrl($assignment).'?'.api_get_cidreq();
                    $linkToDownload = '<a href="'.$downloadUrl.'">'.$saveIcon.'</a> ';
                }

                $feedback = '';
                $count = count($studentPublication->getComments()); // getWorkCommentCount($item_id, $course_info);
                if (null !== $count && !empty($count)) {
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
                $correctionEntity = $assignment->getCorrection();
                if (null !== $correctionEntity) {
                    $downloadUrl = $repo->getResourceFileDownloadUrl($assignment).'?'.api_get_cidreq();
                    $hasCorrection = Display::url(
                        $correctionIcon,
                        $downloadUrl
                    );
                }

                if ($qualification_exists) {
                    $work['qualification'] = $qualification_string.$feedback;
                } else {
                    $work['qualification'] = $qualification_string.$feedback.$hasCorrection;
                }

                $work['qualification_only'] = $qualification_string;

                // Date.
                $sendDate = $studentPublication->getSentDate();
                $sendDateToString = $studentPublication->getSentDate()->format('Y-m-d H:i:s');

                $work_date = api_get_local_time($sendDate);
                $date = date_to_str_ago($sendDate).' '.$work_date;
                $work['formatted_date'] = $work_date.' '.$add_string;
                $work['expiry_note'] = $add_string;
                $work['sent_date_from_db'] = $sendDateToString;
                $work['sent_date'] = '<div class="work-date" title="'.$date.'">'.
                    $add_string.' '.Display::dateToStringAgoAndLongDate($sendDate).
                    '</div>';
                $work['status'] = $hasCorrection;
                $work['has_correction'] = $hasCorrection;

                // Actions.
                $action = '';
                $qualificatorId = $assignment->getQualificatorId();
                if (api_is_allowed_to_edit()) {
                    if ($blockScoreEdition && !api_is_platform_admin() && !empty($studentPublication->getQualification())) {
                        $rateLink = '';
                    } else {
                        $rateLink = '<a
                            href="'.$url.'view.php?'.api_get_cidreq().'&id='.$item_id.'" title="'.get_lang('View').'">'.
                            $rateIcon.'</a> ';
                    }
                    $action .= $rateLink;

                    if ($unoconv && empty($assignment->getContainsFile())) {
                        $action .= '<a
                            href="'.$url.'work_list_all.php?'.api_get_cidreq().'&id='.$workId.'&action=export_to_doc&item_id='.$item_id.'"
                            title="'.get_lang('Export to .doc').'" >'.
                            Display::return_icon('export_doc.png', get_lang('Export to .doc'), [], ICON_SIZE_SMALL).'</a> ';
                    }

                    $alreadyUploaded = '';
                    if ($correctionEntity) {
                        $alreadyUploaded = '<br />'.
                            $assignment->getResourceNode()->getTitle().' '.$correctionIconSmall;
                    }

                    $correction = '
                        <form
                        id="file_upload_'.$item_id.'"
                        class="work_correction_file_upload file_upload_small fileinput-button"
                        action="'.api_get_path(WEB_AJAX_PATH).'work.ajax.php?'.api_get_cidreq().'&a=upload_correction_file&item_id='.$item_id.'"
                        method="POST"
                        enctype="multipart/form-data"
                        >
                        <div id="progress_'.$item_id.'" class="text-center button-load">
                            '.addslashes(get_lang('Click or drop one file here')).'
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
                                get_lang('Correct and rate'),
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
                                $editLink = '<a
                                    href="'.$url.'edit.php?'.api_get_cidreq().'&item_id='.$item_id.'&id='.$workId.'"
                                    title="'.get_lang('Edit').'"  >'.
                                    Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL).'</a>';
                            } else {
                                $editLink = '<a
                                    href="'.$url.'edit.php?'.api_get_cidreq().'&item_id='.$item_id.'&id='.$workId.'"
                                    title="'.get_lang('Edit').'">'.
                                    Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL).'</a>';
                            }
                        }
                        $action .= $editLink;
                    }

                    if ($assignment->getContainsFile()) {
                        if ($locked) {
                            $action .= Display::return_icon(
                                'move_na.png',
                                get_lang('Move'),
                                [],
                                ICON_SIZE_SMALL
                            );
                        } else {
                            $action .= '<a
                                href="'.$url.'work.php?'.api_get_cidreq().'&action=move&item_id='.$item_id.'&id='.$workId.'"
                                title="'.get_lang('Move').'">'.
                                Display::return_icon('move.png', get_lang('Move'), [], ICON_SIZE_SMALL).'</a>';
                        }
                    }

                    if ($assignment->getAccepted()) {
                        $action .= '<a
                            href="'.$url.'work_list_all.php?'.api_get_cidreq().'&id='.$workId.'&action=make_invisible&item_id='.$item_id.'"
                            title="'.get_lang('invisible').'" >'.
                            Display::return_icon('visible.png', get_lang('invisible'), [], ICON_SIZE_SMALL).
                            '</a>';
                    } else {
                        $action .= '<a
                            href="'.$url.'work_list_all.php?'.api_get_cidreq().'&id='.$workId.'&action=make_visible&item_id='.$item_id.'"
                            title="'.get_lang('Visible').'" >'.
                            Display::return_icon('invisible.png', get_lang('Visible'), [], ICON_SIZE_SMALL).
                            '</a> ';
                    }

                    if ($locked) {
                        $action .= Display::return_icon('delete_na.png', get_lang('Delete'), '', ICON_SIZE_SMALL);
                    } else {
                        $action .= '<a
                            href="'.$url.'work_list_all.php?'.api_get_cidreq().'&id='.$workId.'&action=delete&item_id='.$item_id.'"
                            onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('Please confirm your choice'), ENT_QUOTES))."'".')) return false;"
                            title="'.get_lang('Delete').'" >'.
                            Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>';
                    }
                } elseif ($is_author && (empty($qualificatorId) || 0 == $qualificatorId)) {
                    $action .= '<a
                            href="'.$url.'view.php?'.api_get_cidreq().'&id='.$item_id.'"
                            title="'.get_lang('View').'">'.
                        Display::return_icon('default.png', get_lang('View'), [], ICON_SIZE_SMALL).
                        '</a>';

                    if (1 == $studentDeleteOwnPublication) {
                        if (api_is_allowed_to_session_edit(false, true)) {
                            $action .= '<a
                                href="'.$url.'edit.php?'.api_get_cidreq().'&item_id='.$item_id.'&id='.$workId.'"
                                title="'.get_lang('Edit').'">'.
                                Display::return_icon('edit.png', get_lang('Comment'), [], ICON_SIZE_SMALL).
                                '</a>';
                        }
                        $action .= ' <a
                            href="'.$url.'work_list.php?'.api_get_cidreq().'&action=delete&item_id='.$item_id.'&id='.$workId.'"
                            onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('Please confirm your choice'), ENT_QUOTES))."'".')) return false;"
                            title="'.get_lang('Delete').'"  >'.
                            Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>';
                    }
                } else {
                    $action .= '<a
                        href="'.$url.'view.php?'.api_get_cidreq().'&id='.$item_id.'"
                        title="'.get_lang('View').'">'.
                        Display::return_icon('default.png', get_lang('View'), [], ICON_SIZE_SMALL).
                        '</a>';
                }

                // Status.
                if (empty($qualificatorId)) {
                    $qualificator_id = Display::label(get_lang('Not reviewed'), 'warning');
                } else {
                    $qualificator_id = Display::label(get_lang('Revised'), 'success');
                }
                $work['qualificator_id'] = $qualificator_id.' '.$hasCorrection;
                $work['actions'] = '<div class="work-action">'.$linkToDownload.$action.'</div>';
                $work['correction'] = $correction;

                if (!empty($compilation)) {
                    throw new Exception('compilatio');
                    /*
                    $compilationId = $compilation->getCompilatioId($item_id, $course_id);
                    if ($compilationId) {
                        $actionCompilatio = "<div id='id_avancement".$item_id."' class='compilation_block'>
                            ".$loading.'&nbsp;'.get_lang('Connecting with the Compilatio server').'</div>';
                    } else {
                        $workDirectory = api_get_path(SYS_COURSE_PATH).$course_info['directory'];
                        if (!Compilatio::verifiFileType($dbTitle)) {
                            $actionCompilatio = get_lang('File format not supported');
                        } elseif (filesize($workDirectory.'/'.$work['url']) > $compilation->getMaxFileSize()) {
                            $sizeFile = round(filesize($workDirectory.'/'.$work['url']) / 1000000);
                            $actionCompilatio = get_lang('The file is too big to upload.').': '.format_file_size($sizeFile).'<br />';
                        } else {
                            $actionCompilatio = "<div id='id_avancement".$item_id."' class='compilation_block'>";
                            $actionCompilatio .= Display::url(
                                get_lang('Analyse'),
                                'javascript:void(0)',
                                [
                                    'class' => 'getSingleCompilatio btn btn-primary btn-xs',
                                    'onclick' => "getSingleCompilatio($item_id);",
                                ]
                            );
                            $actionCompilatio .= get_lang('with Compilatio');
                        }
                    }
                    $work['compilatio'] = $actionCompilatio;*/
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
    $status = 0
) {
    $work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $user_table = Database::get_main_table(TABLE_MAIN_USER);
    $userId = api_get_user_id();
    if (empty($userId)) {
        return [];
    }

    $courses = CourseManager::get_courses_list_by_user_id($userId, false, false, false);

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
        $session_id = isset($course['session_id']) ? $course['session_id'] : 0;
        //$conditionSession = api_get_session_condition($session_id, true, false, 'w.session_id');
        $conditionSession = '';
        $parentCondition = '';
        if ($withResults) {
            $parentCondition = 'AND ww.parent_id is NOT NULL';
        }
        $courseQuery[] = " (work.c_id = $courseIdItem $conditionSession $parentCondition )";
        $courseList[$courseIdItem] = $courseInfo;
    }

    $courseQueryToString = implode(' OR ', $courseQuery);
    $compilation = null;
    /*if (api_get_configuration_value('allow_compilatio_tool')) {
        $compilation = new Compilatio();
    }*/

    $is_allowed_to_edit = api_is_allowed_to_edit() || api_is_coach();

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

    $sql = " $select
            FROM $work_table work
            INNER JOIN $user_table u
            ON (work.user_id = u.id)
            WHERE
                work.parent_id <> 0 AND
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
    $repo = Container::getStudentPublicationRepository();
    while ($work = Database::fetch_array($result, 'ASSOC')) {
        $courseId = $work['c_id'];
        $courseInfo = $courseList[$work['c_id']];
        $sessionId = $work['session_id'];
        $cidReq = 'cidReq='.$courseInfo['code'].'&id_session='.$sessionId;

        $item_id = $workId = $work['id'];
        $dbTitle = $work['title'];
        // Get the author ID for that document from the item_property table
        $is_author = false;
        $can_read = false;
        $owner_id = $work['user_id'];
        //$visibility = api_get_item_visibility($courseInfo, 'work', $work['id'], $sessionId);
        $studentPublication = $repo->find($work['iid']);
        $workId = $studentPublication->getIid();
        $isVisible = $studentPublication->isVisible($courseEntity, $sessionEntity);
        if (false === $isVisible) {
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
        if (!$is_allowed_to_edit && $owner_id == api_get_user_id()) {
            $is_author = true;
        }

        /*if ($course_info['show_score'] == 0) {
            $can_read = true;
        }*/

        $qualification_string = '';
        if ($qualification_exists) {
            if ('' == $work['qualification']) {
                $qualification_string = Display::label('-');
            } else {
                $qualification_string = formatWorkScore($work['qualification'], $work['qualification']);
            }
        }

        $work_assignment = get_work_assignment_by_id($workId, $courseId);

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

        if (($can_read && '1' == $work['accepted']) ||
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
            if (strlen($work['title']) > 30) {
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
                $parentList[$work['parent_id']] = $parent;
            }
            $work['work_name'] = $parent['title'];

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
                        href="'.$url.'work_list_all.php?'.$cidReq.'&id='.$workId.'&action=export_to_doc&item_id='.$item_id.'"
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
                    $action .= '<a href="'.$url.'work_list_all.php?'.$cidReq.'&id='.$workId.'&action=make_invisible&item_id='.$item_id.'" title="'.get_lang('Invisible').'" >'.
                        Display::return_icon('visible.png', get_lang('Invisible'), [], ICON_SIZE_SMALL).'</a>';
                } else {
                    $action .= '<a href="'.$url.'work_list_all.php?'.$cidReq.'&id='.$workId.'&action=make_visible&item_id='.$item_id.'" title="'.get_lang('Visible').'" >'.
                        Display::return_icon('invisible.png', get_lang('Visible'), [], ICON_SIZE_SMALL).'</a> ';
                }*/
                /*if ($locked) {
                    $action .= Display::return_icon('delete_na.png', get_lang('Delete'), '', ICON_SIZE_SMALL);
                } else {
                    $action .= '<a href="'.$url.'work_list_all.php?'.$cidReq.'&id='.$workId.'&action=delete&item_id='.$item_id.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."'".')) return false;" title="'.get_lang('Delete').'" >'.
                        Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>';
                }*/
            } elseif ($is_author && (empty($work['qualificator_id']) || 0 == $work['qualificator_id'])) {
                $action .= '<a href="'.$url.'view.php?'.$cidReq.'&id='.$item_id.'" title="'.get_lang('View').'">'.
                    Display::return_icon('default.png', get_lang('View'), [], ICON_SIZE_SMALL).'</a>';

                if (1 == api_get_course_setting('student_delete_own_publication')) {
                    if (api_is_allowed_to_session_edit(false, true)) {
                        $action .= '<a href="'.$url.'edit.php?'.$cidReq.'&item_id='.$item_id.'&id='.$work['parent_id'].'" title="'.get_lang('Modify').'">'.
                            Display::return_icon('edit.png', get_lang('Comment'), [], ICON_SIZE_SMALL).'</a>';
                    }
                    $action .= ' <a href="'.$url.'work_list.php?'.$cidReq.'&action=delete&item_id='.$item_id.'&id='.$work['parent_id'].'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."'".')) return false;" title="'.get_lang('Delete').'"  >'.
                        Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>';
                }
            } else {
                $action .= '<a href="'.$url.'view.php?'.$cidReq.'&id='.$item_id.'" title="'.get_lang('View').'">'.
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

            /*if (!empty($compilation) && $is_allowed_to_edit) {
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
            }*/
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
    $task_id = $task_data['iid'];
    $task_title = !empty($task_data['title']) ? $task_data['title'] : basename($task_data['url']);
    $subject = '['.api_get_setting('siteName').'] ';

    // The body can be as long as you wish, and any combination of text and variables
    $content = get_lang('Please remember you still have to send an assignment')."\n".get_lang('Course name').' : '.$_course['name']."\n";
    $content .= get_lang('Assignment name').' : '.$task_title."\n";
    $list_users = get_list_users_without_publication($task_id);
    $mails_sent_to = [];
    foreach ($list_users as $user) {
        $name_user = api_get_person_name($user[1], $user[0], null, PERSON_NAME_EMAIL_ADDRESS);
        $dear_line = get_lang('Dear').' '.api_get_person_name($user[1], $user[0]).", \n\n";
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
                    get_lang('%s got a new assignment in course %s'),
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
    $emailsubject = '['.api_get_setting('siteName').'] '.get_lang('An assignment was created');
    $currentUser = api_get_user_info(api_get_user_id());
    if (!empty($students)) {
        foreach ($students as $student) {
            $user_info = api_get_user_info($student['user_id']);
            if (!empty($user_info)) {
                $link = api_get_path(WEB_CODE_PATH).'work/work_list.php?'.api_get_cidreq().'&id='.$workId;
                $emailbody = get_lang('Dear').' '.$user_info['complete_name'].",\n\n";
                $emailbody .= get_lang('An assignment has been added to course').' '.$courseCode.'. '."\n\n".
                    '<a href="'.$link.'">'.get_lang('Please check the assignments page.').'</a>';
                $emailbody .= "\n\n".$currentUser['complete_name'];

                MessageManager::send_message_simple(
                    $student['user_id'],
                    $emailsubject,
                    $emailbody
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

        $repo = Container::getStudentPublicationRepository();
        /** @var CStudentPublication $studentPublication */
        $studentPublication = $repo->find($itemId);
        if ($studentPublication->getResourceNode()->getCreator()->getId() === $userId) {
            $isAuthor = true;
        }

        /*$data = api_get_item_property_info($courseId, 'work', $itemId, $sessionId);
        if ($data['insert_user_id'] == $userId) {
            $isAuthor = true;
        }*/

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
                    parent_id = '$task_id' AND
                    active IN (0, 1)";
    } else {
        $sql = "SELECT user_id as id FROM $work_table
                WHERE
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
                      WHERE u.status != 1 and cu.c_id='".$course_id."' AND u.id = cu.user_id";
    } else {
        $sql_users = "SELECT cu.user_id, u.lastname, u.firstname, u.email
                      FROM $session_course_rel_user AS cu, $table_user AS u
                      WHERE
                        u.status != 1 AND
                        cu.c_id='".$course_id."' AND
                        u.id = cu.user_id AND
                        cu.session_id = '".$session_id."'";
    }

    if (!empty($studentId)) {
        $sql_users .= ' AND u.id = '.(int) $studentId;
    }

    $group_id = api_get_group_id();
    $new_group_user_list = [];

    if ($group_id) {
        $group = api_get_group_entity($group_id);
        $group_user_list = GroupManager::get_subscribed_users($group);
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
    $table_header[] = [get_lang('Last name'), true];
    $table_header[] = [get_lang('First name'), true];
    $table_header[] = [get_lang('e-mail'), true];

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
        'work_id = ?' => [$workId],
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
        'work_id = ?' => [$workId],
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
 * @return CDocument
 */
function getDocumentTemplateFromWork($workId, $courseInfo, $documentId)
{
    $documents = getAllDocumentToWork($workId, $courseInfo['real_id']);
    $docRepo = Container::getDocumentRepository();
    if (!empty($documents)) {
        foreach ($documents as $doc) {
            if ($documentId !== $doc['document_id']) {
                continue;
            }

            /** @var CDocument $docData */
            $docData = $docRepo->find($doc['document_id']);

            return $docData;

            /*$fileInfo = pathinfo($docData['path']);
            if ('html' == $fileInfo['extension']) {
                if (file_exists($docData['absolute_path']) && is_file($docData['absolute_path'])) {
                    $docData['file_content'] = file_get_contents($docData['absolute_path']);

                    return $docData;
                }
            }*/
        }
    }

    return null;
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
    $docRepo = Container::getDocumentRepository();
    $content = null;
    if (!empty($documents)) {
        $content .= '<ul class="nav nav-list well">';
        $content .= '<li class="nav-header">'.get_lang('Documents').'</li>';
        foreach ($documents as $doc) {
            /** @var CDocument $docData */
            $docData = $docRepo->find($doc['document_id']);
            $url = $docRepo->getResourceFileUrl($docData);
            if ($docData) {
                $content .= '<li>
                                <a class="link_to_download" target="_blank" href="'.$url.'">'.
                                    $docData->getTitle().'
                                </a>
                            </li>';
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

function getWorkComments(CStudentPublication $work)
{
    $comments = $work->getComments();
    $commentList = [];
    if (!empty($comments)) {
        foreach ($comments as $comment) {
            //$userInfo = api_get_user_info($comment['user_id']);
            //$comment['picture'] = $userInfo['avatar'];
            //$comment['complete_name'] = $userInfo['complete_name_with_username'];
            $commentList[] = getWorkComment($comment);
        }
    }

    return $commentList;
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
            FROM $commentTable c
            INNER JOIN $work w
            ON c.c_id = w.c_id AND w.iid = c.work_id
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
            ON c.c_id = w.c_id AND w.iid = c.work_id
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
    $parentId = $parentInfo['iid'];

    $sessionCondition = api_get_session_condition($sessionId);

    $sql = "SELECT *
            FROM $work
            WHERE
                user_id = $userId
                $sessionCondition AND
                parent_id = $parentId AND
                c_id = ".$courseInfo['real_id'].'
            ORDER BY sent_date DESC
            LIMIT 1
            ';
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
    }

    return $scoreBasedInModel;
}

function getWorkComment(CStudentPublicationComment $commentEntity, array $courseInfo = []): array
{
    if (empty($courseInfo)) {
        $courseInfo = api_get_course_info();
    }

    if (empty($courseInfo['real_id'])) {
        return [];
    }

    $repo = Container::getStudentPublicationCommentRepository();

    $comment = [];
    if ($commentEntity) {
        $filePath = null;
        $fileUrl = null;
        $deleteUrl = null;
        $fileName = null;
        $id = $commentEntity->getIid();
        if ($commentEntity->getResourceNode()->hasResourceFile()) {
            $fileUrl = $repo->getResourceFileDownloadUrl($commentEntity);
            $workId = $commentEntity->getPublication()->getIid();
            $filePath = '';
            $deleteUrl = api_get_path(WEB_CODE_PATH).
                'work/view.php?'.api_get_cidreq().'&id='.$workId.'&action=delete_attachment&comment_id='.$id;
            $fileName = $commentEntity->getResourceNode()->getResourceFile()->getName();
        }
        $comment['comment'] = $commentEntity->getComment();
        $comment['delete_file_url'] = $deleteUrl;
        $comment['file_path'] = $filePath;
        $comment['file_url'] = $fileUrl;
        $comment['file_name_to_show'] = $fileName;
        $comment['sent_at_with_label'] = Display::dateToStringAgoAndLongDate($commentEntity->getSentAt());
    }

    return $comment;
}

/**
 * @param int   $id
 * @param array $courseInfo
 */
function deleteCommentFile($id, $courseInfo = [])
{
    $repo = Container::getStudentPublicationCommentRepository();
    $em = Database::getManager();
    $criteria = [
        'iid' => $id,
        'cId' => $courseInfo['real_id'],
    ];

    /** @var CStudentPublicationComment $commentEntity */
    $commentEntity = $repo->findOneBy($criteria);

    if ($commentEntity->getResourceNode()->hasResourceFile()) {
        $file = $commentEntity->getResourceNode()->getResourceFile();

        $commentEntity->getResourceNode()->setResourceFile(null);
        $em->remove($file);
        $em->flush();
    }

    /*
    if (isset($workComment['file']) && !empty($workComment['file'])) {
        if (file_exists($workComment['file_path'])) {
            $result = my_delete($workComment['file_path']);
            if ($result) {
                $commentTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT_COMMENT);
                $params = ['file' => ''];
                Database::update(
                    $commentTable,
                    $params,
                    ['id = ? AND c_id = ? ' => [$workComment['iid'], $workComment['c_id']]]
                );
            }
        }
    }*/
}

/**
 * Adds a comments to the work document.
 *
 * @param array               $courseInfo
 * @param int                 $userId
 * @param array               $parentWork
 * @param CStudentPublication $work
 * @param array               $data
 *
 * @return int
 */
function addWorkComment($courseInfo, $userId, $parentWork, CStudentPublication $studentPublication, $data)
{
    $fileData = isset($data['attachment']) ? $data['attachment'] : null;
    // If no attachment and no comment then don't save comment
    if (empty($fileData['name']) && empty($data['comment'])) {
        return false;
    }
    $courseId = $courseInfo['real_id'];
    $courseEntity = api_get_course_entity($courseId);

    $request = Container::getRequest();
    $file = $request->files->get('attachment');
    if (is_array($file)) {
        $file = $file[0];
    }

    $em = Database::getManager();
    $comment = new CStudentPublicationComment();
    $comment
        ->setComment($data['comment'])
        ->setUser(api_get_user_entity($userId))
        ->setPublication($studentPublication)
        ->setParent($studentPublication)
        ->addCourseLink(
            $courseEntity,
            api_get_session_entity(),
            api_get_group_entity()
        );

    $repo = Container::getStudentPublicationCommentRepository();
    $repo->create($comment);

    if ($file) {
        $repo->addFile($comment, $file);
        $em->flush();
    }

    $userIdListToSend = [];
    if (api_is_allowed_to_edit()) {
        if (isset($data['send_email']) && $data['send_email']) {
            // Teacher sends a feedback
            $userIdListToSend = [$studentPublication->getUser()->getId()];
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
        if (1 != $sendNotification) {
            $userIdListToSend = [];
        }
    }
    $id = $studentPublication->getIid();
    $title = $studentPublication->getTitle();
    $url = api_get_path(WEB_CODE_PATH).'work/view.php?'.api_get_cidreq().'&id='.$id;
    $subject = sprintf(get_lang('There\'s a new feedback in work: %s'), $parentWork['title']);
    $content = sprintf(get_lang('There\'s a new feedback in work: %sInWorkXHere'), $title, $url);

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

    /*if (!empty($commentId) && !empty($fileData)) {
        $workParent = get_work_data_by_id($work['parent_id']);
        if (!empty($workParent)) {
            //$newFileName = 'comment_'.$commentId.'_'.php2phps(api_replace_dangerous_char($fileData['name']));
            //$newFilePath = $uploadDir.'/'.$newFileName;
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
    }*/
}

/**
 * @param array $workParent
 *
 * @return string
 */
function getWorkCommentForm(CStudentPublication $work, $workParent)
{
    $id = $work->getIid();

    $url = api_get_path(WEB_CODE_PATH).'work/view.php?id='.$id.'&action=send_comment&'.api_get_cidreq();
    $form = new FormValidator(
        'work_comment',
        'post',
        $url,
        '',
        ['enctype' => 'multipart/form-data']
    );

    $qualification = $workParent['qualification'];

    $isCourseManager = api_is_platform_admin() || api_is_coach() || api_is_allowed_to_edit(false, false, true);
    $allowEdition = false;
    if ($isCourseManager) {
        $allowEdition = true;
        if (!empty($work->getQualification()) &&
            api_get_configuration_value('block_student_publication_score_edition')
        ) {
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
                    [get_lang('Score'), ' / '.$qualification],
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
                    $work->getQualification()
                );
            }
            $form->addFile('file', get_lang('Correction'));
            $form->setDefaults(['qualification' => $work->getQualification()]);
        }
    }

    SkillModel::addSkillsToUserForm(
        $form,
        ITEM_TYPE_STUDENT_PUBLICATION,
        $workParent['iid'],
        $work->getUser()->getId(),
        $id
    );
    $form->addHtmlEditor('comment', get_lang('Comment'), false);
    $form->addFile('attachment', get_lang('Attachment'));
    $form->addElement('hidden', 'iid', $id);

    if (api_is_allowed_to_edit()) {
        $form->addCheckBox(
            'send_email',
            null,
            get_lang('Send message mail to student')
        );
    }

    $form->addButtonSend(get_lang('Send'), 'button');

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
            $message = Display::return_message(get_lang('End date already passed').' '.$ends_on, 'error');
        } elseif ($has_expired) {
            $message = Display::return_message(get_lang('Expiry date already passed').' '.$expires_on, 'warning');
        } else {
            if ($has_expired) {
                $message = Display::return_message(get_lang('ExpiryDateToSend messageWorkIs').' '.$expires_on);
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
    $form->addHeader(get_lang('Upload a document'));
    $form->addHidden('contains_file', 0, ['id' => 'contains_file_id']);
    $form->addHidden('active', 1);
    $form->addHidden('accepted', 1);
    $form->addElement('text', 'title', get_lang('Title'), ['id' => 'file_upload']);
    $form->addElement(
        'text',
        'extension',
        get_lang('File extension'),
        ['id' => 'file_extension', 'readonly' => 'readonly']
    );
    $form->addRule('title', get_lang('Required field'), 'required');

    switch ($uploadFormType) {
        case 0:
            // File and text.
            $form->addElement(
                'file',
                'file',
                get_lang('Upload a document'),
                'size="40" onchange="updateDocumentTitle(this.value)"'
            );
            $form->addProgress();
            $form->addHtmlEditor('description', get_lang('Description'), false, false, getWorkDescriptionToolbar());

            break;
        case 1:
            // Only text.
            $form->addHtmlEditor('description', get_lang('Description'), false, false, getWorkDescriptionToolbar());
            $form->addRule('description', get_lang('Required field'), 'required');

            break;
        case 2:
            // Only file.
            $form->addElement(
                'file',
                'file',
                get_lang('Upload a document'),
                'size="40" onchange="updateDocumentTitle(this.value)"'
            );
            $form->addProgress();
            $form->addRule('file', get_lang('Required field'), 'required');

            break;
    }

    $form->addButtonUpload(get_lang('Upload'), 'submitWork');
}

/**
 * @param array  $my_folder_data
 * @param Course $course
 * @param bool   $isCorrection
 * @param array  $workInfo
 * @param array  $file
 *
 * @return array
 */
function uploadWork($my_folder_data, $course, $isCorrection = false, $workInfo = [], $file = [])
{
    if (isset($_FILES['file']) && !empty($_FILES['file'])) {
        $file = $_FILES['file'];
    }

    if (empty($file['size'])) {
        return [
            'error' => Display:: return_message(
                get_lang(
                    'There was a problem uploading your document: the received file had a 0 bytes size on the server. Please, review your local file for any corruption or damage, then try again.'
                ),
                'error'
            ),
        ];
    }
    //$updir = api_get_path(SYS_COURSE_PATH).$_course['path'].'/work/'; //directory path to upload

    // Try to add an extension to the file if it has'nt one
    $filename = add_ext_on_mime(stripslashes($file['name']), $file['type']);

    // Replace dangerous characters
    $filename = api_replace_dangerous_char($filename);
    //$filename = api_replace_dangerous_char($filename);

    $filename = php2phps($filename);
    $filesize = filesize($file['tmp_name']);

    if (empty($filesize)) {
        return [
            'error' => Display::return_message(
                get_lang(
                    'There was a problem uploading your document: the received file had a 0 bytes size on the server. Please, review your local file for any corruption or damage, then try again.'
                ),
                'error'
            ),
        ];
    }

    /*if (!filter_extension($new_file_name)) {
        return [
            'error' => Display::return_message(
                get_lang('File upload failed: this file extension or file type is prohibited'),
                'error'
            ),
        ];
    }*/

    $repo = Container::getDocumentRepository();
    $totalSpace = $repo->getTotalSpaceByCourse($course);
    $course_max_space = DocumentManager::get_course_quota($course->getCode());
    $total_size = $filesize + $totalSpace;

    if ($total_size > $course_max_space) {
        return [
            'error' => Display::return_message(
                get_lang(
                    'The upload has failed. Either you have exceeded your maximum quota, or there is not enough disk space.'
                ),
                'error'
            ),
        ];
    }

    // Compose a unique file name to avoid any conflict
    $new_file_name = api_get_unique_id();

    if ($isCorrection) {
        if (!empty($workInfo['url'])) {
            $new_file_name = basename($workInfo['url']).'_correction';
        } else {
            $new_file_name .= '_correction';
        }
    }

    //$curdirpath = basename($my_folder_data['url']);
    // If we come from the group tools the groupid will be saved in $work_table
    /*if (is_dir($updir.$curdirpath) || empty($curdirpath)) {
        $result = move_uploaded_file(
            $file['tmp_name'],
            $updir.$curdirpath.'/'.$new_file_name
        );
    } else {
        return [
            'error' => Display :: return_message(
                get_lang('Target folder doesn\'t exist on the server.'),
                'error'
            ),
        ];
    }*/

    /*if ($result) {
        //$url = 'work/'.$curdirpath.'/'.$new_file_name;
    } else {
        return false;
    }*/

    return [
        //'url' => $url,
        'filename' => $filename,
        'filesize' => $filesize,
        'error' => '',
        'file' => $file,
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
    if (SEND_EMAIL_EVERYONE == $send || SEND_EMAIL_TEACHERS == $send) {
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

    if (SEND_EMAIL_EVERYONE == $send || SEND_EMAIL_STUDENTS == $send) {
        // Send mail only to sender
        $studentList = [[
           'user_id' => api_get_user_id(),
        ]];
        $userList = array_merge($userList, $studentList);
    }

    if ($send) {
        $folderUrl = api_get_path(WEB_CODE_PATH).
            "work/work_list_all.php?cid=".$courseInfo['real_id']."&sid=".$sessionId."&id=".$workInfo['iid'];
        $fileUrl = api_get_path(WEB_CODE_PATH).
            "work/view.php?cid=".$courseInfo['real_id']."&sid=".$sessionId."&id=".$workData['iid'];

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
 * @return CStudentPublication|null
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
    $courseId = $courseInfo['real_id'];
    $courseEntity = api_get_course_entity($courseId);
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
            if (checkExistingWorkFileName($file['name'], $workInfo['iid'])) {
                $saveWork = false;
                $result['error'] = get_lang(
                    'You have already sent this file or another file with the same name. Please make sure you only upload each file once.'
                );
                $workData['error'] = get_lang(' already exists.');
            } else {
                $result = uploadWork($workInfo, $courseEntity, false, [], $file);
            }
        } else {
            $result = uploadWork($workInfo, $courseEntity, false, [], $file);
        }

        if (isset($result['error'])) {
            $saveWork = false;
            if ($showFlashMessage) {
                $message = $result['error'];
            }
            if (empty($result['error'])) {
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

    $studentPublication = null;
    if ($saveWork) {
        $documentId = isset($values['document_id']) ? (int) $values['document_id'] : 0;

        $request = Container::getRequest();
        $content = $request->files->get('file');
        if (is_array($content)) {
            $content = $content[0];
        }

        if (empty($content)) {
            $content = $request->files->get('files');
            if (is_array($content)) {
                $content = $content[0];
            }
        }

        $session = api_get_session_entity($sessionId);

        $repo = Container::getStudentPublicationRepository();
        $parentResource = $repo->find($workInfo['iid']);
        $user = api_get_user_entity($userId);

        $studentPublication = new CStudentPublication();
        $studentPublication
            ->setFiletype('file')
            ->setTitle($title)
            ->setDescription($description)
            ->setContainsFile($containsFile)
            ->setActive(1)
            ->setAccepted(true)
            ->setQualificatorId(0)
            ->setWeight(0)
            ->setAllowTextAssignment(0)
            ->setPostGroupId(api_get_group_id())
            ->setPublicationParent($parentResource)
            ->setFilesize($filesize)
            ->setUser($user)
            ->setDocumentId($documentId)
            ->setParent($parentResource)
            ->addCourseLink($courseEntity, $session, api_get_group_entity())
        ;

        $em = Database::getManager();
        $em->persist($studentPublication);
        $repo->addFile($studentPublication, $content);
        $em->flush();

        $workId = $studentPublication->getIid();

        if ($workId) {
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
                        $workInfo['iid'],
                        null,
                        $userId,
                        false,
                        $courseId,
                        $sessionId
                    );

                    if (1 === count($userWorks)) {
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

            if ($showFlashMessage) {
                Display::addFlash(Display::return_message(get_lang('The file has been added to the list of publications.')));
            }
        }
    } else {
        if ($showFlashMessage) {
            Display::addFlash(
                Display::return_message(
                    $message ?: get_lang('Impossible to save the document'),
                    'error'
                )
            );
        }
    }

    return $studentPublication;
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
    $user_id = (int) $user_id;
    $groupId = (int) $groupId;
    $sessionId = (int) $sessionId;

    $groupIid = 0;
    $groupInfo = [];
    if (!empty($groupId)) {
        $groupInfo = GroupManager::get_group_properties($groupId);
        $groupIid = $groupInfo['iid'];
    }
    $session = api_get_session_entity($sessionId);
    $course_id = $courseInfo['real_id'];

    $enableEndDate = isset($formValues['enableEndDate']) ? true : false;
    $enableExpiryDate = isset($formValues['enableExpiryDate']) ? true : false;

    if ($enableEndDate && $enableExpiryDate) {
        if ($formValues['expires_on'] > $formValues['ends_on']) {
            Display::addFlash(
                Display::return_message(
                    get_lang('The date of effective blocking of sending the work can not be before the displayed posting deadline.'),
                    'warning'
                )
            );

            return false;
        }
    }

    $today = new DateTime(api_get_utc_datetime(), new DateTimeZone('UTC'));
    $title = isset($formValues['work_title']) ? $formValues['work_title'] : $formValues['new_dir'];
    $courseEntity = api_get_course_entity($course_id);

    $studentPublication = new CStudentPublication();
    $studentPublication
        ->setTitle($title)
        ->setDescription($formValues['description'])
        ->setActive(1)
        ->setAccepted(true)
        ->setFiletype('folder')
        ->setPostGroupId($groupIid)
        ->setSentDate($today)
        ->setQualification('' != $formValues['qualification'] ? $formValues['qualification'] : 0)
        ->setWeight(!empty($formValues['weight']) ? $formValues['weight'] : 0)
        ->setAllowTextAssignment(1 === (int) $formValues['allow_text_assignment'] ? 1 : 0)
        ->setUser(api_get_user_entity($user_id))
        ->setParent($courseEntity)
        ->addCourseLink(
            $courseEntity,
            api_get_session_entity(),
            api_get_group_entity()
        )
    ;

    $repo = Container::getStudentPublicationRepository();
    $repo->create($studentPublication);

    // Folder created
    /*api_item_property_update(
        $courseInfo,
        'work',
        $studentPublication->getIid(),
        'DirectoryCreated',
        $user_id,
        $groupInfo
    );

    updatePublicationAssignment(
        $studentPublication->getIid(),
        $formValues,
        $courseInfo,
        $groupIid
    );*/

    // Added the new Work ID to the extra field values
    $formValues['item_id'] = $studentPublication->getIid();

    $workFieldValue = new ExtraFieldValue('work');
    $workFieldValue->saveFieldValues($formValues);

    $sendEmailAlert = api_get_course_setting('email_alert_students_on_new_homework');

    switch ($sendEmailAlert) {
        case 1:
            sendEmailToStudentsOnHomeworkCreation(
                $studentPublication->getIid(),
                $course_id,
                $sessionId
            );
            //no break
        case 2:
            sendEmailToDrhOnHomeworkCreation(
                $studentPublication->getIid(),
                $course_id,
                $sessionId
            );

            break;
    }

    return $studentPublication->getIid();
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
        'weight' => (float) $params['weight'],
        'allow_text_assignment' => $params['allow_text_assignment'],
    ];

    Database::update(
        $workTable,
        $filteredParams,
        [
            'iid = ?' => [
                $workId,
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
    if (isset($params['add_to_calendar']) && 1 == $params['add_to_calendar']) {
        // Setting today date
        $date = $end_date = $now;

        if (isset($params['enableExpiryDate'])) {
            $end_date = $params['expires_on'];
            $date = $end_date;
        }

        $title = sprintf(get_lang('Handing over of task %s'), $params['new_dir']);
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
    $expiryDate = isset($params['enableExpiryDate']) && 1 == (int) $params['enableExpiryDate'] ? api_get_utc_datetime($params['expires_on']) : '';
    $endDate = isset($params['enableEndDate']) && 1 == (int) $params['enableEndDate'] ? api_get_utc_datetime($params['ends_on']) : '';
    $data = get_work_assignment_by_id($workId, $course_id);
    if (!empty($expiryDate)) {
        $expiryDateCondition = "expires_on = '".Database::escape_string($expiryDate)."', ";
    } else {
        $expiryDateCondition = 'expires_on = null, ';
    }

    if (!empty($endDate)) {
        $endOnCondition = "ends_on = '".Database::escape_string($endDate)."', ";
    } else {
        $endOnCondition = 'ends_on = null, ';
    }

    /** @var CStudentPublication $publication */
    $publication = Container::getStudentPublicationRepository()->find($workId);
    $em = Database::getManager();

    if (empty($data)) {
        $assignment = new CStudentPublicationAssignment();

        $publication
            ->setHasProperties(1)
            ->setViewProperties(1)
        ;
        $em->persist($publication);

    /*$sql = "INSERT INTO $table SET
            c_id = $course_id ,
            $expiryDateCondition
            $endOnCondition
            add_to_calendar = $agendaId,
            enable_qualification = '$qualification',
            publication_id = '$workId'";
    Database::query($sql);
    $my_last_id = Database::insert_id();

    if ($my_last_id) {
        $sql = "UPDATE $workTable SET
                    has_properties  = $my_last_id,
                    view_properties = 1
                WHERE iid = $workId";
        Database::query($sql);
    }*/
    } else {
        $assignment = $em->getRepository(CStudentPublicationAssignment::class)->find($data['iid']);
        /*$sql = "UPDATE $table SET
                    $expiryDateCondition
                    $endOnCondition
                    add_to_calendar  = $agendaId,
                    enable_qualification = '".$qualification."'
                WHERE
                    publication_id = $workId AND
                    iid = ".$data['iid'];
        Database::query($sql);*/
    }

    $assignment
        ->setAddToCalendar($agendaId)
        ->setEnableQualification(1 === $qualification)
        ->setPublication($publication)
    ;
    if (!empty($expiryDate)) {
        $assignment->setExpiresOn(api_get_utc_datetime($expiryDate, true, true));
    }

    if (!empty($endDate)) {
        $assignment->setEndsOn(api_get_utc_datetime($endDate, true, true));
    }
    $em->persist($assignment);
    $em->flush();

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
            1 == $params['make_calification']
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
 * @return array return deleted items
 */
function deleteAllWorkPerUser(User $user, Course $course)
{
    $deletedItems = [];
    //$workPerUser = getWorkPerUser($userId);
    $repo = Container::getStudentPublicationRepository();
    $works = $repo->getStudentPublicationByUser($user, $course);

    foreach ($works as $workData) {
        /** @var CStudentPublication[] $results */
        $results = $workData['results'];
        foreach ($results as $userResult) {
            $result = deleteWorkItem($userResult->getIid(), $course);
            if ($result) {
                $deletedItems[] = $userResult;
            }
        }
    }

    return $deletedItems;
}

/**
 * @param int    $item_id
 * @param Course $course  course info
 *
 * @return bool
 */
function deleteWorkItem($item_id, Course $course)
{
    $item_id = (int) $item_id;

    if (empty($item_id) || null === $course) {
        return false;
    }

    $work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $is_allowed_to_edit = api_is_allowed_to_edit();
    $file_deleted = false;
    $is_author = user_is_author($item_id);
    $work_data = get_work_data_by_id($item_id);
    $locked = api_resource_is_locked_by_gradebook($work_data['parent_id'], LINK_STUDENTPUBLICATION);
    $course_id = $course->getId();

    if (($is_allowed_to_edit && false == $locked) ||
        (
            false == $locked &&
            $is_author &&
            1 == api_get_course_setting('student_delete_own_publication') &&
            0 == $work_data['qualificator_id']
        )
    ) {
        // We found the current user is the author
        $sql = "SELECT contains_file, user_id, parent_id
                FROM $work_table
                WHERE iid = $item_id";
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
                if (1 == count($userWorks)) {
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

            $em = Database::getManager();
            $repo = Container::getStudentPublicationRepository();
            /** @var CStudentPublication $work */
            $work = $repo->find($item_id);
            $work->setActive(2);
            $repo->update($work);

            /*$repo = Container::getStudentPublicationAssignmentRepository();
            $params = ['cId' => $course_id, 'publicationId' => $item_id];

            $items = $repo->findBy($params);
            foreach ($items as $item) {
                $repo->delete($item);
            }*/

            $em->flush();

            /*$sql = "DELETE FROM $TSTDPUBASG
                    WHERE c_id = $course_id AND publication_id = $item_id";
            Database::query($sql);*/

            Compilatio::plagiarismDeleteDoc($course_id, $item_id);

            /*api_item_property_update(
                $courseInfo,
                'work',
                $item_id,
                'DocumentDeleted',
                api_get_user_id()
            );*/

            Event::addEvent(
                LOG_WORK_FILE_DELETE,
                LOG_WORK_DATA,
                [
                    'id' => $work_data['iid'],
                    'url' => $work_data['url'],
                    'title' => $work_data['title'],
                ],
                null,
                api_get_user_id(),
                api_get_course_int_id(),
                api_get_session_id()
            );
            $file_deleted = true;

            if (1 == $row['contains_file']) {
                /*if (!empty($work)) {
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
                }*/
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
    $form->addText('new_dir', get_lang('Assignment name'));
    $form->addHtmlEditor(
        'description',
        get_lang('Description'),
        false,
        false,
        getWorkDescriptionToolbar()
    );
    $form->addButtonAdvancedSettings('advanced_params', get_lang('Advanced settings'));

    if (!empty($defaults) && (isset($defaults['enableEndDate']) || isset($defaults['enableExpiryDate']))) {
        $form->addHtml('<div id="advanced_params_options" style="display:block">');
    } else {
        $form->addHtml('<div id="advanced_params_options" style="display:none">');
    }

    // ScoreOfAssignment
    $form->addText('qualification', get_lang('ScoreNumeric'), false);

    if (0 != $sessionId && Gradebook::is_active() || 0 == $sessionId) {
        $form->addElement(
            'checkbox',
            'make_calification',
            null,
            get_lang('Add to gradebook'),
            [
                'id' => 'make_calification_id',
                'onclick' => "javascript: if(this.checked) { document.getElementById('option1').style.display='block';}else{document.getElementById('option1').style.display='none';}",
            ]
        );
    } else {
        // QualificationOfAssignment
        $form->addHidden('make_calification', false);
    }

    if (!empty($defaults) && isset($defaults['category_id'])) {
        $form->addHtml('<div id=\'option1\' style="display:block">');
    } else {
        $form->addHtml('<div id=\'option1\' style="display:none">');
    }

    // Loading Gradebook select
    GradebookUtils::load_gradebook_select_in_tool($form);

    $form->addElement('text', 'weight', get_lang('Weight inside assessment'));
    $form->addHtml('</div>');

    $form->addCheckBox(
        'enableExpiryDate',
        null,
        get_lang('Enable handing over deadline (visible to learners)'),
        ['id' => 'expiry_date']
    );
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

    $form->addElement('date_time_picker', 'expires_on', get_lang('Posted sending deadline'));
    $form->addHtml('</div>');
    $form->addCheckBox(
        'enableEndDate',
        null,
        get_lang('Enable final acceptance date (invisible to learners)'),
        ['id' => 'end_date']
    );

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

    $form->addElement('date_time_picker', 'ends_on', get_lang('Ends at (completely closed)'));
    $form->addHtml('</div>');

    $form->addCheckBox('add_to_calendar', null, get_lang('Add to calendar'));
    $form->addSelect('allow_text_assignment', get_lang('Document type'), getUploadDocumentType());

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

    $skillList = SkillModel::addSkillsToForm($form, ITEM_TYPE_STUDENT_PUBLICATION, $workId);

    if (!empty($defaults)) {
        $defaults['skills'] = array_keys($skillList);
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
        0 => get_lang('Allow files or online text'),
        1 => get_lang('Allow only text'),
        2 => get_lang('Allow only files'),
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

    $repo = Container::getStudentPublicationRepository();
    /** @var CStudentPublication $studentPublication */
    $studentPublication = $repo->find($itemId);
    if ($studentPublication) {
        $studentPublication->setAccepted(1);
        $repo->update($studentPublication);
    }
    /*
    $work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $course_id = $course_info['real_id'];

    $sql = "UPDATE $work_table SET accepted = 1
            WHERE c_id = $course_id AND id = $itemId";
    Database::query($sql);
    api_item_property_update($course_info, 'work', $itemId, 'visible', api_get_user_id());
    */
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

    $repo = Container::getStudentPublicationRepository();
    /** @var CStudentPublication $studentPublication */
    $studentPublication = $repo->find($itemId);
    if ($studentPublication) {
        $studentPublication->setAccepted(0);
        $repo->update($studentPublication);
    }

    /*api_item_property_update(
        $course_info,
        'work',
        $itemId,
        'invisible',
        api_get_user_id()
    );*/

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

    $sql = "SELECT iid, url, title
            FROM $work_table
            WHERE
                c_id = $courseId AND
                active IN (0, 1) AND
                parent_id = 0 AND
                post_group_id = $groupIid
                $sessionCondition";
    $res = Database::query($sql);
    while ($folder = Database::fetch_array($res)) {
        $title = empty($folder['title']) ? basename($folder['url']) : $folder['title'];
        $folders[$folder['iid']] = $title;
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
        get_lang('Learners'),
        get_lang('Assignments'),
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

        if (false == $getCount) {
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
            $works = $userWorks.' / '.count($workParents);
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
    return getFile($id, $course_info, true, $isCorrection, true);
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
    var_dump($file);
    exit;
    if (!empty($file) && isset($file['entity'])) {
        /** @var CStudentPublication $studentPublication */
        $studentPublication = $file['entity'];
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

    $courseEntity = api_get_course_entity($courseInfo['real_id']);
    $sessionEntity = api_get_session_entity($sessionId);

    $repo = Container::getStudentPublicationRepository();
    /** @var CStudentPublication $studentPublication */
    $studentPublication = $repo->find($id);

    if (empty($studentPublication)) {
        return false;
    }

    if ($correction) {
        //$row['url'] = $row['url_correction'];
    }
    $hasFile = $studentPublication->getResourceNode()->hasResourceFile();
    if (!$hasFile) {
        return false;
    }

    /*
    $item_info = api_get_item_property_info(
        api_get_course_int_id(),
        'work',
        $row['iid'],
        $sessionId
    );

    if (empty($item_info)) {
        return false;
    }*/

    $isAllow = allowOnlySubscribedUser(
        api_get_user_id(),
        $studentPublication->getPublicationParent()->getIid(),
        $courseInfo['real_id'],
        $forceAccessForCourseAdmins
    );

    if (empty($isAllow)) {
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

    $work_is_visible = $studentPublication->isVisible($courseEntity, $sessionEntity) && $studentPublication->getAccepted();
    $doc_visible_for_all = 0 === (int) $courseInfo['show_score'];

    $is_editor = api_is_allowed_to_edit(true, true, true);
    $student_is_owner_of_work = user_is_author($studentPublication->getIid(), api_get_user_id());

    if (($forceAccessForCourseAdmins && $isAllow) ||
        $is_editor ||
        $student_is_owner_of_work ||
        ($doc_visible_for_all && $work_is_visible)
    ) {
        $title = $studentPublication->getTitle();
        $titleCorrection = '';
        if ($correction && $studentPublication->getCorrection()) {
            $title = $titleCorrection = $studentPublication->getCorrection()->getTitle();
        }
        if ($hasFile) {
            $title = $studentPublication->getResourceNode()->getResourceFile()->getName();
        }

        $title = str_replace(' ', '_', $title);
        if (false == $correction) {
            $userInfo = $studentPublication->getUser();
            if ($userInfo) {
                $date = api_get_local_time($studentPublication->getSentDate()->format('Y-m-d H:i:s'));
                $date = str_replace([':', '-', ' '], '_', $date);
                $title = $date.'_'.$studentPublication->getUser()->getUsername().'_'.$title;
            }
        }

        return [
            //'path' => $full_file_name,
            'title' => $title,
            'title_correction' => $titleCorrection,
            'entity' => $studentPublication,
        ];
    }

    return false;
}

/**
 * @return bool
 */
function exportAllWork(User $user, Course $course, $format = 'pdf')
{
    $repo = Container::getStudentPublicationRepository();
    $works = $repo->getStudentPublicationByUser($user, $course, null);

    switch ($format) {
        case 'pdf':
            if (!empty($works)) {
                $pdf = new PDF();
                $content = null;
                foreach ($works as $workData) {
                    /** @var CStudentPublication $work */
                    $work = $workData['work'];
                    /** @var CStudentPublication[] $results */
                    $results = $workData['results'];
                    foreach ($results as $userResult) {
                        $content .= $userResult->getTitle();
                        // No need to use api_get_local_time()
                        $content .= $userResult->getSentDate()->format('Y-m-d H:i:s');
                        $content .= $userResult->getQualification();
                        $content .= $userResult->getDescription();
                    }
                }

                if (!empty($content)) {
                    $pdf->content_to_pdf(
                        $content,
                        null,
                        api_replace_dangerous_char(UserManager::formatUserFullName($user)),
                        $course->getCode()
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
    $teachers = CourseManager::getTeacherListFromCourseCodeToString($courseCode);

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

    $header .= '<br />'.get_lang('Trainers').': '.$teachers.'<br />';
    $header .= '<br />'.get_lang('Date').': '.api_get_local_time().'<br />';
    $header .= '<br />'.get_lang('Assignment name').': '.$workData['title'].'<br />';

    $content = null;
    $expiresOn = null;
    if (!empty($assignment) && isset($assignment['expires_on'])) {
        $content .= '<br /><strong>'.
            get_lang('Posted deadline for sending the work (Visible to the learner)').'</strong>: '.
            api_get_local_time($assignment['expires_on']);
        $expiresOn = api_get_local_time($assignment['expires_on']);
    }

    if (!empty($workData['description'])) {
        $content .= '<br /><strong>'.get_lang('Description').'</strong>: '.$workData['description'];
    }

    $workList = get_work_user_list(null, null, null, null, $workId);

    switch ($format) {
        case 'pdf':
            if (!empty($workList)) {
                $table = new HTML_Table(['class' => 'data_table']);
                $headers = [
                    get_lang('Name'),
                    get_lang('User'),
                    get_lang('Deadline'),
                    get_lang('Sent date'),
                    get_lang('Filename'),
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
                /** @var array $work */
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
                            $feedback .= get_lang('User').': '.$comment['complete_name'].'<br />';
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
    throw new Exception('downloadAllFilesPerUser');
    /*
    $userInfo = api_get_user_info($userId);

    if (empty($userInfo) || empty($courseInfo)) {
        return false;
    }

    $tempZipFile = api_get_path(SYS_ARCHIVE_PATH).api_get_unique_id().'.zip';
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
                $data = getFileContents($userResult['iid'], $courseInfo);
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
    exit;*/
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
 * @param int $userId
 * @param int $courseId
 * @param int $sessionId
 *
 * @return array
 */
function getWorkCreatedByUser($userId, $courseId, $sessionId)
{
    $repo = Container::getStudentPublicationRepository();

    $courseEntity = api_get_course_entity($courseId);
    $sessionEntity = api_get_session_entity($sessionId);

    $qb = $repo->getResourcesByCourse($courseEntity, $sessionEntity);

    $qb->andWhere('node.creator = :creator');
    $qb->setParameter('creator', $userId);
    $items = $qb->getQuery()->getResult();

    $list = [];
    if (!empty($items)) {
        /** @var CStudentPublication $work */
        foreach ($items as $work) {
            $list[] = [
                $work->getTitle(),
                api_get_local_time($work->getResourceNode()->getCreatedAt()),
                api_get_local_time($work->getResourceNode()->getUpdatedAt()),
            ];
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

    $workId = $workData['iid'];

    if (1 != $workData['active']) {
        api_not_allowed(true);
    }

    /*$visibility = api_get_item_visibility($courseInfo, 'work', $workId, $sessionId);
    if ($visibility != 1) {
        api_not_allowed(true);
    }*/

    $isAllow = allowOnlySubscribedUser($userId, $workId, $courseInfo['real_id']);
    if (empty($isAllow)) {
        api_not_allowed(true);
    }
    if (!empty($groupId)) {
        $group = api_get_group_entity($groupId);
        $showWork = GroupManager::userHasAccess(
            $userId,
            $group,
            GroupManager::GROUP_TOOL_WORK
        );
        if (!$showWork) {
            api_not_allowed(true);
        }
    }
}

function deleteCorrection(CStudentPublication $work)
{
    $correctionNode = $work->getCorrection();
    if (null !== $correctionNode) {
        $em = Database::getManager();
        $em->remove($correctionNode);
        $em->flush();
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

<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 *	@package chamilo.work
 * 	@author Thomas, Hugues, Christophe - original version
 * 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University -
 * ability for course admins to specify wether uploaded documents are visible or invisible by default.
 * 	@author Roan Embrechts, code refactoring and virtual course support
 * 	@author Frederic Vauthier, directories management
 *  @author Julio Montoya <gugli100@gmail.com> BeezNest 2011 LOTS of bug fixes
 * 	@todo 	this lib should be convert in a static class and moved to main/inc/lib
 */
/**
 * Initialization
 */
require_once api_get_path(SYS_CODE_PATH).'document/document.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileDisplay.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';

//$_configuration['add_document_to_work'] = true;
if (isset($_configuration['add_document_to_work'])) {
    define('ADD_DOCUMENT_TO_WORK', $_configuration['add_document_to_work']);
} else {
    define('ADD_DOCUMENT_TO_WORK', false);
}

//$_configuration['work_user_comments'] = true;
if (isset($_configuration['work_user_comments'])) {
    define('ALLOW_USER_COMMENTS', $_configuration['work_user_comments']);
} else {
    define('ALLOW_USER_COMMENTS', false);
}

/**
 * Displays action links (for admins, authorized groups members and authorized students)
 * @param	string	Current dir
 * @param	integer	Whether to show tool options
 * @param	integer	Whether to show upload form option
 * @return	void
 */
function display_action_links($id, $cur_dir_path, $action)
{
	global $gradebook;

    $id = $my_back_id = intval($id);
    if ($action == 'list') {
        $my_back_id = 0;
    }

    $display_output = '';
    $origin = isset($_GET['origin']) ? Security::remove_XSS($_GET['origin']) : '';

    if (!empty($id)) {
        $display_output .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&origin='.$origin.'&gradebook='.$gradebook.'&id='.$my_back_id.'">'.Display::return_icon('back.png', get_lang('BackToWorksList'),'',ICON_SIZE_MEDIUM).'</a>';
    }

    if (api_is_allowed_to_edit(null, true) && $origin != 'learnpath') {
        // Create dir
        if (empty($id)) {
            $display_output .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;action=create_dir&origin='.$origin.'&gradebook='.$gradebook.'">';
            $display_output .= Display::return_icon('new_work.png', get_lang('CreateAssignment'),'',ICON_SIZE_MEDIUM).'</a>';
        }
        if (empty($id)) {
            // Options
            $display_output .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;action=settings&amp;origin='.$origin.'&amp;gradebook='.$gradebook.'">';
            $display_output .= Display::return_icon('settings.png', get_lang('EditToolOptions'),'',ICON_SIZE_MEDIUM).'</a>';
        }
    }

    if (api_is_allowed_to_edit(null, true) && $origin != 'learnpath' && api_is_allowed_to_session_edit(false, true)) {
        // Delete all files
        if (api_get_setting('permanently_remove_deleted_files') == 'true'){
            $message = get_lang('ConfirmYourChoiceDeleteAllfiles');
        } else {
            $message = get_lang('ConfirmYourChoice');
        }
    }

	if ($display_output != '') {
		echo '<div class="actions">';
		echo $display_output;
		echo '</div>';
	}
}

/**
 * Displays all options for this tool.
 * These are
 * - make all files visible / invisible
 * - set the default visibility of uploaded files
 * @param $defaults
 */
function settingsForm($defaults)
{
    $is_allowed_to_edit = api_is_allowed_to_edit(null, true);
    if (!$is_allowed_to_edit) {
        return;
    }

    $url = api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq().'&action=settings';
    $form = new FormValidator('edit_settings', 'post', $url);
    $form->addElement('hidden', 'changeProperties', 1);
    $form->addElement('header', get_lang('EditToolOptions'));

    $group = array(
        $form->createElement('radio', 'show_score', null, get_lang('NewVisible'), 0),
        $form->createElement('radio', 'show_score', null, get_lang('NewUnvisible'), 1)
    );
    $form->addGroup($group, '', get_lang('DefaultUpload'));

    $group = array(
        $form->createElement('radio', 'student_delete_own_publication', null, get_lang('Yes'), 1),
        $form->createElement('radio', 'student_delete_own_publication', null, get_lang('No'), 0)
    );
    $form->addGroup($group, '', get_lang('StudentAllowedToDeleteOwnPublication'));
    $form->addElement('button', 'submit', get_lang('Save'));
    $form->setDefaults($defaults);

    return $form->return_form();
}

/**
 * converts 1-9 to 01-09
 */
function two_digits($number)
{
	$number = (int)$number;
	return ($number < 10) ? '0'.$number : $number;
}

/**
 * Converts 2008-10-06 12:45:00 to -> array('prefix' => array(year'=>2008, 'month'=>10, etc...)
 * @param string
 * @param string
 * @param array
 */
function convert_date_to_array($date, $group)
{
    $parts = explode(' ', $date);
    $date_parts = explode('-', $parts[0]);
    $date_parts_tmp = array();
    foreach ($date_parts as $item) {
        $date_parts_tmp[] = intval($item);
    }

    $time_parts = explode(':', $parts[1]);
    $time_parts_tmp = array();
    foreach ($time_parts as $item) {
        $time_parts_tmp[] = intval($item);
    }
    list($data[$group]['year'], $data[$group]['month'], $data[$group]['day']) = $date_parts_tmp;
    list($data[$group]['hour'], $data[$group]['minute']) = $time_parts_tmp;
	return $data;
}

/**
 * get date from a group of date
 */
function get_date_from_group($group)
{
	return $_POST[$group]['year'].'-'.two_digits($_POST[$group]['month']).'-'.two_digits($_POST[$group]['day']).' '.two_digits($_POST[$group]['hour']).':'.two_digits($_POST[$group]['minute']).':00';
}

/**
 * Create a group of select from a date
 * @param FormValidator $form
 * @param string $prefix
 * @return array
 */
function create_group_date_select($form, $prefix = '')
{
    $minute = range(10, 59);
    $d_year = date('Y');
    array_unshift($minute, '00', '01', '02', '03', '04', '05', '06', '07', '08', '09');

    $group_name = array(
        $form->createElement('select', $prefix.'day', '', array_combine(range(1, 31), range(1, 31))),
        $form->createElement('select', $prefix.'month', '', array_combine(range(1, 12), api_get_months_long())),
        $form->createElement('select', $prefix.'year', '', array($d_year => $d_year, $d_year + 1 => $d_year + 1)),
        $form->createElement('select', $prefix.'hour', '', array_combine(range(0, 23), range(0, 23))),
        $form->createElement('select', $prefix.'minute', '', $minute)
    );
    return $group_name;
}

/**
 * @param string $path
 * @param int $courseId
 * @return array
 */
function get_work_data_by_path($path, $courseId = null)
{
	$path = Database::escape_string($path);
    if (empty($courseId)) {
        $courseId 	= api_get_course_int_id();
    } else {
        $courseId 	= intval($courseId);
    }

	$work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$sql = "SELECT *  FROM  ".$work_table." WHERE url = '$path' AND c_id = $courseId ";
	$result = Database::query($sql);
	$return = array();
	if (Database::num_rows($result)) {
		$return = Database::fetch_array($result,'ASSOC');
	}
	return $return;
}

/**
 * @param int $id
 * @return array
 */
function get_work_data_by_id($id)
{
	$id = intval($id);
	$course_id 	= api_get_course_int_id();
	$work_table	= Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$sql = "SELECT * FROM $work_table WHERE id = $id AND c_id = $course_id";
	$result = Database::query($sql);
    $work = array();
	if (Database::num_rows($result)) {
		$work = Database::fetch_array($result, 'ASSOC');
        if (empty($work['title'])) {
            $work['title'] = basename($work['url']);
        }
        $work['download_url'] = api_get_path(WEB_CODE_PATH).'work/download.php?id='.$work['id'].'&'.api_get_cidreq();
        $work['view_url'] = api_get_path(WEB_CODE_PATH).'work/view.php?id='.$work['id'].'&'.api_get_cidreq();
        $work['show_url'] = api_get_path(WEB_CODE_PATH).'work/show_file.php?id='.$work['id'].'&'.api_get_cidreq();
        if ($work['contains_file']) {
            $fileInfo = pathinfo($work['title']);
            if (is_array($fileInfo) &&
                !empty($fileInfo['extension']) &&
                in_array($fileInfo['extension'], array('jpg', 'png', 'gif'))
            ) {
                $work['show_content'] = '<img src="'.$work['show_url'].'"/>';
            }
        }
	}
	return $work;
}

/**
 * @param int $user_id
 * @param int $work_id
 * @return int
 */
function get_work_count_by_student($user_id, $work_id)
{
	$user_id = intval($user_id);
	$work_id = intval($work_id);
	$course_id = api_get_course_int_id();
    $session_id = api_get_session_id();

	$work_table      = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$sql = "SELECT COUNT(*) as count FROM  $work_table
            WHERE
                c_id = $course_id AND
                parent_id = $work_id AND
                user_id = $user_id AND
                active IN (0, 1) AND
                session_id = $session_id ";
	$result = Database::query($sql);
	$return = 0;
	if (Database::num_rows($result)) {
		$return = Database::fetch_row($result,'ASSOC');
		$return = intval($return[0]);
	}
	return $return;
}

/**
 * @param int $id
 * @param int $courseId
 * @return array
 */
function get_work_assignment_by_id($id, $courseId = null)
{
    if (empty($courseId)) {
        $courseId = api_get_course_int_id();
    } else {
        $courseId = intval($courseId);
    }
	$id = intval($id);

	$table = Database :: get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
	$sql = "SELECT * FROM $table WHERE c_id = $courseId AND publication_id = $id";
	$result = Database::query($sql);
	$return = array();
	if (Database::num_rows($result)) {
		$return = Database::fetch_array($result,'ASSOC');
	}
	return $return;
}

/**
 * @param int $id
 * @param array $my_folder_data
 * @param string $add_in_where_query
 * @return array
 */
function getWorkList($id, $my_folder_data, $add_in_where_query = null)
{
    $work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

    $course_id = api_get_course_int_id();
    $session_id = api_get_session_id();
    $condition_session = api_get_session_condition($session_id);
    $group_id = api_get_group_id();
    $is_allowed_to_edit = api_is_allowed_to_edit(null, true);

    $linkInfo = is_resource_in_course_gradebook(api_get_course_id(), 3 , $id, api_get_session_id());

    if ($linkInfo) {
        $workInGradeBookLinkId = $linkInfo['id'];
        if ($workInGradeBookLinkId) {
            if ($is_allowed_to_edit) {
                if (intval($my_folder_data['qualification']) == 0) {
                    Display::display_warning_message(get_lang('MaxWeightNeedToBeProvided'));
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
                    post_group_id = '".$group_id."'
                ORDER BY sent_date DESC";
    } else {
        if (!empty($group_id)) {
            // set to select only messages posted by the user's group
            $group_query = " WHERE c_id = $course_id AND post_group_id = '".$group_id."' ";
            $subdirs_query = "AND parent_id = 0";
        } else {
            $group_query = " WHERE c_id = $course_id AND  post_group_id = '0' ";
            $subdirs_query = "AND parent_id = 0";
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

    $work_parents = array();

    $sql_result = Database::query($sql);
    if (Database::num_rows($sql_result)) {
        while ($work = Database::fetch_object($sql_result)) {
            if ($work->parent_id == 0) {
                $work_parents[] = $work;
            }
        }
    }

    return $work_parents;
}

/**
 * @param int $userId
 * @return array
 */
function getWorkPerUser($userId)
{
    $works = getWorkList(null, null, null);
    $result = array();
    if (!empty($works)) {
        foreach ($works as $workData) {
            $workId = $workData->id;
            $result[$workId]['work'] = $workData;
            $result[$workId]['work']->user_results = get_work_user_list(0, 100, null, null, $workId, null, $userId);
        }
    }
    return $result;
}

/**
 * @param int $workId
 * @param int $groupId
 * @param int $course_id
 * @param int $sessionId
 * @return mixed
 */
function getUniqueStudentAttemptsTotal($workId, $groupId, $course_id, $sessionId)
{
    $work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $user_table = Database::get_main_table(TABLE_MAIN_USER);
    $course_id = intval($course_id);
    $workId = intval($workId);
    $sessionId = intval($sessionId);
    $groupId = intval($groupId);

    $sql = "SELECT count(DISTINCT u.user_id)
            FROM $work_table w
            INNER JOIN $user_table u
                ON w.user_id = u.user_id
            WHERE
                w.c_id = $course_id AND
                w.session_id = $sessionId AND
                w.parent_id = ".$workId." AND
                w.post_group_id = ".$groupId." AND
                w.active IN (0, 1)
            ";

    $res_document = Database::query($sql);
    $rowCount = Database::fetch_row($res_document);

    return $rowCount[0];
}

/**
 * @param mixed $workId
 * @param int $groupId
 * @param int $course_id
 * @param int $sessionId
 * @param int $userId user id to filter
 * @param array $onlyUserList only parse this user list
 * @return mixed
 */
function getUniqueStudentAttempts($workId, $groupId, $course_id, $sessionId, $userId = null, $onlyUserList = array())
{
    $work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $user_table = Database::get_main_table(TABLE_MAIN_USER);

    $course_id = intval($course_id);
    $workCondition = null;
    if (is_array($workId)) {
        $workId = array_map('intval', $workId);
        $workId = implode("','", $workId);
        $workCondition = " w.parent_id IN ('".$workId."') AND";
    } else {
        $workId = intval($workId);
        $workCondition = " w.parent_id = ".$workId." AND";
    }

    $sessionId = intval($sessionId);
    $groupId = intval($groupId);

    $studentCondition = null;

    if (!empty($onlyUserList)) {
        $onlyUserList = array_map('intval', $onlyUserList);
        $studentCondition = "AND u.user_id IN ('".implode("', '", $onlyUserList)."') ";
    } else {
        if (empty($userId)) {
            return 0;
        }
    }

    $sql = "SELECT count(*) FROM (
                SELECT count(*)
                FROM $work_table w
                INNER JOIN $user_table u
                    ON w.user_id = u.user_id
                WHERE
                    w.c_id = $course_id AND
                    w.session_id = $sessionId AND
                   $workCondition
                    w.post_group_id = ".$groupId." AND
                    w.active IN (0, 1) $studentCondition
                ";

    if (!empty($userId)) {
        $userId = intval($userId);
        $sql .= " AND u.user_id = ".$userId;
    }
    $sql .= " GROUP BY u.user_id) as t";

    $res_document = Database::query($sql);
    $rowCount = Database::fetch_row($res_document);

    return $rowCount[0];
}

/**
 * Display the list of student publications, taking into account the user status
 * @deprecated
 * @param $id
 * @param $my_folder_data
 * @param $work_parents
 * @param $origin
 * @param array $userList
 */
function display_student_publications_list($id, $my_folder_data, $work_parents, $origin, $userList = array())
{
	global $gradebook;

	// Database table names
	$work_table      = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$iprop_table     = Database::get_course_table(TABLE_ITEM_PROPERTY);
	$work_assigment  = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);

	$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

	$session_id         = api_get_session_id();
    $condition_session  = api_get_session_condition($session_id);
    $course_id          = api_get_course_int_id();
    $course_info        = api_get_course_info(api_get_course_id());

	$sort_params = array();

	if (isset($_GET['column'])) {
		$sort_params[] = 'column='.Security::remove_XSS($_GET['column']);
	}
	if (isset($_GET['page_nr'])) {
		$sort_params[] = 'page_nr='.Security::remove_XSS($_GET['page_nr']);
	}
	if (isset($_GET['per_page'])) {
		$sort_params[] = 'per_page='.Security::remove_XSS($_GET['per_page']);
	}
	if (isset($_GET['direction'])) {
		$sort_params[] = 'direction='.Security::remove_XSS($_GET['direction']);
	}
	$sort_params    = implode('&amp;', $sort_params);
	$my_params      = $sort_params;
	$origin         = Security::remove_XSS($origin);

    $qualification_exists = false;
    if (!empty($my_folder_data['qualification']) && intval($my_folder_data['qualification']) > 0) {
        $qualification_exists = true;
    }

	$table_header = array();
	$table_has_actions_column = false;
	$table_header[] = array(get_lang('Type'), false, 'style="width:40px"');
	$table_header[] = array(get_lang('Title'), true);

	if (!empty($id)) {
		$table_header[] = array(get_lang('FirstName'), true);
		$table_header[] = array(get_lang('LastName'), true);
	}

    $table_header[] = array(get_lang('HandOutDateLimit'), true, 'style="width:200px"');

	if ($is_allowed_to_edit) {
        $table_header[] = array(get_lang('HandedOut'), false);
		$table_header[] = array(get_lang('Actions'), false, 'style="width:90px"', array('class'=>'td_actions'));
		$table_has_actions_column = true;

        if ($qualification_exists) {
            $table_header[] = array(get_lang('Qualification'), true);
        }

	} else {
        // All users
        if ($course_info['show_score'] == 0) {
            $table_header[] = array(get_lang('Others'), false);
        }
    }

	$table_data = array();

	// List of all folders if no id was provided

    $group_id = api_get_group_id();

	if (is_array($work_parents)) {
		foreach ($work_parents as $work_parent) {
			$sql_select_directory = "SELECT
			        title,
			        url,
			        prop.insert_date,
			        prop.lastedit_date,
			        work.id, author,
			        has_properties,
			        view_properties,
			        description,
			        qualification,
			        weight,
			        allow_text_assignment
                FROM ".$iprop_table." prop INNER JOIN ".$work_table." work ON (prop.ref=work.id AND prop.c_id = $course_id)
                WHERE active IN (0, 1) AND ";

			if (!empty($group_id)) {
				$sql_select_directory .= " work.post_group_id = '".$group_id."' "; // set to select only messages posted by the user's group
			} else {
				$sql_select_directory .= " work.post_group_id = '0' ";
			}
			$sql_select_directory .= " AND ".
			                           "  work.c_id = $course_id AND ".
			                           "  work.id  = ".$work_parent->id." AND ".
			                           "  work.filetype = 'folder' AND ".
			                           "  prop.tool='work' $condition_session";
			$result = Database::query($sql_select_directory);
			$row    = Database::fetch_array($result, 'ASSOC');

			if (!$row) {
				// the folder belongs to another session
				continue;
			}

            // form edit directory
            if (!empty($row['has_properties'])) {
                $sql = Database::query('SELECT * FROM '.$work_assigment.'
                WHERE c_id = '.$course_id.' AND id = "'.$row['has_properties'].'" LIMIT 1');
                $homework = Database::fetch_array($sql);
            }
            // save original value for later
            $utc_expiry_time = $homework['expires_on'];

			$work_data = get_work_data_by_id($work_parent->id);

            $workId = $row['id'];

			$action = '';
			$row = array();
			$class = '';
			$course_id  = api_get_course_int_id();

			if (api_is_allowed_to_edit()) {
                $cant_files = get_count_work($work_data['id']);
			} else {
                $isSubscribed = userIsSubscribedToWork(api_get_user_id(), $work_data['id'], $course_id);
                if ($isSubscribed == false) {
                    continue;
                }
                $cant_files = get_count_work($work_data['id'], api_get_user_id());
			}

			$text_file = get_lang('FilesUpload');

			if ($cant_files == 1) {
				$text_file = api_strtolower(get_lang('FileUpload'));
			}

			$icon = Display::return_icon('work.png', get_lang('Assignment'), array(), ICON_SIZE_SMALL);

            $row[] = '<a href="'.api_get_self().'?'.api_get_cidreq().'&origin='.$origin.'&gradebook='.$gradebook.'">'.$icon.'</a>';

            require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';
            $link_info = is_resource_in_course_gradebook(api_get_course_id(), 3, $workId, api_get_session_id());
            $link_id = $link_info['id'];
            $count  = 0;
            if ($link_info !== false) {
                $gradebook_data = get_resource_from_course_gradebook($link_id);
                $count = $gradebook_data['weight'];
            }
            if ($count > 0) {
                $add_to_name = Display::label(get_lang('IncludedInEvaluation'), 'info');
            } else {
                $add_to_name = '';
            }

            $work_title = !empty($work_data['title']) ? $work_data['title'] : basename($work_data['url']);

            // Work name
            if ($cant_files > 0 ) {
                $zip = '<a href="downloadfolder.inc.php?id='.$work_data['id'].'&'.api_get_cidreq().'">'.
                    Display::return_icon('save_pack.png', get_lang('Save'), array('style' => 'float:right;'), ICON_SIZE_SMALL).'</a>';
            }

            $link = 'work_list.php';
            if (api_is_allowed_to_edit()) {
                $link = 'work_list_all.php';
            }

            $url = $zip.'<a href="'.api_get_path(WEB_CODE_PATH).'work/'.$link.'?'.api_get_cidreq().'&origin='.$origin.'&gradebook='.Security::remove_XSS($_GET['gradebook']).'&id='.$work_data['id'].'"'.$class.'>'.
                    $work_title.'</a> '.$add_to_name.'<br />'.$cant_files.' '.$text_file;
            $row[] = $url;

            if (!empty($homework)) {
                // use original utc value saved previously to avoid doubling the utc-to-local conversion ($homework['expires_on'] might have been tainted)
                $row[] = !empty($utc_expiry_time) && $utc_expiry_time != '0000-00-00 00:00:00' ? api_get_local_time($utc_expiry_time): '-';
            } else {
                $row[] = '-';
            }

            if (!$is_allowed_to_edit) {
                if ($course_info['show_score'] == 0) {
                    $url = api_get_path(WEB_CODE_PATH).'work/work_list_others.php?'.api_get_cidreq().'&id='.$work_parent->id;
                    $row[] = Display::url(Display::return_icon('group.png', get_lang('Others')), $url);
                }
            }

			if ($origin != 'learnpath') {
				if ($is_allowed_to_edit) {
			        $cant_files_per_user = getUniqueStudentAttempts($work_data['id'], $group_id, $course_id, api_get_session_id(), null, $userList);

                    $row[] = $cant_files_per_user.'/'.count($userList);
                    if (api_resource_is_locked_by_gradebook($workId, LINK_STUDENTPUBLICATION)) {
                        $action .= Display::return_icon('edit_na.png', get_lang('Edit'), array(), ICON_SIZE_SMALL);
                        $action .= Display::return_icon('delete_na.png', get_lang('Delete'), array(), ICON_SIZE_SMALL);
                    } else {
                        $action .= '<a href="'.api_get_path(WEB_CODE_PATH).'work/edit_work.php?cidReq='.api_get_course_id().'&origin='.$origin.'&gradebook='.$gradebook.'&id='.$workId.'">'.
                            Display::return_icon('edit.png', get_lang('Modify'), array(), ICON_SIZE_SMALL).'</a>';
                        $action .= ' <a href="'.api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq().'&origin='.$origin.'&gradebook='.$gradebook.'&delete_dir='.$workId.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."'".')) return false;" title="'.get_lang('DirDelete').'"  >'.
                            Display::return_icon('delete.png',get_lang('DirDelete'),'',ICON_SIZE_SMALL).'</a>';
                    }
					$row[] = $action;
				} else {
					$row[] = '';
				}
			}
            $row[] = $work_data['title'];
			$table_data[] = $row;
		}
	}

	$sorting_options = array();
	$sorting_options['column'] = 1;

	// Here we change the way how the columns are going to be sorted
	// in this case the the column of LastResent ( 4th element in $column_header) we will be order like the column RealDate
	// because in the column RealDate we have the days in a correct format "2008-03-12 10:35:48"

	$column_order = array();
	$i=0;
	foreach ($table_header as $item) {
		$column_order[$i] = $i;
		$i++;
	}
    if (empty($my_folder_data)) {
		$column_order[1] = 5;
	} else {
		$column_order[2] = 2;
	}

	// An array with the setting of the columns -> 1: columns that we will show, 0:columns that will be hide
	$column_show = array();

	$column_show[] = 1; // type 0
	$column_show[] = 1; // title 1

	if (!empty($my_folder_data)) {
		$column_show[] = 1;  // 2
		$column_show[] = 1;  // 3
		if ($qualification_exists) {
			$column_show[] = 1;  // 4
		}
	}
	$column_show[] = 1; //date
	if ($table_has_actions_column) {
		$column_show[] = 1; // modify
	}
	$column_show[] = 1; //real date in correct format
    $column_show[] = 0; //real date in correct format

	$paging_options = array();
	if (isset($_GET['curdirpath'])) {
		$my_params = array ('curdirpath' => Security::remove_XSS($_GET['curdirpath']));
	}

	$my_params = array ('id' => isset($_GET['id']) ? $_GET['id'] : null);

	if (isset($_GET['edit_dir'])) {
		$my_params = array ('edit_dir' => intval($_GET['edit_dir']));
	}
	$my_params['origin'] = $origin;
	Display::display_sortable_config_table('work', $table_header, $table_data, $sorting_options, $paging_options, $my_params, $column_show, $column_order);
}

/**
 * Shows the work list (student view)
 * @return string
 */
function showStudentWorkGrid()
{
    $courseInfo = api_get_course_info();
    $columnModel = array(
        array('name'=>'type', 'index'=>'type', 'width'=>'30',   'align'=>'left', 'sortable' => 'false'),
        array('name'=>'title', 'index'=>'title', 'width'=>'80',   'align'=>'left'),
        array('name'=>'expires_on', 'index'=>'expires_on', 'width'=>'500',  'align'=>'left', 'sortable'=>'false')
    );

    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_work_student&'.api_get_cidreq();

    $columns = array(
        get_lang('Type'),
        get_lang('Title'),
        get_lang('HandOutDateLimit')
    );

    if ($courseInfo['show_score'] == 0) {
        $columnModel[] = array(
            'name' => 'others',
            'index' => 'others',
            'width' => '80',
            'align' => 'left',
            'sortable' => 'false'
        );
        $columns[] = get_lang('Others');
    }

    $params = array(
        'autowidth' => 'true',
        'height' => 'auto'
    );

    $html = '<script>
    $(function() {
        '.Display::grid_js('workList', $url, $columns, $columnModel, $params, array(), null, true).'
    });
    </script>';

    $html .= Display::grid_html('workList');
    return $html;
}

/**
 * Shows the work list (teacher view)
 * @return string
 */
function showTeacherWorkGrid()
{
    $columnModel = array(
        //array('name'=>'id', 'index'=>'id', 'width'=>'50', 'align'=>'left', 'hidden' => 'true'),
        array('name'=>'type', 'index'=>'type', 'width'=>'35', 'align'=>'left', 'sortable' => 'false'),
        array('name'=>'title', 'index'=>'title',  'width'=>'300',   'align'=>'left', 'wrap_cell' => "true"),
        array('name'=>'sent_date', 'index'=>'sent_date', 'width'=>'125',  'align'=>'left'),
        array('name'=>'expires_on', 'index'=>'expires_on', 'width'=>'125',  'align'=>'left'),
        array('name'=>'amount', 'index'=>'end_on', 'width'=>'110',  'align'=>'left'),
        array('name'=>'actions', 'index'=>'actions', 'width'=>'110', 'align'=>'left', 'sortable'=>'false')
    );
    $token = null;

    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_work_teacher&'.api_get_cidreq();
    $deleteUrl = api_get_path(WEB_AJAX_PATH).'work.ajax.php?a=delete_work&'.api_get_cidreq();

    $columns = array(

        //get_lang('id'),
        get_lang('Type'),
        get_lang('Title'),
        get_lang('SentDate'),
        get_lang('HandOutDateLimit'),
        get_lang('AmountSubmitted'),
        get_lang('Actions')
    );

    $params = array(
        'multiselect' => true,
        'autowidth' => 'true',
        'height' => 'auto',
        //'beforeSelectRow' => 'function(rowid, e) { e.stopPropagation(); }'
    );

    $html = '<script>
    $(function() {
        '.Display::grid_js('workList', $url, $columns, $columnModel, $params, array(), null, true).'
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
 * Builds the form thats enables the user to
 * select a directory to browse/upload in
 * This function has been copied from the document/document.inc.php library
 *
 * @param array $folders
 * @param string $curdirpath
 * @param string $group_dir
 * @return string html form
 */
// TODO: This function is a candidate for removal, it is not used anywhere.
function build_work_directory_selector($folders, $curdirpath, $group_dir = '')
{
	$form = '<form name="selector" action="'.api_get_self().'?'.api_get_cidreq().'" method="POST">';
	$form .= get_lang('CurrentDirectory').' <select name="curdirpath" onchange="javascript: document.selector.submit();">';
	//group documents cannot be uploaded in the root
	if ($group_dir == '') {
		$form .= '<option value="/">/ ('.get_lang('Root').')</option>';
		if (is_array($folders)) {
			foreach ($folders as $folder) {
				$selected = ($curdirpath == $folder) ? ' selected="selected"' : '';
				$form .= '<option'.$selected.' value="'.$folder.'">'.$folder.'</option>'."\n";
			}
		}
	} else {
		foreach ($folders as $folder) {
			$selected = ($curdirpath == $folder) ? ' selected="selected"' : '';
			$display_folder = substr($folder, strlen($group_dir));
			$display_folder = ($display_folder == '') ? '/ ('.get_lang('Root').')' : $display_folder;
			$form .= '<option'.$selected.' value="'.$folder.'">'.$display_folder.'</option>'."\n";
		}
	}

	$form .= '</select>';
	$form .= '<noscript><input type="submit" name="change_path" value="'.get_lang('Ok').'" /></noscript>';
	$form .= '</form>';

	return $form;
}

/**
 * Builds the form thats enables the user to
 * move a document from one directory to another
 * This function has been copied from the document/document.inc.php library
 *
 * @param array $folders
 * @param string $curdirpath
 * @param string $move_file
 * @param string $group_dir
 * @return string html form
 */
function build_work_move_to_selector($folders, $curdirpath, $move_file, $group_dir = '')
{
    $course_id = api_get_course_int_id();
	$move_file	= intval($move_file);
	$tbl_work	= Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$sql 		= "SELECT title, url FROM $tbl_work WHERE c_id = $course_id AND id ='".$move_file."'";
	$result 	= Database::query($sql);
	$row = Database::fetch_array($result, 'ASSOC');
    $title = empty($row['title']) ? basename($row['url']) : $row['title'];

	global $gradebook;
    //@todo use formvalidator please!
	$form = '<form class="form-horizontal" name="move_to_form" action="'.api_get_self().'?'.api_get_cidreq().'&gradebook='.$gradebook.'&curdirpath='.Security::remove_XSS($curdirpath).'" method="POST">';
	$form .= '<legend>'.get_lang('MoveFile').' - '.Security::remove_XSS($title).'</legend>';
	$form .= '<input type="hidden" name="item_id" value="'.$move_file.'" />';
	$form .= '<input type="hidden" name="action" value="move_to" />';
	$form .= '<div class="control-group">
				<label>
					<span class="form_required">*</span>'.get_lang('Select').'
				</label>
				<div class="controls">';
	$form .= ' <select name="move_to_id">';

	//group documents cannot be uploaded in the root
	if ($group_dir == '') {
		if ($curdirpath != '/') {
			//$form .= '<option value="0">/ ('.get_lang('Root').')</option>';
		}
		if (is_array($folders)) {
			foreach ($folders as $fid => $folder) {
				//you cannot move a file to:
				//1. current directory
				//2. inside the folder you want to move
				//3. inside a subfolder of the folder you want to move
				if (($curdirpath != $folder) && ($folder != $move_file) && (substr($folder, 0, strlen($move_file) + 1) != $move_file.'/')) {
					$form .= '<option value="'.$fid.'">'.$folder.'</option>';
				}
			}
		}
	} else {
		if ($curdirpath != '/') {
			$form .= '<option value="0">/ ('.get_lang('Root').')</option>';
		}
		foreach ($folders as $fid => $folder) {
			if (($curdirpath != $folder) && ($folder != $move_file) && (substr($folder, 0, strlen($move_file) + 1) != $move_file.'/')) {
				//cannot copy dir into his own subdir
				$display_folder = substr($folder, strlen($group_dir));
				$display_folder = ($display_folder == '') ? '/ ('.get_lang('Root').')' : $display_folder;
				$form .= '<option value="'.$fid.'">'.$display_folder.'</option>'."\n";
			}
		}
	}

	$form .= '</select>';
	$form .= '	</div>
			</div>';
	$form .= '<div class="control-group">
					<div class="controls">
						<button type="submit" class="save" name="move_file_submit">'.get_lang('MoveFile').'</button>
					</div>
				</div>';
	$form .= '</form>';
	$form .= '<div style="clear: both; margin-bottom: 10px;"></div>';
	return $form;
}

/**
 * Checks if the first given directory exists as a subdir of the second given directory
 * This function should now be deprecated by Security::check_abs_path()
 * @param	string	Subdir
 * @param	string	Base dir
 * @return	integer	-1 on error, 0 if not subdir, 1 if subdir
 */
// TODO: This function is a candidate for removal, it is not used anywhere.
function is_subdir_of($subdir, $basedir)
{
	if (empty($subdir) or empty($basedir)) {
		return -1;
	}
	if (substr($basedir, -1, 1) != '/') {
		$basedir = $basedir.'/';
	}
	if (substr($subdir, 0, 1) == '/') {
		$subdir = substr($subdir, 1);
	}
	return is_dir($basedir.$subdir) ? 1 : 0;
}

/**
 * creates a new directory trying to find a directory name
 * that doesn't already exist
 * (we could use unique_name() here...)
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @author Bert Vanderkimpen
 * @author Yannick Warnier <ywarnier@beeznest.org> Adaptation for work tool
 * @param	string	Base work dir (.../work)
 * @param 	string $desiredDirName complete path of the desired name
 * @return 	string actual directory name if it succeeds, boolean false otherwise
 */
function create_unexisting_work_directory($base_work_dir, $desired_dir_name)
{
	$nb = '';
	$base_work_dir = (substr($base_work_dir, -1, 1) == '/' ? $base_work_dir : $base_work_dir.'/');
	while (file_exists($base_work_dir.$desired_dir_name.$nb)) {
		$nb += 1;
	}
	if (@mkdir($base_work_dir.$desired_dir_name.$nb, api_get_permissions_for_new_directories())) {
		return $desired_dir_name.$nb;
	} else {
		return false;
	}
}

/**
 * Delete a work-tool directory
 * @param	string	Base "work" directory for this course as /var/www/chamilo/courses/ABCD/work/
 * @param	string	The directory name as the bit after "work/", without trailing slash
 * @return	integer	-1 on error
 */
function deleteDirWork($id)
{
    $locked = api_resource_is_locked_by_gradebook($id, LINK_STUDENTPUBLICATION);

    if ($locked == true) {
        Display::display_warning_message(get_lang('ResourceLockedByGradebook'));
        return false;
    }

    $_course = api_get_course_info();
    $id = intval($id);
    $work_data = get_work_data_by_id($id);

    if (empty($work_data)) {
        return false;
    }

    $base_work_dir = api_get_path(SYS_COURSE_PATH) .$_course['path'].'/work';
    $work_data_url = $base_work_dir.$work_data['url'];
	$check = Security::check_abs_path($work_data_url.'/', $base_work_dir.'/');

	$table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $TSTDPUBASG = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
    $t_agenda   = Database::get_course_table(TABLE_AGENDA);

	$course_id = api_get_course_int_id();

	if (!empty($work_data['url'])) {
        if ($check) {
            // Deleting all contents inside the folder
            $sql = "UPDATE $table SET active = 2 WHERE c_id = $course_id AND filetype = 'folder' AND id = $id";
            Database::query($sql);

            $sql = "UPDATE $table SET active = 2 WHERE c_id = $course_id AND parent_id = $id";
            Database::query($sql);

            require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
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
                $sql = "DELETE FROM $t_agenda WHERE c_id = $course_id AND id = '".$calendar_id[0]."'";
                Database::query($sql);
            }
            $sql = "DELETE FROM $TSTDPUBASG WHERE c_id = $course_id AND publication_id = $id";
            Database::query($sql);

            $link_info = is_resource_in_course_gradebook(api_get_course_id(), 3, $id, api_get_session_id());
            $link_id = $link_info['id'];
            if ($link_info !== false) {
                remove_resource_from_course_gradebook($link_id);
            }
            return true;
        }
	}
}

/**
 * Get the path of a document in the student_publication table (path relative to the course directory)
 * @param	integer	Element ID
 * @return	string	Path (or -1 on error)
 */
function get_work_path($id)
{
	$table 		= Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$course_id 	= api_get_course_int_id();
	$sql 		= 'SELECT url FROM '.$table.' WHERE c_id = '.$course_id.' AND id='.intval($id);
	$res 		= Database::query($sql);
	if (Database::num_rows($res)) {
		$row = Database::fetch_array($res);
		return $row['url'];
	}
	return -1;
}

/**
 * Update the url of a work in the student_publication table
 * @param	integer	ID of the work to update
 * @param	string	Destination directory where the work has been moved (must end with a '/')
 * @return	-1 on error, sql query result on success
 */
function updateWorkUrl($id, $new_path, $parent_id)
{
	if (empty($id)) return -1;
	$table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$course_id = api_get_course_int_id();
    $id = intval($id);
    $parent_id = intval($parent_id);

	$sql = "SELECT * FROM $table WHERE c_id = $course_id AND id = $id";
	$res = Database::query($sql);
	if (Database::num_rows($res) != 1) {
		return -1;
	} else {
		$row = Database::fetch_array($res);
		$filename = basename($row['url']);
		$new_url = $new_path.$filename;
        $new_url = Database::escape_string($new_url);
        $sql2 = "UPDATE $table SET url = '$new_url', parent_id = '$parent_id' WHERE c_id = $course_id AND id = $id";
		$res2 = Database::query($sql2);
		return $res2;
	}
}

/**
 * Update the url of a dir in the student_publication table
 * @param   array work original data
 * @param   string new path
 * @return bool
 */
function updateDirName($work_data, $newPath)
{
	$course_id = $work_data['c_id'];
    $courseInfo = api_get_course_info_by_id($course_id);

	$work_id = intval($work_data['id']);
    $oldPath = $work_data['url'];
    $path  = $work_data['url'];
    $originalNewPath = Database::escape_string($newPath);
    $newPath = Database::escape_string($newPath);
    $newPath = replace_dangerous_char($newPath);
    $newPath = disable_dangerous_file($newPath);

    if ($oldPath == '/'.$newPath) {
        return true;
    }

	if (!empty($newPath)) {
        $base_work_dir = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/work';
		my_rename($base_work_dir.$oldPath, $newPath);
		$table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

		//update all the files in the other directories according with the next query
		$sql = "SELECT id, url FROM $table WHERE c_id = $course_id AND parent_id = $work_id";
		$result = Database::query($sql);
        $work_len = strlen('work/'.$path);

		while ($work = Database :: fetch_array($result)) {
			$new_dir = $work['url'];
			$name_with_directory = substr($new_dir, $work_len, strlen($new_dir));
            $name = Database::escape_string('work/'.$newPath.'/'.$name_with_directory);
			$sql = 'UPDATE '.$table.' SET url= "'.$name.'" WHERE c_id = '.$course_id.' AND id = '.$work['id'];
			Database::query($sql);
		}

        $sql = "UPDATE $table SET url= '/".$newPath."', title = '".$originalNewPath."'
                WHERE c_id = $course_id AND id = $work_id";
        Database::query($sql);
	}
}

/**
 * Return an array with all the folder's ids that are in the given path
 * @param	string Path of the directory
 * @return	array The list of ids of all the directories in the path
 * @author 	Julio Montoya Dokeos
 * @version April 2008
 */

function get_parent_directories($id)
{
	$course_id = api_get_course_int_id();
	$work_table      = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $id = intval($id);
    $sql = "SELECT id FROM $work_table WHERE c_id = $course_id AND parent_id = $id";
	$result = Database::query($sql);
    $list_id = array();
    if (Database::num_rows($result)) {
	   while ($row = Database::fetch_array($result)) {
		  $list_id[] = $row['id'];
	   }
    }
	return $list_id;
}

/**
 * Transform an all directory structure (only directories) in an array
 * @param	string path of the directory
 * @return	array the directory structure into an array
 * @author 	Julio Montoya Dokeos
 * @version April 2008
 */
function directory_to_array($directory)
{
	$array_items = array();
	if ($handle = @opendir($directory)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != '.' && $file != '..') {
				if (is_dir($directory. '/' . $file)) {
					$array_items = array_merge($array_items, directory_to_array($directory. '/' . $file));
					$file = $directory . '/' . $file;
					$array_items[] = preg_replace("/\/\//si", '/', $file);
				}
			}
		}
		closedir($handle);
	}
	return $array_items;
}

/**
 * Insert into the DB of the course all the directories
 * @param	string path of the /work directory of the course
 * @return	-1 on error, sql query result on success
 * @author 	Julio Montoya
 * @version April 2008
 * @param string $base_work_dir
 */

function insert_all_directory_in_course_table($base_work_dir)
{
	$dir_to_array = directory_to_array($base_work_dir, true);
	$only_dir = array();

	for ($i = 0; $i < count($dir_to_array); $i++) {
		$only_dir[] = substr($dir_to_array[$i], strlen($base_work_dir), strlen($dir_to_array[$i]));
	}
	$course_id = api_get_course_int_id();
    $group_id  = api_get_group_id();

	for($i = 0; $i < count($only_dir); $i++) {
		global $work_table;
        $url = Database::escape_string($only_dir[$i]);
		$sql = "INSERT INTO " . $work_table . " SET
               c_id 		= '$course_id',
               url 			= '".$url."',
               title        = '',
               description 	= '',
               author      	= '',
               active		= '1',
               accepted		= '1',
               filetype		= 'folder',
               post_group_id = '".$group_id."',
               sent_date	= '0000-00-00 00:00:00' ";
		Database::query($sql);
	}
}

/**
 * This function displays the number of files contained in a directory
 *
 * @param	string the path of the directory
 * @param	boolean true if we want the total quantity of files include in others child directorys , false only  files in the directory
 * @return	array the first element is an integer with the number of files in the folder, the second element is the number of directories
 * @author 	Julio Montoya
 * @version	April 2008
 */
function count_dir($path_dir, $recurse)
{
	$count = 0;
	$count_dir = 0;
	$d = dir($path_dir);
	while ($entry = $d->Read()) {
		if (!(($entry == '..') || ($entry == '.'))) {
			if (is_dir($path_dir.'/'.$entry)) {
				$count_dir++;
				if ($recurse) {
					$count += count_dir($path_dir . '/' . $entry, $recurse);
				}
			} else {
				$count++;
			}
		}
	}
	$return_array = array();
	$return_array[] = $count;
	$return_array[] = $count_dir;
	return $return_array;
}

/**
 * returns all the javascript that is required for easily
 * validation when you create a work
 * this goes into the $htmlHeadXtra[] array
 */
function to_javascript_work()
{
    $origin = isset($_REQUEST['origin']) && !empty($_REQUEST['origin']) ? api_get_tools_lists($_REQUEST['origin']) : '';

	$js = '<script>
			function plus() {
				if(document.getElementById(\'options\').style.display == \'none\') {
					document.getElementById(\'options\').style.display = \'block\';
					document.getElementById(\'plus\').innerHTML=\'&nbsp;'.Display::return_icon('div_hide.gif', get_lang('Hide', ''), array('style' => 'vertical-align:middle')).'&nbsp;'.addslashes(get_lang('AdvancedParameters', '')).'\';
				} else {
					document.getElementById(\'options\').style.display = \'none\';
					document.getElementById(\'plus\').innerHTML=\'&nbsp;'.Display::return_icon('div_show.gif', get_lang('Show', ''), array('style' => 'vertical-align:middle')).'&nbsp;'.addslashes(get_lang('AdvancedParameters', '')).'\';
				}
			}

			function updateDocumentTitle(value) {
				var temp = value.indexOf("/");
				//linux path
				if(temp!=-1){
					var temp=value.split("/");
				} else {
					var temp=value.split("\\\");
				}
				document.getElementById("file_upload").value=temp[temp.length-1];
				$("#contains_file_id").attr("value", 1);
			}

			function checkDate(month, day, year) {
			  var monthLength =
			    new Array(31,28,31,30,31,30,31,31,30,31,30,31);

			  if (!day || !month || !year)
			    return false;

			  // check for bisestile year
			  if (year/4 == parseInt(year/4))
			    monthLength[1] = 29;

			  if (month < 1 || month > 12)
			    return false;

			  if (day > monthLength[month-1])
			    return false;

			  return true;
			}

			function mktime() {

			    var no, ma = 0, mb = 0, i = 0, d = new Date(), argv = arguments, argc = argv.length;
			    d.setHours(0,0,0); d.setDate(1); d.setMonth(1); d.setYear(1972);

			    var dateManip = {
			        0: function(tt){ return d.setHours(tt); },
			        1: function(tt){ return d.setMinutes(tt); },
			        2: function(tt){ set = d.setSeconds(tt); mb = d.getDate() - 1; return set; },
			        3: function(tt){ set = d.setMonth(parseInt(tt)-1); ma = d.getFullYear() - 1972; return set; },
			        4: function(tt){ return d.setDate(tt+mb); },
			        5: function(tt){ return d.setYear(tt+ma); }
			    };

			    for( i = 0; i < argc; i++ ){
			        no = parseInt(argv[i]*1);
			        if (isNaN(no)) {
			            return false;
			        } else {
			            // arg is number, lets manipulate date object
			            if(!dateManip[i](no)){
			                // failed
			                return false;
			            }
			        }
			    }
			    return Math.floor(d.getTime()/1000);
			}

            function setFocus() {
                $("#work_title").focus();
            }

            $(document).ready(function () {
                setFocus();

                $("#expiry_date").click(function() {
                    var checked = $("#expiry_date").attr("checked");
                    if (checked) {
                        $("#option2").show();
                        $("#option3").show();
                        $("#end_date").attr("checked", true);
                    } else {
                        $("#option2").hide();
                        $("#option3").hide();
                        $("#end_date").attr("checked", false);
                    }
                });

                $("#end_date").click(function() {
                    $("#option3").toggle();
                });

            });
			</script>';
    return $js;
}

/**
 * Gets the id of a student publication with a given path
 * @param string $path
 * @return true if is found / false if not found
 */
// TODO: The name of this function does not fit with the kind of information it returns. Maybe check_work_id() or is_work_id()?
function get_work_id($path)
{
	$TBL_STUDENT_PUBLICATION = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$TBL_PROP_TABLE = Database::get_course_table(TABLE_ITEM_PROPERTY);
	$course_id = api_get_course_int_id();
    $path = Database::escape_string($path);

	if (api_is_allowed_to_edit()) {
		$sql = "SELECT work.id FROM $TBL_STUDENT_PUBLICATION AS work, $TBL_PROP_TABLE AS props
				WHERE
				    props.c_id = $course_id AND
				    work.c_id = $course_id AND
				    props.tool='work' AND
				    work.id=props.ref AND
				    work.url LIKE 'work/".$path."%' AND
				    work.filetype='file' AND
				    props.visibility<>'2'";
	} else {
		$sql = "SELECT work.id FROM $TBL_STUDENT_PUBLICATION AS work,$TBL_PROP_TABLE AS props
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
 * @param int $notMeUserId show works from everyone except me
 * @return int
 */
function get_count_work($work_id, $onlyMeUserId = null, $notMeUserId = null)
{
    $work_table 	 = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $iprop_table     = Database::get_course_table(TABLE_ITEM_PROPERTY);
    $user_table      = Database::get_main_table(TABLE_MAIN_USER);

    $is_allowed_to_edit = api_is_allowed_to_edit(null, true);
    $session_id = api_get_session_id();
    $condition_session  = api_get_session_condition($session_id);

    $course_id      = api_get_course_int_id();
    $group_id       = api_get_group_id();
    $course_info    = api_get_course_info(api_get_course_id());
    $work_id       = intval($work_id);

    if (!empty($group_id)) {
        $extra_conditions = " work.post_group_id = '".intval($group_id)."' "; // set to select only messages posted by the user's group
    } else {
        $extra_conditions = " work.post_group_id = '0' ";
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
            INNER JOIN $user_table u ON (work.user_id = u.user_id)
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
 * @param int $start
 * @param int $limit
 * @param string $column
 * @param string $direction
 * @param string $where_condition
 * @param bool $getCount
 * @return array
 */
function getWorkListStudent($start, $limit, $column, $direction, $where_condition, $getCount = false)
{
    $workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $workTableAssignment = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);

    $course_id          = api_get_course_int_id();
    $session_id         = api_get_session_id();
    $condition_session  = api_get_session_condition($session_id);
    $group_id           = api_get_group_id();

    if (!in_array($direction, array('asc','desc'))) {
        $direction = 'desc';
    }

    $column = !empty($column) ? Database::escape_string($column) : 'sent_date';
    $start = intval($start);
    $limit = intval($limit);

    // Get list from database

    if (!empty($group_id)) {
        $group_query = " WHERE w.c_id = $course_id AND post_group_id = '".$group_id."' ";
        $subdirs_query = "AND parent_id = 0";
    } else {
        $group_query = " WHERE w.c_id = $course_id AND post_group_id = '0' ";
        $subdirs_query = "AND parent_id = 0";
    }

    $active_condition = ' AND active IN (1, 0)';

    if ($getCount) {
        $select = "SELECT count(w.id) as count ";
    } else {
        $select = "SELECT w.*, a.expires_on, expires_on, ends_on, enable_qualification ";
    }

    $sql = "$select
            FROM $workTable w
            LEFT JOIN $workTableAssignment a ON (a.publication_id = w.id AND a.c_id = w.c_id)
                $group_query
                $subdirs_query
                $active_condition
                $condition_session
                $where_condition
            ";

    $sql .= " ORDER BY $column $direction ";

    if (!empty($start) && !empty($limit)) {
        $sql .= " LIMIT $start, $limit";
    }

    $result = Database::query($sql);

    if ($getCount) {
        $row = Database::fetch_array($result);
        return $row['count'];
    }

    $works = array();
    $url = api_get_path(WEB_CODE_PATH).'work/work_list.php?'.api_get_cidreq();
    $urlOthers = api_get_path(WEB_CODE_PATH).'work/work_list_others.php?'.api_get_cidreq().'&id=';
    while ($work = Database::fetch_array($result, 'ASSOC')) {
        $isSubscribed = userIsSubscribedToWork(api_get_user_id(), $work['id'], $course_id);
        if ($isSubscribed == false) {
            continue;
        }
        $work['type'] = Display::return_icon('work.png');
        $work['expires_on'] = $work['expires_on']  == '0000-00-00 00:00:00' ? null : api_get_local_time($work['expires_on']);
        if (empty($work['title'])) {
            $work['title'] = basename($work['url']);
        }
        $work['title'] = Display::url($work['title'], $url.'&id='.$work['id']);
        $work['others'] = Display::url(Display::return_icon('group.png', get_lang('Others')), $urlOthers.$work['id']);
        $works[] = $work;
    }
    return $works;

}

/**
 * @param int $start
 * @param int $limit
 * @param string $column
 * @param string $direction
 * @param string $where_condition
 * @param bool $getCount
 * @return array
 */
function getWorkListTeacher($start, $limit, $column, $direction, $where_condition, $getCount = false)
{
    $workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $workTableAssignment  = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);

    $course_id          = api_get_course_int_id();
    $session_id         = api_get_session_id();
    $condition_session  = api_get_session_condition($session_id);
    $group_id           = api_get_group_id();
    $is_allowed_to_edit = api_is_allowed_to_edit(null, true);

    if (!in_array($direction, array('asc','desc'))) {
        $direction = 'desc';
    }

    $column = !empty($column) ? Database::escape_string($column) : 'sent_date';
    $start = intval($start);
    $limit = intval($limit);

    // Get list from database
    if ($is_allowed_to_edit) {
        $active_condition = ' active IN (0, 1)';
        if ($getCount) {
            $select = " SELECT count(w.id) as count";
        } else {
            $select = " SELECT w.*, a.expires_on, expires_on, ends_on, enable_qualification ";
        }
        $sql = " $select
                FROM $workTable w
                LEFT JOIN $workTableAssignment a ON (a.publication_id = w.id AND a.c_id = w.c_id)
                WHERE
                    w.c_id = $course_id
                    $condition_session AND
                    $active_condition AND
                    (parent_id = 0) AND
                    post_group_id = '".$group_id."'
                    $where_condition
                ORDER BY $column $direction
                LIMIT $start, $limit";
        $result = Database::query($sql);

        if ($getCount) {
            $row = Database::fetch_array($result);
            return $row['count'];
        }
        $works = array();
        $url = api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq();
        while ($work = Database::fetch_array($result, 'ASSOC')) {
            $workId = $work['id'];
            $work['type'] = Display::return_icon('work.png');
            $work['expires_on'] = $work['expires_on']  == '0000-00-00 00:00:00' ? null : api_get_local_time($work['expires_on']);
            //$work['ends_on'] = $work['ends_on']  == '0000-00-00 00:00:00' ? null : api_get_local_time($work['ends_on']);

            $totalUsers = getStudentSubscribedToWork(
                $workId,
                $course_id,
                $group_id,
                $session_id,
                true
            );

            $countUniqueAttempts = getUniqueStudentAttemptsTotal(
                $workId,
                $group_id,
                $course_id,
                $session_id
            );

            $work['amount'] = Display::label(
                $countUniqueAttempts . '/' .
                $totalUsers,
                'success'
            );

            if (empty($work['title'])) {
                $work['title'] = basename($work['url']);
            }
            $work['title'] = Display::url($work['title'], $url.'&id='.$workId);
            $work['title'] .= ' '.Display::label(get_count_work($work['id']), 'success');
            $work['sent_date'] = api_get_local_time($work['sent_date']);

            $editLink = Display::url(
                Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL),
                api_get_path(WEB_CODE_PATH).'work/edit_work.php?id='.$workId.'&'.api_get_cidreq()
            );

            if ($countUniqueAttempts > 0) {
                $downloadLink = Display::url(
                    Display::return_icon(
                        'save_pack.png',
                        get_lang('Save'),
                        array(),
                        ICON_SIZE_SMALL
                    ),
                    api_get_path(
                        WEB_CODE_PATH
                    ) . 'work/downloadfolder.inc.php?id=' . $workId . '&' . api_get_cidreq(
                    )
                );
            } else {
                $downloadLink = Display::url(
                    Display::return_icon(
                        'save_pack_na.png',
                        get_lang('Save'),
                        array(),
                        ICON_SIZE_SMALL
                    ),
                    '#'
                );
            }
            $deleteUrl = api_get_path(WEB_CODE_PATH).'work/work.php?id='.$workId.'&action=delete_dir&'.api_get_cidreq();
            $deleteLink = '<a href="#" onclick="showConfirmationPopup(this, \''.$deleteUrl.'\' ) " >'.
                Display::return_icon('delete.png', get_lang('Delete'), array(), ICON_SIZE_SMALL).'</a>';

            $work['actions'] = $downloadLink.$editLink.$deleteLink;
            $works[] = $work;
        }
    }
    return $works;
}

/**
 * @param int $start
 * @param int $limit
 * @param string $column
 * @param string $direction
 * @param int $workId
 * @param int $studentId
 * @param string $whereCondition
 * @param bool $getCount
 * @return array
 */
function get_work_user_list_from_documents(
    $start,
    $limit,
    $column,
    $direction,
    $workId,
    $studentId = null,
    $whereCondition,
    $getCount = false
) {

    if (ADD_DOCUMENT_TO_WORK == false) {
        return array();
    }

    if ($getCount) {
        $select1 = " SELECT count(u.user_id) as count ";
        $select2 = " SELECT count(u.user_id) as count ";
    } else {
        $select1 = " SELECT DISTINCT u.firstname, u.lastname, u.user_id, w.title, w.parent_id, w.document_id document_id, w.id, qualification, qualificator_id";
        $select2 = " SELECT DISTINCT u.firstname, u.lastname, u.user_id, d.title, w.parent_id, d.id document_id, 0, 0, 0";
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
    $studentId = intval($studentId);
    $workId = intval($workId);

    $userCondition = " AND u.user_id = $studentId ";
    $sessionCondition = " AND w.session_id = $sessionId ";
    $workCondition = " AND w_rel.work_id = $workId";
    $workParentCondition  = " AND w.parent_id = $workId";

    $sql = "(
                $select1 FROM $userTable u
                INNER JOIN $workTable w ON (u.user_id = w.user_id AND w.active IN (0, 1) AND w.filetype = 'file')
                WHERE
                    w.c_id = $courseId
                    $userCondition
                    $sessionCondition
                    $whereCondition
                    $workParentCondition
            ) UNION (
                $select2 FROM $workTable w
                INNER JOIN $workRelDocument w_rel ON (w_rel.work_id = w.id AND w.active IN (0, 1) AND w_rel.c_id = w.c_id)
                INNER JOIN $documentTable d ON (w_rel.document_id = d.id AND d.c_id = w.c_id)
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

    $start = intval($start);
    $limit = intval($limit);

    $direction = in_array(strtolower($direction), array('desc', 'asc')) ? $direction : 'desc';
    $column = Database::escape_string($column);

    if ($getCount) {
        $result = Database::query($sql);
        $result = Database::fetch_array($result);
        return $result['count'];
    }

    $sql .= " ORDER BY $column $direction";
    $sql .= " LIMIT $start, $limit";

    $result = Database::query($sql);

    $currentUserId = api_get_user_id();
    $work_data = get_work_data_by_id($workId);

    $qualificationExists = false;

    if (!empty($work_data['qualification']) && intval($work_data['qualification']) > 0) {
        $qualificationExists = true;
    }

    $workList = array();

    $urlAdd = api_get_path(WEB_CODE_PATH).'work/upload_from_template.php?'.api_get_cidreq();
    $urlEdit = api_get_path(WEB_CODE_PATH).'work/edit.php?'.api_get_cidreq();
    $urlDelete = api_get_path(WEB_CODE_PATH).'work/work_list.php?action=delete&'.api_get_cidreq();
    $urlView = api_get_path(WEB_CODE_PATH).'work/view.php?'.api_get_cidreq();

    $editIcon = Display::return_icon('edit.png', get_lang('Edit'));
    $addIcon = Display::return_icon('add.png', get_lang('Add'));
    $deleteIcon = Display::return_icon('delete.png', get_lang('Delete'));
    $viewIcon = Display::return_icon('default.png', get_lang('View'));

    $allowEdition = api_get_course_setting('student_delete_own_publication');
    while ($row = Database::fetch_array($result, 'ASSOC')) {
        $userId = $row['user_id'];
        $documentId = $row['document_id'];
        $itemId = $row['id'];
        $addLinkShowed = false;

        if (empty($documentId)) {
            $url = $urlEdit.'&item_id='.$row['id'].'&id='.$workId;
            $editLink = Display::url($editIcon, $url);
            if ($allowEdition == false) {
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

                if ($allowEdition == false) {
                    $editLink = null;
                }
            }
        }

        if ($allowEdition && !empty($itemId)) {
            $deleteLink  = Display::url($deleteIcon, $urlDelete.'&item_id='.$itemId.'&id='.$workId);
        } else {
            $deleteLink = null;
        }

        $viewLink = null;

        if (!empty($itemId)) {
            $viewLink = Display::url($viewIcon, $urlView.'&id='.$itemId);
        }

        //$row['type'] = build_document_icon_tag('file', $row['url']);
        $row['type'] = null;

        if ($qualificationExists) {
            if (empty($row['qualificator_id'])) {
                $status = Display::label(get_lang('NotRevised'), 'warning');
            } else {
                $status = Display::label(get_lang('Revised'), 'success');
            }
            $row['qualificator_id'] = $status;
        }

        if (!empty($row['qualification'])) {
            $row['qualification'] = Display::label($row['qualification'], 'info');
        }

        if (!empty($row['sent_date'])) {
            $row['sent_date'] = api_get_local_time($row['sent_date']);
        }

        if ($userId == $currentUserId) {
            $row['actions'] = $viewLink.$editLink.$deleteLink;
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
 * @param int $start
 * @param int $limit
 * @param int $column
 * @param string $direction
 * @param int $work_id
 * @param array $where_condition
 * @param int $studentId
 * @param bool $getCount
 * @return array
 */
function get_work_user_list(
    $start,
    $limit,
    $column,
    $direction,
    $work_id,
    $where_condition,
    $studentId = null,
    $getCount = false
) {
    $work_table         = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $iprop_table        = Database::get_course_table(TABLE_ITEM_PROPERTY);
    $user_table         = Database::get_main_table(TABLE_MAIN_USER);

    $session_id     = api_get_session_id();
    $course_id      = api_get_course_int_id();
    $group_id       = api_get_group_id();
    $course_info    = api_get_course_info(api_get_course_id());

    $work_id        = intval($work_id);
    $column         = !empty($column) ? Database::escape_string($column) : 'sent_date';
    $start          = intval($start);
    $limit          = intval($limit);

    if (!in_array($direction, array('asc','desc'))) {
        $direction = 'desc';
    }

    $work_data = get_work_data_by_id($work_id);
    $is_allowed_to_edit = api_is_allowed_to_edit(null, true);
    $condition_session  = api_get_session_condition($session_id);
    $locked = api_resource_is_locked_by_gradebook($work_id, LINK_STUDENTPUBLICATION);

    if (!empty($work_data)) {

        if (!empty($group_id)) {
            $extra_conditions = " work.post_group_id = '".intval($group_id)."' ";
             // set to select only messages posted by the user's group
        } else {
            $extra_conditions = " work.post_group_id = '0' ";
        }

        if ($is_allowed_to_edit) {
            $extra_conditions .= ' AND work.active IN (0, 1) ';
        } else {
            if (isset($course_info['show_score']) &&  $course_info['show_score'] == 1) {
                $extra_conditions .= " AND (u.user_id = ".api_get_user_id()." AND work.active IN (0, 1)) ";
            } else {
                $extra_conditions .= ' AND work.active IN (0, 1) ';
            }
        }

        $extra_conditions .= " AND parent_id  = ".$work_id." ";

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
                        qualificator_id';
        if ($getCount) {
            $select = "SELECT DISTINCT count(u.user_id) as count ";
        }

        $user_condition = "INNER JOIN $user_table u  ON (work.user_id = u.user_id) ";
        $work_condition = "$iprop_table prop INNER JOIN $work_table work
                           ON (prop.ref = work.id AND prop.c_id = $course_id AND work.c_id = $course_id ) ";

        $work_assignment = get_work_assignment_by_id($work_id);

        if (!empty($studentId)) {
            $where_condition.= " AND u.user_id = ".intval($studentId);
        }

        $sql = " $select
                FROM $work_condition  $user_condition
                WHERE $extra_conditions $where_condition $condition_session
                ORDER BY $column $direction";

        if (!empty($start) && !empty($limit)) {
            $sql .= "LIMIT $start, $limit";
        }

        $result = Database::query($sql);
        $works = array();

        if ($getCount) {
            $work = Database::fetch_array($result, 'ASSOC');
            return $work['count'];
        }

        $url = api_get_path(WEB_CODE_PATH).'work/';

        while ($work = Database::fetch_array($result, 'ASSOC')) {
            $item_id = $work['id'];
            //$workItem = get_work_data_by_id($item_id);

            // Get the author ID for that document from the item_property table
			$is_author  = false;
            $can_read   = false;

            $owner_id = $work['user_id'];

            /* Because a bug found when saving items using the api_item_property_update()
               the field $item_property_data['insert_user_id'] is not reliable. */

			// $item_property_data = api_get_item_property_info(api_get_course_int_id(), 'work', $item_id, api_get_session_id());
			//if (!$is_allowed_to_edit && $item_property_data['insert_user_id'] == api_get_user_id()) {
            if (!$is_allowed_to_edit && $owner_id == api_get_user_id()) {
				$is_author = true;
			}

            if ($course_info['show_score'] == 0) {
                $can_read = true;
            }

            if ($work['accepted'] == '0') {
                $class = 'invisible';
            } else {
                $class = '';
            }

            $qualification_exists = false;
            if (!empty($work_data['qualification']) && intval($work_data['qualification']) > 0) {
                $qualification_exists = true;
            }

            $qualification_string = '';

            if ($qualification_exists) {
                if ($work['qualification'] == '') {
                    $qualification_string = Display::label('-');
                } else {
                    $label = 'info';
                    $relativeScore = $work['qualification']/$work_data['qualification'];
                    if ($relativeScore < 0.5) {
                        $label = 'important';
                    } elseif ($relativeScore < 0.75) {
                        $label = 'warning';
                    }
                    $qualification_string = Display::label(
                        $work['qualification'].' / '.$work_data['qualification'],
                        $label
                    );
                }
            }

            $add_string = '';
            $time_expires = api_strtotime($work_assignment['expires_on'], 'UTC');

            if (!empty($work_assignment['expires_on']) &&
                $work_assignment['expires_on'] != '0000-00-00 00:00:00' &&
                $time_expires && ($time_expires < api_strtotime($work['sent_date'], 'UTC'))) {
                $add_string = Display::label(get_lang('Expired'), 'important');
            }

            if (($can_read && $work['accepted'] == '1') ||
                ($is_author && in_array($work['accepted'], array('1', '0'))) ||
                $is_allowed_to_edit
            ) {

                // Firstname, lastname, username
                $work['firstname'] = Display::div($work['firstname'], array('class' => $class));
                $work['lastname'] = Display::div($work['lastname'], array('class' => $class));

                if (strlen($work['title']) > 30) {
                    $short_title = substr($work['title'], 0, 27).'...';
                    $work['title'] = Display::span($short_title, array('class' => $class, 'title' => $work['title']));
                } else {
                    $work['title'] = Display::div($work['title'], array('class' => $class));
                }

                // Type.
                $work['type'] = build_document_icon_tag('file', $work['url']);

                // File name.
                $link_to_download = null;
                // If URL is present then there's a file to download keep BC.
                if ($work['contains_file'] || !empty($work['url'])) {
                    $link_to_download = '<a href="'.$url.'download.php?id='.$item_id.'&'.api_get_cidreq().'">'.
                        Display::return_icon('save.png', get_lang('Save'),array(), ICON_SIZE_SMALL).'</a> ';
                } else {
                   //$link_to_download = '<a href="view.php?id='.$item_id.'">'.Display::return_icon('save_na.png', get_lang('Save'),array(), ICON_SIZE_SMALL).'</a> ';
                }

                $send_to = Portfolio::share('work', $work['id'],  array('style' => 'white-space:nowrap;'));

                $work['qualification'] = $qualification_string;

                // Date.
                $work_date = api_convert_and_format_date($work['sent_date']);
                $work['sent_date'] = date_to_str_ago(api_get_local_time($work['sent_date'])).' '.$add_string.'<br />'.$work_date;

                // Actions.

                $action = '';
                if ($is_allowed_to_edit) {
                    $action .= '<a href="'.$url.'view.php?'.api_get_cidreq().'&id='.$item_id.'" title="'.get_lang('View').'">'.
                        Display::return_icon('default.png', get_lang('View'),array(), ICON_SIZE_SMALL).'</a> ';

                    if ($locked) {
                        if ($qualification_exists) {
                            $action .= Display::return_icon('rate_work_na.png', get_lang('CorrectAndRate'),array(), ICON_SIZE_SMALL);
                        } else {
                            $action .= Display::return_icon('edit_na.png', get_lang('Comment'),array(), ICON_SIZE_SMALL);
                        }
                    } else {
                        if ($qualification_exists) {
                            $action .= '<a href="'.$url.'edit.php?'.api_get_cidreq().'&item_id='.$item_id.'&id='.$work['parent_id'].'" title="'.get_lang('Edit').'"  >'.
                            Display::return_icon('rate_work.png', get_lang('CorrectAndRate'), array(), ICON_SIZE_SMALL).'</a>';
                        } else {
                            $action .= '<a href="'.$url.'edit.php?'.api_get_cidreq().'&item_id='.$item_id.'&id='.$work['parent_id'].'" title="'.get_lang('Modify').'">'.
                            Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL).'</a>';
                        }
                    }

                    if ($work['contains_file']) {
                        if ($locked) {
                            $action .= Display::return_icon('move_na.png', get_lang('Move'),array(), ICON_SIZE_SMALL);
                        } else {
                            $action .= '<a href="'.$url.'work.php?'.api_get_cidreq().'&action=move&item_id='.$item_id.'" title="'.get_lang('Move').'">'.Display::return_icon('move.png', get_lang('Move'),array(), ICON_SIZE_SMALL).'</a>';
                        }
                    }

                    if ($work['accepted'] == '1') {
                        $action .= '<a href="'.$url.'work_list_all.php?'.api_get_cidreq().'&id='.$work_id.'&action=make_invisible&item_id='.$item_id.'" title="'.get_lang('Invisible').'" >'.
                            Display::return_icon('visible.png', get_lang('Invisible'),array(), ICON_SIZE_SMALL).'</a>';
                    } else {
                        $action .= '<a href="'.$url.'work_list_all.php?'.api_get_cidreq().'&id='.$work_id.'&action=make_visible&item_id='.$item_id.'" title="'.get_lang('Visible').'" >'.
                            Display::return_icon('invisible.png', get_lang('Visible'),array(), ICON_SIZE_SMALL).'</a> ';
                    }

                    if ($locked) {
                        $action .= Display::return_icon('delete_na.png', get_lang('Delete'), '', ICON_SIZE_SMALL);
                    } else {
                        $action .= '<a href="'.$url.'work_list_all.php?'.api_get_cidreq().'&id='.$work_id.'&action=delete&amp;item_id='.$item_id.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES))."'".')) return false;" title="'.get_lang('Delete').'" >'.
                            Display::return_icon('delete.png', get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>';
                    }
                } elseif ($is_author && (empty($work['qualificator_id']) || $work['qualificator_id'] == 0)) {
                    $action .= '<a href="'.$url.'view.php?'.api_get_cidreq().'&id='.$item_id.'" title="'.get_lang('View').'">'.
                        Display::return_icon('default.png', get_lang('View'),array(), ICON_SIZE_SMALL).'</a>';

                    if (api_get_course_setting('student_delete_own_publication') == 1) {
                        if (api_is_allowed_to_session_edit(false, true)) {
                            $action .= '<a href="'.$url.'edit.php?'.api_get_cidreq().'&item_id='.$item_id.'&id='.$work['parent_id'].'" title="'.get_lang('Modify').'">'.
                                        Display::return_icon('edit.png', get_lang('Comment'),array(), ICON_SIZE_SMALL).'</a>';
                        }
                        $action .= ' <a href="'.$url.'work_list.php?'.api_get_cidreq().'&action=delete&item_id='.$item_id.'&id='.$work['parent_id'].'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES))."'".')) return false;" title="'.get_lang('Delete').'"  >'.
                            Display::return_icon('delete.png',get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>';
                    } else {
                        $action .= Display::return_icon('edit_na.png', get_lang('Modify'),array(), ICON_SIZE_SMALL);
                    }
                } else {
                    $action .= '<a href="'.$url.'view.php?'.api_get_cidreq().'&id='.$item_id.'" title="'.get_lang('View').'">'.Display::return_icon('default.png', get_lang('View'),array(), ICON_SIZE_SMALL).'</a>';
                    $action .= Display::return_icon('edit_na.png', get_lang('Modify'),array(), ICON_SIZE_SMALL);
                }

                // Status.
                if (empty($work['qualificator_id'])) {
                    $qualificator_id = Display::label(get_lang('NotRevised'), 'warning');
                } else {
                    $qualificator_id = Display::label(get_lang('Revised'), 'success');
                }
                $work['qualificator_id'] = $qualificator_id;
                $work['actions'] = $send_to.$link_to_download.$action;
                $works[] = $work;
            }
        }
        return $works;
    }
}

/**
 * Send reminder to users who have not given the task
 *
 * @param int
 * @return array
 * @author cvargas carlos.vargas@beeznest.com cfasanando, christian.fasanado@beeznest.com
 */
function send_reminder_users_without_publication($task_data)
{
	global $_course;
	$task_id = $task_data['id'];
	$task_title = !empty($task_data['title']) ? $task_data['title'] : basename($task_data['url']);
	$subject = '[' . api_get_setting('siteName') . '] ';

	// The body can be as long as you wish, and any combination of text and variables
	$content = get_lang('ReminderToSubmitPendingTask')."\n".get_lang('CourseName').' : '.$_course['name']."\n";
	$content .= get_lang('WorkName').' : '.$task_title."\n";

	$list_users = get_list_users_without_publication($task_id);

    $mails_sent_to = array();
	foreach ($list_users as $user) {
		$name_user = api_get_person_name($user[1], $user[0], null, PERSON_NAME_EMAIL_ADDRESS);
        $dear_line = get_lang('Dear')." ".api_get_person_name($user[1], $user[0]) .", \n\n";
        $body      = $dear_line.$content;
        MessageManager::send_message($user[3], $subject, $body);
        $mails_sent_to[] = $name_user;
	}
    return $mails_sent_to;
}

/**
 * Sends an email to the students of a course when a homework is created
 *
 * @param string course_id
 *
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 * @author Julio Montoya <gugli100@gmail.com> Adding session support - 2011
 */
function send_email_on_homework_creation($course_id)
{
	// Get the students of the course
	$session_id = api_get_session_id();
	if (empty($session_id)) {
		$students = CourseManager::get_student_list_from_course_code($course_id);
	} else {
		$students = CourseManager::get_student_list_from_course_code($course_id, true, $session_id);
	}
	$emailsubject = '[' . api_get_setting('siteName') . '] '.get_lang('HomeworkCreated');
	$currentUser = api_get_user_info(api_get_user_id());
	if (!empty($students)) {
		foreach($students as $student) {
			$user_info = api_get_user_info($student["user_id"]);
			if(!empty($user_info["mail"])) {
				$name_user = api_get_person_name($user_info["firstname"], $user_info["lastname"], null, PERSON_NAME_EMAIL_ADDRESS);
				$emailbody = get_lang('Dear')." ".$name_user.",\n\n";
				$emailbody .= get_lang('HomeworkHasBeenCreatedForTheCourse')." ".$course_id.". "."\n\n".get_lang('PleaseCheckHomeworkPage');
				$emailbody .= "\n\n".api_get_person_name($currentUser["firstname"], $currentUser["lastname"]);
				@api_mail($name_user, $user_info["mail"], $emailsubject, $emailbody, api_get_person_name($currentUser["firstname"], $currentUser["lastname"], null, PERSON_NAME_EMAIL_ADDRESS), $currentUser["mail"]);
			}
		}
	}
}

/**
 * @param string $url
 * @return bool
 */
function is_work_exist_by_url($url)
{
	$work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$url = Database::escape_string($url);
	$sql = "SELECT id FROM $work_table WHERE url='$url'";
	$result = Database::query($sql);
	if (Database::num_rows($result)> 0) {
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
 * @param $name
 * @param $values
 * @param string $checked
 * @return string
 */
function make_select($name, $values, $checked = '')
{
	$output = '<select name="'.$name.'" id="'.$name.'">';
	foreach ($values as $key => $value) {
		$output .= '<option value="'.$key.'" '.(($checked==$key) ? 'selected="selected"' : '').'>'.$value.'</option>';
	}
	$output .= '</select>';
	return $output;
}

/**
 * @param $name
 * @param string $checked
 * @param null $label
 * @return string
 */
function make_checkbox($name, $checked = '', $label = null)
{
	$check = '<input id ="'.$name.'" type="checkbox" value="1" name="'.$name.'" '.((!empty($checked))?'checked="checked"':'').'/>';
	if (!empty($label)) {
		$check .="<label for ='$name'>$label</label>";
	}
	return $check;
}

/**
 * @param $prefix
 * @param string $default
 * @return string
 */
function draw_date_picker($prefix, $default = '')
{
	if (empty($default)) {
		$default = api_get_local_time();
	}
	$parts = explode(' ', $default);
	list($d_year, $d_month, $d_day) = explode('-', $parts[0]);
	list($d_hour, $d_minute) = explode(':', $parts[1]);

	$minute = range(10, 59);
	array_unshift($minute, '00', '01', '02', '03', '04', '05', '06', '07', '08', '09');
	$date_form = make_select($prefix.'_day', array_combine(range(1, 31), range(1, 31)), $d_day);
	$date_form .= make_select($prefix.'_month', array_combine(range(1, 12), api_get_months_long()), $d_month);
	$date_form .= make_select($prefix.'_year', array($d_year => $d_year, $d_year + 1 => $d_year + 1), $d_year).'&nbsp;&nbsp;&nbsp;&nbsp;';
	$date_form .= make_select($prefix.'_hour', array_combine(range(0, 23), range(0, 23)), $d_hour).' : ';
	$date_form .= make_select($prefix.'_minute', $minute, $d_minute);
	return $date_form;
}

/**
 * @param string $prefix
 * @param array of values
 * @return string
 *
 */
function get_date_from_select($prefix, $array = array())
{
	return
        $array[$prefix]['year'].'-'.
        two_digits($array[$prefix]['month']).'-'.
        two_digits($array[$prefix]['day']).' '.
        two_digits($array[$prefix]['hour']).':'.
        two_digits($array[$prefix]['minute']).':00';
}

/**
 * Check if a user is the author of a work document.
 * @param int $itemId
 * @param int $userId
 * @param int $courseId
 * @param int $sessionId
 * @return bool
 */
function user_is_author($itemId, $userId = null, $courseId = null, $sessionId = null)
{
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
 * Get list of users who have not given the task
 * @param int
 * @param int
 * @return array
 * @author cvargas
 * @author Julio Montoya <gugli100@gmail.com> Fixing query
 */
function get_list_users_without_publication($task_id, $studentId = null)
{
	$work_table 			 = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$table_course_user 		 = Database::get_main_table(TABLE_MAIN_COURSE_USER);
	$table_user 			 = Database::get_main_table(TABLE_MAIN_USER);
	$session_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

	//condition for the session
	$session_id    = api_get_session_id();
	$course_id     = api_get_course_int_id();

	$task_id = intval($task_id);

	if ($session_id == 0) {
		$sql = "SELECT user_id as id FROM $work_table
		        WHERE
		            c_id = $course_id AND
		            parent_id='$task_id' AND
		            active IN (0, 1)";
	} else {
		$sql = "SELECT user_id as id FROM $work_table
		        WHERE
		            c_id = $course_id AND
		            parent_id='$task_id' AND
		            session_id='".$session_id."' AND
		            active IN (0, 1)";
	}

	$result = Database::query($sql);
	$users_with_tasks = array();
	while($row = Database::fetch_array($result)) {
		$users_with_tasks[] = $row['id'];
	}

	if ($session_id == 0) {
		$sql_users = "SELECT cu.user_id, u.lastname, u.firstname, u.email FROM $table_course_user AS cu, $table_user AS u
		              WHERE u.status != 1 and cu.course_code='".api_get_course_id()."' AND u.user_id = cu.user_id";
	} else {
		$sql_users = "SELECT cu.id_user, u.lastname, u.firstname, u.email FROM $session_course_rel_user AS cu, $table_user AS u
		              WHERE u.status != 1 and cu.course_code='".api_get_course_id()."' AND u.user_id = cu.id_user and cu.id_session = '".$session_id."'";
	}

    if (!empty($studentId)) {
        $sql_users.= " AND u.user_id = ".intval($studentId);
    }

    $group_id = api_get_group_id();

    $new_group_user_list = array();

    if ($group_id) {
        $group_user_list = GroupManager::get_subscribed_users($group_id);
        if (!empty($group_user_list)) {
            foreach($group_user_list as $group_user) {
                $new_group_user_list[] = $group_user['user_id'];
            }
        }
    }

	$result_users = Database::query($sql_users);
	$users_without_tasks = array();
	while ($row_users = Database::fetch_row($result_users)) {

		if (in_array($row_users[0], $users_with_tasks)) {
            continue;
        }

		if ($group_id && !in_array($row_users[0], $new_group_user_list)) {
            continue;
        }
        $userId = $row_users[0];
        $row_users[0] = $row_users[1];
        $row_users[1] = $row_users[2];
		$row_users[2] = Display::encrypted_mailto_link($row_users[3]);
        $row_users[3] = $userId;
		$users_without_tasks[] = $row_users;
	}
	return $users_without_tasks;
}

/**
 * Display list of users who have not given the task
 *
 * @param int task id
 * @param int $studentId
 * @return array
 * @author cvargas carlos.vargas@beeznest.com cfasanando, christian.fasanado@beeznest.com
 * @author Julio Montoya <gugli100@gmail.com> Fixes
 */
function display_list_users_without_publication($task_id, $studentId = null)
{
	global $origin;
	$table_header[] = array(get_lang('LastName'), true);
	$table_header[] = array(get_lang('FirstName'), true);
	$table_header[] = array(get_lang('Email'), true);

	$data = get_list_users_without_publication($task_id);

	$sorting_options = array();
	$sorting_options['column'] = 1;
	$paging_options = array();
	$my_params = array();

	if (isset($_GET['curdirpath'])) {
		$my_params['curdirpath'] = Security::remove_XSS($_GET['curdirpath']);
	}
	if (isset($_GET['edit_dir'])) {
		$my_params['edit_dir'] = Security::remove_XSS($_GET['edit_dir']);
	}
	if (isset($_GET['list'])) {
		$my_params['list'] = Security::remove_XSS($_GET['list']);
	}
	$my_params['origin'] = $origin;
    $my_params['id'] = intval($_GET['id']);

	//$column_show
	$column_show[] = 1;
	$column_show[] = 1;
	$column_show[] = 1;
	Display::display_sortable_config_table('work', $table_header, $data, $sorting_options, $paging_options, $my_params, $column_show);
}

/**
 * @param int $documentId
 * @param int $workId
 * @param int $courseId
 */
function addDocumentToWork($documentId, $workId, $courseId)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_DOCUMENT);
    $params = array(
        'document_id' => $documentId,
        'work_id' => $workId,
        'c_id' => $courseId
    );
    Database::insert($table, $params);
}

/**
 * @param int $documentId
 * @param int $workId
 * @param int $courseId
 * @return array
 */
function getDocumentToWork($documentId, $workId, $courseId)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_DOCUMENT);
    $params = array(
        'document_id = ? and work_id = ? and c_id = ?' => array($documentId, $workId, $courseId)
    );
    return Database::select('*', $table, array('where' => $params));
}

/**
 * @param int $documentId
 * @param int $workId
 * @param int $courseId
 * @param int $sessionId
 * @param int $userId
 * @param int $active
 * @return array
 */
function getDocumentToWorkPerUser($documentId, $workId, $courseId, $sessionId, $userId, $active = 1)
{
    $workRel = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_DOCUMENT);
    $work = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

    $documentId = intval($documentId);
    $workId = intval($workId);
    $courseId = intval($courseId);
    $userId = intval($userId);
    $sessionId = intval($sessionId);
    $active = intval($active);

    $sql = "SELECT w.* FROM $work w INNER JOIN $workRel rel ON (w.parent_id = rel.work_id)
            WHERE
                w.document_id = $documentId AND
                w.parent_id = $workId AND
                w.c_id = $courseId AND
                w.session_id = $sessionId AND
                user_id = $userId AND
                active = $active
            ";

    $result = Database::query($sql);
    $workInfo = array();
    if (Database::num_rows($result)) {
        $workInfo = Database::fetch_array($result, 'ASSOC');
    }
    return $workInfo;
}


/**
 * @param int $workId
 * @param int $courseId
 * @return array
 */
function getAllDocumentToWork($workId, $courseId)
{
    if (ADD_DOCUMENT_TO_WORK == false) {
        return array();
    }
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_DOCUMENT);
    $params = array(
        'work_id = ? and c_id = ?' => array($workId, $courseId)
    );
    return Database::select('*', $table, array('where' => $params));
}

/**
 * @param int $documentId
 * @param int $workId
 * @param int $courseId
 */
function deleteDocumentToWork($documentId, $workId, $courseId)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_DOCUMENT);
    $params = array(
        'document_id = ? and work_id = ? and c_id = ?' => array($documentId, $workId, $courseId)
    );
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
    $params = array(
        'user_id' => $userId,
        'work_id' => $workId,
        'c_id' => $courseId
    );
    Database::insert($table, $params);
}

/**
 * @param int $userId
 * @param int $workId
 * @param int $courseId
 * @return array
 */
function getUserToWork($userId, $workId, $courseId)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_USER);
    $params = array(
        'user_id = ? and work_id = ? and c_id = ?' => array($userId, $workId, $courseId)
    );
    return Database::select('*', $table, array('where' => $params));
}

/**
 * @param int $workId
 * @param int $courseId
 * @param bool $getCount
 * @return array
 */
function getAllUserToWork($workId, $courseId, $getCount = false)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_USER);
    $params = array(
        'work_id = ? and c_id = ?' => array($workId, $courseId)
    );
    if ($getCount) {
        $count = 0;
        $result = Database::select(
            'count(user_id) as count',
            $table,
            array('where' => $params),
            'simple'
        );
        if (!empty($result)) {
            $count = intval($result['count']);
        }
        return $count;
    } else {
        return Database::select('*', $table, array('where' => $params));
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
    $params = array(
        'user_id = ? and work_id = ? and c_id = ?' => array($userId, $workId, $courseId)
    );
    Database::delete($table, $params);
}

/**
 * @param int $userId
 * @param int $workId
 * @param int $courseId
 * @return bool
 */
function userIsSubscribedToWork($userId, $workId, $courseId)
{
    if (ADD_DOCUMENT_TO_WORK == false) {
        return true;
    }
    $subscribedUsers = getAllUserToWork($workId, $courseId);

    if (empty($subscribedUsers)) {
        return true;
    } else {
        $subscribedUsersList = array();
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
 * Get the list of students that have to submit their work
 * @param integer $workId The internal ID of the assignment
 * @param integer $courseId The course ID
 * @param integer $groupId The group ID, if any
 * @param integer $sessionId The session ID, if any
 * @param bool $getCount Whether we want just the amount or the full result
 * @return array|int An integer (if we just asked for the count) or an array of users
 */
function getStudentSubscribedToWork($workId, $courseId, $groupId = null, $sessionId = null, $getCount = false)
{
    $usersInWork = null;
    $usersInCourse = null;

    if (empty($groupId)) {
        $courseInfo = api_get_course_info_by_id($courseId);
        $usersInCourse = CourseManager::get_user_list_from_course_code(
            $courseInfo['code'],
            $sessionId,
            null,
            null,
            STUDENT,
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

    if (ADD_DOCUMENT_TO_WORK == true) {
        $usersInWork = getAllUserToWork($workId, $courseId, $getCount);
        if (empty($usersInWork)) {
            return $usersInCourse;
        } else {
            return $usersInWork;
        }
    } else {
        return $usersInCourse;
    }

}

/**
 * @param int $userId
 * @param int $workId
 * @param int $courseId
 * @return bool
 */
function allowOnlySubscribedUser($userId, $workId, $courseId)
{
    if (ADD_DOCUMENT_TO_WORK == false) {
        return true;
    }
    if (api_is_platform_admin() || api_is_allowed_to_edit()) {
        return true;
    }
    if (userIsSubscribedToWork($userId, $workId, $courseId) == false) {
        api_not_allowed(true);
    }
}

/**
 * @param int $workId
 * @param array $courseInfo
 * @param int $documentId
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
            if ($fileInfo['extension'] == 'html') {
                if (file_exists($docData['absolute_path']) && is_file($docData['absolute_path'])) {
                    $docData['file_content'] = file_get_contents($docData['absolute_path']);
                    return $docData;
                }
            }
        }
    }
    return array();
}

/**
 * @param int $workId
 * @param array $courseInfo
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
                $content .= '<li><a target="_blank" href="'.$docData['url'].'">'.$docData['title'].'</a></li>';
            }
        }
        $content .= '</ul><br />';
    }
    return $content;
}

/**
 * Returns fck editor toolbar
 * @return array
 */
function getWorkDescriptionToolbar()
{
    return array(
        'ToolbarStartExpanded' => 'true',
        'ToolbarSet' => 'Work',
        'Width' => '100%',
        'Height' => '400'
    );
}

/**
 * @param array $work
 * @return string
 */
function getWorkComments($work)
{
    if (ADD_DOCUMENT_TO_WORK == false) {
        return array();
    }

    $commentTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT_COMMENT);
    $userTable= Database::get_main_table(TABLE_MAIN_USER);

    $courseId = intval($work['c_id']);
    $workId = intval($work['id']);

    $sql = "SELECT c.id, c.user_id, u.firstname, u.lastname, u.username, u.picture_uri
            FROM $commentTable c INNER JOIN $userTable u ON(u.user_id = c.user_id)
            WHERE c_id = $courseId AND work_id = $workId
            ORDER BY sent_at
            ";
    $result = Database::query($sql);
    $comments = Database::store_result($result, 'ASSOC');
    if (!empty($comments)) {
        foreach ($comments as &$comment) {
            $pictureInfo = UserManager::get_picture_user(
                $comment['user_id'],
                $comment['picture_uri'],
                24,
                USER_IMAGE_SIZE_SMALL
            );
            $comment['picture'] = $pictureInfo['file'];
            $commentInfo = getWorkComment($comment['id']);

            if (!empty($commentInfo)) {
                $comment = array_merge($comment, $commentInfo);
            }
        }
    }
    return $comments;
}


/**
 * @param int $id comment id
 * @param array $courseInfo
 * @return string
 */
function getWorkComment($id, $courseInfo = array())
{
    if (ADD_DOCUMENT_TO_WORK == false) {
        return array();
    }
    if (empty($courseInfo)) {
        $courseInfo = api_get_course_info();
    }

    if (empty($courseInfo['real_id'])) {
        return array();
    }

    $commentTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT_COMMENT);
    $id = intval($id);

    $sql = "SELECT * FROM $commentTable
            WHERE id = $id AND c_id = ".$courseInfo['real_id'];
    $result = Database::query($sql);
    $comment = array();
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
    }
    return $comment;
}

/**
 * @param int $id
 * @param array $courseInfo
 */
function deleteCommentFile($id, $courseInfo = array())
{
    $workComment = getWorkComment($id, $courseInfo);
    if (isset($workComment['file']) && !empty($workComment['file'])) {
        if (file_exists($workComment['file_path'])) {
            $result = my_delete($workComment['file_path']);
            if ($result) {
                $commentTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT_COMMENT);
                $params = array('file' => '');
                Database::update(
                    $commentTable,
                    $params,
                    array('id = ? AND c_id = ? ' => array($workComment['id'], $workComment['c_id']))
                );
            }
        }
    }
}

/**
 * Adds a comments to the work document
 * @param array $courseInfo
 * @param int $userId
 * @param array $work
 * @param array $data
 * @return int
 */
function addWorkComment($courseInfo, $userId, $work, $data)
{
    if (ADD_DOCUMENT_TO_WORK == false) {
        return null;
    }

    $commentTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT_COMMENT);

    $params = array(
        'work_id' => $work['id'],
        'c_id' => $work['c_id'],
        'user_id' => $userId,
        'comment' => $data['comment'],
        'sent_at' => api_get_utc_datetime()
    );

    $commentId = Database::insert($commentTable, $params);
    $fileData = isset($data['file']) ? $data['file'] : null;
    if (!empty($commentId) && !empty($fileData)) {
        $workParent = get_work_data_by_id($work['parent_id']);
        if (!empty($workParent)) {
            $uploadDir = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/work'.$workParent['url'];
            $newFileName = 'comment_'.$commentId.'_'.php2phps(
                replace_dangerous_char($fileData['name'], 'strict')
            );
            $newFilePath = $uploadDir.'/'.$newFileName;
            $result = move_uploaded_file($fileData['tmp_name'], $newFilePath);
            if ($result) {
                $params = array('file' => $newFileName);
                Database::update(
                    $commentTable,
                    $params,
                    array('id = ? AND c_id = ? ' => array($commentId, $work['c_id']))
                );
            }
        }
    }
}

/**
 * @param array $work
 * @return string
 */
function getWorkCommentForm($work)
{
    if (ADD_DOCUMENT_TO_WORK == false) {
        return null;
    }
    $form = new FormValidator(
        'work_comment',
        'post',
        api_get_path(WEB_CODE_PATH).'work/view.php?id='.$work['id'].'&action=send_comment&'.api_get_cidreq()
    );

    $form->addElement('file', 'file', get_lang('Attachment'));
    $form->addElement('textarea', 'comment', get_lang('Comment'), array('class' => 'span5', 'rows' => '8'));
    //$form->addRule('comment', get_lang('ThisFieldIsRequired'), 'required');
    $form->addElement('hidden', 'id', $work['id']);
    $form->addElement('button', 'button', get_lang('Send'));
    return $form->return_form();
}

/**
 * @param array $homework result of get_work_assignment_by_id()
 * @return string
 */
function getWorkDateValidationStatus($homework) {
    $message = null;
    $has_expired = false;
    $has_ended = false;

    if (!empty($homework)) {

        if ($homework['expires_on'] != '0000-00-00 00:00:00' || $homework['ends_on'] != '0000-00-00 00:00:00') {
            $time_now = time();

            if (!empty($homework['expires_on']) && $homework['expires_on'] != '0000-00-00 00:00:00') {
                $time_expires 	= api_strtotime($homework['expires_on'], 'UTC');
                $difference 	= $time_expires - $time_now;
                if ($difference < 0) {
                    $has_expired = true;
                }
            }

            if (empty($homework['expires_on']) || $homework['expires_on'] == '0000-00-00 00:00:00') {
                $has_expired = false;
            }

            if (!empty($homework['ends_on']) && $homework['ends_on'] != '0000-00-00 00:00:00') {
                $time_ends 		= api_strtotime($homework['ends_on'], 'UTC');
                $difference2 	= $time_ends - $time_now;
                if ($difference2 < 0) {
                    $has_ended = true;
                }
            }

            $ends_on 	= api_convert_and_format_date($homework['ends_on']);
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

    return array(
        'message' => $message,
        'has_ended' => $has_ended,
        'has_expired' => $has_expired
    );
}

/**
 * @param FormValidator $form
 * @param int $uploadFormType
 */
function setWorkUploadForm($form, $uploadFormType = 0)
{
    $form->addElement('header', get_lang('UploadADocument'));
    $form->addElement('hidden', 'contains_file', 0, array('id'=>'contains_file_id'));
    $form->addElement('hidden', 'active', 1);
    $form->addElement('hidden', 'accepted', 1);
    $form->addElement('text', 'title', get_lang('Title'), array('id' => 'file_upload', 'class' => 'span4'));
    $form->addRule('title', get_lang('ThisFieldIsRequired'), 'required');

    switch ($uploadFormType) {
        case 0:
            // File and text.
            $form->addElement('file', 'file', get_lang('UploadADocument'), 'size="40" onchange="updateDocumentTitle(this.value)"');
            $form->add_real_progress_bar('uploadWork', 'file');
            $form->add_html_editor('description', get_lang('Description'), false, false, getWorkDescriptionToolbar());
            break;
        case 1:
            // Only text.
            $form->add_html_editor('description', get_lang('Description'), false, false, getWorkDescriptionToolbar());
            $form->addRule('description', get_lang('ThisFieldIsRequired'), 'required');
            break;
        case 2:
            // Only file.
            $form->addElement('file', 'file', get_lang('UploadADocument'), 'size="40" onchange="updateDocumentTitle(this.value)"');
            $form->add_real_progress_bar('uploadWork', 'file');
            $form->addRule('file', get_lang('ThisFieldIsRequired'), 'required');
            break;
    }

    $form->addElement('style_submit_button', 'submitWork', get_lang('Send'), array('class'=> 'upload', 'value' => "submitWork"));
}

/**
 * @param array $my_folder_data
 * @param array $_course
 * @return array
 */
function uploadWork($my_folder_data, $_course)
{
    if (empty($_FILES['file']['size'])) {
        return array('error' => Display :: return_message(get_lang('UplUploadFailedSizeIsZero'), 'error'));
    }
    $updir = api_get_path(SYS_COURSE_PATH).$_course['path'].'/work/'; //directory path to upload

    // Try to add an extension to the file if it has'nt one
    $filename = add_ext_on_mime(stripslashes($_FILES['file']['name']), $_FILES['file']['type']);

    // Replace dangerous characters
    $filename = replace_dangerous_char($filename, 'strict');

    // Transform any .php file in .phps fo security
    $filename = php2phps($filename);
    $filesize = filesize($_FILES['file']['tmp_name']);

    if (empty($filesize)) {
        return array('error' => Display :: return_message(get_lang('UplUploadFailedSizeIsZero'), 'error'));
    } elseif (!filter_extension($new_file_name)) {
        return array('error' => Display :: return_message(get_lang('UplUnableToSaveFileFilteredExtension'), 'error'));
    }

    $totalSpace = DocumentManager::documents_total_space($_course['real_id']);
    $course_max_space = DocumentManager::get_course_quota($_course['code']);
    $total_size = $filesize + $totalSpace;

    if ($total_size > $course_max_space) {
        return array(
            'error' => Display :: return_message(get_lang('NoSpace'), 'error')
        );
    }

    // Compose a unique file name to avoid any conflict
    $new_file_name = api_get_unique_id();
    $curdirpath = basename($my_folder_data['url']);

    // If we come from the group tools the groupid will be saved in $work_table
    if (is_dir($updir.$curdirpath) || empty($curdirpath)) {
        $result = move_uploaded_file(
            $_FILES['file']['tmp_name'],
            $updir.$curdirpath.'/'.$new_file_name
        );
    } else {
        return array(
            'error' => Display :: return_message(
                get_lang('FolderDoesntExistsInFileSystem'),
                'error'
            )
        );
    }

    $url = null;
    if ($result) {
        $url = 'work/'.$curdirpath.'/'.$new_file_name;
    }

    return array(
        'url' => $url,
        'filename' => $filename,
        'error' => null
    );
}

/**
 * @param int $workId
 * @param array $courseInfo
 * @param int $session_id
 */
function sendAlertToTeacher($workId, $courseInfo, $session_id)
{
    $workData = get_work_assignment_by_id($workId, $courseInfo['real_id']);
    //last value is to check this is not "just" an edit
    //YW Tis part serve to send a e-mail to the tutors when a new file is sent
    $send = api_get_course_setting('email_alert_manager_on_new_doc');

    if ($send > 0) {
        // Lets predefine some variables. Be sure to change the from address!
        if (empty($session_id)) {
            //Teachers
            $user_list = CourseManager::get_user_list_from_course_code(api_get_course_id(), null, null, null, COURSEMANAGER);
        } else {
            //Coaches
            $user_list = CourseManager::get_user_list_from_course_code(api_get_course_id(), $session_id, null, null, 2);
        }

        $subject = "[" . api_get_setting('siteName') . "] ".get_lang('SendMailBody')."\n".get_lang('CourseName')." : ".$courseInfo['name']."  ";

        foreach ($user_list as $user_data) {
            $to_user_id = $user_data['user_id'];
            $user_info = api_get_user_info($to_user_id);
            $message = get_lang('SendMailBody')."\n".get_lang('CourseName')." : ".$courseInfo['name']."\n";
            $message .= get_lang('UserName')." : ".api_get_person_name($user_info['firstname'], $user_info['lastname'])."\n";
            $message .= get_lang('DateSent')." : ".api_format_date(api_get_local_time())."\n";
            $message .= get_lang('WorkName')." : ".$workData['title']."\n\n".get_lang('DownloadLink')."\n";
            $url = api_get_path(WEB_CODE_PATH)."work/work.php?cidReq=".$courseInfo['code']."&id_session=".$session_id."&id=".$workData['id'];
            $message .= $url;

            MessageManager::send_message_simple($to_user_id, $subject, $message);
        }
    }
}

/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @author Julio Montoya
 * @desc Record information for upload event
 * @param int $docId
 * @param int $userId
 * @param string $courseCode
 * @param int $sessionId
 * @return int
 */
function event_upload($docId, $userId, $courseCode, $sessionId)
{
    $table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_UPLOADS);
    $reallyNow = api_get_utc_datetime();
    $userId = intval($userId);
    $docId = intval($docId);
    $sessionId = intval($sessionId);
    $courseCode = Database::escape_string($courseCode);

    $sql = "INSERT INTO ".$table."
        		( upload_user_id,
        		  upload_cours_id,
        		  upload_work_id,
        		  upload_date,
        		  upload_session_id
        		)
        		VALUES (
        		 ".$userId.",
        		 '".$courseCode."',
        		 '".$docId."',
        		 '".$reallyNow."',
        		 '".$sessionId."'
        		)";
    Database::query($sql);
    return 1;
}

/**
 * @param array $workInfo
 * @param array $values
 * @param array $courseInfo
 * @param int $sessionId
 * @param int $groupId
 * @param int $userId
 * @return null|string
 */
function processWorkForm($workInfo, $values, $courseInfo, $sessionId, $groupId, $userId)
{
    $work_table = Database :: get_course_table(TABLE_STUDENT_PUBLICATION);

    $courseId = $courseInfo['real_id'];
    $groupId = intval($groupId);
    $sessionId = intval($sessionId);
    $userId = intval($userId);

    $title  = $values['title'];
    $description = $values['description'];
    $contains_file = isset($values['contains_file']) && !empty($values['contains_file']) ? intval($values['contains_file']): 0;

    $saveWork = true;
    $message = null;
    $filename = null;

    if ($values['contains_file']) {
        $result = uploadWork($workInfo, $courseInfo);
        if (isset($result['error'])) {
            $message = $result['error'];
            $saveWork = false;
        }
        $filename = isset($result['filename']) ? $result['filename'] : null;
        if (empty($title)) {
            $title = isset($result['title']) && !empty($result['title']) ? $result['title'] : get_lang('Untitled');
        }
        $url = $result['url'];
    }

    if (empty($title)) {
        $title = get_lang('Untitled');
    }

    if ($saveWork) {
        $active = '1';
        $sql = "INSERT INTO ".$work_table." SET
                   c_id 		= $courseId ,
                   url         	= '".$url . "',
                   title       	= '".Database::escape_string($title)."',
                   description	= '".Database::escape_string($description)."',
                   contains_file = '".$contains_file."',
                   active		= '".$active."',
                   accepted		= '1',
                   post_group_id = '".$groupId."',
                   sent_date	=  '".api_get_utc_datetime()."',
                   parent_id 	=  '".$workInfo['id']."' ,
                   session_id	= '".$sessionId."',
                   user_id 		= '".$userId."'";

        Database::query($sql);
        $workId = Database::insert_id();

        if ($workId) {
            if (array_key_exists('filename', $workInfo) && !empty($filename)) {
                $filename = Database::escape_string($filename);
                $sql = "UPDATE $work_table SET filename = '$filename'
                        WHERE c_id = $courseId AND id = $workId";
                Database::query($sql);
            }

            if (array_key_exists('document_id', $workInfo)) {
                $documentId = isset($values['document_id']) ? intval($values['document_id']) : 0;
                $sql = "UPDATE $work_table SET document_id = '$documentId'
                        WHERE c_id = $courseId AND id = $workId";
                Database::query($sql);
            }
            api_item_property_update($courseInfo, 'work', $workId, 'DocumentAdded', $userId, $groupId);
            sendAlertToTeacher($workId, $courseInfo, $sessionId);
            event_upload($workId, $userId, $courseInfo['code'], $sessionId) ;
            $message = Display::return_message(get_lang('DocAdd'));
        }
    } else {
        if (empty($message)) {
            $message = Display::return_message(get_lang('IsNotPosibleSaveTheDocument'), 'error');
        }
    }
    return $message;
}

/**
 * Creates a new task (directory) in the assignment tool
 * @param array $params
 * @param int $user_id
 * @param array $courseInfo
 * @param int $group_id
 * @param int $session_id
 * @return bool|int
 * @note $params can have the following elements, but should at least have the 2 first ones: (
 *       'new_dir' => 'some-name',
 *       'description' => 'some-desc',
 *       'qualification' => someintvalue (e.g. 20),
 *       'weight' => someintweight (percentage) to add to gradebook (e.g. 50),
 *       'allow_text_assignment' => 0/1/2,
 * @todo Rename createAssignment or createWork, or something like that
 */
function addDir($params, $user_id, $courseInfo, $group_id, $session_id)
{
    $work_table = Database :: get_course_table(TABLE_STUDENT_PUBLICATION);

    $user_id = intval($user_id);
    $group_id = intval($group_id);
    $session_id = intval($session_id);

    $base_work_dir = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/work';
    $course_id = $courseInfo['real_id'];

    $directory 		= replace_dangerous_char($params['new_dir']);
    $directory 		= disable_dangerous_file($directory);
    $created_dir 	= create_unexisting_work_directory($base_work_dir, $directory);

    if (!empty($created_dir)) {
        $dir_name_sql = '/'.$created_dir;
        $today = api_get_utc_datetime();
        $sql = "INSERT INTO " . $work_table . " SET
                c_id				= $course_id,
                url         		= '".Database::escape_string($dir_name_sql)."',
                title               = '".Database::escape_string($params['new_dir'])."',
                description 		= '".Database::escape_string($params['description'])."',
                author      		= '',
                active              = '1',
                accepted			= '1',
                filetype            = 'folder',
                post_group_id       = '".$group_id."',
                sent_date           = '".$today."',
                qualification       = '".(($params['qualification'] != '') ? Database::escape_string($params['qualification']) : '') ."',
                parent_id           = '',
                qualificator_id     = '',
                date_of_qualification	= '0000-00-00 00:00:00',
                weight              = '".Database::escape_string($params['weight'])."',
                session_id          = '".$session_id."',
                allow_text_assignment = '".Database::escape_string($params['allow_text_assignment'])."',
                contains_file       = 0,
                user_id 			= '".$user_id."'";

        Database::query($sql);

        // Add the directory
        $id = Database::insert_id();

        if ($id) {
            // Folder created
            api_item_property_update($courseInfo, 'work', $id, 'DirectoryCreated', $user_id, $group_id);
            updatePublicationAssignment($id, $params, $courseInfo, $group_id);

            if (api_get_course_setting('email_alert_students_on_new_homework') == 1) {
                send_email_on_homework_creation(api_get_course_id());
            }
            return $id;
        }
    }
    return false;
}

/**
 * @param int $workId
 * @param array $courseInfo
 * @return array
 */
function agendaExistsForWork($workId, $courseInfo)
{
    $workTable = Database :: get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
    $courseId = $courseInfo['real_id'];
    $workId = intval($workId);

    $sql = "SELECT add_to_calendar FROM $workTable WHERE c_id = $courseId AND publication_id = ".$workId;
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
 * @param int $workId
 * @param array $params
 * @param array $courseInfo
 */
function updateWork($workId, $params, $courseInfo)
{
    $workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $filteredParams = array(
        'description' => $params['description'],
        'qualification' => $params['qualification'],
        'weight' => $params['weight'],
        'allow_text_assignment' => $params['allow_text_assignment'],
    );
    Database::update(
        $workTable,
        $filteredParams,
        array('id = ? AND c_id = ?' => array($workId, $courseInfo['real_id']))
    );
}

/**
 * @param int $workId
 * @param array $params
 * @param array $courseInfo
 * @param int $groupId
 */
function updatePublicationAssignment($workId, $params, $courseInfo, $groupId)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
    $workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $workId = intval($workId);
    $time = time();
    $course_id = $courseInfo['real_id'];

    // Insert into agenda
    $agendaId = 0;

    if (isset($params['add_to_calendar']) && $params['add_to_calendar'] == 1) {
        require_once api_get_path(SYS_CODE_PATH).'calendar/agenda.inc.php';
        require_once api_get_path(SYS_CODE_PATH).'resourcelinker/resourcelinker.inc.php';

        // Setting today date
        $date = $end_date = $time;

        if (!empty($params['enableExpiryDate'])) {
            $end_date = $params['expires_on'];
            $date	  = $end_date;
        }

        $title = sprintf(get_lang('HandingOverOfTaskX'), $params['new_dir']);
        $description = isset($params['description']) ? $params['description'] : '';
        $content = '<a href="'.api_get_path(WEB_CODE_PATH).'work/work_list.php?'.api_get_cidreq().'&id='.$workId.'">'
            .$params['new_dir'].'</a>'.$description;

        $agendaId = agendaExistsForWork($workId, $courseInfo);

        // Add/edit agenda
        $agenda = new Agenda();
        $agenda->set_course($courseInfo);
        $agenda->type = 'course';

        if (empty($agendaId)) {
            $agendaId = $agenda->add_event(
                $date,
                $end_date,
                'false',
                $title,
                $content,
                array('GROUP:'.$groupId)
            );
        } else {
            $agenda->edit_event(
                $agendaId,
                $end_date,
                $end_date,
                'false',
                $title,
                $content
            );
        }
    }

    $qualification = isset($params['qualification']) && !empty($params['qualification']) ? 1 : 0;
    $expiryDate = (isset($params['enableExpiryDate']) && $params['enableExpiryDate'] == 1) ? api_get_utc_datetime($params['expires_on']) : '0000-00-00 00:00:00';
    $endDate = ((isset($params['enableEndDate']) && $params['enableEndDate']==1) ? api_get_utc_datetime($params['ends_on']) : '0000-00-00 00:00:00');

    $data = get_work_assignment_by_id($workId, $course_id);

    if (empty($data)) {

        $sql = "INSERT INTO $table SET
                c_id = $course_id ,
                expires_on       		= '".Database::escape_string($expiryDate)."',
                ends_on        	 		= '".Database::escape_string($endDate)."',
                add_to_calendar  		= $agendaId,
                enable_qualification 	= '$qualification',
                publication_id 			= '$workId'";
        Database::query($sql);

        $my_last_id = Database::insert_id();

        $sql = "UPDATE $workTable SET
                    has_properties  = $my_last_id ,
                    view_properties = 1
                WHERE c_id = $course_id AND id = $workId";
        Database::query($sql);
    } else {
        $sql = "UPDATE $table SET
                expires_on = '".$expiryDate."',
                ends_on = '".$endDate."',
                add_to_calendar  = $agendaId,
                enable_qualification = '".$qualification."'
            WHERE publication_id = $workId AND c_id = $course_id AND id = ".$data['id'];
        Database::query($sql);
    }

    if (!empty($params['category_id'])) {

        require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/gradebookitem.class.php';
        require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/evaluation.class.php';
        require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/abstractlink.class.php';
        require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';

        $link_info = is_resource_in_course_gradebook(
            $courseInfo['code'],
            LINK_STUDENTPUBLICATION,
            $workId,
            api_get_session_id()
        );

        $linkId = null;
        if (!empty($link_info)) {
            $linkId = $link_info['id'];
        }

        if (isset($params['make_calification']) && $params['make_calification'] == 1) {
            if (empty($linkId)) {
                add_resource_to_course_gradebook(
                    $params['category_id'],
                    $courseInfo['code'],
                    LINK_STUDENTPUBLICATION,
                    $workId,
                    $params['dir_name'],
                    (float)$params['weight'],
                    (float)$params['qualification'],
                    $params['description'],
                    1,
                    api_get_session_id()
                );
            } else {
                update_resource_from_course_gradebook($linkId, $courseInfo['code'], $params['weight']);
            }
        } else {
            // Delete everything of the gradebook for this $linkId
            remove_resource_from_course_gradebook($linkId);
        }
    }
}

/**
 * Delete all work by student
 * @param int $userId
 * @param array $courseInfo
 * @return array return deleted items
 */
function deleteAllWorkPerUser($userId, $courseInfo)
{
    $deletedItems = array();
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
 * @param int $item_id
 * @param array course info
 * @return bool
 */
function deleteWorkItem($item_id, $courseInfo)
{
    $work_table = Database :: get_course_table(TABLE_STUDENT_PUBLICATION);
    $TSTDPUBASG = Database :: get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);

    $currentCourseRepositorySys = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/';

    $is_allowed_to_edit = api_is_allowed_to_edit();
    $file_deleted = false;
    $item_id = intval($item_id);

    $is_author = user_is_author($item_id);
    $work_data = get_work_data_by_id($item_id);
    $locked = api_resource_is_locked_by_gradebook($work_data['parent_id'], LINK_STUDENTPUBLICATION);
    $course_id = $courseInfo['real_id'];

    if (($is_allowed_to_edit && $locked == false) ||
        ($locked == false AND $is_author && api_get_course_setting('student_delete_own_publication') == 1 && $work_data['qualificator_id'] == 0)) {
        //we found the current user is the author
        $sql = "SELECT url, contains_file FROM ".$work_table." WHERE c_id = $course_id AND id = $item_id";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        if (Database::num_rows($result) > 0) {
            $sql = "UPDATE $work_table SET active = 2 WHERE c_id = $course_id AND id = $item_id";
            Database::query($sql);
            $sql = "DELETE FROM $TSTDPUBASG WHERE c_id = $course_id AND publication_id = $item_id";
            Database::query($sql);

            api_item_property_update($courseInfo, 'work', $item_id, 'DocumentDeleted', api_get_user_id());
            $work = $row['url'];

            if ($row['contains_file'] == 1) {
                if (!empty($work)) {
                    if (api_get_setting('permanently_remove_deleted_files') == 'true') {
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
 * @param array $defaults
 * @return FormValidator
 */
function getFormWork($form, $defaults = array())
{
    if (!empty($defaults)) {
        if (isset($defaults['submit'])) {
            unset($defaults['submit']);
        }
    }
    // Create the form that asks for the directory name
    $form->addElement('text', 'new_dir', get_lang('AssignmentName'));
    $form->addRule('new_dir', get_lang('ThisFieldIsRequired'), 'required');
    $form->add_html_editor('description', get_lang('Description'), false, false, getWorkDescriptionToolbar());
    $form->addElement(
        'advanced_settings',
        '<a href="javascript: void(0);" onclick="javascript: return plus();">
        <span id="plus">'.
            Display::return_icon(
                'div_show.gif',
                get_lang('AdvancedParameters'),
                array('style' => 'vertical-align:center')
            ).
        ' '.get_lang('AdvancedParameters').
        '</span></a>'
    );

    if (!empty($defaults) && (isset($defaults['enableEndDate']) || isset($defaults['enableExpiryDate']))) {
        $form->addElement('html', '<div id="options" style="display: block;">');
    } else {
        $form->addElement('html', '<div id="options" style="display: none;">');
    }

    // QualificationOfAssignment
    $form->addElement('text', 'qualification', get_lang('QualificationNumeric'));

    if ((api_get_session_id() != 0 && Gradebook::is_active()) || api_get_session_id() == 0) {
        $form->addElement(
            'checkbox',
            'make_calification',
            null,
            get_lang('MakeQualifiable'),
            array(
                'id' =>'make_calification_id',
                'onclick' => "javascript: if(this.checked) { document.getElementById('option1').style.display='block';}else{document.getElementById('option1').style.display='none';}"
            )
        );
    } else {
        // QualificationOfAssignment
        $form->addElement('hidden', 'make_calification', false);
    }

    if (!empty($defaults) && isset($defaults['category_id'])) {
        $form->addElement('html', '<div id=\'option1\' style="display:block">');
    } else {
        $form->addElement('html', '<div id=\'option1\' style="display:none">');
    }

    // Loading Gradebook select
    load_gradebook_select_in_tool($form);

    $form->addElement('text', 'weight', get_lang('WeightInTheGradebook'));
    $form->addElement('html', '</div>');

    $form->addElement('checkbox', 'enableExpiryDate', null, get_lang('EnableExpiryDate'), 'id="expiry_date"');
    if (isset($defaults['enableExpiryDate']) && $defaults['enableExpiryDate']) {
        $form->addElement('html', '<div id="option2" style="display: block;">');
    } else {
        $form->addElement('html', '<div id="option2" style="display: none;">');
    }

    $currentDate = substr(api_get_local_time(), 0, 10);

    if (!isset($defaults['expires_on'])) {
        $date = substr($currentDate, 0, 10);
        $defaults['expires_on'] = $date.' 23:59';
    }

    if (!isset($defaults['ends_on'])) {
        $date = substr($currentDate, 0, 10);
        $defaults['ends_on'] = $date.' 23:59';
    }

    $form->addElement('date_time_picker', 'expires_on', get_lang('ExpiresAt'));
    $form->addElement('html', '</div>');

    $form->addElement('checkbox', 'enableEndDate', null, get_lang('EnableEndDate'), 'id="end_date"');

    if (isset($defaults['enableEndDate']) && $defaults['enableEndDate']) {
       $form->addElement('html', '<div id="option3" style="display: block;">');
    } else {
       $form->addElement('html', '<div id="option3" style="display: none;">');
    }

    $form->addElement('date_time_picker', 'ends_on', get_lang('EndsAt'));
    $form->addElement('html', '</div>');

    $form->addElement('checkbox', 'add_to_calendar', null, get_lang('AddToCalendar'));
    $form->addElement('select', 'allow_text_assignment', get_lang('DocumentType'), getUploadDocumentType());

    $form->addElement('html', '</div>');

    if (isset($defaults['enableExpiryDate']) && isset($defaults['enableEndDate'])) {
       $form->addRule(array('expires_on', 'ends_on'), get_lang('DateExpiredNotBeLessDeadLine'), 'comparedate');
    }
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
    return array(
        0 => get_lang('AllowFileOrText'),
        1 => get_lang('AllowOnlyText'),
        2 => get_lang('AllowOnlyFiles')
    );
}

/**
 * @param array $courseInfo
 * @param bool $showScore
 * @param bool $studentDeleteOwnPublication
 */
function updateSettings($courseInfo, $showScore, $studentDeleteOwnPublication)
{
    $showScore = intval($showScore);
    $courseId = api_get_course_int_id();
    $main_course_table 	= Database :: get_main_table(TABLE_MAIN_COURSE);
    $table_course_setting = Database :: get_course_table(TOOL_COURSE_SETTING);

    $query = "UPDATE ".$main_course_table." SET show_score = '".$showScore."'
              WHERE id = $courseId";
    Database::query($query);

    /**
    * Course data are cached in session so we need to update both the database
    * and the session data
    */
    $_course['show_score'] = $showScore;
    Session::write('_course', $courseInfo);

    // changing the tool setting: is a student allowed to delete his/her own document

    // counting the number of occurrences of this setting (if 0 => add, if 1 => update)
    $query = "SELECT * FROM " . $table_course_setting . "
              WHERE c_id = $courseId AND variable = 'student_delete_own_publication'";

    $result = Database::query($query);
    $number_of_setting = Database::num_rows($result);

    if ($number_of_setting == 1) {
        $query = "UPDATE " . $table_course_setting . " SET
                  value='" . Database::escape_string($studentDeleteOwnPublication) . "'
                  WHERE variable = 'student_delete_own_publication' AND c_id = $courseId";
        Database::query($query);
    } else {
        $query = "INSERT INTO " . $table_course_setting . " (c_id, variable, value, category) VALUES
                ($courseId, 'student_delete_own_publication' , '".Database::escape_string($studentDeleteOwnPublication) . "','work')";
        Database::query($query);
    }
}

/**
 * @param int $item_id
 * @param array $course_info
 */
function makeVisible($item_id, $course_info)
{
    $work_table = Database :: get_course_table(TABLE_STUDENT_PUBLICATION);
    $course_id = $course_info['real_id'];
    $item_id = intval($item_id);

    $sql = "UPDATE $work_table SET accepted = 1 WHERE c_id = $course_id AND id = $item_id";
    Database::query($sql);
    api_item_property_update($course_info, 'work', $item_id, 'visible', api_get_user_id());
}

/**
 * @param int $item_id
 * @param array $course_info
 */
function makeInvisible($item_id, $course_info)
{
    $work_table = Database :: get_course_table(TABLE_STUDENT_PUBLICATION);
    $item_id = intval($item_id);
    $course_id = $course_info['real_id'];
    $sql = "UPDATE  " . $work_table . " SET accepted = 0
            WHERE c_id = $course_id AND id = '" . $item_id . "'";
    Database::query($sql);
    api_item_property_update($course_info, 'work', $item_id, 'invisible', api_get_user_id());
}

/**
 * @param int $item_id
 * @param string $path
 * @param array $courseInfo
 * @param int $groupId
 * @param int $sessionId
 * @return string
 */
function generateMoveForm($item_id, $path, $courseInfo, $groupId, $sessionId)
{
    $work_table = Database :: get_course_table(TABLE_STUDENT_PUBLICATION);
    $courseId = $courseInfo['real_id'];
    $folders = array();
    $session_id = intval($sessionId);
    $sessionCondition = empty($sessionId) ? " AND session_id = 0 " : " AND session_id='".$session_id."'";
    $sql = "SELECT id, url, title FROM $work_table
            WHERE   c_id = $courseId AND
                    active IN (0, 1) AND
                    url LIKE '/%' AND
                    post_group_id = $groupId
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
 * @return string
 */
function showStudentList($workId)
{
    $columnModel = array(
        array('name'=>'student', 'index'=>'student', 'width'=>'150', 'align'=>'left', 'sortable' => 'false'),
        array('name'=>'works', 'index'=>'works',  'width'=>'50', 'align'=>'left', 'sortable' => 'false')
    );
    $token = null;

    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_work_student_list_overview&work_id='.$workId.'&'.api_get_cidreq();

    $columns = array(
        get_lang('Students'),
        get_lang('Works')
    );

    $order = api_is_western_name_order() ? 'firstname' : 'lastname';
    $params = array(
        'autowidth' => 'true',
        'height' => 'auto',
        'rowNum' => 10,
        'sortname' => $order,
        'sortorder' => 'asc'
    );

    $html = '<script>
    $(function() {
        '.Display::grid_js('studentList', $url, $columns, $columnModel, $params, array(), null, true).'
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
 * @param int $sessionId
 * @param int $groupId
 * @param int $start
 * @param int $limit
 * @param string $sidx
 * @param string $sord
 * @param $getCount
 * @return array|int
 */
function getWorkUserList($courseCode, $sessionId, $groupId, $start, $limit, $sidx, $sord, $getCount = false)
{
    if (!empty($groupId)) {
        $userList = GroupManager::get_users($groupId, false, $start, $limit, $getCount, null, $sidx, $sord);
    } else {
        $limitString = null;
        if (!empty($start) && !empty($limit)) {
            $limitString = " LIMIT $start, $limit";
        }

        $orderBy = null;

        if (!empty($sidx) && !empty($sord)) {
            $sidx = Database::escape_string($sidx);
            $sord = Database::escape_string($sord);
            $orderBy = "ORDER BY $sidx $sord";
        }

        if (empty($sessionId)) {
            $userList = CourseManager::get_user_list_from_course_code(
                $courseCode,
                $sessionId,
                $limitString,
                $orderBy ,
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
 * @param int $workId
 * @param string $courseCode
 * @param int $sessionId
 * @param int $groupId
 * @param int $start
 * @param int $limit
 * @param int $sidx
 * @param string $sord
 * @param bool $getCount
 * @return array|int
 */
function getWorkUserListData($workId, $courseCode, $sessionId, $groupId, $start, $limit, $sidx, $sord, $getCount = false)
{
    $my_folder_data = get_work_data_by_id($workId);
    $workParents = array();
    if (empty($my_folder_data)) {
        $workParents = getWorkList($workId, $my_folder_data, null);
    }

    $workIdList = array();
    if (!empty($workParents)) {
        foreach ($workParents as $work) {
            $workIdList[] = $work->id;
        }
    }

    $courseInfo = api_get_course_info($courseCode);
    $userList = getWorkUserList($courseCode, $sessionId, $groupId, $start, $limit, $sidx, $sord, $getCount);
    if ($getCount) {
        return $userList;
    }
    $results = array();
    if (!empty($userList)) {
        foreach ($userList as $userId) {
            $user = api_get_user_info($userId);
            $link = api_get_path(WEB_CODE_PATH).'work/student_work.php?'.api_get_cidreq().'&studentId='.$user['user_id'];
            $url = Display::url(api_get_person_name($user['firstname'], $user['lastname']), $link);
            $userWorks = 0;
            if (!empty($workIdList)) {
                $userWorks = getUniqueStudentAttempts($workIdList, $groupId, $courseInfo['real_id'], $sessionId, $user['user_id']);
            }
            $works = $userWorks." / ".count($workParents);
            $results[] = array('student' => $url, 'works' => $works);
        }
    }
    return $results;
}

/**
 * @param int $id
 * @param array $course_info
 * @param bool $download
 * @return bool
 */
function getFile($id, $course_info, $download = true)
{
    $file = getFileContents($id, $course_info);
    if (!empty($file) && is_array($file)) {
        return DocumentManager::file_send_for_download($file['path'], $download, $file['title']);
    }
    return false;
}

/**
 * @param int $id
 * @param array $course_info
 * @return bool
 */
function downloadFile($id, $course_info)
{
    return getFile($id, $course_info, true);
}

/**
 * @param int $id
 * @param array $course_info
 * @return array|bool
 */
function getFileContents($id, $course_info)
{
    $id = intval($id);
    if (empty($course_info) || empty($id)) {
        return false;
    }

    $tbl_student_publication = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

    if (!empty($course_info['real_id'])) {
        $sql = 'SELECT * FROM '.$tbl_student_publication.'
                WHERE c_id = '.$course_info['real_id'].' AND id = "'.$id.'"';
        $result = Database::query($sql);
        if ($result && Database::num_rows($result)) {
            $row = Database::fetch_array($result, 'ASSOC');
            $full_file_name = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/'.$row['url'];

            $item_info = api_get_item_property_info(api_get_course_int_id(), 'work', $row['id']);
            allowOnlySubscribedUser(api_get_user_id(), $row['parent_id'], $course_info['real_id']);

            if (empty($item_info)) {
                api_not_allowed();
            }

            /*
            field show_score in table course :
                0 => 	New documents are visible for all users
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

            $work_is_visible = ($item_info['visibility'] == 1 && $row['accepted'] == 1);
            $doc_visible_for_all = ($course_info['show_score'] == 1);

            $is_editor = api_is_allowed_to_edit(true, true, true);
            $student_is_owner_of_work = user_is_author($row['id'], $row['user_id']);

            if ($is_editor ||
                ($student_is_owner_of_work) ||
                ($doc_visible_for_all && $work_is_visible)
            ) {
                $title = $row['title'];
                if (array_key_exists('filename', $row) && !empty($row['filename'])) {
                    $title = $row['filename'];
                }
                $title = str_replace(' ', '_', $title);
                event_download($title);
                if (Security::check_abs_path(
                    $full_file_name,
                    api_get_path(SYS_COURSE_PATH).api_get_course_path().'/')
                ) {
                    return array(
                        'path' => $full_file_name,
                        'title' => $title
                    );
                }
            }
        }
    }

    return false;
}

/**
 * @param int $userId
 * @param array $courseInfo
 * @param string $format
 * @return bool
 */
function exportAllWork($userId, $courseInfo, $format = 'pdf') {
    $userInfo = api_get_user_info($userId);
    if (empty($userInfo) || empty($courseInfo)) {
        return false;
    }

    $workPerUser = getWorkPerUser($userId);

    switch ($format) {
        case 'pdf':
            if (!empty($workPerUser)) {
                require_once api_get_path(LIBRARY_PATH).'pdf.lib.php';
                $pdf = new PDF();

                $content = null;
                foreach ($workPerUser as $work) {
                    $work = $work['work'];
                    foreach ($work->user_results as $userResult) {
                        //var_dump($userResult);exit;
                        $content .= $userResult['title'];
                        $content .= $userResult['sent_date'];
                        $content .= $userResult['qualification'];
                        $content .= $userResult['description'];
                        //$content .= getWorkComments($userResult);
                    }
                }

                if (!empty($content)) {
                    $pdf->content_to_pdf(
                        $content,
                        null,
                        replace_dangerous_char($userInfo['complete_name']),
                        $courseInfo['code']
                    );
                }
            }
            break;
    }
}

/**
 * @param int $userId
 * @param array $courseInfo
 * @return bool
 */
function downloadAllFilesPerUser($userId, $courseInfo)
{
    $userInfo = api_get_user_info($userId);

    if (empty($userInfo) || empty($courseInfo)) {
        return false;
    }

    require_once api_get_path(LIBRARY_PATH).'pclzip/pclzip.lib.php';
    $tempZipFile = api_get_path(SYS_ARCHIVE_PATH).api_get_unique_id().".zip";
    $coursePath = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/work/';

    $zip  = new PclZip($tempZipFile);

    $workPerUser = getWorkPerUser($userId);

    if (!empty($workPerUser)) {
        $files = array();
        foreach ($workPerUser as $work) {
            $work = $work['work'];
            foreach ($work->user_results as $userResult) {
                if (empty($userResult['url']) || empty($userResult['contains_file'])) {
                    continue;
                }
                $data = getFileContents($userResult['id'], $courseInfo);
                if (!empty($data) && isset($data['path'])) {
                    $files[basename($data['path'])] = array(
                        'title' => $data['title'],
                        'path' => $data['path']
                    );
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
        $name = basename(replace_dangerous_char($userInfo['complete_name'])).'.zip';
        event_download($name.'.zip (folder)');
        if (Security::check_abs_path($tempZipFile, api_get_path(SYS_ARCHIVE_PATH))) {
            DocumentManager::file_send_for_download($tempZipFile, true, $name);
            @unlink($tempZipFile);
            exit;
        }
    }
    exit;
}

function preAddAllWorkStudentCallback($p_event, &$p_header)
{
    $files = Session::read('files');
    if (isset($files[basename($p_header['stored_filename'])])) {
        $p_header['stored_filename'] = $files[basename($p_header['stored_filename'])]['title'];
        return 1;
    }
    return 0;
}

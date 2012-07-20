<?php
/* For licensing terms, see /license.txt */

/**
* This file contains additional dropbox functions. Initially there were some
* functions in the init files also but I have moved them over
* to one file 		-- Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/

//require_once '../inc/global.inc.php';

$this_section = SECTION_COURSES;

$htmlHeadXtra[] = '<script type="text/javascript">
function setFocus(){
    $("#category_title").focus();
}
$(document).ready(function () {
    setFocus();
});
</script>';

/**
* This function is a wrapper function for the multiple actions feature.
* @return	Mixed	If there is a problem, return a string message, otherwise nothing
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function handle_multiple_actions() {
	global $_user, $is_courseAdmin, $is_courseTutor;

	// STEP 1: are we performing the actions on the received or on the sent files?
	if ($_POST['action'] == 'delete_received' || $_POST['action'] == 'download_received') {
		$part = 'received';
	} elseif ($_POST['action'] == 'delete_sent' || $_POST['action'] == 'download_sent') {
		$part = 'sent';
	}

	// STEP 2: at least one file has to be selected. If not we return an error message
    $ids = Request::get('id', array());
    if(count($ids)>0){        
        $checked_file_ids = $_POST['id'];
    }
    else{      
        foreach ($_POST as $key => $value) {
            if (strstr($value, $part.'_') AND $key != 'view_received_category' AND $key != 'view_sent_category') {
                $checked_files = true;
                $checked_file_ids[] = intval(substr($value, strrpos($value, '_')));
            }
        }
    }
	$checked_file_ids = $_POST['id'];

	if (!is_array($checked_file_ids) || count($checked_file_ids) == 0) {
		return get_lang('CheckAtLeastOneFile');
	}

	// STEP 3A: deleting
	if ($_POST['action'] == 'delete_received' || $_POST['action'] == 'delete_sent') {
		$dropboxfile = new Dropbox_Person($_user['user_id'], $is_courseAdmin, $is_courseTutor);
		foreach ($checked_file_ids as $key => $value) {
			if ($_GET['view'] == 'received') {
				$dropboxfile->deleteReceivedWork($value);
				$message = get_lang('ReceivedFileDeleted');
			}
			if ($_GET['view'] == 'sent' OR empty($_GET['view'])) {
				$dropboxfile->deleteSentWork($value);
				$message = get_lang('SentFileDeleted');
			}
		}
		return $message;
	}

	// STEP 3B: giving comment
	if ($_POST['actions'] == 'comment') {
		// This has not been implemented.
		// The idea was that it would be possible to write the same feedback for the selected documents.
	}

	// STEP 3C: moving
	if (strstr($_POST['action'], 'move_')) {
        	// check move_received_n or move_sent_n command
		if (strstr($_POST['action'], 'received')) {
              $part = 'received';
              $to_cat_id = str_replace('move_received_', '', $_POST['action']);
        } else {
              $part = 'sent';
              $to_cat_id = str_replace('move_sent_', '', $_POST['action']);
        }

		foreach ($checked_file_ids as $key => $value) {
			store_move($value, $to_cat_id, $part);
		}
		return get_lang('FilesMoved');
    }

	// STEP 3D: downloading
	if ($_POST['action'] == 'download_sent' || $_POST['action'] == 'download_received') {
		zip_download($checked_file_ids);
	}
}

/**
* This function deletes a dropbox category
*
* @todo give the user the possibility what needs to be done with the files in this category: move them to the root, download them as a zip, delete them
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function delete_category($action, $id, $user_id = null) {
    $course_id = api_get_course_int_id();

	global $dropbox_cnf;
	global $is_courseAdmin, $is_courseTutor;
    
    if (empty($user_id)) {
        $user_id = api_get_user_id();
    }
    
    $cat = get_dropbox_category($id);
    if (count($cat)==0) { return false; }
    if ($cat['user_id'] != $user_id && !api_is_platform_admin($user_id)) {
        return false;
    }

	// an additional check that might not be necessary
	if ($action == 'deletereceivedcategory') {
		$sentreceived = 'received';
		$entries_table = $dropbox_cnf['tbl_post'];
		$id_field = 'file_id';
		$return_message = get_lang('ReceivedCatgoryDeleted');
	} elseif ($action == 'deletesentcategory') {
		$sentreceived = 'sent';
		$entries_table = $dropbox_cnf['tbl_file'];
		$id_field = 'id';
		$return_message = get_lang('SentCatgoryDeleted');
	} else {
		return get_lang('Error');
	}

	// step 1: delete the category
	$sql = "DELETE FROM ".$dropbox_cnf['tbl_category']." WHERE c_id = $course_id AND cat_id='".intval($id)."' AND $sentreceived='1'";
	$result = Database::query($sql);

	// step 2: delete all the documents in this category
	$sql = "SELECT * FROM ".$entries_table." WHERE c_id = $course_id AND cat_id='".intval($id)."'";
	$result = Database::query($sql);

	while($row = Database::fetch_array($result)) {
		$dropboxfile = new Dropbox_Person($_user['user_id'], $is_courseAdmin, $is_courseTutor);
		if ($action == 'deletereceivedcategory') {
			$dropboxfile->deleteReceivedWork($row[$id_field]);
		}
		if ($action == 'deletesentcategory') {
			$dropboxfile->deleteSentWork($row[$id_field]);
		}
	}
	return $return_message;
}

/**
* Displays the form to move one individual file to a category
*
* @return html code of the form that appears in a message box.
*
* @author Julio Montoya - function rewritten

*/
function display_move_form($part, $id, $target = array(), $extra_params = array()) {
    $form = new FormValidator('form1', 'post', api_get_self().'?view_received_category='.Security::remove_XSS($_GET['view_received_category']).'&view_sent_category='.Security::remove_XSS($_GET['view_sent_category']).'&view='.Security::remove_XSS($_GET['view']).'&'.$extra_params);
	$form->addElement('header', get_lang('MoveFileTo'));
    $form->addElement('hidden', 'id', intval($id));
    $form->addElement('hidden', 'part', Security::remove_XSS($part));

    $options = array('0' => get_lang('Root'));
	foreach ($target as $category) {
        $options[$category['cat_id']] = $category['cat_name'];
	}
    $form->addElement('select', 'move_target', get_lang('MoveFileTo'), $options);
    $form->addElement('button', 'do_move', get_lang('MoveFile'));
    $form->display();

}

/**
* This function moves a file to a different category
*
* @param $id the id of the file we are moving
* @param $target the id of the folder we are moving to
* @param $part are we moving a received file or a sent file?
*
* @return language string
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function store_move($id, $target, $part) {
	global $_user, $dropbox_cnf;
    $course_id = api_get_course_int_id();

	if ((isset($id) AND $id != '') AND (isset($target) AND $target != '') AND (isset($part) AND $part != '')) {
        
		if ($part == 'received') {
			$sql = "UPDATE ".$dropbox_cnf["tbl_post"]." SET cat_id='".Database::escape_string($target)."'
						WHERE c_id = $course_id AND dest_user_id='".Database::escape_string($_user['user_id'])."'
						AND file_id='".Database::escape_string($id)."'";
			Database::query($sql);
			$return_message = get_lang('ReceivedFileMoved');
		}
		if ($part == 'sent') {
			$sql = "UPDATE ".$dropbox_cnf["tbl_file"]." SET cat_id='".Database::escape_string($target)."'
						WHERE c_id = $course_id AND uploader_id='".Database::escape_string($_user['user_id'])."'
						AND id='".Database::escape_string($id)."'";
			Database::query($sql);
			$return_message = get_lang('SentFileMoved');
		}
	} else {
		$return_message = get_lang('NotMovedError');
	}
	return $return_message;
}

/**
* This functions displays all teh possible actions that can be performed on multiple files. This is the dropdown list that
* appears below the sortable table of the sent / or received files.
*
* @return html value for the dropdown list
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function display_action_options($part, $categories, $current_category = 0) {
	echo '<select name="actions">';
	echo '<option value="download">'.get_lang('Download').'</option>';
	echo '<option value="delete">'.get_lang('Delete').'</option>';
	if (is_array($categories)) {
		echo '<optgroup label="'.get_lang('MoveTo').'">';
		if ($current_category != 0) {
			echo '<option value="move_0">'.get_lang('Root').'</a>';
		}
		foreach ($categories as $key => $value) {
			if ($current_category != $value['cat_id']) {
				echo '<option value="move_'.$value['cat_id'].'">'.$value['cat_name'].'</option>';
			}
		}
		echo '</optgroup>';
	}
	echo '</select>';
	echo '<input type="submit" name="do_actions_'.Security::remove_XSS($part).'" value="'.get_lang('Ok').'" />';
}

/**
* this function returns the html code that displays the checkboxes next to the files so that
* multiple actions on one file are possible.
*
* @param $id the unique id of the file
* @param $part are we dealing with a sent or with a received file?
*
* @return html code
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function display_file_checkbox($id, $part) {
	if (isset($_GET['selectall'])) {
		$checked = 'checked';
	}
	$return_value = '<input type="checkbox" name="'.Security::remove_XSS($part).'_'.Security::remove_XSS($id).'" value="'.Security::remove_XSS($id).'" '.$checked.' />';
	return $return_value;
}

/**
* This function retrieves all dropbox categories and returns them as an array
*
* @param $filter default '', when we need only the categories of the sent or the received part.
*
* @return array
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function get_dropbox_categories($filter = '') {
    $course_id = api_get_course_int_id();
	global $_user;
	global $dropbox_cnf;

	$return_array = array();

	$session_id = api_get_session_id();
	$condition_session = api_get_session_condition($session_id);

	$sql = "SELECT * FROM ".$dropbox_cnf['tbl_category']." WHERE c_id = $course_id AND user_id='".$_user['user_id']."' $condition_session";

	$result = Database::query($sql);
	while ($row = Database::fetch_array($result)) {
		if (($filter == 'sent' AND $row['sent'] == 1) OR ($filter == 'received' AND $row['received'] == 1) OR $filter == '') {
			$return_array[$row['cat_id']] = $row;
		}
	}

	return $return_array;
}

/**
 * Get a dropbox category details
 * @param int The category ID
 * @return array The details of this category
 */
function get_dropbox_category($id) {
    global $dropbox_cnf;    
    if (empty($id) or $id != intval($id)) { return array(); }    
    $sql = "SELECT * FROM ".$dropbox_cnf['tbl_category']." WHERE cat_id='".$id."'";
    $res = Database::query($sql);
    if ($res === false) {
        return array();
    }
    $row = Database::fetch_assoc($res);
    return $row;
}

/**
* This functions stores a new dropboxcategory
*
* @var 	it might not seem very elegant if you create a category in sent and in received with the same name that you get two entries in the
*		dropbox_category table but it is the easiest solution. You get
*		cat_name | received | sent | user_id
*		test	 |	  1		|	0  |	237
*		test	 |	  0		|	1  |	237
*		more elegant would be
*		test	 |	  1		|	1  |	237
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function store_addcategory() {
    $course_id = api_get_course_int_id();
	global $_user;
	global $dropbox_cnf;

	// check if the target is valid
	if ($_POST['target'] == 'sent') {
		$sent = 1;
		$received = 0;
	} elseif ($_POST['target'] == 'received') {
		$sent = 0;
		$received = 1;
	} else {
		return get_lang('Error');
	}

	// check if the category name is valid
	if ($_POST['category_name'] == '') {
		return array('type' => 'error', 'message' => get_lang('ErrorPleaseGiveCategoryName'));
	}

	if (!$_POST['edit_id']) {
		$session_id = api_get_session_id();
		// step 3a, we check if the category doesn't already exist
		$sql = "SELECT * FROM ".$dropbox_cnf['tbl_category']." WHERE c_id = $course_id AND user_id='".$_user['user_id']."' AND cat_name='".Database::escape_string($_POST['category_name'])."' AND received='".$received."' AND sent='$sent' AND session_id='$session_id'";
		$result = Database::query($sql);

		// step 3b, we add the category if it does not exist yet.
		if (Database::num_rows($result) == 0) {
			$sql = "INSERT INTO ".$dropbox_cnf['tbl_category']." (c_id, cat_name, received, sent, user_id, session_id)
					VALUES ($course_id, '".Database::escape_string($_POST['category_name'])."', '".Database::escape_string($received)."', '".Database::escape_string($sent)."', '".Database::escape_string($_user['user_id'])."',$session_id)";
			Database::query($sql);
			return array('type' => 'confirmation', 'message' => get_lang('CategoryStored'));
		} else {
			return array('type' => 'error', 'message' => get_lang('CategoryAlreadyExistsEditIt'));
		}
	} else {
		$sql = "UPDATE ".$dropbox_cnf['tbl_category']." SET cat_name='".Database::escape_string($_POST['category_name'])."', received='".Database::escape_string($received)."' , sent='".Database::escape_string($sent)."'
				WHERE c_id = $course_id AND user_id='".Database::escape_string($_user['user_id'])."'
				AND cat_id='".Database::escape_string($_POST['edit_id'])."'";
		Database::query($sql);
		return array('type' => 'confirmation', 'message' => get_lang('CategoryModified'));
	}
}

/**
* This function displays the form to add a new category.
*
* @param $category_name this parameter is the name of the category (used when no section is selected)
* @param $id this is the id of the category we are editing.
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
  @author Julio Montoya UI changes
 *
* @version march 2006
*/
function display_addcategory_form($category_name = '', $id = '', $action) {
	global $dropbox_cnf;
    $course_id = api_get_course_int_id();
	$title = get_lang('AddNewCategory');

	if (isset($id) AND $id != '') {
		// retrieve the category we are editing
		$sql = "SELECT * FROM ".$dropbox_cnf['tbl_category']." WHERE c_id = $course_id AND cat_id='".Database::escape_string($id)."'";
		$result = Database::query($sql);
		$row = Database::fetch_array($result);

		if (empty($category_name)) { // after an edit with an error we do not want to return to the original name but the name we already modified. (happens when createinrecievedfiles AND createinsentfiles are not checked)
			$category_name = $row['cat_name'];
		}
		if ($row['received'] == '1') {
			$target = 'received';
		}
		if ($row['sent'] == '1') {
			$target = 'sent';
		}
		$title = get_lang('EditCategory');
	}

	if ($action == 'addreceivedcategory') {
		$target = 'received';
	}
	if ($action == 'addsentcategory') {
		$target = 'sent';
	}

	if ($action == 'editcategory') {
		$text = get_lang('ModifyCategory');
		$class = 'save';
	} elseif ($action == 'addreceivedcategory' or $action == 'addsentcategory') {
		$text = get_lang('CreateCategory');
		$class = 'add';
	}

    $form = new FormValidator('add_new_category', 'post', api_get_self().'?view="'.Security::remove_XSS($_GET['view']));
    $form->addElement('header', $title);

	if (isset($id) AND $id != '') {
        $form->addElement('hidden', 'edit_id', intval($id));
	}
    $form->addElement('hidden', 'action', Security::remove_XSS($action));
    $form->addElement('hidden', 'target', Security::remove_XSS($target));

    $form->addElement('text', 'category_name', get_lang('CategoryName'));
    $form->addRule('category_name', get_lang('ThisFieldIsRequired'), 'required');
    $form->addElement('button', 'StoreCategory', $text);

    $defaults = array();
    $defaults['category_name'] = $category_name;
    $form->setDefaults($defaults);
    $form->display();
}

/**
* this function displays the form to upload a new item to the dropbox.
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function display_add_form() {
	global $_user, $is_courseAdmin, $is_courseTutor, $course_info, $origin, $dropbox_unid;

	$token = Security::get_token();
	$dropbox_person = new Dropbox_Person(api_get_user_id(), $is_courseAdmin, $is_courseTutor);
	?>
	<form method="post" action="index.php?view_received_category=<?php echo Security::remove_XSS($_GET['view_received_category']); ?>&view_sent_category=<?php echo Security::remove_XSS($_GET['view_sent_category']); ?>&view=<?php echo Security::remove_XSS($_GET['view']); ?>&<?php echo "origin=$origin"."&".api_get_cidreq(); ?>" enctype="multipart/form-data" onsubmit="javascript: return checkForm(this);">
	<legend><?php echo get_lang('UploadNewFile'); ?></legend>

	<div class="control-group">
		<label>
			<span class="form_required">*</span><?php echo get_lang('UploadFile'); ?>:
		</label>
		<div class="controls">
				<input type="hidden" name="MAX_FILE_SIZE" value='<?php echo dropbox_cnf('maxFilesize'); ?>' />
				<input type="file" name="file" size="20" <?php if (dropbox_cnf('allowOverwrite')) echo 'onChange="javascript: checkfile(this.value);"'; ?> />
				<input type="hidden" name="dropbox_unid" value="<?php echo $dropbox_unid; ?>" />
				<input type="hidden" name="sec_token" value="<?php echo $token; ?>" />
				<?php
				if ($origin == 'learnpath') {
					echo '<input type="hidden" name="origin" value="learnpath" />';
				}
				?>
		</div>
	</div>

	<?php
	if (dropbox_cnf('allowOverwrite')) {
		?>
		<div class="control-group">
			<div class="controls">
				<label class="checkbox">
                    <input type="checkbox" name="cb_overwrite" id="cb_overwrite" value="true" />
				<?php echo get_lang('OverwriteFile'); ?>
				</label>
			</div>
		</div>
		<?php
	}
	?>

	<div class="control-group">
		<label class="control-label">
			<?php echo get_lang('SendTo'); ?>
		</label>
		<div class="controls">
	<?php

	//list of all users in this course and all virtual courses combined with it
	if (api_get_session_id()) {
		$complete_user_list_for_dropbox = array();
		if (api_get_setting('dropbox_allow_student_to_student')=='true' || $_user['status'] != STUDENT) {
			$complete_user_list_for_dropbox = CourseManager :: get_user_list_from_course_code($course_info['code'], api_get_session_id());
		}
		$complete_user_list2 = CourseManager::get_coach_list_from_course_code($course_info['code'], api_get_session_id());
		$complete_user_list_for_dropbox = array_merge($complete_user_list_for_dropbox, $complete_user_list2);
	} else {
		if (api_get_setting('dropbox_allow_student_to_student') == 'true' || $_user['status'] != STUDENT) {
			$complete_user_list_for_dropbox = CourseManager :: get_user_list_from_course_code($course_info['code'], api_get_session_id());
		} else {
			$complete_user_list_for_dropbox = CourseManager :: get_teacher_list_from_course_code($course_info['code'], false);
		}
	}

    if (!empty($complete_user_list_for_dropbox)) {
    	foreach ($complete_user_list_for_dropbox as $k => $e) {
    	    $complete_user_list_for_dropbox[$k] = $e + array('lastcommafirst' => api_get_person_name($e['firstname'], $e['lastname']));
    	}
    	$complete_user_list_for_dropbox = TableSort::sort_table($complete_user_list_for_dropbox, 'lastcommafirst');
    }

	?>

    <select name="recipients[]" size="
	<?php
	if ($dropbox_person -> isCourseTutor || $dropbox_person -> isCourseAdmin) {
		echo 10;
	} else {
		echo 6;
	}

	?>" multiple style="width: 350px;">
	<?php

	/*
		Create the options inside the select box:
		List all selected users their user id as value and a name string as display
	*/

	$current_user_id = '';
	foreach ($complete_user_list_for_dropbox as $current_user) {
		if (($dropbox_person -> isCourseTutor
				|| $dropbox_person -> isCourseAdmin
				|| dropbox_cnf('allowStudentToStudent')
				|| $current_user['status'] != 5							// Always allow teachers.
				|| $current_user['tutor_id'] == 1						// Always allow tutors.
				) && $current_user['user_id'] != $_user['user_id']) {	// Don't include yourself.
			if ($current_user['user_id'] == $current_user_id) {
				continue;
			}
			$full_name = $current_user['lastcommafirst'];
			$current_user_id = $current_user['user_id'];
			echo '<option value="user_' . $current_user_id . '">' . $full_name . '</option>';
		}
	}

	/*
	* Show groups
	*/
    if (($dropbox_person -> isCourseTutor || $dropbox_person -> isCourseAdmin)
    	&& dropbox_cnf('allowGroup') || dropbox_cnf('allowStudentToStudent')) {
		$complete_group_list_for_dropbox = GroupManager::get_group_list(null, dropbox_cnf('courseId'));

		if (count($complete_group_list_for_dropbox) > 0) {
			foreach ($complete_group_list_for_dropbox as $current_group) {
				if ($current_group['number_of_members'] > 0) {
					echo '<option value="group_'.$current_group['id'].'">G: '.$current_group['name'].' - '.$current_group['number_of_members'].' '.get_lang('Users').'</option>';
				}
			}
		}
    }

    if (($dropbox_person -> isCourseTutor || $dropbox_person -> isCourseAdmin) && dropbox_cnf('allowMailing')) {
		// echo '<option value="mailing">'.get_lang('MailingInSelect').'</option>';
	}

    if (dropbox_cnf('allowJustUpload')) {
    	//echo '<option value="upload">'.get_lang('JustUploadInSelect').'</option>';
    	echo '<option value="user_'.$_user['user_id'].'">'.get_lang('JustUploadInSelect').'</option>';
    }

	echo '</select>
		</div>
	</div>';

	echo '
		<div class="control-group">
			<div class="controls">
				<button type="Submit" class="upload" name="submitWork">'.get_lang('Upload', '').'</button>
			</div>
		</div>
	';

	echo '</form>';
}

/**
* returns username or false if user isn't registered anymore
* @todo check if this function is still necessary. There might be a library function for this.
*/
function getUserNameFromId($id) {
	global $dropbox_cnf;

    $mailingId = $id - dropbox_cnf('mailingIdBase');
    if ($mailingId > 0) {
	    return get_lang('MailingAsUsername', '') . $mailingId;
    }
    $id = intval($id);
    $sql = "SELECT ".(api_is_western_name_order() ? "CONCAT(firstname,' ', lastname)" : "CONCAT(lastname,' ', firstname)")." AS name
			FROM " . $dropbox_cnf['tbl_user'] . "
			WHERE user_id='$id'";
    $result = Database::query($sql);
    $res = Database::fetch_array($result);

    if (!$res) return false;
    return stripslashes($res['name']);
}

/**
* returns loginname or false if user isn't registered anymore
* @todo check if this function is still necessary. There might be a library function for this.
*/
function getLoginFromId($id) {
    $id = intval($id);
    $sql = "SELECT username
			FROM " . dropbox_cnf('tbl_user') . "
			WHERE user_id='$id'";
    $result = Database::query($sql);
    $res = Database::fetch_array($result);
    if (!$res) return false;
    return stripslashes($res['username']);
}

/**
* @return boolean indicating if user with user_id=$user_id is a course member
* @todo eliminate global
* @todo check if this function is still necessary. There might be a library function for this.
*/
function isCourseMember($user_id) {
    global $_course;
	$course_code = $_course['sysCode'];
	$is_course_member = CourseManager::is_user_subscribed_in_course($user_id, $course_code, true);
	return $is_course_member;
}

/**
* Checks if there are files in the dropbox_file table that aren't used anymore in dropbox_person table.
* If there are, all entries concerning the file are deleted from the db + the file is deleted from the server
*/
function removeUnusedFiles() {
    $course_id = api_get_course_int_id();

    // select all files that aren't referenced anymore
    $sql = "SELECT DISTINCT f.id, f.filename
			FROM " . dropbox_cnf('tbl_file') . " f
			LEFT JOIN " . dropbox_cnf('tbl_person') . " p ON f.id = p.file_id
			WHERE f.c_id = $course_id AND p.c_id = $course_id AND p.user_id IS NULL";
    $result = Database::query($sql);
    while ($res = Database::fetch_array($result)) {
		//delete the selected files from the post and file tables
        $sql = "DELETE FROM " . dropbox_cnf('tbl_post') . " WHERE c_id = $course_id AND file_id='" . $res['id'] . "'";
        $result1 = Database::query($sql);
        $sql = "DELETE FROM " . dropbox_cnf('tbl_file') . " WHERE c_id = $course_id AND id='" . $res['id'] . "'";
        $result1 = Database::query($sql);

		//delete file from server
        @unlink( dropbox_cnf('sysPath') . '/' . $res['filename']);
    }
}

/**
*
* Mailing zip-file is posted to (dest_user_id = ) mailing pseudo_id
* and is only visible to its uploader (user_id).
*
* Mailing content files have uploader_id == mailing pseudo_id, a normal recipient,
* and are visible initially to recipient and pseudo_id.
*
* @author René Haentjens, Ghent University
*
* @todo check if this function is still necessary.
*/
function getUserOwningThisMailing($mailingPseudoId, $owner = 0, $or_die = '') {
    $course_id = api_get_course_int_id();

	global $dropbox_cnf;
    $mailingPseudoId = intval($mailingPseudoId);
    $sql = "SELECT f.uploader_id
			FROM " . $dropbox_cnf['tbl_file'] . " f
			LEFT JOIN " . $dropbox_cnf['tbl_post'] . " p ON f.id = p.file_id
			WHERE f.c_id = $course_id AND p.c_id = $course_id AND
			p.dest_user_id = '" . $mailingPseudoId . "'";
    $result = Database::query($sql);

    if (!($res = Database::fetch_array($result)))
        die(get_lang('GeneralError').' (code 901)');
    if ($owner == 0) return $res['uploader_id'];
    if ($res['uploader_id'] == $owner) return true;
    die(get_lang('GeneralError').' (code '.$or_die.')');
}

/**
* @author René Haentjens, Ghent University
* @todo check if this function is still necessary.
*/
function removeMoreIfMailing($file_id) {
    $course_id = api_get_course_int_id();
	global $dropbox_cnf;
    // when deleting a mailing zip-file (posted to mailingPseudoId):
    // 1. the detail window is no longer reachable, so
    //    for all content files, delete mailingPseudoId from person-table
    // 2. finding the owner (getUserOwningThisMailing) is no longer possible, so
    //    for all content files, replace mailingPseudoId by owner as uploader
    $file_id = intval($file_id);
    $sql = "SELECT p.dest_user_id
			FROM " . $dropbox_cnf['tbl_post'] . " p
			WHERE c_id = $course_id AND p.file_id = '" . $file_id . "'";
    $result = Database::query($sql);

    if ($res = Database::fetch_array($result)) {
	    $mailingPseudoId = $res['dest_user_id'];
	    if ($mailingPseudoId > dropbox_cnf('mailingIdBase')) {
	        $sql = "DELETE FROM " . dropbox_cnf('tbl_person') . " WHERE c_id = $course_id AND user_id='" . $mailingPseudoId . "'";
	        $result1 = Database::query($sql);

	        $sql = "UPDATE " . dropbox_cnf('tbl_file') .
	            " SET uploader_id='" . api_get_user_id() . "' WHERE c_id = $course_id AND uploader_id='" . $mailingPseudoId . "'";
	        $result1 = Database::query($sql);
        }
    }
}


/**
* Function that finds a given config setting
*
* @author René Haentjens, Ghent University
*/
function dropbox_cnf($variable) {
    return $GLOBALS['dropbox_cnf'][$variable];
}


/**
*
*/
function store_add_dropbox() {
	global $dropbox_cnf;
	global $_user;
	global $_course;

	// Validating the form data

	// the author is
	/*
    if (!isset($_POST['authors'])) {
        return get_lang('AuthorFieldCannotBeEmpty');
    }
    */

    // there are no recipients selected
	if (!isset($_POST['recipients']) || count( $_POST['recipients']) <= 0) {
        return get_lang('YouMustSelectAtLeastOneDestinee');
    }
    // Check if all the recipients are valid
    else {
        $thisIsAMailing = false;
        $thisIsJustUpload = false;
	    foreach ($_POST['recipients'] as $rec) {
			if ($rec == 'mailing') {
				$thisIsAMailing = true;
			} elseif ($rec == 'upload') {
				$thisIsJustUpload = true;
			} elseif (strpos($rec, 'user_') === 0 && !isCourseMember(substr($rec, strlen('user_')))) {
		        return get_lang('InvalideUserDetected');
			} elseif (strpos($rec, 'group_') !== 0 && strpos($rec, 'user_') !== 0) {
				return get_lang('InvalideGroupDetected');
			}
        }
    }

	// we are doing a mailing but an additional recipient is selected
	if ($thisIsAMailing && (count($_POST['recipients']) != 1)) {
		return get_lang('MailingSelectNoOther');
	}

	// we are doing a just upload but an additional recipient is selected.
	// note: why can't this be valid? It is like sending a document to yourself AND to a different person (I do this quite often with my e-mails)
	if ($thisIsJustUpload && (count($_POST['recipients']) != 1)) {
		return get_lang('MailingJustUploadSelectNoOther');
	}

	if (empty($_FILES['file']['name'])) {
		$error = true;
		return get_lang('NoFileSpecified');
	}


	// are we overwriting a previous file or sending a new one

	$dropbox_overwrite = false;
	if (isset($_POST['cb_overwrite']) && $_POST['cb_overwrite']) {
		$dropbox_overwrite = true;
	}


	// doing the upload

	$dropbox_filename = $_FILES['file']['name'];
	$dropbox_filesize = $_FILES['file']['size'];
	$dropbox_filetype = $_FILES['file']['type'];
	$dropbox_filetmpname = $_FILES['file']['tmp_name'];

	// check if the filesize does not exceed the allowed size.
	if ($dropbox_filesize <= 0 || $dropbox_filesize > $dropbox_cnf['maxFilesize']) {
		return get_lang('DropboxFileTooBig'); // TODO: The "too big" message does not fit in the case of uploading zero-sized file.
	}

	// check if the file is actually uploaded
	if (!is_uploaded_file($dropbox_filetmpname)) { // check user fraud : no clean error msg.
		return get_lang('TheFileIsNotUploaded');
	}

	// Try to add an extension to the file if it hasn't got one
	$dropbox_filename = add_ext_on_mime($dropbox_filename, $dropbox_filetype);
	// Replace dangerous characters
	$dropbox_filename = replace_dangerous_char($dropbox_filename);
	// Transform any .php file in .phps fo security
	$dropbox_filename = php2phps($dropbox_filename);
	//filter extension
    if (!filter_extension($dropbox_filename)) {
    	return get_lang('UplUnableToSaveFileFilteredExtension');
    }

	// set title
	$dropbox_title = $dropbox_filename;
	// set author
	if ($_POST['authors'] == '') {
		$_POST['authors'] = getUserNameFromId($_user['user_id']);
	}

	// note: I think we could better migrate everything from here on to separate functions: store_new_dropbox, store_new_mailing, store_just_upload

	if ($dropbox_overwrite) {
		$dropbox_person = new Dropbox_Person($_user['user_id'], api_is_course_admin(), api_is_course_tutor());

		foreach ($dropbox_person->sentWork as $w) {
			if ($w->title == $dropbox_filename) {
			    if (($w->recipients[0]['id'] > dropbox_cnf('mailingIdBase')) xor $thisIsAMailing) {
					return get_lang('MailingNonMailingError');
				}
				if (($w->recipients[0]['id'] == $_user['user_id']) xor $thisIsJustUpload) {
					return get_lang('MailingJustUploadSelectNoOther');
				}
				$dropbox_filename = $w->filename;
				$found = true; // note: do we still need this?
				break;
			}
		}
	} else {  // rename file to login_filename_uniqueId format
		$dropbox_filename = getLoginFromId($_user['user_id']) . "_" . $dropbox_filename . "_".uniqid('');
	}

	// creating the array that contains all the users who will receive the file
	$new_work_recipients = array();
	foreach ($_POST['recipients'] as $rec) {
		if (strpos($rec, 'user_') === 0) {
			$new_work_recipients[] = substr($rec, strlen('user_') );
		} elseif (strpos($rec, 'group_') === 0) {
			$userList = GroupManager::get_subscribed_users(substr($rec, strlen('group_')));
			foreach ($userList as $usr) {
				if (!in_array($usr['user_id'], $new_work_recipients) && $usr['user_id'] != $_user['user_id']) {
					$new_work_recipients[] = $usr['user_id'];
				}
			}
		}
	}

	@move_uploaded_file($dropbox_filetmpname, dropbox_cnf('sysPath') . '/' . $dropbox_filename);

	$b_send_mail = api_get_course_setting('email_alert_on_new_doc_dropbox');

	if ($b_send_mail) {
		foreach ($new_work_recipients as $recipient_id) {
			$recipent_temp = UserManager :: get_user_info_by_id($recipient_id);
			@api_mail(api_get_person_name($recipent_temp['firstname'].' '.$recipent_temp['lastname'], null, PERSON_NAME_EMAIL_ADDRESS), $recipent_temp['email'],
				get_lang('NewDropboxFileUploaded'),
				get_lang('NewDropboxFileUploadedContent').' '.api_get_path(WEB_CODE_PATH).'dropbox/index.php?cidReq='.$_course['sysCode']."\n\n".api_get_person_name($_user['firstName'], $_user['lastName'], null, PERSON_NAME_EMAIL_ADDRESS)."\n".  get_lang('Email') ." : ".$_user['mail'], api_get_person_name($_user['firstName'], $_user['lastName'], null, PERSON_NAME_EMAIL_ADDRESS), $_user['mail']);
		}
	}

	new Dropbox_SentWork( $_user['user_id'], $dropbox_title, $_POST['description'], strip_tags($_POST['authors']), $dropbox_filename, $dropbox_filesize, $new_work_recipients);

	Security::clear_token();
    return get_lang('FileUploadSucces');
}



/**
* This function displays the firstname and lastname of the user as a link to the user tool.
*
* @see this is the same function as in the new forum, so this probably has to move to a user library.
*
* @todo move this function to the user library (there is a duplicate in work.lib.php)
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function display_user_link_work($user_id, $name = '') {
	if ($user_id != 0) {
		if (empty($name)) {
			$table_user = Database::get_main_table(TABLE_MAIN_USER);
			$sql = "SELECT * FROM $table_user WHERE user_id='".Database::escape_string($user_id)."'";
			$result = Database::query($sql);
			$row = Database::fetch_array($result);
			return '<a href="../user/userInfo.php?uInfo='.$row['user_id'].'">'.api_get_person_name($row['firstname'], $row['lastname']).'</a>';
		} else {
            $user_id = intval($user_id);
			return '<a href="../user/userInfo.php?uInfo='.$user_id.'">'.Security::remove_XSS($name).'</a>';
		}
	} else {
		return $name.' ('.get_lang('Anonymous').')';
	}
}

/**
* this function transforms the array containing all the feedback into something visually attractive.
*
* @param an array containing all the feedback about the given message.
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function feedback($array) {
	foreach ($array as $key => $value) {
		$output .= format_feedback($value);
	}
	$output .= feedback_form();
	return $output;
}

/**
* This function returns the html code to display the feedback messages on a given dropbox file
* @param $feedback_array an array that contains all the feedback messages about the given document.
* @return html code
* @todo add the form for adding new comment (if the other party has not deleted it yet).
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function format_feedback($feedback) {
	$output .= display_user_link_work($feedback['author_user_id']);
	$output .= '&nbsp;&nbsp;'.api_convert_and_format_date($feedback['feedback_date'], DATE_TIME_FORMAT_LONG).'<br />';
	$output .= '<div style="padding-top:6px">'.nl2br($feedback['feedback']).'</div><hr size="1" noshade/><br />';
	return $output;
}

/**
* this function returns the code for the form for adding a new feedback message to a dropbox file.
* @return html code
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function feedback_form() {
    $course_id = api_get_course_int_id();

	global $dropbox_cnf;

	$return = get_lang('AddNewFeedback').'<br />';

	// we now check if the other users have not delete this document yet. If this is the case then it is useless to see the
	// add feedback since the other users will never get to see the feedback.
	$sql = "SELECT * FROM ".$dropbox_cnf['tbl_person']." WHERE c_id = $course_id AND file_id = ".intval($_GET['id']);
	$result = Database::query($sql);
	$number_users_who_see_file = Database::num_rows($result);
	if ($number_users_who_see_file > 1) {
		$token = Security::get_token();
		$return .= '<textarea name="feedback" style="width: 80%; height: 80px;"></textarea>';
		$return .= '<input type="hidden" name="sec_token" value="'.$token.'"/>';
		$return .= '<br /><button type="submit" class="add" name="store_feedback" value="'.get_lang('Ok').'"
					onclick="javascript: document.form_dropbox.attributes.action.value = document.location;">'.get_lang('AddComment').'</button>';
	} else {
		$return .= get_lang('AllUsersHaveDeletedTheFileAndWillNotSeeFeedback');
	}
	return $return;
}

/**
* @return a language string (depending on the success or failure.
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function store_feedback() {
	global $dropbox_cnf;
	if (!is_numeric($_GET['id'])) {
		return get_lang('FeedbackError');
	}
	$course_id = api_get_course_int_id();
	if (empty($_POST['feedback'])) {
		return get_lang('PleaseTypeText');
	} else {
		$sql="INSERT INTO ".$dropbox_cnf['tbl_feedback']." (c_id, file_id, author_user_id, feedback, feedback_date) VALUES
			  ($course_id, '".intval($_GET['id'])."','".api_get_user_id()."','".Database::escape_string($_POST['feedback'])."', '".api_get_utc_datetime()."')";
		Database::query($sql);
		return get_lang('DropboxFeedbackStored');
	}
}

/**
* This function downloads all the files of the inputarray into one zip
* @param $array an array containing all the ids of the files that have to be downloaded.
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @todo consider removing the check if the user has received or sent this file (zip download of a folder already sufficiently checks for this).
* @todo integrate some cleanup function that removes zip files that are older than 2 days
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function zip_download($array) {
	global $_course;
	global $dropbox_cnf;
	global $files;

    $course_id = api_get_course_int_id();

	$sys_course_path = api_get_path(SYS_COURSE_PATH);

	// zip library for creation of the zipfile
	require api_get_path(LIBRARY_PATH).'pclzip/pclzip.lib.php';

	// place to temporarily stash the zipfiles
	$temp_zip_dir = api_get_path(SYS_COURSE_PATH);

	array_map('intval', $array);

	// note: we also have to add the check if the user has received or sent this file.
	$sql = "SELECT distinct file.filename, file.title, file.author, file.description
			FROM ".$dropbox_cnf['tbl_file']." file, ".$dropbox_cnf['tbl_person']." person
			WHERE file.c_id = $course_id AND
			person.c_id = $course_id AND
			file.id IN (".implode(', ',$array).")
			AND file.id=person.file_id
			AND person.user_id='".api_get_user_id()."'";
	$result = Database::query($sql);
	$files = array();
	while ($row = Database::fetch_array($result)) {
		$files[$row['filename']] = array('filename' => $row['filename'],'title' => $row['title'], 'author' => $row['author'], 'description' => $row['description']);
	}

	// Step 3: create the zip file and add all the files to it
	$temp_zip_file = api_get_path(SYS_ARCHIVE_PATH).api_get_unique_id().".zip";
	$zip_folder = new PclZip($temp_zip_file);
	foreach ($files as $key => $value) {
		$zip_folder->add(api_get_path(SYS_COURSE_PATH).$_course['path'].'/dropbox/'.$value['filename'], PCLZIP_OPT_REMOVE_ALL_PATH, PCLZIP_CB_PRE_ADD, 'my_pre_add_callback');
	}

	/*
	 * @todo if you want the overview code fix it by yourself
	 *
	// Step 1: create the overview file and add it to the zip
	$overview_file_content = generate_html_overview($files, array('filename'), array('title'));
	$overview_file = $temp_zip_dir.'overview'.replace_dangerous_char(api_is_western_name_order() ? $_user['firstname'].' '.$_user['lastname'] : $_user['lastname'].' '.$_user['firstname'], 'strict').'.html';
	$handle = fopen($overview_file, 'w');
	fwrite($handle, $overview_file_content);
	// todo: find a different solution for this because even 2 seconds is no guarantee.
	sleep(2);*/

	// Step 4: we add the overview file
	//$zip_folder->add($overview_file, PCLZIP_OPT_REMOVE_PATH, api_get_path(SYS_COURSE_PATH).$_course['path'].'/temp');

	// Step 5: send the file for download;

	$name = 'dropbox-'.api_get_utc_datetime().'.zip';
	DocumentManager::file_send_for_download($temp_zip_file, true, $name);
	@unlink($temp_zip_file);
	exit;
}

/**
* This is a callback function to decrypt the files in the zip file to their normal filename (as stored in the database)
* @param $p_event a variable of PCLZip
* @param $p_header a variable of PCLZip
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function my_pre_add_callback($p_event, &$p_header) {
	global $files;
	$p_header['stored_filename'] = $files[$p_header['stored_filename']]['title'];
	return 1;
}


/**
 * @desc Generates the contents of a html file that gives an overview of all the files in the zip file.
 *		This is to know the information of the files that are inside the zip file (who send it, the comment, ...)
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, March 2006
 * @author Ivan Tcholakov, 2010, code for html metadata has been added.
 */
function generate_html_overview($files, $dont_show_columns = array(), $make_link = array()) {
	$return = '<!DOCTYPE html'."\n";
	$return .= "\t".'PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"'."\n";
	$return .= "\t".'"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
	$return .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.api_get_language_isocode().'" lang="'.api_get_language_isocode().'">'."\n";

	$return .= "<head>\n\t<title>".get_lang('OverviewOfFilesInThisZip')."</title>\n";
	$return .= "\t".'<meta http-equiv="Content-Type" content="text/html; charset='.api_get_system_encoding().'" />'."\n";
	$return .= "</head>\n\n";
	$return .= '<body dir="'.api_get_text_direction().'">'."\n\n";
	$return .= "<table border=\"1px\">\n";

	$counter = 0;
	foreach ($files as $key => $value) {

		// Adding the header.
		if ($counter == 0) {
			$columns_array = array_keys($value);
			$return .= "\n<tr>";
			foreach ($columns_array as $columns_array_key => $columns_array_value) {
				if (!in_array($columns_array_value, $dont_show_columns)) {
					$return .= "\n\t<th>".$columns_array_value."</th>";
				}
				$column[] = $columns_array_value;
			}
			$return .= "\n</tr>\n";
		}
		$counter++;

		// Adding the content.
		$return .= "\n<tr>";
		foreach ($column as $column_key => $column_value) {
			if (!in_array($column_value,$dont_show_columns)) {
				$return .= "\n\t<td>";
				if (in_array($column_value, $make_link)) {
					$return .= '<a href="'.$value[$column_value].'">'.$value[$column_value].'</a>';
				} else {
					$return .= $value[$column_value];
				}
				$return .= "</td>";
			}
		}
		$return .= "\n</tr>\n";
	}
	$return .= "\n</table>\n\n</body>";
	$return .= "\n</html>";

	return $return;
}

/**
* @desc This function retrieves the number of feedback messages on every document. This function might become obsolete when
* 		the feedback becomes user individual.
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function get_total_number_feedback($file_id = '') {
	global $dropbox_cnf;
	$course_id = api_get_course_int_id();
	$sql = "SELECT COUNT(feedback_id) AS total, file_id FROM ".$dropbox_cnf['tbl_feedback']."
			WHERE c_id = $course_id GROUP BY file_id";
	$result = Database::query($sql);
	while ($row=Database::fetch_array($result)) {
		$return[$row['file_id']] = $row['total'];
	}
	return $return;
}


/**
* @desc this function checks if the key exists. If this is the case it returns the value, if not it returns 0
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function check_number_feedback($key, $array) {
	if (is_array($array)) {
		if (key_exists($key, $array)) {
			return $array[$key];
		} else {
			return 0;
		}
	} else {
		return 0;
	}
}

/**
 * Get the last access to a given tool of a given user
 * @param $tool string the tool constant
 * @param $course_code the course_id
 * @param $user_id the id of the user
 * @return string last tool access date
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version march 2006
 *
 * @todo consider moving this function to a more appropriate place.
 */
function get_last_tool_access($tool, $course_code='', $user_id='') {
	global $_course, $_user;

	// The default values of the parameters
	if ($course_code == '') {
		$course_code = $_course['id'];
	}
	if ($user_id == '') {
		$user_id = $_user['user_id'];
	}

	// the table where the last tool access is stored (=track_e_lastaccess)
	$table_last_access = Database::get_statistic_table('track_e_lastaccess');

	$sql = "SELECT access_date FROM $table_last_access WHERE access_user_id='".Database::escape_string($user_id)."'
				AND access_cours_code='".Database::escape_string($course_code)."'
				AND access_tool='".Database::escape_string($tool)."'
				ORDER BY access_date DESC
				LIMIT 1";
	$result = Database::query($sql);
	$row = Database::fetch_array($result);
	return $row['access_date'];
}

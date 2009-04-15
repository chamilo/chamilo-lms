<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */

/**
* This file contains additional dropbox functions. Initially there were some 
* functions in the init files also but I have moved them over
* to one file 		-- Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/

/**
* This function is a wrapper function for the multiple actions feature.
* @return	Mixed	If there is a problem, return a string message, otherwise nothing
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function handle_multiple_actions()
{
	global $_user, $is_courseAdmin, $is_courseTutor;

	// STEP 1: are we performing the actions on the received or on the sent files?
	if($_POST['action']=='delete_received' || $_POST['action']=='download_received')
	{
		$part = 'received';
	}
	elseif($_POST['action']=='delete_sent' || $_POST['action']=='download_sent')
	{
		$part = 'sent';
	}

	// STEP 2: at least one file has to be selected. If not we return an error message
	foreach ($_POST as $key=>$value)
	{
		if (strstr($value,$part.'_') AND $key!='view_received_category' AND $key!='view_sent_category')
		{
			$checked_files=true;
			$checked_file_ids[]=intval(substr($value,strrpos($value,'_')));
		}
	}
	$checked_file_ids = $_POST['id'];

	if (!is_array($checked_file_ids) || count($checked_file_ids)==0)
	{
		return get_lang('CheckAtLeastOneFile');
	}


	// STEP 3A: deleting
	if ($_POST['action']=='delete_received' || $_POST['action']=='delete_sent')
	{
		$dropboxfile=new Dropbox_Person( $_user['user_id'], $is_courseAdmin, $is_courseTutor);
		foreach ($checked_file_ids as $key=>$value)
		{
			if ($_GET['view']=='received' OR !$_GET['view'])
			{
				$dropboxfile->deleteReceivedWork($value);
				$message=get_lang('ReceivedFileDeleted');
			}
			if ($_GET['view']=='sent')
			{
				$dropboxfile->deleteSentWork($value);
				$message=get_lang('SentFileDeleted');
			}
		}
		return $message;
	}

	// STEP 3B: giving comment
	if ($_POST['actions']=='comment')
	{
		// This has not been implemented.
		// The idea was that it would be possible to write the same feedback for the selected documents.
	}

	// STEP 3C: moving
	if (strstr($_POST['action'], 'move_'))
	{
        	// check move_received_n or move_sent_n command
		if (strstr($_POST['action'],'received')){
                	$part = 'received';
                	$to_cat_id = str_replace('move_received_','',$_POST['action']);
        	}
        	else {
                	$part = 'sent';
                	$to_cat_id = str_replace('move_sent_','',$_POST['action']);
        	}

		foreach ($checked_file_ids as $key=>$value)
		{
			store_move($value, $to_cat_id, $part);
		}
		return get_lang('FilesMoved');
    }

	// STEP 3D: downloading
	if ($_POST['action']=='download_sent' || $_POST['action']=='download_received')
	{
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
function delete_category($action, $id)
{
	global $dropbox_cnf;
	global $_user, $is_courseAdmin, $is_courseTutor;

	// an additional check that might not be necessary
	if ($action=='deletereceivedcategory')
	{
		$sentreceived='received';
		$entries_table=$dropbox_cnf['tbl_post'];
		$id_field='file_id';
	}
	elseif ($action=='deletesentcategory')
	{
		$sentreceived='sent';
		$entries_table=$dropbox_cnf['tbl_file'];
		$id_field='id';
	}
	else
	{
		return get_lang('Error');
	}

	// step 1: delete the category
	$sql="DELETE FROM ".$dropbox_cnf['tbl_category']." WHERE cat_id='".Database::escape_string($id)."' AND $sentreceived='1'";
	$result=api_sql_query($sql);

	// step 2: delete all the documents in this category
	$sql="SELECT * FROM ".$entries_table." WHERE cat_id='".Database::escape_string($id)."'";
	$result=api_sql_query($sql);

	while ($row=mysql_fetch_array($result))
	{
		$dropboxfile=new Dropbox_Person( $_user['user_id'], $is_courseAdmin, $is_courseTutor);
		if ($action=='deletereceivedcategory')
		{
			$dropboxfile->deleteReceivedWork($row[$id_field]);
		}
		if ($action=='deletesentcategory')
		{
			$dropboxfile->deleteSentWork($row[$id_field]);
		}
	}
}

/**
* Displays the form to move one individual file to a category
*
* @return html code of the form that appears in a dokeos message box.
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function display_move_form($part, $id, $target=array())
{
	echo '<div class="row"><div class="form_header">'.get_lang('MoveFileTo').'</div></div>';
	echo '<form name="form1" method="post" action="'.api_get_self().'?view_received_category='.$_GET['view_received_category'].'&view_sent_category='.$_GET['view_sent_category'].'&view='.$_GET['view'].'">';
	echo '<input type="hidden" name="id" value="'.$id.'">';
	echo '<input type="hidden" name="part" value="'.$part.'">';
	echo '
			<div class="row">
				<div class="label">
					<span class="form_required">*</span> '.get_lang('MoveFileTo').'
				</div>
				<div class="formw">';
	echo '<select name="move_target">';
	echo '<option value="0">'.get_lang('Root').'</option>';
	foreach ($target as $key=>$category)
	{
		echo '<option value="'.$category['cat_id'].'">'.$category['cat_name'].'</option>';
	}
	echo  '</select>';
	echo '	</div>
			</div>';
	
	echo '
		<div class="row">
			<div class="label">
			</div>
			<div class="formw">
				<button class="save" type="submit" name="do_move" value="'.get_lang('Ok').'">'.get_lang('MoveFile').'</button>
			</div>
		</div>	
	';
	echo '</form>';
	
	echo '<div style="clear: both;"></div>';
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
function store_move($id, $target, $part)
{
	global $_user;
	global $dropbox_cnf;

	if ((isset($id) AND $id<>'') AND (isset($target) AND $target<>'') AND (isset($part) AND $part<>''))
	{
		if ($part=='received')
		{
			$sql="UPDATE ".$dropbox_cnf["tbl_post"]." SET cat_id='".Database::escape_string($target)."'
						WHERE dest_user_id='".Database::escape_string($_user['user_id'])."'
						AND file_id='".Database::escape_string($id)."'
						";
			api_sql_query($sql,__FILE__,__LINE__);
			$return_message=get_lang('ReceivedFileMoved');
		}
		if ($part=='sent')
		{
			$sql="UPDATE ".$dropbox_cnf["tbl_file"]." SET cat_id='".Database::escape_string($target)."'
						WHERE uploader_id='".Database::escape_string($_user['user_id'])."'
						AND id='".Database::escape_string($id)."'
						";
			api_sql_query($sql,__FILE__,__LINE__);
			$return_message=get_lang('SentFileMoved');
		}
	}
	else
	{
		$return_message=get_lang('NotMovedError');
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
function display_action_options($part, $categories, $current_category=0)
{
	echo '<select name="actions">';
	echo '<option value="download">'.get_lang('Download').'</option>';
	echo '<option value="delete">'.get_lang('Delete').'</option>';
	if(is_array($categories))
	{
		echo '<optgroup label="'.get_lang('MoveTo').'">';
		if ($current_category<>0)
		{
			echo '<option value="move_0">'.get_lang('Root').'</a>';
		}
		foreach ($categories as $key=>$value)
		{
			if( $current_category<>$value['cat_id'])
			{
				echo '<option value="move_'.$value['cat_id'].'">'.$value['cat_name'].'</option>';
			}
		}
		echo '</optgroup>';
	}
	echo '</select>';
	echo '<input type="submit" name="do_actions_'.$part.'" value="'.get_lang('Ok').'" />';
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
function display_file_checkbox($id, $part)
{
	if (isset($_GET['selectall']))
	{
		$checked='checked';
	}
	$return_value='<input type="checkbox" name="'.$part.'_'.$id.'" value="'.$id.'" '.$checked.' />';
	return $return_value;
}


/**
* This function retrieves all the dropbox categories and returns them as an array
*
* @param $filter default '', when we need only the categories of the sent or the received part.
*
* @return array
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function get_dropbox_categories($filter='')
{
	global $_user;
	global $dropbox_cnf;

	echo '<h1>'.$filter.'</h1>';

	$return_array=array();

	$sql="SELECT * FROM ".$dropbox_cnf['tbl_category']." WHERE user_id='".$_user['user_id']."'";

	$result=api_sql_query($sql);
	while ($row=mysql_fetch_array($result))
	{
		if(($filter=='sent' AND $row['sent']==1) OR ($filter=='received' AND $row['received']==1) OR $filter=='')
		{
			$return_array[$row['cat_id']]=$row;
		}
	}

	return $return_array;
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
function store_addcategory()
{
	global $_user;
	global $dropbox_cnf;

	// check if the target is valid
	if ($_POST['target']=='sent')
	{
		$sent=1;
		$received=0;
	}
	elseif ($_POST['target']=='received')
	{
		$sent=0;
		$received=1;
	}
	else
	{
		return get_lang('Error');
	}

	// check if the category name is valid
	if ($_POST['category_name']=='')
	{
		return get_lang('ErrorPleaseGiveCategoryName');
	}

	if (!$_POST['edit_id'])
	{
		// step 3a, we check if the category doesn't already exist
		$sql="SELECT * FROM ".$dropbox_cnf['tbl_category']." WHERE user_id='".$_user['user_id']."' AND cat_name='".Database::escape_string($_POST['category_name'])."' AND received='".$received."' AND sent='".$sent."'";
		$result=api_sql_query($sql);


		// step 3b, we add the category if it does not exist yet.
		if (mysql_num_rows($result)==0)
		{
			$sql="INSERT INTO ".$dropbox_cnf['tbl_category']." (cat_name, received, sent, user_id)
					VALUES ('".Database::escape_string($_POST['category_name'])."', '".Database::escape_string($received)."', '".Database::escape_string($sent)."', '".Database::escape_string($_user['user_id'])."')";
			api_sql_query($sql);
			return get_lang('CategoryStored');
		}
		else
		{
			return get_lang('CategoryAlreadyExistsEditIt');
		}
	}
	else
	{
		$sql="UPDATE ".$dropbox_cnf['tbl_category']." SET cat_name='".Database::escape_string($_POST['category_name'])."', received='".Database::escape_string($received)."' , sent='".Database::escape_string($sent)."'
				WHERE user_id='".Database::escape_string($_user['user_id'])."'
				AND cat_id='".Database::escape_string($_POST['edit_id'])."'";
		api_sql_query($sql);
		return get_lang('CategoryModified');
	}
}

/**
* This function displays the form to add a new category.
*
* @param $category_name this parameter is the name of the category (used when no section is selected)
* @param $id this is the id of the category we are editing.
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function display_addcategory_form($category_name='', $id='')
{
	global $dropbox_cnf;

	$title=get_lang('AddNewCategory');

	if (isset($id) AND $id<>'')
	{
		// retrieve the category we are editing
		$sql="SELECT * FROM ".$dropbox_cnf['tbl_category']." WHERE cat_id='".Database::escape_string($id)."'";
		$result=api_sql_query($sql);
		$row=mysql_fetch_array($result);

		if ($category_name=='') // after an edit with an error we do not want to return to the original name but the name we already modified. (happens when createinrecievedfiles AND createinsentfiles are not checked)
		{
			$category_name=$row['cat_name'];
		}
		if ($row['received']=='1')
		{
			$target='received';
		}
		if ($row['sent']=='1')
		{
			$target='sent';
		}
		$title=get_lang('EditCategory');

	}

	if ($_GET['action']=='addreceivedcategory') {
		$target='received';
	}
	if ($_GET['action']=='addsentcategory') {
		$target='sent';
	}
	
	if ($_GET['action']=='editcategory') {
		$text=get_lang('ModifyCategory');
		$class='save';
	} else if ($_GET['action']=='addreceivedcategory' or $_GET['action']=='addsentcategory')  {
		$text=get_lang('CreateCategory');
		$class='add';
	}


	echo "<form name=\"add_new_category\" method=\"post\" action=\"".api_get_self()."?view=".$_GET['view']."\">\n";
	if (isset($id) AND $id<>'')
	{
		echo '<input name="edit_id" type="hidden" value="'.$id.'">';
	}
	echo '<input name="target" type="hidden" value="'.$target.'">';
	
	echo '<div class="row"><div class="form_header">'.$title.'</div></div>';
	
	echo '	<div class="row">
				<div class="label">
					<span class="form_required">*</span>'.get_lang('CategoryName').'
				</div>
				<div class="formw">
					<input type="text" name="category_name" value="'.$category_name.'" />
				</div>
			</div>';
	
	echo '	<div class="row">
				<div class="label">
				</div>
				<div class="formw">
					<button class="'.$class.'" type="submit" name="StoreCategory">'.$text.'</button>
				</div>
			</div>';	
	echo "</form>";
	echo '<div style="clear: both;"></div>';
}

/**
* this function displays the form to upload a new item to the dropbox.
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function display_add_form()
{
	global $_user, $is_courseAdmin, $is_courseTutor, $course_info, $origin, $dropbox_unid;

	$token = Security::get_token();
	$dropbox_person = new Dropbox_Person( $_user['user_id'], $is_courseAdmin, $is_courseTutor);
	?>
	<form method="post" action="index.php?view_received_category=<?php echo $_GET['view_received_category']; ?>&view_sent_category=<?php echo $_GET['view_sent_category']; ?>&view=<?php echo $_GET['view']; ?>&<?php echo "origin=$origin"."&".api_get_cidreq(); ?>" enctype="multipart/form-data" onsubmit="return checkForm(this)">
	
	<div class="row"><div class="form_header"><?php echo get_lang('UploadNewFile'); ?></div></div>
	
	<div class="row">
		<div class="label">
			<span class="form_required">*</span><?php echo dropbox_lang("uploadFile")?>:
		</div>
		<div class="formw">	
				<input type="hidden" name="MAX_FILE_SIZE" value='<?php echo dropbox_cnf("maxFilesize")?>' />
				<input type="file" name="file" size="20" <?php if (dropbox_cnf("allowOverwrite")) echo 'onChange="checkfile(this.value)"'; ?> />
				<input type="hidden" name="dropbox_unid" value="<?php echo $dropbox_unid ?>" />
				<input type="hidden" name="sec_token" value="<?php echo $token ?>" />
				<?php
				if ($origin=='learnpath')
				{
					echo "<input type='hidden' name='origin' value='learnpath' />";
				}
				?>
		</div>
	</div>
		
	<?php
	if (dropbox_cnf("allowOverwrite"))
	{
		?>
		<div class="row">
			<div class="label">
				
			</div>
			<div class="formw">
				<input type="checkbox" name="cb_overwrite" id="cb_overwrite" value="true" /><?php echo dropbox_lang("overwriteFile")?>
			</div>
		</div>	
		<?php
	}
	?>

	<div class="row">
		<div class="label">
			<?php echo dropbox_lang("sendTo")?>
		</div>
		<div class="formw">
	<?php

	//list of all users in this course and all virtual courses combined with it
	if(isset($_SESSION['id_session'])){
		$complete_user_list_for_dropbox = array();
		if(api_get_setting('dropbox_allow_student_to_student')=='true' || $_user['status'] != STUDENT)
		{
			$complete_user_list_for_dropbox = CourseManager :: get_user_list_from_course_code($course_info['code'],true,$_SESSION['id_session']);
		}
		$complete_user_list2 = CourseManager :: get_coach_list_from_course_code($course_info['code'],$_SESSION['id_session']);
		$complete_user_list_for_dropbox = array_merge($complete_user_list_for_dropbox,$complete_user_list2);
	}
	else{
		if(api_get_setting('dropbox_allow_student_to_student')=='true' || $_user['status'] != STUDENT)
		{
			$complete_user_list_for_dropbox = CourseManager :: get_user_list_from_course_code($course_info['code'],true,$_SESSION['id_session']);
		}
		else
		{
			$complete_user_list_for_dropbox = CourseManager :: get_teacher_list_from_course_code($course_info['code']);
		}
	}

	foreach ($complete_user_list_for_dropbox as $k => $e)
	    $complete_user_list_for_dropbox[$k] = $e +
	        array('lastcommafirst' => $e['lastname'] . ', ' . $e['firstname']);

	$complete_user_list_for_dropbox = TableSort::sort_table($complete_user_list_for_dropbox, 'lastcommafirst');

	?>

				<select name="recipients[]" size="
	<?php
		if ( $dropbox_person -> isCourseTutor || $dropbox_person -> isCourseAdmin)
		{
			echo 10;
		}
		else
		{
			echo 6;
		}


	?>" multiple style="width: 350px;">
	<?php

	/*
		Create the options inside the select box:
		List all selected users their user id as value and a name string as display
	*/
	foreach ($complete_user_list_for_dropbox as $current_user)
	{
		if ( ($dropbox_person -> isCourseTutor
		|| $dropbox_person -> isCourseAdmin
		|| dropbox_cnf("allowStudentToStudent")	// RH: also if option is set
		|| $current_user['status']!=5				// always allow teachers
		|| $current_user['tutor_id']==1				// always allow tutors
		) && $current_user['user_id'] != $_user['user_id'] ) 	// don't include yourself
		{
			$full_name = $current_user['lastcommafirst'];
			echo '<option value="user_' . $current_user['user_id'] . '">' . $full_name . '</option>';
		}
	}

	/*
	* Show groups
	*/
    if ( ($dropbox_person -> isCourseTutor || $dropbox_person -> isCourseAdmin)
    && dropbox_cnf("allowGroup") || dropbox_cnf("allowStudentToStudent"))
    {
		$complete_group_list_for_dropbox = GroupManager::get_group_list(null,dropbox_cnf("courseId"));

		if (count($complete_group_list_for_dropbox) > 0)
		{
			foreach ($complete_group_list_for_dropbox as $current_group)
			{
				if ($current_group['number_of_members'] > 0)
				{
					echo '<option value="group_'.$current_group['id'].'">G: '.$current_group['name'].' - '.$current_group['number_of_members'].' '.api_get_lang('Users').'</option>';
				}
			}
		}
    }

    if ( ($dropbox_person -> isCourseTutor || $dropbox_person -> isCourseAdmin) && dropbox_cnf("allowMailing"))  // RH: Mailing starting point
	{
			// echo '<option value="mailing">'.dropbox_lang("mailingInSelect").'</option>';
	}

    if ( dropbox_cnf("allowJustUpload"))  // RH
    {
	  //echo '<option value="upload">'.dropbox_lang("justUploadInSelect").'</option>';
	  echo '<option value="user_'.$_user['user_id'].'">'.dropbox_lang("justUploadInSelect").'</option>';
    }

		echo '</select>
		</div>
	</div>';
		
	echo '
		<div class="row">
			<div class="label">
			</div>
			<div class="formw">
				<button type="Submit" class="save" name="submitWork">'.dropbox_lang("upload", "noDLTT").'</button>
			</div>
		</div>
	';		
	
	echo "</form>";
}

/**
* returns username or false if user isn't registered anymore
* @todo check if this function is still necessary. There might be a library function for this.
*/
function getUserNameFromId ( $id)  // RH: Mailing: return 'Mailing ' + id
{
    $mailingId = $id - dropbox_cnf("mailingIdBase");
    if ( $mailingId > 0)
    {
	    return dropbox_lang("mailingAsUsername", "noDLTT") . $mailingId;
    }

    $sql = "SELECT CONCAT(lastname,' ', firstname) AS name
			FROM " . dropbox_cnf("tbl_user") . "
			WHERE user_id='" . addslashes( $id) . "'";
    $result = api_sql_query($sql,__FILE__,__LINE__);
    $res = mysql_fetch_array( $result);

    if ( $res == FALSE) return FALSE;
    return stripslashes( $res["name"]);
}

/**
* returns loginname or false if user isn't registered anymore
* @todo check if this function is still necessary. There might be a library function for this.
*/
function getLoginFromId ( $id)
{
    $sql = "SELECT username
			FROM " . dropbox_cnf("tbl_user") . "
			WHERE user_id='" . addslashes( $id) . "'";
    $result =api_sql_query($sql,__FILE__,__LINE__);
    $res = mysql_fetch_array( $result);
    if ( $res == FALSE) return FALSE;
    return stripslashes( $res["username"]);
}

/**
* @return boolean indicating if user with user_id=$user_id is a course member
* @todo eliminate global
* @todo check if this function is still necessary. There might be a library function for this.
*/
function isCourseMember( $user_id)
{
    global $_course;
	$course_code = $_course['sysCode'];
	$is_course_member = CourseManager::is_user_subscribed_in_course($user_id, $course_code,true);
	return $is_course_member;
}

/**
* Checks if there are files in the dropbox_file table that aren't used anymore in dropbox_person table.
* If there are, all entries concerning the file are deleted from the db + the file is deleted from the server
*/
function removeUnusedFiles( )
{
    // select all files that aren't referenced anymore
    $sql = "SELECT DISTINCT f.id, f.filename
			FROM " . dropbox_cnf("tbl_file") . " f
			LEFT JOIN " . dropbox_cnf("tbl_person") . " p ON f.id = p.file_id
			WHERE p.user_id IS NULL";
    $result = api_sql_query($sql,__FILE__,__LINE__);
    while ( $res = mysql_fetch_array( $result))
    {
		//delete the selected files from the post and file tables
        $sql = "DELETE FROM " . dropbox_cnf("tbl_post") . " WHERE file_id='" . $res['id'] . "'";
        $result1 = api_sql_query($sql,__FILE__,__LINE__);
        $sql = "DELETE FROM " . dropbox_cnf("tbl_file") . " WHERE id='" . $res['id'] . "'";
        $result1 = api_sql_query($sql,__FILE__,__LINE__);

		//delete file from server
        @unlink( dropbox_cnf("sysPath") . "/" . $res["filename"]);
    }
}

/**
* RH: Mailing (2 new functions)
*
* Mailing zip-file is posted to (dest_user_id = ) mailing pseudo_id
* and is only visible to its uploader (user_id).
*
* Mailing content files have uploader_id == mailing pseudo_id, a normal recipient,
* and are visible initially to recipient and pseudo_id.
*
* @author Ren� Haentjens, Ghent University
*
* @todo check if this function is still necessary.
*/
function getUserOwningThisMailing($mailingPseudoId, $owner = 0, $or_die = '')
{
    $sql = "SELECT f.uploader_id
			FROM " . dropbox_cnf("tbl_file") . " f
			LEFT JOIN " . dropbox_cnf("tbl_post") . " p ON f.id = p.file_id
			WHERE p.dest_user_id = '" . $mailingPseudoId . "'";
    $result = api_sql_query($sql,__FILE__,__LINE__);

    if (!($res = mysql_fetch_array($result)))
        die(dropbox_lang("generalError")." (code 901)");

    if ($owner == 0) return $res['uploader_id'];

    if ($res['uploader_id'] == $owner) return TRUE;

    die(dropbox_lang("generalError")." (code ".$or_die.")");
}
/**
* @author Ren� Haentjens, Ghent University
* @todo check if this function is still necessary.
*/
function removeMoreIfMailing($file_id)
{
    // when deleting a mailing zip-file (posted to mailingPseudoId):
    // 1. the detail window is no longer reachable, so
    //    for all content files, delete mailingPseudoId from person-table
    // 2. finding the owner (getUserOwningThisMailing) is no longer possible, so
    //    for all content files, replace mailingPseudoId by owner as uploader

    $sql = "SELECT p.dest_user_id
			FROM " . dropbox_cnf("tbl_post") . " p
			WHERE p.file_id = '" . $file_id . "'";
    $result = api_sql_query($sql,__FILE__,__LINE__);

    if ( $res = mysql_fetch_array( $result))
    {
	    $mailingPseudoId = $res['dest_user_id'];
	    if ( $mailingPseudoId > dropbox_cnf("mailingIdBase"))
	    {
	        $sql = "DELETE FROM " . dropbox_cnf("tbl_person") . " WHERE user_id='" . $mailingPseudoId . "'";
	        $result1 = api_sql_query($sql,__FILE__,__LINE__);

	        $sql = "UPDATE " . dropbox_cnf("tbl_file") .
	            " SET uploader_id='" . api_get_user_id() . "' WHERE uploader_id='" . $mailingPseudoId . "'";
	        $result1 = api_sql_query($sql,__FILE__,__LINE__);
        }
    }
}

/**
* The dropbox has a deviant naming scheme for language files so it needs an additional language function
*
* @todo check if this function is still necessary.
*
* @author Ren� Haentjens, Ghent University
*/
function dropbox_lang($variable, $notrans = 'DLTT')
{
    return (api_get_setting('server_type') == 'test' ?
        get_lang('dropbox_lang["'.$variable.'"]', $notrans) :
        str_replace("\\'", "'", $GLOBALS['dropbox_lang'][$variable]));
}
/**
* Function that finds a given config setting
*
* @author Ren� Haentjens, Ghent University
*/
function dropbox_cnf($variable)
{
    return $GLOBALS['dropbox_cnf'][$variable];
}





/**
*
*/
function store_add_dropbox()
{
	global $dropbox_cnf;
	global $_user;
	global $_course;

	// ----------------------------------------------------------
	// Validating the form data
	// ----------------------------------------------------------

	// the author is
	/*
    if (!isset( $_POST['authors']))
    {
        return get_lang('AuthorFieldCannotBeEmpty');
    }
    */

    // there are no recipients selected
	if ( !isset( $_POST['recipients']) || count( $_POST['recipients']) <= 0)
    {
        return get_lang('YouMustSelectAtLeastOneDestinee');
    }
    // Check if all the recipients are valid
    else
    {
        $thisIsAMailing = FALSE;  // RH: Mailing selected as destination
        $thisIsJustUpload = FALSE;  // RH
	    foreach( $_POST['recipients'] as $rec)
        {
			if ( $rec == 'mailing')
			{
				$thisIsAMailing = TRUE;
			}
			elseif ( $rec == 'upload')
			{
				$thisIsJustUpload = TRUE;
			}
			elseif (strpos($rec, 'user_') === 0 && !isCourseMember(substr($rec, strlen('user_') ) ))
			{
		        return get_lang('InvalideUserDetected');
			}
			elseif (strpos($rec, 'group_') !== 0 && strpos($rec, 'user_') !== 0)
			{
				return get_lang('InvalideGroupDetected');
			}
        }
    }

	// we are doing a mailing but an additional recipient is selected
	if ( $thisIsAMailing && ( count($_POST['recipients']) != 1))
	{
		return get_lang('MailingSelectNoOther');
	}

	// we are doing a just upload but an additional recipient is selected.
	// note: why can't this be valid? It is like sending a document to yourself AND to a different person (I do this quite often with my e-mails)
	if ( $thisIsJustUpload && ( count($_POST['recipients']) != 1))
	{
		return get_lang('mailingJustUploadSelectNoOther');
	}

	if ( empty( $_FILES['file']['name']))
	{
		$error = TRUE;
		return  get_lang('NoFileSpecified');
	}

	// ----------------------------------------------------------
	// are we overwriting a previous file or sending a new one
	// ----------------------------------------------------------
	$dropbox_overwrite = false;
	if ( isset($_POST['cb_overwrite']) && $_POST['cb_overwrite']==true)
	{
		$dropbox_overwrite = true;
	}

	// ----------------------------------------------------------
	// doing the upload
	// ----------------------------------------------------------
	$dropbox_filename = $_FILES['file']['name'];
	$dropbox_filesize = $_FILES['file']['size'];
	$dropbox_filetype = $_FILES['file']['type'];
	$dropbox_filetmpname = $_FILES['file']['tmp_name'];

	// check if the filesize does not exceed the allowed size.
	if ( $dropbox_filesize <= 0 || $dropbox_filesize > $dropbox_cnf["maxFilesize"])
	{
		return get_lang('DropboxFileTooBig');
	}

	// check if the file is actually uploaded
	if ( !is_uploaded_file( $dropbox_filetmpname)) // check user fraud : no clean error msg.
	{
		return get_lang('TheFileIsNotUploaded');
	}

	// Try to add an extension to the file if it hasn't got one
	$dropbox_filename = add_ext_on_mime( $dropbox_filename,$dropbox_filetype);
	// Replace dangerous characters
	$dropbox_filename = replace_dangerous_char( $dropbox_filename);
	// Transform any .php file in .phps fo security
	$dropbox_filename = php2phps ( $dropbox_filename);
	//filter extension
    if(!filter_extension($dropbox_filename))
    {
    	return get_lang('UplUnableToSaveFileFilteredExtension');
    }
	
	// set title
	$dropbox_title = $dropbox_filename;
	// set author
	if ( $_POST['authors'] == '')
	{
		$_POST['authors'] = getUserNameFromId( $_user['user_id']);
	}

	// note: I think we could better migrate everything from here on to separate functions: store_new_dropbox, store_new_mailing, store_just_upload

	if ($dropbox_overwrite)  // RH: Mailing: adapted
	{
		$dropbox_person = new Dropbox_Person( $_user['user_id'], api_is_course_admin(), api_is_course_tutor());

		foreach($dropbox_person->sentWork as $w)
		{
			if ($w->title == $dropbox_filename)
			{
			    if ( ($w->recipients[0]['id'] > dropbox_cnf("mailingIdBase")) xor $thisIsAMailing)
			    {
					return get_lang('MailingNonMailingError');
				}
				if ( ($w->recipients[0]['id'] == $_user['user_id']) xor $thisIsJustUpload)
				{
					return get_lang('MailingJustUploadSelectNoOther');
				}
				$dropbox_filename = $w->filename;
				$found = true; // note: do we still need this?
				break;
			}
		}
	}
	else  // rename file to login_filename_uniqueId format
	{
		$dropbox_filename = getLoginFromId( $_user['user_id']) . "_" . $dropbox_filename . "_".uniqid('');
	}

	// creating the array that contains all the users who will receive the file
	$new_work_recipients = array();
	foreach ($_POST["recipients"] as $rec)
	{
		if (strpos($rec, 'user_') === 0)
		{
			$new_work_recipients[] = substr($rec, strlen('user_') );
		}
		elseif (strpos($rec, 'group_') === 0 )
		{
			$userList = GroupManager::get_subscribed_users(substr($rec, strlen('group_') ));
			foreach ($userList as $usr)
			{
				if (! in_array($usr['user_id'], $new_work_recipients) && $usr['user_id'] != $_user['user_id'])
				{
					$new_work_recipients[] = $usr['user_id'];
				}
			}
		}
	}

	@move_uploaded_file( $dropbox_filetmpname, dropbox_cnf("sysPath") . '/' . $dropbox_filename);
	
	$b_send_mail = api_get_course_setting('email_alert_on_new_doc_dropbox');
	
	if($b_send_mail)
	{
		foreach($new_work_recipients as $recipient_id)
		{
			include_once(api_get_path(LIBRARY_PATH) . 'usermanager.lib.php');
			$recipent_temp=UserManager :: get_user_info_by_id($recipient_id);
			api_mail($recipent_temp['lastname'].' '.$recipent_temp['firstname'],$recipent_temp['email'],
				get_lang('NewDropboxFileUploaded'),
				get_lang('NewDropboxFileUploadedContent').' '.api_get_path(WEB_CODE_PATH).'dropbox/index.php?cidReq='.$_course['sysCode']."\n\n".$_user['firstName']." ".$_user['lastName']."\n".  get_lang('Email') ." : ".$_user['mail'], $_user['firstName']." ".$_user['lastName'],$_user['mail']);
				//get_lang('NewDropboxFileUploadedContent').' '.api_get_path(WEB_CODE_PATH).'dropbox/index.php?cidReq='.$_course['sysCode']."\n\n".get_setting('administratorName')." ".get_setting('administratorSurname')."\n". get_lang('Manager'). " ".get_setting('siteName')."\n" .get_lang('Email') ." : ".get_setting('emailAdministrator'),get_setting('administratorName')." ".get_setting('administratorSurname'),get_setting('emailAdministrator'));
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
* @todo move this function to the user library
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function display_user_link($user_id, $name='')
{
	global $_otherusers;

	if ($user_id<>0)
	{
		if ($name=='')
		{
			$table_user = Database::get_main_table(TABLE_MAIN_USER);
			$sql="SELECT * FROM $table_user WHERE user_id='".Database::escape_string($user_id)."'";
			$result=api_sql_query($sql,__FILE__,__LINE__);
			$row=mysql_fetch_array($result);
			return "<a href=\"../user/userInfo.php?uInfo=".$row['user_id']."\">".$row['firstname']." ".$row['lastname']."</a>";
		}
		else
		{
			return "<a href=\"../user/userInfo.php?uInfo=".$user_id."\">".$name."</a>";
		}
	}
	else
	{
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
function feedback($array)
{

	foreach ($array as $key=>$value)
	{
		$output.=format_feedback($value);
	}
	$output.=feedback_form();
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
function format_feedback($feedback)
{
	$output.=display_user_link($feedback['author_user_id']);
	$output.='&nbsp;&nbsp;['.$feedback['feedback_date'].']<br>';
	$output.='<div style="padding-top:6px">'.nl2br($feedback['feedback']).'</div><hr size="1" noshade/><br>';
	return $output;
}

/**
* this function returns the code for the form for adding a new feedback message to a dropbox file.
* @return html code
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function feedback_form()
{
	global $dropbox_cnf;

	$return = get_lang('AddNewFeedback').'<br />';

	// we now check if the other users have not delete this document yet. If this is the case then it is useless to see the
	// add feedback since the other users will never get to see the feedback.
	$sql="SELECT * FROM ".$dropbox_cnf["tbl_person"]." WHERE file_id='".Database::escape_string($_GET['id'])."'";
	$result=api_sql_query($sql,__LINE__, __FILE__);
	$number_users_who_see_file=mysql_num_rows($result);
	if ($number_users_who_see_file>1)
	{
		$return .= '<textarea name="feedback" style="width: 80%; height: 80px;"></textarea><br /><button type="submit" class="add" name="store_feedback" value="'.get_lang('Ok').'" 
			onclick="document.form_tablename.attributes.action.value = document.location;">'.get_lang('AddComment').'</button>';
	}
	else
	{
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
function store_feedback()
{
	global $dropbox_cnf;
	global $_user;

	if (!is_numeric($_GET['id']))
	{
		return get_lang('FeedbackError');
	}

	if ($_POST['feedback']=='')
	{
		return get_lang('PleaseTypeText');
	}
	else
	{
		$sql="INSERT INTO ".$dropbox_cnf['tbl_feedback']." (file_id, author_user_id, feedback, feedback_date) VALUES
				('".Database::escape_string($_GET['id'])."','".Database::escape_string($_user['user_id'])."','".Database::escape_string($_POST['feedback'])."',NOW())";
		api_sql_query($sql);
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
function zip_download ($array)
{
	global $_course;
	global $dropbox_cnf;
	global $_user;
	global $files;
	
	$sys_course_path = api_get_path(SYS_COURSE_PATH);
	
	// zip library for creation of the zipfile
	include(api_get_path(LIBRARY_PATH)."/pclzip/pclzip.lib.php");

	// place to temporarily stash the zipfiles
	$temp_zip_dir = api_get_path(SYS_COURSE_PATH).$_course['path']."/temp/";

	// create the directory if it does not exist yet.
	if(!is_dir($temp_zip_dir))
	{
		mkdir($temp_zip_dir);
	}

	cleanup_temp_dropbox();

	$files='';

	// note: we also have to add the check if the user has received or sent this file. !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	$sql="SELECT distinct file.filename, file.title, file.author, file.description
			FROM ".$dropbox_cnf["tbl_file"]." file, ".$dropbox_cnf["tbl_person"]." person
			WHERE file.id IN (".implode(', ',$array).")
			AND file.id=person.file_id
			AND person.user_id='".$_user['user_id']."'";
	$result=api_sql_query($sql,__FILE__,__LINE__);
	while ($row=mysql_fetch_array($result))
	{
		$files[$row['filename']]=array('filename'=>$row['filename'],'title'=>$row['title'], 'author'=>$row['author'], 'description'=>$row['description']);
	}

	//$alternative is a variable that uses an alternative method to create the zip
	// because the renaming of the files inside the zip causes error on php5 (unexpected end of archive)
	$alternative=true;
	if ($alternative)
	{
		zip_download_alternative($files);
		exit;
	}

	// create the zip file
    $name = 'dropboxdownload-'.$_user['user_id'].'-'.mktime().'.zip';
	$temp_zip_file=$temp_zip_dir.'/'.$name;
	$zip_folder=new PclZip($temp_zip_file);

	foreach ($files as $key=>$value)
	{
		// met hernoemen van de files in de zip
		$zip_folder->add(api_get_path(SYS_COURSE_PATH).$_course['path']."/dropbox/".$value['filename'],PCLZIP_OPT_REMOVE_PATH, api_get_path(SYS_COURSE_PATH).$_course['path']."/dropbox", PCLZIP_CB_PRE_ADD, 'my_pre_add_callback');
		// zonder hernoemen van de files in de zip
		//$zip_folder->add(api_get_path(SYS_COURSE_PATH).$_course['path']."/dropbox/".$value['filename'],PCLZIP_OPT_REMOVE_PATH, api_get_path(SYS_COURSE_PATH).$_course['path']."/dropbox");
	}

	// create the overview file
	$overview_file_content=generate_html_overview($files, array('filename'), array('title'));
	$overview_file=$temp_zip_dir.'/overview.html';
	$handle=fopen($overview_file,'w');
	fwrite($handle,$overview_file_content);


	// send the zip file
	DocumentManager::file_send_for_download($temp_zip_file,true,$name);
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
function my_pre_add_callback($p_event, &$p_header)
{
	global $files;

	$p_header['stored_filename']=$files[$p_header['stored_filename']]['title'];
	return 1;
}

/**
* This function is an alternative zip download. It was added because PCLZip causes problems on PHP5 when using PCLZIP_CB_PRE_ADD and a callback function to rename
* the files inside the zip file (dropbox scrambles the files to prevent
* @todo consider using a htaccess that denies direct access to the file but only allows the php file to access it. This would remove the scrambling requirement
*		but it would require additional checks to see if the filename of the uploaded file is not used yet.
* @param $files is an associative array that contains the files that the user wants to download (check to see if the user is allowed to download these files already
*		 happened so the array is clean!!. The key is the filename on the filesystem. The value is an array that contains both the filename on the filesystem and
*		 the original filename (that will be used in the zip file)
* @todo when we copy the files there might be two files with the same name. We need a function that (recursively) checks this and changes the name
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function zip_download_alternative($files)
{
	global $_course;
	global $_user;

	$temp_zip_dir = api_get_path(SYS_COURSE_PATH).$_course['path']."/temp/";

	// Step 2: we copy all the original dropbox files to the temp folder and change their name into the original name
	foreach ($files as $key=>$value)
	{
		$value['title']=check_file_name(strtolower($value['title']));
		$files[$value['filename']]['title']=$value['title'];
		copy(api_get_path(SYS_COURSE_PATH).$_course['path']."/dropbox/".$value['filename'], api_get_path(SYS_COURSE_PATH).$_course['path']."/temp/".$value['title']);
	}

	// Step 3: create the zip file and add all the files to it
	$temp_zip_file=$temp_zip_dir.'/dropboxdownload-'.$_user['user_id'].'-'.mktime().'.zip';
	$zip_folder=new PclZip($temp_zip_file);
	foreach ($files as $key=>$value)
	{
		$zip_folder->add(api_get_path(SYS_COURSE_PATH).$_course['path']."/temp/".$value['title'],PCLZIP_OPT_REMOVE_PATH, api_get_path(SYS_COURSE_PATH).$_course['path']."/temp");
	}
	
	// Step 1: create the overview file and add it to the zip
	$overview_file_content=generate_html_overview($files, array('filename'), array('title'));
	$overview_file=$temp_zip_dir.'overview'.$_user['firstname'].$_user['lastname'].'.html';
	$handle=fopen($overview_file,'w');
	fwrite($handle,$overview_file_content);
	// todo: find a different solution for this because even 2 seconds is no guarantee.
	sleep(2);	

	// Step 4: we add the overview file
	$zip_folder->add($overview_file,PCLZIP_OPT_REMOVE_PATH, api_get_path(SYS_COURSE_PATH).$_course['path']."/temp");

	// Step 5: send the file for download;
	DocumentManager::file_send_for_download($temp_zip_file,true);

	// Step 6: remove the files in the temp dir
	foreach ($files as $key=>$value)
	{
		unlink(api_get_path(SYS_COURSE_PATH).$_course['path']."/temp/".$value['title']);
	}
	//unlink($overview_file);

	exit;
}

/**
* @desc This function checks if the real filename of the dropbox files doesn't already exist in the temp folder. If this is the case then
*		it will generate a different filename;
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function check_file_name($file_name_2_check, $counter=0)
{
	global $_course;

	$new_file_name=$file_name_2_check;
	if ($counter<>0)
	{
		$new_file_name=$counter.$new_file_name;
	}

	if (!file_exists(api_get_path(SYS_COURSE_PATH).$_course['path']."/temp/".$new_file_name))
	{
		return $new_file_name;
	}
	else
	{
		$counter++;
		$new_file_name=check_file_name($file_name_2_check,$counter);
		return $new_file_name;
	}
}


/**
* @desc Cleans the temp zip files that were created when users download several files or a whole folder at once.
*		T
* @return true
* @todo
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/

function cleanup_temp_dropbox()
{
	global $_course;

	$handle=opendir(api_get_path(SYS_COURSE_PATH).$_course['path']."/temp");
	while (false !== ($file = readdir($handle)))
	{
		if ($file<>'.' OR $file<>'..')
		{
			$name=str_replace('.zip', '',$file);
			$name_part=explode('-',$name);
			$timestamp_of_file=$name_part[count($name_part)-1];
			// if it is a dropboxdownloadfile and the file is older than one day then we delete it
			if (strstr($file, 'dropboxdownload') AND $timestamp_of_file<(mktime()-86400))
			{
				unlink(api_get_path(SYS_COURSE_PATH).$_course['path']."/temp/".$file);
			}
		}

	}
	closedir($handle);
	return true;
}

/**
* @desc generates the contents of a html file that gives an overview of all the files in the zip file.
*		This is to know the information of the files that are inside the zip file (who send it, the comment, ...)
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function generate_html_overview($files, $dont_show_columns=array(), $make_link=array())
{
	$return="<html>\n<head>\n\t<title>".get_lang('OverviewOfFilesInThisZip')."</title>\n</head>";
	$return.="\n\n<body>\n<table border=\"1px\">";

	$counter=0;
	foreach ($files as $key=>$value)
	{
		// We add the header
		if ($counter==0)
		{
			$columns_array=array_keys($value);
			$return.="\n<tr>";
			foreach ($columns_array AS $columns_array_key=>$columns_array_value)
			{
				if (!in_array($columns_array_value,$dont_show_columns))
				{
					$return.="\n\t<th>".$columns_array_value."</th>";
				}
				$column[]=$columns_array_value;
			}
			$return.="</tr><n";
		}
		$counter++;

		// We add the content
		$return.="\n<tr>";
		foreach ($column AS $column_key=>$column_value)
		{
			if (!in_array($column_value,$dont_show_columns))
			{
				$return.="\n\t<td>";
				if (in_array($column_value, $make_link))
				{
					$return.='<a href="'.$value[$column_value].'">'.$value[$column_value].'</a>';
				}
				else
				{
					$return.=$value[$column_value];
				}
				$return.="</td>";
			}
		}
		$return.="</tr><n";


	}
	$return.="\n</table>\n\n</body>";
	$return.="\n</html>";

	return $return;
}

/**
* @desc This function retrieves the number of feedback messages on every document. This function might become obsolete when
* 		the feedback becomes user individual.
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function get_total_number_feedback($file_id='')
{
	global $dropbox_cnf;

	$sql="SELECT COUNT(feedback_id) AS total, file_id FROM ".$dropbox_cnf['tbl_feedback']." GROUP BY file_id";
	$result=api_sql_query($sql, __FILE__, __LINE__);
	while ($row=mysql_fetch_array($result))
	{
		$return[$row['file_id']]=$row['total'];
	}
	return $return;
}


/**
* @desc this function checks if the key exists. If this is the case it returns the value, if not it returns 0
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version march 2006
*/
function check_number_feedback($key, $array)
{
	if (is_array($array))
	{	
		if (key_exists($key,$array))
		{
			return $array[$key];
		}
		else
		{
			return 0;
		}
	}
	else
	{
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
function get_last_tool_access($tool, $course_code='', $user_id='')
{
	global $_course, $_user;

	// The default values of the parameters
	if ($course_code=='')
	{
		$course_code=$_course['id'];
	}
	if ($user_id=='')
	{
		$user_id=$_user['user_id'];
	}

	// the table where the last tool access is stored (=track_e_lastaccess)
	$table_last_access=Database::get_statistic_table('track_e_lastaccess');

	$sql="SELECT access_date FROM $table_last_access WHERE access_user_id='".Database::escape_string($user_id)."'
				AND access_cours_code='".Database::escape_string($course_code)."'
				AND access_tool='".Database::escape_string($tool)."'
				ORDER BY access_date DESC
				LIMIT 1";
	$result=api_sql_query($sql,__FILE__,__LINE__);
	$row=mysql_fetch_array($result);
	return $row['access_date'];
}
?>
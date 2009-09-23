<?php
/*
THIS FILE IS DEPRECATED - ONLY REMAINING FOR LEGACY CODE STUDY FOR NOW - YW20070129

==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2007 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
==============================================================================
* Update the Dokeos database from an older version
* Notice : This script has to be included by index.php or update_courses.php
*
* @package dokeos.install
* @todo
* - conditional changing of tables. Currently we execute for example
* ALTER TABLE `$dbNameForm`.`cours` instructions without checking wether this is necessary.
* - reorganise code into functions
* @todo use database library
==============================================================================
*/

//load helper functions
require_once("install_upgrade.lib.php");

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

//check if we come from index.php or update_courses.php - otherwise display error msg
if (defined('DOKEOS_INSTALL') || defined('DOKEOS_COURSE_UPDATE'))
{
	//check if the current Dokeos install is elligible for update
	if (empty ($updateFromConfigFile) || !file_exists($_POST['updatePath'].$updateFromConfigFile) || !in_array(get_config_param('clarolineVersion'), $update_from_version))
	{
		echo '<b>'.get_lang('Error').' !</b> Dokeos '.implode('|', $updateFromVersion).' '.get_lang('HasNotBeenFound').'.<br><br>
								'.get_lang('PleasGoBackToStep1').'.
							    <p><button type="submit" class="back" name="step1" value="&lt; '.get_lang('Back').'">'.get_lang('Back').'</button></p>
							    </td></tr></table></form></body></html>';

		exit ();
	}

	//get_config_param() comes from install_functions.inc.php and
	//actually gets the param from
	$_configuration['db_glue'] = get_config_param('dbGlu');

	if ($singleDbForm)
	{
		$_configuration['table_prefix'] = get_config_param('courseTablePrefix');
	}

	$dbScormForm = eregi_replace('[^a-z0-9_-]', '', $dbScormForm);

	if (!empty ($dbPrefixForm) && !ereg('^'.$dbPrefixForm, $dbScormForm))
	{
		$dbScormForm = $dbPrefixForm.$dbScormForm;
	}

	if (empty ($dbScormForm) || $dbScormForm == 'mysql' || $dbScormForm == $dbPrefixForm)
	{
		$dbScormForm = $dbPrefixForm.'scorm';
	}
	@mysql_connect($dbHostForm, $dbUsernameForm, $dbPassForm);

	//if error on connection to the database, show error and exit
	if (mysql_errno() > 0)
	{
		$no = mysql_errno();
		$msg = mysql_error();

		echo '<hr>['.$no.'] - '.$msg.'<hr>
								'.get_lang('DBServerDoesntWorkOrLoginPassIsWrong').'.<br><br>
								'.get_lang('PleaseCheckTheseValues').' :<br><br>
							    <b>'.get_lang('DBHost').'</b> : '.$dbHostForm.'<br>
								<b>'.get_lang('DBLogin').'</b> : '.$dbUsernameForm.'<br>
								<b>'.get_lang('DBPassword').'</b> : '.$dbPassForm.'<br><br>
								'.get_lang('PleaseGoBackToStep').' '. (defined('DOKEOS_INSTALL') ? '3' : '1').'.
							    <p><buton class="back" type="submit" name="step'. (defined('DOKEOS_INSTALL') ? '3' : '1').'" value="&lt; '.get_lang('Back').'">'.get_lang('Back').'</button></p>
							    </td></tr></table></form></body></html>';

		exit ();
	}

	// The Dokeos system has not been designed to use special SQL modes that were introduced since MySQL 5
	@mysql_query("set session sql_mode='';");

	/*
	-----------------------------------------------------------
		Normal upgrade procedure:
		start by updating main, statistic, user databases
	-----------------------------------------------------------
	*/
	//if this script has been included by index.php, not update_courses.php, so
	// that we want to change the main databases as well...
	$only_test = false;
	if (defined('DOKEOS_INSTALL'))
	{
		/**
		 * Update the databases "pre" migration
		 */
		include ("../lang/english/create_course.inc.php");

		if ($languageForm != 'english')
		{
			//languageForm has been escaped in index.php
			include ("../lang/$languageForm/create_course.inc.php");
		}

		//TODO deal with migrate-db-1.6.x-1.8.0-pre.sql here
		//get the main queries list (m_q_list)
		$m_q_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-pre.sql','main');
		if(count($m_q_list)>0)
		{
			//now use the $m_q_list
			/**
			 * We connect to the right DB first to make sure we can use the queries
			 * without a database name
			 */
			mysql_select_db($dbNameForm);
			foreach($m_q_list as $query){
				if($only_test){
					echo "mysql_query($dbNameForm,$query)<br/>";
				}else{
					$res = mysql_query($query);
				}
			}
		}
		//manual updates in here
		//update all registration_date, expiration_date and active fields
		//$sql_upd = "UPDATE user SET registration_date=NOW()";
		//$res_upd = mysql_query($sql_upd);
		//end of manual updates

		//get the stats queries list (s_q_list)
		$s_q_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-pre.sql','stats');
		if(count($s_q_list)>0)
		{
			//now use the $s_q_list
			/**
			 * We connect to the right DB first to make sure we can use the queries
			 * without a database name
			 */
			mysql_select_db($dbStatsForm);
			foreach($s_q_list as $query){
				if($only_test){
					echo "mysql_query($dbStatsForm,$query)<br/>";
				}else{
					$res = mysql_query($query);
				}
			}
		}
		//get the user queries list (u_q_list)
		$u_q_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-pre.sql','user');
		if(count($u_q_list)>0)
		{
			//now use the $u_q_list
			/**
			 * We connect to the right DB first to make sure we can use the queries
			 * without a database name
			 */
			mysql_select_db($dbUserForm);
			foreach($u_q_list as $query){
				if($only_test){
					echo "mysql_query($dbUserForm,$query)<br/>";
				}else{
					$res = mysql_query($query);
				}
			}
		}
		//the SCORM database doesn't need a change in the pre-migrate part - ignore

		die();
		//TODO only update this table
		/*
		$language_table = "`$dbNameForm`.`language`";
		fill_language_table($language_table);

		//set the settings from the form or the old config into config settings.
		//These settings are considered "safe" because they are entered by the admin
		$installation_settings['institution_form'] = $institutionForm;
		$installation_settings['institution_url_form'] = $institutionUrlForm;
		$installation_settings['campus_form'] = $campusForm;
		$installation_settings['email_form'] = $emailForm;
		$installation_settings['admin_last_name'] = $adminLastName;
		$installation_settings['admin_first_name'] = $adminFirstName;
		$installation_settings['language_form'] = $languageForm;
		$installation_settings['allow_self_registration'] = $allowSelfReg;
		$installation_settings['allow_teacher_self_registration'] = $allowSelfRegProf;
		$installation_settings['admin_phone_form'] = $adminPhoneForm;

		//put the settings into the settings table (taken from CSV file)
		$current_settings_table = "`$dbNameForm`.`settings_current`";
		fill_current_settings_table($current_settings_table, $installation_settings);

		//put the options into the options table (taken from CSV file)
		$settings_options_table = "`$dbNameForm`.`settings_options`";
		fill_settings_options_table($settings_options_table);

		//mysql_query("INSERT INTO `$dbNameForm`.`course_module` (`name`,`link`,`image`,`row`,`column`,`position`) VALUES
		//								('AddedLearnpath', NULL, 'scormbuilder.gif', 0, 0, 'external'),
		//								('".TOOL_BACKUP."', 'coursecopy/backup.php' , 'backup.gif', 2, 1, 'courseadmin'),
		//								('".TOOL_COPY_COURSE_CONTENT."', 'coursecopy/copy_course.php' , 'copy.gif', 2, 2, 'courseadmin'),
		//								('".TOOL_RECYCLE_COURSE."', 'coursecopy/recycle_course.php' , 'recycle.gif', 2, 3, 'courseadmin')");
		//...
		//mysql_query("UPDATE `$dbNameForm`.`course_module` SET name='".TOOL_LEARNPATH."' WHERE link LIKE 'scorm/%'");
		*/
	}

	/*
	-----------------------------------------------------------
		Update the Dokeos course databases
		this part can be accessed in two ways:
		- from the normal upgrade process
		- from the script update_courses.php,
		which is used to upgrade more than MAX_COURSE_TRANSFER courses

		Every time this script is accessed, only
		MAX_COURSE_TRANSFER courses are upgraded.
	-----------------------------------------------------------
	*/

	//get the courses databases queries list (c_q_list)
	$c_q_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-pre.sql','course');
	if(count($c_q_list)>0)
	{
		//get the courses list
		mysql_select_db($dbNameForm);
		$res = mysql_query("SELECT code,db_name,directory,course_language FROM course WHERE target_course_code IS NULL");
		if($res===false){die('Error while querying the courses list in update_db.inc.php');}
		if(mysql_num_rows($res)>0)
		{
			while($row = mysql_fetch_array($res))
			{
				$list[] = $row;
			}
			foreach($list as $row)
			{
				//now use the $c_q_list
				/**
				 * We connect to the right DB first to make sure we can use the queries
				 * without a database name
				 */
				mysql_select_db($row['db_name']);
				foreach($c_q_list as $query)
				{
					if($only_test)
					{
						echo "mysql_query(".$row['db_name'].",$query)<br/>";
					}else{
						$res = mysql_query($query);
					}
				}
				//update course manually
				//update group_category.forum_state
				//update group_info.tutor_id (put it in group_tutor table?)
				//update group_info.forum_state, forum_id

				//update forum tables (migrate from bb_ tables to forum_ tables)
				//migrate categories
				$sql_orig = "SELECT * FROM bb_categories";
				$res_orig = mysql_query($sql_orig);
				while($row = mysql_fetch_array($res_orig)){
					$sql = "INSERT INTO forum_category " .
							"(cat_id,cat_title,cat_comment,cat_order,locked) VALUES " .
							"('".$row['cat_id']."','".$row['cat_title']."','','".$row['cat_order']."',0)";
					$res = mysql_query($sql);
				}
				$sql_orig = "SELECT * FROM bb_forums ORDER BY forum_last_post_id desc";
				$res_orig = mysql_query($sql_orig);
				$order = 1;
				while($row = mysql_fetch_array($res_orig)){
					$sql = "INSERT INTO forum_forum " .
							"(forum_id,forum_category,allow_edit,forum_comment," .
							"forum_title," .
							"forum_last_post, forum_threads," .
							"locked, forum_posts, " .
							"allow_new_threads, forum_order) VALUES " .
							"('".$row['forum_id']."','".$row['cat_id']."',1,'".$row['forum_desc']."'," .
							"'".$row['forum_name']."'," .
							"'".$row['forum_last_post_id']."','".$row['forum_topics']."'," .
							"0,'".$row['forum_posts']."'," .
							"1,$order)";
					$res = mysql_query($sql);
					$order++;
				}
				$sql_orig = "SELECT * FROM bb_topics";
				$res_orig = mysql_query($sql_orig);
				while($row = mysql_fetch_array($res_orig)){
					//convert time from varchar to datetime
					$time = $row['topic_time'];
					$name = $row['prenom']." ".$row['nom'];
					$sql = "INSERT INTO forum_thread " .
							"(thread_id,forum_id,thread_poster_id," .
							"locked,thread_replies,thread_sticky,thread_title," .
							"thread_poster_name, thread_date, thread_last_post," .
							"thread_views) VALUES " .
							"('".$row['topic_id']."','".$row['forum_id']."','".$row['topic_poster']."'," .
							"0,'".$row['topic_replies']."',0,'".$row['topic_title']."'," .
							"'$name','$time','".$row['topic_last_post_id']."'," .
							"'".$row['topic_views']."')";
					$res = mysql_query($sql);
				}
				$sql_orig = "SELECT * FROM bb_posts, bb_posts_text WHERE bb_posts.post_id = bb_posts_text.post_id";
				$res_orig = mysql_query($sql_orig);
				while($row = mysql_fetch_array($res_orig)){
					//convert time from varchar to datetime
					$time = $row['post_time'];
					$name = $row['prenom']." ".$row['nom'];
					$sql = "INSERT INTO forum_post " .
							"(post_id,forum_id,thread_id," .
							"poster_id,post_parent_id,visible, " .
							"post_title,poster_name, post_text, " .
							"post_date, post_notification) VALUES " .
							"('".$row['post_id']."','".$row['forum_id']."','".$row['topic_id']."'," .
							"'".$row['poster_id']."','".$row['parent_id']."',1," .
							"'".$row['post_title']."','$name', '".$row['post_text']."'," .
							"'$time',0)";
					$res = mysql_query($sql);
				}
			}
		}
	}

	$newPath = str_replace('\\', '/', realpath('../..')).'/';

	$coursePath = array ();
	$courseDB = array ();
	$nbr_courses = 0;

	if ($result = mysql_query("SELECT code,db_name,directory,course_language FROM `$dbNameForm`.`course` WHERE target_course_code IS NULL"))
	{
		$i = 0;

		$nbr_courses = mysql_num_rows($result);

		while ($i < MAX_COURSE_TRANSFER && (list ($course_code, $mysql_base_course, $directory, $languageCourse) = mysql_fetch_row($result)))
		{
			if (!file_exists($newPath.'courses/'.$directory))
			{
				if ($singleDbForm)
				{
					$prefix = $_configuration['table_prefix'].$mysql_base_course.$_configuration['db_glue'];

					$mysql_base_course = $dbNameForm.'`.`'.$_configuration['table_prefix'].$mysql_base_course;
				}
				else
				{
					$prefix = '';
				}

				$coursePath[$course_code] = $directory;
				$courseDB[$course_code] = $mysql_base_course;

				include ("../lang/english/create_course.inc.php");

				if ($languageCourse != 'english')
				{
					include ("../lang/$languageCourse/create_course.inc.php");
				}

				//TODO process the whole course database migration here. Call an external
				//script/function to keep this script tidy

				// Set item-properties of dropbox files
				/*
				$sql = "SELECT * FROM `$mysql_base_course".$_configuration['db_glue']."dropbox_file` f, `".$_configuration['db_glue']."_base_course".$_configuration['db_glue']."dropbox_post` p WHERE f.id = p.file_id";
				$res = mysql_query($sql);
				while ($obj = mysql_fetch_object($res))
				{
					$sql = "INSERT INTO `$mysql_base_course".$_configuration['db_glue']."item_property` SET ";
					$sql .= " tool = '".TOOL_DROPBOX."', ";
					$sql .= " insert_date = '".$obj->upload_date."', ";
					$sql .= " lastedit_date = '".$obj->last_upload_date."', ";
					$sql .= " ref = '".$obj->id."', ";
					$sql .= " lastedit_type = 'DropboxFileAdded', ";
					$sql .= " to_group_id = '0', ";
					$sql .= " to_user_id = '".$obj->dest_user_id."', ";
					$sql .= " insert_user_id = '".$obj->uploader_id."'";
					mysql_query($sql);
				}
				*/

				$i ++;
			}
			else
			{
				$nbr_courses --;
			}
		}
	}
}
else
{
	echo 'You are not allowed here !';
}








/**
* This function stores the forum category in the database. The new category is added to the end.
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @todo is this the same function as in forumfunction.inc.php? If this is the case then it should not appear here.
*/
function store_forumcategory($values)
{
	global $table_categories;
	global $_course;
	global $_user;

	// find the max cat_order. The new forum category is added at the end => max cat_order + &
	$sql="SELECT MAX(cat_order) as sort_max FROM ".mysql_real_escape_string($table_categories);
	$result=api_sql_query($sql);
	$row=mysql_fetch_array($result);
	$new_max=$row['sort_max']+1;

	$sql="INSERT INTO ".$table_categories." (cat_title, cat_comment, cat_order) VALUES ('".mysql_real_escape_string($values['forum_category_title'])."','".mysql_real_escape_string($values['forum_category_comment'])."','".mysql_real_escape_string($new_max)."')";
	api_sql_query($sql);
	$last_id=mysql_insert_id();
	api_item_property_update($_course, TOOL_FORUM_CATEGORY, $last_id,"ForumCategoryAdded", $_user['user_id']);
	return array('id'=>$last_id,'title'=>$values['forum_category_title']) ;
}

/**
* This function stores the forum in the database. The new forum is added to the end.
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @todo is this the same function as in forumfunction.inc.php? If this is the case then it should not appear here.
*/
function store_forum($values)
{
	global $table_forums;
	global $_course;
	global $_user;

	// find the max forum_order for the given category. The new forum is added at the end => max cat_order + &
	$sql="SELECT MAX(forum_order) as sort_max FROM ".$table_forums." WHERE forum_category=".mysql_real_escape_string($values['forum_category']);
	$result=api_sql_query($sql);
	$row=mysql_fetch_array($result);
	$new_max=$row['sort_max']+1;


	$sql="INSERT INTO ".$table_forums."
				(forum_title, forum_comment, forum_category, allow_anonymous, allow_edit, approval_direct_post, allow_attachments, allow_new_threads, default_view, forum_of_group, forum_group_public_private, forum_order)
				VALUES ('".mysql_real_escape_string($values['forum_title'])."',
					'".mysql_real_escape_string($values['forum_comment'])."',
					'".mysql_real_escape_string($values['forum_category'])."',
					'".mysql_real_escape_string($values['allow_anonymous_group']['allow_anonymous'])."',
					'".mysql_real_escape_string($values['students_can_edit_group']['students_can_edit'])."',
					'".mysql_real_escape_string($values['approval_direct_group']['approval_direct'])."',
					'".mysql_real_escape_string($values['allow_attachments_group']['allow_attachments'])."',
					'".mysql_real_escape_string($values['allow_new_threads_group']['allow_new_threads'])."',
					'".mysql_real_escape_string($values['default_view_type_group']['default_view_type'])."',
					'".mysql_real_escape_string($values['group_forum'])."',
					'".mysql_real_escape_string($values['public_private_group_forum_group']['public_private_group_forum'])."',
					'".mysql_real_escape_string($new_max)."')";
	api_sql_query($sql, __LINE__,__FILE__);
	$last_id=mysql_insert_id();
	api_item_property_update($_course, TOOL_FORUM, $last_id,"ForumCategoryAdded", $_user['user_id']);
	return array('id'=>$last_id, 'title'=>$values['forum_title']);
}

/**
* This function stores a new thread. This is done through an entry in the forum_thread table AND
* in the forum_post table because. The threads are also stored in the item_property table. (forum posts are not (yet))
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @todo is this the same function as in forumfunction.inc.php? If this is the case then it should not appear here.
*/
function store_thread($values)
{
	global $table_threads;
	global $table_posts;
	global $_user;
	global $_course;
	global $current_forum;

	// We first store an entry in the forum_thread table because the thread_id is used in the forum_post table
	$sql="INSERT INTO $table_threads (thread_title, forum_id, thread_poster_id, thread_poster_name, thread_views, thread_date, thread_sticky)
			VALUES ('".mysql_real_escape_string($values['post_title'])."',
					'".mysql_real_escape_string($values['forum_id'])."',
					'".mysql_real_escape_string($values['user_id'])."',
					'".mysql_real_escape_string($values['poster_name'])."',
					'".mysql_real_escape_string($values['topic_views'])."',
					'".mysql_real_escape_string($values['post_date'])."',
					'".mysql_real_escape_string($values['thread_sticky'])."')";
	$result=api_sql_query($sql, __LINE__, __FILE__);
	$last_thread_id=mysql_insert_id();
	api_item_property_update($_course, TOOL_FORUM_THREAD, $last_thread_id,"ForumThreadAdded", $_user['user_id']);
	// if the forum properties tell that the posts have to be approved we have to put the whole thread invisible
	// because otherwise the students will see the thread and not the post in the thread.
	// we also have to change $visible because the post itself has to be visible in this case (otherwise the teacher would have
	// to make the thread visible AND the post
	if ($values['visible']==0)
	{
		api_item_property_update($_course, TOOL_FORUM_THREAD, $last_thread_id,"invisible", $_user['user_id']);
		$visible=1;
	}

	return $last_thread_id;
}

/**
* This function migrates the threads of a given phpbb forum to a new forum of the new forum tool
* @param $phpbb_forum_id the forum_id of the old (phpbb) forum
* @param $new_forum_id the forum_id in the new forum
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @todo is this the same function as in forumfunction.inc.php? If this is the case then it should not appear here.
*/
function migrate_threads_of_forum($phpbb_forum_id, $new_forum_id)
{
	global $phpbb_threads;
	global $table_forums;

	$table_users = Database :: get_main_table(TABLE_MAIN_USER);

	$sql_phpbb_threads="SELECT forum.*, users.user_id
							FROM $phpbb_threads forum, $table_users users
							WHERE forum_id='".mysql_real_escape_string($phpbb_forum_id)."'
							AND forum.nom=users.lastname AND forum.prenom=users.firstname
							";
	$result_phpbb_threads=api_sql_query($sql_phpbb_threads);
	$threads_counter=0;
	while ($row_phpbb_threads=mysql_fetch_array($result_phpbb_threads))
	{
		$values['post_title']=$row_phpbb_threads['topic_title'];
		$values['forum_id']=$new_forum_id;
		$values['user_id']=$row_phpbb_threads['user_id'];
		$values['poster_name']=0;
		$values['topic_views']=$row_phpbb_threads['topic_views'];
		$values['post_date']=$row_phpbb_threads['topic_time'];
		$values['thread_sticky']=0;
		$values['visible']=$row_phpbb_threads['visible'];
		//my_print_r($values);
		$new_forum_thread_id=store_thread($values);

		// now we migrate the posts of the given thread
		$posts_counter=$posts_counter+migrate_posts_of_thread($row_phpbb_threads['topic_id'], $new_forum_thread_id, $new_forum_id);

		$threads_counter++;
	}

	// Now we update the forum_forum table with the total number of posts for the given forum.
	$sql="UPDATE $table_forums
			SET forum_posts='".mysql_real_escape_string($posts_counter)."',
			forum_threads='".mysql_real_escape_string($threads_counter)."'
			WHERE forum_id='".mysql_real_escape_string($new_forum_id)."'";
	//echo $sql;
	$result=api_sql_query($sql);
	return array("threads"=>$threads_counter, "posts"=>$posts_counter);
}

/**
* This function migrates the posts of a given phpbb thread (topic) to a thread in the new forum tool
* @param $phpbb_forum_id the forum_id of the old (phpbb) forum
* @param $new_forum_id the forum_id in the new forum
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @todo is this the same function as in forumfunction.inc.php? If this is the case then it should not appear here.
*/
function migrate_posts_of_thread($phpbb_thread_id, $new_forum_thread_id, $new_forum_id)
{
	global $phpbb_posts;
	global $phpbb_poststext;
	global $table_posts;
	global $table_threads;
	global $added_resources;

	$table_users = Database :: get_main_table(TABLE_MAIN_USER);
	$table_added_resources = Database::get_course_table(TABLE_LINKED_RESOURCES);


	$post_counter=0;

	$sql_phpbb_posts="SELECT posts.*, posts_text.*, users.user_id, users.lastname, users.firstname FROM $phpbb_posts posts, $phpbb_poststext posts_text, $table_users users
						WHERE posts.post_id=posts_text.post_id
						AND posts.nom=users.lastname
						AND posts.prenom=users.firstname
						AND posts.topic_id='".mysql_real_escape_string($phpbb_thread_id)."'
						";
	$result_phpbb_posts=api_sql_query($sql_phpbb_posts);
	while($row_phpbb_posts=mysql_fetch_array($result_phpbb_posts))
	{
		$values=array();
		$values['post_title']=$row_phpbb_posts['post_title'];
		$values['post_text']=$row_phpbb_posts['post_text'];
		$values['thread_id']=$new_forum_thread_id;
		$values['forum_id']=$new_forum_id;
		$values['user_id']=$row_phpbb_posts['user_id'];
		$values['post_date']=$row_phpbb_posts['post_time'];
		$values['post_notification']=$row_phpbb_posts['topic_notify'];
		$values['post_parent_id']=0;
		$values['visible']=1;

		// We first store an entry in the forum_post table
		$sql="INSERT INTO $table_posts (post_title, post_text, thread_id, forum_id, poster_id,  post_date, post_notification, post_parent_id, visible)
				VALUES ('".mysql_real_escape_string($values['post_title'])."',
						'".mysql_real_escape_string($values['post_text'])."',
						'".mysql_real_escape_string($values['thread_id'])."',
						'".mysql_real_escape_string($values['forum_id'])."',
						'".mysql_real_escape_string($values['user_id'])."',
						'".mysql_real_escape_string($values['post_date'])."',
						'".mysql_real_escape_string($values['post_notification'])."',
						'".mysql_real_escape_string($values['post_parent_id'])."',
						'".mysql_real_escape_string($values['visible'])."')";
		$result=api_sql_query($sql, __LINE__, __FILE__);
		$post_counter++;
		$last_post_id=mysql_insert_id();


		// We check if there added resources and if so we update them
		if (in_array($row_phpbb_posts['post_id'],$added_resources))
		{
			$sql_update_added_resource="UPDATE $table_added_resources
					SET source_type='forum_post', source_id='".mysql_real_escape_string($last_post_id)."'
					WHERE source_type='".mysql_real_escape_string(TOOL_BB_POST)."' AND source_id='".mysql_real_escape_string($row_phpbb_posts['post_id'])."'";
			echo $sql_update_added_resource;
			$result=api_sql_query($sql_update_added_resource, __LINE__, __FILE__);
		}


	}

	// update the thread_last_post of the post table AND the
	$sql="UPDATE $table_threads SET thread_last_post='".mysql_real_escape_string($last_post_id)."',
			thread_replies='".mysql_real_escape_string($post_counter-1)."'
			WHERE thread_id='".mysql_real_escape_string($new_forum_thread_id)."'";
	//echo $sql;
	$result=api_sql_query($sql, __LINE__, __FILE__);
	//echo $sql;
	return $post_counter;
}

/**
* This function gets all the added resources for phpbb forum posts
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @todo is this the same function as in forumfunction.inc.php? If this is the case then it should not appear here.
*/
function get_added_resources()
{
	$table_added_resources = Database::get_course_table(TABLE_LINKED_RESOURCES);
	$return_array=array();

	// TODO: now we also migrate the added resources.
	$sql_added_resources="SELECT * FROM $table_added_resources WHERE source_type='".mysql_real_escape_string(TOOL_BB_POST)."'";
	$result=api_sql_query($sql_added_resources);
	while ($row=mysql_fetch_array($result))
	{
		$return_array[]=$row['source_id'];
	}
	return $return_array;
}

/**
* This function gets the forum category information based on the name
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @todo is this the same function as in forumfunction.inc.php? If this is the case then it should not appear here.
*/
function get_forumcategory_id_by_name($forum_category_name)
{
	global $table_categories;

	$sql="SELECT cat_id FROM $table_categories WHERE cat_title='".mysql_real_escape_string($forum_category_name)."'";
	//echo $sql;
	$result=api_sql_query($sql,__LINE__,__FILE__);
	$row=mysql_fetch_array($result);
	//echo $row['cat_id'];
	return $row['cat_id'];
}
?>

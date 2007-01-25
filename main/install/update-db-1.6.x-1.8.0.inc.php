<?php
/*
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
							    <p><input type="submit" name="step1" value="&lt; '.get_lang('Back').'"></p>
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
							    <p><input type="submit" name="step'. (defined('DOKEOS_INSTALL') ? '3' : '1').'" value="&lt; '.get_lang('Back').'"></p>
							    </td></tr></table></form></body></html>';

		exit ();
	}

	/*
	-----------------------------------------------------------
		Normal upgrade procedure:
		start by updating main, statistic, user databases
	-----------------------------------------------------------
	*/
	//if this script has been included by index.php, not update_courses.php, so
	// that we want to change the main databases as well...
	$only_test = true;
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
			$i=0;
			while( ($i < MAX_COURSE_TRANSFER) && ($row = mysql_fetch_array($res)))
			{
				$list[] = $row;
				$i++;
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
}
else
{
	echo 'You are not allowed here !';
}
?>
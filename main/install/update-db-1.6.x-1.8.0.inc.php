<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
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

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
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

//remove memory and time limits as much as possible as this might be a long process...
if(function_exists('ini_set'))
{
	ini_set('memory_limit',-1);
	ini_set('max_execution_time',0);
}else{
	error_log('Update-db script: could not change memory and time limits',0);
}

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

//check if we come from index.php or update_courses.php - otherwise display error msg
if (defined('DOKEOS_INSTALL') || defined('DOKEOS_COURSE_UPDATE'))
{
	//check if the current Dokeos install is elligible for update
	if (empty ($updateFromConfigFile) || !file_exists($_POST['updatePath'].$updateFromConfigFile) || !in_array(get_config_param('clarolineVersion'), $update_from_version_6))
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
		$_configuration['main_database'] = get_config_param('mainDbName');
		$_configuration['db_prefix'] = get_config_param('dbNamePrefix');
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
	$res = @mysql_connect($dbHostForm, $dbUsernameForm, $dbPassForm);

	//if error on connection to the database, show error and exit
	if ($res === false)
	{
		//$no = mysql_errno();
		//$msg = mysql_error();

		//echo '<hr>['.$no.'] - '.$msg.'<hr>';
		echo					get_lang('DBServerDoesntWorkOrLoginPassIsWrong').'.<br /><br />
								'.get_lang('PleaseCheckTheseValues').' :<br /><br />
							    <b>'.get_lang('DBHost').'</b> : '.$dbHostForm.'<br />
								<b>'.get_lang('DBLogin').'</b> : '.$dbUsernameForm.'<br />
								<b>'.get_lang('DBPassword').'</b> : '.$dbPassForm.'<br /><br />
								'.get_lang('PleaseGoBackToStep').' '. (defined('DOKEOS_INSTALL') ? '3' : '1').'.
							    <p><button type="submit" class="back" name="step'. (defined('DOKEOS_INSTALL') ? '3' : '1').'" value="&lt; '.get_lang('Back').'">'.get_lang('Back').'</button></p>
							    </td></tr></table></form></body></html>';

		exit ();
	}

	// The Dokeos system has not been designed to use special SQL modes that were introduced since MySQL 5
	@mysql_query("set session sql_mode='';");

	$dblistres = mysql_list_dbs();
	$dblist = array();
	while ($row = mysql_fetch_object($dblistres)) {
    	$dblist[] = $row->Database;
	}
	/*
	-----------------------------------------------------------
		Normal upgrade procedure:
		start by updating main, statistic, user databases
	-----------------------------------------------------------
	*/
	//if this script has been included by index.php, not update_courses.php, so
	// that we want to change the main databases as well...
	$only_test = false;
	$log = 0;
	if (defined('DOKEOS_INSTALL')) 
	{
		if ($singleDbForm)
		{
			if(empty($dbStatsForm)) $dbStatsForm = $dbNameForm;
			if(empty($dbScormForm)) $dbScormForm = $dbNameForm;
			if(empty($dbUserForm)) $dbUserForm = $dbNameForm;
		}
		/**
		 * Update the databases "pre" migration
		 */
		include ("../lang/english/create_course.inc.php");

		if ($languageForm != 'english')
		{
			//languageForm has been escaped in index.php
			include ("../lang/$languageForm/create_course.inc.php");
		}

		//get the main queries list (m_q_list)
		$m_q_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-pre.sql','main');
		if(count($m_q_list)>0)
		{
			//now use the $m_q_list
			/**
			 * We connect to the right DB first to make sure we can use the queries
			 * without a database name
			 */
			if(strlen($dbNameForm)>40){
				error_log('Database name '.$dbNameForm.' is too long, skipping',0);
			}elseif(!in_array($dbNameForm,$dblist)){
				error_log('Database '.$dbNameForm.' was not found, skipping',0);				
			}else{
				mysql_select_db($dbNameForm);
				foreach($m_q_list as $query){
					if($only_test){
						error_log("mysql_query($dbNameForm,$query)",0);
					}else{
						$res = mysql_query($query);
						if($log)
						{
							error_log("In $dbNameForm, executed: $query",0);
						}
					}
				}
			}
		}
		
		//get the stats queries list (s_q_list)
		$s_q_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-pre.sql','stats');
		if(count($s_q_list)>0)
		{
			//now use the $s_q_list
			/**
			 * We connect to the right DB first to make sure we can use the queries
			 * without a database name
			 */
			if(strlen($dbStatsForm)>40){
				error_log('Database name '.$dbStatsForm.' is too long, skipping',0);
			}elseif(!in_array($dbStatsForm,$dblist)){
				error_log('Database '.$dbStatsForm.' was not found, skipping',0);				
			}else{
				mysql_select_db($dbStatsForm);
				foreach($s_q_list as $query){
					if($only_test){
						error_log("mysql_query($dbStatsForm,$query)",0);
					}else{
						$res = mysql_query($query);
						if($log)
						{
							error_log("In $dbStatsForm, executed: $query",0);
						}
					}
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
			if(strlen($dbUserForm)>40){
				error_log('Database name '.$dbUserForm.' is too long, skipping',0);
			}elseif(!in_array($dbUserForm,$dblist)){
				error_log('Database '.$dbUserForm.' was not found, skipping',0);				
			}else{
				mysql_select_db($dbUserForm);
				foreach($u_q_list as $query){
					if($only_test){
						error_log("mysql_query($dbUserForm,$query)",0);
						error_log("In $dbUserForm, executed: $query",0);
					}else{
						$res = mysql_query($query);
					}
				}
			}
		}
		//the SCORM database doesn't need a change in the pre-migrate part - ignore
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

	$prefix = ''; 
	if ($singleDbForm)
	{
		$prefix = $_configuration['table_prefix'];
	}
	//get the courses databases queries list (c_q_list)
	$c_q_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-pre.sql','course');
	if(count($c_q_list)>0)
	{
		//get the courses list
		if(strlen($dbNameForm)>40){
			error_log('Database name '.$dbNameForm.' is too long, skipping',0);
		}elseif(!in_array($dbNameForm,$dblist)){
				error_log('Database '.$dbNameForm.' was not found, skipping',0);				
		}else{
			mysql_select_db($dbNameForm);
			$res = mysql_query("SELECT code,db_name,directory,course_language FROM course WHERE target_course_code IS NULL");
			if($res===false){die('Error while querying the courses list in update_db.inc.php');}
			if(mysql_num_rows($res)>0)
			{
				$i=0;
                $list = array();
				//while( ($i < MAX_COURSE_TRANSFER) && ($row = mysql_fetch_array($res)))
				while($row = mysql_fetch_array($res))
				{
					$list[] = $row;
					$i++;
				}
				foreach($list as $row_course)
				{
					//now use the $c_q_list
					/**
					 * We connect to the right DB first to make sure we can use the queries
					 * without a database name
					 */
					if (!$singleDbForm) //otherwise just use the main one
					{
						mysql_select_db($row_course['db_name']);
					}
					
					foreach($c_q_list as $query)
					{
						if ($singleDbForm) //otherwise just use the main one
						{
							$query = preg_replace('/^(UPDATE|ALTER TABLE|CREATE TABLE|DROP TABLE|INSERT INTO|DELETE FROM)\s+(\w*)(.*)$/',"$1 $prefix{$row_course['db_name']}_$2$3",$query);
						}
						
						if($only_test)
						{
							error_log("mysql_query(".$row_course['db_name'].",$query)",0);
						}else{
							$res = mysql_query($query);
							if($log)
							{
								error_log("In ".$row_course['db_name'].", executed: $query",0);
							}
						}
					}
	
					//prepare reusable users list to avoid repetition of the SQL query, but only select
					//users from the current course to avoid blowing the memory limit
					$users_list = array();
					$sql_uc = "SELECT u.user_id as ui, u.firstname as fn, u.lastname as ln " .
							" FROM $dbNameForm.user u, $dbNameForm.course_rel_user cu " .
							" WHERE cu.course_code = '".$row_course['code']."' " .
								" AND u.user_id = cu.user_id";
					$res_uc = mysql_query($sql_uc);
					while($user_row = mysql_fetch_array($res_uc))
					{
						$users_list[$user_row['fn'].' '.$user_row['ln']] = $user_row['ui'];
					}
	
					//update course manually
					//update group_category.forum_state ?
					//update group_info.tutor_id (put it in group_tutor table?) ?
					//update group_info.forum_state, forum_id ?
					
					//update forum tables (migrate from bb_ tables to forum_ tables)
					//migrate categories
					$prefix_course = $prefix;
					if($singleDbForm)
					{
						$prefix_course = $prefix.$row_course['db_name']."_";
					}

					$sql_orig = "SELECT * FROM ".$prefix_course."bb_categories";
					$res_orig = mysql_query($sql_orig);
					$order = 1;
					while($row = mysql_fetch_array($res_orig)){
						$myorder = (empty($row['cat_order'])?$order:$row['cat_order']);
						$sql = "INSERT INTO ".$prefix_course."forum_category " .
								"(cat_id,cat_title,cat_comment,cat_order,locked) VALUES " .
								"('".$row['cat_id']."','".mysql_real_escape_string($row['cat_title'])."','','".$myorder."',0)";
						$res = mysql_query($sql);
						$lastcatid = mysql_insert_id();
						//error_log($sql,0);
						$order ++;
						//add item_property - forum categories were not put into item_properties before
						$sql = "INSERT INTO ".$prefix_course."item_property (tool,insert_user_id,ref,lastedit_type,lastedit_user_id,visibility) " .
								"VALUES ('forum_category','1','$lastcatid','ForumCategoryAdded','1','1')";
						$res = mysql_query($sql);
						//error_log($sql,0);
					}
					$sql_orig = "SELECT * FROM ".$prefix_course."bb_forums ORDER BY forum_last_post_id desc";
					$res_orig = mysql_query($sql_orig);
					$order = 1;
					while($row = mysql_fetch_array($res_orig)){
						$sql = "INSERT INTO ".$prefix_course."forum_forum " .
								"(forum_id,forum_category,allow_edit,forum_comment," .
								"forum_title," .
								"forum_last_post, forum_threads," .
								"locked, forum_posts, " .
								"allow_new_threads, forum_order) VALUES " .
								"('".$row['forum_id']."','".$row['cat_id']."',1,'".mysql_real_escape_string($row['forum_desc'])."'," .
								"'".mysql_real_escape_string($row['forum_name'])."'," .
								"'".$row['forum_last_post_id']."','".$row['forum_topics']."'," .
								"0,'".$row['forum_posts']."'," .
								"1,$order)";
						//error_log($sql,0);
						$res = mysql_query($sql);
						$lastforumid = mysql_insert_id();
						$order++;
	
						//add item_property - forums were not put into item_properties before
						$sql = "INSERT INTO ".$prefix_course."item_property (tool,insert_user_id,ref,lastedit_type,lastedit_user_id,visibility) " .
								"VALUES ('forum','1','$lastforumid','ForumAdded','1','1')";
						$res = mysql_query($sql);
						//error_log($sql,0);
					}
					$sql_orig = "SELECT * FROM ".$prefix_course."bb_topics";
					$res_orig = mysql_query($sql_orig);
					while($row = mysql_fetch_array($res_orig)){
						$name = $row['prenom'].' '.$row['nom'];
						//check if user id is reusable
						if($row['topic_poster'] <= 1 )
						{
							if(isset($users_list[$name]))
							{
								$poster_id = $users_list[$name];
							}
							else
							{
								$poster_id = $row['topic_poster'];
							}
						}
						//convert time from varchar to datetime
						$time = $row['topic_time'];
						$name = mysql_real_escape_string($name);
						$sql = "INSERT INTO ".$prefix_course."forum_thread " .
								"(thread_id,forum_id,thread_poster_id," .
								"locked,thread_replies,thread_sticky,thread_title," .
								"thread_poster_name, thread_date, thread_last_post," .
								"thread_views) VALUES " .
								"('".$row['topic_id']."','".$row['forum_id']."','".$poster_id."'," .
								"0,'".$row['topic_replies']."',0,'".mysql_real_escape_string($row['topic_title'])."'," .
								"'$name','$time','".$row['topic_last_post_id']."'," .
								"'".$row['topic_views']."')";
						//error_log($sql,0);
						$res = mysql_query($sql);
						$lastthreadid = mysql_insert_id();
						
						//add item_property - forum threads were not put into item_properties before
						$sql = "INSERT INTO ".$prefix_course."item_property (tool,insert_user_id,ref,lastedit_type,lastedit_user_id,visibility) " .
								"VALUES ('forum_thread','1','$lastthreadid','ForumThreadAdded','1','1')";
						$res = mysql_query($sql);
						//error_log($sql,0);
					}
					$sql_orig = "SELECT * FROM ".$prefix_course."bb_posts bp, ".$prefix_course."bb_posts_text bpt WHERE bp.post_id = bpt.post_id";
					$res_orig = mysql_query($sql_orig);
					while($row = mysql_fetch_array($res_orig)){
						$name = $row['prenom'].' '.$row['nom'];
						//check if user id is reusable
						if($row['poster_id'] <= 0 )
						{
							if(isset($users_list[$name]))
							{
								$poster_id = $users_list[$name];
							}
							else
							{
								$poster_id = $row['poster_id'];
							}
						}
						//convert time from varchar to datetime
						$time = $row['post_time'];
						$name = mysql_real_escape_string($name);
						$sql = "INSERT INTO ".$prefix_course."forum_post " .
								"(post_id,forum_id,thread_id," .
								"poster_id,post_parent_id,visible, " .
								"post_title,poster_name, post_text, " .
								"post_date, post_notification) VALUES " .
								"('".$row['post_id']."','".$row['forum_id']."','".$row['topic_id']."'," .
								"'".$poster_id."','".$row['parent_id']."',1," .
								"'".mysql_real_escape_string($row['post_title'])."','$name', '".mysql_real_escape_string($row['post_text'])."'," .
								"'$time',0)";
						//error_log($sql,0);
						$res = mysql_query($sql);
						$lastpostid = mysql_insert_id();
						
						//add item_property - forum threads were not put into item_properties before
						$sql = "INSERT INTO ".$prefix_course."item_property(tool,insert_user_id,ref,lastedit_type,lastedit_user_id,visibility) " .
								"VALUES ('forum_post','1','$lastpostid','ForumPostAdded','1','1')";
						$res = mysql_query($sql);
						//error_log($sql,0);
					}
					unset($users_list);

					$sql_orig = "SELECT id, tutor_id FROM ".$prefix_course."group_info";
					$res_orig = mysql_query($sql_orig);
					$order = 1;
					while($row = mysql_fetch_array($res_orig)){
						$sql = "INSERT INTO ".$prefix_course."group_rel_tutor " .
								"(user_id,group_id) VALUES " .
								"('".$row['tutor_id']."','".$row['id']."')";
						$res = mysql_query($sql);
					}
				}
			}
		}
	}
	//load the old-scorm to new-scorm migration script
	//TODO: deal with the fact that this should only act on MAX_COURSE_TRANSFER courses
	if(!$only_test){
		include('update-db-scorm-1.6.x-1.8.0.inc.php');
	}
	if (defined('DOKEOS_INSTALL')) 
	{
		if ($singleDbForm)
		{
			if(empty($dbStatsForm)) $dbStatsForm = $dbNameForm;
			if(empty($dbScormForm)) $dbScormForm = $dbNameForm;
			if(empty($dbUserForm)) $dbUserForm = $dbNameForm;
		}
		//deal with migrate-db-1.6.x-1.8.0-post.sql
		//get the main queries list (m_q_list)
		$m_q_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-post.sql','main');
		if(count($m_q_list)>0)
		{
			//now use the $m_q_list
			/**
			 * We connect to the right DB first to make sure we can use the queries
			 * without a database name
			 */
			if(strlen($dbNameForm)>40){
				error_log('Database name '.$dbNameForm.' is too long, skipping',0);
			}elseif(!in_array($dbNameForm,$dblist)){
				error_log('Database '.$dbNameForm.' was not found, skipping',0);				
			}else{
				mysql_select_db($dbNameForm);
				foreach($m_q_list as $query){
					if($only_test){
						error_log("mysql_query($dbNameForm,$query)",0);
					}else{
						$res = mysql_query($query);
						if($log)
						{
							error_log("In $dbNameForm, executed: $query",0);
						}
					}
				}
			}
		}
		
		//get the stats queries list (s_q_list)
		$s_q_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-post.sql','stats');
		if(count($s_q_list)>0)
		{
			//now use the $s_q_list
			/**
			 * We connect to the right DB first to make sure we can use the queries
			 * without a database name
			 */
			if(strlen($dbStatsForm)>40){
				error_log('Database name '.$dbStatsForm.' is too long, skipping',0);
			}elseif(!in_array($dbNameForm,$dblist)){
				error_log('Database '.$dbNameForm.' was not found, skipping',0);				
			}else{
				mysql_select_db($dbStatsForm);
				foreach($s_q_list as $query){
					if($only_test){
						error_log("mysql_query($dbStatsForm,$query)",0);
					}else{
						$res = mysql_query($query);
						if($log)
						{
							error_log("In $dbStatsForm, executed: $query",0);
						}
					}
				}
			}
		}
		//get the user queries list (u_q_list)
		$u_q_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-post.sql','user');
		if(count($u_q_list)>0)
		{
			//now use the $u_q_list
			/**
			 * We connect to the right DB first to make sure we can use the queries
			 * without a database name
			 */
			if(strlen($dbUserForm)>40){
				error_log('Database name '.$dbUserForm.' is too long, skipping',0);
			}elseif(!in_array($dbUserForm,$dblist)){
				error_log('Database '.$dbUserForm.' was not found, skipping',0);				
			}else{
				mysql_select_db($dbUserForm);
				foreach($u_q_list as $query){
					if($only_test){
						error_log("mysql_query($dbUserForm,$query)",0);
					}else{
						$res = mysql_query($query);
						if($log)
						{
							error_log("In $dbUserForm, executed: $query",0);
						}
					}
				}
			}
		}
		//the SCORM database should need a drop in the post-migrate part. However, we will keep these tables a bit more, just in case...
	}
	//get the courses databases queries list (c_q_list)
	$c_q_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-post.sql','course');
	if(count($c_q_list)>0)
	{
		//get the courses list
		if(strlen($dbNameForm)>40){
			error_log('Database name '.$dbNameForm.' is too long, skipping',0);
		}elseif(!in_array($dbNameForm,$dblist)){
				error_log('Database '.$dbNameForm.' was not found, skipping',0);				
		}else{
			mysql_select_db($dbNameForm);
			$res = mysql_query("SELECT code,db_name,directory,course_language FROM course WHERE target_course_code IS NULL");
			if($res===false){die('Error while querying the courses list in update_db.inc.php');}
			if(mysql_num_rows($res)>0)
			{
				$i=0;
				//while( ($i < MAX_COURSE_TRANSFER) && ($row = mysql_fetch_array($res)))
				while($row = mysql_fetch_array($res))
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
					$prefix_course = $prefix;
					if($singleDbForm)
					{
						$prefix_course = $prefix.$row['db_name']."_";
					}else{
						mysql_select_db($row['db_name']);
					}

					foreach($c_q_list as $query)
					{
						if ($singleDbForm) //otherwise just use the main one
						{
							$query = preg_replace('/^(UPDATE|ALTER TABLE|CREATE TABLE|DROP TABLE|INSERT INTO|DELETE FROM)\s+(\w*)(.*)$/',"$1 $prefix$2$3",$query);
						}
						if($only_test)
						{
							error_log("mysql_query(".$row['db_name'].",$query)",0);
						}else{
							$res = mysql_query($query);
							if($log)
							{
								error_log("In ".$row['db_name'].", executed: $query",0);
							}
						}
					}
				}
			}
		}
	}
	
	// upgrade user categories sort
	$table_user_categories = $dbUserForm.'.user_course_category';
	
	
	$sql = 'SELECT * FROM '.$table_user_categories.' ORDER BY user_id, title';
	
	$rs = api_sql_query($sql, __FILE__, __LINE__);
	
	$sort = 0;
	$old_user = 0;
	while($cat = Database :: fetch_array($rs))
	{
        if($old_user != $cat['user_id'])
        {
                $old_user = $cat['user_id'];
                $sort = 0;
        }
        $sort++; 
        $sql = 'UPDATE '.$table_user_categories.' SET
	            sort = '.intval($sort).'
	            WHERE id='.intval($cat['id']);
        api_sql_query($sql, __FILE__, __LINE__);
	}
	        
	
}
else
{
	echo 'You are not allowed here !';
}
?>

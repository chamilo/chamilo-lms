<?php // $Id: update-db-1.8.5-1.8.6.inc.php 18697 2009-02-25 16:55:11Z cfasanando $
/* See license terms in /dokeos_license.txt */
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
require_once('../inc/lib/image.lib.php');
$old_file_version = '1.8.5';
$new_file_version = '1.8.6';

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
	if (!file_exists('../inc/conf/configuration.php'))
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
		echo					get_lang('DBServerDoesntWorkOrLoginPassIsWrong').'.<br /><br />' .
				'				'.get_lang('PleaseCheckTheseValues').' :<br /><br />
							    <b>'.get_lang('DBHost').'</b> : '.$dbHostForm.'<br />
								<b>'.get_lang('DBLogin').'</b> : '.$dbUsernameForm.'<br />
								<b>'.get_lang('DBPassword').'</b> : '.$dbPassForm.'<br /><br />
								'.get_lang('PleaseGoBackToStep').' '. (defined('DOKEOS_INSTALL') ? '3' : '1').'.
							    <p><input type="submit" name="step'. (defined('DOKEOS_INSTALL') ? '3' : '1').'" value="&lt; '.get_lang('Back').'"></p>
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
			$dbStatsForm = $dbNameForm;
			$dbScormForm = $dbNameForm;
			$dbUserForm = $dbNameForm;
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
		$m_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-pre.sql','main');
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
		
		// Filling the access_url_rel_user table with access_url_id by default = 1			
		$query = "SELECT user_id FROM $dbNameForm.user";
		
		$result_users = mysql_query($query);		
		while ($row= mysql_fetch_array($result_users,MYSQL_NUM)) {		
			$user_id = $row[0];	
			$sql="INSERT INTO $dbNameForm.access_url_rel_user SET user_id=$user_id, access_url_id=1";					
			$res = mysql_query($sql);
			//Updating user image
			$query = "SELECT picture_uri FROM $dbNameForm.user WHERE user_id=$user_id";
			$res = mysql_query($query);		
			$picture_uri = mysql_fetch_array($res,MYSQL_NUM);
			$file =  $picture_uri[0];
			$dir = api_get_path(SYS_CODE_PATH).'upload/users/';
			$image_repository = file_exists($dir.$file)? $dir.$file:$dir.$user_id.'/'.$file;
			
			if (!is_dir($dir.$user_id)) {
					$perm = octdec(!empty($perm)?$perm:'0777');							
					@mkdir($dir.$user_id, $perm);					
			}						
						
			if (file_exists($image_repository)) {								
				chmod($image_repository, 0777);
				chmod($dir.$user_id, 0777);
				if (is_dir($dir.$user_id)) {
					$picture_location = $dir.$user_id.'/'.$file;
					$big_picture_location = $dir.$user_id.'/big_'.$file;
					
					$temp = new image($image_repository);						
					
					$picture_infos=getimagesize($image_repository);

					$thumbwidth = 150;
					if (empty($thumbwidth) or $thumbwidth==0) {
						$thumbwidth=150;
					}

					$new_height = round(($thumbwidth/$picture_infos[0])*$picture_infos[1]);
		
					$temp->resize($thumbwidth,$new_height,0);

					$type=$picture_infos[2];
					
					// original picture
					$big_temp = new image($image_repository);
		
					    switch (!empty($type)) {
						    case 2 : $temp->send_image('JPG',$picture_location);
						    		 $big_temp->send_image('JPG',$big_picture_location);
						    		 break;
						    case 3 : $temp->send_image('PNG',$picture_location);
						    		 $big_temp->send_image('JPG',$big_picture_location);
						    		 break;
						    case 1 : $temp->send_image('GIF',$picture_location);
						    		 $big_temp->send_image('JPG',$big_picture_location);
						    		 break;
					    }	
					if ($image_repository == $dir.$file) {				
					   @unlink($image_repository);	
					}
				} 				
			} 						
		}
		// Filling the access_url_rel_session table with access_url_id by default = 1
		$query = "SELECT id FROM $dbNameForm.session";
		$result = mysql_query($query);
		while ($row= mysql_fetch_array($result,MYSQL_NUM)) {			
			$sql="INSERT INTO $dbNameForm.access_url_rel_session SET session_id=".$row[0].", access_url_id=1";			
			$res = mysql_query($sql);
		} 		
		
        // Check if course_module exists, as it was not installed in Dokeos 1.8.5 because of a broken query, and insert it if necessary
        $query = "SELECT * FROM $dbNameForm.course_module";
        $result = mysql_query($query);
        if ($result === false) {
        	//the course_module table doesn't exist, create it
            $sql = "CREATE TABLE $dbNameForm.course_module (
                      id int unsigned NOT NULL auto_increment,
                      name varchar(100) NOT NULL,
                      link varchar(255) NOT NULL,
                      image varchar(100) default NULL,
                      `row` int unsigned NOT NULL default '0',
                      `column` int unsigned NOT NULL default '0',
                      position varchar(20) NOT NULL default 'basic',
                      PRIMARY KEY  (id)
                    )
                    ";
            $result = mysql_query($sql);
            if ($result !== false) {
            	$sql = "INSERT INTO $dbNameForm.course_module (name, link, image, `row`,`column`, position) VALUES
                    ('calendar_event','calendar/agenda.php','agenda.gif',1,1,'basic'),
                    ('link','link/link.php','links.gif',4,1,'basic'),
                    ('document','document/document.php','documents.gif',3,1,'basic'),
                    ('student_publication','work/work.php','works.gif',3,2,'basic'),
                    ('announcement','announcements/announcements.php','valves.gif',2,1,'basic'),
                    ('user','user/user.php','members.gif',2,3,'basic'),
                    ('forum','forum/index.php','forum.gif',1,2,'basic'),
                    ('quiz','exercice/exercice.php','quiz.gif',2,2,'basic'),
                    ('group','group/group.php','group.gif',3,3,'basic'),
                    ('course_description','course_description/','info.gif',1,3,'basic'),
                    ('chat','chat/chat.php','chat.gif',0,0,'external'),
                    ('dropbox','dropbox/index.php','dropbox.gif',4,2,'basic'),
                    ('tracking','tracking/courseLog.php','statistics.gif',1,3,'courseadmin'),
                    ('homepage_link','link/link.php?action=addlink','npage.gif',1,1,'courseadmin'),
                    ('course_setting','course_info/infocours.php','reference.gif',1,1,'courseadmin'),
                    ('External','','external.gif',0,0,'external'),
                    ('AddedLearnpath','','scormbuilder.gif',0,0,'external'),
                    ('conference','conference/index.php?type=conference','conf.gif',0,0,'external'),
                    ('conference','conference/index.php?type=classroom','conf.gif',0,0,'external'),
                    ('learnpath','newscorm/lp_controller.php','scorm.gif',5,1,'basic'),
                    ('blog','blog/blog.php','blog.gif',1,2,'basic'),
                    ('blog_management','blog/blog_admin.php','blog_admin.gif',1,2,'courseadmin'),
                    ('course_maintenance','course_info/maintenance.php','backup.gif',2,3,'courseadmin'),
                    ('survey','survey/survey_list.php','survey.gif',2,1,'basic'),
                    ('wiki','wiki/index.php','wiki.gif',2,3,'basic'),
                    ('gradebook','gradebook/index.php','gradebook.gif',2,2,'basic'),
                    ('glossary','glossary/index.php','glossary.gif',2,1,'basic'),
                    ('notebook','notebook/index.php','notebook.gif',2,1,'basic')";
                $res = mysql_query($sql);
            }
        }

		
		//get the stats queries list (s_q_list)
		$s_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-pre.sql','stats');
	
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
		$u_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-pre.sql','user');
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
		$prefix =  get_config_param ('table_prefix');			
	}
	
	//get the courses databases queries list (c_q_list)
	$c_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-pre.sql','course');

	if(count($c_q_list)>0)
	{
		//get the courses list
		if(strlen($dbNameForm)>40)
		{
			error_log('Database name '.$dbNameForm.' is too long, skipping',0);
		}
		elseif(!in_array($dbNameForm,$dblist))
		{
			error_log('Database '.$dbNameForm.' was not found, skipping',0);				
		}
		else
		{
			mysql_select_db($dbNameForm);
			$res = mysql_query("SELECT code,db_name,directory,course_language FROM course WHERE target_course_code IS NULL ORDER BY code");

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
						}
						else
						{
							$res = mysql_query($query);						
							if($log)
							{
								error_log("In ".$row_course['db_name'].", executed: $query",0);
							}
						}
					}
                    
                    $t_d = $row_course['db_name'].".document";
                    $t_ip = $row_course['db_name'].".item_property";
                    
                    if($singleDbForm)
                    {
                        $t_d = "$prefix{$row_course['db_name']}_document";
                        $t_ip = "$prefix{$row_course['db_name']}_item_property";
                    }
                    // shared documents folder   
                    $query = "INSERT INTO $t_d (path,title,filetype,size) VALUES ('/shared_folder','".get_lang('SharedDocumentsDirectory')."','folder','0')";
                    $myres = mysql_query($query);
                    if ($myres !== false) {
                    	$doc_id = mysql_insert_id();
                        $query = "INSERT INTO $t_ip (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('document',1,NOW(),NOW(),$doc_id,'FolderAdded',1,0,NULL,1)";
                        $myres = mysql_query($query);
                    }
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
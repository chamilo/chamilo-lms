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
		echo					get_lang('DBServerDoesntWorkOrLoginPassIsWrong').'.<br /><br />' .
				'				'.get_lang('PleaseCheckTheseValues').' :<br /><br />
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
		$m_q_list = get_sql_file_contents('migrate-db-1.8.3-1.8.4-pre.sql','main');
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
		$s_q_list = get_sql_file_contents('migrate-db-1.8.3-1.8.4-pre.sql','stats');
	
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
		$u_q_list = get_sql_file_contents('migrate-db-1.8.3-1.8.4-pre.sql','user');
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
	$c_q_list = get_sql_file_contents('migrate-db-1.8.3-1.8.4-pre.sql','course');
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
					//ensure each learnpath is present in the item_property table
					$prefix_course = '';
					if($singleDbForm)
					{
						$prefix_course = $prefix.$row_course['db_name']."_";
					}

					$sql_ip = "SELECT * FROM ".$prefix_course."item_property WHERE tool='learnpath'";
					$res_ip = mysql_query($sql_ip);
					$paths = array();
					while($row_ip = mysql_fetch_array($res_ip))
					{
						$paths[] = $row_ip['ref'];
					}
					$sql_lp = "SELECT * FROM ".$prefix_course."lp";
					$res_lp = mysql_query($sql_lp);
	    			$tbl_tool = $prefix_course."tool";
					while($row_lp = mysql_fetch_array($res_lp))
					{
						$time = date("Y-m-d H:i:s", time());
						$vis = 'v';
						$input = stripslashes($row_lp['name']);
						$input = str_replace("'", "''", $input);
						$input = str_replace('"', "''", $input);							
						$mylink = 'newscorm/lp_controller.php?action=view&lp_id='.$row_lp['id'];
						$sql2="SELECT * FROM $tbl_tool where (name='$input' and image='scormbuilder.gif' and link LIKE '$mylink%')";

						if(in_array($row_lp['id'],$paths))
						{
							//the path is already in item_property, check the visibility is the
							//same as the homepage tool's
							$res2 = api_sql_query($sql2,__FILE__,__LINE__);
							if(mysql_num_rows($res2)>0){
								$row2 = mysql_fetch_array($res2);
								$vis = $row2['visibility'];
							}
							$visi = array('v'=>1,'i'=>0);
							if($visi[$vis] != $row_ip['visibility'])
							{
								$sql_upd = "UPDATE ".$prefix_course."item_propery SET visibility=".$visi[$vis]." WHERE tool='learnpath' AND ref='".$row_lp['id']."'";
								$res_upd = mysql_query($sql_upd);
							}
						}
						else
						{
							//the path is not in item_property, insert it
							$res2 = api_sql_query($sql2,__FILE__,__LINE__);
							if(mysql_num_rows($res2)>0){
								$row2 = mysql_fetch_array($res2);
								$vis = $row2['visibility'];
							}
							$visi = array('v'=>1,'i'=>0);

							$sql_ins = "INSERT INTO ".$prefix_course."item_property " .
									"(tool,ref,insert_date,last_edit_date,insert_user_id,lastedit_type,lastedit_user_id,visibility)" .
									"VALUES" .
									"('learnpath',".$row_lp['id'].",'$time','$time',1,'learnpathAdded',1,".$visi[$vis].")";
							$res_ins = mysql_query($sql_ins);
						}
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

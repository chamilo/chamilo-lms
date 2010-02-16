<?php // $Id: $
/* See license terms in /license.txt */
/**
==============================================================================
* Update the Dokeos database from an older version
* Notice : This script has to be included by index.php or update_courses.php
*
* @package chamilo.install
* @todo
* - conditional changing of tables. Currently we execute for example
* ALTER TABLE `$dbNameForm`.`cours` instructions without checking wether this is necessary.
* - reorganise code into functions
* @todo use database library
==============================================================================
*/

//load helper functions
require_once 'install_upgrade.lib.php';
require_once '../inc/lib/image.lib.php';
$old_file_version = '1.8.6';
$new_file_version = '1.8.6.1';

//remove memory and time limits as much as possible as this might be a long process...
if (function_exists('ini_set')) {
	ini_set('memory_limit', -1);
	ini_set('max_execution_time', 0);
} else {
	error_log('Update-db script: could not change memory and time limits', 0);
}

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

//check if we come from index.php or update_courses.php - otherwise display error msg
if (defined('SYSTEM_INSTALLATION') || defined('DOKEOS_COURSE_UPDATE')) {

	//check if the current Dokeos install is elligible for update
	if (!file_exists('../inc/conf/configuration.php')) {
		echo '<strong>'.get_lang('Error').' !</strong> Dokeos '.implode('|', $updateFromVersion).' '.get_lang('HasNotBeenFound').'.<br /><br />
								'.get_lang('PleasGoBackToStep1').'.
							    <p><button type="submit" class="back" name="step1" value="&lt; '.get_lang('Back').'">'.get_lang('Back').'</button></p>
							    </td></tr></table></form></body></html>';
		exit ();
	}

	//get_config_param() comes from install_functions.inc.php and
	//actually gets the param from
	$_configuration['db_glue'] = get_config_param('dbGlu');

	if ($singleDbForm) {
		$_configuration['table_prefix'] = get_config_param('courseTablePrefix');
		$_configuration['main_database'] = get_config_param('mainDbName');
		$_configuration['db_prefix'] = get_config_param('dbNamePrefix');
	}

	$dbScormForm = preg_replace('/[^a-zA-Z0-9_\-]/', '', $dbScormForm);

	if (!empty($dbPrefixForm) && strpos($dbScormForm, $dbPrefixForm) !== 0) {
		$dbScormForm = $dbPrefixForm.$dbScormForm;
	}

	if (empty($dbScormForm) || $dbScormForm == 'mysql' || $dbScormForm == $dbPrefixForm) {
		$dbScormForm = $dbPrefixForm.'scorm';
	}

	$res = @Database::connect(array('server' => $dbHostForm, 'username' => $dbUsernameForm, 'password' => $dbPassForm));

	//if error on connection to the database, show error and exit
	if ($res === false) {
		//$no = Database::errno();
		//$msg = Database::error();

		//echo '<hr />['.$no.'] - '.$msg.'<hr />';
		echo					get_lang('DBServerDoesntWorkOrLoginPassIsWrong').'.<br /><br />' .
				'				'.get_lang('PleaseCheckTheseValues').' :<br /><br />
							    <strong>'.get_lang('DBHost').'</strong> : '.$dbHostForm.'<br />
								<strong>'.get_lang('DBLogin').'</strong> : '.$dbUsernameForm.'<br />
								<strong>'.get_lang('DBPassword').'</strong> : '.$dbPassForm.'<br /><br />
								'.get_lang('PleaseGoBackToStep').' '. (defined('SYSTEM_INSTALLATION') ? '3' : '1').'.
							    <p><button type="submit" class="back" name="step'. (defined('SYSTEM_INSTALLATION') ? '3' : '1').'" value="&lt; '.get_lang('Back').'">'.get_lang('Back').'</button></p>
							    </td></tr></table></form></body></html>';
		exit ();
	}

	@Database::query("set session sql_mode='';"); // Disabling special SQL modes (MySQL 5)

	$dblist = Database::get_databases();

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
	if (defined('SYSTEM_INSTALLATION')) {

		if ($singleDbForm) {
			$dbStatsForm = $dbNameForm;
			$dbScormForm = $dbNameForm;
			$dbUserForm = $dbNameForm;
		}
		/**
		 * Update the databases "pre" migration
		 */
		include '../lang/english/create_course.inc.php';

		if ($languageForm != 'english') {
			//languageForm has been escaped in index.php
			include '../lang/'.$languageForm.'/create_course.inc.php';
		}

		//get the main queries list (m_q_list)
		$m_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-pre.sql', 'main');
		if (count($m_q_list) > 0) {
			//now use the $m_q_list
			/**
			 * We connect to the right DB first to make sure we can use the queries
			 * without a database name
			 */
			if (strlen($dbNameForm) > 40) {
				error_log('Database name '.$dbNameForm.' is too long, skipping', 0);
			} elseif (!in_array($dbNameForm,$dblist)) {
				error_log('Database '.$dbNameForm.' was not found, skipping', 0);
			} else {
				Database::select_db($dbNameForm);
				foreach($m_q_list as $query) {
					if ($only_test){
						error_log("Database::query($dbNameForm,$query)", 0);
					} else {
						$res = Database::query($query);
						if ($log) {
							error_log("In $dbNameForm, executed: $query", 0);
						}
					}
				}
			}
		}


		//get the stats queries list (s_q_list)
		$s_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-pre.sql', 'stats');

		if (count($s_q_list) > 0) {
			//now use the $s_q_list
			/**
			 * We connect to the right DB first to make sure we can use the queries
			 * without a database name
			 */
			if (strlen($dbStatsForm) > 40) {
				error_log('Database name '.$dbStatsForm.' is too long, skipping', 0);
			} elseif (!in_array($dbStatsForm,$dblist)) {
				error_log('Database '.$dbStatsForm.' was not found, skipping', 0);
			} else {
				Database::select_db($dbStatsForm);
				foreach ($s_q_list as $query) {
					if ($only_test) {
						error_log("Database::query($dbStatsForm,$query)", 0);
					} else {
						$res = Database::query($query);
						if ($log) {
							error_log("In $dbStatsForm, executed: $query", 0);
						}
					}
				}
			}
		}
		//get the user queries list (u_q_list)
		$u_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-pre.sql', 'user');
		if (count($u_q_list) > 0) {
			//now use the $u_q_list
			/**
			 * We connect to the right DB first to make sure we can use the queries
			 * without a database name
			 */
			if (strlen($dbUserForm) > 40) {
				error_log('Database name '.$dbUserForm.' is too long, skipping', 0);
			} elseif (!in_array($dbUserForm, $dblist)) {
				error_log('Database '.$dbUserForm.' was not found, skipping', 0);
			} else {
				Database::select_db($dbUserForm);
				foreach ($u_q_list as $query) {
					if ($only_test) {
						error_log("Database::query($dbUserForm,$query)", 0);
						error_log("In $dbUserForm, executed: $query", 0);
					} else {
						$res = Database::query($query);
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
	if ($singleDbForm) {
		$prefix =  get_config_param ('table_prefix');
	}

	//get the courses databases queries list (c_q_list)
	$c_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-pre.sql', 'course');

	if (count($c_q_list) > 0) {
		//get the courses list
		if (strlen($dbNameForm) > 40) {
			error_log('Database name '.$dbNameForm.' is too long, skipping', 0);
		} elseif (!in_array($dbNameForm, $dblist)) {
			error_log('Database '.$dbNameForm.' was not found, skipping', 0);
		} else {
			Database::select_db($dbNameForm);
			$res = Database::query("SELECT code,db_name,directory,course_language FROM course WHERE target_course_code IS NULL ORDER BY code");

			if ($res === false) { die('Error while querying the courses list in update_db-1.8.6-1.8.6.1.inc.php'); }

			if (Database::num_rows($res) > 0) {
				$i = 0;
                $list = array();
				//while( ($i < MAX_COURSE_TRANSFER) && ($row = Database::fetch_array($res)))
				while ($row = Database::fetch_array($res)) {
					$list[] = $row;
					$i++;
				}
				foreach ($list as $row_course) {
					//now use the $c_q_list
					/**
					 * We connect to the right DB first to make sure we can use the queries
					 * without a database name
					 */
					if (!$singleDbForm) { //otherwise just use the main one
						Database::select_db($row_course['db_name']);
					}

					foreach ($c_q_list as $query) {
						if ($singleDbForm) { //otherwise just use the main one
							$query = preg_replace('/^(UPDATE|ALTER TABLE|CREATE TABLE|DROP TABLE|INSERT INTO|DELETE FROM)\s+(\w*)(.*)$/', "$1 $prefix{$row_course['db_name']}_$2$3", $query);
						}

						if ($only_test) {
							error_log("Database::query(".$row_course['db_name'].",$query)", 0);
						} else {
							$res = Database::query($query);
							if ($log) {
								error_log("In ".$row_course['db_name'].", executed: $query", 0);
							}
						}
					}

					$t_wiki = $row_course['db_name'].".wiki";
                    $t_wiki_conf = $row_course['db_name'].".wiki_conf";

                    if ($singleDbForm) {
                        $t_wiki = "$prefix{$row_course['db_name']}_wiki";
                        $t_wiki_conf = "$prefix{$row_course['db_name']}_wiki_conf";
                    }

                    //update correct page_id to wiki table, actually only store 0
                    $query = "SELECT id, reflink FROM $t_wiki";
                    $res_page = Database::query($query);
                    $wiki_id = $reflink = array();

					if (Database::num_rows($res_page) > 0 ) {
	                    while ($row_page = Database::fetch_row($res_page)) {
	                    	$wiki_id[] = $row_page[0];
	                    	$reflink[] = $row_page[1];
	                    }
					}

                    $reflink_unique = array_unique($reflink);
                    $reflink_flip = array_flip($reflink_unique);

                    if (is_array($wiki_id)) {
	                    foreach ($wiki_id as $key => $wiki_page) {
	                    	$pag_id = $reflink_flip[$reflink[$key]];
	                    	$sql= "UPDATE $t_wiki SET page_id='".($pag_id + 1)."' WHERE id = '$wiki_page'";
	                    	$res_update = Database::query($sql);
	                    }
                    }

                    //insert page_id into wiki_conf table, actually this table is empty
				   	$query = "SELECT DISTINCT page_id FROM $t_wiki ORDER BY page_id";
				   	$myres_wiki = Database::query($query);

				   	if (Database::num_rows($myres_wiki) > 0) {
					   	while ($row_wiki = Database::fetch_row($myres_wiki)) {
					   		$page_id = $row_wiki[0];
					   		$query = "INSERT INTO ".$t_wiki_conf." (page_id, task, feedback1, feedback2, feedback3, fprogress1, fprogress2, fprogress3) VALUES ('".$page_id."','','','','','','','')";
	                   		$myres_wiki_conf = Database::query($query);
					   	}
				   	}

   				}
			}
		}
	}

} else {

	echo 'You are not allowed here !';

}

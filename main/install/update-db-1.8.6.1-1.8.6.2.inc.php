<?php
/* For licensing terms, see /license.txt */
/**
==============================================================================
* Chamilo LMS
*
* Update the Chamilo database from an older Dokeos version
* Notice : This script has to be included by index.php
* or update_courses.php (deprecated).
*
* @package chamilo.install
* @todo
* - conditional changing of tables. Currently we execute for example
* ALTER TABLE `$dbNameForm`.`cours`
* instructions without checking wether this is necessary.
* - reorganise code into functions
* @todo use database library
==============================================================================
*/

// Load helper functions
require_once '../inc/lib/image.lib.php';

$old_file_version = '1.8.6.1';
$new_file_version = '1.8.6.2';

// Remove memory and time limits as much as possible as this might be a long process...
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

// Check if we come from index.php or update_courses.php - otherwise display error msg
if (defined('SYSTEM_INSTALLATION') || defined('DOKEOS_COURSE_UPDATE')) {

	// Check if the current Dokeos install is elligible for update
	if (!file_exists('../inc/conf/configuration.php')) {
		echo '<strong>'.get_lang('Error').' !</strong> Dokeos '.implode('|', $updateFromVersion).' '.get_lang('HasNotBeenFound').'.<br /><br />
								'.get_lang('PleasGoBackToStep1').'.
							    <p><button type="submit" class="back" name="step1" value="&lt; '.get_lang('Back').'">'.get_lang('Back').'</button></p>
							    </td></tr></table></form></body></html>';
		exit ();
	}

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

	database_server_connect();

	$dblist = Database::get_databases();

	/*
	-----------------------------------------------------------
		Normal upgrade procedure:
		start by updating main, statistic, user databases
	-----------------------------------------------------------
	*/
	// If this script has been included by index.php, not update_courses.php, so
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
			// languageForm has been escaped in index.php
			include '../lang/'.$languageForm.'/create_course.inc.php';
		}

		// Get the main queries list (m_q_list)
		$m_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-pre.sql', 'main');
		if (count($m_q_list) > 0) {
			// Now use the $m_q_list
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
				foreach ($m_q_list as $query) {
					if ($only_test) {
						error_log("Database::query($dbNameForm,$query)", 0);
					} else {
						$res = Database::query($query);
						if ($log) {
							error_log("In $dbNameForm, executed: $query", 0);
						}
					}
				}


				// There might now be multiple course coaches. This implies
				// moving the previous course coach elements from the
				// session_rel_course table to the session_rel_course_rel_user
				// table with status 2
				// Select all the current course coaches in sessions

				$sql = "SELECT id_session, course_code, id_coach
				        FROM session_rel_course
				        ORDER BY id_session, course_code";

				$res = Database::query($sql);

				if ($res === false) {
				    error_log('Could not query session course coaches table: '.Database::error());
				} else {
					// For each coach found, add him as a course coach in the
					// session_rel_course_rel_user table
					while ($row = Database::fetch_array($res)) {

						// Check whether coach is a student
						$sql = "SELECT 1 FROM session_rel_course_rel_user
									 WHERE id_session='{$row[id_session]}' AND course_code='{$row[course_code]}' AND id_user='{$row[id_coach]}'";
						$rs  =	Database::query($sql);

						if (Database::num_rows($rs) > 0) {
							$sql_upd = "UPDATE session_rel_course_rel_user SET status=2
										WHERE id_session='{$row[id_session]}' AND course_code='{$row[course_code]}' AND id_user='{$row[id_coach]}'";
						} else {
							$sql_ins = "INSERT INTO session_rel_course_rel_user(id_session, course_code, id_user, status)
						  				VALUES ('{$row[id_session]}','{$row[course_code]}','{$row[id_coach]}',2)";
						}

						$rs_coachs = Database::query($sql_ins);

						if ($rs_coachs === false) {
							error_log('Could not move course coach to new table: '.Database::error());
						}

					}
				}

				// Remove duplicated rows for 'show_tutor_data' AND 'show_teacher_data' into settings_current table

				$sql = "SELECT id FROM settings_current WHERE variable='show_tutor_data' ORDER BY id";
				$rs_chk_id1 = Database::query($sql);

				if ($rs_chk_id1 === false) {
				    error_log('Could not query settings_current ids table: '.Database::error());
				} else {
					$i = 1;
					while ($row_id1 = Database::fetch_array($rs_chk_id1)) {
						$id = $row_id1['id'];
						if ($i > 1) {
							$sql_del = "DELETE FROM settings_current WHERE id = '$id'";
							Database::query($sql_del);
						}
						$i++;
					}
				}

				$sql = "SELECT id FROM settings_current WHERE variable='show_teacher_data' ORDER BY id";
				$rs_chk_id2 = Database::query($sql);

				if ($rs_chk_id2 === false) {
				    error_log('Could not query settings_current ids table: '.Database::error());
				} else {
					$i = 1;
					while ($row_id2 = Database::fetch_array($rs_chk_id2)) {
						$id = $row_id2['id'];
						if ($i > 1) {
							$sql_del = "DELETE FROM settings_current WHERE id = '$id'";
							Database::query($sql_del);
						}
						$i++;
					}
				}

			}
		}
		// Now clean the deprecated id_coach field from the session_rel_course table
        $m_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-post.sql', 'main');
        if (count($m_q_list) > 0) {
            // Now use the $m_q_list
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
                foreach ($m_q_list as $query) {
                    if ($only_test) {
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

		// Get the stats queries list (s_q_list)
		$s_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-pre.sql', 'stats');

		if (count($s_q_list) > 0) {
			// Now use the $s_q_list
			/**
			 * We connect to the right DB first to make sure we can use the queries
			 * without a database name
			 */
			if (strlen($dbStatsForm) > 40) {
				error_log('Database name '.$dbStatsForm.' is too long, skipping', 0);
			} elseif (!in_array($dbStatsForm, $dblist)) {
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
		// Get the user queries list (u_q_list)
		$u_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-pre.sql', 'user');
		if (count($u_q_list) > 0) {
			// Now use the $u_q_list
			/**
			 * We connect to the right DB first to make sure we can use the queries
			 * without a database name
			 */
			if (strlen($dbUserForm) > 40) {
				error_log('Database name '.$dbUserForm.' is too long, skipping', 0);
			} elseif (!in_array($dbUserForm,$dblist)) {
				error_log('Database '.$dbUserForm.' was not found, skipping', 0);
			} else {
				Database::select_db($dbUserForm);
				foreach ($u_q_list as $query) {
					if ($only_test){
						error_log("Database::query($dbUserForm,$query)", 0);
						error_log("In $dbUserForm, executed: $query", 0);
					} else {
						$res = Database::query($query);
					}
				}
			}
		}
		// The SCORM database doesn't need a change in the pre-migrate part - ignore
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

	// Get the courses databases queries list (c_q_list)
	$c_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-pre.sql', 'course');

	if (count($c_q_list) > 0) {
		// Get the courses list
		if (strlen($dbNameForm) > 40) {
			error_log('Database name '.$dbNameForm.' is too long, skipping', 0);
		} elseif (!in_array($dbNameForm, $dblist)) {
			error_log('Database '.$dbNameForm.' was not found, skipping', 0);
		} else {
			Database::select_db($dbNameForm);
			$res = Database::query("SELECT code,db_name,directory,course_language FROM course WHERE target_course_code IS NULL ORDER BY code");

			if ($res === false) { die('Error while querying the courses list in update_db-1.8.6.1-1.8.6.2.inc.php'); }

			if (Database::num_rows($res) > 0) {
				$i = 0;
                $list = array();
				//while(($i < MAX_COURSE_TRANSFER) && ($row = Database::fetch_array($res)))
				while ($row = Database::fetch_array($res)) {
					$list[] = $row;
					$i++;
				}
				foreach ($list as $row_course) {
					// Now use the $c_q_list
					/**
					 * We connect to the right DB first to make sure we can use the queries
					 * without a database name
					 */
					if (!$singleDbForm) { //otherwise just use the main one
						Database::select_db($row_course['db_name']);
					}

					foreach ($c_q_list as $query) {
						if ($singleDbForm) {
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

					// Fill description type into course_description table

					$t_course_description = $row_course['db_name'].".course_description";

                    if ($singleDbForm) {
                        $t_course_description = "$prefix{$row_course['db_name']}_course_description";
                    }

					// Get all ids and update description_type field with them from course_description table
					$sql_sel = "SELECT id FROM $t_course_description";
					$rs_sel = Database::query($sql_sel);

					if ($rs_sel === false) {
				    	error_log('Could not query course_description ids table: '.Database::error());
					} else {
						if (Database::num_rows($rs_sel) > 0) {
							while ($row_ids = Database::fetch_array($rs_sel)) {
								$description_id = $row_ids['id'];
								$sql_upd = "UPDATE $t_course_description SET description_type='$description_id' WHERE id='$description_id'";
								Database::query($sql_upd);
							}
						}
					}

   				}
			}
		}
	}

} else {

	echo 'You are not allowed here !';

}

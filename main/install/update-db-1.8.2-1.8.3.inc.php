<?php
/* For licensing terms, see /license.txt */

/**
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
 */

// Check if we come from index.php or update_courses.php - otherwise display error msg
if (defined('SYSTEM_INSTALLATION')) {

	// Check if the current Dokeos install is eligible for update
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

	/*	Normal upgrade procedure: start by updating main, statistic, user databases */

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

		// get the main queries list (m_q_list)
		$m_q_list = get_sql_file_contents('migrate-db-1.8.2-1.8.3-pre.sql', 'main');
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
		$s_q_list = get_sql_file_contents('migrate-db-1.8.2-1.8.3-pre.sql', 'stats');

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
		$u_q_list = get_sql_file_contents('migrate-db-1.8.2-1.8.3-pre.sql', 'user');
		if (count($u_q_list) > 0) {
			// Now use the $u_q_list
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
						error_log("Database::query($dbUserForm, $query)", 0);
						error_log("In $dbUserForm, executed: $query", 0);
					} else {
						$res = Database::query($query);
					}
				}
			}
		}
		// The SCORM database doesn't need a change in the pre-migrate part - ignore
	}

	$prefix = '';
	if ($singleDbForm) {
		$prefix = $_configuration['table_prefix'];
	}
	// Get the courses databases queries list (c_q_list)
	$c_q_list = get_sql_file_contents('migrate-db-1.8.2-1.8.3-pre.sql', 'course');
	if (count($c_q_list) > 0) {
		// Get the courses list
		if (strlen($dbNameForm) > 40) {
			error_log('Database name '.$dbNameForm.' is too long, skipping', 0);
		} elseif (!in_array($dbNameForm, $dblist)) {
			error_log('Database '.$dbNameForm.' was not found, skipping', 0);
		} else {
			Database::select_db($dbNameForm);
			$res = Database::query("SELECT code,db_name,directory,course_language FROM course WHERE target_course_code IS NULL");
			if ($res === false) { die('Error while querying the courses list in update_db-1.8.2-1.8.3.inc.php'); }
			if (Database::num_rows($res) > 0) {
				$i = 0;
                $list = array();
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
				}
			}
		}
	}

} else {

	echo 'You are not allowed here !';

}

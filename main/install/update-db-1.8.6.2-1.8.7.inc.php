<?php
/* For licensing terms, see /license.txt */

/**
 * Chamilo LMS
 *
 * Update the Chamilo database from an older Chamilo version
 * Notice : This script has to be included by index.php
 * or update_courses.php (deprecated).
 *
 * @package chamilo.install
 * @todo
 * - conditional changing of tables. Currently we execute for example
 * ALTER TABLE $dbNameForm.cours
 * instructions without checking wether this is necessary.
 * - reorganise code into functions
 * @todo use database library
 */
Log::notice('Entering file');

$old_file_version = '1.8.6.2';
$new_file_version = '1.8.7';

// Check if we come from index.php or update_courses.php - otherwise display error msg
if (defined('SYSTEM_INSTALLATION')) {

    // Check if the current Chamilo install is eligible for update
    if (!file_exists('../inc/conf/configuration.php')) {
        echo '<strong>'.get_lang('Error').' !</strong> Chamilo '.implode('|', $updateFromVersion).' '.get_lang('HasNotBeenFound').'.<br /><br />
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

    /*   Normal upgrade procedure: start by updating main, statistic, user databases */

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
                 Log::error('Database name '.$dbNameForm.' is too long, skipping');
            } elseif (!in_array($dbNameForm, $dblist)) {
                 Log::error('Database '.$dbNameForm.' was not found, skipping');
            } else {
                Database::select_db($dbNameForm);
                foreach ($m_q_list as $query){
                    if ($only_test) {
                         Log::notice("Database::query($dbNameForm,$query)");
                    } else {
                        $res = Database::query($query);
                        if ($log) {
                             Log::notice("In $dbNameForm, executed: $query");
                        }
                        if ($res === false) {
                        	 Log::error('Error in '.$query.': '.Database::error());
                        }
                    }
                }
                $tables = Database::get_tables($dbNameForm);
                foreach ($tables as $table) {
            	    $query = "ALTER TABLE `".$table."` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;";
                    $res = Database::query($query);
                    if ($res === false) {
                          Log::error('Error in '.$query.': '.Database::error());
                    }
                }
            	$query = "ALTER DATABASE `".$dbNameForm."` DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci;";
                $res = Database::query($query);
                if ($res === false) {
                      Log::error('Error in '.$query.': '.Database::error());
                }
            }
        }

        if (DATE_TIME_INSTALLED) {
            // Converting dates and times to UTC using the default timezone of PHP
            // Converting gradebook dates and times
            $timezone = date_default_timezone_get();
            // Calculating the offset
            $dateTimeZoneCurrent = new DateTimeZone($timezone);
            $dateTimeUTC = new DateTime("now", new DateTimeZone('UTC'));
            $timeOffsetSeconds = $dateTimeZoneCurrent->getOffset($dateTimeUTC);
            $timeOffsetHours = $timeOffsetSeconds / 3600;
            $timeOffsetString = "";
    
            if($timeOffsetHours < 0) {
                    $timeOffsetString .= "-";
                    $timeOffsetHours = abs($timeOffsetHours);
            } else {
                    $timeOffsetString .= "+";
            }
    
            if($timeOffsetHours < 10) {
                    $timeOffsetString .= "0";
            }
    
            $timeOffsetString .= "$timeOffsetHours";
            $timeOffsetString .= ":00";
    
            // Executing the queries to convert everything
            $queries[] = "UPDATE gradebook_certificate 	SET created_at = CONVERT_TZ(created_at, '".$timeOffsetString."', '+00:00');";
            $queries[] = "UPDATE gradebook_evaluation 	SET created_at = CONVERT_TZ(created_at, '".$timeOffsetString."', '+00:00');";
            $queries[] = "UPDATE gradebook_link 		SET created_at = CONVERT_TZ(created_at, '".$timeOffsetString."', '+00:00');";
            $queries[] = "UPDATE gradebook_linkeval_log SET created_at = CONVERT_TZ(created_at, '".$timeOffsetString."', '+00:00');";
            $queries[] = "UPDATE gradebook_result 		SET created_at = CONVERT_TZ(created_at, '".$timeOffsetString."', '+00:00');";
            $queries[] = "UPDATE gradebook_result_log 	SET created_at = CONVERT_TZ(created_at, '".$timeOffsetString."', '+00:00');";
    
            foreach ($queries as $query) {
                Database::query($query);
            }
        }
        // Moving user followed by a human resource manager from hr_dept_id field to user_rel_user table
        $query = "SELECT user_id, hr_dept_id  FROM $dbNameForm.user";
        $result = Database::query($query);
        if (Database::num_rows($result) > 0) {
            require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $user_id = $row['user_id'];
                $hr_dept_id = $row['hr_dept_id'];
                // moving data to user_rel_user table
                if (!empty($hr_dept_id)) {
                    $sql = " SELECT id FROM $dbNameForm.user_rel_user WHERE user_id = $user_id AND friend_user_id = $hr_dept_id AND relation_type = ".USER_RELATION_TYPE_RRHH." ";
                    $rs  = Database::query($sql);
                    if (Database::num_rows($rs) == 0) {
                        $ins = "INSERT INTO $dbNameForm.user_rel_user SET user_id = $user_id, friend_user_id = $hr_dept_id, relation_type = ".USER_RELATION_TYPE_RRHH." ";
                        Database::query($ins);
                    }
                }
            }
            // cleaning hr_dept_id field inside user table
            $upd = "UPDATE $dbNameForm.user SET hr_dept_id = 0";
            Database::query($upd);
        }

        // Updating score display for each gradebook category

        // first we check if there already is migrated data to categoy_id field
        $query = "SELECT id FROM $dbNameForm.gradebook_score_display WHERE category_id = 0";
        $rs_check = Database::query($query);

        if (Database::num_rows($rs_check) > 0) {
            // get all gradebook categories id
            $a_categories = array();
            $query = "SELECT id FROM $dbNameForm.gradebook_category";
            $rs_gradebook = Database::query($query);
            if (Database::num_rows($rs_gradebook) > 0) {
                while($row_gradebook = Database::fetch_row($rs_gradebook)) {
                    $a_categories[] = $row_gradebook[0];
                }
            }

            // get all gradebook score display
            $query = "SELECT * FROM $dbNameForm.gradebook_score_display";
            $rs_score_display = Database::query($query);
            if (Database::num_rows($rs_score_display) > 0) {
                $score_color_percent = api_get_setting('gradebook_score_display_colorsplit');
                while ($row_score_display = Database::fetch_array($rs_score_display)) {
                    $score = $row_score_display['score'];
                    $display = $row_score_display['display'];
                    foreach ($a_categories as $category_id) {
                        $ins = "INSERT INTO $dbNameForm.gradebook_score_display(score, display, category_id, score_color_percent) VALUES('$score', '$display', $category_id, '$score_color_percent')";
                        Database::query($ins);
                    }
                }
                // remove score display with category id = 0
                $del = "DELETE FROM $dbNameForm.gradebook_score_display WHERE category_id = 0";
                Database::query($del);
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
                 Log::error('Database name '.$dbNameForm.' is too long, skipping');
            } elseif (!in_array($dbNameForm,$dblist)) {
                 Log::error('Database '.$dbNameForm.' was not found, skipping');
            } else {
                Database::select_db($dbNameForm);
                foreach ($m_q_list as $query) {
                    if ($only_test) {
                         Log::notice("Database::query($dbNameForm,$query)");
                    } else {
                        $res = Database::query($query);
                        if ($log) {
                             Log::notice("In $dbNameForm, executed: $query");
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
                 Log::error('Database name '.$dbStatsForm.' is too long, skipping');
            } elseif (!in_array($dbStatsForm, $dblist)){
                 Log::error('Database '.$dbStatsForm.' was not found, skipping');
            } else {
                Database::select_db($dbStatsForm);

                foreach ($s_q_list as $query) {
                    if ($only_test) {
                         Log::notice("Database::query($dbStatsForm,$query)");
                    } else {
                        $res = Database::query($query);
                        if ($log) {
                             Log::notice("In $dbStatsForm, executed: $query");
                        }
                        if ($res === false) {
                             Log::error('Error in '.$query.': '.Database::error());
                        }
                    }
                }
                $tables = Database::get_tables($dbStatsForm);
                foreach ($tables as $table) {
            	    $query = "ALTER TABLE `".$table."` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;";
                    $res = Database::query($query);
                    if ($res === false) {
                          Log::error('Error in '.$query.': '.Database::error());
                    }
                }
                $query = "ALTER DATABASE `".$dbStatsForm."` DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci;";
                $res = Database::query($query);
                if ($res === false) {
                      Log::error('Error in '.$query.': '.Database::error());
                }


                // chamilo_stat.track_e_attempt table update changing id by id_auto

      			$sql = "SELECT exe_id, question_id, course_code, answer FROM $dbStatsForm.track_e_attempt";
                $result = Database::query($sql);
                if (Database::num_rows($result) > 0) {
                	while ($row = Database::fetch_array($result)) {
                		$course_code  	= $row['course_code'];
                		$course_info 	= api_get_course_info($course_code);
						$my_course_db 	= $course_info['dbName'];
 						$question_id  	= $row['question_id'];
						$answer			= $row['answer'];
						$exe_id			= $row['exe_id'];

						//getting the type question id
                		$sql_question = "SELECT type FROM $my_course_db.quiz_question where id = $question_id";
                		$res_question = Database::query($sql_question);
                		$row  = Database::fetch_array($res_question);
                 		$type = $row['type'];

                		require_once api_get_path(SYS_CODE_PATH).'exercice/question.class.php';
                		//this type of questions produce problems in the track_e_attempt table
                		if (in_array($type, array(UNIQUE_ANSWER, MULTIPLE_ANSWER, MATCHING, MULTIPLE_ANSWER_COMBINATION))) {
		            		$sql_question = "SELECT id_auto FROM $my_course_db.quiz_answer where question_id = $question_id and id = $answer";
		            		$res_question = Database::query($sql_question);
		            		$row = Database::fetch_array($res_question);
		            		$id_auto = $row['id_auto'];
		            		if (!empty($id_auto)) {
	                			$sql = "UPDATE $dbStatsForm.track_e_attempt SET answer = '$id_auto' WHERE exe_id = $exe_id AND question_id = $question_id AND course_code = '$course_code' and answer = $answer ";
	                			Database::query($sql);
		            		}
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
                 Log::error('Database name '.$dbUserForm.' is too long, skipping');
            } elseif (!in_array($dbUserForm,$dblist)) {
                 Log::error('Database '.$dbUserForm.' was not found, skipping');
            } else {
                Database::select_db($dbUserForm);
                foreach ($u_q_list as $query) {
                    if ($only_test) {
                         Log::notice("Database::query($dbUserForm,$query)");
                         Log::notice("In $dbUserForm, executed: $query");
                    } else {
                        $res = Database::query($query);
                        if ($res === false) {
                             Log::error('Error in '.$query.': '.Database::error());
                        }
                    }
                }
                $tables = Database::get_tables($dbUserForm);
                foreach ($tables as $table) {
            	    $query = "ALTER TABLE `".$table."` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;";
                    $res = Database::query($query);
                    if ($res === false) {
                          Log::error('Error in '.$query.': '.Database::error());
                    }
                }
                $query = "ALTER DATABASE `".$dbUserForm."` DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci;";
                $res = Database::query($query);
                if ($res === false) {
                      Log::error('Error in '.$query.': '.Database::error());
                }
            }
        }
        // The SCORM database doesn't need a change in the pre-migrate part - ignore
    }

    $prefix = '';
    if ($singleDbForm) {
        $prefix =  get_config_param ('table_prefix');
    }

    // Get the courses databases queries list (c_q_list)
    $c_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-pre.sql', 'course');
    if (count($c_q_list) > 0) {
        // Get the courses list
        if (strlen($dbNameForm) > 40) {
             Log::error('Database name '.$dbNameForm.' is too long, skipping');
        } elseif(!in_array($dbNameForm, $dblist)) {
             Log::error('Database '.$dbNameForm.' was not found, skipping');
        } else {
            Database::select_db($dbNameForm);
            $res = Database::query("SELECT code,db_name,directory,course_language FROM course WHERE target_course_code IS NULL ORDER BY code");

            if ($res === false) { die('Error while querying the courses list in update_db-1.8.6.2-1.8.7.inc.php'); }

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
                    if (!$singleDbForm) { // otherwise just use the main one
                        Database::select_db($row_course['db_name']);
                    }
                    Log::notice('Course ' . $row_course);

                    foreach ($c_q_list as $query) {
                        if ($singleDbForm) {
                            $query = preg_replace('/^(UPDATE|ALTER TABLE|CREATE TABLE|DROP TABLE|INSERT INTO|DELETE FROM)\s+(\w*)(.*)$/', "$1 $prefix{$row_course['db_name']}_$2$3", $query);
                        }

                        if ($only_test) {
                             Log::notice("Database::query(".$row_course['db_name'].",$query)");
                        } else {
                            $res = Database::query($query);
                            if ($log) {
                                 Log::notice("In ".$row_course['db_name'].", executed: $query");
                            }
                            if ($res === false) {
                                 Log::error('Error in '.$query.': '.Database::error());
                            }
                        }
                    }

                    if (!$singleDbForm) {
                        $tables = Database::get_tables($row_course['db_name']);
                        foreach ($tables as $table) {
            	            $query = "ALTER TABLE `".$table."` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;";
                            $res = Database::query($query);
                            if ($res === false) {
                                 Log::error('Error in '.$query.': '.Database::error());
                            }
                        }
                    	$query = "ALTER DATABASE `".$row_course['db_name']."` DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci;";
                    	$res = Database::query($query);
                        if ($res === false) {
                             Log::error('Error in '.$query.': '.Database::error());
                        }
                    }
                    $t_student_publication = $row_course['db_name'].".student_publication";
                    $t_item_property = $row_course['db_name'].".item_property";

                    if ($singleDbForm) {
                        $t_student_publication = "$prefix{$row_course['db_name']}_student_publication";
                        $t_item_property = "$prefix{$row_course['db_name']}_item_property";
                    }

                    $sql_insert_user = "SELECT ref, insert_user_id FROM $t_item_property WHERE tool='work'";

                    $rs_insert_user = Database::query($sql_insert_user);

                    if ($rs_insert_user === false) {
				    	 Log::error('Could not query insert_user_id table: '.Database::error());
					} else {
						if (Database::num_rows($rs_insert_user) > 0) {
							while ($row_ids = Database::fetch_array($rs_insert_user)) {
								$user_id = $row_ids['insert_user_id'];
								$ref = $row_ids['ref'];
								$sql_upd = "UPDATE $t_student_publication SET user_id='$user_id' WHERE id='$ref'";
								Database::query($sql_upd);
							}
						}
					}

					//updating parent_id of the student_publication table
			        $sql = 'SELECT id, url, parent_id FROM '.$t_student_publication;
					$result = Database::query($sql);
					if (Database::num_rows($result) > 0) {
						$items = Database::store_result($result);
						$directory_list = $file_list=array();

						foreach($items as $item) {
							$student_slash = substr($item['url'], 0, 1);
							//means this is a directory
							if ($student_slash == '/') {
								$directory_list[$item['id']]= $item['url'];
							} else {
							// this is a file with no parents
								if ($item['parent_id'] == 0)
									$file_list []= $item;
							}
						}

						if (is_array($file_list) && count($file_list) > 0) {
							foreach ($file_list as $file) {
								$parent_id = 0;
								if (is_array($directory_list) && count($directory_list) > 0) {
									foreach($directory_list as $id => $dir) {
										$pos = strpos($file['url'], $dir.'/');
										if ($pos !== false) {
											$parent_id = $id;
											break;
										}
									}
								}

								if ($parent_id != 0 ) {
									$sql = 'UPDATE '.$t_student_publication.' SET parent_id = '.$parent_id.' WHERE id = '.$file['id'].'';
									Database::query($sql);
								}
							}
						}
                	}






                }
            }
        }
    }
    // Get the courses databases queries list (c_q_list)
    $c_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-post.sql', 'course');
    if (count($c_q_list) > 0) {
        // Get the courses list
        if (strlen($dbNameForm) > 40) {
             Log::error('Database name '.$dbNameForm.' is too long, skipping');
        } elseif (!in_array($dbNameForm, $dblist)) {
             Log::error('Database '.$dbNameForm.' was not found, skipping');
        } else {
            Database::select_db($dbNameForm);
            $res = Database::query("SELECT code,db_name,directory,course_language FROM course WHERE target_course_code IS NULL");
            if ($res === false) { die('Error while querying the courses list in update_db-1.8.6.2-1.8.7.inc.php'); }
            if (Database::num_rows($res) > 0) {
                $i = 0;
                while ($row = Database::fetch_array($res)) {
                    $list[] = $row;
                    $i++;
                }
                foreach ($list as $row) {
                    // Now use the $c_q_list
                    /**
                     * We connect to the right DB first to make sure we can use the queries
                     * without a database name
                     */
                    $prefix_course = $prefix;
                    if ($singleDbForm) {
                        $prefix_course = $prefix.$row['db_name']."_";
                    } else {
                        Database::select_db($row['db_name']);
                    }

                    foreach($c_q_list as $query) {
                        if ($singleDbForm) { //otherwise just use the main one
                            $query = preg_replace('/^(UPDATE|ALTER TABLE|CREATE TABLE|DROP TABLE|INSERT INTO|DELETE FROM)\s+(\w*)(.*)$/', "$1 $prefix$2$3", $query);
                        }
                        if ($only_test) {
                             Log::notice("Database::query(".$row['db_name'].",$query)");
                        } else {
                            $res = Database::query($query);
                            if ($log) {
                                 Log::notice("In ".$row['db_name'].", executed: $query");
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

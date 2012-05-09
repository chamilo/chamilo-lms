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

$old_file_version = '1.8.8';
$new_file_version = '1.9.0';

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
        $_configuration['table_prefix'] 	= get_config_param('courseTablePrefix');
        $_configuration['main_database'] 	= get_config_param('mainDbName');
        $_configuration['db_prefix'] 		= get_config_param('dbNamePrefix');
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
            $dbUserForm  = $dbNameForm;
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
            } elseif (!in_array($dbNameForm, $dblist)) {
                error_log('Database '.$dbNameForm.' was not found, skipping', 0);
            } else {
                Database::select_db($dbNameForm);
                foreach ($m_q_list as $query){
                    if ($only_test) {
                        error_log("Database::query($dbNameForm,$query)", 0);
                    } else {
                        $res = Database::query($query);
                        if ($log) {
                            error_log("In $dbNameForm, executed: $query", 0);
                        }
                        if ($res === false) {
                        	error_log('Error in '.$query.': '.Database::error());
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
            } elseif (!in_array($dbStatsForm, $dblist)){
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
                        if ($res === false) {
                            error_log('Error in '.$query.': '.Database::error());
                        }
                    }
                }
            }
        }
        
        //Moving Stats DB to the main DB
        
        $stats_table = array(        
			"track_c_browsers",
			"track_c_countries",
			"track_c_os",
			"track_c_providers",
			"track_c_referers",
			"track_e_access",
			"track_e_attempt",
			"track_e_attempt_recording",
			"track_e_course_access",
			"track_e_default",
			"track_e_downloads",
			"track_e_exercices",
			"track_e_hotpotatoes",
			"track_e_hotspot",
			"track_e_item_property",
			"track_e_lastaccess",
			"track_e_links",
			"track_e_login",
			"track_e_online",
			"track_e_open",
			"track_e_uploads",
			"report_keys", //@todo add the "track_" prefix see #3967
        	"report_values",
        	"report_keys",
        	"stored_values",
        	"stored_values_stack",
        );
        
        if ($dbNameForm != $dbStatsForm) {
        	Database::select_db($dbStatsForm);
	        foreach ($stats_table as $stat_table) {
	        	$sql = "ALTER TABLE $dbStatsForm.$stat_table RENAME $dbNameForm.$stat_table";
	        	Database::query($sql);
	        }
	        Database::select_db($dbNameForm);
        }
        
        //Renaming user tables in the main DB
        $user_tables = array(
            'personal_agenda',
            'personal_agenda_repeat',
            'personal_agenda_repeat_not',
            'user_course_category',
        );
                
        if ($dbNameForm != $dbUserForm) {
        	Database::select_db($dbUserForm);
	        foreach ($user_tables as $table) {
	        	$sql = "ALTER TABLE $dbUserForm.$table RENAME $dbNameForm.$table";
	        	Database::query($sql);
	        }
	        Database::select_db($dbNameForm);
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
                    if ($only_test) {
                        error_log("Database::query($dbUserForm,$query)", 0);
                        error_log("In $dbUserForm, executed: $query", 0);
                    } else {
                        $res = Database::query($query);
                        if ($res === false) {
                            error_log('Error in '.$query.': '.Database::error());
                        }
                    }
                }
            }
        }
        
        //Moving User DB to the main database
        $users_table = array(
        			"personal_agenda",
        			"personal_agenda_repeat",
        			"personal_agenda_repeat_not",
        			"user_course_category"        			        
        );
        
        if ($dbNameForm != $dbUserForm) {
        	Database::select_db($dbUserForm);
        	foreach($users_table as $table) {
        		$sql = "ALTER TABLE $dbUserForm.$table RENAME  $dbNameForm.$table";
        		Database::query($sql);
        	}
        	Database::select_db($dbNameForm);
        }                
    }    
    
    //Adds the c_XXX courses tables see #3910
    require_once api_get_path(LIBRARY_PATH).'add_course.lib.inc.php';
    global $_configuration;
    create_course_tables();
    
    $prefix = '';
    if ($singleDbForm) {
        $prefix =  get_config_param('table_prefix');
    }

    // Get the courses databases queries list (c_q_list)
    $c_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-pre.sql', 'course');
    error_log('Starting migration: '.$old_file_version.' - '.$new_file_version);
    
    if (count($c_q_list) > 0) {
        // Get the courses list
        if (strlen($dbNameForm) > 40) {
            error_log('Database name '.$dbNameForm.' is too long, skipping', 0);
        } elseif(!in_array($dbNameForm, $dblist)) {
            error_log('Database '.$dbNameForm.' was not found, skipping', 0);
        } else {
            Database::select_db($dbNameForm);
            $res = Database::query("SELECT id, code,db_name,directory,course_language FROM course WHERE target_course_code IS NULL ORDER BY code");

            if ($res === false) { die('Error while querying the courses list in update_db-1.8.6.2-1.8.7.inc.php'); }
			
            $errors = array();
              
            if (Database::num_rows($res) > 0) {
                $i = 0;
                $list = array();
                while ($row = Database::fetch_array($res)) {
                    $list[] = $row;
                    $i++;
                }
                
                foreach ($list as $row_course) {
                    // Now use the $c_q_list
                    
                    if (!$singleDbForm) { // otherwise just use the main one
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
                            if ($res === false) {
                                error_log('Error in '.$query.': '.Database::error());
                            }
                        }
                    }                    
                    
                    //Course tables to be migrated
                    $table_list = array(
                    					'announcement',
                                        'announcement_attachment',
                                        'attendance',
                                        'attendance_calendar',
                                        'attendance_result',
                                        'attendance_sheet',
                                        'attendance_sheet_log',
                                        'blog',
                                        'blog_attachment',
                                        'blog_comment',
                                        'blog_post',
                                        'blog_rating',
                                        'blog_rel_user',
                                        'blog_task',
                                        'blog_task_rel_user',
                                        'calendar_event',
                                        'calendar_event_attachment',
                                        'calendar_event_repeat',
                                        'calendar_event_repeat_not',
                                        'chat_connected',
                                        'course_description',
                                        'course_setting',
                                        'document',
                                        'dropbox_category',
                                        'dropbox_feedback',
                                        'dropbox_file',
                                        'dropbox_person',
                                        'dropbox_post',
                                        'forum_attachment',
                                        'forum_category',
                                        'forum_forum',
                                        'forum_mailcue',
                                        'forum_notification',
                                        'forum_post',
                                        'forum_thread',
                                        'forum_thread_qualify',
                                        'forum_thread_qualify_log',
                                        'glossary',
                                        'group_category',
                                        'group_info',
                                        'group_rel_tutor',
                                        'group_rel_user',
                                        'item_property',
                                        'link',
                                        'link_category',
                                        'lp',
                                        'lp_item',
                                        'lp_item_view',
                                        'lp_iv_interaction',
                                        'lp_iv_objective',
                                        'lp_view',
                                        'notebook',
                                        'metadata',
                                        'online_connected',
                                        'online_link',
                                        'permission_group',
                                        'permission_task',
                                        'permission_user',
                                        'quiz',
                                        'quiz_answer',
                                        'quiz_question',
                                        'quiz_question_option',
                                        'quiz_rel_question',
                                        'resource',
                                        'role',
                                        'role_group',
                                        'role_permissions',
                                        'role_user',
                                        'student_publication',
                                        'student_publication_assignment',
                                        'survey',
                                        'survey_answer',
                                        'survey_group',
                                        'survey_invitation',
                                        'survey_question',
                                        'survey_question_option',
                                        'thematic',
                                        'thematic_advance',
                                        'thematic_plan',
                                        'tool',
                                        'tool_intro',
                                        'userinfo_content',
                                        'userinfo_def',
                                        'wiki',
                                        'wiki_conf',
                                        'wiki_discuss',
                                        'wiki_mailcue'                   
                    );
                    
                    error_log('<<<------- Loading DB course '.$row_course['db_name'].' -------->>');
                    
                    $count = $old_count = 0;
                    foreach ($table_list as $table) {
                    	$old_table = $row_course['db_name'].".".$table;
                    	if ($singleDbForm) {
                    		$old_table = "$prefix{$row_course['db_name']}_".$table;
                    	}
                    	$course_id = $row_course['id'];
                    	$new_table = DB_COURSE_PREFIX.$table;
                    	
                    	if (!$singleDbForm) {
                    		// otherwise just use the main one
                    		Database::select_db($row_course['db_name']);
                    	} else {
                    		Database::select_db($dbNameForm);
                    	}                    	
                    	
                    	//Count of rows
                    	$sql 	= "SELECT count(*) FROM $old_table";
                    	$result = Database::query($sql);
                    	
                    	$old_count = 0;
                    	if ($result) {
                    		$row 		= Database::fetch_row($result);
                    		$old_count = $row[0];
                    	} else {
                    		error_log("Seems that the table $old_table doesn't exists ");
                    	}                    	
                    	error_log("#rows in $old_table: $old_count");
                    	
                    	$sql = "SELECT * FROM $old_table";
                    	$result = Database::query($sql);
                    	
                    	$count = 0;                    	
                    	while($row = Database::fetch_array($result, 'ASSOC')) {
                    		$row['c_id'] = $course_id;
                    		Database::select_db($dbNameForm);
                    		$id = Database::insert($new_table, $row);
                    		if (is_numeric($id)) {
                    			$count++;
                    		} else {
                    			$errors[$old_table][] = $row;                    			
                    		}
                    	}                    	
                    	error_log("# rows inserted in $new_table: $count");
                    	
                    	if ($old_count != $count) {
                    		error_log("ERROR count of new and old table doesn't match: $old_count - $new_table");
                    		error_log("Check the results: ");
                    		error_log(print_r($errors, 1));
                    	}
                    }
                    error_log('<<<------- end  -------->>');
                }
            }
        }
    }
} else {
    echo 'You are not allowed here !';
}
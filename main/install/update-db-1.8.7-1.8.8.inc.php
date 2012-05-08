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
Log::notice("Starting " . basename(__FILE__));

$old_file_version = '1.8.7';
$new_file_version = '1.8.8';

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
            }
        }

        /* // This fragment of code is not necessary so far.
        // Get the main queries list (m_q_list)
        $m_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-post.sql', 'main');
        if (count($m_q_list) > 0) {
            // Now use the $m_q_list
            // We connect to the right DB first to make sure we can use the queries
            // without a database name.
            if (strlen($dbNameForm) > 40) {
                error_log('Database name '.$dbNameForm.' is too long, skipping');
            } elseif (!in_array($dbNameForm,$dblist)) {
                error_log('Database '.$dbNameForm.' was not found, skipping');
            } else {
                Database::select_db($dbNameForm);
                foreach ($m_q_list as $query) {
                    if ($only_test) {
                        error_log("Database::query($dbNameForm,$query)");
                    } else {
                        $res = Database::query($query);
                        if ($log) {
                            error_log("In $dbNameForm, executed: $query");
                        }
                    }
                }
            }
        }
        */

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
                    
                    $table_lp_item_view = $row_course['db_name'].".lp_item_view";
                    $table_lp_view = $row_course['db_name'].".lp_view";
                    $table_lp_item = $row_course['db_name'].".lp_item";                                     

                    if ($singleDbForm) {
                        $table_lp_item_view = "$prefix{$row_course['db_name']}_lp_item_view";
                        $table_lp_view = "$prefix{$row_course['db_name']}_lp_view";
                        $table_lp_item = "$prefix{$row_course['db_name']}_lp_item";                                              
                    }                    
                    
                    // Filling the track_e_exercices.orig_lp_item_view_id field  in order to have better traceability in exercises included in a LP see #3188
                                     
                    $query = "SELECT DISTINCT path as exercise_id, lp_item_id, lp_view_id, user_id, v.lp_id 
                              FROM $table_lp_item_view iv INNER JOIN  $table_lp_view v  ON v.id = iv.lp_view_id INNER JOIN $table_lp_item i ON  i.id = lp_item_id
                              WHERE item_type = 'quiz'";
                    $result = Database::query($query);
                    
                    if (Database::num_rows($result) > 0) {
                        while ($row = Database::fetch_array($result,'ASSOC')) {                            
                            $sql = "SELECT exe_id FROM $dbNameForm.track_e_exercices 
                                    WHERE exe_user_id = {$row['user_id']} AND 
                                    exe_cours_id = '{$row_course['code']}' AND 
                                    exe_exo_id = {$row['exercise_id']}  AND
                                    orig_lp_id = {$row['lp_id']}  AND 
                                    orig_lp_item_id = {$row['lp_item_id']} ";
                            $sub_result = Database::query($sql);
                            $exe_list = array();
                            while ($sub_row = Database::fetch_array($sub_result,'ASSOC')) {
                                $exe_list[] = $sub_row['exe_id'];             
                            }
                            
                            $sql = "SELECT iv.id, iv.view_count
                                      FROM $table_lp_item_view iv INNER JOIN  $table_lp_view v  ON v.id = iv.lp_view_id INNER JOIN $table_lp_item i ON  i.id = lp_item_id
                                      WHERE item_type = 'quiz' AND user_id =  {$row['user_id']} AND path = {$row['exercise_id']} ";
                            $sub_result = Database::query($sql);
                            $lp_item_view_id_list = array();
                            while ($sub_row = Database::fetch_array($sub_result,'ASSOC')) {
                                $lp_item_view_id_list[] = $sub_row['id'];             
                            } 
                            $i = 0;
                            foreach($exe_list as $exe_id) {       
                                $lp_item_view_id = $lp_item_view_id_list[$i];                         
                                $update = "UPDATE $dbNameForm.track_e_exercices SET orig_lp_item_view_id  = '$lp_item_view_id' WHERE exe_id = $exe_id ";                                
                                Database::query($update);                                
                                $i++;
                            }                            
                        }            
                    }
                }
            }
            
            //Adding notifications options
    
            $sql = "INSERT INTO $dbNameForm.user_field (field_type, field_variable, field_display_text, field_visible, field_changeable, field_default_value) values (4, 'mail_notify_invitation',   'MailNotifyInvitation',1,1,'1') ";
            $result = Database::query($sql);
            $id = Database::insert_id();
                        
            $sql = "INSERT INTO $dbNameForm.user_field_options (field_id, option_value, option_display_text, option_order) values ($id, '1', 'AtOnce',1) ";
            $result = Database::query($sql);
            $sql = "INSERT INTO $dbNameForm.user_field_options (field_id, option_value, option_display_text, option_order) values ($id, '8', 'Daily',2) ";
            $result = Database::query($sql);
            $sql = "INSERT INTO $dbNameForm.user_field_options (field_id, option_value, option_display_text, option_order) values ($id, '0', 'No',3) ";
            $result = Database::query($sql);         
                
            
            $sql = "INSERT INTO $dbNameForm.user_field (field_type, field_variable, field_display_text, field_visible, field_changeable, field_default_value) values (4, 'mail_notify_message',		 'MailNotifyMessage',1,1,'1')";
            $result = Database::query($sql);
            $id = Database::insert_id();
            
            $sql = "INSERT INTO $dbNameForm.user_field_options (field_id, option_value, option_display_text, option_order) values ($id, '1', 'AtOnce',1) ";
            $result = Database::query($sql);
            $sql = "INSERT INTO $dbNameForm.user_field_options (field_id, option_value, option_display_text, option_order) values ($id, '8', 'Daily',2) ";
            $result = Database::query($sql);
            $sql = "INSERT INTO $dbNameForm.user_field_options (field_id, option_value, option_display_text, option_order) values ($id, '0', 'No',3) ";
            $result = Database::query($sql);
            
                        
            $sql = "INSERT INTO $dbNameForm.user_field (field_type, field_variable, field_display_text, field_visible, field_changeable, field_default_value) values (4, 'mail_notify_group_message','MailNotifyGroupMessage',1,1,'1') ";
            $result = Database::query($sql);
            $id = Database::insert_id();
            
            $sql = "INSERT INTO $dbNameForm.user_field_options (field_id, option_value, option_display_text, option_order) values ($id, '1', 'AtOnce',1) ";
            $result = Database::query($sql);
            $sql = "INSERT INTO $dbNameForm.user_field_options (field_id, option_value, option_display_text, option_order) values ($id, '8', 'Daily',2) ";
            $result = Database::query($sql);
            $sql = "INSERT INTO $dbNameForm.user_field_options (field_id, option_value, option_display_text, option_order) values ($id, '0', 'No',3) ";
            $result = Database::query($sql);
                     
            //Fixing table access_url_rel_course if the platform have courses that were created in Dok€os 1.8.5
              
            if (!isset($_configuration['multiple_access_urls']) || $_configuration['multiple_access_urls'] == false) {
                $sql = "SELECT code FROM $dbNameForm.course";
                $result = Database::query($sql);
                while ($row = Database::fetch_array($result)) {
                    //Adding course to default URL just in case
                    $sql = "INSERT INTO $dbNameForm.access_url_rel_course SET course_code = '".Database::escape_string($row['code'])."', access_url_id = '1' ";  
                    Database::query($sql);
                }                
            }
        }
    }
    
    




    /* // This fragment of code is not necessary so far.
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
            if ($res === false) { die('Error while querying the courses list in update_db-1.8.7-1.8.8.inc.php'); }
            if (Database::num_rows($res) > 0) {
                $i = 0;
                while ($row = Database::fetch_array($res)) {
                    $list[] = $row;
                    $i++;
                }
                foreach ($list as $row) {
                    // Now use the $c_q_list
                    // We connect to the right DB first to make sure we can use the queries
                    // without a database name
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
    */


} else {

    echo 'You are not allowed here !';

}

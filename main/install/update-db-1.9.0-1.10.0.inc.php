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

$old_file_version = '1.9.0';
$new_file_version = '1.10.0';

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
                Log::error('Database name '.$dbNameForm.' is too long, skipping');
            } elseif (!in_array($dbNameForm, $dblist)) {
                Log::error('Database '.$dbNameForm.' was not found, skipping');
            } else {
                iDatabase::select_db($dbNameForm);
                foreach ($m_q_list as $query){
                    if ($only_test) {
                        Log::notice("iDatabase::query($dbNameForm,$query)");
                    } else {
                        $res = iDatabase::query($query);
                        if ($res === false) {
                        	Log::error('Error in '.$query.': '.iDatabase::error());
                        }
                    }
                }
            }
        }
        
        if (INSTALL_TYPE_UPDATE == 'update') {
            $session_table = "$dbNameForm.session";
            
            //Fixes new changes in sessions
            $sql = "SELECT id, date_start, date_end, nb_days_access_before_beginning, nb_days_access_after_end  FROM $session_table ";
            $result = iDatabase::query($sql);
            while ($session = Database::fetch_array($result)) {
                $session_id = $session['id'];
                
                //Fixing date_start
                if (isset($session['date_start']) && !empty($session['date_start']) && $session['date_start'] != '0000-00-00') {
                    $datetime =  $session['date_start'].' 00:00:00';
                    $update_sql = "UPDATE $session_table SET display_start_date = '$datetime' , access_start_date = '$datetime' WHERE id = $session_id";
                    iDatabase::query($update_sql);
                    
                    //Fixing nb_days_access_before_beginning
                    if (!empty($session['nb_days_access_before_beginning'])) {
                        $datetime = api_strtotime($datetime, 'UTC') - (86400*$session['nb_days_access_before_beginning']);
                        $datetime = api_get_utc_datetime($datetime);
                        $update_sql = "UPDATE $session_table SET coach_access_start_date = '$datetime' WHERE id = $session_id";
                        iDatabase::query($update_sql);
                    }
                }
                
                //Fixing end_date
                if (isset($session['date_end']) && !empty($session['date_end']) && $session['date_end'] != '0000-00-00') {                    
                    $datetime =  $session['date_end'].' 00:00:00';
                    $update_sql = "UPDATE $session_table SET display_end_date = '$datetime' , access_end_date = '$datetime' WHERE id = $session_id";
                    iDatabase::query($update_sql);                    
                    
                    //Fixing nb_days_access_before_beginning
                    if (!empty($session['nb_days_access_after_end'])) {
                        $datetime = api_strtotime($datetime, 'UTC') + (86400*$session['nb_days_access_after_end']);                        
                        $datetime = api_get_utc_datetime($datetime);
                        $update_sql = "UPDATE $session_table SET coach_access_end_date = '$datetime' WHERE id = $session_id";
                        iDatabase::query($update_sql);
                    }
                }                                
            }
        }        
    }    
} else {
    echo 'You are not allowed here !' . __FILE__;
}


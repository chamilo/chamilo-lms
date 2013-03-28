<?php
/* For licensing terms, see /license.txt */

$update = function($_configuration, $mainConnection, $dryRun, $output) {

    $mainConnection->beginTransaction();
    $singleDbForm = false;

    $dbNameForm = $_configuration['main_database'];

    if ($singleDbForm) {
        $dbStatsForm = isset($_configuration['statistics_database']) ? $_configuration['statistics_database'] : $_configuration['main_database'];
        $dbUserForm  = isset($_configuration['user_personal_database']) ? $_configuration['user_personal_database'] : $_configuration['main_database'];
    }

    $prefix = '';
    if ($singleDbForm) {
        $prefix =  $_configuration['table_prefix'];
    }

    iDatabase::select_db($dbNameForm);
    $output->writeln('Getting the course list: ');
    $res = iDatabase::query("SELECT code,db_name,directory,course_language FROM course WHERE target_course_code IS NULL ORDER BY code");

    if ($res === false) { die('Error while querying the courses list in update_db-1.8.7.1-1.8.8.inc.php'); }

    if (iDatabase::num_rows($res) > 0) {
        $i = 0;
        $list = array();
        while ($row = iDatabase::fetch_array($res)) {
            $list[] = $row;
            $i++;
        }
        foreach ($list as $row_course) {
            /**
             * We connect to the right DB first to make sure we can use the queries
             * without a database name
             */
            if (!$singleDbForm) { // otherwise just use the main one
                iDatabase::select_db($row_course['db_name']);
            }
            $output->writeln('Updating course db: ' . $row_course['db_name']);

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
            $result = iDatabase::query($query);

            if (iDatabase::num_rows($result) > 0) {
                while ($row = iDatabase::fetch_array($result,'ASSOC')) {
                    $sql = "SELECT exe_id FROM $dbStatsForm.track_e_exercices
                            WHERE exe_user_id = {$row['user_id']} AND
                            exe_cours_id = '{$row_course['code']}' AND
                            exe_exo_id = {$row['exercise_id']}  AND
                            orig_lp_id = {$row['lp_id']}  AND
                            orig_lp_item_id = {$row['lp_item_id']} ";
                    $sub_result = iDatabase::query($sql);
                    $exe_list = array();
                    while ($sub_row = iDatabase::fetch_array($sub_result,'ASSOC')) {
                        $exe_list[] = $sub_row['exe_id'];
                    }

                    $sql = "SELECT iv.id, iv.view_count
                              FROM $table_lp_item_view iv INNER JOIN  $table_lp_view v  ON v.id = iv.lp_view_id INNER JOIN $table_lp_item i ON  i.id = lp_item_id
                              WHERE item_type = 'quiz' AND user_id =  {$row['user_id']} AND path = {$row['exercise_id']} ";
                    $sub_result = iDatabase::query($sql);
                    $lp_item_view_id_list = array();
                    while ($sub_row = iDatabase::fetch_array($sub_result,'ASSOC')) {
                        $lp_item_view_id_list[] = $sub_row['id'];
                    }
                    $i = 0;
                    foreach($exe_list as $exe_id) {
                        $lp_item_view_id = $lp_item_view_id_list[$i];
                        $update = "UPDATE $dbNameForm.track_e_exercices SET orig_lp_item_view_id  = '$lp_item_view_id' WHERE exe_id = $exe_id ";
                        iDatabase::query($update);
                        $i++;
                    }
                }
            }
        }
    }

    //Adding notifications options

    $sql = "INSERT INTO $dbNameForm.user_field (field_type, field_variable, field_display_text, field_visible, field_changeable, field_default_value) values (4, 'mail_notify_invitation',   'MailNotifyInvitation',1,1,'1') ";
    $result = iDatabase::query($sql);
    $id = iDatabase::insert_id();

    $sql = "INSERT INTO $dbNameForm.user_field_options (field_id, option_value, option_display_text, option_order) values ($id, '1', 'AtOnce',1) ";
    $result = iDatabase::query($sql);
    $sql = "INSERT INTO $dbNameForm.user_field_options (field_id, option_value, option_display_text, option_order) values ($id, '8', 'Daily',2) ";
    $result = iDatabase::query($sql);
    $sql = "INSERT INTO $dbNameForm.user_field_options (field_id, option_value, option_display_text, option_order) values ($id, '0', 'No',3) ";
    $result = iDatabase::query($sql);

    $sql = "INSERT INTO $dbNameForm.user_field (field_type, field_variable, field_display_text, field_visible, field_changeable, field_default_value) values (4, 'mail_notify_message',		 'MailNotifyMessage',1,1,'1')";
    $result = iDatabase::query($sql);
    $id = iDatabase::insert_id();

    $sql = "INSERT INTO $dbNameForm.user_field_options (field_id, option_value, option_display_text, option_order) values ($id, '1', 'AtOnce',1) ";
    $result = iDatabase::query($sql);
    $sql = "INSERT INTO $dbNameForm.user_field_options (field_id, option_value, option_display_text, option_order) values ($id, '8', 'Daily',2) ";
    $result = iDatabase::query($sql);
    $sql = "INSERT INTO $dbNameForm.user_field_options (field_id, option_value, option_display_text, option_order) values ($id, '0', 'No',3) ";
    $result = iDatabase::query($sql);


    $sql = "INSERT INTO $dbNameForm.user_field (field_type, field_variable, field_display_text, field_visible, field_changeable, field_default_value) values (4, 'mail_notify_group_message','MailNotifyGroupMessage',1,1,'1') ";
    $result = iDatabase::query($sql);
    $id = iDatabase::insert_id();

    $sql = "INSERT INTO $dbNameForm.user_field_options (field_id, option_value, option_display_text, option_order) values ($id, '1', 'AtOnce',1) ";
    $result = iDatabase::query($sql);
    $sql = "INSERT INTO $dbNameForm.user_field_options (field_id, option_value, option_display_text, option_order) values ($id, '8', 'Daily',2) ";
    $result = iDatabase::query($sql);
    $sql = "INSERT INTO $dbNameForm.user_field_options (field_id, option_value, option_display_text, option_order) values ($id, '0', 'No',3) ";
    $result = iDatabase::query($sql);

    //Fixing table access_url_rel_course if the platform have courses that were created in Dok€os 1.8.5

    if (!isset($_configuration['multiple_access_urls']) || $_configuration['multiple_access_urls'] == false) {
        $sql = "SELECT code FROM $dbNameForm.course";
        $result = iDatabase::query($sql);
        while ($row = iDatabase::fetch_array($result)) {
            //Adding course to default URL just in case
            $sql = "INSERT INTO $dbNameForm.access_url_rel_course SET course_code = '".iDatabase::escape_string($row['code'])."', access_url_id = '1' ";
            iDatabase::query($sql);
        }
    }
};



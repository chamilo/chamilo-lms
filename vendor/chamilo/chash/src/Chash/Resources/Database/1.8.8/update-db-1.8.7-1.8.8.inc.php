<?php
/* For licensing terms, see /license.txt */

$update = function($_configuration, $mainConnection, $courseList, $dryRun, $output, $upgrade)
{
    $portalSettings = $upgrade->getPortalSettings();
    $databaseList = $upgrade->generateDatabaseList($courseList);
    $courseDatabaseConnectionList = $databaseList['course']; // main  user stats course

    /** @var \Doctrine\DBAL\Connection $userConnection */
    $userConnection = $upgrade->getHelper($databaseList['user'][0]['database'])->getConnection();
    /** @var \Doctrine\DBAL\Connection $mainConnection */
    $mainConnection = $upgrade->getHelper($databaseList['main'][0]['database'])->getConnection();
    /** @var \Doctrine\DBAL\Connection $statsConnection */
    $statsConnection = $upgrade->getHelper($databaseList['stats'][0]['database'])->getConnection();

    $mainConnection->beginTransaction();

    try {
        if (!empty($courseList)) {
            foreach ($courseList as $row_course) {
                $output->writeln('Updating course db: '.$row_course['db_name']);

                $prefix = $upgrade->getTablePrefix($_configuration, $row_course['db_name']);
                $table_lp_item_view = $prefix."lp_item_view";
                $table_lp_view = $prefix."lp_view";
                $table_lp_item = $prefix."lp_item";

                $courseConnection = null;
                foreach ($courseDatabaseConnectionList as $database) {
                    if ($database['database'] == '_chamilo_course_'.$row_course['db_name']) {
                        /** @var \Doctrine\DBAL\Connection $courseConnection */
                        $courseConnection = $upgrade->getHelper($database['database'])->getConnection();
                    }
                }

                if (empty($courseConnection)) {
                    $output->writeln("<info>We can't established a DB connection for this course: ".$row_course['db_name']);
                }

                /* Filling the track_e_exercices.orig_lp_item_view_id field  in order to have better
                traceability in exercises included in a LP see #3188 */

                $query = "SELECT DISTINCT path as exercise_id, lp_item_id, lp_view_id, user_id, v.lp_id
                          FROM $table_lp_item_view iv INNER JOIN  $table_lp_view v
                          ON v.id = iv.lp_view_id INNER JOIN $table_lp_item i
                          ON  i.id = lp_item_id
                          WHERE item_type = 'quiz'";

                $result = $courseConnection->executeQuery($query);
                $rows = $result->fetchAll();

                if (count($rows) > 0) {
                    foreach ($rows as $row) {
                        $sql = "SELECT exe_id FROM track_e_exercices
                                WHERE exe_user_id = {$row['user_id']} AND
                                exe_cours_id = '{$row_course['code']}' AND
                                exe_exo_id = {$row['exercise_id']}  AND
                                orig_lp_id = {$row['lp_id']}  AND
                                orig_lp_item_id = {$row['lp_item_id']} ";
                        $sub_result = $statsConnection->executeQuery($sql);
                        $sub_rows = $sub_result->fetchAll();
                        $exe_list = array();
                        foreach ($sub_rows as $sub_row) {
                            $exe_list[] = $sub_row['exe_id'];
                        }

                        $sql = "SELECT iv.id, iv.view_count
                                  FROM $table_lp_item_view iv INNER JOIN  $table_lp_view v
                                  ON v.id = iv.lp_view_id INNER JOIN $table_lp_item i
                                  ON  i.id = lp_item_id
                                  WHERE item_type = 'quiz' AND
                                        user_id =  {$row['user_id']} AND
                                        path = {$row['exercise_id']} ";
                        $sub_result = $courseConnection->executeQuery($sql);
                        $sub_rows = $sub_result->fetchAll();
                        $lp_item_view_id_list = array();
                        foreach ($sub_rows as $sub_row) {
                            $lp_item_view_id_list[] = $sub_row['id'];
                        }
                        $i = 0;

                        foreach ($exe_list as $exe_id) {
                            $lp_item_view_id = $lp_item_view_id_list[$i];
                            $update = "UPDATE track_e_exercices SET orig_lp_item_view_id  = '$lp_item_view_id'
                                       WHERE exe_id = $exe_id ";
                            $statsConnection->executeQuery($update);
                            $output->writeln($update);
                        }
                    }
                }
            }
        } else {
            $output->writeln('<info>Any course found in this platform.<info>');
        }

        // Adding notifications options

        $output->writeln('<comment>Updating main/stat/user db:</comment>');

        $sql = "INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable, field_default_value)
                VALUES (4, 'mail_notify_invitation', 'MailNotifyInvitation',1,1,'1') ";
        $mainConnection->executeQuery($sql);
        $output->writeln($sql);
        $id = $mainConnection->lastInsertId();

        $sql = "INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order)
                VALUES ($id, '1', 'AtOnce',1) ";
        $mainConnection->executeQuery($sql);
        $output->writeln($sql);
        $sql = "INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order)
                VALUES ($id, '8', 'Daily',2) ";
        $mainConnection->executeQuery($sql);
        $output->writeln($sql);
        $sql = "INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order)
                VALUES ($id, '0', 'No',3) ";
        $mainConnection->executeQuery($sql);
        $output->writeln($sql);

        $sql = "INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable, field_default_value)
                VALUES (4, 'mail_notify_message', 'MailNotifyMessage',1,1,'1')";
        $mainConnection->executeQuery($sql);
        $output->writeln($sql);
        $id = $mainConnection->lastInsertId();

        $sql = "INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order)
                VALUES ($id, '1', 'AtOnce',1) ";
        $mainConnection->executeQuery($sql);
        $output->writeln($sql);

        $sql = "INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order)
                VALUES ($id, '8', 'Daily',2) ";
        $mainConnection->executeQuery($sql);
        $output->writeln($sql);
        $sql = "INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order)
                VALUES ($id, '0', 'No',3) ";
        $mainConnection->executeQuery($sql);
        $output->writeln($sql);

        $sql = "INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable, field_default_value)
                values (4, 'mail_notify_group_message', 'MailNotifyGroupMessage',1,1,'1') ";
        $mainConnection->executeQuery($sql);
        $output->writeln($sql);
        $id = $mainConnection->lastInsertId();

        $sql = "INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order)
                VALUES ($id, '1', 'AtOnce',1) ";
        $mainConnection->executeQuery($sql);
        $output->writeln($sql);
        $sql = "INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order)
                VALUES ($id, '8', 'Daily',2) ";
        $mainConnection->executeQuery($sql);
        $output->writeln($sql);
        $sql = "INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order)
                VALUES ($id, '0', 'No',3) ";
        $mainConnection->executeQuery($sql);
        $output->writeln($sql);

        // Fixing table access_url_rel_course if the platform have courses that were created in Dok€ 1.8.5.

        if (!isset($_configuration['multiple_access_urls']) || $_configuration['multiple_access_urls'] == false) {
            $output->writeln("Fixing access_url_rel_course:");
            $sql = "SELECT code FROM course";
            $result = $mainConnection->executeQuery($sql);
            $rows = $result->fetchAll();
            foreach ($rows as $row) {

                // Adding course to default URL just in case.
                // Check if already exists
                $sql = "SELECT course_code FROM access_url_rel_course
                        WHERE course_code = '".$row['code']."' AND access_url_id = 1";
                $result = $mainConnection->executeQuery($sql);

                if ($result->rowCount() == 0) {
                    $sql = "INSERT INTO access_url_rel_course
                            SET course_code = '".$row['code']."', access_url_id = '1' ";
                    $mainConnection->executeQuery($sql);
                    $output->writeln($sql);
                }
            }
        }

        if ($dryRun) {
            $output->writeln('<info>Queries were not executed. Because dry-run is on<info>');

        } else {
            $mainConnection->commit();
        }
    } catch (Exception $e) {
        $mainConnection->rollback();
        throw $e;
    }
};

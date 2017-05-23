<?php
/* For licensing terms, see /license.txt */

$update = function($_configuration, \Doctrine\DBAL\Connection $mainConnection, $courseList, $dryRun, $output, $upgrade) {

    $mainConnection->beginTransaction();

    $dbNameForm = $_configuration['main_database'];

    $session_table = "$dbNameForm.session";
    $session_rel_course_table = "$dbNameForm.session_rel_course";
    $session_rel_course_rel_user_table = "$dbNameForm.session_rel_course_rel_user";
    $course_table = "$dbNameForm.course";
    $courseRelUserTable = "$dbNameForm.course_rel_user";

    $accessUrlRelCourseTable = "$dbNameForm.access_url_rel_course";
    $accessUrlRelSessionTable = "$dbNameForm.access_url_rel_session";

    // Fixes new changes in sessions
    $sql = "SELECT id, date_start, date_end, nb_days_access_before_beginning, nb_days_access_after_end FROM $session_table";
    $result = $mainConnection->executeQuery($sql);
    $sessions = $result->fetchAll();

    foreach ($sessions as $session) {
        $session_id = $session['id'];

        // Check if the session is registered in the access_url_rel_session table
        $sql = "SELECT session_id FROM $accessUrlRelSessionTable WHERE session_id = $session_id";
        $result = $mainConnection->executeQuery($sql);
        if ($result->rowCount() == 0) {
            $sql = "INSERT INTO $accessUrlRelSessionTable (session_id, access_url_id) VALUES ('$session_id', '1')";
            $mainConnection->executeQuery($sql);
        }

        // Fixing date_start
        if (isset($session['date_start']) && !empty($session['date_start']) && $session['date_start'] != '0000-00-00') {
            $datetime = $session['date_start'].' 00:00:00';
            $update_sql = "UPDATE $session_table SET display_start_date = '$datetime', access_start_date = '$datetime'
                           WHERE id = $session_id";
            $mainConnection->executeQuery($update_sql);

            //Fixing nb_days_access_before_beginning
            if (!empty($session['nb_days_access_before_beginning'])) {
                $datetime = api_strtotime($datetime, 'UTC') - (86400 * $session['nb_days_access_before_beginning']);
                $datetime = api_get_utc_datetime($datetime);
                $update_sql = "UPDATE $session_table SET coach_access_start_date = '$datetime' WHERE id = $session_id";
                $mainConnection->executeQuery($update_sql);
            }
        }

        // Fixing end_date
        if (isset($session['date_end']) && !empty($session['date_end']) && $session['date_end'] != '0000-00-00') {
            $datetime = $session['date_end'].' 00:00:00';
            $update_sql = "UPDATE $session_table SET display_end_date = '$datetime', access_end_date = '$datetime'
                           WHERE id = $session_id";
            $mainConnection->executeQuery($update_sql);

            //Fixing nb_days_access_before_beginning
            if (!empty($session['nb_days_access_after_end'])) {
                $datetime = api_strtotime($datetime, 'UTC') + (86400 * $session['nb_days_access_after_end']);
                $datetime = api_get_utc_datetime($datetime);
                $update_sql = "UPDATE $session_table SET coach_access_end_date = '$datetime' WHERE id = $session_id";
                $mainConnection->executeQuery($update_sql);
            }
        }
    }

    // Fixes new changes course_rel_course
    $sql = "SELECT id, code FROM $course_table";
    $result = $mainConnection->executeQuery($sql);
    $rows = $result->fetchAll();
    foreach ($rows as $row) {
        $courseId = $row['id'];
        $courseCode = $row['code'];

        $sql = "UPDATE $courseRelUserTable SET c_id = '$courseId'
                WHERE  course_code = '$courseCode'";
        $mainConnection->executeQuery($sql);

        $sql = "UPDATE $session_rel_course_rel_user_table SET c_id = '$courseId'
                WHERE  course_code = '$courseCode'";
        $mainConnection->executeQuery($sql);

        $sql = "UPDATE $session_rel_course_table SET c_id = '$courseId'
                WHERE course_code = '$courseCode' ";
        $mainConnection->executeQuery($sql);

        $sql = "UPDATE $accessUrlRelCourseTable SET c_id = '$courseId'
                WHERE course_code = '$courseCode' ";
        $mainConnection->executeQuery($sql);

        // Check if the course is registered in the access_url_rel_course table
        $sql = "SELECT c_id FROM $accessUrlRelCourseTable WHERE c_id = $courseId";
        $result = $mainConnection->executeQuery($sql);
        if ($result->rowCount() == 0) {
            $sql = "INSERT INTO $accessUrlRelCourseTable (access_url_id, course_code, c_id) VALUES ('1', '$courseCode', '$courseId')";
            $mainConnection->executeQuery($sql);
        }
    }

    // Updating c_quiz_order
    $teq = "$dbNameForm.c_quiz";
    $sql = "SELECT c_id, session_id, id FROM $teq ORDER BY c_id, session_id, id";
    $result = $mainConnection->executeQuery($sql);
    $req = $result->fetchAll();

    $to = "$dbNameForm.c_quiz_order";
    $do = "DELETE FROM $to";
    $mainConnection->executeQuery($do);

    $cid = 0;
    $temp_session_id = 0;
    $order = 1;
    foreach ($req as $row) {
        if ($row['c_id'] != $cid) {
            $cid = $row['c_id'];
            $temp_session_id = $row['session_id'];
            $order = 1;
        } elseif ($row['session_id'] != $temp_session_id) {
            $temp_session_id = $row['session_id'];
            $order = 1;
        }
        $ins = "INSERT INTO $to (c_id, session_id, exercise_id, exercise_order)".
               " VALUES ($cid, $temp_session_id, {$row['id']}, $order)";
        $mainConnection->executeQuery($ins);
        $order++;
    }

    // Fixing special course
    $output->writeln('Fixing special course');
    $sql = "SELECT id FROM $dbNameForm.course_field WHERE field_variable = 'special_course'";
    $result = $mainConnection->executeQuery($sql);
    $fieldData = $result->fetch();
    $id = $fieldData['id'];

    $sql = "INSERT INTO $dbNameForm.course_field_options (field_id, option_value, option_display_text, option_order)
            VALUES ('$id', '1', '".'Yes'."', '1')";
    $mainConnection->executeQuery($sql);

    $sql = "INSERT INTO $dbNameForm.course_field_options (field_id, option_value, option_display_text, option_order)
            VALUES ('$id', '0', '".'No'."', '2')";
    $mainConnection->executeQuery($sql);

    //Moving social group to class
    $output->writeln('<comment>Fixing social groups.</comment>');

    $sql = "SELECT * FROM $dbNameForm.groups";
    $result = $mainConnection->executeQuery($sql);
    $groups = $result->fetchAll();

    $oldGroups = array();

    if (!empty($groups )) {
        foreach ($groups as $group) {
            $sql = "INSERT INTO $dbNameForm.usergroup (name, group_type, description, picture, url, visibility, updated_on, created_on)
                    VALUES ('{$group['name']}', '1', '{$group['description']}', '{$group['picture_uri']}', '{$group['url']}', '{$group['visibility']}', '{$group['updated_on']}', '{$group['created_on']}')";

            $mainConnection->executeQuery($sql);
            $id = $mainConnection->lastInsertId('id');
            $oldGroups[$group['id']] = $id;
        }
    }

    if (!empty($oldGroups)) {
        $output->writeln('Moving group files');

        foreach ($oldGroups as $oldId => $newId) {
            $path = GroupPortalManager::get_group_picture_path_by_id($oldId, 'system');
            if (!empty($path)) {

                $newPath = str_replace("groups/$oldId/", "groups/$newId/", $path['dir']);
                $command = "mv {$path['dir']} $newPath ";
                system($command);
                $output->writeln("Moving files: $command");
            }
        }

        $sql = "SELECT * FROM $dbNameForm.group_rel_user";
        $result = $mainConnection->executeQuery($sql);
        $dataList = $result->fetchAll();

        if (!empty($dataList)) {
            foreach ($dataList as $data) {
                if (isset($oldGroups[$data['group_id']])) {
                    $data['group_id'] = $oldGroups[$data['group_id']];
                    $sql = "INSERT INTO $dbNameForm.usergroup_rel_user (usergroup_id, user_id, relation_type)
                            VALUES ('{$data['group_id']}', '{$data['user_id']}', '{$data['relation_type']}')";
                    $mainConnection->executeQuery($sql);
                }
            }
        }

        $sql = "SELECT * FROM $dbNameForm.group_rel_group";
        $result = $mainConnection->executeQuery($sql);
        $dataList = $result->fetchAll();

        if (!empty($dataList)) {
            foreach ($dataList as $data) {
                if (isset($oldGroups[$data['group_id']]) && isset($oldGroups[$data['subgroup_id']])) {
                    $data['group_id'] = $oldGroups[$data['group_id']];
                    $data['subgroup_id'] = $oldGroups[$data['subgroup_id']];
                    $sql = "INSERT INTO $dbNameForm.usergroup_rel_usergroup (group_id, subgroup_id, relation_type)
                            VALUES ('{$data['group_id']}', '{$data['subgroup_id']}', '{$data['relation_type']}')";
                    $mainConnection->executeQuery($sql);
                }
            }
        }

        $sql = "SELECT * FROM $dbNameForm.announcement_rel_group";
        $result = $mainConnection->executeQuery($sql);
        $dataList = $result->fetchAll();

        if (!empty($dataList)) {
            foreach ($dataList as $data) {
                if (isset($oldGroups[$data['group_id']])) {
                    //Deleting relation
                    $sql = "DELETE FROM announcement_rel_group WHERE id = {$data['id']}";
                    $mainConnection->executeQuery($sql);

                    //Add new relation
                    $data['group_id'] = $oldGroups[$data['group_id']];
                    $sql = "INSERT INTO $dbNameForm.announcement_rel_group(group_id, announcement_id)
                            VALUES ('{$data['group_id']}', '{$data['announcement_id']}')";
                    $mainConnection->executeQuery($sql);
                }
            }
        }

        $sql = "SELECT * FROM $dbNameForm.group_rel_tag";
        $result = $mainConnection->executeQuery($sql);
        $dataList = $result->fetchAll();
        if (!empty($dataList)) {
            foreach ($dataList as $data) {
                if (isset($oldGroups[$data['group_id']])) {
                    $data['group_id'] = $oldGroups[$data['group_id']];
                    $sql = "INSERT INTO $dbNameForm.usergroup_rel_tag (tag_id, usergroup_id)
                            VALUES ('{$data['tag_id']}', '{$data['group_id']}')";
                    $mainConnection->executeQuery($sql);
                }
            }
        }
    }

    if (!$dryRun) {
        $mainConnection->commit();
    }
};

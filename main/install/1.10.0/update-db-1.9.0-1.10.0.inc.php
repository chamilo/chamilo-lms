<?php
/* For licensing terms, see /license.txt */

$update = function($_configuration, $mainConnection, $dryRun, $output, $app) {

    $mainConnection->beginTransaction();

    $dbNameForm = $_configuration['main_database'];

    $session_table = "$dbNameForm.session";
    $session_rel_course_table = "$dbNameForm.session_rel_course";
    $session_rel_course_rel_user_table = "$dbNameForm.session_rel_course_rel_user";
    $course_table = "$dbNameForm.course";

    //Fixes new changes in sessions
    $sql = "SELECT id, date_start, date_end, nb_days_access_before_beginning, nb_days_access_after_end FROM $session_table ";
    $result = iDatabase::query($sql);
    while ($session = Database::fetch_array($result)) {
        $session_id = $session['id'];

        //Fixing date_start
        if (isset($session['date_start']) && !empty($session['date_start']) && $session['date_start'] != '0000-00-00') {
            $datetime = $session['date_start'].' 00:00:00';
            $update_sql = "UPDATE $session_table SET display_start_date = '$datetime' , access_start_date = '$datetime' WHERE id = $session_id";
            $mainConnection->executeQuery($update_sql);

            //Fixing nb_days_access_before_beginning
            if (!empty($session['nb_days_access_before_beginning'])) {
                $datetime = api_strtotime($datetime, 'UTC') - (86400 * $session['nb_days_access_before_beginning']);
                $datetime = api_get_utc_datetime($datetime);
                $update_sql = "UPDATE $session_table SET coach_access_start_date = '$datetime' WHERE id = $session_id";
                $mainConnection->executeQuery($update_sql);
            }
        }

        //Fixing end_date
        if (isset($session['date_end']) && !empty($session['date_end']) && $session['date_end'] != '0000-00-00') {
            $datetime = $session['date_end'].' 00:00:00';
            $update_sql = "UPDATE $session_table SET display_end_date = '$datetime', access_end_date = '$datetime' WHERE id = $session_id";
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

    //Fixes new changes session_rel_course
    $sql = "SELECT id_session, sc.course_code, c.id FROM $course_table c INNER JOIN $session_rel_course_table sc ON sc.course_code = c.code";
    $result = iDatabase::query($sql);
    while ($row = Database::fetch_array($result)) {
        $sql = "UPDATE $session_rel_course_table SET course_id = {$row['id']}
                WHERE course_code = '{$row['course_code']}' AND id_session = {$row['id_session']} ";
        $mainConnection->executeQuery($sql);
    }

    //Fixes new changes in session_rel_course_rel_user
    $sql = "SELECT id_session, sc.course_code, c.id FROM $course_table c INNER JOIN $session_rel_course_rel_user_table sc ON sc.course_code = c.code";
    $result = iDatabase::query($sql);
    while ($row = Database::fetch_array($result)) {
        $sql = "UPDATE $session_rel_course_rel_user_table SET course_id = {$row['id']}
                WHERE course_code = '{$row['course_code']}' AND id_session = {$row['id_session']} ";
        $mainConnection->executeQuery($sql);
    }

    //Updating c_quiz_order
    $teq = "$dbNameForm.c_quiz";
    $seq = "SELECT c_id, session_id, id FROM $teq ORDER BY c_id, session_id, id";
    $req = iDatabase::query($seq);
    $to = "$dbNameForm.c_quiz_order";
    $do = "DELETE FROM $to";
    $mainConnection->executeQuery($do);

    $cid = 0;
    $temp_session_id = 0;
    $order = 1;
    while ($row = Database::fetch_assoc($req)) {
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

    //Fixing special course
    $output->writeln('Fixing special course');
    $sql = "SELECT id FROM $dbNameForm.course_field WHERE field_variable = 'special_course'";
    $result = Database::query($sql);
    $fieldData = Database::fetch_array($result, 'ASSOC');
    $id = $fieldData['id'];

    $sql = "INSERT INTO $dbNameForm.course_field_options (field_id, option_value, option_display_text, option_order)
            VALUES ('$id', '1', '".get_lang('Yes')."', '1')";
    Database::query($sql);

    $sql = "INSERT INTO $dbNameForm.course_field_options (field_id, option_value, option_display_text, option_order)
            VALUES ('$id', '0', '".get_lang('No')."', '2')";
    Database::query($sql);


    //Moving social group to class
    $output->writeln('Fixing social groups');

    $sql = "SELECT * FROM $dbNameForm.groups";
    $result = Database::query($sql);
    $oldGroups = array();
    if (Database::num_rows($result)) {
        while ($group = Database::fetch_array($result, 'ASSOC')) {

            $group['name'] = Database::escape_string($group['name']);
            $group['description'] = Database::escape_string($group['description']);
            $group['picture'] = Database::escape_string($group['picture_uri']);
            $group['url'] = Database::escape_string($group['url']);
            $group['visibility'] = Database::escape_string($group['visibility']);
            $group['updated_on'] = Database::escape_string($group['updated_on']);
            $group['created_on'] = Database::escape_string($group['created_on']);

            $sql = "INSERT INTO $dbNameForm.usergroup (name, group_type, description, picture, url, visibility, updated_on, created_on)
                    VALUES ('{$group['name']}', '1', '{$group['description']}', '{$group['picture_uri']}', '{$group['url']}', '{$group['visibility']}', '{$group['updated_on']}', '{$group['created_on']}')";
            //Database::query($sql);
            $mainConnection->executeQuery($sql);
            $id = $mainConnection->lastInsertId('id');
            //$id = Database::get_last_insert_id();
            $oldGroups[$group['id']] = $id;
        }
    }

    if (!empty($oldGroups)) {
        $output->writeln('Moving group files');
        foreach ($oldGroups as $oldId => $newId) {
            $path = GroupPortalManager::get_group_picture_path_by_id($oldId, 'system');
            if (!empty($path)) {
                var_dump($path['dir']);
                $newPath = str_replace("groups/$oldId/", "groups/$newId/", $path['dir']);
                $command = "mv {$path['dir']} $newPath ";
                system($command);
                $output->writeln("Moving files: $command");
            }
        }
        $sql = "SELECT * FROM $dbNameForm.group_rel_user";
        $result = Database::query($sql);

        if (Database::num_rows($result)) {
            while ($data = Database::fetch_array($result, 'ASSOC')) {
                if (isset($oldGroups[$data['group_id']])) {
                    $data['group_id'] = $oldGroups[$data['group_id']];
                    $sql = "INSERT INTO $dbNameForm.usergroup_rel_user (usergroup_id, user_id, relation_type)
                            VALUES ('{$data['group_id']}', '{$data['user_id']}', '{$data['relation_type']}')";
                    $mainConnection->executeQuery($sql);
                }
            }
        }

        $sql = "SELECT * FROM $dbNameForm.group_rel_group";
        $result = Database::query($sql);

        if (Database::num_rows($result)) {
            while ($data = Database::fetch_array($result, 'ASSOC')) {
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
        $result = Database::query($sql);

        if (Database::num_rows($result)) {
            while ($data = Database::fetch_array($result, 'ASSOC')) {
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
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            while ($data = Database::fetch_array($result, 'ASSOC')) {
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
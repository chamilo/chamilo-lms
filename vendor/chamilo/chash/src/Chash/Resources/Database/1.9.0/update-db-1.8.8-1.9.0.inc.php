<?php
/* For licensing terms, see /license.txt */

$update = function ($_configuration, $mainConnection, $courseList, $dryRun, $output, $upgrade, $removeUnusedTables) {

    $sysCoursePath = $upgrade->getCourseSysPath();
    $portalSettings = $upgrade->getPortalSettings();

    define('DB_COURSE_PREFIX', 'c_');

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

        // Checking if option "students_download_folders" exists see BT#7678&
        $output->writeln("Checking option 'students_download_folders'");
        $sql = "SELECT selected_value FROM settings_current
                WHERE variable = 'students_download_folders' ";
        $result = $mainConnection->executeQuery($sql);
        $output->writeln($sql);
        $result = $result->fetch();

        if (empty($result)) {
            $params = array(
                'variable' => 'students_download_folders',
                'subkey' => '',
                'type' => 'radio',
                'category' => 'Tools',
                'selected_value' => 'true',
                'title' => 'AllowStudentsDownloadFoldersTitle',
                'comment' => 'AllowStudentsDownloadFoldersComment',
                'scope' => '',
                'subkeytext' => '',
                'access_url_changeable' => '0'
            );
            $mainConnection->insert('settings_current', $params);

            // Adding options
            $params = array(
                'variable' => 'students_download_folders',
                'value' => 'false',
                'display_text' => 'No'
            );
            $mainConnection->insert('settings_options', $params);

            $params = array(
                'variable' => 'students_download_folders',
                'value' => 'true',
                'display_text' => 'Yes'
            );
            $mainConnection->insert('settings_options', $params);

            $output->writeln("Option 'students_download_folders' was fixed");
        }

        // Session mode

        $sql = "SELECT selected_value FROM settings_current
                WHERE variable='use_session_mode' ";
        $result = $mainConnection->executeQuery($sql);
        $output->writeln($sql);

        $result = $result->fetch();
        $session_mode  = $result['selected_value'];
        $output->writeln("<comment>Session mode: $session_mode</comment>");

        if ($session_mode == 'true') {

            $sql = "UPDATE settings_current SET selected_value = 'true'
                    WHERE variable='use_session_mode' ";
            $mainConnection->executeQuery($sql);

            $sql = "SELECT * FROM class";
            $result = $mainConnection->executeQuery($sql);
            $rows = $result->fetchAll();

            $classes_added = 0;
            $mapping_classes = array();

            if (!empty($rows)) {
                $output->writeln('Moving classes to usergroups ');
                foreach ($rows as $row) {
                    $old_id = $row['id'];
                    unset($row['id']);
                    unset($row['code']);

                    if ($dryRun) {
                        $new_user_group_id = 1;
                    } else {
                        $mainConnection->insert('usergroup', $row);
                        $new_user_group_id = $mainConnection->lastInsertId();
                    }

                    if (is_numeric($new_user_group_id)) {
                        $mapping_classes[$old_id] = $new_user_group_id;
                        $classes_added ++;
                    }
                }
                $output->writeln("Classes added: $classes_added");
            }

            $sql = "SELECT * FROM class_user";
            $result = $mainConnection->executeQuery($sql);
            $rows = $result->fetchAll();

            if (!empty($rows)) {
                $output->writeln('Moving users from class_user to usergroup_rel_user ');
                foreach ($rows as $row) {
                    if (empty($mapping_classes[$row['class_id']])) {
                        // Cover a special case where data would not be
                        // consistent - see BT#7254
                        $output->writeln("<comment>Warning: data inconsistency: \$mapping_classes[".$row['class_id']."] was not defined, suggesting that this class ID was still used in class_user although the class had been removed</comment>");
                        continue;
                    }
                    $values = array(
                        'usergroup_id' => $mapping_classes[$row['class_id']],
                        'user_id' => $row['user_id']
                    );

                    if ($dryRun) {
                        $output->writeln("<comment>Values to be saved:</comment>".implode(', ', $values));
                    } else {
                        $mainConnection->insert('usergroup_rel_user', $values);
                        $output->writeln("<comment>Saving:</comment> ".implode(', ', $values));
                    }
                }
            }

            $sql = "SELECT * FROM course_rel_class";
            $result = $mainConnection->executeQuery($sql);
            $rows = $result->fetchAll();

            if (!empty($rows)) {
                $output->writeln("Moving  course_rel_class to usergroup_rel_course");

                foreach ($rows as $row) {
                    $course_code = $row['course_code'];
                    $course_code = addslashes($course_code);
                    $sql_course = "SELECT id from course WHERE code = '$course_code'";

                    $subResult = $mainConnection->executeQuery($sql_course);
                    $courseInfo = $subResult->fetch();
                    $course_id  = $courseInfo['id'];
                    if (empty($mapping_classes[$row['class_id']])) {
                        // Cover a special case where data would not be
                        // consistent - see BT#7254
                        $output->writeln("<comment>Warning: data inconsistency: \$mapping_classes[".$row['class_id']."] was not defined, suggesting that this class ID was still used in course_rel_class although the class had been removed</comment>");
                        continue;
                    }
                    $values = array(
                        'usergroup_id' => $mapping_classes[$row['class_id']],
                        'course_id' => $course_id
                    );

                    if ($dryRun) {
                        $output->writeln("<comment>Values to be saved:</comment>".implode(', ', $values));
                    } else {
                        $mainConnection->insert('usergroup_rel_course', $values);
                        $output->writeln("<comment>Saving:</comment> ".implode(', ', $values));
                    }
                }
            }
        }

        // Moving Stats DB to the main DB.

        $statsTable = array(
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
            "track_stored_values",
            "track_stored_values_stack",
        );

        if (isset($_configuration['statistics_database'])) {
            $statSchemaManager = $statsConnection->getSchemaManager();
            if ($_configuration['main_database'] != $_configuration['statistics_database']) {
                foreach ($statsTable as $table) {
                    if ($statSchemaManager->tablesExist($table)) {
                        $newTable = $_configuration['main_database'].'.'.$table;
                        $statSchemaManager->renameTable($table, $newTable);
                        $output->writeln("<comment>Renaming  $table to: </comment>".$newTable);
                    }
                }
            }
        }

        // Moving user database to the main database.
        $usersTables = array(
            "personal_agenda",
            "personal_agenda_repeat",
            "personal_agenda_repeat_not",
            "user_course_category"
        );

        $userSchemaManager = $userConnection->getSchemaManager();
        if (isset($_configuration['user_personal_database'])) {
            if ($_configuration['main_database'] != $_configuration['user_personal_database']) {
                foreach ($usersTables as $table) {
                    if ($userSchemaManager->tablesExist($table)) {
                        $newTable = $_configuration['main_database'].'.'.$table;
                        $userSchemaManager->renameTable($table, $newTable);
                        $output->writeln("<comment>Renaming  $table to: </comment>".$newTable);
                    }
                }
            }
        }

        // Adding admin user in the access_url_rel_user table.
        $sql = "SELECT user_id FROM admin WHERE user_id = 1";
        $result = $mainConnection->executeQuery($sql);
        $row = $result->fetch();
        $has_user_id = !empty($row);

        $sql = "SELECT * FROM access_url_rel_user
                WHERE user_id = 1 AND access_url_id = 1";
        $result = $mainConnection->executeQuery($sql);
        $row = $result->fetch();
        $has_entry = !empty($row);

        if ($has_user_id && !$has_entry) {
            $sql = "INSERT INTO access_url_rel_user VALUES(1, 1)";
            $mainConnection->executeQuery($sql);
            $output->writeln($sql);
        }

        $upgrade->createCourseTables($output, $dryRun);

        if (!empty($courseList)) {

            $output->writeln("<comment>Moving old course tables to the new structure 1: single database</comment>");

            $progress = $upgrade->getHelperSet()->get('progress');
            $progress->start($output, count($courseList));

            foreach ($courseList as $row_course) {

                $prefix = $upgrade->getTablePrefix($_configuration, $row_course['db_name']);

                // Course tables to be migrated.
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

                $output->writeln('');
                $output->writeln('Course DB: '.$row_course['db_name']);

                $courseConnection = null;
                foreach ($courseDatabaseConnectionList as $database) {
                    if ($database['database'] == '_chamilo_course_'.$row_course['db_name']) {
                        /** @var \Doctrine\DBAL\Connection $courseConnection */
                        $courseConnection = $upgrade->getHelper($database['database'])->getConnection();
                    }
                }

                if (empty($courseConnection)) {
                    $output->writeln("<info> We can't established a DB connection for this course: ".$row_course['db_name']);
                }

                foreach ($table_list as $table) {
                    $old_table = $prefix.$table;

                    $course_id = $row_course['id'];
                    $newTable = DB_COURSE_PREFIX.$table;

                    $sm = $courseConnection->getSchemaManager();
                    $tableExists = $sm->tablesExist($old_table);

                    if ($tableExists) {
                        $sql = "SELECT count(*) as count FROM $old_table";
                        $result = $courseConnection->executeQuery($sql);

                        $oldCount = 0;
                        if ($result) {
                            $row = $result->fetch();
                            $oldCount = $row['count'];
                        } else {
                            $output->writeln("Count(*) in table $old_table failed");
                        }

                        if ($oldCount > 0) {

                            $sql = "SELECT * FROM $old_table";
                            $result = $courseConnection->executeQuery($sql);
                            $rows = $result->fetchAll();

                            $count = 0;
                            foreach ($rows as $row) {
                                $row['c_id'] = $course_id;

                                if ($dryRun) {
                                    $id = 1;
                                } else {

                                    // Fixing data
                                    switch ($table) {
                                        case 'forum_notification':
                                            // Bug found in 1.8.7.1
                                            if (empty($row['thread_id'])) {
                                                $row['thread_id'] = 0;
                                            }
                                            if (empty($row['post_id'])) {
                                                $row['post_id'] = 0;
                                            }
                                            if (empty($row['forum_id'])) {
                                                $row['forum_id'] = 0;
                                            }
                                            break;
                                        case 'wiki_conf':
                                            /*
                                                Bug in wiki_conf check if there's already a key courseId/pageId
                                                if so then just skip inserting the same page_ids.
                                            */
                                            $pageId = $row['page_id'];
                                            $sql = "SELECT COUNT(page_id) as count FROM $newTable
                                                    WHERE page_id = $pageId AND c_id = $course_id";
                                            $result = $mainConnection->executeQuery($sql);
                                            $rowResult = $result->fetch();
                                            if ($rowResult['count'] >= 1) {
                                                $output->writeln("Skipping content of the c_wiki_conf:");
                                                $output->writeln(print_r($row, 1));
                                                continue 2;
                                            }
                                            break;
                                    }
                                    $mainConnection->insert($newTable, $row);
                                    $id = $mainConnection->lastInsertId();
                                }

                                if (is_numeric($id)) {
                                    $count++;
                                } else {
                                    $errors[$old_table][] = $row;
                                }
                            }

                            if ($dryRun) {
                                // $output->writeln("$count/$oldCount rows to be inserted in $newTable");
                            } else {
                                //$output->writeln("$count/$oldCount rows inserted in $newTable");
                            }

                            if ($oldCount != $count) {
                                $output->writeln("<error>Count of new and old table doesn't match: $oldCount - $newTable</error>");
                            }
                        }
                    } else {
                        $output->writeln("<comment>Seems that the table $old_table doesn't exist.</comment>");
                    }
                }
                $progress->advance();
            }
            $progress->finish();
            $output->writeln("<comment>End course migration.</comment>");

            // Drop prefix tables
            if ($removeUnusedTables && $dryRun == false) {
                $output->writeln("<comment>Removing unused tables:</comment>");

                $onlyPrefix = $upgrade->getTablePrefix($_configuration);
                if (!empty($onlyPrefix)) {
                    $sql = "SHOW TABLES LIKE '".$onlyPrefix."%'";

                    $result = $courseConnection->executeQuery($sql);
                    while ($row = $result->fetch()) {
                        $table = current($row);
                        if (!empty($table)) {
                            $sql = "DROP TABLE $table";
                            $output->writeln("<comment>$sql</comment>");
                            $courseConnection->executeQuery($sql);
                        }
                    }
                }
            }

            /* Start work fix */
            $output->writeln("<comment>Starting work fix:</comment>");

            /* Fixes the work subfolder and work with no parent issues */

            $work_table = "c_student_publication";
            $item_table = "c_item_property";

            $today = time();
            $user_id = 1;

            if ($dryRun == false) {

                foreach ($courseList as $course) {
                    $courseId = $course['id']; //int id

                    //1. Searching for works with no parents
                    $sql = "SELECT * FROM $work_table
                            WHERE parent_id = 0 AND filetype ='file' AND c_id = $courseId ";
                    $result = $mainConnection->executeQuery($sql);
                    $work_list = $result->fetchAll();

                    $course_dir = $sysCoursePath.'/'.$course['directory'];
                    $base_work_dir = $course_dir.'/work';

                    $output->writeln("<comment>Using 'base_work_dir': $base_work_dir.</comment>");

                    //2. Looping if there are works with no parents
                    if (!empty($work_list)) {
                        $work_dir_created = array();

                        foreach ($work_list as $work) {
                            $session_id = intval($work['session_id']);
                            $group_id   = intval($work['post_group_id']);
                            $work_key   = $session_id.$group_id;

                            $dir_name = "default_tasks_".$group_id."_".$session_id;

                            // Only create the folder once
                            if (!isset($work_dir_created[$work_key])) {
                                // 2.1 Creating a new work folder:
                                $sql = "INSERT INTO $work_table SET
                                        c_id                = '$courseId',
                                        url         		= 'work/".$dir_name."',
                                        title               = 'Tasks',
                                        description 		= '',
                                        author      		= '',
                                        active              = '1',
                                        accepted			= '1',
                                        filetype            = 'folder',
                                        post_group_id       = '$group_id',
                                        sent_date           = '".$today."',
                                        parent_id           = '0',
                                        qualificator_id     = '',
                                        user_id 			= '".$user_id."'";
                                $mainConnection->executeQuery($sql);
                                $id  = $mainConnection->lastInsertId();

                                //2.2 Adding the folder in item property
                                if ($id) {
                                    $sql = "INSERT INTO $item_table (c_id, tool, ref, insert_date, insert_user_id, lastedit_date, lastedit_type, lastedit_user_id, to_group_id, visibility, id_session)
                                            VALUES ('$courseId', 'work','$id','$today', '$user_id', '$today', 'DirectoryCreated','$user_id', '$group_id', '1', '$session_id')";

                                    $mainConnection->executeQuery($sql);
                                    $work_dir_created[$work_key] = $id;
                                    create_unexisting_work_directory($base_work_dir, $dir_name, $portalSettings);
                                    $final_dir = $base_work_dir.'/'.$dir_name;
                                }
                            } else {
                                $final_dir = $base_work_dir.'/'.$dir_name;
                            }

                            // 2.3 Updating the url
                            if (!empty($work_dir_created[$work_key])) {
                                $parent_id = $work_dir_created[$work_key];
                                $new_url = "work/".$dir_name.'/'.basename($work['url']);
                                $sql = "UPDATE $work_table SET
                                            url = '$new_url',
                                            parent_id = $parent_id,
                                            contains_file = '1'
                                        WHERE id = {$work['id']} AND c_id = $courseId";
                                $mainConnection->executeQuery($sql);
                                if (is_dir($final_dir)) {
                                    rename($course_dir.'/'.$work['url'], $course_dir.'/'.$new_url);
                                }
                            }
                        }
                    }

                    // 3.0 Moving subfolders to the root.
                    $sql = "SELECT * FROM $work_table
                               WHERE parent_id <> 0 AND filetype ='folder' AND c_id = $courseId";
                    $result = $mainConnection->executeQuery($sql);
                    $work_list = $result->fetchAll();

                    if (!empty($work_list)) {
                        foreach ($work_list as $work_folder) {
                            $folder_id = $work_folder['id'];
                            check_work(
                                $mainConnection,
                                $folder_id,
                                $work_folder['url'],
                                $work_table,
                                $base_work_dir,
                                $courseId
                            );
                        }
                    }
                }
            }
            $output->writeln("<comment>End work fix</comment>");
            if ($dryRun) {
                $output->writeln('<info>Queries were not executed. Because dry-run is on<info>');

            } else {
                $mainConnection->commit();
            }
        }
    } catch (Exception $e) {
        $mainConnection->rollback();
        throw $e;
    }
};

/**
 * @param $mainConnection
 * @param $folder_id
 * @param $work_url
 * @param $work_table
 * @param $base_work_dir
 * @param $courseId
 */
function check_work($mainConnection, $folder_id, $work_url, $work_table, $base_work_dir, $courseId)
{
    $uniq_id = uniqid();
    // Looking for subfolders
    $sql 	= "SELECT * FROM $work_table WHERE parent_id = $folder_id AND filetype ='folder' AND c_id = $courseId";
    $result = $mainConnection->executeQuery($sql);
    $rows = $result->fetchAll();

    if (!empty($rows)) {
        foreach ($rows as $row) {
            check_work($mainConnection, $row['id'], $row['url'], $work_table, $base_work_dir, $courseId);
        }
    }

    // Moving the subfolder in the root.
    $new_url = '/'.basename($work_url).'_mv_'.$uniq_id;
    $new_url = Database::escape_string($new_url);
    $sql = "UPDATE $work_table SET url = '$new_url', parent_id = 0 WHERE id = $folder_id AND c_id = $courseId";
    $mainConnection->executeQuery($sql);

    if (is_dir($base_work_dir.$work_url)) {
        rename($base_work_dir.$work_url, $base_work_dir.$new_url);

        //Rename all files inside the folder
        $sql 	= "SELECT * FROM $work_table WHERE parent_id = $folder_id AND filetype ='file' AND c_id = $courseId";
        $result = $mainConnection->executeQuery($sql);
        $rows = $result->fetchAll();

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $new_url = "work".$new_url.'/'.basename($row['url']);
                $sql = "UPDATE $work_table
                        SET url = '$new_url', parent_id = $folder_id, contains_file = '1'
                        WHERE id = {$row['id']} AND c_id = $courseId";
                $mainConnection->executeQuery($sql);
            }
        }
    }
}

/**
 * @param string $base_work_dir
 * @param string $desired_dir_name
 * @param array $portalSettings
 * @return bool|string
 */
function create_unexisting_work_directory($base_work_dir, $desired_dir_name, $portalSettings)
{
    $nb = '';
    $base_work_dir = (substr($base_work_dir, -1, 1) == '/' ? $base_work_dir : $base_work_dir.'/');
    while (file_exists($base_work_dir.$desired_dir_name.$nb)) {
        $nb += 1;
    }
    if (mkdir($base_work_dir.$desired_dir_name.$nb, $portalSettings['permissions_for_new_directories'])) {
        return $desired_dir_name.$nb;
    } else {
        return false;
    }
}

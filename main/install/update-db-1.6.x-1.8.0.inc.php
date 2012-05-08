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

Log::notice("Starting " . basename(__FILE__));
// Check if we come from index.php or update_courses.php - otherwise display error msg
if (defined('SYSTEM_INSTALLATION')) {

    // Check if the current Dokeos install is eligible for update
    if (empty($updateFromConfigFile) || !file_exists($_POST['updatePath'] . $updateFromConfigFile) || !in_array(get_config_param('clarolineVersion'), $update_from_version_6)) {

        echo '<strong>' . get_lang('Error') . ' !</strong> Dokeos ' . implode('|', $updateFromVersion) . ' ' . get_lang('HasNotBeenFound') . '.<br /><br />
								' . get_lang('PleasGoBackToStep1') . '.
							    <p><button type="submit" class="back" name="step1" value="&lt; ' . get_lang('Back') . '">' . get_lang('Back') . '</button></p>
							    </td></tr></table></form></body></html>';
        exit();
    }

    $_configuration['db_glue'] = get_config_param('dbGlu');

    if ($singleDbForm) {
        $_configuration['table_prefix'] = get_config_param('courseTablePrefix');
        $_configuration['main_database'] = get_config_param('mainDbName');
        $_configuration['db_prefix'] = get_config_param('dbNamePrefix');
    }

    $dbScormForm = preg_replace('/[^a-zA-Z0-9_\-]/', '', $dbScormForm);

    if (!empty($dbPrefixForm) && strpos($dbScormForm, $dbPrefixForm) !== 0) {
        $dbScormForm = $dbPrefixForm . $dbScormForm;
    }

    if (empty($dbScormForm) || $dbScormForm == 'mysql' || $dbScormForm == $dbPrefixForm) {
        $dbScormForm = $dbPrefixForm . 'scorm';
    }

    /* 	Normal upgrade procedure: start by updating main, statistic, user databases */

    // If this script has been included by index.php, not update_courses.php, so
    // that we want to change the main databases as well...
    $only_test = false;
    $log = 0;

    if (defined('SYSTEM_INSTALLATION')) {

        if ($singleDbForm) {
            if (empty($dbStatsForm))
                $dbStatsForm = $dbNameForm;
            if (empty($dbScormForm))
                $dbScormForm = $dbNameForm;
            if (empty($dbUserForm))
                $dbUserForm = $dbNameForm;
        }
        /**
         * Update the databases "pre" migration
         */
        include '../lang/english/create_course.inc.php';

        if ($languageForm != 'english') {
            // languageForm has been escaped in index.php
            include '../lang/' . $languageForm . '/create_course.inc.php';
        }

        //get the main queries list (m_q_list)
        $m_q_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-pre.sql', 'main');
        if (count($m_q_list) > 0) {
            // Now use the $m_q_list
            /**
             * We connect to the right DB first to make sure we can use the queries
             * without a database name
             */
            if (strlen($dbNameForm) > 40) {
                error_log('Database name ' . $dbNameForm . ' is too long, skipping', 0);
            } elseif (!in_array($dbNameForm, $dblist)) {
                error_log('Database ' . $dbNameForm . ' was not found, skipping', 0);
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
        $s_q_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-pre.sql', 'stats');
        if (count($s_q_list) > 0) {
            // Now use the $s_q_list
            /**
             * We connect to the right DB first to make sure we can use the queries
             * without a database name
             */
            if (strlen($dbStatsForm) > 40) {
                error_log('Database name ' . $dbStatsForm . ' is too long, skipping', 0);
            } elseif (!in_array($dbStatsForm, $dblist)) {
                error_log('Database ' . $dbStatsForm . ' was not found, skipping', 0);
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
        $u_q_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-pre.sql', 'user');
        if (count($u_q_list) > 0) {
            // Now use the $u_q_list
            /**
             * We connect to the right DB first to make sure we can use the queries
             * without a database name
             */
            if (strlen($dbUserForm) > 40) {
                error_log('Database name ' . $dbUserForm . ' is too long, skipping', 0);
            } elseif (!in_array($dbUserForm, $dblist)) {
                error_log('Database ' . $dbUserForm . ' was not found, skipping', 0);
            } else {
                Database::select_db($dbUserForm);
                foreach ($u_q_list as $query) {
                    if ($only_test) {
                        error_log("Database::query($dbUserForm,$query)", 0);
                        error_log("In $dbUserForm, executed: $query", 0);
                    } else {
                        $res = Database::query($query);
                    }
                }
            }
        }
        // The SCORM database doesn't need a change in the pre-migrate part - ignore.
    }

    $prefix = '';
    if ($singleDbForm) {
        $prefix = $_configuration['table_prefix'];
    }

    // Get the courses databases queries list (c_q_list)
    $c_q_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-pre.sql', 'course');
    if (count($c_q_list) > 0) {

        // Get the courses list
        if (strlen($dbNameForm) > 40) {
            error_log('Database name ' . $dbNameForm . ' is too long, skipping', 0);
        } elseif (!in_array($dbNameForm, $dblist)) {
            error_log('Database ' . $dbNameForm . ' was not found, skipping', 0);
        } else {
            Database::select_db($dbNameForm);
            $res = Database::query("SELECT code,db_name,directory,course_language FROM course WHERE target_course_code IS NULL");
            if ($res === false) {
                die('Error while querying the courses list in update_db-1.6.x-1.8.0.inc.php');
            }
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
                            error_log("Database::query(" . $row_course['db_name'] . ",$query)", 0);
                        } else {
                            $res = Database::query($query);
                            if ($log) {
                                error_log("In " . $row_course['db_name'] . ", executed: $query", 0);
                            }
                        }
                    }

                    // Prepare reusable users list to avoid repetition of the SQL query, but only select
                    // users from the current course to avoid blowing the memory limit
                    $users_list = array();
                    $sql_uc = "SELECT u.user_id as ui, u.firstname as fn, u.lastname as ln " .
                        " FROM $dbNameForm.user u, $dbNameForm.course_rel_user cu " .
                        " WHERE cu.course_code = '" . $row_course['code'] . "' " .
                        " AND u.user_id = cu.user_id";
                    $res_uc = Database::query($sql_uc);
                    while ($user_row = Database::fetch_array($res_uc)) {
                        $users_list[$user_row['fn'] . ' ' . $user_row['ln']] = $user_row['ui'];
                    }

                    // Update course manually
                    // Update group_category.forum_state ?
                    // Update group_info.tutor_id (put it in group_tutor table?) ?
                    // Update group_info.forum_state, forum_id ?
                    // Update forum tables (migrate from bb_ tables to forum_ tables)
                    // Migrate categories
                    $prefix_course = $prefix;
                    if ($singleDbForm) {
                        $prefix_course = $prefix . $row_course['db_name'] . "_";
                    }

                    $sql_orig = "SELECT * FROM " . $prefix_course . "bb_categories";
                    $res_orig = Database::query($sql_orig);
                    $order = 1;
                    while ($row = Database::fetch_array($res_orig)) {
                        $myorder = (empty($row['cat_order']) ? $order : $row['cat_order']);
                        $sql = "INSERT INTO " . $prefix_course . "forum_category " .
                            "(cat_id,cat_title,cat_comment,cat_order,locked) VALUES " .
                            "('" . $row['cat_id'] . "','" . Database::escape_string($row['cat_title']) . "','','" . $myorder . "',0)";
                        $res = Database::query($sql);
                        $lastcatid = Database::insert_id();
                        //error_log($sql,0);
                        $order++;
                        // Add item_property - forum categories were not put into item_properties before
                        $sql = "INSERT INTO " . $prefix_course . "item_property (tool,insert_user_id,ref,lastedit_type,lastedit_user_id,visibility) " .
                            "VALUES ('forum_category','1','$lastcatid','ForumCategoryAdded','1','1')";
                        $res = Database::query($sql);
                        //error_log($sql,0);
                    }

                    $sql_orig = "SELECT * FROM " . $prefix_course . "bb_forums ORDER BY forum_last_post_id desc";
                    $res_orig = Database::query($sql_orig);
                    $order = 1;
                    while ($row = Database::fetch_array($res_orig)) {
                        $sql = "INSERT INTO " . $prefix_course . "forum_forum " .
                            "(forum_id,forum_category,allow_edit,forum_comment," .
                            "forum_title," .
                            "forum_last_post, forum_threads," .
                            "locked, forum_posts, " .
                            "allow_new_threads, forum_order) VALUES " .
                            "('" . $row['forum_id'] . "','" . $row['cat_id'] . "',1,'" . Database::escape_string($row['forum_desc']) . "'," .
                            "'" . Database::escape_string($row['forum_name']) . "'," .
                            "'" . $row['forum_last_post_id'] . "','" . $row['forum_topics'] . "'," .
                            "0,'" . $row['forum_posts'] . "'," .
                            "1,$order)";
                        //error_log($sql,0);
                        $res = Database::query($sql);
                        $lastforumid = Database::insert_id();
                        $order++;

                        // Add item_property - forums were not put into item_properties before
                        $sql = "INSERT INTO " . $prefix_course . "item_property (tool,insert_user_id,ref,lastedit_type,lastedit_user_id,visibility) " .
                            "VALUES ('forum','1','$lastforumid','ForumAdded','1','1')";
                        $res = Database::query($sql);
                        //error_log($sql,0);
                    }

                    $sql_orig = "SELECT * FROM " . $prefix_course . "bb_topics";
                    $res_orig = Database::query($sql_orig);
                    while ($row = Database::fetch_array($res_orig)) {
                        $name = $row['prenom'] . ' ' . $row['nom'];
                        // Check whether user id is reusable
                        if ($row['topic_poster'] <= 1) {
                            if (isset($users_list[$name])) {
                                $poster_id = $users_list[$name];
                            } else {
                                $poster_id = $row['topic_poster'];
                            }
                        }
                        // Convert time from varchar to datetime
                        $time = $row['topic_time'];
                        $name = Database::escape_string($name);
                        $sql = "INSERT INTO " . $prefix_course . "forum_thread " .
                            "(thread_id,forum_id,thread_poster_id," .
                            "locked,thread_replies,thread_sticky,thread_title," .
                            "thread_poster_name, thread_date, thread_last_post," .
                            "thread_views) VALUES " .
                            "('" . $row['topic_id'] . "','" . $row['forum_id'] . "','" . $poster_id . "'," .
                            "0,'" . $row['topic_replies'] . "',0,'" . Database::escape_string($row['topic_title']) . "'," .
                            "'$name','$time','" . $row['topic_last_post_id'] . "'," .
                            "'" . $row['topic_views'] . "')";
                        //error_log($sql,0);
                        $res = Database::query($sql);
                        $lastthreadid = Database::insert_id();

                        // Add item_property - forum threads were not put into item_properties before
                        $sql = "INSERT INTO " . $prefix_course . "item_property (tool,insert_user_id,ref,lastedit_type,lastedit_user_id,visibility) " .
                            "VALUES ('forum_thread','1','$lastthreadid','ForumThreadAdded','1','1')";
                        $res = Database::query($sql);
                        //error_log($sql,0);
                    }

                    $sql_orig = "SELECT * FROM " . $prefix_course . "bb_posts bp, " . $prefix_course . "bb_posts_text bpt WHERE bp.post_id = bpt.post_id";
                    $res_orig = Database::query($sql_orig);
                    while ($row = Database::fetch_array($res_orig)) {
                        $name = $row['prenom'] . ' ' . $row['nom'];
                        // Check whether user id is reusable
                        if ($row['poster_id'] <= 0) {
                            if (isset($users_list[$name])) {
                                $poster_id = $users_list[$name];
                            } else {
                                $poster_id = $row['poster_id'];
                            }
                        }
                        // Convert time from varchar to datetime
                        $time = $row['post_time'];
                        $name = Database::escape_string($name);
                        $sql = "INSERT INTO " . $prefix_course . "forum_post " .
                            "(post_id,forum_id,thread_id," .
                            "poster_id,post_parent_id,visible, " .
                            "post_title,poster_name, post_text, " .
                            "post_date, post_notification) VALUES " .
                            "('" . $row['post_id'] . "','" . $row['forum_id'] . "','" . $row['topic_id'] . "'," .
                            "'" . $poster_id . "','" . $row['parent_id'] . "',1," .
                            "'" . Database::escape_string($row['post_title']) . "','$name', '" . Database::escape_string($row['post_text']) . "'," .
                            "'$time',0)";
                        //error_log($sql,0);
                        $res = Database::query($sql);
                        $lastpostid = Database::insert_id();

                        // Add item_property - forum threads were not put into item_properties before
                        $sql = "INSERT INTO " . $prefix_course . "item_property(tool,insert_user_id,ref,lastedit_type,lastedit_user_id,visibility) " .
                            "VALUES ('forum_post','1','$lastpostid','ForumPostAdded','1','1')";
                        $res = Database::query($sql);
                        //error_log($sql,0);
                    }
                    unset($users_list);

                    $sql_orig = "SELECT id, tutor_id FROM " . $prefix_course . "group_info";
                    $res_orig = Database::query($sql_orig);
                    $order = 1;
                    while ($row = Database::fetch_array($res_orig)) {
                        $sql = "INSERT INTO " . $prefix_course . "group_rel_tutor " .
                            "(user_id,group_id) VALUES " .
                            "('" . $row['tutor_id'] . "','" . $row['id'] . "')";
                        $res = Database::query($sql);
                    }
                }
            }
        }
    }

    // Load the old-scorm to new-scorm migration script
    if (!$only_test) {
        include('update-db-scorm-1.6.x-1.8.0.inc.php');
    }
    if (defined('SYSTEM_INSTALLATION')) {
        if ($singleDbForm) {
            if (empty($dbStatsForm))
                $dbStatsForm = $dbNameForm;
            if (empty($dbScormForm))
                $dbScormForm = $dbNameForm;
            if (empty($dbUserForm))
                $dbUserForm = $dbNameForm;
        }
        // Deal with migrate-db-1.6.x-1.8.0-post.sql
        // Get the main queries list (m_q_list)
        $m_q_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-post.sql', 'main');
        if (count($m_q_list) > 0) {
            // Now use the $m_q_list
            /**
             * We connect to the right DB first to make sure we can use the queries
             * without a database name
             */
            if (strlen($dbNameForm) > 40) {
                error_log('Database name ' . $dbNameForm . ' is too long, skipping', 0);
            } elseif (!in_array($dbNameForm, $dblist)) {
                error_log('Database ' . $dbNameForm . ' was not found, skipping', 0);
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
        $s_q_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-post.sql', 'stats');
        if (count($s_q_list) > 0) {
            // Now use the $s_q_list
            /**
             * We connect to the right DB first to make sure we can use the queries
             * without a database name
             */
            if (strlen($dbStatsForm) > 40) {
                error_log('Database name ' . $dbStatsForm . ' is too long, skipping', 0);
            } elseif (!in_array($dbNameForm, $dblist)) {
                error_log('Database ' . $dbNameForm . ' was not found, skipping', 0);
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
        $u_q_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-post.sql', 'user');
        if (count($u_q_list) > 0) {
            //now use the $u_q_list
            /**
             * We connect to the right DB first to make sure we can use the queries
             * without a database name
             */
            if (strlen($dbUserForm) > 40) {
                error_log('Database name ' . $dbUserForm . ' is too long, skipping', 0);
            } elseif (!in_array($dbUserForm, $dblist)) {
                error_log('Database ' . $dbUserForm . ' was not found, skipping', 0);
            } else {
                Database::select_db($dbUserForm);
                foreach ($u_q_list as $query) {
                    if ($only_test) {
                        error_log("Database::query($dbUserForm,$query)", 0);
                    } else {
                        $res = Database::query($query);
                        if ($log) {
                            error_log("In $dbUserForm, executed: $query", 0);
                        }
                    }
                }
            }
        }
        // The SCORM database should need a drop in the post-migrate part. However, we will keep these tables a bit more, just in case...
    }

    // Get the courses databases queries list (c_q_list)
    $c_q_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-post.sql', 'course');
    if (count($c_q_list) > 0) {
        // Get the courses list
        if (strlen($dbNameForm) > 40) {
            error_log('Database name ' . $dbNameForm . ' is too long, skipping', 0);
        } elseif (!in_array($dbNameForm, $dblist)) {
            error_log('Database ' . $dbNameForm . ' was not found, skipping', 0);
        } else {
            Database::select_db($dbNameForm);
            $res = Database::query("SELECT code,db_name,directory,course_language FROM course WHERE target_course_code IS NULL");
            if ($res === false) {
                die('Error while querying the courses list in update_db-1.6.x-1.8.0.inc.php');
            }
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
                        $prefix_course = $prefix . $row['db_name'] . "_";
                    } else {
                        Database::select_db($row['db_name']);
                    }

                    foreach ($c_q_list as $query) {
                        if ($singleDbForm) { //otherwise just use the main one
                            $query = preg_replace('/^(UPDATE|ALTER TABLE|CREATE TABLE|DROP TABLE|INSERT INTO|DELETE FROM)\s+(\w*)(.*)$/', "$1 $prefix$2$3", $query);
                        }
                        if ($only_test) {
                            error_log("Database::query(" . $row['db_name'] . ",$query)", 0);
                        } else {
                            $res = Database::query($query);
                            if ($log) {
                                error_log("In " . $row['db_name'] . ", executed: $query", 0);
                            }
                        }
                    }
                }
            }
        }
    }

    // Upgrade user categories sort
    $table_user_categories = $dbUserForm . '.user_course_category';

    $sql = 'SELECT * FROM ' . $table_user_categories . ' ORDER BY user_id, title';
    $rs = Database::query($sql);

    $sort = 0;
    $old_user = 0;
    while ($cat = Database::fetch_array($rs)) {
        if ($old_user != $cat['user_id']) {
            $old_user = $cat['user_id'];
            $sort = 0;
        }
        $sort++;
        $sql = 'UPDATE ' . $table_user_categories . ' SET
	            sort = ' . intval($sort) . '
	            WHERE id=' . intval($cat['id']);
        Database::query($sql);
    }
} else {

    echo 'You are not allowed here !';
}

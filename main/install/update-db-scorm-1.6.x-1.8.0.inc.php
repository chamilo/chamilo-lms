<?php
/* For licensing terms, see /license.txt */

/**
 * Chamilo LMS
 * Script handling the migration between an old Dokeos platform (<1.8.0) to
 * setup the new database system (4 scorm tables inside the course's database)
 * @package chamilo.scorm
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

/**
 * Include mandatory libraries
 */
Log::notice("Starting " . basename(__FILE__));

require_once api_get_path(LIBRARY_PATH).'document.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php'; //check_name_exists()
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/scorm.class.php';

$loglevel = 0;

// Get table prefix from $prefix variable declared in update-db-....inc.php
$table_prefix = $prefix;
$sys_course_path = $pathForm.'courses/';
$upd_course_path = $proposedUpdatePath.'courses/';

function my_get_time($time) {
    $matches = array();
    if (preg_match('/(\d{1,4}):(\d{2})(:(\d{2})(\.\d*)?)?/', $time, $matches)) {
        if (count($matches) == 3) {
            return ($matches[1] * 60) + ($matches[2]);
        } else {
            return ($matches[1] * 3600) + ($matches[2] * 60) + ($matches[4]);
        }
    }
    else return 0;
}

// Open log file
$fh = fopen(api_get_path(SYS_ARCHIVE_PATH).'newscorm_'.time().'.log', 'w');
$fh_revert = fopen(api_get_path(SYS_ARCHIVE_PATH).'newscorm_'.time().'_revert.log', 'w');
$fh_res = fopen(api_get_path(SYS_ARCHIVE_PATH).'newscorm_'.time().'_res.log', 'w');
fwrite($fh, "-- Recording course homepages links changes to enable reverting\n");
fwrite($fh_revert, "-- Recording reverted course homepages links changes to enable reverting\n");
fwrite($fh_res, "-- Recording resulting course homepages links changes\n");

//echo "<html><body>";

/**
 * New tables definition:
 */
$new_lp = 'lp';
$new_lp_view = 'lp_view';
$new_lp_item = 'lp_item';
$new_lp_item_view = 'lp_item_view';
$new_lp_type = 'lp_type';

$max_dsp_lp = 0;
$courses_list = array();
$courses_id_list = array();
$courses_id_full_table_prefix_list = array();
$courses_dir_list = array();

Database::select_db($dbNameForm);

$sql = "SELECT * FROM course";
$res = Database::query($sql);

while ($row = Database::fetch_array($res)) {
    $course_pref = $table_prefix;
    if ($singleDbForm) {
        $dbname = $_configuration['main_database'].'.'.$course_pref.$row['db_name'].'_';
    } else {
        $dbname = $row['db_name'].'.'.$course_pref;
    }
    $courses_list[] = $row['db_name'];
    $courses_id_list[$row['code']] = $row['db_name'];
    $courses_id_full_table_prefix_list[$row['code']] = $dbname;
    $courses_dir_list[$row['code']] = $row['directory'];
}

if ($loglevel > 0) { error_log("Tables created/deleted for all courses", 0); }

/**
 * The migration needs to take all data from the original learnpath tables and add them to the new
 * lp, lp_view, lp_item and lp_item_view tables
 */
// MIGRATING LEARNPATHS
// Test only one course
foreach ($courses_id_full_table_prefix_list as $course_code => $db) {
    if (strlen($courses_id_list[$course_code]) > 40) {
        error_log('Database '.$courses_id_list[$course_code].' is too long, skipping', 0);
        continue;
    }

    $incoherences = 0;
    if ($loglevel > 0) { error_log("Now starting migration of learnpath tables from $db database...", 0); }
    $lp_doc = $db.TABLE_DOCUMENT;
    $lp_main = $db.TABLE_LEARNPATH_MAIN;
    $lp_ids = array();
    $lp_user = $db.TABLE_LEARNPATH_USER;
    $lp_users = array();
    $lp_chap = $db.TABLE_LEARNPATH_CHAPTER;
    $parent_chaps = array();
    $lp_chap_items = array();
    $ordered_chaps = array();
    $lp_item = $db.TABLE_LEARNPATH_ITEM;
    $lp_items = array();
    $lp_ordered_items = array();
    $parent_lps = array(); //keeps a track of chapter's learnpath ids
    $my_new_lp = $db.$new_lp;
    $my_new_lp_item = $db.$new_lp_item;
    $my_new_lp_view = $db.$new_lp_view;
    $my_new_lp_item_view = $db.$new_lp_item_view;

    // Migrate learnpaths
    $sql_test = "SELECT * FROM $my_new_lp";
    $res_test = Database::query($sql_test);
    $sql_lp = "SELECT * FROM $lp_main";
    if ($loglevel > 1) { error_log("$sql_lp", 0); }
    $res_lp = Database::query($sql_lp);
    if (!$res_lp or !$res_test) {
        if ($loglevel > 1) {
            error_log("+++Problem querying DB $lp_main+++ skipping (".Database::error().")", 0);
            if (!$res_test) {
                error_log("This might be due to no existing table in the destination course", 0);
            }
        }
        continue;
    }
    $dsp_ord = 1;
    while ($row = Database::fetch_array($res_lp)) {
        //echo "Treating lp id : ".$row['learnpath_id']."<br />\n";
        $ins_lp_sql = "INSERT INTO $my_new_lp (lp_type,name,description,display_order,content_maker) " .
                "VALUES (1," .
                        "'".Database::escape_string($row['learnpath_name'])."'," .
                        "'".Database::escape_string($row['learnpath_description'])."',$dsp_ord,'Dokeos')";
        $ins_lp_res = Database::query($ins_lp_sql);
        $in_id = Database::insert_id();
        if (!$in_id) die('Could not insert lp: '.$ins_lp_sql);
        $lp_ids[$row['learnpath_id']] = $in_id;
        $dsp_ord++;
        $max_dsp_lp = $dsp_ord;
    }
    //echo "<pre>lp_ids:".print_r($lp_ids,true)."</pre>\n";


    // MIGRATING LEARNPATH CHAPTERS
    $sql_lp_chap = "ALTER TABLE $lp_chap ADD INDEX ( parent_chapter_id, display_order )";
    $res_lp_chap = Database::query($sql_lp_chap);

    $sql_lp_chap = "SELECT * FROM $lp_chap ORDER BY parent_chapter_id, display_order";
    //echo "$sql_lp_chap<br />\n";
    $res_lp_chap = Database::query($sql_lp_chap);
    while ($row = Database::fetch_array($res_lp_chap)) {
        //echo "Treating chapter id : ".$row['id']."<br />\n";

        //TODO: Build path for this chapter (although there is no real path for any chapter)
        //TODO: Find out how to calculate the "next_item_id" with the "ordre" field
        $my_lp_item = $my_new_lp_item;
        $myname = Database::escape_string($row['chapter_name']);
        $mydesc = Database::escape_string($row['chapter_description']);
        $ins_lp_sql = "INSERT INTO $my_new_lp_item (" .
                "lp_id," .
                "item_type," .
                "title," .
                "description," .
                "path, " .
                "display_order, " .
                "next_item_id) " .
                "VALUES (" .
                "'".$lp_ids[$row['learnpath_id']]."'," . //insert new learnpath ID
                "'dokeos_chapter'," .
                "'".$myname."'," .
                "'".$mydesc."'," .
                "''," .
                $row['display_order'].", " .
                "123456" .
                ")";
        //echo $ins_lp_sql."<br />\n";
        $ins_res = Database::query($ins_lp_sql);
        $in_id = Database::insert_id();
        //echo "&nbsp;&nbsp;Inserted item $in_id<br />\n";
        if (!$in_id) die('Could not insert lp: '.$ins_sql);
        $parent_chaps[$row['id']] = $row['parent_chapter_id'];
        $lp_chap_items[$row['id']] = $in_id;
        $parent_lps[$row['id']] = $row['learnpath_id'];
        $ordered_chaps[$row['parent_chapter_id']][$row['display_order']] = $in_id;
        $lp_chaps_list[$row['learnpath_id']][] = $in_id;
    }
    //echo "<pre>parent_lps:".print_r($parent_lps,true)."</pre>\n";

    // Now one loop to update the parent_chapter_ids
    foreach ($parent_chaps as $old_chap => $old_parent_chap) {
        if ($old_parent_chap != 0) {
            $new_chap = $lp_chap_items[$old_chap];
            $new_parent = $lp_chap_items[$old_parent_chap];
            if (isset($new_chap) && $new_chap != '' && isset($new_parent) && $new_parent != '') {
                $sql_par_chap = "UPDATE $my_new_lp_item " .
                    "SET parent_item_id = $new_parent " .
                    "WHERE id = $new_chap";
                $res_par_chap = Database::query($sql_par_chap);
            }
        }
    }
    unset($parent_chaps);

    // Now one loop to set the next_item_id and the previous_item_id
    foreach ($ordered_chaps as $parent_chap) {
        $last = 0;
        foreach ($ordered_chaps[$parent_chap] as $order => $new_id) {
            $sql_upd_chaps = "UPDATE $my_new_lp_item " .
                    "SET previous_item_id = $last " .
                    "WHERE id = $new_id";
            $res_upd_chaps = Database::query($sql_upd_chaps);

            $next = 0;
            if (!empty($ordered_chaps[$parent_chap][$order + 1])) {
                $next = $ordered_chaps[$parent_chap][$order + 1];
            }
            $sql_upd_chaps = "UPDATE $my_new_lp_item " .
                    "SET next_item_id = $next " .
                    "WHERE id = $new_id";
            $res_upd_chaps = Database::query($sql_upd_chaps);
            $last = $new_id;
        }
    }
    unset($ordered_chaps);

    // Migrate learnpath_items
    // TODO: Define this array thanks to types defined in the learnpath_building scripts
    // TODO: Set order correctly
    $type_trans = array(
        'document'  => TOOL_DOCUMENT,
        'exercise'  => TOOL_QUIZ,
        'forum'     => TOOL_FORUM,
        'Agenda'    => TOOL_CALENDAR_EVENT,
        'Ad_Valvas' => TOOL_ANNOUNCEMENT,
        'Link'      => TOOL_LINK,
        'Link _blank' => TOOL_LINK,
        'Exercise'  => TOOL_QUIZ,
        'HotPotatoes'=> 'HotPotatoes',
        'Forum'     => TOOL_FORUM,
        'Thread'    => TOOL_THREAD,
        'Topic'     => TOOL_THREAD,
        'Post'      => TOOL_POST,
        'Document'  => TOOL_DOCUMENT,
        'Assignments'=> 'Assignments',
        'Dropbox'   => TOOL_DROPBOX,
        'Introduction_text'=> 'Introduction_text',
        'Course_description' => TOOL_COURSE_DESCRIPTION,
        'Groups'    => TOOL_GROUP,
        'Users'     => TOOL_USER,

        //'chapter' => 'dokeos_chapter', Chapters should all be in learnpath_chapter, no matter the nesting level

    );

    // MIGRATING LEARNPATH ITEMS
    $sql_lp_item = "ALTER TABLE $lp_item ADD INDEX ( chapter_id, display_order)";
    $res_lp_item = Database::query($sql_lp_item);
    $sql_lp_item = "SELECT * FROM $lp_item ORDER BY chapter_id, display_order";
    //echo "$sql_lp_item<br />\n";
    $res_lp_item = Database::query($sql_lp_item);
    while ($row = Database::fetch_array($res_lp_item)) {
        //echo "Treating chapter ".$row['chapter_id'].", item ".$row['id']."<br />\n";
        $type = $type_trans[$row['item_type']];
        $ref = $row['item_id'];
        //TODO: Build item path
        //TODO: Calculate "next_item_id" with the "ordre" field
        // Prepare prereqs
        // Prerequisites in Dokeos 1.6 is only authorised on previous items, so
        // We know that we are gonna talk about an item that has already been passed
        // through here - if none found, print message
        $prereq_id = '';
        if (!empty($row['prereq_id'])) {
            switch ($row['prereq_type']) {
                case 'c':
                    // chapter-type prereq
                    $prereq_id = $lp_chap_items[$row['prereq_id']];
                    if (empty($prereq_id) && $loglevel > 1) { error_log("Could not find prereq chapter ".$row['prereq_id'], 0); }
                    break;
                case 'i':
                default:
                    // item type prereq
                    $prereq_id = $lp_items[$parent_lps[$row['chapter_id']]][$row['prereq_id']];
                    if (empty($prereq_id) && $loglevel > 1) { error_log("Could not find prereq item ".$row['prereq_id'], 0); }
                    break;
            }
        }
        $my_parent_id = 0;
        if (isset($lp_chap_items[$row['chapter_id']])) {
            $my_parent_id = $lp_chap_items[$row['chapter_id']];
        }
        $title = '';
        if (empty($row['title']) && $type == 'Document' && !empty($row['item_id'])) {
            $my_sql_doctitle = "SELECT title FROM $lp_doc WHERE id = ".$row['item_id'];
            $my_res_doctitle = Database::query($my_sql_doctitle);
            if ($row_doctitle = Database::fetch_array($my_res_doctitle)) {
                $title = $row_doctitle['title'];
            } else  {
                $title = '-';
            }
        } else {
            $title = $row['title'];
        }
        if (isset($lp_ids[$parent_lps[$row['chapter_id']]])) {
        	// If there is a parent learnpath
            $ins_lp_sql = "INSERT INTO $my_new_lp_item (" .
                    "lp_id," .
                    "item_type," .
                    "ref, " .
                    "title," .
                    "description," .
                    "path, " .
                    "parent_item_id," .
                    "prerequisite," .
                    "display_order" .
                    ") VALUES (" .
                    "'".$lp_ids[$parent_lps[$row['chapter_id']]]."'," . // Insert new learnpath ID
                    "'$type'," .
                    "'$ref', " .
                    "'".Database::escape_string($row['title'])."'," .
                    "'".Database::escape_string($row['description'])."'," .
                    "'$ref'," .
                    "".$my_parent_id."," .
                    "'$prereq_id'," .
                    $row['display_order']." " .
                    ")";
            $ins_res = Database::query($ins_lp_sql);
            $in_id = Database::insert_id();
            //echo "&nbsp;&nbsp;Inserted item $in_id (".$row['title'].")<br />\n";
            if (!$in_id) die('Could not insert lp_item: '.$ins_sql);
            $lp_items[$parent_lps[$row['chapter_id']]][$row['id']] = $in_id;
            $lp_ordered_items[$parent_lps[$row['chapter_id']]][$row['chapter_id']][] = $in_id;
        }
    }
    //echo "<pre>lp_items:".print_r($lp_items,true)."</pre>\n";
    // Complete next_item_id field by going through the new table and looking at parent_id and display_order
    $order_sql = "ALTER TABLE $my_new_lp_item ADD INDEX (lp_id, parent_item_id, display_order)";
    $order_res = Database::query($order_sql);
    $order_sql = "SELECT * FROM $my_new_lp_item ORDER by lp_id ASC, parent_item_id ASC, display_order ASC";
    //echo "$order_sql<br />\n";
    $order_res = Database::query($order_sql);
    $order_item = array(); //this will contain a sequential list of item_id's, thus allowing to give a simple way to get next id...
    $lp_id = 0;
    //echo "<pre>";
    while ($row = Database::fetch_array($order_res)) {
        //print_r($row);
        if ($row['lp_id'] != $lp_id) {
            // Apply changes to the database and clean tool arrays
            $last = 0;
            foreach ($order_item as $order_id => $item_id) {
                $next = 0;
                if (!empty($order_item[$order_id+1])) {
                    $next = $order_item[$order_id+1];
                }
                $upd = "UPDATE $my_new_lp_item " .
                        "SET next_item_id = ".$next."," .
                        "    previous_item_id = ".$last." " .
                        "WHERE id = ".$item_id;
                //echo "$upd<br />\n";
                Database::query($upd);
                $last = $item_id;
            }
            $order_item = array();
            $lp_id = $row['lp_id'];
            $order_item[] = $row['id'];
        } else {
            $order_item[] = $row['id'];
        }
    }
    // Process the last LP stack
    $last = 0;
    foreach ($order_item as $order_id => $item_id) {
        $next = 0;
        if (!empty($order_item[$order_id + 1])) {
            $next = $order_item[$order_id + 1];
        }
        $upd = "UPDATE $my_new_lp_item " .
                "SET next_item_id = ".$next."," .
                "    previous_item_id = ".$last." " .
                "WHERE id = ".$item_id;
        //echo "$upd<br />\n";
        Database::query($upd);
        $last = $item_id;
    }

    //echo "</pre>\n";

    // MIGRATING THE learnpath_user TABLE (results)
    $mysql = "ALTER TABLE $my_new_lp_item_view ADD INDEX (lp_view_id)";
    $myres = Database::query($mysql);
    $sql_lp_user = "ALTER TABLE $lp_user ADD INDEX (user_id, learnpath_id, learnpath_item_id)";
    $res_lp_user = Database::query($sql_lp_user);
    $sql_lp_user = "SELECT * FROM $lp_user ORDER BY user_id, learnpath_id, learnpath_item_id";
    //echo "$sql_lp_user<br />\n";
    $res_lp_user = Database::query($sql_lp_user);
    $user_id = 0;
    $learnpath_id = 0;
    $lp_view = 0;
    while ($row = Database::fetch_array($res_lp_user)) {
        if ($row['user_id']!=$user_id  OR $row['learnpath_id']!=$learnpath_id) {  //the user has changed or this is the first
            // Insert a new lp_view
            $last = 0;
            if (!empty($lp_chaps_list[$row['learnpath_id']][0])) {
                $last = $lp_chaps_list[$row['learnpath_id']][0];
            }
            if (empty($lp_ids[$row['learnpath_id']])) {
                // This can be ignored as it means there was an LP before, this user
                // used it, but now it's been removed
                //echo "Somehow we also miss a lp_ids[".$row['learnpath_id']."] here<br />\n";
                $incoherences ++;
            } else {
                $mylpid = $lp_ids[$row['learnpath_id']];
                $sql_ins_view = "INSERT INTO $my_new_lp_view(" .
                        "lp_id," .
                        "user_id," .
                        "view_count," .
                        "last_item" .
                        ")VALUES(" .
                        "".$mylpid."," . // new learnpath id
                        "".$row['user_id']."," . // user IDs stay the same
                        "1," .
                        "".$last."" . // Use the first chapter from this learnpath
                        ")";
                //echo $sql_ins_view;
                $res_ins_view = Database::query($sql_ins_view);
                $in_id = Database::insert_id();
                $user_id = $row['user_id'];
                $learnpath_id = $row['learnpath_id'];
                $lp_view = $in_id;
            }
        }
        // Insert the record into lp_item_view
        // TODO: fix the whole in here (missing one item at least)
        $my_new_lp_item_id = $lp_items[$learnpath_id][$row['learnpath_item_id']];
        if (empty($my_new_lp_item_id)) {
            // This can be ignored safely as it just means a user used a learnpath_item
            // before it was removed from items - maybe fix that in Dokeos?
            //echo "Somehow we miss lp_items[".$learnpath_id."][".$row['learnpath_item_id']."] here...<br />";
            $incoherences ++;
        } else {
            $start_time = 0;
            $my_time = my_get_time($row['time']);
            if ($my_time > 0) {
                $start_time = time() - $my_time;
            }
            $sql_ins_iv = "INSERT INTO $my_new_lp_item_view(" .
                    "lp_item_id," .
                "lp_view_id," .
                "view_count," .
                "start_time," .
                "total_time," .
                "score," .
                "status" .
                ")VALUES(" .
                "".$lp_items[$learnpath_id][$row['learnpath_item_id']]."," .
                "".$lp_view."," .
                "1," .
                "$start_time," .
                "".$my_time."," .
                "".$row['score']."," .
                "'".$row['status']."'" .
                ")";
            //echo $sql_ins_iv;
            $res_ins_iv = Database::query($sql_ins_iv);
        }
        // UPDATE THE LP_VIEW progress
        $mysql = "SELECT count(distinct(lp_item_id)) FROM $my_new_lp_item_view WHERE lp_view_id = ".$lp_view." AND status IN ('passed','completed','succeeded','browsed','failed')";
        $myres = Database::query($mysql);
        $myrow = Database::fetch_array($myres);
        $completed = $myrow[0];
        $mylpid = $lp_ids[$row['learnpath_id']];
        $sql = "SELECT count(*) FROM $my_new_lp_item WHERE lp_id = '".$mylpid."'";
        $myres = Database::query($sql);
        $myrow = Database::fetch_array($myres);
        $total = $myrow[0];
        $progress = ((float)$completed / (float)$total) * 100;
        $progress = number_format($progress, 0);
        $sql = "UPDATE $my_new_lp_view SET progress = '$progress' WHERE id = '$lp_view'";
        $myres = Database::query($sql);
    }

    /**
     * Move prerequisites
     * TODO: Integrate prerequisites migration into learnpath_item migration
     */

    $msg = '';
    if ($incoherences > 0) {
        $msg = "(found $incoherences incoherences between views and items - ignored)";
    }
    /**
     * Migrate links on the homepage as well now (look into the TABLE_TOOL_LIST table and
     * update the links to newscorm/lp_controller.php?action=view&lp_id=x)
     * Only normal learnpaths were visible from the homepage so we only need to update here
     */
    // MIGRATING LEARNPATH LINKS ON COURSES HOMEPAGES
    $tbl_tool = $db.TABLE_TOOL_LIST;
    $sql_tool = "SELECT * FROM $tbl_tool WHERE image='scormbuilder.gif' AND link LIKE '%learnpath_handler%'";
    $res_tool = Database::query($sql_tool);
    while ($row_tool = Database::fetch_array($res_tool)) {
        $name = $row_tool['name'];
        $link = $row_tool['link'];
        // Get old lp_id from there
        $matches = array();
        if (preg_match('/learnpath_id=(\d+)$/', $link,$matches)) {
            $old_lp_id = $matches[1];
            $new_lp_id = $lp_ids[$old_lp_id];
            $sql_tool_upd = "UPDATE $tbl_tool " .
                    "SET link='newscorm/lp_controller.php?action=view&lp_id=$new_lp_id' " .
                    "WHERE id = ".$row_tool['id'];
            error_log('New LP - Migration - Updating tool table: '.$sql_tool_upd, 0);
            // Make sure there is a way of retrieving which links were updated (to revert)
            fwrite($fh,$sql_tool_upd." AND link ='$link'");
            fwrite($fh_revert, "UPDATE $tbl_tool SET link='$link' WHERE id=".$row_tool['id']." AND link ='newscorm/lp_controller.php?action=view&lp_id=$new_lp_id';\n");
            //echo $sql_tool_upd." (and link='$link')<br />\n";
            $res_tool_upd = Database::query($sql_tool_upd);
        }
    }

    $tbl_tool = $db.TABLE_TOOL_LIST;
    $sql_tool = "SELECT * FROM $tbl_tool";
    $res_tool = Database::query($sql_tool);
    while ($row_tool = Database::fetch_array($res_tool)) {
        $link = $row_tool['link'];
        $matches = array();
        $pattern = '@scorm/showinframes\.php([^\s"\'&]*)(&|&amp;)file=([^\s"\'&]*)@';
        if (preg_match($pattern, $link, $matches)) {
            //echo "Found match for scorm link in $tbl_tool: $link<br />";
            if (!empty($matches[3]) && (strtolower(substr($matches[3], -15)) == 'imsmanifest.xml') && !is_file(realpath(urldecode($matches[3])))) {
                //echo "Removing link $link from tools<br />";
                $sql_tool_upd = "DELETE FROM $tbl_tool WHERE id = ".$row_tool['id'];
                error_log('New LP - Migration - Updating tool table (dead link): '.$sql_tool_upd, 0);
                // Make sure there is a way of retrieving which links were updated (to revert)
                fwrite($fh, $sql_tool_upd." AND link ='$link'");
                fwrite($fh_revert, "INSERT INTO $tbl_tool (link) VALUES ('$link');\n");
                //echo $sql_tool_upd." (and link='$link')<br />\n";
                $res_tool_upd = Database::query($sql_tool_upd);
            }
        } else {
            //echo "No scorm link found in tool link $link<br />";
        }
        //flush();
    }
    /**
     * Update course description (intro page) to use new links instead of learnpath/learnpath_handler.php
     */
    $tbl_intro = $db.TABLE_TOOL_INTRO;
    $sql_i = "SELECT * FROM $tbl_intro WHERE id='course_homepage'";
    $res_i = Database::query($sql_i);
    //$link_to_course1 = 'scorm/scormdocument.php';
    while ($row_i = Database::fetch_array($res_i)) {
        $intro = $row_i['intro_text'];
        $update = 0;
        $out = array();
        if (preg_match_all('/claroline\/learnpath\/showinframes\.php([^\s"\']*)learnpath_id=(\d*)/', $intro, $out, PREG_SET_ORDER)) {
            foreach ($out as $results) {
                //echo "---> replace ".'/learnpath\/showinframes\.php([^\s"\']*)learnpath_id='.$results[2].'/ by newscorm/lp_controller.php'.$results[1].'action=view&lp_id='.$lp_ids[$results[2]];
                $intro = preg_replace('/claroline\/learnpath\/showinframes\.php([^\s"\']*)learnpath_id='.$results[2].'/','main/newscorm/lp_controller.php'.$results[1].'action=view&lp_id='.$lp_ids[$results[2]], $intro);
            }
        }
        if (preg_match_all('/claroline\/phpbb\/index.php/', $intro, $out, PREG_SET_ORDER)) {
            foreach ($out as $results) {
                $intro = preg_replace('/claroline\/phpbb\/index\.php([^\s"\']*)/','main/forum/index.php'.$results[1], $intro);
            }
        }
        if ($intrp != $row_i['intro_text']) {
            //echo "<pre>Replacing ".$row_i['intro_text']."\n by \n ".$intro."</pre><br />\n";
            $sql_upd = "update $tbl_intro set intro_text = '".Database::escape_string($intro)."' WHERE id = 'course_homepage' AND intro_text = '".Database::escape_string($row_i['intro_text'])."'";
            //echo $sql_upd."<br />\n";
            fwrite($fh,"$sql_upd\n");
            fwrite($fh_revert,"UPDATE $tbl_intro set intro_text = '".$row_i['intro_text']."' WHERE id = 'course_homepage' AND intro_text = '$intro';\n");
            fwrite($fh_res,"$intro\n");
            Database::query($sql_upd);
        }
    }

    if ($loglevel > 0) { error_log("Done!".$msg, 0); }
    //flush();
    //ob_flush();
}

unset($lp_ids);
unset($lp_users);
unset($parent_chaps);
unset($lp_chap_items);
unset($ordered_chaps);
unset($lp_items);
unset($lp_ordered_items);
unset($parent_lps);

fwrite($fh, "-- Recording course homepages links changes for SCORM to enable reverting\n");
fwrite($fh_revert, "-- Recording reverted course homepages links changes for SCORM to enable reverting\n");
fwrite($fh_res, "-- Recording resulting course homepages links changes for SCORM\n");

/**
 * SCORM
 * The migration needs to take all data from the scorm.scorm_main and scorm.scorm_sco_data tables
 * and add them to the new lp, lp_view, lp_item and lp_item_view tables.
 */
if ($loglevel > 0) { error_log("Now starting migration of scorm tables from global SCORM database", 0); }

$scorm_main = $dbScormForm.'.'.TABLE_SCORM_MAIN;
$scorm_item = $dbScormForm.'.'.TABLE_SCORM_SCO_DATA;
//$lp_main = Database::get_course_table(TABLE_LEARNPATH_MAIN,$db);
$course_pref = $table_prefix;
$lp_ids = array();
$lp_item_ids = array();
$lp_item_refs = array();
$lp_course = array();
$lp_course_code = array();
$scorm_lp_paths = array();

// Avoid empty dokeosCourse fields as they potentially break the rest
Database::select_db($dbNameForm);
$course_main = TABLE_MAIN_COURSE;
$sql_crs = "SELECT * FROM $course_main WHERE target_course_code IS NULL";
if ($loglevel > 0) { error_log("$sql_crs", 0); }
$res_crs = Database::query($sql_crs);
$num = Database::num_rows($res_crs);

// Prepare an array that will contain course codes and for each course code a list of lps [by path prefixed by '/']
$scorms = array();
$course_code_swap = '';
$scormdocuments_lps = array();
while ($course_row = Database::fetch_array($res_crs)) {

    if ($loglevel > 0) { error_log("Now dealing with course ".$course_row['code']."...", 0); }
    // Check the validity of this new course
    $my_course_code = $course_row['code'];

    // Reinit the scormdocuments list
    //$scormdocuments_lps = array();
    $db_name = $courses_id_full_table_prefix_list[$my_course_code];
    if (strlen($courses_id_list[$course_code]) > 40) {
        error_log('Database '.$courses_id_list[$course_code].' is too long, skipping', 0);
        continue;
    }

    //echo "Now processing database $db_name<br />";
    $tblscodoc = $db_name.TABLE_SCORMDOC;
    $sql_scodoc = "SELECT path FROM $tblscodoc WHERE path IS NOT NULL AND path != ''";
    if ($loglevel > 1) { error_log("$sql_scodoc", 0); }
    $res_scodoc = Database::query($sql_scodoc);
    while ($row_scodoc = Database::fetch_array($res_scodoc)) {

        // Check if there's more than one slash in total
        if (strpos($row_scodoc['path'], '/', 1) === false) {
            $tmp_path = $row_scodoc['path'];
            if ($loglevel > 1) { error_log("++Now opening $tmp_path", 0); }

            // Add a prefixing slash if there is none
            if (substr($tmp_path, 0, 1) != '/') {
                $tmp_path = '/'.$tmp_path;
            }

            // If the path is just a slash, empty it
            if ($tmp_path == '/') {
                $tmp_path = '';
            }

            // There is only one 'slash' sign at the beginning, or none at all, so we assume
            // it is a main directory that should be taken as path.
            $courses_dir = $sys_course_path.''.$courses_dir_list[$my_course_code].'/scorm'.$tmp_path;
            if (!is_dir($courses_dir)) {
                //echo "Scormdocument path $my_content_id: $tmp_path doesn't exist in ".$sys_course_path.$courses_dir_list[$my_course_code]."/scorm, skipping<br />\n";
                continue;
                // Avoid if contentTitle is not the name of an existing directory
            } elseif (!is_file($courses_dir."/imsmanifest.xml")) {
                // If the imsmanifest file was not found there
                if ($loglevel > 2) { error_log("  !!imsmanifest.xml  not found at scormdocument's $courses_dir/imsmanifest.xml, skipping", 0); }
                // Try subdirectories on one level depth
                if ($loglevel > 2) { error_log("  Trying subdirectories...", 0); }
                $dh = opendir($courses_dir);
                while ($entry = readdir($dh)) {
                    if (substr($entry, 0, 1) != '.') {
                        if (is_dir($courses_dir."/".$entry)) {
                            if (is_file($courses_dir."/".$entry."/imsmanifest.xml")) {
                                if ($loglevel > 2) { error_log(".  .. and found $courses_dir/$entry/imsmanifest.xml!", 0); }
                                if (!in_array($tmp_path."/".$entry."/imsmanifest.xml",$scormdocuments_lps)) {
                                    if ($loglevel > 2){ error_log("  Recording.<br />", 0); }
                                    $scormdocuments_lps[] = $tmp_path."/".$entry;
                                }
                            }
                        }
                    }
                }
            } else {
                if ($loglevel > 2) { error_log("  Found scormdocument $tmp_path in ".$sys_course_path.$courses_dir_list[$my_course_code]."/scorm, treating it.", 0); }
                $scormdocuments_lps[] = $tmp_path;
            }
        }
    }


    // Because certain people with no admin skills had fun adding direct links to SCORM
    // from the courses introductions, we have to check for SCORM packages from there too...
    $tbl_intro = $db_name.TABLE_TOOL_INTRO;
    $sql_i = "SELECT * FROM $tbl_intro WHERE id='course_homepage'";
    //echo $sql_i;
    $res_i = Database::query($sql_i);
    //$link_to_course1 = 'scorm/scormdocument.php';
    while ($row_scodoc = Database::fetch_array($res_i)) {
        $intro = $row_scodoc['intro_text'];
        //echo $intro."<br />\n";
        $matches = array();
        $pattern = '@scorm/showinframes\.php([^\s"\']*)file=([^\s"\'&]*)@';
        if (preg_match_all($pattern, $intro, $matches, PREG_SET_ORDER)) {
            if (count($matches) < 1) {
                // Skip
            } else {
                //echo "Found matches in $tbl_intro<br />";
                foreach ($matches as $match) {
                    //echo "Found match ".print_r($match,true)."<br />";
                    $mymatch = urldecode($match[2]);
                    $mymatch = str_replace($sys_course_path, $upd_course_path, $mymatch);
                    if (!empty($mymatch) && (strtolower(substr($mymatch, -15)) == 'imsmanifest.xml') && is_file(realpath(urldecode($mymatch)))) {

                        //echo $mymatch." seems ok<br />";
                        // Found a new scorm course in the old directory
                        $courses_dir = $upd_course_path.''.$courses_dir_list[$my_course_code].'/scorm';
                        // Check if the file is in the current course path, otherwise just forget about it
                        // as it would be too difficult to migrate
                        //echo "Comparing $mymatch with $courses_dir<br />";
                        if (strpos($mymatch, $courses_dir) !== false) {
                            // Remove the course dir up to /scorm from this path
                            $entry = substr($mymatch, strlen($courses_dir));
                            // Remove the /imsmanifest.xml from the end of the path
                            $entry = substr($entry, 0, -16);
                            // If $entry was /var/www/dokeos/courses/ABC/scorm/tralala/imsmanifest.xml,
                            // $entry is now /tralala
                            //echo "Checking if manifest exists in ".$courses_dir.$entry."/imsmanifest.xml<br />";
                            if (is_file($courses_dir.$entry."/imsmanifest.xml")) {
                                //echo "found $courses_dir/$entry/imsmanifest.xml!<br />";
                                if ($loglevel > 2) { error_log(".  .. and found $courses_dir/$entry/imsmanifest.xml!", 0); }
                                if (!in_array($entry."/imsmanifest.xml", $scormdocuments_lps)) {
                                    if ($loglevel > 2) { error_log("  Recording.<br />", 0); }
                                    //echo "Recording $entry<br />";
                                    $scormdocuments_lps[] = $entry;
                                }
                            } else {
                                //echo "Manifest does not exist in ".$courses_dir.$entry."/imsmanifest.xml<br />";
                            }
                        }
                    }
                }
            }
        }
    }

    // Prepare the new course's space in the scorms array
    $scorms[$my_course_code] = array();

    $sql_paths = "SELECT * FROM $scorm_main WHERE dokeosCourse = '".$my_course_code."'";
    if ($loglevel > 0) { error_log("$sql_paths", 0); }
    $res_paths = Database::query($sql_paths);
    $num = Database::num_rows($res_paths);
    while ($scorm_row = Database::fetch_array($res_paths)) {
        // Check if this is a new course
        $my_content_id = $scorm_row['contentId'];
        $my_path = $scorm_row['contentTitle'];
        if (substr($my_path, 0, 1) != '/') {
            $my_path = '/'.$my_path;
        }
        if ($my_path == '/') {
            $my_path = '';
        }
        if ($loglevel > 1) { error_log("++++Now opening $my_path", 0); }
        if (!is_dir($courses_dir = $sys_course_path.''.$courses_dir_list[$my_course_code].'/scorm'.$my_path)) {
            if ($loglevel > 1) { error_log("Path $my_content_id: $my_path doesn't exist in ".$sys_course_path.$courses_dir_list[$my_course_code]."/scorm, skipping", 0); }
            continue;
            // Avoid if contentTitle is not the name of an existing directory
        } elseif (!is_file($sys_course_path.$courses_dir_list[$my_course_code].'/scorm'.$my_path."/imsmanifest.xml")) {
            if ($loglevel > 1) { error_log("!!imsmanifest.xml not found at ".$sys_course_path.$courses_dir_list[$my_course_code].'/scorm'.$my_path."/imsmanifest.xml, skipping", 0); }
            continue;
        } else {
            if ($loglevel > 1) { error_log("Found $my_path in ".$sys_course_path.$courses_dir_list[$my_course_code]."/scorm".$mypath."/imsmanifest.xml, keeping it.", 0); }
            $scorms[$my_course_code][$my_path] = $my_content_id;
        }
    }
    // Check if all the lps from scormdocuments_lps are already in the course array,
    // otherwise add them (and set ID of 0 so no tracking will be available)
    foreach ($scormdocuments_lps as $path) {
        if (!in_array($path,array_keys($scorms[$my_course_code]))) {
            // Add it (-1 means no ID)
            if ($loglevel > 1) { error_log("** Scormdocument path $path wasn't recorded yet. Added.", 0); }
            $scorms[$my_course_code][$path] = -1;
        }
    }
    $course_code_swap = $my_course_code;
    unset($scormdocuments_lps);
}

//echo "<pre>courses_id_list: ".print_r($courses_id_list,true)."</pre>\n";

$my_count = 0;
foreach ($scorms as $mycourse => $my_paths) {
    $my_count += count($my_paths);
}
if ($loglevel > 0) { error_log("---- Scorms array now contains ".$mycount." paths to migrate. Starting migration...", 0); }

/**
 * Looping through the SCO_MAIN table for SCORM learnpath attached to courses
 * Order by course to try and reuse the maximum data
 */
$i_count = 0;
foreach ($scorms as $my_course_code => $paths_list) {
  $max_dsp_lp = 0;
  $course_lp_done = array();
  $db_name = $courses_id_full_table_prefix_list[$my_course_code];
  foreach ($paths_list as $my_path => $old_id) {
    if ($loglevel > 1) { error_log("Migrating lp $my_path from course $my_course_code...", 0); }
    $i_count ++;
    //error_log('New LP - Migration script - Content '.$i_count.' on '.$num.' (course '.$scorm['dokeosCourse'].')',0);
    // Check whether there is no embedded learnpaths into other learnpaths (one root-level and another embedded)
    $embedded = false;
    foreach ($course_lp_done as $tmp_lp) {
        if (empty($tmp_lp)) {
            $tmp_lp = '/'; // Allows finding the lp as a subitem, otherwise strstr returns false
        }
        if (strstr($my_path, $tmp_lp) === false) {
            // Let it be
        } else {
            // This lp is embedded inside another lp who's imsmanifest exists, so prevent from parsing
            if ($loglevel > 1) { error_log("LP $my_path is embedded into $tmp_lp, ignoring...", 0); }
            $embedded = true;
            continue;
        }
    }
    if ($embedded) {
        continue;
    }
    $course_lp_done[] = $my_path;
    //echo "<pre>scorm row: ".print_r($scorm,true)."</pre>\n";
    $my_content_id = $old_id;
    $my_path = $my_path;
    $my_name = basename($my_path);

    if ($loglevel > 1) { error_log("Try importing LP $my_path from imsmanifest first as it is more reliable", 0); }

    // Setup the ims path (path to the imsmanifest.xml file)
    //echo "Looking for course with code ".$lp_course_code[$my_content_id]." (using $my_content_id)<br />\n";
    //$courses_dir = $sys_course_path.$courses_dir_list[$my_course_code];
    $courses_dir = $upd_course_path.$courses_dir_list[$my_course_code];
    $sco_path_temp = ($my_path == '/') ? '' : $my_path;
    $sco_middle_path = (empty($sco_path_temp) ? '' : (substr($sco_path_temp, 0, 1) == '/') ? substr($sco_path_temp, 1).'/' : $sco_path_temp.'/'); //same thing as sco_path_temp but with reversed slashes
    $ims = $courses_dir.'/scorm'.$sco_path_temp.'/imsmanifest.xml';

    if (is_file($ims)){
        //echo "Path $ims exists, importing...(line ".__LINE__.")<br />";
        $oScorm = new scorm();
        // Check whether imsmanifest.xml exists at this location. If not, ignore the imsmanifest.
        // That should have been done before already, now.
        if ($loglevel > 1) { error_log("Found imsmanifest ($ims), importing...", 0); }
        if (!empty($sco_middle_path)) { $oScorm->subdir = $sco_middle_path; } //this sets the subdir for the scorm package inside the scorm dir
        // Parse manifest file
        $manifest = $oScorm->parse_manifest($ims);
        // The title is already escaped in the method
        $oScorm->import_manifest($my_course_code);
        //TODO: Add code to update the path in that new lp created, as it probably uses / where
        // $sco_path_temp should be used...
        $lp_ids[$my_content_id] = $oScorm->lp_id; // Contains the old LP ID => the new LP ID
        if ($loglevel > 1) { error_log(" @@@ Created scorm lp ".$oScorm->lp_id." from imsmanifest [".$ims."] in course $my_course_code", 0); }
        $lp_course[$my_content_id] = $courses_id_list[$my_course_code]; // Contains the old learnpath ID => the course DB name
        $lp_course_code[$my_content_id] = $my_course_code;
        $max_dsp_lp++;

        /*
         * QUERY SCORM ITEMS FROM SCORM_SCO_DATA
         * The danger here is that we might have several users for the same data, and so
         * we have to avoid entering the same elements twice
         */
        $sql_items = "SELECT * FROM $scorm_item WHERE contentId = '".$my_content_id."' ORDER BY scoId";
        //echo "$sql_items<br />\n";
        $res_items = Database::query($sql_items);
        while ($scormItem = Database::fetch_array($res_items)) {
            $my_sco_id      = $scormItem['scoId']; //the index for display??? (check that)
            $my_identifier  = $scormItem['scoIdentifier']; //the scorm item path/name
            $my_title       = Database::escape_string($scormItem['scoTitle']);
            $my_status      = $scormItem['status'];
            $my_student     = $scormItem['studentId'];
            $my_score       = $scormItem['score'];
            $my_time        = my_get_time($scormItem['time']);
            $my_type        = 'sco';
            //$my_item_path = $scorm_lp_paths[$my_content_id]['path'];
            $my_item_path   = '';

            //echo "&nbsp;&nbsp;FOUND item belonging to old learnpath num $my_content_id so belongs to course ".$lp_course[$my_content_id]."<br />\n";
            $my_new_lp_item = $db_name.$new_lp_item;
            $my_new_lp_view = $db_name.$new_lp_view;
            $my_new_lp_item_view = $db_name.$new_lp_item_view;

            /*
             * Check if a view is needed
             */
            if ($my_score != '' and $my_status != 'not attempted') {
                // It is worth creating an lp_view and an lp_item_view - otherwise not
                $sel_sqlb = "SELECT * FROM $my_new_lp_view " .
                        "WHERE lp_id = ".$lp_ids[$my_content_id]." AND user_id = $my_student";
                $sel_resb = Database::query($sel_sqlb);
                if (Database::num_rows($sel_resb) > 0) {
                    // Don't insert
                    $rowb = Database::fetch_array($sel_resb);
                    $view_insert_id = $rowb['id'];
                } else {
                    $ins_sql = "INSERT INTO $my_new_lp_view (" .
                        "lp_id," .
                        "user_id," .
                        "view_count" .
                        ") VALUES (" .
                        $lp_ids[$my_content_id].", " .
                        $my_student.", " .
                        "1" .
                        ")";
                    //echo "$ins_sql<br />";
                    $ins_res = Database::query($ins_sql);
                    $view_insert_id = Database::insert_id();
                }
                $sel_sqlc = "SELECT * FROM $my_new_lp_item " .
                        "WHERE lp_id = ".$lp_ids[$my_content_id]." AND ref = '$my_identifier'";
                $sel_resc = Database::query($sel_sqlc);
                if (Database::num_rows($sel_resc) > 0) {
                    $my_item_id_row = Database::fetch_array($sel_resc);
                    $item_insert_id = $my_item_id_row['id'];
                    $ins_sql = "INSERT INTO $my_new_lp_item_view (" .
                            "lp_item_id, lp_view_id, view_count," .
                            "start_time, total_time, score," .
                            "status" .
                            ") VALUES (" .
                            "$item_insert_id, $view_insert_id, 1," .
                            "0, $my_time, $my_score," .
                            "'$my_status'" .
                            ")";
                    //echo "$ins_sql<br />";
                    $ins_res = Database::query($ins_sql);
                } else {
                    //echo "  Didn't find corresponding item for $my_identifier in new tables<br />\n";
                }
            }
        }

    } else {
        //echo "Could not find $ims... Proceeding from database...(line ".__LINE__.")<br />";
        if ($loglevel > 1) { error_log("This is a normal SCORM path", 0); }
        $scorm_lp_paths[$my_content_id]['path'] = $my_path;
        //$scorm_lp_paths[$my_content_id]['ims'] = '';
        $table_name = $db_name.$new_lp;
        $sql_ins = "INSERT INTO $table_name (" .
                "lp_type," .
                "name," .
                "description," .
                "path," .
                "force_commit, " .
                "default_encoding," .
                "display_order," .
                "content_maker," .
                "content_local," .
                "js_lib" .
                ") VALUES (" .
                "2," .
                "'$my_name'," .
                "''," .
                "'$my_path'," .
                "0," .
                "'UTF-8'," .
                "".$max_dsp_lp."," .
                "'Unknown'," .
                "'Unknown'," .
                "'scorm_api.php'" .
                ")";
        if ($loglevel > 1) { error_log("$sql_ins", 0); }
        $sql_res = Database::query($sql_ins);
        $in_id = Database::insert_id();
        if (!$in_id) die('Could not insert scorm lp: '.$sql_ins);
        //echo "&nbsp;&nbsp;Inserted item $in_id<br />\n";
        $lp_ids[$my_content_id] = $in_id; //contains the old LP ID => the new LP ID
        $lp_course[$my_content_id] = $courses_id_list[$my_course_code]; // Contains the old learnpath ID => the course DB name
        $lp_course_code[$my_content_id] = $my_course_code;
        $max_dsp_lp++;

        // Setup the ims path (path to the imsmanifest.xml file)
        //echo "Looking for course with code ".$lp_course_code[$my_content_id]." (using $my_content_id)<br />\n";
        $courses_dir = $sys_course_path.$courses_dir_list[$lp_course_code[$my_content_id]];
        //$scorm_lp_paths[$my_content_id]['path'] = str_replace(' ', '\\ ', $scorm_lp_paths[$my_content_id]['path']);
        $sco_path_temp = ($scorm_lp_paths[$my_content_id]['path'] == '/') ? '' : $scorm_lp_paths[$my_content_id]['path'];
        $scorm_lp_paths[$my_content_id]['ims'] = $courses_dir.'/scorm'.$sco_path_temp.'/imsmanifest.xml';

        // Generate an imsmanifest object to get more info about the learnpath from the file
        $oScorm = new scorm();
        // Check whether imsmanifest.xml exists at this location. If not, ignore the imsmanifest.
        // That should have been done before already, now.
        if (!is_file($scorm_lp_paths[$my_content_id]['ims'])) {
            if ($loglevel > 1) { error_log("!!! imsmanifest file not found at ".$scorm_lp_paths[$my_content_id]['ims'].' for old lp '.$my_content_id.' and new '.$lp_ids[$my_content_id], 0); }
            $manifest = false;
        } else {
            //echo "Parsing ".$scorm_lp_paths[$my_content_id]['ims']."<br />\n";
            // Parse manifest file
            $manifest = $oScorm->parse_manifest($scorm_lp_paths[$my_content_id]['ims']);
            // The title is already escaped in the method
            //$my_lp_title = api_convert_encoding($oScorm->get_title(),'ISO-8859-1',$oScorm->manifest_encoding);
            $my_lp_title = api_convert_encoding($oScorm->get_title(), 'ISO-8859-1', 'UTF-8');  // TODO: This "magic" conversion to be checked.
            if (!empty($my_lp_title)) {
                $my_new_lp = $db_name.$new_lp;
                $my_sql = "UPDATE $my_new_lp " .
                        "SET name = '$my_lp_title', " .
                        "default_encoding = '".strtoupper($oScorm->manifest_encoding)."' " .
                        "WHERE id = ".$lp_ids[$my_content_id];
                if ($loglevel > 1) { error_log("Updating title and encoding: ".$my_sql, 0); }
                $my_res = Database::query($my_sql);
            }
        }

        /*
         * QUERY SCORM ITEMS FROM SCORM_SCO_DATA
         * The danger here is that we might have several users for the same data, and so
         * we have to avoid entering the same elements twice
         */
        $sql_items = "SELECT * FROM $scorm_item WHERE contentId = '".$my_content_id."' ORDER BY scoId";
        //echo "$sql_items<br />\n";
        $res_items = Database::query($sql_items);
        while ($scormItem = Database::fetch_array($res_items)) {
            $my_sco_id      = $scormItem['scoId']; // The index for display??? (check that)
            $my_identifier  = $scormItem['scoIdentifier']; //the scorm item path/name
            $my_title       = Database::escape_string($scormItem['scoTitle']);
            $my_status      = $scormItem['status'];
            $my_student     = $scormItem['studentId'];
            $my_score       = $scormItem['score'];
            $my_time        = my_get_time($scormItem['time']);
            $my_type        = 'sco';
            //$my_item_path = $scorm_lp_paths[$my_content_id]['path'];
            $my_item_path   = '';

            //echo "&nbsp;&nbsp;FOUND item belonging to old learnpath num $my_content_id so belongs to course ".$lp_course[$my_content_id]."<br />\n";
            $my_new_lp_item = $db_name.$new_lp_item;
            $my_new_lp_view = $db_name.$new_lp_view;
            $my_new_lp_item_view = $db_name.$new_lp_item_view;

            /*
             * Query items from the new table to check if it doesn't exist already
             * Otherwise insert it
             */
            $sel_sql = "SELECT * FROM $my_new_lp_item " .
                    "WHERE ref = '$my_identifier' " .
                    "AND lp_id = ".$lp_ids[$my_content_id]."";
            //echo $sel_sql."<br />\n";
            $sel_res = Database::query($sel_sql);
            if (Database::num_rows($sel_res) > 0) {
                // This item already exists, reuse
                $row = Database::fetch_array($sel_res);
                $item_insert_id = $row['lp_id'];
            } else {
                $ins_sql = "INSERT INTO $my_new_lp_item (" .
                    "lp_id," .
                    "item_type," .
                    "ref," .
                    "title," .
                    "path" .
                    ") " .
                    "VALUES (" .
                    "'".$lp_ids[$my_content_id]."'," . //insert new learnpath ID
                    "'$my_type'," .
                    "'".$my_identifier."'," .
                    "'".$my_title."'," .
                    "'$my_item_path'" .
                    ")";
                $ins_res = Database::query($ins_sql);
                $item_insert_id = Database::insert_id();
                $lp_item_ids[$lp_ids[$my_content_id]][$my_sco_id] = $item_insert_id;
                $lp_item_refs[$lp_ids[$my_content_id]][$my_identifier] = $item_insert_id;
            }
            /*
             * Check if a view is needed
             */
            if ($my_score != '' and $my_status != 'not attempted') {
                // It is worth creating an lp_view and an lp_item_view - otherwise not
                $sel_sqlb = "SELECT * FROM $my_new_lp_view " .
                        "WHERE lp_id = ".$lp_ids[$my_content_id]." AND user_id = $my_student";
                $sel_resb = Database::query($sel_sqlb);
                if (Database::num_rows($sel_resb) > 0) {
                    // Don't insert
                    $rowb = Database::fetch_array($sel_resb);
                    $view_insert_id = $rowb['id'];
                } else {
                    $ins_sql = "INSERT INTO $my_new_lp_view (" .
                        "lp_id," .
                        "user_id," .
                        "view_count" .
                        ") VALUES (" .
                        $lp_ids[$my_content_id].", " .
                        $my_student.", " .
                        "1" .
                        ")";
                    $ins_res = Database::query($ins_sql);
                    $view_insert_id = Database::insert_id();
                }
                $ins_sql = "INSERT INTO $my_new_lp_item_view (" .
                        "lp_item_id, lp_view_id, view_count," .
                        "start_time, total_time, score," .
                        "status" .
                        ") VALUES (" .
                        "$item_insert_id, $view_insert_id, 1," .
                        "0, $my_time, $my_score," .
                        "'$my_status'" .
                        ")";
                $ins_res = Database::query($ins_sql);
            }
        }
        // UPDATE THE LP_VIEW progress
        if (!empty($view_insert_id)) {
            $sql = "SELECT count(distinct(lp_item_id)) FROM $my_new_lp_item_view WHERE lp_view_id = ".$view_insert_id." AND status IN ('passed','completed','succeeded','browsed','failed')";
            $myres = Database::query($sql);
            $myrow = Database::fetch_array($myres);
            $completed = $myrow[0];
            $mylpid = $lp_ids[$my_content_id];
            $sql = "SELECT count(*) FROM $my_new_lp_item WHERE lp_id = '".$mylpid."'";
            $myres = Database::query($sql);
            $myrow = Database::fetch_array($myres);
            $total = $myrow[0];
            $progress = ((float)$completed / (float)$total) * 100;
            $progress = number_format($progress, 0);
            $sql = "UPDATE $my_new_lp_view SET progress = '$progress' WHERE id = '$view_insert_id'";
            $myres = Database::query($sql);
        }

        /*
         * Set all information that might be more correct coming from imsmanifest
         */

        //$my_new_lp = $db_name.$new_lp;
        //$my_new_lp_item = $db_name.$new_lp_item;
        //$my_new_lp_view = $db_name.$new_lp_view;
        //$my_new_lp_item_view = $db_name.$new_lp_item_view;
        //$sel_sql = "SELECT * FROM $my_new_lp WHERE id = $in_id";
        //$res = @Database::query($sel_sql);
        //if (!$res) {
        //  echo "Error selecting lp: $sel_sql - ".Database::error()."<br />\n";
        //}
        $lp_details = array();
        //while ($row = Database::fetch_array($res)) {
            $ordered_list = array();
            $mylist = array();
            foreach ($oScorm->organizations as $org) {
                // There should be only one organization (generally)
                // and if there are more, we are not supposed to have been
                // able to manage them before the new tool, so ignore
                if (count($ordered_list) > 0) {
                    break;
                }
                $ordered_list = $org->get_flat_items_list();
            }
            $previous = 0;
            $stock = array(0);
            $level = 0;
            $parent_id = 0;
            foreach ($ordered_list as $index => $subarray) {
                // $subarray is an array representing one item and that contains info like
                // identifier, level, rel_order, prerequisites, title, masteryscore, etc.
                //echo "<pre>Lookin for ".$subarray['identifier']." ".print_r($lp_item_refs,true)."</pre>\n";
                if (!empty($lp_item_refs[$in_id][$subarray['identifier']])) {
                    $new_id = $lp_item_refs[$in_id][$subarray['identifier']];
                    $next = 0;
                    $dsp = $subarray['rel_order'];
                    if ($subarray['level'] > $level) {
                        // Getting one level deeper, just consult
                        $parent_id = $previous;
                        array_push($stock,$previous);
                        $level = $subarray['level'];
                    } elseif ($subarray['level'] == $level) {
                        // We are on the same level, going to the next id
                        //array_pop($stock);
                        //array_push($stock, $new_id);
                    } else {
                        // Getting back from one level deeper
                        array_pop($stock);
                        $parent_id = array_pop($stock);
                        array_push($stock, $parent_id);
                        $level = $subarray['level'];
                    }
                    if (!empty($ordered_list[$index + 1]['identifier']) && !empty($lp_item_refs[$in_id][$ordered_list[$index + 1]['identifier']])){
                        $next = $lp_item_refs[$in_id][$ordered_list[$index + 1]['identifier']];
                    }
                    $path = $oScorm->get_res_path($subarray['identifierref']);
                    $update_path = '';
                    if (!empty($path)) {
                        // If new path is not empty, update
                        $update_path = "path = '$path', ";
                    }
                    $type = $oScorm->get_res_type($subarray['identifierref']);
                    $update_type = '';
                    if (!empty($type)) {
                        // If type is defined, update
                        $update_type = "item_type = '$type', ";
                    }
                    if (empty($path)) {
                        // If path is empty, it is a dir anyway
                        $update_type = "item_type = 'dir', ";
                    }
                    $prereq = $subarray['prerequisites'];
                    $update_prereq = '';
                    if (!empty($prereq)) {
                        $update_prereq = "prerequisite = '$prereq', ";
                    }

                    // We had previous data about this element, update
                    $sql2 = "UPDATE $my_new_lp_item " .
                            "SET parent_item_id = $parent_id, " .
                            "previous_item_id = $previous, " .
                            "next_item_id = $next, " .
                            $update_path.
                            $update_type.
                            $update_prereq.
                            "display_order = $dsp " .
                            "WHERE lp_id = ".$in_id." AND id = ".$new_id;
                    //echo "$sql2<br />\n";
                    $res2 = Database::query($sql2);
                    $previous = $new_id;
                }
            }
            /**
             * Migrate links on the homepage as well now (look into the TABLE_TOOL_LIST table and
             * update the links to newscorm/lp_controller.php?action=view&lp_id=x)
             * See scorm_migrate_hometools.php
             */
        //}
    // end of case where $my_content_id != -1

    }

    /**
     * Update course description (intro page) to use new links instead of learnpath/learnpath_handler.php
     */
    $tbl_intro = $db_name.TABLE_TOOL_INTRO;
    $sql_i = "SELECT * FROM $tbl_intro WHERE id='course_homepage'";
    $res_i = Database::query($sql_i);
    //$link_to_course1 = 'scorm/scormdocument.php';
    while ($row_i = Database::fetch_array($res_i)) {
        $intro = $row_i['intro_text'];
        $out = array();
        $enc_path = str_replace('/', '%2F', $my_path);
        $enc_path = str_replace(' ', '\+', $enc_path);
        //echo "Looking for path ".$enc_path."<br />\n";
        $pattern = '@claroline/scorm/scormdocument\.php([^\s"\']*)openDir='.$enc_path.'([\\"\'\s&]*)@';
        if (preg_match_all($pattern, $intro, $out, PREG_SET_ORDER)) {
            foreach ($out as $results) {
                //echo "---> replace ".'/'.$results[0].'/ by newscorm/lp_controller.php'.$results[1].'action=view&lp_id='.$lp_ids[$my_content_id];
                //$intro = preg_replace('/scorm\/scormdocument\.php([^\s"\']*)openDir='.$enc_path.'([\\"\'\s&])/', 'newscorm/lp_controller.php'.$results[1].'action=view&lp_id='.$lp_ids[$my_content_id], $intro);
                $intro = preg_replace('@claroline/scorm/scormdocument\.php([^\s"\']*)openDir='.$enc_path.'([^\s"\']*)@','main/newscorm/lp_controller.php'.$results[1].'action=view&lp_id='.$lp_ids[$my_content_id], $intro);
            }
        } else {
            //echo "No scorm link found in intro text<br />";
        }
        $pattern = '@claroline/scorm/showinframes\.php([^\s"\']*)file=([^\s"\'&]*)'.$enc_path.'@';
        if (preg_match_all($pattern, $intro, $out, PREG_SET_ORDER)) {
            foreach ($out as $results) {
                //echo "---> replace ".'/'.$results[0].'/ by newscorm/lp_controller.php'.$results[1].'action=view&lp_id='.$lp_ids[$my_content_id];
                //$intro = preg_replace('/scorm\/showinframes\.php([^\s"\']*)file=([^\s"\']*)'.$enc_path.'/', 'newscorm/lp_controller.php'.$results[1].'action=view&lp_id='.$lp_ids[$my_content_id], $intro);
                $intro = preg_replace('@claroline/scorm/showinframes\.php([^\s"\']*)file=([^\s"\'&]*)'.$enc_path.'([^\s"\']*)@','main/newscorm/lp_controller.php'.$results[1].'action=view&lp_id='.$lp_ids[$my_content_id], $intro);
            }
        } else {
            //echo "No scorm link found in intro text<br />";
        }
        if ($intro != $row_i['intro_text']) {
            //echo "<pre>Replacing ".$row_i['intro_text']."\n by \n ".$intro."</pre><br />\n";
            $sql_upd = "update $tbl_intro set intro_text = '".Database::escape_string($intro)."' WHERE id = 'course_homepage' AND intro_text = '".Database::escape_string($row_i['intro_text'])."'";
            //echo $sql_upd."<br />\n";
            fwrite($fh, $sql_upd."\n");
            fwrite($fh_revert, "UPDATE $tbl_intro set intro_text = '".$row_i['intro_text']."' WHERE id = 'course_homepage' AND intro_text = '$intro';\n");
            fwrite($fh_res, $intro."\n");
            Database::query($sql_upd);
        }
    }

    flush();
  }
}
fclose($fh);
fclose($fh_revert);
fclose($fh_res);
if ($loglevel > 0) { error_log("All done!", 0); }
//echo "</body></html>";

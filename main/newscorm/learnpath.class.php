<?php
/* For licensing terms, see /license.txt */
/**
 * This class defines the parent attributes and methods for Chamilo learnpaths and SCORM
 * learnpaths. It is used by the scorm class.
 *
 * @package chamilo.learnpath
 * @author	Yannick Warnier <ywarnier@beeznest.org>
 * @author	Julio Montoya   <gugli100@gmail.com> Several improvements and fixes
 */
/**
 * Defines the learnpath parent class
 * @package chamilo.learnpath
 */
class learnpath {

    public $attempt = 0; // The number for the current ID view.
    public $cc; // Course (code) this learnpath is located in. @todo change name for something more comprensible ...
    public $current; // Id of the current item the user is viewing.
    public $current_score; // The score of the current item.
    public $current_time_start; // The time the user loaded this resource (this does not mean he can see it yet).
    public $current_time_stop; // The time the user closed this resource.
    public $default_status = 'not attempted';
    public $encoding = 'UTF-8';
    public $error = '';
    public $extra_information = ''; // This string can be used by proprietary SCORM contents to store data about the current learnpath.
    public $force_commit = false; // For SCORM only - if set to true, will send a scorm LMSCommit() request on each LMSSetValue().
    public $index; // The index of the active learnpath_item in $ordered_items array.
    public $items = array();
    public $last; // item_id of last item viewed in the learning path.
    public $last_item_seen = 0; // In case we have already come in this learnpath, reuse the last item seen if authorized.
    public $license; // Which license this course has been given - not used yet on 20060522.
    public $lp_id; // DB ID for this learnpath.
    public $lp_view_id; // DB ID for lp_view
    public $log_file; // File where to log learnpath API msg.
    public $maker; // Which maker has conceived the content (ENI, Articulate, ...).
    public $message = '';
    public $mode = 'embedded'; // Holds the video display mode (fullscreen or embedded).
    public $name; // Learnpath name (they generally have one).
    public $ordered_items = array(); // List of the learnpath items in the order they are to be read.
    public $path = ''; // Path inside the scorm directory (if scorm).
    public $theme; // The current theme of the learning path.
    public $preview_image; // The current image of the learning path.

    // Tells if all the items of the learnpath can be tried again. Defaults to "no" (=1).
    public $prevent_reinit = 1;

    // Describes the mode of progress bar display.
    public $seriousgame_mode = 0;

    public $progress_bar_mode = '%';

    // Percentage progress as saved in the db.
    public $progress_db = '0';
    public $proximity; // Wether the content is distant or local or unknown.
    public $refs_list = array (); //list of items by ref => db_id. Used only for prerequisites match.
    // !!!This array (refs_list) is built differently depending on the nature of the LP.
    // If SCORM, uses ref, if Chamilo, uses id to keep a unique value.
    public $type; //type of learnpath. Could be 'dokeos', 'scorm', 'scorm2004', 'aicc', ...
    // TODO: Check if this type variable is useful here (instead of just in the controller script).
    public $user_id; //ID of the user that is viewing/using the course
    public $update_queue = array();
    public $scorm_debug = 0;

    public $arrMenu = array(); // Array for the menu items.

    public $debug = 0; // Logging level.

    public $lp_session_id =0;
    public $lp_view_session_id =0; // The specific view might be bound to a session.

    public $prerequisite = 0;
    public $use_max_score = 1; // 1 or 0

    public $created_on      = '';
    public $modified_on     = '';
    public $publicated_on   = '';
    public $expired_on      = '';

    /**
     * Class constructor. Needs a database handler, a course code and a learnpath id from the database.
     * Also builds the list of items into $this->items.
     * @param	string		Course code
     * @param	integer		Learnpath ID
     * @param	integer		User ID
     * @return	boolean		True on success, false on error
     */
    public function __construct($course, $lp_id, $user_id) {
        $this->encoding = api_get_system_encoding(); // Chamilo 1.8.8: We intend always to use the system encoding.
        // Check params.
        // Check course code.
        $course_db = '';
        
        $course_id = api_get_course_int_id();
        
        if ($this->debug > 0) {error_log('New LP - In learnpath::__construct('.$course.','.$lp_id.','.$user_id.')', 0); }
        if (empty($course)) {
            $this->error = 'Course code is empty';
            return false;
        } else {
            $main_table = Database::get_main_table(TABLE_MAIN_COURSE);
            $course = $this->escape_string($course);            
            $sql = "SELECT * FROM $main_table WHERE code = '$course'";
            if ($this->debug > 2) { error_log('New LP - learnpath::__construct() '.__LINE__.' - Querying course: '.$sql, 0); }
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                $this->cc 			= $course;                
                $row_course         = Database::fetch_array($res);
                $course_id 	        = $row_course['id'];
                $course_db          = $row_course['db_name'];
            } else {
                $this->error = 'Course code does not exist in database ('.$sql.')';
                return false;
            }
        }

        // Check learnpath ID.
        if (empty($lp_id)) {
            $this->error = 'Learnpath ID is empty';
            return false;
        } else {
            // TODO: Make it flexible to use any course_code (still using env course code here).
            $lp_table = Database::get_course_table(TABLE_LP_MAIN);

            //$id = Database::escape_integer($id);
            $lp_id = $this->escape_string($lp_id);
            $sql = "SELECT * FROM $lp_table WHERE id = '$lp_id' AND c_id = $course_id";
            if ($this->debug > 2) { error_log('New LP - learnpath::__construct() '.__LINE__.' - Querying lp: '.$sql, 0); }
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                $this->lp_id            = $lp_id;
                $row                    = Database::fetch_array($res);
                $this->type             = $row['lp_type'];
                $this->name             = stripslashes($row['name']);
                //$this->encoding       = $row['default_encoding']; // Chamilo 1.8.8: We intend not to use 'default_encoding' field anymore.
                $this->proximity        = $row['content_local'];
                $this->theme            = $row['theme'];
                $this->maker            = $row['content_maker'];
                $this->prevent_reinit   = $row['prevent_reinit'];
    	        $this->seriousgame_mode = $row['seriousgame_mode'];
                $this->license          = $row['content_license'];
                $this->scorm_debug      = $row['debug'];
                $this->js_lib           = $row['js_lib'];
                $this->path             = $row['path'];
                $this->preview_image    = $row['preview_image'];
                $this->author           = $row['author'];
                $this->hide_toc_frame = $row['hide_toc_frame'];
                $this->lp_session_id    = $row['session_id'];
                $this->use_max_score    = $row['use_max_score'];                

                $this->created_on       = $row['created_on'];
                $this->modified_on      = $row['modified_on'];

                if ($row['publicated_on'] != '0000-00-00 00:00:00') {
                    $this->publicated_on   = $row['publicated_on'];
                }

                if ($row['expired_on'] != '0000-00-00 00:00:00') {
                    $this->expired_on     = $row['expired_on'];
                }                
                if ($this->type == 2) {
                    if ($row['force_commit'] == 1) {
                        $this->force_commit = true;
                    }
                }                
                $this->mode = $row['default_view_mod'];
            } else {
                $this->error = 'Learnpath ID does not exist in database ('.$sql.')';
                return false;
            }
        }

        // Check user ID.
        if (empty($user_id)) {
            $this->error = 'User ID is empty';
            return false;
        } else {
            //$main_table = Database::get_main_user_table();
            $main_table = Database::get_main_table(TABLE_MAIN_USER);
            //$user_id = Database::escape_integer($user_id);
            $user_id = $this->escape_string($user_id);
            $sql = "SELECT * FROM $main_table WHERE user_id = '$user_id'";
            if ($this->debug > 2) { error_log('New LP - learnpath::__construct() '.__LINE__.' - Querying user: '.$sql, 0); }
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                $this->user_id = $user_id;
            } else {
                $this->error = 'User ID does not exist in database ('.$sql.')';
                return false;
            }
        }
        // End of variables checking.

        $session_id = api_get_session_id();
        //  Get the session condition for learning paths of the base + session.
        $session = api_get_session_condition($session_id);
        // Now get the latest attempt from this user on this LP, if available, otherwise create a new one.
        $lp_table = Database::get_course_table(TABLE_LP_VIEW);
        // Selecting by view_count descending allows to get the highest view_count first.
        $sql = "SELECT * FROM $lp_table WHERE c_id = $course_id AND lp_id = '$lp_id' AND user_id = '$user_id' $session  ORDER BY view_count DESC";
        if ($this->debug > 2) { error_log('New LP - learnpath::__construct() ' . __LINE__ . ' - querying lp_view: ' . $sql, 0); }
        $res = Database::query($sql);
        $view_id = 0; // Used later to query lp_item_view.
        if (Database :: num_rows($res) > 0) {
            if ($this->debug > 2) {
                error_log('New LP - learnpath::__construct() ' . __LINE__ . ' - Found previous view', 0);
            }
            $row = Database :: fetch_array($res);
            $this->attempt = $row['view_count'];
            $this->lp_view_id = $row['id'];
            $this->last_item_seen = $row['last_item'];
            $this->progress_db = $row['progress'];
            $this->lp_view_session_id = $row['session_id'];
        } else {
            if ($this->debug > 2) {
                error_log('New LP - learnpath::__construct() ' . __LINE__ . ' - NOT Found previous view', 0);
            }
            $this->attempt = 1;
            $sql_ins = "INSERT INTO $lp_table (c_id, lp_id,user_id,view_count, session_id) VALUES ($course_id, $lp_id, $user_id, 1, $session_id)";
            $res_ins = Database::query($sql_ins);
            $this->lp_view_id = Database :: insert_id();
            if ($this->debug > 2) {
                error_log('New LP - learnpath::__construct() ' . __LINE__ . ' - inserting new lp_view: ' . $sql_ins, 0);
            }
        }

        // Initialise items.
        $lp_item_table = Database::get_course_table(TABLE_LP_ITEM);
        $sql = "SELECT * FROM $lp_item_table WHERE c_id = $course_id AND lp_id = '".$this->lp_id."' ORDER BY parent_item_id, display_order";
        $res = Database::query($sql);
        while ($row = Database::fetch_array($res)) {
            $oItem = '';
            //$this->ordered_items[] = $row['id'];
            switch ($this->type) {
                case 3: //aicc
                    $oItem = new aiccItem('db', $row['id'], $course_db);
                    if (is_object($oItem)) {
                        $my_item_id = $oItem->get_id();
                        $oItem->set_lp_view($this->lp_view_id, $course_id);
                        $oItem->set_prevent_reinit($this->prevent_reinit);
                        // Don't use reference here as the next loop will make the pointed object change.
                        $this->items[$my_item_id] = $oItem;
                        $this->refs_list[$oItem->ref] = $my_item_id;
                        if ($this->debug > 2) {
                            error_log('New LP - learnpath::__construct() - aicc object with id ' . $my_item_id . ' set in items[]', 0);
                        }
                    }
                    break;
                case 2:
                    require_once 'scorm.class.php';
                    require_once 'scormItem.class.php';
                    $oItem = new scormItem('db', $row['id'], $course_db);
                    if (is_object($oItem)) {
                        $my_item_id = $oItem->get_id();
                        $oItem->set_lp_view($this->lp_view_id, $course_id);
                        $oItem->set_prevent_reinit($this->prevent_reinit);
                        // Don't use reference here as the next loop will make the pointed object change.

                        $this->items[$my_item_id] = $oItem;
                        $this->refs_list[$oItem->ref] = $my_item_id;
                        if ($this->debug > 2) {
                            error_log('New LP - object with id ' . $my_item_id . ' set in items[]', 0);
                        }
                    }
                    break;
                case 1:

                default:
                    require_once 'learnpathItem.class.php';
                    $oItem = new learnpathItem($row['id'], $user_id, $course_id);
                    if (is_object($oItem)) {
                        $my_item_id = $oItem->get_id();
                        //$oItem->set_lp_view($this->lp_view_id); // Moved down to when we are sure the item_view exists.
                        $oItem->set_prevent_reinit($this->prevent_reinit);
                        // Don't use reference here as the next loop will make the pointed object change.
                        $this->items[$my_item_id] = $oItem;
                        $this->refs_list[$my_item_id] = $my_item_id;
                        if ($this->debug > 2) {
                            error_log('New LP - learnpath::__construct() ' . __LINE__ . ' - object with id ' . $my_item_id . ' set in items[]', 0);
                        }
                    }
                    break;
            }

            // Items is a list of pointers to all items, classified by DB ID, not SCO id.
            if ($row['parent_item_id'] == 0 || empty ($this->items[$row['parent_item_id']])) {
                if (is_object($this->items[$row['id']])) {
                  $this->items[$row['id']]->set_level(0);
                }
            } else {
                $level = $this->items[$row['parent_item_id']]->get_level() + 1;
                $this->items[$row['id']]->set_level($level);
                if (is_object($this->items[$row['parent_item_id']])) {
                    // Items is a list of pointers from item DB ids to item objects.
                    $this->items[$row['parent_item_id']]->add_child($row['id']);
                } else {
                    if ($this->debug > 2) {
                        error_log('New LP - learnpath::__construct() ' . __LINE__ . ' - The parent item (' . $row['parent_item_id'] . ') of item ' . $row['id'] . ' could not be found', 0);
                    }
                }
            }
            // Get last viewing vars.
            $lp_item_view_table = Database :: get_course_table(TABLE_LP_ITEM_VIEW);
            // This query should only return one or zero result.
            $sql = "SELECT * FROM $lp_item_view_table 
                    WHERE c_id = $course_id AND lp_view_id = ".$this->lp_view_id." AND lp_item_id = ".$row['id']." 
                    ORDER BY view_count DESC ";
            if ($this->debug > 2) {
                error_log('New LP - learnpath::__construct() - Selecting item_views: ' . $sql, 0);
            }
            // Get the item status.
            $res2 = Database::query($sql);
            if (Database :: num_rows($res2) > 0) {
                // If this learnpath has already been used by this user, get his last attempt count and
                // the last item seen back into this object.
                //$max = 0;
                $row2 = Database :: fetch_array($res2);
                if ($this->debug > 2) {
                    error_log('New LP - learnpath::__construct() - Got item_view: ' . print_r($row2, true), 0);
                }

                if (is_object($this->items[$row['id']])) {
                  $this->items[$row['id']]->set_status($row2['status']);
                  if (empty ($row2['status'])) {
                      $this->items[$row['id']]->set_status($this->default_status);
                  }
                }
                //$this->attempt = $row['view_count'];
                //$this->last_item = $row['id'];
            } else { // No item has been found in lp_item_view for this view.
                // The first attempt of this user. Set attempt to 1 and last_item to 0 (first item available).
                // TODO: If the learnpath has not got attempts activated, always use attempt '1'.
                //$this->attempt = 1;
                //$this->last_item = 0;
                if (is_object($this->items[$row['id']])) {
                  $this->items[$row['id']]->set_status($this->default_status);
                }
                // Add that row to the lp_item_view table so that we have something to show in the stats page.
                $sql_ins = "INSERT INTO $lp_item_view_table (c_id, lp_item_id, lp_view_id, view_count, status) 
                	VALUES ($course_id, ".$row['id'] . "," . $this->lp_view_id . ",1,'not attempted')";
                if ($this->debug > 2) {
                    error_log('New LP - learnpath::__construct() ' . __LINE__ . ' - Inserting blank item_view : ' . $sql_ins, 0);
                }
                $res_ins = Database::query($sql_ins);
            }
            // Setting the view in the item object.
            if (is_object($this->items[$row['id']])) {
              $this->items[$row['id']]->set_lp_view($this->lp_view_id, $course_id);
            }
        }
        $this->ordered_items = $this->get_flat_ordered_items_list($this->get_id(), 0, $course_id);
        $this->max_ordered_items = 0;
        foreach ($this->ordered_items as $index => $dummy) {
            if ($index > $this->max_ordered_items && !empty($dummy)) {
                $this->max_ordered_items = $index;
            }
        }
        // TODO: Define the current item better.
        $this->first();
        if ($this->debug > 2) {
            error_log('New LP - learnpath::__construct() ' . __LINE__ . ' - End of learnpath constructor for learnpath ' . $this->get_id(), 0);
        }
    }

    /**
     * Function rewritten based on old_add_item() from Yannick Warnier. Due the fact that users can decide where the item should come, I had to overlook this function and
     * I found it better to rewrite it. Old function is still available. Added also the possibility to add a description.
     *
     * @param int $parent
     * @param int $previous
     * @param string $type
     * @param int  resource ID (ref)
     * @param string $title
     * @param string $description
     * @return int
     */
    public function add_item($parent, $previous, $type = 'dokeos_chapter', $id, $title, $description, $prerequisites = 0, $max_time_allowed = 0) {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::add_item(' . $parent . ',' . $previous . ',' . $type . ',' . $id . ',' . $title . ')', 0);
        }
        $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);
                
        $parent = intval($parent);
        $previous = intval($previous);
        $type = $this->escape_string($type);
        $id = intval($id);
        $max_time_allowed = $this->escape_string(htmlentities($max_time_allowed));
        if (empty ($max_time_allowed)) {
            $max_time_allowed = 0;
        }
        $title       = $this->escape_string($title);
        $description = $this->escape_string($description);
        $sql_count = "	SELECT COUNT(id) AS num
                        FROM $tbl_lp_item
                        WHERE c_id = $course_id AND lp_id = " . $this->get_id() . " AND parent_item_id = " . $parent;

        $res_count = Database::query($sql_count);
        $row = Database :: fetch_array($res_count);
        $num = $row['num'];

        if ($num > 0) {
            if ($previous == 0) {
                $sql = "SELECT id, next_item_id, display_order
                           FROM " . $tbl_lp_item . "
                           WHERE   c_id = $course_id AND
                                   lp_id = " . $this->get_id() . " AND
                                   parent_item_id = " . $parent . " AND
                                   previous_item_id = 0 OR previous_item_id=" . $parent;
                $result = Database::query($sql);
                $row = Database :: fetch_array($result);

                $tmp_previous = 0;
                $next = $row['id'];
                $display_order = 0;
            } else {
                $previous = (int) $previous;
                $sql = "SELECT id, previous_item_id, next_item_id, display_order
						FROM $tbl_lp_item
                        WHERE c_id = $course_id AND lp_id = " . $this->get_id() . " AND id = " . $previous;

                $result = Database::query($sql);
                $row 	= Database :: fetch_array($result);

                $tmp_previous = $row['id'];
                $next = $row['next_item_id'];

                $display_order = $row['display_order'];
            }
        } else {
            $tmp_previous = 0;
            $next = 0;
            $display_order = 0;
        }

        $new_item_id = -1;
        $id = $this->escape_string($id);
        
        if ($type == 'quiz') {
            $sql = 'SELECT SUM(ponderation)
					FROM ' . Database :: get_course_table(TABLE_QUIZ_QUESTION) . ' as quiz_question
                    INNER JOIN  ' . Database :: get_course_table(TABLE_QUIZ_TEST_QUESTION) . ' as quiz_rel_question
                    ON quiz_question.id = quiz_rel_question.question_id
                    WHERE   quiz_rel_question.exercice_id = ' . $id." AND 
	            			quiz_question.c_id = $course_id AND 
	            			quiz_rel_question.c_id = $course_id ";
            $rsQuiz = Database::query($sql);
            $max_score = Database :: result($rsQuiz, 0, 0);
        } else {
            $max_score = 100;
        }
        
        if ($prerequisites != 0) {
            $sql_ins = "INSERT INTO " . $tbl_lp_item . " (
            					c_id, 
                                lp_id, ".
                                "item_type, ".
                                "ref, ".
                                "title, ".
                                "description, ".
                                "path, ".
                                "max_score, ".
                                "parent_item_id, ".
                                "previous_item_id, ".
                                "next_item_id, ".
                                "display_order, ".
                                "prerequisite, ".
                                "max_time_allowed ".
                            ") VALUES ( 
                            	$course_id , 
                                ".$this->get_id() . ", ".
                                "'" . $type . "', ".
                                "'', ".
                                "'" . $title . "', ".
                                "'" . $description . "', ".
                                "'" . $id . "', ".
                                "'" . $max_score . "', ".
                                $parent . ", ".
                                $previous . ", ".
                                $next . ", ".
                                ($display_order +1) . ", ".
                                $prerequisites . ", ".
                                $max_time_allowed .
                            ")";
        } else {
            // Insert new item.
            $sql_ins = "
                            INSERT INTO " . $tbl_lp_item . " ( ".
            					"c_id, ".
                                "lp_id, ".
                                "item_type, ".
                                "ref, ".
                                "title, ".
                                "description, ".
                                "path, ".
                                "max_score, ".
                                "parent_item_id, ".
                                "previous_item_id, ".
                                "next_item_id, ".
                                "display_order, ".
                                "max_time_allowed ".
                            ") VALUES (".
            					$course_id. ",".
                                $this->get_id() . ",".
                                "'" . $type . "',".
                                "'',".
                                "'" . $title . "',".
                                "'" . $description . "',".
                                "'" . $id . "',".
                                "'" . $max_score . "',".
                                $parent . ",".
                                $previous . ",".
                                $next . ",".
                                ($display_order +1) . ",".
                                $max_time_allowed .
                            ")";
        }

        if ($this->debug > 2) {
            error_log('New LP - Inserting dokeos_chapter: ' . $sql_ins, 0);
        }

        $res_ins = Database::query($sql_ins);

        if ($res_ins > 0) {
            $new_item_id = Database :: insert_id($res_ins);

            // Update the item that should come after the new item.
            $sql_update_next = "
                            UPDATE " . $tbl_lp_item . "
                            SET previous_item_id = " . $new_item_id . "
                            WHERE c_id = $course_id AND id = " . $next;

            $res_update_next = Database::query($sql_update_next);

            // Update the item that should be before the new item.
            $sql_update_previous = "
                            UPDATE " . $tbl_lp_item . "
                            SET next_item_id = " . $new_item_id . "
                            WHERE c_id = $course_id AND id = " . $tmp_previous;

            $res_update_previous = Database::query($sql_update_previous);

            // Update all the items after the new item.
            $sql_update_order = "
                            UPDATE " . $tbl_lp_item . "
                            SET display_order = display_order + 1
                            WHERE
                                c_id = $course_id AND 
                                lp_id = " . $this->get_id() . " AND
                                id <> " . $new_item_id . " AND
                                parent_item_id = " . $parent . " AND
                                display_order > " . $display_order;

            $res_update_previous = Database::query($sql_update_order);

            // Update the item that should come after the new item.
            $sql_update_ref = "UPDATE " . $tbl_lp_item . "
                               SET ref = " . $new_item_id . "
                               WHERE c_id = $course_id AND id = " . $new_item_id;
            Database::query($sql_update_ref);

        }

        // Upload audio.
        if (!empty ($_FILES['mp3']['name'])) {
            // Create the audio folder if it does not exist yet.
            global $_course;
            $filepath = api_get_path(SYS_COURSE_PATH) . $_course['path'] . '/document/';
            if (!is_dir($filepath . 'audio')) {
                mkdir($filepath . 'audio', api_get_permissions_for_new_directories());
                $audio_id = add_document($_course, '/audio', 'folder', 0, 'audio');
                api_item_property_update($_course, TOOL_DOCUMENT, $audio_id, 'FolderCreated', api_get_user_id(), null, null, null, null, api_get_session_id());
            }

            // Upload the file in the documents tool.
            include_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
            $file_path = handle_uploaded_document($_course, $_FILES['mp3'], api_get_path(SYS_COURSE_PATH) . $_course['path'] . '/document', '/audio', api_get_user_id(), '', '', '', '', '', false);

            // Getting the filename only.
            $file_components = explode('/', $file_path);
            $file = $file_components[count($file_components) - 1];

            // Store the mp3 file in the lp_item table.
            $sql_insert_audio = "UPDATE $tbl_lp_item SET audio = '" . Database :: escape_string($file) . "' WHERE id = '" . Database :: escape_string($new_item_id) . "'";
            Database::query($sql_insert_audio);
        }
        return $new_item_id;
    }

    /**
     * Static admin function allowing addition of a learnpath to a course.
     * @param	string	Course code
     * @param	string	Learnpath name
     * @param	string	Learnpath description string, if provided
     * @param	string	Type of learnpath (default = 'guess', others = 'dokeos', 'aicc',...)
     * @param	string	Type of files origin (default = 'zip', others = 'dir','web_dir',...)
     * @param	string	Zip file containing the learnpath or directory containing the learnpath
     * @return	integer	The new learnpath ID on success, 0 on failure
     */
    public function add_lp($course, $name, $description = '', $learnpath = 'guess', $origin = 'zip', $zipname = '', $publicated_on = '', $expired_on = '') {
        global $charset;
        $course_id = api_get_course_int_id();
        $tbl_lp = Database :: get_course_table(TABLE_LP_MAIN);        
        // Check course code exists.
        // Check lp_name doesn't exist, otherwise append something.
        $i = 0;
        $name = learnpath :: escape_string($name);

        // Session id.
        $session_id = api_get_session_id();
        
        $check_name = "SELECT * FROM $tbl_lp WHERE c_id = $course_id AND name = '$name'";
        //if ($this->debug > 2) { error_log('New LP - Checking the name for new LP: '.$check_name, 0); }
        $res_name = Database::query($check_name);

        if ($publicated_on == '0000-00-00 00:00:00' || empty($publicated_on)) {
            //by default the publication date is the same that the creation date
            //The behaviour above was changed due BT#2800
        	global $_custom;
        	if (isset($_custom['lps_hidden_when_no_start_date']) && $_custom['lps_hidden_when_no_start_date']) {
            	$publicated_on = '';
        	} else {
        		$publicated_on = api_get_utc_datetime();
        	}
        } else {
            $publicated_on   = Database::escape_string(api_get_utc_datetime($publicated_on));
        }

        if ($expired_on == '0000-00-00 00:00:00' || empty($expired_on)) {
            $expired_on = '';
        } else {
            $expired_on   = Database::escape_string(api_get_utc_datetime($expired_on));
        }
        

        while (Database :: num_rows($res_name)) {
            // There is already one such name, update the current one a bit.
            $i++;
            $name = $name . ' - ' . $i;
            $check_name = "SELECT * FROM $tbl_lp WHERE c_id = $course_id AND name = '$name'";
            //if ($this->debug > 2) { error_log('New LP - Checking the name for new LP: '.$check_name, 0); }
            $res_name = Database::query($check_name);
        }
        // New name does not exist yet; keep it.
        // Escape description.
        $description = learnpath :: escape_string(api_htmlentities($description, ENT_QUOTES, $charset)); // Kevin: added htmlentities().
        $type = 1;
        switch ($learnpath) {
            case 'guess':
                break;
            case 'dokeos':
            case 'chamilo':
                $type = 1;
                break;
            case 'aicc':
                break;
        }
        switch ($origin) {
            case 'zip':
                // Check zipname string. If empty, we are currently creating a new Chamilo learnpath.
                break;
            case 'manual':
            default:
                $get_max = "SELECT MAX(display_order) FROM $tbl_lp WHERE c_id = $course_id";
                $res_max = Database::query($get_max);
                if (Database :: num_rows($res_max) < 1) {
                    $dsp = 1;
                } else {
                    $row = Database :: fetch_array($res_max);
                    $dsp = $row[0] + 1;
                }
                
                $sql_insert = "INSERT INTO $tbl_lp (c_id, lp_type,name,description,path,default_view_mod, default_encoding,display_order,content_maker,content_local,js_lib,session_id, created_on, publicated_on, expired_on) " .
                              "VALUES ($course_id, $type,'$name','$description','','embedded','UTF-8','$dsp','Chamilo','local','','".$session_id."', '".api_get_utc_datetime()."' , '".$publicated_on."' , '".$expired_on."')";

                $res_insert = Database::query($sql_insert);
                $id = Database :: insert_id();
                if ($id > 0) {
                    // Insert into item_property.
                    api_item_property_update(api_get_course_info(), TOOL_LEARNPATH, $id, 'LearnpathAdded', api_get_user_id());
                    return $id;
                }
                break;
        }
    }

    /**
     * Appends a message to the message attribute
     * @param	string	Message to append.
     */
    public function append_message($string) {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::append_message()', 0);
        }
        $this->message .= $string;
    }

    /**
     * Autocompletes the parents of an item in case it's been completed or passed
     * @param	integer	Optional ID of the item from which to look for parents
     */
    public function autocomplete_parents($item) {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::autocomplete_parents()', 0);
        }
        if (empty ($item)) {
            $item = $this->current;
        }
        $parent_id = $this->items[$item]->get_parent();
        if ($this->debug > 2) {
            error_log('New LP - autocompleting parent of item ' . $item . ' (item ' . $parent_id . ')', 0);
        }
        if (is_object($this->items[$item]) and !empty ($parent_id)) {
            // if $item points to an object and there is a parent.
            if ($this->debug > 2) {
                error_log('New LP - ' . $item . ' is an item, proceed', 0);
            }
            $current_item = & $this->items[$item];
            $parent = & $this->items[$parent_id]; // Get the parent.
            // New experiment including failed and browsed in completed status.
            $current_status = $current_item->get_status();
            if ($current_item->is_done() || $current_status == 'browsed' || $current_status == 'failed') {
                // If the current item is completed or passes or succeeded.
                $completed = true;
                if ($this->debug > 2) {
                    error_log('New LP - Status of current item is alright', 0);
                }
                foreach ($parent->get_children() as $child) {
                    // Check all his brothers (his parent's children) for completion status.
                    if ($child != $item) {
                        if ($this->debug > 2) {
                            error_log('New LP - Looking at brother with ID ' . $child . ', status is ' . $this->items[$child]->get_status(), 0);
                        }
                        //if($this->items[$child]->status_is(array('completed','passed','succeeded')))
                        // Trying completing parents of failed and browsed items as well.
                        if ($this->items[$child]->status_is(array (
                                'completed',
                                'passed',
                                'succeeded',
                                'browsed',
                                'failed'
                            ))) {
                            // Keep completion status to true.
                        } else {
                            if ($this->debug > 2) {
                                error_log('New LP - Found one incomplete child of ' . $parent_id . ': ' . $child . ' is ' . $this->items[$child]->get_status(), 0);
                            }
                            $completed = false;
                        }
                    }
                }
                if ($completed) { // If all the children were completed:
                    $parent->set_status('completed');
                    $parent->save(false, $this->prerequisites_match($parent->get_id()));
                    $this->update_queue[$parent->get_id()] = $parent->get_status();
                    if ($this->debug > 2) {
                        error_log('New LP - Added parent to update queue ' . print_r($this->update_queue, true), 0);
                    }
                    $this->autocomplete_parents($parent->get_id()); // Recursive call.
                }
            } else {
                //error_log('New LP - status of current item is not enough to get bothered with it', 0);
            }
        }
    }

    /**
     * Autosaves the current results into the database for the whole learnpath
     */
    public function autosave() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::autosave()', 0);
        }
        // TODO: Add save operations for the learnpath itself.
    }

    /**
     * Clears the message attribute
     */
    public function clear_message() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::clear_message()', 0);
        }
        $this->message = '';
    }

    /**
     * Closes the current resource
     *
     * Stops the timer
     * Saves into the database if required
     * Clears the current resource data from this object
     * @return	boolean	True on success, false on failure
     */
    public function close() {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::close()', 0);
        }
        if (empty ($this->lp_id)) {
            $this->error = 'Trying to close this learnpath but no ID is set';
            return false;
        }
        $this->current_time_stop = time();
        if ($this->save) {
            $learnpath_view_table = Database :: get_course_table(TABLE_LP_VIEW);
            /*
            $sql = "UPDATE $learnpath_view_table " .
                    "SET " .
                    "stop_time = ".$this->current_time_stop.", " .
                    "score = ".$this->current_score.", ".
                    "WHERE learnpath_id = '".$this->lp_id."'";
            //$res = Database::query($sql);
            $res = Database::query($res);
            if (Database::affected_rows($res) < 1) {
                $this->error = 'Could not update learnpath_view table while closing learnpath';
                return false;
            }
            */
        }
        $this->ordered_items = array ();
        $this->index = 0;
        unset ($this->lp_id);
        //unset other stuff
        return true;
    }

    /**
     * Static admin function allowing removal of a learnpath
     * @param	string	Course code
     * @param	integer	Learnpath ID
     * @param	string	Whether to delete data or keep it (default: 'keep', others: 'remove')
     * @return	boolean	True on success, false on failure (might change that to return number of elements deleted)
     */
    public function delete($course = null, $id = null, $delete = 'keep') {
        $course_id = api_get_course_int_id();
        
        // TODO: Implement a way of getting this to work when the current object is not set.
        // In clear: implement this in the item class as well (abstract class) and use the given ID in queries.
        //if (empty($course)) { $course = api_get_course_id(); }
        //if (empty($id)) { $id = $this->get_id(); }
        // If an ID is specifically given and the current LP is not the same, prevent delete.
        if (!empty ($id) && ($id != $this->lp_id)) {
            return false;
        }

        $lp             = Database :: get_course_table(TABLE_LP_MAIN);
        $lp_item        = Database :: get_course_table(TABLE_LP_ITEM); // Proposed by Christophe (clefevre), see below.
        $lp_view        = Database :: get_course_table(TABLE_LP_VIEW);
        $lp_item_view   = Database :: get_course_table(TABLE_LP_ITEM_VIEW);
        
        
        //if ($this->debug > 0) { error_log('New LP - In learnpath::delete()', 0); }
        // Delete lp item id.
        foreach ($this->items as $id => $dummy) {
            //$this->items[$id]->delete();
            $sql_del_view = "DELETE FROM $lp_item_view WHERE c_id = $course_id AND lp_item_id = '" . $id . "'";
            $res_del_item_view = Database::query($sql_del_view);
        }

        // Proposed by Christophe (nickname: clefevre), see http://www.dokeos.com/forum/viewtopic.php?t=29673
        $sql_del_item = "DELETE FROM $lp_item WHERE c_id = ".$course_id." AND lp_id = " . $this->lp_id;
        $res_del_item = Database::query($sql_del_item);

        $sql_del_view = "DELETE FROM $lp_view WHERE c_id = ".$course_id." AND lp_id = " . $this->lp_id;
        //if ($this->debug > 2) { error_log('New LP - Deleting views bound to lp '.$this->lp_id.': '.$sql_del_view, 0); }
        $res_del_view = Database::query($sql_del_view);
        $this->toggle_publish($this->lp_id, 'i');
        //if ($this->debug > 2) { error_log('New LP - Deleting lp '.$this->lp_id.' of type '.$this->type, 0); }
        if ($this->type == 2 || $this->type == 3) {
            // This is a scorm learning path, delete the files as well.
            $sql = "SELECT path FROM $lp WHERE c_id = ".$course_id." AND id = " . $this->lp_id;
            $res = Database::query($sql);
            if (Database :: num_rows($res) > 0) {
                $row = Database :: fetch_array($res);
                $path = $row['path'];
                $sql = "SELECT id FROM $lp WHERE c_id = ".$course_id." AND path = '$path' AND id != " . $this->lp_id;
                $res = Database::query($sql);
                if (Database :: num_rows($res) > 0) { // Another learning path uses this directory, so don't delete it.
                    if ($this->debug > 2) {
                        error_log('New LP - In learnpath::delete(), found other LP using path ' . $path . ', keeping directory', 0);
                    }
                } else {
                    // No other LP uses that directory, delete it.
                    $course_rel_dir = api_get_course_path() . '/scorm/'; // scorm dir web path starting from /courses
                    $course_scorm_dir = api_get_path(SYS_COURSE_PATH) . $course_rel_dir; // The absolute system path for this course.
                    if ($delete == 'remove' && is_dir($course_scorm_dir . $path) and !empty ($course_scorm_dir)) {
                        if ($this->debug > 2) {
                            error_log('New LP - In learnpath::delete(), found SCORM, deleting directory: ' . $course_scorm_dir . $path, 0);
                        }
                        // Proposed by Christophe (clefevre).
                        if (strcmp(substr($path, -2), "/.") == 0) {
                            $path = substr($path, 0, -1); // Remove "." at the end.
                        }
                        //exec('rm -rf ' . $course_scorm_dir . $path); // See Bug #5208, this is not OS-portable way.
                        rmdirr($course_scorm_dir . $path);
                    }
                }
            }
        }
        $sql_del_lp = "DELETE FROM $lp WHERE c_id = ".$course_id." AND id = " . $this->lp_id;
        //if ($this->debug > 2) { error_log('New LP - Deleting lp '.$this->lp_id.': '.$sql_del_lp, 0); }
        $res_del_lp = Database::query($sql_del_lp);
        $this->update_display_order(); // Updates the display order of all lps.
        api_item_property_update(api_get_course_info(), TOOL_LEARNPATH, $this->lp_id, 'delete', api_get_user_id());

        require_once '../gradebook/lib/be.inc.php';
        
        // Delete link of gradebook tool
        //$tbl_grade_link = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
        /*$sql = 'SELECT gl.id FROM ' . $tbl_grade_link . ' gl WHERE gl.type="4" AND gl.ref_id="' . $id . '";';
        $result = Database::query($sql);
        $row = Database :: fetch_array($result, 'ASSOC');*/

        // Fixing gradebook link deleted see #5229.
        /*
        if (!empty($row['id'])) {
               $link = LinkFactory :: load($row['id']);
            if ($link[0] != null) {
                   $link[0]->delete();
            }
        }*/        
        require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';
        $link_info = is_resource_in_course_gradebook(api_get_course_id(), 4 , $id, api_get_session_id());
        if ($link_info !== false) {
            remove_resource_from_course_gradebook($link_info['id']);
        }

        if (api_get_setting('search_enabled') == 'true') {
            require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';
            $r = delete_all_values_for_item($this->cc, TOOL_LEARNPATH, $this->lp_id);
        }
    }

    /**
     * Removes all the children of one item - dangerous!
     * @param	integer	Element ID of which children have to be removed
     * @return	integer	Total number of children removed
     */
    public function delete_children_items($id) {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::delete_children_items(' . $id . ')', 0);
        }
        $num = 0;
        if (empty ($id) || $id != strval(intval($id))) {
            return false;
        }
        $lp_item = Database :: get_course_table(TABLE_LP_ITEM);
        $sql = "SELECT * FROM $lp_item WHERE c_id = ".$course_id." AND parent_item_id = $id";
        $res = Database::query($sql);
        while ($row = Database :: fetch_array($res)) {
            $num += $this->delete_children_items($row['id']);
            $sql_del = "DELETE FROM $lp_item WHERE c_id = ".$course_id." AND id = " . $row['id'];
            $res_del = Database::query($sql_del);
            $num++;
        }
        return $num;
    }

    /**
     * Removes an item from the current learnpath
     * @param	integer	Elem ID (0 if first)
     * @param	integer	Whether to remove the resource/data from the system or leave it (default: 'keep', others 'remove')
     * @return	integer	Number of elements moved
     * @todo implement resource removal
     */
    public function delete_item($id, $remove = 'keep') {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::delete_item()', 0);
        }
        // TODO: Implement the resource removal.
        if (empty ($id) || $id != strval(intval($id))) {
            return false;
        }
        // First select item to get previous, next, and display order.
        $lp_item = Database :: get_course_table(TABLE_LP_ITEM);
        $sql_sel = "SELECT * FROM $lp_item WHERE c_id = ".$course_id." AND id = $id";
        $res_sel = Database::query($sql_sel);
        if (Database :: num_rows($res_sel) < 1) {
            return false;
        }
        $row = Database :: fetch_array($res_sel);
        $previous = $row['previous_item_id'];
        $next = $row['next_item_id'];
        $display = $row['display_order'];
        $parent = $row['parent_item_id'];
        $lp = $row['lp_id'];
        // Delete children items.
        $num = $this->delete_children_items($id);
        if ($this->debug > 2) {
            error_log('New LP - learnpath::delete_item() - deleted ' . $num . ' children of element ' . $id, 0);
        }
        // Now delete the item.
        $sql_del = "DELETE FROM $lp_item WHERE c_id = $course_id AND id = $id";
        if ($this->debug > 2) {
            error_log('New LP - Deleting item: ' . $sql_del, 0);
        }
        $res_del = Database::query($sql_del);
        // Now update surrounding items.
        $sql_upd = "UPDATE $lp_item SET next_item_id = $next WHERE c_id = ".$course_id." AND id = $previous";
        $res_upd = Database::query($sql_upd);
        $sql_upd = "UPDATE $lp_item SET previous_item_id = $previous WHERE c_id = ".$course_id." AND id = $next";
        $res_upd = Database::query($sql_upd);
        // Now update all following items with new display order.
        $sql_all = "UPDATE $lp_item SET display_order = display_order-1 WHERE c_id = ".$course_id." AND lp_id = $lp AND parent_item_id = $parent AND display_order > $display";
        $res_all = Database::query($sql_all);

        //Removing prerequisites since the item will not longer exist
        $sql_all = "UPDATE $lp_item SET prerequisite = '' WHERE c_id = ".$course_id." AND prerequisite = $id";
        $res_all = Database::query($sql_all);

        // Remove from search engine if enabled.
        if (api_get_setting('search_enabled') == 'true') {
            $tbl_se_ref = Database :: get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
            $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s AND ref_id_second_level=%d LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $this->cc, TOOL_LEARNPATH, $lp, $id);
            $res = Database::query($sql);
            if (Database :: num_rows($res) > 0) {
                $row2 = Database :: fetch_array($res);
                require_once api_get_path(LIBRARY_PATH).'search/DokeosIndexer.class.php';
                $di = new DokeosIndexer();
                $di->remove_document((int) $row2['search_did']);
            }
            $sql = 'DELETE FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s AND ref_id_second_level=%d LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $this->cc, TOOL_LEARNPATH, $lp, $id);
            Database::query($sql);
        }
    }

    /**
     * Updates an item's content in place
     * @param	integer	Element ID
     * @param	integer	Parent item ID
     * @param	integer Previous item ID
     * @param   string	Item title
     * @param   string  Item description
     * @param   string  Prerequisites (optional)
     * @param   string  Indexing terms (optional)
     * @param   array   The array resulting of the $_FILES[mp3] element
     * @return	boolean	True on success, false on error
     */
    public function edit_item($id, $parent, $previous, $title, $description, $prerequisites = 0, $audio = null, $max_time_allowed = 0) {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::edit_item()', 0);
        }
        if (empty ($max_time_allowed)) {
            $max_time_allowed = 0;
        }
        if (empty ($id) || ($id != strval(intval($id))) || empty ($title)) {
            return false;
        }

        $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);
        $sql_select = "SELECT * FROM " . $tbl_lp_item . " WHERE c_id = ".$course_id." AND id = " . $id;
        $res_select = Database::query($sql_select);
        $row_select = Database :: fetch_array($res_select);
        $audio_update_sql = '';
        if (is_array($audio) && !empty ($audio['tmp_name']) && $audio['error'] === 0) {
            // Create the audio folder if it does not exist yet.
            global $_course;
            $filepath = api_get_path(SYS_COURSE_PATH) . $_course['path'] . '/document/';
            if (!is_dir($filepath . 'audio')) {
                mkdir($filepath . 'audio', api_get_permissions_for_new_directories());
                $audio_id = add_document($_course, '/audio', 'folder', 0, 'audio');
                api_item_property_update($_course, TOOL_DOCUMENT, $audio_id, 'FolderCreated', api_get_user_id(), null, null, null, null, api_get_session_id());
            }

            // Upload file in documents.
            $pi = pathinfo($audio['name']);
            if ($pi['extension'] == 'mp3') {
                $c_det = api_get_course_info($this->cc);
                $bp = api_get_path(SYS_COURSE_PATH) . $c_det['path'] . '/document';
                $path = handle_uploaded_document($c_det, $audio, $bp, '/audio', api_get_user_id(), 0, null, '', 0, 'rename', false, 0);
                $path = substr($path, 7);
                // Update reference in lp_item - audio path is the path from inside de document/audio/ dir.
                $audio_update_sql = ", audio = '" . Database :: escape_string($path) . "' ";
            }
        }

        $same_parent = ($row_select['parent_item_id'] == $parent) ? true : false;
        $same_previous = ($row_select['previous_item_id'] == $previous) ? true : false;

        // TODO: htmlspecialchars to be checked for encoding related problems.
        if ($same_parent && $same_previous) {
            // Only update title and description.
            $sql_update = " UPDATE " . $tbl_lp_item . "
                            SET title = '" . $this->escape_string($title) . "',
                                prerequisite = '" . $prerequisites . "',
                                description = '" . $this->escape_string($description) . "'
                                " . $audio_update_sql . ",
                                max_time_allowed = '" . $this->escape_string($max_time_allowed) . "'
                            WHERE c_id = ".$course_id." AND id = " . $id;
            $res_update = Database::query($sql_update);
        } else {
            $old_parent = $row_select['parent_item_id'];
            $old_previous = $row_select['previous_item_id'];
            $old_next = $row_select['next_item_id'];
            $old_order = $row_select['display_order'];
            $old_prerequisite = $row_select['prerequisite'];
            $old_max_time_allowed = $row_select['max_time_allowed'];

            /* BEGIN -- virtually remove the current item id */
            /* for the next and previous item it is like the current item doesn't exist anymore */

            if ($old_previous != 0) {
                $sql_update_next = "
                                    UPDATE " . $tbl_lp_item . "
                                    SET next_item_id = " . $old_next . "
                                    WHERE c_id = ".$course_id." AND id = " . $old_previous;
                $res_update_next = Database::query($sql_update_next);
                //echo '<p>' . $sql_update_next . '</p>';
            }

            if ($old_next != 0) {
                $sql_update_previous = "
                                    UPDATE " . $tbl_lp_item . "
                                    SET previous_item_id = " . $old_previous . "
                                    WHERE c_id = ".$course_id." AND id = " . $old_next;
                $res_update_previous = Database::query($sql_update_previous);

                //echo '<p>' . $sql_update_previous . '</p>';
            }

            // display_order - 1 for every item with a display_order bigger then the display_order of the current item.
            $sql_update_order = "
                            UPDATE " . $tbl_lp_item . "
                            SET display_order = display_order - 1
                            WHERE
                                c_id = ".$course_id." AND 
                                display_order > " . $old_order . " AND lp_id = " . $this->lp_id . " AND
                                parent_item_id = " . $old_parent;
            $res_update_order = Database::query($sql_update_order);

            //echo '<p>' . $sql_update_order . '</p>';

            /* END -- virtually remove the current item id */

            /* BEGIN -- update the current item id to his new location */

            if ($previous == 0) {
                // Select the data of the item that should come after the current item.
                $sql_select_old = "SELECT id, display_order
                                    FROM " . $tbl_lp_item . "
                                    WHERE
                                        c_id = ".$course_id." AND 
                                        lp_id = " . $this->lp_id . " AND
                                        parent_item_id = " . $parent . " AND
                                        previous_item_id = " . $previous;
                $res_select_old = Database::query($sql_select_old);
                $row_select_old = Database :: fetch_array($res_select_old);

                //echo '<p>' . $sql_select_old . '</p>';

                // If the new parent didn't have children before.
                if (Database :: num_rows($res_select_old) == 0) {
                    $new_next = 0;
                    $new_order = 1;
                } else {
                    $new_next = $row_select_old['id'];
                    $new_order = $row_select_old['display_order'];
                }

                //echo 'New next_item_id of current item: ' . $new_next . '<br />';
                //echo 'New previous_item_id of current item: ' . $previous . '<br />';
                //echo 'New display_order of current item: ' . $new_order . '<br />';

            } else {
                // Select the data of the item that should come before the current item.
                $sql_select_old = " SELECT next_item_id, display_order
                                    FROM " . $tbl_lp_item . "
                                    WHERE c_id = ".$course_id." AND id = " . $previous;
                $res_select_old = Database::query($sql_select_old);
                $row_select_old = Database :: fetch_array($res_select_old);

                //echo '<p>' . $sql_select_old . '</p>';

                //echo 'New next_item_id of current item: ' . $row_select_old['next_item_id'] . '<br />';
                //echo 'New previous_item_id of current item: ' . $previous . '<br />';
                //echo 'New display_order of current item: ' . ($row_select_old['display_order'] + 1) . '<br />';

                $new_next = $row_select_old['next_item_id'];
                $new_order = $row_select_old['display_order'] + 1;
            }

            // TODO: htmlspecialchars to be checked for encoding related problems.
            // Update the current item with the new data.
            $sql_update = "UPDATE " . $tbl_lp_item . "
                            SET
                                title = '" . $this->escape_string($title) . "',
                                description = '" . $this->escape_string($description) . "',
                                parent_item_id = " . $parent . ",
                                previous_item_id = " . $previous . ",
                                next_item_id = " . $new_next . ",
                                display_order = " . $new_order . "
                                " . $audio_update_sql . "
                            WHERE c_id = ".$course_id." AND id = " . $id;
            $res_update_next = Database::query($sql_update);
            //echo '<p>' . $sql_update . '</p>';

            if ($previous != 0) {
                // Update the previous item's next_item_id.
                $sql_update_previous = "
                                    UPDATE " . $tbl_lp_item . "
                                    SET next_item_id = " . $id . "
                                    WHERE c_id = ".$course_id." AND id = " . $previous;
                $res_update_next = Database::query($sql_update_previous);
                //echo '<p>' . $sql_update_previous . '</p>';
            }

            if ($new_next != 0) {
                // Update the next item's previous_item_id.
                $sql_update_next = "
                                    UPDATE " . $tbl_lp_item . "
                                    SET previous_item_id = " . $id . "
                                    WHERE c_id = ".$course_id." AND id = " . $new_next;
                $res_update_next = Database::query($sql_update_next);
                //echo '<p>' . $sql_update_next . '</p>';
            }

            if ($old_prerequisite != $prerequisites) {
                $sql_update_next = "
                                    UPDATE " . $tbl_lp_item . "
                                    SET prerequisite = " . $prerequisites . "
                                    WHERE c_id = ".$course_id." AND id = " . $id;
                $res_update_next = Database::query($sql_update_next);
            }

            if ($old_max_time_allowed != $max_time_allowed) {
                $sql_update_max_time_allowed = "
                                    UPDATE " . $tbl_lp_item . "
                                    SET max_time_allowed = " . $max_time_allowed . "
                                    WHERE c_id = ".$course_id." AND id = " . $id;
                $res_update_max_time_allowed = Database::query($sql_update_max_time_allowed);
            }

            // Update all the items with the same or a bigger display_order than the current item.
            $sql_update_order = "
                               UPDATE " . $tbl_lp_item . "
                               SET display_order = display_order + 1
                               WHERE
                                   c_id = ".$course_id." AND 
                                   lp_id = " . $this->get_id() . " AND
                                   id <> " . $id . " AND
                                   parent_item_id = " . $parent . " AND
                                   display_order >= " . $new_order;

            $res_update_next = Database::query($sql_update_order);
        }
    }

    /**
     * Updates an item's prereq in place
     * @param	integer	Element ID
     * @param	string	Prerequisite Element ID
     * @param	string	Prerequisite item type
     * @param	string	Prerequisite min score
     * @param	string	Prerequisite max score
     * @return	boolean	True on success, false on error
     */
    public function edit_item_prereq($id, $prerequisite_id, $mastery_score = 0, $max_score = 100) {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::edit_item_prereq(' . $id . ',' . $prerequisite_id . ',' . $mastery_score . ',' . $max_score . ')', 0);
        }

        if (empty ($id) or ($id != strval(intval($id))) or empty ($prerequisite_id)) {
            return false;
        }

        $prerequisite_id = $this->escape_string($prerequisite_id);

        $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);

        if (!is_numeric($mastery_score) || $mastery_score < 0)
            $mastery_score = 0;

        if (!is_numeric($max_score) || $max_score < 0)
            $max_score = 100;

        if ($mastery_score > $max_score)
            $max_score = $mastery_score;

        if (!is_numeric($prerequisite_id))
            $prerequisite_id = 'NULL';

        $sql_upd = " UPDATE " . $tbl_lp_item . "
                     SET prerequisite = " . $prerequisite_id . " WHERE c_id = ".$course_id." AND id = " . $id;
        $res_upd = Database::query($sql_upd);

        if ($prerequisite_id != 'NULL' && $prerequisite_id != '') {
            $sql_upd = " UPDATE " . $tbl_lp_item . " SET
                         mastery_score = " . $mastery_score .
                         //", max_score = " . $max_score . " " . // Max score cannot be changed in the form anyway - see display_item_prerequisites_form().
                        " WHERE c_id = ".$course_id." AND ref = '" . $prerequisite_id . "'"; // Will this be enough to ensure unicity?
            $res_upd = Database::query($sql_upd);
        }
        // TODO: Update the item object (can be ignored for now because refreshed).
        return true;
    }

    /**
     * Escapes a string with the available database escape function
     * @param	string	String to escape
     * @return	string	String escaped
     */
    public function escape_string($string) {
        //if ($this->debug > 0) { error_log('New LP - In learnpath::escape_string('.$string.')', 0); }
        return Database :: escape_string($string);
    }

    /**
     * Static admin function exporting a learnpath into a zip file
     * @param	string	Export type (scorm, zip, cd)
     * @param	string	Course code
     * @param	integer Learnpath ID
     * @param	string	Zip file name
     * @return	string	Zip file path (or false on error)
     */
    public function export_lp($type, $course, $id, $zipname) {
        $course_id = api_get_course_int_id();
        //if ($this->debug > 0) { error_log('New LP - In learnpath::export_lp()', 0); }
        if (empty($type) || empty($course) || empty($id) || empty($zipname)) {
            return false;
        }
        $url = '';
        switch ($type) {
            case 'scorm':
                break;
            case 'zip':
                break;
            case 'cdrom':
                break;
        }
        return $url;
    }

    /**
     * Gets all the chapters belonging to the same parent as the item/chapter given
     * Can also be called as abstract method
     * @param	integer	Item ID
     * @return	array	A list of all the "brother items" (or an empty array on failure)
     */
    public function get_brother_chapters($id) {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_brother_chapters()', 0);
        }

        if (empty ($id) OR $id != strval(intval($id))) {
            return array ();
        }

        $lp_item = Database :: get_course_table(TABLE_LP_ITEM);
        $sql_parent = "SELECT * FROM $lp_item WHERE c_id = ".$course_id." AND id = $id AND item_type='dokeos_chapter'";
        $res_parent = Database::query($sql_parent);
        if (Database :: num_rows($res_parent) > 0) {
            $row_parent = Database :: fetch_array($res_parent);
            $parent = $row_parent['parent_item_id'];
            $sql_bros = "SELECT * FROM $lp_item WHERE c_id = ".$course_id." AND parent_item_id = $parent AND id = $id AND item_type='dokeos_chapter' ORDER BY display_order";
            $res_bros = Database::query($sql_bros);
            $list = array ();
            while ($row_bro = Database :: fetch_array($res_bros)) {
                $list[] = $row_bro;
            }
            return $list;
        }
        return array ();
    }

    /**
     * Gets all the items belonging to the same parent as the item given
     * Can also be called as abstract method
     * @param	integer	Item ID
     * @return	array	A list of all the "brother items" (or an empty array on failure)
     */
    public function get_brother_items($id) {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_brother_items(' . $id . ')', 0);
        }

        if (empty ($id) OR $id != strval(intval($id))) {
            return array ();
        }

        $lp_item = Database :: get_course_table(TABLE_LP_ITEM);
        $sql_parent = "SELECT * FROM $lp_item WHERE c_id = $course_id AND id = $id";
        $res_parent = Database::query($sql_parent);
        if (Database :: num_rows($res_parent) > 0) {
            $row_parent = Database :: fetch_array($res_parent);
            $parent = $row_parent['parent_item_id'];
            $sql_bros = "SELECT * FROM $lp_item WHERE c_id = ".$course_id." AND parent_item_id = $parent ORDER BY display_order";
            $res_bros = Database::query($sql_bros);
            $list = array ();
            while ($row_bro = Database :: fetch_array($res_bros)) {
                $list[] = $row_bro;
            }
            return $list;
        }
        return array ();
    }

    /**
     * Get the specific prefix index terms of this learning path
     * @return  array Array of terms
     */
    public function get_common_index_terms_by_prefix($prefix) {
        require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';
        $terms = get_specific_field_values_list_by_prefix($prefix, $this->cc, TOOL_LEARNPATH, $this->lp_id);
        $prefix_terms = array();
        if (!empty($terms)) {
            foreach ($terms as $term) {
                $prefix_terms[] = $term['value'];
            }
        }
        return $prefix_terms;
    }

    /**
     * Gets the number of items currently completed
     * @return integer The number of items currently completed
     */
    public function get_complete_items_count() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_complete_items_count()', 0);
        }
        $i = 0;
        foreach ($this->items as $id => $dummy) {
            // Trying failed and browsed considered "progressed" as well.
            if ($this->items[$id]->status_is(array (
                    'completed',
                    'passed',
                    'succeeded',
                    'browsed',
                    'failed'
                )) && $this->items[$id]->get_type() != 'dokeos_chapter' && $this->items[$id]->get_type() != 'dir') {
                $i++;
            }
        }
        return $i;
    }

    /**
     * Gets the current item ID
     * @return	integer	The current learnpath item id
     */
    public function get_current_item_id() {
        $current = 0;
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_current_item_id()', 0);
        }
        if (!empty ($this->current)) {
            $current = $this->current;
        }
        if ($this->debug > 2) {
            error_log('New LP - In learnpath::get_current_item_id() - Returning ' . $current, 0);
        }
        return $current;
    }

    /**
     * Force to get the first learnpath item id
     * @return	integer	The current learnpath item id
     */
    public function get_first_item_id() {
        $current = 0;
        if (is_array($this->ordered_items)) {
            $current = $this->ordered_items[0];
        }
        return $current;
    }

    /**
     * Gets the total number of items available for viewing in this SCORM
     * @return	integer	The total number of items
     */
    public function get_total_items_count() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_total_items_count()', 0);
        }
        return count($this->items);
    }

    /**
     * Gets the total number of items available for viewing in this SCORM but without chapters
     * @return	integer	The total no-chapters number of items
     */
    public function get_total_items_count_without_chapters() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_total_items_count_without_chapters()', 0);
        }
        $total = 0;
        foreach ($this->items as $temp => $temp2) {
            if (!in_array($temp2->get_type(), array (
                    'dokeos_chapter',
                    'chapter',
                    'dir'
                )))
                $total++;
        }
        return $total;
    }

    /**
     * Gets the first element URL.
     * @return	string	URL to load into the viewer
     */
    public function first() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::first()', 0);
        }
        // Test if the last_item_seen exists and is not a dir.
        if (count($this->ordered_items) == 0) {
            $this->index = 0;
        }
        if (!empty($this->last_item_seen) && !empty($this->items[$this->last_item_seen]) && $this->items[$this->last_item_seen]->get_type() != 'dir' && $this->items[$this->last_item_seen]->get_type() != 'dokeos_chapter' && !$this->items[$this->last_item_seen]->is_done()) {
            if ($this->debug > 2) {
                error_log('New LP - In learnpath::first() - Last item seen is ' . $this->last_item_seen . ' of type ' . $this->items[$this->last_item_seen]->get_type(), 0);
            }
            $index = -1;
            foreach ($this->ordered_items as $myindex => $item_id) {
                if ($item_id == $this->last_item_seen) {
                    $index = $myindex;
                    break;
                }
            }
            if ($index == -1) {
                // Index hasn't changed, so item not found - panic (this shouldn't happen).
                if ($this->debug > 2) {
                    error_log('New LP - Last item (' . $this->last_item_seen . ') was found in items but not in ordered_items, panic!', 0);
                }
                return false;
            } else {
                $this->last = $this->last_item_seen;
                $this->current = $this->last_item_seen;
                $this->index = $index;
            }
        } else {
            if ($this->debug > 2) {
                error_log('New LP - In learnpath::first() - No last item seen', 0);
            }
            $index = 0;
            // Loop through all ordered items and stop at the first item that is
            // not a directory *and* that has not been completed yet.
            while (!empty($this->ordered_items[$index]) AND is_a($this->items[$this->ordered_items[$index]], 'learnpathItem') AND ($this->items[$this->ordered_items[$index]]->get_type() == 'dir' OR $this->items[$this->ordered_items[$index]]->get_type() == 'dokeos_chapter' OR $this->items[$this->ordered_items[$index]]->is_done() === true) AND $index < $this->max_ordered_items) {
                $index++;
            }
            $this->last = $this->current;
            // current is
            $this->current = $this->ordered_items[$index];
            $this->index = $index;
            if ($this->debug > 2) {
                error_log('New LP - In learnpath::first() - No last item seen. New last = ' . $this->last . '(' . $this->ordered_items[$index] . ')', 0);
            }
        }
        if ($this->debug > 2) {
            error_log('New LP - In learnpath::first() - First item is ' . $this->get_current_item_id());
        }
    }

    /**
     * Gets the information about an item in a format usable as JavaScript to update
     * the JS API by just printing this content into the <head> section of the message frame
     * @param	integer		Item ID
     * @return	string
     */
    public function get_js_info($item_id = '') {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_js_info(' . $item_id . ')', 0);
        }

        $info = '';
        $item_id = $this->escape_string($item_id);

        if (!empty($item_id) && is_object($this->items[$item_id])) {
            //if item is defined, return values from DB
            $oItem = $this->items[$item_id];
            $info .= '<script language="javascript">';
            $info .= "top.set_score(" . $oItem->get_score() . ");\n";
            $info .= "top.set_max(" . $oItem->get_max() . ");\n";
            $info .= "top.set_min(" . $oItem->get_min() . ");\n";
            $info .= "top.set_lesson_status('" . $oItem->get_status() . "');";
            $info .= "top.set_session_time('" . $oItem->get_scorm_time('js') . "');";
            $info .= "top.set_suspend_data('" . $oItem->get_suspend_data() . "');";
            $info .= "top.set_saved_lesson_status('" . $oItem->get_status() . "');";
            $info .= "top.set_flag_synchronized();";
            $info .= '</script>';
            if ($this->debug > 2) {
                error_log('New LP - in learnpath::get_js_info(' . $item_id . ') - returning: ' . $info, 0);
            }
            return $info;

        } else {

            // If item_id is empty, just update to default SCORM data.
            $info .= '<script language="javascript">';
            $info .= "top.set_score(" . learnpathItem :: get_score() . ");\n";
            $info .= "top.set_max(" . learnpathItem :: get_max() . ");\n";
            $info .= "top.set_min(" . learnpathItem :: get_min() . ");\n";
            $info .= "top.set_lesson_status('" . learnpathItem :: get_status() . "');";
            $info .= "top.set_session_time('" . learnpathItem :: get_scorm_time('js') . "');";
            $info .= "top.set_suspend_data('" . learnpathItem :: get_suspend_data() . "');";
            $info .= "top.set_saved_lesson_status('" . learnpathItem :: get_status() . "');";
            $info .= "top.set_flag_synchronized();";
            $info .= '</script>';
            if ($this->debug > 2) {
                error_log('New LP - in learnpath::get_js_info(' . $item_id . ') - returning: ' . $info, 0);
            }
            return $info;
        }
    }

    /**
     * Gets the js library from the database
     * @return	string	The name of the javascript library to be used
     */
    public function get_js_lib() {
        $lib = '';
        if (!empty ($this->js_lib)) {
            $lib = $this->js_lib;
        }
        return $lib;
    }

    /**
     * Gets the learnpath database ID
     * @return	integer	Learnpath ID in the lp table
     */
    public function get_id() {
        //if ($this->debug > 0) { error_log('New LP - In learnpath::get_id()', 0); }
        if (!empty ($this->lp_id)) {
            return $this->lp_id;
        } else {
            return 0;
        }
    }

    /**
     * Gets the last element URL.
     * @return string URL to load into the viewer
     */
    public function get_last() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_last()', 0);
        }
        $this->index = count($this->ordered_items) - 1;
        return $this->ordered_items[$this->index];
    }

    /**
     * Gets the navigation bar for the learnpath display screen
     * @return	string	The HTML string to use as a navigation bar
     */
    public function get_navigation_bar() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_navigation_bar()', 0);
        }

        // TODO: Find a good value for the following variables.
        $file = '';
        $openDir = '';
        $edoceo = '';
        $time = 0;
        $navbar = '';
        $RequestUri = '';
        $mycurrentitemid = $this->get_current_item_id();
        if ($this->mode == 'fullscreen') {
            $navbar = '
                  <div class="buttons">
                    <a href="lp_controller.php?action=stats" onClick="window.parent.API.save_asset();return true;" target="content_name_blank" title="stats" id="stats_link"><img border="0" src="../img/lp_stats.gif" title="' . get_lang('Reporting') . '"></a>
                    <a href="" onClick="switch_item(' . $mycurrentitemid . ',\'previous\');return false;" title="previous"><img border="0" src="../img/lp_leftarrow.gif" title="' . get_lang('ScormPrevious') . '"></a>
                    <a href="" onClick="switch_item(' . $mycurrentitemid . ',\'next\');return false;" title="next"  ><img border="0" src="../img/lp_rightarrow.gif" title="' . get_lang('ScormNext') . '"></a>.
                    <a href="lp_controller.php?action=mode&mode=embedded" target="_top" title="embedded mode"><img border="0" src="../img/view_choose.gif" title="'.get_lang('ScormExitFullScreen').'"></a>            
                  </div>';

        } else {
            $navbar = '
                  <div class="buttons">
                    <a href="lp_controller.php?action=stats" onClick="window.parent.API.save_asset();return true;" target="content_name" title="stats" id="stats_link"><img border="0" src="../img/lp_stats.gif" title="' . get_lang('Reporting') . '"></a>
                    <a href="" onClick="switch_item(' . $mycurrentitemid . ',\'previous\');return false;" title="previous"><img border="0" src="../img/lp_leftarrow.gif" title="' . get_lang('ScormPrevious') . '"></a>
                    <a href="" onClick="switch_item(' . $mycurrentitemid . ',\'next\');return false;" title="next"  ><img border="0" src="../img/lp_rightarrow.gif" title="' . get_lang('ScormNext') . '"></a>            
                  </div>';
        }
        return $navbar;
    }

    /**
     * Gets the next resource in queue (url).
     * @return	string	URL to load into the viewer
     */
    public function get_next_index() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_next_index()', 0);
        }
        // TODO
        $index = $this->index;
        $index++;
        if ($this->debug > 2) {
            error_log('New LP - Now looking at ordered_items[' . ($index) . '] - type is ' . $this->items[$this->ordered_items[$index]]->type, 0);
        }
        while (!empty ($this->ordered_items[$index]) AND ($this->items[$this->ordered_items[$index]]->get_type() == 'dir' || $this->items[$this->ordered_items[$index]]->get_type() == 'dokeos_chapter') AND $index < $this->max_ordered_items) {
            $index++;
            if ($index == $this->max_ordered_items){
                if ($this->items[$this->ordered_items[$index]]->get_type() == 'dir' || $this->items[$this->ordered_items[$index]]->get_type() == 'dokeos_chapter') {
                    return $this->index;
                } else {
                    return $index;
                }
            }
        }
        if (empty ($this->ordered_items[$index])) {
            return $this->index;
        }
        if ($this->debug > 2) {
            error_log('New LP - index is now ' . $index, 0);
        }
        return $index;
    }

    /**
     * Gets item_id for the next element
     * @return	integer	Next item (DB) ID
     */
    public function get_next_item_id() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_next_item_id()', 0);
        }
        $new_index = $this->get_next_index();
        if (!empty ($new_index)) {
            if (isset ($this->ordered_items[$new_index])) {
                if ($this->debug > 2) {
                    error_log('New LP - In learnpath::get_next_index() - Returning ' . $this->ordered_items[$new_index], 0);
                }
                return $this->ordered_items[$new_index];
            }
        }
        if ($this->debug > 2) {
            error_log('New LP - In learnpath::get_next_index() - Problem - Returning 0', 0);
        }
        return 0;
    }

    /**
     * Returns the package type ('scorm','aicc','scorm2004','dokeos','ppt'...)
     *
     * Generally, the package provided is in the form of a zip file, so the function
     * has been written to test a zip file. If not a zip, the function will return the
     * default return value: ''
     * @param	string	the path to the file
     * @param	string 	the original name of the file
     * @return	string	'scorm','aicc','scorm2004','dokeos' or '' if the package cannot be recognized
     */
    public function get_package_type($file_path, $file_name) {

        // Get name of the zip file without the extension.
        $file_info = pathinfo($file_name);
        $filename = $file_info['basename']; // Name including extension.
        $extension = $file_info['extension']; // Extension only.

        if (!empty($_POST['ppt2lp']) && !in_array(strtolower($extension), array (
                'dll',
                'exe'
            ))) {
            return 'oogie';
        }
        if (!empty($_POST['woogie']) && !in_array(strtolower($extension), array (
                'dll',
                'exe'
            ))) {
            return 'woogie';
        }

        $file_base_name = str_replace('.' . $extension, '', $filename); // Filename without its extension.

        $zipFile = new PclZip($file_path);
        // Check the zip content (real size and file extension).
        $zipContentArray = $zipFile->listContent();
        $package_type = '';
        $at_root = false;
        $manifest = '';

        // The following loop should be stopped as soon as we found the right imsmanifest.xml (how to recognize it?).
        if (is_array($zipContentArray) && count($zipContentArray) > 0) {
            foreach ($zipContentArray as $thisContent) {
                if (preg_match('~.(php.*|phtml)$~i', $thisContent['filename'])) {
                    // New behaviour: Don't do anything. These files will be removed in scorm::import_package.
                }
                elseif (stristr($thisContent['filename'], 'imsmanifest.xml') !== false) {
                    $manifest = $thisContent['filename']; // Just the relative directory inside scorm/
                    $package_type = 'scorm';
                    break; // Exit the foreach loop.
                }
                elseif (preg_match('/aicc\//i', $thisContent['filename'])) {
                    // If found an aicc directory... (!= false means it cannot be false (error) or 0 (no match)).
                    $package_type = 'aicc';
                    //break; // Don't exit the loop, because if we find an imsmanifest afterwards, we want it, not the AICC.
                } else {
                    $package_type = '';
                }
            }
        }
        return $package_type;
    }

    /**
     * Gets the previous resource in queue (url). Also initialises time values for this viewing
     * @return string URL to load into the viewer
     */
    public function get_previous_index() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_previous_index()', 0);
        }
        $index = $this->index;
        if (isset ($this->ordered_items[$index -1])) {
            $index--;
            while (isset ($this->ordered_items[$index]) AND ($this->items[$this->ordered_items[$index]]->get_type() == 'dir' || $this->items[$this->ordered_items[$index]]->get_type() == 'dokeos_chapter')) {
                $index--;
                if ($index < 0) {
                    return $this->index;
                }
            }
        } else {
            if ($this->debug > 2) {
                error_log('New LP - get_previous_index() - there was no previous index available, reusing ' . $index, 0);
            }
            // There is no previous item.
        }
        return $index;
    }

    /**
     * Gets item_id for the next element
     * @return	integer	Previous item (DB) ID
     */
    public function get_previous_item_id() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_previous_item_id()', 0);
        }
        $new_index = $this->get_previous_index();
        return $this->ordered_items[$new_index];
    }

    /**
     * Gets the progress value from the progress_db attribute
     * @return	integer	Current progress value
     */
    public function get_progress() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_progress()', 0);
        }
        if (!empty ($this->progress_db)) {
            return $this->progress_db;
        }
        return 0;
    }

    /**
     * Gets the progress value from the progress field in the database (allows use as abstract method)
     * @param	integer	Learnpath ID
     * @param	integer	User ID
     * @param	string	Mode of display ('%','abs' or 'both')
     * @param	string	Course database name (optional, defaults to '')
     * @param	boolean	Whether to return null if no record was found (true), or 0 (false) (optional, defaults to false)
     * @return	integer	Current progress value as found in the database
     */
    public function get_db_progress($lp_id, $user_id, $mode = '%', $course_code = '', $sincere = false,$session_id = 0) {

        //if ($this->debug > 0) { error_log('New LP - In learnpath::get_db_progress()', 0); }
        $session_id = intval($session_id);
        $course_info = api_get_course_info($course_code);
        $session_condition = api_get_session_condition($session_id);
        $course_id = $course_info['real_id'];
        $table = Database :: get_course_table(TABLE_LP_VIEW);
        $sql = "SELECT * FROM $table WHERE c_id = ".$course_id." AND lp_id = $lp_id AND user_id = $user_id $session_condition";
        $res = Database::query($sql);
        $view_id = 0;
        if (Database :: num_rows($res) > 0) {
            $row = Database :: fetch_array($res);
            $progress = $row['progress'];
            $view_id = $row['id'];
        } else {
            if ($sincere) {
                return null;
            }
        }

        if (empty ($progress)) {
            $progress = '0';
        }
        
        if ($mode == '%') {
            return $progress . '%';
        } else {
            // Get the number of items completed and the number of items total.
            $tbl = Database :: get_course_table(TABLE_LP_ITEM);
            $sql = "SELECT count(*) FROM $tbl 
            		WHERE c_id = $course_id AND c_id = ".$course_id." AND lp_id = " . $lp_id . " AND item_type NOT IN('dokeos_chapter','chapter','dir')";
            $res = Database::query($sql);
            $row = Database :: fetch_array($res);
            $total = $row[0];
            $tbl_item_view = Database :: get_course_table(TABLE_LP_ITEM_VIEW);
            $tbl_item = Database :: get_course_table(TABLE_LP_ITEM);
            
            //$sql = "SELECT count(distinct(lp_item_id)) FROM $tbl WHERE lp_view_id = ".$view_id." AND status IN ('passed','completed','succeeded')";
            // Trying as also counting browsed and failed items.
            $sql = "SELECT count(distinct(lp_item_id))
                    FROM $tbl_item_view as item_view
                    INNER JOIN $tbl_item as item
                    ON item.id = item_view.lp_item_id
                    AND item_type NOT IN('dokeos_chapter','chapter','dir')
                    WHERE
                    	item_view.c_id 	= $course_id AND
                    	item.c_id 		= $course_id  AND 
                    	lp_view_id 		= " . $view_id . " AND 
            			status IN ('passed','completed','succeeded','browsed','failed')"; //echo '<br />';
            $res = Database::query($sql);
            $row = Database :: fetch_array($res);
            $completed = $row[0];
            if ($mode == 'abs') {
                return $completed . '/' . $total;
            } elseif ($mode == 'both') {
                if ($progress < ($completed / ($total ? $total : 1))) {
                    $progress = number_format(($completed / ($total ? $total : 1)) * 100, 0);
                }
                return $progress . '% (' . $completed . '/' . $total . ')';
            }
        }
        return $progress;
    }

    /**
     * Returns the HTML necessary to print a mediaplayer block inside a page
     * @return string	The mediaplayer HTML
     */
    public function get_mediaplayer($autostart='true') {
        $course_id = api_get_course_int_id();
        global $_course;
        $tbl_lp_item 		= Database :: get_course_table(TABLE_LP_ITEM);
        $tbl_lp_item_view 	= Database :: get_course_table(TABLE_LP_ITEM_VIEW);
        
        // Getting all the information about the item.
        $sql = "SELECT * FROM " . $tbl_lp_item . " as lp INNER  JOIN " . $tbl_lp_item_view . " as lp_view on lp.id = lp_view.lp_item_id " .
                "WHERE  lp.id = '" . $_SESSION['oLP']->current . "' AND 
                        lp.c_id = $course_id AND 
                        lp_view.c_id = $course_id";
        $result = Database::query($sql);
        $row 	= Database::fetch_assoc($result);
        $output = '';

        if (!empty ($row['audio'])) {

            $list = $_SESSION['oLP']->get_toc();
            $type_quiz = false;

            foreach($list as $toc) {
                if ($toc['id'] == $_SESSION['oLP']->current && ($toc['type']=='quiz') ) {
                    $type_quiz = true;
                }
            }

            if ($type_quiz) {
                if ($_SESSION['oLP']->prevent_reinit == 1) {
                    $row['status'] === 'completed' ? $autostart_audio = 'false' : $autostart_audio = 'true';
                } else {
                    $autostart_audio = $autostart;
                }
            } else {
                $autostart_audio = 'true';
            }

            // The mp3 player.
            $output  = '<div id="container">';
            $output .= '<script type="text/javascript" src="../inc/lib/mediaplayer/swfobject.js"></script>';
            $output .= '<script type="text/javascript">
                                    var s1 = new SWFObject("../inc/lib/mediaplayer/player.swf","ply","250","20","9","#FFFFFF");
                                    s1.addParam("allowscriptaccess","always");
                                        s1.addParam("flashvars","file=' . api_get_path(WEB_COURSE_PATH) . $_course['path'] . '/document/audio/' . $row['audio'] . '&autostart=' . $autostart_audio.'");
                                    s1.write("container");
						</script></div>';
        }
        return $output;
    }


    /**
     * This function checks if the learnpath is visible for student after the progress of its prerequisite is completed, and considering time availability
     * @param	int		Learnpath id
     * @param	int		Student id
     * @param   string  Course code (optional)
     * @return	bool	True if
     */
    public function is_lp_visible_for_student($lp_id, $student_id, $course = null) {
        $lp_id = (int)$lp_id;
        $course = api_get_course_info($course);
        $tbl_learnpath = Database :: get_course_table(TABLE_LP_MAIN);
        // Get current prerequisite
        $sql = "SELECT id, prerequisite, publicated_on, expired_on FROM $tbl_learnpath WHERE c_id = ".$course['real_id']." AND id = $lp_id";
        $rs  = Database::query($sql);
        $now = time();
        if (Database::num_rows($rs)>0) {
            $row = Database::fetch_array($rs, 'ASSOC');
            $prerequisite = $row['prerequisite'];           
            $is_visible = true;
            $progress = 0;
    
            if (!empty($prerequisite)) {
                $progress = self::get_db_progress($prerequisite,$student_id,'%', '', false, api_get_session_id());
                $progress = intval($progress);
                if ($progress < 100) {
                    $is_visible = false;
                }
            }
            
            // Also check the time availability of the LP   
             
            if ($is_visible) {
	            //Adding visibility reestrinctions
	            if (!empty($row['publicated_on']) && $row['publicated_on'] != '0000-00-00 00:00:00') {
	            	if ($now < api_strtotime($row['publicated_on'], 'UTC')) {
	            		//api_not_allowed();
	            		$is_visible = false;
	            	}
	            }
	            
	            //Blocking empty start times see BT#2800
	            global $_custom;
	            if (isset($_custom['lps_hidden_when_no_start_date']) && $_custom['lps_hidden_when_no_start_date']) {
		            if (empty($row['publicated_on']) || $row['publicated_on'] == '0000-00-00 00:00:00') {
		            	//api_not_allowed();
		            	$is_visible = false;
		            }
	            }
	            
	            if (!empty($row['expired_on']) && $row['expired_on'] != '0000-00-00 00:00:00') {
	            	if ($now > api_strtotime($row['expired_on'], 'UTC')) {
	            		//api_not_allowed();
	            		$is_visible = false;
	            	}
	            }
            }
            
            return $is_visible;
        }
        return false;
    }
    /**
     * Gets a progress bar for the learnpath by counting the number of items in it and the number of items
     * completed so far.
     * @param	string	Mode in which we want the values
     * @param	integer	Progress value to display (optional but mandatory if used in abstract context)
     * @param	string	Text to display near the progress value (optional but mandatory in abstract context)
     * @param	boolean true if it comes from a Diplay LP view
     * @return	string	HTML string containing the progress bar
     */
    public function get_progress_bar($mode = '', $percentage = -1, $text_add = '', $from_lp = false) {
        //if ($this->debug > 0) {error_log('New LP - In learnpath::get_progress_bar('.$mode.','.$percentage.','.$text_add.','.$from_lp.')', 0); }
        global $lp_theme_css;

        // Setting up the CSS path of the current style if exists.
        if (!empty ($lp_theme_css)) {
            $css_path = api_get_path(WEB_CODE_PATH) . 'css/' . $lp_theme_css . '/images/';
        } else {
            $css_path = '../img/';
        }

        //if ($this->debug > 0) { error_log('New LP - In learnpath::get_progress_bar()', 0); }
        if (isset($this) && is_object($this) && ($percentage == '-1' OR $text_add == '')) {
            list($percentage, $text_add) = $this->get_progress_bar_text($mode);
        }
        $text = $percentage . $text_add;
        //@todo use Display::display_progress();
        $output .= '<div class="progress progress-striped">                                    
                        <div id="progress_bar_value" class="bar" style="width: '.$text.';"></div>                                            
                    </div>
                    <div class="progresstext" id="progress_text">' . $text . '</div>';

        return $output;
    }

    /**
     * Gets the progress bar info to display inside the progress bar. Also used by scorm_api.php
     * @param	string	Mode of display (can be '%' or 'abs').abs means we display a number of completed elements per total elements
     * @param	integer	Additional steps to fake as completed
     * @return	list	Percentage or number and symbol (% or /xx)
     */
    public function get_progress_bar_text($mode = '', $add = 0) {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_progress_bar_text()', 0);
        }
        if (empty ($mode)) {
            $mode = $this->progress_bar_mode;
        }
        $total_items = $this->get_total_items_count_without_chapters();
        if ($this->debug > 2) {
            error_log('New LP - Total items available in this learnpath: ' . $total_items, 0);
        }
        $i = $this->get_complete_items_count();
        if ($this->debug > 2) {
            error_log('New LP - Items completed so far: ' . $i, 0);
        }
        if ($add != 0) {
            $i += $add;
            if ($this->debug > 2) {
                error_log('New LP - Items completed so far (+modifier): ' . $i, 0);
            }
        }
        $text = '';
        if ($i > $total_items) {
            $i = $total_items;
        }
        if ($mode == '%') {
            if ($total_items > 0) {
                $percentage = ((float) $i / (float) $total_items) * 100;
            } else {
                $percentage = 0;
            }
            $percentage = number_format($percentage, 0);
            $text = '%';
        }
        elseif ($mode == 'abs') {
            $percentage = $i;
            $text = '/' . $total_items;
        }
        return array (
            $percentage,
            $text
        );
    }

    /**
     * Gets the progress bar mode
     * @return	string	The progress bar mode attribute
     */
    public function get_progress_bar_mode() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_progress_bar_mode()', 0);
        }
        if (!empty ($this->progress_bar_mode)) {
            return $this->progress_bar_mode;
        } else {
            return '%';
        }
    }

    /**
     * Gets the learnpath proximity (remote or local)
     * @return	string	Learnpath proximity
     */
    public function get_proximity() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_proximity()', 0);
        }
        if (!empty ($this->proximity)) {
            return $this->proximity;
        } else {
            return '';
        }
    }

    /**
     * Gets the learnpath theme (remote or local)
     * @return	string	Learnpath theme
     */
    public function get_theme() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_theme()', 0);
        }
        if (!empty ($this->theme)) {
            return $this->theme;
        } else {
            return '';
        }
    }

    /**
     * Gets the learnpath session id
     * @return	string	Learnpath theme
     */
    public function get_lp_session_id() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_lp_session_id()', 0);
        }
        if (!empty ($this->lp_session_id)) {
            return $this->lp_session_id;
        } else {
            return 0;
        }
    }

    /**
     * Gets the learnpath image
     * @return	string	Web URL of the LP image
     */
    public function get_preview_image() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_preview_image()', 0);
        }
        if (!empty ($this->preview_image)) {
            return $this->preview_image;
        } else {
            return '';
        }
    }

    /**
     * Gets the learnpath author
     * @return	string	LP's author
     */
    public function get_author() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_author()', 0);
        }
        if (!empty ($this->author)) {
            return $this->author;
        } else {
            return '';
        }
    }
	/**
	 * Gets the learnpath author
	 * @return	string	LP's author
	 */
	public function get_hide_toc_frame() {
		if ($this->debug > 0) {
			error_log('New LP - In learnpath::get_author()', 0);
		}
		if (!empty ($this->hide_toc_frame)) {
			return $this->hide_toc_frame;
		} else {
			return '';
		}
	}

    /**
     * Generate a new prerequisites string for a given item. If this item was a sco and
     * its prerequisites were strings (instead of IDs), then transform those strings into
     * IDs, knowing that SCORM IDs are kept in the "ref" field of the lp_item table.
     * Prefix all item IDs that end-up in the prerequisites string by "ITEM_" to use the
     * same rule as the scorm_export() method
     * @param	integer		Item ID
     * @return	string		Prerequisites string ready for the export as SCORM
     */
    public function get_scorm_prereq_string($item_id) {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_scorm_prereq_string()', 0);
        }
        if (!is_object($this->items[$item_id])) {
            return false;
        }
        $oItem = $this->items[$item_id];
        $prereq = $oItem->get_prereq_string();
        if (empty ($prereq)) {
            return '';
        }
        if (preg_match('/^\d+$/', $prereq) && is_object($this->items[$prereq])) {	// If the prerequisite is a simple integer ID and this ID exists as an item ID,
                                                                                    // then simply return it (with the ITEM_ prefix).
            return 'ITEM_' . $prereq;
        } else {
            if (isset ($this->refs_list[$prereq])) {
                // It's a simple string item from which the ID can be found in the refs list,
                // so we can transform it directly to an ID for export.
                return 'ITEM_' . $this->refs_list[$prereq];
            } else {
                // The last case, if it's a complex form, then find all the IDs (SCORM strings)
                // and replace them, one by one, by the internal IDs (chamilo db)
                // TODO: Modify the '*' replacement to replace the multiplier in front of it
                // by a space as well.
                $find = array (
                    '&',
                    '|',
                    '~',
                    '=',
                    '<>',
                    '{',
                    '}',
                    '*',
                    '(',
                    ')'
                );
                $replace = array (
                    ' ',
                    ' ',
                    ' ',
                    ' ',
                    ' ',
                    ' ',
                    ' ',
                    ' ',
                    ' ',
                    ' '
                );
                $prereq_mod = str_replace($find, $replace, $prereq);
                $ids = split(' ', $prereq_mod);
                foreach ($ids as $id) {
                    $id = trim($id);
                    if (isset ($this->refs_list[$id])) {
                        $prereq = preg_replace('/[^a-zA-Z_0-9](' . $id . ')[^a-zA-Z_0-9]/', 'ITEM_' . $this->refs_list[$id], $prereq);
                    }
                }
                error_log('New LP - In learnpath::get_scorm_prereq_string(): returning modified string: ' . $prereq, 0);
                return $prereq;
            }
        }
    }

    /**
     * Returns the XML DOM document's node
     * @param	resource	Reference to a list of objects to search for the given ITEM_*
     * @param	string		The identifier to look for
     * @return	mixed		The reference to the element found with that identifier. False if not found
     */
    public function get_scorm_xml_node(& $children, $id) {
        for ($i = 0; $i < $children->length; $i++) {
            $item_temp = $children->item($i);
            if ($item_temp->nodeName == 'item') {
                if ($item_temp->getAttribute('identifier') == $id) {
                    return $item_temp;
                }
            }
            $subchildren = $item_temp->childNodes;
            if ($subchildren->length > 0) {
                $val = $this->get_scorm_xml_node($subchildren, $id);
                if (is_object($val)) {
                    return $val;
                }
            }
        }
        return false;
    }

    /**
     * Returns a usable array of stats related to the current learnpath and user
     * @return array	Well-formatted array containing status for the current learnpath
     */
    public function get_stats() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_stats()', 0);
        }
        // TODO
    }

    /**
     * Static method. Can be re-implemented by children. Gives an array of statistics for
     * the given course (for all learnpaths and all users)
     * @param	string	Course code
     * @return array	Well-formatted array containing status for the course's learnpaths
     */
    public function get_stats_course($course) {
        //if ($this->debug > 0) { error_log('New LP - In learnpath::get_stats_course()', 0); }
        // TODO
    }

    /**
     * Static method. Can be re-implemented by children. Gives an array of statistics for
     * the given course and learnpath (for all users)
     * @param	string	Course code
     * @param	integer	Learnpath ID
     * @return array	Well-formatted array containing status for the specified learnpath
     */
    public function get_stats_lp($course, $lp) {
        //if ($this->debug > 0) { error_log('New LP - In learnpath::get_stats_lp()', 0); }
        // TODO
    }

    /**
     * Static method. Can be re-implemented by children. Gives an array of statistics for
     * the given course, learnpath and user.
     * @param	string	Course code
     * @param	integer	Learnpath ID
     * @param	integer	User ID
     * @return array	Well-formatted array containing status for the specified learnpath and user
     */
    public function get_stats_lp_user($course, $lp, $user) {
        //if ($this->debug > 0) { error_log('New LP - In learnpath::get_stats_lp_user()', 0); }
        // TODO
    }

    /**
     * Static method. Can be re-implemented by children. Gives an array of statistics for
     * the given course and learnpath (for all users)
     * @param	string	Course code
     * @param	integer	User ID
     * @return array	Well-formatted array containing status for the user's learnpaths
     */
    public function get_stats_user($course, $user) {
        //if ($this->debug > 0) { error_log('New LP - In learnpath::get_stats_user()', 0); }
        // TODO
    }

    /**
     * Gets the status list for all LP's items
     * @return	array	Array of [index] => [item ID => current status]
     */
    public function get_items_status_list() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_items_status_list()', 0);
        }
        $list = array ();
        foreach ($this->ordered_items as $item_id) {
            $list[] = array (
                $item_id => $this->items[$item_id]->get_status()
            );
        }
        return $list;
    }

    /**
     * Return the number of interactions for the given learnpath Item View ID.
     * This method can be used as static.
     * @param	integer	Item View ID
     * @param   integer course id
     * @return	integer	Number of interactions
     */
    public function get_interactions_count_from_db($lp_iv_id, $course_id) {        
        $table = Database :: get_course_table(TABLE_LP_IV_INTERACTION);   
        $lp_iv_id = intval($lp_iv_id);
        $course_id = intval($course_id);
             
        $sql = "SELECT count(*) FROM $table WHERE c_id = $course_id AND lp_iv_id = $lp_iv_id";
        $res = Database::query($sql);
        $res = 0;
        if (Database::num_rows($res)) {
            $row = Database::fetch_array($res);
            $num = $row[0];
        }
        return $num;
    }

    /**
     * Return the interactions as an array for the given lp_iv_id.
     * This method can be used as static.
     * @param	integer	Learnpath Item View ID
     * @return	array
     * @todo 	Transcode labels instead of switching to HTML (which requires to know the encoding of the LP)
     */
    public function get_iv_interactions_array($lp_iv_id = 0) {
        $course_id = api_get_course_int_id();
        $list = array ();
        $table = Database :: get_course_table(TABLE_LP_IV_INTERACTION);
        $sql = "SELECT * FROM $table WHERE c_id = ".$course_id." AND lp_iv_id = $lp_iv_id ORDER BY order_id ASC";
        $res = Database::query($sql);
        $num = Database :: num_rows($res);
        if ($num > 0) {
            $list[] = array (
                'order_id' => api_htmlentities(get_lang('Order'), ENT_QUOTES),
                'id' => api_htmlentities(get_lang('InteractionID'), ENT_QUOTES),
                'type' => api_htmlentities(get_lang('Type'), ENT_QUOTES),
                'time' => api_htmlentities(get_lang('TimeFinished'), ENT_QUOTES),
                'correct_responses' => api_htmlentities(get_lang('CorrectAnswers'), ENT_QUOTES),
                'student_response' => api_htmlentities(get_lang('StudentResponse'), ENT_QUOTES),
                'result' => api_htmlentities(get_lang('Result'), ENT_QUOTES),
                'latency' => api_htmlentities(get_lang('LatencyTimeSpent'), ENT_QUOTES)
            );
            while ($row = Database :: fetch_array($res)) {
                $list[] = array (
                    'order_id' => ($row['order_id'] + 1),
                    'id' => urldecode($row['interaction_id']), //urldecode because they often have %2F or stuff like that
                    'type' => $row['interaction_type'],
                    'time' => $row['completion_time'],
                    //'correct_responses' => $row['correct_responses'],
                    'correct_responses' => '', // Hide correct responses from students.
                    'student_response' => $row['student_response'],
                    'result' => $row['result'],
                    'latency' => $row['latency']
                );
            }
        }
        return $list;
    }

    /**
     * Return the number of objectives for the given learnpath Item View ID.
     * This method can be used as static.
     * @param	integer	Item View ID
     * @return	integer	Number of objectives
     */
    public function get_objectives_count_from_db($lp_iv_id, $course_id) {     
        $table = Database :: get_course_table(TABLE_LP_IV_OBJECTIVE);
        $course_id = intval($course_id);
        $lp_iv_id = intval($lp_iv_id);
        $sql = "SELECT count(*) FROM $table WHERE c_id = $course_id AND lp_iv_id = $lp_iv_id";
        $res = Database::query($sql);
        $res = 0;
        if (Database::num_rows($res)) {
            $row = Database :: fetch_array($res);
            $num = $row[0];
        }
        return $num;
    }

    /**
     * Return the objectives as an array for the given lp_iv_id.
     * This method can be used as static.
     * @param	integer	Learnpath Item View ID
     * @return	array
     * @todo 	Translate labels
     */
    public function get_iv_objectives_array($lp_iv_id = 0) {
        $course_id = api_get_course_int_id();
        $list = array();
        $table = Database :: get_course_table(TABLE_LP_IV_OBJECTIVE);
        $sql = "SELECT * FROM $table WHERE c_id = $course_id AND lp_iv_id = $lp_iv_id ORDER BY order_id ASC";
        $res = Database::query($sql);
        $num = Database :: num_rows($res);
        if ($num > 0) {
            $list[] = array (
                'order_id' => api_htmlentities(get_lang('Order'), ENT_QUOTES),
                'objective_id' => api_htmlentities(get_lang('ObjectiveID'), ENT_QUOTES),
                'score_raw' => api_htmlentities(get_lang('ObjectiveRawScore'), ENT_QUOTES),
                'score_max' => api_htmlentities(get_lang('ObjectiveMaxScore'), ENT_QUOTES),
                'score_min' => api_htmlentities(get_lang('ObjectiveMinScore'), ENT_QUOTES),
                'status' => api_htmlentities(get_lang('ObjectiveStatus'), ENT_QUOTES)
            );
            while ($row = Database :: fetch_array($res)) {
                $list[] = array (
                    'order_id' => ($row['order_id'] + 1),
                    'objective_id' => urldecode($row['objective_id']), // urldecode() because they often have %2F or stuff like that.
                    'score_raw' => $row['score_raw'],
                    'score_max' => $row['score_max'],
                    'score_min' => $row['score_min'],
                    'status' => $row['status']
                );
            }
        }
        return $list;
    }

    /**
     * Generate and return the table of contents for this learnpath. The (flat) table returned can be
     * used by get_html_toc() to be ready to display
     * @return	array	TOC as a table with 4 elements per row: title, link, status and level
     */
    public function get_toc() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_toc()', 0);
        }
        $toc = array ();
        //echo "<pre>".print_r($this->items,true)."</pre>";
        foreach ($this->ordered_items as $item_id) {
            if ($this->debug > 2) {
                error_log('New LP - learnpath::get_toc(): getting info for item ' . $item_id, 0);
            }
            // TODO: Change this link generation and use new function instead.
            $toc[] = array (
                'id'            => $item_id,
                'title'         => $this->items[$item_id]->get_title(),
                //'link' => get_addedresource_link_in_learnpath('document', $item_id, 1),
                'status'        => $this->items[$item_id]->get_status(),
                'level'         => $this->items[$item_id]->get_level(),
                'type'          => $this->items[$item_id]->get_type(),
                'description'   => $this->items[$item_id]->get_description(),
                'path'          => $this->items[$item_id]->get_path(),
            );
        }
        if ($this->debug > 2) {
            error_log('New LP - In learnpath::get_toc() - TOC array: ' . print_r($toc, true), 0);
        }
        return $toc;
    }

    /**
     * Generate and return the table of contents for this learnpath. The JS
     * table returned is used inside of scorm_api.php
     * @return  string  A JS array vairiable construction
     */
    public function get_items_details_as_js($varname = 'olms.lms_item_types') {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_items_details_as_js()', 0);
        }
        $toc = $varname.' = new Array();';
        //echo "<pre>".print_r($this->items,true)."</pre>";
        foreach ($this->ordered_items as $item_id) {
            if ($this->debug > 2) {
                error_log('New LP - learnpath::get_items_details_as_js(): getting info for item ' . $item_id, 0);
            }
            $toc.= $varname."['i$item_id'] = '".$this->items[$item_id]->get_type()."';";
        }
        if ($this->debug > 2) {
            error_log('New LP - In learnpath::get_items_details_as_js() - TOC array: ' . print_r($toc, true), 0);
        }
        return $toc;
    }

    /**
     * Gets the learning path type
     * @param	boolean		Return the name? If false, return the ID. Default is false.
     * @return	mixed		Type ID or name, depending on the parameter
     */
    public function get_type($get_name = false) {
        $res = false;
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_type()', 0);
        }
        if (!empty ($this->type)) {
            if ($get_name) {
                // Get it from the lp_type table in main db.
            } else {
                $res = $this->type;
            }
        }
        if ($this->debug > 2) {
            error_log('New LP - In learnpath::get_type() - Returning ' . ($res ? $res : 'false'), 0);
        }
        return $res;
    }

    /**
     * Gets the learning path type as static method
     * @param	boolean		Return the name? If false, return the ID. Default is false.
     * @return	mixed		Type ID or name, depending on the parameter
     */
    public function get_type_static($lp_id = 0) {
        $course_id = api_get_course_int_id();
        $tbl_lp = Database :: get_course_table(TABLE_LP_MAIN);
        $sql = "SELECT lp_type FROM $tbl_lp WHERE c_id = $course_id AND id = '" . $lp_id . "'";
        $res = Database::query($sql);
        if ($res === false) {
            return null;
        }
        if (Database :: num_rows($res) <= 0) {
            return null;
        }
        $row = Database :: fetch_array($res);
        return $row['lp_type'];
    }

    /**
     * Gets a flat list of item IDs ordered for display (level by level ordered by order_display)
     * This method can be used as abstract and is recursive
     * @param	integer	Learnpath ID
     * @param	integer	Parent ID of the items to look for
     * @return	mixed	Ordered list of item IDs or false on error
     */
    public function get_flat_ordered_items_list($lp, $parent = 0, $course_id = null) {
        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        } else {
            $course_id = intval($course_id);
        }
        $list = array();
        if (empty ($lp)) {
            return false;
        }
        $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);
        $sql = "SELECT * FROM $tbl_lp_item WHERE c_id = $course_id AND lp_id = $lp AND parent_item_id = $parent ORDER BY display_order";
        $res = Database::query($sql);
        while ($row = Database :: fetch_array($res)) {
            $sublist = learnpath :: get_flat_ordered_items_list($lp, $row['id'], $course_id);
            $list[] = $row['id'];
            foreach ($sublist as $item) {
                $list[] = $item;
            }
        }
        return $list;
    }

    /**
     * Uses the table generated by get_toc() and returns an HTML-formatted string ready to display
     * @return	string	HTML TOC ready to display
     */
    public function get_html_toc() {
        $course_id      = api_get_course_int_id();
        $course_code    = api_get_course_id();
        $is_allowed_to_edit = api_is_allowed_to_edit(null, true);

        $charset = api_get_system_encoding();
        $display_action_links_with_icons = false;

        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_html_toc()', 0);
        }
        $list = $this->get_toc();
        //echo $this->current;
        //$parent = $this->items[$this->current]->get_parent();
        //if (empty($parent)) { $parent = $this->ordered_items[$this->items[$this->current]->get_previous_index()]; }
        $html = '<div id="scorm_title" class="scorm_title">' . Security::remove_XSS($this->get_name()) . '</div>';
        // Build, display.
        if ($is_allowed_to_edit) {
            $gradebook = Security :: remove_XSS($_GET['gradebook']);
            if ($this->get_lp_session_id() == api_get_session_id()) {
                $html .= '<div id="actions_lp" class="actions_lp">';
                if ($display_action_links_with_icons) {
                    $html .= '<div class = "btn-group">';
                    $html .= "<a href='lp_controller.php?" . api_get_cidreq() . "&amp;action=build&amp;lp_id=" . $this->lp_id . "' target='_parent'>" . Display :: return_icon('build_learnpath.png', get_lang('Build'),'',ICON_SIZE_MEDIUM)."</a>";
                    $html .= "<a href='lp_controller.php?" . api_get_cidreq() . "&amp;action=admin_view&amp;lp_id=" . $this->lp_id . "' target='_parent'>" . Display :: return_icon('move_learnpath.png', get_lang('BasicOverview'),'',ICON_SIZE_MEDIUM)."</a>";
                    //$html .= '<span>' . Display :: return_icon('view_remove_na.png', get_lang('Display'),'',ICON_SIZE_MEDIUM).'</span><br />';
                    $html .= '<a href="lp_controller.php?' . api_get_cidreq() . '">'. get_lang('ReturnToLPList') . '</a>';
                    $html .= '</div>';
                } else {
                    $html .= '<div class="btn-group">';
                    $html .= "<a class='btn' href='lp_controller.php?" . api_get_cidreq() . "&amp;gradebook=$gradebook&amp;action=build&amp;lp_id=" . $this->lp_id . "' target='_parent'>" . get_lang('Build') . "</a>";
                    $html .= "<a class='btn' href='lp_controller.php?" . api_get_cidreq() . "&amp;action=admin_view&amp;lp_id=" . $this->lp_id . "' target='_parent'>" . get_lang('BasicOverview') . "</a>";
                    //$html .= '<span><b>' . get_lang('Display') . '</b></span><br />';
                    $html .= '<a class="btn" href="lp_controller.php?'.api_get_cidreq().'">'.get_lang('Back').'</a>';
                    $html .= '</div>';
                }
                $html .= '</div>';
            }

        }
        $html .= '<div id="inner_lp_toc" class="inner_lp_toc">';
        require_once 'resourcelinker.inc.php';

        // Temporary variables.
        $mycurrentitemid = $this->get_current_item_id();
        $color_counter = 0;
        $i = 0;
        foreach ($list as $item) {
            if ($this->debug > 2) {
                error_log('New LP - learnpath::get_html_toc(): using item ' . $item['id'], 0);
            }
            // TODO: Complete this.
            $icon_name = array (
                'not attempted' => '../img/notattempted.gif',
                'incomplete'    => '../img/incomplete.png',
                'failed'        => '../img/delete.png',
                'completed'     => '../img/completed.png',
                'passed'        => '../img/passed.png',
                'succeeded'     => '../img/succeeded.png',
                'browsed'       => '../img/completed.png',
            );

            $style = 'scorm_item';
            $scorm_color_background = 'scorm_item';
            $style_item = 'scorm_item';
            $current = false;

            if ($item['id'] == $this->current) {
                $style = 'scorm_item_highlight';
                $scorm_color_background = 'scorm_item_highlight';
            } else {
            	
                if ($color_counter % 2 == 0) {
                    $scorm_color_background = 'scorm_item_1';
                } else {
                    $scorm_color_background = 'scorm_item_2';
                }
                if ($item['type'] == 'dokeos_module' || $item['type'] == 'dokeos_chapter') {
                	$scorm_color_background =' scorm_item_section ';
                }
            }

            if ($scorm_color_background != '') {
                $html .= '<div id="toc_' . $item['id'] . '" class="' . $scorm_color_background . '">';
            }

            // The anchor will let us center the TOC on the currently viewed item &^D
            if ($item['type'] != 'dokeos_module' && $item['type'] != 'dokeos_chapter') {                
                $html .= '<div class="' . $style_item . '" style="padding-left: ' . ($item['level'] * 1.5) . 'em; padding-right:' . ($item['level'] / 2) . 'em"             title="' . $item['description'] . '" >';
                $html .= '<a name="atoc_' . $item['id'] . '" />';
            } else {
                $html .= '<div class="' . $style_item . '" style="padding-left: ' . ($item['level'] * 2) . 'em; padding-right:' . ($item['level'] * 1.5) . 'em"             title="' . $item['description'] . '" >';
            }
            $title = $item['title'];
            if (empty ($title)) {
                $title = rl_get_resource_name(api_get_course_id(), $this->get_id(), $item['id']);
            }

            $title = Security::remove_XSS($title);
            if ($item['type'] != 'dokeos_chapter' && $item['type'] != 'dir' && $item['type'] != 'dokeos_module') {
                //$html .= "<a href='lp_controller.php?".api_get_cidreq()."&action=content&lp_id=".$this->get_id()."&item_id=".$item['id']."' target='lp_content_frame_name'>".$title."</a>" ;
                $url = $this->get_link('http', $item['id']);
                //$html .= '<a href="'.$url.'" target="content_name" onClick="top.load_item('.$item['id'].',\''.$url.'\');">'.$title.'</a>' ;
                //$html .= '<a href="" onClick="top.load_item('.$item['id'].',\''.$url.'\');return false;">'.$title.'</a>' ;

                //<img align="absbottom" width="13" height="13" src="../img/lp_document.png">&nbsp;background:#aaa;
                $html .= '<a href="" onClick="switch_item(' .$mycurrentitemid . ',' .$item['id'] . ');' .'return false;" >' . stripslashes($title) . '</a>';
            } elseif ($item['type'] == 'dokeos_module' || $item['type'] == 'dokeos_chapter') {
                $html .= "<img align='absbottom' width='13' height='13' src='../img/lp_dokeos_module.png'>&nbsp;" . stripslashes($title);
            } elseif ($item['type'] == 'dir') {
                $html .= stripslashes($title);
            }

            $tbl_track_e_exercises = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
            $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);
            $user_id = api_get_user_id();
            
            $sql = "SELECT path  FROM $tbl_track_e_exercises, $tbl_lp_item
                    WHERE   c_id = $course_id AND 
                            path =   '" . $item['path'] . "' AND 
                            exe_user_id =  '$user_id' AND 
                            exe_cours_id = '$course_code' AND 
                            path = exe_exo_id AND 
                            status <> 'incomplete'";
            $result = Database::query($sql);
            $count = Database :: num_rows($result);
            if ($item['type'] == 'quiz') {
                if ($item['status'] == 'completed') {
                    $html .= "&nbsp;<img id='toc_img_" . $item['id'] . "' src='" . $icon_name[$item['status']] . "' alt='" . substr($item['status'], 0, 1) . "' width='14'  />";
                }
            } else {
                if ($item['type'] != 'dokeos_chapter' && $item['type'] != 'dokeos_module' && $item['type'] != 'dir') {
                    $html .= "&nbsp;<img id='toc_img_" . $item['id'] . "' src='" . $icon_name[$item['status']] . "' alt='" . substr($item['status'], 0, 1) . "' width='14' />";
                }
            }

            $html .= "</div>";

            if ($scorm_color_background != '') {
                $html .= '</div>';
            }

            $color_counter++;
        }
        $html .= "</div>";
        return $html;
    }

    /**
     * Gets the learnpath maker name - generally the editor's name
     * @return	string	Learnpath maker name
     */
    public function get_maker() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_maker()', 0);
        }
        if (!empty ($this->maker)) {
            return $this->maker;
        } else {
            return '';
        }
    }

    /**
     * Gets the user-friendly message stored in $this->message
     * @return	string	Message
     */
    public function get_message() {

        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_message()', 0);
        }
        return $this->message;
    }

    /**
     * Gets the learnpath name/title
     * @return	string	Learnpath name/title
     */
    public function get_name() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_name()', 0);
        }
        if (!empty ($this->name)) {
            return $this->name;
        } else {
            return 'N/A';
        }
    }

    /**
     * Gets a link to the resource from the present location, depending on item ID.
     * @param	string	Type of link expected
     * @param	integer	Learnpath item ID
     * @return	string	Link to the lp_item resource
     */
    public function get_link($type = 'http', $item_id = null) {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_link(' . $type . ',' . $item_id . ')', 0);
        }
        if (empty($item_id)) {
            if ($this->debug > 2) {
                error_log('New LP - In learnpath::get_link() - no item id given in learnpath::get_link(), using current: ' . $this->get_current_item_id(), 0);
            }
            $item_id = $this->get_current_item_id();
        }

        if (empty ($item_id)) {
            if ($this->debug > 2) {
                error_log('New LP - In learnpath::get_link() - no current item id found in learnpath object', 0);
            }
            //still empty, this means there was no item_id given and we are not in an object context or
            //the object property is empty, return empty link
            $item_id = $this->first();
            return '';
        }

        $file = '';
        $lp_table 			= Database::get_course_table(TABLE_LP_MAIN);
        $lp_item_table 		= Database::get_course_table(TABLE_LP_ITEM);
        $lp_item_view_table = Database::get_course_table(TABLE_LP_ITEM_VIEW);
        $item_id 			= Database::escape_string($item_id);

        $sel = "SELECT l.lp_type as ltype, l.path as lpath, li.item_type as litype, li.path as lipath, li.parameters as liparams 
        		FROM $lp_table l, $lp_item_table li 
        		WHERE 	l.c_id = $course_id AND
        				li.c_id = $course_id AND 
        				li.id = $item_id AND 
        				li.lp_id = l.id";
        if ($this->debug > 2) {
            error_log('New LP - In learnpath::get_link() - selecting item ' . $sel, 0);
        }
        $res = Database::query($sel);
        if (Database :: num_rows($res) > 0) {
            $row = Database :: fetch_array($res);
            $lp_type = $row['ltype'];
            $lp_path = $row['lpath'];
            $lp_item_type = $row['litype'];
            $lp_item_path = $row['lipath'];
            $lp_item_params = $row['liparams'];
            if (empty ($lp_item_params) && strpos($lp_item_path, '?') !== false) {
                list ($lp_item_path, $lp_item_params) = explode('?', $lp_item_path);
            }
            //$lp_item_params = '?'.$lp_item_params;

            //add ? if none - left commented to give freedom to scorm implementation
            //if(substr($lp_item_params,0,1)!='?'){
            //	$lp_item_params = '?'.$lp_item_params;
            //}
            $sys_course_path = api_get_path(SYS_COURSE_PATH) . api_get_course_path();
            if ($type == 'http') {
                $course_path = api_get_path(WEB_COURSE_PATH) . api_get_course_path(); //web path
            } else {
                $course_path = $sys_course_path; //system path
            }


            // Fixed issue BT#1272 - If the item type is a Chamilo Item (quiz, link, etc), then change the lp type to thread it as a normal Chamilo LP not a SCO.
            if (in_array($lp_item_type, array('quiz', 'document', 'link', 'forum', 'thread', 'student_publication'))) {
                $lp_type = 1;
            }
            
            // Now go through the specific cases to get the end of the path.

            // @todo Use constants instead of int values.
            switch ($lp_type) {
                case 1 :
                    if ($lp_item_type == 'dokeos_chapter') {
                        $file = 'lp_content.php?type=dir';
                    } else {
                        require_once 'resourcelinker.inc.php';
                        $file = rl_get_resource_link_for_learnpath(api_get_course_id(), $this->get_id(), $item_id);
                        
                        if ($this->debug > 0) {
                            error_log('rl_get_resource_link_for_learnpath - file: ' . $file, 0);
                        }

                        if ($lp_item_type == 'link') {
                            require_once api_get_path(LIBRARY_PATH).'link.lib.php';
                            if (is_youtube_link($file)) {
                                $src  = get_youtube_video_id($file);                                
                                $file = 'embed.php?type=youtube&src='.$src;
                            }
                        } else {
                            // check how much attempts of a exercise exits in lp
                            $lp_item_id = $this->get_current_item_id();
                            $lp_view_id = $this->get_view_id();
                            $prevent_reinit = $this->items[$this->current]->get_prevent_reinit();
                            $list = $this->get_toc();
                            $type_quiz = false;
    
                            foreach ($list as $toc) {
                                if ($toc['id'] == $lp_item_id && ($toc['type'] == 'quiz')) {
                                    $type_quiz = true;
                                }
                            }
    
                            if ($type_quiz) {
                                $lp_item_id = Database :: escape_string($lp_item_id);
                                $lp_view_id = Database :: escape_string($lp_view_id);
                                $sql = "SELECT count(*) FROM $lp_item_view_table 
                                        WHERE c_id = $course_id AND lp_item_id='" . (int) $lp_item_id . "' AND lp_view_id ='" . (int) $lp_view_id . "' AND status='completed'";
                                $result = Database::query($sql);
                                $row_count = Database :: fetch_row($result);
                                $count_item_view = (int) $row_count[0];
                                $not_multiple_attempt = 0;
                                if ($prevent_reinit === 1 && $count_item_view > 0) {
                                    $not_multiple_attempt = 1;
                                }
                                $file .= '&not_multiple_attempt=' . $not_multiple_attempt;
                            }
    
                            $tmp_array = explode('/', $file);
                            $document_name = $tmp_array[count($tmp_array) - 1];
                            if (strpos($document_name, '_DELETED_')) {
                                $file = 'blank.php?error=document_deleted';
                            }
                        }
                    }
                    break;
                case 2 :
                    if ($this->debug > 2) {
                        error_log('New LP - In learnpath::get_link() ' . __LINE__ . ' - Item type: ' . $lp_item_type, 0);
                    }

                    if ($lp_item_type != 'dir') {
                        // Quite complex here:
                        // We want to make sure 'http://' (and similar) links can
                        // be loaded as is (withouth the Chamilo path in front) but
                        // some contents use this form: resource.htm?resource=http://blablabla
                        // which means we have to find a protocol at the path's start, otherwise
                        // it should not be considered as an external URL.

                        //if ($this->prerequisites_match($item_id)) {
                        if (preg_match('#^[a-zA-Z]{2,5}://#', $lp_item_path) != 0) {
                            if ($this->debug > 2) {
                                error_log('New LP - In learnpath::get_link() ' . __LINE__ . ' - Found match for protocol in ' . $lp_item_path, 0);
                            }
                            // Distant url, return as is.
                            $file = $lp_item_path;
                        } else {
                            if ($this->debug > 2) {
                                error_log('New LP - In learnpath::get_link() ' . __LINE__ . ' - No starting protocol in ' . $lp_item_path, 0);
                            }
                            // Prevent getting untranslatable urls.
                            $lp_item_path = preg_replace('/%2F/', '/', $lp_item_path);
                            $lp_item_path = preg_replace('/%3A/', ':', $lp_item_path);
                            // Prepare the path.
                            $file = $course_path . '/scorm/' . $lp_path . '/' . $lp_item_path;
                            // TODO: Fix this for urls with protocol header.
                            $file = str_replace('//', '/', $file);
                            $file = str_replace(':/', '://', $file);
                            if (substr($lp_path, -1) == '/') {
                                $lp_path = substr($lp_path, 0, -1);
                            }

                            if (!is_file(realpath($sys_course_path . '/scorm/' . $lp_path . '/' . $lp_item_path))) {
                                // if file not found.
                                $decoded = html_entity_decode($lp_item_path);
                                list ($decoded) = explode('?', $decoded);
                                if (!is_file(realpath($sys_course_path . '/scorm/' . $lp_path . '/' . $decoded))) {
                                    require_once 'resourcelinker.inc.php';
                                    $file = rl_get_resource_link_for_learnpath(api_get_course_id(), $this->get_id(), $item_id);
                                    if (empty($file)) {
                                        $file = 'blank.php?error=document_not_found';
                                    } else {
                                        $tmp_array = explode('/', $file);
                                        $document_name = $tmp_array[count($tmp_array) - 1];
                                        if (strpos($document_name, '_DELETED_')) {
                                            $file = 'blank.php?error=document_deleted';
                                        } else {
                                            $file = 'blank.php?error=document_not_found';
                                        }
                                    }
                                } else {
                                    $file = $course_path . '/scorm/' . $lp_path . '/' . $decoded;
                                }
                            }
                        }
                        //}else{
                        //prerequisites did not match
                        //$file = 'blank.php';
                        //}
                        // We want to use parameters if they were defined in the imsmanifest.
                        if ($file != 'blank.php') {
                            $file .= (strstr($file, '?') === false ? '?' : '') . $lp_item_params;
                        }
                    } else {
                        $file = 'lp_content.php?type=dir';
                    }
                    break;
                case 3 :
                    if ($this->debug > 2) {
                        error_log('New LP - In learnpath::get_link() ' . __LINE__ . ' - Item type: ' . $lp_item_type, 0);
                    }
                    // Formatting AICC HACP append URL.
                    $aicc_append = '?aicc_sid=' . urlencode(session_id()) . '&aicc_url=' . urlencode(api_get_path(WEB_CODE_PATH) . 'newscorm/aicc_hacp.php') . '&';
                    if ($lp_item_type != 'dir') {
                        // Quite complex here:
                        // We want to make sure 'http://' (and similar) links can
                        // be loaded as is (withouth the Chamilo path in front) but
                        // some contents use this form: resource.htm?resource=http://blablabla
                        // which means we have to find a protocol at the path's start, otherwise
                        // it should not be considered as an external URL.

                        if (preg_match('#^[a-zA-Z]{2,5}://#', $lp_item_path) != 0) {
                            if ($this->debug > 2) {
                                error_log('New LP - In learnpath::get_link() ' . __LINE__ . ' - Found match for protocol in ' . $lp_item_path, 0);
                            }
                            // Distant url, return as is.
                            $file = $lp_item_path;
                            // Enabled and modified by Ivan Tcholakov, 16-OCT-2008.
                            /*
                            if (stristr($file,'<servername>') !== false) {
                                $file = str_replace('<servername>', $course_path.'/scorm/'.$lp_path.'/', $lp_item_path);
                            }
                            */
                            if (stripos($file, '<servername>') !== false) {
                                //$file = str_replace('<servername>',$course_path.'/scorm/'.$lp_path.'/',$lp_item_path);
                                $web_course_path = str_replace('https://', '', str_replace('http://', '', $course_path));
                                $file = str_replace('<servername>', $web_course_path . '/scorm/' . $lp_path, $lp_item_path);
                            }
                            //
                            $file .= $aicc_append;
                        } else {
                            if ($this->debug > 2) {
                                error_log('New LP - In learnpath::get_link() ' . __LINE__ . ' - No starting protocol in ' . $lp_item_path, 0);
                            }
                            // Prevent getting untranslatable urls.
                            $lp_item_path = preg_replace('/%2F/', '/', $lp_item_path);
                            $lp_item_path = preg_replace('/%3A/', ':', $lp_item_path);
                            // Prepare the path - lp_path might be unusable because it includes the "aicc" subdir name.
                            $file = $course_path . '/scorm/' . $lp_path . '/' . $lp_item_path;
                            // TODO: Fix this for urls with protocol header.
                            $file = str_replace('//', '/', $file);
                            $file = str_replace(':/', '://', $file);
                            $file .= $aicc_append;
                        }
                    } else {
                        $file = 'lp_content.php?type=dir';
                    }
                    break;
                case 4 :
                    break;
                default :
                    break;
            }
        }
        if ($this->debug > 2) {
            error_log('New LP - In learnpath::get_link() - returning "' . $file . '" from get_link', 0);
        }
        return $file;
    }

    /**
     * Gets the latest usable view or generate a new one
     * @param	integer	Optional attempt number. If none given, takes the highest from the lp_view table
     * @return	integer	DB lp_view id
     */
    public function get_view($attempt_num = 0) {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_view()', 0);
        }
        $search = '';
        // Use $attempt_num to enable multi-views management (disabled so far).
        if ($attempt_num != 0 AND intval(strval($attempt_num)) == $attempt_num) {
            $search = 'AND view_count = ' . $attempt_num;
        }
        // When missing $attempt_num, search for a unique lp_view record for this lp and user.
        $lp_view_table = Database :: get_course_table(TABLE_LP_VIEW);
        
        $course_id = api_get_course_int_id();
        
        $sql = "SELECT id, view_count FROM $lp_view_table 
        		WHERE c_id = ".$course_id." AND lp_id = " . $this->get_id() ." AND user_id = " . $this->get_user_id() . " " .$search .
        		" ORDER BY view_count DESC";
        $res = Database::query($sql);
        if (Database :: num_rows($res) > 0) {
            $row = Database :: fetch_array($res);
            $this->lp_view_id = $row['id'];
        } else {
            // There is no database record, create one.
            $sql = "INSERT INTO $lp_view_table (c_id, lp_id,user_id,view_count) VALUES 
            		($course_id, " . $this->get_id() . "," . $this->get_user_id() . ",1)";
            $res = Database::query($sql);
            $id = Database :: insert_id();
            $this->lp_view_id = $id;
        }
        return $this->lp_view_id;
    }

    /**
     * Gets the current view id
     * @return	integer	View ID (from lp_view)
     */
    public function get_view_id() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_view_id()', 0);
        }
        if (!empty ($this->lp_view_id)) {
            return $this->lp_view_id;
        } else {
            return 0;
        }
    }

    /**
     * Gets the update queue
     * @return	array	Array containing IDs of items to be updated by JavaScript
     */
    public function get_update_queue() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_update_queue()', 0);
        }
        return $this->update_queue;
    }

    /**
     * Gets the user ID
     * @return	integer	User ID
     */
    public function get_user_id() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::get_user_id()', 0);
        }
        if (!empty ($this->user_id)) {
            return $this->user_id;
        } else {
            return false;
        }
    }

    /**
     * Checks if any of the items has an audio element attached
     * @return  bool    True or false
     */
    public function has_audio() {
        if ($this->debug > 1) {
            error_log('New LP - In learnpath::has_audio()', 0);
        }
        $has = false;
        foreach ($this->items as $i => $item) {
            if (!empty ($this->items[$i]->audio)) {
                $has = true;
                break;
            }
        }
        return $has;
    }

    /**
     * Logs a message into a file
     * @param	string 	Message to log
     * @return	boolean	True on success, false on error or if msg empty
     */
    public function log($msg) {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::log()', 0);
        }
        // TODO
        $this->error .= $msg;
        return true;
    }

    /**
     * Moves an item up and down at its level
     * @param	integer	Item to move up and down
     * @param	string	Direction 'up' or 'down'
     * @return	integer	New display order, or false on error
     */
    public function move_item($id, $direction) {
        $course_id = api_get_course_int_id(); 
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::move_item(' . $id . ',' . $direction . ')', 0);
        }
        if (empty ($id) or empty ($direction)) {
            return false;
        }
        $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);
        $sql_sel = "SELECT *
                    FROM " . $tbl_lp_item . "
                    WHERE c_id = ".$course_id." AND id = " . $id;
        $res_sel = Database::query($sql_sel);
        // Check if elem exists.
        if (Database :: num_rows($res_sel) < 1) {
            return false;
        }
        // Gather data.
        $row = Database :: fetch_array($res_sel);
        $previous = $row['previous_item_id'];
        $next = $row['next_item_id'];
        $display = $row['display_order'];
        $parent = $row['parent_item_id'];
        $lp = $row['lp_id'];
        // Update the item (switch with previous/next one).
        switch ($direction) {
            case 'up' :
                if ($this->debug > 2) {
                    error_log('Movement up detected', 0);
                }
                if ($display <= 1) { /*do nothing*/
                } else {
                    $sql_sel2 = "SELECT * FROM $tbl_lp_item
                                 WHERE c_id = ".$course_id." AND id = $previous";

                    if ($this->debug > 2) {
                        error_log('Selecting previous: ' . $sql_sel2, 0);
                    }
                    $res_sel2 = Database::query($sql_sel2);
                    if (Database :: num_rows($res_sel2) < 1) {
                        $previous_previous = 0;
                    }
                    // Gather data.
                    $row2 = Database :: fetch_array($res_sel2);
                    $previous_previous = $row2['previous_item_id'];
                    // Update previous_previous item (switch "next" with current).
                    if ($previous_previous != 0) {
                        $sql_upd2 = "UPDATE $tbl_lp_item SET next_item_id = $id WHERE c_id = ".$course_id." AND id = $previous_previous";
                        if ($this->debug > 2) {
                            error_log($sql_upd2, 0);
                        }
                        $res_upd2 = Database::query($sql_upd2);
                    }
                    // Update previous item (switch with current).
                    if ($previous != 0) {
                        $sql_upd2 = "UPDATE $tbl_lp_item SET next_item_id = $next, previous_item_id = $id, display_order = display_order +1 
                                    WHERE c_id = ".$course_id." AND id = $previous";
                        if ($this->debug > 2) {
                            error_log($sql_upd2, 0);
                        }
                        $res_upd2 = Database::query($sql_upd2);
                    }

                    // Update current item (switch with previous).
                    if ($id != 0) {
                        $sql_upd2 = "UPDATE $tbl_lp_item SET next_item_id = $previous, previous_item_id = $previous_previous, display_order = display_order-1 
                                    WHERE c_id = ".$course_id." AND id = $id";
                        if ($this->debug > 2) {
                            error_log($sql_upd2, 0);
                        }
                        $res_upd2 = Database::query($sql_upd2);
                    }
                    // Update next item (new previous item).
                    if ($next != 0) {
                        $sql_upd2 = "UPDATE $tbl_lp_item SET previous_item_id = $previous 
                                     WHERE c_id = ".$course_id." AND id = $next";
                        if ($this->debug > 2) {
                            error_log($sql_upd2, 0);
                        }
                        $res_upd2 = Database::query($sql_upd2);
                    }
                    $display = $display -1;
                }
                break;

            case 'down' :
                if ($this->debug > 2) {
                    error_log('Movement down detected', 0);
                }
                if ($next == 0) { /* Do nothing. */
                } else {
                    $sql_sel2 = "SELECT * FROM $tbl_lp_item WHERE c_id = ".$course_id." AND id = $next";
                    if ($this->debug > 2) {
                        error_log('Selecting next: ' . $sql_sel2, 0);
                    }
                    $res_sel2 = Database::query($sql_sel2);
                    if (Database :: num_rows($res_sel2) < 1) {
                        $next_next = 0;
                    }
                    // Gather data.
                    $row2 = Database :: fetch_array($res_sel2);
                    $next_next = $row2['next_item_id'];
                    // Update previous item (switch with current).
                    if ($previous != 0) {
                        $sql_upd2 = "UPDATE $tbl_lp_item SET next_item_id = $next 
                                     WHERE c_id = ".$course_id." AND id = $previous";
                        $res_upd2 = Database::query($sql_upd2);
                    }
                    // Update current item (switch with previous).
                    if ($id != 0) {
                        $sql_upd2 = "UPDATE $tbl_lp_item SET previous_item_id = $next, next_item_id = $next_next, display_order = display_order+1 
                                     WHERE c_id = ".$course_id." AND id = $id";
                        $res_upd2 = Database::query($sql_upd2);
                    }

                    // Update next item (new previous item).
                    if ($next != 0) {
                        $sql_upd2 = "UPDATE $tbl_lp_item SET previous_item_id = $previous, next_item_id = $id, display_order = display_order-1 
                                     WHERE c_id = ".$course_id." AND id = $next";
                        $res_upd2 = Database::query($sql_upd2);
                    }

                    // Update next_next item (switch "previous" with current).
                    if ($next_next != 0) {
                        $sql_upd2 = "UPDATE $tbl_lp_item SET previous_item_id = $id 
                                     WHERE c_id = ".$course_id." AND id = $next_next";
                        $res_upd2 = Database::query($sql_upd2);
                    }
                    $display = $display +1;
                }
                break;
            default :
                return false;
        }
        return $display;
    }

    /**
     * Move a learnpath up (display_order)
     * @param	integer	Learnpath ID
     */
    public function move_up($lp_id) {
        $course_id = api_get_course_int_id();                        
        $lp_table = Database :: get_course_table(TABLE_LP_MAIN);
        $sql = "SELECT * FROM $lp_table WHERE c_id = ".$course_id." ORDER BY display_order";
        $res = Database::query($sql);
        if ($res === false)
            return false;
        $lps = array ();
        $lp_order = array ();
        $num = Database :: num_rows($res);
        // First check the order is correct, globally (might be wrong because
        // of versions < 1.8.4)
        if ($num > 0) {
            $i = 1;
            while ($row = Database :: fetch_array($res)) {
                if ($row['display_order'] != $i) { // If we find a gap in the order, we need to fix it.
                    $need_fix = true;
                    $sql_u = "UPDATE $lp_table SET display_order = $i WHERE c_id = ".$course_id." AND id = " . $row['id'];
                    $res_u = Database::query($sql_u);
                }
                $row['display_order'] = $i;
                $lps[$row['id']] = $row;
                $lp_order[$i] = $row['id'];
                $i++;
            }
        }
        if ($num > 1) { // If there's only one element, no need to sort.
            $order = $lps[$lp_id]['display_order'];
            if ($order > 1) { // If it's the first element, no need to move up.
                $sql_u1 = "UPDATE $lp_table SET display_order = $order WHERE c_id = ".$course_id." AND id = " . $lp_order[$order - 1];
                $res_u1 = Database::query($sql_u1);
                $sql_u2 = "UPDATE $lp_table SET display_order = " . ($order - 1) . " WHERE c_id = ".$course_id." AND id = " . $lp_id;
                $res_u2 = Database::query($sql_u2);
            }
        }
    }

    /**
     * Move a learnpath down (display_order)
     * @param	integer	Learnpath ID
     */
    public function move_down($lp_id) {
        $course_id = api_get_course_int_id(); 
        $lp_table = Database :: get_course_table(TABLE_LP_MAIN);
        $sql = "SELECT * FROM $lp_table WHERE c_id = ".$course_id." ORDER BY display_order";
        $res = Database::query($sql);
        if ($res === false)
            return false;
        $lps = array ();
        $lp_order = array ();
        $num = Database :: num_rows($res);
        $max = 0;
        // First check the order is correct, globally (might be wrong because
        // of versions < 1.8.4).
        if ($num > 0) {
            $i = 1;
            while ($row = Database :: fetch_array($res)) {
                $max = $i;
                if ($row['display_order'] != $i) { // If we find a gap in the order, we need to fix it.
                    $need_fix = true;
                    $sql_u = "UPDATE $lp_table SET display_order = $i 
                              WHERE c_id = ".$course_id." AND id = " . $row['id'];
                    $res_u = Database::query($sql_u);
                }
                $row['display_order'] = $i;
                $lps[$row['id']] = $row;
                $lp_order[$i] = $row['id'];
                $i++;
            }
        }
        if ($num > 1) { // If there's only one element, no need to sort.
            $order = $lps[$lp_id]['display_order'];
            if ($order < $max) { // If it's the first element, no need to move up.
                $sql_u1 = "UPDATE $lp_table SET display_order = $order 
                           WHERE c_id = ".$course_id." AND id = " . $lp_order[$order + 1];
                $res_u1 = Database::query($sql_u1);
                $sql_u2 = "UPDATE $lp_table SET display_order = " . ($order + 1) . " 
                           WHERE c_id = ".$course_id." AND id = " . $lp_id;
                $res_u2 = Database::query($sql_u2);
            }
        }
    }

    /**
     * Updates learnpath attributes to point to the next element
     * The last part is similar to set_current_item but processing the other way around
     */
    public function next() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::next()', 0);
        }
        $this->last = $this->get_current_item_id();
        $this->items[$this->last]->save(false, $this->prerequisites_match($this->last));
        $this->autocomplete_parents($this->last);
        $new_index = $this->get_next_index();
        if ($this->debug > 2) {
            error_log('New LP - New index: ' . $new_index, 0);
        }
        $this->index = $new_index;
        if ($this->debug > 2) {
            error_log('New LP - Now having orderedlist[' . $new_index . '] = ' . $this->ordered_items[$new_index], 0);
        }
        $this->current = $this->ordered_items[$new_index];
        if ($this->debug > 2) {
            error_log('New LP - new item id is ' . $this->current . '-' . $this->get_current_item_id(), 0);
        }
    }

    /**
     * Open a resource = initialise all local variables relative to this resource. Depending on the child
     * class, this might be redefined to allow several behaviours depending on the document type.
     * @param integer Resource ID
     * @return boolean True on success, false otherwise
     */
    public function open($id) {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::open()', 0);
        }
        // TODO:
        // set the current resource attribute to this resource
        // switch on element type (redefine in child class?)
        // set status for this item to "opened"
        // start timer
        // initialise score
        $this->index = 0; //or = the last item seen (see $this->last)
    }

    /**
     * Check that all prerequisites are fulfilled. Returns true and an empty string on succes, returns false
     * and the prerequisite string on error.
     * This function is based on the rules for aicc_script language as described in the SCORM 1.2 CAM documentation page 108.
     * @param	integer	Optional item ID. If none given, uses the current open item.
     * @return	boolean	True if prerequisites are matched, false otherwise - Empty string if true returned, prerequisites string otherwise.
     */
    public function prerequisites_match($item = null) {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::prerequisites_match()', 0);
        }
        if (empty ($item)) {
            $item = $this->current;
        }
        if (is_object($this->items[$item])) {
            $prereq_string = $this->items[$item]->get_prereq_string();
            if (empty ($prereq_string)) {
                return true;
            }
            // Clean spaces.
            $prereq_string = str_replace(' ', '', $prereq_string);
            if ($this->debug > 0) {
                error_log('Found prereq_string: ' . $prereq_string, 0);
            }
            // Now send to the parse_prereq() function that will check this component's prerequisites.
            $result = $this->items[$item]->parse_prereq($prereq_string, $this->items, $this->refs_list, $this->get_user_id());

            if ($result === false) {
                $this->set_error_msg($this->items[$item]->prereq_alert);
            }
        } else {
            $result = true;
            if ($this->debug > 1) {
                error_log('New LP - $this->items[' . $item . '] was not an object', 0);
            }
        }

        if ($this->debug > 1) {
            error_log('New LP - End of prerequisites_match(). Error message is now ' . $this->error, 0);
        }
        return $result;
    }

    /**
     * Updates learnpath attributes to point to the previous element
     * The last part is similar to set_current_item but processing the other way around
     */
    public function previous() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::previous()', 0);
        }
        $this->last = $this->get_current_item_id();
        $this->items[$this->last]->save(false, $this->prerequisites_match($this->last));
        $this->autocomplete_parents($this->last);
        $new_index = $this->get_previous_index();
        $this->index = $new_index;
        $this->current = $this->ordered_items[$new_index];
    }

    /**
     * Publishes a learnpath. This basically means show or hide the learnpath
     * to normal users.
     * Can be used as abstract
     * @param	integer	Learnpath ID
     * @param	string	New visibility
     */
    public function toggle_visibility($lp_id, $set_visibility = 1) {
        //if ($this->debug > 0) { error_log('New LP - In learnpath::toggle_visibility()', 0); }
        $action = 'visible';
        if ($set_visibility != 1) {
            $action = 'invisible';
        }
        return api_item_property_update(api_get_course_info(), TOOL_LEARNPATH, $lp_id, $action, api_get_user_id());
    }

    /**
     * Publishes a learnpath. This basically means show or hide the learnpath
     * on the course homepage
     * Can be used as abstract
     * @param	integer	Learnpath id
     * @param	string	New visibility (v/s - visible/invisible)
     */
    public function toggle_publish($lp_id, $set_visibility = 'v') {
        //if ($this->debug > 0) { error_log('New LP - In learnpath::toggle_publish()', 0); }
        $course_id = api_get_course_int_id();
        $tbl_lp = Database :: get_course_table(TABLE_LP_MAIN);
        $sql = "SELECT * FROM $tbl_lp where c_id = ".$course_id." AND id=$lp_id";
        $result = Database::query($sql);
        $row = Database :: fetch_array($result);
        $name = domesticate($row['name']);
        if ($set_visibility == 'i') {
            $s = $name . " " . get_lang('_no_published');
            $dialogBox = $s;
            $v = 0;
        }
        if ($set_visibility == 'v') {
            $s = $name . " " . get_lang('_published');
            $dialogBox = $s;
            $v = 1;
        }

        $session_id = api_get_session_id();
        $session_condition = api_get_session_condition($session_id);

        $tbl_tool = Database :: get_course_table(TABLE_TOOL_LIST);
        
        $course_id = api_get_course_int_id();
        
        $link = 'newscorm/lp_controller.php?action=view&lp_id=' . $lp_id.'&id_session='.$session_id;
        $sql = "SELECT * FROM $tbl_tool WHERE c_id = ".$course_id." AND name='$name' and image='scormbuilder.gif' and link LIKE '$link%' $session_condition";
        $result = Database::query($sql);
        $num = Database :: num_rows($result);
        $row2 = Database :: fetch_array($result);
        //if ($this->debug > 2) { error_log('New LP - '.$sql.' - '.$num, 0); }        
        if (($set_visibility == 'i') && ($num > 0)) {
            $sql = "DELETE FROM $tbl_tool WHERE c_id = ".$course_id." AND (name='$name' and image='scormbuilder.gif' and link LIKE '$link%' $session_condition)";
        } elseif (($set_visibility == 'v') && ($num == 0)) {
            $sql = "INSERT INTO $tbl_tool (c_id, name, link, image, visibility, admin, address, added_tool, session_id) VALUES 
            	    ($course_id, '$name','$link','scormbuilder.gif','$v','0','pastillegris.gif',0, $session_id)";            
        } else {            
            // Parameter and database incompatible, do nothing.
        }
        $result = Database::query($sql);
        //if ($this->debug > 2) { error_log('New LP - Leaving learnpath::toggle_visibility: '.$sql, 0); }
    }

    /**
     * Restart the whole learnpath. Return the URL of the first element.
     * Make sure the results are saved with anoter method. This method should probably be
     * redefined in children classes.
     * To use a similar method  statically, use the create_new_attempt() method
     * @return string URL to load in the viewer
     */
    public function restart() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::restart()', 0);
        }
        // TODO
        // Call autosave method to save the current progress.
        //$this->index = 0;
        $session_id = api_get_session_id();
        $course_id = api_get_course_int_id();
        $lp_view_table = Database :: get_course_table(TABLE_LP_VIEW);
        $sql = "INSERT INTO $lp_view_table (c_id, lp_id, user_id, view_count, session_id) " .
        	   "VALUES ($course_id, " . $this->lp_id . "," . $this->get_user_id() . "," . ($this->attempt + 1) . ", $session_id)";
        if ($this->debug > 2) {
            error_log('New LP - Inserting new lp_view for restart: ' . $sql, 0);
        }
        $res = Database::query($sql);
        if ($view_id = Database :: insert_id($res)) {
            $this->lp_view_id = $view_id;
            $this->attempt = $this->attempt + 1;
        } else {
            $this->error = 'Could not insert into item_view table...';
            return false;
        }
        $this->autocomplete_parents($this->current);
        foreach ($this->items as $index => $dummy) {
            $this->items[$index]->restart();
            $this->items[$index]->set_lp_view($this->lp_view_id);
        }
        $this->first();
        return true;
    }

    /**
     * Saves the current item
     * @return	boolean
     */
    public function save_current() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::save_current()', 0);
        }
        // TODO: Do a better check on the index pointing to the right item (it is supposed to be working
        // on $ordered_items[] but not sure it's always safe to use with $items[]).
        if ($this->debug > 2) {
            error_log('New LP - save_current() saving item ' . $this->current, 0);
        }
        if ($this->debug > 2) {
            error_log('' . print_r($this->items, true), 0);
        }
        if (is_object($this->items[$this->current])) {
            //$res = $this->items[$this->current]->save(false);
            $res = $this->items[$this->current]->save(false, $this->prerequisites_match($this->current));
            $this->autocomplete_parents($this->current);
            $status = $this->items[$this->current]->get_status();
            $this->append_message('new_item_status: ' . $status);
            $this->update_queue[$this->current] = $status;
            return $res;
        }
        return false;
    }

    /**
     * Saves the given item
     * @param	integer	Item ID. Optional (will take from $_REQUEST if null)
     * @param	boolean	Save from url params (true) or from current attributes (false). Optional. Defaults to true
     * @return	boolean
     */
    public function save_item($item_id = null, $from_outside = true) {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::save_item(' . $item_id . ',' . $from_outside . ')', 0);
        }
        // TODO: Do a better check on the index pointing to the right item (it is supposed to be working
        // on $ordered_items[] but not sure it's always safe to use with $items[]).
        if (empty ($item_id)) {
            $item_id = $this->escape_string($_REQUEST['id']);
        }
        if (empty ($item_id)) {
            $item_id = $this->get_current_item_id();
        }
        if ($this->debug > 2) {
            error_log('New LP - save_current() saving item ' . $item_id, 0);
        }
        if (is_object($this->items[$item_id])) {
            $res = $this->items[$item_id]->save($from_outside, $this->prerequisites_match($item_id));
            //$res = $this->items[$item_id]->save($from_outside);
            $this->autocomplete_parents($item_id);
            $status = $this->items[$item_id]->get_status();
            $this->append_message('new_item_status: ' . $status);
            $this->update_queue[$item_id] = $status;
            return $res;
        }
        return false;
    }

    /**
     * Saves the last item seen's ID only in case
     */
    public function save_last() {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::save_last()', 0);
        }
        $session_condition = api_get_session_condition(api_get_session_id(), true, false);
        $table = Database :: get_course_table(TABLE_LP_VIEW);
        if (isset ($this->current)) {
            if ($this->debug > 2) {
                error_log('New LP - Saving current item (' . $this->current . ') for later review', 0);
            }
            $sql = "UPDATE $table SET last_item = " . Database::escape_string($this->get_current_item_id()). " " .
                    "WHERE c_id = ".$course_id." AND lp_id = " . $this->get_id() . " AND user_id = " . $this->get_user_id().' '.$session_condition;

            if ($this->debug > 2) {
                error_log('New LP - Saving last item seen : ' . $sql, 0);
            }
            $res = Database::query($sql);
        }

        // Save progress.
        list($progress, $text) = $this->get_progress_bar_text('%');
        if ($progress >= 0 && $progress <= 100) {
            $progress = (int) $progress;
            $sql = "UPDATE $table SET progress = $progress " .
                    "WHERE c_id = ".$course_id." AND lp_id = " . $this->get_id() . " AND " .
                            "user_id = " . $this->get_user_id().' '.$session_condition;
            $res = Database::query($sql); // Ignore errors as some tables might not have the progress field just yet.
            $this->progress_db = $progress;
        }
    }

    /**
     * Sets the current item ID (checks if valid and authorized first)
     * @param	integer	New item ID. If not given or not authorized, defaults to current
     */
    public function set_current_item($item_id = null) {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::set_current_item(' . $item_id . ')', 0);
        }
        if (empty ($item_id)) {
            if ($this->debug > 2) {
                error_log('New LP - No new current item given, ignore...', 0);
            }
            // Do nothing.
        } else {
            if ($this->debug > 2) {
                error_log('New LP - New current item given is ' . $item_id . '...', 0);
            }
            if (is_numeric($item_id)) {
                $item_id = $this->escape_string($item_id);
                // TODO: Check in database here.
                $this->last = $this->current;
                $this->current = $item_id;
                // TODO: Update $this->index as well.
                foreach ($this->ordered_items as $index => $item) {
                    if ($item == $this->current) {
                        $this->index = $index;
                        break;
                    }
                }
                if ($this->debug > 2) {
                    error_log('New LP - set_current_item(' . $item_id . ') done. Index is now : ' . $this->index, 0);
                }
            } else {
                error_log('New LP - set_current_item(' . $item_id . ') failed. Not a numeric value: ', 0);
            }
        }
    }

    /**
     * Sets the encoding
     * @param	string	New encoding
     * TODO (as of Chamilo 1.8.8): Check in the future whether this method is needed.
     */
    public function set_encoding($enc = 'UTF-8') {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::set_encoding()', 0);
        }
        
        $course_id = api_get_course_int_id();

        /* // Deprecated code (Chamilo 1.8.8).
        $enc = strtoupper($enc);
        $encodings = array (
            'UTF-8',
            'ISO-8859-1',
            'ISO-8859-15',
            'cp1251',
            'cp1252',
            'KOI8-R',
            'BIG5',
            'GB2312',
            'Shift_JIS',
            'EUC-JP',
            ''
        );
        if (in_array($enc, $encodings)) { // TODO: Incorrect comparison, fix it.
            $lp = $this->get_id();
            if ($lp != 0) {
                $tbl_lp = Database :: get_course_table(TABLE_LP_MAIN);
                $sql = "UPDATE $tbl_lp SET default_encoding = '$enc' WHERE id = " . $lp;
                $res = Database::query($sql);
                return $res;
            }
        }
        return false;
        */

        $enc = api_refine_encoding_id($enc);
        if (empty($enc)) {
            $enc = api_get_system_encoding();
        }
        if (api_is_encoding_supported($enc)) {
            $lp = $this->get_id();
            if ($lp != 0) {
                $tbl_lp = Database :: get_course_table(TABLE_LP_MAIN);
                $sql = "UPDATE $tbl_lp SET default_encoding = '$enc' WHERE c_id = ".$course_id." AND id = " . $lp;
                $res = Database::query($sql);
                return $res;
            }
        }
        return false;
    }

    /**
     * Sets the JS lib setting in the database directly.
     * This is the JavaScript library file this lp needs to load on startup
     * @param	string	Proximity setting
     * @return  boolean True on update success. False otherwise.
     */
    public function set_jslib($lib = '') {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::set_jslib()', 0);
        }
        $lp = $this->get_id();
        $course_id = api_get_course_int_id();
        
        if ($lp != 0) {
            $tbl_lp = Database :: get_course_table(TABLE_LP_MAIN);
            $sql = "UPDATE $tbl_lp SET js_lib = '$lib' WHERE c_id = ".$course_id." AND id = " . $lp;
            $res = Database::query($sql);
            return $res;
        } else {
            return false;
        }
    }

    /**
     * Sets the name of the LP maker (publisher) (and save)
     * @param	string	Optional string giving the new content_maker of this learnpath
     * @return  boolean True
     */
    public function set_maker($name = '') {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::set_maker()', 0);
        }
        if (empty ($name))
            return false;
        $this->maker = $this->escape_string($name);
        $lp_table = Database :: get_course_table(TABLE_LP_MAIN);
        $course_id = api_get_course_int_id();
        $lp_id = $this->get_id();
        $sql = "UPDATE $lp_table SET content_maker = '" . $this->maker . "' WHERE c_id = ".$course_id." AND id = '$lp_id'";
        if ($this->debug > 2) {
            error_log('New LP - lp updated with new content_maker : ' . $this->maker, 0);
        }
        $res = Database::query($sql);
        return true;
    }

    /**
     * Sets the name of the current learnpath (and save)
     * @param	string	Optional string giving the new name of this learnpath
     * @return  boolean True/False
     */
    public function set_name($name = '') {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::set_name()', 0);
        }
        if (empty ($name))
            return false;

        $this->name = $this->escape_string($name);
        $lp_table = Database :: get_course_table(TABLE_LP_MAIN);
        $lp_id = $this->get_id();
        $course_id = api_get_course_int_id();
        $sql = "UPDATE $lp_table SET name = '" . $this->name . "' WHERE c_id = ".$course_id." AND id = '$lp_id'";
        if ($this->debug > 2) {
            error_log('New LP - lp updated with new name : ' . $this->name, 0);
        }
        $res = Database::query($sql);
        // If the lp is visible on the homepage, change his name there.
        if (Database::affected_rows()) {
            $table = Database :: get_course_table(TABLE_TOOL_LIST);
            $sql = 'UPDATE ' . $table . ' SET
                        name = "' . $this->name . '"
                    WHERE c_id = '.$course_id.' AND link = "newscorm/lp_controller.php?action=view&lp_id=' . $lp_id . '"';
            Database::query($sql);
        }
        return true;
    }

    /**
     * Set index specified prefix terms for all items in this path
     * @param   string  Comma-separated list of terms
     * @param   char Xapian term prefix
     * @return  boolean False on error, true otherwise
     */
    public function set_terms_by_prefix($terms_string, $prefix) {
        $course_id = api_get_course_int_id(); 
        if (api_get_setting('search_enabled') !== 'true')
            return false;
            
        if (!extension_loaded('xapian')) {
            return false;
        }

        $terms_string = trim($terms_string);
        $terms = explode(',', $terms_string);
        array_walk($terms, 'trim_value');

        $stored_terms = $this->get_common_index_terms_by_prefix($prefix);

        // Don't do anything if no change, verify only at DB, not the search engine.
        if ((count(array_diff($terms, $stored_terms)) == 0) && (count(array_diff($stored_terms, $terms)) == 0))
            return false;

        require_once 'xapian.php'; // TODO: Try catch every xapian use or make wrappers on API.
        require_once api_get_path(LIBRARY_PATH).'search/DokeosIndexer.class.php';
        require_once api_get_path(LIBRARY_PATH).'search/xapian/XapianQuery.php';
        require_once api_get_path(LIBRARY_PATH).'search/IndexableChunk.class.php';

        $items_table = Database :: get_course_table(TABLE_LP_ITEM);
        // TODO: Make query secure agains XSS : use member attr instead of post var.
        $lp_id = intval($_POST['lp_id']);
        $sql = "SELECT * FROM $items_table WHERE c_id = $course_id AND lp_id = $lp_id"; 
        $result = Database::query($sql);
        $di = new DokeosIndexer();

        while ($lp_item = Database :: fetch_array($result)) {
            // Get search_did.
            $tbl_se_ref = Database :: get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
            $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s AND ref_id_second_level=%d LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $this->cc, TOOL_LEARNPATH, $lp_id, $lp_item['id']);

            //echo $sql; echo '<br>';
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {

                $se_ref = Database :: fetch_array($res);

                // Compare terms.
                $doc = $di->get_document($se_ref['search_did']);

                $xapian_terms = xapian_get_doc_terms($doc, $prefix);
  
                $xterms = array ();
                foreach ($xapian_terms as $xapian_term) {
                    $xterms[] = substr($xapian_term['name'], 1);
                }

                $dterms = $terms;

                $missing_terms = array_diff($dterms, $xterms);
                $deprecated_terms = array_diff($xterms, $dterms);

                // Save it to search engine.
                foreach ($missing_terms as $term) {
                    $doc->add_term($prefix . $term, 1);
                }
                foreach ($deprecated_terms as $term) {
                    $doc->remove_term($prefix . $term);
                }
                $di->getDb()->replace_document((int) $se_ref['search_did'], $doc);
                $di->getDb()->flush();
            } else {
                //@todo What we should do here?
            }
        }        
        return true;
    }

    /**
     * Sets the theme of the LP (local/remote) (and save)
     * @param	string	Optional string giving the new theme of this learnpath
     * @return   bool    Returns true if theme name is not empty
     */
    public function set_theme($name = '') {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::set_theme()', 0);
        }
        $this->theme = $this->escape_string($name);
        $lp_table = Database :: get_course_table(TABLE_LP_MAIN);
        $lp_id = $this->get_id();
        $sql = "UPDATE $lp_table SET theme = '" . $this->theme . "' WHERE c_id = ".$course_id." AND id = '$lp_id'";
        if ($this->debug > 2) {
            error_log('New LP - lp updated with new theme : ' . $this->theme, 0);
        }
        //$res = Database::query($sql);
        $res = Database::query($sql);
        return true;
    }

    /**
     * Sets the image of an LP (and save)
     * @param	 string	Optional string giving the new image of this learnpath
     * @return bool   Returns true if theme name is not empty
     */
    public function set_preview_image($name = '') {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::set_preview_image()', 0);
        }
        
        $this->preview_image = $this->escape_string($name);
        $lp_table = Database :: get_course_table(TABLE_LP_MAIN);
        $lp_id = $this->get_id();
        $sql = "UPDATE $lp_table SET preview_image = '" . $this->preview_image . "' WHERE c_id = ".$course_id." AND id = '$lp_id'";
        if ($this->debug > 2) {
            error_log('New LP - lp updated with new preview image : ' . $this->preview_image, 0);
        }
        $res = Database::query($sql);
        return true;
    }

    /**
     * Sets the author of a LP (and save)
     * @param	string	Optional string giving the new author of this learnpath
     * @return   bool    Returns true if author's name is not empty
     */
    public function set_author($name = '') {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::set_author()', 0);
        }
        $this->author = $this->escape_string($name);
        $lp_table = Database :: get_course_table(TABLE_LP_MAIN);
        $lp_id = $this->get_id();
        $sql = "UPDATE $lp_table SET author = '" . $this->author . "' WHERE c_id = ".$course_id." AND id = '$lp_id'";
        if ($this->debug > 2) {
            error_log('New LP - lp updated with new preview author : ' . $this->author, 0);
        }
        $res = Database::query($sql);
        return true;
	}
	/**
	* Sets the hide_toc_frame parameter of a LP (and save)
	* @param	int	1 if frame is hiddent 0 thenelse
	* @return   bool    Returns true if author's name is not empty
	*/
	public function set_hide_toc_frame($hide) {
	    $course_id = api_get_course_int_id();
		if ($this->debug > 0) {
			error_log('New LP - In learnpath::set_hide_toc_frame()', 0);
		}
        if (intval($hide) == $hide){
            $this->hide_toc_frame = $hide;
            $lp_table = Database :: get_course_table(TABLE_LP_MAIN);
            $lp_id = $this->get_id();
            $sql = "UPDATE $lp_table SET hide_toc_frame = '" . $this->hide_toc_frame . "' 
            WHERE c_id = ".$course_id." AND id = '$lp_id'";
            if ($this->debug > 2) {
                error_log('New LP - lp updated with new preview hide_toc_frame : ' . $this->author, 0);
            }
            $res = Database::query($sql);
            return true;  
        } else {
            return false;
        }
    }

    /**
     * Sets the prerequisite of a LP (and save)
     * @param	int		integer giving the new prerequisite of this learnpath
     * @return 	bool 	returns true if prerequisite is not empty
     */
    public function set_prerequisite($prerequisite) {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::set_prerequisite()', 0);
        }
        $this->prerequisite = intval($prerequisite);
        $lp_table = Database :: get_course_table(TABLE_LP_MAIN);
        $lp_id = $this->get_id();
        $sql = "UPDATE $lp_table SET prerequisite = '".$this->prerequisite."' 
                WHERE c_id = ".$course_id." AND id = '$lp_id'";
        if ($this->debug > 2) {
            error_log('New LP - lp updated with new preview requisite : ' . $this->requisite, 0);
        }
        $res = Database::query($sql);
        return true;
    }

    /**
     * Sets the location/proximity of the LP (local/remote) (and save)
     * @param	string	Optional string giving the new location of this learnpath
     * @return  boolean True on success / False on error
     */
    public function set_proximity($name = '') {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::set_proximity()', 0);
        }
        if (empty ($name))
            return false;

        $this->proximity = $this->escape_string($name);
        $lp_table = Database :: get_course_table(TABLE_LP_MAIN);
        $lp_id = $this->get_id();
        $sql = "UPDATE $lp_table SET content_local = '" . $this->proximity . "' 
                WHERE c_id = ".$course_id." AND id = '$lp_id'";
        if ($this->debug > 2) {
            error_log('New LP - lp updated with new proximity : ' . $this->proximity, 0);
        }
        $res = Database::query($sql);
        return true;
    }

    /**
     * Sets the previous item ID to a given ID. Generally, this should be set to the previous 'current' item
     * @param	integer	DB ID of the item
     */
    public function set_previous_item($id) {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::set_previous_item()', 0);
        }
        $this->last = $id;
    }


     /**
     * Sets use_max_score
     * @param   string  Optional string giving the new location of this learnpath
     * @return  boolean True on success / False on error
     */
    public function set_use_max_score($use_max_score = 1) {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::set_use_max_score()', 0);
        }        
        $use_max_score = intval($use_max_score);
        $this->use_max_score = $use_max_score;
        $lp_table = Database :: get_course_table(TABLE_LP_MAIN);
        $lp_id = $this->get_id();
        $sql = "UPDATE $lp_table SET use_max_score = '" . $this->use_max_score . "' WHERE c_id = ".$course_id." AND id = '$lp_id'";
        
        if ($this->debug > 2) {
            error_log('New LP - lp updated with new use_max_score : ' . $this->use_max_score, 0);
        }
        $res = Database::query($sql);
        return true;
    }

     /**
     * Sets and saves the expired_on date
     * @param   string  Optional string giving the new author of this learnpath
     * @return   bool    Returns true if author's name is not empty
     */
    public function set_expired_on($expired_on) {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::set_expired_on()', 0);
        }

        if (!empty($expired_on)) {
            $this->expired_on = $this->escape_string(api_get_utc_datetime($expired_on));
        } else {
            $this->expired_on = '';
        }
        $lp_table = Database :: get_course_table(TABLE_LP_MAIN);
        $lp_id = $this->get_id();
        $sql = "UPDATE $lp_table SET expired_on = '" . $this->expired_on . "' WHERE c_id = ".$course_id." AND id = '$lp_id'";
        if ($this->debug > 2) {
            error_log('New LP - lp updated with new expired_on : ' . $this->expired_on, 0);
        }
        $res = Database::query($sql);
        return true;
    }


    /**
     * Sets and saves the publicated_on date
     * @param   string  Optional string giving the new author of this learnpath
     * @return   bool    Returns true if author's name is not empty
     */
    public function set_publicated_on($publicated_on) {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::set_expired_on()', 0);
        }
        if (!empty($publicated_on)) {
            $this->publicated_on = $this->escape_string(api_get_utc_datetime($publicated_on));
        } else {
            $this->publicated_on = '';
        }
        $lp_table = Database :: get_course_table(TABLE_LP_MAIN);
        $lp_id = $this->get_id();
        $sql = "UPDATE $lp_table SET publicated_on = '" . $this->publicated_on . "' WHERE c_id = ".$course_id." AND id = '$lp_id'";
        if ($this->debug > 2) {
            error_log('New LP - lp updated with new publicated_on : ' . $this->publicated_on, 0);
        }
        $res = Database::query($sql);
        return true;
    }



    /**
     * Sets and saves the expired_on date
     * @param   string  Optional string giving the new author of this learnpath
     * @return   bool    Returns true if author's name is not empty
     */
    public function set_modified_on() {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::set_expired_on()', 0);
        }
        $this->modified_on = api_get_utc_datetime();
        $lp_table = Database :: get_course_table(TABLE_LP_MAIN);
        $lp_id = $this->get_id();
        $sql = "UPDATE $lp_table SET modified_on = '" . $this->modified_on . "' WHERE c_id = ".$course_id." AND id = '$lp_id'";
        if ($this->debug > 2) {
            error_log('New LP - lp updated with new expired_on : ' . $this->modified_on, 0);
        }
        $res = Database::query($sql);
        return true;
    }

    /**
     * Sets the object's error message
     * @param	string	Error message. If empty, reinits the error string
     * @return 	void
     */
    public function set_error_msg($error = '') {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::set_error_msg()', 0);
        }
        if (empty ($error)) {
            $this->error = '';
        } else {
            $this->error .= $error;
        }
    }

    /**
     * Launches the current item if not 'sco' (starts timer and make sure there is a record ready in the DB)
     * @param  boolean     Whether to allow a new attempt or not
     * @return boolean     True
     */
    public function start_current_item($allow_new_attempt = false) {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::start_current_item()', 0);
        }
        if ($this->current != 0 AND is_object($this->items[$this->current])) {
            $type = $this->get_type();
            $item_type = $this->items[$this->current]->get_type();
            if (($type == 2 && $item_type != 'sco') OR ($type == 3 && $item_type != 'au') OR ($type == 1 && $item_type != TOOL_QUIZ && $item_type != TOOL_HOTPOTATOES)) {
                $this->items[$this->current]->open($allow_new_attempt);

                $this->autocomplete_parents($this->current);
                $prereq_check = $this->prerequisites_match($this->current);
                $this->items[$this->current]->save(false, $prereq_check);
                //$this->update_queue[$this->last] = $this->items[$this->last]->get_status();
            } else {
                // If sco, then it is supposed to have been updated by some other call.
            }
            if ($item_type == 'sco') {
                $this->items[$this->current]->restart();
            }
        }
        if ($this->debug > 0) {
            error_log('New LP - End of learnpath::start_current_item()', 0);
        }
        return true;
    }

    /**
     * Stops the processing and counters for the old item (as held in $this->last)
     * @return boolean  True/False
     */
    public function stop_previous_item() {
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::stop_previous_item()', 0);
        }

        if ($this->last != 0 && $this->last != $this->current && is_object($this->items[$this->last])) {
            if ($this->debug > 2) {
                error_log('New LP - In learnpath::stop_previous_item() - ' . $this->last . ' is object', 0);
            }
            switch ($this->get_type()) {
                case '3' :
                    if ($this->items[$this->last]->get_type() != 'au') {
                        if ($this->debug > 2) {
                            error_log('New LP - In learnpath::stop_previous_item() - ' . $this->last . ' in lp_type 3 is <> au', 0);
                        }
                        $this->items[$this->last]->close();
                        //$this->autocomplete_parents($this->last);
                        //$this->update_queue[$this->last] = $this->items[$this->last]->get_status();
                    } else {
                        if ($this->debug > 2) {
                            error_log('New LP - In learnpath::stop_previous_item() - Item is an AU, saving is managed by AICC signals', 0);
                        }
                    }
                case '2' :
                    if ($this->items[$this->last]->get_type() != 'sco') {
                        if ($this->debug > 2) {
                            error_log('New LP - In learnpath::stop_previous_item() - ' . $this->last . ' in lp_type 2 is <> sco', 0);
                        }
                        $this->items[$this->last]->close();
                        //$this->autocomplete_parents($this->last);
                        //$this->update_queue[$this->last] = $this->items[$this->last]->get_status();
                    } else {
                        if ($this->debug > 2) {
                            error_log('New LP - In learnpath::stop_previous_item() - Item is a SCO, saving is managed by SCO signals', 0);
                        }
                    }
                    break;
                case '1' :
                default :
                    if ($this->debug > 2) {
                        error_log('New LP - In learnpath::stop_previous_item() - ' . $this->last . ' in lp_type 1 is asset', 0);
                    }
                    $this->items[$this->last]->close();
                    break;
            }
        } else {
            if ($this->debug > 2) {
                error_log('New LP - In learnpath::stop_previous_item() - No previous element found, ignoring...', 0);
            }
            return false;
        }
        return true;
    }

    /**
     * Updates the default view mode from fullscreen to embedded and inversely
     * @return	string The current default view mode ('fullscreen' or 'embedded')
     */
    public function update_default_view_mode() {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::update_default_view_mode()', 0);
        }
        $lp_table = Database :: get_course_table(TABLE_LP_MAIN);
        $sql = "SELECT * FROM $lp_table WHERE c_id = ".$course_id." AND id = " . $this->get_id();
        $res = Database::query($sql);
        if (Database :: num_rows($res) > 0) {
            $row = Database :: fetch_array($res);
            $view_mode = $row['default_view_mod'];
            if ($view_mode == 'fullscreen') {
                $view_mode = 'embedded';
            } elseif ($view_mode == 'embedded') {
                $view_mode = 'embedframe';
            } elseif ($view_mode == 'embedframe') {
                $view_mode = 'fullscreen';            	
            }
            $sql = "UPDATE $lp_table SET default_view_mod = '$view_mode' WHERE c_id = ".$course_id." AND id = " . $this->get_id();
            $res = Database::query($sql);
            $this->mode = $view_mode;
            return $view_mode;
        } else {
            if ($this->debug > 2) {
                error_log('New LP - Problem in update_default_view() - could not find LP ' . $this->get_id() . ' in DB', 0);
            }
        }
        return -1;
    }

    /**
     * Updates the default behaviour about auto-commiting SCORM updates
     * @return	boolean	True if auto-commit has been set to 'on', false otherwise
     */
    public function update_default_scorm_commit() {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::update_default_scorm_commit()', 0);
        }
        $lp_table = Database :: get_course_table(TABLE_LP_MAIN);
        $sql = "SELECT * FROM $lp_table WHERE c_id = ".$course_id." AND id = " . $this->get_id();
        $res = Database::query($sql);
        if (Database :: num_rows($res) > 0) {
            $row = Database :: fetch_array($res);
            $force = $row['force_commit'];
            if ($force == 1) {
                $force = 0;
                $force_return = false;
            } elseif ($force == 0) {
                $force = 1;
                $force_return = true;
            }
            $sql = "UPDATE $lp_table SET force_commit = $force WHERE c_id = ".$course_id." AND id = " . $this->get_id();
            $res = Database::query($sql);
            $this->force_commit = $force_return;
            return $force_return;
        } else {
            if ($this->debug > 2) {
                error_log('New LP - Problem in update_default_scorm_commit() - could not find LP ' . $this->get_id() . ' in DB', 0);
            }
        }
        return -1;
    }

    /**
     * Updates the order of learning paths (goes through all of them by order and fills the gaps)
     * @return	bool	True on success, false on failure
     */
    public function update_display_order() {
        $course_id = api_get_course_int_id();        
        $lp_table = Database :: get_course_table(TABLE_LP_MAIN);
        
        $sql = "SELECT * FROM $lp_table WHERE c_id = ".$course_id." ORDER BY display_order";
        $res = Database::query($sql);
        if ($res === false)
            return false;
        $lps = array ();
        $lp_order = array ();
        $num = Database :: num_rows($res);
        // First check the order is correct, globally (might be wrong because
        // of versions < 1.8.4).
        if ($num > 0) {
            $i = 1;
            while ($row = Database :: fetch_array($res)) {
                if ($row['display_order'] != $i) { // If we find a gap in the order, we need to fix it.
                    $need_fix = true;
                    $sql_u = "UPDATE $lp_table SET display_order = $i WHERE c_id = ".$course_id." AND id = " . $row['id'];
                    $res_u = Database::query($sql_u);
                }
                $i++;
            }
        }
        return true;
    }

    /**
     * Updates the "prevent_reinit" value that enables control on reinitialising items on second view
     * @return	boolean	True if prevent_reinit has been set to 'on', false otherwise (or 1 or 0 in this case)
     */
    public function update_reinit() {
        $course_id = api_get_course_int_id();                
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::update_reinit()', 0);
        }
        $lp_table = Database :: get_course_table(TABLE_LP_MAIN);
        $sql = "SELECT * FROM $lp_table WHERE c_id = ".$course_id." AND id = " . $this->get_id();
        $res = Database::query($sql);
        if (Database :: num_rows($res) > 0) {
            $row = Database :: fetch_array($res);
            $force = $row['prevent_reinit'];
            if ($force == 1) {
                $force = 0;
            } elseif ($force == 0) {
                $force = 1;
            }
            $sql = "UPDATE $lp_table SET prevent_reinit = $force WHERE c_id = ".$course_id." AND id = " . $this->get_id();
            $res = Database::query($sql);
            $this->prevent_reinit = $force;
            return $force;
        } else {
            if ($this->debug > 2) {
                error_log('New LP - Problem in update_reinit() - could not find LP ' . $this->get_id() . ' in DB', 0);
            }
        }
        return -1;
    }

  /**
   * Determine the attempt_mode thanks to prevent_reinit and seriousgame_mode db flag
   *
   * @return string 'single', 'multi' or 'seriousgame'
   * @author ndiechburg <noel@cblue.be>
   **/
  public function get_attempt_mode() {
    if (!isset($this->seriousgame_mode)) { //Set default value for seriousgame_mode
      $this->seriousgame_mode=0;
    }
    if (!isset($this->prevent_reinit)) { // Set default value for prevent_reinit
      $this->prevent_reinit =1;
    }
    if ($this->seriousgame_mode == 1 && $this->prevent_reinit == 1) {
      return 'seriousgame';
    }
    if ($this->seriousgame_mode == 0 && $this->prevent_reinit == 1) {
      return 'single';
    }
    if ($this->seriousgame_mode == 0 && $this->prevent_reinit == 0) {
      return 'multiple';
    }
    return 'single';
  }

  /**
   * Register the attempt mode into db thanks to flags prevent_reinit and seriousgame_mode flags
   *
   * @param string 'seriousgame', 'single' or 'multiple'
   * @return boolean
   * @author ndiechburg <noel@cblue.be>
   **/
  public function set_attempt_mode($mode) {
        $course_id = api_get_course_int_id();
        switch ($mode) {
    case 'seriousgame' : 
      $sg_mode = 1;
      $prevent_reinit = 1;
      break;
    case 'single' : 
      $sg_mode = 0;
      $prevent_reinit = 1;
      break;
    case 'multiple' : 
      $sg_mode = 0;
      $prevent_reinit = 0;
      break;
    default :
      $sg_mode = 0;
      $prevent_reinit = 0;
      break;
    }
    $this->prevent_reinit = $prevent_reinit;
    $this->seriousgame_mode = $sg_mode;
		$lp_table = Database :: get_course_table(TABLE_LP_MAIN);
    $sql = "UPDATE $lp_table SET prevent_reinit = $prevent_reinit , seriousgame_mode = $sg_mode WHERE c_id = ".$course_id." AND id = " . $this->get_id();
    $res = Database::query($sql);
    if ($res) {
      return true;
    }
    else {
      return false;
    }
  }

  /**
   * switch between multiple attempt, single attempt or serious_game mode (only for scorm)
   *
   * @return boolean
   * @author ndiechburg <noel@cblue.be>
   **/
    public function switch_attempt_mode() {
        if ($this->debug > 0) {
          error_log('New LP - In learnpath::switch_attempt_mode()', 0);
        }
        $mode = $this->get_attempt_mode();
        switch ($mode) {
        case 'single' :
          $next_mode = 'multiple';
          break;
        case 'multiple' :
          $next_mode = 'seriousgame';
          break;
        case 'seriousgame' :
          $next_mode = 'single';
          break;
        default : 
          $next_mode = 'single';
          break;
        }
        $this->set_attempt_mode($next_mode);
    }

  /**
   * Swithc the lp in ktm mode. This is a special scorm mode with unique attempt but possibility to do again a completed item.
   *
   * @return boolean true if seriousgame_mode has been set to 1, false otherwise
   * @author ndiechburg <noel@cblue.be>
   **/
    public function set_seriousgame_mode() {
        $course_id = api_get_course_int_id();
		if ($this->debug > 0) {
			error_log('New LP - In learnpath::set_seriousgame_mode()', 0);
		}
		$lp_table = Database :: get_course_table(TABLE_LP_MAIN);
		$sql = "SELECT * FROM $lp_table WHERE c_id = ".$course_id." AND id = " . $this->get_id();
		$res = Database::query($sql);
		if (Database :: num_rows($res) > 0) {
			$row = Database :: fetch_array($res);
			$force = $row['seriousgame_mode'];
			if ($force == 1) {
				$force = 0;
			} elseif ($force == 0) {
				$force = 1;
			}
			$sql = "UPDATE $lp_table SET seriousgame_mode = $force WHERE c_id = ".$course_id." AND id = " . $this->get_id();
			$res = Database::query($sql);
			$this->seriousgame_mode = $force;
			return $force;
		} else {
			if ($this->debug > 2) {
				error_log('New LP - Problem in set_seriousgame_mode() - could not find LP ' . $this->get_id() . ' in DB', 0);
			}
		}
		return -1;

  }
    /**
     * Updates the "scorm_debug" value that shows or hide the debug window
     * @return	boolean	True if scorm_debug has been set to 'on', false otherwise (or 1 or 0 in this case)
     */
    public function update_scorm_debug() {
        $course_id = api_get_course_int_id();
        if ($this->debug > 0) {
            error_log('New LP - In learnpath::update_scorm_debug()', 0);
        }
        $lp_table = Database :: get_course_table(TABLE_LP_MAIN);
        $sql = "SELECT * FROM $lp_table WHERE c_id = ".$course_id." AND id = " . $this->get_id();
        $res = Database::query($sql);
        if (Database :: num_rows($res) > 0) {
            $row = Database :: fetch_array($res);
            $force = $row['debug'];
            if ($force == 1) {
                $force = 0;
            } elseif ($force == 0) {
                $force = 1;
            }
            $sql = "UPDATE $lp_table SET debug = $force WHERE c_id = ".$course_id." AND id = " . $this->get_id();
            $res = Database::query($sql);
            $this->scorm_debug = $force;
            return $force;
        } else {
            if ($this->debug > 2) {
                error_log('New LP - Problem in update_scorm_debug() - could not find LP ' . $this->get_id() . ' in DB', 0);
            }
        }
        return -1;
    }

    /**
     * Function that makes a call to the function sort_tree_array and create_tree_array
     * @author Kevin Van Den Haute
     * @param  array
     */
    public function tree_array($array) {
        if ($this->debug > 1) {
            error_log('New LP - In learnpath::tree_array()', 0);
        }
        $array = $this->sort_tree_array($array);
        $this->create_tree_array($array);
    }

    /**
     * Creates an array with the elements of the learning path tree in it
     *
     * @author Kevin Van Den Haute
     * @param array $array
     * @param int $parent
     * @param int $depth
     * @param array $tmp
     */
    public function create_tree_array($array, $parent = 0, $depth = -1, $tmp = array ()) {
        if ($this->debug > 1) {
            error_log('New LP - In learnpath::create_tree_array())', 0);
        }
        if (is_array($array)) {
            for ($i = 0; $i < count($array); $i++) {
                if ($array[$i]['parent_item_id'] == $parent) {
                    if (!in_array($array[$i]['parent_item_id'], $tmp)) {
                        $tmp[] = $array[$i]['parent_item_id'];
                        $depth++;
                    }
                    $preq = (empty($array[$i]['prerequisite']) ? '' : $array[$i]['prerequisite']);
                    $audio = isset($array[$i]['audio']) ? $array[$i]['audio'] : null;
                    $this->arrMenu[] = array (
                        'id' => $array[$i]['id'],
                        'item_type' => $array[$i]['item_type'],
                        'title' => $array[$i]['title'],
                        'path' => $array[$i]['path'],
                        'description' => $array[$i]['description'],
                        'parent_item_id' => $array[$i]['parent_item_id'],
                        'previous_item_id' => $array[$i]['previous_item_id'],
                        'next_item_id' => $array[$i]['next_item_id'],
                        'min_score' => $array[$i]['min_score'],
                        'max_score' => $array[$i]['max_score'],
                        'mastery_score' => $array[$i]['mastery_score'],
                        'display_order' => $array[$i]['display_order'],
                        'prerequisite' => $preq,
                        'depth' => $depth,
                        'audio' => $audio
                    );

                    $this->create_tree_array($array, $array[$i]['id'], $depth, $tmp);
                }
            }
        }
    }

    /**
     * Sorts a multi dimensional array by parent id and display order
     * @author Kevin Van Den Haute
     *
     * @param array $array (array with al the learning path items in it)
     *
     * @return array
     */
    public function sort_tree_array($array) {
        foreach ($array as $key => $row) {
            $parent[$key] = $row['parent_item_id'];
            $position[$key] = $row['display_order'];
        }

        if (count($array) > 0)
            array_multisort($parent, SORT_ASC, $position, SORT_ASC, $array);

        return $array;
    }

    /**
     * Function that creates a table structure with a learning path his modules, chapters and documents.
     * Also the actions for the modules, chapters and documents are in this table.
     * @author Kevin Van Den Haute
     * @param int $lp_id
     * @return string
     */
    public function overview() {        
        $is_allowed_to_edit = api_is_allowed_to_edit(null,true);

        if ($this->debug > 0) {
            error_log('New LP - In learnpath::overview()', 0);
        }
        global $_course;
        $_SESSION['gradebook'] = isset($_GET['gradebook']) ? Security :: remove_XSS($_GET['gradebook']) : null;
        $return = '';

        $update_audio = isset($_GET['updateaudio']) ? $_GET['updateaudio'] : null;        
        if ($is_allowed_to_edit) {            

            $gradebook = isset($_GET['gradebook']) ? Security :: remove_XSS($_GET['gradebook']) : null;
            $return .= '<div class="actions">';
            $return .= '<a href="' . api_get_self() . '?cidReq=' . Security :: remove_XSS($_GET['cidReq']) . '&amp;gradebook=' . $gradebook . '&amp;action=build&amp;lp_id=' . $this->lp_id . '" title="' . get_lang('Build') . '">' . Display :: return_icon('build_learnpath.png', get_lang('Build'),'',ICON_SIZE_MEDIUM).'</a>';
            if ($update_audio == 'true') {
                $return .='<a href="lp_controller.php?cidReq='.Security::remove_XSS($_GET['cidReq']) .'&amp;gradebook='.$gradebook.'&amp;action=admin_view&amp;lp_id=' . $_SESSION['oLP']->lp_id . '" title="'.get_lang('BasicOverview').'">'.Display::return_icon('move_learnpath.png', get_lang('BasicOverview'),'',ICON_SIZE_MEDIUM).'</a>';
            } else {
                $return .= Display :: return_icon('move_learnpath_na.png', get_lang('BasicOverview'),'',ICON_SIZE_MEDIUM);
            }
            
            $return .= '<a href="lp_controller.php?cidReq=' . Security :: remove_XSS($_GET['cidReq']) . '&action=view&lp_id=' . $this->lp_id . '">' . Display :: return_icon('view_left_right.png', get_lang('Display'),'',ICON_SIZE_MEDIUM).'</a>';
            $return .= ' '.Display :: return_icon('i.gif');	
            $return .= '<a href="' . api_get_self() . '?cidReq=' . Security :: remove_XSS($_GET['cidReq']) . '&amp;gradebook=' . $gradebook . '&amp;action=add_item&amp;type=step&amp;lp_id=' . $_SESSION['oLP']->lp_id . '" title="' . get_lang('NewStep') . '">
			 '. Display :: return_icon('new_learnigpath_object.png', get_lang('NewStep'),'',ICON_SIZE_MEDIUM).'</a>';            
            if ($update_audio == 'true') {            
                $return .= Display::url(Display :: return_icon('upload_audio_na.png', get_lang('UpdateAllAudioFragments'),'',ICON_SIZE_MEDIUM),'#');
            } else {
                $return .= '<a href="' . api_get_self() . '?cidReq=' . Security :: remove_XSS($_GET['cidReq']) . '&amp;action=' . Security :: remove_XSS($_GET['action']) . '&amp;lp_id=' . $_SESSION['oLP']->lp_id . '&amp;updateaudio=true">' . Display :: return_icon('upload_audio.png', get_lang('UpdateAllAudioFragments'),'',ICON_SIZE_MEDIUM).'</a>';
            }
            $return .= '<a href="lp_controller.php?cidReq=' . Security :: remove_XSS($_GET['cidReq']) . '&amp;action=edit&amp;lp_id=' . $_SESSION['oLP']->lp_id . '">' . Display :: return_icon('settings.png', get_lang('CourseSettings'),'',ICON_SIZE_MEDIUM).'</a>';
            $return .= '</div>';
        }

        // we need to start a form when we want to update all the mp3 files
        if ($update_audio == 'true') {
            $return .= '<form action="' . api_get_self() . '?cidReq=' . Security :: remove_XSS($_GET['cidReq']) . '&amp;updateaudio=' . Security :: remove_XSS($_GET['updateaudio']) .'&amp;action=' . Security :: remove_XSS($_GET['action']) . '&amp;lp_id=' . $_SESSION['oLP']->lp_id . '" method="post" enctype="multipart/form-data" name="updatemp3" id="updatemp3">';            
        }
        $return .= '<div id="message"></div>';
        
        $return_audio = '<table class="data_table">';
        $return_audio .= '<tr>';
        $return_audio .= '<th width="60%">' . get_lang('Title') . '</th>';
        $return_audio .= '<th>' . get_lang('Audio') . '</th>';                
   		$return_audio .= '</tr>';
      			
        if ($update_audio != 'true') {
        	$return .= '<div class="span12">';
            $return .= self::return_new_tree($update_audio);
        	$return .='</div>';        	
        	$return .= Display::div(Display::url(get_lang('Save'), '#', array('id'=>'listSubmit', 'class'=>'btn')), array('style'=>'float:left; margin-top:15px;width:100%'));
        } else {        
            $return_audio .= self::return_new_tree($update_audio);
        	$return .= $return_audio.'</table>';
        }
       
        // We need to close the form when we are updating the mp3 files.
        if ($update_audio == 'true') {
            $return .= '<div style="margin:40px 0; float:right;"><button class="save" type="submit" name="save_audio" id="save_audio">' . get_lang('SaveAudioAndOrganization') . '</button></div>'; // TODO: What kind of language variable is this?
        }

        // We need to close the form when we are updating the mp3 files.
        if ($update_audio == 'true' && count($arrLP) != 0) {
            $return .= '</form>';
        }
        return $return;
    }
    
    public function return_new_tree($update_audio = 'false') {
        $is_allowed_to_edit = api_is_allowed_to_edit(null,true);
        
        $course_id = api_get_course_int_id();
        $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);
        
        $sql = "SELECT * FROM $tbl_lp_item
                WHERE c_id = $course_id AND lp_id = ".$this->lp_id;

        $result = Database::query($sql);
        $arrLP = array ();
        while ($row = Database :: fetch_array($result)) {
            $row['title'] = Security :: remove_XSS($row['title']);
            $row['description'] = Security :: remove_XSS($row['description']);
            $arrLP[] = array (
                'id' => $row['id'],
                'item_type' => $row['item_type'],
                'title' => $row['title'],
                'path' => $row['path'],
                'description' => $row['description'],
                'parent_item_id' => $row['parent_item_id'],
                'previous_item_id' => $row['previous_item_id'],
                'next_item_id' => $row['next_item_id'],
                'max_score' => $row['max_score'],
                'min_score' => $row['min_score'],
                'mastery_score' => $row['mastery_score'],
                'prerequisite' => $row['prerequisite'],
                'display_order' => $row['display_order'],
                'audio' => $row['audio']
            );
        }
        
        $this->tree_array($arrLP);        
        $arrLP = $this->arrMenu;
        unset ($this->arrMenu);
        
        $elements = array();
        for ($i = 0; $i < count($arrLP); $i++) {
            $title = $arrLP[$i]['title'];
            if (($i % 2) == 0) {
                $oddclass = 'row_odd';
            } else {
                $oddclass = 'row_even';
            }
            $return_audio .= '<tr id ="lp_item_'.$arrLP[$i]['id'] .'" class="' . $oddclass . '">';            
                      
            $icon_name = str_replace(' ', '', $arrLP[$i]['item_type']);

            $icon = '';
            if (file_exists('../img/lp_' . $icon_name . '.png')) {
            	$icon = '<img align="left" src="../img/lp_' . $icon_name . '.png" style="margin-right:3px;" />';
            } else {
            	if (file_exists('../img/lp_' . $icon_name . '.gif')) {
            		$icon = '<img align="left" src="../img/lp_' . $icon_name . '.gif" style="margin-right:3px;" />';
            	} else {
            		$icon = '<img align="left" src="../img/folder_document.gif" style="margin-right:3px;" />';
            	}
            }

            // The audio column.            
            $return_audio  .= '<td align="center">';
            
            $audio = '';
            if (!$update_audio OR $update_audio <> 'true') {
                if (!empty ($arrLP[$i]['audio'])) {
                    $audio .= '<span id="container'.$i.'"><a href="http://www.macromedia.com/go/getflashplayer">Get the Flash Player</a> to see this player.</span>';
                    $audio .= '<script type="text/javascript" src="../inc/lib/mediaplayer/swfobject.js"></script>';
                    $audio .= '<script type="text/javascript">
                                                                var s1 = new SWFObject("../inc/lib/mediaplayer/player.swf","ply","250","20","9","#FFFFFF");
                                                                s1.addParam("allowscriptaccess","always");
                                                                s1.addParam("flashvars","file=../../courses/' . $_course['path'] . '/document/audio/' . $arrLP[$i]['audio'] . '");
                                                                s1.write("container' . $i . '");
                                                            </script>';
                } else {
                    $audio .= '';
                }
            } else {
                if ($arrLP[$i]['item_type'] != 'dokeos_chapter' && $arrLP[$i]['item_type'] != 'dokeos_module' && $arrLP[$i]['item_type'] != 'dir') {
                    $audio .= '<input type="file" name="mp3file' . $arrLP[$i]['id'] . '" id="mp3file" />';
                    if (!empty ($arrLP[$i]['audio'])) {
                        $audio .= '<br />'.Security::remove_XSS($arrLP[$i]['audio']).'<br /><input type="checkbox" name="removemp3' . $arrLP[$i]['id'] . '" id="checkbox' . $arrLP[$i]['id'] . '" />' . get_lang('RemoveAudio');
                    }
                }
            }
            $return_audio .= Display::span($title.$icon).Display::tag('td', $audio, array('style'=>''));
            $return_audio .= '</td>';
			$move_icon = '';
			$edit_icon = '';
			$delete_icon = '';
            
            if ($is_allowed_to_edit) {
                if (!$update_audio OR $update_audio <> 'true') {					
                    $move_icon .= '<a class="moved" href="#">';
					$move_icon .= Display::return_icon('move_everywhere.png', get_lang('Move'), array(), ICON_SIZE_TINY);
                    $move_icon .= '</a>';
                }
				
                if ($arrLP[$i]['item_type'] != 'dokeos_chapter' && $arrLP[$i]['item_type'] != 'dokeos_module') {
                    $edit_icon .= '<a href="' . api_get_self() . '?cidReq=' . Security :: remove_XSS($_GET['cidReq']) . '&amp;action=edit_item&amp;view=build&amp;id=' . $arrLP[$i]['id'] . '&amp;lp_id=' . $this->lp_id . '&amp;path_item=' . $arrLP[$i]['path'] . '">';                    
                    $edit_icon .= Display::return_icon('edit.png', get_lang('_edit_learnpath_module'), array(), ICON_SIZE_TINY);
                    $edit_icon .= '</a>';
                } else {
                    $edit_icon .= '<a href="' . api_get_self() . '?cidReq=' . Security :: remove_XSS($_GET['cidReq']) . '&amp;action=edit_item&amp;id=' . $arrLP[$i]['id'] . '&amp;lp_id=' . $this->lp_id . '&amp;path_item=' . $arrLP[$i]['path'] . '">';                    
                    $edit_icon .= Display::return_icon('edit.png', get_lang('_edit_learnpath_module'), array(), ICON_SIZE_TINY);
                    $edit_icon .= '</a>';
                }

                $delete_icon .= ' <a href="' . api_get_self() . '?cidReq=' . Security :: remove_XSS($_GET['cidReq']) . '&amp;action=delete_item&amp;id=' . $arrLP[$i]['id'] . '&amp;lp_id=' . $this->lp_id . '" onClick="return confirmation(\'' . addslashes($title) . '\');">';
                $delete_icon .= Display::return_icon('delete.png', get_lang('_delete_learnpath_module'), array(), ICON_SIZE_TINY);
                $delete_icon .= '</a>';
            }
            if ($update_audio != 'true') {
            	$row = $move_icon.Display::span($title.$icon).Display::span($audio.$edit_icon.$delete_icon, array('class'=>'button_actions'));
            } else {
            	$row = Display::span($title.$icon).Display::span($audio, array('class'=>'button_actions'));            	
            }           
            $parent_id = $arrLP[$i]['parent_item_id'];
            
            $default_data[$arrLP[$i]['id']] = $row;
            $default_content[$arrLP[$i]['id']] = $arrLP[$i];
            
            if (empty($parent_id)) {
            	$elements[$arrLP[$i]['id']]['data'] = $row;
            	$elements[$arrLP[$i]['id']]['type'] = $arrLP[$i]['item_type'];
            } else {            
            	$parent_arrays = array();	
            	if ($arrLP[$i]['depth'] > 1) {            		
            		//Getting list of parents
            		for($j = 0; $j < $arrLP[$i]['depth']; $j++) {            			            			
            			foreach($arrLP as $item) {            				
            				if ($item['id'] == $parent_id) {            					
            					if ($item['parent_item_id'] == 0) {
            						$parent_id = $item['id'];            						
            						break;
            					} else {
            						$parent_id = $item['parent_item_id'];
            						if (empty($parent_arrays)) {
            							$parent_arrays[] = intval($item['id']);
            						}
            						$parent_arrays[] = $parent_id;            						
            						break;
            					} 
            				}            				
            			}	            		
            		}
            	}
                
            	if (!empty($parent_arrays)) {
	            	$parent_arrays = array_reverse($parent_arrays);	            	
	            	$val = '$elements';
	            	$x = 0;	            	
	            	foreach($parent_arrays as $item) {
	            		if ($x != count($parent_arrays) -1) {
	            			$val .= '["'.$item.'"]["children"]';
	            		} else {
	            			$val .= '["'.$item.'"]["children"]';
	            		}
	            		$x++;
	            	}
	            	$val .= "";	            	
	            	$code_str = $val."[".$arrLP[$i]['id']."][\"load_data\"] = '".$arrLP[$i]['id']."' ; ";
	            	eval($code_str);	            
            	} else {            	            	
	            	$elements[$parent_id]['children'][$arrLP[$i]['id']]['data'] = $row;            	
	            	$elements[$parent_id]['children'][$arrLP[$i]['id']]['type'] = $arrLP[$i]['item_type'];
            	}
            }            
        }    
        
        $return = '<ul id="lp_item_list" class="well">';
        $return .= self::print_recursive($elements, $default_data, $default_content);
        $return .= '</ul>';
        if ($update_audio == 'true') {
            $return = $return_audio;
        }        
        return $return;
    }
    
    function print_recursive($elements, $default_data, $default_content) {
        $return = '';
        foreach($elements as $key => $item) {
            if (isset($item['load_data']) || empty($item['data'])) {
                $item['data'] = $default_data[$item['load_data']];        			
                $item['type'] = $default_content[$item['load_data']]['item_type'];
            }      	        		
            $sub_list = '';
            if (isset($item['type']) && $item['type'] == 'dokeos_chapter') {
                $sub_list = Display::tag('li', '', array('class'=>'sub_item empty')); // empty value
            }	        	 
            if (empty($item['children'])) {
                $sub_list = Display::tag('ul', $sub_list, array('id'=>'UL_'.$key, 'class'=>'record li_container'));
                $return  .= Display::tag('li', Display::div($item['data'], array('class'=>'item_data')).$sub_list, array('id'=>$key, 'class'=>'record li_container'));
            } else {
                //sections	  
                if (isset($item['children'])) {	        			
                    $data = self::print_recursive($item['children'], $default_data, $default_content);
                }	      
                $sub_list = Display::tag('ul', $sub_list.$data, array('id'=>'UL_'.$key, 'class'=>'record li_container'));
                $return .= Display::tag('li', Display::div($item['data'], array('class'=>'item_data')).$sub_list, array('id'=>$key, 'class'=>'record li_container'));
            }	        
        }
        return $return;
    }

    /**
     * This function builds the action menu
     * @return void
     */
    public function build_action_menu() {
        $gradebook = isset($_GET['gradebook']) ? Security :: remove_XSS($_GET['gradebook']) : null;
        echo '<div class="actions">';
        echo Display :: return_icon('build_learnpath_na.png', get_lang('Build'),'',ICON_SIZE_MEDIUM);
        
        echo '<a href="' . api_get_self().'?'.api_get_cidreq().'&amp;gradebook=' . $gradebook . '&amp;action=admin_view&amp;lp_id=' . $_SESSION['oLP']->lp_id . '" title="' . get_lang('BasicOverview') . '">' . Display :: return_icon('move_learnpath.png', get_lang('BasicOverview'),'',ICON_SIZE_MEDIUM).'</a>';
        echo '<a href="lp_controller.php?'.api_get_cidreq().'&amp;gradebook=' . $gradebook . '&action=view&lp_id=' . $_SESSION['oLP']->lp_id . '">' . Display :: return_icon('view_left_right.png', get_lang('Display'),'',ICON_SIZE_MEDIUM).'</a> ';
        Display :: display_icon('i.gif');
        echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;gradebook=' . $gradebook . '&amp;action=add_item&amp;type=step&amp;lp_id=' . $_SESSION['oLP']->lp_id . '" title="' . get_lang('NewStep') . '">' . Display :: return_icon('new_learnigpath_object.png', get_lang('NewStep'),'',ICON_SIZE_MEDIUM).'</a>';        
//		echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;gradebook=' . $gradebook . '&amp;action=add_item&amp;type=chapter&amp;lp_id=' . $_SESSION['oLP']->lp_id . '" title="' . get_lang('NewChapter') . '">' . Display :: return_icon('add_learnpath_section.png', get_lang('NewChapter'),'',ICON_SIZE_MEDIUM).'</a>';
        echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;action=admin_view&amp;lp_id=' . $_SESSION['oLP']->lp_id . '&amp;updateaudio=true">' . Display :: return_icon('upload_audio.png', get_lang('UpdateAllAudioFragments'),'',ICON_SIZE_MEDIUM).'</a>';
        echo '<a href="lp_controller.php?'.api_get_cidreq().'&amp;action=edit&amp;lp_id=' . $_SESSION['oLP']->lp_id . '">' . Display :: return_icon('settings.png', get_lang('CourseSettings'),'',ICON_SIZE_MEDIUM).'</a>';
        echo '</div>';
    }

    /**
     * This functions builds the LP tree based on data from the database.
     * @return string
     * @uses dtree.js :: necessary javascript for building this tree
     */
    public function build_tree() {
        $course_id = api_get_course_int_id();

        $return = "<script type=\"text/javascript\">\n";
        $return .= "\tm = new dTree('m');\n\n";
        $return .= "\tm.config.folderLinks		= true;\n";
        $return .= "\tm.config.useCookies		= true;\n";
        $return .= "\tm.config.useIcons			= true;\n";
        $return .= "\tm.config.useLines			= true;\n";
        $return .= "\tm.config.useSelection		= true;\n";
        $return .= "\tm.config.useStatustext	= false;\n\n";
        

        $menu = 0;
        $parent = '';
        $return .= "\tm.add(" . $menu . ", -1, '" . addslashes(Security::remove_XSS(($this->name))) . "');\n";
        $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);

        $sql = " SELECT id, title, description, item_type, path, parent_item_id, previous_item_id, next_item_id, max_score, min_score, mastery_score, display_order
                 FROM $tbl_lp_item
                 WHERE c_id = ".$course_id." AND lp_id = " . Database :: escape_string($this->lp_id);
        $result = Database::query($sql);
        $arrLP = array ();

        while ($row = Database :: fetch_array($result)) {
            $row['title'] = Security :: remove_XSS($row['title']);
            $row['description'] = Security :: remove_XSS($row['description']);

            $arrLP[] = array (
                'id' 				=> $row['id'],
                'item_type' 		=> $row['item_type'],
                'title' 			=> $row['title'],
                'path' 				=> $row['path'],
                'description' 		=> $row['description'],
                'parent_item_id' 	=> $row['parent_item_id'],
                'previous_item_id' 	=> $row['previous_item_id'],
                'next_item_id' 		=> $row['next_item_id'],
                'max_score' 		=> $row['max_score'],
                'min_score' 		=> $row['min_score'],
                'mastery_score' 	=> $row['mastery_score'],
                'display_order' 	=> $row['display_order']
            );
        }

        $this->tree_array($arrLP);
        $arrLP = $this->arrMenu;
        unset ($this->arrMenu);
        $title = '';
        for ($i = 0; $i < count($arrLP); $i++) {
            $title = addslashes($arrLP[$i]['title']);
            $menu_page = api_get_self() . '?cidReq=' . Security :: remove_XSS($_GET['cidReq']) . '&amp;action=view_item&amp;id=' . $arrLP[$i]['id'] . '&amp;lp_id=' . $_SESSION['oLP']->lp_id;
            $icon_name = str_replace(' ', '', $arrLP[$i]['item_type']);
            if (file_exists('../img/lp_' . $icon_name . '.png')) {
                $return .= "\tm.add(" . $arrLP[$i]['id'] . ", " . $arrLP[$i]['parent_item_id'] . ", '" . $title . "', '" . $menu_page . "', '', '', '../img/lp_" . $icon_name . ".png', '../img/lp_" . $icon_name . ".png');\n";
            } else
                if (file_exists('../img/lp_' . $icon_name . '.gif')) {
                    $return .= "\tm.add(" . $arrLP[$i]['id'] . ", " . $arrLP[$i]['parent_item_id'] . ", '" . $title . "', '" . $menu_page . "', '', '', '../img/lp_" . $icon_name . ".gif', '../img/lp_" . $icon_name . ".gif');\n";
                } else {
                    $return .= "\tm.add(" . $arrLP[$i]['id'] . ", " . $arrLP[$i]['parent_item_id'] . ", '" . $title . "', '" . $menu_page . "', '', '', '../img/folder_document.gif', '../img/folder_document.gif');\n";
                }
            if ($menu < $arrLP[$i]['id'])
                $menu = $arrLP[$i]['id'];
        }

        $return .= "\n\tdocument.write(m);\n";
        $return .= "\t if(!m.selectedNode) m.s(1);";
        $return .= "</script>\n";

        return $return;
    }

    public function generate_lp_folder($course, $dir) {
    	$filepath = '';
    	//Creating learning_path folder
    	$dir = '/learning_path';
    	$filepath = api_get_path(SYS_COURSE_PATH) . $course['path'] . '/document';
    	$folder = null;
    	if (!is_dir($filepath.'/'.$dir)) {
    		$folder = create_unexisting_directory($course, api_get_user_id(), api_get_session_id(), 0, 0, $filepath, $dir , get_lang('LearningPaths'));
    	} else {
    		$folder = true;
    	}
    	
    	$dir = '/learning_path/';
    	//Creating LP folder
    	if ($folder) {
    		//Limits title size
    		$title = api_substr(replace_dangerous_char($this->name), 0 , 80);
    		$dir   = $dir.$title;
    		$filepath = api_get_path(SYS_COURSE_PATH) . $course['path'] . '/document';
    		if (!is_dir($filepath.'/'.$dir)) {
    			$folder = create_unexisting_directory($course, api_get_user_id(), api_get_session_id(), 0, 0, $filepath, $dir , $this->name);
    		} else {
    			$folder = true;
    		}
    		$dir = $dir.'/';
    		if ($folder) {
    			$filepath = api_get_path(SYS_COURSE_PATH) . $course['path'] . '/document'.$dir;
    		}
    	}
    	$array =  array('dir' => $dir, 'filepath' => $filepath);
    	return $array;
    }
    
    /**
     * Create a new document //still needs some finetuning
     * @param array $_course
     * @return string
     */
    public function create_document($_course) {
        $course_id = api_get_course_int_id();
        global $charset;
        $dir = isset ($_GET['dir']) ? $_GET['dir'] : $_POST['dir']; // Please, do not modify this dirname formatting.
        if (strstr($dir, '..'))
            $dir = '/';
        if ($dir[0] == '.')
            $dir = substr($dir, 1);
        if ($dir[0] != '/')
            $dir = '/' . $dir;
        if ($dir[strlen($dir) - 1] != '/')
            $dir .= '/';
        
        $filepath = api_get_path(SYS_COURSE_PATH) . $_course['path'] . '/document' . $dir;
        
        if (empty($_POST['dir']) && empty($_GET['dir'])) {            
        	$result = $this->generate_lp_folder($_course, $dir);
        	$dir 		= $result['dir'];
        	$filepath 	= $result['filepath'];
        }
                
        if (!is_dir($filepath)) {
            $filepath = api_get_path(SYS_COURSE_PATH) . $_course['path'] . '/document/';
            $dir = '/';
        }        
      
        // stripslashes() before calling replace_dangerous_char() because $_POST['title']
        // is already escaped twice when it gets here.        
        $title = replace_dangerous_char(stripslashes($_POST['title']));
        $title = disable_dangerous_file($title);

        $filename = $title;
        $content = $_POST['content_lp'];

        $tmp_filename = $filename;

        $i = 0;
        while (file_exists($filepath . $tmp_filename . '.html'))
            $tmp_filename = $filename . '_' . ++ $i;
        
        $filename = $tmp_filename . '.html';
        $content = stripslashes($content);
        
        $content = str_replace(api_get_path(WEB_COURSE_PATH), api_get_path(REL_PATH).'courses/', $content);
        
        // Change the path of mp3 to absolute.
        
        // The first regexp deals with ../../../ urls.
        
        $content = preg_replace("|(flashvars=\"file=)(\.+/)+|", "$1" . api_get_path(REL_COURSE_PATH) . $_course['path'] . '/document/', $content);
        // The second regexp deals with audio/ urls.
        $content = preg_replace("|(flashvars=\"file=)([^/]+)/|", "$1" . api_get_path(REL_COURSE_PATH) . $_course['path'] . '/document/$2/', $content);
        // For flv player: To prevent edition problem with firefox, we have to use a strange tip (don't blame me please).
        $content = str_replace('</body>', '<style type="text/css">body{}</style></body>', $content);

        if (!file_exists($filepath . $filename)) {
            if ($fp = @ fopen($filepath . $filename, 'w')) {
                fputs($fp, $content);
                fclose($fp);

                $file_size = filesize($filepath . $filename);
                $save_file_path = $dir.$filename;

                $document_id = add_document($_course, $save_file_path, 'file', $file_size, $tmp_filename);

                if ($document_id) {
                    api_item_property_update($_course, TOOL_DOCUMENT, $document_id, 'DocumentAdded', api_get_user_id(), null, null, null, null, api_get_session_id());

                    $new_comment = (isset($_POST['comment'])) ? trim($_POST['comment']) : '';
                    $new_title = (isset($_POST['title'])) ? trim($_POST['title']) : '';

                    if ($new_comment || $new_title) {
                        $tbl_doc = Database :: get_course_table(TABLE_DOCUMENT);
                        $ct = '';
                        if ($new_comment)
                            $ct .= ", comment='" . Database::escape_string($new_comment). "'";
                        if ($new_title)
                            $ct .= ", title='" . Database :: escape_string(htmlspecialchars($new_title, ENT_QUOTES, $charset))."' ";

                        $sql_update = "UPDATE " . $tbl_doc ." SET " . substr($ct, 1)." WHERE c_id = ".$course_id." AND id = " . $document_id;
                        Database::query($sql_update);
                    }
                }
                return $document_id;
            }
        }
    }

    /**
     * Edit a document based on $_POST and $_GET parameters 'dir' and 'path'
     * @param 	array $_course array
     * @return 	void
     */
    public function edit_document($_course) {
        $course_id = api_get_course_int_id();
        global $_configuration;
        $dir = isset ($_GET['dir']) ? $_GET['dir'] : $_POST['dir']; // Please, do not modify this dirname formatting.

        if (strstr($dir, '..'))
            $dir = '/';

        if ($dir[0] == '.')
            $dir = substr($dir, 1);

        if ($dir[0] != '/')
            $dir = '/' . $dir;

        if ($dir[strlen($dir) - 1] != '/')
            $dir .= '/';

        $filepath = api_get_path(SYS_COURSE_PATH) . $_course['path'] . '/document' . $dir;

        if (!is_dir($filepath)) {
            $filepath = api_get_path(SYS_COURSE_PATH) . $_course['path'] . '/document/';
            $dir = '/';
        }

        $table_doc = Database :: get_course_table(TABLE_DOCUMENT);
        if (isset($_POST['path']) && !empty($_POST['path'])) {
        	$document_id = intval($_POST['path']);
            $sql = "SELECT path FROM " . $table_doc . " WHERE c_id = $course_id AND id = " . $document_id;
            $res = Database::query($sql);
            $row = Database :: fetch_array($res);
            $content = stripslashes($_POST['content_lp']);
            $file = $filepath . $row['path'];

            if ($fp = @ fopen($file, 'w')) {
                $content = text_filter($content);
                $content = str_replace(api_get_path(WEB_COURSE_PATH), $_configuration['url_append'] . '/courses/', $content);

                // Change the path of mp3 to absolute.
                // The first regexp deals with ../../../ urls.
                $content = preg_replace("|(flashvars=\"file=)(\.+/)+|", "$1" . api_get_path(REL_COURSE_PATH) . $_course['path'] . '/document/', $content);
                // The second regexp deals with audio/ urls.
                $content = preg_replace("|(flashvars=\"file=)([^/]+)/|", "$1" . api_get_path(REL_COURSE_PATH) . $_course['path'] . '/document/$2/', $content);

                fputs($fp, $content);
                fclose($fp);
                
                $sql_update = "UPDATE " . $table_doc ." SET title='".Database::escape_string($_POST['title'])."' WHERE c_id = ".$course_id." AND id = " . $document_id;
                Database::query($sql_update);
            }
        }
    }

    /**
     * Displays the selected item, with a panel for manipulating the item
     * @param int $item_id
     * @param string $msg
     * @return string
     */
    public function display_item($item_id, $iframe = true, $msg = '') {
        $course_id = api_get_course_int_id();        
        $return = '';
        if (is_numeric($item_id)) {
            $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);
            $tbl_doc     = Database :: get_course_table(TABLE_DOCUMENT);
            $sql = "SELECT lp.* FROM " . $tbl_lp_item . " as lp
                    WHERE c_id = ".$course_id." AND lp.id = " . Database :: escape_string($item_id);
            $result = Database::query($sql);
            while ($row = Database :: fetch_array($result,'ASSOC')) {
                $_SESSION['parent_item_id'] = ($row['item_type'] == 'dokeos_chapter' || $row['item_type'] == 'dokeos_module' || $row['item_type'] == 'dir') ? $item_id : 0;

                // Prevents wrong parent selection for document, see Bug#1251.
                if ($row['item_type'] != 'dokeos_chapter' || $row['item_type'] != 'dokeos_module') {
                    $_SESSION['parent_item_id'] = $row['parent_item_id'];
                }

                $return .= $this->display_manipulate($item_id, $row['item_type']);
                $return .= '<div style="padding:10px;">';
                
                if ($msg != '')
                    $return .= $msg;
                
                $return .= '<h3>'.$row['title'].'</h3>';
                //var_dump($row);
                switch ($row['item_type']) {
                    case TOOL_QUIZ:
                        if (!empty($row['path'])) {
                            require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.class.php';
                            $exercise = new Exercise();
                            $exercise->read($row['path']);                              
                            $return .= $exercise->description.'<br />';                            
                        }
                        break;
                    case TOOL_DOCUMENT:
                        $tbl_doc      = Database :: get_course_table(TABLE_DOCUMENT);
                        $sql_doc      = "SELECT path FROM " . $tbl_doc . " WHERE c_id = ".$course_id." AND id = " . Database :: escape_string($row['path']);
                        $result       = Database::query($sql_doc);
                        $path_file    = Database::result($result, 0, 0);
                        $path_parts   = pathinfo($path_file);
                        // TODO: Correct the following naive comparisons, also, htm extension is missing.
                        if (in_array($path_parts['extension'], array (
                                'html',
                                'txt',
                                'png',
                                'jpg',
                                'JPG',
                                'jpeg',
                                'JPEG',
                                'gif',
                                'swf'
                            ))) {
                            $return .= $this->display_document($row['path'], true, true);
                        }
                        break;
                }
                $return .= '</div>';
            }
        }
        return $return;
    }

    /**
     * Shows the needed forms for editing a specific item
     * @param int $item_id
     * @return string
     */
    public function display_edit_item($item_id) {
        global $_course; // It will disappear.
        $course_id = api_get_course_int_id();
        
        $return = '';
        if (is_numeric($item_id)) {
            $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);
            $sql = "SELECT * FROM $tbl_lp_item WHERE c_id = ".$course_id." AND id = " . Database :: escape_string($item_id);
            $res = Database::query($sql);
            $row = Database::fetch_array($res);
            
            switch ($row['item_type']) {
                case 'dokeos_chapter' :
                case 'dir' :
                case 'asset' :
                case 'sco' :
                    if (isset ($_GET['view']) && $_GET['view'] == 'build') {
                        $return .= $this->display_manipulate($item_id, $row['item_type']);
                        $return .= $this->display_item_form($row['item_type'], get_lang('EditCurrentChapter') . ' :', 'edit', $item_id, $row);
                    } else {
                        $return .= $this->display_item_small_form($row['item_type'], get_lang('EditCurrentChapter') . ' :', $row);
                    }
                    break;
                case TOOL_DOCUMENT :
                    $tbl_doc = Database :: get_course_table(TABLE_DOCUMENT);
                    $sql_step = " SELECT lp.*, doc.path as dir
                                    FROM " . $tbl_lp_item . " as lp
                                    LEFT JOIN " . $tbl_doc . " as doc ON doc.id = lp.path
                                    WHERE 	lp.c_id = $course_id AND 
                    						doc.c_id = $course_id AND 
                    						lp.id = " . Database :: escape_string($item_id);
                    $res_step = Database::query($sql_step);
                    $row_step = Database :: fetch_array($res_step);
                    $return .= $this->display_manipulate($item_id, $row['item_type']);
                    $return .= $this->display_document_form('edit', $item_id, $row_step);
                    break;
                case TOOL_LINK :
                    $link_id = (string) $row['path'];
                    if (ctype_digit($link_id)) {
                        $tbl_link = Database :: get_course_table(TABLE_LINK);
                        $sql_select = 'SELECT url FROM ' . $tbl_link . ' WHERE c_id = '.$course_id.' AND id = ' . Database :: escape_string($link_id);
                        $res_link = Database::query($sql_select);
                        $row_link = Database :: fetch_array($res_link);
                        if (is_array($row_link)) {
                            $row['url'] = $row_link['url'];
                        }
                    }
                    $return .= $this->display_manipulate($item_id, $row['item_type']);
                    $return .= $this->display_link_form('edit', $item_id, $row);
                    break;
                case 'dokeos_module' :
                    if (isset ($_GET['view']) && $_GET['view'] == 'build') {
                        $return .= $this->display_manipulate($item_id, $row['item_type']);
                        $return .= $this->display_item_form($row['item_type'], get_lang('EditCurrentModule') . ' :', 'edit', $item_id, $row);
                    } else {
                        $return .= $this->display_item_small_form($row['item_type'], get_lang('EditCurrentModule') . ' :', $row);
                    }
                    break;
                case TOOL_QUIZ :
                    $return .= $this->display_manipulate($item_id, $row['item_type']);
                    $return .= $this->display_quiz_form('edit', $item_id, $row);
                    break;
                case TOOL_HOTPOTATOES :
                    $return .= $this->display_manipulate($item_id, $row['item_type']);
                    $return .= $this->display_hotpotatoes_form('edit', $item_id, $row);
                    break;
                case TOOL_STUDENTPUBLICATION :
                    $return .= $this->display_manipulate($item_id, $row['item_type']);
                    $return .= $this->display_student_publication_form('edit', $item_id, $row);
                    break;
                case TOOL_FORUM :
                    $return .= $this->display_manipulate($item_id, $row['item_type']);
                    $return .= $this->display_forum_form('edit', $item_id, $row);
                    break;
                case TOOL_THREAD :
                    $return .= $this->display_manipulate($item_id, $row['item_type']);
                    $return .= $this->display_thread_form('edit', $item_id, $row);
                    break;
            }
        }

        return $return;
    }

    /**
     * Function that displays a list with al the resources that could be added to the learning path
     * @return string
     */
    public function display_resources() {
        global $_course; // TODO: Don't use globals.
        $course_code = api_get_course_id();
     
        //Get all the docs
        $documents = $this->get_documents();

        //Get all the exercises
        $exercises = $this->get_exercises();

        // Get all the links
        $links = $this->get_links();

        //Get al the student publications
        $works = $this->get_student_publications();

        //Get al the forums
        $forums = $this->get_forums(null, $course_code);

        $headers = array(   Display::return_icon('folder_document.png', get_lang('Documents'), array(), 64), 
                            Display::return_icon('quiz.png',  get_lang('Quiz'), array(), 64),
                            Display::return_icon('links.png', get_lang('Links'), array(), 64),
                            Display::return_icon('works.png', get_lang('Works'), array(), 64),
                            Display::return_icon('forum.png', get_lang('Forums'), array(), 64),
                            Display::return_icon('add_learnpath_section.png', get_lang('NewChapter'), array(), 64)
                        );
        
        $chapter = $_SESSION['oLP']->display_item_form('chapter', get_lang('EnterDataNewChapter'), 'add_item');        
        echo Display::tabs($headers, array($documents, $exercises, $links, $works, $forums, $chapter), 'resource_tab');
        return true;
    }

    /**
     * Returns the extension of a document
     * @param string filename
     * @return string Extension (part after the last dot)
     */
    public function get_extension($filename) {
        $explode = explode('.', $filename);
        return $explode[count($explode) - 1];
    }

    /**
     * Displays a document by id
     *
     * @param unknown_type $id
     * @return unknown
     */
    public function display_document($id, $show_title = false, $iframe = true, $edit_link = false) {
        global $_course; // It is temporary.
        $course_id = api_get_course_int_id();
        $return = '';
        $tbl_doc = Database :: get_course_table(TABLE_DOCUMENT);
        $sql_doc = "SELECT * FROM " . $tbl_doc . "
                    WHERE c_id = ".$course_id." AND id = " . $id;
        $res_doc = Database::query($sql_doc);
        $row_doc = Database :: fetch_array($res_doc);

        //if ($show_title)
        //$return .= '<p class="lp_title">' . $row_doc['title'] . ($edit_link ? ' [ <a href="' .api_get_self(). '?cidReq=' . $_GET['cidReq'] . '&amp;action=add_item&amp;type=' . TOOL_DOCUMENT . '&amp;file=' . $_GET['file'] . '&amp;edit=true&amp;lp_id=' . $_GET['lp_id'] . '">Edit this document</a> ]' : '') . '</p>';

        // TODO: Add a path filter.
        if ($iframe) {
            $return .= '<iframe id="learnpath_preview_frame" frameborder="0" height="400" width="100%" scrolling="auto" src="' . api_get_path(WEB_COURSE_PATH) . $_course['path'] . '/document' . str_replace('%2F', '/', urlencode($row_doc['path'])) . '?' . api_get_cidreq() . '"></iframe>';
        } else {
            $return .= file_get_contents(api_get_path(SYS_COURSE_PATH) . $_course['path'] . '/document' . $row_doc['path']);
        }

        return $return;
    }

    /**
     * Return HTML form to add/edit a quiz
     * @param	string	Action (add/edit)
     * @param	integer	Item ID if already exists
     * @param	mixed	Extra information (quiz ID if integer)
     * @return	string	HTML form
     */
    public function display_quiz_form($action = 'add', $id = 0, $extra_info = '') {
        $course_id = api_get_course_int_id();        
        $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);
        $tbl_quiz = Database :: get_course_table(TABLE_QUIZ_TEST);

        if ($id != 0 && is_array($extra_info)) {
            $item_title = $extra_info['title'];
            $item_description = $extra_info['description'];
        } elseif (is_numeric($extra_info)) {
            $sql_quiz = "SELECT title, description FROM " . $tbl_quiz . " WHERE c_id = ".$course_id." AND id = " . $extra_info;

            $result = Database::query($sql_quiz);
            $row = Database :: fetch_array($result);
            $item_title = $row['title'];
            $item_description = $row['description'];
        } else {
            $item_title = '';
            $item_description = '';
        }
        $item_title			= Security::remove_XSS($item_title);        
        $item_description 	= Security::remove_XSS($item_description);

        $legend = '<legend>';
        if ($id != 0 && is_array($extra_info))
            $parent = $extra_info['parent_item_id'];
        else
            $parent = 0;

        $sql = "SELECT * FROM " . $tbl_lp_item . " WHERE c_id = ".$course_id." AND lp_id = " . $this->lp_id;

        $result = Database::query($sql);
        $arrLP = array ();
        while ($row = Database :: fetch_array($result)) {
            $arrLP[] = array (
                'id' => $row['id'],
                'item_type' => $row['item_type'],
                'title' => $row['title'],
                'path' => $row['path'],
                'description' => $row['description'],
                'parent_item_id' => $row['parent_item_id'],
                'previous_item_id' => $row['previous_item_id'],
                'next_item_id' => $row['next_item_id'],
                'display_order' => $row['display_order'],
                'max_score' => $row['max_score'],
                'min_score' => $row['min_score'],
                'mastery_score' => $row['mastery_score'],
                'prerequisite' => $row['prerequisite'],
                'max_time_allowed' => $row['max_time_allowed']
            );
        }

        $this->tree_array($arrLP);
        $arrLP = $this->arrMenu;
        unset ($this->arrMenu);

        if ($action == 'add')
            $legend .= get_lang('CreateTheExercise') . '&nbsp;:';
        elseif ($action == 'move') $legend .= get_lang('MoveTheCurrentExercise') . '&nbsp;:';
        else
            $legend .= get_lang('EditCurrentExecice') . '&nbsp;:';
        if (isset ($_GET['edit']) && $_GET['edit'] == 'true') {
            $legend .= Display :: return_warning_message(get_lang('Warning') . ' ! ' . get_lang('WarningEditingDocument'));
        }
        $legend .= '</legend>';
        $return .= '<div class="sectioncomment">';

        $return .= '<form method="POST">';
        $return .= $legend;
        $return .= '<table class="lp_form">';

        if ($action != 'move') {
            $return .= '<tr>';
            $return .= '<td class="label"><label for="idTitle">' . get_lang('Title') . '</label></td>';
            $return .= '<td class="input"><input id="idTitle" name="title" size="44" type="text" value="' . $item_title . '" /></td>';
            $return .= '</tr>';
        }

        $return .= '<tr>';

        $return .= '<td class="label"><label for="idParent">' . get_lang('Parent') . '</label></td>';
        $return .= '<td class="input">';

        $return .= '<select id="idParent" style="width:100%;" name="parent" onChange="javascript: load_cbo(this.value);" size="1">';

        $return .= '<option class="top" value="0">' . $this->name . '</option>';

        $arrHide = array (
            $id
        );
        //$parent_item_id = $_SESSION['parent_item_id'];
        for ($i = 0; $i < count($arrLP); $i++) {
            if ($action != 'add') {
                if (($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide)) {
                    $return .= '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . $arrLP[$i]['title'] . '</option>';
                } else {
                    $arrHide[] = $arrLP[$i]['id'];
                }
            } else {
                if ($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir')
                    $return .= '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . $arrLP[$i]['title'] . '</option>';
            }
        }
        if (is_array($arrLP)) {
            reset($arrLP);
        }

        $return .= '</select>';
        $return .= '</td>';
        $return .= '</tr>';
        $return .= '<tr>';

        $return .= '<td class="label"><label for="previous">' . get_lang('Position') . '</label></td>';
        $return .= '<td class="input">';

        $return .= '<select class="learnpath_item_form" style="width:100%;" id="previous" name="previous" size="1">';

        $return .= '<option class="top" value="0">' . get_lang('FirstPosition') . '</option>';

        for ($i = 0; $i < count($arrLP); $i++) {
            if ($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id) {
                if ($extra_info['previous_item_id'] == $arrLP[$i]['id'])
                    $selected = 'selected="selected" ';
                elseif ($action == 'add') $selected = 'selected="selected" ';
                else
                    $selected = '';

                $return .= '<option ' . $selected . 'value="' . $arrLP[$i]['id'] . '">' . get_lang('After') . ' "' . $arrLP[$i]['title'] . '"</option>';
            }
        }

        $return .= '</select>';
        $return .= '</td>';
        $return .= '</tr>';
        if ($action != 'move') {
            $id_prerequisite = 0;
            if (is_array($arrLP)) {
                foreach ($arrLP as $key => $value) {
                    if ($value['id'] == $id) {
                        $id_prerequisite = $value['prerequisite'];
                        break;
                    }
                }
            }
            $arrHide = array ();
            for ($i = 0; $i < count($arrLP); $i++) {
                if ($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dokeos_chapter') {
                    if ($extra_info['previous_item_id'] == $arrLP[$i]['id'])
                        $s_selected_position = $arrLP[$i]['id'];
                    elseif ($action == 'add') $s_selected_position = 0;
                    $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
                }
            }
            /*// Commented the prerequisites, only visible in edit (exercise).
            $return .= '<tr>';
            $return .= '<td class="label"><label for="idPrerequisites">'.get_lang('Prerequisites').'</label></td>';
            $return .= '<td class="input"><select name="prerequisites" id="prerequisites" class="learnpath_item_form"><option value="0">'.get_lang('NoPrerequisites').'</option>';

                foreach($arrHide as $key => $value){
                    if($key==$s_selected_position && $action == 'add'){
                        $return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
                    }
                    elseif($key==$id_prerequisite && $action == 'edit'){
                        $return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
                    }
                    else{
                        $return .= '<option value="'.$key.'">'.$value['value'].'</option>';
                    }
                }

            $return .= "</select></td>";
            */
            $return .= '</tr>';
            /*$return .= '<tr>';
            $return .= '<td class="label"><label for="maxTimeAllowed">' . get_lang('MaxTimeAllowed') . '</label></td>';
            $return .= '<td class="input"><input name="maxTimeAllowed" style="width:98%;" id="maxTimeAllowed" value="' . $extra_info['max_time_allowed'] . '" /></td>';

            // Remove temporarily the test description.
            //$return .= '<td class="label"><label for="idDescription">'.get_lang('Description').' :</label></td>';
            //$return .= '<td class="input"><textarea id="idDescription" name="description" rows="4">' . $item_description . '</textarea></td>';

            $return .= '</tr>'; */
        }

        $return .= '<tr>';
        if ($action == 'add') {
            $return .= '<td>&nbsp;</td><td><button class="save" name="submit_button" type="submit">' . get_lang('AddExercise') . '</button></td>';
        } else {
            $return .= '<td>&nbsp;</td><td><button class="save" name="submit_button" type="submit">' . get_lang('EditCurrentExecice') . '</button></td>';
        }

        $return .= '</tr>';
        $return .= '</table>';

        if ($action == 'move') {
            $return .= '<input name="title" type="hidden" value="' . $item_title . '" />';
            $return .= '<input name="description" type="hidden" value="' . $item_description . '" />';
        }

        if (is_numeric($extra_info)) {
            $return .= '<input name="path" type="hidden" value="' . $extra_info . '" />';
        }
        elseif (is_array($extra_info)) {
            $return .= '<input name="path" type="hidden" value="' . $extra_info['path'] . '" />';
        }

        $return .= '<input name="type" type="hidden" value="' . TOOL_QUIZ . '" />';
        $return .= '<input name="post_time" type="hidden" value="' . time() . '" />';

        $return .= '</form>';
        $return .= '</div>';
        return $return;
    }

    /**
     * Addition of Hotpotatoes tests
     * @param	string	Action
     * @param	integer	Internal ID of the item
     * @param	mixed	Extra information - can be an array with title and description indexes
     * @return  string	HTML structure to display the hotpotatoes addition formular
     */
    public function display_hotpotatoes_form($action = 'add', $id = 0, $extra_info = '') {
        $course_id = api_get_course_int_id();
        global $charset;
        $uploadPath = DIR_HOTPOTATOES; //defined in main_api
        $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);

        if ($id != 0 && is_array($extra_info)) {
            $item_title = stripslashes($extra_info['title']);
            $item_description = stripslashes($extra_info['description']);
        } elseif (is_numeric($extra_info)) {
            $TBL_DOCUMENT = Database :: get_course_table(TABLE_DOCUMENT);
            
            $sql_hot = "SELECT * FROM " . $TBL_DOCUMENT . "
                        WHERE   c_id = ".$course_id." AND 
                                path LIKE '" . $uploadPath . "/%/%htm%' AND 
                                id = " . (int) $extra_info . " 
                        ORDER BY id ASC";

            $res_hot = Database::query($sql_hot);

            $row = Database::fetch_array($res_hot);

            $item_title = $row['title'];
            $item_description = $row['description'];

            if (!empty ($row['comment'])) {
                $item_title = $row['comment'];
            }
        } else {
            $item_title = '';
            $item_description = '';
        }
        
        if ($id != 0 && is_array($extra_info))
            $parent = $extra_info['parent_item_id'];
        else
            $parent = 0;

        $sql = "SELECT * FROM $tbl_lp_item WHERE c_id = ".$course_id." AND lp_id = " . $this->lp_id;
        $result = Database::query($sql);
        $arrLP = array ();
        while ($row = Database :: fetch_array($result)) {
            $arrLP[] = array (
                'id' => $row['id'],
                'item_type' => $row['item_type'],
                'title' => $row['title'],
                'path' => $row['path'],
                'description' => $row['description'],
                'parent_item_id' => $row['parent_item_id'],
                'previous_item_id' => $row['previous_item_id'],
                'next_item_id' => $row['next_item_id'],
                'display_order' => $row['display_order'],
                'max_score' => $row['max_score'],
                'min_score' => $row['min_score'],
                'mastery_score' => $row['mastery_score'],
                'prerequisite' => $row['prerequisite'],
                'max_time_allowed' => $row['max_time_allowed']
            );
        }   

        $legend = '<legend>';        
        if ($action == 'add')
            $legend .= get_lang('CreateTheExercise');
        elseif ($action == 'move') $legend .= get_lang('MoveTheCurrentExercise');
        else            
            $legend .= get_lang('EditCurrentExecice');
        if (isset ($_GET['edit']) && $_GET['edit'] == 'true') {
            $legend .= Display :: return_warning_message(get_lang('Warning') . ' ! ' . get_lang('WarningEditingDocument'));
        }
        $legend .= '</legend>';  

        $return .= '<form method="POST">';
        $return .= $legend;
        $return .= '<table cellpadding="0" cellspacing="0" class="lp_form">';
        $return .= '<tr>';
        $return .= '<td class="label"><label for="idParent">' . get_lang('Parent') . ' :</label></td>';
        $return .= '<td class="input">';
        $return .= '<select id="idParent" name="parent" onChange="javascript: load_cbo(this.value);" size="1">';
        $return .= '<option class="top" value="0">' . $this->name . '</option>';
        $arrHide = array (
            $id
        );

        if (count($arrLP) > 0) {
            for ($i = 0; $i < count($arrLP); $i++) {
                if ($action != 'add') {
                    if (($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide)) {
                        $return .= '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . $arrLP[$i]['title'] . '</option>';
                    } else {
                        $arrHide[] = $arrLP[$i]['id'];
                    }
                } else {
                    if ($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir')
                        $return .= '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . $arrLP[$i]['title'] . '</option>';
                }
            }

            reset($arrLP);
        }

        $return .= "\t\t\t\t" . '</select>';
        $return .= '</td>';
        $return .= '</tr>';
        $return .= '<tr>';
        $return .= '<td class="label"><label for="previous">' . get_lang('Position') . ' :</label></td>';
        $return .= '<td class="input">';
        $return .= "\t\t\t\t" . '<select id="previous" name="previous" size="1">';
        $return .= '<option class="top" value="0">' . get_lang('FirstPosition') . '</option>';

        for ($i = 0; $i < count($arrLP); $i++) {
            if ($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id) {
                if ($extra_info['previous_item_id'] == $arrLP[$i]['id'])
                    $selected = 'selected="selected" ';
                elseif ($action == 'add') $selected = 'selected="selected" ';
                else
                    $selected = '';

                $return .= '<option ' . $selected . 'value="' . $arrLP[$i]['id'] . '">' . get_lang('After') . ' "' . $arrLP[$i]['title'] . '"</option>';
            }
        }

        $return .= "\t\t\t\t" . '</select>';
        $return .= '</td>';
        $return .= '</tr>';
        
        if ($action != 'move') {
            $return .= '<tr>';
            $return .= '<td class="label"><label for="idTitle">' . get_lang('Title') . ' :</label></td>';
            $return .= '<td class="input"><input id="idTitle" name="title" type="text" value="' . $item_title . '" /></td>';
            $return .= '</tr>';
            $id_prerequisite = 0;
            if (is_array($arrLP) && count($arrLP) > 0) {
                foreach ($arrLP as $key => $value) {
                    if ($value['id'] == $id) {
                        $id_prerequisite = $value['prerequisite'];
                        break;
                    }
                }

                $arrHide = array ();
                for ($i = 0; $i < count($arrLP); $i++) {
                    if ($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dokeos_chapter') {
                        if ($extra_info['previous_item_id'] == $arrLP[$i]['id'])
                            $s_selected_position = $arrLP[$i]['id'];
                        elseif ($action == 'add') $s_selected_position = 0;
                        $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];

                    }
                }
            }

            //$return .= '<tr>';

            //$return .= '<td class="label"><label for="idPrerequisites">'.get_lang('Prerequisites').' :</label></td>';
            //$return .= '<td class="input"><select name="prerequisites" id="prerequisites"><option value="0">'.get_lang('NoPrerequisites').'</option>';
/*
            foreach ($arrHide as $key => $value) {
                if ($key == $s_selected_position && $action == 'add') {
                    $return .= '<option value="' . $key . '" selected="selected">' . $value['value'] . '</option>';
                }
                elseif ($key == $id_prerequisite && $action == 'edit') {
                    $return .= '<option value="' . $key . '" selected="selected">' . $value['value'] . '</option>';
                } else {
                    $return .= '<option value="' . $key . '">' . $value['value'] . '</option>';
                }
            }
*/
            //$return .= "</select></td>";
            //$return .= '</tr>';
            //$return .= '<tr>';
            //$return .= '</tr>';
        }

        $return .= '<tr>';
        $return .= '<td>&nbsp; </td><td><button class="save" name="submit_button" action="edit" type="submit">' . get_lang('SaveHotpotatoes') . '</button></td>';
        $return .= '</tr>';
        $return .= '</table>';

        if ($action == 'move') {
            $return .= '<input name="title" type="hidden" value="' . $item_title . '" />';
            $return .= '<input name="description" type="hidden" value="' . $item_description . '" />';
        }

        if (is_numeric($extra_info)) {
            $return .= '<input name="path" type="hidden" value="' . $extra_info . '" />';
        }
        elseif (is_array($extra_info)) {
            $return .= '<input name="path" type="hidden" value="' . $extra_info['path'] . '" />';
        }

        $return .= '<input name="type" type="hidden" value="' . TOOL_HOTPOTATOES . '" />';
        $return .= '<input name="post_time" type="hidden" value="' . time() . '" />';

        $return .= '</form>';
        
        return $return;
    }

    /**
     * Return the form to display the forum edit/add option
     * @param	string	Action (add/edit)
     * @param	integer	ID of the lp_item if already exists
     * @param	mixed	Forum ID or title
     * @return	string	HTML form
     */
    public function display_forum_form($action = 'add', $id = 0, $extra_info = '') {
        $course_id = api_get_course_int_id();
        global $charset;
        $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);
        $tbl_forum = Database :: get_course_table(TABLE_FORUM);

        if ($id != 0 && is_array($extra_info)) {
            $item_title = stripslashes($extra_info['title']);
        }
        elseif (is_numeric($extra_info)) {
            $sql_forum = "SELECT forum_title as title, forum_comment as comment
                            FROM " . $tbl_forum . "
                            WHERE c_id = ".$course_id." AND forum_id = " . $extra_info;

            $result = Database::query($sql_forum);
            $row = Database :: fetch_array($result);

            $item_title = $row['title'];
            $item_description = $row['comment'];
        } else {
            $item_title = '';
            $item_description = '';
        }

        $legend = '<legend>';

        if ($id != 0 && is_array($extra_info))
            $parent = $extra_info['parent_item_id'];
        else
            $parent = 0;

        $sql = "SELECT * FROM " . $tbl_lp_item . "
                WHERE   c_id = ".$course_id." AND 
                        lp_id = " . $this->lp_id;

        $result = Database::query($sql);

        $arrLP = array ();

        while ($row = Database :: fetch_array($result)) {
            $arrLP[] = array (
                'id' => $row['id'],
                'item_type' => $row['item_type'],
                'title' => $row['title'],
                'path' => $row['path'],
                'description' => $row['description'],
                'parent_item_id' => $row['parent_item_id'],
                'previous_item_id' => $row['previous_item_id'],
                'next_item_id' => $row['next_item_id'],
                'display_order' => $row['display_order'],
                'max_score' => $row['max_score'],
                'min_score' => $row['min_score'],
                'mastery_score' => $row['mastery_score'],
                'prerequisite' => $row['prerequisite']
            );
        }

        $this->tree_array($arrLP);
        $arrLP = $this->arrMenu;
        unset ($this->arrMenu);

        if ($action == 'add')
            $legend .= get_lang('CreateTheForum') . '&nbsp;:';
        elseif ($action == 'move') $legend .= get_lang('MoveTheCurrentForum') . '&nbsp;:';
        else
            $legend .= get_lang('EditCurrentForum') . '&nbsp;:';

        $legend .= '</legend>';
        
        $return .= '<div class="sectioncomment">';
        $return .= '<form method="POST">';
        $return .= $legend;
        $return .= '<table class="lp_form">';

        if ($action != 'move') {
            $return .= '<tr>';
            $return .= '<td class="label"><label for="idTitle">' . get_lang('Title') . '</label></td>';
            $return .= '<td class="input"><input id="idTitle" size="44" name="title" type="text" value="' . $item_title . '" class="learnpath_item_form" /></td>';
            $return .= '</tr>';
        }

        $return .= '<tr>';
        $return .= '<td class="label"><label for="idParent">' . get_lang('Parent') . '</label></td>';
        $return .= '<td class="input">';
        $return .= '<select id="idParent" style="width:100%;" name="parent" onChange="javascript: load_cbo(this.value);" class="learnpath_item_form" size="1">';
        $return .= '<option class="top" value="0">' . $this->name . '</option>';
        $arrHide = array (
            $id
        );

        //$parent_item_id = $_SESSION['parent_item_id'];
        for ($i = 0; $i < count($arrLP); $i++) {
            if ($action != 'add') {
                if (($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide)) {
                    $return .= '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . $arrLP[$i]['title'] . '</option>';
                } else {
                    $arrHide[] = $arrLP[$i]['id'];
                }
            } else {
                if ($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir')
                    $return .= '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . $arrLP[$i]['title'] . '</option>';
            }
        }
        if (is_array($arrLP)) {
            reset($arrLP);
        }

        $return .= "\t\t\t\t" . '</select>';
        $return .= '</td>';
        $return .= '</tr>';
        $return .= '<tr>';
        $return .= '<td class="label"><label for="previous">' . get_lang('Position') . '</label></td>';
        $return .= '<td class="input">';
        $return .= "\t\t\t\t" . '<select id="previous" name="previous" style="width:100%;" size="1" class="learnpath_item_form">';
        $return .= '<option class="top" value="0">' . get_lang('FirstPosition') . '</option>';

        for ($i = 0; $i < count($arrLP); $i++) {
            if ($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id) {
                if ($extra_info['previous_item_id'] == $arrLP[$i]['id'])
                    $selected = 'selected="selected" ';
                elseif ($action == 'add') $selected = 'selected="selected" ';
                else
                    $selected = '';

                $return .= '<option ' . $selected . 'value="' . $arrLP[$i]['id'] . '">' . get_lang('After') . ' "' . $arrLP[$i]['title'] . '"</option>';
            }
        }

        $return .= "\t\t\t\t" . '</select>';
        $return .= '</td>';
        $return .= '</tr>';
        if ($action != 'move') {
            $return .= '<tr>';
            $return .= '</tr>';
            $id_prerequisite = 0;
            if (is_array($arrLP)) {
                foreach ($arrLP as $key => $value) {
                    if ($value['id'] == $id) {
                        $id_prerequisite = $value['prerequisite'];
                        break;
                    }
                }
            }

            $arrHide = array ();
            for ($i = 0; $i < count($arrLP); $i++) {
                if ($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dokeos_chapter') {
                    if ($extra_info['previous_item_id'] == $arrLP[$i]['id'])
                        $s_selected_position = $arrLP[$i]['id'];
                    elseif ($action == 'add') $s_selected_position = 0;
                    $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
                }
            }
            $return .= '</tr>';
        }
        $return .= '<tr>';

        if ($action == 'add') {
            $return .= '<td>&nbsp;</td><td><button class="save" name="submit_button" type="submit"> ' . get_lang('AddForumToCourse') . ' </button></td>';
        } else {
            $return .= '<td>&nbsp;</td><td><button class="save" name="submit_button" type="submit"> ' . get_lang('EditCurrentForum') . ' </button></td>';
        }
        $return .= '</tr>';
        $return .= '</table>';

        if ($action == 'move') {
            $return .= '<input name="title" type="hidden" value="' . $item_title . '" />';
            $return .= '<input name="description" type="hidden" value="' . $item_description . '" />';
        }

        if (is_numeric($extra_info)) {
            $return .= '<input name="path" type="hidden" value="' . $extra_info . '" />';
        }
        elseif (is_array($extra_info)) {
            $return .= '<input name="path" type="hidden" value="' . $extra_info['path'] . '" />';
        }
        $return .= '<input name="type" type="hidden" value="' . TOOL_FORUM . '" />';
        $return .= '<input name="post_time" type="hidden" value="' . time() . '" />';
        $return .= '</form>';
        $return .= '</div>';
        return $return;
    }

    /**
     * Return HTML form to add/edit forum threads
     * @param	string	Action (add/edit)
     * @param	integer	Item ID if already exists in learning path
     * @param	mixed	Extra information (thread ID if integer)
     * @return 	string	HTML form
     */
    public function display_thread_form($action = 'add', $id = 0, $extra_info = '') {
        $course_id = api_get_course_int_id();
        if (empty($course_id)) {
            return null;
        }                
        $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);
        $tbl_forum = Database :: get_course_table(TABLE_FORUM_THREAD);

        if ($id != 0 && is_array($extra_info)) {
            $item_title = stripslashes($extra_info['title']);
        } elseif (is_numeric($extra_info)) {
            $sql_forum = "SELECT thread_title as title FROM $tbl_forum
                            WHERE c_id = $course_id AND thread_id = " . $extra_info;

            $result = Database::query($sql_forum);
            $row = Database :: fetch_array($result);

            $item_title = $row['title'];
            $item_description = '';
        } else {
            $item_title = '';
            $item_description = '';
        }

        $return = null;

        if ($id != 0 && is_array($extra_info))
            $parent = $extra_info['parent_item_id'];
        else
            $parent = 0;

        $sql = "SELECT * FROM " . $tbl_lp_item . "
                WHERE c_id = ".$course_id." AND lp_id = " . $this->lp_id;

        $result = Database::query($sql);

        $arrLP = array ();

        while ($row = Database :: fetch_array($result)) {
            $arrLP[] = array (
                'id' => $row['id'],
                'item_type' => $row['item_type'],
                'title' => $row['title'],
                'path' => $row['path'],
                'description' => $row['description'],
                'parent_item_id' => $row['parent_item_id'],
                'previous_item_id' => $row['previous_item_id'],
                'next_item_id' => $row['next_item_id'],
                'display_order' => $row['display_order'],
                'max_score' => $row['max_score'],
                'min_score' => $row['min_score'],
                'mastery_score' => $row['mastery_score'],
                'prerequisite' => $row['prerequisite']
            );
        }

        $this->tree_array($arrLP);

        $arrLP = $this->arrMenu;

        unset ($this->arrMenu);

        $return .= '<form method="POST">';
        if ($action == 'add')
            $return .= '<legend>' . get_lang('CreateTheForum') . '</legend>';
        elseif ($action == 'move') $return .= '<p class="lp_title">' . get_lang('MoveTheCurrentForum') . '&nbsp;:</p>';
        else
            $return .= '<legend>' . get_lang('EditCurrentForum') . '</legend>';
        
        
        $return .= '<table cellpadding="0" cellspacing="0" class="lp_form">';
        $return .= '<tr>';
        $return .= '<td class="label"><label for="idParent">' . get_lang('Parent') . '</label></td>';
        $return .= '<td class="input">';
        $return .= '<select id="idParent" name="parent" onChange="javascript: load_cbo(this.value);" size="1">';
        $return .= '<option class="top" value="0">' . $this->name . '</option>';
        $arrHide = array (
            $id
        );

        for ($i = 0; $i < count($arrLP); $i++) {
            if ($action != 'add') {
                if (($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide)) {
                    $return .= '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . $arrLP[$i]['title'] . '</option>';
                } else {
                    $arrHide[] = $arrLP[$i]['id'];
                }
            } else {
                if ($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir')
                    $return .= '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . $arrLP[$i]['title'] . '</option>';
            }
        }
        if ($arrLP != null) {
            reset($arrLP);
        }

        $return .= '</select>';
        $return .= '</td>';
        $return .= '</tr>';
        $return .= '<tr>';
        $return .= '<td class="label"><label for="previous">' . get_lang('Position') . '</label></td>';
        $return .= '<td class="input">';
        $return .= "\t\t\t\t" . '<select id="previous" name="previous" size="1">';
        $return .= '<option class="top" value="0">' . get_lang('FirstPosition') . '</option>';
        for ($i = 0; $i < count($arrLP); $i++) {
            if ($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id) {
                if ($extra_info['previous_item_id'] == $arrLP[$i]['id'])
                    $selected = 'selected="selected" ';
                elseif ($action == 'add') $selected = 'selected="selected" ';
                else
                    $selected = '';

                $return .= '<option ' . $selected . 'value="' . $arrLP[$i]['id'] . '">' . get_lang('After') . ' "' . $arrLP[$i]['title'] . '"</option>';
            }
        }
        $return .= "\t\t\t\t" . '</select>';
        $return .= '</td>';
        $return .= '</tr>';
        if ($action != 'move') {
            $return .= '<tr>';
            $return .= '<td class="label"><label for="idTitle">' . get_lang('Title') . '</label></td>';
            $return .= '<td class="input"><input id="idTitle" name="title" type="text" value="' . $item_title . '" /></td>';
            $return .= '</tr>';
            $return .= '<tr>';
            $return .= '</tr>';

            $id_prerequisite = 0;
            if ($arrLP != null) {
                foreach ($arrLP as $key => $value) {
                    if ($value['id'] == $id) {
                        $id_prerequisite = $value['prerequisite'];
                        break;
                    }
                }
            }

            $arrHide = array();
            for ($i = 0; $i < count($arrLP); $i++) {
                if ($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dokeos_chapter') {
                    if ($extra_info['previous_item_id'] == $arrLP[$i]['id'])
                        $s_selected_position = $arrLP[$i]['id'];
                    elseif ($action == 'add') $s_selected_position = 0;
                    $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];

                }
            }

            $return .= '<tr>';
            $return .= '<td class="label"><label for="idPrerequisites">' . get_lang('Prerequisites') . '</label></td>';
            $return .= '<td class="input"><select name="prerequisites" id="prerequisites"><option value="0">' . get_lang('NoPrerequisites') . '</option>';

            foreach ($arrHide as $key => $value) {
                if ($key == $s_selected_position && $action == 'add') {
                    $return .= '<option value="' . $key . '" selected="selected">' . $value['value'] . '</option>';
                }
                elseif ($key == $id_prerequisite && $action == 'edit') {
                    $return .= '<option value="' . $key . '" selected="selected">' . $value['value'] . '</option>';
                } else {
                    $return .= '<option value="' . $key . '">' . $value['value'] . '</option>';
                }
            }
            $return .= "</select></td>";
            $return .= '</tr>';

        }
        $return .= '<tr>';
        $return .= '<td></td><td>
                    <button class="save" name="submit_button" type="submit" value="'.get_lang('Ok').'" />'.get_lang('Ok').'</button></td>';
        $return .= '</tr>';
        $return .= '</table>';

        if ($action == 'move') {
            $return .= '<input name="title" type="hidden" value="' . $item_title . '" />';
            $return .= '<input name="description" type="hidden" value="' . $item_description . '" />';
        }

        if (is_numeric($extra_info)) {
            $return .= '<input name="path" type="hidden" value="' . $extra_info . '" />';
        }
        elseif (is_array($extra_info)) {
            $return .= '<input name="path" type="hidden" value="' . $extra_info['path'] . '" />';
        }

        $return .= '<input name="type" type="hidden" value="' . TOOL_THREAD . '" />';
        $return .= '<input name="post_time" type="hidden" value="' . time() . '" />';
        $return .= '</form>';
        $return .= '</div>';
        return $return;
    }

    /**
     * Return the HTML form to display an item (generally a section/module item)
     * @param	string	Item type (module/dokeos_module)
     * @param	string	Title (optional, only when creating)
     * @param	string	Action ('add'/'edit')
     * @param	integer	lp_item ID
     * @param	mixed	Extra info
     * @return	string 	HTML form
     */
    public function display_item_form($item_type, $title = '', $action = 'add_item', $id = 0, $extra_info = 'new') {
        $course_id = api_get_course_int_id();
        global $_course;
        global $charset;

        $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);

        if ($id != 0 && is_array($extra_info)) {
            $item_title 		= $extra_info['title'];
            $item_description 	= $extra_info['description'];
            $item_path = api_get_path(WEB_COURSE_PATH) . $_course['path'] . '/scorm/' . $this->path . '/' . stripslashes($extra_info['path']);
            $item_path_fck = '/scorm/' . $this->path . '/' . stripslashes($extra_info['path']);
        } else {
            $item_title = '';
            $item_description = '';
            $item_path_fck = '';
        }

        $legend = '<legend>';

        if ($id != 0 && is_array($extra_info))
            $parent = $extra_info['parent_item_id'];
        else
            $parent = 0;
        
        $id  = intval($id);
        $sql = "SELECT * FROM " . $tbl_lp_item . "
                WHERE c_id = ".$course_id." AND lp_id = " . $this->lp_id . " AND id != $id";

        if ($item_type == 'module')
            $sql .= " AND parent_item_id = 0";

        $result = Database::query($sql);
        $arrLP = array ();

        while ($row = Database :: fetch_array($result)) {
            $arrLP[] = array (
                'id'                => $row['id'],
                'item_type'         => $row['item_type'],
                'title'             => $row['title'],
                'path'              => $row['path'],
                'description'       => $row['description'],
                'parent_item_id'    => $row['parent_item_id'],
                'previous_item_id'  => $row['previous_item_id'],
                'next_item_id'      => $row['next_item_id'],
                'max_score'         => $row['max_score'],
                'min_score'         => $row['min_score'],
                'mastery_score'     => $row['mastery_score'],
                'prerequisite'      => $row['prerequisite'],
                'display_order'     => $row['display_order']
            );
        }

        $this->tree_array($arrLP);
        $arrLP = $this->arrMenu;
        unset ($this->arrMenu);

        $legend .= $title;
        
        $legend .= '</legend>';        
        
        $gradebook = isset($_GET['gradebook']) ? Security :: remove_XSS($_GET['gradebook']) : null;
        $url = api_get_self() . '?' .api_get_cidreq().'&gradeboook='.$gradebook.'&action='.$action.'&type='.$item_type.'&lp_id='.$this->lp_id;        
                
        $form = new FormValidator('form', 'POST',  $url);

        $defaults['title'] = api_html_entity_decode($item_title, ENT_QUOTES, $charset);
        $defaults['description'] = $item_description;

        $form->addElement('html', $legend);

        //$arrHide = array($id);
        $arrHide[0]['value'] = Security :: remove_XSS($this->name);
        $arrHide[0]['padding'] = 3;
        $charset = api_get_system_encoding();
        
        if ($item_type != 'module' && $item_type != 'dokeos_module') {
            for ($i = 0; $i < count($arrLP); $i++) {
                if ($action != 'add') {
                    if (($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide)) {
                        $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
                        $arrHide[$arrLP[$i]['id']]['padding'] = 3 + $arrLP[$i]['depth'] * 10;
                        if ($parent == $arrLP[$i]['id']) {
                            $s_selected_parent = $arrHide[$arrLP[$i]['id']];
                        }
                    }
                } else {
                    if ($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') {
                        $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
                        $arrHide[$arrLP[$i]['id']]['padding'] = 3 + $arrLP[$i]['depth'] * 10;
                        if ($parent == $arrLP[$i]['id']) {
                            $s_selected_parent = $arrHide[$arrLP[$i]['id']];
                        }
                    }
                }
            }

            if ($action != 'move') {
                $form->addElement('text', 'title', get_lang('Title'), 'id="idTitle" class="learnpath_chapter_form" size="40%"');
                $form->applyFilter('title', 'html_filter');
                $form->addRule('title', get_lang('ThisFieldIsRequired'), 'required');
                //$form->addElement('textarea', 'description', get_lang('Description').' :', 'id="idDescription"');
            } else {
                $form->addElement('hidden', 'title');
            }

            $parent_select = & $form->addElement('select', 'parent', get_lang('Parent'), '', 'class="learnpath_chapter_form" style="width:37%;" id="idParent" onchange="javascript: load_cbo(this.value);"');

            foreach ($arrHide as $key => $value) {
                $parent_select->addOption($value['value'], $key, 'style="padding-left:' . $value['padding'] . 'px;"');
            }
            if (!empty($s_selected_parent)) {
            	$parent_select->setSelected($s_selected_parent);
            }
        }
        if (is_array($arrLP)) {
            reset($arrLP);
        }

        $arrHide = array();

        // POSITION
        for ($i = 0; $i < count($arrLP); $i++) {
            if ($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id) {
                if ($extra_info['previous_item_id'] == $arrLP[$i]['id'])
                    $s_selected_position = $arrLP[$i]['id'];
                elseif ($action == 'add') $s_selected_position = $arrLP[$i]['id'];

                $arrHide[$arrLP[$i]['id']]['value'] = get_lang('After') . ' "' . $arrLP[$i]['title'] . '"';
            }
        }

        $position = & $form->addElement('select', 'previous', get_lang('Position'), '', 'id="previous" class="learnpath_chapter_form" style="width:37%;"');
        
        $padding = isset($value['padding']) ? $value['padding'] : 0;

        $position->addOption(get_lang('FirstPosition'), 0, 'style="padding-left:' . $padding . 'px;"');

        foreach ($arrHide as $key => $value) {
            $position->addOption($value['value'] . '"', $key, 'style="padding-left:' . $padding . 'px;"');
        }

        if (!empty ($s_selected_position)) {
            $position->setSelected($s_selected_position);
        }

        if (is_array($arrLP)) {
            reset($arrLP);
        }

        $form->addElement('style_submit_button', 'submit_button', get_lang('SaveSection'), 'class="save"');

        if ($item_type == 'module' || $item_type == 'dokeos_module') {
            $form->addElement('hidden', 'parent', '0');
        }
        //fix in order to use the tab
        if ($item_type == 'chapter') {
            $form->addElement('hidden', 'type', 'chapter');   
        }

        $extension = null;
        if (!empty($item_path)) {
        	$extension = pathinfo($item_path, PATHINFO_EXTENSION);
        }
        if (($item_type == 'asset' || $item_type == 'sco') && ($extension == 'html' || $extension == 'htm')) {
            if ($item_type == 'sco') {
                $form->addElement('html', '<script type="text/javascript">alert("' . get_lang('WarningWhenEditingScorm') . '")</script>');
            }
            $renderer = $form->defaultRenderer();
            $renderer->setElementTemplate('<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{label}<br />{element}', 'content_lp');        
                
            $relative_prefix = '';            
            $editor_config = array( 'ToolbarSet' 			=> 'LearningPathDocuments',
                                    'Width' 				=> '100%', 
                                    'Height' 				=> '500', 
                                    'FullPage' 				=> true,
                                    'CreateDocumentDir' 	=> $relative_prefix,
           							'CreateDocumentWebDir' 	=> api_get_path(WEB_COURSE_PATH) . api_get_course_path().'/scorm/',
            						'BaseHref' 				=> api_get_path(WEB_COURSE_PATH) . api_get_course_path().$item_path_fck
                                    );            
            $form->addElement('html_editor', 'content_lp', '', null, $editor_config);            
            $defaults['content_lp'] = file_get_contents($item_path);
        }

        $form->addElement('hidden', 'type', 'dokeos_' . $item_type);
        $form->addElement('hidden', 'post_time', time());
        $form->setDefaults($defaults);
        return $form->return_form();
    }

    /**
     * Returns the form to update or create a document
     * @param	string	Action (add/edit)
     * @param	integer	ID of the lp_item (if already exists)
     * @param	mixed	Integer if document ID, string if info ('new')
     * @return	string	HTML form
     */
    public function display_document_form($action = 'add', $id = 0, $extra_info = 'new') {
        $course_id = api_get_course_int_id();
        global $charset;
        $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);
        $tbl_doc 	 = Database :: get_course_table(TABLE_DOCUMENT);

        $path_parts = pathinfo($extra_info['dir']);
        $no_display_edit_textarea = false;

        //If action==edit document
        //We don't display the document form if it's not an editable document (html or txt file)
        if ($action == "edit") {
            if (is_array($extra_info)) {
                if ($path_parts['extension'] != "txt" && $path_parts['extension'] != "html") {
                    $no_display_edit_textarea = true;
                }
            }
        }
        $no_display_add = false;

        // If action==add an existing document
        // We don't display the document form if it's not an editable document (html or txt file).
        if ($action == "add") {
            if (is_numeric($extra_info)) {
                $sql_doc = "SELECT path FROM " . $tbl_doc . " WHERE c_id = ".$course_id." AND id = " . Database :: escape_string($extra_info);
                $result = Database::query($sql_doc);
                $path_file = Database :: result($result, 0, 0);
                $path_parts = pathinfo($path_file);
                if ($path_parts['extension'] != "txt" && $path_parts['extension'] != "html") {
                    $no_display_add = true;
                }
            }
        }
        if ($id != 0 && is_array($extra_info)) {
            $item_title = stripslashes($extra_info['title']);
            $item_description = stripslashes($extra_info['description']);
            $item_terms = stripslashes($extra_info['terms']);
            if (empty ($item_title)) {
                $path_parts = pathinfo($extra_info['path']);
                $item_title = stripslashes($path_parts['filename']);
            }
        } elseif (is_numeric($extra_info)) {
            $sql_doc = "SELECT path, title FROM " . $tbl_doc . "
                        WHERE c_id = ".$course_id." AND id = " . Database :: escape_string($extra_info);

            $result = Database::query($sql_doc);
            $row 	= Database::fetch_array($result);

            $explode = explode('.', $row['title']);

            if (count($explode) > 1) {
                for ($i = 0; $i < count($explode) - 1; $i++)
                    $item_title .= $explode[$i];
            } else {
                $item_title = $row['title'];
            }

            $item_title = str_replace('_', ' ', $item_title);
            if (empty ($item_title)) {
                $path_parts = pathinfo($row['path']);
                $item_title = stripslashes($path_parts['filename']);
            }
        } else {
            $item_title = '';
            $item_description = '';
        }
        $return = '<legend>';

        if ($id != 0 && is_array($extra_info))
            $parent = $extra_info['parent_item_id'];
        else
            $parent = 0;
        
        $sql = "SELECT * FROM " . $tbl_lp_item . "
                WHERE c_id = ".$course_id." AND lp_id = " . $this->lp_id;
        $result = Database::query($sql);
        $arrLP = array ();
        while ($row = Database :: fetch_array($result)) {
            $arrLP[] = array (
                'id' 				=> $row['id'],
                'item_type' 		=> $row['item_type'],
                'title' 			=> $row['title'],
                'path' 				=> $row['path'],
                'description' 		=> $row['description'],
                'parent_item_id' 	=> $row['parent_item_id'],
                'previous_item_id'	=> $row['previous_item_id'],
                'next_item_id' 		=> $row['next_item_id'],
                'display_order' 	=> $row['display_order'],
                'max_score' 		=> $row['max_score'],
                'min_score' 		=> $row['min_score'],
                'mastery_score' 	=> $row['mastery_score'],
                'prerequisite' 		=> $row['prerequisite']
            );
        }

        $this->tree_array($arrLP);
        $arrLP = $this->arrMenu;
        unset ($this->arrMenu);

        if ($action == 'add') {
            $return .= get_lang('CreateTheDocument');
        } elseif ($action == 'move') {
            $return .= get_lang('MoveTheCurrentDocument');
        } else {
            $return .= get_lang('EditTheCurrentDocument');
        }

        $return .= '</legend>';

        if (isset ($_GET['edit']) && $_GET['edit'] == 'true') {
            $return .= Display :: return_warning_message('<strong>' . get_lang('Warning') . ' !</strong><br />' . get_lang('WarningEditingDocument'), false);
        }        
        $form = new FormValidator('form', 'POST', api_get_self() . '?' .$_SERVER['QUERY_STRING'], '', array('enctype'=> "multipart/form-data"));
        $defaults['title'] = Security :: remove_XSS($item_title);
        if (empty($item_title)) {
            $defaults['title'] = Security::remove_XSS($item_title);
        }
        $defaults['description'] = $item_description;
        $form->addElement('html', $return);
        if ($action != 'move') {
            $form->addElement('text', 'title', get_lang('Title'), array('id' => 'idTitle', 'class' => 'span4'));
            $form->applyFilter('title', 'html_filter');
        }

        //$arrHide = array($id);

        $arrHide[0]['value'] = $this->name;
        $arrHide[0]['padding'] = 3;

        for ($i = 0; $i < count($arrLP); $i++) {
            if ($action != 'add') {
                if (($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide)) {
                    $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
                    $arrHide[$arrLP[$i]['id']]['padding'] = 3 + $arrLP[$i]['depth'] * 10;
                    if ($parent == $arrLP[$i]['id']) {
                        $s_selected_parent = $arrHide[$arrLP[$i]['id']];
                    }
                }
            } else {
                if ($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') {
                    $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
                    $arrHide[$arrLP[$i]['id']]['padding'] = 3 + $arrLP[$i]['depth'] * 10;
                    if ($parent == $arrLP[$i]['id']) {
                        $s_selected_parent = $arrHide[$arrLP[$i]['id']];
                    }
                }
            }
        }

        $parent_select = & $form->addElement('select', 'parent', get_lang('Parent'), '', 'class="learnpath_item_form" id="idParent" style="width:40%;" onchange="javascript: load_cbo(this.value);"');
        $my_count=0;
        foreach ($arrHide as $key => $value) {
            if ($my_count!=0) {
                // The LP name is also the first section and is not in the same charset like the other sections.
                $value['value'] = Security :: remove_XSS($value['value']);
                $parent_select->addOption($value['value'], $key, 'style="padding-left:' . $value['padding'] . 'px;"');
            } else {
                $value['value'] = Security :: remove_XSS($value['value']);
                $parent_select->addOption($value['value'], $key, 'style="padding-left:' . $value['padding'] . 'px;"');
            }
            $my_count++;
        }

        if (!empty($id)) {            
            $parent_select->setSelected($parent);
        } else {
            $parent_item_id = $_SESSION['parent_item_id'];
            $parent_select->setSelected($parent_item_id);
        }

        if (is_array($arrLP)) {
            reset($arrLP);
        }

        $arrHide = array ();

        //POSITION
        for ($i = 0; $i < count($arrLP); $i++) {
            if ($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id) {
                if ($extra_info['previous_item_id'] == $arrLP[$i]['id'])
                    $s_selected_position = $arrLP[$i]['id'];
                elseif ($action == 'add') $s_selected_position = $arrLP[$i]['id'];
                $arrHide[$arrLP[$i]['id']]['value'] = get_lang('After') . ' "' . $arrLP[$i]['title'] . '"';
            }
        }

        $position = & $form->addElement('select', 'previous', get_lang('Position'), '', 'id="previous" class="learnpath_item_form" style="width:40%;"');
        $position->addOption(get_lang('FirstPosition'), 0);

        foreach ($arrHide as $key => $value) {
        	$padding = isset($value['padding']) ? $value['padding']: 0;
            $position->addOption($value['value'], $key, 'style="padding-left:' . $padding . 'px;"');
        }
        $position->setSelected($s_selected_position);

        if (is_array($arrLP)) {
            reset($arrLP);
        }

        if ($action != 'move') {
            $id_prerequisite = 0;
            if (is_array($arrLP)) {
                foreach ($arrLP as $key => $value) {
                    if ($value['id'] == $id) {
                        $id_prerequisite = $value['prerequisite'];
                        break;
                    }
                }
            }

            $arrHide = array ();

            for ($i = 0; $i < count($arrLP); $i++) {
                if ($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dokeos_chapter') {
                    if ($extra_info['previous_item_id'] == $arrLP[$i]['id'])
                        $s_selected_position = $arrLP[$i]['id'];
                    elseif ($action == 'add') $s_selected_position = $arrLP[$i]['id'];

                    $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];

                }
            }
            
            if (!$no_display_add) {
                if (($extra_info == 'new' || $extra_info['item_type'] == TOOL_DOCUMENT || $_GET['edit'] == 'true')) {
                    if (isset ($_POST['content']))
                        $content = stripslashes($_POST['content']);
                    elseif (is_array($extra_info)) {
                        //If it's an html document or a text file
                        if (!$no_display_edit_textarea) {
                            $content = $this->display_document($extra_info['path'], false, false);
                        }
                    } elseif (is_numeric($extra_info)) 
                    	$content = $this->display_document($extra_info, false, false);
                    else
                        $content = '';
				
                    if (!$no_display_edit_textarea) {
                        // We need to calculate here some specific settings for the online editor.
                        // The calculated settings work for documents in the Documents tool
                        // (on the root or in subfolders).
                        // For documents in native scorm packages it is unclear whether the
                        // online editor should be activated or not.
                        
                    	// A new document, it is in the root of the repository.
                    	$relative_path 	 = '';
                    	$relative_prefix = '';
                    	
                    	if (is_array($extra_info) && $extra_info != 'new') {                        	                 
                            // The document already exists. Whe have to determine its relative path towards the repository root.
                            $relative_path = explode('/', $extra_info['dir']);
                            $cnt = count($relative_path) - 2;
                            if ($cnt < 0) {
                                $cnt = 0;
                            }
                            $relative_prefix = str_repeat('../', $cnt);
                            $relative_path 	 = array_slice($relative_path, 1, $cnt);
                            $relative_path 	 = implode('/', $relative_path);
                            if (strlen($relative_path) > 0) {
                                $relative_path = $relative_path . '/';
                            }
                        } else {
                        	global $_course;
							$result = $this->generate_lp_folder($_course, '');
							$relative_path = api_substr($result['dir'], 1, strlen($result['dir']));
							$relative_prefix = '../../';
                        }                        
                                              
                        $editor_config = array( 'ToolbarSet' 			=> 'LearningPathDocuments', 
                        						'Width' 				=> '100%', 
                        						'Height' 				=> '500', 
                        						'FullPage' 				=> true,
                            					'CreateDocumentDir' 	=> $relative_prefix,
                            					'CreateDocumentWebDir' 	=> api_get_path(WEB_COURSE_PATH) . api_get_course_path().'/document/',
                            					'BaseHref' 				=> api_get_path(WEB_COURSE_PATH) . api_get_course_path().'/document/'.$relative_path
                        );

                        if ($_GET['action'] == 'add_item') {
                            $class = 'add';
                            $text = get_lang('LPCreateDocument');
                        } else
                            if ($_GET['action'] == 'edit_item') {
                                $class = 'save';
                                $text = get_lang('SaveDocument');
                            }

                        $form->addElement('style_submit_button', 'submit_button', $text, 'class="' . $class . '"');
                        $renderer = $form->defaultRenderer();
                        $renderer->setElementTemplate('<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{label}<br />{element}', 'content_lp');

                        $form->addElement('html', '<div>');
                        $form->addElement('html_editor', 'content_lp', '', null, $editor_config);
                        $form->addElement('html', '</div>');
                        $defaults['content_lp'] = $content;
                    }
                } elseif (is_numeric($extra_info)) {
                    $form->addElement('style_submit_button', 'submit_button', get_lang('SaveDocument'), 'class="save"');
                    $return = $this->display_document($extra_info, true, true, true);
                    $form->addElement('html', $return);
                }
            }
        }
        
        if ($action == 'move') {
            $form->addElement('hidden', 'title', $item_title);
            $form->addElement('hidden', 'description', $item_description);
        }
        if (is_numeric($extra_info)) {
            $form->addElement('style_submit_button', 'submit_button', get_lang('SaveDocument'), 'value="submit_button", class="save"');
            $form->addElement('hidden', 'path', $extra_info);
        } elseif (is_array($extra_info)) {
            $form->addElement('style_submit_button', 'submit_button', get_lang('SaveDocument'), 'class="save"');
            $form->addElement('hidden', 'path', $extra_info['path']);
        }

        $form->addElement('hidden', 'type', TOOL_DOCUMENT);
        $form->addElement('hidden', 'post_time', time());

        $form->setDefaults($defaults);

        return $form->return_form();
    }

    /**
     * Return HTML form to add/edit a link item
     * @param string	Action (add/edit)
     * @param integer	Item ID if exists
     * @param mixed		Extra info
     * @return	string	HTML form
     */
    public function display_link_form($action = 'add', $id = 0, $extra_info = '') {
        $course_id = api_get_course_int_id();
        global $charset;
        $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);
        $tbl_link = Database :: get_course_table(TABLE_LINK);

        if ($id != 0 && is_array($extra_info)) {
            $item_title = stripslashes($extra_info['title']);
            $item_description = stripslashes($extra_info['description']);
            $item_url = stripslashes($extra_info['url']);
        } elseif (is_numeric($extra_info)) {
            $sql_link = "SELECT title, description, url FROM " . $tbl_link . " WHERE c_id = ".$course_id." AND id = " . $extra_info;
            $result = Database::query($sql_link);
            $row = Database :: fetch_array($result);
            $item_title       = $row['title'];
            $item_description = $row['description'];
            $item_url = $row['url'];
        } else {
            $item_title = '';
            $item_description = '';
            $item_url = '';
        }

        $legend = '<legend>';

        if ($id != 0 && is_array($extra_info))
            $parent = $extra_info['parent_item_id'];
        else
            $parent = 0;

        $sql = "SELECT * FROM " . $tbl_lp_item . " WHERE c_id = ".$course_id." AND lp_id = " . $this->lp_id;
        $result = Database::query($sql);
        $arrLP = array ();

        while ($row = Database :: fetch_array($result)) {
            $arrLP[] = array (
                'id' => $row['id'],
                'item_type' => $row['item_type'],
                'title' => $row['title'],
                'path' => $row['path'],
                'description' => $row['description'],
                'parent_item_id' => $row['parent_item_id'],
                'previous_item_id' => $row['previous_item_id'],
                'next_item_id' => $row['next_item_id'],
                'display_order' => $row['display_order'],
                'max_score' => $row['max_score'],
                'min_score' => $row['min_score'],
                'mastery_score' => $row['mastery_score'],
                'prerequisite' => $row['prerequisite']
            );
        }

        $this->tree_array($arrLP);
        $arrLP = $this->arrMenu;
        unset ($this->arrMenu);

        if ($action == 'add')
            $legend .= get_lang('CreateTheLink') . '&nbsp;:';
        elseif ($action == 'move') $legend .= get_lang('MoveCurrentLink') . '&nbsp;:';
        else
            $legend .= get_lang('EditCurrentLink') . '&nbsp;:';

        $legend .= '</legend>';
        
        $return .= '<div class="sectioncomment">';
        $return .= '<form method="POST">';
        $return .= $legend;
        $return .= '<table>';

        if ($action != 'move') {
            $return .= '<tr>';
            $return .= '<td class="label"><label for="idTitle">' . get_lang('Title') . '</label></td>';
            $return .= '<td class="input"><input id="idTitle" name="title" size="44" type="text" value="' . $item_title . '" class="learnpath_item_form"/></td>';
            $return .= '</tr>';
        }

        $return .= '<tr>';
        $return .= '<td class="label"><label for="idParent">' . get_lang('Parent') . '</label></td>';
        $return .= '<td class="input">';
        $return .= '<select id="idParent" style="width:100%;" name="parent" onChange="javascript: load_cbo(this.value);" class="learnpath_item_form" size="1">';
        $return .= '<option class="top" value="0">' . $this->name . '</option>';
        $arrHide = array (
            $id
        );

        $parent_item_id = $_SESSION['parent_item_id'];

        for ($i = 0; $i < count($arrLP); $i++) {
            if ($action != 'add') {
                if (($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide)) {
                    $return .= '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . $arrLP[$i]['title'] . '</option>';
                } else {
                    $arrHide[] = $arrLP[$i]['id'];
                }
            } else {
                if ($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir')
                    $return .= '<option ' . (($parent_item_id == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . $arrLP[$i]['title'] . '</option>';
            }
        }

        if (is_array($arrLP)) {
            reset($arrLP);
        }

        $return .= '</select>';
        $return .= '</td>';
        $return .= '</tr>';
        $return .= '<tr>';
        $return .= '<td class="label"><label for="previous">' . get_lang('Position') . '</label></td>';
        $return .= '<td class="input">';
        
        $return .= '<select id="previous" name="previous" style="width:100%;" size="1" class="learnpath_item_form">';
        $return .= '<option class="top" value="0">' . get_lang('FirstPosition') . '</option>';
        for ($i = 0; $i < count($arrLP); $i++) {
            if ($arrLP[$i]['parent_item_id'] == $parent_item_id && $arrLP[$i]['id'] != $id) {
                if ($extra_info['previous_item_id'] == $arrLP[$i]['id'])
                    $selected = 'selected="selected" ';
                elseif ($action == 'add') 
                    $selected = 'selected="selected" ';
                else
                    $selected = '';

                $return .= '<option ' . $selected . 'value="' . $arrLP[$i]['id'] . '">' . get_lang('After') . ' "' . $arrLP[$i]['title'] . '"</option>';
            }
        }
        $return .= '</select>';
        $return .= '</td>';
        $return .= '</tr>';

        if ($action != 'move') {
            $return .= '<tr>';
            $return .= '<td class="label"><label for="idURL">' . get_lang('Url') . '</label></td>';
            $return .= '<td class="input"><input' . (is_numeric($extra_info) ? ' disabled="disabled"' : '') . ' id="idURL" name="url" style="width:99%;" type="text" value="' . $item_url . '" class="learnpath_item_form" /></td>';
            $return .= '</tr>';
            $id_prerequisite = 0;
            if (is_array($arrLP)) {
                foreach ($arrLP as $key => $value) {
                    if ($value['id'] == $id) {
                        $id_prerequisite = $value['prerequisite'];
                        break;
                    }
                }
            }

            $arrHide = array();
            for ($i = 0; $i < count($arrLP); $i++) {
                if ($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dokeos_chapter') {
                    if ($extra_info['previous_item_id'] == $arrLP[$i]['id'])
                        $s_selected_position = $arrLP[$i]['id'];
                    elseif ($action == 'add') $s_selected_position = 0;
                    $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];

                }
            }
            $return .= '</tr>';
        }

        $return .= '<tr>';
        if ($action == 'add') {
            $return .= '<td>&nbsp;</td><td><button class="save" name="submit_button" type="submit">' . get_lang('AddLinkToCourse') . '</button></td>';
        } else {
            $return .= '<td>&nbsp;</td><td><button class="save" name="submit_button" type="submit">' . get_lang('EditCurrentLink') . '</button></td>';
        }
        $return .= '</tr>';
        $return .= '</table>';
        
        if ($action == 'move') {
            $return .= '<input name="title" type="hidden" value="' . $item_title . '" />';
            $return .= '<input name="description" type="hidden" value="' . $item_description . '" />';
        }

        if (is_numeric($extra_info)) {
            $return .= '<input name="path" type="hidden" value="' . $extra_info . '" />';
        } elseif (is_array($extra_info)) {
            $return .= '<input name="path" type="hidden" value="' . $extra_info['path'] . '" />';
        }
        $return .= '<input name="type" type="hidden" value="' . TOOL_LINK . '" />';
        $return .= '<input name="post_time" type="hidden" value="' . time() . '" />';
        $return .= '</form>';
        $return .= '</div>';
        return $return;
    }

    /**
     * Return HTML form to add/edit a student publication (work)
     * @param	string	Action (add/edit)
     * @param	integer	Item ID if already exists
     * @param	mixed	Extra info (work ID if integer)
     * @return	string	HTML form
     */
    public function display_student_publication_form($action = 'add', $id = 0, $extra_info = '') {
        $course_id = api_get_course_int_id();
        global $charset;

        $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);
        $tbl_publication = Database :: get_course_table(TABLE_STUDENT_PUBLICATION);

        if ($id != 0 && is_array($extra_info)) {
            $item_title = stripslashes($extra_info['title']);
            $item_description = stripslashes($extra_info['description']);
        }
        elseif (is_numeric($extra_info)) {
            $sql_publication = "SELECT title, description FROM " . $tbl_publication . "
                            WHERE c_id = ".$course_id." AND id = " . $extra_info;

            $result = Database::query($sql_publication);
            $row = Database :: fetch_array($result);

            $item_title = $row['title'];
        } else {
            $item_title = get_lang('Student_publication');
        }

        $legend = '<legend>';

        if ($id != 0 && is_array($extra_info))
            $parent = $extra_info['parent_item_id'];
        else
            $parent = 0;

        $sql = "SELECT * FROM " . $tbl_lp_item . "
                WHERE c_id = ".$course_id." AND lp_id = " . $this->lp_id;

        $result = Database::query($sql);

        $arrLP = array ();

        while ($row = Database :: fetch_array($result)) {
            $arrLP[] = array (
                'id' => $row['id'],
                'item_type' => $row['item_type'],
                'title' => $row['title'],
                'path' => $row['path'],
                'description' => $row['description'],
                'parent_item_id' => $row['parent_item_id'],
                'previous_item_id' => $row['previous_item_id'],
                'next_item_id' => $row['next_item_id'],
                'display_order' => $row['display_order'],
                'max_score' => $row['max_score'],
                'min_score' => $row['min_score'],
                'mastery_score' => $row['mastery_score'],
                'prerequisite' => $row['prerequisite']
            );
        }

        $this->tree_array($arrLP);
        $arrLP = $this->arrMenu;
        unset ($this->arrMenu);

        if ($action == 'add')
            $legend .= get_lang('Student_publication') . '&nbsp;:' . "\n";
        elseif ($action == 'move') $legend .= get_lang('MoveCurrentStudentPublication') . '&nbsp;:' . "\n";
        else
            $legend .= get_lang('EditCurrentStudentPublication') . '&nbsp;:' . "\n";
        $legend .= '</legend>';
        
        $return .= '<div class="sectioncomment">';
        $return .= '<form method="POST">';
        $return .= $legend;
        $return .= '<table class="lp_form">';
        if ($action != 'move') {
            $return .= '<tr>';
            $return .= '<td class="label"><label for="idTitle">' . get_lang('Title') . '</label></td>';
            $return .= '<td class="input"><input id="idTitle" name="title" size="44" type="text" value="' . $item_title . '" class="learnpath_item_form" /></td>';
            $return .= '</tr>';
        }
        $return .= '<tr>';
        $return .= '<td class="label"><label for="idParent">' . get_lang('Parent') . '</label></td>';
        $return .= '<td class="input">';
        $return .= "\t\t\t\t" . '<select id="idParent" name="parent" style="width:100%;" onChange="javascript: load_cbo(this.value);" class="learnpath_item_form" size="1">';
        //$parent_item_id = $_SESSION['parent_item_id'];
        $return .= '<option class="top" value="0">' . $this->name . '</option>';
        $arrHide = array (
            $id
        );

        for ($i = 0; $i < count($arrLP); $i++) {
            if ($action != 'add') {
                if (($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide)) {
                    $return .= '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . $arrLP[$i]['title'] . '</option>';
                } else {
                    $arrHide[] = $arrLP[$i]['id'];
                }
            } else {
                if ($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir')
                    $return .= '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . $arrLP[$i]['title'] . '</option>';
            }
        }

        if (is_array($arrLP)) {
            reset($arrLP);
        }
        $return .= "\t\t\t\t" . '</select>';
        $return .= '</td>';
        $return .= '</tr>';
        $return .= '<tr>';
        $return .= '<td class="label"><label for="previous">' . get_lang('Position') . '</label></td>';
        $return .= '<td class="input">';
        $return .= "\t\t\t\t" . '<select id="previous" name="previous" style="width:100%;" size="1" class="learnpath_item_form">';
        $return .= '<option class="top" value="0">' . get_lang('FirstPosition') . '</option>';
        for ($i = 0; $i < count($arrLP); $i++) {
            if ($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id) {
                if ($extra_info['previous_item_id'] == $arrLP[$i]['id'])
                    $selected = 'selected="selected" ';
                elseif ($action == 'add') $selected = 'selected="selected" ';
                else
                    $selected = '';

                $return .= '<option ' . $selected . 'value="' . $arrLP[$i]['id'] . '">' . get_lang('After') . ' "' . $arrLP[$i]['title'] . '"</option>';
            }
        }
        $return .= "\t\t\t\t" . '</select>';
        $return .= '</td>';
        $return .= '</tr>';
        if ($action != 'move') {
            $id_prerequisite = 0;
            if (is_array($arrLP)) {
                foreach ($arrLP as $key => $value) {
                    if ($value['id'] == $id) {
                        $id_prerequisite = $value['prerequisite'];
                        break;
                    }
                }
            }
            $arrHide = array ();
            for ($i = 0; $i < count($arrLP); $i++) {
                if ($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dokeos_chapter') {
                    if ($extra_info['previous_item_id'] == $arrLP[$i]['id'])
                        $s_selected_position = $arrLP[$i]['id'];
                    elseif ($action == 'add') $s_selected_position = 0;
                    $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];

                }
            }

            // Commented the prerequisites, only visible in edit (work).
            /*
                    $return .= '<tr>';
                    $return .= '<td class="label"><label for="idPrerequisites">'.get_lang('Prerequisites').'</label></td>';
                    $return .= '<td class="input"><select name="prerequisites" id="prerequisites" class="learnpath_item_form"><option value="0">'.get_lang('NoPrerequisites').'</option>';

                    foreach($arrHide as $key => $value) {
                        if ($key == $s_selected_position && $action == 'add') {
                            $return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
                        }
                        elseif ($key == $id_prerequisite && $action == 'edit') {
                            $return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
                        }
                        else {
                            $return .= '<option value="'.$key.'">'.$value['value'].'</option>';
                        }
                    }

                    $return .= "</select></td>";
            */
            $return .= '</tr>';
        }

        $return .= '<tr>';
        if ($action == 'add') {
            $return .= '<td>&nbsp</td><td><button class="save" name="submit_button" type="submit">' . get_lang('AddAssignmentToCourse') . '</button></td>';
        } else {
            $return .= '<td>&nbsp</td><td><button class="save" name="submit_button" type="submit">' . get_lang('EditCurrentStudentPublication') . '</button></td>';
        }
        $return .= '</tr>';

        $return .= '</table>';

        if ($action == 'move') {
            $return .= '<input name="title" type="hidden" value="' . $item_title . '" />';
            $return .= '<input name="description" type="hidden" value="' . $item_description . '" />';
        }

        if (is_numeric($extra_info)) {
            $return .= '<input name="path" type="hidden" value="' . $extra_info . '" />';
        } elseif (is_array($extra_info)) {
            $return .= '<input name="path" type="hidden" value="' . $extra_info['path'] . '" />';
        }
        $return .= '<input name="type" type="hidden" value="' . TOOL_STUDENTPUBLICATION . '" />';
        $return .= '<input name="post_time" type="hidden" value="' . time() . '" />';
        $return .= '</form>';
        $return .= '</div>';
        return $return;
    }

    /**
     * Displays the menu for manipulating a step
     * @return string html 
     */
    public function display_manipulate($item_id, $item_type = TOOL_DOCUMENT) {
        $course_id = api_get_course_int_id();
        global $charset, $_course;
        $return = '<div class="actions">';

        switch ($item_type) {
            case 'dokeos_chapter' :
            case 'chapter' :
                // Commented the message cause should not show it.
                //$lang = get_lang('TitleManipulateChapter');
                break;

            case 'dokeos_module' :
            case 'module' :
                // Commented the message cause should not show it.
                //$lang = get_lang('TitleManipulateModule');
                break;

            case TOOL_DOCUMENT :
                // Commented the message cause should not show it.
                //$lang = get_lang('TitleManipulateDocument');
                break;

            case TOOL_LINK :
            case 'link' :
                // Commented the message cause should not show it.
                //$lang = get_lang('TitleManipulateLink');
                break;

            case TOOL_QUIZ :
                // Commented the message cause should not show it.
                //$lang = get_lang('TitleManipulateQuiz');
                break;

            case TOOL_STUDENTPUBLICATION :
                // Commented the message cause should not show it.
                //$lang = get_lang('TitleManipulateStudentPublication');
                break;
        }

        $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);
        $item_id = intval($item_id);
        $sql    = "SELECT * FROM " . $tbl_lp_item . " as lp WHERE lp.c_id = ".$course_id." AND lp.id = " . $item_id;
        $result = Database::query($sql);

        $row = Database::fetch_assoc($result);
        $s_title = $row['title'];

        // We display an audio player if needed.
        if (!empty ($row['audio'])) {
            $return .= '<div class="lp_mediaplayer" id="container"><a href="http://www.macromedia.com/go/getflashplayer">Get the Flash Player</a> to see this player.</div>';
            $return .= '<script type="text/javascript" src="../inc/lib/mediaplayer/swfobject.js"></script>';
            $return .= '<script type="text/javascript">
                                        var s1 = new SWFObject("../inc/lib/mediaplayer/player.swf","ply","250","20","9","#FFFFFF");
                                        s1.addParam("allowscriptaccess","always");
                                        s1.addParam("flashvars","file=../../courses/' . $_course['path'] . '/document/audio/' . $row['audio'] . '&autostart=true");
                                        s1.write("container");
                                    </script>';
        }                
        $url = api_get_self() . '?cidReq='.Security::remove_XSS($_GET['cidReq']).'&view=build&id='.$item_id .'&lp_id='.$this->lp_id;
         
        $return .= Display::url(Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL), $url.'&action=edit_item&path_item=' . $row['path']);
        $return .= Display::url(Display::return_icon('move.png', get_lang('Move'), array(), ICON_SIZE_SMALL), $url.'&action=move_item');
        
        // Commented for now as prerequisites cannot be added to chapters.
        if ($item_type != 'dokeos_chapter' && $item_type != 'chapter') {
            $return .= Display::url(Display::return_icon('accept.png', get_lang('Prerequisites'), array(), ICON_SIZE_SMALL), $url.'&action=edit_item_prereq');
        }
        $return .= Display::url(Display::return_icon('delete.png', get_lang('Delete'), array(), ICON_SIZE_SMALL), $url.'&action=delete_item');
        $return .= '</div>';
        return $return;
    }

    /**
     * Creates the javascript needed for filling up the checkboxes without page reload
     *
     * @return string
     */
    public function get_js_dropdown_array() {
        $course_id = api_get_course_int_id();

        $return = 'var child_name = new Array();' . "\n";
        $return .= 'var child_value = new Array();' . "\n\n";
        $return .= 'child_name[0] = new Array();' . "\n";
        $return .= 'child_value[0] = new Array();' . "\n\n";
        $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);
        $sql_zero = "SELECT * FROM " . $tbl_lp_item . "
                    WHERE c_id = ".$course_id." AND lp_id = " . $this->lp_id . " AND parent_item_id = 0
                    ORDER BY display_order ASC";
        $res_zero = Database::query($sql_zero);

        global $charset;
        $i = 0;

        while ($row_zero = Database :: fetch_array($res_zero)) {
        	$js_var = json_encode(get_lang('After').' '.$row_zero['title']);
            $return .= 'child_name[0][' . $i . '] = '.$js_var.' ;' . "\n";
            $return .= 'child_value[0][' . $i++ . '] = "' . $row_zero['id'] . '";' . "\n";
        }
        $return .= "\n";
        $sql = "SELECT * FROM " . $tbl_lp_item . " WHERE c_id = ".$course_id." AND lp_id = " . $this->lp_id;
        $res = Database::query($sql);
        while ($row = Database :: fetch_array($res)) {
            $sql_parent = "
                            SELECT * FROM " . $tbl_lp_item . "
                            WHERE c_id = ".$course_id." AND parent_item_id = " . $row['id'] . "
                            ORDER BY display_order ASC";
            $res_parent = Database::query($sql_parent);
            $i = 0;
            $return .= 'child_name[' . $row['id'] . '] = new Array();' . "\n";
            $return .= 'child_value[' . $row['id'] . '] = new Array();' . "\n\n";

            while ($row_parent = Database :: fetch_array($res_parent)) {
            	$js_var = json_encode(get_lang('After').' '.$row_parent['title']);
                $return .= 'child_name[' . $row['id'] . '][' . $i . '] =   '.$js_var.' ;' . "\n";
                $return .= 'child_value[' . $row['id'] . '][' . $i++ . '] = "' . $row_parent['id'] . '";' . "\n";
            }
            $return .= "\n";
        }
        return $return;
    }

    /**
     * Display the form to allow moving an item
     * @param	integer		Item ID
     * @return	string		HTML form
     */
    public function display_move_item($item_id) {
        $course_id = api_get_course_int_id();

        global $_course; //will disappear
        global $charset;
        $return = '';

        if (is_numeric($item_id)) {
            $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);

            $sql = "SELECT * FROM " . $tbl_lp_item . "
                    WHERE c_id = ".$course_id." AND id = " . $item_id;

            $res = Database::query($sql);
            $row = Database :: fetch_array($res);

            switch ($row['item_type']) {
                case 'dokeos_chapter' :
                case 'dir' :
                case 'asset' :
                    $return .= $this->display_manipulate($item_id, $row['item_type']);
                    $return .= $this->display_item_form($row['item_type'], get_lang('MoveCurrentChapter'), 'move', $item_id, $row);
                    break;

                case 'dokeos_module' :
                    $return .= $this->display_manipulate($item_id, $row['item_type']);
                    $return .= $this->display_item_form($row['item_type'], 'Move th current module:', 'move', $item_id, $row);
                    break;
                case TOOL_DOCUMENT :
                    $return .= $this->display_manipulate($item_id, $row['item_type']);
                    $return .= $this->display_document_form('move', $item_id, $row);
                    break;
                case TOOL_LINK :
                    $return .= $this->display_manipulate($item_id, $row['item_type']);
                    $return .= $this->display_link_form('move', $item_id, $row);
                    break;
                case TOOL_HOTPOTATOES :
                    $return .= $this->display_manipulate($item_id, $row['item_type']);
                    $return .= $this->display_link_form('move', $item_id, $row);
                    break;
                case TOOL_QUIZ :
                    $return .= $this->display_manipulate($item_id, $row['item_type']);
                    $return .= $this->display_quiz_form('move', $item_id, $row);
                    break;
                case TOOL_STUDENTPUBLICATION :
                    $return .= $this->display_manipulate($item_id, $row['item_type']);
                    $return .= $this->display_student_publication_form('move', $item_id, $row);
                    break;
                case TOOL_FORUM :
                    $return .= $this->display_manipulate($item_id, $row['item_type']);
                    $return .= $this->display_forum_form('move', $item_id, $row);
                    break;
                case TOOL_THREAD :
                    $return .= $this->display_manipulate($item_id, $row['item_type']);
                    $return .= $this->display_forum_form('move', $item_id, $row);
                    break;
            }
        }

        return $return;
    }

    /**
     * Displays a basic form on the overview page for changing the item title and the item description.
     * @param string $item_type
     * @param string $title
     * @param array $data
     * @return string
     */
    public function display_item_small_form($item_type, $title = '', $data) {
        $return = '<div class="lp_small_form">';
        $return .= '<p class="lp_title">' . $title . '</p>';
        $return .= '<form method="post">';
        $return .= '<table cellpadding="0" cellspacing="0" class="lp_form">';
        $return .= '<tr>';
        $return .= '<td class="label"><label for="idTitle">Title&nbsp;:</label></td>';
        $return .= '<td class="input"><input class="small_form" id="idTitle" name="title" type="text" value="' . api_html_entity_decode($data['title'], ENT_QUOTES) . '" /></td>';
        $return .= '</tr>';        
        $return .= '<tr>';
        $return .= '<td colspan="2"><button class="save" name="submit_button" type="submit">' . get_lang('Save') . '</button></td>';
        $return .= '</tr>';
        $return .= '</table>';
        $return .= '<input name="parent" type="hidden" value="' . $data['parent_item_id'] . '"/>';
        $return .= '<input name="previous" type="hidden" value="' . $data['previous_item_id'] . '"/>';
        $return .= '</form>';
        $return .= '</div>';
        return $return;
    }

    /**
     * Return HTML form to allow prerequisites selection
     * @param	integer Item ID
     * @return	string	HTML form
     */
    public function display_item_prerequisites_form($item_id) {        
        $course_id = api_get_course_int_id();
        $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);
        $item_id = intval($item_id);
        /* Current prerequisite */
        $sql = "SELECT * FROM $tbl_lp_item WHERE c_id = $course_id AND id = " . $item_id;
        $result = Database::query($sql);
        $row    = Database::fetch_array($result);

        $preq_id = $row['prerequisite'];
        //$preq_mastery = $row['mastery_score'];
        //$preq_max = $row['max_score'];

        $return = $this->display_manipulate($item_id, TOOL_DOCUMENT);
        $return .= '<div class="sectioncomment">';
        $return .= '<form method="POST">';
        
        $return = '<legend>';
        $return .= get_lang('AddEditPrerequisites');
        $return .= '</legend>';
        
        $return .= '<table class="data_table" style="width:650px">';
        $return .= '<tr>';
        $return .= '<th height="24">' . get_lang('Prerequisites') . '</th>';
        $return .= '<th width="70" height="24">' . get_lang('Minimum') . '</th>';
        $return .= '<th width="70" height="24">' . get_lang('Maximum') . '</th>';
        $return .= '</tr>';

        // Adding the none option to the prerequisites see http://www.chamilo.org/es/node/146
        $return .= '<tr >';
        $return .= '<td colspan="3" class="radio">';
        $return .= '<input checked="checked" id="idNone" name="prerequisites"  style="margin-left:0px; margin-right:10px;" type="radio" />';
        $return .= '<label for="idNone">' . get_lang('None') . '</label>';
        $return .= '</tr>';

        $sql 	= "SELECT * FROM " . $tbl_lp_item . " WHERE c_id = $course_id AND lp_id = " . $this->lp_id;
        $result = Database::query($sql);
        $arrLP = array ();
        while ($row = Database :: fetch_array($result)) {
            $arrLP[] = array (
                'id' 				=> $row['id'],
                'item_type' 		=> $row['item_type'],
                'title' 			=> $row['title'],
                'ref' 				=> $row['ref'],
                'description' 		=> $row['description'],
                'parent_item_id' 	=> $row['parent_item_id'],
                'previous_item_id'	=> $row['previous_item_id'],
                'next_item_id' 		=> $row['next_item_id'],
                'max_score' 		=> $row['max_score'],
                'min_score' 		=> $row['min_score'],
                'mastery_score' 	=> $row['mastery_score'],
                'prerequisite' 		=> $row['prerequisite'],
                'next_item_id' 		=> $row['next_item_id'],
                'display_order' 	=> $row['display_order']
            );
            if ($row['ref'] == $preq_id) {
                $preq_mastery = $row['mastery_score'];
                $preq_max = $row['max_score'];
            }
        }
        $this->tree_array($arrLP);
        $arrLP = $this->arrMenu;
        unset ($this->arrMenu);

        for ($i = 0; $i < count($arrLP); $i++) {
            if ($arrLP[$i]['id'] == $item_id)
                break;
            $return .= '<tr>';
            $return .= '<td class="radio"' . (($arrLP[$i]['item_type'] != TOOL_QUIZ && $arrLP[$i]['item_type'] != TOOL_HOTPOTATOES) ? ' colspan="3"' : '') . '>';
            $return .= '<input' . (($arrLP[$i]['id'] == $preq_id) ? ' checked="checked" ' : '') . (($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter') ? ' disabled="disabled" ' : ' ') . 'id="id' . $arrLP[$i]['id'] . '" name="prerequisites" style="margin-left:' . $arrLP[$i]['depth'] * 10 . 'px; margin-right:10px;" type="radio" value="' . $arrLP[$i]['id'] . '" />';
            $icon_name = str_replace(' ', '', $arrLP[$i]['item_type']);
            if (file_exists('../img/lp_' . $icon_name . '.png')) {
                $return .= '<img alt="" src="../img/lp_' . $icon_name . '.png" style="margin-right:5px;" title="" />';
            } else
                if (file_exists('../img/lp_' . $icon_name . '.gif')) {
                    $return .= '<img alt="" src="../img/lp_' . $icon_name . '.gif" style="margin-right:5px;" title="" />';
                } else {
                    $return .= Display::return_icon('folder_document.gif','',array('style'=>'margin-right:5px;'));
                }
            $return .= '<label for="id' . $arrLP[$i]['id'] . '">' . $arrLP[$i]['title'] . '</label>';
            $return .= '</td>';
            //$return .= '<td class="radio"' . (($arrLP[$i]['item_type'] != TOOL_HOTPOTATOES) ? ' colspan="3"' : '') . ' />';

            if ($arrLP[$i]['item_type'] == TOOL_QUIZ) {
                $return .= '<td class="exercise" style="border:1px solid #ccc;">';
                $return .= '<center><input size="4" maxlength="3" name="min_' . $arrLP[$i]['id'] . '" type="text" value="' . (($arrLP[$i]['id'] == $preq_id) ? $preq_mastery : 0) . '" /></center>';
                $return .= '</td>';
                $return .= '<td class="exercise" style="border:1px solid #ccc;">';
                $return .= '<center><input size="4" maxlength="3" name="max_' . $arrLP[$i]['id'] . '" type="text" value="' . $arrLP[$i]['max_score'] . '" disabled="true" /></center>';
                $return .= '</td>';
            }
            if ($arrLP[$i]['item_type'] == TOOL_HOTPOTATOES) {
                $return .= '<td class="exercise" style="border:1px solid #ccc;">';
                $return .= '<center><input size="4" maxlength="3" name="min_' . $arrLP[$i]['id'] . '" type="text" value="' . (($arrLP[$i]['id'] == $preq_id) ? $preq_mastery : 0) . '" /></center>';
                $return .= '</td>';
                $return .= '<td class="exercise" style="border:1px solid #ccc;">';
                $return .= '<center><input size="4" maxlength="3" name="max_' . $arrLP[$i]['id'] . '" type="text" value="' . $arrLP[$i]['max_score'] . '" disabled="true" /></center>';
                $return .= '</td>';
            }
            $return .= '</tr>';
        }
        $return .= '<tr>';        
        $return .= '</tr>';
        $return .= '</table>';
        $return .= '<div style="padding-top:3px;">';
        $return .= '<button class="save" name="submit_button" type="submit">' . get_lang('ModifyPrerequisites') . ' </button></td>';
        $return .= '</div>';
        $return .= '</form>';
        $return .= '</div>';

        return $return;
    }

    /**
     * Return HTML list to allow prerequisites selection for lp
     * @param	integer Item ID
     * @return	string	HTML form
     */
    public function display_lp_prerequisites_list() {        
        $course_id = api_get_course_int_id();
        $lp_id = $this->lp_id;
        $tbl_lp = Database :: get_course_table(TABLE_LP_MAIN);

        // get current prerequisite
        $sql = "SELECT * FROM $tbl_lp WHERE c_id = $course_id AND id = $lp_id ";
        $result = Database::query($sql);
        $row = Database :: fetch_array($result);
        $preq_id = $row['prerequisite'];
        $session_id = api_get_session_id();
        $session_condition = api_get_session_condition($session_id);
        $sql 	= "SELECT * FROM $tbl_lp WHERE c_id = $course_id $session_condition ORDER BY display_order ";
        $rs = Database::query($sql);
        $return = '';
        $return .= '<select name="prerequisites" >';
        $return .= '<option value="0">'.get_lang('None').'</option>';
        if (Database::num_rows($rs) > 0) {
            while ($row = Database::fetch_array($rs)) {
                if ($row['id'] == $lp_id) {
                    continue;
                }
                $return .= '<option value="'.$row['id'].'" '.(($row['id']==$preq_id)?' selected ' : '').'>'.$row['name'].'</option>';
            }
        }
        $return .= '</select>';
        return $return;
    }

    /**
     * Creates a list with all the documents in it
     * @return string
     */
    public function get_documents() {
    	$course_info = api_get_course_info();
    	require_once api_get_path(LIBRARY_PATH).'document.lib.php';    	
    	$document_tree = DocumentManager::get_document_preview($course_info, $this->lp_id, null, 0, true);           
    	return $document_tree;      
    }

    /**
     * Creates a list with all the exercises (quiz) in it
     * @return string
     */
    public function get_exercises() {
        $course_id = api_get_course_int_id();

        // New for hotpotatoes.
        $uploadPath = DIR_HOTPOTATOES; //defined in main_api
        $tbl_doc = Database :: get_course_table(TABLE_DOCUMENT);
        $tbl_quiz = Database :: get_course_table(TABLE_QUIZ_TEST);

        $session_id = api_get_session_id();
        $condition_session = api_get_session_condition($session_id);

        $sql_quiz = "SELECT * FROM $tbl_quiz WHERE c_id = $course_id AND active<>'-1' $condition_session ORDER BY title ASC";
        $sql_hot  = "SELECT * FROM $tbl_doc  WHERE c_id = $course_id AND path LIKE '" . $uploadPath . "/%/%htm%'  $condition_session ORDER BY id ASC";

        $res_quiz = Database::query($sql_quiz);
        $res_hot  = Database::query($sql_hot);

        $return = '<ul class="lp_resource">';        
        
        $return .= '<li class="lp_resource_element">';        
        $return .= '<img alt="" src="../img/new_test_small.gif" style="margin-right:5px;" title="" />';
        $return .= '<a href="' . api_get_path(REL_CODE_PATH) . 'exercice/exercise_admin.php?lp_id=' . $this->lp_id . '">' . get_lang('NewExercise') . '</a>';
        $return .= '</li>';        

        // Display quizhotpotatoes.
        while ($row_hot = Database :: fetch_array($res_hot)) {
            $return .= '<li>';            
            $return .= '<img alt="hp" src="../img/hotpotatoes_s.png" style="margin-right:5px;" title="" width="18px" height="18px" />';
            $return .= '<a href="' . api_get_self() . '?cidReq=' . Security :: remove_XSS($_GET['cidReq']) . '&amp;action=add_item&amp;type=' . TOOL_HOTPOTATOES . '&amp;file=' . $row_hot['id'] . '&amp;lp_id=' . $this->lp_id . '">' . ((!empty ($row_hot['comment'])) ? $row_hot['comment'] : Security :: remove_XSS($row_hot['title'])) . '</a>';
            $return .= '</li>';
        }

        while ($row_quiz = Database :: fetch_array($res_quiz)) {
            $return .= '<li class="lp_resource_element" data_id="'.$row_quiz['id'].'" data_type="quiz" title="'.$row_quiz['title'].'" >';
            $return .= '<img alt="" src="../img/quizz_small.gif" style="margin-right:5px;" title="" />';            
            
            $return .= '<a class="moved" href="#">';
            $return .= Display::return_icon('move_everywhere.png', get_lang('Move'), array(), ICON_SIZE_TINY);
            $return .= '</a> ';
            
            $return .= '<a href="' . api_get_self() . '?cidReq=' . Security :: remove_XSS($_GET['cidReq']) . '&amp;action=add_item&amp;type=' . TOOL_QUIZ . '&amp;file=' . $row_quiz['id'] . '&amp;lp_id=' . $this->lp_id . '">' . 
                        Security :: remove_XSS(cut($row_quiz['title'], 80)).
                        '</a>';            
            $return .= '</li>';
        }

        $return .= '</ul>';
        return $return;
    }

    /**
     * Creates a list with all the links in it
     * @return string
     */
    public function get_links() {
        $course_id = api_get_course_int_id();
        $tbl_link = Database :: get_course_table(TABLE_LINK);

        $session_id = api_get_session_id();
        $condition_session = api_get_session_condition($session_id);

        $sql_link = "SELECT id, title FROM $tbl_link WHERE c_id = ".$course_id." $condition_session ORDER BY title ASC";
        $res_link = Database::query($sql_link);        

        $return = '<ul class="lp_resource">';        
        $return .= '<li class="lp_resource_element">';
        $return .= '<img alt="" src="../img/linksnew.gif" style="margin-right:5px;width:16px" title="" />';
        $return .= '<a href="' . api_get_path(REL_CODE_PATH) . 'link/link.php?' . api_get_cidreq() . '&action=addlink&amp;lp_id=' . $this->lp_id . '" title="' . get_lang('LinkAdd') . '">' . get_lang('LinkAdd') . '</a>';
        $return .= '</li>';
        $course_info = api_get_course_info();
        
        while ($row_link = Database :: fetch_array($res_link)) {
            $item_visibility = api_get_item_visibility($course_info, TOOL_LINK, $row_link['id'], $session_id);
            if ($item_visibility != 2)  {
                $return .= '<li class="lp_resource_element" data_id="'.$row_link['id'].'" data_type="'.TOOL_LINK.'" title="'.$row_link['title'].'" >';
                
                $return .= '<img alt="" src="../img/lp_link.gif" style="margin-right:5px;" title="" />';
                
                $return .= '<a class="moved" href="#">';
                $return .= Display::return_icon('move_everywhere.png', get_lang('Move'), array(), ICON_SIZE_TINY);
                $return .= '</a> ';
                
                $return .= '<a href="' . api_get_self() . '?cidReq=' . Security :: remove_XSS($_GET['cidReq']) . '&amp;action=add_item&amp;type=' . TOOL_LINK . '&amp;file=' . $row_link['id'] . '&amp;lp_id=' . $this->lp_id . '">'.
                            $row_link['title'].
                            '</a>';
                $return .= '</li>';
            }
        }     
        $return .= '</ul>';
        return $return;
    }

    /**
     * Creates a list with all the student publications in it
     * @return unknown
     */
    public function get_student_publications() {
        //$course_id = api_get_course_int_id();
        //$tbl_student = Database :: get_course_table(TABLE_STUDENT_PUBLICATION);
        //$session_id = api_get_session_id();
        //$condition_session = api_get_session_condition($session_id);
        //$sql_student = "SELECT * FROM $tbl_student  WHERE c_id = ".$course_id." $condition_session  ORDER BY title ASC";
        //$res_student = Database::query($sql_student);
        //$return .= '<div class="lp_resource_header"' . " onclick=\"javascript: if(document.getElementById('resStudent').style.display == 'block') {document.getElementById('resStudent').style.display = 'none';} else {document.getElementById('resStudent').style.display = 'block';}\"" . '><img alt="" src="../img/lp_' . TOOL_STUDENTPUBLICATION . '.gif" style="margin-right:5px;" title="" />' . get_lang('Student_publication') . '</div>';
        $return = '<div class="lp_resource" >';
        $return .= '<div class="lp_resource_element">';
        $return .= '<img align="left" alt="" src="../img/works_small.gif" style="margin-right:5px;" title="" />';
        $return .= '<a href="' . api_get_self() . '?cidReq=' . Security :: remove_XSS($_GET['cidReq']) . '&amp;action=add_item&amp;type=' . TOOL_STUDENTPUBLICATION . '&amp;lp_id=' . $this->lp_id . '">' . get_lang('AddAssignmentPage') . '</a>';
        $return .= '</div>';
        $return .= '</div>';
        return $return;
    }

    /**
     * Creates a list with all the forums in it
     * @return string
     */
    public function get_forums() {
        require_once '../forum/forumfunction.inc.php';
        require_once '../forum/forumconfig.inc.php';
        
        $a_forums = get_forums();

        $return = '<ul class="lp_resource">';
        
        //First add link
        $return .= '<li class="lp_resource_element">';
        $return .= '<img alt="" src="../img/forum_new_small.gif" style="margin-right:5px;" title="" />';
        $return .= '<a href="' . api_get_path(REL_CODE_PATH) . 'forum/index.php?' . api_get_cidreq() . '&action=add&amp;content=forum&amp;origin=learnpath&amp;lp_id=' . $this->lp_id . '" title="' . get_lang('CreateANewForum') . '">' . get_lang('CreateANewForum') . '</a>';
        $return .= '</li>';
        
        $return .= '<script>
                    function toggle_forum(forum_id){
                        if(document.getElementById("forum_"+forum_id+"_content").style.display == "none"){
                            document.getElementById("forum_"+forum_id+"_content").style.display = "block";
                            document.getElementById("forum_"+forum_id+"_opener").src = "' . api_get_path(WEB_IMG_PATH) . 'remove.gif";
                        } else {
                            document.getElementById("forum_"+forum_id+"_content").style.display = "none";
                            document.getElementById("forum_"+forum_id+"_opener").src = "' . api_get_path(WEB_IMG_PATH) . 'add.gif";
                        }
                    }
                </script>';

        foreach ($a_forums as $forum) {            
            $return .= '<li class="lp_resource_element" data_id="'.$forum['forum_id'].'" data_type="'.TOOL_FORUM.'" title="'.$forum['forum_title'].'" >';

            if (!empty($forum['forum_id'])) {
                $return .= '<img alt="" src="../img/lp_forum.gif" style="margin-right:5px;" title="" />';
                
                $return .= '<a class="moved" href="#">';
				$return .= Display::return_icon('move_everywhere.png', get_lang('Move'), array(), ICON_SIZE_TINY);
                $return .= ' </a>';
                    
                $return .= '<a style="cursor:hand" onclick="javascript: toggle_forum(' . $forum['forum_id'] . ')" style="vertical-align:middle">
                                <img src="' . api_get_path(WEB_IMG_PATH) . 'add.gif" id="forum_' . $forum['forum_id'] . '_opener" align="absbottom" />
                            </a>
                            <a href="' . api_get_self() . '?cidReq=' . Security :: remove_XSS($_GET['cidReq']) . '&amp;action=add_item&amp;type=' . TOOL_FORUM . '&amp;forum_id=' . $forum['forum_id'] . '&amp;lp_id=' . $this->lp_id . '" style="vertical-align:middle">' . Security :: remove_XSS($forum['forum_title']) . '</a>';
            }
            $return .= '</li>';

            $return .= '<div style="display:none" id="forum_' . $forum['forum_id'] . '_content">';
            $a_threads = get_threads($forum['forum_id']);
            if (is_array($a_threads)) {
                foreach ($a_threads as $thread) {
                    $return .= '<li class="lp_resource_element" data_id="'.$thread['thread_id'].'" data_type="'.TOOL_THREAD.'" title="'.$thread['thread_title'].'" >';                    
                    $return .= Display::return_icon('forumthread.png', get_lang('Thread'), array(), ICON_SIZE_TINY);
                    $return .= '&nbsp;<a class="moved" href="#">';
                    $return .= Display::return_icon('move_everywhere.png', get_lang('Move'), array(), ICON_SIZE_TINY);
                    $return .= ' </a>';                    
                    $return .= '<a href="' . api_get_self() . '?cidReq=' . Security :: remove_XSS($_GET['cidReq']) . '&amp;action=add_item&amp;type=' . TOOL_THREAD . '&amp;thread_id=' . $thread['thread_id'] . '&amp;lp_id=' . $this->lp_id . '">' . Security :: remove_XSS($thread['thread_title']) . '</a>';
                    $return .= '</li>';
                }
            }
            $return .= '</div>';
            
        }
        $return .= '</ul>';
        return $return;
    }

    /**
     * // TODO: The output encoding should be equal to the system encoding.
     *
     * Exports the learning path as a SCORM package. This is the main function that
     * gathers the content, transforms it, writes the imsmanifest.xml file, zips the
     * whole thing and returns the zip.
     *
     * This method needs to be called in PHP5, as it will fail with non-adequate
     * XML package (like the ones for PHP4), and it is *not* a static method, so
     * you need to call it on a learnpath object.
     * @TODO The method might be redefined later on in the scorm class itself to avoid
     * creating a SCORM structure if there is one already. However, if the initial SCORM
     * path has been modified, it should use the generic method here below.
     * @TODO link this function with the export_lp() function in the same class
     * @param	string	Optional name of zip file. If none, title of learnpath is
     * 					domesticated and trailed with ".zip"
     * @return	string	Returns the zip package string, or null if error
     */
    public  function scorm_export() {
        global $_course;
        
        $course_id = api_get_course_int_id();

        // Remove memory and time limits as much as possible as this might be a long process...
        if (function_exists('ini_set')) {
            $mem = ini_get('memory_limit');
            if (substr($mem, -1, 1) == 'M') {
                $mem_num = substr($mem, 0, -1);
                if ($mem_num < 128) {
                    ini_set('memory_limit', '128M');
                }
            } else {
                ini_set('memory_limit', '128M');
            }
            ini_set('max_execution_time', 600);
        //} else {
            //error_log('Scorm export: could not change memory and time limits', 0);
        }

        // Create the zip handler (this will remain available throughout the method).
        $archive_path = api_get_path(SYS_ARCHIVE_PATH);
        $sys_course_path = api_get_path(SYS_COURSE_PATH);
        $temp_dir_short = uniqid();
        $temp_zip_dir = $archive_path.'/'.$temp_dir_short;
        $temp_zip_file = $temp_zip_dir.'/'.md5(time()).'.zip';
        $zip_folder = new PclZip($temp_zip_file);
        $current_course_path = api_get_path(SYS_COURSE_PATH).api_get_course_path();
        $root_path = $main_path = api_get_path(SYS_PATH);
        $files_cleanup = array();

        // Place to temporarily stash the zipfiles.
        // create the temp dir if it doesn't exist
        // or do a cleanup befor creating the zipfile.
        if (!is_dir($temp_zip_dir)) {
            mkdir($temp_zip_dir, api_get_permissions_for_new_directories());
        } else {
            // Cleanup: Check the temp dir for old files and delete them.
            $handle = opendir($temp_zip_dir);
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..') {
                    unlink("$temp_zip_dir/$file");
                }
            }
            closedir($handle);
        }
        $zip_files = $zip_files_abs = $zip_files_dist = array();
        if (is_dir($current_course_path.'/scorm/'.$this->path) && is_file($current_course_path.'/scorm/'.$this->path.'/imsmanifest.xml')) {
            // Remove the possible . at the end of the path.
            $dest_path_to_lp = substr($this->path, -1) == '.' ? substr($this->path, 0, -1) : $this->path;
            $dest_path_to_scorm_folder = str_replace('//','/',$temp_zip_dir.'/scorm/'.$dest_path_to_lp);
            mkdir($dest_path_to_scorm_folder, api_get_permissions_for_new_directories(), true);
            $zip_files_dist = copyr($current_course_path.'/scorm/'.$this->path, $dest_path_to_scorm_folder, array('imsmanifest'), $zip_files);
        }
        // Build a dummy imsmanifest structure. Do not add to the zip yet (we still need it).
        // This structure is developed following regulations for SCORM 1.2 packaging in the SCORM 1.2 Content
        // Aggregation Model official document, secion "2.3 Content Packaging".
        $xmldoc = new DOMDocument('1.0'); // We are going to build a UTF-8 encoded manifest. Later we will recode it to the desired (and supported) encoding.
        $root = $xmldoc->createElement('manifest');
        $root->setAttribute('identifier', 'SingleCourseManifest');
        $root->setAttribute('version', '1.1');
        $root->setAttribute('xmlns', 'http://www.imsproject.org/xsd/imscp_rootv1p1p2');
        $root->setAttribute('xmlns:adlcp', 'http://www.adlnet.org/xsd/adlcp_rootv1p2');
        $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $root->setAttribute('xsi:schemaLocation', 'http://www.imsproject.org/xsd/imscp_rootv1p1p2 imscp_rootv1p1p2.xsd http://www.imsglobal.org/xsd/imsmd_rootv1p2p1 imsmd_rootv1p2p1.xsd http://www.adlnet.org/xsd/adlcp_rootv1p2 adlcp_rootv1p2.xsd');
        // Build mandatory sub-root container elements.
        $metadata = $xmldoc->createElement('metadata');
        $md_schema = $xmldoc->createElement('schema', 'ADL SCORM');
        $metadata->appendChild($md_schema);
        $md_schemaversion = $xmldoc->createElement('schemaversion', '1.2');
        $metadata->appendChild($md_schemaversion);
        $root->appendChild($metadata);

        $organizations = $xmldoc->createElement('organizations');

        $resources = $xmldoc->createElement('resources');

        // Build the only organization we will use in building our learnpaths.
        $organizations->setAttribute('default', 'chamilo_scorm_export');
        $organization = $xmldoc->createElement('organization');
        $organization->setAttribute('identifier', 'chamilo_scorm_export');
        // To set the title of the SCORM entity (=organization), we take the name given
        // in Chamilo and convert it to HTML entities using the Chamilo charset (not the
        // learning path charset) as it is the encoding that defines how it is stored
        // in the database. Then we convert it to HTML entities again as the "&" character
        // alone is not authorized in XML (must be &amp;).
        // The title is then decoded twice when extracting (see scorm::parse_manifest).
        $org_title = $xmldoc->createElement('title', api_utf8_encode($this->get_name()));
        $organization->appendChild($org_title);

        // For each element, add it to the imsmanifest structure, then add it to the zip.
        // Always call the learnpathItem->scorm_export() method to change it to the SCORM format.
        $link_updates = array();

        foreach ($this->items as $index => $item) {
            if (!in_array($item->type, array(TOOL_QUIZ, TOOL_FORUM, TOOL_THREAD, TOOL_LINK, TOOL_STUDENTPUBLICATION))) {
                // Get included documents from this item.
                if ($item->type == 'sco')
                    $inc_docs = $item->get_resources_from_source(null, api_get_path(SYS_COURSE_PATH).api_get_course_path().'/'.'scorm/'.$this->path.'/'.$item->get_path());
                else
                    $inc_docs = $item->get_resources_from_source();
                // Give a child element <item> to the <organization> element.
                $my_item_id = $item->get_id();
                $my_item = $xmldoc->createElement('item');
                $my_item->setAttribute('identifier', 'ITEM_'.$my_item_id);
                $my_item->setAttribute('identifierref', 'RESOURCE_'.$my_item_id);
                $my_item->setAttribute('isvisible', 'true');
                // Give a child element <title> to the <item> element.
                $my_title = $xmldoc->createElement('title', htmlspecialchars(api_utf8_encode($item->get_title()), ENT_QUOTES, 'UTF-8'));
                $my_item->appendChild($my_title);
                // Give a child element <adlcp:prerequisites> to the <item> element.
                $my_prereqs = $xmldoc->createElement('adlcp:prerequisites', $this->get_scorm_prereq_string($my_item_id));
                $my_prereqs->setAttribute('type', 'aicc_script');
                $my_item->appendChild($my_prereqs);
                // Give a child element <adlcp:maxtimeallowed> to the <item> element - not yet supported.
                //$xmldoc->createElement('adlcp:maxtimeallowed','');
                // Give a child element <adlcp:timelimitaction> to the <item> element - not yet supported.
                //$xmldoc->createElement('adlcp:timelimitaction','');
                // Give a child element <adlcp:datafromlms> to the <item> element - not yet supported.
                //$xmldoc->createElement('adlcp:datafromlms','');
                // Give a child element <adlcp:masteryscore> to the <item> element.
                $my_masteryscore = $xmldoc->createElement('adlcp:masteryscore', $item->get_mastery_score());
                $my_item->appendChild($my_masteryscore);

                // Attach this item to the organization element or hits parent if there is one.
                if (!empty($item->parent) && $item->parent != 0) {
                    $children = $organization->childNodes;
                    $possible_parent = &$this->get_scorm_xml_node($children, 'ITEM_'.$item->parent);
                    if (is_object($possible_parent)) {
                        $possible_parent->appendChild($my_item);
                    } else {
                        if ($this->debug > 0) { error_log('Parent ITEM_'.$item->parent.' of item ITEM_'.$my_item_id.' not found'); }
                    }
                } else {
                    if ($this->debug > 0) { error_log('No parent'); }
                    $organization->appendChild($my_item);
                }

                // Get the path of the file(s) from the course directory root.
                $my_file_path = $item->get_file_path('scorm/'.$this->path.'/');

                //$my_xml_file_path = api_htmlentities(api_utf8_encode($my_file_path), ENT_QUOTES, 'UTF-8');
                $my_xml_file_path = $my_file_path;
                $my_sub_dir = dirname($my_file_path);
                $my_sub_dir = str_replace('\\', '/', $my_sub_dir);
                //$my_xml_sub_dir = api_htmlentities(api_utf8_encode($my_sub_dir), ENT_QUOTES, 'UTF-8');
                $my_xml_sub_dir = $my_sub_dir;
                // Give a <resource> child to the <resources> element
                $my_resource = $xmldoc->createElement('resource');
                $my_resource->setAttribute('identifier', 'RESOURCE_'.$item->get_id());
                $my_resource->setAttribute('type', 'webcontent');
                $my_resource->setAttribute('href', $my_xml_file_path);
                // adlcp:scormtype can be either 'sco' or 'asset'.
                if ($item->type == 'sco') {
                    $my_resource->setAttribute('adlcp:scormtype', 'sco');
                } else {
                    $my_resource->setAttribute('adlcp:scormtype', 'asset');
                }
                // xml:base is the base directory to find the files declared in this resource.
                $my_resource->setAttribute('xml:base', '');
                // Give a <file> child to the <resource> element.
                $my_file = $xmldoc->createElement('file');
                $my_file->setAttribute('href', $my_xml_file_path);
                $my_resource->appendChild($my_file);

                // Dependency to other files - not yet supported.
                $i = 1;
                foreach ($inc_docs as $doc_info) {
                    if (count($doc_info) < 1 || empty($doc_info[0])) { continue; }
                    $my_dep = $xmldoc->createElement('resource');
                    $res_id = 'RESOURCE_'.$item->get_id().'_'.$i;
                    $my_dep->setAttribute('identifier', $res_id);
                    $my_dep->setAttribute('type', 'webcontent');
                    $my_dep->setAttribute('adlcp:scormtype', 'asset');
                    $my_dep_file = $xmldoc->createElement('file');
                    // Check type of URL.
                    //error_log(__LINE__.'Now dealing with '.$doc_info[0].' of type '.$doc_info[1].'-'.$doc_info[2], 0);
                    if ($doc_info[1] == 'remote') {
                        // Remote file. Save url as is.
                        $my_dep_file->setAttribute('href', $doc_info[0]);
                        $my_dep->setAttribute('xml:base', '');
                    } elseif ($doc_info[1] == 'local') {
                        switch ($doc_info[2]) {
                            case 'url': // Local URL - save path as url for now, don't zip file.
                                $abs_path = api_get_path(SYS_PATH).str_replace(api_get_path(WEB_PATH), '', $doc_info[0]);
                                $current_dir = dirname($abs_path);
                                $current_dir = str_replace('\\', '/', $current_dir);
                                $file_path = realpath($abs_path);
                                $file_path = str_replace('\\', '/', $file_path);
                                $my_dep_file->setAttribute('href', $file_path);
                                $my_dep->setAttribute('xml:base', '');
                                if (strstr($file_path, $main_path) !== false) {
                                    // The calculated real path is really inside Chamilo's root path.
                                    // Reduce file path to what's under the DocumentRoot.
                                    $file_path = substr($file_path, strlen($root_path) - 1);
                                    //echo $file_path;echo '<br /><br />';
                                    //error_log(__LINE__.'Reduced url path: '.$file_path, 0);
                                    $zip_files_abs[] = $file_path;
                                    $link_updates[$my_file_path][] = array('orig' => $doc_info[0], 'dest' => $file_path);
                                    $my_dep_file->setAttribute('href', $file_path);
                                    $my_dep->setAttribute('xml:base', '');
                                }
                                elseif (empty($file_path)) {
                                    /*$document_root = substr(api_get_path(SYS_PATH), 0, strpos(api_get_path(SYS_PATH), api_get_path(REL_PATH)));
                                    if (strpos($document_root, -1) == '/') {
                                        $document_root = substr(0, -1, $document_root);
                                    }*/
                                    $file_path = $_SERVER['DOCUMENT_ROOT'].$abs_path;
                                    $file_path = str_replace('//', '/', $file_path);
                                    if (file_exists($file_path)) {
                                        $file_path = substr($file_path, strlen($current_dir)); // We get the relative path.
                                        $zip_files[] = $my_sub_dir.'/'.$file_path;
                                        $link_updates[$my_file_path][] = array('orig' => $doc_info[0], 'dest' => $file_path);
                                        $my_dep_file->setAttribute('href', $file_path);
                                        $my_dep->setAttribute('xml:base', '');
                                    }
                                }
                                break;
                            case 'abs': // Absolute path from DocumentRoot. Save file and leave path as is in the zip.
                                $my_dep_file->setAttribute('href', $doc_info[0]);
                                $my_dep->setAttribute('xml:base', '');

                                //$current_dir = str_replace('\\', '/', dirname($current_course_path.'/'.$item->get_file_path())).'/';
                                // The next lines fix a bug when using the "subdir" mode of Chamilo, whereas
                                // an image path would be constructed as /var/www/subdir/subdir/img/foo.bar
                                $abs_img_path_without_subdir = $doc_info[0];
                                $relp = api_get_path(REL_PATH); // The url-append config param.
                                $pos = strpos($abs_img_path_without_subdir, $relp);
                                if ($pos === 0) {
                                    $abs_img_path_without_subdir = '/'.substr($abs_img_path_without_subdir, strlen($relp));
                                }
                                //$file_path = realpath(api_get_path(SYS_PATH).$doc_info[0]);
                                $file_path = realpath(api_get_path(SYS_PATH).$abs_img_path_without_subdir);
                                $file_path = str_replace('\\', '/', $file_path);
                                $file_path = str_replace('//', '/', $file_path);
                                //error_log(__LINE__.'Abs path: '.$file_path, 0);
                                // Prepare the current directory path (until just under 'document') with a trailing slash.
                                $cur_path = substr($current_course_path, -1) == '/' ? $current_course_path : $current_course_path.'/';
                                // Check if the current document is in that path.
                                if (strstr($file_path, $cur_path) !== false) {
                                    // The document is in that path, now get the relative path
                                    // to the containing document.
                                    $orig_file_path = dirname($cur_path.$my_file_path).'/';
                                    $orig_file_path = str_replace('\\', '/', $orig_file_path);
                                    $relative_path = '';
                                    if (strstr($file_path, $cur_path) !== false) {
                                        $relative_path = substr($file_path, strlen($orig_file_path));
                                        $file_path = substr($file_path, strlen($cur_path));
                                    } else {
                                        // This case is still a problem as it's difficult to calculate a relative path easily
                                        // might still generate wrong links.
                                        //$file_path = substr($file_path,strlen($cur_path));
                                        // Calculate the directory path to the current file (without trailing slash).
                                        $my_relative_path = dirname($file_path);
                                        $my_relative_path = str_replace('\\', '/', $my_relative_path);
                                        $my_relative_file = basename($file_path);
                                        // Calculate the directory path to the containing file (without trailing slash).
                                        $my_orig_file_path = substr($orig_file_path, 0, -1);
                                        $dotdot = '';
                                        $subdir = '';
                                        while (strstr($my_relative_path, $my_orig_file_path) === false && (strlen($my_orig_file_path) > 1) && (strlen($my_relative_path) > 1)) {
                                            $my_relative_path2 = dirname($my_relative_path);
                                            $my_relative_path2 = str_replace('\\', '/', $my_relative_path2);
                                            $my_orig_file_path = dirname($my_orig_file_path);
                                            $my_orig_file_path = str_replace('\\', '/', $my_orig_file_path);
                                            $subdir = substr($my_relative_path, strlen($my_relative_path2) + 1).'/'.$subdir;
                                            $dotdot += '../';
                                            $my_relative_path = $my_relative_path2;
                                        }
                                        $relative_path = $dotdot.$subdir.$my_relative_file;
                                    }
                                    // Put the current document in the zip (this array is the array
                                    // that will manage documents already in the course folder - relative).
                                    $zip_files[] = $file_path;
                                    // Update the links to the current document in the containing document (make them relative).
                                    $link_updates[$my_file_path][] = array('orig' => $doc_info[0], 'dest' => $relative_path);
                                    $my_dep_file->setAttribute('href', $file_path);
                                    $my_dep->setAttribute('xml:base', '');
                                }
                                elseif (strstr($file_path,$main_path) !== false) {
                                    // The calculated real path is really inside Chamilo's root path.
                                    // Reduce file path to what's under the DocumentRoot.
                                    $file_path = substr($file_path, strlen($root_path));
                                    //echo $file_path;echo '<br /><br />';
                                    //error_log('Reduced path: '.$file_path, 0);
                                    $zip_files_abs[] = $file_path;
                                    $link_updates[$my_file_path][] = array('orig' => $doc_info[0], 'dest' => $file_path);
                                    $my_dep_file->setAttribute('href', 'document/'.$file_path);
                                    $my_dep->setAttribute('xml:base', '');
                                }
                                elseif (empty($file_path)) {
                                    /*$document_root = substr(api_get_path(SYS_PATH), 0, strpos(api_get_path(SYS_PATH), api_get_path(REL_PATH)));
                                    if(strpos($document_root,-1) == '/') {
                                        $document_root = substr(0, -1, $document_root);
                                    }*/
                                    $file_path = $_SERVER['DOCUMENT_ROOT'].$doc_info[0];
                                    $file_path = str_replace('//', '/', $file_path);
                                    if (file_exists($file_path)) {
                                        $file_path = substr($file_path,strlen($current_dir)); // We get the relative path.
                                        $zip_files[] = $my_sub_dir.'/'.$file_path;
                                        $link_updates[$my_file_path][] = array('orig' => $doc_info[0], 'dest' => $file_path);
                                        $my_dep_file->setAttribute('href','document/'.$file_path);
                                        $my_dep->setAttribute('xml:base', '');
                                    }
                                }
                                break;
                             case 'rel': // Path relative to the current document. Save xml:base as current document's directory and save file in zip as subdir.file_path
                                 if (substr($doc_info[0], 0, 2) == '..') {
                                     // Relative path going up.
                                     $current_dir = dirname($current_course_path.'/'.$item->get_file_path()).'/';
                                     $current_dir = str_replace('\\', '/', $current_dir);
                                     $file_path = realpath($current_dir.$doc_info[0]);
                                     $file_path = str_replace('\\', '/', $file_path);
                                     //error_log($file_path.' <-> '.$main_path,0);
                                     if (strstr($file_path, $main_path) !== false) {
                                         // The calculated real path is really inside Chamilo's root path.
                                         // Reduce file path to what's under the DocumentRoot.
                                         $file_path = substr($file_path, strlen($root_path));
                                         //error_log('Reduced path: '.$file_path, 0);
                                         $zip_files_abs[] = $file_path;
                                         $link_updates[$my_file_path][] = array('orig' => $doc_info[0], 'dest' => $file_path);
                                         $my_dep_file->setAttribute('href', 'document/'.$file_path);
                                         $my_dep->setAttribute('xml:base', '');
                                     }
                                 } else {
                                     $zip_files[] = $my_sub_dir.'/'.$doc_info[0];
                                     $my_dep_file->setAttribute('href', $doc_info[0]);
                                     $my_dep->setAttribute('xml:base', $my_xml_sub_dir);
                                 }
                                 break;
                             default:
                                 $my_dep_file->setAttribute('href', $doc_info[0]);
                                 $my_dep->setAttribute('xml:base', '');
                                 break;
                        }
                    }
                    $my_dep->appendChild($my_dep_file);
                    $resources->appendChild($my_dep);
                    $dependency = $xmldoc->createElement('dependency');
                    $dependency->setAttribute('identifierref', $res_id);
                    $my_resource->appendChild($dependency);
                    $i++;
                }
                //$my_dependency = $xmldoc->createElement('dependency');
                //$my_dependency->setAttribute('identifierref', '');
                $resources->appendChild($my_resource);
                $zip_files[] = $my_file_path;

                //error_log('File '.$my_file_path. ' added to $zip_files', 0);
            } else {
                // If the item is a quiz or a link or whatever non-exportable, we include a step indicating it.
                if ($item->type == TOOL_LINK) {
                    $my_item = $xmldoc->createElement('item');
                    $my_item->setAttribute('identifier', 'ITEM_'.$item->get_id());
                    $my_item->setAttribute('identifierref', 'RESOURCE_'.$item->get_id());
                    $my_item->setAttribute('isvisible', 'true');
                    // Give a child element <title> to the <item> element.
                    $my_title = $xmldoc->createElement('title', htmlspecialchars(api_utf8_encode($item->get_title()), ENT_QUOTES, 'UTF-8'));
                    $my_item->appendChild($my_title);
                    // Give a child element <adlcp:prerequisites> to the <item> element.
                    $my_prereqs = $xmldoc->createElement('adlcp:prerequisites', $item->get_prereq_string());
                    $my_prereqs->setAttribute('type', 'aicc_script');
                    $my_item->appendChild($my_prereqs);
                    // Give a child element <adlcp:maxtimeallowed> to the <item> element - not yet supported.
                    //$xmldoc->createElement('adlcp:maxtimeallowed', '');
                    // Give a child element <adlcp:timelimitaction> to the <item> element - not yet supported.
                    //$xmldoc->createElement('adlcp:timelimitaction', '');
                    // Give a child element <adlcp:datafromlms> to the <item> element - not yet supported.
                    //$xmldoc->createElement('adlcp:datafromlms', '');
                    // Give a child element <adlcp:masteryscore> to the <item> element.
                    $my_masteryscore = $xmldoc->createElement('adlcp:masteryscore', $item->get_mastery_score());
                    $my_item->appendChild($my_masteryscore);

                    // Attach this item to the organization element or its parent if there is one.
                    if (!empty($item->parent) && $item->parent != 0) {
                        $children = $organization->childNodes;
                        for ($i = 0; $i < $children->length; $i++) {
                            $item_temp = $children->item($i);
                            if ($item_temp -> nodeName == 'item') {
                                if ($item_temp->getAttribute('identifier') == 'ITEM_'.$item->parent) {
                                    $item_temp -> appendChild($my_item);
                                }
                            }
                        }
                    } else {
                        $organization->appendChild($my_item);
                    }

                    $my_file_path = 'link_'.$item->get_id().'.html';
                    $sql = 'SELECT url, title FROM '.Database :: get_course_table(TABLE_LINK).' WHERE c_id = '.$course_id.' AND id='.$item->path;
                    $rs = Database::query($sql);
                    if ($link = Database :: fetch_array($rs)) {
                        $url = $link['url'];
                        $title = stripslashes($link['title']);
                        $links_to_create[$my_file_path] = array('title' => $title, 'url' => $url);
                        //$my_xml_file_path = api_htmlentities(api_utf8_encode($my_file_path), ENT_QUOTES, 'UTF-8');
                        $my_xml_file_path = $my_file_path;
                        $my_sub_dir = dirname($my_file_path);
                        $my_sub_dir = str_replace('\\', '/', $my_sub_dir);
                        //$my_xml_sub_dir = api_htmlentities(api_utf8_encode($my_sub_dir), ENT_QUOTES, 'UTF-8');
                        $my_xml_sub_dir = $my_sub_dir;
                        // Give a <resource> child to the <resources> element.
                        $my_resource = $xmldoc->createElement('resource');
                        $my_resource->setAttribute('identifier', 'RESOURCE_'.$item->get_id());
                        $my_resource->setAttribute('type', 'webcontent');
                        $my_resource->setAttribute('href', $my_xml_file_path);
                        // adlcp:scormtype can be either 'sco' or 'asset'.
                        $my_resource->setAttribute('adlcp:scormtype', 'asset');
                        // xml:base is the base directory to find the files declared in this resource.
                        $my_resource->setAttribute('xml:base', '');
                        // give a <file> child to the <resource> element.
                        $my_file = $xmldoc->createElement('file');
                        $my_file->setAttribute('href', $my_xml_file_path);
                        $my_resource->appendChild($my_file);
                        $resources->appendChild($my_resource);
                    }
                }
                elseif ($item->type == TOOL_QUIZ) {
                    require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.class.php';
                    $exe_id = $item->path; // Should be using ref when everything will be cleaned up in this regard.
                    $exe = new Exercise();
                    $exe->read($exe_id);
                    $my_item = $xmldoc->createElement('item');
                    $my_item->setAttribute('identifier', 'ITEM_'.$item->get_id());
                    $my_item->setAttribute('identifierref', 'RESOURCE_'.$item->get_id());
                    $my_item->setAttribute('isvisible', 'true');
                    // Give a child element <title> to the <item> element.
                    $my_title = $xmldoc->createElement('title', htmlspecialchars(api_utf8_encode($item->get_title()), ENT_QUOTES, 'UTF-8'));
                    $my_item->appendChild($my_title);
                    $my_max_score = $xmldoc->createElement('max_score', $item->get_max());
                    //$my_item->appendChild($my_max_score);
                    // Give a child element <adlcp:prerequisites> to the <item> element.
                    $my_prereqs = $xmldoc->createElement('adlcp:prerequisites', $item->get_prereq_string());
                    $my_prereqs->setAttribute('type','aicc_script');
                    $my_item->appendChild($my_prereqs);
                    // Give a child element <adlcp:masteryscore> to the <item> element.
                    $my_masteryscore = $xmldoc->createElement('adlcp:masteryscore', $item->get_mastery_score());
                    $my_item->appendChild($my_masteryscore);

                    // Attach this item to the organization element or hits parent if there is one.
                    if (!empty($item->parent) && $item->parent != 0) {
                        $children = $organization->childNodes;
                        for ($i = 0; $i < $children->length; $i++) {
                            $item_temp = $children->item($i);
                            if ($item_temp -> nodeName == 'item') {
                                if ($item_temp->getAttribute('identifier') == 'ITEM_'.$item->parent) {
                                    $item_temp -> appendChild($my_item);
                                }

                            }
                        }
                    } else {
                        $organization->appendChild($my_item);
                    }

                    // Include export scripts.
                    require_once api_get_path(SYS_CODE_PATH).'exercice/export/scorm/scorm_export.php';

                    // Get the path of the file(s) from the course directory root
                    //$my_file_path = $item->get_file_path('scorm/'.$this->path.'/');
                    $my_file_path = 'quiz_'.$item->get_id().'.html';
                    // Write the contents of the exported exercise into a (big) html file
                    // to later pack it into the exported SCORM. The file will be removed afterwards.
                    $contents = export_exercise($exe_id,true);
                    $tmp_file_path = $archive_path.$temp_dir_short.'/'.$my_file_path;
                    $res = file_put_contents($tmp_file_path, $contents);
                    if ($res === false) { error_log('Could not write into file '.$tmp_file_path.' '.__FILE__.' '.__LINE__, 0); }
                    $files_cleanup[] = $tmp_file_path;
                    //error_log($tmp_path); die();
                    //$my_xml_file_path = api_htmlentities(api_utf8_encode($my_file_path), ENT_QUOTES, 'UTF-8');
                    $my_xml_file_path = $my_file_path;
                    $my_sub_dir = dirname($my_file_path);
                    $my_sub_dir = str_replace('\\', '/', $my_sub_dir);
                    //$my_xml_sub_dir = api_htmlentities(api_utf8_encode($my_sub_dir), ENT_QUOTES, 'UTF-8');
                    $my_xml_sub_dir = $my_sub_dir;
                    // Give a <resource> child to the <resources> element.
                    $my_resource = $xmldoc->createElement('resource');
                    $my_resource->setAttribute('identifier', 'RESOURCE_'.$item->get_id());
                    $my_resource->setAttribute('type', 'webcontent');
                    $my_resource->setAttribute('href', $my_xml_file_path);
                    // adlcp:scormtype can be either 'sco' or 'asset'.
                    $my_resource->setAttribute('adlcp:scormtype', 'sco');
                    // xml:base is the base directory to find the files declared in this resource.
                    $my_resource->setAttribute('xml:base', '');
                    // Give a <file> child to the <resource> element.
                    $my_file = $xmldoc->createElement('file');
                    $my_file->setAttribute('href', $my_xml_file_path);
                    $my_resource->appendChild($my_file);

                    // Get included docs.
                    $inc_docs = $item->get_resources_from_source(null,$tmp_file_path);
                    // Dependency to other files - not yet supported.
                    $i = 1;
                    foreach ($inc_docs as $doc_info) {
                        if (count($doc_info) < 1 || empty($doc_info[0])) { continue; }
                        $my_dep = $xmldoc->createElement('resource');
                        $res_id = 'RESOURCE_'.$item->get_id().'_'.$i;
                        $my_dep->setAttribute('identifier', $res_id);
                        $my_dep->setAttribute('type', 'webcontent');
                        $my_dep->setAttribute('adlcp:scormtype', 'asset');
                        $my_dep_file = $xmldoc->createElement('file');
                        // Check type of URL.
                        //error_log(__LINE__.'Now dealing with '.$doc_info[0].' of type '.$doc_info[1].'-'.$doc_info[2], 0);
                        if ($doc_info[1] == 'remote') {
                            // Remote file. Save url as is.
                            $my_dep_file->setAttribute('href', $doc_info[0]);
                            $my_dep->setAttribute('xml:base', '');
                        } elseif ($doc_info[1] == 'local') {
                            switch ($doc_info[2]) {
                                case 'url': // Local URL - save path as url for now, don't zip file.
                                    // Save file but as local file (retrieve from URL).
                                    $abs_path = api_get_path(SYS_PATH).str_replace(api_get_path(WEB_PATH), '', $doc_info[0]);
                                    $current_dir = dirname($abs_path);
                                    $current_dir = str_replace('\\', '/', $current_dir);
                                    $file_path = realpath($abs_path);
                                    $file_path = str_replace('\\', '/', $file_path);
                                    $my_dep_file->setAttribute('href', 'document/'.$file_path);
                                    $my_dep->setAttribute('xml:base', '');
                                    if (strstr($file_path, $main_path) !== false) {
                                        // The calculated real path is really inside the chamilo root path.
                                        // Reduce file path to what's under the DocumentRoot.
                                        $file_path = substr($file_path, strlen($root_path));
                                        //echo $file_path;echo '<br /><br />';
                                        //error_log('Reduced path: '.$file_path, 0);
                                        $zip_files_abs[] = $file_path;
                                        $link_updates[$my_file_path][] = array('orig' => $doc_info[0], 'dest' => 'document/'.$file_path);
                                        $my_dep_file->setAttribute('href', 'document/'.$file_path);
                                        $my_dep->setAttribute('xml:base', '');
                                    }
                                    elseif (empty($file_path)) {
                                        /*$document_root = substr(api_get_path(SYS_PATH), 0, strpos(api_get_path(SYS_PATH),api_get_path(REL_PATH)));
                                        if (strpos($document_root,-1) == '/') {
                                            $document_root = substr(0, -1, $document_root);
                                        }*/
                                        $file_path = $_SERVER['DOCUMENT_ROOT'].$abs_path;
                                        $file_path = str_replace('//', '/', $file_path);
                                        if (file_exists($file_path)) {
                                            $file_path = substr($file_path, strlen($current_dir)); // We get the relative path.
                                            $zip_files[] = $my_sub_dir.'/'.$file_path;
                                            $link_updates[$my_file_path][] = array('orig' => $doc_info[0], 'dest' => 'document/'.$file_path);
                                            $my_dep_file->setAttribute('href', 'document/'.$file_path);
                                             $my_dep->setAttribute('xml:base', '');
                                        }
                                    }
                                    break;
                                case 'abs': // Absolute path from DocumentRoot. Save file and leave path as is in the zip.
                                    $current_dir = dirname($current_course_path.'/'.$item->get_file_path()).'/';
                                    $current_dir = str_replace('\\', '/', $current_dir);
                                    $file_path = realpath($doc_info[0]);
                                    $file_path = str_replace('\\', '/', $file_path);
                                    $my_dep_file->setAttribute('href', $file_path);
                                    $my_dep->setAttribute('xml:base', '');

                                    if (strstr($file_path,$main_path) !== false) {
                                        // The calculated real path is really inside the chamilo root path.
                                        // Reduce file path to what's under the DocumentRoot.
                                        $file_path = substr($file_path, strlen($root_path));
                                        //echo $file_path;echo '<br /><br />';
                                        //error_log('Reduced path: '.$file_path, 0);
                                        $zip_files_abs[] = $file_path;
                                        $link_updates[$my_file_path][] = array('orig' => $doc_info[0], 'dest' => $file_path);
                                        $my_dep_file->setAttribute('href', 'document/'.$file_path);
                                        $my_dep->setAttribute('xml:base', '');
                                    }
                                    elseif (empty($file_path)) {
                                        /*$document_root = substr(api_get_path(SYS_PATH), 0, strpos(api_get_path(SYS_PATH), api_get_path(REL_PATH)));
                                        if (strpos($document_root,-1) == '/') {
                                            $document_root = substr(0, -1, $document_root);
                                        }*/
                                        $file_path = $_SERVER['DOCUMENT_ROOT'].$doc_info[0];
                                        $file_path = str_replace('//', '/', $file_path);
                                        if (file_exists($file_path)) {
                                            $file_path = substr($file_path,strlen($current_dir)); // We get the relative path.
                                            $zip_files[] = $my_sub_dir.'/'.$file_path;
                                            $link_updates[$my_file_path][] = array('orig' => $doc_info[0], 'dest' => $file_path);
                                            $my_dep_file->setAttribute('href', 'document/'.$file_path);
                                             $my_dep->setAttribute('xml:base', '');
                                        }
                                    }
                                    break;
                                case 'rel': // Path relative to the current document. Save xml:base as current document's directory and save file in zip as subdir.file_path
                                    if (substr($doc_info[0], 0, 2) == '..') {
                                        // Relative path going up.
                                        $current_dir = dirname($current_course_path.'/'.$item->get_file_path()).'/';
                                        $current_dir = str_replace('\\', '/', $current_dir);
                                        $file_path = realpath($current_dir.$doc_info[0]);
                                        $file_path = str_replace('\\', '/', $file_path);
                                        //error_log($file_path.' <-> '.$main_path, 0);
                                        if (strstr($file_path, $main_path) !== false) {
                                            // The calculated real path is really inside Chamilo's root path.
                                            // Reduce file path to what's under the DocumentRoot.

                                            $file_path = substr($file_path, strlen($root_path));
                                            $file_path_dest = $file_path;

                                           // File path is courses/CHAMILO/document/....
                                           $info_file_path = explode('/', $file_path);
                                           if ($info_file_path[0] == 'courses') { // Add character "/" in file path.
                                               $file_path_dest = 'document/'.$file_path;
                                           }

                                            //error_log('Reduced path: '.$file_path, 0);
                                            $zip_files_abs[] = $file_path;

                                            $link_updates[$my_file_path][] = array('orig' => $doc_info[0], 'dest' => $file_path_dest);
                                            $my_dep_file->setAttribute('href', 'document/'.$file_path);
                                             $my_dep->setAttribute('xml:base', '');
                                        }
                                    } else {
                                        $zip_files[] = $my_sub_dir.'/'.$doc_info[0];
                                        $my_dep_file->setAttribute('href', $doc_info[0]);
                                        $my_dep->setAttribute('xml:base', $my_xml_sub_dir);
                                    }

                                    break;
                                default:
                                    $my_dep_file->setAttribute('href', $doc_info[0]); // ../../courses/
                                    $my_dep->setAttribute('xml:base', '');
                                    break;
                            }
                        }
                        $my_dep->appendChild($my_dep_file);
                        $resources->appendChild($my_dep);
                        $dependency = $xmldoc->createElement('dependency');
                        $dependency->setAttribute('identifierref', $res_id);
                        $my_resource->appendChild($dependency);
                        $i++;
                    }
                    $resources->appendChild($my_resource);
                    $zip_files[] = $my_file_path;

                } else {

                    // Get the path of the file(s) from the course directory root
                    $my_file_path = 'non_exportable.html';
                    //$my_xml_file_path = api_htmlentities(api_utf8_encode($my_file_path), ENT_COMPAT, 'UTF-8');
                    $my_xml_file_path = $my_file_path;
                    $my_sub_dir = dirname($my_file_path);
                    $my_sub_dir = str_replace('\\', '/', $my_sub_dir);
                    //$my_xml_sub_dir = api_htmlentities(api_utf8_encode($my_sub_dir), ENT_COMPAT, 'UTF-8');
                    $my_xml_sub_dir = $my_sub_dir;
                    // Give a <resource> child to the <resources> element.
                    $my_resource = $xmldoc->createElement('resource');
                    $my_resource->setAttribute('identifier', 'RESOURCE_'.$item->get_id());
                    $my_resource->setAttribute('type', 'webcontent');
                    $my_resource->setAttribute('href', 'document/'.$my_xml_file_path);
                    // adlcp:scormtype can be either 'sco' or 'asset'.
                    $my_resource->setAttribute('adlcp:scormtype', 'asset');
                    // xml:base is the base directory to find the files declared in this resource.
                    $my_resource->setAttribute('xml:base', '');
                    // Give a <file> child to the <resource> element.
                    $my_file = $xmldoc->createElement('file');
                    $my_file->setAttribute('href', 'document/'.$my_xml_file_path);
                    $my_resource->appendChild($my_file);
                    $resources->appendChild($my_resource);

                }
            }
        }
        $organizations->appendChild($organization);
        $root->appendChild($organizations);
        $root->appendChild($resources);
        $xmldoc->appendChild($root);
        // TODO: Add a readme file here, with a short description and a link to the Reload player
        // then add the file to the zip, then destroy the file (this is done automatically).
        // http://www.reload.ac.uk/scormplayer.html - once done, don't forget to close FS#138

        //error_log(print_r($zip_files,true), 0);
        foreach ($zip_files as $file_path) {
            if (empty($file_path)) { continue; }
            //error_log(__LINE__.'getting document from '.$sys_course_path.$_course['path'].'/'.$file_path.' removing '.$sys_course_path.$_course['path'].'/',0);
            $dest_file = $archive_path.$temp_dir_short.'/'.$file_path;
            $this->create_path($dest_file);
            //error_log('copy '.api_get_path(SYS_COURSE_PATH).$_course['path'].'/'.$file_path.' to '.api_get_path(SYS_ARCHIVE_PATH).$temp_dir_short.'/'.$file_path,0);
            //echo $main_path.$file_path.'<br />';
            @copy($sys_course_path.$_course['path'].'/'.$file_path, $dest_file);
            // Check if the file needs a link update.
            if (in_array($file_path, array_keys($link_updates))) {
                $string = file_get_contents($dest_file);
                unlink($dest_file);
                foreach ($link_updates[$file_path] as $old_new) {
                    //error_log('Replacing '.$old_new['orig'].' by '.$old_new['dest'].' in '.$file_path, 0);
                    // This is an ugly hack that allows .flv files to be found by the flv player that
                    // will be added in document/main/inc/lib/flv_player/flv_player.swf and that needs
                    // to find the flv to play in document/main/, so we replace main/ in the flv path by
                    // ../../.. to return from inc/lib/flv_player to the document/main path.
                    if (substr($old_new['dest'], -3) == 'flv' && substr($old_new['dest'], 0, 5) == 'main/') {
                        $old_new['dest'] = str_replace('main/', '../../../', $old_new['dest']);
                    }
                    elseif (substr($old_new['dest'], -3) == 'flv' && substr($old_new['dest'], 0, 6) == 'video/') {
                        $old_new['dest'] = str_replace('video/', '../../../../video/', $old_new['dest']);
                    }
                    $string = str_replace($old_new['orig'], $old_new['dest'], $string);
                }
                file_put_contents($dest_file, $string);
            }
        }
        foreach ($zip_files_abs as $file_path) {
            if (empty($file_path)) { continue; }
            //error_log(__LINE__.'checking existence of '.$main_path.$file_path.'', 0);
            if (!is_file($main_path.$file_path) || !is_readable($main_path.$file_path)) { continue; }
            //error_log(__LINE__.'getting document from '.$main_path.$file_path.' removing '.api_get_path(SYS_COURSE_PATH).$_course['path'].'/', 0);
            $dest_file = $archive_path.$temp_dir_short.'/document/'.$file_path;
            $this->create_path($dest_file);
            //error_log('Created path '.api_get_path(SYS_ARCHIVE_PATH).$temp_dir_short.'/document/'.$file_path, 0);
            //error_log('copy '.api_get_path(SYS_COURSE_PATH).$_course['path'].'/'.$file_path.' to '.api_get_path(SYS_ARCHIVE_PATH).$temp_dir_short.'/'.$file_path, 0);
            //echo $main_path.$file_path.' - '.$dest_file.'<br />';

            copy($main_path.$file_path, $dest_file);
            // Check if the file needs a link update.
            if (in_array($file_path, array_keys($link_updates))) {
                $string = file_get_contents($dest_file);
                unlink($dest_file);
                foreach ($link_updates[$file_path] as $old_new) {
                    //error_log('Replacing '.$old_new['orig'].' by '.$old_new['dest'].' in '.$file_path, 0);
                    // This is an ugly hack that allows .flv files to be found by the flv player that
                    // will be added in document/main/inc/lib/flv_player/flv_player.swf and that needs
                    // to find the flv to play in document/main/, so we replace main/ in the flv path by
                    // ../../.. to return from inc/lib/flv_player to the document/main path.
                    if (substr($old_new['dest'], -3) == 'flv' && substr($old_new['dest'], 0, 5) == 'main/') {
                        $old_new['dest'] = str_replace('main/', '../../../', $old_new['dest']);
                    }
                    $string = str_replace($old_new['orig'], $old_new['dest'], $string);
                }
                file_put_contents($dest_file, $string);
            }
        }
        if (is_array($links_to_create)) {
            foreach ($links_to_create as $file => $link) {
               $file_content = '<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.api_get_language_isocode().'" lang="'.api_get_language_isocode().'">
    <head>
        <title>'.$link['title'].'</title>
        <meta http-equiv="Content-Type" content="text/html; charset='.api_get_system_encoding().'" />
    </head>
    <body dir="'.api_get_text_direction().'">
        <div style="text-align:center"><a href="'.$link['url'].'">'.$link['title'].'</a></div>
    </body>
</html>';
                file_put_contents($archive_path.$temp_dir_short.'/'.$file, $file_content);
            }
        }
        // Add non exportable message explanation.
        $lang_not_exportable = get_lang('ThisItemIsNotExportable');
        $file_content = '<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.api_get_language_isocode().'" lang="'.api_get_language_isocode().'">
    <head>
        <title>'.$lang_not_exportable.'</title>
        <meta http-equiv="Content-Type" content="text/html; charset='.api_get_system_encoding().'" />
    </head>
    <body dir="'.api_get_text_direction().'">
';

        $file_content .=
<<<EOD
        <style>
            .error-message {
                font-family: arial, verdana, helvetica, sans-serif;
                border-width: 1px;
                border-style: solid;
                left: 50%;
                margin: 10px auto;
                min-height: 30px;
                padding: 5px;
                right: 50%;
                width: 500px;
                background-color: #FFD1D1;
                border-color: #FF0000;
                color: #000;
            }
        </style>
    <body>
        <div class="error-message">
            $lang_not_exportable
        </div>
    </body>
</html>
EOD;
        if (!is_dir($archive_path.$temp_dir_short.'/document')) {
            @mkdir($archive_path.$temp_dir_short.'/document', api_get_permissions_for_new_directories());
        }
        file_put_contents($archive_path.$temp_dir_short.'/document/non_exportable.html', $file_content);

        // Add the extra files that go along with a SCORM package.
        $main_code_path = api_get_path(SYS_CODE_PATH).'newscorm/packaging/';
        $extra_files = scandir($main_code_path);
        foreach ($extra_files as $extra_file) {
            if (strpos($extra_file, '.') === 0)
                continue;
            else {
                $dest_file = $archive_path . $temp_dir_short . '/' . $extra_file;
                $this->create_path($dest_file);
                copy($main_code_path.$extra_file, $dest_file);
            }
        }

        // Finalize the imsmanifest structure, add to the zip, then return the zip.

        $manifest = @$xmldoc->saveXML();
        $manifest = api_utf8_decode_xml($manifest); // The manifest gets the system encoding now.
        file_put_contents($archive_path.'/'.$temp_dir_short.'/imsmanifest.xml', $manifest);
        $zip_folder->add($archive_path.'/'.$temp_dir_short, PCLZIP_OPT_REMOVE_PATH, $archive_path.'/'.$temp_dir_short.'/');

        // Clean possible temporary files.
        foreach ($files_cleanup as $file) {
            $res = unlink($file);
            if ($res === false) { error_log('Could not delete temp file '.$file.' '.__FILE__.' '.__LINE__, 0); }
        }
        // Send file to client.
        //$name = 'scorm_export_'.$this->lp_id.'.zip';
        require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
        $name = replace_dangerous_char($this->get_name()).'.zip';
        DocumentManager::file_send_for_download($temp_zip_file, true, $name);
    }

    public function scorm_export_to_pdf($lp_id) {
        $lp_id = intval($lp_id);
        $files_to_export = array();
        $course_data = api_get_course_info($this->cc); 
        if (!empty($course_data)) {
            $scorm_path = api_get_path(SYS_COURSE_PATH).$course_data['path'].'/scorm/'.$this->path;
            require_once api_get_path(LIBRARY_PATH).'document.lib.php';
            $list = $this->get_flat_ordered_items_list($lp_id);
            
            foreach($list as $item_id) {
                $item = $this->items[$item_id];
                //Getting documents from a LP with chamilo documents
                
                switch ($item->type) {
                    case 'document':
                        $file_data = DocumentManager::get_document_data_by_id($item->path, $this->cc);
                        $file_path = api_get_path(SYS_COURSE_PATH).$course_data['path'].'/document'.$file_data['path'];                        
                        if (file_exists($file_path)) {
                            $files_to_export[] = array('title'=>$item->get_title(),'path'=>$file_path);
                        }
                        break;
                    case 'sco':
                        $file_path = $scorm_path.'/'.$item->path;
                        if (file_exists($file_path)) {
                            $files_to_export[] = array('title'=>$item->get_title(),'path'=>$file_path);
                        }
                        break;
                    case 'dokeos_chapter':
                    case 'dir':
                    case 'chapter':
                        $files_to_export[] = array('title'=>$item->get_title(),'path'=>null);
                        break;
                }
            }            
            require_once api_get_path(LIBRARY_PATH).'pdf.lib.php';
            $pdf = new PDF();
            $result = $pdf->html_to_pdf($files_to_export, $this->name, $this->cc, true);
            return $result;
        }
        return false;
    }

    /**
     * Temp function to be moved in main_api or the best place around for this. Creates a file path
     * if it doesn't exist
     */
    public function create_path($path) {
        $path_bits = split('/', dirname($path));

        // IS_WINDOWS_OS has been defined in main_api.lib.php
        $path_built = IS_WINDOWS_OS ? '' : '/';

        foreach ($path_bits as $bit) {
            if (!empty ($bit)) {
                $new_path = $path_built . $bit;
                if (is_dir($new_path)) {
                    $path_built = $new_path . '/';
                } else {
                    mkdir($new_path, api_get_permissions_for_new_directories());
                    $path_built = $new_path . '/';
                }
            }
        }
    }

    /**
     * Delete the image relative to this learning path. No parameter. Only works on instanciated object.
     * @return	boolean	The results of the unlink function, or false if there was no image to start with
     */
    public function delete_lp_image() {
        $img = $this->get_preview_image();
        if ($img != '') {
            $del_file = api_get_path(SYS_COURSE_PATH) . api_get_course_path() . '/upload/learning_path/images/' . $img;
            $this->set_preview_image('');
            return @ unlink($del_file);
        } else {
            return false;
        }

    }

    /**
     * Uploads an author image to the upload/learning_path/images directory
     * @param	array	The image array, coming from the $_FILES superglobal
     * @return	boolean	True on success, false on error
     */
    public function upload_image($image_array) {
        $image_moved = false;
        if (!empty ($image_array['name'])) {
            $upload_ok = process_uploaded_file($image_array);
            $has_attachment = true;
        } else {
            $image_moved = true;
        }

        if ($upload_ok) {
            if ($has_attachment) {
                $courseDir = api_get_course_path() . '/upload/learning_path/images';
                $sys_course_path = api_get_path(SYS_COURSE_PATH);
                $updir = $sys_course_path . $courseDir;
                // Try to add an extension to the file if it hasn't one.
                $new_file_name = add_ext_on_mime(stripslashes($image_array['name']), $image_array['type']);

                if (!filter_extension($new_file_name)) {
                    //Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
                    $image_moved = false;
                } else {
                    $file_extension = explode('.', $image_array['name']);
                    $file_extension = strtolower($file_extension[sizeof($file_extension) - 1]);
                    $new_file_name = uniqid('') . '.' . $file_extension;
                    $new_path = $updir . '/' . $new_file_name;

                    // Resize the image.                    
                    $temp = new Image($image_array['tmp_name']);
                    $picture_infos = $temp->get_image_info();
                    if ($picture_infos['width'] > 104) {
                        $thumbwidth = 104;
                    } else {
                        $thumbwidth = $picture_infos['width'];
                    }
                    if ($picture_infos['height'] > 96) {
                        $new_height = 96;
                    } else {
                        $new_height = $picture_infos['height'];
                    }                 

                    $temp->resize($thumbwidth, $new_height, 0);                    
                    $result = $temp->send_image($new_path);
                        
                    // Storing the image filename.
                    if ($result) {
                        $image_moved = true;
                        $this->set_preview_image($new_file_name);
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function set_autolunch($lp_id, $status) {
        $course_id = api_get_course_int_id();
        $lp_id   = intval($lp_id);
        $status  = intval($status);
        $lp_table = Database::get_course_table(TABLE_LP_MAIN);

        //Setting everything to autolunch = 0
        $attributes['autolunch'] = 0;
        $where = array('session_id = ? AND c_id = ? '=> array(api_get_session_id(), $course_id));
        Database::update($lp_table, $attributes, $where);
        if ($status == 1) {
            //Setting my lp_id to autolunch = 1
            $attributes['autolunch'] = 1;
            $where = array('id = ? AND session_id = ? AND c_id = ?'=> array($lp_id, api_get_session_id(), $course_id));
            Database::update($lp_table, $attributes, $where );
        }
    }
    
    
    /**
    * Gets previous_item_id for the next element of the lp_item table
    * @author Isaac flores paz
    * @return	integer	Previous item ID
    */
    function select_previous_item_id() {
        $course_id = api_get_course_int_id();
    	if ($this->debug > 0) {
    		error_log('New LP - In learnpath::select_previous_item_id()', 0);
    	}
    	$table_lp_item = Database::get_course_table(TABLE_LP_ITEM);
    
    	// Get the max order of the items
    	$sql_max_order = "SELECT max(display_order) AS display_order FROM $table_lp_item  WHERE c_id = $course_id AND lp_id = '" . $this->lp_id . "'";
    	$rs_max_order = Database::query($sql_max_order);
    	$row_max_order = Database::fetch_object($rs_max_order);
    	$max_order = $row_max_order->display_order;
    	// Get the previous item ID
    	$sql_max = "SELECT id as previous FROM $table_lp_item WHERE c_id = $course_id AND lp_id = '" . $this->lp_id . "' AND display_order = '".$max_order."' ";
    	$rs_max = Database::query($sql_max, __FILE__, __LINE__);
    	$row_max = Database::fetch_object($rs_max);
    
    	// Return the previous item ID
    	return $row_max->previous;
    }
    
    function copy() {
        $main_path = api_get_path(SYS_CODE_PATH);
        require_once $main_path.'coursecopy/classes/CourseBuilder.class.php';
        require_once $main_path.'coursecopy/classes/CourseArchiver.class.php';
        require_once $main_path.'coursecopy/classes/CourseRestorer.class.php';
        require_once $main_path.'coursecopy/classes/CourseSelectForm.class.php';
        
        //Course builder
        $cb = new CourseBuilder();
        
        //Setting tools that will be copied
        $cb->set_tools_to_build(array('learnpaths'));
        
        //Setting elements that will be copied
        $cb->set_tools_specific_id_list(array('learnpaths' => array($this->lp_id)));
        
        $course = $cb->build();
        
        //Course restorer
        $course_restorer = new CourseRestorer($course);        
        $course_restorer->set_add_text_in_items(true);
        $course_restorer->set_tool_copy_settings(array('learnpaths' => array('reset_dates' => true)));        
        $course_restorer->restore(api_get_course_id(), api_get_session_id(), false, false);        
    }
    
}

if (!function_exists('trim_value')) {
    function trim_value(& $value) {
        $value = trim($value);
    }
}

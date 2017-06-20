<?php
/* For licensing terms, see /license.txt */

/**
 * Class learnpathItem
 * lp_item defines items belonging to a learnpath. Each item has a name,
 * a score, a use time and additional information that enables tracking a user's
 * progress in a learning path
 * @package chamilo.learnpath
 * @author  Yannick Warnier <ywarnier@beeznest.org>
 */
class learnpathItem
{
    const DEBUG = 0; // Logging parameter.
    public $attempt_id; // Also called "objectives" SCORM-wise.
    public $audio; // The path to an audio file (stored in document/audio/).
    public $children = array(); // Contains the ids of children items.
    public $condition; // If this item has a special condition embedded.
    public $current_score;
    public $current_start_time;
    public $current_stop_time;
    public $current_data = '';
    public $db_id;
    public $db_item_view_id = '';
    public $description = '';
    public $file;
    /**
     * At the moment, interactions are just an array of arrays with a structure
     * of 8 text fields: id(0), type(1), time(2), weighting(3),
     * correct_responses(4), student_response(5), result(6), latency(7)
     */
    public $interactions = array();
    public $interactions_count = 0;
    public $objectives = array();
    public $objectives_count = 0;
    public $launch_data = '';
    public $lesson_location = '';
    public $level = 0;
    public $core_exit = '';
    //var $location; // Only set this for SCORM?
    public $lp_id;
    public $max_score;
    public $mastery_score;
    public $min_score;
    public $max_time_allowed = '';
    public $name;
    public $next;
    public $parent;
    public $path; // In some cases the exo_id = exercise_id in courseDb exercices table.
    public $possible_status = array(
        'not attempted',
        'incomplete',
        'completed',
        'passed',
        'failed',
        'browsed'
    );
    public $prereq_string = '';
    public $prereq_alert = '';
    public $prereqs = array();
    public $previous;
    public $prevent_reinit = 1; // 0 =  multiple attempts   1 = one attempt
    public $seriousgame_mode;
    public $ref;
    public $save_on_close = true;
    public $search_did = null;
    public $status;
    public $title;
    /**
     * Type attribute can contain one of
     * link|student_publication|dir|quiz|document|forum|thread
     */
    public $type;
    public $view_id;
    //var used if absolute session time mode is used
    private $last_scorm_session_time = 0;
    private $prerequisiteMaxScore;
    private $prerequisiteMinScore;

    /**
     * Prepares the learning path item for later launch.
     * Don't forget to use set_lp_view() if applicable after creating the item.
     * Setting an lp_view will finalise the item_view data collection
     * @param   integer $id Learning path item ID
     * @param   integer $user_id User ID
     * @param   integer $course_id Course int id
     * @param   null|array  $item_content An array with the contents of the item
     */
    public function __construct(
        $id,
        $user_id = 0,
        $course_id = 0,
        $item_content = null
    ) {
        $items_table = Database::get_course_table(TABLE_LP_ITEM);

        // Get items table.
        if (!isset($user_id)) {
            $user_id = api_get_user_id();
        }
        if (self::DEBUG > 0) {
            error_log(
                "learnpathItem constructor: id: $id user_id: ".
                "$user_id course_id: $course_id item_content: $item_content",
                0
            );
        }
        $id = intval($id);
        if (empty($item_content)) {
            if (empty($course_id)) {
                $course_id = api_get_course_int_id();
            } else {
                $course_id = intval($course_id);
            }
            $sql = "SELECT * FROM $items_table
                    WHERE c_id = $course_id AND id = $id";
            $res = Database::query($sql);
            if (Database::num_rows($res) < 1) {
                $this->error = 'Could not find given learnpath item in learnpath_item table';
            }
            $row = Database::fetch_array($res);
        } else {
            $row = $item_content;
        }

        $this->lp_id = $row['lp_id'];
        $this->max_score = $row['max_score'];
        $this->min_score = $row['min_score'];
        $this->name = $row['title'];
        $this->type = $row['item_type'];
        $this->ref = $row['ref'];
        $this->title = $row['title'];
        $this->description = $row['description'];
        $this->path = $row['path'];
        $this->mastery_score = $row['mastery_score'];
        $this->parent = $row['parent_item_id'];
        $this->next = $row['next_item_id'];
        $this->previous = $row['previous_item_id'];
        $this->display_order = $row['display_order'];
        $this->prereq_string = $row['prerequisite'];
        $this->max_time_allowed = $row['max_time_allowed'];
        $this->setPrerequisiteMaxScore($row['prerequisite_max_score']);
        $this->setPrerequisiteMinScore($row['prerequisite_min_score']);

        if (isset($row['launch_data'])) {
            $this->launch_data = $row['launch_data'];
        }
        $this->save_on_close = true;
        $this->db_id = $id;

        // Load children list
        if (!empty($this->lp_id)) {
            $sql = "SELECT id FROM $items_table
                    WHERE
                        c_id = $course_id AND
                        lp_id = ".$this->lp_id." AND
                        parent_item_id = $id";
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                while ($row = Database::fetch_assoc($res)) {
                    $this->children[] = $row['id'];
                }
            }
        }

        //$this->seriousgame_mode = $this->get_seriousgame_mode();
        $this->seriousgame_mode = 0;

        // Get search_did.
        if (api_get_setting('search_enabled') == 'true') {
            $tbl_se_ref = Database::get_main_table(
                TABLE_MAIN_SEARCH_ENGINE_REF
            );
            $sql = 'SELECT *
                FROM %s
                WHERE course_code=\'%s\'
                    AND tool_id=\'%s\'
                    AND ref_id_high_level=%s
                    AND ref_id_second_level=%d
                LIMIT 1';
            // TODO: Verify if it's possible to assume the actual course instead
            // of getting it from db.
            $sql = sprintf(
                $sql,
                $tbl_se_ref,
                api_get_course_id(),
                TOOL_LEARNPATH,
                $this->lp_id,
                $id
            );
            if (self::DEBUG > 0) {
                error_log($sql);
            };
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                $se_ref = Database::fetch_array($res);
                $this->search_did = (int) $se_ref['search_did'];
            }
        }
        $this->audio = $row['audio'];
        if (self::DEBUG > 0) {
            error_log(
                'New LP - End of learnpathItem constructor for item '.$id,
                0
            );
        }
    }

    /**
     * Adds a child to the current item
     * @param   int $item   The child item ID
     */
    public function add_child($item)
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::add_child()', 0);
        }
        if (!empty($item)) {
            // Do not check in DB as we expect the call to come from the
            // learnpath class which should be aware of any fake.
            $this->children[] = $item;
        }
    }

    /**
     * Adds an interaction to the current item
     * @param    int   $index  Index (order ID) of the interaction inside this item
     * @param    array $params Array of parameters:
     * id(0), type(1), time(2), weighting(3), correct_responses(4),
     * student_response(5), result(6), latency(7)
     * @return   void
     */
    public function add_interaction($index, $params)
    {
        $this->interactions[$index] = $params;
        // Take the current maximum index to generate the interactions_count.
        if (($index + 1) > $this->interactions_count) {
            $this->interactions_count = $index + 1;
        }
        /*
        if (is_array($this->interactions[$index]) && count($this->interactions[$index]) > 0) {
            $this->interactions[$index] = $params;
            return false;
        } else {
            if (count($params)==8) {
            // We rely on the calling script to provide parameters in the right order.
                $this->interactions[$index] = $params;
                return true;
            } else {
                return false;
            }
        }
        */
    }

    /**
     * Adds an objective to the current item
     * @param    array    Array of parameters:
     * id(0), status(1), score_raw(2), score_max(3), score_min(4)
     * @return    void
     */
    public function add_objective($index, $params)
    {
        if (empty($params[0])) {
            return null;
        }
        $this->objectives[$index] = $params;
        // Take the current maximum index to generate the objectives_count.
        if ((count($this->objectives) + 1) > $this->objectives_count) {
            $this->objectives_count = (count($this->objectives) + 1);
        }
    }

    /**
     * Closes/stops the item viewing. Finalises runtime values.
     * If required, save to DB.
     * @return    boolean    True on success, false otherwise
     */
    public function close()
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::close()', 0);
        }
        $this->current_stop_time = time();
        $type = $this->get_type();
        if ($type != 'sco') {
            if ($type == TOOL_QUIZ || $type == TOOL_HOTPOTATOES) {
                $this->get_status(
                    true,
                    true
                ); // Update status (second option forces the update).
            } else {
                $this->status = $this->possible_status[2];
            }
        }
        if ($this->save_on_close) {
            $this->save();
        }

        return true;
    }

    /**
     * Deletes all traces of this item in the database
     * @return    boolean    true. Doesn't check for errors yet.
     */
    public function delete()
    {
        if (self::DEBUG > 0) {
            error_log('learnpath_item::delete() for item '.$this->db_id, 0);
        }
        $lp_item_view = Database::get_course_table(TABLE_LP_ITEM_VIEW);
        $lp_item = Database::get_course_table(TABLE_LP_ITEM);

        $course_id = api_get_course_int_id();

        $sql = "DELETE FROM $lp_item_view
                WHERE c_id = $course_id AND lp_item_id = ".$this->db_id;
        if (self::DEBUG > 0) {
            error_log('Deleting from lp_item_view: '.$sql, 0);
        }
        Database::query($sql);

        $sql = "SELECT * FROM $lp_item
                WHERE c_id = $course_id AND id = ".$this->db_id;
        $res_sel = Database::query($sql);
        if (Database::num_rows($res_sel) < 1) {
            return false;
        }

        $sql = "DELETE FROM $lp_item
                WHERE c_id = $course_id AND id = ".$this->db_id;
        Database::query($sql);
        if (self::DEBUG > 0) {
            error_log('Deleting from lp_item: '.$sql);
        }

        if (api_get_setting('search_enabled') == 'true') {
            if (!is_null($this->search_did)) {
                require_once api_get_path(LIBRARY_PATH).'search/ChamiloIndexer.class.php';
                $di = new ChamiloIndexer();
                $di->remove_document($this->search_did);
            }
        }

        return true;
    }

    /**
     * Drops a child from the children array
     * @param    string $item index of child item to drop
     * @return    void
     */
    public function drop_child($item)
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::drop_child()', 0);
        }
        if (!empty($item)) {
            foreach ($this->children as $index => $child) {
                if ($child == $item) {
                    $this->children[$index] = null;
                }
            }
        }
    }

    /**
     * Gets the current attempt_id for this user on this item
     * @return int attempt_id for this item view by this user or 1 if none defined
     */
    public function get_attempt_id()
    {
        if (self::DEBUG > 0) {
            error_log(
                'learnpathItem::get_attempt_id() on item '.$this->db_id,
                0
            );
        }
        $res = 1;
        if (!empty($this->attempt_id)) {
            $res = intval($this->attempt_id);
        }
        if (self::DEBUG > 0) {
            error_log(
                'New LP - End of learnpathItem::get_attempt_id() on item '.
                $this->db_id.' - Returning '.$res,
                0
            );
        }

        return $res;
    }

    /**
     * Gets a list of the item's children
     * @return    array    Array of children items IDs
     */
    public function get_children()
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::get_children()', 0);
        }
        $list = array();
        foreach ($this->children as $child) {
            if (!empty($child)) {
                $list[] = $child;
            }
        }

        return $list;
    }

    /**
     * Gets the core_exit value from the database
     */
    public function get_core_exit()
    {
        return $this->core_exit;
    }

    /**
     * Gets the credit information (rather scorm-stuff) based on current status
     * and reinit autorization. Credit tells the sco(content) if Chamilo will
     * record the data it is sent (credit) or not (no-credit)
     * @return    string    'credit' or 'no-credit'. Defaults to 'credit'
     * Because if we don't know enough about this item, it's probably because
     * it was never used before.
     */
    public function get_credit()
    {
        if (self::DEBUG > 1) {
            error_log('learnpathItem::get_credit()', 0);
        }
        $credit = 'credit';
        // Now check the value of prevent_reinit (if it's 0, return credit as
        // the default was).
        // If prevent_reinit == 1 (or more).
        if ($this->get_prevent_reinit() != 0) {
            // If status is not attempted or incomplete, credit anyway.
            // Otherwise:
            // Check the status in the database rather than in the object, as
            // checking in the object would always return "no-credit" when we
            // want to set it to completed.
            $status = $this->get_status(true);
            if (self::DEBUG > 2) {
                error_log(
                    'learnpathItem::get_credit() - get_prevent_reinit!=0 and '.
                    'status is '.$status,
                    0
                );
            }
            //0=not attempted - 1 = incomplete
            if ($status != $this->possible_status[0] &&
                $status != $this->possible_status[1]
            ) {
                $credit = 'no-credit';
            }
        }
        if (self::DEBUG > 1) {
            error_log("learnpathItem::get_credit() returns: $credit");
        }
        return $credit;
    }

    /**
     * Gets the current start time property
     * @return    integer    Current start time, or current time if none
     */
    public function get_current_start_time()
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::get_current_start_time()', 0);
        }
        if (empty($this->current_start_time)) {
            return time();
        } else {
            return $this->current_start_time;
        }
    }

    /**
     * Gets the item's description
     * @return    string    Description
     */
    public function get_description()
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::get_description()', 0);
        }
        if (empty($this->description)) {
            return '';
        }
        return $this->description;
    }

    /**
     * Gets the file path from the course's root directory, no matter what
     * tool it is from.
     * @param string  $path_to_scorm_dir
     * @return string The file path, or an empty string if there is no file
     * attached, or '-1' if the file must be replaced by an error page
     */
    public function get_file_path($path_to_scorm_dir = '')
    {
        $course_id = api_get_course_int_id();
        if (self::DEBUG > 0) {
            error_log('learnpathItem::get_file_path()', 0);
        }
        $path = $this->get_path();
        $type = $this->get_type();
        if (empty($path)) {
            if ($type == 'dir') {
                return '';
            } else {
                return '-1';
            }
        } elseif ($path == strval(intval($path))) {
            // The path is numeric, so it is a reference to a Chamilo object.
            switch ($type) {
                case 'dir':
                    return '';
                case TOOL_DOCUMENT:
                    $table_doc = Database::get_course_table(TABLE_DOCUMENT);
                    $sql = 'SELECT path
                            FROM ' . $table_doc.'
                            WHERE
                                c_id = ' . $course_id.' AND
                                id = ' . $path;
                    $res = Database::query($sql);
                    $row = Database::fetch_array($res);
                    $real_path = 'document'.$row['path'];
                    return $real_path;
                case TOOL_STUDENTPUBLICATION:
                case TOOL_QUIZ:
                case TOOL_FORUM:
                case TOOL_THREAD:
                case TOOL_LINK:
                default:
                    return '-1';
            }
        } else {
            if (!empty($path_to_scorm_dir)) {
                $path = $path_to_scorm_dir.$path;
            }
            return $path;
        }
    }

    /**
     * Gets the DB ID
     * @return    integer Database ID for the current item
     */
    public function get_id()
    {
        if (self::DEBUG > 1) {
            error_log('learnpathItem::get_id()', 0);
        }
        if (!empty($this->db_id)) {
            return $this->db_id;
        }
        // TODO: Check this return value is valid for children classes (SCORM?).
        return 0;
    }

    /**
     * Loads the interactions into the item object, from the database.
     * If object interactions exist, they will be overwritten by this function,
     * using the database elements only.
     * @return void Directly sets the interactions attribute in memory
     */
    public function load_interactions()
    {
        $this->interactions = array();
        $course_id = api_get_course_int_id();
        $tbl = Database::get_course_table(TABLE_LP_ITEM_VIEW);
        $sql = "SELECT id FROM $tbl
                WHERE
                    c_id = $course_id AND
                    lp_item_id = ".$this->db_id." AND
                    lp_view_id = " . $this->view_id." AND
                    view_count = " . $this->attempt_id;
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res);
            $lp_iv_id = $row[0];
            $iva_table = Database::get_course_table(TABLE_LP_IV_INTERACTION);
            $sql = "SELECT * FROM $iva_table
                    WHERE c_id = $course_id AND lp_iv_id = $lp_iv_id ";
            $res_sql = Database::query($sql);
            while ($row = Database::fetch_array($res_sql)) {
                $this->interactions[$row['interaction_id']] = array(
                    $row['interaction_id'],
                    $row['interaction_type'],
                    $row['weighting'],
                    $row['completion_time'],
                    $row['correct_responses'],
                    $row['student_responses'],
                    $row['result'],
                    $row['latency']
                );
            }
        }
    }

    /**
     * Gets the current count of interactions recorded in the database
     * @param   bool $checkdb Whether to count from database or not (defaults to no)
     * @return  int    The current number of interactions recorder
     */
    public function get_interactions_count($checkdb = false)
    {
        if (self::DEBUG > 1) {
            error_log('learnpathItem::get_interactions_count()', 0);
        }
        $return = 0;
        $course_id = api_get_course_int_id();

        if ($checkdb) {
            $tbl = Database::get_course_table(TABLE_LP_ITEM_VIEW);
            $sql = "SELECT id FROM $tbl
                    WHERE
                        c_id = $course_id AND
                        lp_item_id = ".$this->db_id." AND
                        lp_view_id = " . $this->view_id." AND
                        view_count = " . $this->get_attempt_id();
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                $row = Database::fetch_array($res);
                $lp_iv_id = $row[0];
                $iva_table = Database::get_course_table(
                    TABLE_LP_IV_INTERACTION
                );
                $sql = "SELECT count(id) as mycount
                        FROM $iva_table
                        WHERE c_id = $course_id AND lp_iv_id = $lp_iv_id ";
                $res_sql = Database::query($sql);
                if (Database::num_rows($res_sql) > 0) {
                    $row = Database::fetch_array($res_sql);
                    $return = $row['mycount'];
                }
            }
        } else {
            if (!empty($this->interactions_count)) {
                $return = $this->interactions_count;
            }
        }
        return $return;
    }

    /**
     * Gets the JavaScript array content to fill the interactions array.
     * @param  bool $checkdb  Whether to check directly into the database (default no)
     * @return string An empty string if no interaction, a JS array definition otherwise
     */
    public function get_interactions_js_array($checkdb = false)
    {
        $return = '';
        if ($checkdb) {
            $this->load_interactions(true);
        }
        foreach ($this->interactions as $id => $in) {
            $return .= "[
                '$id',
                '".$in[1]."',
                '" . $in[2]."',
                '" . $in[3]."',
                '" . $in[4]."',
                '" . $in[5]."',
                '" . $in[6]."',
                '" . $in[7]."'],";
        }
        if (!empty($return)) {
            $return = substr($return, 0, -1);
        }
        return $return;
    }

    /**
     * Gets the current count of objectives recorded in the database
     * @return    int    The current number of objectives recorder
     */
    public function get_objectives_count()
    {
        if (self::DEBUG > 1) {
            error_log('learnpathItem::get_objectives_count()', 0);
        }
        $res = 0;
        if (!empty($this->objectives_count)) {
            $res = $this->objectives_count;
        }
        return $res;
    }

    /**
     * Gets the launch_data field found in imsmanifests (this is SCORM- or
     * AICC-related, really)
     * @return    string    Launch data as found in imsmanifest and stored in
     *                      Chamilo (read only). Defaults to ''.
     */
    public function get_launch_data()
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::get_launch_data()', 0);
        }
        if (!empty($this->launch_data)) {
            return str_replace(
                array("\r", "\n", "'"),
                array('\r', '\n', "\\'"),
                $this->launch_data
            );
        }
        return '';
    }

    /**
     * Gets the lesson location
     * @return string lesson location as recorded by the SCORM and AICC
     * elements. Defaults to ''
     */
    public function get_lesson_location()
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::get_lesson_location()', 0);
        }
        if (!empty($this->lesson_location)) {
            return str_replace(
                array("\r", "\n", "'"),
                array('\r', '\n', "\\'"),
                $this->lesson_location
            );
        } else {
            return '';
        }
    }

    /**
     * Gets the lesson_mode (scorm feature, but might be used by aicc as well
     * as chamilo paths)
     *
     * The "browse" mode is not supported yet (because there is no such way of
     * seeing a sco in Chamilo)
     * @return    string    'browse','normal' or 'review'. Defaults to 'normal'
     */
    public function get_lesson_mode()
    {
        $mode = 'normal';
        if ($this->get_prevent_reinit() != 0) { // If prevent_reinit == 0
            $my_status = $this->get_status();
            if ($my_status != $this->possible_status[0]
                    && $my_status != $this->possible_status[1]) {
                $mode = 'review';
            }
        }
        return $mode;
    }

    /**
     * Gets the depth level
     * @return int    Level. Defaults to 0
     */
    public function get_level()
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::get_level()', 0);
        }
        if (empty($this->level)) {
            return 0;
        }
        return $this->level;
    }

    /**
     * Gets the mastery score
     */
    public function get_mastery_score()
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::get_mastery_score()', 0);
        }
        if (isset($this->mastery_score)) {
            return $this->mastery_score;
        } else {
            return -1;
        }
    }

    /**
     * Gets the maximum (score)
     * @return int Maximum score. Defaults to 100 if nothing else is defined
     */
    public function get_max()
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::get_max()', 0);
        }
        if ($this->type == 'sco') {
            if (isset($this->view_max_score) && !empty($this->view_max_score) && $this->view_max_score > 0) {
                return $this->view_max_score;
            } elseif (isset($this->view_max_score) && $this->view_max_score === '') {
                return $this->view_max_score;
            } else {
                if (!empty($this->max_score)) {
                    return $this->max_score;
                } else {
                    return 100;
                }
            }
        } else {
            if (!empty($this->max_score)) {
                return $this->max_score;
            } else {
                return 100;
            }
        }
    }

    /**
     * Gets the maximum time allowed for this user in this attempt on this item
     * @return    string    Time string in SCORM format
     * (HH:MM:SS or HH:MM:SS.SS or HHHH:MM:SS.SS)
     */
    public function get_max_time_allowed()
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::get_max_time_allowed()', 0);
        }
        if (!empty($this->max_time_allowed)) {
            return $this->max_time_allowed;
        } else {
            return '';
        }
    }

    /**
     * Gets the minimum (score)
     * @return int    Minimum score. Defaults to 0
     */
    public function get_min()
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::get_min()', 0);
        }
        if (!empty($this->min_score)) {
            return $this->min_score;
        } else {
            return 0;
        }
    }

    /**
     * Gets the parent ID
     * @return    int    Parent ID. Defaults to null
     */
    public function get_parent()
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::get_parent()', 0);
        }
        if (!empty($this->parent)) {
            return $this->parent;
        }
        // TODO: Check this return value is valid for children classes (SCORM?).
        return null;
    }

    /**
     * Gets the path attribute.
     * @return    string    Path. Defaults to ''
     */
    public function get_path()
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::get_path()', 0);
        }
        if (empty($this->path)) {
            return '';
        }
        return $this->path;
    }

    /**
     * Gets the prerequisites string
     * @return    string    Empty string or prerequisites string if defined.
     */
    public function get_prereq_string()
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::get_prereq_string()', 0);
        }
        if (!empty($this->prereq_string)) {
            return $this->prereq_string;
        } else {
            return '';
        }
    }

    /**
     * Gets the prevent_reinit attribute value (and sets it if not set already)
     * @return    int    1 or 0 (defaults to 1)
     */
    public function get_prevent_reinit()
    {
        $course_id = api_get_course_int_id();
        if (self::DEBUG > 2) {
            error_log('learnpathItem::get_prevent_reinit()', 0);
        }
        if (!isset($this->prevent_reinit)) {
            if (!empty($this->lp_id)) {
                $table = Database::get_course_table(TABLE_LP_MAIN);
                $sql = "SELECT prevent_reinit
                    FROM $table
                    WHERE c_id = $course_id AND id = ".$this->lp_id;
                $res = Database::query($sql);
                if (Database::num_rows($res) < 1) {
                    $this->error = "Could not find parent learnpath in lp table";
                    if (self::DEBUG > 2) {
                        error_log(
                            'New LP - End of learnpathItem::get_prevent_reinit() - Returning false',
                            0
                        );
                    }
                    return false;
                } else {
                    $row = Database::fetch_array($res);
                    $this->prevent_reinit = $row['prevent_reinit'];
                }
            } else {
                // Prevent reinit is always 1 by default - see learnpath.class.php
                $this->prevent_reinit = 1;
            }
        }
        if (self::DEBUG > 2) {
            error_log(
                'New LP - End of learnpathItem::get_prevent_reinit() - Returned '.$this->prevent_reinit,
                0
            );
        }
        return $this->prevent_reinit;
    }

    /**
     * Returns 1 if seriousgame_mode is activated, 0 otherwise
     *
     * @return int (0 or 1)
     * @deprecated seriousgame_mode seems not to be used
     * @author ndiechburg <noel@cblue.be>
     **/
    public function get_seriousgame_mode()
    {
        if (self::DEBUG > 2) {
            error_log('learnpathItem::get_seriousgame_mode()', 0);
        }
        $course_id = api_get_course_int_id();
        if (!isset($this->seriousgame_mode)) {
            if (!empty($this->lp_id)) {
                $table = Database::get_course_table(TABLE_LP_MAIN);
                $sql = "SELECT seriousgame_mode
                        FROM $table
                        WHERE c_id = $course_id AND id = ".$this->lp_id;
                $res = Database::query($sql);
                if (Database::num_rows($res) < 1) {
                    $this->error = "Could not find parent learnpath in learnpath table";
                    if (self::DEBUG > 2) {
                        error_log(
                            'New LP - End of learnpathItem::get_seriousgame_mode() - Returning false',
                            0
                        );
                    }
                    return false;
                } else {
                    $row = Database::fetch_array($res);
                    $this->seriousgame_mode = isset($row['seriousgame_mode']) ? $row['seriousgame_mode'] : 0;
                }
            } else {
                $this->seriousgame_mode = 0; //SeriousGame mode is always off by default
            }
        }
        if (self::DEBUG > 2) {
            error_log(
                'New LP - End of learnpathItem::get_seriousgame_mode() - Returned '.$this->seriousgame_mode,
                0
            );
        }

        return $this->seriousgame_mode;
    }

    /**
     * Gets the item's reference column
     * @return string The item's reference field (generally used for SCORM identifiers)
     */
    public function get_ref()
    {
        return $this->ref;
    }

    /**
     * Gets the list of included resources as a list of absolute or relative
     * paths of resources included in the current item. This allows for a
     * better SCORM export. The list will generally include pictures, flash
     * objects, java applets, or any other stuff included in the source of the
     * current item. The current item is expected to be an HTML file. If it
     * is not, then the function will return and empty list.
     * @param    string   $type type (one of the Chamilo tools) - optional (otherwise takes the current item's type)
     * @param    string   $abs_path absolute file path - optional (otherwise takes the current item's path)
     * @param    int      $recursivity level of recursivity we're in
     * @return   array    List of file paths.
     * An additional field containing 'local' or 'remote' helps determine if
     * the file should be copied into the zip or just linked
     */
    public function get_resources_from_source(
        $type = null,
        $abs_path = null,
        $recursivity = 1
    ) {
        $max = 5;
        if ($recursivity > $max) {
            return array();
        }
        if (!isset($type)) {
            $type = $this->get_type();
        }
        if (!isset($abs_path)) {
            $path = $this->get_file_path();
            $abs_path = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/'.$path;
        }

        $files_list = array();
        $type = $this->get_type();

        switch ($type) {
            case TOOL_DOCUMENT:
            case TOOL_QUIZ:
            case 'sco':
                // Get the document and, if HTML, open it.
                if (is_file($abs_path)) {
                    // for now, read the whole file in one go (that's gonna be
                    // a problem when the file is too big).
                    $info = pathinfo($abs_path);
                    $ext = $info['extension'];

                    switch (strtolower($ext)) {
                        case 'html':
                        case 'htm':
                        case 'shtml':
                        case 'css':
                            $wanted_attributes = array(
                                'src',
                                'url',
                                '@import',
                                'href',
                                'value'
                            );
                            // Parse it for included resources.
                            $file_content = file_get_contents($abs_path);
                            // Get an array of attributes from the HTML source.
                            $attributes = DocumentManager::parse_HTML_attributes(
                                $file_content,
                                $wanted_attributes
                            );

                            // Look at 'src' attributes in this file
                            foreach ($wanted_attributes as $attr) {
                                if (isset($attributes[$attr])) {
                                    // Find which kind of path these are (local or remote).
                                    $sources = $attributes[$attr];

                                    foreach ($sources as $source) {
                                        // Skip what is obviously not a resource.
                                        if (strpos(
                                            $source,
                                            "+this."
                                        )
                                        ) {
                                            continue;
                                        } // javascript code - will still work unaltered.
                                        if (strpos(
                                                $source,
                                                '.'
                                            ) === false
                                        ) {
                                            continue;
                                        } // No dot, should not be an external file anyway.
                                        if (strpos(
                                            $source,
                                            'mailto:'
                                        )
                                        ) {
                                            continue;
                                        } // mailto link.
                                        if (strpos($source, ';') && !strpos(
                                                $source,
                                                '&amp;'
                                            )
                                        ) {
                                            continue;
                                        } // Avoid code - that should help.

                                        if ($attr == 'value') {
                                            if (strpos($source, 'mp3file')) {
                                                $files_list[] = array(
                                                    substr(
                                                        $source,
                                                        0,
                                                        strpos(
                                                            $source,
                                                            '.swf'
                                                        ) + 4
                                                    ),
                                                    'local',
                                                    'abs'
                                                );
                                                $mp3file = substr(
                                                    $source,
                                                    strpos(
                                                        $source,
                                                        'mp3file='
                                                    ) + 8
                                                );
                                                if (substr(
                                                        $mp3file,
                                                        0,
                                                        1
                                                    ) == '/'
                                                ) {
                                                    $files_list[] = array(
                                                        $mp3file,
                                                        'local',
                                                        'abs'
                                                    );
                                                } else {
                                                    $files_list[] = array(
                                                        $mp3file,
                                                        'local',
                                                        'rel'
                                                    );
                                                }
                                            } elseif (strpos(
                                                    $source,
                                                    'flv='
                                                ) === 0
                                            ) {

                                                $source = substr($source, 4);
                                                if (strpos($source, '&') > 0) {
                                                    $source = substr(
                                                        $source,
                                                        0,
                                                        strpos($source, '&')
                                                    );
                                                }
                                                if (strpos(
                                                        $source,
                                                        '://'
                                                    ) > 0
                                                ) {
                                                    if (strpos(
                                                            $source,
                                                            api_get_path(
                                                                WEB_PATH
                                                            )
                                                        ) !== false
                                                    ) {
                                                        // We found the current portal url.
                                                        $files_list[] = array(
                                                            $source,
                                                            'local',
                                                            'url'
                                                        );
                                                    } else {
                                                        // We didn't find any trace of current portal.
                                                        $files_list[] = array(
                                                            $source,
                                                            'remote',
                                                            'url'
                                                        );
                                                    }
                                                } else {
                                                    $files_list[] = array(
                                                        $source,
                                                        'local',
                                                        'abs'
                                                    );
                                                }
                                                continue; // Skipping anything else to avoid two entries
                                                //(while the others can have sub-files in their url, flv's can't).
                                            }
                                        }

                                        if (strpos($source, '://') > 0) {

                                            // Cut at '?' in a URL with params.
                                            if (strpos($source, '?') > 0) {
                                                $second_part = substr(
                                                    $source,
                                                    strpos($source, '?')
                                                );
                                                if (strpos(
                                                        $second_part,
                                                        '://'
                                                    ) > 0
                                                ) {
                                                    // If the second part of the url contains a url too,
                                                    // treat the second one before cutting.

                                                    $pos1 = strpos(
                                                        $second_part,
                                                        '='
                                                    );
                                                    $pos2 = strpos(
                                                        $second_part,
                                                        '&'
                                                    );
                                                    $second_part = substr(
                                                        $second_part,
                                                        $pos1 + 1,
                                                        $pos2 - ($pos1 + 1)
                                                    );
                                                    if (strpos(
                                                            $second_part,
                                                            api_get_path(
                                                                WEB_PATH
                                                            )
                                                        ) !== false
                                                    ) {
                                                        // We found the current portal url.
                                                        $files_list[] = array(
                                                            $second_part,
                                                            'local',
                                                            'url'
                                                        );
                                                        $in_files_list[] = self::get_resources_from_source(
                                                            TOOL_DOCUMENT,
                                                            $second_part,
                                                            $recursivity + 1
                                                        );
                                                        if (count(
                                                                $in_files_list
                                                            ) > 0
                                                        ) {
                                                            $files_list = array_merge(
                                                                $files_list,
                                                                $in_files_list
                                                            );
                                                        }
                                                    } else {
                                                        // We didn't find any trace of current portal.
                                                        $files_list[] = array(
                                                            $second_part,
                                                            'remote',
                                                            'url'
                                                        );
                                                    }
                                                } elseif (strpos(
                                                        $second_part,
                                                        '='
                                                    ) > 0
                                                ) {
                                                    if (substr(
                                                            $second_part,
                                                            0,
                                                            1
                                                        ) === '/'
                                                    ) {
                                                        // Link starts with a /, making it absolute (relative to DocumentRoot).
                                                        $files_list[] = array(
                                                            $second_part,
                                                            'local',
                                                            'abs'
                                                        );
                                                        $in_files_list[] = self::get_resources_from_source(
                                                            TOOL_DOCUMENT,
                                                            $second_part,
                                                            $recursivity + 1
                                                        );
                                                        if (count(
                                                                $in_files_list
                                                            ) > 0
                                                        ) {
                                                            $files_list = array_merge(
                                                                $files_list,
                                                                $in_files_list
                                                            );
                                                        }
                                                    } elseif (strstr(
                                                            $second_part,
                                                            '..'
                                                        ) === 0
                                                    ) {
                                                        // Link is relative but going back in the hierarchy.
                                                        $files_list[] = array(
                                                            $second_part,
                                                            'local',
                                                            'rel'
                                                        );
                                                        $dir = dirname(
                                                            $abs_path
                                                        );
                                                        $new_abs_path = realpath(
                                                            $dir.'/'.$second_part
                                                        );
                                                        $in_files_list[] = self::get_resources_from_source(
                                                            TOOL_DOCUMENT,
                                                            $new_abs_path,
                                                            $recursivity + 1
                                                        );
                                                        if (count(
                                                                $in_files_list
                                                            ) > 0
                                                        ) {
                                                            $files_list = array_merge(
                                                                $files_list,
                                                                $in_files_list
                                                            );
                                                        }
                                                    } else {
                                                        // No starting '/', making it relative to current document's path.
                                                        if (substr(
                                                                $second_part,
                                                                0,
                                                                2
                                                            ) == './'
                                                        ) {
                                                            $second_part = substr(
                                                                $second_part,
                                                                2
                                                            );
                                                        }
                                                        $files_list[] = array(
                                                            $second_part,
                                                            'local',
                                                            'rel'
                                                        );
                                                        $dir = dirname(
                                                            $abs_path
                                                        );
                                                        $new_abs_path = realpath(
                                                            $dir.'/'.$second_part
                                                        );
                                                        $in_files_list[] = self::get_resources_from_source(
                                                            TOOL_DOCUMENT,
                                                            $new_abs_path,
                                                            $recursivity + 1
                                                        );
                                                        if (count(
                                                                $in_files_list
                                                            ) > 0
                                                        ) {
                                                            $files_list = array_merge(
                                                                $files_list,
                                                                $in_files_list
                                                            );
                                                        }
                                                    }
                                                }
                                                // Leave that second part behind now.
                                                $source = substr(
                                                    $source,
                                                    0,
                                                    strpos($source, '?')
                                                );
                                                if (strpos(
                                                        $source,
                                                        '://'
                                                    ) > 0
                                                ) {
                                                    if (strpos(
                                                            $source,
                                                            api_get_path(
                                                                WEB_PATH
                                                            )
                                                        ) !== false
                                                    ) {
                                                        // We found the current portal url.
                                                        $files_list[] = array(
                                                            $source,
                                                            'local',
                                                            'url'
                                                        );
                                                        $in_files_list[] = self::get_resources_from_source(
                                                            TOOL_DOCUMENT,
                                                            $source,
                                                            $recursivity + 1
                                                        );
                                                        if (count(
                                                                $in_files_list
                                                            ) > 0
                                                        ) {
                                                            $files_list = array_merge(
                                                                $files_list,
                                                                $in_files_list
                                                            );
                                                        }
                                                    } else {
                                                        // We didn't find any trace of current portal.
                                                        $files_list[] = array(
                                                            $source,
                                                            'remote',
                                                            'url'
                                                        );
                                                    }
                                                } else {
                                                    // No protocol found, make link local.
                                                    if (substr(
                                                            $source,
                                                            0,
                                                            1
                                                        ) === '/'
                                                    ) {
                                                        // Link starts with a /, making it absolute (relative to DocumentRoot).
                                                        $files_list[] = array(
                                                            $source,
                                                            'local',
                                                            'abs'
                                                        );
                                                        $in_files_list[] = self::get_resources_from_source(
                                                            TOOL_DOCUMENT,
                                                            $source,
                                                            $recursivity + 1
                                                        );
                                                        if (count(
                                                                $in_files_list
                                                            ) > 0
                                                        ) {
                                                            $files_list = array_merge(
                                                                $files_list,
                                                                $in_files_list
                                                            );
                                                        }
                                                    } elseif (strstr(
                                                            $source,
                                                            '..'
                                                        ) === 0
                                                    ) {
                                                        // Link is relative but going back in the hierarchy.
                                                        $files_list[] = array(
                                                            $source,
                                                            'local',
                                                            'rel'
                                                        );
                                                        $dir = dirname(
                                                            $abs_path
                                                        );
                                                        $new_abs_path = realpath(
                                                            $dir.'/'.$source
                                                        );
                                                        $in_files_list[] = self::get_resources_from_source(
                                                            TOOL_DOCUMENT,
                                                            $new_abs_path,
                                                            $recursivity + 1
                                                        );
                                                        if (count(
                                                                $in_files_list
                                                            ) > 0
                                                        ) {
                                                            $files_list = array_merge(
                                                                $files_list,
                                                                $in_files_list
                                                            );
                                                        }
                                                    } else {
                                                        // No starting '/', making it relative to current document's path.
                                                        if (substr(
                                                                $source,
                                                                0,
                                                                2
                                                            ) == './'
                                                        ) {
                                                            $source = substr(
                                                                $source,
                                                                2
                                                            );
                                                        }
                                                        $files_list[] = array(
                                                            $source,
                                                            'local',
                                                            'rel'
                                                        );
                                                        $dir = dirname(
                                                            $abs_path
                                                        );
                                                        $new_abs_path = realpath(
                                                            $dir.'/'.$source
                                                        );
                                                        $in_files_list[] = self::get_resources_from_source(
                                                            TOOL_DOCUMENT,
                                                            $new_abs_path,
                                                            $recursivity + 1
                                                        );
                                                        if (count(
                                                                $in_files_list
                                                            ) > 0
                                                        ) {
                                                            $files_list = array_merge(
                                                                $files_list,
                                                                $in_files_list
                                                            );
                                                        }
                                                    }
                                                }
                                            }

                                            // Found some protocol there.
                                            if (strpos(
                                                    $source,
                                                    api_get_path(WEB_PATH)
                                                ) !== false
                                            ) {
                                                // We found the current portal url.
                                                $files_list[] = array(
                                                    $source,
                                                    'local',
                                                    'url'
                                                );
                                                $in_files_list[] = self::get_resources_from_source(
                                                    TOOL_DOCUMENT,
                                                    $source,
                                                    $recursivity + 1
                                                );
                                                if (count($in_files_list) > 0) {
                                                    $files_list = array_merge(
                                                        $files_list,
                                                        $in_files_list
                                                    );
                                                }
                                            } else {
                                                // We didn't find any trace of current portal.
                                                $files_list[] = array(
                                                    $source,
                                                    'remote',
                                                    'url'
                                                );
                                            }
                                        } else {
                                            // No protocol found, make link local.
                                            if (substr($source, 0, 1) === '/') {
                                                // Link starts with a /, making it absolute (relative to DocumentRoot).
                                                $files_list[] = array(
                                                    $source,
                                                    'local',
                                                    'abs'
                                                );
                                                $in_files_list[] = self::get_resources_from_source(
                                                    TOOL_DOCUMENT,
                                                    $source,
                                                    $recursivity + 1
                                                );
                                                if (count($in_files_list) > 0) {
                                                    $files_list = array_merge(
                                                        $files_list,
                                                        $in_files_list
                                                    );
                                                }
                                            } elseif (strstr(
                                                    $source,
                                                    '..'
                                                ) === 0
                                            ) {
                                                // Link is relative but going back in the hierarchy.
                                                $files_list[] = array(
                                                    $source,
                                                    'local',
                                                    'rel'
                                                );
                                                $dir = dirname($abs_path);
                                                $new_abs_path = realpath(
                                                    $dir.'/'.$source
                                                );
                                                $in_files_list[] = self::get_resources_from_source(
                                                    TOOL_DOCUMENT,
                                                    $new_abs_path,
                                                    $recursivity + 1
                                                );
                                                if (count($in_files_list) > 0) {
                                                    $files_list = array_merge(
                                                        $files_list,
                                                        $in_files_list
                                                    );
                                                }
                                            } else {

                                                // No starting '/', making it relative to current document's path.
                                                if (strpos(
                                                        $source,
                                                        'width='
                                                    ) || strpos(
                                                        $source,
                                                        'autostart='
                                                    )
                                                ) {
                                                    continue;
                                                }

                                                if (substr(
                                                        $source,
                                                        0,
                                                        2
                                                    ) == './'
                                                ) {
                                                    $source = substr(
                                                        $source,
                                                        2
                                                    );
                                                }
                                                $files_list[] = array(
                                                    $source,
                                                    'local',
                                                    'rel'
                                                );
                                                $dir = dirname($abs_path);
                                                $new_abs_path = realpath(
                                                    $dir.'/'.$source
                                                );
                                                $in_files_list[] = self::get_resources_from_source(
                                                    TOOL_DOCUMENT,
                                                    $new_abs_path,
                                                    $recursivity + 1
                                                );
                                                if (count($in_files_list) > 0) {
                                                    $files_list = array_merge(
                                                        $files_list,
                                                        $in_files_list
                                                    );
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            break;
                        default:
                            break;
                    }

                } else {
                    // The file could not be found.
                    return false;
                }
                break;
            default: // Ignore.
                break;
        }

        $checked_files_list = array();
        $checked_array_list = array();
        foreach ($files_list as $idx => $file) {
            if (!empty($file[0])) {
                if (!in_array($file[0], $checked_files_list)) {
                    $checked_files_list[] = $files_list[$idx][0];
                    $checked_array_list[] = $files_list[$idx];
                }
            }
        }
        return $checked_array_list;
    }

    /**
     * Gets the score
     * @return    float The current score or 0 if no score set yet
     */
    public function get_score()
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::get_score()', 0);
        }
        $res = 0;
        if (!empty($this->current_score)) {
            $res = $this->current_score;
        }
        if (self::DEBUG > 1) {
            error_log(
                'New LP - Out of learnpathItem::get_score() - returning '.$res,
                0
            );
        }
        return $res;
    }

    /**
     * Gets the item status
     * @param  boolean $check_db  Do or don't check into the database for the
     * latest value. Optional. Default is true
     * @param  boolean $update_local   Do or don't update the local attribute
     * value with what's been found in DB
     * @return string  Current status or 'Not attempted' if no status set yet
     */
    public function get_status($check_db = true, $update_local = false)
    {
        $course_id = api_get_course_int_id();
        $debug = self::DEBUG;
        if ($debug > 0) {
            error_log('learnpathItem::get_status() on item '.$this->db_id, 0);
        }
        if ($check_db) {
            if ($debug > 2) {
                error_log('learnpathItem::get_status(): checking db', 0);
            }
            if (!empty($this->db_item_view_id) && !empty($course_id)) {
                $table = Database::get_course_table(TABLE_LP_ITEM_VIEW);
                $sql = "SELECT status FROM $table
                        WHERE
                            c_id = $course_id AND
                            id = '".$this->db_item_view_id."' AND
                            view_count = '" . $this->get_attempt_id()."'";

                if ($debug > 2) {
                    error_log(
                        'learnpathItem::get_status() - Checking DB: '.$sql,
                        0
                    );
                }

                $res = Database::query($sql);
                if (Database::num_rows($res) == 1) {
                    $row = Database::fetch_array($res);
                    if ($update_local) {
                        if ($debug > 2) {
                            error_log(
                                'learnpathItem::set_status() :'.$row['status'],
                                0
                            );
                        }
                        $this->set_status($row['status']);
                    }
                    if ($debug > 2) {
                        error_log(
                            'learnpathItem::get_status() - Returning db value '.$row['status'],
                            0
                        );
                    }
                    return $row['status'];
                }
            }
        } else {
            if ($debug > 2) {
                error_log(
                    'learnpathItem::get_status() - in get_status: using attrib',
                    0
                );
            }
            if (!empty($this->status)) {
                if ($debug > 2) {
                    error_log(
                        'learnpathItem::get_status() - Returning attrib: '.$this->status,
                        0
                    );
                }
                return $this->status;
            }
        }

        if ($debug > 2) {
            error_log(
                'learnpathItem::get_status() - Returning default '.$this->possible_status[0],
                0
            );
        }

        return $this->possible_status[0];
    }

    /**
     * Gets the suspend data
     */
    public function get_suspend_data()
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::get_suspend_data()', 0);
        }
        // TODO: Improve cleaning of breaklines ... it works but is it really
        // a beautiful way to do it ?
        if (!empty($this->current_data)) {
            return str_replace(
                array("\r", "\n", "'"),
                array('\r', '\n', "\\'"),
                $this->current_data
            );
        } else {
            return '';
        }
    }

    /**
     * @param string $origin
     * @return string
     */
    public static function getScormTimeFromParameter($origin = 'php', $time = null)
    {
        $h = get_lang('h');
        if (!isset($time)) {
            if ($origin == 'js') {
                return '00 : 00: 00';
            } else {
                return '00 '.$h.' 00 \' 00"';
            }
        } else {
            return api_format_time($time, $origin);
        }
    }

    /**
     * Gets the total time spent on this item view so far
     * @param  string  $origin  Origin of the request. If coming from PHP,
     * send formatted as xxhxx'xx", otherwise use scorm format 00:00:00
     * @param  integer|null $given_time   Given time is a default time to return formatted
     * @param  bool    $query_db Whether to get the value from db or from memory
     * @return string A string with the time in SCORM format
     */
    public function get_scorm_time(
        $origin = 'php',
        $given_time = null,
        $query_db = false
    ) {
        $time = null;
        $course_id = api_get_course_int_id();
        if (!isset($given_time)) {
            if (self::DEBUG > 2) {
                error_log(
                    'learnpathItem::get_scorm_time(): given time empty, current_start_time = '.$this->current_start_time,
                    0
                );
            }
            if ($query_db === true) {
                $table = Database::get_course_table(TABLE_LP_ITEM_VIEW);
                $sql = "SELECT start_time, total_time
                        FROM $table
                        WHERE
                            c_id = $course_id AND
                            id = '".$this->db_item_view_id."' AND
                            view_count = '" . $this->get_attempt_id()."'";
                $res = Database::query($sql);
                $row = Database::fetch_array($res);
                $start = $row['start_time'];
                $stop = $start + $row['total_time'];
            } else {
                $start = $this->current_start_time;
                $stop = $this->current_stop_time;
            }
            if (!empty($start)) {
                if (!empty($stop)) {
                    $time = $stop - $start;
                } else {
                    $time = time() - $start;
                }
            }
        } else {
            $time = $given_time;
        }
        if (self::DEBUG > 2) {
            error_log(
                'learnpathItem::get_scorm_time(): intermediate = '.$time,
                0
            );
        }
        $time = api_format_time($time, $origin);

        return $time;
    }

    /**
     * Get the extra terms (tags) that identify this item
     * @return mixed
     */
    public function get_terms()
    {
        $lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $course_id = api_get_course_int_id();
        $sql = "SELECT * FROM $lp_item
                WHERE
                    c_id = $course_id AND
                    id='".intval($this->db_id)."'";
        $res = Database::query($sql);
        $row = Database::fetch_array($res);
        return $row['terms'];
    }

    /**
     * Returns the item's title
     * @return    string    Title
     */
    public function get_title()
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::get_title()', 0);
        }
        if (empty($this->title)) {
            return '';
        }
        return $this->title;
    }

    /**
     * Returns the total time used to see that item
     * @return    integer    Total time
     */
    public function get_total_time()
    {
        if (self::DEBUG > 0) {
            error_log(
                'learnpathItem::get_total_time() for item '.$this->db_id.
                ' - Initially, current_start_time = '.$this->current_start_time.
                ' and current_stop_time = '.$this->current_stop_time,
                0
            );
        }
        if ($this->current_start_time == 0) {
            // Shouldn't be necessary thanks to the open() method.
            if (self::DEBUG > 2) {
                error_log(
                    'learnpathItem::get_total_time() - Current start time was empty',
                    0
                );
            }
            $this->current_start_time = time();
        }
        //$this->current_stop_time=time();
        if (time() < $this->current_stop_time || $this->current_stop_time == 0
        ) {
            if (self::DEBUG > 2) {
                error_log(
                    'learnpathItem::get_total_time() - Current stop time was '
                    .'greater than the current time or was empty',
                    0
                );
            }
            // If this case occurs, then we risk to write huge time data in db.
            // In theory, stop time should be *always* updated here, but it
            // might be used in some unknown goal.
            $this->current_stop_time = time();
        }
        $time = $this->current_stop_time - $this->current_start_time;
        if ($time < 0) {
            if (self::DEBUG > 2) {
                error_log(
                    'learnpathItem::get_total_time() - Time smaller than 0. Returning 0',
                    0
                );
            }
            return 0;
        } else {
            if (self::DEBUG > 2) {
                error_log(
                    'learnpathItem::get_total_time() - Current start time = '.
                    $this->current_start_time.', current stop time = '.
                    $this->current_stop_time.' Returning '.$time."-----------\n",
                    0
                );
            }
            return $time;
        }
    }

    /**
     * Gets the item type
     * @return    string    The item type (can be doc, dir, sco, asset)
     */
    public function get_type()
    {
        $res = 'asset';
        if (!empty($this->type)) {
            $res = $this->type;
        }
        if (self::DEBUG > 2) {
            error_log(
                'learnpathItem::get_type() - Returning '.$res.' for item '.$this->db_id,
                0
            );
        }
        return $res;
    }

    /**
     * Gets the view count for this item
     * @return  int     Number of attempts or 0
     */
    public function get_view_count()
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::get_view_count()', 0);
        }
        if (!empty($this->attempt_id)) {
            return $this->attempt_id;
        } else {
            return 0;
        }
    }

    /**
     * Tells if an item is done ('completed','passed','succeeded') or not
     * @return bool True if the item is done ('completed','passed','succeeded'),
     * false otherwise
     */
    public function is_done()
    {
        $completedStatusList = array(
            'completed',
            'passed',
            'succeeded',
            'failed'
        );

        if ($this->status_is($completedStatusList)) {
            if (self::DEBUG > 2) {
                error_log(
                    'learnpath::is_done() - Item '.$this->get_id(
                    ).' is complete',
                    0
                );
            }

            return true;
        } else {
            if (self::DEBUG > 2) {
                error_log(
                    'learnpath::is_done() - Item '.$this->get_id(
                    ).' is not complete',
                    0
                );
            }

            return false;
        }
    }

    /**
     * Tells if a restart is allowed (take it from $this->prevent_reinit and $this->status)
     * @return integer    -1 if retaking the sco another time for credit is not allowed,
     *                     0 if it is not allowed but the item has to be finished
     *                     1 if it is allowed. Defaults to 1
     */
    public function is_restart_allowed()
    {
        if (self::DEBUG > 2) {
            error_log('learnpathItem::is_restart_allowed()', 0);
        }
        $restart = 1;
        $mystatus = $this->get_status(true);
        if ($this->get_prevent_reinit() > 0
        ) { // If prevent_reinit == 1 (or more)
            // If status is not attempted or incomplete, authorize retaking (of the same) anyway. Otherwise:
            if ($mystatus != $this->possible_status[0] && $mystatus != $this->possible_status[1]) {
                $restart = -1;
            } else { //status incompleted or not attempted
                $restart = 0;
            }
        } else {
            if ($mystatus == $this->possible_status[0] || $mystatus == $this->possible_status[1]) {
                $restart = -1;
            }
        }
        if (self::DEBUG > 2) {
            error_log(
                'New LP - End of learnpathItem::is_restart_allowed() - Returning '.$restart,
                0
            );
        }
        return $restart;
    }

    /**
     * Opens/launches the item. Initialises runtime values.
     * @return    boolean    True on success, false on failure.
     */
    public function open($allow_new_attempt = false)
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::open()', 0);
        }
        if ($this->prevent_reinit == 0) {
            $this->current_score = 0;
            $this->current_start_time = time();
            // In this case, as we are opening the item, what is important to us
            // is the database status, in order to know if this item has already
            // been used in the past (rather than just loaded and modified by
            // some javascript but not written in the database).
            // If the database status is different from 'not attempted', we can
            // consider this item has already been used, and as such we can
            // open a new attempt. Otherwise, we'll just reuse the current
            // attempt, which is generally created the first time the item is
            // loaded (for example as part of the table of contents).
            $stat = $this->get_status(true);
            if ($allow_new_attempt && isset($stat) && ($stat != $this->possible_status[0])) {
                $this->attempt_id = $this->attempt_id + 1; // Open a new attempt.
            }
            $this->status = $this->possible_status[1];
        } else {
            /*if ($this->current_start_time == 0) {
				// Small exception for start time, to avoid amazing values.
				$this->current_start_time = time();
			}*/
            // If we don't init start time here, the time is sometimes calculated from the last start time.
            $this->current_start_time = time();

            //error_log('New LP - reinit blocked by setting', 0);
        }
    }

    /**
     * Outputs the item contents
     * @return    string    HTML file (displayable in an <iframe>) or empty string if no path defined
     */
    public function output()
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::output()', 0);
        }
        if (!empty($this->path) and is_file($this->path)) {
            $output = '';
            $output .= file_get_contents($this->path);
            return $output;
        }
        return '';
    }

    /**
     * Parses the prerequisites string with the AICC logic language
     * @param    string   $prereqs_string The prerequisites string as it figures in imsmanifest.xml
     * @param    Array    $items Array of items in the current learnpath object.
     * Although we're in the learnpathItem object, it's necessary to have
     * a list of all items to be able to check the current item's prerequisites
     * @param    Array    $refs_list List of references
     * (the "ref" column in the lp_item table) that are strings used in the
     * expression of prerequisites.
     * @param    integer  $user_id The user ID. In some cases like Chamilo quizzes,
     * it's necessary to have the user ID to query other tables (like the results of quizzes)
     * @return boolean True if the list of prerequisites given is entirely satisfied, false otherwise
     */
    public function parse_prereq($prereqs_string, $items, $refs_list, $user_id)
    {
        if (self::DEBUG > 0) {
            error_log(
                'learnpathItem::parse_prereq() for learnpath '.$this->lp_id.' with string '.$prereqs_string,
                0
            );
        }

        $course_id = api_get_course_int_id();
        $sessionId = api_get_session_id();

        // Deal with &, |, ~, =, <>, {}, ,, X*, () in reverse order.
        $this->prereq_alert = '';
        // First parse all parenthesis by using a sequential loop
        //  (looking for less-inclusives first).

        if ($prereqs_string == '_true_') {

            return true;
        }

        if ($prereqs_string == '_false_') {
            if (empty($this->prereq_alert)) {
                $this->prereq_alert = get_lang('LearnpathPrereqNotCompleted');
            }
            return false;
        }
        while (strpos($prereqs_string, '(') !== false) {
            // Remove any () set and replace with its value.
            $matches = array();
            $res = preg_match_all(
                '/(\(([^\(\)]*)\))/',
                $prereqs_string,
                $matches
            );
            if ($res) {
                foreach ($matches[2] as $id => $match) {
                    $str_res = $this->parse_prereq(
                        $match,
                        $items,
                        $refs_list,
                        $user_id
                    );
                    if ($str_res) {
                        $prereqs_string = str_replace(
                            $matches[1][$id],
                            '_true_',
                            $prereqs_string
                        );
                    } else {
                        $prereqs_string = str_replace(
                            $matches[1][$id],
                            '_false_',
                            $prereqs_string
                        );
                    }
                }
            }
        }

        // Parenthesis removed, now look for ORs as it is the lesser-priority
        //  binary operator (= always uses one text operand).
        if (strpos($prereqs_string, '|') === false) {
            if (self::DEBUG > 1) {
                error_log('New LP - Didnt find any OR, looking for AND', 0);
            }
            if (strpos($prereqs_string, '&') !== false) {
                $list = explode('&', $prereqs_string);
                if (count($list) > 1) {
                    $andstatus = true;
                    foreach ($list as $condition) {
                        $andstatus = $andstatus && $this->parse_prereq(
                            $condition,
                            $items,
                            $refs_list,
                            $user_id
                        );

                        if (!$andstatus) {
                            if (self::DEBUG > 1) {
                                error_log(
                                    'New LP - One condition in AND was false, short-circuit',
                                    0
                                );
                            }
                            break;
                        }
                    }
                    if (empty($this->prereq_alert) && !$andstatus) {
                        $this->prereq_alert = get_lang(
                            'LearnpathPrereqNotCompleted'
                        );
                    }
                    return $andstatus;
                } else {
                    if (isset($items[$refs_list[$list[0]]])) {
                        $status = $items[$refs_list[$list[0]]]->get_status(true);
                        $returnstatus = ($status == $this->possible_status[2]) || ($status == $this->possible_status[3]);
                        if (empty($this->prereq_alert) && !$returnstatus) {
                            $this->prereq_alert = get_lang('LearnpathPrereqNotCompleted');
                        }

                        return $returnstatus;
                    }
                    $this->prereq_alert = get_lang('LearnpathPrereqNotCompleted');

                    return false;
                }
            } else {
                // No ORs found, now look for ANDs.

                if (self::DEBUG > 1) {
                    error_log('New LP - Didnt find any AND, looking for =', 0);
                }

                if (strpos($prereqs_string, '=') !== false) {
                    if (self::DEBUG > 1) {
                        error_log('New LP - Found =, looking into it', 0);
                    }
                    // We assume '=' signs only appear when there's nothing else around.
                    $params = explode('=', $prereqs_string);
                    if (count($params) == 2) {
                        // Right number of operands.
                        if (isset($items[$refs_list[$params[0]]])) {
                            $status = $items[$refs_list[$params[0]]]->get_status(true);
                            $returnstatus = $status == $params[1];
                            if (empty($this->prereq_alert) && !$returnstatus) {
                                $this->prereq_alert = get_lang('LearnpathPrereqNotCompleted');
                            }
                            return $returnstatus;
                        }
                        $this->prereq_alert = get_lang('LearnpathPrereqNotCompleted');

                        return false;
                    }
                } else {
                    // No ANDs found, look for <>

                    if (self::DEBUG > 1) {
                        error_log(
                            'New LP - Didnt find any =, looking for <>',
                            0
                        );
                    }

                    if (strpos($prereqs_string, '<>') !== false) {
                        if (self::DEBUG > 1) {
                            error_log('New LP - Found <>, looking into it', 0);
                        }
                        // We assume '<>' signs only appear when there's nothing else around.
                        $params = explode('<>', $prereqs_string);
                        if (count($params) == 2) {
                            // Right number of operands.
                            if (isset($items[$refs_list[$params[0]]])) {
                                $status = $items[$refs_list[$params[0]]]->get_status(true);
                                $returnstatus = $status != $params[1];
                                if (empty($this->prereq_alert) && !$returnstatus) {
                                    $this->prereq_alert = get_lang('LearnpathPrereqNotCompleted');
                                }

                                return $returnstatus;
                            }
                            $this->prereq_alert = get_lang('LearnpathPrereqNotCompleted');

                            return false;
                        }
                    } else {
                        // No <> found, look for ~ (unary)
                        if (self::DEBUG > 1) {
                            error_log(
                                'New LP - Didnt find any =, looking for ~',
                                0
                            );
                        }
                        // Only remains: ~ and X*{}
                        if (strpos($prereqs_string, '~') !== false) {
                            // Found NOT.
                            if (self::DEBUG > 1) {
                                error_log(
                                    'New LP - Found ~, looking into it',
                                    0
                                );
                            }
                            $list = array();
                            $myres = preg_match(
                                '/~([^(\d+\*)\{]*)/',
                                $prereqs_string,
                                $list
                            );
                            if ($myres) {
                                $returnstatus = !$this->parse_prereq(
                                    $list[1],
                                    $items,
                                    $refs_list,
                                    $user_id
                                );
                                if (empty($this->prereq_alert) && !$returnstatus) {
                                    $this->prereq_alert = get_lang('LearnpathPrereqNotCompleted');
                                }
                                return $returnstatus;
                            } else {
                                // Strange...
                                if (self::DEBUG > 1) {
                                    error_log(
                                        'New LP - Found ~ but strange string: '.$prereqs_string,
                                        0
                                    );
                                }
                            }
                        } else {
                            // Finally, look for sets/groups

                            if (self::DEBUG > 1) {
                                error_log(
                                    'New LP - Didnt find any ~, looking for groups',
                                    0
                                );
                            }
                            // Only groups here.
                            $groups = array();
                            $groups_there = preg_match_all(
                                '/((\d+\*)?\{([^\}]+)\}+)/',
                                $prereqs_string,
                                $groups
                            );

                            if ($groups_there) {
                                foreach ($groups[1] as $gr) {
                                    // Only take the results that correspond to
                                    //  the big brackets-enclosed condition.
                                    if (self::DEBUG > 1) {
                                        error_log(
                                            'New LP - Dealing with group '.$gr,
                                            0
                                        );
                                    }
                                    $multi = array();
                                    $mycond = false;
                                    if (preg_match(
                                        '/(\d+)\*\{([^\}]+)\}/',
                                        $gr,
                                        $multi
                                    )
                                    ) {
                                        if (self::DEBUG > 1) {
                                            error_log(
                                                'New LP - Found multiplier '.$multi[0],
                                                0
                                            );
                                        }
                                        $count = $multi[1];
                                        $list = explode(',', $multi[2]);
                                        $mytrue = 0;
                                        foreach ($list as $cond) {
                                            if (isset($items[$refs_list[$cond]])) {
                                                $status = $items[$refs_list[$cond]]->get_status(true);
                                                if ($status == $this->possible_status[2] ||
                                                    $status == $this->possible_status[3]
                                                ) {
                                                    $mytrue++;
                                                    if (self::DEBUG > 1) {
                                                        error_log(
                                                            'New LP - Found true item, counting.. ('.($mytrue).')',
                                                            0
                                                        );
                                                    }
                                                }
                                            } else {
                                                if (self::DEBUG > 1) {
                                                    error_log(
                                                        'New LP - item '.$cond.' does not exist in items list',
                                                        0
                                                    );
                                                }
                                            }
                                        }
                                        if ($mytrue >= $count) {
                                            if (self::DEBUG > 1) {
                                                error_log(
                                                    'New LP - Got enough true results, return true',
                                                    0
                                                );
                                            }
                                            $mycond = true;
                                        } else {
                                            if (self::DEBUG > 1) {
                                                error_log(
                                                    'New LP - Not enough true results',
                                                    0
                                                );
                                            }
                                        }
                                    } else {
                                        if (self::DEBUG > 1) {
                                            error_log(
                                                'New LP - No multiplier',
                                                0
                                            );
                                        }
                                        $list = explode(',', $gr);
                                        $mycond = true;
                                        foreach ($list as $cond) {
                                            if (isset($items[$refs_list[$cond]])) {
                                                $status = $items[$refs_list[$cond]]->get_status(true);
                                                if ($status == $this->possible_status[2] ||
                                                    $status == $this->possible_status[3]
                                                ) {
                                                    $mycond = true;
                                                    if (self::DEBUG > 1) {
                                                        error_log(
                                                            'New LP - Found true item',
                                                            0
                                                        );
                                                    }
                                                } else {
                                                    if (self::DEBUG > 1) {
                                                        error_log(
                                                            'New LP - '.
                                                            ' Found false item, the set is not true, return false',
                                                            0
                                                        );
                                                    }
                                                    $mycond = false;
                                                    break;
                                                }
                                            } else {
                                                if (self::DEBUG > 1) {
                                                    error_log(
                                                        'New LP - item '.$cond.' does not exist in items list',
                                                        0
                                                    );
                                                }
                                                if (self::DEBUG > 1) {
                                                    error_log(
                                                        'New LP - Found false item, the set is not true, return false',
                                                        0
                                                    );
                                                }
                                                $mycond = false;
                                                break;
                                            }
                                        }
                                    }
                                    if (!$mycond && empty($this->prereq_alert)) {
                                        $this->prereq_alert = get_lang('LearnpathPrereqNotCompleted');
                                    }
                                    return $mycond;
                                }
                            } else {

                                // Nothing found there either. Now return the
                                //  value of the corresponding resource completion status.
                                if (self::DEBUG > 1) {
                                    error_log(
                                        'New LP - Didnt find any group, returning value for '.$prereqs_string,
                                        0
                                    );
                                }

                                if (isset($refs_list[$prereqs_string]) &&
                                    isset($items[$refs_list[$prereqs_string]])
                                ) {
                                    if ($items[$refs_list[$prereqs_string]]->type == 'quiz') {

                                        // 1. Checking the status in current items.
                                        $status = $items[$refs_list[$prereqs_string]]->get_status(true);
                                        $returnstatus = $status == $this->possible_status[2] || $status == $this->possible_status[3];

                                        if (!$returnstatus) {
                                            if (self::DEBUG > 1) {
                                                error_log(
                                                    'New LP - Prerequisite '.$prereqs_string.' not complete',
                                                    0
                                                );
                                            }
                                        } else {
                                            if (self::DEBUG > 1) {
                                                error_log(
                                                    'New LP - Prerequisite '.$prereqs_string.' complete',
                                                    0
                                                );
                                            }
                                        }

                                        // For one and first attempt.
                                        if ($this->prevent_reinit == 1) {
                                            // 2. If is completed we check the results in the DB of the quiz.
                                            if ($returnstatus) {
                                                //AND origin_lp_item_id = '.$user_id.'
                                                $sql = 'SELECT exe_result, exe_weighting
                                                        FROM ' . Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES).'
                                                        WHERE
                                                            exe_exo_id = ' . $items[$refs_list[$prereqs_string]]->path.' AND
                                                            exe_user_id = ' . $user_id.' AND
                                                            orig_lp_id = ' . $this->lp_id.' AND
                                                            orig_lp_item_id = ' . $prereqs_string.' AND
                                                            status <> "incomplete"
                                                        ORDER BY exe_date DESC
                                                        LIMIT 0, 1';
                                                $rs_quiz = Database::query($sql);
                                                if ($quiz = Database::fetch_array($rs_quiz)) {
                                                    $minScore = $items[$refs_list[$this->get_id()]]->getPrerequisiteMinScore();
                                                    $maxScore = $items[$refs_list[$this->get_id()]]->getPrerequisiteMaxScore();

                                                    if (isset($minScore) && isset($minScore)) {
                                                        // Taking min/max prerequisites values see BT#5776
                                                        if ($quiz['exe_result'] >= $minScore && $quiz['exe_result'] <= $maxScore) {
                                                            $returnstatus = true;
                                                        } else {
                                                            $this->prereq_alert = get_lang('LearnpathPrereqNotCompleted');
                                                            $returnstatus = false;
                                                        }
                                                    } else {
                                                        // Classic way
                                                        if ($quiz['exe_result'] >= $items[$refs_list[$prereqs_string]]->get_mastery_score()) {
                                                            $returnstatus = true;
                                                        } else {
                                                            $this->prereq_alert = get_lang('LearnpathPrereqNotCompleted');
                                                            $returnstatus = false;
                                                        }
                                                    }
                                                } else {
                                                    $this->prereq_alert = get_lang(
                                                        'LearnpathPrereqNotCompleted'
                                                    );
                                                    $returnstatus = false;
                                                }
                                            }
                                        } else {
                                            // 3. for multiple attempts we check that there are minimum 1 item completed.

                                            // Checking in the database.
                                            $sql = 'SELECT exe_result, exe_weighting
                                                    FROM ' . Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES).'
                                                    WHERE
                                                        exe_exo_id = ' . $items[$refs_list[$prereqs_string]]->path.' AND
                                                        exe_user_id = ' . $user_id.' AND
                                                        orig_lp_id = ' . $this->lp_id.' AND
                                                        orig_lp_item_id = ' . $prereqs_string.' ';

                                            $rs_quiz = Database::query($sql);
                                            if (Database::num_rows($rs_quiz) > 0) {
                                                while ($quiz = Database::fetch_array($rs_quiz)) {

                                                    $minScore = $items[$refs_list[$this->get_id()]]->getPrerequisiteMinScore();
                                                    $maxScore = $items[$refs_list[$this->get_id()]]->getPrerequisiteMaxScore();

                                                    if (isset($minScore) && isset($minScore)) {

                                                        // Taking min/max prerequisites values see BT#5776
                                                        if ($quiz['exe_result'] >= $minScore && $quiz['exe_result'] <= $maxScore) {
                                                            $returnstatus = true;
                                                            break;
                                                        } else {
                                                            $this->prereq_alert = get_lang('LearnpathPrereqNotCompleted');
                                                            $returnstatus = false;
                                                        }
                                                    } else {
                                                        if ($quiz['exe_result'] >= $items[$refs_list[$prereqs_string]]->get_mastery_score()) {
                                                            $returnstatus = true;
                                                            break;
                                                        } else {
                                                            $this->prereq_alert = get_lang(
                                                                'LearnpathPrereqNotCompleted'
                                                            );
                                                            $returnstatus = false;
                                                        }
                                                    }
                                                }
                                            } else {
                                                $this->prereq_alert = get_lang(
                                                    'LearnpathPrereqNotCompleted'
                                                );
                                                $returnstatus = false;
                                            }
                                        }

                                        return $returnstatus;
                                    } else {
                                        $status = $items[$refs_list[$prereqs_string]]->get_status(false);
                                        $returnstatus = $status == $this->possible_status[2] || $status == $this->possible_status[3];

                                        if (!$returnstatus) {
                                            if (self::DEBUG > 1) {
                                                error_log(
                                                    'New LP - Prerequisite '.$prereqs_string.' not complete',
                                                    0
                                                );
                                            }
                                        } else {
                                            if (self::DEBUG > 1) {
                                                error_log(
                                                    'New LP - Prerequisite '.$prereqs_string.' complete',
                                                    0
                                                );
                                            }
                                        }

                                        if ($returnstatus && $this->prevent_reinit == 1) {
                                            // I would prefer check in the database.
                                            $lp_item_view = Database::get_course_table(
                                                TABLE_LP_ITEM_VIEW
                                            );
                                            $lp_view = Database::get_course_table(
                                                TABLE_LP_VIEW
                                            );

                                            $sql = 'SELECT id FROM '.$lp_view.'
                                                    WHERE
                                                        c_id = ' . $course_id.' AND
                                                        user_id = ' . $user_id.'  AND
                                                        lp_id = ' . $this->lp_id.' AND
                                                        session_id = '.$sessionId.'
                                                    LIMIT 0, 1';
                                            $rs_lp = Database::query($sql);
                                            $lp_id = Database::fetch_row(
                                                $rs_lp
                                            );
                                            $my_lp_id = $lp_id[0];

                                            $sql = 'SELECT status FROM '.$lp_item_view.'
                                                   WHERE
                                                        c_id = ' . $course_id.' AND
                                                        lp_view_id = ' . $my_lp_id.' AND
                                                        lp_item_id = ' . $refs_list[$prereqs_string].'
                                                    LIMIT 0, 1';
                                            $rs_lp = Database::query($sql);
                                            $status_array = Database::fetch_row(
                                                $rs_lp
                                            );
                                            $status = $status_array[0];

                                            $returnstatus = ($status == $this->possible_status[2]) || ($status == $this->possible_status[3]);
                                            if (!$returnstatus && empty($this->prereq_alert)) {
                                                $this->prereq_alert = get_lang(
                                                    'LearnpathPrereqNotCompleted'
                                                );
                                            }
                                            if (!$returnstatus) {
                                                if (self::DEBUG > 1) {
                                                    error_log(
                                                        'New LP - Prerequisite '.$prereqs_string.' not complete',
                                                        0
                                                    );
                                                }
                                            } else {
                                                if (self::DEBUG > 1) {
                                                    error_log(
                                                        'New LP - Prerequisite '.$prereqs_string.' complete',
                                                        0
                                                    );
                                                }
                                            }
                                        }
                                        return $returnstatus;
                                    }
                                } else {
                                    if (self::DEBUG > 1) {
                                        error_log(
                                            'New LP - Could not find '.$prereqs_string.' in '.print_r(
                                                $refs_list,
                                                true
                                            ),
                                            0
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $list = explode("\|", $prereqs_string);
            if (count($list) > 1) {
                if (self::DEBUG > 1) {
                    error_log('New LP - Found OR, looking into it', 0);
                }
                $orstatus = false;
                foreach ($list as $condition) {
                    if (self::DEBUG > 1) {
                        error_log(
                            'New LP - Found OR, adding it ('.$condition.')',
                            0
                        );
                    }
                    $orstatus = $orstatus || $this->parse_prereq(
                        $condition,
                        $items,
                        $refs_list,
                        $user_id
                    );
                    if ($orstatus) {
                        // Shortcircuit OR.
                        if (self::DEBUG > 1) {
                            error_log(
                                'New LP - One condition in OR was true, short-circuit',
                                0
                            );
                        }
                        break;
                    }
                }
                if (!$orstatus && empty($this->prereq_alert)) {
                    $this->prereq_alert = get_lang(
                        'LearnpathPrereqNotCompleted'
                    );
                }
                return $orstatus;
            } else {
                if (self::DEBUG > 1) {
                    error_log(
                        'New LP - OR was found but only one elem present !?',
                        0
                    );
                }
                if (isset($items[$refs_list[$list[0]]])) {
                    $status = $items[$refs_list[$list[0]]]->get_status(true);
                    $returnstatus = $status == 'completed' || $status == 'passed';
                    if (!$returnstatus && empty($this->prereq_alert)) {
                        $this->prereq_alert = get_lang(
                            'LearnpathPrereqNotCompleted'
                        );
                    }

                    return $returnstatus;
                }
            }
        }

        if (empty($this->prereq_alert)) {
            $this->prereq_alert = get_lang('LearnpathPrereqNotCompleted');
        }

        if (self::DEBUG > 1) {
            error_log(
                'New LP - End of parse_prereq. Error code is now '.$this->prereq_alert,
                0
            );
        }

        return false;
    }

    /**
     * Reinits all local values as the learnpath is restarted
     * @return    boolean    True on success, false otherwise
     */
    public function restart()
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::restart()', 0);
        }
        if ($this->type == 'sco') {
            // If this is a sco, Chamilo can't update the time without an
            //  explicit scorm call
            $this->current_start_time = 0;
            $this->current_stop_time = 0; //Those 0 value have this effect
            $this->last_scorm_session_time = 0;
        }
        $this->save();

        //For serious game  : We reuse same attempt_id
        if ($this->get_seriousgame_mode() == 1 && $this->type == 'sco') {
            $this->current_start_time = 0;
            $this->current_stop_time = 0;
            return true;
        }
        $allowed = $this->is_restart_allowed();
        if ($allowed === -1) {
            // Nothing allowed, do nothing.
        } elseif ($allowed === 1) {
            // Restart as new attempt is allowed, record a new attempt.
            $this->attempt_id = $this->attempt_id + 1; // Simply reuse the previous attempt_id.
            $this->current_score = 0;
            $this->current_start_time = 0;
            $this->current_stop_time = 0;
            $this->current_data = '';
            $this->status = $this->possible_status[0];
            $this->interactions_count = 0;
            $this->interactions = array();
            $this->objectives_count = 0;
            $this->objectives = array();
            $this->lesson_location = '';
            if ($this->type != TOOL_QUIZ) {
                $this->write_to_db();
            }
        } else {
            // Restart current element is allowed (because it's not finished yet),
            // reinit current.
            //$this->current_score = 0;
            $this->current_start_time = 0;
            $this->current_stop_time = 0;
            //$this->current_data = '';
            //$this->status = $this->possible_status[0];
            $this->interactions_count = $this->get_interactions_count(true);
            if ($this->type == 'sco') {
                $this->scorm_init_time();
            }
        }
        return true;
    }

    /**
     * Saves data in the database
     * @param boolean $from_outside Save from URL params (1) or from object attributes (0)
     * @param boolean $prereqs_complete The results of a check on prerequisites for this item.
     * True if prerequisites are completed, false otherwise. Defaults to false. Only used if not sco or au
     * @return    boolean    True on success, false on failure
     */
    public function save($from_outside = true, $prereqs_complete = false)
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::save()', 0);
        }
        // First check if parameters passed via GET can be saved here
        // in case it's a SCORM, we should get:

        if ($this->type == 'sco' || $this->type == 'au') {
            $status = $this->get_status(true);
            if ($this->prevent_reinit == 1 &&
                $status != $this->possible_status[0] && // not attempted
                $status != $this->possible_status[1]    //incomplete
            ) {
                if (self::DEBUG > 1) {
                    error_log(
                        'learnpathItem::save() - save reinit blocked by setting',
                        0
                    );
                }
                // Do nothing because the status has already been set. Don't allow it to change.
                // TODO: Check there isn't a special circumstance where this should be saved.
            } else {
                if (self::DEBUG > 1) {
                    error_log(
                        'learnpathItem::save() - SCORM save request received',
                        0
                    );
                }

                // Get all new settings from the URL
                if ($from_outside) {
                    if (self::DEBUG > 1) {
                        error_log(
                            'learnpathItem::save() - Getting item data from outside',
                            0
                        );
                    }
                    foreach ($_GET as $param => $value) {
                        switch ($param) {
                            case 'score':
                                $this->set_score($value);
                                if (self::DEBUG > 2) {
                                    error_log(
                                        'learnpathItem::save() - setting score to '.$value,
                                        0
                                    );
                                }
                                break;
                            case 'max':
                                $this->set_max_score($value);
                                if (self::DEBUG > 2) {
                                    error_log(
                                        'learnpathItem::save() - setting view_max_score to '.$value,
                                        0
                                    );
                                }
                                break;
                            case 'min':
                                $this->min_score = $value;
                                if (self::DEBUG > 2) {
                                    error_log(
                                        'learnpathItem::save() - setting min_score to '.$value,
                                        0
                                    );
                                }
                                break;
                            case 'lesson_status':
                                if (!empty($value)) {
                                    $this->set_status($value);
                                    if (self::DEBUG > 2) {
                                        error_log(
                                            'learnpathItem::save() - setting status to '.$value,
                                            0
                                        );
                                    }
                                }
                                break;
                            case 'time':
                                $this->set_time($value);
                                if (self::DEBUG > 2) {
                                    error_log(
                                        'learnpathItem::save() - setting time to '.$value,
                                        0
                                    );
                                }
                                break;
                            case 'suspend_data':
                                $this->current_data = $value;
                                if (self::DEBUG > 2) {
                                    error_log(
                                        'learnpathItem::save() - setting suspend_data to '.$value,
                                        0
                                    );
                                }
                                break;
                            case 'lesson_location':
                                $this->set_lesson_location($value);
                                if (self::DEBUG > 2) {
                                    error_log(
                                        'learnpathItem::save() - setting lesson_location to '.$value,
                                        0
                                    );
                                }
                                break;
                            case 'core_exit':
                                $this->set_core_exit($value);
                                if (self::DEBUG > 2) {
                                    error_log(
                                        'learnpathItem::save() - setting core_exit to '.$value,
                                        0
                                    );
                                }
                                break;
                            case 'interactions':
                                //$interactions = unserialize($value);
                                //foreach($interactions as $interaction){
                                //	;
                                //}
                                break;
                            case 'objectives':
                                break;
                            //case 'maxtimeallowed':
                            //$this->set_max_time_allowed($value);
                            //break;
                            /*
                            case 'objectives._count':
                                $this->attempt_id = $value;
                                break;
                            */
                            default:
                                // Ignore.
                                break;
                        }
                    }
                } else {
                    if (self::DEBUG > 1) {
                        error_log(
                            'learnpathItem::save() - Using inside item status',
                            0
                        );
                    }
                    // Do nothing, just let the local attributes be used.
                }
            }
        } else { // If not SCO, such messages should not be expected.
            $type = strtolower($this->type);
            switch ($type) {
                case 'asset':
                    if ($prereqs_complete) {
                        $this->set_status($this->possible_status[2]);
                    }
                    break;
                case TOOL_HOTPOTATOES:
                    break;
                case TOOL_QUIZ:
                    return false;
                    break;
                default:
                    // For now, everything that is not sco and not asset is set to
                    // completed when saved.
                    if ($prereqs_complete) {
                        $this->set_status($this->possible_status[2]);
                    }
                    break;
            }
        }

        if (self::DEBUG > 1) {
            error_log(
                'New LP - End of learnpathItem::save() - Calling write_to_db()',
                0
            );
        }

        return $this->write_to_db();
    }

    /**
     * Sets the number of attempt_id to a given value
     * @param    integer  $num  The given value to set attempt_id to
     * @return    boolean    TRUE on success, FALSE otherwise
     */
    public function set_attempt_id($num)
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::set_attempt_id()', 0);
        }
        if ($num == strval(intval($num)) && $num >= 0) {
            $this->attempt_id = $num;
            return true;
        }
        return false;
    }

    /**
     * Sets the core_exit value to the one given
     * @return  bool  $value  True (always)
     */
    public function set_core_exit($value)
    {
        switch ($value) {
            case '':
                $this->core_exit = '';
                break;
            case 'suspend':
                $this->core_exit = 'suspend';
                break;
            default:
                $this->core_exit = 'none';
                break;
        }
        return true;
    }

    /**
     * Sets the item's description
     * @param    string  $string  Description
     *
     * @return  void
     */
    public function set_description($string = '')
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::set_description()', 0);
        }
        if (!empty($string)) {
            $this->description = $string;
        }
    }

    /**
     * Sets the lesson_location value
     * @param    string  $location  lesson_location as provided by the SCO
     * @return    boolean    True on success, false otherwise
     */
    public function set_lesson_location($location)
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::set_lesson_location()', 0);
        }
        if (isset($location)) {
            $this->lesson_location = $location;
            return true;
        }
        return false;
    }

    /**
     * Sets the item's depth level in the LP tree (0 is at root)
     * @param    integer $int   Level
     * @return  void
     */
    public function set_level($int = 0)
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::set_level('.$int.')', 0);
        }
        if (!empty($int) && $int == strval(intval($int))) {
            $this->level = $int;
        }
    }

    /**
     * Sets the lp_view id this item view is registered to
     * @param int $lp_view_id   lp_view DB ID
     * @param int $course_id
     * @return  bool
     * @todo //todo insert into lp_item_view if lp_view not exists
     */
    public function set_lp_view($lp_view_id, $course_id = null)
    {
        $lp_view_id = intval($lp_view_id);

        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        } else {
            $course_id = intval($course_id);
        }

        $lpItemId = $this->get_id();

        if (empty($lpItemId)) {
            if (self::DEBUG > 0) {
                error_log(
                    'learnpathItem::set_lp_view('.$lp_view_id.') $lpItemId is empty',
                    0
                );
            }

            return false;
        }

        if (empty($lp_view_id)) {
            if (self::DEBUG > 0) {
                error_log(
                    'learnpathItem::set_lp_view('.$lp_view_id.') $lp_view_id is empty',
                    0
                );
            }

            return false;
        }

        if (self::DEBUG > 0) {
            error_log('learnpathItem::set_lp_view('.$lp_view_id.')', 0);
        }

        $this->view_id = $lp_view_id;

        $item_view_table = Database::get_course_table(TABLE_LP_ITEM_VIEW);
        // Get the lp_item_view with the highest view_count.
        $sql = "SELECT * FROM $item_view_table
                WHERE
                    c_id = $course_id AND
                    lp_item_id = ".$lpItemId." AND
                    lp_view_id = " . $lp_view_id."
                ORDER BY view_count DESC";

        if (self::DEBUG > 2) {
            error_log(
                'learnpathItem::set_lp_view() - Querying lp_item_view: '.$sql,
                0
            );
        }
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res);
            $this->db_item_view_id = $row['id'];
            $this->attempt_id = $row['view_count'];
            $this->current_score = $row['score'];
            $this->current_data = $row['suspend_data'];
            $this->view_max_score = $row['max_score'];
            $this->status = $row['status'];
            $this->current_start_time = $row['start_time'];
            $this->current_stop_time = $this->current_start_time + $row['total_time'];
            $this->lesson_location = $row['lesson_location'];
            $this->core_exit = $row['core_exit'];

            if (self::DEBUG > 2) {
                error_log(
                    'learnpathItem::set_lp_view() - Updated item object with database values',
                    0
                );
            }

            // Now get the number of interactions for this little guy.
            $item_view_interaction_table = Database::get_course_table(
                TABLE_LP_IV_INTERACTION
            );
            $sql = "SELECT * FROM $item_view_interaction_table
                    WHERE
                        c_id = $course_id AND
                        lp_iv_id = '".$this->db_item_view_id."'";

            $res = Database::query($sql);
            if ($res !== false) {
                $this->interactions_count = Database::num_rows($res);
            } else {
                $this->interactions_count = 0;
            }
            // Now get the number of objectives for this little guy.
            $item_view_objective_table = Database::get_course_table(
                TABLE_LP_IV_OBJECTIVE
            );
            $sql = "SELECT * FROM $item_view_objective_table
                    WHERE
                        c_id = $course_id AND
                        lp_iv_id = '".$this->db_item_view_id."'";

            $res = Database::query($sql);
            if ($res !== false) {
                $this->objectives_count = Database::num_rows($res);
            } else {
                $this->objectives_count = 0;
            }
        }

        // End
        if (self::DEBUG > 2) {
            error_log('New LP - End of learnpathItem::set_lp_view()', 0);
        }

        return true;
    }

    /**
     * Sets the path
     * @param   string $string Path
     * @return  void
     */
    public function set_path($string = '')
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::set_path()', 0);
        }
        if (!empty($string)) {
            $this->path = $string;
        }
    }

    /**
     * Sets the prevent_reinit attribute. This is based on the LP value and is set at creation time for
     * each learnpathItem. It is a (bad?) way of avoiding a reference to the LP when saving an item.
     * @param   integer 1 for "prevent", 0 for "don't prevent" saving freshened values (new "not attempted" status etc)
     * @return  void
     */
    public function set_prevent_reinit($prevent)
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::set_prevent_reinit()', 0);
        }
        if ($prevent) {
            $this->prevent_reinit = 1;
        } else {
            $this->prevent_reinit = 0;
        }
    }

    /**
     * Sets the score value. If the mastery_score is set and the score reaches
     * it, then set the status to 'passed'.
     * @param   float    $score   Score
     * @return    boolean   True on success, false otherwise
     */
    public function set_score($score)
    {
        $debug = self::DEBUG;
        if ($debug > 0) {
            error_log('learnpathItem::set_score('.$score.')', 0);
        }
        if (($this->max_score <= 0 || $score <= $this->max_score) && ($score >= $this->min_score)) {
            $this->current_score = $score;
            $masteryScore = $this->get_mastery_score();
            $current_status = $this->get_status(false);

            // Fixes bug when SCORM doesn't send a mastery score even if they sent a score!
            if ($masteryScore == -1) {
                $masteryScore = $this->max_score;
            }

            if ($debug > 0) {
                error_log('get_mastery_score: '.$masteryScore);
                error_log('current_status: '.$current_status);
                error_log('current score : '.$this->current_score);
            }

            // If mastery_score is set AND the current score reaches the mastery
            //  score AND the current status is different from 'completed', then
            //  set it to 'passed'.
            /*
            if ($master != -1 && $this->current_score >= $master && $current_status != $this->possible_status[2]) {
                if ($debug > 0) error_log('Status changed to: '.$this->possible_status[3]);
                $this->set_status($this->possible_status[3]); //passed
            } elseif ($master != -1 && $this->current_score < $master) {
                if ($debug > 0) error_log('Status changed to: '.$this->possible_status[4]);
                $this->set_status($this->possible_status[4]); //failed
            }*/
            return true;
        }
        return false;
    }

    /**
     * Sets the maximum score for this item
     * @param  int $score Maximum score - must be a decimal or an empty string
     *
     * @return boolean    True on success, false on error
     */
    public function set_max_score($score)
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::set_max_score('.$score.')', 0);
        }
        if (is_int($score) || $score == '') {
            $this->view_max_score = $score;
            if (self::DEBUG > 1) {
                error_log(
                    'learnpathItem::set_max_score() - '.
                    'Updated object score of item '.$this->db_id.
                    ' to '.$this->view_max_score,
                    0
                );
            }
            return true;
        }
        return false;
    }

    /**
     * Sets the status for this item
     * @param    string $status Status - must be one of the values defined in $this->possible_status
     * (this affects the status setting)
     * @return    boolean    True on success, false on error
     */
    public function set_status($status)
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::set_status('.$status.')', 0);
        }

        $found = false;
        foreach ($this->possible_status as $possible) {
            if (preg_match('/^'.$possible.'$/i', $status)) {
                $found = true;
            }
        }

        if ($found) {
            $this->status = $status;
            if (self::DEBUG > 1) {
                error_log(
                    'learnpathItem::set_status() - '.
                        'Updated object status of item '.$this->db_id.
                        ' to '.$this->status,
                    0
                );
            }
            return true;
        }

        $this->status = $this->possible_status[0];

        return false;
    }

    /**
     * Set the (indexing) terms for this learnpath item
     * @param   string  $terms Terms, as a comma-split list
     * @return  boolean Always return true
     */
    public function set_terms($terms)
    {
        global $charset;
        $course_id = api_get_course_int_id();
        $lp_item = Database::get_course_table(TABLE_LP_ITEM);
        require_once api_get_path(LIBRARY_PATH).'search/ChamiloIndexer.class.php';
        $a_terms = preg_split('/,/', $terms);
        $i_terms = preg_split('/,/', $this->get_terms());
        foreach ($i_terms as $term) {
            if (!in_array($term, $a_terms)) {
                array_push($a_terms, $term);
            }
        }
        $new_terms = $a_terms;
        $new_terms_string = implode(',', $new_terms);

        // TODO: Validate csv string.
        $terms = Database::escape_string(api_htmlentities($new_terms_string, ENT_QUOTES, $charset));
        $sql = "UPDATE $lp_item
                SET terms = '$terms'
                WHERE
                    c_id = $course_id AND
                    id=".$this->get_id();
        Database::query($sql);
        // Save it to search engine.
        if (api_get_setting('search_enabled') == 'true') {
            $di = new ChamiloIndexer();
            $di->update_terms($this->get_search_did(), $new_terms, 'T');
        }
        return true;
    }

    /**
     * Get the document ID from inside the text index database
     * @return  int     Search index database document ID
     */
    public function get_search_did()
    {
        return $this->search_did;
    }

    /**
     * Sets the item viewing time in a usable form, given that SCORM packages
     * often give it as 00:00:00.0000
     * @param    string    Time as given by SCORM
     * @return  void
     */
    public function set_time($scorm_time, $format = 'scorm')
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::set_time('.$scorm_time.')', 0);
        }

        if ($scorm_time == '0' &&
            $this->type != 'sco' &&
            $this->current_start_time != 0
        ) {
            $my_time = time() - $this->current_start_time;
            if ($my_time > 0) {
                $this->update_time($my_time);
                if (self::DEBUG > 0) {
                    error_log(
                        'learnpathItem::set_time('.$scorm_time.') - '.
                            'found asset - set time to '.$my_time,
                        0
                    );
                }
            }
        } else {
            switch ($format) {
                case 'scorm':
                    $res = array();
                    if (preg_match(
                        '/^(\d{1,4}):(\d{2}):(\d{2})(\.\d{1,4})?/',
                        $scorm_time,
                        $res
                    )
                    ) {
                        $hour = $res[1];
                        $min = $res[2];
                        $sec = $res[3];
                        // Getting total number of seconds spent.
                        $total_sec = $hour * 3600 + $min * 60 + $sec;
                        $this->scorm_update_time($total_sec);
                    }
                    break;
                case 'int':
                    $this->scorm_update_time($scorm_time);
                    break;
            }
        }
    }

    /**
     * Sets the item's title
     * @param    string  $string  Title
     * @return  void
     */
    public function set_title($string = '')
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::set_title()', 0);
        }
        if (!empty($string)) {
            $this->title = $string;
        }
    }

    /**
     * Sets the item's type
     * @param    string  $string  Type
     * @return  void
     */
    public function set_type($string = '')
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::set_type()', 0);
        }
        if (!empty($string)) {
            $this->type = $string;
        }
    }

    /**
     * Checks if the current status is part of the list of status given
     * @param  array  $list  An array of status to check for.
     * If the current status is one of the strings, return true
     *
     * @return boolean True if the status was one of the given strings,
     * false otherwise
     */
    public function status_is($list = array())
    {
        if (self::DEBUG > 1) {
            error_log(
                'learnpathItem::status_is('.print_r(
                    $list,
                    true
                ).') on item '.$this->db_id,
                0
            );
        }
        $currentStatus = $this->get_status(true);
        if (empty($currentStatus)) {
            return false;
        }
        $found = false;
        foreach ($list as $status) {
            if (preg_match('/^'.$status.'$/i', $currentStatus)) {
                if (self::DEBUG > 2) {
                    error_log(
                        'New LP - learnpathItem::status_is() - Found status '.
                            $status.' corresponding to current status',
                        0
                    );
                }
                $found = true;

                return $found;
            }
        }
        if (self::DEBUG > 2) {
            error_log(
                'New LP - learnpathItem::status_is() - Status '.
                    $currentStatus.' did not match request',
                0
            );
        }

        return $found;
    }

    /**
     * Updates the time info according to the given session_time
     * @param    integer  $total_sec  Time in seconds
     * @return  void
     * TODO: Make this method better by allowing better/multiple time slices.
     */
    public function update_time($total_sec = 0)
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::update_time('.$total_sec.')', 0);
        }
        if ($total_sec >= 0) {
            // Getting start time from finish time. The only problem in the calculation is it might be
            // modified by the scripts processing time.
            $now = time();
            $start = $now - $total_sec;
            $this->current_start_time = $start;
            $this->current_stop_time = $now;
            /*if (empty($this->current_start_time)) {
                $this->current_start_time = $start;
                $this->current_stop_time  = $now;
            } else {
                //if ($this->current_stop_time != $this->current_start_time) {
                    // If the stop time has already been set before to something else
                    // than the start time, add the given time to what's already been
                    // recorder.
                    // This is the SCORM way of doing things, because the time comes from
                    // core.session_time, not core.total_time
                    // UPDATE: adding time to previous time is only done on SCORM's finish()
                    // call, not normally, so for now ignore this section.
                    //$this->current_stop_time = $this->current_stop_time + $stop;
                    //error_log('New LP - Adding '.$stop.' seconds - now '.$this->current_stop_time, 0);
                //} else {
                    // If no previous stop time set, use the one just calculated now from
                    // start time.
                    //$this->current_start_time = $start;
                    //$this->current_stop_time  = $now;
                    //error_log('New LP - Setting '.$stop.' seconds - now '.$this->current_stop_time, 0);
                //}
            }*/
        }
    }

    /**
     * Special scorm update time function. This function will update time
     * directly into db for scorm objects
     * @param   int $total_sec  Total number of seconds
     **/
    public function scorm_update_time($total_sec = 0)
    {
        if (self::DEBUG > 0) {
            error_log('Funcion called: scorm_update_time');
            error_log("total_sec: $total_sec");
        }

        // Step 1 : get actual total time stored in db
        $item_view_table = Database::get_course_table(TABLE_LP_ITEM_VIEW);
        $course_id = api_get_course_int_id();

        $sql = 'SELECT total_time, status 
                FROM '.$item_view_table.'
                WHERE 
                    c_id = '.$course_id.' AND 
                    lp_item_id = "'.$this->db_id.'" AND 
                    lp_view_id = "'.$this->view_id.'" AND 
                    view_count = "'.$this->get_attempt_id().'"';
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        if (!isset($row['total_time'])) {
            $total_time = 0;
        } else {
            $total_time = $row['total_time'];
        }
        if (self::DEBUG > 0) {
            error_log("total_time: $total_time");
        }

        $lp_table = Database::get_course_table(TABLE_LP_MAIN);
        $lp_id = intval($this->lp_id);
        $sql = "SELECT * FROM $lp_table
                WHERE id = $lp_id AND c_id = $course_id";
        $res = Database::query($sql);
        $accumulateScormTime = 'false';
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_assoc($res);
            $accumulateScormTime = $row['accumulate_scorm_time'];
        }

        // Step 2.1 : if normal mode total_time = total_time + total_sec
        if ($accumulateScormTime != 0) {
            $total_time += $total_sec;
        } else {
            // Step 2.2 : if not cumulative mode total_time = total_time - last_update + total_sec
            $total_time = $total_time - $this->last_scorm_session_time + $total_sec;
            $this->last_scorm_session_time = $total_sec;

            if ($total_time < 0) {
                $total_time = $total_sec;
            }
        }

        // Step 3 update db only if status != completed, passed, browsed or seriousgamemode not activated
        // @todo complete
        $case_completed = array(
            'completed',
            'passed',
            'browsed',
            'failed'
        );

        if ($this->seriousgame_mode != 1 ||
            !in_array($row['status'], $case_completed)
        ) {
            $sql = "UPDATE $item_view_table
                      SET total_time = '$total_time'
                    WHERE 
                        c_id = $course_id AND 
                        lp_item_id = {$this->db_id} AND 
                        lp_view_id = {$this->view_id} AND 
                        view_count = {$this->get_attempt_id()}";
            if (self::DEBUG > 0) {
                error_log($sql);
            }
            Database::query($sql);
        }
    }

    /**
     * Set the total_time to 0 into db
     **/
    public function scorm_init_time()
    {
        $item_view_table = Database::get_course_table(TABLE_LP_ITEM_VIEW);
        $course_id = api_get_course_int_id();
        $sql = 'UPDATE '.$item_view_table.'
                SET total_time = 0, 
                    start_time=' . time().'
                WHERE c_id = ' . $course_id.'
                    AND lp_item_id="' . $this->db_id.'"
                    AND lp_view_id="' . $this->view_id.'"
                    AND view_count="' . $this->attempt_id.'" ;';
        Database::query($sql);
    }

    /**
     * Write objectives to DB. This method is separate from write_to_db() because otherwise
     * objectives are lost as a side effect to AJAX and session concurrent access
     * @return    boolean        True or false on error
     */
    public function write_objectives_to_db()
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::write_objectives_to_db()', 0);
        }
        $course_id = api_get_course_int_id();
        if (is_array($this->objectives) && count($this->objectives) > 0) {
            // Save objectives.
            $tbl = Database::get_course_table(TABLE_LP_ITEM_VIEW);
            $sql = "SELECT id
                    FROM $tbl
                    WHERE
                        c_id = $course_id AND
                        lp_item_id = ".$this->db_id." AND
                        lp_view_id = " . $this->view_id." AND
                        view_count = " . $this->attempt_id;
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                $row = Database::fetch_array($res);
                $lp_iv_id = $row[0];
                if (self::DEBUG > 2) {
                    error_log(
                        'learnpathItem::write_to_db() - Got item_view_id '.
                            $lp_iv_id.', now checking objectives ',
                        0
                    );
                }
                foreach ($this->objectives as $index => $objective) {
                    $iva_table = Database::get_course_table(
                        TABLE_LP_IV_OBJECTIVE
                    );
                    $iva_sql = "SELECT id FROM $iva_table
                                WHERE
                                    c_id = $course_id AND
                                    lp_iv_id = $lp_iv_id AND
                                    objective_id = '".Database::escape_string($objective[0])."'";
                    $iva_res = Database::query($iva_sql);
                    // id(0), type(1), time(2), weighting(3),
                    // correct_responses(4), student_response(5),
                    // result(6), latency(7)
                    if (Database::num_rows($iva_res) > 0) {
                        // Update (or don't).
                        $iva_row = Database::fetch_array($iva_res);
                        $iva_id = $iva_row[0];
                        $ivau_sql = "UPDATE $iva_table ".
                            "SET objective_id = '".Database::escape_string($objective[0])."',".
                            "status = '".Database::escape_string($objective[1])."',".
                            "score_raw = '".Database::escape_string($objective[2])."',".
                            "score_min = '".Database::escape_string($objective[4])."',".
                            "score_max = '".Database::escape_string($objective[3])."' ".
                            "WHERE c_id = $course_id AND id = $iva_id";
                        Database::query($ivau_sql);
                    } else {
                        // Insert new one.
                        $params = array(
                            'c_id' => $course_id,
                            'lp_iv_id' => $lp_iv_id,
                            'order_id' => $index,
                            'objective_id' => $objective[0],
                            'status' => $objective[1],
                            'score_raw' => $objective[2],
                            'score_min' => $objective[4],
                            'score_max' => $objective[3]
                        );

                        $insertId = Database::insert($iva_table, $params);

                        $sql = "UPDATE $iva_table SET id = iid WHERE iid = $insertId";
                        Database::query($sql);
                    }
                }
            }
        }
    }

    /**
     * Writes the current data to the database
     * @return    boolean    Query result
     */
    public function write_to_db()
    {
        if (self::DEBUG > 0) {
            error_log('learnpathItem::write_to_db()', 0);
        }

        // Check the session visibility.
        if (!api_is_allowed_to_session_edit()) {
            if (self::DEBUG > 0) {
                error_log('return false api_is_allowed_to_session_edit');
            }

            return false;
        }

        $course_id = api_get_course_int_id();
        $mode = $this->get_lesson_mode();
        $credit = $this->get_credit();
        $total_time = ' ';
        $my_status = ' ';

        $item_view_table = Database::get_course_table(TABLE_LP_ITEM_VIEW);
        $sql = 'SELECT status FROM '.$item_view_table.'
                WHERE
                    c_id = ' . $course_id.' AND
                    lp_item_id="' . $this->db_id.'" AND
                    lp_view_id="' . $this->view_id.'" AND
                    view_count="' . $this->get_attempt_id().'" ';
        $rs_verified = Database::query($sql);
        $row_verified = Database::fetch_array($rs_verified);

        $my_case_completed = array(
            'completed',
            'passed',
            'browsed',
            'failed'
        );

        $save = true;

        if (isset($row_verified) && isset($row_verified['status'])) {
            if (in_array($row_verified['status'], $my_case_completed)) {
                $save = false;
            }
        }

        if ((($save === false && $this->type == 'sco') ||
           ($this->type == 'sco' && ($credit == 'no-credit' || $mode == 'review' || $mode == 'browse'))) &&
            ($this->seriousgame_mode != 1 && $this->type == 'sco')
        ) {
            if (self::DEBUG > 1) {
                error_log(
                    "This info shouldn't be saved as the credit or lesson mode info prevent it"
                );
                error_log(
                    'learnpathItem::write_to_db() - credit('.$credit.') or'.
                        ' lesson_mode('.$mode.') prevent recording!',
                    0
                );
            }
        } else {
            // Check the row exists.
            $inserted = false;

            // This a special case for multiple attempts and Chamilo exercises.
            if ($this->type == 'quiz' &&
                $this->get_prevent_reinit() == 0 &&
                $this->get_status() == 'completed'
            ) {
                // We force the item to be restarted.
                $this->restart();
                $params = array(
                    "c_id" => $course_id,
                    "total_time" => $this->get_total_time(),
                    "start_time" => $this->current_start_time,
                    "score" => $this->get_score(),
                    "status" => $this->get_status(false),
                    "max_score" => $this->get_max(),
                    "lp_item_id" => $this->db_id,
                    "lp_view_id" => $this->view_id,
                    "view_count" => $this->get_attempt_id(),
                    "suspend_data" => $this->current_data,
                    //"max_time_allowed" => ,
                    "lesson_location" => $this->lesson_location
                );
                if (self::DEBUG > 2) {
                    error_log(
                        'learnpathItem::write_to_db() - Inserting into item_view forced: '.print_r($params, 1),
                        0
                    );
                }
                $this->db_item_view_id = Database::insert($item_view_table, $params);

                if ($this->db_item_view_id) {
                    $sql = "UPDATE $item_view_table SET id = iid
                            WHERE iid = ".$this->db_item_view_id;
                    Database::query($sql);
                    $inserted = true;
                }
            }

            $item_view_table = Database::get_course_table(TABLE_LP_ITEM_VIEW);
            $sql = "SELECT * FROM $item_view_table
                    WHERE
                        c_id = $course_id AND
                        lp_item_id = ".$this->db_id." AND
                        lp_view_id = " . $this->view_id." AND
                        view_count = " . intval($this->get_attempt_id());
            if (self::DEBUG > 2) {
                error_log(
                    'learnpathItem::write_to_db() - Querying item_view: '.$sql,
                    0
                );
            }
            $check_res = Database::query($sql);
            // Depending on what we want (really), we'll update or insert a new row
            // now save into DB.
            if (!$inserted && Database::num_rows($check_res) < 1) {
                $params = array(
                    "c_id" => $course_id,
                    "total_time" => $this->get_total_time(),
                    "start_time" => $this->current_start_time,
                    "score" => $this->get_score(),
                    "status" => $this->get_status(false),
                    "max_score" => $this->get_max(),
                    "lp_item_id" => $this->db_id,
                    "lp_view_id" => $this->view_id,
                    "view_count" => $this->get_attempt_id(),
                    "suspend_data" => $this->current_data,
                    //"max_time_allowed" => ,$this->get_max_time_allowed()
                    "lesson_location" => $this->lesson_location
                );

                if (self::DEBUG > 2) {
                    error_log(
                        'learnpathItem::write_to_db() - Inserting into item_view forced: '.print_r($params, 1),
                        0
                    );
                }

                $this->db_item_view_id = Database::insert($item_view_table, $params);

                if ($this->db_item_view_id) {
                    $sql = "UPDATE $item_view_table SET id = iid
                            WHERE iid = ".$this->db_item_view_id;
                    Database::query($sql);
                }
            } else {
                if ($this->type == 'hotpotatoes') {
                    $params = array(
                        'total_time' => $this->get_total_time(),
                        'start_time' => $this->get_current_start_time(),
                        'score' =>  $this->get_score(),
                        'status' => $this->get_status(false),
                        'max_score' => $this->get_max(),
                        'suspend_data' => $this->current_data,
                        'lesson_location' => $this->lesson_location
                    );
                    $where = array(
                        'c_id = ? AND lp_item_id = ? AND lp_view_id = ? AND view_count = ?' =>
                            array($course_id, $this->db_id, $this->view_id, $this->get_attempt_id())
                    );
                    Database::update($item_view_table, $params, $where);
                } else {
                    // For all other content types...
                    if ($this->type == 'quiz') {
                        $my_status = ' ';
                        $total_time = ' ';
                        if (!empty($_REQUEST['exeId'])) {
                            $TBL_TRACK_EXERCICES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

                            $safe_exe_id = intval($_REQUEST['exeId']);
                            $sql = "SELECT start_date,exe_date
                                    FROM $TBL_TRACK_EXERCICES
                                    WHERE exe_id = $safe_exe_id";
                            $res = Database::query($sql);
                            $row_dates = Database::fetch_array($res);

                            $time_start_date = convert_sql_date(
                                $row_dates['start_date']
                            );
                            $time_exe_date = convert_sql_date(
                                $row_dates['exe_date']
                            );
                            $mytime = ((int) $time_exe_date - (int) $time_start_date);
                            $total_time = " total_time = ".$mytime.", ";
                        }
                    } else {
                        $my_type_lp = learnpath::get_type_static($this->lp_id);
                        // This is a array containing values finished
                        $case_completed = array(
                            'completed',
                            'passed',
                            'browsed',
                            'failed'
                        );

                        // Is not multiple attempts
                        if ($this->seriousgame_mode == 1 && $this->type == 'sco') {
                            $total_time = " total_time = total_time +".$this->get_total_time().", ";
                            $my_status = " status = '".$this->get_status(false)."' ,";
                        } elseif ($this->get_prevent_reinit() == 1) {
                            // Process of status verified into data base.
                            $sql = 'SELECT status FROM '.$item_view_table.'
                                    WHERE
                                        c_id = ' . $course_id.' AND
                                        lp_item_id="' . $this->db_id.'" AND
                                        lp_view_id="' . $this->view_id.'" AND
                                        view_count="' . $this->get_attempt_id().'"
                                    ';
                            $rs_verified = Database::query($sql);
                            $row_verified = Database::fetch_array($rs_verified);

                            // Get type lp: 1=lp dokeos and  2=scorm.
                            // If not is completed or passed or browsed and learning path is scorm.
                            if (!in_array($this->get_status(false), $case_completed) &&
                                $my_type_lp == 2
                            ) {
                                $total_time = " total_time = total_time +".$this->get_total_time().", ";
                                $my_status = " status = '".$this->get_status(false)."' ,";
                            } else {
                                // Verified into database.
                                if (!in_array($row_verified['status'], $case_completed) &&
                                    $my_type_lp == 2
                                ) {
                                    $total_time = " total_time = total_time +".$this->get_total_time().", ";
                                    $my_status = " status = '".$this->get_status(false)."' ,";
                                } elseif (in_array($row_verified['status'], $case_completed) &&
                                    $my_type_lp == 2 && $this->type != 'sco'
                                ) {
                                    $total_time = " total_time = total_time +".$this->get_total_time().", ";
                                    $my_status = " status = '".$this->get_status(false)."' ,";
                                } else {
                                    if (($my_type_lp == 3 && $this->type == 'au') ||
                                        ($my_type_lp == 1 && $this->type != 'dir')) {
                                        // Is AICC or Chamilo LP
                                        $total_time = " total_time = total_time + ".$this->get_total_time().", ";
                                        $my_status = " status = '".$this->get_status(false)."' ,";
                                    }
                                }
                            }
                        } else {
                            // Multiple attempts are allowed.
                            if (in_array(
                                    $this->get_status(false),
                                    $case_completed
                                ) && $my_type_lp == 2
                            ) {
                                // Reset zero new attempt ?
                                $my_status = " status = '".$this->get_status(false)."' ,";
                            } elseif (!in_array($this->get_status(false), $case_completed) &&
                                $my_type_lp == 2
                            ) {
                                $total_time = " total_time = ".$this->get_total_time().", ";
                                $my_status = " status = '".$this->get_status(false)."' ,";
                            } else {
                                // It is chamilo LP.
                                $total_time = " total_time = total_time +".$this->get_total_time().", ";
                                $my_status = " status = '".$this->get_status(false)."' ,";
                            }

                            // This code line fixes the problem of wrong status.
                            if ($my_type_lp == 2) {
                                // Verify current status in multiples attempts.
                                $sql = 'SELECT status FROM '.$item_view_table.'
                                        WHERE
                                            c_id = ' . $course_id.' AND
                                            lp_item_id="' . $this->db_id.'" AND
                                            lp_view_id="' . $this->view_id.'" AND
                                            view_count="' . $this->get_attempt_id().'" ';
                                $rs_status = Database::query($sql);
                                $current_status = Database::result(
                                    $rs_status,
                                    0,
                                    'status'
                                );
                                if (in_array($current_status, $case_completed)) {
                                    $my_status = '';
                                    $total_time = '';
                                } else {
                                    $total_time = " total_time = total_time +".$this->get_total_time().", ";
                                }
                            }
                        }
                    }

                    if ($this->type == 'sco') {
                        //IF scorm scorm_update_time has already updated total_time in db
                        //" . //start_time = ".$this->get_current_start_time().", " . //scorm_init_time does it
                        ////" max_time_allowed = '".$this->get_max_time_allowed()."'," .
                        $sql = "UPDATE $item_view_table SET
                                    score = ".$this->get_score().",
                                    $my_status
                                    max_score = '".$this->get_max()."',
                                    suspend_data = '" . Database::escape_string($this->current_data)."',
                                    lesson_location = '" . $this->lesson_location."'
                                WHERE
                                    c_id = $course_id AND
                                    lp_item_id = ".$this->db_id." AND
                                    lp_view_id = " . $this->view_id."  AND
                                    view_count = " . $this->get_attempt_id();

                    } else {
                        //" max_time_allowed = '".$this->get_max_time_allowed()."'," .
                        $sql = "UPDATE $item_view_table SET
                                    $total_time
                                    start_time = ".$this->get_current_start_time().",
                                    score = " . $this->get_score().",
                                    $my_status
                                    max_score = '".$this->get_max()."',
                                    suspend_data = '" . Database::escape_string($this->current_data)."',
                                    lesson_location = '" . $this->lesson_location."'
                                WHERE
                                    c_id = $course_id AND
                                    lp_item_id = ".$this->db_id." AND
                                    lp_view_id = " . $this->view_id." AND
                                    view_count = " . $this->get_attempt_id();
                    }
                    $this->current_start_time = time();
                }
                if (self::DEBUG > 2) {
                    error_log(
                        'learnpathItem::write_to_db() - Updating item_view: '.$sql,
                        0
                    );
                }
                Database::query($sql);
            }

            if (is_array($this->interactions) && count($this->interactions) > 0) {
                // Save interactions.
                $tbl = Database::get_course_table(TABLE_LP_ITEM_VIEW);
                $sql = "SELECT id FROM $tbl
                        WHERE
                            c_id = $course_id AND
                            lp_item_id = ".$this->db_id." AND
                            lp_view_id = " . $this->view_id." AND
                            view_count = " . $this->get_attempt_id();
                $res = Database::query($sql);
                if (Database::num_rows($res) > 0) {
                    $row = Database::fetch_array($res);
                    $lp_iv_id = $row[0];
                    if (self::DEBUG > 2) {
                        error_log(
                            'learnpathItem::write_to_db() - Got item_view_id '.
                                $lp_iv_id.', now checking interactions ',
                            0
                        );
                    }
                    foreach ($this->interactions as $index => $interaction) {
                        $correct_resp = '';
                        if (is_array($interaction[4]) && !empty($interaction[4][0])) {
                            foreach ($interaction[4] as $resp) {
                                $correct_resp .= $resp.',';
                            }
                            $correct_resp = substr(
                                $correct_resp,
                                0,
                                strlen($correct_resp) - 1
                            );
                        }
                        $iva_table = Database::get_course_table(
                            TABLE_LP_IV_INTERACTION
                        );

                        //also check for the interaction ID as it must be unique for this SCO view
                        $iva_sql = "SELECT id FROM $iva_table
                                    WHERE
                                        c_id = $course_id AND
                                        lp_iv_id = $lp_iv_id AND
                                        (
                                            order_id = $index OR
                                            interaction_id = '".Database::escape_string($interaction[0])."'
                                        )
                                    ";
                        $iva_res = Database::query($iva_sql);

                        $interaction[0] = isset($interaction[0]) ? $interaction[0] : '';
                        $interaction[1] = isset($interaction[1]) ? $interaction[1] : '';
                        $interaction[2] = isset($interaction[2]) ? $interaction[2] : '';
                        $interaction[3] = isset($interaction[3]) ? $interaction[3] : '';
                        $interaction[4] = isset($interaction[4]) ? $interaction[4] : '';
                        $interaction[5] = isset($interaction[5]) ? $interaction[5] : '';
                        $interaction[6] = isset($interaction[6]) ? $interaction[6] : '';
                        $interaction[7] = isset($interaction[7]) ? $interaction[7] : '';

                        // id(0), type(1), time(2), weighting(3), correct_responses(4), student_response(5), result(6), latency(7)
                        if (Database::num_rows($iva_res) > 0) {
                            // Update (or don't).
                            $iva_row = Database::fetch_array($iva_res);
                            $iva_id = $iva_row[0];
                            // Insert new one.
                            $params = array(
                                'interaction_id' => $interaction[0],
                                'interaction_type' => $interaction[1],
                                'weighting' => $interaction[3],
                                'completion_time' => $interaction[2],
                                'correct_responses' => $correct_resp,
                                'student_response' => $interaction[5],
                                'result' => $interaction[6],
                                'latency' => $interaction[7]
                            );
                            Database::update(
                                $iva_table,
                                $params,
                                array(
                                    'c_id = ? AND id = ?' => array(
                                        $course_id,
                                        $iva_id
                                    )
                                )
                            );
                        } else {
                            // Insert new one.
                            $params = array(
                                'c_id' => $course_id,
                                'order_id' => $index,
                                'lp_iv_id' => $lp_iv_id,
                                'interaction_id' => $interaction[0],
                                'interaction_type' => $interaction[1],
                                'weighting' => $interaction[3],
                                'completion_time' => $interaction[2],
                                'correct_responses' => $correct_resp,
                                'student_response' => $interaction[5],
                                'result' => $interaction[6],
                                'latency' => $interaction[7]
                            );

                            $insertId = Database::insert($iva_table, $params);
                            if ($insertId) {
                                $sql = "UPDATE $iva_table SET id = iid
                                        WHERE iid = $insertId";
                                Database::query($sql);
                            }
                        }
                    }
                }
            }
        }

        if (self::DEBUG > 2) {
            error_log('End of learnpathItem::write_to_db()', 0);
        }

        return true;
    }

    /**
     * Adds an audio file attached to the current item (store on disk and in db)
     * @return bool|null|string
     */
    public function add_audio()
    {
        $course_info = api_get_course_info();
        $filepath = api_get_path(SYS_COURSE_PATH).$course_info['path'].'/document/';

        if (!is_dir($filepath.'audio')) {
            mkdir(
                $filepath.'audio',
                api_get_permissions_for_new_directories()
            );
            $audio_id = add_document(
                $course_info,
                '/audio',
                'folder',
                0,
                'audio'
            );
            api_item_property_update(
                $course_info,
                TOOL_DOCUMENT,
                $audio_id,
                'FolderCreated',
                api_get_user_id(),
                null,
                null,
                null,
                null,
                api_get_session_id()
            );
            api_item_property_update(
                $course_info,
                TOOL_DOCUMENT,
                $audio_id,
                'invisible',
                api_get_user_id(),
                null,
                null,
                null,
                null,
                api_get_session_id()
            );
        }

        $key = 'file';

        if (!isset($_FILES[$key]['name']) || !isset($_FILES[$key]['tmp_name'])) {
            return false;
        }
        $result = DocumentManager::upload_document(
            $_FILES,
            '/audio',
            null,
            null,
            0,
            'rename',
            false,
            false
        );
        $file_path = null;

        if ($result) {
            $file_path = basename($result['path']);

            // Store the mp3 file in the lp_item table.
            $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
            $sql = "UPDATE $tbl_lp_item SET
                        audio = '".Database::escape_string($file_path)."'
                    WHERE
                        c_id = {$course_info['real_id']} AND
                        id = '".intval($this->db_id)."'";
            Database::query($sql);
        }

        return $file_path;
    }

    /**
     * Adds an audio file to the current item, using a file already in documents
     * @param int $doc_id
     * @return string
     */
    public function add_audio_from_documents($doc_id)
    {
        $course_info = api_get_course_info();
        $document_data = DocumentManager::get_document_data_by_id(
            $doc_id,
            $course_info['code']
        );

        if (!empty($document_data)) {
            $file_path = basename($document_data['path']);
            // Store the mp3 file in the lp_item table.
            $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
            $sql = "UPDATE $tbl_lp_item SET
                        audio = '".Database::escape_string($file_path)."'
                    WHERE
                        c_id = {$course_info['real_id']} AND
                        id = ".intval($this->db_id);
            Database::query($sql);
        }
        return $file_path;
    }

    /**
     * Removes the relation between the current item and an audio file. The file
     * is only removed from the lp_item table, but remains in the document table
     * and directory
     * @return bool
     */
    public function remove_audio()
    {
        $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $course_id = api_get_course_int_id();
        if (empty($this->db_id)) {
            return false;
        }
        $sql = "UPDATE $tbl_lp_item SET
                audio = ''
                WHERE c_id = $course_id AND id IN (".$this->db_id.")";
        Database::query($sql);
    }

    /**
     * Transform the SCORM status to a string that can be translated by Chamilo
     * in different user languages
     * @param $status
     * @param bool $decorate
     * @param string $type classic|simple
     * @return array|string
     */
    static function humanize_status($status, $decorate = true, $type = 'classic')
    {
        $statusList = array(
            'completed' => 'ScormCompstatus',
            'incomplete' => 'ScormIncomplete',
            'failed' => 'ScormFailed',
            'passed' => 'ScormPassed',
            'browsed' => 'ScormBrowsed',
            'not attempted' => 'ScormNotAttempted'
        );

        $myLessonStatus = get_lang($statusList[$status]);

        switch ($status) {
            case 'completed':
            case 'browsed':
                $classStatus = 'info';
                break;
            case 'incomplete':
                $classStatus = 'warning';
                break;
            case 'passed':
                $classStatus = 'success';
                break;
            case 'failed':
                $classStatus = 'important';
                break;
            default:
                $classStatus = 'default';
                break;
        }

        if ($type == 'simple') {
            if (in_array($status, array('failed', 'passed', 'browsed'))) {
                $myLessonStatus = get_lang('ScormIncomplete'); ;
                $classStatus = 'warning';
            }
        }

        if ($decorate) {
            return Display::label($myLessonStatus, $classStatus);
        } else {
            return $myLessonStatus;
        }
    }

    /**
     * @return float
     */
    public function getPrerequisiteMaxScore()
    {
        return $this->prerequisiteMaxScore;
    }

    /**
     * @param float $prerequisiteMaxScore
     */
    public function setPrerequisiteMaxScore($prerequisiteMaxScore)
    {
        $this->prerequisiteMaxScore = $prerequisiteMaxScore;
    }

    /**
     * @return float
     */
    public function getPrerequisiteMinScore()
    {
        return $this->prerequisiteMinScore;
    }

    /**
     * @param float $prerequisiteMinScore
     */
    public function setPrerequisiteMinScore($prerequisiteMinScore)
    {
        $this->prerequisiteMinScore = $prerequisiteMinScore;
    }

    /**
     * Check if this LP item has a created thread in the basis course from the forum of its LP
     * @param int $lpCourseId The course ID
     * @return boolean
     */
    public function lpItemHasThread($lpCourseId)
    {
        $forumThreadTable = Database::get_course_table(TABLE_FORUM_THREAD);
        $itemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $fakeFrom = "
            $forumThreadTable ft
            INNER JOIN $itemProperty ip
            ON (ft.thread_id = ip.ref AND ft.c_id = ip.c_id)
        ";

        $resultData = Database::select(
            'COUNT(ft.iid) AS qty',
            $fakeFrom,
            [
                'where' => [
                    'ip.visibility != ? AND ' => 2,
                    'ip.tool = ? AND ' => TOOL_FORUM_THREAD,
                    'ft.c_id = ? AND ' => intval($lpCourseId),
                    '(ft.lp_item_id = ? OR (ft.thread_title = ? AND ft.lp_item_id = ?))' => [
                        intval($this->db_id),
                        "{$this->title} - {$this->db_id}",
                        intval($this->db_id)
                    ]
                ]
            ],
            'first'
        );

        if ($resultData['qty'] > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get the forum thread info
     * @param int $lpCourseId The course ID from the learning path
     * @param int $lpSessionId Optional. The session ID from the learning path
     * @return boolean
     */
    public function getForumThread($lpCourseId, $lpSessionId = 0)
    {
        $lpSessionId = intval($lpSessionId);
        $forumThreadTable = Database::get_course_table(TABLE_FORUM_THREAD);
        $itemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $fakeFrom = "$forumThreadTable ft
            INNER JOIN $itemProperty ip ";

        if ($lpSessionId == 0) {
            $fakeFrom .= "
                ON (
                    ft.thread_id = ip.ref AND ft.c_id = ip.c_id AND (
                        ft.session_id = ip.session_id OR ip.session_id IS NULL
                    )
                )
            ";
        } else {
            $fakeFrom .= "
                ON (
                    ft.thread_id = ip.ref AND ft.c_id = ip.c_id AND ft.session_id = ip.session_id
                )
            ";
        }

        $resultData = Database::select(
            'ft.*',
            $fakeFrom,
            [
                'where' => [
                    'ip.visibility != ? AND ' => 2,
                    'ip.tool = ? AND ' => TOOL_FORUM_THREAD,
                    'ft.session_id = ? AND ' => $lpSessionId,
                    'ft.c_id = ? AND ' => intval($lpCourseId),
                    '(ft.lp_item_id = ? OR (ft.thread_title = ? AND ft.lp_item_id = ?))' => [
                        intval($this->db_id),
                        "{$this->title} - {$this->db_id}",
                        intval($this->db_id)
                    ]
                ]
            ],
            'first'
        );

        if (empty($resultData)) {
            return false;
        }

        return $resultData;
    }

    /**
     * Create a forum thread for this learning path item
     * @param int $currentForumId The forum ID to add the new thread
     * @return int The forum thread if was created. Otherwise return false
     */
    public function createForumThread($currentForumId)
    {
        require_once api_get_path(SYS_CODE_PATH).'/forum/forumfunction.inc.php';

        $em = Database::getManager();
        $threadRepo = $em->getRepository('ChamiloCourseBundle:CForumThread');
        $forumThread = $threadRepo->findOneBy([
            'threadTitle' => "{$this->title} - {$this->db_id}",
            'forumId' => intval($currentForumId)
        ]);

        if (!$forumThread) {
            $forumInfo = get_forum_information($currentForumId);

            store_thread(
                $forumInfo,
                [
                    'forum_id' => intval($currentForumId),
                    'thread_id' => 0,
                    'gradebook' => 0,
                    'post_title' => "{$this->name} - {$this->db_id}",
                    'post_text' => $this->description,
                    'category_id' => 1,
                    'numeric_calification' => 0,
                    'calification_notebook_title' => 0,
                    'weight_calification' => 0.00,
                    'thread_peer_qualify' => 0,
                    'lp_item_id' => $this->db_id
                ],
                [],
                false
            );

            return;
        }

        $forumThread->setLpItemId($this->db_id);

        $em->persist($forumThread);
        $em->flush();
    }

    /**
     * Allow dissociate a forum to this LP item
     * @param int $threadIid The thread id
     * @return boolean
     */
    public function dissociateForumThread($threadIid)
    {
        $threadIid = intval($threadIid);
        $em = Database::getManager();

        $forumThread = $em->find('ChamiloCourseBundle:CForumThread', $threadIid);

        if (!$forumThread) {
            return false;
        }

        $forumThread->setThreadTitle(
            "{$this->get_title()} - {$this->db_id}"
        );
        $forumThread->setLpItemId(0);

        $em->persist($forumThread);
        $em->flush();

        return true;
    }
}

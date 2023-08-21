<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Repository\CourseRepository;
use Chamilo\CoreBundle\Entity\Repository\ItemPropertyRepository;
use Chamilo\CourseBundle\Component\CourseCopy\CourseArchiver;
use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CItemProperty;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\PluginBundle\Entity\H5pImport\H5pImport;
use Chamilo\PluginBundle\Entity\XApi\ToolLaunch;
use Chamilo\UserBundle\Entity\User;
use ChamiloSession as Session;
use Gedmo\Sortable\Entity\Repository\SortableRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Class learnpath
 * This class defines the parent attributes and methods for Chamilo learnpaths
 * and SCORM learnpaths. It is used by the scorm class.
 *
 * @todo decouple class
 *
 * @author  Yannick Warnier <ywarnier@beeznest.org>
 * @author  Julio Montoya   <gugli100@gmail.com> Several improvements and fixes
 */
class learnpath
{
    public const MAX_LP_ITEM_TITLE_LENGTH = 32;
    public const STATUS_CSS_CLASS_NAME = [
        'not attempted' => 'scorm_not_attempted',
        'incomplete' => 'scorm_not_attempted',
        'failed' => 'scorm_failed',
        'completed' => 'scorm_completed',
        'passed' => 'scorm_completed',
        'succeeded' => 'scorm_completed',
        'browsed' => 'scorm_completed',
    ];

    public $attempt = 0; // The number for the current ID view.
    public $cc; // Course (code) this learnpath is located in. @todo change name for something more comprensible ...
    public $current; // Id of the current item the user is viewing.
    public $current_score; // The score of the current item.
    public $current_time_start; // The time the user loaded this resource (this does not mean he can see it yet).
    public $current_time_stop; // The time the user closed this resource.
    public $default_status = 'not attempted';
    public $encoding = 'UTF-8';
    public $error = '';
    public $force_commit = false; // For SCORM only- if true will send a scorm LMSCommit() request on each LMSSetValue()
    public $index; // The index of the active learnpath_item in $ordered_items array.
    /** @var learnpathItem[] */
    public $items = [];
    public $last; // item_id of last item viewed in the learning path.
    public $last_item_seen = 0; // In case we have already come in this lp, reuse the last item seen if authorized.
    public $license; // Which license this course has been given - not used yet on 20060522.
    public $lp_id; // DB iid for this learnpath.
    public $lp_view_id; // DB ID for lp_view
    public $maker; // Which maker has conceived the content (ENI, Articulate, ...).
    public $message = '';
    public $mode = 'embedded'; // Holds the video display mode (fullscreen or embedded).
    public $name; // Learnpath name (they generally have one).
    public $ordered_items = []; // List of the learnpath items in the order they are to be read.
    public $path = ''; // Path inside the scorm directory (if scorm).
    public $theme; // The current theme of the learning path.
    public $preview_image; // The current image of the learning path.
    public $accumulateScormTime; // Flag to decide whether to accumulate SCORM time or not
    public $accumulateWorkTime; // The min time of learnpath

    // Tells if all the items of the learnpath can be tried again. Defaults to "no" (=1).
    public $prevent_reinit = 1;

    // Describes the mode of progress bar display.
    public $seriousgame_mode = 0;
    public $progress_bar_mode = '%';

    // Percentage progress as saved in the db.
    public $progress_db = 0;
    public $proximity; // Wether the content is distant or local or unknown.
    public $refs_list = []; //list of items by ref => db_id. Used only for prerequisites match.
    // !!!This array (refs_list) is built differently depending on the nature of the LP.
    // If SCORM, uses ref, if Chamilo, uses id to keep a unique value.
    public $type; //type of learnpath. Could be 'chamilo', 'scorm', 'scorm2004', 'aicc', ...
    // TODO: Check if this type variable is useful here (instead of just in the controller script).
    public $user_id; //ID of the user that is viewing/using the course
    public $update_queue = [];
    public $scorm_debug = 0;
    public $arrMenu = []; // Array for the menu items.
    public $debug = 0; // Logging level.
    public $lp_session_id = 0;
    public $lp_view_session_id = 0; // The specific view might be bound to a session.
    public $prerequisite = 0;
    public $use_max_score = 1; // 1 or 0
    public $subscribeUsers = 0; // Subscribe users or not
    public $created_on = '';
    public $modified_on = '';
    public $publicated_on = '';
    public $expired_on = '';
    public $ref = null;
    public $course_int_id;
    public $course_info = [];
    public $categoryId;

    /**
     * Constructor.
     * Needs a database handler, a course code and a learnpath id from the database.
     * Also builds the list of items into $this->items.
     *
     * @param string $course  Course code
     * @param int    $lp_id   c_lp.iid
     * @param int    $user_id
     */
    public function __construct($course, $lp_id, $user_id)
    {
        $debug = $this->debug;
        $this->encoding = api_get_system_encoding();
        if (empty($course)) {
            $course = api_get_course_id();
        }
        $course_info = api_get_course_info($course);
        if (!empty($course_info)) {
            $this->cc = $course_info['code'];
            $this->course_info = $course_info;
            $course_id = $course_info['real_id'];
        } else {
            $this->error = 'Course code does not exist in database.';
        }

        $lp_id = (int) $lp_id;
        $course_id = (int) $course_id;
        $this->set_course_int_id($course_id);
        // Check learnpath ID.
        if (empty($lp_id) || empty($course_id)) {
            $this->error = "Parameter is empty: LpId:'$lp_id', courseId: '$lp_id'";
        } else {
            // TODO: Make it flexible to use any course_code (still using env course code here).
            $lp_table = Database::get_course_table(TABLE_LP_MAIN);
            $sql = "SELECT * FROM $lp_table
                    WHERE iid = $lp_id";
            if ($debug) {
                error_log('learnpath::__construct() '.__LINE__.' - Querying lp: '.$sql, 0);
            }
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                $this->lp_id = $lp_id;
                $row = Database::fetch_array($res);
                $this->type = $row['lp_type'];
                $this->name = stripslashes($row['name']);
                $this->proximity = $row['content_local'];
                $this->theme = $row['theme'];
                $this->maker = $row['content_maker'];
                $this->prevent_reinit = $row['prevent_reinit'];
                $this->seriousgame_mode = $row['seriousgame_mode'];
                $this->license = $row['content_license'];
                $this->scorm_debug = $row['debug'];
                $this->js_lib = $row['js_lib'];
                $this->path = $row['path'];
                $this->preview_image = $row['preview_image'];
                $this->author = $row['author'];
                $this->hide_toc_frame = $row['hide_toc_frame'];
                $this->lp_session_id = $row['session_id'];
                $this->use_max_score = $row['use_max_score'];
                $this->subscribeUsers = $row['subscribe_users'];
                $this->created_on = $row['created_on'];
                $this->modified_on = $row['modified_on'];
                $this->ref = $row['ref'];
                $this->categoryId = $row['category_id'];
                $this->accumulateScormTime = isset($row['accumulate_scorm_time']) ? $row['accumulate_scorm_time'] : 'true';
                $this->accumulateWorkTime = isset($row['accumulate_work_time']) ? $row['accumulate_work_time'] : 0;

                if (!empty($row['publicated_on'])) {
                    $this->publicated_on = $row['publicated_on'];
                }

                if (!empty($row['expired_on'])) {
                    $this->expired_on = $row['expired_on'];
                }
                if ($this->type == 2) {
                    if ($row['force_commit'] == 1) {
                        $this->force_commit = true;
                    }
                }
                $this->mode = $row['default_view_mod'];

                // Check user ID.
                if (empty($user_id)) {
                    $this->error = 'User ID is empty';
                } else {
                    $userInfo = api_get_user_info($user_id);
                    if (!empty($userInfo)) {
                        $this->user_id = $userInfo['user_id'];
                    } else {
                        $this->error = 'User ID does not exist in database #'.$user_id;
                    }
                }

                // End of variables checking.
                $session_id = api_get_session_id();
                //  Get the session condition for learning paths of the base + session.
                $session = api_get_session_condition($session_id);
                // Now get the latest attempt from this user on this LP, if available, otherwise create a new one.
                $lp_table = Database::get_course_table(TABLE_LP_VIEW);

                // Selecting by view_count descending allows to get the highest view_count first.
                $sql = "SELECT * FROM $lp_table
                        WHERE
                            c_id = $course_id AND
                            lp_id = $lp_id AND
                            user_id = $user_id
                            $session
                        ORDER BY view_count DESC";
                $res = Database::query($sql);
                if ($debug) {
                    error_log('learnpath::__construct() '.__LINE__.' - querying lp_view: '.$sql, 0);
                }

                if (Database::num_rows($res) > 0) {
                    if ($debug) {
                        error_log('learnpath::__construct() '.__LINE__.' - Found previous view');
                    }
                    $row = Database::fetch_array($res);
                    $this->attempt = $row['view_count'];
                    $this->lp_view_id = $row['id'];
                    $this->last_item_seen = $row['last_item'];
                    $this->progress_db = $row['progress'];
                    $this->lp_view_session_id = $row['session_id'];
                } elseif (!api_is_invitee()) {
                    if ($debug) {
                        error_log('learnpath::__construct() '.__LINE__.' - NOT Found previous view');
                    }
                    $this->attempt = 1;
                    $params = [
                        'c_id' => $course_id,
                        'lp_id' => $lp_id,
                        'user_id' => $user_id,
                        'view_count' => 1,
                        'session_id' => $session_id,
                        'last_item' => 0,
                    ];
                    $this->last_item_seen = 0;
                    $this->lp_view_session_id = $session_id;
                    $this->lp_view_id = Database::insert($lp_table, $params);
                    if (!empty($this->lp_view_id)) {
                        $sql = "UPDATE $lp_table SET id = iid
                                WHERE iid = ".$this->lp_view_id;
                        Database::query($sql);
                    }
                }

                // Initialise items.
                $lp_item_table = Database::get_course_table(TABLE_LP_ITEM);
                $sql = "SELECT * FROM $lp_item_table
                        WHERE c_id = $course_id AND lp_id = '".$this->lp_id."'
                        ORDER BY parent_item_id, display_order";
                $res = Database::query($sql);

                $lp_item_id_list = [];
                while ($row = Database::fetch_array($res)) {
                    $lp_item_id_list[] = $row['iid'];
                    switch ($this->type) {
                        case 3: //aicc
                            $oItem = new aiccItem('db', $row['iid'], $course_id);
                            if (is_object($oItem)) {
                                $my_item_id = $oItem->get_id();
                                $oItem->set_lp_view($this->lp_view_id, $course_id);
                                $oItem->set_prevent_reinit($this->prevent_reinit);
                                // Don't use reference here as the next loop will make the pointed object change.
                                $this->items[$my_item_id] = $oItem;
                                $this->refs_list[$oItem->ref] = $my_item_id;
                                if ($debug) {
                                    error_log(
                                        'learnpath::__construct() - '.
                                        'aicc object with id '.$my_item_id.
                                        ' set in items[]',
                                        0
                                    );
                                }
                            }
                            break;
                        case 2:
                            $oItem = new scormItem('db', $row['iid'], $course_id);
                            if (is_object($oItem)) {
                                $my_item_id = $oItem->get_id();
                                $oItem->set_lp_view($this->lp_view_id, $course_id);
                                $oItem->set_prevent_reinit($this->prevent_reinit);
                                // Don't use reference here as the next loop will make the pointed object change.
                                $this->items[$my_item_id] = $oItem;
                                $this->refs_list[$oItem->ref] = $my_item_id;
                                if ($debug) {
                                    error_log('object with id '.$my_item_id.' set in items[]');
                                }
                            }
                            break;
                        case 1:
                        default:
                            if ($debug) {
                                error_log('learnpath::__construct() '.__LINE__.' - calling learnpathItem');
                            }
                            $oItem = new learnpathItem($row['iid'], $user_id, $course_id, $row);

                            if ($debug) {
                                error_log('learnpath::__construct() '.__LINE__.' - end calling learnpathItem');
                            }
                            if (is_object($oItem)) {
                                $my_item_id = $oItem->get_id();
                                // Moved down to when we are sure the item_view exists.
                                //$oItem->set_lp_view($this->lp_view_id);
                                $oItem->set_prevent_reinit($this->prevent_reinit);
                                // Don't use reference here as the next loop will make the pointed object change.
                                $this->items[$my_item_id] = $oItem;
                                $this->refs_list[$my_item_id] = $my_item_id;
                                if ($debug) {
                                    error_log(
                                        'learnpath::__construct() '.__LINE__.
                                        ' - object with id '.$my_item_id.' set in items[]'
                                    );
                                }
                            }
                            break;
                    }

                    // Setting the object level with variable $this->items[$i][parent]
                    foreach ($this->items as $itemLPObject) {
                        $level = self::get_level_for_item(
                            $this->items,
                            $itemLPObject->db_id
                        );
                        $itemLPObject->level = $level;
                    }

                    // Setting the view in the item object.
                    if (is_object($this->items[$row['iid']])) {
                        $this->items[$row['iid']]->set_lp_view($this->lp_view_id, $course_id);
                        if ($this->items[$row['iid']]->get_type() == TOOL_HOTPOTATOES) {
                            $this->items[$row['iid']]->current_start_time = 0;
                            $this->items[$row['iid']]->current_stop_time = 0;
                        }
                    }
                }

                if (!empty($lp_item_id_list)) {
                    $lp_item_id_list_to_string = implode("','", $lp_item_id_list);
                    if (!empty($lp_item_id_list_to_string)) {
                        // Get last viewing vars.
                        $itemViewTable = Database::get_course_table(TABLE_LP_ITEM_VIEW);
                        // This query should only return one or zero result.
                        $sql = "SELECT lp_item_id, status
                                FROM $itemViewTable
                                WHERE
                                    c_id = $course_id AND
                                    lp_view_id = ".$this->get_view_id()." AND
                                    lp_item_id IN ('".$lp_item_id_list_to_string."')
                                ORDER BY view_count DESC ";
                        $status_list = [];
                        $res = Database::query($sql);
                        while ($row = Database::fetch_array($res)) {
                            $status_list[$row['lp_item_id']] = $row['status'];
                        }

                        foreach ($lp_item_id_list as $item_id) {
                            if (isset($status_list[$item_id])) {
                                $status = $status_list[$item_id];
                                if (is_object($this->items[$item_id])) {
                                    $this->items[$item_id]->set_status($status);
                                    if (empty($status)) {
                                        $this->items[$item_id]->set_status(
                                            $this->default_status
                                        );
                                    }
                                }
                            } else {
                                if (!api_is_invitee()) {
                                    if (is_object($this->items[$item_id])) {
                                        $this->items[$item_id]->set_status(
                                            $this->default_status
                                        );
                                    }

                                    if (!empty($this->lp_view_id)) {
                                        // Add that row to the lp_item_view table so that
                                        // we have something to show in the stats page.
                                        $params = [
                                            'c_id' => $course_id,
                                            'lp_item_id' => $item_id,
                                            'lp_view_id' => $this->lp_view_id,
                                            'view_count' => 1,
                                            'status' => 'not attempted',
                                            'start_time' => time(),
                                            'total_time' => 0,
                                            'score' => 0,
                                        ];
                                        $insertId = Database::insert($itemViewTable, $params);

                                        if ($insertId) {
                                            $sql = "UPDATE $itemViewTable SET id = iid
                                                    WHERE iid = $insertId";
                                            Database::query($sql);
                                        }

                                        $this->items[$item_id]->set_lp_view(
                                            $this->lp_view_id,
                                            $course_id
                                        );
                                    }
                                }
                            }
                        }
                    }
                }

                $this->ordered_items = self::get_flat_ordered_items_list(
                    $this->get_id(),
                    0,
                    $course_id
                );
                $this->max_ordered_items = 0;
                foreach ($this->ordered_items as $index => $dummy) {
                    if ($index > $this->max_ordered_items && !empty($dummy)) {
                        $this->max_ordered_items = $index;
                    }
                }
                // TODO: Define the current item better.
                $this->first();
                if ($debug) {
                    error_log('lp_view_session_id '.$this->lp_view_session_id);
                    error_log('End of learnpath constructor for learnpath '.$this->get_id());
                }
            } else {
                $this->error = 'Learnpath ID does not exist in database ('.$sql.')';
            }
        }
    }

    /**
     * @return string
     */
    public function getCourseCode()
    {
        return $this->cc;
    }

    /**
     * @return int
     */
    public function get_course_int_id()
    {
        return isset($this->course_int_id) ? $this->course_int_id : api_get_course_int_id();
    }

    /**
     * @param $course_id
     *
     * @return int
     */
    public function set_course_int_id($course_id)
    {
        return $this->course_int_id = (int) $course_id;
    }

    /**
     * Function rewritten based on old_add_item() from Yannick Warnier.
     * Due the fact that users can decide where the item should come, I had to overlook this function and
     * I found it better to rewrite it. Old function is still available.
     * Added also the possibility to add a description.
     *
     * @param int    $parent
     * @param int    $previous
     * @param string $type
     * @param int    $id               resource ID (ref)
     * @param string $title
     * @param string $description
     * @param int    $prerequisites
     * @param int    $max_time_allowed
     * @param int    $userId
     *
     * @return int
     */
    public function add_item(
        $parent,
        $previous,
        $type,
        $id,
        $title,
        $description,
        $prerequisites = 0,
        $max_time_allowed = 0,
        $userId = 0,
        $dspOrder = 0
    ) {
        $course_id = $this->course_info['real_id'];
        if (empty($course_id)) {
            // Sometimes Oogie doesn't catch the course info but sets $this->cc
            $this->course_info = api_get_course_info($this->cc);
            $course_id = $this->course_info['real_id'];
        }
        $userId = empty($userId) ? api_get_user_id() : $userId;
        $sessionId = api_get_session_id();
        $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $_course = $this->course_info;
        $parent = (int) $parent;
        $previous = (int) $previous;
        $id = (int) $id;
        $max_time_allowed = htmlentities($max_time_allowed);
        if (empty($max_time_allowed)) {
            $max_time_allowed = 0;
        }
        $sql = "SELECT COUNT(iid) AS num
                FROM $tbl_lp_item
                WHERE
                    c_id = $course_id AND
                    lp_id = ".$this->get_id()." AND
                    parent_item_id = $parent ";

        $res_count = Database::query($sql);
        $row = Database::fetch_array($res_count);
        $num = $row['num'];

        $tmp_previous = 0;
        $display_order = 0;
        $next = 0;
        if ($num > 0) {
            if (empty($previous)) {
                $sql = "SELECT iid, next_item_id, display_order
                        FROM $tbl_lp_item
                        WHERE
                            c_id = $course_id AND
                            lp_id = ".$this->get_id()." AND
                            parent_item_id = $parent AND
                            previous_item_id = 0 OR
                            previous_item_id = $parent";
                $result = Database::query($sql);
                $row = Database::fetch_array($result);
                if ($row) {
                    $next = $row['iid'];
                }
            } else {
                $previous = (int) $previous;
                $sql = "SELECT iid, previous_item_id, next_item_id, display_order
						FROM $tbl_lp_item
                        WHERE
                            c_id = $course_id AND
                            lp_id = ".$this->get_id()." AND
                            id = $previous";
                $result = Database::query($sql);
                $row = Database::fetch_array($result);
                if ($row) {
                    $tmp_previous = $row['iid'];
                    $next = $row['next_item_id'];
                    $display_order = $row['display_order'];
                }
            }
        }

        $id = (int) $id;
        $typeCleaned = Database::escape_string($type);
        $max_score = 100;
        if ($type === 'quiz' && $id) {
            $sql = 'SELECT SUM(ponderation)
                    FROM '.Database::get_course_table(TABLE_QUIZ_QUESTION).' as quiz_question
                    INNER JOIN '.Database::get_course_table(TABLE_QUIZ_TEST_QUESTION).' as quiz_rel_question
                    ON
                        quiz_question.iid = quiz_rel_question.question_id
                    WHERE
                        quiz_rel_question.exercice_id = '.$id." AND
                        quiz_rel_question.c_id = $course_id ";
            $rsQuiz = Database::query($sql);
            $max_score = Database::result($rsQuiz, 0, 0);

            // Disabling the exercise if we add it inside a LP
            $exercise = new Exercise($course_id);
            $exercise->read($id);
            $exercise->disable();
            $exercise->save();
        }

        $newDisplayOrder = $display_order + 1;
        if (!empty($dspOrder)) {
            $newDisplayOrder = (int) $dspOrder;
        }

        $params = [
            'c_id' => $course_id,
            'lp_id' => $this->get_id(),
            'item_type' => $typeCleaned,
            'ref' => '',
            'title' => $title,
            'description' => $description,
            'path' => $id,
            'max_score' => $max_score,
            'parent_item_id' => $parent,
            'previous_item_id' => $previous,
            'next_item_id' => (int) $next,
            'display_order' => $newDisplayOrder,
            'prerequisite' => $prerequisites,
            'max_time_allowed' => $max_time_allowed,
            'min_score' => 0,
            'launch_data' => '',
        ];

        if ($prerequisites != 0) {
            $params['prerequisite'] = $prerequisites;
        }

        $new_item_id = Database::insert($tbl_lp_item, $params);
        if ($new_item_id) {
            $sql = "UPDATE $tbl_lp_item SET id = iid WHERE iid = $new_item_id";
            Database::query($sql);

            if (!empty($next)) {
                $sql = "UPDATE $tbl_lp_item
                        SET previous_item_id = $new_item_id
                        WHERE c_id = $course_id AND id = $next AND item_type != '".TOOL_LP_FINAL_ITEM."'";
                Database::query($sql);
            }

            // Update the item that should be before the new item.
            if (!empty($tmp_previous)) {
                $sql = "UPDATE $tbl_lp_item
                        SET next_item_id = $new_item_id
                        WHERE c_id = $course_id AND id = $tmp_previous";
                Database::query($sql);
            }

            // Update all the items after the new item.
            $sql = "UPDATE $tbl_lp_item
                        SET display_order = display_order + 1
                    WHERE
                        c_id = $course_id AND
                        lp_id = ".$this->get_id()." AND
                        iid <> $new_item_id AND
                        parent_item_id = $parent AND
                        display_order > $display_order";
            Database::query($sql);

            // Update the item that should come after the new item.
            $sql = "UPDATE $tbl_lp_item
                    SET ref = $new_item_id
                    WHERE c_id = $course_id AND iid = $new_item_id";
            Database::query($sql);

            $sql = "UPDATE $tbl_lp_item
                    SET previous_item_id = ".$this->getLastInFirstLevel()."
                    WHERE c_id = $course_id AND lp_id = {$this->lp_id} AND item_type = '".TOOL_LP_FINAL_ITEM."'";
            Database::query($sql);

            // Upload audio.
            if (!empty($_FILES['mp3']['name'])) {
                // Create the audio folder if it does not exist yet.
                $filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
                if (!is_dir($filepath.'audio')) {
                    mkdir(
                        $filepath.'audio',
                        api_get_permissions_for_new_directories()
                    );
                    $audio_id = add_document(
                        $_course,
                        '/audio',
                        'folder',
                        0,
                        'audio',
                        '',
                        0,
                        true,
                        null,
                        $sessionId,
                        $userId
                    );
                    api_item_property_update(
                        $_course,
                        TOOL_DOCUMENT,
                        $audio_id,
                        'FolderCreated',
                        $userId,
                        null,
                        null,
                        null,
                        null,
                        $sessionId
                    );
                    api_item_property_update(
                        $_course,
                        TOOL_DOCUMENT,
                        $audio_id,
                        'invisible',
                        $userId,
                        null,
                        null,
                        null,
                        null,
                        $sessionId
                    );
                }

                $file_path = handle_uploaded_document(
                    $_course,
                    $_FILES['mp3'],
                    api_get_path(SYS_COURSE_PATH).$_course['path'].'/document',
                    '/audio',
                    $userId,
                    '',
                    '',
                    '',
                    '',
                    false
                );

                // Store the mp3 file in the lp_item table.
                $sql = "UPDATE $tbl_lp_item SET
                          audio = '".Database::escape_string($file_path)."'
                        WHERE iid = '".intval($new_item_id)."'";
                Database::query($sql);
            }
        }

        return $new_item_id;
    }

    /**
     * Static admin function allowing addition of a learnpath to a course.
     *
     * @param string $courseCode
     * @param string $name
     * @param string $description
     * @param string $learnpath
     * @param string $origin
     * @param string $zipname       Zip file containing the learnpath or directory containing the learnpath
     * @param string $publicated_on
     * @param string $expired_on
     * @param int    $categoryId
     * @param int    $userId
     *
     * @return int The new learnpath ID on success, 0 on failure
     */
    public static function add_lp(
        $courseCode,
        $name,
        $description = '',
        $learnpath = 'guess',
        $origin = 'zip',
        $zipname = '',
        $publicated_on = '',
        $expired_on = '',
        $categoryId = 0,
        $userId = 0
    ) {
        global $charset;

        if (!empty($courseCode)) {
            $courseInfo = api_get_course_info($courseCode);
            $course_id = $courseInfo['real_id'];
        } else {
            $course_id = api_get_course_int_id();
            $courseInfo = api_get_course_info();
        }

        $em = Database::getManager();
        $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
        // Check course code exists.
        // Check lp_name doesn't exist, otherwise append something.
        $i = 0;
        $categoryId = (int) $categoryId;
        // Session id.
        $session_id = api_get_session_id();
        $userId = empty($userId) ? api_get_user_id() : $userId;

        if (empty($publicated_on)) {
            $publicated_on = null;
        } else {
            $publicated_on = api_get_utc_datetime($publicated_on, false, true);
        }

        if (empty($expired_on)) {
            $expired_on = null;
        } else {
            $expired_on = api_get_utc_datetime($expired_on, false, true);
        }

        $check_name = "SELECT * FROM $tbl_lp
                       WHERE c_id = $course_id AND name = '".Database::escape_string($name)."'";
        $res_name = Database::query($check_name);

        while (Database::num_rows($res_name)) {
            // There is already one such name, update the current one a bit.
            $i++;
            $name = $name.' - '.$i;
            $check_name = "SELECT * FROM $tbl_lp
                           WHERE c_id = $course_id AND name = '".Database::escape_string($name)."' ";
            $res_name = Database::query($check_name);
        }
        // New name does not exist yet; keep it.
        // Escape description.
        // Kevin: added htmlentities().
        $description = Database::escape_string(api_htmlentities($description, ENT_QUOTES, $charset));
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
                // Check zip name string. If empty, we are currently creating a new Chamilo learnpath.
                break;
            case 'manual':
            default:
                $get_max = "SELECT MAX(display_order)
                            FROM $tbl_lp WHERE c_id = $course_id";
                $res_max = Database::query($get_max);
                if (Database::num_rows($res_max) < 1) {
                    $dsp = 1;
                } else {
                    $row = Database::fetch_array($res_max);
                    $dsp = $row[0] + 1;
                }

                $newLp = (new CLp())
                    ->setCId($course_id)
                    ->setLpType($type)
                    ->setName($name)
                    ->setDescription($description)
                    ->setPath('')
                    ->setDefaultViewMod('embedded')
                    ->setDefaultEncoding('UTF-8')
                    ->setDisplayOrder($dsp)
                    ->setContentMaker('Chamilo')
                    ->setContentLocal('local')
                    ->setJsLib('')
                    ->setSessionId($session_id)
                    ->setCreatedOn(api_get_utc_datetime(null, false, true))
                    ->setModifiedOn(api_get_utc_datetime(null, false, true))
                    ->setPublicatedOn($publicated_on)
                    ->setExpiredOn($expired_on)
                    ->setCategoryId($categoryId)
                    ->setForceCommit(false)
                    ->setContentLicense('')
                    ->setDebug(false)
                    ->setTheme('')
                    ->setPreviewImage('')
                    ->setAuthor('')
                    ->setPrerequisite(0)
                    ->setHideTocFrame(false)
                    ->setSeriousgameMode(false)
                    ->setAutolaunch(0)
                    ->setMaxAttempts(0)
                    ->setSubscribeUsers(0)
                    ->setAccumulateScormTime(1)
                ;

                $em->persist($newLp);
                $em->flush();

                HookLearningPathCreated::create()
                    ->setEventData(['lp' => $newLp])
                    ->notifyCreated()
                ;

                $newLp->setId($newLp->getIid());

                $em->flush();

                // Insert into item_property.
                api_item_property_update(
                    $courseInfo,
                    TOOL_LEARNPATH,
                    $newLp->getIid(),
                    'LearnpathAdded',
                    $userId
                );
                api_set_default_visibility(
                    $newLp->getIid(),
                    TOOL_LEARNPATH,
                    0,
                    $courseInfo,
                    $session_id,
                    $userId
                );

                return $newLp->getIid();
        }
    }

    /**
     * Auto completes the parents of an item in case it's been completed or passed.
     *
     * @param int $item Optional ID of the item from which to look for parents
     */
    public function autocomplete_parents($item)
    {
        $debug = $this->debug;

        if (empty($item)) {
            $item = $this->current;
        }

        $currentItem = $this->getItem($item);
        if ($currentItem) {
            $parent_id = $currentItem->get_parent();
            $parent = $this->getItem($parent_id);
            if ($parent) {
                // if $item points to an object and there is a parent.
                if ($debug) {
                    error_log(
                        'Autocompleting parent of item '.$item.' '.
                        $currentItem->get_title().'" (item '.$parent_id.' "'.$parent->get_title().'") ',
                        0
                    );
                }

                // New experiment including failed and browsed in completed status.
                //$current_status = $currentItem->get_status();
                //if ($currentItem->is_done() || $current_status == 'browsed' || $current_status == 'failed') {
                // Fixes chapter auto complete
                if (true) {
                    // If the current item is completed or passes or succeeded.
                    $updateParentStatus = true;
                    if ($debug) {
                        error_log('Status of current item is alright');
                    }

                    foreach ($parent->get_children() as $childItemId) {
                        $childItem = $this->getItem($childItemId);

                        // If children was not set try to get the info
                        if (empty($childItem->db_item_view_id)) {
                            $childItem->set_lp_view($this->lp_view_id, $this->course_int_id);
                        }

                        // Check all his brothers (parent's children) for completion status.
                        if ($childItemId != $item) {
                            if ($debug) {
                                error_log(
                                    'Looking at brother #'.$childItemId.' "'.$childItem->get_title().'", status is '.$childItem->get_status(),
                                    0
                                );
                            }
                            // Trying completing parents of failed and browsed items as well.
                            if ($childItem->status_is(
                                [
                                    'completed',
                                    'passed',
                                    'succeeded',
                                    'browsed',
                                    'failed',
                                ]
                            )
                            ) {
                                // Keep completion status to true.
                                continue;
                            } else {
                                if ($debug > 2) {
                                    error_log(
                                        'Found one incomplete child of parent #'.$parent_id.': child #'.$childItemId.' "'.$childItem->get_title().'", is '.$childItem->get_status().' db_item_view_id:#'.$childItem->db_item_view_id,
                                        0
                                    );
                                }
                                $updateParentStatus = false;
                                break;
                            }
                        }
                    }

                    if ($updateParentStatus) {
                        // If all the children were completed:
                        $parent->set_status('completed');
                        $parent->save(false, $this->prerequisites_match($parent->get_id()));
                        // Force the status to "completed"
                        //$this->update_queue[$parent->get_id()] = $parent->get_status();
                        $this->update_queue[$parent->get_id()] = 'completed';
                        if ($debug) {
                            error_log(
                                'Added parent #'.$parent->get_id().' "'.$parent->get_title().'" to update queue status: completed '.
                                print_r($this->update_queue, 1),
                                0
                            );
                        }
                        // Recursive call.
                        $this->autocomplete_parents($parent->get_id());
                    }
                }
            } else {
                if ($debug) {
                    error_log("Parent #$parent_id does not exists");
                }
            }
        } else {
            if ($debug) {
                error_log("#$item is an item that doesn't have parents");
            }
        }
    }

    /**
     * Closes the current resource.
     *
     * Stops the timer
     * Saves into the database if required
     * Clears the current resource data from this object
     *
     * @return bool True on success, false on failure
     */
    public function close()
    {
        if (empty($this->lp_id)) {
            $this->error = 'Trying to close this learnpath but no ID is set';

            return false;
        }
        $this->current_time_stop = time();
        $this->ordered_items = [];
        $this->index = 0;
        unset($this->lp_id);
        //unset other stuff
        return true;
    }

    /**
     * Static admin function allowing removal of a learnpath.
     *
     * @param array  $courseInfo
     * @param int    $id         Learnpath ID
     * @param string $delete     Whether to delete data or keep it (default: 'keep', others: 'remove')
     *
     * @return bool True on success, false on failure (might change that to return number of elements deleted)
     */
    public function delete($courseInfo = null, $id = null, $delete = 'keep')
    {
        $course_id = api_get_course_int_id();
        if (!empty($courseInfo)) {
            $course_id = isset($courseInfo['real_id']) ? $courseInfo['real_id'] : $course_id;
        }

        // TODO: Implement a way of getting this to work when the current object is not set.
        // In clear: implement this in the item class as well (abstract class) and use the given ID in queries.
        // If an ID is specifically given and the current LP is not the same, prevent delete.
        if (!empty($id) && ($id != $this->lp_id)) {
            return false;
        }

        $lp = Database::get_course_table(TABLE_LP_MAIN);
        $lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $lp_view = Database::get_course_table(TABLE_LP_VIEW);
        $lp_item_view = Database::get_course_table(TABLE_LP_ITEM_VIEW);

        // Delete lp item id.
        foreach ($this->items as $lpItemId => $dummy) {
            $sql = "DELETE FROM $lp_item_view
                    WHERE c_id = $course_id AND lp_item_id = '".$lpItemId."'";
            Database::query($sql);
        }

        // Proposed by Christophe (nickname: clefevre)
        $sql = "DELETE FROM $lp_item
                WHERE c_id = $course_id AND lp_id = ".$this->lp_id;
        Database::query($sql);

        $sql = "DELETE FROM $lp_view
                WHERE c_id = $course_id AND lp_id = ".$this->lp_id;
        Database::query($sql);

        self::toggle_publish($this->lp_id, 'i');

        if ($this->type == 2 || $this->type == 3) {
            // This is a scorm learning path, delete the files as well.
            $sql = "SELECT path FROM $lp
                    WHERE iid = ".$this->lp_id;
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                $row = Database::fetch_array($res);
                $path = $row['path'];
                $sql = "SELECT id FROM $lp
                        WHERE
                            c_id = $course_id AND
                            path = '$path' AND
                            iid != ".$this->lp_id;
                $res = Database::query($sql);
                if (Database::num_rows($res) > 0) {
                    // Another learning path uses this directory, so don't delete it.
                    if ($this->debug > 2) {
                        error_log('In learnpath::delete(), found other LP using path '.$path.', keeping directory', 0);
                    }
                } else {
                    // No other LP uses that directory, delete it.
                    $course_rel_dir = api_get_course_path().'/scorm/'; // scorm dir web path starting from /courses
                    // The absolute system path for this course.
                    $course_scorm_dir = api_get_path(SYS_COURSE_PATH).$course_rel_dir;
                    if ($delete == 'remove' && is_dir($course_scorm_dir.$path) && !empty($course_scorm_dir)) {
                        if ($this->debug > 2) {
                            error_log('In learnpath::delete(), found SCORM, deleting directory: '.$course_scorm_dir.$path, 0);
                        }
                        // Proposed by Christophe (clefevre).
                        if (strcmp(substr($path, -2), "/.") == 0) {
                            $path = substr($path, 0, -1); // Remove "." at the end.
                        }
                        //exec('rm -rf ' . $course_scorm_dir . $path); // See Bug #5208, this is not OS-portable way.
                        rmdirr($course_scorm_dir.$path);
                    }
                }
            }
        }

        $tbl_tool = Database::get_course_table(TABLE_TOOL_LIST);
        $link = 'lp/lp_controller.php?action=view&lp_id='.$this->lp_id;
        // Delete tools
        $sql = "DELETE FROM $tbl_tool
                WHERE c_id = $course_id AND (link LIKE '$link%' AND image='scormbuilder.gif')";
        Database::query($sql);

        if (api_get_configuration_value('allow_lp_subscription_to_usergroups')) {
            $table = Database::get_course_table(TABLE_LP_REL_USERGROUP);
            $sql = "DELETE FROM $table
                    WHERE
                        lp_id = {$this->lp_id} AND
                        c_id = $course_id ";
            Database::query($sql);
        }

        $sql = "DELETE FROM $lp
                WHERE iid = ".$this->lp_id;
        Database::query($sql);
        // Updates the display order of all lps.
        $this->update_display_order();

        api_item_property_update(
            api_get_course_info(),
            TOOL_LEARNPATH,
            $this->lp_id,
            'delete',
            api_get_user_id()
        );

        $link_info = GradebookUtils::isResourceInCourseGradebook(
            api_get_course_id(),
            4,
            $id,
            api_get_session_id()
        );

        if ($link_info !== false) {
            GradebookUtils::remove_resource_from_course_gradebook($link_info['id']);
        }

        if (api_get_setting('search_enabled') == 'true') {
            require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';
            delete_all_values_for_item($this->cc, TOOL_LEARNPATH, $this->lp_id);
        }
    }

    /**
     * Removes all the children of one item - dangerous!
     *
     * @param int $id Element ID of which children have to be removed
     *
     * @return int Total number of children removed
     */
    public function delete_children_items($id)
    {
        $course_id = $this->course_info['real_id'];

        $num = 0;
        $id = (int) $id;
        if (empty($id) || empty($course_id)) {
            return false;
        }
        $lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $sql = "SELECT * FROM $lp_item
                WHERE c_id = $course_id AND parent_item_id = $id";
        $res = Database::query($sql);
        while ($row = Database::fetch_array($res)) {
            $num += $this->delete_children_items($row['iid']);
            $sql = "DELETE FROM $lp_item
                    WHERE c_id = $course_id AND iid = ".$row['iid'];
            Database::query($sql);
            $num++;
        }

        return $num;
    }

    /**
     * Removes an item from the current learnpath.
     *
     * @param int $id Elem ID (0 if first)
     *
     * @return int Number of elements moved
     *
     * @todo implement resource removal
     */
    public function delete_item($id)
    {
        $course_id = api_get_course_int_id();
        $id = (int) $id;
        // TODO: Implement the resource removal.
        if (empty($id) || empty($course_id)) {
            return false;
        }
        // First select item to get previous, next, and display order.
        $lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $sql_sel = "SELECT * FROM $lp_item WHERE iid = $id";
        $res_sel = Database::query($sql_sel);
        if (Database::num_rows($res_sel) < 1) {
            return false;
        }
        $row = Database::fetch_array($res_sel);
        $previous = $row['previous_item_id'];
        $next = $row['next_item_id'];
        $display = $row['display_order'];
        $parent = $row['parent_item_id'];
        $lp = $row['lp_id'];
        // Delete children items.
        $this->delete_children_items($id);
        // Now delete the item.
        $sql_del = "DELETE FROM $lp_item WHERE iid = $id";
        Database::query($sql_del);
        // Now update surrounding items.
        $sql_upd = "UPDATE $lp_item SET next_item_id = $next
                    WHERE iid = $previous";
        Database::query($sql_upd);
        $sql_upd = "UPDATE $lp_item SET previous_item_id = $previous
                    WHERE iid = $next AND item_type != '".TOOL_LP_FINAL_ITEM."'";
        Database::query($sql_upd);
        // Now update all following items with new display order.
        $sql_all = "UPDATE $lp_item SET display_order = display_order-1
                    WHERE
                        c_id = $course_id AND
                        lp_id = $lp AND
                        parent_item_id = $parent AND
                        display_order > $display";
        Database::query($sql_all);

        //Removing prerequisites since the item will not longer exist
        $sql_all = "UPDATE $lp_item SET prerequisite = ''
                    WHERE c_id = $course_id AND prerequisite = $id";
        Database::query($sql_all);

        $sql = "UPDATE $lp_item
                SET previous_item_id = ".$this->getLastInFirstLevel()."
                WHERE c_id = $course_id AND lp_id = {$this->lp_id} AND item_type = '".TOOL_LP_FINAL_ITEM."'";
        Database::query($sql);

        // Remove from search engine if enabled.
        if (api_get_setting('search_enabled') === 'true') {
            $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
            $sql = 'SELECT * FROM %s
                    WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s AND ref_id_second_level=%d
                    LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $this->cc, TOOL_LEARNPATH, $lp, $id);
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                $row2 = Database::fetch_array($res);
                $di = new ChamiloIndexer();
                $di->remove_document($row2['search_did']);
            }
            $sql = 'DELETE FROM %s
                    WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s AND ref_id_second_level=%d
                    LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $this->cc, TOOL_LEARNPATH, $lp, $id);
            Database::query($sql);
        }
    }

    /**
     * Updates an item's content in place.
     *
     * @param int    $id               Element ID
     * @param int    $parent           Parent item ID
     * @param int    $previous         Previous item ID
     * @param string $title            Item title
     * @param string $description      Item description
     * @param string $prerequisites    Prerequisites (optional)
     * @param array  $audio            The array resulting of the $_FILES[mp3] element
     * @param int    $max_time_allowed
     * @param string $url
     *
     * @return bool True on success, false on error
     */
    public function edit_item(
        $id,
        $parent,
        $previous,
        $title,
        $description,
        $prerequisites = '0',
        $audio = [],
        $max_time_allowed = 0,
        $url = ''
    ) {
        $course_id = api_get_course_int_id();
        $_course = api_get_course_info();
        $id = (int) $id;

        if (empty($max_time_allowed)) {
            $max_time_allowed = 0;
        }

        if (empty($id) || empty($_course)) {
            return false;
        }

        $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $sql = "SELECT * FROM $tbl_lp_item
                WHERE iid = $id";
        $res_select = Database::query($sql);
        $row_select = Database::fetch_array($res_select);
        $audio_update_sql = '';
        if (is_array($audio) && !empty($audio['tmp_name']) && $audio['error'] === 0) {
            // Create the audio folder if it does not exist yet.
            $filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
            if (!is_dir($filepath.'audio')) {
                mkdir($filepath.'audio', api_get_permissions_for_new_directories());
                $audio_id = add_document(
                    $_course,
                    '/audio',
                    'folder',
                    0,
                    'audio'
                );
                api_item_property_update(
                    $_course,
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
                    $_course,
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

            // Upload file in documents.
            $pi = pathinfo($audio['name']);
            if ($pi['extension'] === 'mp3') {
                $c_det = api_get_course_info($this->cc);
                $bp = api_get_path(SYS_COURSE_PATH).$c_det['path'].'/document';
                $path = handle_uploaded_document(
                    $c_det,
                    $audio,
                    $bp,
                    '/audio',
                    api_get_user_id(),
                    0,
                    null,
                    0,
                    'rename',
                    false,
                    0
                );
                // Update reference in lp_item - audio path is the path from inside de document/audio/ dir.
                $audio_update_sql = ", audio = '".Database::escape_string($path)."' ";
            }
        }

        $same_parent = $row_select['parent_item_id'] == $parent ? true : false;
        $same_previous = $row_select['previous_item_id'] == $previous ? true : false;

        // TODO: htmlspecialchars to be checked for encoding related problems.
        if ($same_parent && $same_previous) {
            // Only update title and description.
            $sql = "UPDATE $tbl_lp_item
                    SET title = '".Database::escape_string($title)."',
                        prerequisite = '".$prerequisites."',
                        description = '".Database::escape_string($description)."'
                        ".$audio_update_sql.",
                        max_time_allowed = '".Database::escape_string($max_time_allowed)."'
                    WHERE iid = $id";
            Database::query($sql);
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
                // Next
                $sql = "UPDATE $tbl_lp_item
                        SET next_item_id = $old_next
                        WHERE iid = $old_previous";
                Database::query($sql);
            }

            if (!empty($old_next)) {
                // Previous
                $sql = "UPDATE $tbl_lp_item
                        SET previous_item_id = $old_previous
                        WHERE iid = $old_next";
                Database::query($sql);
            }

            // display_order - 1 for every item with a display_order
            // bigger then the display_order of the current item.
            $sql = "UPDATE $tbl_lp_item
                    SET display_order = display_order - 1
                    WHERE
                        c_id = $course_id AND
                        display_order > $old_order AND
                        lp_id = ".$this->lp_id." AND
                        parent_item_id = $old_parent";
            Database::query($sql);
            /* END -- virtually remove the current item id */

            /* BEGIN -- update the current item id to his new location */
            if ($previous == 0) {
                // Select the data of the item that should come after the current item.
                $sql = "SELECT id, display_order
                        FROM $tbl_lp_item
                        WHERE
                            c_id = $course_id AND
                            lp_id = ".$this->lp_id." AND
                            parent_item_id = $parent AND
                            previous_item_id = $previous";
                $res_select_old = Database::query($sql);
                $row_select_old = Database::fetch_array($res_select_old);

                // If the new parent didn't have children before.
                if (Database::num_rows($res_select_old) == 0) {
                    $new_next = 0;
                    $new_order = 1;
                } else {
                    $new_next = $row_select_old['id'];
                    $new_order = $row_select_old['display_order'];
                }
            } else {
                // Select the data of the item that should come before the current item.
                $sql = "SELECT next_item_id, display_order
                        FROM $tbl_lp_item
                        WHERE iid = $previous";
                $res_select_old = Database::query($sql);
                $row_select_old = Database::fetch_array($res_select_old);
                $new_next = $row_select_old['next_item_id'];
                $new_order = $row_select_old['display_order'] + 1;
            }

            // TODO: htmlspecialchars to be checked for encoding related problems.
            // Update the current item with the new data.
            $sql = "UPDATE $tbl_lp_item
                    SET
                        title = '".Database::escape_string($title)."',
                        description = '".Database::escape_string($description)."',
                        parent_item_id = $parent,
                        previous_item_id = $previous,
                        next_item_id = $new_next,
                        display_order = $new_order
                        $audio_update_sql
                    WHERE iid = $id";
            Database::query($sql);

            if ($previous != 0) {
                // Update the previous item's next_item_id.
                $sql = "UPDATE $tbl_lp_item
                        SET next_item_id = $id
                        WHERE iid = $previous";
                Database::query($sql);
            }

            if (!empty($new_next)) {
                // Update the next item's previous_item_id.
                $sql = "UPDATE $tbl_lp_item
                        SET previous_item_id = $id
                        WHERE iid = $new_next";
                Database::query($sql);
            }

            if ($old_prerequisite != $prerequisites) {
                $sql = "UPDATE $tbl_lp_item
                        SET prerequisite = '$prerequisites'
                        WHERE iid = $id";
                Database::query($sql);
            }

            if ($old_max_time_allowed != $max_time_allowed) {
                // update max time allowed
                $sql = "UPDATE $tbl_lp_item
                        SET max_time_allowed = $max_time_allowed
                        WHERE iid = $id";
                Database::query($sql);
            }

            // Update all the items with the same or a bigger display_order than the current item.
            $sql = "UPDATE $tbl_lp_item
                    SET display_order = display_order + 1
                    WHERE
                       c_id = $course_id AND
                       lp_id = ".$this->get_id()." AND
                       iid <> $id AND
                       parent_item_id = $parent AND
                       display_order >= $new_order";
            Database::query($sql);
        }

        if ($row_select['item_type'] == 'link') {
            $link = new Link();
            $linkId = $row_select['path'];
            $link->updateLink($linkId, $url);
        }
    }

    /**
     * Updates an item's prereq in place.
     *
     * @param int    $id              Element ID
     * @param string $prerequisite_id Prerequisite Element ID
     * @param int    $minScore        Prerequisite min score
     * @param int    $maxScore        Prerequisite max score
     *
     * @return bool True on success, false on error
     */
    public function edit_item_prereq(
        $id,
        $prerequisite_id,
        $minScore = 0,
        $maxScore = 100
    ) {
        $id = (int) $id;

        if (empty($id)) {
            return false;
        }

        $prerequisite_id = (int) $prerequisite_id;

        if (empty($minScore) || $minScore < 0) {
            $minScore = 0;
        }

        if (empty($maxScore) || $maxScore < 0) {
            $maxScore = 100;
        }

        $minScore = (float) $minScore;
        $maxScore = (float) $maxScore;

        if (empty($prerequisite_id)) {
            $prerequisite_id = 'NULL';
            $minScore = 0;
            $maxScore = 100;
        }

        $table = Database::get_course_table(TABLE_LP_ITEM);
        $sql = " UPDATE $table
                 SET
                    prerequisite = $prerequisite_id ,
                    prerequisite_min_score = $minScore ,
                    prerequisite_max_score = $maxScore
                 WHERE iid = $id";
        Database::query($sql);

        return true;
    }

    /**
     * Get the specific prefix index terms of this learning path.
     *
     * @param string $prefix
     *
     * @return array Array of terms
     */
    public function get_common_index_terms_by_prefix($prefix)
    {
        require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';
        $terms = get_specific_field_values_list_by_prefix(
            $prefix,
            $this->cc,
            TOOL_LEARNPATH,
            $this->lp_id
        );
        $prefix_terms = [];
        if (!empty($terms)) {
            foreach ($terms as $term) {
                $prefix_terms[] = $term['value'];
            }
        }

        return $prefix_terms;
    }

    /**
     * Gets the number of items currently completed.
     *
     * @param bool $failedStatusException flag to determine the failed status is not considered progressed
     *
     * @return int The number of items currently completed
     */
    public function get_complete_items_count($failedStatusException = false, $typeListNotToCount = [])
    {
        $i = 0;
        $completedStatusList = [
            'completed',
            'passed',
            'succeeded',
            'browsed',
        ];

        if (!$failedStatusException) {
            $completedStatusList[] = 'failed';
        }

        if ($this->debug) {
            error_log('START - get_complete_items_count');
            error_log('Counting steps with status in: '.print_r($completedStatusList, 1));
        }

        $chapters = self::getChapterTypes();
        if (!empty($typeListNotToCount)) {
            $typeListNotToCount = array_merge($typeListNotToCount, $chapters);
        } else {
            $typeListNotToCount = $chapters;
        }

        foreach ($this->items as $id => $dummy) {
            // Trying failed and browsed considered "progressed" as well.
            if ($this->items[$id]->status_is($completedStatusList) &&
                !in_array($this->items[$id]->get_type(), $typeListNotToCount)
            ) {
                $i++;
            }
        }

        if ($this->debug) {
            error_log('END - Count: '.$i);
        }

        return $i;
    }

    /**
     * Gets the current item ID.
     *
     * @return int The current learnpath item id
     */
    public function get_current_item_id()
    {
        $current = 0;
        if (!empty($this->current)) {
            $current = (int) $this->current;
        }

        return $current;
    }

    /**
     * Force to get the first learnpath item id.
     *
     * @return int The current learnpath item id
     */
    public function get_first_item_id()
    {
        $current = 0;
        if (is_array($this->ordered_items)) {
            $current = $this->ordered_items[0];
        }

        return $current;
    }

    /**
     * Gets the total number of items available for viewing in this SCORM.
     *
     * @return int The total number of items
     */
    public function get_total_items_count()
    {
        return count($this->items);
    }

    /**
     * Gets the total number of items available for viewing in this SCORM but without chapters.
     *
     * @return int The total no-chapters number of items
     */
    public function getTotalItemsCountWithoutDirs($typeListNotToCount = [])
    {
        $total = 0;
        $chapters = self::getChapterTypes();
        if (!empty($typeListNotToCount)) {
            $typeListNotToCount = array_merge($typeListNotToCount, $chapters);
        } else {
            $typeListNotToCount = $chapters;
        }
        foreach ($this->items as $temp2) {
            if (!in_array($temp2->get_type(), $typeListNotToCount)) {
                $total++;
            }
        }

        return $total;
    }

    /**
     *  Sets the first element URL.
     */
    public function first()
    {
        if ($this->debug > 0) {
            error_log('In learnpath::first()', 0);
            error_log('$this->last_item_seen '.$this->last_item_seen);
        }

        // Test if the last_item_seen exists and is not a dir.
        if (count($this->ordered_items) == 0) {
            $this->index = 0;
        }

        if (!empty($this->last_item_seen) &&
            !empty($this->items[$this->last_item_seen]) &&
            $this->items[$this->last_item_seen]->get_type() != 'dir'
            //with this change (below) the LP will NOT go to the next item, it will take lp item we left
            //&& !$this->items[$this->last_item_seen]->is_done()
        ) {
            if ($this->debug > 2) {
                error_log(
                    'In learnpath::first() - Last item seen is '.$this->last_item_seen.' of type '.
                    $this->items[$this->last_item_seen]->get_type()
                );
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
                    error_log('Last item ('.$this->last_item_seen.') was found in items but not in ordered_items, panic!', 0);
                }

                return false;
            } else {
                $this->last = $this->last_item_seen;
                $this->current = $this->last_item_seen;
                $this->index = $index;
            }
        } else {
            if ($this->debug > 2) {
                error_log('In learnpath::first() - No last item seen', 0);
            }
            $index = 0;
            // Loop through all ordered items and stop at the first item that is
            // not a directory *and* that has not been completed yet.
            while (!empty($this->ordered_items[$index]) &&
                is_a($this->items[$this->ordered_items[$index]], 'learnpathItem') &&
                (
                    $this->items[$this->ordered_items[$index]]->get_type() == 'dir' ||
                    $this->items[$this->ordered_items[$index]]->is_done() === true
                ) && $index < $this->max_ordered_items) {
                $index++;
            }

            $this->last = $this->current;
            // current is
            $this->current = isset($this->ordered_items[$index]) ? $this->ordered_items[$index] : null;
            $this->index = $index;
            if ($this->debug > 2) {
                error_log('$index '.$index);
                error_log('In learnpath::first() - No last item seen');
                error_log('New last = '.$this->last.'('.$this->ordered_items[$index].')');
            }
        }
        if ($this->debug > 2) {
            error_log('In learnpath::first() - First item is '.$this->get_current_item_id());
        }
    }

    /**
     * Gets the js library from the database.
     *
     * @return string The name of the javascript library to be used
     */
    public function get_js_lib()
    {
        $lib = '';
        if (!empty($this->js_lib)) {
            $lib = $this->js_lib;
        }

        return $lib;
    }

    /**
     * Gets the learnpath database ID.
     *
     * @return int Learnpath ID in the lp table
     */
    public function get_id()
    {
        if (!empty($this->lp_id)) {
            return (int) $this->lp_id;
        }

        return 0;
    }

    /**
     * Gets the last element URL.
     *
     * @return string URL to load into the viewer
     */
    public function get_last()
    {
        // This is just in case the lesson doesn't cointain a valid scheme, just to avoid "Notices"
        if (count($this->ordered_items) > 0) {
            $this->index = count($this->ordered_items) - 1;

            return $this->ordered_items[$this->index];
        }

        return false;
    }

    /**
     * Get the last element in the first level.
     * Unlike learnpath::get_last this function doesn't consider the subsection' elements.
     *
     * @return mixed
     */
    public function getLastInFirstLevel()
    {
        try {
            $lastId = Database::getManager()
                ->createQuery('SELECT i.iid FROM ChamiloCourseBundle:CLpItem i
                WHERE i.lpId = :lp AND i.parentItemId = 0 AND i.itemType != :type ORDER BY i.displayOrder DESC')
                ->setMaxResults(1)
                ->setParameters(['lp' => $this->lp_id, 'type' => TOOL_LP_FINAL_ITEM])
                ->getSingleScalarResult();

            return $lastId;
        } catch (Exception $exception) {
            return 0;
        }
    }

    /**
     * Get the learning path name by id.
     *
     * @param int $lpId
     *
     * @return mixed
     */
    public static function getLpNameById($lpId)
    {
        $em = Database::getManager();

        return $em->createQuery('SELECT clp.name FROM ChamiloCourseBundle:CLp clp
            WHERE clp.iid = :iid')
            ->setParameter('iid', $lpId)
            ->getSingleScalarResult();
    }

    /**
     * Gets the navigation bar for the learnpath display screen.
     *
     * @param string $barId
     *
     * @return string The HTML string to use as a navigation bar
     */
    public function get_navigation_bar($barId = '')
    {
        if (empty($barId)) {
            $barId = 'control-top';
        }
        $lpId = $this->lp_id;
        $mycurrentitemid = $this->get_current_item_id();

        $reportingText = get_lang('Reporting');
        $previousText = get_lang('ScormPrevious');
        $nextText = get_lang('ScormNext');
        $fullScreenText = get_lang('ScormExitFullScreen');

        $settings = api_get_configuration_value('lp_view_settings');
        $display = isset($settings['display']) ? $settings['display'] : false;
        $reportingIcon = '
            <a class="icon-toolbar"
                id="stats_link"
                href="lp_controller.php?action=stats&'.api_get_cidreq(true).'&lp_id='.$lpId.'"
                onclick="window.parent.API.save_asset(); return true;"
                target="content_name" title="'.$reportingText.'">
                <span class="fa fa-info"></span><span class="sr-only">'.$reportingText.'</span>
            </a>';

        if (!empty($display)) {
            $showReporting = isset($display['show_reporting_icon']) ? $display['show_reporting_icon'] : true;
            if ($showReporting === false) {
                $reportingIcon = '';
            }
        }

        $hideArrows = false;
        if (isset($settings['display']) && isset($settings['display']['hide_lp_arrow_navigation'])) {
            $hideArrows = $settings['display']['hide_lp_arrow_navigation'];
        }

        $previousIcon = '';
        $nextIcon = '';
        if ($hideArrows === false) {
            $previousIcon = '
                <a class="icon-toolbar" id="scorm-previous" href="#"
                    onclick="switch_item('.$mycurrentitemid.',\'previous\');return false;" title="'.$previousText.'">
                    <span class="fa fa-chevron-left"></span><span class="sr-only">'.$previousText.'</span>
                </a>';

            $nextIcon = '
                <a class="icon-toolbar" id="scorm-next" href="#"
                    onclick="switch_item('.$mycurrentitemid.',\'next\');return false;" title="'.$nextText.'">
                    <span class="fa fa-chevron-right"></span><span class="sr-only">'.$nextText.'</span>
                </a>';
        }

        if ($this->mode === 'fullscreen') {
            $navbar = '
                  <span id="'.$barId.'" class="buttons">
                    '.$reportingIcon.'
                    '.$previousIcon.'
                    '.$nextIcon.'
                    <a class="icon-toolbar" id="view-embedded"
                        href="lp_controller.php?action=mode&mode=embedded" target="_top" title="'.$fullScreenText.'">
                        <span class="fa fa-columns"></span><span class="sr-only">'.$fullScreenText.'</span>
                    </a>
                  </span>';
        } else {
            $navbar = '
                 <span id="'.$barId.'" class="buttons text-right">
                    '.$reportingIcon.'
                    '.$previousIcon.'
                    '.$nextIcon.'
                </span>';
        }

        return $navbar;
    }

    /**
     * Gets the next resource in queue (url).
     *
     * @return string URL to load into the viewer
     */
    public function get_next_index()
    {
        // TODO
        $index = $this->index;
        $index++;
        while (
            !empty($this->ordered_items[$index]) && ($this->items[$this->ordered_items[$index]]->get_type() == 'dir') &&
            $index < $this->max_ordered_items
        ) {
            $index++;
            if ($index == $this->max_ordered_items) {
                if ($this->items[$this->ordered_items[$index]]->get_type() == 'dir') {
                    return $this->index;
                }

                return $index;
            }
        }
        if (empty($this->ordered_items[$index])) {
            return $this->index;
        }

        return $index;
    }

    /**
     * Gets item_id for the next element.
     *
     * @return int Next item (DB) ID
     */
    public function get_next_item_id()
    {
        $new_index = $this->get_next_index();
        if (!empty($new_index)) {
            if (isset($this->ordered_items[$new_index])) {
                return $this->ordered_items[$new_index];
            }
        }

        return 0;
    }

    /**
     * Returns the package type ('scorm','aicc','scorm2004','dokeos','ppt'...).
     *
     * Generally, the package provided is in the form of a zip file, so the function
     * has been written to test a zip file. If not a zip, the function will return the
     * default return value: ''
     *
     * @param string $file_path the path to the file
     * @param string $file_name the original name of the file
     *
     * @return string 'scorm','aicc','scorm2004','dokeos', 'error-empty-package' if the package is empty, or '' if the package cannot be recognized
     */
    public static function getPackageType($file_path, $file_name)
    {
        // Get name of the zip file without the extension.
        $file_info = pathinfo($file_name);
        $extension = $file_info['extension']; // Extension only.
        if (!empty($_POST['ppt2lp']) && !in_array(strtolower($extension), [
                'dll',
                'exe',
            ])) {
            return 'oogie';
        }
        if (!empty($_POST['woogie']) && !in_array(strtolower($extension), [
                'dll',
                'exe',
            ])) {
            return 'woogie';
        }

        $zipFile = new PclZip($file_path);
        // Check the zip content (real size and file extension).
        $zipContentArray = $zipFile->listContent();
        $package_type = '';
        $manifest = '';
        $aicc_match_crs = 0;
        $aicc_match_au = 0;
        $aicc_match_des = 0;
        $aicc_match_cst = 0;
        $countItems = 0;

        // The following loop should be stopped as soon as we found the right imsmanifest.xml (how to recognize it?).
        if (is_array($zipContentArray)) {
            $countItems = count($zipContentArray);
            if ($countItems > 0) {
                foreach ($zipContentArray as $thisContent) {
                    if (preg_match('~.(php.*|phtml)$~i', $thisContent['filename'])) {
                        // New behaviour: Don't do anything. These files will be removed in scorm::import_package.
                    } elseif (stristr($thisContent['filename'], 'imsmanifest.xml') !== false) {
                        $manifest = $thisContent['filename']; // Just the relative directory inside scorm/
                        $package_type = 'scorm';
                        break; // Exit the foreach loop.
                    } elseif (
                        preg_match('/aicc\//i', $thisContent['filename']) ||
                        in_array(
                            strtolower(pathinfo($thisContent['filename'], PATHINFO_EXTENSION)),
                            ['crs', 'au', 'des', 'cst']
                        )
                    ) {
                        $ext = strtolower(pathinfo($thisContent['filename'], PATHINFO_EXTENSION));
                        switch ($ext) {
                            case 'crs':
                                $aicc_match_crs = 1;
                                break;
                            case 'au':
                                $aicc_match_au = 1;
                                break;
                            case 'des':
                                $aicc_match_des = 1;
                                break;
                            case 'cst':
                                $aicc_match_cst = 1;
                                break;
                            default:
                                break;
                        }
                        //break; // Don't exit the loop, because if we find an imsmanifest afterwards, we want it, not the AICC.
                    } else {
                        $package_type = '';
                    }
                }
            }
        }

        if (empty($package_type) && 4 == ($aicc_match_crs + $aicc_match_au + $aicc_match_des + $aicc_match_cst)) {
            // If found an aicc directory... (!= false means it cannot be false (error) or 0 (no match)).
            $package_type = 'aicc';
        }

        // Try with chamilo course builder
        if (empty($package_type)) {
            // Sometimes users will try to upload an empty zip, or a zip with
            // only a folder. Catch that and make the calling function aware.
            // If the single file was the imsmanifest.xml, then $package_type
            // would be 'scorm' and we wouldn't be here.
            if ($countItems < 2) {
                return 'error-empty-package';
            }
            $package_type = 'chamilo';
        }

        return $package_type;
    }

    /**
     * Gets the previous resource in queue (url). Also initialises time values for this viewing.
     *
     * @return string URL to load into the viewer
     */
    public function get_previous_index()
    {
        $index = $this->index;
        if (isset($this->ordered_items[$index - 1])) {
            $index--;
            while (isset($this->ordered_items[$index]) &&
                ($this->items[$this->ordered_items[$index]]->get_type() == 'dir')
            ) {
                $index--;
                if ($index < 0) {
                    return $this->index;
                }
            }
        }

        return $index;
    }

    /**
     * Gets item_id for the next element.
     *
     * @return int Previous item (DB) ID
     */
    public function get_previous_item_id()
    {
        $index = $this->get_previous_index();

        return $this->ordered_items[$index];
    }

    /**
     * Returns the HTML necessary to print a mediaplayer block inside a page.
     *
     * @param int    $lpItemId
     * @param string $autostart
     *
     * @return string The mediaplayer HTML
     */
    public function get_mediaplayer($lpItemId, $autostart = 'true')
    {
        $course_id = api_get_course_int_id();
        $courseInfo = api_get_course_info();
        $lpItemId = (int) $lpItemId;

        if (empty($courseInfo) || empty($lpItemId)) {
            return '';
        }
        $item = isset($this->items[$lpItemId]) ? $this->items[$lpItemId] : null;

        if (empty($item)) {
            return '';
        }

        $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $tbl_lp_item_view = Database::get_course_table(TABLE_LP_ITEM_VIEW);
        $itemViewId = (int) $item->db_item_view_id;

        // Getting all the information about the item.
        $sql = "SELECT lp_view.status
                FROM $tbl_lp_item as lpi
                INNER JOIN $tbl_lp_item_view as lp_view
                ON (lpi.iid = lp_view.lp_item_id)
                WHERE
                    lp_view.iid = $itemViewId AND
                    lpi.iid = $lpItemId AND
                    lp_view.c_id = $course_id";
        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);
        $output = '';
        $audio = $item->audio;

        if (!empty($audio)) {
            $list = $_SESSION['oLP']->get_toc();

            switch ($item->get_type()) {
                case 'quiz':
                    $type_quiz = false;
                    foreach ($list as $toc) {
                        if ($toc['id'] == $_SESSION['oLP']->current) {
                            $type_quiz = true;
                        }
                    }

                    if ($type_quiz) {
                        if ($_SESSION['oLP']->prevent_reinit == 1) {
                            $autostart_audio = $row['status'] === 'completed' ? 'false' : 'true';
                        } else {
                            $autostart_audio = $autostart;
                        }
                    }
                    break;
                case TOOL_READOUT_TEXT:
                    $autostart_audio = 'false';
                    break;
                default:
                    $autostart_audio = 'true';
            }

            $file = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document'.$audio;
            $url = api_get_path(WEB_COURSE_PATH).$courseInfo['path'].'/document'.$audio.'?'.api_get_cidreq();

            $player = Display::getMediaPlayer(
                $file,
                [
                    'id' => 'lp_audio_media_player',
                    'url' => $url,
                    'autoplay' => $autostart_audio,
                    'width' => '100%',
                ]
            );

            // The mp3 player.
            $output = '<div id="container">';
            $output .= $player;
            $output .= '</div>';
        }

        return $output;
    }

    /**
     * @param int   $studentId
     * @param int   $prerequisite
     * @param array $courseInfo
     * @param int   $sessionId
     *
     * @return bool
     */
    public static function isBlockedByPrerequisite(
        $studentId,
        $prerequisite,
        $courseInfo,
        $sessionId
    ) {
        if (empty($courseInfo)) {
            return false;
        }

        $courseId = $courseInfo['real_id'];

        $allow = api_get_configuration_value('allow_teachers_to_access_blocked_lp_by_prerequisite');
        if ($allow) {
            if (api_is_allowed_to_edit() ||
                api_is_platform_admin(true) ||
                api_is_drh() ||
                api_is_coach($sessionId, $courseId, false)
            ) {
                return false;
            }
        }

        $isBlocked = false;
        if (!empty($prerequisite)) {
            $progress = self::getProgress(
                $prerequisite,
                $studentId,
                $courseId,
                $sessionId
            );
            if ($progress < 100) {
                $isBlocked = true;
            }

            if (Tracking::minimumTimeAvailable($sessionId, $courseId)) {
                // Block if it does not exceed minimum time
                // Minimum time (in minutes) to pass the learning path
                $accumulateWorkTime = self::getAccumulateWorkTimePrerequisite($prerequisite, $courseId);

                if ($accumulateWorkTime > 0) {
                    // Total time in course (sum of times in learning paths from course)
                    $accumulateWorkTimeTotal = self::getAccumulateWorkTimeTotal($courseId);

                    // Connect with the plugin_licences_course_session table
                    // which indicates what percentage of the time applies
                    // Minimum connection percentage
                    $perc = 100;
                    // Time from the course
                    $tc = $accumulateWorkTimeTotal;

                    // Percentage of the learning paths
                    $pl = $accumulateWorkTime / $accumulateWorkTimeTotal;
                    // Minimum time for each learning path
                    $accumulateWorkTime = ($pl * $tc * $perc / 100);

                    // Spent time (in seconds) so far in the learning path
                    $lpTimeList = Tracking::getCalculateTime($studentId, $courseId, $sessionId);
                    $lpTime = isset($lpTimeList[TOOL_LEARNPATH][$prerequisite]) ? $lpTimeList[TOOL_LEARNPATH][$prerequisite] : 0;

                    if ($lpTime < ($accumulateWorkTime * 60)) {
                        $isBlocked = true;
                    }
                }
            }
        }

        return $isBlocked;
    }

    /**
     * Checks if the learning path is visible for student after the progress
     * of its prerequisite is completed, considering the time availability and
     * the LP visibility.
     *
     * @param int   $lp_id
     * @param int   $student_id
     * @param array $courseInfo
     * @param int   $sessionId
     * @param bool  $checkSubscription Optional. Allow don't check if user is subscribed to the LP.
     *
     * @return bool
     */
    public static function is_lp_visible_for_student(
        $lp_id,
        $student_id,
        $courseInfo = [],
        $sessionId = 0,
        bool $checkSubscription = true
    ) {
        $courseInfo = empty($courseInfo) ? api_get_course_info() : $courseInfo;
        $lp_id = (int) $lp_id;
        $sessionId = (int) $sessionId;

        if (empty($courseInfo)) {
            return false;
        }

        if (empty($sessionId)) {
            $sessionId = api_get_session_id();
        }

        $courseId = $courseInfo['real_id'];

        $itemInfo = api_get_item_property_info(
            $courseId,
            TOOL_LEARNPATH,
            $lp_id,
            $sessionId
        );

        // If the item was deleted.
        if (isset($itemInfo['visibility']) && $itemInfo['visibility'] == 2) {
            return false;
        }

        // @todo remove this query and load the row info as a parameter
        $table = Database::get_course_table(TABLE_LP_MAIN);
        // Get current prerequisite
        $sql = "SELECT id, prerequisite, subscribe_users, publicated_on, expired_on, category_id
                FROM $table
                WHERE iid = $lp_id";
        $rs = Database::query($sql);
        $now = time();
        if (Database::num_rows($rs) > 0) {
            $row = Database::fetch_array($rs, 'ASSOC');
            if (!empty($row['category_id'])) {
                $category = self::getCategory($row['category_id']);
                if (self::categoryIsVisibleForStudent($category, api_get_user_entity($student_id)) === false) {
                    return false;
                }
            }

            $prerequisite = $row['prerequisite'];
            $is_visible = true;

            $isBlocked = self::isBlockedByPrerequisite(
                $student_id,
                $prerequisite,
                $courseInfo,
                $sessionId
            );

            if ($isBlocked) {
                $is_visible = false;
            }

            // Also check the time availability of the LP
            if ($is_visible) {
                // Adding visibility restrictions
                if (!empty($row['publicated_on'])) {
                    if ($now < api_strtotime($row['publicated_on'], 'UTC')) {
                        $is_visible = false;
                    }
                }
                // Blocking empty start times see BT#2800
                global $_custom;
                if (isset($_custom['lps_hidden_when_no_start_date']) &&
                    $_custom['lps_hidden_when_no_start_date']
                ) {
                    if (empty($row['publicated_on'])) {
                        $is_visible = false;
                    }
                }

                if (!empty($row['expired_on'])) {
                    if ($now > api_strtotime($row['expired_on'], 'UTC')) {
                        $is_visible = false;
                    }
                }
            }

            if ($is_visible && $checkSubscription) {
                $is_visible = self::isUserSubscribedToLp(
                    $row,
                    (int) $student_id,
                    $courseInfo,
                    (int) $sessionId
                );
            }

            return $is_visible;
        }

        return false;
    }

    public static function isUserSubscribedToLp(
        array $lpInfo,
        int $studentId,
        array $courseInfo,
        int $sessionId = 0
    ): bool {
        $subscriptionSettings = self::getSubscriptionSettings();

        // Check if the subscription users/group to a LP is ON
        if (isset($lpInfo['subscribe_users']) && $lpInfo['subscribe_users'] == 1 &&
            $subscriptionSettings['allow_add_users_to_lp'] === true
        ) {
            // Checking only the user visibility
            $userVisibility = api_get_item_visibility(
                $courseInfo,
                'learnpath',
                $lpInfo['id'],
                $sessionId,
                $studentId,
                'LearnpathSubscription'
            );

            if (1 == $userVisibility) {
                return true;
            }

            // Try group
            $userGroups = GroupManager::getAllGroupPerUserSubscription($studentId, $courseInfo['real_id']);

            if (!empty($userGroups)) {
                foreach ($userGroups as $groupInfo) {
                    $userVisibility = api_get_item_visibility(
                        $courseInfo,
                        'learnpath',
                        $lpInfo['id'],
                        $sessionId,
                        null,
                        'LearnpathSubscription',
                        $groupInfo['iid']
                    );

                    if (1 == $userVisibility) {
                        return true;
                    }
                }
            }

            return false;
        }

        return true;
    }

    /**
     * @param int $lpId
     * @param int $userId
     * @param int $courseId
     * @param int $sessionId
     *
     * @return int
     */
    public static function getProgress($lpId, $userId, $courseId, $sessionId = 0)
    {
        $lpId = (int) $lpId;
        $userId = (int) $userId;
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;

        $sessionCondition = api_get_session_condition($sessionId);
        $table = Database::get_course_table(TABLE_LP_VIEW);
        $sql = "SELECT progress FROM $table
                WHERE
                    c_id = $courseId AND
                    lp_id = $lpId AND
                    user_id = $userId $sessionCondition ";
        $res = Database::query($sql);

        $progress = 0;
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res);
            $progress = (int) $row['progress'];
        }

        return $progress;
    }

    /**
     * @param array $lpList
     * @param int   $userId
     * @param int   $courseId
     * @param int   $sessionId
     *
     * @return array
     */
    public static function getProgressFromLpList($lpList, $userId, $courseId, $sessionId = 0)
    {
        $lpList = array_map('intval', $lpList);
        if (empty($lpList)) {
            return [];
        }

        $lpList = implode("','", $lpList);

        $userId = (int) $userId;
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;

        $sessionCondition = api_get_session_condition($sessionId);
        $table = Database::get_course_table(TABLE_LP_VIEW);
        $sql = "SELECT lp_id, progress FROM $table
                WHERE
                    c_id = $courseId AND
                    lp_id IN ('".$lpList."') AND
                    user_id = $userId $sessionCondition ";
        $res = Database::query($sql);

        if (Database::num_rows($res) > 0) {
            $list = [];
            while ($row = Database::fetch_array($res)) {
                $list[$row['lp_id']] = $row['progress'];
            }

            return $list;
        }

        return [];
    }

    /**
     * Displays a progress bar
     * completed so far.
     *
     * @param int    $percentage Progress value to display
     * @param string $text_add   Text to display near the progress value
     *
     * @return string HTML string containing the progress bar
     */
    public static function get_progress_bar($percentage = -1, $text_add = '')
    {
        $text = $percentage.$text_add;
        $output = '<div class="progress">
            <div id="progress_bar_value"
                class="progress-bar progress-bar-success" role="progressbar"
                aria-valuenow="'.$percentage.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$text.';">
            '.$text.'
            </div>
        </div>';

        return $output;
    }

    /**
     * @param string $mode can be '%' or 'abs'
     *                     otherwise this value will be used $this->progress_bar_mode
     *
     * @return string
     */
    public function getProgressBar($mode = null)
    {
        [$percentage, $text_add] = $this->get_progress_bar_text($mode);

        return self::get_progress_bar($percentage, $text_add);
    }

    /**
     * Gets the progress bar info to display inside the progress bar.
     * Also used by scorm_api.php.
     *
     * @param string $mode Mode of display (can be '%' or 'abs').abs means
     *                     we display a number of completed elements per total elements
     * @param int    $add  Additional steps to fake as completed
     *
     * @return array Percentage or number and symbol (% or /xx)
     */
    public function get_progress_bar_text($mode = '', $add = 0)
    {
        if (empty($mode)) {
            $mode = $this->progress_bar_mode;
        }
        $text = '';
        $percentage = 0;
        // If the option to use the score as progress is set for this learning
        // path, then the rules are completely different: we assume only one
        // item exists and the progress of the LP depends on the score
        $scoreAsProgressSetting = api_get_configuration_value('lp_score_as_progress_enable');

        if ($scoreAsProgressSetting === true) {
            $scoreAsProgress = $this->getUseScoreAsProgress();
            if ($scoreAsProgress) {
                // Get single item's score
                $itemId = $this->get_current_item_id();
                $item = $this->getItem($itemId);
                $score = $item->get_score();
                $maxScore = $item->get_max();
                if ($mode = '%') {
                    if (!empty($maxScore)) {
                        $percentage = ((float) $score / (float) $maxScore) * 100;
                    }
                    $percentage = number_format($percentage, 0);
                    $text = '%';
                } else {
                    $percentage = $score;
                    $text = '/'.$maxScore;
                }

                return [$percentage, $text];
            }
        }
        // otherwise just continue the normal processing of progress
        $total_items = $this->getTotalItemsCountWithoutDirs();
        $completeItems = $this->get_complete_items_count();
        if ($add != 0) {
            $completeItems += $add;
        }
        if ($completeItems > $total_items) {
            $completeItems = $total_items;
        }

        if ($mode == '%') {
            if ($total_items > 0) {
                $percentage = ((float) $completeItems / (float) $total_items) * 100;
            }
            $percentage = number_format($percentage, 0);
            $text = '%';
        } elseif ($mode === 'abs') {
            $percentage = $completeItems;
            $text = '/'.$total_items;
        }

        return [
            $percentage,
            $text,
        ];
    }

    /**
     * Gets the progress bar mode.
     *
     * @return string The progress bar mode attribute
     */
    public function get_progress_bar_mode()
    {
        if (!empty($this->progress_bar_mode)) {
            return $this->progress_bar_mode;
        }

        return '%';
    }

    /**
     * Gets the learnpath theme (remote or local).
     *
     * @return string Learnpath theme
     */
    public function get_theme()
    {
        if (!empty($this->theme)) {
            return $this->theme;
        }

        return '';
    }

    /**
     * Gets the learnpath session id.
     *
     * @return int
     */
    public function get_lp_session_id()
    {
        if (!empty($this->lp_session_id)) {
            return (int) $this->lp_session_id;
        }

        return 0;
    }

    /**
     * Gets the learnpath image.
     *
     * @return string Web URL of the LP image
     */
    public function get_preview_image()
    {
        if (!empty($this->preview_image)) {
            return $this->preview_image;
        }

        return '';
    }

    /**
     * @param string $size
     * @param string $path_type
     *
     * @return bool|string
     */
    public function get_preview_image_path($size = null, $path_type = 'web')
    {
        $preview_image = $this->get_preview_image();
        if (isset($preview_image) && !empty($preview_image)) {
            $image_sys_path = api_get_path(SYS_COURSE_PATH).$this->course_info['path'].'/upload/learning_path/images/';
            $image_path = api_get_path(WEB_COURSE_PATH).$this->course_info['path'].'/upload/learning_path/images/';

            if (isset($size)) {
                $info = pathinfo($preview_image);
                $image_custom_size = $info['filename'].'.'.$size.'.'.$info['extension'];

                if (file_exists($image_sys_path.$image_custom_size)) {
                    if ($path_type == 'web') {
                        return $image_path.$image_custom_size;
                    } else {
                        return $image_sys_path.$image_custom_size;
                    }
                }
            } else {
                if ($path_type == 'web') {
                    return $image_path.$preview_image;
                } else {
                    return $image_sys_path.$preview_image;
                }
            }
        }

        return false;
    }

    /**
     * Gets the learnpath author.
     *
     * @return string LP's author
     */
    public function get_author()
    {
        if (!empty($this->author)) {
            return $this->author;
        }

        return '';
    }

    /**
     * Gets hide table of contents.
     *
     * @return int
     */
    public function getHideTableOfContents()
    {
        return (int) $this->hide_toc_frame;
    }

    /**
     * Generate a new prerequisites string for a given item. If this item was a sco and
     * its prerequisites were strings (instead of IDs), then transform those strings into
     * IDs, knowing that SCORM IDs are kept in the "ref" field of the lp_item table.
     * Prefix all item IDs that end-up in the prerequisites string by "ITEM_" to use the
     * same rule as the scormExport() method.
     *
     * @param int $item_id Item ID
     *
     * @return string Prerequisites string ready for the export as SCORM
     */
    public function get_scorm_prereq_string($item_id)
    {
        if ($this->debug > 0) {
            error_log('In learnpath::get_scorm_prereq_string()');
        }
        if (!is_object($this->items[$item_id])) {
            return false;
        }
        /** @var learnpathItem $oItem */
        $oItem = $this->items[$item_id];
        $prereq = $oItem->get_prereq_string();

        if (empty($prereq)) {
            return '';
        }
        if (preg_match('/^\d+$/', $prereq) &&
            isset($this->items[$prereq]) &&
            is_object($this->items[$prereq])
        ) {
            // If the prerequisite is a simple integer ID and this ID exists as an item ID,
            // then simply return it (with the ITEM_ prefix).
            //return 'ITEM_' . $prereq;
            return $this->items[$prereq]->ref;
        } else {
            if (isset($this->refs_list[$prereq])) {
                // It's a simple string item from which the ID can be found in the refs list,
                // so we can transform it directly to an ID for export.
                return $this->items[$this->refs_list[$prereq]]->ref;
            } elseif (isset($this->refs_list['ITEM_'.$prereq])) {
                return $this->items[$this->refs_list['ITEM_'.$prereq]]->ref;
            } else {
                // The last case, if it's a complex form, then find all the IDs (SCORM strings)
                // and replace them, one by one, by the internal IDs (chamilo db)
                // TODO: Modify the '*' replacement to replace the multiplier in front of it
                // by a space as well.
                $find = [
                    '&',
                    '|',
                    '~',
                    '=',
                    '<>',
                    '{',
                    '}',
                    '*',
                    '(',
                    ')',
                ];
                $replace = [
                    ' ',
                    ' ',
                    ' ',
                    ' ',
                    ' ',
                    ' ',
                    ' ',
                    ' ',
                    ' ',
                    ' ',
                ];
                $prereq_mod = str_replace($find, $replace, $prereq);
                $ids = explode(' ', $prereq_mod);
                foreach ($ids as $id) {
                    $id = trim($id);
                    if (isset($this->refs_list[$id])) {
                        $prereq = preg_replace(
                            '/[^a-zA-Z_0-9]('.$id.')[^a-zA-Z_0-9]/',
                            'ITEM_'.$this->refs_list[$id],
                            $prereq
                        );
                    }
                }

                return $prereq;
            }
        }
    }

    /**
     * Returns the XML DOM document's node.
     *
     * @param DOMNodeList $children Reference to a list of objects to search for the given ITEM_*
     * @param string      $id       The identifier to look for
     *
     * @return mixed The reference to the element found with that identifier. False if not found
     */
    public function get_scorm_xml_node(DOMNodeList &$children, string $id, $nodeName = 'item', $attributeName = 'identifier')
    {
        for ($i = 0; $i < $children->length; $i++) {
            $item_temp = $children->item($i);
            if ($item_temp->nodeName == $nodeName) {
                if ($item_temp instanceof DOMElement && $item_temp->getAttribute($attributeName) == $id) {
                    return $item_temp;
                }
            }
            $subchildren = $item_temp->childNodes;
            if ($subchildren && $subchildren->length > 0) {
                $val = $this->get_scorm_xml_node($subchildren, $id, $nodeName, $attributeName);
                if (is_object($val)) {
                    return $val;
                }
            }
        }

        return false;
    }

    /**
     * Gets the status list for all LP's items.
     *
     * @return array Array of [index] => [item ID => current status]
     */
    public function get_items_status_list()
    {
        $list = [];
        foreach ($this->ordered_items as $item_id) {
            $list[] = [
                $item_id => $this->items[$item_id]->get_status(),
            ];
        }

        return $list;
    }

    /**
     * Return the number of interactions for the given learnpath Item View ID.
     * This method can be used as static.
     *
     * @param int $lp_iv_id  Item View ID
     * @param int $course_id course id
     *
     * @return int
     */
    public static function get_interactions_count_from_db($lp_iv_id, $course_id)
    {
        $table = Database::get_course_table(TABLE_LP_IV_INTERACTION);
        $lp_iv_id = (int) $lp_iv_id;
        $course_id = (int) $course_id;

        $sql = "SELECT count(*) FROM $table
                WHERE c_id = $course_id AND lp_iv_id = $lp_iv_id";
        $res = Database::query($sql);
        $num = 0;
        if (Database::num_rows($res)) {
            $row = Database::fetch_array($res);
            $num = $row[0];
        }

        return $num;
    }

    /**
     * Return the interactions as an array for the given lp_iv_id.
     * This method can be used as static.
     *
     * @param int $lp_iv_id Learnpath Item View ID
     *
     * @return array
     *
     * @todo    Transcode labels instead of switching to HTML (which requires to know the encoding of the LP)
     */
    public static function get_iv_interactions_array($lp_iv_id, $course_id = 0)
    {
        $course_id = empty($course_id) ? api_get_course_int_id() : (int) $course_id;
        $list = [];
        $table = Database::get_course_table(TABLE_LP_IV_INTERACTION);
        $lp_iv_id = (int) $lp_iv_id;

        if (empty($lp_iv_id) || empty($course_id)) {
            return [];
        }

        $sql = "SELECT * FROM $table
                WHERE c_id = ".$course_id." AND lp_iv_id = $lp_iv_id
                ORDER BY order_id ASC";
        $res = Database::query($sql);
        $num = Database::num_rows($res);
        if ($num > 0) {
            $list[] = [
                'order_id' => api_htmlentities(get_lang('Order'), ENT_QUOTES),
                'id' => api_htmlentities(get_lang('InteractionID'), ENT_QUOTES),
                'type' => api_htmlentities(get_lang('Type'), ENT_QUOTES),
                'time' => api_htmlentities(get_lang('TimeFinished'), ENT_QUOTES),
                'correct_responses' => api_htmlentities(get_lang('CorrectAnswers'), ENT_QUOTES),
                'student_response' => api_htmlentities(get_lang('StudentResponse'), ENT_QUOTES),
                'result' => api_htmlentities(get_lang('Result'), ENT_QUOTES),
                'latency' => api_htmlentities(get_lang('LatencyTimeSpent'), ENT_QUOTES),
                'student_response_formatted' => '',
            ];
            while ($row = Database::fetch_array($res)) {
                $studentResponseFormatted = urldecode($row['student_response']);
                $content_student_response = explode('__|', $studentResponseFormatted);
                if (count($content_student_response) > 0) {
                    if (count($content_student_response) >= 3) {
                        // Pop the element off the end of array.
                        array_pop($content_student_response);
                    }
                    $studentResponseFormatted = implode(',', $content_student_response);
                }

                $list[] = [
                    'order_id' => $row['order_id'] + 1,
                    'id' => urldecode($row['interaction_id']), //urldecode because they often have %2F or stuff like that
                    'type' => $row['interaction_type'],
                    'time' => $row['completion_time'],
                    'correct_responses' => '', // Hide correct responses from students.
                    'student_response' => $row['student_response'],
                    'result' => $row['result'],
                    'latency' => $row['latency'],
                    'student_response_formatted' => $studentResponseFormatted,
                ];
            }
        }

        return $list;
    }

    /**
     * Return the number of objectives for the given learnpath Item View ID.
     * This method can be used as static.
     *
     * @param int $lp_iv_id  Item View ID
     * @param int $course_id Course ID
     *
     * @return int Number of objectives
     */
    public static function get_objectives_count_from_db($lp_iv_id, $course_id)
    {
        $table = Database::get_course_table(TABLE_LP_IV_OBJECTIVE);
        $course_id = (int) $course_id;
        $lp_iv_id = (int) $lp_iv_id;
        $sql = "SELECT count(*) FROM $table
                WHERE c_id = $course_id AND lp_iv_id = $lp_iv_id";
        //@todo seems that this always returns 0
        $res = Database::query($sql);
        $num = 0;
        if (Database::num_rows($res)) {
            $row = Database::fetch_array($res);
            $num = $row[0];
        }

        return $num;
    }

    /**
     * Return the objectives as an array for the given lp_iv_id.
     * This method can be used as static.
     *
     * @param int $lpItemViewId Learnpath Item View ID
     * @param int $course_id
     *
     * @return array
     *
     * @todo    Translate labels
     */
    public static function get_iv_objectives_array($lpItemViewId = 0, $course_id = 0)
    {
        $course_id = empty($course_id) ? api_get_course_int_id() : (int) $course_id;
        $lpItemViewId = (int) $lpItemViewId;

        if (empty($course_id) || empty($lpItemViewId)) {
            return [];
        }

        $table = Database::get_course_table(TABLE_LP_IV_OBJECTIVE);
        $sql = "SELECT * FROM $table
                WHERE c_id = $course_id AND lp_iv_id = $lpItemViewId
                ORDER BY order_id ASC";
        $res = Database::query($sql);
        $num = Database::num_rows($res);
        $list = [];
        if ($num > 0) {
            $list[] = [
                'order_id' => api_htmlentities(get_lang('Order'), ENT_QUOTES),
                'objective_id' => api_htmlentities(get_lang('ObjectiveID'), ENT_QUOTES),
                'score_raw' => api_htmlentities(get_lang('ObjectiveRawScore'), ENT_QUOTES),
                'score_max' => api_htmlentities(get_lang('ObjectiveMaxScore'), ENT_QUOTES),
                'score_min' => api_htmlentities(get_lang('ObjectiveMinScore'), ENT_QUOTES),
                'status' => api_htmlentities(get_lang('ObjectiveStatus'), ENT_QUOTES),
            ];
            while ($row = Database::fetch_array($res)) {
                $list[] = [
                    'order_id' => $row['order_id'] + 1,
                    'objective_id' => urldecode($row['objective_id']), // urldecode() because they often have %2F
                    'score_raw' => $row['score_raw'],
                    'score_max' => $row['score_max'],
                    'score_min' => $row['score_min'],
                    'status' => $row['status'],
                ];
            }
        }

        return $list;
    }

    /**
     * Generate and return the table of contents for this learnpath. The (flat) table returned can be
     * used by get_html_toc() to be ready to display.
     *
     * @return array TOC as a table with 4 elements per row: title, link, status and level
     */
    public function get_toc()
    {
        $toc = [];
        foreach ($this->ordered_items as $item_id) {
            // TODO: Change this link generation and use new function instead.
            $toc[] = [
                'id' => $item_id,
                'title' => $this->items[$item_id]->get_title(),
                'status' => $this->items[$item_id]->get_status(),
                'level' => $this->items[$item_id]->get_level(),
                'type' => $this->items[$item_id]->get_type(),
                'description' => $this->items[$item_id]->get_description(),
                'path' => $this->items[$item_id]->get_path(),
                'parent' => $this->items[$item_id]->get_parent(),
            ];
        }

        return $toc;
    }

    /**
     * Returns the CSS class name associated with a given item status.
     *
     * @param $status string an item status
     *
     * @return string CSS class name
     */
    public static function getStatusCSSClassName($status)
    {
        if (array_key_exists($status, self::STATUS_CSS_CLASS_NAME)) {
            return self::STATUS_CSS_CLASS_NAME[$status];
        }

        return '';
    }

    /**
     * Generate the tree of contents for this learnpath as an associative array tree
     * with keys id, title, status, type, description, path, parent_id, children
     * (title and descriptions as secured)
     * and clues for CSS class composition:
     *  - booleans is_current, is_parent_of_current, is_chapter
     *  - string status_css_class_name.
     *
     * @param $parentId int restrict returned list to children of this parent
     *
     * @return array TOC as a table
     */
    public function getTOCTree($parentId = 0)
    {
        $toc = [];
        $currentItemId = $this->get_current_item_id();

        foreach ($this->ordered_items as $itemId) {
            $item = $this->items[$itemId];
            if ($item->get_parent() == $parentId) {
                $title = $item->get_title();
                if (empty($title)) {
                    $title = self::rl_get_resource_name(api_get_course_id(), $this->get_id(), $itemId);
                }

                $itemData = [
                    'id' => $itemId,
                    'title' => Security::remove_XSS($title),
                    'status' => $item->get_status(),
                    'level' => $item->get_level(), // FIXME should not be needed
                    'type' => $item->get_type(),
                    'description' => Security::remove_XSS($item->get_description()),
                    'path' => $item->get_path(),
                    'parent_id' => $item->get_parent(),
                    'children' => $this->getTOCTree($itemId),
                    'is_current' => ($itemId == $currentItemId),
                    'is_parent_of_current' => false,
                    'is_chapter' => in_array($item->get_type(), self::getChapterTypes()),
                    'status_css_class_name' => $this->getStatusCSSClassName($item->get_status()),
                    'current_id' => $currentItemId, // FIXME should not be needed, not a property of item
                ];

                if (!empty($itemData['children'])) {
                    foreach ($itemData['children'] as $child) {
                        if ($child['is_current'] || $child['is_parent_of_current']) {
                            $itemData['is_parent_of_current'] = true;
                            break;
                        }
                    }
                }

                $toc[] = $itemData;
            }
        }

        return $toc;
    }

    /**
     * Generate and return the table of contents for this learnpath. The JS
     * table returned is used inside of scorm_api.php.
     *
     * @param string $varname
     *
     * @return string A JS array variable construction
     */
    public function get_items_details_as_js($varname = 'olms.lms_item_types')
    {
        $toc = $varname.' = new Array();';
        foreach ($this->ordered_items as $item_id) {
            $toc .= $varname."['i$item_id'] = '".$this->items[$item_id]->get_type()."';";
        }

        return $toc;
    }

    /**
     * Gets the learning path type.
     *
     * @param bool $get_name Return the name? If false, return the ID. Default is false.
     *
     * @return mixed Type ID or name, depending on the parameter
     */
    public function get_type($get_name = false)
    {
        $res = false;
        if (!empty($this->type) && (!$get_name)) {
            $res = $this->type;
        }

        return $res;
    }

    /**
     * Gets the learning path type as static method.
     *
     * @param int $lp_id
     *
     * @return mixed Returns the lp_type: 1 = Chamilo lms / 2 = SCORM
     */
    public static function get_type_static($lp_id = 0)
    {
        $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
        $lp_id = (int) $lp_id;
        $sql = "SELECT lp_type FROM $tbl_lp
                WHERE iid = $lp_id";
        $res = Database::query($sql);
        if ($res === false) {
            return null;
        }
        if (Database::num_rows($res) <= 0) {
            return null;
        }
        $row = Database::fetch_array($res);

        return $row['lp_type'];
    }

    /**
     * Gets a flat list of item IDs ordered for display (level by level ordered by order_display)
     * This method can be used as abstract and is recursive.
     *
     * @param int $lp        Learnpath ID
     * @param int $parent    Parent ID of the items to look for
     * @param int $course_id
     *
     * @return array Ordered list of item IDs (empty array on error)
     */
    public static function get_flat_ordered_items_list($lp = 1, $parent = 0, $course_id = 0)
    {
        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        } else {
            $course_id = (int) $course_id;
        }
        $list = [];

        if (empty($lp)) {
            return $list;
        }

        $lp = (int) $lp;
        $parent = (int) $parent;

        $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $sql = "SELECT iid FROM $tbl_lp_item
                WHERE c_id = $course_id AND lp_id = $lp AND parent_item_id = $parent
                ORDER BY display_order";

        $res = Database::query($sql);
        while ($row = Database::fetch_array($res)) {
            $sublist = self::get_flat_ordered_items_list(
                $lp,
                $row['iid'],
                $course_id
            );
            $list[] = $row['iid'];
            foreach ($sublist as $item) {
                $list[] = $item;
            }
        }

        return $list;
    }

    /**
     * @return array
     */
    public static function getChapterTypes()
    {
        return [
            'dir',
        ];
    }

    /**
     * Uses the table generated by get_toc() and returns an HTML-formattedstring ready to display.
     *
     * @param $tree
     *
     * @return array HTML TOC ready to display
     */
    public function getParentToc($tree)
    {
        if (empty($tree)) {
            $tree = $this->get_toc();
        }
        $dirTypes = self::getChapterTypes();
        $myCurrentId = $this->get_current_item_id();
        $listParent = [];
        $listChildren = [];
        $listNotParent = [];
        $list = [];
        foreach ($tree as $subtree) {
            if (in_array($subtree['type'], $dirTypes)) {
                $listChildren = $this->getChildrenToc($tree, $subtree['id']);
                $subtree['children'] = $listChildren;
                if (!empty($subtree['children'])) {
                    foreach ($subtree['children'] as $subItem) {
                        if ($subItem['id'] == $this->current) {
                            $subtree['parent_current'] = 'in';
                            $subtree['current'] = 'on';
                        }
                    }
                }
                $listParent[] = $subtree;
            }
            if (!in_array($subtree['type'], $dirTypes) && $subtree['parent'] == null) {
                if (array_key_exists($subtree['status'], self::STATUS_CSS_CLASS_NAME)) {
                    $cssStatus = self::STATUS_CSS_CLASS_NAME[$subtree['status']];
                }

                $title = Security::remove_XSS($subtree['title']);
                unset($subtree['title']);

                if (empty($title)) {
                    $title = self::rl_get_resource_name(api_get_course_id(), $this->get_id(), $subtree['id']);
                }
                $classStyle = null;
                if ($subtree['id'] == $this->current) {
                    $classStyle = 'scorm_item_normal '.$classStyle.'scorm_highlight';
                } elseif (!in_array($subtree['type'], $dirTypes)) {
                    $classStyle = 'scorm_item_normal '.$classStyle.' ';
                }
                $subtree['title'] = $title;
                $subtree['class'] = $classStyle.' '.$cssStatus;
                $subtree['url'] = $this->get_link('http', $subtree['id'], $tree);
                $subtree['current_id'] = $myCurrentId;
                $listNotParent[] = $subtree;
            }
        }

        $list['are_parents'] = $listParent;
        $list['not_parents'] = $listNotParent;

        return $list;
    }

    /**
     * Uses the table generated by get_toc() and returns an HTML-formattedstring ready to display.
     *
     * @param array $tree
     * @param int   $id
     * @param bool  $parent
     *
     * @return array HTML TOC ready to display
     */
    public function getChildrenToc($tree, $id, $parent = true)
    {
        if (empty($tree)) {
            $tree = $this->get_toc();
        }

        $dirTypes = self::getChapterTypes();
        $currentItemId = $this->get_current_item_id();
        $list = [];

        foreach ($tree as $subtree) {
            $subtree['tree'] = null;

            if (!in_array($subtree['type'], $dirTypes) && $subtree['parent'] == $id) {
                if ($subtree['id'] == $this->current) {
                    $subtree['current'] = 'active';
                } else {
                    $subtree['current'] = null;
                }
                if (array_key_exists($subtree['status'], self::STATUS_CSS_CLASS_NAME)) {
                    $cssStatus = self::STATUS_CSS_CLASS_NAME[$subtree['status']];
                }

                $title = Security::remove_XSS($subtree['title']);
                unset($subtree['title']);
                if (empty($title)) {
                    $title = self::rl_get_resource_name(api_get_course_id(), $this->get_id(), $subtree['id']);
                }

                $classStyle = null;
                if ($subtree['id'] == $this->current) {
                    $classStyle = 'scorm_item_normal '.$classStyle.'scorm_highlight';
                } elseif (!in_array($subtree['type'], $dirTypes)) {
                    $classStyle = 'scorm_item_normal '.$classStyle.' ';
                }

                if (in_array($subtree['type'], $dirTypes)) {
                    $subtree['title'] = stripslashes($title);
                } else {
                    $subtree['title'] = $title;
                    $subtree['class'] = $classStyle.' '.$cssStatus;
                    $subtree['url'] = $this->get_link('http', $subtree['id'], $tree);
                    $subtree['current_id'] = $currentItemId;
                }
                $list[] = $subtree;
            }
        }

        return $list;
    }

    /**
     * Uses the table generated by get_toc() and returns an HTML-formatted string ready to display.
     *
     * @param array $toc_list
     *
     * @return array HTML TOC ready to display
     */
    public function getListArrayToc($toc_list = [])
    {
        if (empty($toc_list)) {
            $toc_list = $this->get_toc();
        }
        // Temporary variables.
        $currentItemId = $this->get_current_item_id();
        $list = [];
        $arrayList = [];

        foreach ($toc_list as $item) {
            $list['id'] = $item['id'];
            $list['status'] = $item['status'];
            $cssStatus = null;

            if (array_key_exists($item['status'], self::STATUS_CSS_CLASS_NAME)) {
                $cssStatus = self::STATUS_CSS_CLASS_NAME[$item['status']];
            }

            $classStyle = ' ';
            $dirTypes = self::getChapterTypes();

            if (in_array($item['type'], $dirTypes)) {
                $classStyle = 'scorm_item_section ';
            }
            if ($item['id'] == $this->current) {
                $classStyle = 'scorm_item_normal '.$classStyle.'scorm_highlight';
            } elseif (!in_array($item['type'], $dirTypes)) {
                $classStyle = 'scorm_item_normal '.$classStyle.' ';
            }
            $title = $item['title'];
            if (empty($title)) {
                $title = self::rl_get_resource_name(
                    api_get_course_id(),
                    $this->get_id(),
                    $item['id']
                );
            }
            $title = Security::remove_XSS($item['title']);

            if (empty($item['description'])) {
                $list['description'] = $title;
            } else {
                $list['description'] = $item['description'];
            }

            $list['class'] = $classStyle.' '.$cssStatus;
            $list['level'] = $item['level'];
            $list['type'] = $item['type'];

            if (in_array($item['type'], $dirTypes)) {
                $list['css_level'] = 'level_'.$item['level'];
            } else {
                $list['css_level'] = 'level_'.$item['level'].' scorm_type_'.self::format_scorm_type_item($item['type']);
            }

            if (in_array($item['type'], $dirTypes)) {
                $list['title'] = stripslashes($title);
            } else {
                $list['title'] = stripslashes($title);
                $list['url'] = $this->get_link('http', $item['id'], $toc_list);
                $list['current_id'] = $currentItemId;
            }
            $arrayList[] = $list;
        }

        return $arrayList;
    }

    /**
     * Returns an HTML-formatted string ready to display with teacher buttons
     * in LP view menu.
     *
     * @return string HTML TOC ready to display
     */
    public function get_teacher_toc_buttons()
    {
        $isAllow = api_is_allowed_to_edit(null, true, false, false);
        $hideIcons = api_get_configuration_value('hide_teacher_icons_lp');
        $html = '';
        if ($isAllow && $hideIcons == false) {
            if ($this->get_lp_session_id() == api_get_session_id()) {
                $html .= '<div id="actions_lp" class="actions_lp"><hr>';
                $html .= '<div class="btn-group">';
                $html .= "<a class='btn btn-sm btn-default' href='lp_controller.php?".api_get_cidreq()."&action=build&lp_id=".$this->lp_id."&isStudentView=false' target='_parent'>".
                    Display::returnFontAwesomeIcon('street-view').get_lang('Overview')."</a>";
                $html .= "<a class='btn btn-sm btn-default' href='lp_controller.php?".api_get_cidreq()."&action=add_item&type=step&lp_id=".$this->lp_id."&isStudentView=false' target='_parent'>".
                    Display::returnFontAwesomeIcon('pencil').get_lang('Edit')."</a>";
                $html .= '<a class="btn btn-sm btn-default" href="lp_controller.php?'.api_get_cidreq()."&action=edit&lp_id=".$this->lp_id.'&isStudentView=false">'.
                    Display::returnFontAwesomeIcon('cog').get_lang('Settings').'</a>';
                $html .= '</div>';
                $html .= '</div>';
            }
        }

        return $html;
    }

    /**
     * Returns an HTML-formatted string ready to display flow buttons
     * in LP view menu.
     *
     * @return string HTML TOC ready to display
     */
    public function getFlowLpbuttons()
    {
        $allowFlowButtons = api_get_configuration_value('lp_enable_flow');
        $html = '';
        if ($allowFlowButtons) {
            $nextLpId = self::getFlowNextLpId($this->lp_id, api_get_course_int_id());
            $prevLpId = self::getFlowPrevLpId($this->lp_id, api_get_course_int_id());
            if (!empty($nextLpId) || !empty($prevLpId)) {
                $html .= '<div id="actions_lp" class="actions_lp"><hr>';
                $html .= '<div class="btn-group">';
                if ($prevLpId > 0) {
                    $html .= "<a class='btn btn-sm btn-default' href='lp_controller.php?".api_get_cidreq()."&action=view&lp_id=".$prevLpId."' target='_parent'>".
                        Display::returnFontAwesomeIcon('arrow-left').'&nbsp;'.get_lang('Previous')."</a>";
                }
                if ($nextLpId > 0) {
                    $html .= "<a class='btn btn-sm btn-default' href='lp_controller.php?".api_get_cidreq()."&action=view&lp_id=".$nextLpId."' target='_parent'>".
                        get_lang('Next').'&nbsp;'.Display::returnFontAwesomeIcon('arrow-right')."</a>";
                }
                $html .= '</div>';
                $html .= '</div>';
            }
        }

        return $html;
    }

    /**
     * Gets the learnpath maker name - generally the editor's name.
     *
     * @return string Learnpath maker name
     */
    public function get_maker()
    {
        if (!empty($this->maker)) {
            return $this->maker;
        }

        return '';
    }

    /**
     * Gets the learnpath name/title.
     *
     * @return string Learnpath name/title
     */
    public function get_name()
    {
        if (!empty($this->name)) {
            return $this->name;
        }

        return 'N/A';
    }

    /**
     * @return string
     */
    public function getNameNoTags()
    {
        return Security::remove_XSS(strip_tags($this->get_name()));
    }

    /**
     * Gets a link to the resource from the present location, depending on item ID.
     *
     * @param string $type         Type of link expected
     * @param int    $item_id      Learnpath item ID
     * @param bool   $provided_toc
     *
     * @return string $provided_toc Link to the lp_item resource
     */
    public function get_link($type = 'http', $item_id = 0, $provided_toc = false)
    {
        $course_id = $this->get_course_int_id();
        $item_id = (int) $item_id;

        if (empty($item_id)) {
            $item_id = $this->get_current_item_id();

            if (empty($item_id)) {
                //still empty, this means there was no item_id given and we are not in an object context or
                //the object property is empty, return empty link
                $this->first();

                return '';
            }
        }

        $file = '';
        $lp_table = Database::get_course_table(TABLE_LP_MAIN);
        $lp_item_table = Database::get_course_table(TABLE_LP_ITEM);
        $lp_item_view_table = Database::get_course_table(TABLE_LP_ITEM_VIEW);

        $sql = "SELECT
                    l.lp_type as ltype,
                    l.path as lpath,
                    li.item_type as litype,
                    li.path as lipath,
                    li.parameters as liparams
        		FROM $lp_table l
                INNER JOIN $lp_item_table li
                ON (li.lp_id = l.iid)
        		WHERE
        		    li.iid = $item_id
        		";
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res);
            $lp_type = $row['ltype'];
            $lp_path = $row['lpath'];
            $lp_item_type = $row['litype'];
            $lp_item_path = $row['lipath'];
            $lp_item_params = $row['liparams'];

            if (empty($lp_item_params) && strpos($lp_item_path, '?') !== false) {
                [$lp_item_path, $lp_item_params] = explode('?', $lp_item_path);
            }
            $sys_course_path = api_get_path(SYS_COURSE_PATH).api_get_course_path();
            if ($type === 'http') {
                //web path
                $course_path = api_get_path(WEB_COURSE_PATH).api_get_course_path();
            } else {
                $course_path = $sys_course_path; //system path
            }

            // Fixed issue BT#1272 - If the item type is a Chamilo Item (quiz, link, etc),
            // then change the lp type to thread it as a normal Chamilo LP not a SCO.
            if (in_array(
                $lp_item_type,
                ['quiz', 'document', 'final_item', 'link', 'forum', 'thread', 'student_publication', 'xapi', 'h5p', 'survey']
            )
            ) {
                $lp_type = 1;
            }

            // Now go through the specific cases to get the end of the path
            // @todo Use constants instead of int values.
            switch ($lp_type) {
                case 1:
                    $file = self::rl_get_resource_link_for_learnpath(
                        $course_id,
                        $this->get_id(),
                        $item_id,
                        $this->get_view_id(),
                        $this->get_lp_session_id()
                    );
                    switch ($lp_item_type) {
                        case 'document':
                            // Shows a button to download the file instead of just downloading the file directly.
                            $documentPathInfo = pathinfo($file);
                            if (isset($documentPathInfo['extension'])) {
                                $parsed = parse_url($documentPathInfo['extension']);
                                if (isset($parsed['path'])) {
                                    $extension = $parsed['path'];
                                    $extensionsToDownload = [
                                        'zip',
                                        'ppt',
                                        'pptx',
                                        'ods',
                                        'xlsx',
                                        'xls',
                                        'csv',
                                        'doc',
                                        'docx',
                                        'dot',
                                    ];

                                    if (in_array($extension, $extensionsToDownload)) {
                                        $file = api_get_path(WEB_CODE_PATH).
                                            'lp/embed.php?type=download&source=file&lp_item_id='.$item_id.'&'.api_get_cidreq();
                                    }
                                }
                            }
                            break;
                        case 'dir':
                            $file = 'lp_content.php?type=dir&'.api_get_cidreq();
                            break;
                        case 'link':
                            if (!empty($file)) {
                                if (Link::is_youtube_link($file)) {
                                    $src = Link::get_youtube_video_id($file);
                                    $file = api_get_path(WEB_CODE_PATH).'lp/embed.php?type=youtube&source='.$src;
                                } elseif (Link::isVimeoLink($file)) {
                                    $src = Link::getVimeoLinkId($file);
                                    $file = api_get_path(WEB_CODE_PATH).'lp/embed.php?type=vimeo&source='.$src;
                                } else {
                                    // If the current site is HTTPS and the link is
                                    // HTTP, browsers will refuse opening the link
                                    $urlId = api_get_current_access_url_id();
                                    $url = api_get_access_url($urlId, false);
                                    $protocol = substr($url['url'], 0, 5);
                                    if ($protocol === 'https') {
                                        $linkProtocol = substr($file, 0, 5);
                                        if ($linkProtocol === 'http:') {
                                            //this is the special intervention case
                                            $file = api_get_path(
                                                    WEB_CODE_PATH
                                                ).'lp/embed.php?type=nonhttps&source='.urlencode($file);
                                        }
                                    }
                                }
                            }
                            break;
                        case 'quiz':
                            // Check how much attempts of a exercise exits in lp
                            $lp_item_id = $this->get_current_item_id();
                            $lp_view_id = $this->get_view_id();

                            $prevent_reinit = null;
                            if (isset($this->items[$this->current])) {
                                $prevent_reinit = $this->items[$this->current]->get_prevent_reinit();
                            }

                            if (empty($provided_toc)) {
                                if ($this->debug > 0) {
                                    error_log('In learnpath::get_link() Loading get_toc ', 0);
                                }
                                $list = $this->get_toc();
                            } else {
                                if ($this->debug > 0) {
                                    error_log('In learnpath::get_link() Loading get_toc from "cache" ', 0);
                                }
                                $list = $provided_toc;
                            }

                            $type_quiz = false;
                            foreach ($list as $toc) {
                                if ($toc['id'] == $lp_item_id && $toc['type'] === 'quiz') {
                                    $type_quiz = true;
                                }
                            }

                            if ($type_quiz) {
                                $lp_item_id = (int) $lp_item_id;
                                $lp_view_id = (int) $lp_view_id;
                                $sql = "SELECT count(*) FROM $lp_item_view_table
                                        WHERE
                                            c_id = $course_id AND
                                            lp_item_id='".$lp_item_id."' AND
                                            lp_view_id ='".$lp_view_id."' AND
                                            status='completed'";
                                $result = Database::query($sql);
                                $row_count = Database::fetch_row($result);
                                $count_item_view = (int) $row_count[0];
                                $not_multiple_attempt = 0;
                                if ($prevent_reinit === 1 && $count_item_view > 0) {
                                    $not_multiple_attempt = 1;
                                }
                                $file .= '&not_multiple_attempt='.$not_multiple_attempt;
                            }
                            break;
                    }

                    $tmp_array = explode('/', $file);
                    $document_name = $tmp_array[count($tmp_array) - 1];
                    if (strpos($document_name, '_DELETED_')) {
                        $file = 'blank.php?error=document_deleted';
                    }
                    break;
                case 2:
                    if ($this->debug > 2) {
                        error_log('In learnpath::get_link() '.__LINE__.' - Item type: '.$lp_item_type, 0);
                    }

                    if ($lp_item_type != 'dir') {
                        // Quite complex here:
                        // We want to make sure 'http://' (and similar) links can
                        // be loaded as is (withouth the Chamilo path in front) but
                        // some contents use this form: resource.htm?resource=http://blablabla
                        // which means we have to find a protocol at the path's start, otherwise
                        // it should not be considered as an external URL.
                        // if ($this->prerequisites_match($item_id)) {
                        if (preg_match('#^[a-zA-Z]{2,5}://#', $lp_item_path) != 0) {
                            if ($this->debug > 2) {
                                error_log('In learnpath::get_link() '.__LINE__.' - Found match for protocol in '.$lp_item_path, 0);
                            }
                            // Distant url, return as is.
                            $file = $lp_item_path;
                        } else {
                            if ($this->debug > 2) {
                                error_log('In learnpath::get_link() '.__LINE__.' - No starting protocol in '.$lp_item_path, 0);
                            }
                            // Prevent getting untranslatable urls.
                            $lp_item_path = preg_replace('/%2F/', '/', $lp_item_path);
                            $lp_item_path = preg_replace('/%3A/', ':', $lp_item_path);
                            // Prepare the path.
                            $file = $course_path.'/scorm/'.$lp_path.'/'.$lp_item_path;
                            // TODO: Fix this for urls with protocol header.
                            $file = str_replace('//', '/', $file);
                            $file = str_replace(':/', '://', $file);
                            if (substr($lp_path, -1) == '/') {
                                $lp_path = substr($lp_path, 0, -1);
                            }

                            if (!is_file(realpath($sys_course_path.'/scorm/'.$lp_path.'/'.$lp_item_path))) {
                                // if file not found.
                                $decoded = html_entity_decode($lp_item_path);
                                [$decoded] = explode('?', $decoded);
                                if (!is_file(realpath($sys_course_path.'/scorm/'.$lp_path.'/'.$decoded))) {
                                    $file = self::rl_get_resource_link_for_learnpath(
                                        $course_id,
                                        $this->get_id(),
                                        $item_id,
                                        $this->get_view_id()
                                    );
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
                                    $file = $course_path.'/scorm/'.$lp_path.'/'.$decoded;
                                }
                            }
                        }

                        // We want to use parameters if they were defined in the imsmanifest
                        if (strpos($file, 'blank.php') === false) {
                            $lp_item_params = ltrim($lp_item_params, '?');
                            $file .= (strstr($file, '?') === false ? '?' : '').$lp_item_params;
                        }
                    } else {
                        $file = 'lp_content.php?type=dir&'.api_get_cidreq();
                    }
                    break;
                case 3:
                    if ($this->debug > 2) {
                        error_log('In learnpath::get_link() '.__LINE__.' - Item type: '.$lp_item_type, 0);
                    }
                    // Formatting AICC HACP append URL.
                    $aicc_append = '?aicc_sid='.urlencode(session_id()).'&aicc_url='.urlencode(api_get_path(WEB_CODE_PATH).'lp/aicc_hacp.php').'&';
                    if (!empty($lp_item_params)) {
                        $aicc_append .= $lp_item_params.'&';
                    }
                    if ($lp_item_type != 'dir') {
                        // Quite complex here:
                        // We want to make sure 'http://' (and similar) links can
                        // be loaded as is (withouth the Chamilo path in front) but
                        // some contents use this form: resource.htm?resource=http://blablabla
                        // which means we have to find a protocol at the path's start, otherwise
                        // it should not be considered as an external URL.
                        if (preg_match('#^[a-zA-Z]{2,5}://#', $lp_item_path) != 0) {
                            if ($this->debug > 2) {
                                error_log('In learnpath::get_link() '.__LINE__.' - Found match for protocol in '.$lp_item_path, 0);
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
                                $file = str_replace('<servername>', $web_course_path.'/scorm/'.$lp_path, $lp_item_path);
                            }

                            $file .= $aicc_append;
                        } else {
                            if ($this->debug > 2) {
                                error_log('In learnpath::get_link() '.__LINE__.' - No starting protocol in '.$lp_item_path, 0);
                            }
                            // Prevent getting untranslatable urls.
                            $lp_item_path = preg_replace('/%2F/', '/', $lp_item_path);
                            $lp_item_path = preg_replace('/%3A/', ':', $lp_item_path);
                            // Prepare the path - lp_path might be unusable because it includes the "aicc" subdir name.
                            $file = $course_path.'/scorm/'.$lp_path.'/'.$lp_item_path;
                            // TODO: Fix this for urls with protocol header.
                            $file = str_replace('//', '/', $file);
                            $file = str_replace(':/', '://', $file);
                            $file .= $aicc_append;
                        }
                    } else {
                        $file = 'lp_content.php?type=dir&'.api_get_cidreq();
                    }
                    break;
                case 4:
                    break;
                default:
                    break;
            }
            // Replace &amp; by & because &amp; will break URL with params
            $file = !empty($file) ? str_replace('&amp;', '&', $file) : '';
        }
        if ($this->debug > 2) {
            error_log('In learnpath::get_link() - returning "'.$file.'" from get_link', 0);
        }

        return $file;
    }

    /**
     * Gets the latest usable view or generate a new one.
     *
     * @param int $attempt_num Optional attempt number. If none given, takes the highest from the lp_view table
     * @param int $userId      The user ID, as $this->get_user_id() is not always available
     *
     * @return int DB lp_view id
     */
    public function get_view($attempt_num = 0, $userId = null)
    {
        $search = '';
        // Use $attempt_num to enable multi-views management (disabled so far).
        if ($attempt_num != 0 && intval(strval($attempt_num)) == $attempt_num) {
            $search = 'AND view_count = '.$attempt_num;
        }
        // When missing $attempt_num, search for a unique lp_view record for this lp and user.
        $lp_view_table = Database::get_course_table(TABLE_LP_VIEW);

        $course_id = api_get_course_int_id();
        $sessionId = api_get_session_id();

        // Check user ID.
        if (empty($userId)) {
            if (empty($this->get_user_id())) {
                $this->error = 'User ID is empty in learnpath::get_view()';

                return null;
            } else {
                $userId = $this->get_user_id();
            }
        }

        $sql = "SELECT iid, view_count FROM $lp_view_table
        		WHERE
        		    c_id = $course_id AND
        		    lp_id = ".$this->get_id()." AND
        		    user_id = ".$userId." AND
        		    session_id = $sessionId
        		    $search
                ORDER BY view_count DESC";
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res);
            $this->lp_view_id = $row['iid'];
        } elseif (!api_is_invitee()) {
            // There is no database record, create one.
            $sql = "INSERT INTO $lp_view_table (c_id, lp_id, user_id, view_count, session_id) VALUES
            		($course_id, ".$this->get_id().",".$this->get_user_id().", 1, $sessionId)";
            Database::query($sql);
            $id = Database::insert_id();
            $this->lp_view_id = $id;

            $sql = "UPDATE $lp_view_table SET id = iid WHERE iid = $id";
            Database::query($sql);
        }

        return $this->lp_view_id;
    }

    /**
     * Gets the current view id.
     *
     * @return int View ID (from lp_view)
     */
    public function get_view_id()
    {
        if (!empty($this->lp_view_id)) {
            return (int) $this->lp_view_id;
        }

        return 0;
    }

    /**
     * Gets the update queue.
     *
     * @return array Array containing IDs of items to be updated by JavaScript
     */
    public function get_update_queue()
    {
        return $this->update_queue;
    }

    /**
     * Gets the user ID.
     *
     * @return int User ID
     */
    public function get_user_id()
    {
        if (!empty($this->user_id)) {
            return (int) $this->user_id;
        }

        return false;
    }

    /**
     * Checks if any of the items has an audio element attached.
     *
     * @return bool True or false
     */
    public function has_audio()
    {
        $has = false;
        foreach ($this->items as $i => $item) {
            if (!empty($this->items[$i]->audio)) {
                $has = true;
                break;
            }
        }

        return $has;
    }

    /**
     * Moves an item up and down at its level.
     *
     * @param int    $id        Item to move up and down
     * @param string $direction Direction 'up' or 'down'
     *
     * @return bool|int
     */
    public function move_item($id, $direction)
    {
        $course_id = api_get_course_int_id();
        if (empty($id) || empty($direction)) {
            return false;
        }
        $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $sql_sel = "SELECT *
                    FROM $tbl_lp_item
                    WHERE
                        iid = $id
                    ";
        $res_sel = Database::query($sql_sel);
        // Check if elem exists.
        if (Database::num_rows($res_sel) < 1) {
            return false;
        }
        // Gather data.
        $row = Database::fetch_array($res_sel);
        $previous = $row['previous_item_id'];
        $next = $row['next_item_id'];
        $display = $row['display_order'];
        $parent = $row['parent_item_id'];
        $lp = $row['lp_id'];
        // Update the item (switch with previous/next one).
        switch ($direction) {
            case 'up':
                if ($display > 1) {
                    $sql_sel2 = "SELECT * FROM $tbl_lp_item
                                 WHERE iid = $previous";
                    $res_sel2 = Database::query($sql_sel2);
                    if (Database::num_rows($res_sel2) < 1) {
                        $previous_previous = 0;
                    }
                    // Gather data.
                    $row2 = Database::fetch_array($res_sel2);
                    $previous_previous = $row2['previous_item_id'];
                    // Update previous_previous item (switch "next" with current).
                    if ($previous_previous != 0) {
                        $sql_upd2 = "UPDATE $tbl_lp_item SET
                                        next_item_id = $id
                                    WHERE iid = $previous_previous";
                        Database::query($sql_upd2);
                    }
                    // Update previous item (switch with current).
                    if ($previous != 0) {
                        $sql_upd2 = "UPDATE $tbl_lp_item SET
                                    next_item_id = $next,
                                    previous_item_id = $id,
                                    display_order = display_order +1
                                    WHERE iid = $previous";
                        Database::query($sql_upd2);
                    }

                    // Update current item (switch with previous).
                    if ($id != 0) {
                        $sql_upd2 = "UPDATE $tbl_lp_item SET
                                        next_item_id = $previous,
                                        previous_item_id = $previous_previous,
                                        display_order = display_order-1
                                    WHERE c_id = ".$course_id." AND id = $id";
                        Database::query($sql_upd2);
                    }
                    // Update next item (new previous item).
                    if (!empty($next)) {
                        $sql_upd2 = "UPDATE $tbl_lp_item SET previous_item_id = $previous
                                     WHERE iid = $next";
                        Database::query($sql_upd2);
                    }
                    $display = $display - 1;
                }
                break;
            case 'down':
                if ($next != 0) {
                    $sql_sel2 = "SELECT * FROM $tbl_lp_item
                                 WHERE iid = $next";
                    $res_sel2 = Database::query($sql_sel2);
                    if (Database::num_rows($res_sel2) < 1) {
                        $next_next = 0;
                    }
                    // Gather data.
                    $row2 = Database::fetch_array($res_sel2);
                    $next_next = $row2['next_item_id'];
                    // Update previous item (switch with current).
                    if ($previous != 0) {
                        $sql_upd2 = "UPDATE $tbl_lp_item
                                     SET next_item_id = $next
                                     WHERE iid = $previous";
                        Database::query($sql_upd2);
                    }
                    // Update current item (switch with previous).
                    if ($id != 0) {
                        $sql_upd2 = "UPDATE $tbl_lp_item SET
                                     previous_item_id = $next,
                                     next_item_id = $next_next,
                                     display_order = display_order + 1
                                     WHERE iid = $id";
                        Database::query($sql_upd2);
                    }

                    // Update next item (new previous item).
                    if ($next != 0) {
                        $sql_upd2 = "UPDATE $tbl_lp_item SET
                                     previous_item_id = $previous,
                                     next_item_id = $id,
                                     display_order = display_order-1
                                     WHERE iid = $next";
                        Database::query($sql_upd2);
                    }

                    // Update next_next item (switch "previous" with current).
                    if ($next_next != 0) {
                        $sql_upd2 = "UPDATE $tbl_lp_item SET
                                     previous_item_id = $id
                                     WHERE iid = $next_next";
                        Database::query($sql_upd2);
                    }
                    $display = $display + 1;
                }
                break;
            default:
                return false;
        }

        return $display;
    }

    /**
     * Move a LP up (display_order).
     *
     * @param int $lp_id      Learnpath ID
     * @param int $categoryId Category ID
     *
     * @return bool
     */
    public static function move_up($lp_id, $categoryId = 0)
    {
        $courseId = api_get_course_int_id();
        $lp_table = Database::get_course_table(TABLE_LP_MAIN);

        $categoryCondition = '';
        if (!empty($categoryId)) {
            $categoryId = (int) $categoryId;
            $categoryCondition = " AND category_id = $categoryId";
        }
        $sql = "SELECT * FROM $lp_table
                WHERE c_id = $courseId
                $categoryCondition
                ORDER BY display_order";
        $res = Database::query($sql);
        if ($res === false) {
            return false;
        }

        $lps = [];
        $lp_order = [];
        $num = Database::num_rows($res);
        // First check the order is correct, globally (might be wrong because
        // of versions < 1.8.4)
        if ($num > 0) {
            $i = 1;
            while ($row = Database::fetch_array($res)) {
                if ($row['display_order'] != $i) { // If we find a gap in the order, we need to fix it.
                    $sql = "UPDATE $lp_table SET display_order = $i
                            WHERE iid = ".$row['iid'];
                    Database::query($sql);
                }
                $row['display_order'] = $i;
                $lps[$row['iid']] = $row;
                $lp_order[$i] = $row['iid'];
                $i++;
            }
        }
        if ($num > 1) { // If there's only one element, no need to sort.
            $order = $lps[$lp_id]['display_order'];
            if ($order > 1) { // If it's the first element, no need to move up.
                $sql = "UPDATE $lp_table SET display_order = $order
                        WHERE iid = ".$lp_order[$order - 1];
                Database::query($sql);
                $sql = "UPDATE $lp_table SET display_order = ".($order - 1)."
                        WHERE iid = $lp_id";
                Database::query($sql);
            }
        }

        return true;
    }

    /**
     * Move a learnpath down (display_order).
     *
     * @param int $lp_id      Learnpath ID
     * @param int $categoryId Category ID
     *
     * @return bool
     */
    public static function move_down($lp_id, $categoryId = 0)
    {
        $courseId = api_get_course_int_id();
        $lp_table = Database::get_course_table(TABLE_LP_MAIN);

        $categoryCondition = '';
        if (!empty($categoryId)) {
            $categoryId = (int) $categoryId;
            $categoryCondition = " AND category_id = $categoryId";
        }

        $sql = "SELECT * FROM $lp_table
                WHERE c_id = $courseId
                $categoryCondition
                ORDER BY display_order";
        $res = Database::query($sql);
        if ($res === false) {
            return false;
        }
        $lps = [];
        $lp_order = [];
        $num = Database::num_rows($res);
        $max = 0;
        // First check the order is correct, globally (might be wrong because
        // of versions < 1.8.4).
        if ($num > 0) {
            $i = 1;
            while ($row = Database::fetch_array($res)) {
                $max = $i;
                if ($row['display_order'] != $i) {
                    // If we find a gap in the order, we need to fix it.
                    $sql = "UPDATE $lp_table SET display_order = $i
                              WHERE iid = ".$row['iid'];
                    Database::query($sql);
                }
                $row['display_order'] = $i;
                $lps[$row['iid']] = $row;
                $lp_order[$i] = $row['iid'];
                $i++;
            }
        }
        if ($num > 1) { // If there's only one element, no need to sort.
            $order = $lps[$lp_id]['display_order'];
            if ($order < $max) { // If it's the first element, no need to move up.
                $sql = "UPDATE $lp_table SET display_order = $order
                        WHERE iid = ".$lp_order[$order + 1];
                Database::query($sql);
                $sql = "UPDATE $lp_table SET display_order = ".($order + 1)."
                        WHERE iid = $lp_id";
                Database::query($sql);
            }
        }

        return true;
    }

    /**
     * Updates learnpath attributes to point to the next element
     * The last part is similar to set_current_item but processing the other way around.
     */
    public function next()
    {
        if ($this->debug > 0) {
            error_log('In learnpath::next()', 0);
        }
        $this->last = $this->get_current_item_id();
        $this->items[$this->last]->save(
            false,
            $this->prerequisites_match($this->last)
        );
        $this->autocomplete_parents($this->last);
        $new_index = $this->get_next_index();
        if ($this->debug > 2) {
            error_log('New index: '.$new_index, 0);
        }
        $this->index = $new_index;
        if ($this->debug > 2) {
            error_log('Now having orderedlist['.$new_index.'] = '.$this->ordered_items[$new_index], 0);
        }
        $this->current = $this->ordered_items[$new_index];
        if ($this->debug > 2) {
            error_log('new item id is '.$this->current.'-'.$this->get_current_item_id(), 0);
        }
    }

    /**
     * Open a resource = initialise all local variables relative to this resource. Depending on the child
     * class, this might be redefined to allow several behaviours depending on the document type.
     *
     * @param int $id Resource ID
     */
    public function open($id)
    {
        // TODO:
        // set the current resource attribute to this resource
        // switch on element type (redefine in child class?)
        // set status for this item to "opened"
        // start timer
        // initialise score
        $this->index = 0; //or = the last item seen (see $this->last)
    }

    /**
     * Check that all prerequisites are fulfilled. Returns true and an
     * empty string on success, returns false
     * and the prerequisite string on error.
     * This function is based on the rules for aicc_script language as
     * described in the SCORM 1.2 CAM documentation page 108.
     *
     * @param int $itemId Optional item ID. If none given, uses the current open item.
     *
     * @return bool true if prerequisites are matched, false otherwise - Empty string if true returned, prerequisites
     *              string otherwise
     */
    public function prerequisites_match($itemId = null)
    {
        $allow = api_get_configuration_value('allow_teachers_to_access_blocked_lp_by_prerequisite');
        if ($allow) {
            if (api_is_allowed_to_edit() ||
                api_is_platform_admin(true) ||
                api_is_drh() ||
                api_is_coach(api_get_session_id(), api_get_course_int_id())
            ) {
                return true;
            }
        }

        $debug = $this->debug;
        if ($debug > 0) {
            error_log('In learnpath::prerequisites_match()');
        }

        if (empty($itemId)) {
            $itemId = $this->current;
        }

        $currentItem = $this->getItem($itemId);
        if ($debug > 0) {
            error_log("Checking item id $itemId");
        }

        if ($currentItem) {
            if ($this->type == 2) {
                // Getting prereq from scorm
                $prereq_string = $this->get_scorm_prereq_string($itemId);
            } else {
                $prereq_string = $currentItem->get_prereq_string();
            }

            if (empty($prereq_string)) {
                if ($debug > 0) {
                    error_log('Found prereq_string is empty return true');
                }
                // checks the dates values as prerequisites
                $result = $this->prerequistesDatesMatch($itemId);

                return $result;
            }

            // Clean spaces.
            $prereq_string = str_replace(' ', '', $prereq_string);
            if ($debug > 0) {
                error_log('Found prereq_string: '.$prereq_string, 0);
            }

            // Now send to the parse_prereq() function that will check this component's prerequisites.
            $result = $currentItem->parse_prereq(
                $prereq_string,
                $this->items,
                $this->refs_list,
                $this->get_user_id()
            );

            if ($result === false) {
                $this->set_error_msg($currentItem->prereq_alert);
            }
        } else {
            $result = true;
            if ($debug > 1) {
                error_log('$this->items['.$itemId.'] was not an object');
            }
        }

        if ($debug > 1) {
            error_log('Result: '.$result);
            error_log('End of prerequisites_match(). Error message is now '.$this->error);
        }

        if (true === $result && $itemId) {
            // checks the dates values as prerequisites
            $result = $this->prerequistesDatesMatch($itemId);
        }

        return $result;
    }

    public function prerequistesDatesMatch(int $itemId)
    {
        if (true === api_get_configuration_value('lp_item_prerequisite_dates')) {
            $extraFieldValue = new ExtraFieldValue('lp_item');
            $startDate = $extraFieldValue->get_values_by_handler_and_field_variable(
                $itemId,
                'start_date'
            );
            $endDate = $extraFieldValue->get_values_by_handler_and_field_variable(
                $itemId,
                'end_date'
            );

            $now = time();
            $start = !empty($startDate['value']) ? api_strtotime($startDate['value']) : 0;
            $end = !empty($endDate['value']) ? api_strtotime($endDate['value']) : 0;
            $result = false;

            if (($start == 0 && $end == 0) ||
                (($start > 0 && $end == 0) && $now > $start) ||
                (($start == 0 && $end > 0) && $now < $end) ||
                (($start > 0 && $end > 0) && ($now > $start && $now < $end))
            ) {
                $result = true;
            }

            if (!$result) {
                $errMsg = get_lang('ItemCanNotBeAccessedPrerequisiteDates');
                if ($start > 0 && $start > $now) {
                    $errMsg = get_lang('AccessibleFrom').' '.api_format_date($start, DATE_TIME_FORMAT_LONG);
                }
                if ($end > 0 && $end < $now) {
                    $errMsg = get_lang('NoMoreAccessible');
                }
                $this->set_error_msg($errMsg);
                $currentItem = $this->getItem($itemId);
                $currentItem->prereq_alert = $errMsg;
            }

            return $result;
        }

        return true;
    }

    /**
     * Updates learnpath attributes to point to the previous element
     * The last part is similar to set_current_item but processing the other way around.
     */
    public function previous()
    {
        $this->last = $this->get_current_item_id();
        $this->items[$this->last]->save(
            false,
            $this->prerequisites_match($this->last)
        );
        $this->autocomplete_parents($this->last);
        $new_index = $this->get_previous_index();
        $this->index = $new_index;
        $this->current = $this->ordered_items[$new_index];
    }

    /**
     * Publishes a learnpath. This basically means show or hide the learnpath
     * to normal users.
     * Can be used as abstract.
     *
     * @param int $lp_id          Learnpath ID
     * @param int $set_visibility New visibility
     *
     * @return bool
     */
    public static function toggle_visibility($lp_id, $set_visibility = 1)
    {
        if (empty($lp_id)) {
            return false;
        }

        $action = 'visible';
        if ($set_visibility != 1) {
            $action = 'invisible';
            self::toggle_publish($lp_id, 'i');
        }

        return api_item_property_update(
            api_get_course_info(),
            TOOL_LEARNPATH,
            $lp_id,
            $action,
            api_get_user_id()
        );
    }

    /**
     * Publishes a learnpath category.
     * This basically means show or hide the learnpath category to normal users.
     *
     * @param int $id
     * @param int $visibility
     *
     * @return bool
     */
    public static function toggleCategoryVisibility($id, $visibility = 1)
    {
        $action = 'visible';
        if ($visibility != 1) {
            self::toggleCategoryPublish($id, 0);
            $action = 'invisible';
        }

        return api_item_property_update(
            api_get_course_info(),
            TOOL_LEARNPATH_CATEGORY,
            $id,
            $action,
            api_get_user_id()
        );
    }

    /**
     * Publishes a learnpath. This basically means show or hide the learnpath
     * on the course homepage
     * Can be used as abstract.
     *
     * @param int    $lp_id          Learnpath id
     * @param string $set_visibility New visibility (v/i - visible/invisible)
     *
     * @return bool
     */
    public static function toggle_publish($lp_id, $set_visibility = 'v')
    {
        if (empty($lp_id)) {
            return false;
        }
        $course_id = api_get_course_int_id();
        $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
        $lp_id = (int) $lp_id;
        $sql = "SELECT * FROM $tbl_lp
                WHERE iid = $lp_id";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result);
            $name = Database::escape_string($row['name']);
            if ($set_visibility === 'i') {
                $v = 0;
            }
            if ($set_visibility === 'v') {
                $v = 1;
            }

            $session_id = api_get_session_id();
            $session_condition = api_get_session_condition($session_id);

            $tbl_tool = Database::get_course_table(TABLE_TOOL_LIST);
            $link = 'lp/lp_controller.php?action=view&lp_id='.$lp_id.'&id_session='.$session_id;
            $oldLink = 'newscorm/lp_controller.php?action=view&lp_id='.$lp_id.'&id_session='.$session_id;

            $extraLpCondition = '';
            $extraLink = '';
            if (!empty($session_id)) {
                $extraLink = 'lp/lp_controller.php?action=view&lp_id='.$lp_id.'&id_session=0';
                $extraLpCondition = " OR (link = '$link' AND session_id = $session_id )  ";
            }

            $sql = "SELECT * FROM $tbl_tool
                    WHERE
                        c_id = $course_id AND
                        (link = '$link' OR link = '$oldLink' $extraLpCondition ) AND
                        image = 'scormbuilder.gif' AND
                        (
                            link LIKE '$link%' OR
                            link LIKE '$oldLink%'
                            $extraLpCondition
                        )
                        $session_condition
                    ";

            $result = Database::query($sql);
            $num = Database::num_rows($result);
            $resultTool = Database::fetch_array($result, 'ASSOC');

            if ($set_visibility === 'i') {
                if ($num > 0) {
                    $sql = "DELETE FROM $tbl_tool
                            WHERE
                                c_id = $course_id AND
                                (link = '$link' OR link = '$oldLink') AND
                                image='scormbuilder.gif'
                                $session_condition";
                    Database::query($sql);
                }

                // Disables the base course link inside a session.
                if (!empty($session_id) && 0 === (int) $row['session_id']) {
                    $sql = "SELECT iid FROM $tbl_tool
                            WHERE
                                c_id = $course_id AND
                                (link = '$extraLink') AND
                                image = 'scormbuilder.gif' AND
                                session_id = $session_id
                    ";
                    $resultBaseLp = Database::query($sql);
                    if (Database::num_rows($resultBaseLp)) {
                        $resultBaseLpRow = Database::fetch_array($resultBaseLp);
                        $id = $resultBaseLpRow['iid'];
                        /*$sql = "UPDATE $tbl_tool
                                SET visibility = 0
                                WHERE iid = $id ";
                        Database::query($sql);*/
                        $sql = "DELETE FROM $tbl_tool
                                WHERE iid = $id";
                        Database::query($sql);
                    } else {
                        /*$params = [
                            'category' => 'authoring',
                            'c_id' => $course_id,
                            'name' => $name,
                            'link' => $link,
                            'image' => 'scormbuilder.gif',
                            'visibility' => '0',
                            'admin' => '0',
                            'address' => 'pastillegris.gif',
                            'added_tool' => '0',
                            'session_id' => $session_id,
                        ];
                        $insertId = Database::insert($tbl_tool, $params);
                        if ($insertId) {
                            $sql = "UPDATE $tbl_tool SET id = iid WHERE iid = $insertId";
                            Database::query($sql);
                        }*/
                    }
                }
            }

            if ($set_visibility === 'v') {
                if ($num == 0) {
                    $sql = "INSERT INTO $tbl_tool (category, c_id, name, link, image, visibility, admin, address, added_tool, session_id)
                            VALUES ('authoring', $course_id, '$name', '$link', 'scormbuilder.gif', '$v', '0','pastillegris.gif', 0, $session_id)";
                    Database::query($sql);
                    $insertId = Database::insert_id();
                    if ($insertId) {
                        $sql = "UPDATE $tbl_tool SET id = iid WHERE iid = $insertId";
                        Database::query($sql);
                    }
                }
                if ($num > 0) {
                    $id = $resultTool['iid'];
                    $sql = "UPDATE $tbl_tool SET
                        c_id = $course_id,
                        name = '$name',
                        link = '$link',
                        image = 'scormbuilder.gif',
                        visibility = '$v',
                        admin = '0',
                        address = 'pastillegris.gif',
                        added_tool = 0,
                        session_id = $session_id
                    WHERE
                        c_id = ".$course_id." AND
                        iid = $id
                    ";
                    Database::query($sql);
                }
            }
        }

        return false;
    }

    /**
     * Publishes a learnpath.
     * Show or hide the learnpath category on the course homepage.
     *
     * @param int $id
     * @param int $setVisibility
     *
     * @return bool
     */
    public static function toggleCategoryPublish($id, $setVisibility = 1)
    {
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();
        $sessionCondition = api_get_session_condition(
            $sessionId,
            true,
            false,
            't.sessionId'
        );

        $em = Database::getManager();
        $category = self::getCategory($id);

        if (!$category) {
            return false;
        }

        if (empty($courseId)) {
            return false;
        }

        $link = self::getCategoryLinkForTool($id);

        /** @var CTool $tool */
        $tool = $em->createQuery("
                SELECT t FROM ChamiloCourseBundle:CTool t
                WHERE
                    t.cId = :course AND
                    t.link = :link1 AND
                    t.image = 'lp_category.gif' AND
                    t.link LIKE :link2
                    $sessionCondition
            ")
            ->setParameters([
                'course' => $courseId,
                'link1' => $link,
                'link2' => "$link%",
            ])
            ->getOneOrNullResult();

        if ($setVisibility == 0 && $tool) {
            $em->remove($tool);
            $em->flush();

            return true;
        }

        if ($setVisibility == 1 && !$tool) {
            $tool = new CTool();
            $tool
                ->setCategory('authoring')
                ->setCId($courseId)
                ->setName(strip_tags($category->getName()))
                ->setLink($link)
                ->setImage('lp_category.gif')
                ->setVisibility(1)
                ->setAdmin(0)
                ->setAddress('pastillegris.gif')
                ->setAddedTool(0)
                ->setSessionId($sessionId)
                ->setTarget('_self');

            $em->persist($tool);
            $em->flush();

            $tool->setId($tool->getIid());

            $em->persist($tool);
            $em->flush();

            return true;
        }

        if ($setVisibility == 1 && $tool) {
            $tool
                ->setName(strip_tags($category->getName()))
                ->setVisibility(1);

            $em->persist($tool);
            $em->flush();

            return true;
        }

        return false;
    }

    /**
     * Check if the learnpath category is visible for a user.
     *
     * @param int
     * @param int
     *
     * @return bool
     */
    public static function categoryIsVisibleForStudent(
        CLpCategory $category,
        User $user,
        $courseId = 0,
        $sessionId = 0
    ) {
        if (empty($category)) {
            return false;
        }

        $isAllowedToEdit = api_is_allowed_to_edit(null, true);

        if ($isAllowedToEdit) {
            return true;
        }

        $courseId = empty($courseId) ? api_get_course_int_id() : (int) $courseId;
        $sessionId = empty($sessionId) ? api_get_session_id() : (int) $sessionId;

        $courseInfo = api_get_course_info_by_id($courseId);

        $categoryVisibility = api_get_item_visibility(
            $courseInfo,
            TOOL_LEARNPATH_CATEGORY,
            $category->getId(),
            $sessionId
        );

        if ($categoryVisibility !== 1 && $categoryVisibility != -1) {
            return false;
        }

        $subscriptionSettings = self::getSubscriptionSettings();

        if ($subscriptionSettings['allow_add_users_to_lp_category'] == false) {
            return true;
        }

        $noUserSubscribed = false;
        $noGroupSubscribed = true;
        $users = $category->getUsers();
        if (empty($users) || !$users->count()) {
            $noUserSubscribed = true;
        } elseif ($category->hasUserAdded($user)) {
            return true;
        }

        $groups = GroupManager::getAllGroupPerUserSubscription($user->getId());
        $em = Database::getManager();

        /** @var ItemPropertyRepository $itemRepo */
        $itemRepo = $em->getRepository('ChamiloCourseBundle:CItemProperty');

        /** @var CourseRepository $courseRepo */
        $courseRepo = $em->getRepository('ChamiloCoreBundle:Course');
        $session = null;
        if (!empty($sessionId)) {
            $session = $em->getRepository('ChamiloCoreBundle:Session')->find($sessionId);
        }

        $course = $courseRepo->find($courseId);

        if ($courseId != 0) {
            // Subscribed groups to a LP
            $subscribedGroupsInLp = $itemRepo->getGroupsSubscribedToItem(
                TOOL_LEARNPATH_CATEGORY,
                $category->getId(),
                $course,
                $session
            );
        }

        if (!empty($subscribedGroupsInLp)) {
            $noGroupSubscribed = false;
            if (!empty($groups)) {
                $groups = array_column($groups, 'iid');
                /** @var CItemProperty $item */
                foreach ($subscribedGroupsInLp as $item) {
                    if ($item->getGroup() &&
                        in_array($item->getGroup()->getId(), $groups)
                    ) {
                        return true;
                    }
                }
            }
        }
        $response = $noGroupSubscribed && $noUserSubscribed;

        return $response;
    }

    /**
     * Check if a learnpath category is published as course tool.
     *
     * @param int $courseId
     *
     * @return bool
     */
    public static function categoryIsPublished(CLpCategory $category, $courseId)
    {
        $link = self::getCategoryLinkForTool($category->getId());
        $em = Database::getManager();

        $tools = $em
            ->createQuery("
                SELECT t FROM ChamiloCourseBundle:CTool t
                WHERE t.cId = :course AND
                    t.name = :name AND
                    t.image = 'lp_category.gif' AND
                    t.link LIKE :link
            ")
            ->setParameters([
                'course' => $courseId,
                'name' => strip_tags($category->getName()),
                'link' => "$link%",
            ])
            ->getResult();

        /** @var CTool $tool */
        $tool = current($tools);

        return $tool ? $tool->getVisibility() : false;
    }

    /**
     * Restart the whole learnpath. Return the URL of the first element.
     * Make sure the results are saved with anoter method. This method should probably be redefined in children classes.
     * To use a similar method  statically, use the create_new_attempt() method.
     *
     * @return bool
     */
    public function restart()
    {
        if ($this->debug > 0) {
            error_log('In learnpath::restart()', 0);
        }
        // TODO
        // Call autosave method to save the current progress.
        //$this->index = 0;
        if (api_is_invitee()) {
            return false;
        }
        $session_id = api_get_session_id();
        $course_id = api_get_course_int_id();
        $lp_view_table = Database::get_course_table(TABLE_LP_VIEW);
        $sql = "INSERT INTO $lp_view_table (c_id, lp_id, user_id, view_count, session_id)
                VALUES ($course_id, ".$this->lp_id.",".$this->get_user_id().",".($this->attempt + 1).", $session_id)";
        if ($this->debug > 2) {
            error_log('Inserting new lp_view for restart: '.$sql, 0);
        }
        Database::query($sql);
        $view_id = Database::insert_id();

        if ($view_id) {
            $sql = "UPDATE $lp_view_table SET id = iid WHERE iid = $view_id";
            Database::query($sql);
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
     * Saves the current item.
     *
     * @return bool
     */
    public function save_current()
    {
        $debug = $this->debug;
        // TODO: Do a better check on the index pointing to the right item (it is supposed to be working
        // on $ordered_items[] but not sure it's always safe to use with $items[]).
        if ($debug) {
            error_log('save_current() saving item '.$this->current, 0);
            error_log(''.print_r($this->items, true), 0);
        }
        if (isset($this->items[$this->current]) &&
            is_object($this->items[$this->current])
        ) {
            if ($debug) {
                error_log('Before save last_scorm_session_time: '.$this->items[$this->current]->getLastScormSessionTime());
            }

            $res = $this->items[$this->current]->save(
                false,
                $this->prerequisites_match($this->current)
            );
            $this->autocomplete_parents($this->current);
            $status = $this->items[$this->current]->get_status();
            $this->update_queue[$this->current] = $status;

            if ($debug) {
                error_log('After save last_scorm_session_time: '.$this->items[$this->current]->getLastScormSessionTime());
            }

            return $res;
        }

        return false;
    }

    /**
     * Saves the given item.
     *
     * @param int  $item_id      Optional (will take from $_REQUEST if null)
     * @param bool $from_outside Save from url params (true) or from current attributes (false). Default true
     *
     * @return bool
     */
    public function save_item($item_id = null, $from_outside = true)
    {
        $debug = $this->debug;
        if ($debug) {
            error_log('In learnpath::save_item('.$item_id.','.intval($from_outside).')', 0);
        }
        // TODO: Do a better check on the index pointing to the right item (it is supposed to be working
        // on $ordered_items[] but not sure it's always safe to use with $items[]).
        if (empty($item_id)) {
            $item_id = (int) $_REQUEST['id'];
        }

        if (empty($item_id)) {
            $item_id = $this->get_current_item_id();
        }
        if (isset($this->items[$item_id]) &&
            is_object($this->items[$item_id])
        ) {
            // Saving the item.
            $res = $this->items[$item_id]->save(
                $from_outside,
                $this->prerequisites_match($item_id)
            );

            if ($debug) {
                error_log('update_queue before:');
                error_log(print_r($this->update_queue, 1));
            }
            $this->autocomplete_parents($item_id);

            $status = $this->items[$item_id]->get_status();
            $this->update_queue[$item_id] = $status;

            if ($debug) {
                error_log('get_status(): '.$status);
                error_log('update_queue after:');
                error_log(print_r($this->update_queue, 1));
            }

            return $res;
        }

        return false;
    }

    /**
     * Update the last progress only in case.
     */
    public function updateLpProgress()
    {
        $debug = $this->debug;
        if ($debug) {
            error_log('In learnpath::updateLpProgress()', 0);
        }
        $sessionCondition = api_get_session_condition(
            api_get_session_id(),
            true,
            false
        );
        $courseId = api_get_course_int_id();
        $userId = $this->get_user_id();
        $table = Database::get_course_table(TABLE_LP_VIEW);

        [$progress] = $this->get_progress_bar_text('%');
        if ($progress >= 0 && $progress <= 100) {
            // Check database.
            $progress = (int) $progress;
            $sql = "UPDATE $table SET
                            progress = $progress
                        WHERE
                            c_id = $courseId AND
                            lp_id = ".$this->get_id()." AND
                            progress < $progress AND
                            user_id = ".$userId." ".$sessionCondition;
            // Ignore errors as some tables might not have the progress field just yet.
            Database::query($sql);
            if ($debug) {
                error_log($sql);
            }
            $this->progress_db = $progress;

            if (100 == $progress) {
                HookLearningPathEnd::create()
                    ->setEventData(['lp_view_id' => $this->lp_view_id])
                    ->hookLearningPathEnd();
            }
        }
    }

    /**
     * Saves the last item seen's ID only in case.
     */
    public function save_last($score = null)
    {
        $course_id = api_get_course_int_id();
        $debug = $this->debug;
        if ($debug) {
            error_log('In learnpath::save_last()', 0);
        }
        $session_condition = api_get_session_condition(
            api_get_session_id(),
            true,
            false
        );
        $table = Database::get_course_table(TABLE_LP_VIEW);

        $userId = $this->get_user_id();
        if (empty($userId)) {
            $userId = api_get_user_id();
            if ($debug) {
                error_log('$this->get_user_id() was empty, used api_get_user_id() instead in '.__FILE__.' line '.__LINE__);
            }
        }
        if (isset($this->current) && !api_is_invitee()) {
            if ($debug) {
                error_log('Saving current item ('.$this->current.') for later review', 0);
            }
            $sql = "UPDATE $table SET
                        last_item = ".$this->get_current_item_id()."
                    WHERE
                        c_id = $course_id AND
                        lp_id = ".$this->get_id()." AND
                        user_id = ".$userId." ".$session_condition;
            if ($debug) {
                error_log('Saving last item seen : '.$sql, 0);
            }
            Database::query($sql);
        }

        if (!api_is_invitee()) {
            // Save progress.
            [$progress] = $this->get_progress_bar_text('%');
            $scoreAsProgressSetting = api_get_configuration_value('lp_score_as_progress_enable');
            $scoreAsProgress = $this->getUseScoreAsProgress();
            if ($scoreAsProgress && $scoreAsProgressSetting && (null === $score || empty($score) || -1 == $score)) {
                if ($debug) {
                    error_log("Return false: Dont save score: $score");
                    error_log("progress: $progress");
                }

                return false;
            }

            if ($scoreAsProgress && $scoreAsProgressSetting) {
                $storedProgress = self::getProgress(
                    $this->get_id(),
                    $userId,
                    $course_id,
                    $this->get_lp_session_id()
                );

                // Check if the stored progress is higher than the new value
                if ($storedProgress >= $progress) {
                    if ($debug) {
                        error_log("Return false: New progress value is lower than stored value - Current value: $storedProgress - New value: $progress [lp ".$this->get_id()." - user ".$userId."]");
                    }

                    return false;
                }
            }

            if ($progress >= 0 && $progress <= 100) {
                // Check database.
                $progress = (int) $progress;
                $sql = "UPDATE $table SET
                            progress = $progress
                        WHERE
                            c_id = $course_id AND
                            lp_id = ".$this->get_id()." AND
                            user_id = ".$userId." ".$session_condition;
                // Ignore errors as some tables might not have the progress field just yet.
                Database::query($sql);
                if ($debug) {
                    error_log($sql);
                }
                $this->progress_db = $progress;

                if (100 == $progress) {
                    HookLearningPathEnd::create()
                        ->setEventData(['lp_view_id' => $this->lp_view_id])
                        ->hookLearningPathEnd();
                }
            }
        }
    }

    /**
     * Sets the current item ID (checks if valid and authorized first).
     *
     * @param int $item_id New item ID. If not given or not authorized, defaults to current
     */
    public function set_current_item($item_id = null)
    {
        $debug = $this->debug;
        if ($debug) {
            error_log('In learnpath::set_current_item('.$item_id.')', 0);
        }
        if (empty($item_id)) {
            if ($debug) {
                error_log('No new current item given, ignore...', 0);
            }
            // Do nothing.
        } else {
            if ($debug) {
                error_log('New current item given is '.$item_id.'...', 0);
            }
            if (is_numeric($item_id)) {
                $item_id = (int) $item_id;
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
                if ($debug) {
                    error_log('set_current_item('.$item_id.') done. Index is now : '.$this->index);
                }
            } else {
                if ($debug) {
                    error_log('set_current_item('.$item_id.') failed. Not a numeric value: ');
                }
            }
        }
    }

    /**
     * Sets the encoding.
     *
     * @param string $enc New encoding
     *
     * @return bool
     *
     * @todo (as of Chamilo 1.8.8): Check in the future whether this method is needed.
     */
    public function set_encoding($enc = 'UTF-8')
    {
        $enc = api_refine_encoding_id($enc);
        if (empty($enc)) {
            $enc = api_get_system_encoding();
        }
        if (api_is_encoding_supported($enc)) {
            $lp = $this->get_id();
            if ($lp != 0) {
                $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
                $sql = "UPDATE $tbl_lp SET default_encoding = '$enc'
                        WHERE iid = ".$lp;
                $res = Database::query($sql);

                return $res;
            }
        }

        return false;
    }

    /**
     * Sets the JS lib setting in the database directly.
     * This is the JavaScript library file this lp needs to load on startup.
     *
     * @param string $lib Proximity setting
     *
     * @return bool True on update success. False otherwise.
     */
    public function set_jslib($lib = '')
    {
        $lp = $this->get_id();

        if ($lp != 0) {
            $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
            $lib = Database::escape_string($lib);
            $sql = "UPDATE $tbl_lp SET js_lib = '$lib'
                    WHERE iid = $lp";
            $res = Database::query($sql);

            return $res;
        }

        return false;
    }

    /**
     * Sets the name of the LP maker (publisher) (and save).
     *
     * @param string $name Optional string giving the new content_maker of this learnpath
     *
     * @return bool True
     */
    public function set_maker($name = '')
    {
        if (empty($name)) {
            return false;
        }
        $this->maker = $name;
        $table = Database::get_course_table(TABLE_LP_MAIN);
        $lp_id = $this->get_id();
        $sql = "UPDATE $table SET
                content_maker = '".Database::escape_string($this->maker)."'
                WHERE iid = $lp_id";
        Database::query($sql);

        return true;
    }

    /**
     * Sets the name of the current learnpath (and save).
     *
     * @param string $name Optional string giving the new name of this learnpath
     *
     * @return bool True/False
     */
    public function set_name($name = null)
    {
        if (empty($name)) {
            return false;
        }
        $lp_table = Database::get_course_table(TABLE_LP_MAIN);
        $name = Database::escape_string($name);

        $this->name = $name;

        $lp_id = $this->get_id();
        $course_id = $this->course_info['real_id'];
        $sql = "UPDATE $lp_table SET
                name = '$name'
                WHERE iid = $lp_id";
        $result = Database::query($sql);
        // If the lp is visible on the homepage, change his name there.
        if (Database::affected_rows($result)) {
            $session_id = api_get_session_id();
            $session_condition = api_get_session_condition($session_id);
            $tbl_tool = Database::get_course_table(TABLE_TOOL_LIST);
            $link = 'lp/lp_controller.php?action=view&lp_id='.$lp_id.'&id_session='.$session_id;
            $sql = "UPDATE $tbl_tool SET name = '$name'
            	    WHERE
            	        c_id = $course_id AND
            	        (link='$link' AND image='scormbuilder.gif' $session_condition)";
            Database::query($sql);

            return true;
        }

        return false;
    }

    /**
     * Set index specified prefix terms for all items in this path.
     *
     * @param string $terms_string Comma-separated list of terms
     * @param string $prefix       Xapian term prefix
     *
     * @return bool False on error, true otherwise
     */
    public function set_terms_by_prefix($terms_string, $prefix)
    {
        $course_id = api_get_course_int_id();
        if (api_get_setting('search_enabled') !== 'true') {
            return false;
        }

        if (!extension_loaded('xapian')) {
            return false;
        }

        $terms_string = trim($terms_string);
        $terms = explode(',', $terms_string);
        array_walk($terms, 'trim_value');
        $stored_terms = $this->get_common_index_terms_by_prefix($prefix);

        // Don't do anything if no change, verify only at DB, not the search engine.
        if ((count(array_diff($terms, $stored_terms)) == 0) && (count(array_diff($stored_terms, $terms)) == 0)) {
            return false;
        }

        require_once 'xapian.php'; // TODO: Try catch every xapian use or make wrappers on API.
        require_once api_get_path(LIBRARY_PATH).'search/xapian/XapianQuery.php';

        $items_table = Database::get_course_table(TABLE_LP_ITEM);
        // TODO: Make query secure agains XSS : use member attr instead of post var.
        $lp_id = (int) $_POST['lp_id'];
        $sql = "SELECT * FROM $items_table WHERE c_id = $course_id AND lp_id = $lp_id";
        $result = Database::query($sql);
        $di = new ChamiloIndexer();

        while ($lp_item = Database::fetch_array($result)) {
            // Get search_did.
            $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
            $sql = 'SELECT * FROM %s
                    WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s AND ref_id_second_level=%d
                    LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $this->cc, TOOL_LEARNPATH, $lp_id, $lp_item['id']);

            //echo $sql; echo '<br>';
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                $se_ref = Database::fetch_array($res);
                // Compare terms.
                $doc = $di->get_document($se_ref['search_did']);
                $xapian_terms = xapian_get_doc_terms($doc, $prefix);
                $xterms = [];
                foreach ($xapian_terms as $xapian_term) {
                    $xterms[] = substr($xapian_term['name'], 1);
                }

                $dterms = $terms;
                $missing_terms = array_diff($dterms, $xterms);
                $deprecated_terms = array_diff($xterms, $dterms);

                // Save it to search engine.
                foreach ($missing_terms as $term) {
                    $doc->add_term($prefix.$term, 1);
                }
                foreach ($deprecated_terms as $term) {
                    $doc->remove_term($prefix.$term);
                }
                $di->getDb()->replace_document((int) $se_ref['search_did'], $doc);
                $di->getDb()->flush();
            }
        }

        return true;
    }

    /**
     * Sets the theme of the LP (local/remote) (and save).
     *
     * @param string $name Optional string giving the new theme of this learnpath
     *
     * @return bool Returns true if theme name is not empty
     */
    public function set_theme($name = '')
    {
        $this->theme = $name;
        $table = Database::get_course_table(TABLE_LP_MAIN);
        $lp_id = $this->get_id();
        $sql = "UPDATE $table
                SET theme = '".Database::escape_string($this->theme)."'
                WHERE iid = $lp_id";
        Database::query($sql);

        return true;
    }

    /**
     * Sets the image of an LP (and save).
     *
     * @param string $name Optional string giving the new image of this learnpath
     *
     * @return bool Returns true if theme name is not empty
     */
    public function set_preview_image($name = '')
    {
        $this->preview_image = $name;
        $table = Database::get_course_table(TABLE_LP_MAIN);
        $lp_id = $this->get_id();
        $sql = "UPDATE $table SET
                preview_image = '".Database::escape_string($this->preview_image)."'
                WHERE iid = $lp_id";
        Database::query($sql);

        return true;
    }

    /**
     * Sets the author of a LP (and save).
     *
     * @param string $name Optional string giving the new author of this learnpath
     *
     * @return bool Returns true if author's name is not empty
     */
    public function set_author($name = '')
    {
        $this->author = $name;
        $table = Database::get_course_table(TABLE_LP_MAIN);
        $lp_id = $this->get_id();
        $sql = "UPDATE $table SET author = '".Database::escape_string($name)."'
                WHERE iid = $lp_id";
        Database::query($sql);

        return true;
    }

    /**
     * Sets the hide_toc_frame parameter of a LP (and save).
     *
     * @param int $hide 1 if frame is hidden 0 then else
     *
     * @return bool Returns true if author's name is not empty
     */
    public function set_hide_toc_frame($hide)
    {
        if (intval($hide) == $hide) {
            $this->hide_toc_frame = $hide;
            $table = Database::get_course_table(TABLE_LP_MAIN);
            $lp_id = $this->get_id();
            $sql = "UPDATE $table SET
                    hide_toc_frame = '".(int) $this->hide_toc_frame."'
                    WHERE iid = $lp_id";
            Database::query($sql);

            return true;
        }

        return false;
    }

    /**
     * Sets the prerequisite of a LP (and save).
     *
     * @param int $prerequisite integer giving the new prerequisite of this learnpath
     *
     * @return bool returns true if prerequisite is not empty
     */
    public function set_prerequisite($prerequisite)
    {
        $this->prerequisite = (int) $prerequisite;
        $table = Database::get_course_table(TABLE_LP_MAIN);
        $lp_id = $this->get_id();
        $sql = "UPDATE $table SET prerequisite = '".$this->prerequisite."'
                WHERE iid = $lp_id";
        Database::query($sql);

        return true;
    }

    /**
     * Sets the location/proximity of the LP (local/remote) (and save).
     *
     * @param string $name Optional string giving the new location of this learnpath
     *
     * @return bool True on success / False on error
     */
    public function set_proximity($name = '')
    {
        if (empty($name)) {
            return false;
        }

        $this->proximity = $name;
        $table = Database::get_course_table(TABLE_LP_MAIN);
        $lp_id = $this->get_id();
        $sql = "UPDATE $table SET
                    content_local = '".Database::escape_string($name)."'
                WHERE iid = $lp_id";
        Database::query($sql);

        return true;
    }

    /**
     * Sets the previous item ID to a given ID. Generally, this should be set to the previous 'current' item.
     *
     * @param int $id DB ID of the item
     */
    public function set_previous_item($id)
    {
        if ($this->debug > 0) {
            error_log('In learnpath::set_previous_item()', 0);
        }
        $this->last = $id;
    }

    /**
     * Sets use_max_score.
     *
     * @param int $use_max_score Optional string giving the new location of this learnpath
     *
     * @return bool True on success / False on error
     */
    public function set_use_max_score($use_max_score = 1)
    {
        $use_max_score = (int) $use_max_score;
        $this->use_max_score = $use_max_score;
        $table = Database::get_course_table(TABLE_LP_MAIN);
        $lp_id = $this->get_id();
        $sql = "UPDATE $table SET
                    use_max_score = '".$this->use_max_score."'
                WHERE iid = $lp_id";
        Database::query($sql);

        return true;
    }

    /**
     * Sets and saves the expired_on date.
     *
     * @param string $expired_on Optional string giving the new author of this learnpath
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return bool Returns true if author's name is not empty
     */
    public function set_expired_on($expired_on)
    {
        $em = Database::getManager();
        /** @var CLp $lp */
        $lp = $em
            ->getRepository('ChamiloCourseBundle:CLp')
            ->findOneBy(
                [
                    'iid' => $this->get_id(),
                ]
            );

        if (!$lp) {
            return false;
        }

        $this->expired_on = !empty($expired_on) ? api_get_utc_datetime($expired_on, false, true) : null;

        $lp->setExpiredOn($this->expired_on);
        $em->persist($lp);
        $em->flush();

        return true;
    }

    /**
     * Sets and saves the publicated_on date.
     *
     * @param string $publicated_on Optional string giving the new author of this learnpath
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return bool Returns true if author's name is not empty
     */
    public function set_publicated_on($publicated_on)
    {
        $em = Database::getManager();
        /** @var CLp $lp */
        $lp = $em
            ->getRepository('ChamiloCourseBundle:CLp')
            ->findOneBy(
                [
                    'iid' => $this->get_id(),
                ]
            );

        if (!$lp) {
            return false;
        }

        $this->publicated_on = !empty($publicated_on) ? api_get_utc_datetime($publicated_on, false, true) : null;
        $lp->setPublicatedOn($this->publicated_on);
        $em->persist($lp);
        $em->flush();

        return true;
    }

    /**
     * Sets and saves the expired_on date.
     *
     * @return bool Returns true if author's name is not empty
     */
    public function set_modified_on()
    {
        $this->modified_on = api_get_utc_datetime();
        $table = Database::get_course_table(TABLE_LP_MAIN);
        $lp_id = $this->get_id();
        $sql = "UPDATE $table SET modified_on = '".$this->modified_on."'
                WHERE iid = $lp_id";
        Database::query($sql);

        return true;
    }

    /**
     * Sets the object's error message.
     *
     * @param string $error Error message. If empty, reinits the error string
     */
    public function set_error_msg($error = '')
    {
        if ($this->debug > 0) {
            error_log('In learnpath::set_error_msg()', 0);
        }
        if (empty($error)) {
            $this->error = '';
        } else {
            $this->error .= $error;
        }
    }

    /**
     * Launches the current item if not 'sco'
     * (starts timer and make sure there is a record ready in the DB).
     *
     * @param bool $allow_new_attempt Whether to allow a new attempt or not
     *
     * @return bool
     */
    public function start_current_item($allow_new_attempt = false)
    {
        $debug = $this->debug;
        if ($debug) {
            error_log('In learnpath::start_current_item()');
            error_log('current: '.$this->current);
        }
        if ($this->current != 0 && isset($this->items[$this->current]) && is_object($this->items[$this->current])) {
            $type = $this->get_type();
            $item_type = $this->items[$this->current]->get_type();
            if (($type == 2 && $item_type != 'sco') ||
                ($type == 3 && $item_type != 'au') ||
                (
                    $type == 1 && $item_type != TOOL_QUIZ && $item_type != TOOL_HOTPOTATOES &&
                    WhispeakAuthPlugin::isAllowedToSaveLpItem($this->current)
                )
            ) {
                if ($debug) {
                    error_log('item type: '.$item_type);
                    error_log('lp type: '.$type);
                }
                $this->items[$this->current]->open($allow_new_attempt);
                $this->autocomplete_parents($this->current);
                $prereq_check = $this->prerequisites_match($this->current);
                if ($debug) {
                    error_log('start_current_item will save item with prereq: '.$prereq_check);
                }

                $saveStatus = learnpathItem::isLpItemAutoComplete($this->current);
                if ($saveStatus) {
                    $this->items[$this->current]->save(false, $prereq_check);
                }
            }
            // If sco, then it is supposed to have been updated by some other call.
            if ($item_type === 'sco') {
                $this->items[$this->current]->restart();
            }
        }
        if ($debug) {
            error_log('lp_view_session_id: '.$this->lp_view_session_id);
            error_log('api_get_session_id: '.api_get_session_id());
            error_log('End of learnpath::start_current_item()');
        }

        return true;
    }

    /**
     * Stops the processing and counters for the old item (as held in $this->last).
     *
     * @return bool True/False
     */
    public function stop_previous_item()
    {
        $debug = $this->debug;
        if ($debug) {
            error_log('In learnpath::stop_previous_item()');
        }

        if ($this->last != 0 && $this->last != $this->current &&
            isset($this->items[$this->last]) && is_object($this->items[$this->last])
        ) {
            if ($debug) {
                error_log('In learnpath::stop_previous_item() - '.$this->last.' is object');
            }
            switch ($this->get_type()) {
                case '3':
                    if ($this->items[$this->last]->get_type() != 'au') {
                        if ($debug) {
                            error_log('In learnpath::stop_previous_item() - '.$this->last.' in lp_type 3 is <> au');
                        }
                        $this->items[$this->last]->close();
                    } else {
                        if ($debug) {
                            error_log('In learnpath::stop_previous_item() - Item is an AU, saving is managed by AICC signals');
                        }
                    }
                    break;
                case '2':
                    if ($this->items[$this->last]->get_type() != 'sco') {
                        if ($debug) {
                            error_log('In learnpath::stop_previous_item() - '.$this->last.' in lp_type 2 is <> sco');
                        }
                        $this->items[$this->last]->close();
                    } else {
                        if ($debug) {
                            error_log('In learnpath::stop_previous_item() - Item is a SCO, saving is managed by SCO signals');
                        }
                    }
                    break;
                case '1':
                default:
                    if ($debug) {
                        error_log('In learnpath::stop_previous_item() - '.$this->last.' in lp_type 1 is asset');
                    }
                    $this->items[$this->last]->close();
                    break;
            }
        } else {
            if ($debug) {
                error_log('In learnpath::stop_previous_item() - No previous element found, ignoring...');
            }

            return false;
        }

        return true;
    }

    /**
     * Updates the default view mode from fullscreen to embedded and inversely.
     *
     * @return string The current default view mode ('fullscreen' or 'embedded')
     */
    public function update_default_view_mode()
    {
        $table = Database::get_course_table(TABLE_LP_MAIN);
        $sql = "SELECT * FROM $table
                WHERE iid = ".$this->get_id();
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res);
            $default_view_mode = $row['default_view_mod'];
            $view_mode = $default_view_mode;
            switch ($default_view_mode) {
                case 'fullscreen': // default with popup
                    $view_mode = 'embedded';
                    break;
                case 'embedded': // default view with left menu
                    $view_mode = 'embedframe';
                    break;
                case 'embedframe': //folded menu
                    $view_mode = 'impress';
                    break;
                case 'impress':
                    $view_mode = 'fullscreen';
                    break;
            }
            $sql = "UPDATE $table SET default_view_mod = '$view_mode'
                    WHERE iid = ".$this->get_id();
            Database::query($sql);
            $this->mode = $view_mode;

            return $view_mode;
        }

        return -1;
    }

    /**
     * Updates the default behaviour about auto-commiting SCORM updates.
     *
     * @return bool True if auto-commit has been set to 'on', false otherwise
     */
    public function update_default_scorm_commit()
    {
        $lp_table = Database::get_course_table(TABLE_LP_MAIN);
        $sql = "SELECT * FROM $lp_table
                WHERE iid = ".$this->get_id();
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res);
            $force = $row['force_commit'];
            if ($force == 1) {
                $force = 0;
                $force_return = false;
            } elseif ($force == 0) {
                $force = 1;
                $force_return = true;
            }
            $sql = "UPDATE $lp_table SET force_commit = $force
                    WHERE iid = ".$this->get_id();
            Database::query($sql);
            $this->force_commit = $force_return;

            return $force_return;
        }

        return -1;
    }

    /**
     * Updates the order of learning paths (goes through all of them by order and fills the gaps).
     *
     * @return bool True on success, false on failure
     */
    public function update_display_order()
    {
        $course_id = api_get_course_int_id();
        $table = Database::get_course_table(TABLE_LP_MAIN);
        $sql = "SELECT * FROM $table
                WHERE c_id = $course_id
                ORDER BY display_order";
        $res = Database::query($sql);
        if ($res === false) {
            return false;
        }

        $num = Database::num_rows($res);
        // First check the order is correct, globally (might be wrong because
        // of versions < 1.8.4).
        if ($num > 0) {
            $i = 1;
            while ($row = Database::fetch_array($res)) {
                if ($row['display_order'] != $i) {
                    // If we find a gap in the order, we need to fix it.
                    $sql = "UPDATE $table SET display_order = $i
                            WHERE iid = ".$row['iid'];
                    Database::query($sql);
                }
                $i++;
            }
        }

        return true;
    }

    /**
     * Updates the "prevent_reinit" value that enables control on reinitialising items on second view.
     *
     * @return bool True if prevent_reinit has been set to 'on', false otherwise (or 1 or 0 in this case)
     */
    public function update_reinit()
    {
        $lp_table = Database::get_course_table(TABLE_LP_MAIN);
        $sql = "SELECT * FROM $lp_table
                WHERE iid = ".$this->get_id();
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res);
            $force = $row['prevent_reinit'];
            if ($force == 1) {
                $force = 0;
            } elseif ($force == 0) {
                $force = 1;
            }
            $sql = "UPDATE $lp_table SET prevent_reinit = $force
                    WHERE iid = ".$this->get_id();
            Database::query($sql);
            $this->prevent_reinit = $force;

            return $force;
        }

        return -1;
    }

    /**
     * Determine the attempt_mode thanks to prevent_reinit and seriousgame_mode db flag.
     *
     * @return string 'single', 'multi' or 'seriousgame'
     *
     * @author ndiechburg <noel@cblue.be>
     */
    public function get_attempt_mode()
    {
        //Set default value for seriousgame_mode
        if (!isset($this->seriousgame_mode)) {
            $this->seriousgame_mode = 0;
        }
        // Set default value for prevent_reinit
        if (!isset($this->prevent_reinit)) {
            $this->prevent_reinit = 1;
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
     * Register the attempt mode into db thanks to flags prevent_reinit and seriousgame_mode flags.
     *
     * @param string 'seriousgame', 'single' or 'multiple'
     *
     * @return bool
     *
     * @author ndiechburg <noel@cblue.be>
     */
    public function set_attempt_mode($mode)
    {
        switch ($mode) {
            case 'seriousgame':
                $sg_mode = 1;
                $prevent_reinit = 1;
                break;
            case 'single':
                $sg_mode = 0;
                $prevent_reinit = 1;
                break;
            case 'multiple':
                $sg_mode = 0;
                $prevent_reinit = 0;
                break;
            default:
                $sg_mode = 0;
                $prevent_reinit = 0;
                break;
        }
        $this->prevent_reinit = $prevent_reinit;
        $this->seriousgame_mode = $sg_mode;
        $table = Database::get_course_table(TABLE_LP_MAIN);
        $sql = "UPDATE $table SET
                prevent_reinit = $prevent_reinit ,
                seriousgame_mode = $sg_mode
                WHERE iid = ".$this->get_id();
        $res = Database::query($sql);
        if ($res) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Switch between multiple attempt, single attempt or serious_game mode (only for scorm).
     *
     * @author ndiechburg <noel@cblue.be>
     */
    public function switch_attempt_mode()
    {
        $mode = $this->get_attempt_mode();
        switch ($mode) {
            case 'single':
                $next_mode = 'multiple';
                break;
            case 'multiple':
                $next_mode = 'seriousgame';
                break;
            case 'seriousgame':
            default:
                $next_mode = 'single';
                break;
        }
        $this->set_attempt_mode($next_mode);
    }

    /**
     * Switch the lp in ktm mode. This is a special scorm mode with unique attempt
     * but possibility to do again a completed item.
     *
     * @return bool true if seriousgame_mode has been set to 1, false otherwise
     *
     * @author ndiechburg <noel@cblue.be>
     */
    public function set_seriousgame_mode()
    {
        $lp_table = Database::get_course_table(TABLE_LP_MAIN);
        $sql = "SELECT * FROM $lp_table
                WHERE iid = ".$this->get_id();
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res);
            $force = $row['seriousgame_mode'];
            if ($force == 1) {
                $force = 0;
            } elseif ($force == 0) {
                $force = 1;
            }
            $sql = "UPDATE $lp_table SET seriousgame_mode = $force
			        WHERE iid = ".$this->get_id();
            Database::query($sql);
            $this->seriousgame_mode = $force;

            return $force;
        }

        return -1;
    }

    /**
     * Updates the "scorm_debug" value that shows or hide the debug window.
     *
     * @return bool True if scorm_debug has been set to 'on', false otherwise (or 1 or 0 in this case)
     */
    public function update_scorm_debug()
    {
        $lp_table = Database::get_course_table(TABLE_LP_MAIN);
        $sql = "SELECT * FROM $lp_table
                WHERE iid = ".$this->get_id();
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res);
            $force = $row['debug'];
            if ($force == 1) {
                $force = 0;
            } elseif ($force == 0) {
                $force = 1;
            }
            $sql = "UPDATE $lp_table SET debug = $force
                    WHERE iid = ".$this->get_id();
            Database::query($sql);
            $this->scorm_debug = $force;

            return $force;
        }

        return -1;
    }

    /**
     * Function that makes a call to the function sort_tree_array and create_tree_array.
     *
     * @author Kevin Van Den Haute
     *
     * @param  array
     */
    public function tree_array($array)
    {
        $array = $this->sort_tree_array($array);
        $this->create_tree_array($array);
    }

    /**
     * Creates an array with the elements of the learning path tree in it.
     *
     * @author Kevin Van Den Haute
     *
     * @param array $array
     * @param int   $parent
     * @param int   $depth
     * @param array $tmp
     */
    public function create_tree_array($array, $parent = 0, $depth = -1, $tmp = [])
    {
        if (is_array($array)) {
            for ($i = 0; $i < count($array); $i++) {
                if ($array[$i]['parent_item_id'] == $parent) {
                    if (!in_array($array[$i]['parent_item_id'], $tmp)) {
                        $tmp[] = $array[$i]['parent_item_id'];
                        $depth++;
                    }
                    $preq = (empty($array[$i]['prerequisite']) ? '' : $array[$i]['prerequisite']);
                    $audio = isset($array[$i]['audio']) ? $array[$i]['audio'] : null;
                    $path = isset($array[$i]['path']) ? $array[$i]['path'] : null;

                    $prerequisiteMinScore = isset($array[$i]['prerequisite_min_score']) ? $array[$i]['prerequisite_min_score'] : null;
                    $prerequisiteMaxScore = isset($array[$i]['prerequisite_max_score']) ? $array[$i]['prerequisite_max_score'] : null;
                    $ref = isset($array[$i]['ref']) ? $array[$i]['ref'] : '';
                    $this->arrMenu[] = [
                        'id' => $array[$i]['id'],
                        'ref' => $ref,
                        'item_type' => $array[$i]['item_type'],
                        'title' => $array[$i]['title'],
                        'title_raw' => $array[$i]['title_raw'],
                        'path' => $path,
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
                        'audio' => $audio,
                        'prerequisite_min_score' => $prerequisiteMinScore,
                        'prerequisite_max_score' => $prerequisiteMaxScore,
                    ];
                    $this->create_tree_array($array, $array[$i]['id'], $depth, $tmp);
                }
            }
        }
    }

    /**
     * Sorts a multi dimensional array by parent id and display order.
     *
     * @author Kevin Van Den Haute
     *
     * @param array $array (array with al the learning path items in it)
     *
     * @return array
     */
    public function sort_tree_array($array)
    {
        foreach ($array as $key => $row) {
            $parent[$key] = $row['parent_item_id'];
            $position[$key] = $row['display_order'];
        }

        if (count($array) > 0) {
            array_multisort($parent, SORT_ASC, $position, SORT_ASC, $array);
        }

        return $array;
    }

    /**
     * Function that creates a html list of learning path items so that we can add audio files to them.
     *
     * @author Kevin Van Den Haute
     *
     * @return string
     */
    public function overview()
    {
        $return = '';
        $update_audio = isset($_GET['updateaudio']) ? $_GET['updateaudio'] : null;

        // we need to start a form when we want to update all the mp3 files
        if ($update_audio == 'true') {
            $return .= '<form action="'.api_get_self().'?'.api_get_cidreq().'&updateaudio='.Security::remove_XSS($_GET['updateaudio']).'&action='.Security::remove_XSS($_GET['action']).'&lp_id='.$_SESSION['oLP']->lp_id.'" method="post" enctype="multipart/form-data" name="updatemp3" id="updatemp3">';
        }
        $return .= '<div id="message"></div>';
        if (count($this->items) == 0) {
            $return .= Display::return_message(get_lang('YouShouldAddItemsBeforeAttachAudio'), 'normal');
        } else {
            $return_audio = '<table class="table table-hover table-striped data_table">';
            $return_audio .= '<tr>';
            $return_audio .= '<th width="40%">'.get_lang('Title').'</th>';
            $return_audio .= '<th>'.get_lang('Audio').'</th>';
            $return_audio .= '</tr>';

            if ($update_audio != 'true') {
                $return .= '<div class="col-md-12">';
                $return .= self::return_new_tree($update_audio);
                $return .= '</div>';
                $return .= Display::div(
                    Display::url(get_lang('Save'), '#', ['id' => 'listSubmit', 'class' => 'btn btn-primary']),
                    ['style' => 'float:left; margin-top:15px;width:100%']
                );
            } else {
                $return_audio .= self::return_new_tree($update_audio);
                $return .= $return_audio.'</table>';
            }

            // We need to close the form when we are updating the mp3 files.
            if ($update_audio == 'true') {
                $return .= '<div class="footer-audio">';
                $return .= Display::button(
                    'save_audio',
                    '<em class="fa fa-file-audio-o"></em> '.get_lang('SaveAudioAndOrganization'),
                    ['class' => 'btn btn-primary', 'type' => 'submit']
                );
                $return .= '</div>';
            }
        }

        // We need to close the form when we are updating the mp3 files.
        if ($update_audio == 'true' && isset($this->arrMenu) && count($this->arrMenu) != 0) {
            $return .= '</form>';
        }

        return $return;
    }

    /**
     * @param string $update_audio
     *
     * @return array
     */
    public function processBuildMenuElements($update_audio = 'false')
    {
        $is_allowed_to_edit = api_is_allowed_to_edit(null, true);
        $arrLP = $this->getItemsForForm();

        $this->tree_array($arrLP);
        $arrLP = isset($this->arrMenu) ? $this->arrMenu : [];
        unset($this->arrMenu);
        $default_data = null;
        $default_content = null;
        $elements = [];
        $return_audio = null;
        $iconPath = api_get_path(SYS_CODE_PATH).'img/';
        $mainUrl = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq();
        $countItems = count($arrLP);

        $upIcon = Display::return_icon(
            'up.png',
            get_lang('Up'),
            [],
            ICON_SIZE_TINY
        );

        $disableUpIcon = Display::return_icon(
            'up_na.png',
            get_lang('Up'),
            [],
            ICON_SIZE_TINY
        );

        $downIcon = Display::return_icon(
            'down.png',
            get_lang('Down'),
            [],
            ICON_SIZE_TINY
        );

        $disableDownIcon = Display::return_icon(
            'down_na.png',
            get_lang('Down'),
            [],
            ICON_SIZE_TINY
        );

        $show = api_get_configuration_value('show_full_lp_item_title_in_edition');

        $pluginCalendar = api_get_plugin_setting('learning_calendar', 'enabled') === 'true';
        $plugin = null;
        if ($pluginCalendar) {
            $plugin = LearningCalendarPlugin::create();
        }

        for ($i = 0; $i < $countItems; $i++) {
            $parent_id = $arrLP[$i]['parent_item_id'];
            $title = $arrLP[$i]['title'];
            $title_cut = $arrLP[$i]['title_raw'];
            if ($show === false) {
                $title_cut = cut($arrLP[$i]['title'], self::MAX_LP_ITEM_TITLE_LENGTH);
            }
            // Link for the documents
            if ($arrLP[$i]['item_type'] === 'document' || $arrLP[$i]['item_type'] == TOOL_READOUT_TEXT) {
                $url = $mainUrl.'&action=view_item&mode=preview_document&id='.$arrLP[$i]['id'].'&lp_id='.$this->lp_id;
                $title_cut = Display::url(
                    $title_cut,
                    $url,
                    [
                        'class' => 'ajax moved',
                        'data-title' => $title,
                        'title' => $title,
                    ]
                );
            }

            // Detect if type is FINAL_ITEM to set path_id to SESSION
            if ($arrLP[$i]['item_type'] == TOOL_LP_FINAL_ITEM) {
                Session::write('pathItem', $arrLP[$i]['path']);
            }

            $oddClass = 'row_even';
            if (($i % 2) == 0) {
                $oddClass = 'row_odd';
            }
            $return_audio .= '<tr id ="lp_item_'.$arrLP[$i]['id'].'" class="'.$oddClass.'">';
            $icon_name = str_replace(' ', '', $arrLP[$i]['item_type']);

            if (file_exists($iconPath.'lp_'.$icon_name.'.png')) {
                $icon = Display::return_icon('lp_'.$icon_name.'.png');
            } else {
                if (file_exists($iconPath.'lp_'.$icon_name.'.gif')) {
                    $icon = Display::return_icon('lp_'.$icon_name.'.gif');
                } else {
                    if ($arrLP[$i]['item_type'] === TOOL_LP_FINAL_ITEM) {
                        $icon = Display::return_icon('flag_checkered.png');
                    } elseif (TOOL_XAPI === $arrLP[$i]['item_type']) {
                        $icon = Display::return_icon('import_scorm.png');
                    } elseif (TOOL_H5P) {
                        $icon = Display::return_icon('plugin_h5p_import.png');
                    } elseif (TOOL_SURVEY === $arrLP[$i]['item_type']) {
                        $icon = Display::return_icon('survey.gif');
                    } else {
                        $icon = Display::return_icon('folder_document.gif');
                    }
                }
            }

            // The audio column.
            $return_audio .= '<td align="left" style="padding-left:10px;">';
            $audio = '';
            if (!$update_audio || $update_audio != 'true') {
                if (empty($arrLP[$i]['audio'])) {
                    $audio .= '';
                }
            } else {
                $types = self::getChapterTypes();
                if (!in_array($arrLP[$i]['item_type'], $types)) {
                    $audio .= '<input type="file" name="mp3file'.$arrLP[$i]['id'].'" id="mp3file" />';
                    if (!empty($arrLP[$i]['audio'])) {
                        $audio .= '<br />'.Security::remove_XSS($arrLP[$i]['audio']).'<br />
                        <input type="checkbox" name="removemp3'.$arrLP[$i]['id'].'" id="checkbox'.$arrLP[$i]['id'].'" />'.get_lang('RemoveAudio');
                    }
                }
            }

            $return_audio .= Display::span($icon.' '.$title).
                Display::tag(
                    'td',
                    $audio,
                    ['style' => '']
                );
            $return_audio .= '</td>';
            $move_icon = '';
            $move_item_icon = '';
            $edit_icon = '';
            $delete_icon = '';
            $audio_icon = '';
            $prerequisities_icon = '';
            $forumIcon = '';
            $previewIcon = '';
            $pluginCalendarIcon = '';
            $orderIcons = '';
            $pluginUrl = api_get_path(WEB_PLUGIN_PATH).'learning_calendar/start.php?';

            if ($is_allowed_to_edit) {
                if (!$update_audio || $update_audio != 'true') {
                    if ($arrLP[$i]['item_type'] !== TOOL_LP_FINAL_ITEM) {
                        $move_icon .= '<a class="moved" href="#">';
                        $move_icon .= Display::return_icon(
                            'move_everywhere.png',
                            get_lang('Move'),
                            [],
                            ICON_SIZE_TINY
                        );
                        $move_icon .= '</a>';
                    }
                }

                // No edit for this item types
                if (!in_array($arrLP[$i]['item_type'], ['sco', 'asset', 'final_item'])) {
                    if ($arrLP[$i]['item_type'] != 'dir') {
                        $edit_icon .= '<a href="'.$mainUrl.'&action=edit_item&view=build&id='.$arrLP[$i]['id'].'&lp_id='.$this->lp_id.'&path_item='.$arrLP[$i]['path'].'" class="btn btn-default">';
                        $edit_icon .= Display::return_icon(
                            'edit.png',
                            get_lang('LearnpathEditModule'),
                            [],
                            ICON_SIZE_TINY
                        );
                        $edit_icon .= '</a>';

                        if (!in_array($arrLP[$i]['item_type'], ['forum', 'thread'])) {
                            $forumThread = null;
                            if (isset($this->items[$arrLP[$i]['id']])) {
                                $forumThread = $this->items[$arrLP[$i]['id']]->getForumThread(
                                    $this->course_int_id,
                                    $this->lp_session_id
                                );
                            }
                            if ($forumThread) {
                                $forumIconUrl = $mainUrl.'&'.http_build_query([
                                        'action' => 'dissociate_forum',
                                        'id' => $arrLP[$i]['id'],
                                        'lp_id' => $this->lp_id,
                                    ]);
                                $forumIcon = Display::url(
                                    Display::return_icon(
                                        'forum.png',
                                        get_lang('DissociateForumToLPItem'),
                                        [],
                                        ICON_SIZE_TINY
                                    ),
                                    $forumIconUrl,
                                    ['class' => 'btn btn-default lp-btn-dissociate-forum']
                                );
                            } else {
                                $forumIconUrl = $mainUrl.'&'.http_build_query([
                                        'action' => 'create_forum',
                                        'id' => $arrLP[$i]['id'],
                                        'lp_id' => $this->lp_id,
                                    ]);
                                $forumIcon = Display::url(
                                    Display::return_icon(
                                        'forum.png',
                                        get_lang('AssociateForumToLPItem'),
                                        [],
                                        ICON_SIZE_TINY
                                    ),
                                    $forumIconUrl,
                                    ['class' => 'btn btn-default lp-btn-associate-forum']
                                );
                            }
                        }
                    } else {
                        $edit_icon .= '<a href="'.$mainUrl.'&action=edit_item&id='.$arrLP[$i]['id'].'&lp_id='.$this->lp_id.'&path_item='.$arrLP[$i]['path'].'" class="btn btn-default">';
                        $edit_icon .= Display::return_icon(
                            'edit.png',
                            get_lang('LearnpathEditModule'),
                            [],
                            ICON_SIZE_TINY
                        );
                        $edit_icon .= '</a>';
                    }
                } else {
                    if ($arrLP[$i]['item_type'] == TOOL_LP_FINAL_ITEM) {
                        $edit_icon .= '<a href="'.$mainUrl.'&action=edit_item&id='.$arrLP[$i]['id'].'&lp_id='.$this->lp_id.'" class="btn btn-default">';
                        $edit_icon .= Display::return_icon(
                            'edit.png',
                            get_lang('Edit'),
                            [],
                            ICON_SIZE_TINY
                        );
                        $edit_icon .= '</a>';
                    }
                }

                if ($pluginCalendar) {
                    $pluginLink = $pluginUrl.
                        '&action=toggle_visibility&lp_item_id='.$arrLP[$i]['id'].'&lp_id='.$this->lp_id;
                    $iconCalendar = Display::return_icon('agenda_na.png', get_lang('OneDay'), [], ICON_SIZE_TINY);
                    $itemInfo = $plugin->getItemVisibility($arrLP[$i]['id']);
                    if ($itemInfo && $itemInfo['value'] == 1) {
                        $iconCalendar = Display::return_icon('agenda.png', get_lang('OneDay'), [], ICON_SIZE_TINY);
                    }
                    $pluginCalendarIcon = Display::url(
                        $iconCalendar,
                        $pluginLink,
                        ['class' => 'btn btn-default']
                    );
                }

                if ($arrLP[$i]['item_type'] != 'final_item') {
                    $orderIcons = Display::url(
                        $upIcon,
                        'javascript:void(0)',
                        ['class' => 'btn btn-default order_items', 'data-dir' => 'up', 'data-id' => $arrLP[$i]['id']]
                    );
                    $orderIcons .= Display::url(
                        $downIcon,
                        'javascript:void(0)',
                        ['class' => 'btn btn-default order_items', 'data-dir' => 'down', 'data-id' => $arrLP[$i]['id']]
                    );
                }

                $delete_icon .= ' <a
                    href="'.$mainUrl.'&action=delete_item&id='.$arrLP[$i]['id'].'&lp_id='.$this->lp_id.'"
                    onclick="return confirmation(\''.addslashes($title).'\');"
                    class="btn btn-default">';
                $delete_icon .= Display::return_icon(
                    'delete.png',
                    get_lang('LearnpathDeleteModule'),
                    [],
                    ICON_SIZE_TINY
                );
                $delete_icon .= '</a>';

                $url = $mainUrl.'&view=build&id='.$arrLP[$i]['id'].'&lp_id='.$this->lp_id;
                $previewImage = Display::return_icon(
                    'preview_view.png',
                    get_lang('Preview'),
                    [],
                    ICON_SIZE_TINY
                );

                switch ($arrLP[$i]['item_type']) {
                    case TOOL_DOCUMENT:
                    case TOOL_LP_FINAL_ITEM:
                    case TOOL_READOUT_TEXT:
                        $urlPreviewLink = $mainUrl.'&action=view_item&mode=preview_document&id='.$arrLP[$i]['id'].'&lp_id='.$this->lp_id;
                        $previewIcon = Display::url(
                            $previewImage,
                            $urlPreviewLink,
                            [
                                'target' => '_blank',
                                'class' => 'btn btn-default',
                                'data-title' => $arrLP[$i]['title'],
                                'title' => $arrLP[$i]['title'],
                            ]
                        );
                        break;
                    case TOOL_THREAD:
                    case TOOL_FORUM:
                    case TOOL_QUIZ:
                    case TOOL_STUDENTPUBLICATION:
                    case TOOL_LP_FINAL_ITEM:
                    case TOOL_LINK:
                        $class = 'btn btn-default';
                        $target = '_blank';
                        $link = self::rl_get_resource_link_for_learnpath(
                            $this->course_int_id,
                            $this->lp_id,
                            $arrLP[$i]['id'],
                            0
                        );
                        $previewIcon = Display::url(
                            $previewImage,
                            $link,
                            [
                                'class' => $class,
                                'data-title' => $arrLP[$i]['title'],
                                'title' => $arrLP[$i]['title'],
                                'target' => $target,
                            ]
                        );
                        break;
                    default:
                        $previewIcon = Display::url(
                            $previewImage,
                            $url.'&action=view_item',
                            ['class' => 'btn btn-default', 'target' => '_blank']
                        );
                        break;
                }

                if ($arrLP[$i]['item_type'] != 'dir') {
                    $prerequisities_icon = Display::url(
                        Display::return_icon(
                            'accept.png',
                            get_lang('LearnpathPrerequisites'),
                            [],
                            ICON_SIZE_TINY
                        ),
                        $url.'&action=edit_item_prereq',
                        ['class' => 'btn btn-default']
                    );
                    if ($arrLP[$i]['item_type'] != 'final_item') {
                        $move_item_icon = Display::url(
                            Display::return_icon(
                                'move.png',
                                get_lang('Move'),
                                [],
                                ICON_SIZE_TINY
                            ),
                            $url.'&action=move_item',
                            ['class' => 'btn btn-default']
                        );
                    }
                    $audio_icon = Display::url(
                        Display::return_icon(
                            'audio.png',
                            get_lang('UplUpload'),
                            [],
                            ICON_SIZE_TINY
                        ),
                        $url.'&action=add_audio',
                        ['class' => 'btn btn-default']
                    );
                }
            }
            if ($update_audio != 'true') {
                $row = $move_icon.' '.$icon.
                    Display::span($title_cut).
                    Display::tag(
                        'div',
                        "<div class=\"btn-group btn-group-xs\">
                                    $previewIcon
                                    $audio
                                    $edit_icon
                                    $pluginCalendarIcon
                                    $forumIcon
                                    $prerequisities_icon
                                    $move_item_icon
                                    $audio_icon
                                    $orderIcons
                                    $delete_icon
                                </div>",
                        ['class' => 'btn-toolbar button_actions']
                    );
            } else {
                $row =
                    Display::span($title.$icon).
                    Display::span($audio, ['class' => 'button_actions']);
            }

            $default_data[$arrLP[$i]['id']] = $row;
            $default_content[$arrLP[$i]['id']] = $arrLP[$i];

            if (empty($parent_id)) {
                $elements[$arrLP[$i]['id']]['data'] = $row;
                $elements[$arrLP[$i]['id']]['type'] = $arrLP[$i]['item_type'];
            } else {
                $parent_arrays = [];
                if ($arrLP[$i]['depth'] > 1) {
                    // Getting list of parents
                    for ($j = 0; $j < $arrLP[$i]['depth']; $j++) {
                        foreach ($arrLP as $item) {
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
                    foreach ($parent_arrays as $item) {
                        if ($x != count($parent_arrays) - 1) {
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

        return [
            'elements' => $elements,
            'default_data' => $default_data,
            'default_content' => $default_content,
            'return_audio' => $return_audio,
        ];
    }

    /**
     * @param string $updateAudio true/false strings
     *
     * @return string
     */
    public function returnLpItemList($updateAudio)
    {
        $result = $this->processBuildMenuElements($updateAudio);

        $html = self::print_recursive(
            $result['elements'],
            $result['default_data'],
            $result['default_content']
        );

        if (!empty($html)) {
            $html .= Display::return_message(get_lang('DragAndDropAnElementHere'));
        }

        return $html;
    }

    /**
     * @param string $update_audio
     * @param bool   $drop_element_here
     *
     * @return string
     */
    public function return_new_tree($update_audio = 'false', $drop_element_here = false)
    {
        $result = $this->processBuildMenuElements($update_audio);

        $list = '<ul id="lp_item_list">';
        $tree = $this->print_recursive(
            $result['elements'],
            $result['default_data'],
            $result['default_content']
        );

        if (!empty($tree)) {
            $list .= $tree;
        } else {
            if ($drop_element_here) {
                $list .= Display::return_message(get_lang('DragAndDropAnElementHere'));
            }
        }
        $list .= '</ul>';

        $return = Display::panelCollapse(
            $this->getNameNoTags(),
            $list,
            'scorm-list',
            null,
            'scorm-list-accordion',
            'scorm-list-collapse'
        );

        if ($update_audio === 'true') {
            $return = $result['return_audio'];
        }

        return $return;
    }

    /**
     * @param array $elements
     * @param array $default_data
     * @param array $default_content
     *
     * @return string
     */
    public function print_recursive($elements, $default_data, $default_content)
    {
        $return = '';
        foreach ($elements as $key => $item) {
            if (TOOL_LP_FINAL_ITEM === $item['type']) {
                $key = 'final_item';
            }
            if (isset($item['load_data']) || empty($item['data'])) {
                $item['data'] = $default_data[$item['load_data']];
                $item['type'] = $default_content[$item['load_data']]['item_type'];
            }
            $sub_list = '';
            if (isset($item['type']) && $item['type'] === 'dir') {
                // empty value
                $sub_list = Display::tag('li', '', ['class' => 'sub_item empty']);
            }
            if (empty($item['children'])) {
                $sub_list = Display::tag('ul', $sub_list, ['id' => 'UL_'.$key, 'class' => 'record li_container']);
                $active = null;
                if (isset($_REQUEST['id']) && $key == $_REQUEST['id']) {
                    $active = 'active';
                }
                $return .= Display::tag(
                    'li',
                    Display::div($item['data'], ['class' => "item_data $active"]).$sub_list,
                    ['id' => $key, 'class' => 'record li_container']
                );
            } else {
                // Sections
                $data = '';
                if (isset($item['children'])) {
                    $data = self::print_recursive($item['children'], $default_data, $default_content);
                }
                $sub_list = Display::tag('ul', $sub_list.$data, ['id' => 'UL_'.$key, 'class' => 'record li_container']);
                $return .= Display::tag(
                    'li',
                    Display::div($item['data'], ['class' => 'item_data']).$sub_list,
                    ['id' => $key, 'class' => 'record li_container']
                );
            }
        }

        return $return;
    }

    /**
     * This function builds the action menu.
     *
     * @param bool   $returnString           Optional
     * @param bool   $showRequirementButtons Optional. Allow show the requirements button
     * @param bool   $isConfigPage           Optional. If is the config page, show the edit button
     * @param bool   $allowExpand            Optional. Allow show the expand/contract button
     * @param string $action
     * @param array  $extraField
     *
     * @return string
     */
    public function build_action_menu(
        $returnString = false,
        $showRequirementButtons = true,
        $isConfigPage = false,
        $allowExpand = true,
        $action = '',
        $extraField = []
    ) {
        $actionsRight = '';
        $lpId = $this->lp_id;
        if (!isset($extraField['backTo']) && empty($extraField['backTo'])) {
            $back = Display::url(
                Display::return_icon(
                    'back.png',
                    get_lang('ReturnToLearningPaths'),
                    '',
                    ICON_SIZE_MEDIUM
                ),
                'lp_controller.php?'.api_get_cidreq()
            );
        } else {
            $back = Display::url(
                Display::return_icon(
                    'back.png',
                    get_lang('Back'),
                    '',
                    ICON_SIZE_MEDIUM
                ),
                $extraField['backTo']
            );
        }

        $actionsLeft = $back;
        $actionsLeft .= Display::url(
            Display::return_icon(
                'preview_view.png',
                get_lang('Preview'),
                '',
                ICON_SIZE_MEDIUM
            ),
            'lp_controller.php?'.api_get_cidreq().'&'.http_build_query([
                'action' => 'view',
                'lp_id' => $lpId,
                'isStudentView' => 'true',
            ])
        );

        $actionsLeft .= Display::url(
            Display::return_icon(
                'upload_audio.png',
                get_lang('UpdateAllAudioFragments'),
                '',
                ICON_SIZE_MEDIUM
            ),
            'lp_controller.php?'.api_get_cidreq().'&'.http_build_query([
                'action' => 'admin_view',
                'lp_id' => $lpId,
                'updateaudio' => 'true',
            ])
        );

        $subscriptionSettings = self::getSubscriptionSettings();
        $request = api_request_uri();
        if (strpos($request, 'edit') === false) {
            $actionsLeft .= Display::url(
                Display::return_icon(
                    'settings.png',
                    get_lang('CourseSettings'),
                    '',
                    ICON_SIZE_MEDIUM
                ),
                'lp_controller.php?'.api_get_cidreq().'&'.http_build_query([
                    'action' => 'edit',
                    'lp_id' => $lpId,
                ])
            );
        }

        if ((strpos($request, 'build') === false &&
            strpos($request, 'add_item') === false) ||
            in_array($action, ['add_audio'])
        ) {
            $actionsLeft .= Display::url(
                Display::return_icon(
                    'edit.png',
                    get_lang('Edit'),
                    '',
                    ICON_SIZE_MEDIUM
                ),
                'lp_controller.php?'.http_build_query([
                    'action' => 'build',
                    'lp_id' => $lpId,
                ]).'&'.api_get_cidreq()
            );
        }

        if (strpos(api_get_self(), 'lp_subscribe_users.php') === false) {
            if ($this->subscribeUsers == 1 &&
                $subscriptionSettings['allow_add_users_to_lp']) {
                $actionsLeft .= Display::url(
                    Display::return_icon(
                        'user.png',
                        get_lang('SubscribeUsersToLp'),
                        '',
                        ICON_SIZE_MEDIUM
                    ),
                    api_get_path(WEB_CODE_PATH)."lp/lp_subscribe_users.php?lp_id=".$lpId."&".api_get_cidreq()
                );
            }
        }

        if ($allowExpand) {
            $actionsLeft .= Display::url(
                Display::return_icon(
                    'expand.png',
                    get_lang('Expand'),
                    ['id' => 'expand'],
                    ICON_SIZE_MEDIUM
                ).
                Display::return_icon(
                    'contract.png',
                    get_lang('Collapse'),
                    ['id' => 'contract', 'class' => 'hide'],
                    ICON_SIZE_MEDIUM
                ),
                '#',
                ['role' => 'button', 'id' => 'hide_bar_template']
            );
        }

        if ($showRequirementButtons) {
            $buttons = [
                [
                    'title' => get_lang('SetPrerequisiteForEachItem'),
                    'href' => 'lp_controller.php?'.api_get_cidreq().'&'.http_build_query([
                        'action' => 'set_previous_step_as_prerequisite',
                        'lp_id' => $lpId,
                    ]),
                ],
                [
                    'title' => get_lang('ClearAllPrerequisites'),
                    'href' => 'lp_controller.php?'.api_get_cidreq().'&'.http_build_query([
                        'action' => 'clear_prerequisites',
                        'lp_id' => $lpId,
                    ]),
                ],
            ];
            $actionsRight = Display::groupButtonWithDropDown(
                get_lang('PrerequisitesOptions'),
                $buttons,
                true
            );
        }

        if (api_is_platform_admin() && isset($extraField['authorlp'])) {
            $actionsLeft .= Display::url(
                Display::return_icon(
                    'add-groups.png',
                    get_lang('Author'),
                    '',
                    ICON_SIZE_MEDIUM
                ),
                'lp_controller.php?'.api_get_cidreq().'&'.http_build_query([
                    'action' => 'author_view',
                    'lp_id' => $lpId,
                ])
            );
        }

        $toolbar = Display::toolbarAction(
            'actions-lp-controller',
            [$actionsLeft, $actionsRight]
        );

        if ($returnString) {
            return $toolbar;
        }

        echo $toolbar;
    }

    /**
     * Creates the default learning path folder.
     *
     * @param array $course
     * @param int   $creatorId
     *
     * @return bool
     */
    public static function generate_learning_path_folder($course, $creatorId = 0)
    {
        // Creating learning_path folder
        $dir = '/learning_path';
        $filepath = api_get_path(SYS_COURSE_PATH).$course['path'].'/document';
        $creatorId = empty($creatorId) ? api_get_user_id() : $creatorId;

        $folder = false;
        if (!is_dir($filepath.'/'.$dir)) {
            $folderData = create_unexisting_directory(
                $course,
                $creatorId,
                0,
                null,
                0,
                $filepath,
                $dir,
                get_lang('LearningPaths'),
                0
            );
            if (!empty($folderData)) {
                $folder = true;
            }
        } else {
            $folder = true;
        }

        return $folder;
    }

    /**
     * @param array  $course
     * @param string $lp_name
     * @param int    $creatorId
     *
     * @return array
     */
    public function generate_lp_folder($course, $lp_name = '', $creatorId = 0)
    {
        $filepath = '';
        $dir = '/learning_path/';

        if (empty($lp_name)) {
            $lp_name = $this->name;
        }
        $creatorId = empty($creatorId) ? api_get_user_id() : $creatorId;
        $folder = self::generate_learning_path_folder($course, $creatorId);

        // Limits title size
        $title = api_substr(api_replace_dangerous_char($lp_name), 0, 80);
        $dir = $dir.$title;

        // Creating LP folder
        $documentId = null;
        if ($folder) {
            $filepath = api_get_path(SYS_COURSE_PATH).$course['path'].'/document';
            if (!is_dir($filepath.'/'.$dir)) {
                $folderData = create_unexisting_directory(
                    $course,
                    $creatorId,
                    0,
                    0,
                    0,
                    $filepath,
                    $dir,
                    $lp_name
                );
                if (!empty($folderData)) {
                    $folder = true;
                }

                $documentId = $folderData['id'];
            } else {
                $folder = true;
            }
            $dir = $dir.'/';
            if ($folder) {
                $filepath = api_get_path(SYS_COURSE_PATH).$course['path'].'/document'.$dir;
            }
        }

        if (empty($documentId)) {
            $dir = api_remove_trailing_slash($dir);
            $documentId = DocumentManager::get_document_id($course, $dir, 0);
        }

        $array = [
            'dir' => $dir,
            'filepath' => $filepath,
            'folder' => $folder,
            'id' => $documentId,
        ];

        return $array;
    }

    /**
     * Create a new document //still needs some finetuning.
     *
     * @param array  $courseInfo
     * @param string $content
     * @param string $title
     * @param string $extension
     * @param int    $parentId
     * @param int    $creatorId  creator id
     *
     * @return int
     */
    public function create_document(
        $courseInfo,
        $content = '',
        $title = '',
        $extension = 'html',
        $parentId = 0,
        $creatorId = 0
    ) {
        if (!empty($courseInfo)) {
            $course_id = $courseInfo['real_id'];
        } else {
            $course_id = api_get_course_int_id();
        }

        $creatorId = empty($creatorId) ? api_get_user_id() : $creatorId;
        $sessionId = api_get_session_id();

        // Generates folder
        $result = $this->generate_lp_folder($courseInfo);
        $dir = $result['dir'];

        if (empty($parentId) || $parentId == '/') {
            $postDir = isset($_POST['dir']) ? $_POST['dir'] : $dir;
            $dir = isset($_GET['dir']) ? $_GET['dir'] : $postDir; // Please, do not modify this dirname formatting.

            if ($parentId === '/') {
                $dir = '/';
            }

            // Please, do not modify this dirname formatting.
            if (strstr($dir, '..')) {
                $dir = '/';
            }

            if (!empty($dir[0]) && $dir[0] == '.') {
                $dir = substr($dir, 1);
            }
            if (!empty($dir[0]) && $dir[0] != '/') {
                $dir = '/'.$dir;
            }
            if (isset($dir[strlen($dir) - 1]) && $dir[strlen($dir) - 1] != '/') {
                $dir .= '/';
            }
        } else {
            $parentInfo = DocumentManager::get_document_data_by_id(
                $parentId,
                $courseInfo['code']
            );
            if (!empty($parentInfo)) {
                $dir = $parentInfo['path'].'/';
            }
        }

        $filepath = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document/'.$dir;
        if (!is_dir($filepath)) {
            $dir = '/';
            $filepath = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document/'.$dir;
        }

        // stripslashes() before calling api_replace_dangerous_char() because $_POST['title']
        // is already escaped twice when it gets here.
        $originalTitle = !empty($title) ? $title : $_POST['title'];
        if (!empty($title)) {
            $title = api_replace_dangerous_char(stripslashes($title));
        } else {
            $title = api_replace_dangerous_char(stripslashes($_POST['title']));
        }

        $title = disable_dangerous_file($title);
        $filename = $title;
        $content = !empty($content) ? $content : $_POST['content_lp'];
        $tmp_filename = $filename;

        $i = 0;
        while (file_exists($filepath.$tmp_filename.'.'.$extension)) {
            $tmp_filename = $filename.'_'.++$i;
        }

        $filename = $tmp_filename.'.'.$extension;
        if ($extension == 'html') {
            $content = stripslashes($content);
            $content = str_replace(
                api_get_path(WEB_COURSE_PATH),
                api_get_path(REL_PATH).'courses/',
                $content
            );

            // Change the path of mp3 to absolute.
            // The first regexp deals with :// urls.
            $content = preg_replace(
                "|(flashvars=\"file=)([^:/]+)/|",
                "$1".api_get_path(
                    REL_COURSE_PATH
                ).$courseInfo['path'].'/document/',
                $content
            );
            // The second regexp deals with audio/ urls.
            $content = preg_replace(
                "|(flashvars=\"file=)([^/]+)/|",
                "$1".api_get_path(
                    REL_COURSE_PATH
                ).$courseInfo['path'].'/document/$2/',
                $content
            );
            // For flv player: To prevent edition problem with firefox,
            // we have to use a strange tip (don't blame me please).
            $content = str_replace(
                '</body>',
                '<style type="text/css">body{}</style></body>',
                $content
            );
        }

        if (!file_exists($filepath.$filename)) {
            if ($fp = @fopen($filepath.$filename, 'w')) {
                fputs($fp, $content);
                fclose($fp);

                $file_size = filesize($filepath.$filename);
                $save_file_path = $dir.$filename;

                $document_id = add_document(
                    $courseInfo,
                    $save_file_path,
                    'file',
                    $file_size,
                    $tmp_filename,
                    '',
                    0, //readonly
                    true,
                    null,
                    $sessionId,
                    $creatorId
                );

                if ($document_id) {
                    api_item_property_update(
                        $courseInfo,
                        TOOL_DOCUMENT,
                        $document_id,
                        'DocumentAdded',
                        $creatorId,
                        null,
                        null,
                        null,
                        null,
                        $sessionId
                    );

                    $new_comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
                    $new_title = $originalTitle;

                    if ($new_comment || $new_title) {
                        $tbl_doc = Database::get_course_table(TABLE_DOCUMENT);
                        $ct = '';
                        if ($new_comment) {
                            $ct .= ", comment='".Database::escape_string($new_comment)."'";
                        }
                        if ($new_title) {
                            $ct .= ", title='".Database::escape_string($new_title)."' ";
                        }

                        $sql = "UPDATE ".$tbl_doc." SET ".substr($ct, 1)."
                               WHERE c_id = ".$course_id." AND id = ".$document_id;
                        Database::query($sql);
                    }
                }

                return $document_id;
            }
        }
    }

    /**
     * Edit a document based on $_POST and $_GET parameters 'dir' and 'path'.
     *
     * @param array $_course array
     */
    public function edit_document($_course)
    {
        $course_id = api_get_course_int_id();
        $urlAppend = api_get_configuration_value('url_append');
        // Please, do not modify this dirname formatting.
        $postDir = isset($_POST['dir']) ? $_POST['dir'] : '';
        $dir = isset($_GET['dir']) ? $_GET['dir'] : $postDir;

        if (strstr($dir, '..')) {
            $dir = '/';
        }

        if (isset($dir[0]) && $dir[0] == '.') {
            $dir = substr($dir, 1);
        }

        if (isset($dir[0]) && $dir[0] != '/') {
            $dir = '/'.$dir;
        }

        if (isset($dir[strlen($dir) - 1]) && $dir[strlen($dir) - 1] != '/') {
            $dir .= '/';
        }

        $filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document'.$dir;
        if (!is_dir($filepath)) {
            $filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
        }

        $table_doc = Database::get_course_table(TABLE_DOCUMENT);

        if (isset($_POST['path']) && !empty($_POST['path'])) {
            $document_id = (int) $_POST['path'];
            $documentInfo = DocumentManager::get_document_data_by_id($document_id, api_get_course_id(), false, null, true);
            if (empty($documentInfo)) {
                // Try with iid
                $table = Database::get_course_table(TABLE_DOCUMENT);
                $sql = "SELECT id, path FROM $table
                        WHERE c_id = $course_id AND iid = $document_id AND path NOT LIKE '%_DELETED_%' ";
                $res_doc = Database::query($sql);
                $row = Database::fetch_array($res_doc);
                if ($row) {
                    $document_id = $row['id'];
                    $documentPath = $row['path'];
                }
            } else {
                $documentPath = $documentInfo['path'];
            }

            $content = stripslashes($_POST['content_lp']);
            $file = $filepath.$documentPath;

            if (!file_exists($file)) {
                return false;
            }

            if ($fp = @fopen($file, 'w')) {
                $content = str_replace(
                    api_get_path(WEB_COURSE_PATH),
                    $urlAppend.api_get_path(REL_COURSE_PATH),
                    $content
                );
                // Change the path of mp3 to absolute.
                // The first regexp deals with :// urls.
                $content = preg_replace(
                    "|(flashvars=\"file=)([^:/]+)/|",
                    "$1".api_get_path(REL_COURSE_PATH).$_course['path'].'/document/',
                    $content
                );
                // The second regexp deals with audio/ urls.
                $content = preg_replace(
                    "|(flashvars=\"file=)([^:/]+)/|",
                    "$1".api_get_path(REL_COURSE_PATH).$_course['path'].'/document/$2/',
                    $content
                );
                fputs($fp, $content);
                fclose($fp);

                $sql = "UPDATE $table_doc SET
                            title='".Database::escape_string($_POST['title'])."'
                        WHERE c_id = $course_id AND id = ".$document_id;
                Database::query($sql);
            }
        }
    }

    /**
     * Displays the selected item, with a panel for manipulating the item.
     *
     * @param int    $item_id
     * @param string $msg
     * @param bool   $show_actions
     *
     * @return string
     */
    public function display_item($item_id, $msg = null, $show_actions = true)
    {
        $course_id = api_get_course_int_id();
        $return = '';
        if (is_numeric($item_id)) {
            $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
            $sql = "SELECT lp.* FROM $tbl_lp_item as lp
                    WHERE lp.iid = ".intval($item_id);
            $result = Database::query($sql);
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $_SESSION['parent_item_id'] = $row['item_type'] == 'dir' ? $item_id : 0;

                // Prevents wrong parent selection for document, see Bug#1251.
                if ($row['item_type'] != 'dir') {
                    $_SESSION['parent_item_id'] = $row['parent_item_id'];
                }

                if ($show_actions) {
                    $return .= $this->display_manipulate($item_id, $row['item_type']);
                }
                $return .= '<div style="padding:10px;">';

                if ($msg != '') {
                    $return .= $msg;
                }

                $return .= '<h3>'.$row['title'].'</h3>';

                switch ($row['item_type']) {
                    case TOOL_THREAD:
                        $link = $this->rl_get_resource_link_for_learnpath(
                            $course_id,
                            $row['lp_id'],
                            $item_id,
                            0
                        );
                        $return .= Display::url(
                            get_lang('GoToThread'),
                            $link,
                            ['class' => 'btn btn-primary']
                        );
                        break;
                    case TOOL_FORUM:
                        $return .= Display::url(
                            get_lang('GoToForum'),
                            api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.api_get_cidreq().'&forum='.$row['path'],
                            ['class' => 'btn btn-primary']
                        );
                        break;
                    case TOOL_QUIZ:
                        if (!empty($row['path'])) {
                            $exercise = new Exercise();
                            $exercise->read($row['path']);
                            $return .= $exercise->description.'<br />';
                            $return .= Display::url(
                                get_lang('GoToExercise'),
                                api_get_path(WEB_CODE_PATH).'exercise/overview.php?'.api_get_cidreq().'&exerciseId='.$exercise->id,
                                ['class' => 'btn btn-primary']
                            );
                        }
                        break;
                    case TOOL_LP_FINAL_ITEM:
                        $return .= $this->getSavedFinalItem();
                        break;
                    case TOOL_DOCUMENT:
                    case TOOL_READOUT_TEXT:
                        $tbl_doc = Database::get_course_table(TABLE_DOCUMENT);
                        $sql_doc = "SELECT path FROM $tbl_doc
                                    WHERE c_id = $course_id AND iid = ".intval($row['path']);
                        $result = Database::query($sql_doc);
                        $path_file = Database::result($result, 0, 0);
                        $path_parts = pathinfo($path_file);
                        // TODO: Correct the following naive comparisons.
                        if (in_array($path_parts['extension'], [
                            'html',
                            'txt',
                            'png',
                            'jpg',
                            'JPG',
                            'jpeg',
                            'JPEG',
                            'gif',
                            'swf',
                            'pdf',
                            'htm',
                        ])) {
                            $return .= $this->display_document($row['path'], true, true);
                        }
                        break;
                    case TOOL_HOTPOTATOES:
                        $return .= $this->display_document($row['path'], false, true);
                        break;
                }
                $return .= '</div>';
            }
        }

        return $return;
    }

    /**
     * Shows the needed forms for editing a specific item.
     *
     * @param int $item_id
     *
     * @throws Exception
     * @throws HTML_QuickForm_Error
     *
     * @return string
     */
    public function display_edit_item($item_id, $excludeExtraFields = [])
    {
        $course_id = api_get_course_int_id();
        $return = '';
        $item_id = (int) $item_id;

        if (empty($item_id)) {
            return '';
        }

        $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $sql = "SELECT * FROM $tbl_lp_item
                WHERE iid = ".$item_id;
        $res = Database::query($sql);
        $row = Database::fetch_array($res);
        switch ($row['item_type']) {
            case 'dir':
            case 'asset':
            case 'sco':
            if (isset($_GET['view']) && $_GET['view'] == 'build') {
                $return .= $this->display_manipulate($item_id, $row['item_type']);
                $return .= $this->display_item_form(
                        $row['item_type'],
                        get_lang('EditCurrentChapter').' :',
                        'edit',
                        $item_id,
                        $row
                    );
            } else {
                $return .= $this->display_item_form(
                        $row['item_type'],
                        get_lang('EditCurrentChapter').' :',
                        'edit_item',
                        $item_id,
                        $row
                    );
            }
                break;
            case TOOL_DOCUMENT:
            case TOOL_READOUT_TEXT:
                $tbl_doc = Database::get_course_table(TABLE_DOCUMENT);
                $sql = "SELECT lp.*, doc.path as dir
                        FROM $tbl_lp_item as lp
                        LEFT JOIN $tbl_doc as doc
                        ON (doc.iid = lp.path AND lp.c_id = doc.c_id)
                        WHERE
                            doc.c_id = $course_id AND
                            lp.iid = ".$item_id;
                $res_step = Database::query($sql);
                $row_step = Database::fetch_array($res_step, 'ASSOC');
                $return .= $this->display_manipulate($item_id, $row['item_type']);

                if ($row['item_type'] === TOOL_DOCUMENT) {
                    $return .= $this->display_document_form(
                        'edit',
                        $item_id,
                        $row_step,
                        null,
                        $excludeExtraFields
                    );
                }

                if ($row['item_type'] === TOOL_READOUT_TEXT) {
                    $return .= $this->displayFrmReadOutText('edit', $item_id, $row_step);
                }
                break;
            case TOOL_LINK:
                $linkId = (int) $row['path'];
                if (!empty($linkId)) {
                    $table = Database::get_course_table(TABLE_LINK);
                    $sql = 'SELECT url FROM '.$table.'
                            WHERE c_id = '.$course_id.' AND iid = '.$linkId;
                    $res_link = Database::query($sql);
                    $row_link = Database::fetch_array($res_link);
                    if (empty($row_link)) {
                        // Try with id
                        $sql = 'SELECT url FROM '.$table.'
                                WHERE c_id = '.$course_id.' AND id = '.$linkId;
                        $res_link = Database::query($sql);
                        $row_link = Database::fetch_array($res_link);
                    }

                    if (is_array($row_link)) {
                        $row['url'] = $row_link['url'];
                    }
                }
                $return .= $this->display_manipulate($item_id, $row['item_type']);
                $return .= $this->display_link_form('edit', $item_id, $row, null, $excludeExtraFields);
                break;
            case TOOL_LP_FINAL_ITEM:
                Session::write('finalItem', true);
                $tbl_doc = Database::get_course_table(TABLE_DOCUMENT);
                $sql = "SELECT lp.*, doc.path as dir
                        FROM $tbl_lp_item as lp
                        LEFT JOIN $tbl_doc as doc
                        ON (doc.iid = lp.path AND lp.c_id = doc.c_id)
                        WHERE
                            doc.c_id = $course_id AND
                            lp.iid = ".$item_id;
                $res_step = Database::query($sql);
                $row_step = Database::fetch_array($res_step, 'ASSOC');
                $return .= $this->display_manipulate($item_id, $row['item_type']);
                $return .= $this->display_document_form(
                    'edit',
                    $item_id,
                    $row_step,
                    null,
                    $excludeExtraFields
                );
                break;
            case TOOL_QUIZ:
                $return .= $this->display_manipulate($item_id, $row['item_type']);
                $return .= $this->display_quiz_form('edit', $item_id, $row, $excludeExtraFields);
                break;
            case TOOL_HOTPOTATOES:
                $return .= $this->display_manipulate($item_id, $row['item_type']);
                $return .= $this->display_hotpotatoes_form('edit', $item_id, $row);
                break;
            case TOOL_STUDENTPUBLICATION:
                $return .= $this->display_manipulate($item_id, $row['item_type']);
                $return .= $this->display_student_publication_form('edit', $item_id, $row, null, $excludeExtraFields);
                break;
            case TOOL_FORUM:
                $return .= $this->display_manipulate($item_id, $row['item_type']);
                $return .= $this->display_forum_form('edit', $item_id, $row, $excludeExtraFields);
                break;
            case TOOL_THREAD:
                $return .= $this->display_manipulate($item_id, $row['item_type']);
                $return .= $this->display_thread_form('edit', $item_id, $row);
                break;
            case TOOL_SURVEY:
                $return .= $this->display_manipulate($item_id, $row['item_type']);
                $return .= $this->displaySurveyForm('edit', $item_id, $row);
                break;
        }

        return $return;
    }

    /**
     * Function that displays a list with al the resources that
     * could be added to the learning path.
     *
     * @throws Exception
     * @throws HTML_QuickForm_Error
     *
     * @return bool
     */
    public function display_resources()
    {
        $course_code = api_get_course_id();

        // Get all the docs.
        $documents = $this->get_documents(true);

        // Get all the exercises.
        $exercises = $this->get_exercises();

        // Get all the links.
        $links = $this->get_links();

        // Get all the student publications.
        $works = $this->get_student_publications();

        // Get all the forums.
        $forums = $this->get_forums(null, $course_code);

        $dir = $this->display_item_form('dir', get_lang('EnterDataNewChapter'), 'add_item');

        // Get the final item form (see BT#11048) .
        $finish = $this->getFinalItemForm();

        $headers = [
            Display::return_icon('folder_document.png', get_lang('Documents'), [], ICON_SIZE_BIG),
            Display::return_icon('quiz.png', get_lang('Quiz'), [], ICON_SIZE_BIG),
            Display::return_icon('links.png', get_lang('Links'), [], ICON_SIZE_BIG),
            Display::return_icon('works.png', get_lang('Works'), [], ICON_SIZE_BIG),
            Display::return_icon('forum.png', get_lang('Forums'), [], ICON_SIZE_BIG),
            Display::return_icon('add_learnpath_section.png', get_lang('NewChapter'), [], ICON_SIZE_BIG),
        ];

        $items = [
            $documents,
            $exercises,
            $links,
            $works,
            $forums,
            $dir,
        ];

        $allowSurveyTool = api_get_configuration_value('allow_survey_tool_in_lp');
        if ($allowSurveyTool) {
            // Get all the surveys
            $surveys = $this->getSurveys();
            $items[] = $surveys;
            $headers[] = Display::return_icon('survey.png', get_lang('Surveys'), [], ICON_SIZE_BIG);
        }

        $xApiPlugin = XApiPlugin::create();
        if ($xApiPlugin->isEnabled()) {
            $headers[] = Display::return_icon(
                'import_scorm.png',
                get_lang($xApiPlugin->get_lang('ToolTinCan')),
                [],
                ICON_SIZE_BIG
            );
            $items[] = $xApiPlugin->getLpResourceBlock($this->lp_id);
        }

        $h5pImportPlugin = H5pImportPlugin::create();
        if ($h5pImportPlugin->isEnabled()) {
            $headers[] = Display::return_icon(
                'plugin_h5p_import_upload.png',
                $h5pImportPlugin->get_lang('plugin_title'),
                [],
                ICON_SIZE_BIG
            );
            $items[] = $h5pImportPlugin->getLpResourceBlock($this->lp_id);
        }

        $headers[] = Display::return_icon('flag_checkered.png', get_lang('Certificate'), [], ICON_SIZE_BIG);
        $items[] = $finish;

        echo Display::return_message(get_lang('ClickOnTheLearnerViewToSeeYourLearningPath'), 'normal');

        $selected = isset($_REQUEST['lp_build_selected']) ? (int) $_REQUEST['lp_build_selected'] : 0;

        echo Display::tabs(
            $headers,
            $items,
            'resource_tab',
            [],
            [],
            $selected
        );

        return true;
    }

    /**
     * Returns the extension of a document.
     *
     * @param string $filename
     *
     * @return string Extension (part after the last dot)
     */
    public function get_extension($filename)
    {
        $explode = explode('.', $filename);

        return $explode[count($explode) - 1];
    }

    /**
     * Displays a document by id.
     *
     * @param int  $id
     * @param bool $show_title
     * @param bool $iframe
     * @param bool $edit_link
     *
     * @return string
     */
    public function display_document($id, $show_title = false, $iframe = true, $edit_link = false)
    {
        $_course = api_get_course_info();
        $course_id = api_get_course_int_id();
        $id = (int) $id;
        $return = '';
        $table = Database::get_course_table(TABLE_DOCUMENT);
        $sql_doc = "SELECT * FROM $table
                    WHERE c_id = $course_id AND iid = $id";
        $res_doc = Database::query($sql_doc);
        $row_doc = Database::fetch_array($res_doc);

        // TODO: Add a path filter.
        if ($iframe) {
            $return .= '<iframe id="learnpath_preview_frame" frameborder="0" height="400" width="100%" scrolling="auto" src="'.api_get_path(WEB_COURSE_PATH).$_course['path'].'/document'.str_replace('%2F', '/', urlencode($row_doc['path'])).'?'.api_get_cidreq().'"></iframe>';
        } else {
            $return .= file_get_contents(api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/'.$row_doc['path']);
        }

        return $return;
    }

    /**
     * Return HTML form to add/edit a quiz.
     *
     * @param string $action     Action (add/edit)
     * @param int    $id         Item ID if already exists
     * @param mixed  $extra_info Extra information (quiz ID if integer)
     *
     * @throws Exception
     *
     * @return string HTML form
     */
    public function display_quiz_form(
        $action = 'add',
        $id = 0,
        $extra_info = '',
        $excludeExtraFields = []
    ) {
        $course_id = api_get_course_int_id();
        $id = (int) $id;
        $tbl_quiz = Database::get_course_table(TABLE_QUIZ_TEST);

        if ($id != 0 && is_array($extra_info)) {
            $item_title = $extra_info['title'];
            $item_description = $extra_info['description'];
        } elseif (is_numeric($extra_info)) {
            $sql = "SELECT title, description
                    FROM $tbl_quiz
                    WHERE iid = $extra_info";

            $result = Database::query($sql);
            $row = Database::fetch_array($result);
            $item_title = $row['title'];
            $item_description = $row['description'];
        } else {
            $item_title = '';
            $item_description = '';
        }
        $item_title = Security::remove_XSS($item_title);
        $item_description = Security::remove_XSS($item_description);

        $parent = 0;
        if ($id != 0 && is_array($extra_info)) {
            $parent = $extra_info['parent_item_id'];
        }

        $arrLP = $this->getItemsForForm();
        $this->tree_array($arrLP);
        $arrLP = isset($this->arrMenu) ? $this->arrMenu : [];
        unset($this->arrMenu);

        $form = new FormValidator(
            'quiz_form',
            'POST',
            $this->getCurrentBuildingModeURL()
        );
        $defaults = [];

        if ($action === 'add') {
            $legend = get_lang('CreateTheExercise');
        } elseif ($action === 'move') {
            $legend = get_lang('MoveTheCurrentExercise');
        } else {
            $legend = get_lang('EditCurrentExecice');
        }

        if (isset($_GET['edit']) && $_GET['edit'] == 'true') {
            $legend .= Display::return_message(get_lang('Warning').' ! '.get_lang('WarningEditingDocument'));
        }

        $form->addHeader($legend);

        if ($action != 'move') {
            $this->setItemTitle($form);
            $defaults['title'] = $item_title;
        }

        // Select for Parent item, root or chapter
        $selectParent = $form->addSelect(
            'parent',
            get_lang('Parent'),
            [],
            ['id' => 'idParent', 'onchange' => 'load_cbo(this.value);']
        );
        $selectParent->addOption($this->name, 0);

        $arrHide = [
            $id,
        ];
        for ($i = 0; $i < count($arrLP); $i++) {
            if ($action != 'add') {
                if (
                    ($arrLP[$i]['item_type'] == 'dir') &&
                    !in_array($arrLP[$i]['id'], $arrHide) &&
                    !in_array($arrLP[$i]['parent_item_id'], $arrHide)
                ) {
                    $selectParent->addOption(
                        $arrLP[$i]['title'],
                        $arrLP[$i]['id'],
                        ['style' => 'padding-left: '.(20 + $arrLP[$i]['depth'] * 20).'px']
                    );

                    if ($parent == $arrLP[$i]['id']) {
                        $selectParent->setSelected($arrLP[$i]['id']);
                    }
                } else {
                    $arrHide[] = $arrLP[$i]['id'];
                }
            } else {
                if ($arrLP[$i]['item_type'] == 'dir') {
                    $selectParent->addOption(
                        $arrLP[$i]['title'],
                        $arrLP[$i]['id'],
                        ['style' => 'padding-left: '.(20 + $arrLP[$i]['depth'] * 20).'px']
                    );

                    if ($parent == $arrLP[$i]['id']) {
                        $selectParent->setSelected($arrLP[$i]['id']);
                    }
                }
            }
        }

        if (is_array($arrLP)) {
            reset($arrLP);
        }

        $selectPrevious = $form->addSelect(
            'previous',
            get_lang('Position'),
            [],
            ['id' => 'previous']
        );
        $selectPrevious->addOption(get_lang('FirstPosition'), 0);

        for ($i = 0; $i < count($arrLP); $i++) {
            if ($arrLP[$i]['parent_item_id'] == $parent &&
                $arrLP[$i]['id'] != $id
            ) {
                $selectPrevious->addOption(
                    get_lang('After').' "'.$arrLP[$i]['title'].'"',
                    $arrLP[$i]['id']
                );

                if (is_array($extra_info)) {
                    if ($extra_info['previous_item_id'] == $arrLP[$i]['id']) {
                        $selectPrevious->setSelected($arrLP[$i]['id']);
                    }
                } elseif ($action == 'add') {
                    $selectPrevious->setSelected($arrLP[$i]['id']);
                }
            }
        }

        if ($action != 'move') {
            $arrHide = [];
            for ($i = 0; $i < count($arrLP); $i++) {
                if ($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dir') {
                    $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
                }
            }
        }

        if ('edit' === $action) {
            if (true !== api_get_configuration_value('lp_item_prerequisite_dates')) {
                $excludeExtraFields = array_merge($excludeExtraFields, ['start_date', 'end_date']);
            }
            $extraField = new ExtraField('lp_item');
            $extraField->addElements($form, $id, $excludeExtraFields);
        }

        if ($action === 'add') {
            $form->addButtonSave(get_lang('AddExercise'), 'submit_button');
        } else {
            $form->addButtonSave(get_lang('EditCurrentExecice'), 'submit_button');
        }

        if ($action === 'move') {
            $form->addHidden('title', $item_title);
            $form->addHidden('description', $item_description);
        }

        if (is_numeric($extra_info)) {
            $form->addHidden('path', $extra_info);
        } elseif (is_array($extra_info)) {
            $form->addHidden('path', $extra_info['path']);
        }

        $form->addHidden('type', TOOL_QUIZ);
        $form->addHidden('post_time', time());
        $this->setAuthorLpItem($form);
        $form->setDefaults($defaults);

        return '<div class="sectioncomment">'.$form->returnForm().'</div>';
    }

    /**
     * Addition of Hotpotatoes tests.
     *
     * @param string $action
     * @param int    $id         Internal ID of the item
     * @param string $extra_info
     *
     * @return string HTML structure to display the hotpotatoes addition formular
     */
    public function display_hotpotatoes_form($action = 'add', $id = 0, $extra_info = '')
    {
        $course_id = api_get_course_int_id();
        $uploadPath = DIR_HOTPOTATOES;

        if ($id != 0 && is_array($extra_info)) {
            $item_title = stripslashes($extra_info['title']);
            $item_description = stripslashes($extra_info['description']);
        } elseif (is_numeric($extra_info)) {
            $TBL_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT);

            $sql = "SELECT * FROM $TBL_DOCUMENT
                    WHERE
                        c_id = $course_id AND
                        path LIKE '".$uploadPath."/%/%htm%' AND
                        iid = ".(int) $extra_info."
                    ORDER BY iid ASC";

            $res_hot = Database::query($sql);
            $row = Database::fetch_array($res_hot);

            $item_title = $row['title'];
            $item_description = $row['description'];

            if (!empty($row['comment'])) {
                $item_title = $row['comment'];
            }
        } else {
            $item_title = '';
            $item_description = '';
        }

        $parent = 0;
        if ($id != 0 && is_array($extra_info)) {
            $parent = $extra_info['parent_item_id'];
        }

        $arrLP = $this->getItemsForForm();
        $legend = '<legend>';
        if ($action == 'add') {
            $legend .= get_lang('CreateTheExercise');
        } elseif ($action == 'move') {
            $legend .= get_lang('MoveTheCurrentExercise');
        } else {
            $legend .= get_lang('EditCurrentExecice');
        }
        if (isset($_GET['edit']) && $_GET['edit'] == 'true') {
            $legend .= Display::return_message(
                get_lang('Warning').' ! '.get_lang('WarningEditingDocument')
            );
        }
        $legend .= '</legend>';

        $return = '<form method="POST">';
        $return .= $legend;
        $return .= '<table cellpadding="0" cellspacing="0" class="lp_form">';
        $return .= '<tr>';
        $return .= '<td class="label"><label for="idParent">'.get_lang('Parent').' :</label></td>';
        $return .= '<td class="input">';
        $return .= '<select id="idParent" name="parent" onChange="javascript: load_cbo(this.value);" size="1">';
        $return .= '<option class="top" value="0">'.$this->name.'</option>';
        $arrHide = [$id];

        if (count($arrLP) > 0) {
            for ($i = 0; $i < count($arrLP); $i++) {
                if ($action != 'add') {
                    if ($arrLP[$i]['item_type'] == 'dir' &&
                        !in_array($arrLP[$i]['id'], $arrHide) &&
                        !in_array($arrLP[$i]['parent_item_id'], $arrHide)
                    ) {
                        $return .= '<option '.(($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '').'style="padding-left:'.($arrLP[$i]['depth'] * 10).'px;" value="'.$arrLP[$i]['id'].'">'.$arrLP[$i]['title'].'</option>';
                    } else {
                        $arrHide[] = $arrLP[$i]['id'];
                    }
                } else {
                    if ($arrLP[$i]['item_type'] == 'dir') {
                        $return .= '<option '.(($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '').'style="padding-left:'.($arrLP[$i]['depth'] * 10).'px;" value="'.$arrLP[$i]['id'].'">'.$arrLP[$i]['title'].'</option>';
                    }
                }
            }
            reset($arrLP);
        }

        $return .= '</select>';
        $return .= '</td>';
        $return .= '</tr>';
        $return .= '<tr>';
        $return .= '<td class="label"><label for="previous">'.get_lang('Position').' :</label></td>';
        $return .= '<td class="input">';
        $return .= '<select id="previous" name="previous" size="1">';
        $return .= '<option class="top" value="0">'.get_lang('FirstPosition').'</option>';

        for ($i = 0; $i < count($arrLP); $i++) {
            if ($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id) {
                if ($extra_info['previous_item_id'] == $arrLP[$i]['id']) {
                    $selected = 'selected="selected" ';
                } elseif ($action == 'add') {
                    $selected = 'selected="selected" ';
                } else {
                    $selected = '';
                }

                $return .= '<option '.$selected.'value="'.$arrLP[$i]['id'].'">'.
                    get_lang('After').' "'.$arrLP[$i]['title'].'"</option>';
            }
        }

        $return .= '</select>';
        $return .= '</td>';
        $return .= '</tr>';

        if ($action != 'move') {
            $return .= '<tr>';
            $return .= '<td class="label"><label for="idTitle">'.get_lang('Title').' :</label></td>';
            $return .= '<td class="input"><input id="idTitle" name="title" type="text" value="'.$item_title.'" /></td>';
            $return .= '</tr>';
            $id_prerequisite = 0;
            if (is_array($arrLP) && count($arrLP) > 0) {
                foreach ($arrLP as $key => $value) {
                    if ($value['id'] == $id) {
                        $id_prerequisite = $value['prerequisite'];
                        break;
                    }
                }

                $arrHide = [];
                for ($i = 0; $i < count($arrLP); $i++) {
                    if ($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dir') {
                        $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
                    }
                }
            }
        }

        $return .= '<tr>';
        $return .= '<td>&nbsp; </td><td><button class="save" name="submit_button" action="edit" type="submit">'.
            get_lang('SaveHotpotatoes').'</button></td>';
        $return .= '</tr>';
        $return .= '</table>';

        if ($action == 'move') {
            $return .= '<input name="title" type="hidden" value="'.$item_title.'" />';
            $return .= '<input name="description" type="hidden" value="'.$item_description.'" />';
        }

        if (is_numeric($extra_info)) {
            $return .= '<input name="path" type="hidden" value="'.$extra_info.'" />';
        } elseif (is_array($extra_info)) {
            $return .= '<input name="path" type="hidden" value="'.$extra_info['path'].'" />';
        }
        $return .= '<input name="type" type="hidden" value="'.TOOL_HOTPOTATOES.'" />';
        $return .= '<input name="post_time" type="hidden" value="'.time().'" />';
        $return .= '</form>';

        return $return;
    }

    public function displaySurveyForm(
      $action = 'add',
      $id = 0,
      $extraInfo = '',
      $excludeExtraFields = []
    ) {
        $courseId = api_get_course_int_id();
        $tblSurvey = Database::get_course_table(TABLE_SURVEY);

        $itemTitle = '';
        $itemDescription = '';

        if ($id != 0 && is_array($extraInfo)) {
            $itemTitle = stripslashes($extraInfo['title']);
        } elseif (is_numeric($extraInfo)) {
            $sql = "SELECT title, intro as comment
                    FROM $tblSurvey
                    WHERE c_id = $courseId AND survey_id = ".(int) $extraInfo;

            $result = Database::query($sql);
            $row = Database::fetch_array($result);

            $itemTitle = strip_tags($row['title']);
            $itemDescription = $row['comment'];
        }
        $parent = 0;
        if ($id != 0 && is_array($extraInfo)) {
            $parent = $extraInfo['parent_item_id'];
        }
        $arrLP = $this->getItemsForForm();
        $this->tree_array($arrLP);
        $arrLP = isset($this->arrMenu) ? $this->arrMenu : [];
        unset($this->arrMenu);

        if ($action == 'add') {
            $legend = get_lang('CreateSurvey');
        } elseif ($action == 'move') {
            $legend = get_lang('MoveTheCurrentSurvey');
        } else {
            $legend = get_lang('ModifySurveyInformation');
        }

        $form = new FormValidator(
            'survey_form',
            'POST',
            $this->getCurrentBuildingModeURL()
        );
        $defaults = [];

        $form->addHeader($legend);

        if ($action != 'move') {
            $this->setItemTitle($form);
            $defaults['title'] = $itemTitle;
        }

        $selectParent = $form->addSelect(
            'parent',
            get_lang('Parent'),
            [],
            ['id' => 'idParent', 'onchange' => 'load_cbo(this.value);', 'class' => 'learnpath_item_form']
        );
        $selectParent->addOption($this->name, 0);
        $arrHide = [
            $id,
        ];
        for ($i = 0; $i < count($arrLP); $i++) {
            if ($action != 'add') {
                if ($arrLP[$i]['item_type'] == 'dir' &&
                    !in_array($arrLP[$i]['id'], $arrHide) &&
                    !in_array($arrLP[$i]['parent_item_id'], $arrHide)
                ) {
                    $selectParent->addOption(
                        $arrLP[$i]['title'],
                        $arrLP[$i]['id'],
                        ['style' => 'padding-left: '.(20 + $arrLP[$i]['depth'] * 20).'px']
                    );

                    if ($parent == $arrLP[$i]['id']) {
                        $selectParent->setSelected($arrLP[$i]['id']);
                    }
                } else {
                    $arrHide[] = $arrLP[$i]['id'];
                }
            } else {
                if ($arrLP[$i]['item_type'] == 'dir') {
                    $selectParent->addOption(
                        $arrLP[$i]['title'],
                        $arrLP[$i]['id'],
                        ['style' => 'padding-left: '.(20 + $arrLP[$i]['depth'] * 20).'px']
                    );

                    if ($parent == $arrLP[$i]['id']) {
                        $selectParent->setSelected($arrLP[$i]['id']);
                    }
                }
            }
        }

        if (is_array($arrLP)) {
            reset($arrLP);
        }

        $selectPrevious = $form->addSelect(
            'previous',
            get_lang('Position'),
            [],
            ['id' => 'previous', 'class' => 'learnpath_item_form']
        );
        $selectPrevious->addOption(get_lang('FirstPosition'), 0);

        for ($i = 0; $i < count($arrLP); $i++) {
            if ($arrLP[$i]['parent_item_id'] == $parent &&
                $arrLP[$i]['id'] != $id
            ) {
                $selectPrevious->addOption(
                    get_lang('After').' "'.$arrLP[$i]['title'].'"',
                    $arrLP[$i]['id']
                );

                if (isset($extra_info['previous_item_id']) &&
                    $extra_info['previous_item_id'] == $arrLP[$i]['id']
                ) {
                    $selectPrevious->setSelected($arrLP[$i]['id']);
                } elseif ($action == 'add') {
                    $selectPrevious->setSelected($arrLP[$i]['id']);
                }
            }
        }

        if ($action != 'move') {
            $idPrerequisite = 0;
            if (is_array($arrLP)) {
                foreach ($arrLP as $key => $value) {
                    if ($value['id'] == $id) {
                        $idPrerequisite = $value['prerequisite'];
                        break;
                    }
                }
            }

            $arrHide = [];
            for ($i = 0; $i < count($arrLP); $i++) {
                if ($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dir') {
                    if (isset($extra_info['previous_item_id']) &&
                        $extra_info['previous_item_id'] == $arrLP[$i]['id']
                    ) {
                        $sSelectedPosition = $arrLP[$i]['id'];
                    } elseif ($action == 'add') {
                        $sSelectedPosition = 0;
                    }
                    $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
                }
            }
        }

        if ('edit' === $action) {
            if (true !== api_get_configuration_value('lp_item_prerequisite_dates')) {
                $excludeExtraFields = array_merge($excludeExtraFields, ['start_date', 'end_date']);
            }
            $extraField = new ExtraField('lp_item');
            $extraField->addElements($form, $id, $excludeExtraFields);
        }

        if ($action == 'add') {
            $form->addButtonSave(get_lang('AddSurveyToCourse'), 'submit_button');
        } else {
            $form->addButtonSave(get_lang('EditCurrentSurvey'), 'submit_button');
        }

        if ($action == 'move') {
            $form->addHidden('title', $itemTitle);
            $form->addHidden('description', $itemDescription);
        }

        if (is_numeric($extraInfo)) {
            $form->addHidden('path', $extraInfo);
        } elseif (is_array($extraInfo)) {
            $form->addHidden('path', $extraInfo['path']);
        }
        $form->addHidden('type', TOOL_SURVEY);
        $form->addHidden('post_time', time());
        $this->setAuthorLpItem($form);
        $form->setDefaults($defaults);

        return '<div class="sectioncomment">'.$form->returnForm().'</div>';
    }

    /**
     * Return the form to display the forum edit/add option.
     *
     * @param string $action
     * @param int    $id         ID of the lp_item if already exists
     * @param string $extra_info
     *
     * @throws Exception
     *
     * @return string HTML form
     */
    public function display_forum_form(
        $action = 'add',
        $id = 0,
        $extra_info = '',
        $excludeExtraFields = []
    ) {
        $course_id = api_get_course_int_id();
        $tbl_forum = Database::get_course_table(TABLE_FORUM);

        $item_title = '';
        $item_description = '';

        if ($id != 0 && is_array($extra_info)) {
            $item_title = stripslashes($extra_info['title']);
        } elseif (is_numeric($extra_info)) {
            $sql = "SELECT forum_title as title, forum_comment as comment
                    FROM $tbl_forum
                    WHERE c_id = $course_id AND forum_id = ".$extra_info;

            $result = Database::query($sql);
            $row = Database::fetch_array($result);

            $item_title = $row['title'];
            $item_description = $row['comment'];
        }
        $parent = 0;
        if ($id != 0 && is_array($extra_info)) {
            $parent = $extra_info['parent_item_id'];
        }
        $arrLP = $this->getItemsForForm();
        $this->tree_array($arrLP);
        $arrLP = isset($this->arrMenu) ? $this->arrMenu : [];
        unset($this->arrMenu);

        if ($action == 'add') {
            $legend = get_lang('CreateTheForum');
        } elseif ($action == 'move') {
            $legend = get_lang('MoveTheCurrentForum');
        } else {
            $legend = get_lang('EditCurrentForum');
        }

        $form = new FormValidator(
            'forum_form',
            'POST',
            $this->getCurrentBuildingModeURL()
        );
        $defaults = [];

        $form->addHeader($legend);

        if ($action != 'move') {
            $this->setItemTitle($form);
            $defaults['title'] = $item_title;
        }

        $selectParent = $form->addSelect(
            'parent',
            get_lang('Parent'),
            [],
            ['id' => 'idParent', 'onchange' => 'load_cbo(this.value);', 'class' => 'learnpath_item_form']
        );
        $selectParent->addOption($this->name, 0);
        $arrHide = [
            $id,
        ];
        for ($i = 0; $i < count($arrLP); $i++) {
            if ($action != 'add') {
                if ($arrLP[$i]['item_type'] == 'dir' &&
                    !in_array($arrLP[$i]['id'], $arrHide) &&
                    !in_array($arrLP[$i]['parent_item_id'], $arrHide)
                ) {
                    $selectParent->addOption(
                        $arrLP[$i]['title'],
                        $arrLP[$i]['id'],
                        ['style' => 'padding-left: '.(20 + $arrLP[$i]['depth'] * 20).'px']
                    );

                    if ($parent == $arrLP[$i]['id']) {
                        $selectParent->setSelected($arrLP[$i]['id']);
                    }
                } else {
                    $arrHide[] = $arrLP[$i]['id'];
                }
            } else {
                if ($arrLP[$i]['item_type'] == 'dir') {
                    $selectParent->addOption(
                        $arrLP[$i]['title'],
                        $arrLP[$i]['id'],
                        ['style' => 'padding-left: '.(20 + $arrLP[$i]['depth'] * 20).'px']
                    );

                    if ($parent == $arrLP[$i]['id']) {
                        $selectParent->setSelected($arrLP[$i]['id']);
                    }
                }
            }
        }

        if (is_array($arrLP)) {
            reset($arrLP);
        }

        $selectPrevious = $form->addSelect(
            'previous',
            get_lang('Position'),
            [],
            ['id' => 'previous', 'class' => 'learnpath_item_form']
        );
        $selectPrevious->addOption(get_lang('FirstPosition'), 0);

        for ($i = 0; $i < count($arrLP); $i++) {
            if ($arrLP[$i]['parent_item_id'] == $parent &&
                $arrLP[$i]['id'] != $id
            ) {
                $selectPrevious->addOption(
                    get_lang('After').' "'.$arrLP[$i]['title'].'"',
                    $arrLP[$i]['id']
                );

                if (isset($extra_info['previous_item_id']) &&
                    $extra_info['previous_item_id'] == $arrLP[$i]['id']
                ) {
                    $selectPrevious->setSelected($arrLP[$i]['id']);
                } elseif ($action == 'add') {
                    $selectPrevious->setSelected($arrLP[$i]['id']);
                }
            }
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

            $arrHide = [];
            for ($i = 0; $i < count($arrLP); $i++) {
                if ($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dir') {
                    if (isset($extra_info['previous_item_id']) &&
                        $extra_info['previous_item_id'] == $arrLP[$i]['id']
                    ) {
                        $s_selected_position = $arrLP[$i]['id'];
                    } elseif ($action == 'add') {
                        $s_selected_position = 0;
                    }
                    $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
                }
            }
        }

        if ('edit' === $action) {
            if (true !== api_get_configuration_value('lp_item_prerequisite_dates')) {
                $excludeExtraFields = array_merge($excludeExtraFields, ['start_date', 'end_date']);
            }
            $extraField = new ExtraField('lp_item');
            $extraField->addElements($form, $id, $excludeExtraFields);
        }

        if ($action == 'add') {
            $form->addButtonSave(get_lang('AddForumToCourse'), 'submit_button');
        } else {
            $form->addButtonSave(get_lang('EditCurrentForum'), 'submit_button');
        }

        if ($action == 'move') {
            $form->addHidden('title', $item_title);
            $form->addHidden('description', $item_description);
        }

        if (is_numeric($extra_info)) {
            $form->addHidden('path', $extra_info);
        } elseif (is_array($extra_info)) {
            $form->addHidden('path', $extra_info['path']);
        }
        $form->addHidden('type', TOOL_FORUM);
        $form->addHidden('post_time', time());
        $this->setAuthorLpItem($form);
        $form->setDefaults($defaults);

        return '<div class="sectioncomment">'.$form->returnForm().'</div>';
    }

    /**
     * Return HTML form to add/edit forum threads.
     *
     * @param string $action
     * @param int    $id         Item ID if already exists in learning path
     * @param string $extra_info
     *
     * @throws Exception
     *
     * @return string HTML form
     */
    public function display_thread_form($action = 'add', $id = 0, $extra_info = '')
    {
        $course_id = api_get_course_int_id();
        if (empty($course_id)) {
            return null;
        }
        $tbl_forum = Database::get_course_table(TABLE_FORUM_THREAD);

        $item_title = '';
        $item_description = '';
        if ($id != 0 && is_array($extra_info)) {
            $item_title = stripslashes($extra_info['title']);
        } elseif (is_numeric($extra_info)) {
            $sql = "SELECT thread_title as title FROM $tbl_forum
                    WHERE c_id = $course_id AND thread_id = ".$extra_info;

            $result = Database::query($sql);
            $row = Database::fetch_array($result);

            $item_title = $row['title'];
            $item_description = '';
        }

        $parent = 0;
        if ($id != 0 && is_array($extra_info)) {
            $parent = $extra_info['parent_item_id'];
        }

        $arrLP = $this->getItemsForForm();
        $this->tree_array($arrLP);
        $arrLP = isset($this->arrMenu) ? $this->arrMenu : [];
        unset($this->arrMenu);

        $form = new FormValidator(
            'thread_form',
            'POST',
            $this->getCurrentBuildingModeURL()
        );
        $defaults = [];

        if ($action == 'add') {
            $legend = get_lang('CreateTheForum');
        } elseif ($action == 'move') {
            $legend = get_lang('MoveTheCurrentForum');
        } else {
            $legend = get_lang('EditCurrentForum');
        }

        $form->addHeader($legend);
        $selectParent = $form->addSelect(
            'parent',
            get_lang('Parent'),
            [],
            ['id' => 'idParent', 'onchange' => 'load_cbo(this.value);']
        );
        $selectParent->addOption($this->name, 0);

        $arrHide = [
            $id,
        ];

        for ($i = 0; $i < count($arrLP); $i++) {
            if ($action != 'add') {
                if (
                    ($arrLP[$i]['item_type'] == 'dir') &&
                    !in_array($arrLP[$i]['id'], $arrHide) &&
                    !in_array($arrLP[$i]['parent_item_id'], $arrHide)
                ) {
                    $selectParent->addOption(
                        $arrLP[$i]['title'],
                        $arrLP[$i]['id'],
                        ['style' => 'padding-left: '.(20 + $arrLP[$i]['depth'] * 20).'px']
                    );

                    if ($parent == $arrLP[$i]['id']) {
                        $selectParent->setSelected($arrLP[$i]['id']);
                    }
                } else {
                    $arrHide[] = $arrLP[$i]['id'];
                }
            } else {
                if ($arrLP[$i]['item_type'] == 'dir') {
                    $selectParent->addOption(
                        $arrLP[$i]['title'],
                        $arrLP[$i]['id'],
                        ['style' => 'padding-left: '.(20 + $arrLP[$i]['depth'] * 20).'px']
                    );

                    if ($parent == $arrLP[$i]['id']) {
                        $selectParent->setSelected($arrLP[$i]['id']);
                    }
                }
            }
        }

        if ($arrLP != null) {
            reset($arrLP);
        }

        $selectPrevious = $form->addSelect(
            'previous',
            get_lang('Position'),
            [],
            ['id' => 'previous']
        );
        $selectPrevious->addOption(get_lang('FirstPosition'), 0);

        for ($i = 0; $i < count($arrLP); $i++) {
            if ($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id) {
                $selectPrevious->addOption(
                    get_lang('After').' "'.$arrLP[$i]['title'].'"',
                    $arrLP[$i]['id']
                );

                if ($extra_info['previous_item_id'] == $arrLP[$i]['id']) {
                    $selectPrevious->setSelected($arrLP[$i]['id']);
                } elseif ($action == 'add') {
                    $selectPrevious->setSelected($arrLP[$i]['id']);
                }
            }
        }

        if ($action != 'move') {
            $this->setItemTitle($form);
            $defaults['title'] = $item_title;

            $id_prerequisite = 0;
            if ($arrLP != null) {
                foreach ($arrLP as $key => $value) {
                    if ($value['id'] == $id) {
                        $id_prerequisite = $value['prerequisite'];
                        break;
                    }
                }
            }

            $arrHide = [];
            $s_selected_position = 0;
            for ($i = 0; $i < count($arrLP); $i++) {
                if ($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dir') {
                    if ($extra_info['previous_item_id'] == $arrLP[$i]['id']) {
                        $s_selected_position = $arrLP[$i]['id'];
                    } elseif ($action == 'add') {
                        $s_selected_position = 0;
                    }
                    $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
                }
            }

            $selectPrerequisites = $form->addSelect(
                'prerequisites',
                get_lang('LearnpathPrerequisites'),
                [],
                ['id' => 'prerequisites']
            );
            $selectPrerequisites->addOption(get_lang('NoPrerequisites'), 0);

            foreach ($arrHide as $key => $value) {
                $selectPrerequisites->addOption($value['value'], $key);

                if ($key == $s_selected_position && $action == 'add') {
                    $selectPrerequisites->setSelected($key);
                } elseif ($key == $id_prerequisite && $action == 'edit') {
                    $selectPrerequisites->setSelected($key);
                }
            }
        }

        if ('edit' === $action) {
            $excludeExtraFields = [];
            if (true !== api_get_configuration_value('lp_item_prerequisite_dates')) {
                $excludeExtraFields = ['start_date', 'end_date'];
            }
            $extraField = new ExtraField('lp_item');
            $extraField->addElements($form, $id, $excludeExtraFields);
        }

        $form->addButtonSave(get_lang('Ok'), 'submit_button');

        if ($action == 'move') {
            $form->addHidden('title', $item_title);
            $form->addHidden('description', $item_description);
        }

        if (is_numeric($extra_info)) {
            $form->addHidden('path', $extra_info);
        } elseif (is_array($extra_info)) {
            $form->addHidden('path', $extra_info['path']);
        }

        $form->addHidden('type', TOOL_THREAD);
        $form->addHidden('post_time', time());
        $this->setAuthorLpItem($form);
        $form->setDefaults($defaults);

        return $form->returnForm();
    }

    /**
     * Return the HTML form to display an item (generally a dir item).
     *
     * @param string $item_type
     * @param string $title
     * @param string $action
     * @param int    $id
     * @param string $extra_info
     *
     * @throws Exception
     * @throws HTML_QuickForm_Error
     *
     * @return string HTML form
     */
    public function display_item_form(
        $item_type,
        $title = '',
        $action = 'add_item',
        $id = 0,
        $extra_info = 'new'
    ) {
        $_course = api_get_course_info();

        global $charset;

        $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $item_title = '';
        $item_description = '';
        $item_path_fck = '';

        $parent = 0;
        $previousId = null;
        if ($id != 0 && is_array($extra_info)) {
            $item_title = $extra_info['title'];
            $item_description = $extra_info['description'];
            $item_path = api_get_path(WEB_COURSE_PATH).$_course['path'].'/scorm/'.$this->path.'/'.stripslashes($extra_info['path']);
            $item_path_fck = '/scorm/'.$this->path.'/'.stripslashes($extra_info['path']);
            $parent = $extra_info['parent_item_id'];
            $previousId = $extra_info['previous_item_id'];
        }

        if ($extra_info instanceof learnpathItem) {
            $item_title = $extra_info->get_title();
            $item_description = $extra_info->get_description();
            $path = $extra_info->get_path();
            $item_path = api_get_path(WEB_COURSE_PATH).$_course['path'].'/scorm/'.$this->path.'/'.stripslashes($path);
            $item_path_fck = '/scorm/'.$this->path.'/'.stripslashes($path);
            $parent = $extra_info->get_parent();
            $previousId = $extra_info->previous;
        }

        $id = (int) $id;
        $sql = "SELECT * FROM $tbl_lp_item
                WHERE
                    lp_id = ".$this->lp_id." AND
                    iid != $id";

        if ($item_type === 'dir') {
            $sql .= " AND parent_item_id = 0";
        }

        $result = Database::query($sql);
        $arrLP = [];
        while ($row = Database::fetch_array($result)) {
            $arrLP[] = [
                'id' => $row['iid'],
                'item_type' => $row['item_type'],
                'title' => $this->cleanItemTitle($row['title']),
                'title_raw' => $row['title'],
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
            ];
        }

        $this->tree_array($arrLP);
        $arrLP = isset($this->arrMenu) ? $this->arrMenu : [];
        unset($this->arrMenu);

        $url = api_get_self().'?'.api_get_cidreq().'&action='.$action.'&type='.$item_type.'&lp_id='.$this->lp_id;

        $form = new FormValidator('form_'.$item_type, 'POST', $url);
        $defaults['title'] = api_html_entity_decode(
            $item_title,
            ENT_QUOTES,
            $charset
        );
        $defaults['description'] = $item_description;

        $form->addHeader($title);
        $arrHide[0]['value'] = Security::remove_XSS($this->name);
        $arrHide[0]['padding'] = 20;
        $charset = api_get_system_encoding();
        for ($i = 0; $i < count($arrLP); $i++) {
            if ($action != 'add') {
                if ($arrLP[$i]['item_type'] === 'dir' && !in_array($arrLP[$i]['id'], $arrHide) &&
                    !in_array($arrLP[$i]['parent_item_id'], $arrHide)
                ) {
                    $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
                    $arrHide[$arrLP[$i]['id']]['padding'] = 20 + $arrLP[$i]['depth'] * 20;
                    if ($parent == $arrLP[$i]['id']) {
                        $s_selected_parent = $arrHide[$arrLP[$i]['id']];
                    }
                }
            } else {
                if ($arrLP[$i]['item_type'] === 'dir') {
                    $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
                    $arrHide[$arrLP[$i]['id']]['padding'] = 20 + $arrLP[$i]['depth'] * 20;
                    if ($parent == $arrLP[$i]['id']) {
                        $s_selected_parent = $arrHide[$arrLP[$i]['id']];
                    }
                }
            }
        }

        if ($action !== 'move') {
            $this->setItemTitle($form);
        } else {
            $form->addElement('hidden', 'title');
        }

        $parentSelect = $form->addElement(
            'select',
            'parent',
            get_lang('Parent'),
            '',
            [
                'id' => 'idParent',
                'onchange' => 'javascript: load_cbo(this.value);',
            ]
        );

        foreach ($arrHide as $key => $value) {
            $parentSelect->addOption(
                $value['value'],
                $key,
                'style="padding-left:'.$value['padding'].'px;"'
            );
            $lastPosition = $key;
        }

        if (!empty($s_selected_parent)) {
            $parentSelect->setSelected($s_selected_parent);
        }

        if (is_array($arrLP)) {
            reset($arrLP);
        }

        $arrHide = [];
        // POSITION
        for ($i = 0; $i < count($arrLP); $i++) {
            if ($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id &&
                $arrLP[$i]['item_type'] !== TOOL_LP_FINAL_ITEM) {
                //this is the same!
                if (isset($previousId) && $previousId == $arrLP[$i]['id']) {
                    $s_selected_position = $arrLP[$i]['id'];
                } elseif ($action === 'add') {
                    $s_selected_position = $arrLP[$i]['id'];
                }

                $arrHide[$arrLP[$i]['id']]['value'] = get_lang('After').' "'.$arrLP[$i]['title'].'"';
            }
        }

        $position = $form->addElement(
            'select',
            'previous',
            get_lang('Position'),
            '',
            ['id' => 'previous']
        );
        $padding = isset($value['padding']) ? $value['padding'] : 0;
        $position->addOption(get_lang('FirstPosition'), 0, 'style="padding-left:'.$padding.'px;"');

        $lastPosition = null;
        foreach ($arrHide as $key => $value) {
            $position->addOption($value['value'], $key, 'style="padding-left:'.$padding.'px;"');
            $lastPosition = $key;
        }

        if (!empty($s_selected_position)) {
            $position->setSelected($s_selected_position);
        }

        // When new chapter add at the end
        if ($action === 'add_item') {
            $position->setSelected($lastPosition);
        }

        if (is_array($arrLP)) {
            reset($arrLP);
        }

        $form->addButtonSave(get_lang('SaveSection'), 'submit_button');

        //fix in order to use the tab
        if ($item_type === 'dir') {
            $form->addElement('hidden', 'type', 'dir');
        }

        $extension = null;
        if (!empty($item_path)) {
            $extension = pathinfo($item_path, PATHINFO_EXTENSION);
        }

        //assets can't be modified
        //$item_type == 'asset' ||
        if (($item_type === 'sco') && ($extension === 'html' || $extension === 'htm')) {
            if ($item_type === 'sco') {
                $form->addElement(
                    'html',
                    '<script>alert("'.get_lang('WarningWhenEditingScorm').'")</script>'
                );
            }
            $renderer = $form->defaultRenderer();
            $renderer->setElementTemplate(
                '<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{label}<br />{element}',
                'content_lp'
            );

            $relative_prefix = '';
            $editor_config = [
                'ToolbarSet' => 'LearningPathDocuments',
                'Width' => '100%',
                'Height' => '500',
                'FullPage' => true,
                'CreateDocumentDir' => $relative_prefix,
                'CreateDocumentWebDir' => api_get_path(WEB_COURSE_PATH).api_get_course_path().'/scorm/',
                'BaseHref' => api_get_path(WEB_COURSE_PATH).api_get_course_path().$item_path_fck,
            ];

            $form->addElement('html_editor', 'content_lp', '', null, $editor_config);
            $content_path = api_get_path(SYS_COURSE_PATH).api_get_course_path().$item_path_fck;
            $defaults['content_lp'] = file_get_contents($content_path);
        }

        if (!empty($id)) {
            $form->addHidden('id', $id);
        }

        $form->addElement('hidden', 'type', $item_type);
        $form->addElement('hidden', 'post_time', time());
        $form->setDefaults($defaults);

        return $form->returnForm();
    }

    /**
     * @return string
     */
    public function getCurrentBuildingModeURL()
    {
        $pathItem = isset($_GET['path_item']) ? (int) $_GET['path_item'] : '';
        $action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : '';
        $id = isset($_GET['id']) ? (int) $_GET['id'] : '';
        $view = isset($_GET['view']) ? Security::remove_XSS($_GET['view']) : '';

        $currentUrl = api_get_self().'?'.api_get_cidreq().
            '&action='.$action.'&lp_id='.$this->lp_id.'&path_item='.$pathItem.'&view='.$view.'&id='.$id;

        return $currentUrl;
    }

    /**
     * Returns the form to update or create a document.
     *
     * @param string        $action     (add/edit)
     * @param int           $id         ID of the lp_item (if already exists)
     * @param mixed         $extra_info Integer if document ID, string if info ('new')
     * @param learnpathItem $item
     *
     * @return string HTML form
     */
    public function display_document_form(
        $action = 'add',
        $id = 0,
        $extra_info = 'new',
        $item = null,
        $excludeExtraFields = []
    ) {
        $course_id = api_get_course_int_id();
        $_course = api_get_course_info();
        $tbl_doc = Database::get_course_table(TABLE_DOCUMENT);

        $no_display_edit_textarea = false;
        //If action==edit document
        //We don't display the document form if it's not an editable document (html or txt file)
        if ($action === 'edit') {
            if (is_array($extra_info)) {
                $path_parts = pathinfo($extra_info['dir']);
                if ($path_parts['extension'] !== 'txt' && $path_parts['extension'] !== 'html') {
                    $no_display_edit_textarea = true;
                }
            }
        }
        $no_display_add = false;
        // If action==add an existing document
        // We don't display the document form if it's not an editable document (html or txt file).
        if ($action === 'add') {
            if (is_numeric($extra_info)) {
                $extra_info = (int) $extra_info;
                $sql_doc = "SELECT path FROM $tbl_doc
                            WHERE c_id = $course_id AND iid = ".$extra_info;
                $result = Database::query($sql_doc);
                $path_file = Database::result($result, 0, 0);
                $path_parts = pathinfo($path_file);
                if ($path_parts['extension'] != 'txt' && $path_parts['extension'] != 'html') {
                    $no_display_add = true;
                }
            }
        }

        $item_title = '';
        $item_description = '';
        if ($id != 0 && is_array($extra_info)) {
            $item_title = stripslashes($extra_info['title']);
            $item_description = stripslashes($extra_info['description']);
            if (empty($item_title)) {
                $path_parts = pathinfo($extra_info['path']);
                $item_title = stripslashes($path_parts['filename']);
            }
        } elseif (is_numeric($extra_info)) {
            $sql = "SELECT path, title FROM $tbl_doc
                    WHERE
                        c_id = ".$course_id." AND
                        iid = ".intval($extra_info);
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
            $item_title = $row['title'];
            $item_title = str_replace('_', ' ', $item_title);
            if (empty($item_title)) {
                $path_parts = pathinfo($row['path']);
                $item_title = stripslashes($path_parts['filename']);
            }
        }

        $return = '<legend>';
        $parent = 0;
        if ($id != 0 && is_array($extra_info)) {
            $parent = $extra_info['parent_item_id'];
        }

        $selectedPosition = 0;
        if (is_array($extra_info) && isset($extra_info['previous_item_id'])) {
            $selectedPosition = $extra_info['previous_item_id'];
        }

        if ($item instanceof learnpathItem) {
            $item_title = stripslashes($item->get_title());
            $item_description = stripslashes($item->get_description());
            $selectedPosition = $item->previous;
            $parent = $item->parent;
        }

        $arrLP = $this->getItemsForForm();
        $this->tree_array($arrLP);
        $arrLP = isset($this->arrMenu) ? $this->arrMenu : [];
        unset($this->arrMenu);

        if ($action === 'add') {
            $return .= get_lang('CreateTheDocument');
        } elseif ($action === 'move') {
            $return .= get_lang('MoveTheCurrentDocument');
        } else {
            $return .= get_lang('EditTheCurrentDocument');
        }
        $return .= '</legend>';

        if (isset($_GET['edit']) && $_GET['edit'] === 'true') {
            $return .= Display::return_message(
                '<strong>'.get_lang('Warning').' !</strong><br />'.get_lang('WarningEditingDocument'),
                false
            );
        }
        $form = new FormValidator(
            'form',
            'POST',
            $this->getCurrentBuildingModeURL(),
            '',
            ['enctype' => 'multipart/form-data']
        );
        $defaults['title'] = Security::remove_XSS($item_title);
        if (empty($item_title)) {
            $defaults['title'] = Security::remove_XSS($item_title);
        }
        $defaults['description'] = $item_description;
        $form->addElement('html', $return);

        if ($action !== 'move') {
            $data = $this->generate_lp_folder($_course);
            if ($action !== 'edit') {
                $folders = DocumentManager::get_all_document_folders(
                    $_course,
                    0,
                    true
                );
                DocumentManager::build_directory_selector(
                    $folders,
                    '',
                    [],
                    true,
                    $form,
                    'directory_parent_id'
                );
            }

            if (isset($data['id'])) {
                $defaults['directory_parent_id'] = $data['id'];
            }
            $this->setItemTitle($form);
        }

        $arrHide[0]['value'] = $this->name;
        $arrHide[0]['padding'] = 20;

        for ($i = 0; $i < count($arrLP); $i++) {
            if ($action !== 'add') {
                if ($arrLP[$i]['item_type'] === 'dir' &&
                    !in_array($arrLP[$i]['id'], $arrHide) &&
                    !in_array($arrLP[$i]['parent_item_id'], $arrHide)
                ) {
                    $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
                    $arrHide[$arrLP[$i]['id']]['padding'] = 20 + $arrLP[$i]['depth'] * 20;
                }
            } else {
                if ($arrLP[$i]['item_type'] == 'dir') {
                    $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
                    $arrHide[$arrLP[$i]['id']]['padding'] = 20 + $arrLP[$i]['depth'] * 20;
                }
            }
        }

        $parentSelect = $form->addSelect(
            'parent',
            get_lang('Parent'),
            [],
            [
                'id' => 'idParent',
                'onchange' => 'javascript: load_cbo(this.value);',
            ]
        );

        $my_count = 0;
        foreach ($arrHide as $key => $value) {
            if ($my_count != 0) {
                // The LP name is also the first section and is not in the same charset like the other sections.
                $value['value'] = Security::remove_XSS($value['value']);
                $parentSelect->addOption(
                    $value['value'],
                    $key,
                    'style="padding-left:'.$value['padding'].'px;"'
                );
            } else {
                $value['value'] = Security::remove_XSS($value['value']);
                $parentSelect->addOption(
                    $value['value'],
                    $key,
                    'style="padding-left:'.$value['padding'].'px;"'
                );
            }
            $my_count++;
        }

        if (!empty($id)) {
            $parentSelect->setSelected($parent);
        } else {
            $parent_item_id = Session::read('parent_item_id', 0);
            $parentSelect->setSelected($parent_item_id);
        }

        if (is_array($arrLP)) {
            reset($arrLP);
        }

        $arrHide = [];
        // POSITION
        for ($i = 0; $i < count($arrLP); $i++) {
            if (($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id) ||
                $arrLP[$i]['item_type'] == TOOL_LP_FINAL_ITEM
            ) {
                $arrHide[$arrLP[$i]['id']]['value'] = get_lang('After').' "'.$arrLP[$i]['title'].'"';
            }
        }

        $position = $form->addSelect(
            'previous',
            get_lang('Position'),
            [],
            ['id' => 'previous']
        );

        $position->addOption(get_lang('FirstPosition'), 0);
        foreach ($arrHide as $key => $value) {
            $padding = isset($value['padding']) ? $value['padding'] : 20;
            $position->addOption(
                $value['value'],
                $key,
                'style="padding-left:'.$padding.'px;"'
            );
        }
        $position->setSelected($selectedPosition);

        if (is_array($arrLP)) {
            reset($arrLP);
        }

        if (true !== api_get_configuration_value('lp_item_prerequisite_dates')) {
            $excludeExtraFields = array_merge($excludeExtraFields, ['start_date', 'end_date']);
        }
        $extraField = new ExtraField('lp_item');
        $extraField->addElements($form, $id, $excludeExtraFields);

        if ($action !== 'move') {
            $arrHide = [];
            for ($i = 0; $i < count($arrLP); $i++) {
                if ($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] !== 'dir' &&
                    $arrLP[$i]['item_type'] !== TOOL_LP_FINAL_ITEM
                ) {
                    $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
                }
            }

            if (!$no_display_add) {
                $item_type = isset($extra_info['item_type']) ? $extra_info['item_type'] : null;
                $edit = isset($_GET['edit']) ? $_GET['edit'] : null;
                if ($extra_info === 'new' || $item_type == TOOL_DOCUMENT ||
                    $item_type == TOOL_LP_FINAL_ITEM || $edit === 'true'
                ) {
                    if (isset($_POST['content'])) {
                        $content = stripslashes($_POST['content']);
                    } elseif (is_array($extra_info)) {
                        //If it's an html document or a text file
                        if (!$no_display_edit_textarea) {
                            $content = $this->display_document(
                                $extra_info['path'],
                                false,
                                false
                            );
                        }
                    } elseif (is_numeric($extra_info)) {
                        $content = $this->display_document(
                            $extra_info,
                            false,
                            false
                        );
                    } else {
                        $content = '';
                    }

                    if (!$no_display_edit_textarea) {
                        // We need to calculate here some specific settings for the online editor.
                        // The calculated settings work for documents in the Documents tool
                        // (on the root or in subfolders).
                        // For documents in native scorm packages it is unclear whether the
                        // online editor should be activated or not.

                        // A new document, it is in the root of the repository.
                        if (is_array($extra_info) && $extra_info != 'new') {
                            // The document already exists. Whe have to determine its relative path towards the repository root.
                            $relative_path = explode('/', $extra_info['dir']);
                            $cnt = count($relative_path) - 2;
                            if ($cnt < 0) {
                                $cnt = 0;
                            }
                            $relative_prefix = str_repeat('../', $cnt);
                            $relative_path = array_slice($relative_path, 1, $cnt);
                            $relative_path = implode('/', $relative_path);
                            if (strlen($relative_path) > 0) {
                                $relative_path = $relative_path.'/';
                            }
                        } else {
                            $result = $this->generate_lp_folder($_course);
                            $relative_path = api_substr($result['dir'], 1, strlen($result['dir']));
                            $relative_prefix = '../../';
                        }

                        $editor_config = [
                            'ToolbarSet' => 'LearningPathDocuments',
                            'Width' => '100%',
                            'Height' => '500',
                            'FullPage' => true,
                            'CreateDocumentDir' => $relative_prefix,
                            'CreateDocumentWebDir' => api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/',
                            'BaseHref' => api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/'.$relative_path,
                        ];

                        if ($_GET['action'] === 'add_item') {
                            $class = 'add';
                            $text = get_lang('LPCreateDocument');
                        } else {
                            if ($_GET['action'] === 'edit_item') {
                                $class = 'save';
                                $text = get_lang('SaveDocument');
                            }
                        }

                        $form->addButtonSave($text, 'submit_button');
                        $renderer = $form->defaultRenderer();
                        $renderer->setElementTemplate('&nbsp;{label}{element}', 'content_lp');
                        $form->addElement('html', '<div class="editor-lp">');
                        $form->addHtmlEditor('content_lp', null, null, true, $editor_config, true);
                        $form->addElement('html', '</div>');
                        $defaults['content_lp'] = $content;
                    }
                } elseif (is_numeric($extra_info)) {
                    $form->addButtonSave(get_lang('SaveDocument'), 'submit_button');

                    $return = $this->display_document($extra_info, true, true, true);
                    $form->addElement('html', $return);
                }
            }
        }
        if (isset($extra_info['item_type']) &&
            $extra_info['item_type'] == TOOL_LP_FINAL_ITEM
        ) {
            $parentSelect->freeze();
            $position->freeze();
        }

        if ($action === 'move') {
            $form->addElement('hidden', 'title', $item_title);
            $form->addElement('hidden', 'description', $item_description);
        }
        if (is_numeric($extra_info)) {
            $form->addButtonSave(get_lang('SaveDocument'), 'submit_button');
            $form->addElement('hidden', 'path', $extra_info);
        } elseif (is_array($extra_info)) {
            $form->addButtonSave(get_lang('SaveDocument'), 'submit_button');
            $form->addElement('hidden', 'path', $extra_info['path']);
        }

        if ($item instanceof learnpathItem) {
            $form->addButtonSave(get_lang('SaveDocument'), 'submit_button');
            $form->addElement('hidden', 'path', $item->path);
        }
        $form->addElement('hidden', 'type', TOOL_DOCUMENT);
        $form->addElement('hidden', 'post_time', time());
        $this->setAuthorLpItem($form);
        $form->setDefaults($defaults);

        return $form->returnForm();
    }

    /**
     * Returns the form to update or create a read-out text.
     *
     * @param string $action     "add" or "edit"
     * @param int    $id         ID of the lp_item (if already exists)
     * @param mixed  $extra_info Integer if document ID, string if info ('new')
     *
     * @throws Exception
     * @throws HTML_QuickForm_Error
     *
     * @return string HTML form
     */
    public function displayFrmReadOutText($action = 'add', $id = 0, $extra_info = 'new')
    {
        $course_id = api_get_course_int_id();
        $_course = api_get_course_info();
        $tbl_doc = Database::get_course_table(TABLE_DOCUMENT);

        $no_display_edit_textarea = false;
        //If action==edit document
        //We don't display the document form if it's not an editable document (html or txt file)
        if ($action == 'edit') {
            if (is_array($extra_info)) {
                $path_parts = pathinfo($extra_info['dir']);
                if ($path_parts['extension'] != "txt" && $path_parts['extension'] != "html") {
                    $no_display_edit_textarea = true;
                }
            }
        }
        $no_display_add = false;

        $item_title = '';
        $item_description = '';
        if ($id != 0 && is_array($extra_info)) {
            $item_title = stripslashes($extra_info['title']);
            $item_description = stripslashes($extra_info['description']);
            $item_terms = stripslashes($extra_info['terms']);
            if (empty($item_title)) {
                $path_parts = pathinfo($extra_info['path']);
                $item_title = stripslashes($path_parts['filename']);
            }
        } elseif (is_numeric($extra_info)) {
            $sql = "SELECT path, title FROM $tbl_doc WHERE c_id = ".$course_id." AND iid = ".intval($extra_info);
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
            $item_title = $row['title'];
            $item_title = str_replace('_', ' ', $item_title);
            if (empty($item_title)) {
                $path_parts = pathinfo($row['path']);
                $item_title = stripslashes($path_parts['filename']);
            }
        }

        $parent = 0;
        if ($id != 0 && is_array($extra_info)) {
            $parent = $extra_info['parent_item_id'];
        }

        $arrLP = $this->getItemsForForm();
        $this->tree_array($arrLP);
        $arrLP = isset($this->arrMenu) ? $this->arrMenu : [];
        unset($this->arrMenu);

        if ($action === 'add') {
            $formHeader = get_lang('CreateTheDocument');
        } else {
            $formHeader = get_lang('EditTheCurrentDocument');
        }

        if ('edit' === $action) {
            $urlAudioIcon = Display::url(
                Display::return_icon('audio.png', get_lang('CreateReadOutText'), [], ICON_SIZE_TINY),
                api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq().'&lp_id='.$this->lp_id.'&'
                    .http_build_query(['view' => 'build', 'id' => $id, 'action' => 'add_audio'])
            );
        } else {
            $urlAudioIcon = Display::return_icon('audio.png', get_lang('CreateReadOutText'), [], ICON_SIZE_TINY);
        }

        $form = new FormValidator(
            'frm_add_reading',
            'POST',
            $this->getCurrentBuildingModeURL(),
            '',
            ['enctype' => 'multipart/form-data']
        );
        $form->addHeader($formHeader);
        $form->addHtml(
            Display::return_message(
                sprintf(get_lang('FrmReadOutTextIntro'), $urlAudioIcon),
                'normal',
                false
            )
        );
        $defaults['title'] = !empty($item_title) ? Security::remove_XSS($item_title) : '';
        $defaults['description'] = $item_description;

        $data = $this->generate_lp_folder($_course);

        if ($action != 'edit') {
            $folders = DocumentManager::get_all_document_folders($_course, 0, true);
            DocumentManager::build_directory_selector(
                $folders,
                '',
                [],
                true,
                $form,
                'directory_parent_id'
            );
        }

        if (isset($data['id'])) {
            $defaults['directory_parent_id'] = $data['id'];
        }
        $this->setItemTitle($form);

        $arrHide[0]['value'] = $this->name;
        $arrHide[0]['padding'] = 20;

        for ($i = 0; $i < count($arrLP); $i++) {
            if ($action != 'add') {
                if ($arrLP[$i]['item_type'] == 'dir' &&
                    !in_array($arrLP[$i]['id'], $arrHide) &&
                    !in_array($arrLP[$i]['parent_item_id'], $arrHide)
                ) {
                    $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
                    $arrHide[$arrLP[$i]['id']]['padding'] = 20 + $arrLP[$i]['depth'] * 20;
                }
            } else {
                if ($arrLP[$i]['item_type'] == 'dir') {
                    $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
                    $arrHide[$arrLP[$i]['id']]['padding'] = 20 + $arrLP[$i]['depth'] * 20;
                }
            }
        }

        $parent_select = $form->addSelect(
            'parent',
            get_lang('Parent'),
            [],
            ['onchange' => "javascript: load_cbo(this.value, 'frm_add_reading_previous');"]
        );

        $my_count = 0;
        foreach ($arrHide as $key => $value) {
            if ($my_count != 0) {
                // The LP name is also the first section and is not in the same charset like the other sections.
                $value['value'] = Security::remove_XSS($value['value']);
                $parent_select->addOption(
                    $value['value'],
                    $key,
                    'style="padding-left:'.$value['padding'].'px;"'
                );
            } else {
                $value['value'] = Security::remove_XSS($value['value']);
                $parent_select->addOption(
                    $value['value'],
                    $key,
                    'style="padding-left:'.$value['padding'].'px;"'
                );
            }
            $my_count++;
        }

        if (!empty($id)) {
            $parent_select->setSelected($parent);
        } else {
            $parent_item_id = Session::read('parent_item_id', 0);
            $parent_select->setSelected($parent_item_id);
        }

        if (is_array($arrLP)) {
            reset($arrLP);
        }

        $arrHide = [];
        $s_selected_position = null;

        // POSITION
        $lastPosition = null;

        for ($i = 0; $i < count($arrLP); $i++) {
            if (($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id) &&
                $arrLP[$i]['item_type'] !== TOOL_LP_FINAL_ITEM
            ) {
                if ((isset($extra_info['previous_item_id']) &&
                    $extra_info['previous_item_id'] == $arrLP[$i]['id']) || $action == 'add'
                ) {
                    $s_selected_position = $arrLP[$i]['id'];
                }
                $arrHide[$arrLP[$i]['id']]['value'] = get_lang('After').' "'.$arrLP[$i]['title'].'"';
            }
            $lastPosition = $arrLP[$i]['id'];
        }

        if (empty($s_selected_position)) {
            $s_selected_position = $lastPosition;
        }

        $position = $form->addSelect(
            'previous',
            get_lang('Position'),
            []
        );
        $position->addOption(get_lang('FirstPosition'), 0);

        foreach ($arrHide as $key => $value) {
            $padding = isset($value['padding']) ? $value['padding'] : 20;
            $position->addOption(
                $value['value'],
                $key,
                'style="padding-left:'.$padding.'px;"'
            );
        }
        $position->setSelected($s_selected_position);

        if (is_array($arrLP)) {
            reset($arrLP);
        }

        if ('edit' === $action) {
            $excludeExtraFields = [];
            if (true !== api_get_configuration_value('lp_item_prerequisite_dates')) {
                $excludeExtraFields = ['start_date', 'end_date'];
            }
            $extraField = new ExtraField('lp_item');
            $extraField->addElements($form, $id, $excludeExtraFields);
        }

        $arrHide = [];

        for ($i = 0; $i < count($arrLP); $i++) {
            if ($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dir' &&
                $arrLP[$i]['item_type'] !== TOOL_LP_FINAL_ITEM
            ) {
                $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
            }
        }

        if (!$no_display_add) {
            $item_type = isset($extra_info['item_type']) ? $extra_info['item_type'] : null;
            $edit = isset($_GET['edit']) ? $_GET['edit'] : null;

            if ($extra_info == 'new' || $item_type == TOOL_READOUT_TEXT || $edit == 'true') {
                if (!$no_display_edit_textarea) {
                    $content = '';

                    if (isset($_POST['content'])) {
                        $content = stripslashes($_POST['content']);
                    } elseif (is_array($extra_info)) {
                        $content = $this->display_document($extra_info['path'], false, false);
                    } elseif (is_numeric($extra_info)) {
                        $content = $this->display_document($extra_info, false, false);
                    }

                    // A new document, it is in the root of the repository.
                    if (is_array($extra_info) && $extra_info != 'new') {
                    } else {
                        $this->generate_lp_folder($_course);
                    }

                    if ($_GET['action'] == 'add_item') {
                        $text = get_lang('LPCreateDocument');
                    } else {
                        $text = get_lang('SaveDocument');
                    }

                    $form->addTextarea('content_lp', get_lang('Content'), ['rows' => 20]);
                    $form
                        ->defaultRenderer()
                        ->setElementTemplate($form->getDefaultElementTemplate(), 'content_lp');
                    $form->addButtonSave($text, 'submit_button');
                    $defaults['content_lp'] = $content;
                }
            } elseif (is_numeric($extra_info)) {
                $form->addButtonSave(get_lang('SaveDocument'), 'submit_button');

                $return = $this->display_document($extra_info, true, true, true);
                $form->addElement('html', $return);
            }
        }

        if (is_numeric($extra_info)) {
            $form->addElement('hidden', 'path', $extra_info);
        } elseif (is_array($extra_info)) {
            $form->addElement('hidden', 'path', $extra_info['path']);
        }

        $form->addElement('hidden', 'type', TOOL_READOUT_TEXT);
        $form->addElement('hidden', 'post_time', time());
        $this->setAuthorLpItem($form);
        $form->setDefaults($defaults);

        return $form->returnForm();
    }

    /**
     * @param array  $courseInfo
     * @param string $content
     * @param string $title
     * @param int    $parentId
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return int
     */
    public function createReadOutText($courseInfo, $content = '', $title = '', $parentId = 0)
    {
        $creatorId = api_get_user_id();
        $sessionId = api_get_session_id();

        // Generates folder
        $result = $this->generate_lp_folder($courseInfo);
        $dir = $result['dir'];

        if (empty($parentId) || $parentId == '/') {
            $postDir = isset($_POST['dir']) ? $_POST['dir'] : $dir;
            $dir = isset($_GET['dir']) ? $_GET['dir'] : $postDir; // Please, do not modify this dirname formatting.

            if ($parentId === '/') {
                $dir = '/';
            }

            // Please, do not modify this dirname formatting.
            if (strstr($dir, '..')) {
                $dir = '/';
            }

            if (!empty($dir[0]) && $dir[0] == '.') {
                $dir = substr($dir, 1);
            }
            if (!empty($dir[0]) && $dir[0] != '/') {
                $dir = '/'.$dir;
            }
            if (isset($dir[strlen($dir) - 1]) && $dir[strlen($dir) - 1] != '/') {
                $dir .= '/';
            }
        } else {
            $parentInfo = DocumentManager::get_document_data_by_id(
                $parentId,
                $courseInfo['code']
            );
            if (!empty($parentInfo)) {
                $dir = $parentInfo['path'].'/';
            }
        }

        $filepath = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document/'.$dir;

        if (!is_dir($filepath)) {
            $dir = '/';
            $filepath = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document/'.$dir;
        }

        $originalTitle = !empty($title) ? $title : $_POST['title'];

        if (!empty($title)) {
            $title = api_replace_dangerous_char(stripslashes($title));
        } else {
            $title = api_replace_dangerous_char(stripslashes($_POST['title']));
        }

        $title = disable_dangerous_file($title);
        $filename = $title;
        $content = !empty($content) ? $content : $_POST['content_lp'];
        $tmpFileName = $filename;

        $i = 0;
        while (file_exists($filepath.$tmpFileName.'.html')) {
            $tmpFileName = $filename.'_'.++$i;
        }

        $filename = $tmpFileName.'.html';
        $content = stripslashes($content);

        if (file_exists($filepath.$filename)) {
            return 0;
        }

        $putContent = file_put_contents($filepath.$filename, $content);

        if ($putContent === false) {
            return 0;
        }

        $fileSize = filesize($filepath.$filename);
        $saveFilePath = $dir.$filename;

        $documentId = add_document(
            $courseInfo,
            $saveFilePath,
            'file',
            $fileSize,
            $tmpFileName,
            '',
            0, //readonly
            true,
            null,
            $sessionId,
            $creatorId
        );

        if (!$documentId) {
            return 0;
        }

        api_item_property_update(
            $courseInfo,
            TOOL_DOCUMENT,
            $documentId,
            'DocumentAdded',
            $creatorId,
            null,
            null,
            null,
            null,
            $sessionId
        );

        $newComment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
        $newTitle = $originalTitle;

        if ($newComment || $newTitle) {
            $em = Database::getManager();

            /** @var CDocument $doc */
            $doc = $em->find('ChamiloCourseBundle:CDocument', $documentId);

            if ($newComment) {
                $doc->setComment($newComment);
            }

            if ($newTitle) {
                $doc->setTitle($newTitle);
            }

            $em->persist($doc);
            $em->flush();
        }

        return $documentId;
    }

    /**
     * Return HTML form to add/edit a link item.
     *
     * @param string $action     (add/edit)
     * @param int    $id         Item ID if exists
     * @param mixed  $extra_info
     *
     * @throws Exception
     * @throws HTML_QuickForm_Error
     *
     * @return string HTML form
     */
    public function display_link_form(
        $action = 'add',
        $id = 0,
        $extra_info = '',
        $item = null,
        $excludeExtraFields = []
    ) {
        $course_id = api_get_course_int_id();
        $tbl_link = Database::get_course_table(TABLE_LINK);

        $item_title = '';
        $item_description = '';
        $item_url = '';

        $previousId = 0;
        if ($id != 0 && is_array($extra_info)) {
            $item_title = stripslashes($extra_info['title']);
            $item_description = stripslashes($extra_info['description']);
            $item_url = stripslashes($extra_info['url']);
            $previousId = $extra_info['previous_item_id'];
        } elseif (is_numeric($extra_info)) {
            $extra_info = (int) $extra_info;
            $sql = "SELECT title, description, url
                    FROM $tbl_link
                    WHERE c_id = $course_id AND iid = $extra_info";
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
            $item_title = $row['title'];
            $item_description = $row['description'];
            $item_url = $row['url'];
        }

        if ($item instanceof learnpathItem) {
            $previousId = $extra_info->previous;
        }

        $form = new FormValidator(
            'edit_link',
            'POST',
            $this->getCurrentBuildingModeURL()
        );
        $defaults = [];
        $parent = 0;
        if ($id != 0 && is_array($extra_info)) {
            $parent = $extra_info['parent_item_id'];
        }

        $arrLP = $this->getItemsForForm();

        $this->tree_array($arrLP);
        $arrLP = isset($this->arrMenu) ? $this->arrMenu : [];
        unset($this->arrMenu);

        if ($action === 'add') {
            $legend = get_lang('CreateTheLink');
        } elseif ($action === 'move') {
            $legend = get_lang('MoveCurrentLink');
        } else {
            $legend = get_lang('EditCurrentLink');
        }

        $form->addHeader($legend);

        if ($action !== 'move') {
            $this->setItemTitle($form);
            $defaults['title'] = $item_title;
        }

        $selectParent = $form->addSelect(
            'parent',
            get_lang('Parent'),
            [],
            ['id' => 'idParent', 'onchange' => 'load_cbo(this.value);', 'class' => 'learnpath_item_form']
        );
        $selectParent->addOption($this->name, 0);
        $arrHide = [
            $id,
        ];

        $parent_item_id = Session::read('parent_item_id', 0);

        for ($i = 0; $i < count($arrLP); $i++) {
            if ($action != 'add') {
                if (
                    ($arrLP[$i]['item_type'] == 'dir') &&
                    !in_array($arrLP[$i]['id'], $arrHide) &&
                    !in_array($arrLP[$i]['parent_item_id'], $arrHide)
                ) {
                    $selectParent->addOption(
                        $arrLP[$i]['title'],
                        $arrLP[$i]['id'],
                        ['style' => 'padding-left: '.(20 + $arrLP[$i]['depth'] * 20).'px;']
                    );

                    if ($parent == $arrLP[$i]['id']) {
                        $selectParent->setSelected($arrLP[$i]['id']);
                    }
                } else {
                    $arrHide[] = $arrLP[$i]['id'];
                }
            } else {
                if ($arrLP[$i]['item_type'] == 'dir') {
                    $selectParent->addOption(
                        $arrLP[$i]['title'],
                        $arrLP[$i]['id'],
                        ['style' => 'padding-left: '.(20 + $arrLP[$i]['depth'] * 20).'px']
                    );

                    if ($parent_item_id == $arrLP[$i]['id']) {
                        $selectParent->setSelected($arrLP[$i]['id']);
                    }
                }
            }
        }

        if (is_array($arrLP)) {
            reset($arrLP);
        }

        $selectPrevious = $form->addSelect(
            'previous',
            get_lang('Position'),
            [],
            ['id' => 'previous', 'class' => 'learnpath_item_form']
        );
        $selectPrevious->addOption(get_lang('FirstPosition'), 0);

        for ($i = 0; $i < count($arrLP); $i++) {
            if ($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id) {
                $selectPrevious->addOption(
                    $arrLP[$i]['title'],
                    $arrLP[$i]['id']
                );

                if ($previousId == $arrLP[$i]['id']) {
                    $selectPrevious->setSelected($arrLP[$i]['id']);
                } elseif ($action === 'add') {
                    $selectPrevious->setSelected($arrLP[$i]['id']);
                }
            }
        }

        if ($action !== 'move') {
            $urlAttributes = ['class' => 'learnpath_item_form'];

            if (is_numeric($extra_info)) {
                $urlAttributes['disabled'] = 'disabled';
            }

            $form->addElement('url', 'url', get_lang('Url'), $urlAttributes);
            $defaults['url'] = $item_url;
            $arrHide = [];
            for ($i = 0; $i < count($arrLP); $i++) {
                if ($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dir') {
                    $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
                }
            }
        }

        if ('edit' === $action) {
            if (true !== api_get_configuration_value('lp_item_prerequisite_dates')) {
                $excludeExtraFields = array_merge($excludeExtraFields, ['start_date', 'end_date']);
            }
            $extraField = new ExtraField('lp_item');
            $extraField->addElements($form, $id, $excludeExtraFields);
        }

        if ($action === 'add') {
            $form->addButtonSave(get_lang('AddLinkToCourse'), 'submit_button');
        } else {
            $form->addButtonSave(get_lang('EditCurrentLink'), 'submit_button');
        }

        if ($action === 'move') {
            $form->addHidden('title', $item_title);
            $form->addHidden('description', $item_description);
        }

        if (is_numeric($extra_info)) {
            $form->addHidden('path', $extra_info);
        } elseif (is_array($extra_info)) {
            $form->addHidden('path', $extra_info['path']);
        }
        $form->addHidden('type', TOOL_LINK);
        $form->addHidden('post_time', time());
        $this->setAuthorLpItem($form);
        $form->setDefaults($defaults);

        return '<div class="sectioncomment">'.$form->returnForm().'</div>';
    }

    /**
     * Return HTML form to add/edit a student publication (work).
     *
     * @param string $action
     * @param int    $id         Item ID if already exists
     * @param string $extra_info
     *
     * @throws Exception
     *
     * @return string HTML form
     */
    public function display_student_publication_form(
        $action = 'add',
        $id = 0,
        $extra_info = '',
        $item = null,
        $excludeExtraFields = []
    ) {
        $course_id = api_get_course_int_id();
        $tbl_publication = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

        $item_title = get_lang('Student_publication');
        $previousId = 0;
        if ($id != 0 && is_array($extra_info)) {
            $item_title = stripslashes($extra_info['title']);
            $item_description = stripslashes($extra_info['description']);
            $previousId = $extra_info['previous_item_id'];
        } elseif (is_numeric($extra_info)) {
            $extra_info = (int) $extra_info;
            $sql = "SELECT title, description
                    FROM $tbl_publication
                    WHERE c_id = $course_id AND id = ".$extra_info;

            $result = Database::query($sql);
            $row = Database::fetch_array($result);
            if ($row) {
                $item_title = $row['title'];
            }
        }

        if ($item instanceof learnpathItem) {
            $item_title = $item->get_title();
            $item_description = $item->get_description();
            $previousId = $item->previous;
        }

        $parent = 0;
        if ($id != 0 && is_array($extra_info)) {
            $parent = $extra_info['parent_item_id'];
        }

        $arrLP = $this->getItemsForForm();

        $this->tree_array($arrLP);
        $arrLP = isset($this->arrMenu) ? $this->arrMenu : [];
        unset($this->arrMenu);

        $form = new FormValidator('frm_student_publication', 'post', '#');

        if ($action === 'add') {
            $form->addHeader(get_lang('Student_publication'));
        } elseif ($action === 'move') {
            $form->addHeader(get_lang('MoveCurrentStudentPublication'));
        } else {
            $form->addHeader(get_lang('EditCurrentStudentPublication'));
        }

        if ($action !== 'move') {
            $this->setItemTitle($form);
        }

        $parentSelect = $form->addSelect(
            'parent',
            get_lang('Parent'),
            ['0' => $this->name],
            [
                'onchange' => 'javascript: load_cbo(this.value);',
                'class' => 'learnpath_item_form',
                'id' => 'idParent',
            ]
        );

        $arrHide = [$id];
        for ($i = 0; $i < count($arrLP); $i++) {
            if ($action != 'add') {
                if (
                    ($arrLP[$i]['item_type'] == 'dir') &&
                    !in_array($arrLP[$i]['id'], $arrHide) &&
                    !in_array($arrLP[$i]['parent_item_id'], $arrHide)
                ) {
                    $parentSelect->addOption(
                        $arrLP[$i]['title'],
                        $arrLP[$i]['id'],
                        ['style' => 'padding-left: '.(($arrLP[$i]['depth'] * 10) + 20).'px;']
                    );

                    if ($parent == $arrLP[$i]['id']) {
                        $parentSelect->setSelected($arrLP[$i]['id']);
                    }
                } else {
                    $arrHide[] = $arrLP[$i]['id'];
                }
            } else {
                if ($arrLP[$i]['item_type'] === 'dir') {
                    $parentSelect->addOption(
                        $arrLP[$i]['title'],
                        $arrLP[$i]['id'],
                        ['style' => 'padding-left: '.(($arrLP[$i]['depth'] * 10) + 20).'px;']
                    );

                    if ($parent == $arrLP[$i]['id']) {
                        $parentSelect->setSelected($arrLP[$i]['id']);
                    }
                }
            }
        }

        if (is_array($arrLP)) {
            reset($arrLP);
        }

        $previousSelect = $form->addSelect(
            'previous',
            get_lang('Position'),
            ['0' => get_lang('FirstPosition')],
            ['id' => 'previous', 'class' => 'learnpath_item_form']
        );

        for ($i = 0; $i < count($arrLP); $i++) {
            if ($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id) {
                $previousSelect->addOption(
                    get_lang('After').' "'.$arrLP[$i]['title'].'"',
                    $arrLP[$i]['id']
                );

                if ($previousId == $arrLP[$i]['id']) {
                    $previousSelect->setSelected($arrLP[$i]['id']);
                } elseif ($action === 'add') {
                    $previousSelect->setSelected($arrLP[$i]['id']);
                }
            }
        }

        if ('edit' === $action) {
            if (true !== api_get_configuration_value('lp_item_prerequisite_dates')) {
                $excludeExtraFields = array_merge($excludeExtraFields, ['start_date', 'end_date']);
            }
            $extraField = new ExtraField('lp_item');
            $extraField->addElements($form, $id, $excludeExtraFields);
        }

        if ($action === 'add') {
            $form->addButtonCreate(get_lang('AddAssignmentToCourse'), 'submit_button');
        } else {
            $form->addButtonCreate(get_lang('EditCurrentStudentPublication'), 'submit_button');
        }

        if ($action === 'move') {
            $form->addHidden('title', $item_title);
            $form->addHidden('description', $item_description);
        }

        if (is_numeric($extra_info)) {
            $form->addHidden('path', $extra_info);
        } elseif (is_array($extra_info)) {
            $form->addHidden('path', $extra_info['path']);
        }

        $form->addHidden('type', TOOL_STUDENTPUBLICATION);
        $form->addHidden('post_time', time());
        $this->setAuthorLpItem($form);
        $form->setDefaults(['title' => $item_title, 'start_date' => null]);

        $return = '<div class="sectioncomment">';
        $return .= $form->returnForm();
        $return .= '</div>';

        return $return;
    }

    /**
     * Displays the menu for manipulating a step.
     *
     * @param int    $item_id
     * @param string $item_type
     *
     * @return string
     */
    public function display_manipulate($item_id, $item_type = TOOL_DOCUMENT)
    {
        $_course = api_get_course_info();
        $course_code = api_get_course_id();
        $item_id = (int) $item_id;

        $return = '<div class="actions">';
        $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $sql = "SELECT * FROM $tbl_lp_item
                WHERE iid = ".$item_id;
        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);

        $audio_player = null;
        // We display an audio player if needed.
        if (!empty($row['audio'])) {
            $audio = learnpathItem::fixAudio($row['audio']);
            $webAudioPath = '../..'.api_get_path(REL_COURSE_PATH).$_course['path'].'/document'.$audio;
            $audio_player .= '<div class="lp_mediaplayer" id="container">
                            <audio src="'.$webAudioPath.'" controls>
                            </div><br />';
        }

        $url = api_get_self().'?'.api_get_cidreq().'&view=build&id='.$item_id.'&lp_id='.$this->lp_id;

        if ($item_type != TOOL_LP_FINAL_ITEM) {
            $return .= Display::url(
                Display::return_icon(
                    'edit.png',
                    get_lang('Edit'),
                    [],
                    ICON_SIZE_SMALL
                ),
                $url.'&action=edit_item&path_item='.$row['path']
            );

            $return .= Display::url(
                Display::return_icon(
                    'move.png',
                    get_lang('Move'),
                    [],
                    ICON_SIZE_SMALL
                ),
                $url.'&action=move_item'
            );
        }

        // Commented for now as prerequisites cannot be added to chapters.
        if ($item_type != 'dir') {
            $return .= Display::url(
                Display::return_icon(
                    'accept.png',
                    get_lang('LearnpathPrerequisites'),
                    [],
                    ICON_SIZE_SMALL
                ),
                $url.'&action=edit_item_prereq'
            );
        }
        $return .= Display::url(
            Display::return_icon(
                'delete.png',
                get_lang('Delete'),
                [],
                ICON_SIZE_SMALL
            ),
            $url.'&action=delete_item'
        );

        if (in_array($item_type, [TOOL_DOCUMENT, TOOL_LP_FINAL_ITEM, TOOL_HOTPOTATOES])) {
            $documentData = DocumentManager::get_document_data_by_id($row['path'], $course_code);
            if (empty($documentData)) {
                // Try with iid
                $table = Database::get_course_table(TABLE_DOCUMENT);
                $sql = "SELECT path FROM $table
                        WHERE
                              c_id = ".api_get_course_int_id()." AND
                              iid = ".$row['path']." AND
                              path NOT LIKE '%_DELETED_%'";
                $result = Database::query($sql);
                $documentData = Database::fetch_array($result);
                if ($documentData) {
                    $documentData['absolute_path_from_document'] = '/document'.$documentData['path'];
                }
            }
            if (isset($documentData['absolute_path_from_document'])) {
                $return .= get_lang('File').': '.$documentData['absolute_path_from_document'];
            }
        }

        $return .= '</div>';

        if (!empty($audio_player)) {
            $return .= $audio_player;
        }

        return $return;
    }

    /**
     * Creates the javascript needed for filling up the checkboxes without page reload.
     *
     * @return string
     */
    public function get_js_dropdown_array()
    {
        $course_id = api_get_course_int_id();
        $return = 'var child_name = new Array();'."\n";
        $return .= 'var child_value = new Array();'."\n\n";
        $return .= 'child_name[0] = new Array();'."\n";
        $return .= 'child_value[0] = new Array();'."\n\n";

        $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $i = 0;
        $list = $this->getItemsForForm(true);

        foreach ($list as $row_zero) {
            if ($row_zero['item_type'] !== TOOL_LP_FINAL_ITEM) {
                if ($row_zero['item_type'] == TOOL_QUIZ) {
                    $row_zero['title'] = Exercise::get_formated_title_variable($row_zero['title']);
                }
                $js_var = json_encode(get_lang('After').' '.$row_zero['title']);
                $return .= 'child_name[0]['.$i.'] = '.$js_var.' ;'."\n";
                $return .= 'child_value[0]['.$i++.'] = "'.$row_zero['iid'].'";'."\n";
            }
        }

        $return .= "\n";
        $sql = "SELECT * FROM $tbl_lp_item
                WHERE c_id = $course_id AND lp_id = ".$this->lp_id;
        $res = Database::query($sql);
        while ($row = Database::fetch_array($res)) {
            $sql_parent = "SELECT * FROM ".$tbl_lp_item."
                           WHERE
                                c_id = ".$course_id." AND
                                parent_item_id = ".$row['iid']."
                           ORDER BY display_order ASC";
            $res_parent = Database::query($sql_parent);
            $i = 0;
            $return .= 'child_name['.$row['iid'].'] = new Array();'."\n";
            $return .= 'child_value['.$row['iid'].'] = new Array();'."\n\n";

            while ($row_parent = Database::fetch_array($res_parent)) {
                $js_var = json_encode(get_lang('After').' '.$this->cleanItemTitle($row_parent['title']));
                $return .= 'child_name['.$row['iid'].']['.$i.'] =   '.$js_var.' ;'."\n";
                $return .= 'child_value['.$row['iid'].']['.$i++.'] = "'.$row_parent['iid'].'";'."\n";
            }
            $return .= "\n";
        }

        $return .= "
            function load_cbo(id) {
                if (!id) {
                    return false;
                }

                var cbo = document.getElementById('previous');
                for (var i = cbo.length - 1; i > 0; i--) {
                    cbo.options[i] = null;
                }

                var k=0;
                for(var i = 1; i <= child_name[id].length; i++){
                    var option = new Option(child_name[id][i - 1], child_value[id][i - 1]);
                    option.style.paddingLeft = '40px';
                    cbo.options[i] = option;
                    k = i;
                }

                cbo.options[k].selected = true;
                $('#previous').selectpicker('refresh');
            }";

        return $return;
    }

    /**
     * Display the form to allow moving an item.
     *
     * @param learnpathItem $item Item ID
     *
     * @throws Exception
     * @throws HTML_QuickForm_Error
     *
     * @return string HTML form
     */
    public function display_move_item($item)
    {
        $return = '';
        if ($item) {
            $item_id = $item->getIid();
            $type = $item->get_type();
            $path = (int) $item->get_path();

            switch ($type) {
                case 'dir':
                case 'asset':
                    $return .= $this->display_manipulate($item_id, $type);
                    $return .= $this->display_item_form(
                        $type,
                        get_lang('MoveCurrentChapter'),
                        'move',
                        $item_id,
                        $item
                    );
                    break;
                case TOOL_DOCUMENT:
                    $return .= $this->display_manipulate($item_id, $type);
                    $return .= $this->display_document_form('move', $item_id, null, $item);
                    break;
                case TOOL_LINK:
                    $return .= $this->display_manipulate($item_id, $type);
                    $return .= $this->display_link_form('move', $item_id, $path, $item);
                    break;
                case TOOL_HOTPOTATOES:
                    $return .= $this->display_manipulate($item_id, $type);
                    $return .= $this->display_link_form('move', $item_id, $item);
                    break;
                case TOOL_QUIZ:
                    $return .= $this->display_manipulate($item_id, $type);
                    $return .= $this->display_quiz_form('move', $item_id, $item);
                    break;
                case TOOL_STUDENTPUBLICATION:
                    $return .= $this->display_manipulate($item_id, $type);
                    $return .= $this->display_student_publication_form('move', $item_id, $path, $item);
                    break;
                case TOOL_FORUM:
                    $return .= $this->display_manipulate($item_id, $type);
                    $return .= $this->display_forum_form('move', $item_id, $path);
                    break;
                case TOOL_THREAD:
                    $return .= $this->display_manipulate($item_id, $type);
                    $return .= $this->display_forum_form('move', $item_id, $path);
                    break;
            }
        }

        return $return;
    }

    /**
     * Return HTML form to allow prerequisites selection.
     *
     * @todo use FormValidator
     *
     * @param int Item ID
     *
     * @return string HTML form
     */
    public function display_item_prerequisites_form($item_id = 0)
    {
        $course_id = api_get_course_int_id();
        $item_id = (int) $item_id;

        if (empty($item_id)) {
            return '';
        }

        $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);

        /* Current prerequisite */
        $sql = "SELECT * FROM $tbl_lp_item
                WHERE iid = $item_id";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $prerequisiteId = $row['prerequisite'];

        $return = '<legend>';
        $return .= get_lang('AddEditPrerequisites');
        $return .= '</legend>';
        $return .= '<form method="POST">';
        $return .= '<div class="table-responsive">';
        $return .= '<table class="table table-hover">';
        $return .= '<thead>';
        $return .= '<tr>';
        $return .= '<th>'.get_lang('LearnpathPrerequisites').'</th>';
        $return .= '<th width="140">'.get_lang('Minimum').'</th>';
        $return .= '<th width="140">'.get_lang('Maximum').'</th>';
        $return .= '</tr>';
        $return .= '</thead>';

        // Adding the none option to the prerequisites see http://www.chamilo.org/es/node/146
        $return .= '<tbody>';
        $return .= '<tr>';
        $return .= '<td colspan="3">';
        $return .= '<div class="radio learnpath"><label for="idNone">';
        $return .= '<input checked="checked" id="idNone" name="prerequisites" type="radio" />';
        $return .= get_lang('None').'</label>';
        $return .= '</div>';
        $return .= '</tr>';

        $sql = "SELECT * FROM $tbl_lp_item
                WHERE c_id = $course_id AND lp_id = ".$this->lp_id;
        $result = Database::query($sql);

        $selectedMinScore = [];
        $selectedMaxScore = [];
        $masteryScore = [];
        while ($row = Database::fetch_array($result)) {
            if ($row['iid'] == $item_id) {
                $selectedMinScore[$row['prerequisite']] = $row['prerequisite_min_score'];
                $selectedMaxScore[$row['prerequisite']] = $row['prerequisite_max_score'];
            }
            $masteryScore[$row['iid']] = $row['mastery_score'];
        }

        $arrLP = $this->getItemsForForm();
        $this->tree_array($arrLP);
        $arrLP = isset($this->arrMenu) ? $this->arrMenu : [];
        unset($this->arrMenu);

        for ($i = 0; $i < count($arrLP); $i++) {
            $item = $arrLP[$i];

            if ($item['id'] == $item_id) {
                break;
            }

            $selectedMaxScoreValue = isset($selectedMaxScore[$item['id']]) ? $selectedMaxScore[$item['id']] : $item['max_score'];
            $selectedMinScoreValue = isset($selectedMinScore[$item['id']]) ? $selectedMinScore[$item['id']] : 0;
            $masteryScoreAsMinValue = isset($masteryScore[$item['id']]) ? $masteryScore[$item['id']] : 0;

            $return .= '<tr>';
            $return .= '<td '.(($item['item_type'] != TOOL_QUIZ && $item['item_type'] != TOOL_HOTPOTATOES) ? ' colspan="3"' : '').'>';
            $return .= '<div style="margin-left:'.($item['depth'] * 20).'px;" class="radio learnpath">';
            $return .= '<label for="id'.$item['id'].'">';

            $checked = '';
            if (null !== $prerequisiteId) {
                $checked = in_array($prerequisiteId, [$item['id'], $item['ref']]) ? ' checked="checked" ' : '';
            }

            $disabled = $item['item_type'] === 'dir' ? ' disabled="disabled" ' : '';

            $return .= '<input '.$checked.' '.$disabled.' id="id'.$item['id'].'" name="prerequisites" type="radio" value="'.$item['id'].'" />';

            $icon_name = str_replace(' ', '', $item['item_type']);
            if (file_exists('../img/lp_'.$icon_name.'.png')) {
                $return .= Display::return_icon('lp_'.$icon_name.'.png');
            } else {
                if (file_exists('../img/lp_'.$icon_name.'.png')) {
                    $return .= Display::return_icon('lp_'.$icon_name.'.png');
                } else {
                    $return .= Display::return_icon('folder_document.png');
                }
            }

            $return .= $item['title'].'</label>';
            $return .= '</div>';
            $return .= '</td>';

            if ($item['item_type'] == TOOL_QUIZ) {
                // lets update max_score Quiz information depending of the Quiz Advanced properties
                $lpItemObj = new LpItem($course_id, $item['id']);
                $exercise = new Exercise($course_id);
                $exercise->read($lpItemObj->path);
                $lpItemObj->max_score = $exercise->get_max_score();
                $lpItemObj->update();
                $item['max_score'] = $lpItemObj->max_score;

                //if (empty($selectedMinScoreValue) && !empty($masteryScoreAsMinValue)) {
                if (!isset($selectedMinScore[$item['id']]) && !empty($masteryScoreAsMinValue)) {
                    // Backwards compatibility with 1.9.x use mastery_score as min value
                    $selectedMinScoreValue = $masteryScoreAsMinValue;
                }

                $return .= '<td>';
                $return .= '<input
                    class="form-control"
                    size="4" maxlength="3"
                    name="min_'.$item['id'].'"
                    type="number"
                    min="0"
                    step="any"
                    max="'.$item['max_score'].'"
                    value="'.$selectedMinScoreValue.'"
                />';
                $return .= '</td>';
                $return .= '<td>';
                $return .= '<input
                    class="form-control"
                    size="4"
                    maxlength="3"
                    name="max_'.$item['id'].'"
                    type="number"
                    min="0"
                    step="any"
                    max="'.$item['max_score'].'"
                    value="'.$selectedMaxScoreValue.'"
                />';
                $return .= '</td>';
            }

            if ($item['item_type'] == TOOL_HOTPOTATOES) {
                $return .= '<td>';
                $return .= '<input
                    size="4"
                    maxlength="3"
                    name="min_'.$item['id'].'"
                    type="number"
                    min="0"
                    step="any"
                    max="'.$item['max_score'].'"
                    value="'.$selectedMinScoreValue.'"
                />';
                $return .= '</td>';
                $return .= '<td>';
                $return .= '<input
                    size="4"
                    maxlength="3"
                    name="max_'.$item['id'].'"
                    type="number"
                    min="0"
                    step="any"
                    max="'.$item['max_score'].'"
                    value="'.$selectedMaxScoreValue.'"
                />';
                $return .= '</td>';
            }
            $return .= '</tr>';
        }
        $return .= '<tr>';
        $return .= '</tr>';
        $return .= '</tbody>';
        $return .= '</table>';
        $return .= '</div>';
        $return .= '<div class="form-group">';
        $return .= '<button class="btn btn-primary" name="submit_button" type="submit">'.
            get_lang('ModifyPrerequisites').'</button>';
        $return .= '</form>';

        return $return;
    }

    /**
     * Return HTML list to allow prerequisites selection for lp.
     *
     * @return string HTML form
     */
    public function display_lp_prerequisites_list()
    {
        $course_id = api_get_course_int_id();
        $lp_id = $this->lp_id;
        $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);

        // get current prerequisite
        $sql = "SELECT * FROM $tbl_lp WHERE iid = $lp_id ";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $prerequisiteId = $row['prerequisite'];
        $session_id = api_get_session_id();
        $session_condition = api_get_session_condition($session_id, true, true);
        $sql = "SELECT * FROM $tbl_lp
                WHERE c_id = $course_id $session_condition
                ORDER BY display_order ";
        $rs = Database::query($sql);
        $return = '';
        $return .= '<select name="prerequisites" class="form-control">';
        $return .= '<option value="0">'.get_lang('None').'</option>';
        if (Database::num_rows($rs) > 0) {
            while ($row = Database::fetch_array($rs)) {
                if ($row['id'] == $lp_id) {
                    continue;
                }
                $return .= '<option value="'.$row['id'].'" '.(($row['id'] == $prerequisiteId) ? ' selected ' : '').'>'.$row['name'].'</option>';
            }
        }
        $return .= '</select>';

        return $return;
    }

    /**
     * Creates a list with all the documents in it.
     *
     * @param bool $showInvisibleFiles
     *
     * @throws Exception
     * @throws HTML_QuickForm_Error
     *
     * @return string
     */
    public function get_documents($showInvisibleFiles = false)
    {
        $course_info = api_get_course_info();
        $sessionId = api_get_session_id();
        $documentTree = DocumentManager::get_document_preview(
            $course_info,
            $this->lp_id,
            null,
            $sessionId,
            true,
            null,
            null,
            $showInvisibleFiles,
            true
        );

        $headers = [
            get_lang('Files'),
            get_lang('CreateTheDocument'),
            get_lang('CreateReadOutText'),
            get_lang('Upload'),
        ];

        $form = new FormValidator(
            'form_upload',
            'POST',
            $this->getCurrentBuildingModeURL(),
            '',
            ['enctype' => 'multipart/form-data']
        );

        $folders = DocumentManager::get_all_document_folders(
            api_get_course_info(),
            0,
            true
        );

        $lpPathInfo = $this->generate_lp_folder(api_get_course_info());

        DocumentManager::build_directory_selector(
            $folders,
            $lpPathInfo['id'],
            [],
            true,
            $form,
            'directory_parent_id'
        );

        $group = [
            $form->createElement(
                'radio',
                'if_exists',
                get_lang('UplWhatIfFileExists'),
                get_lang('UplDoNothing'),
                'nothing'
            ),
            $form->createElement(
                'radio',
                'if_exists',
                null,
                get_lang('UplOverwriteLong'),
                'overwrite'
            ),
            $form->createElement(
                'radio',
                'if_exists',
                null,
                get_lang('UplRenameLong'),
                'rename'
            ),
        ];
        $form->addGroup($group, null, get_lang('UplWhatIfFileExists'));

        $fileExistsOption = api_get_setting('document_if_file_exists_option');
        $defaultFileExistsOption = 'rename';
        if (!empty($fileExistsOption)) {
            $defaultFileExistsOption = $fileExistsOption;
        }
        $form->setDefaults(['if_exists' => $defaultFileExistsOption]);

        // Check box options
        $form->addElement(
            'checkbox',
            'unzip',
            get_lang('Options'),
            get_lang('Uncompress')
        );

        $url = api_get_path(WEB_AJAX_PATH).'document.ajax.php?'.api_get_cidreq().'&a=upload_file&curdirpath=';
        $form->addMultipleUpload($url);
        $new = $this->display_document_form('add', 0);
        $frmReadOutText = $this->displayFrmReadOutText('add');
        $tabs = Display::tabs(
            $headers,
            [$documentTree, $new, $frmReadOutText, $form->returnForm()],
            'subtab'
        );

        return $tabs;
    }

    /**
     * Creates a list with all the exercises (quiz) in it.
     *
     * @return string
     */
    public function get_exercises()
    {
        $course_id = api_get_course_int_id();
        $session_id = api_get_session_id();
        $userInfo = api_get_user_info();

        // New for hotpotatoes.
        $uploadPath = DIR_HOTPOTATOES; //defined in main_api
        $tbl_doc = Database::get_course_table(TABLE_DOCUMENT);
        $tbl_quiz = Database::get_course_table(TABLE_QUIZ_TEST);
        $condition_session = api_get_session_condition($session_id, true, true);
        $setting = api_get_configuration_value('show_invisible_exercise_in_lp_list');

        $activeCondition = ' active <> -1 ';
        if ($setting) {
            $activeCondition = ' active = 1 ';
        }

        $categoryCondition = '';
        $categoryId = isset($_REQUEST['category_id']) ? (int) $_REQUEST['category_id'] : 0;
        if (api_get_configuration_value('allow_exercise_categories') && !empty($categoryId)) {
            $categoryCondition = " AND exercise_category_id = $categoryId ";
        }

        $keywordCondition = '';
        $keyword = isset($_REQUEST['keyword']) ? $_REQUEST['keyword'] : '';

        if (!empty($keyword)) {
            $keyword = Database::escape_string($keyword);
            $keywordCondition = " AND title LIKE '%$keyword%' ";
        }

        $sql_quiz = "SELECT * FROM $tbl_quiz
                     WHERE
                            c_id = $course_id AND
                            $activeCondition
                            $condition_session
                            $categoryCondition
                            $keywordCondition
                     ORDER BY title ASC";

        $sql_hot = "SELECT * FROM $tbl_doc
                    WHERE
                        c_id = $course_id AND
                        path LIKE '".$uploadPath."/%/%htm%'
                        $condition_session
                     ORDER BY id ASC";

        $res_quiz = Database::query($sql_quiz);
        $res_hot = Database::query($sql_hot);

        $currentUrl = api_get_self().'?'.api_get_cidreq().'&action=add_item&type=step&lp_id='.$this->lp_id.'#resource_tab-2';

        // Create a search-box
        $form = new FormValidator('search_simple', 'get', $currentUrl);
        $form->addHidden('action', 'add_item');
        $form->addHidden('type', 'step');
        $form->addHidden('lp_id', $this->lp_id);
        $form->addHidden('lp_build_selected', '2');

        $form->addCourseHiddenParams();
        $form->addText(
            'keyword',
            get_lang('Search'),
            false,
            [
                'aria-label' => get_lang('Search'),
            ]
        );

        if (api_get_configuration_value('allow_exercise_categories')) {
            $manager = new ExerciseCategoryManager();
            $options = $manager->getCategoriesForSelect(api_get_course_int_id());
            if (!empty($options)) {
                $form->addSelect(
                    'category_id',
                    get_lang('Category'),
                    $options,
                    ['placeholder' => get_lang('SelectAnOption')]
                );
            }
        }

        $form->addButtonSearch(get_lang('Search'));
        $return = $form->returnForm();

        $return .= '<ul class="lp_resource">';

        $return .= '<li class="lp_resource_element">';
        $return .= Display::return_icon('new_exercice.png');
        $return .= '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/exercise_admin.php?'.api_get_cidreq().'&lp_id='.$this->lp_id.'">'.
            get_lang('NewExercise').'</a>';
        $return .= '</li>';

        $previewIcon = Display::return_icon(
            'preview_view.png',
            get_lang('Preview')
        );
        $quizIcon = Display::return_icon('quiz.png', '', [], ICON_SIZE_TINY);
        $moveIcon = Display::return_icon('move_everywhere.png', get_lang('Move'), [], ICON_SIZE_TINY);
        $exerciseUrl = api_get_path(WEB_CODE_PATH).'exercise/showinframes.php?'.api_get_cidreq();

        // Display hotpotatoes
        while ($row_hot = Database::fetch_array($res_hot)) {
            $link = Display::url(
                $previewIcon,
                $exerciseUrl.'&file='.$row_hot['path'],
                ['target' => '_blank']
            );
            $return .= '<li class="lp_resource_element" data_id="'.$row_hot['id'].'" data_type="hotpotatoes" title="'.$row_hot['title'].'" >';
            $return .= '<a class="moved" href="#">';
            $return .= Display::return_icon(
                'move_everywhere.png',
                get_lang('Move'),
                [],
                ICON_SIZE_TINY
            );
            $return .= '</a> ';
            $return .= Display::return_icon('hotpotatoes_s.png');
            $return .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=add_item&type='.TOOL_HOTPOTATOES.'&file='.$row_hot['id'].'&lp_id='.$this->lp_id.'">'.
                ((!empty($row_hot['comment'])) ? $row_hot['comment'] : Security::remove_XSS($row_hot['title'])).$link.'</a>';
            $return .= '</li>';
        }

        $exerciseUrl = api_get_path(WEB_CODE_PATH).'exercise/overview.php?'.api_get_cidreq();
        while ($row_quiz = Database::fetch_array($res_quiz)) {
            $title = strip_tags(
                api_html_entity_decode($row_quiz['title'])
            );

            $visibility = api_get_item_visibility(
                ['real_id' => $course_id],
                TOOL_QUIZ,
                $row_quiz['iid'],
                $session_id
            );

            $link = Display::url(
                $previewIcon,
                $exerciseUrl.'&exerciseId='.$row_quiz['iid'],
                ['target' => '_blank']
            );
            $return .= '<li class="lp_resource_element" data_id="'.$row_quiz['iid'].'" data_type="quiz" title="'.$title.'" >';
            $return .= Display::url($moveIcon, '#', ['class' => 'moved']);
            $return .= $quizIcon;
            $sessionStar = api_get_session_image(
                $row_quiz['session_id'],
                $userInfo['status']
            );
            $return .= Display::url(
                Security::remove_XSS(cut($title, 80)).$link.$sessionStar,
                api_get_self().'?'.api_get_cidreq().'&action=add_item&type='.TOOL_QUIZ.'&file='.$row_quiz['iid'].'&lp_id='.$this->lp_id,
                [
                    'class' => $visibility == 0 ? 'moved text-muted' : 'moved',
                ]
            );
            $return .= '</li>';
        }

        $return .= '</ul>';

        return $return;
    }

    /**
     * Creates a list with all the links in it.
     *
     * @return string
     */
    public function get_links()
    {
        $selfUrl = api_get_self();
        $courseIdReq = api_get_cidreq();
        $course = api_get_course_info();
        $userInfo = api_get_user_info();

        $course_id = $course['real_id'];
        $tbl_link = Database::get_course_table(TABLE_LINK);
        $linkCategoryTable = Database::get_course_table(TABLE_LINK_CATEGORY);
        $moveEverywhereIcon = Display::return_icon(
            'move_everywhere.png',
            get_lang('Move'),
            [],
            ICON_SIZE_TINY
        );

        $session_id = api_get_session_id();
        $condition_session = api_get_session_condition(
            $session_id,
            true,
            true,
            'link.session_id'
        );

        $sql = "SELECT
                    link.id as link_id,
                    link.title as link_title,
                    link.session_id as link_session_id,
                    link.category_id as category_id,
                    link_category.category_title as category_title
                FROM $tbl_link as link
                LEFT JOIN $linkCategoryTable as link_category
                ON (link.category_id = link_category.id AND link.c_id = link_category.c_id)
                WHERE link.c_id = $course_id $condition_session
                ORDER BY link_category.category_title ASC, link.title ASC";
        $result = Database::query($sql);
        $categorizedLinks = [];
        $categories = [];

        while ($link = Database::fetch_array($result)) {
            if (!$link['category_id']) {
                $link['category_title'] = get_lang('Uncategorized');
            }
            $categories[$link['category_id']] = $link['category_title'];
            $categorizedLinks[$link['category_id']][$link['link_id']] = $link;
        }

        $linksHtmlCode =
            '<script>
            function toggle_tool(tool, id) {
                if(document.getElementById(tool+"_"+id+"_content").style.display == "none"){
                    document.getElementById(tool+"_"+id+"_content").style.display = "block";
                    document.getElementById(tool+"_"+id+"_opener").src = "'.Display::returnIconPath('remove.gif').'";
                } else {
                    document.getElementById(tool+"_"+id+"_content").style.display = "none";
                    document.getElementById(tool+"_"+id+"_opener").src = "'.Display::returnIconPath('add.gif').'";
                }
            }
        </script>

        <ul class="lp_resource">
            <li class="lp_resource_element">
                '.Display::return_icon('linksnew.gif').'
                <a href="'.api_get_path(WEB_CODE_PATH).'link/link.php?'.$courseIdReq.'&action=addlink&lp_id='.$this->lp_id.'" title="'.get_lang('LinkAdd').'">'.
                get_lang('LinkAdd').'
                </a>
            </li>';

        foreach ($categorizedLinks as $categoryId => $links) {
            $linkNodes = null;
            foreach ($links as $key => $linkInfo) {
                $title = $linkInfo['link_title'];
                $linkSessionId = $linkInfo['link_session_id'];

                $link = Display::url(
                    Display::return_icon('preview_view.png', get_lang('Preview')),
                    api_get_path(WEB_CODE_PATH).'link/link_goto.php?'.api_get_cidreq().'&link_id='.$key,
                    ['target' => '_blank']
                );

                if (api_get_item_visibility($course, TOOL_LINK, $key, $session_id) != 2) {
                    $sessionStar = api_get_session_image($linkSessionId, $userInfo['status']);
                    $linkNodes .=
                        '<li class="lp_resource_element" data_id="'.$key.'" data_type="'.TOOL_LINK.'" title="'.$title.'" >
                        <a class="moved" href="#">'.
                            $moveEverywhereIcon.
                        '</a>
                        '.Display::return_icon('links.png', '', [], ICON_SIZE_TINY).'
                        <a class="moved" href="'.$selfUrl.'?'.$courseIdReq.'&action=add_item&type='.
                        TOOL_LINK.'&file='.$key.'&lp_id='.$this->lp_id.'">'.
                        Security::remove_XSS($title).$sessionStar.$link.
                        '</a>
                    </li>';
                }
            }
            $linksHtmlCode .=
                '<li>
                <a style="cursor:hand" onclick="javascript: toggle_tool(\''.TOOL_LINK.'\','.$categoryId.')" style="vertical-align:middle">
                    <img src="'.Display::returnIconPath('add.gif').'" id="'.TOOL_LINK.'_'.$categoryId.'_opener"
                    align="absbottom" />
                </a>
                <span style="vertical-align:middle">'.Security::remove_XSS($categories[$categoryId]).'</span>
            </li>
            <div style="display:none" id="'.TOOL_LINK.'_'.$categoryId.'_content">'.$linkNodes.'</div>';
        }
        $linksHtmlCode .= '</ul>';

        return $linksHtmlCode;
    }

    /**
     * Creates a list with all the surveys in it.
     *
     * @return string
     */
    public function getSurveys()
    {
        $return = '<ul class="lp_resource">';
        // First add link
        $return .= '<li class="lp_resource_element">';
        $return .= Display::return_icon('new_survey.png', get_lang('CreateNewSurvey'), '', ICON_SIZE_MEDIUM);
        $return .= Display::url(
            get_lang('CreateANewSurvey'),
            api_get_path(WEB_CODE_PATH).'survey/create_new_survey.php?'.api_get_cidreq().'&'.http_build_query([
                'action' => 'add',
                'lp_id' => $this->lp_id,
            ]),
            ['title' => get_lang('CreateNewSurvey')]
        );
        $return .= '</li>';

        $surveys = SurveyManager::get_surveys(api_get_course_id(), api_get_session_id());

        foreach ($surveys as $survey) {
            if (!empty($survey['survey_id'])) {
                $surveyTitle = strip_tags($survey['title']);
                $return .= '<li class="lp_resource_element" data_id="'.$survey['survey_id'].'" data_type="'.TOOL_SURVEY.'" title="'.$surveyTitle.'" >';
                $return .= '<a class="moved" href="#">';
                $return .= Display::return_icon('move_everywhere.png', get_lang('Move'), [], ICON_SIZE_TINY);
                $return .= ' </a>';
                $return .= Display::return_icon('survey.png', '', [], ICON_SIZE_TINY);
                $return .= '<a class="moved" href="'.api_get_self().'?'.api_get_cidreq().'&action=add_item&type='.TOOL_SURVEY.'&survey_id='.$survey['survey_id'].'&lp_id='.$this->lp_id.'" style="vertical-align:middle">'.$surveyTitle.'</a>';
                $return .= '</li>';
            }
        }

        $return .= '</ul>';

        return $return;
    }

    /**
     * Creates a list with all the student publications in it.
     *
     * @return string
     */
    public function get_student_publications()
    {
        $return = '<ul class="lp_resource">';
        $return .= '<li class="lp_resource_element">';
        $return .= Display::return_icon('works_new.gif');
        $return .= ' <a href="'.api_get_self().'?'.api_get_cidreq().'&action=add_item&type='.TOOL_STUDENTPUBLICATION.'&lp_id='.$this->lp_id.'">'.
            get_lang('AddAssignmentPage').'</a>';
        $return .= '</li>';

        require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';
        $works = getWorkListTeacher(0, 100, null, null, null);
        if (!empty($works)) {
            foreach ($works as $work) {
                $link = Display::url(
                    Display::return_icon('preview_view.png', get_lang('Preview')),
                    api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq().'&id='.$work['iid'],
                    ['target' => '_blank']
                );

                $return .= '<li class="lp_resource_element" data_id="'.$work['iid'].'" data_type="'.TOOL_STUDENTPUBLICATION.'" title="'.Security::remove_XSS(cut(strip_tags($work['title']), 80)).'">';
                $return .= '<a class="moved" href="#">';
                $return .= Display::return_icon(
                    'move_everywhere.png',
                    get_lang('Move'),
                    [],
                    ICON_SIZE_TINY
                );
                $return .= '</a> ';

                $return .= Display::return_icon('works.png', '', [], ICON_SIZE_TINY);
                $return .= ' <a class="moved" href="'.api_get_self().'?'.api_get_cidreq().'&action=add_item&type='.TOOL_STUDENTPUBLICATION.'&file='.$work['iid'].'&lp_id='.$this->lp_id.'">'.
                    Security::remove_XSS(cut(strip_tags($work['title']), 80)).' '.$link.'
                </a>';

                $return .= '</li>';
            }
        }

        $return .= '</ul>';

        return $return;
    }

    /**
     * Creates a list with all the forums in it.
     *
     * @return string
     */
    public function get_forums()
    {
        require_once '../forum/forumfunction.inc.php';

        $forumCategories = get_forum_categories();
        $forumsInNoCategory = get_forums_in_category(0);
        if (!empty($forumsInNoCategory)) {
            $forumCategories = array_merge(
                $forumCategories,
                [
                    [
                        'cat_id' => 0,
                        'session_id' => 0,
                        'visibility' => 1,
                        'cat_comment' => null,
                    ],
                ]
            );
        }

        $forumList = get_forums();
        $a_forums = [];
        foreach ($forumCategories as $forumCategory) {
            // The forums in this category.
            $forumsInCategory = get_forums_in_category($forumCategory['cat_id']);
            if (!empty($forumsInCategory)) {
                foreach ($forumList as $forum) {
                    if (isset($forum['forum_category']) &&
                        $forum['forum_category'] == $forumCategory['cat_id']
                    ) {
                        $a_forums[] = $forum;
                    }
                }
            }
        }

        $return = '<ul class="lp_resource">';

        // First add link
        $return .= '<li class="lp_resource_element">';
        $return .= Display::return_icon('new_forum.png');
        $return .= Display::url(
            get_lang('CreateANewForum'),
            api_get_path(WEB_CODE_PATH).'forum/index.php?'.api_get_cidreq().'&'.http_build_query([
                'action' => 'add',
                'content' => 'forum',
                'lp_id' => $this->lp_id,
            ]),
            ['title' => get_lang('CreateANewForum')]
        );
        $return .= '</li>';

        $return .= '<script>
            function toggle_forum(forum_id) {
                if (document.getElementById("forum_"+forum_id+"_content").style.display == "none") {
                    document.getElementById("forum_"+forum_id+"_content").style.display = "block";
                    document.getElementById("forum_"+forum_id+"_opener").src = "'.Display::returnIconPath('remove.gif').'";
                } else {
                    document.getElementById("forum_"+forum_id+"_content").style.display = "none";
                    document.getElementById("forum_"+forum_id+"_opener").src = "'.Display::returnIconPath('add.gif').'";
                }
            }
        </script>';

        foreach ($a_forums as $forum) {
            if (!empty($forum['forum_id'])) {
                $link = Display::url(
                    Display::return_icon('preview_view.png', get_lang('Preview')),
                    api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.api_get_cidreq().'&forum='.$forum['forum_id'],
                    ['target' => '_blank']
                );

                $return .= '<li class="lp_resource_element" data_id="'.$forum['forum_id'].'" data_type="'.TOOL_FORUM.'" title="'.$forum['forum_title'].'" >';
                $return .= '<a class="moved" href="#">';
                $return .= Display::return_icon('move_everywhere.png', get_lang('Move'), [], ICON_SIZE_TINY);
                $return .= ' </a>';
                $return .= Display::return_icon('forum.png', '', [], ICON_SIZE_TINY);
                $return .= '<a onclick="javascript:toggle_forum('.$forum['forum_id'].');" style="cursor:hand; vertical-align:middle">
                                <img src="'.Display::returnIconPath('add.gif').'" id="forum_'.$forum['forum_id'].'_opener" align="absbottom" />
                            </a>
                            <a class="moved" href="'.api_get_self().'?'.api_get_cidreq().'&action=add_item&type='.TOOL_FORUM.'&forum_id='.$forum['forum_id'].'&lp_id='.$this->lp_id.'" style="vertical-align:middle">'.
                    Security::remove_XSS($forum['forum_title']).' '.$link.'</a>';

                $return .= '</li>';

                $return .= '<div style="display:none" id="forum_'.$forum['forum_id'].'_content">';
                $a_threads = get_threads($forum['forum_id']);
                if (is_array($a_threads)) {
                    foreach ($a_threads as $thread) {
                        $link = Display::url(
                            Display::return_icon('preview_view.png', get_lang('Preview')),
                            api_get_path(WEB_CODE_PATH).'forum/viewthread.php?'.api_get_cidreq().'&forum='.$forum['forum_id'].'&thread='.$thread['thread_id'],
                            ['target' => '_blank']
                        );

                        $return .= '<li class="lp_resource_element" data_id="'.$thread['thread_id'].'" data_type="'.TOOL_THREAD.'" title="'.$thread['thread_title'].'" >';
                        $return .= '&nbsp;<a class="moved" href="#">';
                        $return .= Display::return_icon('move_everywhere.png', get_lang('Move'), [], ICON_SIZE_TINY);
                        $return .= ' </a>';
                        $return .= Display::return_icon('forumthread.png', get_lang('Thread'), [], ICON_SIZE_TINY);
                        $return .= '<a class="moved" href="'.api_get_self().'?'.api_get_cidreq().'&action=add_item&type='.TOOL_THREAD.'&thread_id='.$thread['thread_id'].'&lp_id='.$this->lp_id.'">'.
                            Security::remove_XSS($thread['thread_title']).' '.$link.'</a>';
                        $return .= '</li>';
                    }
                }
                $return .= '</div>';
            }
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
     *
     * @TODO The method might be redefined later on in the scorm class itself to avoid
     * creating a SCORM structure if there is one already. However, if the initial SCORM
     * path has been modified, it should use the generic method here below.
     *
     * @return string Returns the zip package string, or null if error
     */
    public function scormExport()
    {
        api_set_more_memory_and_time_limits();

        $_course = api_get_course_info();
        $course_id = $_course['real_id'];
        // Create the zip handler (this will remain available throughout the method).
        $archivePath = api_get_path(SYS_ARCHIVE_PATH);
        $sys_course_path = api_get_path(SYS_COURSE_PATH);
        $temp_dir_short = uniqid('scorm_export', true);
        $temp_zip_dir = $archivePath.'/'.$temp_dir_short;
        $temp_zip_file = $temp_zip_dir.'/'.md5(time()).'.zip';
        $zip_folder = new PclZip($temp_zip_file);
        $current_course_path = api_get_path(SYS_COURSE_PATH).api_get_course_path();
        $root_path = $main_path = api_get_path(SYS_PATH);
        $files_cleanup = [];

        // Place to temporarily stash the zip file.
        // create the temp dir if it doesn't exist
        // or do a cleanup before creating the zip file.
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
        $zip_files = $zip_files_abs = $zip_files_dist = [];
        if (is_dir($current_course_path.'/scorm/'.$this->path) &&
            is_file($current_course_path.'/scorm/'.$this->path.'/imsmanifest.xml')
        ) {
            // Remove the possible . at the end of the path.
            $dest_path_to_lp = substr($this->path, -1) == '.' ? substr($this->path, 0, -1) : $this->path;
            $dest_path_to_scorm_folder = str_replace('//', '/', $temp_zip_dir.'/scorm/'.$dest_path_to_lp);
            mkdir(
                $dest_path_to_scorm_folder,
                api_get_permissions_for_new_directories(),
                true
            );
            copyr(
                $current_course_path.'/scorm/'.$this->path,
                $dest_path_to_scorm_folder,
                ['imsmanifest'],
                $zip_files
            );
        }

        // Build a dummy imsmanifest structure.
        // Do not add to the zip yet (we still need it).
        // This structure is developed following regulations for SCORM 1.2 packaging in the SCORM 1.2 Content
        // Aggregation Model official document, section "2.3 Content Packaging".
        // We are going to build a UTF-8 encoded manifest.
        // Later we will recode it to the desired (and supported) encoding.
        $xmldoc = new DOMDocument('1.0');
        $root = $xmldoc->createElement('manifest');
        $root->setAttribute('identifier', 'SingleCourseManifest');
        $root->setAttribute('version', '1.1');
        $root->setAttribute('xmlns', 'http://www.imsproject.org/xsd/imscp_rootv1p1p2');
        $root->setAttribute('xmlns:adlcp', 'http://www.adlnet.org/xsd/adlcp_rootv1p2');
        $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $root->setAttribute(
            'xsi:schemaLocation',
            'http://www.imsproject.org/xsd/imscp_rootv1p1p2 imscp_rootv1p1p2.xsd http://www.imsglobal.org/xsd/imsmd_rootv1p2p1 imsmd_rootv1p2p1.xsd http://www.adlnet.org/xsd/adlcp_rootv1p2 adlcp_rootv1p2.xsd'
        );
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
        $folder_name = 'document';

        // Removes the learning_path/scorm_folder path when exporting see #4841
        $path_to_remove = '';
        $path_to_replace = '';
        $result = $this->generate_lp_folder($_course);
        if (isset($result['dir']) && strpos($result['dir'], 'learning_path')) {
            $path_to_remove = 'document'.$result['dir'];
            $path_to_replace = $folder_name.'/';
        }

        // Fixes chamilo scorm exports
        if ($this->ref === 'chamilo_scorm_export') {
            $path_to_remove = 'scorm/'.$this->path.'/document/';
        }

        // For each element, add it to the imsmanifest structure, then add it to the zip.
        $link_updates = [];
        $links_to_create = [];
        foreach ($this->ordered_items as $index => $itemId) {
            /** @var learnpathItem $item */
            $item = $this->items[$itemId];
            if (!in_array($item->type, [TOOL_QUIZ, TOOL_FORUM, TOOL_THREAD, TOOL_LINK, TOOL_STUDENTPUBLICATION])) {
                // Get included documents from this item.
                if ($item->type === 'sco') {
                    $inc_docs = $item->get_resources_from_source(
                        null,
                        $current_course_path.'/scorm/'.$this->path.'/'.$item->get_path()
                    );
                } else {
                    $inc_docs = $item->get_resources_from_source();
                }

                // Give a child element <item> to the <organization> element.
                $my_item_id = $item->get_id();
                $my_item = $xmldoc->createElement('item');
                $my_item->setAttribute('identifier', 'ITEM_'.$my_item_id);
                $my_item->setAttribute('identifierref', 'RESOURCE_'.$my_item_id);
                $my_item->setAttribute('isvisible', 'true');
                // Give a child element <title> to the <item> element.
                $my_title = $xmldoc->createElement(
                    'title',
                    htmlspecialchars(
                        api_utf8_encode($item->get_title()),
                        ENT_QUOTES,
                        'UTF-8'
                    )
                );
                $my_item->appendChild($my_title);
                // Give a child element <adlcp:prerequisites> to the <item> element.
                $my_prereqs = $xmldoc->createElement(
                    'adlcp:prerequisites',
                    $this->get_scorm_prereq_string($my_item_id)
                );
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
                    $possible_parent = $this->get_scorm_xml_node($children, 'ITEM_'.$item->parent);
                    if (is_object($possible_parent)) {
                        $possible_parent->appendChild($my_item);
                    } else {
                        if ($this->debug > 0) {
                            error_log('Parent ITEM_'.$item->parent.' of item ITEM_'.$my_item_id.' not found');
                        }
                    }
                } else {
                    if ($this->debug > 0) {
                        error_log('No parent');
                    }
                    $organization->appendChild($my_item);
                }

                // Get the path of the file(s) from the course directory root.
                $my_file_path = $item->get_file_path('scorm/'.$this->path.'/');
                $my_xml_file_path = $my_file_path;
                if (!empty($path_to_remove)) {
                    // From docs
                    $my_xml_file_path = str_replace($path_to_remove, $path_to_replace, $my_file_path);

                    // From quiz
                    if ($this->ref === 'chamilo_scorm_export') {
                        $path_to_remove = 'scorm/'.$this->path.'/';
                        $my_xml_file_path = str_replace($path_to_remove, '', $my_file_path);
                    }
                }

                $my_sub_dir = dirname($my_file_path);
                $my_sub_dir = str_replace('\\', '/', $my_sub_dir);
                $my_xml_sub_dir = $my_sub_dir;
                // Give a <resource> child to the <resources> element
                $my_resource = $xmldoc->createElement('resource');
                $my_resource->setAttribute('identifier', 'RESOURCE_'.$item->get_id());
                $my_resource->setAttribute('type', 'webcontent');
                $my_resource->setAttribute('href', $my_xml_file_path);
                // adlcp:scormtype can be either 'sco' or 'asset'.
                if ($item->type === 'sco') {
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
                if ($inc_docs) {
                    foreach ($inc_docs as $doc_info) {
                        if (count($doc_info) < 1 || empty($doc_info[0])) {
                            continue;
                        }
                        $my_dep = $xmldoc->createElement('resource');
                        $res_id = 'RESOURCE_'.$item->get_id().'_'.$i;
                        $my_dep->setAttribute('identifier', $res_id);
                        $my_dep->setAttribute('type', 'webcontent');
                        $my_dep->setAttribute('adlcp:scormtype', 'asset');
                        $my_dep_file = $xmldoc->createElement('file');
                        // Check type of URL.
                        if ($doc_info[1] == 'remote') {
                            // Remote file. Save url as is.
                            $my_dep_file->setAttribute('href', $doc_info[0]);
                            $my_dep->setAttribute('xml:base', '');
                        } elseif ($doc_info[1] === 'local') {
                            switch ($doc_info[2]) {
                                case 'url':
                                    // Local URL - save path as url for now, don't zip file.
                                    $abs_path = api_get_path(SYS_PATH).
                                        str_replace(api_get_path(WEB_PATH), '', $doc_info[0]);
                                    $current_dir = dirname($abs_path);
                                    $current_dir = str_replace('\\', '/', $current_dir);
                                    $file_path = realpath($abs_path);
                                    $file_path = str_replace('\\', '/', $file_path);
                                    $my_dep_file->setAttribute('href', $file_path);
                                    $my_dep->setAttribute('xml:base', '');
                                    if (strstr($file_path, $main_path) !== false) {
                                        // The calculated real path is really inside Chamilo's root path.
                                        // Reduce file path to what's under the DocumentRoot.
                                        $replace = $file_path;
                                        $file_path = substr($file_path, strlen($root_path) - 1);
                                        $destinationFile = $file_path;

                                        if (strstr($file_path, 'upload/users') !== false) {
                                            $pos = strpos($file_path, 'my_files/');
                                            if ($pos !== false) {
                                                $onlyDirectory = str_replace(
                                                    'upload/users/',
                                                    '',
                                                    substr($file_path, $pos, strlen($file_path))
                                                );
                                            }
                                            $replace = './'.$onlyDirectory;
                                            $destinationFile = $replace;
                                        }

                                        if (strpos($file_path, '/web') === 0) {
                                            $replace = str_replace('/web', 'web', $file_path);
                                        }

                                        $zip_files_abs[] = $file_path;
                                        $link_updates[$my_file_path][] = [
                                            'orig' => $doc_info[0],
                                            'dest' => $destinationFile,
                                            'replace' => $replace,
                                        ];
                                        $my_dep_file->setAttribute('href', $file_path);
                                        $my_dep->setAttribute('xml:base', '');
                                    } elseif (empty($file_path)) {
                                        $file_path = $_SERVER['DOCUMENT_ROOT'].$abs_path;
                                        $file_path = str_replace('//', '/', $file_path);
                                        if (file_exists($file_path)) {
                                            // We get the relative path.
                                            $file_path = substr($file_path, strlen($current_dir));
                                            $zip_files[] = $my_sub_dir.'/'.$file_path;
                                            $link_updates[$my_file_path][] = [
                                                'orig' => $doc_info[0],
                                                'dest' => $file_path,
                                            ];
                                            $my_dep_file->setAttribute('href', $file_path);
                                            $my_dep->setAttribute('xml:base', '');
                                        }
                                    }
                                    break;
                                case 'abs':
                                    // Absolute path from DocumentRoot. Save file and leave path as is in the zip.
                                    $my_dep_file->setAttribute('href', $doc_info[0]);
                                    $my_dep->setAttribute('xml:base', '');

                                    // The next lines fix a bug when using the "subdir" mode of Chamilo, whereas
                                    // an image path would be constructed as /var/www/subdir/subdir/img/foo.bar
                                    $abs_img_path_without_subdir = $doc_info[0];
                                    $relp = api_get_path(REL_PATH); // The url-append config param.
                                    $pos = strpos($abs_img_path_without_subdir, $relp);
                                    if ($pos === 0) {
                                        $abs_img_path_without_subdir = trim('/'.substr($abs_img_path_without_subdir, strlen($relp)));
                                    }

                                    $file_path = realpath(api_get_path(SYS_APP_PATH).$abs_img_path_without_subdir);
                                    $file_path = str_replace(['\\', '//'], '/', $file_path);

                                    // Prepare the current directory path (until just under 'document') with a trailing slash.
                                    $cur_path = substr($current_course_path, -1) == '/' ? $current_course_path : $current_course_path.'/';
                                    // Check if the current document is in that path.
                                    if (strstr($file_path, $cur_path) !== false) {
                                        $destinationFile = substr($file_path, strlen($cur_path));
                                        $filePathNoCoursePart = substr($file_path, strlen($cur_path));

                                        $fileToTest = $cur_path.$my_file_path;
                                        if (!empty($path_to_remove)) {
                                            $fileToTest = str_replace(
                                                $path_to_remove.'/',
                                                $path_to_replace,
                                                $cur_path.$my_file_path
                                            );
                                        }

                                        $relative_path = api_get_relative_path($fileToTest, $file_path);

                                        // Put the current document in the zip (this array is the array
                                        // that will manage documents already in the course folder - relative).
                                        $zip_files[] = $filePathNoCoursePart;
                                        // Update the links to the current document in the
                                        // containing document (make them relative).
                                        $link_updates[$my_file_path][] = [
                                            'orig' => $doc_info[0],
                                            'dest' => $destinationFile,
                                            'replace' => $relative_path,
                                        ];

                                        $my_dep_file->setAttribute('href', $file_path);
                                        $my_dep->setAttribute('xml:base', '');
                                    } elseif (strstr($file_path, $main_path) !== false) {
                                        // The calculated real path is really inside Chamilo's root path.
                                        // Reduce file path to what's under the DocumentRoot.
                                        $file_path = substr($file_path, strlen($root_path));
                                        $zip_files_abs[] = $file_path;
                                        $link_updates[$my_file_path][] = ['orig' => $doc_info[0], 'dest' => $file_path];
                                        $my_dep_file->setAttribute('href', 'document/'.$file_path);
                                        $my_dep->setAttribute('xml:base', '');
                                    } elseif (empty($file_path)) {
                                        // Probably this is an image inside "/main" directory
                                        $file_path = api_get_path(SYS_PATH).$abs_img_path_without_subdir;
                                        $abs_path = api_get_path(SYS_PATH).str_replace(api_get_path(WEB_PATH), '', $doc_info[0]);

                                        if (file_exists($file_path)) {
                                            $pos = strpos($file_path, 'main/default_course_document/');
                                            if ($pos !== false) {
                                                // We get the relative path.
                                                $onlyDirectory = str_replace(
                                                    'main/default_course_document/',
                                                    '',
                                                    substr($file_path, $pos, strlen($file_path))
                                                );

                                                $destinationFile = 'default_course_document/'.$onlyDirectory;
                                                $fileAbs = substr($file_path, strlen(api_get_path(SYS_PATH)));
                                                $zip_files_abs[] = $fileAbs;
                                                $link_updates[$my_file_path][] = [
                                                    'orig' => $doc_info[0],
                                                    'dest' => $destinationFile,
                                                ];
                                                $my_dep_file->setAttribute('href', 'document/'.$destinationFile);
                                                $my_dep->setAttribute('xml:base', '');
                                            }
                                        }
                                    }
                                    break;
                                case 'rel':
                                    // Path relative to the current document.
                                    // Save xml:base as current document's directory and save file in zip as subdir.file_path
                                    if (substr($doc_info[0], 0, 2) === '..') {
                                        // Relative path going up.
                                        $current_dir = dirname($current_course_path.'/'.$item->get_file_path()).'/';
                                        $current_dir = str_replace('\\', '/', $current_dir);
                                        $file_path = realpath($current_dir.$doc_info[0]);
                                        $file_path = str_replace('\\', '/', $file_path);
                                        if (strstr($file_path, $main_path) !== false) {
                                            // The calculated real path is really inside Chamilo's root path.
                                            // Reduce file path to what's under the DocumentRoot.
                                            $file_path = substr($file_path, strlen($root_path));
                                            $zip_files_abs[] = $file_path;
                                            $link_updates[$my_file_path][] = ['orig' => $doc_info[0], 'dest' => $file_path];
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
                }
                $resources->appendChild($my_resource);
                $zip_files[] = $my_file_path;
            } else {
                // If the item is a quiz or a link or whatever non-exportable, we include a step indicating it.
                switch ($item->type) {
                    case TOOL_LINK:
                        $my_item = $xmldoc->createElement('item');
                        $my_item->setAttribute('identifier', 'ITEM_'.$item->get_id());
                        $my_item->setAttribute('identifierref', 'RESOURCE_'.$item->get_id());
                        $my_item->setAttribute('isvisible', 'true');
                        // Give a child element <title> to the <item> element.
                        $my_title = $xmldoc->createElement(
                            'title',
                            htmlspecialchars(
                                api_utf8_encode($item->get_title()),
                                ENT_QUOTES,
                                'UTF-8'
                            )
                        );
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
                                if ($item_temp->nodeName == 'item') {
                                    if ($item_temp->getAttribute('identifier') == 'ITEM_'.$item->parent) {
                                        $item_temp->appendChild($my_item);
                                    }
                                }
                            }
                        } else {
                            $organization->appendChild($my_item);
                        }

                        $my_file_path = 'link_'.$item->get_id().'.html';
                        $sql = 'SELECT url, title FROM '.Database::get_course_table(TABLE_LINK).'
                                WHERE c_id = '.$course_id.' AND id = '.$item->path;
                        $rs = Database::query($sql);
                        if ($link = Database::fetch_array($rs)) {
                            $url = $link['url'];
                            $title = stripslashes($link['title']);
                            $links_to_create[$my_file_path] = ['title' => $title, 'url' => $url];
                            $my_xml_file_path = $my_file_path;
                            $my_sub_dir = dirname($my_file_path);
                            $my_sub_dir = str_replace('\\', '/', $my_sub_dir);
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
                        break;
                    case TOOL_QUIZ:
                        $exe_id = $item->path;
                        // Should be using ref when everything will be cleaned up in this regard.
                        $exe = new Exercise();
                        $exe->read($exe_id);
                        $my_item = $xmldoc->createElement('item');
                        $my_item->setAttribute('identifier', 'ITEM_'.$item->get_id());
                        $my_item->setAttribute('identifierref', 'RESOURCE_'.$item->get_id());
                        $my_item->setAttribute('isvisible', 'true');
                        // Give a child element <title> to the <item> element.
                        $my_title = $xmldoc->createElement(
                            'title',
                            htmlspecialchars(
                                api_utf8_encode($item->get_title()),
                                ENT_QUOTES,
                                'UTF-8'
                            )
                        );
                        $my_item->appendChild($my_title);
                        $my_max_score = $xmldoc->createElement('max_score', $item->get_max());
                        $my_item->appendChild($my_max_score);
                        // Give a child element <adlcp:prerequisites> to the <item> element.
                        $my_prereqs = $xmldoc->createElement('adlcp:prerequisites', $item->get_prereq_string());
                        $my_prereqs->setAttribute('type', 'aicc_script');
                        $my_item->appendChild($my_prereqs);
                        // Give a child element <adlcp:masteryscore> to the <item> element.
                        $my_masteryscore = $xmldoc->createElement('adlcp:masteryscore', $item->get_mastery_score());
                        $my_item->appendChild($my_masteryscore);

                        // Attach this item to the organization element or hits parent if there is one.
                        if (!empty($item->parent) && $item->parent != 0) {
                            $children = $organization->childNodes;
                            $possible_parent = $this->get_scorm_xml_node($children, 'ITEM_'.$item->parent);
                            if ($possible_parent) {
                                if ($possible_parent->getAttribute('identifier') === 'ITEM_'.$item->parent) {
                                    $possible_parent->appendChild($my_item);
                                }
                            }
                        } else {
                            $organization->appendChild($my_item);
                        }

                        // Get the path of the file(s) from the course directory root
                        //$my_file_path = $item->get_file_path('scorm/'.$this->path.'/');
                        $my_file_path = 'quiz_'.$item->get_id().'.html';
                        // Write the contents of the exported exercise into a (big) html file
                        // to later pack it into the exported SCORM. The file will be removed afterwards.
                        $scormExercise = new ScormExercise($exe, true);
                        $contents = $scormExercise->export();

                        $tmp_file_path = $archivePath.$temp_dir_short.'/'.$my_file_path;
                        $res = file_put_contents($tmp_file_path, $contents);
                        if ($res === false) {
                            error_log('Could not write into file '.$tmp_file_path.' '.__FILE__.' '.__LINE__, 0);
                        }
                        $files_cleanup[] = $tmp_file_path;
                        $my_xml_file_path = $my_file_path;
                        $my_sub_dir = dirname($my_file_path);
                        $my_sub_dir = str_replace('\\', '/', $my_sub_dir);
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
                        $inc_docs = $item->get_resources_from_source(null, $tmp_file_path);

                        // Dependency to other files - not yet supported.
                        $i = 1;
                        foreach ($inc_docs as $doc_info) {
                            if (count($doc_info) < 1 || empty($doc_info[0])) {
                                continue;
                            }
                            $my_dep = $xmldoc->createElement('resource');
                            $res_id = 'RESOURCE_'.$item->get_id().'_'.$i;
                            $my_dep->setAttribute('identifier', $res_id);
                            $my_dep->setAttribute('type', 'webcontent');
                            $my_dep->setAttribute('adlcp:scormtype', 'asset');
                            $my_dep_file = $xmldoc->createElement('file');
                            // Check type of URL.
                            if ($doc_info[1] == 'remote') {
                                // Remote file. Save url as is.
                                $my_dep_file->setAttribute('href', $doc_info[0]);
                                $my_dep->setAttribute('xml:base', '');
                            } elseif ($doc_info[1] == 'local') {
                                switch ($doc_info[2]) {
                                    case 'url': // Local URL - save path as url for now, don't zip file.
                                        // Save file but as local file (retrieve from URL).
                                        $abs_path = api_get_path(SYS_PATH).
                                            str_replace(api_get_path(WEB_PATH), '', $doc_info[0]);
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
                                            $zip_files_abs[] = $file_path;
                                            $link_updates[$my_file_path][] = [
                                                'orig' => $doc_info[0],
                                                'dest' => 'document/'.$file_path,
                                            ];
                                            $my_dep_file->setAttribute('href', 'document/'.$file_path);
                                            $my_dep->setAttribute('xml:base', '');
                                        } elseif (empty($file_path)) {
                                            $file_path = $_SERVER['DOCUMENT_ROOT'].$abs_path;
                                            $file_path = str_replace('//', '/', $file_path);
                                            if (file_exists($file_path)) {
                                                $file_path = substr($file_path, strlen($current_dir));
                                                // We get the relative path.
                                                $zip_files[] = $my_sub_dir.'/'.$file_path;
                                                $link_updates[$my_file_path][] = [
                                                    'orig' => $doc_info[0],
                                                    'dest' => 'document/'.$file_path,
                                                ];
                                                $my_dep_file->setAttribute('href', 'document/'.$file_path);
                                                $my_dep->setAttribute('xml:base', '');
                                            }
                                        }
                                        break;
                                    case 'abs':
                                        // Absolute path from DocumentRoot. Save file and leave path as is in the zip.
                                        $current_dir = dirname($current_course_path.'/'.$item->get_file_path()).'/';
                                        $current_dir = str_replace('\\', '/', $current_dir);
                                        $file_path = realpath($doc_info[0]);
                                        $file_path = str_replace('\\', '/', $file_path);
                                        $my_dep_file->setAttribute('href', $file_path);
                                        $my_dep->setAttribute('xml:base', '');

                                        if (strstr($file_path, $main_path) !== false) {
                                            // The calculated real path is really inside the chamilo root path.
                                            // Reduce file path to what's under the DocumentRoot.
                                            $file_path = substr($file_path, strlen($root_path));
                                            $zip_files_abs[] = $file_path;
                                            $link_updates[$my_file_path][] = [
                                                'orig' => $doc_info[0],
                                                'dest' => $file_path,
                                            ];
                                            $my_dep_file->setAttribute('href', 'document/'.$file_path);
                                            $my_dep->setAttribute('xml:base', '');
                                        } elseif (empty($file_path)) {
                                            $docSysPartPath = str_replace(
                                                api_get_path(REL_COURSE_PATH),
                                                '',
                                                $doc_info[0]
                                            );

                                            $docSysPartPathNoCourseCode = str_replace(
                                                $_course['directory'].'/',
                                                '',
                                                $docSysPartPath
                                            );

                                            $docSysPath = api_get_path(SYS_COURSE_PATH).$docSysPartPath;
                                            if (file_exists($docSysPath)) {
                                                $file_path = $docSysPartPathNoCourseCode;
                                                $zip_files[] = $my_sub_dir.'/'.$file_path;
                                                $link_updates[$my_file_path][] = [
                                                    'orig' => $doc_info[0],
                                                    'dest' => $file_path,
                                                ];
                                                $my_dep_file->setAttribute('href', 'document/'.$file_path);
                                                $my_dep->setAttribute('xml:base', '');
                                            }
                                        }
                                        break;
                                    case 'rel':
                                        // Path relative to the current document. Save xml:base as current document's
                                        // directory and save file in zip as subdir.file_path
                                        if (substr($doc_info[0], 0, 2) === '..') {
                                            // Relative path going up.
                                            $current_dir = dirname($current_course_path.'/'.$item->get_file_path()).'/';
                                            $current_dir = str_replace('\\', '/', $current_dir);
                                            $file_path = realpath($current_dir.$doc_info[0]);
                                            $file_path = str_replace('\\', '/', $file_path);
                                            if (strstr($file_path, $main_path) !== false) {
                                                // The calculated real path is really inside Chamilo's root path.
                                                // Reduce file path to what's under the DocumentRoot.

                                                $file_path = substr($file_path, strlen($root_path));
                                                $file_path_dest = $file_path;

                                                // File path is courses/CHAMILO/document/....
                                                $info_file_path = explode('/', $file_path);
                                                if ($info_file_path[0] == 'courses') {
                                                    // Add character "/" in file path.
                                                    $file_path_dest = 'document/'.$file_path;
                                                }
                                                $zip_files_abs[] = $file_path;

                                                $link_updates[$my_file_path][] = [
                                                    'orig' => $doc_info[0],
                                                    'dest' => $file_path_dest,
                                                ];
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
                        break;
                    default:
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
                        $my_resource->setAttribute('href', $folder_name.'/'.$my_xml_file_path);
                        // adlcp:scormtype can be either 'sco' or 'asset'.
                        $my_resource->setAttribute('adlcp:scormtype', 'asset');
                        // xml:base is the base directory to find the files declared in this resource.
                        $my_resource->setAttribute('xml:base', '');
                        // Give a <file> child to the <resource> element.
                        $my_file = $xmldoc->createElement('file');
                        $my_file->setAttribute('href', 'document/'.$my_xml_file_path);
                        $my_resource->appendChild($my_file);
                        $resources->appendChild($my_resource);
                        break;
                }
            }
        }
        $organizations->appendChild($organization);
        $root->appendChild($organizations);
        $root->appendChild($resources);
        $xmldoc->appendChild($root);

        $copyAll = api_get_configuration_value('add_all_files_in_lp_export');

        // then add the file to the zip, then destroy the file (this is done automatically).
        // http://www.reload.ac.uk/scormplayer.html - once done, don't forget to close FS#138
        foreach ($zip_files as $file_path) {
            if (empty($file_path)) {
                continue;
            }

            $filePath = $sys_course_path.$_course['path'].'/'.$file_path;
            $dest_file = $archivePath.$temp_dir_short.'/'.$file_path;

            if (!empty($path_to_remove) && !empty($path_to_replace)) {
                $dest_file = str_replace($path_to_remove, $path_to_replace, $dest_file);
            }

            $this->create_path($dest_file);
            @copy($filePath, $dest_file);

            // Check if the file needs a link update.
            if (in_array($file_path, array_keys($link_updates))) {
                $string = file_get_contents($dest_file);
                unlink($dest_file);
                foreach ($link_updates[$file_path] as $old_new) {
                    // This is an ugly hack that allows .flv files to be found by the flv player that
                    // will be added in document/main/inc/lib/flv_player/flv_player.swf and that needs
                    // to find the flv to play in document/main/, so we replace main/ in the flv path by
                    // ../../.. to return from inc/lib/flv_player to the document/main path.
                    if (substr($old_new['dest'], -3) === 'flv' &&
                        substr($old_new['dest'], 0, 5) === 'main/'
                    ) {
                        $old_new['dest'] = str_replace('main/', '../../../', $old_new['dest']);
                    } elseif (substr($old_new['dest'], -3) === 'flv' &&
                        substr($old_new['dest'], 0, 6) === 'video/'
                    ) {
                        $old_new['dest'] = str_replace('video/', '../../../../video/', $old_new['dest']);
                    }

                    // Fix to avoid problems with default_course_document
                    if (strpos('main/default_course_document', $old_new['dest']) === false) {
                        $newDestination = $old_new['dest'];
                        if (isset($old_new['replace']) && !empty($old_new['replace'])) {
                            $newDestination = $old_new['replace'];
                        }
                    } else {
                        $newDestination = str_replace('document/', '', $old_new['dest']);
                    }
                    $string = str_replace($old_new['orig'], $newDestination, $string);

                    // Add files inside the HTMLs
                    $new_path = str_replace(api_get_path(REL_COURSE_PATH), '', $old_new['orig']);
                    $destinationFile = $archivePath.$temp_dir_short.'/'.$old_new['dest'];
                    if (file_exists($sys_course_path.$new_path) && is_file($sys_course_path.$new_path)) {
                        copy(
                            $sys_course_path.$new_path,
                            $destinationFile
                        );
                    }
                }
                file_put_contents($dest_file, $string);
            }

            if (file_exists($filePath) && $copyAll) {
                $extension = $this->get_extension($filePath);
                if (in_array($extension, ['html', 'html'])) {
                    $containerOrigin = dirname($filePath);
                    $containerDestination = dirname($dest_file);

                    $finder = new Finder();
                    $finder->files()->in($containerOrigin)
                        ->notName('*_DELETED_*')
                        ->exclude('share_folder')
                        ->exclude('chat_files')
                        ->exclude('certificates')
                    ;

                    if (is_dir($containerOrigin) &&
                        is_dir($containerDestination)
                    ) {
                        $fs = new Filesystem();
                        $fs->mirror(
                            $containerOrigin,
                            $containerDestination,
                            $finder
                        );
                    }
                }
            }
        }

        foreach ($zip_files_abs as $file_path) {
            if (empty($file_path)) {
                continue;
            }

            if (!is_file($main_path.$file_path) || !is_readable($main_path.$file_path)) {
                continue;
            }

            $dest_file = $archivePath.$temp_dir_short.'/document/'.$file_path;
            if (strstr($file_path, 'upload/users') !== false) {
                $pos = strpos($file_path, 'my_files/');
                if ($pos !== false) {
                    $onlyDirectory = str_replace(
                        'upload/users/',
                        '',
                        substr($file_path, $pos, strlen($file_path))
                    );
                    $dest_file = $archivePath.$temp_dir_short.'/document/'.$onlyDirectory;
                }
            }

            if (strstr($file_path, 'default_course_document/') !== false) {
                $replace = str_replace('/main', '', $file_path);
                $dest_file = $archivePath.$temp_dir_short.'/document/'.$replace;
            }

            if (empty($dest_file)) {
                continue;
            }

            $this->create_path($dest_file);
            copy($main_path.$file_path, $dest_file);
            // Check if the file needs a link update.
            if (in_array($file_path, array_keys($link_updates))) {
                $string = file_get_contents($dest_file);
                unlink($dest_file);
                foreach ($link_updates[$file_path] as $old_new) {
                    // This is an ugly hack that allows .flv files to be found by the flv player that
                    // will be added in document/main/inc/lib/flv_player/flv_player.swf and that needs
                    // to find the flv to play in document/main/, so we replace main/ in the flv path by
                    // ../../.. to return from inc/lib/flv_player to the document/main path.
                    if (substr($old_new['dest'], -3) == 'flv' &&
                        substr($old_new['dest'], 0, 5) == 'main/'
                    ) {
                        $old_new['dest'] = str_replace('main/', '../../../', $old_new['dest']);
                    }
                    $string = str_replace($old_new['orig'], $old_new['dest'], $string);
                }
                file_put_contents($dest_file, $string);
            }
        }

        if (is_array($links_to_create)) {
            foreach ($links_to_create as $file => $link) {
                $content = '<!DOCTYPE html><head>
                            <meta charset="'.api_get_language_isocode().'" />
                            <title>'.$link['title'].'</title>
                            </head>
                            <body dir="'.api_get_text_direction().'">
                            <div style="text-align:center">
                            <a href="'.$link['url'].'">'.$link['title'].'</a></div>
                            </body>
                            </html>';
                file_put_contents($archivePath.$temp_dir_short.'/'.$file, $content);
            }
        }

        // Add non exportable message explanation.
        $lang_not_exportable = get_lang('ThisItemIsNotExportable');
        $file_content = '<!DOCTYPE html><head>
                        <meta charset="'.api_get_language_isocode().'" />
                        <title>'.$lang_not_exportable.'</title>
                        <meta http-equiv="Content-Type" content="text/html; charset='.api_get_system_encoding().'" />
                        </head>
                        <body dir="'.api_get_text_direction().'">';
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
        if (!is_dir($archivePath.$temp_dir_short.'/document')) {
            @mkdir($archivePath.$temp_dir_short.'/document', api_get_permissions_for_new_directories());
        }
        file_put_contents($archivePath.$temp_dir_short.'/document/non_exportable.html', $file_content);

        // Add the extra files that go along with a SCORM package.
        $main_code_path = api_get_path(SYS_CODE_PATH).'lp/packaging/';

        $fs = new Filesystem();
        $fs->mirror($main_code_path, $archivePath.$temp_dir_short);

        // Finalize the imsmanifest structure, add to the zip, then return the zip.
        $manifest = @$xmldoc->saveXML();
        $manifest = api_utf8_decode_xml($manifest); // The manifest gets the system encoding now.
        file_put_contents($archivePath.'/'.$temp_dir_short.'/imsmanifest.xml', $manifest);

        $htmlIndex = new LpIndexGenerator($this);

        file_put_contents(
            $archivePath.'/'.$temp_dir_short.'/index.html',
            $htmlIndex->generate()
        );

        $zip_folder->add(
            $archivePath.'/'.$temp_dir_short,
            PCLZIP_OPT_REMOVE_PATH,
            $archivePath.'/'.$temp_dir_short.'/'
        );

        // Clean possible temporary files.
        foreach ($files_cleanup as $file) {
            $res = unlink($file);
            if ($res === false) {
                error_log(
                    'Could not delete temp file '.$file.' '.__FILE__.' '.__LINE__,
                    0
                );
            }
        }
        $name = api_replace_dangerous_char($this->get_name()).'.zip';
        DocumentManager::file_send_for_download($temp_zip_file, true, $name);
    }

    /**
     * @param int $lp_id
     *
     * @return bool
     */
    public function scorm_export_to_pdf($lp_id)
    {
        $lp_id = (int) $lp_id;
        $files_to_export = [];

        $sessionId = api_get_session_id();
        $course_data = api_get_course_info($this->cc);

        if (!empty($course_data)) {
            $scorm_path = api_get_path(SYS_COURSE_PATH).$course_data['path'].'/scorm/'.$this->path;
            $list = self::get_flat_ordered_items_list($lp_id);
            if (!empty($list)) {
                foreach ($list as $item_id) {
                    $item = $this->items[$item_id];
                    switch ($item->type) {
                        case 'document':
                            // Getting documents from a LP with chamilo documents
                            $file_data = DocumentManager::get_document_data_by_id($item->path, $this->cc);
                            // Try loading document from the base course.
                            if (empty($file_data) && !empty($sessionId)) {
                                $file_data = DocumentManager::get_document_data_by_id(
                                    $item->path,
                                    $this->cc,
                                    false,
                                    0
                                );
                            }
                            $file_path = api_get_path(SYS_COURSE_PATH).$course_data['path'].'/document'.$file_data['path'];
                            if (file_exists($file_path)) {
                                $files_to_export[] = [
                                    'title' => $item->get_title(),
                                    'path' => $file_path,
                                ];
                            }
                            break;
                        case 'asset': //commes from a scorm package generated by chamilo
                        case 'sco':
                            $file_path = $scorm_path.'/'.$item->path;
                            if (file_exists($file_path)) {
                                $files_to_export[] = [
                                    'title' => $item->get_title(),
                                    'path' => $file_path,
                                ];
                            }
                            break;
                        case 'dir':
                            $files_to_export[] = [
                                'title' => $item->get_title(),
                                'path' => null,
                            ];
                            break;
                    }
                }
            }

            $pdf = new PDF();
            $result = $pdf->html_to_pdf(
                $files_to_export,
                $this->name,
                $this->cc,
                true,
                true,
                true,
                $this->get_name()
            );

            return $result;
        }

        return false;
    }

    /**
     * Temp function to be moved in main_api or the best place around for this.
     * Creates a file path if it doesn't exist.
     *
     * @param string $path
     */
    public function create_path($path)
    {
        $path_bits = explode('/', dirname($path));

        // IS_WINDOWS_OS has been defined in main_api.lib.php
        $path_built = IS_WINDOWS_OS ? '' : '/';
        foreach ($path_bits as $bit) {
            if (!empty($bit)) {
                $new_path = $path_built.$bit;
                if (is_dir($new_path)) {
                    $path_built = $new_path.'/';
                } else {
                    mkdir($new_path, api_get_permissions_for_new_directories());
                    $path_built = $new_path.'/';
                }
            }
        }
    }

    /**
     * Delete the image relative to this learning path. No parameter. Only works on instanciated object.
     *
     * @return bool The results of the unlink function, or false if there was no image to start with
     */
    public function delete_lp_image()
    {
        $img = $this->get_preview_image();
        if ($img != '') {
            $del_file = $this->get_preview_image_path(null, 'sys');
            if (isset($del_file) && file_exists($del_file)) {
                $del_file_2 = $this->get_preview_image_path(64, 'sys');
                if (file_exists($del_file_2)) {
                    unlink($del_file_2);
                }
                $this->set_preview_image('');

                return @unlink($del_file);
            }
        }

        return false;
    }

    /**
     * Uploads an author image to the upload/learning_path/images directory.
     *
     * @param array    The image array, coming from the $_FILES superglobal
     *
     * @return bool True on success, false on error
     */
    public function upload_image($image_array)
    {
        if (!empty($image_array['name'])) {
            $upload_ok = process_uploaded_file($image_array);
            $has_attachment = true;
        }

        if ($upload_ok && $has_attachment) {
            $courseDir = api_get_course_path().'/upload/learning_path/images';
            $sys_course_path = api_get_path(SYS_COURSE_PATH);
            $updir = $sys_course_path.$courseDir;
            // Try to add an extension to the file if it hasn't one.
            $new_file_name = add_ext_on_mime(stripslashes($image_array['name']), $image_array['type']);

            if (filter_extension($new_file_name)) {
                $file_extension = explode('.', $image_array['name']);
                $file_extension = strtolower($file_extension[count($file_extension) - 1]);
                $filename = uniqid('');
                $new_file_name = $filename.'.'.$file_extension;
                $new_path = $updir.'/'.$new_file_name;

                // Resize the image.
                $temp = new Image($image_array['tmp_name']);
                $temp->resize(104);
                $result = $temp->send_image($new_path);

                // Storing the image filename.
                if ($result) {
                    $this->set_preview_image($new_file_name);

                    //Resize to 64px to use on course homepage
                    $temp->resize(64);
                    $temp->send_image($updir.'/'.$filename.'.64.'.$file_extension);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param int    $lp_id
     * @param string $status
     */
    public function set_autolaunch($lp_id, $status)
    {
        $course_id = api_get_course_int_id();
        $lp_id = (int) $lp_id;
        $status = (int) $status;
        $lp_table = Database::get_course_table(TABLE_LP_MAIN);

        // Setting everything to autolaunch = 0
        $attributes['autolaunch'] = 0;
        $where = [
            'session_id = ? AND c_id = ? ' => [
                api_get_session_id(),
                $course_id,
            ],
        ];
        Database::update($lp_table, $attributes, $where);
        if ($status == 1) {
            //Setting my lp_id to autolaunch = 1
            $attributes['autolaunch'] = 1;
            $where = [
                'iid = ? AND session_id = ? AND c_id = ?' => [
                    $lp_id,
                    api_get_session_id(),
                    $course_id,
                ],
            ];
            Database::update($lp_table, $attributes, $where);
        }
    }

    /**
     * Gets previous_item_id for the next element of the lp_item table.
     *
     * @author Isaac flores paz
     *
     * @return int Previous item ID
     */
    public function select_previous_item_id()
    {
        $course_id = api_get_course_int_id();
        $table_lp_item = Database::get_course_table(TABLE_LP_ITEM);

        // Get the max order of the items
        $sql = "SELECT max(display_order) AS display_order FROM $table_lp_item
                WHERE c_id = $course_id AND lp_id = ".$this->lp_id;
        $rs_max_order = Database::query($sql);
        $row_max_order = Database::fetch_object($rs_max_order);
        $max_order = $row_max_order->display_order;
        // Get the previous item ID
        $sql = "SELECT iid as previous FROM $table_lp_item
                WHERE
                    c_id = $course_id AND
                    lp_id = ".$this->lp_id." AND
                    display_order = '$max_order' ";
        $rs_max = Database::query($sql);
        $row_max = Database::fetch_object($rs_max);

        // Return the previous item ID
        return $row_max->previous;
    }

    /**
     * Copies an LP.
     */
    public function copy()
    {
        // Course builder
        $cb = new CourseBuilder();

        //Setting tools that will be copied
        $cb->set_tools_to_build(['learnpaths']);

        //Setting elements that will be copied
        $cb->set_tools_specific_id_list(['learnpaths' => [$this->lp_id]]);

        $course = $cb->build();

        //Course restorer
        $course_restorer = new CourseRestorer($course);
        $course_restorer->set_add_text_in_items(true);
        $course_restorer->set_tool_copy_settings(
            ['learnpaths' => ['reset_dates' => true]]
        );
        $course_restorer->restore(
            api_get_course_id(),
            api_get_session_id(),
            false,
            false
        );
    }

    /**
     * Verify document size.
     *
     * @param string $s
     *
     * @return bool
     */
    public static function verify_document_size($s)
    {
        $post_max = ini_get('post_max_size');
        if (substr($post_max, -1, 1) == 'M') {
            $post_max = intval(substr($post_max, 0, -1)) * 1024 * 1024;
        } elseif (substr($post_max, -1, 1) == 'G') {
            $post_max = intval(substr($post_max, 0, -1)) * 1024 * 1024 * 1024;
        }
        $upl_max = ini_get('upload_max_filesize');
        if (substr($upl_max, -1, 1) == 'M') {
            $upl_max = intval(substr($upl_max, 0, -1)) * 1024 * 1024;
        } elseif (substr($upl_max, -1, 1) == 'G') {
            $upl_max = intval(substr($upl_max, 0, -1)) * 1024 * 1024 * 1024;
        }
        $documents_total_space = DocumentManager::documents_total_space();
        $course_max_space = DocumentManager::get_course_quota();
        $total_size = filesize($s) + $documents_total_space;
        if (filesize($s) > $post_max || filesize($s) > $upl_max || $total_size > $course_max_space) {
            return true;
        }

        return false;
    }

    /**
     * Clear LP prerequisites.
     */
    public function clear_prerequisites()
    {
        $course_id = $this->get_course_int_id();
        $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $lp_id = $this->get_id();
        // Cleaning prerequisites
        $sql = "UPDATE $tbl_lp_item SET prerequisite = ''
                WHERE c_id = $course_id AND lp_id = $lp_id";
        Database::query($sql);

        // Cleaning mastery score for exercises
        $sql = "UPDATE $tbl_lp_item SET mastery_score = ''
                WHERE c_id = $course_id AND lp_id = $lp_id AND item_type = 'quiz'";
        Database::query($sql);
    }

    public function set_previous_step_as_prerequisite_for_all_items()
    {
        $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $course_id = $this->get_course_int_id();
        $lp_id = $this->get_id();

        if (!empty($this->items)) {
            $previous_item_id = null;
            $previous_item_max = 0;
            $previous_item_type = null;
            $last_item_not_dir = null;
            $last_item_not_dir_type = null;
            $last_item_not_dir_max = null;

            foreach ($this->ordered_items as $itemId) {
                $item = $this->getItem($itemId);
                // if there was a previous item... (otherwise jump to set it)
                if (!empty($previous_item_id)) {
                    $current_item_id = $item->get_id(); //save current id
                    if ($item->get_type() != 'dir') {
                        // Current item is not a folder, so it qualifies to get a prerequisites
                        if ($last_item_not_dir_type == 'quiz') {
                            // if previous is quiz, mark its max score as default score to be achieved
                            $sql = "UPDATE $tbl_lp_item SET mastery_score = '$last_item_not_dir_max'
                                    WHERE c_id = $course_id AND lp_id = $lp_id AND iid = $last_item_not_dir";
                            Database::query($sql);
                        }
                        // now simply update the prerequisite to set it to the last non-chapter item
                        $sql = "UPDATE $tbl_lp_item SET prerequisite = '$last_item_not_dir'
                                WHERE c_id = $course_id AND lp_id = $lp_id AND iid = $current_item_id";
                        Database::query($sql);
                        // record item as 'non-chapter' reference
                        $last_item_not_dir = $item->get_id();
                        $last_item_not_dir_type = $item->get_type();
                        $last_item_not_dir_max = $item->get_max();
                    }
                } else {
                    if ($item->get_type() != 'dir') {
                        // Current item is not a folder (but it is the first item) so record as last "non-chapter" item
                        $last_item_not_dir = $item->get_id();
                        $last_item_not_dir_type = $item->get_type();
                        $last_item_not_dir_max = $item->get_max();
                    }
                }
                // Saving the item as "previous item" for the next loop
                $previous_item_id = $item->get_id();
                $previous_item_max = $item->get_max();
                $previous_item_type = $item->get_type();
            }
        }
    }

    /**
     * @param array $params
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return int
     */
    public static function createCategory($params)
    {
        $em = Database::getManager();
        $item = new CLpCategory();
        $item->setName($params['name']);
        $item->setCId($params['c_id']);
        $em->persist($item);
        $em->flush();

        $id = $item->getId();

        $sessionId = api_get_session_id();
        if (!empty($sessionId) && api_get_configuration_value('allow_session_lp_category')) {
            $table = Database::get_course_table(TABLE_LP_CATEGORY);
            $sql = "UPDATE $table SET session_id = $sessionId WHERE iid = $id";
            Database::query($sql);
        }

        api_item_property_update(
            api_get_course_info(),
            TOOL_LEARNPATH_CATEGORY,
            $id,
            'visible',
            api_get_user_id()
        );

        return $item->getId();
    }

    /**
     * @param array $params
     */
    public static function updateCategory($params)
    {
        $em = Database::getManager();
        $item = self::getCategory($params['id']);

        if ($item) {
            $item->setName($params['name']);
            $em->persist($item);
            $em->flush();
        }
    }

    /**
     * @param int $id
     */
    public static function moveUpCategory($id)
    {
        $item = self::getCategory($id);
        if ($item) {
            $em = Database::getManager();
            $position = $item->getPosition() - 1;
            $item->setPosition($position);
            $em->persist($item);
            $em->flush();
        }
    }

    /**
     * @param int $id
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public static function moveDownCategory($id)
    {
        $item = self::getCategory($id);
        if ($item) {
            $em = Database::getManager();
            $position = $item->getPosition() + 1;
            $item->setPosition($position);
            $em->persist($item);
            $em->flush();
        }
    }

    public static function getLpList($courseId, $sessionId, $onlyActiveLp = true)
    {
        $TABLE_LP = Database::get_course_table(TABLE_LP_MAIN);
        $TABLE_ITEM_PROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;

        $sql = "SELECT lp.id, lp.name
                FROM $TABLE_LP lp
                INNER JOIN $TABLE_ITEM_PROPERTY ip
                ON lp.id = ip.ref
                WHERE lp.c_id = $courseId ";

        if (!empty($sessionId)) {
            $sql .= "AND ip.session_id = $sessionId ";
        }

        if ($onlyActiveLp) {
            $sql .= "AND ip.tool = 'learnpath' ";
            $sql .= "AND ip.visibility = 1 ";
        }

        $sql .= "GROUP BY lp.id";

        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * @param int $courseId
     *
     * @throws \Doctrine\ORM\Query\QueryException
     *
     * @return int|mixed
     */
    public static function getCountCategories($courseId)
    {
        if (empty($courseId)) {
            return 0;
        }
        $em = Database::getManager();
        $query = $em->createQuery('SELECT COUNT(u.id) FROM ChamiloCourseBundle:CLpCategory u WHERE u.cId = :id');
        $query->setParameter('id', $courseId);

        return $query->getSingleScalarResult();
    }

    /**
     * @param int $courseId
     *
     * @return array|CLpCategory[]
     */
    public static function getCategories($courseId, $withNoneCategory = false)
    {
        $em = Database::getManager();

        // Using doctrine extensions
        /** @var SortableRepository $repo */
        $repo = $em->getRepository('ChamiloCourseBundle:CLpCategory');

        $categories = $repo->getBySortableGroupsQuery(['cId' => $courseId])->getResult();

        if ($withNoneCategory) {
            $categoryTest = new CLpCategory();
            $categoryTest->setId(0)
                ->setName(get_lang('WithOutCategory'))
                ->setPosition(0);

            array_unshift($categories, $categoryTest);
        }

        return $categories;
    }

    public static function getCategorySessionId($id)
    {
        if (false === api_get_configuration_value('allow_session_lp_category')) {
            return 0;
        }

        $table = Database::get_course_table(TABLE_LP_CATEGORY);
        $id = (int) $id;

        $sql = "SELECT session_id FROM $table WHERE iid = $id";
        $result = Database::query($sql);
        $result = Database::fetch_array($result, 'ASSOC');

        if ($result) {
            return (int) $result['session_id'];
        }

        return 0;
    }

    /**
     * @param int $id
     *
     * @return CLpCategory
     */
    public static function getCategory($id)
    {
        $id = (int) $id;
        $em = Database::getManager();

        return $em->find('ChamiloCourseBundle:CLpCategory', $id);
    }

    /**
     * @param int $courseId
     *
     * @return CLpCategory[]
     */
    public static function getCategoryByCourse($courseId)
    {
        $em = Database::getManager();

        return $em->getRepository('ChamiloCourseBundle:CLpCategory')->findBy(['cId' => $courseId]);
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public static function deleteCategory($id)
    {
        $em = Database::getManager();
        $id = (int) $id;
        $item = self::getCategory($id);
        if ($item) {
            $courseId = $item->getCId();
            $query = $em->createQuery('SELECT u FROM ChamiloCourseBundle:CLp u WHERE u.cId = :id AND u.categoryId = :catId');
            $query->setParameter('id', $courseId);
            $query->setParameter('catId', $item->getId());
            $lps = $query->getResult();

            // Setting category = 0.
            if ($lps) {
                foreach ($lps as $lpItem) {
                    $lpItem->setCategoryId(0);
                }
            }

            if (api_get_configuration_value('allow_lp_subscription_to_usergroups')) {
                $table = Database::get_course_table(TABLE_LP_CATEGORY_REL_USERGROUP);
                $sql = "DELETE FROM $table
                        WHERE
                            lp_category_id = $id AND
                            c_id = $courseId ";
                Database::query($sql);
            }

            // Removing category.
            $em->remove($item);
            $em->flush();

            $courseInfo = api_get_course_info_by_id($courseId);
            $sessionId = api_get_session_id();

            // Delete link tool
            $tbl_tool = Database::get_course_table(TABLE_TOOL_LIST);
            $linkLikeValue = "lp/lp_controller.php?%&action=view_category&id=$id%";
            // Delete tools
            $sql = "DELETE FROM $tbl_tool
                    WHERE c_id = ".$courseId." AND (link LIKE '$linkLikeValue' AND image='lp_category.gif')";
            Database::query($sql);

            return true;
        }

        return false;
    }

    /**
     * @param int  $courseId
     * @param bool $addSelectOption
     *
     * @return mixed
     */
    public static function getCategoryFromCourseIntoSelect($courseId, $addSelectOption = false)
    {
        $items = self::getCategoryByCourse($courseId);
        $cats = [];
        if ($addSelectOption) {
            $cats = [get_lang('SelectACategory')];
        }

        $sessionId = api_get_session_id();
        $avoidCategoryInSession = false;
        if (empty($sessionId)) {
            $avoidCategoryInSession = true;
        }
        /*$checkSession = false;
        if (api_get_configuration_value('allow_session_lp_category')) {
            $checkSession = true;
        }*/

        if (!empty($items)) {
            foreach ($items as $cat) {
                $categoryId = $cat->getId();
                if ($avoidCategoryInSession) {
                    $inSession = self::getCategorySessionId($categoryId);
                    if (!empty($inSession)) {
                        continue;
                    }
                }
                $cats[$categoryId] = $cat->getName();
            }
        }

        return $cats;
    }

    /**
     * @param string $courseCode
     * @param int    $lpId
     * @param int    $user_id
     *
     * @return learnpath
     */
    public static function getLpFromSession($courseCode, $lpId, $user_id)
    {
        $debug = 0;
        $learnPath = null;
        $lpObject = Session::read('lpobject');
        if ($lpObject !== null) {
            $learnPath = UnserializeApi::unserialize('lp', $lpObject);
            if ($debug) {
                error_log('getLpFromSession: unserialize');
                error_log('------getLpFromSession------');
                error_log('------unserialize------');
                error_log("lp_view_session_id: ".$learnPath->lp_view_session_id);
                error_log("api_get_sessionid: ".api_get_session_id());
            }
        }

        if (!is_object($learnPath)) {
            $learnPath = new learnpath($courseCode, $lpId, $user_id);
            if ($debug) {
                error_log('------getLpFromSession------');
                error_log('getLpFromSession: create new learnpath');
                error_log("create new LP with $courseCode - $lpId - $user_id");
                error_log("lp_view_session_id: ".$learnPath->lp_view_session_id);
                error_log("api_get_sessionid: ".api_get_session_id());
            }
        }

        return $learnPath;
    }

    /**
     * @param int $itemId
     *
     * @return learnpathItem|false
     */
    public function getItem($itemId)
    {
        if (isset($this->items[$itemId]) && is_object($this->items[$itemId])) {
            return $this->items[$itemId];
        }

        return false;
    }

    /**
     * @return int
     */
    public function getCurrentAttempt()
    {
        $attempt = $this->getItem($this->get_current_item_id());
        if ($attempt) {
            $attemptId = $attempt->get_attempt_id();

            return $attemptId;
        }

        return 0;
    }

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return (int) $this->categoryId;
    }

    /**
     * @param int $categoryId
     *
     * @return bool
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = (int) $categoryId;
        $table = Database::get_course_table(TABLE_LP_MAIN);
        $lp_id = $this->get_id();
        $sql = "UPDATE $table SET category_id = ".$this->categoryId."
                WHERE iid = $lp_id";
        Database::query($sql);

        return true;
    }

    /**
     * Get whether this is a learning path with the possibility to subscribe
     * users or not.
     *
     * @return int
     */
    public function getSubscribeUsers()
    {
        return $this->subscribeUsers;
    }

    /**
     * Set whether this is a learning path with the possibility to subscribe
     * users or not.
     *
     * @param int $value (0 = false, 1 = true)
     *
     * @return bool
     */
    public function setSubscribeUsers($value)
    {
        $this->subscribeUsers = (int) $value;
        $table = Database::get_course_table(TABLE_LP_MAIN);
        $lp_id = $this->get_id();
        $sql = "UPDATE $table SET subscribe_users = ".$this->subscribeUsers."
                WHERE iid = $lp_id";
        Database::query($sql);

        return true;
    }

    /**
     * Calculate the count of stars for a user in this LP
     * This calculation is based on the following rules:
     * - the student gets one star when he gets to 50% of the learning path
     * - the student gets a second star when the average score of all tests inside the learning path >= 50%
     * - the student gets a third star when the average score of all tests inside the learning path >= 80%
     * - the student gets the final star when the score for the *last* test is >= 80%.
     *
     * @param int $sessionId Optional. The session ID
     *
     * @return int The count of stars
     */
    public function getCalculateStars($sessionId = 0)
    {
        $stars = 0;
        $progress = self::getProgress(
            $this->lp_id,
            $this->user_id,
            $this->course_int_id,
            $sessionId
        );

        if ($progress >= 50) {
            $stars++;
        }

        // Calculate stars chapters evaluation
        $exercisesItems = $this->getExercisesItems();

        if (!empty($exercisesItems)) {
            $totalResult = 0;

            foreach ($exercisesItems as $exerciseItem) {
                $exerciseResultInfo = Event::getExerciseResultsByUser(
                    $this->user_id,
                    $exerciseItem->path,
                    $this->course_int_id,
                    $sessionId,
                    $this->lp_id,
                    $exerciseItem->db_id
                );

                $exerciseResultInfo = end($exerciseResultInfo);

                if (!$exerciseResultInfo) {
                    continue;
                }

                if (!empty($exerciseResultInfo['exe_weighting'])) {
                    $exerciseResult = $exerciseResultInfo['exe_result'] * 100 / $exerciseResultInfo['exe_weighting'];
                } else {
                    $exerciseResult = 0;
                }
                $totalResult += $exerciseResult;
            }

            $totalExerciseAverage = $totalResult / (count($exercisesItems) > 0 ? count($exercisesItems) : 1);

            if ($totalExerciseAverage >= 50) {
                $stars++;
            }

            if ($totalExerciseAverage >= 80) {
                $stars++;
            }
        }

        // Calculate star for final evaluation
        $finalEvaluationItem = $this->getFinalEvaluationItem();

        if (!empty($finalEvaluationItem)) {
            $evaluationResultInfo = Event::getExerciseResultsByUser(
                $this->user_id,
                $finalEvaluationItem->path,
                $this->course_int_id,
                $sessionId,
                $this->lp_id,
                $finalEvaluationItem->db_id
            );

            $evaluationResultInfo = end($evaluationResultInfo);

            if ($evaluationResultInfo) {
                $evaluationResult = $evaluationResultInfo['exe_result'] * 100 / $evaluationResultInfo['exe_weighting'];

                if ($evaluationResult >= 80) {
                    $stars++;
                }
            }
        }

        return $stars;
    }

    /**
     * Get the items of exercise type.
     *
     * @return array The items. Otherwise return false
     */
    public function getExercisesItems()
    {
        $exercises = [];
        foreach ($this->items as $item) {
            if ($item->type != 'quiz') {
                continue;
            }
            $exercises[] = $item;
        }

        array_pop($exercises);

        return $exercises;
    }

    /**
     * Get the item of exercise type (evaluation type).
     *
     * @return array The final evaluation. Otherwise return false
     */
    public function getFinalEvaluationItem()
    {
        $exercises = [];
        foreach ($this->items as $item) {
            if ($item->type != 'quiz') {
                continue;
            }

            $exercises[] = $item;
        }

        return array_pop($exercises);
    }

    /**
     * Calculate the total points achieved for the current user in this learning path.
     *
     * @param int $sessionId Optional. The session Id
     *
     * @return int
     */
    public function getCalculateScore($sessionId = 0)
    {
        // Calculate stars chapters evaluation
        $exercisesItems = $this->getExercisesItems();
        $finalEvaluationItem = $this->getFinalEvaluationItem();
        $totalExercisesResult = 0;
        $totalEvaluationResult = 0;

        if ($exercisesItems !== false) {
            foreach ($exercisesItems as $exerciseItem) {
                $exerciseResultInfo = Event::getExerciseResultsByUser(
                    $this->user_id,
                    $exerciseItem->path,
                    $this->course_int_id,
                    $sessionId,
                    $this->lp_id,
                    $exerciseItem->db_id
                );

                $exerciseResultInfo = end($exerciseResultInfo);

                if (!$exerciseResultInfo) {
                    continue;
                }

                $totalExercisesResult += $exerciseResultInfo['exe_result'];
            }
        }

        if (!empty($finalEvaluationItem)) {
            $evaluationResultInfo = Event::getExerciseResultsByUser(
                $this->user_id,
                $finalEvaluationItem->path,
                $this->course_int_id,
                $sessionId,
                $this->lp_id,
                $finalEvaluationItem->db_id
            );

            $evaluationResultInfo = end($evaluationResultInfo);

            if ($evaluationResultInfo) {
                $totalEvaluationResult += $evaluationResultInfo['exe_result'];
            }
        }

        return $totalExercisesResult + $totalEvaluationResult;
    }

    /**
     * Check if URL is not allowed to be show in a iframe.
     *
     * @param string $src
     *
     * @return string
     */
    public function fixBlockedLinks($src)
    {
        $urlInfo = parse_url($src);

        $platformProtocol = 'https';
        if (strpos(api_get_path(WEB_CODE_PATH), 'https') === false) {
            $platformProtocol = 'http';
        }

        $protocolFixApplied = false;
        //Scheme validation to avoid "Notices" when the lesson doesn't contain a valid scheme
        $scheme = isset($urlInfo['scheme']) ? $urlInfo['scheme'] : null;
        $host = isset($urlInfo['host']) ? $urlInfo['host'] : null;

        if ($platformProtocol != $scheme) {
            Session::write('x_frame_source', $src);
            $src = 'blank.php?error=x_frames_options';
            $protocolFixApplied = true;
        }

        if ($protocolFixApplied == false) {
            if (strpos(api_get_path(WEB_PATH), $host) === false) {
                // Check X-Frame-Options
                $ch = curl_init();
                $options = [
                    CURLOPT_URL => $src,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HEADER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_AUTOREFERER => true,
                    CURLOPT_CONNECTTIMEOUT => 120,
                    CURLOPT_TIMEOUT => 120,
                    CURLOPT_MAXREDIRS => 10,
                ];

                $proxySettings = api_get_configuration_value('proxy_settings');
                if (!empty($proxySettings) &&
                    isset($proxySettings['curl_setopt_array'])
                ) {
                    $options[CURLOPT_PROXY] = $proxySettings['curl_setopt_array']['CURLOPT_PROXY'];
                    $options[CURLOPT_PROXYPORT] = $proxySettings['curl_setopt_array']['CURLOPT_PROXYPORT'];
                }

                curl_setopt_array($ch, $options);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch);
                $headers = substr($response, 0, $httpCode['header_size']);

                $error = false;
                if (stripos($headers, 'X-Frame-Options: DENY') > -1
                    //|| stripos($headers, 'X-Frame-Options: SAMEORIGIN') > -1
                ) {
                    $error = true;
                }

                if ($error) {
                    Session::write('x_frame_source', $src);
                    $src = 'blank.php?error=x_frames_options';
                }
            }
        }

        return $src;
    }

    /**
     * Check if this LP has a created forum in the basis course.
     *
     * @return bool
     */
    public function lpHasForum()
    {
        $forumTable = Database::get_course_table(TABLE_FORUM);
        $itemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $fakeFrom = "
            $forumTable f
            INNER JOIN $itemProperty ip
            ON (f.forum_id = ip.ref AND f.c_id = ip.c_id)
        ";

        $resultData = Database::select(
            'COUNT(f.iid) AS qty',
            $fakeFrom,
            [
                'where' => [
                    'ip.visibility != ? AND ' => 2,
                    'ip.tool = ? AND ' => TOOL_FORUM,
                    'f.c_id = ? AND ' => intval($this->course_int_id),
                    'f.lp_id = ?' => intval($this->lp_id),
                ],
            ],
            'first'
        );

        return $resultData['qty'] > 0;
    }

    /**
     * Get the forum for this learning path.
     *
     * @param int $sessionId
     *
     * @return bool
     */
    public function getForum($sessionId = 0)
    {
        $forumTable = Database::get_course_table(TABLE_FORUM);
        $itemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $fakeFrom = "$forumTable f
            INNER JOIN $itemProperty ip ";

        if ($this->lp_session_id == 0) {
            $fakeFrom .= "
                ON (
                    f.forum_id = ip.ref AND f.c_id = ip.c_id AND (
                        f.session_id = ip.session_id OR ip.session_id IS NULL
                    )
                )
            ";
        } else {
            $fakeFrom .= "
                ON (
                    f.forum_id = ip.ref AND f.c_id = ip.c_id AND f.session_id = ip.session_id
                )
            ";
        }

        $resultData = Database::select(
            'f.*',
            $fakeFrom,
            [
                'where' => [
                    'ip.visibility != ? AND ' => 2,
                    'ip.tool = ? AND ' => TOOL_FORUM,
                    'f.session_id = ? AND ' => $sessionId,
                    'f.c_id = ? AND ' => intval($this->course_int_id),
                    'f.lp_id = ?' => intval($this->lp_id),
                ],
            ],
            'first'
        );

        if (empty($resultData)) {
            return false;
        }

        return $resultData;
    }

    /**
     * Create a forum for this learning path.
     *
     * @param int $forumCategoryId
     *
     * @return int The forum ID if was created. Otherwise return false
     */
    public function createForum($forumCategoryId)
    {
        require_once api_get_path(SYS_CODE_PATH).'/forum/forumfunction.inc.php';

        $forumId = store_forum(
            [
                'lp_id' => $this->lp_id,
                'forum_title' => $this->name,
                'forum_comment' => null,
                'forum_category' => (int) $forumCategoryId,
                'students_can_edit_group' => ['students_can_edit' => 0],
                'allow_new_threads_group' => ['allow_new_threads' => 0],
                'default_view_type_group' => ['default_view_type' => 'flat'],
                'group_forum' => 0,
                'public_private_group_forum_group' => ['public_private_group_forum' => 'public'],
            ],
            [],
            true
        );

        return $forumId;
    }

    /**
     * Get the LP Final Item form.
     *
     * @throws Exception
     * @throws HTML_QuickForm_Error
     *
     * @return string
     */
    public function getFinalItemForm()
    {
        $finalItem = $this->getFinalItem();
        $title = '';

        if ($finalItem) {
            $title = $finalItem->get_title();
            $buttonText = get_lang('Save');
            $content = $this->getSavedFinalItem();
        } else {
            $buttonText = get_lang('LPCreateDocument');
            $content = $this->getFinalItemTemplate();
        }

        $courseInfo = api_get_course_info();
        $result = $this->generate_lp_folder($courseInfo);
        $relative_path = api_substr($result['dir'], 1, strlen($result['dir']));
        $relative_prefix = '../../';

        $editorConfig = [
            'ToolbarSet' => 'LearningPathDocuments',
            'Width' => '100%',
            'Height' => '500',
            'FullPage' => true,
            'CreateDocumentDir' => $relative_prefix,
            'CreateDocumentWebDir' => api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/',
            'BaseHref' => api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/'.$relative_path,
        ];

        $url = api_get_self().'?'.api_get_cidreq().'&'.http_build_query([
            'type' => 'document',
            'lp_id' => $this->lp_id,
        ]);

        $form = new FormValidator('final_item', 'POST', $url);
        $form->addText('title', get_lang('Title'));
        $form->addButtonSave($buttonText);

        $renderer = $form->defaultRenderer();
        $renderer->setElementTemplate('&nbsp;{label}{element}', 'content_lp_certificate');

        $form->addHtmlEditor(
            'content_lp_certificate',
            null,
            true,
            false,
            $editorConfig,
            true
        );
        $form->addHtml(
            Display::return_message(
                get_lang('LPEndStepAddTagsToShowCertificateOrSkillAutomatically').'</br></br> <b>((certificate))</b> </br> <b>((skill))</b>',
                'normal',
                false
            )
        );
        $form->addHidden('action', 'add_final_item');
        $form->addHidden('path', Session::read('pathItem'));
        $form->addHidden('previous', $this->get_last());
        $form->setDefaults(['title' => $title, 'content_lp_certificate' => $content]);

        if ($form->validate()) {
            $values = $form->exportValues();
            $lastItemId = $this->getLastInFirstLevel();

            if (!$finalItem) {
                $documentId = $this->create_document(
                    $this->course_info,
                    $values['content_lp_certificate'],
                    $values['title']
                );
                $this->add_item(
                    0,
                    $lastItemId,
                    'final_item',
                    $documentId,
                    $values['title'],
                    ''
                );

                Display::addFlash(
                    Display::return_message(get_lang('Added'))
                );
            } else {
                $this->edit_document($this->course_info);
            }
        }

        return $form->returnForm();
    }

    /**
     * Check if the current lp item is first, both, last or none from lp list.
     *
     * @param int $currentItemId
     *
     * @return string
     */
    public function isFirstOrLastItem($currentItemId)
    {
        $lpItemId = [];
        $typeListNotToVerify = self::getChapterTypes();

        // Using get_toc() function instead $this->items because returns the correct order of the items
        foreach ($this->get_toc() as $item) {
            if (!in_array($item['type'], $typeListNotToVerify)) {
                $lpItemId[] = $item['id'];
            }
        }

        $lastLpItemIndex = count($lpItemId) - 1;
        $position = array_search($currentItemId, $lpItemId);

        switch ($position) {
            case 0:
                if (!$lastLpItemIndex) {
                    $answer = 'both';
                    break;
                }

                $answer = 'first';
                break;
            case $lastLpItemIndex:
                $answer = 'last';
                break;
            default:
                $answer = 'none';
        }

        return $answer;
    }

    /**
     * Get whether this is a learning path with the accumulated SCORM time or not.
     *
     * @return int
     */
    public function getAccumulateScormTime()
    {
        return $this->accumulateScormTime;
    }

    /**
     * Set whether this is a learning path with the accumulated SCORM time or not.
     *
     * @param int $value (0 = false, 1 = true)
     *
     * @return bool Always returns true
     */
    public function setAccumulateScormTime($value)
    {
        $this->accumulateScormTime = (int) $value;
        $lp_table = Database::get_course_table(TABLE_LP_MAIN);
        $lp_id = $this->get_id();
        $sql = "UPDATE $lp_table
                SET accumulate_scorm_time = ".$this->accumulateScormTime."
                WHERE iid = $lp_id";
        Database::query($sql);

        return true;
    }

    /**
     * Returns an HTML-formatted link to a resource, to incorporate directly into
     * the new learning path tool.
     *
     * The function is a big switch on tool type.
     * In each case, we query the corresponding table for information and build the link
     * with that information.
     *
     * @author Yannick Warnier <ywarnier@beeznest.org> - rebranding based on
     * previous work (display_addedresource_link_in_learnpath())
     *
     * @param int $course_id      Course code
     * @param int $learningPathId The learning path ID (in lp table)
     * @param int $id_in_path     the unique index in the items table
     * @param int $lpViewId
     * @param int $lpSessionId
     *
     * @return string
     */
    public static function rl_get_resource_link_for_learnpath(
        $course_id,
        $learningPathId,
        $id_in_path,
        $lpViewId,
        $lpSessionId = 0
    ) {
        $session_id = api_get_session_id();
        $course_info = api_get_course_info_by_id($course_id);

        $learningPathId = (int) $learningPathId;
        $id_in_path = (int) $id_in_path;
        $lpViewId = (int) $lpViewId;

        $em = Database::getManager();
        $lpItemRepo = $em->getRepository('ChamiloCourseBundle:CLpItem');

        /** @var CLpItem $rowItem */
        $rowItem = $lpItemRepo->findOneBy([
            'cId' => $course_id,
            'lpId' => $learningPathId,
            'iid' => $id_in_path,
        ]);

        if (!$rowItem) {
            // Try one more time with "id"
            /** @var CLpItem $rowItem */
            $rowItem = $lpItemRepo->findOneBy([
                'cId' => $course_id,
                'lpId' => $learningPathId,
                'id' => $id_in_path,
            ]);

            if (!$rowItem) {
                return -1;
            }
        }

        $course_code = $course_info['code'];
        $type = $rowItem->getItemType();
        $id = empty($rowItem->getPath()) ? '0' : $rowItem->getPath();
        $main_dir_path = api_get_path(WEB_CODE_PATH);
        $main_course_path = api_get_path(WEB_COURSE_PATH).$course_info['directory'].'/';
        $link = '';
        $extraParams = api_get_cidreq(true, true, 'learnpath').'&session_id='.$session_id;
        // It adds lti parameter
        if (isset($_REQUEST['lti_launch_id'])) {
            $extraParams .= '&lti_launch_id='.Security::remove_XSS($_REQUEST['lti_launch_id']);
        }

        switch ($type) {
            case 'dir':
                return $main_dir_path.'lp/blank.php';
            case TOOL_CALENDAR_EVENT:
                return $main_dir_path.'calendar/agenda.php?agenda_id='.$id.'&'.$extraParams;
            case TOOL_ANNOUNCEMENT:
                return $main_dir_path.'announcements/announcements.php?ann_id='.$id.'&'.$extraParams;
            case TOOL_LINK:
                $linkInfo = Link::getLinkInfo($id);
                if ($linkInfo) {
                    $itemPropertyInfo = api_get_item_property_info($course_id, TOOL_LINK, $id, $session_id);
                    if ($itemPropertyInfo && 0 === (int) $itemPropertyInfo['visibility']) {
                        return '';
                    }
                    if (isset($linkInfo['url'])) {
                        return $linkInfo['url'];
                    }
                }

                return '';
            case TOOL_QUIZ:
                if (empty($id)) {
                    return '';
                }

                // Get the lp_item_view with the highest view_count.
                $learnpathItemViewResult = $em
                    ->getRepository('ChamiloCourseBundle:CLpItemView')
                    ->findBy(
                        ['cId' => $course_id, 'lpItemId' => $rowItem->getId(), 'lpViewId' => $lpViewId],
                        ['viewCount' => 'DESC'],
                        1
                    );
                /** @var CLpItemView $learnpathItemViewData */
                $learnpathItemViewData = current($learnpathItemViewResult);
                $learnpathItemViewId = $learnpathItemViewData ? $learnpathItemViewData->getId() : 0;

                return $main_dir_path.'exercise/overview.php?'.$extraParams.'&'
                    .http_build_query([
                        'lp_init' => 1,
                        'learnpath_item_view_id' => (int) $learnpathItemViewId,
                        'learnpath_id' => (int) $learningPathId,
                        'learnpath_item_id' => (int) $id_in_path,
                        'exerciseId' => (int) $id,
                    ]);
            case TOOL_HOTPOTATOES: //lowercase because of strtolower above
                $TBL_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT);
                $result = Database::query("SELECT * FROM ".$TBL_DOCUMENT." WHERE c_id = $course_id AND iid=$id");
                $myrow = Database::fetch_array($result);
                $path = $myrow['path'];

                return $main_dir_path.'exercise/showinframes.php?file='.$path.'&cid='.$course_code.'&uid='
                    .api_get_user_id().'&learnpath_id='.$learningPathId.'&learnpath_item_id='.$id_in_path
                    .'&lp_view_id='.$lpViewId.'&'.$extraParams;
            case TOOL_FORUM:
                return $main_dir_path.'forum/viewforum.php?forum='.$id.'&lp=true&'.$extraParams;
            case TOOL_THREAD:
                // forum post
                $tbl_topics = Database::get_course_table(TABLE_FORUM_THREAD);
                if (empty($id)) {
                    return '';
                }
                $sql = "SELECT * FROM $tbl_topics WHERE c_id = $course_id AND thread_id=$id";
                $result = Database::query($sql);
                $myrow = Database::fetch_array($result);

                return $main_dir_path.'forum/viewthread.php?thread='.$id.'&forum='.$myrow['forum_id'].'&lp=true&'
                    .$extraParams;
            case TOOL_POST:
                $tbl_post = Database::get_course_table(TABLE_FORUM_POST);
                $result = Database::query("SELECT * FROM $tbl_post WHERE c_id = $course_id AND post_id=$id");
                $myrow = Database::fetch_array($result);

                return $main_dir_path.'forum/viewthread.php?post='.$id.'&thread='.$myrow['thread_id'].'&forum='
                    .$myrow['forum_id'].'&lp=true&'.$extraParams;
            case TOOL_READOUT_TEXT:
                return api_get_path(WEB_CODE_PATH).
                    'lp/readout_text.php?&id='.$id.'&lp_id='.$learningPathId.'&'.$extraParams;
            case TOOL_DOCUMENT:
                $repo = $em->getRepository('ChamiloCourseBundle:CDocument');
                $document = $repo->findOneBy(['cId' => $course_id, 'iid' => $id]);

                if (empty($document)) {
                    // Try with normal id
                    $document = $repo->findOneBy(['cId' => $course_id, 'id' => $id]);

                    if (empty($document)) {
                        return '';
                    }
                }

                $documentPathInfo = pathinfo($document->getPath());
                $mediaSupportedFiles = ['mp3', 'mp4', 'ogv', 'ogg', 'flv', 'm4v', 'webm', 'wav'];
                $extension = isset($documentPathInfo['extension']) ? $documentPathInfo['extension'] : '';
                $showDirectUrl = !in_array($extension, $mediaSupportedFiles);

                $openmethod = 2;
                $officedoc = false;
                Session::write('openmethod', $openmethod);
                Session::write('officedoc', $officedoc);

                // Same validation as in document/download.php
                $isVisible = DocumentManager::is_visible(
                    $document->getPath(),
                    api_get_course_info(),
                    api_get_session_id()
                );

                if (!api_is_allowed_to_edit() && !$isVisible) {
                    return '';
                }

                if ($showDirectUrl) {
                    $file = $main_course_path.'document'.$document->getPath().'?'.$extraParams;
                    if (api_get_configuration_value('allow_pdf_viewerjs_in_lp')) {
                        if (Link::isPdfLink($file)) {
                            return api_get_path(WEB_LIBRARY_PATH).
                                'javascript/ViewerJS/index.html?zoom=page-width#'.$file;
                        }
                    }

                    return $file;
                }

                return api_get_path(WEB_CODE_PATH).'document/showinframes.php?id='.$id.'&'.$extraParams;
            case TOOL_LP_FINAL_ITEM:
                return api_get_path(WEB_CODE_PATH).'lp/lp_final_item.php?&id='.$id.'&lp_id='.$learningPathId.'&'
                    .$extraParams;
            case 'assignments':
                return $main_dir_path.'work/work.php?'.$extraParams;
            case TOOL_DROPBOX:
                return $main_dir_path.'dropbox/index.php?'.$extraParams;
            case 'introduction_text': //DEPRECATED
                return '';
            case TOOL_COURSE_DESCRIPTION:
                return $main_dir_path.'course_description?'.$extraParams;
            case TOOL_GROUP:
                return $main_dir_path.'group/group.php?'.$extraParams;
            case TOOL_USER:
                return $main_dir_path.'user/user.php?'.$extraParams;
            case TOOL_STUDENTPUBLICATION:
                if (!empty($rowItem->getPath())) {
                    $workId = $rowItem->getPath();
                    if (empty($lpSessionId) && !empty($session_id)) {
                        // Check if a student publication with the same name exists in this session see BT#17700
                        $title = Database::escape_string($rowItem->getTitle());
                        $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
                        $sql = "SELECT * FROM $table
                                WHERE
                                    active = 1 AND
                                    parent_id = 0 AND
                                    c_id = $course_id AND
                                    session_id = $session_id AND
                                    title = '$title'
                                LIMIT 1";
                        $result = Database::query($sql);
                        if (Database::num_rows($result)) {
                            $work = Database::fetch_array($result, 'ASSOC');
                            if ($work) {
                                $workId = $work['iid'];
                            }
                        }
                    }

                    return $main_dir_path.'work/work_list.php?id='.$workId.'&'.$extraParams;
                }

                return $main_dir_path.'work/work.php?'.api_get_cidreq().'&id='.$rowItem->getPath().'&'.$extraParams;
            case TOOL_XAPI:
                $toolLaunch = $em->find(ToolLaunch::class, $id);

                if (empty($toolLaunch)) {
                    break;
                }

                return api_get_path(WEB_PLUGIN_PATH).'xapi/'
                    .('cmi5' === $toolLaunch->getActivityType() ? 'cmi5/view.php' : 'tincan/view.php')
                    ."?id=$id&$extraParams";
            case TOOL_H5P:
                if ('true' === api_get_plugin_setting('h5pimport', 'tool_enable')) {
                    $toolLaunch = $em->find(H5pImport::class, $id);

                    if (empty($toolLaunch)) {
                        break;
                    }

                    return api_get_path(WEB_PLUGIN_PATH).'h5pimport/view.php'."?id=$id&$extraParams";
                }
                break;
            case TOOL_SURVEY:
                $table = Database::get_course_table(TABLE_SURVEY);
                $sql = "SELECT code FROM $table WHERE c_id = $course_id AND survey_id =".(int) $id;
                $result = Database::query($sql);
                $surveyCode = Database::result($result, 0, 0);
                $autoSurveyLink = SurveyUtil::generateFillSurveyLink(
                    'auto',
                    $course_info,
                    api_get_session_id(),
                    $surveyCode
                );
                $lpParams = [
                    'lp_id' => $learningPathId,
                    'lp_item_id' => $id_in_path,
                    'origin' => 'learnpath',
                ];

                return $autoSurveyLink.'&'.http_build_query($lpParams);
        }

        return $link;
    }

    /**
     * Gets the name of a resource (generally used in learnpath when no name is provided).
     *
     * @author Yannick Warnier <ywarnier@beeznest.org>
     *
     * @param string $course_code    Course code
     * @param int    $learningPathId
     * @param int    $id_in_path     The resource ID
     *
     * @return string
     */
    public static function rl_get_resource_name($course_code, $learningPathId, $id_in_path)
    {
        $_course = api_get_course_info($course_code);
        if (empty($_course)) {
            return '';
        }
        $course_id = $_course['real_id'];
        $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $learningPathId = (int) $learningPathId;
        $id_in_path = (int) $id_in_path;

        $sql = "SELECT item_type, title, ref
                FROM $tbl_lp_item
                WHERE c_id = $course_id AND lp_id = $learningPathId AND iid = $id_in_path";
        $res_item = Database::query($sql);

        if (Database::num_rows($res_item) < 1) {
            return '';
        }
        $row_item = Database::fetch_array($res_item);
        $type = strtolower($row_item['item_type']);
        $id = $row_item['ref'];
        $output = '';

        switch ($type) {
            case TOOL_CALENDAR_EVENT:
                $TABLEAGENDA = Database::get_course_table(TABLE_AGENDA);
                $result = Database::query("SELECT * FROM $TABLEAGENDA WHERE c_id = $course_id AND id=$id");
                $myrow = Database::fetch_array($result);
                $output = $myrow['title'];
                break;
            case TOOL_ANNOUNCEMENT:
                $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
                $result = Database::query("SELECT * FROM $tbl_announcement WHERE c_id = $course_id AND id=$id");
                $myrow = Database::fetch_array($result);
                $output = $myrow['title'];
                break;
            case TOOL_LINK:
                // Doesn't take $target into account.
                $TABLETOOLLINK = Database::get_course_table(TABLE_LINK);
                $result = Database::query("SELECT * FROM $TABLETOOLLINK WHERE c_id = $course_id AND id=$id");
                $myrow = Database::fetch_array($result);
                $output = $myrow['title'];
                break;
            case TOOL_QUIZ:
                $TBL_EXERCICES = Database::get_course_table(TABLE_QUIZ_TEST);
                $result = Database::query("SELECT * FROM $TBL_EXERCICES WHERE iid = $id");
                $myrow = Database::fetch_array($result);
                $output = $myrow['title'];
                break;
            case TOOL_FORUM:
                $TBL_FORUMS = Database::get_course_table(TABLE_FORUM);
                $result = Database::query("SELECT * FROM $TBL_FORUMS WHERE c_id = $course_id AND forum_id = $id");
                $myrow = Database::fetch_array($result);
                $output = $myrow['forum_name'];
                break;
            case TOOL_THREAD:
                $tbl_post = Database::get_course_table(TABLE_FORUM_POST);
                // Grabbing the title of the post.
                $sql_title = "SELECT * FROM $tbl_post WHERE c_id = $course_id AND post_id=".$id;
                $result_title = Database::query($sql_title);
                $myrow_title = Database::fetch_array($result_title);
                $output = $myrow_title['post_title'];
                break;
            case TOOL_POST:
                $tbl_post = Database::get_course_table(TABLE_FORUM_POST);
                $sql = "SELECT * FROM $tbl_post p WHERE c_id = $course_id AND p.post_id = $id";
                $result = Database::query($sql);
                $post = Database::fetch_array($result);
                $output = $post['post_title'];
                break;
            case 'dir':
            case TOOL_DOCUMENT:
                $title = $row_item['title'];
                $output = '-';
                if (!empty($title)) {
                    $output = $title;
                }
                break;
            case 'hotpotatoes':
                $tbl_doc = Database::get_course_table(TABLE_DOCUMENT);
                $result = Database::query("SELECT * FROM $tbl_doc WHERE c_id = $course_id AND iid = $id");
                $myrow = Database::fetch_array($result);
                $pathname = explode('/', $myrow['path']); // Making a correct name for the link.
                $last = count($pathname) - 1; // Making a correct name for the link.
                $filename = $pathname[$last]; // Making a correct name for the link.
                $myrow['path'] = rawurlencode($myrow['path']);
                $output = $filename;
                break;
        }

        return stripslashes($output);
    }

    /**
     * Get the parent names for the current item.
     *
     * @param int $newItemId Optional. The item ID
     *
     * @return array
     */
    public function getCurrentItemParentNames($newItemId = 0)
    {
        $newItemId = $newItemId ?: $this->get_current_item_id();
        $return = [];
        $item = $this->getItem($newItemId);
        $parent = $this->getItem($item->get_parent());

        while ($parent) {
            $return[] = $parent->get_title();
            $parent = $this->getItem($parent->get_parent());
        }

        return array_reverse($return);
    }

    /**
     * Reads and process "lp_subscription_settings" setting.
     *
     * @return array
     */
    public static function getSubscriptionSettings()
    {
        $subscriptionSettings = api_get_configuration_value('lp_subscription_settings');
        if (empty($subscriptionSettings)) {
            // By default allow both settings
            $subscriptionSettings = [
                'allow_add_users_to_lp' => true,
                'allow_add_users_to_lp_category' => true,
            ];
        } else {
            $subscriptionSettings = $subscriptionSettings['options'];
        }

        return $subscriptionSettings;
    }

    /**
     * Exports a LP to a courseBuilder zip file. It adds the documents related to the LP.
     */
    public function exportToCourseBuildFormat()
    {
        if (!api_is_allowed_to_edit()) {
            return false;
        }

        $courseBuilder = new CourseBuilder();
        $itemList = [];
        /** @var learnpathItem $item */
        foreach ($this->items as $item) {
            $itemList[$item->get_type()][] = $item->get_path();
        }

        if (empty($itemList)) {
            return false;
        }

        if (isset($itemList['document'])) {
            // Get parents
            foreach ($itemList['document'] as $documentId) {
                $documentInfo = DocumentManager::get_document_data_by_id($documentId, api_get_course_id(), true);
                if (!empty($documentInfo['parents'])) {
                    foreach ($documentInfo['parents'] as $parentInfo) {
                        if (in_array($parentInfo['iid'], $itemList['document'])) {
                            continue;
                        }
                        $itemList['document'][] = $parentInfo['iid'];
                    }
                }
            }

            $courseInfo = api_get_course_info();
            foreach ($itemList['document'] as $documentId) {
                $documentInfo = DocumentManager::get_document_data_by_id($documentId, api_get_course_id());
                $items = DocumentManager::get_resources_from_source_html(
                    $documentInfo['absolute_path'],
                    true,
                    TOOL_DOCUMENT
                );

                if (!empty($items)) {
                    foreach ($items as $item) {
                        // Get information about source url
                        $url = $item[0]; // url
                        $scope = $item[1]; // scope (local, remote)
                        $type = $item[2]; // type (rel, abs, url)

                        $origParseUrl = parse_url($url);
                        $realOrigPath = isset($origParseUrl['path']) ? $origParseUrl['path'] : null;

                        if ($scope == 'local') {
                            if ($type == 'abs' || $type == 'rel') {
                                $documentFile = strstr($realOrigPath, 'document');
                                if (strpos($realOrigPath, $documentFile) !== false) {
                                    $documentFile = str_replace('document', '', $documentFile);
                                    $itemDocumentId = DocumentManager::get_document_id($courseInfo, $documentFile);
                                    // Document found! Add it to the list
                                    if ($itemDocumentId) {
                                        $itemList['document'][] = $itemDocumentId;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $courseBuilder->build_documents(
                api_get_session_id(),
                $this->get_course_int_id(),
                true,
                $itemList['document']
            );
        }

        if (isset($itemList['quiz'])) {
            $courseBuilder->build_quizzes(
                api_get_session_id(),
                $this->get_course_int_id(),
                true,
                $itemList['quiz']
            );
        }

        require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';

        /*if (!empty($itemList['thread'])) {
            $postList = [];
            foreach ($itemList['thread'] as $postId) {
                $post = get_post_information($postId);
                if ($post) {
                    if (!isset($itemList['forum'])) {
                        $itemList['forum'] = [];
                    }
                    $itemList['forum'][] = $post['forum_id'];
                    $postList[] = $postId;
                }
            }

            if (!empty($postList)) {
                $courseBuilder->build_forum_posts(
                    $this->get_course_int_id(),
                    null,
                    null,
                    $postList
                );
            }
        }*/

        if (!empty($itemList['thread'])) {
            $threadList = [];
            $em = Database::getManager();
            $repo = $em->getRepository('ChamiloCourseBundle:CForumThread');
            foreach ($itemList['thread'] as $threadId) {
                /** @var \Chamilo\CourseBundle\Entity\CForumThread $thread */
                $thread = $repo->find($threadId);
                if ($thread) {
                    $itemList['forum'][] = $thread->getForumId();
                    $threadList[] = $thread->getIid();
                }
            }

            if (!empty($threadList)) {
                $courseBuilder->build_forum_topics(
                    api_get_session_id(),
                    $this->get_course_int_id(),
                    null,
                    $threadList
                );
            }
        }

        $forumCategoryList = [];
        if (isset($itemList['forum'])) {
            foreach ($itemList['forum'] as $forumId) {
                $forumInfo = get_forums($forumId);
                $forumCategoryList[] = $forumInfo['forum_category'];
            }
        }

        if (!empty($forumCategoryList)) {
            $courseBuilder->build_forum_category(
                api_get_session_id(),
                $this->get_course_int_id(),
                true,
                $forumCategoryList
            );
        }

        if (!empty($itemList['forum'])) {
            $courseBuilder->build_forums(
                api_get_session_id(),
                $this->get_course_int_id(),
                true,
                $itemList['forum']
            );
        }

        if (isset($itemList['link'])) {
            $courseBuilder->build_links(
                api_get_session_id(),
                $this->get_course_int_id(),
                true,
                $itemList['link']
            );
        }

        if (!empty($itemList['student_publication'])) {
            $courseBuilder->build_works(
                api_get_session_id(),
                $this->get_course_int_id(),
                true,
                $itemList['student_publication']
            );
        }

        $courseBuilder->build_learnpaths(
            api_get_session_id(),
            $this->get_course_int_id(),
            true,
            [$this->get_id()],
            false
        );

        $courseBuilder->restoreDocumentsFromList();

        $zipFile = CourseArchiver::createBackup($courseBuilder->course);
        $zipPath = CourseArchiver::getBackupDir().$zipFile;
        $result = DocumentManager::file_send_for_download(
            $zipPath,
            true,
            $this->get_name().'.zip'
        );

        if ($result) {
            api_not_allowed();
        }

        return true;
    }

    /**
     * Get whether this is a learning path with the accumulated work time or not.
     *
     * @return int
     */
    public function getAccumulateWorkTime()
    {
        return (int) $this->accumulateWorkTime;
    }

    /**
     * Get whether this is a learning path with the accumulated work time or not.
     *
     * @return int
     */
    public function getAccumulateWorkTimeTotalCourse()
    {
        $table = Database::get_course_table(TABLE_LP_MAIN);
        $sql = "SELECT SUM(accumulate_work_time) AS total
                FROM $table
                WHERE c_id = ".$this->course_int_id;
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        return (int) $row['total'];
    }

    /**
     * Set whether this is a learning path with the accumulated work time or not.
     *
     * @param int $value (0 = false, 1 = true)
     *
     * @return bool
     */
    public function setAccumulateWorkTime($value)
    {
        if (!api_get_configuration_value('lp_minimum_time')) {
            return false;
        }

        $this->accumulateWorkTime = (int) $value;
        $table = Database::get_course_table(TABLE_LP_MAIN);
        $lp_id = $this->get_id();
        $sql = "UPDATE $table SET accumulate_work_time = ".$this->accumulateWorkTime."
                WHERE c_id = ".$this->course_int_id." AND id = $lp_id";
        Database::query($sql);

        return true;
    }

    /**
     * @param int $lpId
     * @param int $courseId
     *
     * @return mixed
     */
    public static function getAccumulateWorkTimePrerequisite($lpId, $courseId)
    {
        $lpId = (int) $lpId;
        $courseId = (int) $courseId;

        $table = Database::get_course_table(TABLE_LP_MAIN);
        $sql = "SELECT accumulate_work_time
                FROM $table
                WHERE c_id = $courseId AND id = $lpId";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        return $row['accumulate_work_time'];
    }

    /**
     * @param int $courseId
     *
     * @return int
     */
    public static function getAccumulateWorkTimeTotal($courseId)
    {
        $table = Database::get_course_table(TABLE_LP_MAIN);
        $courseId = (int) $courseId;
        $sql = "SELECT SUM(accumulate_work_time) AS total
                FROM $table
                WHERE c_id = $courseId";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        return (int) $row['total'];
    }

    /**
     * In order to use the lp icon option you need to create the "lp_icon" LP extra field
     * and put the images in.
     *
     * @return array
     */
    public static function getIconSelect()
    {
        $theme = api_get_visual_theme();
        $path = api_get_path(SYS_PUBLIC_PATH).'css/themes/'.$theme.'/lp_icons/';
        $icons = ['' => get_lang('SelectAnOption')];

        if (is_dir($path)) {
            $finder = new Finder();
            $finder->files()->in($path);
            $allowedExtensions = ['jpeg', 'jpg', 'png'];
            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                if (in_array(strtolower($file->getExtension()), $allowedExtensions)) {
                    $icons[$file->getFilename()] = $file->getFilename();
                }
            }
        }

        return $icons;
    }

    /**
     * Get the learnpaths availables for next lp.
     *
     * @param int $courseId
     *
     * @return array
     */
    public static function getNextLpsAvailable($courseId, $lpId)
    {
        $table = Database::get_course_table(TABLE_LP_MAIN);

        $sqlNextLp = "SELECT next_lp_id FROM $table WHERE c_id = $courseId AND next_lp_id > 0";
        $sql = "SELECT iid, name
                FROM $table
            WHERE c_id = $courseId AND
                  iid NOT IN($sqlNextLp)";
        $rs = Database::query($sql);
        $lpsAvailable = [0 => get_lang('None')];
        if (Database::num_rows($rs) > 0) {
            while ($row = Database::fetch_array($rs)) {
                if ($row['iid'] == $lpId) {
                    continue;
                }
                $lpsAvailable[$row['iid']] = $row['name'];
            }
        }

        return $lpsAvailable;
    }

    /**
     * Get The next lp id.
     *
     * @param $lpId
     * @param $courseId
     *
     * @return int
     */
    public static function getFlowNextLpId($lpId, $courseId)
    {
        $table = Database::get_course_table(TABLE_LP_MAIN);

        $sql = "SELECT next_lp_id
                FROM $table
            WHERE c_id = $courseId AND iid = $lpId";
        $rs = Database::query($sql);
        $nextLpId = Database::result($rs, 0, 0);

        return $nextLpId;
    }

    /**
     * Get The previous lp id.
     *
     * @param $nextLpId
     * @param $courseId
     *
     * @return int
     */
    public static function getFlowPrevLpId($nextLpId, $courseId)
    {
        $table = Database::get_course_table(TABLE_LP_MAIN);

        $sql = "SELECT iid
                FROM $table
            WHERE c_id = $courseId AND next_lp_id = $nextLpId";
        $rs = Database::query($sql);
        $prevLpId = Database::result($rs, 0, 0);

        return $prevLpId;
    }

    /**
     * Save the next lp id.
     *
     * @param $lpId
     * @param $nextLpId
     */
    public static function saveTheNextLp($lpId, $nextLpId)
    {
        $nextLpId = (int) $nextLpId;
        $table = Database::get_course_table(TABLE_LP_MAIN);
        Database::query("UPDATE $table SET next_lp_id = $nextLpId WHERE iid = $lpId");
    }

    /**
     * @param int $lpId
     *
     * @return string
     */
    public static function getSelectedIcon($lpId)
    {
        $extraFieldValue = new ExtraFieldValue('lp');
        $lpIcon = $extraFieldValue->get_values_by_handler_and_field_variable($lpId, 'lp_icon');
        $icon = '';
        if (!empty($lpIcon) && isset($lpIcon['value'])) {
            $icon = $lpIcon['value'];
        }

        return $icon;
    }

    /**
     * @param int $lpId
     *
     * @return string
     */
    public static function getSelectedIconHtml($lpId)
    {
        $icon = self::getSelectedIcon($lpId);

        if (empty($icon)) {
            return '';
        }

        $theme = api_get_visual_theme();
        $path = api_get_path(WEB_PUBLIC_PATH).'css/themes/'.$theme.'/lp_icons/'.$icon;

        return Display::img($path);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function cleanItemTitle($value)
    {
        $value = Security::remove_XSS(strip_tags($value));

        return $value;
    }

    public function setItemTitle(FormValidator $form)
    {
        if (api_get_configuration_value('save_titles_as_html')) {
            $form->addHtmlEditor(
                'title',
                get_lang('Title'),
                true,
                false,
                ['ToolbarSet' => 'TitleAsHtml', 'id' => uniqid('editor')]
            );
        } else {
            $form->addText('title', get_lang('Title'), true, ['id' => 'idTitle', 'class' => 'learnpath_item_form']);
            $form->applyFilter('title', 'trim');
            $form->applyFilter('title', 'html_filter');
        }
    }

    /**
     * @return array
     */
    public function getItemsForForm($addParentCondition = false)
    {
        $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $course_id = api_get_course_int_id();

        $sql = "SELECT * FROM $tbl_lp_item
                WHERE c_id = $course_id AND lp_id = ".$this->lp_id;

        if ($addParentCondition) {
            $sql .= ' AND parent_item_id = 0 ';
        }
        $sql .= ' ORDER BY display_order ASC';

        $result = Database::query($sql);
        $arrLP = [];
        while ($row = Database::fetch_array($result)) {
            $arrLP[] = [
                'iid' => $row['iid'],
                'id' => $row['iid'],
                'item_type' => $row['item_type'],
                'title' => $this->cleanItemTitle($row['title']),
                'title_raw' => $row['title'],
                'path' => $row['path'],
                'description' => Security::remove_XSS($row['description']),
                'parent_item_id' => $row['parent_item_id'],
                'previous_item_id' => $row['previous_item_id'],
                'next_item_id' => $row['next_item_id'],
                'display_order' => $row['display_order'],
                'max_score' => $row['max_score'],
                'min_score' => $row['min_score'],
                'mastery_score' => $row['mastery_score'],
                'prerequisite' => $row['prerequisite'],
                'max_time_allowed' => $row['max_time_allowed'],
                'prerequisite_min_score' => $row['prerequisite_min_score'],
                'prerequisite_max_score' => $row['prerequisite_max_score'],
            ];
        }

        return $arrLP;
    }

    /**
     * Gets whether this SCORM learning path has been marked to use the score
     * as progress. Takes into account whether the learnpath matches (SCORM
     * content + less than 2 items).
     *
     * @return bool True if the score should be used as progress, false otherwise
     */
    public function getUseScoreAsProgress()
    {
        // If not a SCORM, we don't care about the setting
        if ($this->get_type() != 2) {
            return false;
        }
        // If more than one step in the SCORM, we don't care about the setting
        if ($this->get_total_items_count() > 1) {
            return false;
        }
        $extraFieldValue = new ExtraFieldValue('lp');
        $doUseScore = false;
        $useScore = $extraFieldValue->get_values_by_handler_and_field_variable($this->get_id(), 'use_score_as_progress');
        if (!empty($useScore) && isset($useScore['value'])) {
            $doUseScore = $useScore['value'];
        }

        return $doUseScore;
    }

    /**
     * Get the user identifier (user_id or username
     * Depends on scorm_api_username_as_student_id in app/config/configuration.php.
     *
     * @return string User ID or username, depending on configuration setting
     */
    public static function getUserIdentifierForExternalServices()
    {
        if (api_get_configuration_value('scorm_api_username_as_student_id')) {
            return api_get_user_info(api_get_user_id())['username'];
        } elseif (api_get_configuration_value('scorm_api_extrafield_to_use_as_student_id') != null) {
            $extraFieldValue = new ExtraFieldValue('user');
            $extrafield = $extraFieldValue->get_values_by_handler_and_field_variable(api_get_user_id(), api_get_configuration_value('scorm_api_extrafield_to_use_as_student_id'));

            return $extrafield['value'];
        } else {
            return api_get_user_id();
        }
    }

    /**
     * Save the new order for learning path items.
     *
     * We have to update parent_item_id, previous_item_id, next_item_id, display_order in the database.
     *
     * @param array $orderList A associative array with item ID as key and parent ID as value.
     * @param int   $courseId
     */
    public static function sortItemByOrderList(array $orderList, $courseId = 0)
    {
        $courseId = $courseId ?: api_get_course_int_id();
        $itemList = new LpItemOrderList();

        foreach ($orderList as $id => $parentId) {
            $item = new LpOrderItem($id, $parentId);
            $itemList->add($item);
        }

        $parents = $itemList->getListOfParents();

        foreach ($parents as $parentId) {
            $sameParentLpItemList = $itemList->getItemWithSameParent($parentId);
            $previous_item_id = 0;
            for ($i = 0; $i < count($sameParentLpItemList->list); $i++) {
                $item_id = $sameParentLpItemList->list[$i]->id;
                // display_order
                $display_order = $i + 1;
                $itemList->setParametersForId($item_id, $display_order, 'display_order');
                // previous_item_id
                $itemList->setParametersForId($item_id, $previous_item_id, 'previous_item_id');
                $previous_item_id = $item_id;
                // next_item_id
                $next_item_id = 0;
                if ($i < count($sameParentLpItemList->list) - 1) {
                    $next_item_id = $sameParentLpItemList->list[$i + 1]->id;
                }
                $itemList->setParametersForId($item_id, $next_item_id, 'next_item_id');
            }
        }

        $table = Database::get_course_table(TABLE_LP_ITEM);

        foreach ($itemList->list as $item) {
            $params = [];
            $params['display_order'] = $item->display_order;
            $params['previous_item_id'] = $item->previous_item_id;
            $params['next_item_id'] = $item->next_item_id;
            $params['parent_item_id'] = $item->parent_item_id;

            Database::update(
                $table,
                $params,
                [
                    'iid = ? AND c_id = ? ' => [
                        (int) $item->id,
                        (int) $courseId,
                    ],
                ]
            );
        }
    }

    public static function findLastView(
        int $lpId,
        int $studentId,
        int $courseId,
        int $sessionId = 0,
        bool $createIfNotExists = false
    ): array {
        $tblLpView = Database::get_course_table(TABLE_LP_VIEW);

        $sessionCondition = api_get_session_condition($sessionId);

        $sql = "SELECT iid FROM $tblLpView
            WHERE c_id = $courseId AND lp_id = $lpId AND user_id = $studentId $sessionCondition
            ORDER BY view_count DESC";
        $result = Database::query($sql);

        $lpView = Database::fetch_assoc($result);

        if ($createIfNotExists && empty($lpView)) {
            $lpViewId = Database::insert(
                $tblLpView,
                [
                    'c_id' => $courseId,
                    'lp_id' => $lpId,
                    'user_id' => $studentId,
                    'view_count' => 1,
                    'session_id' => $sessionId,
                ]
            );
            Database::update($tblLpView, ['id' => $lpViewId], ['iid = ?' => $lpViewId]);

            return ['iid' => $lpViewId];
        }

        return empty($lpView) ? [] : $lpView;
    }

    /**
     * Check and obtain the lp final item if exist.
     *
     * @return learnpathItem
     */
    public function getFinalItem()
    {
        if (empty($this->items)) {
            return null;
        }

        foreach ($this->items as $item) {
            if ($item->type !== 'final_item') {
                continue;
            }

            return $item;
        }
    }

    /**
     * Get the depth level of LP item.
     *
     * @param array $items
     * @param int   $currentItemId
     *
     * @return int
     */
    private static function get_level_for_item($items, $currentItemId)
    {
        $parentItemId = 0;
        if (isset($items[$currentItemId])) {
            $parentItemId = $items[$currentItemId]->parent;
        }

        if ($parentItemId == 0) {
            return 0;
        } else {
            return self::get_level_for_item($items, $parentItemId) + 1;
        }
    }

    /**
     * Generate the link for a learnpath category as course tool.
     *
     * @param int $categoryId
     *
     * @return string
     */
    private static function getCategoryLinkForTool($categoryId)
    {
        $categoryId = (int) $categoryId;
        $link = 'lp/lp_controller.php?'.api_get_cidreq().'&'
            .http_build_query(
                [
                    'action' => 'view_category',
                    'id' => $categoryId,
                ]
            );

        return $link;
    }

    /**
     * Return the scorm item type object with spaces replaced with _
     * The return result is use to build a css classname like scorm_type_$return.
     *
     * @param $in_type
     *
     * @return mixed
     */
    private static function format_scorm_type_item($in_type)
    {
        return str_replace(' ', '_', $in_type);
    }

    /**
     * Get the LP Final Item Template.
     *
     * @return string
     */
    private function getFinalItemTemplate()
    {
        return file_get_contents(api_get_path(SYS_CODE_PATH).'lp/final_item_template/template.html');
    }

    /**
     * Get the LP Final Item Url.
     *
     * @return string
     */
    private function getSavedFinalItem()
    {
        $finalItem = $this->getFinalItem();
        $doc = DocumentManager::get_document_data_by_id(
            $finalItem->path,
            $this->cc
        );
        if ($doc && file_exists($doc['absolute_path'])) {
            return file_get_contents($doc['absolute_path']);
        }

        return '';
    }

    /**
     * Gets the form to evaluate if it exists contains the extra field extra_authorlpitem
     * to establish authors when editing an item of an LP.
     */
    private function setAuthorLpItem(FormValidator $form)
    {
        if ($form->hasElement('extra_authorlpitem')) {
            /** @var HTML_QuickForm_select $author */
            $author = $form->getElement('extra_authorlpitem');
            $options = [];
            $field = new ExtraField('user');
            $authorLp = $field->get_handler_field_info_by_field_variable('authorlp');
            $extraFieldId = isset($authorLp['id']) ? (int) $authorLp['id'] : 0;
            if ($extraFieldId != 0) {
                $extraFieldValueUser = new ExtraFieldValue('user');
                $values = $extraFieldValueUser->get_item_id_from_field_variable_and_field_value(
                    $authorLp['variable'],
                    1,
                    true,
                    false,
                    true
                );

                if (!empty($values)) {
                    foreach ($values as $item) {
                        $teacher = api_get_user_info($item['item_id']);
                        $options[$teacher['id']] = $teacher['complete_name'];
                    }
                }
            }
            $author->setOptions($options);
        }
    }
}

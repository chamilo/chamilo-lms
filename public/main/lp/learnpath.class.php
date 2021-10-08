<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Component\CourseCopy\CourseArchiver;
use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CTool;
use ChamiloSession as Session;
use Doctrine\Common\Collections\Criteria;
use PhpZip\ZipFile;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
    public const MAX_LP_ITEM_TITLE_LENGTH = 36;
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
    public $ref;
    public $course_int_id;
    public $course_info;
    public $categoryId;
    public $scormUrl;
    public $entity;

    public function __construct(CLp $entity = null, $course_info, $user_id)
    {
        $debug = $this->debug;
        $user_id = (int) $user_id;
        $this->encoding = api_get_system_encoding();
        $lp_id = 0;
        if (null !== $entity) {
            $lp_id = $entity->getIid();
        }
        $course_info = empty($course_info) ? api_get_course_info() : $course_info;
        $course_id = (int) $course_info['real_id'];
        $this->course_info = $course_info;
        $this->set_course_int_id($course_id);
        if (empty($lp_id) || empty($course_id)) {
            $this->error = "Parameter is empty: LpId:'$lp_id', courseId: '$lp_id'";
        } else {
            //$this->entity = $entity;
            $this->lp_id = $lp_id;
            $this->type = $entity->getLpType();
            $this->name = stripslashes($entity->getName());
            $this->proximity = $entity->getContentLocal();
            $this->theme = $entity->getTheme();
            $this->maker = $entity->getContentLocal();
            $this->prevent_reinit = $entity->getPreventReinit();
            $this->seriousgame_mode = $entity->getSeriousgameMode();
            $this->license = $entity->getContentLicense();
            $this->scorm_debug = $entity->getDebug();
            $this->js_lib = $entity->getJsLib();
            $this->path = $entity->getPath();
            $this->author = $entity->getAuthor();
            $this->hide_toc_frame = $entity->getHideTocFrame();
            //$this->lp_session_id = $entity->getSessionId();
            $this->use_max_score = $entity->getUseMaxScore();
            $this->subscribeUsers = $entity->getSubscribeUsers();
            $this->created_on = $entity->getCreatedOn()->format('Y-m-d H:i:s');
            $this->modified_on = $entity->getModifiedOn()->format('Y-m-d H:i:s');
            $this->ref = $entity->getRef();
            $this->categoryId = 0;
            if ($entity->getCategory()) {
                $this->categoryId = $entity->getCategory()->getIid();
            }

            if ($entity->hasAsset()) {
                $asset = $entity->getAsset();
                $this->scormUrl = Container::getAssetRepository()->getAssetUrl($asset).'/'.$entity->getPath().'/';
            }

            $this->accumulateScormTime = $entity->getAccumulateWorkTime();

            if (!empty($entity->getPublicatedOn())) {
                $this->publicated_on = $entity->getPublicatedOn()->format('Y-m-d H:i:s');
            }

            if (!empty($entity->getExpiredOn())) {
                $this->expired_on = $entity->getExpiredOn()->format('Y-m-d H:i:s');
            }
            if (2 == $this->type) {
                if (1 == $entity->getForceCommit()) {
                    $this->force_commit = true;
                }
            }
            $this->mode = $entity->getDefaultViewMod();

            // Check user ID.
            if (empty($user_id)) {
                $this->error = 'User ID is empty';
            } else {
                $this->user_id = $user_id;
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

            if (Database::num_rows($res) > 0) {
                $row = Database::fetch_array($res);
                $this->attempt = $row['view_count'];
                $this->lp_view_id = $row['iid'];
                $this->last_item_seen = $row['last_item'];
                $this->progress_db = $row['progress'];
                $this->lp_view_session_id = $row['session_id'];
            } elseif (!api_is_invitee()) {
                $this->attempt = 1;
                $params = [
                    'c_id' => $course_id,
                    'lp_id' => $lp_id,
                    'user_id' => $user_id,
                    'view_count' => 1,
                    //'session_id' => $session_id,
                    'last_item' => 0,
                ];
                if (!empty($session_id)) {
                    $params['session_id'] = $session_id;
                }
                $this->last_item_seen = 0;
                $this->lp_view_session_id = $session_id;
                $this->lp_view_id = Database::insert($lp_table, $params);
            }

            $criteria = new Criteria();
            $criteria
                ->where($criteria->expr()->neq('path', 'root'))
                ->orderBy(
                    [
                        'parent' => Criteria::ASC,
                        'displayOrder' => Criteria::ASC,
                    ]
                );
            $items = $entity->getItems()->matching($criteria);
            $lp_item_id_list = [];
            foreach ($items as $item) {
                $itemId = $item->getIid();
                $lp_item_id_list[] = $itemId;

                switch ($this->type) {
                    case CLp::AICC_TYPE:
                        $oItem = new aiccItem('db', $itemId, $course_id);
                        if (is_object($oItem)) {
                            $oItem->set_lp_view($this->lp_view_id);
                            $oItem->set_prevent_reinit($this->prevent_reinit);
                            // Don't use reference here as the next loop will make the pointed object change.
                            $this->items[$itemId] = $oItem;
                            $this->refs_list[$oItem->ref] = $itemId;
                        }
                        break;
                    case CLp::SCORM_TYPE:
                        $oItem = new scormItem('db', $itemId);
                        if (is_object($oItem)) {
                            $oItem->set_lp_view($this->lp_view_id);
                            $oItem->set_prevent_reinit($this->prevent_reinit);
                            // Don't use reference here as the next loop will make the pointed object change.
                            $this->items[$itemId] = $oItem;
                            $this->refs_list[$oItem->ref] = $itemId;
                        }
                        break;
                    case CLp::LP_TYPE:
                    default:
                        $oItem = new learnpathItem(null, $item);
                        if (is_object($oItem)) {
                            // Moved down to when we are sure the item_view exists.
                            //$oItem->set_lp_view($this->lp_view_id);
                            $oItem->set_prevent_reinit($this->prevent_reinit);
                            // Don't use reference here as the next loop will make the pointed object change.
                            $this->items[$itemId] = $oItem;
                            $this->refs_list[$itemId] = $itemId;
                        }
                        break;
                }

                // Setting the object level with variable $this->items[$i][parent]
                foreach ($this->items as $itemLPObject) {
                    $level = self::get_level_for_item($this->items, $itemLPObject->db_id);
                    $itemLPObject->level = $level;
                }

                // Setting the view in the item object.
                if (isset($this->items[$itemId]) && is_object($this->items[$itemId])) {
                    $this->items[$itemId]->set_lp_view($this->lp_view_id);
                    if (TOOL_HOTPOTATOES == $this->items[$itemId]->get_type()) {
                        $this->items[$itemId]->current_start_time = 0;
                        $this->items[$itemId]->current_stop_time = 0;
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
                                lp_view_id = ".$this->get_view_id()." AND
                                lp_item_id IN ('".$lp_item_id_list_to_string."')
                            ORDER BY view_count DESC ";
                    $status_list = [];
                    $res = Database::query($sql);
                    while ($row = Database:: fetch_array($res)) {
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
                                if (isset($this->items[$item_id]) && is_object($this->items[$item_id])) {
                                    $this->items[$item_id]->set_status(
                                        $this->default_status
                                    );
                                }

                                if (!empty($this->lp_view_id)) {
                                    // Add that row to the lp_item_view table so that
                                    // we have something to show in the stats page.
                                    $params = [
                                        'lp_item_id' => $item_id,
                                        'lp_view_id' => $this->lp_view_id,
                                        'view_count' => 1,
                                        'status' => 'not attempted',
                                        'start_time' => time(),
                                        'total_time' => 0,
                                        'score' => 0,
                                    ];
                                    Database::insert($itemViewTable, $params);

                                    $this->items[$item_id]->set_lp_view(
                                        $this->lp_view_id
                                    );
                                }
                            }
                        }
                    }
                }
            }

            $this->ordered_items = self::get_flat_ordered_items_list($entity, null);
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
        }
    }

    /**
     * @return int
     */
    public function get_course_int_id()
    {
        return $this->course_int_id ?? api_get_course_int_id();
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
     * @param CLpItem $parent
     * @param int     $previousId
     * @param string  $type
     * @param int     $id resource ID (ref)
     * @param string  $title
     * @param string  $description
     * @param int     $prerequisites
     * @param int     $max_time_allowed
     * @param int     $userId
     *
     * @return int
     */
    public function add_item(
        ?CLpItem $parent,
        $previousId,
        $type,
        $id,
        $title,
        $description = '',
        $prerequisites = 0,
        $max_time_allowed = 0
    ) {
        $type = empty($type) ? 'dir' : $type;
        $course_id = $this->course_info['real_id'];
        if (empty($course_id)) {
            // Sometimes Oogie doesn't catch the course info but sets $this->cc
            $this->course_info = api_get_course_info($this->cc);
            $course_id = $this->course_info['real_id'];
        }
        $id = (int) $id;
        $max_time_allowed = (int) $max_time_allowed;
        if (empty($max_time_allowed)) {
            $max_time_allowed = 0;
        }
        $max_score = 100;
        if ('quiz' === $type && $id) {
            // Disabling the exercise if we add it inside a LP
            $exercise = new Exercise($course_id);
            $exercise->read($id);
            $max_score = $exercise->get_max_score();

            $exercise->disable();
            $exercise->save();
            $title = $exercise->get_formated_title();
        }

        $lpItem = (new CLpItem())
            ->setTitle($title)
            ->setDescription($description)
            ->setPath($id)
            ->setLp(api_get_lp_entity($this->get_id()))
            ->setItemType($type)
            ->setMaxScore($max_score)
            ->setMaxTimeAllowed($max_time_allowed)
            ->setPrerequisite($prerequisites)
            //->setDisplayOrder($display_order + 1)
            //->setNextItemId((int) $next)
            //->setPreviousItemId($previous)
        ;

        if (!empty($parent))  {
            $lpItem->setParent($parent);
        }
        $em = Database::getManager();
        $em->persist($lpItem);
        $em->flush();

        $new_item_id = $lpItem->getIid();
        if ($new_item_id) {
            // @todo fix upload audio.
            // Upload audio.
            /*if (!empty($_FILES['mp3']['name'])) {
                // Create the audio folder if it does not exist yet.
                $filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
                if (!is_dir($filepath.'audio')) {
                    mkdir(
                        $filepath.'audio',
                        api_get_permissions_for_new_directories()
                    );
                    DocumentManager::addDocument(
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

                // Getting the filename only.
                $file_components = explode('/', $file_path);
                $file = $file_components[count($file_components) - 1];

                // Store the mp3 file in the lp_item table.
                $sql = "UPDATE $tbl_lp_item SET
                          audio = '".Database::escape_string($file)."'
                        WHERE iid = '".intval($new_item_id)."'";
                Database::query($sql);
            }*/
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
     * @return CLp
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

        $categoryId = (int) $categoryId;

        if (empty($publicated_on)) {
            $publicated_on = null;
        } else {
            $publicated_on = api_get_utc_datetime($publicated_on, true, true);
        }

        if (empty($expired_on)) {
            $expired_on = null;
        } else {
            $expired_on = api_get_utc_datetime($expired_on, true, true);
        }

        $description = Database::escape_string(api_htmlentities($description, ENT_QUOTES));
        $type = 1;
        switch ($learnpath) {
            case 'guess':
            case 'aicc':
                break;
            case 'dokeos':
            case 'chamilo':
                $type = 1;
                break;
        }

        $sessionEntity = api_get_session_entity();
        $courseEntity = api_get_course_entity($courseInfo['real_id']);
        $lp = null;
        switch ($origin) {
            case 'zip':
                // Check zip name string. If empty, we are currently creating a new Chamilo learnpath.
                break;
            case 'manual':
            default:
                /*$get_max = "SELECT MAX(display_order)
                            FROM $tbl_lp WHERE c_id = $course_id";
                $res_max = Database::query($get_max);
                if (Database::num_rows($res_max) < 1) {
                    $dsp = 1;
                } else {
                    $row = Database::fetch_array($res_max);
                    $dsp = $row[0] + 1;
                }*/

                $dsp = 1;
                $category = null;
                if (!empty($categoryId)) {
                    $category = Container::getLpCategoryRepository()->find($categoryId);
                }

                $lpRepo = Container::getLpRepository();

                $lp = (new CLp())
                    ->setLpType($type)
                    ->setName($name)
                    ->setDescription($description)
                    ->setDisplayOrder($dsp)
                    ->setCategory($category)
                    ->setPublicatedOn($publicated_on)
                    ->setExpiredOn($expired_on)
                    ->setParent($courseEntity)
                    ->addCourseLink($courseEntity, $sessionEntity)
                ;
                $lpRepo->createLp($lp);

                break;
        }

        return $lp;
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
                            $childItem->set_lp_view($this->lp_view_id);
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

        $lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $lp_view = Database::get_course_table(TABLE_LP_VIEW);
        $lp_item_view = Database::get_course_table(TABLE_LP_ITEM_VIEW);

        // Delete lp item id.
        foreach ($this->items as $lpItemId => $dummy) {
            $sql = "DELETE FROM $lp_item_view
                    WHERE lp_item_id = '".$lpItemId."'";
            Database::query($sql);
        }

        // Proposed by Christophe (nickname: clefevre)
        $sql = "DELETE FROM $lp_item
                WHERE lp_id = ".$this->lp_id;
        Database::query($sql);

        $sql = "DELETE FROM $lp_view
                WHERE lp_id = ".$this->lp_id;
        Database::query($sql);

        $table = Database::get_course_table(TABLE_LP_REL_USERGROUP);
        $sql = "DELETE FROM $table
                WHERE
                    lp_id = {$this->lp_id}";
        Database::query($sql);

        $repo = Container::getLpRepository();
        $lp = $repo->find($this->lp_id);
        Database::getManager()->remove($lp);
        Database::getManager()->flush();

        // Updates the display order of all lps.
        $this->update_display_order();

        $link_info = GradebookUtils::isResourceInCourseGradebook(
            api_get_course_id(),
            4,
            $id,
            api_get_session_id()
        );

        if (false !== $link_info) {
            GradebookUtils::remove_resource_from_course_gradebook($link_info['id']);
        }

        if ('true' === api_get_setting('search_enabled')) {
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
                WHERE parent_item_id = $id";
        $res = Database::query($sql);
        while ($row = Database::fetch_array($res)) {
            $num += $this->delete_children_items($row['iid']);
            $sql = "DELETE FROM $lp_item
                    WHERE iid = ".$row['iid'];
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

        $repo = Container::getLpItemRepository();
        $item = $repo->find($id);
        if (null === $item) {
            return false;
        }

        $em = Database::getManager();
        $repo->removeFromTree($item);
        $em->flush();
        $lp_item = Database::get_course_table(TABLE_LP_ITEM);

        //Removing prerequisites since the item will not longer exist
        $sql_all = "UPDATE $lp_item SET prerequisite = ''
                    WHERE prerequisite = '$id'";
        Database::query($sql_all);

        $sql = "UPDATE $lp_item
                SET previous_item_id = ".$this->getLastInFirstLevel()."
                WHERE lp_id = {$this->lp_id} AND item_type = '".TOOL_LP_FINAL_ITEM."'";
        Database::query($sql);

        // Remove from search engine if enabled.
        if ('true' === api_get_setting('search_enabled')) {
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
        $_course = api_get_course_info();
        $id = (int) $id;

        if (empty($id) || empty($_course)) {
            return false;
        }
        $repo = Container::getLpItemRepository();
        /** @var CLpItem $item */
        $item = $repo->find($id);
        if (null === $item) {
            return false;
        }

        $item
            ->setTitle($title)
            ->setDescription($description)
            ->setPrerequisite($prerequisites)
            ->setMaxTimeAllowed((int) $max_time_allowed)
        ;

        $em = Database::getManager();
        if (!empty($parent)) {
            $parent = $repo->find($parent);
            $item->setParent($parent);
        } else {
            $item->setParent(null);
        }

        if (!empty($previous)) {
            $previous = $repo->find($previous);
            $repo->persistAsNextSiblingOf( $item, $previous);
        } else {
            $em->persist($item);
        }

        $em->flush();

        if ('link' === $item->getItemType()) {
            $link = new Link();
            $linkId = $item->getPath();
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
    public function edit_item_prereq($id, $prerequisite_id, $minScore = 0, $maxScore = 100)
    {
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
     * @param bool Flag to determine the failed status is not considered progressed
     *
     * @return int The number of items currently completed
     */
    public function get_complete_items_count(bool $failedStatusException = false): int
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

        foreach ($this->items as $id => $dummy) {
            // Trying failed and browsed considered "progressed" as well.
            if ($this->items[$id]->status_is($completedStatusList) &&
                'dir' !== $this->items[$id]->get_type()
            ) {
                $i++;
            }
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
    public function getTotalItemsCountWithoutDirs()
    {
        $total = 0;
        $typeListNotToCount = self::getChapterTypes();
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
        if (0 == count($this->ordered_items)) {
            $this->index = 0;
        }

        if (!empty($this->last_item_seen) &&
            !empty($this->items[$this->last_item_seen]) &&
            'dir' !== $this->items[$this->last_item_seen]->get_type()
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
            if (-1 == $index) {
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
                    'dir' === $this->items[$this->ordered_items[$index]]->get_type() ||
                    true === $this->items[$this->ordered_items[$index]]->is_done()
                ) && $index < $this->max_ordered_items
            ) {
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
                WHERE i.lp = :lp AND i.parent IS NULL AND i.itemType != :type ORDER BY i.displayOrder DESC')
                ->setMaxResults(1)
                ->setParameters(['lp' => $this->lp_id, 'type' => TOOL_LP_FINAL_ITEM])
                ->getSingleScalarResult();

            return $lastId;
        } catch (Exception $exception) {
            return 0;
        }
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
        $previousText = get_lang('Previous');
        $nextText = get_lang('Next');
        $fullScreenText = get_lang('Back to normal screen');

        $settings = api_get_configuration_value('lp_view_settings');
        $display = $settings['display'] ?? false;
        $icon = Display::getMdiIcon('information');

        $reportingIcon = '
            <a class="icon-toolbar"
                id="stats_link"
                href="lp_controller.php?action=stats&'.api_get_cidreq(true).'&lp_id='.$lpId.'"
                onclick="window.parent.API.save_asset(); return true;"
                target="content_name" title="'.$reportingText.'">
                '.$icon.'<span class="sr-only">'.$reportingText.'</span>
            </a>';

        if (!empty($display)) {
            $showReporting = isset($display['show_reporting_icon']) ? $display['show_reporting_icon'] : true;
            if (false === $showReporting) {
                $reportingIcon = '';
            }
        }

        $hideArrows = false;
        if (isset($settings['display']) && isset($settings['display']['hide_lp_arrow_navigation'])) {
            $hideArrows = $settings['display']['hide_lp_arrow_navigation'];
        }

        $previousIcon = '';
        $nextIcon = '';
        if (false === $hideArrows) {
            $icon = Display::getMdiIcon('chevron-left');
            $previousIcon = '
                <a class="icon-toolbar" id="scorm-previous" href="#"
                    onclick="switch_item('.$mycurrentitemid.',\'previous\');return false;" title="'.$previousText.'">
                    '.$icon.'<span class="sr-only">'.$previousText.'</span>
                </a>';

            $icon = Display::getMdiIcon('chevron-right');
            $nextIcon = '
                <a class="icon-toolbar" id="scorm-next" href="#"
                    onclick="switch_item('.$mycurrentitemid.',\'next\');return false;" title="'.$nextText.'">
                    '.$icon.'<span class="sr-only">'.$nextText.'</span>
                </a>';
        }

        if ('fullscreen' === $this->mode) {
            $icon = Display::getMdiIcon('view-column');
            $navbar = '
                  <span id="'.$barId.'" class="buttons">
                    '.$reportingIcon.'
                    '.$previousIcon.'
                    '.$nextIcon.'
                    <a class="icon-toolbar" id="view-embedded"
                        href="lp_controller.php?action=mode&mode=embedded" target="_top" title="'.$fullScreenText.'">
                        '.$icon.'<span class="sr-only">'.$fullScreenText.'</span>
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
            !empty($this->ordered_items[$index]) && ('dir' == $this->items[$this->ordered_items[$index]]->get_type()) &&
            $index < $this->max_ordered_items
        ) {
            $index++;
            if ($index == $this->max_ordered_items) {
                if ('dir' === $this->items[$this->ordered_items[$index]]->get_type()) {
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
     * Returns the package type ('scorm','aicc','scorm2004','ppt'...).
     *
     * Generally, the package provided is in the form of a zip file, so the function
     * has been written to test a zip file. If not a zip, the function will return the
     * default return value: ''
     *
     * @param string $filePath the path to the file
     * @param string $file_name the original name of the file
     *
     * @return string 'scorm','aicc','scorm2004','error-empty-package'
     *                if the package is empty, or '' if the package cannot be recognized
     */
    public static function getPackageType($filePath, $file_name)
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

        $zipFile = new ZipFile();
        $zipFile->openFile($filePath);
        $zipContentArray = $zipFile->getEntries();
        $package_type = '';
        $manifest = '';
        $aicc_match_crs = 0;
        $aicc_match_au = 0;
        $aicc_match_des = 0;
        $aicc_match_cst = 0;
        $countItems = 0;
        // The following loop should be stopped as soon as we found the right imsmanifest.xml (how to recognize it?).
        if ($zipContentArray) {
            $countItems = count($zipContentArray);
            if ($countItems > 0) {
                foreach ($zipContentArray as $thisContent) {
                    $fileName = basename($thisContent->getName());
                    if (preg_match('~.(php.*|phtml)$~i', $fileName)) {
                        // New behaviour: Don't do anything. These files will be removed in scorm::import_package.
                    } elseif (false !== stristr($fileName, 'imsmanifest.xml')) {
                        $manifest = $fileName; // Just the relative directory inside scorm/
                        $package_type = 'scorm';
                        break; // Exit the foreach loop.
                    } elseif (
                        preg_match('/aicc\//i', $fileName) ||
                        in_array(
                            strtolower(pathinfo($fileName, PATHINFO_EXTENSION)),
                            ['crs', 'au', 'des', 'cst']
                        )
                    ) {
                        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
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
                ('dir' === $this->items[$this->ordered_items[$index]]->get_type())
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
        $courseInfo = api_get_course_info();
        $lpItemId = (int) $lpItemId;

        if (empty($courseInfo) || empty($lpItemId)) {
            return '';
        }
        $item = $this->items[$lpItemId] ?? null;

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
                    lpi.iid = $lpItemId
                ";
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
                        if (1 == $_SESSION['oLP']->prevent_reinit) {
                            $autostart_audio = 'completed' === $row['status'] ? 'false' : 'true';
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
     * @param int    $studentId
     * @param int    $prerequisite
     * @param Course $course
     * @param int    $sessionId
     *
     * @return bool
     */
    public static function isBlockedByPrerequisite(
        $studentId,
        $prerequisite,
        Course $course,
        $sessionId
    ) {
        $courseId = $course->getId();

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
     */
    public static function is_lp_visible_for_student(CLp $lp, $student_id, Course $course, SessionEntity $session = null): bool
    {
        $sessionId = $session ? $session->getId() : 0;
        $courseId = $course->getId();
        $visibility = $lp->isVisible($course, $session);

        // If the item was deleted.
        if (false === $visibility) {
            return false;
        }

        $now = time();
        if ($lp->hasCategory()) {
            $category = $lp->getCategory();

            if (false === self::categoryIsVisibleForStudent(
                    $category,
                    api_get_user_entity($student_id),
                    $course,
                    $session
                )) {
                return false;
            }

            $prerequisite = $lp->getPrerequisite();
            $is_visible = true;

            $isBlocked = self::isBlockedByPrerequisite(
                $student_id,
                $prerequisite,
                $course,
                $sessionId
            );

            if ($isBlocked) {
                $is_visible = false;
            }

            // Also check the time availability of the LP
            if ($is_visible) {
                // Adding visibility restrictions
                if (null !== $lp->getPublicatedOn()) {
                    if ($now < $lp->getPublicatedOn()->getTimestamp()) {
                        $is_visible = false;
                    }
                }
                // Blocking empty start times see BT#2800
                global $_custom;
                if (isset($_custom['lps_hidden_when_no_start_date']) &&
                    $_custom['lps_hidden_when_no_start_date']
                ) {
                    if (null !== $lp->getPublicatedOn()) {
                        $is_visible = false;
                    }
                }

                if (null !== $lp->getExpiredOn()) {
                    if ($now > $lp->getExpiredOn()->getTimestamp()) {
                        $is_visible = false;
                    }
                }
            }

            if ($is_visible) {
                $subscriptionSettings = self::getSubscriptionSettings();

                // Check if the subscription users/group to a LP is ON
                if (1 == $lp->getSubscribeUsers() &&
                    true === $subscriptionSettings['allow_add_users_to_lp']
                ) {
                    // Try group
                    $is_visible = false;
                    // Checking only the user visibility
                    // @todo fix visibility
                    $userVisibility = 1;
                    if (1 == $userVisibility) {
                        $is_visible = true;
                    } else {
                        $userGroups = GroupManager::getAllGroupPerUserSubscription($student_id, $courseId);
                        if (!empty($userGroups)) {
                            foreach ($userGroups as $groupInfo) {
                                $groupId = $groupInfo['iid'];
                                // @todo fix visibility.
                                $userVisibility = 1;
                                if (1 == $userVisibility) {
                                    $is_visible = true;
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            return $is_visible;
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

        return '<div class="progress">
            <div id="progress_bar_value"
                class="progress-bar progress-bar-success" role="progressbar"
                aria-valuenow="'.$percentage.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$text.';">
            '.$text.'
            </div>
        </div>';
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
        if (true === $scoreAsProgressSetting) {
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
        if (0 != $add) {
            $completeItems += $add;
        }
        if ($completeItems > $total_items) {
            $completeItems = $total_items;
        }
        if ('%' === $mode) {
            if ($total_items > 0) {
                $percentage = ((float) $completeItems / (float) $total_items) * 100;
            }
            $percentage = number_format($percentage, 0);
            $text = '%';
        } elseif ('abs' === $mode) {
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
     * @param resource $children Reference to a list of objects to search for the given ITEM_*
     * @param string   $id       The identifier to look for
     *
     * @return mixed The reference to the element found with that identifier. False if not found
     */
    public function get_scorm_xml_node(&$children, $id)
    {
        for ($i = 0; $i < $children->length; $i++) {
            $item_temp = $children->item($i);
            if ('item' === $item_temp->nodeName) {
                if ($item_temp->getAttribute('identifier') == $id) {
                    return $item_temp;
                }
            }
            $subchildren = $item_temp->childNodes;
            if ($subchildren && $subchildren->length > 0) {
                $val = $this->get_scorm_xml_node($subchildren, $id);
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
                'id' => api_htmlentities(get_lang('Interaction ID'), ENT_QUOTES),
                'type' => api_htmlentities(get_lang('Type'), ENT_QUOTES),
                'time' => api_htmlentities(get_lang('Time (finished at...)'), ENT_QUOTES),
                'correct_responses' => api_htmlentities(get_lang('Correct answers'), ENT_QUOTES),
                'student_response' => api_htmlentities(get_lang('Learner answers'), ENT_QUOTES),
                'result' => api_htmlentities(get_lang('Result'), ENT_QUOTES),
                'latency' => api_htmlentities(get_lang('Time spent'), ENT_QUOTES),
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
                'objective_id' => api_htmlentities(get_lang('Objective ID'), ENT_QUOTES),
                'score_raw' => api_htmlentities(get_lang('Objective raw score'), ENT_QUOTES),
                'score_max' => api_htmlentities(get_lang('Objective max score'), ENT_QUOTES),
                'score_min' => api_htmlentities(get_lang('Objective min score'), ENT_QUOTES),
                'status' => api_htmlentities(get_lang('Objective status'), ENT_QUOTES),
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
     */
    public function get_toc(): array
    {
        $toc = [];
        foreach ($this->ordered_items as $item_id) {
            // TODO: Change this link generation and use new function instead.
            $toc[] = [
                'id' => $item_id,
                'title' => $this->items[$item_id]->get_title(),
                'status' => $this->items[$item_id]->get_status(false),
                'status_class' => self::getStatusCSSClassName($this->items[$item_id]->get_status(false)),
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
     * @return mixed Type ID or name, depending on the parameter
     */
    public static function get_type_static($lp_id = 0)
    {
        $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
        $lp_id = (int) $lp_id;
        $sql = "SELECT lp_type FROM $tbl_lp
                WHERE iid = $lp_id";
        $res = Database::query($sql);
        if (false === $res) {
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
     * @param CLp $lp
     * @param int $parent    Parent ID of the items to look for
     *
     * @return array Ordered list of item IDs (empty array on error)
     */
    public static function get_flat_ordered_items_list(CLp $lp, $parent = 0)
    {
        $parent = (int) $parent;
        $lpItemRepo = Container::getLpItemRepository();
        if (empty($parent)) {
            $rootItem = $lpItemRepo->getRootItem($lp->getIid());
            if (null !== $rootItem) {
                $parent = $rootItem->getIid();
            }
        }

        if (empty($parent)) {
            return [];
        }

        $criteria = new Criteria();
        $criteria
            ->where($criteria->expr()->neq('path', 'root'))
            ->orderBy(
                [
                    'displayOrder' => Criteria::ASC,
                ]
            );
        $items = $lp->getItems()->matching($criteria);
        $items = $items->filter(
            function (CLpItem $element) use ($parent) {
                if ('root' === $element->getPath()) {
                    return false;
                }

                if (null !== $element->getParent()) {
                    return $element->getParent()->getIid() === $parent;
                }
                return false;

            }
        );
        $list = [];
        foreach ($items as $item) {
            $itemId = $item->getIid();
            $sublist = self::get_flat_ordered_items_list($lp, $itemId);
            $list[] = $itemId;
            foreach ($sublist as $subItem) {
                $list[] = $subItem;
            }
        }

        return $list;
    }

    public static function getChapterTypes(): array
    {
        return [
            'dir',
        ];
    }

    /**
     * Uses the table generated by get_toc() and returns an HTML-formatted string ready to display.
     *
     * @return array HTML TOC ready to display
     */
    public function getListArrayToc()
    {
        $lpItemRepo = Container::getLpItemRepository();
        $itemRoot = $lpItemRepo->getRootItem($this->get_id());
        $options = [
            'decorate' => false,
        ];

        return $lpItemRepo->childrenHierarchy($itemRoot, false, $options);
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
        if ($isAllow && false == $hideIcons) {
            if ($this->get_lp_session_id() == api_get_session_id()) {
                $html .= '<div id="actions_lp" class="actions_lp"><hr>';
                $html .= '<div class="flex flex-row justify-center mb-2">';
                $html .= "<a
                    class='btn btn-sm btn-default mx-1'
                    href='lp_controller.php?".api_get_cidreq()."&action=add_item&type=step&lp_id=".$this->lp_id."&isStudentView=false'
                    target='_parent'>".
                    Display::getMdiIcon('pencil').get_lang('Edit')."</a>";
                $html .= '<a
                    class="btn btn-sm btn-default mx-1"
                    href="lp_controller.php?'.api_get_cidreq()."&action=edit&lp_id=".$this->lp_id.'&isStudentView=false">'.
                    Display::getMdiIcon('hammer-wrench').get_lang('Settings').'</a>';
                $html .= '</div>';
                $html .= '</div>';
            }
        }

        return $html;
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
        return strip_tags($this->get_name());
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
            if (empty($lp_item_params) && false !== strpos($lp_item_path, '?')) {
                [$lp_item_path, $lp_item_params] = explode('?', $lp_item_path);
            }
            //$sys_course_path = api_get_path(SYS_COURSE_PATH).api_get_course_path();
            if ('http' === $type) {
                //web path
                //$course_path = api_get_path(WEB_COURSE_PATH).api_get_course_path();
            } else {
                //$course_path = $sys_course_path; //system path
            }

            // Fixed issue BT#1272 - If the item type is a Chamilo Item (quiz, link, etc),
            // then change the lp type to thread it as a normal Chamilo LP not a SCO.
            if (in_array(
                $lp_item_type,
                ['quiz', 'document', 'final_item', 'link', 'forum', 'thread', 'student_publication']
            )
            ) {
                $lp_type = CLp::LP_TYPE;
            }

            // Now go through the specific cases to get the end of the path
            // @todo Use constants instead of int values.
            switch ($lp_type) {
                case CLp::LP_TYPE:
                    $file = self::rl_get_resource_link_for_learnpath(
                        $course_id,
                        $this->get_id(),
                        $item_id,
                        $this->get_view_id()
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
                            $file = 'lp_content.php?type=dir';
                            break;
                        case 'link':
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
                                if ('https' === $protocol) {
                                    $linkProtocol = substr($file, 0, 5);
                                    if ('http:' === $linkProtocol) {
                                        //this is the special intervention case
                                        $file = api_get_path(WEB_CODE_PATH).
                                            'lp/embed.php?type=nonhttps&source='.urlencode($file);
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
                                $list = $this->get_toc();
                            } else {
                                $list = $provided_toc;
                            }

                            $type_quiz = false;
                            foreach ($list as $toc) {
                                if ($toc['id'] == $lp_item_id && 'quiz' === $toc['type']) {
                                    $type_quiz = true;
                                }
                            }

                            if ($type_quiz) {
                                $lp_item_id = (int) $lp_item_id;
                                $lp_view_id = (int) $lp_view_id;
                                $sql = "SELECT count(*) FROM $lp_item_view_table
                                        WHERE
                                            lp_item_id='".$lp_item_id."' AND
                                            lp_view_id ='".$lp_view_id."' AND
                                            status='completed'";
                                $result = Database::query($sql);
                                $row_count = Database:: fetch_row($result);
                                $count_item_view = (int) $row_count[0];
                                $not_multiple_attempt = 0;
                                if (1 === $prevent_reinit && $count_item_view > 0) {
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
                case CLp::SCORM_TYPE:
                    if ('dir' !== $lp_item_type) {
                        // Quite complex here:
                        // We want to make sure 'http://' (and similar) links can
                        // be loaded as is (withouth the Chamilo path in front) but
                        // some contents use this form: resource.htm?resource=http://blablabla
                        // which means we have to find a protocol at the path's start, otherwise
                        // it should not be considered as an external URL.
                        // if ($this->prerequisites_match($item_id)) {
                        if (0 != preg_match('#^[a-zA-Z]{2,5}://#', $lp_item_path)) {
                            if ($this->debug > 2) {
                                error_log('In learnpath::get_link() '.__LINE__.' - Found match for protocol in '.$lp_item_path, 0);
                            }
                            // Distant url, return as is.
                            $file = $lp_item_path;
                        } else {
                            if ($this->debug > 2) {
                                error_log('In learnpath::get_link() '.__LINE__.' - No starting protocol in '.$lp_item_path);
                            }
                            // Prevent getting untranslatable urls.
                            $lp_item_path = preg_replace('/%2F/', '/', $lp_item_path);
                            $lp_item_path = preg_replace('/%3A/', ':', $lp_item_path);

                            /*$asset = $this->getEntity()->getAsset();
                            $folder = Container::getAssetRepository()->getFolder($asset);
                            $hasFile = Container::getAssetRepository()->getFileSystem()->has($folder.$lp_item_path);
                            $file = null;
                            if ($hasFile) {
                                $file = Container::getAssetRepository()->getAssetUrl($asset).'/'.$lp_item_path;
                            }*/
                            $file = $this->scormUrl.$lp_item_path;

                            // Prepare the path.
                            /*$file = $course_path.'/scorm/'.$lp_path.'/'.$lp_item_path;
                            // TODO: Fix this for urls with protocol header.
                            $file = str_replace('//', '/', $file);
                            $file = str_replace(':/', '://', $file);
                            if ('/' === substr($lp_path, -1)) {
                                $lp_path = substr($lp_path, 0, -1);
                            }*/
                            /*if (!$hasFile) {
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
                            }*/
                        }

                        // We want to use parameters if they were defined in the imsmanifest
                        if (false === strpos($file, 'blank.php')) {
                            $lp_item_params = ltrim($lp_item_params, '?');
                            $file .= (false === strstr($file, '?') ? '?' : '').$lp_item_params;
                        }
                    } else {
                        $file = 'lp_content.php?type=dir';
                    }
                    break;
                case CLp::AICC_TYPE:
                    // Formatting AICC HACP append URL.
                    $aicc_append = '?aicc_sid='.
                        urlencode(session_id()).'&aicc_url='.urlencode(api_get_path(WEB_CODE_PATH).'lp/aicc_hacp.php').'&';
                    if (!empty($lp_item_params)) {
                        $aicc_append .= $lp_item_params.'&';
                    }
                    if ('dir' !== $lp_item_type) {
                        // Quite complex here:
                        // We want to make sure 'http://' (and similar) links can
                        // be loaded as is (withouth the Chamilo path in front) but
                        // some contents use this form: resource.htm?resource=http://blablabla
                        // which means we have to find a protocol at the path's start, otherwise
                        // it should not be considered as an external URL.
                        if (0 != preg_match('#^[a-zA-Z]{2,5}://#', $lp_item_path)) {
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
                            if (false !== stripos($file, '<servername>')) {
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
                        $file = 'lp_content.php?type=dir';
                    }
                    break;
                case 4:
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
        $attempt_num = (int) $attempt_num;
        // Use $attempt_num to enable multi-views management (disabled so far).
        if (!empty($attempt_num)) {
            $search = 'AND view_count = '.$attempt_num;
        }

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
        $sessionCondition = api_get_session_condition($sessionId);

        // When missing $attempt_num, search for a unique lp_view record for this lp and user.
        $table = Database::get_course_table(TABLE_LP_VIEW);
        $sql = "SELECT iid FROM $table
        		WHERE
        		    c_id = $course_id AND
        		    lp_id = ".$this->get_id()." AND
        		    user_id = ".$userId."
        		    $sessionCondition
        		    $search
                ORDER BY view_count DESC";
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res);
            $this->lp_view_id = $row['iid'];
        } elseif (!api_is_invitee()) {
            $params = [
                'c_id' => $course_id,
                'lp_id' => $this->get_id(),
                'user_id' => $this->get_user_id(),
                'view_count' => 1,
                'last_item' => 0,
            ];
            if (!empty($sessionId)) {
                $params['session_id']  = $sessionId;
            }
            $this->lp_view_id = Database::insert($table, $params);
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

        if ($currentItem) {
            if (2 == $this->type) {
                // Getting prereq from scorm
                $prereq_string = $this->get_scorm_prereq_string($itemId);
            } else {
                $prereq_string = $currentItem->get_prereq_string();
            }

            if (empty($prereq_string)) {
                if ($debug > 0) {
                    error_log('Found prereq_string is empty return true');
                }

                return true;
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

            if (false === $result) {
                $this->set_error_msg($currentItem->prereq_alert);
            }
        } else {
            $result = true;
            if ($debug > 1) {
                error_log('$this->items['.$itemId.'] was not an object', 0);
            }
        }

        if ($debug > 1) {
            error_log('End of prerequisites_match(). Error message is now '.$this->error, 0);
        }

        return $result;
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
     * @param int $id         Learnpath ID
     * @param int $visibility New visibility (1 = visible/published, 0= invisible/draft)
     *
     * @return bool
     */
    public static function toggleVisibility($id, $visibility = 1)
    {
        $repo = Container::getLpRepository();
        $lp = $repo->find($id);

        if (!$lp) {
            return false;
        }

        $visibility = (int) $visibility;

        if (1 === $visibility) {
            $repo->setVisibilityPublished($lp);
        } else {
            $repo->setVisibilityDraft($lp);
        }

        return true;
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
        $repo = Container::getLpCategoryRepository();
        $resource = $repo->find($id);

        if (!$resource) {
            return false;
        }

        $visibility = (int) $visibility;

        if (1 === $visibility) {
            $repo->setVisibilityPublished($resource);
        } else {
            $repo->setVisibilityDraft($resource);
            self::toggleCategoryPublish($id, 0);
        }

        return false;
    }

    /**
     * Publishes a learnpath. This basically means show or hide the learnpath
     * on the course homepage.
     *
     * @param int    $id            Learnpath id
     * @param string $setVisibility New visibility (v/i - visible/invisible)
     *
     * @return bool
     */
    public static function togglePublish($id, $setVisibility = 'v')
    {
        $addShortcut = false;
        if ('v' === $setVisibility) {
            $addShortcut = true;
        }
        $repo = Container::getLpRepository();
        /** @var CLp|null $lp */
        $lp = $repo->find($id);
        if (null === $lp) {
            return false;
        }
        $repoShortcut = Container::getShortcutRepository();
        $courseEntity = api_get_course_entity();

        if ($addShortcut) {
            $repoShortcut->addShortCut($lp, $courseEntity, $courseEntity, api_get_session_entity());
        } else {
            $repoShortcut->removeShortCut($lp);
        }

        return true;
    }

    /**
     * Show or hide the learnpath category on the course homepage.
     *
     * @param int $id
     * @param int $setVisibility
     *
     * @return bool
     */
    public static function toggleCategoryPublish($id, $setVisibility = 1)
    {
        $setVisibility = (int) $setVisibility;
        $addShortcut = false;
        if (1 === $setVisibility) {
            $addShortcut = true;
        }

        $repo = Container::getLpCategoryRepository();
        /** @var CLpCategory|null $lp */
        $category = $repo->find($id);

        if (null === $category) {
            return false;
        }

        $repoShortcut = Container::getShortcutRepository();
        if ($addShortcut) {
            $courseEntity = api_get_course_entity(api_get_course_int_id());
            $repoShortcut->addShortCut($category, $courseEntity, $courseEntity, api_get_session_entity());
        } else {
            $repoShortcut->removeShortCut($category);
        }

        return true;
    }

    /**
     * Check if the learnpath category is visible for a user.
     *
     * @return bool
     */
    public static function categoryIsVisibleForStudent(
        CLpCategory $category,
        User $user,
        Course $course,
        SessionEntity $session = null
    ) {
        $isAllowedToEdit = api_is_allowed_to_edit(null, true);

        if ($isAllowedToEdit) {
            return true;
        }

        $categoryVisibility = $category->isVisible($course, $session);

        if (!$categoryVisibility) {
            return false;
        }

        $subscriptionSettings = self::getSubscriptionSettings();

        if (false === $subscriptionSettings['allow_add_users_to_lp_category']) {
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

        //$groups = GroupManager::getAllGroupPerUserSubscription($user->getId());

        return $noGroupSubscribed && $noUserSubscribed;
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
        return false;
        $link = self::getCategoryLinkForTool($category->getId());
        $em = Database::getManager();

        $tools = $em
            ->createQuery("
                SELECT t FROM ChamiloCourseBundle:CTool t
                WHERE t.course = :course AND
                    t.name = :name AND
                    t.image LIKE 'lp_category.%' AND
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
            if ($debug) {
                error_log('Object exists');
            }

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
     * Saves the last item seen's ID only in case.
     */
    public function save_last()
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
                $progress = (int) $progress;
                $sql = "UPDATE $table SET
                            progress = $progress
                        WHERE
                            c_id = $course_id AND
                            lp_id = ".$this->get_id()." AND
                            user_id = ".$userId." ".$session_condition;
                // Ignore errors as some tables might not have the progress field just yet.
                Database::query($sql);
                $this->progress_db = $progress;
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
        if ('true' !== api_get_setting('search_enabled')) {
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
        if ((0 == count(array_diff($terms, $stored_terms))) && (0 == count(array_diff($stored_terms, $terms)))) {
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
        if (0 != $this->current && isset($this->items[$this->current]) &&
            is_object($this->items[$this->current])
        ) {
            $type = $this->get_type();
            $item_type = $this->items[$this->current]->get_type();
            if ($debug) {
                error_log('item type: '.$item_type);
                error_log('lp type: '.$type);
            }
            if ((2 == $type && 'sco' !== $item_type) ||
                (3 == $type && 'au' !== $item_type) ||
                (1 == $type && TOOL_QUIZ != $item_type && TOOL_HOTPOTATOES != $item_type)
            ) {
                $this->items[$this->current]->open($allow_new_attempt);
                $this->autocomplete_parents($this->current);
                $prereq_check = $this->prerequisites_match($this->current);
                if ($debug) {
                    error_log('start_current_item will save item with prereq: '.$prereq_check);
                }
                $this->items[$this->current]->save(false, $prereq_check);
            }
            // If sco, then it is supposed to have been updated by some other call.
            if ('sco' === $item_type) {
                $this->items[$this->current]->restart();
            }
        }
        if ($debug) {
            error_log('lp_view_session_id');
            error_log($this->lp_view_session_id);
            error_log('api session id');
            error_log(api_get_session_id());
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
            error_log('In learnpath::stop_previous_item()', 0);
        }

        if (0 != $this->last && $this->last != $this->current &&
            isset($this->items[$this->last]) && is_object($this->items[$this->last])
        ) {
            if ($debug) {
                error_log('In learnpath::stop_previous_item() - '.$this->last.' is object');
            }
            switch ($this->get_type()) {
                case '3':
                    if ('au' != $this->items[$this->last]->get_type()) {
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
                    if ('sco' != $this->items[$this->last]->get_type()) {
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
            if (1 == $force) {
                $force = 0;
                $force_return = false;
            } elseif (0 == $force) {
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
        return;
        $course_id = api_get_course_int_id();
        $table = Database::get_course_table(TABLE_LP_MAIN);
        $sql = "SELECT * FROM $table
                WHERE c_id = $course_id
                ORDER BY display_order";
        $res = Database::query($sql);
        if (false === $res) {
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
        $force = $this->prevent_reinit;
        if (1 == $force) {
            $force = 0;
        } elseif (0 == $force) {
            $force = 1;
        }

        $table = Database::get_course_table(TABLE_LP_MAIN);
        $sql = "UPDATE $table SET prevent_reinit = $force
                WHERE iid = ".$this->get_id();
        Database::query($sql);
        $this->prevent_reinit = $force;

        return $force;
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
        if (1 == $this->seriousgame_mode && 1 == $this->prevent_reinit) {
            return 'seriousgame';
        }
        if (0 == $this->seriousgame_mode && 1 == $this->prevent_reinit) {
            return 'single';
        }
        if (0 == $this->seriousgame_mode && 0 == $this->prevent_reinit) {
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
        $table = Database::get_course_table(TABLE_LP_MAIN);
        $force = $this->seriousgame_mode;
        if (1 == $force) {
            $force = 0;
        } elseif (0 == $force) {
            $force = 1;
        }
        $sql = "UPDATE $table SET seriousgame_mode = $force
                WHERE iid = ".$this->get_id();
        Database::query($sql);
        $this->seriousgame_mode = $force;

        return $force;
    }

    /**
     * Updates the "scorm_debug" value that shows or hide the debug window.
     *
     * @return bool True if scorm_debug has been set to 'on', false otherwise (or 1 or 0 in this case)
     */
    public function update_scorm_debug()
    {
        $table = Database::get_course_table(TABLE_LP_MAIN);
        $force = $this->scorm_debug;
        if (1 == $force) {
            $force = 0;
        } elseif (0 == $force) {
            $force = 1;
        }
        $sql = "UPDATE $table SET debug = $force
                WHERE iid = ".$this->get_id();
        Database::query($sql);
        $this->scorm_debug = $force;

        return $force;
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
        $update_audio = $_GET['updateaudio'] ?? null;

        // we need to start a form when we want to update all the mp3 files
        if ('true' == $update_audio) {
            $return .= '<form action="'.api_get_self().'?'.api_get_cidreq().'&updateaudio='.Security::remove_XSS(
                    $_GET['updateaudio']
                ).'&action='.Security::remove_XSS(
                    $_GET['action']
                ).'&lp_id='.$_SESSION['oLP']->lp_id.'" method="post" enctype="multipart/form-data" name="updatemp3" id="updatemp3">';
        }
        $return .= '<div id="message"></div>';
        if (0 == count($this->items)) {
            $return .= Display::return_message(
                get_lang(
                    'You should add some items to your learning path, otherwise you won\'t be able to attach audio files to them'
                ),
                'normal'
            );
        } else {
            $return_audio = '<table class="table table-hover table-striped data_table">';
            $return_audio .= '<tr>';
            $return_audio .= '<th width="40%">'.get_lang('Title').'</th>';
            $return_audio .= '<th>'.get_lang('Audio').'</th>';
            $return_audio .= '</tr>';

            if ('true' != $update_audio) {
                /*$return .= '<div class="col-md-12">';
                $return .= self::return_new_tree($update_audio);
                $return .= '</div>';*/
                $return .= Display::div(
                    Display::url(get_lang('Save'), '#', ['id' => 'listSubmit', 'class' => 'btn btn-primary']),
                    ['style' => 'float:left; margin-top:15px;width:100%']
                );
            } else {
                //$return_audio .= self::return_new_tree($update_audio);
                $return .= $return_audio.'</table>';
            }

            // We need to close the form when we are updating the mp3 files.
            if ('true' == $update_audio) {
                $return .= '<div class="footer-audio">';
                $return .= Display::button(
                    'save_audio',
                    '<em class="fa fa-file-audio-o"></em> '.get_lang('Save audio and organization'),
                    ['class' => 'btn btn-primary', 'type' => 'submit']
                );
                $return .= '</div>';
            }
        }

        // We need to close the form when we are updating the mp3 files.
        if ('true' === $update_audio && isset($this->arrMenu) && 0 != count($this->arrMenu)) {
            $return .= '</form>';
        }

        return $return;
    }

    public function showBuildSideBar($updateAudio = false, $dropElementHere = false, $type = null)
    {
        $sureToDelete = trim(get_lang('Are you sure to delete?'));
        $ajax_url = api_get_path(WEB_AJAX_PATH).'lp.ajax.php?lp_id='.$this->get_id().'&'.api_get_cidreq();

        $content = '
        <script>
            /*
            Script to manipulate Learning Path items with Drag and drop
             */
            $(function() {
                function refreshTree() {
                    var params = "&a=get_lp_item_tree";
                    $.get(
                        "'.$ajax_url.'",
                        params,
                        function(result) {
                            serialized = [];
                            $("#lp_item_list").html(result);
                            nestedSortable();
                        }
                    );
                }

                const nestedQuery = ".nested-sortable";
                const identifier = "id";
                const root = document.getElementById("lp_item_list");

                var serialized = [];
                function serialize(sortable) {
                  var children = [].slice.call(sortable.children);
                  for (var i in children) {
                    var nested = children[i].querySelector(nestedQuery);
                    var parentId = $(children[i]).parent().parent().attr("id");
                    var id = children[i].dataset[identifier];
                    if (typeof id === "undefined") {
                        return;
                    }
                    serialized.push({
                      id: children[i].dataset[identifier],
                      parent_id: parentId
                    });

                    if (nested) {
                        serialize(nested);
                    }
                  }

                  return serialized;
                }

                function nestedSortable() {
                    let left = document.getElementsByClassName("nested-sortable");
                    Array.prototype.forEach.call(left, function(resource) {
                        Sortable.create(resource, {
                            group: "nested",
                            put: ["nested-sortable", ".lp_resource", ".nested-source"],
                            animation: 150,
                            //fallbackOnBody: true,
                            swapThreshold: 0.65,
                            dataIdAttr: "data-id",
                            store: {
                                set: function (sortable) {
                                    var order = sortable.toArray();
                                    console.log(order);
                                }
                            },
                            onEnd: function(evt) {
                                console.log("onEnd");
                                let list = serialize(root);
                                let order = "&a=update_lp_item_order&new_order=" + JSON.stringify(list);
                                $.get(
                                    "'.$ajax_url.'",
                                    order,
                                    function(reponse) {
                                        $("#message").html(reponse);
                                        refreshTree();
                                    }
                                );
                            },
                        });
                    });
                }

                nestedSortable();

                let resources = document.getElementsByClassName("lp_resource");
                Array.prototype.forEach.call(resources, function(resource) {
                    Sortable.create(resource, {
                        group: "nested",
                        put: ["nested-sortable"],
                        filter: ".disable_drag",
                        animation: 150,
                        fallbackOnBody: true,
                        swapThreshold: 0.65,
                        dataIdAttr: "data-id",
                        onRemove: function(evt) {
                            console.log("onRemove");
                            var itemEl = evt.item;
                            var newIndex = evt.newIndex;
                            var id = $(itemEl).attr("id");
                            var parent_id = $(itemEl).parent().parent().attr("id");
                            var type =  $(itemEl).find(".link_with_id").attr("data_type");
                            var title = $(itemEl).find(".link_with_id").text();

                            let previousId = 0;
                            if (0 !== newIndex) {
                                previousId = $(itemEl).prev().attr("id");
                            }
                            var params = {
                                "a": "add_lp_item",
                                "id": id,
                                "parent_id": parent_id,
                                "previous_id": previousId,
                                "type": type,
                                "title" : title
                            };
                            console.log(params);
                            $.ajax({
                                type: "GET",
                                url: "'.$ajax_url.'",
                                data: params,
                                success: function(itemId) {
                                    $(itemEl).attr("id", itemId);
                                    $(itemEl).attr("data-id", itemId);
                                    let list = serialize(root);
                                    let listInString = JSON.stringify(list);
                                    if (typeof listInString === "undefined") {
                                        listInString = "";
                                    }
                                    let order = "&a=update_lp_item_order&new_order=" + listInString;
                                    $.get(
                                        "'.$ajax_url.'",
                                        order,
                                        function(reponse) {
                                            $("#message").html(reponse);
                                            refreshTree();
                                        }
                                    );
                                }
                            });
                        },
                    });
                });
            });
        </script>';

        $content .= "
        <script>
            function confirmation(name) {
                if (confirm('$sureToDelete ' + name)) {
                    return true;
                } else {
                    return false;
                }
            }
            function refreshTree() {
                var params = '&a=get_lp_item_tree';
                $.get(
                    '".$ajax_url."',
                    params,
                    function(result) {
                        $('#lp_item_list').html(result);
                    }
                );
            }

            $(function () {
                //$('.scrollbar-inner').scrollbar();
                /*$('#subtab').on('click', 'a:first', function() {
                    window.location.reload();
                });
                $('#subtab ').on('click', 'a:first', function () {
                    window.location.reload();
                });*/

                expandColumnToggle('#hide_bar_template', {
                    selector: '#lp_sidebar'
                }, {
                    selector: '#doc_form'
                });

                $('.lp-btn-associate-forum').on('click', function (e) {
                    var associate = confirm('".get_lang('ConfirmAssociateForumToLPItem')."');
                    if (!associate) {
                        e.preventDefault();
                    }
                });

                $('.lp-btn-dissociate-forum').on('click', function (e) {
                    var dissociate = confirm('".get_lang('ConfirmDissociateForumToLPItem')."');
                    if (!dissociate) {
                        e.preventDefault();
                    }
                });

                // hide the current template list for new documment until it tab clicked
                $('#frmModel').hide();
            });

            // document template for new document tab handler
            /*$(document).on('shown.bs.tab', 'a[data-toggle=\"tab\"]', function (e) {
                var id = e.target.id;
                if (id == 'subtab2') {
                    $('#frmModel').show();
                } else {
                    $('#frmModel').hide();
                }
            });*/

          function deleteItem(event) {
            var id = $(event).attr('data-id');
            var title = $(event).attr('data-title');
            var params = '&a=delete_item&id=' + id;
            if (confirmation(title)) {
                $.get(
                    '".$ajax_url."',
                    params,
                    function(result) {
                        refreshTree();
                    }
                );
            }
        }
        </script>";

        $content .= $this->return_new_tree($updateAudio, $dropElementHere);
        $documentId = isset($_GET['path_item']) ? (int) $_GET['path_item'] : 0;

        $repo = Container::getDocumentRepository();
        $document = $repo->find($documentId);
        if ($document) {
            // Show the template list
            $content .= '<div id="frmModel" class="scrollbar-inner lp-add-item"></div>';
        }

        // Show the template list.
        if (('document' === $type || 'step' === $type) && !isset($_GET['file'])) {
            // Show the template list.
            $content .= '<div id="frmModel" class="scrollbar-inner lp-add-item"></div>';
        }

        return $content;
    }

    /**
     * @param bool  $updateAudio
     * @param bool   $dropElement
     *
     * @return string
     */
    public function return_new_tree($updateAudio = false, $dropElement = false)
    {
        $list = $this->getBuildTree(false, $dropElement);
        $return = Display::panelCollapse(
            $this->name,
            $list,
            'scorm-list',
            null,
            'scorm-list-accordion',
            'scorm-list-collapse'
        );

        if ($updateAudio) {
            //$return = $result['return_audio'];
        }

        return $return;
    }

    public function getBuildTree($noWrapper = false, $dropElement = false): string
    {
        $mainUrl = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq();
        $upIcon = Display::getMdiIcon('arrow-up-bold', 'ch-tool-icon', '', 16, get_lang('Up'));
        $disableUpIcon = Display::getMdiIcon('arrow-up-bold', 'ch-tool-icon-disabled', '', 16, get_lang('Up'));
        $downIcon = Display::getMdiIcon('arrow-down-bold', 'ch-tool-icon', '', 16, get_lang('Down'));
        $previewImage = Display::getMdiIcon('magnify-plus-outline', 'ch-tool-icon', '', 16, get_lang('Preview'));

        $lpItemRepo = Container::getLpItemRepository();
        $itemRoot = $lpItemRepo->getRootItem($this->get_id());

        $options = [
            'decorate' => true,
            'rootOpen' => function($tree) use ($noWrapper) {
                if ($tree[0]['lvl'] === 1) {
                    if ($noWrapper) {
                        return '';
                    }
                    return '<ul id="lp_item_list" class="list-group nested-sortable">';
                }

                return '<ul class="list-group nested-sortable">';
            },
            'rootClose' => function($tree) use ($noWrapper, $dropElement)  {
                if ($tree[0]['lvl'] === 1) {
                    if ($dropElement) {
                        //return Display::return_message(get_lang('Drag and drop an element here'));
                        //return $this->getDropElementHtml();
                    }
                    if ($noWrapper) {
                        return '';
                    }
                }

                return '</ul>';
            },
            'childOpen' => function($child) {
                $id = $child['iid'];
                return '<li
                    id="'.$id.'"
                    data-id="'.$id.'"
                    class=" flex flex-col list-group-item nested-'.$child['lvl'].'">';
            },
            'childClose' => '',
            'nodeDecorator' => function ($node) use ($mainUrl, $previewImage, $upIcon, $downIcon) {
                $fullTitle = $node['title'];
                //$title = cut($fullTitle, self::MAX_LP_ITEM_TITLE_LENGTH);
                $title = $fullTitle;
                $itemId = $node['iid'];
                $type = $node['itemType'];
                $lpId = $this->get_id();

                $moveIcon = '';
                if (TOOL_LP_FINAL_ITEM !== $type) {
                    $moveIcon .= '<a class="moved" href="#">';
                    $moveIcon .= Display::getMdiIcon('cursor-move', 'ch-tool-icon', '', 16, get_lang('Move'));
                    $moveIcon .= '</a>';
                }

                $iconName = str_replace(' ', '', $type);
                $icon = '';
                switch ($iconName) {
                    case 'chapter':
                    case 'folder':
                    case 'dir':
                        $icon = Display::getMdiIcon('bookmark-multiple', 'ch-tool-icon', '', 16);
                        break;
                    default:
                        $icon = Display::return_icon(
                            'lp_'.$iconName.'.png',
                            '',
                            [],
                            ICON_SIZE_TINY
                        );
                        break;
                }

                $urlPreviewLink = $mainUrl.'&action=view_item&mode=preview_document&id='.$itemId.'&lp_id='.$lpId;
                $previewIcon = Display::url(
                    $previewImage,
                    $urlPreviewLink,
                    [
                        'target' => '_blank',
                        'class' => 'btn btn-default',
                        'data-title' => $title,
                        'title' => $title,
                    ]
                );
                $url = $mainUrl.'&view=build&id='.$itemId.'&lp_id='.$lpId;

                $preRequisitesIcon = Display::url(
                    Display::getMdiIcon('graph', 'ch-tool-icon', '', 16, get_lang('Prerequisites')),
                    $url.'&action=edit_item_prereq',
                    ['class' => '']
                );

                $editIcon = '<a
                    href="'.$mainUrl.'&action=edit_item&view=build&id='.$itemId.'&lp_id='.$lpId.'&path_item='.$node['path'].'"
                    class=""
                    >';
                $editIcon .= Display::getMdiIcon('pencil', 'ch-tool-icon', '', 16, get_lang('Edit section description/name'));
                $editIcon .= '</a>';
                $orderIcons = '';
                /*if ('final_item' !== $type) {
                    $orderIcons = Display::url(
                        $upIcon,
                        'javascript:void(0)',
                        ['class' => 'btn btn-default order_items', 'data-dir' => 'up', 'data-id' => $itemId]
                    );
                    $orderIcons .= Display::url(
                        $downIcon,
                        'javascript:void(0)',
                        ['class' => 'btn btn-default order_items', 'data-dir' => 'down', 'data-id' => $itemId]
                    );
                }*/

                $deleteIcon = ' <a
                    data-id = '.$itemId.'
                    data-title = \''.addslashes($title).'\'
                    href="javascript:void(0);"
                    onclick="return deleteItem(this);"
                    class="">';
                $deleteIcon .= Display::getMdiIcon('delete', 'ch-tool-icon', '', 16, get_lang('Delete section'));
                $deleteIcon .= '</a>';
                $extra = '';

                if ('dir' === $type && empty($node['__children'])) {
                    $level = $node['lvl'] + 1;
                    $extra = '<ul class="list-group nested-sortable">
                                <li class="list-group-item list-group-item-empty nested-'.$level.'"></li>
                              </ul>';
                }

                $buttons = Display::tag(
                    'div',
                    "<div class=\"btn-group btn-group-sm\">
                                $editIcon
                                $preRequisitesIcon
                                $orderIcons
                                $deleteIcon
                               </div>",
                    ['class' => 'btn-toolbar button_actions']
                );

                return
                    "<div class='flex flex-row'> $moveIcon  $icon <span class='mx-1'>$title </span></div>
                    $extra
                    $buttons
                    "
                    ;
            },
        ];

        $tree = $lpItemRepo->childrenHierarchy($itemRoot, false, $options);

        if (empty($tree) && $dropElement) {
            return $this->getDropElementHtml($noWrapper);
        }

        return $tree;
    }

    public function getDropElementHtml($noWrapper = false)
    {
        $li = '<li class="list-group-item">'.
            Display::return_message(get_lang('Drag and drop an element here')).
            '</li>';
        if ($noWrapper) {
            return $li;
        }

        return
            '<ul id="lp_item_list" class="list-group nested-sortable">
            '.$li.'
            </ul>';
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
                Display::getMdiIcon('arrow-left-bold-box', 'ch-tool-icon', '', 32, get_lang('Back to learning paths')),
                'lp_controller.php?'.api_get_cidreq()
            );
        } else {
            $back = Display::url(
                Display::getMdiIcon('arrow-left-bold-box', 'ch-tool-icon', '', 32, get_lang('Back')),
                $extraField['backTo']
            );
        }

        /*if ($backToBuild) {
            $back = Display::url(
                Display::return_icon(
                    'back.png',
                    get_lang('GoBack'),
                    '',
                    ICON_SIZE_MEDIUM
                ),
                "lp_controller.php?action=add_item&type=step&lp_id=$lpId&".api_get_cidreq()
            );
        }*/

        $actionsLeft = $back;

        $actionsLeft .= Display::url(
            Display::getMdiIcon('magnify-plus-outline', 'ch-tool-icon', '', 32, get_lang('Preview')),
            'lp_controller.php?'.api_get_cidreq().'&'.http_build_query([
                'action' => 'view',
                'lp_id' => $lpId,
                'isStudentView' => 'true',
            ])
        );

        /*$actionsLeft .= Display::url(
            Display::return_icon(
                'upload_audio.png',
                get_lang('Add audio'),
                '',
                ICON_SIZE_MEDIUM
            ),
            'lp_controller.php?'.api_get_cidreq().'&'.http_build_query([
                'action' => 'admin_view',
                'lp_id' => $lpId,
                'updateaudio' => 'true',
            ])
        );*/

        $subscriptionSettings = self::getSubscriptionSettings();

        $request = api_request_uri();
        if (false === strpos($request, 'edit')) {
            $actionsLeft .= Display::url(
                Display::getMdiIcon('hammer-wrench', 'ch-tool-icon', '', 32, get_lang('Course settings')),
                'lp_controller.php?'.api_get_cidreq().'&'.http_build_query([
                    'action' => 'edit',
                    'lp_id' => $lpId,
                ])
            );
        }

        if ((false === strpos($request, 'build') &&
            false === strpos($request, 'add_item')) ||
            in_array($action, ['add_audio'], true)
        ) {
            $actionsLeft .= Display::url(
                Display::getMdiIcon('pencil', 'ch-tool-icon', '', 32, get_lang('Edit')),
                'lp_controller.php?'.http_build_query([
                    'action' => 'build',
                    'lp_id' => $lpId,
                ]).'&'.api_get_cidreq()
            );
        }

        if (false === strpos(api_get_self(), 'lp_subscribe_users.php')) {
            if (1 == $this->subscribeUsers &&
                $subscriptionSettings['allow_add_users_to_lp']) {
                $actionsLeft .= Display::url(
                    Display::getMdiIcon('account-multiple-plus', 'ch-tool-icon', '', 32, get_lang('Subscribe users to learning path')),
                    api_get_path(WEB_CODE_PATH)."lp/lp_subscribe_users.php?lp_id=$lpId&".api_get_cidreq()
                );
            }
        }

        if ($allowExpand) {
            /*$actionsLeft .= Display::url(
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
            );*/
        }

        if ($showRequirementButtons) {
            $buttons = [
                [
                    'title' => get_lang('Set previous step as prerequisite for each step'),
                    'href' => 'lp_controller.php?'.api_get_cidreq().'&'.http_build_query([
                        'action' => 'set_previous_step_as_prerequisite',
                        'lp_id' => $lpId,
                    ]),
                ],
                [
                    'title' => get_lang('Clear all prerequisites'),
                    'href' => 'lp_controller.php?'.api_get_cidreq().'&'.http_build_query([
                        'action' => 'clear_prerequisites',
                        'lp_id' => $lpId,
                    ]),
                ],
            ];
            $actionsRight = Display::groupButtonWithDropDown(
                get_lang('Prerequisites options'),
                $buttons,
                true
            );
        }

        if (api_is_platform_admin() && isset($extraField['authorlp'])) {
            $actionsLeft .= Display::url(
                Display::getMdiIcon('account-multiple-plus', 'ch-tool-icon', '', 32, get_lang('Author')),
                'lp_controller.php?'.api_get_cidreq().'&'.http_build_query([
                    'action' => 'author_view',
                    'lp_id' => $lpId,
                ])
            );
        }

        $toolbar = Display::toolbarAction('actions-lp-controller', [$actionsLeft, $actionsRight]);

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
     * @return CDocument
     */
    public static function generate_learning_path_folder($course, $creatorId = 0)
    {
        // Creating learning_path folder
        $dir = 'learning_path';
        $creatorId = empty($creatorId) ? api_get_user_id() : $creatorId;

        return create_unexisting_directory(
            $course,
            $creatorId,
            0,
            null,
            0,
            '',
            $dir,
            get_lang('Learning paths'),
            0
        );
    }

    /**
     * @param array  $course
     * @param string $lp_name
     * @param int    $creatorId
     *
     * @return CDocument
     */
    public function generate_lp_folder($course, $lp_name = '', $creatorId = 0)
    {
        $filepath = '';
        $dir = '/learning_path/';

        if (empty($lp_name)) {
            $lp_name = $this->name;
        }
        $creatorId = empty($creatorId) ? api_get_user_id() : $creatorId;
        $parent = self::generate_learning_path_folder($course, $creatorId);

        // Limits title size
        $title = api_substr(api_replace_dangerous_char($lp_name), 0, 80);
        $dir = $dir.$title;

        // Creating LP folder
        $folder = null;
        if ($parent) {
            $folder = create_unexisting_directory(
                $course,
                $creatorId,
                0,
                0,
                0,
                $filepath,
                $dir,
                $lp_name,
                '',
                false,
                false,
                $parent
            );
        }

        return $folder;
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
        $creatorId = empty($creatorId) ? api_get_user_id() : $creatorId;
        $sessionId = api_get_session_id();

        // Generates folder
        $this->generate_lp_folder($courseInfo);
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
        $tmp_filename = $filename;
        /*$i = 0;
        while (file_exists($filepath.$tmp_filename.'.'.$extension)) {
            $tmp_filename = $filename.'_'.++$i;
        }*/
        $filename = $tmp_filename.'.'.$extension;

        if ('html' === $extension) {
            $content = stripslashes($content);
            $content = str_replace(
                api_get_path(WEB_COURSE_PATH),
                api_get_path(REL_PATH).'courses/',
                $content
            );

            // Change the path of mp3 to absolute.
            // The first regexp deals with :// urls.
            /*$content = preg_replace(
                "|(flashvars=\"file=)([^:/]+)/|",
                "$1".api_get_path(
                    REL_COURSE_PATH
                ).$courseInfo['path'].'/document/',
                $content
            );*/
            // The second regexp deals with audio/ urls.
            /*$content = preg_replace(
                "|(flashvars=\"file=)([^/]+)/|",
                "$1".api_get_path(
                    REL_COURSE_PATH
                ).$courseInfo['path'].'/document/$2/',
                $content
            );*/
            // For flv player: To prevent edition problem with firefox,
            // we have to use a strange tip (don't blame me please).
            $content = str_replace(
                '</body>',
                '<style type="text/css">body{}</style></body>',
                $content
            );
        }

        $document = DocumentManager::addDocument(
            $courseInfo,
            null,
            'file',
            '',
            $tmp_filename,
            '',
            0, //readonly
            true,
            null,
            $sessionId,
            $creatorId,
            false,
            $content,
            $parentId
        );

        $document_id = $document->getIid();
        if ($document_id) {
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

                $sql = "UPDATE $tbl_doc SET ".substr($ct, 1)."
                        WHERE iid = $document_id ";
                Database::query($sql);
            }
        }

        return $document_id;
    }

    /**
     * Edit a document based on $_POST and $_GET parameters 'dir' and 'path'.
     */
    public function edit_document()
    {
        $repo = Container::getDocumentRepository();
        if (isset($_REQUEST['document_id']) && !empty($_REQUEST['document_id'])) {
            $id = (int) $_REQUEST['document_id'];
            /** @var CDocument $document */
            $document = $repo->find($id);
            if ($document->getResourceNode()->hasEditableTextContent()) {
                $repo->updateResourceFileContent($document, $_REQUEST['content_lp']);
            }
            $document->setTitle($_REQUEST['title']);
            $repo->update($document);
        }
    }

    /**
     * Displays the selected item, with a panel for manipulating the item.
     *
     * @param CLpItem $lpItem
     * @param string  $msg
     * @param bool    $show_actions
     *
     * @return string
     */
    public function display_item($lpItem, $msg = null, $show_actions = true)
    {
        $course_id = api_get_course_int_id();
        $return = '';

        if (null === $lpItem) {
            return '';
        }
        $item_id = $lpItem->getIid();
        $itemType = $lpItem->getItemType();
        $lpId = $lpItem->getLp()->getIid();
        $path = $lpItem->getPath();

        Session::write('parent_item_id', 'dir' === $itemType ? $item_id : 0);

        // Prevents wrong parent selection for document, see Bug#1251.
        if ('dir' !== $itemType) {
            Session::write('parent_item_id', $lpItem->getParentItemId());
        }

        if ($show_actions) {
            $return .= $this->displayItemMenu($lpItem);
        }
        $return .= '<div style="padding:10px;">';

        if ('' != $msg) {
            $return .= $msg;
        }

        $return .= '<h3>'.$lpItem->getTitle().'</h3>';

        switch ($itemType) {
            case TOOL_THREAD:
                $link = $this->rl_get_resource_link_for_learnpath(
                    $course_id,
                    $lpId,
                    $item_id,
                    0
                );
                $return .= Display::url(
                    get_lang('Go to thread'),
                    $link,
                    ['class' => 'btn btn-primary']
                );
                break;
            case TOOL_FORUM:
                $return .= Display::url(
                    get_lang('Go to the forum'),
                    api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.api_get_cidreq().'&forum='.$path,
                    ['class' => 'btn btn-primary']
                );
                break;
            case TOOL_QUIZ:
                if (!empty($path)) {
                    $exercise = new Exercise();
                    $exercise->read($path);
                    $return .= $exercise->description.'<br />';
                    $return .= Display::url(
                        get_lang('Go to exercise'),
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
                $repo = Container::getDocumentRepository();
                /** @var CDocument $document */
                $document = $repo->find($lpItem->getPath());
                $return .= $this->display_document($document, true, true);
                break;
        }
        $return .= '</div>';

        return $return;
    }

    /**
     * Shows the needed forms for editing a specific item.
     *
     * @param CLpItem $lpItem
     *
     * @throws Exception
     *
     *
     * @return string
     */
    public function display_edit_item($lpItem, $excludeExtraFields = [])
    {
        $return = '';
        if (empty($lpItem)) {
            return '';
        }
        $itemType = $lpItem->getItemType();
        $path = $lpItem->getPath();

        switch ($itemType) {
            case 'dir':
            case 'asset':
            case 'sco':
                if (isset($_GET['view']) && 'build' === $_GET['view']) {
                    $return .= $this->displayItemMenu($lpItem);
                    $return .= $this->display_item_form($lpItem, 'edit');
                } else {
                    $return .= $this->display_item_form($lpItem, 'edit_item');
                }
                break;
            case TOOL_LP_FINAL_ITEM:
            case TOOL_DOCUMENT:
            case TOOL_READOUT_TEXT:
                $return .= $this->displayItemMenu($lpItem);
                $return .= $this->displayDocumentForm('edit', $lpItem);
                break;
            case TOOL_LINK:
                $link = null;
                if (!empty($path)) {
                    $repo = Container::getLinkRepository();
                    $link = $repo->find($path);
                }
                $return .= $this->displayItemMenu($lpItem);
                $return .= $this->display_link_form('edit', $lpItem, $link);

                break;
            case TOOL_QUIZ:
                if (!empty($path)) {
                    $repo = Container::getQuizRepository();
                    $resource = $repo->find($path);
                }
                $return .= $this->displayItemMenu($lpItem);
                $return .= $this->display_quiz_form('edit', $lpItem, $resource);
                break;
            case TOOL_STUDENTPUBLICATION:
                if (!empty($path)) {
                    $repo = Container::getStudentPublicationRepository();
                    $resource = $repo->find($path);
                }
                $return .= $this->displayItemMenu($lpItem);
                $return .= $this->display_student_publication_form('edit', $lpItem, $resource);
                break;
            case TOOL_FORUM:
                if (!empty($path)) {
                    $repo = Container::getForumRepository();
                    $resource = $repo->find($path);
                }
                $return .= $this->displayItemMenu($lpItem);
                $return .= $this->display_forum_form('edit', $lpItem, $resource);
                break;
            case TOOL_THREAD:
                if (!empty($path)) {
                    $repo = Container::getForumPostRepository();
                    $resource = $repo->find($path);
                }
                $return .= $this->displayItemMenu($lpItem);
                $return .= $this->display_thread_form('edit', $lpItem, $resource);
                break;
        }

        return $return;
    }

    /**
     * Function that displays a list with al the resources that
     * could be added to the learning path.
     *
     * @throws Exception
     */
    public function displayResources(): string
    {
        // Get all the docs.
        $documents = $this->get_documents(true);

        // Get all the exercises.
        $exercises = $this->get_exercises();

        // Get all the links.
        $links = $this->get_links();

        // Get all the student publications.
        $works = $this->get_student_publications();

        // Get all the forums.
        $forums = $this->get_forums();

        // Get the final item form (see BT#11048) .
        $finish = $this->getFinalItemForm();
        $size = ICON_SIZE_MEDIUM; //ICON_SIZE_BIG
        $headers = [
            Display::getMdiIcon('bookshelf', 'ch-tool-icon-gradient', '', 64, get_lang('Documents')),
            Display::getMdiIcon('order-bool-ascending-variant', 'ch-tool-icon-gradient', '', 64, get_lang('Tests')),
            Display::getMdiIcon('file-link', 'ch-tool-icon-gradient', '', 64, get_lang('Links')),
            Display::getMdiIcon('inbox-full', 'ch-tool-icon-gradient', '', 64, get_lang('Assignments')),
            Display::getMdiIcon('comment-quote', 'ch-tool-icon-gradient', '', 64, get_lang('Forums')),
            Display::getMdiIcon('bookmark-multiple', 'ch-tool-icon-gradient', '', 64, get_lang('Add section')),
            Display::getMdiIcon('certificate', 'ch-tool-icon-gradient', '', 64, get_lang('Certificate')),
        ];
        $content = '';
        /*$content = Display::return_message(
            get_lang('Click on the [Learner view] button to see your learning path'),
            'normal'
        );*/
        $section = $this->displayNewSectionForm();
        $selected = isset($_REQUEST['lp_build_selected']) ? (int) $_REQUEST['lp_build_selected'] : 0;

        return Display::tabs(
            $headers,
            [
                $documents,
                $exercises,
                $links,
                $works,
                $forums,
                $section,
                $finish,
            ],
            'resource_tab',
            [],
            [],
            $selected
        );
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
     * Displays a document by id.
     *
     * @param CDocument $document
     * @param bool      $show_title
     * @param bool      $iframe
     * @param bool      $edit_link
     *
     * @return string
     */
    public function display_document($document, $show_title = false, $iframe = true, $edit_link = false)
    {
        $return = '';
        if (!$document) {
            return '';
        }

        $repo = Container::getDocumentRepository();

        // TODO: Add a path filter.
        if ($iframe) {
            $url = $repo->getResourceFileUrl($document);

            $return .= '<iframe
                id="learnpath_preview_frame"
                frameborder="0"
                height="400"
                width="100%"
                scrolling="auto"
                src="'.$url.'"></iframe>';
        } else {
            $return = $repo->getResourceFileContent($document);
        }

        return $return;
    }

    /**
     * Return HTML form to add/edit a link item.
     *
     * @param string  $action (add/edit)
     * @param CLpItem $lpItem
     * @param CLink   $link
     *
     * @throws Exception
     *
     *
     * @return string HTML form
     */
    public function display_link_form($action, $lpItem, $link)
    {
        $item_url = '';
        if ($link) {
            $item_url = stripslashes($link->getUrl());
        }
        $form = new FormValidator(
            'edit_link',
            'POST',
            $this->getCurrentBuildingModeURL()
        );

        LearnPathItemForm::setForm($form, $action, $this, $lpItem);

        $urlAttributes = ['class' => 'learnpath_item_form'];
        $urlAttributes['disabled'] = 'disabled';
        $form->addElement('url', 'url', get_lang('URL'), $urlAttributes);
        $form->setDefault('url', $item_url);

        $form->addButtonSave(get_lang('Save'), 'submit_button');

        return '<div class="sectioncomment">'.$form->returnForm().'</div>';
    }

    /**
     * Return HTML form to add/edit a quiz.
     *
     * @param string  $action   Action (add/edit)
     * @param CLpItem $lpItem   Item ID if already exists
     * @param CQuiz   $exercise Extra information (quiz ID if integer)
     *
     * @throws Exception
     *
     * @return string HTML form
     */
    public function display_quiz_form($action, $lpItem, $exercise)
    {
        $form = new FormValidator(
            'quiz_form',
            'POST',
            $this->getCurrentBuildingModeURL()
        );

        LearnPathItemForm::setForm($form, $action, $this, $lpItem);
        $form->addButtonSave(get_lang('Save'), 'submit_button');

        return '<div class="sectioncomment">'.$form->returnForm().'</div>';
    }

    /**
     * Return the form to display the forum edit/add option.
     *
     * @param CLpItem $lpItem
     *
     * @throws Exception
     *
     * @return string HTML form
     */
    public function display_forum_form($action, $lpItem, $resource)
    {
        $form = new FormValidator(
            'forum_form',
            'POST',
            $this->getCurrentBuildingModeURL()
        );
        LearnPathItemForm::setForm($form, $action, $this, $lpItem);

        if ('add' === $action) {
            $form->addButtonSave(get_lang('Add forum to course'), 'submit_button');
        } else {
            $form->addButtonSave(get_lang('Edit the current forum'), 'submit_button');
        }

        return '<div class="sectioncomment">'.$form->returnForm().'</div>';
    }

    /**
     * Return HTML form to add/edit forum threads.
     *
     * @param string  $action
     * @param CLpItem $lpItem
     * @param string  $resource
     *
     * @throws Exception
     *
     * @return string HTML form
     */
    public function display_thread_form($action, $lpItem, $resource)
    {
        $form = new FormValidator(
            'thread_form',
            'POST',
            $this->getCurrentBuildingModeURL()
        );

        LearnPathItemForm::setForm($form, 'edit', $this, $lpItem);

        $form->addButtonSave(get_lang('Save'), 'submit_button');

        return $form->returnForm();
    }

    /**
     * Return the HTML form to display an item (generally a dir item).
     *
     * @param CLpItem $lpItem
     * @param string  $action
     *
     * @throws Exception
     *
     *
     * @return string HTML form
     */
    public function display_item_form(
        $lpItem,
        $action = 'add_item'
    ) {
        $item_type = $lpItem->getItemType();

        $url = api_get_self().'?'.api_get_cidreq().'&action='.$action.'&type='.$item_type.'&lp_id='.$this->lp_id;

        $form = new FormValidator('form_'.$item_type, 'POST', $url);
        LearnPathItemForm::setForm($form, 'edit', $this, $lpItem);

        $form->addButtonSave(get_lang('Save section'), 'submit_button');

        return $form->returnForm();
    }

    /**
     * Return HTML form to add/edit a student publication (work).
     *
     * @param string              $action
     * @param CStudentPublication $resource
     *
     * @throws Exception
     *
     * @return string HTML form
     */
    public function display_student_publication_form($action, CLpItem $lpItem, $resource)
    {
        $form = new FormValidator('frm_student_publication', 'post', '#');
        LearnPathItemForm::setForm($form, 'edit', $this, $lpItem);

        $form->addButtonSave(get_lang('Save'), 'submit_button');

        $return = '<div class="sectioncomment">';
        $return .= $form->returnForm();
        $return .= '</div>';

        return $return;
    }

    public function displayNewSectionForm()
    {
        $action = 'add_item';
        $item_type = 'dir';

        $lpItem = (new CLpItem())
            ->setTitle('')
            ->setItemType('dir')
        ;

        $url = api_get_self().'?'.api_get_cidreq().'&action='.$action.'&type='.$item_type.'&lp_id='.$this->lp_id;

        $form = new FormValidator('form_'.$item_type, 'POST', $url);
        LearnPathItemForm::setForm($form, 'add', $this, $lpItem);

        $form->addButtonSave(get_lang('Save section'), 'submit_button');
        $form->addElement('hidden', 'type', 'dir');

        return $form->returnForm();
    }

    /**
     * Returns the form to update or create a document.
     *
     * @param string  $action (add/edit)
     * @param CLpItem $lpItem
     *
     *
     * @throws Exception
     *
     * @return string HTML form
     */
    public function displayDocumentForm($action = 'add', $lpItem = null)
    {
        $courseInfo = api_get_course_info();

        $form = new FormValidator(
            'form',
            'POST',
            $this->getCurrentBuildingModeURL(),
            '',
            ['enctype' => 'multipart/form-data']
        );

        $data = $this->generate_lp_folder($courseInfo);

        if (null !== $lpItem) {
            LearnPathItemForm::setForm($form, $action, $this, $lpItem);
        }

        switch ($action) {
            case 'add':
                $folders = DocumentManager::get_all_document_folders(
                    $courseInfo,
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

                if ($data) {
                    $defaults['directory_parent_id'] = $data->getIid();
                }

                break;
        }

        $form->addButtonSave(get_lang('Save'), 'submit_button');

        return $form->returnForm();
    }

    /**
     * @param array  $courseInfo
     * @param string $content
     * @param string $title
     * @param int    $parentId
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

        if (empty($parentId) || '/' === $parentId) {
            $postDir = isset($_POST['dir']) ? $_POST['dir'] : $dir;
            $dir = isset($_GET['dir']) ? $_GET['dir'] : $postDir; // Please, do not modify this dirname formatting.

            if ('/' === $parentId) {
                $dir = '/';
            }

            // Please, do not modify this dirname formatting.
            if (strstr($dir, '..')) {
                $dir = '/';
            }

            if (!empty($dir[0]) && '.' == $dir[0]) {
                $dir = substr($dir, 1);
            }
            if (!empty($dir[0]) && '/' != $dir[0]) {
                $dir = '/'.$dir;
            }
            if (isset($dir[strlen($dir) - 1]) && '/' != $dir[strlen($dir) - 1]) {
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

        if (false === $putContent) {
            return 0;
        }

        $fileSize = filesize($filepath.$filename);
        $saveFilePath = $dir.$filename;

        $document = DocumentManager::addDocument(
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

        $documentId = $document->getIid();

        if (!$document) {
            return 0;
        }

        $newComment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
        $newTitle = $originalTitle;

        if ($newComment || $newTitle) {
            $em = Database::getManager();

            if ($newComment) {
                $document->setComment($newComment);
            }

            if ($newTitle) {
                $document->setTitle($newTitle);
            }

            $em->persist($document);
            $em->flush();
        }

        return $documentId;
    }

    /**
     * Displays the menu for manipulating a step.
     *
     * @return string
     */
    public function displayItemMenu(CLpItem $lpItem)
    {
        $item_id = $lpItem->getIid();
        $audio = $lpItem->getAudio();
        $itemType = $lpItem->getItemType();
        $path = $lpItem->getPath();

        $return = '';
        $audio_player = null;
        // We display an audio player if needed.
        if (!empty($audio)) {
            /*$webAudioPath = '../..'.api_get_path(REL_COURSE_PATH).$_course['path'].'/document/audio/'.$row['audio'];
            $audio_player .= '<div class="lp_mediaplayer" id="container">'
                .'<audio src="'.$webAudioPath.'" controls>'
                .'</div><br>';*/
        }

        $url = api_get_self().'?'.api_get_cidreq().'&view=build&id='.$item_id.'&lp_id='.$this->lp_id;

        if (TOOL_LP_FINAL_ITEM !== $itemType) {
            $return .= Display::url(
                Display::return_icon(
                    'edit.png',
                    get_lang('Edit'),
                    [],
                    ICON_SIZE_SMALL
                ),
                $url.'&action=edit_item&path_item='.$path
            );

            /*$return .= Display::url(
                Display::return_icon(
                    'move.png',
                    get_lang('Move'),
                    [],
                    ICON_SIZE_SMALL
                ),
                $url.'&action=move_item'
            );*/
        }

        // Commented for now as prerequisites cannot be added to chapters.
        if ('dir' !== $itemType) {
            $return .= Display::url(
                Display::return_icon(
                    'accept.png',
                    get_lang('Prerequisites'),
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

        /*if (in_array($itemType, [TOOL_DOCUMENT, TOOL_LP_FINAL_ITEM, TOOL_HOTPOTATOES])) {
            $documentData = DocumentManager::get_document_data_by_id($path, $course_code);
            if (empty($documentData)) {
                // Try with iid
                $table = Database::get_course_table(TABLE_DOCUMENT);
                $sql = "SELECT path FROM $table
                        WHERE
                              c_id = ".api_get_course_int_id()." AND
                              iid = ".$path." AND
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
        }*/

        if (!empty($audio_player)) {
            $return .= $audio_player;
        }

        return Display::toolbarAction('lp_item', [$return]);
    }

    /**
     * Creates the javascript needed for filling up the checkboxes without page reload.
     *
     * @return string
     */
    public function get_js_dropdown_array()
    {
        $return = 'var child_name = new Array();'."\n";
        $return .= 'var child_value = new Array();'."\n\n";
        $return .= 'child_name[0] = new Array();'."\n";
        $return .= 'child_value[0] = new Array();'."\n\n";

        $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $sql = "SELECT * FROM ".$tbl_lp_item."
                WHERE
                    lp_id = ".$this->lp_id." AND
                    parent_item_id = 0
                ORDER BY display_order ASC";
        Database::query($sql);
        $i = 0;

        $list = $this->getItemsForForm(true);

        foreach ($list as $row_zero) {
            if (TOOL_LP_FINAL_ITEM !== $row_zero['item_type']) {
                if (TOOL_QUIZ == $row_zero['item_type']) {
                    $row_zero['title'] = Exercise::get_formated_title_variable($row_zero['title']);
                }
                $js_var = json_encode(get_lang('After').' '.$row_zero['title']);
                $return .= 'child_name[0]['.$i.'] = '.$js_var.' ;'."\n";
                $return .= 'child_value[0]['.$i++.'] = "'.$row_zero['iid'].'";'."\n";
            }
        }

        $return .= "\n";
        $sql = "SELECT * FROM $tbl_lp_item
                WHERE lp_id = ".$this->lp_id;
        $res = Database::query($sql);
        while ($row = Database::fetch_array($res)) {
            $sql_parent = "SELECT * FROM ".$tbl_lp_item."
                           WHERE
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
                if (cbo) {
                    for(var i = cbo.length - 1; i > 0; i--) {
                        cbo.options[i] = null;
                    }
                    var k=0;
                    for (var i = 1; i <= child_name[id].length; i++){
                        var option = new Option(child_name[id][i - 1], child_value[id][i - 1]);
                        option.style.paddingLeft = '40px';
                        cbo.options[i] = option;
                        k = i;
                    }
                    cbo.options[k].selected = true;
                }

                //$('#previous').selectpicker('refresh');
            }";

        return $return;
    }

    /**
     * Display the form to allow moving an item.
     *
     * @param CLpItem $lpItem
     *
     * @throws Exception
     *
     *
     * @return string HTML form
     */
    public function display_move_item($lpItem)
    {
        $return = '';
        $path = $lpItem->getPath();

        if ($lpItem) {
            $itemType = $lpItem->getItemType();
            switch ($itemType) {
                case 'dir':
                case 'asset':
                    $return .= $this->displayItemMenu($lpItem);
                    $return .= $this->display_item_form(
                        $lpItem,
                        get_lang('Move the current section'),
                        'move',
                        $row
                    );
                    break;
                case TOOL_DOCUMENT:
                    $return .= $this->displayItemMenu($lpItem);
                    $return .= $this->displayDocumentForm('move', $lpItem);
                    break;
                case TOOL_LINK:
                    $link = null;
                    if (!empty($path)) {
                        $repo = Container::getLinkRepository();
                        $link = $repo->find($path);
                    }
                    $return .= $this->displayItemMenu($lpItem);
                    $return .= $this->display_link_form('move', $lpItem, $link);
                    break;
                case TOOL_HOTPOTATOES:
                    $return .= $this->displayItemMenu($lpItem);
                    $return .= $this->display_link_form('move', $lpItem, $row);
                    break;
                case TOOL_QUIZ:
                    $return .= $this->displayItemMenu($lpItem);
                    $return .= $this->display_quiz_form('move', $lpItem, $row);
                    break;
                case TOOL_STUDENTPUBLICATION:
                    $return .= $this->displayItemMenu($lpItem);
                    $return .= $this->display_student_publication_form('move', $lpItem, $row);
                    break;
                case TOOL_FORUM:
                    $return .= $this->displayItemMenu($lpItem);
                    $return .= $this->display_forum_form('move', $lpItem, $row);
                    break;
                case TOOL_THREAD:
                    $return .= $this->displayItemMenu($lpItem);
                    $return .= $this->display_forum_form('move', $lpItem, $row);
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
     * @return string HTML form
     */
    public function display_item_prerequisites_form(CLpItem $lpItem)
    {
        $course_id = api_get_course_int_id();
        $preRequisiteId = $lpItem->getPrerequisite();
        $itemId = $lpItem->getIid();

        $return = Display::page_header(get_lang('Add/edit prerequisites').' '.$lpItem->getTitle());

        $return .= '<form method="POST">';
        $return .= '<div class="table-responsive">';
        $return .= '<table class="table table-hover">';
        $return .= '<thead>';
        $return .= '<tr>';
        $return .= '<th>'.get_lang('Prerequisites').'</th>';
        $return .= '<th width="140">'.get_lang('minimum').'</th>';
        $return .= '<th width="140">'.get_lang('maximum').'</th>';
        $return .= '</tr>';
        $return .= '</thead>';

        // Adding the none option to the prerequisites see http://www.chamilo.org/es/node/146
        $return .= '<tbody>';
        $return .= '<tr>';
        $return .= '<td colspan="3">';
        $return .= '<div class="radio learnpath"><label for="idnone">';
        $return .= '<input checked="checked" id="idnone" name="prerequisites" type="radio" />';
        $return .= get_lang('none').'</label>';
        $return .= '</div>';
        $return .= '</tr>';

        // @todo use entitites
        $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $sql = "SELECT * FROM $tbl_lp_item
                WHERE lp_id = ".$this->lp_id;
        $result = Database::query($sql);

        $selectedMinScore = [];
        $selectedMaxScore = [];
        $masteryScore = [];
        while ($row = Database::fetch_array($result)) {
            if ($row['iid'] == $itemId) {
                $selectedMinScore[$row['prerequisite']] = $row['prerequisite_min_score'];
                $selectedMaxScore[$row['prerequisite']] = $row['prerequisite_max_score'];
            }
            $masteryScore[$row['iid']] = $row['mastery_score'];
        }

        $displayOrder = $lpItem->getDisplayOrder();
        $lpItemRepo = Container::getLpItemRepository();
        $itemRoot = $lpItemRepo->getRootItem($this->get_id());
        $em = Database::getManager();

        $currentItemId = $itemId;
        $options = [
            'decorate' => true,
            'rootOpen' => function() {
                return '';
            },
            'rootClose' => function() {
                return '';
            },
            'childOpen' => function() {
                return '';
            },
            'childClose' => '',
            'nodeDecorator' => function ($item) use (
                $currentItemId,
                $preRequisiteId,
                $course_id,
                $selectedMaxScore,
                $selectedMinScore,
                $displayOrder,
                $lpItemRepo,
                $em
            ) {
                $mainUrl = '';
                $fullTitle = $item['title'];
                $title = cut($fullTitle, self::MAX_LP_ITEM_TITLE_LENGTH);
                $itemId = $item['iid'];
                $type = $item['itemType'];
                $lpId = $this->get_id();
                $iconName = str_replace(' ', '', $type);
                $icon = Display::return_icon(
                    'lp_'.$iconName.'.png',
                    '',
                    [],
                    ICON_SIZE_TINY
                );
                $url = $mainUrl.'&view=build&id='.$itemId.'&lp_id='.$lpId;

                if ($itemId == $currentItemId) {
                    return '';
                }

                if ($displayOrder < $item['displayOrder']) {
                    return '';
                }

                $selectedMaxScoreValue = isset($selectedMaxScore[$itemId]) ? $selectedMaxScore[$itemId] : $item['maxScore'];
                $selectedMinScoreValue = $selectedMinScore[$itemId] ?? 0;
                $masteryScoreAsMinValue = $masteryScore[$itemId] ?? 0;

                $return = '<tr>';
                $return .= '<td '.((TOOL_QUIZ != $type && TOOL_HOTPOTATOES != $type) ? ' colspan="3"' : '').'>';
                $return .= '<div style="margin-left:'.($item['lvl'] * 20).'px;" class="radio learnpath">';
                $return .= '<label for="id'.$itemId.'">';

                $checked = '';
                if (null !== $preRequisiteId) {
                    $checked = in_array($preRequisiteId, [$itemId, $item['ref']]) ? ' checked="checked" ' : '';
                }

                $disabled = 'dir' === $type ? ' disabled="disabled" ' : '';

                $return .= '<input
                    '.$checked.' '.$disabled.'
                    id="id'.$itemId.'"
                    name="prerequisites"
                    type="radio"
                    value="'.$itemId.'" />';

                $return .= $icon.'&nbsp;&nbsp;'.$item['title'].'</label>';
                $return .= '</div>';
                $return .= '</td>';

                if (TOOL_QUIZ == $type) {
                    // lets update max_score Tests information depending of the Tests Advanced properties
                    $exercise = new Exercise($course_id);
                    /** @var CLpItem $itemEntity */
                    $itemEntity = $lpItemRepo->find($itemId);
                    $exercise->read($item['path']);
                    $itemEntity->setMaxScore($exercise->get_max_score());
                    $em->persist($itemEntity);
                    $em->flush($itemEntity);

                    $item['maxScore'] = $exercise->get_max_score();

                    if (empty($selectedMinScoreValue) && !empty($masteryScoreAsMinValue)) {
                        // Backwards compatibility with 1.9.x use mastery_score as min value
                        $selectedMinScoreValue = $masteryScoreAsMinValue;
                    }
                    $return .= '<td>';
                    $return .= '<input
                        class="form-control"
                        size="4" maxlength="3"
                        name="min_'.$itemId.'"
                        type="number"
                        min="0"
                        step="any"
                        max="'.$item['maxScore'].'"
                        value="'.$selectedMinScoreValue.'"
                    />';
                    $return .= '</td>';
                    $return .= '<td>';
                    $return .= '<input
                        class="form-control"
                        size="4"
                        maxlength="3"
                        name="max_'.$itemId.'"
                        type="number"
                        min="0"
                        step="any"
                        max="'.$item['maxScore'].'"
                        value="'.$selectedMaxScoreValue.'"
                    />';
                        $return .= '</td>';
                    }

                if (TOOL_HOTPOTATOES == $type) {
                    $return .= '<td>';
                    $return .= '<input
                        size="4"
                        maxlength="3"
                        name="min_'.$itemId.'"
                        type="number"
                        min="0"
                        step="any"
                        max="'.$item['maxScore'].'"
                        value="'.$selectedMinScoreValue.'"
                    />';
                        $return .= '</td>';
                        $return .= '<td>';
                        $return .= '<input
                        size="4"
                        maxlength="3"
                        name="max_'.$itemId.'"
                        type="number"
                        min="0"
                        step="any"
                        max="'.$item['maxScore'].'"
                        value="'.$selectedMaxScoreValue.'"
                    />';
                    $return .= '</td>';
                }
                $return .= '</tr>';

                return $return;
            },
        ];

        $tree = $lpItemRepo->childrenHierarchy($itemRoot, false, $options);
        $return .= $tree;
        $return .= '</tbody>';
        $return .= '</table>';
        $return .= '</div>';
        $return .= '<div class="form-group">';
        $return .= '<button class="btn btn-primary" name="submit_button" type="submit">'.
            get_lang('Save prerequisites settings').'</button>';
        $return .= '</form>';

        return $return;
    }

    /**
     * Return HTML list to allow prerequisites selection for lp.
     */
    public function display_lp_prerequisites_list(FormValidator $form)
    {
        $lp_id = $this->lp_id;
        $lp = api_get_lp_entity($lp_id);
        $prerequisiteId = $lp->getPrerequisite();

        $repo = Container::getLpRepository();
        $qb = $repo->findAllByCourse(api_get_course_entity(), api_get_session_entity());
        /** @var CLp[] $lps */
        $lps = $qb->getQuery()->getResult();

        //$session_id = api_get_session_id();
        /*$session_condition = api_get_session_condition($session_id, true, true);
        $sql = "SELECT * FROM $tbl_lp
                WHERE c_id = $course_id $session_condition
                ORDER BY display_order ";
        $rs = Database::query($sql);*/

        $items = [get_lang('none')];
        foreach ($lps as $lp) {
            $myLpId = $lp->getIid();
            if ($myLpId == $lp_id) {
                continue;
            }
            $items[$myLpId] = $lp->getName();
            /*$return .= '<option
                value="'.$myLpId.'" '.(($myLpId == $prerequisiteId) ? ' selected ' : '').'>'.
                $lp->getName().
                '</option>';*/
        }

        $select = $form->addSelect('prerequisites', get_lang('Prerequisites'), $items);
        $select->setSelected($prerequisiteId);
    }

    /**
     * Creates a list with all the documents in it.
     *
     * @param bool $showInvisibleFiles
     *
     * @throws Exception
     *
     *
     * @return string
     */
    public function get_documents($showInvisibleFiles = false)
    {
        $sessionId = api_get_session_id();
        $documentTree = DocumentManager::get_document_preview(
            api_get_course_entity(),
            $this->lp_id,
            null,
            $sessionId,
            true,
            null,
            null,
            $showInvisibleFiles,
            true
        );

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

        $folder = $this->generate_lp_folder(api_get_course_info());

        DocumentManager::build_directory_selector(
            $folders,
            $folder->getIid(),
            [],
            true,
            $form,
            'directory_parent_id'
        );

        $group = [
            $form->createElement(
                'radio',
                'if_exists',
                get_lang('If file exists:'),
                get_lang('Do nothing'),
                'nothing'
            ),
            $form->createElement(
                'radio',
                'if_exists',
                null,
                get_lang('Overwrite the existing file'),
                'overwrite'
            ),
            $form->createElement(
                'radio',
                'if_exists',
                null,
                get_lang('Rename the uploaded file if it exists'),
                'rename'
            ),
        ];
        $form->addGroup($group, null, get_lang('If file exists:'));

        $fileExistsOption = api_get_setting('document_if_file_exists_option');
        $defaultFileExistsOption = 'rename';
        if (!empty($fileExistsOption)) {
            $defaultFileExistsOption = $fileExistsOption;
        }
        $form->setDefaults(['if_exists' => $defaultFileExistsOption]);

        // Check box options
        $form->addCheckBox(
            'unzip',
            get_lang('Options'),
            get_lang('Uncompress zip')
        );

        $url = api_get_path(WEB_AJAX_PATH).'document.ajax.php?'.api_get_cidreq().'&a=upload_file&curdirpath=';
        $form->addMultipleUpload($url);

        $lpItem = (new CLpItem())
            ->setTitle('')
            ->setItemType(TOOL_DOCUMENT)
        ;
        $new = $this->displayDocumentForm('add', $lpItem);

        /*$lpItem = new CLpItem();
        $lpItem->setItemType(TOOL_READOUT_TEXT);
        $frmReadOutText = $this->displayDocumentForm('add');*/

        $headers = [
            get_lang('Files'),
            get_lang('Create a new document'),
            //get_lang('Create read-out text'),
            get_lang('Upload'),
        ];

        return Display::tabs(
            $headers,
            [$documentTree, $new, $form->returnForm()],
            'subtab',
            ['class' => 'mt-2']
        );
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
        $setting = 'true' === api_get_setting('lp.show_invisible_exercise_in_lp_toc');

        //$activeCondition = ' active <> -1 ';
        $active = 2;
        if ($setting) {
            $active = 1;
            //$activeCondition = ' active = 1 ';
        }

        $categoryCondition = '';

        $keyword = $_REQUEST['keyword'] ?? null;
        $categoryId = $_REQUEST['category_id'] ?? null;
        /*if (api_get_configuration_value('allow_exercise_categories') && !empty($categoryId)) {
            $categoryCondition = " AND exercise_category_id = $categoryId ";
        }

        $keywordCondition = '';

        if (!empty($keyword)) {
            $keyword = Database::escape_string($keyword);
            $keywordCondition = " AND title LIKE '%$keyword%' ";
        }
        */
        $course = api_get_course_entity($course_id);
        $session = api_get_session_entity($session_id);

        $qb = Container::getQuizRepository()->findAllByCourse($course, $session, $keyword, $active, false, $categoryId);
        /** @var CQuiz[] $exercises */
        $exercises = $qb->getQuery()->getResult();

        /*$sql_quiz = "SELECT * FROM $tbl_quiz
                     WHERE
                            c_id = $course_id AND
                            $activeCondition
                            $condition_session
                            $categoryCondition
                            $keywordCondition
                     ORDER BY title ASC";
        $res_quiz = Database::query($sql_quiz);*/

        $currentUrl = api_get_self().'?'.api_get_cidreq().'&action=add_item&type=step&lp_id='.$this->lp_id.'#resource_tab-2';

        // Create a search-box
        /*$form = new FormValidator('search_simple', 'get', $currentUrl);
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
                    ['placeholder' => get_lang('Please select an option')]
                );
            }
        }

        $form->addButtonSearch(get_lang('Search'));
        $return = $form->returnForm();*/

        $return = '<ul class="mt-2 bg-white list-group lp_resource">';
        $return .= '<li class="list-group-item lp_resource_element disable_drag">';
        $return .= Display::return_icon('new_exercice.png');
        $return .= '<a
            href="'.api_get_path(WEB_CODE_PATH).'exercise/exercise_admin.php?'.api_get_cidreq().'&lp_id='.$this->lp_id.'">'.
            get_lang('New test').'</a>';
        $return .= '</li>';

        $previewIcon = Display::return_icon(
            'preview_view.png',
            get_lang('Preview')
        );
        $quizIcon = Display::return_icon('quiz.png', '', [], ICON_SIZE_TINY);
        $moveIcon = Display::getMdiIcon('cursor-move', 'ch-tool-icon', '', 16, get_lang('Move'));
        $exerciseUrl = api_get_path(WEB_CODE_PATH).'exercise/overview.php?'.api_get_cidreq();
        foreach ($exercises as $exercise) {
            $exerciseId = $exercise->getIid();
            $title = strip_tags(api_html_entity_decode($exercise->getTitle()));
            $visibility = $exercise->isVisible($course, $session);

            $link = Display::url(
                $previewIcon,
                $exerciseUrl.'&exerciseId='.$exerciseId,
                ['target' => '_blank']
            );
            $return .= '<li
                class="list-group-item lp_resource_element"
                id="'.$exerciseId.'"
                data-id="'.$exerciseId.'"
                title="'.$title.'">';
            $return .= Display::url($moveIcon, '#', ['class' => 'moved']);
            $return .= $quizIcon;
            $sessionStar = '';
            /*$sessionStar = api_get_session_image(
                $row_quiz['session_id'],
                $userInfo['status']
            );*/
            $return .= Display::url(
                Security::remove_XSS(cut($title, 80)).$link.$sessionStar,
                api_get_self().'?'.
                    api_get_cidreq().'&action=add_item&type='.TOOL_QUIZ.'&file='.$exerciseId.'&lp_id='.$this->lp_id,
                [
                    'class' => false === $visibility ? 'moved text-muted ' : 'moved link_with_id',
                    'data_type' => 'quiz',
                    'data-id' => $exerciseId,
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
        $sessionId = api_get_session_id();
        $repo = Container::getLinkRepository();

        $course = api_get_course_entity();
        $session = api_get_session_entity($sessionId);
        $qb = $repo->getResourcesByCourse($course, $session);
        /** @var CLink[] $links */
        $links = $qb->getQuery()->getResult();

        $selfUrl = api_get_self();
        $courseIdReq = api_get_cidreq();
        $userInfo = api_get_user_info();

        $moveEverywhereIcon = Display::getMdiIcon('cursor-move', 'ch-tool-icon', '', 16, get_lang('Move'));

        $categorizedLinks = [];
        $categories = [];

        foreach ($links as $link) {
            $categoryId = null !== $link->getCategory() ? $link->getCategory()->getIid() : 0;
            if (empty($categoryId)) {
                $categories[0] = get_lang('Uncategorized');
            } else {
                $category = $link->getCategory();
                $categories[$categoryId] = $category->getCategoryTitle();
            }
            $categorizedLinks[$categoryId][$link->getIid()] = $link;
        }

        $linksHtmlCode =
            '<script>
            function toggle_tool(tool, id) {
                if(document.getElementById(tool+"_"+id+"_content").style.display == "none"){
                    document.getElementById(tool+"_"+id+"_content").style.display = "block";
                    document.getElementById(tool+"_"+id+"_opener").src = "'.Display::returnIconPath('remove.gif').'";
                } else {
                    document.getElementById(tool+"_"+id+"_content").style.display = "none";
                    document.getElementById(tool+"_"+id+"_opener").src = "'.Display::returnIconPath('add.png').'";
                }
            }
        </script>

        <ul class="mt-2 bg-white list-group lp_resource">
            <li class="list-group-item lp_resource_element disable_drag ">
                '.Display::return_icon('linksnew.gif').'
                <a
                href="'.api_get_path(WEB_CODE_PATH).'link/link.php?'.$courseIdReq.'&action=addlink&lp_id='.$this->lp_id.'"
                title="'.get_lang('Add a link').'">'.
                get_lang('Add a link').'
                </a>
            </li>';
        $linkIcon = Display::return_icon('links.png', '', [], ICON_SIZE_TINY);
        foreach ($categorizedLinks as $categoryId => $links) {
            $linkNodes = null;
            /** @var CLink $link */
            foreach ($links as $key => $link) {
                $title = $link->getTitle();
                $id = $link->getIid();
                $linkUrl = Display::url(
                    Display::return_icon('preview_view.png', get_lang('Preview')),
                    api_get_path(WEB_CODE_PATH).'link/link_goto.php?'.api_get_cidreq().'&link_id='.$key,
                    ['target' => '_blank']
                );

                if ($link->isVisible($course, $session)) {
                    //$sessionStar = api_get_session_image($linkSessionId, $userInfo['status']);
                    $sessionStar = '';
                    $url = $selfUrl.'?'.$courseIdReq.'&action=add_item&type='.TOOL_LINK.'&file='.$key.'&lp_id='.$this->lp_id;
                    $link = Display::url(
                        Security::remove_XSS($title).$sessionStar.$linkUrl,
                        $url,
                        [
                            'class' => 'moved link_with_id',
                            'data-id' => $key,
                            'data_type' => TOOL_LINK,
                            'title' => $title,
                        ]
                    );
                    $linkNodes .=
                        "<li
                            class='list-group-item lp_resource_element'
                            id= $id
                            data-id= $id
                            >
                         <a class='moved' href='#'>
                            $moveEverywhereIcon
                        </a>
                        $linkIcon $link
                        </li>";
                }
            }
            $linksHtmlCode .=
                '<li class="list-group-item disable_drag">
                    <a style="cursor:hand" onclick="javascript: toggle_tool(\''.TOOL_LINK.'\','.$categoryId.')" >
                        <img src="'.Display::returnIconPath('add.png').'" id="'.TOOL_LINK.'_'.$categoryId.'_opener"
                        align="absbottom" />
                    </a>
                    <span style="vertical-align:middle">'.Security::remove_XSS($categories[$categoryId]).'</span>
                </li>
            '.
                $linkNodes.
            '';
            //<div style="display:none" id="'.TOOL_LINK.'_'.$categoryId.'_content">'.
        }
        $linksHtmlCode .= '</ul>';

        return $linksHtmlCode;
    }

    /**
     * Creates a list with all the student publications in it.
     *
     * @return string
     */
    public function get_student_publications()
    {
        $return = '<ul class="mt-2 bg-white list-group lp_resource">';
        $return .= '<li class="list-group-item lp_resource_element">';
        $works = getWorkListTeacher(0, 100, null, null, null);
        if (!empty($works)) {
            $icon = Display::return_icon('works.png', '', [], ICON_SIZE_TINY);
            foreach ($works as $work) {
                $workId = $work['iid'];
                $link = Display::url(
                    Display::return_icon('preview_view.png', get_lang('Preview')),
                    api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq().'&id='.$workId,
                    ['target' => '_blank']
                );

                $return .= '<li
                    class="list-group-item lp_resource_element"
                    id="'.$workId.'"
                    data-id="'.$workId.'"
                    >';
                $return .= '<a class="moved" href="#">';
                $return .= Display::getMdiIcon('cursor-move', 'ch-tool-icon', '', 16, get_lang('Move'));
                $return .= '</a> ';

                $return .= $icon;
                $return .= Display::url(
                    Security::remove_XSS(cut(strip_tags($work['title']), 80)).' '.$link,
                    api_get_self().'?'.
                    api_get_cidreq().'&action=add_item&type='.TOOL_STUDENTPUBLICATION.'&file='.$work['iid'].'&lp_id='.$this->lp_id,
                    [
                        'class' => 'moved link_with_id',
                        'data-id' => $work['iid'],
                        'data_type' => TOOL_STUDENTPUBLICATION,
                        'title' => Security::remove_XSS(cut(strip_tags($work['title']), 80)),
                    ]
                );
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

        $a_forums = [];
        $courseEntity = api_get_course_entity(api_get_course_int_id());
        $sessionEntity = api_get_session_entity(api_get_session_id());

        foreach ($forumCategories as $forumCategory) {
            // The forums in this category.
            $forumsInCategory = get_forums_in_category($forumCategory->getIid());
            if (!empty($forumsInCategory)) {
                foreach ($forumsInCategory as $forum) {
                    if ($forum->isVisible($courseEntity, $sessionEntity)) {
                        $a_forums[] = $forum;
                    }
                }
            }
        }

        $return = '<ul class="mt-2 bg-white list-group lp_resource">';

        // First add link
        $return .= '<li class="list-group-item lp_resource_element disable_drag">';
        $return .= Display::return_icon('new_forum.png');
        $return .= Display::url(
            get_lang('Create a new forum'),
            api_get_path(WEB_CODE_PATH).'forum/index.php?'.api_get_cidreq().'&'.http_build_query([
                'action' => 'add',
                'content' => 'forum',
                'lp_id' => $this->lp_id,
            ]),
            ['title' => get_lang('Create a new forum')]
        );
        $return .= '</li>';

        $return .= '<script>
            function toggle_forum(forum_id) {
                if (document.getElementById("forum_"+forum_id+"_content").style.display == "none") {
                    document.getElementById("forum_"+forum_id+"_content").style.display = "block";
                    document.getElementById("forum_"+forum_id+"_opener").src = "'.Display::returnIconPath('remove.gif').'";
                } else {
                    document.getElementById("forum_"+forum_id+"_content").style.display = "none";
                    document.getElementById("forum_"+forum_id+"_opener").src = "'.Display::returnIconPath('add.png').'";
                }
            }
        </script>';
        $moveIcon = Display::getMdiIcon('cursor-move', 'ch-tool-icon', '', 16, get_lang('Move'));
        foreach ($a_forums as $forum) {
            $forumId = $forum->getIid();
            $title = Security::remove_XSS($forum->getForumTitle());
            $link = Display::url(
                Display::return_icon('preview_view.png', get_lang('Preview')),
                api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.api_get_cidreq().'&forum='.$forumId,
                ['target' => '_blank']
            );

            $return .= '<li
                    class="list-group-item lp_resource_element"
                    id="'.$forumId.'"
                    data-id="'.$forumId.'"
                    >';
            $return .= '<a class="moved" href="#">';
            $return .= $moveIcon;
            $return .= ' </a>';
            $return .= Display::return_icon('forum.png', '', [], ICON_SIZE_TINY);

            $moveLink = Display::url(
                $title.' '.$link,
                api_get_self().'?'.
                api_get_cidreq().'&action=add_item&type='.TOOL_FORUM.'&forum_id='.$forumId.'&lp_id='.$this->lp_id,
                [
                    'class' => 'moved link_with_id',
                    'data-id' => $forumId,
                    'data_type' => TOOL_FORUM,
                    'title' => $title,
                    'style' => 'vertical-align:middle',
                ]
            );
            $return .= '<a onclick="javascript:toggle_forum('.$forumId.');" style="cursor:hand; vertical-align:middle">
                            <img
                                src="'.Display::returnIconPath('add.png').'"
                                id="forum_'.$forumId.'_opener" align="absbottom"
                             />
                        </a>
                        '.$moveLink;
            $return .= '</li>';

            $return .= '<div style="display:none" id="forum_'.$forumId.'_content">';
            $threads = get_threads($forumId);
            if (is_array($threads)) {
                foreach ($threads as $thread) {
                    $threadId = $thread->getIid();
                    $link = Display::url(
                        Display::return_icon('preview_view.png', get_lang('Preview')),
                        api_get_path(WEB_CODE_PATH).
                        'forum/viewthread.php?'.api_get_cidreq().'&forum='.$forumId.'&thread='.$threadId,
                        ['target' => '_blank']
                    );

                    $return .= '<li
                        class="list-group-item lp_resource_element"
                      id="'.$threadId.'"
                        data-id="'.$threadId.'"
                    >';
                    $return .= '&nbsp;<a class="moved" href="#">';
                    $return .= $moveIcon;
                    $return .= ' </a>';
                    $return .= Display::return_icon('forumthread.png', get_lang('Thread'), [], ICON_SIZE_TINY);
                    $return .= '<a
                        class="moved link_with_id"
                        data-id="'.$threadId.'"
                        data_type="'.TOOL_THREAD.'"
                        title="'.$thread->getThreadTitle().'"
                        href="'.api_get_self().'?'.api_get_cidreq().'&action=add_item&type='.TOOL_THREAD.'&thread_id='.$threadId.'&lp_id='.$this->lp_id.'"
                        >'.
                        Security::remove_XSS($thread->getThreadTitle()).' '.$link.'</a>';
                    $return .= '</li>';
                }
            }
            $return .= '</div>';
        }
        $return .= '</ul>';

        return $return;
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
        if (1 == $status) {
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
        $cb->set_tools_specific_id_list(
            ['learnpaths' => [$this->lp_id]]
        );

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
        if ('M' == substr($post_max, -1, 1)) {
            $post_max = intval(substr($post_max, 0, -1)) * 1024 * 1024;
        } elseif ('G' == substr($post_max, -1, 1)) {
            $post_max = intval(substr($post_max, 0, -1)) * 1024 * 1024 * 1024;
        }
        $upl_max = ini_get('upload_max_filesize');
        if ('M' == substr($upl_max, -1, 1)) {
            $upl_max = intval(substr($upl_max, 0, -1)) * 1024 * 1024;
        } elseif ('G' == substr($upl_max, -1, 1)) {
            $upl_max = intval(substr($upl_max, 0, -1)) * 1024 * 1024 * 1024;
        }

        $repo = Container::getDocumentRepository();
        $documents_total_space = $repo->getTotalSpace(api_get_course_int_id());

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
    public function clearPrerequisites()
    {
        $course_id = $this->get_course_int_id();
        $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $lp_id = $this->get_id();
        // Cleaning prerequisites
        $sql = "UPDATE $tbl_lp_item SET prerequisite = ''
                WHERE lp_id = $lp_id";
        Database::query($sql);

        // Cleaning mastery score for exercises
        $sql = "UPDATE $tbl_lp_item SET mastery_score = ''
                WHERE lp_id = $lp_id AND item_type = 'quiz'";
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
                    if ('dir' != $item->get_type()) {
                        // Current item is not a folder, so it qualifies to get a prerequisites
                        if ('quiz' == $last_item_not_dir_type) {
                            // if previous is quiz, mark its max score as default score to be achieved
                            $sql = "UPDATE $tbl_lp_item SET mastery_score = '$last_item_not_dir_max'
                                    WHERE c_id = $course_id AND lp_id = $lp_id AND iid = $last_item_not_dir";
                            Database::query($sql);
                        }
                        // now simply update the prerequisite to set it to the last non-chapter item
                        $sql = "UPDATE $tbl_lp_item SET prerequisite = '$last_item_not_dir'
                                WHERE lp_id = $lp_id AND iid = $current_item_id";
                        Database::query($sql);
                        // record item as 'non-chapter' reference
                        $last_item_not_dir = $item->get_id();
                        $last_item_not_dir_type = $item->get_type();
                        $last_item_not_dir_max = $item->get_max();
                    }
                } else {
                    if ('dir' != $item->get_type()) {
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
     * @return int
     */
    public static function createCategory($params)
    {
        $courseEntity = api_get_course_entity(api_get_course_int_id());

        $item = new CLpCategory();
        $item
            ->setName($params['name'])
            ->setParent($courseEntity)
            ->addCourseLink($courseEntity, api_get_session_entity())
        ;

        $repo = Container::getLpCategoryRepository();
        $repo->create($item);

        return $item->getIid();
    }

    /**
     * @param array $params
     */
    public static function updateCategory($params)
    {
        $em = Database::getManager();
        /** @var CLpCategory $item */
        $item = $em->find(CLpCategory::class, $params['id']);
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
        $id = (int) $id;
        $em = Database::getManager();
        /** @var CLpCategory $item */
        $item = $em->find(CLpCategory::class, $id);
        if ($item) {
            $position = $item->getPosition() - 1;
            $item->setPosition($position);
            $em->persist($item);
            $em->flush();
        }
    }

    /**
     * @param int $id
     */
    public static function moveDownCategory($id)
    {
        $id = (int) $id;
        $em = Database::getManager();
        /** @var CLpCategory $item */
        $item = $em->find(CLpCategory::class, $id);
        if ($item) {
            $position = $item->getPosition() + 1;
            $item->setPosition($position);
            $em->persist($item);
            $em->flush();
        }
    }

    /**
     * @param int $courseId
     *
     * @return int
     */
    public static function getCountCategories($courseId)
    {
        if (empty($courseId)) {
            return 0;
        }
        $repo = Container::getLpCategoryRepository();
        $qb = $repo->getResourcesByCourse(api_get_course_entity($courseId));
        $qb->addSelect('count(resource)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int $courseId
     *
     * @return CLpCategory[]
     */
    public static function getCategories($courseId)
    {
        // Using doctrine extensions
        $repo = Container::getLpCategoryRepository();
        $qb = $repo->getResourcesByCourse(api_get_course_entity($courseId), api_get_session_entity());

        return $qb->getQuery()->getResult();
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
     */
    public static function deleteCategory($id): bool
    {
        $repo = Container::getLpCategoryRepository();
        /** @var CLpCategory $category */
        $category = $repo->find($id);
        if ($category) {
            $em = Database::getManager();
            $lps = $category->getLps();

            foreach ($lps as $lp) {
                $lp->setCategory(null);
                $em->persist($lp);
            }

            // Removing category.
            $em->remove($category);
            $em->flush();

            return true;
        }

        return false;
    }

    /**
     * @param int  $courseId
     * @param bool $addSelectOption
     *
     * @return array
     */
    public static function getCategoryFromCourseIntoSelect($courseId, $addSelectOption = false)
    {
        $repo = Container::getLpCategoryRepository();
        $qb = $repo->getResourcesByCourse(api_get_course_entity($courseId));
        $items = $qb->getQuery()->getResult();

        $cats = [];
        if ($addSelectOption) {
            $cats = [get_lang('Select a category')];
        }

        if (!empty($items)) {
            foreach ($items as $cat) {
                $cats[$cat->getIid()] = $cat->getName();
            }
        }

        return $cats;
    }

    /**
     * @param int   $courseId
     * @param int   $lpId
     * @param int   $user_id
     *
     * @return learnpath
     */
    public static function getLpFromSession(int $courseId, int $lpId, int $user_id)
    {
        $debug = 0;
        $learnPath = null;
        $lpObject = Session::read('lpobject');

        $repo = Container::getLpRepository();
        $lp = $repo->find($lpId);
        if (null !== $lpObject) {
            /** @var learnpath $learnPath */
            $learnPath = UnserializeApi::unserialize('lp', $lpObject);
            $learnPath->entity = $lp;
            if ($debug) {
                error_log('getLpFromSession: unserialize');
                error_log('------getLpFromSession------');
                error_log('------unserialize------');
                error_log("lp_view_session_id: ".$learnPath->lp_view_session_id);
                error_log("api_get_sessionid: ".api_get_session_id());
            }
        }

        if (!is_object($learnPath)) {
            $learnPath = new learnpath($lp, api_get_course_info_by_id($courseId), $user_id);
            if ($debug) {
                error_log('------getLpFromSession------');
                error_log('getLpFromSession: create new learnpath');
                error_log("create new LP with $courseId - $lpId - $user_id");
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
            return $attempt->get_attempt_id();
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

                if (!empty($exerciseResultInfo['max_score'])) {
                    $exerciseResult = $exerciseResultInfo['score'] * 100 / $exerciseResultInfo['max_score'];
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
                $evaluationResult = $evaluationResultInfo['score'] * 100 / $evaluationResultInfo['max_score'];
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
            if ('quiz' !== $item->type) {
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
            if (TOOL_QUIZ !== $item->type) {
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

        if (false !== $exercisesItems) {
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

                $totalExercisesResult += $exerciseResultInfo['score'];
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
                $totalEvaluationResult += $evaluationResultInfo['score'];
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
        if (false === strpos(api_get_path(WEB_CODE_PATH), 'https')) {
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

        if (false == $protocolFixApplied) {
            if (false === strpos(api_get_path(WEB_PATH), $host)) {
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
     * @deprecated
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
     * @return array
     */
    public function getForum($sessionId = 0)
    {
        $repo = Container::getForumRepository();

        $course = api_get_course_entity();
        $session = api_get_session_entity($sessionId);
        $qb = $repo->getResourcesByCourse($course, $session);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the LP Final Item form.
     *
     * @throws Exception
     *
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
            $buttonText = get_lang('Add this document to the course');
            $content = $this->getFinalItemTemplate();
        }

        $editorConfig = [
            'ToolbarSet' => 'LearningPathDocuments',
            'Width' => '100%',
            'Height' => '500',
            'FullPage' => true,
        ];

        $url = api_get_self().'?'.api_get_cidreq().'&'.http_build_query([
            'type' => 'document',
            'lp_id' => $this->lp_id,
        ]);

        $form = new FormValidator('final_item', 'POST', $url);
        $form->addText('title', get_lang('Title'));
        $form->addButtonSave($buttonText);
        $form->addHtml(
            Display::return_message(
                'Variables :</br></br> <b>((certificate))</b> </br> <b>((skill))</b>',
                'normal',
                false
            )
        );

        $renderer = $form->defaultRenderer();
        $renderer->setElementTemplate('&nbsp;{label}{element}', 'content_lp_certificate');

        $form->addHtmlEditor(
            'content_lp_certificate',
            null,
            true,
            false,
            $editorConfig
        );
        $form->addHidden('action', 'add_final_item');
        $form->addHidden('path', Session::read('pathItem'));
        $form->addHidden('previous', $this->get_last());
        $form->setDefaults(
            ['title' => $title, 'content_lp_certificate' => $content]
        );

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
                );

                Display::addFlash(
                    Display::return_message(get_lang('Added'))
                );
            } else {
                $this->edit_document();
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
     *
     * @return string
     */
    public static function rl_get_resource_link_for_learnpath(
        $course_id,
        $learningPathId,
        $id_in_path,
        $lpViewId
    ) {
        $session_id = api_get_session_id();

        $learningPathId = (int) $learningPathId;
        $id_in_path = (int) $id_in_path;
        $lpViewId = (int) $lpViewId;

        $em = Database::getManager();
        $lpItemRepo = $em->getRepository(CLpItem::class);

        /** @var CLpItem $rowItem */
        $rowItem = $lpItemRepo->findOneBy([
            'lp' => $learningPathId,
            'iid' => $id_in_path,
        ]);
        $type = $rowItem->getItemType();
        $id = empty($rowItem->getPath()) ? '0' : $rowItem->getPath();
        $main_dir_path = api_get_path(WEB_CODE_PATH);
        $link = '';
        $extraParams = api_get_cidreq(true, true, 'learnpath').'&sid='.$session_id;

        switch ($type) {
            case 'dir':
                return $main_dir_path.'lp/blank.php';
            case TOOL_CALENDAR_EVENT:
                return $main_dir_path.'calendar/agenda.php?agenda_id='.$id.'&'.$extraParams;
            case TOOL_ANNOUNCEMENT:
                return $main_dir_path.'announcements/announcements.php?ann_id='.$id.'&'.$extraParams;
            case TOOL_LINK:
                $linkInfo = Link::getLinkInfo($id);
                if (isset($linkInfo['url'])) {
                    return $linkInfo['url'];
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
                        ['item' => $rowItem->getIid(), 'view' => $lpViewId],
                        ['viewCount' => 'DESC'],
                        1
                    );
                /** @var CLpItemView $learnpathItemViewData */
                $learnpathItemViewData = current($learnpathItemViewResult);
                $learnpathItemViewId = $learnpathItemViewData ? $learnpathItemViewData->getIid() : 0;

                return $main_dir_path.'exercise/overview.php?'.$extraParams.'&'
                    .http_build_query([
                        'lp_init' => 1,
                        'learnpath_item_view_id' => $learnpathItemViewId,
                        'learnpath_id' => $learningPathId,
                        'learnpath_item_id' => $id_in_path,
                        'exerciseId' => $id,
                    ]);
            case TOOL_HOTPOTATOES:
                return '';
            case TOOL_FORUM:
                return $main_dir_path.'forum/viewforum.php?forum='.$id.'&lp=true&'.$extraParams;
            case TOOL_THREAD:
                // forum post
                $tbl_topics = Database::get_course_table(TABLE_FORUM_THREAD);
                if (empty($id)) {
                    return '';
                }
                $sql = "SELECT * FROM $tbl_topics WHERE iid=$id";
                $result = Database::query($sql);
                $row = Database::fetch_array($result);

                return $main_dir_path.'forum/viewthread.php?thread='.$id.'&forum='.$row['forum_id'].'&lp=true&'
                    .$extraParams;
            case TOOL_POST:
                $tbl_post = Database::get_course_table(TABLE_FORUM_POST);
                $result = Database::query("SELECT * FROM $tbl_post WHERE post_id=$id");
                $row = Database::fetch_array($result);

                return $main_dir_path.'forum/viewthread.php?post='.$id.'&thread='.$row['thread_id'].'&forum='
                    .$row['forum_id'].'&lp=true&'.$extraParams;
            case TOOL_READOUT_TEXT:
                return api_get_path(WEB_CODE_PATH).
                    'lp/readout_text.php?&id='.$id.'&lp_id='.$learningPathId.'&'.$extraParams;
            case TOOL_DOCUMENT:
                $repo = Container::getDocumentRepository();
                $document = $repo->find($rowItem->getPath());
                if ($document) {
                    $params = [
                        'cid' => $course_id,
                        'sid' => $session_id,
                    ];

                    return $repo->getResourceFileUrl($document, $params, UrlGeneratorInterface::ABSOLUTE_URL);
                }

                return null;
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
                    return $main_dir_path.'work/work_list.php?id='.$rowItem->getPath().'&'.$extraParams;
                }

                return $main_dir_path.'work/work.php?'.api_get_cidreq().'&id='.$rowItem->getPath().'&'.$extraParams;
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
                $result = Database::query("SELECT * FROM $TBL_EXERCICES WHERE c_id = $course_id AND id = $id");
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

                        if ('local' === $scope) {
                            if ('abs' === $type || 'rel' === $type) {
                                $documentFile = strstr($realOrigPath, 'document');
                                if (false !== strpos($realOrigPath, $documentFile)) {
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

        if (!empty($itemList['thread'])) {
            $threadList = [];
            $repo = Container::getForumThreadRepository();
            foreach ($itemList['thread'] as $threadId) {
                /** @var CForumThread $thread */
                $thread = $repo->find($threadId);
                if ($thread) {
                    $itemList['forum'][] = $thread->getForum() ? $thread->getForum()->getIid() : 0;
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
     * @param int $lpId
     * @param int $courseId
     *
     * @return mixed
     */
    public static function getAccumulateWorkTimePrerequisite($lpId, $courseId)
    {
        $lpId = (int) $lpId;
        $table = Database::get_course_table(TABLE_LP_MAIN);
        $sql = "SELECT accumulate_work_time
                FROM $table
                WHERE iid = $lpId";
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
        $icons = ['' => get_lang('Please select an option')];

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

        $sql = "SELECT * FROM $tbl_lp_item
                WHERE path <> 'root' AND lp_id = ".$this->lp_id;

        if ($addParentCondition) {
            $sql .= ' AND parent_item_id IS NULL ';
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
        if (2 != $this->get_type()) {
            return false;
        }
        // If more than one step in the SCORM, we don't care about the setting
        if ($this->get_total_items_count() > 1) {
            return false;
        }
        $extraFieldValue = new ExtraFieldValue('lp');
        $doUseScore = false;
        $useScore = $extraFieldValue->get_values_by_handler_and_field_variable(
            $this->get_id(),
            'use_score_as_progress'
        );
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
        } elseif (null != api_get_configuration_value('scorm_api_extrafield_to_use_as_student_id')) {
            $extraFieldValue = new ExtraFieldValue('user');
            $extrafield = $extraFieldValue->get_values_by_handler_and_field_variable(
                api_get_user_id(),
                api_get_configuration_value('scorm_api_extrafield_to_use_as_student_id')
            );

            return $extrafield['value'];
        } else {
            return api_get_user_id();
        }
    }

    /**
     * Save the new order for learning path items.
     *
     * @param array $orderList A associative array with id and parent_id keys.
     */
    public static function sortItemByOrderList(CLpItem $rootItem, array $orderList = [], $flush = true)
    {
        if (empty($orderList)) {
            return true;
        }
        $lpItemRepo = Container::getLpItemRepository();
        $em = Database::getManager();
        $counter = 2;
        $rootItem->setDisplayOrder(1);
        $rootItem->setPreviousItemId(null);
        $em->persist($rootItem);
        if ($flush) {
            $em->flush();
        }

        foreach ($orderList as $item) {
            $itemId = $item->id ?? 0;
            if (empty($itemId)) {
                continue;
            }
            $parentId = $item->parent_id ?? 0;
            $parent = $rootItem;
            if (!empty($parentId)) {
                $parentExists = $lpItemRepo->find($parentId);
                if (null !== $parentExists) {
                    $parent = $parentExists;
                }
            }

            /** @var CLpItem $itemEntity */
            $itemEntity = $lpItemRepo->find($itemId);
            $itemEntity->setParent($parent);
            $previousId = (int) $itemEntity->getPreviousItemId();
            //if (0 === $previousId) {
                $itemEntity->setPreviousItemId(null);
            //}

            $nextId = (int) $itemEntity->getNextItemId();
            //if (0 === $nextId) {
                $itemEntity->setNextItemId(null);
            //}

            $itemEntity->setDisplayOrder($counter);
            $em->persist($itemEntity);
            if ($flush) {
                $em->flush();
            }
            $counter++;
        }

        $em->flush();
        $lpItemRepo->recoverNode($rootItem, 'displayOrder');
        $em->persist($rootItem);
        if ($flush) {
            $em->flush();
        }

        return true;
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

        if (0 == $parentItemId) {
            return 0;
        }

        return self::get_level_for_item($items, $parentItemId) + 1;
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
        return 'lp/lp_controller.php?'.api_get_cidreq().'&'
            .http_build_query(
                [
                    'action' => 'view_category',
                    'id' => $categoryId,
                ]
            );
    }

    /**
     * Check and obtain the lp final item if exist.
     *
     * @return learnpathItem
     */
    private function getFinalItem()
    {
        if (empty($this->items)) {
            return null;
        }

        foreach ($this->items as $item) {
            if ('final_item' !== $item->type) {
                continue;
            }

            return $item;
        }
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

        $repo = Container::getDocumentRepository();
        /** @var CDocument $document */
        $document = $repo->find($finalItem->path);

        if ($document && $document->getResourceNode()->hasResourceFile()) {
            return $repo->getResourceFileContent($document);
        }

        return '';
    }
}

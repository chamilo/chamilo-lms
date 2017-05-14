<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CLp;

/**
 * Class LearnpathList
 * This class is only a learning path list container with several practical methods for sorting the list and
 * provide links to specific paths
 * @uses    Database.lib.php to use the database
 * @uses    learnpath.class.php to generate learnpath objects to get in the list
 * @author Yannick Warnier <ywarnier@beeznest.org>
 *
 */
class LearnpathList
{
    // Holds a flat list of learnpaths data from the database.
    public $list = array();
    // Holds a list of references to the learnpaths objects (only filled by get_refs()).
    public $ref_list = array();
    // Holds a flat list of learnpaths sorted by alphabetical name order.
    public $alpha_list = array();
    public $course_code;
    public $user_id;
    public $refs_active = false;

    /**
     * This method is the constructor for the learnpathList. It gets a list of available learning paths from
     * the database and creates the learnpath objects. This list depends on the user that is connected
     * (only displays) items if he has enough permissions to view them.
     * @param   integer $user_id
     * @param   string $course_code Optional course code (otherwise we use api_get_course_id())
     * @param   int $session_id Optional session id (otherwise we use api_get_session_id())
     * @param   string $order_by
     * @param   bool $check_publication_dates
     * @param   int     $categoryId
     * @param bool $ignoreCategoryFilter
     */
    public function __construct(
        $user_id,
        $course_code = '',
        $session_id = null,
        $order_by = null,
        $check_publication_dates = false,
        $categoryId = null,
        $ignoreCategoryFilter = false
    ) {
        $course_info = api_get_course_info($course_code);
        $tbl_tool = Database::get_course_table(TABLE_TOOL_LIST);

        $this->course_code = $course_code;
        $this->user_id = $user_id;
        $course_id = $course_info['real_id'];

        if (empty($course_id)) {
            return false;
        }

        // Condition for the session.
        if (isset($session_id)) {
            $session_id = intval($session_id);
        } else {
            $session_id = api_get_session_id();
        }

        $condition_session = api_get_session_condition(
            $session_id,
            true,
            true,
            'lp.sessionId'
        );

        $order = "ORDER BY lp.displayOrder ASC, lp.name ASC";
        if (isset($order_by)) {
            $order = Database::parse_conditions(array('order' => $order_by));
        }

        $now = api_get_utc_datetime();
        $time_conditions = '';

        if ($check_publication_dates) {
            $time_conditions = " AND (
                (lp.publicatedOn IS NOT NULL AND lp.publicatedOn < '$now' AND lp.expiredOn IS NOT NULL AND lp.expiredOn > '$now') OR
                (lp.publicatedOn IS NOT NULL AND lp.publicatedOn < '$now' AND lp.expiredOn IS NULL) OR
                (lp.publicatedOn IS NULL AND lp.expiredOn IS NOT NULL AND lp.expiredOn > '$now') OR
                (lp.publicatedOn IS NULL AND lp.expiredOn IS NULL ))
            ";
        }

        $categoryFilter = '';
        if ($ignoreCategoryFilter == false) {
            if (!empty($categoryId)) {
                $categoryId = intval($categoryId);
                $categoryFilter = " AND lp.categoryId = $categoryId";
            } else {
                $categoryFilter = " AND (lp.categoryId = 0 OR lp.categoryId IS NULL) ";
            }
        }

        $dql = "SELECT lp FROM ChamiloCourseBundle:CLp as lp
                WHERE
                    lp.cId = $course_id
                    $time_conditions
                    $condition_session
                    $categoryFilter
                    $order
                ";

        $learningPaths = Database::getManager()->createQuery($dql)->getResult();
        $showBlockedPrerequisite = api_get_configuration_value('show_prerequisite_as_blocked');
        $names = [];
        /** @var CLp $row */
        foreach ($learningPaths as $row) {
            // Use domesticate here instead of Database::escape_string because
            // it prevents ' to be slashed and the input (done by learnpath.class.php::toggle_visibility())
            // is done using domesticate()
            $name = domesticate($row->getName());
            $link = 'lp/lp_controller.php?action=view&lp_id='.$row->getId().'&id_session='.$session_id;
            $oldLink = 'newscorm/lp_controller.php?action=view&lp_id='.$row->getId().'&id_session='.$session_id;

            $sql2 = "SELECT * FROM $tbl_tool
                     WHERE
                        c_id = $course_id AND 
                        name = '$name' AND
                        image = 'scormbuilder.gif' AND
                        (
                            link LIKE '$link%' OR
                            link LIKE '$oldLink%'                                
                        )
                      ";
            $res2 = Database::query($sql2);
            if (Database::num_rows($res2) > 0) {
                $row2 = Database::fetch_array($res2);
                $pub = $row2['visibility'];

            } else {
                $pub = 'i';
            }

            // Check if visible.
            $visibility = api_get_item_visibility(
                api_get_course_info($course_code),
                'learnpath',
                $row->getId(),
                $session_id
            );

            // If option is not true then don't show invisible LP to user
            if ($showBlockedPrerequisite !== true && !api_is_allowed_to_edit()) {
                $lpVisibility = learnpath::is_lp_visible_for_student(
                    $row->getId(),
                    $user_id,
                    $course_code
                );
                if ($lpVisibility === false) {
                    continue;
                }
            }

            $this->list[$row->getIid()] = array(
                'lp_type' => $row->getLpType(),
                'lp_session' => $row->getSessionId(),
                'lp_name' => stripslashes($row->getName()),
                'lp_desc' => stripslashes($row->getDescription()),
                'lp_path' => $row->getPath(),
                'lp_view_mode' => $row->getDefaultViewMod(),
                'lp_force_commit' => $row->getForceCommit(),
                'lp_maker' => stripslashes($row->getContentMaker()),
                'lp_proximity' => $row->getContentLocal(),
                'lp_encoding' => api_get_system_encoding(),
                'lp_visibility' => $visibility,
                'lp_published' => $pub,
                'lp_prevent_reinit' => $row->getPreventReinit(),
                'seriousgame_mode' => $row->getSeriousgameMode(),
                'lp_scorm_debug' => $row->getDebug(),
                'lp_display_order' => $row->getDisplayOrder(),
                'lp_preview_image' => stripslashes($row->getPreviewImage()),
                'autolaunch' => $row->getAutolaunch(),
                'session_id' => $row->getSessionId(),
                'created_on' => $row->getCreatedOn() ? $row->getCreatedOn()->format('Y-m-d H:i:s') : null,
                'modified_on' => $row->getModifiedOn() ? $row->getModifiedOn()->format('Y-m-d H:i:s') : null,
                'publicated_on' => $row->getPublicatedOn() ? $row->getPublicatedOn()->format('Y-m-d H:i:s') : null,
                'expired_on' => $row->getExpiredOn() ? $row->getExpiredOn()->format('Y-m-d H:i:s') : null,
                //'category_id'       => $row['category_id'],
                'subscribe_users' => $row->getSubscribeUsers(),
                'lp_old_id' => $row->getId(),
                'iid' => $row->getIid(),
                'prerequisite' => $row->getPrerequisite()
            );
            $names[$row->getName()] = $row->getIid();
        }
        asort($names);
        $this->alpha_list = $names;
    }

    /**
     * Gets references to learnpaths for all learnpaths IDs kept in the local list.
     * This applies a transformation internally on list and ref_list and returns a copy of the refs list
     * @return array    List of references to learnpath objects
     */
    public function get_refs()
    {
        foreach ($this->list as $id => $dummy) {
            $this->ref_list[$id] = new learnpath($this->course_code, $id, $this->user_id);
        }
        $this->refs_active = true;
        return $this->ref_list;
    }

    /**
     * Gets a table of the different learnpaths we have at the moment
     * @return array    Learnpath info as [lp_id] => ([lp_type]=> ..., [lp_name]=>...,[lp_desc]=>...,[lp_path]=>...)
     */
    public function get_flat_list()
    {
        return $this->list;
    }

    /**
     *  Gets a list of lessons  of the given course_code and session_id
     *  This functions doesn't need user_id
     *  @param string $course_code Text code of the course
     *  @param int  $session_id Id of session
     *  @return array List of lessons with lessons id as keys
     */
    public static function get_course_lessons($course_code, $session_id)
    {
        $table = Database::get_course_table(TABLE_LP_MAIN);
        $course = api_get_course_info($course_code);
        // @todo AND session_id = %s ?
        $sql = "SELECT * FROM $table WHERE c_id = %s ";
        $sql_query = sprintf($sql, $course['real_id']);
        $result = Database::query($sql_query);

        $lessons = array();
        while ($row = Database::fetch_array($result)) {
            if (api_get_item_visibility($course, 'learnpath', $row['id'], $session_id)) {
                $lessons[$row['id']] = $row;
            }
        }

        return $lessons;
    }
}

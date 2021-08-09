<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CLp;

/**
 * Class LearnpathList
 * This class is only a learning path list container with several practical methods for sorting the list and
 * provide links to specific paths.
 *
 * @uses    \Database.lib.php to use the database
 * @uses    \learnpath.class.php to generate learnpath objects to get in the list
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
class LearnpathList
{
    // Holds a flat list of learnpaths data from the database.
    public $list = [];
    // Holds a flat list of learnpaths sorted by alphabetical name order.
    public $alpha_list = [];
    public $course_code;
    public $user_id;

    /**
     * This method is the constructor for the learnpathList. It gets a list of available learning paths from
     * the database and creates the learnpath objects. This list depends on the user that is connected
     * (only displays) items if he has enough permissions to view them.
     *
     * @param int    $user_id
     * @param array  $courseInfo              Optional course code (otherwise we use api_get_course_id())
     * @param int    $session_id              Optional session id (otherwise we use api_get_session_id())
     * @param string $order_by
     * @param bool   $check_publication_dates
     * @param int    $categoryId
     * @param bool   $ignoreCategoryFilter
     * @param bool   $ignoreLpVisibility      get the list of LPs for reports
     */
    public function __construct(
        $user_id,
        $courseInfo = [],
        $session_id = 0,
        $order_by = null,
        $check_publication_dates = false,
        $categoryId = null,
        $ignoreCategoryFilter = false,
        $ignoreLpVisibility = false,
        bool $includeSubscribedLp = true
    ) {
        if (empty($courseInfo)) {
            $courseInfo = api_get_course_info();
        }

        $this->course_code = $courseInfo['code'];
        $course_id = $courseInfo['real_id'];
        $this->user_id = $user_id;

        // Condition for the session.
        $session_id = empty($session_id) ? api_get_session_id() : (int) $session_id;
        $condition_session = api_get_session_condition(
            $session_id,
            true,
            true,
            'lp.sessionId'
        );

        $tbl_tool = Database::get_course_table(TABLE_TOOL_LIST);

        $order = ' ORDER BY lp.displayOrder ASC, lp.name ASC';
        if (!empty($order_by)) {
            // @todo Replace with criteria order by
            $order = ' ORDER BY '.Database::escape_string($order_by);
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
                $categoryId = (int) $categoryId;
                $categoryFilter = " AND lp.categoryId = $categoryId";
            } else {
                $categoryFilter = ' AND (lp.categoryId = 0 OR lp.categoryId IS NULL) ';
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
        $isAllowToEdit = api_is_allowed_to_edit();
        $toolSessionCondition = api_get_session_condition($session_id);

        /** @var CLp $row */
        foreach ($learningPaths as $row) {
            $link = 'lp/lp_controller.php?action=view&lp_id='.$row->getId().'&id_session='.$session_id;
            $oldLink = 'newscorm/lp_controller.php?action=view&lp_id='.$row->getId().'&id_session='.$session_id;

            $extraCondition = '';
            if (!empty($session_id)) {
                $extraLink = 'lp/lp_controller.php?action=view&lp_id='.$row->getId().'&id_session=0';
                $extraCondition = " OR link LIKE '$extraLink' ";
            }

            $sql2 = "SELECT visibility FROM $tbl_tool
                     WHERE
                        c_id = $course_id AND
                        image = 'scormbuilder.gif' AND
                        (
                            link LIKE '$link%' OR
                            link LIKE '$oldLink%'
                            $extraCondition
                        )
                        $toolSessionCondition
                      ";
            $res2 = Database::query($sql2);
            $pub = 'i';
            if (Database::num_rows($res2) > 0) {
                $row2 = Database::fetch_array($res2);
                $pub = (int) $row2['visibility'];
                if (!empty($session_id)) {
                    $pub = 'v';
                    // Check exact value in session:
                    /*$sql3 = "SELECT visibility FROM $tbl_tool
                             WHERE
                                c_id = $course_id AND
                                image = 'scormbuilder.gif' AND
                                (   link LIKE '$link'
                                )
                                $toolSessionCondition
                              ";
                    $res3 = Database::query($sql3);
                    if (Database::num_rows($res3)) {
                        $pub = 'v';
                    }*/
                    //$pub = 0 === $pub ? 'i' : 'v';
                }
            }

            // Check if visible.
            $visibility = api_get_item_visibility(
                $courseInfo,
                'learnpath',
                $row->getId(),
                $session_id
            );

            // If option is not true then don't show invisible LP to user
            if ($ignoreLpVisibility === false) {
                if ($showBlockedPrerequisite !== true && !$isAllowToEdit) {
                    $lpVisibility = learnpath::is_lp_visible_for_student(
                        $row->getId(),
                        $user_id,
                        $courseInfo,
                        $session_id,
                        $includeSubscribedLp
                    );
                    if ($lpVisibility === false) {
                        continue;
                    }
                }
            }

            if (!$includeSubscribedLp && $row->getSubscribeUsers() && $isAllowToEdit) {
                $isSubscribedToLp = learnpath::isUserSubscribedToLp(
                    ['subscribe_users' => $row->getSubscribeUsers(), 'id' => $row->getIid()],
                    (int) $this->user_id,
                    $courseInfo,
                    (int) $session_id
                );

                if (!$isSubscribedToLp) {
                    continue;
                }
            }

            $this->list[$row->getIid()] = [
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
                'prerequisite' => $row->getPrerequisite(),
                'category_id' => $row->getCategoryId(),
            ];
            $names[$row->getName()] = $row->getIid();
        }
        asort($names);
        $this->alpha_list = $names;
    }

    /**
     * Gets a table of the different learnpaths we have at the moment.
     *
     * @return array Learnpath info as [lp_id] => ([lp_type]=> ..., [lp_name]=>...,[lp_desc]=>...,[lp_path]=>...)
     */
    public function get_flat_list()
    {
        return $this->list;
    }

    /**
     *  Gets a list of lessons  of the given course_code and session_id
     *  This functions doesn't need user_id.
     *
     *  @param string $course_code Text code of the course
     *  @param int  $session_id Id of session
     *
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

        $lessons = [];
        while ($row = Database::fetch_array($result)) {
            if (api_get_item_visibility($course, 'learnpath', $row['id'], $session_id)) {
                $lessons[$row['id']] = $row;
            }
        }

        return $lessons;
    }
}

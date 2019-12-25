<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
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
        $ignoreLpVisibility = false
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

        $order = ' ORDER BY lp.displayOrder ASC, lp.name ASC';
        if (isset($order_by)) {
            $order = Database::parse_conditions(['order' => $order_by]);
        }

        $repo = Container::getLpRepository();

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
        $courseEntity = api_get_course_entity($course_id);
        $sessionEntity = api_get_session_entity($session_id);
        $shortcutRepository = Container::getShortcutRepository();

        /** @var CLp $lp */
        foreach ($learningPaths as $lp) {
            //$name = Database::escape_string($lp->getName());
            //$link = 'lp/lp_controller.php?action=view&lp_id='.$lp->getId().'&id_session='.$session_id;
            //$oldLink = 'newscorm/lp_controller.php?action=view&lp_id='.$lp->getId().'&id_session='.$session_id;

            /*$sql2 = "SELECT visibility FROM $tbl_tool
                     WHERE
                        c_id = $course_id AND
                        name = '$name' AND
                        image = 'scormbuilder.gif' AND
                        (
                            link LIKE '$link%' OR
                            link LIKE '$oldLink%'
                        )
                      ";
            $res2 = Database::query($sql2);*/
            $pub = 'i';
            $shortcut = $shortcutRepository->getShortcutFromResource($lp);
            if ($shortcut) {
                $pub = 'v';
            }
            /*if (Database::num_rows($res2) > 0) {
                $lp2 = Database::fetch_array($res2);
                $pub = $lp2['visibility'];
            }*/

            // Check if visible.
            /*$visibility = api_get_item_visibility(
                $courseInfo,
                'learnpath',
                $lp->getId(),
                $session_id
            );*/
            $visibility = $lp->isVisible($courseEntity, $sessionEntity);

            // If option is not true then don't show invisible LP to user
            if ($ignoreLpVisibility === false) {
                if ($showBlockedPrerequisite !== true && !$isAllowToEdit) {
                    $lpVisibility = learnpath::is_lp_visible_for_student(
                        $lp,
                        $user_id,
                        $courseInfo
                    );
                    if ($lpVisibility === false) {
                        continue;
                    }
                }
            }

            $this->list[$lp->getIid()] = [
                'lp_type' => $lp->getLpType(),
                'lp_session' => $lp->getSessionId(),
                'lp_name' => stripslashes($lp->getName()),
                'lp_desc' => stripslashes($lp->getDescription()),
                'lp_path' => $lp->getPath(),
                'lp_view_mode' => $lp->getDefaultViewMod(),
                'lp_force_commit' => $lp->getForceCommit(),
                'lp_maker' => stripslashes($lp->getContentMaker()),
                'lp_proximity' => $lp->getContentLocal(),
                'lp_encoding' => api_get_system_encoding(),
                'lp_visibility' => $visibility,
                'lp_published' => $pub,
                'lp_prevent_reinit' => $lp->getPreventReinit(),
                'seriousgame_mode' => $lp->getSeriousgameMode(),
                'lp_scorm_debug' => $lp->getDebug(),
                'lp_display_order' => $lp->getDisplayOrder(),
                'lp_preview_image' => stripslashes($lp->getPreviewImage()),
                'autolaunch' => $lp->getAutolaunch(),
                'session_id' => $lp->getSessionId(),
                'created_on' => $lp->getCreatedOn() ? $lp->getCreatedOn()->format('Y-m-d H:i:s') : null,
                'modified_on' => $lp->getModifiedOn() ? $lp->getModifiedOn()->format('Y-m-d H:i:s') : null,
                'publicated_on' => $lp->getPublicatedOn() ? $lp->getPublicatedOn()->format('Y-m-d H:i:s') : null,
                'expired_on' => $lp->getExpiredOn() ? $lp->getExpiredOn()->format('Y-m-d H:i:s') : null,
                //'category_id'       => $lp['category_id'],
                'subscribe_users' => $lp->getSubscribeUsers(),
                'lp_old_id' => $lp->getId(),
                'iid' => $lp->getIid(),
                'prerequisite' => $lp->getPrerequisite(),
                'entity' => $lp,
            ];
            $names[$lp->getName()] = $lp->getIid();
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

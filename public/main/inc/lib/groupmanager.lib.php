<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CGroupCategory;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

/**
 * This library contains some functions for group-management.
 *
 * @author Bart Mollet
 *
 * @todo Add $course_code parameter to all functions. So this GroupManager can
 * be used outside a session.
 */
class GroupManager
{
    /* DEFAULT_GROUP_CATEGORY:
    When group categories aren't available (platform-setting),
    all groups are created in this 'dummy'-category*/
    public const DEFAULT_GROUP_CATEGORY = 2;

    /**
     * infinite.
     */
    public const INFINITE = 99999;
    /**
     * No limit on the number of users in a group.
     */
    public const MEMBER_PER_GROUP_NO_LIMIT = 0;
    /**
     * No limit on the number of groups per user.
     */
    public const GROUP_PER_MEMBER_NO_LIMIT = 0;
    /**
     * The tools of a group can have 3 states
     * - not available
     * - public
     * - private.
     */
    public const TOOL_NOT_AVAILABLE = 0;
    public const TOOL_PUBLIC = 1;
    public const TOOL_PRIVATE = 2;
    public const TOOL_PRIVATE_BETWEEN_USERS = 3;

    /**
     * Constants for the available group tools.
     */
    public const GROUP_TOOL_FORUM = 0;
    public const GROUP_TOOL_DOCUMENTS = 1;
    public const GROUP_TOOL_CALENDAR = 2;
    public const GROUP_TOOL_ANNOUNCEMENT = 3;
    public const GROUP_TOOL_WORK = 4;
    public const GROUP_TOOL_WIKI = 5;
    public const GROUP_TOOL_CHAT = 6;

    public const DOCUMENT_MODE_SHARE = 0; // By default
    public const DOCUMENT_MODE_READ_ONLY = 1;
    public const DOCUMENT_MODE_COLLABORATION = 2;

    public function __construct()
    {
    }

    /**
     * @param int $courseId
     *
     * @return CGroup[]
     */
    public static function get_groups($courseId = 0)
    {
        $repo = Container::getGroupRepository();
        $course = api_get_course_entity($courseId);
        if (null !== $course) {
            return [];
        }

        $qb = $repo->getResourcesByCourse($course);

        return $qb->getQuery()->getResult();
        /*$table_group = Database::get_course_table(TABLE_GROUP);
        $courseId = !empty($courseId) ? (int) $courseId : api_get_course_int_id();

        $sql = "SELECT * FROM $table_group WHERE c_id = $courseId  ";
        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');*/
    }

    /**
     * Get list of groups for current course.
     *
     * @param int    $categoryId       The id of the category from which the groups are
     *                                 requested
     * @param Course $course           Default is current course
     * @param int    $status           group status
     * @param int    $sessionId
     * @param bool   $getCount
     * @param bool   $notInGroup       Get groups not in a category
     * @param bool   $returnEntityList
     *
     * @return array|CGroup[] an array with all information about the groups
     */
    public static function get_group_list(
        $categoryId = null,
        Course $course = null,
        $status = null,
        $sessionId = 0,
        $getCount = false,
        $filterByKeyword = null,
        $returnEntityList = false
    ) {
        $course = $course ?? api_get_course_entity();

        if (null === $course) {
            return [];
        }

        $sessionId = empty($sessionId) ? api_get_session_id() : (int) $sessionId;
        $repo = Container::getGroupRepository();
        $session = api_get_session_entity($sessionId);
        $qb = $repo->findAllByCourse($course, $session, $filterByKeyword, $status, $categoryId);

        if ($getCount) {
            return $repo->getCount($qb);
        }

        if ($returnEntityList) {
            return $qb->getQuery()->getResult();
        }

        return $qb->getQuery()->getArrayResult();

        /*$table_group = Database::get_course_table(TABLE_GROUP);
        $select = ' g.iid,
                    g.name,
                    g.description,
                    g.category_id,
                    g.max_student maximum_number_of_members,
                    g.secret_directory,
                    g.self_registration_allowed,
                    g.self_unregistration_allowed,
                    g.status
                    ';
        if ($getCount) {
            $select = ' DISTINCT count(g.iid) as count ';
        }

        $sql = "SELECT
                $select
                FROM $table_group g
                WHERE 1 = 1 ";

        if (!is_null($categoryId)) {
            $sql .= " AND g.category_id = '".intval($categoryId)."' ";
            $session_condition = api_get_session_condition($sessionId);
            if (!empty($session_condition)) {
                //$sql .= $session_condition;
            }
        } else {
            $session_condition = api_get_session_condition($sessionId, true);
        }

        $session_condition = '';

        if (!is_null($status)) {
            $sql .= " AND g.status = '".intval($status)."' ";
        }

        //$sql .= " AND g.c_id = $course_id ";
        if ($notInGroup) {
            $sql .= "  AND (g.category_id IS NULL OR g.category_id = 0) ";
        }

        if (!empty($session_condition)) {
            $sql .= $session_condition;
        }
        $sql .= ' ORDER BY UPPER(g.name)';

        $result = Database::query($sql);

        if ($getCount) {
            $row = Database::fetch_array($result);

            return $row['count'];
        }

        $groups = [];
        while ($thisGroup = Database::fetch_array($result)) {
            $thisGroup['number_of_members'] = count(self::get_subscribed_users($thisGroup));
            if (0 != $thisGroup['session_id']) {
                $sql = 'SELECT name FROM '.Database::get_main_table(TABLE_MAIN_SESSION).'
                        WHERE id='.$thisGroup['session_id'];
                $rs_session = Database::query($sql);
                if (Database::num_rows($rs_session) > 0) {
                    $thisGroup['session_name'] = Database::result($rs_session, 0, 0);
                }
            }
            $groups[] = $thisGroup;
        }

        return $groups;*/
    }

    /**
     * Create a group.
     *
     * @param string $name        The name for this group
     * @param int    $category_id
     * @param int    $tutor       The user-id of the group's tutor
     * @param int    $places      How many people can subscribe to the new group
     *
     * @return int
     */
    public static function create_group($name, $category_id, $tutor, $places)
    {
        $_course = api_get_course_info();
        $session_id = api_get_session_id();
        $course_id = $_course['real_id'];
        $category = self::get_category($category_id);
        $places = (int) $places;

        // Default values
        $docState = CGroup::TOOL_PRIVATE;
        $calendarState = CGroup::TOOL_PRIVATE;
        $workState = CGroup::TOOL_PRIVATE;
        $anonuncementState = CGroup::TOOL_PRIVATE;
        $forumState = CGroup::TOOL_PRIVATE;
        $wikiState = CGroup::TOOL_PRIVATE;
        $chatState = CGroup::TOOL_PRIVATE;
        $selfRegAllowed = 0;
        $selfUnregAllwoed = 0;
        $documentAccess = 0;

        if ($category) {
            if (0 == $places) {
                //if the amount of users per group is not filled in, use the setting from the category
                $places = $category['max_student'];
            } else {
                if ($places > $category['max_student'] && 0 != $category['max_student']) {
                    $places = $category['max_student'];
                }
            }
            $docState = $category['doc_state'];
            $calendarState = $category['calendar_state'];
            $workState = $category['work_state'];
            $anonuncementState = $category['announcements_state'];
            $forumState = $category['forum_state'];
            $wikiState = $category['wiki_state'];
            $chatState = $category['chat_state'];
            $selfRegAllowed = $category['self_reg_allowed'];
            $selfUnregAllwoed = $category['self_unreg_allowed'];
            $documentAccess = isset($category['document_access']) ? $category['document_access'] : 0;
        }

        $course = api_get_course_entity($course_id);
        $session = api_get_session_entity($session_id);

        $category = null;
        if (!empty($category_id)) {
            $category = Container::getGroupCategoryRepository()->find($category_id);
        }

        /** @var CGroup $group */
        $group = (new CGroup())
            ->setName($name)
            ->setCategory($category)
            ->setMaxStudent($places)
            ->setDocState($docState)
            ->setCalendarState($calendarState)
            ->setWorkState($workState)
            ->setForumState($forumState)
            ->setWikiState($wikiState)
            ->setAnnouncementsState($anonuncementState)
            ->setChatState($chatState)
            ->setSelfRegistrationAllowed(1 === (int) $selfRegAllowed)
            ->setSelfUnregistrationAllowed(1 === (int) $selfUnregAllwoed)
            ->setDocumentAccess((int) $documentAccess)
            ->setParent($course)
            ->addCourseLink($course, $session)
        ;

        $repo = Container::getGroupRepository();
        $repo->create($group);
        $lastId = $group->getIid();

        if ($lastId) {
            // create a forum if needed
            if ($forumState >= 0) {
                $forumName = get_lang('Group forums');
                $repo = Container::getForumCategoryRepository();
                $category = $repo->findCourseResourceByTitle($forumName, $course->getResourceNode(), $course);
                /*$criteria = ['cId' => $course_id, 'catTitle' => $forumName];
                $category = $repo->findOneBy($criteria);*/

                if (empty($category)) {
                    $categoryId = saveForumCategory(['forum_category_title' => $forumName]);
                } else {
                    $categoryId = $category->getIid();
                }

                $values = [];
                $values['forum_title'] = $name;
                $values['group_id'] = $lastId;
                $values['forum_category'] = $categoryId;
                $values['allow_anonymous_group']['allow_anonymous'] = 0;
                $values['students_can_edit_group']['students_can_edit'] = 0;
                $values['approval_direct_group']['approval_direct'] = 0;
                $values['allow_attachments_group']['allow_attachments'] = 1;
                $values['allow_new_threads_group']['allow_new_threads'] = 1;
                $values['default_view_type_group']['default_view_type'] = api_get_setting('default_forum_view');
                $values['group_forum'] = $lastId;
                if ('1' == $forumState) {
                    $values['public_private_group_forum_group']['public_private_group_forum'] = 'public';
                } elseif ('2' == $forumState) {
                    $values['public_private_group_forum_group']['public_private_group_forum'] = 'private';
                } elseif ('0' == $forumState) {
                    $values['public_private_group_forum_group']['public_private_group_forum'] = 'unavailable';
                }
                store_forum($values);
            }
        }

        return $lastId;
    }

    /**
     * Create subgroups.
     * This function creates new groups based on an existing group. It will
     * create the specified number of groups and fill those groups with users
     * from the base group.
     *
     * @param int $group_id         the group from which subgroups have to be created
     * @param int $number_of_groups The number of groups that have to be created
     */
    public static function create_subgroups($group_id, $number_of_groups)
    {
        $courseId = api_get_course_int_id();
        $table_group = Database::get_course_table(TABLE_GROUP);
        $category_id = self::create_category(
            get_lang('Subgroups'),
            '',
            self::TOOL_PRIVATE,
            self::TOOL_PRIVATE,
            0,
            0,
            1,
            1
        );
        $users = self::get_users($group_id);
        $group_ids = [];

        for ($group_nr = 1; $group_nr <= $number_of_groups; $group_nr++) {
            $group_ids[] = self::create_group(
                get_lang('Subgroup').' '.$group_nr,
                $category_id,
                0,
                0
            );
        }

        $members = [];
        foreach ($users as $index => $user_id) {
            $groupId = $group_ids[$index % $number_of_groups];
            self::subscribeUsers(
                $user_id,
                api_get_group_entity($groupId)
            );
            $members[$group_ids[$groupId]]++;
        }

        foreach ($members as $group_id => $places) {
            $sql = "UPDATE $table_group SET max_student = $places
                    WHERE c_id = $courseId  AND id = $group_id";
            Database::query($sql);
        }
    }

    /**
     * Create a group for every class subscribed to the current course.
     *
     * @param int $categoryId The category in which the groups should be created
     *
     * @return array
     */
    public static function create_class_groups($categoryId)
    {
        $options['where'] = [' usergroup.course_id = ? ' => api_get_course_int_id()];
        $obj = new UserGroupModel();
        $classes = $obj->getUserGroupInCourse($options);
        $group_ids = [];

        foreach ($classes as $class) {
            $userList = $obj->get_users_by_usergroup($class['id']);
            $groupId = self::create_group(
                $class['name'],
                $categoryId,
                0,
                null
            );

            if ($groupId) {
                self::subscribeUsers($userList, api_get_group_entity($groupId));
                $group_ids[] = $groupId;
            }
        }

        return $group_ids;
    }

    /**
     * Deletes groups and their data.
     *
     * @author Christophe Gesche <christophe.gesche@claroline.net>
     * @author Hugues Peeters <hugues.peeters@claroline.net>
     * @author Bart Mollet
     *
     * @param string $course_code Default is current course
     *
     * @return int - number of groups deleted
     */
    public static function deleteGroup(CGroup $group, $course_code = null)
    {
        if (empty($group)) {
            return false;
        }
        $course_info = api_get_course_info($course_code);
        if (empty($course_info)) {
            return false;
        }

        $em = Database::getManager();
        $groupIid = $group->getIid();

        // Unsubscribe all users
        self::unsubscribeAllUsers($groupIid);
        self::unsubscribe_all_tutors($groupIid);

        $em
            ->createQuery(
                'DELETE FROM ChamiloCourseBundle:CForum f WHERE f.forumOfGroup = :group'
            )
            ->execute(['group' => $groupIid]);

        // delete the groups
        $repo = Container::getGroupRepository();
        $em->remove($group);
        $em->flush();

        return true;
    }

    /**
     * @deprecated Should be deleted by the resources.
     *
     * Function needed only when deleting a course, in order to be sure that all group ids are deleted.
     *
     * @param int $courseId
     *
     * @return bool
     */
    public static function deleteAllGroupsFromCourse($courseId)
    {
        $courseId = (int) $courseId;

        if (empty($courseId)) {
            return false;
        }

        $table = Database::get_course_table(TABLE_GROUP_CATEGORY);
        $sql = "SELECT iid FROM $table
                WHERE c_id = $courseId ";
        Database::query($sql);

        // Database table definitions
        $table = Database::get_course_table(TABLE_GROUP_USER);
        $sql = "DELETE FROM $table
                WHERE c_id = $courseId";
        Database::query($sql);

        $table = Database::get_course_table(TABLE_GROUP_TUTOR);
        $sql = "DELETE FROM $table
                WHERE c_id = $courseId";
        Database::query($sql);

        $groupTable = Database::get_course_table(TABLE_GROUP);
        $sql = "DELETE FROM $groupTable
                WHERE c_id = $courseId";
        Database::query($sql);

        return true;
    }

    /**
     * Get group properties.
     *
     * @param int $group_id the group from which properties are requested
     *
     * @return array All properties. Array-keys are:
     *               name, tutor_id, description, maximum_number_of_students,
     *               directory and visibility of tools
     */
    public static function get_group_properties($group_id)
    {
        $group_id = (int) $group_id;

        if (empty($group_id)) {
            return null;
        }

        $table_group = Database::get_course_table(TABLE_GROUP);

        $sql = "SELECT * FROM $table_group
                WHERE iid = ".$group_id;

        $db_result = Database::query($sql);
        $db_object = Database::fetch_object($db_result);

        $result = [];
        if ($db_object) {
            $result['id'] = $db_object->iid;
            $result['iid'] = $db_object->iid;
            $result['name'] = $db_object->name;
            $result['status'] = $db_object->status;
            $result['description'] = $db_object->description;
            $result['maximum_number_of_students'] = $db_object->max_student;
            $result['max_student'] = $db_object->max_student;
            $result['doc_state'] = $db_object->doc_state;
            $result['work_state'] = $db_object->work_state;
            $result['calendar_state'] = $db_object->calendar_state;
            $result['announcements_state'] = $db_object->announcements_state;
            $result['forum_state'] = $db_object->forum_state;
            $result['wiki_state'] = $db_object->wiki_state;
            $result['chat_state'] = $db_object->chat_state;
            $result['self_registration_allowed'] = $db_object->self_registration_allowed;
            $result['self_unregistration_allowed'] = $db_object->self_unregistration_allowed;
            $result['count_users'] = 0;
            /*$result['count_users'] = count(
                self::get_subscribed_users($result)
            );*/
            /*$result['count_tutor'] = count(
                self::get_subscribed_tutors($result)
            );*/
            //$result['count_all'] = $result['count_users'] + $result['count_tutor'];
            $result['count_all'] = null;
            $result['document_access'] = isset($db_object->document_access) ? $db_object->document_access : self::DOCUMENT_MODE_SHARE;
        }

        return $result;
    }

    /**
     * @param string $name
     * @param string $courseCode
     * @param int    $sessionId
     *
     * @return array
     */
    public static function getGroupByName($name, $courseCode = null, $sessionId = 0)
    {
        $name = trim($name);

        if (empty($name)) {
            return [];
        }

        $course_info = api_get_course_info($courseCode);
        $course_id = $course_info['real_id'];
        $name = Database::escape_string($name);
        $sessionId = empty($sessionId) ? api_get_session_id() : (int) $sessionId;
        $sessionCondition = api_get_session_condition($sessionId);

        $table = Database::get_course_table(TABLE_GROUP);
        $sql = "SELECT * FROM $table
                WHERE
                  c_id = $course_id AND
                  name = '$name'
                  $sessionCondition
                LIMIT 1";
        $res = Database::query($sql);
        $group = [];
        if (Database::num_rows($res)) {
            $group = Database::fetch_array($res, 'ASSOC');
        }

        return $group;
    }

    /**
     * Set group properties
     * Changes the group's properties.
     *
     * @param int       Group Id
     * @param string    Group name
     * @param string    Group description
     * @param int       Max number of students in group
     * @param int       Document tool's visibility (0=none,1=private,2=public)
     * @param int       Work tool's visibility (0=none,1=private,2=public)
     * @param int       Calendar tool's visibility (0=none,1=private,2=public)
     * @param int       Announcement tool's visibility (0=none,1=private,2=public)
     * @param int       Forum tool's visibility (0=none,1=private,2=public)
     * @param int       Wiki tool's visibility (0=none,1=private,2=public)
     * @param int       Chat tool's visibility (0=none,1=private,2=public)
     * @param bool Whether self registration is allowed or not
     * @param bool Whether self unregistration is allowed or not
     * @param int $categoryId
     * @param int $documentAccess
     *
     * @return bool TRUE if properties are successfully changed, false otherwise
     */
    public static function set_group_properties(
        $group_id,
        $name,
        $description,
        $maxStudent,
        $docState,
        $workState,
        $calendarState,
        $anonuncementState,
        $forumState,
        $wikiState,
        $chatState,
        $selfRegistrationAllowed,
        $selfUnRegistrationAllowed,
        $categoryId = null,
        $documentAccess = 0
    ) {
        $table_forum = Database::get_course_table(TABLE_FORUM);
        $categoryId = (int) $categoryId;
        $group_id = (int) $group_id;
        $forumState = (int) $forumState;
        $repo = Container::getGroupRepository();

        /** @var CGroup $group */
        $group = $repo->find($group_id);

        $category = null;
        if (!empty($categoryId)) {
            $category = Container::getGroupCategoryRepository()->find($categoryId);
        }

        $group
            ->setName($name)
            ->setCategory($category)
            ->setMaxStudent($maxStudent)
            ->setDocState($docState)
            ->setCalendarState($calendarState)
            ->setWorkState($workState)
            ->setForumState($forumState)
            ->setWikiState($wikiState)
            ->setAnnouncementsState($anonuncementState)
            ->setChatState($chatState)
            ->setSelfRegistrationAllowed($selfRegistrationAllowed)
            ->setSelfUnregistrationAllowed($selfUnRegistrationAllowed)
            ->setDocumentAccess($documentAccess)
        ;

        $repo->update($group);

        /* Here we are updating a field in the table forum_forum that perhaps
        duplicates the table group_info.forum_state cvargas*/
        $sql2 = "UPDATE $table_forum SET ";
        if (1 === $forumState) {
            $sql2 .= " forum_group_public_private='public' ";
        } elseif (2 === $forumState) {
            $sql2 .= " forum_group_public_private='private' ";
        } elseif (0 === $forumState) {
            $sql2 .= " forum_group_public_private='unavailable' ";
        }
        $sql2 .= ' WHERE forum_of_group='.$group_id;
        Database::query($sql2);

        return true;
    }

    /**
     * Get the total number of groups for the current course.
     *
     * @return int the number of groups for the current course
     */
    public static function get_number_of_groups()
    {
        $repo = Container::getGroupRepository();

        $course = api_get_course_entity(api_get_course_int_id());
        if (null === $course) {
            return 0;
        }

        $session = api_get_session_entity(api_get_session_id());
        $group = api_get_group_entity(api_get_group_id());
        $qb = $repo->getResourcesByCourse($course, $session, $group);
        $qb->select('count(resource)');

        return $qb->getQuery()->getSingleScalarResult();
        /*$courseId = api_get_course_int_id();
        $table = Database::get_course_table(TABLE_GROUP);
        $sql = "SELECT COUNT(id) AS number_of_groups
                FROM $table
                WHERE c_id = $courseId ";
        $res = Database::query($sql);
        $obj = Database::fetch_object($res);

        return $obj->number_of_groups;*/
    }

    /**
     * Get all categories.
     */
    public static function get_categories(Course $course = null): array
    {
        $repo = Container::getGroupCategoryRepository();

        if (null === $course) {
            $course = api_get_course_entity(api_get_course_int_id());
        }

        $session = api_get_session_entity(api_get_session_id());
        //$group = api_get_group_entity(api_get_group_id());
        $group = null;

        $qb = $repo->getResourcesByCourse($course, $session, $group);

        return $qb->getQuery()->getArrayResult();
        /*$course_info = api_get_course_info($course_code);
        $courseId = $course_info['real_id'];
        $table = Database::get_course_table(TABLE_GROUP_CATEGORY);
        $sql = "SELECT * FROM $table
                WHERE c_id = $courseId
                ORDER BY display_order";
        $res = Database::query($sql);
        $cats = [];
        while ($cat = Database::fetch_array($res)) {
            $cats[] = $cat;
        }

        return $cats;*/
    }

    /**
     * Get a group category.
     *
     * @param int    $id          The category id
     * @param string $course_code The course (default = current course)
     *
     * @return array
     */
    public static function get_category($id, $course_code = null)
    {
        if (empty($id)) {
            return [];
        }

        $courseInfo = api_get_course_info($course_code);
        $courseId = $courseInfo['real_id'];
        $id = (int) $id;
        $table = Database::get_course_table(TABLE_GROUP_CATEGORY);
        $sql = "SELECT * FROM $table
                WHERE  iid = $id
                LIMIT 1";
        $res = Database::query($sql);

        return Database::fetch_array($res);
    }

    /**
     * Get a group category.
     *
     * @param string $title
     * @param string $course_code The course (default = current course)
     *
     * @return array
     */
    public static function getCategoryByTitle($title, $course_code = null)
    {
        $title = trim($title);

        if (empty($title)) {
            return [];
        }

        $course_info = api_get_course_info($course_code);
        $courseId = $course_info['real_id'];
        $title = Database::escape_string($title);
        $table = Database::get_course_table(TABLE_GROUP_CATEGORY);
        $sql = "SELECT * FROM $table
                WHERE c_id = $courseId AND title = '$title'
                LIMIT 1";
        $res = Database::query($sql);
        $category = [];
        if (Database::num_rows($res)) {
            $category = Database::fetch_array($res, 'ASSOC');
        }

        return $category;
    }

    /**
     * Get the unique category of a given group.
     *
     * @param int    $group_id    The iid of the group
     * @param string $course_code The course in which the group is (default =
     *                            current course)
     *
     * @return array The category
     */
    public static function get_category_from_group($group_id, $course_code = '')
    {
        $table_group = Database::get_course_table(TABLE_GROUP);
        $table_group_cat = Database::get_course_table(TABLE_GROUP_CATEGORY);

        $group_id = (int) $group_id;

        if (empty($group_id)) {
            return [];
        }

        $course_info = api_get_course_info($course_code);

        if (empty($course_info)) {
            return false;
        }

        $sql = "SELECT gc.* FROM $table_group_cat gc
                INNER JOIN $table_group g
                ON (gc.iid = g.category_id)
                WHERE
                    g.iid = $group_id
                LIMIT 1";
        $res = Database::query($sql);
        $cat = [];
        if (Database::num_rows($res)) {
            $cat = Database::fetch_array($res);
        }

        return $cat;
    }

    /**
     * Delete a group category.
     *
     * @param int    $cat_id      The id of the category to delete
     * @param string $course_code The code in which the category should be
     *                            deleted (default = current course)
     *
     * @return bool
     */
    public static function delete_category($cat_id, $course_code = '')
    {
        $course_info = api_get_course_info($course_code);
        if (empty($course_info)) {
            return false;
        }

        $table_group = Database::get_course_table(TABLE_GROUP);
        $cat_id = (int) $cat_id;
        $sql = "SELECT iid FROM $table_group
                WHERE category_id='".$cat_id."'";
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            while ($group = Database::fetch_object($res)) {
                // Delete all groups in category
                /*$groupInfo = self::get_group_properties($group->iid, true);
                self::deleteGroup($groupInfo, $course_code);
                */
                // Set the category to NULL to avoid losing groups in sessions.
                $sql = "UPDATE $table_group SET category_id = NULL WHERE iid = ".$group->iid;
                Database::query($sql);
            }
        }

        $category = Database::getManager()->getRepository(CGroupCategory::class)->find($cat_id);
        if ($category) {
            Database::getManager()->remove($category);
            Database::getManager()->flush();
        }

        /*$sql = "DELETE FROM $table_group_cat
                WHERE iid='".$cat_id."'";
        Database::query($sql);*/

        return true;
    }

    /**
     * Create group category.
     *
     * @param string $title                     The title of the new category
     * @param string $description               The description of the new category
     * @param int    $selfRegistrationAllowed   allow users to self register
     * @param int    $selfUnRegistrationAllowed allow user to self unregister
     * @param int    $documentAccess            document access
     *
     * @return mixed
     */
    public static function create_category(
        $title,
        $description,
        $docState,
        $workState,
        $calendarState,
        $anonuncementState,
        $forumState,
        $wikiState,
        $chatState = 1,
        $selfRegistrationAllowed = 0,
        $selfUnRegistrationAllowed = 0,
        $maxStudent = 8,
        $groupsPerUser = 0,
        $documentAccess = 0
    ) {
        if (empty($title)) {
            return false;
        }
        /*$sql = "SELECT MAX(display_order)+1 as new_order
                FROM $table
                WHERE c_id = $course_id ";
        $res = Database::query($sql);
        $obj = Database::fetch_object($res);
        if (!isset($obj->new_order)) {
            $obj->new_order = 1;
        }*/

        $course = api_get_course_entity(api_get_course_int_id());
        $session = api_get_session_entity(api_get_session_id());

        $category = new CGroupCategory();
        $category
            ->setTitle($title)
            ->setDescription($description)
            ->setMaxStudent($maxStudent)
            ->setDocState($docState)
            ->setCalendarState($calendarState)
            ->setWorkState($workState)
            ->setForumState($forumState)
            ->setWikiState($wikiState)
            ->setAnnouncementsState($anonuncementState)
            ->setChatState($chatState)
            ->setSelfRegAllowed($selfRegistrationAllowed)
            ->setSelfUnregAllowed($selfUnRegistrationAllowed)
            ->setDocumentAccess($documentAccess)
            ->setGroupsPerUser($groupsPerUser)
            ->setParent($course)
            ->addCourseLink($course, $session)
        ;

        $repo = Container::getGroupCategoryRepository();
        $repo->create($category);

        return $category->getIid();
    }

    /**
     * Update group category.
     *
     * @param int    $id
     * @param string $title
     * @param string $description
     * @param $doc_state
     * @param $work_state
     * @param $calendar_state
     * @param $announcements_state
     * @param $forum_state
     * @param $wiki_state
     * @param $chat_state
     * @param $selfRegistrationAllowed
     * @param $selfUnRegistrationAllowed
     * @param $maximum_number_of_students
     * @param $groups_per_user
     * @param $documentAccess
     */
    public static function update_category(
        $id,
        $title,
        $description,
        $doc_state,
        $work_state,
        $calendar_state,
        $announcements_state,
        $forum_state,
        $wiki_state,
        $chat_state,
        $selfRegistrationAllowed,
        $selfUnRegistrationAllowed,
        $maximum_number_of_students,
        $groups_per_user,
        $documentAccess
    ) {
        $table = Database::get_course_table(TABLE_GROUP_CATEGORY);
        $id = (int) $id;

        $allowDocumentAccess = api_get_configuration_value('group_category_document_access');
        $documentCondition = '';
        if ($allowDocumentAccess) {
            $documentAccess = (int) $documentAccess;
            $documentCondition = " document_access = $documentAccess, ";
        }

        $sql = 'UPDATE '.$table." SET
                    title='".Database::escape_string($title)."',
                    description='".Database::escape_string($description)."',
                    doc_state = '".Database::escape_string($doc_state)."',
                    work_state = '".Database::escape_string($work_state)."',
                    calendar_state = '".Database::escape_string($calendar_state)."',
                    announcements_state = '".Database::escape_string($announcements_state)."',
                    forum_state = '".Database::escape_string($forum_state)."',
                    wiki_state = '".Database::escape_string($wiki_state)."',
                    chat_state = '".Database::escape_string($chat_state)."',
                    groups_per_user   = '".Database::escape_string($groups_per_user)."',
                    self_reg_allowed = '".Database::escape_string($selfRegistrationAllowed)."',
                    self_unreg_allowed = '".Database::escape_string($selfUnRegistrationAllowed)."',
                    $documentCondition
                    max_student = ".intval($maximum_number_of_students)."
                WHERE iid = $id";
        Database::query($sql);

        // Updating all groups inside this category
        $groups = self::get_group_list($id);

        if (!empty($groups)) {
            foreach ($groups as $group) {
                self::set_group_properties(
                    $group['iid'],
                    $group['name'],
                    $group['description'],
                    $maximum_number_of_students,
                    $doc_state,
                    $work_state,
                    $calendar_state,
                    $announcements_state,
                    $forum_state,
                    $wiki_state,
                    $chat_state,
                    $selfRegistrationAllowed,
                    $selfUnRegistrationAllowed,
                    $id,
                    $documentAccess
                );
            }
        }
    }

    /**
     * Returns the number of groups of the user with the greatest number of
     * subscriptions in the given category.
     */
    public static function get_current_max_groups_per_user($category_id = null, $course_code = null)
    {
        $course_info = api_get_course_info($course_code);
        $group_table = Database::get_course_table(TABLE_GROUP);

        $group_user_table = Database::get_course_table(TABLE_GROUP_USER);
        $sql = "SELECT COUNT(gu.group_id) AS current_max
                FROM $group_user_table gu
                INNER JOIN $group_table g
                ON (gu.group_id = g.iid)
                WHERE 1= 1 ";
        if (null != $category_id) {
            $category_id = intval($category_id);
            $sql .= ' AND g.category_id = '.$category_id;
        }
        $sql .= ' GROUP BY gu.user_id
                  ORDER BY current_max DESC LIMIT 1';

        $res = Database::query($sql);
        $obj = Database::fetch_object($res);

        if ($obj) {
            return $obj->current_max;
        }

        return 0;
    }

    /**
     * Swaps the display-order of two categories.
     *
     * @param int $id1 The id of the first category
     * @param int $id2 The id of the second category
     */
    public static function swap_category_order($id1, $id2)
    {
        $table = Database::get_course_table(TABLE_GROUP_CATEGORY);
        $id1 = intval($id1);
        $id2 = intval($id2);
        $course_id = api_get_course_int_id();

        $sql = "SELECT id, display_order FROM $table
                WHERE id IN ($id1,$id2) AND c_id = $course_id ";
        $res = Database::query($sql);
        $cat1 = Database::fetch_object($res);
        $cat2 = Database::fetch_object($res);
        if ($cat1 && $cat2) {
            $sql = "UPDATE $table SET display_order=$cat2->display_order
                    WHERE id = $cat1->id AND c_id = $course_id ";
            Database::query($sql);

            $sql = "UPDATE $table SET display_order=$cat1->display_order
                    WHERE id = $cat2->id AND c_id = $course_id ";
            Database::query($sql);
        }
    }

    /**
     * Get all users from a given group.
     *
     * @param int  $group_id        The group
     * @param bool $load_extra_info
     * @param int  $start
     * @param int  $limit
     * @param bool $getCount
     * @param int  $courseId
     * @param $column
     * @param $direction
     *
     * @return array list of user id
     */
    public static function get_users(
        $group_id,
        $load_extra_info = false,
        $start = null,
        $limit = null,
        $getCount = false,
        $courseId = null,
        $column = null,
        $direction = null
    ) {
        $group_user_table = Database::get_course_table(TABLE_GROUP_USER);
        $groupTable = Database::get_course_table(TABLE_GROUP);
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $group_id = (int) $group_id;

        if (empty($courseId)) {
            $courseId = api_get_course_int_id();
        } else {
            $courseId = (int) $courseId;
        }

        $select = ' SELECT u.id, firstname, lastname ';
        if ($getCount) {
            $select = ' SELECT count(u.id) count';
        }
        $sql = "$select
                FROM $group_user_table gu
                INNER JOIN $groupTable g
                ON (gu.group_id = g.iid)
                INNER JOIN $user_table u
                ON (u.id = gu.user_id)
                WHERE
                    g.iid = $group_id";

        if (!empty($column) && !empty($direction)) {
            $column = Database::escape_string($column);
            $direction = ('ASC' === $direction ? 'ASC' : 'DESC');
            $sql .= " ORDER BY `$column` $direction";
        }

        if (!empty($start) && !empty($limit)) {
            $start = (int) $start;
            $limit = (int) $limit;
            $sql .= " LIMIT $start, $limit";
        }
        $res = Database::query($sql);
        $users = [];
        while ($obj = Database::fetch_object($res)) {
            if ($getCount) {
                return $obj->count;
                break;
            }
            if ($load_extra_info) {
                $users[] = api_get_user_info($obj->id);
            } else {
                $users[] = $obj->id;
            }
        }

        return $users;
    }

    /**
     * @param int $group_id id
     *
     * @return array
     */
    public static function getStudentsAndTutors($group_id)
    {
        $group_user_table = Database::get_course_table(TABLE_GROUP_USER);
        $tutor_user_table = Database::get_course_table(TABLE_GROUP_TUTOR);
        $groupTable = Database::get_course_table(TABLE_GROUP);

        $course_id = api_get_course_int_id();
        $group_id = (int) $group_id;

        $sql = "SELECT user_id
                FROM $group_user_table gu
                INNER JOIN $groupTable g
                ON (gu.group_id = g.iid)
                WHERE g.iid = $group_id";
        $res = Database::query($sql);
        $users = [];

        while ($obj = Database::fetch_object($res)) {
            $users[] = api_get_user_info($obj->user_id);
        }

        $sql = "SELECT user_id
                FROM $tutor_user_table gu
                INNER JOIN $groupTable g
                ON (gu.group_id = g.iid)
                WHERE g.iid = $group_id";
        $res = Database::query($sql);
        while ($obj = Database::fetch_object($res)) {
            $users[] = api_get_user_info($obj->user_id);
        }

        return $users;
    }

    /**
     * Get only tutors from a group.
     *
     * @param array $groupInfo
     *
     * @return array
     */
    public static function getTutors($groupInfo)
    {
        $groupTable = Database::get_course_table(TABLE_GROUP);
        $tutor_user_table = Database::get_course_table(TABLE_GROUP_TUTOR);
        $course_id = api_get_course_int_id();
        $group_id = (int) $groupInfo['iid'];

        $sql = "SELECT user_id
                FROM $tutor_user_table gu
                INNER JOIN $groupTable g
                ON (gu.group_id = g.iid)
                WHERE g.iid = $group_id";
        $res = Database::query($sql);

        $users = [];
        while ($obj = Database::fetch_object($res)) {
            $users[] = api_get_user_info($obj->user_id);
        }

        return $users;
    }

    /**
     * Get only students from a group (not tutors).
     *
     * @param bool $filterOnlyActive
     *
     * @return array
     */
    public static function getStudents($groupId, $filterOnlyActive = false)
    {
        $groupId = (int) $groupId;
        $activeCondition = $filterOnlyActive ? 'AND u.active = 1' : '';

        $em = Database::getManager();
        $subscriptions = $em
            ->createQuery("
                SELECT u.id FROM ChamiloCoreBundle:User u
                INNER JOIN ChamiloCourseBundle:CGroupRelUser gu
                WITH u.id = gu.user
                INNER JOIN ChamiloCourseBundle:CGroup g
                WITH gu.group = g.iid
                WHERE g.iid = :group
                    $activeCondition
            ")
            ->setParameters([
                'group' => $groupId,
            ])
            ->getResult();

        $users = [];
        if (!empty($subscriptions)) {
            foreach ($subscriptions as $subscription) {
                $users[] = api_get_user_info($subscription['id']);
            }
        }

        return $users;
    }

    public static function getStudentsCount($groupId, $filterOnlyActive = false)
    {
        $groupId = (int) $groupId;
        $activeCondition = $filterOnlyActive ? 'AND u.active = 1' : '';

        $em = Database::getManager();

        return $em
            ->createQuery("
                SELECT COUNT(u.id) FROM ChamiloCoreBundle:User u
                INNER JOIN ChamiloCourseBundle:CGroupRelUser gu
                WITH u.id = gu.user
                INNER JOIN ChamiloCourseBundle:CGroup g
                WITH gu.group = g.iid
                WHERE g.iid = :group
                    $activeCondition
            ")
            ->setParameters([
                'group' => $groupId,
            ])
            ->getSingleScalarResult();
    }

    /**
     * Returns users belonging to any of the group.
     *
     * @param array $groups list of group ids
     *
     * @return array list of user ids
     */
    public static function get_groups_users($groups = [])
    {
        $result = [];
        $table = Database::get_course_table(TABLE_GROUP_USER);
        $groups = array_map('intval', $groups);
        // protect individual elements with surrounding quotes
        $groups = implode(', ', $groups);
        $sql = "SELECT DISTINCT user_id
                FROM $table gu
                WHERE gu.group_id IN ($groups)";
        $rs = Database::query($sql);
        while ($row = Database::fetch_array($rs)) {
            $result[] = $row['user_id'];
        }

        return $result;
    }

    /**
     * Fill the groups with students.
     * The algorithm takes care to first fill the groups with the least # of users.
     * Analysis
     * There was a problem with the "ALL" setting.
     * When max # of groups is set to all, the value is sometimes NULL and sometimes ALL
     * and in both cased the query does not work as expected.
     * Stupid solution (currently implemented: set ALL to a big number (INFINITE) and things are solved :)
     * Better solution: that's up to you.
     *
     * Note
     * Throughout Dokeos there is some confusion about "course id" and "course code"
     * The code is e.g. TEST101, but sometimes a variable that is called courseID also contains a course code string.
     * However, there is also a integer course_id that uniquely identifies the course.
     * ywarnier:> Now the course_id has been removed (25/1/2005)
     * The databases are als very inconsistent in this.
     *
     * @author Chrisptophe Gesche <christophe.geshe@claroline.net>,
     *         Hugues Peeters     <hugues.peeters@claroline.net> - original version
     * @author Roan Embrechts - virtual course support, code cleaning
     * @author Bart Mollet - code cleaning, use other GroupManager-functions
     *
     * @return bool
     */
    public static function fillGroupWithUsers(CGroup $group)
    {
        $_course = api_get_course_info();
        if (empty($_course) || empty($group)) {
            return false;
        }
        $session_id = api_get_session_id();
        $complete_user_list = CourseManager::get_user_list_from_course_code(
            $_course['code'],
            $session_id
        );
        $groupIid = $group->getIid();
        $category = self::get_category_from_group($groupIid);

        // Getting max numbers of user from group
        $maxNumberStudents = empty($group->getMaxStudent()) ? self::INFINITE : $group->getMaxStudent();
        $groupsPerUser = self::INFINITE;
        $categoryId = 0;
        if ($category) {
            $groupsPerUser = empty($category['groups_per_user']) ? self::INFINITE : $category['groups_per_user'];
            $maxNumberStudentsCategory = empty($category['max_student']) ? self::INFINITE : $category['max_student'];
            $categoryId = $category['iid'];
            if ($maxNumberStudentsCategory < $maxNumberStudents) {
                $maxNumberStudents = $maxNumberStudentsCategory;
            }
        }

        $usersToAdd = [];
        foreach ($complete_user_list as $userInfo) {
            $isSubscribed = self::is_subscribed($userInfo['user_id'], $group);
            if ($isSubscribed) {
                continue;
            }
            $numberOfGroups = self::user_in_number_of_groups(
                $userInfo['user_id'],
                $categoryId
            );
            if ($groupsPerUser > $numberOfGroups) {
                $usersToAdd[] = $userInfo['user_id'];
            }
            if (count($usersToAdd) == $maxNumberStudents) {
                break;
            }
        }

        foreach ($usersToAdd as $userId) {
            self::subscribeUsers($userId, $group);
        }
    }

    /**
     * Get the number of students in a group.
     *
     * @param int $group_id id
     *
     * @return int number of students in the given group
     */
    public static function number_of_students($group_id, $course_id = null)
    {
        $table = Database::get_course_table(TABLE_GROUP_USER);
        $group_id = (int) $group_id;
        $course_id = (int) $course_id;

        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        }
        $sql = "SELECT COUNT(*) AS number_of_students
                FROM $table
                WHERE c_id = $course_id AND group_id = $group_id";
        $result = Database::query($sql);
        $db_object = Database::fetch_object($result);

        return $db_object->number_of_students;
    }

    /**
     * Maximum number of students in a group.
     *
     * @param int $group_id iid
     *
     * @return int maximum number of students in the given group
     */
    public static function maximum_number_of_students($group_id)
    {
        $table = Database::get_course_table(TABLE_GROUP);
        $group_id = (int) $group_id;
        $course_id = api_get_course_int_id();
        $sql = "SELECT max_student FROM $table
                WHERE iid = $group_id";
        $db_result = Database::query($sql);
        $db_object = Database::fetch_object($db_result);
        if (0 == $db_object->max_student) {
            return self::INFINITE;
        }

        return $db_object->max_student;
    }

    /**
     * Number of groups of a user.
     *
     * @param int $user_id
     * @param int $cat_id
     *
     * @return int the number of groups the user is subscribed in
     */
    public static function user_in_number_of_groups($user_id, $cat_id = 0)
    {
        $table_group_user = Database::get_course_table(TABLE_GROUP_USER);
        $table_group = Database::get_course_table(TABLE_GROUP);
        $user_id = (int) $user_id;
        $cat_id = (int) $cat_id;

        $course_id = api_get_course_int_id();
        $cat_condition = '';
        if (!empty($cat_id)) {
            $cat_condition = " AND g.category_id =  $cat_id ";
        }

        $sql = "SELECT COUNT(*) AS number_of_groups
                FROM $table_group_user gu
                INNER JOIN $table_group g
                ON (g.iid = gu.group_id)
                WHERE
                    gu.user_id = $user_id
                    $cat_condition";

        $result = Database::query($sql);
        $db_object = Database::fetch_object($result);

        return $db_object->number_of_groups;
    }

    /**
     * Is sef-registration allowed?
     *
     * @param int $user_id
     *
     * @return bool TRUE if self-registration is allowed in the given group
     */
    public static function is_self_registration_allowed($user_id, CGroup $group)
    {
        if (empty($user_id)) {
            return false;
        }

        if ($group) {
            if (0 == $group->getStatus() || 1 != $group->getSelfRegistrationAllowed()) {
                return false;
            }

            return self::canUserSubscribe($user_id, $group);
        }

        return false;
    }

    /**
     * Is sef-unregistration allowed?
     *
     * @param int $user_id
     *
     * @return bool TRUE if self-unregistration is allowed in the given group
     */
    public static function is_self_unregistration_allowed($user_id, CGroup $group)
    {
        if (empty($user_id) || empty($group)) {
            return false;
        }
        if (0 == $group->getStatus() || 1 != $group->getSelfUnregistrationAllowed()) {
            return false;
        }

        return self::is_subscribed($user_id, $group);
    }

    /**
     * Is user subscribed in group?
     *
     * @param int $user_id
     *
     * @return bool TRUE if given user is subscribed in given group
     */
    public static function is_subscribed($user_id, CGroup $group)
    {
        $course_id = api_get_course_int_id();
        if (empty($user_id) || empty($group) || empty($course_id)) {
            return false;
        }
        $table = Database::get_course_table(TABLE_GROUP_USER);
        $group_id = $group->getIid();
        $user_id = (int) $user_id;

        $sql = "SELECT 1 FROM $table
                WHERE
                    group_id = $group_id AND
                    user_id = $user_id
                ";
        $result = Database::query($sql);

        return Database::num_rows($result) > 0;
    }

    /**
     * Can a user subscribe to a specified group in a course.
     *
     * @param int  $user_id
     * @param bool $checkMaxNumberStudents
     *
     * @return bool true if success
     */
    public static function canUserSubscribe(
        $user_id,
        CGroup $group,
        $checkMaxNumberStudents = true
    ) {
        $groupIid = $group->getIid();
        if ($checkMaxNumberStudents) {
            $category = self::get_category_from_group($group->getIid());
            if ($category) {
                if (self::GROUP_PER_MEMBER_NO_LIMIT == $category['groups_per_user']) {
                    $category['groups_per_user'] = self::INFINITE;
                }
                $result = self::user_in_number_of_groups($user_id, $category['iid']) < $category['groups_per_user'];
                if (false == $result) {
                    return false;
                }
            }

            $result = self::number_of_students($groupIid) < self::maximum_number_of_students($groupIid);

            if (false == $result) {
                return false;
            }
        }

        $result = self::isTutorOfGroup($user_id, $group);

        if ($result) {
            return false;
        }

        $result = self::is_subscribed($user_id, $group);

        if ($result) {
            return false;
        }

        return true;
    }

    /**
     * Get all subscribed users (members) from a group.
     *
     * @return array An array with information of all users from the given group.
     *               (user_id, firstname, lastname, email)
     */
    public static function get_subscribed_users(CGroup $group)
    {
        //$order = api_sort_by_first_name() ? ' ORDER BY u.firstname, u.lastname' : ' ORDER BY u.lastname, u.firstname';
        $order = ['lastname' => Criteria::DESC, 'firstname' => Criteria::ASC];
        if (api_sort_by_first_name()) {
            $order = ['firstname' => Criteria::ASC, 'lastname' => Criteria::ASC];
        }
        $orderListByOfficialCode = api_get_setting('order_user_list_by_official_code');
        if ('true' === $orderListByOfficialCode) {
            //$order = ' ORDER BY u.official_code, u.firstname, u.lastname';
            $order = ['officialCode' => Criteria::ASC, 'firstname' => Criteria::ASC, 'lastname' => Criteria::ASC];
        }

        $users = $group->getMembers();
        $userList = [];
        foreach ($users as $groupRelUser) {
            $userList[] = $groupRelUser->getUser();
        }

        $criteria = Criteria::create();
        $criteria->orderBy($order);
        $users = new ArrayCollection($userList);
        $users = $users->matching($criteria);

        $list = [];
        foreach ($users as $user) {
            $list[$user->getId()] = [
                'user_id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
            ];
        }

        return $list;
    }

    /**
     * Subscribe user(s) to a specified group in current course (as a student).
     *
     * @param mixed  $userList  Can be an array with user-id's or a single user-id
     * @param CGroup $group
     * @param int    $course_id
     *
     * @return bool
     */
    public static function subscribeUsers($userList, CGroup $group = null, $course_id = null)
    {
        if (empty($group)) {
            return false;
        }
        $userList = is_array($userList) ? $userList : [$userList];
        $course_id = empty($course_id) ? api_get_course_int_id() : (int) $course_id;
        $group_id = $group->getIid();

        if (!empty($userList)) {
            $table = Database::get_course_table(TABLE_GROUP_USER);
            foreach ($userList as $user_id) {
                if (self::canUserSubscribe($user_id, $group)) {
                    $user_id = (int) $user_id;
                    $sql = "INSERT INTO $table (c_id, user_id, group_id, status, role)
                            VALUES ('$course_id', '".$user_id."', '".$group_id."', 0, '')";
                    Database::query($sql);
                }
            }
        }

        return true;
    }

    /**
     * Subscribe tutor(s) to a specified group in current course.
     *
     * @param mixed $userList  Can be an array with user-id's or a single user-id
     * @param array $groupInfo
     * @param int   $course_id
     */
    public static function subscribeTutors($userList, CGroup $group = null, $course_id = 0)
    {
        if (empty($group)) {
            return false;
        }
        $userList = is_array($userList) ? $userList : [$userList];
        $result = true;
        $course_id = isset($course_id) && !empty($course_id) ? intval($course_id) : api_get_course_int_id();
        $table_group_tutor = Database::get_course_table(TABLE_GROUP_TUTOR);
        $groupId = (int) $group->getIid();

        foreach ($userList as $user_id) {
            $user_id = intval($user_id);
            if (self::canUserSubscribe($user_id, $group, false)) {
                $sql = 'INSERT INTO '.$table_group_tutor." (c_id, user_id, group_id)
                        VALUES ('$course_id', '".$user_id."', '".$groupId."')";
                $result = Database::query($sql);
            }
        }

        return $result;
    }

    /**
     * Unsubscribe user(s) from a specified group in current course.
     *
     * @param mixed $userList Can be an array with user-id's or a single user-id
     *
     * @return bool
     */
    public static function unsubscribeUsers($userList, CGroup $group = null)
    {
        if (empty($group)) {
            return false;
        }
        $userList = is_array($userList) ? $userList : [$userList];
        $table_group_user = Database::get_course_table(TABLE_GROUP_USER);
        $group_id = $group->getIid();
        $course_id = api_get_course_int_id();
        $sql = 'DELETE FROM '.$table_group_user.'
                WHERE
                    group_id = '.$group_id.' AND
                    user_id IN ('.implode(',', $userList).')
                ';
        Database::query($sql);
    }

    /**
     * Unsubscribe all users from one or more groups.
     *
     * @param int $groupId
     *
     * @return bool
     */
    public static function unsubscribeAllUsers($groupId)
    {
        $groupId = (int) $groupId;
        if (empty($groupId)) {
            return false;
        }

        $table = Database::get_course_table(TABLE_GROUP_USER);
        $sql = "DELETE FROM $table
                WHERE
                    group_id = $groupId ";

        return Database::query($sql);
    }

    /**
     * Unsubscribe all tutors from one or more groups.
     *
     * @param int $groupId iid
     *
     * @see unsubscribe_all_users. This function is almost an exact copy of that function.
     *
     * @return bool TRUE if successful
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     */
    public static function unsubscribe_all_tutors($groupId)
    {
        $courseId = api_get_course_int_id();
        $groupId = (int) $groupId;

        if (empty($courseId) || empty($groupId)) {
            return false;
        }

        if (!empty($groupId)) {
            $table_group_tutor = Database::get_course_table(TABLE_GROUP_TUTOR);
            $sql = "DELETE FROM $table_group_tutor
                    WHERE group_id = $groupId ";
            Database::query($sql);
        }

        return true;
    }

    /**
     * Is the user a tutor of this group?
     *
     * @todo replace this function with $group->isUserTutor
     */
    public static function isTutorOfGroup($userId, CGroup $group = null)
    {
        if (empty($group)) {
            return false;
        }

        $userId = (int) $userId;
        $group_id = (int) $group->getIid();

        $table = Database::get_course_table(TABLE_GROUP_TUTOR);

        $sql = "SELECT * FROM $table
                WHERE
                    user_id = $userId AND
                    group_id = $group_id";
        $result = Database::query($sql);

        return Database::num_rows($result) > 0;
    }

    /**
     * Is the user part of this group? This can be a tutor or a normal member
     * you should use this function if the access to a tool or functionality is
     * restricted to the people who are actually in the group
     * before you had to check if the user was
     * 1. a member of the group OR
     * 2. a tutor of the group. This function combines both.
     */
    public static function isUserInGroup($user_id, CGroup $group)
    {
        $member = self::is_subscribed($user_id, $group);
        if ($member) {
            return true;
        }

        $tutor = self::isTutorOfGroup($user_id, $group);
        if ($tutor) {
            return true;
        }

        return false;
    }

    /**
     * Get all group's from a given course in which a given user is unsubscribed.
     *
     * @author  Patrick Cool
     *
     * @param int $course_id retrieve the groups for
     * @param int $user_id   the ID of the user you want to know all its group memberships
     *
     * @return array
     */
    public static function get_group_ids($course_id, $user_id)
    {
        $groups = [];
        $tbl_group = Database::get_course_table(TABLE_GROUP_USER);
        $tbl_group_tutor = Database::get_course_table(TABLE_GROUP_TUTOR);
        $user_id = intval($user_id);
        $course_id = intval($course_id);

        $sql = "SELECT group_id FROM $tbl_group
                WHERE user_id = '$user_id'";
        $result = Database::query($sql);

        if ($result) {
            while ($row = Database::fetch_array($result)) {
                $groups[] = $row['group_id'];
            }
        }

        //Also loading if i'm the tutor
        $sql = "SELECT group_id FROM $tbl_group_tutor
                WHERE user_id = '$user_id'";
        $result = Database::query($sql);
        if ($result) {
            while ($row = Database::fetch_array($result)) {
                $groups[] = $row['group_id'];
            }
        }
        if (!empty($groups)) {
            array_filter($groups);
        }

        return $groups;
    }

    /**
     * Check if a user has access to a certain group tool.
     *
     * @param int    $user_id The user id
     * @param CGroup $group
     * @param string $tool    The tool to check the access rights. This should be
     *                        one of constants: GROUP_TOOL_DOCUMENTS
     *
     * @return bool true if the given user has access to the given tool in the
     *              given course
     */
    public static function userHasAccess($user_id, CGroup $group = null, $tool)
    {
        if (null === $group) {
            return false;
        }

        // Admin have access everywhere
        if (api_is_platform_admin()) {
            return true;
        }

        // Course admin also have access to everything
        if (api_is_allowed_to_edit(false, true, true)) {
            return true;
        }

        if (0 == $group->getStatus()) {
            return false;
        }

        switch ($tool) {
            case self::GROUP_TOOL_FORUM:
                $toolStatus = $group->getForumState();
                break;
            case self::GROUP_TOOL_DOCUMENTS:
                $toolStatus = $group->getDocState();
                break;
            case self::GROUP_TOOL_CALENDAR:
                $toolStatus = $group->getCalendarState();
                break;
            case self::GROUP_TOOL_ANNOUNCEMENT:
                $toolStatus = $group->getAnnouncementsState();
                break;
            case self::GROUP_TOOL_WORK:
                $toolStatus = $group->getWorkState();
                break;
            case self::GROUP_TOOL_WIKI:
                $toolStatus = $group->getWikiState();
                break;
            case self::GROUP_TOOL_CHAT:
                $toolStatus = $group->getChatState();
                break;
            default:
                return false;
        }

        switch ($toolStatus) {
            case self::TOOL_NOT_AVAILABLE:
                return false;
                break;
            case self::TOOL_PUBLIC:
                return true;
                break;
            case self::TOOL_PRIVATE:
                $userIsInGroup = self::isUserInGroup($user_id, $group);
                if ($userIsInGroup) {
                    return true;
                }
                break;
            case self::TOOL_PRIVATE_BETWEEN_USERS:
                // Only works for announcements for now
                $userIsInGroup = self::isUserInGroup($user_id, $group);
                if ($userIsInGroup && self::GROUP_TOOL_ANNOUNCEMENT == $tool) {
                    return true;
                }
                break;
        }

        return false;
    }

    /**
     * @param int $userId
     * @param int $sessionId
     *
     * @return bool
     */
    public static function userHasAccessToBrowse($userId, CGroup $group = null, $sessionId = 0)
    {
        if (null === $group) {
            return false;
        }

        if (api_is_platform_admin()) {
            return true;
        }

        if (api_is_allowed_to_edit(false, true, true)) {
            return true;
        }

        if (!empty($sessionId)) {
            if (api_is_coach($sessionId, api_get_course_int_id())) {
                return true;
            }

            if (api_is_drh()) {
                if (SessionManager::isUserSubscribedAsHRM($sessionId, $userId)) {
                    return true;
                }
            }
        }

        if (self::isTutorOfGroup($userId, $group)) {
            return true;
        }

        if (0 == $group->getStatus()) {
            return false;
        }

        if (self::userHasAccess($userId, $group, self::GROUP_TOOL_FORUM) ||
            self::userHasAccess($userId, $group, self::GROUP_TOOL_DOCUMENTS) ||
            self::userHasAccess($userId, $group, self::GROUP_TOOL_CALENDAR) ||
            self::userHasAccess($userId, $group, self::GROUP_TOOL_ANNOUNCEMENT) ||
            self::userHasAccess($userId, $group, self::GROUP_TOOL_WORK) ||
            self::userHasAccess($userId, $group, self::GROUP_TOOL_WIKI) ||
            self::userHasAccess($userId, $group, self::GROUP_TOOL_CHAT)
        ) {
            return true;
        }
        //@todo check group session id
        $groupSessionId = null;

        if (api_is_session_general_coach() && $groupSessionId == $sessionId) {
            return true;
        }

        return false;
    }

    /**
     * Get all groups where a specific user is subscribed.
     *
     * @param int $user_id
     *
     * @return array
     */
    public static function get_user_group_name($user_id)
    {
        $table_group_user = Database::get_course_table(TABLE_GROUP_USER);
        $table_group = Database::get_course_table(TABLE_GROUP);
        $user_id = intval($user_id);
        $course_id = api_get_course_int_id();
        $sql = "SELECT name
                FROM $table_group g
                INNER JOIN $table_group_user gu
                ON (gu.group_id = g.iid)
                WHERE
                  gu.user_id = $user_id";
        $res = Database::query($sql);
        $groups = [];
        while ($group = Database::fetch_array($res)) {
            $groups[] .= $group['name'];
        }

        return $groups;
    }

    /**
     * Get all groups where a specific user is subscribed.
     *
     * @param int      $user_id
     * @param int      $courseId
     * @param int|null $sessionId
     *
     * @return array
     */
    public static function getAllGroupPerUserSubscription($user_id, $courseId = 0, $sessionId = null)
    {
        $table_group_user = Database::get_course_table(TABLE_GROUP_USER);
        $table_tutor_user = Database::get_course_table(TABLE_GROUP_TUTOR);
        $table_group = Database::get_course_table(TABLE_GROUP);
        $user_id = (int) $user_id;
        $courseId = empty($courseId) ? api_get_course_int_id() : (int) $courseId;

        $sql = "SELECT DISTINCT g.*
               FROM $table_group g
               LEFT JOIN $table_group_user gu
               ON (gu.group_id = g.iid)
               LEFT JOIN $table_tutor_user tu
               ON (tu.group_id = g.iid)
               WHERE
                  (gu.user_id = $user_id OR tu.user_id = $user_id) ";

        /*if (null !== $sessionId) {
            $sessionId = (int) $sessionId;
            $sql .= " AND g.session_id = $sessionId ";
        }*/

        $res = Database::query($sql);
        $groups = [];
        while ($group = Database::fetch_array($res, 'ASSOC')) {
            $groups[] = $group;
        }

        return $groups;
    }

    /**
     * @param CGroup[] $groupList
     * @param int      $category_id
     *
     * @return string
     */
    public static function processGroups($groupList, $category_id = 0)
    {
        $charset = 'UTF-8';
        $category_id = (int) $category_id;
        $totalRegistered = 0;
        $group_data = [];
        $user_info = api_get_user_info();
        $session_id = api_get_session_id();
        $user_id = $user_info['user_id'];
        $hideGroup = api_get_setting('hide_course_group_if_no_tools_available');
        $extraField = new ExtraField('survey');
        $surveyGroupExists = $extraField->get_handler_field_info_by_field_variable('group_id') ? true : false;
        $url = api_get_path(WEB_CODE_PATH).'group/';

        $confirmMessage = addslashes(
            api_htmlentities(get_lang('Please confirm your choice'), ENT_QUOTES)
        );

        foreach ($groupList as $group) {
            $groupId = $group->getIid();

            // Validation when belongs to a session
            $session_img = '';
            //$session_img = api_get_session_image($group['session_id'], $user_info['status']);

            // All the tutors of this group
            $tutors = $group->getTutors();
            $isMember = self::is_subscribed($user_id, $group);

            // Create a new table-row
            $row = [];
            // Checkbox
            if (api_is_allowed_to_edit(false, true) && count($groupList) > 1) {
                $row[] = $groupId;
            }

            if (self::userHasAccessToBrowse($user_id, $group, $session_id)) {
                // Group name
                $groupNameClass = null;
                if (0 == $group->getStatus()) {
                    $groupNameClass = 'muted';
                }

                $group_name = '<a
                    class="'.$groupNameClass.'"
                    href="group_space.php?'.api_get_cidreq(true, false).'&gid='.$groupId.'">'.
                    Security::remove_XSS($group->getName()).'</a> ';

                $group_name2 = '';
                if (api_get_configuration_value('extra')) {
                    $group_name2 = '<a
                        href="group_space_tracking.php?'.api_get_cidreq(true, false).'&gid='.$groupId.'">'.
                        get_lang('suivi_de').''.stripslashes($group->getName()).'</a>';
                }

                /*
                if (!empty($user_id) && !empty($this_group['id_tutor']) && $user_id == $this_group['id_tutor']) {
                    $group_name .= Display::label(get_lang('my supervision'), 'success');
                } elseif ($isMember) {
                    $group_name .= Display::label(get_lang('my group'), 'success');
                }*/

                /*if (api_is_allowed_to_edit() && !empty($this_group['session_name'])) {
                    $group_name .= ' ('.$this_group['session_name'].')';
                }
                $group_name .= $session_img;*/

                $row[] = $group_name.$group_name2.'<br />'.stripslashes(trim($group->getDescription()));
            } else {
                if ('true' === $hideGroup) {
                    continue;
                }
                $row[] = $group->getName().'<br />'.stripslashes(trim($group->getDescription()));
            }

            // Tutor name
            $tutor_info = '';
            if (count($tutors) > 0) {
                foreach ($tutors as $tutorGroup) {
                    $tutor = $tutorGroup->getUser();
                    $username = api_htmlentities(
                        sprintf(get_lang('Login: %s'), $tutor->getUsername()),
                        ENT_QUOTES
                    );
                    if ('true' === api_get_setting('show_email_addresses')) {
                        $tutor_info .= Display::tag(
                            'span',
                            Display::encrypted_mailto_link(
                                $tutor->getEmail(),
                                UserManager::formatUserFullName($tutor)
                            ),
                            ['title' => $username]
                        ).', ';
                    } else {
                        if (api_is_allowed_to_edit()) {
                            $tutor_info .= Display::tag(
                            'span',
                                Display::encrypted_mailto_link(
                                    $tutor->getEmail(),
                                    UserManager::formatUserFullName($tutor)
                                ),
                                ['title' => $username]
                            ).', ';
                        } else {
                            $tutor_info .= Display::tag(
                                'span',
                                    UserManager::formatUserFullName($tutor),
                                ['title' => $username]
                            ).', ';
                        }
                    }
                }
            }

            $tutor_info = api_substr(
                $tutor_info,
                0,
                api_strlen($tutor_info) - 2
            );
            $row[] = $tutor_info;

            // Max number of members in group
            $max_members = self::MEMBER_PER_GROUP_NO_LIMIT === $group->getMaxStudent() ? ' ' : ' / '.$group->getMaxStudent();
            $registeredUsers = self::getStudentsCount($groupId);
            // Number of members in group
            $row[] = $registeredUsers.$max_members;

            // Self-registration / unregistration
            if (!api_is_allowed_to_edit(false, true)) {
                if (self::is_self_registration_allowed($user_id, $group)) {
                    $row[] = '<a
                        class = "btn btn-default"
                        href="group.php?'.api_get_cidreq().'&category='.$category_id.'&action=self_reg&group_id='.$groupId.'"
                        onclick="javascript:if(!confirm('."'".$confirmMessage."'".')) return false;">'.get_lang('register').'</a>';
                } elseif (self::is_self_unregistration_allowed($user_id, $group)) {
                    $row[] = '<a
                        class = "btn btn-default"
                        href="group.php?'.api_get_cidreq().'&category='.$category_id.'&action=self_unreg&group_id='.$groupId.'"
                        onclick="javascript:if(!confirm('."'".$confirmMessage."'".')) return false;">'.get_lang('unregister').'</a>';
                } else {
                    $row[] = '-';
                }
            }

            // @todo fix group session access.
            $groupSessionId = null;

            // Edit-links
            if (api_is_allowed_to_edit(false, true) &&
                !(api_is_session_general_coach() && $groupSessionId != $session_id)
            ) {
                $edit_actions = '<a
                    href="'.$url.'settings.php?'.api_get_cidreq(true, false).'&gid='.$groupId.'"
                    title="'.get_lang('Edit').'">'.
                    Display::return_icon('edit.png', get_lang('Edit this group'), '', ICON_SIZE_SMALL).
                    '</a>&nbsp;';

                if (1 == $group->getStatus()) {
                    $edit_actions .= '<a
                        href="'.api_get_self().'?'.api_get_cidreq(true, false).'&category='.$category_id.'&action=set_invisible&group_id='.$groupId.'"
                        title="'.get_lang('Hide').'">'.
                        Display::return_icon('visible.png', get_lang('Hide'), '', ICON_SIZE_SMALL).'</a>&nbsp;';
                } else {
                    $edit_actions .= '<a
                        href="'.api_get_self().'?'.api_get_cidreq(true, false).'&category='.$category_id.'&action=set_visible&group_id='.$groupId.'"
                        title="'.get_lang('Show').'">'.
                        Display::return_icon('invisible.png', get_lang('Show'), '', ICON_SIZE_SMALL).'</a>&nbsp;';
                }

                $edit_actions .= '<a
                    href="'.$url.'member_settings.php?'.api_get_cidreq(true, false).'&gid='.$groupId.'"
                    title="'.get_lang('Group members').'">'.
                    Display::return_icon('user.png', get_lang('Group members'), '', ICON_SIZE_SMALL).'</a>&nbsp;';

                $edit_actions .= '<a
                    href="'.$url.'group_overview.php?action=export&type=xls&'.api_get_cidreq(true, false).'&id='.$groupId.'"
                    title="'.get_lang('Export users list').'">'.
                    Display::return_icon('export_excel.png', get_lang('Export'), '', ICON_SIZE_SMALL).'</a>&nbsp;';

                if ($surveyGroupExists) {
                    $edit_actions .= Display::url(
                        Display::return_icon('survey.png', get_lang('ExportSurveyResults'), '', ICON_SIZE_SMALL),
                        $url.'group_overview.php?action=export_surveys&'.api_get_cidreq(true, false).'&id='.$groupId
                    ).'&nbsp;';
                }
                $edit_actions .= '<a
                    href="'.api_get_self().'?'.api_get_cidreq(true, false).'&category='.$category_id.'&action=fill_one&group_id='.$groupId.'"
                    onclick="javascript: if(!confirm('."'".$confirmMessage."'".')) return false;" title="'.get_lang('FillGroup').'">'.
                    Display::return_icon('fill.png', get_lang('FillGroup'), '', ICON_SIZE_SMALL).'</a>&nbsp;';

                $edit_actions .= '<a
                    href="'.api_get_self().'?'.api_get_cidreq(true, false).'&category='.$category_id.'&action=delete_one&group_id='.$groupId.'"
                    onclick="javascript: if(!confirm('."'".$confirmMessage."'".')) return false;" title="'.get_lang('Delete').'">'.
                    Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>&nbsp;';

                $row[] = $edit_actions;
            }

            /*if (!empty($this_group['nbMember'])) {
                $totalRegistered = $totalRegistered + $this_group['nbMember'];
            }*/
            $group_data[] = $row;
        }

        // If no groups then don't show the table (only for students)
        if (!api_is_allowed_to_edit(true, false)) {
            if (empty($group_data)) {
                return '';
            }
        }

        $table = new SortableTableFromArrayConfig(
            $group_data,
            1,
            20,
            'group_category_'.$category_id
        );
        $table->set_additional_parameters(['category' => $category_id]);
        $column = 0;
        if (api_is_allowed_to_edit(false, true) && count($groupList) > 1) {
            $table->set_header($column++, '', false);
        }
        $table->set_header($column++, get_lang('Groups'));
        $table->set_header($column++, get_lang('Group tutor'));
        $table->set_header($column++, get_lang('Registered'), false);

        if (!api_is_allowed_to_edit(false, true)) {
            // If self-registration allowed
            $table->set_header($column++, get_lang('Registration'), false);
        }

        if (api_is_allowed_to_edit(false, true)) {
            // Only for course administrator
            $table->set_header($column++, get_lang('Edit'), false);
            $form_actions = [];
            $form_actions['fill_selected'] = get_lang('Fill the group randomly with course students');
            $form_actions['empty_selected'] = get_lang('unsubscribe all users');
            $form_actions['delete_selected'] = get_lang('Delete');
            if (count($groupList) > 1) {
                $table->set_form_actions($form_actions, 'group');
            }
        }

        return $table->return_table();
    }

    /**
     * @param array $groupData
     * @param bool  $deleteNotInArray
     *
     * @return array
     */
    public static function importCategoriesAndGroupsFromArray($groupData, $deleteNotInArray = false)
    {
        $result = [];
        $elementsFound = [
            'categories' => [],
            'groups' => [],
        ];

        $courseCode = api_get_course_id();
        $sessionId = api_get_session_id();
        $groupCategories = self::get_categories();

        if (empty($groupCategories)) {
            $result['error'][] = get_lang('Create a category');

            return $result;
        }

        foreach ($groupData as $data) {
            $isCategory = empty($data['group']) ? true : false;
            if ($isCategory) {
                $categoryInfo = self::getCategoryByTitle($data['category']);
                $categoryId = $categoryInfo['iid'];

                if (!empty($categoryInfo)) {
                    // Update
                    self::update_category(
                        $categoryId,
                        $data['category'],
                        $data['description'],
                        $data['doc_state'],
                        $data['work_state'],
                        $data['calendar_state'],
                        $data['announcements_state'],
                        $data['forum_state'],
                        $data['wiki_state'],
                        $data['chat_state'],
                        $data['self_reg_allowed'],
                        $data['self_unreg_allowed'],
                        $data['max_student'],
                        $data['groups_per_user'],
                        $data['document_access']
                    );
                    $data['category_id'] = $categoryId;
                    $result['updated']['category'][] = $data;
                } else {
                    // Add
                    $categoryId = self::create_category(
                        $data['category'],
                        $data['description'],
                        $data['doc_state'],
                        $data['work_state'],
                        $data['calendar_state'],
                        $data['announcements_state'],
                        $data['forum_state'],
                        $data['wiki_state'],
                        $data['chat_state'],
                        $data['self_reg_allowed'],
                        $data['self_unreg_allowed'],
                        $data['max_student'],
                        $data['groups_per_user']
                    );

                    if ($categoryId) {
                        $data['category_id'] = $categoryId;
                        $result['added']['category'][] = $data;
                    }
                }
                $elementsFound['categories'][] = $categoryId;
            } else {
                $groupInfo = self::getGroupByName($data['group']);
                $categoryInfo = [];
                if (isset($data['category'])) {
                    $categoryInfo = self::getCategoryByTitle($data['category']);
                }
                $categoryId = null;
                if (!empty($categoryInfo)) {
                    $categoryId = $categoryInfo['iid'];
                } else {
                    if (!empty($groupCategories) && isset($groupCategories[0])) {
                        $defaultGroupCategory = $groupCategories[0];
                        $categoryId = $defaultGroupCategory['iid'];
                    }
                }

                if (empty($groupInfo)) {
                    // Add
                    $groupId = self::create_group(
                        $data['group'],
                        $categoryId,
                        null,
                        $data['max_student']
                    );

                    if ($groupId) {
                        self::set_group_properties(
                            $groupId,
                            $data['group'],
                            $data['description'],
                            $data['max_student'],
                            $data['doc_state'],
                            $data['work_state'],
                            $data['calendar_state'],
                            $data['announcements_state'],
                            $data['forum_state'],
                            $data['wiki_state'],
                            $data['chat_state'],
                            $data['self_reg_allowed'],
                            $data['self_unreg_allowed'],
                            $categoryId
                        );
                        $data['group_id'] = $groupId;
                        $result['added']['group'][] = $data;
                    }
                    $groupInfo = self::get_group_properties($groupId, true);
                } else {
                    // Update
                    $groupId = $groupInfo['iid'];
                    self::set_group_properties(
                        $groupId,
                        $data['group'],
                        $data['description'],
                        $data['max_student'],
                        $data['doc_state'],
                        $data['work_state'],
                        $data['calendar_state'],
                        $data['announcements_state'],
                        $data['forum_state'],
                        $data['wiki_state'],
                        $data['chat_state'],
                        $data['self_reg_allowed'],
                        $data['self_unreg_allowed'],
                        $categoryId
                    );

                    $data['group_id'] = $groupId;
                    $result['updated']['group'][] = $data;
                    $groupInfo = self::get_group_properties($groupId);
                }

                $students = isset($data['students']) ? explode(',', $data['students']) : [];
                if (!empty($students)) {
                    $studentUserIdList = [];
                    foreach ($students as $student) {
                        $userInfo = api_get_user_info_from_username($student);

                        if (!$userInfo) {
                            continue;
                        }

                        if (!CourseManager::is_user_subscribed_in_course(
                                $userInfo['user_id'],
                                $courseCode,
                                !empty($sessionId),
                                $sessionId
                            )
                        ) {
                            Display::addFlash(
                                Display::return_message(
                                    sprintf(
                                        get_lang('Student %s is no subscribed to this course'),
                                        $userInfo['complete_name']
                                    ),
                                    'warning'
                                )
                            );
                            continue;
                        }

                        $studentUserIdList[] = $userInfo['user_id'];
                    }
                    self::subscribeUsers($studentUserIdList, api_get_group_entity($groupInfo['iid']));
                }

                $tutors = isset($data['tutors']) ? explode(',', $data['tutors']) : [];
                if (!empty($tutors)) {
                    $tutorIdList = [];
                    foreach ($tutors as $tutor) {
                        $userInfo = api_get_user_info_from_username($tutor);

                        if (!$userInfo) {
                            continue;
                        }

                        if (!CourseManager::is_user_subscribed_in_course(
                                $userInfo['user_id'],
                                $courseCode,
                                !empty($sessionId),
                                $sessionId
                            )
                        ) {
                            Display::addFlash(
                                Display::return_message(
                                    sprintf(get_lang('Tutor %s is no subscribed to this course'), $userInfo['complete_name']),
                                    'warning'
                                )
                            );

                            continue;
                        }

                        $tutorIdList[] = $userInfo['user_id'];
                    }
                    self::subscribeTutors($tutorIdList, api_get_group_entity($groupInfo['iid']));
                }

                $elementsFound['groups'][] = $groupId;
            }
        }

        if ($deleteNotInArray) {
            // Check categories
            $categories = self::get_categories();
            foreach ($categories as $category) {
                if (!in_array($category['iid'], $elementsFound['categories'])) {
                    self::delete_category($category['iid']);
                    $category['category'] = $category['title'];
                    $result['deleted']['category'][] = $category;
                }
            }

            $groups = self::get_groups();
            foreach ($groups as $group) {
                if (!in_array($group->getIid(), $elementsFound['groups'])) {
                    self::deleteGroup($group);
                    $result['deleted']['group'][] = $group;
                }
            }
        }

        return $result;
    }

    /**
     * Export all categories/group from a course to an array.
     * This function works only in a context of a course.
     *
     * @param int  $groupId
     * @param bool $loadUsers
     *
     * @return array
     */
    public static function exportCategoriesAndGroupsToArray($groupId = null, $loadUsers = false)
    {
        $data = [];
        $data[] = [
            'category',
            'group',
            'description',
            'announcements_state',
            'calendar_state',
            'chat_state',
            'doc_state',
            'forum_state',
            'work_state',
            'wiki_state',
            'max_student',
            'self_reg_allowed',
            'self_unreg_allowed',
            'groups_per_user',
        ];

        $count = 1;

        if ($loadUsers) {
            $data[0][] = 'students';
            $data[0][] = 'tutors';
        }

        if (false == $loadUsers) {
            $categories = self::get_categories();

            foreach ($categories as $categoryInfo) {
                $data[$count] = [
                    $categoryInfo['title'],
                    null,
                    $categoryInfo['description'],
                    $categoryInfo['announcements_state'],
                    $categoryInfo['calendar_state'],
                    $categoryInfo['chat_state'],
                    $categoryInfo['doc_state'],
                    $categoryInfo['forum_state'],
                    $categoryInfo['work_state'],
                    $categoryInfo['wiki_state'],
                    $categoryInfo['max_student'],
                    $categoryInfo['self_reg_allowed'],
                    $categoryInfo['self_unreg_allowed'],
                    $categoryInfo['groups_per_user'],
                ];
                $count++;
            }
        }

        $groups = self::get_group_list();
        foreach ($groups as $groupInfo) {
            $groupId = $groupInfo['iid'];
            $categoryTitle = null;
            $categoryInfo = [];
            if (isset($groupInfo['category'])) {
                $categoryInfo = self::get_category($groupInfo['category']);
            }

            $groupSettings = self::get_group_properties($groupId);
            if (!empty($categoryInfo)) {
                $categoryTitle = $categoryInfo['title'];
            }

            $users = self::getStudents($groupId);
            $userList = [];
            foreach ($users as $user) {
                $user = api_get_user_info($user['user_id']);
                $userList[] = $user['username'];
            }

            $tutors = self::getTutors($groupInfo);
            $tutorList = [];
            foreach ($tutors as $user) {
                $user = api_get_user_info($user['user_id']);
                $tutorList[] = $user['username'];
            }

            $userListToString = null;
            if (!empty($userList)) {
                $userListToString = implode(',', $userList);
            }

            $tutorListToString = null;
            if (!empty($tutorList)) {
                $tutorListToString = implode(',', $tutorList);
            }

            $data[$count] = [
                $categoryTitle,
                $groupSettings['name'],
                $groupSettings['description'],
                $groupSettings['announcements_state'],
                $groupSettings['calendar_state'],
                $groupSettings['chat_state'],
                $groupSettings['doc_state'],
                $groupSettings['forum_state'],
                $groupSettings['work_state'],
                $groupSettings['wiki_state'],
                $groupSettings['maximum_number_of_students'],
                $groupSettings['self_registration_allowed'],
                $groupSettings['self_unregistration_allowed'],
                null,
            ];

            if ($loadUsers) {
                $data[$count][] = $userListToString;
                $data[$count][] = $tutorListToString;
            }

            if (!empty($groupId)) {
                if ($groupId == $groupInfo['iid']) {
                    break;
                }
            }
            $count++;
        }

        return $data;
    }

    /**
     * @param string $default
     */
    public static function getSettingBar($default)
    {
        $activeSettings = null;
        $activeTutor = null;
        $activeMember = null;

        switch ($default) {
            case 'settings':
                $activeSettings = 'active';
                break;
            case 'tutor':
                $activeTutor = 'active';
                break;
            case 'member':
                $activeMember = 'active';
                break;
        }

        $url = api_get_path(WEB_CODE_PATH).'group/%s?'.api_get_cidreq();
        $items = [
            '<a class="nav-link '.$activeSettings.'"
                id="group_settings_tab" href="'.sprintf($url, 'settings.php').'">
                '.Display::return_icon('settings.png').' '.get_lang('Settings').'
            </a>'.
            '<a class="nav-link '.$activeMember.'"
                    id="group_members_tab" href="'.sprintf($url, 'member_settings.php').'">
                    '.Display::return_icon('user.png').' '.get_lang('Group members').
            '</a>'.
            '<a class="nav-link  '.$activeTutor.'"
                    id="group_tutors_tab" href="'.sprintf($url, 'tutor_settings.php').'">
                    '.Display::return_icon('teacher.png').' '.get_lang('Group tutors').'
            </a>',
        ];

        echo Display::toolbarAction('group', $items);
    }

    public static function groupOverview($group, $url)
    {
        $groupId = $group['iid'];
        $content = '<li>';
        $content .= Display::tag(
            'h3',
            Display::url(
                Security::remove_XSS($group['name']),
                $url.'&gid='.$groupId
            )
        );
        $users = self::getTutors($group);
        if (!empty($users)) {
            $content .= '<ul>';
            $content .= "<li>".Display::tag('h4', get_lang('Tutors'))."</li><ul>";
            foreach ($users as $user) {
                $userInfo = api_get_user_info($user['user_id']);
                $content .= '<li title="'.$userInfo['username'].'">'.
                    $userInfo['complete_name_with_username'].
                    '</li>';
            }
            $content .= '</ul>';
            $content .= '</ul>';
        }

        $users = self::getStudents($group['iid']);
        if (!empty($users)) {
            $content .= '<ul>';
            $content .= "<li>".Display::tag('h4', get_lang('Students'))."</li><ul>";
            foreach ($users as $user) {
                $userInfo = api_get_user_info($user['user_id']);
                $content .= '<li title="'.$userInfo['username'].'">'.
                    $userInfo['complete_name_with_username'].
                    '</li>';
            }
            $content .= '</ul>';
            $content .= '</ul>';
        }
        $content .= '</li>';

        return $content;
    }

    public static function getOverview(Course $course, string $keyword = ''): string
    {
        $content = '';
        $categories = self::get_categories();
        $url = api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(true, false);
        if (!empty($categories)) {
            foreach ($categories as $category) {
                if ('true' === api_get_setting('allow_group_categories')) {
                    $content .= '<h2>'.$category['title'].'</h2>';
                }
                $groups = self::get_group_list($category['iid'], $course, null, 0, false, $keyword);
                $content .= '<ul>';
                if (!empty($groups)) {
                    foreach ($groups as $group) {
                        $content .= self::groupOverview($group, $url);
                    }
                }
                $content .= '</ul>';
            }
        }

        // Check groups with no categories.
        $groups = self::get_group_list(null, $course, null, api_get_session_id(), false, true);
        if (!empty($groups)) {
            $content .= '<h2>'.get_lang('NoCategorySelected').'</h2>';
            $content .= '<ul>';
            foreach ($groups as $group) {
                if (!empty($group['category_id'])) {
                    continue;
                }
                $content .= self::groupOverview($group, $url);
            }
            $content .= '</ul>';
        }

        return $content;
    }

    public static function getSearchForm(): string
    {
        $url = api_get_path(WEB_CODE_PATH).'group/group_overview.php?'.api_get_cidreq();
        $form = new FormValidator(
            'search_groups',
            'get',
            $url,
            null,
            ['class' => 'form-search'],
            FormValidator::LAYOUT_INLINE
        );
        $form->addElement('text', 'keyword');
        $form->addCourseHiddenParams();
        $form->addButtonSearch();

        return $form->toHtml();
    }

    public static function setStatus(CGroup $group, $status)
    {
        $group->setStatus($status);
        $em = Database::getManager();
        $em->persist($group);
        $em->flush();
    }

    public static function setVisible(CGroup $group)
    {
        self::setStatus($group, true);
    }

    public static function setInvisible(CGroup $group)
    {
        self::setStatus($group, false);
    }

    /**
     * @param int   $userId
     * @param int   $courseId
     * @param array $documentInfoToBeCheck
     * @param bool  $blockPage
     *
     * @return bool
     */
    public static function allowUploadEditDocument(
        $userId,
        $courseId,
        CGroup $group,
        $documentInfoToBeCheck = null,
        $blockPage = false
    ) {
        // Admin and teachers can make any change no matter what
        if (api_is_platform_admin() || api_is_allowed_to_edit(false, true)) {
            return true;
        }

        if (empty($group)) {
            if ($blockPage) {
                api_not_allowed(true);
            }

            return false;
        }

        // Tutor can also make any change
        $isTutor = self::isTutorOfGroup($userId, $group);

        if ($isTutor) {
            return true;
        }

        // Just in case also check if document in group is available
        if (0 == $group->getDocState()) {
            if ($blockPage) {
                api_not_allowed(true);
            }

            return false;
        }

        // Default behaviour
        $documentAccess = self::DOCUMENT_MODE_SHARE;

        // Check category document access
        /*$allowCategoryGroupDocumentAccess = api_get_configuration_value('group_category_document_access');
        if ($allowCategoryGroupDocumentAccess) {
            $category = GroupManager::get_category_from_group($groupInfo['iid']);
            if (!empty($category) && isset($category['document_access'])) {
                $documentAccess = (int) $category['document_access'];
            }
        }*/

        // Check group document access
        $allow = api_get_configuration_value('group_document_access');
        if ($allow) {
            $documentAccess = $group->getDocumentAccess();
        }

        // Check access for students
        $result = false;
        switch ($documentAccess) {
            case self::DOCUMENT_MODE_SHARE:
                // Default chamilo behaviour
                // Student can upload his own content, cannot modify another content.
                $isMember = self::is_subscribed($userId, $group);
                if ($isMember) {
                    // No document to check, allow access to document feature.
                    if (empty($documentInfoToBeCheck)) {
                        $result = true;
                    } else {
                        // Member can only edit his own document
                        $authorId = isset($documentInfoToBeCheck['insert_user_id']) ? $documentInfoToBeCheck['insert_user_id'] : 0;
                        // If "insert_user_id" is not set, check the author id from c_item_property
                        if (empty($authorId) && isset($documentInfoToBeCheck['id'])) {
                            // @todo use resources
                            /*$documentInfo = api_get_item_property_info(
                                $courseId,
                                'document',
                                $documentInfoToBeCheck['id'],
                                0
                            );
                            // Try to find this document in the session
                            if (!empty($sessionId)) {
                                $documentInfo = api_get_item_property_info(
                                    $courseId,
                                    'document',
                                    $documentInfoToBeCheck['id'],
                                    api_get_session_id()
                                );
                            }

                            if (!empty($documentInfo) && isset($documentInfo['insert_user_id'])) {
                                $authorId = $documentInfo['insert_user_id'];
                            }*/
                        }

                        if ($authorId == $userId) {
                            $result = true;
                        }
                    }
                }
                break;
            case self::DOCUMENT_MODE_READ_ONLY:
                // Student cannot upload content, cannot modify another content.
                $result = false;
                break;
            case self::DOCUMENT_MODE_COLLABORATION:
                // Student can upload content, can modify another content.
                $isMember = self::is_subscribed($userId, $group);
                if ($isMember) {
                    $result = true;
                }
                break;
        }

        if ($blockPage && false == $result) {
            api_not_allowed(true);
        }

        return $result;
    }
}

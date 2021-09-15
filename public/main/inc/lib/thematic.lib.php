<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Entity\CThematicAdvance;
use Chamilo\CourseBundle\Entity\CThematicPlan;

/**
 * Provides functions for thematic option inside attendance tool.
 * It's also used like model to thematic_controller (MVC pattern)
 * Thematic class can be used to instanciate objects or as a library for thematic control.
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 * @author Julio Montoya <gugli100@gmail.com> SQL fixes
 */
class Thematic
{
    private $session_id;
    private $thematic_id;
    private $thematic_title;
    private $thematic_content;
    private $thematic_plan_id;
    private $thematic_plan_title;
    private $thematic_plan_description;
    private $thematic_plan_description_type;
    private $thematic_advance_id;
    private $attendance_id;
    private $thematic_advance_content;
    private $start_date;
    private $duration;
    private $course_int_id;

    public function __construct()
    {
        $this->course_int_id = api_get_course_int_id();
    }

    /**
     * Get the total number of thematic inside current course and current session.
     *
     * @see SortableTable#get_total_number_of_items()
     */
    public function get_number_of_thematics()
    {
        $tbl_thematic = Database::get_course_table(TABLE_THEMATIC);
        $condition_session = '';
        if (!api_get_session_id()) {
            $condition_session = api_get_session_condition(0);
        }
        $course_id = api_get_course_int_id();
        $sql = "SELECT COUNT(id) AS total_number_of_items
                FROM $tbl_thematic
                WHERE c_id = $course_id AND active = 1 $condition_session ";
        $res = Database::query($sql);
        $obj = Database::fetch_object($res);

        return $obj->total_number_of_items;
    }

    /**
     * Get the thematics to display on the current page (fill the sortable-table).
     *
     * @param   int     offset of first user to recover
     * @param   int     Number of users to get
     * @param   int     Column to sort on
     * @param   string  Order (ASC,DESC)
     *
     * @return array
     *
     * @see SortableTable#get_table_data($from)
     */
    public function get_thematic_data($from, $number_of_items, $column, $direction)
    {
        $column = (int) $column;
        $from = (int) $from;
        $number_of_items = (int) $number_of_items;

        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }
        /*
        $course = api_get_course_entity();
        $session = api_get_session_entity();

        $sql = "SELECT id AS col0, title AS col1, display_order AS col2, session_id
                FROM $tbl_thematic
                WHERE c_id = $course_id AND active = 1 $condition_session
                ORDER BY col2
                LIMIT $from,$number_of_items ";
        $res = Database::query($sql);

        $thematics = [];
        $user_info = api_get_user_info(api_get_user_id());
        while ($thematic = Database::fetch_row($res)) {
            $session_star = '';
            if (api_get_session_id() == $thematic[3]) {
                $session_star = api_get_session_image(api_get_session_id(), $user_info['status']);
            }
            $thematic[1] = '<a href="index.php?'.api_get_cidreq().'&action=thematic_details&thematic_id='.$thematic[0].'">'.
                Security::remove_XSS($thematic[1], STUDENT).$session_star.'</a>';
            if (api_is_allowed_to_edit(null, true)) {
                $actions = '';

                if (api_get_session_id()) {
                    if (api_get_session_id() == $thematic[3]) {
                        $actions .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_plan_list&thematic_id='.$thematic[0].'">'.
                            Display::return_icon('lesson_plan.png', get_lang('Thematic plan'), '', ICON_SIZE_SMALL).'</a>&nbsp;';
                        $actions .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_advance_list&thematic_id='.$thematic[0].'">'.
                            Display::return_icon('lesson_plan_calendar.png', get_lang('Thematic advance'), '', ICON_SIZE_SMALL).'</a>&nbsp;';

                        $actions .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_edit&thematic_id='.$thematic[0].'">'.
                            Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>';
                        $actions .= '<a onclick="javascript:if(!confirm(\''.get_lang('Are you sure you want to delete').'\')) return false;" href="index.php?'.api_get_cidreq().'&action=thematic_delete&thematic_id='.$thematic[0].'">'.
                            Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>';
                    } else {
                        $actions .= Display::return_icon(
                            'lesson_plan_na.png',
                            get_lang('Thematic plan'),
                            '',
                            ICON_SIZE_SMALL
                        ).'&nbsp;';
                        $actions .= Display::return_icon(
                            'lesson_plan_calendar_na.png',
                            get_lang('Thematic advance'),
                            '',
                            ICON_SIZE_SMALL
                        ).'&nbsp;';
                        $actions .= Display::return_icon('edit_na.png', get_lang('Edit'), '', ICON_SIZE_SMALL);
                        $actions .= Display::return_icon(
                            'delete_na.png',
                            get_lang('Delete'),
                            '',
                            ICON_SIZE_SMALL
                        ).'&nbsp;';
                        $actions .= Display::url(
                            Display::return_icon('cd.gif', get_lang('Copy')),
                            'index.php?'.api_get_cidreq().'&action=thematic_copy&thematic_id='.$thematic[0]
                        );
                    }
                } else {
                    $actions .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_plan_list&thematic_id='.$thematic[0].'">'.
                        Display::return_icon('lesson_plan.png', get_lang('Thematic plan'), '', ICON_SIZE_SMALL).'</a>&nbsp;';
                    $actions .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_advance_list&thematic_id='.$thematic[0].'">'.
                        Display::return_icon('lesson_plan_calendar.png', get_lang('Thematic advance'), '', ICON_SIZE_SMALL).'</a>&nbsp;';

                    if ($thematic[2] > 1) {
                        $actions .= '<a href="'.api_get_self().'?action=moveup&'.api_get_cidreq().'&thematic_id='.$thematic[0].'">'.
                            Display::return_icon('up.png', get_lang('Up'), '', ICON_SIZE_SMALL).'</a>';
                    } else {
                        $actions .= Display::return_icon('up_na.png', '&nbsp;', '', ICON_SIZE_SMALL);
                    }
                    /*if ($thematic[2] < self::get_max_thematic_item()) {
                        $actions .= '<a href="'.api_get_self().'?action=movedown&a'.api_get_cidreq().'&thematic_id='.$thematic[0].'">'.
                            Display::return_icon('down.png', get_lang('down'), '', ICON_SIZE_SMALL).'</a>';
                    } else {
                        $actions .= Display::return_icon('down_na.png', '&nbsp;', '', ICON_SIZE_SMALL);
                    }
                    $actions .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_edit&thematic_id='.$thematic[0].'">'.
                        Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>';
                    $actions .= '<a onclick="javascript:if(!confirm(\''.get_lang('Are you sure you want to delete').'\')) return false;" href="index.php?'.api_get_cidreq().'&action=thematic_delete&thematic_id='.$thematic[0].'">'.
                        Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>';
                }
                $thematics[] = [$thematic[0], $thematic[1], $actions];
            }
        }*/

        //return $thematics;
    }

    /**
     * Get the maximum display order of the thematic item.
     *
     * @return int Maximum display order
     */
    public function get_max_thematic_item(Course $course, Session $session = null)
    {
        // Database table definition
        /*$tbl_thematic = Database::get_course_table(TABLE_THEMATIC);
        $session_id = api_get_session_id();
        if ($use_session) {
            $condition_session = api_get_session_condition($session_id);
        } else {
            $condition_session = '';
        }
        $course_id = api_get_course_int_id();
        $sql = "SELECT MAX(display_order)
                FROM $tbl_thematic
                WHERE c_id = $course_id AND active = 1 $condition_session";
        $rs = Database::query($sql);
        $row = Database::fetch_array($rs);

        return $row[0];*/
    }

    /**
     * Move a thematic.
     *
     * @param string $direction   (up, down)
     * @param int    $thematic_id
     */
    public function moveThematic($direction, $thematic_id, $course, $session = null)
    {
        // Database table definition
        $tbl_thematic = Database::get_course_table(TABLE_THEMATIC);

        // sort direction
        if ('up' === $direction) {
            $sortorder = 'DESC';
        } else {
            $sortorder = 'ASC';
        }
        $course_id = api_get_course_int_id();
        $session_id = api_get_session_id();
        $condition_session = api_get_session_condition($session_id);

        $sql = "SELECT id, display_order
                FROM $tbl_thematic
                WHERE c_id = $course_id AND active = 1 $condition_session
                ORDER BY display_order $sortorder";
        $res = Database::query($sql);
        $found = false;

        // Variable definition
        $current_id = 0;
        $next_id = 0;
        while ($row = Database::fetch_array($res)) {
            if ($found && empty($next_id)) {
                $next_id = intval($row['id']);
                $next_display_order = intval($row['display_order']);
            }

            if ($row['id'] == $thematic_id) {
                $current_id = intval($thematic_id);
                $current_display_order = intval($row['display_order']);
                $found = true;
            }
        }

        // get last done thematic advance before move thematic list
        $last_done_thematic_advance = $this->get_last_done_thematic_advance($course, $session);

        if (!empty($next_display_order) && !empty($current_id)) {
            $sql = "UPDATE $tbl_thematic SET display_order = $next_display_order
                    WHERE c_id = $course_id AND id = $current_id ";
            Database::query($sql);
        }
        if (!empty($current_display_order) && !empty($next_id)) {
            $sql = "UPDATE $tbl_thematic SET
                    display_order = $current_display_order
                    WHERE c_id = $course_id AND id = $next_id ";
            Database::query($sql);
        }

        // update done advances with de current thematic list
        $this->updateDoneThematicAdvance($last_done_thematic_advance, $course, $session);
    }

    /**
     * Get thematic list.
     *
     * @return CThematic[]
     */
    public static function getThematicList(Course $course, Session $session = null)
    {
        // set current course and session
        /*$tbl_thematic = Database::get_course_table(TABLE_THEMATIC);
        $course_info = api_get_course_info($course_code);
        $course_id = $course_info['real_id'];

        if (!empty($session_id)) {
            $session_id = (int) $session_id;
        } else {
            $session_id = api_get_session_id();
        }

        $data = [];
        if (empty($session_id)) {
            $condition_session = api_get_session_condition(0);
        } else {
            $condition_session = api_get_session_condition($session_id, true, true);
        }
        $condition = " WHERE active = 1 $condition_session ";*/

        $repo = Container::getThematicRepository();
        $qb = $repo->getResourcesByCourse($course, $session);
        $qb->andWhere('resource.active = 1');

        return $qb->getQuery()->getResult();

        /*$sql = "SELECT *
                FROM $tbl_thematic $condition AND c_id = $course_id
                ORDER BY display_order ";

        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res, 'ASSOC')) {
                $entity = $repo->find($row['iid']);
                $data[$row['iid']] = $entity;
            }
        }

        return $data;*/
    }

    /**
     * Insert or update a thematic.
     *
     * @return CThematic
     */
    public function thematicSave($id, $title, $content, Course $course, Session $session = null)
    {
        $id = (int) $id;

        // get the maximum display order of all the glossary items
        //$max_thematic_item = $this->get_max_thematic_item(false);
        $repo = Container::getThematicRepository();
        if (empty($id)) {
            $thematic = new CThematic();
            $thematic
                ->setTitle($title)
                ->setContent($content)
                //->setDisplayOrder($max_thematic_item + 1)
                ->setDisplayOrder(0)
                ->setParent($course)
                ->addCourseLink($course, $session)
            ;

            $repo->create($thematic);
        } else {
            $thematic = $repo->find($id);
            if ($thematic) {
                $thematic
                    ->setTitle($title)
                    ->setContent($content)
                ;
                $repo->update($thematic);
            }
        }

        return $thematic;
    }

    /**
     * Delete logically (set active field to 0) a thematic.
     *
     * @param int|array One or many thematic ids
     *
     * @return int Affected rows
     */
    public function delete($thematic_id)
    {
        $tbl_thematic = Database::get_course_table(TABLE_THEMATIC);
        $affected_rows = 0;
        if (is_array($thematic_id)) {
            foreach ($thematic_id as $id) {
                $id = (int) $id;
                $sql = "UPDATE $tbl_thematic SET active = 0
                        WHERE iid = $id";
                $result = Database::query($sql);
                $affected_rows += Database::affected_rows($result);
                if (!empty($affected_rows)) {
                    // update row item property table
                    /*api_item_property_update(
                        $_course,
                        'thematic',
                        $id,
                        'ThematicDeleted',
                        $user_id
                    );*/
                }
            }
        } else {
            $thematic_id = (int) $thematic_id;
            $sql = "UPDATE $tbl_thematic SET active = 0
                    WHERE iid = $thematic_id";
            $result = Database::query($sql);
            $affected_rows = Database::affected_rows($result);
            if (!empty($affected_rows)) {
                // update row item property table
                /*api_item_property_update(
                    $_course,
                    'thematic',
                    $thematic_id,
                    'ThematicDeleted',
                    $user_id
                );*/
            }
        }

        return $affected_rows;
    }

    /**
     * @param int $thematicId
     */
    public function copy($thematicId)
    {
        $repo = Container::getThematicRepository();
        /** @var CThematic $thematic */
        $thematic = $repo->find($thematicId);
        if (null === $thematic) {
            return false;
        }

        $thematicManager = new Thematic();

        $newThematic = $thematicManager->thematicSave(
            null,
            $thematic->getTitle().' - '.get_lang('Copy'),
            $thematic->getContent(),
            api_get_course_entity(),
            api_get_session_entity()
        );

        if (!empty($newThematic->getIid())) {
            $thematic_advanced = $thematic->getAdvances();
            if (!empty($thematic_advanced)) {
                foreach ($thematic_advanced as $item) {
                    $thematic = new Thematic();
                    $thematic->thematicAdvanceSave(
                        $newThematic,
                        $item->getAttendance(),
                        null,
                        $item->getContent(),
                        $item->getStartDate()->format('Y-m-d H:i:s'),
                        $item->getDuration()
                    );
                }
            }

            $thematic_plan = $thematic->getPlans();
            if (!empty($thematic_plan)) {
                foreach ($thematic_plan as $item) {
                    $thematic->thematicPlanSave(
                        $newThematic,
                        $item->getTitle(),
                        $item->getDescription(),
                        $item->getDescriptionType()
                    );
                }
            }
        }
    }

    /**
     * Get the total number of thematic advance inside current course.
     *
     * @see SortableTable#get_total_number_of_items()
     */
    public static function get_number_of_thematic_advances(array $params)
    {
        $table = Database::get_course_table(TABLE_THEMATIC_ADVANCE);
        $thematic_id = (int) $params['thematic_id'];

        $sql = "SELECT COUNT(iid) AS total_number_of_items
                FROM $table
                WHERE thematic_id = $thematic_id ";
        $res = Database::query($sql);
        $obj = Database::fetch_object($res);

        return $obj->total_number_of_items;
    }

    /**
     * Get the thematic advances to display on the current page (fill the sortable-table).
     *
     * @param   int     offset of first user to recover
     * @param   int     Number of users to get
     * @param   int     Column to sort on
     * @param   string  Order (ASC,DESC)
     *
     * @return array
     *
     * @see SortableTable#get_table_data($from)
     */
    public static function get_thematic_advance_data($from, $number_of_items, $column, $direction, $params = [])
    {
        $table = Database::get_course_table(TABLE_THEMATIC_ADVANCE);
        $column = (int) $column;
        $from = (int) $from;
        $number_of_items = (int) $number_of_items;
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }
        $data = [];
        $thematic_id = (int) $params['thematic_id'];
        if (api_is_allowed_to_edit(null, true)) {
            $sql = "SELECT iid AS col0, start_date AS col1, duration AS col2, content AS col3
                    FROM $table
                    WHERE thematic_id = $thematic_id
                    ORDER BY col$column $direction
                    LIMIT $from,$number_of_items ";

            /*$list = api_get_item_property_by_tool(
                'thematic_advance',
                api_get_course_id(),
                api_get_session_id()
            );*/

            /*$elements = [];
            foreach ($list as $value) {
                $elements[] = $value['ref'];
            }*/

            $res = Database::query($sql);
            $i = 1;
            while ($thematic_advance = Database::fetch_row($res)) {
                //if (in_array($thematic_advance[0], $elements)) {
                $thematic_advance[1] = api_get_local_time($thematic_advance[1]);
                $thematic_advance[1] = api_format_date($thematic_advance[1], DATE_TIME_FORMAT_LONG);
                $actions = '';
                $actions .= '<a
                        href="index.php?'.api_get_cidreq().'&action=thematic_advance_edit&thematic_id='.$thematic_id.'&thematic_advance_id='.$thematic_advance[0].'">'.
                        Display::return_icon('edit.png', get_lang('Edit'), '', 22).'</a>';
                $actions .= '<a
                    onclick="javascript:if(!confirm(\''.get_lang('Are you sure you want to delete').'\')) return false;"
                    href="index.php?'.api_get_cidreq().'&action=thematic_advance_delete&thematic_id='.$thematic_id.'&thematic_advance_id='.$thematic_advance[0].'">'.
                        Display::return_icon('delete.png', get_lang('Delete'), '', 22).'</a></center>';
                $data[] = [$i, $thematic_advance[1], $thematic_advance[2], $thematic_advance[3], $actions];
                $i++;
                // }
            }
        }

        return $data;
    }

    public function getThematicAdvance($id): ?CThematicAdvance
    {
        $repo = Container::getThematicAdvanceRepository();

        return $repo->find($id);
    }

    /**
     * insert or update a thematic advance.
     *
     * @return CThematicAdvance
     */
    public function thematicAdvanceSave(
        CThematic $thematic,
        CAttendance $attendance = null,
        CThematicAdvance $advance = null,
        $content,
        $start_date,
        $duration
    ) {
        $em = Database::getManager();
        $duration = (int) $duration;

        if (null === $advance) {
            $advance = (new CThematicAdvance())
                ->setContent($content)
                ->setThematic($thematic)
                //->setAttendance($attendance)
                ->setStartDate(api_get_utc_datetime($start_date, true, true))
                ->setDuration($duration)
            ;

            if ($attendance) {
                $advance->setAttendance($attendance);
            }
            //$courseEntity = api_get_course_entity();
            /*$advance
                ->setParent($courseEntity)
                ->addCourseLink($courseEntity, api_get_session_entity())
            ;*/
            $em->persist($advance);
            $em->flush();
        } else {
            $advance
                ->setContent($content)
                ->setStartDate(api_get_utc_datetime($start_date, true, true))
                ->setDuration($duration)
            ;

            if ($thematic) {
                $advance->setThematic($thematic);
            }

            if ($attendance) {
                $advance->setAttendance($attendance);
            }
            $em->persist($advance);
            $em->flush();
        }

        return $advance;
    }

    /**
     * insert or update a thematic plan.
     *
     * @return int affected rows
     */
    public function thematicPlanSave(CThematic $thematic, $title, $description, $description_type, $course = null, $session = null)
    {
        // protect data
        $thematic_id = $thematic->getIid();
        $description_type = (int) $description_type;

        /*$list = api_get_item_property_by_tool(
            'thematic_plan',
            api_get_course_id(),
            api_get_session_id()
        );

        $elements_to_show = [];
        foreach ($list as $value) {
            $elements_to_show[] = $value['ref'];
        }
        $condition = '';
        if (!empty($elements_to_show)) {
            $condition = 'AND id IN ('.implode(',', $elements_to_show).') ';
        }*/

        $repo = Container::getThematicPlanRepository();
        $criteria = [
            'thematic' => $thematic_id,
            'descriptionType' => $description_type,
        ];
        /** @var CThematicPlan $plan */
        $plan = $repo->findOneBy($criteria);
        $em = Database::getManager();
        // check thematic plan type already exists
        /*$sql = "SELECT id FROM $tbl_thematic_plan
                WHERE
                    c_id = $course_id AND
                    thematic_id = $thematic_id AND
                    description_type = '$description_type'";
        $rs = Database::query($sql);*/
        if ($plan) {
            $plan
                ->setTitle($title)
                ->setDescription($description)
            ;
            $em->persist($plan);
            $em->flush();
        //$repo->update($plan);

        // update
            /*$params = [
                'title' => $title,
                'description' => $description,
            ];
            Database::update(
                $tbl_thematic_plan,
                $params,
                ['c_id = ? AND id = ?' => [$course_id, $thematic_plan_id]]
            );

            api_item_property_update(
                $_course,
                'thematic_plan',
                $thematic_plan_id,
                'ThematicPlanUpdated',
                $user_id
            );*/
        } else {
            $thematic = Container::getThematicRepository()->find($thematic_id);
            $plan = new CThematicPlan();
            $plan
                ->setTitle($title)
                ->setDescription($description)
                ->setThematic($thematic)
                ->setDescriptionType($description_type)
                //->setParent($course)
                //->addCourseLink($course, api_get_session_entity())
            ;

            //$repo->create($plan);
            $em->persist($plan);
            $em->flush();
            if ($plan && $plan->getIid()) {
                /*
                api_item_property_update(
                    $_course,
                    'thematic_plan',
                    $last_id,
                    'ThematicPlanAdded',
                    $user_id
                );*/
            }
        }

        return true;
    }

    /**
     * Delete a thematic plan description.
     *
     * @param int $thematic_id Thematic id
     *
     * @return int Affected rows
     */
    public function thematic_plan_destroy($thematic_id, $descriptionType)
    {
        $repo = Container::getThematicRepository();

        /** @var CThematic $thematic */
        $thematic = $repo->find($thematic_id);

        foreach ($thematic->getPlans() as $plan) {
            if ($descriptionType == $plan->getDescriptionType()) {
                $thematic->getPlans()->removeElement($plan);
            }
        }

        $repo->update($thematic);

        return false;

        /*$_course = api_get_course_info();
        // definition database table
        $tbl_thematic_plan = Database::get_course_table(TABLE_THEMATIC_PLAN);

        // protect data
        $thematic_id = intval($thematic_id);
        $description_type = intval($description_type);
        $user_id = api_get_user_id();
        $course_info = api_get_course_info();
        $course_id = $course_info['real_id'];

        // get thematic plan id
        $thematic_plan_data = $this->get_thematic_plan_data($thematic_id, $description_type);
        $thematic_plan_id = $thematic_plan_data[0]['id'];

        // delete
        $sql = "DELETE FROM $tbl_thematic_plan
                WHERE
                    c_id = $course_id AND
                    thematic_id = $thematic_id AND
                    description_type = $description_type ";
        $result = Database::query($sql);
        $affected_rows = Database::affected_rows($result);*/
        /*
        if ($affected_rows) {
            /*api_item_property_update(
                $_course,
                'thematic_plan',
                $thematic_plan_id,
                'ThematicPlanDeleted',
                $user_id
            );
        }*/

        return $affected_rows;
    }

    /**
     * Get next description type for a new thematic plan description (option 'others').
     *
     * @param int $thematic_id Thematic id
     *
     * @return int New Description type
     */
    public function get_next_description_type($thematic_id)
    {
        // definition database table
        $tbl_thematic_plan = Database::get_course_table(TABLE_THEMATIC_PLAN);

        // protect data
        $thematic_id = intval($thematic_id);
        $course_id = api_get_course_int_id();

        $sql = "SELECT MAX(description_type) as max
                FROM $tbl_thematic_plan
                WHERE
                    c_id = $course_id AND
                    thematic_id = $thematic_id AND
                    description_type >= ".ADD_THEMATIC_PLAN;
        $rs = Database::query($sql);
        $row = Database::fetch_array($rs);
        $last_description_type = $row['max'];

        if (isset($last_description_type)) {
            $next_description_type = $last_description_type + 1;
        } else {
            $next_description_type = ADD_THEMATIC_PLAN;
        }

        return $next_description_type;
    }

    /**
     * update done thematic advances from thematic details interface.
     *
     * @return int Affected rows
     */
    public function updateDoneThematicAdvance($advanceId, $course, $session = null)
    {
        $em = Database::getManager();
        $list = self::getThematicList($course, $session);
        $ordered = [];

        foreach ($list as $thematic) {
            $done = true;
            foreach ($thematic->getAdvances() as $advance) {
                $ordered[] = $advance;
                /*if ($advanceId === $advance->getIid()) {
                    $done = false;
                }*/
                $advance->setDoneAdvance($done);
            }
        }

        $done = true;
        foreach ($ordered as $advance) {
            if ($advanceId === $advance->getIid()) {
                $done = false;
                $advance->setDoneAdvance(true);
                $em->persist($advance);
                continue;
            }

            $advance->setDoneAdvance($done);
            $em->persist($advance);
        }

        $em->flush();

        return true;

        /*$_course = api_get_course_info();
        $thematic_data = self::getThematicList(api_get_course_id());
        $table = Database::get_course_table(TABLE_THEMATIC_ADVANCE);

        $affected_rows = 0;
        $user_id = api_get_user_id();*/

        /*$all = [];
        if (!empty($thematic_data)) {
            foreach ($thematic_data as $thematic) {
                $thematic_id = $thematic['id'];
                if (!empty($thematic_advance_data[$thematic['id']])) {
                    foreach ($thematic_advance_data[$thematic['id']] as $thematic_advance) {
                        $all[] = $thematic_advance['id'];
                    }
                }
            }
        }*/
        $error = null;
        $a_thematic_advance_ids = [];
        $course_id = api_get_course_int_id();
        $sessionId = api_get_session_id();

        /*if (!empty($thematic_data)) {
            foreach ($thematic_data as $thematic) {
                $my_affected_rows = 0;
                $thematic_id = $thematic['id'];
                if (!empty($thematic_advance_data[$thematic['id']])) {
                    foreach ($thematic_advance_data[$thematic['id']] as $thematic_advance) {
                        $item_info = api_get_item_property_info(
                            api_get_course_int_id(),
                            'thematic_advance',
                            $thematic_advance['id'],
                            $sessionId
                        );

                        if ($item_info['session_id'] == $sessionId) {
                            $a_thematic_advance_ids[] = $thematic_advance['id'];
                            // update done thematic for previous advances ((done_advance = 1))
                            $upd = "UPDATE $table SET
                                    done_advance = 1
                                    WHERE c_id = $course_id AND id = ".$thematic_advance['id'].' ';
                            $result = Database::query($upd);
                            $my_affected_rows = Database::affected_rows($result);
                            $affected_rows += $my_affected_rows;
                            //if ($my_affected_rows) {
                            api_item_property_update(
                                $_course,
                                'thematic_advance',
                                $thematic_advance['id'],
                                'ThematicAdvanceDone',
                                $user_id
                            );
                            //}
                            if ($thematic_advance['id'] == $thematic_advance_id) {
                                break 2;
                            }
                        }
                    }
                }
            }
        }*/

        // Update done thematic for others advances (done_advance = 0)
        if (!empty($a_thematic_advance_ids) && count($a_thematic_advance_ids) > 0) {
            $diff = array_diff($all, $a_thematic_advance_ids);
            if (!empty($diff)) {
                $upd = "UPDATE $table SET done_advance = 0
                        WHERE iid IN(".implode(',', $diff).') ';
                Database::query($upd);
            }

            // update item_property
            /*$tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
            $sql = "SELECT ref FROM $tbl_item_property
                    WHERE
                        c_id = $course_id AND
                        tool='thematic_advance' AND
                        lastedit_type='ThematicAdvanceDone' AND
                        session_id = $sessionId ";
            // get all thematic advance done
            $rs_thematic_done = Database::query($sql);
            if (Database::num_rows($rs_thematic_done) > 0) {
                while ($row_thematic_done = Database::fetch_array($rs_thematic_done)) {
                    $ref = $row_thematic_done['ref'];
                    if (in_array($ref, $a_thematic_advance_ids)) {
                        continue;
                    }
                    // update items
                    $sql = "UPDATE $tbl_item_property SET
                                lastedit_date='".api_get_utc_datetime()."',
                                lastedit_type='ThematicAdvanceUpdated',
                                lastedit_user_id = $user_id
                            WHERE
                                c_id = $course_id AND
                                tool='thematic_advance' AND
                                ref=$ref AND
                                session_id = $sessionId  ";
                    Database::query($sql);
                }
            }*/
        }

        return $affected_rows;
    }

    /**
     * Get last done thematic advance from thematic details interface.
     *
     * @return int Last done thematic advance id
     */
    public function get_last_done_thematic_advance($course, $session = null)
    {
        $thematic_data = self::getThematicList($course, $session);
        $a_thematic_advance_ids = [];
        $last_done_advance_id = 0;
        if (!empty($thematic_data)) {
            /** @var CThematic $thematic */
            foreach ($thematic_data as $thematic) {
                $id = $thematic->getIid();
                if ($thematic->getAdvances()->count()) {
                    foreach ($thematic->getAdvances() as $thematic_advance) {
                        if (1 == $thematic_advance->getDoneAdvance()) {
                            $a_thematic_advance_ids[] = $thematic_advance->getIid();
                        }
                    }
                }
            }
        }
        if (!empty($a_thematic_advance_ids)) {
            $last_done_advance_id = array_pop($a_thematic_advance_ids);
            $last_done_advance_id = intval($last_done_advance_id);
        }

        return $last_done_advance_id;
    }

    /**
     * Get next thematic advance not done from thematic details interface.
     *
     * @param   int Offset (if you want to get an item that is not directly the next)
     *
     * @return int next thematic advance not done
     */
    public function get_next_thematic_advance_not_done($offset = 1, $course, $session = null)
    {
        $thematic_data = self::getThematicList($course, $session);
        $a_thematic_advance_ids = [];
        if (!empty($thematic_data)) {
            foreach ($thematic_data as $thematic) {
                $advanceList = $thematic->getAdvances();
                foreach ($advanceList as $advance) {
                    if (0 == $advance->getDoneAdvance()) {
                        $a_thematic_advance_ids[] = $advance->getIid();
                    }
                }
            }
        }

        $next_advance_not_done = 0;
        if (!empty($a_thematic_advance_ids)) {
            for ($i = 0; $i < $offset; $i++) {
                $next_advance_not_done = array_shift($a_thematic_advance_ids);
            }
            $next_advance_not_done = intval($next_advance_not_done);
        }

        return $next_advance_not_done;
    }

    /**
     * Get total average of thematic advances.
     *
     * @return float Average of thematic advances
     */
    public function get_total_average_of_thematic_advances(Course $course, Session $session = null)
    {
        $thematic_data = self::getThematicList($course, $session);

        $list = [];
        $total_average = 0;
        if (!empty($thematic_data)) {
            /** @var CThematic $thematic */
            foreach ($thematic_data as $thematic) {
                $thematic_id = $thematic->getIid();
                $list[$thematic_id] = $this->get_average_of_advances_by_thematic(
                    $thematic
                );
            }
        }

        // calculate total average
        if (!empty($list)) {
            $count = count($thematic_data);
            $score = array_sum($list);
            $total_average = round(($score * 100) / ($count * 100));
        }

        return $total_average;
    }

    /**
     * Get average of advances by thematic.
     *
     * @param CThematic $thematic
     *
     * @return float Average of thematic advances
     */
    public function get_average_of_advances_by_thematic($thematic)
    {
        $advances = $thematic->getAdvances();
        $average = 0;
        if ($advances->count()) {
            // get all done advances by thematic
            $count = 0;
            /** @var CThematicAdvance $thematic_advance */
            foreach ($advances as $thematic_advance) {
                if ($thematic_advance->getDoneAdvance()) {
                    $count++;
                }
            }

            // calculate average by thematic
            $average = round(($count * 100) / count($advances));
        }

        return $average;
    }

    /**
     * set attributes for fields of thematic_advance table.
     *
     * @param int $id Thematic advance id
     * @param    int        Thematic id
     * @param    int        Attendance id
     * @param    string    Content
     * @param    string    Date and time
     * @param    int        Duration in hours
     */
    public function set_thematic_advance_attributes(
        $id = null,
        $thematic_id = 0,
        $attendance_id = 0,
        $content = '',
        $start_date = null,
        $duration = 0
    ) {
        $this->thematic_advance_id = $id;
        $this->thematic_id = $thematic_id;
        $this->attendance_id = $attendance_id;
        $this->thematic_advance_content = $content;
        $this->start_date = $start_date;
        $this->duration = $duration;
    }

    /**
     * set thematic id.
     *
     * @param    int     Thematic id
     */
    public function set_thematic_id($thematic_id)
    {
        $this->thematic_id = $thematic_id;
    }

    /**
     * get thematic id.
     *
     * @return int
     */
    public function get_thematic_id()
    {
        return $this->thematic_id;
    }

    /**
     * Get thematic plan titles by default.
     *
     * @return array
     */
    public function get_default_thematic_plan_title()
    {
        $default_thematic_plan_titles = [];
        $default_thematic_plan_titles[1] = get_lang('Objectives');
        $default_thematic_plan_titles[2] = get_lang('Skills to acquire');
        $default_thematic_plan_titles[3] = get_lang('Methodology');
        $default_thematic_plan_titles[4] = get_lang('Infrastructure');
        $default_thematic_plan_titles[5] = get_lang('Assessment');
        $default_thematic_plan_titles[6] = get_lang('Others');

        return $default_thematic_plan_titles;
    }

    /**
     * Get thematic plan icons by default.
     *
     * @return array
     */
    public function get_default_thematic_plan_icon()
    {
        $default_thematic_plan_icon = [];
        $default_thematic_plan_icon[1] = 'icons/32/objective.png';
        $default_thematic_plan_icon[2] = 'icons/32/skills.png';
        $default_thematic_plan_icon[3] = 'icons/32/strategy.png';
        $default_thematic_plan_icon[4] = 'icons/32/laptop.png';
        $default_thematic_plan_icon[5] = 'icons/32/assessment.png';
        $default_thematic_plan_icon[6] = 'icons/32/wizard.png';

        return $default_thematic_plan_icon;
    }

    /**
     * Get questions by default for help.
     *
     * @return array
     */
    public function get_default_question()
    {
        $question = [];
        $question[1] = get_lang('What should the end results be when the learner has completed the course? What are the activities performed during the course?');
        $question[2] = get_lang('Skills to acquireQuestions');
        $question[3] = get_lang('What methods and activities help achieve the objectives of the course?  What would the schedule be?');
        $question[4] = get_lang('What infrastructure is necessary to achieve the goals of this topic normally?');
        $question[5] = get_lang('How will learners be assessed? Are there strategies to develop in order to master the topic?');

        return $question;
    }
}

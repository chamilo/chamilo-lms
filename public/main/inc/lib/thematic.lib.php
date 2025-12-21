<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
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
     * Moves a thematic item up or down in the list by adjusting its display order in the associated resource node.
     */
    public function moveThematic(string $direction, int $thematicId, Course $course, ?Session $session = null): bool
    {
        $em = Database::getManager();
        $thematicRepo = $em->getRepository(CThematic::class);

        $thematic = $thematicRepo->find($thematicId);
        if (null === $thematic) {
            return false;
        }

        $resourceNode = $thematic->getResourceNode();
        if (null === $resourceNode) {
            return false;
        }

        $link = $resourceNode->getResourceLinkByContext($course, $session);

        if (!$link) {
            return false;
        }

        if ('down' === $direction) {
            $link->moveDownPosition();
        } else {
            $link->moveUpPosition();
        }

        $em->flush();

        // update done advances with de current thematic list
        $last_done_thematic_advance = $this->get_last_done_thematic_advance($course, $session);
        $this->updateDoneThematicAdvance($last_done_thematic_advance, $course, $session);

        return true;
    }

    /**
     * Get thematic list.
     *
     * @return CThematic[]
     */
    public static function getThematicList(Course $course, Session $session = null): array
    {
        $repo = Container::getThematicRepository();
        $qb = $repo->getResourcesByCourse($course, $session, null, null, true, true);
        $qb->andWhere('resource.active = 1');

        return $qb->getQuery()->getResult();
    }

    /**
     * Generates HTML for move up and move down action buttons for a thematic item.
     */
    public function getMoveActions(int $thematicId, int $currentOrder, int $maxOrder): string
    {
        $toolbarThematic = '';
        $params = '&thematic_id=' . $thematicId . '&sec_token=' . Security::get_token();

        if ($currentOrder > 0) {
            $toolbarThematic .= '<a class="btn btn--default" href="'.api_get_self().'?action=moveup&'.api_get_cidreq().$params.'">' . Display::getMdiIcon(ActionIcon::UP, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Up')) . '</a>';
        } else {
            $toolbarThematic .= '<div class="btn btn--default">' . Display::getMdiIcon(ActionIcon::UP, 'ch-tool-icon-disabled', null, ICON_SIZE_MEDIUM, '') . '</div>';
        }

        if ($currentOrder < $maxOrder - 1) {
            $toolbarThematic .= '<a class="btn btn--default" href="'.api_get_self().'?action=movedown&'.api_get_cidreq().$params.'">' . Display::getMdiIcon(ActionIcon::DOWN, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Down')) . '</a>';
        } else {
            $toolbarThematic .= '<div class="btn btn--default">' . Display::getMdiIcon(ActionIcon::DOWN, 'ch-tool-icon-disabled', null, ICON_SIZE_MEDIUM, '') . '</div>';
        }

        return $toolbarThematic;
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
                ->setParent($course)
                ->addCourseLink($course, $session)
                ->setActive(true)
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

    public function delete(int|array $thematicId): void
    {
        $repo = Container::getThematicRepository();
        $linksRepo = Container::$container->get(ResourceLinkRepository::class);

        $course = api_get_course_entity();
        $session = api_get_session_entity();

        if (is_array($thematicId)) {
            foreach ($thematicId as $id) {
                /** @var CThematic $resource */
                $resource = $repo->find($id);
                $linksRepo->removeByResourceInContext($resource, $course, $session);
            }
        } else {
            /** @var CThematic $resource */
            $resource = $repo->find($thematicId);
            $linksRepo->removeByResourceInContext($resource, $course, $session);
        };
    }

    /**
     * Duplicate a thematic (title, content, plans and advances) into the same Course/Session.
     */
    public function copy(int $thematicId, ?Course $course = null, ?Session $session = null): ?CThematic
    {
        $repo = Container::getThematicRepository();
        /** @var CThematic|null $source */
        $source = $repo->find($thematicId);
        if (!$source) {
            return null;
        }

        // Resolve context if not provided
        $course  = $course   ?: api_get_course_entity();
        $session = $session  ?: api_get_session_entity();

        // Create the new thematic using the existing helper (keeps linking logic consistent)
        $new = $this->thematicSave(
            null,
            (string) $source->getTitle(),
            (string) $source->getContent(),
            $course,
            $session
        );

        if (!$new) {
            return null;
        }

        // Copy advances
        foreach ($source->getAdvances() as $adv) {
            // Normalize start date to string Y-m-d H:i:s for thematicAdvanceSave
            $startDate = $adv->getStartDate();
            if ($startDate instanceof \DateTimeInterface) {
                $startDate = $startDate->format('Y-m-d H:i:s');
            } else {
                $startDate = (string) $startDate;
            }

            // Keep the same attendance relation if any (same course/session context)
            $this->thematicAdvanceSave(
                $new,
                $adv->getAttendance(),
                null,
                (string) $adv->getContent(),
                $startDate,
                (float) $adv->getDuration()
            );
        }

        // Copy plans
        foreach ($source->getPlans() as $plan) {
            $this->thematicPlanSave(
                $new,
                (string) $plan->getTitle(),
                (string) $plan->getDescription(),
                (int) $plan->getDescriptionType()
            );
        }

        return $new;
    }

    /**
     * Get the total number of thematic advance inside current course.
     *
     * @see SortableTable#get_total_number_of_items()
     */
    public static function get_number_of_thematic_advances(array $params): int
    {
        $thematic_id = (int) $params['thematic_id'];
        $repo = Container::getThematicAdvanceRepository();
        return $repo->count(['thematic' => $thematic_id]);
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
                        Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')).'</a>';
                $actions .= '<a
                    onclick="javascript:if(!confirm(\''.get_lang('Are you sure you want to delete').'\')) return false;"
                    href="index.php?'.api_get_cidreq().'&action=thematic_advance_delete&thematic_id='.$thematic_id.'&thematic_advance_id='.$thematic_advance[0].'">'.
                        Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')).'</a></center>';
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
        $question[2] = get_lang('What skills are to be acquired bu the end of this thematic section?');
        $question[3] = get_lang('What methods and activities help achieve the objectives of the course?  What would the schedule be?');
        $question[4] = get_lang('What infrastructure is necessary to achieve the goals of this topic normally?');
        $question[5] = get_lang('How will learners be assessed? Are there strategies to develop in order to master the topic?');

        return $question;
    }
}

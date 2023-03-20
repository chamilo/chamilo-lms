<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CThematicAdvance;

require_once __DIR__.'/../global.inc.php';
api_protect_course_script(true);

$action = $_GET['a'];
$thematic = new Thematic();
$course = api_get_course_entity();
$session = api_get_session_entity();

switch ($action) {
    case 'save_thematic_plan':
        /*$title_list         = $_REQUEST['title'];
        $description_list   = $_REQUEST['desc'];
        //$description_list   = $_REQUEST['description'];
        $description_type   = $_REQUEST['description_type'];
        if (api_is_allowed_to_edit(null, true)) {
            for($i=1;$i<count($title_list)+1; $i++) {
                $thematic->set_thematic_plan_attributes($_REQUEST['thematic_id'], $title_list[$i], $description_list[$i], $description_type[$i]);
                $affected_rows = $thematic->thematic_plan_save();
            }
        }
        $thematic_plan_data = $thematic->get_thematic_plan_data();
        $return = $thematic->get_thematic_plan_div($thematic_plan_data);
        echo $return[$_REQUEST['thematic_id']];*/
        break;
    case 'save_thematic_advance':
        if (!api_is_allowed_to_edit(null, true)) {
            echo '';
            exit;
        }
        /*
        if (($_REQUEST['start_date_type'] == 1 && empty($_REQUEST['start_date_by_attendance'])) || (!empty($_REQUEST['duration_in_hours']) && !is_numeric($_REQUEST['duration_in_hours'])) ) {
            if ($_REQUEST['start_date_type'] == 1 && empty($_REQUEST['start_date_by_attendance'])) {
                $start_date_error = true;
                $data['start_date_error'] = $start_date_error;
            }

            if (!empty($_REQUEST['duration_in_hours']) && !is_numeric($_REQUEST['duration_in_hours'])) {
                $duration_error = true;
                $data['duration_error'] = $duration_error;
            }

            $data['action'] = $_REQUEST['action'];
            $data['thematic_id'] = $_REQUEST['thematic_id'];
            $data['attendance_select'] = $attendance_select;
            if (isset($_REQUEST['thematic_advance_id'])) {
                $data['thematic_advance_id'] = $_REQUEST['thematic_advance_id'];
                $thematic_advance_data = $thematic->get_thematic_advance_list($_REQUEST['thematic_advance_id']);
                $data['thematic_advance_data'] = $thematic_advance_data;
            }
        } else {
            if ($_REQUEST['thematic_advance_token'] == $_SESSION['thematic_advance_token'] && api_is_allowed_to_edit(null, true)) {
                $thematic_advance_id 	= $_REQUEST['thematic_advance_id'];
                $thematic_id 			= $_REQUEST['thematic_id'];
                $content 				= $_REQUEST['real_content'];
                $duration				= $_REQUEST['duration_in_hours'];
                if (isset($_REQUEST['start_date_type']) && $_REQUEST['start_date_type'] == 2) {
                    $start_date 	= $thematic->build_datetime_from_array($_REQUEST['custom_start_date']);
                    $attendance_id 	= 0;
                } else {
                    $start_date 	= $_REQUEST['start_date_by_attendance'];
                    $attendance_id 	= $_REQUEST['attendance_select'];
                }
                $thematic->set_thematic_advance_attributes($thematic_advance_id, $thematic_id,  $attendance_id, $content, $start_date, $duration);
                $affected_rows = $thematic->thematic_advance_save();
                if ($affected_rows) {
                    // get last done thematic advance before move thematic list
                    $last_done_thematic_advance = $thematic->get_last_done_thematic_advance();
                    // update done advances with de current thematic list
                    if (!empty($last_done_thematic_advance)) {
                        $update_done_advances = $thematic->update_done_thematic_advances($last_done_thematic_advance);
                    }
                }
            }
        }
        $thematic_advance_data = $thematic->get_thematic_advance_list(null, null, true);
        $return = $thematic->get_thematic_advance_div($thematic_advance_data);
        echo $return[$_REQUEST['thematic_id']][$_REQUEST['thematic_advance_id']];*/
        break;
    case 'get_datetime_by_attendance':
        $attendance_id = (int) $_REQUEST['attendance_id'];
        $thematic_advance_id = (int) $_REQUEST['thematic_advance_id'];
        $label = '';
        $inputSelect = '';

        $repo = Container::getAttendanceRepository();
        /** @var CAttendance $attendance */
        $attendance = $repo->find($attendance_id);

        $repo = Container::getThematicAdvanceRepository();
        /** @var CThematicAdvance $thematicAdvance */
        $thematicAdvance = $repo->find($thematic_advance_id);

        if (!empty($attendance)) {
            $thematicList = $thematic->getThematicList($course, $session);
            $my_list = $thematic_list_temp = [];
            foreach ($thematicList as $item) {
                $thematic_list_temp[] = $item->getAdvances();
            }

            $new_thematic_list = [];
            foreach ($thematic_list_temp as $advanceList) {
                foreach ($advanceList as $advanceItem) {
                    if (!empty($advanceItem->getAttendance())) {
                        $new_thematic_list[$advanceItem->getIid()] = [
                            'attendance_id' => $advanceItem->getAttendance()->getIid(),
                            'start_date' => $advanceItem->getStartDate()->format('Y-m-d H:i:s'),
                        ];
                    }
                }
            }

            $attendance_calendar = $attendance->getCalendars();
            $label = get_lang('Start Date');
            if (!empty($attendance_calendar)) {
                $inputSelect .= '<select
                        id="start_date_select_calendar"
                        name="start_date_by_attendance" size="7" class="form-control">';
                foreach ($attendance_calendar as $calendar) {
                    $selected = null;
                    $insert = true;
                    $utcDateTime = $calendar->getDateTime()->format('Y-m-d H:i:s');
                    $dateTime = api_get_local_time($calendar->getDateTime()->format('Y-m-d H:i:s'));

                    // Checking if it was already taken.
                    foreach ($new_thematic_list as $key => $thematic_item) {
                        if ($utcDateTime === $thematic_item['start_date']) {
                            $insert = false;
                            if ($thematic_advance_id == $key) {
                                $insert = true;
                                $selected = 'selected';
                            }
                            break;
                        }
                    }

                    if ($insert) {
                        $inputSelect .= '<option
                            '.$selected.'
                            value="'.$dateTime.'">'.
                            $dateTime.'</option>';
                    }
                }
                $inputSelect .= '</select>';
            } else {
                $inputSelect .= '<em>'.get_lang('There is no date/time registered yet').'</em>';
            }
        }
        echo '<div class="row form-group">';
        echo '<label class="col-sm-2 control-label">'.$label.'</label>';
        echo '<div class="col-sm-8">'.$inputSelect.'</div>';
        echo '</div>';

        break;
    case 'update_done_thematic_advance':
        $id = (int) $_GET['thematic_advance_id'];
        $average = 0;
        if (!empty($id)) {
            $thematic = new Thematic();
            $thematic->updateDoneThematicAdvance($id, $course, $session);
            $average = $thematic->get_total_average_of_thematic_advances($course, $session);
        }
        echo $average;
        break;
    default:
        echo '';
}
exit;

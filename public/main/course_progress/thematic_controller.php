<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Thematic Controller script.
 * Prepares the common background variables to give to the scripts corresponding to
 * the requested action.
 *
 * This file contains class used like controller for thematic,
 * it should be included inside a dispatcher file (e.g: index.php)
 *
 * !!! WARNING !!! : ALL DATES IN THIS MODULE ARE STORED IN UTC !
 * DO NOT CONVERT DURING THE TRANSITION FROM CHAMILO 1.8.x TO 2.0
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 * @author Julio Montoya <gugli100@gmail.com> token support improving UI
 */
class ThematicController
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->toolname = 'course_progress';
        $this->view = new View($this->toolname);
    }

    /**
     * This method is used for thematic advance control (update, insert or listing)
     * render to thematic_advance.php.
     *
     * @param string $action
     */
    public function thematic_advance($action)
    {
        $thematic = new Thematic();
        $attendance = new Attendance();
        $data = [];
        $displayHeader = !empty($_REQUEST['display']) && 'no_header' === $_REQUEST['display'] ? false : true;


        $thematic_id = intval($_REQUEST['thematic_id']);
        $thematic_advance_id = isset($_REQUEST['thematic_advance_id']) ? (int) $_REQUEST['thematic_advance_id'] : null;
        $thematic_advance_data = [];
        switch ($action) {
            case 'thematic_advance_delete':

                break;
            case 'thematic_advance_list':
                if (!api_is_allowed_to_edit(null, true)) {
                    echo '';
                    exit;
                }

                $data['action'] = $_REQUEST['action'];
                $data['thematic_id'] = $_REQUEST['thematic_id'];
                $data['attendance_select'] = $attendance_select;
                if (isset($_REQUEST['thematic_advance_id'])) {
                    $data['thematic_advance_id'] = $_REQUEST['thematic_advance_id'];
                    $thematic_advance_data = $thematic->get_thematic_advance_list($_REQUEST['thematic_advance_id']);
                    $data['thematic_advance_data'] = $thematic_advance_data;
                }
                break;
            default:
                $thematic_advance_data = $thematic->get_thematic_advance_list($thematic_advance_id);
                break;
        }

        // get calendar select by attendance id
        $calendar_select = [];
        if (!empty($thematic_advance_data)) {
            if (!empty($thematic_advance_data['attendance_id'])) {
                $attendance_calendar = $attendance->get_attendance_calendar($thematic_advance_data['attendance_id']);
                if (!empty($attendance_calendar)) {
                    foreach ($attendance_calendar as $calendar) {
                        $calendar_select[$calendar['date_time']] = $calendar['date_time'];
                    }
                }
            }
        }

        $data['action'] = $action;
        $data['thematic_id'] = $thematic_id;
        $data['thematic_advance_id'] = $thematic_advance_id;
        $data['attendance_select'] = $attendance_select;
        $data['thematic_advance_data'] = $thematic_advance_data;
        $data['calendar_select'] = $calendar_select;
        $layoutName = $displayHeader ? 'layout' : 'layout_no_header';

        // render to the view
        $this->view->set_data($data);
        $this->view->set_layout($layoutName);
        $this->view->set_template('thematic_advance');
        $this->view->render();
    }
}

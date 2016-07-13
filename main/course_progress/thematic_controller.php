<?php
/* For licensing terms, see /license.txt */

/**
 * Thematic Controller script.
 * Prepares the common background variables to give to the scripts corresponding to
 * the requested action
 *
 * This file contains class used like controller for thematic,
 * it should be included inside a dispatcher file (e.g: index.php)
 *
 * !!! WARNING !!! : ALL DATES IN THIS MODULE ARE STORED IN UTC !
 * DO NOT CONVERT DURING THE TRANSITION FROM CHAMILO 1.8.x TO 2.0
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 * @author Julio Montoya <gugli100@gmail.com> token support improving UI
 *
 * @package chamilo.course_progress
 */
class ThematicController
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->toolname = 'course_progress';
        $this->view = new View($this->toolname);
    }

    /**
     * This method is used for thematic control (update, insert or listing)
     * @param 	string	$action
     * render to thematic.php
     */
    public function thematic($action)
    {
        $thematic = new Thematic();
        $data = array();
        $check = Security::check_token('request');
        $thematic_id = isset($_REQUEST['thematic_id']) ? intval($_REQUEST['thematic_id']) : null;
        $displayHeader = !empty($_REQUEST['display']) && $_REQUEST['display'] === 'no_header' ? false : true;

        if ($check) {
            switch ($action) {
                case 'thematic_add':
                case 'thematic_edit':
                    // insert or update a thematic
                    if (strtoupper($_SERVER['REQUEST_METHOD']) == "POST") {
                        if (trim($_POST['title']) !== '') {
                            if (api_is_allowed_to_edit(null, true)) {

                                $id = isset($_POST['thematic_id']) ? $_POST['thematic_id'] : null;
                                $title = trim($_POST['title']);
                                $content = trim($_POST['content']);
                                $session_id = api_get_session_id();
                                $thematic->set_thematic_attributes($id, $title, $content, $session_id);
                                $last_id = $thematic->thematic_save();
                                if ($_POST['action'] == 'thematic_add') {
                                    $action = 'thematic_details';
                                    $thematic_id = null;
                                    if ($last_id) {
                                        $data['last_id'] = $last_id;
                                    }
                                } else {
                                    $action = 'thematic_details';
                                    $thematic_id = null;
                                }
                            }
                        } else {
                            $error = true;
                            $data['error'] = $error;
                            $data['action'] = $_POST['action'];
                            $data['thematic_id'] = $_POST['thematic_id'];
                            // render to the view
                            $this->view->set_data($data);
                            $this->view->set_layout('layout');
                            $this->view->set_template('thematic');
                            $this->view->render();
                        }
                    }
                    break;
                case 'thematic_copy':
                    //Copy a thematic to a session
                    $thematic->copy($thematic_id);
                    $thematic_id = null;
                    $action = 'thematic_details';
                    break;
                case 'thematic_delete_select':
                    //Delete many thematics
                    if (strtoupper($_SERVER['REQUEST_METHOD']) == "POST") {
                        if (api_is_allowed_to_edit(null, true)) {
                            $thematic_ids = $_POST['id'];
                            $affected_rows = $thematic->thematic_destroy($thematic_ids);
                        }
                        $action = 'thematic_details';
                    }
                    break;
                case 'thematic_delete':
                    // Delete a thematic
                    if (isset($thematic_id)) {
                        if (api_is_allowed_to_edit(null, true)) {
                            $thematic->thematic_destroy($thematic_id);
                        }
                        $thematic_id = null;
                        $action = 'thematic_details';
                    }
                    break;
                case 'thematic_import_select':
                    break;
                case 'thematic_import':
                    $csv_import_array = Import::csv_reader($_FILES['file']['tmp_name'], false);

                    if (isset($_POST['replace']) && $_POST['replace']) {
                        // Remove current thematic.
                        $list = $thematic->get_thematic_list();
                        foreach ($list as $i) {
                            $thematic->thematic_destroy($i);
                        }
                    }

                    // Import the progress.
                    $current_thematic = null;

                    foreach ($csv_import_array as $key => $item) {
                        if (!$key) {
                            continue;
                        }

                        switch ($item[0]) {
                            case 'title':
                                $thematic->set_thematic_attributes(
                                    null,
                                    $item[1],
                                    $item[2],
                                    api_get_session_id()
                                );
                                $current_thematic = $thematic->thematic_save();
                                $description_type = 0;
                                break;
                            case 'plan':
                                $thematic->set_thematic_plan_attributes(
                                    $current_thematic,
                                    $item[1],
                                    $item[2],
                                    $description_type
                                );
                                $thematic->thematic_plan_save();
                                $description_type++;
                                break;
                            case 'progress':
                                $thematic->set_thematic_advance_attributes(
                                    null,
                                    $current_thematic,
                                    0,
                                    $item[3],
                                    $item[1],
                                    $item[2]
                                );
                                $thematic->thematic_advance_save();
                                break;
                        }
                    }

                    $action = 'thematic_details';
                    break;
                case 'thematic_export':
                    $list = $thematic->get_thematic_list();
                    $csv = array();
                    $csv[] = array('type', 'data1', 'data2', 'data3');
                    foreach ($list as $theme) {
                        $csv[] = array('title', $theme['title'], $theme['content']);
                        $data = $thematic->get_thematic_plan_data($theme['id']);
                        if (!empty($data)) {
                            foreach ($data as $plan) {
                                if (empty($plan['description'])) {
                                    continue;
                                }

                                $csv[] = [
                                    'plan',
                                    strip_tags($plan['title']),
                                    strip_tags($plan['description'])
                                ];
                            }
                        }
                        $data = $thematic->get_thematic_advance_by_thematic_id($theme['id']);
                        if (!empty($data)) {
                            foreach ($data as $advance) {
                                $csv[] = array(
                                    'progress',
                                    strip_tags($advance['start_date']),
                                    strip_tags($advance['duration']),
                                    strip_tags($advance['content']),
                                );
                            }
                        }
                    }
                    Export::arrayToCsv($csv);
                    exit;
                    // Don't continue building a normal page.
                    return;
                case 'thematic_export_pdf':
                    $list = $thematic->get_thematic_list();
                    $table = array();
                    $table[] = array(
                        get_lang('Thematic'),
                        get_lang('ThematicPlan'),
                        get_lang('ThematicAdvance')
                    );
                    foreach ($list as $theme) {
                        $data = $thematic->get_thematic_plan_data($theme['id']);
                        $plan_html = null;
                        if (!empty($data)) {
                            foreach ($data as $plan) {
                                if (empty($plan['description'])) {
                                    continue;
                                }

                                $plan_html .= '<strong>' . $plan['title'] . '</strong><br /> ' . $plan['description'] . '<br />';
                            }
                        }
                        $data = $thematic->get_thematic_advance_by_thematic_id($theme['id']);
                        $advance_html = null;
                        if (!empty($data)) {
                            foreach ($data as $advance) {
                                $advance_html .= api_convert_and_format_date($advance['start_date'], DATE_FORMAT_LONG) . ' ('.$advance['duration'].' '.get_lang('HourShort').')<br />'.$advance['content'].'<br />';
                            }
                        }
                        $table[] = array($theme['title'], $plan_html, $advance_html);
                    }
                    $params = array(
                        'filename' => get_lang('Thematic') . '-' . api_get_local_time(),
                        'pdf_title' => get_lang('Thematic'),
                        'add_signatures' => true,
                        'format' => 'A4-L',
                        'orientation' => 'L'
                    );
                    Export::export_table_pdf($table, $params);
                    break;
                case 'moveup':
                    $thematic->move_thematic('up', $thematic_id);
                    $action = 'thematic_details';
                    $thematic_id = null;
                    break;
                case 'movedown':
                    $thematic->move_thematic('down', $thematic_id);
                    $action = 'thematic_details';
                    $thematic_id = null;
                    break;
            }
            Security::clear_token();
        } else {
            $action = 'thematic_details';
            $thematic_id = null;
        }
        if (isset($thematic_id)) {
            $data['thematic_data'] = $thematic->get_thematic_list($thematic_id);
            $data['thematic_id'] = $thematic_id;
        }

        if ($action == 'thematic_details') {
            if (isset($thematic_id)) {
                $thematic_data_result = $thematic->get_thematic_list($thematic_id);
                if (!empty($thematic_data_result)) {
                    $thematic_data[$thematic_id] = $thematic_data_result;
                }
                $data['total_average_of_advances'] = $thematic->get_average_of_advances_by_thematic($thematic_id);
            } else {
                $thematic_data = $thematic->get_thematic_list(null, api_get_course_id(), api_get_session_id());
                $data['max_thematic_item'] = $thematic->get_max_thematic_item();
                $data['last_done_thematic_advance'] = $thematic->get_last_done_thematic_advance();
                $data['total_average_of_advances'] = $thematic->get_total_average_of_thematic_advances();
            }

            // Second column
            $thematic_plan_data = $thematic->get_thematic_plan_data();

            // Third column
            $thematic_advance_data = $thematic->get_thematic_advance_list(null, null, true);

            $data['thematic_plan_div'] = $thematic->get_thematic_plan_div($thematic_plan_data);
            $data['thematic_advance_div'] = $thematic->get_thematic_advance_div($thematic_advance_data);
            $data['thematic_plan_data'] = $thematic_plan_data;
            $data['thematic_advance_data'] = $thematic_advance_data;
            $data['thematic_data'] = $thematic_data;
        }

        $data['default_thematic_plan_title'] = $thematic->get_default_thematic_plan_title();

        $data['action'] = $action;
        $layoutName = $displayHeader ? 'layout' : 'layout_no_header';

        // render to the view
        $this->view->set_data($data);
        $this->view->set_layout($layoutName);
        $this->view->set_template('thematic');
        $this->view->render();
    }

    /**
     * This method is used for thematic plan control (update, insert or listing)
     * @param 	string	$action
     * render to thematic_plan.php
     */
    public function thematic_plan($action)
    {
        $thematic = new Thematic();
        $data = array();
        if (strtoupper($_SERVER['REQUEST_METHOD']) == "POST") {
            if (isset($_POST['action']) && ($_POST['action'] == 'thematic_plan_add' || $_POST['action'] == 'thematic_plan_edit')) {
                if (isset($_POST['title'])) {
                    if ($_POST['thematic_plan_token'] == $_SESSION['thematic_plan_token']) {
                        if (api_is_allowed_to_edit(null, true)) {
                            $title_list = $_REQUEST['title'];
                            $description_list = $_REQUEST['description'];
                            $description_type = $_REQUEST['description_type'];
                            for ($i = 1; $i < count($title_list) + 1; $i++) {
                                $thematic->set_thematic_plan_attributes(
                                    $_REQUEST['thematic_id'],
                                    $title_list[$i],
                                    $description_list[$i],
                                    $description_type[$i]
                                );
                                $thematic->thematic_plan_save();
                            }
                            unset($_SESSION['thematic_plan_token']);
                            $data['message'] = 'ok';

                            $saveRedirect = api_get_path(WEB_PATH) . 'main/course_progress/index.php?';
                            $saveRedirect.= api_get_cidreq() . '&';
                            $saveRedirect.= 'thematic_plan_save_message=ok';

                            header("Location: $saveRedirect");
                            exit;
                        }
                        $data['action'] = 'thematic_plan_list';
                    }
                } else {
                    $error = true;
                    $action = $_POST['action'];
                    $data['error'] = $error;
                    $data['thematic_plan_data'] = $thematic->get_thematic_plan_data($_POST['thematic_id'], $_POST['description_type']);
                    $data['thematic_id'] = $_POST['thematic_id'];
                    $data['description_type'] = $_POST['description_type'];
                    $data['action'] = $action;
                    $data['default_thematic_plan_title'] = $thematic->get_default_thematic_plan_title();
                    $data['default_thematic_plan_icon'] = $thematic->get_default_thematic_plan_icon();
                    $data['default_thematic_plan_question'] = $thematic->get_default_question();
                    $data['next_description_type'] = $thematic->get_next_description_type($_POST['thematic_id']);

                    // render to the view
                    $this->view->set_data($data);
                    $this->view->set_layout('layout');
                    $this->view->set_template('thematic_plan');
                    $this->view->render();
                }
            }
        }

        $thematic_id = intval($_GET['thematic_id']);

        if ($action == 'thematic_plan_list') {
            $data['thematic_plan_data'] = $thematic->get_thematic_plan_data($thematic_id);
        }

        $description_type = isset($_GET['description_type']) ? intval($_GET['description_type']) : null;

        if (!empty($thematic_id) && !empty($description_type)) {
            if ($action === 'thematic_plan_delete') {
                if (api_is_allowed_to_edit(null, true)) {
                    $thematic->thematic_plan_destroy($thematic_id, $description_type);
                }
                $data['thematic_plan_data'] = $thematic->get_thematic_plan_data($thematic_id);
                $action = 'thematic_plan_list';
            } else {
                $data['thematic_plan_data'] = $thematic->get_thematic_plan_data($thematic_id, $description_type);
            }
            $data['thematic_id'] = $thematic_id;
            $data['description_type'] = $description_type;
        } else if (!empty($thematic_id) && $action === 'thematic_plan_list') {
            $data['thematic_plan_data'] = $thematic->get_thematic_plan_data($thematic_id);
            $data['thematic_id'] = $thematic_id;
        }

        $data['thematic_id'] = $thematic_id;
        $data['action'] = $action;
        $data['default_thematic_plan_title'] = $thematic->get_default_thematic_plan_title();
        $data['default_thematic_plan_icon'] = $thematic->get_default_thematic_plan_icon();
        $data['next_description_type'] = $thematic->get_next_description_type($thematic_id);
        $data['default_thematic_plan_question'] = $thematic->get_default_question();
        $data['thematic_data'] = $thematic->get_thematic_list($thematic_id);

        //render to the view
        $this->view->set_data($data);
        $this->view->set_layout('layout');
        $this->view->set_template('thematic_plan');
        $this->view->render();
        exit;
    }

    /**
     * This method is used for thematic advance control (update, insert or listing)
     * render to thematic_advance.php
     * @param    string $action
     *
     */
    public function thematic_advance($action)
    {
        $thematic = new Thematic();
        $attendance = new Attendance();
        $data = array();

        $displayHeader = (!empty($_REQUEST['display']) && $_REQUEST['display'] === 'no_header') ? false : true;

        // get data for attendance input select
        $attendance_list = $attendance->get_attendances_list();
        $attendance_select = array();
        $attendance_select[0] = get_lang('SelectAnAttendance');
        foreach ($attendance_list as $attendance_id => $attendance_data) {
            $attendance_select[$attendance_id] = $attendance_data['name'];
        }

        $thematic_id = intval($_REQUEST['thematic_id']);
        $thematic_advance_id = isset($_REQUEST['thematic_advance_id']) ? intval($_REQUEST['thematic_advance_id']) : null;
        $thematic_advance_data = array();

        switch ($action) {
            case 'thematic_advance_delete':
                if (!empty($thematic_advance_id)) {
                    if (api_is_allowed_to_edit(null, true)) {
                        $thematic->thematic_advance_destroy($thematic_advance_id);
                    }
                    header('Location: index.php');
                    exit;
                }
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
        $calendar_select = array();
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

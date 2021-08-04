<?php
/**
 * This file is part of course block plugin for dashboard,
 * it should be required inside dashboard controller for showing it into dashboard interface from plattform.
 *
 * @package chamilo.dashboard
 *
 * @author Marco Sousa original code
 * @author Julio Montoya class named was changed of name, and some minor changes
 */

/**
 * required files for getting data.
 */

/**
 * This class is used like controller for this course block plugin,
 * the class name must be registered inside path.info file
 * (e.g: controller = "BlockDiario"), so dashboard controller will be instantiate it.
 *
 * @package chamilo.dashboard
 */
class BlockDaily extends Block
{
    private $user_id;
    private $courses;
    private $path;
    private $permission = [DRH];

    /**
     * Constructor.
     */
    public function __construct($user_id)
    {
        $this->user_id = $user_id;
        $this->path = 'block_daily';
        if ($this->is_block_visible_for_user($user_id)) {
            $this->courses = CourseManager::get_courses_followed_by_drh(
                $user_id
            );
        }
    }

    /**
     * This method check if a user is allowed to see the block inside dashboard interface.
     *
     * @param    int        User id
     *
     * @return bool Is block visible for user
     */
    public function is_block_visible_for_user($user_id)
    {
        $user_info = api_get_user_info($user_id);
        $user_status = $user_info['status'];
        $is_block_visible_for_user = false;
        if (UserManager::is_admin($user_id) || in_array(
                $user_status,
                $this->permission
            )
        ) {
            $is_block_visible_for_user = true;
        }

        return $is_block_visible_for_user;
    }

    /**
     * This method return content html containing information about courses and its position for showing it inside dashboard interface
     * it's important to use the name 'get_block' for beeing used from dashboard controller.
     *
     * @return array column and content html
     */
    public function get_block()
    {
        global $charset;
        $column = 2;
        $data = [];
        $content = $this->get_content_html();
        $html = '<div class="panel panel-default" id="intro">
		                <div class="panel-heading">'.get_lang('GradebookAndAttendances').'
		                    <div class="pull-right"><a class="btn btn-danger btn-xs" onclick="javascript:if(!confirm(\''.addslashes(
                api_htmlentities(
                    get_lang('ConfirmYourChoice'),
                    ENT_QUOTES,
                    $charset
                )
            ).'\')) return false;" href="index.php?action=disable_block&path='.$this->path.'">
                    <em class="fa fa-times"></em>
                        </a></div>
		                </div>
		                <div class="panel-body">
		                   '.$content.'
		                </div>
		            </div>
				';
        $data['column'] = $column;
        $data['content_html'] = $html;

        return $data;
    }

    /**
     * This method return a content html, it's used inside get_block method for showing it inside dashboard interface.
     *
     * @return string content html
     */
    public function get_content_html()
    {
        $course_data = $this->get_course_information_data();
        $content = '<h4>'.get_lang('YourCourseList').'</h4>';
        $data_table = null;
        if (!empty($course_data)) {
            $data_table .= '<table class="table table-hover table-striped data_table" width:"95%">';
            $data_table .= '<tr>
	    						<th>'.get_lang('CourseTitle').'</th>
	    						<th width="20%">'.get_lang('NbStudents').'</th>
	    						<th width="20%">'.get_lang('Evaluation').'</th>
	    						<th width="20%">'.get_lang('ToolAttendance').'</th>
	    					</tr>';
            $i = 1;
            foreach ($course_data as $course) {
                if ($i % 2 == 0) {
                    $class_tr = 'row_odd';
                } else {
                    $class_tr = 'row_even';
                }
                $data_table .= '<tr class="'.$class_tr.'">';
                if (!isset($course[3])) {
                    $course[3] = get_lang('NotAvailable');
                }
                foreach ($course as $cell) {
                    $data_table .= '<td align="right">'.$cell.'</td>';
                }
                $data_table .= '</tr>';
                $i++;
            }
            $data_table .= '</table>';
        } else {
            $data_table .= get_lang('ThereIsNoInformationAboutYourCourses');
        }
        $content .= $data_table;
        if (!empty($course_data)) {
            $content .= '<div style="text-align:right;margin-top:10px;">
            <a href="'.api_get_path(WEB_CODE_PATH).'mySpace/course.php">'.get_lang('SeeMore').'</a></div>';
        }
        //$content .= '</div>';
        return $content;
    }

    /**
     * Get number of courses.
     *
     * @return int
     */
    public function get_number_of_courses()
    {
        return count($this->courses);
    }

    /**
     * Get course information data.
     *
     * @return array
     */
    public function get_course_information_data()
    {
        $tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $course_data = [];
        $courses = $this->courses;

        foreach ($courses as $row_course) {
            $score = null;
            $courseId = $row_course['c_id'];
            $course_info = api_get_course_info_by_id($courseId);
            $course_code = $course_info['code'];
            if (empty($course_info)) {
                continue;
            }

            // Attendance table
            $table_course = Database::get_course_table(TABLE_ATTENDANCE);

            $sql = "SELECT id, name, attendance_qualify_max FROM $table_course
                    WHERE c_id = ".$course_info['real_id']." AND active = 1 AND session_id = 0";
            $rs = Database::query($sql);
            $attendance = [];
            $attendances = [];

            while ($row = Database::fetch_array($rs, 'ASSOC')) {
                $attendance['done'] = $row['attendance_qualify_max'];
                $attendance['id'] = $row['id'];
                //$attendance['name'] = $row['name'];
                $attendance['course_code'] = $course_info['code'];

                if ($attendance['done'] != '0') {
                    $attendances[] = '<a href="'.api_get_path(WEB_PATH).'main/attendance/index.php?cidReq='.$attendance['course_code'].'&action=attendance_sheet_print&attendance_id='.$attendance['id'].'">'.Display::return_icon('printmgr.gif', get_lang('Print')).'</a>';
                } else {
                    $attendances[] = get_lang("NotAvailable");
                }
            }
            if (count($attendances) == 0) {
                $attendances[] = get_lang("NotAvailable");
            }

            // Number of students

            $sql = "SELECT user_id FROM $tbl_course_user as course_rel_user
                    WHERE course_rel_user.status=".STUDENT." AND course_rel_user.c_id=$courseId";
            $rs = Database::query($sql);
            $users = [];
            while ($row = Database::fetch_array($rs)) {
                $users[] = $row['user_id'];
            }
            if (count($users) > 0) {
                $nb_students_in_course = count($users);
            }

            if (!empty($tematic_advance)) {
                $tematic_advance_progress = '<a title="'.get_lang(
                        'GoToThematicAdvance'
                    ).'" href="'.api_get_path(
                        WEB_CODE_PATH
                    ).'attendance/index.php?cidReq='.$course_code.'&action=attendance_sheet_print&attendance_id=">'.$tematic_advance.'%</a>';
            } else {
                $tematic_advance_progress = '0%';
            }

            // Score
            $tbl_grade_categories = Database::get_main_table(
                TABLE_MAIN_GRADEBOOK_CATEGORY
            );
            $sql = "SELECT id from ".$tbl_grade_categories."
                    WHERE course_code ='".$course_code."'";
            $rs = Database::query($sql);
            $category = null;
            while ($row = Database::fetch_array($rs)) {
                $category = $row['id'];
            }

            if (!empty($category)) {
                $cat = Category::load($category);
                $eval = $cat[0]->get_evaluations();
                if (count($eval) > 0) {
                    $i = 0;
                    foreach ($eval as $item) {
                        $score .= '<a href="'.api_get_path(WEB_PATH).'main/gradebook/gradebook_view_result.php?export=pdf&cat_code='.$cat[0]->get_id().'&official_code='.$cat[0]->get_course_code().'&selecteval='.$item->get_id().'">'.$item->get_name().'</a>';
                        if (count($eval) - 1 != $i) {
                            $score .= ', ';
                        }
                        $i++;
                    }
                } else {
                    $score = get_lang("NotAvailable");
                }
            } else {
                $score = get_lang("NotAvailable");
            }

            $table_row = [];
            $table_row[] = $row_course['title'];
            $table_row[] = $nb_students_in_course;
            $table_row[] = $score;
            $table_row[] = $attendances[0];
            $course_data[] = $table_row;
        }

        return $course_data;
    }
}

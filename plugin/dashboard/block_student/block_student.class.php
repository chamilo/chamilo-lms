<?php
/**
 * This file is part of student block plugin for dashboard,
 * it should be required inside dashboard controller for showing it into dashboard interface from plattform.
 *
 * @package chamilo.dashboard
 *
 * @author Christian Fasanando
 */

/**
 * This class is used like controller for student block plugin,
 * the class name must be registered inside path.info file
 * (e.g: controller = "BlockStudent"), so dashboard controller will be instantiate it.
 *
 * @package chamilo.dashboard
 */
class BlockStudent extends Block
{
    private $user_id;
    private $students;
    private $permission = [DRH];

    /**
     * Constructor.
     */
    public function __construct($user_id)
    {
        $this->user_id = $user_id;
        $this->path = 'block_student';
        if ($this->is_block_visible_for_user($user_id)) {
            $this->students = UserManager::get_users_followed_by_drh($user_id, STUDENT);
        }
    }

    /**
     * This method check if a user is allowed to see the block inside dashboard interface.
     *
     * @param int        User id
     *
     * @return bool Is block visible for user
     */
    public function is_block_visible_for_user($user_id)
    {
        $user_info = api_get_user_info($user_id);
        $user_status = $user_info['status'];
        $is_block_visible_for_user = false;
        if (UserManager::is_admin($user_id) || in_array($user_status, $this->permission)) {
            $is_block_visible_for_user = true;
        }

        return $is_block_visible_for_user;
    }

    /**
     * This method return content html containing information
     * about students and its position for showing it inside dashboard interface
     * it's important to use the name 'get_block' for beeing used from dashboard controller.
     *
     * @return array column and content html
     */
    public function get_block()
    {
        $column = 1;
        $data = [];
        $html = $this->getBlockCard(
            get_lang('Your learners'),
            $this->getContent()
        );
        $data['column'] = $column;
        $data['content_html'] = $html;

        return $data;
    }

    /**
     * This method return a content html, it's used inside get_block method for showing it inside dashboard interface.
     *
     * @return string content html
     */
    public function getContent()
    {
        $students = $this->students;
        $students_table = null;
        if (count($students) > 0) {
            $students_table .= '<table class="data_table">';
            $students_table .= '<tr>
                                    <th width="10%" rowspan="2">'.get_lang('First name').'</th>
                                    <th width="10%" rowspan="2">'.get_lang('Last name').'</th>
                                    <th width="30%" colspan="2">'.get_lang('Course Information').'</th>
                                </tr>
                                <tr>
                                    <th width="10%">'.get_lang('Courses').'</th>
                                    <th width="10%">'.get_lang('Time').'</th>
                                </tr>';

            $i = 1;
            foreach ($students as $student) {
                $courses_by_user = CourseManager::get_courses_list_by_user_id($student['user_id'], true);
                $count_courses = count($courses_by_user);
                $rowspan = $count_courses ? $count_courses + 1 : 2;

                if ($i % 2 == 0) {
                    $style = ' style="background-color:#F2F2F2" ';
                } else {
                    $style = ' style="background-color:#FFF" ';
                }

                $students_table .= '<tr '.$style.'>
                                        <td rowspan="'.$rowspan.'">'.$student['firstname'].'</td>
                                        <td rowspan="'.$rowspan.'">'.$student['lastname'].'</td>
                                    </tr>';

                // courses information about the student
                if (!empty($courses_by_user)) {
                    foreach ($courses_by_user as $course) {
                        $course_code = $course['code'];
                        $courseInfo = api_get_course_info($course_code);
                        $courseId = $courseInfo['real_id'];
                        $course_title = $course['title'];
                        $time = api_time_to_hms(Tracking::get_time_spent_on_the_course($student['user_id'], $courseId));
                        $students_table .= '<tr '.$style.'>
                                            <td align="right">'.$course_title.'</td>
                                            <td align="right">'.$time.'</td>
                                            </tr>';
                    }
                } else {
                    $students_table .= '<tr '.$style.'>
                                            <td align="center" colspan="2"><i>'.get_lang('You left some fields empty.<br>Use the <b>Back</b> button on your browser and try again.<br>If you ignore your training code, see the Training Program').'</i></td>
                                        </tr>';
                }
                $i++;
            }
            $students_table .= '</table>';
        } else {
            $students_table .= get_lang('ThereIsNoInformationAboutYour learners');
        }

        $content = $students_table;

        if (count($students) > 0) {
            $content .= '<div style="text-align:right;margin-top:10px;"><a href="'.api_get_path(WEB_CODE_PATH).'mySpace/index.php?view=admin&display=useroverview">'.get_lang('See more').'</a></div>';
        }

        return $content;
    }

    /**
     * @return string
     */
    public function get_students_content_html_for_drh()
    {
        $attendance = new Attendance();
        $students = $this->students;
        $content = '<h4>'.get_lang('Your learners').'</h4>';
        $students_table = null;
        if (count($students) > 0) {
            $students_table .= '<table class="data_table">';
            $students_table .= '<tr>
                                    <th>'.get_lang('User').'</th>
                                    <th>'.get_lang('Not attended').'</th>
                                    <th>'.get_lang('Evaluations').'</th>
                                </tr>';
            $i = 1;
            foreach ($students as $student) {
                $student_id = $student['user_id'];
                $firstname = $student['firstname'];
                $lastname = $student['lastname'];
                $username = $student['username'];
                // get average of faults in attendances by student
                $results_faults_avg = $attendance->get_faults_average_inside_courses($student_id);
                if (!empty($results_faults_avg)) {
                    $attendances_faults_avg = '<a title="'.get_lang('Go to learner details').'" href="'.api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?student='.$student_id.'">'.$results_faults_avg['faults'].'/'.$results_faults_avg['total'].' ('.$results_faults_avg['porcent'].'%)</a>';
                } else {
                    $attendances_faults_avg = '0%';
                }

                $courses_by_user = CourseManager::get_courses_list_by_user_id($student_id, true);
                $evaluations_avg = 0;
                $score = $weight = 0;
                foreach ($courses_by_user as $course) {
                    $course_code = $course['code'];
                    $cats = Category::load(
                        null,
                        null,
                        $course_code,
                        null,
                        null,
                        null,
                        false
                    );
                    $scoretotal = [];
                    if (isset($cats) && isset($cats[0])) {
                        $scoretotal = $cats[0]->calc_score($student_id, null, $course_code);
                    }

                    if (!empty($scoretotal)) {
                        $score += $scoretotal[0];
                        $weight += $scoretotal[1];
                    }
                }

                if (!empty($weight)) {
                    $evaluations_avg = '<a title="'.get_lang('Go to learner details').'" href="'.api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?student='.$student_id.'">'.round($score, 2).'/'.round($weight, 2).'('.round(($score / $weight) * 100, 2).' %)</a>';
                }

                if ($i % 2 == 0) {
                    $class_tr = 'row_odd';
                } else {
                    $class_tr = 'row_even';
                }
                $students_table .= '<tr class="'.$class_tr.'">
                                        <td>'.api_get_person_name($firstname, $lastname).' ('.$username.')</td>
                                        <td>'.$attendances_faults_avg.'</td>
                                        <td>'.$evaluations_avg.'</td>
                                    </tr>';

                $i++;
            }
            $students_table .= '</table>';
        } else {
            $students_table .= get_lang('ThereIsNoInformationAboutYour learners');
        }

        $content .= $students_table;

        if (count($students) > 0) {
            $content .= '<div style="text-align:right;margin-top:10px;">
                            <a href="'.api_get_path(WEB_CODE_PATH).'mySpace/index.php?view=admin&display=yourstudents">'.get_lang('See more').'</a>
                         </div>';
        }
        //$content .= '</div>';

        return $content;
    }

    /**
     * Get number of students.
     *
     * @return int
     */
    public function get_number_of_students()
    {
        return count($this->students);
    }
}

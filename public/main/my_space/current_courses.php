<?php

/* For licensing terms, see /license.txt */

/**
 * Report for current courses followed by the user.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_TRACKING;
$filename = 'reporting';

if (!api_is_allowed_to_create_course()) {
    api_not_allowed(true);
}

$user_id = api_get_user_id();
$my_courses = CourseManager::get_course_list_of_user_as_course_admin($user_id);
$array = [];

$i = 0;
$session_id = 0;
if (!empty($my_courses)) {
    foreach ($my_courses as $course) {
        $course_code = $course['code'];
        $course_id = $course['real_id'];
        $course_info = api_get_course_info($course_code);

        //Only show open courses
        if (0 == $course_info['visibility']) {
            continue;
        }

        $teachers = CourseManager::get_teacher_list_from_course_code($course_code);
        $teacher_list = [];

        if (!empty($teachers)) {
            foreach ($teachers as $teacher) {
                $teacher_list[] = $teacher['firstname'].' '.$teacher['lastname'];
            }
        }

        $tmp_students = CourseManager :: get_student_list_from_course_code($course_code, false);

        //Cleaning students only REAL students
        $students = [];
        foreach ($tmp_students as $student) {
            $user_info = api_get_user_info($student['user_id']);
            if (STUDENT != $user_info['status']) {
                continue;
            }
            $students[] = $student['user_id'];
        }

        $t_lp = Database::get_course_table(TABLE_LP_MAIN);
        $sql_lp = "SELECT lp.name, lp.id FROM $t_lp lp
                   WHERE c_id = $course_id AND lp.session_id = 0";
        $rs_lp = Database::query($sql_lp);
        $t_lpi = Database::get_course_table(TABLE_LP_ITEM);
        $t_news = Database::get_course_table(TABLE_ANNOUNCEMENT);

        $total_tools_list = Tracking::get_tools_most_used_by_course(
            $course_id,
            $session_id
        );

        $total_tools = 0;
        foreach ($total_tools_list as $tool) {
            $total_tools += $tool['count_access_tool'];
        }

        if (Database :: num_rows($rs_lp) > 0) {
            while ($learnpath = Database :: fetch_array($rs_lp)) {
                $lp_id = $learnpath['id'];

                $lp_items =
                $array[$i]['lp'] = '<a href="'.api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?cidReq='.$course_code.'&amp;action=view&amp;lp_id='.$lp_id.'" target="_blank">'.$learnpath['name'].'</a>';

                $array[$i]['teachers'] = '';
                if (!empty($teacher_list)) {
                    $array[$i]['teachers'] = implode(', ', $teacher_list);
                }

                $array[$i]['course_name'] = $course['title'];
                $count_students_accessing = 0;
                $count_students_complete_all_activities = 0;
                $count_students_complete_all_activities_at_50 = 0;
                $total_time_spent = 0;
                $total_average_progress = 0;

                if (!empty($students)) {
                    foreach ($students  as $student_id) {
                        $avg_student_progress = Tracking::get_avg_student_progress($student_id, $course_code, [$lp_id], $session_id);
                        $myavg_temp = Tracking::get_avg_student_score($student_id, $course_code, [$lp_id], $session_id);
                        $avg_progress_in_course = Tracking::get_avg_student_progress($student_id, $course_code, [$lp_id], $session_id);

                        if (100 == intval($avg_progress_in_course)) {
                            $count_students_complete_all_activities++;
                        }
                        if (intval($avg_progress_in_course) > 0 && intval($avg_progress_in_course) <= 50) {
                            $count_students_complete_all_activities_at_50++;
                        }
                        $total_average_progress += $avg_progress_in_course;

                        $time_spent = Tracking::get_time_spent_on_the_course($student_id, $course_id, $session_id);
                        $total_time_spent += $time_spent;
                        if (!empty($time_spent)) {
                            $count_students_accessing++;
                        }
                    }
                    //$total_tools += $nb_assignments +  $messages + $links + $chat_last_connection + $documents;
                }

                $student_count = count($students);

                $array[$i]['count_students'] = $student_count;
                $array[$i]['count_students_accessing'] = 0;
                $array[$i]['count_students_accessing_percentage'] = 0;
                $array[$i]['count_students_complete_all_activities_at_50'] = 0;
                $array[$i]['count_students_complete_all_activities'] = 0;
                $array[$i]['average_percentage_activities_completed_per_student'] = 0;
                $array[$i]['total_time_spent'] = 0;
                $array[$i]['average_time_spent_per_student'] = 0;
                $array[$i]['total_time_spent'] = 0;
                $array[$i]['average_time_spent_per_student'] = 0;
                //$array[$i]['tools_used'] = 0;
                $array[$i]['learnpath_docs'] = 0;
                $array[$i]['learnpath_exercises'] = 0;
                $array[$i]['learnpath_links'] = 0;
                $array[$i]['learnpath_forums'] = 0;
                $array[$i]['learnpath_assignments'] = 0;

                //registering the number of each category of
                //items in learning path
                $sql_lpi = "SELECT lpi.item_type FROM $t_lpi lpi
                            WHERE c_id = $course_id AND lpi.lp_id = $lp_id
                            ORDER BY item_type";
                $res_lpi = Database::query($sql_lpi);
                while ($row_lpi = Database::fetch_array($res_lpi)) {
                    switch ($row_lpi['item_type']) {
                        case 'document':
                            $array[$i]['learnpath_docs']++;
                            break;
                        case 'quiz':
                            $array[$i]['learnpath_exercises']++;
                            break;
                        case 'link':
                            $array[$i]['learnpath_links']++;
                            break;
                        case 'forum':
                        case 'thread':
                            $array[$i]['learnpath_forums']++;
                            break;
                        case 'student_publication':
                            $array[$i]['learnpath_assignments']++;
                            break;
                    }
                }
                // Count announcements
                $array[$i]['total_announcements'] = 0;
                $sql_news = "SELECT count(id) FROM $t_news WHERE c_id = $course_id ";
                $res_news = Database::query($sql_news);
                while ($row_news = Database::fetch_array($res_news)) {
                    $array[$i]['total_announcements'] = $row_news[0];
                }

                //@todo don't know what means this value
                $count_students_complete_all_activities_at_50 = 0;

                if (!empty($student_count)) {
                    $array[$i]['count_students_accessing'] = $count_students_accessing;
                    $array[$i]['count_students_accessing_percentage'] = round($count_students_accessing / $student_count * 100, 0);
                    $array[$i]['count_students_complete_all_activities_at_50'] = $count_students_complete_all_activities;
                    $array[$i]['count_students_complete_all_activities'] = round($count_students_complete_all_activities / $student_count * 100, 0);

                    $array[$i]['average_percentage_activities_completed_per_student'] = round($count_students_complete_all_activities / $student_count * 100, 2);
                    $array[$i]['total_time_spent'] = 0;
                    $array[$i]['average_time_spent_per_student'] = 0;

                    if (!empty($total_time_spent)) {
                        $array[$i]['total_time_spent'] = api_time_to_hms($total_time_spent);
                        $array[$i]['average_time_spent_per_student'] = api_time_to_hms($total_time_spent / $student_count);
                    }
                    //$array[$i]['tools_used'] = $total_tools;
                }
                $i++;
            }
        }
    }
}

$headers = [
    get_lang('Learning paths'),
    get_lang('Trainers'),
    get_lang('Courses'),
    get_lang('Number of learners'),
    get_lang('Number of learners accessing the course'),
    get_lang('Percentage of learners accessing the course'),
    get_lang('Number of learners who completed all activities (100% progress)'),
    get_lang('Percentage of learners who completed all activities (100% progress)'),
    get_lang('Average number of activities completed per learner'),
    get_lang('Total time spent in the course'),
    get_lang('Average time spent per learner in the course'),
    get_lang('Number of documents in learning path'),
    get_lang('Number of exercises in learning path'),
    get_lang('Number of links in learning path'),
    get_lang('Number of forums in learning path'),
    get_lang('Number of assignments in learning path'),
    get_lang('Number of announcements in course'),
];

if (isset($_GET['export'])) {
    $list = [
        0 => $headers,
        1 => $array[0],
    ];
    Export::arrayToXls($list, $filename);
    exit;
}

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Reporting')];
Display::display_header(get_lang('Current courses'));

$table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
$row = 0;
$column = 0;
foreach ($headers as $header) {
    $table->setHeaderContents($row, $column, $header);
    $column++;
}
$row++;

foreach ($array as $row_table) {
    $column = 0;
    foreach ($row_table as $cell) {
        $table->setCellContents($row, $column, $cell);
        //$table->updateCellAttributes($row, $column, 'align="center"');
        $column++;
    }
    $table->updateRowAttributes($row, $row % 2 ? 'class="row_even"' : 'class="row_odd"', true);
    $row++;
}

echo '<div class="actions">';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'mySpace">'.Display::return_icon('back.png', get_lang('Back'), [], 32).'</a>';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'mySpace/current_courses.php?export=1">'.Display::return_icon('export_excel.png', get_lang('Current coursesReport'), [], 32).'</a> ';
echo '</div>';
echo '<div style="overflow:auto;">';
echo $table->toHtml();
echo '</div>';

Display::display_footer();

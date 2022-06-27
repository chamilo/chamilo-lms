<?php
/* For licensing terms, see /license.txt */

/**
 * Exams script.
 */
require_once __DIR__.'/../inc/global.inc.php';

$toolTable = Database::get_course_table(TABLE_TOOL_LIST);
$quizTable = Database::get_course_table(TABLE_QUIZ_TEST);

$this_section = SECTION_TRACKING;
$is_allowedToTrack = api_is_course_admin() || api_is_platform_admin(true) || $is_session_general_coach;

if (!$is_allowedToTrack) {
    api_not_allowed(true);
}

$exportToXLS = false;
if (isset($_GET['export'])) {
    $exportToXLS = true;
}

if (api_is_platform_admin() && empty($_GET['cidReq'])) {
    $global = true;
} else {
    $global = false;
}

$courseList = [];
if ($global) {
    $temp = CourseManager::get_courses_list();
    foreach ($temp as $tempCourse) {
        $courseInfo = api_get_course_info($tempCourse['code']);
        $courseList[] = $courseInfo;
    }
} else {
    $courseList = [api_get_course_info()];
}

$sessionId = api_get_session_id();

if (empty($sessionId)) {
    $sessionCondition = ' AND (session_id = 0 OR session_id IS NULL)';
} else {
    $sessionCondition = api_get_session_condition($sessionId, true, true);
}

$form = new FormValidator(
    'search_simple',
    'POST',
    api_get_self().'?'.api_get_cidreq(),
    '',
    null,
    false
);
$form->addElement('number', 'score', get_lang('Percentage'));
if ($global) {
    $form->addElement('hidden', 'view', 'admin');
} else {
    // Get exam lists
    $courseId = api_get_course_int_id();

    $sql = "SELECT quiz.title, iid FROM $quizTable AS quiz
            WHERE
                c_id = $courseId AND
                active = 1
                $sessionCondition
            ORDER BY quiz.title ASC";
    $result = Database::query($sql);
    // Only show select bar if there is more than one test
    if (Database::num_rows($result) > 0) {
        $exerciseList = [get_lang('All')];
        while ($row = Database::fetch_array($result)) {
            $exerciseList[$row['iid']] = $row['title'];
        }
        $form->addElement('select', 'exercise_id', get_lang('Exercise'), $exerciseList);
    }
}
$form->addButton('filter', get_lang('Filter'), 'filter', 'primary', null, null, ['style' => 'margin-top: 5px; margin-left: 15px;']);

$filter_score = isset($_REQUEST['score']) ? intval($_REQUEST['score']) : 70;
$exerciseId = isset($_REQUEST['exercise_id']) ? intval($_REQUEST['exercise_id']) : 0;

$form->setDefaults(['score' => $filter_score]);

if (!$exportToXLS) {
    Display::display_header(get_lang('Reporting'));
    $actionsLeft = $actionsRight = '';
    if ($global) {
        $actionsLeft .= '<a href="'.api_get_path(WEB_CODE_PATH).'auth/my_progress.php">'.
        Display::return_icon('statistics.png', get_lang('MyStats'), '', ICON_SIZE_MEDIUM);
        $actionsLeft .= '</a>';
        $courseInfo = api_get_course_info();

        $actionsRight .= '<a href="'.api_get_self().'?export=1&score='.$filter_score.'&exercise_id='.$exerciseId.'">'.
            Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), '', ICON_SIZE_MEDIUM).'</a>';
        $actionsRight .= '<a href="javascript: void(0);" onclick="javascript: window.print()">'.
            Display::return_icon('printer.png', get_lang('Print'), '', ICON_SIZE_MEDIUM).'</a>';

        $menuItems[] = Display::url(
            Display::return_icon('teacher.png', get_lang('TeacherInterface'), [], 32),
            api_get_path(WEB_CODE_PATH).'mySpace/?view=teacher'
        );
        if (api_is_platform_admin()) {
            $menuItems[] = Display::url(
                Display::return_icon('star.png', get_lang('AdminInterface'), [], 32),
                api_get_path(WEB_CODE_PATH).'mySpace/admin_view.php'
            );
        } else {
            $menuItems[] = Display::url(
                Display::return_icon('star.png', get_lang('CoachInterface'), [], 32),
                api_get_path(WEB_CODE_PATH).'mySpace/index.php?view=coach'
            );
        }
        $menuItems[] = '<a href="#">'.Display::return_icon('quiz_na.png', get_lang('ExamTracking'), [], 32).'</a>';

        $nb_menu_items = count($menuItems);
        if ($nb_menu_items > 1) {
            foreach ($menuItems as $key => $item) {
                $actionsLeft .= $item;
            }
        }
    } else {
        $actionsLeft = TrackingCourseLog::actionsLeft('exams', api_get_session_id());

        $actionsRight .= Display::url(
            Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), [], 32),
            api_get_self().'?'.api_get_cidreq().'&export=1&score='.$filter_score.'&exercise_id='.$exerciseId
        );
    }

    $toolbar = Display::toolbarAction('toolbar-exams', [$actionsLeft, $actionsRight]);
    echo $toolbar;

    $form->display();
    echo '<h3>'.sprintf(get_lang('FilteringWithScoreX'), $filter_score).'%</h3>';
}

$html = '<div class="table-responsive">';
$html .= '<table  class="table table-hover table-striped data_table">';
if ($global) {
    $html .= '<tr>';
    $html .= '<th>'.get_lang('Courses').'</th>';
    $html .= '<th>'.get_lang('Quiz').'</th>';
    $html .= '<th>'.get_lang('ExamTaken').'</th>';
    $html .= '<th>'.get_lang('ExamNotTaken').'</th>';
    $html .= '<th>'.sprintf(get_lang('ExamPassX'), $filter_score).'%</th>';
    $html .= '<th>'.get_lang('ExamFail').'</th>';
    $html .= '<th>'.get_lang('TotalStudents').'</th>';
    $html .= '</tr>';
} else {
    $html .= '<tr>';
    $html .= '<th>'.get_lang('Quiz').'</th>';
    $html .= '<th>'.get_lang('User').'</th>';
    $html .= '<th>'.get_lang('Username').'</th>';
    //$html .= '<th>'.sprintf(get_lang('ExamPassX'), $filter_score).'</th>';
    $html .= '<th>'.get_lang('Percentage').' %</th>';
    $html .= '<th>'.get_lang('Status').'</th>';
    $html .= '<th>'.get_lang('Attempts').'</th>';
    $html .= '</tr>';
}

$export_array_global = $export_array = [];
$s_css_class = null;

if (!empty($courseList) && is_array($courseList)) {
    foreach ($courseList as $courseInfo) {
        $sessionList = SessionManager::get_session_by_course($courseInfo['real_id']);

        $newSessionList = [];
        if (!empty($sessionList)) {
            foreach ($sessionList as $session) {
                $newSessionList[$session['id']] = $session['name'];
            }
        }

        $courseId = $courseInfo['real_id'];

        if ($global) {
            $sql = "SELECT count(iid) as count
                    FROM $quizTable AS quiz
                    WHERE c_id = $courseId AND  active = 1 AND (session_id = 0 OR session_id IS NULL)";
            $result = Database::query($sql);
            $countExercises = Database::store_result($result);
            $exerciseCount = $countExercises[0]['count'];

            $sql = "SELECT count(iid) as count
                    FROM $quizTable AS quiz
                    WHERE c_id = $courseId AND active = 1 AND session_id <> 0";
            $result = Database::query($sql);
            $countExercises = Database::store_result($result);
            $exerciseSessionCount = $countExercises[0]['count'];

            $exerciseCount = $exerciseCount + $exerciseCount * count($newSessionList) + $exerciseSessionCount;

            // Add course and session list.
            if (0 == $exerciseCount) {
                $exerciseCount = 2;
            }
            $html .= "<tr>
                        <td rowspan=$exerciseCount>";
            $html .= $courseInfo['title'];
            $html .= "</td>";
        }

        $sql = "SELECT visibility FROM $toolTable
                WHERE c_id = $courseId AND name = 'quiz'";
        $result = Database::query($sql);

        // If main tool is visible.
        if (1 == Database::result($result, 0, 'visibility')) {
            // Getting the exam list.
            if ($global) {
                $sql = "SELECT quiz.title, iid, session_id
                    FROM $quizTable AS quiz
                    WHERE c_id = $courseId AND active = 1
                    ORDER BY session_id, quiz.title ASC";
            } else {
                //$sessionCondition = api_get_session_condition($sessionId, true, false);
                if (!empty($exerciseId)) {
                    $sql = "SELECT quiz.title, iid, session_id
                            FROM $quizTable AS quiz
                            WHERE
                                c_id = $courseId AND
                                active = 1 AND
                                id = $exerciseId
                                $sessionCondition

                            ORDER BY session_id, quiz.title ASC";
                } else {
                    $sql = "SELECT quiz.title, iid, session_id
                            FROM $quizTable AS quiz
                            WHERE
                                c_id = $courseId AND
                                active = 1
                                $sessionCondition
                            ORDER BY session_id, quiz.title ASC";
                }
            }

            $resultExercises = Database::query($sql);

            if (Database::num_rows($resultExercises) > 0) {
                while ($exercise = Database::fetch_array($resultExercises, 'ASSOC')) {
                    $exerciseSessionId = $exercise['session_id'];

                    if (empty($exerciseSessionId)) {
                        if ($global) {
                            // If the exercise was created in the base course.
                            // Load all sessions.
                            foreach ($newSessionList as $currentSessionId => $sessionName) {
                                $result = processStudentList(
                                    $filter_score,
                                    $global,
                                    $exercise,
                                    $courseInfo,
                                    $currentSessionId,
                                    $newSessionList
                                );

                                $html .= $result['html'];
                                $export_array_global = array_merge($export_array_global, $result['export_array_global']);
                            }

                            // Load base course.
                            $result = processStudentList(
                                $filter_score,
                                $global,
                                $exercise,
                                $courseInfo,
                                0,
                                $newSessionList
                            );
                            $html .= $result['html'];
                            $export_array_global = array_merge($export_array_global, $result['export_array_global']);
                        } else {
                            if (empty($sessionId)) {
                                // Load base course.
                                $result = processStudentList(
                                    $filter_score,
                                    $global,
                                    $exercise,
                                    $courseInfo,
                                    0,
                                    $newSessionList
                                );

                                $html .= $result['html'];
                                if (is_array($result['export_array_global'])) {
                                    $export_array_global = array_merge(
                                        $export_array_global,
                                        $result['export_array_global']
                                    );
                                }
                            } else {
                                $result = processStudentList(
                                    $filter_score,
                                    $global,
                                    $exercise,
                                    $courseInfo,
                                    $sessionId,
                                    $newSessionList
                                );

                                $html .= $result['html'];
                                $export_array_global = array_merge(
                                    $export_array_global,
                                    $result['export_array_global']
                                );
                            }
                        }
                    } else {
                        // If the exercise only exists in this session.
                        $result = processStudentList(
                            $filter_score,
                            $global,
                            $exercise,
                            $courseInfo,
                            $exerciseSessionId,
                            $newSessionList
                        );

                        $html .= $result['html'];
                        $export_array_global = array_merge(
                            $export_array_global,
                            $result['export_array_global']
                        );
                    }
                }
            } else {
                $html .= "<tr>
                            <td colspan='6'>
                                ".get_lang('NoExercise')."
                            </td>
                        </tr>
                     ";
            }
        } else {
            $html .= "<tr>
                        <td colspan='6'>
                            ".get_lang('NoExercise')."
                        </td>
                    </tr>
                 ";
        }
    }
}

$html .= '</table>';
$html .= '</div>';

if (!$exportToXLS) {
    echo $html;
}

$filename = 'exam-reporting-'.api_get_local_time().'.xlsx';

if ($exportToXLS) {
    export_complete_report_xls($filename, $export_array_global);
    exit;
}
/**
 * @param $a
 * @param $b
 *
 * @return int
 */
function sort_user($a, $b)
{
    if (is_numeric($a['score']) && is_numeric($b['score'])) {
        if ($a['score'] < $b['score']) {
            return 1;
        }

        return 0;
    }

    return 1;
}

/**
 * @param string $filename
 * @param array  $array
 */
function export_complete_report_xls($filename, $array)
{
    global $global, $filter_score;

    $spreadsheet = new PHPExcel();
    $spreadsheet->setActiveSheetIndex(0);
    $worksheet = $spreadsheet->getActiveSheet();

    $line = 1;
    $column = 0; //skip the first column (row titles)

    if ($global) {
        $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('Courses'));
        $column++;
        $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('Exercises'));
        $column++;
        $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('ExamTaken'));
        $column++;
        $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('ExamNotTaken'));
        $column++;
        $worksheet->setCellValueByColumnAndRow($column, $line, sprintf(get_lang('ExamPassX'), $filter_score).'%');
        $column++;
        $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('ExamFail'));
        $column++;
        $worksheet->setCellValueByColumnAndRow($column, $line, get_lang('TotalStudents'));
        $column++;

        $line++;
        foreach ($array as $row) {
            $column = 0;
            foreach ($row as $item) {
                $worksheet->setCellValueByColumnAndRow($column, $line, html_entity_decode(strip_tags($item)));
                $column++;
            }
            $line++;
        }
        $line++;
    } else {
        $worksheet->setCellValueByColumnAndRow(0, $line, get_lang('Exercises'));
        $worksheet->setCellValueByColumnAndRow(1, $line, get_lang('User'));
        $worksheet->setCellValueByColumnAndRow(2, $line, get_lang('Username'));
        $worksheet->setCellValueByColumnAndRow(3, $line, get_lang('Percentage'));
        $worksheet->setCellValueByColumnAndRow(4, $line, get_lang('Status'));
        $worksheet->setCellValueByColumnAndRow(5, $line, get_lang('Attempts'));
        $line++;

        foreach ($array as $row) {
            $worksheet->setCellValueByColumnAndRow(
                0,
                $line,
                html_entity_decode(strip_tags($row['exercise']))
            );

            foreach ($row['users'] as $key => $user) {
                $worksheet->setCellValueByColumnAndRow(
                    1,
                    $line,
                    html_entity_decode(strip_tags($user))
                );
                $worksheet->setCellValueByColumnAndRow(
                    2,
                    $line,
                    $row['usernames'][$key]
                );
                $column = 3;

                foreach ($row['results'][$key] as $result_item) {
                    $worksheet->setCellValueByColumnAndRow(
                        $column,
                        $line,
                        html_entity_decode(strip_tags($result_item))
                    );
                    $column++;
                }

                $line++;
            }
        }
    }

    $file = api_get_path(SYS_ARCHIVE_PATH).api_replace_dangerous_char($filename);
    $writer = new PHPExcel_Writer_Excel2007($spreadsheet);
    $writer->save($file);
    DocumentManager::file_send_for_download($file, true, $filename);
    exit;
}

/**
 * @param $filter_score
 * @param $global
 * @param $exercise
 * @param $courseInfo
 * @param $sessionId
 * @param $newSessionList
 *
 * @return array
 */
function processStudentList($filter_score, $global, $exercise, $courseInfo, $sessionId, $newSessionList)
{
    if ((isset($exercise['iid']) && empty($exercise['iid'])) ||
        !isset($exercise['iid'])
    ) {
        return [
            'html' => '',
            'export_array_global' => [],
            'total_students' => 0,
        ];
    }

    $exerciseStatsTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
    $courseId = api_get_course_int_id($courseInfo['code']);

    if (empty($sessionId)) {
        $students = CourseManager::get_student_list_from_course_code(
            $courseInfo['code'],
            false,
            0,
            null,
            null,
            false
        );
    } else {
        $students = CourseManager::get_student_list_from_course_code(
            $courseInfo['code'],
            true,
            $sessionId,
            null,
            null,
            false
        );
    }

    $html = null;
    $totalStudents = count($students);

    if (!$global) {
        $html .= "<tr>";
    }

    if (!$global) {
        $html .= '<td rowspan="'.$totalStudents.'">';
    } else {
        $html .= '<td>';
    }

    $html .= $exercise['title'];

    if ($global && !empty($sessionId)) {
        $sessionName = isset($newSessionList[$sessionId]) ? $newSessionList[$sessionId] : null;
        $html .= Display::return_icon('star.png', get_lang('Session')).' ('.$sessionName.')';
    }

    $html .= '</td>';

    $globalRow = [
        $courseInfo['title'],
        $exercise['title'],
    ];

    $total_with_parameter_score = 0;
    $taken = 0;
    $export_array_global = [];
    $studentResult = [];
    $export_array = [];

    foreach ($students as $student) {
        $studentId = isset($student['user_id']) ? $student['user_id'] : $student['id_user'];
        $sql = "SELECT COUNT(ex.exe_id) as count
                FROM $exerciseStatsTable AS ex
                WHERE
                    ex.c_id = $courseId AND
                    ex.exe_exo_id = ".$exercise['iid']." AND
                    exe_user_id= $studentId AND
                    session_id = $sessionId
                ";
        $result = Database::query($sql);
        $attempts = Database::fetch_array($result);

        $sql = "SELECT exe_id, exe_result, exe_weighting
                FROM $exerciseStatsTable
                WHERE
                    exe_user_id = $studentId AND
                    c_id = $courseId AND
                    exe_exo_id = ".$exercise['iid']." AND
                    session_id = $sessionId
                ORDER BY exe_result DESC
                LIMIT 1";
        $result = Database::query($sql);
        $score = 0;
        $weighting = 0;
        while ($scoreInfo = Database::fetch_array($result)) {
            $score = $score + $scoreInfo['exe_result'];
            $weighting = $weighting + $scoreInfo['exe_weighting'];
        }

        $percentageScore = 0;

        if ($weighting != 0) {
            $percentageScore = round(($score * 100) / $weighting);
        }

        if ($attempts['count'] > 0) {
            $taken++;
        }

        if ($percentageScore >= $filter_score) {
            $total_with_parameter_score++;
        }

        $tempArray = [];

        if (!$global) {
            $userInfo = api_get_user_info($studentId);

            // User
            $userRow = '<td>';
            $userRow .= $userInfo['complete_name'];
            $userRow .= '</td>';
            $userRow .= '<td>'.$userInfo['username'].'</td>';

            // Best result

            if (!empty($attempts['count'])) {
                $userRow .= '<td>';
                $userRow .= $percentageScore;
                $tempArray[] = $percentageScore;
                $userRow .= '</td>';

                if ($percentageScore >= $filter_score) {
                    $userRow .= '<td style="background-color:#DFFFA8">';
                    $userRow .= get_lang('PassExam').'</td>';
                    $tempArray[] = get_lang('PassExam');
                } else {
                    $userRow .= '<td style="background-color:#FC9A9E"  >';
                    $userRow .= get_lang('ExamFail').'</td>';
                    $tempArray[] = get_lang('ExamFail');
                }

                $userRow .= '<td>';
                $userRow .= $attempts['count'];
                $tempArray[] = $attempts['count'];
                $userRow .= '</td>';
            } else {
                $score = '-';
                $userRow .= '<td>';
                $userRow .= '-';
                $tempArray[] = '-';
                $userRow .= '</td>';

                $userRow .= '<td style="background-color:#FCE89A">';
                $userRow .= get_lang('NoAttempt');
                $tempArray[] = get_lang('NoAttempt');
                $userRow .= '</td>';
                $userRow .= '<td>';
                $userRow .= 0;
                $tempArray[] = 0;
                $userRow .= '</td>';
            }
            $userRow .= '</tr>';

            $studentResult[$studentId] = [
                'html' => $userRow,
                'score' => $score,
                'array' => $tempArray,
                'user' => $userInfo['complete_name'],
                'username' => $userInfo['username'],
            ];
        }
    }

    $row_not_global['exercise'] = $exercise['title'];

    if (!$global) {
        if (!empty($studentResult)) {
            $studentResultEmpty = $studentResultContent = [];
            foreach ($studentResult as $row) {
                if ($row['score'] == '-') {
                    $studentResultEmpty[] = $row;
                } else {
                    $studentResultContent[] = $row;
                }
            }

            // Sort only users with content
            usort($studentResultContent, 'sort_user');
            $studentResult = array_merge($studentResultContent, $studentResultEmpty);

            foreach ($studentResult as $row) {
                $html .= $row['html'];
                $row_not_global['results'][] = $row['array'];
                $row_not_global['users'][] = $row['user'];
                $row_not_global['usernames'][] = $row['username'];
            }
            $export_array[] = $row_not_global;
        }
    }

    if ($global) {
        // Exam taken
        $html .= '<td>';
        $html .= $taken;
        $globalRow[] = $taken;
        $html .= '</td>';

        // Exam NOT taken
        $html .= '<td>';
        $html .= $not_taken = $totalStudents - $taken;
        $globalRow[] = $not_taken;
        $html .= '</td>';

        // Exam pass
        if (!empty($total_with_parameter_score)) {
            $html .= '<td style="background-color:#DFFFA8" >';
        } else {
            $html .= '<td style="background-color:#FCE89A"  >';
        }

        $html .= $total_with_parameter_score;
        $globalRow[] = $total_with_parameter_score;
        $html .= '</td>';

        // Exam fail
        $html .= '<td>';

        $html .= $fail = $taken - $total_with_parameter_score;
        $globalRow[] = $fail;
        $html .= '</td>';

        $html .= '<td>';
        $html .= $totalStudents;
        $globalRow[] = $totalStudents;

        $html .= '</td>';

        $html .= '</tr>';
        $export_array_global[] = $globalRow;
    }

    return [
        'html' => $html,
        'export_array_global' => $global ? $export_array_global : $export_array,
        'total_students' => $totalStudents,
    ];
}
Display::display_footer();

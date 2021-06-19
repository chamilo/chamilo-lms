<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CQuiz;

require_once __DIR__.'/../inc/global.inc.php';

$toolTable = Database::get_course_table(TABLE_TOOL_LIST);
$quizTable = Database::get_course_table(TABLE_QUIZ_TEST);

$this_section = SECTION_TRACKING;
$is_allowedToTrack = api_is_course_admin() || api_is_platform_admin(true) || api_is_session_general_coach();

if (!$is_allowedToTrack) {
    api_not_allowed(true);
}

$exportToXLS = false;
if (isset($_GET['export'])) {
    $exportToXLS = true;
}

$courseInfo = api_get_course_info();

$global = false;
if (api_is_platform_admin() && empty($_GET['cidReq'])) {
    $global = true;
}

$courseList = [];
if ($global) {
    $temp = CourseManager::get_courses_list();
    foreach ($temp as $tempCourse) {
        $courseList[] = api_get_course_entity($tempCourse['real_id']);
    }
} else {
    $courseList = [api_get_course_entity()];
}

$sessionId = api_get_session_id();

if (empty($sessionId)) {
    $sessionCondition = ' AND session_id = 0';
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
            $exerciseList[$row['id']] = $row['title'];
        }
        $form->addSelect('exercise_id', get_lang('Exercise'), $exerciseList);
    }
}

$form->addButton(
    'filter',
    get_lang('Filter'),
    'filter',
    'primary',
    null,
    null,
    ['style' => 'margin-top: 5px; margin-left: 15px;']
);

$filter_score = isset($_REQUEST['score']) ? intval($_REQUEST['score']) : 70;
$exerciseId = isset($_REQUEST['exercise_id']) ? intval($_REQUEST['exercise_id']) : 0;

$form->setDefaults(['score' => $filter_score]);

if (!$exportToXLS) {
    Display:: display_header(get_lang('Reporting'));
    $actionsLeft = $actionsRight = '';
    if ($global) {
        $actionsLeft .= '<a href="'.api_get_path(WEB_CODE_PATH).'auth/my_progress.php">'.
            Display::return_icon('statistics.png', get_lang('View my progress'), '', ICON_SIZE_MEDIUM);
        $actionsLeft .= '</a>';

        $actionsRight .= '<a href="'.api_get_self().'?export=1&score='.$filter_score.'&exercise_id='.$exerciseId.'">'.
            Display::return_icon('export_excel.png', get_lang('Excel export'), '', ICON_SIZE_MEDIUM).'</a>';
        $actionsRight .= '<a href="javascript: void(0);" onclick="javascript: window.print()">'.
            Display::return_icon('printer.png', get_lang('Print'), '', ICON_SIZE_MEDIUM).'</a>';

        $menuItems[] = Display::url(
            Display::return_icon('teacher.png', get_lang('Trainer View'), [], 32),
            api_get_path(WEB_CODE_PATH).'mySpace/?view=teacher'
        );
        if (api_is_platform_admin()) {
            $menuItems[] = Display::url(
                Display::return_icon('star.png', get_lang('Admin view'), [], 32),
                api_get_path(WEB_CODE_PATH).'mySpace/admin_view.php'
            );
        } else {
            $menuItems[] = Display::url(
                Display::return_icon('star.png', get_lang('Coach interface'), [], 32),
                api_get_path(WEB_CODE_PATH).'mySpace/index.php?view=coach'
            );
        }
        $menuItems[] = '<a href="#">'.Display::return_icon('quiz_na.png', get_lang('Exam tracking'), [], 32).'</a>';

        $nb_menu_items = count($menuItems);
        if ($nb_menu_items > 1) {
            foreach ($menuItems as $key => $item) {
                $actionsLeft .= $item;
            }
        }
    } else {
        $actionsLeft = TrackingCourseLog::actionsLeft('exams', api_get_session_id(), false);
        $actionsRight .= Display::url(
            Display::return_icon('export_excel.png', get_lang('Excel export'), [], 32),
            api_get_self().'?'.api_get_cidreq().'&export=1&score='.$filter_score.'&exercise_id='.$exerciseId
        );
    }

    $toolbar = Display::toolbarAction('toolbar-exams', [$actionsLeft, $actionsRight]);
    echo $toolbar;

    $form->display();
    echo '<h3>'.sprintf(get_lang('Filtering with score %s'), $filter_score).'%</h3>';
}

$html = '<div class="table-responsive">';
$html .= '<table  class="table table-hover table-striped data_table">';
if ($global) {
    $html .= '<tr>';
    $html .= '<th>'.get_lang('Courses').'</th>';
    $html .= '<th>'.get_lang('Tests').'</th>';
    $html .= '<th>'.get_lang('Taken').'</th>';
    $html .= '<th>'.get_lang('Not taken').'</th>';
    $html .= '<th>'.sprintf(get_lang('Pass minimum %s'), $filter_score).'%</th>';
    $html .= '<th>'.get_lang('Fail').'</th>';
    $html .= '<th>'.get_lang('Total learners').'</th>';
    $html .= '</tr>';
} else {
    $html .= '<tr>';
    $html .= '<th>'.get_lang('Tests').'</th>';
    $html .= '<th>'.get_lang('User').'</th>';
    $html .= '<th>'.get_lang('Username').'</th>';
    //$html .= '<th>'.sprintf(get_lang('Pass minimum %s'), $filter_score).'</th>';
    $html .= '<th>'.get_lang('Percentage').' %</th>';
    $html .= '<th>'.get_lang('Status').'</th>';
    $html .= '<th>'.get_lang('Attempts').'</th>';
    $html .= '</tr>';
}

$export_array_global = $export_array = [];
$s_css_class = null;

if (!empty($courseList)) {
    $quizRepo = Container::getQuizRepository();
    foreach ($courseList as $course) {
        $courseId = $course->getId();
        $sessionList = SessionManager::get_session_by_course($courseId);

        $newSessionList = [];
        if (!empty($sessionList)) {
            foreach ($sessionList as $session) {
                $newSessionList[$session['id']] = $session['name'];
            }
        }

        if ($global) {
            $qb = $quizRepo->getResourcesByCourse($course);
            $qb->select('count(resource)');

            $exerciseCount = $qb->getQuery()->getSingleScalarResult();

            /*$sql = "SELECT count(iid) as count
                    FROM $quizTable AS quiz
                    WHERE c_id = $courseId AND  active = 1 AND (session_id = 0 OR session_id IS NULL)";
            $result = Database::query($sql);
            $countExercises = Database::store_result($result);
            $exerciseCount = $countExercises[0]['count'];*/

            $qb = $quizRepo->getResourcesByCourse($course);
            $qb->select('count(resource)');
            $qb->andWhere('links.session IS NOT NULL');

            $exerciseSessionCount = $qb->getQuery()->getSingleScalarResult();

            /*$sql = "SELECT count(iid) as count
                    FROM $quizTable AS quiz
                    WHERE c_id = $courseId AND active = 1 AND session_id <> 0";
            $result = Database::query($sql);
            $countExercises = Database::store_result($result);
            $exerciseSessionCount = $countExercises[0]['count'];*/

            $exerciseCount = $exerciseCount + $exerciseCount * count($newSessionList) + $exerciseSessionCount;

            // Add course and session list.
            if (0 == $exerciseCount) {
                $exerciseCount = 2;
            }
            $html .= "<tr>
                        <td rowspan=$exerciseCount>";
            $html .= $course->getTitle();
            $html .= "</td>";
        }

        $sql = "SELECT visibility FROM $toolTable
                WHERE c_id = $courseId AND name = 'quiz'";
        $result = Database::query($sql);

        // If main tool is visible.
        if (1 == Database::result($result, 0, 'visibility')) {
            $exercises = [];
            // Getting the exam list.
            if ($global) {
                $qb = $quizRepo->getResourcesByCourse($course);
                $exercises = $qb->getQuery()->getResult();
                /*$sql = "SELECT quiz.title, iid, session_id
                        FROM $quizTable AS quiz
                        WHERE c_id = $courseId AND active = 1
                        ORDER BY session_id, quiz.title ASC";*/
            } else {
                //$sessionCondition = api_get_session_condition($sessionId, true, false);
                if (!empty($exerciseId)) {
                    $exercises = [];
                    $exercises[] = $quizRepo->find($exerciseId);
                    /*
                    $sql = "SELECT quiz.title, iid, session_id
                            FROM $quizTable AS quiz
                            WHERE
                                c_id = $courseId AND
                                active = 1 AND
                                id = $exerciseId
                                $sessionCondition
                            ORDER BY session_id, quiz.title ASC";
                    */
                } else {
                    $qb = $quizRepo->getResourcesByCourse($course, api_get_session_entity());
                    $exercises = $qb->getQuery()->getResult();
                    /*
                    $sql = "SELECT quiz.title, iid, session_id
                            FROM $quizTable AS quiz
                            WHERE
                                c_id = $courseId AND
                                active = 1
                                $sessionCondition
                            ORDER BY session_id, quiz.title ASC";
                    */
                }
            }

            if (!empty($exercises)) {
                /** @var CQuiz $exercise */
                foreach ($exercises as $exercise) {
                    $links = $exercise->getResourceNode()->getResourceLinks();

                    $exerciseSessionId = null;
                    foreach ($links as $link) {
                        if ($link->hasSession()) {
                            $exerciseSessionId = $link->getSession()->getId();
                            break;
                        }
                    }

                    //$exerciseSessionId = $exercise['session_id'];

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
                                $export_array_global = array_merge(
                                    $export_array_global,
                                    $result['export_array_global']
                                );
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
                                ".get_lang('NoTest')."
                            </td>
                        </tr>
                     ";
            }
        } else {
            $html .= "<tr>
                        <td colspan='6'>
                            ".get_lang('NoTest')."
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

    $list = [];
    if ($global) {
        $headers[] = get_lang('Courses');
        $headers[] = get_lang('Tests');
        $headers[] = get_lang('Taken');
        $headers[] = get_lang('Not taken');
        $headers[] = sprintf(get_lang('Pass minimum %s'), $filter_score).'%';
        $headers[] = get_lang('Fail');
        $headers[] = get_lang('Total learners');

        $list[] = $headers;
        foreach ($array as $row) {
            $listItem = [];
            foreach ($row as $item) {
                $listItem[] = html_entity_decode(strip_tags($item));
            }
            $list[] = $listItem;
        }
    } else {
        $headers[] = get_lang('Tests');
        $headers[] = get_lang('User');
        $headers[] = get_lang('Username');
        $headers[] = get_lang('Percentage');
        $headers[] = get_lang('Status');
        $headers[] = get_lang('Attempts');

        $list[] = $headers;

        foreach ($array as $row) {
            $listItem = [];
            $listItem[] = html_entity_decode(strip_tags($row['exercise']));

            foreach ($row['users'] as $key => $user) {
                $listItem[] = html_entity_decode(strip_tags($user));
                $listItem[] = $row['usernames'][$key];
                foreach ($row['results'][$key] as $result_item) {
                    $listItem[] = html_entity_decode(strip_tags($result_item));
                }
                $line++;
            }

            $list[] = $listItem;
        }
    }

    Export::arrayToXls($list, $filename);
}

function processStudentList($filter_score, $global, Cquiz $exercise, $courseInfo, $sessionId, $newSessionList)
{
    /*if ((isset($exercise['id']) && empty($exercise['id'])) ||
        !isset($exercise['id'])
    ) {
        return [
            'html' => '',
            'export_array_global' => [],
            'total_students' => 0,
        ];
    }*/

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

    $html .= $exercise->getTitle();

    if ($global && !empty($sessionId)) {
        $sessionName = isset($newSessionList[$sessionId]) ? $newSessionList[$sessionId] : null;
        $html .= Display::return_icon('star.png', get_lang('Session')).' ('.$sessionName.')';
    }

    $html .= '</td>';

    $globalRow = [
        $courseInfo['title'],
        $exercise->getTitle(),
    ];

    $total_with_parameter_score = 0;
    $taken = 0;
    $export_array_global = [];
    $studentResult = [];
    $export_array = [];

    $exerciseId = $exercise->getIid();

    foreach ($students as $student) {
        $studentId = isset($student['user_id']) ? $student['user_id'] : $student['id_user'];
        $sql = "SELECT COUNT(ex.exe_id) as count
                FROM $exerciseStatsTable AS ex
                WHERE
                    ex.c_id = $courseId AND
                    ex.exe_exo_id = ".$exerciseId." AND
                    exe_user_id= $studentId AND
                    session_id = $sessionId
                ";
        $result = Database::query($sql);
        $attempts = Database::fetch_array($result);

        $sql = "SELECT exe_id, score, max_score
                FROM $exerciseStatsTable
                WHERE
                    exe_user_id = $studentId AND
                    c_id = $courseId AND
                    exe_exo_id = ".$exerciseId." AND
                    session_id = $sessionId
                ORDER BY score DESC
                LIMIT 1";
        $result = Database::query($sql);
        $score = 0;
        $weighting = 0;
        while ($scoreInfo = Database::fetch_array($result)) {
            $score = $score + $scoreInfo['score'];
            $weighting = $weighting + $scoreInfo['max_score'];
        }

        $percentageScore = 0;

        if (0 != $weighting) {
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

            // Best result.
            if (!empty($attempts['count'])) {
                $userRow .= '<td>';
                $userRow .= $percentageScore;
                $tempArray[] = $percentageScore;
                $userRow .= '</td>';

                if ($percentageScore >= $filter_score) {
                    $userRow .= '<td style="background-color:#DFFFA8">';
                    $userRow .= get_lang('Pass').'</td>';
                    $tempArray[] = get_lang('Pass');
                } else {
                    $userRow .= '<td style="background-color:#FC9A9E"  >';
                    $userRow .= get_lang('Fail').'</td>';
                    $tempArray[] = get_lang('Fail');
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
                $userRow .= get_lang('No attempts');
                $tempArray[] = get_lang('No attempts');
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

    $row_not_global['exercise'] = $exercise->getTitle();

    if (!$global) {
        if (!empty($studentResult)) {
            $studentResultEmpty = $studentResultContent = [];
            foreach ($studentResult as $row) {
                if ('-' == $row['score']) {
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

Display:: display_footer();

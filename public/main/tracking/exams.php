<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CQuiz;

require_once __DIR__.'/../inc/global.inc.php';

$toolTable = Database::get_course_table(TABLE_TOOL_LIST);
$quizTable = Database::get_course_table(TABLE_QUIZ_TEST);

$this_section = SECTION_TRACKING;

$is_allowedToTrack =
    api_is_allowed_to_edit() ||
    api_is_platform_admin(true) ||
    api_is_session_general_coach();

if (!$is_allowedToTrack) {
    api_not_allowed(true);
}

$exportToXLS = isset($_GET['export']);
$courseInfo = api_get_course_info();

$global = false;
if (api_is_platform_admin() && empty($_GET['cidReq'])) {
    // Global reporting: all courses.
    $global = true;
}

$courseList = [];
if ($global) {
    $temp = CourseManager::get_courses_list();
    foreach ($temp as $tempCourse) {
        $courseList[] = api_get_course_entity($tempCourse['real_id']);
    }
} else {
    // Course context: current course only.
    $courseList = [api_get_course_entity()];
}

$sessionId = api_get_session_id();

if (empty($sessionId)) {
    $sessionCondition = ' AND session_id = 0';
} else {
    $sessionCondition = api_get_session_condition($sessionId, true, true);
}

// -----------------------------------------------------------------------------
// Filter form
// -----------------------------------------------------------------------------
$form = new FormValidator(
    'search_simple',
    'POST',
    api_get_self().'?'.api_get_cidreq(),
    '',
    null,
    false
);

// Percentage filter – use a narrow input, since the value is between 0 and 100.
$form->addElement(
    'number',
    'score',
    get_lang('Percentage'),
    [
        'min' => 0,
        'max' => 100,
        'step' => 1,
        'style' => 'max-width: 6rem;',
    ]
);

if ($global) {
    $form->addElement('hidden', 'view', 'admin');
} else {
    $courseId  = (int) api_get_course_int_id();
    $sessionId = (int) api_get_session_id();

    $course  = api_get_course_entity($courseId);
    $session = $sessionId ? api_get_session_entity($sessionId) : null;

    $repo = Container::getQuizRepository();
    $qb = $repo->getResourcesByCourse($course, $session);

    $qb->select('DISTINCT resource');

    if ($session) {
        $qb->andWhere('(links.session = :sess OR links.session IS NULL)')
            ->setParameter('sess', $session);
    } else {
        $qb->andWhere('links.session IS NULL');
    }
    $qb->orderBy('resource.title', 'ASC');
    $quizzes = $qb->getQuery()->getResult();
    $exerciseList = [0 => get_lang('All')];

    foreach ($quizzes as $quiz) {
        $id = method_exists($quiz, 'getIid') ? (int) $quiz->getIid() : (int) $quiz->getId();
        $exerciseList[$id] = $quiz->getTitle();
    }

    $form->addSelect('exercise_id', get_lang('Test'), $exerciseList);
}

$form->addButton(
    'filter',
    get_lang('Filter'),
    'filter',
    'primary',
    null,
    null,
    [
        'style' => 'margin-top: 15px;',
    ]
);

$filter_score = isset($_REQUEST['score']) ? (int) $_REQUEST['score'] : 70;
$exerciseId = isset($_REQUEST['exercise_id']) ? (int) $_REQUEST['exercise_id'] : 0;

$form->setDefaults(['score' => $filter_score]);

// -----------------------------------------------------------------------------
// Build main HTML table and export array
// -----------------------------------------------------------------------------
$html = '<div class="table-responsive">';
$html .= '<table class="table table-hover table-striped data_table">';

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
    $html .= '<th>'.get_lang('Percentage').' %</th>';
    $html .= '<th>'.get_lang('Status').'</th>';
    $html .= '<th>'.get_lang('Attempts').'</th>';
    $html .= '</tr>';
}

$export_array_global = [];

if (!empty($courseList)) {
    $quizRepo = Container::getQuizRepository();

    /** @var Course $course */
    foreach ($courseList as $course) {
        $courseId = (int) $course->getId();
        $courseInfo = api_get_course_info_by_id($courseId);

        if (empty($courseInfo)) {
            continue;
        }

        $sessionList = SessionManager::get_session_by_course($courseId);
        $newSessionList = [];

        if (!empty($sessionList)) {
            foreach ($sessionList as $session) {
                $newSessionList[$session['id']] = $session['title'];
            }
        }

        // Check quiz tool visibility for this course.
        $toolVisible = 0;
        $sessionIdInt = (int) $sessionId;
        $whereSession = '';
        $orderSession = '';

        if ($global) {
            $whereSession = ' AND (ct.session_id IS NULL OR ct.session_id = 0)';
        } else {
            if ($sessionIdInt > 0) {
                $orderSession = "(ct.session_id = $sessionIdInt) DESC,";
            }
        }

        $sql = "SELECT
                    COALESCE(rl.visibility, ".ResourceLink::VISIBILITY_PUBLISHED.") AS visibility
                FROM $toolTable ct
                LEFT JOIN resource_link rl
                    ON rl.resource_node_id = ct.resource_node_id
                    AND rl.c_id = ct.c_id
                    AND (
                        ((rl.session_id IS NULL OR rl.session_id = 0) AND (ct.session_id IS NULL OR ct.session_id = 0))
                        OR rl.session_id = ct.session_id
                    )
                    AND rl.user_id IS NULL
                    AND rl.usergroup_id IS NULL
                    AND rl.group_id IS NULL
                    AND rl.deleted_at IS NULL
                WHERE ct.c_id = $courseId
                  AND ct.title = 'quiz'
                  $whereSession
                ORDER BY
                  $orderSession
                  ct.iid ASC
                LIMIT 1";

        $result = Database::query($sql);

        if (Database::num_rows($result) > 0) {
            $linkVisibility = (int) Database::result($result, 0, 'visibility');
            $toolVisible = ($linkVisibility === ResourceLink::VISIBILITY_PUBLISHED) ? 1 : 0;
        }

        if (1 === $toolVisible) {
            // Fetch exams depending on context.
            if ($global) {
                $qb = $quizRepo->getResourcesByCourse($course);
                $exercises = $qb->getQuery()->getResult();
            } else {
                if (!empty($exerciseId)) {
                    $exercises = [];
                    $found = $quizRepo->find($exerciseId);
                    if ($found instanceof CQuiz) {
                        $exercises[] = $found;
                    }
                } else {
                    $qb = $quizRepo->getResourcesByCourse($course, api_get_session_entity());
                    $exercises = $qb->getQuery()->getResult();
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

                    // Base-course exam replicated across sessions.
                    if (empty($exerciseSessionId)) {
                        if ($global) {
                            // Per session.
                            foreach ($newSessionList as $currentSessionId => $sessionName) {
                                $resultRow = processStudentList(
                                    $filter_score,
                                    $global,
                                    $exercise,
                                    $courseInfo,
                                    $currentSessionId,
                                    $newSessionList
                                );

                                $html .= $resultRow['html'];
                                $export_array_global = array_merge(
                                    $export_array_global,
                                    $resultRow['export_array_global']
                                );
                            }

                            // Base course (no session).
                            $resultRow = processStudentList(
                                $filter_score,
                                $global,
                                $exercise,
                                $courseInfo,
                                0,
                                $newSessionList
                            );

                            $html .= $resultRow['html'];
                            $export_array_global = array_merge(
                                $export_array_global,
                                $resultRow['export_array_global']
                            );
                        } else {
                            // Course context (teacher view).
                            if (empty($sessionId)) {
                                $resultRow = processStudentList(
                                    $filter_score,
                                    $global,
                                    $exercise,
                                    $courseInfo,
                                    0,
                                    $newSessionList
                                );
                            } else {
                                $resultRow = processStudentList(
                                    $filter_score,
                                    $global,
                                    $exercise,
                                    $courseInfo,
                                    $sessionId,
                                    $newSessionList
                                );
                            }

                            $html .= $resultRow['html'];

                            if (is_array($resultRow['export_array_global'])) {
                                $export_array_global = array_merge(
                                    $export_array_global,
                                    $resultRow['export_array_global']
                                );
                            }
                        }
                    } else {
                        // Exam exists only inside a specific session.
                        $resultRow = processStudentList(
                            $filter_score,
                            $global,
                            $exercise,
                            $courseInfo,
                            $exerciseSessionId,
                            $newSessionList
                        );

                        $html .= $resultRow['html'];
                        $export_array_global = array_merge(
                            $export_array_global,
                            $resultRow['export_array_global']
                        );
                    }
                }
            } else {
                // No exams in this course.
                if ($global) {
                    $html .= '<tr>';
                    $html .= '<td>'.Security::remove_XSS($course->getTitle()).'</td>';
                    $html .= '<td colspan="6">'.get_lang('There is no test for the moment').'</td>';
                    $html .= '</tr>';
                } else {
                    $html .= '<tr>
                                <td colspan="6">'.
                        get_lang('There is no test for the moment').
                        '</td>
                              </tr>';
                }
            }
        } else {
            // Quiz tool is not visible in this course.
            if ($global) {
                $html .= '<tr>';
                $html .= '<td>'.Security::remove_XSS($course->getTitle()).'</td>';
                $html .= '<td colspan="6">'.get_lang('There is no test for the moment').'</td>';
                $html .= '</tr>';
            } else {
                $html .= '<tr>
                            <td colspan="6">'.
                    get_lang('There is no test for the moment').
                    '</td>
                          </tr>';
            }
        }
    }
}

$html .= '</table>';
$html .= '</div>';

// -----------------------------------------------------------------------------
// Export handling
// -----------------------------------------------------------------------------
$filename = 'exam-reporting-'.api_get_local_time().'.xlsx';

if ($exportToXLS) {
    export_complete_report_xls($filename, $export_array_global);

    exit;
}

// -----------------------------------------------------------------------------
// Page rendering (normal HTML view)
// -----------------------------------------------------------------------------
Display::display_header(get_lang('Reporting'));

// Top-level My Space menu (main navigation for reporting).
$primaryMenu = Display::mySpaceMenu('exams');

// Secondary navigation: exam-specific menu (icons row).
$sessionForToolbar = $global ? 0 : api_get_session_id();
$examMenu = TrackingCourseLog::actionsLeft('exams', $sessionForToolbar, $global);

// Right side: export + print.
$queryBase = api_get_self().'?';
if (!$global) {
    $queryBase .= api_get_cidreq().'&';
}

$exportUrl = $queryBase.'export=1&score='.$filter_score.'&exercise_id='.$exerciseId;

$actionsRight = Display::url(
    Display::getMdiIcon(
        ActionIcon::EXPORT_SPREADSHEET,
        'ch-tool-icon',
        null,
        ICON_SIZE_MEDIUM,
        get_lang('Excel export')
    ),
    $exportUrl
);
$actionsRight .= Display::url(
    Display::getMdiIcon(
        ActionIcon::PRINT,
        'ch-tool-icon',
        null,
        ICON_SIZE_MEDIUM,
        get_lang('Print')
    ),
    'javascript: void(0);',
    ['onclick' => 'javascript: window.print();']
);

// Toolbar with exam local menu on the left and actions on the right.
$toolbar = Display::toolbarAction('toolbar-exams', [$examMenu, $actionsRight]);

// Main layout wrapper.
echo '<div class="w-full px-4 md:px-8 pb-8 space-y-4">';

// Row 1: primary My Space menu.
echo '  <div class="flex flex-wrap gap-2">';
echo        $primaryMenu;
echo '  </div>';

// Row 2: exam toolbar (local icons + export/print).
echo '  <div class="flex flex-wrap gap-2">';
echo        $toolbar;
echo '  </div>';

// Filter card.
echo '  <section class="bg-white rounded-xl shadow-sm border border-gray-50">';
echo '      <div class="p-4 md:p-5">';
$form->display();
echo '          <p class="mt-3 font-semibold">'.
    sprintf(get_lang('Filtering with score %s'), $filter_score).'%</p>';
echo '      </div>';
echo '  </section>';

// Results table card.
echo '  <section class="bg-white rounded-xl shadow-sm border border-gray-50 overflow-x-auto">';
echo        $html;
echo '  </section>';

echo '</div>';

Display::display_footer();

/**
 * Compare two users by score (used when sorting student results).
 *
 * @param array $a
 * @param array $b
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
 * Export complete report to XLS.
 *
 * @param string $filename
 * @param array  $array
 */
function export_complete_report_xls($filename, $array)
{
    global $global, $filter_score;

    $list = [];

    if ($global) {
        $headers = [];
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
        $headers = [];
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
                foreach ($row['results'][$key] as $resultItem) {
                    $listItem[] = html_entity_decode(strip_tags($resultItem));
                }
            }

            $list[] = $listItem;
        }
    }

    Export::arrayToXls($list, $filename);
}

/**
 * Process exam statistics for a given exam and session.
 *
 * @param int   $filter_score
 * @param bool  $global
 * @param CQuiz $exercise
 * @param array $courseInfo
 * @param int   $sessionId
 * @param array $newSessionList
 *
 * @return array{html:string, export_array_global:array, total_students:int}
 */
function processStudentList(
    $filter_score,
    $global,
    Cquiz $exercise,
    $courseInfo,
    $sessionId,
    $newSessionList
) {
    $exerciseStatsTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
    $courseId = $courseInfo['real_id'];

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

    $html = '';
    $totalStudents = count($students);

    if ($global) {
        // One row per course + exam (+ session).
        $html .= '<tr>';
        $html .= '<td>'.Security::remove_XSS($courseInfo['title']).'</td>';
        $html .= '<td>';

        $html .= Security::remove_XSS($exercise->getTitle());

        if (!empty($sessionId)) {
            $sessionName = $newSessionList[$sessionId] ?? null;
            if (!empty($sessionName)) {
                $html .= ' '.Display::getMdiIcon(
                        ObjectIcon::STAR,
                        'ch-tool-icon',
                        null,
                        ICON_SIZE_SMALL,
                        get_lang('Session')
                    ).' ('.$sessionName.')';
            }
        }

        $html .= '</td>';
    } else {
        // Course context: first column is the exam title with rowspan.
        $html .= '<tr>';
        $html .= '<td rowspan="'.$totalStudents.'">';
        $html .= Security::remove_XSS($exercise->getTitle());
        $html .= '</td>';
    }

    $globalRow = [
        $courseInfo['title'],
        $exercise->getTitle(),
    ];

    $totalWithParameterScore = 0;
    $taken = 0;
    $export_array_global = [];
    $studentResult = [];
    $export_array = [];

    $exerciseId = $exercise->getIid();
    $sessionCondition = api_get_session_condition($sessionId);

    foreach ($students as $student) {
        $studentId = isset($student['user_id']) ? (int) $student['user_id'] : (int) $student['id_user'];

        $sql = "SELECT COUNT(exe_id) AS count
                FROM $exerciseStatsTable
                WHERE
                    c_id = $courseId AND
                    exe_exo_id = $exerciseId AND
                    exe_user_id = $studentId
                    $sessionCondition";
        $result = Database::query($sql);
        $attempts = Database::fetch_array($result);

        $sql = "SELECT exe_id, score, max_score
                FROM $exerciseStatsTable
                WHERE
                    exe_user_id = $studentId AND
                    c_id = $courseId AND
                    exe_exo_id = $exerciseId AND
                    session_id = $sessionId
                ORDER BY score DESC
                LIMIT 1";
        $result = Database::query($sql);

        $score = 0;
        $weighting = 0;

        while ($scoreInfo = Database::fetch_array($result)) {
            $score += $scoreInfo['score'];
            $weighting += $scoreInfo['max_score'];
        }

        $percentageScore = 0;

        // Protect against zero or "0" weighting values.
        if (!empty($weighting) && (float) $weighting !== 0.0) {
            $percentageScore = round(($score * 100) / (float) $weighting);
        }

        if ($attempts['count'] > 0) {
            $taken++;
        }

        if ($percentageScore >= $filter_score) {
            $totalWithParameterScore++;
        }

        $tempArray = [];

        if (!$global) {
            $userInfo = api_get_user_info($studentId);

            $userRow = '<td>'.$userInfo['complete_name'].'</td>';
            $userRow .= '<td>'.$userInfo['username'].'</td>';

            if (!empty($attempts['count'])) {
                $userRow .= '<td>'.$percentageScore.'</td>';
                $tempArray[] = $percentageScore;

                if ($percentageScore >= $filter_score) {
                    $userRow .= '<td style="background-color:#DFFFA8">';
                    $userRow .= get_lang('Pass').'</td>';
                    $tempArray[] = get_lang('Pass');
                } else {
                    $userRow .= '<td style="background-color:#FC9A9E">';
                    $userRow .= get_lang('Fail').'</td>';
                    $tempArray[] = get_lang('Fail');
                }

                $userRow .= '<td>'.$attempts['count'].'</td>';
                $tempArray[] = $attempts['count'];
            } else {
                $userRow .= '<td>-</td>';
                $tempArray[] = '-';

                $userRow .= '<td style="background-color:#FCE89A">';
                $userRow .= get_lang('No attempts').'</td>';
                $tempArray[] = get_lang('No attempts');

                $userRow .= '<td>0</td>';
                $tempArray[] = 0;
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
            $studentResultEmpty = [];
            $studentResultContent = [];

            foreach ($studentResult as $row) {
                if ('-' === $row['score']) {
                    $studentResultEmpty[] = $row;
                } else {
                    $studentResultContent[] = $row;
                }
            }

            // Sort only users with an actual score.
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
        // Exam taken.
        $html .= '<td>'.$taken.'</td>';
        $globalRow[] = $taken;

        // Exam not taken.
        $notTaken = $totalStudents - $taken;
        $html .= '<td>'.$notTaken.'</td>';
        $globalRow[] = $notTaken;

        // Exam passed (>= filter score).
        if (!empty($totalWithParameterScore)) {
            $html .= '<td style="background-color:#DFFFA8">'.$totalWithParameterScore.'</td>';
        } else {
            $html .= '<td style="background-color:#FCE89A">'.$totalWithParameterScore.'</td>';
        }
        $globalRow[] = $totalWithParameterScore;

        // Exam failed.
        $fail = $taken - $totalWithParameterScore;
        $html .= '<td>'.$fail.'</td>';
        $globalRow[] = $fail;

        // Total learners.
        $html .= '<td>'.$totalStudents.'</td>';
        $globalRow[] = $totalStudents;

        $html .= '</tr>';
        $export_array_global[] = $globalRow;
    }

    return [
        'html' => $html,
        'export_array_global' => $global ? $export_array_global : $export_array,
        'total_students' => $totalStudents,
    ];
}

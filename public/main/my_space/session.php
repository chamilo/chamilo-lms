<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;

ob_start();
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$this_section = SECTION_TRACKING;
$action = $_GET['action'] ?? '';

$allowToTrack = api_is_platform_admin(true, true) || api_is_teacher();

if (!$allowToTrack) {
    api_not_allowed(true);
}

$studentId = isset($_GET['student']) ? (int) $_GET['student'] : 0;
$sessionId = isset($_GET['sid']) ? (int) $_GET['sid'] : 0;
$currentUrl = api_get_self().'?student='.$studentId.'&sid='.$sessionId;

switch ($action) {
    case 'export_to_pdf':
        $allStudents = isset($_GET['all_students']) && 1 === (int) $_GET['all_students'] ? true : false;
        $sessionToExport = isset($_GET['session_to_export']) ? (int) $_GET['session_to_export'] : 0;
        $sessionEntity = $sessionToExport ? api_get_session_entity($sessionToExport) : null;
        $type = isset($_GET['type']) ? $_GET['type'] : 'attendance';
        if (empty($sessionEntity)) {
            api_not_allowed(true);
        }
        $courses = Tracking::get_courses_list_from_session($sessionToExport);

        // A teacher may only export a session they coach, or one with a course they teach,
        // and only for the learners of that session.
        if (!api_is_platform_admin(true, true)) {
            $isLinkedToSession = $sessionEntity->hasCoach(api_get_user_entity());
            foreach ($courses as $sessionCourseInfo) {
                $isLinkedToSession = $isLinkedToSession
                    || CourseManager::isCourseTeacher(api_get_user_id(), $sessionCourseInfo['c_id']);
            }

            if (!$isLinkedToSession
                || (!$allStudents && !SessionManager::isUserSubscribedAsStudent($sessionToExport, $studentId))
            ) {
                api_not_allowed(true);
            }
        }

        /** @var array<int, Course> $courseEntityList */
        $courseEntityList = array_map(fn(array $courseInfo) => api_get_course_entity($courseInfo['c_id']), $courses);
        $studentList = [$studentId];
        if ($allStudents) {
            $users = SessionManager::get_users_by_session($sessionToExport, 0);
            $studentList = array_column($users, 'user_id');
        }

        $totalCourses = count($courses);
        $scoreDisplay = ScoreDisplay::instance();
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $pdfList = [];
        foreach ($studentList as $studentId) {
            $studentInfo = api_get_user_info($studentId);
            $timeSpent = 0;
            $numberVisits = 0;
            $progress = 0;
            $timeSpentPerCourse = [];
            $progressPerCourse = [];

            foreach ($courseEntityList as $course) {
                $courseTimeSpent = Tracking::get_time_spent_on_the_course($studentId, $course->getId(), $sessionToExport);
                $timeSpentPerCourse[$course->getId()] = $courseTimeSpent;
                $timeSpent += $courseTimeSpent;
                $sql = "SELECT DISTINCT count(course_access_id) as count
                        FROM $table
                        WHERE
                            c_id = {$course->getId()} AND
                            session_id = $sessionToExport AND
                            user_id = $studentId";
                $result = Database::query($sql);
                $row = Database::fetch_array($result);
                $numberVisits += $row['count'];
                $courseProgress = Tracking::get_avg_student_progress(
                    $studentId,
                    $course,
                    [],
                    $sessionEntity
                );
                $progressPerCourse[$course->getId()] = $courseProgress;
                $progress += $courseProgress;
            }

            $average = round($progress / $totalCourses, 1);
            $average = empty($average) ? '0%' : $average.'%';
            $first = Tracking::get_first_connection_date($studentId);
            $last = Tracking::get_last_connection_date($studentId);
            $timeSpentContent = '';
            $pdfTitle = get_lang('Attestation of attendance');
            if ('attendance' === $type) {
                $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
                $column = 0;
                $row = 0;
                $headers = [
                    get_lang('Time spent'),
                    get_lang('Number of visits'),
                    get_lang('Global progress'),
                    get_lang('First connection'),
                    get_lang('Last connexion date'),
                ];
                foreach ($headers as $header) {
                    $table->setHeaderContents($row, $column, $header);
                    $column++;
                }
                $table->setCellContents(1, 0, api_time_to_hms($timeSpent));
                $table->setCellContents(1, 1, $numberVisits);
                $table->setCellContents(1, 2, $average);
                $table->setCellContents(1, 3, $first);
                $table->setCellContents(1, 4, $last);
                $timeSpentContent = $table->toHtml();
            } else {
                $pdfTitle = get_lang('Certificate of achievement');
            }

            $courseTable = '';
            if (!empty($courses)) {
                $courseTable .= '<table class="table table-hover table-striped data_table">';
                $courseTable .= '<thead>';
                $courseTable .= '<tr>
                    <th>'.get_lang('Formation unit').'</th>
                    <th>'.get_lang('Connection time').'</th>
                    <th>'.get_lang('Progress').'</th>';

                if ('attendance' === $type) {
                    $courseTable .= '<th>'.get_lang('Score').'</th>';
                }
                $courseTable .= '</tr>';
                $courseTable .= '</thead>';
                $courseTable .= '<tbody>';

                $totalCourseTime = 0;
                $totalAttendance = [0, 0];
                $totalScore = 0;
                $totalProgress = 0;
                $gradeBookTotal = [0, 0];
                $totalEvaluations = '0/0 (0%)';

                foreach ($courseEntityList as $course) {
                    $courseInfoItem = api_get_course_info_by_id($course->getId());
                    $courseCodeItem = $courseInfoItem['code'];

                    $isSubscribed = CourseManager::is_user_subscribed_in_course(
                        $studentId,
                        $courseCodeItem,
                        true,
                        $sessionToExport
                    );

                    if ($isSubscribed) {
                        $timeInSeconds = $timeSpentPerCourse[$course->getId()];
                        $totalCourseTime += $timeInSeconds;
                        $time_spent_on_course = api_time_to_hms($timeInSeconds);
                        $progress = $progressPerCourse[$course->getId()];
                        $totalProgress += $progress;

                        $bestScore = Tracking::get_avg_student_score(
                            $studentId,
                            $course,
                            [],
                            $sessionEntity,
                            false,
                            false,
                            true
                        );

                        if (is_numeric($bestScore)) {
                            $totalScore += $bestScore;
                        }

                        $progress = empty($progress) ? '0%' : $progress.'%';
                        $score = empty($bestScore) ? '0%' : $bestScore.'%';

                        $courseTable .= '<tr>
                        <td>
                            <a href="'.$courseInfoItem['course_public_url'].'?id_session='.$sessionToExport.'">'.
                            $courseInfoItem['title'].'</a>
                        </td>
                        <td >'.$time_spent_on_course.'</td>
                        <td >'.$progress.'</td>';
                        if ('attendance' === $type) {
                            $courseTable .= '<td >'.$score.'</td>';
                        }
                        $courseTable .= '</tr>';
                    }
                }

                $totalAttendanceFormatted = $scoreDisplay->display_score($totalAttendance);
                $totalScoreFormatted = $scoreDisplay->display_score([$totalScore / $totalCourses, 100], SCORE_AVERAGE);
                $totalProgressFormatted = $scoreDisplay->display_score(
                    [$totalProgress / $totalCourses, 100],
                    SCORE_AVERAGE
                );
                $totalEvaluations = $scoreDisplay->display_score($gradeBookTotal);
                $totalTimeFormatted = api_time_to_hms($totalCourseTime);
                $courseTable .= '
                    <tr>
                        <th>'.get_lang('Total').'</th>
                        <th>'.$totalTimeFormatted.'</th>
                        <th>'.$totalProgressFormatted.'</th>
                    ';
                if ('attendance' === $type) {
                    $courseTable .= '<th>'.$totalScoreFormatted.'</th>';
                }
                $courseTable .= '</tr>';
                $courseTable .= '</tbody></table>';
            }

            $tpl = new Template('', false, false, false, true, false, false);
            $tpl->assign('title', $pdfTitle);
            $tpl->assign('session_title', $sessionEntity->getTitle());
            $tpl->assign('session_info', $sessionEntity);
            $sessionCategoryTitle = '';
            if ($sessionEntity->getCategory()) {
                $sessionCategoryTitle = $sessionEntity->getCategory()->getTitle();
            }
            $dateData = SessionManager::parseSessionDates($sessionEntity, false);
            $dateToString = $dateData['access'];
            $tpl->assign('session_display_dates', $dateToString);
            $tpl->assign('session_category_title', $sessionCategoryTitle);
            $tpl->assign('student', $studentInfo['complete_name']);
            $tpl->assign('student_info', $studentInfo);
            $tpl->assign('student_info_extra_fields', UserManager::get_extra_user_data($studentInfo['user_id']));
            $tpl->assign('table_progress', $timeSpentContent);
            $tpl->assign(
                'subtitle',
                sprintf(
                    get_lang('In session %s, you had the following results'),
                    $sessionEntity->getTitle()
                )
            );
            $tpl->assign('table_course', $courseTable);
            $template = 'pdf_export_student.tpl';
            if ('achievement' === $type) {
                $template = 'certificate_achievement.tpl';
            }
            $content = $tpl->fetch($tpl->get_template('my_space/'.$template));

            $params = [
                'pdf_title' => get_lang('Resume'),
                'session_info' => $sessionEntity,
                'course_info' => '',
                'pdf_date' => '',
                'student_info' => $studentInfo,
                'show_grade_generated_date' => true,
                'show_real_course_teachers' => false,
                'show_teacher_as_myself' => false,
                'orientation' => 'P',
            ];
            @$pdf = new PDF('A4', $params['orientation'], $params);
            $pdf->setBackground($tpl->theme);
            $mode = 'D';

            $pdfName = $sessionEntity->getTitle().'_'.$studentInfo['complete_name'];
            if ($allStudents) {
                $mode = 'F';
                $pdfName = $studentInfo['complete_name'];
            }
            $pdf->set_footer();
            $result = @$pdf->content_to_pdf(
                $content,
                null,
                $pdfName,
                null,
                $mode,
                false,
                null,
                false,
                true,
                false
            );
            $pdfList[] = $result;
        }

        if (empty($pdfList)) {
            api_not_allowed(true);
        }

        // Creating a ZIP file.
        $tempZipFile = api_get_path(SYS_ARCHIVE_PATH).uniqid('report_session_'.$sessionToExport, true).'.zip';

        $zip = new PclZip($tempZipFile);
        foreach ($pdfList as $file) {
            $zip->add(
                $file,
                PCLZIP_OPT_REMOVE_PATH,
                api_get_path(SYS_ARCHIVE_PATH)
            );
        }
        $name = $sessionEntity->getTitle().'_'.api_get_utc_datetime().'.zip';
        DocumentManager::file_send_for_download($tempZipFile, true, $name);
        exit;
}

// The session list moved to the Vue global reporting; only the PDF export above stays here.
header('Location: /reporting/sessions');
exit;

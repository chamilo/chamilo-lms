<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField as EntityExtraField;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\UserBundle\Entity\User;
use ChamiloSession as Session;
use CpChart\Cache as pCache;
use CpChart\Data as pData;
use CpChart\Image as pImage;
use ExtraField as ExtraFieldModel;

/**
 *  Class Tracking.
 *
 *  @author  Julio Montoya <gugli100@gmail.com>
 *
 *  @package chamilo.library
 */
class Tracking
{
    /**
     * Get group reporting.
     *
     * @param int    $course_id
     * @param int    $sessionId
     * @param int    $group_id
     * @param string $type
     * @param int    $start
     * @param int    $limit
     * @param int    $sidx
     * @param string $sord
     * @param array  $where_condition
     *
     * @return array|null
     */
    public static function get_group_reporting(
        $course_id,
        $sessionId = 0,
        $group_id = 0,
        $type = 'all',
        $start = 0,
        $limit = 1000,
        $sidx = 1,
        $sord = 'desc',
        $where_condition = []
    ) {
        $course_id = (int) $course_id;
        $sessionId = (int) $sessionId;

        if (empty($course_id)) {
            return null;
        }
        $courseInfo = api_get_course_info_by_id($course_id);
        if ($type == 'count') {
            return GroupManager::get_group_list(null, $courseInfo, null, $sessionId, true);
        }

        $groupList = GroupManager::get_group_list(null, $courseInfo, null, $sessionId);
        $parsedResult = [];
        if (!empty($groupList)) {
            foreach ($groupList as $group) {
                $users = GroupManager::get_users($group['id'], true, null, null, false, $courseInfo['real_id']);
                $time = 0;
                $avg_student_score = 0;
                $avg_student_progress = 0;
                $work = 0;
                $messages = 0;

                foreach ($users as $user_data) {
                    $time += self::get_time_spent_on_the_course(
                        $user_data['user_id'],
                        $courseInfo['real_id'],
                        $sessionId
                    );
                    $average = self::get_avg_student_score(
                        $user_data['user_id'],
                        $courseInfo['code'],
                        [],
                        $sessionId
                    );
                    if (is_numeric($average)) {
                        $avg_student_score += $average;
                    }
                    $avg_student_progress += self::get_avg_student_progress(
                        $user_data['user_id'],
                        $courseInfo['code'],
                        [],
                        $sessionId
                    );
                    $work += self::count_student_assignments(
                        $user_data['user_id'],
                        $courseInfo['code'],
                        $sessionId
                    );
                    $messages += self::count_student_messages(
                        $user_data['user_id'],
                        $courseInfo['code'],
                        $sessionId
                    );
                }

                $countUsers = count($users);
                $averageProgress = empty($countUsers) ? 0 : round($avg_student_progress / $countUsers, 2);
                $averageScore = empty($countUsers) ? 0 : round($avg_student_score / $countUsers, 2);

                $groupItem = [
                    'id' => $group['id'],
                    'name' => $group['name'],
                    'time' => api_time_to_hms($time),
                    'progress' => $averageProgress,
                    'score' => $averageScore,
                    'works' => $work,
                    'messages' => $messages,
                ];
                $parsedResult[] = $groupItem;
            }
        }

        return $parsedResult;
    }

    /**
     * @param int    $user_id
     * @param array  $courseInfo
     * @param int    $session_id
     * @param string $origin
     * @param bool   $export_csv
     * @param int    $lp_id
     * @param int    $lp_item_id
     * @param int    $extendId
     * @param int    $extendAttemptId
     * @param string $extendedAttempt
     * @param string $extendedAll
     * @param string $type            classic or simple
     * @param bool   $allowExtend     Optional. Allow or not extend te results
     *
     * @return string
     */
    public static function getLpStats(
        $user_id,
        $courseInfo,
        $session_id,
        $origin,
        $export_csv,
        $lp_id,
        $lp_item_id = null,
        $extendId = null,
        $extendAttemptId = null,
        $extendedAttempt = null,
        $extendedAll = null,
        $type = 'classic',
        $allowExtend = true
    ) {
        if (empty($courseInfo) || empty($lp_id)) {
            return '';
        }

        $hideTime = api_get_configuration_value('hide_lp_time');
        $allowNewTracking = api_get_configuration_value('use_new_tracking_in_lp_item');

        $lp_id = (int) $lp_id;

        if ($allowNewTracking) {
            $extraField = new ExtraFieldValue('lp');
            $result = $extraField->get_values_by_handler_and_field_variable($lp_id, 'track_lp_item');
            if (empty($result)) {
                $allowNewTracking = false;
            } else {
                if (isset($result['value']) && $result['value'] == 1) {
                    $allowNewTracking = true;
                }
            }
        }

        $lp_item_id = (int) $lp_item_id;
        $user_id = (int) $user_id;
        $session_id = (int) $session_id;
        $origin = Security::remove_XSS($origin);
        $list = learnpath::get_flat_ordered_items_list($lp_id, 0, $courseInfo['real_id']);
        $is_allowed_to_edit = api_is_allowed_to_edit(null, true);
        $course_id = $courseInfo['real_id'];
        $courseCode = $courseInfo['code'];
        $session_condition = api_get_session_condition($session_id);

        // Extend all button
        $output = '';
        $url_suffix = '&lp_id='.$lp_id;
        if ($origin === 'tracking') {
            $url_suffix = '&session_id='.$session_id.'&course='.$courseCode.'&student_id='.$user_id.'&lp_id='.$lp_id.'&origin='.$origin;
        }

        $extend_all = 0;
        if (!empty($extendedAll)) {
            $extend_all_link = Display::url(
                Display::return_icon('view_less_stats.gif', get_lang('Hide all attempts')),
                api_get_self().'?action=stats'.$url_suffix
            );
            $extend_all = 1;
        } else {
            $extend_all_link = Display::url(
                Display::return_icon('view_more_stats.gif', get_lang('Show all attempts')),
                api_get_self().'?action=stats&extend_all=1'.$url_suffix
            );
        }

        if ($origin != 'tracking') {
            $output .= '<div class="section-status">';
            $output .= Display::page_header(get_lang('My progress'));
            $output .= '</div>';
        }

        $actionColumn = null;
        if ($type === 'classic') {
            $actionColumn = ' <th>'.get_lang('Detail').'</th>';
        }

        $timeHeader = '<th class="lp_time" colspan="2">'.get_lang('Time').'</th>';
        if ($hideTime) {
            $timeHeader = '';
        }
        $output .= '<div class="table-responsive">';
        $output .= '<table id="lp_tracking" class="table tracking">
            <thead>
            <tr class="table-header">
                <th width="16">'.($allowExtend == true ? $extend_all_link : '&nbsp;').'</th>
                <th colspan="4">
                    '.get_lang('Learning object name').'
                </th>
                <th colspan="2">
                    '.get_lang('Status').'
                </th>
                <th colspan="2">
                    '.get_lang('Score').'
                </th>
                '.$timeHeader.'
                '.$actionColumn.'
                </tr>
            </thead>
            <tbody>
        ';

        // Going through the items using the $items[] array instead of the database order ensures
        // we get them in the same order as in the imsmanifest file, which is rather random when using
        // the database table.
        $TBL_LP_ITEM = Database::get_course_table(TABLE_LP_ITEM);
        $TBL_LP_ITEM_VIEW = Database::get_course_table(TABLE_LP_ITEM_VIEW);
        $TBL_LP_VIEW = Database::get_course_table(TABLE_LP_VIEW);
        $tbl_quiz_questions = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $TBL_QUIZ = Database::get_course_table(TABLE_QUIZ_TEST);
        $tbl_stats_exercices = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $tbl_stats_attempts = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        $sql = "SELECT max(view_count)
                FROM $TBL_LP_VIEW
                WHERE
                    c_id = $course_id AND
                    lp_id = $lp_id AND
                    user_id = $user_id
                    $session_condition";
        $res = Database::query($sql);
        $view = 0;
        if (Database::num_rows($res) > 0) {
            $myrow = Database::fetch_array($res);
            $view = (int) $myrow[0];
        }

        $counter = 0;
        $total_time = 0;
        $h = get_lang('h');

        if (!empty($export_csv)) {
            $csvHeaders = [
                get_lang('Learning object name'),
                get_lang('Status'),
                get_lang('Score'),
            ];

            if ($hideTime === false) {
                $csvHeaders[] = get_lang('Time');
            }

            $csv_content[] = $csvHeaders;
        }

        $result_disabled_ext_all = true;
        $chapterTypes = learnpath::getChapterTypes();
        $accessToPdfExport = api_is_allowed_to_edit(false, false, true);

        $minimunAvailable = self::minimumTimeAvailable($session_id, $course_id);
        $timeCourse = [];
        if ($minimunAvailable) {
            $timeCourse = self::getCalculateTime($user_id, $course_id, $session_id);
            Session::write('trackTimeCourse', $timeCourse);
        }

        // Show lp items
        if (is_array($list) && count($list) > 0) {
            foreach ($list as $my_item_id) {
                $extend_this = 0;
                $order = 'DESC';
                if ((!empty($extendId) && $extendId == $my_item_id) || $extend_all) {
                    $extend_this = 1;
                    $order = 'ASC';
                }

                // Prepare statement to go through each attempt.
                $viewCondition = null;
                if (!empty($view)) {
                    $viewCondition = " AND v.view_count = $view  ";
                }

                $sql = "SELECT
                    iv.status as mystatus,
                    v.view_count as mycount,
                    iv.score as myscore,
                    iv.total_time as mytime,
                    i.iid as myid,
                    i.lp_id as mylpid,
                    iv.lp_view_id as mylpviewid,
                    i.title as mytitle,
                    i.max_score as mymaxscore,
                    iv.max_score as myviewmaxscore,
                    i.item_type as item_type,
                    iv.view_count as iv_view_count,
                    iv.id as iv_id,
                    path
                FROM $TBL_LP_ITEM as i
                INNER JOIN $TBL_LP_ITEM_VIEW as iv
                ON (i.iid = iv.lp_item_id AND i.c_id = iv.c_id)
                INNER JOIN $TBL_LP_VIEW as v
                ON (iv.lp_view_id = v.id AND v.c_id = iv.c_id)
                WHERE
                    v.c_id = $course_id AND
                    i.iid = $my_item_id AND
                    i.lp_id = $lp_id  AND
                    v.user_id = $user_id AND
                    v.session_id = $session_id
                    $viewCondition
                ORDER BY iv.view_count $order ";

                $result = Database::query($sql);
                $num = Database::num_rows($result);
                $time_for_total = 0;
                $attemptResult = 0;

                if ($allowNewTracking && $timeCourse) {
                    if (isset($timeCourse['learnpath_detailed']) &&
                        isset($timeCourse['learnpath_detailed'][$lp_id]) &&
                        isset($timeCourse['learnpath_detailed'][$lp_id][$my_item_id])
                    ) {
                        $attemptResult = $timeCourse['learnpath_detailed'][$lp_id][$my_item_id][$view];
                    }
                }

                // Extend all
                if (($extend_this || $extend_all) && $num > 0) {
                    $row = Database::fetch_array($result);
                    $result_disabled_ext_all = false;
                    if ($row['item_type'] === 'quiz') {
                        // Check results_disabled in quiz table.
                        $my_path = Database::escape_string($row['path']);
                        $sql = "SELECT results_disabled
                                FROM $TBL_QUIZ
                                WHERE
                                    c_id = $course_id AND
                                    id ='".$my_path."'";
                        $res_result_disabled = Database::query($sql);
                        $row_result_disabled = Database::fetch_row($res_result_disabled);

                        if (Database::num_rows($res_result_disabled) > 0 &&
                            (int) $row_result_disabled[0] === 1
                        ) {
                            $result_disabled_ext_all = true;
                        }
                    }

                    // If there are several attempts, and the link to extend has been clicked, show each attempt...
                    $oddclass = 'row_even';
                    if (($counter % 2) === 0) {
                        $oddclass = 'row_odd';
                    }
                    $extend_link = '';
                    if (!empty($inter_num)) {
                        $extend_link = Display::url(
                            Display::return_icon(
                                'visible.png',
                                get_lang('Hide attempt view')
                            ),
                            api_get_self().'?action=stats&fold_id='.$my_item_id.$url_suffix
                        );
                    }
                    $title = $row['mytitle'];

                    if (empty($title)) {
                        $title = learnpath::rl_get_resource_name($courseInfo['code'], $lp_id, $row['myid']);
                    }

                    if (in_array($row['item_type'], $chapterTypes)) {
                        $title = "<h4> $title </h4>";
                    }
                    $lesson_status = $row['mystatus'];
                    $title = Security::remove_XSS($title);
                    $counter++;

                    $action = null;
                    if ($type === 'classic') {
                        $action = '<td></td>';
                    }

                    if (in_array($row['item_type'], $chapterTypes)) {
                        $output .= '<tr class="'.$oddclass.'">
                                <td>'.$extend_link.'</td>
                                <td colspan="4">
                                   '.$title.'
                                </td>
                                <td colspan="2">'.learnpathItem::humanize_status($lesson_status, true, $type).'</td>
                                <td colspan="2"></td>
                                <td colspan="2"></td>
                                '.$action.'
                            </tr>';
                        continue;
                    } else {
                        $output .= '<tr class="'.$oddclass.'">
                                <td>'.$extend_link.'</td>
                                <td colspan="4">'.$title.'</td>
                                <td colspan="2"></td>
                                <td colspan="2"></td>
                                <td colspan="2"></td>
                                '.$action.'
                            </tr>';
                    }

                    $attemptCount = 1;
                    do {
                        // Check if there are interactions below.
                        $extend_attempt_link = '';
                        $extend_this_attempt = 0;

                        if ($allowNewTracking && $timeCourse) {
                            //$attemptResult = 0;
                            if (isset($timeCourse['learnpath_detailed']) &&
                                isset($timeCourse['learnpath_detailed'][$lp_id]) &&
                                isset($timeCourse['learnpath_detailed'][$lp_id][$my_item_id])
                            ) {
                                $attemptResult = $timeCourse['learnpath_detailed'][$lp_id][$my_item_id][$row['iv_view_count']];
                            }
                        }
                        if ((
                            learnpath::get_interactions_count_from_db($row['iv_id'], $course_id) > 0 ||
                            learnpath::get_objectives_count_from_db($row['iv_id'], $course_id) > 0
                            ) &&
                            !$extend_all
                        ) {
                            if ($extendAttemptId == $row['iv_id']) {
                                // The extend button for this attempt has been clicked.
                                $extend_this_attempt = 1;
                                $extend_attempt_link = Display::url(
                                    Display::return_icon('visible.png', get_lang('Hide attempt view')),
                                    api_get_self().'?action=stats&extend_id='.$my_item_id.'&fold_attempt_id='.$row['iv_id'].$url_suffix
                                );
                                if ($accessToPdfExport) {
                                    $extend_attempt_link .= '&nbsp;'.
                                        Display::url(
                                            Display::return_icon('pdf.png', get_lang('Export to PDF')),
                                            api_get_self(
                                            ).'?action=export_stats&extend_id='.$my_item_id.'&extend_attempt_id='.$row['iv_id'].$url_suffix
                                        );
                                }
                            } else { // Same case if fold_attempt_id is set, so not implemented explicitly.
                                // The extend button for this attempt has not been clicked.
                                $extend_attempt_link = Display::url(
                                    Display::return_icon('invisible.png', get_lang('Extend attempt view')),
                                    api_get_self().'?action=stats&extend_id='.$my_item_id.'&extend_attempt_id='.$row['iv_id'].$url_suffix
                                );
                                if ($accessToPdfExport) {
                                    $extend_attempt_link .= '&nbsp;'.
                                        Display::url(
                                            Display::return_icon('pdf.png', get_lang('Export to PDF')),
                                            api_get_self(
                                            ).'?action=export_stats&extend_id='.$my_item_id.'&extend_attempt_id='.$row['iv_id'].$url_suffix
                                        );
                                }
                            }
                        }

                        $oddclass = 'row_even';
                        if (($counter % 2) == 0) {
                            $oddclass = 'row_odd';
                        }

                        $lesson_status = $row['mystatus'];
                        $score = $row['myscore'];
                        $time_for_total = $row['mytime'];
                        $attemptTime = $row['mytime'];

                        if ($minimunAvailable) {
                            $lp_time = $timeCourse[TOOL_LEARNPATH];
                            $lpTime = null;
                            if (isset($lp_time[$lp_id])) {
                                $lpTime = (int) $lp_time[$lp_id];
                            }
                            $time_for_total = $lpTime;

                            if ($allowNewTracking) {
                                $time_for_total = (int) $attemptResult;
                                $attemptTime = (int) $attemptResult;
                            }
                        }

                        $time = learnpathItem::getScormTimeFromParameter('js', $attemptTime);

                        if ($score == 0) {
                            $maxscore = $row['mymaxscore'];
                        } else {
                            if ($row['item_type'] === 'sco') {
                                if (!empty($row['myviewmaxscore']) && $row['myviewmaxscore'] > 0) {
                                    $maxscore = $row['myviewmaxscore'];
                                } elseif ($row['myviewmaxscore'] === '') {
                                    $maxscore = 0;
                                } else {
                                    $maxscore = $row['mymaxscore'];
                                }
                            } else {
                                $maxscore = $row['mymaxscore'];
                            }
                        }

                        // Remove "NaN" if any (@todo: locate the source of these NaN)
                        $time = str_replace('NaN', '00'.$h.'00\'00"', $time);

                        if ($row['item_type'] !== 'dir') {
                            if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                                $view_score = Display::return_icon(
                                    'invisible.png',
                                    get_lang('Results hidden by the exercise setting')
                                );
                            } else {
                                switch ($row['item_type']) {
                                    case 'sco':
                                        if ($maxscore == 0) {
                                            $view_score = $score;
                                        } else {
                                            $view_score = ExerciseLib::show_score(
                                                $score,
                                                $maxscore,
                                                false
                                            );
                                        }
                                        break;
                                    case 'document':
                                        $view_score = ($score == 0 ? '/' : ExerciseLib::show_score($score, $maxscore, false));
                                        break;
                                    default:
                                        $view_score = ExerciseLib::show_score(
                                            $score,
                                            $maxscore,
                                            false
                                        );
                                        break;
                                }
                            }

                            $action = null;
                            if ($type == 'classic') {
                                $action = '<td></td>';
                            }
                            $timeRow = '<td class="lp_time" colspan="2">'.$time.'</td>';
                            if ($hideTime) {
                                $timeRow = '';
                            }
                            $output .= '<tr class="'.$oddclass.'">
                                    <td></td>
                                    <td style="width:70px;float:left;">'.$extend_attempt_link.'</td>
                                    <td colspan="3">'.get_lang('Attempt').' '.$attemptCount.'</td>
                                    <td colspan="2">'.learnpathItem::humanize_status($lesson_status, true, $type).'</td>
                                    <td colspan="2">'.$view_score.'</td>
                                    '.$timeRow.'
                                    '.$action.'
                                </tr>';
                            $attemptCount++;
                            if (!empty($export_csv)) {
                                $temp = [];
                                $temp[] = $title = Security::remove_XSS($title);
                                $temp[] = Security::remove_XSS(
                                    learnpathItem::humanize_status($lesson_status, false, $type)
                                );

                                if ($row['item_type'] === 'quiz') {
                                    if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                                        $temp[] = '/';
                                    } else {
                                        $temp[] = ($score == 0 ? '0/'.$maxscore : ($maxscore == 0 ? $score : $score.'/'.float_format($maxscore, 1)));
                                    }
                                } else {
                                    $temp[] = ($score == 0 ? '/' : ($maxscore == 0 ? $score : $score.'/'.float_format($maxscore, 1)));
                                }

                                if ($hideTime === false) {
                                    $temp[] = $time;
                                }
                                $csv_content[] = $temp;
                            }
                        }

                        $counter++;
                        $action = null;
                        if ($type === 'classic') {
                            $action = '<td></td>';
                        }

                        if ($extend_this_attempt || $extend_all) {
                            $list1 = learnpath::get_iv_interactions_array($row['iv_id'], $course_id);
                            foreach ($list1 as $id => $interaction) {
                                $oddclass = 'row_even';
                                if (($counter % 2) == 0) {
                                    $oddclass = 'row_odd';
                                }
                                $timeRow = '<td class="lp_time">'.$interaction['time'].'</td>';
                                if ($hideTime) {
                                    $timeRow = '';
                                }

                                $output .= '<tr class="'.$oddclass.'">
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>'.$interaction['order_id'].'</td>
                                        <td>'.$interaction['id'].'</td>';

                                $output .= '
                                        <td colspan="2">'.$interaction['type'].'</td>
                                        <td>'.$interaction['student_response_formatted'].'</td>
                                        <td>'.$interaction['result'].'</td>
                                        <td>'.$interaction['latency'].'</td>
                                        '.$timeRow.'
                                        '.$action.'
                                    </tr>';
                                $counter++;
                            }
                            $list2 = learnpath::get_iv_objectives_array($row['iv_id'], $course_id);
                            foreach ($list2 as $id => $interaction) {
                                $oddclass = 'row_even';
                                if (($counter % 2) === 0) {
                                    $oddclass = 'row_odd';
                                }
                                $output .= '<tr class="'.$oddclass.'">
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>'.$interaction['order_id'].'</td>
                                        <td colspan="2">'.$interaction['objective_id'].'</td>
                                        <td colspan="2">'.$interaction['status'].'</td>
                                        <td>'.$interaction['score_raw'].'</td>
                                        <td>'.$interaction['score_max'].'</td>
                                        <td>'.$interaction['score_min'].'</td>
                                        '.$action.'
                                     </tr>';
                                $counter++;
                            }
                        }
                    } while ($row = Database::fetch_array($result));
                } elseif ($num > 0) {
                    // Not extended.
                    $row = Database::fetch_array($result, 'ASSOC');
                    $my_id = $row['myid'];
                    $my_lp_id = $row['mylpid'];
                    $my_lp_view_id = $row['mylpviewid'];
                    $my_path = $row['path'];
                    $result_disabled_ext_all = false;
                    if ($row['item_type'] === 'quiz') {
                        // Check results_disabled in quiz table.
                        $my_path = Database::escape_string($my_path);
                        $sql = "SELECT results_disabled
                                FROM $TBL_QUIZ
                                WHERE c_id = $course_id AND id = '$my_path' ";
                        $res_result_disabled = Database::query($sql);
                        $row_result_disabled = Database::fetch_row($res_result_disabled);

                        if (Database::num_rows($res_result_disabled) > 0 &&
                            (int) $row_result_disabled[0] === 1
                        ) {
                            $result_disabled_ext_all = true;
                        }
                    }

                    // Check if there are interactions below
                    $extend_this_attempt = 0;
                    $inter_num = learnpath::get_interactions_count_from_db($row['iv_id'], $course_id);
                    $objec_num = learnpath::get_objectives_count_from_db($row['iv_id'], $course_id);
                    $extend_attempt_link = '';
                    if ($inter_num > 0 || $objec_num > 0) {
                        if (!empty($extendAttemptId) && $extendAttemptId == $row['iv_id']) {
                            // The extend button for this attempt has been clicked.
                            $extend_this_attempt = 1;
                            $extend_attempt_link = Display::url(
                                Display::return_icon('visible.png', get_lang('Hide attempt view')),
                                api_get_self().'?action=stats&extend_id='.$my_item_id.'&fold_attempt_id='.$row['iv_id'].$url_suffix
                            );
                        } else {
                            // Same case if fold_attempt_id is set, so not implemented explicitly.
                            // The extend button for this attempt has not been clicked.
                            $extend_attempt_link = Display::url(
                                Display::return_icon('invisible.png', get_lang('Extend attempt view')),
                                api_get_self().'?action=stats&extend_id='.$my_item_id.'&extend_attempt_id='.$row['iv_id'].$url_suffix
                            );
                        }
                    }

                    $oddclass = 'row_even';
                    if (($counter % 2) == 0) {
                        $oddclass = 'row_odd';
                    }

                    $extend_link = '';
                    if ($inter_num > 1) {
                        $extend_link = Display::url(
                            Display::return_icon('invisible.png', get_lang('Extend attempt view')),
                            api_get_self().'?action=stats&extend_id='.$my_item_id.'&extend_attempt_id='.$row['iv_id'].$url_suffix
                        );
                    }

                    $lesson_status = $row['mystatus'];
                    $score = $row['myscore'];
                    $subtotal_time = $row['mytime'];
                    while ($tmp_row = Database::fetch_array($result)) {
                        $subtotal_time += $tmp_row['mytime'];
                    }

                    if ($allowNewTracking) {
                        $subtotal_time = $attemptResult;
                    }

                    $title = $row['mytitle'];
                    // Selecting the exe_id from stats attempts tables in order to look the max score value.
                    $sql = 'SELECT * FROM '.$tbl_stats_exercices.'
                            WHERE
                                exe_exo_id="'.$row['path'].'" AND
                                exe_user_id="'.$user_id.'" AND
                                orig_lp_id = "'.$lp_id.'" AND
                                orig_lp_item_id = "'.$row['myid'].'" AND
                                c_id = '.$course_id.' AND
                                status <> "incomplete" AND
                                session_id = '.$session_id.'
                             ORDER BY exe_date DESC
                             LIMIT 1';

                    $resultLastAttempt = Database::query($sql);
                    $num = Database::num_rows($resultLastAttempt);
                    $id_last_attempt = null;
                    if ($num > 0) {
                        while ($rowLA = Database::fetch_array($resultLastAttempt)) {
                            $id_last_attempt = $rowLA['exe_id'];
                        }
                    }

                    switch ($row['item_type']) {
                        case 'sco':
                            if (!empty($row['myviewmaxscore']) && $row['myviewmaxscore'] > 0) {
                                $maxscore = $row['myviewmaxscore'];
                            } elseif ($row['myviewmaxscore'] === '') {
                                $maxscore = 0;
                            } else {
                                $maxscore = $row['mymaxscore'];
                            }
                            break;
                        case 'quiz':
                            // Get score and total time from last attempt of a exercise en lp.
                            $sql = "SELECT iid, score
                                    FROM $TBL_LP_ITEM_VIEW
                                    WHERE
                                        c_id = $course_id AND
                                        lp_item_id = '".(int) $my_id."' AND
                                        lp_view_id = '".(int) $my_lp_view_id."'
                                    ORDER BY view_count DESC 
                                    LIMIT 1";
                            $res_score = Database::query($sql);
                            $row_score = Database::fetch_array($res_score);

                            $sql = "SELECT SUM(total_time) as total_time
                                    FROM $TBL_LP_ITEM_VIEW
                                    WHERE
                                        c_id = $course_id AND
                                        lp_item_id = '".(int) $my_id."' AND
                                        lp_view_id = '".(int) $my_lp_view_id."'";
                            $res_time = Database::query($sql);
                            $row_time = Database::fetch_array($res_time);

                            $score = 0;
                            $subtotal_time = 0;
                            if (Database::num_rows($res_score) > 0 &&
                                Database::num_rows($res_time) > 0
                            ) {
                                $score = (float) $row_score['score'];
                                $subtotal_time = (int) $row_time['total_time'];
                            }
                            // Selecting the max score from an attempt.
                            $sql = "SELECT SUM(t.ponderation) as maxscore
                                    FROM (
                                        SELECT DISTINCT
                                            question_id, marks, ponderation
                                        FROM $tbl_stats_attempts as at
                                        INNER JOIN $tbl_quiz_questions as q
                                        ON (q.id = at.question_id AND q.c_id = $course_id)
                                        WHERE exe_id ='$id_last_attempt'
                                    ) as t";

                            $result = Database::query($sql);
                            $row_max_score = Database::fetch_array($result);
                            $maxscore = $row_max_score['maxscore'];

                            // Get duration time from track_e_exercises.exe_duration instead of lp_view_item.total_time
                            $sql = 'SELECT SUM(exe_duration) exe_duration
                                    FROM '.$tbl_stats_exercices.'
                                    WHERE
                                        exe_exo_id="'.$row['path'].'" AND
                                        exe_user_id="'.$user_id.'" AND
                                        orig_lp_id = "'.$lp_id.'" AND
                                        orig_lp_item_id = "'.$row['myid'].'" AND
                                        c_id = '.$course_id.' AND
                                        status <> "incomplete" AND
                                        session_id = '.$session_id.'
                                     ORDER BY exe_date DESC ';
                            $sumScoreResult = Database::query($sql);
                            $durationRow = Database::fetch_array($sumScoreResult, 'ASSOC');
                            if (!empty($durationRow['exe_duration'])) {
                                $exeDuration = $durationRow['exe_duration'];
                                if ($exeDuration != $subtotal_time &&
                                    !empty($row_score['iid']) &&
                                    !empty($exeDuration)
                                ) {
                                    $subtotal_time = $exeDuration;
                                    // Update c_lp_item_view.total_time
                                    $sqlUpdate = "UPDATE $TBL_LP_ITEM_VIEW SET total_time = '$exeDuration' 
                                                  WHERE iid = ".$row_score['iid'];
                                    Database::query($sqlUpdate);
                                }
                            }
                            break;
                        default:
                            $maxscore = $row['mymaxscore'];
                            break;
                    }

                    $time_for_total = $subtotal_time;
                    $time = learnpathItem::getScormTimeFromParameter('js', $subtotal_time);
                    if (empty($title)) {
                        $title = learnpath::rl_get_resource_name(
                            $courseInfo['code'],
                            $lp_id,
                            $row['myid']
                        );
                    }

                    $action = null;
                    if ($type == 'classic') {
                        $action = '<td></td>';
                    }

                    if (in_array($row['item_type'], $chapterTypes)) {
                        $title = Security::remove_XSS($title);
                        $output .= '<tr class="'.$oddclass.'">
                                <td>'.$extend_link.'</td>
                                <td colspan="4">
                                <h4>'.$title.'</h4>
                                </td>
                                <td colspan="2">'.learnpathitem::humanize_status($lesson_status).'</td>
                                <td colspan="2"></td>
                                <td colspan="2"></td>
                                '.$action.'
                            </tr>';
                    } else {
                        $correct_test_link = '-';
                        $showRowspan = false;
                        if ($row['item_type'] === 'quiz') {
                            $my_url_suffix = '&course='.$courseCode.'&student_id='.$user_id.'&lp_id='.intval($row['mylpid']).'&origin='.$origin;
                            $sql = 'SELECT * FROM '.$tbl_stats_exercices.'
                                     WHERE
                                        exe_exo_id="'.$row['path'].'" AND
                                        exe_user_id="'.$user_id.'" AND
                                        orig_lp_id = "'.$lp_id.'" AND
                                        orig_lp_item_id = "'.$row['myid'].'" AND
                                        c_id = '.$course_id.' AND
                                        status <> "incomplete" AND
                                        session_id = '.$session_id.'
                                     ORDER BY exe_date DESC ';

                            $resultLastAttempt = Database::query($sql);
                            $num = Database::num_rows($resultLastAttempt);
                            $showRowspan = false;
                            if ($num > 0) {
                                $linkId = 'link_'.$my_id;
                                if ($extendedAttempt == 1 &&
                                    $lp_id == $my_lp_id &&
                                    $lp_item_id == $my_id
                                ) {
                                    $showRowspan = true;
                                    $correct_test_link = Display::url(
                                        Display::return_icon(
                                            'view_less_stats.gif',
                                            get_lang('Hide all attempts')
                                        ),
                                        api_get_self().'?action=stats'.$my_url_suffix.'&session_id='.$session_id.'&lp_item_id='.$my_id.'#'.$linkId,
                                        ['id' => $linkId]
                                    );
                                } else {
                                    $correct_test_link = Display::url(
                                        Display::return_icon(
                                            'view_more_stats.gif',
                                            get_lang(
                                                'Show all attemptsByExercise'
                                            )
                                        ),
                                        api_get_self().'?action=stats&extend_attempt=1'.$my_url_suffix.'&session_id='.$session_id.'&lp_item_id='.$my_id.'#'.$linkId,
                                        ['id' => $linkId]
                                    );
                                }
                            }
                        }

                        $title = Security::remove_XSS($title);
                        $action = null;
                        if ($type === 'classic') {
                            $action = '<td '.($showRowspan ? 'rowspan="2"' : '').'>'.$correct_test_link.'</td>';
                        }

                        if ($lp_id == $my_lp_id && false) {
                            $output .= '<tr class ='.$oddclass.'>
                                    <td>'.$extend_link.'</td>
                                    <td colspan="4">'.$title.'</td>
                                    <td colspan="2">&nbsp;</td>
                                    <td colspan="2">&nbsp;</td>
                                    <td colspan="2">&nbsp;</td>
                                    '.$action.'
                                </tr>';
                            $output .= '</tr>';
                        } else {
                            if ($lp_id == $my_lp_id && $lp_item_id == $my_id) {
                                $output .= "<tr class='$oddclass'>";
                            } else {
                                $output .= "<tr class='$oddclass'>";
                            }

                            $scoreItem = null;
                            if ($row['item_type'] == 'quiz') {
                                if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                                    $scoreItem .= Display::return_icon(
                                        'invisible.png',
                                        get_lang('Results hidden by the exercise setting')
                                    );
                                } else {
                                    $scoreItem .= ExerciseLib::show_score($score, $maxscore, false);
                                }
                            } else {
                                $scoreItem .= $score == 0 ? '/' : ($maxscore == 0 ? $score : $score.'/'.$maxscore);
                            }

                            $timeRow = '<td class="lp_time" colspan="2">'.$time.'</td>';
                            if ($hideTime) {
                                $timeRow = '';
                            }

                            $output .= '
                                <td>'.$extend_link.'</td>
                                <td colspan="4">'.$title.'</td>
                                <td colspan="2">'.learnpathitem::humanize_status($lesson_status).'</td>
                                <td colspan="2">'.$scoreItem.'</td>
                                '.$timeRow.'
                                '.$action.'
                             ';
                            $output .= '</tr>';
                        }

                        if (!empty($export_csv)) {
                            $temp = [];
                            $temp[] = api_html_entity_decode($title, ENT_QUOTES);
                            $temp[] = api_html_entity_decode($lesson_status, ENT_QUOTES);
                            if ($row['item_type'] === 'quiz') {
                                if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                                    $temp[] = '/';
                                } else {
                                    $temp[] = ($score == 0 ? '0/'.$maxscore : ($maxscore == 0 ? $score : $score.'/'.float_format($maxscore, 1)));
                                }
                            } else {
                                $temp[] = ($score == 0 ? '/' : ($maxscore == 0 ? $score : $score.'/'.float_format($maxscore, 1)));
                            }

                            if ($hideTime === false) {
                                $temp[] = $time;
                            }
                            $csv_content[] = $temp;
                        }
                    }

                    $counter++;
                    $action = null;
                    if ($type === 'classic') {
                        $action = '<td></td>';
                    }

                    if ($extend_this_attempt || $extend_all) {
                        $list1 = learnpath::get_iv_interactions_array($row['iv_id'], $course_id);
                        foreach ($list1 as $id => $interaction) {
                            if (($counter % 2) == 0) {
                                $oddclass = 'row_odd';
                            } else {
                                $oddclass = 'row_even';
                            }

                            $timeRow = '<td class="lp_time">'.$interaction['time'].'</td>';
                            if ($hideTime) {
                                $timeRow = '';
                            }

                            $output .= '<tr class="'.$oddclass.'">
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td>'.$interaction['order_id'].'</td>
                                    <td>'.$interaction['id'].'</td>
                                    <td colspan="2">'.$interaction['type'].'</td>
                                    <td>'.urldecode($interaction['student_response']).'</td>
                                    <td>'.$interaction['result'].'</td>
                                    <td>'.$interaction['latency'].'</td>
                                    '.$timeRow.'
                                    '.$action.'
                               </tr>';
                            $counter++;
                        }
                        $list2 = learnpath::get_iv_objectives_array($row['iv_id'], $course_id);

                        foreach ($list2 as $id => $interaction) {
                            if (($counter % 2) == 0) {
                                $oddclass = 'row_odd';
                            } else {
                                $oddclass = 'row_even';
                            }
                            $output .= '<tr class="'.$oddclass.'">
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td>'.$interaction['order_id'].'</td>
                                    <td colspan="2">'.$interaction['objective_id'].'</td>
                                    <td colspan="2">'.$interaction['status'].'</td>
                                    <td>'.$interaction['score_raw'].'</td>
                                    <td>'.$interaction['score_max'].'</td>
                                    <td>'.$interaction['score_min'].'</td>
                                    '.$action.'
                               </tr>';
                            $counter++;
                        }
                    }

                    // Attempts listing by exercise.
                    if ($lp_id == $my_lp_id && $lp_item_id == $my_id && $extendedAttempt) {
                        // Get attempts of a exercise.
                        if (!empty($lp_id) &&
                            !empty($lp_item_id) &&
                            $row['item_type'] === 'quiz'
                        ) {
                            $sql = "SELECT path FROM $TBL_LP_ITEM
                                    WHERE
                                        c_id = $course_id AND
                                        iid = '$lp_item_id' AND
                                        lp_id = '$lp_id'";
                            $res_path = Database::query($sql);
                            $row_path = Database::fetch_array($res_path);

                            if (Database::num_rows($res_path) > 0) {
                                $sql = 'SELECT * FROM '.$tbl_stats_exercices.'
                                        WHERE
                                            exe_exo_id="'.(int) $row_path['path'].'" AND
                                            status <> "incomplete" AND
                                            exe_user_id="'.$user_id.'" AND
                                            orig_lp_id = "'.(int) $lp_id.'" AND
                                            orig_lp_item_id = "'.(int) $lp_item_id.'" AND
                                            c_id = '.$course_id.'  AND
                                            session_id = '.$session_id.'
                                        ORDER BY exe_date';
                                $res_attempts = Database::query($sql);
                                $num_attempts = Database::num_rows($res_attempts);
                                if ($num_attempts > 0) {
                                    $n = 1;
                                    while ($row_attempts = Database::fetch_array($res_attempts)) {
                                        $my_score = $row_attempts['score'];
                                        $my_maxscore = $row_attempts['max_score'];
                                        $my_exe_id = $row_attempts['exe_id'];
                                        $mktime_start_date = api_strtotime($row_attempts['start_date'], 'UTC');
                                        $mktime_exe_date = api_strtotime($row_attempts['exe_date'], 'UTC');
                                        $time_attemp = ' - ';
                                        if ($mktime_start_date && $mktime_exe_date) {
                                            $time_attemp = api_format_time($row_attempts['exe_duration'], 'js');
                                        }
                                        if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                                            $view_score = Display::return_icon(
                                                'invisible.png',
                                                get_lang(
                                                    'Results hidden by the exercise setting'
                                                )
                                            );
                                        } else {
                                            // Show only float when need it
                                            if ($my_score == 0) {
                                                $view_score = ExerciseLib::show_score(
                                                    0,
                                                    $my_maxscore,
                                                    false
                                                );
                                            } else {
                                                if ($my_maxscore == 0) {
                                                    $view_score = $my_score;
                                                } else {
                                                    $view_score = ExerciseLib::show_score(
                                                        $my_score,
                                                        $my_maxscore,
                                                        false
                                                    );
                                                }
                                            }
                                        }
                                        $my_lesson_status = $row_attempts['status'];
                                        if ($my_lesson_status == '') {
                                            $my_lesson_status = learnpathitem::humanize_status('completed');
                                        } elseif ($my_lesson_status == 'incomplete') {
                                            $my_lesson_status = learnpathitem::humanize_status('incomplete');
                                        }
                                        $timeRow = '<td class="lp_time" colspan="2">'.$time_attemp.'</td>';
                                        if ($hideTime) {
                                            $timeRow = '';
                                        }

                                        $output .= '<tr class="'.$oddclass.'" >
                                        <td></td>
                                        <td>'.$extend_attempt_link.'</td>
                                        <td colspan="3">'.get_lang('Attempt').' '.$n.'</td>
                                        <td colspan="2">'.$my_lesson_status.'</td>
                                        <td colspan="2">'.$view_score.'</td>
                                        '.$timeRow;

                                        if ($action == 'classic') {
                                            if ($origin != 'tracking') {
                                                if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                                                    $output .= '<td>
                                                            <img src="'.Display::returnIconPath('quiz_na.gif').'" alt="'.get_lang('Show attempt').'" title="'.get_lang('Show attempt').'">
                                                            </td>';
                                                } else {
                                                    $output .= '<td>
                                                            <a href="../exercise/exercise_show.php?origin='.$origin.'&id='.$my_exe_id.'&cidReq='.$courseCode.'" target="_parent">
                                                            <img src="'.Display::returnIconPath('quiz.png').'" alt="'.get_lang('Show attempt').'" title="'.get_lang('Show attempt').'">
                                                            </a></td>';
                                                }
                                            } else {
                                                if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                                                    $output .= '<td>
                                                                <img src="'.Display::returnIconPath('quiz_na.gif').'" alt="'.get_lang('Show and grade attempt').'" title="'.get_lang('Show and grade attempt').'"></td>';
                                                } else {
                                                    $output .= '<td>
                                                                    <a href="../exercise/exercise_show.php?cidReq='.$courseCode.'&origin=correct_exercise_in_lp&id='.$my_exe_id.'" target="_parent">
                                                                    <img src="'.Display::returnIconPath('quiz.gif').'" alt="'.get_lang('Show and grade attempt').'" title="'.get_lang('Show and grade attempt').'"></a></td>';
                                                }
                                            }
                                        }
                                        $output .= '</tr>';
                                        $n++;
                                    }
                                }
                                $output .= '<tr><td colspan="12">&nbsp;</td></tr>';
                            }
                        }
                    }
                }

                $total_time += $time_for_total;
                // QUIZZ IN LP
                $a_my_id = [];
                if (!empty($my_lp_id)) {
                    $a_my_id[] = $my_lp_id;
                }
            }
        }

        // NOT Extend all "left green cross"
        if (!empty($a_my_id)) {
            if ($extendedAttempt) {
                // "Right green cross" extended
                $total_score = self::get_avg_student_score(
                    $user_id,
                    $course_id,
                    $a_my_id,
                    $session_id,
                    false,
                    false
                );
            } else {
                // "Left green cross" extended
                $total_score = self::get_avg_student_score(
                    $user_id,
                    $course_id,
                    $a_my_id,
                    $session_id,
                    false,
                    true
                );
            }
        } else {
            // Extend all "left green cross"
            $total_score = self::get_avg_student_score(
                $user_id,
                $course_id,
                [$lp_id],
                $session_id,
                false,
                false
            );
        }

        $total_time = learnpathItem::getScormTimeFromParameter('js', $total_time);
        $total_time = str_replace('NaN', '00'.$h.'00\'00"', $total_time);

        if (!$is_allowed_to_edit && $result_disabled_ext_all) {
            $final_score = Display::return_icon('invisible.png', get_lang('Results hidden by the exercise setting'));
            $finalScoreToCsv = get_lang('Results hidden by the exercise setting');
        } else {
            if (is_numeric($total_score)) {
                $final_score = $total_score.'%';
            } else {
                $final_score = $total_score;
            }
            $finalScoreToCsv = $final_score;
        }
        $progress = learnpath::getProgress($lp_id, $user_id, $course_id, $session_id);

        $oddclass = 'row_even';
        if (($counter % 2) == 0) {
            $oddclass = 'row_odd';
        }

        $action = null;
        if ($type === 'classic') {
            $action = '<td></td>';
        }

        $timeTotal = '<td class="lp_time" colspan="2">'.$total_time.'</div>';
        if ($hideTime) {
            $timeTotal = '';
        }

        $output .= '<tr class="'.$oddclass.'">
                <td></td>
                <td colspan="4">
                    <i>'.get_lang('Total of completed learning objects').'</i>
                </td>
                <td colspan="2">'.$progress.'%</td>
                <td colspan="2">'.$final_score.'</td>
                '.$timeTotal.'
                '.$action.'
           </tr>';

        $output .= '
                    </tbody>
                </table>
            </div>
        ';

        if (!empty($export_csv)) {
            $temp = [
                '',
                '',
                '',
                '',
            ];
            $csv_content[] = $temp;
            $temp = [
                get_lang('Total of completed learning objects'),
                '',
                $finalScoreToCsv,
            ];

            if ($hideTime === false) {
                $temp[] = $total_time;
            }

            $csv_content[] = $temp;
            ob_end_clean();
            Export::arrayToCsv($csv_content, 'reporting_learning_path_details');
            exit;
        }

        return $output;
    }

    /**
     * @param int  $userId
     * @param bool $getCount
     *
     * @return array
     */
    public static function getStats($userId, $getCount = false)
    {
        $courses = [];
        $assignedCourses = [];
        $drhCount = 0;
        $teachersCount = 0;
        $studentsCount = 0;
        $studentBossCount = 0;
        $courseCount = 0;
        $sessionCount = 0;
        $assignedCourseCount = 0;

        if (api_is_drh() && api_drh_can_access_all_session_content()) {
            $studentList = SessionManager::getAllUsersFromCoursesFromAllSessionFromStatus(
                'drh_all',
                $userId,
                false,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                [],
                [],
                STUDENT
            );

            $students = [];
            if (is_array($studentList)) {
                foreach ($studentList as $studentData) {
                    $students[] = $studentData['user_id'];
                }
            }

            $studentBossesList = SessionManager::getAllUsersFromCoursesFromAllSessionFromStatus(
                'drh_all',
                $userId,
                $getCount,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                [],
                [],
                STUDENT_BOSS
            );

            if ($getCount) {
                $studentBossCount = $studentBossesList;
            } else {
                $studentBosses = [];
                if (is_array($studentBossesList)) {
                    foreach ($studentBossesList as $studentBossData) {
                        $studentBosses[] = $studentBossData['user_id'];
                    }
                }
            }

            $teacherList = SessionManager::getAllUsersFromCoursesFromAllSessionFromStatus(
                'drh_all',
                $userId,
                $getCount,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                [],
                [],
                COURSEMANAGER
            );

            if ($getCount) {
                $teachersCount = $teacherList;
            } else {
                $teachers = [];
                foreach ($teacherList as $teacherData) {
                    $teachers[] = $teacherData['user_id'];
                }
            }

            $humanResources = SessionManager::getAllUsersFromCoursesFromAllSessionFromStatus(
                'drh_all',
                $userId,
                $getCount,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                [],
                [],
                DRH
            );

            if ($getCount) {
                $drhCount = $humanResources;
            } else {
                $humanResourcesList = [];
                if (is_array($humanResources)) {
                    foreach ($humanResources as $item) {
                        $humanResourcesList[] = $item['user_id'];
                    }
                }
            }

            $platformCourses = SessionManager::getAllCoursesFollowedByUser(
                $userId,
                null,
                null,
                null,
                null,
                null,
                $getCount
            );

            if ($getCount) {
                $courseCount = $platformCourses;
            } else {
                foreach ($platformCourses as $course) {
                    $courses[$course['code']] = $course['code'];
                }
            }

            $sessions = SessionManager::get_sessions_followed_by_drh(
                $userId,
                null,
                null,
                false
            );
        } else {
            $studentList = UserManager::getUsersFollowedByUser(
                $userId,
                STUDENT,
                false,
                false,
                false,
                null,
                null,
                null,
                null,
                null,
                null,
                COURSEMANAGER
            );

            $students = [];
            if (is_array($studentList)) {
                foreach ($studentList as $studentData) {
                    $students[] = $studentData['user_id'];
                }
            }

            $studentBossesList = UserManager::getUsersFollowedByUser(
                $userId,
                STUDENT_BOSS,
                false,
                false,
                $getCount,
                null,
                null,
                null,
                null,
                null,
                null,
                COURSEMANAGER
            );

            if ($getCount) {
                $studentBossCount = $studentBossesList;
            } else {
                $studentBosses = [];
                if (is_array($studentBossesList)) {
                    foreach ($studentBossesList as $studentBossData) {
                        $studentBosses[] = $studentBossData['user_id'];
                    }
                }
            }

            $teacherList = UserManager::getUsersFollowedByUser(
                $userId,
                COURSEMANAGER,
                false,
                false,
                $getCount,
                null,
                null,
                null,
                null,
                null,
                null,
                COURSEMANAGER
            );

            if ($getCount) {
                $teachersCount = $teacherList;
            } else {
                $teachers = [];
                foreach ($teacherList as $teacherData) {
                    $teachers[] = $teacherData['user_id'];
                }
            }

            $humanResources = UserManager::getUsersFollowedByUser(
                $userId,
                DRH,
                false,
                false,
                $getCount,
                null,
                null,
                null,
                null,
                null,
                null,
                COURSEMANAGER
            );

            if ($getCount) {
                $drhCount = $humanResources;
            } else {
                $humanResourcesList = [];
                foreach ($humanResources as $item) {
                    $humanResourcesList[] = $item['user_id'];
                }
            }

            $platformCourses = CourseManager::getCoursesFollowedByUser(
                $userId,
                COURSEMANAGER,
                null,
                null,
                null,
                null,
                $getCount,
                null,
                null,
                true
            );

            if ($getCount) {
                $assignedCourseCount = $platformCourses;
            } else {
                foreach ($platformCourses as $course) {
                    $assignedCourses[$course['code']] = $course['code'];
                }
            }

            $platformCourses = CourseManager::getCoursesFollowedByUser(
                $userId,
                COURSEMANAGER,
                null,
                null,
                null,
                null,
                $getCount
            );

            if ($getCount) {
                $courseCount = $platformCourses;
            } else {
                foreach ($platformCourses as $course) {
                    $courses[$course['code']] = $course['code'];
                }
            }

            $sessions = SessionManager::getSessionsFollowedByUser(
                $userId,
                COURSEMANAGER,
                null,
                null,
                false
            );
        }

        if ($getCount) {
            return [
                'drh' => $drhCount,
                'teachers' => $teachersCount,
                'student_count' => count($students),
                'student_list' => $students,
                'student_bosses' => $studentBossCount,
                'courses' => $courseCount,
                'session_count' => count($sessions),
                'session_list' => $sessions,
                'assigned_courses' => $assignedCourseCount,
            ];
        }

        return [
            'drh' => $humanResourcesList,
            'teachers' => $teachers,
            'student_list' => $students,
            'student_bosses' => $studentBosses,
            'courses' => $courses,
            'sessions' => $sessions,
            'assigned_courses' => $assignedCourses,
        ];
    }

    /**
     * Calculates the time spent on the platform by a user.
     *
     * @param int|array $userId
     * @param string    $timeFilter type of time filter: 'last_week' or 'custom'
     * @param string    $start_date start date date('Y-m-d H:i:s')
     * @param string    $end_date   end date date('Y-m-d H:i:s')
     *
     * @return int
     */
    public static function get_time_spent_on_the_platform(
        $userId,
        $timeFilter = 'last_7_days',
        $start_date = null,
        $end_date = null
    ) {
        $tbl_track_login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $condition_time = '';

        if (is_array($userId)) {
            $userList = array_map('intval', $userId);
            $userCondition = " login_user_id IN ('".implode("','", $userList)."')";
        } else {
            $userCondition = " login_user_id = ".intval($userId);
        }

        if (empty($timeFilter)) {
            $timeFilter = 'last_week';
        }

        $today = new DateTime('now', new DateTimeZone('UTC'));

        switch ($timeFilter) {
            case 'last_7_days':
                $newDate = new DateTime('-7 day', new DateTimeZone('UTC'));
                $condition_time = " AND (login_date >= '{$newDate->format('Y-m-d H:i:s')}'";
                $condition_time .= " AND logout_date <= '{$today->format('Y-m-d H:i:s')}') ";
                break;
            case 'last_30_days':
                $newDate = new DateTime('-30 days', new DateTimeZone('UTC'));
                $condition_time = " AND (login_date >= '{$newDate->format('Y-m-d H:i:s')}'";
                $condition_time .= "AND logout_date <= '{$today->format('Y-m-d H:i:s')}') ";
                break;
            case 'custom':
                if (!empty($start_date) && !empty($end_date)) {
                    $start_date = Database::escape_string($start_date);
                    $end_date = Database::escape_string($end_date);
                    $condition_time = ' AND (login_date >= "'.$start_date.'" AND logout_date <= "'.$end_date.'" ) ';
                }
                break;
        }

        $sql = 'SELECT SUM(TIMESTAMPDIFF(SECOND, login_date, logout_date)) diff
    	        FROM '.$tbl_track_login.'
                WHERE '.$userCondition.$condition_time;
        $rs = Database::query($sql);
        $row = Database::fetch_array($rs, 'ASSOC');
        $diff = $row['diff'];

        if ($diff >= 0) {
            return $diff;
        }

        return -1;
    }

    /**
     * @param string $startDate
     * @param string $endDate
     *
     * @return int
     */
    public static function getTotalTimeSpentOnThePlatform(
        $startDate = '',
        $endDate = ''
    ) {
        $tbl_track_login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

        $url_table = null;
        $url_condition = null;
        if (api_is_multiple_url_enabled()) {
            $access_url_id = api_get_current_access_url_id();
            $url_table = ", ".$tbl_url_rel_user." as url_users";
            $url_condition = " AND u.login_user_id = url_users.user_id AND access_url_id='$access_url_id'";
        }

        if (!empty($startDate) && !empty($endDate)) {
            $startDate = Database::escape_string($startDate);
            $endDate = Database::escape_string($endDate);
            $condition_time = ' (login_date >= "'.$startDate.'" AND logout_date <= "'.$endDate.'" ) ';
        }

        $sql = "SELECT SUM(TIMESTAMPDIFF(SECOND, login_date, logout_date)) diff
    	        FROM $tbl_track_login u $url_table
                WHERE $condition_time $url_condition";
        $rs = Database::query($sql);
        $row = Database::fetch_array($rs, 'ASSOC');
        $diff = $row['diff'];

        if ($diff >= 0) {
            return $diff;
        }

        return -1;
    }

    /**
     * Checks if the "lp_minimum_time" feature is available for the course.
     *
     * @param int $sessionId
     * @param int $courseId
     *
     * @return bool
     */
    public static function minimumTimeAvailable($sessionId, $courseId)
    {
        if (!api_get_configuration_value('lp_minimum_time')) {
            return false;
        }

        if (!empty($sessionId)) {
            $extraFieldValue = new ExtraFieldValue('session');
            $value = $extraFieldValue->get_values_by_handler_and_field_variable($sessionId, 'new_tracking_system');

            if ($value && isset($value['value']) && $value['value'] == 1) {
                return true;
            }
        } else {
            if ($courseId) {
                $extraFieldValue = new ExtraFieldValue('course');
                $value = $extraFieldValue->get_values_by_handler_and_field_variable($courseId, 'new_tracking_system');
                if ($value && isset($value['value']) && $value['value'] == 1) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Calculates the time spent on the course.
     *
     * @param int $user_id
     * @param int $courseId
     * @param int Session id (optional)
     *
     * @return int Time in seconds
     */
    public static function get_time_spent_on_the_course(
        $user_id,
        $courseId,
        $session_id = 0
    ) {
        $courseId = (int) $courseId;

        if (empty($courseId) || empty($user_id)) {
            return 0;
        }

        if (self::minimumTimeAvailable($session_id, $courseId)) {
            $courseTime = self::getCalculateTime($user_id, $courseId, $session_id);
            $time = isset($courseTime['total_time']) ? $courseTime['total_time'] : 0;

            return $time;
        }

        $session_id = (int) $session_id;
        if (is_array($user_id)) {
            $user_id = array_map('intval', $user_id);
            $conditionUser = " AND user_id IN (".implode(',', $user_id).") ";
        } else {
            $user_id = (int) $user_id;
            $conditionUser = " AND user_id = $user_id ";
        }

        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $sql = "SELECT
                SUM(UNIX_TIMESTAMP(logout_course_date) - UNIX_TIMESTAMP(login_course_date)) as nb_seconds
                FROM $table
                WHERE 
                    UNIX_TIMESTAMP(logout_course_date) > UNIX_TIMESTAMP(login_course_date) AND 
                    c_id = '$courseId' ";

        if ($session_id != -1) {
            $sql .= "AND session_id = '$session_id' ";
        }

        $sql .= $conditionUser;
        $rs = Database::query($sql);
        $row = Database::fetch_array($rs);

        return $row['nb_seconds'];
    }

    /**
     * Get first connection date for a student.
     *
     * @param int $student_id
     *
     * @return string|bool Date format long without day or false if there are no connections
     */
    public static function get_first_connection_date($student_id)
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $sql = 'SELECT login_date
                FROM '.$table.'
                WHERE login_user_id = '.intval($student_id).'
                ORDER BY login_date ASC
                LIMIT 0,1';

        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            if ($first_login_date = Database::result($rs, 0, 0)) {
                return api_convert_and_format_date(
                    $first_login_date,
                    DATE_FORMAT_SHORT
                );
            }
        }

        return false;
    }

    /**
     * Get las connection date for a student.
     *
     * @param int  $student_id
     * @param bool $warning_message  Show a warning message (optional)
     * @param bool $return_timestamp True for returning results in timestamp (optional)
     *
     * @return string|int|bool Date format long without day, false if there are no connections or
     *                         timestamp if parameter $return_timestamp is true
     */
    public static function get_last_connection_date(
        $student_id,
        $warning_message = false,
        $return_timestamp = false
    ) {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $sql = 'SELECT login_date
                FROM '.$table.'
                WHERE login_user_id = '.intval($student_id).'
                ORDER BY login_date
                DESC LIMIT 0,1';

        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            if ($last_login_date = Database::result($rs, 0, 0)) {
                $last_login_date = api_get_local_time($last_login_date);
                if ($return_timestamp) {
                    return api_strtotime($last_login_date, 'UTC');
                } else {
                    if (!$warning_message) {
                        return api_format_date($last_login_date, DATE_FORMAT_SHORT);
                    } else {
                        $timestamp = api_strtotime($last_login_date, 'UTC');
                        $currentTimestamp = time();

                        //If the last connection is > than 7 days, the text is red
                        //345600 = 7 days in seconds
                        if ($currentTimestamp - $timestamp > 604800) {
                            return '<span style="color: #F00;">'.api_format_date($last_login_date, DATE_FORMAT_SHORT).'</span>';
                        } else {
                            return api_format_date($last_login_date, DATE_FORMAT_SHORT);
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get first user's connection date on the course.
     *
     * @param int User id
     * @param int $courseId
     * @param int Session id (optional, default=0)
     * @param bool $convert_date
     *
     * @return string|bool Date with format long without day or false if there is no date
     */
    public static function get_first_connection_date_on_the_course(
        $student_id,
        $courseId,
        $session_id = 0,
        $convert_date = true
    ) {
        $student_id = (int) $student_id;
        $courseId = (int) $courseId;
        $session_id = (int) $session_id;

        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $sql = 'SELECT login_course_date
                FROM '.$table.'
                WHERE
                    user_id = '.$student_id.' AND
                    c_id = '.$courseId.' AND
                    session_id = '.$session_id.'
                ORDER BY login_course_date ASC 
                LIMIT 0,1';
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            if ($first_login_date = Database::result($rs, 0, 0)) {
                if (empty($first_login_date)) {
                    return false;
                }

                if ($convert_date) {
                    return api_convert_and_format_date(
                        $first_login_date,
                        DATE_FORMAT_SHORT
                    );
                }

                return $first_login_date;
            }
        }

        return false;
    }

    /**
     * Get last user's connection date on the course.
     *
     * @param     int         User id
     * @param array $courseInfo real_id and code are used
     * @param    int            Session id (optional, default=0)
     * @param bool $convert_date
     *
     * @return string|bool Date with format long without day or false if there is no date
     */
    public static function get_last_connection_date_on_the_course(
        $student_id,
        $courseInfo,
        $session_id = 0,
        $convert_date = true
    ) {
        // protect data
        $student_id = (int) $student_id;
        $session_id = (int) $session_id;

        if (empty($courseInfo) || empty($student_id)) {
            return false;
        }

        $courseId = $courseInfo['real_id'];

        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

        if (self::minimumTimeAvailable($session_id, $courseId)) {
            // Show the last date on which the user acceed the session when it was active
            $where_condition = '';
            $userInfo = api_get_user_info($student_id);
            if ($userInfo['status'] == STUDENT && !empty($session_id)) {
                // fin de acceso a la sesión
                $sessionInfo = SessionManager::fetch($session_id);
                $last_access = $sessionInfo['access_end_date'];
                if (!empty($last_access)) {
                    $where_condition = ' AND logout_course_date < "'.$last_access.'" ';
                }
            }
            $sql = "SELECT logout_course_date
                    FROM $table
                    WHERE   user_id = $student_id AND
                            c_id = $courseId AND
                            session_id = $session_id $where_condition
                    ORDER BY logout_course_date DESC
                    LIMIT 0,1";

            $rs = Database::query($sql);
            if (Database::num_rows($rs) > 0) {
                if ($last_login_date = Database::result($rs, 0, 0)) {
                    if (empty($last_login_date)) {
                        return false;
                    }
                    if ($convert_date) {
                        return api_convert_and_format_date($last_login_date, DATE_FORMAT_SHORT);
                    }

                    return $last_login_date;
                }
            }
        } else {
            $sql = "SELECT logout_course_date
                    FROM $table
                    WHERE   user_id = $student_id AND
                            c_id = $courseId AND
                            session_id = $session_id
                    ORDER BY logout_course_date DESC
                    LIMIT 0,1";

            $rs = Database::query($sql);
            if (Database::num_rows($rs) > 0) {
                if ($last_login_date = Database::result($rs, 0, 0)) {
                    if (empty($last_login_date)) {
                        return false;
                    }
                    //see #5736
                    $last_login_date_timestamp = api_strtotime($last_login_date);
                    $now = time();
                    //If the last connection is > than 7 days, the text is red
                    //345600 = 7 days in seconds
                    if ($now - $last_login_date_timestamp > 604800) {
                        if ($convert_date) {
                            $last_login_date = api_convert_and_format_date($last_login_date, DATE_FORMAT_SHORT);
                            $icon = null;
                            if (api_is_allowed_to_edit()) {
                                $url = api_get_path(WEB_CODE_PATH).
                                    'announcements/announcements.php?action=add&remind_inactive='.$student_id.'&cidReq='.$courseInfo['code'];
                                $icon = '<a href="'.$url.'" title="'.get_lang('Remind inactive user').'">
                                  '.Display::return_icon('messagebox_warning.gif').'
                                 </a>';
                            }

                            return $icon.Display::label($last_login_date, 'warning');
                        }

                        return $last_login_date;
                    } else {
                        if ($convert_date) {
                            return api_convert_and_format_date($last_login_date, DATE_FORMAT_SHORT);
                        }

                        return $last_login_date;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get count of the connections to the course during a specified period.
     *
     * @param int $courseId
     * @param   int     Session id (optional)
     * @param   int     Datetime from which to collect data (defaults to 0)
     * @param   int     Datetime to which to collect data (defaults to now)
     *
     * @return int count connections
     */
    public static function get_course_connections_count(
        $courseId,
        $session_id = 0,
        $start = 0,
        $stop = null
    ) {
        if ($start < 0) {
            $start = 0;
        }
        if (!isset($stop) || $stop < 0) {
            $stop = api_get_utc_datetime();
        }

        // Given we're storing in cache, round the start and end times
        // to the lower minute
        $roundedStart = substr($start, 0, -2).'00';
        $roundedStop = substr($stop, 0, -2).'00';
        $roundedStart = Database::escape_string($roundedStart);
        $roundedStop = Database::escape_string($roundedStop);
        $month_filter = " AND login_course_date > '$roundedStart' AND login_course_date < '$roundedStop' ";
        $courseId = (int) $courseId;
        $session_id = (int) $session_id;
        $count = 0;
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $sql = "SELECT count(*) as count_connections
                FROM $table
                WHERE
                    c_id = $courseId AND
                    session_id = $session_id
                    $month_filter";

        //This query can be very slow (several seconds on an indexed table
        // with 14M rows). As such, we'll try to use APCu if it is
        // available to store the resulting value for a few seconds
        $cacheAvailable = api_get_configuration_value('apc');
        if ($cacheAvailable === true) {
            $apc = apcu_cache_info(true);
            $apc_end = $apc['start_time'] + $apc['ttl'];
            $apc_var = api_get_configuration_value('apc_prefix').'course_access_'.$courseId.'_'.$session_id.'_'.strtotime($roundedStart).'_'.strtotime($roundedStop);
            if (apcu_exists($apc_var) && (time() < $apc_end) &&
                apcu_fetch($apc_var) > 0
            ) {
                $count = apcu_fetch($apc_var);
            } else {
                $rs = Database::query($sql);
                if (Database::num_rows($rs) > 0) {
                    $row = Database::fetch_object($rs);
                    $count = $row->count_connections;
                }
                apcu_clear_cache();
                apcu_store($apc_var, $count, 60);
            }
        } else {
            $rs = Database::query($sql);
            if (Database::num_rows($rs) > 0) {
                $row = Database::fetch_object($rs);
                $count = $row->count_connections;
            }
        }

        return $count;
    }

    /**
     * Get count courses per student.
     *
     * @param int  $user_id          Student id
     * @param bool $include_sessions Include sessions (optional)
     *
     * @return int count courses
     */
    public static function count_course_per_student($user_id, $include_sessions = true)
    {
        $user_id = (int) $user_id;
        $tbl_course_rel_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_session_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        $sql = 'SELECT DISTINCT c_id
                FROM '.$tbl_course_rel_user.'
                WHERE user_id = '.$user_id.' AND relation_type<>'.COURSE_RELATION_TYPE_RRHH;
        $rs = Database::query($sql);
        $nb_courses = Database::num_rows($rs);

        if ($include_sessions) {
            $sql = 'SELECT DISTINCT c_id
                    FROM '.$tbl_session_course_rel_user.'
                    WHERE user_id = '.$user_id;
            $rs = Database::query($sql);
            $nb_courses += Database::num_rows($rs);
        }

        return $nb_courses;
    }

    /**
     * Gets the score average from all tests in a course by student.
     *
     * @param $student_id
     * @param $course_code
     * @param int  $exercise_id
     * @param null $session_id
     * @param int  $active_filter 2 for consider all tests
     *                            1 for active <> -1
     *                            0 for active <> 0
     * @param int  $into_lp       1 for all exercises
     *                            0 for without LP
     * @param mixed id
     * @param string code
     * @param int id (optional), filtered by exercise
     * @param int id (optional), if param $session_id is null
     *                                                it'll return results including sessions, 0 = session is not filtered
     *
     * @return string value (number %) Which represents a round integer about the score average
     */
    public static function get_avg_student_exercise_score(
        $student_id,
        $course_code,
        $exercise_id = 0,
        $session_id = null,
        $active_filter = 1,
        $into_lp = 0
    ) {
        $course_code = Database::escape_string($course_code);
        $course_info = api_get_course_info($course_code);
        if (!empty($course_info)) {
            // table definition
            $tbl_course_quiz = Database::get_course_table(TABLE_QUIZ_TEST);
            $tbl_stats_exercise = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

            // Compose a filter based on optional exercise given
            $condition_quiz = "";
            if (!empty($exercise_id)) {
                $exercise_id = intval($exercise_id);
                $condition_quiz = " AND id = $exercise_id ";
            }

            // Compose a filter based on optional session id given
            $condition_session = '';
            if (isset($session_id)) {
                $session_id = intval($session_id);
                $condition_session = " AND session_id = $session_id ";
            }
            if ($active_filter == 1) {
                $condition_active = 'AND active <> -1';
            } elseif ($active_filter == 0) {
                $condition_active = 'AND active <> 0';
            } else {
                $condition_active = '';
            }
            $condition_into_lp = '';
            $select_lp_id = '';
            if ($into_lp == 0) {
                $condition_into_lp = 'AND orig_lp_id = 0 AND orig_lp_item_id = 0';
            } else {
                $select_lp_id = ', orig_lp_id as lp_id ';
            }

            $sql = "SELECT count(id) 
    		        FROM $tbl_course_quiz
    				WHERE c_id = {$course_info['real_id']} $condition_active $condition_quiz ";
            $count_quiz = 0;
            $countQuizResult = Database::query($sql);
            if (!empty($countQuizResult)) {
                $count_quiz = Database::fetch_row($countQuizResult);
            }

            if (!empty($count_quiz[0]) && !empty($student_id)) {
                if (is_array($student_id)) {
                    $student_id = array_map('intval', $student_id);
                    $condition_user = " AND exe_user_id IN (".implode(',', $student_id).") ";
                } else {
                    $student_id = intval($student_id);
                    $condition_user = " AND exe_user_id = '$student_id' ";
                }

                if (empty($exercise_id)) {
                    $sql = "SELECT id FROM $tbl_course_quiz
                            WHERE c_id = {$course_info['real_id']} $condition_active $condition_quiz";
                    $result = Database::query($sql);
                    $exercise_list = [];
                    $exercise_id = null;
                    if (!empty($result) && Database::num_rows($result)) {
                        while ($row = Database::fetch_array($result)) {
                            $exercise_list[] = $row['id'];
                        }
                    }
                    if (!empty($exercise_list)) {
                        $exercise_id = implode("','", $exercise_list);
                    }
                }

                $count_quiz = Database::fetch_row(Database::query($sql));
                $sql = "SELECT
                        SUM(score/max_score*100) as avg_score,
                        COUNT(*) as num_attempts
                        $select_lp_id
                        FROM $tbl_stats_exercise
                        WHERE
                            exe_exo_id IN ('".$exercise_id."')
                            $condition_user AND
                            status = '' AND
                            c_id = {$course_info['real_id']}
                            $condition_session
                            $condition_into_lp
                        ORDER BY exe_date DESC";

                $res = Database::query($sql);
                $row = Database::fetch_array($res);
                $quiz_avg_score = null;

                if (!empty($row['avg_score'])) {
                    $quiz_avg_score = round($row['avg_score'], 2);
                }

                if (!empty($row['num_attempts'])) {
                    $quiz_avg_score = round($quiz_avg_score / $row['num_attempts'], 2);
                }
                if (is_array($student_id)) {
                    $quiz_avg_score = round($quiz_avg_score / count($student_id), 2);
                }
                if ($into_lp == 0) {
                    return $quiz_avg_score;
                } else {
                    if (!empty($row['lp_id'])) {
                        $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
                        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
                        $sql = "SELECT lp.name
                                FROM $tbl_lp as lp, $tbl_course as c
                                WHERE
                                    c.code = '$course_code' AND
                                    lp.id = ".$row['lp_id']." AND
                                    lp.c_id = c.id
                                LIMIT 1;
                        ";
                        $result = Database::query($sql);
                        $row_lp = Database::fetch_row($result);
                        $lp_name = $row_lp[0];

                        return [$quiz_avg_score, $lp_name];
                    } else {
                        return [$quiz_avg_score, null];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Get count student's exercise COMPLETED attempts.
     *
     * @param int $student_id
     * @param int $courseId
     * @param int $exercise_id
     * @param int $lp_id
     * @param int $lp_item_id
     * @param int $session_id
     * @param int $find_all_lp 0 = just LP specified
     *                         1 = LP specified or whitout LP,
     *                         2 = all rows
     *
     * @internal param \Student $int id
     * @internal param \Course $string code
     * @internal param \Exercise $int id
     * @internal param \Learning $int path id (optional),
     * for showing attempts inside a learning path $lp_id and $lp_item_id params are required
     * @internal param \Learning $int path item id (optional),
     * for showing attempts inside a learning path $lp_id and $lp_item_id params are required
     *
     * @return int count of attempts
     */
    public static function count_student_exercise_attempts(
        $student_id,
        $courseId,
        $exercise_id,
        $lp_id = 0,
        $lp_item_id = 0,
        $session_id = 0,
        $find_all_lp = 0
    ) {
        $courseId = intval($courseId);
        $student_id = intval($student_id);
        $exercise_id = intval($exercise_id);
        $session_id = intval($session_id);

        $lp_id = intval($lp_id);
        $lp_item_id = intval($lp_item_id);
        $tbl_stats_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

        $sql = "SELECT COUNT(ex.exe_id) as essais 
                FROM $tbl_stats_exercises AS ex
                WHERE  
                    ex.c_id = $courseId AND 
                    ex.exe_exo_id = $exercise_id AND 
                    status = '' AND 
                    exe_user_id= $student_id AND 
                    session_id = $session_id ";

        if ($find_all_lp == 1) {
            $sql .= "AND (orig_lp_id = $lp_id OR orig_lp_id = 0)
                AND (orig_lp_item_id = $lp_item_id OR orig_lp_item_id = 0)";
        } elseif ($find_all_lp == 0) {
            $sql .= "AND orig_lp_id = $lp_id
                AND orig_lp_item_id = $lp_item_id";
        }

        $rs = Database::query($sql);
        $row = Database::fetch_row($rs);
        $count_attempts = $row[0];

        return $count_attempts;
    }

    /**
     * Get count student's exercise progress.
     *
     * @param array $exercise_list
     * @param int   $user_id
     * @param int   $courseId
     * @param int   $session_id
     *
     * @return string
     */
    public static function get_exercise_student_progress(
        $exercise_list,
        $user_id,
        $courseId,
        $session_id
    ) {
        $courseId = (int) $courseId;
        $user_id = (int) $user_id;
        $session_id = (int) $session_id;

        if (empty($exercise_list)) {
            return '0%';
        }
        $tbl_stats_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $exercise_list = array_keys($exercise_list);
        $exercise_list = array_map('intval', $exercise_list);

        $exercise_list_imploded = implode("' ,'", $exercise_list);

        $sql = "SELECT COUNT(DISTINCT ex.exe_exo_id)
                FROM $tbl_stats_exercises AS ex
                WHERE
                    ex.c_id = $courseId AND
                    ex.session_id  = $session_id AND
                    ex.exe_user_id = $user_id AND
                    ex.exe_exo_id IN ('$exercise_list_imploded') ";

        $rs = Database::query($sql);
        $count = 0;
        if ($rs) {
            $row = Database::fetch_row($rs);
            $count = $row[0];
        }
        $count = ($count != 0) ? 100 * round(intval($count) / count($exercise_list), 2).'%' : '0%';

        return $count;
    }

    /**
     * @param array $exercise_list
     * @param int   $user_id
     * @param int   $courseId
     * @param int   $session_id
     *
     * @return string
     */
    public static function get_exercise_student_average_best_attempt(
        $exercise_list,
        $user_id,
        $courseId,
        $session_id
    ) {
        $result = 0;
        if (!empty($exercise_list)) {
            foreach ($exercise_list as $exercise_data) {
                $exercise_id = $exercise_data['id'];
                $best_attempt = Event::get_best_attempt_exercise_results_per_user(
                    $user_id,
                    $exercise_id,
                    $courseId,
                    $session_id
                );

                if (!empty($best_attempt) && !empty($best_attempt['max_score'])) {
                    $result += $best_attempt['score'] / $best_attempt['max_score'];
                }
            }
            $result = $result / count($exercise_list);
            $result = round($result, 2) * 100;
        }

        return $result.'%';
    }

    /**
     * Returns the average student progress in the learning paths of the given
     * course, it will take into account the progress that were not started.
     *
     * @param int|array $studentId
     * @param string    $courseCode
     * @param array     $lpIdList        Limit average to listed lp ids
     * @param int       $sessionId       Session id (optional),
     *                                   if parameter $session_id is null(default) it'll return results including
     *                                   sessions, 0 = session is not filtered
     * @param bool      $returnArray     Will return an array of the type:
     *                                   [sum_of_progresses, number] if it is set to true
     * @param bool      $onlySeriousGame Optional. Limit average to lp on seriousgame mode
     *
     * @return float Average progress of the user in this course
     */
    public static function get_avg_student_progress(
        $studentId,
        $courseCode = null,
        $lpIdList = [],
        $sessionId = null,
        $returnArray = false,
        $onlySeriousGame = false
    ) {
        // If there is at least one learning path and one student.
        if (empty($studentId)) {
            return false;
        }

        $sessionId = (int) $sessionId;
        $courseInfo = api_get_course_info($courseCode);

        if (empty($courseInfo)) {
            return false;
        }

        $lPTable = Database::get_course_table(TABLE_LP_MAIN);
        $lpViewTable = Database::get_course_table(TABLE_LP_VIEW);
        $lpConditions = [];
        $lpConditions['c_id = ? '] = $courseInfo['real_id'];

        if ($sessionId > 0) {
            $lpConditions['AND (session_id = ? OR session_id = 0 OR session_id IS NULL)'] = $sessionId;
        } else {
            $lpConditions['AND session_id = ?'] = $sessionId;
        }

        if (is_array($lpIdList) && count($lpIdList) > 0) {
            $placeHolders = [];
            for ($i = 0; $i < count($lpIdList); $i++) {
                $placeHolders[] = '?';
            }
            $lpConditions['AND id IN('.implode(', ', $placeHolders).') '] = $lpIdList;
        }

        if ($onlySeriousGame) {
            $lpConditions['AND seriousgame_mode = ? '] = true;
        }

        $resultLP = Database::select(
            'id',
            $lPTable,
            ['where' => $lpConditions]
        );
        $filteredLP = array_keys($resultLP);

        if (empty($filteredLP)) {
            return false;
        }

        $conditions = [
            " c_id = {$courseInfo['real_id']} ",
            " lp_view.lp_id IN (".implode(', ', $filteredLP).") ",
        ];

        $groupBy = 'GROUP BY lp_id';

        if (is_array($studentId)) {
            $studentId = array_map('intval', $studentId);
            $conditions[] = " lp_view.user_id IN (".implode(',', $studentId).")  ";
        } else {
            $studentId = (int) $studentId;
            $conditions[] = " lp_view.user_id = '$studentId' ";

            if (empty($lpIdList)) {
                $lpList = new LearnpathList(
                    $studentId,
                    $courseInfo,
                    $sessionId,
                    null,
                    false,
                    null,
                    true
                );
                $lpList = $lpList->get_flat_list();
                if (!empty($lpList)) {
                    /** @var $lp */
                    foreach ($lpList as $lpId => $lp) {
                        $lpIdList[] = $lp['lp_old_id'];
                    }
                }
            }
        }

        if (!empty($sessionId)) {
            $conditions[] = " session_id = $sessionId ";
        } else {
            $conditions[] = ' (session_id = 0 OR session_id IS NULL) ';
        }

        $conditionToString = implode('AND', $conditions);
        $sql = "SELECT lp_id, view_count, progress 
                FROM $lpViewTable lp_view
                WHERE
                    $conditionToString
                    $groupBy
                ORDER BY view_count DESC";

        $result = Database::query($sql);

        $progress = [];
        $viewCount = [];
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            if (!isset($viewCount[$row['lp_id']])) {
                $progress[$row['lp_id']] = $row['progress'];
            }
            $viewCount[$row['lp_id']] = $row['view_count'];
        }

        // Fill with lp ids
        $newProgress = [];
        if (!empty($lpIdList)) {
            foreach ($lpIdList as $lpId) {
                if (isset($progress[$lpId])) {
                    $newProgress[] = $progress[$lpId];
                }
            }
            $total = count($lpIdList);
        } else {
            $newProgress = $progress;
            $total = count($newProgress);
        }

        $average = 0;
        $sum = 0;
        if (!empty($newProgress)) {
            $sum = array_sum($newProgress);
            $average = $sum / $total;
        }

        if ($returnArray) {
            return [
                $sum,
                $total,
            ];
        }

        return round($average, 1);
    }

    /**
     * This function gets:
     * 1. The score average from all SCORM Test items in all LP in a course-> All the answers / All the max scores.
     * 2. The score average from all Tests (quiz) in all LP in a course-> All the answers / All the max scores.
     * 3. And finally it will return the average between 1. and 2.
     *
     * @todo improve performance, when loading 1500 users with 20 lps the script dies
     * This function does not take the results of a Test out of a LP
     *
     * @param mixed  $student_id                      Array of user ids or an user id
     * @param string $course_code
     * @param array  $lp_ids                          List of LP ids
     * @param int    $session_id                      Session id (optional),
     *                                                if param $session_id is null(default) it'll return results
     *                                                including sessions, 0 = session is not filtered
     * @param bool   $return_array                    Returns an array of the
     *                                                type [sum_score, num_score] if set to true
     * @param bool   $get_only_latest_attempt_results get only the latest attempts or ALL attempts
     * @param bool   $getOnlyBestAttempt
     *
     * @return string value (number %) Which represents a round integer explain in got in 3
     */
    public static function get_avg_student_score(
        $student_id,
        $course_code,
        $lp_ids = [],
        $session_id = null,
        $return_array = false,
        $get_only_latest_attempt_results = false,
        $getOnlyBestAttempt = false
    ) {
        $debug = false;
        if ($debug) {
            echo '<h1>Tracking::get_avg_student_score</h1>';
        }
        $tbl_stats_exercices = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $tbl_stats_attempts = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $course = api_get_course_info($course_code);

        if (empty($course)) {
            return null;
        }

        // Get course tables names
        $tbl_quiz_questions = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $lp_table = Database::get_course_table(TABLE_LP_MAIN);
        $lp_item_table = Database::get_course_table(TABLE_LP_ITEM);
        $lp_view_table = Database::get_course_table(TABLE_LP_VIEW);
        $lp_item_view_table = Database::get_course_table(TABLE_LP_ITEM_VIEW);
        $course_id = $course['real_id'];

        // Compose a filter based on optional learning paths list given
        $condition_lp = '';
        if (count($lp_ids) > 0) {
            $condition_lp = " AND id IN(".implode(',', $lp_ids).") ";
        }

        // Compose a filter based on optional session id
        $session_id = intval($session_id);
        if (count($lp_ids) > 0) {
            $condition_session = " AND session_id = $session_id ";
        } else {
            $condition_session = " WHERE session_id = $session_id ";
        }

        // Check the real number of LPs corresponding to the filter in the
        // database (and if no list was given, get them all)
        if (empty($session_id)) {
            $sql = "SELECT DISTINCT(id), use_max_score
                    FROM $lp_table
                    WHERE 
                        c_id = $course_id AND 
                        (session_id = 0 OR session_id IS NULL) $condition_lp ";
        } else {
            $sql = "SELECT DISTINCT(id), use_max_score
                    FROM $lp_table
                    WHERE c_id = $course_id $condition_lp ";
        }

        $res_row_lp = Database::query($sql);
        $count_row_lp = Database::num_rows($res_row_lp);

        $lp_list = $use_max_score = [];
        while ($row_lp = Database::fetch_array($res_row_lp)) {
            $lp_list[] = $row_lp['id'];
            $use_max_score[$row_lp['id']] = $row_lp['use_max_score'];
        }

        // prepare filter on users
        if (is_array($student_id)) {
            array_walk($student_id, 'intval');
            $condition_user1 = " AND user_id IN (".implode(',', $student_id).") ";
        } else {
            $condition_user1 = " AND user_id = $student_id ";
        }

        if (empty($count_row_lp) || empty($student_id)) {
            return null;
        }

        // Getting latest LP result for a student
        //@todo problem when a  course have more than 1500 users
        $sql = "SELECT MAX(view_count) as vc, id, progress, lp_id, user_id
                FROM $lp_view_table
                WHERE
                    c_id = $course_id AND
                    lp_id IN (".implode(',', $lp_list).")
                    $condition_user1 AND
                    session_id = $session_id
                GROUP BY lp_id, user_id";

        $rs_last_lp_view_id = Database::query($sql);
        $global_result = 0;

        if (Database::num_rows($rs_last_lp_view_id) > 0) {
            // Cycle through each line of the results (grouped by lp_id, user_id)
            while ($row_lp_view = Database::fetch_array($rs_last_lp_view_id)) {
                $count_items = 0;
                $lpPartialTotal = 0;
                $list = [];
                $lp_view_id = $row_lp_view['id'];
                $lp_id = $row_lp_view['lp_id'];
                $user_id = $row_lp_view['user_id'];

                if ($debug) {
                    echo '<h2>LP id '.$lp_id.'</h2>';
                    echo "get_only_latest_attempt_results: $get_only_latest_attempt_results <br />";
                    echo "getOnlyBestAttempt: $getOnlyBestAttempt <br />";
                }

                if ($get_only_latest_attempt_results || $getOnlyBestAttempt) {
                    // Getting lp_items done by the user
                    $sql = "SELECT DISTINCT lp_item_id
                                    FROM $lp_item_view_table
                                    WHERE
                                        c_id = $course_id AND
                                        lp_view_id = $lp_view_id
                                    ORDER BY lp_item_id";
                    $res_lp_item = Database::query($sql);

                    while ($row_lp_item = Database::fetch_array($res_lp_item, 'ASSOC')) {
                        $my_lp_item_id = $row_lp_item['lp_item_id'];
                        $order = ' view_count DESC';
                        if ($getOnlyBestAttempt) {
                            $order = ' lp_iv.score DESC';
                        }

                        // Getting the most recent attempt
                        $sql = "SELECT  
                                    lp_iv.id as lp_item_view_id,
                                    lp_iv.score as score,
                                    lp_i.max_score,
                                    lp_iv.max_score as max_score_item_view,
                                    lp_i.path,
                                    lp_i.item_type,
                                    lp_i.id as iid
                                FROM $lp_item_view_table as lp_iv
                                INNER JOIN $lp_item_table as lp_i
                                ON (
                                    lp_i.id = lp_iv.lp_item_id AND 
                                    lp_iv.c_id = lp_i.c_id
                                )                                            
                                WHERE
                                    lp_iv.c_id = $course_id AND
                                    lp_i.c_id  = $course_id AND
                                    lp_item_id = $my_lp_item_id AND
                                    lp_view_id = $lp_view_id AND
                                    (lp_i.item_type='sco' OR lp_i.item_type='".TOOL_QUIZ."')
                                ORDER BY $order
                                LIMIT 1";

                        $res_lp_item_result = Database::query($sql);
                        while ($row_max_score = Database::fetch_array($res_lp_item_result, 'ASSOC')) {
                            $list[] = $row_max_score;
                        }
                    }
                } else {
                    // For the currently analysed view, get the score and
                    // max_score of each item if it is a sco or a TOOL_QUIZ
                    $sql = "SELECT
                                lp_iv.id as lp_item_view_id,
                                lp_iv.score as score,
                                lp_i.max_score,
                                lp_iv.max_score as max_score_item_view,
                                lp_i.path,
                                lp_i.item_type,
                                lp_i.id as iid
                              FROM $lp_item_view_table as lp_iv
                              INNER JOIN $lp_item_table as lp_i
                              ON lp_i.id = lp_iv.lp_item_id AND
                                 lp_iv.c_id = lp_i.c_id
                              WHERE 
                                lp_iv.c_id = $course_id AND 
                                lp_i.c_id  = $course_id AND
                                lp_view_id = $lp_view_id AND
                                (lp_i.item_type='sco' OR lp_i.item_type='".TOOL_QUIZ."')
                            ";
                    $res_max_score = Database::query($sql);
                    while ($row_max_score = Database::fetch_array($res_max_score, 'ASSOC')) {
                        $list[] = $row_max_score;
                    }
                }

                // Go through each scorable element of this view
                $score_of_scorm_calculate = 0;
                foreach ($list as $row_max_score) {
                    // Came from the original lp_item
                    $max_score = $row_max_score['max_score'];
                    // Came from the lp_item_view
                    $max_score_item_view = $row_max_score['max_score_item_view'];
                    $score = $row_max_score['score'];
                    if ($debug) {
                        echo '<h3>Item Type: '.$row_max_score['item_type'].'</h3>';
                    }

                    if ($row_max_score['item_type'] == 'sco') {
                        /* Check if it is sco (easier to get max_score)
                           when there's no max score, we assume 100 as the max score,
                           as the SCORM 1.2 says that the value should always be between 0 and 100.
                        */
                        if ($max_score == 0 || is_null($max_score) || $max_score == '') {
                            // Chamilo style
                            if ($use_max_score[$lp_id]) {
                                $max_score = 100;
                            } else {
                                // Overwrites max score = 100 to use the one that came in the lp_item_view see BT#1613
                                $max_score = $max_score_item_view;
                            }
                        }
                        // Avoid division by zero errors
                        if (!empty($max_score)) {
                            $lpPartialTotal += $score / $max_score;
                        }
                        if ($debug) {
                            var_dump("lpPartialTotal: $lpPartialTotal");
                            var_dump("score: $score");
                            var_dump("max_score: $max_score");
                        }
                    } else {
                        // Case of a TOOL_QUIZ element
                        $item_id = $row_max_score['iid'];
                        $item_path = $row_max_score['path'];
                        $lp_item_view_id = (int) $row_max_score['lp_item_view_id'];

                        $lpItemCondition = '';
                        if (empty($lp_item_view_id)) {
                            $lpItemCondition = ' (orig_lp_item_view_id = 0 OR orig_lp_item_view_id IS NULL) ';
                        } else {
                            $lpItemCondition = " orig_lp_item_view_id = $lp_item_view_id ";
                        }

                        // Get last attempt to this exercise through
                        // the current lp for the current user
                        $order = 'exe_date DESC';
                        if ($getOnlyBestAttempt) {
                            $order = 'score DESC';
                        }
                        $sql = "SELECT exe_id, score
                                FROM $tbl_stats_exercices
                                WHERE
                                    exe_exo_id = '$item_path' AND
                                    exe_user_id = $user_id AND
                                    orig_lp_item_id = $item_id AND
                                    $lpItemCondition AND
                                    c_id = $course_id AND
                                    session_id = $session_id AND
                                    status = ''
                                ORDER BY $order
                                LIMIT 1";

                        $result_last_attempt = Database::query($sql);
                        $num = Database::num_rows($result_last_attempt);
                        if ($num > 0) {
                            $attemptResult = Database::fetch_array($result_last_attempt, 'ASSOC');
                            $id_last_attempt = $attemptResult['exe_id'];
                            // We overwrite the score with the best one not the one saved in the LP (latest)
                            if ($getOnlyBestAttempt && $get_only_latest_attempt_results == false) {
                                if ($debug) {
                                    echo "Following score comes from the track_exercise table not in the LP because the score is the best<br />";
                                }
                                $score = $attemptResult['score'];
                            }

                            if ($debug) {
                                echo "Attempt id: $id_last_attempt with score $score<br />";
                            }
                            // Within the last attempt number tracking, get the sum of
                            // the max_scores of all questions that it was
                            // made of (we need to make this call dynamic because of random questions selection)
                            $sql = "SELECT SUM(t.ponderation) as maxscore FROM
                                            (
                                                SELECT DISTINCT
                                                    question_id,
                                                    marks,
                                                    ponderation
                                                FROM $tbl_stats_attempts AS at
                                                INNER JOIN $tbl_quiz_questions AS q
                                                ON (q.id = at.question_id AND q.c_id = q.c_id)
                                                WHERE
                                                    exe_id ='$id_last_attempt' AND
                                                    q.c_id = $course_id
                                            )
                                            AS t";

                            $res_max_score_bis = Database::query($sql);
                            $row_max_score_bis = Database::fetch_array($res_max_score_bis);

                            if (!empty($row_max_score_bis['maxscore'])) {
                                $max_score = $row_max_score_bis['maxscore'];
                            }
                            if (!empty($max_score) && floatval($max_score) > 0) {
                                $lpPartialTotal += $score / $max_score;
                            }
                            if ($debug) {
                                var_dump("score: $score");
                                var_dump("max_score: $max_score");
                                var_dump("lpPartialTotal: $lpPartialTotal");
                            }
                        }
                    }

                    if (in_array($row_max_score['item_type'], ['quiz', 'sco'])) {
                        // Normal way
                        if ($use_max_score[$lp_id]) {
                            $count_items++;
                        } else {
                            if ($max_score != '') {
                                $count_items++;
                            }
                        }
                        if ($debug) {
                            echo '$count_items: '.$count_items;
                        }
                    }
                } //end for

                $score_of_scorm_calculate += $count_items ? (($lpPartialTotal / $count_items) * 100) : 0;
                $global_result += $score_of_scorm_calculate;

                if ($debug) {
                    var_dump("count_items: $count_items");
                    var_dump("score_of_scorm_calculate: $score_of_scorm_calculate");
                    var_dump("global_result: $global_result");
                }
            } // end while
        }

        $lp_with_quiz = 0;
        foreach ($lp_list as $lp_id) {
            // Check if LP have a score we assume that all SCO have an score
            $sql = "SELECT count(id) as count
                            FROM $lp_item_table
                            WHERE
                                c_id = $course_id AND
                                (item_type = 'quiz' OR item_type = 'sco') AND
                                lp_id = ".$lp_id;
            $result_have_quiz = Database::query($sql);
            if (Database::num_rows($result_have_quiz) > 0) {
                $row = Database::fetch_array($result_have_quiz, 'ASSOC');
                if (is_numeric($row['count']) && $row['count'] != 0) {
                    $lp_with_quiz++;
                }
            }
        }

        if ($debug) {
            echo '<h3>$lp_with_quiz '.$lp_with_quiz.' </h3>';
        }
        if ($debug) {
            echo '<h3>Final return</h3>';
        }

        if ($lp_with_quiz != 0) {
            if (!$return_array) {
                $score_of_scorm_calculate = round(($global_result / $lp_with_quiz), 2);
                if ($debug) {
                    var_dump($score_of_scorm_calculate);
                }
                if (empty($lp_ids)) {
                    if ($debug) {
                        echo '<h2>All lps fix: '.$score_of_scorm_calculate.'</h2>';
                    }
                }

                return $score_of_scorm_calculate;
            }

            if ($debug) {
                var_dump($global_result, $lp_with_quiz);
            }

            return [$global_result, $lp_with_quiz];
        }

        return '-';
    }

    /**
     * This function gets:
     * 1. The score average from all SCORM Test items in all LP in a course-> All the answers / All the max scores.
     * 2. The score average from all Tests (quiz) in all LP in a course-> All the answers / All the max scores.
     * 3. And finally it will return the average between 1. and 2.
     * This function does not take the results of a Test out of a LP.
     *
     * @param int|array $student_id  Array of user ids or an user id
     * @param string    $course_code Course code
     * @param array     $lp_ids      List of LP ids
     * @param int       $session_id  Session id (optional), if param $session_id is 0(default)
     *                               it'll return results including sessions, 0 = session is not filtered
     *
     * @return string value (number %) Which represents a round integer explain in got in 3
     */
    public static function getAverageStudentScore(
        $student_id,
        $course_code = '',
        $lp_ids = [],
        $session_id = 0
    ) {
        if (empty($student_id)) {
            return 0;
        }

        $conditions = [];
        if (!empty($course_code)) {
            $course = api_get_course_info($course_code);
            $courseId = $course['real_id'];
            $conditions[] = " c_id = $courseId";
        }

        // Get course tables names
        $lp_table = Database::get_course_table(TABLE_LP_MAIN);
        $lp_item_table = Database::get_course_table(TABLE_LP_ITEM);
        $lp_view_table = Database::get_course_table(TABLE_LP_VIEW);
        $lp_item_view_table = Database::get_course_table(TABLE_LP_ITEM_VIEW);

        // Compose a filter based on optional learning paths list given
        if (!empty($lp_ids) && count($lp_ids) > 0) {
            $conditions[] = ' id IN ('.implode(',', $lp_ids).') ';
        }

        // Compose a filter based on optional session id
        $session_id = (int) $session_id;
        if (!empty($session_id)) {
            $conditions[] = " session_id = $session_id ";
        }

        if (is_array($student_id)) {
            array_walk($student_id, 'intval');
            $conditions[] = " lp_view.user_id IN (".implode(',', $student_id).") ";
        } else {
            $student_id = (int) $student_id;
            $conditions[] = " lp_view.user_id = $student_id ";
        }

        $conditionsToString = implode('AND ', $conditions);
        $sql = "SELECT
                    SUM(lp_iv.score) sum_score,
                    SUM(lp_i.max_score) sum_max_score
                FROM $lp_table as lp
                INNER JOIN $lp_item_table as lp_i
                ON lp.iid = lp_id AND lp.c_id = lp_i.c_id
                INNER JOIN $lp_view_table as lp_view
                ON lp_view.lp_id = lp_i.lp_id AND lp_view.c_id = lp_i.c_id
                INNER JOIN $lp_item_view_table as lp_iv
                ON lp_i.iid = lp_iv.lp_item_id AND lp_view.c_id = lp_iv.c_id AND lp_iv.lp_view_id = lp_view.iid
                WHERE (lp_i.item_type='sco' OR lp_i.item_type='".TOOL_QUIZ."') AND
                $conditionsToString
        ";
        $result = Database::query($sql);
        $row = Database::fetch_array($result, 'ASSOC');

        if (empty($row['sum_max_score'])) {
            return 0;
        }

        return ($row['sum_score'] / $row['sum_max_score']) * 100;
    }

    /**
     * This function gets time spent in learning path for a student inside a course.
     *
     * @param int|array $student_id  Student id(s)
     * @param string    $course_code Course code
     * @param array     $lp_ids      Limit average to listed lp ids
     * @param int       $session_id  Session id (optional), if param $session_id is null(default)
     *                               it'll return results including sessions, 0 = session is not filtered
     *
     * @return int Total time
     */
    public static function get_time_spent_in_lp(
        $student_id,
        $course_code,
        $lp_ids = [],
        $session_id = 0
    ) {
        $course = api_get_course_info($course_code);
        $student_id = (int) $student_id;
        $session_id = (int) $session_id;
        $total_time = 0;

        if (!empty($course)) {
            $lpTable = Database::get_course_table(TABLE_LP_MAIN);
            $lpItemTable = Database::get_course_table(TABLE_LP_ITEM);
            $lpViewTable = Database::get_course_table(TABLE_LP_VIEW);
            $lpItemViewTable = Database::get_course_table(TABLE_LP_ITEM_VIEW);
            $trackExercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
            $course_id = $course['real_id'];

            // Compose a filter based on optional learning paths list given
            $condition_lp = '';
            if (count($lp_ids) > 0) {
                $condition_lp = " AND id IN(".implode(',', $lp_ids).") ";
            }

            // Check the real number of LPs corresponding to the filter in the
            // database (and if no list was given, get them all)
            $sql = "SELECT DISTINCT(id) FROM $lpTable 
                    WHERE c_id = $course_id $condition_lp";
            $result = Database::query($sql);
            $session_condition = api_get_session_condition($session_id);

            // calculates time
            if (Database::num_rows($result) > 0) {
                while ($row = Database::fetch_array($result)) {
                    $lp_id = (int) $row['id'];

                    // Start Exercise in LP total_time
                    // Get duration time from track_e_exercises.exe_duration instead of lp_view_item.total_time
                    $list = learnpath::get_flat_ordered_items_list($lp_id, 0, $course_id);
                    foreach ($list as $itemId) {
                        $sql = "SELECT max(view_count)
                                FROM $lpViewTable
                                WHERE
                                    c_id = $course_id AND
                                    lp_id = $lp_id AND
                                    user_id = $student_id
                                    $session_condition";
                        $res = Database::query($sql);
                        $view = '';
                        if (Database::num_rows($res) > 0) {
                            $myrow = Database::fetch_array($res);
                            $view = $myrow[0];
                        }
                        $viewCondition = null;
                        if (!empty($view)) {
                            $viewCondition = " AND v.view_count = $view  ";
                        }
                        $sql = "SELECT
                            iv.iid,                                             
                            iv.total_time as mytime,
                            i.id as myid,
                            iv.view_count as iv_view_count,                            
                            path
                        FROM $lpItemTable as i
                        INNER JOIN $lpItemViewTable as iv
                        ON (i.id = iv.lp_item_id AND i.c_id = iv.c_id)
                        INNER JOIN $lpViewTable as v
                        ON (iv.lp_view_id = v.id AND v.c_id = iv.c_id)
                        WHERE
                            v.c_id = $course_id AND
                            i.id = $itemId AND
                            i.lp_id = $lp_id  AND
                            v.user_id = $student_id AND
                            item_type = 'quiz' AND 
                            path <> '' AND
                            v.session_id = $session_id
                            $viewCondition
                        ORDER BY iv.view_count DESC ";

                        $resultRow = Database::query($sql);
                        if (Database::num_rows($resultRow)) {
                            $row = Database::fetch_array($resultRow);
                            $totalTimeInLpItemView = $row['mytime'];
                            $lpItemViewId = $row['iid'];

                            $sql = 'SELECT SUM(exe_duration) exe_duration 
                                    FROM '.$trackExercises.'
                                    WHERE
                                        exe_exo_id="'.$row['path'].'" AND
                                        exe_user_id="'.$student_id.'" AND
                                        orig_lp_id = "'.$lp_id.'" AND
                                        orig_lp_item_id = "'.$row['myid'].'" AND
                                        c_id = '.$course_id.' AND
                                        status <> "incomplete" AND
                                        session_id = '.$session_id.'
                                     ORDER BY exe_date DESC ';

                            $sumScoreResult = Database::query($sql);
                            $durationRow = Database::fetch_array($sumScoreResult, 'ASSOC');
                            if (!empty($durationRow['exe_duration'])) {
                                $exeDuration = $durationRow['exe_duration'];
                                if ($exeDuration != $totalTimeInLpItemView &&
                                    !empty($lpItemViewId) &&
                                    !empty($exeDuration)
                                ) {
                                    // Update c_lp_item_view.total_time
                                    $sqlUpdate = "UPDATE $lpItemViewTable SET total_time = '$exeDuration' 
                                                  WHERE iid = ".$lpItemViewId;
                                    Database::query($sqlUpdate);
                                }
                            }
                        }
                    }

                    // End total_time fix

                    // Calculate total time
                    $sql = "SELECT SUM(total_time)
                            FROM $lpItemViewTable AS item_view
                            INNER JOIN $lpViewTable AS view
                            ON (
                                item_view.lp_view_id = view.id AND
                                item_view.c_id = view.c_id
                            )
                            WHERE
                                item_view.c_id = $course_id AND
                                view.c_id = $course_id AND
                                view.lp_id = $lp_id AND
                                view.user_id = $student_id AND
                                session_id = $session_id";

                    $rs = Database::query($sql);
                    if (Database::num_rows($rs) > 0) {
                        $total_time += Database::result($rs, 0, 0);
                    }
                }
            }
        }

        return $total_time;
    }

    /**
     * This function gets last connection time to one learning path.
     *
     * @param int|array $student_id  Student id(s)
     * @param string    $course_code Course code
     * @param int       $lp_id       Learning path id
     * @param int       $session_id
     *
     * @return int Total time
     */
    public static function get_last_connection_time_in_lp(
        $student_id,
        $course_code,
        $lp_id,
        $session_id = 0
    ) {
        $course = api_get_course_info($course_code);
        $student_id = (int) $student_id;
        $lp_id = (int) $lp_id;
        $session_id = (int) $session_id;
        $lastTime = 0;

        if (!empty($course)) {
            $course_id = $course['real_id'];
            $lp_table = Database::get_course_table(TABLE_LP_MAIN);
            $t_lpv = Database::get_course_table(TABLE_LP_VIEW);
            $t_lpiv = Database::get_course_table(TABLE_LP_ITEM_VIEW);

            // Check the real number of LPs corresponding to the filter in the
            // database (and if no list was given, get them all)
            $sql = "SELECT id FROM $lp_table 
                    WHERE c_id = $course_id AND id = $lp_id ";
            $row = Database::query($sql);
            $count = Database::num_rows($row);

            // calculates last connection time
            if ($count > 0) {
                $sql = 'SELECT MAX(start_time)
                        FROM '.$t_lpiv.' AS item_view
                        INNER JOIN '.$t_lpv.' AS view
                        ON (item_view.lp_view_id = view.id AND item_view.c_id = view.c_id)
                        WHERE
                            status != "not attempted" AND
                            item_view.c_id = '.$course_id.' AND
                            view.c_id = '.$course_id.' AND
                            view.lp_id = '.$lp_id.' AND 
                            view.user_id = '.$student_id.' AND 
                            view.session_id = '.$session_id;
                $rs = Database::query($sql);
                if (Database::num_rows($rs) > 0) {
                    $lastTime = Database::result($rs, 0, 0);
                }
            }
        }

        return $lastTime;
    }

    /**
     * gets the list of students followed by coach.
     *
     * @param int $coach_id Coach id
     *
     * @return array List of students
     */
    public static function get_student_followed_by_coach($coach_id)
    {
        $coach_id = intval($coach_id);
        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_session_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);

        $students = [];
        // At first, courses where $coach_id is coach of the course //
        $sql = 'SELECT session_id, c_id
                FROM '.$tbl_session_course_user.'
                WHERE user_id='.$coach_id.' AND status=2';

        if (api_is_multiple_url_enabled()) {
            $tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $sql = 'SELECT scu.session_id, scu.c_id
                        FROM '.$tbl_session_course_user.' scu
                        INNER JOIN '.$tbl_session_rel_access_url.'  sru
                        ON (scu.session_id=sru.session_id)
                        WHERE
                            scu.user_id='.$coach_id.' AND
                            scu.status=2 AND
                            sru.access_url_id = '.$access_url_id;
            }
        }

        $result = Database::query($sql);

        while ($a_courses = Database::fetch_array($result)) {
            $courseId = $a_courses['c_id'];
            $id_session = $a_courses['session_id'];

            $sql = "SELECT DISTINCT srcru.user_id
                    FROM $tbl_session_course_user AS srcru
                    INNER JOIN $tbl_session_user sru
                    ON (srcru.user_id = sru.user_id AND srcru.session_id = sru.session_id)
                    WHERE
                        sru.relation_type <> ".SESSION_RELATION_TYPE_RRHH." AND
                        srcru.c_id = '$courseId' AND
                        srcru.session_id = '$id_session'";

            $rs = Database::query($sql);
            while ($row = Database::fetch_array($rs)) {
                $students[$row['user_id']] = $row['user_id'];
            }
        }

        // Then, courses where $coach_id is coach of the session
        $sql = 'SELECT session_course_user.user_id
                FROM '.$tbl_session_course_user.' as session_course_user
                INNER JOIN '.$tbl_session_user.' sru
                ON session_course_user.user_id = sru.user_id AND session_course_user.session_id = sru.session_id
                INNER JOIN '.$tbl_session_course.' as session_course
                ON session_course.c_id = session_course_user.c_id
                AND session_course_user.session_id = session_course.session_id
                INNER JOIN '.$tbl_session.' as session
                ON session.id = session_course.session_id
                AND session.id_coach = '.$coach_id;
        if (api_is_multiple_url_enabled()) {
            $tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $sql = 'SELECT session_course_user.user_id
                        FROM '.$tbl_session_course_user.' as session_course_user
                        INNER JOIN '.$tbl_session_user.' sru
                        ON session_course_user.user_id = sru.user_id AND
                           session_course_user.session_id = sru.session_id
                        INNER JOIN '.$tbl_session_course.' as session_course
                        ON session_course.c_id = session_course_user.c_id AND
                        session_course_user.session_id = session_course.session_id
                        INNER JOIN '.$tbl_session.' as session
                        ON session.id = session_course.session_id AND
                        session.id_coach = '.$coach_id.'
                        INNER JOIN '.$tbl_session_rel_access_url.' session_rel_url
                        ON session.id = session_rel_url.session_id 
                        WHERE access_url_id = '.$access_url_id;
            }
        }

        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            $students[$row['user_id']] = $row['user_id'];
        }

        return $students;
    }

    /**
     * Check if a coach is allowed to follow a student.
     *
     * @param    int        Coach id
     * @param    int        Student id
     *
     * @return bool
     */
    public static function is_allowed_to_coach_student($coach_id, $student_id)
    {
        $coach_id = intval($coach_id);
        $student_id = intval($student_id);

        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);

        // At first, courses where $coach_id is coach of the course
        $sql = 'SELECT 1 FROM '.$tbl_session_course_user.'
                WHERE user_id='.$coach_id.' AND status=2';
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            return true;
        }

        // Then, courses where $coach_id is coach of the session
        $sql = 'SELECT session_course_user.user_id
                FROM '.$tbl_session_course_user.' as session_course_user
                INNER JOIN '.$tbl_session_course.' as session_course
                ON session_course.c_id = session_course_user.c_id
                INNER JOIN '.$tbl_session.' as session
                ON session.id = session_course.session_id
                AND session.id_coach = '.$coach_id.'
                WHERE user_id = '.$student_id;
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get courses followed by coach.
     *
     * @param     int        Coach id
     * @param    int        Session id (optional)
     *
     * @return array Courses list
     */
    public static function get_courses_followed_by_coach($coach_id, $id_session = 0)
    {
        $coach_id = intval($coach_id);
        if (!empty($id_session)) {
            $id_session = intval($id_session);
        }

        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

        // At first, courses where $coach_id is coach of the course.
        $sql = 'SELECT DISTINCT c.code
                FROM '.$tbl_session_course_user.' sc
                INNER JOIN '.$tbl_course.' c
                ON (c.id = sc.c_id)
                WHERE user_id = '.$coach_id.' AND status = 2';

        if (api_is_multiple_url_enabled()) {
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $sql = 'SELECT DISTINCT c.code
                        FROM '.$tbl_session_course_user.' scu
                        INNER JOIN '.$tbl_course.' c
                        ON (c.code = scu.c_id)
                        INNER JOIN '.$tbl_course_rel_access_url.' cru
                        ON (c.id = cru.c_id)
                        WHERE
                            scu.user_id='.$coach_id.' AND
                            scu.status=2 AND
                            cru.access_url_id = '.$access_url_id;
            }
        }

        if (!empty($id_session)) {
            $sql .= ' AND session_id='.$id_session;
        }

        $courseList = [];
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            $courseList[$row['code']] = $row['code'];
        }

        // Then, courses where $coach_id is coach of the session
        $sql = 'SELECT DISTINCT course.code
                FROM '.$tbl_session_course.' as session_course
                INNER JOIN '.$tbl_session.' as session
                    ON session.id = session_course.session_id
                    AND session.id_coach = '.$coach_id.'
                INNER JOIN '.$tbl_course.' as course
                    ON course.id = session_course.c_id';

        if (api_is_multiple_url_enabled()) {
            $tbl_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $sql = 'SELECT DISTINCT c.code
                    FROM '.$tbl_session_course.' as session_course
                    INNER JOIN '.$tbl_course.' c
                    ON (c.id = session_course.c_id)
                    INNER JOIN '.$tbl_session.' as session
                    ON session.id = session_course.session_id
                        AND session.id_coach = '.$coach_id.'
                    INNER JOIN '.$tbl_course.' as course
                        ON course.id = session_course.c_id
                     INNER JOIN '.$tbl_course_rel_access_url.' course_rel_url
                    ON (course_rel_url.c_id = c.id)';
            }
        }

        if (!empty($id_session)) {
            $sql .= ' WHERE session_course.session_id='.$id_session;
            if (api_is_multiple_url_enabled()) {
                $sql .= ' AND access_url_id = '.$access_url_id;
            }
        } else {
            if (api_is_multiple_url_enabled()) {
                $sql .= ' WHERE access_url_id = '.$access_url_id;
            }
        }

        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            $courseList[$row['code']] = $row['code'];
        }

        return $courseList;
    }

    /**
     * Get sessions coached by user.
     *
     * @param int    $coach_id
     * @param int    $start
     * @param int    $limit
     * @param bool   $getCount
     * @param string $keyword
     * @param string $description
     * @param string $orderByName
     * @param string $orderByDirection
     * @param array  $options
     *
     * @return mixed
     */
    public static function get_sessions_coached_by_user(
        $coach_id,
        $start = 0,
        $limit = 0,
        $getCount = false,
        $keyword = '',
        $description = '',
        $orderByName = '',
        $orderByDirection = '',
        $options = []
    ) {
        // table definition
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $coach_id = (int) $coach_id;

        $select = ' SELECT * FROM ';
        if ($getCount) {
            $select = ' SELECT count(DISTINCT id) as count FROM ';
        }

        $limitCondition = null;
        if (!empty($start) && !empty($limit)) {
            $limitCondition = " LIMIT ".intval($start).", ".intval($limit);
        }

        $keywordCondition = null;
        if (!empty($keyword)) {
            $keyword = Database::escape_string($keyword);
            $keywordCondition = " AND (name LIKE '%$keyword%' ) ";

            if (!empty($description)) {
                $description = Database::escape_string($description);
                $keywordCondition = " AND (name LIKE '%$keyword%' OR description LIKE '%$description%' ) ";
            }
        }

        $extraFieldModel = new ExtraFieldModel('session');
        $conditions = $extraFieldModel->parseConditions($options);
        $sqlInjectJoins = $conditions['inject_joins'];
        $extraFieldsConditions = $conditions['where'];
        $sqlInjectWhere = $conditions['inject_where'];
        $injectExtraFields = $conditions['inject_extra_fields'];

        $tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
        $access_url_id = api_get_current_access_url_id();

        $orderBy = '';
        if (!empty($orderByName)) {
            if (in_array($orderByName, ['name', 'access_start_date'])) {
                $orderByDirection = in_array(strtolower($orderByDirection), ['asc', 'desc']) ? $orderByDirection : 'asc';
                $orderByName = Database::escape_string($orderByName);
                $orderBy .= " ORDER BY $orderByName $orderByDirection";
            }
        }

        $sql = "
            $select
            (
                SELECT DISTINCT
                    s.id,
                    name,
                    $injectExtraFields
                    access_start_date,
                    access_end_date
                FROM $tbl_session s
                INNER JOIN $tbl_session_rel_access_url session_rel_url
                ON (s.id = session_rel_url.session_id)
                $sqlInjectJoins
                WHERE
                    id_coach = $coach_id AND
                    access_url_id = $access_url_id
                    $keywordCondition
                    $extraFieldsConditions
                    $sqlInjectWhere
            UNION
                SELECT DISTINCT
                    s.id,
                    s.name,
                    $injectExtraFields
                    s.access_start_date,
                    s.access_end_date
                FROM $tbl_session as s
                INNER JOIN $tbl_session_course_user as session_course_user
                ON
                    s.id = session_course_user.session_id AND
                    session_course_user.user_id = $coach_id AND
                    session_course_user.status = 2
                INNER JOIN $tbl_session_rel_access_url session_rel_url                
                ON (s.id = session_rel_url.session_id)
                $sqlInjectJoins
                WHERE
                    access_url_id = $access_url_id
                    $keywordCondition
                    $extraFieldsConditions
                    $sqlInjectWhere
            ) as sessions $limitCondition $orderBy
            ";

        $rs = Database::query($sql);
        if ($getCount) {
            $row = Database::fetch_array($rs);

            return $row['count'];
        }

        $sessions = [];
        while ($row = Database::fetch_array($rs)) {
            if ($row['access_start_date'] === '0000-00-00 00:00:00') {
                $row['access_start_date'] = null;
            }

            $sessions[$row['id']] = $row;
        }

        if (!empty($sessions)) {
            foreach ($sessions as &$session) {
                if (empty($session['access_start_date'])) {
                    $session['status'] = get_lang('active');
                } else {
                    $time_start = api_strtotime($session['access_start_date'], 'UTC');
                    $time_end = api_strtotime($session['access_end_date'], 'UTC');
                    if ($time_start < time() && time() < $time_end) {
                        $session['status'] = get_lang('active');
                    } else {
                        if (time() < $time_start) {
                            $session['status'] = get_lang('Not yet begun');
                        } else {
                            if (time() > $time_end) {
                                $session['status'] = get_lang('Past');
                            }
                        }
                    }
                }
            }
        }

        return $sessions;
    }

    /**
     * Get courses list from a session.
     *
     * @param    int        Session id
     *
     * @return array Courses list
     */
    public static function get_courses_list_from_session($session_id)
    {
        $session_id = (int) $session_id;

        // table definition
        $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);

        $sql = "SELECT DISTINCT code, c_id
                FROM $tbl_session_course sc
                INNER JOIN $courseTable c
                ON sc.c_id = c.id
                WHERE session_id= $session_id";

        $result = Database::query($sql);

        $courses = [];
        while ($row = Database::fetch_array($result)) {
            $courses[$row['code']] = $row;
        }

        return $courses;
    }

    /**
     * Count the number of documents that an user has uploaded to a course.
     *
     * @param    int|array   Student id(s)
     * @param    string      Course code
     * @param    int         Session id (optional),
     * if param $session_id is null(default)
     * return count of assignments including sessions, 0 = session is not filtered
     *
     * @return int Number of documents
     */
    public static function count_student_uploaded_documents(
        $student_id,
        $course_code,
        $session_id = null
    ) {
        // get the information of the course
        $a_course = api_get_course_info($course_code);
        if (!empty($a_course)) {
            // table definition
            $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
            $tbl_document = Database::get_course_table(TABLE_DOCUMENT);
            $course_id = $a_course['real_id'];
            if (is_array($student_id)) {
                $studentList = array_map('intval', $student_id);
                $condition_user = " AND ip.insert_user_id IN ('".implode(',', $studentList)."') ";
            } else {
                $student_id = (int) $student_id;
                $condition_user = " AND ip.insert_user_id = '$student_id' ";
            }

            $condition_session = null;
            if (isset($session_id)) {
                $session_id = (int) $session_id;
                $condition_session = " AND pub.session_id = $session_id ";
            }

            $sql = "SELECT count(ip.tool) AS count
                    FROM $tbl_item_property ip
                    INNER JOIN $tbl_document pub
                    ON (ip.ref = pub.iid AND ip.c_id = pub.c_id)
                    WHERE
                        ip.c_id  = $course_id AND
                        pub.c_id  = $course_id AND
                        pub.filetype ='file' AND
                        ip.tool = 'document'
                        $condition_user $condition_session ";
            $rs = Database::query($sql);
            $row = Database::fetch_array($rs, 'ASSOC');

            return $row['count'];
        }

        return null;
    }

    /**
     * Count assignments per student.
     *
     * @param array|int $student_id
     * @param string    $course_code
     * @param int       $session_id  if param is null(default) return count of assignments including sessions,
     *                               0 = session is not filtered
     *
     * @return int Count of assignments
     */
    public static function count_student_assignments(
        $student_id,
        $course_code = null,
        $session_id = null
    ) {
        if (empty($student_id)) {
            return 0;
        }

        $conditions = [];

        // Get the information of the course
        $a_course = api_get_course_info($course_code);
        if (!empty($a_course)) {
            $course_id = $a_course['real_id'];
            $conditions[] = " ip.c_id  = $course_id AND pub.c_id  = $course_id ";
        }

        // table definition
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $tbl_student_publication = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

        if (is_array($student_id)) {
            $studentList = array_map('intval', $student_id);
            $conditions[] = " ip.insert_user_id IN ('".implode("','", $studentList)."') ";
        } else {
            $student_id = (int) $student_id;
            $conditions[] = " ip.insert_user_id = '$student_id' ";
        }

        $conditions[] = ' pub.active <> 2 ';
        $conditionToString = implode(' AND ', $conditions);
        $sessionCondition = api_get_session_condition($session_id, true, false, 'pub.session_id');
        $conditionToString .= $sessionCondition;

        $sql = "SELECT count(ip.tool) as count
                FROM $tbl_item_property ip
                INNER JOIN $tbl_student_publication pub
                ON (ip.ref = pub.iid AND ip.c_id = pub.c_id)
                WHERE
                    ip.tool='work' AND
                    $conditionToString";
        $rs = Database::query($sql);
        $row = Database::fetch_array($rs, 'ASSOC');

        return $row['count'];
    }

    /**
     * Count messages per student inside forum tool.
     *
     * @param int|array  Student id
     * @param string     Course code
     * @param int        Session id if null(default) return count of messages including sessions, 0 = session is not filtered
     *
     * @return int Count of messages
     */
    public static function count_student_messages($student_id, $courseCode = null, $session_id = null)
    {
        if (empty($student_id)) {
            return 0;
        }

        // Table definition.
        $tbl_forum_post = Database::get_course_table(TABLE_FORUM_POST);
        $tbl_forum = Database::get_course_table(TABLE_FORUM);

        $conditions = [];
        if (is_array($student_id)) {
            $studentList = array_map('intval', $student_id);
            $conditions[] = " post.poster_id IN ('".implode("','", $studentList)."') ";
        } else {
            $student_id = (int) $student_id;
            $conditions[] = " post.poster_id = '$student_id' ";
        }

        $conditionsToString = implode('AND ', $conditions);

        if (empty($courseCode)) {
            $sql = "SELECT count(poster_id) as count
                    FROM $tbl_forum_post post
                    INNER JOIN $tbl_forum forum
                    ON (forum.forum_id = post.forum_id AND forum.c_id = post.c_id)
                    WHERE $conditionsToString";

            $rs = Database::query($sql);
            $row = Database::fetch_array($rs, 'ASSOC');

            return $row['count'];
        }

        require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';

        $courseInfo = api_get_course_info($courseCode);

        $forums = [];
        if (!empty($courseInfo)) {
            $forums = get_forums('', $courseCode, true, $session_id);
            $course_id = $courseInfo['real_id'];
            $conditions[] = " post.c_id  = $course_id ";
        }

        if (!empty($forums)) {
            $idList = array_column($forums, 'forum_id');
            $idListToString = implode("', '", $idList);
            $conditions[] = " post.forum_id  IN ('$idListToString')";
        }

        $conditionsToString = implode('AND ', $conditions);
        $sql = "SELECT count(poster_id) as count
                FROM $tbl_forum_post post
                WHERE $conditionsToString";

        $rs = Database::query($sql);
        $row = Database::fetch_array($rs, 'ASSOC');
        $count = $row['count'];

        return $count;
    }

    /**
     * This function counts the number of post by course.
     *
     * @param string $course_code
     * @param int    $session_id  (optional), if is null(default) it'll return results including sessions,
     *                            0 = session is not filtered
     * @param int    $groupId
     *
     * @return int The number of post by course
     */
    public static function count_number_of_posts_by_course($course_code, $session_id = null, $groupId = 0)
    {
        $courseInfo = api_get_course_info($course_code);
        if (!empty($courseInfo)) {
            $tbl_posts = Database::get_course_table(TABLE_FORUM_POST);
            $tbl_forums = Database::get_course_table(TABLE_FORUM);

            $condition_session = '';
            if (isset($session_id)) {
                $session_id = (int) $session_id;
                $condition_session = api_get_session_condition(
                    $session_id,
                    true,
                    false,
                    'f.session_id'
                );
            }

            $course_id = $courseInfo['real_id'];
            $groupId = (int) $groupId;
            if (!empty($groupId)) {
                $groupCondition = " i.to_group_id = $groupId ";
            } else {
                $groupCondition = ' (i.to_group_id = 0 OR i.to_group_id IS NULL) ';
            }

            $item = Database::get_course_table(TABLE_ITEM_PROPERTY);
            $sql = "SELECT count(*) FROM $tbl_posts p
                    INNER JOIN $tbl_forums f
                    ON f.forum_id = p.forum_id AND p.c_id = f.c_id
                    INNER JOIN $item i
                    ON (tool = '".TOOL_FORUM."' AND f.c_id = i.c_id AND f.iid = i.ref)
                    WHERE
                        p.c_id = $course_id AND
                        f.c_id = $course_id AND
                        $groupCondition
                        $condition_session
                    ";
            $result = Database::query($sql);
            $row = Database::fetch_row($result);
            $count = $row[0];

            return $count;
        }

        return 0;
    }

    /**
     * This function counts the number of threads by course.
     *
     * @param      string     Course code
     * @param    int        Session id (optional),
     * if param $session_id is null(default) it'll return results including
     * sessions, 0 = session is not filtered
     * @param int $groupId
     *
     * @return int The number of threads by course
     */
    public static function count_number_of_threads_by_course(
        $course_code,
        $session_id = null,
        $groupId = 0
    ) {
        $course_info = api_get_course_info($course_code);
        if (empty($course_info)) {
            return null;
        }

        $course_id = $course_info['real_id'];
        $tbl_threads = Database::get_course_table(TABLE_FORUM_THREAD);
        $tbl_forums = Database::get_course_table(TABLE_FORUM);

        $condition_session = '';
        if (isset($session_id)) {
            $session_id = (int) $session_id;
            $condition_session = ' AND f.session_id = '.$session_id;
        }

        $groupId = (int) $groupId;

        if (!empty($groupId)) {
            $groupCondition = " i.to_group_id = $groupId ";
        } else {
            $groupCondition = " (i.to_group_id = 0 OR i.to_group_id IS NULL) ";
        }

        $item = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $sql = "SELECT count(*)
                FROM $tbl_threads t
                INNER JOIN $tbl_forums f
                ON f.iid = t.forum_id AND f.c_id = t.c_id
                INNER JOIN $item i
                ON (
                    tool = '".TOOL_FORUM_THREAD."' AND
                    f.c_id = i.c_id AND
                    t.iid = i.ref
                )
                WHERE
                    t.c_id = $course_id AND
                    f.c_id = $course_id AND
                    $groupCondition
                    $condition_session
                ";

        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $row = Database::fetch_row($result);
            $count = $row[0];

            return $count;
        }

        return 0;
    }

    /**
     * This function counts the number of forums by course.
     *
     * @param      string     Course code
     * @param    int        Session id (optional),
     * if param $session_id is null(default) it'll return results
     * including sessions, 0 = session is not filtered
     * @param int $groupId
     *
     * @return int The number of forums by course
     */
    public static function count_number_of_forums_by_course(
        $course_code,
        $session_id = null,
        $groupId = 0
    ) {
        $course_info = api_get_course_info($course_code);
        if (empty($course_info)) {
            return null;
        }
        $course_id = $course_info['real_id'];

        $condition_session = '';
        if (isset($session_id)) {
            $session_id = (int) $session_id;
            $condition_session = ' AND f.session_id = '.$session_id;
        }

        $groupId = (int) $groupId;
        if (!empty($groupId)) {
            $groupCondition = " i.to_group_id = $groupId ";
        } else {
            $groupCondition = " (i.to_group_id = 0 OR i.to_group_id IS NULL) ";
        }

        $tbl_forums = Database::get_course_table(TABLE_FORUM);
        $item = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $sql = "SELECT count(*)
                FROM $tbl_forums f
                INNER JOIN $item i
                ON f.c_id = i.c_id AND f.iid = i.ref AND tool = '".TOOL_FORUM."'
                WHERE
                    f.c_id = $course_id AND
                    $groupCondition
                    $condition_session
                ";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $row = Database::fetch_row($result);
            $count = $row[0];

            return $count;
        }

        return 0;
    }

    /**
     * This function counts the chat last connections by course in x days.
     *
     * @param      string     Course code
     * @param      int     Last x days
     * @param    int        Session id (optional)
     *
     * @return int Chat last connections by course in x days
     */
    public static function chat_connections_during_last_x_days_by_course(
        $course_code,
        $last_days,
        $session_id = 0
    ) {
        $course_info = api_get_course_info($course_code);
        if (empty($course_info)) {
            return null;
        }
        $course_id = $course_info['real_id'];

        // Protect data
        $last_days = (int) $last_days;
        $session_id = (int) $session_id;

        $tbl_stats_access = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ACCESS);
        $now = api_get_utc_datetime();

        $sql = "SELECT count(*) FROM $tbl_stats_access
                WHERE
                    DATE_SUB('$now',INTERVAL $last_days DAY) <= access_date AND
                    c_id = '$course_id' AND
                    access_tool='".TOOL_CHAT."' AND
                    access_session_id = '$session_id' ";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $row = Database::fetch_row($result);
            $count = $row[0];

            return $count;
        }

        return 0;
    }

    /**
     * This function gets the last student's connection in chat.
     *
     * @param      int     Student id
     * @param      string     Course code
     * @param    int        Session id (optional)
     *
     * @return string datetime formatted without day (e.g: February 23, 2010 10:20:50 )
     */
    public static function chat_last_connection(
        $student_id,
        $courseId,
        $session_id = 0
    ) {
        $student_id = (int) $student_id;
        $courseId = (int) $courseId;
        $session_id = (int) $session_id;
        $date_time = '';

        // table definition
        $tbl_stats_access = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
        $sql = "SELECT access_date
                FROM $tbl_stats_access
                WHERE
                     access_tool='".TOOL_CHAT."' AND
                     access_user_id='$student_id' AND
                     c_id = $courseId AND
                     access_session_id = '$session_id'
                ORDER BY access_date DESC limit 1";
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            $row = Database::fetch_array($rs);
            $date_time = api_convert_and_format_date(
                $row['access_date'],
                null,
                date_default_timezone_get()
            );
        }

        return $date_time;
    }

    /**
     * Get count student's visited links.
     *
     * @param int $student_id Student id
     * @param int $courseId
     * @param int $session_id Session id (optional)
     *
     * @return int count of visited links
     */
    public static function count_student_visited_links($student_id, $courseId, $session_id = 0)
    {
        $student_id = (int) $student_id;
        $courseId = (int) $courseId;
        $session_id = (int) $session_id;

        // table definition
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LINKS);

        $sql = 'SELECT 1
                FROM '.$table.'
                WHERE
                    links_user_id= '.$student_id.' AND
                    c_id = "'.$courseId.'" AND
                    links_session_id = '.$session_id.' ';

        $rs = Database::query($sql);

        return Database::num_rows($rs);
    }

    /**
     * Get count student downloaded documents.
     *
     * @param    int        Student id
     * @param int $courseId
     * @param    int        Session id (optional)
     *
     * @return int Count downloaded documents
     */
    public static function count_student_downloaded_documents($student_id, $courseId, $session_id = 0)
    {
        $student_id = (int) $student_id;
        $courseId = (int) $courseId;
        $session_id = (int) $session_id;

        // table definition
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);

        $sql = 'SELECT 1
                FROM '.$table.'
                WHERE down_user_id = '.$student_id.'
                AND c_id  = "'.$courseId.'"
                AND down_session_id = '.$session_id.' ';
        $rs = Database::query($sql);

        return Database::num_rows($rs);
    }

    /**
     * Get course list inside a session from a student.
     *
     * @param int $user_id    Student id
     * @param int $id_session Session id (optional)
     *
     * @return array Courses list
     */
    public static function get_course_list_in_session_from_student($user_id, $id_session = 0)
    {
        $user_id = intval($user_id);
        $id_session = intval($id_session);
        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);

        $sql = "SELECT c.code
                FROM $tbl_session_course_user sc
                INNER JOIN $courseTable c
                WHERE
                    user_id= $user_id  AND
                    session_id = $id_session";
        $result = Database::query($sql);
        $courses = [];
        while ($row = Database::fetch_array($result)) {
            $courses[$row['code']] = $row['code'];
        }

        return $courses;
    }

    /**
     * Get inactive students in course.
     *
     * @param int        $courseId
     * @param string|int $since      Since login course date (optional, default = 'never')
     * @param int        $session_id (optional)
     *
     * @return array Inactive users
     */
    public static function getInactiveStudentsInCourse(
        $courseId,
        $since = 'never',
        $session_id = 0
    ) {
        $tbl_track_login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $table_course_rel_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
        $now = api_get_utc_datetime();
        $courseId = (int) $courseId;
        $session_id = (int) $session_id;

        if (empty($courseId)) {
            return false;
        }

        if ($since === 'never') {
            if (empty($session_id)) {
                $sql = 'SELECT course_user.user_id
                        FROM '.$table_course_rel_user.' course_user
                        LEFT JOIN '.$tbl_track_login.' stats_login
                        ON course_user.user_id = stats_login.user_id AND
                        relation_type<>'.COURSE_RELATION_TYPE_RRHH.'
                        INNER JOIN '.$tableCourse.' c
                        ON (c.id = course_user.c_id)
                        WHERE
                            course_user.c_id = '.$courseId.' AND
                            stats_login.login_course_date IS NULL
                        GROUP BY course_user.user_id';
            } else {
                $sql = 'SELECT session_course_user.user_id
                        FROM '.$tbl_session_course_user.' session_course_user
                        LEFT JOIN '.$tbl_track_login.' stats_login
                        ON session_course_user.user_id = stats_login.user_id
                        INNER JOIN '.$tableCourse.' c
                        ON (c.id = session_course_user.c_id)
                        WHERE
                            session_course_user.c_id = '.$courseId.' AND
                            stats_login.login_course_date IS NULL
                        GROUP BY session_course_user.user_id';
            }
        } else {
            $since = (int) $since;
            if (empty($session_id)) {
                $inner = 'INNER JOIN '.$table_course_rel_user.' course_user
                          ON course_user.user_id = stats_login.user_id AND course_user.c_id = c.id ';
            } else {
                $inner = 'INNER JOIN '.$tbl_session_course_user.' session_course_user
                          ON
                            c.id = session_course_user.c_id AND
                            session_course_user.session_id = '.$session_id.' AND
                            session_course_user.user_id = stats_login.user_id ';
            }

            $sql = 'SELECT 
                    stats_login.user_id, 
                    MAX(login_course_date) max_date
                FROM '.$tbl_track_login.' stats_login
                INNER JOIN '.$tableCourse.' c
                ON (c.id = stats_login.c_id)
                '.$inner.'
                WHERE c.id = '.$courseId.'
                GROUP BY stats_login.user_id
                HAVING DATE_SUB("'.$now.'", INTERVAL '.$since.' DAY) > max_date ';
        }

        $rs = Database::query($sql);
        $users = [];
        while ($user = Database::fetch_array($rs)) {
            $users[] = $user['user_id'];
        }

        return $users;
    }

    /**
     * get count clicks about tools most used by course.
     *
     * @param int $courseId
     * @param    int        Session id (optional),
     * if param $session_id is null(default) it'll return results
     * including sessions, 0 = session is not filtered
     *
     * @return array tools data
     */
    public static function get_tools_most_used_by_course($courseId, $session_id = null)
    {
        $courseId = (int) $courseId;
        $data = [];
        $TABLETRACK_ACCESS = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
        $condition_session = '';
        if (isset($session_id)) {
            $session_id = (int) $session_id;
            $condition_session = ' AND access_session_id = '.$session_id;
        }
        $sql = "SELECT
                    access_tool,
                    COUNT(DISTINCT access_user_id),
                    count(access_tool) as count_access_tool
                FROM $TABLETRACK_ACCESS
                WHERE
                    access_tool IS NOT NULL AND
                    access_tool != '' AND
                    c_id = '$courseId'
                    $condition_session
                GROUP BY access_tool
                ORDER BY count_access_tool DESC
                LIMIT 0, 3";
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            while ($row = Database::fetch_array($rs)) {
                $data[] = $row;
            }
        }

        return $data;
    }

    /**
     * get documents most downloaded by course.
     *
     * @param      string     Course code
     * @param    int        Session id (optional),
     * if param $session_id is null(default) it'll return results including
     * sessions, 0 = session is not filtered
     * @param    int        Limit (optional, default = 0, 0 = without limit)
     *
     * @return array documents downloaded
     */
    public static function get_documents_most_downloaded_by_course(
        $course_code,
        $session_id = 0,
        $limit = 0
    ) {
        $courseId = api_get_course_int_id($course_code);
        $data = [];

        $TABLETRACK_DOWNLOADS = Database::get_main_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
        $condition_session = '';
        $session_id = intval($session_id);
        if (!empty($session_id)) {
            $condition_session = ' AND down_session_id = '.$session_id;
        }
        $sql = "SELECT
                    down_doc_path,
                    COUNT(DISTINCT down_user_id),
                    COUNT(down_doc_path) as count_down
                FROM $TABLETRACK_DOWNLOADS
                WHERE c_id = $courseId
                    $condition_session
                GROUP BY down_doc_path
                ORDER BY count_down DESC
                LIMIT 0,  $limit";
        $rs = Database::query($sql);

        if (Database::num_rows($rs) > 0) {
            while ($row = Database::fetch_array($rs)) {
                $data[] = $row;
            }
        }

        return $data;
    }

    /**
     * get links most visited by course.
     *
     * @param      string     Course code
     * @param    int        Session id (optional),
     * if param $session_id is null(default) it'll
     * return results including sessions, 0 = session is not filtered
     *
     * @return array links most visited
     */
    public static function get_links_most_visited_by_course($course_code, $session_id = null)
    {
        $course_code = Database::escape_string($course_code);
        $course_info = api_get_course_info($course_code);
        $course_id = $course_info['real_id'];
        $data = [];

        $TABLETRACK_LINKS = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LINKS);
        $TABLECOURSE_LINKS = Database::get_course_table(TABLE_LINK);

        $condition_session = '';
        if (isset($session_id)) {
            $session_id = intval($session_id);
            $condition_session = ' AND cl.session_id = '.$session_id;
        }

        $sql = "SELECT cl.title, cl.url,count(DISTINCT sl.links_user_id), count(cl.title) as count_visits
                FROM $TABLETRACK_LINKS AS sl, $TABLECOURSE_LINKS AS cl
                WHERE
                    cl.c_id = $course_id AND
                    sl.links_link_id = cl.id AND
                    sl.c_id = $course_id
                    $condition_session
                GROUP BY cl.title, cl.url
                ORDER BY count_visits DESC
                LIMIT 0, 3";
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            while ($row = Database::fetch_array($rs)) {
                $data[] = $row;
            }
        }

        return $data;
    }

    /**
     * Shows the user progress (when clicking in the Progress tab).
     *
     * @param int    $user_id
     * @param int    $session_id
     * @param string $extra_params
     * @param bool   $show_courses
     * @param bool   $showAllSessions
     * @param bool   $returnArray
     *
     * @return string|array
     */
    public static function show_user_progress(
        $user_id,
        $session_id = 0,
        $extra_params = '',
        $show_courses = true,
        $showAllSessions = true,
        $returnArray = false
    ) {
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_access_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_access_rel_session = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);

        $trackingColumns = [
            'course_session' => [
                'course_title' => true,
                'published_exercises' => true,
                'new_exercises' => true,
                'my_average' => true,
                'average_exercise_result' => true,
                'time_spent' => true,
                'lp_progress' => true,
                'score' => true,
                'best_score' => true,
                'last_connection' => true,
                'details' => true,
            ],
        ];

        $trackingColumnsConfig = api_get_configuration_value('tracking_columns');
        if (!empty($trackingColumnsConfig)) {
            $trackingColumns = $trackingColumnsConfig;
        }

        $user_id = (int) $user_id;
        $session_id = (int) $session_id;
        $urlId = api_get_current_access_url_id();

        if (api_is_multiple_url_enabled()) {
            $sql = "SELECT c.id, c.code, title
                    FROM $tbl_course_user cu
                    INNER JOIN $tbl_course c
                    ON (cu.c_id = c.id)
                    INNER JOIN $tbl_access_rel_course a
                    ON (a.c_id = c.id)
                    WHERE
                        cu.user_id = $user_id AND
                        relation_type<> ".COURSE_RELATION_TYPE_RRHH." AND
                        access_url_id = $urlId
                    ORDER BY title";
        } else {
            $sql = "SELECT c.id, c.code, title
                    FROM $tbl_course_user u
                    INNER JOIN $tbl_course c ON (c_id = c.id)
                    WHERE
                        u.user_id= $user_id AND
                        relation_type <> ".COURSE_RELATION_TYPE_RRHH."
                    ORDER BY title";
        }

        $rs = Database::query($sql);
        $courses = $course_in_session = $temp_course_in_session = [];
        $courseIdList = [];
        while ($row = Database::fetch_array($rs, 'ASSOC')) {
            $courses[$row['code']] = $row['title'];
            $courseIdList[] = $row['id'];
        }

        $orderBy = ' ORDER BY name ';
        $extraInnerJoin = null;

        if (SessionManager::orderCourseIsEnabled() && !empty($session_id)) {
            $orderBy = ' ORDER BY s.id, src.position ';
            $tableSessionRelCourse = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
            $extraInnerJoin = " INNER JOIN $tableSessionRelCourse src
                                ON (cu.c_id = src.c_id AND src.session_id = $session_id) ";
        }

        $sessionCondition = '';
        if (!empty($session_id)) {
            $sessionCondition = " AND s.id = $session_id";
        }

        // Get the list of sessions where the user is subscribed as student
        if (api_is_multiple_url_enabled()) {
            $sql = "SELECT DISTINCT c.code, s.id as session_id, name
                    FROM $tbl_session_course_user cu
                    INNER JOIN $tbl_access_rel_session a
                    ON (a.session_id = cu.session_id)
                    INNER JOIN $tbl_session s
                    ON (s.id = a.session_id)
                    INNER JOIN $tbl_course c
                    ON (c.id = cu.c_id)
                    $extraInnerJoin
                    WHERE
                        cu.user_id = $user_id AND
                        access_url_id = ".$urlId."
                        $sessionCondition
                    $orderBy ";
        } else {
            $sql = "SELECT DISTINCT c.code, s.id as session_id, name
                    FROM $tbl_session_course_user cu
                    INNER JOIN $tbl_session s
                    ON (s.id = cu.session_id)
                    INNER JOIN $tbl_course c
                    ON (c.id = cu.c_id)
                    $extraInnerJoin
                    WHERE
                        cu.user_id = $user_id
                        $sessionCondition
                    $orderBy ";
        }

        $rs = Database::query($sql);
        $simple_session_array = [];
        while ($row = Database::fetch_array($rs, 'ASSOC')) {
            $course_info = api_get_course_info($row['code']);
            $temp_course_in_session[$row['session_id']]['course_list'][$course_info['real_id']] = $course_info;
            $temp_course_in_session[$row['session_id']]['name'] = $row['name'];
            $simple_session_array[$row['session_id']] = $row['name'];
        }

        foreach ($simple_session_array as $my_session_id => $session_name) {
            $course_list = $temp_course_in_session[$my_session_id]['course_list'];
            $my_course_data = [];
            foreach ($course_list as $courseId => $course_data) {
                $my_course_data[$courseId] = $course_data['title'];
            }

            if (empty($session_id)) {
                $my_course_data = utf8_sort($my_course_data);
            }

            $final_course_data = [];
            foreach ($my_course_data as $course_id => $value) {
                if (isset($course_list[$course_id])) {
                    $final_course_data[$course_id] = $course_list[$course_id];
                }
            }
            $course_in_session[$my_session_id]['course_list'] = $final_course_data;
            $course_in_session[$my_session_id]['name'] = $session_name;
        }

        if ($returnArray) {
            $course_in_session[0] = $courseIdList;

            return $course_in_session;
        }

        $html = '';
        // Course list
        if ($show_courses) {
            if (!empty($courses)) {
                $html .= Display::page_subheader(
                    Display::return_icon(
                        'course.png',
                        get_lang('My courses'),
                        [],
                        ICON_SIZE_SMALL
                    ).' '.get_lang('My courses')
                );

                $columns = [
                    'course_title' => get_lang('Course'),
                    'time_spent' => get_lang('Time spent in the course'),
                    'progress' => get_lang('Progress'),
                    'best_score_in_lp' => get_lang('Best score in learning path'),
                    'best_score_not_in_lp' => get_lang('Best score not in learning path'),
                    'latest_login' => get_lang('Latest login'),
                    'details' => get_lang('Details'),
                ];
                $availableColumns = [];
                if (isset($trackingColumns['my_progress_courses'])) {
                    $availableColumns = $trackingColumns['my_progress_courses'];
                }
                $html .= '<div class="table-responsive">';
                $html .= '<table class="table table-striped table-hover">';
                $html .= '<thead><tr>';
                foreach ($columns as $columnKey => $name) {
                    if (!empty($availableColumns)) {
                        if (isset($availableColumns[$columnKey]) && $availableColumns[$columnKey] == false) {
                            continue;
                        }
                    }
                    $html .= Display::tag('th', $name);
                }
                $html .= '</tr></thead><tbody>';

                foreach ($courses as $course_code => $course_title) {
                    $courseInfo = api_get_course_info($course_code);
                    $courseId = $courseInfo['real_id'];

                    $total_time_login = self::get_time_spent_on_the_course(
                        $user_id,
                        $courseId
                    );
                    $time = api_time_to_hms($total_time_login);
                    $progress = self::get_avg_student_progress(
                        $user_id,
                        $course_code
                    );
                    $bestScore = self::get_avg_student_score(
                        $user_id,
                        $course_code,
                        [],
                        null,
                        false,
                        false,
                        true
                    );

                    $exerciseList = ExerciseLib::get_all_exercises(
                        $courseInfo,
                        0,
                        false,
                        null,
                        false,
                        1
                    );

                    $bestScoreAverageNotInLP = 0;
                    if (!empty($exerciseList)) {
                        foreach ($exerciseList as $exerciseData) {
                            $results = Event::get_best_exercise_results_by_user(
                                $exerciseData['id'],
                                $courseInfo['real_id'],
                                0,
                                $user_id
                            );
                            $best = 0;
                            if (!empty($results)) {
                                foreach ($results as $result) {
                                    if (!empty($result['max_score'])) {
                                        $score = $result['score'] / $result['max_score'];
                                        if ($score > $best) {
                                            $best = $score;
                                        }
                                    }
                                }
                            }
                            $bestScoreAverageNotInLP += $best;
                        }
                        $bestScoreAverageNotInLP = round($bestScoreAverageNotInLP / count($exerciseList) * 100, 2);
                    }

                    $last_connection = self::get_last_connection_date_on_the_course(
                        $user_id,
                        $courseInfo
                    );

                    if (is_null($progress) || empty($progress)) {
                        $progress = '0%';
                    } else {
                        $progress = $progress.'%';
                    }

                    if (isset($_GET['course']) &&
                        $course_code == $_GET['course'] &&
                        empty($_GET['session_id'])
                    ) {
                        $html .= '<tr class="row_odd" style="background-color:#FBF09D">';
                    } else {
                        $html .= '<tr class="row_even">';
                    }
                    $url = api_get_course_url($course_code, $session_id);
                    $course_url = Display::url($course_title, $url, ['target' => SESSION_LINK_TARGET]);
                    $bestScoreResult = '';
                    if (empty($bestScore)) {
                        $bestScoreResult = '-';
                    } else {
                        $bestScoreResult = $bestScore.'%';
                    }
                    $bestScoreNotInLP = '';
                    if (empty($bestScoreAverageNotInLP)) {
                        $bestScoreNotInLP = '-';
                    } else {
                        $bestScoreNotInLP = $bestScoreAverageNotInLP.'%';
                    }

                    $detailsLink = '';
                    if (isset($_GET['course']) &&
                        $course_code == $_GET['course'] &&
                        empty($_GET['session_id'])
                    ) {
                        $detailsLink .= '<a href="#course_session_header">';
                        $detailsLink .= Display::return_icon('2rightarrow_na.png', get_lang('Details'));
                        $detailsLink .= '</a>';
                    } else {
                        $detailsLink .= '<a href="'.api_get_self().'?course='.$course_code.$extra_params.'#course_session_header">';
                        $detailsLink .= Display::return_icon('2rightarrow.png', get_lang('Details'));
                        $detailsLink .= '</a>';
                    }

                    $result = [
                        'course_title' => $course_url,
                        'time_spent' => $time,
                        'progress' => $progress,
                        'best_score_in_lp' => $bestScoreResult,
                        'best_score_not_in_lp' => $bestScoreNotInLP,
                        'latest_login' => $last_connection,
                        'details' => $detailsLink,
                    ];

                    foreach ($result as $columnKey => $data) {
                        if (!empty($availableColumns)) {
                            if (isset($availableColumns[$columnKey]) && $availableColumns[$columnKey] == false) {
                                continue;
                            }
                        }
                        $html .= '<td>'.$data.'</td>';
                    }

                    $html .= '</tr>';
                }
                $html .= '</tbody></table>';
                $html .= '</div>';
            }
        }

        // Session list
        if (!empty($course_in_session)) {
            $main_session_graph = '';
            // Load graphics only when calling to an specific session
            $all_exercise_graph_name_list = [];
            $my_results = [];
            $all_exercise_graph_list = [];
            $all_exercise_start_time = [];
            foreach ($course_in_session as $my_session_id => $session_data) {
                $course_list = $session_data['course_list'];
                $user_count = count(SessionManager::get_users_by_session($my_session_id));
                $exercise_graph_name_list = [];
                $exercise_graph_list = [];

                foreach ($course_list as $course_data) {
                    $exercise_list = ExerciseLib::get_all_exercises(
                        $course_data,
                        $my_session_id,
                        false,
                        null,
                        false,
                        1
                    );

                    foreach ($exercise_list as $exercise_data) {
                        $exercise_obj = new Exercise($course_data['real_id']);
                        $exercise_obj->read($exercise_data['id']);
                        // Exercise is not necessary to be visible to show results check the result_disable configuration instead
                        //$visible_return = $exercise_obj->is_visible();
                        if ($exercise_data['results_disabled'] == 0 || $exercise_data['results_disabled'] == 2) {
                            $best_average = (int)
                                ExerciseLib::get_best_average_score_by_exercise(
                                    $exercise_data['id'],
                                    $course_data['real_id'],
                                    $my_session_id,
                                    $user_count
                                )
                            ;

                            $exercise_graph_list[] = $best_average;
                            $all_exercise_graph_list[] = $best_average;

                            $user_result_data = ExerciseLib::get_best_attempt_by_user(
                                api_get_user_id(),
                                $exercise_data['id'],
                                $course_data['real_id'],
                                $my_session_id
                            );

                            $score = 0;
                            if (!empty($user_result_data['max_score']) && intval($user_result_data['max_score']) != 0) {
                                $score = intval($user_result_data['score'] / $user_result_data['max_score'] * 100);
                            }
                            $time = api_strtotime($exercise_data['start_time']) ? api_strtotime($exercise_data['start_time'], 'UTC') : 0;
                            $all_exercise_start_time[] = $time;
                            $my_results[] = $score;
                            if (count($exercise_list) <= 10) {
                                $title = cut($course_data['title'], 30)." \n ".cut($exercise_data['title'], 30);
                                $exercise_graph_name_list[] = $title;
                                $all_exercise_graph_name_list[] = $title;
                            } else {
                                // if there are more than 10 results, space becomes difficult to find,
                                // so only show the title of the exercise, not the tool
                                $title = cut($exercise_data['title'], 30);
                                $exercise_graph_name_list[] = $title;
                                $all_exercise_graph_name_list[] = $title;
                            }
                        }
                    }
                }
            }

            // Complete graph
            if (!empty($my_results) && !empty($all_exercise_graph_list)) {
                asort($all_exercise_start_time);

                //Fix exams order
                $final_all_exercise_graph_name_list = [];
                $my_results_final = [];
                $final_all_exercise_graph_list = [];

                foreach ($all_exercise_start_time as $key => $time) {
                    $label_time = '';
                    if (!empty($time)) {
                        $label_time = date('d-m-y', $time);
                    }
                    $final_all_exercise_graph_name_list[] = $all_exercise_graph_name_list[$key].' '.$label_time;
                    $my_results_final[] = $my_results[$key];
                    $final_all_exercise_graph_list[] = $all_exercise_graph_list[$key];
                }
                $main_session_graph = self::generate_session_exercise_graph(
                    $final_all_exercise_graph_name_list,
                    $my_results_final,
                    $final_all_exercise_graph_list
                );
            }

            $sessionIcon = Display::return_icon(
                'session.png',
                get_lang('Course sessions'),
                [],
                ICON_SIZE_SMALL
            );

            $anchor = Display::url('', '', ['name' => 'course_session_header']);
            $html .= $anchor.Display::page_subheader(
                $sessionIcon.' '.get_lang('Course sessions')
            );

            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-striped table-hover">';
            $html .= '<thead>';
            $html .= '<tr>
                  '.Display::tag('th', get_lang('Session'), ['width' => '300px']).'
                  '.Display::tag('th', get_lang('Tests available'), ['width' => '300px']).'
                  '.Display::tag('th', get_lang('New exercises')).'
                  '.Display::tag('th', get_lang('Average exercise result')).'
                  '.Display::tag('th', get_lang('Details')).'
                  </tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            foreach ($course_in_session as $my_session_id => $session_data) {
                $course_list = $session_data['course_list'];
                $session_name = $session_data['name'];
                if ($showAllSessions == false) {
                    if (isset($session_id) && !empty($session_id)) {
                        if ($session_id != $my_session_id) {
                            continue;
                        }
                    }
                }

                $all_exercises = 0;
                $all_unanswered_exercises_by_user = 0;
                $all_average = 0;
                $stats_array = [];

                foreach ($course_list as $course_data) {
                    // All exercises in the course @todo change for a real count
                    $exercises = ExerciseLib::get_all_exercises($course_data, $my_session_id);
                    $count_exercises = 0;
                    if (is_array($exercises) && !empty($exercises)) {
                        $count_exercises = count($exercises);
                    }

                    // Count of user results
                    $done_exercises = null;
                    $courseInfo = api_get_course_info($course_data['code']);

                    $answered_exercises = 0;
                    if (!empty($exercises)) {
                        foreach ($exercises as $exercise_item) {
                            $attempts = Event::count_exercise_attempts_by_user(
                                api_get_user_id(),
                                $exercise_item['id'],
                                $courseInfo['real_id'],
                                $my_session_id
                            );
                            if ($attempts > 1) {
                                $answered_exercises++;
                            }
                        }
                    }

                    // Average
                    $average = ExerciseLib::get_average_score_by_course(
                        $courseInfo['real_id'],
                        $my_session_id
                    );
                    $all_exercises += $count_exercises;
                    $all_unanswered_exercises_by_user += $count_exercises - $answered_exercises;
                    $all_average += $average;
                }

                if (!empty($course_list)) {
                    $all_average = $all_average / count($course_list);
                }

                if (isset($_GET['session_id']) && $my_session_id == $_GET['session_id']) {
                    $html .= '<tr style="background-color:#FBF09D">';
                } else {
                    $html .= '<tr>';
                }
                $url = api_get_path(WEB_CODE_PATH)."session/index.php?session_id={$my_session_id}";

                $html .= Display::tag('td', Display::url($session_name, $url, ['target' => SESSION_LINK_TARGET]));
                $html .= Display::tag('td', $all_exercises);
                $html .= Display::tag('td', $all_unanswered_exercises_by_user);
                $html .= Display::tag('td', ExerciseLib::convert_to_percentage($all_average));

                if (isset($_GET['session_id']) && $my_session_id == $_GET['session_id']) {
                    $icon = Display::url(
                        Display::return_icon(
                            '2rightarrow_na.png',
                            get_lang('Details')
                        ),
                        api_get_self().'?session_id='.$my_session_id.'#course_session_list'
                    );
                } else {
                    $icon = Display::url(
                        Display::return_icon(
                            '2rightarrow.png',
                            get_lang('Details')
                        ),
                        api_get_self().'?session_id='.$my_session_id.'#course_session_list'
                    );
                }
                $html .= Display::tag('td', $icon);
                $html .= '</tr>';
            }
            $html .= '</tbody>';
            $html .= '</table></div><br />';
            $html .= Display::div(
                $main_session_graph,
                [
                    'id' => 'session_graph',
                    'class' => 'chart-session',
                    'style' => 'position:relative; text-align: center;',
                ]
            );

            // Checking selected session.
            if (isset($_GET['session_id'])) {
                $session_id_from_get = (int) $_GET['session_id'];
                $session_data = $course_in_session[$session_id_from_get];
                $course_list = $session_data['course_list'];

                $html .= '<a name= "course_session_list"></a>';
                $html .= Display::tag('h3', $session_data['name'].' - '.get_lang('Course list'));

                $html .= '<div class="table-responsive">';
                $html .= '<table class="table table-hover table-striped">';

                $columnHeaders = [
                    'course_title' => [
                        get_lang('Course'),
                        ['width' => '300px'],
                    ],
                    'published_exercises' => [
                        get_lang('Tests available'),
                    ],
                    'new_exercises' => [
                        get_lang('New exercises'),
                    ],
                    'my_average' => [
                        get_lang('My average'),
                    ],
                    'average_exercise_result' => [
                        get_lang('Average exercise result'),
                    ],
                    'time_spent' => [
                        get_lang('Time spent in the course'),
                    ],
                    'lp_progress' => [
                        get_lang('Learning path progress'),
                    ],
                    'score' => [
                        get_lang('Score').
                        Display::return_icon(
                            'info3.gif',
                            get_lang('Average of tests in Learning Paths'),
                            ['align' => 'absmiddle', 'hspace' => '3px']
                        ),
                    ],
                    'best_score' => [
                        get_lang('Best score'),
                    ],
                    'last_connection' => [
                        get_lang('Latest login'),
                    ],
                    'details' => [
                        get_lang('Details'),
                    ],
                ];

                $html .= '<thead><tr>';
                foreach ($columnHeaders as $key => $columnSetting) {
                    if (isset($trackingColumns['course_session']) &&
                        in_array($key, $trackingColumns['course_session']) &&
                        $trackingColumns['course_session'][$key]
                    ) {
                        $settings = isset($columnSetting[1]) ? $columnSetting[1] : [];
                        $html .= Display::tag(
                             'th',
                             $columnSetting[0],
                             $settings
                         );
                    }
                }

                $html .= '</tr>
                    </thead>
                    <tbody>';

                foreach ($course_list as $course_data) {
                    $course_code = $course_data['code'];
                    $course_title = $course_data['title'];
                    $courseId = $course_data['real_id'];

                    // All exercises in the course @todo change for a real count
                    $exercises = ExerciseLib::get_all_exercises(
                        $course_data,
                        $session_id_from_get
                    );
                    $count_exercises = 0;
                    if (!empty($exercises)) {
                        $count_exercises = count($exercises);
                    }
                    $answered_exercises = 0;
                    foreach ($exercises as $exercise_item) {
                        $attempts = Event::count_exercise_attempts_by_user(
                            api_get_user_id(),
                            $exercise_item['id'],
                            $courseId,
                            $session_id_from_get
                        );
                        if ($attempts > 1) {
                            $answered_exercises++;
                        }
                    }

                    $unanswered_exercises = $count_exercises - $answered_exercises;

                    // Average
                    $average = ExerciseLib::get_average_score_by_course(
                        $courseId,
                        $session_id_from_get
                    );
                    $my_average = ExerciseLib::get_average_score_by_course_by_user(
                        api_get_user_id(),
                        $courseId,
                        $session_id_from_get
                    );

                    $bestScore = self::get_avg_student_score(
                        $user_id,
                        $course_code,
                        [],
                        $session_id_from_get,
                        false,
                        false,
                        true
                    );

                    $stats_array[$course_code] = [
                        'exercises' => $count_exercises,
                        'unanswered_exercises_by_user' => $unanswered_exercises,
                        'done_exercises' => $done_exercises,
                        'average' => $average,
                        'my_average' => $my_average,
                        'best_score' => $bestScore,
                    ];

                    $last_connection = self::get_last_connection_date_on_the_course(
                        $user_id,
                        $course_data,
                        $session_id_from_get
                    );

                    $progress = self::get_avg_student_progress(
                        $user_id,
                        $course_code,
                        [],
                        $session_id_from_get
                    );

                    $total_time_login = self::get_time_spent_on_the_course(
                        $user_id,
                        $courseId,
                        $session_id_from_get
                    );
                    $time = api_time_to_hms($total_time_login);

                    $percentage_score = self::get_avg_student_score(
                        $user_id,
                        $course_code,
                        [],
                        $session_id_from_get
                    );
                    $courseCodeFromGet = isset($_GET['course']) ? $_GET['course'] : null;

                    if ($course_code == $courseCodeFromGet && $_GET['session_id'] == $session_id_from_get) {
                        $html .= '<tr class="row_odd" style="background-color:#FBF09D" >';
                    } else {
                        $html .= '<tr class="row_even">';
                    }

                    $url = api_get_course_url($course_code, $session_id_from_get);
                    $course_url = Display::url(
                        $course_title,
                        $url,
                        ['target' => SESSION_LINK_TARGET]
                    );

                    if (is_numeric($progress)) {
                        $progress = $progress.'%';
                    } else {
                        $progress = '0%';
                    }
                    if (is_numeric($percentage_score)) {
                        $percentage_score = $percentage_score.'%';
                    } else {
                        $percentage_score = '0%';
                    }

                    if (is_numeric($stats_array[$course_code]['best_score'])) {
                        $bestScore = $stats_array[$course_code]['best_score'].'%';
                    } else {
                        $bestScore = '-';
                    }

                    if (empty($last_connection) || is_bool($last_connection)) {
                        $last_connection = '';
                    }

                    if ($course_code == $courseCodeFromGet &&
                        $_GET['session_id'] == $session_id_from_get
                    ) {
                        $details = Display::url(
                            Display::return_icon('2rightarrow_na.png', get_lang('Details')),
                        '#course_session_data'
                        );
                    } else {
                        $url = api_get_self().'?course='.$course_code.'&session_id='.$session_id_from_get.$extra_params.'#course_session_data';
                        $details = Display::url(
                            Display::return_icon(
                                '2rightarrow.png',
                                get_lang('Details')
                            ),
                            $url
                        );
                    }
                    $details .= '</a>';

                    $data = [
                        'course_title' => $course_url,
                        'published_exercises' => $stats_array[$course_code]['exercises'], // exercise available
                        'new_exercises' => $stats_array[$course_code]['unanswered_exercises_by_user'],
                        'my_average' => ExerciseLib::convert_to_percentage($stats_array[$course_code]['my_average']),
                        'average_exercise_result' => $stats_array[$course_code]['average'] == 0 ? '-' : '('.ExerciseLib::convert_to_percentage($stats_array[$course_code]['average']).')',
                        'time_spent' => $time,
                        'lp_progress' => $progress,
                        'score' => $percentage_score,
                        'best_score' => $bestScore,
                        'last_connection' => $last_connection,
                        'details' => $details,
                    ];

                    foreach ($data as $key => $value) {
                        if (in_array($key, $trackingColumns['course_session'])
                            && $trackingColumns['course_session'][$key]
                        ) {
                            $html .= Display::tag('td', $value);
                        }
                    }
                    $html .= '</tr>';
                }
                $html .= '</tbody></table></div>';
            }
        }

        $pluginCalendar = api_get_plugin_setting('learning_calendar', 'enabled') === 'true';
        if ($pluginCalendar) {
            $course_in_session[0] = $courseIdList;
            $plugin = LearningCalendarPlugin::create();
            $html .= $plugin->getUserStatsPanel($user_id, $course_in_session);
        }

        return $html;
    }

    /**
     * Shows the user detail progress (when clicking in the details link).
     *
     * @param int    $user_id
     * @param string $course_code
     * @param int    $session_id
     *
     * @return string html code
     */
    public static function show_course_detail($user_id, $course_code, $session_id)
    {
        $html = '';
        if (isset($course_code)) {
            $user_id = (int) $user_id;
            $session_id = (int) $session_id;
            $course = Database::escape_string($course_code);
            $course_info = api_get_course_info($course);
            if (empty($course_info)) {
                return '';
            }

            $html .= '<a name="course_session_data"></a>';
            $html .= Display::page_subheader($course_info['title']);
            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-striped table-hover">';

            // Course details
            $html .= '
                <thead>
                <tr>
                <th>'.get_lang('Tests').'</th>
                <th>'.get_lang('Attempts').'</th>
                <th>'.get_lang('Best attempt').'</th>
                <th>'.get_lang('Ranking').'</th>
                <th>'.get_lang('Best result in course').'</th>
                <th>'.get_lang('Statistics').' '.Display::return_icon('info3.gif', get_lang('In case of multiple attempts, only shows the best result of each learner'), ['align' => 'absmiddle', 'hspace' => '3px']).'</th>
                </tr>
                </thead>
                <tbody>';

            if (empty($session_id)) {
                $user_list = CourseManager::get_user_list_from_course_code(
                    $course,
                    $session_id,
                    null,
                    null,
                    STUDENT
                );
            } else {
                $user_list = CourseManager::get_user_list_from_course_code(
                    $course,
                    $session_id,
                    null,
                    null,
                    0
                );
            }

            // Show exercise results of invisible exercises? see BT#4091
            $exercise_list = ExerciseLib::get_all_exercises(
                $course_info,
                $session_id,
                false,
                null,
                false,
                2
            );

            $to_graph_exercise_result = [];
            if (!empty($exercise_list)) {
                $score = $weighting = $exe_id = 0;
                foreach ($exercise_list as $exercices) {
                    $exercise_obj = new Exercise($course_info['real_id']);
                    $exercise_obj->read($exercices['id']);
                    $visible_return = $exercise_obj->is_visible();
                    $score = $weighting = $attempts = 0;

                    // Getting count of attempts by user
                    $attempts = Event::count_exercise_attempts_by_user(
                        api_get_user_id(),
                        $exercices['id'],
                        $course_info['real_id'],
                        $session_id
                    );

                    $html .= '<tr class="row_even">';
                    $url = api_get_path(WEB_CODE_PATH)."exercise/overview.php?cidReq={$course_info['code']}&id_session=$session_id&exerciseId={$exercices['id']}";

                    if ($visible_return['value'] == true) {
                        $exercices['title'] = Display::url(
                            $exercices['title'],
                            $url,
                            ['target' => SESSION_LINK_TARGET]
                        );
                    } elseif ($exercices['active'] == -1) {
                        $exercices['title'] = sprintf(get_lang('%s (deleted)'), $exercices['title']);
                    }

                    $html .= Display::tag('td', $exercices['title']);

                    // Exercise configuration show results or show only score
                    if ($exercices['results_disabled'] == 0 || $exercices['results_disabled'] == 2) {
                        //For graphics
                        $best_exercise_stats = Event::get_best_exercise_results_by_user(
                            $exercices['id'],
                            $course_info['real_id'],
                            $session_id
                        );

                        $to_graph_exercise_result[$exercices['id']] = [
                            'title' => $exercices['title'],
                            'data' => $best_exercise_stats,
                        ];

                        $latest_attempt_url = '';
                        $best_score = $position = $percentage_score_result = '-';
                        $graph = $normal_graph = null;

                        // Getting best results
                        $best_score_data = ExerciseLib::get_best_attempt_in_course(
                            $exercices['id'],
                            $course_info['real_id'],
                            $session_id
                        );

                        $best_score = '';
                        if (!empty($best_score_data)) {
                            $best_score = ExerciseLib::show_score(
                                $best_score_data['score'],
                                $best_score_data['max_score']
                            );
                        }

                        if ($attempts > 0) {
                            $exercise_stat = ExerciseLib::get_best_attempt_by_user(
                                api_get_user_id(),
                                $exercices['id'],
                                $course_info['real_id'],
                                $session_id
                            );
                            if (!empty($exercise_stat)) {
                                // Always getting the BEST attempt
                                $score = $exercise_stat['score'];
                                $weighting = $exercise_stat['max_score'];
                                $exe_id = $exercise_stat['exe_id'];

                                $latest_attempt_url .= api_get_path(WEB_CODE_PATH).'exercise/result.php?id='.$exe_id.'&cidReq='.$course_info['code'].'&show_headers=1&id_session='.$session_id;
                                $percentage_score_result = Display::url(
                                    ExerciseLib::show_score($score, $weighting),
                                    $latest_attempt_url
                                );
                                $my_score = 0;
                                if (!empty($weighting) && intval($weighting) != 0) {
                                    $my_score = $score / $weighting;
                                }
                                //@todo this function slows the page
                                if (is_int($user_list)) {
                                    $user_list = [$user_list];
                                }
                                $position = ExerciseLib::get_exercise_result_ranking(
                                    $my_score,
                                    $exe_id,
                                    $exercices['id'],
                                    $course_info['code'],
                                    $session_id,
                                    $user_list
                                );

                                $graph = self::generate_exercise_result_thumbnail_graph(
                                    $to_graph_exercise_result[$exercices['id']]
                                );
                                $normal_graph = self::generate_exercise_result_graph(
                                    $to_graph_exercise_result[$exercices['id']]
                                );
                            }
                        }
                        $html .= Display::div(
                            $normal_graph,
                            [
                                'id' => 'main_graph_'.$exercices['id'],
                                'class' => 'dialog',
                                'style' => 'display:none',
                            ]
                        );

                        if (empty($graph)) {
                            $graph = '-';
                        } else {
                            $graph = Display::url(
                                '<img src="'.$graph.'" >',
                                $normal_graph,
                                [
                                    'id' => $exercices['id'],
                                    'class' => 'expand-image',
                                ]
                            );
                        }

                        $html .= Display::tag('td', $attempts);
                        $html .= Display::tag('td', $percentage_score_result);
                        $html .= Display::tag('td', $position);
                        $html .= Display::tag('td', $best_score);
                        $html .= Display::tag('td', $graph);
                    } else {
                        // Exercise configuration NO results
                        $html .= Display::tag('td', $attempts);
                        $html .= Display::tag('td', '-');
                        $html .= Display::tag('td', '-');
                        $html .= Display::tag('td', '-');
                        $html .= Display::tag('td', '-');
                    }
                    $html .= '</tr>';
                }
            } else {
                $html .= '<tr><td colspan="5">'.get_lang('There is no test for the moment').'</td></tr>';
            }
            $html .= '</tbody></table></div>';

            $columnHeaders = [
                'lp' => get_lang('Learning paths'),
                'time' => get_lang('Time spent'),
                'progress' => get_lang('Progress'),
                'score' => get_lang('Score'),
                'best_score' => get_lang('Best score'),
                'last_connection' => get_lang('Latest login'),
            ];

            $headers = '';
            $trackingColumns = api_get_configuration_value('tracking_columns');
            if (isset($trackingColumns['my_progress_lp'])) {
                foreach ($columnHeaders as $key => $value) {
                    if (!isset($trackingColumns['my_progress_lp'][$key]) ||
                        $trackingColumns['my_progress_lp'][$key] == false
                    ) {
                        unset($columnHeaders[$key]);
                    }
                }
            }

            $columnHeadersKeys = array_keys($columnHeaders);
            foreach ($columnHeaders as $key => $columnName) {
                $headers .= Display::tag(
                    'th',
                    $columnName
                );
            }

            // LP table results
            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-striped table-hover">';
            $html .= '<thead><tr>';
            $html .= $headers;
            $html .= '</tr></thead><tbody>';

            $list = new LearnpathList(
                api_get_user_id(),
                $course_info,
                $session_id,
                'lp.publicatedOn ASC',
                true,
                null,
                true
            );

            $lp_list = $list->get_flat_list();

            if (!empty($lp_list)) {
                foreach ($lp_list as $lp_id => $learnpath) {
                    if (!$learnpath['lp_visibility']) {
                        continue;
                    }

                    $progress = self::get_avg_student_progress(
                        $user_id,
                        $course,
                        [$lp_id],
                        $session_id
                    );
                    $last_connection_in_lp = self::get_last_connection_time_in_lp(
                        $user_id,
                        $course,
                        $lp_id,
                        $session_id
                    );

                    $time_spent_in_lp = self::get_time_spent_in_lp(
                        $user_id,
                        $course,
                        [$lp_id],
                        $session_id
                    );
                    $percentage_score = self::get_avg_student_score(
                        $user_id,
                        $course,
                        [$lp_id],
                        $session_id
                    );

                    $bestScore = self::get_avg_student_score(
                        $user_id,
                        $course,
                        [$lp_id],
                        $session_id,
                        false,
                        false,
                        true
                    );

                    if (is_numeric($progress)) {
                        $progress = $progress.'%';
                    }
                    if (is_numeric($percentage_score)) {
                        $percentage_score = $percentage_score.'%';
                    } else {
                        $percentage_score = '0%';
                    }

                    if (is_numeric($bestScore)) {
                        $bestScore = $bestScore.'%';
                    } else {
                        $bestScore = '-';
                    }

                    $time_spent_in_lp = api_time_to_hms($time_spent_in_lp);
                    $last_connection = '-';
                    if (!empty($last_connection_in_lp)) {
                        $last_connection = api_convert_and_format_date(
                            $last_connection_in_lp,
                            DATE_TIME_FORMAT_LONG
                        );
                    }

                    $url = api_get_path(WEB_CODE_PATH)."lp/lp_controller.php?cidReq={$course_code}&id_session=$session_id&lp_id=$lp_id&action=view";
                    $html .= '<tr class="row_even">';

                    if (in_array('lp', $columnHeadersKeys)) {
                        if ($learnpath['lp_visibility'] == 0) {
                            $html .= Display::tag('td', $learnpath['lp_name']);
                        } else {
                            $html .= Display::tag(
                                'td',
                                Display::url(
                                    $learnpath['lp_name'],
                                    $url,
                                    ['target' => SESSION_LINK_TARGET]
                                )
                            );
                        }
                    }

                    if (in_array('time', $columnHeadersKeys)) {
                        $html .= Display::tag(
                            'td',
                            $time_spent_in_lp
                        );
                    }

                    if (in_array('progress', $columnHeadersKeys)) {
                        $html .= Display::tag(
                            'td',
                            $progress
                        );
                    }

                    if (in_array('score', $columnHeadersKeys)) {
                        $html .= Display::tag('td', $percentage_score);
                    }
                    if (in_array('best_score', $columnHeadersKeys)) {
                        $html .= Display::tag('td', $bestScore);
                    }

                    if (in_array('last_connection', $columnHeadersKeys)) {
                        $html .= Display::tag('td', $last_connection, ['width' => '180px']);
                    }
                    $html .= '</tr>';
                }
            } else {
                $html .= '<tr>
                        <td colspan="4" align="center">
                            '.get_lang('No learning path').'
                        </td>
                      </tr>';
            }
            $html .= '</tbody></table></div>';

            $html .= self::displayUserSkills($user_id, $course_info['id'], $session_id);
        }

        return $html;
    }

    /**
     * Generates an histogram.
     *
     * @param array $names      list of exercise names
     * @param array $my_results my results 0 to 100
     * @param array $average    average scores 0-100
     *
     * @return string
     */
    public static function generate_session_exercise_graph($names, $my_results, $average)
    {
        $html = api_get_js('chartjs/Chart.js');
        $canvas = Display::tag('canvas', '', ['id' => 'session_graph_chart']);
        $html .= Display::tag('div', $canvas, ['style' => 'width:100%']);
        $jsStr = " var data = {
                       labels:".json_encode($names).",
                       datasets: [
                       {
                         label: '".get_lang('My results')."',
                         backgroundColor: 'rgb(255, 99, 132)',
                         stack: 'Stack1',
                         data: ".json_encode($my_results).",
                        },
                        {
                         label: '".get_lang('Average score')."',
                         backgroundColor: 'rgb(75, 192, 192)',
                         stack: 'Stack2',
                         data: ".json_encode($average).",
                        },
                        ],  
                    };
                    var ctx = document.getElementById('session_graph_chart').getContext('2d');
                    var myBarChart = new Chart(ctx, {
                    type: 'bar',
                    data: data,
                    options: {
                            title: {
                                    display: true,
                                    text: '".get_lang('TestsInTimeProgressChart')."'
                            },
                            tooltips: {
                                    mode: 'index',
                                    intersect: false
                            },
                            responsive: true,
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        // Include a dollar sign in the ticks
                                        callback: function(value, index, values) {
                                            return value + '%';
                                        }
                                    }
                                }]
                            }
                    }
                });";
        $html .= Display::tag('script', $jsStr);

        return $html;
    }

    /**
     * Returns a thumbnail of the function generate_exercise_result_graph.
     *
     * @param array $attempts
     */
    public static function generate_exercise_result_thumbnail_graph($attempts)
    {
        //$exercise_title = $attempts['title'];
        $attempts = $attempts['data'];
        $my_exercise_result_array = $exercise_result = [];
        if (empty($attempts)) {
            return null;
        }

        foreach ($attempts as $attempt) {
            if (api_get_user_id() == $attempt['exe_user_id']) {
                if ($attempt['max_score'] != 0) {
                    $my_exercise_result_array[] = $attempt['score'] / $attempt['max_score'];
                }
            } else {
                if ($attempt['max_score'] != 0) {
                    $exercise_result[] = $attempt['score'] / $attempt['max_score'];
                }
            }
        }

        // Getting best result
        rsort($my_exercise_result_array);
        $my_exercise_result = 0;
        if (isset($my_exercise_result_array[0])) {
            $my_exercise_result = $my_exercise_result_array[0] * 100;
        }

        $max = 100;
        $pieces = 5;
        $part = round($max / $pieces);
        $x_axis = [];
        $final_array = [];
        $my_final_array = [];

        for ($i = 1; $i <= $pieces; $i++) {
            $sum = 1;
            if ($i == 1) {
                $sum = 0;
            }
            $min = ($i - 1) * $part + $sum;
            $max = ($i) * $part;
            $x_axis[] = $min." - ".$max;
            $count = 0;
            foreach ($exercise_result as $result) {
                $percentage = $result * 100;
                //echo $percentage.' - '.$min.' - '.$max."<br />";
                if ($percentage >= $min && $percentage <= $max) {
                    //echo ' is > ';
                    $count++;
                }
            }
            //echo '<br />';
            $final_array[] = $count;

            if ($my_exercise_result >= $min && $my_exercise_result <= $max) {
                $my_final_array[] = 1;
            } else {
                $my_final_array[] = 0;
            }
        }

        // Fix to remove the data of the user with my data
        for ($i = 0; $i <= count($my_final_array); $i++) {
            if (!empty($my_final_array[$i])) {
                $my_final_array[$i] = $final_array[$i] + 1; //Add my result
                $final_array[$i] = 0;
            }
        }

        // Dataset definition
        $dataSet = new pData();
        $dataSet->addPoints($final_array, 'Serie1');
        $dataSet->addPoints($my_final_array, 'Serie2');
        $dataSet->normalize(100, "%");
        $dataSet->loadPalette(api_get_path(SYS_CODE_PATH).'palettes/pchart/default.color', true);

        // Cache definition
        $cachePath = api_get_path(SYS_ARCHIVE_PATH);
        $myCache = new pCache(['CacheFolder' => substr($cachePath, 0, strlen($cachePath) - 1)]);
        $chartHash = $myCache->getHash($dataSet);
        if ($myCache->isInCache($chartHash)) {
            $imgPath = api_get_path(SYS_ARCHIVE_PATH).$chartHash;
            $myCache->saveFromCache($chartHash, $imgPath);
            $imgPath = api_get_path(WEB_ARCHIVE_PATH).$chartHash;
        } else {
            /* Create the pChart object */
            $widthSize = 80;
            $heightSize = 35;
            $fontSize = 2;

            $myPicture = new pImage($widthSize, $heightSize, $dataSet);

            /* Turn of Antialiasing */
            $myPicture->Antialias = false;

            /* Add a border to the picture */
            $myPicture->drawRectangle(
                0,
                0,
                $widthSize - 1,
                $heightSize - 1,
                ['R' => 0, 'G' => 0, 'B' => 0]
            );

            /* Set the default font */
            $myPicture->setFontProperties(
                [
                    'FontName' => api_get_path(
                            SYS_FONTS_PATH
                        ).'opensans/OpenSans-Regular.ttf',
                    'FontSize' => $fontSize,
                ]
            );

            /* Do not write the chart title */
            /* Define the chart area */
            $myPicture->setGraphArea(5, 5, $widthSize - 5, $heightSize - 5);

            /* Draw the scale */
            $scaleSettings = [
                'GridR' => 200,
                'GridG' => 200,
                'GridB' => 200,
                'DrawSubTicks' => true,
                'CycleBackground' => true,
                'Mode' => SCALE_MODE_MANUAL,
                'ManualScale' => [
                    '0' => [
                        'Min' => 0,
                        'Max' => 100,
                    ],
                ],
            ];
            $myPicture->drawScale($scaleSettings);

            /* Turn on shadow computing */
            $myPicture->setShadow(
                true,
                [
                    'X' => 1,
                    'Y' => 1,
                    'R' => 0,
                    'G' => 0,
                    'B' => 0,
                    'Alpha' => 10,
                ]
            );

            /* Draw the chart */
            $myPicture->setShadow(
                true,
                [
                    'X' => 1,
                    'Y' => 1,
                    'R' => 0,
                    'G' => 0,
                    'B' => 0,
                    'Alpha' => 10,
                ]
            );
            $settings = [
                'DisplayValues' => true,
                'DisplaySize' => $fontSize,
                'DisplayR' => 0,
                'DisplayG' => 0,
                'DisplayB' => 0,
                'DisplayOrientation' => ORIENTATION_HORIZONTAL,
                'Gradient' => false,
                'Surrounding' => 5,
                'InnerSurrounding' => 5,
            ];
            $myPicture->drawStackedBarChart($settings);

            /* Save and write in cache */
            $myCache->writeToCache($chartHash, $myPicture);
            $imgPath = api_get_path(SYS_ARCHIVE_PATH).$chartHash;
            $myCache->saveFromCache($chartHash, $imgPath);
            $imgPath = api_get_path(WEB_ARCHIVE_PATH).$chartHash;
        }

        return $imgPath;
    }

    /**
     * Generates a big graph with the number of best results.
     *
     * @param	array
     */
    public static function generate_exercise_result_graph($attempts)
    {
        $exercise_title = strip_tags($attempts['title']);
        $attempts = $attempts['data'];
        $my_exercise_result_array = $exercise_result = [];
        if (empty($attempts)) {
            return null;
        }
        foreach ($attempts as $attempt) {
            if (api_get_user_id() == $attempt['exe_user_id']) {
                if ($attempt['max_score'] != 0) {
                    $my_exercise_result_array[] = $attempt['score'] / $attempt['max_score'];
                }
            } else {
                if ($attempt['max_score'] != 0) {
                    $exercise_result[] = $attempt['score'] / $attempt['max_score'];
                }
            }
        }

        //Getting best result
        rsort($my_exercise_result_array);
        $my_exercise_result = 0;
        if (isset($my_exercise_result_array[0])) {
            $my_exercise_result = $my_exercise_result_array[0] * 100;
        }

        $max = 100;
        $pieces = 5;
        $part = round($max / $pieces);
        $x_axis = [];
        $final_array = [];
        $my_final_array = [];

        for ($i = 1; $i <= $pieces; $i++) {
            $sum = 1;
            if ($i == 1) {
                $sum = 0;
            }
            $min = ($i - 1) * $part + $sum;
            $max = ($i) * $part;
            $x_axis[] = $min." - ".$max;
            $count = 0;
            foreach ($exercise_result as $result) {
                $percentage = $result * 100;
                if ($percentage >= $min && $percentage <= $max) {
                    $count++;
                }
            }
            $final_array[] = $count;

            if ($my_exercise_result >= $min && $my_exercise_result <= $max) {
                $my_final_array[] = 1;
            } else {
                $my_final_array[] = 0;
            }
        }

        //Fix to remove the data of the user with my data

        for ($i = 0; $i <= count($my_final_array); $i++) {
            if (!empty($my_final_array[$i])) {
                $my_final_array[$i] = $final_array[$i] + 1; //Add my result
                $final_array[$i] = 0;
            }
        }

        // Dataset definition
        $dataSet = new pData();
        $dataSet->addPoints($final_array, 'Serie1');
        $dataSet->addPoints($my_final_array, 'Serie2');
        $dataSet->addPoints($x_axis, 'Serie3');

        $dataSet->setSerieDescription('Serie1', get_lang('Score'));
        $dataSet->setSerieDescription('Serie2', get_lang('My results'));
        $dataSet->setAbscissa('Serie3');

        $dataSet->setXAxisName(get_lang('Score'));
        $dataSet->normalize(100, "%");

        $dataSet->loadPalette(api_get_path(SYS_CODE_PATH).'palettes/pchart/default.color', true);

        // Cache definition
        $cachePath = api_get_path(SYS_ARCHIVE_PATH);
        $myCache = new pCache(['CacheFolder' => substr($cachePath, 0, strlen($cachePath) - 1)]);
        $chartHash = $myCache->getHash($dataSet);

        if ($myCache->isInCache($chartHash)) {
            $imgPath = api_get_path(SYS_ARCHIVE_PATH).$chartHash;
            $myCache->saveFromCache($chartHash, $imgPath);
            $imgPath = api_get_path(WEB_ARCHIVE_PATH).$chartHash;
        } else {
            /* Create the pChart object */
            $widthSize = 480;
            $heightSize = 250;
            $fontSize = 8;
            $myPicture = new pImage($widthSize, $heightSize, $dataSet);

            /* Turn of Antialiasing */
            $myPicture->Antialias = false;

            /* Add a border to the picture */
            $myPicture->drawRectangle(0, 0, $widthSize - 1, $heightSize - 1, ['R' => 0, 'G' => 0, 'B' => 0]);

            /* Set the default font */
            $myPicture->setFontProperties(
                [
                    'FontName' => api_get_path(
                            SYS_FONTS_PATH
                        ).'opensans/OpenSans-Regular.ttf',
                    'FontSize' => 10,
                ]
            );

            /* Write the chart title */
            $myPicture->drawText(
                250,
                20,
                $exercise_title,
                [
                    'FontSize' => 12,
                    'Align' => TEXT_ALIGN_BOTTOMMIDDLE,
                ]
            );

            /* Define the chart area */
            $myPicture->setGraphArea(50, 50, $widthSize - 20, $heightSize - 30);

            /* Draw the scale */
            $scaleSettings = [
                'GridR' => 200,
                'GridG' => 200,
                'GridB' => 200,
                'DrawSubTicks' => true,
                'CycleBackground' => true,
                'Mode' => SCALE_MODE_MANUAL,
                'ManualScale' => [
                    '0' => [
                        'Min' => 0,
                        'Max' => 100,
                    ],
                ],
            ];
            $myPicture->drawScale($scaleSettings);

            /* Turn on shadow computing */
            $myPicture->setShadow(true, ['X' => 1, 'Y' => 1, 'R' => 0, 'G' => 0, 'B' => 0, 'Alpha' => 10]);

            /* Draw the chart */
            $myPicture->setShadow(true, ['X' => 1, 'Y' => 1, 'R' => 0, 'G' => 0, 'B' => 0, 'Alpha' => 10]);
            $settings = [
                'DisplayValues' => true,
                'DisplaySize' => $fontSize,
                'DisplayR' => 0,
                'DisplayG' => 0,
                'DisplayB' => 0,
                'DisplayOrientation' => ORIENTATION_HORIZONTAL,
                'Gradient' => false,
                'Surrounding' => 30,
                'InnerSurrounding' => 25,
            ];
            $myPicture->drawStackedBarChart($settings);

            $legendSettings = [
                'Mode' => LEGEND_HORIZONTAL,
                'Style' => LEGEND_NOBORDER,
            ];
            $myPicture->drawLegend($widthSize / 2, 30, $legendSettings);

            /* Write and save into cache */
            $myCache->writeToCache($chartHash, $myPicture);
            $imgPath = api_get_path(SYS_ARCHIVE_PATH).$chartHash;
            $myCache->saveFromCache($chartHash, $imgPath);
            $imgPath = api_get_path(WEB_ARCHIVE_PATH).$chartHash;
        }

        return $imgPath;
    }

    /**
     * @param FormValidator $form
     *
     * @return mixed
     */
    public static function setUserSearchForm($form)
    {
        global $_configuration;
        $form->addElement('text', 'keyword', get_lang('Keyword'));
        $form->addElement(
            'select',
            'active',
            get_lang('Status'),
            [1 => get_lang('active'), 0 => get_lang('inactive')]
        );

        $form->addElement(
            'select',
            'sleeping_days',
            get_lang('inactiveDays'),
            [
                '',
                1 => 1,
                5 => 5,
                15 => 15,
                30 => 30,
                60 => 60,
                90 => 90,
                120 => 120,
            ]
        );

        $form->addButtonSearch(get_lang('Search'));

        return $form;
    }

    /**
     * Get the progress of a exercise.
     *
     * @param int    $sessionId  The session ID (session.id)
     * @param int    $courseId   The course ID (course.id)
     * @param int    $exerciseId The quiz ID (c_quiz.id)
     * @param string $date_from
     * @param string $date_to
     * @param array  $options    An array of options you can pass to the query (limit, where and order)
     *
     * @return array An array with the data of exercise(s) progress
     */
    public static function get_exercise_progress(
        $sessionId = 0,
        $courseId = 0,
        $exerciseId = 0,
        $date_from = null,
        $date_to = null,
        $options = []
    ) {
        $sessionId = intval($sessionId);
        $courseId = intval($courseId);
        $exerciseId = intval($exerciseId);
        $date_from = Database::escape_string($date_from);
        $date_to = Database::escape_string($date_to);
        /*
         * This method gets the data by blocks, as previous attempts at one single
         * query made it take ages. The logic of query division is described below
         */
        // Get tables names
        $tuser = Database::get_main_table(TABLE_MAIN_USER);
        $tquiz = Database::get_course_table(TABLE_QUIZ_TEST);
        $tquiz_answer = Database::get_course_table(TABLE_QUIZ_ANSWER);
        $tquiz_question = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $tquiz_rel_question = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $ttrack_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $ttrack_attempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        $sessions = [];
        $courses = [];
        // if session ID is defined but course ID is empty, get all the courses
        // from that session
        if (!empty($sessionId) && empty($courseId)) {
            // $courses is an array of course int id as index and course details hash as value
            $courses = SessionManager::get_course_list_by_session_id($sessionId);
            $sessions[$sessionId] = api_get_session_info($sessionId);
        } elseif (empty($sessionId) && !empty($courseId)) {
            // if, to the contrary, course is defined but not sessions, get the sessions that include this course
            // $sessions is an array like: [0] => ('id' => 3, 'name' => 'Session 35'), [1] => () etc;
            $course = api_get_course_info_by_id($courseId);
            $sessionsTemp = SessionManager::get_session_by_course($courseId);
            $courses[$courseId] = $course;
            foreach ($sessionsTemp as $sessionItem) {
                $sessions[$sessionItem['id']] = $sessionItem;
            }
        } elseif (!empty($courseId) && !empty($sessionId)) {
            //none is empty
            $course = api_get_course_info_by_id($courseId);
            $courses[$courseId] = [$course['code']];
            $courses[$courseId]['code'] = $course['code'];
            $sessions[$sessionId] = api_get_session_info($sessionId);
        } else {
            //both are empty, not enough data, return an empty array
            return [];
        }
        // Now we have two arrays of courses and sessions with enough data to proceed
        // If no course could be found, we shouldn't return anything.
        // Course sessions can be empty (then we only return the pure-course-context results)
        if (count($courses) < 1) {
            return [];
        }

        $data = [];
        // The following loop is less expensive than what it seems:
        // - if a course was defined, then we only loop through sessions
        // - if a session was defined, then we only loop through courses
        // - if a session and a course were defined, then we only loop once
        foreach ($courses as $courseIdx => $courseData) {
            $where = '';
            $whereParams = [];
            $whereSessionParams = '';
            if (count($sessions > 0)) {
                foreach ($sessions as $sessionIdx => $sessionData) {
                    if (!empty($sessionIdx)) {
                        $whereSessionParams .= $sessionIdx.',';
                    }
                }
                $whereSessionParams = substr($whereSessionParams, 0, -1);
            }

            if (!empty($exerciseId)) {
                $exerciseId = intval($exerciseId);
                $where .= ' AND q.id = %d ';
                $whereParams[] = $exerciseId;
            }

            /*
             * This feature has been disabled for now, to avoid having to
             * join two very large tables
            //2 = show all questions (wrong and correct answered)
            if ($answer != 2) {
                $answer = intval($answer);
                //$where .= ' AND qa.correct = %d';
                //$whereParams[] = $answer;
            }
            */

            $limit = '';
            if (!empty($options['limit'])) {
                $limit = " LIMIT ".$options['limit'];
            }

            if (!empty($options['where'])) {
                $where .= ' AND '.Database::escape_string($options['where']);
            }

            $order = '';
            if (!empty($options['order'])) {
                $order = " ORDER BY ".$options['order'];
            }

            if (!empty($date_to) && !empty($date_from)) {
                $where .= sprintf(" AND (te.start_date BETWEEN '%s 00:00:00' AND '%s 23:59:59')", $date_from, $date_to);
            }

            $sql = "SELECT
                te.session_id,
                ta.id as attempt_id,
                te.exe_user_id as user_id,
                te.exe_id as exercise_attempt_id,
                ta.question_id,
                ta.answer as answer_id,
                ta.tms as time,
                te.exe_exo_id as quiz_id,
                CONCAT ('c', q.c_id, '_e', q.id) as exercise_id,
                q.title as quiz_title,
                qq.description as description
                FROM $ttrack_exercises te
                INNER JOIN $ttrack_attempt ta 
                ON ta.exe_id = te.exe_id
                INNER JOIN $tquiz q 
                ON q.id = te.exe_exo_id
                INNER JOIN $tquiz_rel_question rq 
                ON rq.exercice_id = q.id AND rq.c_id = q.c_id
                INNER JOIN $tquiz_question qq
                ON
                    qq.id = rq.question_id AND
                    qq.c_id = rq.c_id AND
                    qq.position = rq.question_order AND
                    ta.question_id = rq.question_id
                WHERE
                    te.c_id = $courseIdx ".(empty($whereSessionParams) ? '' : "AND te.session_id IN ($whereSessionParams)")."
                    AND q.c_id = $courseIdx
                    $where $order $limit";
            $sql_query = vsprintf($sql, $whereParams);

            // Now browse through the results and get the data
            $rs = Database::query($sql_query);
            $userIds = [];
            $questionIds = [];
            $answerIds = [];
            while ($row = Database::fetch_array($rs)) {
                //only show if exercise is visible
                if (api_get_item_visibility($courseData, 'quiz', $row['exercise_id'])) {
                    $userIds[$row['user_id']] = $row['user_id'];
                    $questionIds[$row['question_id']] = $row['question_id'];
                    $answerIds[$row['question_id']][$row['answer_id']] = $row['answer_id'];
                    $row['session'] = $sessions[$row['session_id']];
                    $data[] = $row;
                }
            }
            // Now fill questions data. Query all questions and answers for this test to avoid
            $sqlQuestions = "SELECT tq.c_id, tq.id as question_id, tq.question, tqa.id_auto,
                            tqa.answer, tqa.correct, tq.position, tqa.id_auto as answer_id
                            FROM $tquiz_question tq, $tquiz_answer tqa
                            WHERE
                                tqa.question_id = tq.id AND
                                tqa.c_id = tq.c_id AND
                                tq.c_id = $courseIdx AND
                                tq.id IN (".implode(',', $questionIds).")";

            $resQuestions = Database::query($sqlQuestions);
            $answer = [];
            $question = [];
            while ($rowQuestion = Database::fetch_assoc($resQuestions)) {
                $questionId = $rowQuestion['question_id'];
                $answerId = $rowQuestion['answer_id'];
                $answer[$questionId][$answerId] = [
                    'position' => $rowQuestion['position'],
                    'question' => $rowQuestion['question'],
                    'answer' => $rowQuestion['answer'],
                    'correct' => $rowQuestion['correct'],
                ];
                $question[$questionId]['question'] = $rowQuestion['question'];
            }

            // Now fill users data
            $sqlUsers = "SELECT user_id, username, lastname, firstname
                         FROM $tuser
                         WHERE user_id IN (".implode(',', $userIds).")";
            $resUsers = Database::query($sqlUsers);
            while ($rowUser = Database::fetch_assoc($resUsers)) {
                $users[$rowUser['user_id']] = $rowUser;
            }

            foreach ($data as $id => $row) {
                $rowQuestId = $row['question_id'];
                $rowAnsId = $row['answer_id'];
                $data[$id]['session'] = $sessions[$row['session_id']]['name'];
                $data[$id]['firstname'] = $users[$row['user_id']]['firstname'];
                $data[$id]['lastname'] = $users[$row['user_id']]['lastname'];
                $data[$id]['username'] = $users[$row['user_id']]['username'];
                $data[$id]['answer'] = $answer[$rowQuestId][$rowAnsId]['answer'];
                $data[$id]['correct'] = ($answer[$rowQuestId][$rowAnsId]['correct'] == 0 ? get_lang('No') : get_lang('Yes'));
                $data[$id]['question'] = $question[$rowQuestId]['question'];
                $data[$id]['question_id'] = $rowQuestId;
                $data[$id]['description'] = $row['description'];
            }

            /*
            The minimum expected array structure at the end is:
            attempt_id,
            session name,
            exercise_id,
            quiz_title,
            username,
            lastname,
            firstname,
            time,
            question_id,
            question,
            answer,
            */
        }

        return $data;
    }

    /**
     * @param User                $user
     * @param string              $tool
     * @param Course              $course
     * @param sessionEntity |null $session Optional
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return \Chamilo\CourseBundle\Entity\CStudentPublication|null
     */
    public static function getLastStudentPublication(
        User $user,
        $tool,
        Course $course,
        SessionEntity $session = null
    ) {
        return Database::getManager()
            ->createQuery("
                SELECT csp
                FROM ChamiloCourseBundle:CStudentPublication csp
                INNER JOIN ChamiloCourseBundle:CItemProperty cip
                    WITH (
                        csp.iid = cip.ref AND
                        csp.session = cip.session AND
                        csp.cId = cip.course AND
                        csp.userId = cip.lasteditUserId
                    )
                WHERE
                    cip.session = :session AND cip.course = :course AND cip.lasteditUserId = :user AND cip.tool = :tool
                ORDER BY csp.iid DESC
            ")
            ->setMaxResults(1)
            ->setParameters([
                'tool' => $tool,
                'session' => $session,
                'course' => $course,
                'user' => $user,
            ])
            ->getOneOrNullResult();
    }

    /**
     * Get the HTML code for show a block with the achieved user skill on course/session.
     *
     * @param int  $userId
     * @param int  $courseId
     * @param int  $sessionId
     * @param bool $forceView forces the view of the skills, not checking for deeper access
     *
     * @return string
     */
    public static function displayUserSkills($userId, $courseId = 0, $sessionId = 0, $forceView = false)
    {
        if (Skill::isAllowed($userId, false) === false && $forceView == false) {
            return '';
        }
        $skillManager = new Skill();
        $html = $skillManager->getUserSkillsTable($userId, $courseId, $sessionId)['table'];

        return $html;
    }

    /**
     * @param int $userId
     * @param int $courseId
     * @param int $sessionId
     *
     * @return array
     */
    public static function getCalculateTime($userId, $courseId, $sessionId)
    {
        $userId = (int) $userId;
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;

        if (empty($userId) || empty($courseId)) {
            return [];
        }

        $sql = "SELECT MIN(date_reg) min, MAX(date_reg) max
                FROM track_e_access_complete
                WHERE
                    user_id = $userId AND
                    c_id = $courseId AND
                    session_id = $sessionId AND                    
                    login_as = 0
                ORDER BY date_reg ASC
                LIMIT 1";
        $rs = Database::query($sql);

        $firstConnection = '';
        $lastConnection = '';
        if (Database::num_rows($rs) > 0) {
            $value = Database::fetch_array($rs);
            $firstConnection = $value['min'];
            $lastConnection = $value['max'];
        }

        $sql = "SELECT * FROM track_e_access_complete 
                WHERE
                    user_id = $userId AND
                    c_id = $courseId AND                      
                    session_id = $sessionId AND      
                    login_as = 0 AND current_id <> 0";

        $res = Database::query($sql);
        $reg = [];
        while ($row = Database::fetch_assoc($res)) {
            $reg[$row['id']] = $row;
            $reg[$row['id']]['date_reg'] = strtotime($row['date_reg']);
        }

        $sessions = [];
        foreach ($reg as $key => $value) {
            $sessions[$value['current_id']][$value['tool']][] = $value;
        }

        $quizTime = 0;
        $result = [];
        $totalTime = 0;
        $lpTime = [];
        $lpDetailTime = [];
        foreach ($sessions as $listPerTool) {
            $min = 0;
            $max = 0;
            $sessionDiff = 0;
            foreach ($listPerTool as $tool => $results) {
                $beforeItem = [];
                foreach ($results as $item) {
                    if (empty($beforeItem)) {
                        $beforeItem = $item;
                        continue;
                    }

                    $partialTime = $item['date_reg'] - $beforeItem['date_reg'];

                    if ($item['date_reg'] > $max) {
                        $max = $item['date_reg'];
                    }

                    if (empty($min)) {
                        $min = $item['date_reg'];
                    }

                    if ($item['date_reg'] < $min) {
                        $min = $item['date_reg'];
                    }

                    switch ($tool) {
                        case TOOL_AGENDA:
                        case TOOL_FORUM:
                        case TOOL_ANNOUNCEMENT:
                        case TOOL_COURSE_DESCRIPTION:
                        case TOOL_SURVEY:
                        case TOOL_NOTEBOOK:
                        case TOOL_GRADEBOOK:
                        case TOOL_DROPBOX:
                        case 'Reports':
                        case 'Videoconference':
                        case TOOL_LINK:
                        case TOOL_CHAT:
                        case 'course-main':
                            if (!isset($result[$tool])) {
                                $result[$tool] = 0;
                            }
                            $result[$tool] += $partialTime;
                            break;
                        case TOOL_LEARNPATH:
                            if ($item['tool_id'] != $beforeItem['tool_id']) {
                                break;
                            }
                            if (!isset($lpTime[$item['tool_id']])) {
                                $lpTime[$item['tool_id']] = 0;
                            }

                            // Saving the attempt id "action_details"
                            if (!empty($item['tool_id'])) {
                                if (!empty($item['tool_id_detail'])) {
                                    if (!isset($lpDetailTime[$item['tool_id']][$item['tool_id_detail']][$item['action_details']])) {
                                        $lpDetailTime[$item['tool_id']][$item['tool_id_detail']][$item['action_details']] = 0;
                                    }
                                    $lpDetailTime[$item['tool_id']][$item['tool_id_detail']][$item['action_details']] += $partialTime;
                                }
                                $lpTime[$item['tool_id']] += $partialTime;
                            }
                            break;
                        case TOOL_QUIZ:
                            if (!isset($lpTime[$item['action_details']])) {
                                $lpTime[$item['action_details']] = 0;
                            }
                            if ($beforeItem['action'] === 'learnpath_id') {
                                $lpTime[$item['action_details']] += $partialTime;
                            } else {
                                $quizTime += $partialTime;
                            }
                            break;
                    }
                    $beforeItem = $item;
                }
            }

            $sessionDiff += $max - $min;
            if ($sessionDiff > 0) {
                $totalTime += $sessionDiff;
            }
        }

        $totalLp = 0;
        foreach ($lpTime as $value) {
            $totalLp += $value;
        }

        $result['learnpath_detailed'] = $lpDetailTime;
        $result[TOOL_LEARNPATH] = $lpTime;
        $result[TOOL_QUIZ] = $quizTime;
        $result['total_learnpath'] = $totalLp;
        $result['total_time'] = $totalTime;
        $result['number_connections'] = count($sessions);
        $result['first'] = $firstConnection;
        $result['last'] = $lastConnection;

        return $result;
    }

    /**
     * Gets the IP of a given user, using the last login before the given date.
     *
     * @param int User ID
     * @param string Datetime
     * @param bool Whether to return the IP as a link or just as an IP
     * @param string If defined and return_as_link if true, will be used as the text to be shown as the link
     *
     * @return string IP address (or false on error)
     * @assert (0,0) === false
     */
    public static function get_ip_from_user_event(
        $user_id,
        $event_date,
        $return_as_link = false,
        $body_replace = null
    ) {
        if (empty($user_id) || empty($event_date)) {
            return false;
        }
        $user_id = intval($user_id);
        $event_date = Database::escape_string($event_date);
        $table_login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $sql_ip = "SELECT login_date, user_ip 
                   FROM $table_login
                   WHERE login_user_id = $user_id AND login_date < '$event_date'
                   ORDER BY login_date DESC LIMIT 1";
        $ip = '';
        $res_ip = Database::query($sql_ip);
        if ($res_ip !== false && Database::num_rows($res_ip) > 0) {
            $row_ip = Database::fetch_row($res_ip);
            if ($return_as_link) {
                $ip = Display::url(
                    (empty($body_replace) ? $row_ip[1] : $body_replace),
                    'http://www.whatsmyip.org/ip-geo-location/?ip='.$row_ip[1],
                    ['title' => get_lang('Trace IP'), 'target' => '_blank']
                );
            } else {
                $ip = $row_ip[1];
            }
        }

        return $ip;
    }

    /**
     * @param int   $userId
     * @param array $courseInfo
     * @param int   $sessionId
     *
     * @return array
     */
    public static function getToolInformation(
        $userId,
        $courseInfo,
        $sessionId = 0
    ) {
        $csvContent = [];
        $courseToolInformation = '';
        $headerTool = [
            [get_lang('Title')],
            [get_lang('Created at')],
            [get_lang('Updated at')],
        ];

        $headerListForCSV = [];
        foreach ($headerTool as $item) {
            $headerListForCSV[] = $item[0];
        }

        $courseForumInformationArray = getForumCreatedByUser(
            $userId,
            $courseInfo,
            $sessionId
        );

        if (!empty($courseForumInformationArray)) {
            $csvContent[] = [];
            $csvContent[] = [get_lang('Forums')];
            $csvContent[] = $headerListForCSV;
            foreach ($courseForumInformationArray as $row) {
                $csvContent[] = $row;
            }

            $courseToolInformation .= Display::page_subheader2(
                get_lang('Forums')
            );
            $courseToolInformation .= Display::return_sortable_table(
                $headerTool,
                $courseForumInformationArray
            );
        }

        $courseWorkInformationArray = getWorkCreatedByUser(
            $userId,
            $courseInfo['real_id'],
            $sessionId
        );

        if (!empty($courseWorkInformationArray)) {
            $csvContent[] = null;
            $csvContent[] = [get_lang('Assignments')];
            $csvContent[] = $headerListForCSV;

            foreach ($courseWorkInformationArray as $row) {
                $csvContent[] = $row;
            }
            $csvContent[] = null;

            $courseToolInformation .= Display::page_subheader2(
                get_lang('Assignments')
            );
            $courseToolInformation .= Display::return_sortable_table(
                $headerTool,
                $courseWorkInformationArray
            );
        }

        $courseToolInformationTotal = null;
        if (!empty($courseToolInformation)) {
            $sessionTitle = null;
            if (!empty($sessionId)) {
                $sessionTitle = ' ('.api_get_session_name($sessionId).')';
            }

            $courseToolInformationTotal .= Display::page_subheader(
                $courseInfo['title'].$sessionTitle
            );
            $courseToolInformationTotal .= $courseToolInformation;
        }

        return [
            'array' => $csvContent,
            'html' => $courseToolInformationTotal,
        ];
    }

    /**
     * @param int $sessionId
     *
     * @return bool
     */
    public static function isAllowToTrack($sessionId)
    {
        $allow =
            api_is_platform_admin(true, true) ||
            SessionManager::user_is_general_coach(api_get_user_id(), $sessionId) ||
            api_is_allowed_to_create_course() ||
            api_is_course_tutor() ||
            api_is_course_admin();

        return $allow;
    }
}

/**
 * @todo move into a proper file
 *
 * @package chamilo.tracking
 */
class TrackingCourseLog
{
    /**
     * @return mixed
     */
    public static function count_item_resources()
    {
        $session_id = api_get_session_id();
        $course_id = api_get_course_int_id();

        $table_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $table_user = Database::get_main_table(TABLE_MAIN_USER);

        $sql = "SELECT count(tool) AS total_number_of_items
                FROM $table_item_property track_resource, $table_user user
                WHERE
                    track_resource.c_id = $course_id AND
                    track_resource.insert_user_id = user.user_id AND
                    session_id ".(empty($session_id) ? ' IS NULL ' : " = $session_id ");

        if (isset($_GET['keyword'])) {
            $keyword = Database::escape_string(trim($_GET['keyword']));
            $sql .= " AND (
                        user.username LIKE '%".$keyword."%' OR
                        lastedit_type LIKE '%".$keyword."%' OR
                        tool LIKE '%".$keyword."%'
                    )";
        }

        $sql .= " AND tool IN (
                    'document',
                    'learnpath',
                    'quiz',
                    'glossary',
                    'link',
                    'course_description',
                    'announcement',
                    'thematic',
                    'thematic_advance',
                    'thematic_plan'
                )";
        $res = Database::query($sql);
        $obj = Database::fetch_object($res);

        return $obj->total_number_of_items;
    }

    /**
     * @param $from
     * @param $number_of_items
     * @param $column
     * @param $direction
     *
     * @return array
     */
    public static function get_item_resources_data($from, $number_of_items, $column, $direction)
    {
        $session_id = api_get_session_id();
        $course_id = api_get_course_int_id();

        $table_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $table_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $session_id = intval($session_id);

        $sql = "SELECT
                    tool as col0,
                    lastedit_type as col1,
                    ref as ref,
                    user.username as col3,
                    insert_date as col6,
                    visibility as col7,
                    user.user_id as user_id
                FROM $table_item_property track_resource, $table_user user
                WHERE
                  track_resource.c_id = $course_id AND
                  track_resource.insert_user_id = user.user_id AND
                  session_id ".(empty($session_id) ? ' IS NULL ' : " = $session_id ");

        if (isset($_GET['keyword'])) {
            $keyword = Database::escape_string(trim($_GET['keyword']));
            $sql .= " AND (
                        user.username LIKE '%".$keyword."%' OR
                        lastedit_type LIKE '%".$keyword."%' OR
                        tool LIKE '%".$keyword."%'
                     ) ";
        }

        $sql .= " AND tool IN (
                    'document',
                    'learnpath',
                    'quiz',
                    'glossary',
                    'link',
                    'course_description',
                    'announcement',
                    'thematic',
                    'thematic_advance',
                    'thematic_plan'
                )";

        if ($column == 0) {
            $column = '0';
        }
        if ($column != '' && $direction != '') {
            if ($column != 2 && $column != 4) {
                $sql .= " ORDER BY col$column $direction";
            }
        } else {
            $sql .= " ORDER BY col6 DESC ";
        }

        $from = intval($from);
        if ($from) {
            $number_of_items = intval($number_of_items);
            $sql .= " LIMIT $from, $number_of_items ";
        }

        $res = Database::query($sql);
        $resources = [];
        $thematic_tools = ['thematic', 'thematic_advance', 'thematic_plan'];
        while ($row = Database::fetch_array($res)) {
            $ref = $row['ref'];
            $table_name = self::get_tool_name_table($row['col0']);
            $table_tool = Database::get_course_table($table_name['table_name']);

            $id = $table_name['id_tool'];
            $recorset = false;

            if (in_array($row['col0'], ['thematic_plan', 'thematic_advance'])) {
                $tbl_thematic = Database::get_course_table(TABLE_THEMATIC);
                $sql = "SELECT thematic_id FROM $table_tool
                        WHERE c_id = $course_id AND id = $ref";
                $rs_thematic = Database::query($sql);
                if (Database::num_rows($rs_thematic)) {
                    $row_thematic = Database::fetch_array($rs_thematic);
                    $thematic_id = $row_thematic['thematic_id'];

                    $sql = "SELECT session.id, session.name, user.username
                            FROM $tbl_thematic t, $table_session session, $table_user user
                            WHERE
                              t.c_id = $course_id AND
                              t.session_id = session.id AND
                              session.id_coach = user.user_id AND
                              t.id = $thematic_id";
                    $recorset = Database::query($sql);
                }
            } else {
                $sql = "SELECT session.id, session.name, user.username
                          FROM $table_tool tool, $table_session session, $table_user user
                          WHERE
                              tool.c_id = $course_id AND
                              tool.session_id = session.id AND
                              session.id_coach = user.user_id AND
                              tool.$id = $ref";
                $recorset = Database::query($sql);
            }

            if (!empty($recorset)) {
                $obj = Database::fetch_object($recorset);

                $name_session = '';
                $coach_name = '';
                if (!empty($obj)) {
                    $name_session = $obj->name;
                    $coach_name = $obj->username;
                }

                $url_tool = api_get_path(WEB_CODE_PATH).$table_name['link_tool'];
                $row[0] = '';
                if ($row['col6'] != 2) {
                    if (in_array($row['col0'], $thematic_tools)) {
                        $exp_thematic_tool = explode('_', $row['col0']);
                        $thematic_tool_title = '';
                        if (is_array($exp_thematic_tool)) {
                            foreach ($exp_thematic_tool as $exp) {
                                $thematic_tool_title .= api_ucfirst($exp);
                            }
                        } else {
                            $thematic_tool_title = api_ucfirst($row['col0']);
                        }

                        $row[0] = '<a href="'.$url_tool.'?'.api_get_cidreq().'&action=thematic_details">'.get_lang($thematic_tool_title).'</a>';
                    } else {
                        $row[0] = '<a href="'.$url_tool.'?'.api_get_cidreq().'">'.get_lang('Tool'.api_ucfirst($row['col0'])).'</a>';
                    }
                } else {
                    $row[0] = api_ucfirst($row['col0']);
                }
                $row[1] = get_lang($row[1]);
                $row[6] = api_convert_and_format_date($row['col6'], null, date_default_timezone_get());
                $row[5] = '';
                //@todo Improve this code please
                switch ($table_name['table_name']) {
                    case 'document':
                        $sql = "SELECT tool.title as title FROM $table_tool tool
                                WHERE c_id = $course_id AND id = $ref";
                        $rs_document = Database::query($sql);
                        $obj_document = Database::fetch_object($rs_document);
                        if ($obj_document) {
                            $row[5] = $obj_document->title;
                        }
                        break;
                    case 'announcement':
                        $sql = "SELECT title FROM $table_tool
                                WHERE c_id = $course_id AND id = $ref";
                        $rs_document = Database::query($sql);
                        $obj_document = Database::fetch_object($rs_document);
                        if ($obj_document) {
                            $row[5] = $obj_document->title;
                        }
                        break;
                    case 'glossary':
                        $sql = "SELECT name FROM $table_tool
                                WHERE c_id = $course_id AND glossary_id = $ref";
                        $rs_document = Database::query($sql);
                        $obj_document = Database::fetch_object($rs_document);
                        if ($obj_document) {
                            $row[5] = $obj_document->name;
                        }
                        break;
                    case 'lp':
                        $sql = "SELECT name
                                FROM $table_tool WHERE c_id = $course_id AND id = $ref";
                        $rs_document = Database::query($sql);
                        $obj_document = Database::fetch_object($rs_document);
                        $row[5] = $obj_document->name;
                        break;
                    case 'quiz':
                        $sql = "SELECT title FROM $table_tool
                                WHERE c_id = $course_id AND id = $ref";
                        $rs_document = Database::query($sql);
                        $obj_document = Database::fetch_object($rs_document);
                        if ($obj_document) {
                            $row[5] = $obj_document->title;
                        }
                        break;
                    case 'course_description':
                        $sql = "SELECT title FROM $table_tool
                                WHERE c_id = $course_id AND id = $ref";
                        $rs_document = Database::query($sql);
                        $obj_document = Database::fetch_object($rs_document);
                        if ($obj_document) {
                            $row[5] = $obj_document->title;
                        }
                        break;
                    case 'thematic':
                        $rs = Database::query("SELECT title FROM $table_tool WHERE c_id = $course_id AND id = $ref");
                        if (Database::num_rows($rs) > 0) {
                            $obj = Database::fetch_object($rs);
                            if ($obj) {
                                $row[5] = $obj->title;
                            }
                        }
                        break;
                    case 'thematic_advance':
                        $rs = Database::query("SELECT content FROM $table_tool WHERE c_id = $course_id AND id = $ref");
                        if (Database::num_rows($rs) > 0) {
                            $obj = Database::fetch_object($rs);
                            if ($obj) {
                                $row[5] = $obj->content;
                            }
                        }
                        break;
                    case 'thematic_plan':
                        $rs = Database::query("SELECT title FROM $table_tool WHERE c_id = $course_id AND id = $ref");
                        if (Database::num_rows($rs) > 0) {
                            $obj = Database::fetch_object($rs);
                            if ($obj) {
                                $row[5] = $obj->title;
                            }
                        }
                        break;
                    default:
                        break;
                }

                $row2 = $name_session;
                if (!empty($coach_name)) {
                    $row2 .= '<br />'.get_lang('Coach').': '.$coach_name;
                }
                $row[2] = $row2;
                if (!empty($row['col3'])) {
                    $userInfo = api_get_user_info($row['user_id']);
                    $row['col3'] = Display::url(
                        $row['col3'],
                        $userInfo['profile_url']
                    );
                    $row[3] = $row['col3'];

                    $ip = Tracking::get_ip_from_user_event(
                        $row['user_id'],
                        $row['col6'],
                        true
                    );
                    if (empty($ip)) {
                        $ip = get_lang('Unknown');
                    }
                    $row[4] = $ip;
                }

                $resources[] = $row;
            }
        }

        return $resources;
    }

    /**
     * @param string $tool
     *
     * @return array
     */
    public static function get_tool_name_table($tool)
    {
        switch ($tool) {
            case 'document':
                $table_name = TABLE_DOCUMENT;
                $link_tool = 'document/document.php';
                $id_tool = 'id';
                break;
            case 'learnpath':
                $table_name = TABLE_LP_MAIN;
                $link_tool = 'lp/lp_controller.php';
                $id_tool = 'id';
                break;
            case 'quiz':
                $table_name = TABLE_QUIZ_TEST;
                $link_tool = 'exercise/exercise.php';
                $id_tool = 'id';
                break;
            case 'glossary':
                $table_name = TABLE_GLOSSARY;
                $link_tool = 'glossary/index.php';
                $id_tool = 'glossary_id';
                break;
            case 'link':
                $table_name = TABLE_LINK;
                $link_tool = 'link/link.php';
                $id_tool = 'id';
                break;
            case 'course_description':
                $table_name = TABLE_COURSE_DESCRIPTION;
                $link_tool = 'course_description/';
                $id_tool = 'id';
                break;
            case 'announcement':
                $table_name = TABLE_ANNOUNCEMENT;
                $link_tool = 'announcements/announcements.php';
                $id_tool = 'id';
                break;
            case 'thematic':
                $table_name = TABLE_THEMATIC;
                $link_tool = 'course_progress/index.php';
                $id_tool = 'id';
                break;
            case 'thematic_advance':
                $table_name = TABLE_THEMATIC_ADVANCE;
                $link_tool = 'course_progress/index.php';
                $id_tool = 'id';
                break;
            case 'thematic_plan':
                $table_name = TABLE_THEMATIC_PLAN;
                $link_tool = 'course_progress/index.php';
                $id_tool = 'id';
                break;
            default:
                $table_name = $tool;
            break;
        }

        return [
            'table_name' => $table_name,
            'link_tool' => $link_tool,
            'id_tool' => $id_tool,
        ];
    }

    /**
     * @return string
     */
    public static function display_additional_profile_fields()
    {
        // getting all the extra profile fields that are defined by the platform administrator
        $extra_fields = UserManager::get_extra_fields(0, 50, 5, 'ASC');

        // creating the form
        $return = '<form action="courseLog.php" method="get" name="additional_profile_field_form" id="additional_profile_field_form">';

        // the select field with the additional user profile fields (= this is where we select the field of which we want to see
        // the information the users have entered or selected.
        $return .= '<select class="chzn-select" name="additional_profile_field[]" multiple>';
        $return .= '<option value="-">'.get_lang('Select user profile field to add').'</option>';
        $extra_fields_to_show = 0;
        foreach ($extra_fields as $key => $field) {
            // show only extra fields that are visible + and can be filtered, added by J.Montoya
            if ($field[6] == 1 && $field[8] == 1) {
                if (isset($_GET['additional_profile_field']) && $field[0] == $_GET['additional_profile_field']) {
                    $selected = 'selected="selected"';
                } else {
                    $selected = '';
                }
                $extra_fields_to_show++;
                $return .= '<option value="'.$field[0].'" '.$selected.'>'.$field[3].'</option>';
            }
        }
        $return .= '</select>';

        // the form elements for the $_GET parameters (because the form is passed through GET
        foreach ($_GET as $key => $value) {
            if ($key != 'additional_profile_field') {
                $return .= '<input type="hidden" name="'.Security::remove_XSS($key).'" value="'.Security::remove_XSS($value).'" />';
            }
        }
        // the submit button
        $return .= '<button class="save" type="submit">'.get_lang('Add user profile field').'</button>';
        $return .= '</form>';
        if ($extra_fields_to_show > 0) {
            return $return;
        } else {
            return '';
        }
    }

    /**
     * This function gets all the information of a certrain ($field_id)
     * additional profile field for a specific list of users is more efficent
     * than get_addtional_profile_information_of_field() function
     * It gets the information of all the users so that it can be displayed
     * in the sortable table or in the csv or xls export.
     *
     * @author    Julio Montoya <gugli100@gmail.com>
     *
     * @param    int field id
     * @param    array list of user ids
     *
     * @return array
     *
     * @since    Nov 2009
     *
     * @version    1.8.6.2
     */
    public static function getAdditionalProfileInformationOfFieldByUser($field_id, $users)
    {
        // Database table definition
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $table_user_field_values = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $extraField = Database::get_main_table(TABLE_EXTRA_FIELD);
        $result_extra_field = UserManager::get_extra_field_information($field_id);
        $return = [];
        if (!empty($users)) {
            if ($result_extra_field['field_type'] == UserManager::USER_FIELD_TYPE_TAG) {
                foreach ($users as $user_id) {
                    $user_result = UserManager::get_user_tags($user_id, $field_id);
                    $tag_list = [];
                    foreach ($user_result as $item) {
                        $tag_list[] = $item['tag'];
                    }
                    $return[$user_id][] = implode(', ', $tag_list);
                }
            } else {
                $new_user_array = [];
                foreach ($users as $user_id) {
                    $new_user_array[] = "'".$user_id."'";
                }
                $users = implode(',', $new_user_array);
                $extraFieldType = EntityExtraField::USER_FIELD_TYPE;
                // Selecting only the necessary information NOT ALL the user list
                $sql = "SELECT user.user_id, v.value
                        FROM $table_user user
                        INNER JOIN $table_user_field_values v
                        ON (user.user_id = v.item_id)
                        INNER JOIN $extraField f
                        ON (f.id = v.field_id)
                        WHERE
                            f.extra_field_type = $extraFieldType AND
                            v.field_id=".intval($field_id)." AND
                            user.user_id IN ($users)";

                $result = Database::query($sql);
                while ($row = Database::fetch_array($result)) {
                    // get option value for field type double select by id
                    if (!empty($row['value'])) {
                        if ($result_extra_field['field_type'] ==
                            ExtraField::FIELD_TYPE_DOUBLE_SELECT
                        ) {
                            $id_double_select = explode(';', $row['value']);
                            if (is_array($id_double_select)) {
                                $value1 = $result_extra_field['options'][$id_double_select[0]]['option_value'];
                                $value2 = $result_extra_field['options'][$id_double_select[1]]['option_value'];
                                $row['value'] = ($value1.';'.$value2);
                            }
                        }

                        if ($result_extra_field['field_type'] == ExtraField::FIELD_TYPE_SELECT_WITH_TEXT_FIELD) {
                            $parsedValue = explode('::', $row['value']);

                            if ($parsedValue) {
                                $value1 = $result_extra_field['options'][$parsedValue[0]]['display_text'];
                                $value2 = $parsedValue[1];

                                $row['value'] = "$value1: $value2";
                            }
                        }

                        if ($result_extra_field['field_type'] == ExtraField::FIELD_TYPE_TRIPLE_SELECT) {
                            list($level1, $level2, $level3) = explode(';', $row['value']);

                            $row['value'] = $result_extra_field['options'][$level1]['display_text'].' / ';
                            $row['value'] .= $result_extra_field['options'][$level2]['display_text'].' / ';
                            $row['value'] .= $result_extra_field['options'][$level3]['display_text'];
                        }
                    }
                    // get other value from extra field
                    $return[$row['user_id']][] = $row['value'];
                }
            }
        }

        return $return;
    }

    /**
     * count the number of students in this course (used for SortableTable)
     * Deprecated.
     */
    public function count_student_in_course()
    {
        global $nbStudents;

        return $nbStudents;
    }

    public function sort_users($a, $b)
    {
        $tracking = Session::read('tracking_column');

        return strcmp(
            trim(api_strtolower($a[$tracking])),
            trim(api_strtolower($b[$tracking]))
        );
    }

    public function sort_users_desc($a, $b)
    {
        $tracking = Session::read('tracking_column');

        return strcmp(
            trim(api_strtolower($b[$tracking])),
            trim(api_strtolower($a[$tracking]))
        );
    }

    /**
     * Get number of users for sortable with pagination.
     *
     * @return int
     */
    public static function get_number_of_users()
    {
        global $user_ids;

        return count($user_ids);
    }

    /**
     * Get data for users list in sortable with pagination.
     *
     * @param $from
     * @param $number_of_items
     * @param $column
     * @param $direction
     * @param $includeInvitedUsers boolean Whether include the invited users
     *
     * @return array
     */
    public static function get_user_data(
        $from,
        $number_of_items,
        $column,
        $direction,
        $includeInvitedUsers = false
    ) {
        global $user_ids, $course_code, $export_csv, $session_id;

        $csv_content = [];
        $course_code = Database::escape_string($course_code);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $access_url_id = api_get_current_access_url_id();

        // get all users data from a course for sortable with limit
        if (is_array($user_ids)) {
            $user_ids = array_map('intval', $user_ids);
            $condition_user = " WHERE user.user_id IN (".implode(',', $user_ids).") ";
        } else {
            $user_ids = (int) $user_ids;
            $condition_user = " WHERE user.user_id = $user_ids ";
        }

        if (!empty($_GET['user_keyword'])) {
            $keyword = trim(Database::escape_string($_GET['user_keyword']));
            $condition_user .= " AND (
                user.firstname LIKE '%".$keyword."%' OR
                user.lastname LIKE '%".$keyword."%'  OR
                user.username LIKE '%".$keyword."%'  OR
                user.email LIKE '%".$keyword."%'
             ) ";
        }

        $url_table = null;
        $url_condition = null;
        if (api_is_multiple_url_enabled()) {
            $url_table = ", $tbl_url_rel_user as url_users";
            $url_condition = " AND user.user_id = url_users.user_id AND access_url_id = '$access_url_id'";
        }

        $invitedUsersCondition = '';
        if (!$includeInvitedUsers) {
            $invitedUsersCondition = " AND user.status != ".INVITEE;
        }

        $sql = "SELECT  user.user_id as user_id,
                    user.official_code  as col0,
                    user.lastname       as col1,
                    user.firstname      as col2,
                    user.username       as col3
                FROM $tbl_user as user $url_table
                $condition_user $url_condition $invitedUsersCondition";

        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }

        $column = (int) $column;
        $from = (int) $from;
        $number_of_items = (int) $number_of_items;

        $sql .= " ORDER BY col$column $direction ";
        $sql .= " LIMIT $from, $number_of_items";

        $res = Database::query($sql);
        $users = [];

        $course_info = api_get_course_info($course_code);
        $total_surveys = 0;
        $total_exercises = ExerciseLib::get_all_exercises(
            $course_info,
            $session_id,
            false,
            null,
            false,
            3
        );

        if (empty($session_id)) {
            $survey_user_list = [];
            $survey_list = SurveyManager::get_surveys($course_code, $session_id);

            $total_surveys = count($survey_list);
            foreach ($survey_list as $survey) {
                $user_list = SurveyManager::get_people_who_filled_survey(
                    $survey['survey_id'],
                    false,
                    $course_info['real_id']
                );

                foreach ($user_list as $user_id) {
                    isset($survey_user_list[$user_id]) ? $survey_user_list[$user_id]++ : $survey_user_list[$user_id] = 1;
                }
            }
        }

        $courseInfo = api_get_course_info($course_code);
        $courseId = $courseInfo['real_id'];

        $urlBase = api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?details=true&cidReq='.$course_code.
            '&course='.$course_code.'&origin=tracking_course&id_session='.$session_id;

        $sortByFirstName = api_sort_by_first_name();
        while ($user = Database::fetch_array($res, 'ASSOC')) {
            $user['official_code'] = $user['col0'];
            $user['username'] = $user['col3'];

            $user['time'] = api_time_to_hms(
                Tracking::get_time_spent_on_the_course(
                    $user['user_id'],
                    $courseId,
                    $session_id
                )
            );

            $avg_student_score = Tracking::get_avg_student_score(
                $user['user_id'],
                $course_code,
                [],
                $session_id
            );

            $avg_student_progress = Tracking::get_avg_student_progress(
                $user['user_id'],
                $course_code,
                [],
                $session_id
            );

            if (empty($avg_student_progress)) {
                $avg_student_progress = 0;
            }
            $user['average_progress'] = $avg_student_progress.'%';

            $total_user_exercise = Tracking::get_exercise_student_progress(
                $total_exercises,
                $user['user_id'],
                $courseId,
                $session_id
            );

            $user['exercise_progress'] = $total_user_exercise;

            $total_user_exercise = Tracking::get_exercise_student_average_best_attempt(
                $total_exercises,
                $user['user_id'],
                $courseId,
                $session_id
            );

            $user['exercise_average_best_attempt'] = $total_user_exercise;

            if (is_numeric($avg_student_score)) {
                $user['student_score'] = $avg_student_score.'%';
            } else {
                $user['student_score'] = $avg_student_score;
            }

            $user['count_assignments'] = Tracking::count_student_assignments(
                $user['user_id'],
                $course_code,
                $session_id
            );
            $user['count_messages'] = Tracking::count_student_messages(
                $user['user_id'],
                $course_code,
                $session_id
            );
            $user['first_connection'] = Tracking::get_first_connection_date_on_the_course(
                $user['user_id'],
                $courseId,
                $session_id,
                $export_csv === false
            );

            $user['last_connection'] = Tracking::get_last_connection_date_on_the_course(
                $user['user_id'],
                $courseInfo,
                $session_id,
                $export_csv === false
            );

            if ($export_csv) {
                $user['first_connection'] = api_get_local_time($user['first_connection']);
                $user['last_connection'] = api_get_local_time($user['last_connection']);
            }

            if (empty($session_id)) {
                $user['survey'] = (isset($survey_user_list[$user['user_id']]) ? $survey_user_list[$user['user_id']] : 0).' / '.$total_surveys;
            }

            $url = $urlBase.'&student='.$user['user_id'];

            $user['link'] = '<center><a href="'.$url.'">
                            '.Display::return_icon('2rightarrow.png', get_lang('Details')).'
                             </a></center>';

            // store columns in array $users
            $user_row = [];
            $user_row['official_code'] = $user['official_code']; //0
            if ($sortByFirstName) {
                $user_row['firstname'] = $user['col2'];
                $user_row['lastname'] = $user['col1'];
            } else {
                $user_row['lastname'] = $user['col1'];
                $user_row['firstname'] = $user['col2'];
            }
            $user_row['username'] = $user['username'];
            $user_row['time'] = $user['time'];
            $user_row['average_progress'] = $user['average_progress'];
            $user_row['exercise_progress'] = $user['exercise_progress'];
            $user_row['exercise_average_best_attempt'] = $user['exercise_average_best_attempt'];
            $user_row['student_score'] = $user['student_score'];
            $user_row['count_assignments'] = $user['count_assignments'];
            $user_row['count_messages'] = $user['count_messages'];

            $userGroupManager = new UserGroup();
            $user_row['classes'] = $userGroupManager->getLabelsFromNameList($user['user_id'], UserGroup::NORMAL_CLASS);

            if (empty($session_id)) {
                $user_row['survey'] = $user['survey'];
            }

            $user_row['first_connection'] = $user['first_connection'];
            $user_row['last_connection'] = $user['last_connection'];

            // we need to display an additional profile field
            if (isset($_GET['additional_profile_field'])) {
                $data = Session::read('additional_user_profile_info');
                $extraFieldInfo = Session::read('extra_field_info');
                foreach ($_GET['additional_profile_field'] as $fieldId) {
                    if (isset($data[$fieldId]) && isset($data[$fieldId][$user['user_id']])) {
                        if (is_array($data[$fieldId][$user['user_id']])) {
                            $user_row[$extraFieldInfo[$fieldId]['variable']] = implode(
                                ', ',
                                $data[$fieldId][$user['user_id']]
                            );
                        } else {
                            $user_row[$extraFieldInfo[$fieldId]['variable']] = $data[$fieldId][$user['user_id']];
                        }
                    } else {
                        $user_row[$extraFieldInfo[$fieldId]['variable']] = '';
                    }
                }
            }

            $user_row['link'] = $user['link'];

            if ($export_csv) {
                if (empty($session_id)) {
                    unset($user_row['classes']);
                    unset($user_row['link']);
                } else {
                    unset($user_row['classes']);
                    unset($user_row['link']);
                }

                $csv_content[] = $user_row;
            }
            $users[] = array_values($user_row);
        }

        if ($export_csv) {
            Session::write('csv_content', $csv_content);
        }

        Session::erase('additional_user_profile_info');
        Session::erase('extra_field_info');

        return $users;
    }

    /**
     * Get data for users list in sortable with pagination.
     *
     * @param $from
     * @param $number_of_items
     * @param $column
     * @param $direction
     * @param $includeInvitedUsers boolean Whether include the invited users
     *
     * @return array
     */
    public static function getTotalTimeReport(
        $from,
        $number_of_items,
        $column,
        $direction,
        $includeInvitedUsers = false
    ) {
        global $user_ids, $course_code, $export_csv, $csv_content, $session_id;

        $course_code = Database::escape_string($course_code);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $access_url_id = api_get_current_access_url_id();

        // get all users data from a course for sortable with limit
        if (is_array($user_ids)) {
            $user_ids = array_map('intval', $user_ids);
            $condition_user = " WHERE user.user_id IN (".implode(',', $user_ids).") ";
        } else {
            $user_ids = intval($user_ids);
            $condition_user = " WHERE user.user_id = $user_ids ";
        }

        $url_table = null;
        $url_condition = null;
        if (api_is_multiple_url_enabled()) {
            $url_table = ", ".$tbl_url_rel_user." as url_users";
            $url_condition = " AND user.user_id = url_users.user_id AND access_url_id='$access_url_id'";
        }

        $invitedUsersCondition = '';
        if (!$includeInvitedUsers) {
            $invitedUsersCondition = " AND user.status != ".INVITEE;
        }

        $sql = "SELECT  user.user_id as user_id,
                    user.official_code  as col0,
                    user.lastname       as col1,
                    user.firstname      as col2,
                    user.username       as col3
                FROM $tbl_user as user $url_table
                $condition_user $url_condition $invitedUsersCondition";

        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }

        $column = intval($column);
        $from = intval($from);
        $number_of_items = intval($number_of_items);

        $sql .= " ORDER BY col$column $direction ";
        $sql .= " LIMIT $from,$number_of_items";

        $res = Database::query($sql);
        $users = [];

        $course_info = api_get_course_info($course_code);
        $sortByFirstName = api_sort_by_first_name();
        while ($user = Database::fetch_array($res, 'ASSOC')) {
            $courseInfo = api_get_course_info($course_code);
            $courseId = $courseInfo['real_id'];

            $user['official_code'] = $user['col0'];
            $user['lastname'] = $user['col1'];
            $user['firstname'] = $user['col2'];
            $user['username'] = $user['col3'];

            $totalCourseTime = Tracking::get_time_spent_on_the_course(
                $user['user_id'],
                $courseId,
                $session_id
            );

            $user['time'] = api_time_to_hms($totalCourseTime);
            $totalLpTime = Tracking::get_time_spent_in_lp(
                $user['user_id'],
                $course_code,
                [],
                $session_id
            );

            $user['total_lp_time'] = $totalLpTime;
            $warning = '';
            if ($totalLpTime > $totalCourseTime) {
                $warning = '&nbsp;'.Display::label(get_lang('Time difference'), 'danger');
            }

            $user['total_lp_time'] = api_time_to_hms($totalLpTime).$warning;

            $user['first_connection'] = Tracking::get_first_connection_date_on_the_course(
                $user['user_id'],
                $courseId,
                $session_id
            );
            $user['last_connection'] = Tracking::get_last_connection_date_on_the_course(
                $user['user_id'],
                $courseInfo,
                $session_id,
                $export_csv === false
            );

            $user['link'] = '<center>
                             <a href="../mySpace/myStudents.php?student='.$user['user_id'].'&details=true&course='.$course_code.'&origin=tracking_course&id_session='.$session_id.'">
                             '.Display::return_icon('2rightarrow.png', get_lang('Details')).'
                             </a>
                         </center>';

            // store columns in array $users
            $user_row = [];
            $user_row['official_code'] = $user['official_code']; //0
            if ($sortByFirstName) {
                $user_row['firstname'] = $user['firstname'];
                $user_row['lastname'] = $user['lastname'];
            } else {
                $user_row['lastname'] = $user['lastname'];
                $user_row['firstname'] = $user['firstname'];
            }
            $user_row['username'] = $user['username'];
            $user_row['time'] = $user['time'];
            $user_row['total_lp_time'] = $user['total_lp_time'];
            $user_row['first_connection'] = $user['first_connection'];
            $user_row['last_connection'] = $user['last_connection'];

            $user_row['link'] = $user['link'];
            $users[] = array_values($user_row);
        }

        return $users;
    }

    /**
     * @param string $current
     */
    public static function actionsLeft($current, $sessionId = 0)
    {
        $usersLink = Display::url(
            Display::return_icon('user.png', get_lang('Report on learners'), [], ICON_SIZE_MEDIUM),
            'courseLog.php?'.api_get_cidreq(true, false)
        );

        $groupsLink = Display::url(
            Display::return_icon('group.png', get_lang('Group reporting'), [], ICON_SIZE_MEDIUM),
            'course_log_groups.php?'.api_get_cidreq()
        );

        $resourcesLink = Display::url(
            Display::return_icon('tools.png', get_lang('Report on resource'), [], ICON_SIZE_MEDIUM),
            'course_log_resources.php?'.api_get_cidreq(true, false)
        );

        $courseLink = Display::url(
            Display::return_icon('course.png', get_lang('Course report'), [], ICON_SIZE_MEDIUM),
            'course_log_tools.php?'.api_get_cidreq(true, false)
        );

        $examLink = Display::url(
            Display::return_icon('quiz.png', get_lang('Exam tracking'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'tracking/exams.php?'.api_get_cidreq()
        );

        $eventsLink = Display::url(
            Display::return_icon('security.png', get_lang('Audit report'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'tracking/course_log_events.php?'.api_get_cidreq()
        );

        $attendanceLink = '';
        if (!empty($sessionId)) {
            $attendanceLink = Display::url(
                Display::return_icon('attendance_list.png', get_lang('Logins'), '', ICON_SIZE_MEDIUM),
                api_get_path(WEB_CODE_PATH).'attendance/index.php?'.api_get_cidreq().'&action=calendar_logins'
            );
        }

        switch ($current) {
            case 'users':
                $usersLink = Display::url(
                        Display::return_icon(
                        'user_na.png',
                        get_lang('Report on learners'),
                        [],
                        ICON_SIZE_MEDIUM
                    ),
                    '#'
                );
                break;
            case 'groups':
                $groupsLink = Display::url(
                    Display::return_icon('group_na.png', get_lang('Group reporting'), [], ICON_SIZE_MEDIUM),
                    '#'
                );
                break;
            case 'courses':
                $courseLink = Display::url(
                    Display::return_icon('course_na.png', get_lang('Course report'), [], ICON_SIZE_MEDIUM),
                    '#'
                );
                break;
            case 'resources':
                $resourcesLink = Display::url(
                    Display::return_icon(
                    'tools_na.png',
                    get_lang('Report on resource'),
                    [],
                    ICON_SIZE_MEDIUM
                    ),
                    '#'
                );
                break;
            case 'exams':
                $examLink = Display::url(
                    Display::return_icon('quiz_na.png', get_lang('Exam tracking'), [], ICON_SIZE_MEDIUM),
                    '#'
                );
                break;
            case 'logs':
                $eventsLink = Display::url(
                    Display::return_icon('security_na.png', get_lang('Audit report'), [], ICON_SIZE_MEDIUM),
                    '#'
                );
                break;
            case 'attendance':
                if (!empty($sessionId)) {
                    $attendanceLink = Display::url(
                        Display::return_icon('attendance_list.png', get_lang('Logins'), '', ICON_SIZE_MEDIUM),
                        '#'
                    );
                }
                break;
        }

        $items = [
            $usersLink,
            $groupsLink,
            $courseLink,
            $resourcesLink,
            $examLink,
            $eventsLink,
            $attendanceLink,
        ];

        return implode('', $items).'&nbsp;';
    }
}

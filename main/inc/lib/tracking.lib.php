<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField as EntityExtraField;
use CpChart\Chart\Cache as pCache;
use CpChart\Chart\Data as pData;
use CpChart\Chart\Image as pImage;
use Chamilo\UserBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;

/**
 *  Class Tracking
 *
 *  @author  Julio Montoya <gugli100@gmail.com>
 *  @package chamilo.library
 */
class Tracking
{
    /**
     * Get group reporting
     * @param int $course_id
     * @param int $sessionId
     * @param int $group_id
     * @param string $type
     * @param int $start
     * @param int $limit
     * @param int $sidx
     * @param string $sord
     * @param array $where_condition
     * @return array|null
     */
    public static function get_group_reporting(
        $course_id,
        $sessionId = null,
        $group_id = null,
        $type = 'all',
        $start = 0,
        $limit = 1000,
        $sidx = 1,
        $sord = 'desc',
        $where_condition = array()
    ) {
        if (empty($course_id)) {
            return null;
        }
        $course_info = api_get_course_info_by_id($course_id);
        $sessionId = (int) $sessionId;
        $table_group = Database::get_course_table(TABLE_GROUP);
        $course_id = intval($course_id);

        $select = ' * ';
        if ($type == 'count') {
            $select = ' count(id) as count ';
        }

        if (empty($sessionId)) {
            $default_where = array('c_id = ? AND (session_id = 0 or session_id IS NULL)' => array($course_id));
        } else {
            $default_where = array('c_id = ? AND session_id = ? ' => array($course_id, $sessionId));
        }

        $result = Database::select(
            $select,
            $table_group,
            array(
                'limit' => " $start, $limit",
                'where' => $default_where,
                'order' => "$sidx $sord",
            )
        );

        if ($type == 'count') {
            return $result[0]['count'];
        }

        $parsed_result = array();
        if (!empty($result)) {
            foreach ($result as $group) {
                $users = GroupManager::get_users($group['id'], true);
                $time = 0;
                $avg_student_score = 0;
                $avg_student_progress = 0;
                $work = 0;
                $messages = 0;

                foreach ($users as $user_data) {
                    $time += self::get_time_spent_on_the_course($user_data['user_id'], $course_info['code'], $sessionId);
                    $avg_student_score += self::get_avg_student_score($user_data['user_id'], $course_info['code'], array(), $sessionId);
                    $avg_student_progress += self::get_avg_student_progress($user_data['user_id'], $course_info['code'], array(), $sessionId);
                    $work += self::count_student_assignments($user_data['user_id'], $course_info['code'], $sessionId);
                    $messages += self::count_student_messages($user_data['user_id'], $course_info['code'], $sessionId);
                }

                $countUsers = count($users);
                $averageProgress = empty($countUsers) ? 0 : $avg_student_progress / $countUsers;
                $averageScore = empty($countUsers) ? 0 : $avg_student_score / $countUsers;

                $group_item = array(
                    'id' => $group['id'],
                    'name' => $group['name'],
                    'time' => api_time_to_hms($time),
                    'progress' => $averageProgress,
                    'score' => $averageScore,
                    'works' => $work,
                    'messages' => $messages,
                );
                $parsed_result[] = $group_item;
            }
        }

        return $parsed_result;
    }

    /**
     * @param int $user_id
     * @param array $courseInfo
     * @param int $session_id
     * @param string $origin
     * @param bool $export_csv
     * @param int $lp_id
     * @param int $lp_item_id
     * @param int $extendId
     * @param int $extendAttemptId
     * @param string $extendedAttempt
     * @param string $extendedAll
     * @param string $type classic or simple
     * @param boolean $allowExtend Optional. Allow or not extend te results
     * @return null|string
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
            return null;
        }

        $hideTime = api_get_configuration_value('hide_lp_time');

        $lp_id = intval($lp_id);
        $lp_item_id = intval($lp_item_id);
        $user_id = intval($user_id);
        $session_id = intval($session_id);
        $origin = Security::remove_XSS($origin);
        $list = learnpath::get_flat_ordered_items_list($lp_id, 0, $courseInfo['real_id']);
        $is_allowed_to_edit = api_is_allowed_to_edit(null, true);
        $course_id = $courseInfo['real_id'];
        $courseCode = $courseInfo['code'];
        $session_condition = api_get_session_condition($session_id);

        // Extend all button
        $output = null;
        $extend_all = 0;

        if ($origin == 'tracking') {
            $url_suffix = '&session_id='.$session_id.'&course='.$courseCode.'&student_id='.$user_id.'&lp_id='.$lp_id.'&origin='.$origin;
        } else {
            $url_suffix = '&lp_id='.$lp_id;
        }

        if (!empty($extendedAll)) {
            $extend_all_link = Display::url(
                Display::return_icon('view_less_stats.gif', get_lang('HideAllAttempts')),
                api_get_self().'?action=stats'.$url_suffix
            );
            $extend_all = 1;
        } else {
            $extend_all_link = Display::url(
                Display::return_icon('view_more_stats.gif', get_lang('ShowAllAttempts')),
                api_get_self().'?action=stats&extend_all=1'.$url_suffix
            );
        }

        if ($origin != 'tracking') {
            $output .= '<div class="section-status">';
            $output .= Display::page_header(get_lang('ScormMystatus'));
            $output .= '</div>';
        }

        $actionColumn = null;
        if ($type == 'classic') {
            $actionColumn = ' <th>'.get_lang('Actions').'</th>';
        }

        $timeHeader = '<th class="lp_time" colspan="2">'.get_lang('ScormTime').'</th>';
        if ($hideTime) {
            $timeHeader = '';
        }
        $output .= '<div class="table-responsive">';
        $output .= '<table id="lp_tracking" class="table tracking">
            <thead>
            <tr class="table-header">
                <th width="16">' . ($allowExtend == true ? $extend_all_link : '&nbsp;').'</th>
                <th colspan="4">
                    ' . get_lang('ScormLessonTitle').'
                </th>
                <th colspan="2">
                    ' . get_lang('ScormStatus').'
                </th>
                <th colspan="2">
                    ' . get_lang('ScormScore').'
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
        $view = '';
        if (Database::num_rows($res) > 0) {
            $myrow = Database::fetch_array($res);
            $view = $myrow[0];
        }

        $counter = 0;
        $total_time = 0;
        $h = get_lang('h');

        if (!empty($export_csv)) {
            $csvHeaders = array(
                get_lang('ScormLessonTitle'),
                get_lang('ScormStatus'),
                get_lang('ScormScore')
            );

            if ($hideTime === false) {
                $csvHeaders[] = get_lang('ScormTime');
            }

            $csv_content[] = $csvHeaders;
        }

        $result_disabled_ext_all = true;
        $chapterTypes = learnpath::getChapterTypes();

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
                    i.id as myid,
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
                ON (i.id = iv.lp_item_id AND i.c_id = iv.c_id)
                INNER JOIN $TBL_LP_VIEW as v
                ON (iv.lp_view_id = v.id AND v.c_id = iv.c_id)
                WHERE
                    v.c_id = $course_id AND
                    i.id = $my_item_id AND
                    i.lp_id = $lp_id  AND
                    v.user_id = $user_id AND
                    v.session_id = $session_id
                    $viewCondition
                ORDER BY iv.view_count $order ";

                $result = Database::query($sql);
                $num = Database::num_rows($result);
                $time_for_total = 'NaN';

                // Extend all
                if (($extend_this || $extend_all) && $num > 0) {
                    $row = Database::fetch_array($result);
                    $result_disabled_ext_all = false;
                    if ($row['item_type'] == 'quiz') {
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
                    if (($counter % 2) == 0) {
                        $oddclass = 'row_odd';
                    } else {
                        $oddclass = 'row_even';
                    }

                    $extend_link = '';
                    if (!empty($inter_num)) {
                        $extend_link = Display::url(
                            Display::return_icon(
                                'visible.gif',
                                get_lang('HideAttemptView')
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
                    if ($type == 'classic') {
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
                                <td colspan="4">
                                   '.$title.'
                                </td>
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

                        if ((learnpath::get_interactions_count_from_db($row['iv_id'], $course_id) > 0 ||
                            learnpath::get_objectives_count_from_db($row['iv_id'], $course_id) > 0) &&
                            !$extend_all
                        ) {
                            if ($extendAttemptId == $row['iv_id']) {
                                // The extend button for this attempt has been clicked.
                                $extend_this_attempt = 1;
                                $extend_attempt_link = Display::url(
                                    Display::return_icon('visible.gif', get_lang('HideAttemptView')),
                                    api_get_self().'?action=stats&extend_id='.$my_item_id.'&fold_attempt_id='.$row['iv_id'].$url_suffix
                                );
                            } else { // Same case if fold_attempt_id is set, so not implemented explicitly.
                                // The extend button for this attempt has not been clicked.
                                $extend_attempt_link = Display::url(
                                    Display::return_icon('invisible.gif', get_lang('ExtendAttemptView')),
                                    api_get_self().'?action=stats&extend_id='.$my_item_id.'&extend_attempt_id='.$row['iv_id'].$url_suffix
                                );
                            }
                        }

                        if (($counter % 2) == 0) {
                            $oddclass = 'row_odd';
                        } else {
                            $oddclass = 'row_even';
                        }

                        $lesson_status = $row['mystatus'];
                        $score = $row['myscore'];
                        $time_for_total = $row['mytime'];
                        $time = learnpathItem::getScormTimeFromParameter('js', $row['mytime']);

                        if ($score == 0) {
                            $maxscore = $row['mymaxscore'];
                        } else {
                            if ($row['item_type'] == 'sco') {
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

                        if ($row['item_type'] != 'dir') {
                            if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                                $view_score = Display::return_icon(
                                    'invisible.gif',
                                    get_lang('ResultsHiddenByExerciseSetting')
                                );
                            } else {
                                switch ($row['item_type']) {
                                    case 'sco':
                                        if ($maxscore == 0) {
                                            $view_score = $score;
                                        } else {
                                            $view_score = ExerciseLib::show_score($score, $maxscore, false);
                                        }
                                        break;
                                    case 'document':
                                        $view_score = ($score == 0 ? '/' : ExerciseLib::show_score($score, $maxscore, false));
                                        break;
                                    default:
                                        $view_score = ExerciseLib::show_score($score, $maxscore, false);
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
                                    <td>' . $extend_attempt_link.'</td>
                                    <td colspan="3">' . get_lang('Attempt').' '.$attemptCount.'</td>
                                    <td colspan="2">' . learnpathItem::humanize_status($lesson_status, true, $type).'</td>
                                    <td colspan="2">' . $view_score.'</td>
                                    '.$timeRow.'
                                    '.$action.'
                                </tr>';
                            $attemptCount++;
                            if (!empty($export_csv)) {
                                $temp = array();
                                $temp[] = $title = Security::remove_XSS($title);
                                $temp[] = Security::remove_XSS(
                                    learnpathItem::humanize_status($lesson_status, false, $type)
                                );

                                if ($row['item_type'] == 'quiz') {
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
                        if ($type == 'classic') {
                            $action = '<td></td>';
                        }

                        if ($extend_this_attempt || $extend_all) {
                            $list1 = learnpath::get_iv_interactions_array($row['iv_id']);
                            foreach ($list1 as $id => $interaction) {
                                if (($counter % 2) == 0) {
                                    $oddclass = 'row_odd';
                                } else {
                                    $oddclass = 'row_even';
                                }
                                $student_response = urldecode($interaction['student_response']);
                                $content_student_response = explode('__|', $student_response);

                                if (count($content_student_response) > 0) {
                                    if (count($content_student_response) >= 3) {
                                        // Pop the element off the end of array.
                                        array_pop($content_student_response);
                                    }
                                    $student_response = implode(',', $content_student_response);
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
                                        <td colspan="2">' . $interaction['type'].'</td>
                                        <td>'.$student_response.'</td>
                                        <td>'.$interaction['result'].'</td>
                                        <td>'.$interaction['latency'].'</td>
                                        '.$timeRow.'
                                        '.$action.'
                                    </tr>';
                                $counter++;
                            }
                            $list2 = learnpath::get_iv_objectives_array($row['iv_id']);

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
                                        <td>' . $interaction['order_id'].'</td>
                                        <td colspan="2">' . $interaction['objective_id'].'</td>
                                        <td colspan="2">' . $interaction['status'].'</td>
                                        <td>' . $interaction['score_raw'].'</td>
                                        <td>' . $interaction['score_max'].'</td>
                                        <td>' . $interaction['score_min'].'</td>
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

                    if ($row['item_type'] == 'quiz') {
                        // Check results_disabled in quiz table.
                        $my_path = Database::escape_string($my_path);
                        $sql = "SELECT results_disabled
                                FROM $TBL_QUIZ
                                WHERE c_id = $course_id AND id ='".$my_path."'";
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
                                Display::return_icon('visible.gif', get_lang('HideAttemptView')),
                                api_get_self().'?action=stats&extend_id='.$my_item_id.'&fold_attempt_id='.$row['iv_id'].$url_suffix
                            );
                        } else {
                            // Same case if fold_attempt_id is set, so not implemented explicitly.
                            // The extend button for this attempt has not been clicked.
                            $extend_attempt_link = Display::url(
                                Display::return_icon('invisible.gif', get_lang('ExtendAttemptView')),
                                api_get_self().'?action=stats&extend_id='.$my_item_id.'&extend_attempt_id='.$row['iv_id'].$url_suffix
                            );
                        }
                    }

                    if (($counter % 2) == 0) {
                        $oddclass = 'row_odd';
                    } else {
                        $oddclass = 'row_even';
                    }

                    $extend_link = '';
                    if ($inter_num > 1) {
                        $extend_link = Display::url(
                            Display::return_icon('invisible.gif', get_lang('ExtendAttemptView')),
                            api_get_self().'?action=stats&extend_id='.$my_item_id.'&extend_attempt_id='.$row['iv_id'].$url_suffix
                        );
                    }

                    $lesson_status = $row['mystatus'];
                    $score = $row['myscore'];
                    $subtotal_time = $row['mytime'];

                    while ($tmp_row = Database::fetch_array($result)) {
                        $subtotal_time += $tmp_row['mytime'];
                    }

                    $title = $row['mytitle'];

                    // Selecting the exe_id from stats attempts tables in order to look the max score value.
                    $sql = 'SELECT * FROM '.$tbl_stats_exercices.'
                             WHERE
                                exe_exo_id="' . $row['path'].'" AND
                                exe_user_id="' . $user_id.'" AND
                                orig_lp_id = "' . $lp_id.'" AND
                                orig_lp_item_id = "' . $row['myid'].'" AND
                                c_id = ' . $course_id.' AND
                                status <> "incomplete" AND
                                session_id = ' . $session_id.'
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
                            if (!empty($row['myviewmaxscore']) and $row['myviewmaxscore'] > 0) {
                                $maxscore = $row['myviewmaxscore'];
                            } elseif ($row['myviewmaxscore'] === '') {
                                $maxscore = 0;
                            } else {
                                $maxscore = $row['mymaxscore'];
                            }
                            break;
                        case 'quiz':
                            // Get score and total time from last attempt of a exercise en lp.
                            $sql = "SELECT score
                                    FROM $TBL_LP_ITEM_VIEW
                                    WHERE
                                        c_id = $course_id AND
                                        lp_item_id = '".(int) $my_id."' AND
                                        lp_view_id = '" . (int) $my_lp_view_id."'
                                    ORDER BY view_count DESC limit 1";
                            $res_score = Database::query($sql);
                            $row_score = Database::fetch_array($res_score);

                            $sql = "SELECT SUM(total_time) as total_time
                                    FROM $TBL_LP_ITEM_VIEW
                                    WHERE
                                        c_id = $course_id AND
                                        lp_item_id = '".(int) $my_id."' AND
                                        lp_view_id = '" . (int) $my_lp_view_id."'";
                            $res_time = Database::query($sql);
                            $row_time = Database::fetch_array($res_time);

                            if (Database::num_rows($res_score) > 0 &&
                                Database::num_rows($res_time) > 0
                            ) {
                                $score = (float) $row_score['score'];
                                $subtotal_time = (int) $row_time['total_time'];
                            } else {
                                $score = 0;
                                $subtotal_time = 0;
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
                            break;
                        default:
                            $maxscore = $row['mymaxscore'];
                            break;
                    }

                    $time_for_total = $subtotal_time;
                    $time = learnpathItem::getScormTimeFromParameter(
                        'js',
                        $subtotal_time
                    );
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
                        if ($row['item_type'] == 'quiz') {
                            $my_url_suffix = '&course='.$courseCode.'&student_id='.$user_id.'&lp_id='.intval($row['mylpid']).'&origin='.$origin;
                            $sql = 'SELECT * FROM '.$tbl_stats_exercices.'
                                     WHERE
                                        exe_exo_id="' . $row['path'].'" AND
                                        exe_user_id="' . $user_id.'" AND
                                        orig_lp_id = "' . $lp_id.'" AND
                                        orig_lp_item_id = "' . $row['myid'].'" AND
                                        c_id = ' . $course_id.' AND
                                        status <> "incomplete" AND
                                        session_id = ' . $session_id.'
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
                                            get_lang('HideAllAttempts')
                                        ),
                                        api_get_self().'?action=stats'.$my_url_suffix.'&session_id='.$session_id.'&lp_item_id='.$my_id.'#'.$linkId,
                                        ['id' => $linkId]
                                    );
                                } else {
                                    $correct_test_link = Display::url(
                                        Display::return_icon(
                                            'view_more_stats.gif',
                                            get_lang(
                                                'ShowAllAttemptsByExercise'
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
                        if ($type == 'classic') {
                            $action = '<td '.($showRowspan ? 'rowspan="2"' : '').'>'.$correct_test_link.'</td>';
                        }

                        if ($lp_id == $my_lp_id && false) {
                            $output .= '<tr class ='.$oddclass.'>
                                    <td>' . $extend_link.'</td>
                                    <td colspan="4">' . $title.'</td>
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
                                        'invisible.gif',
                                        get_lang('ResultsHiddenByExerciseSetting')
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
                                <td colspan="4">' . $title.'</td>
                                <td colspan="2">' . learnpathitem::humanize_status($lesson_status).'</td>
                                <td colspan="2">'.$scoreItem.'</td>
                                '.$timeRow.'
                                '.$action.'
                             ';
                            $output .= '</tr>';
                        }

                        if (!empty($export_csv)) {
                            $temp = array();
                            $temp[] = api_html_entity_decode($title, ENT_QUOTES);
                            $temp[] = api_html_entity_decode($lesson_status, ENT_QUOTES);

                            if ($row['item_type'] == 'quiz') {
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
                    if ($type == 'classic') {
                        $action = '<td></td>';
                    }

                    if ($extend_this_attempt || $extend_all) {
                        $list1 = learnpath::get_iv_interactions_array($row['iv_id']);
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
                                    <td colspan="2">' . $interaction['type'].'</td>
                                    <td>'.urldecode($interaction['student_response']).'</td>
                                    <td>'.$interaction['result'].'</td>
                                    <td>'.$interaction['latency'].'</td>
                                    '.$timeRow.'
                                    '.$action.'
                               </tr>';
                            $counter++;
                        }
                        $list2 = learnpath::get_iv_objectives_array($row['iv_id']);
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
                                    <td>' . $interaction['order_id'].'</td>
                                    <td colspan="2">'.$interaction['objective_id'].'</td>
                                    <td colspan="2">' . $interaction['status'].'</td>
                                    <td>' . $interaction['score_raw'].'</td>
                                    <td>' . $interaction['score_max'].'</td>
                                    <td>' . $interaction['score_min'].'</td>
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
                                        id = '$lp_item_id' AND
                                        lp_id = '$lp_id'";
                            $res_path = Database::query($sql);
                            $row_path = Database::fetch_array($res_path);

                            if (Database::num_rows($res_path) > 0) {
                                $sql = 'SELECT * FROM '.$tbl_stats_exercices.'
                                        WHERE
                                            exe_exo_id="' . (int) $row_path['path'].'" AND
                                            status <> "incomplete" AND
                                            exe_user_id="' . $user_id.'" AND
                                            orig_lp_id = "' . (int) $lp_id.'" AND
                                            orig_lp_item_id = "' . (int) $lp_item_id.'" AND
                                            c_id = ' . $course_id.'  AND
                                            session_id = ' . $session_id.'
                                        ORDER BY exe_date';
                                $res_attempts = Database::query($sql);
                                $num_attempts = Database::num_rows($res_attempts);
                                if ($num_attempts > 0) {
                                    $n = 1;
                                    while ($row_attempts = Database::fetch_array($res_attempts)) {
                                        $my_score = $row_attempts['exe_result'];
                                        $my_maxscore = $row_attempts['exe_weighting'];
                                        $my_exe_id = $row_attempts['exe_id'];
                                        $my_orig_lp = $row_attempts['orig_lp_id'];
                                        $my_orig_lp_item = $row_attempts['orig_lp_item_id'];
                                        $my_exo_exe_id = $row_attempts['exe_exo_id'];
                                        $mktime_start_date = api_strtotime($row_attempts['start_date'], 'UTC');
                                        $mktime_exe_date = api_strtotime($row_attempts['exe_date'], 'UTC');
                                        if ($mktime_start_date && $mktime_exe_date) {
                                            $mytime = ((int) $mktime_exe_date - (int) $mktime_start_date);
                                            $time_attemp = learnpathItem::getScormTimeFromParameter('js', $mytime);
                                            $time_attemp = str_replace('NaN', '00'.$h.'00\'00"', $time_attemp);
                                        } else {
                                            $time_attemp = ' - ';
                                        }
                                        if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                                            $view_score = Display::return_icon('invisible.gif', get_lang('ResultsHiddenByExerciseSetting'));
                                        } else {
                                            // Show only float when need it
                                            if ($my_score == 0) {
                                                $view_score = ExerciseLib::show_score(0, $my_maxscore, false);
                                            } else {
                                                if ($my_maxscore == 0) {
                                                    $view_score = $my_score;
                                                } else {
                                                    $view_score = ExerciseLib::show_score($my_score, $my_maxscore, false);
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
                                        <td>' . $extend_attempt_link.'</td>
                                        <td colspan="3">' . get_lang('Attempt').' '.$n.'</td>
                                        <td colspan="2">' . $my_lesson_status.'</td>
                                        <td colspan="2">'.$view_score.'</td>
                                        '.$timeRow;

                                        if ($action == 'classic') {
                                            if ($origin != 'tracking') {
                                                if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                                                    $output .= '<td>
                                                            <img src="' . Display::returnIconPath('quiz_na.gif').'" alt="'.get_lang('ShowAttempt').'" title="'.get_lang('ShowAttempt').'">
                                                            </td>';
                                                } else {
                                                    $output .= '<td>
                                                            <a href="../exercise/exercise_show.php?origin=' . $origin.'&id='.$my_exe_id.'&cidReq='.$courseCode.'" target="_parent">
                                                            <img src="' . Display::returnIconPath('quiz.png').'" alt="'.get_lang('ShowAttempt').'" title="'.get_lang('ShowAttempt').'">
                                                            </a></td>';
                                                }
                                            } else {
                                                if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                                                    $output .= '<td>
                                                                <img src="' . Display::returnIconPath('quiz_na.gif').'" alt="'.get_lang('ShowAndQualifyAttempt').'" title="'.get_lang('ShowAndQualifyAttempt').'"></td>';
                                                } else {
                                                    $output .= '<td>
                                                                    <a href="../exercise/exercise_show.php?cidReq=' . $courseCode.'&origin=correct_exercise_in_lp&id='.$my_exe_id.'" target="_parent">
                                                                    <img src="' . Display::returnIconPath('quiz.gif').'" alt="'.get_lang('ShowAndQualifyAttempt').'" title="'.get_lang('ShowAndQualifyAttempt').'"></a></td>';
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
                $a_my_id = array();
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
                array($lp_id),
                $session_id,
                false,
                false
            );
        }

        $total_time = learnpathItem::getScormTimeFromParameter('js', $total_time);
        $total_time = str_replace('NaN', '00'.$h.'00\'00"', $total_time);

        if (!$is_allowed_to_edit && $result_disabled_ext_all) {
            $final_score = Display::return_icon('invisible.gif', get_lang('ResultsHiddenByExerciseSetting'));
        } else {
            if (is_numeric($total_score)) {
                $final_score = $total_score.'%';
            } else {
                $final_score = $total_score;
            }
        }

        $progress = learnpath::getProgress($lp_id, $user_id, $course_id, $session_id);

        if (($counter % 2) == 0) {
            $oddclass = 'row_odd';
        } else {
            $oddclass = 'row_even';
        }

        $action = null;
        if ($type == 'classic') {
            $action = '<td></td>';
        }

        $timeTotal = '<td class="lp_time" colspan="2">'.$total_time.'</div>';
        if ($hideTime) {
            $timeTotal = '';
        }

        $output .= '<tr class="'.$oddclass.'">
                <td></td>
                <td colspan="4">
                    <i>' . get_lang('AccomplishedStepsTotal').'</i>
                </td>
                <td colspan="2">'.$progress.'%</td>
                <td colspan="2">' . $final_score.'</td>
                '.$timeTotal.'
                '.$action.'
           </tr>';

        $output .= '
                    </tbody>
                </table>
            </div>
        ';

        if (!empty($export_csv)) {
            $temp = array(
                '',
                '',
                '',
                ''
            );
            $csv_content[] = $temp;
            $temp = array(
                get_lang('AccomplishedStepsTotal'),
                '',
                $final_score
            );

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
     * @param int $userId
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
                array(),
                array(),
                STUDENT
            );

            $students = array();
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
                array(),
                array(),
                STUDENT_BOSS
            );

            if ($getCount) {
                $studentBossCount = $studentBossesList;
            } else {
                $studentBosses = array();
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
                array(),
                array(),
                COURSEMANAGER
            );

            if ($getCount) {
                $teachersCount = $teacherList;
            } else {
                $teachers = array();
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
                array(),
                array(),
                DRH
            );

            if ($getCount) {
                $drhCount = $humanResources;
            } else {
                $humanResourcesList = array();
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

            $students = array();
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
                $studentBosses = array();
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
                $teachers = array();
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
                $humanResourcesList = array();
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
                'assigned_courses' => $assignedCourseCount
            ];
        }

        return array(
            'drh' => $humanResourcesList,
            'teachers' => $teachers,
            'student_list' => $students,
            'student_bosses' => $studentBosses,
            'courses' => $courses,
            'sessions' => $sessions,
            'assigned_courses' => $assignedCourses
        );
    }

    /**
     * Calculates the time spent on the platform by a user
     * @param   int|array User id
     * @param   string $timeFilter type of time filter: 'last_week' or 'custom'
     * @param   string  $start_date start date date('Y-m-d H:i:s')
     * @param   string  $end_date end date date('Y-m-d H:i:s')
     *
     * @return int $nb_seconds
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
        } else {
            return -1;
        }
    }

    /**
     * Calculates the time spent on the course
     * @param integer $user_id
     * @param integer  $courseId
     * @param int Session id (optional)
     *
     * @return int Time in seconds
     */
    public static function get_time_spent_on_the_course($user_id, $courseId, $session_id = 0)
    {
        $courseId = intval($courseId);
        $session_id  = intval($session_id);
        $tbl_track_course = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        if (is_array($user_id)) {
            $user_id = array_map('intval', $user_id);
            $condition_user = " AND user_id IN (".implode(',', $user_id).") ";
        } else {
            $user_id = intval($user_id);
            $condition_user = " AND user_id = $user_id ";
        }

        $sql = "SELECT
                SUM(UNIX_TIMESTAMP(logout_course_date) - UNIX_TIMESTAMP(login_course_date)) as nb_seconds
                FROM $tbl_track_course
                WHERE UNIX_TIMESTAMP(logout_course_date) > UNIX_TIMESTAMP(login_course_date) ";

        if ($courseId != 0) {
            $sql .= "AND c_id = '$courseId' ";
        }

        if ($session_id != -1) {
            $sql .= "AND session_id = '$session_id' ";
        }

        $sql .= $condition_user;
        $rs = Database::query($sql);
        $row = Database::fetch_array($rs);

        return $row['nb_seconds'];
    }

    /**
     * Get first connection date for a student
     * @param    int $student_id
     *
     * @return    string|bool Date format long without day or false if there are no connections
     */
    public static function get_first_connection_date($student_id)
    {
        $tbl_track_login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $sql = 'SELECT login_date
                FROM ' . $tbl_track_login.'
                WHERE login_user_id = ' . intval($student_id).'
                ORDER BY login_date ASC
                LIMIT 0,1';

        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            if ($first_login_date = Database::result($rs, 0, 0)) {
                return api_convert_and_format_date(
                    $first_login_date,
                    DATE_FORMAT_SHORT,
                    date_default_timezone_get()
                );
            }
        }

        return false;
    }

    /**
     * Get las connection date for a student
     * @param int $student_id
     * @param bool $warning_message Show a warning message (optional)
     * @param bool $return_timestamp True for returning results in timestamp (optional)
     * @return string|int|bool Date format long without day, false if there are no connections or
     * timestamp if parameter $return_timestamp is true
     */
    public static function get_last_connection_date(
        $student_id,
        $warning_message = false,
        $return_timestamp = false
    ) {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $sql = 'SELECT login_date
                FROM ' . $table.'
                WHERE login_user_id = ' . intval($student_id).'
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
     * Get las connection date for a student
     * @param array $studentList Student id array
     * @param int $days
     * @param bool $getCount
     * @return int
     */
    public static function getInactiveUsers($studentList, $days, $getCount = true)
    {
        if (empty($studentList)) {
            return 0;
        }
        $days = intval($days);
        $date = api_get_utc_datetime(strtotime($days.' days ago'));
        $studentList = array_map('intval', $studentList);

        $tbl_track_login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $select = " SELECT login_user_id ";
        if ($getCount) {
            $select = " SELECT count(DISTINCT login_user_id) as count";
        }
        $sql = "$select
                FROM $tbl_track_login
                WHERE
                    login_user_id IN (' ".implode("','", $studentList)."' ) AND
                    login_date < '$date'
                ";
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            if ($getCount) {
                $count = Database::fetch_array($rs);
                return $count['count'];
            }
            return Database::store_result($rs, 'ASSOC');
        }
        return false;
    }

    /**
     * Get first user's connection date on the course
     * @param int User id
     * @param int $courseId
     * @param int Session id (optional, default=0)
     * @return string|bool Date with format long without day or false if there is no date
     */
    public static function get_first_connection_date_on_the_course(
        $student_id,
        $courseId,
        $session_id = 0,
        $convert_date = true
    ) {
        $student_id  = intval($student_id);
        $courseId = intval($courseId);
        $session_id  = intval($session_id);

        $tbl_track_login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $sql = 'SELECT login_course_date
                FROM '.$tbl_track_login.'
                WHERE
                    user_id = '.$student_id.' AND
                    c_id = '.$courseId.' AND
                    session_id = '.$session_id.'
                ORDER BY login_course_date ASC 
                LIMIT 0,1';
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            if ($first_login_date = Database::result($rs, 0, 0)) {
                if ($convert_date) {
                    return api_convert_and_format_date(
                        $first_login_date,
                        DATE_FORMAT_SHORT
                    );
                } else {
                    return $first_login_date;
                }
            }
        }

        return false;
    }

    /**
     * Get last user's connection date on the course
     * @param     int         User id
     * @param    array        $courseInfo real_id and code are used
     * @param    int            Session id (optional, default=0)
     * @param bool $convert_date
     * @return    string|bool    Date with format long without day or false if there is no date
     */
    public static function get_last_connection_date_on_the_course(
        $student_id,
        $courseInfo,
        $session_id = 0,
        $convert_date = true
    ) {
        // protect data
        $student_id  = intval($student_id);
        $courseId = $courseInfo['real_id'];
        $session_id  = intval($session_id);

        $tbl_track_e_access = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ACCESS);
        $sql = 'SELECT access_date
                FROM '.$tbl_track_e_access.'
                WHERE   access_user_id = '.$student_id.' AND
                        c_id = "'.$courseId.'" AND
                        access_session_id = '.$session_id.'
                ORDER BY access_date DESC
                LIMIT 0,1';

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
                        $icon = api_is_allowed_to_edit() ?
                            '<a href="'.api_get_path(WEB_CODE_PATH).'announcements/announcements.php?action=add&remind_inactive='.$student_id.'&cidReq='.$courseInfo['code'].'" title="'.get_lang('RemindInactiveUser').'">
                              '.Display::return_icon('messagebox_warning.gif').'
                             </a>'
                            : null;
                        return $icon.Display::label($last_login_date, 'warning');
                    } else {
                        return $last_login_date;
                    }
                } else {
                    if ($convert_date) {
                        return api_convert_and_format_date($last_login_date, DATE_FORMAT_SHORT);
                    } else {
                        return $last_login_date;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Get count of the connections to the course during a specified period
     * @param   int  $courseId
     * @param   int     Session id (optional)
     * @param   int     Datetime from which to collect data (defaults to 0)
     * @param   int     Datetime to which to collect data (defaults to now)
     * @return  int     count connections
     */
    public static function get_course_connections_count($courseId, $session_id = 0, $start = 0, $stop = null)
    {
        if ($start < 0) {
            $start = 0;
        }
        if (!isset($stop) or ($stop < 0)) {
            $stop = api_get_utc_datetime();
        }

        // Given we're storing in cache, round the start and end times
        // to the lower minute
        $roundedStart = substr($start, 0, -2).'00';
        $roundedStop = substr($stop, 0, -2).'00';
        $roundedStart = Database::escape_string($roundedStart);
        $roundedStop = Database::escape_string($roundedStop);
    	$month_filter = " AND login_course_date > '$roundedStart' AND login_course_date < '$roundedStop' ";
        $courseId = intval($courseId);
        $session_id = intval($session_id);
    	$count = 0;
        $tbl_track_e_course_access = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $sql = "SELECT count(*) as count_connections
                FROM $tbl_track_e_course_access
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
     * Get count courses per student
     * @param     int $user_id Student id
     * @param    bool $include_sessions Include sessions (optional)
     * @return  int        count courses
     */
    public static function count_course_per_student($user_id, $include_sessions = true)
    {
        $user_id = intval($user_id);
        $tbl_course_rel_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_session_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        $sql = 'SELECT DISTINCT c_id
                FROM ' . $tbl_course_rel_user.'
                WHERE user_id = ' . $user_id.' AND relation_type<>'.COURSE_RELATION_TYPE_RRHH;
        $rs = Database::query($sql);
        $nb_courses = Database::num_rows($rs);

        if ($include_sessions) {
            $sql = 'SELECT DISTINCT c_id
                    FROM ' . $tbl_session_course_rel_user.'
                    WHERE user_id = ' . $user_id;
            $rs = Database::query($sql);
            $nb_courses += Database::num_rows($rs);
        }

        return $nb_courses;
    }

    /**
     * Gets the score average from all tests in a course by student
     *
     * @param $student_id
     * @param $course_code
     * @param int $exercise_id
     * @param null $session_id
     * @param int $active_filter    2 for consider all tests
     *                              1 for active <> -1
     *                              0 for active <> 0
     * @param int $into_lp  1 for all exercises
     *                      0 for without LP
     * @internal param \Student $mixed id
     * @internal param \Course $string code
     * @internal param \Exercise $int id (optional), filtered by exercise
     * @internal param \Session $int id (optional), if param $session_id is null
     * it'll return results including sessions, 0 = session is not filtered
     * @return   string    value (number %) Which represents a round integer about the score average.
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
                    $exercise_list = array();
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
                        SUM(exe_result/exe_weighting*100) as avg_score,
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
                        return array($quiz_avg_score, $lp_name);
                    } else {
                        return array($quiz_avg_score, null);
                    }
                }
            }
        }
        return null;
    }

    /**
     * Get count student's exercise COMPLETED attempts
     * @param int $student_id
     * @param int $courseId
     * @param int $exercise_id
     * @param int $lp_id
     * @param int $lp_item_id
     * @param int $session_id
     * @param int $find_all_lp  0 = just LP specified
     *                          1 = LP specified or whitout LP,
     *                          2 = all rows
     * @internal param \Student $int id
     * @internal param \Course $string code
     * @internal param \Exercise $int id
     * @internal param \Learning $int path id (optional),
     * for showing attempts inside a learning path $lp_id and $lp_item_id params are required.
     * @internal param \Learning $int path item id (optional),
     * for showing attempts inside a learning path $lp_id and $lp_item_id params are required.
     * @return  int     count of attempts
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
        $student_id  = intval($student_id);
        $exercise_id = intval($exercise_id);
        $session_id  = intval($session_id);

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
     * Get count student's exercise progress
     *
     * @param array $exercise_list
     * @param int $user_id
     * @param int $courseId
     * @param int $session_id
     */
    public static function get_exercise_student_progress($exercise_list, $user_id, $courseId, $session_id)
    {
        $courseId = intval($courseId);
        $user_id = intval($user_id);
        $session_id = intval($session_id);

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
     * @param int $user_id
     * @param int $courseId
     * @param int $session_id
     * @return string
     */
    public static function get_exercise_student_average_best_attempt($exercise_list, $user_id, $courseId, $session_id)
    {
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

                if (!empty($best_attempt) && !empty($best_attempt['exe_weighting'])) {
                    $result += $best_attempt['exe_result'] / $best_attempt['exe_weighting'];
                }
            }
            $result = $result / count($exercise_list);
            $result = round($result, 2) * 100;
        }

        return $result.'%';
    }

    /**
     * get teacher progress by course and session
     * @param int course id
     * @param int session id
     * @return array
     */
    static function get_teachers_progress_by_course($courseId, $sessionId)
    {
        $course = api_get_course_info_by_id($courseId);
        $sessionId = intval($sessionId);
        $courseId = intval($courseId);

        $sessionCourseUserTable = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);

        //get teachers
        $sql = "SELECT scu.session_id, scu.user_id, s.name
                FROM $sessionCourseUserTable scu, $sessionTable s
                WHERE
                    scu.session_id = s.id
                    AND scu.status = 2
                    AND scu.visibility = 1
                    AND scu.c_id = '%s'
                    AND scu.session_id = %s";
        $query = sprintf($sql, intval($courseId), $sessionId);
        $rs = Database::query($query);
        $teachers = array();
        while ($teacher = Database::fetch_array($rs, 'ASSOC')) {
            $teachers[] = $teacher;
        }
        $data = array();
        foreach ($teachers as $teacher) {
            //total documents added
            $sql = "SELECT count(*) as total
                    FROM c_item_property
                    WHERE lastedit_type = 'DocumentAdded'
                    AND c_id = %s
                    AND insert_user_id = %s
                    AND session_id = %s";
            $query = sprintf($sql,
                $courseId,
                $teacher['user_id'],
                $teacher['session_id']
            );

            $rs = Database::query($query);
            $totalDocuments = 0;
            if ($rs) {
                $row = Database::fetch_row($rs);
                $totalDocuments = $row[0];
            }
            //total links added
            $sql = "SELECT count(*) as total
                    FROM c_item_property
                    WHERE lastedit_type = 'LinkAdded'
                    AND c_id = %s
                    AND insert_user_id = %s
                    AND session_id = %s";
            $query = sprintf($sql,
                $courseId,
                $teacher['user_id'],
                $teacher['session_id']
            );
            $rs = Database::query($query);

            $totalLinks = 0;
            if ($rs) {
                $row = Database::fetch_row($rs);
                $totalLinks = $row[0];
            }
            //total forums added
            $sql = "SELECT count(*) as total
                    FROM c_item_property
                    WHERE lastedit_type = 'ForumthreadVisible'
                    AND c_id = %s
                    AND insert_user_id = %s
                    AND session_id = %s";
            $query = sprintf($sql,
                $courseId,
                $teacher['user_id'],
                $teacher['session_id']
            );
            $rs = Database::query($query);

            $totalForums = 0;
            if ($rs) {
                $row = Database::fetch_row($rs);
                $totalForums = $row[0];
            }
            //total wikis added
            $sql = "SELECT COUNT(DISTINCT(ref)) as total
                    FROM c_item_property
                    WHERE lastedit_type = 'WikiAdded'
                    AND c_id = %s
                    AND insert_user_id = %s
                    AND session_id = %s";
            $query = sprintf($sql,
                $courseId,
                $teacher['user_id'],
                $teacher['session_id']
            );
            $rs = Database::query($query);

            $totalWikis = 0;
            if ($rs) {
                $row = Database::fetch_row($rs);
                $totalWikis = $row[0];
            }
            //total works added
            $sql = "SELECT COUNT(*) as total
                    FROM c_item_property
                    WHERE lastedit_type = 'DirectoryCreated'
                    AND tool = 'work'
                    AND c_id = %s
                    AND insert_user_id = %s
                    AND session_id = %s";
            $query = sprintf($sql,
                $courseId,
                $teacher['user_id'],
                $teacher['session_id']
            );
            $rs = Database::query($query);

            $totalWorks = 0;
            if ($rs) {
                $row = Database::fetch_row($rs);
                $totalWorks = $row[0];
            }
            //total announcements added
            $sql = "SELECT COUNT(*) as total
                    FROM c_item_property
                    WHERE lastedit_type = 'AnnouncementAdded'
                    AND c_id = %s
                    AND insert_user_id = %s
                    AND session_id = %s";
            $query = sprintf($sql,
                $courseId,
                $teacher['user_id'],
                $teacher['session_id']
            );
            $rs = Database::query($query);

            $totalAnnouncements = 0;
            if ($rs) {
                $row = Database::fetch_row($rs);
                $totalAnnouncements = $row[0];
            }
            $tutor = api_get_user_info($teacher['user_id']);
            $data[] = array(
                'course' => $course['title'],
                'session' => $teacher['name'],
                'tutor' => $tutor['username'].' - '.$tutor['lastname'].' '.$tutor['firstname'],
                'documents' => $totalDocuments,
                'links' => $totalLinks,
                'forums' => $totalForums,
                'works' => $totalWorks,
                'wikis' => $totalWikis,
                'announcements' => $totalAnnouncements,
            );
        }

        return $data;
    }

    /**
     * Returns the average student progress in the learning paths of the given
     * course.
     * @param int|array $studentId
     * @param string    $courseCode
     * @param array     $lpIdList Limit average to listed lp ids
     * @param int       $sessionId     Session id (optional),
     * if parameter $session_id is null(default) it'll return results including
     * sessions, 0 = session is not filtered
     * @param bool      $returnArray Will return an array of the type:
     * [sum_of_progresses, number] if it is set to true
     * @param boolean $onlySeriousGame Optional. Limit average to lp on seriousgame mode
     * @return double   Average progress of the user in this course
     */
    public static function get_avg_student_progress(
        $studentId,
        $courseCode = null,
        $lpIdList = array(),
        $sessionId = null,
        $returnArray = false,
        $onlySeriousGame = false
    ) {
        // If there is at least one learning path and one student.
        if (empty($studentId)) {
            return false;
        }

        $sessionId = intval($sessionId);
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
            " lp_view.lp_id IN(".implode(', ', $filteredLP).") "
        ];

        $groupBy = 'GROUP BY lp_id';

        if (is_array($studentId)) {
            $studentId = array_map('intval', $studentId);
            $conditions[] = " lp_view.user_id IN (".implode(',', $studentId).")  ";


        } else {
            $studentId = intval($studentId);
            $conditions[] = " lp_view.user_id = '$studentId' ";

            if (empty($lpIdList)) {
                $lpList = new LearnpathList($studentId, $courseCode, $sessionId);
                $lpList = $lpList->get_flat_list();
                if (!empty($lpList)) {
                    /** @var  $lp */
                    foreach ($lpList as $lpId => $lp) {
                        $lpIdList[] = $lpId;
                    }
                }
            }
        }

        if (!empty($sessionId)) {
            $conditions[] = " session_id = $sessionId ";
        }

        $conditionToString = implode('AND', $conditions);
        // Get last view for each student (in case of multi-attempt)
        // Also filter on LPs of this session
        /*$sql = " SELECT
                    MAX(view_count),
                    AVG(progress) average,
                    SUM(progress) sum_progress,
                    count(progress) count_progress
                FROM $lpViewTable lp_view
                WHERE
                  $conditionToString
                $groupBy";*/

        $sql = "
            SELECT lp_id, view_count, progress FROM $lpViewTable lp_view
            WHERE
                $conditionToString
                $groupBy
            ORDER BY view_count DESC
        ";

        $result = Database::query($sql);

        $progress = array();
        $viewCount = array();
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            if (!isset($viewCount[$row['lp_id']])) {
                $progress[$row['lp_id']] = $row['progress'];
            }
            $viewCount[$row['lp_id']] = $row['view_count'];
        }

        // Fill with lp ids
        if (!empty($lpIdList)) {
            foreach ($lpIdList as $lpId) {
                if (!isset($progress[$lpId])) {
                    $progress[$lpId] = 0;
                }
            }
        }

        if (!empty($progress)) {
            $sum = array_sum($progress);
            $average = $sum / count($progress);
        } else {
            $average = 0;
            $sum = 0;
        }

        if ($returnArray) {
            return [
                $sum,
                count($progress)
            ];
        }

        return round($average, 1);
    }

    /**
     * This function gets:
     * 1. The score average from all SCORM Test items in all LP in a course-> All the answers / All the max scores.
     * 2. The score average from all Tests (quiz) in all LP in a course-> All the answers / All the max scores.
     * 3. And finally it will return the average between 1. and 2.
     * @todo improve performance, when loading 1500 users with 20 lps the script dies
     * This function does not take the results of a Test out of a LP
     *
     * @param mixed $student_id Array of user ids or an user id
     * @param string $course_code
     * @param array $lp_ids List of LP ids
     * @param int $session_id Session id (optional),
     * if param $session_id is null(default) it'll return results
     * including sessions, 0 = session is not filtered
     * @param bool $return_array Returns an array of the
     * type [sum_score, num_score] if set to true
     * @param bool $get_only_latest_attempt_results get only the latest attempts or ALL attempts
     * @param bool $getOnlyBestAttempt
     *
     * @return  string      Value (number %) Which represents a round integer explain in got in 3.
     */
    public static function get_avg_student_score(
        $student_id,
        $course_code,
        $lp_ids = array(),
        $session_id = null,
        $return_array = false,
        $get_only_latest_attempt_results = false,
        $getOnlyBestAttempt = false
    ) {
        $debug = false;
        if ($debug) echo '<h1>Tracking::get_avg_student_score</h1>';
        $tbl_stats_exercices = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $tbl_stats_attempts = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        $course = api_get_course_info($course_code);

        if (!empty($course)) {
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
                        WHERE c_id = $course_id AND (session_id = 0 OR session_id IS NULL ) $condition_lp ";
            } else {
                $sql = "SELECT DISTINCT(id), use_max_score
                        FROM $lp_table
                        WHERE c_id = $course_id $condition_lp ";
            }

            $res_row_lp = Database::query($sql);
            $count_row_lp = Database::num_rows($res_row_lp);

            $lp_list = $use_max_score = array();
            while ($row_lp = Database::fetch_array($res_row_lp)) {
                $lp_list[] = $row_lp['id'];
                $use_max_score[$row_lp['id']] = $row_lp['use_max_score'];
            }

            if ($debug) {
                echo '$lp_list: ';
                var_dump($lp_list);
                echo 'Use max score or not list: '; var_dump($use_max_score);
            }

            // prepare filter on users
            if (is_array($student_id)) {
                array_walk($student_id, 'intval');
                $condition_user1 = " AND user_id IN (".implode(',', $student_id).") ";
            } else {
                $condition_user1 = " AND user_id = $student_id ";
            }

            if ($count_row_lp > 0 && !empty($student_id)) {
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
                if ($debug) {
                    echo 'get LP results';
                    var_dump($sql);
                }

                $rs_last_lp_view_id = Database::query($sql);
                $global_result = 0;

                if (Database::num_rows($rs_last_lp_view_id) > 0) {
                    // Cycle through each line of the results (grouped by lp_id, user_id)
                    while ($row_lp_view = Database::fetch_array($rs_last_lp_view_id)) {
                        $count_items = 0;
                        $lpPartialTotal = 0;

                        $list = array();
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
                            if ($debug) {
                                echo 'Getting lp_items done by the user<br />';
                                var_dump($sql);
                            }

                            while ($row_lp_item = Database::fetch_array($res_lp_item, 'ASSOC')) {
                                $my_lp_item_id = $row_lp_item['lp_item_id'];
                                $order = ' view_count DESC';
                                if ($getOnlyBestAttempt) {
                                    $order = ' lp_iv.score DESC';
                                }

                                // Getting the most recent attempt
                                $sql = "SELECT  lp_iv.id as lp_item_view_id,
                                                lp_iv.score as score,
                                                lp_i.max_score,
                                                lp_iv.max_score as max_score_item_view,
                                                lp_i.path,
                                                lp_i.item_type,
                                                lp_i.id as iid
                                        FROM $lp_item_view_table as lp_iv
                                        INNER JOIN $lp_item_table as lp_i
                                        ON (lp_i.id = lp_iv.lp_item_id AND lp_iv.c_id = lp_i.c_id)                                            
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
                            if ($debug) var_dump($sql);
                            $res_max_score = Database::query($sql);

                            while ($row_max_score = Database::fetch_array($res_max_score, 'ASSOC')) {
                                $list[] = $row_max_score;
                            }
                        }

                        if ($debug) var_dump($list);

                        // Go through each scorable element of this view
                        $score_of_scorm_calculate = 0;
                        foreach ($list as $row_max_score) {
                            // Came from the original lp_item
                            $max_score = $row_max_score['max_score'];
                            // Came from the lp_item_view
                            $max_score_item_view = $row_max_score['max_score_item_view'];
                            $score = $row_max_score['score'];

                            if ($debug) echo '<h3>Item Type: '.$row_max_score['item_type'].'</h3>';

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
                                $lp_item_view_id = $row_max_score['lp_item_view_id'];

                                // Get last attempt to this exercise through
                                // the current lp for the current user
                                $order = 'exe_date DESC';
                                if ($getOnlyBestAttempt) {
                                    $order = 'exe_result DESC';
                                }
                                $sql = "SELECT exe_id, exe_result
                                        FROM $tbl_stats_exercices
                                        WHERE
                                            exe_exo_id           = '$item_path' AND
                                            exe_user_id          = $user_id AND
                                            orig_lp_item_id      = $item_id AND
                                            orig_lp_item_view_id = $lp_item_view_id AND
                                            c_id                 = $course_id AND
                                            session_id           = $session_id AND
                                            status = ''
                                        ORDER BY $order
                                        LIMIT 1";

                                $result_last_attempt = Database::query($sql);
                                if ($debug) var_dump($sql);
                                $num = Database::num_rows($result_last_attempt);
                                if ($num > 0) {
                                    $attemptResult = Database::fetch_array($result_last_attempt, 'ASSOC');
                                    $id_last_attempt = $attemptResult['exe_id'];
                                    // We overwrite the score with the best one not the one saved in the LP (latest)
                                    if ($getOnlyBestAttempt && $get_only_latest_attempt_results == false) {
                                        if ($debug) echo "Following score comes from the track_exercise table not in the LP because the score is the best<br />";
                                        $score = $attemptResult['exe_result'];
                                    }

                                    if ($debug) echo "Attempt id: $id_last_attempt with score $score<br />";
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

                            if (in_array($row_max_score['item_type'], array('quiz', 'sco'))) {
                                // Normal way
                                if ($use_max_score[$lp_id]) {
                                    $count_items++;
                                } else {
                                    if ($max_score != '') {
                                        $count_items++;
                                    }
                                }
                                if ($debug) echo '$count_items: '.$count_items;
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
                    if ($debug) {
                        var_dump($sql);
                    }
                    $result_have_quiz = Database::query($sql);
                    if (Database::num_rows($result_have_quiz) > 0) {
                        $row = Database::fetch_array($result_have_quiz, 'ASSOC');
                        if (is_numeric($row['count']) && $row['count'] != 0) {
                            $lp_with_quiz++;
                        }
                    }
                }

                if ($debug) echo '<h3>$lp_with_quiz '.$lp_with_quiz.' </h3>';
                if ($debug) echo '<h3>Final return</h3>';

                if ($lp_with_quiz != 0) {
                    if (!$return_array) {
                        $score_of_scorm_calculate = round(($global_result / $lp_with_quiz), 2);
                        if ($debug) var_dump($score_of_scorm_calculate);
                        if (empty($lp_ids)) {
                            if ($debug) echo '<h2>All lps fix: '.$score_of_scorm_calculate.'</h2>';
                        }
                        return $score_of_scorm_calculate;
                    } else {
                        if ($debug) var_dump($global_result, $lp_with_quiz);
                        return array($global_result, $lp_with_quiz);
                    }
                } else {

                    return '-';
                }
            }
        }

        return null;
    }

    /**
     * This function gets:
     * 1. The score average from all SCORM Test items in all LP in a course-> All the answers / All the max scores.
     * 2. The score average from all Tests (quiz) in all LP in a course-> All the answers / All the max scores.
     * 3. And finally it will return the average between 1. and 2.
     * This function does not take the results of a Test out of a LP
     *
     * @param   int|array   Array of user ids or an user id
     * @param   string      $course_code Course code
     * @param   array       $lp_ids List of LP ids
     * @param   int         $session_id Session id (optional), if param $session_id is 0(default)
     * it'll return results including sessions, 0 = session is not filtered
     * @param   bool        Returns an array of the type [sum_score, num_score] if set to true
     * @param   bool        get only the latest attempts or ALL attempts
     * @return  string      Value (number %) Which represents a round integer explain in got in 3.
     */
    public static function getAverageStudentScore(
        $student_id,
        $course_code = '',
        $lp_ids = array(),
        $session_id = 0
    ) {
        if (empty($student_id)) {
            return 0;
        }

        $conditions = array();

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
            $conditions[] = " id IN(".implode(',', $lp_ids).") ";
        }

        // Compose a filter based on optional session id
        $session_id = intval($session_id);
        if (!empty($session_id)) {
            $conditions[] = " session_id = $session_id ";
        }

        if (is_array($student_id)) {
            array_walk($student_id, 'intval');
            $conditions[] = " lp_view.user_id IN (".implode(',', $student_id).") ";
        } else {
            $conditions[] = " lp_view.user_id = $student_id ";
        }

        $conditionsToString = implode('AND ', $conditions);
        $sql = "SELECT
                    SUM(lp_iv.score) sum_score,
                    SUM(lp_i.max_score) sum_max_score
                FROM $lp_table as lp
                INNER JOIN $lp_item_table as lp_i
                ON lp.id = lp_id AND lp.c_id = lp_i.c_id
                INNER JOIN $lp_view_table as lp_view
                ON lp_view.lp_id = lp_i.lp_id AND lp_view.c_id = lp_i.c_id
                INNER JOIN $lp_item_view_table as lp_iv
                ON lp_i.id = lp_iv.lp_item_id AND lp_view.c_id = lp_iv.c_id AND lp_iv.lp_view_id = lp_view.id
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
     * This function gets time spent in learning path for a student inside a course
     * @param int|array $student_id Student id(s)
     * @param string $course_code Course code
     * @param array $lp_ids Limit average to listed lp ids
     * @param int $session_id Session id (optional), if param $session_id is null(default)
     * it'll return results including sessions, 0 = session is not filtered
     * @return int Total time
     */
    public static function get_time_spent_in_lp($student_id, $course_code, $lp_ids = array(), $session_id = 0)
    {
        $course = api_get_course_info($course_code);
        $student_id = (int) $student_id;
        $session_id = (int) $session_id;
        $total_time = 0;

        if (!empty($course)) {
            $lp_table = Database::get_course_table(TABLE_LP_MAIN);
            $t_lpv = Database::get_course_table(TABLE_LP_VIEW);
            $t_lpiv = Database::get_course_table(TABLE_LP_ITEM_VIEW);
            $course_id = $course['real_id'];

            // Compose a filter based on optional learning paths list given
            $condition_lp = '';
            if (count($lp_ids) > 0) {
                $condition_lp = " AND id IN(".implode(',', $lp_ids).") ";
            }

            // Check the real number of LPs corresponding to the filter in the
            // database (and if no list was given, get them all)
            $sql = "SELECT DISTINCT(id) FROM $lp_table 
                    WHERE c_id = $course_id $condition_lp";
            $res_row_lp = Database::query($sql);
            $count_row_lp = Database::num_rows($res_row_lp);

            // calculates time
            if ($count_row_lp > 0) {
                while ($row_lp = Database::fetch_array($res_row_lp)) {
                    $lp_id = intval($row_lp['id']);
                    $sql = "SELECT SUM(total_time)
                            FROM $t_lpiv AS item_view
                            INNER JOIN $t_lpv AS view
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
     * This function gets last connection time to one learning path
     * @param int|array $student_id Student id(s)
     * @param string $course_code      Course code
     * @param int $lp_id    Learning path id
     * @param int $session_id
     * @return int         Total time
     */
    public static function get_last_connection_time_in_lp(
        $student_id,
        $course_code,
        $lp_id,
        $session_id = 0
    ) {
        $course = api_get_course_info($course_code);
        $student_id = intval($student_id);
        $lp_id = intval($lp_id);
        $last_time = 0;
        $session_id = intval($session_id);

        if (!empty($course)) {
            $course_id = $course['real_id'];
            $lp_table = Database::get_course_table(TABLE_LP_MAIN);
            $t_lpv = Database::get_course_table(TABLE_LP_VIEW);
            $t_lpiv = Database::get_course_table(TABLE_LP_ITEM_VIEW);

            // Check the real number of LPs corresponding to the filter in the
            // database (and if no list was given, get them all)
            $sql = "SELECT id FROM $lp_table WHERE c_id = $course_id AND id = $lp_id ";
            $res_row_lp = Database::query($sql);
            $count_row_lp = Database::num_rows($res_row_lp);

            // calculates last connection time
            if ($count_row_lp > 0) {
                $sql = 'SELECT MAX(start_time)
                        FROM ' . $t_lpiv.' AS item_view
                        INNER JOIN ' . $t_lpv.' AS view
                        ON (item_view.lp_view_id = view.id AND item_view.c_id = view.c_id)
                        WHERE
                            item_view.c_id = '.$course_id.' AND
                            view.c_id = '.$course_id.' AND
                            view.lp_id = '.$lp_id.' AND 
                            view.user_id = '.$student_id.' AND 
                            view.session_id = '.$session_id;
                $rs = Database::query($sql);
                if (Database::num_rows($rs) > 0) {
                    $last_time = Database::result($rs, 0, 0);
                }
            }
        }

        return $last_time;
    }

    /**
     * gets the list of students followed by coach
     * @param     int     $coach_id Coach id
     * @return     array     List of students
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
                FROM ' . $tbl_session_course_user.'
                WHERE user_id=' . $coach_id.' AND status=2';

        if (api_is_multiple_url_enabled()) {
            $tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $sql = 'SELECT scu.session_id, scu.c_id
                    FROM ' . $tbl_session_course_user.' scu
                    INNER JOIN '.$tbl_session_rel_access_url.'  sru
                    ON (scu.session_id=sru.session_id)
                    WHERE
                        scu.user_id=' . $coach_id.' AND
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
                        sru.relation_type<>".SESSION_RELATION_TYPE_RRHH." AND
                        srcru.c_id = '$courseId' AND
                        srcru.session_id = '$id_session'";

            $rs = Database::query($sql);

            while ($row = Database::fetch_array($rs)) {
                $students[$row['user_id']] = $row['user_id'];
            }
        }

        // Then, courses where $coach_id is coach of the session    //
        $sql = 'SELECT session_course_user.user_id
                FROM ' . $tbl_session_course_user.' as session_course_user
                INNER JOIN     '.$tbl_session_user.' sru
                ON session_course_user.user_id = sru.user_id AND session_course_user.session_id = sru.session_id
                INNER JOIN ' . $tbl_session_course.' as session_course
                ON session_course.c_id = session_course_user.c_id
                AND session_course_user.session_id = session_course.session_id
                INNER JOIN ' . $tbl_session.' as session
                ON session.id = session_course.session_id
                AND session.id_coach = ' . $coach_id;
        if (api_is_multiple_url_enabled()) {
            $tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $sql = 'SELECT session_course_user.user_id
                        FROM ' . $tbl_session_course_user.' as session_course_user
                        INNER JOIN     '.$tbl_session_user.' sru
                        ON session_course_user.user_id = sru.user_id AND
                           session_course_user.session_id = sru.session_id
                        INNER JOIN ' . $tbl_session_course.' as session_course
                        ON session_course.c_id = session_course_user.c_id AND
                        session_course_user.session_id = session_course.session_id
                        INNER JOIN ' . $tbl_session.' as session
                        ON session.id = session_course.session_id AND
                        session.id_coach = ' . $coach_id.'
                        INNER JOIN '.$tbl_session_rel_access_url.' session_rel_url
                        ON session.id = session_rel_url.session_id WHERE access_url_id = '.$access_url_id;
            }
        }

        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            $students[$row['user_id']] = $row['user_id'];
        }

        return $students;
    }

    /**
     * Get student followed by a coach inside a session
     * @param    int        Session id
     * @param    int        Coach id
     * @return   array    students list
     */
    public static function get_student_followed_by_coach_in_a_session($id_session, $coach_id)
    {
        $coach_id = intval($coach_id);
        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);

        $students = [];
        // At first, courses where $coach_id is coach of the course //
        $sql = 'SELECT c_id FROM '.$tbl_session_course_user.'
                WHERE session_id="' . $id_session.'" AND user_id='.$coach_id.' AND status=2';
        $result = Database::query($sql);

        while ($a_courses = Database::fetch_array($result)) {
            $courseId = $a_courses["c_id"];

            $sql = "SELECT DISTINCT srcru.user_id
                    FROM $tbl_session_course_user AS srcru
                    WHERE
                        c_id = '$courseId' AND
                        session_id = '".$id_session."'";
            $rs = Database::query($sql);
            while ($row = Database::fetch_array($rs)) {
                $students[$row['user_id']] = $row['user_id'];
            }
        }

        // Then, courses where $coach_id is coach of the session
        $sql = 'SELECT id_coach FROM '.$tbl_session.'
                WHERE id="' . $id_session.'" AND id_coach="'.$coach_id.'"';
        $result = Database::query($sql);

        //He is the session_coach so we select all the users in the session
        if (Database::num_rows($result) > 0) {
            $sql = 'SELECT DISTINCT srcru.user_id
                    FROM ' . $tbl_session_course_user.' AS srcru
                    WHERE session_id="' . $id_session.'"';
            $result = Database::query($sql);
            while ($row = Database::fetch_array($result)) {
                $students[$row['user_id']] = $row['user_id'];
            }
        }

        return $students;
    }

    /**
     * Check if a coach is allowed to follow a student
     * @param    int        Coach id
     * @param    int        Student id
     * @return    bool
     */
    public static function is_allowed_to_coach_student($coach_id, $student_id)
    {
        $coach_id = intval($coach_id);
        $student_id = intval($student_id);

        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);

        // At first, courses where $coach_id is coach of the course //

        $sql = 'SELECT 1 FROM '.$tbl_session_course_user.'
                WHERE user_id=' . $coach_id.' AND status=2';
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            return true;
        }

        // Then, courses where $coach_id is coach of the session
        $sql = 'SELECT session_course_user.user_id
                FROM ' . $tbl_session_course_user.' as session_course_user
                INNER JOIN ' . $tbl_session_course.' as session_course
                    ON session_course.c_id = session_course_user.c_id
                INNER JOIN ' . $tbl_session.' as session
                    ON session.id = session_course.session_id
                    AND session.id_coach = ' . $coach_id.'
                WHERE user_id = ' . $student_id;
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get courses followed by coach
     * @param     int        Coach id
     * @param    int        Session id (optional)
     * @return    array    Courses list
     */
    public static function get_courses_followed_by_coach($coach_id, $id_session = null)
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
                FROM ' . $tbl_session_course_user.' sc
                INNER JOIN '.$tbl_course.' c
                ON (c.id = sc.c_id)
                WHERE user_id = ' . $coach_id.' AND status = 2';

        if (api_is_multiple_url_enabled()) {
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $sql = 'SELECT DISTINCT c.code
                        FROM ' . $tbl_session_course_user.' scu
                        INNER JOIN '.$tbl_course.' c
                        ON (c.code = scu.c_id)
                        INNER JOIN '.$tbl_course_rel_access_url.' cru
                        ON (c.id = cru.c_id)
                        WHERE
                            scu.user_id=' . $coach_id.' AND
                            scu.status=2 AND
                            cru.access_url_id = '.$access_url_id;
            }
        }

        if (!empty($id_session)) {
            $sql .= ' AND session_id='.$id_session;
        }

        $courseList = array();
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            $courseList[$row['code']] = $row['code'];
        }

        // Then, courses where $coach_id is coach of the session

        $sql = 'SELECT DISTINCT course.code
                FROM ' . $tbl_session_course.' as session_course
                INNER JOIN ' . $tbl_session.' as session
                    ON session.id = session_course.session_id
                    AND session.id_coach = ' . $coach_id.'
                INNER JOIN ' . $tbl_course.' as course
                    ON course.id = session_course.c_id';

        if (api_is_multiple_url_enabled()) {
            $tbl_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $sql = 'SELECT DISTINCT c.code
                    FROM ' . $tbl_session_course.' as session_course
                    INNER JOIN '.$tbl_course.' c
                    ON (c.id = session_course.c_id)
                    INNER JOIN ' . $tbl_session.' as session
                    ON session.id = session_course.session_id
                        AND session.id_coach = ' . $coach_id.'
                    INNER JOIN ' . $tbl_course.' as course
                        ON course.id = session_course.c_id
                     INNER JOIN '.$tbl_course_rel_access_url.' course_rel_url
                    ON (course_rel_url.c_id = c.id)';
            }
        }

        if (!empty ($id_session)) {
            $sql .= ' WHERE session_course.session_id='.$id_session;
            if (api_is_multiple_url_enabled())
            $sql .= ' AND access_url_id = '.$access_url_id;
        } else {
            if (api_is_multiple_url_enabled())
            $sql .= ' WHERE access_url_id = '.$access_url_id;
        }

        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            $courseList[$row['code']] = $row['code'];
        }

        return $courseList;
    }

    /**
     * Get sessions coached by user
     * @param $coach_id
     * @param int $start
     * @param int $limit
     * @param bool $getCount
     * @param string $keyword
     * @param string $description
     * @return mixed
     */
    public static function get_sessions_coached_by_user(
        $coach_id,
        $start = 0,
        $limit = 0,
        $getCount = false,
        $keyword = '',
        $description = ''
    ) {
        // table definition
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $coach_id = intval($coach_id);

        $select = " SELECT * FROM ";
        if ($getCount) {
            $select = " SELECT count(DISTINCT id) as count FROM ";
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

        $tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
        $access_url_id = api_get_current_access_url_id();

        $sql = "
            $select
            (
                SELECT DISTINCT
                    id,
                    name,
                    access_start_date,
                    access_end_date
                FROM $tbl_session session
                INNER JOIN $tbl_session_rel_access_url session_rel_url
                ON (session.id = session_rel_url.session_id)
                WHERE
                    id_coach = $coach_id AND
                    access_url_id = $access_url_id
                    $keywordCondition
            UNION
                SELECT DISTINCT
                    session.id,
                    session.name,
                    session.access_start_date,
                    session.access_end_date
                FROM $tbl_session as session
                INNER JOIN $tbl_session_course_user as session_course_user
                ON
                    session.id = session_course_user.session_id AND
                    session_course_user.user_id = $coach_id AND
                    session_course_user.status = 2
                INNER JOIN $tbl_session_rel_access_url session_rel_url
                ON (session.id = session_rel_url.session_id)
                WHERE
                    access_url_id = $access_url_id
                    $keywordCondition
            ) as sessions $limitCondition
            ";

        $rs = Database::query($sql);
        if ($getCount) {
            $row = Database::fetch_array($rs);
            return $row['count'];
        }

        $sessions = [];
        while ($row = Database::fetch_array($rs)) {
            if ($row['access_start_date'] == '0000-00-00 00:00:00') {
                $row['access_start_date'] = null;
            }

            $sessions[$row['id']] = $row;
        }

        if (!empty($sessions)) {
            foreach ($sessions as & $session) {
                if (empty($session['access_start_date'])
                ) {
                    $session['status'] = get_lang('SessionActive');
                }
                else {
                    $time_start = api_strtotime($session['access_start_date'], 'UTC');
                    $time_end = api_strtotime($session['access_end_date'], 'UTC');
                    if ($time_start < time() && time() < $time_end) {
                        $session['status'] = get_lang('SessionActive');
                    } else {
                        if (time() < $time_start) {
                            $session['status'] = get_lang('SessionFuture');
                        } else {
                            if (time() > $time_end) {
                                $session['status'] = get_lang('SessionPast');
                            }
                        }
                    }
                }
            }
        }

        return $sessions;
    }

    /**
     * Get courses list from a session
     * @param    int        Session id
     * @return    array    Courses list
     */
    public static function get_courses_list_from_session($session_id)
    {
        $session_id = intval($session_id);

        // table definition
        $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);

        $sql = "SELECT DISTINCT code, c_id
                FROM $tbl_session_course sc
                INNER JOIN $courseTable c
                ON sc.c_id = c.id
                WHERE session_id= $session_id";

        $result = Database::query($sql);

        $courses = array();
        while ($row = Database::fetch_array($result)) {
            $courses[$row['code']] = $row;
        }

        return $courses;
    }

    /**
     * Count the number of documents that an user has uploaded to a course
     * @param    int|array   Student id(s)
     * @param    string      Course code
     * @param    int         Session id (optional),
     * if param $session_id is null(default)
     * return count of assignments including sessions, 0 = session is not filtered
     * @return    int        Number of documents
     */
    public static function count_student_uploaded_documents($student_id, $course_code, $session_id = null)
    {
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
                $student_id = intval($student_id);
                $condition_user = " AND ip.insert_user_id = '$student_id' ";
            }

            $condition_session = null;
            if (isset($session_id)) {
                $session_id = intval($session_id);
                $condition_session = " AND pub.session_id = $session_id ";
            }

            $sql = "SELECT count(ip.tool) AS count
                    FROM $tbl_item_property ip
                    INNER JOIN $tbl_document pub
                    ON (ip.ref = pub.id AND ip.c_id = pub.c_id)
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
     * Count assignments per student
     * @param $student_id
     * @param null $course_code
     * @param null $session_id
     * @return int Count of assignments
     * @internal param array|int $Student id(s)
     * @internal param Course $string code
     * @internal param Session $int id (optional),
     * if param $session_id is null(default) return count of assignments
     * including sessions, 0 = session is not filtered
     */
    public static function count_student_assignments($student_id, $course_code = null, $session_id = null)
    {
        if (empty($student_id)) {
            return 0;
        }

        $conditions = array();

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
            $student_id = intval($student_id);
            $conditions[] = " ip.insert_user_id = '$student_id' ";
        }
        if (isset($session_id)) {
            $session_id = intval($session_id);
            $conditions[] = " pub.session_id = $session_id ";
        }

        $conditions[] = ' pub.active <> 2 ';

        $conditionToString = implode('AND', $conditions);

        $sql = "SELECT count(ip.tool) as count
                FROM $tbl_item_property ip
                INNER JOIN $tbl_student_publication pub
                ON (ip.ref = pub.id AND ip.c_id = pub.c_id)
                WHERE
                    ip.tool='work' AND
                    $conditionToString";
        $rs = Database::query($sql);
        $row = Database::fetch_array($rs, 'ASSOC');
        return $row['count'];
    }

    /**
     * Count messages per student inside forum tool
     * @param    int|array        Student id
     * @param    string    Course code
     * @param    int        Session id (optional), if param $session_id is
     * null(default) return count of messages including sessions, 0 = session is not filtered
     * @return    int        Count of messages
     */
    public static function count_student_messages($student_id, $courseCode = null, $session_id = null)
    {
        if (empty($student_id)) {
            return 0;
        }

        // Table definition.
        $tbl_forum_post = Database::get_course_table(TABLE_FORUM_POST);
        $tbl_forum = Database::get_course_table(TABLE_FORUM);

        $conditions = array();
        if (is_array($student_id)) {
            $studentList = array_map('intval', $student_id);
            $conditions[] = " post.poster_id IN ('".implode("','", $studentList)."') ";
        } else {
            $student_id = intval($student_id);
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

        require_once api_get_path(SYS_CODE_PATH).'forum/forumconfig.inc.php';
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
     * This function counts the number of post by course
     * @param      string     Course code
     * @param    int        Session id (optional), if param $session_id is
     * null(default) it'll return results including sessions,
     * 0 = session is not filtered
     * @param int $groupId
     * @return    int     The number of post by course
     */
    public static function count_number_of_posts_by_course($course_code, $session_id = null, $groupId = 0)
    {
        $courseInfo = api_get_course_info($course_code);
        if (!empty($courseInfo)) {
            $tbl_posts = Database::get_course_table(TABLE_FORUM_POST);
            $tbl_forums = Database::get_course_table(TABLE_FORUM);

            $condition_session = '';
            if (isset($session_id)) {
                $session_id = intval($session_id);
                $condition_session = api_get_session_condition($session_id, true, false, 'f.session_id');
            }

            $course_id = $courseInfo['real_id'];
            $groupId = intval($groupId);
            if (!empty($groupId)) {
                $groupCondition = " i.to_group_id = $groupId  ";
            } else {
                $groupCondition = " (i.to_group_id = 0 OR i.to_group_id IS NULL) ";
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
        } else {
            return null;
        }
    }

    /**
     * This function counts the number of threads by course
     * @param      string     Course code
     * @param    int        Session id (optional),
     * if param $session_id is null(default) it'll return results including
     * sessions, 0 = session is not filtered
     * @param int $groupId
     * @return    int     The number of threads by course
     */
    public static function count_number_of_threads_by_course($course_code, $session_id = null, $groupId = 0)
    {
        $course_info = api_get_course_info($course_code);
        if (empty($course_info)) {
            return null;
        }

        $course_id = $course_info['real_id'];
        $tbl_threads = Database::get_course_table(TABLE_FORUM_THREAD);
        $tbl_forums = Database::get_course_table(TABLE_FORUM);

        $condition_session = '';
        if (isset($session_id)) {
            $session_id = intval($session_id);
            $condition_session = ' AND f.session_id = '.$session_id;
        }

        $groupId = intval($groupId);

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
        } else {

            return null;
        }
    }

    /**
     * This function counts the number of forums by course
     * @param      string     Course code
     * @param    int        Session id (optional),
     * if param $session_id is null(default) it'll return results
     * including sessions, 0 = session is not filtered
     * @param int $groupId
     * @return    int     The number of forums by course
     */
    public static function count_number_of_forums_by_course($course_code, $session_id = null, $groupId = 0)
    {
        $course_info = api_get_course_info($course_code);
        if (empty($course_info)) {
            return null;
        }
        $course_id = $course_info['real_id'];

        $condition_session = '';
        if (isset($session_id)) {
             $session_id = intval($session_id);
             $condition_session = ' AND f.session_id = '.$session_id;
        }

        $groupId = intval($groupId);
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
        } else {
            return null;
        }
    }

    /**
     * This function counts the chat last connections by course in x days
     * @param      string     Course code
     * @param      int     Last x days
     * @param    int        Session id (optional)
     * @return     int     Chat last connections by course in x days
     */
    public static function chat_connections_during_last_x_days_by_course($course_code, $last_days, $session_id = 0)
    {
        $course_info = api_get_course_info($course_code);
        if (empty($course_info)) {
            return null;
        }
        $course_id = $course_info['real_id'];

        //protect data
        $last_days   = intval($last_days);
        $session_id  = intval($session_id);
        $tbl_stats_access = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ACCESS);
        $now = api_get_utc_datetime();
        $sql = "SELECT count(*) FROM $tbl_stats_access
                WHERE
                    DATE_SUB('$now',INTERVAL $last_days DAY) <= access_date AND
                    c_id = '$course_id' AND
                    access_tool='".TOOL_CHAT."' AND
                    access_session_id='$session_id' ";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $row = Database::fetch_row($result);
            $count = $row[0];
            return $count;
        } else {
            return null;
        }
    }

    /**
     * This function gets the last student's connection in chat
     * @param      int     Student id
     * @param      string     Course code
     * @param    int        Session id (optional)
     * @return     string    datetime formatted without day (e.g: February 23, 2010 10:20:50 )
     */
    public static function chat_last_connection($student_id, $courseId, $session_id = 0)
    {
        $student_id = intval($student_id);
        $courseId = intval($courseId);
        $session_id = intval($session_id);
        $date_time  = '';

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
     * Get count student's visited links
     * @param    int $student_id Student id
     * @param    int $courseId
     * @param    int $session_id Session id (optional)
     * @return    int        count of visited links
     */
    public static function count_student_visited_links($student_id, $courseId, $session_id = 0)
    {
        $student_id  = intval($student_id);
        $courseId = intval($courseId);
        $session_id  = intval($session_id);

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
     * Get count student downloaded documents
     * @param    int        Student id
     * @param    int    $courseId
     * @param    int        Session id (optional)
     * @return    int        Count downloaded documents
     */
    public static function count_student_downloaded_documents($student_id, $courseId, $session_id = 0)
    {
        $student_id  = intval($student_id);
        $courseId = intval($courseId);
        $session_id  = intval($session_id);

        // table definition
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);

        $sql = 'SELECT 1
                FROM ' . $table.'
                WHERE down_user_id = '.$student_id.'
                AND c_id  = "'.$courseId.'"
                AND down_session_id = '.$session_id.' ';
        $rs = Database::query($sql);

        return Database::num_rows($rs);
    }

    /**
     * Get course list inside a session from a student
     * @param    int        $user_id Student id
     * @param    int        $id_session Session id (optional)
     * @return    array    Courses list
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
        $courses = array();
        while ($row = Database::fetch_array($result)) {
            $courses[$row['code']] = $row['code'];
        }

        return $courses;
    }

    /**
     * Get inactive students in course
     * @param    int   $courseId
     * @param    string  $since  Since login course date (optional, default = 'never')
     * @param    int        $session_id    (optional)
     * @return    array    Inactive users
     */
    public static function getInactiveStudentsInCourse($courseId, $since = 'never', $session_id = 0)
    {
        $tbl_track_login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $table_course_rel_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
        $now = api_get_utc_datetime();
        $courseId = intval($courseId);

        if (empty($courseId)) {
            return false;
        }

        if (empty($session_id)) {
            $inner = '
                INNER JOIN '.$table_course_rel_user.' course_user
                ON course_user.user_id = stats_login.user_id AND course_user.c_id = c.id
            ';
        } else {
            $inner = '
                    INNER JOIN '.$tbl_session_course_user.' session_course_user
                    ON
                        c.id = session_course_user.c_id AND
                        session_course_user.session_id = '.intval($session_id).' AND
                        session_course_user.user_id = stats_login.user_id ';
        }

        $sql = 'SELECT stats_login.user_id, MAX(login_course_date) max_date
                FROM '.$tbl_track_login.' stats_login
                INNER JOIN '.$tableCourse.' c
                ON (c.id = stats_login.c_id)
                '.$inner.'
                WHERE c.id = '.$courseId.'
                GROUP BY stats_login.user_id
                HAVING DATE_SUB( "' . $now.'", INTERVAL '.$since.' DAY) > max_date ';

        if ($since == 'never') {
            if (empty($session_id)) {
                $sql = 'SELECT course_user.user_id
                        FROM ' . $table_course_rel_user.' course_user
                        LEFT JOIN ' . $tbl_track_login.' stats_login
                        ON course_user.user_id = stats_login.user_id AND
                        relation_type<>' . COURSE_RELATION_TYPE_RRHH.'
                        INNER JOIN ' . $tableCourse.' c
                        ON (c.id = course_user.c_id)
                        WHERE
                            course_user.c_id = ' . $courseId.' AND
                            stats_login.login_course_date IS NULL
                        GROUP BY course_user.user_id';
            } else {
                $sql = 'SELECT session_course_user.user_id
                        FROM '.$tbl_session_course_user.' session_course_user
                        LEFT JOIN ' . $tbl_track_login.' stats_login
                        ON session_course_user.user_id = stats_login.user_id
                        INNER JOIN ' . $tableCourse.' c
                        ON (c.id = session_course_user.c_id)
                        WHERE
                            session_course_user.c_id = ' . $courseId.' AND
                            stats_login.login_course_date IS NULL
                        GROUP BY session_course_user.user_id';
            }
        }

        $rs = Database::query($sql);
        $inactive_users = array();
        while ($user = Database::fetch_array($rs)) {
            $inactive_users[] = $user['user_id'];
        }

        return $inactive_users;
    }

    /**
     * Get count login per student
     * @param    int    $student_id    Student id
     * @param    int    $courseId
     * @param    int    $session_id    Session id (optional)
     * @return    int        count login
     */
    public static function count_login_per_student($student_id, $courseId, $session_id = 0)
    {
        $student_id  = intval($student_id);
        $courseId = intval($courseId);
        $session_id  = intval($session_id);
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ACCESS);

        $sql = 'SELECT '.$student_id.'
                FROM ' . $table.'
                WHERE
                    access_user_id=' . $student_id.' AND
                    c_id="' . $courseId.'" AND
                    access_session_id = "'.$session_id.'" ';

        $rs = Database::query($sql);
        $nb_login = Database::num_rows($rs);

        return $nb_login;
    }

    /**
     * Get students followed by a human resources manager
     * @param    int        Drh id
     * @return    array    Student list
     */
    public static function get_student_followed_by_drh($hr_dept_id)
    {
        $hr_dept_id = intval($hr_dept_id);
        $a_students = array();
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);

        $sql = 'SELECT DISTINCT user_id FROM '.$tbl_user.' as user
                WHERE hr_dept_id='.$hr_dept_id;
        $rs = Database::query($sql);

        while ($user = Database::fetch_array($rs)) {
            $a_students[$user['user_id']] = $user['user_id'];
        }

        return $a_students;
    }



    /**
     * get count clicks about tools most used by course
     * @param    int      $courseId
     * @param    int        Session id (optional),
     * if param $session_id is null(default) it'll return results
     * including sessions, 0 = session is not filtered
     * @return    array     tools data
     */
    public static function get_tools_most_used_by_course($courseId, $session_id = null)
    {
        $courseId = intval($courseId);
        $data = array();
        $TABLETRACK_ACCESS = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
        $condition_session     = '';
        if (isset($session_id)) {
            $session_id = intval($session_id);
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
     * Get total clicks
     * THIS FUNCTION IS NOT BEEN USED, IT WAS MEANT TO BE USE WITH track_e_course_access.date_from and track_e_course_access.date_to,
     * BUT NO ROW MATCH THE CONDITION, IT SHOULD BE FINE TO USE IT WHEN YOU USE USER DEFINED DATES AND NO CHAMILO DATES
     * @param   int     User Id
     * @param   int     Course Id
     * @param   int     Session Id (optional), if param $session_id is 0 (default) it'll return results including sessions, 0 = session is not filtered
     * @param   string  Date from
     * @param   string  Date to
     * @return  array   Data
     * @author  César Perales cesar.perales@beeznest.com 2014-01-16
     */
    public static function get_total_clicks($userId, $courseId, $sessionId = 0, $date_from = '', $date_to = '')
    {
        $course = api_get_course_info_by_id($courseId);
        $tables = array(
            TABLE_STATISTIC_TRACK_E_LASTACCESS => array(
                'course'    => 'c_id',
                'session'   => 'access_session_id',
                'user'      => 'access_user_id',
                'start_date'=> 'access_date',
            ),
            TABLE_STATISTIC_TRACK_E_ACCESS => array(
                'course'    => 'c_id',
                'session'   => 'access_session_id',
                'user'      => 'access_user_id',
                'start_date'=> 'access_date',
            ),
            #TABLE_STATISTIC_TRACK_E_LOGIN, array(,, 'login_date', 'logout_date');
            TABLE_STATISTIC_TRACK_E_DOWNLOADS => array(
                'course'    => 'c_id',
                'session'   => 'down_session_id',
                'user'      => 'down_user_id',
                'start_date'=> 'down_date',
                ),
            TABLE_STATISTIC_TRACK_E_LINKS => array(
                'course'    => 'c_id',
                'session'   => 'links_session_id',
                'user'      => 'links_user_id',
                'start_date'=> 'links_date',
            ),
            TABLE_STATISTIC_TRACK_E_ONLINE => array(
                'course'    => 'c_id',
                'session'   => 'session_id',
                'user'      => 'login_user_id',
                'start_date'=> 'login_date',
            ),
            #TABLE_STATISTIC_TRACK_E_HOTPOTATOES,
            /*TABLE_STATISTIC_TRACK_E_COURSE_ACCESS => array(
                'course'    => 'c_id',
                'session'   => 'session_id',
                'user'      => 'user_id',
                'start_date'=> 'login_course_date',
                'end_date'  => 'logout_course_date',
                ),*/
            TABLE_STATISTIC_TRACK_E_EXERCISES => array(
                'course'    => 'c_id',
                'session'   => 'session_id',
                'user'      => 'exe_user_id',
                'start_date'=> 'exe_date',
            ),
            TABLE_STATISTIC_TRACK_E_ATTEMPT => array(
                'course'    => 'c_id',
                'session'   => 'session_id',
                'user'      => 'user_id',
                'start_date'=> 'tms',
            ),
            #TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING,
            #TABLE_STATISTIC_TRACK_E_DEFAULT,
            TABLE_STATISTIC_TRACK_E_UPLOADS => array(
                'course'    => 'c_id',
                'session'   => 'upload_session_id',
                'user'      => 'upload_user_id',
                'start_date'=> 'upload_date',
            ),
        );

        foreach ($tables as $tableName => $fields) {
            //If session is defined, add it to query
            $where = '';
            if (isset($sessionId) && !empty($sessionId)) {
                $sessionField = $fields['session'];
                $where .= " AND $sessionField = $sessionId";
            }

            //filter by date
            if (!empty($date_from) && !empty($date_to)) {
                $fieldStartDate = $fields['start_date'];
                if (!isset($fields['end_date'])) {
                    $where .= sprintf(" AND ($fieldStartDate BETWEEN '%s' AND '%s' )", $date_from, $date_to);
                } else {
                    $fieldEndDate = $fields['end_date'];
                    $where .= sprintf(" AND fieldStartDate >= '%s'
                        AND $fieldEndDate <= '%s'", $date_from, $date_to);
                }
            }

            //query
            $sql = "SELECT %s as user, count(*) as total
                FROM %s
                WHERE %s = '%s'
                AND %s = %s
                $where
                GROUP BY %s";
            $sql = sprintf($sql,
                $fields['user'], //user field
                $tableName, //FROM
                $fields['course'], //course condition
                $course['real_id'], //course condition
                $fields['user'], //user condition
                $userId, //user condition
                $fields['user']     //GROUP BY
                );
            $rs = Database::query($sql);

            //iterate query
            if (Database::num_rows($rs) > 0) {
                while ($row = Database::fetch_array($rs)) {
                    $data[$row['user']] = (isset($data[$row['user']])) ? $data[$row['user']] + $row[total] : $row['total'];
                }
            }
        }

        return $data;
    }

    /**
     * get documents most downloaded by course
     * @param      string     Course code
     * @param    int        Session id (optional),
     * if param $session_id is null(default) it'll return results including
     * sessions, 0 = session is not filtered
     * @param    int        Limit (optional, default = 0, 0 = without limit)
     * @return    array     documents downloaded
     */
    public static function get_documents_most_downloaded_by_course($course_code, $session_id = 0, $limit = 0)
    {
        //protect data
        $courseId = api_get_course_int_id($course_code);
        $data = array();

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
     * get links most visited by course
     * @param      string     Course code
     * @param    int        Session id (optional),
     * if param $session_id is null(default) it'll
     * return results including sessions, 0 = session is not filtered
     * @return    array     links most visited
     */
    public static function get_links_most_visited_by_course($course_code, $session_id = null)
    {
        $course_code = Database::escape_string($course_code);
        $course_info = api_get_course_info($course_code);
        $course_id = $course_info['real_id'];
        $data = array();

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
     * Shows the user progress (when clicking in the Progress tab)
     *
     * @param int $user_id
     * @param int $session_id
     * @param string $extra_params
     * @param bool $show_courses
     * @param bool $showAllSessions
     *
     * @return string
     */
    public static function show_user_progress(
        $user_id,
        $session_id = 0,
        $extra_params = '',
        $show_courses = true,
        $showAllSessions = true
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

        $user_id = intval($user_id);
        $session_id = intval($session_id);
        $urlId = api_get_current_access_url_id();

        if (api_is_multiple_url_enabled()) {
            $sql = "SELECT c.code, title
                    FROM $tbl_course_user cu
                    INNER JOIN $tbl_course c
                    ON (cu.c_id = c.id)
                    INNER JOIN $tbl_access_rel_course a
                    ON (a.c_id = c.id)
                    WHERE
                        cu.user_id = $user_id AND
                        relation_type<> ".COURSE_RELATION_TYPE_RRHH." AND
                        access_url_id = ".$urlId."
                    ORDER BY title";
        } else {
            $sql = "SELECT c.code, title
                    FROM $tbl_course_user u
                    INNER JOIN $tbl_course c ON (c_id = c.id)
                    WHERE
                        u.user_id= $user_id AND
                        relation_type<>".COURSE_RELATION_TYPE_RRHH."
                    ORDER BY title";
        }

        $rs = Database::query($sql);
        $courses = $course_in_session = $temp_course_in_session = array();
        while ($row = Database::fetch_array($rs, 'ASSOC')) {
            $courses[$row['code']] = $row['title'];
        }

        $orderBy = " ORDER BY name ";
        $extraInnerJoin = null;

        if (SessionManager::orderCourseIsEnabled() && !empty($session_id)) {
            $orderBy = " ORDER BY s.id, position ";
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
        $simple_session_array = array();
        while ($row = Database::fetch_array($rs)) {
            $course_info = api_get_course_info($row['code']);
            $temp_course_in_session[$row['session_id']]['course_list'][$course_info['real_id']] = $course_info;
            $temp_course_in_session[$row['session_id']]['name'] = $row['name'];
            $simple_session_array[$row['session_id']] = $row['name'];
        }

        foreach ($simple_session_array as $my_session_id => $session_name) {
            $course_list = $temp_course_in_session[$my_session_id]['course_list'];
            $my_course_data = array();
            foreach ($course_list as $course_data) {
                $my_course_data[$course_data['id']] = $course_data['title'];
            }

            if (empty($session_id)) {
                $my_course_data = utf8_sort($my_course_data);
            }

            $final_course_data = array();
            foreach ($my_course_data as $course_id => $value) {
                if (isset($course_list[$course_id])) {
                    $final_course_data[$course_id] = $course_list[$course_id];
                }
            }
            $course_in_session[$my_session_id]['course_list'] = $final_course_data;
            $course_in_session[$my_session_id]['name'] = $session_name;
        }

        $html = '';

        // Course list
        if ($show_courses) {
            if (!empty($courses)) {
                $html .= Display::page_subheader(
                    Display::return_icon('course.png', get_lang('MyCourses'), array(), ICON_SIZE_SMALL).' '.get_lang('MyCourses')
                );
                $html .= '<div class="table-responsive">';
                $html .= '<table class="table table-striped table-hover">';
                $html .= '<thead>';
                //'.Display::tag('th', get_lang('Score').Display::return_icon('info3.gif', get_lang('ScormAndLPTestTotalAverage'), array('align' => 'absmiddle', 'hspace' => '3px')), array('class'=>'head')).'
                $html .= '<tr>
                          '.Display::tag('th', get_lang('Course'), array('width'=>'300px')).'
                          '.Display::tag('th', get_lang('TimeSpentInTheCourse')).'
                          '.Display::tag('th', get_lang('Progress')).'
                          '.Display::tag('th', get_lang('BestScore')).'
                          '.Display::tag('th', get_lang('LastConnexion')).'
                          '.Display::tag('th', get_lang('Details')).'
                        </tr>';
                $html .= '</thead><tbody>';

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
                        array(),
                        null,
                        false,
                        false,
                        true
                    );

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
                    $course_url = Display::url($course_title, $url, array('target'=>SESSION_LINK_TARGET));
                    $html .= '<td>'.$course_url.'</td>';
                    $html .= '<td align="center">'.$time.'</td>';
                    $html .= '<td align="center">'.$progress.'</td>';
                    $html .= '<td align="center">';
                    if (empty($bestScore)) {
                        $html .= '-';
                    } else {
                        $html .= $bestScore.'%';
                    }

                    $html .= '</td>';
                    $html .= '<td align="center">'.$last_connection.'</td>';
                    $html .= '<td align="center">';
                    if (isset($_GET['course']) &&
                        $course_code == $_GET['course'] &&
                        empty($_GET['session_id'])
                    ) {
                        $html .= '<a href="#course_session_header">';
                        $html .= Display::return_icon('2rightarrow_na.png', get_lang('Details'));
                    } else {
                        $html .= '<a href="'.api_get_self().'?course='.$course_code.$extra_params.'#course_session_header">';
                        $html .= Display::return_icon('2rightarrow.png', get_lang('Details'));
                    }
                    $html .= '</a>';
                    $html .= '</td></tr>';
                }
                $html .= '</tbody></table>';
                $html .= '</div>';
            }
        }

        // Session list
        if (!empty($course_in_session)) {
            $main_session_graph = '';
            //Load graphics only when calling to an specific session
            $session_graph = array();
            $all_exercise_graph_name_list = array();
            $my_results = array();
            $all_exercise_graph_list = array();
            $all_exercise_start_time = array();

            foreach ($course_in_session as $my_session_id => $session_data) {
                $course_list = $session_data['course_list'];
                $user_count = count(SessionManager::get_users_by_session($my_session_id));
                $exercise_graph_name_list = array();
                $exercise_graph_list = array();

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
                        $exercise_obj = new Exercise($course_data['id']);
                        $exercise_obj->read($exercise_data['id']);
                        // Exercise is not necessary to be visible to show results check the result_disable configuration instead
                        //$visible_return = $exercise_obj->is_visible();
                        if ($exercise_data['results_disabled'] == 0 || $exercise_data['results_disabled'] == 2) {
                            $best_average = intval(
                                ExerciseLib::get_best_average_score_by_exercise(
                                    $exercise_data['id'],
                                    $course_data['id'],
                                    $my_session_id,
                                    $user_count
                                )
                            );

                            $exercise_graph_list[] = $best_average;
                            $all_exercise_graph_list[] = $best_average;

                            $user_result_data = ExerciseLib::get_best_attempt_by_user(
                                api_get_user_id(),
                                $exercise_data['id'],
                                $course_data['real_id'],
                                $my_session_id
                            );

                            $score = 0;
                            if (!empty($user_result_data['exe_weighting']) && intval($user_result_data['exe_weighting']) != 0) {
                                $score = intval($user_result_data['exe_result'] / $user_result_data['exe_weighting'] * 100);
                            }
                            $time = api_strtotime($exercise_data['start_time']) ? api_strtotime($exercise_data['start_time'], 'UTC') : 0;
                            $all_exercise_start_time[] = $time;
                            $my_results[] = $score;
                            if (count($exercise_list) <= 10) {
                                $title = cut($course_data['title'], 30)." \n ".cut($exercise_data['title'], 30);
                                $exercise_graph_name_list[] = $title;
                                $all_exercise_graph_name_list[] = $title;
                            } else {
                                // if there are more than 10 results, space becomes difficult to find, so only show the title of the exercise, not the tool
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
                $final_all_exercise_graph_name_list = array();
                $my_results_final = array();
                $final_all_exercise_graph_list = array();

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
                get_lang('Sessions'),
                array(),
                ICON_SIZE_SMALL
            );

            $anchor = Display::url('', '', ['name' => 'course_session_header']);
            $html .= $anchor.Display::page_subheader(
                $sessionIcon.' '.get_lang('Sessions')
            );

            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-striped table-hover">';
            $html .= '<thead>';
            $html .= '<tr>
                  '.Display::tag('th', get_lang('Session'), array('width'=>'300px')).'
                  '.Display::tag('th', get_lang('PublishedExercises'), array('width'=>'300px')).'
                  '.Display::tag('th', get_lang('NewExercises')).'
                  '.Display::tag('th', get_lang('AverageExerciseResult')).'
                  '.Display::tag('th', get_lang('Details')).'
                  </tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            foreach ($course_in_session as $my_session_id => $session_data) {
                $course_list  = $session_data['course_list'];
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
                $stats_array = array();

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

                $html .= Display::tag('td', Display::url($session_name, $url, array('target'=>SESSION_LINK_TARGET)));
                $html .= Display::tag('td', $all_exercises);
                $html .= Display::tag('td', $all_unanswered_exercises_by_user);
                //$html .= Display::tag('td', $all_done_exercise);
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
                array(
                    'id' => 'session_graph',
                    'class' => 'chart-session',
                    'style' => 'position:relative; text-align: center;',
                )
            );

            // Checking selected session.
            if (isset($_GET['session_id'])) {
                $session_id_from_get = intval($_GET['session_id']);
                $session_data = $course_in_session[$session_id_from_get];
                $course_list = $session_data['course_list'];

                $html .= '<a name= "course_session_list"></a>';
                $html .= Display::tag('h3', $session_data['name'].' - '.get_lang('CourseList'));

                $html .= '<div class="table-responsive">';
                $html .= '<table class="table table-hover table-striped">';

                $columnHeaders = [
                    'course_title' => [
                        get_lang('Course'),
                        array('width'=>'300px')
                    ],
                    'published_exercises' => [
                        get_lang('PublishedExercises')
                    ],
                    'new_exercises' => [
                        get_lang('NewExercises'),
                    ],
                    'my_average' => [
                        get_lang('MyAverage'),
                    ],
                    'average_exercise_result'  => [
                        get_lang('AverageExerciseResult'),
                    ],
                    'time_spent'  => [
                        get_lang('TimeSpentInTheCourse'),
                    ],
                    'lp_progress'  => [
                        get_lang('LPProgress'),
                    ],
                    'score'  => [
                        get_lang('Score').Display::return_icon('info3.gif', get_lang('ScormAndLPTestTotalAverage'), array('align' => 'absmiddle', 'hspace' => '3px')),
                    ],
                    'best_score'  => [
                        get_lang('BestScore'),
                    ],
                    'last_connection'  => [
                        get_lang('LastConnexion'),
                    ],
                    'details'  => [
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
                    $course_code  = $course_data['code'];
                    $course_title = $course_data['title'];
                    $courseInfo = api_get_course_info($course_code);
                    $courseId = $courseInfo['real_id'];

                    // All exercises in the course @todo change for a real count
                    $exercises = ExerciseLib::get_all_exercises($course_data, $session_id_from_get);
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
                        array(),
                        $session_id_from_get,
                        false,
                        false,
                        true
                    );

                    $stats_array[$course_code] = array(
                        'exercises' => $count_exercises,
                        'unanswered_exercises_by_user' => $unanswered_exercises,
                        'done_exercises' => $done_exercises,
                        'average' => $average,
                        'my_average' => $my_average,
                        'best_score' => $bestScore
                    );

                    $last_connection = self::get_last_connection_date_on_the_course(
                        $user_id,
                        $courseInfo,
                        $session_id_from_get
                    );

                    $progress = self::get_avg_student_progress(
                        $user_id,
                        $course_code,
                        array(),
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
                        array(),
                        $session_id_from_get
                    );
                    $courseCodeFromGet = isset($_GET['course']) ? $_GET['course'] : null;

                    if ($course_code == $courseCodeFromGet && $_GET['session_id'] == $session_id_from_get) {
                        $html .= '<tr class="row_odd" style="background-color:#FBF09D" >';
                    } else {
                        $html .= '<tr class="row_even">';
                    }

                    $url = api_get_course_url($course_code, $session_id_from_get);
                    $course_url = Display::url($course_title, $url, array('target' => SESSION_LINK_TARGET));

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

                    if ($course_code == $courseCodeFromGet && $_GET['session_id'] == $session_id_from_get) {
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

        return $html;
    }

    /**
     * Shows the user detail progress (when clicking in the details link)
     * @param   int     $user_id
     * @param   string  $course_code
     * @param   int     $session_id
     * @return  string  html code
     */
    public static function show_course_detail($user_id, $course_code, $session_id)
    {
        $html = '';
        if (isset($course_code)) {
            $user_id = intval($user_id);
            $session_id = intval($session_id);
            $course = Database::escape_string($course_code);
            $course_info = api_get_course_info($course);

            $html .= '<a name="course_session_data"></a>';
            $html .= Display::page_subheader($course_info['title']);
            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-striped table-hover">';

            //Course details
            $html .= '
                <thead>
                <tr>
                <th>'.get_lang('Exercises').'</th>
                <th>'.get_lang('Attempts').'</th>
                <th>'.get_lang('BestAttempt').'</th>
                <th>'.get_lang('Ranking').'</th>
                <th>'.get_lang('BestResultInCourse').'</th>
                <th>'.get_lang('Statistics').' '.Display::return_icon('info3.gif', get_lang('OnlyBestResultsPerStudent'), array('align' => 'absmiddle', 'hspace' => '3px')).'</th>
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

            $to_graph_exercise_result = array();
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
                            array('target' => SESSION_LINK_TARGET)
                        );
                    } elseif ($exercices['active'] == -1) {
                        $exercices['title'] = sprintf(get_lang('XParenthesisDeleted'), $exercices['title']);
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

                        $to_graph_exercise_result[$exercices['id']] = array(
                            'title' => $exercices['title'],
                            'data' => $best_exercise_stats
                        );

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
                                $best_score_data['exe_result'],
                                $best_score_data['exe_weighting']
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
                                $score = $exercise_stat['exe_result'];
                                $weighting = $exercise_stat['exe_weighting'];
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
                                    $user_list = array($user_list);
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
                            array('id'=>'main_graph_'.$exercices['id'], 'class'=>'dialog', 'style'=>'display:none')
                        );

                        if (empty($graph)) {
                            $graph = '-';
                        } else {
                            $graph = Display::url(
                                '<img src="'.$graph.'" >',
                                $normal_graph,
                                array(
                                    'id' => $exercices['id'],
                                    'class' => 'expand-image',
                                )
                            );
                        }

                        $html .= Display::tag('td', $attempts, array('align'=>'center'));
                        $html .= Display::tag('td', $percentage_score_result, array('align'=>'center'));
                        $html .= Display::tag('td', $position, array('align'=>'center'));
                        $html .= Display::tag('td', $best_score, array('align'=>'center'));
                        $html .= Display::tag('td', $graph, array('align'=>'center'));
                        //$html .= Display::tag('td', $latest_attempt_url,       array('align'=>'center', 'width'=>'25'));

                    } else {
                        // Exercise configuration NO results
                        $html .= Display::tag('td', $attempts, array('align'=>'center'));
                        $html .= Display::tag('td', '-', array('align'=>'center'));
                        $html .= Display::tag('td', '-', array('align'=>'center'));
                        $html .= Display::tag('td', '-', array('align'=>'center'));
                        $html .= Display::tag('td', '-', array('align'=>'center'));
                    }
                    $html .= '</tr>';
                }
            } else {
                $html .= '<tr><td colspan="5" align="center">'.get_lang('NoEx').'</td></tr>';
            }
            $html .= '</tbody></table></div>';

            $columnHeaders = [
                'lp' => get_lang('LearningPath'),
                'time' => get_lang('LatencyTimeSpent'),
                'progress' => get_lang('Progress'),
                'score' => get_lang('Score'),
                'best_score' => get_lang('BestScore'),
                'last_connection' => get_lang('LastConnexion'),
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
                $course_info['code'],
                $session_id,
                'lp.publicatedOn ASC',
                true,
                null,
                true
            );

            $lp_list = $list->get_flat_list();

            if (!empty($lp_list) > 0) {
                foreach ($lp_list as $lp_id => $learnpath) {
                    $progress = self::get_avg_student_progress(
                        $user_id,
                        $course,
                        array($lp_id),
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
                        array($lp_id),
                        $session_id
                    );
                    $percentage_score = self::get_avg_student_score(
                        $user_id,
                        $course,
                        array($lp_id),
                        $session_id
                    );

                    $bestScore = self::get_avg_student_score(
                        $user_id,
                        $course,
                        array($lp_id),
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
                        $last_connection = api_convert_and_format_date($last_connection_in_lp, DATE_TIME_FORMAT_LONG);
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
                                    array('target' => SESSION_LINK_TARGET)
                                )
                            );
                        }
                    }


                    if (in_array('time', $columnHeadersKeys)) {
                        $html .= Display::tag(
                            'td',
                            $time_spent_in_lp,
                            array('align' => 'center')
                        );
                    }

                    if (in_array('progress', $columnHeadersKeys)) {
                        $html .= Display::tag(
                            'td',
                            $progress,
                            array('align' => 'center')
                        );
                    }

                    if (in_array('score', $columnHeadersKeys)) {
                        $html .= Display::tag('td', $percentage_score);
                    }
                    if (in_array('best_score', $columnHeadersKeys)) {
                        $html .= Display::tag('td', $bestScore);
                    }

                    if (in_array('last_connection', $columnHeadersKeys)) {
                        $html .= Display::tag('td', $last_connection, array('align'=>'center', 'width'=>'180px'));
                    }
                    $html .= '</tr>';
                }
            } else {
                $html .= '<tr>
                        <td colspan="4" align="center">
                            '.get_lang('NoLearnpath').'
                        </td>
                      </tr>';
            }
            $html .= '</tbody></table></div>';

            $html .= self::displayUserSkills($user_id, $course_info['id'], $session_id);
        }

        return $html;
    }

    /**
     * Generates an histogram
     * @param array $names list of exercise names
     * @param array $my_results my results 0 to 100
     * @param array $average average scores 0-100
     * @return string
     */
    static function generate_session_exercise_graph($names, $my_results, $average)
    {
        /* Create and populate the pData object */
        $myData = new pData();
        $myData->addPoints($names, 'Labels');
        $myData->addPoints($my_results, 'Serie1');
        $myData->addPoints($average, 'Serie2');
        $myData->setSerieWeight('Serie1', 1);
        $myData->setSerieTicks('Serie2', 4);
        $myData->setSerieDescription('Labels', 'Months');
        $myData->setAbscissa('Labels');
        $myData->setSerieDescription('Serie1', get_lang('MyResults'));
        $myData->setSerieDescription('Serie2', get_lang('AverageScore'));
        $myData->setAxisUnit(0, '%');
        $myData->loadPalette(api_get_path(SYS_CODE_PATH).'palettes/pchart/default.color', true);
        // Cache definition
        $cachePath = api_get_path(SYS_ARCHIVE_PATH);
        $myCache = new pCache(array('CacheFolder' => substr($cachePath, 0, strlen($cachePath) - 1)));
        $chartHash = $myCache->getHash($myData);

        if ($myCache->isInCache($chartHash)) {
            //if we already created the img
            $imgPath = api_get_path(SYS_ARCHIVE_PATH).$chartHash;
            $myCache->saveFromCache($chartHash, $imgPath);
            $imgPath = api_get_path(WEB_ARCHIVE_PATH).$chartHash;
        } else {
            /* Define width, height and angle */
            $mainWidth = 860;
            $mainHeight = 500;
            $angle = 50;

            /* Create the pChart object */
            $myPicture = new pImage($mainWidth, $mainHeight, $myData);

            /* Turn of Antialiasing */
            $myPicture->Antialias = false;

            /* Draw the background */
            $settings = array('R' => 255, 'G' => 255, 'B' => 255);
            $myPicture->drawFilledRectangle(0, 0, $mainWidth, $mainHeight, $settings);

            /* Add a border to the picture */
            $myPicture->drawRectangle(
                0,
                0,
                $mainWidth - 1,
                $mainHeight - 1,
                array('R' => 0, 'G' => 0, 'B' => 0)
            );

            /* Set the default font */
            $myPicture->setFontProperties(
                array(
                    'FontName' => api_get_path(SYS_FONTS_PATH).'opensans/OpenSans-Regular.ttf',
                    'FontSize' => 10)
            );
            /* Write the chart title */
            $myPicture->drawText(
                $mainWidth / 2,
                30,
                get_lang('ExercisesInTimeProgressChart'),
                array(
                    'FontSize' => 12,
                    'Align' => TEXT_ALIGN_BOTTOMMIDDLE
                )
            );

            /* Set the default font */
            $myPicture->setFontProperties(
                array(
                    'FontName' => api_get_path(SYS_FONTS_PATH).'opensans/OpenSans-Regular.ttf',
                    'FontSize' => 6
                )
            );

            /* Define the chart area */
            $myPicture->setGraphArea(60, 60, $mainWidth - 60, $mainHeight - 150);

            /* Draw the scale */
            $scaleSettings = array(
                'XMargin' => 10,
                'YMargin' => 10,
                'Floating' => true,
                'GridR' => 200,
                'GridG' => 200,
                'GridB' => 200,
                'DrawSubTicks' => true,
                'CycleBackground' => true,
                'LabelRotation' => $angle,
                'Mode' => SCALE_MODE_ADDALL_START0,
            );
            $myPicture->drawScale($scaleSettings);

            /* Turn on Antialiasing */
            $myPicture->Antialias = true;

            /* Enable shadow computing */
            $myPicture->setShadow(
                true,
                array(
                    'X' => 1,
                    'Y' => 1,
                    'R' => 0,
                    'G' => 0,
                    'B' => 0,
                    'Alpha' => 10
                )
            );

            /* Draw the line chart */
            $myPicture->setFontProperties(
                array(
                    'FontName' => api_get_path(SYS_FONTS_PATH).'opensans/OpenSans-Regular.ttf',
                    'FontSize' => 10
                )
            );
            $myPicture->drawSplineChart();
            $myPicture->drawPlotChart(
                array(
                    'DisplayValues' => true,
                    'PlotBorder' => true,
                    'BorderSize' => 1,
                    'Surrounding' => -60,
                    'BorderAlpha' => 80
                )
            );

            /* Write the chart legend */
            $myPicture->drawLegend(
                $mainWidth / 2 + 50,
                50,
                array(
                    'Style' => LEGEND_BOX,
                    'Mode' => LEGEND_HORIZONTAL,
                    'FontR' => 0,
                    'FontG' => 0,
                    'FontB' => 0,
                    'R' => 220,
                    'G' => 220,
                    'B' => 220,
                    'Alpha' => 100
                )
            );

            $myCache->writeToCache($chartHash, $myPicture);
            $imgPath = api_get_path(SYS_ARCHIVE_PATH).$chartHash;
            $myCache->saveFromCache($chartHash, $imgPath);
            $imgPath = api_get_path(WEB_ARCHIVE_PATH).$chartHash;
        }

        $html = '<img src="'.$imgPath.'">';

        return $html;
    }

    /**
     * Returns a thumbnail of the function generate_exercise_result_graph
     * @param  array $attempts
     */
    static function generate_exercise_result_thumbnail_graph($attempts)
    {
        //$exercise_title = $attempts['title'];
        $attempts = $attempts['data'];
        $my_exercise_result_array = $exercise_result = array();
        if (empty($attempts)) {
            return null;
        }

        foreach ($attempts as $attempt) {
            if (api_get_user_id() == $attempt['exe_user_id']) {
                if ($attempt['exe_weighting'] != 0) {
                    $my_exercise_result_array[] = $attempt['exe_result'] / $attempt['exe_weighting'];
                }
            } else {
                if ($attempt['exe_weighting'] != 0) {
                    $exercise_result[] = $attempt['exe_result'] / $attempt['exe_weighting'];
                }
            }
        }

        //Getting best result
        rsort($my_exercise_result_array);
        $my_exercise_result = 0;
        if (isset($my_exercise_result_array[0])) {
            $my_exercise_result = $my_exercise_result_array[0] * 100;
        }

        $max     = 100;
        $pieces  = 5;
        $part    = round($max / $pieces);
        $x_axis = array();
        $final_array = array();
        $my_final_array = array();

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
        $dataSet->normalize(100, "%");
        $dataSet->loadPalette(api_get_path(SYS_CODE_PATH).'palettes/pchart/default.color', true);

        // Cache definition
        $cachePath = api_get_path(SYS_ARCHIVE_PATH);
        $myCache = new pCache(array('CacheFolder' => substr($cachePath, 0, strlen($cachePath) - 1)));
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
            $myPicture->drawRectangle(0, 0, $widthSize - 1, $heightSize - 1, array('R' => 0, 'G' => 0, 'B' => 0));

            /* Set the default font */
            $myPicture->setFontProperties(array('FontName' => api_get_path(SYS_FONTS_PATH).'opensans/OpenSans-Regular.ttf', 'FontSize' => $fontSize));

            /* Do not write the chart title */

            /* Define the chart area */
            $myPicture->setGraphArea(5, 5, $widthSize - 5, $heightSize - 5);

            /* Draw the scale */
            $scaleSettings = array(
                'GridR' => 200,
                'GridG' => 200,
                'GridB' => 200,
                'DrawSubTicks' => true,
                'CycleBackground' => true,
                'Mode' => SCALE_MODE_MANUAL,
                'ManualScale' => array(
                    '0' => array(
                        'Min' => 0,
                        'Max' => 100
                    )
                )
            );
            $myPicture->drawScale($scaleSettings);

            /* Turn on shadow computing */
            $myPicture->setShadow(
                true,
                array(
                    'X' => 1,
                    'Y' => 1,
                    'R' => 0,
                    'G' => 0,
                    'B' => 0,
                    'Alpha' => 10
                )
            );

            /* Draw the chart */
            $myPicture->setShadow(
                true,
                array(
                    'X' => 1,
                    'Y' => 1,
                    'R' => 0,
                    'G' => 0,
                    'B' => 0,
                    'Alpha' => 10
                )
            );
            $settings = array(
                'DisplayValues' => true,
                'DisplaySize' => $fontSize,
                'DisplayR' => 0,
                'DisplayG' => 0,
                'DisplayB' => 0,
                'DisplayOrientation' => ORIENTATION_HORIZONTAL,
                'Gradient' => false,
                'Surrounding' => 5,
                'InnerSurrounding' => 5
            );
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
     * Generates a big graph with the number of best results
     * @param	array
     */
    static function generate_exercise_result_graph($attempts)
    {
        $exercise_title = strip_tags($attempts['title']);
        $attempts       = $attempts['data'];
        $my_exercise_result_array = $exercise_result = array();
        if (empty($attempts)) {
            return null;
        }
        foreach ($attempts as $attempt) {
            if (api_get_user_id() == $attempt['exe_user_id']) {
                if ($attempt['exe_weighting'] != 0) {
                    $my_exercise_result_array[] = $attempt['exe_result'] / $attempt['exe_weighting'];
                }
            } else {
                if ($attempt['exe_weighting'] != 0) {
                    $exercise_result[] = $attempt['exe_result'] / $attempt['exe_weighting'];
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
        $x_axis = array();
        $final_array = array();
        $my_final_array = array();

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
        $dataSet->setSerieDescription('Serie2', get_lang('MyResults'));
        $dataSet->setAbscissa('Serie3');

        $dataSet->setXAxisName(get_lang('Score'));
        $dataSet->normalize(100, "%");

        $dataSet->loadPalette(api_get_path(SYS_CODE_PATH).'palettes/pchart/default.color', true);

        // Cache definition
        $cachePath = api_get_path(SYS_ARCHIVE_PATH);
        $myCache = new pCache(array('CacheFolder' => substr($cachePath, 0, strlen($cachePath) - 1)));
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
            $myPicture->drawRectangle(0, 0, $widthSize - 1, $heightSize - 1, array('R' => 0, 'G' => 0, 'B' => 0));

            /* Set the default font */
            $myPicture->setFontProperties(array('FontName' => api_get_path(SYS_FONTS_PATH).'opensans/OpenSans-Regular.ttf', 'FontSize' => 10));

            /* Write the chart title */
            $myPicture->drawText(
                250,
                20,
                $exercise_title,
                array(
                    'FontSize' => 12,
                    'Align' => TEXT_ALIGN_BOTTOMMIDDLE
                )
            );

            /* Define the chart area */
            $myPicture->setGraphArea(50, 50, $widthSize - 20, $heightSize - 30);

            /* Draw the scale */
            $scaleSettings = array(
                'GridR' => 200,
                'GridG' => 200,
                'GridB' => 200,
                'DrawSubTicks' => true,
                'CycleBackground' => true,
                'Mode' => SCALE_MODE_MANUAL,
                'ManualScale' => array(
                    '0' => array(
                        'Min' => 0,
                        'Max' => 100
                    )
                )
            );
            $myPicture->drawScale($scaleSettings);

            /* Turn on shadow computing */
            $myPicture->setShadow(true, array('X' => 1, 'Y' => 1, 'R' => 0, 'G' => 0, 'B' => 0, 'Alpha' => 10));

            /* Draw the chart */
            $myPicture->setShadow(true, array('X' => 1, 'Y' => 1, 'R' => 0, 'G' => 0, 'B' => 0, 'Alpha' => 10));
            $settings = array(
                'DisplayValues' => true,
                'DisplaySize' => $fontSize,
                'DisplayR' => 0,
                'DisplayG' => 0,
                'DisplayB' => 0,
                'DisplayOrientation' => ORIENTATION_HORIZONTAL,
                'Gradient' => false,
                'Surrounding' => 30,
                'InnerSurrounding' => 25
            );
            $myPicture->drawStackedBarChart($settings);

            $legendSettings = array(
                'Mode' => LEGEND_HORIZONTAL,
                'Style' => LEGEND_NOBORDER,
            );
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
            array(1 => get_lang('Active'), 0 => get_lang('Inactive'))
        );

        $form->addElement(
            'select',
            'sleeping_days',
            get_lang('InactiveDays'),
            array(
                '',
                1 => 1,
                5 => 5,
                15 => 15,
                30 => 30,
                60 => 60,
                90 => 90,
                120 => 120,
            )
        );

        $form->addButtonSearch(get_lang('Search'));

        return $form;
    }

    /**
     * Get the progress of a exercise
     * @param   int $sessionId  The session ID (session.id)
     * @param   int $courseId   The course ID (course.id)
     * @param   int $exerciseId The quiz ID (c_quiz.id)
     * @param   string $date_from
     * @param   string $date_to
     * @param   array   $options    An array of options you can pass to the query (limit, where and order)
     * @return array An array with the data of exercise(s) progress
     */
    public static function get_exercise_progress(
        $sessionId = 0,
        $courseId = 0,
        $exerciseId = 0,
        $date_from = null,
        $date_to = null,
        $options = array()
    ) {
        $sessionId  = intval($sessionId);
        $courseId   = intval($courseId);
        $exerciseId = intval($exerciseId);
        $date_from  = Database::escape_string($date_from);
        $date_to    = Database::escape_string($date_to);
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

        $sessions = array();
        $courses = array();
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
            $courses[$courseId] = array($course['code']);
            $courses[$courseId]['code'] = $course['code'];
            $sessions[$sessionId] = api_get_session_info($sessionId);
        } else {
            //both are empty, not enough data, return an empty array
            return array();
        }
        // Now we have two arrays of courses and sessions with enough data to proceed
        // If no course could be found, we shouldn't return anything.
        // Sessions can be empty (then we only return the pure-course-context results)
        if (count($courses) < 1) {
            return array();
        }

        $data = array();
        // The following loop is less expensive than what it seems:
        // - if a course was defined, then we only loop through sessions
        // - if a session was defined, then we only loop through courses
        // - if a session and a course were defined, then we only loop once
        foreach ($courses as $courseIdx => $courseData) {
            $where = '';
            $whereParams = array();
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
            $userIds = array();
            $questionIds = array();
            $answerIds = array();
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
            $answer = array();
            $question = array();
            while ($rowQuestion = Database::fetch_assoc($resQuestions)) {
                $questionId = $rowQuestion['question_id'];
                $answerId = $rowQuestion['answer_id'];
                $answer[$questionId][$answerId] = array(
                    'position' => $rowQuestion['position'],
                    'question' => $rowQuestion['question'],
                    'answer' => $rowQuestion['answer'],
                    'correct' => $rowQuestion['correct']
                );
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
     * @param User $user
     * @param string $tool
     * @param Course $course
     * @param Session|null $session Optional.
     * @return \Chamilo\CourseBundle\Entity\CStudentPublication|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public static function getLastStudentPublication(
        User $user,
        $tool,
        Course $course,
        Session $session = null
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
                'user' => $user
            ])
            ->getOneOrNullResult();
    }

    /**
     * Get the HTML code for show a block with the achieved user skill on course/session
     * @param int $userId
     * @param int $courseId Optional.
     * @param int $sessionId Optional.
     * @return string
     */
    public static function displayUserSkills($userId, $courseId = 0, $sessionId = 0)
    {
        $userId = intval($userId);
        $courseId = intval($courseId);
        $sessionId = intval($sessionId);

        if (api_get_setting('allow_skills_tool') !== 'true') {
            return '';
        }

        $filter = ['user' => $userId];

        $filter['course'] = $courseId ?: null;
        $filter['session'] = $sessionId ?: null;

        $em = Database::getManager();

        $skillsRelUser = $em->getRepository('ChamiloCoreBundle:SkillRelUser')->findBy($filter);

        $html = '
            <div class="table-responsive">
                <table class="table" id="skillList">
                    <thead>
                        <tr>
                            <th>' . get_lang('AchievedSkills').'</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
        ';

        if (count($skillsRelUser)) {
            $html .= '
                                <div class="scrollbar-inner badges-sidebar">
                                    <ul class="list-unstyled list-badges">
            ';

            foreach ($skillsRelUser as $userSkill) {
                $skill = $userSkill->getSkill();

                $html .= '
                                            <li class="thumbnail">
                                                <a href="' . api_get_path(WEB_PATH).'badge/'.$userSkill->getId().'/user/'.$userId.'" target="_blank">
                                                    <img class="img-responsive" title="' . $skill->getName().'" src="'.$skill->getWebIconPath().'" width="64" height="64">
                                                    <div class="caption">
                                                        <p class="text-center">' . $skill->getName().'</p>
                                                    </div>
                                                </a>
                                            </li>
                ';
            }


            $html .= '
                                    </ul>
                                </div>
            ';
        } else {
            $html .= get_lang('WithoutAchievedSkills');
        }

        $html .= '
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        ';

        return $html;
    }
}

/**
 * @todo move into a proper file
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
                    insert_date as col5,
                    visibility as col6,
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
            $sql .= " ORDER BY col5 DESC ";
        }

        $from = intval($from);
        $number_of_items = intval($number_of_items);

        $sql .= " LIMIT $from, $number_of_items ";

        $res = Database::query($sql);
        $resources = array();
        $thematic_tools = array('thematic', 'thematic_advance', 'thematic_plan');
        while ($row = Database::fetch_array($res)) {
            $ref = $row['ref'];
            $table_name = self::get_tool_name_table($row['col0']);
            $table_tool = Database::get_course_table($table_name['table_name']);

            $id = $table_name['id_tool'];
            $recorset = false;

            if (in_array($row['col0'], array('thematic_plan', 'thematic_advance'))) {
                $tbl_thematic = Database::get_course_table(TABLE_THEMATIC);
                $sql = "SELECT thematic_id FROM $table_tool
                        WHERE c_id = $course_id AND id = $ref";
                $rs_thematic  = Database::query($sql);
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
                    $coach_name   = $obj->username;
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
                $row[6] = api_convert_and_format_date($row['col5'], null, date_default_timezone_get());
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

                    $ip = TrackingUserLog::get_ip_from_user_event($row['user_id'], $row['col5'], true);
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

        return array(
            'table_name' => $table_name,
            'link_tool' => $link_tool,
            'id_tool' => $id_tool
        );
    }

    public static function display_additional_profile_fields()
    {
        // getting all the extra profile fields that are defined by the platform administrator
        $extra_fields = UserManager::get_extra_fields(0, 50, 5, 'ASC');

        // creating the form
        $return = '<form action="courseLog.php" method="get" name="additional_profile_field_form" id="additional_profile_field_form">';

        // the select field with the additional user profile fields (= this is where we select the field of which we want to see
        // the information the users have entered or selected.
        $return .= '<select class="chzn-select" name="additional_profile_field[]" multiple>';
        $return .= '<option value="-">'.get_lang('SelectFieldToAdd').'</option>';
        $extra_fields_to_show = 0;
        foreach ($extra_fields as $key=>$field) {
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
        foreach ($_GET as $key=>$value) {
            if ($key <> 'additional_profile_field') {
                $return .= '<input type="hidden" name="'.Security::remove_XSS($key).'" value="'.Security::remove_XSS($value).'" />';
            }
        }
        // the submit button
        $return .= '<button class="save" type="submit">'.get_lang('AddAdditionalProfileField').'</button>';
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
     * in the sortable table or in the csv or xls export
     *
     * @author    Julio Montoya <gugli100@gmail.com>
     * @param    int field id
     * @param    array list of user ids
     * @return    array
     * @since    Nov 2009
     * @version    1.8.6.2
     */
    public static function get_addtional_profile_information_of_field_by_user($field_id, $users)
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
                    $tag_list = array();
                    foreach ($user_result as $item) {
                        $tag_list[] = $item['tag'];
                    }
                    $return[$user_id][] = implode(', ', $tag_list);
                }
            } else {
                $new_user_array = array();
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
     * Deprecated
     */
    public function count_student_in_course()
    {
        global $nbStudents;
        return $nbStudents;
    }

    public function sort_users($a, $b)
    {
        return strcmp(trim(api_strtolower($a[$_SESSION['tracking_column']])), trim(api_strtolower($b[$_SESSION['tracking_column']])));
    }

    public function sort_users_desc($a, $b)
    {
        return strcmp(trim(api_strtolower($b[$_SESSION['tracking_column']])), trim(api_strtolower($a[$_SESSION['tracking_column']])));
    }

    /**
     * Get number of users for sortable with pagination
     * @return int
     */
    public static function get_number_of_users()
    {
        global $user_ids;
        return count($user_ids);
    }

    /**
     * Get data for users list in sortable with pagination
     * @param $from
     * @param $number_of_items
     * @param $column
     * @param $direction
     * @param $includeInvitedUsers boolean Whether include the invited users
     * @return array
     */
    public static function get_user_data($from, $number_of_items, $column, $direction, $includeInvitedUsers = false)
    {
        global $user_ids, $course_code, $export_csv, $is_western_name_order, $csv_content, $session_id;

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

        if (!in_array($direction, array('ASC', 'DESC'))) {
            $direction = 'ASC';
        }

        $column = intval($column);
        $from = intval($from);
        $number_of_items = intval($number_of_items);

        $sql .= " ORDER BY col$column $direction ";
        $sql .= " LIMIT $from,$number_of_items";

        $res = Database::query($sql);
        $users = array();

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
            $survey_user_list = array();
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

        while ($user = Database::fetch_array($res, 'ASSOC')) {
            $courseInfo = api_get_course_info($course_code);
            $courseId = $courseInfo['real_id'];

            $user['official_code'] = $user['col0'];
            $user['lastname'] = $user['col1'];
            $user['firstname'] = $user['col2'];
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
                array(),
                $session_id
            );

            $avg_student_progress = Tracking::get_avg_student_progress(
                $user['user_id'],
                $course_code,
                array(),
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
                $user['student_score']  = $avg_student_score.'%';
            } else {
                $user['student_score']  = $avg_student_score;
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
                $session_id
            );
            $user['last_connection'] = Tracking::get_last_connection_date_on_the_course(
                $user['user_id'],
                $courseInfo,
                $session_id
            );

            if (empty($session_id)) {
                $user['survey'] = (isset($survey_user_list[$user['user_id']]) ? $survey_user_list[$user['user_id']] : 0).' / '.$total_surveys;
            }

            $user['link'] = '<center>
                             <a href="../mySpace/myStudents.php?student='.$user['user_id'].'&details=true&course='.$course_code.'&origin=tracking_course&id_session='.$session_id.'">
                             '.Display::return_icon('2rightarrow.png').'
                             </a>
                         </center>';

            // store columns in array $users
            $is_western_name_order = api_is_western_name_order();
            $user_row = array();
            $user_row['official_code'] = $user['official_code']; //0
            if ($is_western_name_order) {
                $user_row['firstname'] = $user['firstname'];
                $user_row['lastname'] = $user['lastname'];
            } else {
                $user_row['lastname'] = $user['lastname'];
                $user_row['firstname'] = $user['firstname'];
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
                $data = \System\Session::read('additional_user_profile_info');
                $extraFieldInfo = \System\Session::read('extra_field_info');
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

        \System\Session::erase('additional_user_profile_info');
        \System\Session::erase('extra_field_info');

        return $users;
    }
}

/**
 * @package chamilo.tracking
 */
class TrackingUserLog
{
    /**
     * Displays the number of logins every month for a specific user in a specific course.
     * @param $view
     * @param int $user_id
     * @param int $course_id
     * @param int $session_id
     */
    public static function display_login_tracking_info($view, $user_id, $course_id, $session_id = 0)
    {
        $MonthsLong = $GLOBALS['MonthsLong'];

        // protected data
        $user_id = intval($user_id);
        $session_id = intval($session_id);
        $course_id = Database::escape_string($course_id);

        $track_access_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ACCESS);
        $tempView = $view;
        if (substr($view, 0, 1) == '1') {
            $new_view = substr_replace($view, '0', 0, 1);
            echo "
                <tr>
                    <td valign='top'>
                    <font color='#0000FF'>-&nbsp;&nbsp;&nbsp;</font>" .
                    "<b>".get_lang('LoginsAndAccessTools')."</b>&nbsp;&nbsp;&nbsp;[<a href='".api_get_self()."?uInfo=".$user_id."&view=".Security::remove_XSS($new_view)."'>".get_lang('Close')."</a>]&nbsp;&nbsp;&nbsp;[<a href='userLogCSV.php?".api_get_cidreq()."&uInfo=".Security::remove_XSS($_GET['uInfo'])."&view=10000'>".get_lang('ExportAsCSV')."</a>]
                    </td>
                </tr>
                ";
            echo "<tr><td style='padding-left : 40px;' valign='top'>".get_lang('LoginsDetails')."<br>";

            $sql = "SELECT UNIX_TIMESTAMP(access_date), count(access_date)
                        FROM $track_access_table
                        WHERE access_user_id = $user_id
                        AND c_id = $course_id
                        AND access_session_id = $session_id
                        GROUP BY YEAR(access_date),MONTH(access_date)
                        ORDER BY YEAR(access_date),MONTH(access_date) ASC";

            echo "<tr><td style='padding-left : 40px;padding-right : 40px;'>";
            $results = getManyResults3Col($sql);

            echo "<table cellpadding='2' cellspacing='1' border='0' align=center>";
            echo "<tr>
                    <td class='secLine'>
                    ".get_lang('LoginsTitleMonthColumn')."
                    </td>
                    <td class='secLine'>
                    ".get_lang('LoginsTitleCountColumn')."
                    </td>
                </tr>";
            $total = 0;
            if (is_array($results)) {
                for ($j = 0; $j < count($results); $j++) {
                    echo "<tr>";
                    echo "<td class='content'><a href='logins_details.php?uInfo=".$user_id."&reqdate=".$results[$j][0]."&view=".Security::remove_XSS($view)."'>".$MonthsLong[date('n', $results[$j][0]) - 1].' '.date('Y', $results[$j][0])."</a></td>";
                    echo "<td valign='top' align='right' class='content'>".$results[$j][1]."</td>";
                    echo"</tr>";
                    $total = $total + $results[$j][1];
                }
                echo "<tr>";
                echo "<td>".get_lang('Total')."</td>";
                echo "<td align='right' class='content'>".$total."</td>";
                echo"</tr>";
            } else {
                echo "<tr>";
                echo "<td colspan='2'><center>".get_lang('NoResult')."</center></td>";
                echo"</tr>";
            }
            echo "</table>";
            echo "</td></tr>";
        } else {
            $new_view = substr_replace($view, '1', 0, 1);
            echo "
                <tr>
                    <td valign='top'>
                    +<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".api_get_self()."?uInfo=".$user_id."&view=".Security::remove_XSS($new_view)."' class='specialLink'>".get_lang('LoginsAndAccessTools')."</a>
                    </td>
                </tr>
            ";
        }
    }

    /**
     * Displays the exercise results for a specific user in a specific course.
     * @param   string $view
     * @param   int $user_id    User ID
     * @param   string  $courseCode Course code
     * @return array
     * @todo remove globals
     */
    public static function display_exercise_tracking_info($view, $user_id, $courseCode)
    {
        global $TBL_TRACK_HOTPOTATOES, $TABLECOURSE_EXERCICES, $TABLETRACK_EXERCICES, $dateTimeFormatLong;
        $courseId = api_get_course_int_id($courseCode);
        if (substr($view, 1, 1) == '1') {
            $new_view = substr_replace($view, '0', 1, 1);
            echo "<tr>
                    <td valign='top'>
                        <font color='#0000FF'>-&nbsp;&nbsp;&nbsp;</font><b>".get_lang('ExercicesResults')."</b>&nbsp;&nbsp;&nbsp;[<a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."'>".get_lang('Close')."</a>]&nbsp;&nbsp;&nbsp;[<a href='userLogCSV.php?".api_get_cidreq()."&uInfo=".Security::remove_XSS($_GET['uInfo'])."&view=01000'>".get_lang('ExportAsCSV')."</a>]
                    </td>
                </tr>";
            echo "<tr><td style='padding-left : 40px;' valign='top'>".get_lang('ExercicesDetails')."<br />";

            $sql = "SELECT ce.title, te.exe_result , te.exe_weighting, UNIX_TIMESTAMP(te.exe_date)
                    FROM $TABLECOURSE_EXERCICES AS ce , $TABLETRACK_EXERCICES AS te
                    WHERE te.c_id = $courseId
                        AND te.exe_user_id = ".intval($user_id)."
                        AND te.exe_exo_id = ce.id
                    ORDER BY ce.title ASC, te.exe_date ASC";

            $hpsql = "SELECT te.exe_name, te.exe_result , te.exe_weighting, UNIX_TIMESTAMP(te.exe_date)
                        FROM $TBL_TRACK_HOTPOTATOES AS te
                        WHERE te.exe_user_id = '".intval($user_id)."' AND te.c_id = $courseId
                        ORDER BY te.c_id ASC, te.exe_date ASC";

            $hpresults = StatsUtils::getManyResultsXCol($hpsql, 4);

            $NoTestRes = 0;
            $NoHPTestRes = 0;

            echo "<tr>\n<td style='padding-left : 40px;padding-right : 40px;'>\n";
            $results = StatsUtils::getManyResultsXCol($sql, 4);
            echo "<table cellpadding='2' cellspacing='1' border='0' align='center'>\n";
            echo "
                <tr bgcolor='#E6E6E6'>
                    <td>
                    ".get_lang('ExercicesTitleExerciceColumn')."
                    </td>
                    <td>
                    ".get_lang('Date')."
                    </td>
                    <td>
                    ".get_lang('ExercicesTitleScoreColumn')."
                    </td>
                </tr>";

            if (is_array($results)) {
                for ($i = 0; $i < sizeof($results); $i++) {
                    $display_date = api_convert_and_format_date($results[$i][3], null, date_default_timezone_get());
                    echo "<tr>\n";
                    echo "<td class='content'>".$results[$i][0]."</td>\n";
                    echo "<td class='content'>".$display_date."</td>\n";
                    echo "<td valign='top' align='right' class='content'>".$results[$i][1]." / ".$results[$i][2]."</td>\n";
                    echo "</tr>\n";
                }
            } else {
                // istvan begin
                $NoTestRes = 1;
            }

            // The Result of Tests
            if (is_array($hpresults)) {
                for ($i = 0; $i < sizeof($hpresults); $i++) {
                    $title = GetQuizName($hpresults[$i][0], '');
                    if ($title == '')
                    $title = basename($hpresults[$i][0]);
                    $display_date = api_convert_and_format_date($hpresults[$i][3], null, date_default_timezone_get());
                    ?>
                    <tr>
                        <td class="content"><?php echo $title; ?></td>
                        <td class="content" align="center"><?php echo $display_date; ?></td>
                        <td class="content" align="center"><?php echo $hpresults[$i][1]; ?> / <?php echo $hpresults[$i][2]; ?>
                        </td>
                    </tr>

                    <?php
                }
            } else {
                $NoHPTestRes = 1;
            }

            if ($NoTestRes == 1 && $NoHPTestRes == 1) {
                echo "<tr>\n";
                echo "<td colspan='3'><center>".get_lang('NoResult')."</center></td>\n";
                echo "</tr>\n";
            }
            echo "</table>";
            echo "</td>\n</tr>\n";
        } else {
            $new_view = substr_replace($view, '1', 1, 1);
            echo "
                <tr>
                    <td valign='top'>
                        +<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".api_get_self()."?uInfo=$user_id&view=".$new_view."' class='specialLink'>".get_lang('ExercicesResults')."</a>
                    </td>
                </tr>";
        }
    }

    /**
     * Displays the student publications for a specific user in a specific course.
     * @todo remove globals
     */
    public static function display_student_publications_tracking_info($view, $user_id, $course_id)
    {
        global $TABLETRACK_UPLOADS, $TABLECOURSE_WORK;
        $_course = api_get_course_info_by_id($course_id);

        if (substr($view, 2, 1) == '1') {
            $new_view = substr_replace($view, '0', 2, 1);
            echo "<tr>
                    <td valign='top'>
                    <font color='#0000FF'>-&nbsp;&nbsp;&nbsp;</font><b>".get_lang('WorkUploads')."</b>&nbsp;&nbsp;&nbsp;[<a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."'>".get_lang('Close')."</a>]&nbsp;&nbsp;&nbsp;[<a href='userLogCSV.php?".api_get_cidreq()."&uInfo=".Security::remove_XSS($_GET['uInfo'])."&view=00100'>".get_lang('ExportAsCSV')."</a>]
                    </td>
                </tr>";
            echo "<tr><td style='padding-left : 40px;' valign='top'>".get_lang('WorksDetails')."<br>";
            $sql = "SELECT u.upload_date, w.title, w.author,w.url
                    FROM $TABLETRACK_UPLOADS u , $TABLECOURSE_WORK w
                    WHERE u.upload_work_id = w.id
                        AND u.upload_user_id = '".intval($user_id)."'
                        AND u.c_id = '".intval($course_id)."'
                    ORDER BY u.upload_date DESC";
            echo "<tr><td style='padding-left : 40px;padding-right : 40px;'>";
            $results = StatsUtils::getManyResultsXCol($sql, 4);
            echo "<table cellpadding='2' cellspacing='1' border='0' align=center>";
            echo "<tr>
                    <td class='secLine' width='40%'>
                    ".get_lang('WorkTitle')."
                    </td>
                    <td class='secLine' width='30%'>
                    ".get_lang('WorkAuthors')."
                    </td>
                    <td class='secLine' width='30%'>
                    ".get_lang('Date')."
                    </td>
                </tr>";
            if (is_array($results)) {
                for ($j = 0; $j < count($results); $j++) {
                    $pathToFile = api_get_path(WEB_COURSE_PATH).$_course['path']."/".$results[$j][3];
                    $beautifulDate = api_convert_and_format_date($results[$j][0], null, date_default_timezone_get());
                    echo "<tr>";
                    echo "<td class='content'>"
                    ."<a href ='".$pathToFile."'>".$results[$j][1]."</a>"
                    ."</td>";
                    echo "<td class='content'>".$results[$j][2]."</td>";
                    echo "<td class='content'>".$beautifulDate."</td>";
                    echo"</tr>";
                }
            } else {
                echo "<tr>";
                echo "<td colspan='3'><center>".get_lang('NoResult')."</center></td>";
                echo"</tr>";
            }
            echo "</table>";
            echo "</td></tr>";
        } else {
            $new_view = substr_replace($view, '1', 2, 1);
            echo "
                <tr>
                    <td valign='top'>
                    +<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."' class='specialLink'>".get_lang('WorkUploads')."</a>
                    </td>
                </tr>
            ";
        }
    }

    /**
     * Displays the links followed for a specific user in a specific course.
     * @todo remove globals
     */
    public static function display_links_tracking_info($view, $user_id, $courseCode)
    {
        global $TABLETRACK_LINKS, $TABLECOURSE_LINKS;
        $courseId = api_get_course_int_id($courseCode);
        if (substr($view, 3, 1) == '1') {
            $new_view = substr_replace($view, '0', 3, 1);
            echo "
                <tr>
                        <td valign='top'>
                        <font color='#0000FF'>-&nbsp;&nbsp;&nbsp;</font><b>".get_lang('LinksAccess')."</b>&nbsp;&nbsp;&nbsp;[<a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."'>".get_lang('Close')."</a>]&nbsp;&nbsp;&nbsp;[<a href='userLogCSV.php?".api_get_cidreq()."&uInfo=".Security::remove_XSS($_GET['uInfo'])."&view=00010'>".get_lang('ExportAsCSV')."</a>]
                        </td>
                </tr>
            ";
            echo "<tr><td style='padding-left : 40px;' valign='top'>".get_lang('LinksDetails')."<br>";
            $sql = "SELECT cl.title, cl.url
                    FROM $TABLETRACK_LINKS AS sl, $TABLECOURSE_LINKS AS cl
                    WHERE sl.links_link_id = cl.id
                        AND sl.c_id = $courseId
                        AND sl.links_user_id = ".intval($user_id)."
                    GROUP BY cl.title, cl.url";
            echo "<tr><td style='padding-left : 40px;padding-right : 40px;'>";
            $results = StatsUtils::getManyResults2Col($sql);
            echo "<table cellpadding='2' cellspacing='1' border='0' align=center>";
            echo "<tr>
                    <td class='secLine'>
                    ".get_lang('LinksTitleLinkColumn')."
                    </td>
                </tr>";
            if (is_array($results)) {
                for ($j = 0; $j < count($results); $j++) {
                    echo "<tr>";
                    echo "<td class='content'><a href='".$results[$j][1]."'>".$results[$j][0]."</a></td>";
                    echo"</tr>";
                }
            } else {
                echo "<tr>";
                echo "<td ><center>".get_lang('NoResult')."</center></td>";
                echo"</tr>";
            }
            echo "</table>";
            echo "</td></tr>";
        } else {
            $new_view = substr_replace($view, '1', 3, 1);
            echo "
                <tr>
                    <td valign='top'>
                    +<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."' class='specialLink'>".get_lang('LinksAccess')."</a>
                    </td>
                </tr>
            ";
        }
    }

    /**
     * Displays the documents downloaded for a specific user in a specific course.
     * @param     string    kind of view inside tracking info
     * @param    int        User id
     * @param    string    Course code
     * @param    int        Session id (optional, default = 0)
     * @return     void
     */
    public static function display_document_tracking_info($view, $user_id, $course_code, $session_id = 0)
    {
        // protect data
        $user_id = intval($user_id);
        $courseId = api_get_course_int_id($course_code);
        $session_id = intval($session_id);

        $downloads_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
        if (substr($view, 4, 1) == '1') {
            $new_view = substr_replace($view, '0', 4, 1);
            echo "
                <tr>
                    <td valign='top'>
                    <font color='#0000FF'>-&nbsp;&nbsp;&nbsp;</font><b>".get_lang('DocumentsAccess')."</b>&nbsp;&nbsp;&nbsp;[<a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."'>".get_lang('Close')."</a>]&nbsp;&nbsp;&nbsp;[<a href='userLogCSV.php?".api_get_cidreq()."&uInfo=".Security::remove_XSS($_GET['uInfo'])."&view=00001'>".get_lang('ExportAsCSV')."</a>]
                    </td>
                </tr>
            ";
            echo "<tr><td style='padding-left : 40px;' valign='top'>".get_lang('DocumentsDetails')."<br>";

            $sql = "SELECT down_doc_path
                    FROM $downloads_table
                    WHERE c_id = $courseId
                        AND down_user_id = $user_id
                        AND down_session_id = $session_id
                    GROUP BY down_doc_path";

            echo "<tr><td style='padding-left : 40px;padding-right : 40px;'>";
            $results = StatsUtils::getManyResults1Col($sql);
            echo "<table cellpadding='2' cellspacing='1' border='0' align='center'>";
            echo "<tr>
                    <td class='secLine'>
                    ".get_lang('DocumentsTitleDocumentColumn')."
                    </td>
                </tr>";
            if (is_array($results)) {
                for ($j = 0; $j < count($results); $j++) {
                    echo "<tr>";
                    echo "<td class='content'>".$results[$j]."</td>";
                    echo"</tr>";
                }
            } else {
                echo "<tr>";
                echo "<td><center>".get_lang('NoResult')."</center></td>";
                echo"</tr>";
            }
            echo "</table>";
            echo "</td></tr>";
        } else {
            $new_view = substr_replace($view, '1', 4, 1);
            echo "
                <tr>
                    <td valign='top'>
                    +<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."' class='specialLink'>".get_lang('DocumentsAccess')."</a>
                    </td>
                </tr>
            ";
        }
    }

    /**
     * Gets the IP of a given user, using the last login before the given date
     * @param int User ID
     * @param string Datetime
     * @param bool Whether to return the IP as a link or just as an IP
     * @param string If defined and return_as_link if true, will be used as the text to be shown as the link
     * @return string IP address (or false on error)
     * @assert (0,0) === false
     */
    public static function get_ip_from_user_event($user_id, $event_date, $return_as_link = false, $body_replace = null)
    {
        if (empty($user_id) or empty($event_date)) {
            return false;
        }
        $user_id = intval($user_id);
        $event_date = Database::escape_string($event_date);

        $table_login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $sql_ip = "SELECT login_date, user_ip FROM $table_login
                   WHERE login_user_id = $user_id AND login_date < '$event_date'
                   ORDER BY login_date DESC LIMIT 1";
        $ip = '';
        $res_ip = Database::query($sql_ip);
        if ($res_ip !== false && Database::num_rows($res_ip) > 0) {
            $row_ip = Database::fetch_row($res_ip);
            if ($return_as_link) {
                $ip = Display::url(
                    (empty($body_replace) ? $row_ip[1] : $body_replace), 'http://www.whatsmyip.org/ip-geo-location/?ip='.$row_ip[1],
                    array('title'=>get_lang('TraceIP'), 'target'=>'_blank')
                );
            } else {
                $ip = $row_ip[1];
            }
        }

        return $ip;
    }
}

/**
 * @package chamilo.tracking
 */
class TrackingUserLogCSV
{
    /**
     * Displays the number of logins every month for a specific user in a specific course.
     * @param $view
     * @param int $user_id
     * @param int $course_id
     * @param int $session_id
     * @return array
     */
    public function display_login_tracking_info($view, $user_id, $course_id, $session_id = 0)
    {
        $MonthsLong = $GLOBALS['MonthsLong'];
        $track_access_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ACCESS);

        // protected data
        $user_id    = intval($user_id);
        $session_id = intval($session_id);
        $course_id  = intval($course_id);

        $tempView = $view;
        if (substr($view, 0, 1) == '1') {
            $new_view = substr_replace($view, '0', 0, 1);
            $title[1] = get_lang('LoginsAndAccessTools').get_lang('LoginsDetails');
            $sql = "SELECT UNIX_TIMESTAMP(access_date), count(access_date)
                    FROM $track_access_table
                    WHERE access_user_id = $user_id
                    AND c_id = $course_id
                    AND access_session_id = $session_id
                    GROUP BY YEAR(access_date),MONTH(access_date)
                    ORDER BY YEAR(access_date),MONTH(access_date) ASC";
            //$results = getManyResults2Col($sql);
            $results = getManyResults3Col($sql);
            $title_line = get_lang('LoginsTitleMonthColumn').';'.get_lang('LoginsTitleCountColumn')."\n";
            $line = '';
            $total = 0;
            if (is_array($results)) {
                for ($j = 0; $j < count($results); $j++) {
                    $line .= $results[$j][0].';'.$results[$j][1]."\n";
                    $total = $total + $results[$j][1];
                }
                $line .= get_lang('Total').";".$total."\n";
            } else {
                $line = get_lang('NoResult')."</center></td>";
            }
        } else {
            $new_view = substr_replace($view, '1', 0, 1);
        }
        return array($title_line, $line);
    }

    /**
     * Displays the exercise results for a specific user in a specific course.
     * @param   string $view
     * @param   int $user_id    User ID
     * @param   string  $courseCode Course code
     * @return array
     * @todo remove globals
     */
    public function display_exercise_tracking_info($view, $userId, $courseCode)
    {
        global $TABLECOURSE_EXERCICES, $TABLETRACK_EXERCICES, $TABLETRACK_HOTPOTATOES, $dateTimeFormatLong;
        $courseId = api_get_course_int_id($courseCode);
        $userId = intval($userId);
        if (substr($view, 1, 1) == '1') {
            $new_view = substr_replace($view, '0', 1, 1);
            $title[1] = get_lang('ExercicesDetails');
            $line = '';
            $sql = "SELECT ce.title, te.exe_result , te.exe_weighting, UNIX_TIMESTAMP(te.exe_date)
                    FROM $TABLECOURSE_EXERCICES AS ce , $TABLETRACK_EXERCICES AS te
                    WHERE te.c_id = $courseId
                        AND te.exe_user_id = $userId
                        AND te.exe_exo_id = ce.id
                    ORDER BY ce.title ASC, te.exe_date ASC";

            $hpsql = "SELECT te.exe_name, te.exe_result , te.exe_weighting, UNIX_TIMESTAMP(te.exe_date)
                        FROM $TABLETRACK_HOTPOTATOES AS te
                        WHERE te.exe_user_id = '$userId' AND te.c_id = $courseId
                        ORDER BY te.c_id ASC, te.exe_date ASC";

            $hpresults = StatsUtils::getManyResultsXCol($hpsql, 4);

            $NoTestRes = 0;
            $NoHPTestRes = 0;

            $results = StatsUtils::getManyResultsXCol($sql, 4);
            $title_line = get_lang('ExercicesTitleExerciceColumn').";".get_lang('Date').';'.get_lang('ExercicesTitleScoreColumn')."\n";

            if (is_array($results)) {
                for ($i = 0; $i < sizeof($results); $i++)
                {
                    $display_date = api_convert_and_format_date($results[$i][3], null, date_default_timezone_get());
                    $line .= $results[$i][0].";".$display_date.";".$results[$i][1]." / ".$results[$i][2]."\n";
                }
            } else {
                // istvan begin
                $NoTestRes = 1;
            }

            // The Result of Tests
            if (is_array($hpresults)) {
                for ($i = 0; $i < sizeof($hpresults); $i++) {
                    $title = GetQuizName($hpresults[$i][0], '');

                    if ($title == '')
                    $title = basename($hpresults[$i][0]);

                    $display_date = api_convert_and_format_date($hpresults[$i][3], null, date_default_timezone_get());

                    $line .= $title.';'.$display_date.';'.$hpresults[$i][1].'/'.$hpresults[$i][2]."\n";
                }
            } else {
                $NoHPTestRes = 1;
            }

            if ($NoTestRes == 1 && $NoHPTestRes == 1) {
                $line = get_lang('NoResult');
            }
        } else {
            $new_view = substr_replace($view, '1', 1, 1);
        }
        return array($title_line, $line);
    }

    /**
     * Displays the student publications for a specific user in a specific course.
     * @todo remove globals
     */
    public function display_student_publications_tracking_info($view, $user_id, $course_id)
    {
        global $TABLETRACK_UPLOADS, $TABLECOURSE_WORK;
        $_course = api_get_course_info();
        $user_id = intval($user_id);
        $course_id = intval($course_id);

        if (substr($view, 2, 1) == '1') {
            $sql = "SELECT u.upload_date, w.title, w.author, w.url
                    FROM $TABLETRACK_UPLOADS u , $TABLECOURSE_WORK w
                    WHERE
                        u.upload_work_id = w.id AND
                        u.upload_user_id = '$user_id' AND
                        u.c_id = '$course_id'
                    ORDER BY u.upload_date DESC";
            $results = StatsUtils::getManyResultsXCol($sql, 4);

            $title[1] = get_lang('WorksDetails');
            $line = '';
            $title_line = get_lang('WorkTitle').";".get_lang('WorkAuthors').";".get_lang('Date')."\n";

            if (is_array($results)) {
                for ($j = 0; $j < count($results); $j++) {
                    $pathToFile = api_get_path(WEB_COURSE_PATH).$_course['path']."/".$results[$j][3];
                    $beautifulDate = api_convert_and_format_date($results[$j][0], null, date_default_timezone_get());
                    $line .= $results[$j][1].";".$results[$j][2].";".$beautifulDate."\n";
                }

            } else {
                $line = get_lang('NoResult');
            }
        }
        return array($title_line, $line);
    }

    /**
     * Displays the links followed for a specific user in a specific course.
     * @todo remove globals
     */
    public function display_links_tracking_info($view, $userId, $courseCode)
    {
        global $TABLETRACK_LINKS, $TABLECOURSE_LINKS;
        $courseId = api_get_course_int_id($courseCode);
        $userId = intval($userId);
        $line = null;
        if (substr($view, 3, 1) == '1') {
            $new_view = substr_replace($view, '0', 3, 1);
            $title[1] = get_lang('LinksDetails');
            $sql = "SELECT cl.title, cl.url
                        FROM $TABLETRACK_LINKS AS sl, $TABLECOURSE_LINKS AS cl
                        WHERE sl.links_link_id = cl.id
                            AND sl.c_id = $courseId
                            AND sl.links_user_id = $userId
                        GROUP BY cl.title, cl.url";
            $results = StatsUtils::getManyResults2Col($sql);
            $title_line = get_lang('LinksTitleLinkColumn')."\n";
            if (is_array($results)) {
                for ($j = 0; $j < count($results); $j++) {
                    $line .= $results[$j][0]."\n";
                }
            } else {
                $line = get_lang('NoResult');
            }
        } else {
            $new_view = substr_replace($view, '1', 3, 1);
        }
        return array($title_line, $line);
    }

    /**
     * Displays the documents downloaded for a specific user in a specific course.
     * @param     string    kind of view inside tracking info
     * @param    int        User id
     * @param    string    Course code
     * @param    int        Session id (optional, default = 0)
     * @return     void
     */
    public function display_document_tracking_info($view, $user_id, $courseCode, $session_id = 0)
    {
        // protect data
        $user_id = intval($user_id);
        $courseId = api_get_course_int_id($courseCode);
        $session_id = intval($session_id);

        $downloads_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);

        if (substr($view, 4, 1) == '1') {
            $new_view = substr_replace($view, '0', 4, 1);
            $title[1] = get_lang('DocumentsDetails');

            $sql = "SELECT down_doc_path
                        FROM $downloads_table
                        WHERE c_id = $courseId
                            AND down_user_id = $user_id
                            AND down_session_id = $session_id
                        GROUP BY down_doc_path";

            $results = StatsUtils::getManyResults1Col($sql);
            $title_line = get_lang('DocumentsTitleDocumentColumn')."\n";
            $line = null;
            if (is_array($results)) {
                for ($j = 0; $j < count($results); $j++) {
                    $line .= $results[$j]."\n";
                }
            } else {
                $line = get_lang('NoResult');
            }
        } else {
            $new_view = substr_replace($view, '1', 4, 1);
        }
        return array($title_line, $line);
    }

    /**
     * @param $userId
     * @param $courseInfo
     * @param int $sessionId
     * @return array
     */
    public static function getToolInformation(
        $userId,
        $courseInfo,
        $sessionId = 0
    ) {
        $csvContent = array();
        $courseToolInformation = null;
        $headerTool = array(
            array(get_lang('Title')),
            array(get_lang('CreatedAt')),
            array(get_lang('UpdatedAt')),
        );

        $headerListForCSV = array();
        foreach ($headerTool as $item) {
            $headerListForCSV[] = $item[0];
        }

        $courseForumInformationArray = getForumCreatedByUser(
            $userId,
            $courseInfo['real_id'],
            $sessionId
        );

        if (!empty($courseForumInformationArray)) {
            $csvContent[] = array();
            $csvContent[] = get_lang('Forums');
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
            $csvContent[] = get_lang('Works');
            $csvContent[] = $headerListForCSV;

            foreach ($courseWorkInformationArray as $row) {
                $csvContent[] = $row;
            }
            $csvContent[] = null;

            $courseToolInformation .= Display::page_subheader2(
                get_lang('Works')
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

        return array(
            'array' => $csvContent,
            'html' => $courseToolInformationTotal
        );
    }
}

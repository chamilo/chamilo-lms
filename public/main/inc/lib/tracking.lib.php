<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField as EntityExtraField;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use ChamiloSession as Session;
use CpChart\Cache as pCache;
use CpChart\Data as pData;
use CpChart\Image as pImage;
use ExtraField as ExtraFieldModel;

/**
 *  Class Tracking.
 *
 *  @author  Julio Montoya <gugli100@gmail.com>
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
        $courseId,
        $sessionId = 0,
        $group_id = 0,
        $type = 'all',
        $start = 0,
        $limit = 1000,
        $sidx = 1,
        $sord = 'desc',
        $where_condition = []
    ) {
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;

        if (empty($courseId)) {
            return null;
        }
        $course = api_get_course_entity($courseId);

        $session = api_get_session_entity($sessionId);
        if ('count' === $type) {
            return GroupManager::get_group_list(null, $course, null, $sessionId, true);
        }

        $groupList = GroupManager::get_group_list(null, $course, null, $sessionId, false, null, true);
        $parsedResult = [];
        if (!empty($groupList)) {
            foreach ($groupList as $group) {
                $users = GroupManager::get_users($group->getIid(), true, null, null, false, $courseId);
                $time = 0;
                $avg_student_score = 0;
                $avg_student_progress = 0;
                $work = 0;
                $messages = 0;
                foreach ($users as $user_data) {
                    $user = api_get_user_entity($user_data['user_id']);
                    $time += self::get_time_spent_on_the_course(
                        $user_data['user_id'],
                        $courseId,
                        $sessionId
                    );
                    $average = self::get_avg_student_score(
                        $user_data['user_id'],
                        $course,
                        [],
                        $session
                    );
                    if (is_numeric($average)) {
                        $avg_student_score += $average;
                    }
                    $avg_student_progress += self::get_avg_student_progress(
                        $user_data['user_id'],
                        $course,
                        [],
                        $session
                    );
                    $work += Container::getStudentPublicationRepository()->countUserPublications(
                        $user,
                        $course,
                        $session
                    );
                    $messages += Container::getForumPostRepository()->countUserForumPosts($user, $course, $session);
                }

                $countUsers = count($users);
                $averageProgress = empty($countUsers) ? 0 : round($avg_student_progress / $countUsers, 2);
                $averageScore = empty($countUsers) ? 0 : round($avg_student_score / $countUsers, 2);

                $groupItem = [
                    'id' => $group->getIid(),
                    'name' => $group->getName(),
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
        int $user_id,
        Course $course,
        ?SessionEntity $session,
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
        if (empty($lp_id)) {
            return '';
        }

        $hideTime = api_get_configuration_value('hide_lp_time');
        $lp_id = (int) $lp_id;

        $lp_item_id = (int) $lp_item_id;
        $user_id = (int) $user_id;
        $sessionId = $session ? $session->getId() : 0;
        $origin = Security::remove_XSS($origin);
        $lp = Container::getLpRepository()->find($lp_id);
        $list = learnpath::get_flat_ordered_items_list($lp);
        $is_allowed_to_edit = api_is_allowed_to_edit(null, true);
        $courseId = $course->getId();
        $session_condition = api_get_session_condition($sessionId);

        // Extend all button
        $output = '';
        $extra = '<script>
        $(function() {
            $( "#dialog:ui-dialog" ).dialog( "destroy" );
            $( "#dialog-confirm" ).dialog({
                autoOpen: false,
                show: "blind",
                resizable: false,
                height:300,
                modal: true
            });

            $(".export").click(function() {
                var targetUrl = $(this).attr("href");
                $( "#dialog-confirm" ).dialog({
                    width:400,
                    height:300,
                    buttons: {
                        "'.addslashes(get_lang('Download')).'": function() {
                            var option = $("input[name=add_logo]:checked").val();
                            location.href = targetUrl+"&add_logo="+option;
                            $(this).dialog("close");
                        }
                    }
                });
                $("#dialog-confirm").dialog("open");

                return false;
            });
        });
        </script>';

        $extra .= '<div id="dialog-confirm" title="'.get_lang('Please confirm your choice').'">';
        $form = new FormValidator('report', 'post', null, null, ['class' => 'form-vertical']);
        $form->addCheckBox('add_logo', '', get_lang('AddRightLogo'), ['id' => 'export_format_csv_label']);
        $extra .= $form->returnForm();
        $extra .= '</div>';
        $output .= $extra;

        $url_suffix = '&lp_id='.$lp_id;
        if ('tracking' === $origin) {
            $url_suffix = '&sid='.$sessionId.'&cid='.$courseId.'&student_id='.$user_id.'&lp_id='.$lp_id.'&origin='.$origin;
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

        if ('tracking' !== $origin) {
            $output .= '<div class="section-status">';
            $output .= Display::page_header(get_lang('My progress'));
            $output .= '</div>';
        }

        $actionColumn = null;
        if ('classic' === $type) {
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
                <th width="16">'.(true === $allowExtend ? $extend_all_link : '&nbsp;').'</th>
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
                    c_id = $courseId AND
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

            if (false === $hideTime) {
                $csvHeaders[] = get_lang('Time');
            }
            $csv_content[] = $csvHeaders;
        }

        $result_disabled_ext_all = true;
        $chapterTypes = learnpath::getChapterTypes();
        $accessToPdfExport = api_is_allowed_to_edit(false, false, true);

        $minimumAvailable = self::minimumTimeAvailable($sessionId, $courseId);
        $timeCourse = [];
        if ($minimumAvailable) {
            $timeCourse = self::getCalculateTime($user_id, $courseId, $sessionId);
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
                    iv.iid as iv_id,
                    path
                FROM $TBL_LP_ITEM as i
                INNER JOIN $TBL_LP_ITEM_VIEW as iv
                ON (i.iid = iv.lp_item_id)
                INNER JOIN $TBL_LP_VIEW as v
                ON (iv.lp_view_id = v.iid)
                WHERE
                    i.iid = $my_item_id AND
                    i.lp_id = $lp_id  AND
                    v.user_id = $user_id
                    $session_condition
                    $viewCondition
                ORDER BY iv.view_count $order ";

                $result = Database::query($sql);
                $num = Database::num_rows($result);
                $time_for_total = 0;
                $attemptResult = 0;

                if ($timeCourse) {
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
                    if ('quiz' === $row['item_type']) {
                        // Check results_disabled in quiz table.
                        $my_path = Database::escape_string($row['path']);
                        $sql = "SELECT results_disabled
                                FROM $TBL_QUIZ
                                WHERE
                                    iid ='".$my_path."'";
                        $res_result_disabled = Database::query($sql);
                        $row_result_disabled = Database::fetch_row($res_result_disabled);

                        if (Database::num_rows($res_result_disabled) > 0 &&
                            1 === (int) $row_result_disabled[0]
                        ) {
                            $result_disabled_ext_all = true;
                        }
                    }

                    // If there are several attempts, and the link to extend has been clicked, show each attempt...
                    $oddclass = 'row_even';
                    if (0 === ($counter % 2)) {
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
                    if ('classic' === $type) {
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

                        if ($timeCourse) {
                            //$attemptResult = 0;
                            if (isset($timeCourse['learnpath_detailed']) &&
                                isset($timeCourse['learnpath_detailed'][$lp_id]) &&
                                isset($timeCourse['learnpath_detailed'][$lp_id][$my_item_id])
                            ) {
                                $attemptResult = $timeCourse['learnpath_detailed'][$lp_id][$my_item_id][$row['iv_view_count']];
                            }
                        }
                        if ((
                            learnpath::get_interactions_count_from_db($row['iv_id'], $courseId) > 0 ||
                            learnpath::get_objectives_count_from_db($row['iv_id'], $courseId) > 0
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
                                            api_get_self().'?action=export_stats&extend_id='.$my_item_id.'&extend_attempt_id='.$row['iv_id'].$url_suffix,
                                            ['class' => 'export']
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
                                            api_get_self().'?action=export_stats&extend_id='.$my_item_id.'&extend_attempt_id='.$row['iv_id'].$url_suffix,
                                            ['class' => 'export']
                                        );
                                }
                            }
                        }

                        $oddclass = 'row_even';
                        if (0 == ($counter % 2)) {
                            $oddclass = 'row_odd';
                        }

                        $lesson_status = $row['mystatus'];
                        $score = $row['myscore'];
                        $time_for_total += $row['mytime'];
                        $attemptTime = $row['mytime'];

                        if ($minimumAvailable) {
                            $lp_time = $timeCourse[TOOL_LEARNPATH];
                            $lpTime = null;
                            if (isset($lp_time[$lp_id])) {
                                $lpTime = (int) $lp_time[$lp_id];
                            }
                            $time_for_total = $lpTime;

                            if ($timeCourse) {
                                $time_for_total = (int) $attemptResult;
                                $attemptTime = (int) $attemptResult;
                            }
                        }

                        $time = learnpathItem::getScormTimeFromParameter('js', $attemptTime);

                        if (0 == $score) {
                            $maxscore = $row['mymaxscore'];
                        } else {
                            if ('sco' === $row['item_type']) {
                                if (!empty($row['myviewmaxscore']) && $row['myviewmaxscore'] > 0) {
                                    $maxscore = $row['myviewmaxscore'];
                                } elseif ('' === $row['myviewmaxscore']) {
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

                        if ('dir' !== $row['item_type']) {
                            if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                                $view_score = Display::return_icon(
                                    'invisible.png',
                                    get_lang('Results hidden by the exercise setting')
                                );
                            } else {
                                switch ($row['item_type']) {
                                    case 'sco':
                                        if (0 == $maxscore) {
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
                                        $view_score = (0 == $score ? '/' : ExerciseLib::show_score($score, $maxscore, false));
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
                            if ('classic' === $type) {
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

                                if ('quiz' === $row['item_type']) {
                                    if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                                        $temp[] = '/';
                                    } else {
                                        $temp[] = (0 == $score ? '0/'.$maxscore : (0 == $maxscore ? $score : $score.'/'.float_format($maxscore, 1)));
                                    }
                                } else {
                                    $temp[] = (0 == $score ? '/' : (0 == $maxscore ? $score : $score.'/'.float_format($maxscore, 1)));
                                }

                                if (false === $hideTime) {
                                    $temp[] = $time;
                                }
                                $csv_content[] = $temp;
                            }
                        }

                        $counter++;
                        $action = null;
                        if ('classic' === $type) {
                            $action = '<td></td>';
                        }

                        if ($extend_this_attempt || $extend_all) {
                            $list1 = learnpath::get_iv_interactions_array($row['iv_id'], $courseId);
                            foreach ($list1 as $id => $interaction) {
                                $oddclass = 'row_even';
                                if (0 == ($counter % 2)) {
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
                            $list2 = learnpath::get_iv_objectives_array($row['iv_id'], $courseId);
                            foreach ($list2 as $id => $interaction) {
                                $oddclass = 'row_even';
                                if (0 === ($counter % 2)) {
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
                    if ('quiz' === $row['item_type']) {
                        // Check results_disabled in quiz table.
                        $my_path = Database::escape_string($my_path);
                        $sql = "SELECT results_disabled
                                FROM $TBL_QUIZ
                                WHERE iid = '$my_path' ";
                        $res_result_disabled = Database::query($sql);
                        $row_result_disabled = Database::fetch_row($res_result_disabled);

                        if (Database::num_rows($res_result_disabled) > 0 &&
                            1 === (int) $row_result_disabled[0]
                        ) {
                            $result_disabled_ext_all = true;
                        }
                    }

                    // Check if there are interactions below
                    $extend_this_attempt = 0;
                    $inter_num = learnpath::get_interactions_count_from_db($row['iv_id'], $courseId);
                    $objec_num = learnpath::get_objectives_count_from_db($row['iv_id'], $courseId);
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
                    if (0 == ($counter % 2)) {
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

                    $title = $row['mytitle'];
                    // Selecting the exe_id from stats attempts tables in order to look the max score value.
                    $sql = 'SELECT * FROM '.$tbl_stats_exercices.'
                            WHERE
                                exe_exo_id="'.$row['path'].'" AND
                                exe_user_id="'.$user_id.'" AND
                                orig_lp_id = "'.$lp_id.'" AND
                                orig_lp_item_id = "'.$row['myid'].'" AND
                                c_id = '.$courseId.' AND
                                status <> "incomplete" AND
                                session_id = '.$sessionId.'
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
                            } elseif ('' === $row['myviewmaxscore']) {
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
                                        lp_item_id = '".(int) $my_id."' AND
                                        lp_view_id = '".(int) $my_lp_view_id."'
                                    ORDER BY view_count DESC
                                    LIMIT 1";
                            $res_score = Database::query($sql);
                            $row_score = Database::fetch_array($res_score);

                            $sql = "SELECT SUM(total_time) as total_time
                                    FROM $TBL_LP_ITEM_VIEW
                                    WHERE
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
                                        ON (q.iid = at.question_id)
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
                                        c_id = '.$courseId.' AND
                                        status <> "incomplete" AND
                                        session_id = '.$sessionId.'
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
                    if ('classic' === $type) {
                        $action = '<td></td>';
                    }

                    if (in_array($row['item_type'], $chapterTypes)) {
                        $title = Security::remove_XSS($title);
                        $output .= '<tr class="'.$oddclass.'">
                                <td>'.$extend_link.'</td>
                                <td colspan="10">
                                <h4>'.$title.'</h4>
                                </td>
                                '.$action.'
                            </tr>';
                    } else {
                        $correct_test_link = '-';
                        $showRowspan = false;
                        if ('quiz' === $row['item_type']) {
                            $my_url_suffix = '&cid='.$courseId.'&student_id='.$user_id.'&lp_id='.intval($row['mylpid']).'&origin='.$origin;
                            $sql = 'SELECT * FROM '.$tbl_stats_exercices.'
                                     WHERE
                                        exe_exo_id="'.$row['path'].'" AND
                                        exe_user_id="'.$user_id.'" AND
                                        orig_lp_id = "'.$lp_id.'" AND
                                        orig_lp_item_id = "'.$row['myid'].'" AND
                                        c_id = '.$courseId.' AND
                                        status <> "incomplete" AND
                                        session_id = '.$sessionId.'
                                     ORDER BY exe_date DESC ';

                            $resultLastAttempt = Database::query($sql);
                            $num = Database::num_rows($resultLastAttempt);
                            $showRowspan = false;
                            if ($num > 0) {
                                $linkId = 'link_'.$my_id;
                                if (1 == $extendedAttempt &&
                                    $lp_id == $my_lp_id &&
                                    $lp_item_id == $my_id
                                ) {
                                    $showRowspan = true;
                                    $correct_test_link = Display::url(
                                        Display::return_icon(
                                            'view_less_stats.gif',
                                            get_lang('Hide all attempts')
                                        ),
                                        api_get_self().'?action=stats'.$my_url_suffix.'&sid='.$sessionId.'&lp_item_id='.$my_id.'#'.$linkId,
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
                                        api_get_self().'?action=stats&extend_attempt=1'.$my_url_suffix.'&sid='.$sessionId.'&lp_item_id='.$my_id.'#'.$linkId,
                                        ['id' => $linkId]
                                    );
                                }
                            }
                        }

                        $title = Security::remove_XSS($title);
                        $action = null;
                        if ('classic' === $type) {
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
                            if ('quiz' === $row['item_type']) {
                                if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                                    $scoreItem .= Display::return_icon(
                                        'invisible.png',
                                        get_lang('Results hidden by the exercise setting')
                                    );
                                } else {
                                    $scoreItem .= ExerciseLib::show_score($score, $maxscore, false);
                                }
                            } else {
                                $scoreItem .= 0 == $score ? '/' : (0 == $maxscore ? $score : $score.'/'.$maxscore);
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
                            if ('quiz' === $row['item_type']) {
                                if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                                    $temp[] = '/';
                                } else {
                                    $temp[] = (0 == $score ? '0/'.$maxscore : (0 == $maxscore ? $score : $score.'/'.float_format($maxscore, 1)));
                                }
                            } else {
                                $temp[] = (0 == $score ? '/' : (0 == $maxscore ? $score : $score.'/'.float_format($maxscore, 1)));
                            }

                            if (false === $hideTime) {
                                $temp[] = $time;
                            }
                            $csv_content[] = $temp;
                        }
                    }

                    $counter++;
                    $action = null;
                    if ('classic' === $type) {
                        $action = '<td></td>';
                    }

                    if ($extend_this_attempt || $extend_all) {
                        $list1 = learnpath::get_iv_interactions_array($row['iv_id'], $courseId);
                        foreach ($list1 as $id => $interaction) {
                            $oddclass = 'row_even';
                            if (0 == ($counter % 2)) {
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

                        $list2 = learnpath::get_iv_objectives_array($row['iv_id'], $courseId);
                        foreach ($list2 as $id => $interaction) {
                            $oddclass = 'row_even';
                            if (0 == ($counter % 2)) {
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

                    // Attempts listing by exercise.
                    if ($lp_id == $my_lp_id && $lp_item_id == $my_id && $extendedAttempt) {
                        // Get attempts of a exercise.
                        if (!empty($lp_id) &&
                            !empty($lp_item_id) &&
                            'quiz' === $row['item_type']
                        ) {
                            $sql = "SELECT path FROM $TBL_LP_ITEM
                                    WHERE
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
                                            c_id = '.$courseId.'  AND
                                            session_id = '.$sessionId.'
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
                                            if (0 == $my_score) {
                                                $view_score = ExerciseLib::show_score(
                                                    0,
                                                    $my_maxscore,
                                                    false
                                                );
                                            } else {
                                                if (0 == $my_maxscore) {
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
                                        if ('' === $my_lesson_status) {
                                            $my_lesson_status = learnpathitem::humanize_status('completed');
                                        } elseif ('incomplete' === $my_lesson_status) {
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

                                        if ('classic' === $action) {
                                            if ('tracking' !== $origin) {
                                                if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                                                    $output .= '<td>
                                                            <img
                                                                src="'.Display::returnIconPath('quiz_na.gif').'"
                                                                alt="'.get_lang('Show attempt').'"
                                                                title="'.get_lang('Show attempt').'" />
                                                            </td>';
                                                } else {
                                                    $output .= '<td>
                                                            <a
                                                                href="../exercise/exercise_show.php?origin='.$origin.'&id='.$my_exe_id.'&cid='.$courseId.'"
                                                                target="_parent">
                                                            <img
                                                                src="'.Display::returnIconPath('quiz.png').'"
                                                                alt="'.get_lang('Show attempt').'"
                                                                title="'.get_lang('Show attempt').'" />
                                                            </a></td>';
                                                }
                                            } else {
                                                if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                                                    $output .= '<td>
                                                                <img
                                                                    src="'.Display::returnIconPath('quiz_na.gif').'"
                                                                    alt="'.get_lang('Show and grade attempt').'"
                                                                    title="'.get_lang('Show and grade attempt').'" />
                                                                </td>';
                                                } else {
                                                    $output .= '<td>
                                                                    <a
                                                                        href="../exercise/exercise_show.php?cid='.$courseId.'&origin=correct_exercise_in_lp&id='.$my_exe_id.'"
                                                                        target="_parent">
                                                                    <img
                                                                        src="'.Display::returnIconPath('quiz.gif').'"
                                                                        alt="'.get_lang('Show and grade attempt').'"
                                                                        title="'.get_lang('Show and grade attempt').'">
                                                                    </a>
                                                                    </td>';
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
                    $course,
                    $a_my_id,
                    $session,
                    false,
                    false
                );
            } else {
                // "Left green cross" extended
                $total_score = self::get_avg_student_score(
                    $user_id,
                    $course,
                    $a_my_id,
                    $session,
                    false,
                    true
                );
            }
        } else {
            // Extend all "left green cross"
            $total_score = self::get_avg_student_score(
                $user_id,
                $course,
                [$lp_id],
                $session,
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
        $progress = learnpath::getProgress($lp_id, $user_id, $courseId, $sessionId);

        $oddclass = 'row_even';
        if (0 == ($counter % 2)) {
            $oddclass = 'row_odd';
        }

        $action = null;
        if ('classic' === $type) {
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

            if (false === $hideTime) {
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
     * @param string    $timeFilter       type of time filter: 'last_week' or 'custom'
     * @param string    $start_date       start date date('Y-m-d H:i:s')
     * @param string    $end_date         end date date('Y-m-d H:i:s')
     * @param bool      $returnAllRecords
     *
     * @return int|array
     */
    public static function get_time_spent_on_the_platform(
        $userId,
        $timeFilter = 'last_7_days',
        $start_date = null,
        $end_date = null,
        $returnAllRecords = false
    ) {
        $tbl_track_login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $condition_time = '';

        if (is_array($userId)) {
            $userList = array_map('intval', $userId);
            $userCondition = " login_user_id IN ('".implode("','", $userList)."')";
        } else {
            $userId = (int) $userId;
            $userCondition = " login_user_id = $userId ";
        }

        $url_condition = null;
        $tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $url_table = null;
        if (api_is_multiple_url_enabled()) {
            $access_url_id = api_get_current_access_url_id();
            $url_table = ", $tbl_url_rel_user as url_users";
            $url_condition = " AND u.login_user_id = url_users.user_id AND access_url_id='$access_url_id'";
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
            case 'wide':
                if (!empty($start_date) && !empty($end_date)) {
                    $start_date = Database::escape_string($start_date);
                    $end_date = Database::escape_string($end_date);
                    $condition_time = ' AND (
                        (login_date >= "'.$start_date.'" AND login_date <= "'.$end_date.'") OR
                        (logout_date >= "'.$start_date.'" AND logout_date <= "'.$end_date.'") OR
                        (login_date <= "'.$start_date.'" AND logout_date >= "'.$end_date.'")
                    ) ';
                }
                break;
            case 'custom':
                if (!empty($start_date) && !empty($end_date)) {
                    $start_date = Database::escape_string($start_date);
                    $end_date = Database::escape_string($end_date);
                    $condition_time = ' AND (login_date >= "'.$start_date.'" AND logout_date <= "'.$end_date.'" ) ';
                }
                break;
        }

        if ($returnAllRecords) {
            $sql = "SELECT login_date, logout_date, TIMESTAMPDIFF(SECOND, login_date, logout_date) diff
                    FROM $tbl_track_login u $url_table
                    WHERE $userCondition $condition_time $url_condition
                    ORDER BY login_date";
            $rs = Database::query($sql);

            return Database::store_result($rs, 'ASSOC');
        }

        $sql = "SELECT SUM(TIMESTAMPDIFF(SECOND, login_date, logout_date)) diff
    	        FROM $tbl_track_login u $url_table
                WHERE $userCondition $condition_time $url_condition";
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

            if ($value && isset($value['value']) && 1 == $value['value']) {
                return true;
            }
        } else {
            if ($courseId) {
                $extraFieldValue = new ExtraFieldValue('course');
                $value = $extraFieldValue->get_values_by_handler_and_field_variable($courseId, 'new_tracking_system');
                if ($value && isset($value['value']) && 1 == $value['value']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Calculates the time spent on the course.
     *
     * @param array|int $user_id
     * @param int       $courseId
     * @param int       $sessionId
     *
     * @return int Time in seconds
     */
    public static function get_time_spent_on_the_course(
        $user_id,
        $courseId,
        $sessionId = 0
    ) {
        $courseId = (int) $courseId;

        if (empty($courseId) || empty($user_id)) {
            return 0;
        }

        if (self::minimumTimeAvailable($sessionId, $courseId)) {
            $courseTime = self::getCalculateTime($user_id, $courseId, $sessionId);

            return isset($courseTime['total_time']) ? $courseTime['total_time'] : 0;
        }

        $conditionUser = '';
        $sessionId = (int) $sessionId;
        if (is_array($user_id)) {
            $user_id = array_map('intval', $user_id);
            $conditionUser = " AND user_id IN (".implode(',', $user_id).") ";
        } else {
            if (!empty($user_id)) {
                $user_id = (int) $user_id;
                $conditionUser = " AND user_id = $user_id ";
            }
        }

        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $sql = "SELECT
                SUM(UNIX_TIMESTAMP(logout_course_date) - UNIX_TIMESTAMP(login_course_date)) as nb_seconds
                FROM $table
                WHERE
                    UNIX_TIMESTAMP(logout_course_date) > UNIX_TIMESTAMP(login_course_date) AND
                    c_id = '$courseId' ";

        if (-1 != $sessionId) {
            $sql .= "AND session_id = '$sessionId' ";
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
        $sessionId = 0,
        $convert_date = true
    ) {
        $student_id = (int) $student_id;
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;

        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $sql = 'SELECT login_course_date
                FROM '.$table.'
                WHERE
                    user_id = '.$student_id.' AND
                    c_id = '.$courseId.' AND
                    session_id = '.$sessionId.'
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
        $sessionId = 0,
        $convert_date = true
    ) {
        // protect data
        $student_id = (int) $student_id;
        $sessionId = (int) $sessionId;

        if (empty($courseInfo) || empty($student_id)) {
            return false;
        }

        $courseId = (int) $courseInfo['real_id'];

        if (empty($courseId)) {
            return false;
        }

        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

        if (self::minimumTimeAvailable($sessionId, $courseId)) {
            // Show the last date on which the user acceed the session when it was active
            $where_condition = '';
            $userInfo = api_get_user_info($student_id);
            if (STUDENT == $userInfo['status'] && !empty($sessionId)) {
                // fin de acceso a la sesión
                $sessionInfo = SessionManager::fetch($sessionId);
                $last_access = $sessionInfo['access_end_date'];
                if (!empty($last_access)) {
                    $where_condition = ' AND logout_course_date < "'.$last_access.'" ';
                }
            }
            $sql = "SELECT logout_course_date
                    FROM $table
                    WHERE   user_id = $student_id AND
                            c_id = $courseId AND
                            session_id = $sessionId $where_condition
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
                            session_id = $sessionId
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
                                    'announcements/announcements.php?action=add&remind_inactive='.$student_id.'&cid='.$courseInfo['real_id'];
                                $icon = '<a href="'.$url.'" title="'.get_lang('Remind inactive user').'">
                                  '.Display::getMdiIcon('alert').'
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

    public static function getLastConnectionInAnyCourse($studentId)
    {
        $studentId = (int) $studentId;

        if (empty($studentId)) {
            return false;
        }

        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $sql = "SELECT logout_course_date
                FROM $table
                WHERE user_id = $studentId
                ORDER BY logout_course_date DESC
                LIMIT 1";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result);

            return $row['logout_course_date'];
        }

        return false;
    }

    /**
     * Get last course access by course/session.
     */
    public static function getLastConnectionDateByCourse($courseId, $sessionId = 0)
    {
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

        $sql = "SELECT logout_course_date
                FROM $table
                WHERE
                        c_id = $courseId AND
                        session_id = $sessionId
                ORDER BY logout_course_date DESC
                LIMIT 0,1";

        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result);
            if ($row) {
                return $row['logout_course_date'];
            }
        }

        return '';
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
        $sessionId = 0,
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
        $sessionId = (int) $sessionId;
        $count = 0;
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $sql = "SELECT count(*) as count_connections
                FROM $table
                WHERE
                    c_id = $courseId AND
                    session_id = $sessionId
                    $month_filter";

        //This query can be very slow (several seconds on an indexed table
        // with 14M rows). As such, we'll try to use APCu if it is
        // available to store the resulting value for a few seconds
        $cacheAvailable = api_get_configuration_value('apc');
        if (true === $cacheAvailable) {
            $apc = apcu_cache_info(true);
            $apc_end = $apc['start_time'] + $apc['ttl'];
            $apc_var = api_get_configuration_value('apc_prefix').'course_access_'.$courseId.'_'.$sessionId.'_'.strtotime($roundedStart).'_'.strtotime($roundedStop);
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
     * @param null $sessionId
     * @param int  $active_filter 2 for consider all tests
     *                            1 for active <> -1
     *                            0 for active <> 0
     * @param int  $into_lp       1 for all exercises
     *                            0 for without LP
     * @param mixed id
     * @param string code
     * @param int id (optional), filtered by exercise
     * @param int id (optional), if param $sessionId is null
     *                                               it'll return results including sessions, 0 = session is not
     *                                               filtered
     *
     * @return string value (number %) Which represents a round integer about the score average
     */
    public static function get_avg_student_exercise_score(
        $student_id,
        $course_code,
        $exercise_id = 0,
        $sessionId = null,
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
            $condition_quiz = '';
            if (!empty($exercise_id)) {
                $exercise_id = (int) $exercise_id;
                $condition_quiz = " AND iid = $exercise_id ";
            }

            // Compose a filter based on optional session id given
            $condition_session = '';
            $session = null;
            if (isset($sessionId)) {
                $session = api_get_session_entity($course_info['real_id']);
                $sessionId = (int) $sessionId;
                $condition_session = " AND session_id = $sessionId ";
            }

            $condition_active = '';
            if (1 == $active_filter) {
                $condition_active = 'AND active <> -1';
            } elseif (0 == $active_filter) {
                $condition_active = 'AND active <> 0';
            }
            $condition_into_lp = '';
            $select_lp_id = '';
            if (0 == $into_lp) {
                $condition_into_lp = 'AND orig_lp_id = 0 AND orig_lp_item_id = 0';
            } else {
                $select_lp_id = ', orig_lp_id as lp_id ';
            }

            $quizRepo = Container::getQuizRepository();
            $course = api_get_course_entity($course_info['real_id']);
            $qb = $quizRepo->getResourcesByCourse($course, $session);
            $qb
                ->select('count(resource)')
                ->setMaxResults(1);
            $count_quiz = $qb->getQuery()->getSingleScalarResult();

            /*$sql = "SELECT count(iid)
    		        FROM $tbl_course_quiz
    				WHERE c_id = {$course_info['real_id']} $condition_active $condition_quiz ";
            $count_quiz = 0;
            $countQuizResult = Database::query($sql);
            if (!empty($countQuizResult)) {
                $count_quiz = Database::fetch_row($countQuizResult);
            }*/
            if (!empty($count_quiz) && !empty($student_id)) {
                if (is_array($student_id)) {
                    $student_id = array_map('intval', $student_id);
                    $condition_user = " AND exe_user_id IN (".implode(',', $student_id).") ";
                } else {
                    $student_id = (int) $student_id;
                    $condition_user = " AND exe_user_id = '$student_id' ";
                }

                if (empty($exercise_id)) {
                    $sql = "SELECT iid FROM $tbl_course_quiz
                            WHERE c_id = {$course_info['real_id']} $condition_active $condition_quiz";
                    $result = Database::query($sql);
                    $exercise_list = [];
                    $exercise_id = null;
                    if (!empty($result) && Database::num_rows($result)) {
                        while ($row = Database::fetch_array($result)) {
                            $exercise_list[] = $row['iid'];
                        }
                    }
                    if (!empty($exercise_list)) {
                        $exercise_id = implode("','", $exercise_list);
                    }
                }

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
                if (0 == $into_lp) {
                    return $quiz_avg_score;
                } else {
                    if (!empty($row['lp_id'])) {
                        $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
                        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
                        $sql = "SELECT lp.name
                                FROM $tbl_lp as lp, $tbl_course as c
                                WHERE
                                    c.code = '$course_code' AND
                                    lp.iid = ".$row['lp_id']." AND
                                    lp.c_id = c.id
                                LIMIT 1;
                        ";
                        $result = Database::query($sql);
                        $row_lp = Database::fetch_row($result);
                        $lp_name = null;
                        if ($row_lp && isset($row_lp[0])) {
                            $lp_name = $row_lp[0];
                        }

                        return [$quiz_avg_score, $lp_name];
                    }

                    return [$quiz_avg_score, null];
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
     * @param int $sessionId
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
        $sessionId = 0,
        $find_all_lp = 0
    ) {
        $courseId = intval($courseId);
        $student_id = intval($student_id);
        $exercise_id = intval($exercise_id);
        $sessionId = intval($sessionId);

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
                    session_id = $sessionId ";

        if (1 == $find_all_lp) {
            $sql .= "AND (orig_lp_id = $lp_id OR orig_lp_id = 0)
                AND (orig_lp_item_id = $lp_item_id OR orig_lp_item_id = 0)";
        } elseif (0 == $find_all_lp) {
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
     * @param CQuiz[] $exerciseList
     * @param int     $user_id
     * @param int     $courseId
     * @param int     $sessionId
     *
     * @return string
     */
    public static function get_exercise_student_progress(
        $exerciseList,
        $user_id,
        $courseId,
        $sessionId
    ) {
        $courseId = (int) $courseId;
        $user_id = (int) $user_id;
        $sessionId = (int) $sessionId;

        if (empty($exerciseList)) {
            return '0%';
        }
        $tbl_stats_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $exerciseIdList = [];
        foreach ($exerciseList as $exercise) {
            $exerciseIdList[] = $exercise->getIid();
        }
        $exercise_list_imploded = implode("' ,'", $exerciseIdList);

        $sql = "SELECT COUNT(DISTINCT ex.exe_exo_id)
                FROM $tbl_stats_exercises AS ex
                WHERE
                    ex.c_id = $courseId AND
                    ex.session_id  = $sessionId AND
                    ex.exe_user_id = $user_id AND
                    ex.status = '' AND
                    ex.exe_exo_id IN ('$exercise_list_imploded') ";

        $rs = Database::query($sql);
        $count = 0;
        if ($rs) {
            $row = Database::fetch_row($rs);
            $count = (int) $row[0];
        }
        $count = (0 != $count) ? 100 * round($count / count($exerciseList), 2).'%' : '0%';

        return $count;
    }

    /**
     * @param CQuiz $exercise_list
     * @param int   $user_id
     * @param int   $courseId
     * @param int   $sessionId
     *
     * @return string
     */
    public static function get_exercise_student_average_best_attempt(
        $exercise_list,
        $user_id,
        $courseId,
        $sessionId
    ) {
        $result = 0;
        if (!empty($exercise_list)) {
            foreach ($exercise_list as $exercise_data) {
                $exercise_id = $exercise_data->getIid();
                $best_attempt = Event::get_best_attempt_exercise_results_per_user(
                    $user_id,
                    $exercise_id,
                    $courseId,
                    $sessionId
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
     * @param int|array     $studentId
     * @param array         $lpIdList        Limit average to listed lp ids
     * @param SessionEntity $session         Session id (optional),
     *                                       if parameter $sessionId is null(default) it'll return results including
     *                                       sessions, 0 = session is not filtered
     * @param bool          $returnArray     Will return an array of the type:
     *                                       [sum_of_progresses, number] if it is set to true
     * @param bool          $onlySeriousGame Optional. Limit average to lp on seriousgame mode
     *
     * @return float Average progress of the user in this course from 0 to 100
     */
    public static function get_avg_student_progress(
        $studentId,
        Course $course,
        $lpIdList = [],
        SessionEntity $session = null,
        $returnArray = false,
        $onlySeriousGame = false
    ) {
        // If there is at least one learning path and one student.
        if (empty($studentId)) {
            return false;
        }

        $repo = Container::getLpRepository();
        $qb = $repo->findAllByCourse($course, $session);
        $lps = $qb->getQuery()->getResult();
        $filteredLP = [];

        $sessionId = null !== $session ? $session->getId() : 0;

        /** @var CLp $lp */
        foreach ($lps as $lp) {
            $filteredLP[] = $lp->getIid();
        }

        if (empty($filteredLP)) {
            return false;
        }

        $lpViewTable = Database::get_course_table(TABLE_LP_VIEW);
        /*$lpConditions = [];
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
            $lpConditions['AND iid IN('.implode(', ', $placeHolders).') '] = $lpIdList;
        }

        if ($onlySeriousGame) {
            $lpConditions['AND seriousgame_mode = ? '] = true;
        }

        $resultLP = Database::select(
            'iid',
            $lPTable,
            ['where' => $lpConditions]
        );
        $filteredLP = array_keys($resultLP);

        if (empty($filteredLP)) {
            return false;
        }*/

        $conditions = [
            //" c_id = {$courseInfo['real_id']} ",
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
                    ['real_id' => $course->getId()],
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
     * @param mixed         $student_id                      Array of user ids or an user id
     * @param array         $lp_ids                          List of LP ids
     * @param SessionEntity $session
     *                                                       if param $sessionId is null(default) it'll return results
     *                                                       including sessions, 0 = session is not filtered
     * @param bool          $return_array                    Returns an array of the
     *                                                       type [sum_score, num_score] if set to true
     * @param bool          $get_only_latest_attempt_results get only the latest attempts or ALL attempts
     * @param bool          $getOnlyBestAttempt
     *
     * @return string value (number %) Which represents a round integer explain in got in 3
     *
     * @todo improve performance, when loading 1500 users with 20 lps the script dies
     * This function does not take the results of a Test out of a LP
     */
    public static function get_avg_student_score(
        $student_id,
        Course $course,
        $lp_ids = [],
        SessionEntity $session = null,
        $return_array = false,
        $get_only_latest_attempt_results = false,
        $getOnlyBestAttempt = false
    ) {
        if (empty($student_id)) {
            return null;
        }

        $debug = false;
        $tbl_stats_exercices = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $tbl_stats_attempts = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        // Get course tables names
        $tbl_quiz_questions = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $lp_table = Database::get_course_table(TABLE_LP_MAIN);
        $lp_item_table = Database::get_course_table(TABLE_LP_ITEM);
        $lp_view_table = Database::get_course_table(TABLE_LP_VIEW);
        $lp_item_view_table = Database::get_course_table(TABLE_LP_ITEM_VIEW);
        $courseId = $course->getId();

        // Compose a filter based on optional learning paths list given
        $condition_lp = '';
        if (count($lp_ids) > 0) {
            $condition_lp = " iid IN(".implode(',', $lp_ids).") ";
        }

        // Compose a filter based on optional session id
        $sessionId = null;
        if (null !== $session) {
            $sessionId = $session->getId();
        }
        $sessionCondition = api_get_session_condition($sessionId);

        //$sessionId = (int) $sessionId;
        /*if (count($lp_ids) > 0) {
            $condition_session = " AND session_id = $sessionId ";
        } else {
            $condition_session = " WHERE session_id = $sessionId ";
        }

        // Check the real number of LPs corresponding to the filter in the
        // database (and if no list was given, get them all)
        if (empty($sessionId)) {
            $sql = "SELECT DISTINCT(iid), use_max_score
                    FROM $lp_table
                    WHERE
                        c_id = $courseId AND
                        (session_id = 0 OR session_id IS NULL) $condition_lp ";
        } else {

        }*/

        $lp_list = $use_max_score = [];
        if (empty($condition_lp)) {
            $repo = Container::getLpRepository();
            $qb = $repo->findAllByCourse($course, $session);
            $lps = $qb->getQuery()->getResult();
            /** @var CLp $lp */
            foreach ($lps as $lp) {
                $lpId = $lp->getIid();
                $lp_list[] = $lpId;
                $use_max_score[$lpId] = $lp->getUseMaxScore();
            }
        } else {
            $sql = "SELECT DISTINCT(iid), use_max_score
                    FROM $lp_table
                    WHERE $condition_lp ";
            $res_row_lp = Database::query($sql);
            while ($row_lp = Database::fetch_array($res_row_lp)) {
                $lp_list[] = $row_lp['iid'];
                $use_max_score[$row_lp['iid']] = $row_lp['use_max_score'];
            }
        }

        if (empty($lp_list)) {
            return null;
        }

        // prepare filter on users
        if (is_array($student_id)) {
            array_walk($student_id, 'intval');
            $condition_user1 = " AND user_id IN (".implode(',', $student_id).") ";
        } else {
            $condition_user1 = " AND user_id = $student_id ";
        }

        // Getting latest LP result for a student
        //@todo problem when a  course have more than 1500 users
        $sql = "SELECT MAX(view_count) as vc, iid, progress, lp_id, user_id
                FROM $lp_view_table
                WHERE
                    lp_id IN (".implode(',', $lp_list).")
                    $condition_user1
                GROUP BY lp_id, user_id";
        //AND        session_id = $sessionId

        $rs_last_lp_view_id = Database::query($sql);
        $global_result = 0;
        if (Database::num_rows($rs_last_lp_view_id) > 0) {
            // Cycle through each line of the results (grouped by lp_id, user_id)
            while ($row_lp_view = Database::fetch_array($rs_last_lp_view_id)) {
                $count_items = 0;
                $lpPartialTotal = 0;
                $list = [];
                $lp_view_id = $row_lp_view['iid'];
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
                                    lp_iv.iid as lp_item_view_id,
                                    lp_iv.score as score,
                                    lp_i.max_score,
                                    lp_iv.max_score as max_score_item_view,
                                    lp_i.path,
                                    lp_i.item_type,
                                    lp_i.iid
                                FROM $lp_item_view_table as lp_iv
                                INNER JOIN $lp_item_table as lp_i
                                ON (
                                    lp_i.iid = lp_iv.lp_item_id
                                )
                                WHERE
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
                                lp_iv.iid as lp_item_view_id,
                                lp_iv.score as score,
                                lp_i.max_score,
                                lp_iv.max_score as max_score_item_view,
                                lp_i.path,
                                lp_i.item_type,
                                lp_i.iid
                              FROM $lp_item_view_table as lp_iv
                              INNER JOIN $lp_item_table as lp_i
                              ON lp_i.iid = lp_iv.lp_item_id
                              WHERE
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

                    if ('sco' === $row_max_score['item_type']) {
                        /* Check if it is sco (easier to get max_score)
                           when there's no max score, we assume 100 as the max score,
                           as the SCORM 1.2 says that the value should always be between 0 and 100.
                        */
                        if (0 == $max_score || is_null($max_score) || '' == $max_score) {
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
                                    c_id = $courseId AND
                                    status = ''
                                    $sessionCondition
                                ORDER BY $order
                                LIMIT 1";

                        $result_last_attempt = Database::query($sql);
                        $num = Database::num_rows($result_last_attempt);
                        if ($num > 0) {
                            $attemptResult = Database::fetch_array($result_last_attempt, 'ASSOC');
                            $id_last_attempt = $attemptResult['exe_id'];
                            // We overwrite the score with the best one not the one saved in the LP (latest)
                            if ($getOnlyBestAttempt && false == $get_only_latest_attempt_results) {
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
                                            ON (q.iid = at.question_id)
                                            WHERE
                                                exe_id ='$id_last_attempt' AND
                                                at.c_id = $courseId
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
                            if ('' != $max_score) {
                                $count_items++;
                            }
                        }
                        if ($debug) {
                            echo '$count_items: '.$count_items;
                        }
                    }
                }

                $score_of_scorm_calculate += $count_items ? (($lpPartialTotal / $count_items) * 100) : 0;
                $global_result += $score_of_scorm_calculate;

                if ($debug) {
                    var_dump("count_items: $count_items");
                    var_dump("score_of_scorm_calculate: $score_of_scorm_calculate");
                    var_dump("global_result: $global_result");
                }
            }
        }

        $lp_with_quiz = 0;
        foreach ($lp_list as $lp_id) {
            // Check if LP have a score we assume that all SCO have an score
            $sql = "SELECT count(iid) as count
                    FROM $lp_item_table
                    WHERE
                        (item_type = 'quiz' OR item_type = 'sco') AND
                        lp_id = ".$lp_id;
            $result_have_quiz = Database::query($sql);
            if (Database::num_rows($result_have_quiz) > 0) {
                $row = Database::fetch_array($result_have_quiz, 'ASSOC');
                if (is_numeric($row['count']) && 0 != $row['count']) {
                    $lp_with_quiz++;
                }
            }
        }

        if ($debug) {
            echo '<h3>$lp_with_quiz '.$lp_with_quiz.' </h3>';
            echo '<h3>Final return</h3>';
        }

        if (0 != $lp_with_quiz) {
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
     * @param int       $sessionId   Session id (optional), if param $sessionId is 0(default)
     *                               it'll return results including sessions, 0 = session is not filtered
     *
     * @return string value (number %) Which represents a round integer explain in got in 3
     */
    public static function getAverageStudentScore(
        $student_id,
        $course_code = '',
        $lp_ids = [],
        $sessionId = 0
    ) {
        if (empty($student_id)) {
            return 0;
        }

        $conditions = [];
        if (!empty($course_code)) {
            $course = api_get_course_info($course_code);
            $courseId = $course['real_id'];
            //$conditions[] = " lp.c_id = $courseId";
        }

        // Get course tables names
        $lp_table = Database::get_course_table(TABLE_LP_MAIN);
        $lp_item_table = Database::get_course_table(TABLE_LP_ITEM);
        $lp_view_table = Database::get_course_table(TABLE_LP_VIEW);
        $lp_item_view_table = Database::get_course_table(TABLE_LP_ITEM_VIEW);

        // Compose a filter based on optional learning paths list given
        if (!empty($lp_ids) && count($lp_ids) > 0) {
            $conditions[] = ' lp.iid IN ('.implode(',', $lp_ids).') ';
        }

        // Compose a filter based on optional session id
        $sessionId = (int) $sessionId;
        if (!empty($sessionId)) {
            $conditions[] = " lp_view.session_id = $sessionId ";
        }

        if (is_array($student_id)) {
            array_walk($student_id, 'intval');
            $conditions[] = " lp_view.user_id IN (".implode(',', $student_id).") ";
        } else {
            $student_id = (int) $student_id;
            $conditions[] = " lp_view.user_id = $student_id ";
        }

        $conditionsToString = implode(' AND ', $conditions);
        $sql = "SELECT
                    SUM(lp_iv.score) sum_score,
                    SUM(lp_i.max_score) sum_max_score
                FROM $lp_table as lp
                INNER JOIN $lp_item_table as lp_i
                ON lp.iid = lp_i.lp_id
                INNER JOIN $lp_view_table as lp_view
                ON lp_view.lp_id = lp_i.lp_id
                INNER JOIN $lp_item_view_table as lp_iv
                ON
                    lp_i.iid = lp_iv.lp_item_id AND
                    lp_iv.lp_view_id = lp_view.iid
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
     * @param int|array $student_id Student id(s)
     * @param Course    $course     Course code
     * @param array     $lp_ids     Limit average to listed lp ids
     * @param int       $sessionId  Session id (optional), if param $sessionId is null(default)
     *                              it'll return results including sessions, 0 = session is not filtered
     *
     * @return int Total time in seconds
     */
    public static function get_time_spent_in_lp(
        $student_id,
        Course $course,
        $lp_ids = [],
        $sessionId = 0
    ) {
        $student_id = (int) $student_id;
        $sessionId = (int) $sessionId;
        $total_time = 0;

        if (!empty($course)) {
            $lpTable = Database::get_course_table(TABLE_LP_MAIN);
            $lpItemTable = Database::get_course_table(TABLE_LP_ITEM);
            $lpViewTable = Database::get_course_table(TABLE_LP_VIEW);
            $lpItemViewTable = Database::get_course_table(TABLE_LP_ITEM_VIEW);
            $trackExercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
            $courseId = $course->getId();

            // Compose a filter based on optional learning paths list given
            $condition_lp = '';
            if (count($lp_ids) > 0) {
                $condition_lp = " iid IN(".implode(',', $lp_ids).") ";
            }

            // Check the real number of LPs corresponding to the filter in the
            // database (and if no list was given, get them all)
            $sql = "SELECT DISTINCT(iid) FROM $lpTable
                    WHERE $condition_lp";
            $result = Database::query($sql);
            $session_condition = api_get_session_condition($sessionId);

            // calculates time
            if (Database::num_rows($result) > 0) {
                while ($row = Database::fetch_array($result)) {
                    $lp_id = (int) $row['iid'];
                    $lp = Container::getLpRepository()->find($lp_id);
                    // Start Exercise in LP total_time
                    // Get duration time from track_e_exercises.exe_duration instead of lp_view_item.total_time
                    $list = learnpath::get_flat_ordered_items_list($lp, 0, $courseId);
                    foreach ($list as $itemId) {
                        $sql = "SELECT max(view_count)
                                FROM $lpViewTable
                                WHERE
                                    c_id = $courseId AND
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
                            i.iid as myid,
                            iv.view_count as iv_view_count,
                            path
                        FROM $lpItemTable as i
                        INNER JOIN $lpItemViewTable as iv
                        ON (i.iid = iv.lp_item_id)
                        INNER JOIN $lpViewTable as v
                        ON (iv.lp_view_id = v.iid)
                        WHERE
                            v.c_id = $courseId AND
                            i.iid = $itemId AND
                            i.lp_id = $lp_id  AND
                            v.user_id = $student_id AND
                            item_type = 'quiz' AND
                            path <> '' AND
                            v.session_id = $sessionId
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
                                        c_id = '.$courseId.' AND
                                        status <> "incomplete" AND
                                        session_id = '.$sessionId.'
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
                                    $sqlUpdate = "UPDATE $lpItemViewTable
                                                  SET total_time = '$exeDuration'
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
                                item_view.lp_view_id = view.iid
                            )
                            WHERE
                                view.c_id = $courseId AND
                                view.lp_id = $lp_id AND
                                view.user_id = $student_id AND
                                session_id = $sessionId";

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
     * @param int       $sessionId
     *
     * @return int last connection timestamp
     */
    public static function get_last_connection_time_in_lp(
        $student_id,
        $course_code,
        $lp_id,
        $sessionId = 0
    ) {
        $course = api_get_course_info($course_code);
        if (empty($course)) {
            return 0;
        }

        $courseId = $course['real_id'];
        $student_id = (int) $student_id;
        $lp_id = (int) $lp_id;
        $sessionId = (int) $sessionId;
        $lastTime = 0;

        if (self::minimumTimeAvailable($sessionId, $courseId)) {
            $sql = "SELECT MAX(date_reg) max
                    FROM track_e_access_complete
                    WHERE
                        user_id = $student_id AND
                        c_id = $courseId AND
                        session_id = $sessionId AND
                        tool = 'learnpath' AND
                        tool_id = $lp_id AND
                        action = 'view' AND
                        login_as = 0
                    ORDER BY date_reg ASC
                    LIMIT 1";
            $rs = Database::query($sql);

            $lastConnection = 0;
            if (Database::num_rows($rs) > 0) {
                $value = Database::fetch_array($rs);
                if (isset($value['max']) && !empty($value['max'])) {
                    $lastConnection = api_strtotime($value['max'], 'UTC');
                }
            }

            if (!empty($lastConnection)) {
                return $lastConnection;
            }
        }
        if (!empty($course)) {
            $lp_table = Database::get_course_table(TABLE_LP_MAIN);
            $t_lpv = Database::get_course_table(TABLE_LP_VIEW);
            $t_lpiv = Database::get_course_table(TABLE_LP_ITEM_VIEW);

            // Check the real number of LPs corresponding to the filter in the
            // database (and if no list was given, get them all)
            $sql = "SELECT iid FROM $lp_table
                    WHERE iid = $lp_id ";
            $row = Database::query($sql);
            $count = Database::num_rows($row);

            // calculates last connection time
            if ($count > 0) {
                $sql = 'SELECT MAX(start_time)
                        FROM '.$t_lpiv.' AS item_view
                        INNER JOIN '.$t_lpv.' AS view
                        ON (item_view.lp_view_id = view.iid)
                        WHERE
                            status != "not attempted" AND
                            view.c_id = '.$courseId.' AND
                            view.lp_id = '.$lp_id.' AND
                            view.user_id = '.$student_id.' AND
                            view.session_id = '.$sessionId;
                $rs = Database::query($sql);
                if (Database::num_rows($rs) > 0) {
                    $lastTime = Database::result($rs, 0, 0);
                }
            }
        }

        return $lastTime;
    }

    public static function getFirstConnectionTimeInLp(
        $student_id,
        $course_code,
        $lp_id,
        $sessionId = 0
    ) {
        $course = api_get_course_info($course_code);
        $student_id = (int) $student_id;
        $lp_id = (int) $lp_id;
        $sessionId = (int) $sessionId;
        $time = 0;

        if (!empty($course)) {
            $courseId = $course['real_id'];
            $lp_table = Database::get_course_table(TABLE_LP_MAIN);
            $t_lpv = Database::get_course_table(TABLE_LP_VIEW);
            $t_lpiv = Database::get_course_table(TABLE_LP_ITEM_VIEW);

            // Check the real number of LPs corresponding to the filter in the
            // database (and if no list was given, get them all)
            $sql = "SELECT iid FROM $lp_table
                    WHERE iid = $lp_id ";
            $row = Database::query($sql);
            $count = Database::num_rows($row);

            // calculates first connection time
            if ($count > 0) {
                $sql = 'SELECT MIN(start_time)
                        FROM '.$t_lpiv.' AS item_view
                        INNER JOIN '.$t_lpv.' AS view
                        ON (item_view.lp_view_id = view.iid)
                        WHERE
                            status != "not attempted" AND
                            view.c_id = '.$courseId.' AND
                            view.lp_id = '.$lp_id.' AND
                            view.user_id = '.$student_id.' AND
                            view.session_id = '.$sessionId;
                $rs = Database::query($sql);
                if (Database::num_rows($rs) > 0) {
                    $time = Database::result($rs, 0, 0);
                }
            }
        }

        return $time;
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
                WHERE user_id='.$coach_id.' AND status = '.SessionEntity::COURSE_COACH;

        if (api_is_multiple_url_enabled()) {
            $tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
            $access_url_id = api_get_current_access_url_id();
            if (-1 != $access_url_id) {
                $sql = 'SELECT scu.session_id, scu.c_id
                        FROM '.$tbl_session_course_user.' scu
                        INNER JOIN '.$tbl_session_rel_access_url.'  sru
                        ON (scu.session_id=sru.session_id)
                        WHERE
                            scu.user_id='.$coach_id.' AND
                            scu.status = '.SessionEntity::COURSE_COACH.' AND
                            sru.access_url_id = '.$access_url_id;
            }
        }

        $result = Database::query($sql);

        while ($a_courses = Database::fetch_array($result)) {
            $courseId = $a_courses['c_id'];
            $sessionId = $a_courses['session_id'];

            $sql = "SELECT DISTINCT srcru.user_id
                    FROM $tbl_session_course_user AS srcru
                    INNER JOIN $tbl_session_user sru
                    ON (srcru.user_id = sru.user_id AND srcru.session_id = sru.session_id)
                    WHERE
                        sru.relation_type = ".SessionEntity::STUDENT." AND
                        srcru.c_id = '$courseId' AND
                        srcru.session_id = '$sessionId'";

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
            if (-1 != $access_url_id) {
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
                WHERE user_id='.$coach_id.' AND status = '.SessionEntity::COURSE_COACH;
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
    public static function get_courses_followed_by_coach($coach_id, $sessionId = 0)
    {
        $coach_id = intval($coach_id);
        if (!empty($sessionId)) {
            $sessionId = intval($sessionId);
        }

        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $tblSessionRelUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);

        // At first, courses where $coach_id is coach of the course.
        $sql = 'SELECT DISTINCT c.code
                FROM '.$tbl_session_course_user.' sc
                INNER JOIN '.$tbl_course.' c
                ON (c.id = sc.c_id)
                WHERE sc.user_id = '.$coach_id.' AND sc.status = '.SessionEntity::COURSE_COACH;

        if (api_is_multiple_url_enabled()) {
            $access_url_id = api_get_current_access_url_id();
            if (-1 != $access_url_id) {
                $sql = 'SELECT DISTINCT c.code
                        FROM '.$tbl_session_course_user.' scu
                        INNER JOIN '.$tbl_course.' c
                        ON (c.code = scu.c_id)
                        INNER JOIN '.$tbl_course_rel_access_url.' cru
                        ON (c.id = cru.c_id)
                        WHERE
                            scu.user_id='.$coach_id.' AND
                            scu.status = '.SessionEntity::COURSE_COACH.' AND
                            cru.access_url_id = '.$access_url_id;
            }
        }

        if (!empty($sessionId)) {
            $sql .= ' AND session_id='.$sessionId;
        }

        $courseList = [];
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            $courseList[$row['code']] = $row['code'];
        }

        // Then, courses where $coach_id is coach of the session
        $sql = "SELECT DISTINCT course.code
                FROM $tbl_session_course as session_course
                INNER JOIN $tbl_session as session
                    ON (session.id = session_course.session_id)
                INNER JOIN $tblSessionRelUser session_user
                    ON (session.id = session_user.session_id
                    AND session_user.user_id = $coach_id
                    AND session_user.relation_type = ".SessionEntity::SESSION_COACH.")
                INNER JOIN $tbl_course as course
                    ON course.id = session_course.c_id";

        if (api_is_multiple_url_enabled()) {
            $tbl_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
            $access_url_id = api_get_current_access_url_id();
            if (-1 != $access_url_id) {
                $sql = "SELECT DISTINCT c.code
                    FROM $tbl_session_course as session_course
                    INNER JOIN $tbl_course c
                    ON (c.id = session_course.c_id)
                    INNER JOIN $tbl_session as session
                    ON session.id = session_course.session_id
                    INNER JOIN $tblSessionRelUser session_user
                        ON (session.id = session_user.session_id
                        AND session_user.user_id = $coach_id
                        AND session_user.relation_type = ".SessionEntity::SESSION_COACH.")
                    INNER JOIN $tbl_course as course
                        ON course.id = session_course.c_id
                     INNER JOIN $tbl_course_rel_access_url course_rel_url
                    ON (course_rel_url.c_id = c.id)";
            }
        }

        if (!empty($sessionId)) {
            $sql .= ' WHERE session_course.session_id='.$sessionId;
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
                $orderBy .= " ORDER BY `$orderByName` $orderByDirection";
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
                    session_course_user.status = ".SessionEntity::COURSE_COACH."
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
            if ('0000-00-00 00:00:00' === $row['access_start_date']) {
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
    public static function get_courses_list_from_session($sessionId)
    {
        $sessionId = (int) $sessionId;

        // table definition
        $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);

        $sql = "SELECT DISTINCT code, c_id
                FROM $tbl_session_course sc
                INNER JOIN $courseTable c
                ON sc.c_id = c.id
                WHERE session_id= $sessionId";

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
     * if param $sessionId is null(default)
     * return count of assignments including sessions, 0 = session is not filtered
     *
     * @return int Number of documents
     */
    public static function count_student_uploaded_documents(
        $student_id,
        $course_code,
        $sessionId = null
    ) {
        $a_course = api_get_course_info($course_code);
        $repo = Container::getDocumentRepository();

        $user = api_get_user_entity($student_id);
        $course = api_get_course_entity($a_course['real_id']);
        $session = api_get_session_entity($sessionId);
        //$group = api_get_group_entity(api_get_group_id());

        $qb = $repo->getResourcesByCourseLinkedToUser($user, $course, $session);

        $qb->select('count(resource)');
        $count = $qb->getQuery()->getSingleScalarResult();

        return $count;
    }

    /**
     * This function counts the number of post by course.
     *
     * @param string $course_code
     * @param int    $sessionId   (optional), if is null(default) it'll return results including sessions,
     *                            0 = session is not filtered
     * @param int    $groupId
     *
     * @return int The number of post by course
     */
    public static function count_number_of_posts_by_course($course_code, $sessionId = null, $groupId = 0)
    {
        $courseInfo = api_get_course_info($course_code);
        if (!empty($courseInfo)) {
            $tbl_posts = Database::get_course_table(TABLE_FORUM_POST);
            $tbl_forums = Database::get_course_table(TABLE_FORUM);

            $condition_session = '';
            if (isset($sessionId)) {
                $sessionId = (int) $sessionId;
                $condition_session = api_get_session_condition(
                    $sessionId,
                    true,
                    false,
                    'f.session_id'
                );
            }

            $courseId = $courseInfo['real_id'];
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
                        p.c_id = $courseId AND
                        f.c_id = $courseId AND
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
     * if param $sessionId is null(default) it'll return results including
     * sessions, 0 = session is not filtered
     * @param int $groupId
     *
     * @return int The number of threads by course
     */
    public static function count_number_of_threads_by_course(
        $course_code,
        $sessionId = null,
        $groupId = 0
    ) {
        $course_info = api_get_course_info($course_code);
        if (empty($course_info)) {
            return null;
        }

        $courseId = $course_info['real_id'];
        $tbl_threads = Database::get_course_table(TABLE_FORUM_THREAD);
        $tbl_forums = Database::get_course_table(TABLE_FORUM);

        $condition_session = '';
        if (isset($sessionId)) {
            $sessionId = (int) $sessionId;
            $condition_session = ' AND f.session_id = '.$sessionId;
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
                    t.c_id = $courseId AND
                    f.c_id = $courseId AND
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
     * if param $sessionId is null(default) it'll return results
     * including sessions, 0 = session is not filtered
     * @param int $groupId
     *
     * @return int The number of forums by course
     */
    public static function count_number_of_forums_by_course(
        $course_code,
        $sessionId = null,
        $groupId = 0
    ) {
        $course_info = api_get_course_info($course_code);
        if (empty($course_info)) {
            return null;
        }
        $courseId = $course_info['real_id'];

        $condition_session = '';
        if (isset($sessionId)) {
            $sessionId = (int) $sessionId;
            $condition_session = ' AND f.session_id = '.$sessionId;
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
                    f.c_id = $courseId AND
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
        $courseId = $course_info['real_id'];

        // Protect data
        $last_days = (int) $last_days;
        $session_id = (int) $session_id;

        $tbl_stats_access = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ACCESS);
        $now = api_get_utc_datetime();

        $sql = "SELECT count(*) FROM $tbl_stats_access
                WHERE
                    DATE_SUB('$now',INTERVAL $last_days DAY) <= access_date AND
                    c_id = '$courseId' AND
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
     * @param int $user_id   Student id
     * @param int $sessionId Session id (optional)
     *
     * @return array Courses list
     */
    public static function get_course_list_in_session_from_student($user_id, $sessionId = 0)
    {
        $user_id = (int) $user_id;
        $sessionId = (int) $sessionId;
        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);

        $sql = "SELECT c.code
                FROM $tbl_session_course_user sc
                INNER JOIN $courseTable c
                WHERE
                    user_id= $user_id  AND
                    session_id = $sessionId";
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

        if ('never' === $since) {
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

        $allow = 'true' === api_get_plugin_setting('pausetraining', 'tool_enable');
        $allowPauseFormation = 'true' === api_get_plugin_setting('pausetraining', 'allow_users_to_edit_pause_formation');

        $extraFieldValue = new ExtraFieldValue('user');
        $users = [];
        while ($user = Database::fetch_array($rs)) {
            $userId = $user['user_id'];

            if ($allow && $allowPauseFormation) {
                $pause = $extraFieldValue->get_values_by_handler_and_field_variable($userId, 'pause_formation');
                if (!empty($pause) && isset($pause['value']) && 1 == $pause['value']) {
                    // Skip user because he paused his formation.
                    continue;
                }
            }

            $users[] = $userId;
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
        $courseId = $course_info['real_id'];
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
                    cl.c_id = $courseId AND
                    sl.links_link_id = cl.id AND
                    sl.c_id = $courseId
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
                    FROM $tbl_course_user cu
                    INNER JOIN $tbl_course c
                    ON (cu.c_id = c.id)
                    WHERE
                        cu.user_id = $user_id AND
                        relation_type <> ".COURSE_RELATION_TYPE_RRHH."
                    ORDER BY title";
        }

        $rs = Database::query($sql);
        $courses = $course_in_session = $temp_course_in_session = [];
        $courseIdList = [];
        while ($row = Database::fetch_array($rs, 'ASSOC')) {
            $courses[$row['id']] = $row['title'];
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
                        if (isset($availableColumns[$columnKey]) && false == $availableColumns[$columnKey]) {
                            continue;
                        }
                    }
                    $html .= Display::tag('th', $name);
                }
                $html .= '</tr></thead><tbody>';

                foreach ($courses as $courseId => $course_title) {
                    $course = api_get_course_entity($courseId);
                    $courseCode = $course->getCode();

                    $total_time_login = self::get_time_spent_on_the_course(
                        $user_id,
                        $courseId
                    );
                    $time = api_time_to_hms($total_time_login);
                    $progress = self::get_avg_student_progress(
                        $user_id,
                        $course
                    );
                    $bestScore = self::get_avg_student_score(
                        $user_id,
                        $course,
                        [],
                        null,
                        false,
                        false,
                        true
                    );

                    /*$exerciseList = ExerciseLib::get_all_exercises(
                        $courseInfo,
                        0,
                        false,
                        null,
                        false,
                        1
                    );*/

                    $qb = Container::getQuizRepository()->findAllByCourse($course, null, null, 1, false);
                    /** @var CQuiz[] $exercises */
                    $exercises = $qb->getQuery()->getResult();

                    $bestScoreAverageNotInLP = 0;
                    if (!empty($exercises)) {
                        foreach ($exercises as $exerciseData) {
                            $results = Event::get_best_exercise_results_by_user(
                                $exerciseData->getIid(),
                                $courseId,
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
                        $bestScoreAverageNotInLP = round($bestScoreAverageNotInLP / count($exercises) * 100, 2);
                    }

                    $last_connection = self::get_last_connection_date_on_the_course(
                        $user_id,
                        ['real_id' => $courseId]
                    );

                    if (is_null($progress) || empty($progress)) {
                        $progress = '0%';
                    } else {
                        $progress = $progress.'%';
                    }

                    if (isset($_GET['course']) &&
                        $courseCode == $_GET['course'] &&
                        empty($_GET['session_id'])
                    ) {
                        $html .= '<tr class="row_odd" style="background-color:#FBF09D">';
                    } else {
                        $html .= '<tr class="row_even">';
                    }
                    $url = api_get_course_url($courseId, $session_id);
                    $course_url = Display::url($course_title, $url, ['target' => SESSION_LINK_TARGET]);
                    if (empty($bestScore)) {
                        $bestScoreResult = '-';
                    } else {
                        $bestScoreResult = $bestScore.'%';
                    }
                    if (empty($bestScoreAverageNotInLP)) {
                        $bestScoreNotInLP = '-';
                    } else {
                        $bestScoreNotInLP = $bestScoreAverageNotInLP.'%';
                    }

                    $detailsLink = '';
                    if (isset($_GET['course']) &&
                        $courseCode == $_GET['course'] &&
                        empty($_GET['session_id'])
                    ) {
                        $detailsLink .= '<a href="#course_session_header">';
                        $detailsLink .= Display::return_icon('2rightarrow_na.png', get_lang('Details'));
                        $detailsLink .= '</a>';
                    } else {
                        $detailsLink .= '<a href="'.api_get_self().'?course='.$courseCode.$extra_params.'#course_session_header">';
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
                            if (isset($availableColumns[$columnKey]) && false == $availableColumns[$columnKey]) {
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
                    $course = api_get_course_entity($course_data['real_id']);
                    $courseId = $course->getId();
                    /*$exercise_list = ExerciseLib::get_all_exercises(
                        $course_data,
                        $my_session_id,
                        false,
                        null,
                        false,
                        1
                    );*/

                    $qb = Container::getQuizRepository()->findAllByCourse($course, null, null, 1, false);
                    /** @var CQuiz[] $exerciseList */
                    $exercises = $qb->getQuery()->getResult();
                    $countExercises = count($exercises);
                    foreach ($exercises as $exercise_data) {
                        //$exercise_obj = new Exercise($course_data['real_id']);
                        //$exercise_obj->read($exercise_data['id']);
                        // Exercise is not necessary to be visible to show results check the result_disable configuration instead
                        //$visible_return = $exercise_obj->is_visible();
                        $disabled = $exercise_data->getResultsDisabled();
                        $exerciseId = $exercise_data->getIid();
                        if (0 == $disabled || 2 == $disabled) {
                            $best_average = (int)
                                ExerciseLib::get_best_average_score_by_exercise(
                                    $exerciseId,
                                    $courseId,
                                    $my_session_id,
                                    $user_count
                                )
                            ;

                            $exercise_graph_list[] = $best_average;
                            $all_exercise_graph_list[] = $best_average;

                            $user_result_data = ExerciseLib::get_best_attempt_by_user(
                                api_get_user_id(),
                                $exerciseId,
                                $courseId,
                                $my_session_id
                            );

                            $score = 0;
                            if (!empty($user_result_data['max_score']) && 0 != intval($user_result_data['max_score'])) {
                                $score = intval($user_result_data['score'] / $user_result_data['max_score'] * 100);
                            }
                            $start = $exercise_data->getStartTime() ? $exercise_data->getStartTime()->getTimestamp() : null;
                            $time = null !== $start ? $start : 0;
                            $all_exercise_start_time[] = $time;
                            $my_results[] = $score;
                            $exerciseTitle = $exercise_data->getTitle();
                            if ($countExercises <= 10) {
                                $title = cut($course_data['title'], 30)." \n ".cut($exerciseTitle, 30);
                                $exercise_graph_name_list[] = $title;
                                $all_exercise_graph_name_list[] = $title;
                            } else {
                                // if there are more than 10 results, space becomes difficult to find,
                                // so only show the title of the exercise, not the tool
                                $title = cut($exerciseTitle, 30);
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

            $session = api_get_session_entity($my_session_id);

            foreach ($course_in_session as $my_session_id => $session_data) {
                $course_list = $session_data['course_list'];
                $session_name = $session_data['name'];
                if (false == $showAllSessions) {
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
                    $courseId = $course_data['real_id'];
                    $course = api_get_course_entity($courseId);

                    // All exercises in the course @todo change for a real count
                    //$exercises = ExerciseLib::get_all_exercises($course_data, $my_session_id);

                    $qb = Container::getQuizRepository()->findAllByCourse($course, $session, null, 2);

                    /** @var CQuiz[] $exercises */
                    $exercises = $qb->getQuery()->getResult();
                    $count_exercises = count($exercises);

                    // Count of user results
                    $done_exercises = null;
                    $answered_exercises = 0;
                    if (!empty($exercises)) {
                        foreach ($exercises as $exercise_item) {
                            $attempts = Event::count_exercise_attempts_by_user(
                                api_get_user_id(),
                                $exercise_item->getIid(),
                                $courseId,
                                $my_session_id
                            );
                            if ($attempts > 1) {
                                $answered_exercises++;
                            }
                        }
                    }

                    // Average
                    $average = ExerciseLib::get_average_score_by_course(
                        $courseId,
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
                    $course = api_get_course_entity($courseId);
                    $session = api_get_session_entity($session_id_from_get);

                    $qb = Container::getQuizRepository()->findAllByCourse($course, $session, null, 2);

                    /** @var CQuiz[] $exercises */
                    $exercises = $qb->getQuery()->getResult();
                    $count_exercises = count($exerciseList);
                    $answered_exercises = 0;
                    foreach ($exercises as $exercise_item) {
                        $attempts = Event::count_exercise_attempts_by_user(
                            api_get_user_id(),
                            $exercise_item->getIid(),
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
                        $course,
                        [],
                        $session,
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
                        $course,
                        [],
                        $session
                    );

                    $total_time_login = self::get_time_spent_on_the_course(
                        $user_id,
                        $courseId,
                        $session_id_from_get
                    );
                    $time = api_time_to_hms($total_time_login);

                    $percentage_score = self::get_avg_student_score(
                        $user_id,
                        $course,
                        [],
                        $session
                    );
                    $courseCodeFromGet = isset($_GET['course']) ? $_GET['course'] : null;

                    if ($course_code == $courseCodeFromGet && $_GET['session_id'] == $session_id_from_get) {
                        $html .= '<tr class="row_odd" style="background-color:#FBF09D" >';
                    } else {
                        $html .= '<tr class="row_even">';
                    }

                    $url = api_get_course_url($courseId, $session_id_from_get);
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
                        $url = api_get_self().
                            '?course='.$course_code.'&session_id='.$session_id_from_get.$extra_params.'#course_session_data';
                        $details = Display::url(
                            Display::return_icon(
                                '2rightarrow.png',
                                get_lang('Details')
                            ),
                            $url
                        );
                    }

                    $data = [
                        'course_title' => $course_url,
                        'published_exercises' => $stats_array[$course_code]['exercises'], // exercise available
                        'new_exercises' => $stats_array[$course_code]['unanswered_exercises_by_user'],
                        'my_average' => ExerciseLib::convert_to_percentage($stats_array[$course_code]['my_average']),
                        'average_exercise_result' => 0 == $stats_array[$course_code]['average'] ? '-' : '('.ExerciseLib::convert_to_percentage($stats_array[$course_code]['average']).')',
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

        $pluginCalendar = 'true' === api_get_plugin_setting('learning_calendar', 'enabled');
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
     * @param int  $userId
     * @param int  $courseId
     * @param int  $sessionId
     * @param bool $showDiagram
     *
     * @return string html code
     */
    public static function show_course_detail($userId, $courseId, $sessionId = 0, $showDiagram = false)
    {
        $html = '';
        $courseId = (int) $courseId;

        if (empty($courseId)) {
            return '';
        }
        $userId = (int) $userId;
        $sessionId = (int) $sessionId;
        $course = api_get_course_entity($courseId);
        if (null === $course) {
            return '';
        }
        $courseCode = $course->getCode();

        $html .= '<a name="course_session_data"></a>';
        $html .= Display::page_subheader($course->getTitle());

        if ($showDiagram && !empty($sessionId)) {
            $visibility = api_get_session_visibility($sessionId);
            if (SESSION_AVAILABLE === $visibility) {
                $html .= Display::page_subheader2($course->getTitle());
            }
        }

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
            <th>'.get_lang('Statistics').' '
                .Display::return_icon(
                    'info3.gif',
                    get_lang('In case of multiple attempts, only shows the best result of each learner'),
                    ['align' => 'absmiddle', 'hspace' => '3px']
                ).
            '</th>
            </tr>
            </thead>
            <tbody>';
        $session = null;
        if (empty($sessionId)) {
            $user_list = CourseManager::get_user_list_from_course_code(
                $courseCode,
                $sessionId,
                null,
                null,
                STUDENT
            );
        } else {
            $session = api_get_session_entity($sessionId);
            $user_list = CourseManager::get_user_list_from_course_code(
                $courseCode,
                $sessionId,
                null,
                null,
                0
            );
        }

        // Show exercise results of invisible exercises? see BT#4091
        /*$exercise_list = ExerciseLib::get_all_exercises(
            $course_info,
            $session_id,
            false,
            null,
            false,
            2
        );*/
        $qb = Container::getQuizRepository()->findAllByCourse($course, $session, null, 2, false);
        /** @var CQuiz[] $exercises */
        $exercises = $qb->getQuery()->getResult();

        $to_graph_exercise_result = [];
        if (!empty($exercises)) {
            $weighting = $exe_id = 0;
            foreach ($exercises as $exercise) {
                $exercise_obj = new Exercise($courseId);
                $exercise_obj->read($exercise->getIid());
                $visible_return = $exercise_obj->is_visible();
                $score = $weighting = $attempts = 0;

                // Getting count of attempts by user
                $attempts = Event::count_exercise_attempts_by_user(
                    api_get_user_id(),
                    $exercise->getIid(),
                    $courseId,
                    $sessionId
                );

                $html .= '<tr class="row_even">';
                $url = api_get_path(WEB_CODE_PATH).
                    "exercise/overview.php?cid={$courseId}&sid=$sessionId&exerciseId={$exercices['id']}";

                if (true == $visible_return['value']) {
                    $exercices['title'] = Display::url(
                        $exercices['title'],
                        $url,
                        ['target' => SESSION_LINK_TARGET]
                    );
                } elseif (-1 == $exercices['active']) {
                    $exercices['title'] = sprintf(get_lang('%s (deleted)'), $exercices['title']);
                }

                $html .= Display::tag('td', $exercices['title']);

                // Exercise configuration show results or show only score
                if (0 == $exercices['results_disabled'] || 2 == $exercices['results_disabled']) {
                    //For graphics
                    $best_exercise_stats = Event::get_best_exercise_results_by_user(
                        $exercices['id'],
                        $courseId,
                        $sessionId
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
                        $courseId,
                        $sessionId
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
                            $courseId,
                            $sessionId
                        );
                        if (!empty($exercise_stat)) {
                            // Always getting the BEST attempt
                            $score = $exercise_stat['score'];
                            $weighting = $exercise_stat['max_score'];
                            $exe_id = $exercise_stat['exe_id'];

                            $latest_attempt_url .= api_get_path(WEB_CODE_PATH).
                                'exercise/result.php?id='.$exe_id.'&cid='.$courseId.'&show_headers=1&sid='.$sessionId;
                            $percentage_score_result = Display::url(
                                ExerciseLib::show_score($score, $weighting),
                                $latest_attempt_url
                            );
                            $my_score = 0;
                            if (!empty($weighting) && 0 != intval($weighting)) {
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
                                $courseCode,
                                $sessionId,
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
                    false == $trackingColumns['my_progress_lp'][$key]
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
            ['real_id' => $courseId],
            $sessionId,
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
                    $userId,
                    $course,
                    [$lp_id],
                    $session
                );
                $last_connection_in_lp = self::get_last_connection_time_in_lp(
                    $userId,
                    $course,
                    $lp_id,
                    $sessionId
                );

                $time_spent_in_lp = self::get_time_spent_in_lp(
                    $userId,
                    $course,
                    [$lp_id],
                    $sessionId
                );
                $percentage_score = self::get_avg_student_score(
                    $userId,
                    $course,
                    [$lp_id],
                    $session
                );

                $bestScore = self::get_avg_student_score(
                    $userId,
                    $course,
                    [$lp_id],
                    $session,
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

                $url = api_get_path(WEB_CODE_PATH).
                    "lp/lp_controller.php?cid={$courseId}&sid=$sessionId&lp_id=$lp_id&action=view";
                $html .= '<tr class="row_even">';

                if (in_array('lp', $columnHeadersKeys)) {
                    if (0 == $learnpath['lp_visibility']) {
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

        $html .= self::displayUserSkills($userId, $courseId, $sessionId);

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
        //$html = api_get_js('chartjs/Chart.js');
        $canvas = Display::tag('canvas', '', ['id' => 'session_graph_chart']);
        $html = Display::tag('div', $canvas, ['style' => 'width:100%']);
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
                if (0 != $attempt['max_score']) {
                    $my_exercise_result_array[] = $attempt['score'] / $attempt['max_score'];
                }
            } else {
                if (0 != $attempt['max_score']) {
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
            if (1 == $i) {
                $sum = 0;
            }
            $min = ($i - 1) * $part + $sum;
            $max = ($i) * $part;
            $x_axis[] = $min." - ".$max;
            $count = 0;
            foreach ($exercise_result as $result) {
                $percentage = $result * 100;
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
                if (0 != $attempt['max_score']) {
                    $my_exercise_result_array[] = $attempt['score'] / $attempt['max_score'];
                }
            } else {
                if (0 != $attempt['max_score']) {
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
            if (1 == $i) {
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
        $form->addElement('text', 'keyword', get_lang('Keyword'));
        $form->addSelect(
            'active',
            get_lang('Status'),
            [1 => get_lang('active'), 0 => get_lang('inactive')]
        );

        $form->addSelect(
            'sleeping_days',
            get_lang('Inactive days'),
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
                $where .= ' AND q.iid = %d ';
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
                CONCAT ('c', q.c_id, '_e', q.iid) as exercise_id,
                q.title as quiz_title,
                qq.description as description
                FROM $ttrack_exercises te
                INNER JOIN $ttrack_attempt ta
                ON ta.exe_id = te.exe_id
                INNER JOIN $tquiz q
                ON q.iid = te.exe_exo_id
                INNER JOIN $tquiz_rel_question rq
                ON rq.quiz_id = q.iid AND rq.c_id = q.c_id
                INNER JOIN $tquiz_question qq
                ON
                    qq.iid = rq.question_id AND
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
            $sqlQuestions = "SELECT tq.c_id, tq.iid as question_id, tq.question, tqa.iid,
                            tqa.answer, tqa.correct, tq.position, tqa.iid as answer_id
                            FROM $tquiz_question tq, $tquiz_answer tqa
                            WHERE
                                tqa.question_id = tq.iid AND
                                tqa.c_id = tq.c_id AND
                                tq.c_id = $courseIdx AND
                                tq.iid IN (".implode(',', $questionIds).")";

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
            $sqlUsers = "SELECT id as user_id, username, lastname, firstname
                         FROM $tuser
                         WHERE id IN (".implode(',', $userIds).")";
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
                $data[$id]['correct'] = (0 == $answer[$rowQuestId][$rowAnsId]['correct'] ? get_lang('No') : get_lang('Yes'));
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
     * @param string              $tool
     * @param SessionEntity |null $session
     *
     * @return CStudentPublication|null
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
        if (false === SkillModel::isAllowed($userId, false) && false == $forceView) {
            return '';
        }
        $skillManager = new SkillModel();

        return $skillManager->getUserSkillsTable($userId, $courseId, $sessionId)['table'];
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
                        if (empty($min)) {
                            $min = $item['date_reg'];
                        }

                        if (empty($max)) {
                            $max = $item['date_reg'];
                        }
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
                            if ('learnpath_id' === $beforeItem['action']) {
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
        if (false !== $res_ip && Database::num_rows($res_ip) > 0) {
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
        return
            api_is_platform_admin(true, true) ||
            SessionManager::user_is_general_coach(api_get_user_id(), $sessionId) ||
            api_is_allowed_to_create_course() ||
            api_is_course_tutor() ||
            api_is_course_admin();
    }

    public static function getCourseLpProgress($userId, $sessionId)
    {
        $controller = new IndexManager(get_lang('MyCourses'));
        $data = $controller->returnCoursesAndSessions($userId);
        $courseList = $data['courses'];
        $result = [];
        if ($courseList) {
            //$counter = 1;
            foreach ($courseList as $course) {
                $courseId = $course['course_id'];
                $courseInfo = api_get_course_info_by_id($courseId);
                if (empty($courseInfo)) {
                    continue;
                }
                $courseCode = $courseInfo['code'];
                $lpTimeList = self::getCalculateTime($userId, $courseId, $sessionId);

                // total progress
                $list = new LearnpathList(
                    $userId,
                     $courseInfo,
                    0,
                    'lp.publicatedOn ASC',
                    true,
                    null,
                    true
                );

                $list = $list->get_flat_list();
                $totalProgress = 0;
                $totalTime = 0;
                if (!empty($list)) {
                    foreach ($list as $lp_id => $learnpath) {
                        if (!$learnpath['lp_visibility']) {
                            continue;
                        }
                        $lpProgress = self::get_avg_student_progress($userId, $courseCode, [$lp_id], $sessionId);
                        $time = isset($lpTimeList[TOOL_LEARNPATH][$lp_id]) ? $lpTimeList[TOOL_LEARNPATH][$lp_id] : 0;
                        if (100 == $lpProgress) {
                            if (!empty($time)) {
                                $timeInMinutes = $time / 60;
                                $min = (int) learnpath::getAccumulateWorkTimePrerequisite($lp_id, $courseId);
                                if ($timeInMinutes >= $min) {
                                    $totalProgress++;
                                }
                            }
                        }
                        $totalTime += $time;
                    }

                    if (!empty($totalProgress)) {
                        $totalProgress = (float) api_number_format($totalProgress / count($list) * 100, 2);
                    }
                }

                $progress = self::get_avg_student_progress($userId, $courseCode, [], $sessionId);

                $result[] = [
                    'module' => $courseInfo['name'],
                    'progress' => $progress,
                    'qualification' => $totalProgress,
                    'activeTime' => $totalTime,
                ];
            }
        }

        return $result;
    }

    /**
     * @param int $userId
     * @param int $courseId
     * @param int $sessionId
     *
     * @return int
     */
    public static function getNumberOfCourseAccessDates($userId, $courseId, $sessionId)
    {
        $tblTrackCourseAccess = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $sessionCondition = api_get_session_condition($sessionId);
        $courseId = (int) $courseId;
        $userId = (int) $userId;

        $sql = "SELECT COUNT(DISTINCT (DATE(login_course_date))) AS c
            FROM $tblTrackCourseAccess
            WHERE c_id = $courseId $sessionCondition AND user_id = $userId";

        $result = Database::fetch_assoc(Database::query($sql));

        return (int) $result['c'];
    }

    public static function processUserDataMove(
        $user_id,
        $course_info,
        $origin_session_id,
        $new_session_id,
        $update_database,
        $debug = false
    ) {
        // Begin with the import process
        $origin_course_code = $course_info['code'];
        $course_id = $course_info['real_id'];
        $user_id = (int) $user_id;
        $origin_session_id = (int) $origin_session_id;
        $new_session_id = (int) $new_session_id;
        $session = api_get_session_entity($new_session_id);
        $em = Database::getManager();

        $TABLETRACK_EXERCICES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $TBL_TRACK_ATTEMPT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $attemptRecording = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
        $TBL_TRACK_E_COURSE_ACCESS = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $TBL_TRACK_E_LAST_ACCESS = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
        $TBL_LP_VIEW = Database::get_course_table(TABLE_LP_VIEW);
        $TBL_NOTEBOOK = Database::get_course_table(TABLE_NOTEBOOK);
        $TBL_STUDENT_PUBLICATION = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
        $TBL_STUDENT_PUBLICATION_ASSIGNMENT = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
        $TBL_ITEM_PROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $TBL_DROPBOX_FILE = Database::get_course_table(TABLE_DROPBOX_FILE);
        $TBL_DROPBOX_POST = Database::get_course_table(TABLE_DROPBOX_POST);
        $TBL_AGENDA = Database::get_course_table(TABLE_AGENDA);

        //1. track_e_exercises
        //ORIGINAL COURSE
        $sql = "SELECT * FROM $TABLETRACK_EXERCICES
                WHERE c_id = $course_id AND  session_id = $origin_session_id AND exe_user_id = $user_id ";
        $res = Database::query($sql);
        $list = [];
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $list[$row['exe_id']] = $row;
        }

        $result_message = [];
        $result_message_compare = [];
        if (!empty($list)) {
            foreach ($list as $exe_id => $data) {
                if ($update_database) {
                    $sql = "UPDATE $TABLETRACK_EXERCICES SET session_id = '$new_session_id' WHERE exe_id = $exe_id";
                    Database::query($sql);

                    $sql = "UPDATE $TBL_TRACK_ATTEMPT SET session_id = '$new_session_id' WHERE exe_id = $exe_id";
                    Database::query($sql);

                    $sql = "UPDATE $attemptRecording SET session_id = '$new_session_id' WHERE exe_id = $exe_id";
                    Database::query($sql);

                    if (!isset($result_message[$TABLETRACK_EXERCICES])) {
                        $result_message[$TABLETRACK_EXERCICES] = 0;
                    }
                    $result_message[$TABLETRACK_EXERCICES]++;
                } else {
                    if (!empty($data['orig_lp_id']) && !empty($data['orig_lp_item_id'])) {
                        $result_message['TRACK_E_EXERCISES'][$exe_id] = $data;
                    } else {
                        $result_message['TRACK_E_EXERCISES_IN_LP'][$exe_id] = $data;
                    }
                }
            }
        }

        // DESTINY COURSE
        if (!$update_database) {
            $sql = "SELECT * FROM $TABLETRACK_EXERCICES
                    WHERE
                        c_id = $course_id AND
                        session_id = $new_session_id AND
                        exe_user_id = $user_id ";
            $res = Database::query($sql);
            $list = [];
            while ($row = Database::fetch_array($res, 'ASSOC')) {
                $list[$row['exe_id']] = $row;
            }

            if (!empty($list)) {
                foreach ($list as $exe_id => $data) {
                    if ($update_database) {
                        $sql = "UPDATE $TABLETRACK_EXERCICES
                                SET session_id = '$new_session_id'
                                WHERE exe_id = $exe_id";
                        Database::query($sql);
                        $result_message[$TABLETRACK_EXERCICES]++;
                    } else {
                        if (!empty($data['orig_lp_id']) && !empty($data['orig_lp_item_id'])) {
                            $result_message_compare['TRACK_E_EXERCISES'][$exe_id] = $data;
                        } else {
                            $result_message_compare['TRACK_E_EXERCISES_IN_LP'][$exe_id] = $data;
                        }
                    }
                }
            }
        }

        // 2.track_e_attempt, track_e_attempt_recording, track_e_downloads
        // Nothing to do because there are not relationship with a session
        // 3. track_e_course_access
        $sql = "SELECT * FROM $TBL_TRACK_E_COURSE_ACCESS
                WHERE c_id = $course_id AND session_id = $origin_session_id  AND user_id = $user_id ";
        $res = Database::query($sql);
        $list = [];
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $list[$row['course_access_id']] = $row;
        }

        if (!empty($list)) {
            foreach ($list as $id => $data) {
                if ($update_database) {
                    $sql = "UPDATE $TBL_TRACK_E_COURSE_ACCESS
                            SET session_id = $new_session_id
                            WHERE course_access_id = $id";
                    if ($debug) {
                        echo $sql;
                    }
                    Database::query($sql);
                    if (!isset($result_message[$TBL_TRACK_E_COURSE_ACCESS])) {
                        $result_message[$TBL_TRACK_E_COURSE_ACCESS] = 0;
                    }
                    $result_message[$TBL_TRACK_E_COURSE_ACCESS]++;
                }
            }
        }

        // 4. track_e_lastaccess
        $sql = "SELECT access_id FROM $TBL_TRACK_E_LAST_ACCESS
                WHERE
                    c_id = $course_id AND
                    access_session_id = $origin_session_id AND
                    access_user_id = $user_id ";
        $res = Database::query($sql);
        $list = [];
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $list[] = $row['access_id'];
        }

        if (!empty($list)) {
            foreach ($list as $id) {
                if ($update_database) {
                    $sql = "UPDATE $TBL_TRACK_E_LAST_ACCESS
                            SET access_session_id = $new_session_id
                            WHERE access_id = $id";
                    if ($debug) {
                        echo $sql;
                    }
                    Database::query($sql);
                    if (!isset($result_message[$TBL_TRACK_E_LAST_ACCESS])) {
                        $result_message[$TBL_TRACK_E_LAST_ACCESS] = 0;
                    }
                    $result_message[$TBL_TRACK_E_LAST_ACCESS]++;
                }
            }
        }

        // 5. lp_item_view
        // CHECK ORIGIN
        $sql = "SELECT * FROM $TBL_LP_VIEW
                WHERE user_id = $user_id AND session_id = $origin_session_id AND c_id = $course_id ";
        $res = Database::query($sql);

        // Getting the list of LPs in the new session
        $lp_list = new LearnpathList($user_id, $course_info, $new_session_id);
        $flat_list = $lp_list->get_flat_list();
        $list = [];
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            // Checking if the LP exist in the new session
            //if (in_array($row['lp_id'], array_keys($flat_list))) {
            $list[$row['id']] = $row;
            //}
        }

        if (!empty($list)) {
            foreach ($list as $id => $data) {
                if ($update_database) {
                    $sql = "UPDATE $TBL_LP_VIEW
                            SET session_id = $new_session_id
                            WHERE c_id = $course_id AND iid = $id ";
                    if ($debug) {
                        var_dump($sql);
                    }
                    $res = Database::query($sql);
                    if ($debug) {
                        var_dump($res);
                    }
                    if (!isset($result_message[$TBL_LP_VIEW])) {
                        $result_message[$TBL_LP_VIEW] = 0;
                    }
                    $result_message[$TBL_LP_VIEW]++;
                } else {
                    // Getting all information of that lp_item_id
                    $score = self::get_avg_student_score(
                        $user_id,
                        $origin_course_code,
                        [$data['lp_id']],
                        $origin_session_id
                    );
                    $progress = self::get_avg_student_progress(
                        $user_id,
                        $origin_course_code,
                        [$data['lp_id']],
                        $origin_session_id
                    );
                    $result_message['LP_VIEW'][$data['lp_id']] = [
                        'score' => $score,
                        'progress' => $progress,
                    ];
                }
            }
        }

        // Check destination.
        if (!$update_database) {
            $sql = "SELECT * FROM $TBL_LP_VIEW
                    WHERE user_id = $user_id AND session_id = $new_session_id AND c_id = $course_id";
            $res = Database::query($sql);

            // Getting the list of LPs in the new session
            $lp_list = new LearnpathList($user_id, $course_info, $new_session_id);
            $flat_list = $lp_list->get_flat_list();

            $list = [];
            while ($row = Database::fetch_array($res, 'ASSOC')) {
                //Checking if the LP exist in the new session
                //if (in_array($row['lp_id'], array_keys($flat_list))) {
                $list[$row['id']] = $row;
                //}
            }

            if (!empty($list)) {
                foreach ($list as $id => $data) {
                    // Getting all information of that lp_item_id
                    $score = self::get_avg_student_score(
                        $user_id,
                        $origin_course_code,
                        [$data['lp_id']],
                        $new_session_id
                    );
                    $progress = self::get_avg_student_progress(
                        $user_id,
                        $origin_course_code,
                        [$data['lp_id']],
                        $new_session_id
                    );
                    $result_message_compare['LP_VIEW'][$data['lp_id']] = [
                        'score' => $score,
                        'progress' => $progress,
                    ];
                }
            }
        }

        // 6. Agenda
        // calendar_event_attachment no problems no session_id
        $sql = "SELECT ref FROM $TBL_ITEM_PROPERTY
                WHERE tool = 'calendar_event' AND insert_user_id = $user_id AND c_id = $course_id ";
        $res = Database::query($sql);
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $id = $row['ref'];
            if ($update_database) {
                $sql = "UPDATE $TBL_AGENDA SET session_id = $new_session_id WHERE c_id = $course_id AND iid = $id ";
                if ($debug) {
                    var_dump($sql);
                }
                $res_update = Database::query($sql);
                if ($debug) {
                    var_dump($res_update);
                }
                if (!isset($result_message['agenda'])) {
                    $result_message['agenda'] = 0;
                }
                $result_message['agenda']++;
            }
        }

        // 7. Forum ?? So much problems when trying to import data
        // 8. Student publication - Works
        $sql = "SELECT ref FROM $TBL_ITEM_PROPERTY
                WHERE tool = 'work' AND insert_user_id = $user_id AND c_id = $course_id";
        if ($debug) {
            echo $sql;
        }
        $res = Database::query($sql);
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $id = $row['ref'];
            $sql = "SELECT * FROM $TBL_STUDENT_PUBLICATION
                    WHERE iid = $id AND session_id = $origin_session_id AND c_id = $course_id";
            $sub_res = Database::query($sql);
            if (Database::num_rows($sub_res) > 0) {
                $data = Database::fetch_array($sub_res, 'ASSOC');
                if ($debug) {
                    var_dump($data);
                }
                $parent_id = $data['parent_id'];
                if (isset($data['parent_id']) && !empty($data['parent_id'])) {
                    $sql = "SELECT * FROM $TBL_STUDENT_PUBLICATION
                            WHERE iid = $parent_id AND c_id = $course_id";
                    $select_res = Database::query($sql);
                    $parent_data = Database::fetch_array($select_res, 'ASSOC');
                    if ($debug) {
                        var_dump($parent_data);
                    }

                    $sys_course_path = api_get_path(SYS_COURSE_PATH);
                    $course_dir = $sys_course_path.$course_info['path'];
                    $base_work_dir = $course_dir.'/work';

                    // Creating the parent folder in the session if does not exists already
                    //@todo ugly fix
                    $search_this = "folder_moved_from_session_id_$origin_session_id";
                    $search_this2 = $parent_data['url'];
                    $sql = "SELECT * FROM $TBL_STUDENT_PUBLICATION
                            WHERE description like '%$search_this%' AND
                                  url LIKE '%$search_this2%' AND
                                  session_id = $new_session_id AND
                                  c_id = $course_id
                            ORDER BY id desc  LIMIT 1";
                    if ($debug) {
                        echo $sql;
                    }
                    $sub_res = Database::query($sql);
                    $num_rows = Database::num_rows($sub_res);

                    $new_parent_id = 0;
                    if ($num_rows > 0) {
                        $new_result = Database::fetch_array($sub_res, 'ASSOC');
                        $created_dir = $new_result['url'];
                        $new_parent_id = $new_result['id'];
                    } else {
                        if ($update_database) {
                            $dir_name = substr($parent_data['url'], 1);
                            $created_dir = create_unexisting_work_directory($base_work_dir, $dir_name);
                            $created_dir = '/'.$created_dir;
                            $now = new DateTime(api_get_utc_datetime(), new DateTimeZone('UTC'));
                            // Creating directory
                            $publication = (new CStudentPublication())
                                ->setTitle($parent_data['title'])
                                ->setDescription(
                                    $parent_data['description']."folder_moved_from_session_id_$origin_session_id"
                                )
                                ->setActive(false)
                                ->setAccepted(true)
                                ->setPostGroupId(0)
                                ->setHasProperties($parent_data['has_properties'])
                                ->setWeight($parent_data['weight'])
                                ->setContainsFile($parent_data['contains_file'])
                                ->setFiletype('folder')
                                ->setSentDate($now)
                                ->setQualification($parent_data['qualification'])
                                ->setParentId(0)
                                ->setQualificatorId(0)
                                ->setUserId($parent_data['user_id'])
                                ->setAllowTextAssignment($parent_data['allow_text_assignment'])
                                ->setSession($session);

                            $publication->setDocumentId($parent_data['document_id']);

                            Database::getManager()->persist($publication);
                            Database::getManager()->flush();
                            $id = $publication->getIid();
                            //Folder created
                            //api_item_property_update($course_info, 'work', $id, 'DirectoryCreated', api_get_user_id());
                            $new_parent_id = $id;
                            if (!isset($result_message[$TBL_STUDENT_PUBLICATION.' - new folder created called: '.$created_dir])) {
                                $result_message[$TBL_STUDENT_PUBLICATION.' - new folder created called: '.$created_dir] = 0;
                            }
                            $result_message[$TBL_STUDENT_PUBLICATION.' - new folder created called: '.$created_dir]++;
                        }
                    }

                    //Creating student_publication_assignment if exists
                    $sql = "SELECT * FROM $TBL_STUDENT_PUBLICATION_ASSIGNMENT
                            WHERE publication_id = $parent_id AND c_id = $course_id";
                    if ($debug) {
                        var_dump($sql);
                    }
                    $rest_select = Database::query($sql);
                    if (Database::num_rows($rest_select) > 0) {
                        if ($update_database && $new_parent_id) {
                            $assignment_data = Database::fetch_array($rest_select, 'ASSOC');
                            $sql_add_publication = "INSERT INTO ".$TBL_STUDENT_PUBLICATION_ASSIGNMENT." SET
                                    	c_id = '$course_id',
                                       expires_on          = '".$assignment_data['expires_on']."',
                                       ends_on              = '".$assignment_data['ends_on']."',
                                       add_to_calendar      = '".$assignment_data['add_to_calendar']."',
                                       enable_qualification = '".$assignment_data['enable_qualification']."',
                                       publication_id       = '".$new_parent_id."'";
                            if ($debug) {
                                echo $sql_add_publication;
                            }
                            Database::query($sql_add_publication);
                            $id = (int) Database::insert_id();
                            if ($id) {
                                $sql_update = "UPDATE $TBL_STUDENT_PUBLICATION
                                           SET  has_properties = '".$id."',
                                                view_properties = '1'
                                           WHERE iid = ".$new_parent_id;
                                if ($debug) {
                                    echo $sql_update;
                                }
                                Database::query($sql_update);
                                if (!isset($result_message[$TBL_STUDENT_PUBLICATION_ASSIGNMENT])) {
                                    $result_message[$TBL_STUDENT_PUBLICATION_ASSIGNMENT] = 0;
                                }
                                $result_message[$TBL_STUDENT_PUBLICATION_ASSIGNMENT]++;
                            }
                        }
                    }

                    $doc_url = $data['url'];
                    $new_url = str_replace($parent_data['url'], $created_dir, $doc_url);

                    if ($update_database) {
                        // Creating a new work
                        $data['sent_date'] = new DateTime($data['sent_date'], new DateTimeZone('UTC'));

                        $data['post_group_id'] = (int) $data['post_group_id'];
                        $publication = (new CStudentPublication())
                            ->setTitle($data['title'])
                            ->setDescription($data['description'].' file moved')
                            ->setActive($data['active'])
                            ->setAccepted($data['accepted'])
                            ->setPostGroupId($data['post_group_id'])
                            ->setSentDate($data['sent_date'])
                            ->setParentId($new_parent_id)
                            ->setWeight($data['weight'])
                            ->setHasProperties(0)
                            ->setWeight($data['weight'])
                            ->setContainsFile($data['contains_file'])
                            ->setSession($session)
                            ->setUserId($data['user_id'])
                            ->setFiletype('file')
                            ->setDocumentId(0)
                        ;

                        $em->persist($publication);
                        $em->flush();

                        $id = $publication->getIid();
                        /*api_item_property_update(
                            $course_info,
                            'work',
                            $id,
                            'DocumentAdded',
                            $user_id,
                            null,
                            null,
                            null,
                            null,
                            $new_session_id
                        );*/
                        if (!isset($result_message[$TBL_STUDENT_PUBLICATION])) {
                            $result_message[$TBL_STUDENT_PUBLICATION] = 0;
                        }
                        $result_message[$TBL_STUDENT_PUBLICATION]++;
                        $full_file_name = $course_dir.'/'.$doc_url;
                        $new_file = $course_dir.'/'.$new_url;

                        if (file_exists($full_file_name)) {
                            // deleting old assignment
                            $result = copy($full_file_name, $new_file);
                            if ($result) {
                                unlink($full_file_name);
                                if (isset($data['id'])) {
                                    $sql = "DELETE FROM $TBL_STUDENT_PUBLICATION WHERE id= ".$data['id'];
                                    if ($debug) {
                                        var_dump($sql);
                                    }
                                    Database::query($sql);
                                }
                                api_item_property_update(
                                    $course_info,
                                    'work',
                                    $data['id'],
                                    'DocumentDeleted',
                                    api_get_user_id()
                                );
                            }
                        }
                    }
                }
            }
        }

        //9. Survey   Pending
        //10. Dropbox - not neccesary to move categories (no presence of session_id)
        $sql = "SELECT id FROM $TBL_DROPBOX_FILE
                WHERE uploader_id = $user_id AND session_id = $origin_session_id AND c_id = $course_id";
        if ($debug) {
            var_dump($sql);
        }
        $res = Database::query($sql);
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $id = (int) $row['id'];
            if ($update_database) {
                $sql = "UPDATE $TBL_DROPBOX_FILE SET session_id = $new_session_id WHERE c_id = $course_id AND iid = $id";
                if ($debug) {
                    var_dump($sql);
                }
                Database::query($sql);
                if ($debug) {
                    var_dump($res);
                }

                $sql = "UPDATE $TBL_DROPBOX_POST SET session_id = $new_session_id WHERE file_id = $id";
                if ($debug) {
                    var_dump($sql);
                }
                Database::query($sql);
                if ($debug) {
                    var_dump($res);
                }
                if (!isset($result_message[$TBL_DROPBOX_FILE])) {
                    $result_message[$TBL_DROPBOX_FILE] = 0;
                }
                $result_message[$TBL_DROPBOX_FILE]++;
            }
        }

        // 11. Notebook
        /*$sql = "SELECT notebook_id FROM $TBL_NOTEBOOK
                WHERE
                    user_id = $user_id AND
                    session_id = $origin_session_id AND
                    course = '$origin_course_code' AND
                    c_id = $course_id";
        if ($debug) {
            var_dump($sql);
        }
        $res = Database::query($sql);
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $id = $row['notebook_id'];
            if ($update_database) {
                $sql = "UPDATE $TBL_NOTEBOOK
                        SET session_id = $new_session_id
                        WHERE c_id = $course_id AND notebook_id = $id";
                if ($debug) {
                    var_dump($sql);
                }
                $res = Database::query($sql);
                if ($debug) {
                    var_dump($res);
                }
            }
        }*/

        if ($update_database) {
            echo Display::return_message(get_lang('StatsMoved'));
            if (is_array($result_message)) {
                foreach ($result_message as $table => $times) {
                    echo 'Table '.$table.' - '.$times.' records updated <br />';
                }
            }
        } else {
            echo '<h4>'.get_lang('UserInformationOfThisCourse').'</h4>';
            echo '<br />';
            echo '<table class="table" width="100%">';
            echo '<tr>';
            echo '<td width="50%" valign="top">';

            if (0 == $origin_session_id) {
                echo '<h5>'.get_lang('OriginCourse').'</h5>';
            } else {
                echo '<h5>'.get_lang('OriginSession').' #'.$origin_session_id.'</h5>';
            }
            self::compareUserData($result_message);
            echo '</td>';
            echo '<td width="50%" valign="top">';
            if (0 == $new_session_id) {
                echo '<h5>'.get_lang('DestinyCourse').'</h5>';
            } else {
                echo '<h5>'.get_lang('DestinySession').' #'.$new_session_id.'</h5>';
            }
            self::compareUserData($result_message_compare);
            echo '</td>';
            echo '</tr>';
            echo '</table>';
        }
    }

    public static function compareUserData($result_message)
    {
        foreach ($result_message as $table => $data) {
            $title = $table;
            if ('TRACK_E_EXERCISES' === $table) {
                $title = get_lang('Exercises');
            } elseif ('TRACK_E_EXERCISES_IN_LP' === $table) {
                $title = get_lang('ExercisesInLp');
            } elseif ('LP_VIEW' === $table) {
                $title = get_lang('LearningPaths');
            }
            echo '<br / ><h3>'.get_lang($title).' </h3><hr />';

            if (is_array($data)) {
                foreach ($data as $id => $item) {
                    if ('TRACK_E_EXERCISES' === $table || 'TRACK_E_EXERCISES_IN_LP' === $table) {
                        echo "<br /><h3>".get_lang('Attempt')." #$id</h3>";
                        echo '<h3>';
                        echo get_lang('Exercise').' #'.$item['exe_exo_id'];
                        echo '</h3>';
                        if (!empty($item['orig_lp_id'])) {
                            echo '<h3>';
                            echo get_lang('LearningPath').' #'.$item['orig_lp_id'];
                            echo '</h3>';
                        }
                        // Process data.
                        $array = [
                            'exe_date' => get_lang('Date'),
                            'score' => get_lang('Score'),
                            'max_score' => get_lang('Weighting'),
                        ];
                        foreach ($item as $key => $value) {
                            if (in_array($key, array_keys($array))) {
                                $key = $array[$key];
                                echo "$key =  $value <br />";
                            }
                        }
                    } else {
                        echo "<br /><h3>".get_lang('Id')." #$id</h3>";
                        // process data
                        foreach ($item as $key => $value) {
                            echo "$key =  $value <br />";
                        }
                    }
                }
            } else {
                echo get_lang('NoResults');
            }
        }
    }

    private static function generateQuizzesTable(array $courseInfo, int $sessionId = 0): string
    {
        if (empty($sessionId)) {
            $userList = CourseManager::get_user_list_from_course_code(
                $courseInfo['code'],
                $sessionId,
                null,
                null,
                STUDENT
            );
        } else {
            $userList = CourseManager::get_user_list_from_course_code($courseInfo['code'], $sessionId, null, null, 0);
        }

        $exerciseList = ExerciseLib::get_all_exercises($courseInfo, $sessionId, false, null);

        if (empty($exerciseList)) {
            return Display::return_message(get_lang('NoEx'));
        }

        $toGraphExerciseResult = [];

        $quizzesTable = new SortableTableFromArray([], 0, 0, 'quizzes');
        $quizzesTable->setHeaders(
            [
                get_lang('Exercises'),
                get_lang('Attempts'),
                get_lang('BestAttempt'),
                get_lang('Ranking'),
                get_lang('BestResultInCourse'),
                get_lang('Statistics').Display::return_icon('info3.gif', get_lang('OnlyBestResultsPerStudent')),
            ]
        );

        $webCodePath = api_get_path(WEB_CODE_PATH);

        foreach ($exerciseList as $exercices) {
            $objExercise = new Exercise($courseInfo['real_id']);
            $objExercise->read($exercices['id']);
            $visibleReturn = $objExercise->is_visible();

            // Getting count of attempts by user
            $attempts = Event::count_exercise_attempts_by_user(
                api_get_user_id(),
                $exercices['id'],
                $courseInfo['real_id'],
                $sessionId
            );

            $url = $webCodePath.'exercise/overview.php?'
                .http_build_query(
                    ['cidReq' => $courseInfo['code'], 'id_session' => $sessionId, 'exerciseId' => $exercices['id']]
                );

            if (true == $visibleReturn['value']) {
                $exercices['title'] = Display::url(
                    $exercices['title'],
                    $url,
                    ['target' => SESSION_LINK_TARGET]
                );
            } elseif (-1 == $exercices['active']) {
                $exercices['title'] = sprintf(get_lang('XParenthesisDeleted'), $exercices['title']);
            }

            $quizData = [
                $exercices['title'],
                $attempts,
                '-',
                '-',
                '-',
                '-',
            ];

            // Exercise configuration show results or show only score
            if (!in_array($exercices['results_disabled'], [0, 2])
                || empty($attempts)
            ) {
                $quizzesTable->addRow($quizData);

                continue;
            }

            //For graphics
            $bestExerciseAttempts = Event::get_best_exercise_results_by_user(
                $exercices['id'],
                $courseInfo['real_id'],
                $sessionId
            );

            $toGraphExerciseResult[$exercices['id']] = [
                'title' => $exercices['title'],
                'data' => $bestExerciseAttempts,
            ];

            // Getting best results
            $bestScoreData = ExerciseLib::get_best_attempt_in_course(
                $exercices['id'],
                $courseInfo['real_id'],
                $sessionId
            );

            if (!empty($bestScoreData)) {
                $quizData[5] = ExerciseLib::show_score(
                    $bestScoreData['score'],
                    $bestScoreData['max_score']
                );
            }

            $exerciseAttempt = ExerciseLib::get_best_attempt_by_user(
                api_get_user_id(),
                $exercices['id'],
                $courseInfo['real_id'],
                $sessionId
            );

            if (!empty($exerciseAttempt)) {
                // Always getting the BEST attempt
                $score = $exerciseAttempt['score'];
                $weighting = $exerciseAttempt['max_score'];
                $exeId = $exerciseAttempt['exe_id'];

                $latestAttemptUrl = $webCodePath.'exercise/result.php?'
                    .http_build_query(
                        [
                            'id' => $exeId,
                            'cidReq' => $courseInfo['code'],
                            'show_headers' => 1,
                            'id_session' => $sessionId,
                        ]
                    );

                $quizData[3] = Display::url(
                    ExerciseLib::show_score($score, $weighting),
                    $latestAttemptUrl
                );

                $myScore = !empty($weighting) && 0 != intval($weighting) ? $score / $weighting : 0;

                //@todo this function slows the page
                if (is_int($userList)) {
                    $userList = [$userList];
                }

                $quizData[4] = ExerciseLib::get_exercise_result_ranking(
                    $myScore,
                    $exeId,
                    $exercices['id'],
                    $courseInfo['code'],
                    $sessionId,
                    $userList
                );
                $graph = self::generate_exercise_result_thumbnail_graph($toGraphExerciseResult[$exercices['id']]);
                $normalGraph = self::generate_exercise_result_graph($toGraphExerciseResult[$exercices['id']]);

                $quizData[6] = Display::url(
                    Display::img($graph, '', [], false),
                    $normalGraph,
                    ['id' => $exercices['id'], 'class' => 'expand-image']
                );
            }

            $quizzesTable->addRow($quizData);
        }

        return Display::div(
            $quizzesTable->toHtml(),
            ['class' => 'table-responsive']
        );
    }

    private static function generateLearningPathsTable(int $userId, array $courseInfo, int $sessionId = 0): string
    {
        $columnHeaders = [
            'lp' => get_lang('LearningPath'),
            'time' => get_lang('LatencyTimeSpent'),
            'progress' => get_lang('Progress'),
            'score' => get_lang('Score'),
            'best_score' => get_lang('BestScore'),
            'last_connection' => get_lang('LastConnexion'),
        ];

        $trackingColumns = api_get_configuration_value('tracking_columns');

        if (isset($trackingColumns['my_progress_lp'])) {
            $columnHeaders = array_filter(
                $columnHeaders,
                function ($columHeader, $key) use ($trackingColumns) {
                    if (!isset($trackingColumns['my_progress_lp'][$key])
                        || false == $trackingColumns['my_progress_lp'][$key]
                    ) {
                        return false;
                    }

                    return true;
                },
                ARRAY_FILTER_USE_BOTH
            );
        }

        if (true === api_get_configuration_value('student_follow_page_add_LP_subscription_info')) {
            $columnHeaders['student_follow_page_add_LP_subscription_info'] = get_lang('Unlock');
        }

        if (true === api_get_configuration_value('student_follow_page_add_LP_acquisition_info')) {
            $columnHeaders['student_follow_page_add_LP_acquisition_info'] = get_lang('Acquisition');
        }

        $addLpInvisibleCheckbox = api_get_configuration_value('student_follow_page_add_LP_invisible_checkbox');

        $columnHeadersKeys = array_keys($columnHeaders);

        $learningpathsTable = new SortableTableFromArray([], 0, 0, 'learningpaths');
        $learningpathsTable->setHeaders($columnHeaders);

        // LP table results
        $list = new LearnpathList(
            api_get_user_id(),
            $courseInfo,
            $sessionId,
            'lp.publicatedOn ASC',
            true,
            null,
            true
        );

        $lpList = $list->get_flat_list();

        if (empty($lpList)) {
            return Display::return_message(get_lang('NoLearnpath'));
        }

        $webCodePath = api_get_path(WEB_CODE_PATH);

        foreach ($lpList as $lpId => $learnpath) {
            $learningpathData = [];

            if (!$learnpath['lp_visibility']) {
                continue;
            }

            if ($addLpInvisibleCheckbox) {
                if (!StudentFollowPage::isViewVisible($lpId, $userId, $courseInfo['real_id'], $sessionId)) {
                    continue;
                }
            }

            $url = $webCodePath.'lp/lp_controller.php?'
                .http_build_query(
                    ['cidReq' => $courseInfo['code'], 'id_session' => $sessionId, 'lp_id' => $lpId, 'action' => 'view']
                );

            if (in_array('lp', $columnHeadersKeys)) {
                if (0 == $learnpath['lp_visibility']) {
                    $learningpathData[] = $learnpath['lp_name'];
                } else {
                    $learningpathData[] = Display::url(
                        $learnpath['lp_name'],
                        $url,
                        ['target' => SESSION_LINK_TARGET]
                    );
                }
            }

            if (in_array('time', $columnHeadersKeys)) {
                $time_spent_in_lp = self::get_time_spent_in_lp(
                    $userId,
                    $courseInfo['code'],
                    [$lpId],
                    $sessionId
                );

                $learningpathData[] = api_time_to_hms($time_spent_in_lp);
            }

            if (in_array('progress', $columnHeadersKeys)) {
                $progress = self::get_avg_student_progress(
                    $userId,
                    $courseInfo['code'],
                    [$lpId],
                    $sessionId
                );

                if (is_numeric($progress)) {
                    $progress = sprintf(get_lang('XPercent'), $progress);
                }

                $learningpathData[] = $progress;
            }

            if (in_array('score', $columnHeadersKeys)) {
                $percentage_score = self::get_avg_student_score(
                    $userId,
                    $courseInfo['code'],
                    [$lpId],
                    $sessionId
                );

                if (is_numeric($percentage_score)) {
                    $percentage_score = sprintf(get_lang('XPercent'), $percentage_score);
                } else {
                    $percentage_score = sprintf(get_lang('XPercent'), 0);
                }

                $learningpathData[] = $percentage_score;
            }

            if (in_array('best_score', $columnHeadersKeys)) {
                $bestScore = self::get_avg_student_score(
                    $userId,
                    $courseInfo['code'],
                    [$lpId],
                    $sessionId,
                    false,
                    false,
                    true
                );

                if (is_numeric($bestScore)) {
                    $bestScore = sprintf(get_lang('XPercent'), $bestScore);
                } else {
                    $bestScore = '-';
                }

                $learningpathData[] = $bestScore;
            }

            if (in_array('last_connection', $columnHeadersKeys)) {
                $lastConnectionInLp = self::get_last_connection_time_in_lp(
                    $userId,
                    $courseInfo['code'],
                    $lpId,
                    $sessionId
                );

                $lastConnection = '-';

                if (!empty($lastConnectionInLp)) {
                    $lastConnection = api_convert_and_format_date($lastConnectionInLp, DATE_TIME_FORMAT_LONG);
                }

                $learningpathData[] = $lastConnection;
            }

            if (in_array('student_follow_page_add_LP_subscription_info', $columnHeadersKeys)) {
                $learningpathData[] = StudentFollowPage::getLpSubscription(
                    $learnpath,
                    $userId,
                    $courseInfo['real_id'],
                    $sessionId
                );
            }

            if (in_array('student_follow_page_add_LP_acquisition_info', $columnHeadersKeys)) {
                $learningpathData[] = StudentFollowPage::getLpAcquisition(
                    $learnpath,
                    $userId,
                    $courseInfo['real_id'],
                    $sessionId
                );
            }

            $learningpathsTable->addRow($learningpathData);
        }

        return Display::div(
            $learningpathsTable->toHtml(),
            ['class' => 'table-responsive']
        );
    }
}

/**
 * @todo move into a proper file
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
                    track_resource.insert_user_id = user.id user_id AND
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
        $tblSessionRelUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $column = (int) $column;
        $direction = !in_array(strtolower(trim($direction)), ['asc', 'desc']) ? 'asc' : $direction;

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

        if (0 == $column) {
            $column = '0';
        }
        if ('' != $column && '' != $direction) {
            if (2 != $column && 4 != $column) {
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
                        WHERE c_id = $course_id AND iid = $ref";
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
                $sql = "SELECT session.id s.id, s.name u.username
                          FROM c_tool t, session s, user u, $tblSessionRelUser sru
                          WHERE
                              t.c_id = $course_id AND
                              t.session_id = s.id AND
                              sru.session_id = s.id AND
                              sru.user_id = u.id AND
                              t.$id = $ref";
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
                if (2 != $row['col6']) {
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
                                WHERE c_id = $course_id AND iid = $ref";
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
            if (1 == $field[6] && 1 == $field[8]) {
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
            if ('additional_profile_field' != $key) {
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
            if (UserManager::USER_FIELD_TYPE_TAG == $result_extra_field['field_type']) {
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
                        if (ExtraField::FIELD_TYPE_DOUBLE_SELECT ==
                            $result_extra_field['field_type']
                        ) {
                            $id_double_select = explode(';', $row['value']);
                            if (is_array($id_double_select)) {
                                $value1 = $result_extra_field['options'][$id_double_select[0]]['option_value'];
                                $value2 = $result_extra_field['options'][$id_double_select[1]]['option_value'];
                                $row['value'] = ($value1.';'.$value2);
                            }
                        }

                        if (ExtraField::FIELD_TYPE_SELECT_WITH_TEXT_FIELD == $result_extra_field['field_type']) {
                            $parsedValue = explode('::', $row['value']);

                            if ($parsedValue) {
                                $value1 = $result_extra_field['options'][$parsedValue[0]]['display_text'];
                                $value2 = $parsedValue[1];

                                $row['value'] = "$value1: $value2";
                            }
                        }

                        if (ExtraField::FIELD_TYPE_TRIPLE_SELECT == $result_extra_field['field_type']) {
                            [$level1, $level2, $level3] = explode(';', $row['value']);

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
    public static function get_number_of_users($conditions)
    {
        $conditions['get_count'] = true;

        return self::get_user_data(null, null, null, null, $conditions);
    }

    /**
     * Get data for users list in sortable with pagination.
     *
     * @param int $from
     * @param int $number_of_items
     * @param $column
     * @param $direction
     * @param $conditions
     *
     * @return array
     */
    public static function get_user_data(
        $from,
        $number_of_items,
        $column,
        $direction,
        $conditions = [],
        $options = []
    ) {
        global $user_ids, $export_csv, $session_id;
        $includeInvitedUsers = $conditions['include_invited_users']; // include the invited users
        $getCount = isset($conditions['get_count']) ? $conditions['get_count'] : false;

        $course = api_get_course_entity($conditions['course_id']);
        $courseId = $course->getId();
        $courseCode = $course->getCode();

        $csv_content = [];
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $access_url_id = api_get_current_access_url_id();

        // get all users data from a course for sortable with limit
        if (is_array($user_ids)) {
            $user_ids = array_map('intval', $user_ids);
            $condition_user = " WHERE user.id IN (".implode(',', $user_ids).") ";
        } else {
            $user_ids = (int) $user_ids;
            $condition_user = " WHERE user.id = $user_ids ";
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

        $url_table = '';
        $url_condition = '';
        if (api_is_multiple_url_enabled()) {
            $url_table = " INNER JOIN $tbl_url_rel_user as url_users ON (user.id = url_users.user_id)";
            $url_condition = " AND access_url_id = '$access_url_id'";
        }

        $invitedUsersCondition = '';
        if (!$includeInvitedUsers) {
            $invitedUsersCondition = " AND user.status != ".INVITEE;
        }

        $select = '
                SELECT user.id as user_id,
                    user.official_code  as col0,
                    user.lastname       as col1,
                    user.firstname      as col2,
                    user.username       as col3,
                    user.email          as col4';
        if ($getCount) {
            $select = ' SELECT COUNT(distinct(user.id)) as count ';
        }

        $sqlInjectJoins = '';
        $where = 'AND 1 = 1 ';
        $sqlInjectWhere = '';
        if (!empty($conditions)) {
            if (isset($conditions['inject_joins'])) {
                $sqlInjectJoins = $conditions['inject_joins'];
            }
            if (isset($conditions['where'])) {
                $where = $conditions['where'];
            }
            if (isset($conditions['inject_where'])) {
                $sqlInjectWhere = $conditions['inject_where'];
            }
            $injectExtraFields = !empty($conditions['inject_extra_fields']) ? $conditions['inject_extra_fields'] : 1;
            $injectExtraFields = rtrim($injectExtraFields, ', ');
            if (false === $getCount) {
                $select .= " , $injectExtraFields";
            }
        }

        $sql = "$select
                FROM $tbl_user as user
                $url_table
                $sqlInjectJoins
                $condition_user
                $url_condition
                $invitedUsersCondition
                $where
                $sqlInjectWhere
                ";

        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }

        $column = (int) $column;
        $from = (int) $from;
        $number_of_items = (int) $number_of_items;

        if ($getCount) {
            $res = Database::query($sql);
            $row = Database::fetch_array($res);

            return $row['count'];
        }

        $sql .= " ORDER BY col$column $direction ";
        $sql .= " LIMIT $from, $number_of_items";

        $res = Database::query($sql);
        $users = [];

        $total_surveys = 0;
        /*$total_exercises = ExerciseLib::get_all_exercises(
            $courseInfo,
            $session_id,
            false,
            null,
            false,
            3
        );*/
        $session = api_get_session_entity($session_id);
        $repo = Container::getQuizRepository();
        $qb = $repo->findAllByCourse($course, $session, null, 2);
        $exercises = $qb->getQuery()->getResult();

        if (empty($session_id)) {
            $survey_user_list = [];
            // @todo
            //$surveyList = SurveyManager::get_surveys($courseCode, $session_id);
            $surveyList = [];
            if ($surveyList) {
                $total_surveys = count($surveyList);
                foreach ($surveyList as $survey) {
                    $user_list = SurveyManager::get_people_who_filled_survey(
                        $survey['survey_id'],
                        false,
                        $courseId
                    );

                    foreach ($user_list as $user_id) {
                        isset($survey_user_list[$user_id]) ? $survey_user_list[$user_id]++ : $survey_user_list[$user_id] = 1;
                    }
                }
            }
        }

        $urlBase = api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?details=true&cid='.$courseId.
            '&origin=tracking_course&sid='.$session_id;

        $sortByFirstName = api_sort_by_first_name();
        Session::write('user_id_list', []);
        $userIdList = [];
        $addExerciseOption = api_get_configuration_value('add_exercise_best_attempt_in_report');
        $exerciseResultsToCheck = [];
        if (!empty($addExerciseOption) && isset($addExerciseOption['courses']) &&
            isset($addExerciseOption['courses'][$courseCode])
        ) {
            foreach ($addExerciseOption['courses'][$courseCode] as $exerciseId) {
                $exercise = new Exercise();
                $exercise->read($exerciseId);
                if ($exercise->iId) {
                    $exerciseResultsToCheck[] = $exercise;
                }
            }
        }
        while ($user = Database::fetch_array($res, 'ASSOC')) {
            $userId = $user['user_id'];
            $userIdList[] = $userId;
            $userEntity = api_get_user_entity($userId);
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
                $userId,
                $course,
                [],
                $session
            );

            $averageBestScore = Tracking::get_avg_student_score(
                $user['user_id'],
                $course,
                [],
                $session,
                false,
                false,
                true
            );

            $avg_student_progress = Tracking::get_avg_student_progress(
                $user['user_id'],
                $course,
                [],
                $session
            );

            if (empty($avg_student_progress)) {
                $avg_student_progress = 0;
            }
            $user['average_progress'] = $avg_student_progress.'%';

            $total_user_exercise = Tracking::get_exercise_student_progress(
                $exercises,
                $user['user_id'],
                $courseId,
                $session_id
            );

            $user['exercise_progress'] = $total_user_exercise;

            $total_user_exercise = Tracking::get_exercise_student_average_best_attempt(
                $exercises,
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

            if (is_numeric($averageBestScore)) {
                $user['student_score_best'] = $averageBestScore.'%';
            } else {
                $user['student_score_best'] = $averageBestScore;
            }

            $exerciseResults = [];
            if (!empty($exerciseResultsToCheck)) {
                foreach ($exerciseResultsToCheck as $exercise) {
                    $bestExerciseResult = Event::get_best_attempt_exercise_results_per_user(
                        $user['user_id'],
                        $exercise->iId,
                        $courseId,
                        $session_id,
                        false
                    );

                    $best = null;
                    if ($bestExerciseResult) {
                        $best = $bestExerciseResult['score'] / $bestExerciseResult['max_score'];
                        $best = round($best, 2) * 100;
                        $best .= '%';
                    }
                    $exerciseResults['exercise_'.$exercise->iId] = $best;
                }
            }
            $user['count_assignments'] = Container::getStudentPublicationRepository()->countUserPublications(
                $userEntity,
                $course,
                $session
            );
            $user['count_messages'] = Container::getForumPostRepository()->countUserForumPosts(
                $userEntity,
                $course,
                $session
            );
            $user['first_connection'] = Tracking::get_first_connection_date_on_the_course(
                $user['user_id'],
                $courseId,
                $session_id,
                false === $export_csv
            );

            $user['last_connection'] = Tracking::get_last_connection_date_on_the_course(
                $user['user_id'],
                ['real_id' => $course->getId()],
                $session_id,
                false === $export_csv
            );

            if ($export_csv) {
                if (!empty($user['first_connection'])) {
                    $user['first_connection'] = api_get_local_time($user['first_connection']);
                } else {
                    $user['first_connection'] = '-';
                }
                if (!empty($user['last_connection'])) {
                    $user['last_connection'] = api_get_local_time($user['last_connection']);
                } else {
                    $user['last_connection'] = '-';
                }
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
            $user_row['student_score_best'] = $user['student_score_best'];
            if (!empty($exerciseResults)) {
                foreach ($exerciseResults as $exerciseId => $bestResult) {
                    $user_row[$exerciseId] = $bestResult;
                }
            }
            $user_row['count_assignments'] = $user['count_assignments'];
            $user_row['count_messages'] = $user['count_messages'];

            $userGroupManager = new UserGroupModel();
            $user_row['classes'] = $userGroupManager->getLabelsFromNameList($user['user_id'], Usergroup::NORMAL_CLASS);

            if (empty($session_id)) {
                $user_row['survey'] = $user['survey'];
            } else {
                $userSession = SessionManager::getUserSession($user['user_id'], $session_id);
                $user_row['registered_at'] = '';
                if ($userSession) {
                    $user_row['registered_at'] = api_get_local_time($userSession['registered_at']);
                }
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

            if ('true' === api_get_setting('show_email_addresses')) {
                $user_row['email'] = $user['col4'];
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
        Session::write('user_id_list', $userIdList);

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
        $params = []
    ) {
        global $user_ids, $course_code, $export_csv, $csv_content, $session_id;
        $includeInvitedUsers = false;
        $courseId = $params['cid'];
        $sessionId = $params['sid'];

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

        $sql = "SELECT
                    user.user_id as user_id,
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
        $sql .= " LIMIT $from,$number_of_items";

        $res = Database::query($sql);
        $users = [];

        $sortByFirstName = api_sort_by_first_name();
        $course = api_get_course_entity($courseId);

        while ($user = Database::fetch_array($res, 'ASSOC')) {
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
                $course,
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
                false === $export_csv
            );

            $user['link'] = '
                <center>
                 <a
                    href="../mySpace/myStudents.php?student='.$user['user_id'].'&details=true&cid='.$courseId.'&origin=tracking_course&sid='.$session_id.'">
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
    public static function actionsLeft($current, $sessionId = 0, $addWrapper = true)
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

        $lpLink = Display::url(
            Display::return_icon('scorms.png', get_lang('CourseLPsGenericStats'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'tracking/lp_report.php?'.api_get_cidreq()
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
            case 'lp':
                $lpLink = Display::url(
                    Display::return_icon('scorms_na.png', get_lang('CourseLPsGenericStats'), [], ICON_SIZE_MEDIUM),
                    '#'
                );
                break;
        }

        $links =
            $usersLink.
            $groupsLink.
            $courseLink.
            $resourcesLink.
            $examLink.
            $eventsLink.
            $lpLink.
            $attendanceLink
        ;

        if ($addWrapper) {
            return Display::toolbarAction('tracking', [$links]);
        }

        return $links;
    }
}

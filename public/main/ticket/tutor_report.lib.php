<?php

/* For licensing terms, see /license.txt */

/**
 * Helper library for weekly reports.
 */

use Chamilo\CoreBundle\Component\Utils\ActionIcon;
use Chamilo\CoreBundle\Component\Utils\StateIcon;

/**
 * @param $course_code
 *
 * @return array|bool
 */
function initializeReport($course_code)
{
    $course_info = api_get_course_info($course_code);
    $table_reporte_semanas = Database::get_main_table('rp_reporte_semanas');
    $table_students_report = Database::get_main_table('rp_students_report');
    $table_semanas_curso = Database::get_main_table('rp_semanas_curso');
    $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
    $table_course_rel_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $table_post = Database::get_course_table(TABLE_FORUM_POST);
    $table_work = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $course_code = Database::escape_string($course_code);
    $res = Database::query("SELECT COUNT(*) as cant FROM $table_reporte_semanas WHERE course_code = '".$course_code."'");
    $sqlWeeks = "SELECT semanas FROM $table_semanas_curso WHERE course_code = '$course_code'";
    $resWeeks = Database::query($sqlWeeks);
    $weeks = Database::fetch_object($resWeeks);
    $obj = Database::fetch_object($res);
    $weeksCount = !isset($_POST['weeksNumber']) ? (0 == $weeks->semanas ? 7 : $weeks->semanas) : (int) $_POST['weeksNumber'];
    $weeksCount = Database::escape_string($weeksCount);
    Database::query("REPLACE INTO $table_semanas_curso (course_code , semanas) VALUES ('$course_code','$weeksCount')");
    if ((int) ($obj->cant) != $weeksCount) {
        if ((int) ($obj->cant) > $weeksCount) {
            $sql = "DELETE FROM $table_reporte_semanas
                    WHERE  week_id > $weeksCount AND course_code = '$course_code'";
            Database::query($sql);
        } else {
            for ($i = $obj->cant + 1; $i <= $weeksCount; $i++) {
                if (!Database::query("INSERT INTO $table_reporte_semanas (week_id, course_code, forum_id, work_id, quiz_id, pc_id)
						VALUES ($i, '$course_code', '0', '0', '0', '0' )")) {
                    return false;
                }
            }
        }
    }

    $sql = "REPLACE INTO $table_students_report (user_id, week_report_id, work_ok , thread_ok , quiz_ok , pc_ok)
			SELECT cu.user_id, rs.id, 0, 0, 0, 0
			FROM $table_course_rel_user cu
			INNER JOIN $courseTable c
			ON (c.id = cu.c_id)
			LEFT JOIN $table_reporte_semanas rs ON c.code = rs.course_code
			WHERE cu.status = 5 AND rs.course_code = '$course_code'
			ORDER BY cu.user_id, rs.id";
    if (!Database::query($sql)) {
        return false;
    } else {
        $page = !isset($_GET['page']) ? 1 : (int) $_GET['page'];

        Database::query("UPDATE $table_students_report sr SET sr.work_ok = 1
		WHERE CONCAT (sr.user_id,',',sr.week_report_id)
		IN (SELECT DISTINCT CONCAT(w.user_id,',',rs.id)
		FROM $table_work w  JOIN $table_reporte_semanas rs ON w.parent_id = rs.work_id)");
        Database::query("UPDATE $table_students_report sr SET sr.thread_ok = 1
		WHERE CONCAT (sr.user_id,',',sr.week_report_id)
		IN (SELECT DISTINCT CONCAT(f.poster_id,',',rs.id)
		FROM $table_post f  JOIN $table_reporte_semanas rs ON f.thread_id = rs.forum_id)");

        return showResults($course_info, $weeksCount, $page);
    }
}

/**
 * @param $courseInfo
 * @param $weeksCount
 * @param $page
 *
 * @return array
 */
function showResults($courseInfo, $weeksCount, $page)
{
    $course_code = $courseInfo['code'];
    $page = (int) $page;
    $weeksCount = (int) $weeksCount;

    $tableWeeklyReport = Database::get_main_table('rp_reporte_semanas');
    $tableStudentsReport = Database::get_main_table('rp_students_report');
    //$table_course_rel_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $tableUser = Database::get_main_table(TABLE_MAIN_USER);
    $tableThread = Database::get_course_table(TABLE_FORUM_THREAD);
    $tableWork = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

    $results = [];
    $tableExport = [];
    $sqlHeader = "SELECT rs.id as id,rs.week_id, w.title AS work_title,  t.title ,'EVALUATION' as eval_title ,'QUIZ' as pc_title
                    FROM $tableWeeklyReport rs
                    LEFT JOIN $tableThread t ON t.thread_id =  rs.forum_id
                    LEFT JOIN $tableWork w ON w.id = rs.work_id
                    WHERE rs.course_code = '$course_code'
                    ORDER BY rs.week_id";
    $resultHeader = Database::query($sqlHeader);
    $ids = [];
    $line = '<tr>
        <th ></th>';
    $lineHeaderExport = [null, null];
    $lineHeaderExport2 = [null, null];
    while ($rowe = Database::fetch_assoc($resultHeader)) {
        $lineHeaderExport[] = utf8_decode('Work'.$rowe['week_id']);
        $lineHeaderExport[] = utf8_decode('Forum'.$rowe['week_id']);
        //$fila_export_encabezado[] =  utf8_decode('Eval'.$rowe['week_id']);
        //$fila_export_encabezado[] =  utf8_decode('PC'.$rowe['week_id']);
        $lineHeaderExport2[] = utf8_decode($rowe['work_title']);
        $lineHeaderExport2[] = utf8_decode($rowe['title']);
        //$fila_export_encabezado2[] = utf8_decode($rowe['eval_title']);
        //$fila_export_encabezado2[] = utf8_decode($rowe['pc_title']);
        $fila_export = ['Work'.$rowe['week_id'], 'Forum'.$rowe['week_id'], 'Eval'.$rowe['week_id'], 'PC'.$rowe['week_id']];
        if ($rowe['week_id'] > (($page - 1) * 7) && $rowe['week_id'] <= 7 * $page) {
            $ids[$rowe['week_id']] = $rowe['id'];
            $line .= '<th>
                <a href="#" onClick="showContent('."'tarea".$rowe['week_id']."'".');">Work'.$rowe['week_id'].'
                        <div class="blackboard_hide" id="tarea'.$rowe['week_id'].'">'.$rowe['work_title'].'</div>
                </a></th>';
            $line .= '<th>
                <a href="#" onClick="showContent('."'foro".$rowe['week_id']."'".');">Forum'.$rowe['week_id'].'
                        <div class="blackboard_hide" id="foro'.$rowe['week_id'].'">'.$rowe['title'].'</div>
                </a>
                </th>';
        }
    }
    $tableExport[] = $lineHeaderExport;
    $tableExport[] = $lineHeaderExport2;
    $line .= '</tr>';

    $html = '<form action="tutor.php" name="semanas" id="semanas" method="POST">
            <div class="row">
            '.get_lang('Select the timespan in weeks').'
            <select name="weeksNumber" id="weeksNumber" onChange="submit();">
            <option value="7" '.(7 == $weeksCount ? 'selected="selected"' : '').'>7 weeks</option>
            <option value="14" '.(14 == $weeksCount ? 'selected="selected"' : '').'>14 weeks</option>
            </select>';

    if (14 == $weeksCount) {
        $html .= '<span style="float:right;"><a href="tutor.php?page='.(1 == $page ? 2 : 1).'">'.(1 == $page ? 'Siguiente' : 'Anterior').'</a></span>';
    }
    //$html .= '<span style="float:right;"><a href="'.api_get_self().'?action=export'.$get_parameter.$get_parameter2.'">'.Display::getMdiIcon(ActionIcon::EXPORT_SPREADSHEET, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Export')).'</a></span>';

    $html .= '</form>';
    $html .= '<table class="reports">';
    $html .= '<tr>
            <th ></th>';
    for ($i = 7 * $page - 6; $i <= $page * 7; $i++) {
        $html .= '<th colspan="2">Week '.$i.'<a href="assign_tickets.php?id='.$ids[$i].'" class="ajax">'.Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')).'</a></th>';
    }
    $html .= '</tr>';
    $html .= $line;
    $sql = "SELECT
                u.username,
                u.id as user_id,
                CONCAT(u.lastname,' ', u.firstname ) as fullname ,
                rs.week_id , sr.work_ok ,
                sr.thread_ok ,
                sr.quiz_ok ,
                sr.pc_ok ,
                rs.course_code
            FROM $tableStudentsReport sr
            JOIN $tableWeeklyReport rs ON sr.week_report_id = rs.id
            JOIN $tableUser u
            ON u.id = sr.user_id
            WHERE rs.course_code = '$course_code'
            ORDER BY u.lastname , u.username , rs.week_id
    ";
    $result = Database::query($sql);
    while ($row = Database::fetch_assoc($result)) {
        $resultadose[$row['username']][$row['week_id']] = $row;
        if ($row['week_id'] > (($page - 1) * 7) && $row['week_id'] <= 7 * $page) {
            $results[$row['username']][$row['week_id']] = $row;
            if (7 == count($results[$row['username']])) {
                $html .= showStudentResult($results[$row['username']], $page);
            }
        }
        if (count($resultadose[$row['username']]) == $weeksCount) {
            $tableExport[] = showStudentResultExport($resultadose[$row['username']], $weeksCount);
        }
    }
    $html .= '
          </table>';

    return ['show' => $html, 'export' => $tableExport];
}

/**
 * @param $datos
 * @param $pagina
 *
 * @return string
 */
function showStudentResult($datos, $pagina)
{
    $inicio = 7 * $pagina - 6;
    $fila = '<tr>';

    $fila .= '<td><a href="'.api_get_path(WEB_CODE_PATH).'user/userInfo.php?'.api_get_cidreq().'&uInfo='.$datos[$inicio]['user_id'].'">'.$datos[$inicio]['username'].'</a></td>';
    foreach ($datos as $dato) {
        $fila .= '<td align="center">'.(1 == $dato['work_ok'] ? Display::getMdiIcon(StateIcon::COMPLETE, 'ch-tool-icon', null, ICON_SIZE_SMALL) : Display::getMdiIcon(StateIcon::INCOMPLETE, 'ch-tool-icon', null, ICON_SIZE_SMALL)).'</td>';
        $fila .= '<td align="center">'.(1 == $dato['thread_ok'] ? Display::getMdiIcon(StateIcon::COMPLETE, 'ch-tool-icon', null, ICON_SIZE_SMALL) : Display::getMdiIcon(StateIcon::INCOMPLETE, 'ch-tool-icon', null, ICON_SIZE_SMALL)).'</td>';
    }
    $fila .= '</tr>';

    return $fila;
}

/**
 * @param $data
 * @param $numero_semanas
 *
 * @return array
 */
function showStudentResultExport($data, $numero_semanas)
{
    $fila = [];
    $fila[] = utf8_decode($data[1]['username']);
    $fila[] = utf8_decode($data[1]['fullname']);
    foreach ($data as $line) {
        $fila[] = 1 == $line['work_ok'] ? get_lang('Yes') : get_lang('No');
        $fila[] = 1 == $line['thread_ok'] ? get_lang('Yes') : get_lang('No');
    }

    return $fila;
}

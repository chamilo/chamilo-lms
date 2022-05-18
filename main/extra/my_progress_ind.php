<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

$allow = api_get_configuration_value('extra');
if (empty($allow)) {
    exit;
}

api_block_anonymous_users();

$nameTools = get_lang('MyProgress');
$this_section = 'session_my_progress_ind';
$_user = api_get_user_info();

$tbl_stats_exercices = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$tbl_stats_access = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ACCESS);

Display::display_header($nameTools);

$result = Database::query(
    "SELECT DISTINCT session.id as id, name, access_start_date date_start, access_end_date date_end
    FROM session_rel_course_rel_user,session
    WHERE session_id=session.id AND user_id=".$_user['user_id']."
    ORDER BY date_start, date_end, name");

$Sessions = Database::store_result($result);
$Courses = [];

foreach ($Sessions as $enreg) {
    $id_session_temp = $enreg['id'];
    $sql8 = "SELECT *
                FROM course
                WHERE code = '$courses_code'
                ";
    $result8 = Database::query($sql8);
    $course_code_id = Database::fetch_array($result8);
    $c_id = $course_code_id['id'];
    $sql = "SELECT DISTINCT c_id,title, CONCAT(lastname, ' ',firstname) coach, username, date_start, date_end, db_name
            FROM $tbl_course , $tbl_session_course
            LEFT JOIN $tbl_user
                ON $tbl_session_course.id_coach = $tbl_user.user_id
            INNER JOIN $tbl_session_course_user
                ON $tbl_session_course_user.id_session = $tbl_session_course.id_session
                AND $tbl_session_course_user.id_user = '".$_user['user_id']."'
            INNER JOIN $tbl_session ON $tbl_session.id = $tbl_session_course.id_session
            WHERE $tbl_session_course.c_id=$c_id
            AND $tbl_session_course.id_session='$id_session_temp'
            ORDER BY title";
    $result = Database::query($sql);
    while ($a_session_courses = Database::fetch_array($result)) {
        $a_session_courses['id_session'] = $id_session_temp;
        $Courses[$a_session_courses['code']] = $a_session_courses;
    }
}

// affichage des jours complétés dans les parcours l'élève
//on recherche les cours où sont inscrit les user
$user_c_id = $_user['user_id'];
$sql2 = "SELECT  c_id, user_id
        FROM course_rel_user
        WHERE user_id = '$user_c_id'
        ";
$result2 = Database::query($sql2);
$Total = 0;
while ($a_courses = Database::fetch_array($result2)) {
    $courses_code = $a_courses['c_id'];
    //on sort le c_id avec le code du cours
    //$sql8 = "SELECT *
//      FROM course
//      WHERE code = '$courses_code'
//      ";
//        $result8 = Database::query($sql8);
//        $course_code_id = Database::fetch_array($result8) ;
    $c_id = $courses_code;
    //pours chaque cours dans lequel il est inscrit, on cherche les jours complétés
    $Req1 = "SELECT *
             FROM c_lp_view
             WHERE user_id  =  '$user_c_id' AND c_id = '$c_id'
    ";
    $res = Database::query($Req1);
    while ($result = Database::fetch_array($res)) {
        $lp_id = $result['lp_id'];
        $lp_id_view = $result['id'];
        $c_id_view = $result['c_id'];
        $Req2 = "SELECT id, lp_id ,title ,item_type
                    FROM  c_lp_item
                 WHERE lp_id =  '$lp_id'
                 AND title LIKE '(+)%'
                 AND c_id = '$c_id_view'
                 AND item_type = 'document'
            ";
        $res2 = Database::query($Req2);
        while ($resulta = Database::fetch_array($res2)) {
            $lp_item_id = $resulta['id'];
            $Req3 = " SELECT Max(id)
                      FROM  c_lp_item_view
                      WHERE
                        lp_item_id =  '$lp_item_id' AND
                        lp_view_id =  '$lp_id_view' AND
                        c_id = '$c_id_view' AND
                        status =  'completed'
                      ";
            $res3 = Database::query($Req3);
            while ($resul = Database::fetch_array($res3)) {
                $max = $resul['0'];
                $Req4 = "SELECT COUNT( id )
                          FROM  c_lp_item_view
                          WHERE
                            id = '$max' AND
                            c_id = '$c_id_view'
                                ";
                $res4 = Database::query($Req4);
                while ($resultat = Database::fetch_array($res4)) {
                    if ($resultat[0] == null) {
                        $resultat[0] = 0;
                    }
                    $Total = $Total + $resultat[0];
                }
            }
        }
    }
}

api_display_tool_title($nameTools);

$now = date('Y-m-d');
$tbl_personal_agenda = Database::get_main_table(TABLE_PERSONAL_AGENDA);

//on compte le nombre de m% dans l'agenda pour chaque module
$sqljtot = "SELECT COUNT( * ) AS TOT
             FROM $tbl_personal_agenda
             WHERE user = '".$_user['user_id']."'
             And title like 'm%'

             ";
$resultjt = Database::query($sqljtot);
$jour_realise = 0;
while ($jtot = Database::fetch_array($resultjt)) {
    $jour_realise_tot = ($jour_realise + $jtot['TOT']) / 2;
}

//fin des jour de l'agenda
//on trouve le nombre dans l'agenda selon la date d'aujourdhui
//si rien n'est inscrit cette journée dans l'agenda, recule de -1
$jour_agenda = '';
$tour = -1;
while ($jour_agenda == '') {
    $tour++;
    $date = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - $tour, date("Y")));
    $sql4 = "SELECT title  FROM $tbl_personal_agenda
             WHERE user = '".$_user['user_id']."' AND
             text='Pour le calendrier, ne pas effacer' AND
             date like '".$date." %:%' ";
    $result4 = Database::query($sql4);
    $res4 = Database::fetch_array($result4);
    $jour_agenda = $res4['title'];
    if ($tour > 300) {
        break;
    }
}
$diff = $jour_agenda - $Total;
if ($diff > 0) {
    $sing = get_lang('retard');
} else {
    $sing = get_lang('avance');
}
$diff = abs($diff);
?>
<table class="table table-hover table-striped data_table">
    <th rowspan="5">
        <?php
        //on récupere les points de controle de l'élève
        $pt[] = '0';
        $pt[] = '0';
        $sqlcontrole = "SELECT diff
                         FROM $tbl_stats_exercices
                         WHERE exe_user_id =  ".$_user['user_id']."
                         AND diff  != ''
                         ORDER BY exe_date ASC
                         ";
        $result = Database::query($sqlcontrole);
        while ($ptctl = Database::fetch_array($result)) {
            $pt[] = $ptctl['diff'];
        }

        //graphique de suivi

        /*include "../inc/teechartphp/sources/TChart.php";
        $chart = new TChart(500, 300);
        $chart->getAspect()->setView3D(false);
        $chart->getHeader()->setText("Graphique de suivi");
        $chart->getAxes()->getLeft()->setMinimumOffset(10);
        $chart->getAxes()->getLeft()->setMaximumOffset(10);
        $chart->getAxes()->getBottom()->setMinimumOffset(10);
        $chart->getAxes()->getBottom()->setMaximumOffset(10);
        $line1 = new Line($chart->getChart());
        $data = $pt;
        $line1->addArray($data);
        foreach ($chart->getSeries() as $serie) {
            $pointer = $serie->getPointer();
            $pointer->setVisible(true);
            $pointer->getPen()->setVisible(false);
            $pointer->setHorizSize(2);
            $pointer->setVertSize(2);

            $marks = $serie->getMarks();
            $marks->setVisible(true);
            $marks->setArrowLength(5);
            $marks->getArrow()->setVisible(false);
            $marks->setTransparent(true);
        }

        $x = $_user['user_id'];
        $line1->getPointer()->setStyle(PointerStyle::$CIRCLE);
        $chart->getLegend()->setVisible(false);
        $chart->render("../garbage/$x-image.png");
        $rand = rand();
        print '<img src="../garbage/'.$x.'-image.png?rand='.$rand.'">';
        */
        ?>
        <tr>
            <th align="left" width="412">
                <?php echo get_lang('Cumulatif_agenda'); ?>:
                <b><font color=#CC0000> <?php echo $jour_realise_tot; ?></font></b>
            </th>
        </tr>
        <tr>
            <th align="left">
                <?php echo get_lang('Cumulatif'); ?> <b><font color=#CC0000> <?php echo $Total; ?></font></b>
            </th>
        </tr>
        <tr>
            <th align="left">
                <?php echo get_lang('jours_selon_horaire'); ?>:
                <b><font color=#CC0000> <?php echo $jour_agenda; ?><?php echo $Days; ?></font></b>
            </th>
        </tr>
        <tr>
            <th align="left">
                <?php echo get_lang('diff2'); ?>:
                <b><font color=#CC0000> <?php echo $diff; ?><?php echo $Days, $sing; ?></font></b>
            </th>
        </tr>
    </table>
    <hr>
    <table class='table table-hover table-striped data_table'>
        <tr>
            <th><?php echo get_lang('level'); ?> </th>
            <th>
                <?php echo get_lang('lang_date'); ?>
            </th>
            <th>
                <?php echo get_lang('interventions_commentaires'); ?>
            </th>
        </tr>
        <?php

        $sqlinter = "SELECT *
                     FROM $tbl_stats_exercices
                     WHERE exe_user_id = ".$_user['user_id']."
                     And level != 0
                     Order by LEVEL ASC";
        $resultinter = Database::query($sqlinter);
        $level = '';
        while ($a_inter = Database::fetch_array($resultinter)) {
            $level = $a_inter['level'];
            $mod_no = $a_inter['mod_no'];
            $inter_coment = Security::remove_XSS($a_inter['inter_coment']);
            $inter_date = substr($a_inter['exe_date'], 0, 11);
            echo "
                <tr>
                    <td> ".$a_inter['level']."</td>
                    <td> $inter_date </td>
                    <td>$inter_coment</td>";
            $exe_id = $a_inter['exe_id'];
        }
        if ($level == 3) {
            echo '<span style="color: red; font-weight: bold;"><img src="../img/anim/pointeranim.gif"align="middle"  > ';
            echo $limit;
            echo '</span>';
        }
        ?>
        <p>
    </table><br>
<?php
//début de fin des cours prevu
$user_info = api_get_user_info();
$user_id = api_get_user_id();
//On cherche le calendrier pour ce user et le c_id de ce calendrier
$sql = "SELECT *
        FROM user
        WHERE user_id = '$user_id'
        ";
$result = Database::query($sql);
$horaire_id = Database::fetch_array($result);
$nom_hor = $horaire_id['official_code'];
$c_id_horaire = strstr($nom_hor, '.');
$c_id_horaire = str_replace(".", "", "$c_id_horaire");
// Courses
echo '<h3>'.get_lang('Course').'</h3>';
echo '<table class="table table-hover table-striped data_table">';
echo '<tr>
        <th>'.get_lang('Course').'</th>
        <th>'.get_lang('Time').'</th>
        <th>'.get_lang('FirstConnexion').'</th>
        <th>'.get_lang('Progress').'</th>
        <th>'.get_lang('fin_mod_prevue').'</th>
    </tr>';
//on recherche les cours où sont inscrit les user
$user_c_id = $_user['user_id'];
$sql2 = "SELECT  c_id, user_id
         FROM course_rel_user
         WHERE user_id = '$user_id'";

$result2 = Database::query($sql2);
while ($a_courses = Database::fetch_array($result2)) {
    $courses_code = $a_courses['c_id'];
    //on sort le c_id avec le code du cours
    $sql8 = "SELECT title, code
             FROM course
             WHERE id = '$courses_code'";
    $result8 = Database::query($sql8);
    $course_code_id = Database::fetch_array($result8);
    $c_id = $courses_code;
    $c_title = $course_code_id['title'];
    // Francois Belisle Kezber
    // The Tracking Class still uses the course code rather then the course id.
    $tracking_c_code = $course_code_id['code'];
    // time spent on the course
    $time_spent_on_course = api_time_to_hms(Tracking::get_time_spent_on_the_course($user_id, $c_id, $session_id));
    //  firts connection date
    $sql2 = "SELECT STR_TO_DATE(access_date,'%Y-%m-%d')
            FROM $tbl_stats_access
            WHERE
             access_user_id = '$user_id' AND
             c_id = '$c_id'
             ORDER BY access_id ASC
            LIMIT 0,1";

    //Francois Belisle Kezber
    // mysql fonctions rather then Database::
    // conversion to Database::
    $rs2 = Database::query($sql2);
    $num_rows = Database::num_rows($rs2);
    if ($num_rows > 0) {
        $rw2 = Database::fetch_array($rs2);
        $first_connection_date_to_module = $rw2[0];
    }

    //pour trouver la date de fin prévue du module
    $end_date_module = get_lang('hors_cal');
    //on trouve le nombre de jour pour ce module
    $sql = "SELECT * FROM c_cal_set_module
            where c_id = '$c_id'";
    $res = Database::query($sql);
    $resulta = Database::fetch_array($res);
    $nombre_heure = $resulta['minutes'];
    // on trouve le nombre de minute par jour
    $sql = "SELECT * FROM c_cal_horaire
            where c_id = '$c_id_horaire'
            AND name =  '$nom_hor'
             ";

    $res = Database::query($sql);
    $resulta = Database::fetch_array($res);
    $nombre_minutes = (int) $resulta['num_minute'];
    //on calcule le nombre de jour par module
    $nombre_jours_module = 0;
    if (!empty($nombre_minutes)) {
        $nombre_jours_module = $nombre_heure * '60' / $nombre_minutes;
    }
    //on arrondi
    $nombre_jours_module = (int) $nombre_jours_module;
    //on trouve la date de fin de chaque module AND date = date_format('$first_connection_date_to_module','%Y-%m-%d')
    $sql = "SELECT * FROM c_cal_dates
              WHERE
              horaire_name = '$nom_hor' AND
              c_id = '$c_id_horaire' AND
              STR_TO_DATE(date,'%Y-%m-%d') >= STR_TO_DATE('$first_connection_date_to_module','%Y-%m-%d')
              ORDER BY STR_TO_DATE(date, '%Y-%m-%d') asc
              LIMIT $nombre_jours_module, 18446744073709551615
          ";
    $res = Database::query($sql);
    //Database::data_seek($res,$nombre_jours_module);
    $row = Database::fetch_row($res);
    $end_date_module = $row[1];
    //fin de trouver la date de fin prévue du module
    //progression en %
    $t_lp = Database::get_course_table(TABLE_LP_MAIN);
    $sql_lp = " SELECT lp.name, lp.id FROM $t_lp lp WHERE c_id = '$c_id'  ORDER BY lp.display_order";
    $rs_lp = Database::query($sql_lp);
    $i = 0;
    while ($learnpath = Database::fetch_array($rs_lp)) {
        $lp_id = intval($learnpath['id']);
        $lp_name = $learnpath['name'];
        $any_result = false;
        // Get progress in lp
        // Francois Belisle Kezber
        // Course Code passed rather then course_id
        $progress = Tracking::get_avg_student_progress(
            $user_c_id, /*$c_id*/
            $tracking_c_code,
            [$lp_id],
            $session_id
        );
        if ($progress === null) {
            $progress = '0%';
        } else {
            $any_result = true;
        }

        // Get time in lp
        // Francois Belisle Kezber
        // Course Code passed rather then course_id
        $total_time = Tracking::get_time_spent_in_lp(
            $user_c_id, /*$c_id*/
            $tracking_c_code,
            [$lp_id],
            $session_id
        );
        if (!empty($total_time)) {
            $any_result = true;
        }

        if ($i % 2 == 0) {
            $css_class = "row_even";
        } else {
            $css_class = "row_odd";
        }

        $i++;

        if (is_numeric($progress)) {
            $progress = $progress.'%';
        } else {
            $progress = '-';
        }
        $data_learnpath[/*$i*/
        $lp_id][] = $progress.'%';
    }
    $warming = '';
    $today = date('Y-m-d');
    if (isset($end_date_module) && $end_date_module <= $today and $progress != '100%') {
        $warming = '<b><font color=#CC0000>  '.get_lang('limite_atteinte').'</font></b>';
    }
    $end_date_module = $end_date_module.$warming;
    echo '<tr>
            <td >'.$c_title.'</td>
            <td >'.$time_spent_on_course.'</td>
            <td >'.$first_connection_date_to_module.'</td>
            <td >'.$progress.'</td>
            <td >'.$end_date_module.'</td>';
    echo '</tr>';
}
echo '</table>';
?>   </table>
    <br/><br/>
    <table class='table table-hover table-striped data_table'>
        <tr>
            <th colspan="6">
                <?php
                echo get_lang('result_exam');
                //echo $_user['name'];
                ?>
            </th>
        <tr>
            <th><?php echo get_lang('module'); ?></th>
            <th><?php echo get_lang('result_exam'); ?></th>
            <th><?php echo get_lang('result_rep_1'); ?></th>
            <th><?php echo get_lang('result_rep_2'); ?></th>
            <th><?php echo get_lang('comment'); ?></th>
        </tr>
        <?php

        $sqlexam = "SELECT *
                     FROM $tbl_stats_exercices
                     WHERE exe_user_id = '".$_user['user_id']."'
                     AND c_id = '0'  AND mod_no != '0'
                     ORDER BY mod_no ASC";
        $resultexam = Database::query($sqlexam);
        while ($a_exam = Database::fetch_array($resultexam)) {
            $ex_id = $a_exam['ex_id'];
            $mod_no = $a_exam['mod_no'];
            $score_ex = $a_exam['score_ex'];
            $score_rep1 = $a_exam['score_rep1'];
            $score_rep2 = $a_exam['score_rep2'];
            $coment = stripslashes($a_exam['coment']);
            echo "
                <tr>
                    <td><center> ".$a_exam['mod_no']."
                    </td>
                <td><center>
                        ".$a_exam['score_ex']."
                    </td>
                <td><center>
                        ".$a_exam['score_rep1']."
                    </td><center>
                    <td><center>
                        ".$a_exam['score_rep2']."
                    </td>
                    <td>$coment

                ";
            $exe_idd = $a_exam['exe_id']; ?>
            </tr>
            <?php
        }
        ?>
    </table>
<?php

Display::display_footer();

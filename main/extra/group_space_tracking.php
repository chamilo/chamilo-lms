<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

$allow = api_get_configuration_value('extra');
if (empty($allow)) {
    exit;
}

$current_course_tool = TOOL_GROUP;

// Notice for unauthorized people.
api_protect_course_script(true);

/*  Libraries & config files */
require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';
/*  MAIN CODE */
$group_id = api_get_group_id();
$user_id = api_get_user_id();
$current_group = GroupManager::get_group_properties($group_id);
// regroup table names for maintenance purpose
if (!api_is_allowed_to_edit(false, true) && !$is_group_member) {
    api_not_allowed(true);
}
$tbl_stats_exercices_temp = Database::get_main_table(track_e_exercices_temp);
//on vide la table temp qui recoit les examen complétés
$query = "TRUNCATE TABLE $tbl_stats_exercices_temp ";
$result = Database::query($query);
if (empty($current_group)) {
    api_not_allowed(true);
}

$this_section = SECTION_COURSES;
$nameTools = get_lang('suivi_de');
$interbreadcrumb[] = ['url' => 'group.php', 'name' => get_lang('Groups')];
$forums_of_groups = get_forums_of_group($current_group['id']);
$forum_state_public = 0;
if (is_array($forums_of_groups)) {
    foreach ($forums_of_groups as $key => $value) {
        if ('public' == $value['forum_group_public_private']) {
            $forum_state_public = 1;
        }
    }
}

if (1 != $current_group['doc_state'] &&
    1 != $current_group['calendar_state'] &&
    1 != $current_group['work_state'] &&
    1 != $current_group['announcements_state'] &&
    1 != $current_group['wiki_state'] &&
    1 != $current_group['chat_state'] &&
    1 != $forum_state_public
) {
    if (!api_is_allowed_to_edit(null, true) &&
        !GroupManager::is_user_in_group($user_id, $group_id)) {
        api_not_allowed($print_headers);
    }
}

/*  Header */
Display::display_header($nameTools.' '.Security::remove_XSS($current_group['name']), 'Group');
Display::display_introduction_section(TOOL_GROUP);

$course_code = api_get_course_id();
$is_course_member = CourseManager::is_user_subscribed_in_real_or_linked_course(
    api_get_user_id(),
    $course_code
);

/*
 * List all the tutors of the current group
 */
$tutors = GroupManager::get_subscribed_tutors($current_group['id']);

$tutor_info = '';
if (count($tutors) == 0) {
    $tutor_info = get_lang('GroupNoneMasc');
} else {
    isset($origin) ? $my_origin = $origin : $my_origin = '';
    $tutor_info .= '<ul class="thumbnails">';
    foreach ($tutors as $index => $tutor) {
        $tab_user_info = api_get_user_info($tutor['user_id']);
        $username = api_htmlentities(sprintf(get_lang('LoginX'), $tab_user_info['username']), ENT_QUOTES);
        $image_path = UserManager::get_user_picture_path_by_id($tutor['user_id'], 'web', false, true);
        $image_repository = $image_path['dir'];
        $existing_image = $image_path['file'];
        $completeName = api_get_person_name($tutor['firstname'], $tutor['lastname']);
        $photo = '<img src="'.$image_repository.$existing_image.'" alt="'.$completeName.'" width="32" height="32" title="'.$completeName.'" />';
        $tutor_info .= '<li><a href="'.api_get_path(
                WEB_CODE_PATH
            ).'user/userInfo.php?origin='.$my_origin.'&amp;uInfo='.$tutor['user_id'].'">'.
            $photo.'&nbsp;'.$completeName.'</a></li>';
    }
    $tutor_info .= '</ul>';
}

echo Display::page_subheader(get_lang('GroupTutors'));
if (!empty($tutor_info)) {
    echo $tutor_info;
}

/*
 * List all the members of the current group
 */
echo '<div class="actions">';
$now = date('Y-m-d');
echo '&nbsp;Le '.$now.' <a href="#" onclick="window.print()"><img align="absbottom" src="../img/printmgr.gif">&nbsp;'.get_lang(
        'Print'
    ).'</a>';
echo '&nbsp; <a target="_blank" href="save_diff_all.php"><img src="'.api_get_path(
        WEB_IMG_PATH
    ).'addd.gif" border="0" />'.get_lang('save_diff_all').'</a>';
echo '</div>';

$table = new SortableTable(
    'group_users',
    'get_number_of_group_users',
    'get_group_user_data',
    (api_is_western_name_order() xor api_sort_by_first_name()) ? 2 : 1
);
//$table -> display();
$my_cidreq = isset($_GET['cidReq']) ? Security::remove_XSS($_GET['cidReq']) : '';
$my_origin = isset($_GET['origin']) ? Security::remove_XSS($_GET['origin']) : '';
$my_gidreq = isset($_GET['gidReq']) ? Security::remove_XSS($_GET['gidReq']) : '';
$parameters = ['cidReq' => $my_cidreq, 'origin' => $my_origin, 'gidReq' => $my_gidreq];
$table->set_additional_parameters($parameters);

$table->set_header(1, get_lang('OfficialCode'), false, 'align="center"');
if (api_is_western_name_order()) {
    $table->set_header(2, get_lang('LastName'));
    $table->set_header(3, get_lang('FirstName'));
} else {
    $table->set_header(2, get_lang('LastName'));
    $table->set_header(3, get_lang('FirstName'));
}
//the order of these calls is important
$table->set_column_filter(2, 'user_name_filter');
$table->set_column_filter(3, 'user_name_filter');
$table->set_header(4, get_lang('Examen'), false);
//the order of these calls is important
$table->set_column_filter(2, 'user_name_filter');
$table->set_column_filter(3, 'user_name_filter');
$table->set_header(4, get_lang('Examen'), false);
$table->set_header(5, get_lang('LatestLogin'), false, 'align="center"');
$table->set_header(6, get_lang('time_tot'), false, 'align="center"');
$table->set_header(7, get_lang('jours_complet'), false, 'align="center"');
$table->set_header(8, get_lang('diff_horaire'), false, 'align="center"');
$table->set_header(9, get_lang('save_diff'), false, 'align="center"');
$table->set_header(10, get_lang('GroupCalendar'), false, 'align="center"');
$table->set_header(11, get_lang('Details'), false);

// Database table definition
$table_group_user = Database::get_course_table(TABLE_GROUP_USER);
$table_user = Database::get_main_table(TABLE_MAIN_USER);

//  $tbl_personal_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);
$tbl_personal_agenda = Database::get_main_table(TABLE_PERSONAL_AGENDA);
$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_stats_exercices = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$tbl_stats_exercices_temp = Database::get_main_table(track_e_exercices_temp);
$tbl_group_course_info = Database::get_course_table(TABLE_GROUP);
$course_id = api_get_course_int_id();

//on trouve le vrai groupID
$sql = "SELECT iid FROM ".$tbl_group_course_info."
        WHERE c_id=".$course_id." and id=".$current_group['id'];
$current_group_result = Database::query($sql);
$current_group = Database::fetch_assoc($current_group_result)['iid'];
//on trouve les user dans le groupe
$sql = "SELECT *
        FROM ".$table_user." user, ".$table_group_user." group_rel_user
        WHERE group_rel_user.c_id = $course_id AND group_rel_user.user_id = user.user_id
        AND group_rel_user.group_id = ".$current_group." order by lastname
  ";
$result = Database::query($sql);
// Francois Belisle Kezber
//  Le TableDisplay contient une fonction qui set les headers... les headers sont plac�s dans la Row 0... Ce qui ecrase le contenue
// le la vrai row 0... Il faut donc ajouter un empty record a la row 0 qui se fera ecras� par lesh headers plutot que le premier record
// en ajoutant un empty record, ca fonctionne, mais il faut trouver pourquoi les headers ecrasent le premier record
$row = [];

$row[] = $student_datas['official_code'];
$row[] = $student_datas['lastname'];
$row[] = $student_datas['firstname'];
$row[] = substr_replace($exampass, '', '0', '2');
$row[] = $last_connection_date;
$row[] = $time_tot;
$row[] = $Total;
$row[] = '<center>'.$sing.''.$diff.'&nbsp;'.$Days.''.$avertissement.'</font></b></center>';
$row[] = '<center><a  target="_blank" href="save_diff.php?student='.$user_in_groupe.'&diff='.$ajout.''.$diff.'"><img src="'.api_get_path(
        WEB_IMG_PATH
    ).'addd.gif" border="0" /></a></center>';
$row[] = '<center><a target="_blank" href="suivi_myagenda.php?user_id='.$user_in_groupe.'&action=view&view=personal&firstname='.$student_datas['firstname'].'&name='.$student_datas['lastname'].'"><img src="'.api_get_path(
        WEB_IMG_PATH
    ).'calendar_week.gif" border="0" /></a></center>';
$row[] = '<center><a target="_blank" href="../mySpace/myStudents.php?student='.$user_in_groupe.'"><img src="'.api_get_path(
        WEB_IMG_PATH
    ).'2rightarrow.gif" border="0" /></a></center>';
$all_datas[] = $row;

foreach ($all_datas as $row) {
    $table->addRow($row);
}

while ($resulta = Database::fetch_array($result)) {
    $user_in_groupe = $resulta['user_id'];
    unset($all_datas);
    //on cherche les examens
    $sqlexam = "SELECT  mod_no
                 FROM $tbl_stats_exercices
                 WHERE exe_user_id = '$user_in_groupe'
                AND c_id = 0 AND (score_ex = 'SU' || score_rep1 = 'SU' || score_rep2 ='SU')
                ORDER BY mod_no ASC";
    $resultexam = Database::query($sqlexam);
    while ($a_exam = Database::fetch_array($resultexam)) {
        $exam = "$exam - $a_exam[0]";
    }
    Database::query(
        "INSERT INTO $tbl_stats_exercices_temp (id, exe_user_id, mod_passed) VALUES('', '$user_in_groupe',  '$exam') "
    );
    //fin de exam
    //on compte le nombre de m% dans l'agenda pour chaque module
    /*
       $sqljtot =  "SELECT COUNT( * ) AS TOT
                                   FROM $tbl_personal_agenda
                                   WHERE user = '$user_in_groupe'
                                   And title like 'm%'

                                   ";
                      $resultjt = Database::query($sqljtot);

                      while($jtot = mysql_fetch_array($resultjt))
                      {
                       $jour_realise_tot = ($jour_realise + $jtot['TOT'])/ 2;

                  }
   //fin des jour de l'agenda
  */
    $course_code = $_course['id'];
    $student_datas = api_get_user_info($user_in_groupe);
    // affichage des jours complétés dans les parcours par chaque élève
    //on recherche les cours où sont inscrit les user
    $sql2 = "SELECT  c_id, user_id
        FROM course_rel_user
        WHERE user_id = '$user_in_groupe'
        ";
    $result2 = Database::query($sql2);
    $Total = 0;
    while ($a_courses = Database::fetch_array($result2)) {
        $Courses_code = $a_courses['c_id'];
        $Courses_rel_user_id = $a_courses['user_id'];
        //on sort le temps passé dans chaque cours
        $sql = "SELECT  SUM(UNIX_TIMESTAMP(logout_course_date) - UNIX_TIMESTAMP(login_course_date)) as nb_seconds
                FROM track_e_course_access
                WHERE UNIX_TIMESTAMP(logout_course_date) > UNIX_TIMESTAMP(login_course_date) AND c_id = $course_id AND user_id = '$user_in_groupe'
                ";
        //echo($sql);
        $rs = Database::query($sql);
        $row2 = Database::fetch_array($rs);
        $nb_secondes = $row2['nb_seconds'];
        $time_tot1 += $nb_secondes;
        //convertion secondes en temps
        $time_tot = api_time_to_hms($time_tot1);
        //on sort le c_id avec le code du cours
        //$sql8 = "SELECT *
        //      FROM course
        //      WHERE id = '$Courses_code'
        //      ";
        //        $result8 = Database::query($sql8);
        //        $course_code_id = Database::fetch_array($result8) ;
        $c_id = $Courses_code;
        //pours chaque cours dans lequel il est inscrit, on cherche les jours complétés
        $Req3 = "SELECT *
                    FROM c_lp_view
                  WHERE user_id  = '$Courses_rel_user_id'
                  AND c_id = '$c_id'
                    ";
        $res3 = Database::query($Req3);
        while ($result3 = Database::fetch_array($res3)) {
            $lp_id = $result3['lp_id'];
            $lp_id_view = $result3['id'];
            $c_id_view = $result3['c_id'];

            $Req4 = "SELECT id, lp_id ,title ,item_type
                    FROM  c_lp_item
                 WHERE lp_id =  '$lp_id'
                 AND title LIKE '(+)%'
                 AND c_id = '$c_id_view'
                 AND item_type = 'document'
            ";
            $res4 = Database::query($Req4);

            while ($resulta4 = Database::fetch_array($res4)) {
                $lp_item_id = $resulta4['id'];
                $Req5 = " SELECT Max(id)
                FROM  c_lp_item_view
                       WHERE  lp_item_id =  '$lp_item_id'
                       AND lp_view_id =  '$lp_id_view'
                        AND c_id = '$c_id_view'
                         AND status =  'completed'
                         ";
                $res5 = Database::query($Req5);

                while ($resul5 = Database::fetch_array($res5)) {
                    $max = $resul5['0'];
                    $Req6 = "SELECT COUNT( id )
                             FROM  c_lp_item_view
                             WHERE  id = '$max'
                                  AND c_id = '$c_id_view'
                                     ";
                    $res6 = Database::query($Req6);
                    while ($resultat6 = Database::fetch_array($res6)) {
                        $Total = $Total + $resultat6[0];
                    }
                }
            }
        }
    }

    // fin affichage des jours complétés dans les parcours par chaque élève
    // on recherche les exam sauvegardé dans la table temp
    $sqlexamtot = "SELECT  mod_passed
                 FROM $tbl_stats_exercices_temp
                 WHERE exe_user_id = '$user_in_groupe'";
    $resultexamtot = Database::query($sqlexamtot);
    $a_examtot = Database::fetch_array($resultexamtot);
    $exampass = $a_examtot['mod_passed'];
    //on trouve le nombre dans l'agenda selon la date d'aujourdhui
    //si rien n'est inscrit cette journée dans l'agenda, recule de -1
    unset($jour_agenda);
    $tour = -1;
    while ($jour_agenda == '') {
        $tour++;
        $date = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - $tour, date("Y")));
        $sql4 = "SELECT *  FROM $tbl_personal_agenda
                 WHERE user = '$user_in_groupe' AND
                 text='Pour le calendrier, ne pas effacer'
                 AND date like '".$date." %:%'
                  ";
        $result4 = Database::query($sql4);
        $res4 = Database::fetch_array($result4);
        $jour_agenda = $res4['title'];
        if ($tour > 300) {
            break;
        }
    }

    //on affiche la différence
    $diff = $jour_agenda - $Total;
    if ($diff > 0) {
        $sing = '<b><font color=#CC0000>';
    } else {
        $sing = '<b><font color=#008000>';
    }
    if ($diff > 0) {
        $avertissement = get_lang('retard');
    } else {
        $avertissement = get_lang('avance');
    }
    if ($diff > 0) {
        $ajout = '-';
    } else {
        $ajout = '';
    }

    $diff = abs($diff);
    $last_connection_date = Tracking::get_last_connection_date($user_in_groupe, true);
    if ($last_connection_date == '') {
        $last_connection_date = get_lang('NoConnexion');
    }
    // on présente tous les résultats
    $row = [];
    $row[] = $student_datas['official_code'];
    $row[] = $student_datas['lastname'];
    $row[] = $student_datas['firstname'];
    $row[] = substr_replace($exampass, '', '0', '2');
    $row[] = $last_connection_date;
    $row[] = $time_tot;
    $row[] = $Total;
    $row[] = '<center>'.$sing.''.$diff.'&nbsp;'.$Days.''.$avertissement.'</font></b></center>';
    $row[] = '<center><a  target="_blank" href="save_diff.php?student='.$user_in_groupe.'&diff='.$ajout.''.$diff.'"><img src="'.api_get_path(
            WEB_IMG_PATH
        ).'addd.gif" border="0" /></a></center>';
    $row[] = '<center><a target="_blank" href="suivi_myagenda.php?user_id='.$user_in_groupe.'&action=view&view=personal&firstname='.$student_datas['firstname'].'&name='.$student_datas['lastname'].'"><img src="'.api_get_path(
            WEB_IMG_PATH
        ).'calendar_week.gif" border="0" /></a></center>';
    $row[] = '<center><a target="_blank" href="../mySpace/myStudents.php?student='.$user_in_groupe.'"><img src="'.api_get_path(
            WEB_IMG_PATH
        ).'2rightarrow.gif" border="0" /></a></center>';
    $all_datas[] = $row;

    foreach ($all_datas as $row) {
        $table->addRow($row);
    }
}
$table->display();
echo $precision_time;

/**
 * Return user profile link around the given user name.
 *
 * The parameters use a trick of the sorteable table, where the first param is
 * the original value of the column
 *
 * @param   string  User name (value of the column at the time of calling)
 * @param   string  URL parameters
 * @param   array   Row of the "sortable table" as it is at the time of function call - we extract the user ID from
 *                      there
 *
 * @return string HTML link
 */
function user_name_filter($name, $url_params, $row)
{
    $tab_user_info = api_get_user_info($row[0]);
    $username = api_htmlentities(sprintf(get_lang('LoginX'), $tab_user_info['username']), ENT_QUOTES);

    return '<a href="../user/userInfo.php?uInfo='.$row[0].'&amp;'.$url_params.'" title="'.$username.'">'.$name.'</a>';
}

// Footer
$orig = isset($origin) ? $origin : '';
if ('learnpath' != $orig) {
    Display::display_footer();
}

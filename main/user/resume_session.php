<?php
/* For licensing terms, see /license.txt */

/**
 *	@author Bart Mollet, Julio Montoya lot of fixes
 *
 *	@package chamilo.admin
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;
$id_session = (int) $_GET['id_session'];
SessionManager::protect_teacher_session_edit($id_session);

$tool_name = get_lang('SessionOverview');

$allowTutors = api_get_setting('allow_tutors_to_assign_students_to_session');
if ($allowTutors === 'true') {
    // Database Table Definitions
    $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
    $tbl_session_rel_class = Database::get_main_table(TABLE_MAIN_SESSION_CLASS);
    $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
    $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
    $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
    $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
    $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    $tbl_session_category = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
    $table_access_url_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

    $sql = 'SELECT
              name,
              nbr_courses,
              nbr_users,
              nbr_classes,
              DATE_FORMAT(access_start_date,"%d-%m-%Y") as access_start_date,
              DATE_FORMAT(access_end_date,"%d-%m-%Y") as access_end_date,
              lastname,
              firstname,
              username,
              session_admin_id,
              coach_access_start_date,
              coach_access_end_date,
              session_category_id,
              visibility
            FROM '.$tbl_session.'
            LEFT JOIN '.$tbl_user.'
            ON id_coach = user_id
            WHERE '.$tbl_session.'.id='.$id_session;

    $rs = Database::query($sql);
    $session = Database::store_result($rs);
    $session = $session[0];

    $sql = 'SELECT name
            FROM  '.$tbl_session_category.'
            WHERE id = '.intval($session['session_category_id']);
    $rs = Database::query($sql);
    $session_category = '';

    if (Database::num_rows($rs) > 0) {
        $rows_session_category = Database::store_result($rs);
        $rows_session_category = $rows_session_category[0];
        $session_category = $rows_session_category['name'];
    }

    $action = isset($_GET['action']) ? $_GET['action'] : null;

    $url_id = api_get_current_access_url_id();

    switch ($action) {
        case 'add_user_to_url':
            $user_id = $_REQUEST['user_id'];
            $result = UrlManager::add_user_to_url($user_id, $url_id);
            $user_info = api_get_user_info($user_id);
            if ($result) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('UserAdded').' '.api_get_person_name(
                            $user_info['firstname'],
                            $user_info['lastname']
                        ),
                        'confirm'
                    )
                );
            }
            break;
        case 'delete':
            $idChecked = $_GET['idChecked'];
            if (is_array($idChecked)) {
                $my_temp = [];
                foreach ($idChecked as $id) {
                    $courseInfo = api_get_course_info($id);
                    $my_temp[] = $courseInfo['real_id']; // forcing the escape_string
                }
                $idChecked = $my_temp;
                $idChecked = "'".implode("','", $idChecked)."'";

                $result = Database::query("DELETE FROM $tbl_session_rel_course WHERE session_id='$id_session' AND c_id IN($idChecked)");
                $nbr_affected_rows = Database::affected_rows($result);

                Database::query("DELETE FROM $tbl_session_rel_course_rel_user WHERE session_id='$id_session' AND c_id IN($idChecked)");
                Database::query("UPDATE $tbl_session SET nbr_courses=nbr_courses-$nbr_affected_rows WHERE id='$id_session'");
            }

            if (!empty($_GET['class'])) {
                $result = Database::query("DELETE FROM $tbl_session_rel_class WHERE session_id='$id_session' AND class_id=".intval($_GET['class']));
                $nbr_affected_rows = Database::affected_rows($result);
                Database::query("UPDATE $tbl_session SET nbr_classes=nbr_classes-$nbr_affected_rows WHERE id='$id_session'");
            }

            if (!empty($_GET['user'])) {
                $result = Database::query("DELETE FROM $tbl_session_rel_user WHERE relation_type<>".SESSION_RELATION_TYPE_RRHH." AND session_id ='$id_session' AND user_id=".intval($_GET['user']));
                $nbr_affected_rows = Database::affected_rows($result);
                Database::query("UPDATE $tbl_session SET nbr_users=nbr_users-$nbr_affected_rows WHERE id='$id_session'");

                $result = Database::query("DELETE FROM $tbl_session_rel_course_rel_user WHERE session_id ='$id_session' AND user_id=".intval($_GET['user']));
                $nbr_affected_rows = Database::affected_rows($result);

                Database::query("UPDATE $tbl_session_rel_course SET nbr_users=nbr_users-$nbr_affected_rows WHERE session_id ='$id_session'");
            }
            break;
    }
    Display::display_header($tool_name);

    echo Display::page_header(
        Display::return_icon(
            'session.png',
            get_lang('Session')
        ).' '.$session['name']
    );
    echo Display::page_subheader(get_lang('GeneralProperties').$url); ?>
    <!-- General properties -->
    <table class="table table-hover table-striped data_table">
    <tr>
        <td><?php echo get_lang('GeneralCoach'); ?> :</td>
        <td><?php echo api_get_person_name($session['firstname'], $session['lastname']).' ('.$session['username'].')'; ?></td>
    </tr>
    <?php if (!empty($session_category)) {
        ?>
    <tr>
        <td><?php echo get_lang('SessionCategory'); ?></td>
        <td><?php echo $session_category; ?></td>
    </tr>
    <?php
    } ?>
    <tr>
        <td><?php echo get_lang('Date'); ?> :</td>
        <td>
        <?php
        if ($session['access_start_date'] == '00-00-0000' && $session['access_end_date'] == '00-00-0000') {
            echo get_lang('NoTimeLimits');
        } else {
            if ($session['access_start_date'] != '00-00-0000') {
                //$session['date_start'] = Display::tag('i', get_lang('NoTimeLimits'));
                $session['access_start_date'] = get_lang('From').' '.$session['access_start_date'];
            } else {
                $session['access_start_date'] = '';
            }
            if ($session['access_end_date'] == '00-00-0000') {
                $session['access_end_date'] = '';
            } else {
                $session['access_end_date'] = get_lang('Until').' '.$session['access_end_date'];
            }
            echo $session['access_start_date'].' '.$session['access_end_date'];
        } ?>
        </td>
    </tr>
    <!-- show nb_days_before and nb_days_after only if they are different from 0 -->
    <tr>
        <td>
            <?php echo api_ucfirst(get_lang('SessionCoachStartDate')); ?> :
        </td>
        <td>
            <?php echo intval($session['coach_access_start_date']); ?>
        </td>
    </tr>
    <tr>
        <td>
            <?php echo api_ucfirst(get_lang('SessionCoachEndDate')); ?> :
        </td>
        <td>
            <?php echo intval($session['coach_session_access_end_date']); ?>
        </td>
    </tr>
    <tr>
        <td>
            <?php echo api_ucfirst(get_lang('SessionVisibility')); ?> :
        </td>
        <td>
            <?php if ($session['visibility'] == 1) {
            echo get_lang('ReadOnly');
        } elseif ($session['visibility'] == 2) {
            echo get_lang('Visible');
        } elseif ($session['visibility'] == 3) {
            echo api_ucfirst(get_lang('Invisible'));
        } ?>
        </td>
    </tr>
    <?php

    $multiple_url_is_on = api_get_multiple_access_url();
    if ($multiple_url_is_on) {
        echo '<tr><td>';
        echo 'URL';
        echo '</td>';
        echo '<td>';
        $url_list = UrlManager::get_access_url_from_session($id_session);
        foreach ($url_list as $url_data) {
            echo $url_data['url'].'<br />';
        }
        echo '</td></tr>';
    } ?>
    </table>
    <br />
    <?php
    echo Display::page_subheader(get_lang('CourseList').$url); ?>
    <!--List of courses -->
    <table class="table table-hover table-striped data_table">
    <tr>
      <th width="35%"><?php echo get_lang('CourseTitle'); ?></th>
      <th width="30%"><?php echo get_lang('CourseCoach'); ?></th>
      <th width="20%"><?php echo get_lang('UsersNumber'); ?></th>
    </tr>
    <?php
    if ($session['nbr_courses'] == 0) {
        echo '<tr>
            <td colspan="4">'.get_lang('NoCoursesForThisSession').'</td>
            </tr>';
    } else {
        // select the courses
        $sql = "SELECT c.id, code,title,visual_code, nbr_users
                FROM $tbl_course c,$tbl_session_rel_course sc
                WHERE c.id = sc.c_id
                AND	session_id='$id_session'
                ORDER BY title";
        $result = Database::query($sql);
        $courses = Database::store_result($result);
        foreach ($courses as $course) {
            // Select the number of users
            $sql = "SELECT count(*) FROM $tbl_session_rel_user sru, $tbl_session_rel_course_rel_user srcru
                    WHERE
                        srcru.user_id = sru.user_id AND
                        srcru.session_id = sru.session_id AND
                        srcru.c_id = '".Database::escape_string($course['id'])."'AND
                        sru.relation_type<>".SESSION_RELATION_TYPE_RRHH." AND
                        srcru.session_id = '".intval($id_session)."'";

            $rs = Database::query($sql);
            $course['nbr_users'] = Database::result($rs, 0, 0);

            // Get coachs of the courses in session

            $sql = "SELECT user.lastname,user.firstname,user.username
                    FROM $tbl_session_rel_course_rel_user session_rcru, $tbl_user user
                    WHERE
                        session_rcru.user_id = user.id AND
                        session_rcru.session_id = '".intval($id_session)."' AND
                        session_rcru.c_id ='".Database::escape_string($course['id'])."' AND
                        session_rcru.status=2";
            $rs = Database::query($sql);

            $coachs = [];
            if (Database::num_rows($rs) > 0) {
                while ($info_coach = Database::fetch_array($rs)) {
                    $coachs[] = api_get_person_name(
                        $info_coach['firstname'],
                        $info_coach['lastname']
                    ).' ('.$info_coach['username'].')';
                }
            } else {
                $coach = get_lang('None');
            }

            if (count($coachs) > 0) {
                $coach = implode('<br />', $coachs);
            } else {
                $coach = get_lang('None');
            }

            $orig_param = '&origin=resume_session';
            //hide_course_breadcrumb the parameter has been added to hide the
            // name of the course, that appeared in the default $interbreadcrumb
            echo '
            <tr>
                <td>'.Display::url($course['title'].' ('.$course['visual_code'].')', api_get_path(WEB_COURSE_PATH).$course['code'].'/?id_session='.$id_session), '</td>
                <td>'.$coach.'</td>
                <td>'.$course['nbr_users'].'</td>

            </tr>';
        }
    } ?>
    </table>
    <br />
    <?php
    echo Display::page_subheader(get_lang('UserList').$url); ?>
    <!--List of users -->
    <table class="table table-hover table-striped data_table">
        <tr>
            <th>
                <?php echo get_lang('User'); ?>
            </th>
            <th>
                <?php echo get_lang('Actions'); ?>
            </th>
        </tr>
    <?php

    if ($session['nbr_users'] == 0) {
        echo '<tr>
                <td colspan="2">'.get_lang('NoUsersForThisSession').'</td>
            </tr>';
    } else {
        $order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname' : ' ORDER BY lastname, firstname';

        if ($multiple_url_is_on) {
            $sql = "SELECT u.user_id, lastname, firstname, username, access_url_id
                    FROM $tbl_user u
                    INNER JOIN $tbl_session_rel_user su
                    ON u.user_id = su.user_id AND su.relation_type<>".SESSION_RELATION_TYPE_RRHH."
                    LEFT OUTER JOIN $table_access_url_user uu ON (uu.user_id = u.user_id)
                    WHERE su.session_id = $id_session AND (access_url_id = $url_id OR access_url_id is null )
                    $order_clause";
        } else {
            $sql = "SELECT u.user_id, lastname, firstname, username
                    FROM $tbl_user u
                    INNER JOIN $tbl_session_rel_user su
                    ON u.user_id = su.user_id AND su.relation_type<>".SESSION_RELATION_TYPE_RRHH."
                    AND su.session_id = ".$id_session.$order_clause;
        }

        $result = Database::query($sql);
        $users = Database::store_result($result);
        // change breadcrumb in destination page
        $orig_param = '&origin=resume_session&id_session='.$id_session;
        foreach ($users as $user) {
            $user_link = '';
            if (!empty($user['user_id'])) {
                $user_link = '<a href="'.api_get_path(WEB_CODE_PATH).'admin/user_information.php?user_id='.intval($user['user_id']).'">'.
                    api_htmlentities(api_get_person_name($user['firstname'], $user['lastname']), ENT_QUOTES, $charset).' ('.$user['username'].')</a>';
            }

            $link_to_add_user_in_url = '';

            if ($multiple_url_is_on) {
                if ($user['access_url_id'] != $url_id) {
                    $user_link .= ' '.Display::return_icon('warning.png', get_lang('UserNotAddedInURL'), [], ICON_SIZE_SMALL);
                    $add = Display::return_icon('add.png', get_lang('AddUsersToURL'), [], ICON_SIZE_SMALL);
                    $link_to_add_user_in_url = '<a href="resume_session.php?action=add_user_to_url&id_session='.$id_session.'&user_id='.$user['user_id'].'">'.$add.'</a>';
                }
            }

            echo '<tr>
                    <td width="90%">
                        '.$user_link.'
                    </td>
                    <td>
                        <a href="../mySpace/myStudents.php?student='.$user['user_id'].''.$orig_param.'">'.
                        Display::return_icon('statistics.gif', get_lang('Reporting')).'</a>&nbsp;
                        <a href="session_course_user.php?id_user='.$user['user_id'].'&id_session='.$id_session.'">'.
                        Display::return_icon('course.png', get_lang('BlockCoursesForThisUser')).'</a>&nbsp;
                        <a href="'.api_get_self().'?id_session='.$id_session.'&action=delete&user='.$user['user_id'].'" onclick="javascript:if(!confirm(\''.get_lang('ConfirmYourChoice').'\')) return false;">'.
                        Display::return_icon('delete.png', get_lang('Delete')).'</a>
                        '.$link_to_add_user_in_url.'
                    </td>
                    </tr>';
        }
    } ?>
    </table>
<?php
} else {
        api_not_allowed();
    }
// footer
Display::display_footer();

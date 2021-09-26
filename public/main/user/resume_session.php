<?php

/* For licensing terms, see /license.txt */

/**
 *	@author Bart Mollet, Julio Montoya lot of fixes
 */

use Chamilo\CoreBundle\Component\Utils\NameConvention;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;
$id_session = (int) $_GET['id_session'];
SessionManager::protect_teacher_session_edit($id_session);
$url = null;
$tool_name = get_lang('Session overview');

$allowTutors = api_get_setting('allow_tutors_to_assign_students_to_session');
if ('true' === $allowTutors) {
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

    $nameConvention = Container::$container->get(NameConvention::class);

    $session = api_get_session_entity($id_session);

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
                        get_lang('The user has been added').' '.api_get_person_name(
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
                $result = Database::query("DELETE FROM $tbl_session_rel_user WHERE relation_type = ".Session::STUDENT." AND session_id ='$id_session' AND user_id=".intval($_GET['user']));
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
        ).' '.$session->getName()
    );
    echo Display::page_subheader(get_lang('General properties').$url); ?>
    <!-- General properties -->
    <table class="table table-hover table-striped data_table">
    <tr>
        <td><?php echo get_lang('General coaches'); ?> :</td>
        <td>
            <?php
            foreach ($session->getGeneralCoaches() as $generalCoach) {
                echo $nameConvention->getPersonName($generalCoach).'<br>';
            }
            ?>
        </td>
    </tr>
    <?php if ($session->getCategory()) { ?>
    <tr>
        <td><?php echo get_lang('Sessions categories'); ?></td>
        <td><?php echo $session->getCategory()->getName(); ?></td>
    </tr>
    <?php } ?>

    <?php if ($session->getDuration()) { ?>
        <tr>
            <td><?php echo get_lang('Duration'); ?></td>
            <td><?php echo $session->getDuration().' '.get_lang('Days'); ?></td>
        </tr>
    <?php } else { ?>
        <?php $sessionDates = SessionManager::parseSessionDates($session, true); ?>
        <tr>
            <td><?php echo get_lang('Access dates for students'); ?></td>
            <td><?php echo $sessionDates['access'] ?></td>
        </tr>
        <tr>
            <td><?php echo get_lang('Access dates for coaches'); ?></td>
            <td><?php echo $sessionDates['coach'] ?></td>
        </tr>
    <?php } ?>
    <tr>
        <td>
            <?php echo api_ucfirst(get_lang('Visibility after end date')); ?> :
        </td>
        <td>
            <?php echo SessionManager::getSessionVisibility($session); ?>
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
    echo Display::page_subheader(get_lang('Course list').$url); ?>
    <!--List of courses -->
    <table class="table table-hover table-striped data_table">
    <tr>
      <th width="35%"><?php echo get_lang('Course title'); ?></th>
      <th width="30%"><?php echo get_lang('Course coach'); ?></th>
      <th width="20%"><?php echo get_lang('Users number'); ?></th>
    </tr>
    <?php
    if (0 == $session->getNbrCourses()) {
        echo '<tr>
            <td colspan="3">'.get_lang('No course for this session').'</td>
            </tr>';
    } else {
        // select the courses
        foreach ($session->getCourses() as $sessionRelCourse) {
            $course = $sessionRelCourse->getCourse();

            $coachSubscriptionList = $session->getSessionRelCourseRelUsersByStatus($course, Session::COURSE_COACH)
                ->map(
                    fn(SessionRelCourseRelUser $sessionCourseUser) => $nameConvention->getPersonName($sessionCourseUser->getUser())
                );

            $courseLink = Display::url(
                $course->getTitle().' ('.$course->getVisualCode().')',
                api_get_course_url($course->getId(), $session->getId())
            );
            $coaches = $coachSubscriptionList ? implode('<br>', $coachSubscriptionList->getValues()) : get_lang('None');
            $nbrUsers = $sessionRelCourse->getNbrUsers();

            echo '
            <tr>
                <td>'.$courseLink.'</td>
                <td>'.$coaches.'</td>
                <td>'.$nbrUsers.'</td>
            </tr>';
        }
    } ?>
    </table>
    <br />
    <?php
    echo Display::page_subheader(get_lang('User list').$url); ?>
    <!--List of users -->
    <table class="table table-hover table-striped data_table">
        <tr>
            <th>
                <?php echo get_lang('User'); ?>
            </th>
            <th>
                <?php echo get_lang('Detail'); ?>
            </th>
        </tr>
    <?php

    if (0 == $session->getNbrUsers()) {
        echo '<tr>
                <td colspan="2">'.get_lang('No Users for this session').'</td>
            </tr>';
    } else {
        $order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname' : ' ORDER BY lastname, firstname';

        if ($multiple_url_is_on) {
            $sql = "SELECT u.id as user_id, lastname, firstname, username, access_url_id
                    FROM $tbl_user u
                    INNER JOIN $tbl_session_rel_user su
                    ON u.id = su.user_id AND su.relation_type = ".Session::STUDENT."
                    LEFT OUTER JOIN $table_access_url_user uu ON (uu.user_id = u.id)
                    WHERE su.session_id = $id_session AND (access_url_id = $url_id OR access_url_id is null )
                    $order_clause";
        } else {
            $sql = "SELECT u.id as user_id, lastname, firstname, username
                    FROM $tbl_user u
                    INNER JOIN $tbl_session_rel_user su
                    ON u.id = su.user_id AND su.relation_type = ".Session::STUDENT."
                    AND su.session_id = ".$id_session.$order_clause;
        }

        $result = Database::query($sql);
        $users = Database::store_result($result);
        // change breadcrumb in destination page
        $orig_param = '&origin=resume_session&id_session='.$id_session;
        foreach ($users as $user) {
            $user_link = '';
            if (!empty($user['user_id'])) {
                $user_link = '<a
                    href="'.api_get_path(WEB_CODE_PATH).'admin/user_information.php?user_id='.intval($user['user_id']).'">'.
                    api_htmlentities(api_get_person_name($user['firstname'], $user['lastname']), ENT_QUOTES, $charset).' ('.$user['username'].')</a>';
            }

            $link_to_add_user_in_url = '';

            if ($multiple_url_is_on) {
                if ($user['access_url_id'] != $url_id) {
                    $user_link .= ' '.Display::return_icon('warning.png', get_lang('Users not added to the URL'), [], ICON_SIZE_SMALL);
                    $add = Display::return_icon('add.png', get_lang('Add users to an URL'), [], ICON_SIZE_SMALL);
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
                        Display::return_icon('course.png', get_lang('Block user from courses in this session')).'</a>&nbsp;
                        <a href="'.api_get_self().'?id_session='.$id_session.'&action=delete&user='.$user['user_id'].'" onclick="javascript:if(!confirm(\''.get_lang('Please confirm your choice').'\')) return false;">'.
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

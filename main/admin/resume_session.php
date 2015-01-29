<?php
/* For licensing terms, see /license.txt */
/**
*	@author Bart Mollet, Julio Montoya lot of fixes
*	@package chamilo.admin
*/

// name of the language file that needs to be included
$language_file = 'admin';
$cidReset = true;
require_once '../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

$sessionId = isset($_GET['id_session']) ? intval($_GET['id_session']) : null;

if (empty($sessionId)) {
    api_not_allowed(true);
}

SessionManager::protect_session_edit($sessionId);

$tool_name = get_lang('SessionOverview');

$interbreadcrumb[] = array('url' => 'index.php','name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'session_list.php','name' => get_lang('SessionList'));

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
            DATE_FORMAT(date_start,"%d-%m-%Y") as date_start,
            DATE_FORMAT(date_end,"%d-%m-%Y") as date_end,
            lastname,
            firstname,
            username,
            session_admin_id,
            nb_days_access_before_beginning,
            nb_days_access_after_end,
            session_category_id,
            visibility
		FROM '.$tbl_session.'
		LEFT JOIN '.$tbl_user.'
		ON id_coach = user_id
		WHERE '.$tbl_session.'.id='.$sessionId;

$rs      = Database::query($sql);
$session = Database::store_result($rs);
$session = $session[0];

$sql = 'SELECT name FROM  '.$tbl_session_category.'
        WHERE id = "'.intval($session['session_category_id']).'"';
$rs = Database::query($sql);
$session_category = '';

if (Database::num_rows($rs)>0) {
	$rows_session_category = Database::store_result($rs);
	$rows_session_category = $rows_session_category[0];
	$session_category = $rows_session_category['name'];
}

$action = isset($_GET['action']) ? $_GET['action'] : null;

$url_id = api_get_current_access_url_id();

switch ($action) {
    case 'move_up':
        SessionManager::moveUp($sessionId, $_GET['course_code']);
        header('Location: resume_session.php?id_session='.$sessionId);
        exit;
        break;
    case 'move_down':
        SessionManager::moveDown($sessionId, $_GET['course_code']);
        header('Location: resume_session.php?id_session='.$sessionId);
        exit;
        break;
    case 'add_user_to_url':
        $user_id = $_REQUEST['user_id'];
        $result = UrlManager::add_user_to_url($user_id, $url_id);
        $user_info = api_get_user_info($user_id);
        if ($result) {
            $message = Display::return_message(
                get_lang('UserAdded').' '.api_get_person_name($user_info['firstname'], $user_info['lastname']),
                'confirm'
            );
        }
        break;
    case 'delete':
        // Delete course from session.
        $idChecked = isset($_GET['idChecked']) ? $_GET['idChecked'] : null;
        if (is_array($idChecked)) {
            $usersToDelete = array();
            foreach ($idChecked as $courseCode) {
                // forcing the escape_string
                $courseInfo = api_get_course_info($courseCode);
                SessionManager::unsubscribe_course_from_session(
                    $sessionId,
                    $courseInfo['real_id']
                );
            }
        }

        if (!empty($_GET['class'])) {
            Database::query("DELETE FROM $tbl_session_rel_class
                             WHERE session_id='$sessionId' AND class_id=".intval($_GET['class']));
            $nbr_affected_rows=Database::affected_rows();
            Database::query("UPDATE $tbl_session SET nbr_classes=nbr_classes-$nbr_affected_rows WHERE id='$sessionId'");
        }

        if (!empty($_GET['user'])) {
            SessionManager::unsubscribe_user_from_session(
                $sessionId,
                $_GET['user']
            );
        }
        break;
}
Display::display_header($tool_name);

if (!empty($_GET['warn'])) {
    Display::display_warning_message(urldecode($_GET['warn']));
}

if (!empty($message)) {
    echo $message;
}

echo Display::page_header(
    Display::return_icon('session.png', get_lang('Session')).' '.$session['name']
);

$url = Display::url(
    Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL),
    "session_edit.php?page=resume_session.php&id=$sessionId"
);
echo Display::page_subheader(get_lang('GeneralProperties').$url);

?>
<!-- General properties -->
<table class="data_table">
<tr>
	<td><?php echo get_lang('GeneralCoach'); ?> :</td>
	<td><?php echo api_get_person_name($session['firstname'], $session['lastname']).' ('.$session['username'].')' ?></td>
</tr>
<?php if(!empty($session_category)) { ?>
<tr>
	<td><?php echo get_lang('SessionCategory') ?></td>
	<td><?php echo $session_category;  ?></td>
</tr>
<?php } ?>
<tr>
	<td><?php echo get_lang('Date'); ?> :</td>
	<td>
	<?php
		if ($session['date_start'] == '00-00-0000' && $session['date_end']== '00-00-0000' )
			echo get_lang('NoTimeLimits');
		else {
            if ($session['date_start'] != '00-00-0000') {
                $session['date_start'] =  get_lang('From').' '.$session['date_start'];
            } else {
            	$session['date_start'] = '';
            }
            if ($session['date_end'] == '00-00-0000') {
                $session['date_end'] ='';
            } else {
            	$session['date_end'] = get_lang('Until').' '.$session['date_end'];
            }
			echo $session['date_start'].' '.$session['date_end'];
        }
        ?>
	</td>
</tr>
<!-- show nb_days_before and nb_days_after only if they are different from 0 -->
<tr>
	<td>
		<?php echo api_ucfirst(get_lang('DaysBefore')) ?> :
	</td>
	<td>
		<?php echo intval($session['nb_days_access_before_beginning']) ?>
	</td>
</tr>
<tr>
	<td>
		<?php echo api_ucfirst(get_lang('DaysAfter')) ?> :
	</td>
	<td>
		<?php echo intval($session['nb_days_access_after_end']) ?>
	</td>
</tr>
<tr>
	<td>
		<?php echo api_ucfirst(get_lang('SessionVisibility')) ?> :
	</td>
	<td>
		<?php
        if ($session['visibility']==1)
            echo get_lang('ReadOnly');
        elseif($session['visibility']==2)
            echo get_lang('Visible');
        elseif($session['visibility']==3)
            echo api_ucfirst(get_lang('Invisible'));
        ?>
	</td>
</tr>

<?php
$sessionField = new SessionField();
$sessionFields = $sessionField->get_all();

foreach ($sessionFields as $field) {
    if ($field['field_visible'] != '1') {
        continue;
    }

    $sesionFieldValue = new ExtraFieldValue('session');
    $sesionValueData = $sesionFieldValue->get_values_by_handler_and_field_id($sessionId, $field['id'], true);
    ?>
        <tr>
            <td><?php echo $field['field_display_text'] ?></td>
            <td>
                <?php
                switch ($field['field_type']) {
                    case ExtraField::FIELD_TYPE_CHECKBOX:
                        if ($sesionValueData !== false && $sesionValueData['field_value'] == '1') {
                            echo get_lang('Yes');
                        } else {
                            echo get_lang('No');
                        }
                        break;
                    case ExtraField::FIELD_TYPE_DATE:
                        if ($sesionValueData !== false && !empty($sesionValueData['field_value'])) {
                            echo api_format_date($sesionValueData['field_value'], DATE_FORMAT_LONG_NO_DAY);
                        } else {
                            echo get_lang('None');
                        }
                        break;
                    case ExtraField::FIELD_TYPE_FILE_IMAGE:
                        if ($sesionValueData !== false && !empty($sesionValueData['field_value'])) {
                            if (file_exists(api_get_path(SYS_CODE_PATH) . $sesionValueData['field_value'])) {
                                $image = Display::img(
                                    api_get_path(WEB_CODE_PATH) . $sesionValueData['field_value'],
                                    $field['field_display_text'],
                                    array('width' => '300')
                                );
                                
                                echo Display::url(
                                    $image,
                                    api_get_path(WEB_CODE_PATH) . $sesionValueData['field_value'],
                                    array('target' => '_blank')
                                );
                            }
                        }
                        break;
                    case ExtraField::FIELD_TYPE_FILE:
                        if ($sesionValueData !== false && !empty($sesionValueData['field_value'])) {
                            if (file_exists(api_get_path(SYS_CODE_PATH) . $sesionValueData['field_value'])) {
                                echo Display::url(
                                    get_lang('Download'),
                                    api_get_path(WEB_CODE_PATH) . $sesionValueData['field_value'],
                                    array(
                                        'title' => $field['field_display_text'],
                                        'target' => '_blank'
                                    )
                                );
                            }
                        }
                        break;
                    default:
                        echo $sesionValueData['field_value'];
                        break;
                }
                ?>
            </td>
        </tr>
    <?php
}

$multiple_url_is_on = api_get_multiple_access_url();

if ($multiple_url_is_on) {
    echo '<tr><td>';
    echo 'URL';
    echo '</td>';
    echo '<td>';
    $url_list = UrlManager::get_access_url_from_session($sessionId);
    foreach ($url_list as $url_data) {
        echo $url_data['url'].'<br />';
    }
    echo '</td></tr>';
}

if (SessionManager::durationPerUserIsEnabled()) {
    $sessionInfo = api_get_session_info($sessionId);
    echo '<tr><td>';
    echo get_lang('Duration');
    echo '</td>';
    echo '<td>';
    echo $sessionInfo['duration'].' ';
    echo get_lang('Days');
    echo '</td></tr>';

}
?>
</table>
<br />

<?php

$url = Display::url(
    Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL),
    "add_courses_to_session.php?page=resume_session.php&id_session=$sessionId"
);
echo Display::page_subheader(get_lang('CourseList').$url);

?>

<!--List of courses -->
<table class="data_table">
<tr>
  <th width="35%"><?php echo get_lang('CourseTitle'); ?></th>
  <th width="30%"><?php echo get_lang('CourseCoach'); ?></th>
  <th width="10%"><?php echo get_lang('UsersNumber'); ?></th>
  <th width="25%"><?php echo get_lang('Actions'); ?></th>
</tr>
<?php
if ($session['nbr_courses'] == 0) {
	echo '<tr>
			<td colspan="4">'.get_lang('NoCoursesForThisSession').'</td>
		</tr>';
} else {
	// select the courses

    $orderBy = "ORDER BY title";
    if (SessionManager::orderCourseIsEnabled()) {
        $orderBy = "ORDER BY position";
    }

	$sql = "SELECT code,title,visual_code, nbr_users
			FROM $tbl_course, $tbl_session_rel_course
			WHERE
			    course_code = code AND
			    id_session='$sessionId'
			$orderBy";

    $result = Database::query($sql);
    $courses = Database::store_result($result);
    $count = 0;

	foreach ($courses as $course) {
		//select the number of users

		$sql = "SELECT count(*)
                FROM $tbl_session_rel_user sru, $tbl_session_rel_course_rel_user srcru
				WHERE
				    srcru.id_user = sru.id_user AND
				    srcru.id_session = sru.id_session AND
				    srcru.course_code = '".Database::escape_string($course['code'])."' AND
				    sru.relation_type <> ".SESSION_RELATION_TYPE_RRHH." AND
				    srcru.id_session = '".intval($sessionId)."'";

		$rs = Database::query($sql);
		$course['nbr_users'] = Database::result($rs, 0, 0);

		// Get coachs of the courses in session

		$sql = "SELECT user.lastname,user.firstname,user.username
                FROM $tbl_session_rel_course_rel_user session_rcru, $tbl_user user
				WHERE
				    session_rcru.id_user = user.user_id AND
				    session_rcru.id_session = '".intval($sessionId)."' AND
				    session_rcru.course_code ='".Database::escape_string($course['code'])."' AND
				    session_rcru.status=2";
		$rs = Database::query($sql);

		$coachs = array();
		if (Database::num_rows($rs) > 0) {
			while($info_coach = Database::fetch_array($rs)) {
				$coachs[] = api_get_person_name($info_coach['firstname'], $info_coach['lastname']).' ('.$info_coach['username'].')';
			}
		} else {
			$coach = get_lang('None');
		}

		if (count($coachs) > 0) {
			$coach = implode('<br />',$coachs);
		} else {
			$coach = get_lang('None');
		}

        $orderButtons = null;

        if (SessionManager::orderCourseIsEnabled()) {
            $upIcon = 'up.png';
            $urlUp = api_get_self().'?id_session='.$sessionId.'&course_code='.$course['code'].'&action=move_up';

            if ($count == 0) {
                $upIcon = 'up_na.png';
                $urlUp = '#';
            }

            $orderButtons = Display::url(
                Display::return_icon($upIcon, get_lang('MoveUp')),
                $urlUp
            );

            $downIcon = 'down.png';
            $downUrl = api_get_self().'?id_session='.$sessionId.'&course_code='.$course['code'].'&action=move_down';

            if ($count +1 == count($courses)) {
                $downIcon = 'down_na.png';
                $downUrl = '#';
            }

            $orderButtons .= Display::url(
                Display::return_icon($downIcon, get_lang('MoveDown')),
                $downUrl
            );
        }

		$orig_param = '&origin=resume_session';
		//hide_course_breadcrumb the parameter has been added to hide the name of the course, that appeared in the default $interbreadcrumb
		echo '
		<tr>
			<td>'.Display::url($course['title'].' ('.$course['visual_code'].')', api_get_path(WEB_COURSE_PATH).$course['code'].'/?id_session='.$sessionId),'</td>
			<td>'.$coach.'</td>
			<td>'.$course['nbr_users'].'</td>
			<td>
                <a href="'.api_get_path(WEB_COURSE_PATH).$course['code'].'/?id_session='.$sessionId.'">'.Display::return_icon('course_home.gif', get_lang('Course')).'</a>
                '.$orderButtons.'
                <a href="session_course_user_list.php?id_session='.$sessionId.'&course_code='.$course['code'].'">'.Display::return_icon('user.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>
                <a href="'.api_get_path(WEB_CODE_PATH).'/user/user_import.php?action=import&cidReq='.$course['code'].'&id_session='.$sessionId.'">'.Display::return_icon('import_csv.png', get_lang('ImportUsersToACourse'), null, ICON_SIZE_SMALL).'</a>
				<a href="../tracking/courseLog.php?id_session='.$sessionId.'&cidReq='.$course['code'].$orig_param.'&hide_course_breadcrumb=1">'.Display::return_icon('statistics.gif', get_lang('Tracking')).'</a>&nbsp;
				<a href="session_course_edit.php?id_session='.$sessionId.'&page=resume_session.php&course_code='.$course['code'].''.$orig_param.'">'.Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>
				<a href="'.api_get_self().'?id_session='.$sessionId.'&action=delete&idChecked[]='.$course['code'].'" onclick="javascript:if(!confirm(\''.get_lang('ConfirmYourChoice').'\')) return false;">'.Display::return_icon('delete.png', get_lang('Delete')).'</a>
			</td>
		</tr>';
        $count++;
	}
}
?>
</table>
<br />

<?php

$url = Display::url(
    Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL),
    "add_users_to_session.php?page=resume_session.php&id_session=$sessionId"
);
$url .= Display::url(
    Display::return_icon('import_csv.png', get_lang('ImportUsers'), array(), ICON_SIZE_SMALL),
    "session_user_import.php?id_session=$sessionId"
);
echo Display::page_subheader(get_lang('UserList').$url);

$userList = SessionManager::get_users_by_session($sessionId);

if (!empty($userList)) {
    $table = new HTML_Table(array('class' => 'data_table'));

    $table->setHeaderContents(0, 0, get_lang('User'));
    $table->setHeaderContents(0, 1, get_lang('Status'));
    $table->setHeaderContents(0, 2, get_lang('Actions'));

    $row = 1;
    foreach ($userList as $user) {
        $userId = $user['user_id'];
        $userInfo = api_get_user_info($userId);

        $userLink = '<a href="'.api_get_path(WEB_CODE_PATH).'admin/user_information.php?user_id='.$userId.'">'.
            api_htmlentities($userInfo['complete_name_with_username']).'</a>';

        $reportingLink = Display::url(
            Display::return_icon('statistics.gif', get_lang('Reporting')),
            api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?student='.$user['user_id'].''.$orig_param
        );

        $courseUserLink = Display::url(
            Display::return_icon('course.gif', get_lang('BlockCoursesForThisUser')),
            api_get_path(WEB_CODE_PATH).'admin/session_course_user.php?id_user='.$user['user_id'].'&id_session='.$sessionId
        );

        $removeLink = Display::url(
            Display::return_icon('delete.png', get_lang('Delete')),
            api_get_self().'?id_session='.$sessionId.'&action=delete&user='.$user['user_id'],
            array('onclick' => "javascript:if(!confirm(\''.get_lang('ConfirmYourChoice').'\')) return false;")
        );

        $addUserToUrlLink= '';
        if ($multiple_url_is_on) {
            if ($user['access_url_id'] != $url_id) {
                $userLink .= ' '.Display::return_icon(
                    'warning.png',
                    get_lang('UserNotAddedInURL'),
                    array(),
                    ICON_SIZE_SMALL
                );
                $add = Display::return_icon(
                    'add.png',
                    get_lang('AddUsersToURL'),
                    array(),
                    ICON_SIZE_SMALL
                );
                $addUserToUrlLink = '<a href="resume_session.php?action=add_user_to_url&id_session='.$sessionId.'&user_id='.$user['user_id'].'">'.$add.'</a>';
            }
        }

        $editUrl = null;
        if (SessionManager::durationPerUserIsEnabled()) {
            if (isset($sessionInfo['duration']) && !empty($sessionInfo['duration'])) {
                $editUrl = api_get_path(WEB_CODE_PATH) . 'admin/session_user_edit.php?session_id=' . $sessionId . '&user_id=' . $userId;
                $editUrl = Display::url(
                    Display::return_icon('agenda.png', get_lang('SessionDurationEdit')),
                    $editUrl
                );
            }
        }

        $table->setCellContents($row, 0, $userLink);
        $link = $reportingLink.$courseUserLink.$removeLink.$addUserToUrlLink.$editUrl;
        switch ($user['relation_type']) {
            case 1:
                $status = get_lang('Drh');
                $link = Display::url(
                    Display::return_icon('edit.png', get_lang('Edit')),
                    api_get_path(WEB_CODE_PATH).'admin/dashboard_add_sessions_to_user.php?user='.$userId
                );
                break;
            default:
                $status = get_lang('Student');
        }

        $table->setCellContents($row, 1, $status);
        $table->setCellContents($row, 2, $link);
        $row++;
    }
    $table->display();
}

Display :: display_footer();

/*
 ALTER TABLE session_rel_course ADD COLUMN position int;
 ALTER TABLE session_rel_course ADD COLUMN category varchar(255);

 https://task.beeznest.com/issues/8317:

 ALTER TABLE session ADD COLUMN duration int;
 ALTER TABLE session_rel_user ADD COLUMN duration int;
 *
*/

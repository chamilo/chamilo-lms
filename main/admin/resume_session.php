<?php
/* For licensing terms, see /license.txt */
/**
*	@author Bart Mollet, Julio Montoya lot of fixes
*	@package chamilo.admin
*/
/*		INIT SECTION */

// name of the language file that needs to be included
$language_file = 'admin';
$cidReset = true;
require_once '../inc/global.inc.php';

// setting the section (for the tabs)
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script(true);
$tool_name = get_lang('SessionOverview');
$interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array('url' => 'session_list.php','name' => get_lang('SessionList'));

// Database Table Definitions
$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_class				= Database::get_main_table(TABLE_MAIN_SESSION_CLASS);
$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_course							= Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_user							= Database::get_main_table(TABLE_MAIN_USER);
$tbl_session_rel_user				= Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_class							= Database::get_main_table(TABLE_MAIN_CLASS);
$tbl_class_rel_user					= Database::get_main_table(TABLE_MAIN_CLASS_USER);
$tbl_session_category				= Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);

$id_session = (int)$_GET['id_session'];

$sql = 'SELECT name, nbr_courses, nbr_users, nbr_classes, DATE_FORMAT(date_start,"%d-%m-%Y") as date_start, DATE_FORMAT(date_end,"%d-%m-%Y") as date_end, lastname, firstname, username, session_admin_id, nb_days_access_before_beginning, nb_days_access_after_end, session_category_id, visibility
		FROM '.$tbl_session.' LEFT JOIN '.$tbl_user.' ON id_coach = user_id
		WHERE '.$tbl_session.'.id='.$id_session;

$rs = Database::query($sql);
$session = Database::store_result($rs);
$session = $session[0];

if(!api_is_platform_admin() && $session['session_admin_id']!=$_user['user_id']) {
	api_not_allowed(true);
}

$sql = 'SELECT name FROM  '.$tbl_session_category.' WHERE id = "'.intval($session['session_category_id']).'"';
$rs = Database::query($sql);
$session_category = '';
if(Database::num_rows($rs)>0) {
	$rows_session_category = Database::store_result($rs);
	$rows_session_category = $rows_session_category[0];
	$session_category = $rows_session_category['name'];
}

if($_GET['action'] == 'delete') {
	$idChecked = $_GET['idChecked'];
	if(is_array($idChecked)) {
		$my_temp = array();
		foreach ($idChecked as $id){
			$my_temp[]= Database::escape_string($id);// forcing the escape_string
		}
		$idChecked = $my_temp;

		$idChecked="'".implode("','",$idChecked)."'";

		Database::query("DELETE FROM $tbl_session_rel_course WHERE id_session='$id_session' AND course_code IN($idChecked)");

		$nbr_affected_rows=Database::affected_rows();

		Database::query("DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code IN($idChecked)");

		Database::query("UPDATE $tbl_session SET nbr_courses=nbr_courses-$nbr_affected_rows WHERE id='$id_session'");
	}

	if(!empty($_GET['class'])){
		Database::query("DELETE FROM $tbl_session_rel_class WHERE session_id='$id_session' AND class_id=".Database::escape_string($_GET['class']));
		$nbr_affected_rows=Database::affected_rows();
		Database::query("UPDATE $tbl_session SET nbr_classes=nbr_classes-$nbr_affected_rows WHERE id='$id_session'");
	}

	if (!empty($_GET['user'])) {
		Database::query("DELETE FROM $tbl_session_rel_user WHERE relation_type<>".SESSION_RELATION_TYPE_RRHH." AND id_session='$id_session' AND id_user=".intval($_GET['user']));
		$nbr_affected_rows=Database::affected_rows();
		Database::query("UPDATE $tbl_session SET nbr_users=nbr_users-$nbr_affected_rows WHERE id='$id_session'");

		Database::query("DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND id_user=".intval($_GET['user']));
		$nbr_affected_rows=Database::affected_rows();
        
		Database::query("UPDATE $tbl_session_rel_course SET nbr_users=nbr_users-$nbr_affected_rows WHERE id_session='$id_session'");
	}
}

Display::display_header($tool_name);
if (!empty($_GET['warn'])) {
    Display::display_warning_message(urldecode($_GET['warn']));
}

echo Display::tag('h1', Display::return_icon('session.png', get_lang('Session')).' '.$session['name']);
//echo Display::tag('h3', $tool_name);
?>
<!-- General properties -->
<table class="data_table" width="100%">
<tr>
  <th colspan="2"><?php echo get_lang('GeneralProperties'); ?>
  	<a href="session_edit.php?page=resume_session.php&id=<?php echo $id_session; ?>"><?php Display::display_icon('edit.png', get_lang('Edit'), array(), 22); ?></a>
  </th>
</tr>
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
            	//$session['date_start'] = Display::tag('i', get_lang('NoTimeLimits'));
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
		<?php if ($session['visibility']==1) echo get_lang('ReadOnly'); elseif($session['visibility']==2) echo get_lang('Visible');elseif($session['visibility']==3) echo api_ucfirst(get_lang('Invisible'))  ?>
	</td>
</tr>

<?php 
require_once api_get_path(LIBRARY_PATH).'urlmanager.lib.php';

if (api_get_multiple_access_url()) {
    echo '<tr><td>';   
    echo get_lang('URL');    
    echo '</td>';
    echo '<td>';
    $url_list = UrlManager::get_access_url_from_session($id_session);
    foreach($url_list as $url_data) {
        echo $url_data['url'].'<br />';
    }        
    echo '</td></tr>';
}
?>
</table>
<br />
<!--List of courses -->
<table class="data_table" width="100%">
<tr>
  <th colspan="4"><?php echo get_lang('CourseList'); ?>
  	<a href="add_courses_to_session.php?page=resume_session.php&id_session=<?php echo $id_session; ?>"><?php Display::display_icon('edit.png', get_lang('Edit'), array(), 22); ?></a></th>
  </th>
</tr>
<tr>
  <tr>
  <th width="35%"><?php echo get_lang('CourseTitle'); ?></th>
  <th width="30%"><?php echo get_lang('CourseCoach'); ?></th>
  <th width="20%"><?php echo get_lang('UsersNumber'); ?></th>
  <th width="15%"><?php echo get_lang('Actions'); ?></th>
</tr>
</tr>
<?php
if($session['nbr_courses']==0){
	echo '
		<tr>
			<td colspan="4">'.get_lang('NoCoursesForThisSession').'</td>
		</tr>';
}
else {
	// select the courses
	$sql = "SELECT code,title,visual_code, nbr_users
			FROM $tbl_course,$tbl_session_rel_course
			WHERE course_code = code
			AND	id_session='$id_session'
			ORDER BY title";

	$result=Database::query($sql);
	$courses=Database::store_result($result);
	foreach($courses as $course){
		//select the number of users

		$sql = " SELECT count(*) FROM $tbl_session_rel_user sru, $tbl_session_rel_course_rel_user srcru
				WHERE srcru.id_user = sru.id_user AND srcru.id_session = sru.id_session AND srcru.course_code = '".Database::escape_string($course['code'])."'
				AND sru.relation_type<>".SESSION_RELATION_TYPE_RRHH." AND srcru.id_session = '".intval($id_session)."'";

		$rs = Database::query($sql);
		$course['nbr_users'] = Database::result($rs,0,0);

		// Get coachs of the courses in session

		$sql = "SELECT user.lastname,user.firstname,user.username FROM $tbl_session_rel_course_rel_user session_rcru, $tbl_user user
				WHERE session_rcru.id_user = user.user_id AND session_rcru.id_session = '".intval($id_session)."' AND session_rcru.course_code ='".Database::escape_string($course['code'])."' AND session_rcru.status=2";
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

		$orig_param = '&origin=resume_session';
		//hide_course_breadcrumb the parameter has been added to hide the name of the course, that appeared in the default $interbreadcrumb
		echo '
		<tr>
			<td>'.$course['title'].' ('.$course['visual_code'].')</td>
			<td>'.$coach.'</td>
			<td>'.$course['nbr_users'].'</td>
			<td>
                <a href="'.api_get_path(WEB_COURSE_PATH).$course['code'].'/?id_session='.$id_session.'">'.Display::return_icon('course_home.gif', get_lang('Course')).'</a>   
				<a href="../tracking/courseLog.php?id_session='.$id_session.'&cidReq='.$course['code'].$orig_param.'&hide_course_breadcrumb=1">'.Display::return_icon('statistics.gif', get_lang('Tracking')).'</a>&nbsp;                
				<a href="session_course_edit.php?id_session='.$id_session.'&page=resume_session.php&course_code='.$course['code'].''.$orig_param.'">'.Display::return_icon('edit.png', get_lang('Edit'), '', 22).'</a>
				<a href="'.api_get_self().'?id_session='.$id_session.'&action=delete&idChecked[]='.$course['code'].'" onclick="javascript:if(!confirm(\''.get_lang('ConfirmYourChoice').'\')) return false;">'.Display::return_icon('delete.png', get_lang('Delete')).'</a>
			</td>
		</tr>';
	}
}
?>
</table>

<br />

<!--List of courses -->
<table class="data_table" width="100%">
<tr>
  <th colspan="4"><?php echo get_lang('UserList'); ?>
  	<a href="add_users_to_session.php?page=resume_session.php&id_session=<?php echo $id_session; ?>"><?php Display::display_icon('edit.png', get_lang('Edit'), array(), 22); ?></a></th>
  </th>
</tr>
</tr>
<?php
if($session['nbr_users']==0){
	echo '
		<tr>
			<td colspan="2">'.get_lang('NoUsersForThisSession').'</td>
		</tr>';
}
else {
	$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname' : ' ORDER BY lastname, firstname';
	$sql = 'SELECT '.$tbl_user.'.user_id, lastname, firstname, username '.
			' FROM '.$tbl_user.
			' INNER JOIN '.$tbl_session_rel_user.
			' ON '.$tbl_user.'.user_id = '.$tbl_session_rel_user.'.id_user AND '.$tbl_session_rel_user.'.relation_type<>'.SESSION_RELATION_TYPE_RRHH.
			' AND '.$tbl_session_rel_user.'.id_session = '.$id_session.$order_clause;

	$result=Database::query($sql);
	$users=Database::store_result($result);
	$orig_param = '&origin=resume_session&id_session='.$id_session; // change breadcrumb in destination page
	foreach($users as $user){
        $user_link = '';
        if (!empty($user['user_id'])) {
            $user_link = '<a href="'.api_get_path(WEB_CODE_PATH).'admin/user_information.php?user_id='.intval($user['user_id']).'">'.api_htmlentities(api_get_person_name($user['firstname'], $user['lastname']),ENT_QUOTES,$charset).' ('.$user['username'].')</a>';
        }
		echo '<tr>
					<td width="90%">
						<b>'.$user_link.'</b>
					</td>
					<td>
						<a href="../mySpace/myStudents.php?student='.$user['user_id'].''.$orig_param.'">'.Display::return_icon('statistics.gif', get_lang('Reporting')).'</a>&nbsp;
						<a href="session_course_user.php?id_user='.$user['user_id'].'&id_session='.$id_session.'">'.Display::return_icon('course.gif', get_lang('BlockCoursesForThisUser')).'</a>&nbsp;
						<a href="'.api_get_self().'?id_session='.$id_session.'&action=delete&user='.$user['user_id'].'" onclick="javascript:if(!confirm(\''.get_lang('ConfirmYourChoice').'\')) return false;">'.Display::return_icon('delete.png', get_lang('Delete')).'</a>
					</td>
				  </tr>';
	}
}
?>
</table>
<?php
// footer
Display :: display_footer();
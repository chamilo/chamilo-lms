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
$this_section = SECTION_PLATFORM_ADMIN;

$id_session = (int)$_GET['id_session'];

SessionManager::protect_session_edit($id_session);

$tool_name = get_lang('SessionOverview');

$interbreadcrumb[] = array('url' => 'index.php','name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'session_list.php','name' => get_lang('SessionList'));

$session = SessionManager::get_session_info($id_session);
$session_cat_info = SessionManager::get_session_category($session['session_category_id']);
$session_category = null;
if (!empty($session_cat_info)) {
    $session_category = $session_cat_info['name'];
}

$action = $_GET['action'];

$url_id = api_get_current_access_url_id();     

switch ($action) {
    case 'add_user_to_url':        
        $user_id = $_REQUEST['user_id'];
        $result = UrlManager::add_user_to_url($user_id, $url_id);
        $user_info = api_get_user_info($user_id);
        if ($result) {
            $message = Display::return_message(get_lang('UserAdded').' '.api_get_person_name($user_info['firstname'], $user_info['lastname']), 'confirm');
        }
        break;
    case 'delete':
        if (isset($_GET['course_code_to_delete'])) {
            SessionManager::delete_course_in_session($id_session, $_GET['course_code_to_delete']);
        }
        if (!empty($_GET['user'])) {
            SessionManager::unsubscribe_user_from_session($id_session, $_GET['user']);            
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

echo Display::page_header(Display::return_icon('session.png', get_lang('Session')).' '.$session['name']);

$url = Display::url(Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL), "session_edit.php?page=resume_session.php&id=$id_session");
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
		<?php        
        if (isset($session['date_end']) && $session['date_end'] != '00-00-0000') {
            if ($session['visibility'] == 1) 
                echo get_lang('ReadOnly'); 
             elseif($session['visibility'] == 2) 
                 echo get_lang('Visible');
             elseif($session['visibility'] == 3) 
                echo api_ucfirst(get_lang('Invisible'))  ;
        } else {
            //By default course sessions can be access normally see function api_get_session_visibility() when no date_end is proposed            
            echo get_lang('Visible'); 
        }
        ?>
	</td>
</tr>

<?php 

$multiple_url_is_on = api_is_multiple_url_enabled();

if ($multiple_url_is_on) {
    echo '<tr><td>';   
    echo 'URL';    
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

<?php

$url = Display::url(Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL), "add_courses_to_session.php?page=resume_session.php&id_session=$id_session");
echo Display::page_subheader(get_lang('CourseList').$url);

?>

<!--List of courses -->
<table class="data_table">
<tr>
  <th width="35%"><?php echo get_lang('CourseTitle'); ?></th>
  <th width="30%"><?php echo get_lang('CourseCoach'); ?></th>
  <th width="20%"><?php echo get_lang('UsersNumber'); ?></th>
  <th width="15%"><?php echo get_lang('Actions'); ?></th>
</tr>
<?php
if ($session['nbr_courses'] == 0){
	echo '<tr>
			<td colspan="4">'.get_lang('NoCoursesForThisSession').'</td>
		</tr>';
} else {    
    $courses = SessionManager::get_course_list_by_session_id($id_session);	
	foreach ($courses as $course) {
        $count_users = SessionManager::get_count_users_in_course_session($course['code'], $id_session);
        $coaches = SessionManager::get_session_course_coaches_to_string($course['code'], $id_session);

		$orig_param = '&origin=resume_session';
		//hide_course_breadcrumb the parameter has been added to hide the name of the course, that appeared in the default $interbreadcrumb
		echo '
		<tr>
			<td>'.Display::url($course['title'].' ('.$course['visual_code'].')', api_get_path(WEB_COURSE_PATH).$course['code'].'/?id_session='.$id_session),'</td>
			<td>'.$coaches.'</td>
			<td>'.$count_users.'</td>
			<td>
                <a href="'.api_get_path(WEB_COURSE_PATH).$course['code'].'/?id_session='.$id_session.'">'.Display::return_icon('course_home.gif', get_lang('Course')).'</a>   
                <a href="session_course_user_list.php?id_session='.$id_session.'&course_code='.$course['code'].'">'.Display::return_icon('user.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>
                <a href="'.api_get_path(WEB_CODE_PATH).'/user/user_import.php?action=import&cidReq='.$course['code'].'&id_session='.$id_session.'">'.Display::return_icon('import_csv.png', get_lang('ImportUsersToACourse'), null, ICON_SIZE_SMALL).'</a>   
				<a href="../tracking/courseLog.php?id_session='.$id_session.'&cidReq='.$course['code'].$orig_param.'&hide_course_breadcrumb=1">'.Display::return_icon('statistics.gif', get_lang('Tracking')).'</a>&nbsp;                
				<a href="session_course_edit.php?id_session='.$id_session.'&page=resume_session.php&course_code='.$course['code'].''.$orig_param.'">'.Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>
				<a href="'.api_get_self().'?id_session='.$id_session.'&action=delete&course_code_to_delete='.$course['code'].'" onclick="javascript:if(!confirm(\''.get_lang('ConfirmYourChoice').'\')) return false;">'.Display::return_icon('delete.png', get_lang('Delete')).'</a>
			</td>
		</tr>';
	}
}
?>
</table>
<br />

<?php

$url = Display::url(Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL), "add_users_to_session.php?page=resume_session.php&id_session=$id_session");
$url .= Display::url(Display::return_icon('import_csv.png', get_lang('ImportUsers'), array(), ICON_SIZE_SMALL), "session_user_import.php?id_session=$id_session");
echo Display::page_subheader(get_lang('UserList').$url);

?>

<!--List of users -->

<table class="data_table">
    <tr>
        <th>
            <?php echo get_lang('User'); ?>
        </th>
        <th>
            <?php echo get_lang('Information'); ?>
        </th>
        <th>
            <?php echo get_lang('MovedAt'); ?>
        </th>
        <th>
            <?php echo get_lang('Destination'); ?>
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
	/*$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname' : ' ORDER BY lastname, firstname';
     *     
    if ($multiple_url_is_on) {           
        $sql = "SELECT u.user_id, lastname, firstname, username, access_url_id
                FROM $tbl_user u
                INNER JOIN $tbl_session_rel_user su
                ON u.user_id = su.id_user AND su.relation_type<>".SESSION_RELATION_TYPE_RRHH."
                LEFT OUTER JOIN $table_access_url_user uu ON (uu.user_id = u.user_id)
                WHERE su.id_session = $id_session AND (access_url_id = $url_id OR access_url_id is null )
                $order_clause";
    } else {
        $sql = "SELECT u.user_id, lastname, firstname, username
                FROM $tbl_user u
                INNER JOIN $tbl_session_rel_user su
                ON u.user_id = su.id_user AND su.relation_type<>".SESSION_RELATION_TYPE_RRHH."
                AND su.id_session = ".$id_session.$order_clause;
    }

	$result = Database::query($sql);
	$users  = Database::store_result($result);*/
	$orig_param = '&origin=resume_session&id_session='.$id_session; // change breadcrumb in destination page
    
    $users = SessionManager::get_users_by_session($id_session, 0);    
    $reasons = SessionManager::get_session_change_user_reasons();
    
    if (!empty($users))
	foreach ($users as $user) {        
        $user_link = '';
        if (!empty($user['user_id'])) {
            $user_link = '<a href="'.api_get_path(WEB_CODE_PATH).'admin/user_information.php?user_id='.intval($user['user_id']).'">'.api_htmlentities(api_get_person_name($user['firstname'], $user['lastname']),ENT_QUOTES,$charset).' ('.$user['username'].')</a>';
        }
        $information = null;
        $origin_destination = null;
        
        $row_class = null;
        $moved_date = '-';
        
        $moved_link =  '<a href="change_user_session.php?user_id='.$user['user_id'].'&id_session='.$id_session.'">'.Display::return_icon('move.png', get_lang('ChangeUserSession')).'</a>&nbsp;';
        
        if (isset($user['moved_to']) && !empty($user['moved_to']) || $user['moved_status'] == SessionManager::SESSION_CHANGE_USER_REASON_ENROLLMENT_ANNULATION) {
            $information = $reasons[$user['moved_status']];
            
            $moved_date = isset($user['moved_at']) && $user['moved_at'] != '0000-00-00 00:00:00' ? api_get_local_time($user['moved_at']) : '-';                        
            $session_info = SessionManager::fetch($user['moved_to']);
            
            if ($session_info) {
                $url = api_get_path(WEB_CODE_PATH).'admin/resume_session.php?id_session='.$session_info['id'];
                $origin_destination = Display::url($session_info['name'], $url);
            }
            $row_class = 'row_odd';
            
            $moved_link =  Display::return_icon('move_na.png', get_lang('ChangeUserSession')).'&nbsp;';
        }
        
        $link_to_add_user_in_url = '';
        
        if ($multiple_url_is_on) {
            if ($user['access_url_id'] != $url_id) {            
                $user_link .= ' '.Display::return_icon('warning.png', get_lang('UserNotAddedInURL'), array(), ICON_SIZE_SMALL);
                $add = Display::return_icon('add.png', get_lang('AddUsersToURL'), array(), ICON_SIZE_SMALL);
                $link_to_add_user_in_url = '<a href="resume_session.php?action=add_user_to_url&id_session='.$id_session.'&user_id='.$user['user_id'].'">'.$add.'</a>';
            }                
        }
		echo '<tr class="'.$row_class.'">
                <td width="50%">
                    '.$user_link.'
                </td>
                <td>'.$information.'</td>
                <td>'.$moved_date.'</td>                    
                <td>'.$origin_destination.'</td>
                <td>
                    <a href="../mySpace/myStudents.php?student='.$user['user_id'].''.$orig_param.'">'.Display::return_icon('statistics.gif', get_lang('Reporting')).'</a>&nbsp;
                    <a href="session_course_user.php?id_user='.$user['user_id'].'&id_session='.$id_session.'">'.Display::return_icon('course.gif', get_lang('BlockCoursesForThisUser')).'</a>&nbsp;
                    '.$moved_link.'
                    <a href="'.api_get_self().'?id_session='.$id_session.'&action=delete&user='.$user['user_id'].'" onclick="javascript:if(!confirm(\''.get_lang('ConfirmYourChoice').'\')) return false;">'.Display::return_icon('delete.png', get_lang('Delete')).'</a>
                    '.$link_to_add_user_in_url.'
                </td>
                </tr>';
	}
}
?>
</table>
<?php
Display :: display_footer();
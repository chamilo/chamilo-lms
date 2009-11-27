<?php
/* For licensing terms, see /dokeos_license.txt */

// name of the language file that needs to be included
$language_file = array ('index', 'chat', 'tracking');

include_once './main/inc/global.inc.php';
api_block_anonymous_users();

$tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);
$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

/*
-----------------------------------------------------------
	Header
	include the HTTP, HTML headers plus the top banner
-----------------------------------------------------------
*/

Display::display_header(get_lang('UserOnlineListSession'));
?>
<br /><br />
<table class="data_table" width="60%">
	<tr class="tableName">
		<td colspan="4">
			<strong><?php echo get_lang('UserOnlineListSession'); ?></strong>
		</td>
	</tr>
	<tr>
		<th>
			<?php echo get_lang('Name'); ?>
		</th>
		<th>
			<?php echo get_lang('InCourse'); ?>
		</th>
		<th>
			<?php echo get_lang('Email'); ?>
		</th>
		<th>
			<?php echo get_lang('Chat'); ?>
		</th>
	</tr>
<?php
	$session_is_coach = array();
	if (isset($_user['user_id']) && $_user['user_id'] != '') {
		$_user['user_id'] = intval($_user['user_id']);
		$result = Database::query("SELECT DISTINCT id,
										name,
										date_start,
										date_end
									FROM $tbl_session as session
									INNER JOIN $tbl_session_course_user as srcru
										ON srcru.id_user = ".$_user['user_id']." AND srcru.status=2
										AND session.id = srcru.id_session
									ORDER BY date_start, date_end, name",__FILE__,__LINE__);

		while ($session = Database:: fetch_array($result)) {
			$session_is_coach[$session['id']] = $session;
		}

		$result = Database::query("SELECT DISTINCT id,
										name,
										date_start,
										date_end
								FROM $tbl_session as session
								WHERE session.id_coach = ".$_user['user_id']."
								ORDER BY date_start, date_end, name",__FILE__,__LINE__);
		while ($session = Database:: fetch_array($result)) {
			$session_is_coach[$session['id']] = $session;
		}

		$students_online = array();
		foreach ($session_is_coach as $session) {
			$sql = "SELECT 	DISTINCT last_access.access_user_id,
							last_access.access_date,
							last_access.access_cours_code,
							last_access.access_session_id,
							".(api_is_western_name_order() ? "CONCAT(user.firstname,' ',user.lastname)" : "CONCAT(user.lastname,' ',user.firstname)")." as name,
							user.email
					FROM ".Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS)." AS last_access
					INNER JOIN ".Database::get_main_table(TABLE_MAIN_USER)." AS user
						ON user.user_id = last_access.access_user_id
					WHERE access_session_id='".$session['id']."'
					AND NOW()-access_date<1000 GROUP BY access_user_id";

			$result = Database::query($sql, __FILE__, __LINE__);

			while($user_list = Database::fetch_array($result)) {
				$students_online[$user_list['access_user_id']] = $user_list;
			}
		}

		if (count($students_online) > 0) {
			foreach ($students_online as $student_online) {
				echo "<tr>
						<td>
					";
				echo $student_online['name'];
				echo "	</td>
						<td align='center'>
					 ";
				echo $student_online['access_cours_code'];
				echo "	</td>
						<td align='center'>
					 ";
							 if (!empty($student_online['email'])) {
								echo $student_online['email'];
							 } else {
							 	echo get_lang('NoEmail');
							 }
				echo "	</td>
						<td align='center'>
					 ";
				echo '<a href="main/chat/chat.php?cidReq='.$student_online['access_cours_code'].'&id_session='.$student_online['access_session_id'].'"> -> </a>';
				echo "	</td>
					</tr>
					 ";
			}
		} else {
			echo '	<tr>
						<td colspan="4">
							'.get_lang('NoOnlineStudents').'
						</td>
					</tr>
				 ';
		}
	}
?>
</table>
<?php

/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();

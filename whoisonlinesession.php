<?php
// Dokeos Header here

/**
 * @todo variables are sometimes in cammelcase, or even worse a mixture of CammelCase and udnerscoring: $a_userList
 * 
 */
 
// name of the language file that needs to be included 
$language_file = array ('index', 'chat', 'tracking');

include_once("./main/inc/global.inc.php");
api_block_anonymous_users();


$tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);
$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);

/*
-----------------------------------------------------------
	Header
	include the HTTP, HTML headers plus the top banner
-----------------------------------------------------------
*/

Display::display_header(get_lang('UserOnlineListSession'));
?>
<br/><br/>
<table class="data_table" width="60%">
	<tr class="tableName">
		<td colspan="4">
			<strong><?php echo get_lang('UserOnlineListSession'); ?></strong>
		</td>
	</tr>
	<tr>
		<th class="head">
			<?php echo get_lang('Name'); ?>
		</th>
		<th class="head">
			<?php echo get_lang('InCourse'); ?>
		</th>
		<th class="head">
			<?php echo get_lang('Email'); ?>
		</th>
		<th class="head">
			<?php echo get_lang('Chat'); ?>
		</th>
	</tr>
<?php
	$sessionIsCoach = array();
	if (isset($_user['user_id']) && $_user['user_id']!='') {
		$result = api_sql_query("SELECT DISTINCT id, 
										name, 
										date_start, 
										date_end 
									FROM $tbl_session as session 
									INNER JOIN $tbl_session_course as session_rel_course
										ON session_rel_course.id_coach = ".$_user['user_id']."
										AND session.id = session_rel_course.id_session
									ORDER BY date_start, date_end, name",__FILE__,__LINE__);
		
		while ($session = Database:: fetch_array($result)) {
			$sessionIsCoach[$session['id']] = $session;
		}
		
		$result = api_sql_query("SELECT DISTINCT id, 
										name, 
										date_start, 
										date_end 
								FROM $tbl_session as session 
								WHERE session.id_coach = ".$_user['user_id']."
								ORDER BY date_start, date_end, name",__FILE__,__LINE__);
		while ($session = Database:: fetch_array($result)) {
			$sessionIsCoach[$session['id']] = $session;
		}
			
		foreach($sessionIsCoach as $session) {
			$sql = "SELECT 	DISTINCT last_access.access_user_id, 
							last_access.access_date, 
							last_access.access_cours_code,
							last_access.access_session_id,
							CONCAT(user.lastname,' ',user.firstname) as name,
							user.email
					FROM ".Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS)." AS last_access
					INNER JOIN ".Database::get_main_table(TABLE_MAIN_USER)." AS user
						ON user.user_id = last_access.access_user_id
					WHERE access_session_id='".$session['id']."' 
					AND NOW()-access_date<1000 GROUP BY access_user_id";
			
			$result = api_sql_query($sql,__FILE__,__LINE__);
			
			while($a_userList = mysql_fetch_array($result)) {
				$a_onlineStudent[$a_userList['access_user_id']] = $a_userList;
			}
		}
		
		if(count($a_onlineStudent)>0) {
			foreach($a_onlineStudent as $onlineStudent) {
				echo "<tr>
						<td>
					";
				echo		$onlineStudent['name'];
				echo "	</td>
						<td align='center'>
					 ";
				echo		$onlineStudent['access_cours_code'];
				echo "	</td>
						<td align='center'>
					 ";
							 if(!empty($onlineStudent['email']))
							 {
								echo $onlineStudent['email'];
							 }
							 else
							 {
							 	echo get_lang('NoEmail');
							 }	 
				echo "	</td>
						<td align='center'>
					 ";
				echo '<a href="main/chat/chat.php?cidReq='.$onlineStudent['access_cours_code'].'&id_session='.$onlineStudent['access_session_id'].'"> -> </a>';
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
?>
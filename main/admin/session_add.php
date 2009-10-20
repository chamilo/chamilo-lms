<?php
/* For licensing terms, see /dokeos_license.txt */

/**
==============================================================================
*	@package dokeos.admin
* 	@todo use formvalidator for the form
==============================================================================
*/

// name of the language file that needs to be included
$language_file='admin';

$cidReset=true;

// including the global Dokeos file
require_once('../inc/global.inc.php');

// including additional libraries
require_once(api_get_path(LIBRARY_PATH).'sessionmanager.lib.php');
require_once ('../inc/lib/xajax/xajax.inc.php');
$xajax = new xajax();
//$xajax->debugOn();
$xajax -> registerFunction ('search_coachs');

// setting the section (for the tabs)
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script(true);

$formSent=0;
$errorMsg='';

$interbreadcrumb[]=array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array('url' => "session_list.php","name" => get_lang('SessionList'));

// Database Table Definitions
$tbl_user		= Database::get_main_table(TABLE_MAIN_USER);
$tbl_session	= Database::get_main_table(TABLE_MAIN_SESSION);

function search_coachs($needle)
{
	global $tbl_user;

	$xajax_response = new XajaxResponse();
	$return = '';

	if(!empty($needle))
	{
		// xajax send utf8 datas... datas in db can be non-utf8 datas
		$charset = api_get_setting('platform_charset');
		$needle = api_convert_encoding($needle, $charset, 'utf-8');

		$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';

		// search users where username or firstname or lastname begins likes $needle
		$sql = 'SELECT username, lastname, firstname FROM '.$tbl_user.' user
				WHERE (username LIKE "'.$needle.'%"
				OR firstname LIKE "'.$needle.'%"
				OR lastname LIKE "'.$needle.'%")
				AND status=1'.
				$order_clause.
				' LIMIT 10';

		global $_configuration;
		if ($_configuration['multiple_access_urls']==true) {
			$tbl_user_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1){

				$sql = 'SELECT username, lastname, firstname FROM '.$tbl_user.' user
				INNER JOIN '.$tbl_user_rel_access_url.' url_user ON (url_user.user_id=user.user_id)
				WHERE access_url_id = '.$access_url_id.'  AND (username LIKE "'.$needle.'%"
				OR firstname LIKE "'.$needle.'%"
				OR lastname LIKE "'.$needle.'%")
				AND status=1'.
				$order_clause.
				' LIMIT 10';

			}
		}

		$rs = Database::query($sql, __FILE__, __LINE__);
		while ($user = Database :: fetch_array($rs)) {
			$return .= '<a href="javascript: void(0);" onclick="javascript: fill_coach_field(\''.$user['username'].'\')">'.api_get_person_name($user['firstname'], $user['lastname']).' ('.$user['username'].')</a><br />';
		}
	}
	$xajax_response -> addAssign('ajax_list_coachs','innerHTML', api_utf8_encode($return));
	return $xajax_response;
}
$xajax -> processRequests();

$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');

$htmlHeadXtra[] = '
<script type="text/javascript">
function fill_coach_field (username) {

	document.getElementById("coach_username").value = username;
	document.getElementById("ajax_list_coachs").innerHTML = "";

}
</script>';


if ($_POST['formSent']) {
	$formSent=1;
	$name= $_POST['name'];
	$year_start= $_POST['year_start'];
	$month_start=$_POST['month_start'];
	$day_start=$_POST['day_start'];
	$year_end=$_POST['year_end'];
	$month_end=$_POST['month_end'];
	$day_end=$_POST['day_end'];
	$nb_days_acess_before = $_POST['nb_days_acess_before'];
	$nb_days_acess_after = $_POST['nb_days_acess_after'];
	$nolimit=$_POST['nolimit'];
	$coach_username=$_POST['coach_username'];
	$id_session_category = $_POST['session_category'];
	$id_visibility = $_POST['session_visibility'];
	$return = SessionManager::create_session($name,$year_start,$month_start,$day_start,$year_end,$month_end,$day_end,$nb_days_acess_before,$nb_days_acess_after,$nolimit,$coach_username, $id_session_category,$id_visibility);
	if ($return == strval(intval($return))) {
		// integer => no error on session creation
		header('Location: add_courses_to_session.php?id_session='.$return.'&add=true&msg=');
		exit();
	}
}

$nb_days_acess_before = 0;
$nb_days_acess_after = 0;

$thisYear=date('Y');
$thisMonth=date('m');
$thisDay=date('d');


$tool_name = get_lang('AddSession');

//display the header
Display::display_header($tool_name);

// display the tool title
// api_display_tool_title($tool_name);


if (!empty($return)) {
	Display::display_error_message($return,false);
}
?>
<form method="post" name="form" action="<?php echo api_get_self(); ?>" style="margin:0px;">
<input type="hidden" name="formSent" value="1">
<div class="row"><div class="form_header"><?php echo $tool_name; ?></div></div>
<table border="0" cellpadding="5" cellspacing="0" width="650">
<tr>
  <td width="40%"><?php echo get_lang('SessionName') ?>&nbsp;&nbsp;</td>
  <td width="60%"><input type="text" name="name" size="50" maxlength="50" value="<?php if($formSent) echo api_htmlentities($name,ENT_QUOTES,$charset); ?>"></td>
</tr>
<tr>
  <td width="40%"><?php echo get_lang('CoachName') ?>&nbsp;&nbsp;</td>
  <td width="60%">
<?php

$sql = 'SELECT COUNT(1) FROM '.$tbl_user.' WHERE status=1';
$rs = Database::query($sql, __FILE__, __LINE__);
$count_users = Database::result($rs, 0, 0);

if (intval($count_users)<50) {
	$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';
	$sql="SELECT user_id,lastname,firstname,username FROM $tbl_user WHERE status='1'".$order_clause;
	global $_configuration;
	if ($_configuration['multiple_access_urls']==true) {
		$tbl_user_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
		$access_url_id = api_get_current_access_url_id();
		if ($access_url_id != -1){
			$sql = 'SELECT username, lastname, firstname FROM '.$tbl_user.' user
			INNER JOIN '.$tbl_user_rel_access_url.' url_user ON (url_user.user_id=user.user_id)
			WHERE access_url_id = '.$access_url_id.'  AND status=1'.$order_clause;
		}
	}

	$result=Database::query($sql,__FILE__,__LINE__);
	$Coaches=Database::store_result($result);
	?>
	<select name="coach_username" value="true" style="width:250px;">
		<option value="0"><?php get_lang('None'); ?></option>
		<?php foreach($Coaches as $enreg): ?>
		<option value="<?php echo $enreg['username']; ?>" <?php if($sent && $enreg['user_id'] == $id_coach) echo 'selected="selected"'; ?>><?php echo api_get_person_name($enreg['firstname'], $enreg['lastname']).' ('.$enreg['username'].')'; ?></option>
		<?php endforeach; ?>
	</select>
	<?php
} else {
	?>
	<input type="text" name="coach_username" id="coach_username" onkeyup="xajax_search_coachs(document.getElementById('coach_username').value)" /><div id="ajax_list_coachs"></div>
	<?php
}
?>


</td>
</tr>
<?php
	$id_session_category = '';
	$tbl_session_category = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
	$sql = 'SELECT id, name FROM '.$tbl_session_category.' ORDER BY name ASC';
	$result = Database::query($sql,__FILE__,__LINE__);
	$Categories = Database::store_result($result);
?>
<tr>
  <td width="40%"><?php echo get_lang('SessionCategory') ?></td>
  <td width="60%">
  	<select name="session_category" value="true" style="width:250px;">
		<option value="0"><?php get_lang('None'); ?></option>
		<?php foreach($Categories as $Rows): ?>
		<option value="<?php echo $Rows['id']; ?>" <?php if($Rows['id'] == $id_session_category) echo 'selected="selected"'; ?>><?php echo $Rows['name']; ?></option>
		<?php endforeach; ?>
	</select>
  </td>
</tr>
<tr>
  <td width="40%"><?php echo get_lang('NoTimeLimits') ?></td>
  <td width="60%">
  	<input type="checkbox" name="nolimit" onChange="setDisable(this)" />
  </td>
<tr>
  <td width="40%"><?php echo get_lang('DateStartSession') ?>&nbsp;&nbsp;</td>
  <td width="60%">
  <select name="day_start">
	<option value="1">01</option>
	<option value="2" <?php if((!$formSent && $thisDay == 2) || ($formSent && $day_start == 2)) echo 'selected="selected"'; ?> >02</option>
	<option value="3" <?php if((!$formSent && $thisDay == 3) || ($formSent && $day_start == 3)) echo 'selected="selected"'; ?> >03</option>
	<option value="4" <?php if((!$formSent && $thisDay == 4) || ($formSent && $day_start == 4)) echo 'selected="selected"'; ?> >04</option>
	<option value="5" <?php if((!$formSent && $thisDay == 5) || ($formSent && $day_start == 5)) echo 'selected="selected"'; ?> >05</option>
	<option value="6" <?php if((!$formSent && $thisDay == 6) || ($formSent && $day_start == 6)) echo 'selected="selected"'; ?> >06</option>
	<option value="7" <?php if((!$formSent && $thisDay == 7) || ($formSent && $day_start == 7)) echo 'selected="selected"'; ?> >07</option>
	<option value="8" <?php if((!$formSent && $thisDay == 8) || ($formSent && $day_start == 8)) echo 'selected="selected"'; ?> >08</option>
	<option value="9" <?php if((!$formSent && $thisDay == 9) || ($formSent && $day_start == 9)) echo 'selected="selected"'; ?> >09</option>
	<option value="10" <?php if((!$formSent && $thisDay == 10) || ($formSent && $day_start == 10)) echo 'selected="selected"'; ?> >10</option>
	<option value="11" <?php if((!$formSent && $thisDay == 11) || ($formSent && $day_start == 11)) echo 'selected="selected"'; ?> >11</option>
	<option value="12" <?php if((!$formSent && $thisDay == 12) || ($formSent && $day_start == 12)) echo 'selected="selected"'; ?> >12</option>
	<option value="13" <?php if((!$formSent && $thisDay == 13) || ($formSent && $day_start == 13)) echo 'selected="selected"'; ?> >13</option>
	<option value="14" <?php if((!$formSent && $thisDay == 14) || ($formSent && $day_start == 14)) echo 'selected="selected"'; ?> >14</option>
	<option value="15" <?php if((!$formSent && $thisDay == 15) || ($formSent && $day_start == 15)) echo 'selected="selected"'; ?> >15</option>
	<option value="16" <?php if((!$formSent && $thisDay == 16) || ($formSent && $day_start == 16)) echo 'selected="selected"'; ?> >16</option>
	<option value="17" <?php if((!$formSent && $thisDay == 17) || ($formSent && $day_start == 17)) echo 'selected="selected"'; ?> >17</option>
	<option value="18" <?php if((!$formSent && $thisDay == 18) || ($formSent && $day_start == 18)) echo 'selected="selected"'; ?> >18</option>
	<option value="19" <?php if((!$formSent && $thisDay == 19) || ($formSent && $day_start == 19)) echo 'selected="selected"'; ?> >19</option>
	<option value="20" <?php if((!$formSent && $thisDay == 20) || ($formSent && $day_start == 20)) echo 'selected="selected"'; ?> >20</option>
	<option value="21" <?php if((!$formSent && $thisDay == 21) || ($formSent && $day_start == 21)) echo 'selected="selected"'; ?> >21</option>
	<option value="22" <?php if((!$formSent && $thisDay == 22) || ($formSent && $day_start == 22)) echo 'selected="selected"'; ?> >22</option>
	<option value="23" <?php if((!$formSent && $thisDay == 23) || ($formSent && $day_start == 23)) echo 'selected="selected"'; ?> >23</option>
	<option value="24" <?php if((!$formSent && $thisDay == 24) || ($formSent && $day_start == 24)) echo 'selected="selected"'; ?> >24</option>
	<option value="25" <?php if((!$formSent && $thisDay == 25) || ($formSent && $day_start == 25)) echo 'selected="selected"'; ?> >25</option>
	<option value="26" <?php if((!$formSent && $thisDay == 26) || ($formSent && $day_start == 26)) echo 'selected="selected"'; ?> >26</option>
	<option value="27" <?php if((!$formSent && $thisDay == 27) || ($formSent && $day_start == 27)) echo 'selected="selected"'; ?> >27</option>
	<option value="28" <?php if((!$formSent && $thisDay == 28) || ($formSent && $day_start == 28)) echo 'selected="selected"'; ?> >28</option>
	<option value="29" <?php if((!$formSent && $thisDay == 29) || ($formSent && $day_start == 29)) echo 'selected="selected"'; ?> >29</option>
	<option value="30" <?php if((!$formSent && $thisDay == 30) || ($formSent && $day_start == 30)) echo 'selected="selected"'; ?> >30</option>
	<option value="31" <?php if((!$formSent && $thisDay == 31) || ($formSent && $day_start == 31)) echo 'selected="selected"'; ?> >31</option>
  </select>
  /
  <select name="month_start">
	<option value="1">01</option>
	<option value="2" <?php if((!$formSent && $thisMonth == 2) || ($formSent && $month_start == 2)) echo 'selected="selected"'; ?> >02</option>
	<option value="3" <?php if((!$formSent && $thisMonth == 3) || ($formSent && $month_start == 3)) echo 'selected="selected"'; ?> >03</option>
	<option value="4" <?php if((!$formSent && $thisMonth == 4) || ($formSent && $month_start == 4)) echo 'selected="selected"'; ?> >04</option>
	<option value="5" <?php if((!$formSent && $thisMonth == 5) || ($formSent && $month_start == 5)) echo 'selected="selected"'; ?> >05</option>
	<option value="6" <?php if((!$formSent && $thisMonth == 6) || ($formSent && $month_start == 6)) echo 'selected="selected"'; ?> >06</option>
	<option value="7" <?php if((!$formSent && $thisMonth == 7) || ($formSent && $month_start == 7)) echo 'selected="selected"'; ?> >07</option>
	<option value="8" <?php if((!$formSent && $thisMonth == 8) || ($formSent && $month_start == 8)) echo 'selected="selected"'; ?> >08</option>
	<option value="9" <?php if((!$formSent && $thisMonth == 9) || ($formSent && $month_start == 9)) echo 'selected="selected"'; ?> >09</option>
	<option value="10" <?php if((!$formSent && $thisMonth == 10) || ($formSent && $month_start == 10)) echo 'selected="selected"'; ?> >10</option>
	<option value="11" <?php if((!$formSent && $thisMonth == 11) || ($formSent && $month_start == 11)) echo 'selected="selected"'; ?> >11</option>
	<option value="12" <?php if((!$formSent && $thisMonth == 12) || ($formSent && $month_start == 12)) echo 'selected="selected"'; ?> >12</option>
  </select>
  /
  <select name="year_start">

<?php
for ($i=$thisYear-5;$i <= ($thisYear+5);$i++) {
?>
	<option value="<?php echo $i; ?>" <?php if((!$formSent && $thisYear == $i) || ($formSent && $year_start == $i)) echo 'selected="selected"'; ?> ><?php echo $i; ?></option>
<?php
}
?>

  </select>
  </td>
</tr>
<tr>
  <td width="40%"><?php echo get_lang('DateEndSession') ?>&nbsp;&nbsp;</td>
  <td width="60%">
  <select name="day_end">
	<option value="1">01</option>
	<option value="2" <?php if((!$formSent && $thisDay == 2) || ($formSent && $day_end == 2)) echo 'selected="selected"'; ?> >02</option>
	<option value="3" <?php if((!$formSent && $thisDay == 3) || ($formSent && $day_end == 3)) echo 'selected="selected"'; ?> >03</option>
	<option value="4" <?php if((!$formSent && $thisDay == 4) || ($formSent && $day_end == 4)) echo 'selected="selected"'; ?> >04</option>
	<option value="5" <?php if((!$formSent && $thisDay == 5) || ($formSent && $day_end == 5)) echo 'selected="selected"'; ?> >05</option>
	<option value="6" <?php if((!$formSent && $thisDay == 6) || ($formSent && $day_end == 6)) echo 'selected="selected"'; ?> >06</option>
	<option value="7" <?php if((!$formSent && $thisDay == 7) || ($formSent && $day_end == 7)) echo 'selected="selected"'; ?> >07</option>
	<option value="8" <?php if((!$formSent && $thisDay == 8) || ($formSent && $day_end == 8)) echo 'selected="selected"'; ?> >08</option>
	<option value="9" <?php if((!$formSent && $thisDay == 9) || ($formSent && $day_end == 9)) echo 'selected="selected"'; ?> >09</option>
	<option value="10" <?php if((!$formSent && $thisDay == 10) || ($formSent && $day_end == 10)) echo 'selected="selected"'; ?> >10</option>
	<option value="11" <?php if((!$formSent && $thisDay == 11) || ($formSent && $day_end == 11)) echo 'selected="selected"'; ?> >11</option>
	<option value="12" <?php if((!$formSent && $thisDay == 12) || ($formSent && $day_end == 12)) echo 'selected="selected"'; ?> >12</option>
	<option value="13" <?php if((!$formSent && $thisDay == 13) || ($formSent && $day_end == 13)) echo 'selected="selected"'; ?> >13</option>
	<option value="14" <?php if((!$formSent && $thisDay == 14) || ($formSent && $day_end == 14)) echo 'selected="selected"'; ?> >14</option>
	<option value="15" <?php if((!$formSent && $thisDay == 15) || ($formSent && $day_end == 15)) echo 'selected="selected"'; ?> >15</option>
	<option value="16" <?php if((!$formSent && $thisDay == 16) || ($formSent && $day_end == 16)) echo 'selected="selected"'; ?> >16</option>
	<option value="17" <?php if((!$formSent && $thisDay == 17) || ($formSent && $day_end == 17)) echo 'selected="selected"'; ?> >17</option>
	<option value="18" <?php if((!$formSent && $thisDay == 18) || ($formSent && $day_end == 18)) echo 'selected="selected"'; ?> >18</option>
	<option value="19" <?php if((!$formSent && $thisDay == 19) || ($formSent && $day_end == 19)) echo 'selected="selected"'; ?> >19</option>
	<option value="20" <?php if((!$formSent && $thisDay == 20) || ($formSent && $day_end == 20)) echo 'selected="selected"'; ?> >20</option>
	<option value="21" <?php if((!$formSent && $thisDay == 21) || ($formSent && $day_end == 21)) echo 'selected="selected"'; ?> >21</option>
	<option value="22" <?php if((!$formSent && $thisDay == 22) || ($formSent && $day_end == 22)) echo 'selected="selected"'; ?> >22</option>
	<option value="23" <?php if((!$formSent && $thisDay == 23) || ($formSent && $day_end == 23)) echo 'selected="selected"'; ?> >23</option>
	<option value="24" <?php if((!$formSent && $thisDay == 24) || ($formSent && $day_end == 24)) echo 'selected="selected"'; ?> >24</option>
	<option value="25" <?php if((!$formSent && $thisDay == 25) || ($formSent && $day_end == 25)) echo 'selected="selected"'; ?> >25</option>
	<option value="26" <?php if((!$formSent && $thisDay == 26) || ($formSent && $day_end == 26)) echo 'selected="selected"'; ?> >26</option>
	<option value="27" <?php if((!$formSent && $thisDay == 27) || ($formSent && $day_end == 27)) echo 'selected="selected"'; ?> >27</option>
	<option value="28" <?php if((!$formSent && $thisDay == 28) || ($formSent && $day_end == 28)) echo 'selected="selected"'; ?> >28</option>
	<option value="29" <?php if((!$formSent && $thisDay == 29) || ($formSent && $day_end == 29)) echo 'selected="selected"'; ?> >29</option>
	<option value="30" <?php if((!$formSent && $thisDay == 30) || ($formSent && $day_end == 30)) echo 'selected="selected"'; ?> >30</option>
	<option value="31" <?php if((!$formSent && $thisDay == 31) || ($formSent && $day_end == 31)) echo 'selected="selected"'; ?> >31</option>
  </select>
  /
  <select name="month_end">
	<option value="1">01</option>
	<option value="2" <?php if((!$formSent && $thisMonth == 2) || ($formSent && $month_end == 2)) echo 'selected="selected"'; ?> >02</option>
	<option value="3" <?php if((!$formSent && $thisMonth == 3) || ($formSent && $month_end == 3)) echo 'selected="selected"'; ?> >03</option>
	<option value="4" <?php if((!$formSent && $thisMonth == 4) || ($formSent && $month_end == 4)) echo 'selected="selected"'; ?> >04</option>
	<option value="5" <?php if((!$formSent && $thisMonth == 5) || ($formSent && $month_end == 5)) echo 'selected="selected"'; ?> >05</option>
	<option value="6" <?php if((!$formSent && $thisMonth == 6) || ($formSent && $month_end == 6)) echo 'selected="selected"'; ?> >06</option>
	<option value="7" <?php if((!$formSent && $thisMonth == 7) || ($formSent && $month_end == 7)) echo 'selected="selected"'; ?> >07</option>
	<option value="8" <?php if((!$formSent && $thisMonth == 8) || ($formSent && $month_end == 8)) echo 'selected="selected"'; ?> >08</option>
	<option value="9" <?php if((!$formSent && $thisMonth == 9) || ($formSent && $month_end == 9)) echo 'selected="selected"'; ?> >09</option>
	<option value="10" <?php if((!$formSent && $thisMonth == 10) || ($formSent && $month_end == 10)) echo 'selected="selected"'; ?> >10</option>
	<option value="11" <?php if((!$formSent && $thisMonth == 11) || ($formSent && $month_end == 11)) echo 'selected="selected"'; ?> >11</option>
	<option value="12" <?php if((!$formSent && $thisMonth == 12) || ($formSent && $month_end == 12)) echo 'selected="selected"'; ?> >12</option>
  </select>
  /
  <select name="year_end">

<?php
for ($i=$thisYear-5;$i <= ($thisYear+5);$i++) {
?>
	<option value="<?php echo $i; ?>" <?php if((!$formSent && ($thisYear+1) == $i) || ($formSent && $year_end == $i)) echo 'selected="selected"'; ?> ><?php echo $i; ?></option>
<?php
}
?>
  </select>
  </td>
</tr>
<tr>
	<td>
		&nbsp;
	</td>
	<td>
		<a href="javascript://" onclick="if(document.getElementById('options').style.display == 'none'){document.getElementById('options').style.display = 'block';}else{document.getElementById('options').style.display = 'none';}"><?php echo get_lang('DefineSessionOptions') ?></a>
		<div style="display: <?php if($formSent && ($nb_days_acess_before!=0 || $nb_days_acess_after!=0)) echo 'block'; else echo 'none'; ?>;" id="options">
			<br>
			<input type="text" name="nb_days_acess_before" value="<?php echo $nb_days_acess_before; ?>" style="width: 30px;">&nbsp;<?php echo get_lang('DaysBefore') ?><br>
			<input type="text" name="nb_days_acess_after" value="<?php echo $nb_days_acess_after; ?>" style="width: 30px;">&nbsp;<?php echo get_lang('DaysAfter') ?>
			<br>
		</div>
	</td>
</tr>


<tr>
  <td width="40%"><?php echo get_lang('SessionVisibility') ?></td>
  <td width="60%">
  	<select name="session_visibility" style="width:250px;">
		<?php
		$visibility_list = array(SESSION_VISIBLE_READ_ONLY=>get_lang('ReadOnly'), SESSION_VISIBLE=>get_lang('Visible'), SESSION_INVISIBLE=>api_ucfirst(get_lang('Invisible')));
		foreach($visibility_list as $key=>$item): ?>
		<option value="<?php echo $key; ?>" <?php if($item == $visibility_id) echo 'selected="selected"'; ?>><?php echo $item; ?></option>
		<?php endforeach; ?>
	</select>
  </td>
</tr>


<tr>
  <td>&nbsp;</td>
  <td><button class="save" type="submit" value="<?php echo get_lang('NextStep') ?>"><?php echo get_lang('NextStep') ?></button>
 </td>
</tr>

</table>

</form>
<script type="text/javascript">

function setDisable(select){
	document.form.day_start.disabled = (select.checked) ? true : false;
	document.form.month_start.disabled = (select.checked) ? true : false;
	document.form.year_start.disabled = (select.checked) ? true : false;

	document.form.day_end.disabled = (select.checked) ? true : false;
	document.form.month_end.disabled = (select.checked) ? true : false;
	document.form.year_end.disabled = (select.checked) ? true : false;

	document.form.session_visibility.disabled = (select.checked) ? true : false;
	document.form.session_visibility.selectedIndex = 0;
}
</script>
<?php
Display::display_footer();
?>

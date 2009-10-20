<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2009 Dokeos SPRL

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium, info@dokeos.com
==============================================================================
*/

// name of the language file that needs to be included
$language_file ='admin';
$cidReset=true;
include('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH).'sessionmanager.lib.php');
// setting the section (for the tabs)
$this_section=SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true);
$id=intval($_GET['id']);
$formSent=0;
$errorMsg='';

// Database Table Definitions
$tbl_session_category = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
$tool_name = get_lang('EditSessionCategory');
$interbreadcrumb[]=array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array('url' => "session_category_list.php","name" => get_lang('ListSessionCategory'));
$sql = "SELECT * FROM $tbl_session_category WHERE id='".$id."' ORDER BY name";
$result=Database::query($sql,__FILE__,__LINE__);
if (!$infos=Database::fetch_array($result)) {
	header('Location: session_list.php');
	exit();
}
list($year_start,$month_start,$day_start)=explode('-',$infos['date_start']);
list($year_end,$month_end,$day_end)=explode('-',$infos['date_end']);

if (!api_is_platform_admin() && $infos['session_admin_id']!=$_user['user_id']) {
	api_not_allowed(true);
}
if ($_POST['formSent']) {
	$formSent=1;
	$name= $_POST['name'];
	$year_start= $_POST['year_start'];
	$month_start=$_POST['month_start'];
	$day_start=$_POST['day_start'];
	$year_end=$_POST['year_end'];
	$month_end=$_POST['month_end'];
	$day_end=$_POST['day_end'];
	$return = SessionManager::edit_category_session($id, $name, $year_start, $month_start, $day_start, $year_end, $month_end, $day_end);
	if ($return == strval(intval($return))) {
		header('Location: session_category_list.php?action=show_message&message='.urlencode(get_lang('SessionCategoryUpdate')));
		exit();
	}
}
$thisYear=date('Y');
$thisMonth=date('m');
$thisDay=date('d');

// display the header
Display::display_header($tool_name);
if (!empty($return)) {
	Display::display_error_message($return,false);
}
?>
<form method="post" name="form" action="<?php echo api_get_self(); ?>?page=<?php echo $_GET['page'] ?>&id=<?php echo $id; ?>" style="margin:0px;">
<input type="hidden" name="formSent" value="1">
<div class="row"><div class="form_header"><?php echo $tool_name; ?></div></div>
<table border="0" cellpadding="5" cellspacing="0" width="550">
<tr>
  <td width="30%"><?php echo get_lang('SessionName') ?>&nbsp;&nbsp;</td>
  <td width="70%"><input type="text" name="name" size="50" maxlength="50" value="<?php if($formSent) echo api_htmlentities($name,ENT_QUOTES,$charset); else echo api_htmlentities($infos['name'],ENT_QUOTES,$charset); ?>"></td>
</tr>
<tr>
  <td width="30%"><?php echo get_lang('DateStartSession') ?>&nbsp;&nbsp;</td>
  <td width="70%">
  <select name="day_start">
	<option value="1">01</option>
	<option value="2" <?php if($day_start == 2) echo 'selected="selected"'; ?> >02</option>
	<option value="3" <?php if($day_start == 3) echo 'selected="selected"'; ?> >03</option>
	<option value="4" <?php if($day_start == 4) echo 'selected="selected"'; ?> >04</option>
	<option value="5" <?php if($day_start == 5) echo 'selected="selected"'; ?> >05</option>
	<option value="6" <?php if($day_start == 6) echo 'selected="selected"'; ?> >06</option>
	<option value="7" <?php if($day_start == 7) echo 'selected="selected"'; ?> >07</option>
	<option value="8" <?php if($day_start == 8) echo 'selected="selected"'; ?> >08</option>
	<option value="9" <?php if($day_start == 9) echo 'selected="selected"'; ?> >09</option>
	<option value="10" <?php if($day_start == 10) echo 'selected="selected"'; ?> >10</option>
	<option value="11" <?php if($day_start == 11) echo 'selected="selected"'; ?> >11</option>
	<option value="12" <?php if($day_start == 12) echo 'selected="selected"'; ?> >12</option>
	<option value="13" <?php if($day_start == 13) echo 'selected="selected"'; ?> >13</option>
	<option value="14" <?php if($day_start == 14) echo 'selected="selected"'; ?> >14</option>
	<option value="15" <?php if($day_start == 15) echo 'selected="selected"'; ?> >15</option>
	<option value="16" <?php if($day_start == 16) echo 'selected="selected"'; ?> >16</option>
	<option value="17" <?php if($day_start == 17) echo 'selected="selected"'; ?> >17</option>
	<option value="18" <?php if($day_start == 18) echo 'selected="selected"'; ?> >18</option>
	<option value="19" <?php if($day_start == 19) echo 'selected="selected"'; ?> >19</option>
	<option value="20" <?php if($day_start == 20) echo 'selected="selected"'; ?> >20</option>
	<option value="21" <?php if($day_start == 21) echo 'selected="selected"'; ?> >21</option>
	<option value="22" <?php if($day_start == 22) echo 'selected="selected"'; ?> >22</option>
	<option value="23" <?php if($day_start == 23) echo 'selected="selected"'; ?> >23</option>
	<option value="24" <?php if($day_start == 24) echo 'selected="selected"'; ?> >24</option>
	<option value="25" <?php if($day_start == 25) echo 'selected="selected"'; ?> >25</option>
	<option value="26" <?php if($day_start == 26) echo 'selected="selected"'; ?> >26</option>
	<option value="27" <?php if($day_start == 27) echo 'selected="selected"'; ?> >27</option>
	<option value="28" <?php if($day_start == 28) echo 'selected="selected"'; ?> >28</option>
	<option value="29" <?php if($day_start == 29) echo 'selected="selected"'; ?> >29</option>
	<option value="30" <?php if($day_start == 30) echo 'selected="selected"'; ?> >30</option>
	<option value="31" <?php if($day_start == 31) echo 'selected="selected"'; ?> >31</option>
  </select>
  /
  <select name="month_start">
	<option value="1">01</option>
	<option value="2" <?php if($month_start == 2) echo 'selected="selected"'; ?> >02</option>
	<option value="3" <?php if($month_start == 3) echo 'selected="selected"'; ?> >03</option>
	<option value="4" <?php if($month_start == 4) echo 'selected="selected"'; ?> >04</option>
	<option value="5" <?php if($month_start == 5) echo 'selected="selected"'; ?> >05</option>
	<option value="6" <?php if($month_start == 6) echo 'selected="selected"'; ?> >06</option>
	<option value="7" <?php if($month_start == 7) echo 'selected="selected"'; ?> >07</option>
	<option value="8" <?php if($month_start == 8) echo 'selected="selected"'; ?> >08</option>
	<option value="9" <?php if($month_start == 9) echo 'selected="selected"'; ?> >09</option>
	<option value="10" <?php if($month_start == 10) echo 'selected="selected"'; ?> >10</option>
	<option value="11" <?php if($month_start == 11) echo 'selected="selected"'; ?> >11</option>
	<option value="12" <?php if($month_start == 12) echo 'selected="selected"'; ?> >12</option>
  </select>
  /
  <select name="year_start">
<?php
for($i=$thisYear-5;$i <= ($thisYear+5);$i++)
{ ?>
	<option value="<?php echo $i; ?>" <?php if($year_start == $i) echo 'selected="selected"'; ?> ><?php echo $i; ?></option>
<?php
} ?>
  </select>
  </td>
</tr>
<tr>
  <td width="30%"><?php echo get_lang('DateEndSession') ?>&nbsp;&nbsp;</td>
  <td width="70%">
  <select name="day_end">
	<option value="1">01</option>
	<option value="2" <?php if($day_end == 2) echo 'selected="selected"'; ?> >02</option>
	<option value="3" <?php if($day_end == 3) echo 'selected="selected"'; ?> >03</option>
	<option value="4" <?php if($day_end == 4) echo 'selected="selected"'; ?> >04</option>
	<option value="5" <?php if($day_end == 5) echo 'selected="selected"'; ?> >05</option>
	<option value="6" <?php if($day_end == 6) echo 'selected="selected"'; ?> >06</option>
	<option value="7" <?php if($day_end == 7) echo 'selected="selected"'; ?> >07</option>
	<option value="8" <?php if($day_end == 8) echo 'selected="selected"'; ?> >08</option>
	<option value="9" <?php if($day_end == 9) echo 'selected="selected"'; ?> >09</option>
	<option value="10" <?php if($day_end == 10) echo 'selected="selected"'; ?> >10</option>
	<option value="11" <?php if($day_end == 11) echo 'selected="selected"'; ?> >11</option>
	<option value="12" <?php if($day_end == 12) echo 'selected="selected"'; ?> >12</option>
	<option value="13" <?php if($day_end == 13) echo 'selected="selected"'; ?> >13</option>
	<option value="14" <?php if($day_end == 14) echo 'selected="selected"'; ?> >14</option>
	<option value="15" <?php if($day_end == 15) echo 'selected="selected"'; ?> >15</option>
	<option value="16" <?php if($day_end == 16) echo 'selected="selected"'; ?> >16</option>
	<option value="17" <?php if($day_end == 17) echo 'selected="selected"'; ?> >17</option>
	<option value="18" <?php if($day_end == 18) echo 'selected="selected"'; ?> >18</option>
	<option value="19" <?php if($day_end == 19) echo 'selected="selected"'; ?> >19</option>
	<option value="20" <?php if($day_end == 20) echo 'selected="selected"'; ?> >20</option>
	<option value="21" <?php if($day_end == 21) echo 'selected="selected"'; ?> >21</option>
	<option value="22" <?php if($day_end == 22) echo 'selected="selected"'; ?> >22</option>
	<option value="23" <?php if($day_end == 23) echo 'selected="selected"'; ?> >23</option>
	<option value="24" <?php if($day_end == 24) echo 'selected="selected"'; ?> >24</option>
	<option value="25" <?php if($day_end == 25) echo 'selected="selected"'; ?> >25</option>
	<option value="26" <?php if($day_end == 26) echo 'selected="selected"'; ?> >26</option>
	<option value="27" <?php if($day_end == 27) echo 'selected="selected"'; ?> >27</option>
	<option value="28" <?php if($day_end == 28) echo 'selected="selected"'; ?> >28</option>
	<option value="29" <?php if($day_end == 29) echo 'selected="selected"'; ?> >29</option>
	<option value="30" <?php if($day_end == 30) echo 'selected="selected"'; ?> >30</option>
	<option value="31" <?php if($day_end == 31) echo 'selected="selected"'; ?> >31</option>
  </select>
  /
  <select name="month_end">
	<option value="1">01</option>
	<option value="2" <?php if($month_end == 2) echo 'selected="selected"'; ?> >02</option>
	<option value="3" <?php if($month_end == 3) echo 'selected="selected"'; ?> >03</option>
	<option value="4" <?php if($month_end == 4) echo 'selected="selected"'; ?> >04</option>
	<option value="5" <?php if($month_end == 5) echo 'selected="selected"'; ?> >05</option>
	<option value="6" <?php if($month_end == 6) echo 'selected="selected"'; ?> >06</option>
	<option value="7" <?php if($month_end == 7) echo 'selected="selected"'; ?> >07</option>
	<option value="8" <?php if($month_end == 8) echo 'selected="selected"'; ?> >08</option>
	<option value="9" <?php if($month_end == 9) echo 'selected="selected"'; ?> >09</option>
	<option value="10" <?php if($month_end == 10) echo 'selected="selected"'; ?> >10</option>
	<option value="11" <?php if($month_end == 11) echo 'selected="selected"'; ?> >11</option>
	<option value="12" <?php if($month_end == 12) echo 'selected="selected"'; ?> >12</option>
  </select>
  /
  <select name="year_end">

<?php
for($i=$thisYear-5;$i <= ($thisYear+5);$i++)
{ ?>
	<option value="<?php echo $i; ?>" <?php if($year_end == $i) echo 'selected="selected"'; ?> ><?php echo $i; ?></option>
<?php
} ?>
  </select>
  </td>
</tr>
<tr>
	<td>
		&nbsp;
	</td>
</tr>
<tr>
  <td>&nbsp;</td>
  <td>
<button class="save" type="submit" value="<?php echo get_lang('ModifyThisSession') ?>"><?php echo get_lang('ModifyThisSession') ?></button>

  </td>
</tr>

</table>

</form>
<script type="text/javascript">
<?php if($year_start=="0000") echo "setDisable(document.form.nolimit);\r\n"; ?>
function setDisable(select){
	document.form.day_start.disabled = (select.checked) ? true : false;
	document.form.month_start.disabled = (select.checked) ? true : false;
	document.form.year_start.disabled = (select.checked) ? true : false;
	document.form.day_end.disabled = (select.checked) ? true : false;
	document.form.month_end.disabled = (select.checked) ? true : false;
	document.form.year_end.disabled = (select.checked) ? true : false;
}
</script>
<?php
Display::display_footer();
?>
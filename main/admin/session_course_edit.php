<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */
/**
 * Implements the edition of course-session settings
 * @package dokeos.admin
 */
// name of the language file that needs to be included
$language_file='admin';

$cidReset=true;

require '../inc/global.inc.php';

// setting the section (for the tabs)
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

$id_session=intval($_GET['id_session']);
$course_code=trim(stripslashes($_GET['course_code']));

$formSent=0;
$errorMsg='';

// Database Table Definitions
$tbl_user			= Database::get_main_table(TABLE_MAIN_USER);
$tbl_course			= Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_session		= Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_course	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);

$course_info=api_get_course_info($_REQUEST['course_code']);
$tool_name=$course_info['name'];

$interbreadcrumb[]=array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array('url' => "session_list.php","name" => get_lang("SessionList"));
$interbreadcrumb[]=array('url' => "../admin/resume_session.php?id_session=".Security::remove_XSS($_REQUEST['id_session']),"name" => get_lang('SessionOverview'));
$interbreadcrumb[]=array('url' => "session_course_list.php?id_session=$id_session","name" =>api_htmlentities($session_name,ENT_QUOTES,$charset));

$result=Database::query("SELECT name,title FROM $tbl_session_course,$tbl_session,$tbl_course WHERE id_session=id AND course_code=code AND id_session='$id_session' AND course_code='".addslashes($course_code)."'",__FILE__,__LINE__);

if (!list($session_name,$course_title)=mysql_fetch_row($result)) {
	header('Location: session_course_list.php?id_session='.$id_session);
	exit();
}

if ($_POST['formSent']) {
	$formSent=1;

	$id_coach=intval($_POST['id_coach']);


	Database::query("UPDATE $tbl_session_course
				   SET id_coach='$id_coach'
				   WHERE id_session='$id_session' AND course_code='$course_code'",__FILE__,__LINE__);
	header('Location: '.$_GET['page'].'?id_session='.$id_session);
	exit();

}else {
	$result=Database::query("SELECT id_coach FROM $tbl_session_course WHERE id_session='$id_session' AND course_code='$course_code'",__FILE__,__LINE__);

	if (!$infos=Database::fetch_array($result)) {
		//header('Location: '.$_GET['page'].'?id_session='.$id_session);
		exit();
	}
}

$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';
$sql="SELECT user_id,lastname,firstname,username FROM $tbl_user WHERE status='1'".$order_clause;

$result=Database::query($sql,__FILE__,__LINE__);

$coaches=Database::store_result($result);

Display::display_header($tool_name);

$tool_name=get_lang('ModifySessionCourse');
api_display_tool_title($tool_name);
?>
<form method="post" action="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&page=<?php echo Security::remove_XSS($_GET['page']) ?>" style="margin:0px;">
<input type="hidden" name="formSent" value="1">

<table border="0" cellpadding="5" cellspacing="0" width="550">

<?php
if(!empty($errorMsg))
{
?>

<tr>
  <td colspan="2">

<?php
	Display::display_normal_message($errorMsg);
?>

  </td>
</tr>

<?php
}
?>

<tr>
  <td width="30%"><?php echo get_lang("CoachName") ?>&nbsp;&nbsp;</td>
  <td width="70%"><select name="id_coach" style="width:250px;">
	<option value="0">----- <?php echo get_lang("Choose") ?> -----</option>
	<option value="0" <?php if((!$sent && $enreg['user_id'] == $infos['id_coach']) || ($sent && $enreg['user_id'] == $id_coach)) echo 'selected="selected"'; ?>><?php echo get_lang('None') ?></option>
<?php

foreach($coaches as $enreg)
{
?>

	<option value="<?php echo $enreg['user_id']; ?>" <?php if((!$sent && $enreg['user_id'] == $infos['id_coach']) || ($sent && $enreg['user_id'] == $id_coach)) echo 'selected="selected"'; ?>><?php echo api_get_person_name($enreg['lastname'], $enreg['firstname']).' ('.$enreg['username'].')'; ?></option>

<?php
}

unset($coaches);
?>

  </select></td>
</tr>
<tr>
  <td>&nbsp;</td>
  <td><button class="save" type="submit" name="name" value="<?php echo get_lang('ModifyCoach') ?>"><?php echo get_lang('ModifyCoach') ?></button>
</td>
</tr>

</table>

</form>

<?php
Display::display_footer();
?>

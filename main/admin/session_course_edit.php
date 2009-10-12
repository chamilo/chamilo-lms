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
require_once '../inc/lib/sessionmanager.lib.php';
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
$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

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

$arr_infos = array();
if ($_POST['formSent']) {
	$formSent=1;

	// get all tutor by course_code in the session	
	$sql = "SELECT id_user FROM $tbl_session_rel_course_rel_user WHERE id_session = '$id_session' AND course_code = '$course_code' AND status = 2";	
	$rs_coachs = Database::query($sql,__FILE__,__LINE__);
	
	$coachs_course_session = array();
	if (Database::num_rows($rs_coachs) > 0){
		while ($row_coachs = Database::fetch_row($rs_coachs)) {
			$coachs_course_session[] = $row_coachs[0];		
		}	
	}			
	
	$id_coachs= $_POST['id_coach'];
			
	if (is_array($id_coachs) && count($id_coachs) > 0) {
		
		foreach ($id_coachs as $id_coach) {			
			$id_coach = intval($id_coach);
			$rs1 = SessionManager::set_coach_to_course_session($id_coach, $id_session, $course_code);										
		}
		
		// set status to 0 other tutors from multiple list		
		$array_intersect = array_diff($coachs_course_session,$id_coachs);				
					
		foreach ($array_intersect as $nocoach_user_id) {							
			$rs2 = SessionManager::set_coach_to_course_session($nocoach_user_id, $id_session, $course_code,true);
		}

		header('Location: '.Security::remove_XSS($_GET['page']).'?id_session='.$id_session);
		exit();		
				
	}
	
}else {
	
	$sql = "SELECT id_user FROM $tbl_session_rel_course_rel_user WHERE id_session = '$id_session' AND course_code = '$course_code' AND status = 2 ";	
	$rs = Database::query($sql,__FILE__,__LINE__);	

	if (Database::num_rows($rs) > 0) {					
		while ($infos = Database::fetch_array($rs)) {
			$arr_infos[] = $infos['id_user'];
		}	
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
  <td width="25%" valign = "top"><?php echo get_lang("CoachName") ?>&nbsp;&nbsp;</td>
  <td width="70%"><select name="id_coach[]" style="width:300px;" multiple="multiple" size="7">
	<option value="0">----- <?php echo get_lang("Choose") ?> -----</option>
	<option value="0" <?php if(count($arr_infos) == 0) echo 'selected="selected"'; ?>><?php echo get_lang('None') ?></option>
<?php

foreach($coaches as $enreg)
{
?>

	<option value="<?php echo $enreg['user_id']; ?>" <?php if((!$sent && (is_array($arr_infos) && in_array($enreg['user_id'],$arr_infos))) || ($sent && $enreg['user_id'] == $id_coach)) echo 'selected="selected"'; ?>><?php echo api_get_person_name($enreg['firstname', $enreg['lastname']).' ('.$enreg['username'].')'; ?></option>

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

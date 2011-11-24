<?php
/**
*	@package chamilo.admin
*/
// name of the language file that needs to be included
$language_file = 'admin';

$cidReset = true;

include '../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script(true);
include api_get_path(LIBRARY_PATH).'fileManage.lib.php';

$session_id = intval($_GET['session_id']);
$formSent = 0;
$errorMsg = '';

// Database Table Definitions
$tbl_user					= Database::get_main_table(TABLE_MAIN_USER);
$tbl_course      			= Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_course_user 			= Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_session      			= Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_user      		= Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_course      	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_course_user 	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);


$archivePath = api_get_path(SYS_ARCHIVE_PATH);
$archiveURL  = api_get_path(WEB_CODE_PATH).'course_info/download.php?archive=';

$tool_name   = get_lang('ExportSessionListXMLCSV');

global $_configuration;


$interbreadcrumb[] = array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));

set_time_limit(0);

if ($_POST['formSent']) {
	$formSent = $_POST['formSent'];
	$file_type = ($_POST['file_type'] == 'csv')?'csv':'xml';
	$session_id = $_POST['session_id'];
	if (empty($session_id)) {
		$sql = "SELECT id,name,id_coach,username,date_start,date_end,visibility,session_category_id FROM $tbl_session INNER JOIN $tbl_user
					ON $tbl_user.user_id = $tbl_session.id_coach ORDER BY id";

		
		if ($_configuration['multiple_access_urls']) {
			$tbl_session_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1){
			$sql = "SELECT id, name,id_coach,username,date_start,date_end,visibility,session_category_id FROM $tbl_session s INNER JOIN $tbl_session_rel_access_url as session_rel_url
				ON (s.id= session_rel_url.session_id) INNER JOIN $tbl_user u ON (u.user_id = s.id_coach)
				WHERE access_url_id = $access_url_id
				ORDER BY id";

			}
		}
		$result=Database::query($sql);
	}
	else
	{
		$sql = "SELECT id,name,username,date_start,date_end,visibility,session_category_id
				FROM $tbl_session
				INNER JOIN $tbl_user
					ON $tbl_user.user_id = $tbl_session.id_coach
				WHERE id='$session_id'";

		$result = Database::query($sql);

	}

	if(Database::num_rows($result))
	{
		if(!file_exists($archivePath))
		{
			mkdir($archivePath, api_get_permissions_for_new_directories(), true);
		}

		if(!file_exists($archivePath.'index.html'))
		{
			$fp=fopen($archivePath.'index.html','w');

			fputs($fp,'<html><head></head><body></body></html>');

			fclose($fp);
		}

		$archiveFile='export_sessions_'.$session_id.'_'.date('Y-m-d_H-i-s').'.'.$file_type;

		while( file_exists($archivePath.$archiveFile))
		{
			$archiveFile='export_users_'.$session_id.'_'.date('Y-m-d_H-i-s').'_'.uniqid('').'.'.$file_type;
		}
		$fp=fopen($archivePath.$archiveFile,'w');

		if($file_type == 'csv')
		{
			$cvs = true;
			fputs($fp,"SessionName;Coach;DateStart;DateEnd;Visibility;SessionCategory;Users;Courses;\n");
		}
		else
		{
			$cvs = false;
			fputs($fp, "<?xml version=\"1.0\" encoding=\"".api_get_system_encoding()."\"?>\n<Sessions>\n");
		}

		while($row=Database::fetch_array($result))
		{
			$add = '';
			$row['name'] = str_replace(';',',',$row['name']);
			$row['username'] = str_replace(';',',',$row['username']);
			$row['date_start'] = str_replace(';',',',$row['date_start']);
			$row['date_end'] = str_replace(';',',',$row['date_end']);
			$row['visibility'] = str_replace(';',',',$row['visibility']);
			$row['session_category'] = str_replace(';',',',$row['session_category_id']);
			if($cvs){
				$add.= $row['name'].';'.$row['username'].';'.$row['date_start'].';'.$row['date_end'].';'.$row['visibility'].';'.$row['session_category'].';';
			}
			else {
				$add = "\t<Session>\n"
						 ."\t\t<SessionName>$row[name]</SessionName>\n"
						 ."\t\t<Coach>$row[username]</Coach>\n"
						 ."\t\t<DateStart>$row[date_start]</DateStart>\n"
						 ."\t\t<DateEnd>$row[date_end]</DateEnd>\n"
						 ."\t\t<Visibility>$row[visibility]</Visibility>\n"
						 ."\t\t<SessionCategory>$row[session_category]</SessionCategory>\n";
			}

			//users
			$sql = "SELECT DISTINCT $tbl_user.username FROM $tbl_user
					INNER JOIN $tbl_session_user
						ON $tbl_user.user_id = $tbl_session_user.id_user AND $tbl_session_user.relation_type<>".SESSION_RELATION_TYPE_RRHH."
						AND $tbl_session_user.id_session = '".$row['id']."'";

			$rsUsers = Database::query($sql);
			$users = '';
			while($rowUsers = Database::fetch_array($rsUsers)){
				if($cvs){
					$users .= str_replace(';',',',$rowUsers['username']).'|';
				}
				else {
					$users .= "\t\t<User>$rowUsers[username]</User>\n";
				}
			}
			if(!empty($users) && $cvs)
				$users = api_substr($users , 0, api_strlen($users)-1);

			if($cvs)
				$users .= ';';

			$add .= $users;

			//courses
			$sql = "SELECT DISTINCT $tbl_course.code
					FROM $tbl_course
					INNER JOIN $tbl_session_course_user
						ON $tbl_course.code = $tbl_session_course_user.course_code
						AND $tbl_session_course_user.id_session = '".$row['id']."'";

			$rsCourses = Database::query($sql);

			$courses = '';
			while($rowCourses = Database::fetch_array($rsCourses)){

				// get coachs from a course
				$sql = "SELECT u.username
					FROM $tbl_session_course_user scu
					INNER JOIN $tbl_user u ON u.user_id = scu.id_user
					WHERE scu.course_code = '{$rowCourses['code']}'
						AND scu.id_session = '".$row['id']."' AND scu.status = 2 ";

				$rs_coachs = Database::query($sql);
				$coachs = array();
				while ($row_coachs = Database::fetch_array($rs_coachs)) {
					$coachs[] = $row_coachs['username'];
				}

				$coachs = implode(",",$coachs);

				if($cvs){
					$courses .= str_replace(';',',',$rowCourses['code']);
					$courses .= '['.str_replace(';',',',$coachs).'][';
				}
				else {
					$courses .= "\t\t<Course>\n";
					$courses .= "\t\t\t<CourseCode>$rowCourses[code]</CourseCode>\n";
					$courses .= "\t\t\t<Coach>$coachs</Coach>\n";
				}

				// rel user courses
				$sql = "SELECT DISTINCT u.username
						FROM $tbl_session_course_user scu
						INNER JOIN $tbl_session_user su ON scu.id_user = su.id_user AND scu.id_session = su.id_session AND su.relation_type<>".SESSION_RELATION_TYPE_RRHH."
						INNER JOIN $tbl_user u
						ON scu.id_user = u.user_id
						AND scu.course_code='".$rowCourses['code']."'
						AND scu.id_session='".$row['id']."'";

				$rsUsersCourse = Database::query($sql);
				$userscourse = '';
				while($rowUsersCourse = Database::fetch_array($rsUsersCourse)){

					if($cvs){
						$userscourse .= str_replace(';',',',$rowUsersCourse['username']).',';
					}
					else {
						$courses .= "\t\t\t<User>$rowUsersCourse[username]</User>\n";
					}
				}
				if($cvs){
					if(!empty($userscourse))
						$userscourse = api_substr($userscourse , 0, api_strlen($userscourse)-1);

					$courses .= $userscourse.']|';
				}
				else {
					$courses .= "\t\t</Course>\n";
				}
			}

			if(!empty($courses) && $cvs)
				$courses = api_substr($courses , 0, api_strlen($courses)-1);
			$add .= $courses;

			if($cvs) {
				$breakline = api_is_windows_os()?"\r\n":"\n";
				$add .= ";$breakline";
			} else {
				$add .= "\t</Session>\n";
			}

			fputs($fp, $add);
		}

		if(!$cvs)
			fputs($fp,"</Sessions>\n");
		fclose($fp);

		$errorMsg=get_lang('UserListHasBeenExported').'<br/><a href="'.$archiveURL.$archiveFile.'">'.get_lang('ClickHereToDownloadTheFile').'</a>';
	}
}

// display the header
Display::display_header($tool_name);

//select of sessions
$sql = "SELECT id, name FROM $tbl_session ORDER BY name";

if ($_configuration['multiple_access_urls']) {
	$tbl_session_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
	$access_url_id = api_get_current_access_url_id();
	if ($access_url_id != -1){
	$sql = "SELECT id, name FROM $tbl_session s INNER JOIN $tbl_session_rel_access_url as session_rel_url
		ON (s.id= session_rel_url.session_id)
		WHERE access_url_id = $access_url_id
		ORDER BY name";
	}
}


$result=Database::query($sql);

$Sessions=Database::store_result($result);

echo '<div class="actions">';
echo '<a href="../admin/index.php">'.Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'),'','32').'</a>';
echo '</div>';

if(!empty($errorMsg)) {
	Display::display_normal_message($errorMsg, false); //main API
}
?>
<form method="post" action="<?php echo api_get_self(); ?>" style="margin:0px;">
<input type="hidden" name="formSent" value="1">
<table border="0" cellpadding="5" cellspacing="0">
<tr>
  <td nowrap="nowrap" valign="top"><?php echo get_lang('OutputFileType'); ?> :</td>
  <td>
	<input class="checkbox" type="radio" name="file_type" id="file_type_xml" value="xml" <?php if($formSent && $file_type == 'xml') echo 'checked="checked"'; ?>> <label for="file_type_xml">XML</label><br>
	<input class="checkbox" type="radio" name="file_type" id="file_type_csv"  value="csv" <?php if(!$formSent || $file_type == 'csv') echo 'checked="checked"'; ?>> <label for="file_type_csv">CSV</label><br>
  </td>
</tr>
<tr>
  <td><?php echo get_lang('WhichSessionToExport'); ?> :</td>
  <td><select name="session_id">
	<option value=""><?php echo get_lang('AllSessions') ?></option>

<?php
foreach($Sessions as $enreg) {
?>
    <option value="<?php echo $enreg['id']; ?>" <?php if($session_id == $enreg['id']) echo 'selected="selected"'; ?>>
        <?php echo $enreg['name']; ?></option>
<?php
}
unset($Courses);
?>

  </select></td>
</tr>
<tr>
  <td>&nbsp;</td>
  <td>
  <button class="save" type="submit" name="name" value="<?php echo get_lang('ExportSession') ?>"><?php echo get_lang('ExportSession') ?></button>
  </td>
</tr>
</table>
</form>
<?php
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
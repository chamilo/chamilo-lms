<?php
/* For licensing terms, see /license.txt */
/**
 *	@package chamilo.admin
 */

$cidReset = true;

require_once '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

// Database table definitions
$course_table = Database:: get_main_table(TABLE_MAIN_COURSE);
$user_table = Database:: get_main_table(TABLE_MAIN_USER);
$course_user_table = Database:: get_main_table(TABLE_MAIN_COURSE_USER);

$tool_name = get_lang('ExportUserListXMLCSV');

$interbreadcrumb[] = array("url" => 'index.php', "name" => get_lang('PlatformAdmin'));

set_time_limit(0);

$courses = array ();
$courses[''] = '--';
$sql = "SELECT code,visual_code,title FROM $course_table ORDER BY visual_code";

global $_configuration;

if (api_is_multiple_url_enabled()) {
	$tbl_course_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
	$access_url_id = api_get_current_access_url_id();
	if ($access_url_id != -1){
	$sql = "SELECT code,visual_code,title
		FROM $course_table as c
		INNER JOIN $tbl_course_rel_access_url as course_rel_url
		ON (c.id = course_rel_url.c_id)
		WHERE access_url_id = $access_url_id
		ORDER BY visual_code";
	}
}
$result = Database::query($sql);
while ($course = Database::fetch_object($result)) {
	$courses[$course->code] = $course->visual_code.' - '.$course->title;
}
$form = new FormValidator('export_users');
$form->addElement('header', $tool_name);
$form->addElement('radio', 'file_type', get_lang('OutputFileType'), 'XML','xml');
$form->addElement('radio', 'file_type', null, 'CSV', 'csv');
$form->addElement('radio', 'file_type', null, 'XLS', 'xls');

$form->addElement('checkbox', 'addcsvheader', get_lang('AddCSVHeader'), get_lang('YesAddCSVHeader'),'1');
$form->addElement('select', 'course_code', get_lang('OnlyUsersFromCourse'), $courses);
$form->addButtonExport(get_lang('Export'));
$form->setDefaults(array('file_type' => 'csv'));

if ($form->validate()) {
	$export = $form->exportValues();
	$file_type = $export['file_type'];
	$course_code = Database::escape_string($export['course_code']);
	$courseInfo = api_get_course_info($course_code);
	$courseId = $courseInfo['real_id'];

	$sql = "SELECT
				u.user_id 	AS UserId,
				u.lastname 	AS LastName,
				u.firstname 	AS FirstName,
				u.email 		AS Email,
				u.username	AS UserName,
				".(($_configuration['password_encryption']!='none')?" ":"u.password AS Password, ")."
				u.auth_source	AS AuthSource,
				u.status		AS Status,
				u.official_code	AS OfficialCode,
				u.phone		AS Phone";
	if (strlen($course_code) > 0) {
		$sql .= " FROM $user_table u, $course_user_table cu
					WHERE
						u.user_id = cu.user_id AND
						cu.c_id = $courseId AND
						cu.relation_type<>".COURSE_RELATION_TYPE_RRHH."
					ORDER BY lastname,firstname";
		$filename = 'export_users_'.$course_code.'_'.api_get_local_time();
	} else {
		if (api_is_multiple_url_enabled()) {
			$tbl_user_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1) {
			$sql.= " FROM $user_table u
					INNER JOIN $tbl_user_rel_access_url as user_rel_url
				ON (u.user_id= user_rel_url.user_id)
				WHERE access_url_id = $access_url_id
				ORDER BY lastname,firstname";
			}
		} else {
			$sql .= " FROM $user_table u ORDER BY lastname,firstname";
		}
		$filename = 'export_users_'.api_get_local_time();
	}
	$data = array();
	$extra_fields = UserManager::get_extra_fields(0, 0, 5, 'ASC',false);
	if ($export['addcsvheader']=='1' AND $export['file_type']=='csv') {
		if ($_configuration['password_encryption'] != 'none') {
			$data[] = array(
				'UserId',
				'LastName',
				'FirstName',
				'Email',
				'UserName',
				'AuthSource',
				'Status',
				'OfficialCode',
				'PhoneNumber',
			);
		} else {
			$data[] = array(
				'UserId',
				'LastName',
				'FirstName',
				'Email',
				'UserName',
				'Password',
				'AuthSource',
				'Status',
				'OfficialCode',
				'PhoneNumber',
			);
		}

		foreach($extra_fields as $extra) {
			$data[0][]=$extra[1];
		}
	}

	$res = Database::query($sql);
	while($user = Database::fetch_array($res,'ASSOC')) {
		$student_data = UserManager:: get_extra_user_data(
			$user['UserId'],
			true,
			false
		);
		foreach($student_data as $key=>$value) {
			$key = substr($key, 6);
			if (is_array($value)) {
				$user[$key] = $value[$key];
			} else {
				$user[$key] = $value;
			}
		}
		$data[] = $user	;
	}

	switch ($file_type) {
		case 'xml':
			Export::arrayToXml($data, $filename, 'Contact', 'Contacts');
			exit;
			break;
		case 'csv':
			Export::arrayToCsv($data, $filename);
			exit;
		case 'xls':
			Export::arrayToXls($data, $filename);
			exit;
			break;
	}
}

Display :: display_header($tool_name);
$form->display();
Display :: display_footer();

<?php
/* For licensing terms, see /license.txt */
/**
* 	This tool allows platform admins to export courses to CSV file
*	@package chamilo.admin
*/
/**
 * Code
 */
$language_file = array ('admin', 'registration','create_course', 'document');
$cidReset = true;

require_once '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();

$tool_name = get_lang('ExportCourses').' CSV';
$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));

set_time_limit(0);
Display :: display_header($tool_name);

$archivePath = api_get_path(SYS_ARCHIVE_PATH);
$archiveURL = api_get_path(WEB_CODE_PATH).'course_info/download.php?archive=';

$course_list = CourseManager::get_courses_list();

if ($_POST['formSent']) {
	$formSent	=$_POST['formSent'];
	$select_type=intval($_POST['select_type']);
	$file_type = 'csv';
	$courses = $selected_courses = array();

	if ($select_type == 2) {
		// Get selected courses from courses list in form sent
		$selected_courses = $_POST['course_code'];
		if (is_array($selected_courses)) {
			foreach ($course_list as $course) {
				if (!in_array($course['code'],$selected_courses)) continue;
				$courses[] = $course;
			}
		}
	} else {
		// Get all courses
		$courses = $course_list;
	}

	if (!empty($courses)) {
		if (!file_exists($archivePath)) {
			mkdir($archivePath, api_get_permissions_for_new_directories(), true);
		}
		$archiveFile = 'export_courses_list_'.date('Y-m-d_H-i-s').'.'.$file_type;
		$fp = fopen($archivePath.$archiveFile,'w');
		if ($file_type == 'csv') {
			$add = "Code;Title;CourseCategory;Teacher;Language;".PHP_EOL;
			foreach($courses as $course) {
				$course['code'] = str_replace(';',',',$course['code']);
				$course['title'] = str_replace(';',',',$course['title']);
				$course['category_code'] = str_replace(';',',',$course['category_code']);
				$course['tutor_name'] = str_replace(';',',',$course['tutor_name']);
				$course['course_language'] = str_replace(';',',',$course['course_language']);

				$add.= $course['code'].';'.$course['title'].';'.$course['category_code'].';'.$course['tutor_name'].';'.$course['course_language'].';'.PHP_EOL;
			}
			fputs($fp, $add);
		}
		fclose($fp);
		$msg = get_lang('CoursesListHasBeenExported').'<br/><a href="'.$archiveURL.$archiveFile.'">'.get_lang('ClickHereToDownloadTheFile').'</a>';
	} else {
		$msg = get_lang('ThereAreNotSelectedCoursesOrCoursesListIsEmpty');
	}
}

if (!empty($msg)) {
	Display::display_normal_message($msg, false);
}
?>


<form method="post" action="<?php echo api_get_self(); ?>" style="margin:0px;">
    <input type="hidden" name="formSent" value="1">
    <div class="row"><div class="form_header"><?php echo $tool_name; ?></div></div>
    <br />
<?php if (!empty($course_list)) { ?>
<div>
<input id="all-courses" class="checkbox" type="radio" value="1" name="select_type" <?php if(!$formSent || ($formSent && $select_type == 1)) echo 'checked="checked"'; ?> onclick="javascript: if(this.checked){document.getElementById('div-course-list').style.display='none';}"/>
<label for="all-courses"><?php echo get_lang('ExportAllCoursesList')?></label>
<br/>
<input id="select-courses" class="checkbox" type="radio" value="2" name="select_type" <?php if($formSent && $select_type == 2) echo 'checked="checked"'; ?> onclick="javascript: if(this.checked){document.getElementById('div-course-list').style.display='block';}"/>
<label for="select-courses"><?php echo get_lang('ExportSelectedCoursesFromCoursesList')?></label>
</div>
<br />
<div id="div-course-list" style="<?php echo (!$formSent || ($formSent && $select_type == 1))?'display:none':'display:block';?>">
<table border="0" cellpadding="5" cellspacing="0">
<tr>
  <td valign="top"><?php echo get_lang('WhichCoursesToExport'); ?> :</td>
  <td>
      <select name="course_code[]" multiple="multiple" size="10">
        <?php
        foreach($course_list as $course) {
        ?>
        	<option value="<?php echo $course['code']; ?>" <?php if(is_array($selected_courses) && in_array($course['code'],$selected_courses)) echo 'selected="selected"'; ?>><?php echo $course['title'].' ('.$course['code'].') ' ?></option>
        <?php
        }
        ?>
        </select>
    </td>
</tr>
</table>
</div>
<br />
<div id="actions">
  <button class="save" type="submit" name="name" value="<?php echo get_lang('ExportCourses') ?>"><?php echo get_lang('ExportCourses') ?></button>
</div>
<?php } else { echo get_lang('ThereAreNotCreatedCourses'); }?>
</form>
<?php
Display :: display_footer();
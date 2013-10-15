<?php
$language_file = array ('registration');
require_once '../../../main/inc/global.inc.php';
require_once '../../../main/inc/lib/course.lib.php';
$user_id=intval($_GET['user_id']);
$list_course_all_info=array();
$list_course=array();
$user_info = api_get_user_info($user_id);
$courses_list=CourseManager::get_courses_list_by_user_id($user_id,false,true);
?>
<div class="row">
	<div class="label2">Usuario:</div>
       <div class="formw2" id="user_request"><?php echo $user_info['firstname']." ".$user_info['lastname'] ;?></div>
</div>
<div class="row" id="divCourse">
	<div class="label2"  >Curso:</div>
	<div class="formw2" id="courseuser">
	 <select  class="chzn-select" name = "course_id" id="course_id"  style="width:95%;">
		<option value="0">---Seleccionar---</option>
		<?php  for($k=0;$k<count($courses_list);$k++) { ?>
				<option value = "<?php echo $courses_list[$k]['course_id']?>"><?php echo $courses_list[$k]['title']?></option>
		<?php } ?>
	</select>
	</div>
</div>
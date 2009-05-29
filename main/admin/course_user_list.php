<?php
$language_file = array ('registration');
require_once '../inc/global.inc.php';
require_once '../inc/lib/course.lib.php';
$user_id=intval($_POST['user_id']);
$list_course_all_info=array();
$list_course=array();
$list_course_all_info=CourseManager::get_courses_list_by_user_id($user_id);
for ($i=0;$i<count($list_course_all_info);$i++) {
	$list_course[]=$list_course_all_info[$i]['title'];
}
?>
<table width="200" border="0" cellspacing="2" cellpadding="2">
<?php
if (count($list_course)==0) {
?>
<tr>
	<td><?php echo api_ucfirst((get_lang('HaveNoCourse'))); ?></td>
</tr>
<?php
}
?>
<?php for($k=0;$k<count($list_course);$k++) { ?>
    <tr>
        <td><?php echo api_convert_encoding($list_course[$k],'UTF-8',$charset);?></td>
    </tr>
<?php }?>	
</table>

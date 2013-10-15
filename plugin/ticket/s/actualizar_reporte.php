<?php
require_once '../../../main/inc/global.inc.php';
$work_id = $_POST['work_id'];
$forum_id = $_POST['forum_id'];
$rs_id = $_POST['rs_id'];
api_protect_course_script();
if (!api_is_allowed_to_edit()){
	Display::display_error_message("Acceso Denegado");
}else{
	$sql ="UPDATE ".Database::get_main_table('rp_reporte_semanas')." SET work_id = '$work_id' , forum_id = '$forum_id' WHERE  id ='$rs_id'";
	Database::query($sql);
	Display::display_confirmation_message("Se actualizo con exito");
}
?>
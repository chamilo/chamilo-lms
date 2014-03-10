<?php
require_once '../config.php';
$plugin = TicketPlugin::create();

api_protect_course_script();
if (!api_is_allowed_to_edit()){
	api_not_allowed();
}
$course_info = api_get_course_info();
$course_code = $course_info['code'];
    echo '<form action="tutor.php" name="asignar" id ="asignar">';
    echo '<div id="confirmacion"></div>';
    $id = $_GET['id'];
    $table_reporte_semanas = Database::get_main_table('rp_reporte_semanas');
    $sql ="SELECT * FROM $table_reporte_semanas WHERE id = '$id'";
    $sql_tareas = "SELECT id AS colid, title as coltitle FROM ".Database::get_course_table(TABLE_STUDENT_PUBLICATION , $course_info['dbName'])." WHERE parent_id = 0
        		   AND id NOT IN (SELECT work_id FROM $table_reporte_semanas WHERE course_code = '$course_code' AND id != '$id')";
    $sql_foros = "SELECT thread_id AS colid, thread_title AS coltitle FROM ".Database::get_course_table(TABLE_FORUM_THREAD, $course_info['dbName'])."
    			  WHERE thread_id NOT IN (SELECT forum_id FROM $table_reporte_semanas WHERE course_code = '$course_code' AND id != '$id')";
    $rs = Database::fetch_object(Database::query($sql));
    $result_tareas = Database::query($sql_tareas);
    $result_foros = Database::query($sql_foros);

   echo '<div class="row">
   		<input type="hidden" id="rs_id" name ="rs_id" value="'.$id.'">
    	<div class="formw">Seleccione la Tarea</div>	
    	</div>';
    echo '<div class="row"><div class="formw"><select name ="work_id" id="work_id">';
    	echo '<option value="0"'.(($row['colid']==$rs->work_id)?"selected":"").'>'.'Seleccione'.'</option>';
    	while($row = Database::fetch_assoc($result_tareas)){
    		echo '<option value="'.$row['colid'].'"'.(($row['colid']==$rs->work_id)?"selected":"").'>'.$row['coltitle'].'</option>';
    	}
    echo '</select></div><div>';
    echo '<div class="row">
    	<div class="formw">Seleccione el tema</div>
    	</div>';
    echo '<div class="row"><div class="formw"><select name ="forum_id" id="forum_id">';
    echo '<option value="0"'.(($row['colid']==$rs->work_id)?"forum_id":"").'>'.'Seleccione'.'</option>';    
    while($row = Database::fetch_assoc($result_foros)){
    	echo '<option value="'.$row['colid'].'"'.(($row['colid']==$rs->forum_id)?"selected":"").'>'.$row['coltitle'].'</option>';
    }
    echo '</select></div><div>';
    echo '<div class="row">
    		<div class="formw"><button class="save" name="editar" type="button" value="Editar" onClick="save('."$id".');">Editar</button></div>	
    	  </div>';
  	echo '</form>';
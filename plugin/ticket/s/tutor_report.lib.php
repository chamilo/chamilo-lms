<?php
function inicializarReporte($course_code){
	$course_info = api_get_course_info($course_code);
	$table_reporte_semanas = Database::get_main_table('rp_reporte_semanas');
	$table_students_report = Database::get_main_table('rp_students_report');
	$table_semanas_curso = Database::get_main_table('rp_semanas_curso');
	$table_course_rel_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
	$table_post = Database::get_course_table(TABLE_FORUM_POST, $course_info['dbName']);
	$table_work = Database::get_course_table(TABLE_STUDENT_PUBLICATION, $course_info['dbName']);
	$res = Database::query("SELECT COUNT(*) as cant FROM $table_reporte_semanas WHERE course_code = '".$course_code."'");
	$sql_semanas = "SELECT semanas FROM $table_semanas_curso WHERE course_code = '$course_code'";
	$res_semanas = Database::query($sql_semanas);
	$semanas = Database::fetch_object($res_semanas);
	$obj = Database::fetch_object($res);
	$numero_semanas = (!isset($_POST['numerosemanas']))?(($semanas->semanas==0)?7:$semanas->semanas):$_POST['numerosemanas'];
	Database::query("REPLACE INTO $table_semanas_curso (course_code , semanas) VALUES ('$course_code','$numero_semanas')");
	if(intval($obj->cant) != $numero_semanas){
		
		if(intval($obj->cant) > $numero_semanas){
			 $sql ="DELETE FROM $table_reporte_semanas WHERE  week_id > $numero_semanas AND course_code = '$course_code'";
			 Database::query("DELETE FROM $table_reporte_semanas WHERE  week_id > $numero_semanas AND course_code = '$course_code'");
		}else{
			for ($i = $obj->cant+1 ; $i <= $numero_semanas ; $i++){
				if(!Database::query("INSERT INTO $table_reporte_semanas (week_id,course_code,forum_id,work_id,quiz_id,pc_id)
						VALUES ('$i','$course_code','0','0','0','0' )")){							
						return false;
				}
			}
		}		
	}
	
	$sql = "REPLACE INTO $table_students_report (user_id,week_report_id, work_ok , thread_ok , quiz_ok , pc_ok)
			SELECT cu.user_id, rs.id, 0, 0, 0, 0
			FROM $table_course_rel_user cu
			LEFT JOIN $table_reporte_semanas rs ON cu.course_code = rs.course_code
			WHERE cu.status = '5' AND rs.course_code = '$course_code'
			ORDER BY cu.user_id, rs.id";
	if(!Database::query($sql)){
		return false;
	}else{
		$pagina = (!isset($_GET['page']))?1:$_GET['page'];
		Database::query("UPDATE $table_students_report sr SET sr.work_ok = 1
		WHERE CONCAT (sr.user_id,',',sr.week_report_id)
		IN (SELECT DISTINCT CONCAT(w.user_id,',',rs.id)
		FROM $table_work w  JOIN $table_reporte_semanas rs ON w.parent_id = rs.work_id)");
		Database::query("UPDATE $table_students_report sr SET sr.thread_ok = 1
		WHERE CONCAT (sr.user_id,',',sr.week_report_id)
		IN (SELECT DISTINCT CONCAT(f.poster_id,',',rs.id)
		FROM $table_post f  JOIN $table_reporte_semanas rs ON f.thread_id = rs.forum_id)");
		return mostrarResultados($course_info,$numero_semanas,$pagina);
	}
	
}
function mostrarResultados($course_info,$numero_semanas, $pagina){
	$course_code = $course_info['code'];
	$table_reporte_semanas = Database::get_main_table('rp_reporte_semanas');
	$table_students_report = Database::get_main_table('rp_students_report');
	$table_course_rel_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
	$table_user = Database::get_main_table(TABLE_MAIN_USER );
	$table_thread = Database::get_course_table(TABLE_FORUM_THREAD, $course_info['dbName']);
	$table_work = Database::get_course_table(TABLE_STUDENT_PUBLICATION, $course_info['dbName']);
	
	$resultados = array();
	$table_export = array();
	$sql_encabezado = "SELECT rs.id as id,rs.week_id, w.title AS work_title,  t.thread_title ,'EVALUACION' as eval_title ,'QUIZ' as pc_title
						FROM $table_reporte_semanas rs
						LEFT JOIN $table_thread t ON t.thread_id =  rs.forum_id
						LEFT JOIN $table_work w ON w.id = rs.work_id
						WHERE rs.course_code = '$course_code'
						ORDER BY rs.week_id";
	$result_encabezado = Database::query($sql_encabezado) ;
	$ids = array();
	$fila = '<tr>
		<th ></th>';
	$fila_export_encabezado = array(null,null);
	$fila_export_encabezado2 = array(null,ull);
	while ($rowe = Database::fetch_assoc($result_encabezado)){
		$fila_export_encabezado[] =  utf8_decode('Tarea'.$rowe['week_id']);
		$fila_export_encabezado[] =  utf8_decode('Foro'.$rowe['week_id']);
		//$fila_export_encabezado[] =  utf8_decode('Eval'.$rowe['week_id']);
		//$fila_export_encabezado[] =  utf8_decode('PC'.$rowe['week_id']);
		$fila_export_encabezado2[] = utf8_decode($rowe['work_title']);
		$fila_export_encabezado2[] = utf8_decode($rowe['thread_title']);
		//$fila_export_encabezado2[] = utf8_decode($rowe['eval_title']);
		//$fila_export_encabezado2[] = utf8_decode($rowe['pc_title']);
		$fila_export = array('Tarea'.$rowe['week_id'],'Foro'.$rowe['week_id'],'Eval'.$rowe['week_id'],'PC'.$rowe['week_id']);
		if ($rowe['week_id'] > (($pagina-1)*7) &&  $rowe['week_id'] <= (7*$pagina)){
			$ids[$rowe['week_id']] = $rowe['id'];
			$fila.='<th>
				<a href="#" onClick="mostrarContenido('."'tarea".$rowe['week_id']."'".');">Tarea'.$rowe['week_id'].'
						<div class="blackboard_hide" id="tarea'.$rowe['week_id'].'">'.$rowe['work_title'].'</div>
				</a></th>';
			$fila.= '<th>
				<a href="#" onClick="mostrarContenido('."'foro".$rowe['week_id']."'".');">Foro'.$rowe['week_id'].'
						<div class="blackboard_hide" id="foro'.$rowe['week_id'].'">'.$rowe['thread_title'].'</div>
				</a>
				</th>';
			/*$fila.= '<th>
				<a href="#" onClick="mostrarContenido('."'eval".$rowe['week_id']."'".');">Eval'.$rowe['week_id'].'
					<div class="blackboard_hide" id="eval'.$rowe['week_id'].'">'.$rowe['eval_title'].'</div>
				</a>
				</th>';
			$fila.= '<th>
				<a href="#" onClick="mostrarContenido('."'pc".$rowe['week_id']."'".');">PC'.$rowe['week_id'].'
					<div class="blackboard_hide" id="pc'.$rowe['week_id'].'">'.$rowe['pc_title'].'</div>
				</a>
				</th>';*/
		}
		
	}
	$table_export[] = $fila_export_encabezado;
	$table_export[] = $fila_export_encabezado2;
	$fila.=  '</tr>';
	
	$html = '<form action="tutor.php" name="semanas" id="semanas" method="POST">
			<div class="row">
			Seleccione cantidad de semanas: 
			<select name="numerosemanas" id="numerosemanas" onChange="submit();">
			<option value="7" '.(($numero_semanas ==7)?'selected="selected"':"").'>7 Semanas</option>
			<option value="14" '.(($numero_semanas ==14)?'selected="selected"':"").'>14 Semanas</option>
			</select>';
	
	
	if($numero_semanas == 14) {
		$html .= '<span style="float:right;"><a href="tutor.php?page='.(($pagina == 1)?2:1).'">'.(($pagina == 1)?"Siguiente":"Anterior").'</a></span>';		
	}
	$html .= '<span style="float:right;"><a href="'.api_get_self().'?action=export'.$get_parameter.$get_parameter2.'">'.Display::return_icon('import_excel.png',get_lang('Export'),'','32').'</a></span>';
	
	$html .=	'</form>';
	$html .= '<table class="reportes">';
	$html .= '<tr>
			<th ></th>';
	for ($i=(7*$pagina-6); $i <= $pagina*7;$i++){
		$html .= '<th colspan="2">Semana '.$i.'<a href="asignar_tareas.php?id='.$ids[$i].'" class="ajax">'.Display::return_icon('edit.png', get_lang('Edit'),  array('width'=>'16','height'=>'16'), 22).'</a></th>';
	}
	$html .=  '</tr>';
	$html .= $fila;
	$sql = "SELECT u.username , u.user_id , CONCAT(u.lastname,' ', u.firstname ) as fullname , rs.week_id , sr.work_ok ,sr.thread_ok , sr.quiz_ok , sr.pc_ok , rs.course_code
			FROM $table_students_report sr
			JOIN $table_reporte_semanas rs ON sr.week_report_id = rs.id
			JOIN $table_user u ON u.user_id = sr.user_id
			WHERE rs.course_code = '$course_code'
			ORDER BY u.lastname , u.username , rs.week_id
	";
	$result = Database::query($sql);
	while ($row = Database::fetch_assoc($result)){
		$resultadose[$row['username']][$row['week_id']] = $row;
		if ($row['week_id'] > (($pagina-1)*7) &&  $row['week_id'] <= (7*$pagina) ){
			$resultados[$row['username']][$row['week_id']] = $row;
			if(count($resultados[$row['username']]) == 7 ){
				$html.= mostrarResultadoAlumno($resultados[$row['username']],$pagina);
			}
		}
		if(count($resultadose[$row['username']]) == $numero_semanas ){
				$table_export[] = mostrarResultadoAlumnoExportar($resultadose[$row['username']],$numero_semanas);
		}
	}
	$html .= '
		  </table>';
	return array('mostrar'=>$html,'exportar'=>$table_export);
}
function mostrarResultadoAlumno($datos,$pagina){
	$inicio = (7*$pagina-6);
	$fila = '<tr>';
	
	$fila.= '<td><a href="'.api_get_path(WEB_CODE_PATH).'user/userInfo.php?'.api_get_cidreq().'&uInfo='.$datos[$inicio]['user_id'].'">'.$datos[$inicio]['username'].'</a></td>';
	foreach ($datos as $dato){
			$fila.= '<td align="center">'.(($dato['work_ok']==1)?Display::return_icon('check.png'):Display::return_icon('aspa.png')).'</td>';
			$fila.= '<td align="center">'.(($dato['thread_ok']==1)?Display::return_icon('check.png'):Display::return_icon('aspa.png')).'</td>';
			//$fila.= '<td>'.(($dato['quiz_ok']==1)?Display::return_icon('check.png'):Display::return_icon('aspa.png')).'</td>';
			//$fila.= '<td>'.(($dato['pc_ok']==1)?Display::return_icon('check.png'):Display::return_icon('aspa.png')).'</td>';
	}
	$fila.= '</tr>';	
	return $fila;
}
function mostrarResultadoAlumnoExportar($datos ,$numero_semanas){
	$fila = array();
	$fila[] = utf8_decode($datos[1]['username']);
	$fila[]=  utf8_decode($datos[1]['fullname']);
	foreach ($datos as $dato){
		//if ($datos['week_id'] < $numero_semanas){
			$fila[]= ($dato['work_ok']==1)?"SI":"NO";
			$fila[]= ($dato['thread_ok']==1)?"SI":"NO";
			//$fila[]= ($dato['quiz_ok']==1)?"SI":"NO";
			//$fila[]= ($dato['pc_ok']==1)?"SI":"NO";
		//}
	}
	return $fila;
}
?>
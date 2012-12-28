<?php
require '../../main/inc/global.inc.php';
$alumnos = array();
$sql = "SELECT f.field_value, u.user_id from user_field_values f INNER JOIN user u ON u.user_id=f.user_id WHERE f.field_id='13' AND u.status=5";
$res = mysql_query($sql);
file_put_contents('/tmp/alumnos.sql','CREATE TABLE chamilo_alumno ( uididpersona char(36));'."\n", FILE_APPEND);
while ($row = mysql_fetch_array($res)) {
  file_put_contents('/tmp/alumnos.sql', 'INSERT INTO chamilo_alumno(uididpersona) values (\''.$row[0].'\');'."\n", FILE_APPEND);
  $alumnos[$row[1]] = $row[0];
}

$sql = "SELECT f.field_value, u.user_id from user_field_values f INNER JOIN user u ON u.user_id=f.user_id WHERE f.field_id='13' AND u.status=1";
$res = mysql_query($sql);
file_put_contents('/tmp/profesores.sql','CREATE TABLE chamilo_profesor ( uididpersona char(36));'."\n", FILE_APPEND);
while ($row = mysql_fetch_array($res)) {
  file_put_contents('/tmp/profesores.sql', 'INSERT INTO chamilo_profesor(uididpersona) values (\''.$row[0].'\');'."\n", FILE_APPEND);
  $alumnos[$row[1]] = $row[0];
}

$sql = "SELECT f.field_value from course_field_values f INNER JOIN course c ON c.code=f.course_code WHERE f.field_id='5'";
$res = mysql_query($sql);
file_put_contents('/tmp/cursos.sql','CREATE TABLE chamilo_curso ( uididcurso char(36));'."\n", FILE_APPEND);
while ($row = mysql_fetch_array($res)) {
  file_put_contents('/tmp/cursos.sql', 'INSERT INTO chamilo_curso (uididcurso) values (\''.$row[0].'\');'."\n", FILE_APPEND);
}

$branches = array(
  1 => '8F67B2B3-667E-4EBC-8605-766D2FF71B55',
  2 => '7379A7D3-6DC5-42CA-9ED4-97367519F1D9',
  3 => '30DE73B6-8203-4F81-96C8-3B27977BB924',
  4 => '8BA65461-60B5-4716-BEB3-22BC7B71BC09',
  5 => '257AD17D-91F7-4BC8-81D4-71EBD35A4E50',
);
$fielduid = 1;
$fieldbra = 3;
foreach ($branches as $idbranch => $branch) {
  // First, get all sessions with the given branch
  $sql = "SELECT s.id from session_field_values f INNER JOIN session s ON s.id=f.session_id WHERE f.field_id='$fieldbra' AND f.field_value = '$branch'";
  echo $sql."\n";
  $res = mysql_query($sql);
  file_put_contents('/tmp/programas'.$idbranch.'.sql','CREATE TABLE chamilo_programa'.$idbranch.' ( uididprograma char(36));'."\n", FILE_APPEND);
  file_put_contents('/tmp/matriculas'.$idbranch.'.sql','CREATE TABLE chamilo_matricula'.$idbranch.' ( uididprograma char(36), uididpersona char(36));'."\n", FILE_APPEND);
  while ($row = mysql_fetch_array($res)) {
    // Get uididprograma from programas (there should be only one by "programa")
    $sql2 = "SELECT f.field_value from session_field_values f WHERE f.field_id='$fielduid' AND f.session_id = ".$row[0];
    $res2 = mysql_query($sql2);
    $row2 = mysql_fetch_array($res2);
    file_put_contents('/tmp/programas'.$idbranch.'.sql', 'INSERT INTO chamilo_programa'.$idbranch.' (uididprograma) values (\''.$row2[0].'\');'."\n", FILE_APPEND);
    // get subscriptions from session_rel_user
    $sql3 = "SELECT id_session, id_user FROM session_rel_user WHERE id_session='".$row[0]."'";
    //echo $sql3."\n";
    $res3 = mysql_query($sql3);
    while ($row3 = mysql_fetch_array($res3)) {
      file_put_contents('/tmp/matriculas'.$idbranch.'.sql','INSERT INTO chamilo_matricula'.$idbranch.' (uididprograma, uididpersona) values (\''.$row2[0].'\',\''.$alumnos[$row3[1]].'\');'."\n", FILE_APPEND);
    }
  } 
}

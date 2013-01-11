<?php
/**
 * Init: config connection to MSSQL
 */
ini_set('display_errors',1);
ini_set('mssql.datetimeconvert',0);
$eol = "<br />";
if (PHP_SAPI == 'cli') {
  $eol = "\n";
}
/**
 * Load connect info
 */
//require 'config.dist.php';
$db_host = substr(htmlentities(empty($_POST['host'])?'':$_POST['host']),0,25);
$db_user = substr(htmlentities(empty($_POST['user'])?'':$_POST['user']),0,25);
$db_pass = substr(htmlentities(empty($_POST['pass'])?'':$_POST['pass']),0,25);
$db_name = substr(htmlentities(empty($_POST['name'])?'':$_POST['name']),0,25);
echo '<p><form action="" method="POST">';
echo '<table>';
echo '<tr><td>Host:</td><td><input type="text" name="host">'.(empty($db_host)?'':$db_host).'</input></td></tr>';
echo '<tr><td>User:</td><td><input type="text" name="user">'.(empty($db_user)?'':$db_user).'</input></td></tr>';
echo '<tr><td>Pass:</td><td><input type="text" name="pass">'.(empty($db_pass)?'':$db_pass).'</input></td></tr>';
echo '<tr><td>Base:</td><td><input type="text" name="name">'.(empty($db_name)?'':$db_name).'</input></td></tr>';
echo '<tr><td colspan="2"><input type="submit" name="ok" value="Test"></td></tr>';
echo '</table>';
echo '</form></p>';
/**
 * Try connecting
 */
$conn = @mssql_connect($db_host,$db_user,$db_pass,TRUE);
if ($conn == FALSE) {
  printf("Could not connect. MSSQL error: %s$eol",mssql_get_last_message());
  die();
}
echo "Connected$eol";
/**
 * Try querying
 */
mssql_select_db($db_name,$conn);
$sql = 'SELECT MAX(idt) FROM Transaccion';
$res = @mssql_query($sql,$conn);
if ($res === false) {
  echo "Error with query ".$sql.". MSSQL error: ".mssql_get_last_message().$eol;
  die();
}
$row = @mssql_fetch_array($res);
print_r($row);
echo $eol;
$sql = 'SELECT cast(uidIdAlumno as varchar(50)) FROM Alumno WHERE intIdAlumno = 62165';
$res = @mssql_query($sql,$conn);
if ($res === false) {
  echo "Error with query ".$sql.". MSSQL error: ".mssql_get_last_message().$eol;
  die();
}
$row = @mssql_fetch_array($res);
print_r($row);
echo $eol;

<?php
/**
 * This script gets users details of a given list of users
 * (given by e-mail) and prints the details in /tmp/list.txt
 * To enable script, prefix the first die(); with //
 * @package chamilo.cron.user_import
 */
/**
 * Initialization
 */
/* Example of input file:
sam@example.com
Matthew@example.com
HERMAN@example.com
*/
die();
//change filename depending on file containing mails list
$list = file('input.txt');
require_once '../../inc/global.inc.php';
$users = Database::get_main_table(TABLE_MAIN_USER);
$string='';
foreach ($list as $mail) {
  $mail = trim($mail);
  $sql = "SELECT user_id, official_code, firstname, lastname, email FROM $users WHERE email = '$mail'\n";
  $res = Database::query($sql);
  if ($res === false) { die(mysql_error());}
  if (Database::num_rows($res) == 0) {
    $string .= 'No encontrado;'.$row['email'];
  } else {
    $row = Database::fetch_assoc($res);
   $string .= $row['user_id'].';'.$row['email'].';'.$row['firstname'].';'.$row['lastname'].';'.$row['official_code']."\r\n";
  }
}
echo $string;
file_put_contents('/tmp/list.txt',$string);

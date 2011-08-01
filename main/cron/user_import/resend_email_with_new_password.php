<?php
/**
 * This script updates the passwords of a given list of users
 * (given by e-mail) and resends them their account creation
 * confirmation e-mail.
 * Note that the password generation has been simplified, which
 * means the password below is not really "safe"
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
//change filename depending on file containing mails list, with one e-mail per line.
$list = file('input.txt');
$language_file = array ('admin', 'registration');
require_once '../../inc/global.inc.php';
$users = Database::get_main_table(TABLE_MAIN_USER);
/**
 * E-mails list loop
 */
foreach ($list as $mail) {
  $mail = trim($mail);
  $sql = "SELECT user_id, official_code, firstname, lastname, email, username, language FROM $users WHERE email = '$mail'\n";
  $res = Database::query($sql);
  if ($res === false) { echo 'Error in database with email '.$mail."\n";}
  if (Database::num_rows($res) == 0) {
    echo '[Error] Email not found in database: '.$row['email']."\n";
  } else {
    $row = Database::fetch_assoc($res);
    $pass = api_substr($row['username'], 0,4).rand(0,9).rand(0,9);
    $crypass = api_get_encrypted_password($password);
    $sqlu = "UPDATE $users SET password='$crypass' WHERE user_id = ".$row['user_id'];
    $resu = Database::query($sqlu);
    if ($resu === false ) {
      echo "[Error] Error updating password. Skipping $mail\n";
      continue;
    }
    $user = array('FirstName'=>$row['firstname'],'LastName'=>$row['lastname'],'UserName'=>$row['username'],'Password'=>$pass,'Email'=>$mail);
    $l = api_get_interface_language();
    if (!empty($row['language'])) {
      $l = $row['language'];
    }
    //This comes from main/admin/user_import.php::save_data() slightly modified
    $recipient_name = api_get_person_name($user['FirstName'], $user['LastName'], null, PERSON_NAME_EMAIL_ADDRESS);
    $emailsubject = '['.api_get_setting('siteName').'] '.get_lang('YourReg',null,$l).' '.api_get_setting('siteName');
    $emailbody = get_lang('Dear',null,$l).' '.api_get_person_name($user['FirstName'], $user['LastName']).",\n\n".get_lang('YouAreReg',null,$l)." ".api_get_setting('siteName')." ".get_lang('WithTheFollowingSettings',null,$l)."\n\n".get_lang('Username',null,$l)." : ".$user['UserName']."\n".get_lang('Pass',null,$l)." : ".$user['Password']."\n\n".get_lang('Address',null,$l)." ".api_get_setting('siteName')." ".get_lang('Is',null,$l)." : ".api_get_path(WEB_PATH)." \n\n".get_lang('Problem',null,$l)."\n\n".get_lang('Formula',null,$l).",\n\n".api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n".get_lang('Manager',null,$l)." ".api_get_setting('siteName')."\nT. ".api_get_setting('administratorTelephone')."\n".get_lang('Email',null,$l)." : ".api_get_setting('emailAdministrator')."";
    $sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
    $email_admin = api_get_setting('emailAdministrator');
    @api_mail($recipient_name, $user['Email'], $emailsubject, $emailbody, $sender_name, $email_admin); 
    echo "[OK] Sent to $mail with new password $pass (encrypted:$crypass)... w/ subject: $emailsubject\n";
  }
}

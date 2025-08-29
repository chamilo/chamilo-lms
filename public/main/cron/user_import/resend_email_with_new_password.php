<?php

/**
 * This script updates the passwords of a given list of users
 * (given by e-mail) and resends them their account creation
 * confirmation e-mail.
 * Note that the password generation has been simplified, which
 * means the password below is not really "safe"
 * To enable script, prefix the first die(); with //.
 */
/**
 * Initialization.
 */
/* Example of input file:
  sam@example.com
  Matthew@example.com
  HERMAN@example.com
 */
exit();
//change filename depending on file containing mails list, with one e-mail per line.
$list = file('input.txt');
require_once '../../inc/global.inc.php';
$users = Database::get_main_table(TABLE_MAIN_USER);
$userManager = UserManager::getRepository();
$repository = UserManager::getRepository();

/**
 * E-mails list loop.
 */
foreach ($list as $mail) {
    $mail = trim($mail);
    $sql = "SELECT user_id, official_code, firstname, lastname, email, username, language
            FROM $users WHERE email = '$mail'\n";
    $res = Database::query($sql);
    if (false === $res) {
        echo 'Error in database with email '.$mail."\n";
    }
    if (0 == Database::num_rows($res)) {
        echo '[Error] Email not found in database: '.$row['email']."\n";
    } else {
        $row = Database::fetch_assoc($res);
        $pass = api_substr($row['username'], 0, 4).rand(0, 9).rand(0, 9);

        if ($user) {
            $user = api_get_user_entity($row['user_id']);
            $user->setPlainPassword($pass);
            $userManager->updateUser($user, true);
        } else {
            echo "[Error] Error updating password. Skipping $mail\n";
            continue;
        }

        $user = [
            'FirstName' => $row['firstname'],
            'LastName' => $row['lastname'],
            'UserName' => $row['username'],
            'Password' => $pass,
            'Email' => $mail,
        ];
        $l = api_get_language_isocode();
        if (!empty($row['language'])) {
            $l = $row['language'];
        }
        //This comes from main/admin/user_import.php::save_data() slightly modified
        $recipient_name = api_get_person_name(
            $user['FirstName'],
            $user['LastName'],
            null,
            PERSON_NAME_EMAIL_ADDRESS
        );
        $emailsubject = '['.api_get_setting('siteName').'] '.get_lang('Your registration on').' '.api_get_setting('siteName');
        $emailbody = sprintf(get_lang('Dear %s,'), api_get_person_name($user['FirstName'], $user['LastName']))."\n\n".get_lang('You are registered to')." ".api_get_setting('siteName')." ".get_lang('with the following settings:')."\n\n".get_lang('Username')." : ".$user['UserName']."\n".get_lang('Password')." : ".$user['Password']."\n\n".get_lang('Address')." ".api_get_setting('siteName')." ".get_lang('is')." : ".api_get_path(WEB_PATH)." \n\n".get_lang('In case of trouble, contact us.')."\n\n".get_lang('Formula').",\n\n".api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n".get_lang('Administrator')." ".api_get_setting('siteName')."\nT. ".api_get_setting('administratorTelephone')."\n".get_lang('E-mail')." : ".api_get_setting('emailAdministrator');
        $sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
        $email_admin = api_get_setting('emailAdministrator');
        @api_mail_html(
            $recipient_name,
            $user['Email'],
            $emailsubject,
            $emailbody,
            $sender_name,
            $email_admin
        );
        echo "[OK] Sent to $mail with new password $pass (encrypted:$crypass)... w/ subject: $emailsubject\n";
    }
}

<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

if (PHP_SAPI != 'cli') {
    exit('Run this script through the command line or comment this line in the code');
}

$urlList = UrlManager::get_url_data();

$defaultSenderId = 1;

// Loop all portals
foreach ($urlList as $url) {
    // Set access_url in order to get the correct url links and admins
    $_configuration['access_url'] = $url['id'];

    $sql = '';
    $user_table = Database::get_main_table(TABLE_MAIN_USER);
    $admin_table = Database::get_main_table(TABLE_MAIN_ADMIN);

    $sql .= "SELECT u.id, v.updated_at FROM $user_table u";

    // adding the filter to see the user's only of the current access_url
    if (api_get_multiple_access_url()) {
        $access_url_rel_user_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $sql .= " INNER JOIN $access_url_rel_user_table url_rel_user 
                   ON (u.id = url_rel_user.user_id)";
    }

    $extraFields = UserManager::createDataPrivacyExtraFields();
    $extraFieldId = $extraFields['delete_legal'];
    $extraFieldIdDeleteAccount = $extraFields['delete_account_extra_field'];

    $extraFieldValue = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
    $sql .= " INNER JOIN $extraFieldValue v
              ON (
                    u.id = v.item_id AND 
                    (field_id = $extraFieldId OR field_id = $extraFieldIdDeleteAccount) AND
                    v.value = 1
              ) ";

    $sql .= " WHERE 1 = 1 ";

    if (api_get_multiple_access_url()) {
        $sql .= " AND url_rel_user.access_url_id = ".api_get_current_access_url_id();
    }

    $numberOfDays = 7;
    $date = new DateTime();
    $date->sub(new \DateInterval('P'.$numberOfDays.'D'));
    $dateToString = $date->format('Y-m-d h:i:s');
    $sql .= " AND v.updated_at < '$dateToString'";

    $url = api_get_path(WEB_CODE_PATH).'admin/user_list_consent.php';
    $link = Display::url($url, $url);
    $subject = get_lang('UserRequestWaitingForAction');

    $email = api_get_configuration_value('data_protection_officer_email');

    $message = 'Checking requests from '.strip_tags(Display::dateToStringAgoAndLongDate($dateToString))."\n";

    $result = Database::query($sql);
    while ($user = Database::fetch_array($result, 'ASSOC')) {
        $userId = $user['id'];
        $userInfo = api_get_user_info($userId);
        if ($userInfo) {
            $content = sprintf(
                get_lang('TheUserXIsWaitingForAnActionGoHereX'),
                $userInfo['complete_name'],
                $link
            );

            if (!empty($email)) {
                api_mail_html('', $email, $subject, $content);
            } else {
                MessageManager::sendMessageToAllAdminUsers($defaultSenderId, $subject, $content);
            }

            $date = strip_tags(Display::dateToStringAgoAndLongDate($user['updated_at']));
            $message .= "User ".$userInfo['complete_name_with_username']." is waiting for an action since $date \n";
        }
    }
    echo $message;
}

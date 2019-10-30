<?php
/* For licensing terms, see /license.txt */

/**
 * This script checks if the default extra fields are present in the platform.
 * If a default extra field doesn't exists then it will created.
 * Extra field list as in 1.11.8
 */

exit;

require_once __DIR__.'/../../main/inc/global.inc.php';

$em = Database::getManager();
$repo = $em->getRepository('ChamiloCoreBundle:ExtraField');
$extraFields = $repo->findAll();

$list = [
    'legal_accept' => "INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'legal_accept','Legal',0,0, NOW());",
    'already_logged_in' => "INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'already_logged_in','Already logged in',0,0, NOW());",
    'update_type' => "INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'update_type','Update script type',0,0, NOW())",
    'rssfeeds' => "INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'rssfeeds','RSS',0,0, NOW())",
    'dashboard' => "INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'dashboard', 'Dashboard', 0, 0, NOW())",
    'timezone' => "INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 11, 'timezone', 'Timezone', 0, 0, NOW())",
    'user_chat_status' => "INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'user_chat_status','User chat status',0,0, NOW())",
    'google_calendar_url' => "INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'google_calendar_url','Google Calendar URL',0,0, NOW())",
    'special_course' => "INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, default_value, created_at) VALUES (2, 13, 'special_course', 'Special course', 1 , 1, '', NOW())",
    'video_url' => "INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (2, 19, 'video_url', 'VideoUrl', 1, 1, NOW())",
    'image' => "INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (3, 16, 'image', 'Image', 1, 1, NOW())",
    'captcha_blocked_until_date' => "INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'captcha_blocked_until_date', 'Account locked until', 0, 0, NOW())",
];

$extraFieldList = [];
/** @var \Chamilo\CoreBundle\Entity\ExtraField $extraField */
foreach ($extraFields as $extraField) {
    $extraFieldList[$extraField->getVariable()] =  $extraField;
}
$extraFieldVariableList = array_keys($extraFieldList);

$queriesExecuted = [];
foreach ($list as $variable => $sql) {
    if (!in_array($variable, $extraFieldVariableList)) {
        Database::query($sql);
        $queriesExecuted[] = $sql;
    }
}

if (!isset($extraFieldList['mail_notify_invitation'])) {
    $sql = "INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, default_value, created_at) values (1, 4, 'mail_notify_invitation',   'MailNotifyInvitation',0,1,'1', NOW())";
    Database::query($sql);
    $queriesExecuted[] = $sql;
    $id = Database::insert_id();
    $sql = "INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES ($id, '1', 'AtOnce',1)";
    Database::query($sql);
    $queriesExecuted[] = $sql;
    $sql = "INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES ($id, '8', 'Daily',2)";
    Database::query($sql);
    $queriesExecuted[] = $sql;
    $sql = "INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES ($id, '0', 'No',3)";
    Database::query($sql);
    $queriesExecuted[] = $sql;
}

if (!isset($extraFieldList['mail_notify_message'])) {
    $sql = "INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, default_value, created_at) values (1, 4, 'mail_notify_message',      'MailNotifyMessage',0,1,'1', NOW())";
    Database::query($sql);
    $queriesExecuted[] = $sql;

    $id = Database::insert_id();
    if ($id) {
        $sql = "INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES ($id, '1', 'AtOnce',1)";
        Database::query($sql);
        $queriesExecuted[] = $sql;

        $sql = "INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES ($id, '8', 'Daily',2)";
        Database::query($sql);
        $queriesExecuted[] = $sql;

        $sql = "INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES ($id, '0', 'No',3)";
        Database::query($sql);
        $queriesExecuted[] = $sql;
    }
}

if (!isset($extraFieldList['mail_notify_group_message'])) {
    $sql = "INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, default_value, created_at) values (1, 4, 'mail_notify_group_message','MailNotifyGroupMessage',0,1,'1', NOW())";
    Database::query($sql);
    $queriesExecuted[] = $sql;
    $id = Database::insert_id();
    if ($id) {
        $sql = "INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES ($id, '1', 'AtOnce',1)";
        Database::query($sql);
        $queriesExecuted[] = $sql;
        $sql = "INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES ($id, '8', 'Daily',2)";
        Database::query($sql);
        $queriesExecuted[] = $sql;
        $sql = "INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES ($id, '0', 'No',3)";
        Database::query($sql);
        $queriesExecuted[] = $sql;
    }
}

$tag1Exists = false;
$tag2Exists = false;

foreach ($extraFields as $extraField) {
    if ($extraField->getVariable() === 'tags' && $extraField->getExtraFieldType() == 1) {
        $tag1Exists = true;
    }
    if ($extraField->getVariable() === 'tags' && $extraField->getExtraFieldType() == 2) {
        $tag2Exists = true;
    }
}

if ($tag1Exists === false) {
    $sql = "INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 10, 'tags','tags',0,0, NOW())";
    Database::query($sql);
    $queriesExecuted[] = $sql;
}

if ($tag2Exists === false) {
    $sql = "INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (2, 10, 'tags', 'Tags', 1, 1, NOW())";
    Database::query($sql);
    $queriesExecuted[] = $sql;
}

if (empty($queriesExecuted)) {
    echo 'No database changes';
    exit;
}

foreach ($queriesExecuted as $query) {
    echo $query.PHP_EOL;
}

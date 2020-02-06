<?php

/* For license terms, see /license.txt */

exit;

require_once __DIR__.'/../../main/inc/global.inc.php';

$defaultValue = 'xxx';
$variable = 'careerid';

$extraField = new ExtraField('session');
$extraFieldInfo = $extraField->get_handler_field_info_by_field_variable($variable);

if (empty($extraFieldInfo)) {
    echo 'Extra field careerid not found';
    exit;
}

$extraFieldValue = new ExtraFieldValue('session');
$sql = 'SELECT * FROM session ';
$result = Database::query($sql);
while ($row = Database::fetch_array($result)) {
    $sessionId = $row['id'];
    $value = $extraFieldValue->get_values_by_handler_and_field_id($sessionId, $extraFieldInfo['id']);
    if (empty($value)) {
        $params = ['item_id' => $sessionId, 'extra_'.$variable => $defaultValue];
        $extraFieldValue->saveFieldValues($params, true);
    }
}


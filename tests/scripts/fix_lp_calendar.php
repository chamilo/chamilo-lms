<?php

/* For licensing terms, see /license.txt */

exit;
require __DIR__ . '/../../main/inc/global.inc.php';

$extraField = new ExtraField('lp_item');
$extraFieldInfo = $extraField->get_handler_field_info_by_field_variable('calendar');
if (empty($extraFieldInfo)) {
    echo 'No calendar extra field';
    exit;
}

$extraFieldId = $extraFieldInfo['id'];

$sql = 'select iid from c_lp_item where title like "%(+)%"';
$result = Database::query($sql);
while ($row = Database::fetch_array($result)) {
    $lpItemId = $row['iid'];

    $extraField = new ExtraFieldValue('lp_item');
    $values = $extraField->get_values_by_handler_and_field_variable($lpItemId, 'calendar');

    if (empty($values)) {
        $value = 1;
        $params = [
            'field_id' => $extraFieldId,
            'value' => $value,
            'item_id' => $lpItemId,
        ];
        //var_dump($params);
 	echo 'LP item id added: '.$lpItemId.PHP_EOL;
	$extraField->save($params);
    }
}

exit;





<?php

/* For licensing terms, see /license.txt */

/**
 * Updates an user extra field based on other user extra field value
 * An array of rules needs to be defined : 
 * * the existing extra fields and their values
 * * the extra field to update and its value
 *
 */

use Chamilo\CoreBundle\Entity\ExtraField as EntityExtraField;

exit;

require __DIR__.'/../../main/inc/global.inc.php';

// Define the rules to follow for the update
$rules = [
    '1' => [
        'SourceExtraFieldName' => 'region',
	'SourceExtraFieldValue' => '("NAISAUKI", "ME")',
        'UpdateExtraFieldName' => 'segment', 
	'UpdateExtraFieldValue' => 'GEEMS'
    ],
    '2' => [
        'SourceExtraFieldName' => 'region', 
        'SourceExtraFieldValue' => '("IBILAT", "FAB", "GEE")', 
        'UpdateExtraFieldName' => 'segment',
        'UpdateExtraFieldValue' => 'EASA'
    ],
    '3' => [
        'SourceExtraFieldName' => 'country',
        'SourceExtraFieldValue' => '("CHINA", "JAPAN", "SOUTH KOREA")',
        'UpdateExtraFieldName' => 'segment',
        'UpdateExtraFieldValue' => 'CEA'
    ]
];

// Internal variables
$userExtraField = EntityExtraField::USER_FIELD_TYPE;
$ExtraFieldTable = Database::get_main_table(TABLE_EXTRA_FIELD);
$ExtraFieldValueTable = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);



foreach ($rules as $rule) {
    $sqlUserIds = "select item_id 
                   from " . $ExtraFieldValueTable . " 
                   where field_id in 
                       (select id from " . $ExtraFieldTable . " 
                        where variable = '". $rule['SourceExtraFieldName'] . "' 
                        and extra_field_type = '" . $userExtraField ."')
                   and value in " . $rule['SourceExtraFieldValue'] . ";";
    echo $sqlUserIds . PHP_EOL;
    $ResultUserIds = Database::query($sqlUserIds);
    while ($data = Database::fetch_array($ResultUserIds)) {
        // See tests/scripts/synchronize_user_base_from_ldap.php for global entities update at once for better performance if necessary
        echo "Updating extrafield " . $rule['UpdateExtraFieldName'] . " with value " . $rule['UpdateExtraFieldValue'] . " for user with user_id = " . $data['item_id'] . PHP_EOL;
        UserManager::update_extra_field_value(
            $data['item_id'],
            $rule['UpdateExtraFieldName'],
            $rule['UpdateExtraFieldValue']
        );

    }
}

?>

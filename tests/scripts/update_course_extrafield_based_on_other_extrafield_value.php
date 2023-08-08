<?php

/* For licensing terms, see /license.txt */

/**
 * Updates course extra fields based on the value of other extra field
 * An array is needed to define the existing extra fields and there values (the rules)
 *
 */

use Chamilo\CoreBundle\Entity\ExtraField as EntityExtraField;

exit;

require __DIR__.'/../../main/inc/global.inc.php';

// Define the rules to follow for the update
$rules = [
    '1' => [
        'SourceExtraFieldName' => 'region',
        'SourceExtraFieldValue' => '("NAISAUKI", "ME", "NA")',
        'UpdateExtraFieldName' => 'segment',
        'UpdateExtraFieldValue' => 'GEEMS'
    ],
    '2' => [
        'SourceExtraFieldName' => 'region',
        'SourceExtraFieldValue' => '("IBILAT", "FAB", "TR", "EE", "DE")',
        'UpdateExtraFieldName' => 'segment',
        'UpdateExtraFieldValue' => 'EASA'
    ],
    '3' => [
        'SourceExtraFieldName' => 'region',
        'SourceExtraFieldValue' => '("CN", "EA")',
        'UpdateExtraFieldName' => 'segment',
        'UpdateExtraFieldValue' => 'CEA'
    ]
];

// Internal variables
$courseExtraField = EntityExtraField::COURSE_FIELD_TYPE;
$ExtraFieldTable = Database::get_main_table(TABLE_EXTRA_FIELD);
$ExtraFieldValueTable = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);

foreach ($rules as $rule) {
    $sqlCourseIds = "select item_id 
                   from " . $ExtraFieldValueTable . " 
                   where field_id in 
                       (select id from " . $ExtraFieldTable . " 
                        where variable = '". $rule['SourceExtraFieldName'] . "' 
                        and extra_field_type = '" . $courseExtraField ."')
                   and value in " . $rule['SourceExtraFieldValue'] . ";";
    $ResultCourseIds = Database::query($sqlCourseIds);
    while ($data = Database::fetch_array($ResultCourseIds)) {
        // See tests/scripts/synchronize_user_base_from_ldap.php for global entities update at once for better performance if necessary
        $courseInfo = api_get_course_info_by_id($data['item_id']);
        $courseCode = $courseInfo['code'];
        $saved = CourseManager::update_course_extra_field_value($courseCode, $rule['UpdateExtraFieldName'], $rule['UpdateExtraFieldValue']);
        if ($saved) {
            echo "Updating extrafield " . $rule['UpdateExtraFieldName'] . " with value " . $rule['UpdateExtraFieldValue'] . " for course with code = " . $courseCode . PHP_EOL;
        }
    }
}

?>

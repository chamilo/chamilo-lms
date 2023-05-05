<?php

/* For licensing terms, see /license.txt */

/**
 * Bulk update course settings.
 */

exit;

require __DIR__.'/../../main/inc/global.inc.php';

// params

$settingsWithValues = [
   //'student_delete_own_publication' => 1,
];

$courseIdList = [
];

// process
if (empty($courseIdList)) {
    $result = Database::select(
        'id',
        Database::get_main_table(TABLE_MAIN_COURSE)
    );

    $courseIdList = array_column($result, 'id');
}

$appPlugin = new AppPlugin();

foreach ($courseIdList as $courseId) {
    echo "Course ID: $courseId".PHP_EOL;

    foreach ($settingsWithValues as $setting => $value) {
        CourseManager::saveCourseConfigurationSetting(
            $appPlugin,
            $setting,
            $value,
            $courseId
        );
        echo "\tSetting: $setting <- value: ".((string) $value).PHP_EOL;
    }
}

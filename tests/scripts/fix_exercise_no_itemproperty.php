<?php

/* For licensing terms, see /license.txt */

exit;

/**
 * Fixes course base exercises with no item property.
 */
error_reporting(0);

require_once __DIR__.'/../../main/inc/global.inc.php';

$sql = 'SELECT id FROM course ';
$result = Database::query($sql);
$fix = false;
$userId = 1;

while ($row = Database::fetch_array($result, 'ASSOC')) {
    $courseId = $row['id'];
    $courseInfo = api_get_course_info_by_id($courseId);
    if (empty($courseInfo)) {
        continue;
    }

    $exerciseList = ExerciseLib::get_all_exercises_for_course_id(
        $courseInfo,
        0,
        $courseId,
        false
    );

    if (empty($exerciseList)) {
        continue;
    }

    $counter = 0;
    $emptyCounter = 0;
    $fixed = 0;
    foreach ($exerciseList as $exerciseItem) {
        $oldId = $exerciseItem['id'];
        $exerciseId = $exerciseItem['iid'];

        if ($oldId <> $exerciseId) {
            continue;
        }

        $oldVisibility = api_get_item_visibility(
            $courseInfo,
            TOOL_QUIZ,
            $oldId,
            0
        );

        $visibility = api_get_item_visibility(
            $courseInfo,
            TOOL_QUIZ,
            $exerciseId,
            0
        );

        if (-1 === $visibility && -1 === $oldVisibility) {
            if ($fix) {
                api_item_property_update(
                    $courseInfo,
                    TOOL_QUIZ,
                    $exerciseId,
                    'QuizUpdated',
                    $userId,
                    null,
                    null,
                    null,
                    null,
                    0
                );
                echo "Fix exercise iid = $exerciseId / ".$exerciseItem['title'].PHP_EOL;
                $fixed++;
            }
            $emptyCounter++;
        }
        $counter++;
    }

    if ($emptyCounter > 0) {
        echo 'Course: '.$courseId.' - '.$courseInfo['title'].PHP_EOL.PHP_EOL;
        echo $emptyCounter.' / ' . $counter.PHP_EOL;
        echo 'To be fix: ' . $emptyCounter.PHP_EOL;
    }

    // Fix exercise in session
}


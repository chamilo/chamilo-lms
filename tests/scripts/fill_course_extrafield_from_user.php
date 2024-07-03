<?php
/* For license terms, see /license.txt */
/**
 * This script allows prefill the course extra fields related with
 * the user creator
 */
exit;
if (PHP_SAPI != 'cli') {
    die('This script can only be launched from the command line');
}

require_once __DIR__.'/../../main/inc/global.inc.php';

$fillExtraField = api_get_configuration_value('course_creation_user_course_extra_field_relation_to_prefill');

$courses = CourseManager::get_courses_list();
if (!empty($courses)) {
    foreach ($courses as $course) {
        $courseId = $course['id'];
        $courseCode = $course['code'];
        $creatorId = getCourseCreatorId($courseId);

        // Relation to prefill course extra field with user extra field
        if (false !== $fillExtraField && !empty($fillExtraField['fields'])) {
            foreach ($fillExtraField['fields'] as $courseVariable => $userVariable) {
                $extraValue = UserManager::get_extra_user_data_by_field($creatorId, $userVariable);
                if (isset($extraValue[$userVariable])) {
                    $saved = CourseManager::update_course_extra_field_value($courseCode, $courseVariable, $extraValue[$userVariable]);
                    if ($saved) {
                        echo "Updated $courseCode with creator user_id $creatorId, user_field_variable : $userVariable , user_field_value : {$extraValue[$userVariable]}".PHP_EOL;
                    }
                }
            }
        }
    }
}

/**
 * Get the user who creates the course
 *
 * @param $courseId
 * @return int
 */
function getCourseCreatorId($courseId):int
{
    $tblTrackDefault = Database::get_main_table(TABLE_STATISTIC_TRACK_E_DEFAULT);

    $sql = "SELECT
            default_user_id
        FROM $tblTrackDefault
        WHERE c_id = $courseId AND
              default_event_type = '".LOG_COURSE_CREATE."'";
    $rs = Database::query($sql);
    $creatorId = Database::result($rs, 0, 0);

    return $creatorId;
}


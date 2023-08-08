<?php
/* For license terms, see /license.txt */
/**
 * This script allows to update the course region extra field based on
 * * original course region extra field value
 * * the creator user's country extra field value
 */

exit;

if (PHP_SAPI != 'cli') {
    die('This script can only be launched from the command line');
}

require_once __DIR__.'/../../main/inc/global.inc.php';

// Define the rules to follow for the update
$rules = [
    '1' => [
        'oldRegion' => 'GEE',
        'creatorCountry' => ['Croatia', 'Armenia', 'Estonia', 'Georgia', 'Greece', 'Lithuania', 'North Macedonia', 'Poland', 'Romania'],
        'newRegion' => 'EE',
    ],
    '2' => [
        'oldRegion' => 'GEE',
        'creatorCountry' => ['Turkey', 'Arzebajian'],
        'newRegion' => 'TR',
    ],
    '3' => [
        'oldRegion' => 'GEE',
        'creatorCountry' => ['Germany'],
        'newRegion' => 'DE',
    ],
    '4' => [
        'oldRegion' => 'CHINA',
        'creatorCountry' => ['China'],
        'newRegion' => 'CN',
    ],
    '5' => [
        'oldRegion' => 'CHINA',
        'creatorCountry' => ['Japan', 'South Korea'],
        'newRegion' => 'EA',
    ],
    '6' => [
        'oldRegion' => 'NAISAUKI',
        'creatorCountry' => ['Canada', 'Ireland', 'Mexico', 'United States'],
        'newRegion' => 'NA',
    ],
    '7' => [
        'oldRegion' => 'NAISAUKI',
        'creatorCountry' => ['India', 'Kenya', 'Malaysia', 'Thailand', 'The Philippines'],
        'newRegion' => 'SEAKI',
    ]
];

$courses = CourseManager::get_courses_list();
if (!empty($courses)) {
    foreach ($courses as $course) {
        $regionUpdated = false;
        $courseId = $course['id'];
        $courseCode = $course['code'];
        $creatorId = getCourseCreatorId($courseId);
        $courseRegion = CourseManager::get_course_extra_field_value('region', $courseCode);
        $creatorUserExtraField = UserManager::get_extra_user_data_by_field($creatorId, 'country');
        $creatorUserCountry = $creatorUserExtraField['country'];
        $courseClient = CourseManager::get_course_extra_field_value('client', $courseCode);
        if (!empty($courseClient) && $courseClient == "Corporate" && !empty($courseRegion) && $courseRegion == "GEE") {
            $saved = CourseManager::update_course_extra_field_value($courseCode, 'region', 'GLOBAL');
            if ($saved) {
                echo "Updated $courseCode with client $courseClient, from old region : $courseRegion, to new region : GLOBAL".PHP_EOL;
            }
        } else {
            // Relation to prefill course extra field with user extra field
            if (false !== $creatorUserCountry && !empty($creatorUserCountry) && false !== $courseRegion && !empty($courseRegion)) {
                foreach ($rules as $rule) {
                    if ($courseRegion == $rule['oldRegion'] && in_array($creatorUserCountry,$rule['creatorCountry'])) {
                        if (isset($rule['newRegion'])) {
                            $regionUpdated = true;
                            $saved = CourseManager::update_course_extra_field_value($courseCode, 'region', $rule['newRegion']);
                            if ($saved) {
                                echo "Updated $courseCode with creator's country $creatorUserCountry, from old region : " . $rule['oldRegion'] . ", to new region : " . $rule['newRegion'].PHP_EOL;
                            }
                        }
                    }
                }
                if (!$regionUpdated) {
                    echo "Course $courseCode not updated because no rule is corresponding with creator's country $creatorUserCountry and region $courseRegion".PHP_EOL;
                }
            } else {
//                echo "Course $courseCode not updated because the course does not have a region or the creator of the course with user_id $creatorId does not have a country or the user has been deleted".PHP_EOL;
                echo "$courseClient;" . $course['title'] .PHP_EOL;
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


<?php

/* For licensing terms, see /license.txt */

use Symfony\Component\Filesystem\Filesystem;

exit;

/**
 * Update the code for a list of courses.
 *
 * Goes through all HTML files of the courses directory and replaces the current code by the new code.
 */

require __DIR__.'/../../main/inc/global.inc.php';

$list = [
    'CURRENTCODE' => 'NEWCODE',
];

foreach (replaceCodes($list) as $message) {
    echo time()." -- $message".PHP_EOL;
};

function replaceCodes(array $list): Generator
{
    foreach ($list as $currentCode => $newCode) {
        $currentCodeExists = CourseManager::course_code_exists($currentCode);

        if (!$currentCodeExists) {
            yield "Current course code '$currentCode' does not exist.";
            yield "Skipping conversion of '$currentCode' to '$newCode'. Please update codes mapping table to provide the correct code to be converted.";

            continue;
        }

        $newCodeExists = CourseManager::course_code_exists($newCode);

        if ($newCodeExists) {
            yield "New course code '$newCode' already exists.";
            yield "Skipping conversion of '$currentCode' to '$newCode'. Please update mapping table to provide a different code to be converted to.";

            continue;
        }

        $currentCourseInfo = api_get_course_info($currentCode);
        $currentDirectory = $currentCourseInfo['directory'];

        $newCourseKeys = AddCourse::define_course_keys($newCode);

        $newCode = $newCourseKeys['currentCourseCode'];
        $newVisualCode = $newCourseKeys['currentCourseId'];
        $newDirectory = $newCourseKeys['currentCourseRepository'];

        $newCode = CourseManager::generate_course_code($newCode);

        yield "New code to use for '$currentCode' is '$newCode' and its directory is '$newDirectory'";

        Database::update(
            'course',
            [
                'code' => $newCode,
                'visual_code' => $newVisualCode,
                'directory' => $newDirectory,
            ],
            ['code = ?' => [$currentCode]]
        );

        $tablesWithCode = [
            'course_rel_class' => ['course_code'],
            'course_request' => ['code'],
            'gradebook_category' => ['course_code'],
            'gradebook_evaluation' => ['course_code'],
            'gradebook_link' => ['course_code'],
            'search_engine_ref' => ['course_code'],
            'shared_survey' => ['course_code'],
            'specific_field_values' => ['course_code'],
            'templates' => ['course_code'],
        ];

        yield "Updating database tables new code";

        Database::query('SET foreign_key_checks = 0');

        foreach ($tablesWithCode as $tblName => $fieldNames) {
            foreach ($fieldNames as $fieldName) {
                Database::update(
                    $tblName,
                    [$fieldName => $newCode],
                    ["$fieldName = ?" => [$currentCode]]
                );
            }
        }

        yield "Replacing course code in exercises content";

        ExerciseLib::replaceTermsInContent("/courses/$currentDirectory/", "/courses/$newDirectory/");
        ExerciseLib::replaceTermsInContent("cidReq=$currentCode", "cidReq=$newCode");

        yield "Replacing course code in HTML files";

        $coursePath = api_get_path(SYS_COURSE_PATH);

        exec('find '.$coursePath.$currentDirectory.'/document/ -type f -name "*.html" -exec sed -i '."'s#/courses/$currentDirectory/#/courses/$newDirectory/#g' {} +");

        yield "Renaming course directory";

        $fs = new Filesystem();
        $fs->rename(
            $coursePath.$currentDirectory,
            $coursePath.$newDirectory
        );
    }

    yield "Done";
}

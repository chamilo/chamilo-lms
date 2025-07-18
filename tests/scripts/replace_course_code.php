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
$previewMode = false;
$updateFilesAndDirs = true;

foreach (replaceCodes($list) as $message) {
    echo time()." -- $message".PHP_EOL;
};

function replaceCodes(array $list): Generator
{
    global $updateFilesAndDirs, $previewMode;
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

        yield "New code to use for '$currentCode' is '$newCode' and its (new) directory is '$newDirectory'";

        if (false === $previewMode) {
            Database::update(
                'course',
                [
                    'code' => $newCode,
                    'visual_code' => $newVisualCode,
                    'directory' => $newDirectory,
                ],
                ['code = ?' => [$currentCode]]
            );
            yield "  Course table updated with new code and directory $newCode/$newDirectory";
        } else {
            yield "  Course table not updated: previewMode=true";
        }


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

        yield "Updating database tables. Replacing $currentCode with new code $newCode...";

        Database::query('SET foreign_key_checks = 0');

        if (false === $previewMode) {
            foreach ($tablesWithCode as $tblName => $fieldNames) {
                foreach ($fieldNames as $fieldName) {
                        Database::update(
                            $tblName,
                            [$fieldName => $newCode],
                            ["$fieldName = ?" => [$currentCode]]
                        );
                }
            }
        } else {
            yield "  Tables with course code not updated for $currentCode: previewMode=true";
        }

        yield "Replacing course code in database content";

        if (false === $previewMode) {
            api_replace_terms_in_content("/courses/$currentDirectory/", "/courses/$newDirectory/");
            api_replace_terms_in_content("cidReq=$currentCode&", "cidReq=$newCode&");
        } else {
            yield "  Content replacement not executed: previewMode=true";
        }

        yield "Replacing course code in HTML files";

        $coursePath = api_get_path(SYS_COURSE_PATH);

        if (false === $previewMode && true === $updateFilesAndDirs) {
            exec('find '.$coursePath.$currentDirectory.'/document/ -type f -name "*.html" -exec sed -i '."'s#/courses/$currentDirectory/#/courses/$newDirectory/#g' {} +");
            exec('find '.$coursePath.$currentDirectory.'/document/ -type f -name "*.html" -exec sed -i '."'s#cidReq=$currentCode\&#cidReq=$newCode\&#g' {} +");
        } else {
            yield "  File changes ignored (variable updateFilesAndDirs set to false)";
        }

        yield "Renaming course directory";

        if (false === $previewMode && true === $updateFilesAndDirs) {
            $fs = new Filesystem();
            $fs->rename(
                $coursePath.$currentDirectory,
                $coursePath.$newDirectory
            );
        } else {
            yield "  Directory changes ignored (variable updateFilesAndDirs set to false)";
        }
    }

    yield "Done";
}

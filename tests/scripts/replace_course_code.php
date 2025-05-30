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
            yield "Current course code '$currentCode' not exists";

            continue;
        }

        $newCodeExists = CourseManager::course_code_exists($newCode);

        if ($newCodeExists) {
            yield "New course code '$currentCode' already exists";

            continue;
        }

        $newCode = CourseManager::generate_course_code($newCode);

        yield "New code to use for '$currentCode' is '$newCode'";

        $tablesWithCode = [
            'course' => ['code', 'visual_code', 'directory'],
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

        ExerciseLib::replaceTermsInContent("/courses/$currentCode/", "/courses/$newCode/");

        yield "Replacing course code in HTML files";

        $coursePath = api_get_path(SYS_COURSE_PATH);

        exec('find '.$coursePath.$currentCode.'/document/ -type f -name "*.html" -exec sed -i '."'s#/courses/$currentCode/#/courses/$newCode/#g' {} +");

        yield "Renaming course directory";

        $fs = new Filesystem();
        $fs->rename(
            $coursePath.$currentCode,
            $coursePath.$newCode
        );
    }

    yield "Done";
}

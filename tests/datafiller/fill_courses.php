<?php

/* For licensing terms, see /license.txt */

/**
 * This script contains a data filling procedure for users
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 *
 */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\CourseHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;

/**
 * Loads the data and injects it into the Chamilo database, using the Chamilo
 * internal functions.
 * @return  array  List of user IDs for the users that have just been inserted
 */
function fill_courses(): array
{
    $courses = [];
    require_once 'data_courses.php'; // populates $courses

    $output = [];
    $output[] = ['title' => 'Courses Filling Report: '];

    $languages = array_column(SubLanguageManager::getAllLanguages(true), 'isocode');

    /** @var CourseHelper $courseHelper */
    $courseHelper = Container::$container->get(CourseHelper::class);
    /** @var CourseRepository $courseRepo */
    $courseRepo   = Container::$container->get(CourseRepository::class);

    $i = 1;
    foreach ($courses as $course) {
        // Keep original title for the report row
        $output[$i]['line-init'] = $course['title'];

        // Ensure required keys expected by CourseHelper
        $course['wanted_code'] = $course['code'];
        if (!in_array($course['course_language'], $languages)) {
            $course['course_language'] = 'en_US';
        }

        if ($courseRepo->courseCodeExists($course['wanted_code'])) {
            $output[$i]['line-info'] = get_lang('The course code already exists');
            $output[$i]['status']    = 'exists';
            $i++;
            continue;
        }

        // Create with the Symfony helper
        try {
            $created = $courseHelper->createCourse([
                'title' => $course['title'],
                'wanted_code' => $course['wanted_code'],
                'course_language' => $course['course_language'],
                'exemplary_content' => $course['exemplary_content'] ?? false,
                'illustration_path' => $course['illustration_path'] ?? null,
            ]);

            if ($created) {
                $output[$i]['line-info'] = get_lang('Added');
                $output[$i]['status']    = 'ok';
            } else {
                $output[$i]['line-info'] = get_lang('Not inserted');
                $output[$i]['status']    = 'error';
            }
        } catch (Throwable $e) {
            $output[$i]['line-info'] = get_lang('Not inserted');
            $output[$i]['status']    = 'error';
        }

        $i++;
    }

    return $output;
}

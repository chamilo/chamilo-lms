<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Activity;

/**
 * Class Course.
 */
class Course extends BaseActivity
{
    public function generate(): array
    {
        $course = api_get_course_entity();
        $session = api_get_session_entity();

        if (!$course) {
            return [];
        }

        $courseLanguage = method_exists($course, 'getCourseLanguage')
            ? $course->getCourseLanguage()
            : null;

        $languageIso = !empty($courseLanguage)
            ? api_get_language_isocode($courseLanguage)
            : 'en';

        $courseUrl = api_get_course_url(
            $course->getCode(),
            $session ? $session->getId() : 0
        );

        return [
            'objectType' => 'Activity',
            'id' => $courseUrl,
            'definition' => [
                'name' => [
                    $languageIso => (string) $course->getTitle(),
                ],
                'type' => 'http://id.tincanapi.com/activitytype/lms/course',
            ],
        ];
    }
}

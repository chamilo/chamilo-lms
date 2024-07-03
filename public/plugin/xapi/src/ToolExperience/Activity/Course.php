<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Activity;

use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Definition;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;

/**
 * Class Course.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Activity
 */
class Course extends BaseActivity
{
    public function generate(): Activity
    {
        $course = api_get_course_entity();
        $session = api_get_session_entity();

        $languageIso = api_get_language_isocode($course->getCourseLanguage());

        $courseUrl = api_get_course_url(
            $course->getCode(),
            $session ? $session->getId() : 0
        );

        return new Activity(
            IRI::fromString($courseUrl),
            new Definition(
                LanguageMap::create([$languageIso => $course->getTitle()]),
                null,
                IRI::fromString('http://id.tincanapi.com/activitytype/lms/course')
            )
        );
    }
}

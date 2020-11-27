<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course as CourseEntity;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\UserBundle\Entity\User as UserEntity;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\Definition;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\Uuid;
use Xabbuh\XApi\Model\Verb;

/**
 * Trait XApiStatementTrait.
 */
trait XApiStatementTrait
{
    /**
     * @return \Xabbuh\XApi\Model\Agent
     */
    protected function generateActor(UserEntity $user)
    {
        $mboxIri = IRI::fromString(
            'mailto:'.$user->getEmail()
        );

        return new Agent(
            InverseFunctionalIdentifier::withMbox($mboxIri),
            $user->getCompleteName()
        );
    }

    /**
     * @param string $word
     * @param string $uri
     *
     * @return \Xabbuh\XApi\Model\Verb
     */
    protected function generateVerb($word, $uri)
    {
        $languageMap = XApiPlugin::create()->getLangMap($word);

        return new Verb(
            IRI::fromString($uri),
            LanguageMap::create($languageMap)
        );
    }

    /**
     * @param string $type
     * @param string $value
     *
     * @return \Xabbuh\XApi\Model\StatementId
     */
    protected function generateId($type, $value)
    {
        $uuid = Uuid::uuid5(
            XApiPlugin::create()->get(XApiPlugin::SETTING_UUID_NAMESPACE),
            "$type/$value"
        );

        return StatementId::fromUuid($uuid);
    }

    /**
     * @return \Xabbuh\XApi\Model\Activity
     */
    protected function generateActivityFromSite()
    {
        $platform = api_get_setting('Institution').' - '.api_get_setting('siteName');
        $platformLanguage = api_get_setting('platformLanguage');
        $platformLanguageIso = api_get_language_isocode($platformLanguage);

        return new Activity(
            IRI::fromString('http://id.tincanapi.com/activitytype/lms'),
            new Definition(
                LanguageMap::create([$platformLanguageIso => $platform])
            )
        );
    }

    /**
     * @return \Xabbuh\XApi\Model\Activity
     */
    protected function generateActivityFromCourse(
        CourseEntity $course,
        SessionEntity $session = null
    ) {
        $languageIso = api_get_language_isocode($course->getCourseLanguage());

        return new Activity(
            IRI::fromString(
                api_get_course_url($course->getCode(), $session ? $session->getId() : null)
            ),
            new Definition(
                LanguageMap::create([$languageIso => $course->getTitle()]),
                null,
                IRI::fromString('http://id.tincanapi.com/activitytype/lms/course')
            )
        );
    }
}

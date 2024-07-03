<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Activity;

use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Definition;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;

/**
 * Class Site.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Activity
 */
class Site extends BaseActivity
{
    public function generate(): Activity
    {
        $platformLanguageIso = api_get_language_isocode(
            api_get_setting('platformLanguage')
        );
        $platform = api_get_setting('Institution').' - '.api_get_setting('siteName');

        return new Activity(
            IRI::fromString('http://id.tincanapi.com/activitytype/lms'),
            new Definition(
                LanguageMap::create([$platformLanguageIso => $platform])
            )
        );
    }
}

<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Activity;

/**
 * Class Site.
 */
class Site extends BaseActivity
{
    public function generate(): array
    {
        $platformLanguage = api_get_setting('platformLanguage');
        $platformLanguageIso = !empty($platformLanguage)
            ? api_get_language_isocode($platformLanguage)
            : 'en';

        $platform = trim((string) api_get_setting('Institution').' - '.(string) api_get_setting('siteName'));

        return [
            'objectType' => 'Activity',
            'id' => 'http://id.tincanapi.com/activitytype/lms',
            'definition' => [
                'name' => [
                    $platformLanguageIso => $platform,
                ],
            ],
        ];
    }
}

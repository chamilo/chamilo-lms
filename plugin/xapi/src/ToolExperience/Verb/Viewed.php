<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Verb;

use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;
use Xabbuh\XApi\Model\Verb;

/**
 * Class Viewed.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Verb
 */
class Viewed extends BaseVerb
{
    public function generate(): Verb
    {
        $langIso = api_get_language_isocode();

        return new Verb(
            IRI::fromString('http://id.tincanapi.com/verb/viewed'),
            LanguageMap::create([$langIso => get_lang('Viewed')])
        );
    }
}

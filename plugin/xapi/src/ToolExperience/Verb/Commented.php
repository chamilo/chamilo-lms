<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Verb;

use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;
use Xabbuh\XApi\Model\Verb;

/**
 * Class Commented.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Verb
 */
class Commented extends BaseVerb
{
    public function generate(): Verb
    {
        $langIso = api_get_language_isocode();

        return new Verb(
            IRI::fromString('http://adlnet.gov/expapi/verbs/commented'),
            LanguageMap::create([$langIso => get_lang('Commented')])
        );
    }
}

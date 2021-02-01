<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Verb;

use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;
use Xabbuh\XApi\Model\Verb;

/**
 * Class Answered.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Verb
 */
class Answered extends BaseVerb
{
    public function __construct()
    {
        parent::__construct(
            'http://adlnet.gov/expapi/verbs/answered',
            'Answered'
        );
    }
}

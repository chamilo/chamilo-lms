<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Verb;

use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;
use Xabbuh\XApi\Model\Verb;

/**
 * Class Completed.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Verb
 */
class Completed extends BaseVerb
{
    public function __construct()
    {
        parent::__construct(
            'http://activitystrea.ms/schema/1.0/complete',
            'Completed'
        );
    }
}

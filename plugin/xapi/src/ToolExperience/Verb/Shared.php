<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Verb;

/**
 * Class Shared.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Verb
 */
class Shared extends BaseVerb
{
    public function __construct()
    {
        parent::__construct(
            'http://adlnet.gov/expapi/verbs/shared',
            'Shared'
        );
    }
}

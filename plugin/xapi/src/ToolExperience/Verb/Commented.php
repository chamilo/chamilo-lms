<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Verb;

/**
 * Class Commented.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Verb
 */
class Commented extends BaseVerb
{
    public function __construct()
    {
        parent::__construct(
            'http://adlnet.gov/expapi/verbs/commented',
            'Commented'
        );
    }
}

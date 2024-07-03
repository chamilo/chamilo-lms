<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Verb;

/**
 * Class Replied.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Verb
 */
class Replied extends BaseVerb
{
    public function __construct()
    {
        parent::__construct(
            'http://id.tincanapi.com/verb/replied',
            'Replied'
        );
    }
}

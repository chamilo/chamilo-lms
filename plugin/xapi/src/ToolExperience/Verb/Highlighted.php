<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Verb;

class Highlighted extends BaseVerb
{
    public function __construct()
    {
        parent::__construct(
            'http://id.tincanapi.com/verb/promoted',
            'Highlighted'
        );
    }
}

<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Verb;

class Edited extends BaseVerb
{
    public function __construct()
    {
        parent::__construct(
            'http://curatr3.com/define/verb/edited',
        'Edited'
        );
    }
}

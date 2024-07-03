<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Verb;

class Scored extends BaseVerb
{
    public function __construct()
    {
        parent::__construct(
        'http://adlnet.gov/expapi/verbs/scored',
        'Scored'
    );
    }
}

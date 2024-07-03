<?php

namespace Chamilo\PluginBundle\XApi\ToolExperience\Verb;

class Downloaded extends BaseVerb
{
    public function __construct()
    {
        parent::__construct(
            'http://id.tincanapi.com/verb/downloaded',
            'Downloaded'
        );
    }
}

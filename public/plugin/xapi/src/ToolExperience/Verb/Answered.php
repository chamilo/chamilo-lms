<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Verb;

/**
 * Class Answered.
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

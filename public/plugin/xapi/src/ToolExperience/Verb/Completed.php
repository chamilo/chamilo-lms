<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Verb;

/**
 * Class Completed.
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

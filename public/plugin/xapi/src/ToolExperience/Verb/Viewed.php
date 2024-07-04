<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Verb;

/**
 * Class Viewed.
 */
class Viewed extends BaseVerb
{
    public function __construct()
    {
        parent::__construct(
            'http://id.tincanapi.com/verb/viewed',
            'Viewed'
        );
    }
}

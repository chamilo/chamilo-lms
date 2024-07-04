<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Actor;

use Xabbuh\XApi\Model\Agent;

/**
 * Class BaseActor.
 */
abstract class BaseActor
{
    abstract public function generate(): Agent;
}

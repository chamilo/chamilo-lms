<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Actor;

use Xabbuh\XApi\Model\Agent;

/**
 * Class BaseActor.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Actor
 */
abstract class BaseActor
{
    abstract public function generate(): Agent;
}

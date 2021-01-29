<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Verb;

use Xabbuh\XApi\Model\Verb;

/**
 * Class BaseVerb
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Verb
 */
abstract class BaseVerb
{
    public abstract function generate(): Verb;
}

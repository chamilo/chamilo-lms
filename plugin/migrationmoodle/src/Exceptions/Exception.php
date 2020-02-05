<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Exceptions;

/**
 * Class Exception.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Exceptions
 */
abstract class Exception extends \Exception
{
    public function displayAsString()
    {
        $pieces = [$this->getMessage()];

        if ($this->getPrevious()) {
            $pieces[] = "\t".$this->getPrevious()->getMessage();
        }

        echo implode(PHP_EOL, $pieces);
    }
}

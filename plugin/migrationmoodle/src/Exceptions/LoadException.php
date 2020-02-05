<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Exceptions;

use Throwable;

/**
 * Class LoadException.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Exceptions
 */
class LoadException extends Exception
{
    /**
     * @var array
     */
    private $incomingData;

    public function __construct($incomingData, Throwable $previous = null)
    {
        $message = 'Error while loading transformed data.';
        $this->incomingData = $incomingData;

        parent::__construct($message, 0, $previous);
    }

    public function displayAsString()
    {
        $pieces = [
            parent::displayAsString(),
            "\t".print_r($this->incomingData, true),
        ];

        echo implode(PHP_EOL, $pieces);
    }
}

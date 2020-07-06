<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Messages;

use Throwable;

/**
 * Class LoadMessage.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Messages
 */
class LoadMessage extends Message
{
    /**
     * @var array
     */
    private $incomingData;

    /**
     * LoadMessage constructor.
     *
     * @param $incomingData
     */
    public function __construct($incomingData, Throwable $previous = null)
    {
        $message = 'Error while loading transformed data.';
        $this->incomingData = $incomingData;

        parent::__construct($message, $previous);
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

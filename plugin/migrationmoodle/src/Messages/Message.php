<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Messages;

use Throwable;

/**
 * Class Message.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Messages
 */
abstract class Message
{
    /**
     * @var string
     */
    protected $message;
    /**
     * @var Throwable
     */
    protected $previous;

    /**
     * Message constructor.
     *
     * @param string $message
     */
    public function __construct($message = "", Throwable $previous = null)
    {
        $this->message = $message;
        $this->previous = $previous;

        $this->displayAsString();
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return Throwable
     */
    public function getPrevious()
    {
        return $this->previous;
    }

    public function displayAsString()
    {
        $pieces = [$this->message];

        if ($this->previous) {
            $pieces[] = "\t".$this->previous->getMessage();
        }

        echo implode(PHP_EOL, $pieces);
    }
}

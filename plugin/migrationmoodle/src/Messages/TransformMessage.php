<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Messages;

use Throwable;

/**
 * Class TransformMessage.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Messages
 */
class TransformMessage extends Message
{
    /**
     * @var array
     */
    private $extractedData;

    /**
     * TransformMessage constructor.
     */
    public function __construct(array $extractedData, Throwable $previous = null)
    {
        $message = 'Error while transforming extracted data.';
        $this->extractedData = $extractedData;

        parent::__construct($message, $previous);
    }

    public function displayAsString()
    {
        $pieces = [
            parent::displayAsString(),
            "\t".print_r($this->extractedData, true),
        ];

        echo implode(PHP_EOL, $pieces);
    }
}

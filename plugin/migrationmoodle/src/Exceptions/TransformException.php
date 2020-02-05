<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Exceptions;

use Throwable;

/**
 * Class TransformException.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Exceptions
 */
class TransformException extends Exception
{
    /**
     * @var array
     */
    private $extractedData;

    /**
     * TransformException constructor.
     *
     * @param array          $extractedData
     * @param Throwable|null $previous
     */
    public function __construct(array $extractedData, Throwable $previous = null)
    {
        $message = 'Error while transforming extracted data.';
        $this->extractedData = $extractedData;

        parent::__construct($message, 0, $previous);
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

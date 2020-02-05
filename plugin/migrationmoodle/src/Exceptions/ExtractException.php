<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Exceptions;

use Throwable;

/**
 * Class ExtractException.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Exceptions
 */
class ExtractException extends Exception
{
    /**
     * ExtractException constructor.
     *
     * @param Throwable|null $previous
     */
    public function __construct(Throwable $previous = null)
    {
        $message = 'Error while extracting data.';

        parent::__construct($message, 0, $previous);
    }
}

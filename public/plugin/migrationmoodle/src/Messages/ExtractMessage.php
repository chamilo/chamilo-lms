<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Messages;

use Throwable;

/**
 * Class ExtractException.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Messages
 */
class ExtractMessage extends Message
{
    /**
     * ExtractMessage constructor.
     */
    public function __construct(Throwable $previous = null)
    {
        $message = 'Error while extracting data.';

        parent::__construct($message, $previous);
    }
}

<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Exception;

use Exception;

class NotAllowedException extends Exception
{
    public function __construct($message = 'Not allowed', $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

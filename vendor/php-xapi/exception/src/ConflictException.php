<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Common\Exception;

/**
 * Exception indicating an error due to a conflict with the current state of
 * a resource.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class ConflictException extends XApiException
{
    public function __construct($message)
    {
        parent::__construct($message, 409);
    }
}

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
 * More specific xAPI exception indicating that a resource could not be found.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class NotFoundException extends XApiException
{
    public function __construct($message)
    {
        parent::__construct($message, 404);
    }
}

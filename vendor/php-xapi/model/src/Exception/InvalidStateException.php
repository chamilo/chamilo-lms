<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Model\Exception;

use Xabbuh\XApi\Common\Exception\XApiException;

/**
 * Exception indicating that an xAPI statement is in an invalid state (e.g.
 * some necessary properties are missing).
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class InvalidStateException extends XApiException
{
}

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
 * Statement id already exists exception.
 *
 * @author Jérôme Parmentier <jerome.parmentier@acensi.fr>
 */
class StatementIdAlreadyExistsException extends XApiException
{
    public function __construct($statementId, \Exception $previous = null)
    {
        parent::__construct(sprintf('A statement with ID "%s" already exists.', $statementId), 0, $previous);
    }
}

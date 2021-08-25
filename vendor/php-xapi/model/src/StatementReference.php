<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Model;

/**
 * A reference to an existing {@link Statement}.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class StatementReference extends StatementObject
{
    private $statementId;

    public function __construct(StatementId $statementId)
    {
        $this->statementId = $statementId;
    }

    /**
     * Returns the id of the referenced Statement.
     */
    public function getStatementId(): StatementId
    {
        return $this->statementId;
    }

    /**
     * {@inheritdoc}
     */
    public function equals(StatementObject $object): bool
    {
        if (!$object instanceof StatementReference) {
            return false;
        }

        return $this->statementId->equals($object->statementId);
    }
}

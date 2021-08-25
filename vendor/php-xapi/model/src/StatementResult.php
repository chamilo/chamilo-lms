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
 * Result when querying a Learning Record Store (LRS) for a list of
 * {@link Statement Statements}.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class StatementResult
{
    private $statements;
    private $moreUrlPath;

    /**
     * @param Statement[] $statements The collection of Statements
     */
    public function __construct(array $statements, IRL $moreUrlPath = null)
    {
        $this->statements = $statements;
        $this->moreUrlPath = $moreUrlPath;
    }

    /**
     * Returns the Statements.
     *
     * @return Statement[]
     */
    public function getStatements(): array
    {
        return $this->statements;
    }

    /**
     * Relative IRL that can be used to retrieve the next results.
     */
    public function getMoreUrlPath(): ?IRL
    {
        return $this->moreUrlPath;
    }
}

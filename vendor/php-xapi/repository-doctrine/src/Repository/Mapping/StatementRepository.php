<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\Repository\Doctrine\Repository\Mapping;

use XApi\Repository\Doctrine\Mapping\Statement;

/**
 * {@link Statement} repository interface definition.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
interface StatementRepository
{
    /**
     * @param array $criteria
     *
     * @return Statement The statement or null if no matching statement
     *                   has been found
     */
    public function findStatement(array $criteria);

    /**
     * @param array $criteria
     *
     * @return Statement[] The statements matching the given criteria
     */
    public function findStatements(array $criteria);

    /**
     * Saves a {@link Statement} in the underlying storage.
     *
     * @param Statement $statement The statement being stored
     * @param bool      $flush     Whether or not to flush the managed objects
     *                             (i.e. write them to the data storage immediately)
     */
    public function storeStatement(Statement $statement, $flush = true);
}

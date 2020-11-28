<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\Repository\ORM;

use Doctrine\ORM\EntityRepository;
use XApi\Repository\Doctrine\Mapping\Statement;
use XApi\Repository\Doctrine\Repository\Mapping\StatementRepository as BaseStatementRepository;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class StatementRepository extends EntityRepository implements BaseStatementRepository
{
    /**
     * {@inheritdoc}
     */
    public function findStatement(array $criteria)
    {
        return parent::findOneBy($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function findStatements(array $criteria)
    {
        return parent::findBy($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function storeStatement(Statement $mappedStatement, $flush = true)
    {
        $this->_em->persist($mappedStatement);

        if ($flush) {
            $this->_em->flush();
        }
    }
}

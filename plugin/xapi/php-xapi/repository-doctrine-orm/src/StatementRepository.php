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
use XApi\Repository\Doctrine\Mapping\Verb;
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
        return $this->getQueryBuilder($criteria)->getQuery()->getResult();
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

    private function getQueryBuilder(array $criteria): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('statement');

        if (!empty($criteria['verb'])) {
            $qb->innerJoin('statement.verb', 'verb');
            $qb->andWhere($qb->expr()->eq('verb.id', ':c_verb'));
            $qb->setParameter('c_verb', $criteria['verb']);
        }

        $qb->setFirstResult($criteria['cursor']);

        if (isset($criteria['limit'])) {
            $qb->setMaxResults($criteria['limit']);
        }

        return $qb;
    }
}

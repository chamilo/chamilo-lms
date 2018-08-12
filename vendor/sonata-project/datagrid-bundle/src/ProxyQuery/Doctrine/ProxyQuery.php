<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DatagridBundle\ProxyQuery\Doctrine;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Sonata\DatagridBundle\ProxyQuery\BaseProxyQuery;

/**
 * This is the Doctrine proxy query class.
 */
class ProxyQuery extends BaseProxyQuery
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $params = [], $hydrationMode = null)
    {
        // Limit & offset
        $this->queryBuilder->setMaxResults($this->getMaxResults());
        $this->queryBuilder->setFirstResult($this->getFirstResult());

        // Sorted field and sort order
        $sortBy = $this->getSortBy();
        $sortOrder = $this->getSortOrder();

        if ($sortBy && $sortOrder) {
            $rootAliases = $this->queryBuilder->getRootAliases();
            $rootAlias = $rootAliases[0];
            $sortBy = sprintf('%s.%s', $rootAlias, $sortBy);

            $this->queryBuilder->orderBy($sortBy, $sortOrder);
        }

        return $this->getFixedQueryBuilder($this->queryBuilder)->getQuery()->execute($params, $hydrationMode);
    }

    /**
     * Generates new QueryBuilder for Postgresql or Oracle if necessary.
     *
     * @param $queryBuilder QueryBuilder
     *
     * @return QueryBuilder
     */
    public function preserveSqlOrdering(QueryBuilder $queryBuilder)
    {
        $rootAliases = $queryBuilder->getRootAliases();
        $rootAlias = $rootAliases[0];

        // for SELECT DISTINCT, ORDER BY expressions must appear in select list
        // Consider SELECT DISTINCT x FROM tab ORDER BY y;
        // For any particular x-value in the table there might be many different y
        // values. Which one will you use to sort that x-value in the output?

        // todo : check how doctrine behave, potential SQL injection here ...
        if ($this->getSortBy()) {
            $sortBy = $this->getSortBy();
            if (false === strpos($sortBy, '.')) {
                // add the current alias
                $sortBy = $rootAlias.'.'.$sortBy;
            }
            $sortBy .= ' AS __order_by';
            $queryBuilder->addSelect($sortBy);
        }

        // For any orderBy clause defined directly in the dqlParts
        $dqlParts = $queryBuilder->getDqlParts();
        if ($dqlParts['orderBy'] && count($dqlParts['orderBy'])) {
            $sqlOrderColumns = [];
            foreach ($dqlParts['orderBy'] as $part) {
                foreach ($part->getParts() as $orderBy) {
                    $sqlOrderColumns[] = preg_replace("/\s+(ASC|DESC)$/i", '', $orderBy);
                }
            }
            $queryBuilder->addSelect(implode(', ', $sqlOrderColumns));
        }

        return $queryBuilder;
    }

    /**
     * This method alters the query to return a clean set of object with a working
     * set of Object.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return QueryBuilder
     */
    private function getFixedQueryBuilder(QueryBuilder $queryBuilder)
    {
        $queryBuilderId = clone $queryBuilder;

        // step 1 : retrieve the targeted class
        $from = $queryBuilderId->getDQLPart('from');
        $class = $from[0]->getFrom();

        // step 2 : retrieve the column id
        $idName = current($queryBuilderId->getEntityManager()->getMetadataFactory()->getMetadataFor($class)->getIdentifierFieldNames());

        // step 3 : retrieve the different subjects id
        $rootAliases = $queryBuilderId->getRootAliases();
        $rootAlias = $rootAliases[0];
        $select = sprintf('%s.%s', $rootAlias, $idName);
        $queryBuilderId->resetDQLPart('select');
        $queryBuilderId->add('select', 'DISTINCT '.$select);
        $queryBuilderId = $this->preserveSqlOrdering($queryBuilderId);

        $results = $queryBuilderId->getQuery()->execute([], Query::HYDRATE_ARRAY);

        $idx = [];
        $connection = $queryBuilder->getEntityManager()->getConnection();
        foreach ($results as $id) {
            $idx[] = $connection->quote($id[$idName]);
        }

        // step 4 : alter the query to match the targeted ids
        if (count($idx) > 0) {
            $queryBuilder->andWhere(sprintf('%s IN (%s)', $select, implode(',', $idx)));
            $queryBuilder->setMaxResults(null);
            $queryBuilder->setFirstResult(null);
        }

        return $queryBuilder;
    }
}

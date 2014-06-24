<?php

/*
 * This file is part of the Pagerfanta package.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pagerfanta\Adapter;

use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Doctrine\ODM\PHPCR\Query\Query;

/**
 * Pagerfanta adapter for Doctrine PHPCR-ODM.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
class DoctrineODMPhpcrAdapter implements AdapterInterface
{
    private $queryBuilder;

    /**
     * Constructor.
     *
     * @param QueryBuilder $queryBuilder A Doctrine PHPCR-ODM query builder.
     */
    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Returns the query builder.
     *
     * @return QueryBuilder The query builder.
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getNbResults()
    {
        return $this->queryBuilder->getQuery()->execute(null, Query::HYDRATE_PHPCR)->getRows()->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getSlice($offset, $length)
    {
        return $this->queryBuilder
            ->getQuery()
            ->setMaxResults($length)
            ->setFirstResult($offset)
            ->execute();
    }
}

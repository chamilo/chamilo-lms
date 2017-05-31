<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DatagridBundle\ProxyQuery\Elastica;

use Elastica\Search;
use Sonata\DatagridBundle\ProxyQuery\BaseProxyQuery;

/**
 * This class try to unify the query usage with Doctrine.
 */
class ProxyQuery extends BaseProxyQuery
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $params = array(), $hydrationMode = null)
    {
        $query = $this->queryBuilder->getQuery();

        // Sorted field and sort order
        $sortBy = $this->getSortBy();
        $sortOrder = $this->getSortOrder();

        if ($sortBy && $sortOrder) {
            $query->setSort(array($sortBy => array('order' => $sortOrder)));
        }

        // Limit & offset
        $this->results = $this->queryBuilder->getRepository()->createPaginatorAdapter($query, array(
            Search::OPTION_SIZE => $this->getMaxResults(),
            Search::OPTION_FROM => $this->getFirstResult(),
        ));

        return $this->results->getResults($this->getFirstResult(), $this->getMaxResults())->toArray();
    }
}

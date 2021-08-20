<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Traits\Repository;

/* For licensing terms, see /license.txt */

use Doctrine\ORM\QueryBuilder;

trait RepositoryQueryBuilderTrait
{
    abstract public function createQueryBuilder($alias, $indexBy = null);

    protected function getOrCreateQueryBuilder(QueryBuilder $qb = null, string $alias = 'resource'): QueryBuilder
    {
        return $qb ?: $this->createQueryBuilder($alias);
    }
}

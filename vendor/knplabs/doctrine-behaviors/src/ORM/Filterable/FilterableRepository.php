<?php

/*
 * This file is part of the KnpDoctrineBehaviors package.
 *
 * (c) KnpLabs <http://knplabs.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Knp\DoctrineBehaviors\ORM\Filterable;

use Doctrine\ORM\QueryBuilder;

/**
 * Filterable trait.
 *
 * Should be used inside entity repository, that needs to be filterable
 */
trait FilterableRepository
{
    /**
     * Retrieve field which will be sorted using LIKE
     *
     * Example format: ['e:name', 'e:description']
     *
     * @return array
     */
    abstract public function getLikeFilterColumns();

    /**
     * Retrieve field which will be sorted using LOWER() LIKE
     *
     * Example format: ['e:name', 'e:description']
     *
     * @return array
     */
    abstract public function getILikeFilterColumns();

    /**
     * Retrieve field which will be sorted using EQUAL
     *
     * Example format: ['e:name', 'e:description']
     *
     * @return array
     */
    abstract public function getEqualFilterColumns();

    /**
     * Retrieve field which will be sorted using IN()
     *
     * Example format: ['e:group_id']
     *
     * @return array
     */
    abstract public function getInFilterColumns();

    /**
     * Filter values
     *
     * @param  array                      $filters - array like ['e:name' => 'nameValue'] where "e" is entity alias query, so we can filter using joins.
     * @param \Doctrine\ORM\QueryBuilder
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function filterBy(array $filters, QueryBuilder $qb = null)
    {
        $filters = array_filter($filters, function ($filter) {
            return !empty($filter);
        });

        if (null === $qb) {
            $qb = $this->createFilterQueryBuilder();
        }

        foreach ($filters as $col => $value) {
            foreach ($this->getColumnParameters($col) as $colName => $colParam) {
                $compare = $this->getWhereOperator($col).'Where';

                if (in_array($col, $this->getLikeFilterColumns())) {
                    $qb
                        ->$compare(sprintf('%s LIKE :%s', $colName, $colParam))
                        ->setParameter($colParam, '%'.$value.'%')
                    ;
                }

                if (in_array($col, $this->getILikeFilterColumns())) {
                    $qb
                        ->$compare(sprintf('LOWER(%s) LIKE :%s', $colName, $colParam))
                        ->setParameter($colParam, '%'.strtolower($value).'%')
                    ;
                }

                if (in_array($col, $this->getEqualFilterColumns())) {
                    $qb
                        ->$compare(sprintf('%s = :%s', $colName, $colParam))
                        ->setParameter($colParam, $value)
                    ;
                }

                if (in_array($col, $this->getInFilterColumns())) {
                    $qb
                        ->$compare($qb->expr()->in(sprintf('%s', $colName), (array) $value))
                    ;
                }
            }
        }

        return $qb;
    }

    protected function getColumnParameters($col)
    {
        $colName  = str_replace(':', '.', $col);
        $colParam = str_replace(':', '_', $col);

        return [$colName => $colParam];
    }

    protected function getWhereOperator($col)
    {
        return 'and';
    }

    protected function createFilterQueryBuilder()
    {
        return $this->createQueryBuilder('e');
    }
}

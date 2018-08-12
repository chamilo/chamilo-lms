<?php

namespace Knp\DoctrineBehaviors\ORM\Sortable;

use Doctrine\ORM\QueryBuilder;

trait SortableRepository
{
    public function reorderEntity($entity)
    {
        if (!$entity->isReordered()) {
            return;
        }

        $qb = $this->createQueryBuilder('e')
            ->update($this->getEntityName(), 'e')
            ->set('e.sort', 'e.sort + 1')
            ->andWhere('e.sort >= :sort')
            ->setParameter('sort', $entity->getSort())
        ;
        $entity->setReordered();

        $this->addSortingScope($qb, $entity);

        $qb
            ->getQuery()
            ->execute()
        ;
    }

    protected function addSortingScope(QueryBuilder $qb, $entity)
    {
    }
}


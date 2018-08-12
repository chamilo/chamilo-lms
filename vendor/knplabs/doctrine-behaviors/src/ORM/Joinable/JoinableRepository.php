<?php

/*
 * This file is part of the KnpDoctrineBehaviors package.
 *
 * (c) KnpLabs <http://knplabs.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Knp\DoctrineBehaviors\ORM\Joinable;

use Doctrine\ORM\QueryBuilder;

/**
 * Joinable trait.
 *
 * Should be used inside entity repository, that needs to easily make joined queries
 */
trait JoinableRepository
{
    public function getJoinAllQueryBuilder($alias = null, QueryBuilder $qb = null)
    {
        if (null === $alias) {
            $alias = $this->getAlias($this->getClassName());
        }

        if (null === $qb) {
            $qb = $this->createQueryBuilder($alias);
        }

        $className = $this->getClassName();

        $this->addJoinsToQueryBuilder($alias, $qb, $className);

        return $qb;
    }

    private function addJoinsToQueryBuilder($alias, QueryBuilder $qb, $className, $recursive = true)
    {
        foreach ($this->getEntityManager()->getClassMetadata($className)->getAssociationMappings() as $assoc) {

            if (in_array($assoc['targetEntity'], $qb->getRootEntities()) || $className === $assoc['targetEntity']) {
                continue;
            }

            $uniqueJoinAlias = $this->getUniqueAlias($assoc['targetEntity'], $qb);
            $qb
                ->addSelect($uniqueJoinAlias)
                ->leftJoin(sprintf('%s.%s', $alias, $assoc['fieldName']), $uniqueJoinAlias)
            ;
            if ($recursive) {
                $this->addJoinsToQueryBuilder($uniqueJoinAlias, $qb, $assoc['targetEntity']);
            }
        }
    }

    private function getAlias($className)
    {
        $shortName = $this->getEntityManager()->getClassMetadata($className)->reflClass->getShortName();
        $alias = strtolower(substr($shortName, 0, 1));

        return $alias;
    }

    private function getUniqueAlias($className, QueryBuilder $qb)
    {
        $alias = $this->getAlias($className);

        $i = 1;
        $firstAlias = $alias;
        while ($this->aliasExists($alias, $qb)) {
            $alias = $firstAlias.$i;
            $i++;
        }

        return $alias;
    }

    private function aliasExists($alias, QueryBuilder $qb)
    {
        $aliases = [];
        foreach ($qb->getDqlPart('join') as $joins) {
            foreach ($joins as $join) {
                $aliases[] = $join->getAlias();
            }
        }

        $aliases[] = $qb->getRootAlias();

        return in_array($alias, $aliases);
    }
}

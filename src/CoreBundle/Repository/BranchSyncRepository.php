<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\BranchSync;
use Chamilo\CoreBundle\Traits\Repository\ORM\NestedTreeRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

class BranchSyncRepository extends ServiceEntityRepository
{
    use NestedTreeRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BranchSync::class);
        $this->initializeTreeRepository($this->getEntityManager(), $this->getClassMetadata());
    }

    /**
     * @return BranchSync[]|Collection
     */
    public function searchByKeyword(string $keyword)
    {
        $qb = $this->createQueryBuilder('a');

        //Selecting user info
        $qb->select('DISTINCT b');

        $qb->from(BranchSync::class, 'b');

        //Selecting courses for users
        //$qb->innerJoin('u.courses', 'c');

        //@todo check app settings
        $qb->addOrderBy('b.branchName', 'ASC');
        $qb->where('b.branchName LIKE :keyword');
        $qb->setParameter('keyword', "%$keyword%", Types::STRING);
        $q = $qb->getQuery();

        return $q->execute();
    }

    /**
     * Gets the first branch with parent_id = NULL.
     */
    public function getTopBranch(): ?BranchSync
    {
        $qb = $this->createQueryBuilder('a');

        //Selecting user info
        $qb->select('DISTINCT b');

        $qb->from(BranchSync::class, 'b');
        $qb->where('b.parent IS NULL');
        $qb->orderBy('b.id', Criteria::ASC);
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}

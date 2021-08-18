<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;

class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * @return Collection|Tag[]
     */
    public function findTagsByField(string $tag, int $fieldId)
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.tag LIKE :tag')
            ->andWhere('t.field = :field')
            ->setParameter('field', $fieldId)
            ->setParameter('tag', "$tag%")
        ;

        return $qb->getQuery()->getResult();
    }
}

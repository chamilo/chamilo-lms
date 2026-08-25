<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Traits\NonResourceRepository;
use Chamilo\CoreBundle\Traits\Repository\ORM\NestedTreeRepositoryTrait;
use Chamilo\CourseBundle\Entity\CLpItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class CLpItemRepository extends ServiceEntityRepository
{
    use NestedTreeRepositoryTrait;
    use NonResourceRepository;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CLpItem::class);

        $this->initializeTreeRepository($this->getEntityManager(), $this->getClassMetadata());
    }

    public function create(CLpItem $item): void
    {
        $this->getEntityManager()->persist($item);
        $this->getEntityManager()->flush();
    }

    public function getRootItem(int $lpId): ?CLpItem
    {
        return $this->findOneBy([
            'path' => 'root',
            'lp' => $lpId,
        ]);
    }

    public function findItemsByLearningPathAndType(int $learningPathId, string $itemType): array
    {
        $qb = $this->createQueryBuilder('i')
            ->where('i.lp = :learningPathId')
            ->andWhere('i.itemType = :itemType')
            ->setParameter('learningPathId', $learningPathId)
            ->setParameter('itemType', $itemType)
        ;

        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * Minimal data needed to know which resource an item points at, without hydrating it.
     *
     * @return null|array{itemType: string, path: string, lpId: int}
     */
    public function findResourceTargetData(int $iid): ?array
    {
        $row = $this->createQueryBuilder('i')
            ->select('i.itemType AS itemType', 'i.path AS path', 'IDENTITY(i.lp) AS lpId')
            ->where('i.iid = :iid')
            ->setParameter('iid', $iid)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!\is_array($row)) {
            return null;
        }

        return [
            'itemType' => (string) $row['itemType'],
            'path' => (string) $row['path'],
            'lpId' => (int) $row['lpId'],
        ];
    }

    public function findLearningPathsUsingDocument(int $resourceFileId): array
    {
        return $this->createQueryBuilder('i')
            ->select('lp.iid AS lpId, lp.title AS lpTitle')
            ->join('i.lp', 'lp')
            ->where('i.itemType = :type')
            ->andWhere('i.path = :path')
            ->setParameter('type', 'document')
            ->setParameter('path', $resourceFileId)
            ->getQuery()
            ->getArrayResult()
        ;
    }
}

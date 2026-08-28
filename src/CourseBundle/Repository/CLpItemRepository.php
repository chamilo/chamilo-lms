<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Traits\NonResourceRepository;
use Chamilo\CoreBundle\Traits\Repository\ORM\NestedTreeRepositoryTrait;
use Chamilo\CourseBundle\Entity\CLpItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class CLpItemRepository extends ServiceEntityRepository
{
    use NestedTreeRepositoryTrait;
    use NonResourceRepository;

    private const string ITEM_TYPE_QUIZ = 'quiz';

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

    /**
     * Whether the learning path really contains this exercise as the given item, through a link
     * that is published in the course and session.
     */
    public function hasPublishedQuizItem(
        int $itemId,
        int $learningPathId,
        int $exerciseId,
        int $courseId,
        ?int $sessionId
    ): bool {
        $queryBuilder = $this->createQueryBuilder('item')
            ->select('item.iid')
            ->innerJoin('item.lp', 'lp')
            ->innerJoin('lp.resourceNode', 'lpNode')
            ->innerJoin('lpNode.resourceLinks', 'lpLinks')
            ->andWhere('item.iid = :itemId')
            ->andWhere('IDENTITY(item.lp) = :learningPathId')
            ->andWhere('item.itemType = :itemType')
            ->andWhere('(item.path = :exerciseIdString OR item.ref = :exerciseIdString)')
            ->andWhere('IDENTITY(lpLinks.course) = :courseId')
            ->andWhere('lpLinks.visibility = :publishedVisibility')
            ->andWhere('lpLinks.deletedAt IS NULL')
            ->andWhere('lpLinks.endVisibilityAt IS NULL')
            ->setParameter('itemId', $itemId)
            ->setParameter('learningPathId', $learningPathId)
            ->setParameter('itemType', self::ITEM_TYPE_QUIZ)
            ->setParameter('exerciseIdString', (string) $exerciseId)
            ->setParameter('courseId', $courseId)
            ->setParameter('publishedVisibility', ResourceLink::VISIBILITY_PUBLISHED)
            ->setMaxResults(1)
        ;

        if (null !== $sessionId) {
            $queryBuilder
                ->andWhere('(IDENTITY(lpLinks.session) = :sessionId OR lpLinks.session IS NULL)')
                ->setParameter('sessionId', $sessionId)
            ;
        } else {
            $queryBuilder->andWhere('lpLinks.session IS NULL');
        }

        return null !== $queryBuilder->getQuery()->getOneOrNullResult();
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

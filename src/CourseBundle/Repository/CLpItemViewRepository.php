<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Traits\NonResourceRepository;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class CLpItemViewRepository extends ServiceEntityRepository
{
    use NonResourceRepository;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CLpItemView::class);
    }

    /**
     * Whether the given row belongs to this user's own CLpView of the learning path — the same
     * item, in the same course and session.
     *
     * A learner can edit learnpath_item_view_id in the URL, so reaching an item view is only
     * legitimate when it is part of their own progress through the lesson.
     */
    public function belongsToUserLearnpathView(
        int $itemViewId,
        int $itemId,
        int $learningPathId,
        int $courseId,
        ?int $sessionId,
        int $userId
    ): bool {
        $queryBuilder = $this->createQueryBuilder('itemView')
            ->select('itemView.iid')
            ->innerJoin('itemView.view', 'lpView')
            ->andWhere('itemView.iid = :itemViewId')
            ->andWhere('IDENTITY(itemView.item) = :itemId')
            ->andWhere('IDENTITY(lpView.lp) = :learningPathId')
            ->andWhere('IDENTITY(lpView.course) = :courseId')
            ->andWhere('IDENTITY(lpView.user) = :userId')
            ->setParameter('itemViewId', $itemViewId)
            ->setParameter('itemId', $itemId)
            ->setParameter('learningPathId', $learningPathId)
            ->setParameter('courseId', $courseId)
            ->setParameter('userId', $userId)
            ->setMaxResults(1)
        ;

        if (null !== $sessionId) {
            $queryBuilder
                ->andWhere('IDENTITY(lpView.session) = :sessionId')
                ->setParameter('sessionId', $sessionId)
            ;
        } else {
            $queryBuilder->andWhere('lpView.session IS NULL');
        }

        return null !== $queryBuilder->getQuery()->getOneOrNullResult();
    }
}

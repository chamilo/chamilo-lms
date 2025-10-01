<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\PortfolioComment;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Traits\Repository\ORM\NestedTreeRepositoryTrait;
use Doctrine\Persistence\ManagerRegistry;

class PortfolioCommentRepository extends ResourceRepository
{
    use NestedTreeRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PortfolioComment::class);
    }

    public function findCommentsByUser(User $user, ?Course $course, ?Session $session, ?array $orderBy = null): array
    {
        $qbComments = $this->createQueryBuilder('comment');
        $qbComments
            ->where('comment.author = :owner')
            ->setParameter('owner', $user)
        ;

        if ($course) {
            $qbComments
                ->join('comment.item', 'item')
                ->andWhere('item.course = :course')
                ->setParameter('course', $course)
            ;

            if ($session) {
                $qbComments
                    ->andWhere('item.session = :session')
                    ->setParameter('session', $session)
                ;
            } else {
                $qbComments->andWhere('item.session IS NULL');
            }
        }

        if ($orderBy) {
            foreach ($orderBy as $sort => $order) {
                $qbComments->addOrderBy($sort, $order);
            }
        }

        return $qbComments->getQuery()->getResult();
    }
}

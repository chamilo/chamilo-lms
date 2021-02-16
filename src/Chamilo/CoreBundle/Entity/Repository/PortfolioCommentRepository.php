<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\UserBundle\Entity\User;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * Class PortfolioCommentRepository.
 *
 * @package Chamilo\CoreBundle\Entity\Repository
 */
class PortfolioCommentRepository extends NestedTreeRepository
{
    public function findCommentsByUser(User $user, ?Course $course, ?Session $session, ?array $orderBy = null): array
    {
        $qbComments = $this->createQueryBuilder('comment');
        $qbComments
            ->where('comment.author = :owner')
            ->setParameter('owner', $user);

        if ($course) {
            $qbComments
                ->join('comment.item', 'item')
                ->andWhere('item.course = :course')
                ->setParameter('course', $course);

            if ($session) {
                $qbComments
                    ->andWhere('item.session = :session')
                    ->setParameter('session', $session);
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

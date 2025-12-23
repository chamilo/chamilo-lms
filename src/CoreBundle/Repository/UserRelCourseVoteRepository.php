<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\UserRelCourseVote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserRelCourseVote>
 */
class UserRelCourseVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRelCourseVote::class);
    }

    /**
     * Retrieves the average vote and the count of votes for a specific course.
     *
     * @return array the first element of the array is the average vote (rounded to 2 decimal places),
     *               and the second element is the count of votes
     */
    public function getCouseRating(Course $course, ?Session $session = null): array
    {
        $qb = $this->createQueryBuilder('v');

        $qb
            ->select([
                $qb->expr()->avg('v.vote'),
                $qb->expr()->count('v.id'),
            ])
            ->where($qb->expr()->eq('v.course', ':course'))
            ->setParameter('course', $course->getId(), ParameterType::INTEGER)
        ;

        if (null !== $session) {
            $qb
                ->andWhere($qb->expr()->eq('v.session', ':session'))
                ->setParameter('session', $session->getId(), ParameterType::INTEGER)
            ;
        } else {
            $qb->andWhere($qb->expr()->isNull('v.session'));
        }

        try {
            $result = $qb
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleResult()
            ;
        } catch (NonUniqueResultException|NoResultException) {
            $result = [1 => 0, 2 => 0];
        }

        return [
            'average' => round((float) $result[1], 2),
            'count' => $result[2],
        ];
    }
}

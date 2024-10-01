<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\TrackEDefault;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TrackEDefaultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrackEDefault::class);
    }

    /**
     * Retrieves the registration date of a user in a specific course or session.
     */
    public function getUserCourseRegistrationAt(int $courseId, int $userId, ?int $sessionId = 0): ?\DateTime
    {
        $serializedPattern = '%s:2:"id";i:' . $userId . ';%';

        $qb = $this->createQueryBuilder('te')
            ->select('te.defaultDate')
            ->where('te.cId = :courseId')
            ->andWhere('te.defaultValueType = :valueType')
            ->andWhere('te.defaultEventType = :eventType')
            ->andWhere('te.defaultValue LIKE :serializedPattern')
            ->setParameter('courseId', $courseId)
            ->setParameter('valueType', 'user_object')
            ->setParameter('eventType', 'user_subscribed')
            ->setParameter('serializedPattern', $serializedPattern);

        if ($sessionId > 0) {
            $qb->andWhere('te.sessionId = :sessionId')
                ->setParameter('sessionId', $sessionId);
        }

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result ? $result['defaultDate'] : null;
    }
}

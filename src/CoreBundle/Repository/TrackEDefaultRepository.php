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
        $serializedPattern = sprintf('s:2:"id";i:%d;', $userId);

        $qb = $this->createQueryBuilder('te')
            ->select('te.defaultDate')
            ->where('te.cId = :courseId')
            ->andWhere('te.defaultValueType = :valueType')
            ->andWhere('te.defaultEventType = :eventType')
            ->andWhere('te.defaultValue LIKE :serializedPattern')
            ->setParameter('courseId', $courseId)
            ->setParameter('valueType', 'user_object')
            ->setParameter('eventType', 'user_subscribed')
            ->setParameter('serializedPattern', '%' . $serializedPattern . '%');

        if ($sessionId !== null) {
            $qb->andWhere('te.sessionId = :sessionId')
                ->setParameter('sessionId', $sessionId);
        } else {
            $qb->andWhere('te.sessionId IS NULL');
        }

        $qb->setMaxResults(1);
        $query = $qb->getQuery();
        $result = $query->getOneOrNullResult();

        // Devuelve directamente si el resultado ya es DateTime
        if (isset($result['defaultDate']) && $result['defaultDate'] instanceof \DateTime) {
            return $result['defaultDate'];
        }

        // Convierte si es necesario
        return isset($result['defaultDate']) ? new \DateTime($result['defaultDate']) : null;
    }
}

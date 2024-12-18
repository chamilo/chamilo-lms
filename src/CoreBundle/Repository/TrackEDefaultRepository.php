<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\TrackEDefault;
use Chamilo\CoreBundle\Entity\ValidationToken;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use RuntimeException;

class TrackEDefaultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrackEDefault::class);
    }

    /**
     * Retrieves the registration date of a user in a specific course or session.
     */
    public function getUserCourseRegistrationAt(int $courseId, int $userId, ?int $sessionId = 0): ?DateTime
    {
        $serializedPattern = \sprintf('s:2:"id";i:%d;', $userId);

        $qb = $this->createQueryBuilder('te')
            ->select('te.defaultDate')
            ->where('te.cId = :courseId')
            ->andWhere('te.defaultValueType = :valueType')
            ->andWhere('te.defaultEventType = :eventType')
            ->andWhere('te.defaultValue LIKE :serializedPattern')
            ->setParameter('courseId', $courseId)
            ->setParameter('valueType', 'user_object')
            ->setParameter('eventType', 'user_subscribed')
            ->setParameter('serializedPattern', '%'.$serializedPattern.'%')
        ;

        if ($sessionId > 0) {
            $qb->andWhere('te.sessionId = :sessionId')
                ->setParameter('sessionId', $sessionId)
            ;
        } elseif (0 === $sessionId) {
            $qb->andWhere('te.sessionId = 0');
        } else {
            $qb->andWhere('te.sessionId IS NULL');
        }

        $qb->setMaxResults(1);
        $query = $qb->getQuery();

        try {
            $result = $query->getOneOrNullResult();
            if ($result && isset($result['defaultDate'])) {
                return $result['defaultDate'] instanceof DateTime
                    ? $result['defaultDate']
                    : new DateTime($result['defaultDate']);
            }
        } catch (Exception $e) {
            throw new RuntimeException('Error fetching registration date: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Registers an event when a validation token is used.
     */
    public function registerTokenUsedEvent(ValidationToken $token, ?int $userId = null): void
    {
        $event = new TrackEDefault();
        $event->setDefaultUserId($userId ?? 0);
        $event->setCId(null);
        $event->setDefaultDate(new \DateTime());
        $event->setDefaultEventType('VALIDATION_TOKEN_USED');
        $event->setDefaultValueType('validation_token');
        $event->setDefaultValue(\json_encode(['hash' => $token->getHash()]));
        $event->setSessionId(null);

        $this->_em->persist($event);
        $this->_em->flush();
    }
}

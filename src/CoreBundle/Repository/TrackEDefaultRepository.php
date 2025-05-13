<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\TrackEDefault;
use Chamilo\CoreBundle\Entity\ValidationToken;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;

class TrackEDefaultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly Security $security)
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
        $event->setDefaultDate(new DateTime());
        $event->setDefaultEventType('VALIDATION_TOKEN_USED');
        $event->setDefaultValueType('validation_token');
        $event->setDefaultValue(json_encode(['hash' => $token->getHash()]));
        $event->setSessionId(null);

        $this->_em->persist($event);
        $this->_em->flush();
    }

    /**
     * Registers a specific event such as ticket unsubscribe.
     */
    public function registerTicketUnsubscribeEvent(int $ticketId, int $userId): void
    {
        $event = new TrackEDefault();
        $event->setDefaultUserId($userId);
        $event->setCId($ticketId);
        $event->setDefaultDate(new DateTime());
        $event->setDefaultEventType('ticket_unsubscribe');
        $event->setDefaultValueType('ticket_event');
        $event->setDefaultValue(json_encode(['user_id' => $userId, 'ticket_id' => $ticketId, 'action' => 'unsubscribe']));
        $event->setSessionId(null);

        $this->_em->persist($event);
        $this->_em->flush();
    }

    public function registerResourceEvent(
        ResourceNode $resourceNode,
        string $eventType,
        ?int $userId = null
    ): void {
        if (!$userId) {
            $user = $this->security->getUser();
            if ($user && method_exists($user, 'getId')) {
                $userId = $user->getId();
            }
        }

        $link = $resourceNode->getResourceLinks()->first();
        $courseId = $link?->getCourse()?->getId();
        $sessionId = $link?->getSession()?->getId();

        $event = new TrackEDefault();
        $event->setDefaultUserId($userId ?? 0);
        $event->setCId($courseId);
        $event->setSessionId($sessionId);
        $event->setDefaultDate(new \DateTime());
        $event->setDefaultEventType($eventType);
        $event->setDefaultValueType('resource_type_' . ($resourceNode->getResourceType()?->getTitle() ?? 'unknown'));
        $event->setDefaultValue((string) $resourceNode->getId());

        $this->_em->persist($event);
        $this->_em->flush();
    }
}

<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ConferenceActivity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for the ConferenceActivity entity.
 */
class ConferenceActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConferenceActivity::class);
    }

    public function findOpenWithSameInAndOutTime(int $meetingId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.meeting = :meetingId')
            ->andWhere('a.inAt = a.outAt')
            ->andWhere('a.close = :open')
            ->setParameter('meetingId', $meetingId)
            ->setParameter('open', \BbbPlugin::ROOM_OPEN)
            ->getQuery()
            ->getResult();
    }

    public function closeAllByMeetingId(int $meetingId): void
    {
        $this->createQueryBuilder('a')
            ->update()
            ->set('a.close', ':closed')
            ->where('a.meeting = :meetingId')
            ->setParameter('closed', \BbbPlugin::ROOM_CLOSE)
            ->setParameter('meetingId', $meetingId)
            ->getQuery()
            ->execute();
    }

    public function findOneArrayByMeetingAndParticipant(int $meetingId, int $participantId): ?array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.meeting = :meetingId')
            ->andWhere('a.participant = :participantId')
            ->orderBy('a.id', 'DESC')
            ->setMaxResults(1)
            ->setParameters([
                'meetingId' => $meetingId,
                'participantId' => $participantId,
            ]);

        $result = $qb->getQuery()->getResult();

        if (empty($result)) {
            return null;
        }

        $entity = $result[0];

        return [
            'id' => $entity->getId(),
            'meeting_id' => $entity->getMeeting()?->getId(),
            'participant_id' => $entity->getParticipant()?->getId(),
            'in_at' => $entity->getInAt()?->format('Y-m-d H:i:s'),
            'out_at' => $entity->getOutAt()?->format('Y-m-d H:i:s'),
            'close' => $entity->isClose(),
            'type' => $entity->getType(),
            'event' => $entity->getEvent(),
            'activity_data' => $entity->getActivityData(),
            'signature_file' => $entity->getSignatureFile(),
            'signed_at' => $entity->getSignedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}

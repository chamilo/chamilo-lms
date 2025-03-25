<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ConferenceRecording;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for the ConferenceRecording entity.
 */
class ConferenceRecordingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConferenceRecording::class);
    }

    /**
     * Find recordings by meetingId (remoteId).
     */
    public function findByMeetingId(string $meetingId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.meetingId = :meetingId')
            ->setParameter('meetingId', $meetingId)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Delete all recordings by meetingId.
     */
    public function deleteByMeetingId(string $meetingId): void
    {
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->delete('Chamilo\CoreBundle\Entity\ConferenceRecording', 'r')
            ->where('r.meetingId = :meetingId')
            ->setParameter('meetingId', $meetingId)
            ->getQuery()
            ->execute();
    }

    /**
     * Find a recording by recordId.
     */
    public function findByRecordId(string $recordId): ?array
    {
        $result = $this->createQueryBuilder('r')
            ->where('r.recordId = :recordId')
            ->setParameter('recordId', $recordId)
            ->getQuery()
            ->getArrayResult();

        return $result[0] ?? null;
    }

    public function findByMeetingRemoteId(string $remoteId): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.meeting', 'm')
            ->where('m.remoteId = :remoteId')
            ->setParameter('remoteId', $remoteId)
            ->getQuery()
            ->getArrayResult();
    }
}

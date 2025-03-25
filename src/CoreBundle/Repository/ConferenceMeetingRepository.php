<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ConferenceMeeting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for the ConferenceMeeting entity.
 */
class ConferenceMeetingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConferenceMeeting::class);
    }

    public function findByMeetingRemoteId(string $remoteId): array
    {
        $qb = $this->createQueryBuilder('r')
            ->innerJoin('r.meeting', 'm')
            ->where('m.remoteId = :remoteId')
            ->setParameter('remoteId', $remoteId);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Find a meeting by remote ID and access URL, return as associative array.
     */
    public function findOneByRemoteIdAndAccessUrl(string $remoteId, int $accessUrlId): ?array
    {
        return $this->createQueryBuilder('m')
            ->select('m.id', 'IDENTITY(m.user) AS user_id', 'm.remoteId', 'm.status', 'm.videoUrl')
            ->where('m.remoteId = :remoteId')
            ->andWhere('m.accessUrl = :accessUrlId')
            ->setParameter('remoteId', $remoteId)
            ->setParameter('accessUrlId', $accessUrlId)
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult()[0] ?? null;
    }

    /**
     * Find meeting by ID and return as array.
     */
    public function findOneAsArrayById(int $id): ?array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1);

        $result = $qb->getQuery()->getArrayResult();

        return $result[0] ?? null;
    }

    /**
     * Insert a new ConferenceMeeting and flush immediately.
     */
    public function insert(ConferenceMeeting $meeting): void
    {
        $this->_em->persist($meeting);
        $this->_em->flush();
    }

    /**
     * Update the video URL for a meeting.
     */
    public function updateVideoUrl(int $id, string $url): void
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->update(ConferenceMeeting::class, 'm')
            ->set('m.videoUrl', ':url')
            ->where('m.id = :id')
            ->setParameter('url', $url)
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }

    /**
     * Update visibility (1 = visible, 0 = invisible).
     */
    public function updateVisibility(int $id, bool $visible): void
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->update(ConferenceMeeting::class, 'm')
            ->set('m.visibility', ':visible')
            ->where('m.id = :id')
            ->setParameter('visible', $visible ? 1 : 0)
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }

    /**
     * Close the meeting (status = 0, update closed_at).
     */
    public function closeMeeting(int $id, \DateTimeInterface $closedAt): void
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->update(ConferenceMeeting::class, 'm')
            ->set('m.status', 0)
            ->set('m.closedAt', ':closedAt')
            ->where('m.id = :id')
            ->setParameter('closedAt', $closedAt)
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }

    /**
     * Delete meeting by ID.
     */
    public function deleteById(int $id): void
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->delete(ConferenceMeeting::class, 'm')
            ->where('m.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }

    /**
     * Find meetings created between two dates.
     *
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     * @return ConferenceMeeting[]
     */
    public function findByDateRange(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $start->format('Y-m-d 00:00:00'))
            ->setParameter('end', $end->format('Y-m-d 23:59:59'))
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

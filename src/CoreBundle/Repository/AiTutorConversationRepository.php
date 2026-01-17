<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AiTutorConversation;
use Chamilo\CoreBundle\Entity\AiTutorMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class AiTutorConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AiTutorConversation::class);
    }

    public function findOneByUserCourseProvider(int $userId, int $courseId, string $provider): ?AiTutorConversation
    {
        return $this->createQueryBuilder('c')
            ->andWhere('IDENTITY(c.user) = :uid')
            ->andWhere('IDENTITY(c.course) = :cid')
            ->andWhere('c.aiProvider = :p')
            ->setParameter('uid', $userId)
            ->setParameter('cid', $courseId)
            ->setParameter('p', $provider)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return AiTutorMessage[]
     */
    public function findMessages(AiTutorConversation $conversation, int $limit = 0): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('m')
            ->from(AiTutorMessage::class, 'm')
            ->andWhere('m.conversation = :c')
            ->setParameter('c', $conversation)
            ->orderBy('m.createdAt', 'ASC')
        ;

        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return AiTutorMessage[]
     */
    public function findMessagesSlice(AiTutorConversation $conversation, int $offset, int $limit): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('m')
            ->from(AiTutorMessage::class, 'm')
            ->andWhere('m.conversation = :c')
            ->setParameter('c', $conversation)
            ->orderBy('m.createdAt', 'ASC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(0, $limit))
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return AiTutorMessage[]
     */
    public function findMessagesSinceId(AiTutorConversation $conversation, int $sinceId, int $limit = 80): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('m')
            ->from(AiTutorMessage::class, 'm')
            ->andWhere('m.conversation = :c')
            ->andWhere('m.id > :sid')
            ->setParameter('c', $conversation)
            ->setParameter('sid', $sinceId)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult()
        ;
    }

    public function countMessages(AiTutorConversation $conversation): int
    {
        $n = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(m.id)')
            ->from(AiTutorMessage::class, 'm')
            ->andWhere('m.conversation = :c')
            ->setParameter('c', $conversation)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) ($n ?? 0);
    }

    public function clearMessages(AiTutorConversation $conversation): int
    {
        return (int) $this->getEntityManager()
            ->createQueryBuilder()
            ->delete(AiTutorMessage::class, 'm')
            ->andWhere('m.conversation = :c')
            ->setParameter('c', $conversation)
            ->getQuery()
            ->execute()
        ;
    }

    public function getLastMessageId(AiTutorConversation $conversation): int
    {
        $id = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('MAX(m.id)')
            ->from(AiTutorMessage::class, 'm')
            ->andWhere('m.conversation = :c')
            ->setParameter('c', $conversation)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) ($id ?? 0);
    }
}

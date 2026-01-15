<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AiTutorConversation;
use Chamilo\CoreBundle\Entity\AiTutorMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AiTutorConversationRepository extends ServiceEntityRepository
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
            ->getOneOrNullResult();
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
            ->orderBy('m.createdAt', 'ASC');

        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function clearMessages(AiTutorConversation $conversation): int
    {
        return (int) $this->getEntityManager()
            ->createQueryBuilder()
            ->delete(AiTutorMessage::class, 'm')
            ->andWhere('m.conversation = :c')
            ->setParameter('c', $conversation)
            ->getQuery()
            ->execute();
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
            ->getSingleScalarResult();

        return (int) ($id ?? 0);
    }
}

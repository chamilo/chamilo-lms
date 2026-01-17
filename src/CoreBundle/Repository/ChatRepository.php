<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Chat as ChatEntity;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ChatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatEntity::class);
    }

    public function insertMessage(
        int $fromUser,
        int $toUser,
        string $message,
        int $recd,
        DateTimeImmutable $sentUtc
    ): int {
        $chat = (new ChatEntity())
            ->setFromUser($fromUser)
            ->setToUser($toUser)
            ->setMessage($message)
            ->setRecd($recd)
            ->setSent(DateTime::createFromImmutable($sentUtc))
        ;

        $em = $this->getEntityManager();
        $em->persist($chat);
        $em->flush();

        return (int) $chat->getId();
    }

    /**
     * Inserts a chat row and returns its id.
     * Uses UTC date string (Y-m-d H:i:s) to remain compatible with legacy helpers.
     */
    public function insertChatRow(
        int $fromUser,
        int $toUser,
        string $message,
        int $recd,
        string $sentUtcDateTime
    ): int {
        $sent = DateTime::createFromFormat('Y-m-d H:i:s', $sentUtcDateTime, new DateTimeZone('UTC'));
        if (false === $sent) {
            $sent = new DateTime('now', new DateTimeZone('UTC'));
        }

        $entity = (new ChatEntity())
            ->setFromUser($fromUser)
            ->setToUser($toUser)
            ->setMessage($message)
            ->setSent($sent)
            ->setRecd($recd)
        ;

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return (int) $entity->getId();
    }
}

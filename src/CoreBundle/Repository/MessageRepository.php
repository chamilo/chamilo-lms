<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class MessageRepository.
 */
class MessageRepository extends ServiceEntityRepository
{
    /**
     * MessageRepository constructor.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * @param int $lastMessageId
     */
    public function getFromLastOneReceived(User $user, $lastMessageId = 0)
    {
        $qb = $this->createQueryBuilder('m');

        $qb
            ->where(
                $qb->expr()->eq('m.userReceiver', $user->getId())
            )
            ->andWhere(
                $qb->expr()->eq('m.msgStatus', MESSAGE_STATUS_UNREAD)
            )
            ->andWhere(
                $qb->expr()->gt('m.id', (int) $lastMessageId)
            )
            ->orderBy(
                'm.sendDate',
                'DESC'
            );

        return $qb->getQuery()->getResult();
    }
}

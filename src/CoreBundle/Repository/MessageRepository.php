<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function getFromLastOneReceived(User $user, int $lastMessageId = 0)
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
                $qb->expr()->gt('m.id', $lastMessageId)
            )
            ->orderBy(
                'm.sendDate',
                Criteria::DESC
            )
        ;

        return $qb->getQuery()->getResult();
    }
}

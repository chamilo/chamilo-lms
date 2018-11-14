<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\UserBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Class MessageRepository.
 *
 * @package Chamilo\CoreBundle\Repository
 */
class MessageRepository extends ServiceEntityRepository
{
    /**
     * MessageRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * @param User $user
     * @param int  $lastMessageId
     *
     * @return mixed
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

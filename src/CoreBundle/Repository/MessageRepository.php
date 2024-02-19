<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Traits\Repository\RepositoryQueryBuilderTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    use RepositoryQueryBuilderTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function update(Message $message, bool $andFlush = true): void
    {
        $this->getEntityManager()->persist($message);
        if ($andFlush) {
            $this->getEntityManager()->flush();
        }
    }

    public function delete(Message $message): void
    {
        $this->getEntityManager()->remove($message);
        $this->getEntityManager()->flush();
    }

    /**
     * @return Message[]
     */
    public function getMessageByUser(User $user, int $type)
    {
        $qb = $this->addReceiverQueryBuilder($user);
        $qb = $this->addMessageTypeQueryBuilder($type, $qb);

        return $qb->getQuery()->getResult();
    }

    protected function addReceiverQueryBuilder(User $user, ?QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'm');
        $qb
            ->join('m.receivers', 'r')
            ->andWhere('r.receiver = :user')
            ->setParameter('user', $user)
        ;

        return $qb;
    }

    protected function addMessageTypeQueryBuilder(int $type, ?QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'm');
        $qb
            ->andWhere('m.msgType = :type')
            ->setParameter('type', $type)
        ;

        return $qb;
    }

    public function findByGroupId(int $groupId)
    {
        $qb = $this->createQueryBuilder('m');
        $qb->where('m.group = :groupId')
            ->andWhere('m.status NOT IN (:excludedStatuses)')
            ->setParameter('groupId', $groupId)
            ->setParameter('excludedStatuses', [Message::MESSAGE_STATUS_DRAFT, Message::MESSAGE_STATUS_DELETED])
            ->orderBy('m.id', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }

    public function findReceivedInvitationsByUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.receivers', 'mr')
            ->where('mr.receiver = :user')
            ->andWhere('m.msgType = :msgType')
            ->andWhere('m.status = :status')
            ->setParameters([
                'user' => $user,
                'msgType' => Message::MESSAGE_TYPE_INVITATION,
                'status' => Message::MESSAGE_STATUS_INVITATION_PENDING,
            ])
            ->getQuery()
            ->getResult();
    }

    public function findSentInvitationsByUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.sender = :user')
            ->andWhere('m.msgType = :msgType')
            ->andWhere('m.status = :status')
            ->setParameters([
                'user' => $user,
                'msgType' => Message::MESSAGE_TYPE_INVITATION,
                'status' => Message::MESSAGE_STATUS_INVITATION_PENDING,
            ])
            ->getQuery()
            ->getResult();
    }

    public function sendInvitationToFriend(User $userSender, User $userReceiver, string $messageTitle, string $messageContent): bool
    {
        $existingInvitations = $this->findSentInvitationsByUserAndStatus($userSender, $userReceiver, [
            Message::MESSAGE_STATUS_INVITATION_PENDING,
            Message::MESSAGE_STATUS_INVITATION_ACCEPTED,
            Message::MESSAGE_STATUS_INVITATION_DENIED
        ]);

        if (count($existingInvitations) > 0) {
            // Invitation already exists
            return false;
        }

        $message = new Message();
        $message->setSender($userSender);
        $message->setMsgType(Message::MESSAGE_TYPE_INVITATION);
        $message->setStatus(Message::MESSAGE_STATUS_INVITATION_PENDING);
        $message->setSendDate(new \DateTime());
        $message->setTitle($messageTitle);
        $message->setContent(nl2br($messageContent));

        $messageRelUser = new MessageRelUser();
        $messageRelUser->setReceiver($userReceiver);
        $messageRelUser->setReceiverType(MessageRelUser::TYPE_TO);
        $message->addReceiver($messageRelUser);

        $this->_em->persist($message);
        $this->_em->persist($messageRelUser);
        $this->_em->flush();

        return true;
    }

    public function findSentInvitationsByUserAndStatus(User $userSender, User $userReceiver, array $statuses): array
    {
        $qb = $this->createQueryBuilder('m');
        $qb->join('m.receivers', 'mr')
            ->where('m.sender = :sender')
            ->andWhere('mr.receiver = :receiver')
            ->andWhere('m.msgType = :msgType')
            ->andWhere($qb->expr()->in('m.status', ':statuses'))
            ->setParameters([
                'sender' => $userSender,
                'receiver' => $userReceiver,
                'msgType' => Message::MESSAGE_TYPE_INVITATION,
                'statuses' => $statuses,
            ]);

        return $qb->getQuery()->getResult();
    }

    public function invitationAccepted(User $sender, User $receiver): bool
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select('m')
            ->from(Message::class, 'm')
            ->where('m.sender = :sender')
            ->andWhere('m.status = :status')
            ->setParameter('sender', $sender)
            ->setParameter('status', Message::MESSAGE_STATUS_INVITATION_PENDING);

        $messages = $queryBuilder->getQuery()->getResult();

        foreach ($messages as $message) {
            $messageRelUser = $this->_em->getRepository(MessageRelUser::class)->findOneBy([
                'message' => $message,
                'receiver' => $receiver
            ]);

            if ($messageRelUser) {
                $invitation = $messageRelUser->getMessage();
                $invitation->setStatus(Message::MESSAGE_STATUS_INVITATION_ACCEPTED);

                $this->_em->flush();

                $friendship = $this->_em->getRepository(UserRelUser::class)->findOneBy([
                    'user' => $sender,
                    'friend' => $receiver,
                ]) ?: new UserRelUser();

                $friendship->setUser($sender);
                $friendship->setFriend($receiver);
                $friendship->setRelationType(UserRelUser::USER_RELATION_TYPE_FRIEND);

                $this->_em->persist($friendship);
                $this->_em->flush();
                return true;
            }
        }

        return false;
    }


    public function invitationDenied(User $sender, User $receiver): bool
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select('m')
            ->from(Message::class, 'm')
            ->where('m.sender = :sender')
            ->andWhere('m.status = :status')
            ->setParameter('sender', $sender)
            ->setParameter('status', Message::MESSAGE_STATUS_INVITATION_PENDING);

        $messages = $queryBuilder->getQuery()->getResult();

        foreach ($messages as $message) {
            $messageRelUser = $this->_em->getRepository(MessageRelUser::class)->findOneBy([
                'message' => $message,
                'receiver' => $receiver
            ]);

            if ($messageRelUser) {
                $this->_em->remove($messageRelUser);
                $this->_em->flush();
                return true;
            }
        }

        return false;
    }

}

<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Traits\Repository\RepositoryQueryBuilderTrait;
use DateTime;
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

    public function getMessagesByGroup(int $groupId, bool $mainMessagesOnly = false): array
    {
        $qb = $this->createQueryBuilder('m');

        $qb->where('m.group = :group')
            ->andWhere('m.msgType = :msgType')
            ->setParameter('group', $groupId)
            ->setParameter('msgType', Message::MESSAGE_TYPE_GROUP)
        ;

        if ($mainMessagesOnly) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->isNull('m.parent'),
                $qb->expr()->eq('m.parent', ':zeroParent')
            ))
                ->setParameter('zeroParent', 0)
            ;
        }

        $qb->orderBy('m.id', 'ASC');

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
            ->getResult()
        ;
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
            ->getResult()
        ;
    }

    public function sendInvitationToFriend(User $userSender, User $userReceiver, string $messageTitle, string $messageContent): bool
    {
        if ($this->existingInvitations($userSender, $userReceiver)) {
            // Invitation already exists
            return false;
        }

        $message = new Message();
        $message->setSender($userSender);
        $message->setMsgType(Message::MESSAGE_TYPE_INVITATION);
        $message->setStatus(Message::MESSAGE_STATUS_INVITATION_PENDING);
        $message->setSendDate(new DateTime());
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

    public function existingInvitations(User $userSender, User $userReceiver): bool
    {
        $existingInvitations = $this->findSentInvitationsByUserAndStatus($userSender, $userReceiver, [
            Message::MESSAGE_STATUS_INVITATION_PENDING,
            Message::MESSAGE_STATUS_INVITATION_ACCEPTED,
            Message::MESSAGE_STATUS_INVITATION_DENIED,
        ]);

        return \count($existingInvitations) > 0;
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
            ])
        ;

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
            ->setParameter('status', Message::MESSAGE_STATUS_INVITATION_PENDING)
        ;

        $messages = $queryBuilder->getQuery()->getResult();

        foreach ($messages as $message) {
            $messageRelUser = $this->_em->getRepository(MessageRelUser::class)->findOneBy([
                'message' => $message,
                'receiver' => $receiver,
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
            ->setParameter('status', Message::MESSAGE_STATUS_INVITATION_PENDING)
        ;

        $messages = $queryBuilder->getQuery()->getResult();

        foreach ($messages as $message) {
            $messageRelUser = $this->_em->getRepository(MessageRelUser::class)->findOneBy([
                'message' => $message,
                'receiver' => $receiver,
            ]);

            if ($messageRelUser) {
                $this->_em->remove($messageRelUser);
                $this->_em->flush();

                return true;
            }
        }

        return false;
    }

    public function getMessagesByGroupAndMessage(int $groupId, int $messageId): array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.group = :groupId')
            ->andWhere('m.msgType = :msgType')
            ->setParameter('groupId', $groupId)
            ->setParameter('msgType', Message::MESSAGE_TYPE_GROUP)
            ->orderBy('m.id', 'ASC')
        ;

        $allMessages = $qb->getQuery()->getResult();

        return $this->filterMessagesStartingFromId($allMessages, $messageId);
    }

    public function deleteTopicAndChildren(int $groupId, int $topicId): void
    {
        $entityManager = $this->getEntityManager();
        $messages = $this->createQueryBuilder('m')
            ->where('m.group = :groupId AND (m.id = :topicId OR m.parent = :topicId)')
            ->setParameter('groupId', $groupId)
            ->setParameter('topicId', $topicId)
            ->getQuery()
            ->getResult()
        ;

        /** @var Message $message */
        foreach ($messages as $message) {
            $message->setMsgType(Message::MESSAGE_STATUS_DELETED);
            $entityManager->persist($message);
        }

        $entityManager->flush();
    }

    /**
     * Filters messages starting from a specific message ID.
     * This function first adds the message with the given start ID to the filtered list.
     * Then, it checks all messages to find descendants of the message with the start ID
     * and adds them to the filtered list as well.
     */
    private function filterMessagesStartingFromId(array $messages, int $startId): array
    {
        $filtered = [];

        foreach ($messages as $message) {
            if ($message->getId() == $startId) {
                $filtered[] = $message;

                break;
            }
        }

        foreach ($messages as $message) {
            if ($this->isDescendantOf($message, $startId, $messages)) {
                $filtered[] = $message;
            }
        }

        return $filtered;
    }

    /**
     * Determines if a given message is a descendant of another message identified by startId.
     * A descendant is a message that has a chain of parent messages leading up to the message
     * with the startId. This function iterates up the parent chain of the given message to
     * check if any parent matches the startId.
     */
    private function isDescendantOf(Message $message, int $startId, array $allMessages): bool
    {
        while ($parent = $message->getParent()) {
            if ($parent->getId() == $startId) {
                return true;
            }

            $filteredMessages = array_filter($allMessages, function ($m) use ($parent) {
                return $m->getId() === $parent->getId();
            });

            $message = \count($filteredMessages) ? array_values($filteredMessages)[0] : null;

            if (!$message) {
                break;
            }
        }

        return false;
    }

    public function usersHaveSharedMessages(?User $currentUser, ?User $targetUser): bool
    {
        if (null === $currentUser || null === $targetUser) {
            return false;
        }

        $qb = $this->createQueryBuilder('m');
        $qb->select('m')
            ->innerJoin('m.receivers', 'mr')
            ->where('mr.receiver = :userTwo')
            ->andWhere('m.sender = :userOne')
            ->setParameters([
                'userOne' => $targetUser,
                'userTwo' => $currentUser,
            ])
            ->setMaxResults(1)
        ;

        return \count($qb->getQuery()->getResult()) > 0;
    }
}

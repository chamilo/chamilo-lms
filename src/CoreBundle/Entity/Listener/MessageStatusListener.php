<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;

class MessageStatusListener
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function postRemove(MessageRelUser $messageRelUser, LifecycleEventArgs $args): void
    {
        $message = $messageRelUser->getMessage();
        $remainingReceivers = $this->entityManager->getRepository(MessageRelUser::class)
            ->count(['message' => $message]);

        if ($remainingReceivers === 0) {
            $message->setStatus(Message::MESSAGE_STATUS_DELETED);
            $this->entityManager->flush();
        }
    }
}

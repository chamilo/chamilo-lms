<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Gedmo\SoftDeleteable\Event\PostSoftDeleteEventArgs;
use Gedmo\SoftDeleteable\SoftDeleteableListener;

#[AsDoctrineListener(event: SoftDeleteableListener::POST_SOFT_DELETE, connection: 'default')]
class MessageStatusListener
{
    public function postSoftDelete(PostSoftDeleteEventArgs $args): void
    {
        $object = $args->getObject();

        if (!$object instanceof MessageRelUser) {
            return;
        }

        $ob = $args->getObjectManager();

        $message = $object->getMessage();
        $remainingReceivers = $message
            ->getReceivers()
            ->filter(
                fn (MessageRelUser $messageRelUser) => !$messageRelUser->isDeleted()
            )
            ->count()
        ;

        if (0 === $remainingReceivers) {
            $message->setStatus(Message::MESSAGE_STATUS_DELETED);
            $ob->flush();
        }
    }
}

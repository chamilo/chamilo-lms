<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;

class MessageListener
{
    public function postLoad(Message $message, PostLoadEventArgs $args): void
    {
        $om = $args->getObjectManager();
        $messageRelUserRepo = $om->getRepository(MessageRelUser::class);

        $softDeleteable = $om->getFilters()->enable('softdeleteable');

        if ($softDeleteable instanceof SoftDeleteableFilter) {
            $softDeleteable->disableForEntity(MessageRelUser::class);
        }

        $message->setReceiversFromArray(
            $messageRelUserRepo->findBy(['message' => $message])
        );

        if ($softDeleteable instanceof SoftDeleteableFilter) {
            $softDeleteable->enableForEntity(Message::class);
        }
    }
}

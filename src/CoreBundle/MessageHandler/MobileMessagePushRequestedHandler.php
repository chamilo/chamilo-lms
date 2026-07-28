<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\MessageHandler;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Message\MobileMessagePushRequested;
use Chamilo\CoreBundle\Push\MobileMessagePushSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class MobileMessagePushRequestedHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MobileMessagePushSender $pushSender,
    ) {}

    public function __invoke(MobileMessagePushRequested $request): void
    {
        $message = $this->entityManager->find(Message::class, $request->messageId);
        $accessUrl = $this->entityManager->find(AccessUrl::class, $request->accessUrlId);

        if (!$message instanceof Message || !$accessUrl instanceof AccessUrl) {
            return;
        }

        $this->pushSender->send($message, $accessUrl);
    }
}

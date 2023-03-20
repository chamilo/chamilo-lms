<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use ApiPlatform\Core\DataPersister\ResumableDataPersisterInterface;
use Chamilo\CoreBundle\Entity\Message;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageDataPersister implements ContextAwareDataPersisterInterface, ResumableDataPersisterInterface
{
    private EntityManager $entityManager;
    private ContextAwareDataPersisterInterface $decorated;
    private MessageBusInterface $bus;

    public function __construct(ContextAwareDataPersisterInterface $decorated, EntityManager $entityManager, MessageBusInterface $bus)
    {
        $this->decorated = $decorated;
        $this->entityManager = $entityManager;
        $this->bus = $bus;
    }

    public function supports($data, array $context = []): bool
    {
        return $this->decorated->supports($data, $context);
    }

    public function persist($data, array $context = [])
    {
        $result = $this->decorated->persist($data, $context);

        if ($data instanceof Message && (
            ($context['collection_operation_name'] ?? null) === 'post' ||
                ($context['graphql_operation_name'] ?? null) === 'create'
                //($context['item_operation_name'] ?? null) === 'put' // on update
        )
        ) {
            /*if (Message::MESSAGE_TYPE_INBOX === $result->getMsgType()) {
                $messageSent = clone $result;
                $messageSent
                    ->setMsgType(Message::MESSAGE_TYPE_OUTBOX)
                    //->setRead(true)
                ;
                $this->entityManager->persist($messageSent);
                $this->entityManager->flush();
                echo 'send11';

                // Send message.
                $this->bus->dispatch($data);
            }*/
        }

        /*$this->entityManager->persist($data);
        $this->entityManager->flush();*/

        return $result;
    }

    public function remove($data, array $context = []): void
    {
        $this->entityManager->remove($data);
        $this->entityManager->flush();
    }

    public function resumable(array $context = []): bool
    {
        return true;
    }
}

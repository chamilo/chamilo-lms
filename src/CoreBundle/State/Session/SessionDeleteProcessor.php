<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Session;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\SessionDeletedEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Announces SESSION_DELETED before API Platform removes the session, so listeners
 * can drop the rows that reference it while it still exists.
 *
 * @implements ProcessorInterface<Session, Session|null>
 */
final readonly class SessionDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private ProcessorInterface $removeProcessor,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?Session
    {
        if ($data instanceof Session) {
            $this->eventDispatcher->dispatch(
                new SessionDeletedEvent(['session' => $data], AbstractEvent::TYPE_PRE),
                Events::SESSION_DELETED
            );
        }

        return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
    }
}

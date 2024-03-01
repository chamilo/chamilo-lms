<?php

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Exception;
use Symfony\Component\Security\Core\Security;

/**
 * @implements ProcessorInterface<CCalendarEvent>
 */
class CCalendarEventProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly Security $security,
    ) {
    }

    /**
     * @throws Exception
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): CCalendarEvent
    {
        assert($data instanceof CCalendarEvent);

        /** @var User $currentUser */
        $currentUser = $this->security->getUser();

        $data->setCreator($currentUser);


        if ($this->isPersonalEvent($data)) {
            if ($currentUser->getResourceNode()->getId() !== $data->getParentResourceNode()) {
                throw new Exception('Not allowed');
            }
        }

        /** @var CCalendarEvent $result */
        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        return $result;
    }

    private function isPersonalEvent(CCalendarEvent $event): bool
    {
        $type = 'personal';

        if (!empty($event->getResourceLinkArray())) {
            foreach ($event->getResourceLinkArray() as $link) {
                if (isset($link['cid'])) {
                    $type = 'course';

                    break;
                }
            }
        }

        return 'personal' === $type;
    }
}

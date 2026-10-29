<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Deletes a calendar event, or its whole series of repeated events with ?deleteSeries=1, as the
 * "Delete all items" option of the legacy agenda did.
 *
 * @implements ProcessorInterface<CCalendarEvent, mixed>
 */
final readonly class CCalendarEventDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        \assert($data instanceof CCalendarEvent);

        if ($this->requestStack->getCurrentRequest()?->query->getBoolean('deleteSeries')) {
            $this->deleteSeries($data->getParentEvent() ?? $data);

            return null;
        }

        // The other events of the series stay, on their own.
        foreach ($data->getChildren() as $child) {
            $child->setParentEvent(null);
        }

        $this->removeEvent($data);
        $this->entityManager->flush();

        return null;
    }

    private function deleteSeries(CCalendarEvent $firstEvent): void
    {
        $events = [...$firstEvent->getChildren(), $firstEvent];

        foreach ($events as $event) {
            if (!$this->security->isGranted('DELETE', $event)) {
                throw new AccessDeniedHttpException();
            }
        }

        foreach ($events as $event) {
            $this->removeEvent($event);
        }

        $this->entityManager->flush();
    }

    /**
     * Removing the event alone leaves its resource node and its published links readable, and the
     * files of its attachments on disk.
     */
    private function removeEvent(CCalendarEvent $event): void
    {
        foreach ($event->getAttachments() as $attachment) {
            if (null !== $attachment->getResourceNode()) {
                $this->entityManager->remove($attachment->getResourceNode());
            }
        }

        $this->entityManager->remove($event);

        if (null !== $event->getResourceNode()) {
            $this->entityManager->remove($event->getResourceNode());
        }
    }
}

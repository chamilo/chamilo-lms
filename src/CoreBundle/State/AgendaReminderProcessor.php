<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\AgendaReminder;
use Chamilo\CourseBundle\Repository\CCalendarEventRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;

class AgendaReminderProcessor implements ProcessorInterface
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private CCalendarEventRepository $eventRepository
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): AgendaReminder
    {
        \assert($data instanceof AgendaReminder);

        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $payload = json_decode($request->getContent(), true);
            $eventId = $payload['eventId'] ?? null;
            $event = $this->eventRepository->find($eventId);

            if (!$event) {
                throw new \Exception("Event not found with ID: $eventId");
            }

            $type = $this->eventRepository->determineEventType($event);
            $data->setType($type);
            $data->setEventId($eventId);

            $count = $payload['count'] ?? 0;
            $period = $payload['period'] ?? '';

            $data->setDateInterval($this->convertToInterval((int) $count, $period));
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }

    private function convertToInterval(int $count, string $period): DateInterval
    {
        return match ($period) {
            'i' => new DateInterval("PT{$count}M"),
            'h' => new DateInterval("PT{$count}H"),
            'd' => new DateInterval("P{$count}D"),
            default => throw new \InvalidArgumentException("Period not valid: $period"),
        };
    }
}

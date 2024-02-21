<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use Chamilo\CoreBundle\ApiResource\CalendarEvent;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class CalendarEventTransformer implements DataTransformerInterface
{
    public function __construct(
        private readonly RouterInterface $router,
    ) {}

    public function transform($object, string $to, array $context = []): object
    {
        if ($object instanceof Session) {
            return $this->mapSessionToDto($object);
        }

        return $this->mapCCalendarToDto($object);
    }

    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return ($data instanceof CCalendarEvent || $data instanceof Session) && CalendarEvent::class === $to;
    }

    private function mapCCalendarToDto(object $object): CalendarEvent
    {
        \assert($object instanceof CCalendarEvent);

        return new CalendarEvent(
            'calendar_event_'.$object->getIid(),
            $object->getTitle(),
            $object->getContent(),
            $object->getStartDate(),
            $object->getEndDate(),
            $object->isAllDay(),
            null,
            $object->getResourceNode(),
        );
    }

    private function mapSessionToDto(object $object): CalendarEvent
    {
        \assert($object instanceof Session);

        /** @var ?SessionRelCourse $sessionRelCourse */
        $sessionRelCourse = $object->getCourses()->first();
        $course = $sessionRelCourse?->getCourse();

        $sessionUrl = null;

        if ($course) {
            $baseUrl = $this->router->generate('index', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $sessionUrl = "{$baseUrl}course/{$course->getId()}/home?".http_build_query(['sid' => $object->getId()]);
        }

        return new CalendarEvent(
            'session_'.$object->getId(),
            $object->getTitle(),
            $object->getShowDescription() ? $object->getDescription() : null,
            $object->getDisplayStartDate(),
            $object->getDisplayEndDate(),
            false,
            $sessionUrl,
        );
    }
}

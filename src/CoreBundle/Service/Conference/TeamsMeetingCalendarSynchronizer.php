<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Conference;

use Chamilo\CoreBundle\Entity\ConferenceMeeting;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

final readonly class TeamsMeetingCalendarSynchronizer
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function createForMeeting(ConferenceMeeting $meeting, User $creator): void
    {
        if (null !== $meeting->getCalendarId()) {
            return;
        }

        $course = $meeting->getCourse();
        if (!$course instanceof Course) {
            throw new RuntimeException('Only course Teams meetings can be added to the Chamilo agenda.');
        }

        $startAt = $meeting->getStartAt();
        $endAt = $meeting->getEndAt();
        if (null === $startAt || null === $endAt) {
            throw new RuntimeException('The Teams meeting schedule is required to create its agenda event.');
        }

        $event = (new CCalendarEvent())
            ->setTitle($meeting->getTitle())
            ->setContent($this->buildContent($meeting, false))
            ->setStartDate(DateTime::createFromInterface($startAt))
            ->setEndDate(DateTime::createFromInterface($endAt))
            ->setParent($course)
            ->setCreator($creator)
        ;

        $event->addCourseLink($course, $meeting->getSession(), $meeting->getGroup());

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        $eventId = $event->getIid();
        if (null === $eventId) {
            throw new RuntimeException('The Chamilo agenda event could not be created.');
        }

        $meeting->setCalendarId($eventId);
    }

    public function updateFromMeeting(ConferenceMeeting $meeting): void
    {
        $event = $this->findLinkedEvent($meeting);
        if (!$event instanceof CCalendarEvent) {
            return;
        }

        $startAt = $meeting->getStartAt();
        $endAt = $meeting->getEndAt();
        if (null === $startAt || null === $endAt) {
            throw new RuntimeException('The Teams meeting schedule is required to update its agenda event.');
        }

        $event
            ->setTitle($meeting->getTitle())
            ->setContent($this->buildContent($meeting, false))
            ->setStartDate(DateTime::createFromInterface($startAt))
            ->setEndDate(DateTime::createFromInterface($endAt))
        ;
    }

    public function markCancelled(ConferenceMeeting $meeting): void
    {
        $event = $this->findLinkedEvent($meeting);
        if (!$event instanceof CCalendarEvent) {
            return;
        }

        $event
            ->setTitle('Cancelled: '.$meeting->getTitle())
            ->setContent($this->buildContent($meeting, true))
        ;
    }

    private function findLinkedEvent(ConferenceMeeting $meeting): ?CCalendarEvent
    {
        $calendarId = $meeting->getCalendarId();
        if (null === $calendarId || $calendarId <= 0) {
            return null;
        }

        $event = $this->entityManager->find(CCalendarEvent::class, $calendarId);
        if (!$event instanceof CCalendarEvent) {
            $meeting->setCalendarId(null);

            return null;
        }

        return $event;
    }

    private function buildContent(ConferenceMeeting $meeting, bool $cancelled): string
    {
        $title = htmlspecialchars($meeting->getTitle(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        if ($cancelled) {
            return '<p><strong>Microsoft Teams:</strong> '.$title.'</p>'
                .'<p>This meeting has been cancelled in Chamilo.</p>';
        }

        $meetingId = (int) $meeting->getId();
        if ($meetingId <= 0) {
            throw new RuntimeException('The Teams meeting must be persisted before creating its agenda link.');
        }

        $joinUrl = $this->urlGenerator->generate(
            'teams_conference_auth_join',
            ['id' => $meetingId],
            UrlGeneratorInterface::ABSOLUTE_PATH,
        );

        return '<p><strong>Microsoft Teams:</strong> '.$title.'</p>'
            .'<p><a href="'.htmlspecialchars($joinUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"'
            .' target="_blank" rel="noopener noreferrer">Join Microsoft Teams meeting</a></p>';
    }
}

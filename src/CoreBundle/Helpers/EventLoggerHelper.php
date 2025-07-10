<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\TrackEDefault;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class EventLoggerHelper
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CidReqHelper $cidReqHelper,
        private readonly Security $security,
    ) {}

    public function addEvent(
        string $eventType,
        string $valueType,
        $value,
        ?DateTime $dateTime = null,
        ?int $userId = null,
        ?int $courseId = null,
        ?int $sessionId = null
    ): bool {
        if (empty($eventType)) {
            return false;
        }

        $courseId = $courseId ?? $this->cidReqHelper->getCourseId();
        $sessionId = $sessionId ?? $this->cidReqHelper->getSessionId();
        $userId = $userId ?? $this->security->getUser()->getId();

        $value = $this->serializeEventValue($value);

        $trackEvent = new TrackEDefault();
        $trackEvent
            ->setDefaultUserId($userId)
            ->setCId($courseId)
            ->setDefaultDate($dateTime ?? new DateTime('now', new DateTimeZone('UTC')))
            ->setDefaultEventType($eventType)
            ->setDefaultValueType($valueType)
            ->setDefaultValue($value)
            ->setSessionId((int) $sessionId)
        ;

        $this->entityManager->persist($trackEvent);
        $this->entityManager->flush();

        return true;
    }

    private function serializeEventValue($value): string
    {
        return serialize($value);
    }
}

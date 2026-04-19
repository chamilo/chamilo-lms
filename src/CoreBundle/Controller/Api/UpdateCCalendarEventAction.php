<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\AgendaReminder;
use Chamilo\CoreBundle\Entity\Career;
use Chamilo\CoreBundle\Entity\Promotion;
use Chamilo\CoreBundle\Entity\Room;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Repository\CCalendarEventRepository;
use DateTime;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UpdateCCalendarEventAction extends BaseResourceFileAction
{
    public function __invoke(
        CCalendarEvent $calendarEvent,
        Request $request,
        CCalendarEventRepository $repo,
        EntityManager $em,
        SettingsManager $settingsManager,
    ): CCalendarEvent {
        $this->handleUpdateRequest($calendarEvent, $repo, $request, $em);

        $result = json_decode($request->getContent(), true);
        if (!\is_array($result)) {
            throw new BadRequestHttpException('Invalid request payload.');
        }

        $calendarEvent
            ->setContent($result['content'] ?? '')
            ->setComment($result['comment'] ?? '')
            ->setColor($result['color'] ?? '')
            ->setStartDate(new DateTime($result['startDate'] ?? ''))
            ->setEndDate(new DateTime($result['endDate'] ?? ''))
            // ->setAllDay($result['allDay'] ?? false)
            ->setCollective($result['collective'] ?? false)
        ;

        if (\array_key_exists('room', $result)) {
            $roomId = $this->extractIdentifier($result['room']);

            if (null !== $roomId) {
                $room = $em->find(Room::class, $roomId);
                if (!$room instanceof Room) {
                    throw new BadRequestHttpException('Selected room was not found.');
                }

                $calendarEvent->setRoom($room);
            } else {
                $calendarEvent->setRoom(null);
            }
        }

        $this->applyCareerAndPromotionUpdate($calendarEvent, $result, $em, $settingsManager);

        $calendarEvent->getReminders()->clear();

        if (isset($result['reminders']) && \is_array($result['reminders'])) {
            foreach ($result['reminders'] as $reminderInfo) {
                $reminder = new AgendaReminder();
                $reminder->count = $reminderInfo['count'];
                $reminder->period = $reminderInfo['period'];
                $reminder->decodeDateInterval();
                $calendarEvent->addReminder($reminder);
            }
        }

        return $calendarEvent;
    }

    private function applyCareerAndPromotionUpdate(
        CCalendarEvent $calendarEvent,
        array $payload,
        EntityManager $em,
        SettingsManager $settingsManager,
    ): void {
        $allowCareerAgenda = 'true' === $settingsManager->getSetting(
                'agenda.allow_careers_in_global_agenda',
                true
            );

        if (!$allowCareerAgenda || 'global' !== $calendarEvent->determineType()) {
            $calendarEvent->setCareer(null);
            $calendarEvent->setPromotion(null);

            return;
        }

        $careerId = $this->extractIdentifier($payload['career'] ?? null);
        $promotionId = $this->extractIdentifier($payload['promotion'] ?? null);

        $career = null;
        $promotion = null;

        if (null !== $careerId) {
            $career = $em->find(Career::class, $careerId);
            if (!$career instanceof Career) {
                throw new BadRequestHttpException('Selected career was not found.');
            }
        }

        if (null !== $promotionId) {
            $promotion = $em->find(Promotion::class, $promotionId);
            if (!$promotion instanceof Promotion) {
                throw new BadRequestHttpException('Selected promotion was not found.');
            }
        }

        if (null !== $promotion && null === $career) {
            $career = $promotion->getCareer();
        }

        if (
            null !== $promotion &&
            null !== $career &&
            (int) $promotion->getCareer()->getId() !== (int) $career->getId()
        ) {
            throw new BadRequestHttpException('Promotion does not belong to the selected career.');
        }

        $calendarEvent->setCareer($career);
        $calendarEvent->setPromotion($promotion);
    }

    private function extractIdentifier(mixed $value): ?int
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (\is_int($value)) {
            return $value;
        }

        if (\is_string($value)) {
            if (ctype_digit($value)) {
                return (int) $value;
            }

            if (preg_match('/(\d+)$/', $value, $matches)) {
                return (int) $matches[1];
            }

            return null;
        }

        if (\is_array($value)) {
            if (isset($value['id']) && ctype_digit((string) $value['id'])) {
                return (int) $value['id'];
            }

            if (isset($value['@id']) && preg_match('/(\d+)$/', (string) $value['@id'], $matches)) {
                return (int) $matches[1];
            }
        }

        return null;
    }
}

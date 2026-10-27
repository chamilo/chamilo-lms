<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\AgendaReminder;
use Chamilo\CoreBundle\Entity\Career;
use Chamilo\CoreBundle\Entity\Promotion;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Room;
use Chamilo\CoreBundle\Helpers\AgendaHelper;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Repository\CCalendarEventRepository;
use DateTime;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UpdateCCalendarEventAction extends BaseResourceFileAction
{
    public function __invoke(
        CCalendarEvent $calendarEvent,
        Request $request,
        CCalendarEventRepository $repo,
        EntityManager $em,
        SettingsManager $settingsManager,
        AgendaHelper $agendaHelper,
        CidReqHelper $cidReqHelper,
    ): CCalendarEvent {
        // The new links need the right to add an event where they point.
        $this->handleUpdateRequest(
            $calendarEvent,
            $repo,
            $request,
            $em,
            static function (array $links) use ($agendaHelper): void {
                if (!$agendaHelper->canAddLinks($links)) {
                    throw new AccessDeniedHttpException();
                }
            },
            // The agenda form sends the creator's user id as parent; events are scoped by their links.
            false,
        );

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

        if (\array_key_exists('courseRecipients', $result)) {
            $this->replaceCourseRecipients($calendarEvent, $result['courseRecipients'], $agendaHelper, $cidReqHelper, $em);
        }

        $this->applyCareerAndPromotionUpdate($calendarEvent, $result, $em, $settingsManager);
        $this->applyResourceLanguageFromRequest($calendarEvent, $request, $em);

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

    /**
     * Replaces the links of the event in the current course with one link per recipient, or with
     * one link for the whole course when there is no recipient.
     */
    private function replaceCourseRecipients(
        CCalendarEvent $calendarEvent,
        mixed $recipients,
        AgendaHelper $agendaHelper,
        CidReqHelper $cidReqHelper,
        EntityManager $em,
    ): void {
        $course = $cidReqHelper->getDoctrineCourseEntity();
        $resourceNode = $calendarEvent->getResourceNode();
        if (null === $course || null === $resourceNode || !\is_array($recipients)) {
            throw new BadRequestHttpException('Invalid course recipients.');
        }

        $session = $cidReqHelper->getDoctrineSessionEntity();

        $currentLinks = [];
        $visibility = ResourceLink::VISIBILITY_PUBLISHED;
        foreach ($resourceNode->getResourceLinks() as $link) {
            if ($link->getCourse()?->getId() === $course->getId()
                && $link->getSession()?->getId() === $session?->getId()
            ) {
                $currentLinks[$this->getRecipientKey($link->getUser()?->getId(), $link->getGroup()?->getIid())] = $link;
                $visibility = $link->getVisibility();
            }
        }

        $wantedLinks = [];
        foreach ($recipients as $recipient) {
            $userId = (int) ($recipient['uid'] ?? 0);
            $groupId = (int) ($recipient['gid'] ?? 0);
            if (($userId > 0) === ($groupId > 0)) {
                throw new BadRequestHttpException('A recipient is either a user or a group.');
            }

            $wantedLinks[$this->getRecipientKey($userId, $groupId)] = array_filter(['uid' => $userId, 'gid' => $groupId]);
        }

        if ([] === $wantedLinks) {
            $wantedLinks[$this->getRecipientKey(null, null)] = [];
        }

        $newLinks = [];
        foreach (array_diff_key($wantedLinks, $currentLinks) as $recipient) {
            $newLinks[] = ['cid' => $course->getId(), 'sid' => $session?->getId(), 'visibility' => $visibility] + $recipient;
        }

        if ($newLinks && !$agendaHelper->canAddLinks($newLinks)) {
            throw new AccessDeniedHttpException();
        }

        foreach (array_diff_key($currentLinks, $wantedLinks) as $link) {
            $resourceNode->getResourceLinks()->removeElement($link);
            $em->remove($link);
        }

        if ($newLinks) {
            $calendarEvent->setResourceLinkArray($newLinks);
            self::setLinks($calendarEvent, $em);
        }
    }

    private function getRecipientKey(?int $userId, ?int $groupId): string
    {
        return (int) $userId.':'.(int) $groupId;
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
            null !== $promotion
            && null !== $career
            && (int) $promotion->getCareer()->getId() !== (int) $career->getId()
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

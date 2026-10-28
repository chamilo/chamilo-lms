<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\Career;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\Promotion;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\DateTimeHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CCalendarEventRepeat;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

/**
 * @implements ProcessorInterface<CCalendarEvent, CCalendarEvent>
 */
final class CCalendarEventStateProcessor implements ProcessorInterface
{
    /**
     * The repeat types of the legacy agenda, and the time between two events of the series.
     */
    private const array REPEAT_INTERVALS = [
        'daily' => 'P1D',
        'weekly' => 'P1W',
        'monthlyByDate' => 'P1M',
        'yearly' => 'P1Y',
    ];

    private const int MAX_REPEATED_EVENTS = 365;

    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly Security $security,
        private readonly SettingsManager $settingsManager,
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly CidReqHelper $cidReqHelper,
    ) {}

    /**
     * @param mixed $data
     *
     * @throws Exception
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): CCalendarEvent
    {
        \assert($data instanceof CCalendarEvent);

        /** @var User $currentUser */
        $currentUser = $this->security->getUser();

        $data->setCreator($currentUser);

        if ($this->isPersonalEvent($data)) {
            if ($currentUser->getResourceNode()->getId() !== $data->getParentResourceNode()) {
                throw new Exception('Not allowed');
            }
        }

        $this->applyCareerAndPromotionRules($data);

        foreach ($data->getReminders() as $reminder) {
            $reminder->decodeDateInterval();
        }

        $repeat = $this->getRepeatRequest($data);

        /** @var CCalendarEvent $result */
        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        $this->applyLanguageFromPayload($result, $this->getRequestPayload());

        if (null !== $repeat) {
            $this->addRepeatedEvents($result, $currentUser, ...$repeat);
        }

        $this->entityManager->flush();

        return $result;
    }

    /**
     * The repeat type and the last day of the series, when the course event is repeated.
     *
     * @return array{string, DateTimeImmutable}|null
     */
    private function getRepeatRequest(CCalendarEvent $event): ?array
    {
        $payload = $this->getRequestPayload();
        $type = $payload['repeatType'] ?? null;
        if (null === $type || '' === $type) {
            return null;
        }

        if (!\is_string($type) || !isset(self::REPEAT_INTERVALS[$type])) {
            throw new BadRequestHttpException('Invalid repeat type.');
        }

        // As in the legacy agenda, only course events are repeated.
        if (null === $this->cidReqHelper->getDoctrineCourseEntity()) {
            throw new BadRequestHttpException('Only course events can be repeated.');
        }

        $timezone = new DateTimeZone(DateTimeHelper::resolveTimezone());
        $lastDay = DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($payload['repeatEndDate'] ?? ''), $timezone);
        if (false === $lastDay || null === $event->getStartDate() || null === $event->getEndDate()) {
            throw new BadRequestHttpException('A valid repeat end date is required.');
        }

        // The last event of the series starts on that day at the latest.
        $lastDay = $lastDay->setTime(23, 59, 59);
        if ($lastDay < $event->getStartDate()) {
            throw new BadRequestHttpException('The repeat end date must be after the event.');
        }

        return [$type, $lastDay];
    }

    /**
     * Creates a copy of the event at each repetition until the last day, as Agenda::addRepeatedItem() did.
     */
    private function addRepeatedEvents(CCalendarEvent $event, User $creator, string $type, DateTimeImmutable $lastDay): void
    {
        $interval = new DateInterval(self::REPEAT_INTERVALS[$type]);
        $utc = new DateTimeZone('UTC');

        // The dates move in the user's timezone, so the events keep their local time across a DST change.
        $start = DateTimeImmutable::createFromMutable($event->getStartDate())->setTimezone($lastDay->getTimezone());
        $end = DateTimeImmutable::createFromMutable($event->getEndDate())->setTimezone($lastDay->getTimezone());

        for ($i = 0; $i < self::MAX_REPEATED_EVENTS; $i++) {
            $start = $start->add($interval);
            $end = $end->add($interval);
            if ($start > $lastDay) {
                break;
            }

            $repeatedEvent = (new CCalendarEvent())
                ->setTitle($event->getTitle())
                ->setContent((string) $event->getContent())
                ->setComment((string) $event->getComment())
                ->setColor((string) $event->getColor())
                ->setAllDay($event->isAllDay())
                ->setRoom($event->getRoom())
                ->setStartDate(DateTime::createFromImmutable($start->setTimezone($utc)))
                ->setEndDate(DateTime::createFromImmutable($end->setTimezone($utc)))
                ->setParentEvent($event)
                ->setCreator($creator)
                ->setParentResourceNode($event->getParentResourceNode())
                ->setResourceLinkArray($event->getResourceLinkArray())
            ;
            $event->addChild($repeatedEvent);
            $this->entityManager->persist($repeatedEvent);
        }

        $event->getRepeatEvents()->add(
            (new CCalendarEventRepeat())
                ->setEvent($event)
                ->setCalType($type)
                ->setCalEnd($lastDay->getTimestamp())
        );
    }

    private function applyLanguageFromPayload(CCalendarEvent $event, array $payload): void
    {
        if (!\array_key_exists('language', $payload)) {
            return;
        }

        $resourceNode = $event->getResourceNode();
        if (null === $resourceNode) {
            return;
        }

        $resourceNode->setLanguage($this->findLanguage($payload['language']));
    }

    private function findLanguage(mixed $rawLanguage): ?Language
    {
        if (null === $rawLanguage) {
            return null;
        }

        if (\is_array($rawLanguage)) {
            if (isset($rawLanguage['@id'])) {
                $rawLanguage = $rawLanguage['@id'];
            } elseif (isset($rawLanguage['isocode'])) {
                $rawLanguage = $rawLanguage['isocode'];
            } elseif (isset($rawLanguage['id'])) {
                $rawLanguage = $rawLanguage['id'];
            }
        }

        $languageCode = trim((string) $rawLanguage);
        if ('' === $languageCode) {
            return null;
        }

        if (preg_match('#/api/languages/(\d+)$#', $languageCode, $matches) || ctype_digit($languageCode)) {
            $languageId = isset($matches[1]) ? (int) $matches[1] : (int) $languageCode;
            $language = $this->entityManager->getRepository(Language::class)->find($languageId);

            if ($language instanceof Language) {
                return $language;
            }

            throw new BadRequestHttpException('Invalid resource language.');
        }

        if (!preg_match('/^[a-zA-Z0-9_-]{1,8}$/', $languageCode)) {
            throw new BadRequestHttpException('Invalid resource language.');
        }

        $language = $this->entityManager->getRepository(Language::class)->findOneBy([
            'isocode' => $languageCode,
            'available' => true,
        ]);

        if ($language instanceof Language) {
            return $language;
        }

        throw new BadRequestHttpException('Invalid resource language.');
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

    private function applyCareerAndPromotionRules(CCalendarEvent $event): void
    {
        $allowCareerAgenda = 'true' === $this->settingsManager->getSetting(
            'agenda.allow_careers_in_global_agenda',
            true
        );

        $isGlobalEvent = $this->isGlobalEventFromRequest();

        if (!$allowCareerAgenda || !$isGlobalEvent) {
            $event->setCareer(null);
            $event->setPromotion(null);

            return;
        }

        $payload = $this->getRequestPayload();

        $requestedCareerId = $this->extractIdentifier($payload['career'] ?? null);
        $requestedPromotionId = $this->extractIdentifier($payload['promotion'] ?? null);

        $career = null;
        $promotion = null;

        if (null !== $requestedCareerId) {
            $career = $this->entityManager->getRepository(Career::class)->find($requestedCareerId);
            if (null === $career) {
                throw new BadRequestHttpException('Selected career was not found.');
            }
        }

        if (null !== $requestedPromotionId) {
            $promotion = $this->entityManager->getRepository(Promotion::class)->find($requestedPromotionId);
            if (null === $promotion) {
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

        $event->setCareer($career);
        $event->setPromotion($promotion);
    }

    private function isGlobalEventFromRequest(): bool
    {
        $payload = $this->getRequestPayload();
        $isGlobal = $payload['isGlobal'] ?? false;

        if (\is_bool($isGlobal)) {
            return $isGlobal;
        }

        if (\is_int($isGlobal)) {
            return 1 === $isGlobal;
        }

        if (\is_string($isGlobal)) {
            $value = strtolower(trim($isGlobal));

            return 'true' === $value || '1' === $value;
        }

        return false;
    }

    private function getRequestPayload(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return [];
        }

        try {
            $payload = $request->toArray();

            return \is_array($payload) ? $payload : [];
        } catch (Throwable) {
            return [];
        }
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

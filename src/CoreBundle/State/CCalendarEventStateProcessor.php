<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\AgendaReminder;
use Chamilo\CoreBundle\Entity\Career;
use Chamilo\CoreBundle\Entity\Promotion;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
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
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly Security $security,
        private readonly SettingsManager $settingsManager,
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
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

        $data->getReminders()->forAll(function (int $i, AgendaReminder $reminder) {
            return $reminder->decodeDateInterval();
        });

        /** @var CCalendarEvent $result */
        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
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

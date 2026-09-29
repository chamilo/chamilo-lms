<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\Gradebook\GradebookLinkManager;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\State\Gradebook\GradebookLinkResourceResolver;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationAssignment;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * @implements ProcessorInterface<CStudentPublication, CStudentPublication>
 */
final class CStudentPublicationPostStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        private readonly RouterInterface $router,
        private readonly Security $security,
        private readonly SettingsManager $settingsManager,
        private readonly GradebookLinkManager $gradebookLinkManager,
        private readonly RequestStack $requestStack,
    ) {}

    public function process(
        $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): CStudentPublication {
        /** @var CStudentPublication $publication */
        $publication = $data;
        $isUpdate = null !== $publication->getIid();
        $previous = $context['previous_data'] ?? null;
        $originalUser = $previous instanceof CStudentPublication ? $previous->getUser() : null;

        /** @var User|null $currentUser */
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            $currentUser = null;
        }

        // Ensure we always assign a managed User reference BEFORE any persist/flush happens.
        // This prevents Doctrine from treating the User as a new/unknown entity.
        $targetUserId = null;
        if ($isUpdate && $originalUser instanceof User && null !== $originalUser->getId()) {
            $targetUserId = $originalUser->getId();
        } elseif (!$isUpdate && $currentUser instanceof User && null !== $currentUser->getId()) {
            $targetUserId = $currentUser->getId();
        }

        if (null !== $targetUserId) {
            $publication->setUser($this->entityManager->getReference(User::class, $targetUserId));
        }

        $this->entityManager->beginTransaction();

        try {
            // Persist/flush (ApiPlatform default processor). Keep the assignment and
            // its Gradebook relation atomic so an invalid Gradebook context cannot
            // leave a partially updated assignment behind.
            $result = $this->persistProcessor->process($publication, $operation, $uriVariables, $context);

            $assignment = $publication->getAssignment();
            $courseLink = $publication->getFirstResourceLink();
            $course = $courseLink->getCourse();
            $session = $courseLink->getSession();
            $group = $courseLink->getGroup();

            if (!$assignment) {
                $assignment = new CStudentPublicationAssignment();
                $assignment->setPublication($publication);
                $publication->setAssignment($assignment);
                $this->entityManager->persist($assignment);
            }

            $payload = [];
            $request = $this->requestStack->getCurrentRequest();
            if (null !== $request) {
                try {
                    $payload = $request->toArray();
                } catch (Throwable) {
                    // Non-fatal: keep processing without request-only fields.
                    $payload = [];
                }
            }

            $this->applyResourceLanguage($publication, $payload);

            if (\array_key_exists('qualification', $payload)) {
                $publication->setQualification((float) $payload['qualification']);

                // Store who graded (qualificator) and when.
                if ($currentUser instanceof User) {
                    $publication->setQualificatorId($currentUser->getId());
                    $publication->setDateOfQualification(new DateTime());
                }
            }

            if (isset($payload['expiresOn'])) {
                $assignment->setExpiresOn(new DateTime($payload['expiresOn']));
            }
            if (isset($payload['endsOn'])) {
                $assignment->setEndsOn(new DateTime($payload['endsOn']));
            }

            if (!$isUpdate || $publication->getQualification() > 0) {
                $assignment->setEnableQualification(true);
            }

            if ($publication->addToCalendar) {
                $event = $this->saveCalendarEvent($publication, $assignment, $courseLink, $course, $session, $group);
                $assignment->setEventCalendarId($event->getIid());
            } elseif (!$isUpdate) {
                $assignment->setEventCalendarId(0);
            }

            if (null !== $assignment->getIid()) {
                $publication->setHasProperties($assignment->getIid());
            }

            $publication->setViewProperties(true);
            $this->entityManager->flush();

            $this->saveGradebookConfig($publication, $course, $session, $payload);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Throwable $exception) {
            $this->entityManager->rollback();

            throw $exception;
        }

        if (!$isUpdate) {
            $this->sendEmailAlertStudentsOnNewHomework($publication, $course, $session);
        }

        return $result;
    }

    private function applyResourceLanguage(CStudentPublication $publication, array $payload): void
    {
        if (!\array_key_exists('language', $payload)) {
            return;
        }

        $resourceNode = $publication->getResourceNode();
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

    private function saveCalendarEvent(
        CStudentPublication $publication,
        CStudentPublicationAssignment $assignment,
        ResourceLink $courseLink,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): CCalendarEvent {
        $eventTitle = \sprintf(
            $this->translator->trans('Handing over of task %s'),
            $publication->getTitle()
        );

        $publicationUrl = '/main/work/work_list.php?'.http_build_query([
            'cid' => $course->getId(),
            'sid' => $session?->getId(),
            'gid' => $group?->getIid(),
            'id' => $publication->getIid(),
        ]);

        $content = \sprintf(
            '<div><a href="%s">%s</a></div> %s',
            $publicationUrl,
            $publication->getTitle(),
            $publication->getDescription()
        );

        $startDate = new DateTime('now', new DateTimeZone('UTC'));
        $endDate = new DateTime('now', new DateTimeZone('UTC'));

        if ($expiresOn = $assignment->getExpiresOn()) {
            $startDate = clone $expiresOn;
            $endDate = clone $expiresOn;
        }

        $color = CCalendarEvent::COLOR_STUDENT_PUBLICATION;

        if ($agendaColors = $this->settingsManager->getSetting('agenda.agenda_colors')) {
            $color = $agendaColors['student_publication'];
        }

        $creator = $publication->getCreator();
        if ($creator instanceof User && null !== $creator->getId()) {
            $creator = $this->entityManager->getReference(User::class, $creator->getId());
        }

        $event = (new CCalendarEvent())
            ->setTitle($eventTitle)
            ->setContent($content)
            ->setParent($course)
            ->setCreator($creator)
            ->addLink(clone $courseLink)
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setColor($color)
        ;

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return $event;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function saveGradebookConfig(
        CStudentPublication $publication,
        Course $course,
        ?Session $session,
        array $payload,
    ): void {
        if (!\array_key_exists('addToGradebook', $payload)
            && !\array_key_exists('gradebookCategoryId', $payload)
        ) {
            return;
        }

        $publicationId = (int) ($publication->getIid() ?? 0);
        if ($publicationId <= 0) {
            return;
        }

        if (!$publication->addToGradebook) {
            $this->gradebookLinkManager->removeLinks(
                $course,
                $session,
                GradebookLinkResourceResolver::LINK_STUDENT_PUBLICATION,
                $publicationId,
            );

            return;
        }

        if ($publication->gradebookCategoryId <= 0) {
            throw new BadRequestHttpException('A Gradebook category is required.');
        }

        $this->gradebookLinkManager->upsertLink(
            $course,
            $session,
            GradebookLinkResourceResolver::LINK_STUDENT_PUBLICATION,
            $publicationId,
            $publication->gradebookCategoryId,
            max(0.0, (float) $publication->getWeight()),
            true,
            0.0,
        );
    }

    private function sendEmailAlertStudentsOnNewHomework(
        CStudentPublication $publication,
        Course $course,
        ?Session $session
    ): void {
        $sendEmailAlert = api_get_course_setting('email_alert_students_on_new_homework');

        switch ($sendEmailAlert) {
            case 1:
                sendEmailToStudentsOnHomeworkCreation(
                    $publication->getIid(),
                    $course->getId(),
                    $session?->getId()
                );

                // no break
            case 2:
                sendEmailToDrhOnHomeworkCreation(
                    $publication->getIid(),
                    $course->getId(),
                    $session?->getId()
                );

                break;
        }
    }
}

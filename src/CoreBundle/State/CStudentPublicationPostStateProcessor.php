<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationAssignment;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use GradebookUtils;
use Symfony\Bundle\SecurityBundle\Security;
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

        // Persist/flush (ApiPlatform default processor)
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
        if (isset($context['request'])) {
            try {
                $payload = $context['request']->toArray();
            } catch (Throwable $e) {
                // Non-fatal: keep processing without payload.
                $payload = [];
            }
        }

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

        $this->saveGradebookConfig($publication, $course, $session);

        if (!$isUpdate) {
            $this->sendEmailAlertStudentsOnNewHomework($publication, $course, $session);
        }

        return $result;
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

    private function saveGradebookConfig(CStudentPublication $publication, Course $course, ?Session $session): void
    {
        if ($publication->gradebookCategoryId <= 0) {
            return;
        }

        $gradebookLinkInfo = GradebookUtils::isResourceInCourseGradebook(
            $course->getId(),
            LINK_STUDENTPUBLICATION,
            $publication->getIid(),
            $session?->getId()
        );

        $linkId = empty($gradebookLinkInfo) ? null : $gradebookLinkInfo['id'];

        if ($publication->addToGradebook) {
            if (empty($linkId)) {
                GradebookUtils::add_resource_to_course_gradebook(
                    $publication->gradebookCategoryId,
                    $course->getId(),
                    LINK_STUDENTPUBLICATION,
                    $publication->getIid(),
                    $publication->getTitle(),
                    $publication->getWeight(),
                    $publication->getQualification(),
                    $publication->getDescription(),
                    1,
                    $session?->getId()
                );
            } else {
                GradebookUtils::updateResourceFromCourseGradebook(
                    $linkId,
                    $course->getId(),
                    $publication->getWeight()
                );
            }
        } else {
            // Delete everything of the gradebook for this $linkId
            GradebookUtils::remove_resource_from_course_gradebook($linkId);
        }
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

<?php

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
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class CStudentPublicationStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        private readonly RouterInterface $router,
        private readonly Security $security,
        private readonly SettingsManager $settingsManager,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        /** @var CStudentPublication $publication */
        $publication = $data;
        $assignment = $publication->getAssignment();
        $courseLink = $publication->getFirstResourceLink();
        $course = $courseLink->getCourse();
        $session = $courseLink->getSession();
        $group = $courseLink->getGroup();
        /** @var User $currentUser */
        $currentUser = $this->security->getUser();

        if ($publication->addToCalendar) {
            $event = $this->saveCalendarEvent($publication, $assignment, $courseLink, $course, $session, $group);

            $assignment->setEventCalendarId($event->getIid());
        } else {
            $assignment->setEventCalendarId(0);
        }

        $publication
            ->setHasProperties($assignment->getIid())
            ->setViewProperties(true)
            ->setUser($currentUser);

        $this->entityManager->flush();

        $this->saveGradebookConfig($publication, $course, $session);

        // Save extrafields

        $this->sendEmailAlertStudentsOnNewHomework($publication, $course, $session);

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
        $eventTitle = sprintf(
            $this->translator->trans('Handing over of task %s'),
            $publication->getTitle()
        );

        $publicationUrl = $this->router->generate(
            'legacy_main',
            [
                'name' => 'work/work_list.php',
                'cid' => $course->getId(),
                'sid' => $session?->getId(),
                'gid' => $group?->getIid(),
                'id' => $publication->getIid(),
            ]
        );

        $content = sprintf(
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

        $event = (new CCalendarEvent())
            ->setTitle($eventTitle)
            ->setContent($content)
            ->setParent($course)
            ->setCreator($publication->getCreator())
            ->addLink(clone $courseLink)
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setColor($color);

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
            $course->getCode(),
            LINK_STUDENTPUBLICATION,
            $publication->getIid(),
            $session?->getId()
        );

        $linkId = empty($gradebookLinkInfo) ? null : $gradebookLinkInfo['id'];

        if ($publication->addToGradebook) {
            if (empty($linkId)) {
                GradebookUtils::add_resource_to_course_gradebook(
                    $publication->gradebookCategoryId,
                    $course->getCode(),
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
                    $course->getCode(),
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
            //no break
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

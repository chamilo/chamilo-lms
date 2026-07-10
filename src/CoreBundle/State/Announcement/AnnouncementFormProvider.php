<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Announcement;

use AnnouncementManager;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Announcement\AnnouncementForm;
use Chamilo\CoreBundle\Controller\AnnouncementAttachmentController;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CAnnouncementAttachment;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Repository\CAnnouncementRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Tracking;

/**
 * @implements ProviderInterface<AnnouncementForm>
 */
final readonly class AnnouncementFormProvider implements ProviderInterface
{
    use AnnouncementAccessHelperTrait;

    public const CSRF_TOKEN_ID = 'announcement_form';

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CAnnouncementRepository $announcementRepository,
        private AnnouncementRecipientResolver $recipientResolver,
        private AnnouncementScheduleManager $scheduleManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AnnouncementForm
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $this->assertAnnouncementToolEnabled($this->entityManager, $course);

        $session = $this->getSession($request);
        $this->assertSessionBelongsToCourse($session, $course);

        $group = $this->getGroup($request);
        $this->assertGroupBelongsToContext($group, $course, $session);

        if ($this->isStudentView($request) || !$this->canManageAnnouncements(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            $group,
        )) {
            throw new AccessDeniedHttpException('You are not allowed to manage announcements in this context.');
        }

        $announcementId = $request->query->getInt('id');
        $announcement = null;
        if ($announcementId > 0) {
            $announcement = $this->getAnnouncementForEdit($announcementId, $course, $session, $group);
        }

        $formData = $this->recipientResolver->getFormData($course, $session, $group);
        $result = new AnnouncementForm();
        $result->csrfToken = (string) $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID);
        $result->canEdit = true;
        $result->isNew = !$announcement instanceof CAnnouncement;
        $result->groupContext = $group instanceof CGroup;
        $result->classLabel = $this->getClassLabel($session);
        $result->recipientOptions = $formData['options'];
        $result->classes = $formData['classes'];
        $result->languages = $this->getLanguages();
        $result->tags = $this->getTags();
        $result->recipients = ['everyone'];
        $result->sendByEmail = true;
        $result->emailCsrfToken = (string) $this->csrfTokenManager->getToken(AnnouncementEmailProcessor::CSRF_TOKEN_ID);
        $result->sendToSessionsAvailable = !$session instanceof Session;
        $result->sendToHrmAvailable = !$this->isSettingEnabled(
            $this->settingsManager->getSetting('announcement.announcements_hide_send_to_hrm_users', true),
        );
        $result->sendCopyToSelf = true;
        $result->scheduleAvailable = $this->scheduleManager->isAvailable($session);
        $result->scheduleMinimumDate = (new DateTimeImmutable('today'))->format('Y-m-d');
        $result->scheduleDate = (new DateTimeImmutable('tomorrow'))->format('Y-m-d');
        $result->calendarAvailable = true;
        $result->remindersAvailable = $this->areAgendaRemindersEnabled();
        $result->eventStartDate = new DateTime();
        $result->eventEndDate = (new DateTime())->modify('+1 hour');
        $result->attachmentsEnabled = !$this->isSettingEnabled(
            $this->settingsManager->getSetting('announcement.disable_announcement_attachment', true),
        );
        $result->attachmentCsrfToken = $result->attachmentsEnabled
            ? (string) $this->csrfTokenManager->getToken(AnnouncementAttachmentController::CSRF_TOKEN_ID)
            : '';

        if ($announcement instanceof CAnnouncement) {
            $result->id = $announcementId;
            $result->title = $announcement->getTitle();
            $result->content = (string) $announcement->getContent();
            $result->language = (string) ($announcement->getResourceNode()?->getLanguage()?->getIsocode() ?? '');
            $result->recipients = $this->recipientResolver->getSelectedRecipients(
                $announcement,
                $course,
                $session,
                $group,
            );
            $result->attachments = $this->normalizeAttachments($announcement, $course, $session, $group);
            $result->emailAlreadySent = true === $announcement->getEmailSent();
            $result->sendByEmail = !$result->emailAlreadySent;

            if ($result->scheduleAvailable) {
                $schedule = $this->scheduleManager->getValues($announcement);
                $result->scheduleByDate = !$result->emailAlreadySent && $schedule['scheduleByDate'];
                $result->scheduleDate = '' !== $schedule['scheduleDate']
                    ? $schedule['scheduleDate']
                    : $result->scheduleDate;
                $result->sendToUsersInSessions = $schedule['sendToUsersInSessions'];
            }

            $this->registerAnnouncementEventLog('modify', $course, $session, $announcementId, details: 'form');

            return $result;
        }

        $this->applyReminderDefaults($result, $request, $course, $session, $group);
        $this->registerAnnouncementEventLog('add', $course, $session, details: 'form');

        return $result;
    }

    private function getCourse(Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if ($courseId <= 0) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('The requested course was not found.');
        }

        return $course;
    }

    private function getSession(Request $request): ?Session
    {
        $sessionId = $request->query->getInt('sid');
        if ($sessionId <= 0) {
            return null;
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        return $session;
    }

    private function getGroup(Request $request): ?CGroup
    {
        $groupId = $request->query->getInt('gid');
        if ($groupId <= 0) {
            return null;
        }

        $group = $this->entityManager->getRepository(CGroup::class)->find($groupId);
        if (!$group instanceof CGroup) {
            throw new BadRequestHttpException('The requested group was not found.');
        }

        return $group;
    }

    private function getAnnouncementForEdit(
        int $announcementId,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): CAnnouncement {
        $announcement = $this->announcementRepository->find($announcementId);
        if (!$announcement instanceof CAnnouncement) {
            throw new NotFoundHttpException('The requested announcement was not found.');
        }

        if ([] === $this->recipientResolver->getScopedLinks($announcement, $course, $session, $group)) {
            throw new AccessDeniedHttpException('The requested announcement does not belong to the current course context.');
        }

        if ($group instanceof CGroup && $this->recipientResolver->hasMultipleGroupTargets(
            $announcement,
            $course,
            $session,
        )) {
            throw new AccessDeniedHttpException('This announcement targets several groups and cannot be edited from one group.');
        }

        if (!$this->canEditAnnouncement(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $announcement,
            $course,
            $session,
            $group,
        )) {
            throw new AccessDeniedHttpException('You are not allowed to edit this announcement.');
        }

        return $announcement;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeAttachments(
        CAnnouncement $announcement,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): array {
        $query = ['cid' => (int) $course->getId()];
        if ($session instanceof Session && null !== $session->getId()) {
            $query['sid'] = (int) $session->getId();
        }
        if ($group instanceof CGroup && null !== $group->getIid()) {
            $query['gid'] = (int) $group->getIid();
        }

        $attachments = [];
        foreach ($announcement->getAttachments() as $attachment) {
            if (!$attachment instanceof CAnnouncementAttachment || null === $attachment->getIid()) {
                continue;
            }

            $attachments[] = [
                'id' => (int) $attachment->getIid(),
                'filename' => $attachment->getFilename(),
                'comment' => (string) $attachment->getComment(),
                'size' => (int) $attachment->getSize(),
                'downloadUrl' => \sprintf(
                    '/api/announcement/%d/attachment/%d/download?%s',
                    (int) $announcement->getIid(),
                    (int) $attachment->getIid(),
                    http_build_query($query),
                ),
            ];
        }

        return $attachments;
    }

    private function getClassLabel(?Session $session): string
    {
        if ($session instanceof Session) {
            return $this->translate('Classes of session').' '.$session->getTitle();
        }

        return $this->translate('Classes of course');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function getLanguages(): array
    {
        $languages = [
            [
                'value' => '',
                'label' => $this->translate('No specific language'),
            ],
        ];

        $availableLanguages = $this->entityManager
            ->getRepository(Language::class)
            ->findBy(['available' => true], ['englishName' => 'ASC'])
        ;

        foreach ($availableLanguages as $language) {
            if (!$language instanceof Language) {
                continue;
            }

            $label = $language->getOriginalName() ?: $language->getEnglishName();
            $languages[] = [
                'value' => $language->getIsocode(),
                'label' => $label ?: $language->getIsocode(),
            ];
        }

        return $languages;
    }

    /**
     * @return array<int, string>
     */
    private function getTags(): array
    {
        if (!class_exists(AnnouncementManager::class)) {
            return [];
        }

        $tags = AnnouncementManager::getTags();
        if (!\is_array($tags)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $tags)));
    }

    private function applyReminderDefaults(
        AnnouncementForm $form,
        Request $request,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): void {
        $recipientValues = [];
        $since = 7;
        $never = false;

        $inactiveUserId = $request->query->getInt('remind_inactive');
        if ($inactiveUserId > 0) {
            $recipientValues[] = 'USER:'.$inactiveUserId;
        } elseif ($request->query->getBoolean('remindallinactives') && class_exists(Tracking::class)) {
            $sinceValue = trim((string) $request->query->get('since', '6'));
            if ('never' === $sinceValue) {
                $never = true;
                $since = 0;
                $trackingSince = 'never';
            } else {
                $since = max(0, (int) $sinceValue);
                $trackingSince = $since;
            }

            $inactiveUsers = Tracking::getInactiveStudentsInCourse(
                (int) $course->getId(),
                $trackingSince,
                null !== $session ? (int) $session->getId() : 0,
            );

            if (\is_array($inactiveUsers)) {
                foreach ($inactiveUsers as $inactiveUser) {
                    $inactiveId = (int) $inactiveUser;
                    if ($inactiveId > 0) {
                        $recipientValues[] = 'USER:'.$inactiveId;
                    }
                }
            }
        }

        if ([] === $recipientValues) {
            return;
        }

        $form->recipients = $this->recipientResolver->normalizeSelection(
            $recipientValues,
            $course,
            $session,
            $group,
        );
        $form->sendByEmail = true;

        $siteName = $course->getTitle();
        if (\function_exists('api_get_setting')) {
            $configuredSiteName = trim((string) api_get_setting('siteName'));
            if ('' !== $configuredSiteName) {
                $siteName = $configuredSiteName;
            }
        }

        $form->title = \sprintf($this->translate('Inactivity on %s'), $siteName);
        if ($never) {
            $form->content = $this->translate('YourAccountIsActiveYouCanLoginAndCheckYourCourses');

            return;
        }

        $form->content = \sprintf(
            $this->translate('Dear user,<br /><br /> you are not active on %s since more than %s days.'),
            $siteName,
            $since,
        );
    }

    private function areAgendaRemindersEnabled(): bool
    {
        if (!\function_exists('api_get_configuration_value')) {
            return false;
        }

        return true === api_get_configuration_value('agenda_reminders');
    }

    private function translate(string $message): string
    {
        if (\function_exists('get_lang')) {
            return (string) get_lang($message);
        }

        return $message;
    }
}

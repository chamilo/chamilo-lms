<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Announcement;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Announcement\AnnouncementEmailAction;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\StudentViewHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Repository\CAnnouncementRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<AnnouncementEmailAction, AnnouncementEmailAction>
 */
final readonly class AnnouncementEmailProcessor implements ProcessorInterface
{
    use AnnouncementAccessHelperTrait;

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CAnnouncementRepository $announcementRepository,
        private AnnouncementRecipientResolver $recipientResolver,
        private AnnouncementEmailRecipientResolver $emailRecipientResolver,
        private AnnouncementEmailSender $emailSender,
        private AnnouncementScheduleManager $scheduleManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private StudentViewHelper $studentViewHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AnnouncementEmailAction
    {
        if (!$data instanceof AnnouncementEmailAction) {
            throw new BadRequestHttpException('The request payload is invalid.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $this->assertAnnouncementToolEnabled($this->entityManager, $course);

        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $this->assertSessionBelongsToCourse($session, $course);

        $group = $this->cidReqHelper->getDoctrineGroupEntity();
        $this->assertGroupBelongsToContext($group, $course, $session);

        if ($this->studentViewHelper->isActive() || !$this->canManageAnnouncements(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            $group,
        )) {
            throw new AccessDeniedHttpException('You are not allowed to manage announcements in this context.');
        }

        if (!$data->sendByEmail && !$data->sendCopyToSelf) {
            throw new BadRequestHttpException('At least one email delivery option is required.');
        }

        if (!$data->sendByEmail && ($data->sendToUsersInSessions || $data->sendToHrmUsers)) {
            throw new BadRequestHttpException('Additional recipients require email delivery to be enabled.');
        }

        if ($data->sendToUsersInSessions && $session instanceof Session) {
            throw new BadRequestHttpException('Users from all sessions can only be selected from the base course.');
        }

        if ($data->sendToHrmUsers && $this->isSettingEnabled(
            $this->settingsManager->getSetting('announcement.announcements_hide_send_to_hrm_users', true),
        )) {
            throw new AccessDeniedHttpException('Sending copies to HR managers is disabled.');
        }

        $announcement = $this->getEditableAnnouncement(
            (int) ($uriVariables['id'] ?? 0),
            $course,
            $session,
            $group,
        );

        if ($data->sendByEmail && true === $announcement->getEmailSent()) {
            throw new BadRequestHttpException('This announcement has already been sent by email.');
        }

        if ($data->sendByEmail
            && $this->scheduleManager->isAvailable($session)
            && $this->scheduleManager->getValues($announcement)['scheduleByDate']
        ) {
            throw new BadRequestHttpException('This announcement is scheduled and cannot be sent before its delivery date.');
        }

        $sender = $this->security->getUser();
        if (!$sender instanceof User || null === $sender->getId()) {
            throw new AccessDeniedHttpException('An authenticated user is required.');
        }

        $selection = $this->recipientResolver->getSelectedRecipients($announcement, $course, $session, $group);
        $primaryRecipients = $data->sendByEmail
            ? $this->emailRecipientResolver->resolvePrimaryRecipients(
                $selection,
                $course,
                $session,
                $group,
                $data->sendToUsersInSessions,
            )
            : [];
        $hrmCopies = $data->sendByEmail && $data->sendToHrmUsers
            ? $this->emailRecipientResolver->resolveHrmCopies($primaryRecipients)
            : [];

        if ($data->sendByEmail && [] === $primaryRecipients && [] === $hrmCopies) {
            throw new BadRequestHttpException('No valid email recipients were found.');
        }

        try {
            $delivery = $this->emailSender->send(
                $announcement,
                $course,
                $session,
                $group,
                $sender,
                $primaryRecipients,
                $hrmCopies,
                $data->sendByEmail,
                $data->sendCopyToSelf,
            );
        } catch (RuntimeException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }

        $primarySentCount = $delivery['primarySentCount'];
        if ($data->sendByEmail && $primarySentCount > 0) {
            $announcement->setEmailSent(true);
            $this->entityManager->persist($announcement);
            $this->entityManager->flush();
        }

        $failedRecipients = $delivery['failedRecipients'];
        $failedCount = \count($failedRecipients);
        $sentCount = $primarySentCount + ($delivery['copyWasAdditional'] ? 1 : 0);
        $result = new AnnouncementEmailAction();
        $result->id = (int) $announcement->getIid();
        $result->emailSent = true === $announcement->getEmailSent();
        $result->copySent = $delivery['copySent'];
        $result->sentCount = $sentCount;
        $result->failedCount = $failedCount;
        $result->internalMessageCount = $delivery['internalMessageCount'];
        $result->internalMessageCreatedCount = $delivery['internalMessageCreatedCount'];
        $result->internalMessageFailedCount = $delivery['internalMessageFailedCount'];
        $result->failedRecipients = $failedRecipients;
        $result->partial = $failedCount > 0 && ($sentCount > 0 || $result->internalMessageCount > 0);
        $result->success = 0 === $failedCount && $sentCount > 0;
        $result->message = $this->buildResultMessage($result);

        $status = $result->success ? 'success' : ($result->partial ? 'partial' : 'failed');
        $this->registerAnnouncementEventLog(
            'send_email',
            $course,
            $session,
            (int) $announcement->getIid(),
            details: $status,
            info: \sprintf(
                'sent=%d;failed=%d;internal=%d;internal_created=%d',
                $result->sentCount,
                $result->failedCount,
                $result->internalMessageCount,
                $result->internalMessageCreatedCount,
            ),
        );

        return $result;
    }

    private function getEditableAnnouncement(
        int $announcementId,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): CAnnouncement {
        if ($announcementId <= 0) {
            throw new BadRequestHttpException('A valid announcement id is required.');
        }

        $announcement = $this->announcementRepository->find($announcementId);
        if (!$announcement instanceof CAnnouncement) {
            throw new NotFoundHttpException('The requested announcement was not found.');
        }

        if ([] === $this->recipientResolver->getScopedLinks($announcement, $course, $session, $group)) {
            throw new AccessDeniedHttpException('The requested announcement does not belong to the current course context.');
        }

        if ($group instanceof CGroup && $this->hasMultipleAnnouncementGroupTargets(
            $announcement,
            $course,
            $session,
        )) {
            throw new AccessDeniedHttpException('This announcement targets several groups and cannot be emailed from one group.');
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
            throw new AccessDeniedHttpException('You are not allowed to email this announcement.');
        }

        return $announcement;
    }

    private function buildResultMessage(AnnouncementEmailAction $result): string
    {
        if ($result->success) {
            return \sprintf(
                'The announcement was saved, %d internal message(s) are available, and email delivery completed successfully.',
                $result->internalMessageCount,
            );
        }

        if ($result->partial && $result->sentCount > 0) {
            return \sprintf(
                'The announcement was saved, %d internal message(s) are available, and email was sent to %d recipient(s), but %d delivery attempt(s) failed.',
                $result->internalMessageCount,
                $result->sentCount,
                $result->failedCount,
            );
        }

        if ($result->internalMessageCount > 0) {
            return \sprintf(
                'The announcement was saved and %d internal message(s) are available, but no external email could be delivered.',
                $result->internalMessageCount,
            );
        }

        return 'The announcement was saved, but neither internal messages nor external email could be delivered.';
    }
}

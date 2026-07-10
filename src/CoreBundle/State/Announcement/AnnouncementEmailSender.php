<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Announcement;

use AnnouncementManager;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\MailHelper;
use Chamilo\CoreBundle\Helpers\MessageHelper;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CAnnouncementAttachment;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Repository\CAnnouncementAttachmentRepository;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Throwable;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const FILTER_VALIDATE_EMAIL;
use const PHP_SAPI;

final readonly class AnnouncementEmailSender
{
    public function __construct(
        private MailHelper $mailHelper,
        private MessageHelper $messageHelper,
        private MessageRepository $messageRepository,
        private CAnnouncementAttachmentRepository $attachmentRepository,
        private RouterInterface $router,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param array<int, User>                                              $primaryRecipients
     * @param array<int, array{user: User, relatedUsers: array<int, User>}> $hrmCopies
     *
     * @return array{
     *     primarySentCount: int,
     *     copySent: bool,
     *     copyWasAdditional: bool,
     *     failedRecipients: array<int, string>,
     *     internalMessageCount: int,
     *     internalMessageCreatedCount: int,
     *     internalMessageFailedCount: int
     * }
     */
    public function send(
        CAnnouncement $announcement,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        User $sender,
        array $primaryRecipients,
        array $hrmCopies,
        bool $sendPrimary,
        bool $sendCopyToSelf,
        bool $storeInternalMessages = true,
    ): array {
        $attachments = $this->buildAttachments($announcement);
        $subject = $course->getTitle().' - '.$announcement->getTitle();
        $replyTo = $this->buildReplyTo($sender);
        $successfulPrimaryIds = [];
        $intendedPrimaryIds = [];
        $failedRecipients = [];
        $internalMessageKeys = [];
        $internalMessageCreatedCount = 0;
        $internalMessageFailedCount = 0;

        if ($sendPrimary) {
            foreach ($primaryRecipients as $recipient) {
                if (!$recipient instanceof User || null === $recipient->getId()) {
                    continue;
                }

                $recipientId = (int) $recipient->getId();
                $intendedPrimaryIds[$recipientId] = true;
                $message = $this->buildMessage($announcement, $course, $session, $group, $sender, $recipient);
                $delivery = $this->deliverMessage(
                    $announcement,
                    $recipient,
                    $sender,
                    $subject,
                    $message,
                    $replyTo,
                    $attachments,
                    'primary',
                    $storeInternalMessages,
                );

                if ($storeInternalMessages) {
                    if ($delivery['internalMessageAvailable']) {
                        $internalMessageKeys['primary-'.$recipientId] = true;
                    } else {
                        $internalMessageFailedCount++;
                    }

                    if ($delivery['internalMessageCreated']) {
                        $internalMessageCreatedCount++;
                    }
                }

                if ($delivery['emailSent']) {
                    $successfulPrimaryIds[$recipientId] = true;

                    continue;
                }

                $failedRecipients[] = $this->formatRecipient($recipient);
            }

            foreach ($hrmCopies as $hrmId => $copy) {
                $hrmUser = $copy['user'];
                if (isset($intendedPrimaryIds[$hrmId])) {
                    continue;
                }

                $message = $this->buildHrmMessage(
                    $announcement,
                    $course,
                    $session,
                    $group,
                    $sender,
                    $copy['relatedUsers'],
                );
                $delivery = $this->deliverMessage(
                    $announcement,
                    $hrmUser,
                    $sender,
                    $subject,
                    $message,
                    $replyTo,
                    $attachments,
                    'hrm',
                    $storeInternalMessages,
                );

                if ($storeInternalMessages) {
                    if ($delivery['internalMessageAvailable']) {
                        $internalMessageKeys['hrm-'.$hrmId] = true;
                    } else {
                        $internalMessageFailedCount++;
                    }

                    if ($delivery['internalMessageCreated']) {
                        $internalMessageCreatedCount++;
                    }
                }

                if ($delivery['emailSent']) {
                    $successfulPrimaryIds[$hrmId] = true;

                    continue;
                }

                $failedRecipients[] = $this->formatRecipient($hrmUser);
            }
        }

        $copySent = false;
        $copyWasAdditional = false;
        $senderId = null !== $sender->getId() ? (int) $sender->getId() : 0;

        if ($sendCopyToSelf && $senderId > 0) {
            if (isset($intendedPrimaryIds[$senderId])) {
                $copySent = isset($successfulPrimaryIds[$senderId]);
            } else {
                $message = $this->buildMessage($announcement, $course, $session, $group, $sender, $sender);
                $delivery = $this->deliverMessage(
                    $announcement,
                    $sender,
                    $sender,
                    $subject,
                    $message,
                    $replyTo,
                    $attachments,
                    'self',
                    $storeInternalMessages,
                );
                $copySent = $delivery['emailSent'];
                $copyWasAdditional = $copySent;

                if ($storeInternalMessages) {
                    if ($delivery['internalMessageAvailable']) {
                        $internalMessageKeys['self-'.$senderId] = true;
                    } else {
                        $internalMessageFailedCount++;
                    }

                    if ($delivery['internalMessageCreated']) {
                        $internalMessageCreatedCount++;
                    }
                }

                if (!$copySent) {
                    $failedRecipients[] = $this->formatRecipient($sender);
                }
            }
        }

        $this->logger->info('Announcement email delivery completed.', [
            'announcement_id' => $announcement->getIid(),
            'course_id' => $course->getId(),
            'session_id' => $session?->getId(),
            'group_id' => $group?->getIid(),
            'primary_sent_count' => \count($successfulPrimaryIds),
            'copy_sent' => $copySent,
            'failed_count' => \count($failedRecipients),
            'internal_message_count' => \count($internalMessageKeys),
            'internal_message_created_count' => $internalMessageCreatedCount,
            'internal_message_failed_count' => $internalMessageFailedCount,
            'store_internal_messages' => $storeInternalMessages,
        ]);

        return [
            'primarySentCount' => \count($successfulPrimaryIds),
            'copySent' => $copySent,
            'copyWasAdditional' => $copyWasAdditional,
            'failedRecipients' => array_values(array_unique($failedRecipients)),
            'internalMessageCount' => \count($internalMessageKeys),
            'internalMessageCreatedCount' => $internalMessageCreatedCount,
            'internalMessageFailedCount' => $internalMessageFailedCount,
        ];
    }

    /**
     * Optionally store the Chamilo internal message before attempting SMTP delivery.
     *
     * @param array<string, array{mail: string, name: string}>    $replyTo
     * @param array<int, array{stream: string, filename: string}> $attachments
     *
     * @return array{emailSent: bool, internalMessageAvailable: bool, internalMessageCreated: bool}
     */
    private function deliverMessage(
        CAnnouncement $announcement,
        User $recipient,
        User $sender,
        string $subject,
        string $message,
        array $replyTo,
        array $attachments,
        string $deliveryType,
        bool $storeInternalMessages,
    ): array {
        $internalMessage = ['available' => false, 'created' => false];
        if ($storeInternalMessages) {
            $internalMessage = $this->storeInternalMessage(
                $announcement,
                $recipient,
                $sender,
                $subject,
                $message,
                $deliveryType,
            );
        }

        try {
            $emailSent = $this->mailHelper->send(
                $this->getRecipientName($recipient),
                $recipient->getEmail(),
                $subject,
                $message,
                null,
                null,
                $replyTo,
                $attachments,
            );
        } catch (Throwable $throwable) {
            $emailSent = false;
            $this->logger->warning('Announcement external email delivery failed before the mail helper could return.', [
                'announcement_id' => $announcement->getIid(),
                'recipient_id' => $recipient->getId(),
                'delivery_type' => $deliveryType,
                'exception' => $throwable,
            ]);
        }

        return [
            'emailSent' => $emailSent,
            'internalMessageAvailable' => $internalMessage['available'],
            'internalMessageCreated' => $internalMessage['created'],
        ];
    }

    /**
     * @return array{available: bool, created: bool}
     */
    private function storeInternalMessage(
        CAnnouncement $announcement,
        User $recipient,
        User $sender,
        string $subject,
        string $message,
        string $deliveryType,
    ): array {
        if (null === $recipient->getId() || null === $sender->getId() || null === $announcement->getIid()) {
            return ['available' => false, 'created' => false];
        }

        $marker = \sprintf(
            'chamilo-announcement-email announcement=%d recipient=%d delivery=%s',
            (int) $announcement->getIid(),
            (int) $recipient->getId(),
            $deliveryType,
        );
        $internalMessage = $message."\n<!-- ".$marker.' -->';
        $existingMessage = $this->messageRepository->createQueryBuilder('message')
            ->andWhere('message.sender = :sender')
            ->andWhere('message.content LIKE :marker')
            ->setParameter('sender', $sender)
            ->setParameter('marker', '%'.$marker.'%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (null !== $existingMessage) {
            return ['available' => true, 'created' => false];
        }

        try {
            $messageId = $this->messageHelper->sendMessageSimple(
                (int) $recipient->getId(),
                $subject,
                $internalMessage,
                (int) $sender->getId(),
                false,
                false,
            );

            if (null !== $messageId && $messageId > 0) {
                return ['available' => true, 'created' => true];
            }
        } catch (Throwable $throwable) {
            $this->logger->warning('Announcement internal message could not be stored.', [
                'announcement_id' => $announcement->getIid(),
                'recipient_id' => $recipient->getId(),
                'sender_id' => $sender->getId(),
                'delivery_type' => $deliveryType,
                'exception' => $throwable,
            ]);
        }

        return ['available' => false, 'created' => false];
    }

    /**
     * @return array<int, array{stream: string, filename: string}>
     */
    private function buildAttachments(CAnnouncement $announcement): array
    {
        $attachments = [];

        foreach ($announcement->getAttachments() as $attachment) {
            if (!$attachment instanceof CAnnouncementAttachment) {
                continue;
            }

            try {
                $content = $this->attachmentRepository->getResourceFileContent($attachment);
            } catch (Throwable $throwable) {
                $this->logger->error('Announcement attachment could not be read for email delivery.', [
                    'announcement_id' => $announcement->getIid(),
                    'attachment_id' => $attachment->getIid(),
                    'exception' => $throwable,
                ]);

                throw new RuntimeException('An announcement attachment could not be prepared for email delivery.', 0, $throwable);
            }

            $attachments[] = [
                'stream' => $content,
                'filename' => $attachment->getFilename(),
            ];
        }

        return $attachments;
    }

    /**
     * @return array<string, array{mail: string, name: string}>
     */
    private function buildReplyTo(User $sender): array
    {
        if (!filter_var($sender->getEmail(), FILTER_VALIDATE_EMAIL)) {
            return [];
        }

        return [
            'reply_to' => [
                'mail' => $sender->getEmail(),
                'name' => $this->getRecipientName($sender),
            ],
        ];
    }

    private function buildMessage(
        CAnnouncement $announcement,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        User $sender,
        User $recipient,
    ): string {
        $content = $this->buildPersonalizedContent($announcement, $course, $session, $group, $recipient);

        return '<div>'.$content.'</div>'
            .$this->buildAttachmentLinks($announcement, $course, $session, $group)
            .$this->buildFooter($course, $sender);
    }

    /**
     * @param array<int, User> $relatedUsers
     */
    private function buildHrmMessage(
        CAnnouncement $announcement,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        User $sender,
        array $relatedUsers,
    ): string {
        $sections = [];
        $copyLabel = \function_exists('get_lang')
            ? (string) get_lang('Copy of message sent to %s')
            : 'Copy of message sent to %s';

        foreach ($relatedUsers as $relatedUser) {
            $recipientName = htmlspecialchars(
                $this->getRecipientName($relatedUser),
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8',
            );
            $content = $this->buildPersonalizedContent(
                $announcement,
                $course,
                $session,
                $group,
                $relatedUser,
            );
            $sections[] = '<div><strong>'.\sprintf($copyLabel, $recipientName).'</strong><br>'.$content.'</div>';
        }

        return implode('<hr>', $sections)
            .$this->buildAttachmentLinks($announcement, $course, $session, $group)
            .$this->buildFooter($course, $sender);
    }

    private function buildAttachmentLinks(
        CAnnouncement $announcement,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): string {
        $links = [];

        foreach ($announcement->getAttachments() as $attachment) {
            if (!$attachment instanceof CAnnouncementAttachment || null === $attachment->getIid()) {
                continue;
            }

            $parameters = [
                'announcementId' => (int) $announcement->getIid(),
                'attachmentId' => (int) $attachment->getIid(),
                'cid' => (int) $course->getId(),
            ];
            if (null !== $session && null !== $session->getId()) {
                $parameters['sid'] = (int) $session->getId();
            }
            if (null !== $group && null !== $group->getIid()) {
                $parameters['gid'] = (int) $group->getIid();
            }

            $url = $this->router->generate(
                'announcement_attachment_download',
                $parameters,
                UrlGeneratorInterface::ABSOLUTE_URL,
            );
            $links[] = '<li><a href="'.htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'">'
                .htmlspecialchars($attachment->getFilename(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                .'</a></li>';
        }

        return [] !== $links ? '<div><ul>'.implode('', $links).'</ul></div>' : '';
    }

    private function buildPersonalizedContent(
        CAnnouncement $announcement,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        User $recipient,
    ): string {
        $content = (string) $announcement->getContent();

        if ('cli' !== PHP_SAPI && class_exists(AnnouncementManager::class) && null !== $recipient->getId()) {
            try {
                return (string) AnnouncementManager::parseContent(
                    (int) $recipient->getId(),
                    $content,
                    $course->getCode(),
                    null !== $session ? (int) $session->getId() : 0,
                );
            } catch (Throwable $throwable) {
                $this->logger->warning('Legacy announcement tag parsing failed; using the entity-based fallback.', [
                    'announcement_id' => $announcement->getIid(),
                    'recipient_id' => $recipient->getId(),
                    'exception' => $throwable,
                ]);
            }
        }

        return $this->replaceCoreTags($content, $course, $session, $group, $recipient);
    }

    private function buildFooter(Course $course, User $sender): string
    {
        $senderName = htmlspecialchars($this->getRecipientName($sender), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $courseTitle = htmlspecialchars($course->getTitle(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<hr><div>'.$senderName.'<br>'.$courseTitle.'</div>';
    }

    private function replaceCoreTags(
        string $content,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        User $recipient,
    ): string {
        $courseParams = ['cid' => (int) $course->getId()];
        if (null !== $session && null !== $session->getId()) {
            $courseParams['sid'] = (int) $session->getId();
        }
        if (null !== $group && null !== $group->getIid()) {
            $courseParams['gid'] = (int) $group->getIid();
        }

        $courseUrl = $this->router->generate(
            'chamilo_core_course_home',
            $courseParams,
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $teachers = [];
        foreach ($course->getTeachersSubscriptions() as $subscription) {
            $teachers[] = $subscription->getUser()->getFullName();
        }

        $coaches = [];
        $generalCoachNames = [];
        $generalCoachEmails = [];
        if (null !== $session) {
            foreach ($session->getCoachesSubscriptions() as $subscription) {
                $coaches[] = $subscription->getUser()->getFullName();
            }

            foreach ($session->getGeneralCoaches() as $generalCoach) {
                $generalCoachNames[] = $generalCoach->getFullName();
                $generalCoachEmails[] = $generalCoach->getEmail();
            }
        }

        $escape = static fn (?string $value): string => htmlspecialchars(
            (string) $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
        );

        return str_replace(
            [
                '((user_name))',
                '((user_email))',
                '((user_firstname))',
                '((user_lastname))',
                '((user_official_code))',
                '((course_title))',
                '((course_link))',
                '((teachers))',
                '((coaches))',
                '((general_coach))',
                '((general_coach_email))',
            ],
            [
                $escape($recipient->getUsername()),
                $escape($recipient->getEmail()),
                $escape($recipient->getFirstname()),
                $escape($recipient->getLastname()),
                $escape($recipient->getOfficialCode()),
                $escape($course->getTitle()),
                '<a href="'.$escape($courseUrl).'">'.$escape($courseUrl).'</a>',
                $escape(implode(', ', $teachers)),
                $escape(implode(', ', $coaches)),
                $escape(implode(', ', $generalCoachNames)),
                $escape(implode(', ', $generalCoachEmails)),
            ],
            $content,
        );
    }

    private function getRecipientName(User $user): string
    {
        $fullName = trim($user->getFullName());

        return '' !== $fullName ? $fullName : $user->getUsername();
    }

    private function formatRecipient(User $user): string
    {
        $name = $this->getRecipientName($user);
        $email = trim($user->getEmail());

        return '' !== $email ? $name.' ('.$email.')' : $name;
    }
}

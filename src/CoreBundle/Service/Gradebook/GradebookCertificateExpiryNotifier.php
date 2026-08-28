<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\GradebookCertificateExpiryNotification;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Enums\CertificateExpiryNotificationType;
use Chamilo\CoreBundle\Helpers\MessageHelper;
use Chamilo\CoreBundle\Repository\GradebookCertificateExpiryNotificationRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The single unit that decides whether a certificate is due a reminder, sends
 * it (email + in-app message), and records that it was sent. Both the
 * teacher-triggered `notify_expiry` API action and the
 * app:send-certificate-expiry-reminders console command call only this, so
 * the two paths cannot drift.
 */
final readonly class GradebookCertificateExpiryNotifier
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GradebookCertificateExpiryNotificationRepository $notificationRepository,
        private GradebookCertificateExpiryMailer $mailer,
        private MessageHelper $messageHelper,
        private UserRepository $userRepository,
        private LoggerInterface $logger,
    ) {}

    /**
     * @return array{sent: bool, reason: string, type: ?CertificateExpiryNotificationType}
     */
    public function notify(
        GradebookCertificate $certificate,
        string $certificateUrl,
        bool $resend,
        ?User $triggeredBy,
    ): array {
        $expiryDate = $certificate->getExpiryDate();
        if (null === $expiryDate) {
            return ['sent' => false, 'reason' => 'no_expiry_date', 'type' => null];
        }

        $category = $certificate->getCategory();
        $course = $category?->getCourse();
        if (!$category instanceof GradebookCategory || null === $course) {
            return ['sent' => false, 'reason' => 'no_course_context', 'type' => null];
        }

        $today = new DateTime('today', new DateTimeZone('UTC'));
        $type = $expiryDate < $today
            ? CertificateExpiryNotificationType::EXPIRED
            : CertificateExpiryNotificationType::ABOUT_TO_EXPIRE;

        if (!$resend && $this->notificationRepository->hasBeenNotified((int) $certificate->getId(), $type, $expiryDate)) {
            return ['sent' => false, 'reason' => 'already_notified', 'type' => $type];
        }

        $learner = $certificate->getUser();
        if (!api_valid_email((string) $learner->getEmail())) {
            return ['sent' => false, 'reason' => 'invalid_email', 'type' => $type];
        }

        $subject = $this->mailer->buildSubject($type);
        $body = $this->mailer->renderBody($learner, $course, $category, $expiryDate, $type, $certificateUrl);

        try {
            $this->mailer->send($learner, $subject, $body);
        } catch (Throwable $exception) {
            $this->logger->error('Unable to send a certificate expiry reminder email.', [
                'certificateId' => (int) $certificate->getId(),
                'userId' => (int) $learner->getId(),
                'exception' => $exception,
            ]);

            return ['sent' => false, 'reason' => 'mail_failed', 'type' => $type];
        }

        try {
            $this->messageHelper->sendMessageSimple(
                (int) $learner->getId(),
                $subject,
                $body,
                $this->resolveMessageSenderId($triggeredBy),
                false,
                false,
            );
        } catch (Throwable $exception) {
            // Best-effort: the email is what matters; a failed in-app copy shouldn't block the reminder.
            $this->logger->warning('Certificate expiry reminder email sent, but the in-app message failed.', [
                'certificateId' => (int) $certificate->getId(),
                'userId' => (int) $learner->getId(),
                'exception' => $exception,
            ]);
        }

        $notification = new GradebookCertificateExpiryNotification();
        $notification
            ->setCertificate($certificate)
            ->setNotificationType($type)
            ->setExpiryDateAtSend(clone $expiryDate)
            ->setSentAt(new DateTime())
            ->setSentBy($triggeredBy)
        ;
        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return ['sent' => true, 'reason' => '', 'type' => $type];
    }

    private function resolveMessageSenderId(?User $triggeredBy): int
    {
        if (null !== $triggeredBy && null !== $triggeredBy->getId()) {
            return (int) $triggeredBy->getId();
        }

        return (int) ($this->userRepository->findOnePlatformAdmin()?->getId() ?? 1);
    }
}

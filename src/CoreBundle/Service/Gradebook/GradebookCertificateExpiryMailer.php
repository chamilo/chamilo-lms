<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Enums\CertificateExpiryNotificationType;
use Chamilo\CoreBundle\Helpers\MailHelper;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Renders and sends certificate-expiry reminder emails. Kept separate from
 * GradebookCertificateExpiryNotifier (which owns dedup + persistence + the
 * in-app message) so both the teacher-triggered API action and the cron
 * command share the exact same rendering and send logic — mirrors the
 * dedicated-mailer-service pattern already used by CourseInvitationMailer.
 */
final readonly class GradebookCertificateExpiryMailer
{
    public function __construct(
        private Environment $twig,
        private MailerInterface $mailer,
        private MailHelper $mailHelper,
        private TranslatorInterface $translator,
    ) {}

    public function buildSubject(CertificateExpiryNotificationType $type): string
    {
        return CertificateExpiryNotificationType::EXPIRED === $type
            ? $this->translator->trans('Your certificate has expired')
            : $this->translator->trans('Your certificate is about to expire');
    }

    public function renderBody(
        User $learner,
        Course $course,
        GradebookCategory $category,
        DateTimeInterface $expiryDate,
        CertificateExpiryNotificationType $type,
        string $certificateUrl,
    ): string {
        $today = new DateTime('today', new DateTimeZone('UTC'));
        $daysUntilExpiry = (int) $today->diff($expiryDate)->format('%r%a');

        $template = CertificateExpiryNotificationType::EXPIRED === $type
            ? '@ChamiloCore/Mailer/GradebookCertificate/expiry_expired.html.twig'
            : '@ChamiloCore/Mailer/GradebookCertificate/expiry_about_to_expire.html.twig';

        return $this->twig->render($template, [
            'full_name' => $learner->getFullName(),
            'course_title' => (string) $course->getTitle(),
            'category_title' => $category->getTitle(),
            'expiry_date' => $expiryDate->format('Y-m-d'),
            'days_until_expiry' => $daysUntilExpiry,
            'certificate_url' => $certificateUrl,
        ]);
    }

    public function send(User $learner, string $subject, string $body): void
    {
        $email = (new Email())
            ->from($this->mailHelper->getPlatformFromAddress())
            ->to(new Address((string) $learner->getEmail(), $learner->getFullName()))
            ->subject($subject)
            ->html($body)
        ;

        $this->mailer->send($email);
    }
}

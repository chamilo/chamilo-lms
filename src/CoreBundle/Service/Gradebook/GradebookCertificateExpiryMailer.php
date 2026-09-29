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

    /**
     * $locale should be the recipient's own User::getLocale(), not the current
     * session's — like any other internal notification, this is addressed to the
     * learner and must read in their language regardless of who triggered the send.
     */
    public function buildSubject(CertificateExpiryNotificationType $type, string $locale): string
    {
        return CertificateExpiryNotificationType::EXPIRED === $type
            ? $this->translator->trans('Your certificate has expired', [], null, $locale)
            : $this->translator->trans('Your certificate is about to expire', [], null, $locale);
    }

    /**
     * @see buildSubject() for $locale
     */
    public function renderBody(
        User $learner,
        Course $course,
        GradebookCategory $category,
        DateTimeInterface $expiryDate,
        CertificateExpiryNotificationType $type,
        string $certificateUrl,
        string $locale,
    ): string {
        $today = new DateTime('today', new DateTimeZone('UTC'));
        $daysUntilExpiry = (int) $today->diff($expiryDate)->format('%r%a');

        return $this->twig->render($this->templateFor($type), [
            'full_name' => $learner->getFullName(),
            'course_title' => (string) $course->getTitle(),
            'category_title' => $category->getTitle(),
            'expiry_date' => $expiryDate->format('Y-m-d'),
            'days_until_expiry' => $daysUntilExpiry,
            'certificate_url' => $certificateUrl,
            'locale' => $locale,
        ]);
    }

    /**
     * Renders the same template as renderBody(), but with placeholder tokens instead of
     * real values — used to preview what a reminder looks like before sending, without
     * exposing (or requiring) any specific learner's data. A single send can target many
     * learners at once, so there is no single set of real values to show.
     */
    public function renderPreview(CertificateExpiryNotificationType $type): string
    {
        return $this->twig->render($this->templateFor($type), [
            'full_name' => '['.$this->translator->trans('Learner name').']',
            'course_title' => '['.$this->translator->trans('Course').']',
            'category_title' => '['.$this->translator->trans('Certificate').']',
            'expiry_date' => '['.$this->translator->trans('Expiry date').']',
            'days_until_expiry' => '['.$this->translator->trans('Number of days').']',
            'certificate_url' => '#',
        ]);
    }

    private function templateFor(CertificateExpiryNotificationType $type): string
    {
        return CertificateExpiryNotificationType::EXPIRED === $type
            ? '@ChamiloCore/Mailer/GradebookCertificate/expiry_expired.html.twig'
            : '@ChamiloCore/Mailer/GradebookCertificate/expiry_about_to_expire.html.twig';
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

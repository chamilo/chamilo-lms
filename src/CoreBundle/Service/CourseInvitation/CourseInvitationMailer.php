<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\CourseInvitation;

use Chamilo\CoreBundle\Entity\CourseInvitation;
use Chamilo\CoreBundle\Helpers\MailHelper;
use RuntimeException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Renders and sends the "you have been invited" email. Kept separate from
 * CourseInvitationTokenService (which only owns the invitation/token
 * lifecycle) — mirrors the dedicated-mailer-service pattern already used by
 * ExerciseRuntimeResultEmailService for a different feature.
 */
final readonly class CourseInvitationMailer
{
    public function __construct(
        private Environment $twig,
        private MailerInterface $mailer,
        private MailHelper $mailHelper,
        private TranslatorInterface $translator,
    ) {}

    public function send(CourseInvitation $invitation, string $url): void
    {
        $isSession = $invitation->isSessionInvitation();
        $targetTitle = $isSession
            ? (string) $invitation->getSession()?->getTitle()
            : (string) $invitation->getCourse()?->getTitle();

        if ('' === $targetTitle) {
            throw new RuntimeException('The invitation has no course or session title to notify about.');
        }

        $body = $this->twig->render('@ChamiloCore/Mailer/CourseInvitation/invitation_body.html.twig', [
            'is_session' => $isSession,
            'is_existing_user' => $invitation->isForExistingUser(),
            'target_title' => $targetTitle,
            'url' => $url,
            'inviter_name' => $invitation->getCreatedBy()->getFullName(),
        ]);

        $email = (new Email())
            ->from($this->mailHelper->getPlatformFromAddress())
            ->to(new Address($invitation->getEmail()))
            ->subject($this->translator->trans('You have been invited to join a course'))
            ->html($body)
        ;

        $this->mailer->send($email);
    }
}

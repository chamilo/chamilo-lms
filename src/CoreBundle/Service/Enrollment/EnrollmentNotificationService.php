<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Enrollment;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\MessageHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Contracts\Translation\TranslatorInterface;

use const ENT_QUOTES;

/**
 * Sends a "you've been enrolled in X" notification to a user, reminding them of
 * the Chamilo username the enrollment is linked to.
 *
 * Deliberately NOT wired to any Doctrine listener/event subscriber on
 * CourseRelUser/SessionRelUser, and not called from anywhere in the normal
 * Chamilo enrollment workflow (the legacy subscribe screens, the API resources
 * themselves, etc.) — a plain callable service, invoked only where something
 * explicitly asks for it. Built for the WordPress storefront plugin's case: an
 * existing Chamilo account (matched by email) gets silently enrolled after a
 * purchase, with no Chamilo-side signal at all that anything happened — unlike a
 * brand-new account, which already gets a "set your password" email mentioning
 * its username (CreateUserOnAccessUrlAction). This fills that specific gap
 * without changing what happens for every other, already-existing way to
 * enroll a user in Chamilo.
 */
final readonly class EnrollmentNotificationService
{
    public function __construct(
        private MessageHelper $messageHelper,
        private TranslatorInterface $translator,
        private SettingsManager $settingsManager,
        private UserHelper $userHelper,
    ) {}

    /**
     * @param 'course'|'session' $itemType
     */
    public function notifyEnrollment(User $user, string $itemType, string $itemTitle): void
    {
        $platformName = (string) $this->settingsManager->getSetting('platform.site_name', true);

        // User-supplied/admin-authored values (full name, item title, username) are
        // escaped before interpolation — this body is sent both as a stored Chamilo
        // message (rendered as HTML in the recipient's Messages UI) and as an HTML
        // email, same reasoning as the fix in CreateUserOnAccessUrlAction.
        $safeFullName = htmlspecialchars($user->getFullName(), ENT_QUOTES, 'UTF-8');
        $safeUsername = htmlspecialchars($user->getUsername(), ENT_QUOTES, 'UTF-8');
        $safeItemTitle = htmlspecialchars($itemTitle, ENT_QUOTES, 'UTF-8');
        $itemLabel = 'session' === $itemType
            ? $this->translator->trans('session')
            : $this->translator->trans('course');

        $subject = \sprintf(
            $this->translator->trans('You have been enrolled in %s'),
            $itemLabel
        );

        $body = \sprintf(
            $this->translator->trans(
                'Hello %s,<br><br>'.
                'You have been enrolled in the %s: <strong>%s</strong>.<br><br>'.
                'This enrollment is linked to your Chamilo account, username: <strong>%s</strong><br><br>'.
                'Log in to %s to access it.<br><br>'.
                'Best regards,<br>'.
                '%s'
            ),
            $safeFullName,
            $itemLabel,
            $safeItemTitle,
            $safeUsername,
            $platformName,
            $platformName
        );

        $currentUser = $this->userHelper->getCurrent();
        $senderId = $currentUser?->getId() ?? 1;

        $this->messageHelper->sendMessage(
            $user->getId(),
            $subject,
            $body,
            [],
            [],
            0,
            0,
            0,
            $senderId,
            0,
            false,
            true // Force sending an email too, not just the internal Chamilo message.
        );
    }
}

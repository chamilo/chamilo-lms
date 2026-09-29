<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\CourseInvitation;

use Chamilo\CoreBundle\Entity\CourseInvitation;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\ValidationToken;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

/**
 * The only piece of public/main/auth/registration.php that knows about
 * course invitations. Deliberately separate from, and not interleaved with,
 * that file's ~4 pre-existing "direct link" visibility checks (all driven by
 * $_REQUEST['c']/['s']/['e']) — those stay untouched for the existing
 * open-course case. This gate resolves the `?invitation=` hash once, decides
 * whether it should unlock the registration form even when allow_registration
 * is 'false', and performs the actual subscribe once a new account exists.
 */
final readonly class CourseInvitationRegistrationGate
{
    public function __construct(
        private CourseInvitationTokenService $tokenService,
        private CourseInvitationSubscriptionService $subscriptionService,
    ) {}

    /**
     * @return array{invitation: CourseInvitation, token: ValidationToken}|null
     */
    public function resolveFromRequest(Request $request): ?array
    {
        // The initial GET carries the hash in the query string; the
        // registration form (see registration.php) then resubmits it via a
        // hidden field on POST, since its <form action=""> does not repeat
        // the original query string. Check both explicitly (avoiding the
        // ambiguous Request::get()).
        $hash = (string) $request->query->get('invitation', '');
        if ('' === $hash) {
            $hash = (string) $request->request->get('invitation', '');
        }

        $hash = trim($hash);
        if ('' === $hash) {
            return null;
        }

        $resolved = $this->tokenService->resolve($hash);
        // Existing-user invitations redeem via /course-invitation/accept only.
        if (null === $resolved || $resolved['invitation']->isForExistingUser()) {
            return null;
        }

        return $resolved;
    }

    /**
     * @param array{invitation: CourseInvitation, token: ValidationToken}|null $resolved
     */
    public function canShowForm(?array $resolved): bool
    {
        if ('false' !== api_get_setting('allow_registration')) {
            return true;
        }

        return null !== $resolved && 'true' === api_get_setting('registration.allow_invitation_registration');
    }

    /**
     * Subscribes the freshly-created user per the invitation (whole session
     * if set, otherwise the plain course), confirms/consumes the invitation,
     * and returns the URL the caller should redirect to.
     *
     * @param array{invitation: CourseInvitation, token: ValidationToken} $resolved
     */
    public function subscribeAndRedirect(User $registeredUser, array $resolved, callable $buildRedirectUrl): string
    {
        $invitation = $resolved['invitation'];
        $course = $invitation->getCourse();

        // Existing-user invitations must not be redeemed via registration.
        if ($invitation->isForExistingUser()) {
            throw new RuntimeException('This invitation is for an existing account and cannot be used to register.');
        }

        $this->subscriptionService->subscribe($registeredUser, $invitation);
        $this->tokenService->confirm($resolved, $registeredUser);

        $redirectCourseId = null !== $course ? (int) $course->getId() : 0;

        return $buildRedirectUrl($redirectCourseId, (int) ($invitation->getExerciseId() ?? 0));
    }
}

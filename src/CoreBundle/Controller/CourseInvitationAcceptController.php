<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\CourseInvitation\CourseInvitationSubscriptionService;
use Chamilo\CoreBundle\Service\CourseInvitation\CourseInvitationTokenService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * Redeems an existing-user course invitation: requires the invited account
 * to be logged in, then auto-subscribes and consumes the one-time token
 * (same 7-day validity rules as registration invitations).
 */
final class CourseInvitationAcceptController extends BaseController
{
    public function __construct(
        private readonly CourseInvitationTokenService $tokenService,
        private readonly CourseInvitationSubscriptionService $subscriptionService,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('/course-invitation/accept', name: 'course_invitation_accept', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $hash = trim((string) $request->query->get('invitation', ''));
        $acceptPath = '/course-invitation/accept'.($hash ? '?invitation='.rawurlencode($hash) : '');

        $currentUser = $this->getUser();
        if (!$currentUser instanceof User
            || null === $currentUser->getId()
            || $currentUser->hasRole('ROLE_ANONYMOUS')
        ) {
            return $this->redirectToRoute('login', ['redirect' => $acceptPath]);
        }

        $resolved = '' !== $hash ? $this->tokenService->resolve($hash) : null;
        if (null === $resolved) {
            $this->addFlash('error', $this->translator->trans('This invitation link is invalid or has expired.'));

            return new RedirectResponse(api_get_path(WEB_PATH));
        }

        $invitation = $resolved['invitation'];
        if (!$invitation->isForExistingUser()) {
            // Registration invitations must not be redeemed here.
            return new RedirectResponse(
                api_get_path(WEB_CODE_PATH).'auth/registration.php?invitation='.rawurlencode($hash)
            );
        }

        $invitedUser = $invitation->getInvitedUser();
        if (!$invitedUser instanceof User || (int) $invitedUser->getId() !== (int) $currentUser->getId()) {
            $this->addFlash(
                'error',
                $this->translator->trans('This invitation was sent to a different user. Please log in with the invited account.')
            );

            return new RedirectResponse(api_get_path(WEB_PATH));
        }

        $homeUrl = $this->subscriptionService->buildCourseHomeUrl($invitation);

        try {
            if (!$this->subscriptionService->isAlreadySubscribedToInvitation($currentUser, $invitation)) {
                $this->subscriptionService->subscribe($currentUser, $invitation);
            }

            $this->tokenService->confirm($resolved, $currentUser);
        } catch (Throwable) {
            $this->addFlash('error', $this->translator->trans('The invitation could not be accepted.'));

            return new RedirectResponse(api_get_path(WEB_PATH));
        }

        $this->addFlash('success', $this->translator->trans('You have been subscribed successfully.'));

        return new RedirectResponse($homeUrl);
    }
}

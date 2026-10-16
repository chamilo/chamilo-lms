<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\ConferenceMeeting;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ConferenceMeetingRepository;
use Chamilo\CoreBundle\Service\Conference\MicrosoftTeamsJoinAuthenticationService;
use Chamilo\CoreBundle\Service\Conference\TeamsMeetingAccessHelper;
use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use const PHP_QUERY_RFC3986;

#[IsGranted('ROLE_USER')]
final class TeamsConferenceAuthController extends BaseController
{
    #[Route(
        '/conference/teams-auth/join/{id}',
        name: 'teams_conference_auth_join',
        requirements: ['id' => '\d+'],
        methods: ['GET'],
    )]
    public function join(
        int $id,
        Request $request,
        ConferenceMeetingRepository $meetingRepository,
        TeamsMeetingAccessHelper $accessHelper,
        MicrosoftTeamsJoinAuthenticationService $joinAuthentication,
    ): RedirectResponse {
        $meeting = $meetingRepository->findTeamsMeeting($id);
        if (!$meeting instanceof ConferenceMeeting) {
            throw new NotFoundHttpException('The Microsoft Teams meeting was not found.');
        }

        $accessHelper->assertCanJoinMeetingDirect($meeting);
        $user = $accessHelper->getAuthenticatedUser();

        if (!$this->isMeetingOrganizer($meeting, $user)) {
            return new RedirectResponse($accessHelper->getSafeJoinUrl($meeting));
        }

        $authorizationUrl = $joinAuthentication->begin(
            $request,
            (int) $meeting->getId(),
            (int) $user->getId(),
            $this->resolveLoginHint($meeting, $user),
        );

        return new RedirectResponse($authorizationUrl);
    }

    #[Route(
        '/conference/teams-auth/callback',
        name: 'teams_conference_auth_callback',
        methods: ['GET'],
    )]
    public function callback(
        Request $request,
        ConferenceMeetingRepository $meetingRepository,
        TeamsMeetingAccessHelper $accessHelper,
        MicrosoftTeamsJoinAuthenticationService $joinAuthentication,
    ): RedirectResponse {
        $pendingMeeting = $this->findPendingMeeting($request, $meetingRepository, $joinAuthentication);
        $user = $accessHelper->getAuthenticatedUser();

        try {
            $identity = $joinAuthentication->complete($request, (int) $user->getId());
        } catch (RuntimeException) {
            return $this->redirectAfterAuthFailure($pendingMeeting, 'failed');
        }

        $meeting = $meetingRepository->findTeamsMeeting($identity['meetingId']);
        if (!$meeting instanceof ConferenceMeeting) {
            throw new NotFoundHttpException('The Microsoft Teams meeting was not found.');
        }

        $accessHelper->assertCanJoinMeetingDirect($meeting);

        $expectedOrganizer = strtolower(trim((string) $meeting->getAccountEmail()));

        if ($this->isMeetingOrganizer($meeting, $user) && '' !== $expectedOrganizer) {
            $microsoftEmails = array_values(array_filter([
                strtolower(trim($identity['mail'])),
                strtolower(trim($identity['userPrincipalName'])),
            ]));

            if (!\in_array($expectedOrganizer, $microsoftEmails, true)) {
                return $this->redirectAfterAuthFailure($meeting, 'account_mismatch');
            }
        }

        return new RedirectResponse($accessHelper->getSafeJoinUrl($meeting));
    }

    private function resolveLoginHint(ConferenceMeeting $meeting, User $user): string
    {
        $expectedOrganizer = trim((string) $meeting->getAccountEmail());
        $currentEmail = trim($user->getEmail());

        return $this->isMeetingOrganizer($meeting, $user) && '' !== $expectedOrganizer
            ? $expectedOrganizer
            : $currentEmail;
    }

    private function isMeetingOrganizer(ConferenceMeeting $meeting, User $user): bool
    {
        $expectedOrganizer = strtolower(trim((string) $meeting->getAccountEmail()));
        $currentChamiloEmail = strtolower(trim($user->getEmail()));

        return $meeting->getUser()?->getId() === $user->getId()
            || ('' !== $expectedOrganizer && $expectedOrganizer === $currentChamiloEmail);
    }

    private function findPendingMeeting(
        Request $request,
        ConferenceMeetingRepository $meetingRepository,
        MicrosoftTeamsJoinAuthenticationService $joinAuthentication,
    ): ?ConferenceMeeting {
        $meetingId = $joinAuthentication->getPendingMeetingId($request);
        if (null === $meetingId) {
            return null;
        }

        return $meetingRepository->findTeamsMeeting($meetingId);
    }

    private function redirectAfterAuthFailure(?ConferenceMeeting $meeting, string $status): RedirectResponse
    {
        $params = ['teamsAuth' => $status];

        if ($meeting instanceof ConferenceMeeting && $meeting->getCourse()) {
            $params['cid'] = (int) $meeting->getCourse()->getId();

            if ($meeting->getSession()) {
                $params['sid'] = (int) $meeting->getSession()->getId();
            }

            if ($meeting->getGroup()) {
                $params['gid'] = (int) $meeting->getGroup()->getIid();
            }

            return new RedirectResponse('/conference/teams/course?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986));
        }

        $scope = $meeting instanceof ConferenceMeeting && null === $meeting->getUser()
            ? TeamsMeetingAccessHelper::SCOPE_GLOBAL
            : TeamsMeetingAccessHelper::SCOPE_PERSONAL;
        $params['scope'] = $scope;

        return new RedirectResponse('/conference/teams?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986));
    }
}

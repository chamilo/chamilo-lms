<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseInvitation;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseInvitation\CourseInvitationItem;
use Chamilo\CoreBundle\Entity\CourseInvitation;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Repository\CourseInvitationRepository;
use Chamilo\CoreBundle\Service\CourseInvitation\CourseInvitationTokenService;
use JsonException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use const JSON_THROW_ON_ERROR;

/**
 * @implements ProcessorInterface<CourseInvitationItem, void>
 */
final readonly class CourseInvitationRevokeProcessor implements ProcessorInterface
{
    use CourseInvitationAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private CourseInvitationRepository $invitationRepository,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private CourseInvitationTokenService $tokenService,
        private CidReqHelper $cidReqHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($this->cidReqHelper);
        $session = $this->getSession($this->cidReqHelper);
        $this->assertSessionBelongsToCourse($session, $course);

        if (!$this->canManageCourseInvitations($this->security, $course, $session)) {
            throw new AccessDeniedHttpException('You are not allowed to manage course invitations in this context.');
        }

        $this->validateCsrfToken($this->getSubmittedCsrfToken($request));

        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            throw new AccessDeniedHttpException('An authenticated user is required.');
        }

        $invitationId = isset($uriVariables['id']) ? (int) $uriVariables['id'] : 0;
        if ($invitationId <= 0) {
            throw new BadRequestHttpException('A valid invitation id is required.');
        }

        $invitation = $this->invitationRepository->find($invitationId);
        if (!$invitation instanceof CourseInvitation || $invitation->getCourse()?->getId() !== $course->getId()) {
            throw new NotFoundHttpException('The requested invitation was not found.');
        }

        if ($invitation->isAccepted()) {
            throw new ConflictHttpException('This invitation has already been accepted and can no longer be revoked.');
        }

        $this->tokenService->revoke($invitation, $currentUser);
    }

    private function getSubmittedCsrfToken(Request $request): string
    {
        $content = trim($request->getContent());
        if ('' === $content) {
            return '';
        }

        try {
            $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new BadRequestHttpException('The request payload is invalid.');
        }

        if (!\is_array($payload)) {
            return '';
        }

        $token = $payload['csrfToken'] ?? '';

        return \is_string($token) ? $token : '';
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(CourseInvitationSendProcessor::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }
}

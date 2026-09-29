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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<CourseInvitationItem, void>
 */
final readonly class CourseInvitationRevokeProcessor implements ProcessorInterface
{
    use CourseInvitationAccessHelperTrait;

    public function __construct(
        private CourseInvitationRepository $invitationRepository,
        private Security $security,
        private CourseInvitationTokenService $tokenService,
        private CidReqHelper $cidReqHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $course = $this->getCourse($this->cidReqHelper);
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $this->assertSessionBelongsToCourse($session, $course);

        if (!$this->canManageCourseInvitations($this->security, $course, $session)) {
            throw new AccessDeniedHttpException('You are not allowed to manage course invitations in this context.');
        }

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
}

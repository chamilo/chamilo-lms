<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseInvitation;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseInvitation\CourseInvitationItem;
use Chamilo\CoreBundle\Entity\CourseInvitation;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Repository\CourseInvitationRepository;
use Chamilo\CoreBundle\Service\CourseInvitation\CourseInvitationTokenService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<CourseInvitationItem>
 */
final readonly class CourseInvitationProvider implements ProviderInterface
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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|CourseInvitationItem|null
    {
        $course = $this->getCourse($this->cidReqHelper);
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $this->assertSessionBelongsToCourse($session, $course);

        if (!$this->canManageCourseInvitations($this->security, $course, $session)) {
            throw new AccessDeniedHttpException('You are not allowed to manage course invitations in this context.');
        }

        if ($operation instanceof CollectionOperationInterface) {
            $items = [];
            foreach ($this->invitationRepository->findAllForCourse($course) as $invitation) {
                $items[] = $this->toItem($invitation);
            }

            return $items;
        }

        if (isset($uriVariables['id'])) {
            $invitation = $this->invitationRepository->find((int) $uriVariables['id']);
            if (!$invitation instanceof CourseInvitation || $invitation->getCourse()?->getId() !== $course->getId()) {
                throw new NotFoundHttpException('The requested invitation was not found.');
            }

            return $this->toItem($invitation);
        }

        $item = new CourseInvitationItem();
        $item->isSessionContext = null !== $session;
        $item->contextTitle = $session?->getTitle() ?? $course->getTitle();

        return $item;
    }

    private function toItem(CourseInvitation $invitation): CourseInvitationItem
    {
        $item = CourseInvitationItem::fromInvitation($invitation);
        $item->invitationUrl = $this->tokenService->getActiveUrl($invitation);

        return $item;
    }
}

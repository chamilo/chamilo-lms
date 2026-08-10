<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseInvitation;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseInvitation\CourseInvitationItem;
use Chamilo\CoreBundle\ApiResource\CourseInvitation\CourseInvitationWriteInput;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Service\CourseInvitation\CourseInvitationMailer;
use Chamilo\CoreBundle\Service\CourseInvitation\CourseInvitationSubscriptionService;
use Chamilo\CoreBundle\Service\CourseInvitation\CourseInvitationTokenService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements ProcessorInterface<CourseInvitationWriteInput, CourseInvitationItem>
 */
final readonly class CourseInvitationSendProcessor implements ProcessorInterface
{
    use CourseInvitationAccessHelperTrait;

    public function __construct(
        private Security $security,
        private UserRepository $userRepository,
        private CourseInvitationTokenService $tokenService,
        private CourseInvitationMailer $mailer,
        private CourseInvitationSubscriptionService $subscriptionService,
        private CidReqHelper $cidReqHelper,
        private TranslatorInterface $translator,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CourseInvitationItem
    {
        if (!$data instanceof CourseInvitationWriteInput) {
            throw new BadRequestHttpException('The request payload is invalid.');
        }

        $course = $this->getCourse($this->cidReqHelper);
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $this->assertSessionBelongsToCourse($session, $course);

        if (!$this->canManageCourseInvitations($this->security, $course, $session)) {
            throw new AccessDeniedHttpException('You are not allowed to send course invitations in this context.');
        }

        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User || null === $currentUser->getId()) {
            throw new AccessDeniedHttpException('An authenticated user is required.');
        }

        $email = trim(strtolower($data->email));
        if ('' === $email) {
            throw new BadRequestHttpException('A valid email address is required.');
        }

        $existingUser = $this->userRepository->findByEmailCaseInsensitive($email);
        $invitedUser = null;

        if ($existingUser instanceof User) {
            if (User::SOFT_DELETED === $existingUser->getActive()) {
                throw new BadRequestHttpException($this->translator->trans('This email address cannot be invited.'));
            }

            if ($this->subscriptionService->isAlreadySubscribed($existingUser, $course, $session)) {
                throw new ConflictHttpException($session instanceof Session ? $this->translator->trans('This user is already subscribed to the session.') : $this->translator->trans('This user is already subscribed to the course.'));
            }

            $invitedUser = $existingUser;
        }

        $created = $this->tokenService->create(
            $course,
            $session,
            null,
            $email,
            $currentUser,
            $invitedUser,
        );

        $this->mailer->send($created['invitation'], $created['url']);

        return CourseInvitationItem::fromInvitation($created['invitation']);
    }
}

<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseInvitation;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseInvitation\CourseInvitationItem;
use Chamilo\CoreBundle\ApiResource\CourseInvitation\CourseInvitationWriteInput;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Service\CourseInvitation\CourseInvitationMailer;
use Chamilo\CoreBundle\Service\CourseInvitation\CourseInvitationTokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<CourseInvitationWriteInput, CourseInvitationItem>
 */
final readonly class CourseInvitationSendProcessor implements ProcessorInterface
{
    use CourseInvitationAccessHelperTrait;

    public const string CSRF_TOKEN_ID = 'course_invitation';

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private UserRepository $userRepository,
        private CourseInvitationTokenService $tokenService,
        private CourseInvitationMailer $mailer,
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

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request, $this->entityManager);
        $session = $this->getSession($request, $this->entityManager);
        $this->assertSessionBelongsToCourse($session, $course);

        if (!$this->canManageCourseInvitations($this->security, $course, $session)) {
            throw new AccessDeniedHttpException('You are not allowed to send course invitations in this context.');
        }

        $this->validateCsrfToken($data->csrfToken);

        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User || null === $currentUser->getId()) {
            throw new AccessDeniedHttpException('An authenticated user is required.');
        }

        $email = trim(strtolower($data->email));
        if ('' === $email) {
            throw new BadRequestHttpException('A valid email address is required.');
        }

        if ($this->userRepository->findByEmailCaseInsensitive($email) instanceof User) {
            throw new ConflictHttpException('This email address already has an account on this platform. Subscribe the existing user directly instead of sending an invitation.');
        }

        $created = $this->tokenService->create(
            $course,
            $session,
            null,
            $email,
            $currentUser,
        );

        $this->mailer->send($created['invitation'], $created['url']);

        return CourseInvitationItem::fromInvitation($created['invitation']);
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }
}

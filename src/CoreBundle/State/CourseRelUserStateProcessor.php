<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Guards course subscriptions on writing:
 * - Rejects duplicate subscriptions of the same user to the same course;
 * - Forces a safe status — only admins and teachers of the target course may assign a status other
 *   than STUDENT (e.g., enrol someone as a teacher); any other user, typically self-enrolling from
 *   the course catalogue, is always persisted as a student regardless of the status in the body.
 *
 * @implements ProcessorInterface<CourseRelUser, CourseRelUser|void>
 */
final class CourseRelUserStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly Security $security,
        private readonly UserHelper $userHelper,
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): ?CourseRelUser
    {
        if ($operation instanceof Post) {
            \assert($data instanceof CourseRelUser);

            if ($data->getCourse()->hasSubscriptionByUser($data->getUser())) {
                throw new ConflictHttpException('User is already subscribed to this course.');
            }

            $currentUser = $this->userHelper->getCurrent();
            $isPrivileged = $this->security->isGranted('ROLE_ADMIN')
                || (null !== $currentUser && $data->getCourse()->hasUserAsTeacher($currentUser));

            if (!$isPrivileged || !\in_array($data->getStatus(), [CourseRelUser::STUDENT, CourseRelUser::TEACHER], true)) {
                $data->setStatus(CourseRelUser::STUDENT);
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}

<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use ApiPlatform\Validator\ValidatorInterface;
use Chamilo\CoreBundle\Dto\EnrollmentNotificationInput;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\Enrollment\EnrollmentNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * POST /api/enrollment-notifications — the only way EnrollmentNotificationService
 * ever gets called; nothing else in Chamilo core invokes it. Verifies the given
 * user is actually enrolled in the given course/session before sending anything —
 * cheap to check, and avoids sending a misleading "you've been enrolled" message
 * on a bad itemId/itemType combination instead of failing loudly.
 */
#[AsController]
readonly class NotifyEnrollmentAction
{
    public function __construct(
        private ValidatorInterface $validator,
        private EntityManagerInterface $em,
        private EnrollmentNotificationService $enrollmentNotificationService,
    ) {}

    public function __invoke(EnrollmentNotificationInput $data): void
    {
        $this->validator->validate($data);

        $user = $data->getUser();
        if (!$user instanceof User) {
            throw new BadRequestHttpException('A valid user is required.');
        }

        if ('session' === $data->getItemType()) {
            $session = $this->em->getRepository(Session::class)->find($data->getItemId());
            if (!$session instanceof Session) {
                throw new NotFoundHttpException('Session not found.');
            }

            $enrolled = null !== $this->em->getRepository(SessionRelUser::class)->findOneBy([
                'user' => $user,
                'session' => $session,
            ]);
            if (!$enrolled) {
                throw new BadRequestHttpException('This user is not enrolled in this session.');
            }

            $this->enrollmentNotificationService->notifyEnrollment($user, 'session', (string) $session->getTitle());

            return;
        }

        $course = $this->em->getRepository(Course::class)->find($data->getItemId());
        if (!$course instanceof Course) {
            throw new NotFoundHttpException('Course not found.');
        }

        $enrolled = null !== $this->em->getRepository(CourseRelUser::class)->findOneBy([
            'user' => $user,
            'course' => $course,
        ]);
        if (!$enrolled) {
            throw new BadRequestHttpException('This user is not enrolled in this course.');
        }

        $this->enrollmentNotificationService->notifyEnrollment($user, 'course', (string) $course->getTitle());
    }
}

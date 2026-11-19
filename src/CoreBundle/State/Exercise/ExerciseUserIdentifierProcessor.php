<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseUserIdentifier;
use Chamilo\CoreBundle\Component\Exercise\FinalExamAccessRule;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Saves, on the profile of the current user only, the identifier a test of the current course
 * requires before it starts.
 *
 * @implements ProcessorInterface<ExerciseUserIdentifier, ExerciseUserIdentifier>
 */
final readonly class ExerciseUserIdentifierProcessor implements ProcessorInterface
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private CQuizRepository $quizRepository,
        private Security $security,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExerciseUserIdentifier
    {
        if (!$data instanceof ExerciseUserIdentifier) {
            throw new BadRequestHttpException('Invalid user identifier payload.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        $exerciseId = (int) ($uriVariables['exerciseId'] ?? 0);
        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        if ($exerciseId <= 0 || null === $this->quizRepository->findInCourseContextWithVisibility($exerciseId, $course, $session)) {
            throw new NotFoundHttpException('The requested exercise was not found.');
        }

        $result = new ExerciseUserIdentifier();
        $result->exerciseId = $exerciseId;
        $result->success = FinalExamAccessRule::saveUserIdentifier((int) $user->getId(), $exerciseId, $data->value);

        return $result;
    }
}

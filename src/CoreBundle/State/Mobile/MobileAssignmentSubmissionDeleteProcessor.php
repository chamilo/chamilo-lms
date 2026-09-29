<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Mobile;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Service\Assignment\MobileAssignmentSubmissionAccess;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use LogicException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<object, void>
 */
final readonly class MobileAssignmentSubmissionDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private CStudentPublicationRepository $studentPublicationRepository,
        private MobileAssignmentSubmissionAccess $submissionAccess,
        private RequestStack $requestStack,
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): void {
        if (!$operation instanceof Delete) {
            throw new LogicException('Unsupported mobile assignment submission delete operation.');
        }

        $submissionId = (int) ($uriVariables['id'] ?? 0);
        $request = $this->requestStack->getCurrentRequest();

        if ($submissionId <= 0 || null === $request) {
            throw new NotFoundHttpException('Assignment submission not found.');
        }

        $assignmentId = $request->query->getInt('assignmentId');
        $courseId = $request->query->getInt('courseId');
        $rawSessionId = $request->query->get('sessionId');
        $sessionId = null;

        if (null !== $rawSessionId && '' !== $rawSessionId) {
            $sessionId = (int) $rawSessionId;
        }

        if ($assignmentId <= 0 || $courseId <= 0 || (null !== $sessionId && $sessionId <= 0)) {
            throw new UnprocessableEntityHttpException('A valid assignment course context is required.');
        }

        $courseContext = $this->submissionAccess->resolveCourseContext($courseId, $sessionId);
        $submission = $this->submissionAccess->resolveOwnedSubmission(
            $submissionId,
            $assignmentId,
            $courseContext['user'],
            $courseContext['course'],
            $courseContext['session'],
        );
        $this->submissionAccess->assertCanDelete(
            $submission,
            $courseContext['user'],
            $courseContext['course'],
            $courseContext['session'],
        );

        $this->studentPublicationRepository->delete($submission);
    }
}

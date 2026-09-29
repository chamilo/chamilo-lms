<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Mobile;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Mobile\MobileAssignmentSubmission;
use Chamilo\CoreBundle\ApiResource\Mobile\MobileAssignmentSubmissionUpdateInput;
use Chamilo\CoreBundle\Service\Assignment\MobileAssignmentSubmissionAccess;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use LogicException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * @implements ProcessorInterface<MobileAssignmentSubmissionUpdateInput, MobileAssignmentSubmission>
 */
final readonly class MobileAssignmentSubmissionUpdateProcessor implements ProcessorInterface
{
    public function __construct(
        private CStudentPublicationRepository $studentPublicationRepository,
        private MobileAssignmentSubmissionAccess $submissionAccess,
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): MobileAssignmentSubmission {
        if (!$operation instanceof Patch || !$data instanceof MobileAssignmentSubmissionUpdateInput) {
            throw new LogicException('Unsupported mobile assignment submission update operation.');
        }

        $submissionId = (int) ($uriVariables['id'] ?? 0);

        if ($submissionId <= 0) {
            throw new NotFoundHttpException('Assignment submission not found.');
        }

        $courseContext = $this->submissionAccess->resolveCourseContext(
            $data->courseId,
            $data->sessionId,
        );
        $submission = $this->submissionAccess->resolveOwnedSubmission(
            $submissionId,
            $data->assignmentId,
            $courseContext['user'],
            $courseContext['course'],
            $courseContext['session'],
        );
        $this->submissionAccess->assertCanEdit(
            $submission,
            $courseContext['user'],
            $courseContext['course'],
            $courseContext['session'],
        );

        $title = trim(strip_tags($data->title));

        if ('' === $title) {
            throw new UnprocessableEntityHttpException('A submission title is required.');
        }

        $description = trim($data->description);
        $safeDescription = nl2br(htmlspecialchars(
            $description,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
        ));

        $submission
            ->setTitle($title)
            ->setDescription($safeDescription)
        ;

        $this->studentPublicationRepository->update($submission);

        return MobileAssignmentSubmission::fromSubmission($submission);
    }
}

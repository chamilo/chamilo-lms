<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Service\Gradebook\GradebookExportService;
use Chamilo\CoreBundle\State\Gradebook\GradebookEvaluationResultsProvider;
use Chamilo\CoreBundle\State\Gradebook\GradebookLearnerReportProvider;
use Chamilo\CoreBundle\State\Gradebook\GradebookReportProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use const PHP_SESSION_ACTIVE;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route(
    '/api/gradebook/export/{scope}.{format}',
    name: 'gradebook_export',
    requirements: [
        'scope' => 'flat|evaluation|learner|students',
        'format' => 'csv|xls|docx|pdf|xml',
    ],
    methods: ['GET'],
)]
final readonly class GradebookExportController
{
    public function __construct(
        private GradebookReportProvider $reportProvider,
        private GradebookLearnerReportProvider $learnerReportProvider,
        private GradebookEvaluationResultsProvider $evaluationResultsProvider,
        private GradebookExportService $exportService,
        private EntityManagerInterface $entityManager,
    ) {}

    public function __invoke(Request $request, string $scope, string $format): Response
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        $course = $this->getCourse($request);

        return match ($scope) {
            'flat' => $this->exportFlat($request, $format, $course),
            'evaluation' => $this->exportEvaluation($request, $format, $course),
            'learner' => $this->exportLearner($request, $format, $course),
            'students' => $this->exportStudents($request, $format, $course),
            default => throw new BadRequestHttpException('Unsupported Gradebook export scope.'),
        };
    }

    private function exportFlat(Request $request, string $format, Course $course): Response
    {
        if (!\in_array($format, ['csv', 'xls', 'docx', 'pdf'], true)) {
            throw new BadRequestHttpException('The requested format is not supported for the Gradebook list view.');
        }

        $report = $this->reportProvider->buildReport($request, true, true);
        if ('pdf' === $format && true === ($report->settings['hidePdfReportButton'] ?? false)) {
            throw new AccessDeniedHttpException('Gradebook PDF reports are disabled by platform settings.');
        }

        return $this->exportService->createFlatResponse($report, $format, $course->getTitle());
    }

    private function exportEvaluation(Request $request, string $format, Course $course): Response
    {
        if (!\in_array($format, ['csv', 'xml', 'pdf'], true)) {
            throw new BadRequestHttpException('The requested format is not supported for manual evaluation results.');
        }

        return $this->exportService->createEvaluationResponse(
            $this->evaluationResultsProvider->buildReport($request),
            $format,
            $course->getTitle(),
        );
    }

    private function exportLearner(Request $request, string $format, Course $course): Response
    {
        if ('pdf' !== $format) {
            throw new BadRequestHttpException('Detailed learner reports can only be exported as PDF.');
        }

        $report = $this->learnerReportProvider->buildReport($request);
        if (!$report->canManage && true === ($report->settings['hidePdfReportButton'] ?? false)) {
            throw new AccessDeniedHttpException('Gradebook PDF reports are disabled by platform settings.');
        }

        return $this->exportService->createLearnerPdfResponse($report, $course->getTitle());
    }

    private function exportStudents(Request $request, string $format, Course $course): Response
    {
        if ('pdf' !== $format) {
            throw new BadRequestHttpException('The learner summary can only be exported as PDF.');
        }

        $report = $this->reportProvider->buildReport($request, true, false);
        $reports = [];
        foreach ($report->rows as $row) {
            $user = \is_array($row['user'] ?? null) ? $row['user'] : [];
            $userId = (int) ($user['id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            $reports[] = $this->learnerReportProvider->buildReport($request, $userId);
        }

        return $this->exportService->createStudentsPdfResponse(
            $reports,
            $course->getTitle(),
            (string) ($report->category['title'] ?? ''),
        );
    }

    private function getCourse(Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if ($courseId <= 0) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('The requested course was not found.');
        }

        return $course;
    }
}

<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingContextResolver;
use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingQueryService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

use const PREG_SPLIT_NO_EMPTY;

#[Route(
    '/api/course-reporting/learners.csv',
    name: 'course_reporting_learners_csv',
    methods: ['GET']
)]
final readonly class CourseReportingLearnersCsvController
{
    public function __construct(
        private CourseReportingContextResolver $contextResolver,
        private CourseReportingQueryService $queryService,
    ) {}

    public function __invoke(Request $request): StreamedResponse
    {
        $context = $this->contextResolver->resolve();
        $selectedExtraFieldIds = array_values(array_unique(array_filter(array_map(
            'intval',
            preg_split('/\s*,\s*/', (string) $request->query->get('extraFieldIds', ''), -1, PREG_SPLIT_NO_EMPTY) ?: []
        ))));
        $configuration = $this->queryService->getConfiguration($context);
        $configuredExercises = $configuration['configuredExercises'];
        $selectedExtraFields = array_values(array_filter(
            $configuration['extraFields'],
            static fn (array $field): bool => \in_array((int) $field['id'], $selectedExtraFieldIds, true)
        ));

        $rows = $this->queryService->getLearnersForExport(
            $context,
            [
                'keyword' => (string) $request->query->get('keyword', ''),
                'groupFilter' => (string) $request->query->get('groupFilter', ''),
                'showTeachers' => $request->query->getBoolean('showTeachers'),
                'showActiveUsers' => $request->query->getBoolean('showActiveUsers'),
                'sort' => (string) $request->query->get('sort', 'lastname'),
                'direction' => (string) $request->query->get('direction', 'ASC'),
                'extraFieldIds' => (string) $request->query->get('extraFieldIds', ''),
                'extraFieldFilters' => (string) $request->query->get('extraFieldFilters', ''),
            ]
        );

        $filename = preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '-',
            strtolower($context->course->getCode())
        ) ?: 'course';
        $filename .= '-learner-report.csv';

        $response = new StreamedResponse(function () use (
            $rows,
            $context,
            $configuredExercises,
            $selectedExtraFields
        ): void {
            $output = fopen('php://output', 'wb');
            if (false === $output) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");

            $headers = [
                'Code',
                'Last name',
                'First name',
                'Login',
                'Time',
                'Progress',
                'Exercise progress',
                'Exercise average',
                'Score',
                'Score - Only best attempts',
            ];

            foreach ($configuredExercises as $exercise) {
                $headers[] = 'Test: '.(string) $exercise['title'];
            }

            array_push(
                $headers,
                'Assignments',
                'Messages',
                'Classes',
                0 === $context->sessionId() ? 'Survey' : 'Registered date',
                'First access to course',
                'Latest access in course',
                "Last lp's finalization date",
                'Last quiz finalization date'
            );

            if ($context->showEmailAddresses) {
                $headers[] = 'E-mail';
            }

            foreach ($selectedExtraFields as $field) {
                $headers[] = (string) $field['label'];
            }

            fputcsv($output, $headers);

            foreach ($rows as $row) {
                $line = [
                    $row['officialCode'],
                    $row['lastname'],
                    $row['firstname'],
                    $row['username'],
                    $this->formatDuration((int) $row['timeSeconds']),
                    $row['learningPathProgress'].'%',
                    $row['exerciseProgress'].'%',
                    $row['exerciseAverage'].'%',
                    $row['score'].'%',
                    $row['bestScore'].'%',
                ];

                foreach ($configuredExercises as $exercise) {
                    $exerciseId = (string) (int) $exercise['id'];
                    $exerciseResult = $row['configuredExerciseResults'][$exerciseId] ?? null;
                    $line[] = null === $exerciseResult ? '' : $exerciseResult.'%';
                }

                array_push(
                    $line,
                    $row['assignments'],
                    $row['messages'],
                    implode(', ', $row['classes']),
                    0 === $context->sessionId() ? $row['survey'] : ($row['registeredAt'] ?? ''),
                    $row['firstAccess'] ?? '',
                    $row['latestAccess'] ?? '',
                    $row['learningPathFinalizationDate'] ?? '',
                    $row['quizFinalizationDate'] ?? ''
                );

                if ($context->showEmailAddresses) {
                    $line[] = $row['email'];
                }

                foreach ($selectedExtraFields as $field) {
                    $line[] = (string) ($row['extraFields'][(string) $field['variable']] ?? '');
                }

                fputcsv($output, array_map(
                    fn (mixed $value): mixed => \is_string($value)
                        ? $this->neutralizeSpreadsheetFormula($value)
                        : $value,
                    $line
                ));
            }

            fclose($output);
        });

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    private function neutralizeSpreadsheetFormula(string $value): string
    {
        if ('' !== $value && \in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'".$value;
        }

        return $value;
    }

    private function formatDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        return \sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
    }
}

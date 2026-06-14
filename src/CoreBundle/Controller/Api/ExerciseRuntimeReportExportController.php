<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Service\Exercise\ExerciseRuntimeReportExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ExerciseRuntimeReportExportController extends AbstractController
{
    public function __construct(
        private readonly ExerciseRuntimeReportExportService $exportService,
    ) {}

    #[Route(
        '/api/exercise/runtime/{exerciseId}/attempts/export.csv',
        name: 'chamilo_core_exercise_runtime_report_export_csv',
        requirements: ['exerciseId' => '\\d+'],
        methods: ['GET']
    )]
    public function csv(int $exerciseId, Request $request): StreamedResponse
    {
        return $this->exportService->exportCsv($exerciseId, $request);
    }

    #[Route(
        '/api/exercise/runtime/{exerciseId}/attempts/export.xlsx',
        name: 'chamilo_core_exercise_runtime_report_export_xlsx',
        requirements: ['exerciseId' => '\\d+'],
        methods: ['GET']
    )]
    public function xlsx(int $exerciseId, Request $request): BinaryFileResponse
    {
        return $this->exportService->exportXlsx($exerciseId, $request);
    }
}

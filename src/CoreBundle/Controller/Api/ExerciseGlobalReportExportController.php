<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Service\Exercise\ExerciseGlobalReportExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class ExerciseGlobalReportExportController extends AbstractController
{
    public function __construct(
        private readonly ExerciseGlobalReportExportService $exportService,
    ) {}

    #[Route(
        '/api/exercise/global-report/export.csv',
        name: 'chamilo_core_exercise_global_report_export_csv',
        methods: ['GET']
    )]
    public function __invoke(Request $request): StreamedResponse
    {
        return $this->exportService->exportCsv($request);
    }
}

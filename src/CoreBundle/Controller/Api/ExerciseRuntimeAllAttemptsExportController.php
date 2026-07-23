<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Service\Exercise\ExerciseRuntimeAllAttemptsExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ExerciseRuntimeAllAttemptsExportController extends AbstractController
{
    public function __construct(
        private readonly ExerciseRuntimeAllAttemptsExportService $exportService,
    ) {}

    #[Route(
        '/api/exercise/runtime/{exerciseId}/attempts/export-all.zip',
        name: 'chamilo_core_exercise_runtime_all_attempts_export_zip',
        requirements: ['exerciseId' => '\\d+'],
        methods: ['GET']
    )]
    public function __invoke(int $exerciseId, Request $request): BinaryFileResponse
    {
        return $this->exportService->exportAllAttemptsZip($exerciseId, $request);
    }
}

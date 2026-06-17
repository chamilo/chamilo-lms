<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Service\Exercise\ExerciseQti2ExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ExerciseQti2ExportController extends AbstractController
{
    public function __construct(
        private readonly ExerciseQti2ExportService $exportService,
    ) {}

    #[Route(
        '/api/exercise/{exerciseId}/qti2-export',
        name: 'chamilo_core_exercise_qti2_export_zip',
        requirements: ['exerciseId' => '\\d+'],
        methods: ['GET']
    )]
    public function __invoke(int $exerciseId, Request $request): BinaryFileResponse
    {
        return $this->exportService->exportExerciseZip($exerciseId, $request);
    }
}

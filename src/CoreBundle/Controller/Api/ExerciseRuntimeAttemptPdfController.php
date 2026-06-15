<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Service\Exercise\ExerciseRuntimeAttemptPdfService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ExerciseRuntimeAttemptPdfController extends AbstractController
{
    public function __construct(
        private readonly ExerciseRuntimeAttemptPdfService $pdfService,
    ) {}

    #[Route(
        '/api/exercise/runtime/{exerciseId}/attempt/{attemptId}/pdf',
        name: 'chamilo_core_exercise_runtime_attempt_pdf',
        requirements: ['exerciseId' => '\\d+', 'attemptId' => '\\d+'],
        methods: ['GET']
    )]
    public function __invoke(int $exerciseId, int $attemptId, Request $request): Response
    {
        return $this->pdfService->exportAttemptPdf($exerciseId, $attemptId, $request);
    }
}

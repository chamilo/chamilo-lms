<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Service\Exercise\ExerciseQuestionReportPdfService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ExerciseQuestionReportPdfController extends AbstractController
{
    public function __construct(
        private readonly ExerciseQuestionReportPdfService $pdfService,
    ) {}

    #[Route(
        '/api/exercise/runtime/{exerciseId}/report-by-question.pdf',
        name: 'chamilo_core_exercise_report_by_question_pdf',
        requirements: ['exerciseId' => '\\d+'],
        methods: ['GET']
    )]
    public function reportByQuestion(int $exerciseId): Response
    {
        return $this->pdfService->exportReportByQuestionPdf($exerciseId);
    }

    #[Route(
        '/api/exercise/runtime/{exerciseId}/question-stats.pdf',
        name: 'chamilo_core_exercise_question_stats_pdf',
        requirements: ['exerciseId' => '\\d+'],
        methods: ['GET']
    )]
    public function questionStats(int $exerciseId): Response
    {
        return $this->pdfService->exportQuestionStatsPdf($exerciseId);
    }
}

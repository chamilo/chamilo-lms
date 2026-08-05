<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\State\CourseProgress\CourseProgressPdfManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class CourseProgressPdfController extends AbstractController
{
    public function __construct(
        private readonly CourseProgressPdfManager $pdfManager,
    ) {}

    #[Route('/api/course-progress/export.pdf', name: 'api_course_progress_export_pdf', methods: ['GET'])]
    public function export(Request $request): Response
    {
        return $this->pdfManager->export($request);
    }

    #[Route(
        '/api/course-progress/thematic/{thematicId}/export.pdf',
        name: 'api_course_progress_export_thematic_pdf',
        requirements: ['thematicId' => '\d+'],
        methods: ['GET'],
    )]
    public function exportThematic(int $thematicId, Request $request): Response
    {
        return $this->pdfManager->export($request, $thematicId);
    }
}

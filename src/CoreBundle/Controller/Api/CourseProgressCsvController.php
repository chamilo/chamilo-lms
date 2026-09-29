<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\State\CourseProgress\CourseProgressCsvManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class CourseProgressCsvController extends AbstractController
{
    public function __construct(
        private readonly CourseProgressCsvManager $csvManager,
    ) {}

    #[Route('/api/course-progress/export.csv', name: 'api_course_progress_export_csv', methods: ['GET'])]
    public function export(Request $request): StreamedResponse
    {
        return $this->csvManager->export($request);
    }

    #[Route('/api/course-progress/import.csv', name: 'api_course_progress_import_csv', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        return $this->json($this->csvManager->import($request));
    }
}

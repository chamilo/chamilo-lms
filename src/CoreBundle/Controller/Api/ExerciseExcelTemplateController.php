<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Service\Exercise\ExerciseExcelTemplateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ExerciseExcelTemplateController extends AbstractController
{
    public function __construct(
        private readonly ExerciseExcelTemplateService $templateService,
    ) {}

    #[Route(
        '/api/exercise/import/excel/template.xlsx',
        name: 'chamilo_core_exercise_excel_import_template',
        methods: ['GET']
    )]
    public function __invoke(Request $request): BinaryFileResponse
    {
        return $this->templateService->downloadTemplate($request);
    }
}

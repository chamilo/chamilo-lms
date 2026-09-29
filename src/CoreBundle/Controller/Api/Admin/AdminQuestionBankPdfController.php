<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api\Admin;

use Chamilo\CoreBundle\Service\Admin\AdminQuestionBankPdfService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_QUESTION_MANAGER')"))]
final class AdminQuestionBankPdfController extends AbstractController
{
    #[Route('/api/admin/questions/export.pdf', name: 'api_admin_question_bank_export_pdf', methods: ['GET'])]
    public function __invoke(Request $request, AdminQuestionBankPdfService $pdfService): Response
    {
        return $pdfService->export($request);
    }
}

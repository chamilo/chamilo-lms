<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\State\Exercise\ExercisePendingAttemptsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ExercisePendingAttemptsExportController extends AbstractController
{
    public function __construct(
        private readonly ExercisePendingAttemptsProvider $provider,
    ) {}

    #[Route(
        '/api/exercise/pending-attempts/export.csv',
        name: 'chamilo_core_exercise_pending_attempts_export_csv',
        methods: ['GET']
    )]
    public function csv(Request $request): StreamedResponse
    {
        $data = $this->provider->buildData($request);
        $exportData = [
            'settings' => $data->settings,
            'items' => $data->items,
        ];
        $headers = $this->provider->getCsvHeaders($exportData);
        $rows = $this->provider->getCsvRows($exportData);

        $response = new StreamedResponse(static function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');
            if (!\is_resource($handle)) {
                return;
            }

            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'pending_exercise_attempts.csv')
        );

        return $response;
    }
}

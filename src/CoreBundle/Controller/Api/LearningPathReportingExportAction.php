<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathReporting;
use Chamilo\CoreBundle\Component\Mpdf\SafeMpdfHttpClient;
use Chamilo\CoreBundle\State\LearningPath\LearningPathReportingProvider;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_USER')]
final class LearningPathReportingExportAction extends AbstractController
{
    public function __construct(
        private readonly LearningPathReportingProvider $reportingProvider,
        private readonly TranslatorInterface $translator,
        #[Autowire('%kernel.cache_dir%')]
        private readonly string $cacheDir,
    ) {}

    #[Route(
        '/api/learning_paths/{lpId}/reporting.pdf',
        name: 'api_learning_path_reporting_pdf',
        requirements: ['lpId' => '\\d+'],
        methods: ['GET'],
    )]
    public function __invoke(int $lpId): Response
    {
        $report = $this->reportingProvider->provide(new Get(), ['lpId' => $lpId]);
        $html = $this->buildHtml($report);

        $tempDir = $this->cacheDir.'/mpdf';
        if (!is_dir($tempDir) && !mkdir($tempDir, 0775, true) && !is_dir($tempDir)) {
            throw new RuntimeException('Failed to create the Mpdf temporary directory.');
        }

        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        try {
            $mpdf = new Mpdf([
                'format' => 'A4',
                'orientation' => 'L',
                'tempDir' => $tempDir,
            ], SafeMpdfHttpClient::container());
            $mpdf->WriteHTML($html);
            $pdfBinary = $mpdf->Output('', Destination::STRING_RETURN);
        } catch (MpdfException $exception) {
            throw new RuntimeException('Failed to generate the learning path report PDF.', 0, $exception);
        }

        $filename = 'learning-path-report-'.$lpId.'-'.date('Ymd-His').'.pdf';
        $disposition = HeaderUtils::makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename,
        );

        return new Response(
            $pdfBinary,
            Response::HTTP_OK,
            [
                'Cache-Control' => 'private, no-store, max-age=0',
                'Content-Disposition' => $disposition,
                'Content-Type' => 'application/pdf',
            ],
        );
    }

    private function buildHtml(LearningPathReporting $report): string
    {
        $headers = [
            $this->translator->trans('First name'),
            $this->translator->trans('Last name'),
        ];
        if ($report->showEmail) {
            $headers[] = $this->translator->trans('Email');
        }
        $headers[] = $report->allowUserGroups
            ? $this->translator->trans('Groups').' / '.$this->translator->trans('Classes')
            : $this->translator->trans('Groups');
        if (!$report->hideTime) {
            $headers[] = $this->translator->trans('Time');
        }
        $headers[] = $this->translator->trans('Progress');
        $headers[] = $this->translator->trans('Score');
        $headers[] = $this->translator->trans('Last connection');

        $html = '<style>
            @page { margin: 12mm; }
            body { color: #222; font-family: sans-serif; font-size: 10px; }
            h1 { font-size: 18px; margin: 0 0 4px; }
            p { color: #555; margin: 0 0 12px; }
            table { border-collapse: collapse; width: 100%; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
            th, td { border: 1px solid #777; padding: 5px; text-align: left; vertical-align: top; }
            th { background: #eee; font-weight: bold; }
            td.numeric { text-align: right; white-space: nowrap; }
        </style>';
        $html .= '<h1>'.$this->escape($this->translator->trans('Learner score')).'</h1>';
        $html .= '<p>'.$this->escape($report->courseTitle).' — '.$this->escape($report->lpTitle).'</p>';
        $html .= '<table><thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>'.$this->escape($header).'</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($report->learners as $learner) {
            $html .= '<tr>';
            $html .= '<td>'.$this->escape((string) ($learner['firstname'] ?? '')).'</td>';
            $html .= '<td>'.$this->escape((string) ($learner['lastname'] ?? '')).'</td>';
            if ($report->showEmail) {
                $html .= '<td>'.$this->escape((string) ($learner['email'] ?? '')).'</td>';
            }
            $names = array_merge(
                $this->normalizeStringList($learner['groups'] ?? []),
                $this->normalizeStringList($learner['classes'] ?? []),
            );
            $html .= '<td>'.$this->escape(implode(', ', $names)).'</td>';
            if (!$report->hideTime) {
                $html .= '<td class="numeric">'.$this->formatDuration((int) ($learner['timeSeconds'] ?? 0)).'</td>';
            }
            $html .= '<td class="numeric">'.$this->formatPercentage($learner['progress'] ?? 0).'</td>';
            $html .= '<td class="numeric">'.$this->formatPercentage($learner['score'] ?? null).'</td>';
            $html .= '<td>'.$this->formatDate($learner['lastConnection'] ?? null).'</td>';
            $html .= '</tr>';
        }

        if ([] === $report->learners) {
            $html .= '<tr><td colspan="'.\count($headers).'">'.$this->escape(
                $this->translator->trans('No user added'),
            ).'</td></tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** @return array<int, string> */
    private function normalizeStringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            if (is_scalar($item)) {
                $result[] = (string) $item;
            }
        }

        return $result;
    }

    private function formatDuration(int $totalSeconds): string
    {
        $totalSeconds = max(0, $totalSeconds);
        $hours = intdiv($totalSeconds, 3600);
        $minutes = intdiv($totalSeconds % 3600, 60);
        $seconds = $totalSeconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    private function formatPercentage(mixed $value): string
    {
        if (null === $value || !is_numeric($value)) {
            return '-';
        }

        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.').'%';
    }

    private function formatDate(mixed $timestamp): string
    {
        $timestamp = (int) $timestamp;
        if ($timestamp <= 0) {
            return '-';
        }

        return date('Y-m-d H:i', $timestamp);
    }
}

<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookEvaluationResults;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookLearnerReport;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookReport;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DOMDocument;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpWord\PhpWord;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Contracts\Translation\TranslatorInterface;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const FILTER_VALIDATE_BOOL;

final readonly class GradebookExportService
{
    public function __construct(
        private TranslatorInterface $translator,
        private SettingsManager $settingsManager,
    ) {}

    public function createFlatResponse(GradebookReport $report, string $format, string $courseTitle): Response
    {
        [$headers, $rows] = $this->buildFlatTable($report);
        $baseName = $this->safeFileName('gradebook-results-'.$courseTitle);

        return match ($format) {
            'csv' => $this->createCsvResponse($headers, $rows, $baseName.'.csv'),
            'xls' => $this->createXlsResponse($headers, $rows, $baseName.'.xls'),
            'docx' => $this->createDocxResponse($headers, $rows, $baseName.'.docx', $courseTitle),
            'pdf' => $this->createPdfTableResponse(
                $headers,
                $rows,
                $baseName.'.pdf',
                $this->translator->trans('List view').' - '.$courseTitle,
                'L',
            ),
            default => throw new RuntimeException('Unsupported Gradebook flat export format.'),
        };
    }

    public function createEvaluationResponse(
        GradebookEvaluationResults $results,
        string $format,
        string $courseTitle,
    ): Response {
        [$headers, $rows] = $this->buildEvaluationTable($results);
        $title = (string) ($results->evaluation['title'] ?? $this->translator->trans('Assessment details'));
        $baseName = $this->safeFileName('export-results-'.$title);
        [$pdfHeaders, $pdfRows] = $this->buildEvaluationPrintableTable($results);

        return match ($format) {
            'csv' => $this->createCsvResponse($headers, $rows, $baseName.'.csv'),
            'xml' => $this->createEvaluationXmlResponse($results, $baseName.'.xml'),
            'pdf' => $this->createPdfTableResponse(
                $pdfHeaders,
                $pdfRows,
                $baseName.'.pdf',
                $title.' - '.$courseTitle,
                'P',
                [
                    $this->translator->trans('Score') => $this->formatNumber($results->evaluation['maxScore'] ?? null, $results),
                    $this->translator->trans('Weight') => $this->formatNumber($results->evaluation['weight'] ?? null, $results),
                ],
            ),
            default => throw new RuntimeException('Unsupported Gradebook evaluation export format.'),
        };
    }

    public function createLearnerPdfResponse(GradebookLearnerReport $report, string $courseTitle): Response
    {
        $headers = [
            $this->translator->trans('Assessment'),
            $this->translator->trans('Category'),
            $this->translator->trans('Score average'),
            $this->translator->trans('Result'),
        ];
        if (true === ($report->settings['customScoreDisplay'] ?? false)) {
            $headers[] = $this->translator->trans('Ranking');
        }

        $rows = [];
        foreach ($report->rows as $row) {
            $values = [
                (string) ($row['title'] ?? ''),
                (string) ($row['categoryTitle'] ?? ''),
                $this->formatScorePair($row['averageScore'] ?? null, $row['averageMaxScore'] ?? null, null, $report->settings),
                $this->formatScorePair($row['score'] ?? null, $row['maxScore'] ?? null, $row['percentage'] ?? null, $report->settings),
            ];
            if (true === ($report->settings['customScoreDisplay'] ?? false)) {
                $values[] = (string) ($row['ranking'] ?? '');
            }
            $rows[] = $values;
        }

        $learnerName = (string) ($report->learner['fullName'] ?? $this->translator->trans('Learner'));
        $baseName = $this->safeFileName('gradebook-'.$learnerName);
        $summary = [
            $this->translator->trans('Learner') => $learnerName,
            $this->translator->trans('Username') => (string) ($report->learner['username'] ?? ''),
            $this->translator->trans('Official code') => (string) ($report->learner['officialCode'] ?? ''),
        ];
        if (true === ($report->settings['showEmailAddresses'] ?? false) && '' !== (string) ($report->learner['email'] ?? '')) {
            $summary[$this->translator->trans('Email')] = (string) $report->learner['email'];
        }
        $summary[$this->translator->trans('Course')] = $courseTitle;
        $summary[$this->translator->trans('Result')] = $this->formatTotal($report->total, $report->settings);
        if ('' !== trim($report->comment)) {
            $summary[$this->translator->trans('Comment')] = $report->comment;
        }

        return $this->createPdfTableResponse(
            $headers,
            $rows,
            $baseName.'.pdf',
            $this->translator->trans('Results and feedback per user'),
            'P',
            $summary,
            $this->showPdfFeedbackArea(),
        );
    }

    /**
     * @param list<GradebookLearnerReport> $reports
     */
    public function createStudentsPdfResponse(array $reports, string $courseTitle, string $categoryTitle): Response
    {
        $html = '<html><head><meta charset="UTF-8"><style>'.$this->pdfCss().'</style></head><body>';
        foreach ($reports as $index => $report) {
            if (!$report instanceof GradebookLearnerReport) {
                continue;
            }
            if ($index > 0) {
                $html .= '<pagebreak />';
            }
            $html .= '<h1>'.$this->escape($this->translator->trans('Grades from course: %s', ['%s' => $courseTitle])).'</h1>';
            $html .= '<h2>'.$this->escape((string) ($report->learner['fullName'] ?? '')).'</h2>';
            $html .= '<p>'.$this->escape($categoryTitle).'</p>';
            $html .= '<table class="summary"><tbody>';
            $html .= $this->htmlSummaryRow($this->translator->trans('Username'), (string) ($report->learner['username'] ?? ''));
            $html .= $this->htmlSummaryRow($this->translator->trans('Official code'), (string) ($report->learner['officialCode'] ?? ''));
            $html .= $this->htmlSummaryRow($this->translator->trans('Result'), $this->formatTotal($report->total, $report->settings));
            if ('' !== trim($report->comment)) {
                $html .= $this->htmlSummaryRow($this->translator->trans('Comment'), $report->comment);
            }
            $html .= '</tbody></table>';
            $html .= '<table class="data"><thead><tr>';
            foreach ([
                $this->translator->trans('Assessment'),
                $this->translator->trans('Category'),
                $this->translator->trans('Result'),
            ] as $header) {
                $html .= '<th>'.$this->escape($header).'</th>';
            }
            $html .= '</tr></thead><tbody>';
            foreach ($report->rows as $row) {
                $html .= '<tr>';
                $html .= '<td>'.$this->escape((string) ($row['title'] ?? '')).'</td>';
                $html .= '<td>'.$this->escape((string) ($row['categoryTitle'] ?? '')).'</td>';
                $html .= '<td>'.$this->escape($this->formatScorePair(
                    $row['score'] ?? null,
                    $row['maxScore'] ?? null,
                    $row['percentage'] ?? null,
                    $report->settings,
                )).'</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
            if ($this->showPdfFeedbackArea()) {
                $html .= $this->feedbackAreaHtml();
            }
        }
        $html .= '</body></html>';

        return $this->createPdfResponse(
            $html,
            $this->safeFileName('gradebook-all-'.$courseTitle).'.pdf',
            $this->translator->trans('Grades from course: %s', ['%s' => $courseTitle]),
            'P',
        );
    }

    /**
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    private function buildFlatTable(GradebookReport $report): array
    {
        $headers = [
            $this->translator->trans('First name'),
            $this->translator->trans('Last name'),
            $this->translator->trans('Username'),
            $this->translator->trans('Official code'),
        ];
        foreach ($report->extraFieldColumns as $field) {
            $headers[] = (string) ($field['label'] ?? $field['variable'] ?? '');
        }
        foreach ($report->columns as $column) {
            $headers[] = (string) ($column['title'] ?? '');
        }
        $headers[] = $this->translator->trans('Total');
        if (true === ($report->settings['customScoreStandalone'] ?? false)) {
            $headers[] = $this->translator->trans('Ranking');
        }

        $rows = [];
        foreach ($report->rows as $row) {
            $user = \is_array($row['user'] ?? null) ? $row['user'] : [];
            $values = [
                (string) ($user['firstName'] ?? ''),
                (string) ($user['lastName'] ?? ''),
                (string) ($user['username'] ?? ''),
                (string) ($user['officialCode'] ?? ''),
            ];
            $extraValues = \is_array($row['extraFields'] ?? null) ? $row['extraFields'] : [];
            foreach ($report->extraFieldColumns as $field) {
                $values[] = (string) ($extraValues[(string) ($field['variable'] ?? '')] ?? '');
            }
            $scores = \is_array($row['scores'] ?? null) ? $row['scores'] : [];
            foreach ($report->columns as $column) {
                $score = $scores[(string) ($column['key'] ?? '')] ?? null;
                $values[] = \is_array($score) ? $this->formatScoreResult($score, $report->settings) : '';
            }
            $values[] = \is_array($row['total'] ?? null)
                ? $this->formatTotal($row['total'], $report->settings)
                : '';
            if (true === ($report->settings['customScoreStandalone'] ?? false)) {
                $values[] = (string) ($row['customScore'] ?? '');
            }
            $rows[] = $values;
        }

        return [$headers, $rows];
    }

    /**
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    private function buildEvaluationTable(GradebookEvaluationResults $results): array
    {
        $headers = ['username', 'official_code', 'lastname', 'firstname', 'score', 'date'];
        $rows = [];
        foreach ($results->results as $result) {
            if (null === ($result['resultId'] ?? null)) {
                continue;
            }
            $rows[] = [
                (string) ($result['username'] ?? ''),
                (string) ($result['officialCode'] ?? ''),
                (string) ($result['lastname'] ?? ''),
                (string) ($result['firstname'] ?? ''),
                $this->formatNumber($result['score'] ?? null, $results),
                $this->formatDate((string) ($result['createdAt'] ?? '')),
            ];
        }

        return [$headers, $rows];
    }

    /**
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    private function buildEvaluationPrintableTable(GradebookEvaluationResults $results): array
    {
        $headers = [
            $this->translator->trans('Official code'),
            $this->translator->trans('Username'),
            $this->translator->trans('First name'),
            $this->translator->trans('Last name'),
            $this->translator->trans('Score'),
            $this->translator->trans('Date'),
        ];
        $rows = [];
        foreach ($results->results as $result) {
            $score = null !== ($result['resultId'] ?? null)
                ? $this->formatNumber($result['score'] ?? null, $results)
                : $this->translator->trans('The user did not take the exam.');
            $rows[] = [
                (string) ($result['officialCode'] ?? ''),
                (string) ($result['username'] ?? ''),
                (string) ($result['firstname'] ?? ''),
                (string) ($result['lastname'] ?? ''),
                $score,
                null !== ($result['resultId'] ?? null)
                    ? $this->formatDate((string) ($result['createdAt'] ?? ''))
                    : '',
            ];
        }

        return [$headers, $rows];
    }

    /**
     * @param list<string>       $headers
     * @param list<list<string>> $rows
     */
    private function createCsvResponse(array $headers, array $rows, string $fileName): Response
    {
        $stream = fopen('php://temp', 'w+');
        if (false === $stream) {
            throw new RuntimeException('Failed to create the CSV export stream.');
        }
        fputcsv($stream, array_map(fn (string $value): string => $this->sanitizeSpreadsheetValue($value), $headers), ';');
        foreach ($rows as $row) {
            fputcsv($stream, array_map(fn (string $value): string => $this->sanitizeSpreadsheetValue($value), $row), ';');
        }
        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);
        if (false === $content) {
            throw new RuntimeException('Failed to read the CSV export stream.');
        }

        return $this->downloadResponse($content, 'text/csv; charset=UTF-8', $fileName);
    }

    /**
     * @param list<string>       $headers
     * @param list<list<string>> $rows
     */
    private function createXlsResponse(array $headers, array $rows, string $fileName): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $allRows = [$headers, ...$rows];
        foreach ($allRows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $sheet->setCellValueExplicit(
                    [$columnIndex + 1, $rowIndex + 1],
                    $this->sanitizeSpreadsheetValue((string) $value),
                    DataType::TYPE_STRING,
                );
            }
        }
        $writer = new Xls($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();
        $spreadsheet->disconnectWorksheets();
        if (false === $content) {
            throw new RuntimeException('Failed to generate the XLS export.');
        }

        return $this->downloadResponse($content, 'application/vnd.ms-excel', $fileName);
    }

    /**
     * @param list<string>       $headers
     * @param list<list<string>> $rows
     */
    private function createDocxResponse(
        array $headers,
        array $rows,
        string $fileName,
        string $title,
    ): Response {
        $document = new PhpWord();
        $section = $document->addSection(['orientation' => 'landscape']);
        $section->addTitle($title, 1);
        $table = $section->addTable(['borderSize' => 6, 'cellMargin' => 80]);
        $table->addRow();
        foreach ($headers as $header) {
            $table->addCell()->addText($header);
        }
        foreach ($rows as $row) {
            $table->addRow();
            foreach ($row as $value) {
                $table->addCell()->addText($value);
            }
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'gradebook-docx-');
        if (false === $tempFile) {
            throw new RuntimeException('Failed to create a temporary DOCX export file.');
        }

        try {
            $document->save($tempFile, 'Word2007');
            $content = file_get_contents($tempFile);
        } finally {
            @unlink($tempFile);
        }
        if (false === $content) {
            throw new RuntimeException('Failed to read the generated DOCX export.');
        }

        return $this->downloadResponse(
            $content,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            $fileName,
        );
    }

    /**
     * @param list<string>          $headers
     * @param list<list<string>>    $rows
     * @param array<string, string> $summary
     */
    private function createPdfTableResponse(
        array $headers,
        array $rows,
        string $fileName,
        string $title,
        string $orientation,
        array $summary = [],
        bool $showFeedbackArea = false,
    ): Response {
        $html = '<html><head><meta charset="UTF-8"><style>'.$this->pdfCss().'</style></head><body>';
        $html .= '<h1>'.$this->escape($title).'</h1>';
        if ([] !== $summary) {
            $html .= '<table class="summary"><tbody>';
            foreach ($summary as $label => $value) {
                $html .= $this->htmlSummaryRow($label, $value);
            }
            $html .= '</tbody></table>';
        }
        $html .= '<table class="data"><thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>'.$this->escape($header).'</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $value) {
                $html .= '<td>'.$this->escape($value).'</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        if ($showFeedbackArea) {
            $html .= $this->feedbackAreaHtml();
        }
        $html .= '</body></html>';

        return $this->createPdfResponse($html, $fileName, $title, $orientation);
    }

    private function createPdfResponse(string $html, string $fileName, string $title, string $orientation): Response
    {
        $pdf = new Mpdf([
            'format' => 'A4',
            'mode' => 'utf-8',
            'orientation' => $orientation,
            'tempDir' => sys_get_temp_dir(),
        ]);
        $pdf->SetTitle($title);
        $pdf->WriteHTML($html);

        return $this->downloadResponse(
            $pdf->Output('', Destination::STRING_RETURN),
            'application/pdf',
            $fileName,
        );
    }

    private function createEvaluationXmlResponse(GradebookEvaluationResults $results, string $fileName): Response
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;
        $root = $document->createElement('XMLResults');
        $document->appendChild($root);

        foreach ($results->results as $result) {
            if (null === ($result['resultId'] ?? null)) {
                continue;
            }
            $node = $document->createElement('Result');
            foreach ([
                'username' => (string) ($result['username'] ?? ''),
                'official_code' => (string) ($result['officialCode'] ?? ''),
                'lastname' => (string) ($result['lastname'] ?? ''),
                'firstname' => (string) ($result['firstname'] ?? ''),
                'score' => $this->formatNumber($result['score'] ?? null, $results),
                'date' => $this->formatDate((string) ($result['createdAt'] ?? '')),
            ] as $name => $value) {
                $element = $document->createElement($name);
                $element->appendChild($document->createTextNode($value));
                $node->appendChild($element);
            }
            $root->appendChild($node);
        }

        $content = $document->saveXML();
        if (false === $content) {
            throw new RuntimeException('Failed to generate the XML export.');
        }

        return $this->downloadResponse($content, 'application/xml; charset=UTF-8', $fileName);
    }

    private function showPdfFeedbackArea(): bool
    {
        $settings = $this->settingsManager->getSetting('gradebook.gradebook_pdf_export_settings', true);
        if (!\is_array($settings)) {
            return true;
        }

        return !filter_var($settings['hide_feedback_textarea'] ?? false, FILTER_VALIDATE_BOOL);
    }

    private function feedbackAreaHtml(): string
    {
        return '<div class="feedback"><h2>'.$this->escape($this->translator->trans('Feedback')).'</h2><div class="feedback-box">&nbsp;</div></div>';
    }

    private function downloadResponse(string $content, string $contentType, string $fileName): Response
    {
        $response = new Response($content);
        $response->headers->set('Content-Type', $contentType);
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName),
        );
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function formatScoreResult(array $result, array $settings): string
    {
        if (true !== ($result['hasResult'] ?? false)) {
            return '';
        }

        if (isset($result['display']) && \is_string($result['display']) && '' !== $result['display']) {
            return $result['display'];
        }

        return $this->formatScorePair(
            $result['score'] ?? null,
            $result['maxScore'] ?? null,
            $result['percentage'] ?? null,
            $settings,
        );
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function formatTotal(?array $result, array $settings): string
    {
        if (!\is_array($result) || true !== ($result['hasResult'] ?? false)) {
            return '';
        }
        if (isset($result['display']) && \is_string($result['display']) && '' !== $result['display']) {
            return $result['display'];
        }

        return $this->formatScorePair(
            $result['score'] ?? null,
            $result['maxScore'] ?? null,
            $result['percentage'] ?? null,
            $settings,
        );
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function formatScorePair(mixed $score, mixed $maxScore, mixed $percentage, array $settings): string
    {
        if (null === $score && null === $percentage) {
            return '';
        }

        $decimals = max(0, min(6, (int) ($settings['numberDecimals'] ?? 2)));
        if (null === $score) {
            return $this->number($percentage, $decimals).'%';
        }

        $value = $this->number($score, $decimals);
        if (null !== $maxScore) {
            $value .= ' / '.$this->number($maxScore, $decimals);
        }
        if (null !== $percentage) {
            $value .= ' ('.$this->number($percentage, $decimals).'%)';
        }

        return $value;
    }

    private function formatNumber(mixed $value, GradebookEvaluationResults $results): string
    {
        if (null === $value || '' === $value) {
            return '';
        }

        return $this->number($value, max(0, min(6, (int) ($results->settings['numberDecimals'] ?? 2))));
    }

    private function number(mixed $value, int $decimals): string
    {
        if (!is_numeric($value)) {
            return (string) $value;
        }

        return number_format((float) $value, $decimals, '.', '');
    }

    private function formatDate(string $value): string
    {
        if ('' === trim($value)) {
            return '';
        }

        $timestamp = strtotime($value);
        if (false === $timestamp) {
            return $value;
        }

        return date('d/m/Y H:i', $timestamp);
    }

    private function sanitizeSpreadsheetValue(string $value): string
    {
        $trimmed = ltrim($value);
        if ('' !== $trimmed && \in_array($trimmed[0], ['=', '+', '-', '@'], true)) {
            return "'".$value;
        }

        return $value;
    }

    private function safeFileName(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9._-]+/', '-', trim($value)) ?? 'gradebook';
        $value = trim($value, '-_.');

        return '' !== $value ? $value : 'gradebook';
    }

    private function htmlSummaryRow(string $label, string $value): string
    {
        return '<tr><th>'.$this->escape($label).'</th><td>'.$this->escape($value).'</td></tr>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function pdfCss(): string
    {
        return 'body{font-family:sans-serif;font-size:10pt;color:#222}'
            .'h1{font-size:16pt;margin:0 0 12px}'
            .'h2{font-size:13pt;margin:8px 0}'
            .'table{width:100%;border-collapse:collapse;margin:8px 0 16px}'
            .'th,td{border:1px solid #aaa;padding:5px;vertical-align:top}'
            .'th{background:#f2f2f2;font-weight:bold}'
            .'table.summary{width:100%;table-layout:fixed}'
            .'table.summary th{text-align:left;width:30%}'
            .'.feedback{margin-top:20px;page-break-inside:avoid}'
            .'.feedback h2{font-size:13pt;margin:0 0 8px}'
            .'.feedback-box{height:80px;border:1px solid #aaa}';
    }
}

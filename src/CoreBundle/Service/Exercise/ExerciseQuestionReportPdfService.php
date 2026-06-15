<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseQuestionStats;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseReportByQuestion;
use Chamilo\CoreBundle\State\Exercise\ExerciseQuestionStatsProvider;
use Chamilo\CoreBundle\State\Exercise\ExerciseReportByQuestionProvider;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ExerciseQuestionReportPdfService
{
    public function __construct(
        private ExerciseReportByQuestionProvider $reportByQuestionProvider,
        private ExerciseQuestionStatsProvider $questionStatsProvider,
    ) {}

    public function exportReportByQuestionPdf(int $exerciseId): Response
    {
        if (0 >= $exerciseId) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        $report = $this->reportByQuestionProvider->provide(new Get(), ['exerciseId' => $exerciseId]);
        if (!$report instanceof ExerciseReportByQuestion) {
            throw new NotFoundHttpException('The requested report by question was not found.');
        }

        return $this->createPdfResponse(
            $this->renderReportByQuestionHtml($report),
            sprintf('exercise_%d_report_by_question.pdf', $exerciseId),
            $this->text($report->title) ?: 'Report by question'
        );
    }

    public function exportQuestionStatsPdf(int $exerciseId): Response
    {
        if (0 >= $exerciseId) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        $stats = $this->questionStatsProvider->provide(new Get(), ['exerciseId' => $exerciseId]);
        if (!$stats instanceof ExerciseQuestionStats) {
            throw new NotFoundHttpException('The requested question statistics were not found.');
        }

        return $this->createPdfResponse(
            $this->renderQuestionStatsHtml($stats),
            sprintf('exercise_%d_question_statistics.pdf', $exerciseId),
            $this->text($stats->title) ?: 'Question statistics'
        );
    }

    private function createPdfResponse(string $html, string $fileName, string $title): Response
    {
        $pdf = new Mpdf([
            'format' => 'A4',
            'mode' => 'utf-8',
            'tempDir' => sys_get_temp_dir(),
        ]);
        $pdf->SetTitle($title);
        $pdf->WriteHTML($html);

        $response = new Response($pdf->Output('', 'S'));
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName)
        );

        return $response;
    }

    private function renderReportByQuestionHtml(ExerciseReportByQuestion $report): string
    {
        $summary = $report->summary;
        $html = '<html><head><meta charset="UTF-8"><style>'.$this->getCss().'</style></head><body>';
        $html .= '<h1>'.$this->escape($report->title ?: 'Report by question').'</h1>';
        if ('' !== $this->text($report->description)) {
            $html .= '<div class="description">'.$this->escape($report->description).'</div>';
        }

        $html .= '<table class="summary"><tbody>';
        $html .= $this->summaryRow('Questions', (string) ($summary['totalQuestions'] ?? 0));
        $html .= $this->summaryRow('Configured answers', (string) ($summary['totalAnswers'] ?? 0));
        $html .= $this->summaryRow('Selections', (string) ($summary['totalSelections'] ?? 0));
        $html .= '</tbody></table>';

        foreach ($report->questions as $question) {
            $html .= '<div class="question">';
            $html .= '<h2>'.$this->escape($this->text($question['title'] ?? 'Question')).'</h2>';
            $html .= '<div class="muted">#'.(int) ($question['questionId'] ?? 0).' · '.$this->escape($this->text($question['typeLabel'] ?? 'Question')).' · Score: '.$this->formatNumber($question['maxScore'] ?? 0).'</div>';

            if (!empty($question['usesSpecialCounting']) && empty($question['countingAvailable'])) {
                $html .= '<div class="notice">Detailed answer counting for this question type is not available in the migrated report yet.</div>';
            }

            $html .= '<table><thead><tr>';
            $html .= '<th>Answer</th><th>Correct</th><th>Selected</th><th>Selection %</th>';
            $html .= '</tr></thead><tbody>';

            foreach (($question['answers'] ?? []) as $answer) {
                $selectedCount = $answer['selectedCount'] ?? null;
                $selectedPercentage = $answer['selectedPercentage'] ?? null;
                $html .= '<tr>';
                $html .= '<td>'.$this->escape($this->text($answer['answer'] ?? '-')).'<br><span class="muted">#'.$this->escape($answer['answerId'] ?? 0).' · Score: '.$this->formatNumber($answer['score'] ?? 0).'</span></td>';
                $html .= '<td>'.(!empty($question['usesSpecialCounting']) ? '—' : (!empty($answer['correct']) ? 'Yes' : 'No')).'</td>';
                $html .= '<td>'.(null === $selectedCount ? '—' : (string) (int) $selectedCount).'</td>';
                $html .= '<td>'.(null === $selectedCount ? '—' : $this->formatNumber($selectedPercentage).' %').'</td>';
                $html .= '</tr>';
            }

            if ([] === ($question['answers'] ?? [])) {
                $html .= '<tr><td colspan="4">No answer distribution found</td></tr>';
            }

            $html .= '</tbody></table>';
            $html .= '</div>';
        }

        if ([] === $report->questions) {
            $html .= '<div class="notice">No report by question data found.</div>';
        }

        $html .= '</body></html>';

        return $html;
    }

    private function renderQuestionStatsHtml(ExerciseQuestionStats $stats): string
    {
        $summary = $stats->summary;
        $html = '<html><head><meta charset="UTF-8"><style>'.$this->getCss().'</style></head><body>';
        $html .= '<h1>'.$this->escape($stats->title ?: 'Question statistics').'</h1>';
        if ('' !== $this->text($stats->description)) {
            $html .= '<div class="description">'.$this->escape($stats->description).'</div>';
        }

        $html .= '<table class="summary"><tbody>';
        $html .= $this->summaryRow('Questions', (string) ($summary['totalQuestions'] ?? 0));
        $html .= $this->summaryRow('Answered', (string) ($summary['totalAnswered'] ?? 0));
        $html .= $this->summaryRow('Wrong answers', (string) ($summary['totalWrong'] ?? 0));
        $html .= $this->summaryRow('Wrong %', $this->formatNumber($summary['wrongPercentage'] ?? 0).' %');
        $html .= '</tbody></table>';

        $html .= '<table><thead><tr>';
        $html .= '<th>Question</th><th>Question type</th><th>Answered</th><th>Lowest</th><th>Average</th><th>Highest</th><th>Score</th><th>Wrong / Total</th><th>%</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($stats->questions as $question) {
            $html .= '<tr>';
            $html .= '<td>'.$this->escape($this->text($question['title'] ?? '-')).'<br><span class="muted">#'.(int) ($question['questionId'] ?? 0).'</span></td>';
            $html .= '<td>'.$this->escape($this->text($question['typeLabel'] ?? 'Question')).'</td>';
            $html .= '<td>'.(int) ($question['answeredAttempts'] ?? 0).'</td>';
            $html .= '<td>'.$this->formatNumber($question['lowestScore'] ?? 0).'</td>';
            $html .= '<td>'.$this->formatNumber($question['averageScore'] ?? 0).'</td>';
            $html .= '<td>'.$this->formatNumber($question['highestScore'] ?? 0).'</td>';
            $html .= '<td>'.$this->formatNumber($question['maxScore'] ?? 0).'</td>';
            $html .= '<td>'.(int) ($question['wrongAttempts'] ?? 0).' / '.(int) ($question['answeredAttempts'] ?? 0).'</td>';
            $html .= '<td>'.$this->formatNumber($question['wrongPercentage'] ?? 0).' %</td>';
            $html .= '</tr>';
        }

        if ([] === $stats->questions) {
            $html .= '<tr><td colspan="9">No question statistics found.</td></tr>';
        }

        $html .= '</tbody></table>';
        $html .= '</body></html>';

        return $html;
    }

    private function summaryRow(string $label, string $value): string
    {
        return '<tr><th>'.$this->escape($label).'</th><td>'.$this->escape($value).'</td></tr>';
    }

    private function formatNumber(mixed $value): string
    {
        if (null === $value || '' === $value) {
            return '—';
        }

        $number = (float) $value;
        $formatted = number_format($number, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }

    private function text(mixed $value): string
    {
        return trim(strip_tags(html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    private function escape(mixed $value): string
    {
        return htmlspecialchars($this->text($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function getCss(): string
    {
        return <<<'CSS'
body { font-family: DejaVu Sans, sans-serif; color: #222; font-size: 11px; line-height: 1.4; }
h1 { color: #1f3763; font-size: 20px; margin: 0 0 12px; }
h2 { color: #1f3763; font-size: 14px; margin: 0 0 4px; }
.description { border-left: 3px solid #2b7bb9; padding: 8px 10px; background: #f5f8fb; margin-bottom: 12px; }
.summary { width: 100%; margin-bottom: 16px; border-collapse: collapse; }
.summary th { width: 30%; text-align: left; background: #eef3f8; }
table { width: 100%; border-collapse: collapse; margin: 8px 0 16px; }
th, td { border: 1px solid #d7dee8; padding: 6px; vertical-align: top; }
th { background: #eef3f8; color: #1f3763; font-weight: bold; }
.question { page-break-inside: avoid; margin-bottom: 14px; padding-bottom: 4px; }
.muted { color: #6b7280; font-size: 10px; }
.notice { border: 1px solid #b7d7f0; background: #eef8ff; color: #1f5f93; padding: 8px; margin: 8px 0; }
CSS;
    }
}

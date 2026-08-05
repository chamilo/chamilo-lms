<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Admin;

use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

final readonly class AdminQuestionBankPdfService
{
    public function __construct(
        private AdminQuestionBankManager $manager
    ) {}

    public function export(Request $request): Response
    {
        $data = $this->manager->getData($request, true);
        $pdf = new Mpdf([
            'format' => 'A4',
            'mode' => 'utf-8',
            'tempDir' => sys_get_temp_dir(),
        ]);
        $pdf->SetTitle('Questions');
        $pdf->WriteHTML($this->renderHtml($data));

        $response = new Response($pdf->Output('', 'S'));
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'questions-export-'.date('Y-m-d-His').'.pdf'
            )
        );

        return $response;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderHtml(array $data): string
    {
        $html = '<html><head><meta charset="UTF-8"><style>'.$this->css().'</style></head><body>';
        $html .= '<h1>Questions</h1>';
        $html .= '<div class="summary">'.(int) ($data['totalItems'] ?? 0).' question(s)</div>';

        foreach ((array) ($data['items'] ?? []) as $question) {
            $html .= '<section class="question">';
            $html .= '<h2>#'.(int) ($question['id'] ?? 0).'. '.$this->escape($question['titleText'] ?? 'Question').'</h2>';
            $html .= '<div class="meta">'.$this->escape($question['typeLabel'] ?? 'Question');

            $source = $question['source'] ?? null;
            if (\is_array($source) && '' !== trim((string) ($source['courseCode'] ?? ''))) {
                $html .= ' · Source: '.$this->escape($source['courseCode']);
            }
            $html .= '</div>';

            if ('' !== trim((string) ($question['descriptionHtml'] ?? ''))) {
                $html .= '<div class="description">'.(string) $question['descriptionHtml'].'</div>';
            }

            $answers = (array) ($question['answers'] ?? []);
            if ([] !== $answers) {
                $html .= '<h3>Answers</h3><ul class="answers">';
                foreach ($answers as $answer) {
                    $html .= '<li>'.(string) ($answer['html'] ?? '');
                    if (!empty($answer['correct'])) {
                        $html .= ' <span class="correct">Correct answer</span>';
                    }
                    $html .= ' <span class="score">Score: '.$this->escape($this->formatNumber($answer['score'] ?? 0)).'</span>';
                    $html .= '</li>';
                }
                $html .= '</ul>';
            }

            $references = (array) ($question['exerciseReferences'] ?? []);
            if ([] !== $references) {
                $html .= '<h3>Tests using this question</h3><ul>';
                foreach ($references as $reference) {
                    $label = (string) ($reference['exerciseTitle'] ?? 'Unknown test');
                    $courseCode = trim((string) ($reference['courseCode'] ?? ''));
                    if ('' !== $courseCode) {
                        $label .= ' ['.$courseCode.']';
                    }
                    if (!empty($reference['deleted'])) {
                        $label .= ' (The test has been deleted)';
                    }
                    $html .= '<li>'.$this->escape($label).'</li>';
                }
                $html .= '</ul>';
            } elseif (!empty($question['orphan'])) {
                $html .= '<div class="orphan">Orphan question</div>';
            }

            $html .= '</section>';
        }

        if ([] === ($data['items'] ?? [])) {
            $html .= '<div class="empty">No results found</div>';
        }

        $html .= '</body></html>';

        return $html;
    }

    private function formatNumber(mixed $value): string
    {
        $formatted = number_format((float) $value, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }

    private function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function css(): string
    {
        return <<<'CSS'
body { font-family: DejaVu Sans, sans-serif; color: #20242a; font-size: 10.5px; line-height: 1.45; }
h1 { color: #1f3763; font-size: 21px; margin: 0 0 8px; }
h2 { color: #1f3763; font-size: 14px; margin: 0 0 3px; }
h3 { color: #364152; font-size: 11px; margin: 8px 0 4px; }
.summary { color: #667085; margin-bottom: 12px; }
.question { border: 1px solid #d9e1ea; border-left: 4px solid #2b7bb9; border-radius: 5px; margin: 0 0 12px; padding: 10px 12px; page-break-inside: avoid; }
.meta { color: #667085; font-size: 9px; margin-bottom: 7px; }
.description { background: #f7f9fb; border-radius: 4px; padding: 7px 9px; }
ul { margin: 4px 0 0; padding-left: 18px; }
.answers li { margin-bottom: 4px; }
.correct { background: #eaf7ef; border-radius: 8px; color: #176b38; font-size: 8px; padding: 2px 5px; }
.score { color: #667085; font-size: 8px; }
.orphan { background: #fff4e5; color: #8a4b08; margin-top: 7px; padding: 5px 7px; }
.empty { background: #f7f9fb; border: 1px solid #d9e1ea; padding: 16px; text-align: center; }
mark { background: #fff1a8; border-radius: 3px; padding: 0 2px; }
CSS;
    }
}

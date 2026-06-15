<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeResult;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\State\Exercise\ExerciseRuntimeResultProvider;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ExerciseRuntimeAttemptPdfService
{
    public function __construct(
        private ExerciseRuntimeResultProvider $resultProvider,
        private EntityManagerInterface $entityManager,
    ) {}

    public function exportAttemptPdf(int $exerciseId, int $attemptId, Request $request): Response
    {
        $pdfFile = $this->buildAttemptPdfFile($exerciseId, $attemptId, $request);

        $response = new Response($pdfFile['content']);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $pdfFile['fileName'])
        );

        return $response;
    }

    /**
     * @return array{fileName: string, content: string}
     */
    public function buildAttemptPdfFile(int $exerciseId, int $attemptId, Request $request): array
    {
        if (0 >= $exerciseId || 0 >= $attemptId) {
            throw new BadRequestHttpException('A valid exercise and attempt are required.');
        }

        $result = $this->resultProvider->provide(new Get(), [
            'exerciseId' => $exerciseId,
            'attemptId' => $attemptId,
        ]);
        if (!$result instanceof ExerciseRuntimeResult) {
            throw new NotFoundHttpException('The requested attempt result was not found.');
        }

        $attempt = $this->getAttempt($exerciseId, $attemptId, $request);
        $html = $this->renderHtml($result, $attempt);

        $pdf = new Mpdf([
            'format' => 'A4',
            'mode' => 'utf-8',
            'tempDir' => sys_get_temp_dir(),
        ]);
        $pdf->SetTitle($this->text($result->title));
        $pdf->WriteHTML($html);

        return [
            'fileName' => $this->buildFileName($result, $attempt),
            'content' => $pdf->Output('', 'S'),
        ];
    }

    private function getAttempt(int $exerciseId, int $attemptId, Request $request): TrackEExercise
    {
        $course = $this->getCourse($request);
        $session = $this->getSession($request);

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('attempt', 'user')
            ->from(TrackEExercise::class, 'attempt')
            ->innerJoin('attempt.user', 'user')
            ->andWhere('attempt.exeId = :attemptId')
            ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->setParameter('exerciseId', $exerciseId, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(attempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('attempt.session IS NULL');
        }

        $attempt = $queryBuilder->getQuery()->getOneOrNullResult();
        if (!$attempt instanceof TrackEExercise) {
            throw new NotFoundHttpException('The requested attempt was not found.');
        }

        return $attempt;
    }

    private function getCourse(Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if (0 >= $courseId) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('The requested course was not found.');
        }

        return $course;
    }

    private function getSession(Request $request): ?Session
    {
        $sessionId = $request->query->getInt('sid');
        if (0 >= $sessionId) {
            return null;
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        return $session;
    }

    private function renderHtml(ExerciseRuntimeResult $result, TrackEExercise $attempt): string
    {
        $attemptData = $result->attempt;
        $visibility = $result->visibility;
        $user = $attempt->getUser();
        $course = $attempt->getCourse();
        $session = $attempt->getSession();

        $score = $this->formatScore($attemptData);
        $percentage = isset($attemptData['percentage']) && null !== $attemptData['percentage']
            ? $this->formatNumber((float) $attemptData['percentage']).'%'
            : 'Hidden';

        $html = '<html><head><meta charset="UTF-8"><style>'.$this->getCss().'</style></head><body>';
        $html .= '<h1>'.$this->escape($result->title).'</h1>';
        if ('' !== $this->text($result->description)) {
            $html .= '<div class="description">'.$this->escape($result->description).'</div>';
        }

        $html .= '<table class="summary"><tbody>';
        $html .= $this->summaryRow('Learner', trim($user->getFirstname().' '.$user->getLastname()));
        $html .= $this->summaryRow('Username', $user->getUsername());
        $html .= $this->summaryRow('Course', $course->getTitle());
        if (null !== $session) {
            $html .= $this->summaryRow('Session', '#'.(int) $session->getId());
        }
        $html .= $this->summaryRow('Attempt', '#'.(int) $attempt->getExeId());
        $html .= $this->summaryRow('Status', (string) ($attemptData['status'] ?? $attempt->getStatus()));
        $html .= $this->summaryRow('Started at', $this->formatDateValue($attemptData['startedAt'] ?? null));
        $html .= $this->summaryRow('Completed at', $this->formatDateValue($attemptData['completedAt'] ?? null));
        $html .= $this->summaryRow('Duration', $this->formatDuration((int) ($attemptData['duration'] ?? $attempt->getExeDuration())));
        $html .= $this->summaryRow('Score', $score);
        $html .= $this->summaryRow('Percentage', $percentage);
        $html .= '</tbody></table>';

        if ('' !== $this->text($attemptData['textWhenFinished'] ?? '')) {
            $html .= '<div class="notice">'.$this->escape((string) $attemptData['textWhenFinished']).'</div>';
        }

        if (true !== ($visibility['showQuestionDetails'] ?? false)) {
            $html .= '<div class="notice">Question details are hidden according to the exercise result settings.</div>';
        } elseif ([] === $result->questions) {
            $html .= '<div class="notice">No question details available.</div>';
        } else {
            foreach ($result->questions as $question) {
                $html .= $this->renderQuestion($question);
            }
        }

        $html .= '</body></html>';

        return $html;
    }

    /**
     * @param array<string, mixed> $attemptData
     */
    private function formatScore(array $attemptData): string
    {
        if (!isset($attemptData['score'], $attemptData['maxScore']) || null === $attemptData['score'] || null === $attemptData['maxScore']) {
            return 'Hidden';
        }

        return $this->formatNumber((float) $attemptData['score']).' / '.$this->formatNumber((float) $attemptData['maxScore']);
    }

    /**
     * @param array<string, mixed> $question
     */
    private function renderQuestion(array $question): string
    {
        $title = $this->text($question['title'] ?? 'Question');
        $typeLabel = $this->text($question['typeLabel'] ?? '');
        $position = (int) ($question['position'] ?? 0);
        $score = $this->questionScore($question);

        $html = '<div class="question">';
        $html .= '<h2>'.(0 < $position ? $position.'. ' : '').$this->escape($title).'</h2>';
        if ('' !== $typeLabel) {
            $html .= '<div class="muted">'.$this->escape($typeLabel).'</div>';
        }
        if ('' !== $this->text($question['description'] ?? '')) {
            $html .= '<div class="description small">'.$this->escape((string) $question['description']).'</div>';
        }
        if ('' !== $score) {
            $html .= '<div class="score">'.$this->escape($score).'</div>';
        }
        if (true === ($question['pendingCorrection'] ?? false)) {
            $html .= '<div class="warning">Pending correction</div>';
        }
        if (null !== ($question['feedback'] ?? null) && '' !== $this->text($question['feedback'])) {
            $html .= '<div class="feedback"><strong>Feedback:</strong> '.$this->escape((string) $question['feedback']).'</div>';
        }

        $answer = $question['answer'] ?? [];
        if (\is_array($answer)) {
            $html .= $this->renderAnswer($answer);
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * @param array<string, mixed> $question
     */
    private function questionScore(array $question): string
    {
        if (!isset($question['score'], $question['maxScore']) || null === $question['score'] || null === $question['maxScore']) {
            return '';
        }

        return 'Score: '.$this->formatNumber((float) $question['score']).' / '.$this->formatNumber((float) $question['maxScore']);
    }

    /**
     * @param array<string, mixed> $answer
     */
    private function renderAnswer(array $answer): string
    {
        $kind = (string) ($answer['kind'] ?? '');

        return match ($kind) {
            'choice', 'dropdown' => $this->renderChoiceAnswer($answer),
            'true_false' => $this->renderTrueFalseAnswer($answer),
            'fill_blanks' => $this->renderFillBlankAnswer($answer),
            'matching' => $this->renderMatchingAnswer($answer),
            'draggable' => $this->renderDraggableAnswer($answer),
            'calculated' => $this->renderCalculatedAnswer($answer),
            'hotspot' => $this->renderHotspotAnswer($answer),
            'free_answer', 'annotation', 'upload_answer', 'oral_expression' => $this->renderManualAnswer($answer),
            default => $this->renderGenericAnswer($answer),
        };
    }

    /**
     * @param array<string, mixed> $answer
     */
    private function renderChoiceAnswer(array $answer): string
    {
        $items = $answer['choices'] ?? $answer['options'] ?? [];
        if (!\is_array($items) || [] === $items) {
            return '<p class="muted">No answer data available.</p>';
        }

        $html = '<ul class="answers">';
        foreach ($items as $item) {
            if (!\is_array($item)) {
                continue;
            }

            $badges = [];
            if (true === ($item['selected'] ?? false)) {
                $badges[] = 'Your answer';
            }
            if (true === ($item['correct'] ?? false)) {
                $badges[] = 'Correct answer';
            }

            $html .= '<li>'.$this->escape($item['answer'] ?? '').$this->renderBadges($badges);
            if ('' !== $this->text($item['comment'] ?? '')) {
                $html .= '<div class="comment">'.$this->escape((string) $item['comment']).'</div>';
            }
            $html .= '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * @param array<string, mixed> $answer
     */
    private function renderTrueFalseAnswer(array $answer): string
    {
        $items = $answer['choices'] ?? [];
        if (!\is_array($items) || [] === $items) {
            return '<p class="muted">No answer data available.</p>';
        }

        $html = '<ul class="answers">';
        foreach ($items as $item) {
            if (!\is_array($item)) {
                continue;
            }

            $html .= '<li>'.$this->escape($item['answer'] ?? '');
            if ('' !== $this->text($item['selectedOptionLabel'] ?? '')) {
                $html .= '<div>Your answer: '.$this->escape((string) $item['selectedOptionLabel']).'</div>';
            }
            if ('' !== $this->text($item['correctOptionLabel'] ?? '')) {
                $html .= '<div>Correct answer: '.$this->escape((string) $item['correctOptionLabel']).'</div>';
            }
            if ('' !== $this->text($item['selectedDegreeLabel'] ?? '')) {
                $html .= '<div>Degree of certainty: '.$this->escape((string) $item['selectedDegreeLabel']).'</div>';
            }
            $html .= '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * @param array<string, mixed> $answer
     */
    private function renderFillBlankAnswer(array $answer): string
    {
        $blanks = $answer['blanks'] ?? [];
        if (!\is_array($blanks) || [] === $blanks) {
            return '<p class="muted">No blank answer data available.</p>';
        }

        $html = '<table class="details"><thead><tr><th>Blank</th><th>Your answer</th><th>Correct answer</th></tr></thead><tbody>';
        foreach ($blanks as $blank) {
            if (!\is_array($blank)) {
                continue;
            }

            $html .= '<tr>';
            $html .= '<td>'.$this->escape($blank['position'] ?? '').'</td>';
            $html .= '<td>'.$this->escape($blank['studentAnswer'] ?? 'No answer').'</td>';
            $html .= '<td>'.$this->escape($blank['correctAnswer'] ?? '').'</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * @param array<string, mixed> $answer
     */
    private function renderMatchingAnswer(array $answer): string
    {
        $prompts = $answer['prompts'] ?? [];
        if (!\is_array($prompts) || [] === $prompts) {
            return '<p class="muted">No matching answer data available.</p>';
        }

        $html = '<table class="details"><thead><tr><th>Prompt</th><th>Your answer</th><th>Correct answer</th></tr></thead><tbody>';
        foreach ($prompts as $prompt) {
            if (!\is_array($prompt)) {
                continue;
            }

            $html .= '<tr>';
            $html .= '<td>'.$this->escape($prompt['answer'] ?? '').'</td>';
            $html .= '<td>'.$this->escape($prompt['selectedOptionAnswer'] ?? 'No answer').'</td>';
            $html .= '<td>'.$this->escape($prompt['correctOptionAnswer'] ?? '').'</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * @param array<string, mixed> $answer
     */
    private function renderDraggableAnswer(array $answer): string
    {
        $studentItems = $answer['studentItems'] ?? [];
        $correctItems = $answer['correctItems'] ?? [];
        $html = '<div class="columns">';
        $html .= '<div><h3>Your order</h3>'.$this->renderOrderedItems($studentItems).'</div>';
        if (\is_array($correctItems) && [] !== $correctItems) {
            $html .= '<div><h3>Correct order</h3>'.$this->renderOrderedItems($correctItems).'</div>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * @param mixed $items
     */
    private function renderOrderedItems(mixed $items): string
    {
        if (!\is_array($items) || [] === $items) {
            return '<p class="muted">No answer</p>';
        }

        $html = '<ol>';
        foreach ($items as $item) {
            if (\is_array($item)) {
                $html .= '<li>'.$this->escape($item['answer'] ?? $item['text'] ?? '').'</li>';
            }
        }
        $html .= '</ol>';

        return $html;
    }

    /**
     * @param array<string, mixed> $answer
     */
    private function renderCalculatedAnswer(array $answer): string
    {
        $html = '<div class="answer-block">';
        if ('' !== $this->text($answer['text'] ?? '')) {
            $html .= '<div>'.$this->escape($answer['text']).'</div>';
        }
        $html .= '<div><strong>Your answer:</strong> '.$this->escape($answer['studentAnswer'] ?? 'No answer').'</div>';
        if ('' !== $this->text($answer['expectedAnswer'] ?? '')) {
            $html .= '<div><strong>Expected answer:</strong> '.$this->escape($answer['expectedAnswer']).'</div>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * @param array<string, mixed> $answer
     */
    private function renderHotspotAnswer(array $answer): string
    {
        $html = '<div class="answer-block">';
        if ('' !== $this->text($answer['imageName'] ?? '')) {
            $html .= '<div><strong>Image:</strong> '.$this->escape($answer['imageName']).'</div>';
        }

        $points = $answer['studentPoints'] ?? [];
        if (\is_array($points) && [] !== $points) {
            $html .= '<div><strong>Your points:</strong> '.$this->escape($this->compactList($points)).'</div>';
        }

        $zones = $answer['zones'] ?? [];
        if (\is_array($zones) && [] !== $zones) {
            $html .= '<div><strong>Expected zones:</strong> '.$this->escape($this->compactList($zones)).'</div>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * @param array<string, mixed> $answer
     */
    private function renderManualAnswer(array $answer): string
    {
        $html = '<div class="answer-block">';
        if ('' !== $this->text($answer['studentAnswer'] ?? '')) {
            $html .= '<div><strong>Your answer:</strong><br>'.$this->nl2br($answer['studentAnswer']).'</div>';
        }

        $files = $answer['files'] ?? [];
        if (\is_array($files) && [] !== $files) {
            $html .= '<div><strong>Files:</strong><ul>';
            foreach ($files as $file) {
                if (\is_array($file)) {
                    $html .= '<li>'.$this->escape($file['name'] ?? 'File').'</li>';
                }
            }
            $html .= '</ul></div>';
        }

        if ('' !== $this->text($answer['teacherComment'] ?? '')) {
            $html .= '<div><strong>Teacher comment:</strong><br>'.$this->nl2br($answer['teacherComment']).'</div>';
        }
        if (isset($answer['marks'])) {
            $html .= '<div><strong>Marks:</strong> '.$this->escape($this->formatNumber((float) $answer['marks'])).'</div>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * @param array<string, mixed> $answer
     */
    private function renderGenericAnswer(array $answer): string
    {
        return '<pre class="generic">'.$this->escape(json_encode($answer, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '').'</pre>';
    }

    /**
     * @param array<int, string> $badges
     */
    private function renderBadges(array $badges): string
    {
        if ([] === $badges) {
            return '';
        }

        $html = ' ';
        foreach ($badges as $badge) {
            $html .= '<span class="badge">'.$this->escape($badge).'</span> ';
        }

        return $html;
    }

    /**
     * @param mixed $items
     */
    private function compactList(mixed $items): string
    {
        if (!\is_array($items)) {
            return '';
        }

        $values = [];
        foreach ($items as $item) {
            if (!\is_array($item)) {
                continue;
            }

            $parts = [];
            foreach ($item as $key => $value) {
                if (\is_scalar($value)) {
                    $parts[] = $key.': '.$value;
                }
            }
            if ([] !== $parts) {
                $values[] = implode(', ', $parts);
            }
        }

        return implode(' | ', $values);
    }

    private function summaryRow(string $label, string $value): string
    {
        return '<tr><th>'.$this->escape($label).'</th><td>'.$this->escape('' !== $value ? $value : '-').'</td></tr>';
    }

    private function formatDateValue(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (\is_string($value) && '' !== $value) {
            try {
                return (new \DateTimeImmutable($value))->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                return $value;
            }
        }

        return '';
    }

    private function formatDuration(int $duration): string
    {
        $seconds = max(0, $duration);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if (0 < $hours) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        }

        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function buildFileName(ExerciseRuntimeResult $result, TrackEExercise $attempt): string
    {
        $safeTitle = preg_replace('/[^A-Za-z0-9_-]+/', '-', $this->text($result->title)) ?: 'exercise-attempt';
        $safeTitle = trim($safeTitle, '-');
        if ('' === $safeTitle) {
            $safeTitle = 'exercise-attempt';
        }

        return strtolower($safeTitle).'-attempt-'.(int) $attempt->getExeId().'.pdf';
    }

    private function nl2br(mixed $value): string
    {
        return nl2br($this->escape((string) $value));
    }

    private function escape(mixed $value): string
    {
        return htmlspecialchars($this->text($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function text(mixed $value): string
    {
        if (null === $value) {
            return '';
        }

        $text = \is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        if (false === $text) {
            return '';
        }

        return trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function getCss(): string
    {
        return <<<'CSS'
body { font-family: DejaVu Sans, sans-serif; color: #222; font-size: 11pt; line-height: 1.45; }
h1 { color: #1f4e79; font-size: 20pt; margin: 0 0 10px; }
h2 { color: #1f4e79; font-size: 13pt; margin: 0 0 4px; }
h3 { color: #333; font-size: 11pt; margin: 8px 0 4px; }
.summary, .details { border-collapse: collapse; width: 100%; margin: 12px 0; }
.summary th { width: 30%; background: #f2f5f8; text-align: left; }
.summary th, .summary td, .details th, .details td { border: 1px solid #d9e2ec; padding: 6px 8px; vertical-align: top; }
.details th { background: #f2f5f8; text-align: left; }
.question { page-break-inside: avoid; border: 1px solid #d9e2ec; border-left: 4px solid #1f4e79; padding: 12px; margin: 14px 0; }
.description { color: #555; margin: 8px 0; }
.small { font-size: 10pt; }
.muted { color: #666; font-size: 9.5pt; }
.score { background: #eef7ff; border: 1px solid #cce5ff; color: #1f4e79; display: inline-block; margin: 8px 0; padding: 4px 8px; }
.warning { background: #fff3cd; border: 1px solid #ffeeba; color: #856404; display: inline-block; margin: 8px 0; padding: 4px 8px; }
.notice { background: #eef7ff; border: 1px solid #cce5ff; margin: 12px 0; padding: 8px; }
.feedback, .comment { background: #f8f9fa; border: 1px solid #e9ecef; margin-top: 8px; padding: 6px; }
.answers { margin: 8px 0; padding-left: 18px; }
.answers li { margin-bottom: 8px; }
.badge { background: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 10px; color: #2e7d32; font-size: 8.5pt; padding: 2px 6px; }
.answer-block { border: 1px solid #e9ecef; background: #fbfbfb; padding: 8px; margin: 8px 0; }
.columns { width: 100%; }
.columns div { width: 48%; display: inline-block; vertical-align: top; }
.generic { background: #f8f9fa; border: 1px solid #e9ecef; padding: 8px; white-space: pre-wrap; }
CSS;
    }
}

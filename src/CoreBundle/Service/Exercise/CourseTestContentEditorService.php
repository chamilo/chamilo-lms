<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Service\Document\CourseDocumentContentService;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

/**
 * Teacher-facing write helpers for the HTML description bodies of exercise
 * questions and proposed answers. Titles / short labels stay untouched:
 * question "title" text (CQuizQuestion::$question) and correctness/score
 * metadata are never modified by these methods.
 */
final readonly class CourseTestContentEditorService
{
    private const int MAX_HTML_LENGTH = 2_000_000;

    public function __construct(
        private CourseTestReaderService $testReader,
        private CourseDocumentContentService $documentContentService,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Updates the HTML description of a question (CQuizQuestion::$description).
     * The short question title text is left unchanged.
     *
     * @return array{updated: true, question: array<string, mixed>}
     */
    public function editQuestionDescription(
        CQuiz $quiz,
        int $questionId,
        string $description,
    ): array {
        $resolved = $this->testReader->resolveQuestionWithPosition($quiz, $questionId);
        $question = $resolved['question'];
        $html = $this->sanitizeHtmlDescription($description, allowEmpty: true);

        $question->setDescription($html);
        $this->entityManager->persist($question);
        $this->entityManager->flush();

        return [
            'updated' => true,
            'question' => $this->testReader->normalizeQuestion($question, $resolved['position']),
        ];
    }

    /**
     * Updates the HTML body of a proposed answer (CQuizAnswer::$answer).
     * Score, correctness and feedback comment are left unchanged.
     *
     * @return array{updated: true, answer: array<string, mixed>, question: array<string, mixed>}
     */
    public function editAnswerDescription(
        CQuiz $quiz,
        int $questionId,
        int $answerId,
        string $description,
    ): array {
        $resolved = $this->testReader->resolveQuestionWithPosition($quiz, $questionId);
        $question = $resolved['question'];
        $answer = $this->resolveAnswer($question, $answerId);
        $html = $this->sanitizeHtmlDescription($description, allowEmpty: false);

        $answer->setAnswer($html);
        $this->entityManager->persist($answer);
        $this->entityManager->flush();

        return [
            'updated' => true,
            'question' => $this->testReader->normalizeQuestion($question, $resolved['position']),
            'answer' => $this->testReader->normalizeAnswer($answer),
        ];
    }

    private function resolveAnswer(CQuizQuestion $question, int $answerId): CQuizAnswer
    {
        if ($answerId <= 0) {
            throw new InvalidArgumentException('The answer ID must be a positive integer.');
        }

        foreach ($this->testReader->listAnswers($question) as $answer) {
            if ($answerId === (int) $answer->getIid()) {
                return $answer;
            }
        }

        throw new InvalidArgumentException('The answer was not found for this question.');
    }

    private function sanitizeHtmlDescription(string $description, bool $allowEmpty): string
    {
        $description = trim($description);
        if (mb_strlen($description) > self::MAX_HTML_LENGTH) {
            throw new InvalidArgumentException('The HTML description is too large.');
        }

        if ('' === $description) {
            if ($allowEmpty) {
                return '';
            }

            throw new InvalidArgumentException('The HTML description is required.');
        }

        $sanitized = $this->documentContentService->sanitizeHtml($description);
        if ('' === trim(strip_tags($sanitized))) {
            if ($allowEmpty && '' === trim(strip_tags($description))) {
                return '';
            }

            throw new InvalidArgumentException('The HTML description is empty after sanitization.');
        }

        return $sanitized;
    }
}

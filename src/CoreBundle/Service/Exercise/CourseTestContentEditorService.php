<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Service\Document\CourseDocumentContentService;
use Chamilo\CoreBundle\Service\Html\TranslateHtmlLanguageService;
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
        private TranslateHtmlLanguageService $translateHtmlLanguageService,
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
     * Score, correctness and feedback are left unchanged (see editAnswerFeedback).
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

    /**
     * Updates the HTML feedback comment of a proposed answer (CQuizAnswer::$comment),
     * shown to learners after answering. The answer body, score and correctness are
     * left unchanged. Feedback may be cleared by passing an empty string.
     *
     * @return array{updated: true, answer: array<string, mixed>, question: array<string, mixed>}
     */
    public function editAnswerFeedback(
        CQuiz $quiz,
        int $questionId,
        int $answerId,
        string $feedback,
    ): array {
        $resolved = $this->testReader->resolveQuestionWithPosition($quiz, $questionId);
        $question = $resolved['question'];
        $answer = $this->resolveAnswer($question, $answerId);
        $html = $this->sanitizeHtmlDescription($feedback, allowEmpty: true);

        $answer->setComment($html);
        $this->entityManager->persist($answer);
        $this->entityManager->flush();

        return [
            'updated' => true,
            'question' => $this->testReader->normalizeQuestion($question, $resolved['position']),
            'answer' => $this->testReader->normalizeAnswer($answer),
        ];
    }

    /**
     * @return array{updated: true, action: 'created'|'replaced'}&array<string, mixed>
     */
    public function upsertQuestionDescriptionLanguage(
        Course $course,
        CQuiz $quiz,
        int $questionId,
        string $language,
        string $content,
        string $mode = TranslateHtmlLanguageService::MODE_UPSERT,
        ?string $sourceLanguage = null,
        ?string $ifMatchSha256 = null,
    ): array {
        $resolved = $this->testReader->resolveQuestionWithPosition($quiz, $questionId);
        $question = $resolved['question'];
        $languageIso = $this->testReader->resolveRequiredLanguageIsoCode($language);
        $sourceLanguageIso = $this->testReader->resolveSourceLanguageIsoCode($course, $sourceLanguage);

        $result = $this->translateHtmlLanguageService->upsertLanguageSanitized(
            (string) $question->getDescription(),
            $languageIso,
            $content,
            $mode,
            $sourceLanguageIso,
            $ifMatchSha256,
            fn (string $html): string => $this->sanitizeHtmlDescription($html, allowEmpty: true),
        );

        $question->setDescription($result['html']);
        $this->entityManager->persist($question);
        $this->entityManager->flush();

        return [
            'updated' => true,
            'quiz_id' => (int) $quiz->getIid(),
            'question_id' => (int) $question->getIid(),
            'position' => $resolved['position'],
            'action' => $result['action'],
            'language' => $result['language'],
            'present_languages' => $result['present_languages'],
            'content_sha256' => $result['content_sha256'],
            'chars' => $result['chars'],
            'words' => $result['words'],
            'has_markers' => $result['has_markers'],
            'per_language' => $result['per_language'],
        ];
    }

    /**
     * @return array{updated: true, action: 'created'|'replaced'}&array<string, mixed>
     */
    public function upsertAnswerDescriptionLanguage(
        Course $course,
        CQuiz $quiz,
        int $questionId,
        int $answerId,
        string $language,
        string $content,
        string $mode = TranslateHtmlLanguageService::MODE_UPSERT,
        ?string $sourceLanguage = null,
        ?string $ifMatchSha256 = null,
    ): array {
        $resolved = $this->testReader->resolveQuestionWithPosition($quiz, $questionId);
        $question = $resolved['question'];
        $answer = $this->resolveAnswer($question, $answerId);
        $languageIso = $this->testReader->resolveRequiredLanguageIsoCode($language);
        $sourceLanguageIso = $this->testReader->resolveSourceLanguageIsoCode($course, $sourceLanguage);

        $result = $this->translateHtmlLanguageService->upsertLanguageSanitized(
            (string) $answer->getAnswer(),
            $languageIso,
            $content,
            $mode,
            $sourceLanguageIso,
            $ifMatchSha256,
            fn (string $html): string => $this->sanitizeHtmlDescription($html, allowEmpty: false),
        );

        $answer->setAnswer($result['html']);
        $this->entityManager->persist($answer);
        $this->entityManager->flush();

        return [
            'updated' => true,
            'quiz_id' => (int) $quiz->getIid(),
            'question_id' => (int) $question->getIid(),
            'answer_id' => (int) $answer->getIid(),
            'action' => $result['action'],
            'language' => $result['language'],
            'present_languages' => $result['present_languages'],
            'content_sha256' => $result['content_sha256'],
            'chars' => $result['chars'],
            'words' => $result['words'],
            'has_markers' => $result['has_markers'],
            'per_language' => $result['per_language'],
        ];
    }

    /**
     * Add or replace one language variant of an answer's feedback comment
     * (CQuizAnswer::$comment), without rewriting the full multi-language body.
     * The answer body, score and correctness are never touched here.
     *
     * @return array{updated: true, action: 'created'|'replaced'}&array<string, mixed>
     */
    public function upsertAnswerFeedbackLanguage(
        Course $course,
        CQuiz $quiz,
        int $questionId,
        int $answerId,
        string $language,
        string $content,
        string $mode = TranslateHtmlLanguageService::MODE_UPSERT,
        ?string $sourceLanguage = null,
        ?string $ifMatchSha256 = null,
    ): array {
        $resolved = $this->testReader->resolveQuestionWithPosition($quiz, $questionId);
        $question = $resolved['question'];
        $answer = $this->resolveAnswer($question, $answerId);
        $languageIso = $this->testReader->resolveRequiredLanguageIsoCode($language);
        $sourceLanguageIso = $this->testReader->resolveSourceLanguageIsoCode($course, $sourceLanguage);

        $result = $this->translateHtmlLanguageService->upsertLanguageSanitized(
            (string) $answer->getComment(),
            $languageIso,
            $content,
            $mode,
            $sourceLanguageIso,
            $ifMatchSha256,
            fn (string $html): string => $this->sanitizeHtmlDescription($html, allowEmpty: true),
        );

        $answer->setComment($result['html']);
        $this->entityManager->persist($answer);
        $this->entityManager->flush();

        return [
            'updated' => true,
            'quiz_id' => (int) $quiz->getIid(),
            'question_id' => (int) $question->getIid(),
            'answer_id' => (int) $answer->getIid(),
            'action' => $result['action'],
            'language' => $result['language'],
            'present_languages' => $result['present_languages'],
            'content_sha256' => $result['content_sha256'],
            'chars' => $result['chars'],
            'words' => $result['words'],
            'has_markers' => $result['has_markers'],
            'per_language' => $result['per_language'],
        ];
    }

    /**
     * @return array{updated: true, action: 'created'|'replaced'}&array<string, mixed>
     */
    public function upsertTestDescriptionLanguage(
        Course $course,
        CQuiz $quiz,
        string $language,
        string $content,
        string $mode = TranslateHtmlLanguageService::MODE_UPSERT,
        ?string $sourceLanguage = null,
        ?string $ifMatchSha256 = null,
    ): array {
        $languageIso = $this->testReader->resolveRequiredLanguageIsoCode($language);
        $sourceLanguageIso = $this->testReader->resolveSourceLanguageIsoCode($course, $sourceLanguage);

        $result = $this->translateHtmlLanguageService->upsertLanguageSanitized(
            (string) $quiz->getDescription(),
            $languageIso,
            $content,
            $mode,
            $sourceLanguageIso,
            $ifMatchSha256,
            fn (string $html): string => $this->sanitizeHtmlDescription($html, allowEmpty: true),
        );

        $quiz->setDescription($result['html']);
        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        return [
            'updated' => true,
            'quiz_id' => (int) $quiz->getIid(),
            'title' => $quiz->getTitle(),
            'action' => $result['action'],
            'language' => $result['language'],
            'present_languages' => $result['present_languages'],
            'content_sha256' => $result['content_sha256'],
            'chars' => $result['chars'],
            'words' => $result['words'],
            'has_markers' => $result['has_markers'],
            'per_language' => $result['per_language'],
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

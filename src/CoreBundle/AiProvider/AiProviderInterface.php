<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\AiProvider;

interface AiProviderInterface
{
    /**
     * Generates questions using the AI provider's API.
     *
     * @param string $topic        the topic for the questions
     * @param int    $numQuestions number of questions to generate
     * @param string $questionType The type of questions (e.g., "multiple_choice").
     * @param string $language     the language of the questions
     *
     * @return string|null returns the generated questions in Aiken format or null if an error occurs
     */
    public function generateQuestions(string $topic, int $numQuestions, string $questionType, string $language): ?string;

    /**
     * Generates a structured learning path with AI-generated content.
     *
     * @param string $topic         the main subject of the learning path
     * @param int    $chaptersCount number of chapters to generate
     * @param string $language      language for the generated content
     * @param int    $wordsCount    word limit per chapter
     * @param bool   $addTests      whether to include quizzes
     * @param int    $numQuestions  number of quiz questions per chapter
     *
     * @return array|null returns the generated learning path data or null on failure
     */
    public function generateLearnPath(string $topic, int $chaptersCount, string $language, int $wordsCount, bool $addTests, int $numQuestions): ?array;

    /**
     * Grade a single open‑answer.
     *
     * @param string $prompt   The complete prompt, with language, question, context and answer
     * @param string $toolName A tag, e.g. 'open_answer_grade'.
     *
     * @return string|null The raw text of the answer: "X\nFeedback…"
     */
    public function gradeOpenAnswer(string $prompt, string $toolName): ?string;
}

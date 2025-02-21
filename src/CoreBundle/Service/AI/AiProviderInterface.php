<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\AI;

interface AiProviderInterface
{
    /**
     * Generates questions using the AI provider's API.
     *
     * @param string $topic The topic for the questions.
     * @param int $numQuestions Number of questions to generate.
     * @param string $questionType The type of questions (e.g., "multiple_choice").
     * @param string $language The language of the questions.
     *
     * @return string|null Returns the generated questions in Aiken format or null if an error occurs.
     */
    public function generateQuestions(string $topic, int $numQuestions, string $questionType, string $language): ?string;
}

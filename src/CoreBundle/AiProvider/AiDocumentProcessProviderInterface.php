<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\AiProvider;

interface AiDocumentProcessProviderInterface
{
    /**
     * Process a document and return feedback text.
     *
     * @param string $prompt        Prompt that includes context and grading instructions
     * @param string $toolName      Tag, e.g. 'task_grader'
     * @param string $filename      Original file name, e.g. "submission.pdf"
     * @param string $mimeType      e.g. "application/pdf"
     * @param string $binaryContent Raw file bytes (NOT base64)
     * @param array  $options       Optional overrides (model, temperature, max_output_tokens, etc.)
     */
    public function processDocument(
        string $prompt,
        string $toolName,
        string $filename,
        string $mimeType,
        string $binaryContent,
        array $options = []
    ): ?string;
}

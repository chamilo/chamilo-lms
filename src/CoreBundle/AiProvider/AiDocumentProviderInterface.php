<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\AiProvider;

interface AiDocumentProviderInterface
{
    /**
     * Generate a document.
     *
     * @param string $prompt   The complete prompt, with language, question, context and answer
     * @param string $toolName A tag, e.g. 'open_answer_grade'.
     * @param ?array $options  An array of options (format etc.)
     *
     * @return ?string The raw text of the image in base64, or a URL
     */
    public function generateDocument(string $prompt, string $toolName, ?array $options = []): ?string;
}

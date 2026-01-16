<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\AiProvider;

interface AiImageProviderInterface
{
    /**
     * Generate an image.
     *
     * @param string $prompt   The prompt to generate an image from
     * @param string $toolName A tag, e.g. 'document_image_generate'
     * @param ?array $options  Provider-specific options
     *
     * @return string|array|null
     *                           - string: legacy mode (base64 or URL)
     *                           - array: structured response for UI (preferred)
     *                           [
     *                           'content' => string (base64),
     *                           'url' => string,
     *                           'is_base64' => bool,
     *                           'content_type' => string,
     *                           'revised_prompt' => ?string
     *                           ]
     */
    public function generateImage(string $prompt, string $toolName, ?array $options = []): array|string|null;
}

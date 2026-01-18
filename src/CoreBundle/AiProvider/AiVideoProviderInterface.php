<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\AiProvider;

interface AiVideoProviderInterface
{
    /**
     * Generate a video.
     *
     * @param string $prompt   The prompt to generate a video from
     * @param string $toolName A tag, e.g. 'document_video_generate'
     * @param ?array $options  Provider-specific options
     *
     * @return string|array|null
     *                           - string: legacy mode (base64 or URL)
     *                           - array: structured response for UI (preferred)
     */
    public function generateVideo(string $prompt, string $toolName, ?array $options = []): array|string|null;
}

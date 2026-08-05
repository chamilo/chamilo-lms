<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\AiProvider;

interface AiSearchMediaTextProviderInterface
{
    /**
     * Extract searchable plain text from a media file.
     *
     * Implementations may transcribe audio/video or describe images.
     *
     * @param array<string,mixed> $options
     */
    public function extractSearchableMediaText(
        string $filename,
        string $mimeType,
        string $binaryContent,
        string $mediaType,
        array $options = []
    ): ?string;
}

<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\AiProvider;

interface AiVideoJobProviderInterface extends AiVideoProviderInterface
{
    /**
     * Returns basic job info (status + provider raw payload or error).
     *
     * Expected shape:
     * [
     *   'id' => string,
     *   'status' => string,
     *   'error' => ?string,
     *   'job' => mixed|null
     * ]
     */
    public function getVideoJobStatus(string $jobId): ?array;

    /**
     * Returns video content (base64) or a URL when base64 is not available.
     *
     * Expected shape:
     * [
     *   'is_base64' => bool,
     *   'content' => ?string,
     *   'url' => ?string,
     *   'content_type' => string,
     *   'error' => ?string
     * ]
     */
    public function getVideoJobContentAsBase64(string $jobId, int $maxBytes = 15728640): ?array;
}

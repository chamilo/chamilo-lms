<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\AiProvider;

interface AiImageProviderInterface
{
    /**
     * Generate an image
     * @param string $prompt   The complete prompt, with language, question, context and answer
     * @param ?array $options  An array of options (format etc.)
     *
     * @return string The raw text of the image in base64, or a URL
     */
    public function generateImage(string $prompt, ?array $options = []): string;

    /**
     * Generate a video
     * @param string $prompt   The complete prompt, with language, question, context and answer
     * @param ?array $options  An array of options (format etc.)
     *
     * @return string The raw text of the video in base64, or a URL
     */
    public function generateVideo(string $prompt, ?array $options = []): string;


}

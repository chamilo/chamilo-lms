<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Assignment;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * HTML produced by the online assignment editor must be safe both in the
 * persisted file and in the description returned to review interfaces.
 */
final readonly class AssignmentHtmlSubmission
{
    private const int MAX_DESCRIPTION_BYTES = 60000;

    public function sanitizeFragment(string $html): string
    {
        $sanitizer = new HtmlSanitizer(
            (new HtmlSanitizerConfig())
                ->allowSafeElements()
                ->allowRelativeLinks()
                ->allowRelativeMedias(),
        );

        return $sanitizer->sanitize($html);
    }

    /**
     * @return array{fileName: string, description: string, document: string}
     */
    public function prepare(string $title, string $html): array
    {
        if ('' === trim($title)) {
            throw new UnprocessableEntityHttpException('A submission title is required.');
        }

        if ('' === trim($html) || \strlen($html) > self::MAX_DESCRIPTION_BYTES) {
            throw new UnprocessableEntityHttpException('Submission HTML is empty or too large.');
        }

        $description = $this->sanitizeFragment($html);
        if ('' === trim($description) || \strlen($description) > self::MAX_DESCRIPTION_BYTES) {
            throw new UnprocessableEntityHttpException('Submission HTML is empty or too large after sanitization.');
        }

        $title = preg_replace('/\.html?$/i', '', trim($title));
        $fileName = preg_replace('/[^\pL\pN _-]+/u', '_', $title);
        $fileName = trim((string) $fileName, ' _-');
        $fileName = mb_substr($fileName, 0, 120);
        if ('' === $fileName) {
            throw new UnprocessableEntityHttpException('A valid submission title is required.');
        }

        return [
            'fileName' => $fileName.'.html',
            'description' => $description,
            'document' => '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'.$description.'</body></html>',
        ];
    }
}

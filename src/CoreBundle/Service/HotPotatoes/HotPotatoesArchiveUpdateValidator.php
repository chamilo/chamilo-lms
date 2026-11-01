<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\HotPotatoes;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/** Only course-facing metadata can be changed; archive contents are immutable. */
final class HotPotatoesArchiveUpdateValidator
{
    /** @param array<string, mixed> $payload
     * @return array{title?: string, published?: bool}
     */
    public function validate(array $payload): array
    {
        if ([] === $payload || array_diff(array_keys($payload), ['title', 'published'])) {
            throw new BadRequestHttpException('Only title and published may be updated.');
        }

        $validated = [];
        if (\array_key_exists('title', $payload)) {
            $title = $payload['title'];
            if (!\is_string($title)) {
                throw new BadRequestHttpException('The title must be text.');
            }
            $title = trim($title);
            if ('' === $title || \strlen($title) > 255 || preg_match('/[\x00-\x1f\x7f<>]/', $title)) {
                throw new BadRequestHttpException('The title must contain 1–255 safe bytes.');
            }
            $validated['title'] = $title;
        }

        if (\array_key_exists('published', $payload)) {
            if (!\is_bool($payload['published'])) {
                throw new BadRequestHttpException('The published flag must be boolean.');
            }
            $validated['published'] = $payload['published'];
        }

        return $validated;
    }
}

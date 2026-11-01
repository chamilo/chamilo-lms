<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\HotPotatoes;

use Chamilo\CourseBundle\Entity\CDocument;

/**
 * Enumerates the HTML entry points verified during package import.
 * The archive is the physical resource; LP item refs select a page within it.
 */
final class HotPotatoesEntrypoints
{
    /**
     * @return list<string>
     */
    public static function forDocument(CDocument $document): array
    {
        $node = $document->getResourceNode();
        if (null === $node) {
            return [];
        }

        foreach ($node->getResourceFiles() as $file) {
            $entrypoints = $file->getMetadata()['chamilo_hotpotatoes']['entrypoints'] ?? null;
            if (!\is_array($entrypoints)) {
                continue;
            }

            $result = [];
            foreach ($entrypoints as $path) {
                if (!\is_string($path) || !self::isValidPath($path)) {
                    continue;
                }
                $result[$path] = $path;
            }

            return array_values($result);
        }

        return [];
    }

    private static function isValidPath(string $path): bool
    {
        if ('' === $path || \strlen($path) > 512
            || str_contains($path, '\\') || str_contains($path, '%')
            || str_contains($path, ':') || str_contains($path, '?') || str_contains($path, '#')
            || preg_match('/[\x00-\x1f\x7f]/', $path)
            || !preg_match('/\.html?$/iD', $path)
        ) {
            return false;
        }

        foreach (explode('/', $path) as $part) {
            if ('' === $part || '.' === $part || '..' === $part || str_starts_with($part, '.')) {
                return false;
            }
        }

        return true;
    }
}

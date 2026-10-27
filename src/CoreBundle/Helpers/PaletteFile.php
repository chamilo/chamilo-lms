<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use InvalidArgumentException;

/**
 * Parse the four-column pChart .color format without accepting arbitrary file contents.
 */
final class PaletteFile
{
    /**
     * @return list<array{int, int, int, int}> R, G, B (0..255), Alpha (0..100).
     */
    public static function parse(string $contents): array
    {
        if ('' === trim($contents) || \strlen($contents) > 16384) {
            throw new InvalidArgumentException('A palette must contain between 1 and 256 colors (16 KB maximum).');
        }

        $colors = [];
        foreach (preg_split('/\r\n|\r|\n/', $contents) ?: [] as $line) {
            $line = trim($line);
            if ('' === $line) {
                continue;
            }
            if (!preg_match('/^([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})$/D', $line, $parts)) {
                throw new InvalidArgumentException('Each palette line must contain R,G,B,Alpha integer values.');
            }

            $values = array_map('intval', \array_slice($parts, 1));
            if ($values[0] > 255 || $values[1] > 255 || $values[2] > 255 || $values[3] > 100) {
                throw new InvalidArgumentException('Palette color components are outside their allowed ranges.');
            }
            $colors[] = $values;

            if (\count($colors) > 256) {
                throw new InvalidArgumentException('A palette cannot contain more than 256 colors.');
            }
        }

        if ([] === $colors) {
            throw new InvalidArgumentException('A palette must contain at least one color.');
        }

        return $colors;
    }
}

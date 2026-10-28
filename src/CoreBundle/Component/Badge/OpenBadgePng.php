<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Component\Badge;

use InvalidArgumentException;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class OpenBadgePng
{
    public static function bake(string $png, array $assertion): string
    {
        $json = json_encode($assertion, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $chunks = self::chunks($png);
        $output = substr($png, 0, 8);
        foreach ($chunks as [$type, $data, $raw]) {
            if (\in_array($type, ['tEXt', 'iTXt', 'zTXt'], true)
                && 'openbadges' === explode("\0", $data, 2)[0]
            ) {
                continue;
            }
            if ('IEND' === $type) {
                $payload = "openbadges\0\0\0\0\0".$json;
                $output .= pack('N', \strlen($payload)).'iTXt'.$payload.pack('N', crc32('iTXt'.$payload));
            }
            $output .= $raw;
        }

        return $output;
    }

    public static function validate(string $png): void
    {
        self::chunks($png);
    }

    private static function chunks(string $png): array
    {
        if ("\x89PNG\r\n\x1a\n" !== substr($png, 0, 8)) {
            throw new InvalidArgumentException('The badge image must be a PNG.');
        }
        $size = \strlen($png);
        $offset = 8;
        $chunks = [];
        $hasImageData = false;
        while ($offset < $size) {
            if ($size - $offset < 12) {
                throw new InvalidArgumentException('Truncated PNG chunk.');
            }
            $length = unpack('Nlength', substr($png, $offset, 4))['length'];
            if ($length > $size - $offset - 12) {
                throw new InvalidArgumentException('Invalid PNG chunk length.');
            }
            $type = substr($png, $offset + 4, 4);
            $data = substr($png, $offset + 8, $length);
            if (pack('N', crc32($type.$data)) !== substr($png, $offset + 8 + $length, 4)) {
                throw new InvalidArgumentException('Invalid PNG checksum.');
            }
            if ([] === $chunks && ('IHDR' !== $type || 13 !== $length)) {
                throw new InvalidArgumentException('Invalid PNG header.');
            }
            $chunks[] = [$type, $data, substr($png, $offset, $length + 12)];
            $offset += $length + 12;
            $hasImageData = $hasImageData || 'IDAT' === $type;
            if ('IEND' === $type) {
                if (0 !== $length || $offset !== $size || !$hasImageData) {
                    throw new InvalidArgumentException('Invalid PNG end chunk.');
                }

                return $chunks;
            }
        }

        throw new InvalidArgumentException('Missing PNG end chunk.');
    }
}

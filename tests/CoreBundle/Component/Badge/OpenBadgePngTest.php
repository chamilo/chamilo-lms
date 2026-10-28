<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Component\Badge;

use Chamilo\CoreBundle\Component\Badge\OpenBadgePng;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use const JSON_THROW_ON_ERROR;

final class OpenBadgePngTest extends TestCase
{
    public function testPortableAssertionAndPixelsSurviveBaking(): void
    {
        $assertion = ['type' => 'Assertion', 'id' => 'https://example.org/assertions/7', 'name' => 'Compétence'];
        $png = self::png();
        $baked = OpenBadgePng::bake($png, $assertion);
        $chunks = self::readChunks($baked);
        $metadata = array_values(array_filter($chunks, static fn (array $chunk): bool => 'iTXt' === $chunk[0]));
        self::assertCount(1, $metadata);
        self::assertStringStartsWith("openbadges\0\0\0\0\0", $metadata[0][1]);
        self::assertSame($assertion, json_decode(substr($metadata[0][1], 15), true, 512, JSON_THROW_ON_ERROR));
        self::assertSame(self::readChunks($png)[1], $chunks[1], 'Baking must preserve compressed pixel data.');
        self::assertSame('IEND', $chunks[array_key_last($chunks)][0]);
    }

    public function testRebakingReplacesEveryOldBadgeChunkAndPreservesOtherText(): void
    {
        $png = substr(self::png(), 0, -12)
            .self::chunk('tEXt', "openbadges\0https://example.org/old")
            .self::chunk('zTXt', "openbadges\0\0".gzcompress('old'))
            .self::chunk('iTXt', "openbadges\0\0\0\0\0{}")
            .self::chunk('tEXt', "Author\0Teacher")
            .self::chunk('IEND', '');
        $baked = OpenBadgePng::bake($png, ['id' => 'https://example.org/new']);
        $chunks = self::readChunks($baked);
        $badges = array_values(array_filter($chunks, static fn (array $chunk): bool => str_starts_with($chunk[1], "openbadges\0")));
        self::assertCount(1, $badges);
        self::assertSame('iTXt', $badges[0][0]);
        self::assertContains(['tEXt', "Author\0Teacher"], $chunks);
        self::assertSame($baked, OpenBadgePng::bake($baked, ['id' => 'https://example.org/new']));
    }

    /**
     * @dataProvider invalidPngProvider
     */
    public function testDamagedImagesAreRejectedInsteadOfExported(string $png): void
    {
        $this->expectException(InvalidArgumentException::class);
        OpenBadgePng::bake($png, ['id' => 'https://example.org/assertions/7']);
    }

    public static function invalidPngProvider(): array
    {
        $png = self::png();
        $corrupt = $png;
        $corrupt[29] = \chr(\ord($corrupt[29]) ^ 1);

        return [
            'not a PNG' => ['<svg/>'],
            'truncated length' => [substr($png, 0, 10)],
            'truncated payload' => [substr($png, 0, 25)],
            'wrong checksum' => [$corrupt],
            'missing end' => [substr($png, 0, -12)],
            'trailing bytes' => [$png.'trailing'],
            'no pixels' => [substr($png, 0, 33).self::chunk('IEND', '')],
            'wrong first chunk' => [substr($png, 0, 8).self::chunk('IDAT', 'x').self::chunk('IEND', '')],
            'oversized chunk' => [substr($png, 0, 8).pack('N', 0x7FFFFFFF).'IDAT'.str_repeat("\0", 4)],
        ];
    }

    private static function png(): string
    {
        return "\x89PNG\r\n\x1a\n"
            .self::chunk('IHDR', pack('NNCCCCC', 1, 1, 8, 6, 0, 0, 0))
            .self::chunk('IDAT', gzcompress("\0\xff\x80\0\xff"))
            .self::chunk('IEND', '');
    }

    private static function chunk(string $type, string $data): string
    {
        return pack('N', \strlen($data)).$type.$data.pack('N', crc32($type.$data));
    }

    private static function readChunks(string $png): array
    {
        $chunks = [];
        for ($offset = 8; $offset < \strlen($png); $offset += $length + 12) {
            $length = unpack('N', substr($png, $offset, 4))[1];
            $type = substr($png, $offset + 4, 4);
            $data = substr($png, $offset + 8, $length);
            self::assertSame(pack('N', crc32($type.$data)), substr($png, $offset + 8 + $length, 4));
            $chunks[] = [$type, $data];
        }

        return $chunks;
    }
}

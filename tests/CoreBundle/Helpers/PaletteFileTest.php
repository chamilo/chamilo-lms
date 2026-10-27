<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Helpers\PaletteFile;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PaletteFileTest extends TestCase
{
    public function testReadsLegacyPchartFormat(): void
    {
        self::assertSame([
            [186, 206, 151, 100],
            [0, 12, 255, 0],
        ], PaletteFile::parse("186,206,151,100\r\n0,12,255,0\n"));
    }

    /**
     * @dataProvider invalidPalettes
     */
    public function testRejectsInvalidPaletteWithoutSilentlySkippingLines(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        PaletteFile::parse($input);
    }

    public function invalidPalettes(): iterable
    {
        yield 'empty' => [''];

        yield 'script' => ['<?php echo 1;'];

        yield 'negative red' => ['-1,2,3,100'];

        yield 'invalid red' => ['256,2,3,100'];

        yield 'invalid alpha' => ['2,3,4,101'];

        yield 'missing alpha' => ['2,3,4'];

        yield 'extra component' => ['1,2,3,4,5'];

        yield 'injected second line' => ["1,2,3,100\n<script>alert(1)</script>"];

        yield 'too large' => [str_repeat('1,2,3,100'."\n", 257)];
    }
}

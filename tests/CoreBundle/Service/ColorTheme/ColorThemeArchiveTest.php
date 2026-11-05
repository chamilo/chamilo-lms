<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Service\ColorTheme;

use Chamilo\CoreBundle\Service\ColorTheme\ColorThemeArchive;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ZipArchive;

use const JSON_THROW_ON_ERROR;

final class ColorThemeArchiveTest extends TestCase
{
    private string $archive;

    protected function setUp(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'chamilo_theme_test_');
        self::assertIsString($path);
        $this->archive = $path;
    }

    protected function tearDown(): void
    {
        if (isset($this->archive) && is_file($this->archive)) {
            unlink($this->archive);
        }
    }

    public function testRoundTripPreservesColorVariablesAndDescription(): void
    {
        $vars = ['--color-primary-base' => '46 117 163', '--color-secondary-gradient' => '-1 86 130'];
        ColorThemeArchive::write($this->archive, 'My Theme', $vars);

        $result = ColorThemeArchive::read($this->archive);
        self::assertSame('My Theme', $result['name']);
        self::assertSame($vars, $result['variables']);
        self::assertSame([], $result['assets']);
    }

    public function testUnsafeColorValuesCannotBecomeCss(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ColorThemeArchive::write($this->archive, 'Test', ['--color-primary-base' => 'red; } body { display:none']);
    }

    public function testOutOfRangeColorChannelIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ColorThemeArchive::validateVariables(['--color-primary-base' => '999 0 1']);
    }

    public function testArbitraryZipEntryIsRejectedBeforeExtraction(): void
    {
        $this->writeRawPackage([
            'theme.json' => $this->manifest(),
            '../outside.php' => '<?php echo 1;',
        ]);

        $this->expectException(InvalidArgumentException::class);
        ColorThemeArchive::read($this->archive);
    }

    public function testExternalScriptIsRejected(): void
    {
        $this->writeRawPackage([
            'theme.json' => $this->manifest(),
            'tiny-settings.js' => 'alert(1)',
        ]);

        $this->expectException(InvalidArgumentException::class);
        ColorThemeArchive::read($this->archive);
    }

    public function testIncompatibleVersionIsRejected(): void
    {
        $this->writeRawPackage([
            'theme.json' => json_encode([
                'formatVersion' => 1,
                'chamiloVersion' => '4.0',
                'name' => 'Test',
                'variables' => ['--color-primary-base' => '46 117 163'],
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->expectException(InvalidArgumentException::class);
        ColorThemeArchive::read($this->archive);
    }

    public function testInvalidImageIsRejected(): void
    {
        $this->writeRawPackage([
            'theme.json' => $this->manifest(),
            'images/header-logo.png' => 'not a PNG',
        ]);

        $this->expectException(InvalidArgumentException::class);
        ColorThemeArchive::read($this->archive);
    }

    public function testMissingDescriptionIsRejected(): void
    {
        $this->writeRawPackage(['images/header-logo.png' => 'a']);

        $this->expectException(InvalidArgumentException::class);
        ColorThemeArchive::read($this->archive);
    }

    private function manifest(): string
    {
        return json_encode([
            'formatVersion' => 1,
            'chamiloVersion' => '3.1',
            'name' => 'Test',
            'variables' => ['--color-primary-base' => '46 117 163'],
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, string> $files
     */
    private function writeRawPackage(array $files): void
    {
        $zip = new ZipArchive();
        self::assertTrue(true === $zip->open($this->archive, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
    }
}

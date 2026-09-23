<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Toolbox;

use Chamilo\CoreBundle\Service\LearningPath\ScormManifestParser;
use Chamilo\CoreBundle\Service\Toolbox\ToolboxScormPackageBuilder;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class ToolboxScormPackageBuilderTest extends TestCase
{
    public function testItBuildsAValidSingleScoPackageWithTheSandboxBridge(): void
    {
        if (!class_exists(ZipArchive::class)) {
            self::markTestSkipped('The ZIP extension is required.');
        }

        $builder = new ToolboxScormPackageBuilder();
        $package = $builder->build(
            'Letters blaster',
            '<main id="game"><button id="start">Start</button></main>',
            '#game { min-height: 20rem; }',
            'window.API.LMSInitialize(""); window.API.LMSSetValue("cmi.core.lesson_status", "incomplete");',
            12,
            3,
        );

        try {
            $zip = new ZipArchive();
            self::assertTrue($zip->open($package['path']));
            $manifest = $zip->getFromName('imsmanifest.xml');
            $html = $zip->getFromName('index.html');
            $zip->close();

            self::assertIsString($manifest);
            self::assertIsString($html);
            self::assertStringContainsString('adlcp:scormtype="sco"', $manifest);
            self::assertStringContainsString('chamilo-toolbox-scorm-commit', $html);
            self::assertStringContainsString('window.API = api', $html);

            $parsed = (new ScormManifestParser())->parse($manifest);
            self::assertSame('1.2', $parsed['version']);
            self::assertCount(1, $parsed['organizations']);
            self::assertSame('index.html', $parsed['resources']['RES_1']['href']);
            self::assertSame('sco', $parsed['resources']['RES_1']['scormType']);
        } finally {
            if (is_file($package['path'])) {
                @unlink($package['path']);
            }
        }
    }
}

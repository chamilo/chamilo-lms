<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CourseBundle\Component\CourseCopy;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Converter\CcEntities;
use PHPUnit\Framework\TestCase;

/**
 * Regression test for GHSA-hp9w-mg25-479h.
 *
 * The Common Cartridge 1.3 importer resolves every file reference it reads from
 * the manifest (resource/@href, file/@href, web-link and discussion sources)
 * against the extracted package folder. Those references are attacker-controlled
 * — any teacher able to run the import supplies the cartridge — so a reference
 * such as "../../../../.env" would otherwise escape the package and let the
 * importer copy an application-readable file into the course, disclosing it.
 *
 * CcEntities::safeResolveInside() is the single confinement guard every sink now
 * goes through. These cases lock both halves of its contract: a genuine file
 * inside the package resolves, and anything escaping it (relative traversal,
 * absolute path, or a symlink pointing outside) is rejected.
 */
final class Cc13ImportPathConfinementTest extends TestCase
{
    private string $root = '';

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/chamilo_cc13_confine_'.uniqid('', true);

        mkdir($this->root.'/package/web_resources', 0777, true);
        file_put_contents($this->root.'/package/web_resources/page.html', 'legitimate');
        file_put_contents($this->root.'/secret.txt', 'database credentials');
    }

    protected function tearDown(): void
    {
        if ('' !== $this->root && is_dir($this->root)) {
            $this->removeDirectory($this->root);
        }
    }

    public function testAGenuinePackageFileResolves(): void
    {
        $resolved = CcEntities::safeResolveInside($this->root.'/package', 'web_resources/page.html');

        $this->assertSame(realpath($this->root.'/package/web_resources/page.html'), $resolved);
    }

    public function testRelativeTraversalIsRejected(): void
    {
        $this->assertNull(CcEntities::safeResolveInside($this->root.'/package', '../secret.txt'));
        $this->assertNull(CcEntities::safeResolveInside($this->root.'/package', 'web_resources/../../secret.txt'));
    }

    public function testAbsolutePathIsRejected(): void
    {
        // Treated as package-relative, so it cannot reach outside the package.
        $this->assertNull(CcEntities::safeResolveInside($this->root.'/package', $this->root.'/secret.txt'));
    }

    public function testSymlinkEscapingThePackageIsRejected(): void
    {
        if (!@symlink($this->root.'/secret.txt', $this->root.'/package/escape.txt')) {
            $this->markTestSkipped('Symlinks are not supported on this filesystem.');
        }

        $this->assertNull(CcEntities::safeResolveInside($this->root.'/package', 'escape.txt'));
    }

    public function testMissingFileResolvesToNull(): void
    {
        $this->assertNull(CcEntities::safeResolveInside($this->root.'/package', 'web_resources/does-not-exist.html'));
    }

    private function removeDirectory(string $dir): void
    {
        $items = scandir($dir);
        if (false === $items) {
            return;
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $path = $dir.'/'.$item;
            if (is_link($path) || is_file($path)) {
                unlink($path);

                continue;
            }
            if (is_dir($path)) {
                $this->removeDirectory($path);
            }
        }

        rmdir($dir);
    }
}

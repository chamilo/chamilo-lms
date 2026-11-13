<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CourseBundle\Component\CourseCopy;

use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Regression test for GHSA-45px-24pm-v7wm.
 *
 * While rewriting restored HTML, the fallback resolves every src/href that points
 * under /document/ to a file of the extracted backup and republishes it as a course
 * document. The path travels inside the backup HTML, which any teacher can craft, and
 * the normalizer that prepares it deliberately keeps "..". The fallback therefore has
 * to confine the resolved file to the extracted backup; otherwise a crafted reference
 * resolves to a file of the server and publishes its contents as a course document.
 *
 * These cases lock both halves of that guarantee: the normalizer still preserves the
 * traversal that makes the confinement necessary, and the confinement the fallback now
 * applies rejects a reference escaping the backup while accepting a genuine asset.
 */
final class CourseRestorerHtmlAssetConfinementTest extends TestCase
{
    private string $root = '';

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/chamilo_restorer_html_'.uniqid('', true);

        mkdir($this->root.'/backup/document', 0777, true);
        file_put_contents($this->root.'/backup/document/asset.png', 'legitimate');
        file_put_contents($this->root.'/secret.txt', 'database credentials');
    }

    protected function tearDown(): void
    {
        if ('' !== $this->root && is_dir($this->root)) {
            $this->removeDirectory($this->root);
        }
    }

    public function testTheDocumentPathNormalizerKeepsTraversal(): void
    {
        // The normalizer never strips "..", which is exactly why the fallback must
        // confine the resolved file instead of trusting the normalized path.
        $normalized = $this->normalize('document/../../secret.txt');

        $this->assertStringContainsString('..', $normalized);
    }

    public function testAGenuineHtmlAssetResolves(): void
    {
        $rel = $this->normalize('document/asset.png');

        $this->assertSame(
            realpath($this->root.'/backup/document/asset.png'),
            $this->resolve($this->root.'/backup/'.$rel)
        );
    }

    public function testAnHtmlAssetThatEscapesTheBackupIsRejected(): void
    {
        $rel = $this->normalize('document/../../secret.txt');

        // Without the confinement the fallback would import $this->root/secret.txt.
        $this->assertNull($this->resolve($this->root.'/backup/'.$rel));
    }

    private function normalize(string $rel): string
    {
        return $this->call('normalizeImportedDocumentRelativePath', $rel);
    }

    private function resolve(string $candidate): ?string
    {
        return $this->call('resolveFileInsideBackup', $candidate);
    }

    /**
     * Calls a private method of a restorer whose course only carries a backup path.
     */
    private function call(string $method, string $argument): ?string
    {
        $reflection = new ReflectionClass(CourseRestorer::class);

        $restorer = $reflection->newInstanceWithoutConstructor();
        $restorer->course = (object) ['backup_path' => $this->root.'/backup'];

        $call = $reflection->getMethod($method);
        $call->setAccessible(true);

        return $call->invoke($restorer, $argument);
    }

    private function removeDirectory(string $directory): void
    {
        foreach (array_diff(scandir($directory) ?: [], ['.', '..']) as $entry) {
            $child = $directory.'/'.$entry;

            if (is_dir($child) && !is_link($child)) {
                $this->removeDirectory($child);

                continue;
            }

            unlink($child);
        }

        rmdir($directory);
    }
}

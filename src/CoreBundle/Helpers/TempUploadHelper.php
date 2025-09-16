<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use const DIRECTORY_SEPARATOR;

/**
 * Cleans temporary upload artifacts inside %kernel.project_dir%/var/cache
 * while SAFELY skipping Symfony's own cache directories/files.
 */
final class TempUploadHelper
{
    /**
     * @var string[] Top-level directories to skip under var/cache
     */
    private array $excludeTop = ['dev', 'prod', 'test', 'pools'];

    /**
     * @var string[] Regex patterns to skip anywhere under var/cache
     */
    private array $excludePatterns = [
        '#/(vich_uploader|twig|doctrine|http_cache|profiler)/#i',
        '#/jms_[^/]+/#i',
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%/var/cache')]
        private readonly string $tempUploadDir
    ) {}

    public function getTempDir(): string
    {
        return rtrim($this->tempUploadDir, DIRECTORY_SEPARATOR);
    }

    /**
     * Stats for files that WOULD be targeted (i.e., excluding Symfony cache).
     *
     * @return array{files:int,bytes:int}
     */
    public function stats(): array
    {
        $dir = $this->getTempDir();
        $this->assertBaseDir($dir);

        $files = 0;
        $bytes = 0;

        if (!is_dir($dir) || !is_readable($dir)) {
            return ['files' => 0, 'bytes' => 0];
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $dir,
                FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $f) {
            if ($this->isExcluded($dir, $f)) {
                // Skip whole excluded subtree quickly
                if ($f->isDir()) {
                    $it->next(); // let iterator move on
                }

                continue;
            }
            if ($f->isFile()) {
                $bn = $f->getBasename();
                if ($this->isProtected($bn)) {
                    continue;
                }
                $files++;
                $bytes += (int) $f->getSize();
            }
        }

        return ['files' => $files, 'bytes' => $bytes];
    }

    /**
     * Purge target files (excluding Symfony cache) older than $olderThanMinutes.
     * If $olderThanMinutes = 0, delete all target files.
     * If $dryRun = true, do not delete; only count.
     *
     * If $strict = true, DO NOT exclude Symfony cache: dangerous; use only
     * for manual maintenance and ensure proper permissions afterwards.
     *
     * @return array{files:int,bytes:int}
     */
    public function purge(int $olderThanMinutes = 0, bool $dryRun = false, bool $strict = false): array
    {
        $dir = $this->getTempDir();
        $this->assertBaseDir($dir);

        $deleted = 0;
        $bytes = 0;

        if (!is_dir($dir) || !is_readable($dir)) {
            return ['files' => 0, 'bytes' => 0];
        }

        $cutoff = $olderThanMinutes > 0 ? (time() - $olderThanMinutes * 60) : null;

        $rii = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $dir,
                FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($rii as $f) {
            if (!$strict && $this->isExcluded($dir, $f)) {
                // Skip excluded subtree
                if ($f->isDir()) {
                    $rii->next();
                }

                continue;
            }

            $bn = $f->getBasename();
            if ($this->isProtected($bn)) {
                continue;
            }

            if ($f->isFile()) {
                if (null !== $cutoff && $f->getMTime() > $cutoff) {
                    continue;
                }
                $bytes += (int) $f->getSize();
                if (!$dryRun) {
                    @unlink($f->getPathname());
                }
                $deleted++;
            } elseif ($f->isDir()) {
                if (!$dryRun) {
                    @rmdir($f->getPathname()); // best-effort (only if empty)
                }
            }
        }

        return ['files' => $deleted, 'bytes' => $bytes];
    }

    private function isProtected(string $basename): bool
    {
        return '.htaccess' === $basename || '.gitignore' === $basename;
    }

    /**
     * Prevent catastrophes and ensure directory exists & is writable.
     */
    private function assertBaseDir(string $dir): void
    {
        // Ensure base exists
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (!is_writable($dir)) {
            throw new InvalidArgumentException(\sprintf('Temp dir not writable: %s', $dir));
        }
    }

    /**
     * Decide if a file/dir should be excluded from cleanup.
     */
    private function isExcluded(string $base, SplFileInfo $f): bool
    {
        $path = $f->getPathname();
        $rel = ltrim(str_replace('\\', '/', substr($path, \strlen($base))), '/');

        // Top-level directory name?
        $first = explode('/', $rel, 2)[0] ?? '';
        if ('' !== $first && \in_array($first, $this->excludeTop, true)) {
            return true;
        }

        // Pattern matches anywhere in the path
        foreach ($this->excludePatterns as $re) {
            if (preg_match($re, '/'.$rel.'/')) {
                return true;
            }
        }

        return false;
    }
}

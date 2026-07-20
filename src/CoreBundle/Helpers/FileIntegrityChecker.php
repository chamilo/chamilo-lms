<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use FilesystemIterator;
use JsonException;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\HttpKernel\KernelInterface;
use UnexpectedValueException;

use const FILE_APPEND;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const LOCK_EX;

/**
 * Walks the installed file tree (everything except var/ and .git/, though
 * .git/config is still watched on its own to catch a remote being repointed),
 * compares it to a stored baseline of sha256 checksums, and tracks the
 * snooze window used during maintenance windows.
 *
 * State lives under var/security/file_integrity/ (inside the excluded var/
 * directory, so it never becomes part of its own baseline).
 */
class FileIntegrityChecker
{
    private const EXCLUDED_DIRECTORIES = ['var', '.git'];
    private const GIT_CONFIG_RELATIVE_PATH = '.git/config';
    private const MAX_REPORTED_PATHS = 500;
    private const MAX_SNOOZE_SECONDS = 86400;

    private readonly string $projectDir;
    private readonly string $stateDir;
    private readonly string $baselineFile;
    private readonly string $reportFile;
    private readonly string $stateFile;
    private readonly string $cefLogFile;

    public function __construct(KernelInterface $kernel)
    {
        $this->projectDir = rtrim($kernel->getProjectDir(), '/');
        $this->stateDir = $this->projectDir.'/var/security/file_integrity';
        $this->baselineFile = $this->stateDir.'/baseline.json';
        $this->reportFile = $this->stateDir.'/last_report.json';
        $this->stateFile = $this->stateDir.'/state.json';
        $this->cefLogFile = $kernel->getLogDir().'/security/file_integrity.log';

        if (!is_dir($this->stateDir)) {
            mkdir($this->stateDir, 0o755, true);
        }
    }

    public function hasBaseline(): bool
    {
        return is_file($this->baselineFile);
    }

    /**
     * Walks the tree and adopts it as the new trusted reference.
     *
     * @return int the number of files captured in the new baseline
     */
    public function generateBaseline(): int
    {
        $walk = $this->walkTree();
        $this->writeJson($this->baselineFile, $walk['manifest']);

        return \count($walk['manifest']);
    }

    /**
     * Compares the current tree to the baseline, persists the report, and
     * (when something changed) appends CEF events for external SIEM ingestion.
     *
     * @return array{
     *     added: array<string, array{size:int,mtime:int,sha256:string}>,
     *     modified: array<string, array{old:string,new:string}>,
     *     deleted: array<string, array{size:int,mtime:int,sha256:string}>,
     *     addedCount: int,
     *     modifiedCount: int,
     *     deletedCount: int,
     *     unreadable: string[],
     *     gitConfigChanged: bool,
     *     scannedCount: int,
     *     truncated: bool,
     *     scanIncomplete: bool,
     *     establishedBaseline: bool,
     *     at: int
     * }
     */
    public function scan(): array
    {
        if (!$this->hasBaseline()) {
            $walk = $this->walkTree();
            $this->writeJson($this->baselineFile, $walk['manifest']);

            $report = [
                'added' => [],
                'modified' => [],
                'deleted' => [],
                'addedCount' => 0,
                'modifiedCount' => 0,
                'deletedCount' => 0,
                'unreadable' => $walk['unreadable'],
                'gitConfigChanged' => false,
                'scannedCount' => \count($walk['manifest']),
                'truncated' => false,
                'scanIncomplete' => $walk['scanIncomplete'],
                'establishedBaseline' => true,
                'at' => time(),
            ];

            $this->writeJson($this->reportFile, $report);

            return $report;
        }

        $baseline = $this->readJson($this->baselineFile) ?? [];
        $walk = $this->walkTree();
        $current = $walk['manifest'];

        $added = [];
        $modified = [];
        $deleted = [];

        foreach ($current as $path => $info) {
            if (!isset($baseline[$path])) {
                $added[$path] = $info;

                continue;
            }

            if ($baseline[$path]['sha256'] !== $info['sha256']) {
                $modified[$path] = ['old' => $baseline[$path]['sha256'], 'new' => $info['sha256']];
            }
        }

        foreach ($baseline as $path => $info) {
            if (!isset($current[$path])) {
                $deleted[$path] = $info;
            }
        }

        $gitConfigChanged = isset($added[self::GIT_CONFIG_RELATIVE_PATH])
            || isset($modified[self::GIT_CONFIG_RELATIVE_PATH])
            || isset($deleted[self::GIT_CONFIG_RELATIVE_PATH]);

        $truncated = \count($added) > self::MAX_REPORTED_PATHS
            || \count($modified) > self::MAX_REPORTED_PATHS
            || \count($deleted) > self::MAX_REPORTED_PATHS;

        $report = [
            'added' => \array_slice($added, 0, self::MAX_REPORTED_PATHS, true),
            'modified' => \array_slice($modified, 0, self::MAX_REPORTED_PATHS, true),
            'deleted' => \array_slice($deleted, 0, self::MAX_REPORTED_PATHS, true),
            'addedCount' => \count($added),
            'modifiedCount' => \count($modified),
            'deletedCount' => \count($deleted),
            'unreadable' => $walk['unreadable'],
            'gitConfigChanged' => $gitConfigChanged,
            'scannedCount' => \count($current),
            'truncated' => $truncated,
            'scanIncomplete' => $walk['scanIncomplete'],
            'establishedBaseline' => false,
            'at' => time(),
        ];

        $this->writeJson($this->reportFile, $report);
        $this->writeCefEvents($report);

        return $report;
    }

    public function hasDrift(array $report): bool
    {
        return [] !== $report['added'] || [] !== $report['modified'] || [] !== $report['deleted'];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getLastReport(): ?array
    {
        return $this->readJson($this->reportFile);
    }

    public function snooze(int $seconds): void
    {
        $seconds = max(1, min($seconds, self::MAX_SNOOZE_SECONDS));

        $this->writeJson($this->stateFile, ['snoozedUntil' => time() + $seconds]);
    }

    public function clearSnooze(): void
    {
        $this->writeJson($this->stateFile, ['snoozedUntil' => 0]);
    }

    public function isSnoozed(): bool
    {
        return $this->getSnoozeUntil() > time();
    }

    public function getSnoozeUntil(): int
    {
        $state = $this->readJson($this->stateFile);

        return (int) ($state['snoozedUntil'] ?? 0);
    }

    /**
     * @return array{manifest: array<string, array{size:int,mtime:int,sha256:string}>, unreadable: string[], scanIncomplete: bool}
     */
    private function walkTree(): array
    {
        $manifest = [];
        $unreadable = [];
        $scanIncomplete = false;
        $root = $this->projectDir;
        $rootLength = \strlen($root) + 1;

        $directoryIterator = new RecursiveDirectoryIterator(
            $root,
            FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS
        );

        $filterIterator = new RecursiveCallbackFilterIterator(
            $directoryIterator,
            static function (SplFileInfo $file, string $key, RecursiveDirectoryIterator $iterator): bool {
                // Never follow symlinks: avoids escaping the project root and traversal loops.
                if ($file->isLink()) {
                    return false;
                }

                if ($iterator->hasChildren()) {
                    return !\in_array($file->getFilename(), self::EXCLUDED_DIRECTORIES, true);
                }

                return true;
            }
        );

        $iterator = new RecursiveIteratorIterator($filterIterator, RecursiveIteratorIterator::LEAVES_ONLY);

        try {
            foreach ($iterator as $fileInfo) {
                /** @var SplFileInfo $fileInfo */
                if (!$fileInfo->isFile()) {
                    continue;
                }

                $relativePath = substr($fileInfo->getPathname(), $rootLength);

                if (!$fileInfo->isReadable()) {
                    $unreadable[] = $relativePath;

                    continue;
                }

                $hash = hash_file('sha256', $fileInfo->getPathname());

                if (false === $hash) {
                    $unreadable[] = $relativePath;

                    continue;
                }

                $manifest[$relativePath] = [
                    'size' => $fileInfo->getSize(),
                    'mtime' => $fileInfo->getMTime(),
                    'sha256' => $hash,
                ];
            }
        } catch (UnexpectedValueException) {
            // A subdirectory could not be opened (permissions) mid-walk: keep partial
            // results but flag the scan so it isn't trusted as a full clean sweep.
            $scanIncomplete = true;
        }

        $gitConfigPath = $root.'/'.self::GIT_CONFIG_RELATIVE_PATH;

        if (is_file($gitConfigPath) && !is_link($gitConfigPath) && is_readable($gitConfigPath)) {
            $hash = hash_file('sha256', $gitConfigPath);

            if (false !== $hash) {
                $manifest[self::GIT_CONFIG_RELATIVE_PATH] = [
                    'size' => filesize($gitConfigPath) ?: 0,
                    'mtime' => filemtime($gitConfigPath) ?: 0,
                    'sha256' => $hash,
                ];
            }
        }

        ksort($manifest);

        return ['manifest' => $manifest, 'unreadable' => $unreadable, 'scanIncomplete' => $scanIncomplete];
    }

    /**
     * @param array<string, mixed> $report
     */
    private function writeCefEvents(array $report): void
    {
        $events = [];

        foreach ($report['added'] as $path => $info) {
            $isGitConfig = self::GIT_CONFIG_RELATIVE_PATH === $path;
            $events[] = $this->buildCefLine(
                $isGitConfig ? 'FIM-GITCONFIG' : 'FIM-ADDED',
                $isGitConfig ? 8 : 6,
                (string) $path,
                null,
                $info['sha256']
            );
        }

        foreach ($report['modified'] as $path => $hashes) {
            $isGitConfig = self::GIT_CONFIG_RELATIVE_PATH === $path;
            $events[] = $this->buildCefLine(
                $isGitConfig ? 'FIM-GITCONFIG' : 'FIM-MODIFIED',
                8,
                (string) $path,
                $hashes['old'],
                $hashes['new']
            );
        }

        foreach ($report['deleted'] as $path => $info) {
            $isGitConfig = self::GIT_CONFIG_RELATIVE_PATH === $path;
            $events[] = $this->buildCefLine(
                $isGitConfig ? 'FIM-GITCONFIG' : 'FIM-DELETED',
                8,
                (string) $path,
                $info['sha256'],
                null
            );
        }

        if ($report['truncated']) {
            $events[] = \sprintf(
                'CEF:0|Chamilo|Chamilo LMS|n/a|FIM-TRUNCATED|File integrity report truncated|5|'
                ."msg=%d added, %d modified, %d deleted; only the first entries of each were logged individually\n",
                $report['addedCount'],
                $report['modifiedCount'],
                $report['deletedCount']
            );
        }

        if ([] === $events) {
            return;
        }

        $dir = \dirname($this->cefLogFile);

        if (!is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        file_put_contents($this->cefLogFile, implode('', $events), FILE_APPEND | LOCK_EX);
    }

    private function buildCefLine(
        string $signatureId,
        int $severity,
        string $filePath,
        ?string $oldHash,
        ?string $newHash,
    ): string {
        $extension = \sprintf(
            'filePath=%s rt=%d',
            $this->cefEscapeExtensionValue($filePath),
            (int) round(microtime(true) * 1000)
        );

        if (null !== $oldHash) {
            $extension .= ' oldFileHash='.$oldHash;
        }

        if (null !== $newHash) {
            $extension .= ' fileHash='.$newHash;
        }

        return \sprintf(
            "CEF:0|Chamilo|Chamilo LMS|n/a|%s|File integrity change: %s|%d|%s\n",
            $signatureId,
            $this->cefEscapeHeader($filePath),
            $severity,
            $extension
        );
    }

    private function cefEscapeHeader(string $value): string
    {
        return str_replace(['\\', '|'], ['\\\\', '\|'], $value);
    }

    private function cefEscapeExtensionValue(string $value): string
    {
        $value = str_replace(['\\', '='], ['\\\\', '\='], $value);

        return str_replace(["\r\n", "\n", "\r"], '\n', $value);
    }

    private function writeJson(string $path, array $data): void
    {
        $dir = \dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        file_put_contents($path, json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), LOCK_EX);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readJson(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if (false === $contents || '' === trim($contents)) {
            return null;
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return \is_array($decoded) ? $decoded : null;
    }
}

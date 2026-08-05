<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use FilesystemIterator;
use JsonException;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;
use UnexpectedValueException;

use const FILE_APPEND;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const LOCK_EX;
use const LOCK_NB;
use const LOCK_SH;
use const LOCK_UN;
use const PHP_OS_FAMILY;

/**
 * Walks the installed file tree (everything except var/ and .git/, though
 * .git/config is still watched on its own to catch a remote being repointed),
 * compares it to a stored baseline of sha256 checksums and permission markers,
 * and keeps the snooze window used during maintenance windows.
 *
 * Permission tracking is platform-dependent: on Linux the POSIX mode bits are
 * compared directly (e.g. a file becoming world-writable is flagged); Windows
 * has no equivalent to expose here (fileperms() doesn't reflect real NTFS
 * ACLs), so only the read-only attribute is tracked there.
 *
 * The walk is chunked one top-level entry at a time (each directory directly
 * under the project root, plus a synthetic entry for loose root-level files)
 * so progress can be reported while it runs — the whole walk can take minutes
 * on a large install, and a web request driving it can hit a gateway timeout
 * long before it finishes.
 *
 * While a walk runs, an exclusive flock() is held on run.lock; its content is
 * the progress report. flock() is released by the kernel no matter how the
 * holding process ends (normal return, uncaught error, SIGTERM, even SIGKILL),
 * so getRunStatus() can always tell whether a previous run is still alive
 * without relying on signal handlers or PID bookkeeping.
 *
 * getLastReport() only ever reflects the most recent scan, and baseline.json
 * is replaced outright by "Establish new baseline" — neither is a record of
 * what happened. getHistory() is: every scan that found something is appended
 * to it (never overwritten by a clean scan or a rebaseline), so past alerts
 * stay visible even after the drift they flagged has been resolved or folded
 * into a new baseline.
 *
 * State lives under var/security/file_integrity/ (inside the excluded var/
 * directory, so it never becomes part of its own baseline).
 */
class FileIntegrityChecker
{
    private const array EXCLUDED_DIRECTORIES = ['var', '.git'];
    private const string GIT_CONFIG_RELATIVE_PATH = '.git/config';
    private const int MAX_REPORTED_PATHS = 500;
    private const int MAX_SNOOZE_SECONDS = 86400;
    private const int MAX_HISTORY_ENTRIES = 50;
    private const string ROOT_FILES_LABEL = '(root files)';

    private readonly string $projectDir;
    private readonly string $stateDir;
    private readonly string $baselineFile;
    private readonly string $reportFile;
    private readonly string $historyFile;
    private readonly string $stateFile;
    private readonly string $runLockFile;
    private readonly string $cefLogFile;

    /**
     * @var resource|null
     */
    private $runLockHandle;
    private ?int $runStartedAt = null;

    public function __construct(KernelInterface $kernel)
    {
        $this->projectDir = rtrim($kernel->getProjectDir(), '/');
        $this->stateDir = $this->projectDir.'/var/security/file_integrity';
        $this->baselineFile = $this->stateDir.'/baseline.json';
        $this->reportFile = $this->stateDir.'/last_report.json';
        $this->historyFile = $this->stateDir.'/history.json';
        $this->stateFile = $this->stateDir.'/state.json';
        $this->runLockFile = $this->stateDir.'/run.lock';
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
     * Absolute path to the CEF log, so a controller can offer it for download —
     * the JSON report is capped to MAX_REPORTED_PATHS per category, but every
     * individual change is always logged here regardless of that cap.
     */
    public function getCefLogPath(): string
    {
        return $this->cefLogFile;
    }

    /**
     * Walks the tree and adopts it as the new trusted reference.
     *
     * @return int the number of files captured in the new baseline
     *
     * @throws RuntimeException if a scan/baseline walk is already running
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
     *     added: array<string, array{size:int,mtime:int,sha256:string,perms:string}>,
     *     modified: array<string, array{old:string,new:string}>,
     *     deleted: array<string, array{size:int,mtime:int,sha256:string,perms:string}>,
     *     permissionsChanged: array<string, array{old:string,new:string}>,
     *     addedCount: int,
     *     modifiedCount: int,
     *     deletedCount: int,
     *     permissionsChangedCount: int,
     *     unreadable: string[],
     *     gitConfigChanged: bool,
     *     scannedCount: int,
     *     truncated: bool,
     *     scanIncomplete: bool,
     *     establishedBaseline: bool,
     *     at: int
     * }
     *
     * @throws RuntimeException if a scan/baseline walk is already running
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
                'permissionsChanged' => [],
                'addedCount' => 0,
                'modifiedCount' => 0,
                'deletedCount' => 0,
                'permissionsChangedCount' => 0,
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
        $permissionsChanged = [];

        foreach ($current as $path => $info) {
            if (!isset($baseline[$path])) {
                $added[$path] = $info;

                continue;
            }

            if ($baseline[$path]['sha256'] !== $info['sha256']) {
                $modified[$path] = ['old' => $baseline[$path]['sha256'], 'new' => $info['sha256']];
            }

            // A baseline taken before permission tracking existed has no 'perms'
            // key: skip the comparison rather than reporting every file as
            // "changed" just because the field is new.
            $oldPerms = $baseline[$path]['perms'] ?? null;
            $newPerms = $info['perms'];

            if (null !== $oldPerms && $oldPerms !== $newPerms) {
                $permissionsChanged[$path] = ['old' => $oldPerms, 'new' => $newPerms];
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
            || \count($deleted) > self::MAX_REPORTED_PATHS
            || \count($permissionsChanged) > self::MAX_REPORTED_PATHS;

        $report = [
            'added' => \array_slice($added, 0, self::MAX_REPORTED_PATHS, true),
            'modified' => \array_slice($modified, 0, self::MAX_REPORTED_PATHS, true),
            'deleted' => \array_slice($deleted, 0, self::MAX_REPORTED_PATHS, true),
            'permissionsChanged' => \array_slice($permissionsChanged, 0, self::MAX_REPORTED_PATHS, true),
            'addedCount' => \count($added),
            'modifiedCount' => \count($modified),
            'deletedCount' => \count($deleted),
            'permissionsChangedCount' => \count($permissionsChanged),
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
        $this->appendToHistory($report);

        return $report;
    }

    public function hasDrift(array $report): bool
    {
        return [] !== $report['added']
            || [] !== $report['modified']
            || [] !== $report['deleted']
            || [] !== ($report['permissionsChanged'] ?? []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getLastReport(): ?array
    {
        return $this->readJson($this->reportFile);
    }

    /**
     * Past alerts, newest first. Unlike getLastReport() (overwritten by every
     * scan, clean or not) and baseline.json (replaced by "Establish new
     * baseline"), this file is only ever appended to — re-baselining never
     * clears it — so it stays the durable answer to "did this platform ever
     * get flagged", even long after the underlying drift has been resolved or
     * a new baseline has absorbed it.
     *
     * Shape: a list of entries, each like the report returned by scan().
     *
     * @return array<string, mixed>
     */
    public function getHistory(): array
    {
        return $this->readJson($this->historyFile) ?? [];
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

    public function isRunInProgress(): bool
    {
        $status = $this->getRunStatus();

        return null !== $status && $status['alive'];
    }

    /**
     * Current or last-known scan/baseline run, for the admin page. Distinguishes
     * three situations: no run has ever happened (null), a run is actively
     * holding the lock right now (alive=true), or the lock is free but the last
     * recorded status isn't "completed" (alive=false, status="running") — which
     * means that run was killed or crashed mid-walk rather than finishing.
     *
     * Shape: {pid:int, startedAt:int, updatedAt:int, status:string, items:string[],
     * current:?string, completed:array<int,array{name:string,files:int}>, alive:bool}.
     *
     * @return array<string, mixed>|null
     */
    public function getRunStatus(): ?array
    {
        if (!is_file($this->runLockFile)) {
            return null;
        }

        $handle = fopen($this->runLockFile, 'r');
        if (false === $handle) {
            return null;
        }

        // A successful *shared* non-blocking lock means nobody holds the
        // exclusive lock right now, i.e. no run is currently in progress.
        $gotSharedLock = flock($handle, LOCK_SH | LOCK_NB);
        $alive = !$gotSharedLock;

        if ($gotSharedLock) {
            flock($handle, LOCK_UN);
        }

        $contents = stream_get_contents($handle) ?: '';
        fclose($handle);

        try {
            $status = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!\is_array($status)) {
            return null;
        }

        $status['alive'] = $alive;

        return $status;
    }

    /**
     * Acquires the run lock (throws if another run already holds it) and
     * records the plan. Every subsequent progress update goes through
     * updateRunProgress(); the lock is always released via endRun().
     *
     * @param string[] $items
     *
     * @throws RuntimeException if a scan/baseline walk is already running
     */
    private function beginRun(array $items): void
    {
        $handle = fopen($this->runLockFile, 'c+');
        if (false === $handle) {
            throw new RuntimeException("Unable to open the file-integrity run lock: {$this->runLockFile}");
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            $existing = stream_get_contents($handle) ?: '';
            fclose($handle);

            $info = json_decode($existing, true);
            $startedAt = \is_array($info) && isset($info['startedAt']) ? (int) $info['startedAt'] : null;

            throw new RuntimeException(null !== $startedAt ? \sprintf('A file integrity scan is already running (started at %s). Please wait for it to finish.', date('Y-m-d H:i:s', $startedAt)) : 'A file integrity scan is already running. Please wait for it to finish.');
        }

        $this->runLockHandle = $handle;
        $this->runStartedAt = time();
        $this->updateRunProgress('running', $items, null, []);
    }

    /**
     * @param string[]                                $items
     * @param array<int,array{name:string,files:int}> $completed
     */
    private function updateRunProgress(string $status, array $items, ?string $current, array $completed): void
    {
        if (null === $this->runLockHandle) {
            return;
        }

        $payload = json_encode([
            'pid' => getmypid() ?: 0,
            'startedAt' => $this->runStartedAt,
            'updatedAt' => time(),
            'status' => $status,
            'items' => $items,
            'current' => $current,
            'completed' => $completed,
        ], JSON_THROW_ON_ERROR);

        ftruncate($this->runLockHandle, 0);
        rewind($this->runLockHandle);
        fwrite($this->runLockHandle, $payload);
        fflush($this->runLockHandle);
    }

    /**
     * @param string[]                                $items
     * @param array<int,array{name:string,files:int}> $completed
     */
    private function endRun(string $status, array $items, array $completed): void
    {
        if (null === $this->runLockHandle) {
            return;
        }

        $this->updateRunProgress($status, $items, null, $completed);
        flock($this->runLockHandle, LOCK_UN);
        fclose($this->runLockHandle);
        $this->runLockHandle = null;
    }

    /**
     * Orchestrates the walk one top-level entry at a time (loose root files as
     * one group, then each top-level directory), updating the run lock's
     * progress after every entry so a concurrent request can report where
     * things stand instead of just "a scan is running".
     *
     * @return array{manifest: array<string, array{size:int,mtime:int,sha256:string,perms:string}>, unreadable: string[], scanIncomplete: bool}
     *
     * @throws RuntimeException if a scan/baseline walk is already running
     */
    private function walkTree(): array
    {
        $root = $this->projectDir;
        [$directories, $rootFiles] = $this->listTopLevelEntries();

        $items = $directories;
        if ([] !== $rootFiles) {
            $items[] = self::ROOT_FILES_LABEL;
        }

        $this->beginRun($items);

        $manifest = [];
        $unreadable = [];
        $scanIncomplete = false;
        $completed = [];

        try {
            if ([] !== $rootFiles) {
                $this->updateRunProgress('running', $items, self::ROOT_FILES_LABEL, $completed);

                $before = \count($manifest);
                foreach ($rootFiles as $relativePath) {
                    $this->hashOneFile($root.'/'.$relativePath, $relativePath, $manifest, $unreadable);
                }
                $completed[] = ['name' => self::ROOT_FILES_LABEL, 'files' => \count($manifest) - $before];
            }

            foreach ($directories as $directory) {
                $this->updateRunProgress('running', $items, $directory, $completed);

                $before = \count($manifest);
                $walked = $this->walkSubtree($root.'/'.$directory, $root);

                foreach ($walked['manifest'] as $path => $info) {
                    $manifest[$path] = $info;
                }
                foreach ($walked['unreadable'] as $path) {
                    $unreadable[] = $path;
                }
                $scanIncomplete = $scanIncomplete || $walked['scanIncomplete'];

                $completed[] = ['name' => $directory, 'files' => \count($manifest) - $before];
            }

            $this->captureGitConfig($root, $manifest);
            ksort($manifest);

            $this->endRun('completed', $items, $completed);

            return ['manifest' => $manifest, 'unreadable' => $unreadable, 'scanIncomplete' => $scanIncomplete];
        } catch (Throwable $e) {
            // Leaves status "interrupted" behind so getRunStatus() can tell
            // this run didn't finish, even though the lock is now free.
            $this->endRun('interrupted', $items, $completed);

            throw $e;
        }
    }

    /**
     * @return array{0: string[], 1: string[]} [directoryNames, fileNames], both relative to the project root
     */
    private function listTopLevelEntries(): array
    {
        $directories = [];
        $files = [];

        foreach (scandir($this->projectDir) ?: [] as $name) {
            if ('.' === $name || '..' === $name || \in_array($name, self::EXCLUDED_DIRECTORIES, true)) {
                continue;
            }

            $path = $this->projectDir.'/'.$name;

            // Never follow symlinks: avoids escaping the project root and traversal loops.
            if (is_link($path)) {
                continue;
            }

            if (is_dir($path)) {
                $directories[] = $name;
            } elseif (is_file($path)) {
                $files[] = $name;
            }
        }

        sort($directories);
        sort($files);

        return [$directories, $files];
    }

    /**
     * Walks a single top-level directory. Paths in the returned manifest are
     * relative to the project root (not to $absoluteStart), matching the rest
     * of the manifest.
     *
     * @return array{manifest: array<string, array{size:int,mtime:int,sha256:string,perms:string}>, unreadable: string[], scanIncomplete: bool}
     */
    private function walkSubtree(string $absoluteStart, string $root): array
    {
        $manifest = [];
        $unreadable = [];
        $scanIncomplete = false;
        $rootLength = \strlen($root) + 1;

        $directoryIterator = new RecursiveDirectoryIterator(
            $absoluteStart,
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
                $this->hashFileInfo($fileInfo, $relativePath, $manifest, $unreadable);
            }
        } catch (UnexpectedValueException) {
            // A subdirectory could not be opened (permissions) mid-walk: keep partial
            // results but flag the scan so it isn't trusted as a full clean sweep.
            $scanIncomplete = true;
        }

        return ['manifest' => $manifest, 'unreadable' => $unreadable, 'scanIncomplete' => $scanIncomplete];
    }

    /**
     * @param array<string, array{size:int,mtime:int,sha256:string,perms:string}> $manifest
     * @param string[]                                                            $unreadable
     */
    private function hashFileInfo(SplFileInfo $fileInfo, string $relativePath, array &$manifest, array &$unreadable): void
    {
        if (!$fileInfo->isReadable()) {
            $unreadable[] = $relativePath;

            return;
        }

        $hash = hash_file('sha256', $fileInfo->getPathname());

        if (false === $hash) {
            $unreadable[] = $relativePath;

            return;
        }

        $manifest[$relativePath] = [
            'size' => $fileInfo->getSize(),
            'mtime' => $fileInfo->getMTime(),
            'sha256' => $hash,
            'perms' => $this->getPermissionsMarker($fileInfo->getPathname()),
        ];
    }

    /**
     * @param array<string, array{size:int,mtime:int,sha256:string,perms:string}> $manifest
     * @param string[]                                                            $unreadable
     */
    private function hashOneFile(string $absolutePath, string $relativePath, array &$manifest, array &$unreadable): void
    {
        if (!is_file($absolutePath) || is_link($absolutePath)) {
            return;
        }

        if (!is_readable($absolutePath)) {
            $unreadable[] = $relativePath;

            return;
        }

        $hash = hash_file('sha256', $absolutePath);

        if (false === $hash) {
            $unreadable[] = $relativePath;

            return;
        }

        $manifest[$relativePath] = [
            'size' => filesize($absolutePath) ?: 0,
            'mtime' => filemtime($absolutePath) ?: 0,
            'sha256' => $hash,
            'perms' => $this->getPermissionsMarker($absolutePath),
        ];
    }

    /**
     * @param array<string, array{size:int,mtime:int,sha256:string,perms:string}> $manifest
     */
    private function captureGitConfig(string $root, array &$manifest): void
    {
        $gitConfigPath = $root.'/'.self::GIT_CONFIG_RELATIVE_PATH;

        if (is_file($gitConfigPath) && !is_link($gitConfigPath) && is_readable($gitConfigPath)) {
            $hash = hash_file('sha256', $gitConfigPath);

            if (false !== $hash) {
                $manifest[self::GIT_CONFIG_RELATIVE_PATH] = [
                    'size' => filesize($gitConfigPath) ?: 0,
                    'mtime' => filemtime($gitConfigPath) ?: 0,
                    'sha256' => $hash,
                    'perms' => $this->getPermissionsMarker($gitConfigPath),
                ];
            }
        }
    }

    /**
     * Linux: the POSIX mode bits as a 3-digit octal string (e.g. "644"), which
     * is directly comparable between scans. Windows has no equivalent concept
     * — real NTFS ACLs aren't exposed by fileperms() — so this only tracks the
     * one meaningful, platform-native signal available: the read-only attribute.
     */
    private function getPermissionsMarker(string $path): string
    {
        if ('Windows' === PHP_OS_FAMILY) {
            return is_writable($path) ? 'rw' : 'ro';
        }

        $perms = @fileperms($path);

        return false !== $perms ? \sprintf('%03o', $perms & 0777) : '';
    }

    /**
     * Records an alert-worthy scan in the durable history (skipped for clean
     * scans, matching writeCefEvents() — a scan finding nothing has no alert
     * to remember). Keeps only the most recent MAX_HISTORY_ENTRIES so the file
     * doesn't grow forever; this is a rolling window, not a full audit log.
     *
     * @param array<string, mixed> $report
     */
    private function appendToHistory(array $report): void
    {
        if (!$this->hasDrift($report) && !$report['gitConfigChanged']) {
            return;
        }

        $history = $this->getHistory();

        array_unshift($history, [
            'at' => $report['at'],
            'added' => array_keys($report['added']),
            'modified' => array_keys($report['modified']),
            'deleted' => array_keys($report['deleted']),
            'permissionsChanged' => $report['permissionsChanged'] ?? [],
            'addedCount' => $report['addedCount'],
            'modifiedCount' => $report['modifiedCount'],
            'deletedCount' => $report['deletedCount'],
            'permissionsChangedCount' => $report['permissionsChangedCount'] ?? 0,
            'gitConfigChanged' => $report['gitConfigChanged'],
            'truncated' => $report['truncated'],
        ]);

        $this->writeJson($this->historyFile, \array_slice($history, 0, self::MAX_HISTORY_ENTRIES));
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

        foreach ($report['permissionsChanged'] ?? [] as $path => $perms) {
            $events[] = $this->buildPermissionsCefLine((string) $path, $perms['old'], $perms['new']);
        }

        if ($report['truncated']) {
            $events[] = \sprintf(
                'CEF:0|Chamilo|Chamilo LMS|n/a|FIM-TRUNCATED|File integrity report truncated|5|'
                .'msg=%d added, %d modified, %d deleted, %d permission change(s); only the first entries of each were logged individually'."\n",
                $report['addedCount'],
                $report['modifiedCount'],
                $report['deletedCount'],
                $report['permissionsChangedCount'] ?? 0
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

    /**
     * Builds one CEF (Common Event Format) log line for a permission change.
     *
     * CEF is a text-based log syntax originally defined by ArcSight and now a
     * de facto standard for security event interchange: it's what lets a SIEM
     * (Wazuh, Splunk, QRadar, ArcSight, Elastic via Filebeat, ...) parse events
     * from many unrelated products with one generic parser, instead of needing
     * a custom one per vendor. Each line has a fixed pipe-delimited header
     * followed by a free-form, space-separated "extension" of key=value pairs:
     *
     *   CEF:Version|Device Vendor|Device Product|Device Version|Signature ID|Name|Severity|Extension
     *
     *   - Version:      CEF syntax version (always 0 here; unrelated to our own version).
     *   - Device Vendor/Product/Version: who/what generated the event — "Chamilo LMS", "n/a"
     *                   since we don't track a version number for this purpose.
     *   - Signature ID: a stable, vendor-defined event-type code the SIEM can filter/alert on
     *                   (here "FIM-PERMS"; sibling codes are FIM-ADDED, FIM-MODIFIED, FIM-DELETED,
     *                   FIM-GITCONFIG, FIM-TRUNCATED — see buildCefLine()).
     *   - Name:         a short human-readable summary of the event.
     *   - Severity:     0 (lowest) to 10 (highest); 7 here, between a plain content edit (8,
     *                   since content changes are the core signal) and a new file appearing (6).
     *   - Extension:    the event's actual data as "key=value" pairs. A few keys are part of
     *                   the CEF standard dictionary (filePath, rt = "receipt time" in epoch ms);
     *                   others are free-form custom keys of our own (oldPerms/newPerms), which
     *                   is normal — CEF only standardizes the envelope, not every possible field.
     *
     * Values containing CEF's own delimiters must be escaped or they'd corrupt the line for any
     * parser: header fields escape "\" and "|" (cefEscapeHeader()), extension values escape "\",
     * "=" and newlines (cefEscapeExtensionValue()) — see the CEF spec section on encoding.
     */
    private function buildPermissionsCefLine(string $filePath, string $oldPerms, string $newPerms): string
    {
        $extension = \sprintf(
            'filePath=%s rt=%d oldPerms=%s newPerms=%s',
            $this->cefEscapeExtensionValue($filePath),
            (int) round(microtime(true) * 1000),
            $this->cefEscapeExtensionValue($oldPerms),
            $this->cefEscapeExtensionValue($newPerms)
        );

        return \sprintf(
            "CEF:0|Chamilo|Chamilo LMS|n/a|FIM-PERMS|File permissions change: %s|7|%s\n",
            $this->cefEscapeHeader($filePath),
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

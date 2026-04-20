<?php

declare(strict_types=1);

/**
 * Finds platform settings that have no implementation in the codebase.
 *
 * Queries the `settings` table for access_url=1, then for each variable name
 * searches the entire source tree with grep. A setting is considered "not
 * implemented" when its variable string is either absent from the code or
 * appears only inside PHP comment lines / blocks.
 *
 * Usage:
 *   php tests/scripts/find_unsupported_settings.php [output.csv]
 *
 * If no output file is given the CSV is written to stdout.
 *
 * @package chamilo.tests.scripts
 */

die('Remove the "die()" statement on line '.__LINE__.' to execute this script'.PHP_EOL);

// ---------------------------------------------------------------------------
// Configuration
// ---------------------------------------------------------------------------

/** Root of the repository – adjust if the script moves. */
$repoRoot = \dirname(__DIR__, 2);

/** Directories / files to search inside $repoRoot (relative paths). */
$searchPaths = [
    'src',
    'public/main',
    'assets',
    'config',
    'templates',
];

/** File extensions passed to grep via --include. */
$includeGlobs = ['*.php', '*.twig', '*.js', '*.ts', '*.vue', '*.yaml', '*.yml'];

/**
 * Files that only define/register/insert settings — not actual usage.
 * Matches are excluded so a setting appearing only here is still "not implemented".
 */
$excludeFiles = [
    'public/main/admin/settings.php',                          // legacy settings admin UI
    'src/CoreBundle/DataFixtures/SettingsCurrentFixtures.php', // initial DB fixtures
];

/**
 * Directory name patterns excluded from the grep search entirely.
 * - Migrations: only INSERT/UPDATE settings rows.
 * - Settings: SettingsManager.php + *Schema.php files define/register settings,
 *   they do not constitute actual usage of the setting value in application logic.
 */
$excludeDirs = [
    'Migrations',
    'Settings',
];

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Parse DB credentials from an .env-style file.
 * Reads DATABASE_HOST, DATABASE_PORT, DATABASE_NAME, DATABASE_USER, DATABASE_PASSWORD.
 * Falls back to .env.local when a variable is missing.
 */
function parseDatabaseConfig(string $envFile): array
{
    $load = static function (string $file): array {
        $vars = [];
        if (!is_readable($file)) {
            return $vars;
        }
        foreach (file($file) as $line) {
            $line = trim($line);
            if ('' === $line || str_starts_with($line, '#')) {
                continue;
            }
            $eq = strpos($line, '=');
            if (false === $eq) {
                continue;
            }
            $key = substr($line, 0, $eq);
            $val = trim(substr($line, $eq + 1), '"\'');
            $vars[$key] = $val;
        }

        return $vars;
    };

    $vars = $load($envFile);
    $local = \dirname($envFile).'/.env.local';
    if (is_readable($local)) {
        $vars = array_merge($vars, $load($local));
    }

    $keys = ['DATABASE_HOST', 'DATABASE_PORT', 'DATABASE_NAME', 'DATABASE_USER', 'DATABASE_PASSWORD'];
    $missing = array_diff($keys, array_keys($vars));
    if ([] !== $missing) {
        fwrite(STDERR, 'Missing .env variables: '.implode(', ', $missing)."\n");
        exit(1);
    }

    return [
        'host'   => $vars['DATABASE_HOST'],
        'port'   => (int) $vars['DATABASE_PORT'],
        'user'   => $vars['DATABASE_USER'],
        'pass'   => $vars['DATABASE_PASSWORD'],
        'dbname' => $vars['DATABASE_NAME'],
    ];
}

/**
 * Returns true when a trimmed source line looks like a PHP comment.
 *
 * Covers:
 *   // single-line comment
 *   # hash comment
 *   *  inside a block comment  (line starts with * after trim)
 *   /* opening of block comment
 */
function isCommentLine(string $line): bool
{
    $t = ltrim($line);

    return str_starts_with($t, '//')
        || str_starts_with($t, '#')
        || str_starts_with($t, '*')
        || str_starts_with($t, '/*');
}

/**
 * Grep the repository for an exact occurrence of $needle (as a word or
 * partial token – whatever grep finds for the literal string).
 *
 * Returns an array of matching lines as strings "file:lineNo:content".
 */
function grepForVariable(
    string $needle,
    string $repoRoot,
    array $searchPaths,
    array $includeGlobs,
    array $excludeFiles = [],
    array $excludeDirs = [],
): array {
    // Build --include flags
    $includes = implode(' ', array_map(
        static fn(string $g): string => '--include='.escapeshellarg($g),
        $includeGlobs
    ));

    // Build --exclude flags (basename patterns for specific files)
    $excludes = implode(' ', array_map(
        static fn(string $f): string => '--exclude='.escapeshellarg(basename($f)),
        $excludeFiles
    ));

    // Build --exclude-dir flags
    $excludeDirFlags = implode(' ', array_map(
        static fn(string $d): string => '--exclude-dir='.escapeshellarg($d),
        $excludeDirs
    ));

    // Absolute search paths that actually exist
    $paths = [];
    foreach ($searchPaths as $rel) {
        $abs = $repoRoot.'/'.$rel;
        if (file_exists($abs)) {
            $paths[] = escapeshellarg($abs);
        }
    }

    if ([] === $paths) {
        return [];
    }

    // -r  recursive
    // -n  line numbers
    // -F  fixed string (not a regex) – safer for variable names with special chars
    $cmd = sprintf(
        'grep -rn -F %s %s %s %s %s 2>/dev/null',
        escapeshellarg($needle),
        $includes,
        $excludes,
        $excludeDirFlags,
        implode(' ', $paths)
    );

    $output = [];
    exec($cmd, $output);

    // Filter out any remaining excluded files that grep's --exclude may have missed
    // (e.g. when the same basename appears in multiple directories)
    $excludeAbsolute = array_map(
        static fn(string $f): string => realpath($repoRoot.'/'.$f) ?: ($repoRoot.'/'.$f),
        $excludeFiles
    );

    return array_values(array_filter(
        $output,
        static function (string $line) use ($excludeAbsolute): bool {
            $file = explode(':', $line, 2)[0];
            $real = realpath($file) ?: $file;

            return !\in_array($real, $excludeAbsolute, true);
        }
    ));
}

/**
 * Returns true when $variable is genuinely used in the codebase
 * (i.e. at least one non-comment match was found).
 */
function isImplemented(
    string $variable,
    string $repoRoot,
    array $searchPaths,
    array $includeGlobs,
    array $excludeFiles = [],
    array $excludeDirs = [],
): bool {
    $matches = grepForVariable($variable, $repoRoot, $searchPaths, $includeGlobs, $excludeFiles, $excludeDirs);

    if ([] === $matches) {
        return false;
    }

    foreach ($matches as $match) {
        // Format: /path/to/file.php:42:   content of line
        // Split on the first two colons only
        $parts = explode(':', $match, 3);
        $lineContent = $parts[2] ?? $match;

        if (!isCommentLine($lineContent)) {
            return true;
        }
    }

    // Every match was in a comment
    return false;
}

// ---------------------------------------------------------------------------
// Main
// ---------------------------------------------------------------------------

$outputFile = $argv[1] ?? null;

// Connect to DB
$db = parseDatabaseConfig($repoRoot.'/.env');

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['dbname']),
        $db['user'],
        $db['pass'],
        [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
    );
} catch (\PDOException $e) {
    fwrite(STDERR, 'DB connection failed: '.$e->getMessage()."\n");
    exit(1);
}

// Fetch all settings for access_url = 1, deduplicated by variable
// (subkeys share the same variable; we only need to check the variable once)
$stmt = $pdo->query(
    "SELECT DISTINCT variable, category, title, comment
       FROM settings
      WHERE access_url = 1
      ORDER BY category, variable"
);

$settings = $stmt->fetchAll(\PDO::FETCH_ASSOC);

if ([] === $settings) {
    fwrite(STDERR, "No settings found for access_url=1.\n");
    exit(0);
}

fwrite(STDERR, sprintf("Checking %d distinct settings variables...\n", count($settings)));

// Build CSV rows for unimplemented settings
$rows = [];
$checked = 0;

foreach ($settings as $row) {
    $variable = $row['variable'];
    ++$checked;

    if ($checked % 25 === 0) {
        fwrite(STDERR, "  {$checked}/".count($settings)."...\n");
    }

    if (!isImplemented($variable, $repoRoot, $searchPaths, $includeGlobs, $excludeFiles, $excludeDirs)) {
        $rows[] = [
            $variable,
            $row['category'] ?? '',
            $row['title'] ?? '',
            $row['comment'] ?? '',
        ];
    }
}

fwrite(STDERR, sprintf(
    "Done. %d/%d settings appear unimplemented.\n",
    count($rows),
    count($settings)
));

// Write CSV
$header = ['variable', 'category', 'title', 'comment'];

if (null !== $outputFile) {
    $fh = fopen($outputFile, 'w');
    if (false === $fh) {
        fwrite(STDERR, "Cannot open output file: {$outputFile}\n");
        exit(1);
    }
} else {
    $fh = STDOUT;
}

fputcsv($fh, $header);
foreach ($rows as $row) {
    fputcsv($fh, $row);
}

if (null !== $outputFile) {
    fclose($fh);
    fwrite(STDERR, "CSV written to {$outputFile}\n");
}

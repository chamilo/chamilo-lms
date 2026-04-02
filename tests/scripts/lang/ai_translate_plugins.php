<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

/**
 * Translates Chamilo plugin language files using the Grok API.
 *
 * Plugin lang files live at: public/plugin/<PluginName>/lang/<language>.php
 * Each file defines a $strings array:  $strings['key'] = 'value';
 *
 * This script:
 *  1. Takes en_US.php as the source of truth for each plugin.
 *  2. Completes missing (or empty) keys in existing language files (leaves
 *     non-empty translations untouched).
 *  3. Creates new language files for languages that are missing from a plugin.
 *
 * Usage (run from any directory):
 *   php tests/scripts/lang/ai_translate_plugins.php [--test] [--plugin Name] [lang …]
 *
 *   lang         ISO code (fr_FR, de, es) OR filename stem (french, german, spanish).
 *                If omitted, all language files found across existing plugins are used.
 *   --test           Send only one API batch per plugin (for quick smoke-testing).
 *   --all-languages  Target every language in the built-in map instead of auto-detecting
 *                    from existing files. Useful for bootstrapping a new language everywhere.
 *   --backup         Create a .bak copy of each existing file before modifying it.
 *                    Off by default (Git history serves as backup).
 *   --plugin N       Restrict to the named plugin directory (repeatable).
 *
 * Requires config.php (copy from config.dist.php) with $translationAPIKey set.
 */

if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}

$configFile = __DIR__.'/config.php';
if (!is_file($configFile)) {
    exit('No config.php found. Copy config.dist.php to config.php and fill in your API key.'.PHP_EOL);
}
require_once $configFile;

$apiKey    = $translationAPIKey      ?? '';
$apiUrl    = $translationAPIEndpoint ?? 'https://api.x.ai/v1/chat/completions';
$pluginDir = realpath(__DIR__.'/../../../public/plugin');
$logFile   = __DIR__.'/ai_translate_plugins.log';
$batchSize = 50;

// ===================== LANGUAGE MAPS =====================

/**
 * Maps the language filename stem (without .php) → ISO language code.
 * Add entries as needed for less common languages.
 */
$langNameToCode = [
    'arabic'              => 'ar',
    'asturian'            => 'ast_ES',
    'basque'              => 'eu_ES',
    'bosnian'             => 'bs_BA',
    'brazilian'           => 'pt_BR',
    'bulgarian'           => 'bg',
    'catalan'             => 'ca_ES',
    'chinese'             => 'zh_CN',
    'chinese_traditional' => 'zh_TW',
    'croatian'            => 'hr_HR',
    'czech'               => 'cs_CZ',
    'danish'              => 'da',
    'dutch'               => 'nl',
    'english'             => 'en_US',
    'esperanto'           => 'eo',
    'finnish'             => 'fi_FI',
    'french'              => 'fr_FR',
    'galician'            => 'gl',
    'georgian'            => 'ka_GE',
    'german'              => 'de',
    'greek'               => 'el',
    'hebrew'              => 'he_IL',
    'hindi'               => 'hi',
    'hungarian'           => 'hu_HU',
    'indonesian'          => 'id_ID',
    'italian'             => 'it',
    'japanese'            => 'ja',
    'korean'              => 'ko_KR',
    'latvian'             => 'lv_LV',
    'lithuanian'          => 'lt_LT',
    'malay'               => 'ms_MY',
    'norwegian'           => 'nn_NO',
    'persian'             => 'fa_IR',
    'polish'              => 'pl_PL',
    'portuguese'          => 'pt_PT',
    'romanian'            => 'ro_RO',
    'russian'             => 'ru_RU',
    'serbian'             => 'sr_RS',
    'slovak'              => 'sk_SK',
    'slovenian'           => 'sl_SI',
    'spanish'             => 'es',
    'swedish'             => 'sv_SE',
    'thai'                => 'th',
    'turkish'             => 'tr',
    'ukrainian'           => 'uk_UA',
    'vietnamese'          => 'vi_VN',
];

/** ISO code → filename stem (reverse of the above). */
$langCodeToName = array_flip($langNameToCode);

// ===================== HELPER FUNCTIONS =====================

function eprintln(string $msg, bool $timestamp = false): void
{
    if ($timestamp) {
        $msg = '['.date('H:i:s').'] '.$msg;
    }
    fwrite(STDERR, $msg.PHP_EOL);
}

function pluginLogAction(string $logFile, string $context, string $key, string $action): void
{
    $ts       = date('Y-m-d H:i:s');
    $shortKey = mb_substr(str_replace(["\n", "\r"], ' ', $key), 0, 80);
    file_put_contents($logFile, "[{$ts}] [{$context}] {$action} | key: {$shortKey}".PHP_EOL, FILE_APPEND);
}

/**
 * Include a plugin language PHP file and return the $strings it defines.
 * The include runs in local scope so only $strings is captured.
 * Use this for both the English source (to get values) and target files (to get existing translations).
 */
function parsePluginLangFile(string $filePath): array
{
    if (!is_file($filePath)) {
        return [];
    }
    $strings = [];
    include $filePath;

    return $strings;
}

/**
 * Parse the structural layout of a plugin language PHP file.
 *
 * Returns an ordered list of entries, each one of:
 *   ['type' => 'raw',    'line'  => string]   – comment, blank line, preamble (<?php, declare…)
 *   ['type' => 'string', 'key'   => string]   – a $strings assignment (value comes from include)
 *
 * Multi-line assignments (concatenation, heredoc, literal newlines inside strings) are
 * collapsed to a single 'string' entry; the actual translated value is supplied at write time.
 */
function parseSourceFileStructure(string $filePath): array
{
    $lines   = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES | 0);
    // Re-read without FILE_SKIP_EMPTY_LINES so blank lines are preserved
    $lines   = file($filePath, FILE_IGNORE_NEW_LINES);
    $entries = [];

    $inAssignment = false;
    $currentKey   = null;
    $depth        = 0; // track open quotes to detect end-of-assignment

    foreach ($lines as $line) {
        if ($inAssignment) {
            // Accumulate continuation lines; detect end of the PHP statement.
            // We look for a line whose trimmed form ends with ; and is not inside
            // an open string — heuristic: line ends with '; or "; or just ; after closing paren.
            $trimmed = rtrim($line);
            if (
                str_ends_with($trimmed, "';")
                || str_ends_with($trimmed, '";')
                || str_ends_with($trimmed, ');')
                || (str_ends_with($trimmed, ';') && !str_contains($trimmed, "'") && !str_contains($trimmed, '"'))
            ) {
                // End of the multi-line assignment — record only the key, not the raw lines
                $entries[]    = ['type' => 'string', 'key' => $currentKey];
                $inAssignment = false;
                $currentKey   = null;
            }
            // Continuation lines are intentionally dropped; value comes from include()
            continue;
        }

        // Check for start of a $strings assignment
        if (preg_match('/^\$strings\[([\'"])(.+?)\1\]\s*=/', $line, $m)) {
            $key = $m[2];
            // Single-line assignment ends with ; on the same line
            $trimmed = rtrim($line);
            if (str_ends_with($trimmed, ';')) {
                $entries[] = ['type' => 'string', 'key' => $key];
            } else {
                // Multi-line assignment — skip continuation lines until ; found
                $inAssignment = true;
                $currentKey   = $key;
            }
            continue;
        }

        // Everything else: comments, blank lines, <?php, declare(…)
        $entries[] = ['type' => 'raw', 'line' => $line];
    }

    return $entries;
}

/**
 * Render a PHP string literal.
 * Uses double quotes when the value contains a single quote, single quotes otherwise.
 */
function formatPhpValue(string $value): string
{
    if (str_contains($value, "'")) {
        $escaped = str_replace(
            ['\\',    '"',    '$',    "\r\n", "\r",   "\n"],
            ['\\\\',  '\\"',  '\\$',  '\\n',  '\\n',  '\\n'],
            $value
        );

        return '"'.$escaped.'"';
    }

    $escaped = str_replace(['\\', "'"], ['\\\\', "\\'"], $value);

    return "'".$escaped."'";
}

/**
 * Write a plugin language file preserving the structural layout of the English source.
 *
 * @param string $filePath       Where to write
 * @param array  $sourceEntries  Structural entries from parseSourceFileStructure()
 * @param array  $strings        Final [key => translated value] map
 */
function writePluginLangFile(string $filePath, array $sourceEntries, array $strings): bool
{
    $outputLines = [];

    foreach ($sourceEntries as $entry) {
        if ('raw' === $entry['type']) {
            $outputLines[] = $entry['line'];
        } else {
            // 'string' entry: emit translated (or empty) value
            $key        = $entry['key'];
            $value      = $strings[$key] ?? '';
            $escapedKey = str_replace("'", "\\'", $key);
            $outputLines[] = "\$strings['{$escapedKey}'] = ".formatPhpValue($value).';';
        }
    }

    // Append any keys present in $strings but not in the source structure
    // (should not normally happen, but guards against edge cases)
    $structureKeys = [];
    foreach ($sourceEntries as $entry) {
        if ('string' === $entry['type']) {
            $structureKeys[$entry['key']] = true;
        }
    }
    foreach ($strings as $key => $value) {
        if (!isset($structureKeys[$key])) {
            $escapedKey    = str_replace("'", "\\'", $key);
            $outputLines[] = "\$strings['{$escapedKey}'] = ".formatPhpValue($value).';';
        }
    }

    return file_put_contents($filePath, implode("\n", $outputLines)."\n") !== false;
}

/**
 * Return a human-readable name for a language ISO code.
 */
function pluginGetLanguageName(string $code): string
{
    static $map = [
        'ar'    => 'Arabic',
        'ast_ES'=> 'Asturian',
        'bg'    => 'Bulgarian',
        'bs_BA' => 'Bosnian',
        'ca_ES' => 'Catalan',
        'cs_CZ' => 'Czech',
        'da'    => 'Danish',
        'de'    => 'German',
        'el'    => 'Greek',
        'en_US' => 'English',
        'eo'    => 'Esperanto',
        'es'    => 'Spanish',
        'eu_ES' => 'Basque',
        'fa_IR' => 'Persian',
        'fi_FI' => 'Finnish',
        'fr_FR' => 'French',
        'gl'    => 'Galician',
        'he_IL' => 'Hebrew',
        'hi'    => 'Hindi',
        'hr_HR' => 'Croatian',
        'hu_HU' => 'Hungarian',
        'id_ID' => 'Indonesian',
        'it'    => 'Italian',
        'ja'    => 'Japanese',
        'ka_GE' => 'Georgian',
        'ko_KR' => 'Korean',
        'lt_LT' => 'Lithuanian',
        'lv_LV' => 'Latvian',
        'ms_MY' => 'Malay',
        'nl'    => 'Dutch',
        'nn_NO' => 'Norwegian Nynorsk',
        'pl_PL' => 'Polish',
        'pt_BR' => 'Brazilian Portuguese',
        'pt_PT' => 'Portuguese',
        'ro_RO' => 'Romanian',
        'ru_RU' => 'Russian',
        'sk_SK' => 'Slovak',
        'sl_SI' => 'Slovenian',
        'sr_RS' => 'Serbian',
        'sv_SE' => 'Swedish',
        'th'    => 'Thai',
        'tr'    => 'Turkish',
        'uk_UA' => 'Ukrainian',
        'vi_VN' => 'Vietnamese',
        'zh_CN' => 'Simplified Chinese',
        'zh_TW' => 'Traditional Chinese',
    ];

    return $map[$code] ?? $code;
}

/**
 * Send a batch of strings to the Grok API for translation.
 *
 * @param array $batchItems  [ ['id' => int, 'source' => string], … ]
 * @return array             [id => translatedString]  (empty on soft failure)
 * @throws RuntimeException  on hard failures (cURL error, HTTP error, bad structure)
 */
function callGrokTranslateBatch(
    string $apiUrl,
    string $apiKey,
    string $targetLangCode,
    string $targetLangName,
    array $batchItems
): array {
    if (empty($batchItems)) {
        return [];
    }

    $systemPrompt = <<<'EOT'
You are a translation assistant for Chamilo, an open source Learning Management System (LMS).
Your task is to translate user interface strings used in web and mobile applications.

Requirements:
- Translations must be clear and professional, suitable for training and academic contexts.
- Prefer concise wording so that labels fit in web and mobile UI elements.
- Maintain the original meaning precisely; do not add explanations.
- Preserve all placeholders (like %s, %d, {name}), HTML tags, and punctuation.
- Do not reorder placeholders or change their format.
- When in doubt, prefer neutral, academic-language style.
EOT;

    $inputList = [];
    foreach ($batchItems as $item) {
        $inputList[] = ['id' => $item['id'], 'source' => $item['source']];
    }

    $userPrompt =
        "Translate the following Chamilo LMS interface strings from English (source_language: en) "
        ."into {$targetLangName} (target_language code: {$targetLangCode}).\n"
        ."Return ONLY a valid JSON array, no extra text. Each array item MUST be an object with:\n"
        ."  - \"id\": the same integer id as in the input\n"
        ."  - \"translation\": the translated string\n\n"
        ."Do not change or remove any placeholders (e.g. %s, %d, {name}) or HTML tags.\n\n"
        ."Input:\n"
        .json_encode($inputList, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    $payload = [
        'model'       => 'grok-4-1-fast-non-reasoning',
        'messages'    => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $userPrompt],
        ],
        'temperature' => 0.2,
    ];

    $ch = curl_init($apiUrl);
    if ($ch === false) {
        throw new RuntimeException('Failed to initialize cURL.');
    }

    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer '.$apiKey,
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT        => 60,
    ]);

    $responseBody = curl_exec($ch);
    if ($responseBody === false) {
        $err   = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        throw new RuntimeException("cURL error ({$errno}): {$err}");
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new RuntimeException("Grok API HTTP error {$httpCode}: {$responseBody}");
    }

    $data = json_decode($responseBody, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        eprintln('[Grok] Invalid JSON in full response ('.json_last_error_msg().') – skipping batch.', true);

        return [];
    }

    if (!is_array($data) || !isset($data['choices'][0]['message']['content'])) {
        throw new RuntimeException('Unexpected Grok API response structure.');
    }

    $content = $data['choices'][0]['message']['content'];
    $start   = strpos($content, '[');
    $end     = strrpos($content, ']');

    if ($start === false || $end === false || $end <= $start) {
        eprintln('[Grok] No JSON array found in response – skipping batch.', true);

        return [];
    }

    $arr = json_decode(substr($content, $start, $end - $start + 1), true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($arr)) {
        eprintln('[Grok] Could not parse translations array – skipping batch.', true);

        return [];
    }

    $result = [];
    foreach ($arr as $item) {
        if (isset($item['id'], $item['translation']) && is_scalar($item['translation'])) {
            $result[(int) $item['id']] = (string) $item['translation'];
        }
    }

    return $result;
}

/**
 * Send all items in $pendingBatch to the API, populate $result with translations,
 * then clear $pendingBatch and $pendingMap.
 */
function flushBatch(
    string $apiUrl,
    string $apiKey,
    string $langCode,
    string $langLabel,
    string $context,
    string $logFile,
    array $existing,
    array &$pendingBatch,
    array &$pendingMap,
    array &$result,
    int   &$apiBatchCount
): void {
    if (empty($pendingBatch)) {
        return;
    }

    $apiBatchCount++;
    eprintln("[{$context}] Sending batch {$apiBatchCount} (".count($pendingBatch)." terms) to Grok.", true);

    try {
        $translations = callGrokTranslateBatch($apiUrl, $apiKey, $langCode, $langLabel, $pendingBatch);
        eprintln("[{$context}] Batch {$apiBatchCount}: ".count($translations)." translation(s) received.", true);
        sleep(1);
    } catch (Throwable $ex) {
        eprintln("[{$context}] Batch {$apiBatchCount} FAILED: ".$ex->getMessage(), true);
        $translations = [];
    }

    foreach ($pendingBatch as $item) {
        $id  = $item['id'];
        $key = $pendingMap[$id];
        $val = $translations[$id] ?? '';

        if ($val === '') {
            // Fall back to whatever was there before (may be empty string)
            $val = $existing[$key] ?? '';
            pluginLogAction($logFile, $context, $key, 'failed – kept original');
        } else {
            $action = array_key_exists($key, $existing) ? 'completed' : 'created';
            pluginLogAction($logFile, $context, $key, $action);
        }

        $result[$key] = $val;
    }

    $pendingBatch = [];
    $pendingMap   = [];
}

// ===================== CLI ARGUMENT PARSING =====================

$argvCopy = $argv;
array_shift($argvCopy);

$testMode       = false;
$allLanguages   = false;
$makeBackups    = false;
$onlyPlugins    = [];
$requestedLangs = [];

for ($i = 0; $i < count($argvCopy); $i++) {
    $arg = $argvCopy[$i];
    if ('--test' === $arg) {
        $testMode = true;
    } elseif ('--all-languages' === $arg) {
        $allLanguages = true;
    } elseif ('--backup' === $arg) {
        $makeBackups = true;
    } elseif ('--plugin' === $arg) {
        if (isset($argvCopy[++$i])) {
            $onlyPlugins[] = $argvCopy[$i];
        }
    } else {
        $requestedLangs[] = trim($arg);
    }
}

// Normalise requested languages to filename stems
$normalisedLangs = [];
foreach ($requestedLangs as $lang) {
    if ('' === $lang) {
        continue;
    }
    if (isset($langCodeToName[$lang])) {
        // Was given as ISO code
        $normalisedLangs[] = $lang;
    } elseif (isset($langNameToCode[$lang])) {
        // Was given as filename stem
        $normalisedLangs[] = $langNameToCode[$lang];
    } else {
        eprintln("Warning: unknown language '{$lang}' – skipped. Add it to \$langNameToCode if needed.");
    }
}
$requestedLangs = array_values(array_unique($normalisedLangs));

// ===================== DISCOVER PLUGINS =====================

if (!$pluginDir || !is_dir($pluginDir)) {
    eprintln("Plugin directory not found: ".(__DIR__.'/../../../public/plugin'));
    exit(1);
}

$allDirs = array_filter((array) glob($pluginDir.'/*'), 'is_dir');

if (!empty($onlyPlugins)) {
    $allDirs = array_filter($allDirs, fn ($d) => in_array(basename($d), $onlyPlugins, true));
}

$plugins = [];
foreach ($allDirs as $dir) {
    $langDir = $dir.'/lang';
    $english = $langDir.'/en_US.php';
    if (is_dir($langDir) && is_file($english)) {
        $plugins[] = [
            'name'    => basename($dir),
            'langDir' => $langDir,
            'source'  => $english,
        ];
    }
}

if (empty($plugins)) {
    eprintln('No plugins with a lang/en_US.php found.', true);
    exit(0);
}

eprintln('Found '.count($plugins).' plugin(s) with translatable lang files.', true);

// ===================== DETERMINE TARGET LANGUAGES =====================

if ($allLanguages) {
    // Use every language in the map except English
    $requestedLangs = array_keys(array_filter($langNameToCode, fn ($code) => 'en_US' !== $code));
    sort($requestedLangs);
    eprintln('--all-languages: targeting '.count($requestedLangs).' language(s): '.implode(', ', $requestedLangs), true);
} elseif (empty($requestedLangs)) {
    // Auto-detect: union of all non-English language file stems across all plugins
    $detected = [];
    foreach ($plugins as $plugin) {
        foreach ((array) glob($plugin['langDir'].'/*.php') as $f) {
            $stem = basename($f, '.php');
            if ('en_US' !== $stem && isset($langNameToCode[$stem])) {
                $detected[$stem] = true;
            }
        }
    }
    $requestedLangs = array_keys($detected);
    sort($requestedLangs);
    eprintln('Auto-detected language(s): '.implode(', ', $requestedLangs), true);
}

if (empty($requestedLangs)) {
    eprintln('No target languages to process. Specify languages on the command line or add translation files to plugins.', true);
    exit(0);
}

if ($apiKey === '' || str_starts_with($apiKey, '{')) {
    eprintln('Warning: Grok API key is not configured. Please edit config.php.');
}

if ($testMode) {
    eprintln('Running in TEST MODE – at most one API batch per plugin/language combination.', true);
}

// ===================== MAIN PROCESSING LOOP =====================

foreach ($plugins as $plugin) {
    $pluginName  = $plugin['name'];
    $langDir     = $plugin['langDir'];

    $sourceStrings  = parsePluginLangFile($plugin['source']);
    $sourceStructure = parseSourceFileStructure($plugin['source']);
    $totalTerms     = count($sourceStrings);

    if (0 === $totalTerms) {
        eprintln("[{$pluginName}] en_US.php appears empty – skipping.", true);
        continue;
    }

    eprintln("============================================================");
    eprintln("[{$pluginName}] {$totalTerms} term(s) in en_US.php", true);

    foreach ($requestedLangs as $langCode) {
        $langName   = $langCodeToName[$langCode];
        $langLabel  = pluginGetLanguageName($langCode);
        $targetFile = $langDir.'/'.$langCode.'.php';
        $fileExists = is_file($targetFile);
        $context    = "{$pluginName}/{$langName}";

        eprintln("------------------------------------------------------------");
        eprintln("[{$context}] ({$langLabel})".($fileExists ? '' : ' [NEW FILE]'), true);

        // Backup existing file (only when --backup is given)
        if ($fileExists && $makeBackups) {
            $backup = $targetFile.'.bak';
            if (!is_file($backup)) {
                if (copy($targetFile, $backup)) {
                    eprintln("[{$context}] Backup: {$backup}");
                } else {
                    eprintln("[{$context}] Warning: could not create backup.");
                }
            }
        }

        $existing = parsePluginLangFile($targetFile);

        // Keys that need translation: absent or empty in the target file
        $toTranslate = [];
        foreach ($sourceStrings as $key => $sourceValue) {
            if (!array_key_exists($key, $existing) || trim((string) $existing[$key]) === '') {
                $toTranslate[$key] = $sourceValue;
            }
        }

        if (empty($toTranslate)) {
            eprintln("[{$context}] All {$totalTerms} term(s) already translated – nothing to do.", true);
            continue;
        }

        eprintln("[{$context}] ".count($toTranslate)." term(s) to translate.", true);

        // Build result starting from existing translations (preserved as-is)
        $result       = $existing;
        $pendingBatch = [];
        $pendingMap   = [];  // localId => key
        $apiBatchCount = 0;
        $maxBatches   = $testMode ? 1 : PHP_INT_MAX;

        foreach ($toTranslate as $key => $sourceValue) {
            if ($apiBatchCount >= $maxBatches) {
                // Test mode limit reached: leave key empty (or keep existing empty)
                if (!array_key_exists($key, $result)) {
                    $result[$key] = '';
                }
                continue;
            }

            $localId              = count($pendingBatch);
            $pendingBatch[]       = ['id' => $localId, 'source' => $sourceValue];
            $pendingMap[$localId] = $key;

            if (count($pendingBatch) >= $batchSize) {
                flushBatch(
                    $apiUrl, $apiKey, $langCode, $langLabel,
                    $context, $logFile, $existing,
                    $pendingBatch, $pendingMap, $result, $apiBatchCount
                );
            }
        }

        // Final batch
        if (!empty($pendingBatch) && $apiBatchCount < $maxBatches) {
            flushBatch(
                $apiUrl, $apiKey, $langCode, $langLabel,
                $context, $logFile, $existing,
                $pendingBatch, $pendingMap, $result, $apiBatchCount
            );
        }

        if (writePluginLangFile($targetFile, $sourceStructure, $result)) {
            eprintln("[{$context}] File written: {$targetFile}", true);
        } else {
            eprintln("[{$context}] ERROR: failed to write {$targetFile}!", true);
        }
    }
}

eprintln("============================================================");
eprintln("Done.", true);

<?php
declare(strict_types=1);

/* For licensing terms, see /license.txt */

// This script completes translations for Chamilo LMS using the Grok (or other AI) API.
// Run from tests/scripts/lang/ with access to ../../../translations/
// Requires curl and JSON extensions.
// Set the $apiKey variable with your Grok API key.
// Usage: php grok_translate.php [lang1] [lang2] ...
// If no languages provided, processes all messages.*.po except messages.en.po

if (PHP_SAPI != 'cli') {
    die('This script can only be executed from the command line');
}

$translationSourceLanguageCode = 'en_US';
$translationAPIEndpoint = 'https://api.x.ai/v1/chat/completions';

$configFile = __DIR__ . '/config.php';
if (!is_file(__DIR__ . '/config.php')) {
    exit('No config.php file found in this directory. Please copy config.php.dist to config.php and fill in your API key.');
}
require_once __DIR__ . '/config.php';
$apiKey = $translationAPIKey ?? '';

/**
 * Chamilo Gettext auto-translator using Grok (grok-4-1-fast-non-reasoning)
 *
 * Usage:
 *   php translate.php [--test] [fr_FR es de]
 *
 * - messages.en.po is used as the source of truth for terms and ordering.
 * - For each requested language (e.g. "fr_FR"), messages.fr_FR.po will be updated. If no language requested, all except English are processed.
 * - The translation is done by calling the Grok API, which returns a JSON response with the translated terms.
 * - The translation is applied to the target .po file, replacing existing terms.
 * - The translation is logged to a file for review.
 * - In --test mode, only ONE API request of up to 50 terms is sent, and
 *   the partially translated .po file is written so you can inspect it.
 *
 * Notes:
 * - This script rewrites translation .po files (except the header entry),
 *   using the ordering and comments from messages.en.po.
 * - Plural-form entries (msgid_plural / msgstr[0..]) are not translated
 *   automatically; existing ones are preserved, missing ones are copied
 *   from messages.en.po in English and should be reviewed manually.
 */

// ===================== CONFIGURATION =====================
// AI chat-completions-like endpoint URL
$apiUrl = $translationAPIEndpoint;

// Base (source) language and file
$sourceLanguageCode = $translationSourceLanguageCode;
$translationsDir = __DIR__ . "/../../../translations/";
$basePoFile = $translationsDir . "messages.{$sourceLanguageCode}.po";

// Log file
$logFile = __DIR__ . "/grok_translate.log";

// Batch size for API calls
$batchSize = 50;
$batchSizeInTestMode = 20;

// ===================== HELPER FUNCTIONS =====================

/**
 * Simple stderr output.
 */
function eprintln(string $msg, bool $timestam = false): void {
    if ($timestam) {
        $msg = '[' . date('H:i:s') . '] ' . $msg;
    }
    fwrite(STDERR, $msg . PHP_EOL);
}

/**
 * Get a human-readable language name from code (best-effort).
 */
function getLanguageName(string $code): string {
    static $map = [
        'ar'    => 'Arabic',
        'ast_ES'=> 'Asturian (Spain)',
        'bg'    => 'Bulgarian',
        'bn_BD' => 'Bengali (Bangladesh)',
        'bo_CN' => 'Tibetan (China)',
        'bs_BA' => 'Bosnian (Bosnia and Herzegovina)',
        'ca_ES' => 'Catalan (Spain)',
        'cs_CZ' => 'Czech (Czech Republic)',
        'da'    => 'Danish',
        'de'    => 'German',
        'el'    => 'Greek',
        'en_US' => 'English',
        'eo'    => 'Esperanto',
        'es'    => 'Spanish',
        'es_MX' => 'Spanish (Mexico)',
        'eu_ES' => 'Basque (Spain)',
        'fa_AF' => 'Persian (Afghanistan)',
        'fa_IR' => 'Persian (Iran)',
        'fi_FI' => 'Finnish',
        'fo_FO' => 'Faroese',
        'fr_FR' => 'French',
        'fur'   => 'Friulian (Italy)',
        'ga'    => 'Irish',
        'gl'    => 'Galician (Spain)',
        'he_IL' => 'Hebrew',
        'hi'    => 'Hindi',
        'hr_HR' => 'Croatian',
        'hu_HU' => 'Hungarian',
        'hy'    => 'Armenian',
        'id_ID' => 'Indonesian',
        'it'    => 'Italian',
        'ja'    => 'Japanese',
        'ka_GE' => 'Georgian',
        'ko_KR' => 'Korean',
        'lt_LT' => 'Lithuanian',
        'lv_LV' => 'Latvian',
        'mk_MK' => 'Macedonian',
        'ms_MY' => 'Malay (Malaysia)',
        'my_MM' => 'Burmese (Myanmar)',
        'ne'    => 'Nepali (Nepal)',
        'nl'    => 'Dutch',
        'nn_NO' => 'Norwegian Nynorsk',
        'oc'    => 'Occitan (France)',
        'pl_PL' => 'Polish',
        'ps'    => 'Pashto (Afghanistan)',
        'pt_BR' => 'Brazilian Portuguese',
        'pt_PT' => 'Portuguese',
        'qu_PE' => 'Quechua (Peru)',
        'ro_RO' => 'Romanian',
        'ru_RU' => 'Russian',
        'sk_SK' => 'Slovak',
        'sl_SI' => 'Slovenian',
        'so_SO' => 'Somali (Somalia)',
        'sq'    => 'Albanian',
        'sr_RS' => 'Serbian',
        'sv_SE' => 'Swedish',
        'sw_KE' => 'Swahili',
        'ta'    => 'Tamil (India)',
        'th'    => 'Thai',
        'tl_PH' => 'Tagalog (Philippines)',
        'tr'    => 'Turkish',
        'uk_UA' => 'Ukrainian',
        'vi_VN' => 'Vietnamese',
        'xh_ZA' => 'Xhosa',
        'yo_NG' => 'Yoruba (Nigeria)',
        'zh_CN' => 'Simplified Chinese',
        'zh_TW' => 'Traditional Chinese',
    ];
    return $map[$code] ?? $code;
}

/**
 * Escape a string for use in a .po msgid/msgstr.
 *
 * Rules:
 * - Do NOT touch existing backslashes. Any "\" remains exactly as-is.
 * - Convert actual newline characters to the two-character sequence "\n".
 * - Escape only unescaped double quotes:
 *     - If a " is not immediately preceded by "\", turn it into \".
 *     - If it is already preceded by "\", leave it as-is.
 */
function poEscape(string $s): string {
    // Normalize actual newlines to "\n" escape sequences
    $s = str_replace(["\r\n", "\r"], "\n", $s);
    $s = str_replace("\n", '\\n', $s); // convert real newline chars, not "\n" sequences

    $out = '';
    $len = strlen($s); // byte-wise is fine; we only care about " and \
    for ($i = 0; $i < $len; $i++) {
        $ch = $s[$i];
        if ($ch === '"') {
            $prev = $i > 0 ? $s[$i - 1] : null;
            if ($prev !== '\\') {
                // Unescaped quote -> add a backslash before it
                $out .= '\\"';
            } else {
                // Already escaped as \" -> keep both \ and " unchanged
                $out .= '"';
            }
        } else {
            $out .= $ch;
        }
    }

    return $out;
}

/**
 * "Unescape" a .po string for internal processing.
 *
 * IMPORTANT:
 * - We do NOT modify backslash sequences at all.
 * - We only normalize actual newline characters (which usually do not appear
 *   inside .po string literals anyway).
 */
function poUnescape(string $s): string {
    $s = str_replace(["\r\n", "\r"], "\n", $s);
    return $s;
}

/**
 * Parse the base messages.en.po file.
 *
 * Returns an array of entries in order:
 * [
 *   [
 *     'msgid'      => string,
 *     'comments'   => string[] (comment lines, starting with #),
 *     'isHeader'   => bool,
 *     'hasPlural'  => bool,
 *     'raw'        => string (raw entry text, including comments, msgid, msgstr, etc.)
 *   ],
 *   ...
 * ]
 */
function parseBasePoFile(string $filePath): array {
    if (!is_file($filePath)) {
        throw new RuntimeException("Base PO file not found: {$filePath}");
    }

    $content = file_get_contents($filePath);
    if ($content === false) {
        throw new RuntimeException("Unable to read base PO file: {$filePath}");
    }

    $lines = preg_split("/\R/u", $content);
    $entries = [];
    $currentLines = [];
    $firstEntry = true;

    $flushEntry = function () use (&$entries, &$currentLines, &$firstEntry) {
        if (empty($currentLines)) {
            return;
        }
        $entryText = implode("\n", $currentLines);
        $entry = [
            'msgid'     => '',
            'comments'  => [],
            'isHeader'  => false,
            'hasPlural' => false,
            'raw'       => $entryText,
        ];

        $lines = $currentLines;
        $msgidParts = [];
        $mode = null;

        foreach ($lines as $line) {
            $trim = ltrim($line);
            if ($trim === '') {
                continue;
            }
            if ($trim[0] === '#') {
                $entry['comments'][] = $line;
                continue;
            }
            if (preg_match('/^msgid_plural\s+"/', $trim)) {
                $entry['hasPlural'] = true;
            }
            if (preg_match('/^msgid\s+"(.*)"/', $trim, $m)) {
                $mode = 'msgid';
                $msgidParts = [$m[1]];
                continue;
            }
            if (preg_match('/^msgstr\s+"(.*)"/', $trim)) {
                $mode = 'msgstr';
                continue;
            }
            if (preg_match('/^"(.*)"/', $trim, $m)) {
                if ($mode === 'msgid') {
                    $msgidParts[] = $m[1];
                }
                continue;
            }
        }

        if ($msgidParts) {
            // Keep sequences like "\n", "\\", \" exactly as-is
            $entry['msgid'] = poUnescape(implode('', $msgidParts));
        }

        if ($firstEntry && $entry['msgid'] === '') {
            $entry['isHeader'] = true;
        }

        $entries[] = $entry;
        $currentLines = [];
        $firstEntry = false;
    };

    foreach ($lines as $line) {
        if (trim($line) === '') {
            $flushEntry();
        } else {
            $currentLines[] = $line;
        }
    }
    $flushEntry();

    return $entries;
}

/**
 * Parse a target translation .po file.
 *
 * Returns:
 * [
 *   'headerRaw'   => string|null,
 *   'singular'    => [msgid => msgstr],
 *   'singularRaw' => [msgid => rawEntryText],
 *   'pluralRaw'   => [msgid => rawEntryText],
 * ]
 */
function parseTargetPoFile(string $filePath): array {
    if (!is_file($filePath)) {
        return [
            'headerRaw'   => null,
            'singular'    => [],
            'singularRaw' => [],
            'pluralRaw'   => [],
        ];
    }

    $content = file_get_contents($filePath);
    if ($content === false) {
        throw new RuntimeException("Unable to read target PO file: {$filePath}");
    }

    $lines = preg_split("/\R/u", $content);
    $headerRaw = null;
    $singular = [];
    $singularRaw = [];
    $pluralRaw = [];

    $currentLines = [];
    $entryIndex = 0;
    $inHeader = true;

    $flushEntry = function () use (&$currentLines, &$headerRaw, &$singular, &$singularRaw, &$pluralRaw, &$entryIndex, &$inHeader) {
        if (empty($currentLines)) {
            return;
        }
        $entryText = implode("\n", $currentLines);
        $lines = $currentLines;
        $hasPlural = false;
        $msgidParts = [];
        $msgstrParts = [];
        $mode = null;

        foreach ($lines as $line) {
            $trim = ltrim($line);
            if ($trim === '') {
                continue;
            }
            if (preg_match('/^msgid_plural\s+"/', $trim)) {
                $hasPlural = true;
            }
            if (preg_match('/^msgid\s+"(.*)"/', $trim, $m)) {
                $mode = 'msgid';
                $msgidParts = [$m[1]];
                continue;
            }
            if (preg_match('/^msgstr(\[\d+\])?\s+"(.*)"/', $trim, $m)) {
                if (!isset($m[1]) || $m[1] === '') {
                    $mode = 'msgstr';
                    $msgstrParts = [$m[2]];
                } else {
                    // plural msgstr[n], ignore for singular parsing
                }
                continue;
            }
            if (preg_match('/^"(.*)"/', $trim, $m)) {
                if ($mode === 'msgid') {
                    $msgidParts[] = $m[1];
                } elseif ($mode === 'msgstr') {
                    $msgstrParts[] = $m[1];
                }
                continue;
            }
        }

        $msgid = $msgidParts ? poUnescape(implode('', $msgidParts)) : '';
        $msgstr = $msgstrParts ? poUnescape(implode('', $msgstrParts)) : '';

        if ($entryIndex === 0 && $msgid === '') {
            $headerRaw = $entryText;
            $inHeader = false;
        } else {
            if ($hasPlural) {
                $pluralRaw[$msgid] = $entryText;
            } else {
                $singular[$msgid] = $msgstr;
                $singularRaw[$msgid] = $entryText;
            }
        }

        $currentLines = [];
        $entryIndex++;
    };

    foreach ($lines as $line) {
        if (trim($line) === '') {
            $flushEntry();
        } else {
            $currentLines[] = $line;
        }
    }
    $flushEntry();

    return [
        'headerRaw'   => $headerRaw,
        'singular'    => $singular,
        'singularRaw' => $singularRaw,
        'pluralRaw'   => $pluralRaw,
    ];
}

/**
 * Decide if an existing translation should be considered "not translated"
 * and thus replaced.
 *
 * Rules (for non-English targets):
 * - If msgstr is empty      -> needs translation
 * - If msgstr has >2 words and equals msgid (case-insensitive) -> needs translation
 * - If msgstr has >10 words and >=60% of source words also appear in msgstr
 *   -> considered mostly English, needs translation
 */
function needsTranslationUpdate(string $msgid, string $msgstr, string $targetLang): bool {
    if ($targetLang === 'en_US') {
        return false;
    }

    $s = trim($msgstr);
    if ($s === '') {
        return true;
    }

    // Normalize whitespace and case; do NOT touch backslashes
    $src = mb_strtolower(preg_replace('/\s+/u', ' ', poUnescape($msgid)));
    $tgt = mb_strtolower(preg_replace('/\s+/u', ' ', poUnescape($s)));

    $tgtWords = preg_split('/\s+/u', $tgt, -1, PREG_SPLIT_NO_EMPTY);
    $srcWords = preg_split('/\s+/u', $src, -1, PREG_SPLIT_NO_EMPTY);

    $tgtWordCount = count($tgtWords);
    $srcWordCount = count($srcWords);

    if ($tgtWordCount > 1 && $srcWordCount > 0 && $tgt === $src) {
        return true;
    }

    if ($tgtWordCount > 10 && $srcWordCount > 0) {
        $srcSet = array_unique($srcWords);
        $tgtSet = array_unique($tgtWords);
        $common = array_intersect($srcSet, $tgtSet);
        $ratio = count($common) / max(1, count($srcSet));
        if ($ratio >= 0.6) {
            return true;
        }
    }

    return false;
}

/**
 * Append line to log file.
 */
function logAction(string $logFile, string $lang, string $msgid, string $action): void {
    $timestamp = date('Y-m-d H:i:s');
    $shortId = mb_substr(str_replace(["\n", "\r"], ' ', $msgid), 0, 80);
    $line = "[{$timestamp}] [{$lang}] action: {$action} | msgid: {$shortId}" . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND);
}

/**
 * Call Grok API to translate a batch of strings.
 * → Only malformed JSON is gracefully ignored (batch skipped).
 * → All other errors (cURL, HTTP 5xx, timeout, invalid structure) still throw exceptions.
 * @param string $apiUrl
 * @param string $apiKey
 * @param string $targetLangCode
 * @param string $targetLangName
 * @param array  $batchItems [ ['id'=>int, 'source'=>string], ... ]
 *
 * @return array [id => translation]
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

    $systemPrompt = <<<EOT
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
        $inputList[] = [
            'id'     => $item['id'],
            'source' => $item['source'],
        ];
    }

    $userPrompt = "Translate the following Chamilo LMS interface strings from English (source_language: en) "
        . "into {$targetLangName} (target_language code: {$targetLangCode}).\n"
        . "Return ONLY a valid JSON array, no extra text. Each array item MUST be an object with:\n"
        . "  - \"id\": the same integer id as in the input\n"
        . "  - \"translation\": the translated string\n\n"
        . "Do not change or remove any placeholders (e.g., %s, %d, {name}) or HTML tags.\n\n"
        . "Input:\n"
        . json_encode($inputList, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    $payload = [
        'model'    => 'grok-4-1-fast-non-reasoning',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $userPrompt],
        ],
        'temperature' => 0.2,
    ];

    $ch = curl_init($apiUrl);
    if ($ch === false) {
        throw new RuntimeException("Failed to initialize cURL.");
    }

    $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS     => $payloadJson,
        CURLOPT_TIMEOUT        => 30,
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

    // ——— SAFE JSON PARSING STARTS HERE ———
    $data = json_decode($responseBody, true);

    // Case 1: Full response is not valid JSON → treat as malformed, skip batch
    if (json_last_error() !== JSON_ERROR_NONE) {
        eprintln("[Grok] Invalid JSON in full response (" . json_last_error_msg() . ") – skipping batch.", true);
        return [];
    }

    // Case 2: Valid JSON, but missing expected structure
    if (!is_array($data) || !isset($data['choices'][0]['message']['content'])) {
        throw new RuntimeException("Unexpected Grok API response structure (missing choices/content).");
    }

    $content = $data['choices'][0]['message']['content'];

    // Extract the JSON array part
    $start = strpos($content, '[');
    $end   = strrpos($content, ']');
    if ($start === false || $end === false || $end <= $start) {
        eprintln("[Grok] No JSON array found in response content – skipping batch.", true);
        return [];
    }

    $jsonPart = substr($content, $start, $end - $start + 1);
    $translationsArray = json_decode($jsonPart, true);

    // Case 3: The extracted part is not valid JSON → ignore and continue
    if (json_last_error() !== JSON_ERROR_NONE) {
        eprintln("[Grok] Invalid JSON in extracted array (" . json_last_error_msg() . ") – skipping batch.", true);
        return [];
    }

    if (!is_array($translationsArray)) {
        eprintln("[Grok] Extracted JSON is not an array – skipping batch.", true);
        return [];
    }

    // ——— SUCCESS: Valid translations ———
    $result = [];
    foreach ($translationsArray as $item) {
        if (isset($item['id'], $item['translation']) && is_scalar($item['translation'])) {
            $result[(int)$item['id']] = (string)$item['translation'];
        }
    }

    return $result;
}

/**
 * Build new .po file content for a target language from base entries and translations.
 *
 * @param array  $baseEntries        Parsed base entries
 * @param array  $targetParsed       Parsed target file (headerRaw, singular, singularRaw, pluralRaw)
 * @param array  $targetTranslations Final msgstr for singular entries [msgid => msgstr]
 * @param array  $keepRawSingular    [msgid => true] for entries whose raw form must be preserved
 *
 * @return string
 */
function buildTargetPoContent(
    array $baseEntries,
    array $targetParsed,
    array $targetTranslations,
    array $keepRawSingular
): string {
    $out = '';

    // Header: keep existing header if present, otherwise use base header
    $headerWritten = false;
    foreach ($baseEntries as $index => $entry) {
        if ($entry['isHeader']) {
            if ($targetParsed['headerRaw'] !== null) {
                $out .= rtrim($targetParsed['headerRaw']) . "\n\n";
            } else {
                $out .= rtrim($entry['raw']) . "\n\n";
            }
            $headerWritten = true;
            break;
        }
    }

    if (!$headerWritten && $targetParsed['headerRaw'] !== null) {
        $out .= rtrim($targetParsed['headerRaw']) . "\n\n";
        $headerWritten = true;
    }

    // Now all other entries, strictly following base order
    foreach ($baseEntries as $entry) {
        if ($entry['isHeader']) {
            continue;
        }

        $msgid = $entry['msgid'];

        if ($entry['hasPlural']) {
            // Plural entries: keep target raw if exists, otherwise copy base raw
            if (isset($targetParsed['pluralRaw'][$msgid])) {
                $out .= rtrim($targetParsed['pluralRaw'][$msgid]) . "\n\n";
            } else {
                // Copy base raw; these are still in English and should be reviewed manually
                $out .= rtrim($entry['raw']) . "\n\n";
            }
            continue;
        }

        // Singular entry
        // If we explicitly decided to keep the existing translation as-is,
        // and we have its raw entry, just reuse it (keeps multiline layout).
        if (isset($keepRawSingular[$msgid], $targetParsed['singularRaw'][$msgid])) {
            $out .= rtrim($targetParsed['singularRaw'][$msgid]) . "\n\n";
            continue;
        }

        $comments = $entry['comments'];
        $translation = $targetTranslations[$msgid] ?? '';

        foreach ($comments as $cLine) {
            $out .= $cLine . "\n";
        }
        // msgid and translation are escaped with backslashes preserved exactly
        $out .= 'msgid "' . poEscape($msgid) . '"' . "\n";
        $out .= 'msgstr "' . poEscape($translation) . '"' . "\n\n";
    }

    return $out;
}

// ===================== MAIN SCRIPT =====================

// CLI args
$argvCopy = $argv;
array_shift($argvCopy); // drop the script name

$testMode = false;
$testKey = array_search('--test', $argvCopy, true);
if ($testKey !== false) {
    $testMode = true;
    unset($argvCopy[$testKey]);
    $argvCopy = array_values($argvCopy);
}

// Determine which languages to process
if (empty($argvCopy)) {
    eprintln("No languages specified. Scanning {$translationsDir} for messages.*.po files...", true);

    $poFiles = glob($translationsDir . 'messages.*.po');
    if ($poFiles === false || empty($poFiles)) {
        eprintln("No .po files found in {$translationsDir}", true);
        exit(1);
    }

    $langCodes = [];
    foreach ($poFiles as $file) {
        $filename = basename($file);
        if (!preg_match('/^messages\.(.+)\.po$/', $filename, $matches)) {
            continue;
        }
        $code = $matches[1];

        // Skip English and any template (.pot disguised as .po)
        // Tibetan (bo) is skipped because error-prone. Might be "unskipped" in the future. Execute manually (add 'po' parameter to command) to update.
        if ($code === 'en_US' || $code === 'bo_CN' || $code === 'pot') {
            continue;
        }

        $langCodes[] = $code;
    }

    if (empty($langCodes)) {
        eprintln("No translatable language files found (only English or template detected).", true);
        exit(0);
    }

    // Sort alphabetically for consistent processing order
    sort($langCodes);

    eprintln("Auto-detected languages: " . implode(', ', $langCodes), true);
} else {
    $langCodes = $argvCopy;
}

if (!is_file($basePoFile)) {
    eprintln("Base PO file not found: {$basePoFile}");
    exit(1);
}

if ($apiKey === '' || $apiKey === 'YOUR_GROK_API_KEY_HERE') {
    eprintln("Warning: Grok API key is not set or still has the placeholder value.");
    eprintln("Please edit this script and set \$apiKey at the top.");
}

// Parse base messages.en.po
eprintln("Loading base file: {$basePoFile}", true);
$baseEntries = parseBasePoFile($basePoFile);
$totalTerms = count($baseEntries);
eprintln("Base entries loaded: {$totalTerms} (including header and plurals).", true);

foreach ($langCodes as $lang) {
    $lang = trim($lang);
    if ($lang === '') {
        continue;
    }

    $targetLangName = getLanguageName($lang);
    $targetFile = $translationsDir."messages.{$lang}.po";
    eprintln("------------------------------------------------------------");
    eprintln("Processing language: {$lang} ({$targetLangName})", true);
    eprintln("Target file: {$targetFile}");

    // Backup logic (unchanged)
    if (is_file($targetFile)) {
        $backupFile = $targetFile . '.bak';
        if (!is_file($backupFile)) {
            if (!copy($targetFile, $backupFile)) {
                eprintln("Warning: Could not create backup file: {$backupFile}");
            } else {
                eprintln("Backup created: {$backupFile}");
            }
        } else {
            eprintln("Backup already exists: {$backupFile}");
        }
    } else {
        eprintln("Target file does not exist; it will be created.");
    }

    $targetParsed = parseTargetPoFile($targetFile);
    $existingSingular = $targetParsed['singular'];

    $targetTranslations = [];
    $keepRawSingular = [];
    $processedCount = 0;
    $apiBatchCount = 0;
    $maxBatches = $testMode ? $batchSizeInTestMode : PHP_INT_MAX;

    $pendingBatch = [];
    $pendingMap = []; // id => ['msgid'=>..., 'action'=>...]

    $entryIndex = 0;

    // Helper to write current state to disk (used on success AND on failure)
    $writeCurrentState = function () use (
        &$targetTranslations,
        &$keepRawSingular,
        $baseEntries,
        $targetParsed,
        $targetFile,
        $lang
    ) {
        $newContent = buildTargetPoContent($baseEntries, $targetParsed, $targetTranslations, $keepRawSingular);
        if (file_put_contents($targetFile, $newContent) !== false) {
            eprintln("[{$lang}] Partial translation file successfully written after error.", true);
        } else {
            eprintln("[{$lang}] WARNING: Failed to write partial file!", true);
        }
    };

    try {
        foreach ($baseEntries as $entry) {
            $entryIndex++;

            if ($entry['isHeader'] || $entry['hasPlural']) {
                $processedCount++;
                if ($processedCount % 50 === 0) {
                    eprintln("[{$lang}] Progress: {$processedCount} / {$totalTerms} entries processed (header/plurals included).",
                        true);
                }
                continue;
            }

            $msgid = $entry['msgid'];
            $existing = $existingSingular[$msgid] ?? '';

            $needsTranslation = false;
            $action = 'ignored';

            if (!array_key_exists($msgid, $existingSingular)) {
                $needsTranslation = true;
                $action = 'translation added';
            } else {
                if (needsTranslationUpdate($msgid, $existing, $lang)) {
                    $needsTranslation = true;
                    $action = ($existing === '') ? 'translation added' : 'translation updated';
                } else {
                    $targetTranslations[$msgid] = $existing;
                    $keepRawSingular[$msgid] = true;
                    $action = 'ignored';
                    logAction($logFile, $lang, $msgid, $action);
                }
            }

            if ($needsTranslation && $apiBatchCount < $maxBatches) {
                $localId = count($pendingBatch);
                $pendingBatch[] = [
                    'id' => $localId,
                    'source' => $msgid,
                ];
                $pendingMap[$localId] = [
                    'msgid' => $msgid,
                    'action' => $action,
                ];

                // Send batch when full
                if (count($pendingBatch) >= $batchSize) {
                    $apiBatchCount++;
                    eprintln("[{$lang}] Sending batch {$apiBatchCount} to Grok API ("
                        .count($pendingBatch)." terms).", true);

                    $batchSuccess = false;
                    try {
                        $translations = callGrokTranslateBatch(
                            $apiUrl,
                            $apiKey,
                            $lang,
                            $targetLangName,
                            $pendingBatch
                        );
                        eprintln("[{$lang}] Grok API batch {$apiBatchCount} completed, "
                            .count($translations)." translations received.", true);
                        $batchSuccess = true;
                    } catch (Throwable $ex) {
                        eprintln("[{$lang}] API ERROR in batch {$apiBatchCount}: ".$ex->getMessage(), true);
                        eprintln("[{$lang}] Skipping this batch. All previous batches are preserved.", true);
                        // Do NOT re-throw — we continue with next entries
                    }

                    if ($batchSuccess) {
                        foreach ($pendingBatch as $item) {
                            $id = $item['id'];
                            $msgidBatch = $pendingMap[$id]['msgid'];
                            $actionBatch = $pendingMap[$id]['action'];
                            $translated = $translations[$id] ?? '';

                            if ($translated === '') {
                                $translated = $existingSingular[$msgidBatch] ?? '';
                                if (isset($existingSingular[$msgidBatch])) {
                                    $keepRawSingular[$msgidBatch] = true;
                                }
                            }

                            $targetTranslations[$msgidBatch] = $translated;
                            logAction($logFile, $lang, $msgidBatch, $actionBatch);
                        }
                        sleep(1);
                    } else {
                        // On failure: keep existing translations (or empty) and preserve raw formatting
                        foreach ($pendingBatch as $item) {
                            $id = $item['id'];
                            $msgidBatch = $pendingMap[$id]['msgid'];
                            $translated = $existingSingular[$msgidBatch] ?? '';
                            $targetTranslations[$msgidBatch] = $translated;
                            if ($translated !== '') {
                                $keepRawSingular[$msgidBatch] = true;
                            }
                            logAction($logFile, $lang, $msgidBatch, 'failed – kept original');
                        }
                    }

                    // Always clear batch after processing (success or fail)
                    $pendingBatch = [];
                    $pendingMap = [];

                    if ($testMode && $apiBatchCount >= $maxBatches) {
                        eprintln("[{$lang}] Test mode limit reached.", true);
                    }
                }
            } elseif ($needsTranslation && $apiBatchCount >= $maxBatches) {
                // Test mode or limit reached → keep existing
                $targetTranslations[$msgid] = $existing;
                $keepRawSingular[$msgid] = true;
                logAction($logFile, $lang, $msgid, 'ignored (limit reached)');
            }

            $processedCount++;
            if ($processedCount % 50 === 0) {
                eprintln("[{$lang}] Progress: {$processedCount} / {$totalTerms} entries processed.", true);
            }
        }

        // Final batch (if any)
        if (!empty($pendingBatch) && $apiBatchCount < $maxBatches) {
            $apiBatchCount++;
            eprintln("[{$lang}] Sending final batch {$apiBatchCount} to Grok API ("
                .count($pendingBatch)." terms).", true);

            $finalSuccess = false;
            try {
                $translations = callGrokTranslateBatch(
                    $apiUrl,
                    $apiKey,
                    $lang,
                    $targetLangName,
                    $pendingBatch
                );
                eprintln("[{$lang}] Final batch completed.", true);
                $finalSuccess = true;
            } catch (Throwable $ex) {
                eprintln("[{$lang}] FINAL BATCH FAILED: ".$ex->getMessage(), true);
                eprintln("[{$lang}] Writing file with all previously translated batches.", true);
            }

            if ($finalSuccess) {
                foreach ($pendingBatch as $item) {
                    $id = $item['id'];
                    $msgidBatch = $pendingMap[$id]['msgid'];
                    $actionBatch = $pendingMap[$id]['action'];
                    $translated = $translations[$id] ?? '';
                    if ($translated === '') {
                        $translated = $existingSingular[$msgidBatch] ?? '';
                        if (isset($existingSingular[$msgidBatch])) {
                            $keepRawSingular[$msgidBatch] = true;
                        }
                    }
                    $targetTranslations[$msgidBatch] = $translated;
                    logAction($logFile, $lang, $msgidBatch, $actionBatch);
                }
            } else {
                // Keep existing or leave empty for failed items
                foreach ($pendingBatch as $item) {
                    $id = $item['id'];
                    $msgidBatch = $pendingMap[$id]['msgid'];
                    $translated = $existingSingular[$msgidBatch] ?? '';
                    $targetTranslations[$msgidBatch] = $translated;
                    if ($translated !== '') {
                        $keepRawSingular[$msgidBatch] = true;
                    }
                    logAction($logFile, $lang, $msgidBatch, 'failed – kept original');
                }
            }
        }

        // Final write on success
        $writeCurrentState();
        eprintln("[{$lang}] Translation completed and file written successfully.", true);

    } catch (Throwable $fatal) {
        // Any unexpected fatal error outside batch processing
        eprintln("[{$lang}] FATAL ERROR: ".$fatal->getMessage(), true);
        eprintln("[{$lang}] Attempting to save partial progress...", true);
        $writeCurrentState();
        throw $fatal; // re-throw so you know something went very wrong
    }
}

eprintln("All requested languages processed.", true);

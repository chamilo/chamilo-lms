<?php

/* For licensing terms, see /license.txt */

if (PHP_SAPI != 'cli') {
    die('This script can only be executed from the command line');
}

/**
 * Script to scan Chamilo LMS master branch for translatable terms and compare with messages.pot
 */

$showAll = false;
$apply = false;
foreach ($argv as $arg) {
    if ($arg === '--all') {
        $showAll = true;
    }
    if ($arg === '--apply') {
        $apply = true;
    }
}

$root = realpath(__DIR__ . '/../../../');

if (!file_exists($root . '/.git')) {
    die("Project root not found. Ensure script is run from tests/scripts/lang/.\n");
}

$potPath = $root . '/translations/messages.pot';
$poPath = $root . '/translations/messages.en_US.po';
$jsonPath = $root . '/assets/locales/en_US.json';

// Regex fragment for matching quoted string content, handling escaped characters.
// Use with a captured opening quote in group N: (["']) then QUOTED_CONTENT then \N
// The content group handles:
//   - \\. (any escaped character, including \', \", \\)
//   - (?!\N). (any character that is not the closing quote)
define('QUOTED_CONTENT', '((?:\\\\.|(?!\\1)[^\\\\])*)');

// Parse messages.pot to get all msgids
$msgids = parsePotFile($potPath);

$missing = [];
// For terms found in assets/vue, keep the original term (with {0}/{1} or %s/%d
// placeholders exactly as written in the source) keyed by its normalized lookup
// term, so it can be added to assets/locales/en_US.json with --apply.
$vueTerms = [];

//$dirsToScan = ['assets', 'public', 'src', 'tests'];
$dirsToScan = ['assets', 'public', 'src'];
$dirsToAvoid = ['public/plugin'];

$termIndex = 1;

foreach ($dirsToScan as $dir) {
    echo "Scanning subdirectory: $dir\n";
    $fullDir = $root . '/' . $dir;

    if (!is_dir($fullDir)) {
        echo "Directory $dir does not exist. Skipping.\n";
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($fullDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $file) {
        if ($file->isDir()) {
            continue;
        }

        $path = $file->getPathname();

        $relPath = str_replace($root . '/', '', $path);
        $isVue = str_contains($relPath, 'assets/vue/');
        foreach ($dirsToAvoid as $dirToAvoid) {
            if (str_contains($relPath, $dirToAvoid)) {
                continue 2;
            }
        }

        $lines = file($path);
        foreach ($lines as $num => $line) {
            $terms = extractTermsFromLine($line, $isVue);
            $hiddenTerms = extractHiddenTermsFromLine($line, $isVue);
            foreach ($terms as $term) {
                $lookupTerm = normalizePlaceholders($term, $isVue);
                $isMissing = !isset($msgids[$lookupTerm]);
                if ($showAll || $isMissing) {
                    echo "[".str_pad($termIndex,5, ' ', STR_PAD_LEFT)."] Found \"{$term}\" in {$path}:" . ($num + 1) . "\n";
                }
                $termIndex++;
                if ($isMissing) {
                    echo "\033[31m Missing in messages.pot\033[0m\n";
                    $missing[$lookupTerm] = true;
                    if ($isVue && !isset($vueTerms[$lookupTerm])) {
                        $vueTerms[$lookupTerm] = $term;
                    }
                }
            }
            foreach ($hiddenTerms as $term) {
                $lookupTerm = normalizePlaceholders($term, $isVue);
                $isMissing = !isset($msgids[$lookupTerm]);
                if ($isMissing) {
                    $missing[$lookupTerm] = true;
                    if ($isVue && !isset($vueTerms[$lookupTerm])) {
                        $vueTerms[$lookupTerm] = $term;
                    }
                }
            }
        }
    }
}

// Write missing terms to /tmp/missing_terms.txt in .pot format
if (!empty($missing)) {
    $output = '';
    foreach (array_keys($missing) as $term) {
        $escaped = escape_pot($term);
        $output .= "msgid \"{$escaped}\"\nmsgstr \"\"\n\n";
    }
    file_put_contents('/tmp/missing_terms.txt', $output);
}

if ($apply) {
    applyMissingTerms(array_keys($missing), $vueTerms, $potPath, $poPath, $jsonPath, $msgids);
}

/**
 * Append the missing terms to messages.pot and messages.en_US.po, and add the
 * Vue-sourced terms to assets/locales/en_US.json (converting placeholders).
 *
 * @param string[] $missingTerms Normalized lookup terms missing from messages.pot
 * @param array<string, string> $vueTerms lookupTerm => original Vue source term
 * @param array<string, bool> $potMsgids Already-parsed messages.pot msgids
 */
function applyMissingTerms(
    array $missingTerms,
    array $vueTerms,
    string $potPath,
    string $poPath,
    string $jsonPath,
    array $potMsgids
): void {
    if (empty($missingTerms)) {
        echo "\nNothing to apply: no missing terms found.\n";

        return;
    }

    // --- messages.pot ---
    // All $missingTerms are by definition absent from the .pot, so append them all.
    $potAppend = '';
    foreach ($missingTerms as $term) {
        $escaped = escape_pot($term);
        $potAppend .= "\nmsgid \"{$escaped}\"\nmsgstr \"\"\n";
    }
    file_put_contents($potPath, $potAppend, FILE_APPEND);
    echo "\nAppended ".count($missingTerms)." term(s) to ".basename($potPath).".\n";

    // --- messages.en_US.po ---
    // Skip any term already present in the .po (msgstr mirrors the English source).
    $poMsgids = parsePotFile($poPath);
    $poAppend = '';
    $poCount = 0;
    foreach ($missingTerms as $term) {
        if (isset($poMsgids[$term])) {
            continue;
        }
        $escaped = escape_pot($term);
        $poAppend .= "\nmsgid \"{$escaped}\"\nmsgstr \"{$escaped}\"\n";
        $poCount++;
    }
    if ('' !== $poAppend) {
        file_put_contents($poPath, $poAppend, FILE_APPEND);
    }
    echo "Appended {$poCount} term(s) to ".basename($poPath).".\n";

    // --- assets/locales/en_US.json ---
    if (empty($vueTerms)) {
        echo "No Vue-sourced terms to add to ".basename($jsonPath).".\n";

        return;
    }

    $json = file_get_contents($jsonPath);
    $existing = json_decode($json, true);
    if (!is_array($existing)) {
        echo "Could not parse ".basename($jsonPath)."; skipping JSON update.\n";

        return;
    }

    $newLines = [];
    foreach ($vueTerms as $term) {
        if (isset($existing[$term])) {
            continue;
        }
        $existing[$term] = true; // guard against duplicate additions within this run
        $key = json_encode($term);
        $value = json_encode(toJsonValue($term));
        $newLines[] = '    '.$key.': '.$value;
    }

    if (empty($newLines)) {
        echo "No new Vue-sourced terms to add to ".basename($jsonPath).".\n";

        return;
    }

    // Insert the new entries before the closing brace, keeping the existing
    // formatting (4-space indent, trailing newline) untouched.
    $body = rtrim($json);
    $closePos = strrpos($body, '}');
    $head = rtrim(substr($body, 0, $closePos));
    $result = $head.",\n".implode(",\n", $newLines)."\n}\n";
    file_put_contents($jsonPath, $result);
    echo 'Added '.count($newLines).' term(s) to '.basename($jsonPath).".\n";
}

/**
 * Convert a Vue source term into the JSON value form: sequential printf-style
 * placeholders (%s, %d, %1$s) become positional vue-i18n placeholders {0}, {1}.
 * Terms that already use {n} placeholders are left unchanged.
 */
function toJsonValue(string $term): string
{
    $index = 0;

    return preg_replace_callback(
        '/%(?:\d+\$)?[sd]/',
        static function () use (&$index): string {
            return '{'.$index++.'}';
        },
        $term
    );
}

function parsePotFile(string $filePath): array
{
    if (!file_exists($filePath)) {
        die("messages.pot not found.\n");
    }

    $lines = file($filePath);
    $msgids = [];
    $currentMsgid = null;
    $isMsgid = false;

    foreach ($lines as $line) {
        $line = trim($line);

        if (strpos($line, 'msgid ') === 0) {
            if ($currentMsgid !== null && $currentMsgid !== '') {
                registerPotMsgid($msgids, $currentMsgid);
            }
            if (preg_match('/msgid\s+"(.*)"/', $line, $matches)) {
                $currentMsgid = $matches[1];
            } elseif ($line === 'msgid ""') {
                $currentMsgid = '';
            }
            $isMsgid = true;
        } elseif ($isMsgid && preg_match('/^"(.*)"/', $line, $matches)) {
            $currentMsgid .= $matches[1];
        } elseif ($currentMsgid !== null && (strpos($line, 'msgstr') === 0 || $line === '')) {
            if ($currentMsgid !== null && $currentMsgid !== '') {
                registerPotMsgid($msgids, $currentMsgid);
            }
            $currentMsgid = null;
            $isMsgid = false;
        }
    }

    // Handle last one if no empty line at end
    if ($currentMsgid !== null && $currentMsgid !== '') {
        registerPotMsgid($msgids, $currentMsgid);
    }

    return $msgids;
}

/**
 * Register a .pot msgid under both its unescaped form (for double-quoted source
 * strings where \t is a real tab) and its raw escaped form (for single-quoted
 * source strings where \t stays as literal backslash + t).
 */
function registerPotMsgid(array &$msgids, string $rawMsgid): void
{
    $unescaped = unescape_double($rawMsgid);
    $msgids[$unescaped] = true;
    // Also register the raw form so that single-quoted PHP sources
    // like get_lang('Substraction:\t\t\t-') match the .pot entry
    if ($rawMsgid !== $unescaped) {
        $msgids[$rawMsgid] = true;
    }
}

function extractTermsFromLine(string $line, bool $isVue): array
{
    $terms = [];

    if ($isVue) {
        // 1. v-t="'term'" or v-t='"term"'
        preg_match_all('/\bv-t\s*=\s*(["\'])'.QUOTED_CONTENT.'\1/', $line, $matches);
        addUnescapedTerms($matches, $terms);

        // 2. {{ $t("term") }}
        preg_match_all('/\{\{\s*\$t\s*\(\s*(["\'])'.QUOTED_CONTENT.'\1\s*(?:,.*?)?\)\s*\}\}/', $line, $matches);
        addUnescapedTerms($matches, $terms);

        // 3. :label="$t('term')"
        preg_match_all('/:\w+\s*=\s*["\']\$t\s*\(\s*(["\'])'.QUOTED_CONTENT.'\1\s*(?:,.*?)?\)["\']/', $line, $matches);
        addUnescapedTerms($matches, $terms);

        // 4. t("term") - exact t(
        preg_match_all('/\bt\s*\(\s*(["\'])'.QUOTED_CONTENT.'\1\s*(?:,.*?)?\)/', $line, $matches);
        addUnescapedTerms($matches, $terms);
    } else {
        // get_lang("term")
        preg_match_all('/(?<!\$this->)(?<!\$plugin->)\bget_lang\s*\(\s*(["\'])'.QUOTED_CONTENT.'\1\s*(?:,.*)?\)/', $line, $matches);
        addUnescapedTerms($matches, $terms);

        // trans("term"), ->trans("term"), .trans("term")
        preg_match_all('/(?:->|\.)?trans\s*\(\s*(["\'])'.QUOTED_CONTENT.'\1\s*(?:,.*)?\)/', $line, $matches);
        addUnescapedTerms($matches, $terms);

        // 'display_text' => 'term',
        preg_match_all('/\'display_text\'\s*=>\s*\'((?:[^\'\\\\]|\\\\.)*)\'/', $line, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $term) {
                $terms[] = unescape_single($term);
            }
        }
    }

    return $terms;
}

function extractHiddenTermsFromLine(string $line, bool $isVue): array
{
    $terms = [];

    if ($isVue) {
        return $terms;
    }

    // $this->get_lang("term") or $plugin->get_lang("term")
    // Group 1: this|plugin, Group 2: quote, Group 3: term content
    preg_match_all('/\$(this|plugin)->get_lang\s*\(\s*(["\'])((?:\\\\.|(?!\2)[^\\\\])*)\2\s*(?:,.*)?\)/', $line, $matches);
    // Shift groups: addUnescapedTerms expects group 1=quote, group 2=term
    if (!empty($matches[0])) {
        $shifted = [
            $matches[0],
            $matches[2], // quote character
            $matches[3], // term content
        ];
        addUnescapedTerms($shifted, $terms);
    }

    return $terms;
}

/**
 * Normalize placeholders so Vue {0},{1},... match .pot %s,%d,... entries.
 * Also handles named Vue i18n placeholders like {count} → %s.
 */
function normalizePlaceholders(string $term, bool $isVue): string
{
    if ($isVue) {
        // Convert {0}, {1}, {n}, {count}, {name}, etc. to %s for .pot lookup
        return preg_replace('/\{[0-9a-z_]+\}/i', '%s', $term);
    }

    return $term;
}

/**
 * Escape a term for .pot msgid output (double-quoted string).
 */
function escape_pot(string $str): string
{
    return strtr($str, [
        '\\' => '\\\\',
        '"' => '\\"',
        "\n" => '\\n',
        "\r" => '\\r',
        "\t" => '\\t',
    ]);
}

function addUnescapedTerms(array $matches, array &$terms): void
{
    foreach ($matches[2] ?? [] as $key => $term) {
        $quote = $matches[1][$key];
        if ($quote === '"') {
            $term = unescape_double($term);
        } else {
            $term = unescape_single($term);
        }
        $first = substr($term, 0, 1);
        $last = substr($term, -1);
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            $term = substr($term, 1, strlen($term) - 2);
        }
        $terms[] = $term;
    }
}

function unescape_double(string $str): string
{
    $escapes = [
        '\\\\' => '\\',
        '\\"' => '"',
        "\\'" => "'",
        '\\n' => "\n",
        '\\r' => "\r",
        '\\t' => "\t",
        '\\v' => "\v",
        '\\f' => "\f",
        '\\e' => "\e",
        '\\$' => '$',
    ];
    return strtr($str, $escapes);
}

function unescape_single(string $str): string
{
    $escapes = [
        '\\\\' => '\\',
        "\\'" => "'",
    ];
    return strtr($str, $escapes);
}

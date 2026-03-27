<?php

/* For licensing terms, see /license.txt */

if (PHP_SAPI != 'cli') {
    die('This script can only be executed from the command line');
}

/**
 * Script to scan Chamilo LMS master branch for translatable terms and compare with messages.pot
 */

$showAll = false;
foreach ($argv as $arg) {
    if ($arg === '--all') {
        $showAll = true;
        break;
    }
}

$root = realpath(__DIR__ . '/../../../');

if (!file_exists($root . '/.git')) {
    die("Project root not found. Ensure script is run from tests/scripts/lang/.\n");
}

$potPath = $root . '/translations/messages.pot';

// Regex fragment for matching quoted string content, handling escaped characters.
// Use with a captured opening quote in group N: (["']) then QUOTED_CONTENT then \N
// The content group handles:
//   - \\. (any escaped character, including \', \", \\)
//   - (?!\N). (any character that is not the closing quote)
define('QUOTED_CONTENT', '((?:\\\\.|(?!\\1)[^\\\\])*)');

// Parse messages.pot to get all msgids
$msgids = parsePotFile($potPath);

$missing = [];

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
                }
            }
            foreach ($hiddenTerms as $term) {
                $lookupTerm = normalizePlaceholders($term, $isVue);
                $isMissing = !isset($msgids[$lookupTerm]);
                if ($isMissing) {
                    $missing[$lookupTerm] = true;
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
        // Convert {0}, {1}, {count}, {name}, etc. to %s for .pot lookup
        return preg_replace('/\{[0-9]+\}/', '%s', $term);
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

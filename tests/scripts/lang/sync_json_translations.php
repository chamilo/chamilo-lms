<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

/**
 * Sync assets/locales/<iso>.json values from translations/messages.<iso>.po.
 *
 * Background: chamilo:update_vue_translations rewrites every value of every language
 * from the Symfony translator, so one missing translation regresses a JSON entry to
 * English. It also needs a working container and database. This script needs neither:
 * it reads the .po files directly and only ever writes a value it found there.
 *
 * It covers the languages shipped with the code. A sublanguage is not one of them: its
 * catalogue lives in var/translations/ and LocaleController serves it at runtime, so it
 * has no file here to sync.
 *
 * It is surgical: for every key in en_US.json (the source-of-truth key list),
 * if the matching .po entry has a real (non-empty) translation, the JSON value
 * is set to it (placeholder markers %s/%d/%f -> {0}/{1}/{2}, same transform as
 * the command). Keys with no .po translation are left exactly as they are
 * (English fallback preserved), and keys not present in en_US.json (orphans)
 * are left untouched. Nothing is emptied or deleted.
 *
 * Usage (from project root):
 *   php tests/scripts/lang/sync_json_translations.php              # dry-run, all languages
 *   php tests/scripts/lang/sync_json_translations.php --apply      # write changes
 *   php tests/scripts/lang/sync_json_translations.php --lang=es    # restrict to one iso
 *   php tests/scripts/lang/sync_json_translations.php --lang=es --apply
 */

use Chamilo\CoreBundle\Translation\VueTranslationsBuilder;

$projectDir = \dirname(__DIR__, 3);

// Only the autoloader, never the kernel: the transforms below are pure, and staying out
// of the container is what lets this script run when bin/console cannot.
require_once $projectDir.'/vendor/autoload.php';

$localeDir = $projectDir.'/assets/locales/';
$poDir = $projectDir.'/translations/';

$apply = \in_array('--apply', $argv, true);
$onlyLang = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--lang=')) {
        $onlyLang = substr($arg, 7);
    }
}

/**
 * Parse a .po file into [msgid => msgstr], decoding C/JSON-style escapes and
 * concatenating multi-line strings. Plural forms (msgid_plural / msgstr[n]) and
 * msgctxt-qualified entries are ignored — irrelevant for the vue key set.
 *
 * @return array<string, string>
 */
function parsePo(string $path): array
{
    $entries = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if (false === $lines) {
        return $entries;
    }

    $current = null;          // 'id' | 'str' | null
    $msgid = null;
    $msgstr = null;
    $hasContext = false;
    $isPlural = false;

    $decode = static function (string $line, string $keyword): string {
        $token = trim(substr($line, \strlen($keyword)));
        $decoded = json_decode($token, true);

        return \is_string($decoded) ? $decoded : '';
    };

    $flush = static function () use (&$entries, &$msgid, &$msgstr, &$hasContext, &$isPlural): void {
        if (null !== $msgid && null !== $msgstr && '' !== $msgid && !$hasContext && !$isPlural) {
            $entries[$msgid] = $msgstr;
        }
    };

    foreach ($lines as $line) {
        if (str_starts_with($line, 'msgctxt ')) {
            $flush();
            $msgid = null;
            $msgstr = null;
            $hasContext = true;
            $isPlural = false;
            $current = null;

            continue;
        }

        if (str_starts_with($line, 'msgid_plural ')) {
            $isPlural = true;
            $current = null;

            continue;
        }

        if (str_starts_with($line, 'msgid ')) {
            $flush();
            $msgid = $decode($line, 'msgid ');
            $msgstr = null;
            $hasContext = false;
            $isPlural = false;
            $current = 'id';

            continue;
        }

        if (str_starts_with($line, 'msgstr ')) {
            // msgstr[0]/msgstr[1]... start with 'msgstr[' — those are plural, skip via $isPlural.
            $msgstr = $decode($line, 'msgstr ');
            $current = 'str';

            continue;
        }

        if (str_starts_with($line, '"')) {
            $part = json_decode($line, true);
            $part = \is_string($part) ? $part : '';
            if ('id' === $current) {
                $msgid .= $part;
            } elseif ('str' === $current) {
                $msgstr .= $part;
            }

            continue;
        }

        // Blank line or comment terminates the entry.
        if ('' === trim($line) || str_starts_with($line, '#')) {
            // comments may appear between blocks; only blank lines reset state safely
            if ('' === trim($line)) {
                $current = null;
            }
        }
    }
    $flush();

    return $entries;
}

/**
 * Compute the correct vue value for a given en_US key from a parsed .po map, or null
 * when the .po has no real translation. The %s-then-%d lookup and the gettext to
 * vue-i18n transform come from VueTranslationsBuilder, shared with the command.
 *
 * @param array<string, string> $po
 */
function correctValue(string $key, array $po): ?string
{
    $gid = VueTranslationsBuilder::toGettextKey($key);
    $msgstr = $po[$gid] ?? null;

    if (null === $msgstr || '' === $msgstr) {
        $gidAlt = VueTranslationsBuilder::toGettextKey($key, true);
        $msgstr = $po[$gidAlt] ?? null;
    }

    if (null === $msgstr || '' === $msgstr) {
        return null;
    }

    return VueTranslationsBuilder::toVueValue($msgstr);
}

// --- main ---------------------------------------------------------------

$englishRaw = file_get_contents($localeDir.'en_US.json');
if (false === $englishRaw) {
    fwrite(STDERR, "Cannot read en_US.json\n");

    exit(1);
}
$englishKeys = array_keys(json_decode($englishRaw, true));

$jsonFiles = glob($localeDir.'*.json');
sort($jsonFiles);

$grandFixed = 0;
$grandChanged = 0;
$grandFilesTouched = 0;
$processedLangs = 0;
/** @var array<string, list<string>> $leftEnglishByKey key => isos still using the English value */
$leftEnglishByKey = [];

printf("Mode: %s\n\n", $apply ? 'APPLY (writing files)' : 'DRY-RUN (no files written)');
printf("%-10s %8s %8s %8s %8s %8s\n", 'iso', 'fixed', 'changed', 'left-en', 'po-miss', 'orphans');
printf("%s\n", str_repeat('-', 60));

foreach ($jsonFiles as $jsonFile) {
    $iso = basename($jsonFile, '.json');
    if ('en_US' === $iso) {
        continue;
    }
    if (null !== $onlyLang && $iso !== $onlyLang) {
        continue;
    }

    $poFile = $poDir.'messages.'.$iso.'.po';
    if (!is_file($poFile)) {
        printf("%-10s  (no messages.%s.po — skipped)\n", $iso, $iso);

        continue;
    }

    $processedLangs++;
    $po = parsePo($poFile);
    $existing = json_decode((string) file_get_contents($jsonFile), true);
    if (!\is_array($existing)) {
        printf("%-10s  (unreadable json — skipped)\n", $iso);

        continue;
    }

    $updated = $existing;     // preserve order + orphan keys
    $fixed = 0;               // English fallback -> real translation
    $changed = 0;             // value differed for another reason
    $leftEnglish = 0;         // had no real translation, kept as English/value
    $poMissing = 0;           // key absent from this .po entirely

    foreach ($englishKeys as $key) {
        $correct = correctValue($key, $po);
        $hadKey = \array_key_exists($key, $existing);
        $oldVal = $hadKey ? $existing[$key] : null;

        if (null === $correct) {
            // No real translation in the .po: leave existing value untouched.
            if (!\array_key_exists(VueTranslationsBuilder::toGettextKey($key), $po)
                && !\array_key_exists(VueTranslationsBuilder::toGettextKey($key, true), $po)) {
                $poMissing++;
            }
            if ($hadKey && $oldVal === $key) {
                $leftEnglish++;
                $leftEnglishByKey[$key][] = $iso;
            }

            continue;
        }

        if ($oldVal === $correct) {
            continue; // already correct
        }

        $updated[$key] = $correct;
        if ($oldVal === $key) {
            $fixed++;   // was the raw English key, now translated
        } else {
            $changed++; // was something else (stale/different)
        }
    }

    $orphans = \count(array_diff(array_keys($existing), $englishKeys));

    printf("%-10s %8d %8d %8d %8d %8d\n", $iso, $fixed, $changed, $leftEnglish, $poMissing, $orphans);

    $grandFixed += $fixed;
    $grandChanged += $changed;

    if ($apply && ($fixed > 0 || $changed > 0)) {
        $encoded = json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $encoded = str_replace('</br>', '<br>', $encoded);
        file_put_contents($jsonFile, $encoded);
        $grandFilesTouched++;
    }
}

printf("%s\n", str_repeat('-', 60));
printf("Totals: fixed=%d changed=%d%s\n", $grandFixed, $grandChanged, $apply ? " filesWritten={$grandFilesTouched}" : '');
if (!$apply) {
    if ([] !== $leftEnglishByKey) {
        ksort($leftEnglishByKey, \SORT_NATURAL | \SORT_FLAG_CASE);
        printf(
            "\nTerms left in English (%d unique; no real .po translation, JSON still the English key):\n",
            \count($leftEnglishByKey)
        );
        foreach ($leftEnglishByKey as $term => $isos) {
            $suffix = '';
            if ($processedLangs > 1 && \count($isos) < $processedLangs) {
                $suffix = '  ['.implode(', ', $isos).']';
            }
            echo '  '.$term.$suffix."\n";
        }
    }

    echo "\nThis was a DRY-RUN. Re-run with --apply to write the changes.\n";
}

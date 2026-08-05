<?php

/**
 * Extracts untranslated (or optionally still-English) entries from a language's
 * PO file by diffing it against the English reference PO file, keyed by msgid.
 *
 * Both input files are read from the `translations/` directory. For every msgid
 * present in the English file, an entry is written to the output file when it is
 * missing from the target-language file entirely, or present with an empty
 * msgstr, or (with --include-english) present but identical to the English text.
 * The output keeps the target file's PO header and writes each selected entry
 * with an empty msgstr, ready to be filled in with translations.
 *
 * Usage:   php extract_missing_po.php <messages.en.po> <messages.[language].po> [--include-english]
 * Example: php extract_missing_po.php messages.en.po messages.ar.po
 * Outputs: /tmp/missing_terms_[language].po
 *
 * --include-english: also include terms whose msgstr equals the msgid
 *                     (i.e. left untranslated as English); default is to
 *                     only include entries with an empty msgstr.
 *
 * The resulting missing_terms_[language].po can be handed to an LLM (see the
 * prompt template below) to produce a completed_terms_[language].po, which can
 * then be fed into merge_translations_po.php to merge the translations back in.
 */

// The results of this script can be fed to an LLM with the following prompt, and this result can be fed to merge_translations_po.php
/*
You are an expert translator specializing in software user interfaces, particularly for learning management systems
like Chamilo LMS. Translate the attached PO file (missing_terms_[language].po) into natural, idiomatic [language].
These are UI elements such as button labels, form fields, error messages, and tooltips—keep translations concise,
user-friendly, and consistent with Chamilo's style (e.g., polite/formal tone where appropriate; retain technical
terms if they appear).

Rules:

Preserve the exact PO format: headers, comments (# lines), msgid lines (unchanged), and multi-line continuations (").
Translate ONLY the content inside msgstr "..." (or multi-line msgstr continuations). If msgstr is empty (""), fill it with the translation.
Do not translate msgids, line numbers, references (#:), or any metadata.
Handle plurals/variables naturally (e.g., %s, %d placeholders stay as-is; translate around them).
For HTML in strings, keep tags intact and translate text content only.
Output the FULL translated PO file as a <DOCUMENT> block, ready to save as completed_terms_[language].po.
If a string is already translated (non-empty msgstr not matching msgid), leave it unchanged—but since this is missing terms, assume all need translation.
Contents of missing_terms_[language].po are attached.
Provide only the <DOCUMENT> with the translated PO—no introductions or extra text.
*/

declare(strict_types=1);

if ($argc < 3) {
    echo "Usage: php extract_missing_po.php <messages.en.po> <messages.[language].po> [--include-english]\n";
    exit(1);
}

$langDir = __DIR__.'/../../../translations/';
$enPoPath = $langDir.$argv[1];
$langPoPath = $langDir.$argv[2];

// Check for --include-english flag
$includeEnglish = false;
for ($j = 3; $j < $argc; $j++) {
    if ($argv[$j] === '--include-english') {
        $includeEnglish = true;
        break;
    }
}

// Extract language code from filename (e.g., 'ar' from messages.ar.po)
if (!preg_match('/messages\.([a-z]{2})\.po$/', $langPoPath, $matches)) {
    echo "Error: Could not extract language code from second filename.\n";
    exit(1);
}
$langCode = $matches[1];
$outputPath = '/tmp/'."missing_terms_{$langCode}.po";

// Function to parse PO file into array of [msgid => ['msgstr' => string, 'full_entry' => array of lines]]
function parsePoFile(string $path): array {
    $entries = [];
    $handle = fopen($path, 'r');
    if ($handle === false) {
        throw new RuntimeException("Cannot open file: $path");
    }

    $currentEntry = [];
    $currentMsgid = '';
    $currentMsgstr = '';
    $inEntry = false;
    $lines = [];

    while (($line = fgets($handle)) !== false) {
        $trimmed = trim($line);
        $lines[] = rtrim($line, "\n"); // Preserve trailing spaces if any

        if ($trimmed === '' || $trimmed[0] === '#') {
            // Header or comment: if in entry, add to current; else skip for now
            if ($inEntry) {
                $currentEntry[] = $line;
            }
            continue;
        }

        if (preg_match('/^msgid\s+"(.+)"$/', $line, $m)) {
            if ($inEntry && $currentMsgid !== '') {
                // Save previous entry
                $entries[$currentMsgid] = [
                    'msgstr' => $currentMsgstr,
                    'lines' => $currentEntry
                ];
            }
            $inEntry = true;
            $currentEntry = [$line];
            $currentMsgid = $m[1];
            $currentMsgstr = '';
        } elseif (preg_match('/^msgstr\s+"(.+)"$/', $line, $m)) {
            $currentEntry[] = $line;
            if ($m[1] === '') {
                $currentMsgstr = '';
            } else {
                $currentMsgstr = $m[1];
            }
        } elseif (preg_match('/^"\s*(.+?)"\s*$/', $line, $m)) {
            // Continuation line
            if ($inEntry) {
                $currentEntry[] = $line;
                if ($currentMsgid !== '' && $currentMsgstr !== '') {
                    $currentMsgstr .= $m[1];
                } elseif ($currentMsgid === '') {
                    $currentMsgid .= $m[1];
                }
            }
        } else {
            // Other lines (e.g., #: references)
            if ($inEntry) {
                $currentEntry[] = $line;
            }
        }
    }

    // Save last entry
    if ($inEntry && $currentMsgid !== '') {
        $entries[$currentMsgid] = [
            'msgstr' => $currentMsgstr,
            'lines' => $currentEntry
        ];
    }

    fclose($handle);
    return $entries;
}

// Parse both files
try {
    $enEntries = parsePoFile($enPoPath);
    $langEntries = parsePoFile($langPoPath);
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Collect missing/incomplete/wrong entries
$missingLines = [];
// Copy header from lang PO (first few lines until first msgid)
$headerLines = [];
$handle = fopen($langPoPath, 'r');
if ($handle !== false) {
    while (($line = fgets($handle)) !== false) {
        if (strpos(trim($line), 'msgid') === 0) {
            break;
        }
        $headerLines[] = rtrim($line, "\n");
    }
    fclose($handle);
}
$missingLines = array_merge($headerLines, ['']); // Add blank after header

foreach ($enEntries as $msgid => $enData) {
    if (!isset($langEntries[$msgid])) {
        // Missing entirely
        $missingLines[] = 'msgid "' . addslashes($msgid) . '"';
        $missingLines[] = 'msgstr ""';
        $missingLines[] = '';
    } else {
        $langStr = trim($langEntries[$msgid]['msgstr']);
        $isEmpty = ($langStr === '');
        $isEnglish = ($langStr === trim($msgid));
        if ($isEmpty || ($isEnglish && $includeEnglish)) {
            // Incomplete or same as English (if flag set)
            $missingLines[] = 'msgid "' . addslashes($msgid) . '"';
            $missingLines[] = 'msgstr ""';
            $missingLines[] = '';
        }
    }
}

// Write output
if (file_put_contents($outputPath, implode("\n", $missingLines) . "\n") === false) {
    echo "Error: Cannot write $outputPath\n";
    exit(1);
}

echo "Extracted missing terms to $outputPath (include-english: " . ($includeEnglish ? 'yes' : 'no') . ")\n";

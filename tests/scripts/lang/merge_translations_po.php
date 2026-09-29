<?php

/**
 * Merges freshly-translated terms into a language's messages.[lang].po,
 * using messages.en_US.po as the reference for what still needs translating.
 *
 * For each entry in the target language .po, msgstr is replaced with the
 * matching entry from completed_terms.[lang].po when it is empty or still
 * identical to the English string (i.e. never actually translated). Any
 * msgid present in the English reference but entirely missing from the
 * target .po is appended too, provided completed_terms has a translation
 * for it. Everything else (comments, blank lines, already-translated
 * entries) is left untouched, preserving the original file structure.
 *
 * This is the "apply" step of a translate-then-merge workflow: extract
 * untranslated/English-fallback terms from a language's .po, get them
 * translated (e.g. by AI), then run this script to weave the results back
 * into the full .po without disturbing what's already correctly translated.
 *
 * Usage: php merge_translations_po.php <messages.en.po> <messages.[language].po> <completed_terms.[language].po>
 * Example: php merge_translations_po.php messages.en.po messages.ar.po completed.ar.po
 * Outputs: /tmp/complete_[language].po
 */

declare(strict_types=1);

if ($argc !== 4) {
    echo "Usage: php merge_translations_po.php <messages.en.po> <messages.[language].po> <completed_terms.[language].po>\n";
    exit(1);
}

$langDir = __DIR__.'/../../../translations/';
$enPoPath = $langDir.$argv[1];
$originalPoPath = $langDir.$argv[2];
$completedTermsPath = $argv[3];

// Extract language code
if (!preg_match('/messages\.([a-z]{2})\.po$/', $originalPoPath, $matches)) {
    echo "Error: Could not extract language code from original filename.\n";
    exit(1);
}
$langCode = $matches[1];
$outputPath = "/tmp/complete_{$langCode}.po";

// Parse PO to [full_deescaped_msgid => full_deescaped_string]
function parsePoFullStrings(string $path): array {
    $entries = [];
    $handle = fopen($path, 'r');
    if ($handle === false) {
        throw new RuntimeException("Cannot open file: $path");
    }

    $currentKey = ''; // msgid full
    $currentValue = ''; // msgstr full
    $isMsgid = false;
    $isMsgstr = false;

    while (($line = fgets($handle)) !== false) {
        $trimmed = trim($line);
        if ($trimmed === '' || $trimmed[0] === '#') continue; // Skip blanks/comments for key/value extraction

        if (preg_match('/^msgid\s+"(.+?)"\s*$/', rtrim($line, "\n"), $m)) {
            if ($currentKey !== '') {
                $entries[$currentKey] = $currentValue;
            }
            $currentKey = stripslashes($m[1]);
            $currentValue = '';
            $isMsgid = true;
            $isMsgstr = false;
        } elseif (preg_match('/^msgstr\s+"(.+?)"\s*$/', rtrim($line, "\n"), $m)) {
            $currentValue = stripslashes($m[1]);
            $isMsgid = false;
            $isMsgstr = true;
        } elseif (preg_match('/^"\s*(.+?)"\s*$/', rtrim($line, "\n"), $m)) {
            $content = stripslashes($m[1]);
            if ($isMsgid) {
                $currentKey .= $content;
            } elseif ($isMsgstr) {
                $currentValue .= $content;
            }
        }
    }

    if ($currentKey !== '') {
        $entries[$currentKey] = $currentValue;
    }

    fclose($handle);
    return $entries;
}

// Parse completed terms: [msgid full => translated full]
$completedTerms = parsePoFullStrings($completedTermsPath);

// Parse en.po for verification and missing detection: [msgid full => english full]
$enStrings = parsePoFullStrings($enPoPath);

// Parse original to get existing msgids set
$originalStrings = parsePoFullStrings($originalPoPath);
$existingMsgids = array_keys($originalStrings);

// Read original lang.po as lines array to preserve exact structure
$originalLines = file($originalPoPath, FILE_IGNORE_NEW_LINES);
if ($originalLines === false) {
    throw new RuntimeException("Cannot read original PO file: $originalPoPath");
}

// Process lines (replacements for existing)
$outputLines = [];
$i = 0;
while ($i < count($originalLines)) {
    $line = $originalLines[$i];
    $trimmed = trim($line);

    // Preserve non-entry lines (blanks, comments, headers)
    if ($trimmed === '' || $trimmed[0] === '#') {
        $outputLines[] = $line;
        $i++;
        continue;
    }

    // Start of entry: collect full msgid block
    if (strpos($trimmed, 'msgid') === 0) {
        $msgidLines = [];
        $fullMsgid = '';
        while ($i < count($originalLines) && strpos(trim($originalLines[$i]), 'msgid') === 0 || (strpos(trim($originalLines[$i]), '"') === 0 && trim($originalLines[$i]) !== '')) {
            $msgidLines[] = $originalLines[$i];
            if (strpos($trimmed, 'msgid') === 0) {
                $fullMsgid .= stripslashes(trim($originalLines[$i], 'msgid "'));
            } else {
                $fullMsgid .= stripslashes(trim($originalLines[$i], '"'));
            }
            $i++;
            $trimmed = trim($originalLines[$i]);
        }
        $fullMsgid = trim($fullMsgid);

        // Now collect msgstr block
        $msgstrLines = [];
        $fullMsgstr = '';
        while ($i < count($originalLines) && (strpos(trim($originalLines[$i]), 'msgstr') === 0 || (strpos(trim($originalLines[$i]), '"') === 0 && trim($originalLines[$i]) !== ''))) {
            $msgstrLines[] = $originalLines[$i];
            if (strpos(trim($originalLines[$i]), 'msgstr') === 0) {
                $fullMsgstr .= stripslashes(trim($originalLines[$i], 'msgstr "'));
            } else {
                $fullMsgstr .= stripslashes(trim($originalLines[$i], '"'));
            }
            $i++;
        }
        $fullMsgstr = trim($fullMsgstr);

        // Decide if needs replacement
        $needsReplace = (empty($fullMsgstr) || ($fullMsgid !== '' && isset($enStrings[$fullMsgid]) && $fullMsgstr === $enStrings[$fullMsgid]));

        // Output msgid lines
        foreach ($msgidLines as $ml) {
            $outputLines[] = $ml;
        }

        if ($needsReplace && isset($completedTerms[$fullMsgid])) {
            // Replace with new msgstr (single line, as per translation output)
            $translated = $completedTerms[$fullMsgid];
            $outputLines[] = 'msgstr "' . addslashes($translated) . '"' . "\n";
        } else {
            // Output original msgstr block unchanged
            foreach ($msgstrLines as $sl) {
                $outputLines[] = $sl;
            }
        }

        // Add blank line if original had one after entry (check next line)
        if ($i < count($originalLines) && trim($originalLines[$i]) === '') {
            $outputLines[] = '';
            $i++;
        }
    } else {
        // Unexpected line, output as-is
        $outputLines[] = $line;
        $i++;
    }
}

// Add missing terms: Loop through en.po msgids, add if not existing and translated
$addedCount = 0;
foreach (array_keys($enStrings) as $msgid) {
    if (!in_array($msgid, $existingMsgids) && isset($completedTerms[$msgid])) {
        // Construct new entry (single-line for simplicity)
        $newEntry = 'msgid "' . addslashes($msgid) . '"' . "\n";
        $newEntry .= 'msgstr "' . addslashes($completedTerms[$msgid]) . '"' . "\n";
        $newEntry .= "\n"; // Blank after new entry

        // Append to output
        $outputLines[] = $newEntry;
        $addedCount++;
    }
}

if ($addedCount > 0) {
    echo "Added $addedCount missing terms from en.po + completed_terms.\n";
}

// Quality check: Collapse multiple consecutive blank lines to one
$processedLines = [];
$lastWasBlank = false;
foreach ($outputLines as $line) {
    $isBlank = (trim($line) === '');
    if ($isBlank && $lastWasBlank) {
        continue; // Skip duplicate blank
    }
    $processedLines[] = $line;
    $lastWasBlank = $isBlank;
}
$outputLines = $processedLines;

// Write output
$outputContent = implode("\n", $outputLines) . "\n";
if (file_put_contents($outputPath, $outputContent) === false) {
    echo "Error: Cannot write $outputPath\n";
    exit(1);
}

echo "Merged translations to $outputPath (replacements + $addedCount missing added, structure preserved)\n";

<?php
/* For licensing terms, see /license.txt */
/**
 * Script to switch all language variables in Chamilo to a more Gettext-like syntax.
 */
/**
 * Includes and declarations.
 */
die('Remove the "die()" statement on line '.__LINE__.' to execute this script'.PHP_EOL);
require_once __DIR__.'/../../public/main/inc/global.inc.php';
$path = api_get_path(SYS_PATH) . 'main/lang/english'; // Adjusted path
ini_set('memory_limit', '128M');

/**
 * Main code.
 */
$terms = [];
$list = SubLanguageManager::get_lang_folder_files_list($path);

// Verify that the directory content is being read
echo "Reading language files from: $path\n";

foreach ($list as $entry) {
    $file = $path . '/' . $entry;
    echo "Processing language file: $file\n"; // Add debug message
    if (is_file($file)) {
        $file_terms = SubLanguageManager::get_all_language_variable_in_file($file, true);
        //print_r($file_terms); // Debug: print terms loaded from the file
        $terms = array_merge($terms, $file_terms);
    }
}

foreach ($terms as $index => $translation) {
    $terms[$index] = trim(rtrim($translation, ';'), '"');
}

// Get only the array keys (the language variables defined in language files)
$defined_terms = array_flip(array_keys($terms));
echo count($defined_terms) . " terms were found in language files" . PHP_EOL;
//print_r($defined_terms); // Debug: print the terms found

// Now get all terms found in all PHP, TPL, and Twig files of Chamilo (this takes some time and memory)
$usedTerms = [];
$l = strlen(api_get_path(SYS_PATH));
$pathfile = api_get_path(SYS_PATH) . "main/template/"; //Path for the missing files, should be adapted for other use
$files = getAllPhpFiles($pathfile);
//$files = [$pathfile]; // Process only the specific file for now
$rootLength = strlen(api_get_path(SYS_PATH));
$countFiles = 0;
$countReplaces = 0;

// Browse files
foreach ($files as $file) {
    echo "Analyzing $file" . PHP_EOL;
    $lines = file($file);
    $newContent = ''; // Store new file content
    $fileModified = false;

    // Browse lines inside file $file
    foreach ($lines as $lineIndex => $line) {
        $lineModified = false;

        // Regular expression for {{ 'variable'|get_lang|format() }}
        $res = preg_match_all('/\{\{\s*([\'"]\w+[\'"])\s*\|\s*get_lang\s*\|\s*format\s*\((.*?)\)\s*\}\}/m', $line, $myTerms);
        if ($res > 0) {
            echo "Match found for get_lang|format in line: $line" . PHP_EOL;
            foreach ($myTerms[1] as $index => $quotedTerm) {
                $term = trim($quotedTerm, '\'\"');
                if (isset($terms[$term])) {
                    $translation = $terms[$term];
                    echo "Replacing $quotedTerm with '$translation'" . PHP_EOL;
                    $line = str_replace($quotedTerm, "'$translation'", $line);
                    $lineModified = true;
                    $countReplaces++;
                } else {
                    echo "Term $term not found in language file" . PHP_EOL; // Debug: term not found
                }
            }
        }

        // Regular expression for {{ 'variable'|get_lang }}
        $res = preg_match_all('/\{\{\s*([\'"]\w+[\'"])\s*\|\s*get_lang\s*\}\}/m', $line, $myTerms);
        if ($res > 0) {
            echo "Match found for get_lang in line: $line" . PHP_EOL;
            foreach ($myTerms[1] as $index => $quotedTerm) {
                $term = trim($quotedTerm, '\'\"');
                if (isset($terms[$term])) {
                    $translation = $terms[$term];
                    echo "Replacing $quotedTerm with '$translation'" . PHP_EOL;
                    $line = str_replace($quotedTerm, "'$translation'", $line);
                    $lineModified = true;
                    $countReplaces++;
                } else {
                    echo "Term $term not found in language file" . PHP_EOL; // Debug: term not found
                }
            }
        }

        // Regular expression for get_lang('variable') or get_lang("variable")
        $res = preg_match_all('/get_lang\(([\'"](\w+)[\'"])\)/m', $line, $myTerms);
        if ($res > 0) {
            echo "Match found for get_lang() in line: $line" . PHP_EOL;
            foreach ($myTerms[2] as $index => $term) {
                if (isset($terms[$term])) {
                    $translation = $terms[$term];
                    $quotedTerm = $myTerms[1][$index];
                    echo "Replacing $quotedTerm with '$translation'" . PHP_EOL;
                    $line = str_replace($quotedTerm, "'$translation'", $line);
                    $lineModified = true;
                    $countReplaces++;
                } else {
                    echo "Term $term not found in language file" . PHP_EOL; // Debug: term not found
                }
            }
        }

        $newContent .= $line; // Add modified line to new content
        if ($lineModified) {
            $fileModified = true;
        }
    }

    // Write the modified content back to the file if there were modifications
    if ($fileModified) {
        file_put_contents($file, $newContent);
    }

    $countFiles++;
}

echo "Done analyzing $countFiles files, with $countReplaces replacements!\n";

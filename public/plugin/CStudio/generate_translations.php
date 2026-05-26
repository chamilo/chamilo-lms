<?php

declare(strict_types=1);

if ('cli' !== \PHP_SAPI) {
    http_response_code(403);

    exit('This script must be run from the command line.');
}

// Keys that belong to the Chamilo plugin system, not the JS editor UI.
$pluginOnlyKeys = ['plugin_title', 'plugin_comment', 'Term', 'Definition'];

// Any lang/*.php file that contains editor UI strings (i.e. more than the
// four plugin-system keys above) will be converted to a JSON file whose name
// matches the Chamilo locale code exactly — no mapping needed.
// To add a new language: create lang/xx_XX.php with the translated strings,
// then re-run this script.
$langDir = __DIR__.'/lang/';
$jsonDir = $langDir.'json/';

if (!is_dir($jsonDir) && !mkdir($jsonDir, 0755, true)) {
    fwrite(\STDERR, "ERROR: Could not create directory {$jsonDir}\n");

    exit(1);
}

$generated = 0;

foreach (glob($langDir.'*.php') as $phpFile) {
    $strings = [];

    require $phpFile;

    foreach ($pluginOnlyKeys as $key) {
        unset($strings[$key]);
    }

    if (0 === count($strings)) {
        // No editor UI strings — skip (plugin-only lang file)
        continue;
    }

    $locale = basename($phpFile, '.php');     // e.g. "fr_FR", "es", "de"
    $jsonFile = $jsonDir.$locale.'.json';
    $json = json_encode($strings, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE);

    if (false === file_put_contents($jsonFile, $json)) {
        fwrite(\STDERR, "ERROR: Could not write {$jsonFile}\n");

        exit(1);
    }

    $count = count($strings);
    echo "OK    {$locale}.php → json/{$locale}.json ({$count} strings)\n";
    $generated++;
}

echo "\nGenerated {$generated} file(s) in {$jsonDir}\n";

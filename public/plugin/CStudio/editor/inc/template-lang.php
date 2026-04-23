<?php

declare(strict_types=1);

/**
 * Replace ###LANG:key### placeholders in a template HTML string with translated values.
 *
 * Resolution order:
 *   1. Load en_US.php as the base (guarantees every tpl_* key has an English fallback).
 *   2. Load the target locale file and overlay its keys on top (if the file exists).
 *   3. Also try the bare language code (e.g. "fr" from "fr_FR") if the full file is missing.
 *   4. If a key is absent even from en_US, return the raw key name.
 */
function apply_cstudio_template_lang(string $html, string $locale): string
{
    $langDir = __DIR__.'/../../lang/';

    // Step 1 — base layer: en_US guarantees every tpl_* key resolves to English text
    $strings = [];
    $enFile = $langDir.'en_US.php';
    if (file_exists($enFile)) {
        require $enFile;
    }
    $merged = $strings;

    // Step 2 — overlay with target locale (skip when it IS en_US to avoid double-loading)
    if ('en_US' !== $locale) {
        $strings = [];
        $localeFile = null;

        if (file_exists($langDir.$locale.'.php')) {
            $localeFile = $langDir.$locale.'.php';
        } else {
            // Try bare language code: "fr_FR" → "fr"
            $bare = explode('_', $locale)[0];
            if (file_exists($langDir.$bare.'.php')) {
                $localeFile = $langDir.$bare.'.php';
            }
        }

        if (null !== $localeFile) {
            require $localeFile;
            $merged = array_merge($merged, $strings);
        }
    }

    return preg_replace_callback(
        '/###LANG:([a-z0-9_]+)###/i',
        static function (array $m) use ($merged): string {
            return $merged[$m[1]] ?? $m[1];
        },
        $html
    );
}

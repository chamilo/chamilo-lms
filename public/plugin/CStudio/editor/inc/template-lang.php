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

    // Allowlist: only accept iso-style locales ("fr", "fr_FR"). Anything else
    // (path traversal, absolute paths, leading dots) collapses to en_US so the
    // value can never escape the lang/ directory when reaching require below.
    if (!preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $locale)) {
        $locale = 'en_US';
    }

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

        // Defense in depth: basename() strips any traversal that slipped past
        // the allowlist (should be impossible, but cheap to assert).
        $localeSafe = basename($locale);
        $bareSafe = basename(explode('_', $localeSafe)[0]);

        $candidate = $langDir.$localeSafe.'.php';
        if (realpath($candidate) !== false
            && str_starts_with((string) realpath($candidate), (string) realpath($langDir))
            && file_exists($candidate)
        ) {
            $localeFile = $candidate;
        } else {
            // Try bare language code: "fr_FR" → "fr"
            $candidateBare = $langDir.$bareSafe.'.php';
            if (realpath($candidateBare) !== false
                && str_starts_with((string) realpath($candidateBare), (string) realpath($langDir))
                && file_exists($candidateBare)
            ) {
                $localeFile = $candidateBare;
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

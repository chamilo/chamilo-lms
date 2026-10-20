<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Sanitizes HTML coming from an uploaded QTI package before it is stored as answer text.
 *
 * Matching options and items are rendered as raw HTML in the exercise views (unlike unique or
 * multiple answers, which go through Security::remove_XSS() when displayed), so the importers
 * must clean them: scripts, event handlers and javascript: URLs are removed, formatting is kept.
 * It does not depend on the legacy bootstrap, so both QTI2 importers can use it.
 */
final class QtiImportHtmlSanitizer
{
    private static ?HTMLPurifier $purifier = null;

    public static function sanitize(string $html): string
    {
        if ('' === trim($html) || !preg_match('/[<&]/', $html)) {
            return trim($html);
        }

        return trim(self::getPurifier()->purify($html));
    }

    private static function getPurifier(): HTMLPurifier
    {
        if (null === self::$purifier) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('Cache.DefinitionImpl', null);
            $config->set('Core.Encoding', 'UTF-8');
            $config->set('HTML.Doctype', 'XHTML 1.0 Transitional');
            self::$purifier = new HTMLPurifier($config);
        }

        return self::$purifier;
    }
}

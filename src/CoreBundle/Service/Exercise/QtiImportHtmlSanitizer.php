<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Sanitizes HTML coming from an uploaded QTI package before it is stored as answer text.
 *
 * Matching options and items, and the text of fill in blanks questions, are rendered as raw HTML in
 * the exercise views (unlike unique or multiple answers, which go through Security::remove_XSS() when
 * displayed), so the importers must clean them: scripts, event handlers and javascript: URLs are
 * removed, formatting is kept.
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

    /**
     * Sanitizes the text of a fill in blanks answer ("<html with [blanks]>::scores:sizes:separator@switchable")
     * without touching its blanks.
     *
     * Each blank is swapped for a plain-text token before the HTML is purified, then put back, so
     * markers and options ([word], [a||b], [a|b|c], {word}…) come out as they went in. A blank that
     * HTMLPurifier removed (it sat inside a <script>, for example) is dropped together with its score
     * and size, so the scores still match the blanks; the returned total is the sum of the kept scores.
     * Inside a blank, "<" and ">" (and quotes when the blank sits inside a tag) are escaped, so a blank
     * can't bring markup back.
     *
     * @return array{0: string, 1: float}
     */
    public static function sanitizeFillBlanksAnswer(string $answer, float $total): array
    {
        $position = strrpos($answer, '::');
        $meta = false === $position ? '' : substr($answer, $position + 2);
        if (false === $position || 1 !== preg_match('/^([^:]*):([^:]*):(\d+)@(\d*)$/', $meta, $parts)) {
            return [self::sanitize($answer), $total];
        }

        $html = substr($answer, 0, $position);
        $pattern = Qti2FillBlanksWriter::blankPattern((int) $parts[3]);
        $token = 'qtiblank'.bin2hex(random_bytes(6)).'n';
        $blanks = [];
        $protected = (string) preg_replace_callback(
            $pattern,
            static function (array $match) use (&$blanks, $token): string {
                $blanks[] = $match[0];

                return $token.(\count($blanks) - 1).'x';
            },
            $html
        );

        $clean = '' === trim($protected) ? '' : trim(self::getPurifier()->purify($protected));

        $weights = explode(',', $parts[1]);
        $sizes = explode(',', $parts[2]);
        $keptWeights = [];
        $keptSizes = [];
        $clean = (string) preg_replace_callback(
            '/'.$token.'(\d+)x/',
            static function (array $match) use ($blanks, $weights, $sizes, &$keptWeights, &$keptSizes, $clean): string {
                $index = (int) $match[1];
                $blank = $blanks[$index] ?? '';
                $keptWeights[] = $weights[$index] ?? '0';
                $keptSizes[] = $sizes[$index] ?? '200';
                $search = ['<', '>'];
                $replace = ['&lt;', '&gt;'];
                $before = substr($clean, 0, (int) strpos($clean, $match[0]));
                $lastOpen = strrpos($before, '<');
                $lastClose = strrpos($before, '>');
                if (false !== $lastOpen && (false === $lastClose || $lastOpen > $lastClose)) {
                    // The blank sits inside a tag (an attribute value): keep it from closing the attribute.
                    $search = ['<', '>', '"', "'"];
                    $replace = ['&lt;', '&gt;', '&quot;', '&#039;'];
                }

                return str_replace($search, $replace, $blank);
            },
            $clean
        );

        if (\count($keptWeights) !== \count($blanks)) {
            $total = array_sum(array_map('floatval', $keptWeights));
        }

        return [
            $clean.'::'.implode(',', $keptWeights).':'.implode(',', $keptSizes).':'.$parts[3].'@'.$parts[4],
            $total,
        ];
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

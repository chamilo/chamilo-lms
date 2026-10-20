<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use const ENT_HTML5;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const ENT_XML1;
use const PREG_OFFSET_CAPTURE;

/**
 * Writes a Chamilo "Fill in blanks" answer as QTI 2.1 markup, shared by the legacy and the API exporters.
 *
 * Chamilo stores the whole question in one answer row:
 *   "<html with [blank]...>::w1,w2:size1,size2:separator@switchable"
 * where a blank is "[word]", "[word||alternative]" (any of them is accepted) or "[correct|wrong1|wrong2]"
 * (drop-down menu, the first option being the correct one), and the separator number selects the
 * blank delimiters ([], {}, (), **, ##, %%, $$).
 *
 * Typed blanks become a textEntryInteraction with a string response, menus an inlineChoiceInteraction.
 * Every blank has its own "fill_N" responseDeclaration with its score in the mapping, which is what
 * the Chamilo QTI2 importers read. Texts are written as CDATA holding HTML-escaped text, like the other
 * texts of Chamilo QTI2 exports, so the importers get the stored HTML back.
 */
final class Qti2FillBlanksWriter
{
    private const SEPARATORS = [
        ['[', ']'],
        ['{', '}'],
        ['(', ')'],
        ['*', '*'],
        ['#', '#'],
        ['%', '%'],
        ['$', '$'],
    ];

    /**
     * @return array{declarations: string, body: string, blankCount: int}|null null when the answer has no blank
     */
    public static function write(string $answer, bool $caseInsensitive = false): ?array
    {
        $parsed = self::parse($answer);
        if (null === $parsed || [] === $parsed['blanks']) {
            return null;
        }

        $declarations = '';
        $body = '';
        foreach ($parsed['segments'] as $index => $segment) {
            $body .= self::textSpan($segment);
            if (!isset($parsed['blanks'][$index])) {
                continue;
            }

            $blank = $parsed['blanks'][$index];
            $responseId = 'fill_'.($index + 1);
            $score = self::formatScore($blank['score']);

            if ('menu' === $blank['type']) {
                $declarations .= '  <responseDeclaration identifier="'.$responseId.'" cardinality="single" baseType="identifier">'."\n";
                $declarations .= '    <correctResponse>'."\n";
                $declarations .= '      <value>'.$responseId.'_1</value>'."\n";
                $declarations .= '    </correctResponse>'."\n";
                $declarations .= '    <mapping defaultValue="0">'."\n";
                $declarations .= '      <mapEntry mapKey="'.$responseId.'_1" mappedValue="'.$score.'"/>'."\n";
                $declarations .= '    </mapping>'."\n";
                $declarations .= '  </responseDeclaration>'."\n";

                $body .= '<inlineChoiceInteraction responseIdentifier="'.$responseId.'" shuffle="false">';
                foreach ($blank['options'] as $position => $option) {
                    $body .= '<inlineChoice identifier="'.$responseId.'_'.($position + 1).'">'.self::cdata($option).'</inlineChoice>';
                }
                $body .= '</inlineChoiceInteraction>';

                continue;
            }

            // The correct response keeps the Chamilo alternatives ("a||b") so the importers can rebuild
            // the blank; the mapping lists each accepted answer, which is what QTI scoring uses.
            $declarations .= '  <responseDeclaration identifier="'.$responseId.'" cardinality="single" baseType="string">'."\n";
            $declarations .= '    <correctResponse>'."\n";
            $declarations .= '      <value>'.self::cdata(implode('||', $blank['options'])).'</value>'."\n";
            $declarations .= '    </correctResponse>'."\n";
            $declarations .= '    <mapping defaultValue="0">'."\n";
            foreach (array_unique($blank['options']) as $option) {
                // The importers look the score up by the stored blank text.
                $declarations .= '      <mapEntry mapKey="'.self::attribute($option).'" mappedValue="'.$score.'"'
                    .' caseSensitive="'.($caseInsensitive ? 'false' : 'true').'"/>'."\n";
            }
            $declarations .= '    </mapping>'."\n";
            $declarations .= '  </responseDeclaration>'."\n";

            $expectedLength = max(array_map(static fn (string $option): int => mb_strlen(html_entity_decode($option, ENT_QUOTES | ENT_HTML5, 'UTF-8')), $blank['options']));
            $body .= '<textEntryInteraction responseIdentifier="'.$responseId.'" expectedLength="'.max(1, $expectedLength).'"/>';
        }

        $declarations .= '  <outcomeDeclaration identifier="SCORE" cardinality="single" baseType="float"/>'."\n";

        return [
            'declarations' => $declarations,
            // QTI only allows interactions inside a block; the importers drop <p> and read the children.
            'body' => '    <p>'.$body.'</p>'."\n",
            'blankCount' => \count($parsed['blanks']),
        ];
    }

    /**
     * @return array{segments: array<int, string>, blanks: array<int, array{type: string, options: array<int, string>, score: float}>}|null
     */
    public static function parse(string $answer): ?array
    {
        $text = $answer;
        $system = '';
        $position = strrpos($answer, '::');
        if (false !== $position) {
            $text = substr($answer, 0, $position);
            $system = substr($answer, $position + 2);
        }

        $details = explode(':', explode('@', $system)[0]);
        $weights = explode(',', $details[0]);
        $separatorNumber = \count($details) >= 3 ? (int) $details[2] : 0;
        [$start, $end] = self::SEPARATORS[$separatorNumber] ?? self::SEPARATORS[0];

        $pattern = '/'.preg_quote($start, '/').'[^'.preg_quote($end, '/').']*'.preg_quote($end, '/').'/u';
        if (false === preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $segments = [];
        $blanks = [];
        $offset = 0;
        foreach ($matches[0] as $index => [$raw, $rawOffset]) {
            $segments[] = substr($text, $offset, $rawOffset - $offset);
            $offset = $rawOffset + \strlen($raw);

            $inside = trim($raw, $start.$end);
            if (str_contains($inside, '||')) {
                $type = 'several';
                $options = explode('||', $inside);
            } elseif (str_contains($inside, '|')) {
                $type = 'menu';
                $options = explode('|', $inside);
            } else {
                $type = 'standard';
                $options = [$inside];
            }
            $options = array_values(array_filter(
                array_map(static fn (string $option): string => trim((string) preg_replace('/\s+/u', ' ', $option)), $options),
                static fn (string $option): bool => '' !== $option
            ));
            if ([] === $options) {
                $options = [''];
            }

            $weight = str_replace(',', '.', trim($weights[$index] ?? '0'));
            $score = is_numeric($weight) ? (float) $weight : 0.0;

            $blanks[] = [
                'type' => $type,
                'options' => $options,
                'score' => is_finite($score) ? $score : 0.0,
            ];
        }
        $segments[] = substr($text, $offset);

        return ['segments' => $segments, 'blanks' => $blanks];
    }

    /**
     * Text between blanks, in a span so that the importers keep it in place. A segment that is already
     * a single plain span (as imported from a previous export) is not wrapped again.
     */
    private static function textSpan(string $html): string
    {
        if ('' === $html) {
            return '';
        }
        if (1 === preg_match('#^<span>((?:(?!</?span[\s>]).)*)</span>$#su', $html, $match)) {
            $html = $match[1];
        }

        return '<span>'.self::cdata($html).'</span>';
    }

    private static function cdata(string $html): string
    {
        // htmlspecialchars() leaves no "]]>" in the content, so the CDATA section cannot be closed early.
        return '<![CDATA['.self::escapeHtml($html).']]>';
    }

    private static function escapeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function attribute(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1, 'UTF-8');
    }

    private static function formatScore(float $score): string
    {
        $formatted = rtrim(rtrim(\sprintf('%.6F', $score), '0'), '.');

        return '' === $formatted || '-0' === $formatted ? '0' : $formatted;
    }
}

<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use DOMElement;
use InvalidArgumentException;
use ZipArchive;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const FILTER_VALIDATE_INT;

/**
 * QTI 2.1 definitions for graphic hotspots, graphic ordering and manually graded file responses.
 * The learner's uploaded files are attempts, not portable question-definition assets.
 */
final class Qti2SpecialQuestionItem
{
    public const HOTSPOT = 6;
    public const GRAPHIC_ORDER = 7;
    public const ORAL = 13;
    public const UPLOAD = 23;

    private const LABELS = [
        self::HOTSPOT => 'chamilo:hotspot',
        self::GRAPHIC_ORDER => 'chamilo:hotspot-order',
        self::ORAL => 'chamilo:oral-expression',
        self::UPLOAD => 'chamilo:upload-answer',
    ];

    public static function label(int $type): string
    {
        return self::LABELS[$type] ?? '';
    }

    /**
     * @param array{path: string, mime: string, width: int, height: int}|null $image
     */
    public static function write(CQuizQuestion $question, string $identifier, ?array $image = null): string
    {
        $type = (int) $question->getType();
        if (!isset(self::LABELS[$type])) {
            throw new InvalidArgumentException('Unsupported QTI special question type.');
        }
        $score = self::number((float) $question->getPonderation());
        $outcome = '  <outcomeDeclaration identifier="SCORE" cardinality="single" baseType="float">'
            .'<defaultValue><value>0</value></defaultValue></outcomeDeclaration>'."\n"
            .'  <outcomeDeclaration identifier="MAXSCORE" cardinality="single" baseType="float">'
            .'<defaultValue><value>'.$score.'</value></defaultValue></outcomeDeclaration>'."\n";
        $xml = '';
        if (self::ORAL === $type || self::UPLOAD === $type) {
            $xml .= '  <responseDeclaration identifier="'.$identifier.'" cardinality="single" baseType="file"/>'."\n";
            $xml .= $outcome;
            $xml .= '  <itemBody><uploadInteraction responseIdentifier="'.$identifier.'">'
                .'<prompt>'.self::escape((string) ($question->getDescription() ?: $question->getQuestion())).'</prompt>'
                .'</uploadInteraction></itemBody>'."\n";

            return $xml;
        }
        if (null === $image || !isset($image['path'], $image['mime'], $image['width'], $image['height'])) {
            throw new InvalidArgumentException('A graphic QTI question requires its stored image.');
        }
        $answers = iterator_to_array($question->getAnswers());
        usort($answers, static fn (CQuizAnswer $a, CQuizAnswer $b): int => $a->getPosition() <=> $b->getPosition());
        if ([] === $answers || \count($answers) > 40) {
            throw new InvalidArgumentException('A graphic QTI question requires between 1 and 40 zones.');
        }
        $ordered = self::GRAPHIC_ORDER === $type;
        $xml .= '  <responseDeclaration identifier="'.$identifier.'" cardinality="'.($ordered ? 'ordered' : 'multiple').'" baseType="identifier">'."\n";
        $xml .= '    <correctResponse>';
        foreach ($answers as $answer) {
            if ((float) $answer->getPonderation() <= 0 && !$ordered) {
                throw new InvalidArgumentException('Hotspot zones require a positive individual score.');
            }
            $xml .= '<value>zone_'.(int) $answer->getIid().'</value>';
        }
        $xml .= '</correctResponse>'."\n";
        $xml .= '    <mapping>';
        foreach ($answers as $answer) {
            $xml .= '<mapEntry mapKey="zone_'.(int) $answer->getIid().'" mappedValue="'
                .self::number((float) $answer->getPonderation()).'"/>';
        }
        $xml .= '</mapping></responseDeclaration>'."\n";
        $xml .= $outcome;
        $tag = $ordered ? 'graphicOrderInteraction' : 'hotspotInteraction';
        $xml .= '  <itemBody><'.$tag.' responseIdentifier="'.$identifier.'"';
        if (!$ordered) {
            $xml .= ' maxChoices="'.\count($answers).'"';
        }
        $xml .= '><prompt>'.self::escape((string) $question->getDescription()).'</prompt>';
        $xml .= '<object type="'.self::escape($image['mime']).'" data="'.self::escape($image['path']).'"'
            .' width="'.(int) $image['width'].'" height="'.(int) $image['height'].'">Image</object>';
        foreach ($answers as $answer) {
            [$shape, $coords] = self::encodeCoordinates((string) $answer->getHotspotType(), (string) $answer->getHotspotCoordinates());
            $label = trim(strip_tags($answer->getAnswer()));
            if ('' === $label || \strlen($label) > 256) {
                throw new InvalidArgumentException('Invalid hotspot zone label.');
            }
            $xml .= '<hotspotChoice identifier="zone_'.(int) $answer->getIid().'"'
                .' hotspotLabel="'.self::escape($label).'" shape="'.$shape.'" coords="'.self::escape($coords).'"/>';
        }
        $xml .= '</'.$tag.'></itemBody>'."\n";
        if ($ordered) {
            $xml .= '  <responseProcessing><responseCondition><responseIf><match>'
                .'<variable identifier="'.$identifier.'"/><correct identifier="'.$identifier.'"/>'
                .'</match><setOutcomeValue identifier="SCORE"><baseValue baseType="float">'.$score.'</baseValue>'
                .'</setOutcomeValue></responseIf><responseElse><setOutcomeValue identifier="SCORE">'
                .'<baseValue baseType="float">0</baseValue></setOutcomeValue></responseElse>'
                .'</responseCondition></responseProcessing>'."\n";
        } else {
            $xml .= '  <responseProcessing template="http://www.imsglobal.org/question/qti_v2p1/rptemplates/map_response"/>'."\n";
        }

        return $xml;
    }

    /**
     * @return array{type: int, score: float, image?: string, mime?: string, width?: int, height?: int, zones?: list<array{answer: string, coordinates: string, hotspotType: string, score: float}>}|null
     */
    public static function read(DOMElement $item): ?array
    {
        $label = $item->getAttribute('label');
        $marked = array_search($label, self::LABELS, true);
        $type = false === $marked ? null : (int) $marked;
        $hotspot = $item->getElementsByTagName('hotspotInteraction')->item(0);
        $ordered = $item->getElementsByTagName('graphicOrderInteraction')->item(0);
        $upload = $item->getElementsByTagName('uploadInteraction')->item(0);
        $interaction = $hotspot ?? $ordered ?? $upload;
        if (!$interaction instanceof DOMElement) {
            return null;
        }
        $inferred = $hotspot instanceof DOMElement ? self::HOTSPOT : ($ordered instanceof DOMElement ? self::GRAPHIC_ORDER : self::UPLOAD);
        $type ??= $inferred;
        if ((self::HOTSPOT === $type && !$hotspot instanceof DOMElement)
            || (self::GRAPHIC_ORDER === $type && !$ordered instanceof DOMElement)
            || (\in_array($type, [self::ORAL, self::UPLOAD], true) && !$upload instanceof DOMElement)) {
            return null;
        }
        $responseIdentifier = $interaction->getAttribute('responseIdentifier');
        if ('' === $responseIdentifier) {
            return null;
        }
        $declaration = null;
        foreach ($item->getElementsByTagName('responseDeclaration') as $candidate) {
            if ($candidate->getAttribute('identifier') === $responseIdentifier) {
                $declaration = $candidate;

                break;
            }
        }
        if (!$declaration instanceof DOMElement) {
            return null;
        }
        $score = 0.0;
        foreach ($item->getElementsByTagName('outcomeDeclaration') as $outcome) {
            if (!\in_array($outcome->getAttribute('identifier'), ['MAXSCORE', 'SCORE'], true)) {
                continue;
            }
            $value = $outcome->getElementsByTagName('value')->item(0)?->textContent;
            if (null !== $value && is_numeric($value)
                && ('MAXSCORE' === $outcome->getAttribute('identifier') || 0.0 === $score)) {
                $score = (float) $value;
            }
        }
        if (\in_array($type, [self::ORAL, self::UPLOAD], true)) {
            if ('file' !== $declaration->getAttribute('baseType') || 'single' !== $declaration->getAttribute('cardinality')) {
                return null;
            }

            return ['type' => $type, 'score' => max(0.0, $score)];
        }
        if ('identifier' !== $declaration->getAttribute('baseType')
            || (self::GRAPHIC_ORDER === $type ? 'ordered' : 'multiple') !== $declaration->getAttribute('cardinality')) {
            return null;
        }
        $object = $interaction->getElementsByTagName('object')->item(0);
        if (!$object instanceof DOMElement || !preg_match('#^qti_media/[a-f0-9]{64}\.(?:png|jpg|gif)$#D', $object->getAttribute('data'))) {
            return null;
        }
        $width = filter_var($object->getAttribute('width'), FILTER_VALIDATE_INT);
        $height = filter_var($object->getAttribute('height'), FILTER_VALIDATE_INT);
        if (false === $width || false === $height || $width <= 0 || $height <= 0 || $width > 8192 || $height > 8192) {
            return null;
        }
        $correct = [];
        $correctElement = $declaration->getElementsByTagName('correctResponse')->item(0);
        if ($correctElement instanceof DOMElement) {
            foreach ($correctElement->getElementsByTagName('value') as $node) {
                $correct[] = trim((string) $node->textContent);
            }
        }
        $weights = [];
        foreach ($declaration->getElementsByTagName('mapEntry') as $entry) {
            if (is_numeric($entry->getAttribute('mappedValue'))) {
                $weights[$entry->getAttribute('mapKey')] = (float) $entry->getAttribute('mappedValue');
            }
        }
        $zones = [];
        foreach ($interaction->getElementsByTagName('hotspotChoice') as $choice) {
            $id = $choice->getAttribute('identifier');
            if ('' === $id || isset($zones[$id])) {
                return null;
            }
            $geometry = self::decodeCoordinates($choice->getAttribute('shape'), $choice->getAttribute('coords'));
            $answer = trim(strip_tags($choice->getAttribute('hotspotLabel')));
            if (null === $geometry || '' === $answer || \strlen($answer) > 256) {
                return null;
            }
            $zones[$id] = [
                'answer' => $answer,
                'coordinates' => $geometry[1],
                'hotspotType' => $geometry[0],
                'score' => (float) ($weights[$id] ?? 0.0),
            ];
        }
        if ([] === $zones || \count($zones) > 40 || \count($correct) !== \count($zones)
            || array_values(array_unique($correct)) !== $correct
            || (self::GRAPHIC_ORDER === $type ? $correct !== array_keys($zones) : [] !== array_diff($correct, array_keys($zones)))) {
            return null;
        }
        if (self::HOTSPOT === $type) {
            foreach ($zones as $zone) {
                if ($zone['score'] <= 0) {
                    return null;
                }
            }
        }

        return [
            'type' => $type,
            'score' => $score,
            'image' => $object->getAttribute('data'),
            'mime' => $object->getAttribute('type'),
            'width' => $width,
            'height' => $height,
            'zones' => array_values($zones),
        ];
    }

    /**
     * @param array{image: string, mime: string, width: int, height: int} $data
     */
    public static function validateImage(array $data, ZipArchive $archive): void
    {
        [$content, , $mime] = Qti2MediaArchive::readImage($archive, (string) $data['image']);
        $size = getimagesizefromstring($content);
        if (!\is_array($size) || $mime !== (string) $data['mime'] || (int) $size[0] !== (int) $data['width'] || (int) $size[1] !== (int) $data['height']) {
            throw new InvalidArgumentException('The QTI graphic dimensions do not match the embedded image.');
        }
    }

    /**
     * @return array{string, string}
     */
    private static function encodeCoordinates(string $type, string $coordinates): array
    {
        if (\in_array($type, ['square', 'circle'], true)
            && preg_match('/^(-?\d+(?:\.\d+)?);(-?\d+(?:\.\d+)?)\|(-?\d+(?:\.\d+)?)\|(-?\d+(?:\.\d+)?)$/D', $coordinates, $match)) {
            $x = (float) $match[1];
            $y = (float) $match[2];
            $w = (float) $match[3];
            $h = (float) $match[4];
            if ($w <= 0 || $h <= 0) {
                throw new InvalidArgumentException('Invalid hotspot dimensions.');
            }
            if ('square' === $type) {
                return ['rect', implode(',', [self::number($x), self::number($y), self::number($x + $w), self::number($y + $h)])];
            }
            if (abs($w - $h) > 0.00001) {
                throw new InvalidArgumentException('Elliptical Chamilo hotspots cannot be exported as QTI circles without changing their geometry.');
            }

            return ['circle', implode(',', [self::number($x + $w / 2), self::number($y + $h / 2), self::number($w / 2)])];
        }
        if ('poly' === $type) {
            $vertices = explode('|', $coordinates);
            if (\count($vertices) < 3) {
                throw new InvalidArgumentException('A hotspot polygon must have at least three vertices.');
            }
            $points = [];
            foreach ($vertices as $vertex) {
                if (!preg_match('/^(-?\d+(?:\.\d+)?);(-?\d+(?:\.\d+)?)$/D', $vertex, $match)) {
                    throw new InvalidArgumentException('Invalid hotspot polygon coordinates.');
                }
                $points[] = self::number((float) $match[1]);
                $points[] = self::number((float) $match[2]);
            }

            return ['poly', implode(',', $points)];
        }

        throw new InvalidArgumentException('Unsupported hotspot shape or coordinates.');
    }

    /**
     * @return array{string, string}|null
     */
    private static function decodeCoordinates(string $shape, string $coordinates): ?array
    {
        $values = explode(',', $coordinates);
        foreach ($values as $number) {
            if (\strlen($number) > 20 || !preg_match('/^-?\d+(?:\.\d+)?$/D', $number) || !is_finite((float) $number)) {
                return null;
            }
        }
        if ('rect' === $shape && 4 === \count($values)) {
            [$x, $y, $right, $bottom] = array_map('floatval', $values);
            if ($right <= $x || $bottom <= $y) {
                return null;
            }

            return ['square', self::number($x).';'.self::number($y).'|'.self::number($right - $x).'|'.self::number($bottom - $y)];
        }
        if ('circle' === $shape && 3 === \count($values)) {
            [$cx, $cy, $radius] = array_map('floatval', $values);
            if ($radius <= 0) {
                return null;
            }

            return ['circle', self::number($cx - $radius).';'.self::number($cy - $radius).'|'.self::number(2 * $radius).'|'.self::number(2 * $radius)];
        }
        if ('poly' === $shape && \count($values) >= 6 && 0 === \count($values) % 2) {
            $vertices = [];
            for ($index = 0; $index < \count($values); $index += 2) {
                $vertices[] = self::number((float) $values[$index]).';'.self::number((float) $values[$index + 1]);
            }

            return ['poly', implode('|', $vertices)];
        }

        return null;
    }

    private static function number(float $number): string
    {
        return rtrim(rtrim(\sprintf('%.6F', $number), '0'), '.') ?: '0';
    }

    private static function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

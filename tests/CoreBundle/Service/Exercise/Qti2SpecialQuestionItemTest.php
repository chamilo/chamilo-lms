<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Service\Exercise\Qti2SpecialQuestionItem;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use DOMDocument;
use DOMElement;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use const LIBXML_NONET;

/**
 * Portable QTI definitions preserve geometry and manual-review question types.
 */
final class Qti2SpecialQuestionItemTest extends TestCase
{
    public function testOralInteractionRequiresFileResponseAndPreservesScore(): void
    {
        $xml = $this->write($this->question(13, 4.5));
        self::assertStringContainsString('chamilo:oral-expression', $xml);
        self::assertStringContainsString('baseType="file"', $xml);
        self::assertStringNotContainsString('map_correct', $xml);
        self::assertSame(['type' => 13, 'score' => 4.5], Qti2SpecialQuestionItem::read($this->item($xml)));
    }

    public function testUploadInteractionRequiresManualReview(): void
    {
        $xml = $this->write($this->question(23, 10.0));
        self::assertStringContainsString('chamilo:upload-answer', $xml);
        self::assertStringContainsString('<uploadInteraction ', $xml);
        self::assertSame(['type' => 23, 'score' => 10.0], Qti2SpecialQuestionItem::read($this->item($xml)));
    }

    public function testUnmarkedStandardUploadIsNotMistakenForOralRecording(): void
    {
        $xml = str_replace('label="chamilo:oral-expression"', 'label=""', $this->write($this->question(13, 2.0)));
        self::assertSame(23, Qti2SpecialQuestionItem::read($this->item($xml))['type']);
    }

    public function testHotspotRectangleCircleAndPolygonRoundTrip(): void
    {
        $question = $this->question(6, 7.5);
        $this->addZone($question, 101, 'Square region', 'square', '10;20|30|40', 1, 2.0);
        $this->addZone($question, 102, 'Round region', 'circle', '50;40|20|20', 2, 2.5);
        $this->addZone($question, 103, 'Triangle region', 'poly', '10;10|20;10|20;20', 3, 3.0);
        $xml = $this->write($question);
        self::assertStringContainsString('<hotspotInteraction ', $xml);
        self::assertStringContainsString('coords="10,20,40,60"', $xml);
        self::assertStringContainsString('coords="60,50,10"', $xml);
        self::assertStringContainsString('coords="10,10,20,10,20,20"', $xml);
        $read = Qti2SpecialQuestionItem::read($this->item($xml));
        self::assertSame(6, $read['type']);
        self::assertSame('qti_media/'.str_repeat('a', 64).'.png', $read['image']);
        self::assertSame(7.5, array_sum(array_column($read['zones'], 'score')));
        self::assertSame(['square', 'circle', 'poly'], array_column($read['zones'], 'hotspotType'));
        self::assertSame(['10;20|30|40', '50;40|20|20', '10;10|20;10|20;20'], array_column($read['zones'], 'coordinates'));
    }

    public function testGraphicOrderingKeepsPositionAndScore(): void
    {
        $question = $this->question(7, 6.0);
        $this->addZone($question, 110, 'First', 'square', '1;1|20|20', 1, 2.0);
        $this->addZone($question, 111, 'Second', 'square', '22;22|20|20', 2, 4.0);
        $xml = $this->write($question);
        self::assertStringContainsString('<graphicOrderInteraction ', $xml);
        self::assertStringContainsString('cardinality="ordered"', $xml);
        self::assertStringContainsString('<baseValue baseType="float">6</baseValue>', $xml);
        $read = Qti2SpecialQuestionItem::read($this->item($xml));
        self::assertSame(7, $read['type']);
        self::assertSame(6.0, $read['score']);
        self::assertSame(['First', 'Second'], array_column($read['zones'], 'answer'));
        self::assertSame([2.0, 4.0], array_column($read['zones'], 'score'));
    }

    public function testMalformedHotspotCoordinatesAreRejected(): void
    {
        $xml = $this->write($this->hotspotQuestion());
        $xml = str_replace('coords="10,20,40,60"', 'coords="10,20,5,60"', $xml);
        self::assertNull(Qti2SpecialQuestionItem::read($this->item($xml)));
    }

    public function testForeignArchivePathIsRejected(): void
    {
        $xml = str_replace('qti_media/', '../outside/', $this->write($this->hotspotQuestion()));
        self::assertNull(Qti2SpecialQuestionItem::read($this->item($xml)));
    }

    public function testEllipseIsRejectedRatherThanChangingScoringGeometry(): void
    {
        $question = $this->question(6, 3.0);
        $this->addZone($question, 101, 'Ellipse', 'circle', '10;20|50|20', 1, 3.0);
        $this->expectException(InvalidArgumentException::class);
        $this->write($question);
    }

    private function hotspotQuestion(): CQuizQuestion
    {
        $question = $this->question(6, 2.0);
        $this->addZone($question, 101, 'Region', 'square', '10;20|30|40', 1, 2.0);

        return $question;
    }

    private function question(int $type, float $score): CQuizQuestion
    {
        $question = (new CQuizQuestion())->setType($type)->setQuestion('Describe the target')->setDescription('Follow the instructions')->setPonderation($score);
        (new ReflectionProperty(CQuizQuestion::class, 'iid'))->setValue($question, 900);

        return $question;
    }

    private function addZone(CQuizQuestion $question, int $id, string $text, string $type, string $coordinates, int $position, float $score): void
    {
        $answer = (new CQuizAnswer())->setQuestion($question)->setAnswer($text)->setCorrect(0)->setPosition($position)->setPonderation($score)->setHotspotType($type)->setHotspotCoordinates($coordinates);
        (new ReflectionProperty(CQuizAnswer::class, 'iid'))->setValue($answer, $id);
        $question->getAnswers()->add($answer);
    }

    private function write(CQuizQuestion $question): string
    {
        $image = \in_array((int) $question->getType(), [6, 7], true) ? [
            'path' => 'qti_media/'.str_repeat('a', 64).'.png',
            'mime' => 'image/png',
            'width' => 200,
            'height' => 120,
        ] : null;

        return '<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1" identifier="QST_900" title="Target" label="'
            .Qti2SpecialQuestionItem::label((int) $question->getType()).'">'
            .Qti2SpecialQuestionItem::write($question, 'QST_900', $image).'</assessmentItem>';
    }

    private function item(string $xml): DOMElement
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($document->loadXML($xml, LIBXML_NONET));

        return $document->documentElement;
    }
}

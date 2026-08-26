<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Service\Exercise\ExerciseRegressionFixtureQuestionFactory;
use PHPUnit\Framework\TestCase;

final class ExerciseRegressionFixtureQuestionFactoryTest extends TestCase
{
    public function testSupportedTypesMatchCurrentModernQuestionEditorCoverage(): void
    {
        $factory = new ExerciseRegressionFixtureQuestionFactory();

        $supported = $factory->supportedTypes();

        self::assertCount(30, $supported);
        self::assertCount(30, array_unique($supported));
        self::assertNotContains(7, $supported);
        self::assertContains(8, $supported);
        self::assertContains(23, $supported);
        self::assertContains(30, $supported);
        self::assertContains(31, $supported);

        $standard = $factory->standardTypes();
        self::assertCount(29, $standard);
        self::assertNotContains(8, $standard);
        self::assertNotSame(31, $standard[0]);
        self::assertNotSame(31, end($standard));
        self::assertSame([8], $factory->adaptiveTypes());
    }

    public function testEverySupportedTypeBuildsACompleteNamedPayload(): void
    {
        $factory = new ExerciseRegressionFixtureQuestionFactory();

        foreach ($factory->supportedTypes() as $type) {
            $payload = $factory->create($type);

            self::assertSame($type, $payload->type);
            self::assertNotSame('', trim(strip_tags($payload->title)));
            self::assertStringContainsString(\sprintf('T%02d', $type), $payload->title);
        }
    }

    public function testSpecialTypesContainRequiredDeterministicFixtureData(): void
    {
        $factory = new ExerciseRegressionFixtureQuestionFactory();

        $hotspot = $factory->create(6);
        self::assertStringStartsWith('data:image/png;base64,', $hotspot->hotspotImageData);
        self::assertNotSame('0;0|0|0', $hotspot->hotspotItems[0]['coordinates']);

        $annotation = $factory->create(20);
        self::assertStringStartsWith('data:image/png;base64,', $annotation->annotationImageData);

        $calculated = $factory->create(16);
        self::assertSame('[x] + [y]', $calculated->calculatedFormula);
        self::assertCount(2, $calculated->calculatedRanges);

        $onlyoffice = $factory->create(30);
        self::assertSame('chamilo-regression-answer.docx', $onlyoffice->onlyofficeTemplateName);
        self::assertStringContainsString(';base64,', $onlyoffice->onlyofficeTemplateData);

        $pageBreak = $factory->create(31);
        self::assertSame(0.0, $pageBreak->score);
    }
}

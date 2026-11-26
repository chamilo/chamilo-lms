<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificateGenerator;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class GradebookCertificateIssueDateTest extends TestCase
{
    public function testFrenchIssueDateUsesCourseLanguageWithoutTime(): void
    {
        $date = new DateTimeImmutable('2026-11-18 16:09:00', new DateTimeZone('America/Lima'));
        $format = new ReflectionMethod(GradebookCertificateGenerator::class, 'formatIssueDate');

        self::assertSame('18 novembre 2026', $format->invoke(null, $date, 'fr_FR', false));
        $withTime = $format->invoke(null, $date, 'fr_FR', true);
        self::assertStringContainsString('18 novembre 2026', $withTime);
        self::assertStringContainsString('16:09', $withTime);
    }

    public function testIssueDateUsesRequestedLocaleWithoutChangingTimezone(): void
    {
        $date = new DateTimeImmutable('2026-11-18 23:30:00', new DateTimeZone('America/Lima'));
        $format = new ReflectionMethod(GradebookCertificateGenerator::class, 'formatIssueDate');
        $defaultTimezone = date_default_timezone_get();

        self::assertSame('November 18, 2026', $format->invoke(null, $date, 'en_US', false));
        self::assertSame('18 de noviembre de 2026', $format->invoke(null, $date, 'es', false));
        self::assertSame('2026-11-18 23:30', $date->format('Y-m-d H:i'));
        self::assertSame($defaultTimezone, date_default_timezone_get());
    }
}

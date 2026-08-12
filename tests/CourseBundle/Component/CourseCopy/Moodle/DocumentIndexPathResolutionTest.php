<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Component\CourseCopy\Moodle;

use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleExport;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

final class DocumentIndexPathResolutionTest extends TestCase
{
    public function testPrefersNormalizedWrapperPathOverOriginalDocumentPath(): void
    {
        $export = $this->createExport();

        $doc = new stdClass();
        $doc->path = 'Module 1: Finding Your Way Around Chamilo 3.0';

        $wrap = new stdClass();
        $wrap->path = 'document/Module 1: Finding Your Way Around Chamilo 3.0.html';
        $wrap->obj = $doc;

        $method = (new ReflectionClass($export))->getMethod('resolveDocumentIndexRawPath');
        $method->setAccessible(true);

        self::assertSame(
            'document/Module 1: Finding Your Way Around Chamilo 3.0.html',
            $method->invoke($export, $doc, $wrap)
        );
    }

    public function testFallsBackToDocumentPathWhenWrapperPathIsMissing(): void
    {
        $export = $this->createExport();

        $doc = new stdClass();
        $doc->path = 'document/manual.pdf';

        $wrap = new stdClass();
        $wrap->obj = $doc;

        $method = (new ReflectionClass($export))->getMethod('resolveDocumentIndexRawPath');
        $method->setAccessible(true);

        self::assertSame('document/manual.pdf', $method->invoke($export, $doc, $wrap));
    }

    private function createExport(): MoodleExport
    {
        $reflection = new ReflectionClass(MoodleExport::class);

        /** @var MoodleExport $export */
        return $reflection->newInstanceWithoutConstructor();
    }
}

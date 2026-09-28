<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Component\CourseCopy;

use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class CourseRestorerDocumentClassificationTest extends TestCase
{
    public function testJpegContainingHtmlLikeBytesIsNotClassifiedAsHtml(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'chamilo-jpeg-');
        self::assertNotFalse($path);

        try {
            $jpegLikePayload = "\xFF\xD8\xFF\xE0JFIF\x00".str_repeat('A', 2048).'<P'.str_repeat('B', 2048);
            file_put_contents($path, $jpegLikePayload);

            self::assertFalse($this->isHtmlDocumentFile($path, 'actors.jpg'));
        } finally {
            @unlink($path);
        }
    }

    public function testExtensionlessHtmlFragmentIsStillDetected(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'chamilo-html-');
        self::assertNotFalse($path);

        try {
            file_put_contents($path, str_repeat(' ', 4096).'<p>Learning path content</p>');

            self::assertTrue($this->isHtmlDocumentFile($path, 'Module 1'));
        } finally {
            @unlink($path);
        }
    }

    private function isHtmlDocumentFile(string $filePath, string $nameGuess): bool
    {
        $reflection = new ReflectionClass(CourseRestorer::class);
        $restorer = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('isHtmlDocumentFile');
        $method->setAccessible(true);

        return (bool) $method->invoke($restorer, $filePath, $nameGuess);
    }
}

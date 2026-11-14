<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\PluginBundle\H5pImport\Entity\H5pImport;
use H5pImportPlugin;
use PHPUnit\Framework\TestCase;

/**
 * Results recorded by public/plugin/H5pImport/src/ajax.php.
 *
 * An H5P score is computed by the browser, so the server cannot recalculate it. What it can do is refuse a result
 * that contradicts itself, and keep it on the learning path item that launches that very content.
 *
 * The decisions live in H5pImportPlugin (behavioural tests). The handler is procedural, so its guards are pinned
 * at source level: H5PCore::ajaxError() and ajaxSuccess() only print a response, they do not end the request, so
 * a guard that does not exit lets the request go on to the write.
 */
final class H5pImportResultTest extends TestCase
{
    private const HANDLER = __DIR__.'/../../../public/plugin/H5pImport/src/ajax.php';

    public function testScoreStaysWithinItsOwnRange(): void
    {
        self::assertTrue(H5pImportPlugin::isValidScore(0, 0), 'A content without a score reports 0 out of 0');
        self::assertTrue(H5pImportPlugin::isValidScore(0, 5));
        self::assertTrue(H5pImportPlugin::isValidScore(5, 5));

        self::assertFalse(H5pImportPlugin::isValidScore(6, 5), 'A score cannot exceed its maximum');
        self::assertFalse(H5pImportPlugin::isValidScore(-1, 5), 'A score cannot be negative');
        self::assertFalse(H5pImportPlugin::isValidScore(-2, -1), 'A maximum cannot be negative');
    }

    public function testResultOnlyReachesTheLearningPathItemOfItsContent(): void
    {
        $content = new H5pImport();
        $content->setIid(7);

        self::assertTrue(H5pImportPlugin::isLpItemOfContent($this->lpItem('h5p', '7'), $content));
        self::assertFalse(
            H5pImportPlugin::isLpItemOfContent($this->lpItem('h5p', '8'), $content),
            'The item of another content must not be completed by this one'
        );
        self::assertFalse(
            H5pImportPlugin::isLpItemOfContent($this->lpItem('quiz', '7'), $content),
            'An item of another tool with the same identifier is not this content'
        );
    }

    public function testEveryResponseOfTheHandlerEndsTheRequest(): void
    {
        $source = (string) file_get_contents(self::HANDLER);

        $found = preg_match_all('/H5PCore::ajax(?:Error|Success)\([^;]*\);\s*(\S*)/', $source, $matches);

        self::assertGreaterThan(0, $found);

        foreach ($matches[1] as $index => $next) {
            // Nothing follows the last response of the file.
            self::assertContains($next, ['exit;', ''], 'Response #'.($index + 1).' lets the request continue');
        }
    }

    public function testHandlerChecksAResultBeforeRecordingIt(): void
    {
        $source = (string) file_get_contents(self::HANDLER);

        $scoreCheck = strpos($source, 'H5pImportPlugin::isValidScore(');
        $itemCheck = strpos($source, 'H5pImportPlugin::isLpItemOfContent(');

        self::assertNotFalse($scoreCheck);
        self::assertNotFalse($itemCheck);
        self::assertLessThan(strpos($source, '->setScore('), $scoreCheck);
        self::assertLessThan(strpos($source, '$lpItemView->setScore('), $itemCheck);
    }

    private function lpItem(string $type, string $path): CLpItem
    {
        return (new CLpItem())->setItemType($type)->setPath($path);
    }
}

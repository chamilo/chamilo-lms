<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Admin;

use Chamilo\CoreBundle\Service\Admin\CourseCsvImportValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CourseCsvImportValidatorTest extends TestCase
{
    public function testNormalizesExportedColumnsAndOptionalValues(): void
    {
        $rows = (new CourseCsvImportValidator())->normalizeRows([
            [
                "\xEF\xBB\xBFCode" => ' IMP9144A ',
                'Title' => ' First course ',
                'CourseCategory' => ' QA9144 ',
                'CourseCategoryName' => ' Test category ',
                'Teacher' => ' admin ',
                'Language' => ' english ',
                'Users' => '',
                'OtherTeachers' => '',
            ],
        ]);

        self::assertCount(1, $rows);
        self::assertSame('IMP9144A', $rows[0]['Code']);
        self::assertSame('First course', $rows[0]['Title']);
        self::assertSame('QA9144', $rows[0]['CourseCategory']);
        self::assertSame('Test category', $rows[0]['CourseCategoryName']);
        self::assertSame('admin', $rows[0]['Teacher']);
    }

    public function testAcceptsMissingOptionalColumns(): void
    {
        $rows = (new CourseCsvImportValidator())->normalizeRows([
            ['Code' => 'IMP9144B', 'Title' => 'Second', 'CourseCategory' => 'QA9144'],
        ]);

        self::assertSame('', $rows[0]['Teacher']);
        self::assertSame('', $rows[0]['Language']);
        self::assertSame('', $rows[0]['CourseCategoryName']);
    }

    public function testRejectsMissingRequiredHeader(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required CSV column: CourseCategory.');

        (new CourseCsvImportValidator())->normalizeRows([
            ['Code' => 'IMP9144C', 'Title' => 'Third'],
        ]);
    }

    public function testRejectsEmptyMandatoryCellWithoutWriting(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Line 2: Title is required.');

        (new CourseCsvImportValidator())->normalizeRows([
            ['Code' => 'IMP9144D', 'Title' => ' ', 'CourseCategory' => 'QA9144'],
        ]);
    }

    public function testRejectsCaseInsensitiveDuplicateCodes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Line 3: duplicate course code 'imp9144e'.");

        (new CourseCsvImportValidator())->normalizeRows([
            ['Code' => 'IMP9144E', 'Title' => 'One', 'CourseCategory' => 'QA9144'],
            ['Code' => 'imp9144e', 'Title' => 'Two', 'CourseCategory' => 'QA9144'],
        ]);
    }

    public function testRejectsCsvWithoutCourseRows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The CSV file contains no courses.');

        (new CourseCsvImportValidator())->normalizeRows([]);
    }
}

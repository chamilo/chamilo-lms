<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\LearningPath;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Service\LearningPath\ScormPackageImporter;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use const UPLOAD_ERR_INI_SIZE;
use const UPLOAD_ERR_PARTIAL;

final class ScormPackageImporterTest extends TestCase
{
    public function testItReportsThePhpUploadLimitForOversizedPackages(): void
    {
        $package = new UploadedFile(
            '',
            'minerias.zip',
            'application/zip',
            UPLOAD_ERR_INI_SIZE,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($package->getErrorMessage());

        $this->createImporterWithoutDependencies()->import(
            $package,
            $this->createCourseWithoutConstructor(),
            null,
            null,
            true,
            'local',
            'Scorm',
            false,
        );
    }

    public function testItKeepsTheGenericMessageForOtherUploadFailures(): void
    {
        $package = new UploadedFile(
            '',
            'minerias.zip',
            'application/zip',
            UPLOAD_ERR_PARTIAL,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The uploaded package is not valid.');

        $this->createImporterWithoutDependencies()->import(
            $package,
            $this->createCourseWithoutConstructor(),
            null,
            null,
            true,
            'local',
            'Scorm',
            false,
        );
    }

    private function createImporterWithoutDependencies(): ScormPackageImporter
    {
        $reflection = new ReflectionClass(ScormPackageImporter::class);

        return $reflection->newInstanceWithoutConstructor();
    }

    private function createCourseWithoutConstructor(): Course
    {
        $reflection = new ReflectionClass(Course::class);

        return $reflection->newInstanceWithoutConstructor();
    }
}

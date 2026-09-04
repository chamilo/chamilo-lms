<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Document;

use Chamilo\CoreBundle\Service\Document\CourseDocumentContentService;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CourseDocumentContentServiceDuplicateTitleTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testDetectsExistingTitleScopedToTheParentFolder(): void
    {
        self::bootKernel();

        $service = self::getContainer()->get(CourseDocumentContentService::class);
        $documentRepo = self::getContainer()->get(CDocumentRepository::class);

        $course = $this->createCourse('Duplicate title');
        $admin = $this->getUser('admin');
        $rootNodeId = (int) $course->getResourceNode()->getId();

        $documentRepo->create(
            (new CDocument())
                ->setFiletype('file')
                ->setTitle('At the root')
                ->setParent($course)
                ->setCreator($admin)
                ->addCourseLink($course)
        );

        $folder = (new CDocument())
            ->setFiletype('folder')
            ->setTitle('A folder')
            ->setParent($course)
            ->setCreator($admin)
            ->addCourseLink($course)
        ;
        $documentRepo->create($folder);

        $documentRepo->create(
            (new CDocument())
                ->setFiletype('file')
                ->setTitle('Inside the folder')
                ->setParent($folder)
                ->setCreator($admin)
                ->addCourseLink($course)
        );

        self::assertTrue($service->titleExistsInParentFolder($course, $rootNodeId, 'At the root'));
        self::assertFalse($service->titleExistsInParentFolder($course, $rootNodeId, 'Not A Real Title 12345'));

        // The scoping is the point: this title exists in the course, but under the
        // folder rather than directly under the course root.
        self::assertFalse($service->titleExistsInParentFolder($course, $rootNodeId, 'Inside the folder'));
        self::assertTrue(
            $service->titleExistsInParentFolder($course, (int) $folder->getResourceNode()->getId(), 'Inside the folder')
        );
    }
}

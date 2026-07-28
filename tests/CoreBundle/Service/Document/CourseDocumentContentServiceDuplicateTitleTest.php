<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Document;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Service\Document\CourseDocumentContentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CourseDocumentContentServiceDuplicateTitleTest extends KernelTestCase
{
    public function testDetectsExistingTitleScopedToTheParentFolder(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $service = $container->get(CourseDocumentContentService::class);

        $course = $em->getRepository(Course::class)->find(2);
        if (!$course instanceof Course) {
            self::markTestSkipped('Course #2 does not exist in this DB, skipping.');
        }

        $rootNodeId = (int) $course->getResourceNode()?->getId();

        self::assertTrue(
            $service->titleExistsInParentFolder($course, $rootNodeId, 'Certificado predeterminado'),
            'A direct child of the course root node with this exact title exists and must be detected.',
        );
        self::assertFalse(
            $service->titleExistsInParentFolder($course, $rootNodeId, 'Not A Real Title 12345'),
        );
        self::assertFalse(
            $service->titleExistsInParentFolder($course, $rootNodeId, 'Intro 1'),
            '"Intro 1" exists in this course but under the LP1 folder, not directly under the course root — must not match.',
        );
    }
}

<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CourseBundle\Component\CourseCopy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use Chamilo\CourseBundle\Entity\CQuiz;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class CourseRestorerLearningPathQuizVisibilityTest extends TestCase
{
    public function testRestoredQuizAddedToLearningPathIsSetToDraft(): void
    {
        $course = $this->createMock(Course::class);
        $resourceLink = (new ResourceLink())->setVisibility(ResourceLink::VISIBILITY_PUBLISHED);

        $resourceNode = $this->createMock(ResourceNode::class);
        $resourceNode
            ->expects($this->once())
            ->method('getResourceLinkByContext')
            ->with($course, null)
            ->willReturn($resourceLink)
        ;

        $quiz = $this->createMock(CQuiz::class);
        $quiz
            ->method('getResourceNode')
            ->willReturn($resourceNode)
        ;

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($quiz)
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(CQuiz::class)
            ->willReturn($repository)
        ;
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($resourceLink)
        ;

        $this->callVisibilityHelper('quiz', '42', $course, $entityManager);

        $this->assertSame(ResourceLink::VISIBILITY_DRAFT, $resourceLink->getVisibility());
    }

    public function testNonQuizLearningPathItemDoesNotChangeResourceVisibility(): void
    {
        $course = $this->createMock(Course::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('getRepository')
        ;
        $entityManager
            ->expects($this->never())
            ->method('persist')
        ;

        $this->callVisibilityHelper('document', '42', $course, $entityManager);
    }

    private function callVisibilityHelper(
        string $itemType,
        string $path,
        Course $course,
        EntityManagerInterface $entityManager
    ): void {
        $reflection = new ReflectionClass(CourseRestorer::class);
        $restorer = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('setLearningPathQuizVisibilityToDraft');
        $method->setAccessible(true);
        $method->invoke($restorer, $itemType, $path, $course, null, $entityManager);
    }
}

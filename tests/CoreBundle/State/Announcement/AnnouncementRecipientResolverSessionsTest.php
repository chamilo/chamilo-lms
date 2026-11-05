<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\State\Announcement;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Repository\Node\UsergroupRepository;
use Chamilo\CoreBundle\State\Announcement\AnnouncementRecipientResolver;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class AnnouncementRecipientResolverSessionsTest extends TestCase
{
    /**
     * @dataProvider sessionCountProvider
     */
    public function testSessionOptionRequiresAnActualCourseSession(int $count, bool $expected): void
    {
        $course = $this->createMock(Course::class);
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('count')
            ->with(['course' => $course])
            ->willReturn($count)
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('getRepository')
            ->with(SessionRelCourse::class)
            ->willReturn($repository)
        ;

        // Only the session lookup is exercised; group repositories are not accessed.
        $groupRepository = (new ReflectionClass(CGroupRepository::class))->newInstanceWithoutConstructor();
        $usergroupRepository = (new ReflectionClass(UsergroupRepository::class))->newInstanceWithoutConstructor();

        $resolver = new AnnouncementRecipientResolver($entityManager, $groupRepository, $usergroupRepository);

        self::assertSame($expected, $resolver->hasCourseSessions($course));
    }

    public static function sessionCountProvider(): array
    {
        return [
            'course without sessions' => [0, false],
            'course with one session' => [1, true],
            'course with multiple sessions' => [3, true],
        ];
    }
}

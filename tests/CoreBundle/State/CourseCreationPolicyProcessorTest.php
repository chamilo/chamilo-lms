<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\State;

use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\State\CourseCreationPolicyProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class CourseCreationPolicyProcessorTest extends TestCase
{
    public function testTeacherCannotCreateCourseDirectlyWhenApprovalIsEnabled(): void
    {
        $persist = $this->createMock(ProcessorInterface::class);
        $persist->expects(self::never())->method('process');

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);

        $settings = $this->createMock(SettingsManager::class);
        $settings->method('getSetting')
            ->with('course.course_validation', true)
            ->willReturn('true')
        ;

        $processor = new CourseCreationPolicyProcessor($persist, $security, $settings);

        $this->expectException(AccessDeniedHttpException::class);
        $processor->process(new Course(), new Post());
    }

    public function testTeacherCanCreateCourseWhenApprovalIsDisabled(): void
    {
        $course = new Course();
        $persist = $this->createMock(ProcessorInterface::class);
        $persist->expects(self::once())->method('process')->willReturn($course);

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);

        $settings = $this->createMock(SettingsManager::class);
        $settings->method('getSetting')
            ->with('course.course_validation', true)
            ->willReturn('false')
        ;

        $processor = new CourseCreationPolicyProcessor($persist, $security, $settings);
        self::assertSame($course, $processor->process($course, new Post()));
    }

    public function testAdministratorCanCreateCourseWhenApprovalIsEnabled(): void
    {
        $course = new Course();
        $persist = $this->createMock(ProcessorInterface::class);
        $persist->expects(self::once())->method('process')->willReturn($course);

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(true);

        $settings = $this->createMock(SettingsManager::class);
        $settings->expects(self::never())->method('getSetting');

        $processor = new CourseCreationPolicyProcessor($persist, $security, $settings);
        self::assertSame($course, $processor->process($course, new Post()));
    }
}

<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Mcp;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Service\Mcp\McpTestReadCourseContext;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class McpTestReadCourseContextTest extends KernelTestCase
{
    private McpTestReadCourseContext $context;
    private TokenStorageInterface $tokenStorage;
    private Course $course;
    private User $user;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);
        Container::setSession(new Session(new MockArraySessionStorage()));

        $entityManager = $container->get(EntityManagerInterface::class);
        $course = $entityManager->getRepository(Course::class)->find(2);
        $user = $entityManager->getRepository(User::class)->find(1);
        if (!$course instanceof Course || !$user instanceof User) {
            self::markTestSkipped('Course #2 / User #1 fixtures are not present in this DB.');
        }

        $this->context = $container->get(McpTestReadCourseContext::class);
        $this->tokenStorage = $container->get(TokenStorageInterface::class);
        $this->course = $course;
        $this->user = $user;
    }

    public function testTeacherOfTheCourseCanReadItsOwnCourse(): void
    {
        $this->tokenStorage->setToken(
            new UsernamePasswordToken($this->user, 'api', ['ROLE_USER', 'ROLE_TEACHER'])
        );

        $resolved = $this->context->resolve((int) $this->course->getId());

        self::assertSame($this->course->getId(), $resolved['course']->getId());
    }

    public function testPlainTeacherIsDeniedForACourseTheyDoNotTeach(): void
    {
        $this->tokenStorage->setToken(
            new UsernamePasswordToken($this->user, 'api', ['ROLE_USER', 'ROLE_TEACHER'])
        );

        $this->expectException(AccessDeniedException::class);
        $this->context->resolve(999999999);
    }

    public function testQuestionManagerCanReadAnyCourseWithoutBeingItsTeacher(): void
    {
        $this->tokenStorage->setToken(
            new UsernamePasswordToken($this->user, 'api', ['ROLE_USER', 'ROLE_QUESTION_MANAGER'])
        );

        $resolved = $this->context->resolve((int) $this->course->getId());

        self::assertSame($this->course->getId(), $resolved['course']->getId());
    }

    public function testGlobalAdminCanReadAnyCourseWithoutBeingItsTeacher(): void
    {
        $this->tokenStorage->setToken(
            new UsernamePasswordToken($this->user, 'api', ['ROLE_USER', 'ROLE_GLOBAL_ADMIN'])
        );

        $resolved = $this->context->resolve((int) $this->course->getId());

        self::assertSame($this->course->getId(), $resolved['course']->getId());
    }

    public function testAdminCanReadAnyCourseWithoutBeingItsTeacher(): void
    {
        $this->tokenStorage->setToken(
            new UsernamePasswordToken($this->user, 'api', ['ROLE_USER', 'ROLE_ADMIN'])
        );

        $resolved = $this->context->resolve((int) $this->course->getId());

        self::assertSame($this->course->getId(), $resolved['course']->getId());
    }

    public function testElevatedRoleStillRequiresAnExistingCourse(): void
    {
        $this->tokenStorage->setToken(
            new UsernamePasswordToken($this->user, 'api', ['ROLE_USER', 'ROLE_ADMIN'])
        );

        $this->expectException(InvalidArgumentException::class);
        $this->context->resolve(999999999);
    }

    public function testCourseIdMustBePositive(): void
    {
        $this->tokenStorage->setToken(
            new UsernamePasswordToken($this->user, 'api', ['ROLE_USER', 'ROLE_ADMIN'])
        );

        $this->expectException(InvalidArgumentException::class);
        $this->context->resolve(0);
    }
}

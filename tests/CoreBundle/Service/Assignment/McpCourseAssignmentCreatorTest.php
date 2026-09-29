<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Assignment;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Service\Assignment\McpCourseAssignmentCreator;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use const DATE_ATOM;

final class McpCourseAssignmentCreatorTest extends KernelTestCase
{
    private McpCourseAssignmentCreator $creator;
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

        $this->creator = $container->get(McpCourseAssignmentCreator::class);
        $this->course = $course;
        $this->user = $user;

        $container->get(TokenStorageInterface::class)->setToken(
            new UsernamePasswordToken($user, 'api', $user->getRoles())
        );
    }

    public function testRelativeDeadlineIsStableWhenTheAssignmentIsReused(): void
    {
        $title = 'MCP Assignment Relative Due '.bin2hex(random_bytes(6));
        $initialDueDate = (new DateTime('+7 days'))->setTime(12, 0);
        $recalculatedDueDate = (clone $initialDueDate)->modify('+1 minute');

        $created = $this->creator->create(
            $this->course,
            $this->user,
            $title,
            'Assignment idempotency regression test.',
            20.0,
            false,
            0,
            $initialDueDate,
            true,
        );

        self::assertTrue($created['created']);
        self::assertFalse($created['reused_existing']);
        self::assertSame($initialDueDate->format(DATE_ATOM), $created['due_at']);

        $reused = $this->creator->create(
            $this->course,
            $this->user,
            $title,
            'Assignment idempotency regression test.',
            20.0,
            false,
            0,
            $recalculatedDueDate,
            true,
        );

        self::assertFalse($reused['created']);
        self::assertTrue($reused['reused_existing']);
        self::assertFalse($reused['updated_existing']);
        self::assertSame($created['assignment_id'], $reused['assignment_id']);
        self::assertSame($initialDueDate->format(DATE_ATOM), $reused['due_at']);
    }

    public function testExplicitDeadlineCanUpdateAReusedAssignment(): void
    {
        $title = 'MCP Assignment Explicit Due '.bin2hex(random_bytes(6));
        $initialDueDate = (new DateTime('+7 days'))->setTime(12, 0);
        $updatedDueDate = (clone $initialDueDate)->modify('+1 day');

        $created = $this->creator->create(
            $this->course,
            $this->user,
            $title,
            'Assignment explicit deadline regression test.',
            20.0,
            false,
            0,
            $initialDueDate,
            false,
        );

        $updated = $this->creator->create(
            $this->course,
            $this->user,
            $title,
            'Assignment explicit deadline regression test.',
            20.0,
            false,
            0,
            $updatedDueDate,
            false,
        );

        self::assertFalse($updated['created']);
        self::assertTrue($updated['reused_existing']);
        self::assertTrue($updated['updated_existing']);
        self::assertSame($created['assignment_id'], $updated['assignment_id']);
        self::assertSame($updatedDueDate->format(DATE_ATOM), $updated['due_at']);
    }
}

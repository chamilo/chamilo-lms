<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Enums\GradebookCalculationMode;
use Chamilo\CoreBundle\Service\Gradebook\GradebookRootCategoryFactory;
use PHPUnit\Framework\TestCase;

final class GradebookRootCategoryFactoryTest extends TestCase
{
    public function testCourseRootUsesTheExistingGradebookDefaults(): void
    {
        $course = $this->createMock(Course::class);
        $course->method('getCode')->willReturn('AI101');
        $user = $this->createMock(User::class);

        $root = (new GradebookRootCategoryFactory())->create($course, null, $user);

        self::assertSame('AI101', $root->getTitle());
        self::assertSame($course, $root->getCourse());
        self::assertSame($user, $root->getUser());
        self::assertNull($root->getParent());
        self::assertNull($root->getSession());
        self::assertNull($root->getDescription());
        self::assertSame(100.0, $root->getWeight());
        self::assertSame(GradebookCalculationMode::WEIGHTED_AVERAGE, $root->getCalculationMode());
        self::assertFalse($root->getVisible());
        self::assertSame(75, $root->getCertifMinScore());
        self::assertSame(0, $root->getLocked());
        self::assertFalse($root->getGenerateCertificates());
        self::assertTrue($root->getIsRequirement());
        self::assertSame(1, $root->getAllowSkillsBySubcategory());
    }

    public function testSessionRootUsesTheExistingSessionDefaults(): void
    {
        $course = $this->createMock(Course::class);
        $course->method('getCode')->willReturn('AI101');
        $session = $this->createMock(Session::class);
        $session->method('getTitle')->willReturn('November');
        $user = $this->createMock(User::class);

        $root = (new GradebookRootCategoryFactory())->create($course, $session, $user);

        self::assertSame('AI101 - Session November', $root->getTitle());
        self::assertSame($session, $root->getSession());
        self::assertFalse($root->getIsRequirement());
    }
}

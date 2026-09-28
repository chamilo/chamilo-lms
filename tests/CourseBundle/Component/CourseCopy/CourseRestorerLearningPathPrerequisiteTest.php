<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Component\CourseCopy;

use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use Chamilo\CourseBundle\Entity\CLpItem;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

final class CourseRestorerLearningPathPrerequisiteTest extends TestCase
{
    public function testNumericPrerequisitesAreRemappedToDestinationItemIds(): void
    {
        $first = (new CLpItem())->setPrerequisite('');
        $second = (new CLpItem())->setPrerequisite('116');
        $third = (new CLpItem())->setPrerequisite('104');

        $this->setItemId($first, 701);
        $this->setItemId($second, 702);
        $this->setItemId($third, 703);

        $restorerReflection = new ReflectionClass(CourseRestorer::class);
        $restorer = $restorerReflection->newInstanceWithoutConstructor();
        $method = $restorerReflection->getMethod('remapLearningPathPrerequisites');
        $method->setAccessible(true);

        $method->invoke(
            $restorer,
            [
                116 => ['prerequisite' => ''],
                104 => ['prerequisite' => '116'],
                105 => ['prerequisite' => '104'],
            ],
            [
                116 => $first,
                104 => $second,
                105 => $third,
            ],
        );

        self::assertSame('', $first->getPrerequisite());
        self::assertSame('701', $second->getPrerequisite());
        self::assertSame('702', $third->getPrerequisite());
    }

    public function testNonNumericPrerequisiteIsKeptUntouched(): void
    {
        $item = (new CLpItem())->setPrerequisite('custom-rule');
        $this->setItemId($item, 801);

        $restorerReflection = new ReflectionClass(CourseRestorer::class);
        $restorer = $restorerReflection->newInstanceWithoutConstructor();
        $method = $restorerReflection->getMethod('remapLearningPathPrerequisites');
        $method->setAccessible(true);

        $method->invoke(
            $restorer,
            [801 => ['prerequisite' => 'custom-rule']],
            [801 => $item],
        );

        self::assertSame('custom-rule', $item->getPrerequisite());
    }

    private function setItemId(CLpItem $item, int $id): void
    {
        $property = new ReflectionProperty(CLpItem::class, 'iid');
        $property->setAccessible(true);
        $property->setValue($item, $id);
    }
}

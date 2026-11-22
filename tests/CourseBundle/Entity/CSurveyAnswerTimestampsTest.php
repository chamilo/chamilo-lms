<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Entity;

use Chamilo\CourseBundle\Entity\CSurveyAnswer;
use Doctrine\ORM\Mapping as ORM;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class CSurveyAnswerTimestampsTest extends TestCase
{
    public function testNewAnswerReceivesBothUtcTimestamps(): void
    {
        $answer = new CSurveyAnswer();
        $this->assertNull($answer->getCreatedAt());
        $this->assertNull($answer->getUpdatedAt());

        $answer->setTimestampsOnCreate();

        $this->assertNotNull($answer->getCreatedAt());
        $this->assertSame($answer->getCreatedAt(), $answer->getUpdatedAt());
        $this->assertSame('UTC', $answer->getCreatedAt()->getTimezone()->getName());
    }

    public function testUpdatingAnExistingAnswerKeepsItsCreationTime(): void
    {
        $answer = new CSurveyAnswer();
        $answer->setTimestampsOnCreate();
        $originalCreatedAt = $answer->getCreatedAt();
        $originalUpdatedAt = $answer->getUpdatedAt();

        $answer->setTimestampOnUpdate();

        $this->assertSame($originalCreatedAt, $answer->getCreatedAt());
        $this->assertNotSame($originalUpdatedAt, $answer->getUpdatedAt());
        $this->assertSame('UTC', $answer->getUpdatedAt()->getTimezone()->getName());
    }

    public function testUpdatingALegacyAnswerDoesNotInventItsCreationDate(): void
    {
        $answer = new CSurveyAnswer();
        $answer->setTimestampOnUpdate();

        $this->assertNull($answer->getCreatedAt());
        $this->assertNotNull($answer->getUpdatedAt());
    }

    public function testDoctrineLifecycleCallbacksAreDeclared(): void
    {
        $entity = new ReflectionClass(CSurveyAnswer::class);
        $this->assertCount(1, $entity->getAttributes(ORM\HasLifecycleCallbacks::class));

        $create = new ReflectionMethod(CSurveyAnswer::class, 'setTimestampsOnCreate');
        $update = new ReflectionMethod(CSurveyAnswer::class, 'setTimestampOnUpdate');

        $this->assertCount(1, $create->getAttributes(ORM\PrePersist::class));
        $this->assertCount(1, $update->getAttributes(ORM\PreUpdate::class));
    }
}

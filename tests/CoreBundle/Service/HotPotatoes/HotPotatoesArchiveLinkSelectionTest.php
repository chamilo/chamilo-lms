<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\HotPotatoes;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Service\HotPotatoes\HotPotatoesArchiveService;
use Chamilo\CourseBundle\Entity\CDocument;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

final class HotPotatoesArchiveLinkSelectionTest extends TestCase
{
    public function testOnlyAnExactActiveLinkCanBeRemoved(): void
    {
        $course = $this->course(13);
        $otherCourse = $this->course(14);
        $node = new ResourceNode();
        $document = (new CDocument())->setResourceNode($node);

        $current = (new ResourceLink())->setCourse($course);
        $foreign = (new ResourceLink())->setCourse($otherCourse);
        $deleted = (new ResourceLink())->setCourse($course)->setDeletedAt(new DateTime());
        $node->setResourceLinks(new ArrayCollection([$foreign, $deleted, $current]));

        $service = (new ReflectionClass(HotPotatoesArchiveService::class))->newInstanceWithoutConstructor();
        self::assertSame($current, $service->findExactActiveLink($document, $course, null, null));
        self::assertSame($foreign, $service->findExactActiveLink($document, $otherCourse, null, null));

        $current->setDeletedAt(new DateTime());
        self::assertNull($service->findExactActiveLink($document, $course, null, null));
    }

    public function testDuplicateActiveLinksAreNotEditable(): void
    {
        $course = $this->course(13);
        $document = (new CDocument())->setResourceNode((new ResourceNode())->setResourceLinks(new ArrayCollection([
            (new ResourceLink())->setCourse($course),
            (new ResourceLink())->setCourse($course),
        ])));

        $service = (new ReflectionClass(HotPotatoesArchiveService::class))->newInstanceWithoutConstructor();
        self::assertNull($service->findExactActiveLink($document, $course, null, null));
    }

    private function course(int $id): Course
    {
        $course = (new ReflectionClass(Course::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty(Course::class, 'id'))->setValue($course, $id);

        return $course;
    }
}

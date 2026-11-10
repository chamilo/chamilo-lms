<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Controller\Admin\AdminController;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class AdminFileLocationPathTest extends TestCase
{
    public function testSharedFileUsesEachCourseLinkHierarchy(): void
    {
        $firstRoot = $this->node(100, 'First course');
        $secondRoot = $this->node(200, 'Second course');
        $folder = $this->node(201, 'Chapter 2', $secondRoot);
        $file = $this->node(300, 'manual.pdf', $firstRoot);

        $firstLocation = $this->resolve($this->link(301, $file), $firstRoot);
        $secondLocation = $this->resolve(
            $this->link(302, $file, $this->link(303, $folder)),
            $secondRoot
        );

        self::assertSame(['path' => 'manual.pdf', 'folderNodeId' => 100], $firstLocation);
        self::assertSame(['path' => 'Chapter 2 / manual.pdf', 'folderNodeId' => 201], $secondLocation);
    }

    public function testLegacyNodeParentsAreUsedOnlyWithinTheCorrectContext(): void
    {
        $ownerRoot = $this->node(10, 'Owner');
        $otherRoot = $this->node(20, 'Other');
        $folder = $this->node(11, 'Materials', $ownerRoot);
        $file = $this->node(12, 'slides.pdf', $folder);
        $link = $this->link(13, $file);

        self::assertSame(
            ['path' => 'Materials / slides.pdf', 'folderNodeId' => 11],
            $this->resolve($link, $ownerRoot)
        );
        self::assertSame(
            ['path' => 'slides.pdf', 'folderNodeId' => 20],
            $this->resolve($link, $otherRoot)
        );
    }

    public function testPersonalFileWithoutResourceLinkUsesOwnerTree(): void
    {
        $ownerRoot = $this->node(1, 'Owner');
        $folder = $this->node(2, 'Invoices', $ownerRoot);
        $file = $this->node(3, 'receipt.pdf', $folder);
        $otherRoot = $this->node(4, 'Another user');

        $controller = (new ReflectionClass(AdminController::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(AdminController::class, 'getNodeLocationInContext');

        self::assertSame('Invoices / receipt.pdf', $method->invoke($controller, $file, $ownerRoot));
        self::assertNull($method->invoke($controller, $file, $otherRoot));
    }

    private function node(int $id, string $title, ?ResourceNode $parent = null): ResourceNode
    {
        $node = $this->createMock(ResourceNode::class);
        $node->method('getId')->willReturn($id);
        $node->method('getTitle')->willReturn($title);
        $node->method('getParent')->willReturn($parent);

        return $node;
    }

    private function link(int $id, ResourceNode $node, ?ResourceLink $parent = null): ResourceLink
    {
        $link = $this->createMock(ResourceLink::class);
        $link->method('getId')->willReturn($id);
        $link->method('getResourceNode')->willReturn($node);
        $link->method('getParent')->willReturn($parent);

        return $link;
    }

    /**
     * @return array{path: string, folderNodeId: ?int}
     */
    private function resolve(ResourceLink $link, ResourceNode $root): array
    {
        $controller = (new ReflectionClass(AdminController::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(AdminController::class, 'getFileLocationInContext');

        return $method->invoke($controller, $link, $root);
    }
}

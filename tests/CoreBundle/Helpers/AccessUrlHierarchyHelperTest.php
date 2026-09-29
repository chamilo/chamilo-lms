<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Helpers\AccessUrlHierarchyHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * A parent must be immediately followed by its own children, recursively, with siblings
 * ordered alphabetically by URL -- this is what lets the Multi URLs admin pages show the
 * hierarchy via indentation alone, with no separate "Parent" column. See
 * AccessUrlHierarchyHelper.
 */
class AccessUrlHierarchyHelperTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private function createChildUrl(AccessUrl $parent, string $url): AccessUrl
    {
        /** @var AccessUrlRepository $urlRepo */
        $urlRepo = static::getContainer()->get(AccessUrlRepository::class);
        $admin = $this->getAdmin();

        $child = (new AccessUrl())
            ->setUrl($url)
            ->setActive(1)
            ->setCreator($admin)
            ->setSuperior($parent)
        ;
        $urlRepo->create($child);

        return $child;
    }

    public function testOrdersParentBeforeChildrenAlphabeticallyAmongSiblings(): void
    {
        self::bootKernel();
        $root = $this->getAccessUrl();

        // Created in "wrong" order (B before A) to prove sibling order comes from the URL
        // string, not from creation/insertion order.
        $childB = $this->createChildUrl($root, 'https://hierarchy-b.example.org/');
        $childA = $this->createChildUrl($root, 'https://hierarchy-a.example.org/');
        $grandchildOfA = $this->createChildUrl($childA, 'https://hierarchy-a-child.example.org/');

        /** @var AccessUrlHierarchyHelper $helper */
        $helper = static::getContainer()->get(AccessUrlHierarchyHelper::class);

        // Pass in yet another order to prove the helper -- not the caller -- decides the order.
        $ordered = $helper->order([$grandchildOfA, $childB, $root, $childA]);

        $this->assertSame(
            [$root->getId(), $childA->getId(), $grandchildOfA->getId(), $childB->getId()],
            array_map(static fn (array $entry): int => $entry['url']->getId(), $ordered)
        );
        $this->assertSame(
            [0, 1, 2, 1],
            array_map(static fn (array $entry): int => $entry['depth'], $ordered)
        );
    }

    public function testAnEntryWhoseParentIsNotInTheGivenListBecomesARootForThisListing(): void
    {
        self::bootKernel();
        $root = $this->getAccessUrl();
        $child = $this->createChildUrl($root, 'https://hierarchy-scoped-child.example.org/');
        $grandchild = $this->createChildUrl($child, 'https://hierarchy-scoped-grandchild.example.org/');

        /** @var AccessUrlHierarchyHelper $helper */
        $helper = static::getContainer()->get(AccessUrlHierarchyHelper::class);

        // $root is deliberately omitted -- as it would be for a subtree-scoped admin whose
        // managed set doesn't include their subtree's real parent.
        $ordered = $helper->order([$grandchild, $child]);

        $this->assertSame([$child->getId(), $grandchild->getId()], array_map(static fn (array $entry): int => $entry['url']->getId(), $ordered));
        $this->assertSame([0, 1], array_map(static fn (array $entry): int => $entry['depth'], $ordered));
    }
}

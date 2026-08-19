<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\AccessUrl;

/**
 * Orders a flat list of AccessUrl entities into a depth-first hierarchy (a parent immediately
 * followed by its own children, recursively, siblings alphabetical by URL) so the Multi URLs
 * admin pages can display the tree via indentation alone, with no separate "Parent" column.
 *
 * Only relates entries to each other within the given list itself, so a scoped caller passing in
 * a filtered subtree still gets a correct, self-contained order: an entry whose real parent was
 * filtered out is treated as a root for this listing.
 */
final class AccessUrlHierarchyHelper
{
    /**
     * @param AccessUrl[] $urls
     *
     * @return array<int, array{url: AccessUrl, depth: int}>
     */
    public function order(array $urls): array
    {
        $knownIds = [];
        foreach ($urls as $url) {
            $knownIds[(int) $url->getId()] = true;
        }

        $childrenByParentId = [];
        foreach ($urls as $url) {
            $parentId = $url->getSuperior()?->getId();
            $parentId = (null !== $parentId && isset($knownIds[$parentId])) ? $parentId : 0;
            $childrenByParentId[$parentId][] = $url;
        }

        foreach ($childrenByParentId as &$children) {
            usort($children, static fn (AccessUrl $a, AccessUrl $b): int => strcasecmp($a->getUrl(), $b->getUrl()));
        }
        unset($children);

        $ordered = [];
        $this->appendChildren($childrenByParentId, 0, 0, $ordered, []);

        return $ordered;
    }

    /**
     * @param array<int, AccessUrl[]>                       $childrenByParentId
     * @param array<int, array{url: AccessUrl, depth: int}> $ordered
     * @param int[]                                         $visited            guards against a cycle
     */
    private function appendChildren(array $childrenByParentId, int $parentId, int $depth, array &$ordered, array $visited): void
    {
        foreach ($childrenByParentId[$parentId] ?? [] as $url) {
            $id = (int) $url->getId();
            if (\in_array($id, $visited, true)) {
                continue;
            }

            $ordered[] = ['url' => $url, 'depth' => $depth];
            $this->appendChildren($childrenByParentId, $id, $depth + 1, $ordered, [...$visited, $id]);
        }
    }
}

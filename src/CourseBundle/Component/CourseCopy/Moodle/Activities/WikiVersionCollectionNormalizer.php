<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

use stdClass;

final class WikiVersionCollectionNormalizer
{
    /**
     * @param array<int|string, mixed> $bag
     *
     * @return array<int, array{
     *     id:int,
     *     reflink:string,
     *     title:string,
     *     versions:array<int, array{
     *         id:int,
     *         content:string,
     *         version:int,
     *         userid:int,
     *         timestamp:string|int
     *     }>
     * }>
     */
    public function normalize(array $bag): array
    {
        $grouped = [];

        foreach ($bag as $key => $wrapper) {
            if (!\is_object($wrapper)) {
                continue;
            }

            $page = isset($wrapper->obj) && \is_object($wrapper->obj)
                ? $wrapper->obj
                : $wrapper;
            if (!$page instanceof stdClass && !\is_object($page)) {
                continue;
            }

            $pageId = (int) ($page->page_id ?? $page->pageId ?? $page->iid ?? $key ?? 0);
            if ($pageId <= 0) {
                continue;
            }

            $versionNumber = max(1, (int) ($page->version ?? 1));
            $versionId = (int) ($page->iid ?? $page->id ?? $wrapper->source_id ?? $key ?? 0);
            if ($versionId <= 0) {
                $versionId = ($pageId * 1000) + $versionNumber;
            }

            $reflink = trim((string) ($page->reflink ?? ''));
            $title = trim((string) ($page->title ?? $page->name ?? ''));
            if ('' === $title) {
                $title = 'Wiki page '.$pageId;
            }

            if (!isset($grouped[$pageId])) {
                $grouped[$pageId] = [
                    'id' => $pageId,
                    'reflink' => $reflink,
                    'title' => $title,
                    'versions' => [],
                ];
            }

            $grouped[$pageId]['versions'][] = [
                'id' => $versionId,
                'content' => (string) ($page->content ?? ''),
                'version' => $versionNumber,
                'userid' => (int) ($page->user_id ?? $page->userId ?? 0),
                'timestamp' => $page->dtime ?? $page->timestamp ?? '',
            ];
        }

        foreach ($grouped as &$page) {
            usort(
                $page['versions'],
                static function (array $left, array $right): int {
                    $byVersion = $left['version'] <=> $right['version'];
                    if (0 !== $byVersion) {
                        return $byVersion;
                    }

                    return $left['id'] <=> $right['id'];
                },
            );
        }
        unset($page);

        ksort($grouped, SORT_NUMERIC);

        return array_values($grouped);
    }
}

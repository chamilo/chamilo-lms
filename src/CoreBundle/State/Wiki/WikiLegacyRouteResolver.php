<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

final class WikiLegacyRouteResolver
{
    /**
     * @param array<string, mixed> $legacyParameters
     */
    public function resolve(
        int $nodeId,
        int $courseId,
        int $sessionId,
        int $groupId,
        string $action,
        string $reflink,
        ?int $pageId,
        ?int $versionIid,
        array $legacyParameters = [],
    ): string {
        $basePath = '/resources/wiki/'.$nodeId.'/';
        $action = strtolower(trim($action));
        $reflink = '' === trim($reflink) ? 'index' : $reflink;
        $commonQuery = $this->buildCommonQuery($courseId, $sessionId, $groupId, $legacyParameters);

        if ($pageId > 0 && $versionIid > 0 && \in_array($action, ['', 'show', 'showpage'], true)) {
            return $this->buildHistoryUrl($basePath, $pageId, $versionIid, $commonQuery, $legacyParameters);
        }

        return match ($action) {
            'addnew' => $this->withQuery($basePath.'new', $commonQuery),
            'edit' => $pageId > 0
                ? $this->withQuery($basePath.'edit/'.$pageId, $commonQuery)
                : $this->buildPageUrl($basePath, $reflink, $commonQuery),
            'history', 'restorepage' => $pageId > 0
                ? $this->buildHistoryUrl($basePath, $pageId, $versionIid, $commonQuery, $legacyParameters)
                : $this->buildPageUrl($basePath, $reflink, $commonQuery),
            'discuss' => $pageId > 0
                ? $this->withQuery($basePath.'discussion/'.$pageId, $commonQuery)
                : $this->buildPageUrl($basePath, $reflink, $commonQuery),
            'allpages', 'deletewiki' => $this->buildReportUrl($basePath, 'all', $commonQuery),
            'recentchanges' => $this->buildReportUrl($basePath, 'recent', $commonQuery),
            'searchpages' => $this->buildSearchUrl($basePath, $commonQuery, $legacyParameters),
            'links' => $this->buildReportUrl($basePath, 'backlinks', $commonQuery, ['target' => $reflink]),
            'statistics', 'more' => $this->buildReportUrl($basePath, 'statistics', $commonQuery),
            'mactiveusers' => $this->buildReportUrl($basePath, 'active-users', $commonQuery),
            'usercontrib' => $this->buildReportUrl(
                $basePath,
                'user-contributions',
                $commonQuery,
                ['userId' => $this->positiveInt($legacyParameters['user_id'] ?? null)],
            ),
            'mostchanged' => $this->buildReportUrl($basePath, 'most-changed', $commonQuery),
            'mvisited' => $this->buildReportUrl($basePath, 'most-visited', $commonQuery),
            'wanted' => $this->buildReportUrl($basePath, 'wanted', $commonQuery),
            'orphaned' => $this->buildReportUrl($basePath, 'orphaned', $commonQuery),
            'mostlinked' => $this->buildReportUrl($basePath, 'most-linked', $commonQuery),
            'category', 'delete_category' => $this->withQuery($basePath.'categories', $commonQuery),
            'settings' => $this->withQuery($basePath.'settings', $commonQuery),
            'export_to_pdf' => $pageId > 0
                ? $this->withQuery(
                    '/api/wiki/page/'.$pageId.'/export.pdf',
                    ['node' => $nodeId] + $commonQuery,
                )
                : $this->buildPageUrl($basePath, $reflink, $commonQuery),
            // Legacy write actions are intentionally not replayed from GET/POST bookmarks.
            'delete', 'export2doc', 'export_to_doc_file' => $this->buildPageUrl(
                $basePath,
                $reflink,
                $commonQuery,
            ),
            '', 'show', 'showpage' => $this->buildPageUrl($basePath, $reflink, $commonQuery),
            default => $this->buildPageUrl($basePath, $reflink, $commonQuery),
        };
    }

    /**
     * @param array<string, mixed> $legacyParameters
     *
     * @return array<string, int|string>
     */
    private function buildCommonQuery(
        int $courseId,
        int $sessionId,
        int $groupId,
        array $legacyParameters,
    ): array {
        $query = ['cid' => $courseId];

        if ($sessionId > 0) {
            $query['sid'] = $sessionId;
        }

        if ($groupId > 0) {
            $query['gid'] = $groupId;
        }

        $studentView = $legacyParameters['isStudentView'] ?? null;
        if (\is_scalar($studentView)) {
            $normalizedStudentView = strtolower(trim((string) $studentView));
            if (\in_array($normalizedStudentView, ['1', 'true'], true)) {
                $query['isStudentView'] = 'true';
            } elseif (\in_array($normalizedStudentView, ['0', 'false'], true)) {
                $query['isStudentView'] = 'false';
            }
        }

        return $query;
    }

    /**
     * @param array<string, int|string> $commonQuery
     */
    private function buildPageUrl(string $basePath, string $reflink, array $commonQuery): string
    {
        return $this->withQuery($basePath, ['title' => $reflink] + $commonQuery);
    }

    /**
     * @param array<string, int|string> $commonQuery
     * @param array<string, mixed>      $legacyParameters
     */
    private function buildHistoryUrl(
        string $basePath,
        int $pageId,
        ?int $versionIid,
        array $commonQuery,
        array $legacyParameters,
    ): string {
        $query = $commonQuery;
        $oldIid = $this->positiveInt(
            $legacyParameters['oldIid']
                ?? $legacyParameters['oldid']
                ?? $legacyParameters['old']
                ?? null,
        );
        $newIid = $this->positiveInt(
            $legacyParameters['newIid']
                ?? $legacyParameters['newid']
                ?? $legacyParameters['new']
                ?? null,
        );

        if ($oldIid > 0) {
            $query['oldIid'] = $oldIid;
        }

        if ($newIid > 0) {
            $query['newIid'] = $newIid;
        }

        if ($oldIid > 0 && $newIid > 0) {
            $requestedMode = strtolower(trim((string) ($legacyParameters['mode'] ?? '')));
            $query['mode'] = isset($legacyParameters['HistoryDifferences2']) || 'word' === $requestedMode
                ? 'word'
                : 'line';
        } elseif (null !== $versionIid && $versionIid > 0) {
            $query['versionIid'] = $versionIid;
        }

        return $this->withQuery($basePath.'history/'.$pageId, $query);
    }

    /**
     * @param array<string, int|string> $commonQuery
     * @param array<string, int|string> $extraQuery
     */
    private function buildReportUrl(
        string $basePath,
        string $report,
        array $commonQuery,
        array $extraQuery = [],
    ): string {
        $query = ['report' => $report] + array_filter(
            $extraQuery,
            static fn (int|string $value): bool => '' !== (string) $value && 0 !== $value,
        ) + $commonQuery;

        return $this->withQuery($basePath.'reports', $query);
    }

    /**
     * @param array<string, int|string> $commonQuery
     * @param array<string, mixed>      $legacyParameters
     */
    private function buildSearchUrl(
        string $basePath,
        array $commonQuery,
        array $legacyParameters,
    ): string {
        $query = ['report' => 'search'];
        $search = $legacyParameters['search_term'] ?? $legacyParameters['search'] ?? null;
        if (\is_scalar($search) && '' !== trim((string) $search)) {
            $query['search'] = trim((string) $search);
        }

        if ($this->isTruthy($legacyParameters['search_content'] ?? null)) {
            $query['searchContent'] = 1;
        }

        if ($this->isTruthy($legacyParameters['all_vers'] ?? null)) {
            $query['allVersions'] = 1;
        }

        $categoryIds = $this->categoryIds($legacyParameters['categories'] ?? null);
        if ('' !== $categoryIds) {
            $query['categoryIds'] = $categoryIds;
        }

        if ($this->isTruthy($legacyParameters['match_all_categories'] ?? null)) {
            $query['matchAllCategories'] = 1;
        }

        return $this->withQuery($basePath.'reports', $query + $commonQuery);
    }

    /**
     * @param array<string, int|string> $query
     */
    private function withQuery(string $path, array $query): string
    {
        return $path.'?'.http_build_query($query);
    }

    private function positiveInt(mixed $value): int
    {
        return \is_scalar($value) ? max(0, (int) $value) : 0;
    }

    private function isTruthy(mixed $value): bool
    {
        if (!\is_scalar($value)) {
            return false;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function categoryIds(mixed $value): string
    {
        $values = \is_array($value) ? $value : explode(',', \is_scalar($value) ? (string) $value : '');
        $ids = [];

        foreach ($values as $item) {
            if (!\is_scalar($item)) {
                continue;
            }

            $id = (int) $item;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return implode(',', array_values($ids));
    }
}

<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\State\Wiki\WikiLegacyRouteResolver;
use Chamilo\CoreBundle\State\Wiki\WikiPageRenderer;
use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Repository\CWikiRepository;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;
$current_course_tool = TOOL_WIKI;

api_protect_course_script(true);
api_block_anonymous_users();
api_protect_course_group(GroupManager::GROUP_TOOL_WIKI);

$course = api_get_course_entity();
$courseNodeId = $course?->getResourceNode()?->getId();
if (null === $course || null === $courseNodeId) {
    api_not_allowed(true);
}

$courseId = (int) $course->getId();
$sessionId = (int) api_get_session_id();
$groupId = (int) api_get_group_id();
$renderer = new WikiPageRenderer();
$resolver = new WikiLegacyRouteResolver();

$scalarString = static fn (mixed $value, string $default = ''): string => \is_scalar($value)
    ? trim((string) $value)
    : $default;
$positiveInt = static fn (mixed $value): int => \is_scalar($value) ? max(0, (int) $value) : 0;

$legacyParameters = array_merge($_GET, $_POST);
$action = strtolower($scalarString($legacyParameters['action'] ?? null, 'showpage'));
$reflink = $renderer->normalizeReflink($scalarString($legacyParameters['title'] ?? null));
$pageId = $positiveInt($legacyParameters['page_id'] ?? null);
$versionIid = $positiveInt($legacyParameters['view'] ?? $legacyParameters['wiki_id'] ?? null);

/** @var CWikiRepository $wikiRepository */
$wikiRepository = Container::getEntityManager()->getRepository(CWiki::class);

$isVersionInContext = static function (
    CWiki $wiki,
    int $expectedCourseId,
    int $expectedSessionId,
    int $expectedGroupId,
): bool {
    if ($wiki->getCId() !== $expectedCourseId) {
        return false;
    }

    if ((int) ($wiki->getGroupId() ?? 0) !== $expectedGroupId) {
        return false;
    }

    $sourceSessionId = (int) ($wiki->getSessionId() ?? 0);

    return $sourceSessionId === $expectedSessionId
        || ($expectedSessionId > 0 && 0 === $sourceSessionId);
};

$resolvedVersion = null;
if ($versionIid > 0) {
    $candidate = $wikiRepository->find($versionIid);
    if ($candidate instanceof CWiki && $isVersionInContext($candidate, $courseId, $sessionId, $groupId)) {
        $resolvedVersion = $candidate;
    }
}

if (!$resolvedVersion instanceof CWiki && $pageId > 0) {
    $resolvedVersion = $wikiRepository->findLatestVersionInContext($courseId, $pageId, $groupId, $sessionId);
    if (!$resolvedVersion instanceof CWiki && $sessionId > 0) {
        $resolvedVersion = $wikiRepository->findLatestVersionInContext($courseId, $pageId, $groupId, 0);
    }
}

if (!$resolvedVersion instanceof CWiki) {
    $resolvedVersion = $wikiRepository->findFirstVersionInContext($courseId, $reflink, $groupId, $sessionId);
    if (!$resolvedVersion instanceof CWiki && $sessionId > 0) {
        $resolvedVersion = $wikiRepository->findFirstVersionInContext($courseId, $reflink, $groupId, 0);
    }
}

if ($resolvedVersion instanceof CWiki) {
    $pageId = (int) ($resolvedVersion->getPageId() ?? 0);
    $reflink = $resolvedVersion->getReflink();
    $versionIid = $versionIid > 0 ? $versionIid : null;
} else {
    $pageId = 0;
    $versionIid = null;
}

$targetPath = $resolver->resolve(
    (int) $courseNodeId,
    $courseId,
    $sessionId,
    $groupId,
    $action,
    $reflink,
    $pageId > 0 ? $pageId : null,
    $versionIid,
    $legacyParameters,
);

api_location(rtrim(api_get_path(WEB_PATH), '/').$targetPath);

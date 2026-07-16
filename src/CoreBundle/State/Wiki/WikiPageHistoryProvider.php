<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Wiki\WikiPageHistory;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Repository\CWikiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use const DATE_ATOM;

/**
 * @implements ProviderInterface<WikiPageHistory>
 */
final readonly class WikiPageHistoryProvider implements ProviderInterface
{
    use WikiAccessHelperTrait;

    public const CSRF_TOKEN_ID = 'wiki_page_restore';

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CWikiRepository $wikiRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private WikiPageRenderer $renderer,
        private WikiPageDiffService $diffService,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): WikiPageHistory
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getWikiCourse($this->entityManager, $request);
        $this->assertWikiToolEnabled($this->entityManager, $course);
        $nodeId = $this->assertWikiRouteNode($course, $request);
        $session = $this->getWikiSession($this->entityManager, $request);
        $this->assertWikiSessionBelongsToCourse($session, $course);
        $group = $this->getWikiGroup($this->entityManager, $request);
        $this->assertWikiGroupBelongsToContext($group, $course, $session);

        if (!$this->canReadWikiContext($this->security, $this->settingsManager, $course, $session, $group)) {
            throw new AccessDeniedHttpException('You are not allowed to view Wiki history in this context.');
        }

        $pageId = isset($uriVariables['pageId']) ? (int) $uriVariables['pageId'] : 0;
        if ($pageId <= 0) {
            throw new BadRequestHttpException('A valid Wiki page id is required.');
        }

        $courseId = (int) $course->getId();
        $sessionId = null !== $session ? (int) $session->getId() : 0;
        $groupId = null !== $group?->getIid() ? (int) $group->getIid() : 0;
        $sourceSessionId = $sessionId;
        $latest = $this->wikiRepository->findLatestVersionInContext(
            $courseId,
            $pageId,
            $groupId,
            $sourceSessionId,
        );

        if (!$latest instanceof CWiki && $sessionId > 0) {
            $sourceSessionId = 0;
            $latest = $this->wikiRepository->findLatestVersionInContext(
                $courseId,
                $pageId,
                $groupId,
                $sourceSessionId,
            );
        }

        if (!$latest instanceof CWiki) {
            throw new NotFoundHttpException('The requested Wiki page was not found in the current context.');
        }

        $studentView = $this->isWikiStudentView($request);
        $canManage = !$studentView && $this->canManageWikiContext(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            $group,
        );
        $this->assertWikiPageVisible($this->security, $latest, $canManage);

        $isInheritedFromCourse = $sessionId > 0 && 0 === $sourceSessionId;
        $canRestore = !$studentView
            && !$isInheritedFromCourse
            && $this->canEditWikiPage(
                $this->entityManager,
                $this->security,
                $this->settingsManager,
                $course,
                $session,
                $group,
                $latest,
            );
        $versions = $this->wikiRepository->findPageVersionsInContext(
            $courseId,
            $pageId,
            $groupId,
            $sourceSessionId,
        );
        $users = $this->getUsers($versions);
        $strictFiltering = $this->isWikiCourseSettingEnabled(
            $this->entityManager,
            $course,
            'wiki_html_strict_filtering',
            false,
        );

        $history = new WikiPageHistory();
        $history->pageId = $pageId;
        $history->courseId = $courseId;
        $history->sessionId = $sessionId > 0 ? $sessionId : null;
        $history->groupId = $groupId > 0 ? $groupId : null;
        $history->nodeId = $nodeId;
        $history->reflink = $latest->getReflink();
        $history->title = $this->renderer->displayTitle($latest->getReflink(), $latest->getTitle());
        $history->isInheritedFromCourse = $isInheritedFromCourse;
        $history->currentIid = null !== $latest->getIid() ? (int) $latest->getIid() : null;
        $history->currentVersion = (int) $latest->getVersion();
        $history->canRestore = $canRestore;
        $history->csrfToken = $canRestore
            ? (string) $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID)
            : '';
        $history->versions = array_map(
            fn (CWiki $version): array => $this->buildVersionSummary($version, $users, $latest),
            $versions,
        );

        $selectedIid = $request->query->getInt('versionIid');
        if ($selectedIid > 0) {
            $selected = $this->getVersion($courseId, $pageId, $selectedIid, $groupId, $sourceSessionId);
            $history->selectedVersion = $this->buildSelectedVersion(
                $selected,
                $users,
                $courseId,
                $sessionId,
                $groupId,
                $nodeId,
                $strictFiltering,
            );
        }

        $oldIid = $request->query->getInt('oldIid');
        $newIid = $request->query->getInt('newIid');
        if ($oldIid > 0 || $newIid > 0) {
            if ($oldIid <= 0 || $newIid <= 0 || $oldIid === $newIid) {
                throw new BadRequestHttpException('Two different Wiki versions are required for comparison.');
            }

            $mode = (string) $request->query->get('mode', 'line');
            if (!\in_array($mode, ['line', 'word'], true)) {
                throw new BadRequestHttpException('The Wiki comparison mode is invalid.');
            }

            $oldVersion = $this->getVersion($courseId, $pageId, $oldIid, $groupId, $sourceSessionId);
            $newVersion = $this->getVersion($courseId, $pageId, $newIid, $groupId, $sourceSessionId);

            if ((int) $oldVersion->getVersion() >= (int) $newVersion->getVersion()) {
                throw new BadRequestHttpException('The old Wiki version must precede the new version.');
            }

            $oldContent = $this->renderer->sanitizeContent($oldVersion->getContent(), $strictFiltering);
            $newContent = $this->renderer->sanitizeContent($newVersion->getContent(), $strictFiltering);
            $history->comparison = [
                'mode' => $mode,
                'oldVersion' => $this->buildVersionSummary($oldVersion, $users, $latest),
                'newVersion' => $this->buildVersionSummary($newVersion, $users, $latest),
                'lineChanges' => 'line' === $mode ? $this->diffService->compareLines($oldContent, $newContent) : [],
                'wordChanges' => 'word' === $mode ? $this->diffService->compareWords($oldContent, $newContent) : [],
            ];
        }

        return $history;
    }

    /**
     * @param array<int, CWiki> $versions
     *
     * @return array<int, User>
     */
    private function getUsers(array $versions): array
    {
        $userIds = [];

        foreach ($versions as $version) {
            if ($version->getUserId() > 0) {
                $userIds[$version->getUserId()] = $version->getUserId();
            }
        }

        if ([] === $userIds) {
            return [];
        }

        $users = $this->entityManager->getRepository(User::class)->findBy(['id' => array_values($userIds)]);
        $indexed = [];

        foreach ($users as $user) {
            if ($user instanceof User && null !== $user->getId()) {
                $indexed[(int) $user->getId()] = $user;
            }
        }

        return $indexed;
    }

    /**
     * @param array<int, User> $users
     *
     * @return array<string, int|string|bool|null>
     */
    private function buildVersionSummary(CWiki $version, array $users, CWiki $latest): array
    {
        $author = $users[$version->getUserId()] ?? null;
        $comment = trim($version->getComment());

        return [
            'iid' => null !== $version->getIid() ? (int) $version->getIid() : null,
            'version' => (int) $version->getVersion(),
            'updatedAt' => $version->getDtime()?->format(DATE_ATOM),
            'authorId' => $version->getUserId() > 0 ? $version->getUserId() : null,
            'authorName' => $author instanceof User ? $author->getFullName() : 'Anonymous',
            'comment' => '' !== $comment ? $comment : '---',
            'progress' => $this->renderer->normalizeStoredProgress($version->getProgress()),
            'isCurrent' => $version->getIid() === $latest->getIid(),
        ];
    }

    /**
     * @param array<int, User> $users
     *
     * @return array<string, int|string|bool|null>
     */
    private function buildSelectedVersion(
        CWiki $version,
        array $users,
        int $courseId,
        int $sessionId,
        int $groupId,
        int $nodeId,
        bool $strictFiltering,
    ): array {
        $author = $users[$version->getUserId()] ?? null;
        $content = $this->renderer->sanitizeContent($version->getContent(), $strictFiltering);
        $linkedReflinks = $this->renderer->extractInternalReflinks($content);
        $existingReflinks = $this->wikiRepository->findExistingReflinks(
            $courseId,
            $linkedReflinks,
            $groupId,
            $sessionId,
        );

        return [
            'iid' => null !== $version->getIid() ? (int) $version->getIid() : null,
            'version' => (int) $version->getVersion(),
            'title' => $this->renderer->displayTitle($version->getReflink(), $version->getTitle()),
            'content' => $this->renderer->renderInternalLinks(
                $content,
                $existingReflinks,
                $nodeId,
                [
                    'cid' => $courseId,
                    'sid' => $sessionId,
                    'gid' => $groupId,
                ],
            ),
            'updatedAt' => $version->getDtime()?->format(DATE_ATOM),
            'authorId' => $version->getUserId() > 0 ? $version->getUserId() : null,
            'authorName' => $author instanceof User ? $author->getFullName() : 'Anonymous',
            'comment' => '' !== trim($version->getComment()) ? trim($version->getComment()) : '---',
            'progress' => $this->renderer->normalizeStoredProgress($version->getProgress()),
            'wordCount' => $this->renderer->wordCount($content),
        ];
    }

    private function getVersion(
        int $courseId,
        int $pageId,
        int $versionIid,
        int $groupId,
        int $sessionId,
    ): CWiki {
        $version = $this->wikiRepository->findPageVersionByIidInContext(
            $courseId,
            $pageId,
            $versionIid,
            $groupId,
            $sessionId,
        );

        if (!$version instanceof CWiki) {
            throw new NotFoundHttpException('The requested Wiki version was not found in the current context.');
        }

        return $version;
    }
}

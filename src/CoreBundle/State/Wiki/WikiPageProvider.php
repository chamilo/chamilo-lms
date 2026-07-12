<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Wiki\WikiPage;
use Chamilo\CoreBundle\ApiResource\Wiki\WikiPageAction;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Entity\CWikiConf;
use Chamilo\CourseBundle\Entity\CWikiMailcue;
use Chamilo\CourseBundle\Repository\CWikiRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Event;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

use const DATE_ATOM;

/**
 * @implements ProviderInterface<WikiPage>
 */
final readonly class WikiPageProvider implements ProviderInterface
{
    use WikiAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CWikiRepository $wikiRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private WikiPageRenderer $renderer,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): WikiPage
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
            throw new AccessDeniedHttpException('You are not allowed to view Wiki pages in this context.');
        }

        $this->registerToolAccess();

        $studentView = $this->isWikiStudentView($request);
        $canManage = !$studentView && $this->canManageWikiContext(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            $group,
        );
        $reflink = $this->renderer->normalizeReflink($request->query->get('title'));
        $courseId = (int) $course->getId();
        $sessionId = null !== $session ? (int) $session->getId() : 0;
        $groupId = null !== $group?->getIid() ? (int) $group->getIid() : 0;
        $sourceSessionId = $sessionId;

        $first = $this->wikiRepository->findFirstVersionInContext(
            $courseId,
            $reflink,
            $groupId,
            $sourceSessionId,
        );

        if (!$first instanceof CWiki && $sessionId > 0) {
            $sourceSessionId = 0;
            $first = $this->wikiRepository->findFirstVersionInContext(
                $courseId,
                $reflink,
                $groupId,
                $sourceSessionId,
            );
        }

        $addLock = $this->wikiRepository->findContextAddLock($courseId, $groupId, $sessionId);
        $contextHasPages = $canManage && [] !== $this->wikiRepository->findVersionsInContext(
            $courseId,
            $groupId,
            $sessionId,
            true,
        );
        $canCreateAnyPage = !$studentView && $this->canCreateWikiPage(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            $group,
            'new_page',
            $addLock,
        );

        $page = new WikiPage();
        $page->courseId = $courseId;
        $page->sessionId = $sessionId > 0 ? $sessionId : null;
        $page->groupId = $groupId > 0 ? $groupId : null;
        $page->nodeId = $nodeId;
        $page->reflink = $reflink;
        $page->sourceSessionId = $sourceSessionId > 0 ? $sourceSessionId : null;
        $page->isInheritedFromCourse = $sessionId > 0 && 0 === $sourceSessionId;
        $page->canManage = $canManage;
        $page->canCreate = $canCreateAnyPage;
        $page->addLocked = 0 === $addLock;
        $page->canChangeAddLock = $canManage && $contextHasPages;
        $page->managementCsrfToken = $canManage
            ? (string) $this->csrfTokenManager->getToken(WikiPageAction::CSRF_TOKEN_ID)
            : '';
        $page->studentView = $studentView;
        $page->settings = [
            'categoriesEnabled' => $this->isWikiCourseSettingEnabled(
                $this->entityManager,
                $course,
                'wiki_categories_enabled',
                false,
            ),
            'strictHtmlFiltering' => $this->isWikiCourseSettingEnabled(
                $this->entityManager,
                $course,
                'wiki_html_strict_filtering',
                false,
            ),
        ];
        $page->legacyUrl = $this->buildLegacyUrl($courseId, $sessionId, $groupId, $reflink);

        if (!$first instanceof CWiki || null === $first->getPageId()) {
            $page->title = $this->renderer->displayTitle($reflink);
            $page->canEdit = !$studentView && $this->canCreateWikiPage(
                $this->entityManager,
                $this->security,
                $this->settingsManager,
                $course,
                $session,
                $group,
                $reflink,
                $addLock,
            );

            return $page;
        }

        $latest = $this->wikiRepository->findLatestVersionInContext(
            $courseId,
            (int) $first->getPageId(),
            $groupId,
            $sourceSessionId,
        );

        if (!$latest instanceof CWiki) {
            $page->title = $this->renderer->displayTitle($reflink);

            return $page;
        }

        $this->assertWikiPageVisible($this->security, $latest, $canManage);

        $isExactContextPage = $sourceSessionId === $sessionId;
        $page->canChangeVisibility = $canManage && $isExactContextPage;
        $page->canChangeProtection = $canManage && $isExactContextPage;
        $page->canDelete = $canManage && $isExactContextPage;
        $page->canSubscribe = $canManage;
        $currentUser = $this->security->getUser();
        $subscription = null;
        if ($currentUser instanceof User) {
            $subscription = $this->entityManager->getRepository(CWikiMailcue::class)->createQueryBuilder('m')
                ->andWhere('m.cId = :courseId')
                ->andWhere('COALESCE(m.groupId, 0) = :groupId')
                ->andWhere('COALESCE(m.sessionId, 0) = :sessionId')
                ->andWhere('m.userId = :userId')
                ->andWhere('m.type = :type')
                ->setParameter('courseId', $courseId, Types::INTEGER)
                ->setParameter('groupId', $groupId, Types::INTEGER)
                ->setParameter('sessionId', $sessionId, Types::INTEGER)
                ->setParameter('userId', (int) $currentUser->getId(), Types::INTEGER)
                ->setParameter('type', 'watch:'.$latest->getReflink(), Types::STRING)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
            ;
        }
        $page->subscribed = $subscription instanceof CWikiMailcue;
        $page->canEdit = !$studentView && (
            $isExactContextPage
                ? $this->canEditWikiPage(
                    $this->entityManager,
                    $this->security,
                    $this->settingsManager,
                    $course,
                    $session,
                    $group,
                    $latest,
                )
                : $this->canCreateWikiPage(
                    $this->entityManager,
                    $this->security,
                    $this->settingsManager,
                    $course,
                    $session,
                    $group,
                    $reflink,
                    $addLock,
                )
        );

        $strictFiltering = true === ($page->settings['strictHtmlFiltering'] ?? false);
        $sanitizedContent = $this->renderer->sanitizeContent($latest->getContent(), $strictFiltering);
        $linkedReflinks = $this->renderer->extractInternalReflinks($sanitizedContent);
        $existingReflinks = $this->wikiRepository->findExistingReflinks(
            $courseId,
            $linkedReflinks,
            $groupId,
            $sessionId,
        );
        $renderedContent = $this->renderer->renderInternalLinks(
            $sanitizedContent,
            $existingReflinks,
            $nodeId,
            [
                'cid' => $courseId,
                'sid' => $sessionId,
                'gid' => $groupId,
            ],
        );
        $author = $this->entityManager->getRepository(User::class)->find($latest->getUserId());
        $configuration = $this->entityManager->getRepository(CWikiConf::class)->findOneBy([
            'cId' => $courseId,
            'pageId' => (int) $latest->getPageId(),
        ]);

        $page->exists = true;
        $page->iid = null !== $latest->getIid() ? (int) $latest->getIid() : null;
        $page->pageId = (int) $latest->getPageId();
        $page->version = null !== $latest->getVersion() ? (int) $latest->getVersion() : null;
        $page->title = $this->renderer->displayTitle($reflink, $latest->getTitle());
        $page->content = $renderedContent;
        $page->updatedAt = $latest->getDtime()?->format(DATE_ATOM);
        $page->authorId = $author instanceof User ? (int) $author->getId() : $latest->getUserId();
        $page->authorName = $author instanceof User ? $author->getFullName() : '';
        $page->assignment = $latest->getAssignment();
        $page->hasTask = $configuration instanceof CWikiConf && '' !== trim((string) $configuration->getTask());
        $page->progress = $this->renderer->normalizeStoredProgress($latest->getProgress());
        $page->score = $latest->getScore();
        $page->wordCount = $this->renderer->wordCount($sanitizedContent);
        $page->hits = (int) $latest->getHits();
        $page->visible = 1 === $latest->getVisibility();
        $page->editLocked = 1 === $latest->getEditlock();

        $this->registerPageView($latest);

        return $page;
    }

    private function buildLegacyUrl(int $courseId, int $sessionId, int $groupId, string $reflink): string
    {
        $query = [
            'cid' => $courseId,
            'action' => 'showpage',
            'title' => $reflink,
        ];

        if ($sessionId > 0) {
            $query['sid'] = $sessionId;
        }

        if ($groupId > 0) {
            $query['gid'] = $groupId;
        }

        return '/main/wiki/index.php?'.http_build_query($query);
    }

    private function registerToolAccess(): void
    {
        if (!class_exists(Event::class) || !\defined('TOOL_WIKI')) {
            return;
        }

        try {
            Event::event_access_tool((string) \constant('TOOL_WIKI'));
        } catch (Throwable) {
            // Tracking must never break Wiki page rendering.
        }
    }

    private function registerPageView(CWiki $wiki): void
    {
        $pageId = $wiki->getPageId();
        if (null === $pageId) {
            return;
        }

        if (class_exists(Event::class)
            && \defined('LOG_WIKI_ACCESS')
            && \defined('LOG_WIKI_PAGE_ID')
        ) {
            try {
                Event::addEvent(
                    (string) \constant('LOG_WIKI_ACCESS'),
                    (string) \constant('LOG_WIKI_PAGE_ID'),
                    (int) $pageId,
                );
            } catch (Throwable) {
                // Tracking must never break Wiki page rendering.
            }
        }

        $wiki->setHits(((int) $wiki->getHits()) + 1);
        $this->entityManager->flush();
    }
}

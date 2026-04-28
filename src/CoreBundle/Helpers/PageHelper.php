<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Page;
use Chamilo\CoreBundle\Entity\PageCategory;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\PageCategoryRepository;
use Chamilo\CoreBundle\Repository\PageRepository;
use Chamilo\CoreBundle\Repository\SysAnnouncementRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use const PHP_URL_PATH;

class PageHelper
{
    public const CUSTOM_PAGE_INDEX_LOGGED = 'index-logged';
    public const CUSTOM_PAGE_INDEX_UNLOGGED = 'index-unlogged';
    public const CUSTOM_PAGE_LOGGED_OUT = 'loggedout';
    public const CUSTOM_PAGE_REGISTRATION = 'registration';
    public const CUSTOM_PAGE_REGISTRATION_FEEDBACK = 'registration-feedback';
    public const CUSTOM_PAGE_LOST_PASSWORD = 'lostpassword';

    private const CUSTOM_PAGE_DIRECTORY = 'custompages';
    private const CUSTOM_PAGE_EXTENSION = 'php';

    private const CUSTOM_PAGES = [
        self::CUSTOM_PAGE_INDEX_LOGGED,
        self::CUSTOM_PAGE_INDEX_UNLOGGED,
        self::CUSTOM_PAGE_LOGGED_OUT,
        self::CUSTOM_PAGE_REGISTRATION,
        self::CUSTOM_PAGE_REGISTRATION_FEEDBACK,
        self::CUSTOM_PAGE_LOST_PASSWORD,
    ];
    protected PageRepository $pageRepository;
    protected PageCategoryRepository $pageCategoryRepository;

    /**
     * Repository used to read system announcements (platform news).
     */
    protected SysAnnouncementRepository $sysAnnouncementRepository;

    /**
     * Helper used to retrieve the current AccessUrl.
     */
    protected AccessUrlHelper $accessUrlHelper;

    public function __construct(
        PageRepository $pageRepository,
        PageCategoryRepository $pageCategoryRepository,
        SysAnnouncementRepository $sysAnnouncementRepository,
        AccessUrlHelper $accessUrlHelper,
        private TranslatorInterface $translator,
        private SettingsManager $settingsManager,
        private ThemeHelper $themeHelper,
        #[Autowire(param: 'kernel.project_dir')]
        private string $projectDir,
    ) {
        $this->pageRepository = $pageRepository;
        $this->pageCategoryRepository = $pageCategoryRepository;
        $this->sysAnnouncementRepository = $sysAnnouncementRepository;
        $this->accessUrlHelper = $accessUrlHelper;
    }

    public function createDefaultPages(User $user, AccessUrl $url, string $locale): bool
    {
        $categories = $this->pageCategoryRepository->findAll();

        if (!empty($categories)) {
            return false;
        }

        $category = (new PageCategory())
            ->setTitle('home')
            ->setType('grid')
            ->setCreator($user)
        ;
        $this->pageCategoryRepository->update($category);

        $indexCategory = (new PageCategory())
            ->setTitle('index')
            ->setType('grid')
            ->setCreator($user)
        ;
        $this->pageCategoryRepository->update($indexCategory);

        $faqCategory = (new PageCategory())
            ->setTitle('faq')
            ->setType('grid')
            ->setCreator($user)
        ;
        $this->pageCategoryRepository->update($faqCategory);

        $demoCategory = (new PageCategory())
            ->setTitle('demo')
            ->setType('grid')
            ->setCreator($user)
        ;
        $this->pageCategoryRepository->update($demoCategory);

        $page = (new Page())
            ->setTitle($this->translator->trans('Welcome'))
            ->setContent($this->translator->trans('Welcome to Chamilo'))
            ->setCategory($category)
            ->setCreator($user)
            ->setLocale($locale)
            ->setEnabled(true)
            ->setUrl($url)
        ;
        $this->pageRepository->update($page);

        $indexPage = (new Page())
            ->setTitle($this->translator->trans('Welcome'))
            ->setContent('<img src="/img/document/images/mr_chamilo/svg/teaching.svg" />')
            ->setCategory($indexCategory)
            ->setCreator($user)
            ->setLocale($locale)
            ->setEnabled(false)
            ->setUrl($url)
        ;
        $this->pageRepository->update($indexPage);

        $footerPublicCategory = (new PageCategory())
            ->setTitle('footer_public')
            ->setType('grid')
            ->setCreator($user)
        ;
        $this->pageCategoryRepository->update($footerPublicCategory);

        $footerPrivateCategory = (new PageCategory())
            ->setTitle('footer_private')
            ->setType('grid')
            ->setCreator($user)
        ;
        $this->pageCategoryRepository->update($footerPrivateCategory);

        $menuLinksCategory = (new PageCategory())
            ->setTitle('menu_links')
            ->setType('grid')
            ->setCreator($user)
        ;
        $this->pageCategoryRepository->update($menuLinksCategory);

        // Categories for extra content in admin blocks.
        foreach (PageCategory::ADMIN_BLOCKS_CATEGORIES as $nameBlock) {
            $usersAdminBlock = (new PageCategory())
                ->setTitle($nameBlock)
                ->setType('grid')
                ->setCreator($user)
            ;
            $this->pageCategoryRepository->update($usersAdminBlock);
        }

        $publicCategory = (new PageCategory())
            ->setTitle('public')
            ->setType('grid')
            ->setCreator($user)
        ;
        $this->pageCategoryRepository->update($publicCategory);

        $introductionCategory = (new PageCategory())
            ->setTitle('introduction')
            ->setType('grid')
            ->setCreator($user)
        ;
        $this->pageCategoryRepository->update($introductionCategory);

        return true;
    }

    public function isCustomPagesEnabled(): bool
    {
        return 'true' === $this->settingsManager->getSetting('platform.use_custom_pages', true);
    }

    public function getCustomAccessPageResponse(Request $request): ?Response
    {
        if (!$request->isMethod(Request::METHOD_GET)) {
            return null;
        }

        if (!$this->isCustomPagesEnabled()) {
            return null;
        }

        if ($request->query->has('normal')) {
            return null;
        }

        $pathInfo = rtrim((string) $request->getPathInfo(), '/');

        if ('' === $pathInfo) {
            $pathInfo = '/';
        }

        if (!\in_array($pathInfo, ['/', '/login'], true)) {
            return null;
        }

        if ($request->query->has('loggedout')) {
            return $this->getCustomPageResponse(self::CUSTOM_PAGE_LOGGED_OUT, [
                'request' => $request,
            ]);
        }

        return $this->getCustomPageResponse(self::CUSTOM_PAGE_INDEX_UNLOGGED, [
            'request' => $request,
        ]);
    }

    public function getCustomPageResponse(string $page, array $content = []): ?Response
    {
        if (!$this->isCustomPagesEnabled()) {
            return null;
        }

        if (!\in_array($page, self::CUSTOM_PAGES, true)) {
            return null;
        }

        $file = $this->resolveCustomPageFile($page);

        if (null === $file) {
            return null;
        }

        $output = $this->renderCustomPhpPage($file, $page, $content);

        return new Response(
            $output,
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/html; charset=UTF-8',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Checks if a document file URL is effectively exposed through a visible system announcement.
     *
     * This centralizes the logic used by different parts of the platform (e.g. voters, controllers)
     * to decide if a file coming from personal files can be considered "public" because it is
     * embedded inside a system announcement that is visible to the current user.
     *
     * @param string             $pathInfo   Full request path (e.g. /r/document/files/{uuid}/view)
     * @param string|null        $identifier File identifier extracted from the URL (usually a UUID)
     * @param UserInterface|null $user       Current user, or null to behave as anonymous
     * @param string             $locale     Current locale used to fetch announcements
     */
    public function isFilePathExposedByVisibleAnnouncement(
        string $pathInfo,
        ?string $identifier,
        ?UserInterface $user,
        string $locale
    ): bool {
        // Only relax security for the document file viewer route.
        if ('' === $pathInfo || !str_contains($pathInfo, '/r/document/files/')) {
            return false;
        }

        // Normalize user: if no authenticated user is provided, behave as anonymous.
        if (null === $user) {
            $anon = new User();
            $anon->setRoles(['ROLE_ANONYMOUS']);
            $user = $anon;
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();

        // Fetch announcements that are visible for the given user, URL and locale.
        $announcements = $this->sysAnnouncementRepository->getAnnouncements(
            $user,
            $accessUrl,
            $locale
        );

        foreach ($announcements as $item) {
            $content = '';

            if (\is_array($item)) {
                $content = (string) ($item['content'] ?? '');
            } elseif (\is_object($item) && method_exists($item, 'getContent')) {
                $content = (string) $item->getContent();
            }

            if ('' === $content) {
                continue;
            }

            // Check if the announcement HTML contains the viewer path or the identifier.
            if (
                str_contains($content, $pathInfo)
                || ($identifier && str_contains($content, $identifier))
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns CSS classes that identify the current "global page type".
     *
     * @return string[]
     */
    public function getPageTypeCssClasses(Request $request): array
    {
        $pathInfo = rtrim((string) $request->getPathInfo(), '/');
        if ('' === $pathInfo) {
            $pathInfo = '/';
        }

        // For legacy PHP script URLs (/main/.../*.php), Symfony may return "/" as pathInfo.
        // In that case, rely on REQUEST_URI (path part only) to infer the real page type.
        $requestUri = (string) $request->server->get('REQUEST_URI', '');
        $uriPath = (string) (parse_url($requestUri, PHP_URL_PATH) ?? '');
        $uriPath = rtrim($uriPath, '/');
        if ('' === $uriPath) {
            $uriPath = '/';
        }

        // Use REQUEST_URI path when pathInfo is "/" but the actual URL path is not "/".
        $effectivePath = $pathInfo;
        if ('/' === $pathInfo && '/' !== $uriPath) {
            $effectivePath = $uriPath;
        }

        $segments = array_values(array_filter(
            explode('/', trim($effectivePath, '/')),
            static fn ($v) => '' !== $v
        ));
        $seg0 = $segments[0] ?? '';
        $seg1 = $segments[1] ?? '';
        $seg2 = $segments[2] ?? '';

        // Home (only when the real URL path is "/")
        if ('/' === $effectivePath) {
            return ['page-home'];
        }

        if ('home' === $seg0) {
            return ['page-home'];
        }

        if ('courses' === $seg0) {
            return ['page-my-courses'];
        }

        if ('catalogue' === $seg0) {
            return ['page-catalogue'];
        }

        if ('agenda' === $seg0 || 'calendar' === $seg0) {
            return ['page-agenda'];
        }

        if ('tracking' === $seg0) {
            return ['page-tracking'];
        }

        if ('social' === $seg0) {
            return ['page-social'];
        }

        if ('account' === $seg0) {
            return ['page-account-security'];
        }

        if ('admin-dashboard' === $seg0) {
            return ['page-administration', 'page-administration-session'];
        }

        // Administration + sub-blocks (Vue)
        if ('admin' === $seg0) {
            $classes = ['page-administration'];

            if ('' !== $seg1) {
                // Example: /admin/settings -> page-administration page-administration-settings
                $classes[] = 'page-administration-'.$this->slugCss($seg1);
            }

            // Most Vue admin pages are platform-level.
            if (!\in_array('page-administration-platform', $classes, true)) {
                $classes[] = 'page-administration-platform';
            }

            return array_values(array_unique($classes));
        }

        // Vue "resources" routes -> optional tool markers
        // Example: /resources/document/... -> page-tool page-tool-document
        if ('resources' === $seg0 && '' !== $seg1) {
            return ['page-tool', 'page-tool-'.$this->slugCss($seg1)];
        }

        // Legacy PHP pages under /main/*
        if ('main' === $seg0 && '' !== $seg1) {
            // Tracking must share the same marker across all its pages.
            if ('tracking' === $seg1) {
                return ['page-tracking'];
            }

            // Legacy administration pages are NOT tools.
            // Examples:
            // - /main/admin/user_list.php    -> page-administration page-administration-user
            // - /main/admin/course_add.php   -> page-administration page-administration-course
            // - /main/admin/session_list.php -> page-administration page-administration-session
            if ('admin' === $seg1) {
                $classes = ['page-administration'];

                // Try to detect admin sub-block from the script filename (seg2), or fallback to SCRIPT_NAME.
                $scriptFile = $seg2;
                if ('' === $scriptFile) {
                    $scriptName = (string) $request->server->get('SCRIPT_NAME', '');
                    $scriptFile = basename($scriptName);
                }

                $block = $this->detectLegacyAdminBlock($scriptFile);
                $classes[] = 'page-administration-'.$block;

                // Ensure we always have a stable "platform" marker when no specific block applies.
                if (!\in_array('page-administration-platform', $classes, true)
                    && !\in_array('page-administration-user', $classes, true)
                    && !\in_array('page-administration-course', $classes, true)
                    && !\in_array('page-administration-session', $classes, true)
                ) {
                    $classes[] = 'page-administration-platform';
                }

                return array_values(array_unique($classes));
            }

            // Other legacy tools: /main/<tool>/* -> page-tool + page-tool-<tool>
            return ['page-tool', 'page-tool-'.$this->slugCss($seg1)];
        }

        // Legacy tools fallback by script name (extra safety)
        $script = (string) $request->server->get('SCRIPT_NAME', '');
        if (preg_match('#/main/([a-z_]+)/#', $script, $m)) {
            $tool = (string) $m[1];

            if ('tracking' === $tool) {
                return ['page-tracking'];
            }

            // Legacy administration pages are NOT tools.
            if ('admin' === $tool) {
                $classes = ['page-administration'];
                $classes[] = 'page-administration-'.$this->detectLegacyAdminBlock(basename($script));

                // Safe default when no specific block applies.
                if (!\in_array('page-administration-platform', $classes, true)
                    && !\in_array('page-administration-user', $classes, true)
                    && !\in_array('page-administration-course', $classes, true)
                    && !\in_array('page-administration-session', $classes, true)
                ) {
                    $classes[] = 'page-administration-platform';
                }

                return array_values(array_unique($classes));
            }

            return ['page-tool', 'page-tool-'.$this->slugCss($tool)];
        }

        // Generic fallback: page-<first segment>
        if ('' !== $seg0) {
            return ['page-'.$this->slugCss($seg0)];
        }

        return ['page-generic'];
    }

    /**
     * Detect legacy admin block from filename.
     * Keeps theming markers stable without hardcoding every admin file.
     */
    private function detectLegacyAdminBlock(string $scriptFile): string
    {
        $file = strtolower($scriptFile);

        if (str_starts_with($file, 'user_') || str_starts_with($file, 'user-')) {
            return 'user';
        }

        if (str_starts_with($file, 'course_') || str_starts_with($file, 'course-')) {
            return 'course';
        }

        if (str_starts_with($file, 'session_') || str_starts_with($file, 'session-')) {
            return 'session';
        }

        return 'platform';
    }

    /**
     * Converts a string into a safe CSS class fragment.
     */
    private function slugCss(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9\-_]+/', '-', $value) ?? $value;
        $value = trim($value, '-');

        return '' !== $value ? $value : 'generic';
    }

    private function resolveCustomPageFile(string $page): ?string
    {
        $relativePath = self::CUSTOM_PAGE_DIRECTORY.\DIRECTORY_SEPARATOR.$page.'.'.self::CUSTOM_PAGE_EXTENSION;
        $themesBasePath = $this->projectDir.\DIRECTORY_SEPARATOR.'var'.\DIRECTORY_SEPARATOR.'themes';

        $themeCandidates = [
            $this->themeHelper->getVisualTheme(),
            ThemeHelper::DEFAULT_THEME,
        ];

        foreach (array_unique($themeCandidates) as $theme) {
            $theme = trim($theme);

            if ('' === $theme) {
                continue;
            }

            $customPagesDirectory = $themesBasePath.\DIRECTORY_SEPARATOR.$theme.\DIRECTORY_SEPARATOR.self::CUSTOM_PAGE_DIRECTORY;
            $candidate = $customPagesDirectory.\DIRECTORY_SEPARATOR.$page.'.'.self::CUSTOM_PAGE_EXTENSION;

            $realDirectory = realpath($customPagesDirectory);
            $realFile = realpath($candidate);

            if (false === $realDirectory || false === $realFile) {
                continue;
            }

            if (!str_starts_with($realFile, $realDirectory.\DIRECTORY_SEPARATOR)) {
                continue;
            }

            if (!is_file($realFile) || !is_readable($realFile)) {
                continue;
            }

            return $realFile;
        }

        return null;
    }

    private function renderCustomPhpPage(string $file, string $page, array $content): string
    {
        $render = static function (string $customPageFile, string $customPageName, array $content): void {
            $pageName = $customPageName;

            include $customPageFile;
        };

        ob_start();

        try {
            $render($file, $page, $content);

            return (string) ob_get_clean();
        } catch (Throwable $throwable) {
            ob_end_clean();

            throw $throwable;
        }
    }
}

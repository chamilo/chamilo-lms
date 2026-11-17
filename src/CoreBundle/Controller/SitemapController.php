<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use Chamilo\CoreBundle\Helpers\SitemapCourseProvider;
use Chamilo\CoreBundle\Helpers\SitemapXmlBuilder;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use const PHP_URL_HOST;
use const PHP_URL_SCHEME;

final class SitemapController extends AbstractController
{
    public function __construct(
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly PluginHelper $pluginHelper,
        private readonly SettingsManager $settingsManager,
        private readonly SitemapCourseProvider $courseProvider,
        private readonly SitemapXmlBuilder $xmlBuilder,
    ) {}

    #[Route('/sitemap.xml', name: 'chamilo_sitemap_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        [$accessUrl, $baseUrl] = $this->resolvePublicPortal($request);

        $sitemaps = [$baseUrl.'sitemap-pages.xml'];

        if ($this->isCourseCataloguePublic()) {
            $count = $this->courseProvider->countPublicCourses($accessUrl);
            $pages = $count > 0 ? intdiv($count - 1, SitemapCourseProvider::PAGE_SIZE) + 1 : 0;

            for ($page = 1; $page <= $pages; ++$page) {
                $sitemaps[] = $baseUrl.'sitemap-courses-'.$page.'.xml';
            }
        }

        return $this->xmlResponse($this->xmlBuilder->index($sitemaps));
    }

    #[Route('/sitemap-pages.xml', name: 'chamilo_sitemap_pages', methods: ['GET'])]
    public function pages(Request $request): Response
    {
        [, $baseUrl] = $this->resolvePublicPortal($request);

        $urls = [$baseUrl];
        if ($this->isCourseCataloguePublic()) {
            $urls[] = $baseUrl.'catalogue/courses';
        }
        if ($this->isSessionCataloguePublic()) {
            $urls[] = $baseUrl.'catalogue/sessions';
        }

        return $this->xmlResponse($this->xmlBuilder->urlSet($urls));
    }

    #[Route('/sitemap-courses-{page}.xml', name: 'chamilo_sitemap_courses', requirements: ['page' => '[1-9][0-9]*'], methods: ['GET'])]
    public function courses(Request $request, int $page): Response
    {
        [$accessUrl, $baseUrl] = $this->resolvePublicPortal($request);

        if (!$this->isCourseCataloguePublic()) {
            throw $this->createNotFoundException();
        }

        $total = $this->courseProvider->countPublicCourses($accessUrl);
        if ($total < 1 || $page > intdiv($total - 1, SitemapCourseProvider::PAGE_SIZE) + 1) {
            throw $this->createNotFoundException();
        }

        $ids = $this->courseProvider->findPublicCourseIds($accessUrl, $page);
        if ([] === $ids) {
            throw $this->createNotFoundException();
        }

        $urls = array_map(static fn (int $id): string => $baseUrl.'course/'.$id.'/about', $ids);

        return $this->xmlResponse($this->xmlBuilder->urlSet($urls));
    }

    /**
     * @return array{AccessUrl, string}
     */
    private function resolvePublicPortal(Request $request): array
    {
        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (!$accessUrl instanceof AccessUrl || $this->pluginHelper->isPluginEnabled('NoSearchIndex')) {
            throw $this->createNotFoundException();
        }

        // AccessUrlHelper can fall back to the first portal for an unknown host.
        // A sitemap must never publish the first portal under a different multi-URL host.
        if ($this->accessUrlHelper->isMultiple()) {
            $configuredOrigin = rtrim($accessUrl->getUrl(), '/').'/';
            $requestOrigin = $request->getSchemeAndHttpHost().'/';
            if (0 !== strcasecmp($configuredOrigin, $requestOrigin)) {
                throw $this->createNotFoundException();
            }
        }

        $baseUrl = $this->accessUrlHelper->getCurrentBaseUrl();
        $scheme = parse_url($baseUrl, PHP_URL_SCHEME);
        $host = parse_url($baseUrl, PHP_URL_HOST);
        if (!\in_array($scheme, ['http', 'https'], true) || !\is_string($host) || '' === $host) {
            throw $this->createNotFoundException();
        }

        return [$accessUrl, rtrim($baseUrl, '/').'/'];
    }

    private function isCourseCataloguePublic(): bool
    {
        return $this->isCataloguePublished()
            && \in_array((string) $this->settingsManager->getSetting('catalog.show_courses_sessions', true), ['0', '2'], true);
    }

    private function isSessionCataloguePublic(): bool
    {
        return $this->isCataloguePublished()
            && \in_array((string) $this->settingsManager->getSetting('catalog.show_courses_sessions', true), ['1', '2'], true);
    }

    private function isCataloguePublished(): bool
    {
        return 'true' === (string) $this->settingsManager->getSetting('catalog.course_catalog_published', true);
    }

    private function xmlResponse(string $content): Response
    {
        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/pages')]
class PageController extends AbstractController
{
    public function __construct(
        private readonly AccessUrlHelper $accessUrlHelper
    ) {}

    /**
     * Public endpoint consumed by the top bar (not logged-in).
     * It returns which entries should be visible for current URL and locale.
     *
     * GET /pages/_topbar-visibility?locale=es_spanish
     */
    #[Route('/_topbar-visibility', name: 'public_topbar_visibility', methods: ['GET'])]
    public function topbarVisibility(Request $request, PageRepository $pageRepo): JsonResponse
    {
        $accessUrl = $this->accessUrlHelper->getCurrent();
        $locale    = trim((string) $request->query->get('locale', ''));

        // We first try exact locale, then fallback to the 2-letter prefix.
        $prefix = $locale !== '' ? substr($locale, 0, 2) : '';

        $homeExact   = $locale !== '' ? $pageRepo->countByCategoryAndLocale($accessUrl, 'index', $locale) : 0;
        $homePrefix  = $prefix !== '' ? $pageRepo->countByCategoryAndLocalePrefix($accessUrl, 'index', $prefix) : 0;
        $home        = ($homeExact + $homePrefix) > 0;

        $faqExact    = $locale !== '' ? $pageRepo->countByCategoryAndLocale($accessUrl, 'faq', $locale) : 0;
        $faqPrefix   = $prefix !== '' ? $pageRepo->countByCategoryAndLocalePrefix($accessUrl, 'faq', $prefix) : 0;
        $faq         = ($faqExact + $faqPrefix) > 0;

        $demoExact   = $locale !== '' ? $pageRepo->countByCategoryAndLocale($accessUrl, 'demo', $locale) : 0;
        $demoPrefix  = $prefix !== '' ? $pageRepo->countByCategoryAndLocalePrefix($accessUrl, 'demo', $prefix) : 0;
        $demo        = ($demoExact + $demoPrefix) > 0;

        $contactExact  = $locale !== '' ? $pageRepo->countByCategoryAndLocale($accessUrl, 'contact', $locale) : 0;
        $contactPrefix = $prefix !== '' ? $pageRepo->countByCategoryAndLocalePrefix($accessUrl, 'contact', $prefix) : 0;
        $contact       = ($contactExact + $contactPrefix) > 0;

        return $this->json([
            'home'    => $home,
            'faq'     => $faq,
            'demo'    => $demo,
            'contact' => $contact,
        ]);
    }

    #[Route('/{slug}', name: 'public_page_show', requirements: ['slug' => '^[^_].+'], methods: ['GET'])]
    public function show(string $slug, PageRepository $pageRepo): Response
    {
        $accessUrl = $this->accessUrlHelper->getCurrent();

        $page = $pageRepo->findOneBy([
            'slug' => $slug,
            'enabled' => true,
            'url' => $accessUrl,
        ]);

        if (!$page) {
            throw $this->createNotFoundException('Page not found or not available for this access URL');
        }

        return $this->render('@ChamiloCore/Page/show.html.twig', [
            'page' => $page,
        ]);
    }

    #[Route('/{id}/preview', name: 'admin_page_preview', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function preview(int $id, PageRepository $pageRepo): Response
    {
        $page = $pageRepo->find($id);

        if (!$page) {
            throw $this->createNotFoundException('Page not found');
        }

        return $this->render('@ChamiloCore/Page/preview.html.twig', [
            'page' => $page,
        ]);
    }
}

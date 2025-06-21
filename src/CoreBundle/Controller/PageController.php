<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Repository\PageRepository;
use Chamilo\CoreBundle\Utils\AccessUrlUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/pages')]
class PageController extends AbstractController
{
    public function __construct(
        private readonly AccessUrlUtil $accessUrlUtil
    ) {}

    #[Route('/{slug}', name: 'public_page_show', methods: ['GET'])]
    public function show(string $slug, PageRepository $pageRepo): Response
    {
        $accessUrl = $this->accessUrlUtil->getCurrent();

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

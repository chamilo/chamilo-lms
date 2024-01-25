<?php
declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CoreBundle\Repository\SystemTemplateRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class TemplateController extends AbstractController
{
    #[Route('/system-templates', name: 'system-templates')]
    public function getTemplates(SystemTemplateRepository $templateRepository, AssetRepository $assetRepository): JsonResponse
    {
        $templates = $templateRepository->findAll();

        $data = array_map(function ($template) use ($assetRepository) {
            return [
                'id' => $template->getId(),
                'title' => $template->getTitle(),
                'comment' => $template->getComment(),
                'content' => $template->getContent(),
                'image' => $template->getImage() ? $assetRepository->getAssetUrl($template->getImage()) : null,
            ];
        }, $templates);

        return $this->json($data);
    }
}

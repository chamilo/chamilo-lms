<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Repository\ColorThemeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ThemeController extends AbstractController
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly ColorThemeRepository $colorThemeRepository,
    ) {}

    #[Route('/theme/colors.css', name: 'chamilo_color_theme', methods: ['GET'])]
    public function colorThemeAction(): Response
    {
        $response = new Response('');

        $colorTheme = $this->colorThemeRepository->getActiveOne();

        if ($colorTheme) {
            $fs = new Filesystem();
            $path = $this->parameterBag->get('kernel.project_dir')."/var/theme/{$colorTheme->getSlug()}/colors.css";

            if ($fs->exists($path)) {
                $response = $this->file($path);
            }
        }

        $response->headers->add(['Content-Type' => 'text/css']);

        return $response;
    }
}

<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\ServiceHelper\AccessUrlHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ThemeController extends AbstractController
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly AccessUrlHelper $accessUrlHelper,
    ) {}

    #[Route('/theme/colors.css', name: 'chamilo_color_theme', methods: ['GET'])]
    public function colorTheme(): Response
    {
        $response = new Response(
            null,
            Response::HTTP_OK,
            ['Content-Type' => 'text/css']
        );

        $accesUrlRelColorTheme = $this->accessUrlHelper->getCurrent()->getActiveColorTheme();

        if ($accesUrlRelColorTheme) {
            $colorTheme = $accesUrlRelColorTheme->getColorTheme();

            $fs = new Filesystem();
            $path = $this->parameterBag->get('kernel.project_dir')."/var/themes/{$colorTheme->getSlug()}/colors.css";

            if ($fs->exists($path)) {
                $response = $this->file($path);
            }
        }

        return $response;
    }
}

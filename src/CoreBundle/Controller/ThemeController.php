<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ThemeController extends AbstractController
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
    ) {}

    #[Route('/theme/colors.css', name: 'chamilo_color_theme', methods: ['GET'])]
    public function colorTehemeAction(): Response
    {
        $fs = new Filesystem();
        $path = $this->parameterBag->get('kernel.project_dir').'/var/theme/colors.css';

        if ($fs->exists($path)) {
            $response = $this->file($path);
        } else {
            $response = new Response('');
        }

        $response->headers->add(['Content-Type' => 'text/css']);

        return $response;
    }
}

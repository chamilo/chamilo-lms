<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Helpers\ThemeHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PwaController extends AbstractController
{
    #[Route('/manifest.json', name: 'pwa_manifest')]
    public function manifest(ThemeHelper $themeHelper): Response
    {
        $theme = $themeHelper->getVisualTheme();

        $icon192 = $themeHelper->getThemeAssetUrl('images/pwa-icons/icon-192.png');
        $icon512 = $themeHelper->getThemeAssetUrl('images/pwa-icons/icon-512.png');

        $icons = [];

        if (!empty($icon192)) {
            $icons[] = [
                'src' => $icon192,
                'sizes' => '192x192',
                'type' => 'image/png',
            ];
        }

        if (!empty($icon512)) {
            $icons[] = [
                'src' => $icon512,
                'sizes' => '512x512',
                'type' => 'image/png',
            ];
        }

        $data = [
            'name' => 'Chamilo LMS',
            'short_name' => 'Chamilo',
            'start_url' => '/',
            'display' => 'standalone',
            'theme_color' => '#1b4fa0',
            'background_color' => '#ffffff',
            'orientation' => 'portrait-primary',
            'icons' => $icons,
        ];

        return $this->json($data);
    }
}

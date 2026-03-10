<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use AppPlugin;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use Chamilo\CoreBundle\Repository\PluginRepository;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/plugin-regions')]
class PluginRegionController extends AbstractController
{
    public function __construct(
        private readonly PluginRepository $pluginRepo,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly PluginHelper $pluginHelper,
    ) {}

    #[Route('/{region}', name: 'chamilo_core_plugin_region', methods: ['GET'])]
    public function __invoke(string $region, Request $request): JsonResponse
    {
        if (!in_array($region, AppPlugin::$plugin_regions, true)) {
            throw $this->createNotFoundException('Invalid region: '.$region);
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();

        if (!$accessUrl) {
            throw new LogicException('Access URL not found');
        }

        $installedPlugins = $this->pluginRepo->getInstalledPlugins();
        $appPlugin = new AppPlugin();
        $blocks = [];

        foreach ($installedPlugins as $plugin) {
            if (!$this->pluginHelper->isPluginEnabled($plugin->getTitle())) {
                continue;
            }

            $configByAccessUrl = $plugin->getOrCreatePluginConfiguration($accessUrl);
            $configuration = $configByAccessUrl->getConfiguration();
            $regions = $configuration['regions'] ?? [];

            if (!in_array($region, $regions)) {
                continue;
            }

            $html = $appPlugin->loadRegion(
                $plugin->getTitle(),
                $region,
                self::sanitizeContext($request)
            );

            if ('' === trim($html)) {
                continue;
            }

            $blocks[] = [
                'pluginName' => $plugin->getTitle(),
                'region' => $region,
                'html' => $html,
            ];
        }

        return new JsonResponse(['blocks' => $blocks]);
    }

    private static function sanitizeContext(Request $request): array
    {
        $context = [];

        foreach ($request->query->all() as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $key = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            $context[$key] = htmlspecialchars($value);
        }

        return $context;
    }
}

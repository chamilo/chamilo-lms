<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use AppPlugin;
use Plugin;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Repository\PluginRepository;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/plugin-regions')]
class PluginRegionController extends AbstractController
{
    public function __construct(
        private readonly PluginRepository $pluginRepo,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly CidReqHelper $cidReqHelper,
    ) {}

    #[Route('/{region}', name: 'chamilo_core_plugin_region', methods: ['GET'])]
    public function __invoke(string $region, Request $request): JsonResponse
    {
        if (!\in_array($region, AppPlugin::$plugin_regions, true)) {
            throw $this->createNotFoundException('Invalid region: '.$region);
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();

        if (!$accessUrl) {
            throw new LogicException('Access URL not found');
        }

        $installedPlugins = $this->pluginRepo->getInstalledPlugins();
        $appPlugin = new AppPlugin();
        $context = self::sanitizeContext($request);
        $courseId = $this->cidReqHelper->getCourseId();
        $blocks = [];

        foreach ($installedPlugins as $plugin) {
            $configByAccessUrl = $plugin->getConfigurationsByAccessUrl($accessUrl);

            if (!$configByAccessUrl?->isActive()) {
                continue;
            }

            $regions = $configByAccessUrl->getConfiguration()['regions'] ?? [];

            if (!\in_array($region, $regions)) {
                continue;
            }

            $title = $plugin->getTitle();
            $pluginInfo = $appPlugin->getPluginInfo($title);
            $html = $appPlugin->loadRegion($title, $region, $context);

            if (($pluginInfo['is_course_plugin'] ?? false)
                && $courseId
                && isset($pluginInfo['obj']) && $pluginInfo['obj'] instanceof Plugin
            ) {
                $html .= $pluginInfo['obj']->renderRegion($region);
            }

            if ('' === trim($html)) {
                continue;
            }

            $blocks[] = [
                'pluginName' => $title,
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
            if (!\is_scalar($value)) {
                continue;
            }

            $key = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            $context[$key] = htmlspecialchars($value);
        }

        return $context;
    }
}

<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Twig\Extension;

use AppPlugin;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class PluginRegionExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('chamilo_plugin_region', [$this, 'renderRegion'], ['is_safe' => ['html']]),
        ];
    }

    public function renderRegion(string $region, array $context = []): string
    {
        if (!\in_array($region, AppPlugin::$plugin_regions, true)) {
            return '';
        }

        $appPlugin = AppPlugin::getInstance();
        $pluginList = $appPlugin->getInstalledPluginListObject();

        if (empty($pluginList)) {
            return '';
        }

        $content = '';

        foreach ($pluginList as $plugin) {
            if (!method_exists($plugin, 'get_name')) {
                continue;
            }

            if (!method_exists($plugin, 'isEnabled') || !$plugin->isEnabled(true)) {
                continue;
            }

            $pluginName = $plugin->get_name();
            $regions = $appPlugin->getAreasByPlugin($pluginName);

            if (empty($regions) || !\in_array($region, $regions, true)) {
                continue;
            }

            $regionContent = $appPlugin->loadRegion($pluginName, $region, $context);

            if (!empty($regionContent)) {
                $content .= $regionContent;
            }
        }

        return $content;
    }
}

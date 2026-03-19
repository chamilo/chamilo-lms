<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CoreBundle\Helpers\PluginHelper;
use Chamilo\CourseBundle\Entity\CTool;
use Throwable;

final class LegacyPluginCourseToolResolver
{
    public function __construct(
        private readonly ToolChain $toolChain,
        private readonly PluginHelper $pluginHelper,
    ) {
    }

    /**
     * Resolve a course tool model from:
     * 1. the normal ToolChain handlers
     * 2. a legacy plugin fallback when the tool belongs to an enabled plugin
     *
     * @return array{model: AbstractTool, name: string}|null
     */
    public function resolveFromCTool(CTool $cTool): ?array
    {
        $toolEntity = $cTool->getTool();
        $rawTitle = $toolEntity ? (string) $toolEntity->getTitle() : '';

        foreach ($this->buildNameCandidates($rawTitle) as $candidate) {
            try {
                $model = $this->toolChain->getToolFromName($candidate);

                return [
                    'model' => $model,
                    'name' => $candidate,
                ];
            } catch (Throwable) {
                // Try next candidate.
            }
        }

        $legacyModel = $this->resolveLegacyPluginTool($rawTitle, $cTool->getTitle());
        if (null === $legacyModel) {
            return null;
        }

        return [
            'model' => $legacyModel,
            'name' => $legacyModel->getTitle(),
        ];
    }

    private function resolveLegacyPluginTool(string $rawTitle, string $courseToolTitle): ?LegacyPluginCourseTool
    {
        foreach ($this->buildPluginCandidates($rawTitle) as $pluginName) {
            if (!$this->pluginHelper->isPluginEnabled($pluginName)) {
                continue;
            }

            $plugin = $this->pluginHelper->loadLegacyPlugin($pluginName);
            if (!$plugin) {
                continue;
            }

            if (empty($plugin->addCourseTool)) {
                continue;
            }

            return LegacyPluginCourseTool::fromLegacyPlugin($plugin, $courseToolTitle);
        }

        return null;
    }

    /**
     * Build tool name candidates from DB titles.
     *
     * @return string[]
     */
    private function buildNameCandidates(string $rawTitle): array
    {
        $rawTitle = trim($rawTitle);
        if ('' === $rawTitle) {
            return [];
        }

        $candidates = [];

        $lower = strtolower($rawTitle);
        $candidates[] = $lower;

        if ($rawTitle !== $lower) {
            $candidates[] = $rawTitle;
        }

        $spaceSnake = strtolower(preg_replace('/[\s\-]+/', '_', $rawTitle) ?? $rawTitle);
        $spaceSnake = trim($spaceSnake, '_');
        $candidates[] = $spaceSnake;

        $alnumSnake = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $rawTitle) ?? $rawTitle);
        $alnumSnake = trim($alnumSnake, '_');
        $candidates[] = $alnumSnake;

        $camelSnake = preg_replace('/(?<!^)[A-Z]/', '_$0', $rawTitle) ?? $rawTitle;
        $camelSnake = strtolower($camelSnake);
        $camelSnake = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $camelSnake) ?? $camelSnake);
        $camelSnake = trim($camelSnake, '_');
        $candidates[] = $camelSnake;

        $candidates[] = str_replace('_', '', $camelSnake);
        $candidates[] = str_replace('_', '', $alnumSnake);

        return array_values(array_unique(array_filter($candidates, static fn ($value): bool => \is_string($value) && '' !== trim($value))));
    }

    /**
     * Build plugin title candidates for legacy plugin loading.
     *
     * @return string[]
     */
    private function buildPluginCandidates(string $rawTitle): array
    {
        $rawTitle = trim($rawTitle);
        if ('' === $rawTitle) {
            return [];
        }

        $candidates = [
            $rawTitle,
            ucfirst(strtolower($rawTitle)),
            strtolower($rawTitle),
        ];

        return array_values(array_unique(array_filter($candidates, static fn ($value): bool => '' !== trim($value))));
    }
}

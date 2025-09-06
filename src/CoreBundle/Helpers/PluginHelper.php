<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Plugin as PluginEntity;
use Chamilo\CoreBundle\Repository\AccessUrlRelPluginRepository;
use Chamilo\CoreBundle\Repository\PluginRepository;
use Event;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class PluginHelper
{
    /** @var array<string,string> normalized_title => OriginalTitle */
    private array $titleMap = [];

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly AccessUrlRelPluginRepository $pluginRelRepo,
        private readonly PluginRepository $pluginRepo,
        private readonly AccessUrlHelper $accessUrlHelper,
    ) {
        $this->titleMap = [];
    }

    private static function normalize(string $s): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/i', '', $s));
    }

    private function buildTitleMap(): void
    {
        if (!empty($this->titleMap)) {
            return;
        }
        $all = $this->pluginRepo->findAll();
        foreach ($all as $p) {
            /** @var PluginEntity $p */
            $title = $p->getTitle();
            $norm  = self::normalize($title);
            $this->titleMap[$norm] = $title;
        }
    }

    private function resolveTitle(string $name): ?string
    {
        $this->buildTitleMap();

        $norm = self::normalize($name);
        if (isset($this->titleMap[$norm])) {
            return $this->titleMap[$norm];
        }

        $studly = implode('', array_map('ucfirst', preg_split('/[^a-z0-9]+/i', $name)));
        $candidates = array_unique([
            $name,
            ucfirst(strtolower($name)),
            strtolower($name),
            strtoupper($name),
            $studly,
            self::normalize($studly)
        ]);

        foreach ($candidates as $cand) {
            $normCand = self::normalize((string) $cand);
            if (isset($this->titleMap[$normCand])) {
                return $this->titleMap[$normCand];
            }
        }

        return null;
    }

    public function loadLegacyPlugin(string $pluginName): ?object
    {
        $projectDir = $this->parameterBag->get('kernel.project_dir');

        $cands = array_unique([
            $pluginName,
            implode('', array_map('ucfirst', preg_split('/[^a-z0-9]+/i', $pluginName))),
        ]);

        foreach ($cands as $cand) {
            $pluginPath  = $projectDir.'/public/plugin/'.$cand .'/src/'.$cand .'.php';
            $pluginClass = $cand;

            if (!file_exists($pluginPath)) {
                continue;
            }
            if (!class_exists($pluginClass)) {
                require_once $pluginPath;
            }
            if (class_exists($pluginClass) && method_exists($pluginClass, 'create')) {
                return $pluginClass::create();
            }
        }

        return null;
    }

    public function getPluginSetting(string $pluginName, string $settingKey): mixed
    {
        $plugin = $this->loadLegacyPlugin($pluginName);

        if (!$plugin || !method_exists($plugin, 'get')) {
            return null;
        }

        return $plugin->get($settingKey);
    }

    public function isPluginEnabled(string $pluginName): bool
    {
        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (!$accessUrl instanceof AccessUrl) {
            return false;
        }

        $realTitle = $this->resolveTitle($pluginName);
        if (null === $realTitle) {
            return false;
        }

        $plugin = $this->pluginRepo->findOneBy(['title' => $realTitle]);
        if (!$plugin || !$plugin->isInstalled()) {
            return false;
        }

        $rel = $this->pluginRelRepo->findOneByPluginName($realTitle, $accessUrl->getId());
        return $rel && $rel->isActive();
    }

    public function shouldBlockAccessByPositioning(?int $userId, int $courseId, ?int $sessionId): bool
    {
        if (!$this->isPluginEnabled('Positioning') || !$userId) {
            return false;
        }

        $plugin = $this->loadLegacyPlugin('Positioning');

        if (!$plugin || 'true' !== $plugin->get('block_course_if_initial_exercise_not_attempted')) {
            return false;
        }

        $initialData = $plugin->getInitialExercise($courseId, $sessionId);

        if (empty($initialData['exercise_id'])) {
            return false;
        }

        $results = Event::getExerciseResultsByUser(
            $userId,
            (int) $initialData['exercise_id'],
            $courseId,
            $sessionId
        );

        return empty($results);
    }

    /**
     * Return the whole configuration array for a plugin in the current Access URL,
     * or null if not found.
     */
    public function getPluginConfiguration(string $pluginName): ?array
    {
        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (!$accessUrl instanceof AccessUrl) {
            return null;
        }

        $realTitle = $this->resolveTitle($pluginName);
        if (null === $realTitle) {
            return null;
        }

        $rel = $this->pluginRelRepo->findOneByPluginName($realTitle, $accessUrl->getId());
        if (!$rel) {
            return null;
        }

        $cfg = $rel->getConfiguration();
        return \is_array($cfg) ? $cfg : null;
    }

    /**
     * Get a single configuration value from the plugin configuration JSON.
     * Tries both the plain key ($key) and the legacy-prefixed key ($pluginName.'_'.$key).
     * Falls back to legacy plugin::get($key) if available.
     */
    public function getPluginConfigValue(string $pluginName, string $key, mixed $default = null): mixed
    {
        // Special case for legacy callers expecting "tool_enable"
        if ($key === 'tool_enable') {
            return $this->isPluginEnabled($pluginName) ? 'true' : 'false';
        }

        $cfg = $this->getPluginConfiguration($pluginName);

        if (\is_array($cfg)) {
            // try plain key
            if (\array_key_exists($key, $cfg)) {
                return $cfg[$key];
            }
            // try legacy-prefixed key (some migrations removed this, but keep BC)
            $prefixed = $pluginName . '_' . $key;
            if (\array_key_exists($prefixed, $cfg)) {
                return $cfg[$prefixed];
            }
        }

        // Fallback to legacy plugin object if present
        $legacy = $this->loadLegacyPlugin($pluginName);
        if ($legacy && \method_exists($legacy, 'get')) {
            return $legacy->get($key) ?? $default;
        }

        return $default;
    }
}

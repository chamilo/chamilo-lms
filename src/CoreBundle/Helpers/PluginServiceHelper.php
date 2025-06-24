<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Repository\AccessUrlRelPluginRepository;
use Event;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

readonly class PluginServiceHelper
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private AccessUrlRelPluginRepository $pluginRepo,
        private AccessUrlHelper $accessUrlHelper,
    ) {}

    public function loadLegacyPlugin(string $pluginName): ?object
    {
        $projectDir = $this->parameterBag->get('kernel.project_dir');
        $pluginPath = $projectDir.'/public/plugin/'.$pluginName.'/src/'.$pluginName.'.php';
        $pluginClass = $pluginName;

        if (!file_exists($pluginPath)) {
            return null;
        }

        if (!class_exists($pluginClass)) {
            require_once $pluginPath;
        }

        if (class_exists($pluginClass) && method_exists($pluginClass, 'create')) {
            return $pluginClass::create();
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
        if (null === $accessUrl) {
            return false;
        }

        $pluginSetting = $this->pluginRepo->findOneByPluginName($pluginName, $accessUrl->getId());

        return $pluginSetting && $pluginSetting->isActive();
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
}

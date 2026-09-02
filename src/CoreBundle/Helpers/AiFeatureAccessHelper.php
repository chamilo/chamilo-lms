<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use BuyCoursesPlugin;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Throwable;

final readonly class AiFeatureAccessHelper
{
    public const string MODE_ENABLED = 'true';
    public const string MODE_DISABLED = 'false';
    public const string MODE_PLUGIN_DEFINED = 'plugin_defined';

    private const array FEATURES = [
        'learning_path_generator',
        'exercise_generator',
        'open_answers_grader',
        'tutor_chatbot',
        'task_grader',
        'content_analyser',
        'image_generator',
        'glossary_terms_generator',
        'video_generator',
        'course_analyser',
    ];

    public function __construct(
        private SettingsManager $settingsManager,
    ) {}

    public function isFeatureConfigurableForCourse(string $feature, int $courseId): bool
    {
        if (!$this->isSupportedFeature($feature) || $courseId <= 0 || !$this->isMasterEnabled()) {
            return false;
        }

        $mode = $this->getFeatureMode($feature);

        // The tutor chatbot becomes platform-controlled when globally enabled.
        // Keep the course switch only for plugin-defined (BuyCourses) mode.
        if ('tutor_chatbot' === $feature && self::MODE_ENABLED === $mode) {
            return false;
        }

        if (self::MODE_ENABLED === $mode) {
            return true;
        }

        if (self::MODE_PLUGIN_DEFINED !== $mode) {
            return false;
        }

        return $this->hasActiveBuyCoursesFeature($courseId, $feature);
    }

    public function isFeatureEnabledForCourse(string $feature, int $courseId): bool
    {
        if (!$this->isSupportedFeature($feature) || $courseId <= 0 || !$this->isMasterEnabled()) {
            return false;
        }

        $mode = $this->getFeatureMode($feature);

        // When the tutor is enabled at platform level, it is enabled in every course.
        // plugin_defined intentionally keeps the existing per-course BuyCourses behavior.
        if ('tutor_chatbot' === $feature && self::MODE_ENABLED === $mode) {
            return true;
        }

        if (!$this->isFeatureConfigurableForCourse($feature, $courseId)) {
            return false;
        }

        return 'true' === (string) api_get_course_setting(
            $feature,
            ['real_id' => $courseId],
            true
        );
    }

    public function isFeatureEnabledAtPlatform(string $feature): bool
    {
        return $this->isSupportedFeature($feature)
            && $this->isMasterEnabled()
            && self::MODE_ENABLED === $this->getFeatureMode($feature);
    }

    public function getFeatureMode(string $feature): string
    {
        if (!$this->isSupportedFeature($feature)) {
            return self::MODE_DISABLED;
        }

        $value = strtolower(trim((string) $this->settingsManager->getSetting('ai_helpers.'.$feature, true)));

        return match ($value) {
            self::MODE_ENABLED, '1', 'yes', 'on' => self::MODE_ENABLED,
            self::MODE_PLUGIN_DEFINED, 'plugin-defined' => self::MODE_PLUGIN_DEFINED,
            default => self::MODE_DISABLED,
        };
    }

    private function isMasterEnabled(): bool
    {
        $value = strtolower(trim((string) $this->settingsManager->getSetting('ai_helpers.enable_ai_helpers', true)));

        return \in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    private function isSupportedFeature(string $feature): bool
    {
        return \in_array($feature, self::FEATURES, true);
    }

    private function hasActiveBuyCoursesFeature(int $courseId, string $feature): bool
    {
        try {
            if (!class_exists(BuyCoursesPlugin::class, false)) {
                $pluginPath = api_get_path(SYS_PLUGIN_PATH).'BuyCourses/src/buy_course_plugin.class.php';
                if (!is_file($pluginPath)) {
                    return false;
                }

                require_once $pluginPath;
            }

            if (!class_exists(BuyCoursesPlugin::class, false)) {
                return false;
            }

            $plugin = BuyCoursesPlugin::create();

            if (!$plugin->isEnabled(true) || 'true' !== $plugin->get('include_services')) {
                return false;
            }

            return $plugin->hasActiveAiCourseFeature($courseId, $feature);
        } catch (Throwable $exception) {
            error_log(
                '[AI][PluginDefined] Unable to resolve BuyCourses AI feature. course_id='.
                $courseId.
                ' feature='.
                $feature.
                ' error='.
                $exception->getMessage()
            );

            return false;
        }
    }
}

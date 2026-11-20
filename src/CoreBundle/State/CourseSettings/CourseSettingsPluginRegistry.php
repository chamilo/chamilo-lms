<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseSettings;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\CourseSettingPolicyManager;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use const FILTER_VALIDATE_URL;

/**
 * One registry for built-in and third-party Symfony course-settings plugins.
 */
final readonly class CourseSettingsPluginRegistry
{
    /**
     * @param iterable<CourseSettingsPluginInterface> $plugins
     */
    public function __construct(
        #[AutowireIterator('chamilo.course_settings_plugin')]
        private iterable $plugins,
        private CourseSettingPolicyManager $policies,
    ) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getIntegrations(Course $course, ?Session $session): array
    {
        $integrations = [];
        foreach ($this->plugins as $plugin) {
            $key = $plugin->getIntegrationKey();
            if (isset($integrations[$key])) {
                throw new InvalidArgumentException('Duplicate course settings plugin integration: '.$key);
            }
            $integrations[$key] = $plugin->getIntegration($course, $session);
        }

        return $integrations;
    }

    /**
     * @return array<string, mixed>
     */
    public function getValues(Course $course, ?Session $session): array
    {
        $values = [];
        foreach ($this->plugins as $plugin) {
            $pluginValues = $plugin->getValues($course, $session);
            foreach ($pluginValues as $key => $value) {
                if (\array_key_exists($key, $values)) {
                    throw new InvalidArgumentException('Duplicate course settings plugin field: '.$key);
                }
                $values[$key] = $value;
            }
        }

        return $values;
    }

    /** @param array<string, mixed> $values
     * @return array<int, array<string, mixed>>
     */
    public function getSections(Course $course, ?Session $session, array $values): array
    {
        $sections = [];
        $seen = [];
        foreach ($this->plugins as $plugin) {
            if (empty($plugin->getIntegration($course, $session)['enabled'])) {
                continue;
            }
            $section = $plugin->getSection($course, $session, $values);
            if (null === $section) {
                continue;
            }
            if (!isset($section['key'], $section['title'], $section['icon']) || !\is_array($section['fields'] ?? null)) {
                throw new InvalidArgumentException('Invalid plugin course settings section.');
            }
            if (isset($seen[$section['key']])) {
                throw new InvalidArgumentException('Duplicate course settings plugin section: '.$section['key']);
            }
            $seen[$section['key']] = true;
            $section['fields'] = array_values(array_filter(
                $section['fields'],
                fn (array $field): bool => $this->policies->isEditable((string) $field['key'], 'plugin'),
            ));
            $sections[] = $section;
        }

        return $sections;
    }

    /**
     * Validate plugin fields from the submitted payload against plugin-provided descriptors.
     * Never write arbitrary client-supplied keys or disabled plugin fields.
     *
     * @param array<string, mixed> $submitted
     * @param array<string, mixed> $current
     *
     * @return array<string, mixed>
     */
    public function save(Course $course, ?Session $session, array $submitted, array $current): array
    {
        $saved = [];
        foreach ($this->plugins as $plugin) {
            if (empty($plugin->getIntegration($course, $session)['enabled'])) {
                continue;
            }
            $section = $plugin->getSection($course, $session, $current);
            if (null === $section) {
                continue;
            }
            $validated = [];
            foreach ($section['fields'] as $field) {
                $key = (string) $field['key'];
                if (!\array_key_exists($key, $submitted) || !$this->policies->isEditable($key, 'plugin')
                    || !empty($field['disabled'])) {
                    continue;
                }
                if (\in_array($field['type'], ['external-link', 'readonly-link', 'readonly'], true)) {
                    continue;
                }
                if (\array_key_exists($key, $saved)) {
                    throw new InvalidArgumentException('Duplicate submitted plugin setting: '.$key);
                }
                $validated[$key] = $this->validateValue($key, $submitted[$key], $field);
                $saved[$key] = $validated[$key];
            }
            if ([] !== $validated) {
                // Preserve values from the full course form when an API client sends a partial payload.
                // Only pass this plugin's declared fields to its persistence layer.
                $currentFields = [];
                foreach ($section['fields'] as $field) {
                    $key = (string) $field['key'];
                    if (\array_key_exists($key, $current)) {
                        $currentFields[$key] = $current[$key];
                    }
                }
                $plugin->saveValues(
                    $course,
                    $session,
                    array_replace($plugin->getValues($course, $session), $currentFields, $validated),
                );
            }
        }

        return $saved;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function afterSave(Course $course, ?Session $session, array $values): void
    {
        foreach ($this->plugins as $plugin) {
            if (!empty($plugin->getIntegration($course, $session)['enabled'])) {
                $plugin->afterSave($course, $session, $values);
            }
        }
    }

    public function managesCourseSetting(string $variable, Course $course, ?Session $session): bool
    {
        foreach ($this->plugins as $plugin) {
            if ($plugin->managesCourseSetting($variable)) {
                return !empty($plugin->getIntegration($course, $session)['enabled']);
            }
        }

        return false;
    }

    public function hasActivePaidCourse(Course $course): bool
    {
        foreach ($this->plugins as $plugin) {
            if ($plugin instanceof CourseSettingsPaidCourseProviderInterface && $plugin->hasActivePaidCourse($course)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $field
     */
    private function validateValue(string $key, mixed $value, array $field): mixed
    {
        $type = (string) $field['type'];
        if ('checkbox' === $type) {
            if (!\in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true)) {
                throw new BadRequestHttpException('Invalid checkbox for course setting "'.$key.'".');
            }

            return \in_array($value, [true, 1, '1', 'true'], true);
        }
        if ('number' === $type) {
            if (!is_numeric($value) || !preg_match('/^-?\d+$/', (string) $value)) {
                throw new BadRequestHttpException('Invalid number for course setting "'.$key.'".');
            }
            $number = (int) $value;
            if ((isset($field['min']) && $number < (int) $field['min'])
                || (isset($field['max']) && $number > (int) $field['max'])) {
                throw new BadRequestHttpException('Course setting is outside its allowed range: '.$key);
            }

            return $number;
        }
        if ('checkbox-list' === $type) {
            if (!\is_array($value)) {
                throw new BadRequestHttpException('Invalid list for course setting "'.$key.'".');
            }
            $allowed = array_map('strval', array_column($field['options'] ?? [], 'value'));
            $selected = [];
            foreach ($value as $item) {
                if (!\is_scalar($item) || !\in_array((string) $item, $allowed, true)) {
                    throw new BadRequestHttpException('Invalid list option for course setting "'.$key.'".');
                }
                $selected[] = (string) $item;
            }

            return array_values(array_unique($selected));
        }
        if (!\is_scalar($value) && null !== $value) {
            throw new BadRequestHttpException('Invalid value for course setting "'.$key.'".');
        }
        $normalized = (string) ($value ?? '');
        if (!empty($field['required']) && '' === trim($normalized)) {
            throw new BadRequestHttpException('Required course setting is empty: '.$key);
        }
        if ('url' === $type && '' !== $normalized && false === filter_var($normalized, FILTER_VALIDATE_URL)) {
            throw new BadRequestHttpException('Invalid URL for course setting "'.$key.'".');
        }
        if (\in_array($type, ['select', 'radio'], true)) {
            $allowed = array_column($field['options'] ?? [], 'value');
            if (!\in_array($normalized, array_map('strval', $allowed), true)) {
                throw new BadRequestHttpException('Invalid option for course setting "'.$key.'".');
            }
            foreach ($allowed as $option) {
                if ((string) $option === $normalized) {
                    return $option;
                }
            }
        }

        return $normalized;
    }
}

<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseSettings;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\PluginHelper;

/**
 * Base class for plugin-provided form sections. The default store is c_course_setting.
 */
abstract class AbstractCourseSettingsPlugin implements CourseSettingsPluginInterface
{
    public function __construct(
        protected readonly PluginHelper $pluginHelper,
        protected readonly CourseSettingsPluginStore $pluginStore,
    ) {}

    abstract protected function pluginName(): string;

    public function getIntegration(Course $course, ?Session $session): array
    {
        return ['enabled' => $this->pluginHelper->isPluginEnabled($this->pluginName())];
    }

    public function getSection(Course $course, ?Session $session, array $values): ?array
    {
        return null;
    }

    public function getValues(Course $course, ?Session $session): array
    {
        if (!$this->getIntegration($course, $session)['enabled']) {
            return [];
        }
        $values = [];
        foreach ($this->getSection($course, $session, [])['fields'] ?? [] as $field) {
            if (\in_array($field['type'], ['external-link', 'readonly-link', 'readonly'], true)) {
                continue;
            }
            $key = (string) $field['key'];
            $default = match ($field['type']) {
                'checkbox' => false,
                'number' => 0,
                'select', 'radio' => $field['options'][0]['value'] ?? '',
                default => '',
            };
            $raw = $this->pluginStore->get($course, $key, $default);
            $values[$key] = match ($field['type']) {
                'checkbox' => \in_array(strtolower((string) $raw), ['1', 'true', 'yes', 'on'], true),
                'number' => (int) $raw,
                'checkbox-list' => '' === (string) $raw ? [] : explode(',', (string) $raw),
                'select', 'radio' => $this->matchOptionValue($raw, $field['options'] ?? []),
                default => $raw,
            };
        }

        return $values;
    }

    /**
     * @param array<int, array<string, mixed>> $options
     */
    private function matchOptionValue(mixed $value, array $options): mixed
    {
        foreach ($options as $option) {
            if ((string) ($option['value'] ?? '') === (string) $value) {
                return $option['value'];
            }
        }

        return $value;
    }

    public function saveValues(Course $course, ?Session $session, array $values): void
    {
        foreach ($values as $key => $value) {
            $this->pluginStore->save($course, (string) $key, $value);
        }
    }

    public function afterSave(Course $course, ?Session $session, array $values): void {}

    public function managesCourseSetting(string $variable): bool
    {
        return false;
    }

    /** @param array<int, array<string, mixed>> $fields
     * @return array<string, mixed>
     */
    protected function section(string $key, string $title, string $icon, array $fields, bool $collapsed = false): array
    {
        return ['key' => $key, 'title' => $title, 'icon' => $icon, 'collapsed' => $collapsed, 'fields' => $fields];
    }

    /** @param array<int, array<string, mixed>> $options
     * @return array<string, mixed>
     */
    protected function field(
        string $key,
        string $label,
        string $type,
        array $options = [],
        bool $required = false,
        bool $disabled = false,
        ?int $min = null,
        ?int $max = null,
        string $help = '',
        mixed $value = null,
    ): array {
        return array_filter([
            'key' => $key,
            'label' => $label,
            'type' => $type,
            'options' => $options,
            'required' => $required,
            'disabled' => $disabled,
            'min' => $min,
            'max' => $max,
            'help' => $help,
            'value' => $value,
        ], static fn (mixed $item): bool => null !== $item && [] !== $item && '' !== $item && false !== $item);
    }
}

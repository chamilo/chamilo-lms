<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

/**
 * Evaluates platform overrides without changing the stored course preferences.
 * Missing policies preserve the historic behaviour of the course setting.
 */
final class CourseSettingPolicy
{
    public const string SETTING = 'course_settings_policies';

    /**
     * @return array<string, array{editable: bool, default: string}>
     */
    public static function parse(mixed $json): array
    {
        if (!\is_string($json) || '' === $json) {
            return [];
        }

        $data = json_decode($json, true);
        if (!\is_array($data)) {
            return [];
        }

        $policies = [];
        foreach ($data as $name => $item) {
            if (!\is_string($name) || !preg_match('/^[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)?$/', $name)
                || !\is_array($item)
                || !\array_key_exists('editable', $item)
                || !\is_bool($item['editable'])
                || !\array_key_exists('default', $item)
                || !\is_string($item['default'])) {
                continue;
            }

            $policies[$name] = [
                'editable' => $item['editable'],
                'default' => $item['default'],
            ];
        }

        return $policies;
    }

    /**
     * Empty categories are legacy aliases, not distinct course settings.
     * A variable is ambiguous only when it belongs to separate named categories.
     *
     * @param array<int, string> $categories
     */
    public static function hasConflictingCategories(array $categories): bool
    {
        $named = [];
        foreach ($categories as $category) {
            $category = trim($category);
            if ('' === $category) {
                continue;
            }

            $named[$category] = true;
            if (\count($named) > 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, array{editable: bool, default: string}> $policies
     */
    public static function isEditable(array $policies, string $variable, ?string $category = null): bool
    {
        $policy = self::findPolicy($policies, $variable, $category);

        return $policy['editable'] ?? true;
    }

    /**
     * @param array<string, array{editable: bool, default: string}> $policies
     */
    public static function effectiveValue(
        array $policies,
        string $variable,
        mixed $courseValue,
        mixed $originalDefault,
        ?string $category = null,
    ): mixed {
        $policy = self::findPolicy($policies, $variable, $category);
        if (null === $policy) {
            return $courseValue ?? $originalDefault;
        }

        if (!$policy['editable']) {
            return $policy['default'];
        }

        if (null === $courseValue || ('-1' === $courseValue)) {
            return $policy['default'];
        }

        return $courseValue;
    }

    /**
     * Category-qualified settings take precedence. Unqualified policies remain
     * compatible with previous stored JSON for variables unique to one tool.
     * Never fall back to another tool's category.
     *
     * @param array<string, array{editable: bool, default: string}> $policies
     *
     * @return array{editable: bool, default: string}|null
     */
    private static function findPolicy(array $policies, string $variable, ?string $category): ?array
    {
        if (null !== $category && '' !== $category) {
            $qualified = $category.'.'.$variable;
            if (isset($policies[$qualified])) {
                return $policies[$qualified];
            }
        }

        return $policies[$variable] ?? null;
    }
}

<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\Learnpath;

final class SessionOrderPlanner
{
    /**
     * Build an LP-to-position map by permuting the session LPs' existing slots.
     *
     * Returning null means the submitted order is incomplete or invalid. Keeping
     * the existing slots prevents a session-specific reorder from changing the
     * relative positions reserved by base-course or other-session LPs.
     *
     * @param array<int, array{id: mixed, display_order: mixed}> $currentRows
     * @param array<int, mixed>                                  $orderedIds
     *
     * @return array<int, int>|null
     */
    public static function buildPlan(array $currentRows, array $orderedIds): ?array
    {
        if (empty($currentRows) || count($currentRows) !== count($orderedIds)) {
            return null;
        }

        $normalizedOrder = [];
        foreach ($orderedIds as $id) {
            $normalizedId = self::toPositiveInt($id);
            if (null === $normalizedId) {
                return null;
            }

            $normalizedOrder[] = $normalizedId;
        }

        if (count($normalizedOrder) !== count(array_unique($normalizedOrder))) {
            return null;
        }

        $currentIds = [];
        $positions = [];
        foreach ($currentRows as $row) {
            if (!array_key_exists('id', $row) || !array_key_exists('display_order', $row)) {
                return null;
            }

            $id = self::toPositiveInt($row['id']);
            $position = self::toNonNegativeInt($row['display_order']);
            if (null === $id || null === $position) {
                return null;
            }

            $currentIds[] = $id;
            $positions[] = $position;
        }

        if (
            count($currentIds) !== count(array_unique($currentIds)) ||
            count($positions) !== count(array_unique($positions))
        ) {
            return null;
        }

        $expectedIds = $currentIds;
        $submittedIds = $normalizedOrder;
        sort($expectedIds, SORT_NUMERIC);
        sort($submittedIds, SORT_NUMERIC);
        if ($expectedIds !== $submittedIds) {
            return null;
        }

        sort($positions, SORT_NUMERIC);

        $plan = [];
        foreach ($normalizedOrder as $index => $id) {
            $plan[$id] = $positions[$index];
        }

        return $plan;
    }

    /**
     * @param mixed $value
     */
    private static function toPositiveInt($value): ?int
    {
        $value = self::toNonNegativeInt($value);

        return null !== $value && $value > 0 ? $value : null;
    }

    /**
     * @param mixed $value
     */
    private static function toNonNegativeInt($value): ?int
    {
        if (is_int($value)) {
            return $value >= 0 ? $value : null;
        }

        if (is_string($value) && '' !== $value && ctype_digit($value)) {
            return (int) $value;
        }

        return null;
    }
}

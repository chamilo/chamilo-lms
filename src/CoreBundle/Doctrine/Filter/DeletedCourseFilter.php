<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Doctrine\Filter;

use Chamilo\CoreBundle\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Hides deleted courses from every ORM query (repositories, DQL, joins, API Platform).
 *
 * A deleted course keeps its row, so that its id is never given to another course and the logs
 * that still point to it never attach to a new course. To the ORM, it is as if the row was gone.
 * The filter is enabled by default (config/packages/doctrine.yaml). Code that must see deleted
 * courses, such as the course code uniqueness checks, runs through withDeletedCourses().
 */
final class DeletedCourseFilter extends SQLFilter
{
    public const string NAME = 'deleted_course';

    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        if (Course::class !== $targetEntity->getName()) {
            return '';
        }

        return $targetTableAlias.'.'.$targetEntity->getColumnName('deletedAt').' IS NULL';
    }

    /**
     * Runs $callback with deleted courses visible, then restores the previous filter state.
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    public static function withDeletedCourses(EntityManagerInterface $entityManager, callable $callback): mixed
    {
        $filters = $entityManager->getFilters();
        $wasEnabled = $filters->isEnabled(self::NAME);

        if ($wasEnabled) {
            $filters->disable(self::NAME);
        }

        try {
            return $callback();
        } finally {
            if ($wasEnabled) {
                $filters->enable(self::NAME);
            }
        }
    }
}

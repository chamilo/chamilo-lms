<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * Class GradeBookBackup
 *
 * Represents a Gradebook resource snapshot for copy/export operations.
 * NOTE: This class extends Resource and MUST keep method signatures compatible.
 */
class GradeBookBackup extends Resource
{
    /**
     * Categories included in this gradebook backup.
     *
     * @var array<int, mixed>
     */
    public array $categories;

    /**
     * Constructor.
     *
     * @param array<int, mixed> $categories Categories to be included.
     */
    public function __construct(array $categories)
    {
        // Use a unique id and the proper resource type constant.
        // uniqid with more_entropy=true minimizes collision risk.
        parent::__construct(uniqid('', true), RESOURCE_GRADEBOOK);
        $this->categories = $categories;
    }

    /**
     * Render the gradebook backup summary.
     * IMPORTANT: Keep signature compatible with parent: Resource::show(): void
     */
    public function show(): void
    {
        // Call parent show (if it performs common rendering/initialization).
        parent::show();

        // Do not return any value here; echo/print or set properties instead.
        // Keep user-facing text retrievable via get_lang for localization.
        echo get_lang('All');
    }
}

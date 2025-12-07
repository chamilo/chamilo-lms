<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * Thematic backup script.
 */
class Thematic extends Resource
{
    public $params = [];
    public $thematic_advance_list = [];
    public $thematic_plan_list = [];

    /**
     * Create a new Thematic.
     *
     * @param array $params
     */
    public function __construct($params)
    {
        parent::__construct($params['id'], RESOURCE_THEMATIC);
        $this->params = $params;
    }

    public function show(): void
    {
        parent::show();
        echo $this->params['title'];
    }

    /**
     * @param array $data
     */
    public function addThematicAdvance($data): void
    {
        $this->thematic_advance_list[] = $data;
    }

    /**
     * @param array $data
     */
    public function addThematicPlan($data): void
    {
        $this->thematic_plan_list[] = $data;
    }
}

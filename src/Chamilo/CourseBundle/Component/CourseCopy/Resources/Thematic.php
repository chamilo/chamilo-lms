<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * Thematic backup script.
 *
 * @package chamilo.backup
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

    public function show()
    {
        parent::show();
        echo $this->params['title'];
    }

    /**
     * @param array $data
     */
    public function addThematicAdvance($data)
    {
        $this->thematic_advance_list[] = $data;
    }

    /**
     * @param array $data
     */
    public function addThematicPlan($data)
    {
        $this->thematic_plan_list[] = $data;
    }
}

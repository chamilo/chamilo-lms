<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * Thematic backup script
 * @package chamilo.backup
 */

class Thematic extends Resource
{
    public $params = array();
    public $thematic_advance_list = array();
	public $thematic_plan_list = array();
    
    /**
    * Create a new Thematic
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

    public function add_thematic_advance($data)
    {
        $this->thematic_advance_list[] = $data;
    }

    public function add_thematic_plan($data)
    {
        $this->thematic_plan_list[] = $data;
    }
}

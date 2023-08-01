<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * H5P tool backup script.
 *
 * @package chamilo.backup
 */
class H5pTool extends Resource
{
    public $params = [];

    /**
     * Create a new H5P tool.
     *
     * @param array $params
     */
    public function __construct($params)
    {
        parent::__construct($params['iid'], RESOURCE_H5P_TOOL);
        $this->params = $params;
    }

    public function show()
    {
        parent::show();
        echo $this->params['name'];
    }
}

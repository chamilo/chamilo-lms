<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * Xapi tool backup script.
 *
 * @package chamilo.backup
 */
class XapiTool extends Resource
{
    public $params = [];

    /**
     * Create a new Xapi tool.
     *
     * @param array $params
     */
    public function __construct($params)
    {
        parent::__construct($params['id'], RESOURCE_XAPI_TOOL);
        $this->params = $params;
    }

    public function show()
    {
        parent::show();
        echo $this->params['title'];
    }
}

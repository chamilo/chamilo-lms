<?php
/* For licensing terms, see /license.txt */

require_once 'Resource.class.php';

/**
 * Work/Assignment/Student publication backup script
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 * @package chamilo.backup
 */
class Work extends Resource
{
    public $params = array();

    /**
     * Create a new Work
     *
     * @param array parameters
     */
    public function __construct($params)
    {
        parent::Resource($params['id'], RESOURCE_WORK);
        $this->params = $params;
    }

    public function show()
    {
        parent::show();
        echo $this->params['title'];
    }
}

<?php
/* For licensing terms, see /license.txt */

require_once 'Resource.class.php';

/**
 * Work/Assignment/Student publication backup script
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 * @package chamilo.backup
 */
class Work extends Coursecopy\Resource
{
    public $params = array();

    /**
     * Create a new Work
     *
     * @param array parameters
     */
    public function __construct($params)
    {
        parent::__construct($params['id'], RESOURCE_WORK);
        $this->params = $params;
    }

    public function show()
    {
        parent::show();
        echo $this->params['title'];
    }
}

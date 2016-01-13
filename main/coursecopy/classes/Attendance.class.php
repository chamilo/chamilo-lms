<?php
/* For licensing terms, see /license.txt */

require_once 'Resource.class.php';

/**
 * Attendance backup script
 * @package chamilo.backup
 */

class Attendance extends Coursecopy\Resource
{
    public $params = array();
    public $attendance_calendar = array();

	/**
	 * Create a new Thematic
	 *
	 * @param array parameters
	 */
    public function __construct($params)
    {
		parent::__construct($params['id'], RESOURCE_ATTENDANCE);
		$this->params = $params;
	}

    /**
     * @inheritdoc
     */
    public function show()
    {
		parent::show();
		echo $this->params['name'];
	}

    public function add_attendance_calendar($data)
    {
		$this->attendance_calendar[] = $data;
	}
}

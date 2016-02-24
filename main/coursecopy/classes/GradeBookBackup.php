<?php
/* For licensing terms, see /license.txt */

require_once 'Resource.class.php';

/**
 * Class GradeBookBackup
 */
class GradeBookBackup extends Coursecopy\Resource
{
    public $categories;

    /**
     * GradeBookBackup constructor.
     * @param array $categories
     */
    public function __construct($categories)
    {
        parent::__construct(uniqid(), RESOURCE_GRADEBOOK);
        $this->categories = $categories;
    }

    /**
     * @return string
     */
    public function show()
    {
        parent::show();
        echo get_lang('All');
    }
}
